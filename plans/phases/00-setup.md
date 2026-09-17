# Phase 00 — Environment & Skeleton

> **Stage A — Foundations** · Depends on: *nothing* · Unblocks: Phase 01
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

Get PHP executing on your machine and serve a real page from a real project folder.

## Concepts You'll Practice

- How PHP executes: one process per request, no shared memory between requests
- The built-in dev server (`php -S`) and what a *docroot* is
- `echo`, PHP tags, mixing PHP and HTML
- Checking which extensions are compiled in

## Files Produced

```
public/index.php
.gitignore
README.md
data/            (empty, gitignored contents)
src/partials/    (empty, created now)
tests/
docs/
```

## Sub-phases

### 0.1 — Verify PHP & SQLite support

- [x] Run `php -v` and note the version (target PHP 8.1 or newer)
  → **PHP 8.3.14** (NTS, x64) at `C:\php\php.exe`
- [x] Run `php -m` and confirm **both** `pdo_sqlite` and `sqlite3` are listed
  → neither was loaded initially; both DLLs were present, both `extension=` lines were commented out
- [x] If `pdo_sqlite` is missing, open `php.ini`, uncomment `extension=pdo_sqlite`, restart your shell
  → uncommented `;extension=pdo_sqlite` (line 960) **and** `;extension=sqlite3` (line 971) in `C:\php\php.ini`
  → backup written to `C:\php\php.ini.bak` first
- [x] Run `php --ini` to see which `php.ini` is actually loaded
  → `C:\php\php.ini`
- [x] Confirm `php -r "echo PHP_VERSION;"` works from any directory
  → works; `PHP_VERSION = 8.3.14`

### 0.2 — Project skeleton

- [x] Create `public/`, `src/`, `src/partials/`, `data/`, `tests/`, `docs/`
  → all created; `.gitkeep` added to the empty ones so the structure survives the first commit
- [x] Create `.gitignore` containing:

  ```
  /data/*.sqlite
  /vendor/
  .env
  .DS_Store
  Thumbs.db
  ```

  → plus one addition: `.commandcode/` — Command Code tooling state (agent skills and
    `settings.local.json`), not project source. Delete that line if you'd rather version it.
- [x] Confirm `data/` exists but stays empty in version control (if you need git to keep it, add `data/.gitkeep`)
  → `data/.gitkeep` added. Verified with a **real probe file**, not just the pattern:
    `git check-ignore -v data/tracker.sqlite` → `.gitignore:1:/data/*.sqlite`.
    The probe was deleted before committing.

### 0.3 — First page + dev server

- [x] Create `public/index.php` with a minimal HTML5 shell and a single `echo`
  → plus `declare(strict_types=1)`, `error_reporting(E_ALL)`, `display_errors=1`, timezone `UTC`
- [x] From the project root run: `php -S localhost:8000 -t public`
  → starts clean, no warnings
- [x] Open `http://localhost:8000` in a browser
  → `200 OK`, `Content-Type: text/html; charset=UTF-8`
- [x] Watch the terminal — one log line should appear per request
  → confirmed. Each request logs `Accepted` / `[status]: METHOD path` / `Closing`:

    ```
    [Thu Sep 17 22:41:33 2026] [::1]:50145 [200]: GET /
    [Thu Sep 17 22:41:33 2026] [::1]:50147 [404]: GET /nope.php - No such file or directory
    ```

- [x] Experiment: `var_dump()` an array, then a `null`, then a `bool` — see how each prints
  → `array(4) { ... }`, `NULL`, `bool(true)`, `int(0)`, `string(0) ""`.
    The one to burn in: **the string `'0'` is falsy** — `empty('0')` is `true`, while `'0.0'` is truthy.
    This bites in Phase 03, where every `$_POST` value arrives as a string.

### 0.4 — Git

- [x] `git init`
  → `git init -b main` (git 2.38.1)
- [x] `git add .` then `git status` — confirm `data/*.sqlite` is **not** staged (it doesn't exist yet, but the rule should be in place)
  → verified with a real probe file: absent from `git diff --cached`, and `git check-ignore -v` named the rule
- [x] First commit
  → `1b3f645` — 24 files, 1973 insertions. Working tree clean.

## Done When

`http://localhost:8000` renders your page, and the server log shows the request.

## Verify

Edit the text in `public/index.php`, refresh the browser — the change appears **without restarting the server**.

## Gotchas

- `php -S` serves the directory you give with `-t`. If you omit `-t`, it serves the current directory and your `src/` files become web-reachable.
- The built-in server is single-threaded and for development only. Never treat it as production.
- If `php` isn't found on Windows, it isn't on `PATH` — either add it or call the full path (`C:\php\php.exe`).
- A port already in use gives `Failed to listen on localhost:8000`. Use a different port.

## Environment Notes

Recorded 2026-09-17 when this phase was executed.

| Item | Value |
|---|---|
| PHP | 8.3.14 (NTS, x64) — `C:\php\php.exe` |
| php.ini | `C:\php\php.ini` |
| php.ini backup | `C:\php\php.ini.bak` |
| SQLite library | 3.40.0 |
| `pdo_sqlite` | **enabled** — was commented out at line 960 |
| `sqlite3` | **enabled** — was commented out at line 971 |
| PDO drivers | mysql, sqlite |
| Composer | 2.7.1 — available, so Phase 10 can use real PHPUnit |
| git | 2.38.1.windows.1 |

**Verified here, relied on later:**

- A PDO round-trip against SQLite works, including storing quotes and angle brackets verbatim.
- `PRAGMA foreign_keys` defaults to `0` and reads `1` only after being set — it is **per connection**.
  Phase 02 depends on this.
- A `UNIQUE` violation raises `SQLSTATE 23000`. Phase 04 depends on this.

**⚠️ Known constraint for Phase 02:** the `sqlite3` **command-line tool is not on PATH** — only the PHP
`sqlite3` *extension* is available. So the command written in
[02-pdo-read.md](./02-pdo-read.md#23--create-and-seed-the-database):

```
sqlite3 data/tracker.sqlite < schema.sql
```

will not run as written. Two options:

1. **Run the SQL through PDO** with a small bootstrap script. Preferred — it exercises the same code
   path the app uses, and it is the only option that works identically on any machine.
2. Install the SQLite CLI and put it on `PATH`.

Everything else in the plan is unaffected.

## Reference

- PHP tags: <https://www.php.net/manual/en/language.basic-syntax.phpmode.php>
- Built-in server: <https://www.php.net/manual/en/features.commandline.webserver.php>
