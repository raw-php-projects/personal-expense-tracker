# Personal Expense Tracker

A single-user tracker for recording income and expenses and seeing monthly spending.

Built in **procedural PHP** with **PDO + SQLite** — no framework, no OOP, no patterns. That constraint is
deliberate: see [`ProjectBrief.md`](./ProjectBrief.md).

## Status

This project is built in phases. Live status lives in [`PROGRESS.md`](./PROGRESS.md).
The plan itself is in [`plans/`](./plans), with one file per phase in [`plans/phases/`](./plans/phases).

## Requirements

- PHP 8.1 or newer, with the `pdo_sqlite` and `sqlite3` extensions **enabled**
- No database server required — SQLite is a single file

Verified on PHP 8.3.14 / SQLite 3.40.0.

## Running

```bash
php -S localhost:8000 -t public
```

Then open <http://localhost:8000>.

The `-t public` matters. The document root is `public/`, which keeps `src/` and the database file from
being reachable over HTTP.

## Project Structure

```
public/     web root — one PHP file per page (views)
src/        application logic, not web-accessible
data/       the SQLite database file (gitignored)
tests/      PHPUnit tests
docs/       manual QA checklist and notes
plans/      the phased build plan
```

## Development Notes

- Every dynamic value printed to HTML passes through an escaping helper.
- Every SQL value is a bound parameter — never string-concatenated.
- Money is stored as an integer number of cents, never a float.
- Dates are stored as `YYYY-MM-DD` text.
