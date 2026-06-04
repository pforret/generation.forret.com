# PRP — Static-Site Generator (Laravel → Markdown → MkDocs)

> **Feature:** Enable the Laravel app to render the entire `generation.forret.com`
> dataset as Markdown into `static/docs/`, then build a static HTML site into
> `static/site/` with MkDocs (Material). Implements the URL taxonomy and AEO
> requirements from `docs/upgrade/url-structure.md` and
> `docs/upgrade/ai-search-optimization-plan.md`.
>
> **Date:** 2026-06-04 · **Target:** PHP ^8.1 / Laravel ^9.19 · **Author:** generate-prp

---

## 1. Goal

Add an Artisan command `static:generate` that reads the existing `Generation`,
`Person`, `Quote`, and `Event` records and writes a complete tree of Markdown files
under `static/docs/` matching the URL map in `docs/upgrade/url-structure.md`. A second
step (`mkdocs build -f static/mkdocs.yml`) turns that into the publishable
`static/site/` folder.

End-to-end pipeline:

```
php artisan migrate --seed
php artisan import:data            # loads database/files/import.xlsx into MySQL
php artisan static:generate        # NEW — writes Markdown into static/docs/   ← THIS PRP STOPS HERE
# ── handled by the user, NOT by this implementation ──
mkdocs build -f static/mkdocs.yml  # user runs this to produce static/site/ (or `mkdox build`)
```

> 🚧 **SCOPE BOUNDARY (user directive):** The implementation's job is to **generate the
> Markdown files in `static/docs/`**. Do **NOT** run `mkdocs`/`mkdox`, and do not make the
> implementation *depend* on running them. The build into `static/site/` is the user's step.
> The `mkdocs.yml`/redirect config is still written (so the user's build works), but the
> agent must not invoke or shell out to the build tool.

**Definition of done:** `static:generate` runs clean and **idempotently** (byte-stable
output on re-run), producing the complete, internally-consistent Markdown tree under
`static/docs/` with valid front matter and resolvable cross-links — ready for the user to
build with MkDocs.

---

## 2. Why (from the strategy docs)

- **Be the source AI cites** (`ai-search-optimization-plan.md`): every page needs a
  liftable 2–3 sentence summary, an FAQ block, data tables, "Last updated" + current-year
  anchoring, and clean static HTML. Schema markup is **explicitly low priority** — do not
  build a sprint around it.
- **Listicle / comparison formats** are the #1 cited format → `famous-people`, per-category
  people lists, and the 45-page `/compare/` matrix are the highest-ROI pages.
- **Entity consistency** (Finding 10): one canonical set of year ranges/definitions reused
  everywhere. Use the DB ranges; never invent new boundaries.
- **Anchoring rule** (`url-structure.md`): **people = born-in** (cohort), **everything else
  (events, culture) = peaked/happened-during**. This is already how
  `GenerationController::show` works — reuse that logic, don't reinvent it.

---

## 3. Current state — what exists (verified in code)

### 3.1 Data models & **actual** DB schema (⚠ docs disagree with reality)

Read straight from `database/migrations/` — **trust the migrations, not the PHPDoc / CLAUDE.md**:

| Model | Table | Real columns (from migration) | Routed by |
|---|---|---|---|
| `Generation` | `generations` | `id, title, slug, first_year, last_year, alternatives, description, image, timestamps` | `slug` |
| `Person` | `people` | `id, **name**, born_at(date), category, country, description, image, timestamps` | id (no slug!) |
| `Event` | `events` | `id, title, category, description, url, image, happened_at(date), timestamps` | id (no slug!) |
| `Quote` | `quotes` | `id, generation_id(FK), title, description, image, author, url, timestamps` | id |

> 🔴 **CRITICAL GOTCHA #1 — `Person` has no `title` column; it is `name`.**
> `app/Models/Person.php` PHPDoc and `CLAUDE.md` both say `title` — they are **wrong**.
> `PersonController::show` correctly uses `$person->name` (line 39, 43).
> Use `$person->name` everywhere. Likewise `Person` has a `country` column the PHPDoc omits.
>
> 🔴 **GOTCHA #2 — `Person::$casts` has a typo:** `"born" => "date"` (should be `born_at`).
> Harmless for us because we format `born_at` manually, but do **not** rely on the cast.
>
> 🟡 **GOTCHA #3 — `Generation` has `description` + `image` columns** (present in migration,
> absent from PHPDoc). Descriptions exist — use them for summaries/meta.
>
> 🟡 **GOTCHA #4 — `Person`, `Event` have NO `slug` column.** URLs like `/people/<slug>/`
> and `/events/<slug>/` must derive a slug at generation time via `Str::slug($name)` with
> collision de-duplication (append `-2`, `-3`…). Build the slug map **once** up front so
> every cross-link resolves to the same slug.

