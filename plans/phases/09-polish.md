# Phase 09 — Structure & UX Polish (and feel the procedural pain)

> **Stage D — Quality** · Depends on: Phase 08 · Unblocks: Phase 10
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

Every page has the same shape, nothing is duplicated, and refreshing after a POST doesn't resubmit. Then write down exactly where procedural PHP is hurting you.

## Concepts You'll Practice

extraction into functions · `$_SESSION` flash messages · the POST/Redirect/GET pattern · `require` vs `require_once` · `__DIR__` · recognising duplication as a smell

## Files Produced

```
src/bootstrap.php    # one include that wires config, db, functions, validation
src/functions.php    # + flash(), get_flash(), redirect()
src/repositories.php # all query functions in one place
every page           # refactored to the uniform page shape
README.md            # + the reflection section
```

## Sub-phases

### 9.1 — Flash messages

- [ ] `function flash(string $type, string $message): void` — pushes onto `$_SESSION['flash'][]`
- [ ] `function get_flash(): array` — returns and **clears** in one call so it shows once
- [ ] Render them in `header.php` with a class per type (`success` / `error`)
- [ ] Use them for: transaction saved, transaction updated, transaction deleted, category added, category duplicate, any generic failure

### 9.2 — Redirect-after-POST everywhere

- [ ] `function redirect(string $path): never` — sends the header and exits. The `never` return type (PHP 8.1+) makes the exit explicit and stops static analysis complaining.
- [ ] Audit **every** POST handler: each one must end in a redirect on both success and failure (on failure, redirect back with errors in the session, or re-render with 422 — pick one and be consistent)
- [ ] Confirm: submit a form → refresh → no resubmit prompt, no duplicate row
- [ ] Confirm: submit → back button → sensible behaviour

### 9.3 — Extract the repeated queries

- [ ] Move every inline query into a named function in one file:
  - `all_categories(): array`
  - `category_totals(): array`
  - `find_transaction(int $id): ?array`
  - `transactions_for(array $filters): array`
  - `transactions_in_month(string $month): array`
  - `insert_transaction(array $clean): int`
  - `update_transaction(int $id, array $clean): bool`
  - `delete_transaction(int $id): bool`
  - `insert_category(array $clean): int`
- [ ] These still take no `$pdo` argument — they call `db()` internally. **Note this decision**: it's convenient but it's hidden global state. This is exactly the kind of thing you'll want to fix later.
- [ ] Pages must contain no SQL at all after this

### 9.4 — `src/bootstrap.php`

- [ ] One file that: sets the timezone, starts the session, `require_once`s config, db, functions, validation, calculations, repositories
- [ ] Every page's first line becomes `require __DIR__ . '/../src/bootstrap.php';`
- [ ] Compare against the old "four requires at the top of every file" and note the improvement
- [ ] Keep `calculations.php` pure — the bootstrap must not be required by it

### 9.5 — Uniform page shape

- [ ] Every page follows exactly this order:

  ```php
  <?php
  require __DIR__ . '/../src/bootstrap.php';

  // 1. handle input (only on POST handlers)
  // 2. fetch data
  // 3. compute / derive

  $pageTitle = '...';
  include __DIR__ . '/../src/partials/header.php';
  ?>
  <!-- 4. HTML with e() on everything dynamic -->
  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
  ```

- [ ] No page has logic after `include header`
- [ ] Consistent 4-space indentation and `snake_case` function names throughout
- [ ] Every function has parameter and return type declarations
- [ ] Run `php -l` on every file

### 9.6 — Write the reflection

- [ ] Add a **"What hurt, and why"** section to `README.md`
- [ ] Cover at least:
  - Global state: `db()` hiding the connection makes dependencies invisible
  - `$pdo` would otherwise have to be threaded through every function
  - Duplicated render logic across list/dashboard/recent
  - `$_GET`/`$_POST`/`$_SESSION` reached into from everywhere
  - No clear boundary between "what a transaction is" and "how it's stored"
  - Validation rules and the form markup are kept in sync by hand
  - No way to swap SQLite for MySQL without touching many files
- [ ] For each, name the concept that would fix it (dependency injection, a model class, a repository, a template engine, a router)
- [ ] This is the bridge to the OOP/patterns course the brief deliberately postpones

## Done When

No duplicated query or render block remains, and refreshing after a POST produces no resubmit prompt and no duplicate row.

## Verify

1. `grep -rn "SELECT\|INSERT\|UPDATE\|DELETE" public/` → **zero** hits.
2. `grep -rn "require" public/` → every page has exactly one `require` of the bootstrap.
3. Add a transaction, hit F5 → flash message, no duplicate.
4. Trigger a validation failure → flash message survives the redirect and appears once.
5. Delete a transaction → flash message confirms it.
6. Load all six pages → zero warnings with `error_reporting(E_ALL)`.
7. `php -l` every PHP file → clean.

## Gotchas

- `get_flash()` must *consume* the messages. If it doesn't clear them, they reappear on every page.
- A flash set and read in the same request needs care — decide whether reads happen before or after writes in the current request.
- `$_SESSION` requires `session_start()` on **every** request that touches it — including the redirect target.
- `require` on a missing file is a fatal error; `include` is a warning. For your own bootstrap, fatal is correct.
- Don't "fix" the global-state problem here by half-introducing classes. Note it and move on — that's a different course.

## Reference

- `header()` / redirects: <https://www.php.net/manual/en/function.header.php>
- Sessions: <https://www.php.net/manual/en/book.session.php>
- POST/Redirect/GET: <https://en.wikipedia.org/wiki/Post/Redirect/Get>
