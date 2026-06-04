# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`generation.forret.com` is a Laravel 9 reference/educational site about human generations
(Lost Generation → Generation Beta). Each generation page aggregates its year range, a
description, memorable quotes, notable people born in that cohort, and world events grouped
by the life stage the cohort was in when they happened. Content is editor-managed (Filament
admin) and bulk-loaded from a spreadsheet.

PHP ^8.1, Laravel ^9.19, Filament v2 (admin), Tailwind + Vite (frontend), MySQL.

## Commands

```bash
# Setup
cp .env.example .env && php artisan key:generate
composer install && npm install
php artisan migrate --seed

# Run (local)
php artisan serve              # app on http://localhost:8000
npm run dev                    # Vite dev server (hot reload)
npm run build                  # production assets

# Run (Docker / Laravel Sail — bundles MySQL, Redis, Meilisearch, Mailhog, Selenium)
./vendor/bin/sail up

# Tests (PHPUnit, two suites: Unit + Feature)
php artisan test                                   # all
php artisan test --testsuite=Feature
php artisan test --filter=AuthenticationTest       # single class
php artisan test tests/Feature/Auth/AuthenticationTest.php

# Lint / format (Laravel Pint)
./vendor/bin/pint            # fix
./vendor/bin/pint --test     # check only

# Bulk data import (see Data import below)
php artisan import:data

# Static-site generation (see Static site below)
php artisan static:generate             # write the Markdown tree into static/docs/
php artisan static:generate --clean     # wipe generated subtrees first (keeps about/, icon/, overrides/)
php artisan static:generate --with-wiki # also pull per-person Wikipedia bios (network, slower)
mkdocs build -f static/mkdocs.yml       # build static/site/ from the Markdown (run manually)
```

## Architecture

**Domain models** (`app/Models/`) — all use `$guarded = [id, created_at, updated_at]`
(mass-assignment open by default), no `$fillable`:
- `Generation` — `title`, `slug`, `alternatives`, `first_year`, `last_year`. The central
  entity; routed by `slug` (not id).
- `Person` — `name` (⚠ the column is `name`, **not** `title` — the model PHPDoc is wrong),
  `category`, `country`, `description`, `born_at`, `image`. Anchored to a generation by
  **birth year** falling within `[first_year, last_year]`. (No `slug` column — derive one.)
- `Event` — `title`, `category`, `description`, `url`, `happened_at`. Surfaced per
  generation by **when it happened** relative to the cohort's age (see below).
- `Quote` — belongs to a `Generation` (`generation_id`); `author`, `url`.

**The generation-show join logic** (`GenerationController::show`) is the conceptual core.
There is *no* foreign key linking people/events to generations. Instead the relationship is
**derived at query time**:
- People: `whereBetween('born_at', [first_year, last_year+1])` — cohort membership.
- Events: bucketed into life stages (`child` 6-12, `puberty` 13-20, `adult` 21-60,
  `retired` 61-80) by computing the cohort's *middle* birth year and querying
  `happened_at` windows offset from it.

This logic now lives in `App\Services\GenerationAnchor` (`peopleBornIn`, `eventsByLifeStage`,
`generationForYear`, `lifeStageAtYear`, `fmt`); `GenerationController`, `PersonController`,
and `StaticSiteGenerator` all delegate to it so the anchoring rule is defined once.

This "people = born-in, everything else = peaked/happened-during" anchoring rule is the
intended model for all generation content — see `docs/url-structure.md`.

**HTTP layer** (`app/Http/Controllers/`) — thin controllers returning Blade views.
Public routes are read-only resource routes (`generation.index/show` keyed by slug,
`person.index/show`) plus Breeze auth. Admin is **Filament** at `/admin`
(`app/Filament/Resources/`), the primary editing UI for all five models.

**Data import** (`app/Imports/` + `app/Console/Commands/ImportDataCommand.php`) — a single
multi-sheet `.xlsx` at `database/files/import.xlsx` is the canonical content source.
`DataImporter` (maatwebsite/excel `WithMultipleSheets`) dispatches each named sheet
(`generations`, `events`, `people`, `quotes`) to its own importer. Run `php artisan import:data`.

**Wikipedia enrichment** — `app/Helpers/CleanWiki::get($topic, $maxWords)` pulls a
Wikipedia preview (illuminated/wikipedia-grabber), strips HTML, and caches the result by
topic. Used to populate descriptions without storing full text.

**Static site** (`app/Services/StaticSiteGenerator.php` + `GenerateStaticSiteCommand`) —
`php artisan static:generate` renders the whole dataset to a Markdown tree under
`static/docs/` (matching `docs/upgrade/url-structure.md`) and copies images to
`static/docs/img/`. Output is **deterministic** (injected run date, ordered queries) so
re-runs are byte-stable. Pages carry AEO scaffolding (front-matter description, factual
summary, FAQ, tables, "Last updated"). Canonical generation slugs = `Str::slug(title)`
with a `generation-y → millennials` override (DB slugs are short: `x`, `boomers`, `y`).
The command **does not** build HTML — run `mkdocs build -f static/mkdocs.yml` yourself.
Phase B page types (blog best/worst-of, bands/movies/tv-shows, influences) are skipped:
they need DB schema that doesn't exist yet (`Person.influence`, peak-period tables).

## Conventions & gotchas

- **Generation slugs are canonical and fixed** (`baby-boomers`, `generation-x`,
  `millennials`, …). Aliases (`boomers`, `gen-x`, `gen-y`) should 301-redirect to the
  canonical slug. Do not invent new year boundaries — reuse the DB ranges for entity
  consistency. Full slug/alias table in `docs/url-structure.md`.
- Model PHPDoc blocks (`@property`, `@method whereX`) are generated by
  `barryvdh/laravel-ide-helper` — regenerate rather than hand-editing.
- `resources/views/_template/` holds vendor Bootstrap/Landwind scaffolding examples, not
  live app views — the real pages are `generation/`, `person/`, `welcome.blade.php`.
- `VERSION.md` and the `version` field in both `composer.json` and `package.json` are kept
  in sync manually.

## Strategic context (docs/)

The site's direction is documented in two planning files — read them before large content
or URL-structure changes:
- `docs/ai-search-optimization-plan.md` — goal is to be the **source AI assistants cite**
  (AI Overviews, ChatGPT, Perplexity), not just rank in Google. Prioritizes "Best X"
  listicles, comparisons, and clean crawlable HTML. Note: schema markup is explicitly
  **low priority** for AI citation (do it only for traditional rich results).
- `docs/url-structure.md` — target URL/folder taxonomy (trailing-slash directory URLs,
  people as top-level entities, fixed canonical comparison order).
