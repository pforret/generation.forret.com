# Git Analysis

> Repository: `generation.forret.com` (`/Users/pforret/Code/laravel/generation.forret.com`)
> Generated: 2026-06-17 • Analysis window: full history (53 commits, 2022-11 → 2026-06)
> Method: history-based diagnostics (per piechowski.io)

## TL;DR
- **Single-author repo.** Peter Forret owns 44/52 commits (~83%); the rest are
  Claude-authored. Bus factor = 1. All institutional knowledge sits with one person.
- **Two bursts, one long sleep.** 37 commits in Nov–Dec 2022 (initial Laravel build),
  then **3.5 years of silence**, then 24 commits in June 2026 (reactivation: static-site
  generator + bulk content). This isn't declining momentum — it's a dormant project
  recently revived.
- **Churn is dominated by generated output, not source.** The top-20 most-changed files
  are almost all `static/site/**/index.html` — deterministic output of
  `StaticSiteGenerator`. They look "hot" but carry no risk; ignore them.
- **Real hotspots are bookkeeping + the generator.** Excluding generated files, the
  most-touched paths are manual-sync files (`VERSION.md`, `composer.json`,
  `package.json`) and `docs/url-structure.md` — consistent with the documented
  "kept in sync manually" convention. The only meaningful *code* hotspot is
  `app/Services/StaticSiteGenerator.php`.
- **Zero firefighting signal.** No commits match fix/bug/broken/revert/hotfix/rollback.
  Either genuinely clean, or — more likely — commit messages don't use those words
  (recent style is `MOD:`, `Add …`, `setver:`). Treat the "no bugs" reading as weak.

## 1. Code Churn Hotspots

Raw (last 12 months, all files):
```
  23 (blank)
   6 VERSION.md
   6 package.json
   6 package-lock.json
   6 docs/url-structure.md
   6 composer.json
   5 static/site/what-generation-am-i/index.html
   5 static/site/search.json
   5 static/site/quotes/index.html
   5 static/site/people/<many>/index.html   ← ~14 generated person pages, 5 each
```

Filtered (excluding `static/`, lockfiles, blanks):
```
   6 VERSION.md
   6 package.json
   6 docs/url-structure.md
   6 composer.json
   2 zensical.toml
   2 docs/ai-search-optimization-plan.md
   2 app/Services/StaticSiteGenerator.php
   1 tests/Feature/StaticSiteGeneratorTest.php
   1 app/... (everything else single-touch)
```

**Read:** The unfiltered list is misleading — `static/site/**` is build output committed
to the repo, so every regeneration shows up as churn across dozens of files. The genuine
signal after filtering: the four manual-sync files (`VERSION.md`, `composer.json`,
`package.json`, `docs/url-structure.md`) change together on every release/restructure,
exactly as `CLAUDE.md` documents. `StaticSiteGenerator.php` is the one source file with
repeated edits — it's the active development surface.

## 2. Bus Factor

```
  44  Peter Forret <peter@forret.com>
   8  Claude <noreply@anthropic.com>
```

**Read:** ~83% single-owner; effectively a solo project with AI-assisted commits. This is
a critical-dependency risk in principle (one person holds all context), but expected for a
personal reference site. Recent activity is *not* dropped off — the owner is the one who
just revived it in June 2026 — so the usual "key person left" alarm doesn't apply here.

## 3. Bug Clusters

```
(no commits match fix|bug|broken|error|issue|patch|revert)
```

**Read:** Empty result. No file can be flagged as a bug magnet because the history records
no bug-fix commits at all. This is almost certainly a **commit-message convention gap**,
not evidence of a bug-free codebase — the project uses `MOD:` / `Add` / `setver:` prefixes
rather than fix/bug language. Conclusion: the churn∩bugs intersection can't be computed;
the signal is unavailable, not negative.

## 4. Velocity Trend

```
   9  2022-11   (initial build)
  20  2022-12   (initial build continues)
   —  2023, 2024, 2025  → no commits (dormant)
  24  2026-06   (reactivation)
```
By day, June 2026: 11 on the 4th, 8 on the 5th, 5 on the 17th.

**Read:** Episodic, not a smooth curve. A strong initial sprint, a 42-month gap, then a
focused burst around the static-site/AI-search-optimization work. The project is in an
active phase right now after long dormancy — momentum is *returning*, not fading.

## 5. Firefighting Frequency

```
(zero matches for revert|hotfix|emergency|rollback in the last year)
```

**Read:** No emergency/rollback commits. Combined with §3, this reflects a low-stakes,
no-CI-pressure personal project rather than a battle-tested deployment record. Don't
over-read it as stability — there's simply no production-incident history to read.

## Highest-Risk Code (churn ∩ bugs)
- **Cannot be computed** — §3 produced no bug-tagged commits, so there is no overlap set.
- Best available proxy for "where surprises live": **`app/Services/StaticSiteGenerator.php`**
  — the only repeatedly-edited source file, central to the current work, and the thing most
  likely to change again. Its test (`tests/Feature/StaticSiteGeneratorTest.php`) is the
  guardrail to watch.

## Where to start reading
1. **`app/Services/GenerationAnchor.php`** — `CLAUDE.md` names it the conceptual core
   (the born-in vs happened-during anchoring rule, defined once and reused by all
   controllers/generator). Read this before any content or query logic.
2. **`app/Services/StaticSiteGenerator.php`** — the active development surface and only
   real code hotspot; read alongside its Feature test to understand the deterministic
   output contract.
3. **`docs/url-structure.md`** and **`docs/ai-search-optimization-plan.md`** — both churned
   recently and called out as required reading for structural changes; they explain the
   *intent* behind the June 2026 burst.
4. Treat **`static/site/**`** as build artifacts, not source — don't read them to
   understand behavior; regenerate via `php artisan static:generate`.
