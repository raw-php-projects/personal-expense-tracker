# Phase 05 — Dashboard: Monthly Totals

> **Stage C — Features** · Depends on: Phase 04 · Unblocks: Phase 06
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

`/` becomes a real dashboard: current month's income, expense, net, a per-category breakdown, and the most recent transactions.

## Concepts You'll Practice

reusing pure functions with real DB data · `GROUP BY` · `SUM` · `strftime()` · `DateTime` month arithmetic · layouts via `include` partials

## Files Produced

```
src/partials/header.php     # <head>, nav, output buffering or plain include
src/partials/footer.php     # closing tags, scripts
public/index.php            # the dashboard
```

## Sub-phases

### 5.1 — Layout partials

- [ ] `src/partials/header.php`: doctype, `<head>`, `<link>` to `assets/style.css`, nav (`Dashboard` · `Transactions` · `Add` · `Categories`), page title from a variable
- [ ] `src/partials/footer.php`: closing tags and any script tag
- [ ] Decide the variable contract — e.g. `$pageTitle` must be set **before** the include. Document it in a comment at the top of `header.php`.
- [ ] Refactor `transactions.php`, `transaction-new.php`, `categories.php` to use both partials
- [ ] Add `public/assets/style.css` with a minimal but pleasant stylesheet (cards, table, form rows, income/expense colours)

### 5.2 — Month selection

- [ ] Read `$_GET['month']` if present; validate it strictly against `/^\d{4}-(0[1-9]|1[0-2])$/`
- [ ] Default to `date('Y-m')` when absent or invalid
- [ ] Render **Previous** / **Next** links using `DateTime::modify('±1 month')`
- [ ] Guard the next link so you can't wander into the far future if that bothers you — decide and document

### 5.3 — Monthly summary

- [ ] Query the month's rows: `WHERE strftime('%Y-%m', occurred_on) = :month`
- [ ] Feed the results into `monthlyTotals()` from Phase 01 — **do not** rewrite the summing in SQL. The point is to reuse the tested pure function.
- [ ] Render three cards: Income, Expense, Net
- [ ] Style a negative net differently from a positive one
- [ ] Sanity check against a raw SQL query: `SELECT type, SUM(amount_cents) FROM transactions WHERE strftime('%Y-%m', occurred_on) = '2025-01' GROUP BY type;`

### 5.4 — By-category breakdown

- [ ] Use `totalsByCategory()` from Phase 01 on the same rows
- [ ] Render as a table: category, type, total, and **percentage of that type's total**
- [ ] Sort descending by amount
- [ ] Handle percentages when the total is zero (avoid division by zero)
- [ ] Add a simple CSS bar using the percentage as a width — inline `style="width: X%"` is acceptable here

### 5.5 — Recent transactions

- [ ] `SELECT ... ORDER BY occurred_on DESC, id DESC LIMIT 5`
- [ ] Render compactly with a link through to the filtered list (Phase 06) or the transaction
- [ ] Show an "Add your first transaction" call to action when the table is empty

### 5.6 — Empty and edge states

- [ ] A month with no transactions → zeroes and a friendly empty state, not warnings
- [ ] A month with only expenses → income shows `0.00`, net is negative
- [ ] `$_GET['month']=9999-99` → falls back to the current month, no error

## Done When

Dashboard totals for a month exactly match a manual `SUM()` query for that month.

## Verify

1. Run the raw SQL sum and compare against the rendered cards — must match to the cent.
2. Bump one transaction's `amount_cents` in `sqlite3`, reload → the dashboard reflects it.
3. Navigate to a month with no data → clean empty state, no PHP notices.
4. `error_reporting(E_ALL)` and walk every page — zero warnings.
5. Confirm `header.php`/`footer.php` are included on **all four** pages, not just the dashboard.

## Gotchas

- `strftime('%Y-%m', occurred_on)` on an indexed column **cannot** use `idx_tx_occurred_on` — SQLite has no functional indexes by default. Fine at this data size; note it as a scaling lesson.
- `DateTime::modify('-1 month')` on 31 March gives 3 March-ish behaviour in some cases. Use `->modify('first day of last month')` if you need exactness.
- Including a header partial after you've already output HTML causes "headers already sent" on the *next* redirect.
- Percentage formatting: watch for `0/0`.

## Reference

- Aggregates: <https://www.sqlite.org/lang_aggfunc.html>
- `strftime`: <https://www.sqlite.org/lang_datefunc.html>
