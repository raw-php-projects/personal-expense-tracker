<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// functions.php first: it defines config() and pulls in calculations.php.
require __DIR__ . '/../src/functions.php';

// The timezone must be set before anything computes a date. sample-data.php
// builds its dates relative to the current month, so loading it under the wrong
// timezone would place a month boundary in the wrong place. Phase 09's
// bootstrap turns this ordering rule into a single explicit step.
date_default_timezone_set((string) config('timezone'));

require __DIR__ . '/../src/sample-data.php';

$displayMonth = date('Y-m');

// Anchored to the first of the month *before* subtracting. PHP does not clamp
// when the target day does not exist in the previous month - it rolls forward.
// 31 March minus one month returns 3 March, the month it started in. Day 1
// always exists, so anchoring here is correct at every month end.
$previousMonth = (new DateTimeImmutable('first day of this month'))
    ->modify('-1 month')
    ->format('Y-m');

$summaries = [
    'Current month'  => $displayMonth,
    'Previous month' => $previousMonth,
];

// totals_by_category() deliberately returns first-seen order, so the ordering
// is decided here. The dashboard will want it by size, the list by date.
$byCategory = totals_by_category($sampleTransactions);
arsort($byCategory);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e((string) config('app_name')) ?> &mdash; Playground</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 62rem; margin: 2rem auto; padding: 0 1rem; color: #1f2933; line-height: 1.5; }
        h1 { margin-bottom: .25rem; }
        .subtitle { color: #627d98; margin-top: 0; }
        h2 { margin-top: 2rem; font-size: 1.1rem; }
        table { border-collapse: collapse; width: 100%; margin: .75rem 0; }
        th, td { border: 1px solid #d3dce6; padding: .45rem .7rem; text-align: left; }
        th { background: #f0f4f8; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #486581; }
        td.amount, th.amount { text-align: right; font-variant-numeric: tabular-nums; }
        tr.income td.amount { color: #0b7a3b; }
        tr.expense td.amount { color: #b3261e; }
        .cards { display: flex; gap: 1rem; flex-wrap: wrap; }
        .card { border: 1px solid #d3dce6; border-radius: 6px; padding: .7rem 1rem; min-width: 11rem; }
        .card .label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #627d98; }
        .card .value { font-size: 1.35rem; font-variant-numeric: tabular-nums; }
        .positive { color: #0b7a3b; }
        .negative { color: #b3261e; }
        .muted { color: #627d98; font-size: .875rem; }
    </style>
</head>
<body>
    <h1><?= e((string) config('app_name')) ?> &mdash; Playground</h1>
    <p class="subtitle">
        Hardcoded data, no database. Experiment page &mdash; not part of the final app.
        Amounts shown in <strong><?= e((string) config('currency_code')) ?></strong>
        (<?= e((string) config('currency_symbol')) ?>).
        Dates read in <strong><?= e(date_default_timezone_get()) ?></strong>.
    </p>

    <?php foreach ($summaries as $label => $month): ?>
        <?php
        $totals   = monthly_totals($sampleTransactions, $month);
        $netClass = $totals['net'] < 0 ? 'negative' : 'positive';
        ?>
        <h2><?= e($label) ?> &mdash; <?= e($month) ?></h2>

        <div class="cards">
            <div class="card">
                <div class="label">Income</div>
                <div class="value positive"><?= e(money($totals['income'])) ?></div>
            </div>
            <div class="card">
                <div class="label">Expense</div>
                <div class="value negative"><?= e(money($totals['expense'])) ?></div>
            </div>
            <div class="card">
                <div class="label">Net</div>
                <div class="value <?= e($netClass) ?>"><?= e(money($totals['net'])) ?></div>
            </div>
        </div>
    <?php endforeach; ?>

    <h2>All transactions</h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Type</th>
                <th class="amount">Amount</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sampleTransactions as $transaction): ?>
                <?php
                switch ($transaction['type']) {
                    case 'income':
                        $rowClass = 'income';
                        break;
                    case 'expense':
                        $rowClass = 'expense';
                        break;
                    default:
                        $rowClass = '';
                        break;
                }

                $note = $transaction['note'];
                ?>
                <tr class="<?= e($rowClass) ?>">
                    <td><?= e($transaction['occurred_on']) ?></td>
                    <td><?= e($transaction['category']) ?></td>
                    <td><?= e($transaction['type']) ?></td>
                    <td class="amount"><?= e(money($transaction['amount_cents'])) ?></td>
                    <td><?= $note === null ? '&mdash;' : e($note) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>By category <span class="muted">(whole dataset, largest first)</span></h2>

    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th class="amount">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($byCategory as $category => $total): ?>
                <tr>
                    <td><?= e((string) $category) ?></td>
                    <td class="amount"><?= e(money($total)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
