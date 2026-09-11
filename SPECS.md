# Project specification

This file describes the specification of this project and should always be up-to-date with the latest guidelines from the project owner. It describes what the application should be, not only its current state: implemented items are listed under "Implemented," and planned items are clearly labelled under "Backlog." For the accurate, code-verified status (commands, tests, auth, ownership), refer to `AGENTS.md`.

## Technology stack

The application is a Laravel 13 full-stack project (single app, not a monorepo).

- Backend: PHP, Laravel 13 + Tailwind.
- Database: SQLite by default. The test suite runs against an in-memory SQLite database (`DB_DATABASE=:memory:`), and MySQL or PostgreSQL are possible alternatives.
- Frontend: Blade templates styled with Tailwind CSS 4, compiled through Vite. Vite inputs are `resources/css/app.css` and `resources/js/app.js`; the compiled assets are published to `public/build`.
- Styling: a custom theme defined with `@theme` tokens (for example `--color-primary`, `--color-secondary`, `--color-alert`) and the Instrument Sans font.
- Testing: PHPUnit for unit and feature tests.

## Testing and quality

Unit tests must cover critical business logic such as authentication and money calculations.
Feature tests validate the end-to-end behaviour of the main flows.
Tests are expected to run both through a CI pipeline and locally before commits. CI and pre-commit hooks are planned but not yet wired up.
Formatting and linting use `pint` (PSR-12, no custom configuration).

## Main objectives

Eagle is a web application to manage personal budgets. It should never become too complex; the goal is to match the requirements defined below and not more.

## Implemented features

These features are already coded.

User accounts: users can create an account and log in with a username and password. The `users` table identifies users by `username` (unique, max 255 characters); there is no email field.
Account management: an account page lets users manage their profile and change their password.
Budget creation: a page to create a budget with a name, a starting month, and a start amount (initial envelope balance).
Budget listing: a page listing the user's budgets.
Budget details: a page showing budget-specific information.
Update a budget's state for a month: edit the budgeted and realized amounts for the starting month or later (the only way to enter the realized amount, since there is no external connection). The inline “Month details” form on the budget details page covers the former “month details page” backlog item.
Budgeted amount pre-fill: when the displayed month has no stored record, the form pre-fills the budgeted amount with the most recent previous month's value (always editable); with no previous month it pre-fills 0; a stored value for the month takes precedence.
Dashboard: a summary of the budget information for the latest month (the tail of the cumulative series) — the running net per budget — plus the envelope-positivity alert banner and the net-borrowed metric per budget.
Borrowing / reallocation: the user can reallocate money from one budget to another (design in `reallocation_plan.md`):
- Persisted as a **month-specific reallocation ledger** (`reallocations` table: `recipient_budget_id`, `source_budget_id`, `month`, `amount > 0`). Raw monthly data (`budgeted`/`realized`) is never mutated; metrics are computed, so no desynchronization.
- **Month-specific & cumulative**: a reallocation applies to a single month and affects both budgets from that month onward — the running partial sum includes `reallocation_in − reallocation_out`. When creating a reallocation from the budget details page, its month is always the month currently displayed on that page (no month input in the form; a hidden field submits it). The month can never be edited afterwards — correcting a wrong month means deleting the reallocation and recreating it.
- **Unique**: one row per `(recipient, source, month)`; stacked rows are not allowed (rows are not summed). The store/update actions reject duplicates.
- **Aggregate net borrowed** is a separate metric: sum of money lent minus money borrowed across all reallocations, shown as a single figure per budget (independent of the per-month envelope); positive ⇒ net lender.
- Reallocations can be edited (amount only — the month is immutable after creation) or deleted, on the budget details page and the per-budget reallocations list; nets are recomputed dynamically, no triggers needed.
- The budget details page lists only the reallocations of the **month currently displayed**; the per-budget reallocations list page (`/budgets/{budget}/reallocations`) shows all months.
- Reallocation selector on the budget details page lists **all** other budgets sorted by current net descending (negatives/zero not hidden) so the user can choose the source. Any budget can be a source; borrowing is **not** restricted to budgets with a positive net.
- Source and recipient must both be owned by the authenticated user (sharing not yet implemented); self-reallocation is rejected.
Envelope-positivity alert: an alert banner is displayed on the dashboard and on the budget details page when the running total of a budget dips below zero for any month; it clears once the user resolves the conflict with a reallocation. A dip below zero means a budget has not been properly represented (in real life budgets are always positive): it signals that a reallocation was performed but not yet reported by the user.
Login and register pages.

## Requirements — money domain

The following rules describe how budgets, months, and the envelope behave once the related features are built.

A user owns one or more budgets. Budgets can be shared between users so that two or more users can view and edit them.
A budget has a name, a starting month, and a start amount (the initial envelope balance).
Budgets are updated on a monthly basis, so dates contain no day component. The starting month is the first month with budgeted and realized amounts.
Months are stored with only their basic information: month, budgeted amount (the target amount), and realized amount. No running total or balance is stored; all metrics are computed from these values, which avoids desynchronization between stored data and computed metrics.
The envelope total is the start amount offset plus the cumulative sum of monthly cash flow (budgeted minus realized) and of all reallocations up to that month (`reallocation_in − reallocation_out`), computed chronologically from the earliest month onward.
Because the computation is cumulative, editing any month — including pre-start months — changes the running total of every subsequent month.
The budgeted amount is analogous to the amount of money injected into the budget. When editing a month, the form pre-fills the budgeted amount with the previous month's value by default, but it is always editable; the stored value reflects whatever the user enters.
The remainder of a budget for a month is the difference between the budgeted amount and the realized amount; it can be positive (under-spent) or negative (over-spent).
The envelope must stay positive at all times. Envelope positivity is checked on any running partial sum of the cumulative computation, so the alert triggers if the running total dips below zero at any point, not only on the final total.
When the running total goes negative, the user resolves the conflict by adding a reallocation (borrowing money from another budget). Envelope positivity is re-checked after the reallocation.
Budgets can borrow money from other budgets. Borrowing is a manual user action used to resolve budget conflicts. Amounts do not need to be repaid.
The net amount borrowed per budget is the aggregate of money borrowed minus money lent across all reallocations, shown as a single figure.

## Backlog — future features

These features are designed but not yet coded. They must not be forgotten.

Budget sharing page: invite other users to view and edit shared budgets. Reallocations currently assume both budgets are owned by the same user; when sharing is added, ensure reallocations only link budgets the user can edit.

## Open design questions

_None at this time._ (Reallocation month alignment is resolved: the create form always uses the month currently displayed on the budget details page, which the controller clamps to the budget's start month or later.)
