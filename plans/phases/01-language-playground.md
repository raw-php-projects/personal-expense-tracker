# Phase 01 — PHP Language Playground

> **Stage A — Foundations** · Depends on: Phase 00 · Unblocks: Phase 02
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

Render a table of transactions with month totals — using **hardcoded data only**. No database, no forms, no HTML forms yet. This phase is purely about the PHP language.

## Concepts You'll Practice

variables · scalars · arrays (indexed + associative) · strings · operators · `if/else` · `switch` · `foreach` · functions · type declarations · `null` · boolean logic · date/time

## Files Produced

```
src/sample-data.php     # the hardcoded transaction array (temporary, dies in Phase 02)
src/calculations.php    # pure functions — no I/O, ever
public/playground.php   # experiment page (not part of the final app)
```

> **Important:** `calculations.php` must contain **functions only**. No `$pdo`, no `$_POST`, no `echo`, no `date()` defaults baked into logic. Keeping it pure is what makes Phase 10 painless. The sample data goes in its own file precisely so `calculations.php` stays pure.

## Sub-phases

### 1.1 — The sample data

- [ ] Create `src/sample-data.php` returning `$sampleTransactions`, an **indexed array of associative arrays**
- [ ] Each row: `type` (`'income'|'expense'`), `amount_cents` (int), `occurred_on` (`'YYYY-MM-DD'`), `category` (string), `note` (string or `null`)
- [ ] Include at least 8 rows spanning **two different months** so filtering has something to work with
- [ ] Deliberately include one row with a `null` note to practice `null` handling
- [ ] Deliberately include one amount over 100,000 cents to exercise thousands separators

### 1.2 — `formatMoney(int $cents): string`

- [ ] Write the function in `src/calculations.php`
- [ ] `123456` → `'1,234.56'` · `5` → `'0.05'` · `0` → `'0.00'` · `-250` → `'-2.50'`
- [ ] Use integer maths for the cents/whole split — do **not** divide into a float and use `number_format()` on it
- [ ] Add a return type declaration and a parameter type declaration

### 1.3 — `sumByType()` and `monthlyTotals()`

- [ ] `sumByType(array $transactions, string $type): int`
- [ ] `monthlyTotals(array $transactions, string $month): array` returning `['income' => int, 'expense' => int, 'net' => int]`
- [ ] `$month` is `'YYYY-MM'` — compare against the first 7 characters of `occurred_on`
- [ ] `net` is `income - expense` and **may be negative** — decide how the UI shows that
- [ ] Both functions must tolerate an empty array and return zeroes, not warnings

### 1.4 — `totalsByCategory()`

- [ ] `totalsByCategory(array $transactions): array` returning category name → total cents
- [ ] Handle the case where the same category appears many times
- [ ] Use `??` or `isset()` when accumulating so you don't emit an undefined-key warning
- [ ] Decide and document: should income and expense with the same name be merged or kept apart?

### 1.5 — Render `public/playground.php`

- [ ] `require` the sample data and `calculations.php`
- [ ] `foreach` the transactions into an HTML `<table>`
- [ ] Use `switch` on `type` to add a CSS class (`income` / `expense`)
- [ ] Use `if/else` (or a ternary) to print `—` when `note` is `null`
- [ ] Print the month summary above the table
- [ ] Print the by-category breakdown below the table

### 1.6 — Dates

- [ ] Set the timezone once at the top of the playground with `date_default_timezone_set()`
- [ ] Default the display month to the current month using `date('Y-m')`
- [ ] Add a second summary for the **previous** month using `DateTime::modify('-1 month')` + `format('Y-m')`
- [ ] Note in a comment why `strtotime('-1 month')` can surprise you at month ends

## Done When

The playground table matches totals you calculated by hand for the sample month.

## Verify

1. Add a new row to `$sampleTransactions` — totals update correctly with **no other edit**.
2. Run `php -l src/calculations.php` and `php -l public/playground.php` — no syntax errors.
3. Every function has a type declaration on both parameters and return.
4. Set `error_reporting(E_ALL)` and confirm **zero** warnings/notices on the page.

## Deliberate Reflection

Before moving on, write two sentences in your own words:

- How much of the logic lives in one file right now?
- If a second page needed the same totals, what would you have to do?

You will feel this friction grow through Phases 03–07. It is the point of the exercise.

## Gotchas

- `$array['missing']` on a real array emits a warning in PHP 8; use `??` or `array_key_exists()`.
- Comparing float money will bite you — that's why everything is cents.
- `foreach ($a as $row)` copies; `foreach ($a as &$row)` references — prefer the copy and build a new array.
- Concatenation is `.` not `+`.

## Reference

- Arrays: <https://www.php.net/manual/en/language.types.array.php>
- Functions: <https://www.php.net/manual/en/language.functions.php>
- `DateTime`: <https://www.php.net/manual/en/class.datetime.php>
