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

Update a budget's state for a month: edit the budgeted and realized amounts for the starting month or later (the only way to enter the realized amount, since there is no external connection).
Month details page: a dedicated page to view and edit a specific month's budgeted and realized amounts.
Budget sharing page: invite other users to view and edit shared budgets.
Dashboard: a summary of the budget information for the latest month (the tail of the cumulative series) — most importantly the running net per budget — and the alerts.
Budgeted amount (target): store the per-month budgeted amount; when editing a month, the UI form pre-fills it with the previous month's value by default, always editable.
Borrowing / reallocation: allow the user to reallocate money from one budget to another. Design finalized (see `reallocation_plan.md`):
- Persisted as a **month-specific reallocation ledger** (`reallocations` table: `recipient_budget_id`, `source_budget_id`, `month`, `amount > 0`). Raw monthly data (`budgeted`/`realized`) is never mutated; metrics are computed, so no desynchronization.
- **Month-specific & cumulative**: a reallocation applies to a single month and affects both budgets from that month onward — the running partial sum includes `reallocation_in − reallocation_out`.
- **Aggregate net borrowed** is a separate metric: sum of money borrowed minus money lent across all reallocations, shown as a single figure per budget (independent of the per-month envelope).
- Reallocations can be edited or deleted; nets are recomputed dynamically, no triggers needed.
- Reallocation selector lists **all** other budgets sorted by current net (negatives/zero not hidden) so the user can choose the source. Any budget can be a source; borrowing is **not** restricted to budgets with a positive net.
Envelope-positivity alert: display an alert banner on the dashboard when the running total of any budget dips below zero for any month, and require the user to resolve it before it clears. A dip below zero means a budget has not been properly represented (in real life budgets are always positive): it signals that a reallocation was performed but not yet reported by the user.

## Open design questions

- Reallocations: one row per `(recipient, source, month)`, or allow multiple stacked rows that are summed? **Decided: unique — one row per `(recipient, source, month)`; stacked rows are not allowed (rows are not summed).** See `reallocation_plan.md` §2.
