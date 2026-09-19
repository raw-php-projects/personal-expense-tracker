-- Sample data for development.
--
-- Loaded by bin/init-db.php immediately after schema.sql, into an empty
-- database. It is NOT idempotent, so it needs a fresh database every time, which
-- init-db.php enforces.
--
-- Running it against an already-seeded database fails with a UNIQUE violation on
-- categories.id, because the categories below are given explicit ids, and
-- execution stops at that first failing statement. Nothing is duplicated. That
-- is a side effect of the explicit ids rather than a designed guarantee: drop
-- the id column from the category insert below and a second run would silently
-- duplicate every transaction instead.
--
-- Dates are relative to the current month so the dashboard always has something
-- to show. Every date is built with:
--
--     strftime('%Y-%m-<day>', 'now', 'localtime', 'start of month')
--     strftime('%Y-%m-<day>', 'now', 'localtime', 'start of month', '-1 month')
--
-- 'localtime' tells SQLite to use the machine's timezone rather than UTC.
-- Without it the first six hours of a month would seed into the wrong one.
--
-- The numbers below are the same shape as Phase 01's sample data, so the totals
-- already checked by hand still apply.

-- Explicit ids, so the numbers used by the transactions below are readable in
-- both places rather than depending on insertion order.
INSERT INTO categories (id, name, kind) VALUES
    (1, 'Salary',    'income'),
    (2, 'Freelance', 'income'),
    (3, 'Groceries', 'expense'),
    (4, 'Rent',      'expense'),
    (5, 'Transport', 'expense'),
    (6, 'Utilities', 'expense');

INSERT INTO transactions (category_id, type, amount_cents, occurred_on, note) VALUES
    -- Last month: 4,400.00 in, 1,715.15 out.
    (1, 'income',  320000,
     strftime('%Y-%m-01', 'now', 'localtime', 'start of month', '-1 month'), 'Monthly salary'),
    (4, 'expense', 145000,
     strftime('%Y-%m-02', 'now', 'localtime', 'start of month', '-1 month'), 'Monthly rent'),
    (3, 'expense',  20315,
     strftime('%Y-%m-14', 'now', 'localtime', 'start of month', '-1 month'), 'Weekly shop'),
    -- Deliberately no note, so the views have a NULL to deal with.
    (2, 'income',  120000,
     strftime('%Y-%m-20', 'now', 'localtime', 'start of month', '-1 month'), NULL),
    (5, 'expense',   6200,
     strftime('%Y-%m-27', 'now', 'localtime', 'start of month', '-1 month'), 'Monthly transit pass'),

    -- This month: 3,650.50 in, 1,731.75 out.
    (1, 'income',  320000,
     strftime('%Y-%m-01', 'now', 'localtime', 'start of month'), 'Monthly salary'),
    (4, 'expense', 145000,
     strftime('%Y-%m-02', 'now', 'localtime', 'start of month'), 'Monthly rent'),
    (3, 'expense',  18745,
     strftime('%Y-%m-05', 'now', 'localtime', 'start of month'), 'Weekly shop'),
    (2, 'income',   45050,
     strftime('%Y-%m-12', 'now', 'localtime', 'start of month'), 'Invoice #014'),
    (6, 'expense',   9430,
     strftime('%Y-%m-15', 'now', 'localtime', 'start of month'), 'Electricity + water');
