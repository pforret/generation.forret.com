# URL & Folder Structure — Static Site (MkDocs)

> Target: convert generation.forret.com to a static HTML site with **MkDocs**
> (`use_directory_urls: true` → clean `/folder/` URLs, auto‑generated `sitemap.xml`).
> The layout below carries over all current content (generations, people, quotes,
> events) and adds the AEO content from the optimization plan (listicles,
> comparisons, FAQ, born‑in lookups, blog).

## Design conventions

- **Lowercase, hyphenated, descriptive slugs**; no IDs, no dates in the path.
- **Trailing‑slash directory URLs** (`/generations/generation-x/`), one topic per folder.
- **Topical clustering**: each generation is a hub with child pages (famous people,
  quotes, FAQ) so internal links reinforce the entity (helps Findings 1, 4, 8).
- **People are top‑level entities** (`/people/<slug>/`), *not* nested under a
  generation — a person URL stays stable even if its generation classification is
  debated, and entity pages are strong citation targets (Finding 2/4). Generations
  cross‑link to them.
- **Comparisons use a fixed canonical order** = older generation first; the reverse
  order 301‑redirects to the canonical one (avoids duplicate content, Finding 9).
- **One set of canonical facts** (year ranges, definitions) reused everywhere for
  entity consistency (Finding 10).

## Canonical generation slugs (chronological)

| Slug | Generation | Aliases → redirect to canonical |
|---|---|---|
| `lost-generation` | Lost Generation | — |
| `interbellum-generation` | Interbellum Generation | `interbellum` |
| `greatest-generation` | Greatest Generation | `gi-generation` |
| `silent-generation` | Silent Generation | — |
| `baby-boomers` | Baby Boomers | `boomers`, `baby-boomer-generation` |
| `generation-x` | Generation X | `gen-x` |
| `millennials` | Millennials | `generation-y`, `gen-y` |
| `generation-z` | Generation Z | `gen-z`, `zoomers` |
| `generation-alpha` | Generation Alpha | `gen-alpha` |
| `generation-beta` | Generation Beta (2025+) | `gen-beta` |

> Use the site's existing canonical year ranges for each — do not introduce new
> boundaries (entity consistency, Finding 10).

## The anchoring rule: people = "born in", everything else = "peaked during"

The relationship a thing has to a generation depends on the **entity type**:

- **People** (individuals) → **born‑in / cohort** anchor (`Person.born_at`).
  `/generations/<gen>/actors/` means *actors born in that generation*. Drives all
  `/generations/<gen>/{famous-people,actors,musicians,politicians,business-leaders}/`
  pages and the `best-of`/`worst-of` series. *Already supported by the data.*
- **Non‑person entities** (bands, movies, TV shows — and events) → **peaked/released‑during**
  anchor. A band/film/show has no birth cohort; what matters is *when it peaked and which
  generations it influenced* (by the life stage each living generation was at). These get
  their own entity pages (`/bands/`, `/movies/`, `/tv-shows/`, `/events/`) and are surfaced
  per generation via `/generations/<gen>/influences/`.

**Data implications:**
- People need no new fields for the category lists — `Person.category` × `born_at` is enough.
- Non‑person entities each need a small table (`Band`, `Movie`, `TvShow`; `Event` already
  exists) with `slug`, title, and a **peak/release period** (`peak_year` or
  `active_from`/`active_to`). Generational influence is *derived* from that period vs each
  generation's life‑stage windows — not stored.

---

## Full URL map