All models use `$guarded = [id, created_at, updated_at]` (mass-assignment open), no `$fillable`.

### 3.2 The anchoring/join logic to REUSE (conceptual core)

`app/Http/Controllers/GenerationController.php::show()` (lines 37–62):

```php
$middle = round(($generation->first_year + $generation->last_year) / 2);
// life-stage event buckets — happened_at offset from the cohort's MIDDLE birth year
$events["child"]   = Event::whereBetween('happened_at',[fmt($middle,6),  fmt($middle,12)])->orderBy('happened_at')->get();
$events["puberty"] = Event::whereBetween('happened_at',[fmt($middle,13), fmt($middle,20)])->orderBy('happened_at')->get();
$events["adult"]   = Event::whereBetween('happened_at',[fmt($middle,21), fmt($middle,60)])->orderBy('happened_at')->get();
$events["retired"] = Event::whereBetween('happened_at',[fmt($middle,61), fmt($middle,80)])->orderBy('happened_at')->get();
// people = born-in cohort
$people = Person::whereBetween('born_at',[fmt($first_year), fmt($last_year+1)])->get();
// fmt($year,$offset) => sprintf("%04d-%02d-%02d", $year+$offset, 1, 1)
```

Person → generation anchor (`PersonController::show`, lines 39–42):
```php
$birth_year = date("Y", strtotime($person->born_at));
$generation = Generation::where("first_year","<=",$birth_year)
                        ->where("last_year",">=",$birth_year)->first();
```

**Extract these two pieces of logic into a shared service** (`GenerationAnchor`) so the
controller and the generator share one implementation (DRY, entity consistency).

### 3.3 Existing import pattern to MIRROR

`app/Console/Commands/ImportDataCommand.php` — minimal command shape (signature, `handle()`,
`$this->info/error`, return `Command::SUCCESS|FAILURE`). Mirror this structure exactly for
`static:generate`. The import reads `database/files/import.xlsx` via `DataImporter`
(maatwebsite/excel, `WithMultipleSheets`: `generations|events|people|quotes`).

### 3.4 Existing static scaffold (already in repo)

```
static/
├── mkdocs.yml                       # ⚠ placeholder: site_name "static Docs" — must be updated
└── docs/
    ├── index.md                     # placeholder "Welcome to static Docs" — WILL be overwritten by home page
    ├── about/index.md               # "# About this site" stub — keep hand-authored
    ├── icon/                         # favicons, webmanifest — keep
    └── overrides/partials/integrations/analytics/custom.html   # keep
```

- `mkdocs` **1.5.3 is installed** (`/opt/homebrew/bin/mkdocs`, Python 3.11). MkDocs core
  auto-generates `sitemap.xml`. `awesome-pages` plugin is referenced in the existing config.
- `mkdox` (pforret's bash wrapper, `~/.basher/cellar/bin/mkdox`) is also present; `mkdox build`
  is the user's normal build verb. **`zensical` is NOT installed** (root `zensical.toml` exists
  but Zensical is not on PATH) → **build with MkDocs Material, not Zensical** (see Open Questions).
- `composer` packages available: `illuminate/support` (Str::slug), `maatwebsite/excel`,
  `illuminated/wikipedia-grabber` + `App\Helpers\CleanWiki` (Wikipedia enrichment, cached).

---

## 4. Target structure (from `docs/upgrade/url-structure.md`)

