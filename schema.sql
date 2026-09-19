-- Schema for the Personal Expense Tracker.
--
-- Apply with:  sqlite3 data/tracker.sqlite < schema.sql
-- The sqlite3 CLI is not installed here, so bin/init-db.php loads this through
-- PDO instead. Creates tables only - no database file, no rows.

-- Foreign keys are off by default and the setting is per connection, so this
-- covers only the run creating the tables. db.php sets it again on connect.
PRAGMA foreign_keys = ON;

CREATE TABLE categories (
    -- INTEGER PRIMARY KEY already auto-assigns. AUTOINCREMENT adds the promise
    -- that an id is never reused after a delete.
    id         INTEGER PRIMARY KEY AUTOINCREMENT,

    -- Stops two categories called "Groceries", which would split one budget
    -- across rows that look identical in every list.
    name       TEXT    NOT NULL UNIQUE,

    -- One of these two only. The app branches on this to decide whether a
    -- category is usable for income or for expense.
    kind       TEXT    NOT NULL CHECK (kind IN ('income', 'expense')),

    -- UTC audit stamp; unrelated to occurred_on, which is a local date.
    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE transactions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,

    -- Every transaction needs a category, and cannot outlive it.
    category_id INTEGER NOT NULL REFERENCES categories(id),

    -- Direction of the money. With this, amount_cents stays positive and
    -- means one thing.
    type        TEXT    NOT NULL CHECK (type IN ('income', 'expense')),

    -- Positive only: a negative amount would be a second, contradictory way
    -- to say "expense", and zero is not a transaction.
    amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),

    -- 'YYYY-MM-DD' text, not a timestamp. That format sorts and compares
    -- correctly as text, so months match by prefix with no date parsing.
    occurred_on TEXT    NOT NULL,

    -- NULL is the honest value for "no note"; views render it as a dash.
    note        TEXT,

    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- Not enforced here: that a transaction's `type` matches its category's `kind`.
-- A CHECK cannot read another table, so PHP validates it on every write.
-- For the dashboard and month filter, which narrow by date.
CREATE INDEX idx_tx_occurred_on ON transactions(occurred_on);

-- For joining a transaction to its category, and counting per category.
CREATE INDEX idx_tx_category ON transactions(category_id);