```
/                                           Home / overview hub (data table of all generations)

/generations/                               Hub: all generations, sortable comparison table
      People — notable individuals BORN in this generation (anchor = born_at, cohort):
/generations/<generation>/famous-people/     Overview "Best X" listicle, top across all fields
/generations/<generation>/actors/            Actors born in this generation
/generations/<generation>/musicians/         Solo musicians born in this generation
/generations/<generation>/politicians/       Politicians born in this generation
/generations/<generation>/business-leaders/  Business people / entrepreneurs born in this generation
        (extensible per Person.category: /athletes/, /writers/, /scientists/ …)

/generations/<generation>/quotes/            Memorable quotes from this generation
/generations/<generation>/timeline/          Defining events during this generation's life stages
        (e.g. /generations/generation-x/actors/)

      Culture that SHAPED this generation (anchor = peak period overlapping formative years):
/generations/<generation>/influences/        Bands, movies & TV that peaked during this generation's youth
        → aggregates the non‑person entities below whose peak window overlaps this
          generation's formative years. Links out to each entity's own page.

/compare/                                    Hub: all generation comparisons (matrix grid)
/compare/<older>-vs-<newer>/                 Comparison page (years, traits, events side‑by‑side)
        FULL pairwise matrix: every 2‑generation combination = C(10,2) = 45 pages,
        including adjacent AND skip‑level pairs. Canonical order = older generation
        first; the reverse order 301‑redirects to canonical.
        Adjacent:    /compare/baby-boomers-vs-generation-x/
                     /compare/generation-x-vs-millennials/
                     /compare/millennials-vs-generation-z/
        Skip‑level:  /compare/generation-x-vs-generation-z/      ← e.g. "gen-x vs gen-z"
                     /compare/baby-boomers-vs-millennials/
                     /compare/baby-boomers-vs-generation-z/
                     /compare/silent-generation-vs-generation-alpha/
                     …all remaining pairs…

/events/                                     Hub: defining events, by era/decade
/events/<event>/                             Event page — what happened + per‑generation influence
        (e.g. /events/covid-19-pandemic/, /events/world-war-2/, /events/9-11-attacks/)
        Each page breaks down influence by the life‑stage each living generation was at
        when the event happened (child / teen / adult / retired) — reuses the existing
        life‑stage logic. Bidirectionally linked with /generations/<gen>/timeline/.

/people/                                     Hub: A–Z index of notable individuals (born‑in anchor)
/people/<person>/                            Person page (bio, birth year, generation, quotes)
        (e.g. /people/keanu-reeves/) — covers actors, musicians, politicians, business people…

      Non‑person entities — anchored by PEAK/RELEASE period, each with a per‑generation
      influence breakdown (same model as /events/):
/bands/         /bands/<band>/               Music groups / acts (e.g. /bands/nirvana/)
/movies/        /movies/<movie>/             Films (e.g. /movies/the-matrix/)
/tv-shows/      /tv-shows/<show>/            TV programs (e.g. /tv-shows/friends/)
        Each entity page: when it peaked + which generations it influenced and how
        (by the life stage each living generation was at). Surfaced per generation via
        /generations/<gen>/influences/.

/quotes/                                     Hub: all memorable quotes, grouped/filterable by generation

/what-generation-am-i/                       Lookup + explainer ("which generation by birth year")
/born-in/                                    Hub: index of all birth years, grouped by decade
/born-in/<decade>s/                          Decade index (e.g. /born-in/1980s/) — nav aid
/born-in/<year>/                             "What generation is someone born in <year>?" (1883–2025)
        (e.g. /born-in/1985/) — long‑tail informational AEO (Finding 8).
        DECISION: keep one page per year (~150 pages), generated from data.

/glossary/                                   Definitions of terms (cohort, generation gap, micro‑generation…)
/glossary/<term>/                            Single‑term definition page

/blog/                                       Articles hub (publishing cadence — Findings 2, 10)
/blog/<article-slug>/                        Free‑form individual article
        Systematic editorial listicle series (one per generation, "Best X" format — Finding 1):
        /blog/best-of-<generation>/          The 5 most positively influential people of <gen>
        /blog/worst-of-<generation>/         The 5 most negatively influential people of <gen>
             (e.g. /blog/best-of-generation-x/, /blog/worst-of-millennials/)
             → 10 generations × 2 = 20 ranked posts, generated from a per‑person
               "influence" rating (polarity + magnitude). Cross‑linked from each
               /generations/<gen>/ hub.
        Optional category cut (uses the existing Person.category field):
        /blog/best-<category>-of-<generation>/   e.g. /blog/best-musicians-of-generation-x/

/about/                                      About / methodology / sources (E‑E‑A‑T, Finding 2)
/sitemap.xml                                 Auto‑generated by MkDocs
/robots.txt                                  Static — allow AI crawlers, link the sitemap
```

