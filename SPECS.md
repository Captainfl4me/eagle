# Project specification

This file describes the specification of this project. It should always be up-to-date with latest guidelines from project owner.

## Technology stack

- Backend: Laravel (PHP)
- Database: MySQL or PostgreSQL (to be determined)
- Frontend: Blade templates (Laravel's default)
- Testing: PHPUnit for unit tests

## Testing requirements

- Unit tests must be included for critical business logic
- Tests should be runnable via CI pipeline and locally before commits

## Main objectives

Eagle is a web application to manage personal budgets. It should never become a too complex project, the goal is only to match the requirements defined below and not more.

## Features and money management explanation

- Users should be able to create new accounts and use them to log into the application.
- Users should be able to create budget with a name, a starting month and a start amount of money (initial envelope balance).
- Users should be able to update the state of a budget for a specific month (updates should only be allowed for the starting month or later). The user updates the budgeted amount and the realized amount (this is the only way to enter the realized amount as there will be no external connection).
- One user owns the different budgets but they can be shared between users so that two or more users can view and edit budgets.
- Budgets are updated on a monthly basis, so dates should not contain the day of the month as it does not make sense. The starting month is the first month with realized and budgeted amounts.
- Budgets should have a target amount that is the budgeted amount. By default, for next month the value should be carried over from last month but must be editable individually for each month.
- Budgets should have a realized amount that is the real amount used for that budget. The difference between budgeted amount and real amount used is the remainder of the budget, it can be positive if budget is not fully used or negative if more money was used.
- Each budget can be seen as an envelope where remainder money accumulates (or is consumed if remainder is negative). The starting amount is used as an offset when summing all the money flow from this budget to calculate the total money in this envelope.
- Each budget envelope should be positive at all times, if not, it means that money that did not exist was consumed which is not possible. This should trigger an alert displayed as a banner on the dashboard that will need to be resolved by the user.
- To be more accurate, budgets should be able to borrow money from other budgets that are positive. This is a manual action for the user to resolve budget conflicts. Amounts do not need to be repaid but users should be able to see the net amount borrowed (money borrowed minus money lent) from budgets that lent them money.

## Pages

- Login and register pages
- Account page to manage user account
- Dashboard with summary of information, current (last) month information, and alerts
- Budget creation page
- Budget details page with budget specific information
- Budget sharing page to invite other users
- Month details page to view and edit a specific month's budgeted and realized amounts
