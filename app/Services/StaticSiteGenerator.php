<?php

namespace App\Services;

use App\Helpers\CleanWiki;
use App\Models\Event;
use App\Models\Generation;
use App\Models\Person;
use App\Models\Quote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Renders the whole site dataset (Generation / Person / Quote / Event) to a tree
 * of Markdown files under a docs root, matching docs/upgrade/url-structure.md.
 *
 * Scope: writes Markdown + copies images only. It never runs mkdocs/mkdox — the
 * static HTML build is a separate, user-run step.
 *
 * Output is deterministic: a run date is injected, all queries are ordered, and
 * no now()/random calls happen here, so re-runs produce byte-identical files.
 */
class StaticSiteGenerator
{
    /** Generation slug overrides where Str::slug(title) != the canonical URL slug. */
    private const CANONICAL_OVERRIDE = [
        'generation-y' => 'millennials',
    ];

    /** Top-level paths this generator owns (safe to wipe on --clean). */
    private const GENERATED_PATHS = [
        'index.md', 'robots.txt', 'img',
        'generations', 'people', 'events', 'quotes',
        'compare', 'born-in', 'what-generation-am-i',
    ];

    private string $docsRoot;

    private string $runDate;

    private int $currentYear;

    private bool $withWiki = false;

    private bool $withImages = false;

    private Collection $generations;

    /** Weight-5 events = defining milestones shown on every generation page. */
    private Collection $milestones;

    /** Weight >= 4 events = key events listed on each born-in/<year> page. */
    private Collection $keyEvents;

    /** @var array<int,string> person id => slug */
    private array $personSlugs = [];

    /** @var array<int,string> event id => slug */
    private array $eventSlugs = [];

    /** @var array<int,string> generation id => canonical slug */
    private array $genSlugs = [];

    private int $written = 0;

    private int $imagesCopied = 0;

    /** @var array<int,string> human-readable list of skipped (Phase B) outputs */
    private array $skipped = [];

    /** @var callable|null */
    private $logger = null;

    public function __construct(private GenerationAnchor $anchor)
    {
    }

