<?php

declare(strict_types=1);

/**
 * Application configuration.
 *
 * Every value here is read through `config()` in `functions.php` rather than
 * being required directly by a page, so there is exactly one place to change
 * and exactly one place to look when something is wrong.
 *
 * Both the symbol and the ISO code are kept. A symbol alone is ambiguous —
 * `$` is USD, CAD, AUD and several others — so the code is what actually
 * identifies the currency, and it is what an export should declare.
 */

return [
    'app_name' => 'Personal Expense Tracker',

    // An IANA identifier, not the Windows name "Bangladesh Standard Time".
    // Transaction dates are stored as bare 'YYYY-MM-DD' text, so the timezone
    // never shifts a stored date. It only decides what "today" is - which in
    // turn decides which month the app opens on. With UTC on this UTC+6
    // machine, the first six hours of every month would report the month that
    // had just ended.
    'timezone' => 'Asia/Dhaka',

    // Absolute, and deliberately outside public/ — the docroot. A database file
    // sitting in the web root can be downloaded by anyone who guesses its name,
    // and this one holds every transaction. __DIR__ is this file's own directory,
    // so the path still resolves when a script runs from somewhere else.
    'db_path' => __DIR__ . '/../data/tracker.sqlite',

    'currency_code'   => 'USD',
    'currency_symbol' => '$',
];
