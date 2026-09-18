# Personal Expense Tracker — Master Plan

Procedural PHP + PDO + SQLite. Single user. No framework, no OOP, no patterns — deliberately.
Goal: learn PHP/web fundamentals by shipping one small working milestone per phase.

> **This file is the overview.** Each phase has its own file in [`phases/`](./phases) with sub-phases and checklists.
> Live status lives in [`/PROGRESS.md`](../PROGRESS.md) — that file is the single source of truth for what is done.
> Working in this repo as an AI agent? Read [`/AGENT.md`](../AGENT.md) first.

---

## Phase Index

| # | Phase | File | Stage |
|---|-------|------|-------|
| 00 | Environment & Skeleton | [00-setup.md](./phases/00-setup.md) | A — Foundations |
| 01 | PHP Language Playground | [01-language-playground.md](./phases/01-language-playground.md) | A — Foundations |
| 02 | PDO Connection & Reading from SQLite | [02-pdo-read.md](./phases/02-pdo-read.md) | B — Data & Writing |
| 03 | Forms, `$_POST` & Validation (CREATE) | [03-forms-create.md](./phases/03-forms-create.md) | B — Data & Writing |
| 04 | Categories | [04-categories.md](./phases/04-categories.md) | B — Data & Writing |
| 05 | Dashboard: Monthly Totals | [05-dashboard.md](./phases/05-dashboard.md) | C — Features |
| 06 | Filtering with `$_GET` | [06-filtering.md](./phases/06-filtering.md) | C — Features |
| 07 | Edit & Delete (UPDATE / DELETE) | [07-edit-delete.md](./phases/07-edit-delete.md) | C — Features |
| 08 | Security Hardening | [08-security.md](./phases/08-security.md) | D — Quality |
| 09 | Structure & UX Polish | [09-polish.md](./phases/09-polish.md) | D — Quality |
| 10 | Manual QA Checklist + PHPUnit | [10-testing.md](./phases/10-testing.md) | D — Quality |
| 11 | Stretch Goals | [11-stretch.md](./phases/11-stretch.md) | E — Stretch |

Order is strict: `00 → 01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09 → 10 → 11`.
Phases 00–10 are sequential and each depends on the previous. Do not start a phase until the prior phase's **Verify** step passes.

---

## 1. Locked Decisions

| Decision | Choice | Why |
|---|---|---|
| Language | Procedural PHP (no classes) | Brief says experience the limitations before abstracting |
| Database | SQLite (file: `data/tracker.sqlite`) | Zero-config, PDO built in, trivially migratable to MySQL |
| DB access | PDO + prepared statements only | Learn SQLi prevention properly from day one |
| Server | `php -S localhost:8000 -t public` | No Apache config, docroot isolation for free |
| Auth | None | Keeps forms/PDO/sessions from being buried under auth complexity |
| Front controller | None — one `.php` file per page | No premature routing architecture |
| Money storage | `INTEGER` cents (`amount_cents`) | Avoids float rounding errors; a real lesson |
| Templates | Plain PHP + `include` partials | No template engine |

**Ground rules for every phase:**
1. Every value echoed into HTML goes through `e()` (escaping helper).
2. Every SQL statement is a prepared statement — never string-concatenated values.
3. Every phase ends in a runnable state you can open in a browser.
4. Never move to the next phase with a broken milestone.

---

## 2. Target Project Structure

```
personal-expense-tracker/
├── public/                     # web root (only this is web-accessible)
│   ├── index.php               # dashboard: monthly totals + recent
│   ├── transactions.php        # list + filters
│   ├── transaction-new.php     # add form (render only)
│   ├── transaction-edit.php    # edit form (render only)
│   ├── transaction-save.php    # POST handler: create/update
│   ├── transaction-delete.php  # POST handler: delete
│   ├── categories.php          # category list + add form
│   ├── category-save.php       # POST handler: create category
│   └── assets/style.css
├── src/                        # NOT web-accessible
│   ├── bootstrap.php           # Phase 09: one require that wires everything
│   ├── config.php              # Phase 01: app name, currency; Phase 02 adds db_path
│   ├── db.php                  # Phase 02: PDO factory
│   ├── functions.php           # Phase 01: e(); later phases add redirect(), flash(), csrf
│   ├── validation.php          # Phase 03: parseAmountToCents(), validate*
│   ├── calculations.php        # Phase 01: PURE functions (no I/O, unit-testable)
│   ├── filters.php             # Phase 06: $_GET parsing + query builder
│   ├── repositories.php        # Phase 09: all SQL lives here
│   ├── sample-data.php         # Phase 01 only — retired in Phase 02
│   └── partials/
│       ├── header.php
│       ├── footer.php
│       └── transaction-form-fields.php
├── data/
│   └── tracker.sqlite          # gitignored
├── tests/
│   ├── CalculationsTest.php
│   └── ValidationTest.php
├── docs/
│   ├── qa-checklist.md
│   └── security-notes.md
├── plans/                      # the plan itself
│   ├── README.md               #   ← this file: the master overview
│   └── phases/                 #   ← one file per phase, 00–11
├── schema.sql                  # CREATE TABLE statements
├── seed.sql                    # sample categories + a few transactions
├── composer.json
├── phpunit.xml
├── .gitignore
├── AGENT.md                    # guide for AI agents
├── PROGRESS.md                 # live phase status
├── README.md
└── ProjectBrief.md
```