---

## Corresponding MkDocs `docs/` file tree

With `use_directory_urls: true`, file paths map 1:1 to the URLs above.

```
docs/
├── index.md                                 → /
├── generations/
│   ├── index.md                             → /generations/
│   ├── baby-boomers/
│   │   ├── index.md                         → /generations/baby-boomers/
│   │   ├── famous-people.md                 → /generations/baby-boomers/famous-people/
│   │   ├── quotes.md                        → /generations/baby-boomers/quotes/
│   │   └── timeline.md                      → /generations/baby-boomers/timeline/
│   ├── generation-x/
│   │   ├── index.md
│   │   ├── famous-people.md                 (people born in — overview)
│   │   ├── actors.md                         → /generations/generation-x/actors/
│   │   ├── musicians.md
│   │   ├── politicians.md
│   │   ├── business-leaders.md
│   │   ├── quotes.md
│   │   ├── timeline.md
│   │   └── influences.md                     (bands/movies/TV that peaked during its youth)
│   └── …one folder per generation slug…
├── compare/
│   ├── index.md                             → /compare/   (matrix grid linking all 45)
│   ├── baby-boomers-vs-generation-x.md
│   ├── generation-x-vs-generation-z.md      → /compare/generation-x-vs-generation-z/
│   └── …all 45 ordered pairs (generated)…
├── events/
│   ├── index.md                             → /events/
│   ├── covid-19-pandemic.md                 → /events/covid-19-pandemic/
│   ├── world-war-2.md
│   └── …one file per event (generated)…
├── people/
│   ├── index.md                             → /people/
│   ├── keanu-reeves.md                      → /people/keanu-reeves/
│   └── …one file per individual…
├── bands/
│   ├── index.md                             → /bands/
│   └── nirvana.md                           → /bands/nirvana/
├── movies/
│   ├── index.md                             → /movies/
│   └── the-matrix.md                        → /movies/the-matrix/
├── tv-shows/
│   ├── index.md                             → /tv-shows/
│   └── friends.md                           → /tv-shows/friends/
├── quotes/
│   └── index.md                             → /quotes/
├── what-generation-am-i/
│   └── index.md                             → /what-generation-am-i/
├── born-in/
│   ├── index.md                             → /born-in/         (all years, by decade)
│   ├── 1980s.md                             → /born-in/1980s/   (decade index, nav aid)
│   ├── 1985.md                              → /born-in/1985/
│   └── …one file per year, 1883–2025 (generated)…
├── glossary/
│   ├── index.md
│   └── generation-gap.md
├── blog/
│   ├── index.md
│   ├── best-of-generation-x.md              → /blog/best-of-generation-x/
│   ├── worst-of-millennials.md              → /blog/worst-of-millennials/
│   ├── …best-of-/worst-of- per generation (20, generated)…
│   └── <free-form-article>.md
└── about.md                                 → /about/
```

> The large repetitive sets (people, born‑in years, per‑generation child pages) should
> be **generated** from the existing Laravel database/import data into Markdown at build
> time, rather than hand‑authored — see "Migration" below.

---

## `mkdocs.yml` essentials

```yaml
site_url: https://generation.forret.com/
use_directory_urls: true        # clean /folder/ URLs + sitemap

theme:
  name: material                # good SSR HTML, fast, mobile

plugins:
  - search
  # sitemap.xml is generated automatically by MkDocs core.
  - awesome-pages               # manage nav across many generated files
  - redirects:                  # aliases + old Laravel URLs → new URLs
      redirect_maps: {}         # see Migration table

nav:
  - Home: index.md
  - Generations:
      - Overview: generations/index.md
      - Baby Boomers: generations/baby-boomers/index.md
      - Generation X: generations/generation-x/index.md
      # …
  - Compare: compare/index.md
  - Events: events/index.md
  - People: people/index.md
  - Bands: bands/index.md
  - Movies: movies/index.md
  - TV shows: tv-shows/index.md
  - Quotes: quotes/index.md
  - What generation am I?: what-generation-am-i/index.md
  - Blog: blog/index.md
  - About: about.md
```

