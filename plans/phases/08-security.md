# Phase 08 — Security Hardening

> **Stage D — Quality** · Depends on: Phase 07 · Unblocks: Phase 09
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

A deliberate, end-to-end security pass over everything built so far — not new features, just making what exists trustworthy.

## Concepts You'll Practice

input validation · output escaping · SQL injection prevention · CSRF awareness · sessions · `hash_equals()` · security headers

## Files Produced

```
src/functions.php    # + csrf_token(), csrf_field(), csrf_verify()
                     # + security headers helper
                     # + e() audit fixes
all POST handlers    # CSRF check at the top of each
all views            # escaping audit
```

## Sub-phases

### 8.1 — Output escaping audit

- [ ] `grep -rn "echo" public/ src/partials/` and read **every** hit
- [ ] Any `echo` of data originating from the DB or user input must be wrapped in `e()`
- [ ] Check attribute contexts too: `value="<?= e($v) ?>"`, `href="...?id=<?= (int)$id ?>"`, `class="<?= e($cls) ?>"`
- [ ] Check that `e()` is used with `ENT_QUOTES` — otherwise single-quoted attributes break out
- [ ] Add `e()` to anything printed inside `<title>`, `<option>`, `data-*` attributes
- [ ] Confirm the CSV/`href` case: URL-encoding and HTML-escaping are **different** jobs (`urlencode()` vs `e()`)
- [ ] Test: put `<script>alert(1)</script>`, `"><img src=x onerror=alert(1)>`, and `O'Brien` into a note and a category name → all render inert, everywhere they appear

### 8.2 — SQL injection audit

- [ ] `grep -rn "SELECT\|INSERT\|UPDATE\|DELETE" src/ public/` and inspect each statement
- [ ] Every value must be a `:placeholder`. Every interpolated identifier must come from a hardcoded whitelist array.
- [ ] Pay special attention to the Phase 06 query builder — that's the one place a variable touches SQL structure
- [ ] Confirm `ATTR_EMULATE_PREPARES => false` is still set
- [ ] Test with `1' OR '1'='1`, `'; DROP TABLE transactions; --`, and a `%` wildcard in the search box
- [ ] Confirm an invalid `category_id` of `1 OR 1=1` fails validation rather than reaching SQL

### 8.3 — Sessions and CSRF tokens

- [ ] Start a session in one place — call `session_start()` before any output. Put it in a shared bootstrap or at the top of every page; prefer the bootstrap if you already have one, otherwise do it explicitly and note the duplication.
- [ ] `function csrf_token(): string` — generate once per session with `bin2hex(random_bytes(32))`, store in `$_SESSION['csrf_token']`
- [ ] `function csrf_field(): string` — returns `<input type="hidden" name="csrf_token" value="...">`
- [ ] `function csrf_verify(?string $token): bool` — `hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '')`. **`hash_equals`, not `===`** (timing-safe).
- [ ] Handle the missing-session-token case: if `$_SESSION['csrf_token']` doesn't exist, regenerate rather than comparing against `''`
- [ ] Reject with `403` and a clear message on failure
- [ ] Optional hardening: regenerate the token on a schedule, or rotate per form

### 8.4 — Apply CSRF to every state change

- [ ] `transaction-save.php` (both create and update paths)
- [ ] `transaction-delete.php`
- [ ] `category-save.php`
- [ ] `category-delete.php` if you built it
- [ ] Every form that posts includes `<?= csrf_field() ?>`
- [ ] Check the order of operations in each handler: CSRF first, then validation, then write. Never write before verifying.

### 8.5 — Validation audit

- [ ] Confirm all handler-side validation runs regardless of what the client sent
- [ ] Confirm `$_POST` is never trusted for `type`, `category_id`, `amount`, or `occurred_on`
- [ ] Confirm `$_GET['id']` is cast/validated in every handler
- [ ] Confirm `note` length is enforced server-side
- [ ] Confirm you never `extract($_POST)` or use `$$var`

### 8.6 — Response headers and error handling

- [ ] Send `X-Content-Type-Options: nosniff` and a basic `Content-Security-Policy` (`default-src 'self'` — you may need `'unsafe-inline'` for the inline percentage bar; prefer moving that to a class or CSS variable)
- [ ] `Referrer-Policy: same-origin`
- [ ] Turn **off** `display_errors` for anything resembling production; log instead
- [ ] Confirm a failing page answers **500**, not 200. Found during Phase 02: a page that throws returns
      500 on its own, but returns 200 once `ini_set('display_errors', '1')` has run — so every page here
      currently reports success while showing an error. Verify the status code, not just the body.
- [ ] Confirm no page leaks a raw `PDOException` message or file path to the browser
- [ ] Confirm `data/tracker.sqlite` is outside the docroot

### 8.7 — Adversarial test pass

- [ ] Write down each attack you tried and its result in `docs/security-notes.md`
- [ ] Include: XSS in note/category, SQLi in filters and amount, CSRF on delete and save, IDOR (edit someone else's id — only relevant if you add auth, note it), open redirect via `return_to`

## Done When

XSS payloads render as literal text, SQLi payloads are inert, and any POST without a valid token is rejected with 403.

## Verify

| Attack | Expected |
|---|---|
| `<script>alert(1)</script>` in note | rendered as text everywhere |
| `"><img src=x onerror=alert(1)>` in category name | rendered as text |
| `1' OR '1'='1` in search | no results, no SQL error |
| `'; DROP TABLE transactions; --` in amount | validation error |
| POST save with token field removed | 403, no row written |
| POST delete with a stale token | 403, row still there |
| `?sort=` any string | ignored, default sort |
| response headers | `nosniff`, CSP present |
| forced `PDOException` | generic message, no stack trace |

## Gotchas

- `session_start()` must come before **any** output — including a stray newline before `<?php`.
- Comparing tokens with `===` is timing-attackable; `hash_equals()` is the point of the exercise.
- `htmlspecialchars()` on a URL does not make it safe against `javascript:` — validate the scheme separately if you ever accept a URL.
- A CSP with `'unsafe-inline'` is much weaker; if you can, move inline styles to classes.
- Escaping at *output* is the correct layer. Escaping at *input* is wrong — you'd double-encode and corrupt the data.

## Reference

- `hash_equals`: <https://www.php.net/manual/en/function.hash-equals.php>
- `random_bytes`: <https://www.php.net/manual/en/function.random-bytes.php>
- OWASP Cheat Sheets: <https://cheatsheetseries.owasp.org/>
