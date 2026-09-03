# AGENTS.md

## Project Overview
Eagle is a Laravel 13 personal budget management web app (single full-stack app, not a monorepo).
Backend: PHP/Laravel + SQLite/MySQL/PostgreSQL. Frontend: Blade templates + Tailwind CSS 4 via Vite.
Testing: PHPUnit (no Pest). No CI, no pre-commit hooks.

- Feature specification (implemented vs planned): see `SPECS.md`.

## Developer Commands
```bash
composer setup      # deps, copy .env, key:generate, migrate, npm install, npm run build
composer dev        # php artisan serve + queue:listen + logs + vite (queue/cache/session are DB-backed)
composer test       # config:clear then php artisan test
php artisan test    # run tests directly
vendor/bin/pint     # PHP formatter/linter (PSR-12); no custom config
npm run dev         # Vite dev server (hot reload)
npm run build       # compile assets to public/build
```

## Testing
- `tests/TestCase.php` disables CSRF (`PreventRequestForgery`) for ALL tests → feature tests need no CSRF tokens.
- PHPUnit forces in-memory SQLite (`DB_DATABASE=:memory:`) regardless of `.env` → every test starts fresh; don't rely on state persisting across tests.
- `phpunit.xml` sets `BCRYPT_ROUNDS=4` (dev `.env` uses 12) → password hashing is fast in tests.
- `composer test` clears the config cache before running.
- Run one test: `php artisan test --filter=RegisterTest` or `vendor/bin/phpunit tests/Unit/RegisterTest.php`.

## Auth & Users
- The `users` table identifies users by **`username`** (unique string), NOT `email` or `name` (Laravel default).
- Registration validates `username` (required, max:255, unique:users) + `password` (min:8, confirmed). There is no email field.
- Login uses `Auth::attempt(['username', 'password'])`.

## Budget Domain
- Ownership: `User hasMany Budget` / `Budget belongsTo User`; `Budget hasMany BudgetMonth`.
- Months are keyed by `month` (a **date** with no time component); `start_month` is cast to `date`.
- Envelope model: `start_amount` is the offset when summing monthly cash flow for the total; keep the envelope non-negative (enforce in logic/alerts).

## Frontend
- Vite inputs: `resources/css/app.css`, `resources/js/app.js`; compiled output goes to `public/build`.
- Tailwind CSS 4 uses custom theme tokens in `@theme` in `resources/css/app.css` (e.g. `--color-primary`, `--color-secondary`, `--color-alert`). Custom font: Instrument Sans.

## Conventions & Gotchas
- Standard Laravel layout: `app/`, `routes/web.php`, `database/`, `resources/views`. Tests split into `tests/Unit` and `tests/Feature`.
- DB: SQLite by default (`database/database.sqlite`, git-ignored). MySQL/PostgreSQL possible per `SPECS.md`.
- `.gitignore` ignores `database/*.sqlite`, `_ide_helper.php`, `.phpunit.result.cache`, and `/public/build`.
- Conventional commits (`feat`/`fix`/`chore`/`docs`/`color`/`package`); single `main` branch; GitHub remote via SSH.
- README is partly aspirational (e.g. it references `app/Http/Middleware/`, which does not exist). Prefer executable code/config over the README.
