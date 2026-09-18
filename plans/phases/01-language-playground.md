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
src/config.php          # configuration — app name and currency
src/sample-data.php     # the hardcoded transaction array (temporary, dies in Phase 02)
src/calculations.php    # pure functions — no I/O, ever
src/functions.php       # view helpers — e(), config(), money()
public/playground.php   # experiment page (not part of the final app)
```

> **Important:** `calculations.php` must contain **functions only**. No `$pdo`, no `$_POST`, no `echo`, no `date()` defaults baked into logic. Keeping it pure is what makes Phase 10 painless. The sample data goes in its own file precisely so `calculations.php` stays pure.
>
> Function names are `snake_case` per [`AGENT.md`](../../AGENT.md#7-conventions) — `format_money`, not
> `formatMoney`. A few later phase files still use camelCase in their prose; the conventions document
> is what governs.

## Sub-phases

### 1.1 — The sample data

- [x] Create `src/sample-data.php` returning `$sampleTransactions`, an **indexed array of associative arrays**
  → 10 rows; outer array verified as a list, inner rows associative with the 5 keys
- [x] Each row: `type` (`'income'|'expense'`), `amount_cents` (int), `occurred_on` (`'YYYY-MM-DD'`), `category` (string), `note` (string or `null`)
  → confirmed `amount_cents` is a real `int`, never a numeric string
- [x] Include at least 8 rows spanning **two different months** so filtering has something to work with
  → 10 rows across two months. **Revised during 1.8:** the dates were originally pinned to fixed months,
    which meant the page would go blank as soon as the calendar moved on. They are now built from
    `DateTimeImmutable('first day of this month')` and the month before it. Verified no `YYYY-MM-DD`
    literal remains in the file, and that the construction survives January, year end, a short February
    and a leap day.
- [x] Deliberately include one row with a `null` note to practice `null` handling
  → 1 row: the `Freelance` entry in the previous month
- [x] Deliberately include one amount over 100,000 cents to exercise thousands separators
  → 5 rows qualify; largest is `320_000` cents → `3,200.00`

### 1.2 — `format_money(int $cents): string`

- [x] Create `src/calculations.php` with a file header DocBlock, then write the function in it
  → header states the purity rule: no PDO, no `$_POST`, no `$_GET`, no `$_SESSION`, no `echo`
- [x] `123456` → `'1,234.56'` · `5` → `'0.05'` · `0` → `'0.00'` · `-250` → `'-2.50'` · `100` → `'1.00'`
  → all pass, plus 8 more edge cases. **13 checked, 0 failed.**
- [x] Use integer maths for the cents/whole split — do **not** divide into a float and use `number_format()` on it
  → `intdiv()` + `%` do the split; `number_format()` only groups the already-integer thousands.
    The trap this avoids, measured: `(int)(19.99 * 100)` is **1998**, not 1999.
- [x] A negative amount renders with a leading `-` (`-2.50`), not as a separate sign
  → sign stripped before the split and re-applied in `sprintf`, so `-5` → `'-0.05'` and `-123456` → `'-1,234.56'`
- [x] Parameter type, return type, and a DocBlock

> **Learned while verifying:** `declare(strict_types=1)` binds to the **calling** file, not to the file
> it is written in. The same call raises a `TypeError` from a strict caller but silently coerces from a
> loose one — `format_money('1234')` returns `"12.34"` instead of failing. That is why every file
> declares it, and why the page shape in [`AGENT.md`](../../AGENT.md#7-conventions) starts with it.

### 1.3 — `sum_by_type()` and `monthly_totals()`

- [x] `sum_by_type(array $transactions, string $type): int`
  → one `foreach` with a strict `===` on `type`. An empty array — or a valid type with no matching rows — returns `0`.
- [x] Validate `$type` against an allowlist and throw on anything else
  → `in_array($type, ['income', 'expense'], true)` → `InvalidArgumentException`. Preferred over a silent
    `0`, which in a money total is a wrong answer indistinguishable from a right one. The message names
    the bad value: `Unknown transaction type "expence"; expected "income" or "expense".`
- [x] `monthly_totals(array $transactions, string $month): array` returning `['income' => int, 'expense' => int, 'net' => int]`
  → filters to the month, then delegates to `sum_by_type()` twice instead of re-implementing the sum.
    Passes only `'income'`/`'expense'` literals, so it cannot trip the new guard — asserted.
- [x] `$month` is `'YYYY-MM'` — compare against the first 7 characters of `occurred_on`
  → `substr($occurred_on, 0, 7) === $month`. Chosen over `str_starts_with()`: strictly length-exact,
    matches this instruction literally, and exercises `substr` from the brief's "Strings" list.
- [x] `net` is `income - expense` and **may be negative** — decide how the UI shows that
  → returned as a signed int; the sign is the UI's problem, not this function's. 1.6 renders it.
- [x] Both functions must tolerate an empty array and return zeroes, not warnings
  → tested with an error handler that promotes any notice or warning to a thrown exception

**22 checks, 0 failed** — hand-computed totals for both months, four rejection cases, a negative-net
case, an exact-key-and-order assertion, two no-throw assertions, and a reconciliation check that the
two months sum back to the whole set.

### 1.4 — `totals_by_category()`

- [x] `totals_by_category(array $transactions): array` returning category name → total cents
  → six categories from ten rows — Salary 640,000 · Freelance 165,050 · Rent 290,000 ·
    Groceries 39,060 · Transport 6,200 · Utilities 9,430 (all in cents)
- [x] Handle the case where the same category appears many times
  → `$totals[$category] ??= 0;` then `+=`, so repeats accumulate rather than overwrite
- [x] Use `??` or `isset()` when accumulating so you don't emit an undefined-key warning
  → `??=` (PHP 7.4+). Asserted under an error handler that promotes notices to exceptions.
- [x] Decide and document: should income and expense with the same name be merged or kept apart?
  → **Merged.** A flat `name => total` contract cannot honestly express anything else. The DocBlock
    flags that a name used for both types would produce a net figure wearing a total's label, and that
    the Phase 05 dashboard needs a real per-type breakdown and will need a different shape.

**18 checks, 0 failed** — including three cross-checks reconciling this function against `sum_by_type()`,
and one asserting the documented all-digit-key coercion really does happen.

### 1.5 — The escaping helper `e()`

> **Moved forward from Phase 02.** Sub-phase 1.6 renders values into HTML, and non-negotiable #2 says
> every dynamic value must go through `e()`. Phase 02 now *extends* `src/functions.php` instead of
> creating it.

- [x] Create `src/functions.php` with a file header DocBlock
  → header draws the line against `calculations.php`: this file may touch the environment (later phases
    add `header()`, `$_SESSION`) but holds no domain logic
- [x] `function e(?string $value): string` wrapping `htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8')`
  → exactly as specified
