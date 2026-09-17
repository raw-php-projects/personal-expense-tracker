# Phase 11 — Stretch Goals

> **Stage E — Stretch** · Depends on: Phase 10 · Each item is independent
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

Do these **one at a time**, and only after Phase 10 is `done`. Each is self-contained: finishing one leaves the app working. Pick in any order, but they're listed roughly easiest → hardest.

Before starting any item here, read [`/AGENT.md`](../../AGENT.md) and note that the same non-negotiables apply.

---

## S1 — CSV Export

**Goal:** Download the currently filtered transaction list as a CSV.

**Files:** `public/export.php`

- [ ] Reuse `buildFilters()` and `buildTransactionQuery()` from Phase 06 — the export must respect the active filters
- [ ] Send headers **before** any output: `Content-Type: text/csv; charset=utf-8`, `Content-Disposition: attachment; filename="transactions-2025-01.csv"`
- [ ] Write with `fputcsv()` to `php://output` — no temp files
- [ ] Include a header row: date, type, category, amount, note
- [ ] Output the amount as a plain decimal (`12.34`), **not** `formatMoney()`'s `1,234.56` — spreadsheets misparse thousands separators
- [ ] Guard against CSV injection: prefix cells starting with `=`, `+`, `-`, or `@` with a single quote
- [ ] Ensure `application/json`/HTML escaping is **not** applied — `e()` is for HTML, this is CSV. Escaping the wrong layer is a real bug.
- [ ] **Verify:** filtered export row count equals the on-screen count; open in Excel/LibreOffice and confirm columns land correctly
- [ ] **Verify:** a note containing a comma and a newline still round-trips through one CSV row

---

## S2 — Monthly Report Page

**Goal:** A 12-month view so you can see spending trends.

**Files:** `public/reports.php`

- [ ] Query 12 months of aggregates in **one** statement, not 12 queries. `GROUP BY strftime('%Y-%m', occurred_on)` over a date range.
- [ ] Build a complete month list in PHP (`DateTime` + `DatePeriod`) and left-join the results onto it so empty months still appear
- [ ] Render: month, income, expense, net, and a text/CSS bar proportional to expense
- [ ] Find the maximum expense across the 12 months to scale the bars — avoid division by zero when all months are empty
- [ ] Make each month row link to the dashboard for that month
- [ ] **Verify:** a month with no data shows `0.00` and an empty bar, not a missing row
- [ ] **Verify:** the year boundary works (December → January)

---

## S3 — Recurring Expenses

**Goal:** Define a rule (e.g. rent, monthly) and generate its occurrences.

**Files:** `schema-rec recurring.sql` → `schema.sql` append, `public/recurring.php`, `src/recurring.php`

- [ ] New table `recurring_rules`: `id`, `category_id`, `type`, `amount_cents`, `note`, `day_of_month`, `started_on`, `ends_on` (nullable), `last_generated_on` (nullable)
- [ ] `function generate_due_rules(string $through_month): int` — creates transactions for each month from `last_generated_on` (or `started_on`) up to the target month
- [ ] **Idempotency is the whole problem here.** Re-running must not duplicate. Use `last_generated_on` as the watermark and only ever move it forward.
- [ ] Handle `day_of_month` = 31 in a 30-day month: clamp to the last day and **document** the choice
- [ ] Trigger generation explicitly with a button, or on every dashboard load (decide and document)
- [ ] **Verify:** run generation twice → the second run adds nothing
- [ ] **Verify:** create a rule starting 3 months ago → 3 transactions appear, each in the right month
- [ ] **Verify:** a rule with `ends_on` set stops generating after that date

---

## S4 — Budget Limits

**Goal:** Set a monthly budget per category and warn when you exceed it.

**Files:** `schema.sql` (`categories.monthly_budget_cents` nullable, or a separate table), `public/budgets.php`, dashboard

- [ ] Decide: a column on `categories`, or a `budgets` table with a month? The table is more flexible (budgets can change per month); the column is simpler. **Write down why you chose.**
- [ ] Track spend per category per month — you already have `totalsByCategory()` and the month query
- [ ] Dashboard shows budget vs actual with a progress bar and an over-budget state
- [ ] Handle: no budget set (skip), budget of 0 (block everything or ignore — decide), spend exactly equal to budget (is that over?)
- [ ] **Verify:** a category over budget is visually distinct
- [ ] **Verify:** a budget of `0` behaves per your documented decision
- [ ] **Verify:** budgets reset when you change month

---

## S5 — MySQL Migration

**Goal:** Prove the PDO layer was portable.

**Files:** `src/config.php`, `schema.sql`, a new `schema-mysql.sql`

- [ ] Create the MySQL database and a `schema-mysql.sql`
- [ ] Translate: `INTEGER PRIMARY KEY AUTOINCREMENT` → `INT AUTO_INCREMENT PRIMARY KEY`; `TEXT` → `VARCHAR(...)` where a length is meaningful; add `ENGINE=InnoDB` for FK support
- [ ] `CHECK` constraints are enforced in MySQL 8.0.16+; older versions parse and ignore them — verify your version and note it
- [ ] `datetime('now')` → `CURRENT_TIMESTAMP`
- [ ] Update `config.php` to build a MySQL DSN and accept credentials; **do not** hardcode a password — read from an env var with a sane local default
- [ ] SQLite's `strftime('%Y-%m', ...)` has no MySQL equivalent — replace with `DATE_FORMAT(occurred_on, '%Y-%m')` or, better, a `BETWEEN` range that works on both
- [ ] Audit for other SQLite-isms: `||` concatenation, `LIMIT x OFFSET y` (fine), boolean literals
- [ ] **Document every incompatibility you hit** — that list is the actual deliverable
- [ ] **Verify:** `docs/qa-checklist.md` passes end-to-end on MySQL
- [ ] **Verify:** the PHPUnit suite still passes unchanged (it needs no database — that's the point)

---

## Cross-Cutting Reminders

- All the Phase 08 rules still apply to every new feature: `e()` on output, prepared statements, CSRF on POST, validation server-side.
- New POST handlers must redirect after success (Phase 09 rule).
- New pages must use `header.php`/`footer.php` and the uniform page shape.
- New pure logic belongs in a pure file so Phase 10's suite can reach it.
- Update `/PROGRESS.md` when you finish an item.
