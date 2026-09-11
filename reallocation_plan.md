# Reallocation feature — implementation plan

Status: **implemented.** This plan documents the finalized design for the borrowing /
reallocation feature. The finalized decisions are also captured in `SPECS.md`
(Implemented features). Implementation follows this plan.

---

## 1. Context

Budgets are modelled as an "envelope": each month has a `budgeted_amount` and a
`realized_amount`, and the envelope is a running partial sum. When the running sum dips
below zero, the user must resolve the conflict by borrowing money from another budget. This
feature implements that borrowing as an explicit, month-specific reallocation ledger.

Related design questions were resolved in earlier planning (month-specific scope, separate
ledger, carry-over target amount, no stored totals). This document adds the reallocation
design.

---

## 2. Confirmed decisions

| # | Decision | Value |
|---|----------|-------|
| 1 | Reallocation scope | **Month-specific** — a reallocation applies to a single month. |
| 2 | Persistence | **Separate ledger** (`reallocations` table). Raw monthly data is never mutated. |
| 3 | Timing | **Immediate + cumulative** — affects both budgets from the reallocation month onward. |
| 4 | Source selector | **All budgets shown** (negatives/zero not hidden), sorted by current net. |
| 5 | Aggregate net borrowed | **Pure metric** = Σ borrowed − Σ lent, independent of per-month envelope. |
| 6 | Lifecycle | **Editable / deletable**; everything recomputed dynamically, no triggers. |
| 7 | Stacking vs. unique | **Unique** — one row per `(recipient, source, month)`; stacked rows are **not** allowed (rows are not summed). |

---

## 3. Scope

### In
- New `reallocations` ledger table.
- Per-month reallocations (add / list / edit / delete).
- Reallocation selector on the budget details page (other budgets + current net, sorted).
- Computed metrics: per-month effective net, aggregate net borrowed.
- Envelope-positivity alert that includes reallocations in the running sum.
- Tests (unit + feature).

### Out (out of scope for this feature)
- Budget **sharing** between users (still in Backlog). Reallocations assume the budgets are
  already accessible to the user; authorization against sharing will be layered on if needed.
- External payment connections (out of scope per project scope — realized amounts are entered
  manually).
- Repayment of borrowed money (amounts are never repaid).

---

## 4. Data model

New table `reallocations` (cascade delete from budgets):

| Column | Type | Notes |
|--------|------|-------|
| `id` | PK | |
| `recipient_budget_id` | FK → budgets.id | Budget being funded (the page the user is on). |
| `source_budget_id` | FK → budgets.id | Budget borrowed from. |
| `month` | date | The specific month this reallocation applies to. |
| `amount` | decimal(15,2) | **> 0.** Never repaid. |
| `created_at`, `updated_at` | timestamp | |

Recommended constraint: `unique(recipient_budget_id, source_budget_id, month)` — one
reallocation per source per month (the alternative, stackable rows that are summed, is a
minor open decision — see §9).

**No change to `budget_months`** — it keeps only `month`, `budgeted_amount`,
`realized_amount`. No running totals or balances are ever stored (money-domain rule).

---

## 5. Computation logic (on the fly; nothing stored)

### 5.1 Per-month effective net (running partial sum)

For budget `B` at month `M` (chronological), the running partial sum is:

```
S(M) = start_amount
     + Σ(months m ≤ M: budgeted(m) − realized(m))
     + Σ(reallocations r ≤ M where recipient=B:  +r.amount)
     − Σ(reallocations r ≤ M where source=B:      +r.amount)
```

A reallocation at month `M` therefore raises `S(M)` and every later month for the recipient,
and lowers them for the source. This is the "current net" shown next to a budget name in the
selector and on the budget details page.

**Worked example**

Budget `B`: `start_amount = 1000`.

| Month | budgeted | realized | S(M) without reallocation |
|-------|----------|----------|---------------------------|
| Jan   | 300      | 200      | 1000 + 100 = **1100**     |
| Feb   | 400      | 500      | 1100 − 100 = **1000**     |

At Feb, `B` reallocates **200** from budget `A` into `B`:

- `B` from Feb onward: Feb `S = 1000 + 200 = 1200` (and every later month is +200).
- `A` from Feb onward: each later month is −200.