- [x] Accept `?string` so a `null` note passes straight through with no caller-side check
  → `e(null)` returns `''`, verified
- [x] Use `ENT_QUOTES` — otherwise single-quoted attributes break out of their context
  → both breakout attempts neutralised: `" onmouseover="alert(1)` → `&quot; onmouseover=&quot;alert(1)`,
    and the single-quoted variant → `&#039; onmouseover=&#039;alert(1)`
- [x] Add `ENT_SUBSTITUTE` alongside it
  → `htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`
- [x] This becomes the **only** sanctioned way to print dynamic data. Commit to it now.

> **Resolved: `ENT_SUBSTITUTE` added.** With `ENT_QUOTES` alone, input that is not valid UTF-8 makes
> `htmlspecialchars` return `''` — the entire value vanishes with no warning. Probed first rather than
> assumed: SQLite stores and returns such bytes **byte-identically** (`636166c328` in, `636166c328` out),
> so a bad paste or mangled import really does reach this function. It now renders `caf\u{FFFD}(`, so
> the damage is visible instead of the field going blank. PHP 8.1+ defaults to
> `ENT_QUOTES | ENT_SUBSTITUTE`; naming the flags explicitly is what removed the safety net.
>
> Note this was never a security hole — escaping was complete either way. It is a data-visibility fix.

**6 assertions, 0 failed**, plus the demonstrative cases above. Also confirms the type contract: under
`strict_types`, `e(123)` and `e(['a'])` both raise `TypeError` — an integer needs an explicit `(string)`
cast, which forces a deliberate choice at the call site.

### 1.6 — Render `public/playground.php`

- [x] `require` the sample data, `calculations.php`, and `functions.php`
  → **Revised during 1.8:** order now matters — `functions.php` → set the timezone → `sample-data.php`.
    `functions.php` defines `config()`, and the timezone must be applied before the data loads because
    that file computes dates. Loading the data first would build it under UTC. Phase 09's bootstrap
    makes this ordering rule an explicit single step.
- [x] `foreach` the transactions into an HTML `<table>`
  → 10 rows
- [x] Use `switch` on `type` to add a CSS class (`income` / `expense`)
  → 4 `income` rows, 6 `expense` rows. `switch` rather than `match` because the brief lists it as a
    concept to learn; the `default` branch exists so `$rowClass` can never be undefined if a type
    somehow slips through.
- [x] Use `if/else` (or a ternary) to print `—` when `note` is `null`
  → ternary at the point of output, keeping the null check and the escaping in one place. Asserted that
    the null-note row renders a dash, and that a non-null row renders its text with no dash.
- [x] Run every dynamic value through `e()` — `category` and `note` included
  → audited line by line: **12 short-echoes, 0 without `e()`**
- [x] Print the month summary above the table
  → three cards fed by `monthly_totals()`. The month is hardcoded to `2026-09` here — 1.7 makes it dynamic.
- [x] Print the by-category breakdown below the table
  → from `totals_by_category()`, sorted here with `arsort()`, since that function deliberately returns
    first-seen order and leaves ordering to its caller

