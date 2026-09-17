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

- [ ] Create `public/`, `src/`, `src/partials/`, `data/`, `tests/`, `docs/`
- [ ] Create `.gitignore` containing:

  ```
  /data/*.sqlite
  /vendor/
  .env
  .DS_Store
  Thumbs.db
  ```

- [ ] Confirm `data/` exists but stays empty in version control (if you need git to keep it, add `data/.gitkeep`)

### 0.3 — First page + dev server

- [ ] Create `public/index.php` with a minimal HTML5 shell and a single `echo`
- [ ] From the project root run: `php -S localhost:8000 -t public`
- [ ] Open `http://localhost:8000` in a browser
- [ ] Watch the terminal — one log line should appear per request
- [ ] Experiment: `var_dump()` an array, then a `null`, then a `bool` — see how each prints

### 0.4 — Git

- [ ] `git init`
- [ ] `git add .` then `git status` — confirm `data/*.sqlite` is **not** staged (it doesn't exist yet, but the rule should be in place)
- [ ] First commit

## Done When

`http://localhost:8000` renders your page, and the server log shows the request.

## Verify

Edit the text in `public/index.php`, refresh the browser — the change appears **without restarting the server**.

## Gotchas

- `php -S` serves the directory you give with `-t`. If you omit `-t`, it serves the current directory and your `src/` files become web-reachable.
- The built-in server is single-threaded and for development only. Never treat it as production.
- If `php` isn't found on Windows, it isn't on `PATH` — either add it or call the full path (`C:\php\php.exe`).
- A port already in use gives `Failed to listen on localhost:8000`. Use a different port.

## Reference

- PHP tags: <https://www.php.net/manual/en/language.basic-syntax.phpmode.php>
- Built-in server: <https://www.php.net/manual/en/features.commandline.webserver.php>
