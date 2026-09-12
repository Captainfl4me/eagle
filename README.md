# Eagle

Eagle is a Laravel 13 personal budget management web app. Users register, log in, manage a profile, and create/edit budgets with an envelope (monthly cash-flow) model.

## Features

- **Auth**: register (unique `username`, password min 8 chars + confirmation), login/logout, session-based. No email field — accounts are keyed by `username`.
- **Profile**: view username, change password with current-password verification.
- **Budgets**: create budgets with a starting month (`date`) and `start_amount` (initial envelope balance); list, view, and delete budgets; record budgeted/realized amounts per month (pre-filled with the previous month's budgeted value).
- **Reallocations / borrowing**: move money between budgets of the same user, persisted as a month-specific ledger (`reallocations`), with a computed per-month envelope and aggregate net-borrowed metric.
- **Dashboard**: summary view for authenticated users at `/`, including the envelope-positivity alert banner.
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
app/Models/          User, Budget, BudgetMonth, Reallocation
app/Services/        BudgetCalculator (envelope, net-borrowed, negative months)
app/Http/Controllers/   Login, Register, Profile, Dashboard, Budget, Reallocation
database/migrations/  users, budgets, budget_months, reallocations
database/factories/   User, Budget, BudgetMonth, Reallocation
resources/views/     login, register, profile, dashboard, budgets/, reallocations/, welcome
routes/web.php
tests/Unit/          Login, Register, Profile, ReallocationCalculator
tests/Feature/       Budget(routes, ownership, months), Reallocation
```

## Notes

- `users.username` is the unique identifier, not `email`/`name`.
- Budgets are owned by a user (`User hasMany Budget`); months are keyed by a `date` (no day).
- The envelope (`start_amount`) is the offset when summing monthly cash flow; it should stay non-negative.
- Reallocations are month-specific (one row per `(recipient, source, month)`, amounts never repaid); the envelope and net-borrowed metric are computed on the fly — nothing is stored.
- Backlog (designed, not yet implemented): budget sharing.
