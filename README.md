# Eagle

Eagle is a Laravel 13 personal budget management web app. Users register, log in, manage a profile, and create/edit budgets with an envelope (monthly cash-flow) model.

## Features

- **Auth**: register (unique `username`, password min 8 chars + confirmation), login/logout, session-based. No email field — accounts are keyed by `username`.
- **Profile**: view username, change password with current-password verification.
- **Budgets**: create budgets with a starting month (`date`) and `start_amount` (initial envelope balance); list, view, and delete budgets; record budgeted/realized amounts per month.
- **Dashboard**: summary view for authenticated users at `/`.
- **UI**: Blade templates styled with Tailwind CSS 4 (custom theme tokens in `resources/css/app.css`, Instrument Sans font).

## Getting Started

```bash
composer setup      # deps, .env, key:generate, migrate, npm install, npm run build
composer dev        # php artisan serve + queue:listen + logs + vite
npm run build       # compile assets to public/build
```

SQLite is the default database. For MySQL/PostgreSQL, set the credentials in `.env`.

## Running & Testing

```bash
composer test       # config:clear then php artisan test
php artisan test --filter=RegisterTest   # run one test
```

Notes:
- PHPUnit forces in-memory SQLite (`phpunit.xml`), so every test starts fresh.
- CSRF middleware is disabled for all tests (`tests/TestCase.php`).

## Project Structure

```
app/Models/          User, Budget, BudgetMonth
app/Http/Controllers/   Login, Register, Profile, Budget
database/migrations/  users, budgets, budget_months
resources/views/     login, register, profile, dashboard, budgets/, welcome
routes/web.php
tests/Unit/          Login, Register, Profile
tests/Feature/       Budget (routes, ownership, months, calculations)
```

## Notes

- `users.username` is the unique identifier, not `email`/`name`.
- Budgets are owned by a user (`User hasMany Budget`); months are keyed by a `date` (no day).
- The envelope (`start_amount`) is the offset when summing monthly cash flow; it should stay non-negative.
- Planned (not yet implemented): budget sharing, detailed month view, borrowing between budgets.
