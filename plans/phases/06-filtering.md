# Phase 06 — Filtering with `$_GET`

> **Stage C — Features** · Depends on: Phase 05 · Unblocks: Phase 07
>
> **Status:** tracked in [`/PROGRESS.md`](../../PROGRESS.md) — never edit status in this file.

## Goal

The transaction list can be narrowed by month, category, and type — and the totals shown always describe the **filtered** set, not the whole table.

## Concepts You'll Practice

`$_GET` · query strings · `http_build_query()` · dynamic-but-safe `WHERE` clauses · identifier whitelisting · strict input validation

## Files Produced

```
public/transactions.php     # extended with filters
src/filters.php             # buildTransactionFilters() + buildTransactionQuery()
```

## Sub-phases

### 6.1 — Parse and validate `$_GET`

- [ ] `function buildFilters(array $query): array` in `src/filters.php`
- [ ] **`month`** — must match `/^\d{4}-(0[1-9]|1[0-2])$/` or be discarded
- [ ] **`category_id`** — must be a positive integer (`filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])`)
- [ ] **`type`** — must be in `['income', 'expense']`
- [ ] **`q`** (optional search on note) — trim, cap length at 100, escape `%` and `_` if you use `LIKE`
- [ ] Return only the keys that validated; an invalid value is dropped, **never** passed through
- [ ] Note the philosophy: `$_GET` is untrusted input exactly like `$_POST`. Different verb, same suspicion.

### 6.2 — Dynamic `WHERE` with placeholders

- [ ] `function buildTransactionQuery(array $filters): array` returning `['sql' => string, 'params' => array]`
- [ ] Start with `$conditions = []` and `$params = []`
- [ ] **Push a placeholder per condition** — `$conditions[] = 'strftime(\'%Y-%m\', t.occurred_on) = :month'` and `$params[':month'] = $filters['month']`
- [ ] `implode(' AND ', $conditions)` (or use a dummy `1=1` when empty)
- [ ] **The rule:** values are *always* placeholders. Only identifiers may be interpolated, and only from a whitelist. Never `"... WHERE type = '$type'"`.
- [ ] Cast `category_id` to `int` as a belt-and-braces second layer
- [ ] Bind with `execute($params)` and confirm the named keys include the leading colon

### 6.3 — Whitelisted sorting

- [ ] Accept `sort` and `dir` from `$_GET`
- [ ] `$allowedSort = ['occurred_on', 'amount_cents', 'category']` — map any input to one of these or fall back to the default
- [ ] `$dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC'`
- [ ] Interpolate **only** the mapped whitelist value, never the raw input
- [ ] Write a comment explaining why a bound parameter cannot be used for a column name
- [ ] Make each column header a link that toggles direction

### 6.4 — Filter form UI

- [ ] A `<form method="get" action="transactions.php">` above the table
- [ ] Month `<input type="month">`, category `<select>` (with an "All" option), type `<select>` (with "All")
- [ ] Every control is pre-selected from `$filters` on re-render
- [ ] A **Clear** link back to bare `transactions.php`
- [ ] Sort links preserve the other filters — use `http_build_query(array_merge($filters, ['sort' => ..., 'dir' => ...]))`
- [ ] Note: a GET form with empty fields still submits `?month=&type=` — decide whether to treat empty-string as "unset"

### 6.5 — Result count and filtered totals

- [ ] Show "Showing N transactions" where N is the actual row count
- [ ] Show income / expense / net **for the filtered set** — reuse `monthlyTotals()`? No: that function filters by month internally. Either pass a pre-filtered array to a general `sumByType()`, or add a `totalsFor()` helper. Decide and be consistent.
- [ ] Verify: filter to one category and confirm the total equals that category's dashboard breakdown figure

### 6.6 — Combination testing

- [ ] Build a truth table and check each combination against a raw SQL query run in `sqlite3`
- [ ] Month + type, month + category, category + type, all three, none
- [ ] Include a category with zero transactions in the filtered range → empty state with a message

## Done When

Every filter combination returns correct rows and correct totals, and the URL is shareable (copy the URL, open it in a new tab, same view).

## Verify

| Test | Expected |
|---|---|
| `?month=2025-01` | only January rows, totals match |
| `?type=income` | only income, expense total `0.00` |
| `?category_id=3` | only that category |
| `?type=bogus` | filter ignored, all rows |
| `?month=2025-13` | filter ignored, no error |
| `?sort=id;DROP TABLE` | falls back to default sort |
| `?q=1' OR '1'='1` | no results, no SQL error |
| no query string | identical to pre-Phase-06 behaviour |

## Gotchas

- `execute()` with named params: the array keys **must** include the colon (`':month'`) unless you bind explicitly.
- Reusing the same placeholder twice works only if `ATTR_EMULATE_PREPARES` is off. Prefer distinct names.
- `http_build_query()` correctly encodes `&` and spaces — don't hand-concatenate query strings.
- `strftime` in a `WHERE` kills index usage (same note as Phase 05). A `BETWEEN '2025-01-01' AND '2025-01-31'` range is index-friendly; consider it and document the trade-off.
- An empty `<select>` value arrives as `''`, which `FILTER_VALIDATE_INT` rejects — that's correct, but only if you treat `''` as "unset" rather than "invalid".

## Reference

- `$_GET`: <https://www.php.net/manual/en/reserved.variables.get.php>
- `FILTER_VALIDATE_INT`: <https://www.php.net/manual/en/filter.filters.validate.php>
- `http_build_query`: <https://www.php.net/manual/en/function.http-build-query.php>
