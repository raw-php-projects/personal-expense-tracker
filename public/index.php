<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('UTC');

$appName = 'Personal Expense Tracker';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>

    <p>Phase 00 complete &mdash; the skeleton is alive.</p>

    <p>Served by PHP <?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?>.</p>
</body>
</html>
