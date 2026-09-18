<?php

declare(strict_types=1);

/**
 * Pure calculation helpers — money formatting, totals, and groupings.
 *
 * Nothing in this file may touch PDO, $_POST, $_GET, $_SESSION, or echo. It
 * takes plain arrays in and returns plain values out. That purity is what lets
 * Phase 10 unit-test it with no database and no request.
 */

/**
 * Format a whole number of cents as a human-readable money string.
 *
 * The cents/whole split uses integer arithmetic rather than `$cents / 100`.
 * That division yields a float, and binary floating point cannot represent most
 * decimal fractions exactly — the same defect that makes 0.1 + 0.2 unequal to
 * 0.3, and the reason money is stored as cents in the first place.
 *
 * `number_format()` is used only to group the thousands of a value that is
 * already an integer, so no money is ever rounded through a float.
 *
 * The symbol is a parameter rather than a lookup. This file stays free of I/O
 * and configuration so it can be tested without either; `money()` in
 * `functions.php` is the config-aware wrapper callers normally use.
 *
 * @param int $cents Amount in cents. May be negative or zero.
 * @param string $symbol Symbol to place before the digits, e.g. '$'. Pass ''
 *                       for a bare number. The sign stays ahead of it either
 *                       way, so a negative reads "-$2.50" and not "$-2.50".
 * @return string Always exactly two decimal places, e.g. "1,234.56", "$0.05",
 *                "0.00", "-$2.50".
 */
function format_money(int $cents, string $symbol = ''): string
{
    $isNegative = $cents < 0;
    $absolute   = $isNegative ? -$cents : $cents;

    $whole    = intdiv($absolute, 100);
    $fraction = $absolute % 100;

    return sprintf(
        '%s%s%s.%02d',
        $isNegative ? '-' : '',
        $symbol,
        number_format($whole, 0, '.', ','),
        $fraction
    );
}

/**
 * Sum the amounts of every transaction of one type.
 *
 * `amount_cents` is always positive and direction lives in `type`, so the
 * result is never negative. Use `monthly_totals()` when you want an
 * income-minus-expense figure.
 *
 * The type is checked against an allowlist and an unknown value throws rather
 * than returning 0. A silent zero in a money total is a wrong answer that is
 * indistinguishable from a right one: `sum_by_type($rows, 'expence')` should
 * fail loudly, not report that there was no income.
 *
 * @param array<int, array{type: string, amount_cents: int}> $transactions
 *        Rows to total. Only `type` and `amount_cents` are read.
 * @param string $type Either 'income' or 'expense'. Case-sensitive.
 * @return int Total in cents, zero or greater. Zero is a real answer — it means
 *         no row matched, not that something went wrong.
 * @throws InvalidArgumentException When `$type` is neither 'income' nor 'expense'.
 */
function sum_by_type(array $transactions, string $type): int
{
    if (!in_array($type, ['income', 'expense'], true)) {
        throw new InvalidArgumentException(
            sprintf('Unknown transaction type "%s"; expected "income" or "expense".', $type)
        );
    }

    $total = 0;

    foreach ($transactions as $transaction) {
        if ($transaction['type'] === $type) {
            $total += $transaction['amount_cents'];
        }
    }

    return $total;
}

/**
 * Total income, total expense, and the net for one calendar month.
 *
 * The month is matched on the date text, not by parsing it. `occurred_on` is
 * stored as 'YYYY-MM-DD', so its first seven characters are the month — no
 * DateTime is needed just to throw the day away.
 *
 * Always returns all three keys, so callers never have to guard against a
 * missing one, and an empty month yields zeroes rather than warnings.
 *
 * @param array<int, array{type: string, amount_cents: int, occurred_on: string}> $transactions
 *        Rows to total. Only `type`, `amount_cents`, and `occurred_on` are read.
 * @param string $month Month to match, as 'YYYY-MM'. Callers are expected to
 *                     have validated that format (Phase 05 and 06).
 * @return array{income: int, expense: int, net: int} All three in cents.
 *         `net` is `income - expense` and **may be negative** — an
 *         expense-heavy month is a normal outcome, not an error.
 */
function monthly_totals(array $transactions, string $month): array
{
    $inMonth = [];

    foreach ($transactions as $transaction) {
        if (substr($transaction['occurred_on'], 0, 7) === $month) {
            $inMonth[] = $transaction;
        }
    }

    $income  = sum_by_type($inMonth, 'income');
    $expense = sum_by_type($inMonth, 'expense');

    return [
        'income'  => $income,
        'expense' => $expense,
        'net'     => $income - $expense,
    ];
}

/**
 * Total the amounts per category name.
 *
 * The result is keyed by name, so a name that appears many times collapses into
 * a single figure.
 *
 * Names are merged across `type`. If one name were used for both income and
 * expense, the two would be summed into one number rather than reported
 * separately — which would be a net figure wearing a total's label. No name in
 * the sample data is used for both, so it never arises here, but the Phase 05
 * dashboard needs a genuine per-type breakdown and will require a different
 * shape. Merging is what a flat name => total contract can honestly mean.
 *
 * Returned in first-seen order. Sorting belongs to the caller: the dashboard
 * sorts by size, the transaction list sorts by date.
 *
 * @param array<int, array{category: string, amount_cents: int}> $transactions
 *        Rows to total. Only `category` and `amount_cents` are read.
 * @return array<string, int> Category name => total cents. Empty input yields
 *         an empty array — there are no categories to report, which is not the
 *         same as reporting zeroes. Caveat: PHP coerces an all-digit string key
 *         to an integer, so a category named "2024" arrives as int 2024.
 */
function totals_by_category(array $transactions): array
{
    $totals = [];

    foreach ($transactions as $transaction) {
        $category = $transaction['category'];

        $totals[$category] ??= 0;
        $totals[$category] += $transaction['amount_cents'];
    }

    return $totals;
}
