# Code Standards

These apply to **every** file written in this project, in every phase. They expand on the
non-negotiables in [`AGENT.md`](../AGENT.md) §6.

Three rules drive everything below:

1. **Document what and why** — a PHPDoc block on every function.
2. **Secure by default** — never trust input, never concatenate SQL, always escape output.
3. **Modern PHP** — target the installed PHP version and use current language features.

---

## 1. Documentation — PHPDoc

### Every function gets a DocBlock

No exceptions — including one-line helpers, test helpers, and functions you think are obvious.

```php
/**
 * Format a whole number of cents as a human-readable money string.
 *
 * Uses integer arithmetic to split the cents rather than dividing into a float.
 * Binary floating point cannot represent most decimal fractions exactly, so
 * (int)($amount * 100) yields 1998 for "19.99" instead of 1999.
 *
 * @param int $cents Amount in cents. May be negative.
 * @return string e.g. "1,234.56", "0.05", "-2.50".
 */
function format_money(int $cents): string
{
```

### The summary line

One sentence, imperative mood: *"Format…"*, *"Validate…"*, *"Return…"*.
Not *"This function formats…"*. The reader already knows it's a function.

### What goes in `@param` and `@return`

The native type is already in the signature — **don't repeat it**. Use the DocBlock to say what the
type *cannot*:

- **units** — is `$amount` cents or dollars?
- **range and validity** — `1–50 characters`, `may be negative`, `never zero`
- **array shape** — `array{type: string, amount_cents: int, occurred_on: string}`
- **what `null` means** on a nullable return
- **what escapes** via `@throws`

```php
/**
 * Look up one transaction, joined to its category name.
 *
 * @param int $id Transaction id.
 * @return array{id: int, type: string, amount_cents: int, occurred_on: string, note: ?string}|null
 *         Null when no transaction has that id — callers are expected to render a
 *         404, not to treat it as an error.
 */
function find_transaction(int $id): ?array
{
```

### Explain *why*, not *what*

The code already says what it does. A comment earns its place by recording a **decision**, a
**constraint**, or a **trap**:

- ✅ `// Integer maths, not (int)($raw * 100) — that gives 1998 for "19.99".`
- ✅ `// SQLite has no functional indexes, so this WHERE can't use idx_tx_occurred_on.`
- ✅ `// hash_equals, not ===, so the comparison is timing-safe.`
- ❌ `// loop over the transactions`
- ❌ `// increment the counter`
- ❌ `// the amount field`

### Inside a function

Short `//` comments for the genuinely surprising step. Never a comment per line. If a block needs
five comments, extract it into a named function and give *that* a DocBlock instead.

### File headers

`src/` files open with a short block saying what the file is for and what belongs in it:

```php
<?php

declare(strict_types=1);

/**
 * Pure calculation helpers — totals, groupings, and money formatting.
 *
 * Nothing here may touch PDO, $_POST, $_GET, $_SESSION, or echo. Keeping this
 * file free of I/O is what makes it unit-testable without a database, which
 * Phase 10 depends on.
 */
```

Not needed in `public/` pages — the page shape there is self-describing.

### Never reference the task, the phase, or the user in a comment

No `// added for Phase 04`, no `// TODO: ask the user`. Comments describe the code, permanently.

---

## 2. Security — Secure by Default

The default assumption is that **every** value from outside the process is hostile.

### Input

- **Validate at the boundary, server-side, always.** Client-side `required` / `maxlength` /
  `type="number"` are UX conveniences. Anyone can POST with curl.