### 5.2 Envelope-positivity alert

For every budget, evaluate `S(M)` month by month. **If `S(M) < 0` for any month, the
envelope went negative.** Show an alert banner on the dashboard listing the affected
budget(s). The conflict is resolved by adding a reallocation (which raises `S` for the
recipient from that month onward). Re-check after each reallocation.

### 5.3 Aggregate net borrowed (metric)

Per budget `B`, across **all** reallocations (month-independent):

```
net_borrowed(B) = Σ(all: reallocation_out by B) − Σ(all: reallocation_in by B)
```

- Positive ⇒ net lender.
- Negative ⇒ net borrower.

This is a single figure shown on the budget details page (and optionally the listing). It is
**independent** of the per-month envelope math — it is a metric describing the user's
borrowing behaviour, not the envelope balance.

---

## 6. Features / UI

- **Budget details page** (own + shared budgets):
  - Per-month reallocations list for the current month (add / edit / delete).
  - **Reallocation selector**: list of other budgets, each showing its **current net** at the
    selected month, **sorted by net descending**, all budgets shown (negatives/zero included).
    User picks a value per source; multiple sources can feed the current budget in one action.
- **Dashboard**: summary, current (last) month info, and the envelope-positivity alert banner
  (now incorporating reallocations), plus the net-borrowed metric per budget.
- **Budget listing**: optionally show the net-borrowed metric per budget.

---

## 7. Controllers & routes

Extend `BudgetController`:
- `reallocations()` — GET, returns the per-month reallocations for the budget (read for the page).
- Expose computation helpers (effective net at a month, net borrowed, alert state).

New `ReallocationController`:
- `store` — create one or more reallocations (recipient = current budget; sources + amounts from request).
- `update` — edit existing reallocation(s).
- `destroy` — delete reallocation(s).

Routes:
```
POST   /budgets/{budget}/reallocations
PATCH  /budgets/{budget}/reallocations/{reallocation}
DELETE /budgets/{budget}/reallocations/{reallocation}
GET    /budgets/{budget}/reallocations      # list for the details page
```

---

## 8. Authorization & validation

- Budget and source must be **owned by** (or **shared with**) the authenticated user.
- `source_budget_id ≠ recipient_budget_id` (no self-reallocation).
- `amount > 0`.
- `month` aligns with an existing month on the recipient budget.
- **Source restriction:** borrowing is **not** restricted to positive-net budgets. Any budget can be
  a source. The selector still lists all budgets sorted by net so the user can choose (per SPECS.md §48 / Backlog).

---

## 9. Tests

**Unit** (money calculations):
- Effective `S(M)` with and without reallocations.
- Aggregate `net_borrowed` (Σ out − Σ in).
- Envelope check: a negative dip at a later month, and a reallocation that resolves it.

**Feature** (end-to-end):
- Create / edit / delete reallocations via the API routes.
- Selector renders all budgets sorted by current net.
- Dashboard alert appears when the running sum dips below zero and clears after a resolving
  reallocation.

---

## 10. Migration

```bash
php artisan make:migration add reallocations to budgets_table
```

Table `reallocations` as in §4. No columns added to `budget_months`.

---

## 11. SPECS.md updates

- Move "Borrowing / reallocation" from **Backlog** into the Backlog with the finalized design
  captured inline (persistence, scope, computation, aggregate net, selector, lifecycle).
- Remove the two resolved **Open design questions** (reallocation persistence → ledger; target
  amount → same as budgeted amount).
- Money-domain requirements (§49–52) already describe the envelope + reallocation behaviour;
  they stay as the authoritative rules.

---

## 12. Risks / open items

- **Stacking vs. unique** per (recipient, source, month) — **RESOLVED: unique.** One row per
  `(recipient, source, month)`; rows are **not** summed.
- **Positive-only action** — **RESOLVED: not restricted.** Any budget can be a source (per SPECS.md §48 / Backlog).
- **Self-reallocation** blocked (planned).
- **Authorization vs. sharing** — reallocations assume the budgets are accessible; if budget
  sharing is added later, ensure reallocations only link budgets the user can edit.
- Reallocations are **never repaid** (matches project scope).
