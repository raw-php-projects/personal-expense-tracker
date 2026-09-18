# AGENT.md — Guide for AI Agents

This file tells an AI coding agent **where to look** and **how to work** in this repository.

---

## 1. What This Project Is

A single-user **Personal Expense Tracker** built in **procedural PHP** with **PDO + SQLite**.

It exists primarily as a **learning vehicle** for PHP and web fundamentals: variables, arrays, functions, forms, `$_GET`/`$_POST`, validation, PDO, prepared statements, escaping, and basic security.

**This means the code is deliberately simple.** A more "elegant" solution is often the *wrong* solution here. See [Section 6](#6-non-negotiables).

Read [`ProjectBrief.md`](./ProjectBrief.md) for the original brief.

---

## 2. Read Order — Do This First, Every Session

1. **[`PROGRESS.md`](./PROGRESS.md)** — which phase is `working`, what's `done`, what's next.
2. **[`plans/README.md`](./plans/README.md)** — locked decisions, schema, route map, conventions.
3. **The current phase file** in [`plans/phases/`](./plans/phases) — its sub-phases are your task list.
4. **This file** — the rules below.
5. **[`docs/code-standards.md`](./docs/code-standards.md)** — how to write the code: DocBlocks, security, modern PHP. **Read this before writing any code.**
6. **[`docs/qa-checklist.md`](./docs/qa-checklist.md)** — if it exists, it's the manual test script.

Do not start writing code before doing 1–4.

---

## 3. Where To Look

| I need to... | Look at |
|---|---|
| Know what to work on next | [`PROGRESS.md`](./PROGRESS.md) → Status Board |
| Understand a phase's tasks | the phase file in `plans/phases/` |
| Know **how to write the code** | [`docs/code-standards.md`](./docs/code-standards.md) |
| Know an architectural decision | [`plans/README.md`](./plans/README.md) §1 |
| See the DB schema | [`plans/README.md`](./plans/README.md) §3, then `schema.sql` |
| Know a URL's purpose | [`plans/README.md`](./plans/README.md) §4 Route Map |
| Find a rule I must follow | [`plans/README.md`](./plans/README.md) §5 + this file §6 |
| Change an **app-wide setting** (currency, app name) | `src/config.php`, read via `config()` |
| Change a **page** | `public/*.php` — one file per page |
| Change **business logic** | `src/calculations.php` (pure), `src/validation.php` |
| Change **SQL** | `src/repositories.php` (Phase 09+), `src/db.php`, `src/filters.php` |
| Change **markup/layout** | `src/partials/*.php` |
| Change **shared helpers** | `src/functions.php` |
| Change **config** | `src/config.php` |
| Add a **test** | `tests/` |
| Update **progress** | [`PROGRESS.md`](./PROGRESS.md) — see §5 |

**The layer rule:** `public/` renders, `src/` decides, `data/` stores. If you're writing SQL in a `public/` file after Phase 09, you're in the wrong file.

---

## 4. How To Work — The Loop

For every unit of work:

1. **Read `PROGRESS.md`.** Identify the `working` phase. If none is `working`, take the next `not started` one and set it to `working` first (see §5).
2. **Read the phase file.** Work its sub-phases top to bottom.
3. **Tick checkboxes as you go** — edit the phase file, `- [ ]` → `- [x]`.
4. **Verify as you go.** Each phase has a **Verify** section. Run the checks relevant to what you just changed. Don't batch verification to the end.
5. **Check the non-negotiables** (§6) before you consider anything finished.
6. **When all sub-phases are ticked and Verify passes**, flip the phase to `done` in `PROGRESS.md` and add a Log entry.
7. **Then** move to the next phase — flipping it to `working` *before* writing code for it.

**Never skip a phase. Never work on two phases at once.**

---

## 5. Progress Tracking — `PROGRESS.md` Is Authoritative

`PROGRESS.md` is the **only** place phase status lives. Phase files must never contain a status — if you see one, it's stale.

### The three transitions

**Starting a phase**
```
row status:  not started → working     ← do this BEFORE writing any code
Started:     fill in today's date (YYYY-MM-DD)
header:      Currently working on → this phase
             Next phase to start → the following phase
Log:         add "<date> · <phase> · started"
```

**Finishing a phase**
```
row status:  working → done
Completed:   fill in today's date
header:      Currently working on → *nothing*
             Overall → refresh the count
             Last updated → today
Log:         add "<date> · <phase> · done"
```

**Blocked**
```
row status:  working → blocked
Log:         add an entry naming the blocker precisely
```

### Hard rules

- **Exactly one phase may be `working` at a time.**
- **Always update status *before* starting the work, not after.** Setting `working` afterwards defeats the entire mechanism — the user uses this file to see what is happening right now.
- A phase is **not** `done` because the code was written. It is `done` when its **Verify** section passes.
- Update `Last updated` on every change.

### Stretch goals

Phase 11's five items are tracked in their own table in `PROGRESS.md`. Update the specific item row, not the Phase 11 row, while working them. Mark the Phase 11 row `done` only when you stop working stretch items.

---

## 6. Non-Negotiables

These are the rules that make this codebase what it is. **Break one and you've broken the project.**

| # | Rule | Why |
|---|---|---|
| 1 | **No OOP. No classes. No frameworks.** Plain functions and files. | The brief explicitly says to experience procedural PHP's limits before abstracting. A class here is a defect, not an improvement. |
| 2 | **Every dynamic value printed to HTML goes through `e()`.** | XSS. `e()` wraps `htmlspecialchars(..., ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')`. |
| 3 | **Every SQL value is a `:placeholder`.** Only whitelist-validated identifiers may be interpolated. | SQL injection. See `filters.php` for the one place this is subtle. |
| 4 | **Validate and escape at the correct layer.** Escape on *output*, validate on *input*. Never escape on input. | Escaping on input double-encodes and corrupts stored data. |
| 5 | **Money is an integer number of cents.** Never a float. | `0.1 + 0.2 !== 0.3`. Conversion happens only at the edges via `parse_amount_to_cents()` / `format_money()`. |
| 6 | **`src/calculations.php` stays pure.** No `$pdo`, no `$_POST`, no `$_GET`, no `echo`, no `date()` defaults, no `require`. | It's the unit-test target. Impurity here costs you Phase 10. |
| 7 | **Destructive actions are POST-only and CSRF-verified.** | A `GET` delete can be fired by an `<img>` tag. |
| 8 | **Every POST handler redirects on completion.** | Prevents duplicate rows on refresh. |
| 9 | **`exit` after every `header('Location: ...')`.** | The rest of the script otherwise runs anyway. |
| 10 | **`session_start()` before any output.** | Even a stray newline breaks it. |
| 11 | **`data/` stays outside the docroot.** The docroot is `public/`. | The SQLite file must not be fetchable over HTTP. |
| 12 | **Every function has a PHPDoc block** — imperative summary, `@param`, `@return`, `@throws` where relevant. | This is a learning artefact; the *why* matters as much as the code. See [`docs/code-standards.md`](./docs/code-standards.md) §1. |
| 13 | **Comments explain *why*, never *what*.** No line-by-line narration. | The code already says what it does. A comment earns its place by recording a decision, a constraint, or a trap. |
| 14 | **Secure by default** — validate every input at the boundary, escape on output, allowlist rather than blocklist, fail closed. | See [`docs/code-standards.md`](./docs/code-standards.md) §2. |
| 15 | **Write modern PHP** — target the installed version (8.3.14): `strict_types`, typed signatures, `match`, nullsafe, `str_contains`, `never`. No deprecated functions. | Language features now; class-based features (enums, `readonly`, attributes) wait for the OOP follow-up. See [`docs/code-standards.md`](./docs/code-standards.md) §3. |

---

## 7. Conventions

Full detail in [`docs/code-standards.md`](./docs/code-standards.md).

- **Documentation:** a PHPDoc block on **every** function. One-line imperative summary ("Format…",
  "Validate…"), then `@param`/`@return` describing what the *type can't* — units, ranges, array
  shapes, what `null` means — plus `@throws` where relevant. `src/` files carry a header block saying
  what belongs in them.
- **Naming:** `snake_case` for functions, `$snake_case` for variables, `kebab-case` for public PHP filenames.
- **Type declarations:** every function declares parameter **and** return types.
- **Strict types:** `declare(strict_types=1);` as the first statement of every `.php` file.
- **Formatting:** PSR-12. 4 spaces. No tabs.
- **Money and currency:** never hardcode a currency symbol. Print amounts with `money()`, which reads `config('currency_symbol')`. `format_money()` is the pure number formatter — it takes the symbol as an argument and must stay free of configuration so `calculations.php` remains testable on its own.
- **Page shape (Phase 09+):** every page in `public/` is exactly:

  ```php
  <?php
  require __DIR__ . '/../src/bootstrap.php';

  // 1. handle input   2. fetch data   3. derive

  $pageTitle = '...';
  include __DIR__ . '/../src/partials/header.php';
  ?>
  <!-- 4. markup, e() on everything dynamic -->
  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
  ```

  No logic may appear after `include header`.
- **Paths:** always `__DIR__ . '/...'`. Never a relative path. Never `include 'src/x.php'`.
- **Dates:** stored as `'YYYY-MM-DD'`, formatted for display only at the edge.
- **Months:** compared as `'YYYY-MM'` via `strftime('%Y-%m', occurred_on)`.

---

## 8. Running Things

```bash
# Serve the app (from the project root)
php -S localhost:8000 -t public

# Create the database from scratch.
# NOTE: the sqlite3 CLI is NOT on PATH on this machine - only the PHP extension is.
# Do NOT run these; load the SQL through PDO instead (Phase 02 adds the script):
#   sqlite3 data/tracker.sqlite < schema.sql
#   sqlite3 data/tracker.sqlite < seed.sql

# Syntax-check a file (use liberally — it's instant)
php -l src/functions.php

# Run the test suite (Phase 10+)
vendor/bin/phpunit

# Fallback suite if Composer isn't available
php tests/run.php
```

**Windows note:** if `php` isn't on `PATH`, use the full path (`C:\php\php.exe`).

**Important:** the dev server is long-running. If you start it in the background to test, **stop it when you're done** so the port isn't left occupied.

---

## 9. Definition of Done

A phase is `done` only when **all** of these hold:

- [ ] Every sub-phase checkbox in its `plans/phases/` file is ticked.
- [ ] Its **Verify** section has been executed and every item passes.
- [ ] `php -l` is clean on every file you touched.
- [ ] The app loads with `error_reporting(E_ALL)` and emits **zero** warnings or notices.
- [ ] Every function written has a PHPDoc block, and every comment explains *why*, not *what*.
- [ ] The §5 checklist in [`docs/code-standards.md`](./docs/code-standards.md) passes for every file touched.
- [ ] No non-negotiable from §6 is violated.
- [ ] `PROGRESS.md` is updated per §5, including a Log entry.

---

## 10. Anti-Patterns — Do Not Do These

- ❌ Introducing a `Transaction` class, a `Repository` interface, or a DI container. **Not yet** — the brief defers this on purpose.
- ❌ Following the `.commandcode/skills/php-pro/` skill's architecture advice — DTOs, service classes, DI, namespaces, enums, PHPStan level 9. Take only its language-level rules; see [`docs/code-standards.md`](./docs/code-standards.md) §4.
- ❌ Adding Composer packages beyond PHPUnit. No router, no ORM, no template engine, no validation library.
- ❌ Leaving a function without a DocBlock, or writing comments that narrate *what* the code does.
- ❌ Using a deprecated function, `global`, `@`, or leaving `var_dump()` in committed code.
- ❌ Building a front controller / router. One `.php` file per page.
- ❌ Rewriting a phase's approach because you prefer it. If the plan is wrong, say so and ask — don't silently diverge.
- ❌ Working ahead into a later phase "while you're in there". Each phase ends in a runnable, verified state.
- ❌ Marking a phase `done` without running its Verify section.
- ❌ Setting a phase to `working` *after* doing the work.
- ❌ Editing a phase file's checkboxes without updating `PROGRESS.md` when the phase completes.
- ❌ Adding `the user wants...` commentary or status notes to code comments.

---

## 11. When Something Is Genuinely Wrong With The Plan

Phase files are a plan, not scripture. If you find a real problem — a rule that's impossible, a step that's out of order, a missing prerequisite — **stop and say so**:

1. Set the phase to `blocked` in `PROGRESS.md`.
2. Add a Log entry describing the problem precisely.
3. Tell the user what you found and what you'd change.

Do not silently invent a different approach. The user is following this plan phase by phase and needs to know when it stops matching reality.
