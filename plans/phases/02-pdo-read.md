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
src/config.php          # created in Phase 01 — 2.1 adds db_path to it
src/db.php              # PDO factory function
src/functions.php       # created in Phase 01 — e() lives here; new helpers join it
schema.sql              # CREATE TABLE statements
seed.sql                # sample categories + transactions
public/transactions.php # read-only list page
data/tracker.sqlite     # created at runtime (gitignored)
```

## Sub-phases

### 2.1 — Extend `src/config.php`

> `config.php` already exists from Phase 01 (sub-phase 1.7), holding `app_name`, `currency_code`, and
> `currency_symbol`, read through the `config()` accessor in `functions.php`. **Add to it — don't
> recreate it.** It still returns an array; no constants.

- [x] Add `db_path` to the existing array
  → `__DIR__ . '/../data/tracker.sqlite'`
- [x] `db_path` must be absolute and outside the docroot: `__DIR__ . '/../data/tracker.sqlite'`
  → verified it is absolute (drive-letter form), contains no `/public/`, and that
    `realpath(dirname($dbPath))` equals `realpath('data')` — so `src/../data` genuinely resolves
    rather than merely looking correct
- [x] Keep the timezone in config too, so there is one place for settings rather than two
  → already in place from 1.8; asserted still intact alongside the other keys
- [ ] Create the `data/` directory if it doesn't exist, or fail with a clear message
  → **moved to 2.3.** `config.php` only returns an array, and creating a directory would give it a side
    effect — merely *reading* configuration would start touching the filesystem. The directory check
    belongs where the database is actually opened.

**14 checks, 0 failed.**

### 2.2 — `schema.sql`

- [x] Write the `categories` table exactly as specified in [`README.md`](../README.md#3-database-schema-schemasql)
  → as specified. `AUTOINCREMENT` is explained rather than copied: it is what promises an id is never
    reused after a delete, which a bare `INTEGER PRIMARY KEY` does not.
- [x] Write the `transactions` table with the `CHECK` constraints and both indexes
  → both `CHECK`s, the `NOT NULL`s, the foreign key, and both indexes. Deliberately **plain
    `CREATE TABLE`, no `IF NOT EXISTS`** — applying it twice now fails loudly instead of silently doing
    nothing. Database lifetime is the bootstrap script's business (2.3), not the schema's.
- [x] Include `PRAGMA foreign_keys = ON;` at the top
  → included, with a comment stating plainly that it is **per connection**: setting it here only covers
    the run that creates the tables, so `db.php` has to set it again on every connect (2.4).
- [x] Reason about each `CHECK` before writing it — what bad state does it prevent?
  → every constraint in the file carries a comment naming the bad state it blocks: a third category
    kind, a duplicate category name that would quietly split one budget across two rows, a zero or
    negative amount that would contradict `type`, a missing date, an orphaned category reference.

**23 checks, 0 failed** — the file was applied to a real SQLite connection and every constraint was
*exercised* rather than eyeballed:

- **accepted:** two valid categories, a valid transaction, an explicit `NULL` note
- **rejected:** `kind = 'transfer'` · duplicate category name · missing `name` · missing `kind` ·
  `type = 'other'` · `amount_cents = 0` · `amount_cents = -500` · missing `occurred_on` ·
  missing `category_id` · `category_id = 9999` · deleting a category that is referenced
- **confirmed allowed on purpose:** a transaction whose `type` disagrees with its category's `kind`.
  The row really is stored — which is precisely why Phase 03 has to validate that rule in PHP.
- **defaults:** `created_at` / `updated_at` fill themselves in (`2026-09-19 06:29:50`, UTC)

### 2.3 — Create and seed the database

> **Environment note (recorded during Phase 00):** the `sqlite3` command-line tool is **not on PATH** on
> this machine — only the PHP `sqlite3` *extension* is. So the shell commands below won't run as written.
> Run `schema.sql` and `seed.sql` through a small PDO bootstrap script instead; it's the same code path
> the app uses. See [00-setup.md § Environment Notes](./00-setup.md#environment-notes).

- [x] Ensure the `data/` directory exists before opening the database (moved here from 2.1)
  → `bin/init-db.php` creates it with a recursive `mkdir` before connecting. PDO creates the database
    *file* on demand but not the *folder*, and a missing folder surfaces as a vague "unable to open
    database file".
- [x] Apply the schema: `sqlite3 data/tracker.sqlite < schema.sql` — or, on this machine, run it through PDO
  → `bin/init-db.php`. **Deviation:** that introduces a `bin/` directory the target structure did not
    list, so `plans/README.md` §2 has been updated to include it.
- [x] Write `seed.sql`: the 6 categories from the overview plus ~8 transactions across 2 months
  → 6 categories with explicit ids, plus 10 transactions across the current and previous month
- [x] Apply it: `sqlite3 data/tracker.sqlite < seed.sql`
  → the same script. One constraint worth recording: `exec()` runs **every** statement in a string, but
    `prepare()` runs only the **first** — measured, 1 of 3 rows inserted. A parameterised
    multi-statement seed is therefore impossible, which is why `seed.sql` is self-contained SQL and
    builds its dates with `strftime(..., 'localtime', ...)` rather than taking them as parameters.
- [x] Verify with `sqlite3 data/tracker.sqlite "SELECT COUNT(*) FROM transactions;"`
  → 10 rows, read back through PDO
- [x] Verify the join works in raw SQL: `SELECT t.*, c.name FROM transactions t JOIN categories c ON c.id = t.category_id LIMIT 5;`
  → all 10 rows join, and every one finds its category
- [x] Decide: does re-running `seed.sql` duplicate rows? If so, make it idempotent or document that it needs a fresh DB.
  → **Documented — and my first answer was wrong.** I wrote that a second run would duplicate everything.
    The test disproved it: it throws `UNIQUE constraint failed: categories.id` and stops at that first
    failing statement, duplicating nothing. That is a side effect of the explicit ids, not a designed
    guarantee, so the file now says so — drop the ids and it *would* silently duplicate. `init-db.php`
    refuses to touch an existing database unless passed `--fresh`.

**23 checks, 0 failed.**

Worth flagging: `seed.sql` dates use `'localtime'`, so SQLite follows the **machine's** timezone while
the app follows `config('timezone')`. They agree here (both `Asia/Dhaka`), but they are two independent
sources. Changing the configured timezone without changing the machine's would let them drift.

### 2.4 — `src/db.php`

- [x] Write `function db(): PDO` returning a lazily-created singleton (a `static` variable inside the function is fine)
  → `static $pdo`, opened on first call and reused after. Asserted `db() === db()` holds.
- [x] DSN: `'sqlite:' . $config['db_path']`
  → `'sqlite:' . (string) config('db_path')`
- [x] Options: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `ATTR_EMULATE_PREPARES => false`
  → all three are set. **`ATTR_EMULATE_PREPARES` turns out to be a no-op on SQLite** — the constructor
    accepts it without complaint, but reading it back throws
    `SQLSTATE[IM001]: Driver does not support this function`. Kept because the plan asks for it and
    because it keeps the options correct if the database is ever swapped for MySQL, but the comment
    says plainly that it does nothing here rather than implying a benefit it does not deliver.
- [x] Execute `PRAGMA foreign_keys = ON` on connect
  → set on every connect, and checked as **live enforcement** rather than a reported value: inserting a
    transaction with `category_id = 9999` is rejected with a FOREIGN KEY error. A bare `new PDO` against
    the same file reports `0`, which demonstrates the setting really is per connection and this is what
    switches it on.
- [x] Confirm a bad path throws a `PDOException` rather than failing silently
  → `SQLSTATE[HY000] [14] unable to open database file`

**15 checks, 0 failed.** Also confirmed end to end that it reads the seeded database (10 transactions,
6 categories), that rows come back associative without asking, and that bound parameters work.

One trade-off recorded in the DocBlock: `db()` reads the path from config itself rather than taking it
as an argument. Short call sites, but a caller cannot point it at a different database — which is the
hidden-dependency pain this project is meant to run into before reaching for OOP.

### 2.5 — `public/transactions.php`

> `src/functions.php` and its `e()` helper were created in Phase 01 (sub-phase 1.5) — this phase only
> *uses* them.

- [x] `require` config → db → functions → calculations (in that order)
  → **Revised:** config is read through the `config()` accessor rather than `require`d, so the page
    requires `functions.php` (which pulls in `calculations.php`) and then `db.php`. Requiring
    `config.php` directly would hand back an array nobody uses.
- [x] Call `db()` and run a prepared `SELECT` joining `categories`, `ORDER BY occurred_on DESC, id DESC`
  → prepared and executed. The `id` tie-break earns its place: without it, two transactions sharing a
    date could swap order between page loads.
- [x] Render rows with `e()` on every dynamic value
  → audited by parsing every `<?=` in the file: **10 short-echoes, none bypassing `e()` or `money()`**
- [x] Show `formatMoney($row['amount_cents'])` from Phase 01 — the pure function finally earns its keep
  → rendered with `money()` rather than `format_money()`, because the symbol is configurable (1.7). So
    both Phase 01 helpers — the formatter and `e()` — finally earn their keep on a real page.
- [x] Handle the empty-table case with a friendly message instead of an empty `<table>`
  → exercised for real by swapping in a schema-only database: no table rendered, message shown, naming
    the command that would fix it
- [x] Wrap the whole page in `<html><body>` — no partials yet, that comes in Phase 05
  → plus a short inline `<style>` so the table is actually readable. Phase 05 replaces it with
    `assets/style.css`.

**23 checks against the seeded database and 14 against the empty one, 0 failed.**

Checked rather than assumed: PDO returns `amount_cents` as a real PHP `integer`, not a string, so
`money($transaction['amount_cents'])` needs no cast. An initial `(int)` cast was written, measured
against, and then removed.

**Added on request:** a `<tfoot>` carrying **Total income** and **Total expense**.

- Totalled with `sum_by_type()` over the rows already fetched, rather than a second query — so the
  footer can never disagree with the table above it, and the Phase 01 pure function is reused rather
  than reimplemented.
- Both footer rows carry a `total` class alongside `income` / `expense`. They keep the same colour, but
  become distinguishable from data rows — which the tests needed, and which any later styling will want.

**27 further checks for the totals**, including two independent cross-checks: the footer against `SUM()`
in SQL, and the footer against the amounts parsed back out of the rendered body rows.

## Done When

Seeded rows appear in a table, and a note containing `"quotes" & <b>tags</b>` renders as **literal text**, not markup.

**Met.** A note of `"quotes" & <b>tags</b> <script>alert(1)</script>` rendered as
`&quot;quotes&quot; &amp; &lt;b&gt;tags&lt;/b&gt; &lt;script&gt;alert(1)&lt;/script&gt;` — no raw quote,
no live tag, nothing executable.

## Verify

1. Insert a row manually via `sqlite3` → it appears on refresh with no code change.
2. Rename `data/tracker.sqlite` and reload → a clear error, not a blank page.
3. Confirm the SQL string contains **zero** concatenated values — only `:placeholders`.
4. Request `http://localhost:8000/../data/tracker.sqlite` → denied. (The docroot is `public/`.)

