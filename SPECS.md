# Project specification

This file describes the specification of this project. It should always be up-to-date with the latest guidelines from the project owner.

## Implementation status

This table is the single source of truth for what is implemented vs. planned. It is the most important part of this file: before writing code, confirm a feature is marked **Done** or **Planned (in scope)** rather than assuming it exists.

| Area | Status | Notes |
| --- | --- | --- |
| Auth (register/login/logout) | **Done** | Users identified by `username` (no email); registration requires username (unique) + password (min 8, confirmed) |
| Profile (view username, change password) | **Done** | Current-password check required |
| Budget create / list / details / delete | **Done** | See [Features & rules](#features-and-money-management-explanation) |
| Month-level budgeted/realized amounts | **Done** | Editable inline on the budget-details page |
| Envelope total (start_amount + monthly cash flow) | **Done** | Green if ≥ 0, red if < 0 |
| Contiguous-month rule | **Done** | A month can only be created if the previous month exists |
| Dashboard summary | **Placeholder** | `/` renders a placeholder; no budget summary yet |
| Alerts banner (envelope went negative) | **Planned (out of scope)** | See [Money model](#money-model) |
| Budget sharing between users | **Planned (out of scope)** | See [Money model](#money-model) |
| Target amount (carried over, per-month editable) | **Planned (out of scope)** | See [Money model](#money-model) |
| Borrowing between budgets | **Planned (out of scope)** | See [Money model](#money-model) |
| Month details page | **Planned (out of scope)** | Month editing currently lives on the budget-details page |

## Technology stack

- Backend: Laravel (PHP)
- Database: SQLite (default), MySQL or PostgreSQL possible via `.env`
- Frontend: Blade templates (Laravel's default)
- Styling: Tailwind CSS 4 (custom theme tokens in `resources/css/app.css`, `@theme` block; OS-based dark/light theming via `prefers-color-scheme`; Instrument Sans font)
- Testing: PHPUnit (unit + feature). No Pest.

## Testing requirements

- Unit tests must cover critical business logic (money calculations, month ordering, validation).
- Feature tests cover HTTP behavior (routes, auth, views, database effects).
- Tests are runnable locally. **There is no CI pipeline** — run them manually before committing.
- Test environment details (see `phpunit.xml` / `tests/TestCase.php`):
  - Forces in-memory SQLite (`DB_DATABASE=:memory:`) regardless of `.env` → every test starts fresh.
  - `BCRYPT_ROUNDS=4` (the dev `.env` uses 12) → password hashing is fast in tests.
  - CSRF (`PreventRequestForgery`) is disabled for **all** tests → feature tests need no token.
  - `composer test` runs `config:clear` first, then `php artisan test`.

## Main objectives

Eagle is a web application to manage personal budgets. Keep it intentionally simple — match the requirements below and not more. Do not add complexity for speculative features; if a planned item (alerts, sharing, borrowing, target amounts) is picked up, it should be scoped down before implementation.

## Features and money management explanation

### Money model (precise definitions)

- **Envelope total** for a budget up to a given month = `start_amount` + Σ(`budgeted_amount − realized_amount`) for every month ≤ that month.
- **Remainder** = budgeted amount − realized amount for a month. Over the whole budget the running remainder is `total − start_amount`.
- **Sign convention**: a positive total means the budget still has money left; a negative total means more was spent than available. The total is displayed green when ≥ 0 and red when < 0.
- **Contiguous months**: months are added one after another. To create a month after the start month, the immediately previous month must already exist (enforced in `BudgetController@updateMonth`, returns a validation error otherwise).
- **Borrowing between budgets** — *Planned, out of scope.* When implemented: a user may manually move value from a positive budget to a negative one. Track net borrowed (borrowed − lent) per budget. **Do not implement until scoped.**
- **Target amount** — *Planned, out of scope.* By default a month's target would carry over from the previous month, but it is individually editable. **Do not implement until scoped.** (No `target_amount` column exists yet.)
- **Envelope positivity alert** — *Planned, out of scope.* If the envelope would go negative, show a banner on the dashboard requiring the user to resolve it. **Do not implement until scoped.**

### Implemented features and rules

- Users should be able to create new accounts and use them to log into the application.
- Users can create a budget with a name, a starting month and a start amount (initial envelope balance).
- Users update the budgeted and realized amounts for a specific month. The first month is the start month; subsequent months must be contiguous (previous month must exist). This is the only way to enter realized amounts (no external imports).
- Budgets are updated on a monthly basis, so dates contain **no day component** (`date` type; the first day of the month is used internally). The start month is the first month that can have amounts.
- The envelope accumulates the running cash flow: `start_amount` is the offset when summing monthly flow to compute the current total.
- Budgets are owned by a single user; they are **not** shared between users yet.

## Pages

| Page | Route | Status |
| --- | --- | --- |
| Login | `GET/POST /login` | **Done** |
| Register | `GET/POST /register` | **Done** |
| Logout | `POST /logout` | **Done** |
| Profile (view + change password) | `GET /profile`, `POST /profile/password` | **Done** |
| Budget creation | `GET/POST /budgets/create` → stored at `POST /budgets` | **Done** |
| Budget listing | `GET /budgets` | **Done** |
| Budget details | `GET /budgets/{id}` | **Done** (inline month editing via `POST /budgets/{id}/month`) |
| Delete budget | `DELETE /budgets/{id}` | **Done** (cascades to months) |
| Dashboard | `GET /` | **Placeholder** (no summary/alerts) |
| Budget sharing | — | **Planned (out of scope)** |
| Month details | — | **Planned (out of scope)** |

## Data model

### `users`

- `username` — unique string, the identity used for login (there is **no email field**).
- `password` — hashed (bcrypt). Min length 8, must be confirmed on registration.
- `remember_token` — Laravel session token.

### `budgets`

- `id`, `user_id` (FK → `users.id`, cascade delete), `name`, `start_month` (`date`), `start_amount` (`decimal(15,2)`).

### `budget_months`

- `id`, `budget_id` (FK → `budgets.id`, cascade delete), `month` (`date`, unique per budget via `[budget_id, month]`), `budgeted_amount` (`decimal(15,2)`, default 0), `realized_amount` (`decimal(15,2)`, default 0).

### Relationships

- `User hasMany Budget` / `Budget belongsTo User`.
- `Budget hasMany BudgetMonth` / `BudgetMonth belongsTo Budget`.

## Behavioral rules

- **Month ordering**: months are stored as dates and displayed/navigated in ascending order. The default displayed month is the start month when no records exist, otherwise the most recent month that has a record, clamped to be ≥ the start month.
- **Validation**: budget creation requires `name` (string, max 255), `start_month` (date `Y-m`), `start_amount` (numeric ≥ 0). Month update requires `month` (`Y-m-d`), `budgeted_amount` (numeric ≥ 0), `realized_amount` (numeric ≥ 0).
- **Ownership**: every budget is scoped to the authenticated user. A user may not access another user's budget (returns 404).
- **UI**: green (`text-green-600`) for a non-negative total, red (`text-red-600`) for a negative total.