Plugins to add: `mkdocs-material`, `mkdocs-awesome-pages-plugin`, `mkdocs-redirects`.

---

## Migration: old Laravel URLs → new static URLs

Preserve link equity and existing citations with 301s (via `mkdocs-redirects`
and/or host‑level rules):

| Old (Laravel) | New (static) |
|---|---|
| `/generation` | `/generations/` |
| `/generation/{slug}` | `/generations/{slug}/` |
| `/person` | `/people/` |
| `/person/{slug}` | `/people/{slug}/` |

Plus the alias redirects from the slug table (e.g. `/generations/gen-x/` → `/generations/generation-x/`,
`/generations/generation-y/` → `/generations/millennials/`).

**Content generation:** export the current `Generation`, `Person`, `Quote`, `Event`
records to Markdown via an Artisan command (one `.md` per record using the trees above),
so the static build stays in sync with the existing dataset. The `famous-people`,
category lists, `compare`, `quotes`, `events`, `influences`, `bands`/`movies`/`tv-shows`,
`best-of`/`worst-of`, and `born-in` pages are all **derived from that same data** — no page
in this structure is hand‑authored except free‑form blog articles and `/about/`.

### New data fields required for the new page types

| Page type | Source data | New field(s) needed |
|---|---|---|
| `/compare/<a>-vs-<b>/` (45) | `Generation` pairs | none — generated from year ranges + traits |
| `/events/<event>/` | `Event` (`happened_at` exists) | `slug`, `description`/significance; influence derived from each generation's life stage at `happened_at` |
| `/blog/best-of-<gen>/`, `/worst-of-<gen>/` (20) | `Person` grouped by generation | `influence` **polarity** (+/−) and **magnitude/score** to rank the top & bottom 5 |
| `/blog/best-<category>-of-<gen>/` (optional) | `Person` | reuses existing `Person.category` + the `influence` score above |
| `/generations/<gen>/{actors,musicians,politicians,…}/` | `Person` | none — `Person.category` × `born_at` (born‑in anchor) |
| `/generations/<gen>/influences/` | `Band`/`Movie`/`TvShow` peak periods | derived from each entity's peak period vs the generation's formative window |
| `/bands/<band>/` | new `Band` entity | `slug`, name, members, formation year, **peak/active period** |
| `/movies/<movie>/` | new `Movie` entity | `slug`, title, **release/peak year** |
| `/tv-shows/<show>/` | new `TvShow` entity | `slug`, title, **run/peak period** (`active_from`/`active_to`) |

---

## How this maps back to the optimization plan

- **Listicles (Finding 1):** `/generations/<gen>/famous-people/`, the full 45‑page `/compare/`
  matrix, and the `/blog/best-of-*` / `/blog/worst-of-*` ranked series.
- **Culture × generation intersection:** `/events/`, `/bands/`, `/movies/`, `/tv-shows/`
  pages each turn one cultural artifact into a multi‑generation answer ("how COVID‑19 / Nirvana /
  The Matrix / Friends shaped each generation") — dense, citable, long‑tail informational
  content (Findings 4, 8), aggregated per generation at `/generations/<gen>/influences/`.
- **Winnable educational niche (Finding 2):** `/glossary/`, `/about/` (sources/methodology), `/blog/`.
- **AI‑crawlable separate layer (Finding 3):** clean static HTML, auto `sitemap.xml`, AI‑bot‑friendly `robots.txt`.
- **Citation‑worthy (Finding 4):** stable entity URLs for people; FAQ/summary blocks per page.
- **Informational intent (Finding 8):** `/what-generation-am-i/`, `/born-in/<year>/`, per‑page FAQ.
- **Freshness/consistency (Finding 10):** one canonical fact set reused; `/blog/` cadence; "last updated" per page.
```