`use_directory_urls: true` → file path maps 1:1 to URL. Full file tree is in
`url-structure.md` §"Corresponding MkDocs docs/ file tree" (lines 210–277) and the route
inventory table (lines 159–194). **Read that doc; do not duplicate the whole tree here.**

### 4.1 Scope split — generate from EXISTING data vs. DEFERRED (needs new schema)

> The url-structure doc describes the *full* target including page types whose source data
> does **not exist yet**. Split the work so Phase A ships entirely from current data.

**✅ Phase A — generate now (data exists: Generation/Person/Quote/Event):**

| Output | Source | Notes |
|---|---|---|
| `index.md` (`/`) | all `Generation` | home hub + data table (years/traits/key figures) |
| `generations/index.md` | all `Generation` | sortable comparison table |
| `generations/<gen>/index.md` | `Generation` | summary block + FAQ + year range + "last updated" |
| `generations/<gen>/famous-people.md` | `Person` born-in | "Best X" overview listicle |
| `generations/<gen>/<category>.md` | `Person` born-in × `category` | one file per distinct `Person.category` (actors, musicians, politicians, business-leaders, …) |
| `generations/<gen>/quotes.md` | `Quote` where generation_id | |
| `generations/<gen>/timeline.md` | `Event` life-stage buckets | reuse `GenerationAnchor` |
| `people/index.md` + `people/<slug>.md` | `Person` | A–Z hub + per-person page (bio, born year, generation link, optional CleanWiki) |
| `events/index.md` + `events/<slug>.md` | `Event` | hub by era/decade + per-event page with per-generation life-stage influence breakdown |
| `quotes/index.md` | `Quote` | all quotes grouped by generation |
| `compare/index.md` + 45 `compare/<older>-vs-<newer>.md` | `Generation` pairs C(10,2) | canonical = older first; reverse 301 via redirects |
| `born-in/index.md` + `born-in/<decade>s.md` + `born-in/<year>.md` | derived from generation ranges | ~143 year pages (min first_year … max last_year, capped at 2025) |
| `what-generation-am-i/index.md` | `Generation` ranges | lookup + explainer table |
| `about.md` / `about/index.md` | hand-authored | **preserve existing** — do not overwrite |

**⛔ Phase B — DEFERRED (requires new schema; out of scope for this PRP, log a stub note):**

| Output | Missing data |
|---|---|
| `blog/best-of-<gen>.md`, `blog/worst-of-<gen>.md` (20 posts) | `Person.influence` polarity (+/−) + magnitude score — **not in DB** |
| `blog/best-<category>-of-<gen>.md` | same influence score |
| `bands/`, `movies/`, `tv-shows/` + `generations/<gen>/influences.md` | new `Band`/`Movie`/`TvShow` tables with peak/active periods — **not in DB** |
| `glossary/` term pages | hand-authored content — optional, can stub the hub |

> Phase A emits a `generations/<gen>/index.md` "Related" section that links to Phase-B pages
> **only if** they exist, so adding Phase B later needs no edits to Phase A. Print a clear
> summary line: `Skipped (no data): blog/best-of-*, bands/, movies/, tv-shows/, influences/`.

---

## 5. Implementation blueprint

### 5.1 New files

```
app/Services/GenerationAnchor.php          # shared anchoring logic (life-stage buckets, person→gen, middle year)
app/Services/StaticSiteGenerator.php       # orchestrates the whole doc tree; owns the slug map
app/Console/Commands/GenerateStaticSiteCommand.php   # signature: static:generate {--clean}
resources/views/static/                     # (optional) Blade partials rendered to Markdown strings
tests/Feature/StaticSiteGeneratorTest.php   # validation gate
```

> Prefer plain PHP heredoc/`sprintf` string building or small Blade partials rendered with
> `view(...)->render()` — keep it simple and testable. No new Composer deps required for
> Phase A (Str::slug, Carbon, File facade all built in).

### 5.2 `GenerationAnchor` (extract & share)

