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

    'currency_code'   => 'USD',
    'currency_symbol' => '$',
];