    public function setLogger(callable $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param  array{clean?:bool, withWiki?:bool, withImages?:bool, runDate?:string}  $opts
     * @return array{written:int, images:int, skipped:array<int,string>}
     */
    public function generate(string $docsRoot, array $opts = []): array
    {
        $this->docsRoot = rtrim($docsRoot, '/');
        $this->runDate = $opts['runDate'] ?? date('Y-m-d');
        $this->currentYear = (int) substr($this->runDate, 0, 4);
        $this->withWiki = (bool) ($opts['withWiki'] ?? false);
        $this->withImages = (bool) ($opts['withImages'] ?? false);

        if ($opts['clean'] ?? false) {
            $this->cleanGenerated();
        }

        File::ensureDirectoryExists($this->docsRoot);

        // Build slug maps & generation list ONCE up front so every cross-link resolves.
        $this->generations = Generation::orderBy('first_year')->get();
        foreach ($this->generations as $g) {
            $this->genSlugs[$g->id] = $this->canonicalSlug($g);
        }
        $this->personSlugs = $this->buildSlugMap(Person::orderBy('id')->get(), 'name');
        $this->eventSlugs = $this->buildSlugMap(Event::orderBy('id')->get(), 'title');
        $this->keyEvents = Event::where('weight', '>=', 4)->orderBy('happened_at')->get();
        $this->milestones = $this->keyEvents->where('weight', 5)->values();

        $this->log('Building slug maps: '.count($this->genSlugs).' generations, '
            .count($this->personSlugs).' people, '.count($this->eventSlugs).' events');

        $this->writeHome();
        $this->writeGenerationsHub();
        foreach ($this->generations as $g) {
            $this->writeGeneration($g);
        }
        $this->writePeople();
        $this->writeEvents();
        $this->writeQuotesHub();
        $this->writeCompare();
        $this->writeBornIn();
        $this->writeWhatGenerationAmI();
        $this->writeRobots();

        // Phase B (deferred — needs schema not present): record as skipped.
        $this->skipped = [
            'blog/best-of-*, blog/worst-of-* (needs Person.influence polarity + score)',
            'bands/, movies/, tv-shows/, generations/<gen>/influences/ (needs Band/Movie/TvShow tables)',
            'glossary/ term pages (hand-authored content)',
        ];

        return ['written' => $this->written, 'images' => $this->imagesCopied, 'skipped' => $this->skipped];
    }

    // ---------------------------------------------------------------- page writers

    private function writeHome(): void
    {
        $body = "# Generations Explained — Lost Generation to Generation Alpha\n\n";
        $body .= "As of {$this->currentYear}, the people alive today span roughly "
            ."{$this->generations->first()->first_year} to {$this->generations->last()->last_year}, "
            .'grouped into '.$this->generations->count().' named generations. This site is a '
            .'consolidated reference: birth-year ranges, notable people born in each cohort, '
            ."memorable quotes, and the defining events of each generation's life stages.\n\n";

        $body .= "## All generations at a glance\n\n";
        $body .= "<div class=\"grid cards\" markdown>\n\n";
        foreach ($this->generations->sortByDesc('first_year') as $g) {
            $slug = $this->genSlugs[$g->id];
            $link = $this->rel('index.md', $this->genDoc($g));
            $img = $this->image('index.md', $g->image, 'generations', $slug);
            $body .= '-   ';
            if ($img) {
                $body .= "[![{$g->title}]({$img['rel']})]({$link})\n\n    ";
            }
            $body .= "**[{$g->title}]({$link})**\n\n";
            $body .= "    Born {$this->yearRange($g)} · {$this->ageRange($g)} years old in {$this->currentYear}\n\n";
        }
        $body .= "</div>\n\n";
        $body .= "## Explore\n\n";
        $body .= '- [All generations]('.$this->rel('index.md', 'generations/index.md').")\n";
        $body .= '- [Compare two generations]('.$this->rel('index.md', 'compare/index.md').")\n";
        $body .= '- [Notable people A–Z]('.$this->rel('index.md', 'people/index.md').")\n";
        $body .= '- [Defining events]('.$this->rel('index.md', 'events/index.md').")\n";
        $body .= '- [Memorable quotes]('.$this->rel('index.md', 'quotes/index.md').")\n";
        $body .= '- [What generation am I?]('.$this->rel('index.md', 'what-generation-am-i/index.md').")\n";
        $body .= '- [What generation is someone born in a given year?]('.$this->rel('index.md', 'born-in/index.md').")\n";

        $desc = 'A consolidated reference to the human generations from the Lost Generation to '
            .'Generation Alpha: birth-year ranges, notable people, quotes and defining events.';
        $this->write('index.md', $this->page('Generations Explained', $desc, $body));
    }

    private function writeGenerationsHub(): void
    {
        $body = "# Generations: birth-year ranges and key facts\n\n";
        $body .= 'The '.$this->generations->count().' named human generations, oldest first. '
            ."Year ranges are fixed and reused consistently across this site.\n\n";
        $body .= "| Generation | Born | Age in {$this->currentYear} | Also known as |\n";
        $body .= "|---|---|---|---|\n";
        foreach ($this->generations as $g) {
            $link = $this->rel('generations/index.md', $this->genDoc($g));
            $alt = trim((string) $g->alternatives) !== '' ? $g->alternatives : '—';
            $body .= "| [{$g->title}]({$link}) | {$this->yearRange($g)} | {$this->ageRange($g)} | {$alt} |\n";
        }

        $desc = 'All '.$this->generations->count().' human generations with their canonical '
            .'birth-year ranges and current ages, oldest to youngest.';
        $this->write('generations/index.md', $this->page('All generations', $desc, $body));
    }

    private function writeGeneration(Generation $g): void
    {
        $slug = $this->genSlugs[$g->id];
        $base = "generations/{$slug}";
        $people = $this->anchor->peopleBornIn($g);
        $byCategory = $people->groupBy('category')->sortKeys();
        $img = $this->image("{$base}/index.md", $g->image, 'generations', $slug);

        // --- index.md ---
        [$prev, $next] = $this->neighbours($g);

        $body = "# {$g->title}\n\n";

        // Navigation + key-facts table (previous / this / born / ages / next).
        $titleCell = '**'.$g->title.(trim((string) $g->alternatives) !== '' ? ', '.$g->alternatives : '').'**';
        $prevCell = $prev ? "[{$prev->title}](".$this->rel("{$base}/index.md", $this->genDoc($prev)).')' : '';
        $nextCell = $next ? "[{$next->title}](".$this->rel("{$base}/index.md", $this->genDoc($next)).')' : '';
        $ages = $this->ageRange($g) === 'not yet born' ? 'not yet born' : $this->ageRange($g).' year old';
        $body .= "| Previous | This Generation | Born in | Ages in {$this->currentYear} | Next |\n";
        $body .= "|---|---|---|---|---|\n";
        $body .= "| {$prevCell} | {$titleCell} | {$this->yearRange($g)} | {$ages} | {$nextCell} |\n\n";

        if ($img) {
            $body .= "![{$g->title}]({$img['rel']})\n\n";
        }

        $body .= $this->milestonesSection($g, "{$base}/index.md");

        // Explore child pages
        $body .= "## On this generation\n\n";
        if ($people->isNotEmpty()) {
            $body .= "[Notable people of {$g->title}](".$this->rel("{$base}/index.md", "{$base}/famous-people.md").") ({$people->count()})\n\n";
        }
        foreach ($byCategory as $cat => $list) {
            $catSlug = Str::slug((string) $cat);
            $body .= "- [{$this->categoryPlural((string) $cat)} that belong to {$g->title}](".$this->rel("{$base}/index.md", "{$base}/{$catSlug}.md").") ({$list->count()})\n";
        }
        $body .= '- [Memorable quotes about '.$g->title.']('.$this->rel("{$base}/index.md", "{$base}/quotes.md").")\n";
        $body .= '- [Detailed Timeline of defining events]('.$this->rel("{$base}/index.md", "{$base}/timeline.md").")\n\n";

        $body .= $this->generationFaq($g, $people->count(), $prev, $next);

        $desc = "{$g->title}: people born {$this->yearRange($g)}, {$this->ageRange($g)} years old in "
            ."{$this->currentYear}. Notable people, quotes and defining events.";
        $this->write("{$base}/index.md", $this->page($g->title, $desc, $body, $img['fm'] ?? null));

        // --- famous-people.md (overview listicle) — skip entirely when empty (no orphan page) ---
        if ($people->isNotEmpty()) {
            $this->writePeopleListing(
                "{$base}/famous-people.md",
                "Notable people born in the {$g->title} ({$this->yearRange($g)})",
                "The most notable people born between {$g->first_year} and {$g->last_year}, across all fields.",
                $people,
                $g,
                showCategory: true,
            );
        }

        // --- per-category listings ---
        foreach ($byCategory as $cat => $list) {
            $catSlug = Str::slug((string) $cat);
            $this->writePeopleListing(
                "{$base}/{$catSlug}.md",
                "{$this->categoryPlural((string) $cat)} that belong to the {$g->title}",
                "Notable {$this->categoryPlural((string) $cat)} born between {$g->first_year} and {$g->last_year}.",
                $list,
                $g,
                showCategory: false,
            );
        }

        // --- quotes.md ---
        $this->writeGenerationQuotes($g);

        // --- timeline.md ---
        $this->writeTimeline($g);
    }

    private function writePeopleListing(string $doc, string $title, string $desc, Collection $people, Generation $g, bool $showCategory): void
    {
        $body = "# {$title}\n\n";
        if ($people->isEmpty()) {
            $body .= "_No people on record yet for this list._\n";
        } else {
            $body .= "{$desc}\n\n";
            $i = 1;
            foreach ($people as $p) {
                $year = $this->birthYear($p);
                $link = $this->rel($doc, $this->personDoc($p));
                $meta = [];
                if ($showCategory && trim((string) $p->category) !== '') {
                    $meta[] = $this->categoryLabel((string) $p->category);
                }
                if (trim((string) $p->country) !== '') {
                    $meta[] = $p->country;
                }
                $meta[] = "born {$year}";
                $body .= "{$i}. [{$p->name}]({$link}) — ".implode(', ', $meta)."\n";
                $i++;
            }
        }
        $this->write($doc, $this->page($title, $desc, $body));
    }

    private function writeGenerationQuotes(Generation $g): void
    {
        $slug = $this->genSlugs[$g->id];
        $doc = "generations/{$slug}/quotes.md";
        $quotes = Quote::whereGenerationId($g->id)->orderBy('id')->get();

        $body = "# Memorable quotes about the {$g->title}\n\n";
        if ($quotes->isEmpty()) {
            $body .= "_No quotes on record yet._\n";
        } else {
            foreach ($quotes as $q) {
                $body .= "> {$this->clean($q->description)}\n>\n";
                $attr = trim((string) $q->author) !== '' ? $q->author : 'Unknown';
                if (trim((string) $q->url) !== '') {
                    $body .= "> — [{$attr}]({$q->url})\n\n";
                } else {
                    $body .= "> — {$attr}\n\n";
                }
            }
        }
        $desc = "Memorable, attributed quotes about the {$g->title} (born {$this->yearRange($g)}).";
        $this->write($doc, $this->page("{$g->title} quotes", $desc, $body));
    }

    private function writeTimeline(Generation $g): void
    {
        $slug = $this->genSlugs[$g->id];
        $doc = "generations/{$slug}/timeline.md";
        $stages = $this->anchor->eventsByLifeStage($g);
        $labels = [
            'child' => 'Childhood (ages 6–12)',
            'puberty' => 'Teenage years (ages 13–20)',
            'adult' => 'Adulthood (ages 21–60)',
            'retired' => 'Later life (ages 61–80)',
        ];

        $body = "# Defining events in the life of the {$g->title}\n\n";
        $body .= "Events grouped by the life stage the {$g->title} cohort (born {$this->yearRange($g)}) "
            ."was in when they happened.\n\n";
        $any = false;
        foreach ($labels as $stage => $label) {
            $events = $stages[$stage];
            if ($events->isEmpty()) {
                continue;
            }
            $any = true;
            $body .= "## {$label}\n\n";
            foreach ($events as $e) {
                $body .= "- {$this->eventLine($doc, $e)}\n";
            }
            $body .= "\n";
        }
        if (! $any) {
            $body .= "_No events on record for this generation's life stages yet._\n";
        }
        $desc = "Timeline of the defining world events during the {$g->title}'s childhood, "
            .'teenage years, adulthood and later life.';
        $this->write($doc, $this->page("{$g->title} timeline", $desc, $body));
    }

    private function writePeople(): void
    {
        $people = Person::orderBy('name')->get();

        // Hub: A–Z grouped by first letter.
        $body = "# Notable people A–Z\n\n";
        $body .= "Every notable individual on record, anchored to a generation by birth year.\n\n";
        $grouped = $people->groupBy(fn ($p) => strtoupper(substr(trim((string) $p->name), 0, 1) ?: '#'))->sortKeys();
        foreach ($grouped as $letter => $list) {
            $body .= "## {$letter}\n\n";
            foreach ($list as $p) {
                $link = $this->rel('people/index.md', $this->personDoc($p));
                $body .= "- [{$p->name}]({$link}) — born {$this->birthYear($p)}\n";
            }
            $body .= "\n";
        }
        $this->write('people/index.md', $this->page('Notable people A–Z',
            'An A–Z index of every notable person on record, with birth year and generation.', $body));

        foreach ($people as $p) {
            $this->writePerson($p);
        }
    }

    private function writePerson(Person $p): void
    {
        $slug = $this->personSlugs[$p->id];
        $doc = "people/{$slug}.md";
        $year = $this->birthYear($p);
        $gen = $this->anchor->generationForYear((int) $year);
        $img = $this->image($doc, $p->image, 'people', $slug);

        $body = "# {$p->name}\n\n";
        $summaryGen = $gen ? "a member of the {$gen->title}" : 'outside the named generations on this site';
        $body .= "> **{$p->name}** was born in **{$year}**, making them {$summaryGen}";
        if (trim((string) $p->category) !== '') {
            $body .= ". Field: {$this->categoryLabel((string) $p->category)}";
        }
        $body .= ".\n\n";
        if ($img) {
            $body .= "![{$p->name}]({$img['rel']})\n\n";
        }

        $description = $this->clean($p->description);
        if ($description === '' && $this->withWiki) {
            $description = $this->clean((string) CleanWiki::get($p->name, 120));
        }
        if ($description !== '') {
            $body .= "{$description}\n\n";
        }

        $body .= "## Facts\n\n| | |\n|---|---|\n";
        $body .= "| Born | {$year} |\n";
        if (trim((string) $p->category) !== '') {
            $body .= "| Field | {$this->categoryLabel((string) $p->category)} |\n";
        }
        if (trim((string) $p->country) !== '') {
            $body .= "| Country | {$p->country} |\n";
        }
        if ($gen) {
            $body .= "| Generation | [{$gen->title}](".$this->rel($doc, $this->genDoc($gen)).") |\n";
        }
        $body .= "| Born in | [Year {$year}](".$this->rel($doc, "born-in/{$year}.md").") |\n";

        $desc = "{$p->name}, born {$year}"
            .($gen ? ", a member of the {$gen->title}" : '')
            .(trim((string) $p->category) !== '' ? " ({$this->categoryLabel((string) $p->category)})" : '').'.';
        $this->write($doc, $this->page($p->name, $desc, $body, $img['fm'] ?? null));
    }

    private function writeEvents(): void
    {
        $events = Event::orderBy('happened_at')->get();

        // Hub grouped by decade.
        $body = "# Defining events by decade\n\n";
        $body .= 'World events on record, grouped by the decade they happened. Each event page '
            ."breaks down how it touched every living generation by their life stage at the time.\n\n";
        $grouped = $events->groupBy(fn ($e) => $this->decade($this->eventYear($e)).'s')->sortKeys();
        foreach ($grouped as $decade => $list) {
            $body .= "## {$decade}\n\n";
            foreach ($list as $e) {
                $body .= "- {$this->eventLine('events/index.md', $e)}\n";
            }
            $body .= "\n";
        }
        $this->write('events/index.md', $this->page('Defining events by decade',
            'A timeline of defining world events, grouped by decade, each linked to its per-generation impact.', $body));

        foreach ($events as $e) {
            $this->writeEvent($e);
        }
    }

    private function writeEvent(Event $e): void
    {
        $slug = $this->eventSlugs[$e->id];
        $doc = "events/{$slug}.md";
        $year = $this->eventYear($e);

        $body = "# {$e->title}\n\n";
        $body .= "> **{$e->title}** happened in **{$year}**. Below is how it touched each "
            ."generation, based on the life stage they were in at the time.\n\n";
        if (trim((string) $e->description) !== '') {
            $body .= "{$this->clean($e->description)}\n\n";
        }
        if (trim((string) $e->url) !== '') {
            $body .= "Source: [{$e->url}]({$e->url})\n\n";
        }

        $body .= "## Impact by generation ({$year})\n\n";
        $body .= "How old each generation was when this happened, and the life stage they were in.\n\n";
        $body .= "| Generation | Born | Age in {$year} | Life stage |\n|---|---|---|---|\n";
        foreach ($this->generations as $g) {
            $oldest = $year - $g->first_year;   // oldest members' age
            $youngest = $year - $g->last_year;  // youngest members' age
            $link = $this->rel($doc, $this->genDoc($g));
            if ($oldest < 0) {
                $ageCell = 'not yet born';
                $stageCell = '—';
            } elseif ($youngest > 100) {
                $ageCell = 'no longer living';
                $stageCell = '—';
            } else {
                $ageCell = $this->agePhrase(max(0, $youngest), $oldest);
                $stageCell = $oldest < 6 ? '—' : $this->stageRange(max(6, $youngest), $oldest);
            }
            $body .= "| [{$g->title}]({$link}) | {$this->yearRange($g)} | {$ageCell} | {$stageCell} |\n";
        }

        $desc = "{$e->title} ({$year}): what happened and how it shaped each living generation "
            .'by their life stage at the time.';
        $this->write($doc, $this->page($e->title, $desc, $body));
    }

    private function writeQuotesHub(): void
    {
        $body = "# Memorable quotes by generation\n\n";
        foreach ($this->generations as $g) {
            $quotes = Quote::whereGenerationId($g->id)->orderBy('id')->get();
            if ($quotes->isEmpty()) {
                continue;
            }
            $link = $this->rel('quotes/index.md', "generations/{$this->genSlugs[$g->id]}/quotes.md");
            $body .= "## [{$g->title}]({$link})\n\n";
            foreach ($quotes as $q) {
                $attr = trim((string) $q->author) !== '' ? $q->author : 'Unknown';
                $body .= "> {$this->clean($q->description)}\n>\n> — {$attr}\n\n";
            }
        }
        $this->write('quotes/index.md', $this->page('Memorable quotes',
            'Memorable, attributed quotes grouped by generation.', $body));
    }

    private function writeCompare(): void
    {
        $gens = $this->generations->values();
        $pairs = 0;

        // Index: matrix grid.
        $body = "# Compare two generations\n\n";
        $body .= 'Side-by-side comparisons of every pair of generations — birth years, current '
            ."ages and defining people. Pick a row and column.\n\n";
        $body .= '| vs |'.implode('', $gens->map(fn ($g) => " {$g->title} |")->all())."\n";
        $body .= '|---|'.str_repeat('---|', $gens->count())."\n";
        foreach ($gens as $i => $row) {
            $cells = " **{$row->title}** |";
            foreach ($gens as $j => $col) {
                if ($i === $j) {
                    $cells .= ' — |';
                } else {
                    [$older, $newer] = $i < $j ? [$row, $col] : [$col, $row];
                    $pairDoc = $this->compareDoc($older, $newer);
                    $cells .= ' [vs]('.$this->rel('compare/index.md', $pairDoc).') |';
                }
            }
            $body .= "|{$cells}\n";
        }
        $this->write('compare/index.md', $this->page('Compare generations',
            'A matrix of every generation-vs-generation comparison: birth years, ages and key people.', $body));

        // One page per ordered (older, newer) pair.
        for ($i = 0; $i < $gens->count(); $i++) {
            for ($j = $i + 1; $j < $gens->count(); $j++) {
                $this->writeComparePair($gens[$i], $gens[$j]);
                $pairs++;
            }
        }
        $this->log("Compare pages written: {$pairs} (expected C(n,2))");
    }

    private function writeComparePair(Generation $older, Generation $newer): void
    {
        $doc = $this->compareDoc($older, $newer);
        $oPeople = $this->anchor->peopleBornIn($older);
        $nPeople = $this->anchor->peopleBornIn($newer);

        $body = "# {$older->title} vs {$newer->title}\n\n";
        $body .= "> The **{$older->title}** (born {$this->yearRange($older)}) and the "
            ."**{$newer->title}** (born {$this->yearRange($newer)}) compared. In {$this->currentYear} "
            ."they are {$this->ageRange($older)} and {$this->ageRange($newer)} years old respectively.\n\n";

        $oLink = $this->rel($doc, $this->genDoc($older));
        $nLink = $this->rel($doc, $this->genDoc($newer));
        $body .= "| | [{$older->title}]({$oLink}) | [{$newer->title}]({$nLink}) |\n|---|---|---|\n";
        $body .= "| Born | {$this->yearRange($older)} | {$this->yearRange($newer)} |\n";
        $body .= "| Age in {$this->currentYear} | {$this->ageRange($older)} | {$this->ageRange($newer)} |\n";
        $body .= "| Midpoint birth year | {$this->anchor->middleYear($older)} | {$this->anchor->middleYear($newer)} |\n";
        $body .= "| Notable people on record | {$oPeople->count()} | {$nPeople->count()} |\n\n";
        $body .= "_The two cohorts' midpoints are about "
            .abs($this->anchor->middleYear($newer) - $this->anchor->middleYear($older))." years apart._\n\n";

        $body .= "## A few notable people\n\n";
        $body .= "**{$older->title}:** ".$this->namesInline($doc, $oPeople->take(5))."  \n";
        $body .= "**{$newer->title}:** ".$this->namesInline($doc, $nPeople->take(5))."\n";

        $desc = "{$older->title} vs {$newer->title}: birth years ({$this->yearRange($older)} vs "
            ."{$this->yearRange($newer)}), current ages and notable people compared.";
        $this->write($doc, $this->page("{$older->title} vs {$newer->title}", $desc, $body));
    }

    private function writeBornIn(): void
    {
        $minYear = (int) $this->generations->min('first_year');
        $maxYear = min((int) $this->generations->max('last_year'), $this->currentYear);

        // Hub grouped by decade.
        $body = "# What generation is someone born in a given year?\n\n";
        $body .= "Pick a birth year ({$minYear}–{$maxYear}) to see which generation it belongs to "
            ."and who was born then.\n\n";
        for ($d = $this->decade($minYear); $d <= $this->decade($maxYear); $d += 10) {
            $body .= "- [{$d}s](".$this->rel('born-in/index.md', "born-in/{$d}s.md").")\n";
        }
        $this->write('born-in/index.md', $this->page('Birth year lookup',
            "Find which generation a person belongs to by birth year ({$minYear}–{$maxYear}).", $body));

        // Decade indexes.
        for ($d = $this->decade($minYear); $d <= $this->decade($maxYear); $d += 10) {
            $dbody = "# Born in the {$d}s\n\n";
            for ($y = max($d, $minYear); $y <= min($d + 9, $maxYear); $y++) {
                $g = $this->anchor->generationForYear($y);
                $tag = $g ? " — {$g->title}" : '';
                $dbody .= "- [Born in {$y}](".$this->rel("born-in/{$d}s.md", "born-in/{$y}.md")."){$tag}\n";
            }
            $this->write("born-in/{$d}s.md", $this->page("Born in the {$d}s",
                "Every birth year in the {$d}s and which generation it belongs to.", $dbody));
        }

        // One page per year.
        for ($y = $minYear; $y <= $maxYear; $y++) {
            $this->writeBornInYear($y);
        }
    }

    private function writeBornInYear(int $year): void
    {
        $doc = "born-in/{$year}.md";
        $g = $this->anchor->generationForYear($year);
        $age = $this->currentYear - $year;
        $people = Person::whereBetween('born_at', [$this->anchor->fmt($year), $this->anchor->fmt($year + 1)])
            ->orderBy('name')->get();

        $body = "# What generation is someone born in {$year}?\n\n";
        if ($g) {
            $body .= "> Someone born in **{$year}** belongs to the **{$g->title}** "
                ."(born {$this->yearRange($g)}). In {$this->currentYear} they are **{$age} years old**.\n\n";
            $body .= "- Generation: [{$g->title}](".$this->rel($doc, $this->genDoc($g)).")\n";
            $body .= "- Age in {$this->currentYear}: {$age}\n\n";
        } else {
            $body .= "> Birth year **{$year}** falls outside the named generations on this site.\n\n";
        }
        if ($people->isNotEmpty()) {
            $body .= "## Notable people born in {$year}\n\n";
            foreach ($people as $p) {
                $body .= "- [{$p->name}](".$this->rel($doc, $this->personDoc($p)).")\n";
            }
            $body .= "\n";
        }

        // Key events (weight >= 4) from age 6 onwards (younger than that they won't recall).
        $lifeEvents = $this->keyEvents->filter(function ($e) use ($year) {
            $age = $this->eventYear($e) - $year;

            return $age >= 6 && $age <= 100;
        });
        if ($lifeEvents->isNotEmpty()) {
            $body .= "## Major events in their lifetime\n\n";
            $body .= "How old someone born in {$year} was when each defining event happened.\n\n";
            $body .= "| Year | Event | Their age | Life stage |\n|---|---|---|---|\n";
            foreach ($lifeEvents as $e) {
                $eventYear = $this->eventYear($e);
                $age = $eventYear - $year;
                $link = $this->rel($doc, "events/{$this->eventSlugs[$e->id]}.md");
                $body .= "| {$eventYear} | [{$e->title}]({$link}) | {$age} | {$this->ageStage($age)} |\n";
            }
            $body .= "\n";
        }

        $desc = $g
            ? "A person born in {$year} belongs to the {$g->title} and is {$age} years old in {$this->currentYear}."
            : "Birth year {$year} and its generation.";
        $this->write($doc, $this->page("Born in {$year}", $desc, $body));
    }

    private function writeWhatGenerationAmI(): void
    {
        $body = "# What generation am I?\n\n";
        $body .= 'Find your generation from your birth year. Ranges are fixed and used consistently '
            ."across this site.\n\n";
        $body .= "| If you were born… | You are… | Age in {$this->currentYear} |\n|---|---|---|\n";
        foreach ($this->generations as $g) {
            $link = $this->rel('what-generation-am-i/index.md', $this->genDoc($g));
            $body .= "| {$this->yearRange($g)} | [{$g->title}]({$link}) | {$this->ageRange($g)} |\n";
        }
        $body .= "\nLooking for a specific year? See the "
            .'[birth-year lookup]('.$this->rel('what-generation-am-i/index.md', 'born-in/index.md').").\n";
        $this->write('what-generation-am-i/index.md', $this->page('What generation am I?',
            'Find your generation from your birth year, with current ages for each cohort.', $body));
    }

    private function writeRobots(): void
    {
        $robots = "# generation.forret.com — AI crawlers welcome\n"
            ."User-agent: *\n"
            ."Allow: /\n\n"
            ."# Named AI crawlers explicitly allowed\n"
            ."User-agent: GPTBot\nAllow: /\n\n"
            ."User-agent: ClaudeBot\nAllow: /\n\n"
            ."User-agent: PerplexityBot\nAllow: /\n\n"
            ."User-agent: Google-Extended\nAllow: /\n\n"
            ."Sitemap: https://generation.forret.com/sitemap.xml\n";
        $this->write('robots.txt', $robots);
    }

    // ---------------------------------------------------------------- helpers

    private function clean(?string $text): string
    {
        $text = trim((string) $text);
        // Collapse newlines so descriptions sit on one logical paragraph (markdown-safe).
        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    /**
     * "How old the cohort was at each defining (weight-5) milestone."
     * Only milestones the generation actually lived through are shown.
     */
    private function milestonesSection(Generation $g, string $fromDoc): string
    {
        $rows = [];
        foreach ($this->milestones as $e) {
            $eventYear = $this->eventYear($e);
            $oldest = $eventYear - $g->first_year;   // age of the cohort's oldest members
            $youngest = $eventYear - $g->last_year;  // age of the cohort's youngest members
            if ($oldest < 0) {
                continue; // generation not born yet
            }
            if ($youngest > 100) {
                continue; // generation essentially no longer living
            }
            $age = $this->agePhrase(max(0, $youngest), $oldest);
            $link = $this->rel($fromDoc, "events/{$this->eventSlugs[$e->id]}.md");
            $rows[] = "| {$eventYear} | [{$e->title}]({$link}) | {$age} |";
        }

        if ($rows === []) {
            return '';
        }

        $out = "## How old the {$g->title} were at key moments\n\n";
        $out .= "The age of this cohort when each defining event happened.\n\n";
        $out .= "| Year | Event | Their age |\n|---|---|---|\n";
        $out .= implode("\n", $rows)."\n\n";

        return $out;
    }

    private function agePhrase(int $youngest, int $oldest): string
    {
        if ($youngest === $oldest) {
            return "{$youngest}";
        }
        if ($youngest === 0) {
            return "newborn–{$oldest}";
        }

        return "{$youngest}–{$oldest}";
    }

    private function generationFaq(Generation $g, int $count, ?Generation $prev, ?Generation $next): string
    {
        $faq = "## Frequently asked questions\n\n";
        $faq .= "### When were the {$g->title} born?\n\n";
        $faq .= "The {$g->title} were born between {$g->first_year} and {$g->last_year}.\n\n";
        $faq .= "### How old are the {$g->title} in {$this->currentYear}?\n\n";
        $faq .= "In {$this->currentYear} the {$g->title} are {$this->ageRange($g)} years old.\n\n";
        $faq .= "### What generation comes after the {$g->title}?\n\n";
        $faq .= ($next
            ? "The {$next->title} (born {$this->yearRange($next)}) come after the {$g->title}."
            : "The {$g->title} are the youngest named generation on this site.")."\n\n";
        $faq .= "### What generation came before the {$g->title}?\n\n";
        $faq .= ($prev
            ? "The {$prev->title} (born {$this->yearRange($prev)}) came before the {$g->title}."
            : "The {$g->title} are the oldest named generation on this site.")."\n\n";
        $faq .= "### How many notable people were born in the {$g->title}?\n\n";
        $faq .= ($count > 0
            ? "This site lists {$count} notable people born in the {$g->title}."
            : "No notable people are on record for the {$g->title} yet.")."\n";

        return $faq;
    }

    /** @return array{0: ?Generation, 1: ?Generation} [previous (older), next (younger)] */
    private function neighbours(Generation $g): array
    {
        $vals = $this->generations->values();
        $idx = $vals->search(fn ($x) => $x->id === $g->id);

        return [
            $idx > 0 ? $vals[$idx - 1] : null,
            $idx < $vals->count() - 1 ? $vals[$idx + 1] : null,
        ];
    }

    private function eventLine(string $fromDoc, Event $e): string
    {
        $link = $this->rel($fromDoc, "events/{$this->eventSlugs[$e->id]}.md");
        $year = $this->eventYear($e);

        return "**{$year}** — [{$e->title}]({$link})";
    }

    private function namesInline(string $fromDoc, Collection $people): string
    {
        if ($people->isEmpty()) {
            return '_none on record_';
        }

        return $people->map(fn ($p) => "[{$p->name}](".$this->rel($fromDoc, $this->personDoc($p)).')')
            ->implode(', ');
    }

    private function categoryLabel(string $category): string
    {
        return trim($category) === '' ? 'Notable' : ucfirst(strtolower($category));
    }

    /** Plural "person noun" for a category (Politics → Politicians, Personality → Personalities). */
    private function categoryPlural(string $category): string
    {
        $map = [
            'actor' => 'Actors',
            'comedian' => 'Comedians',
            'director' => 'Directors',
            'musician' => 'Musicians',
            'personality' => 'Personalities',
            'politics' => 'Politicians',
            'religion' => 'Religious figures',
            'business' => 'Business leaders',
            'sports' => 'Sportspeople',
        ];

        return $map[strtolower(trim($category))] ?? Str::plural($this->categoryLabel($category));
    }

    private function yearRange(Generation $g): string
    {
        return "{$g->first_year}–{$g->last_year}";
    }

    private function ageRange(Generation $g): string
    {
        $youngest = $this->currentYear - $g->last_year;
        $oldest = $this->currentYear - $g->first_year;
        if ($youngest < 0) {
            return 'not yet born';
        }

        return "{$youngest}–{$oldest}";
    }

    /** Life-stage label for a given age (events are only listed from age 6 onwards). */
    private function ageStage(int $age): string
    {
        return match (true) {
            $age <= 18 => 'school',
            $age <= 22 => 'college',
            $age <= 64 => 'working',
            default => 'retired',
        };
    }

    /** Life stage(s) spanning an age range — a single label, or "younger–older". */
    private function stageRange(int $youngest, int $oldest): string
    {
        $low = $this->ageStage($youngest);
        $high = $this->ageStage($oldest);

        return $low === $high ? $low : "{$low}–{$high}";
    }

    private function birthYear(Person $p): int
    {
        return (int) date('Y', strtotime((string) $p->born_at));
    }

    private function eventYear(Event $e): int
    {
        return (int) date('Y', strtotime((string) $e->happened_at));
    }

    private function decade(int $year): int
    {
        return (int) (floor($year / 10) * 10);
    }

    private function genDoc(Generation $g): string
    {
        return "generations/{$this->genSlugs[$g->id]}/index.md";
    }

    private function personDoc(Person $p): string
    {
        return "people/{$this->personSlugs[$p->id]}.md";
    }

    private function compareDoc(Generation $older, Generation $newer): string
    {
        return "compare/{$this->genSlugs[$older->id]}-vs-{$this->genSlugs[$newer->id]}.md";
    }

    // ---------------------------------------------------------------- slugs, links, files, images

    private function canonicalSlug(Generation $g): string
    {
        $base = Str::slug($g->title);

        return self::CANONICAL_OVERRIDE[$base] ?? $base;
    }

    /**
     * @return array<int,string> model id => unique slug
     */
    private function buildSlugMap(Collection $items, string $field): array
    {
        $used = [];
        $map = [];
        foreach ($items as $item) {
            $base = Str::slug((string) $item->{$field});
            if ($base === '') {
                $base = 'item-'.$item->id;
            }
            $slug = $base;
            $i = 2;
            while (isset($used[$slug])) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $used[$slug] = true;
            $map[$item->id] = $slug;
        }

        return $map;
    }

    /** Relative link from one doc-root-relative file path to another (.md kept for MkDocs). */
    private function rel(string $from, string $to): string
    {
        $fromParts = explode('/', $from);
        array_pop($fromParts); // directory of $from
        $toParts = explode('/', $to);

        while ($fromParts && count($toParts) > 1 && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $up = str_repeat('../', count($fromParts));
        $rel = $up.implode('/', $toParts);

        return $rel === '' ? './' : $rel;
    }

    private function write(string $relPath, string $content): void
    {
        $full = "{$this->docsRoot}/{$relPath}";
        File::ensureDirectoryExists(dirname($full));
        File::put($full, $content);
        $this->written++;
    }

    private function page(string $title, string $description, string $body, ?string $image = null): string
    {
        $fm = "---\n";
        $fm .= 'title: '.$this->yaml($title)."\n";
        $fm .= 'description: '.$this->yaml(Str::limit($this->clean($description), 155, ''))."\n";
        if ($image) {
            $fm .= 'image: '.$this->yaml($image)."\n";
        }
        $fm .= "---\n\n";

        $body = rtrim($body, "\n")."\n";
        $body .= "\n----\n\n_Last updated: {$this->runDate}_\n";

        return $fm.$body;
    }

    private function yaml(string $s): string
    {
        return '"'.str_replace('"', '\"', trim($s)).'"';
    }

    /**
     * Resolve/copy an image into docs/img/<type>/<slug>.<ext>.
     *
     * @return array{rel:string, fm:string}|null  rel = inline markdown link, fm = front-matter path
     */
    private function image(string $fromDoc, ?string $rawValue, string $type, string $slug): ?array
    {
        $rawValue = trim((string) $rawValue);
        if ($rawValue === '') {
            return null;
        }

        $isRemote = Str::startsWith($rawValue, ['http://', 'https://']);
        $path = $isRemote ? (parse_url($rawValue, PHP_URL_PATH) ?: $rawValue) : $rawValue;
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) ?: 'jpg';
        $imgDoc = "img/{$type}/{$slug}.{$ext}";
        $dest = "{$this->docsRoot}/{$imgDoc}";

        if ($isRemote) {
            // Remote URLs are only downloaded with --with-images; otherwise keep the URL as-is.
            if (! $this->withImages) {
                return ['rel' => $rawValue, 'fm' => $rawValue];
            }
            $data = @file_get_contents($rawValue);
            if ($data === false) {
                return ['rel' => $rawValue, 'fm' => $rawValue];
            }
            File::ensureDirectoryExists(dirname($dest));
            File::put($dest, $data);
            $this->imagesCopied++;

            return ['rel' => $this->rel($fromDoc, $imgDoc), 'fm' => "/{$imgDoc}"];
        }

        $src = $this->resolveLocal($rawValue);
        if ($src === null) {
            return null; // omit broken reference rather than emit a dead src
        }
        File::ensureDirectoryExists(dirname($dest));
        File::copy($src, $dest);
        $this->imagesCopied++;

        return ['rel' => $this->rel($fromDoc, $imgDoc), 'fm' => "/{$imgDoc}"];
    }

    private function resolveLocal(string $value): ?string
    {
        $trim = ltrim($value, '/');
        foreach ([public_path($trim), base_path($trim), $value] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Remove only the subtrees this generator owns. Hand-authored content
     * (about/, icon/, overrides/, glossary/, blog/, …) is never touched.
     */
    private function cleanGenerated(): void
    {
        foreach (self::GENERATED_PATHS as $path) {
            $full = "{$this->docsRoot}/{$path}";
            if (is_dir($full)) {
                File::deleteDirectory($full);
            } elseif (is_file($full)) {
                File::delete($full);
            }
        }
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            ($this->logger)($message);
        }
    }
}