```php
class GenerationAnchor
{
    public function middleYear(Generation $g): int            // round((first+last)/2)
    public function fmt(int $year, int $offset = 0): string   // "%04d-%02d-%02d" (year+offset,1,1)
    public function peopleBornIn(Generation $g): Collection    // whereBetween born_at [first, last+1]
    public function eventsByLifeStage(Generation $g): array    // ['child'=>..,'puberty'=>..,'adult'=>..,'retired'=>..]
    public function generationForYear(int $year): ?Generation  // first_year <= y <= last_year
    public function lifeStageAtYear(Generation $g, int $eventYear): string  // for /events/<slug>/ breakdown
}
```
Refactor `GenerationController::show` and `PersonController::show` to call this service
(behaviour-preserving — existing feature tests must still pass).

### 5.3 `StaticSiteGenerator` — pseudocode

```php
public function generate(string $docsRoot): array   // returns ['written'=>int, 'skipped'=>[...]]
{
    $this->personSlugs = $this->buildSlugMap(Person::all(), 'name');   // GOTCHA #4 — once, deduped
    $this->eventSlugs  = $this->buildSlugMap(Event::all(), 'title');
    $gens = Generation::orderBy('first_year')->get();   // chronological = canonical order

    $this->writeHome($docsRoot, $gens);
    $this->writeGenerationsHub($docsRoot, $gens);
    foreach ($gens as $g) {
        $people = $this->anchor->peopleBornIn($g);
        $this->writeGenerationIndex($docsRoot, $g);          // summary + FAQ + last-updated
        $this->writeFamousPeople($docsRoot, $g, $people);
        foreach ($people->groupBy('category') as $cat => $list)
            $this->writeCategoryList($docsRoot, $g, $cat, $list);   // Str::slug($cat).'.md'
        $this->writeGenerationQuotes($docsRoot, $g);
        $this->writeTimeline($docsRoot, $g, $this->anchor->eventsByLifeStage($g));
    }
    $this->writePeopleHub(...); foreach (Person::all() as $p) $this->writePerson(...);
    $this->writeEventsHub(...); foreach (Event::all() as $e) $this->writeEvent(...);  // per-gen influence
    $this->writeQuotesHub(...);
    $this->writeComparePages($docsRoot, $gens);   // C(10,2)=45, older-first
    $this->writeBornIn($docsRoot, $gens);         // hub + decades + per-year
    $this->writeWhatGenerationAmI($docsRoot, $gens);
    // Phase B intentionally skipped — record in $skipped
}
```

### 5.4 AEO requirements every generated page MUST satisfy

(from `ai-search-optimization-plan.md` Findings 4, 8, 10)

