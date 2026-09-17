# Phase 10 — Manual QA Checklist + PHPUnit

> **Stage D — Quality** · Depends on: Phase 09 · Unblocks: Phase 11
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

Two test stories: a written manual script anyone can run, and an automated suite for the pure functions that were designed for it back in Phase 01.

## Concepts You'll Practice

Composer · PHPUnit · arranging code for testability · assertions · edge-case thinking · writing docs for a stranger

## Files Produced

```
composer.json
phpunit.xml
tests/CalculationsTest.php
tests/ValidationTest.php     (optional)
docs/qa-checklist.md
README.md                    # + how to run everything
```

## Sub-phases

### 10.1 — Composer and PHPUnit

- [ ] `composer init` (or hand-write `composer.json`)
- [ ] `composer require --dev phpunit/phpunit`
- [ ] Confirm PHPUnit matches your PHP version
- [ ] Add `bin/` and `vendor/` handling to `.gitignore` as needed (`vendor/` is already there)
- [ ] Commit `composer.json` and `composer.lock`

**If Composer isn't available:** write `tests/run.php` — a plain script that requires `calculations.php` and calls a small `assert_equal($expected, $actual, $label)` helper, printing pass/fail and exiting non-zero on failure. Same coverage, zero dependencies.

### 10.2 — Configuration and autoloading

- [ ] `composer.json` → `autoload` → `psr-4` is for classes, so it won't help with plain functions. Instead: `"autoload": { "files": ["src/calculations.php", "src/validation.php"] }`
- [ ] Run `composer dump-autoload`
- [ ] Confirm `calculations.php` and `validation.php` have **no** `require` statements of their own — that's the purity paying off
- [ ] `phpunit.xml`: bootstrap `vendor/autoload.php`, one `<testsuite>` pointing at `tests/`, and `<php>` env vars if needed

### 10.3 — `tests/CalculationsTest.php`

- [ ] One test method per behaviour, names describing the rule not the function
- [ ] `formatMoney`:
  - `formatMoney(123456)` → `'1,234.56'`
  - `formatMoney(5)` → `'0.05'`
  - `formatMoney(0)` → `'0.00'`
  - `formatMoney(-250)` → `'-2.50'`
  - `formatMoney(100)` → `'1.00'` (not `'1.0'`, not `'1'`)
  - a very large number → correct grouping
- [ ] `sumByType`:
  - empty array → `0`
  - array with no matching type → `0`
  - mixed array → only the requested type sums
  - `null` note in a row doesn't break it
- [ ] `monthlyTotals`:
  - only rows in the requested month count
  - returns all three keys even for an empty month
  - `net` is negative when expenses exceed income
  - `net` equals `income - expense`
- [ ] `totalsByCategory`:
  - groups duplicates
  - empty input → `[]`
  - documented behaviour for same-name income and expense
- [ ] Use a data provider for `formatMoney` — it's the ideal candidate

### 10.4 — `tests/ValidationTest.php` (recommended)

- [ ] `parseAmountToCents` is pure, so it's testable:
  - valid: `'12'`, `'12.5'`, `'12.50'`, `'1,234.56'`, `'  9.99  '`
  - invalid: `''`, `'abc'`, `'-5'`, `'0'`, `'1.234'`, `'1e3'`, `'12.'`?
  - decide and **test** the `'12.'` and `'.5'` cases explicitly rather than leaving them to chance
- [ ] Round-trip property: for every valid input, `formatMoney(parseAmountToCents($s))` equals the normalised form
- [ ] `validateTransaction`: each rule from Phase 03 gets a test
  - the cross-field rule (income + expense category) is the most valuable one
  - `occurred_on` rejects `'2025-02-30'` and `'next tuesday'`
  - note length boundary: exactly 255 passes, 256 fails

### 10.5 — `docs/qa-checklist.md`

- [ ] A numbered manual script covering **every phase's Verify table**, in order
- [ ] Each item: the action, the expected result, and a checkbox
- [ ] Must include the adversarial cases from Phase 08 (XSS, SQLi, CSRF, GET-delete)
- [ ] Must include the full-app checks from [`README.md`](../README.md#6-final-verification-whole-app)
- [ ] Include a "fresh install" section: delete `data/*.sqlite`, run `schema.sql` + `seed.sql`, confirm the app comes up
- [ ] Write it for a stranger — someone who has never seen this project should be able to run it

### 10.6 — Document it in `README.md`

- [ ] How to install and run (server command, DB setup, seed)
- [ ] How to run the tests (`vendor/bin/phpunit` or `php tests/run.php`)
- [ ] The project structure and what lives where
- [ ] The "What hurt, and why" reflection from Phase 09
- [ ] The verification checklist from the overview

## Done When

`vendor/bin/phpunit` is green, and `docs/qa-checklist.md` is complete enough for someone else to run without asking you questions.

## Verify

1. `vendor/bin/phpunit` → all green.
2. Break `formatMoney` on purpose (return the wrong grouping) → the suite **fails** with a useful message. Revert.
3. Break `validateTransaction`'s cross-field rule → the suite fails. Revert.
4. Delete `data/tracker.sqlite`, follow only the README + checklist → a working app.
5. Confirm the test suite runs **without a database** — if it needs one, your functions aren't pure.

## Gotchas

- PHPUnit tests that touch `$_POST` or `$_SESSION` are a different discipline (integration). Don't go there yet — the whole point of Phase 01's purity was to avoid it.
- `assertSame` vs `assertEquals`: for `'0.00'` vs `0.00` you want `assertSame` to catch type drift.
- Composer's `files` autoload runs on **every** request in a web context too — fine here, but be aware.
- `phpunit.xml` schema changes between major PHPUnit versions; copy the schema from the installed version's docs if it complains.
- Tests must not depend on the current date. If `monthlyTotals` defaults to "now", pass the month explicitly in tests.

## Reference

- PHPUnit: <https://docs.phpunit.de/>
- Data providers: <https://docs.phpunit.de/en/11.5/writing-tests-for-phpunit.html#data-providers>
