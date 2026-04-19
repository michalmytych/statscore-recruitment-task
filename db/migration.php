<?php

declare(strict_types=1);

$databasePath = __DIR__ . '/events.sqlite';

$connection = new PDO('sqlite:' . $databasePath);
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$connection->exec(
    'CREATE TABLE IF NOT EXISTS events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_type TEXT NOT NULL,
        match_id TEXT NOT NULL,
        event_minute INTEGER NOT NULL,
        event_second INTEGER NOT NULL,
        event_timestamp INTEGER,
        idempotency_key TEXT UNIQUE NOT NULL,
        payload_json TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )'
);

// Basic index as it was said that app should be somehow optimized for large volumes
$connection->exec(
    'CREATE INDEX IF NOT EXISTS idx_events_match_id ON events (match_id)'
);
