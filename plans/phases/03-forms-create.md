# Phase 03 — Forms, `$_POST` & Validation (CREATE)

> **Stage B — Data & Writing** · Depends on: Phase 02 · Unblocks: Phase 04
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

Add income and expenses through a real HTML form. Invalid input is rejected with a specific message and the form re-renders with what you typed still in it.

## Concepts You'll Practice

`$_POST` · `$_SERVER['REQUEST_METHOD']` · HTML forms · input validation · `INSERT` · `lastInsertId()` · redirect-after-POST

## Files Produced

```
src/validation.php                          # parseAmountToCents(), validateTransaction()
src/partials/transaction-form-fields.php    # the shared field markup
public/transaction-new.php                  # render the add form
public/transaction-save.php                 # POST handler → INSERT → redirect
```

## Sub-phases

### 3.1 — `parseAmountToCents(string $raw): ?int`

- [ ] Put it in `src/validation.php`
- [ ] Accept `"12"`, `"12.5"`, `"12.50"`, `"1,234.56"` (strip separators), and surrounding whitespace
- [ ] Reject `""`, `"abc"`, `"-5"`, `"1.234"` (>2 decimals), `"1e3"`, and anything non-positive
- [ ] Return `null` on rejection — never `0`, since `0` is a valid-ish number and you need to tell them apart
- [ ] Implement it with **integer maths** (split on `.`), not `(int)($raw * 100)`
- [ ] Round-trip check: `formatMoney(parseAmountToCents($s))` should equal the normalised input

### 3.2 — `validateTransaction(array $input, array $categories): array`

- [ ] Return shape: `['errors' => [field => message], 'clean' => [...]]`
- [ ] **`type`** must be in `['income', 'expense']`
- [ ] **`amount`** via `parseAmountToCents()`; error message names the problem ("Amount must be a positive number with at most 2 decimals")
- [ ] **`category_id`** must be a positive integer **and** exist in `$categories` **and** its `kind` must equal `type` — this is the cross-field rule beginners miss
- [ ] **`occurred_on`** must match `Y-m-d` strictly: `DateTime::createFromFormat('Y-m-d', $v)` plus `getLastErrors()` check. `strtotime()` alone accepts garbage like `'next tuesday'`.
- [ ] **`note`** optional, trimmed, max 255 characters; empty string normalises to `null`
- [ ] Collect **all** errors, don't stop at the first — the user should see every problem at once
- [ ] `clean` contains only validated, normalised, correctly-typed values

### 3.3 — The form partial

- [ ] `src/partials/transaction-form-fields.php` renders: type (radio or select), amount (text), category (`<select>`), date, note
- [ ] It receives `$values` (sticky values) and `$errors` — plan the variable contract before writing it
- [ ] Use `e($values['amount'] ?? '')` for every `value=""` attribute
- [ ] Render an inline error next to each field when `isset($errors['field'])`
- [ ] Add `required` / `inputmode="decimal"` / `maxlength` — but treat these as **convenience only**; the server still validates
- [ ] Category `<select>` is still **hardcoded** for now — Phase 04 wires it to the DB

### 3.4 — `public/transaction-new.php`

- [ ] GET only. If `$_SERVER['REQUEST_METHOD'] !== 'GET'`, redirect to itself or show 405.
- [ ] Build a `$values` array of sensible defaults: `type = 'expense'`, `occurred_on = date('Y-m-d')`
- [ ] `$errors = []`
- [ ] `include` the header (stub it for now) and the form partial

### 3.5 — `public/transaction-save.php`

- [ ] Reject anything that isn't POST immediately
- [ ] Read `$_POST`, run `validateTransaction()`
- [ ] **On failure:** re-render the form with `$values = $_POST` and `$errors` — status code 422 is a nice touch
- [ ] **On success:** prepared `INSERT` into `transactions` with `created_at`/`updated_at` set to `date('Y-m-d H:i:s')`
- [ ] Confirm `$pdo->lastInsertId()` returns the new id
- [ ] Then `header('Location: transactions.php'); exit;` — **always exit after a redirect**
- [ ] Wrap the write in a try/catch for `PDOException` and surface a generic failure message

### 3.6 — Sticky values & error display

- [ ] Submit an empty form → every required field shows its message, nothing is lost
- [ ] Submit a bad amount → only the amount field errors
- [ ] Submit `type=income` with an expense category → the cross-field rule fires
- [ ] Verify the browser back button after a failed submit still shows your input

## Done When

Submitting a valid income and a valid expense each create a row visible on `transactions.php`, and invalid input is blocked with specific, per-field messages.

## Verify

| Input | Expected |
|---|---|
| empty form | errors on amount, type, category_id, occurred_on |
| amount `-5` | amount error |
| amount `abc` | amount error |
| amount `1.234` | amount error (too many decimals) |
| amount `0` | amount error |
| type `income` + expense category | category error |
| date `2025-02-30` | date error |
| note of 300 chars | note error |
| valid row | redirect, row appears in the list |

Also confirm: refreshing the list page after a successful save does **not** re-insert.

## Gotchas

- `header()` fails with "headers already sent" if you `echo` before it — even a blank line before `<?php`.
- Forgetting `exit` after `header('Location: ...')` lets the rest of the script run anyway.
- `$_POST` values are always **strings** (or arrays). `'0'` is falsy — use `isset()`/`!== ''`, not truthiness.
- `(int)$_POST['amount'] * 100` on `"19.99"` gives `1998`, not `1999`. Integer string maths only.
- Trusting the `required` attribute is the classic beginner mistake. Anyone can POST directly with curl.

## Reference

- `$_POST`: <https://www.php.net/manual/en/reserved.variables.post.php>
- `DateTime::createFromFormat`: <https://www.php.net/manual/en/datetime.createfromformat.php>
- `header()`: <https://www.php.net/manual/en/function.header.php>