**27 checks, 0 failed**, exercised over HTTP rather than by reading the source. These include the
`Done When` comparison — September income **3,650.50** / expense **1,731.75** / net **1,918.75** —
matching the figures hand-computed back in 1.3, and a sweep confirming no `Warning`, `Notice`,
`Fatal error`, `Deprecated`, `Undefined` or `Uncaught` text reaches the page.

### 1.7 — Configurable currency

> **Added mid-phase, on review.** 1.2 formatted bare numbers and 1.6 printed them with no currency
> mark. The gap: the app should let you change currency in **one place**. This sub-phase landed after
> 1.6, which is why it sits before Dates rather than after.

- [x] Create `src/config.php` returning a configuration array
  → `app_name`, `currency_code`, `currency_symbol`. Both code and symbol are kept because `$` alone is
    ambiguous — USD, CAD, AUD all use it.
- [x] Add a `config()` accessor to `src/functions.php`
  → lazily requires `config.php` and caches it in a `static` for the request. Uses `require`, not
    `require_once`, because `require_once` returns `true` on a repeat include and would poison the cache.
- [x] Give `format_money()` an optional symbol parameter while keeping it pure
  → `format_money(int $cents, string $symbol = '')`. The symbol is passed in, never looked up, so
    `calculations.php` stays free of configuration. Asserted by calling it *before* `functions.php` is
    loaded — the existing six no-symbol results are unchanged.
- [x] Add `money()` to `src/functions.php` as the config-aware wrapper
  → `format_money($cents, (string) config('currency_symbol'))`. This is what templates call.
- [x] Put the sign before the symbol
  → `-$2.50`, not `$-2.50`. Asserted the wrong form does **not** appear, and that a multi-character
    symbol (`CHF `) still works.
- [x] Point the playground at `money()`, and show the currency code in the page

**Proof it is genuinely one place:** with the server running, `currency_symbol` in `config.php` was
changed from `$` to `€` — no code edited, no restart — and every card, table row, and breakdown total
switched from `$3,650.50` to `€3,650.50`. Reverted, and the page came back byte-identical.

**25 checks, 0 failed** at unit level, plus the end-to-end swap. Also confirmed the served bytes are
valid UTF-8 (`E2 82 AC`, 20 occurrences, zero mojibake lead bytes) rather than a mangled symbol.

### 1.8 — Dates

- [x] Set the timezone once at the top of the playground with `date_default_timezone_set()`
  → reads `config('timezone')`, set to **Asia/Dhaka**. PHP was defaulting to UTC, and on this UTC+6
    machine a UTC-based "current month" reports the month that just ended for the first six hours of
    every month. Asserted the value is a real IANA identifier, not the Windows name.
- [x] Default the display month to the current month using `date('Y-m')`
  → now resolves to `2026-09`, replacing the hardcoded literal
- [x] Add a second summary for the **previous** month using `DateTime::modify('-1 month')` + `format('Y-m')`
  → both months render through a single loop, so the three-card markup exists once rather than twice
- [x] Note in a comment why `strtotime('-1 month')` can surprise you at month ends
  → plus the fix: anchor to `first day of this month` **before** subtracting. Measured against 12
    dates, the naive form was wrong on **7**. It is not only the 31st — 29 March fails too, because
    29 February 2026 does not exist and PHP rolls *forward* to 1 March.

**34 checks, 0 failed** — including the anchored-vs-naive comparison against an independently derived
truth (step back one day from the 1st, rather than trusting either implementation), and the rendered
page showing Current month 2026-09 and Previous month 2026-08 with totals that reconcile.

## Done When

The playground table matches totals you calculated by hand for the sample month.

**Met.** Current month — income **3,650.50**, expense **1,731.75**, net **1,918.75**. Previous month —
income **4,400.00**, expense **1,715.15**, net **2,684.85**. Both sets are the figures derived by hand
back in 1.3 and 1.6.

## Verify

1. Add a new row to `$sampleTransactions` — totals update correctly with **no other edit**.
2. Run `php -l` on `src/calculations.php`, `src/functions.php`, and `public/playground.php` — no syntax errors.
3. Every function has a DocBlock, a parameter type on every parameter, and a return type.
4. Set `error_reporting(E_ALL)` and confirm **zero** warnings/notices on the page.

**Result — all four pass (10 checks, 0 failed), run 2026-09-18.**

- **1** — appending one income row to an *in-memory copy* moved income and net by exactly 12,345 cents,
  left expense untouched, added exactly one category, and did not mutate the real array.
- **2** — `php -l` clean on all six PHP files in the project.
- **3** — checked by **reflection**, not by eye. All seven functions report a DocBlock, every parameter
  typed, and a declared return type (`format_money` → `string`, `sum_by_type` → `int`,
  `monthly_totals` → `array`, `totals_by_category` → `array`, `e` → `string`, `config` → `mixed`,
  `money` → `string`).
- **4** — no `Warning`, `Notice`, `Fatal error`, `Deprecated`, `Undefined`, `Uncaught` or `Parse error`
  text in the served HTML.

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