Rationale: `public/` as docroot means the SQLite file and `src/` cannot be fetched over HTTP by accident — a security lesson worth learning early, and cheap to set up.

---

## 3. Database Schema (`schema.sql`)

```sql
PRAGMA foreign_keys = ON;

CREATE TABLE categories (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  name        TEXT    NOT NULL UNIQUE,
  kind        TEXT    NOT NULL CHECK (kind IN ('income','expense')),
  created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE transactions (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id  INTEGER NOT NULL REFERENCES categories(id),
  type         TEXT    NOT NULL CHECK (type IN ('income','expense')),
  amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
  occurred_on  TEXT    NOT NULL,                 -- 'YYYY-MM-DD'
  note         TEXT,
  created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_tx_occurred_on ON transactions(occurred_on);
CREATE INDEX idx_tx_category    ON transactions(category_id);
```

Seed data: categories `Salary (income)`, `Freelance (income)`, `Groceries`, `Rent`, `Transport`, `Utilities (expense)` + ~8 transactions spread over 2 months so totals and filters have something to show.

---

## 4. Route Map

| URL | File | Method | Purpose |
|---|---|---|---|
| `/` | `public/index.php` | GET | Dashboard: month totals, breakdown, recent |
| `/transactions.php` | `public/transactions.php` | GET | List + filters |
| `/transaction-new.php` | `public/transaction-new.php` | GET | Render add form |
| `/transaction-edit.php?id=` | `public/transaction-edit.php` | GET | Render edit form |
| `/transaction-save.php` | `public/transaction-save.php` | POST | Create or update, then redirect |
| `/transaction-delete.php` | `public/transaction-delete.php` | POST | Delete, then redirect |
| `/categories.php` | `public/categories.php` | GET | Category list + add form |
| `/category-save.php` | `public/category-save.php` | POST | Create category, then redirect |

---

## 5. Cross-Cutting Conventions

Full detail in [`docs/code-standards.md`](../docs/code-standards.md).

- **Documentation:** a PHPDoc block on **every** function — one-line imperative summary, then
  `@param`/`@return` saying what the *type can't* (units, ranges, array shapes, what `null` means),
  plus `@throws` where relevant. Comments explain **why**, never narrate *what*.
- **Strict types:** `declare(strict_types=1);` as the first statement of every `.php` file.
- **Typing:** every function declares parameter **and** return types.
- **Formatting:** PSR-12 — 4 spaces, no tabs.
- **Modern PHP:** target the installed version (8.3.14). Use `match`, arrow functions, named arguments,
  nullsafe `?->`, `??=`, `str_contains`/`str_starts_with`, `array_is_list()`, `never`. No deprecated
  functions. Class-based features (enums, `readonly`, attributes) wait for the OOP follow-up.
- **Security:** validate every input at the boundary; allowlist, don't blocklist; escape on output,
  never on input; fail closed; never leak internals to the browser. Full rules in
  [`docs/code-standards.md`](../docs/code-standards.md) §2.
- **Escaping:** every dynamic value printed to HTML goes through `e()`. No exceptions.
- **SQL:** prepared statements for all values. Dynamic identifiers — only from a hardcoded whitelist array.
- **Validation:** server-side always; client-side attributes are a convenience, never a gate.
- **Money:** store cents; convert at the edges (form input → cents, display → `format_money`).
- **Dates:** store `Y-m-d`; compare months with `strftime('%Y-%m', occurred_on)`; set one timezone in `config.php`.
- **Destructive actions:** POST only, CSRF-verified, redirect after.
- **File layout:** `src/` for logic, `public/` for views, `data/` for the DB file.
- **`calculations.php` stays pure:** no `$pdo`, no `$_POST`, no `echo`. This keeps it testable in Phase 10.
- **Ignore the `php-pro` skill's architecture advice:** no DTOs, service classes, DI, namespaces or
  enums. Take only its language-level rules. See [`docs/code-standards.md`](../docs/code-standards.md) §4.

---

## 6. Final Verification (whole app)

1. `php -S localhost:8000 -t public` starts clean with no warnings/notices.
2. Fresh clone → run `schema.sql` + `seed.sql` → dashboard shows seeded totals.
3. Add income and expense → appears in list and dashboard; refresh does not duplicate.
4. Filter by month / category / type / combinations → correct rows and totals.
5. Edit a transaction → reflects everywhere. Delete it → gone everywhere.
6. Add a duplicate category → friendly error. Delete a referenced category → refused.
7. `<script>alert(1)</script>` in a note renders as literal text everywhere it's shown.
8. `1' OR '1'='1` as an amount / filter → rejected or inert, never a SQL error.
9. POST any handler with the CSRF field removed → rejected.
10. `vendor/bin/phpunit` → green.
11. `data/tracker.sqlite` is not reachable at `http://localhost:8000/../data/tracker.sqlite`.

---

## 7. How To Use This Plan

1. Open [`/PROGRESS.md`](../PROGRESS.md) and find the current phase.
2. Open that phase's file here.
3. Work the sub-phases top to bottom, ticking checkboxes in the phase file.
4. When the phase's **Verify** step passes, update `/PROGRESS.md` to `done`.
5. Set the next phase to `working` in `/PROGRESS.md` before touching any code for it.

Status values: `not started` · `working` · `done` · `blocked`.
Exactly one phase may be `working` at a time.
