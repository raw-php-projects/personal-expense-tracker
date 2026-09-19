<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// functions.php provides config(), e() and money(), and pulls in calculations.php.
require __DIR__ . '/../src/functions.php';
require __DIR__ . '/../src/db.php';

date_default_timezone_set((string) config('timezone'));

// Newest first. The id breaks ties so two transactions on the same day keep a
// stable order instead of shuffling between page loads.
//
// Prepared even though nothing is bound yet: this query gains a WHERE clause
// with parameters as soon as filtering arrives, and using the same shape for
// every statement means there is no "remember to switch to prepare" step.
$statement = db()->prepare(
    'SELECT t.id, t.type, t.amount_cents, t.occurred_on, t.note, c.name AS category
       FROM transactions t
       JOIN categories c ON c.id = t.category_id
      ORDER BY t.occurred_on DESC, t.id DESC'
);
$statement->execute();

$transactions = $statement->fetchAll();

// Totalled in PHP from the rows already in hand rather than with a second
// query, which also means the footer can never disagree with the table above it.
$totalIncome  = sum_by_type($transactions, 'income');
$totalExpense = sum_by_type($transactions, 'expense');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e((string) config('app_name')) ?> &mdash; Transactions</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 62rem; margin: 2rem auto; padding: 0 1rem; color: #1f2933; }
        h1 { margin-bottom: .25rem; }
        .muted { color: #627d98; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { border: 1px solid #d3dce6; padding: .45rem .7rem; text-align: left; }
        th { background: #f0f4f8; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #486581; }
        td.amount, th.amount { text-align: right; font-variant-numeric: tabular-nums; }
        tr.income td.amount { color: #0b7a3b; }
        tr.expense td.amount { color: #b3261e; }
        tfoot td { font-weight: 600; background: #fafcfe; border-top: 2px solid #9fb3c8; }
    </style>
</head>
<body>
    <h1>Transactions</h1>
    <p class="muted">
        <?= e((string) count($transactions)) ?> rows &middot;
        amounts in <?= e((string) config('currency_code')) ?>
        (<?= e((string) config('currency_symbol')) ?>)
    </p>

    <?php if ($transactions === []): ?>
        <p>No transactions yet. Run <code>php bin/init-db.php --fresh</code> to seed some.</p>
    <?php else: ?>
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
                <?php foreach ($transactions as $transaction): ?>
                    <?php
                    // Reuse the Phase 01 switch purely to pick a colour class, so
                    // income and expense are distinguishable at a glance.
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
            <!-- "total" marks these as summary rows, so they can be styled and
                 targeted separately from the data rows above. -->
            <tfoot>
                <tr class="income total">
                    <td colspan="3">Total income</td>
                    <td class="amount"><?= e(money($totalIncome)) ?></td>
                    <td></td>
                </tr>
                <tr class="expense total">
                    <td colspan="3">Total expense</td>
                    <td class="amount"><?= e(money($totalExpense)) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</body>
</html>