1. **YAML front matter** with a **real, per-page `description`** (≤155 chars, derived from the
   entity's own description — never the literal `"some text"` placeholder the old site used).
2. **Liftable summary** — first content block is a 2–3 sentence standalone factual answer,
   current-year anchored (`"As of 2026, Generation X refers to people born 1965–1980."`).
3. **FAQ block** on each generation page answering literal questions ("When were X born?",
   "What is X known for?", "What comes after X?") — each with a one-sentence liftable answer.
4. **Data tables** (generation → years → traits → key figures) — extractable markdown tables.
5. **`Last updated: 2026-06-04`** line (pull date from the generator run; pass it in — see
   Gotcha #6, do not call `now()` inside templates if you want deterministic test output).
6. Clear `##`/`###` headings + ordered lists for listicles (machine-readable structure).

> ⚠ **GOTCHA #5 — internal links under `use_directory_urls: true`.** Cross-folder links
> must be **relative and resolve after the directory rewrite**. Safest: use root-relative
> paths in markdown that MkDocs validates against the source tree, e.g. link to the **source
> file** `[Gen X](../generation-x/index.md)` from a sibling. Since we **don't run the build
> here**, enforce link integrity in-process: route every link through one
> `link(string $fromDocPath, string $toDocPath)` helper backed by the slug map, and have
> `StaticSiteGeneratorTest` assert every emitted `[..](..)` target resolves to a generated
> file — the build-free substitute for `mkdocs --strict`. Apply one convention uniformly.

> ⚠ **GOTCHA #6 — determinism.** Avoid `now()`/random ordering inside generation so output is
> byte-stable across runs (clean diffs, testable). Inject the run date once; always
> `orderBy()` queries (e.g. people by `born_at`, then `name`).

### 5.5 `mkdocs.yml` updates (`static/mkdocs.yml`)

- Set `site_name: "Generations — generation.forret.com"`,
  `site_url: https://generation.forret.com/`, real `site_description`.
- Keep `use_directory_urls: true` (default; required for clean `/folder/` URLs + sitemap).
- `theme: material` (already), keep existing palette/features.
- Plugins: keep `search`; add `awesome-pages` (manage the big generated nav) and
  `redirects` (`mkdocs-redirects`) for: old Laravel URLs (`/generation/{slug}` →
  `/generations/{slug}/`, `/person/{slug}` → `/people/{slug}/`), slug aliases
  (`gen-x`→`generation-x`, `generation-y`→`millennials`, …), and reverse compare-order 301s.
  Build the `redirect_maps` from the same slug/alias table + the 45 compare pairs.
- `robots.txt`: ship a static `static/docs/robots.txt` that **allows** AI crawlers
  (GPTBot, ClaudeBot, PerplexityBot, Google-Extended) and links the sitemap (Finding 3).

### 5.6 Slug & alias source of truth

The canonical generation slugs + aliases are tabulated in `url-structure.md` lines 26–37.
Generation slugs already live in the DB (`generations.slug`, `generations.alternatives`).
**Read aliases from `Generation.alternatives`** where possible; fall back to the doc table
for any gaps. Do not hardcode a second copy of the slug list if the DB already has it.

### 5.7 Images → `static/docs/img/` (user directive)

All four content tables have an `image` column (`Generation.image`, `Person.image`,
`Event.image`, `Quote.image`). Every image referenced by a generated page **must live under
`static/docs/img/`** so the built site serves them from `/img/…`.

- Add an `ImageHandler` (or method on the generator) that, for each non-empty `image` value:
  1. **Local/relative path** (e.g. `storage/…`, `public/…`) → **copy** the file into
     `static/docs/img/<type>/<slug>.<ext>` (e.g. `img/people/keanu-reeves.jpg`).
  2. **Absolute URL** (`http(s)://…`) → only **download** it when `--with-images` is passed
     (network, like `--with-wiki`); otherwise keep the remote URL as-is in the markdown
     (don't break the build by referencing a missing local file). Default: **don't download**.
  3. **Empty / missing** → omit the `<img>` / front-matter `image:` entirely (never emit a
     broken `src` or the `"some text"` placeholder).
- Filenames come from the **same slug map** as the page (stable, deduped, deterministic).
- In markdown, reference copied images by a path the build resolves to `/img/…` (mirror the
  `link()` convention from Gotcha #5; one `image(string $fromDocPath, string $imgDocPath)`
  helper). Set `image:` in front matter for `og:image` (Finding: real `og:image`, plan §Phase 0).
- `--clean` may wipe `static/docs/img/` (it is generator-owned) but **must not** touch
  `static/docs/icon/` (hand-authored favicons).
- Determinism: copying is idempotent (overwrite in place); no timestamps in filenames.

---

## 6. Task list (in order)

1. **Service extraction.** Create `app/Services/GenerationAnchor.php`; move the life-stage
   bucketing + person→generation logic out of the two controllers and have them delegate.
   Run existing feature tests — must stay green.
2. **Slug map.** Implement `buildSlugMap()` (Str::slug + collision suffixing) on
   `StaticSiteGenerator`; unit-test collisions ("José X" / "Jose X").
3. **Command skeleton.** `GenerateStaticSiteCommand`
   (`static:generate {--clean} {--with-wiki} {--with-images}`) mirroring `ImportDataCommand`;
   resolves `static/docs` path, optional `--clean` wipes only generated subtrees + `img/`
   (never `about/`, `icon/`, `overrides/`).
4. **Image handler** (§5.7) — copy local images into `static/docs/img/<type>/<slug>.<ext>`,
   shared slug map; remote URLs only fetched under `--with-images`. Used by all writers below.
5. **Home + generations hub + per-generation index** (with summary/FAQ/table/last-updated).
6. **People-born-in pages:** `famous-people.md` + per-category lists.
7. **Quotes + timeline** per generation.
8. **People hub + per-person pages** (use `$person->name`; optional CleanWiki enrichment,
   guarded by a `--with-wiki` flag to avoid network calls in CI/tests).
9. **Events hub + per-event pages** with per-generation life-stage influence breakdown.
10. **Quotes hub.**
11. **Compare matrix** — 45 ordered pairs + `compare/index.md` grid.
12. **born-in** hub + decade indexes + per-year pages (range = min(first_year)…min(max last_year, 2025)).
13. **what-generation-am-i** lookup page.
14. **`static/mkdocs.yml`** update + `mkdocs-redirects` map + static `robots.txt`.
15. **Phase-B stubs:** skip generation, print skipped list; leave `about.md`/`glossary` hubs alone.
16. **Tests** (`StaticSiteGeneratorTest`, incl. internal-link resolution) + **Pint**.
    *(Do not run mkdocs/mkdox — leave the build to the user.)*
17. Update `CLAUDE.md` (Commands section) and `VERSION.md`/`composer.json`/`package.json`
    version bump (kept in sync manually per CLAUDE.md).

---

## 7. Validation gates (executable)

```bash
# 1. Lint / format
./vendor/bin/pint --test

# 2. PHP tests (existing suite must stay green after the service refactor + new test)
php artisan test
php artisan test --filter=StaticSiteGeneratorTest

# 3. Generate against seeded data (CI: migrate --seed + import:data first)
php artisan migrate:fresh --seed --quiet
php artisan import:data
php artisan static:generate --clean

# 4. Sanity on output: expected dirs exist, NO leftover placeholder text
test -f static/docs/index.md
test -d static/docs/generations && test -d static/docs/people && test -d static/docs/compare
# 45 compare pages:
[ "$(ls static/docs/compare/*-vs-*.md | wc -l)" -eq 45 ] || echo "FAIL: expected 45 compare pages"
# no placeholder leaked into any page:
! grep -rln "some text" static/docs/ || echo "FAIL: placeholder text present"

# every generated page has YAML front matter with a real description:
! grep -rL "^description:" static/docs/generations static/docs/people static/docs/compare 2>/dev/null \
  | grep -q . || echo "FAIL: page(s) missing front-matter description"

# images live under static/docs/img/ and every local image reference resolves there:
test -d static/docs/img
! grep -roh "](\.\./*img/[^)]*)" static/docs/ >/dev/null 2>&1 || true   # any img refs point at /img/

# 5. Link integrity WITHOUT building (do NOT run mkdocs/mkdox — user does that).
#    A self-test inside StaticSiteGeneratorTest verifies every internal markdown link
#    target resolves to a generated source file in the slug map. Optionally, a read-only
#    link-lint script may parse [..](..) targets and assert each exists on disk.
php artisan test --filter=StaticSiteGeneratorTest
```

> The MkDocs build (`mkdocs build --strict` → `static/site/` + `sitemap.xml`) is the
> **user's** verification step and is intentionally **not** part of the automated gates here.

`StaticSiteGeneratorTest` (Feature) asserts, against a small factory-seeded dataset:
- correct file count per category & 45 compare files;
- each generation page contains its `first_year`/`last_year`, an FAQ heading, and a
  `Last updated:` line;
- a person page uses the person's **`name`** (regression guard for Gotcha #1);
- output is byte-identical on a second run (idempotency / determinism, Gotcha #6);
- **every internal markdown link target resolves** to a file the generator wrote (parse
  `[text](target)`, map to disk path, assert exists — this is the build-free substitute for
  `mkdocs --strict`);
- Phase-B paths are **absent** and reported as skipped.

---

## 8. Gotchas & known quirks (consolidated)

1. 🔴 `Person.name` not `Person.title` — PHPDoc & CLAUDE.md lie; migration + PersonController are truth.
2. 🟡 `Person::$casts` typo `"born"` (should be `born_at`) — don't depend on the cast.
3. 🟡 `Generation` has `description` + `image` (not in PHPDoc) — use them.
4. 🔴 No `slug` on Person/Event — derive & dedupe once into a shared map; reuse for all links.
5. 🔴 `use_directory_urls: true` link resolution — one link helper + `mkdocs build --strict`.
6. 🟡 Determinism — inject run date, always `orderBy`, no `now()`/random in templates.
7. 🟡 `--clean` must never delete `about/`, `icon/`, `overrides/` (hand-authored/assets).
8. 🟡 CleanWiki makes network calls + caches — gate behind `--with-wiki`; off by default for CI.
9. 🟡 Build tool ambiguity: root `zensical.toml` vs `static/mkdocs.yml`. MkDocs 1.5.3 is
   installed; Zensical is not → **use MkDocs**. Confirm with user (Open Questions).
10. 🟡 Compare canonical order = older generation first (chronological by `first_year`);
    reverse pair 301-redirects via `mkdocs-redirects`.
11. 🟡 IDE-helper PHPDoc is generated (`barryvdh/laravel-ide-helper`) — after adding any
    field usage, regenerate rather than hand-editing (CLAUDE.md convention).

---

## 9. Reference material

**Codebase:**
- `app/Http/Controllers/GenerationController.php:37-62` — life-stage + born-in join (extract).
- `app/Http/Controllers/PersonController.php:37-50` — person→generation anchor + CleanWiki.
- `app/Console/Commands/ImportDataCommand.php` — command shape to mirror.
- `database/migrations/2022_11_23_*` — **authoritative** schema for all four tables.
- `app/Helpers/CleanWiki.php` — `CleanWiki::get($topic,$maxWords)` cached Wikipedia preview.
- `docs/upgrade/url-structure.md` — full URL map, file tree, slug/alias table, redirect table.
- `docs/upgrade/ai-search-optimization-plan.md` — AEO requirements (summaries/FAQ/tables/freshness).

**External docs (Agent has WebSearch — fetch if needed):**
- MkDocs config & `use_directory_urls`: https://www.mkdocs.org/user-guide/configuration/
- MkDocs Material setup: https://squidfunk.github.io/mkdocs-material/setup/
- mkdocs-redirects: https://github.com/mkdocs/mkdocs-redirects
- awesome-pages plugin: https://github.com/lukasgeiter/mkdocs-awesome-pages-plugin
- Laravel `Str::slug` / `File` facade: https://laravel.com/docs/9.x/helpers
- Laravel Artisan commands: https://laravel.com/docs/9.x/artisan

---

## 10. Decisions (resolved with the user 2026-06-04)

1. ✅ **Phase B DEFERRED.** `blog/best-of-*`, `blog/worst-of-*`, `bands/`, `movies/`,
   `tv-shows/`, `influences/` are **not generated** this pass (need new DB tables/fields —
   `Person.influence`, peak periods). Generate everything possible from current
   Generation/Person/Quote/Event data and print a `Skipped (no data): …` summary line.
2. ✅ **Wikipedia enrichment OFF by default.** Use the stored `Person.description`. Gate
   CleanWiki behind an opt-in `--with-wiki` flag (keeps output deterministic + network-free
   in CI/tests).
3. ✅ **All images go under `static/docs/img/`** (user directive — see §5.7).

**Still open (assumed defaults; confirm if wrong):**

- **Build tool:** `mkdocs build` (installed, 1.5.3) — the implementation does **not** run it
  (user's step). `mkdocs.yml` is written for MkDocs Material; Zensical (`zensical.toml`) is
  not installed. Assumed: **MkDocs Material**.
- **`born-in` year range:** assumed 1883→2025 (Lost Generation's `first_year` → 2025 cap),
  ≈ 143 year pages.

---

## Confidence score: **9 / 10**

Strong: the data layer, anchoring logic, and command pattern are fully understood; the
validation gates are concrete and build-free (per the user's scope boundary); the schema
discrepancies that derail one-pass runs (esp. `Person.name`) are documented up front; and the
three judgement calls that previously held this back are now **resolved** — Phase B deferred,
Wiki enrichment off by default, images under `static/docs/img/`.

The remaining −1 is residual data-shape risk: actual `image` column contents (local paths vs.
absolute URLs) and `Person.category` value spelling drive the per-category filenames and the
image handler's copy-vs-skip branch — both only fully knowable against the seeded prod data.
The handler degrades safely (omit on missing, skip remote unless `--with-images`), so this is
a polish risk, not a blocker.
