# Phase 04 — Categories

> **Stage B — Data & Writing** · Depends on: Phase 03 · Unblocks: Phase 05
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

Categories become something you manage **in the app**, not by hand in `sqlite3`. The transaction form's dropdown is populated from the database.

## Concepts You'll Practice

more forms · `UNIQUE` constraints · catching `PDOException` · rendering `<select>` from query results · referential integrity

## Files Produced

```
public/categories.php       # list grouped by kind + add form
public/category-save.php    # POST handler → INSERT → redirect
src/validation.php          # + validateCategory()
```

## Sub-phases

### 4.1 — `validateCategory(array $input): array`

- [ ] `name`: required, trimmed, 1–50 characters, collapse internal whitespace runs
- [ ] `kind`: must be in `['income', 'expense']`
- [ ] Return the same `['errors' => [...], 'clean' => [...]]` shape as `validateTransaction()` for consistency
- [ ] Decide whether names are case-sensitively unique — SQLite's `UNIQUE` is case-sensitive by default, so `Food` and `food` would both be allowed. Either accept that or normalise.

### 4.2 — `public/categories.php`

- [ ] GET only
- [ ] Query: `SELECT * FROM categories ORDER BY kind, name`
- [ ] Group into income/expense sections — do the grouping in PHP with `foreach`, not with two queries
- [ ] Show a count of transactions per category (a `LEFT JOIN` + `COUNT`, or a second grouped query)
- [ ] Render the add form below the list
- [ ] Show sticky values and errors after a failed submit (use the redirect/flash pattern or pass through `$_SESSION`)

### 4.3 — `public/category-save.php`

- [ ] Reject non-POST
- [ ] Validate with `validateCategory()`
- [ ] Prepared `INSERT`
- [ ] Catch `PDOException` and detect the unique violation — check the SQLSTATE `23000` (or SQLite's `SQLITE_CONSTRAINT_UNIQUE`), **not** the message text
- [ ] On duplicate: re-render with "A category with that name already exists"
- [ ] On success: redirect to `categories.php` and exit
- [ ] Catch and report any other `PDOException` as a generic failure

### 4.4 — Wire the dropdown to the database

- [ ] `public/transaction-new.php` queries categories and passes them to the form partial
- [ ] The partial groups options with `<optgroup>` by kind, or uses a `data-kind` attribute
- [ ] The selected category survives an invalid re-render
- [ ] **Decide the UX for the cross-field rule:** when the user switches type, should the category list filter? If yes, that needs JS — note it, and make sure the server still enforces it either way.

### 4.5 — Delete a category (optional but recommended)

- [ ] `public/category-delete.php`, POST only, redirect after
- [ ] Before deleting, count referencing transactions: `SELECT COUNT(*) FROM transactions WHERE category_id = ?`
- [ ] If count > 0, refuse with a clear message listing the count
- [ ] If count is 0, delete
- [ ] Try deleting a referenced category directly in `sqlite3` too — see the FK error, and note that it only fires because `PRAGMA foreign_keys = ON` is set per connection

## Done When

You can add a category in the browser and immediately choose it in the transaction form.

## Verify

1. Add `Health` as an expense → appears in the list and in the dropdown without a page-level code change.
2. Add `Health` again → friendly "already exists" error, **no stack trace, no 500**.
3. Add an empty name → validation error.
4. Type the raw URL `category-save.php` into the address bar (a GET) → rejected, not a silent no-op.
5. Delete a category with transactions → refused with a count.
6. Delete an unused category → removed.

## Gotchas

- SQLite's `UNIQUE` allows multiple `NULL`s, but `name` is `NOT NULL` here, so that's moot — still worth knowing.
- Matching on exception *message* text is brittle and locale-dependent. Match on `$e->getCode()` / SQLSTATE.
- A `<select>` with no `name` attribute submits nothing. Obvious, but it's the usual cause of "why is category_id empty".
- `ORDER BY kind, name` puts `expense` before `income` alphabetically. If you want income first, use a `CASE` expression or sort in PHP.

## Reference

- `PDOException`: <https://www.php.net/manual/en/class.pdoexception.php>
- `<optgroup>`: <https://developer.mozilla.org/en-US/docs/Web/HTML/Element/optgroup>
