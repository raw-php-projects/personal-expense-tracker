<?php

declare(strict_types=1);

/**
 * Build the development database from schema.sql and seed.sql.
 *
 * A script rather than a shell one-liner for two reasons: the sqlite3 command
 * line tool is not installed on this machine, and loading the files through PDO
 * is the same code path the app itself uses.
 *
 * Usage:
 *     php bin/init-db.php           build, but refuse if a database already exists
 *     php bin/init-db.php --fresh   delete the existing database and rebuild
 *
 * Exit codes: 0 built, 1 refused to overwrite, 2 failed.
 */

require dirname(__DIR__) . '/src/functions.php';

// Dates in seed.sql are relative to the current month, so the timezone has to be
// the configured one before any of it runs.
date_default_timezone_set((string) config('timezone'));

$root   = dirname(__DIR__);
$dbPath = (string) config('db_path');
$fresh  = in_array('--fresh', $argv, true);

// PDO creates the database file on demand but not the folder holding it, and a
// missing folder surfaces as a vague "unable to open database file".
$dataDir = dirname($dbPath);
if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
    fwrite(STDERR, "Could not create the data directory: {$dataDir}\n");
    exit(2);
}

if (file_exists($dbPath)) {
    if (!$fresh) {
        fwrite(STDERR, "A database already exists:\n    {$dbPath}\n");
        fwrite(STDERR, "Re-run with --fresh to delete it and rebuild from scratch.\n");
        exit(1);
    }

    unlink($dbPath);
    echo "Removed the existing database.\n";
}

try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Foreign keys are off by default and the setting is per connection, so it
    // has to be switched on here even though schema.sql sets it too.
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Both files go through exec(), which runs every statement in the string.
    // prepare() would run only the first one, which is why seed.sql cannot use
    // bound parameters.
    $pdo->exec((string) file_get_contents($root . '/schema.sql'));
    $pdo->exec((string) file_get_contents($root . '/seed.sql'));

    $categories   = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $transactions = (int) $pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    $months       = $pdo->query(
        "SELECT DISTINCT strftime('%Y-%m', occurred_on) AS month
           FROM transactions ORDER BY month"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    fwrite(STDERR, "Failed: " . $e->getMessage() . "\n");
    exit(2);
}

echo "Created {$dbPath}\n";
echo "  categories   : {$categories}\n";
echo "  transactions : {$transactions}\n";
echo "  months       : " . implode(', ', $months) . "\n";