- **Treat these as untrusted:** `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, `$_SERVER`, HTTP headers,
  and anything read from a file or the database that originally came from a user.
- **Allowlist, don't blocklist.** Decide what is valid and reject everything else. `in_array($v,
  ['income','expense'], true)` — not a regex that tries to catch bad characters.
- **Validate, then normalise, then store.** Reject rather than silently "fix" malformed input.
- **Cast after validating:** `(int) $id` once you know it's a positive integer.
- **Bound every input** — length, range, type. `note` is capped at 255; the search box at 100.

### SQL

- **Every value is a bound parameter.** `:placeholder` — no exceptions, no convenience escapes.
- **Identifiers (column/table names) can never be bound.** They may only ever come from a hardcoded
  whitelist array. See `buildTransactionQuery()` in Phase 06 for the one place this is subtle.
- **Never build SQL with concatenation or interpolation** of a variable.
- **Set `ATTR_EMULATE_PREPARES => false`** so placeholders are real server-side prepares.
- **`PRAGMA foreign_keys = ON` per connection** — it is not a database-level setting.

### Output

- **Escape on output, never on input.** Escaping on input double-encodes and corrupts stored data.
- **Every dynamic value printed into HTML goes through `e()`** — attributes and `<title>` and
  `<option>` included.
- **Escape for the correct context.** They are not interchangeable:
  - HTML → `e()` (`htmlspecialchars` with `ENT_QUOTES | ENT_SUBSTITUTE`)
  - URL query → `urlencode()` / `http_build_query()`
  - CSV → quote-prefix cells starting with `=`, `+`, `-`, `@` (CSV injection)
  - Shell → `escapeshellarg()` (and prefer not to shell out at all)
- **Never put untrusted data in a `href`/`src` without validating the scheme.** `htmlspecialchars`
  does not stop `javascript:`.

### State changes

- **Anything that writes or deletes is POST-only.** A GET must be refused — a link, a prefetch, or an
  `<img src>` can fire a GET.
- **Every POST is CSRF-verified** with `hash_equals()`, never `===`.
- **Verify CSRF first, then validate, then write.** Never write before verifying.
- **`confirm()` on a button is UX, not a security control.** Say so in a comment where you use it.

### Secrets and randomness

- **`random_bytes()` / `random_int()` for anything security-relevant.** Never `rand()` or `mt_rand()`.
- **No secrets, keys, or passwords in code or in git.** Config comes from the environment; `.env` is
  gitignored.
- **`password_hash()` with the default algorithm** if authentication is ever added. Never md5/sha1
  for passwords.

### Failure behaviour

- **Fail closed.** An error denies access; it never grants it.
- **Never leak internals to the browser** — no raw `PDOException` messages, no file paths, no stack
  traces. Log them; show a generic message.
- **`display_errors` off** for anything reachable by anyone but you.

### Forbidden constructs

`eval()`, `extract()`, variable variables (`$$name`), `unserialize()` on untrusted input,
`preg_replace()` with `/e`, `include`/`require` on a user-supplied path, and `global`.

### Response headers

`X-Content-Type-Options: nosniff`, a restrictive `Content-Security-Policy`, and
`Referrer-Policy: same-origin`. Details in Phase 08.

---

## 3. Modern PHP

**Target: PHP 8.3.14** — the version installed on this machine. Code must run on it. Do not use
8.4+ syntax (property hooks, asymmetric visibility, `new` without parentheses) unless the interpreter
is deliberately upgraded first.

### Always

| Feature | Example |
|---|---|
| `declare(strict_types=1);` | first statement in **every** `.php` file |
| Type declarations | on every parameter **and** every return |
| Nullable types | `?string` — explicit, never an implicit null |
| Union types | `int\|string` where genuinely needed |
| `match` | for value→value mapping; `switch` is for real branching |
| Arrow functions | `fn($t) => $t['amount_cents']` for one-expression closures |
| Named arguments | `format_money(cents: 1234)` when a call has several optionals |
| Nullsafe / coalescing | `$row?->note`, `$x ?? 'default'`, `$x ??= []` |
| String helpers | `str_contains`, `str_starts_with`, `str_ends_with` over `strpos` |
| `array_is_list()` | instead of `array_keys($a) === range(0, count($a) - 1)` |
| `never` return type | on `redirect()` and anything that always exits |
| First-class callables | `array_map(strlen(...), $rows)` over `'strlen'` |
| Array destructuring | `[$first, $second] = $pair` |
| Numeric separators | `1_000_000` for readability |
| `json_validate()` | (8.3) instead of decoding just to check |
| `Randomizer` | (8.2) when you need more than one random value |

### Deliberately not used here

These are modern PHP, but **class-based** — and the brief defers OOP on purpose. Don't reach for them
until the follow-up OOP work:

| Feature | Why not yet |
|---|---|
| `enum` | enums are classes. Use validated string constants instead. |
| `readonly` properties / classes | require classes |
| Attributes | applied to classes, methods, properties |
| Constructor promotion | requires constructors |
| Intersection / DNF types | only meaningful for object types |
| Namespaces / PSR-4 | there are no classes to autoload |

**The rule when "latest PHP" and "no OOP" collide: language features now, class-based features later.**

### Never

- Deprecated or removed functions — `each()`, `create_function()`, `mysql_*`, `utf8_encode()`,
  `FILTER_SANITIZE_STRING`, passing `null` to non-nullable internal params.
- `global` — pass values in, return values out.
- Suppressing errors with `@`.
- `var_dump()` / `print_r()` left in committed code (fine in a scratch script, never in `src/` or
  `public/`).
- Comparing floats for equality — that is why money is stored as integer cents.

### Style

Follow **PSR-12** for formatting: 4 spaces, one class-free file per concern, blank line after
`declare`, opening brace on its own line for functions. Naming per [`AGENT.md`](../AGENT.md) §7.

---

## 4. The `php-pro` Skill — What To Take, What To Ignore

`.commandcode/skills/php-pro/` is installed in this repo. It targets **PHP 8.3+ with Laravel and
Symfony**, and its architecture advice contradicts this project's core constraint.

**Do not follow it wholesale.** Take the language-level rules; ignore the architecture.

### Take

- `declare(strict_types=1)`
- Type hints on all parameters, returns, and properties
- PHPDoc blocks
- PSR-12 formatting
- Validate all user input
- Never write injectable SQL
- Never hardcode configuration — read it
- No `var_dump()` in production code

### Ignore

- `final readonly class`, DTOs, value objects, service classes
- Dependency injection, repositories, "DI over global state"
- Namespaces and PSR-4 autoloading — there are no classes
- Enums
- PHPStan level 9 and a mandatory 80% coverage gate — Phase 10 adds PHPUnit for pure functions only
- Laravel / Symfony / Eloquent / Doctrine / Swoole / ReactPHP
- "Every implementation delivers a typed entity, a service class, and a test"

If you find yourself writing `class` in this project, stop and re-read
[`AGENT.md`](../AGENT.md) §6 rule 1.

---

## 5. Checklist Before Finishing Any File

- [ ] `declare(strict_types=1);` at the top
- [ ] Every function has a DocBlock with a summary, `@param`, `@return`, and `@throws` where relevant
- [ ] Every parameter and return type is declared
- [ ] Comments explain *why*, not *what*
- [ ] No input is trusted; every value reaching SQL is a bound parameter
- [ ] Every dynamic value reaching HTML goes through `e()`
- [ ] No deprecated functions, no `global`, no `@`, no `var_dump()`
- [ ] `php -l <file>` reports no syntax errors
- [ ] Runs clean under `error_reporting(E_ALL)`
