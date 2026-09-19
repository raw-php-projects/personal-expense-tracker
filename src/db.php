<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * The application's database connection.
 *
 * One connection per request, handed back on every later call. Opening a second
 * connection to the same SQLite file would take a second write lock, so a page
 * could deadlock against itself.
 */

/**
 * Return the shared PDO connection, opening it the first time it is asked for.
 *
 * The database path is read from config here rather than passed in. That keeps
 * call sites short, at the cost of hiding a dependency: a caller cannot point
 * this at a different database, which is what makes it awkward to test.
 *
 * @return PDO With exceptions on, associative fetches by default, and foreign
 *             keys enforced.
 * @throws PDOException If the database cannot be opened, for example when the
 *                      data folder is missing. Failing here is deliberate - a
 *                      silent failure would resurface later as missing data.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . (string) config('db_path'), null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Accepted but not modelled by SQLite's driver - reading it back
            // throws. A no-op here, set so the options stay right for MySQL.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Foreign keys are off by default and the setting lives on the
        // connection, so it has to be enabled on every connect. schema.sql sets
        // it too, but that only covered the run that created the tables.
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}
