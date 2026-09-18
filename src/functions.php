<?php

declare(strict_types=1);

// money() composes the configured symbol with format_money(), so this file
// needs the pure formatter loaded.
require_once __DIR__ . '/calculations.php';

/**
 * View helpers — output escaping here, and later redirects, flash messages, and
 * CSRF tokens.
 *
 * This is the counterpart to `calculations.php`. Where that file must stay pure
 * so it can be tested with no request at all, this one is allowed to touch the
 * environment: later phases add helpers around `header()`, `$_SESSION`, and
 * `session_start()`. The rule the two files share is that neither holds domain
 * logic — if it knows what a transaction *is*, it belongs somewhere else.
 */

/**
 * Escape a value for safe insertion into HTML.
 *
 * The one-letter name is deliberate. This appears on nearly every line that
 * prints something, and a longer name would be noise.
 *
 * `?string` and the `?? ''` are one decision, not two: a null value means "no
 * text", and printing nothing is the correct output for it. That lets a caller
 * write `e($row['note'])` with no guard, including when the column is NULL.
 *
 * `ENT_QUOTES` escapes both `"` and `'`. With the default `ENT_COMPAT`, single
 * quotes pass through unescaped and a value placed inside a single-quoted
 * attribute can close it and inject attributes of its own.
 *
 * The charset is passed explicitly rather than left to the `default_charset`
 * ini setting, so escaping cannot quietly change behaviour with configuration.
 *
 * `ENT_SUBSTITUTE` matters more than it looks. Without it, a value that is not
 * valid UTF-8 makes `htmlspecialchars` return an empty string, so the whole
 * field renders blank with no warning at all. SQLite stores and returns such
 * bytes unmodified, so one bad paste or mangled import reaches this function
 * intact and then silently disappears. With the flag, the damaged stretch shows
 * as U+FFFD instead. PHP 8.1+ enables it by default — naming the flags
 * explicitly is what would otherwise drop it.
 *
 * For **HTML text and attribute contexts only.** A URL, a CSS block, a
 * JavaScript string, and a SQL fragment each need their own encoding; using
 * this one there buys false confidence rather than safety.
 *
 * @param string|null $value Text to escape. `null` is treated as empty.
 * @return string Escaped for HTML. Never null. Input that is not valid UTF-8
 *                renders as U+FFFD rather than being dropped.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Read a value from the application configuration.
 *
 * Loaded lazily on first call and held for the rest of the request, so a page
 * that never reads config never pays for loading it.
 *
 * @param string $key Configuration key, e.g. 'currency_symbol'.
 * @return mixed The configured value, or null when the key is not set.
 */
function config(string $key): mixed
{
    static $config = null;

    if ($config === null) {
        // require, not require_once. On a repeat include require_once returns
        // true rather than the file's value, which would poison this cache if a
        // page had already pulled config.php in itself.
        $config = require __DIR__ . '/config.php';
    }

    return $config[$key] ?? null;
}

/**
 * Format cents as money, using the configured currency symbol.
 *
 * The sign goes before the symbol ("-$2.50"), not between the symbol and the
 * digits ("$-2.50"), which is how negative money is normally written.
 *
 * A thin wrapper: the arithmetic and the thousands grouping stay in
 * format_money(). This adds only the symbol, so changing `currency_symbol` in
 * `config.php` changes it everywhere this is called.
 *
 * @param int $cents Amount in cents. May be negative or zero.
 * @return string e.g. "$1,234.56", "$0.05", "-$2.50". Plain text, not escaped —
 *                callers still pass the result through `e()` where they print it.
 */
function money(int $cents): string
{
    return format_money($cents, (string) config('currency_symbol'));
}
