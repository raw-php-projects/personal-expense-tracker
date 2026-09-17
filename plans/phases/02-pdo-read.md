# Phase 02 — PDO Connection & Reading from SQLite

> **Stage B — Data & Writing** · Depends on: Phase 01 · Unblocks: Phase 03
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

The transaction list page reads **real rows** from a SQLite database through PDO. The hardcoded sample data from Phase 01 is retired.

## Concepts You'll Practice

tables · primary keys · `SELECT` · `WHERE` · `ORDER BY` · `JOIN` · PDO connection options · prepared statements · `fetchAll` vs `fetch` · output escaping

## Files Produced

```
src/config.php          # returns a config array
src/db.php              # PDO factory function
src/functions.php       # e() and other shared helpers
schema.sql              # CREATE TABLE statements
seed.sql                # sample categories + transactions
public/transactions.php # read-only list page
data/tracker.sqlite     # created at runtime (gitignored)
```

## Sub-phases

### 2.1 — `src/config.php`

- [ ] Return an **array** (don't define constants yet): `db_path`, `timezone`, `app_name`
- [ ] `db_path` must be absolute and outside the docroot: `__DIR__ . '/../data/tracker.sqlite'`
- [ ] Set the timezone with `date_default_timezone_set()` in one place
- [ ] Create the file `data/` if it doesn't exist, or fail with a clear message

### 2.2 — `schema.sql`

- [ ] Write the `categories` table exactly as specified in [`README.md`](../README.md#3-database-schema-schemasql)
- [ ] Write the `transactions` table with the `CHECK` constraints and both indexes
- [ ] Include `PRAGMA foreign_keys = ON;` at the top
- [ ] Reason about each `CHECK` before writing it — what bad state does it prevent?

### 2.3 — Create and seed the database

> **Environment note (recorded during Phase 00):** the `sqlite3` command-line tool is **not on PATH** on
> this machine — only the PHP `sqlite3` *extension* is. So the shell commands below won't run as written.
> Run `schema.sql` and `seed.sql` through a small PDO bootstrap script instead; it's the same code path
> the app uses. See [00-setup.md § Environment Notes](./00-setup.md#environment-notes).

- [ ] Apply the schema: `sqlite3 data/tracker.sqlite < schema.sql` — or, on this machine, run it through PDO
- [ ] Write `seed.sql`: the 6 categories from the overview plus ~8 transactions across 2 months
- [ ] Apply it: `sqlite3 data/tracker.sqlite < seed.sql`
- [ ] Verify with `sqlite3 data/tracker.sqlite "SELECT COUNT(*) FROM transactions;"`
- [ ] Verify the join works in raw SQL: `SELECT t.*, c.name FROM transactions t JOIN categories c ON c.id = t.category_id LIMIT 5;`
- [ ] Decide: does re-running `seed.sql` duplicate rows? If so, make it idempotent or document that it needs a fresh DB.

### 2.4 — `src/db.php`

- [ ] Write `function db(): PDO` returning a lazily-created singleton (a `static` variable inside the function is fine)
- [ ] DSN: `'sqlite:' . $config['db_path']`
- [ ] Options: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `ATTR_EMULATE_PREPARES => false`
- [ ] Execute `PRAGMA foreign_keys = ON` on connect
- [ ] Confirm a bad path throws a `PDOException` rather than failing silently

### 2.5 — `src/functions.php`

- [ ] `function e(?string $value): string` wrapping `htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8')`
- [ ] This is the **only** sanctioned way to print dynamic data. Commit to it now.

### 2.6 — `public/transactions.php`

- [ ] `require` config → db → functions → calculations (in that order)
- [ ] Call `db()` and run a prepared `SELECT` joining `categories`, `ORDER BY occurred_on DESC, id DESC`
- [ ] Render rows with `e()` on every dynamic value
- [ ] Show `formatMoney($row['amount_cents'])` from Phase 01 — the pure function finally earns its keep
- [ ] Handle the empty-table case with a friendly message instead of an empty `<table>`
- [ ] Wrap the whole page in `<html><body>` — no partials yet, that comes in Phase 05

## Done When

Seeded rows appear in a table, and a note containing `"quotes" & <b>tags</b>` renders as **literal text**, not markup.

## Verify

1. Insert a row manually via `sqlite3` → it appears on refresh with no code change.
2. Rename `data/tracker.sqlite` and reload → a clear error, not a blank page.
3. Confirm the SQL string contains **zero** concatenated values — only `:placeholders`.
4. Request `http://localhost:8000/../data/tracker.sqlite` → denied. (The docroot is `public/`.)

## Gotchas

- `ATTR_EMULATE_PREPARES => false` means real server-side prepares. With SQLite this is the default anyway, but set it explicitly so the habit carries to MySQL.
- `fetchAll()` returns `[]` for no rows — check `empty()`, don't check `=== false`.
- PDO's `FETCH_ASSOC` is a connection default; you can still override per call.
- A relative `db_path` breaks the moment you run a script from another directory. Always use `__DIR__`.
- `PRAGMA foreign_keys` is **per connection**, not per database. It must be set every connect.

## Reference

- PDO: <https://www.php.net/manual/en/book.pdo.php>
- Prepared statements: <https://www.php.net/manual/en/pdo.prepared-statements.php>
- SQLite `CHECK`: <https://www.sqlite.org/lang_createtable.html>