**Result — all four pass (13 checks, 0 failed), run 2026-09-19.**

- **1** — inserted a row with PDO (the `sqlite3` CLI is not installed), reloaded: 11 rows, the new amount
  rendered, and the footer total moved with it. Deleted afterwards, and confirmed the page returned to
  10 rows and the original total.
- **2** — renamed `data/tracker.sqlite` and reloaded. The page answers with
  `Fatal error: Uncaught PDOException: SQLSTATE[HY000]: General error: 1 no such table: transactions`,
  naming the file and line. A clear error, not a blank page. **But see the note below about the status code.**
- **3** — every SQL string in the codebase was extracted and inspected. **4 found, none interpolating a
  PHP variable** — each is either parameterless or `:placeholder`-ready.
- **4** — `/data/tracker.sqlite`, `/../data/tracker.sqlite` (sent raw with `curl --path-as-is`, so the
  client did not resolve the `..`), `/schema.sql` and `/src/db.php` all return **404**. Only `public/`
  is reachable.

> **⚠️ Found while verifying — a fatal error answers `HTTP 200`, not `500`.**
> Measured side by side: a page that throws with `display_errors` untouched returns **500**; the identical
> throw after `ini_set('display_errors', '1')` returns **200**. Every page in this project sets that ini
> at runtime, so a failure currently reports "200 OK" with the error in the body — which a monitor, proxy
> or crawler would read as success. The fix belongs wherever `display_errors` gets turned off, so it is
> recorded against **8.6**.

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
