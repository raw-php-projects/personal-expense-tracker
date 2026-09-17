# Phase 07 — Edit & Delete (UPDATE / DELETE)

> **Stage C — Features** · Depends on: Phase 06 · Unblocks: Phase 08
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

Full CRUD. You can correct a mistaken amount and remove a transaction you shouldn't have entered.

## Concepts You'll Practice

`UPDATE` · `DELETE` · `rowCount()` · reusing a form partial for two purposes · POST-only destructive actions · 404-style handling

## Files Produced

```
public/transaction-edit.php     # load + render edit form
public/transaction-save.php     # extended to branch INSERT / UPDATE
public/transaction-delete.php   # POST → DELETE → redirect
src/db.php or src/functions.php # + findTransaction(), findCategory()
```

## Sub-phases

### 7.1 — `findTransaction(int $id): ?array`

- [ ] Prepared `SELECT` joining categories, `WHERE t.id = :id`
- [ ] Return `null` when not found — the `?array` return type makes the contract explicit
- [ ] Decide where this lives: a `src/repositories.php`-ish file or alongside `db.php`. Procedural is fine; just pick one and stay consistent.

### 7.2 — `public/transaction-edit.php`

- [ ] GET only
- [ ] Read `$_GET['id']`, validate it as a positive integer
- [ ] `findTransaction()` → if `null`, respond `404` with `http_response_code(404)` and a friendly page
- [ ] Convert `amount_cents` **back** to a decimal string for the input: `formatMoney()` gives `1,234.56` — you need `1234.56` for a `<input type="text">`. Write a `centsToInput(int): string` helper rather than stripping commas ad hoc.
- [ ] Reuse `transaction-form-fields.php` with the row as `$values`
- [ ] Include a hidden `<input type="hidden" name="id" value="...">`
- [ ] Add a hidden `_method`-style marker if it helps the save handler decide — or just branch on the presence of `id`

### 7.3 — Extend `public/transaction-save.php`

- [ ] Still POST-only
- [ ] Determine mode: `id` present and valid → UPDATE, otherwise INSERT
- [ ] **Validate the same way in both modes** — reuse of `validateTransaction()` is mandatory
- [ ] In UPDATE mode, confirm the row actually exists before updating (a deleted row plus a stale form should not silently succeed)
- [ ] UPDATE sets `updated_at = date('Y-m-d H:i:s')` and **does not** touch `created_at`
- [ ] Check `rowCount()` — `0` means nothing changed (same values) or the row vanished. Distinguish those.
- [ ] Same redirect-on-success / re-render-on-error behaviour as Phase 03
- [ ] When re-rendering an invalid edit, preserve the hidden `id` so the retry stays an edit

### 7.4 — `public/transaction-delete.php`

- [ ] **POST only.** A GET must be refused — a destructive action must never be triggerable by a link, a prefetch, or a `<img src>`.
- [ ] Validate `id` as a positive integer
- [ ] Prepared `DELETE`, then check `rowCount()`
- [ ] Redirect back to `transactions.php` (preserving filters if you're feeling generous — pass a `return_to` hidden field, **validated against a local allowlist**, never a raw redirect target)
- [ ] Handle the already-deleted case gracefully

### 7.5 — Wire up the UI

- [ ] Add an **Edit** link on every row in `transactions.php` and on the dashboard's recent list
- [ ] Add a delete `<form method="post" action="transaction-delete.php">` per row with a hidden `id`
- [ ] Add `onsubmit="return confirm('Delete this transaction?')"` — and write a comment that this is **UX only, not a security control**
- [ ] Style destructive buttons distinctly
- [ ] Add a "Cancel" link on the edit page back to the list

### 7.6 — Referential thinking

- [ ] What happens if you edit a transaction to `type=income` while its category is an expense category? The Phase 03 cross-field rule should catch it — confirm it does on the **update** path too.
- [ ] What happens if the category is deleted between page load and submit? Decide and handle.
- [ ] Confirm deleting a transaction does not delete its category, and vice versa.

## Done When

Editing and deleting both take effect immediately on the list **and** the dashboard.

## Verify

1. Edit an amount → dashboard total changes accordingly.
2. Edit a date to a different month → it leaves the old month's dashboard and joins the new one.
3. Edit with an invalid amount → error shown, hidden `id` preserved, retry still updates (doesn't insert a duplicate).
4. Delete a row → gone from list, dashboard, and any filtered view.
5. POST to `transaction-delete.php` with no `id` → handled, no crash.
6. GET `transaction-delete.php?id=1` → refused.
7. `transaction-edit.php?id=999999` → 404 page, not a blank screen.
8. Submit the edit form twice quickly → one update, no duplicate row.

## Gotchas

- `rowCount()` returns `0` both for "no matching row" and "values identical". If that distinction matters, `SELECT` first or compare.
- `PDO::rowCount()` is reliable for SQLite/MySQL writes, but **not** for `SELECT` on some drivers — don't use it to count reads.
- `formatMoney()` output (`"1,234.56"`) is **not** valid for an amount input. That's what `centsToInput()` is for.
- An edit form that loses its `id` on a validation failure becomes a create on retry. Classic bug.
- Deleting by GET is a real vulnerability class (CSRF via `<img>`). POST-only is the fix.

## Reference

- `UPDATE`: <https://www.sqlite.org/lang_update.html>
- `rowCount()`: <https://www.php.net/manual/en/pdostatement.rowcount.php>
