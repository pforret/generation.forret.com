# generation.forret.com

Reference/educational site about human generations (Lost Generation → Generation Beta):
per-generation year ranges, descriptions, memorable quotes, notable people, and world
events. Public site is read-only; all content is managed through a Filament admin and
bulk-loaded from a spreadsheet.

This README is written for whoever **operates and deploys** the site. For application
architecture and development conventions, see [`CLAUDE.md`](CLAUDE.md).

---

## Stack & requirements

| Component   | Version / notes                                         |
|-------------|---------------------------------------------------------|
| PHP         | **8.1+** (ext: pdo_mysql, mbstring, openssl, bcmath, ctype, json, tokenizer, xml, gd) |
| Laravel     | 9.x                                                     |
| Database    | **MySQL 8** (default schema name `generation`)          |
| Web admin   | Filament v2, served at **`/admin`**                     |
| Frontend    | Tailwind CSS + Vite (assets compiled at build time)     |
| Node        | 16+ (build-time only — not needed at runtime)           |
| Composer    | 2.x                                                     |
| Cache/queue | File cache, `sync` queue by default (no worker required)|

There is **no scheduled/cron work** (`app/Console/Kernel.php` schedule is empty) and **no
queue worker** is required unless `QUEUE_CONNECTION` is changed away from `sync`.

---

## Configuration

All configuration is environment-driven. Copy and fill in:

```bash
cp .env.example .env
php artisan key:generate
```

Variables that **must** be set correctly per environment:

| Variable | Production value |
|----------|------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (never `true` in prod — leaks stack traces) |
| `APP_URL` | `https://generation.forret.com` |
| `APP_KEY` | generated once, keep stable (rotating it invalidates sessions/encrypted data) |
| `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | production MySQL credentials |
| `LOG_CHANNEL` | `stack` (writes to `storage/logs/laravel.log`) |
| `MAIL_*` | real SMTP — needed for admin password resets / email verification |

`.env`, `.env.production`, and `auth.json` are gitignored — provision them out of band.

---

## First-time server setup

```bash
git clone <repo> && cd generation.forret.com

# PHP deps (no dev packages, optimized autoloader)
composer install --no-dev --optimize-autoloader

# Build front-end assets (output goes to public/build, gitignored)
npm ci
npm run build

# App key + storage symlink
php artisan key:generate          # only if APP_KEY is empty
php artisan storage:link

# Database schema + base seed data (admin user, generations)
php artisan migrate --force
php artisan db:seed --force
```

**Web root must point at `public/`.** The `.htaccess` for Apache is included; for nginx use
the standard Laravel `try_files` config. Ensure `storage/` and `bootstrap/cache/` are
writable by the web server user.

### Production cache warming (run after every deploy)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> If you ever change `.env` on the server, re-run `config:cache` (or `config:clear`) —
> cached config ignores later `.env` edits.

---

## Deploying an update

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force        # applies any new migrations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear          # if you prefer a clean rebuild instead of the three caches above
```

Zero new infra is needed for a routine deploy — it's pull, install, build, migrate,
re-cache. Roll back by checking out the previous tag and re-running the same steps
(reverse any migration with `php artisan migrate:rollback` only if it was destructive).

---

## Content management & data import

Content lives in MySQL and is edited two ways:

1. **Filament admin** at `/admin` — log in with a seeded/created admin user. CRUD for
   generations, people, events, quotes, and users.
2. **Bulk spreadsheet import** — a single multi-sheet workbook at
   `database/files/import.xlsx` (sheets: `generations`, `events`, `people`, `quotes`):

   ```bash
   php artisan import:data
   ```

   This **upserts/appends** content from the workbook. Drop the updated `.xlsx` in place
   first, then run the command. (The file is not committed — keep the canonical copy in
   shared storage / a backup location.)

### ⚠️ `initialize.sh` is destructive

```bash
./initialize.sh   # = migrate:fresh --seed --force  +  import:data
```

`migrate:fresh` **drops every table and recreates the schema**, wiping all live data before
re-seeding and re-importing. Use it only for first provisioning or a deliberate full reset —
**never** as a routine deploy step. Take a DB backup first.

---

## Backups

The only stateful component is **MySQL** (content + admin users). Back it up on a schedule:

```bash
mysqldump -u <user> -p generation | gzip > generation-$(date +%F).sql.gz
```

Also preserve, out of band:
- `.env` (contains `APP_KEY` — required to decrypt existing encrypted/session data)
- `database/files/import.xlsx` (canonical content source)

`public/build/`, `vendor/`, and `node_modules/` are all regenerable and need no backup.

---

## Monitoring & logs

- **Application log:** `storage/logs/laravel.log` (`LOG_CHANNEL=stack`). Note the
  generation-show controller logs verbose event dumps at `info` level — expect this file to
  grow; rotate it (logrotate or `LOG_CHANNEL=daily`).
- **Health check:** `GET /` returns 200 and lists generations — a good uptime probe.
- **Crawlers:** `public/robots.txt` currently allows everything (`Disallow:` empty), so all
  search/AI crawlers can index the site. There is **no `sitemap.xml`** yet (noted as a gap
  in `docs/ai-search-optimization-plan.md`).

---

## Local development (Docker)

A Laravel Sail stack is provided (`docker-compose.yml`) bundling MySQL, Redis, Meilisearch,
Mailhog, and Selenium:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm run dev
```

Mailhog dashboard at `http://localhost:8025`. Without Docker: `php artisan serve` +
`npm run dev` against a local MySQL.

### Tests

```bash
php artisan test            # PHPUnit (Feature + Unit suites)
./vendor/bin/pint           # code style (Laravel Pint)
```

---

## Troubleshooting

| Symptom | Likely cause / fix |
|---------|--------------------|
| 500 on every page, blank screen | `storage/` or `bootstrap/cache/` not writable; check `storage/logs/laravel.log` |
| Config changes ignored | stale `config:cache` — run `php artisan config:clear` |
| Old assets / styling broken after deploy | `npm run build` not run, or browser caching `public/build` |
| "No application encryption key" | `APP_KEY` empty — `php artisan key:generate` |
| Admin login / password reset emails not arriving | `MAIL_*` not configured for prod |
| Import does nothing / errors | `database/files/import.xlsx` missing or sheet names changed (`php artisan import:data` expects `generations`, `events`, `people`, `quotes`) |
