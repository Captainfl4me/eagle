# AGENTS.md

## Project Overview
Eagle is a Laravel 13 personal budget management web application.

## Developer Commands

```bash
# Full setup (from composer.json scripts)
composer setup

# Run dev server with all services (PHP server, queue, logs, Vite)
composer dev

# Run tests
composer test
# or directly: php artisan test

# Run frontend dev server only
npm run dev

# Build frontend assets
npm run build
```

## Testing

- Uses PHPUnit with SQLite in-memory database (`phpunit.xml` lines 26-27)
- Test command clears config cache first (`composer.json` line 47)
- Test suites: `Unit` and `Feature` in `tests/` directory

## Key Configuration

- **DB**: SQLite by default (`.env` line 23), MySQL/PostgreSQL possible per `SPECS.md`
- **Frontend**: Vite + Tailwind CSS 4 (`vite.config.js`, `package.json`)
- **PHP**: Requires PHP 8.3+ (`composer.json` line 9)
- **Laravel**: Version 13.x

## Architecture Notes

- Standard Laravel structure: `app/`, `routes/`, `database/`, `config/`
- Frontend assets: `resources/css/app.css`, `resources/js/app.js`
- Blade templates (not SPA)
- Tests use in-memory SQLite regardless of `.env` DB setting