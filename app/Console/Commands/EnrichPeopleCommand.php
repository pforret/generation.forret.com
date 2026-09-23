<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Services\WikipediaPersonLookup;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EnrichPeopleCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'people:enrich
                            {name? : Only enrich people whose name contains this text}
                            {--force : Overwrite existing description/URLs (default: only fill empty fields)}
                            {--dry-run : Show what would change without saving}';

    /**
     * @var string
     */
    protected $description = 'Fill people.description (Wikipedia first paragraph), url_wikipedia and url_imdb';

    public function handle(WikipediaPersonLookup $lookup): int
    {
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $people = Person::orderBy('name')
            ->when($this->argument('name'), fn ($q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->get();

        $updated = 0;
        $notFound = [];
        foreach ($people as $p) {
            $year = (int) date('Y', strtotime((string) $p->born_at));
            $found = $lookup->lookup($p->name, $year, $p->url_wikipedia ?: null);
            if (! $found) {
                $notFound[] = "{$p->name} ({$year})";
                $this->warn("  ? {$p->name} ({$year}): no Wikipedia article with a matching birth year");

                continue;
            }

            $changes = [];
            foreach (['description', 'url_wikipedia', 'url_imdb'] as $field) {
                $new = $found[$field];
                if ($new && ($force || trim((string) $p->{$field}) === '') && $new !== $p->{$field}) {
                    $changes[$field] = $new;
                }
            }
            $this->line("  ✓ {$p->name} → {$found['title']}"
                .($found['url_imdb'] ? ' [imdb]' : '')
                .($changes ? ' — '.implode(', ', array_keys($changes)) : ' — unchanged'));
            if ($dryRun && isset($changes['description'])) {
                $this->line('      '.Str::limit($changes['description'], 160));
            }
            if ($changes && ! $dryRun) {
                $p->update($changes);
                $updated++;
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '')."Updated {$updated} of {$people->count()} people.");
        if ($notFound) {
            $this->warn('Not matched (set url_wikipedia manually, then re-run): '.implode(', ', $notFound));
        }

        return Command::SUCCESS;
    }
}
