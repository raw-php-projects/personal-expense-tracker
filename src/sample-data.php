<?php

declare(strict_types=1);

/**
 * Hardcoded sample transactions for the Phase 01 playground.
 *
 * TEMPORARY. Phase 02 reads real rows from SQLite and deletes this file. It
 * exists so the Phase 01 language exercises need no database.
 *
 * Dates are built relative to the current month rather than pinned to fixed
 * ones. The page opens on the current month and summarises the previous one, so
 * fixed dates would mean an empty table the moment the calendar moved on.
 *
 * That makes this file depend on the timezone having already been set — see the
 * load-order note in playground.php.
 *
 * Each row is an associative array shaped like the row the `transactions` table
 * will eventually produce:
 *
 *     type         'income' or 'expense'
 *     amount_cents integer, always positive
 *     occurred_on  'YYYY-MM-DD' text
 *     category     category name
 *     note         free text, or null when there is none
 *
 * Two decisions here carry straight into the database schema:
 *
 * 1. Amounts are integer cents, never floats. Binary floating point cannot
 *    represent most decimal fractions exactly, so money summed as floats
 *    drifts - 0.1 + 0.2 is not 0.3. Integers make the arithmetic exact.
 *
 * 2. Direction lives in `type`, so `amount_cents` stays positive. Allowing a
 *    negative amount alongside a `type` of 'income' would give two
 *    contradictory ways to express the same thing.
 *
 * Note this file assigns a variable rather than returning a value, so
 * `$sampleTransactions` lands in the scope of whatever file requires it. That
 * leakage is deliberate - it is one of the procedural-pain points the brief
 * asks you to feel before reaching for better structure.
 *
 * @var array<int, array{
 *     type: string,
 *     amount_cents: int,
 *     occurred_on: string,
 *     category: string,
 *     note: ?string
 * }>
 */

$currentMonth  = new DateTimeImmutable('first day of this month');
$previousMonth = $currentMonth->modify('-1 month');

// Dates are built as 'Y-m-' plus a day number. Every day used here is 28 or
// below, so the result is a real date in every month. A 31 in this position
// would render 31 February in a short month and store a date that does not
// exist.
$sampleTransactions = [
    // ----- the previous month -----
    [
        'type'         => 'income',
        'amount_cents' => 320_000,          // 3,200.00
        'occurred_on'  => $previousMonth->format('Y-m-01'),
        'category'     => 'Salary',
        'note'         => 'Monthly salary',
    ],
    [
        'type'         => 'expense',
        'amount_cents' => 145_000,          // 1,450.00
        'occurred_on'  => $previousMonth->format('Y-m-02'),
        'category'     => 'Rent',
        'note'         => 'Monthly rent',
    ],
    [
        'type'         => 'expense',
        'amount_cents' => 20_315,           // 203.15
        'occurred_on'  => $previousMonth->format('Y-m-14'),
        'category'     => 'Groceries',
        'note'         => 'Weekly shop',
    ],
    [
        'type'         => 'income',
        'amount_cents' => 120_000,          // 1,200.00
        'occurred_on'  => $previousMonth->format('Y-m-20'),
        'category'     => 'Freelance',
        'note'         => null,             // exercises null handling in the view
    ],
    [
        'type'         => 'expense',
        'amount_cents' => 6_200,            // 62.00
        'occurred_on'  => $previousMonth->format('Y-m-27'),
        'category'     => 'Transport',
        'note'         => 'Monthly transit pass',
    ],

    // ----- the current month -----
    [
        'type'         => 'income',
        'amount_cents' => 320_000,          // 3,200.00
        'occurred_on'  => $currentMonth->format('Y-m-01'),
        'category'     => 'Salary',
        'note'         => 'Monthly salary',
    ],
    [
        'type'         => 'expense',
        'amount_cents' => 145_000,          // 1,450.00
        'occurred_on'  => $currentMonth->format('Y-m-02'),
        'category'     => 'Rent',
        'note'         => 'Monthly rent',
    ],
    [
        'type'         => 'expense',
        'amount_cents' => 18_745,           // 187.45
        'occurred_on'  => $currentMonth->format('Y-m-05'),
        'category'     => 'Groceries',
        'note'         => 'Weekly shop',
    ],
    [
        'type'         => 'income',
        'amount_cents' => 45_050,           // 450.50
        'occurred_on'  => $currentMonth->format('Y-m-12'),
        'category'     => 'Freelance',
        'note'         => 'Invoice #014',
    ],
    [
        'type'         => 'expense',
        'amount_cents' => 9_430,            // 94.30
        'occurred_on'  => $currentMonth->format('Y-m-15'),
        'category'     => 'Utilities',
        'note'         => 'Electricity + water',
    ],
];

// These two anchors are scaffolding, not data. Unsetting them keeps them out of
// the requiring scope, where the page keeps month variables of its own.
unset($currentMonth, $previousMonth);
