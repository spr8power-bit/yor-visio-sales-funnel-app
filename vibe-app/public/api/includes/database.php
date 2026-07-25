<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function payment_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    ensure_storage_directories();

    $pdo = new PDO('sqlite:' . app_db_path());
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');

    initialize_payment_schema($pdo);

    return $pdo;
}

function initialize_payment_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reference_id TEXT NOT NULL UNIQUE,
            payment_reference_id TEXT,
            status TEXT NOT NULL,
            payment_type TEXT NOT NULL,
            customer_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT NOT NULL,
            address TEXT NOT NULL,
            region TEXT NOT NULL,
            province TEXT NOT NULL,
            city TEXT NOT NULL,
            barangay TEXT NOT NULL,
            postal TEXT NOT NULL,
            notes TEXT,
            plan_key TEXT NOT NULL,
            plan_label TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price INTEGER NOT NULL,
            subtotal INTEGER NOT NULL,
            discount INTEGER NOT NULL,
            shipping INTEGER NOT NULL,
            total INTEGER NOT NULL,
            currency TEXT NOT NULL,
            retry_count INTEGER NOT NULL DEFAULT 0,
            xendit_session_id TEXT,
            xendit_payment_request_id TEXT,
            xendit_payment_id TEXT,
            xendit_invoice_id TEXT,
            xendit_checkout_url TEXT,
            xendit_payment_method TEXT,
            xendit_payment_channel TEXT,
            xendit_webhook_status TEXT,
            raw_latest_webhook TEXT,
            paid_amount INTEGER,
            paid_at TEXT,
            expires_at TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS webhook_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            webhook_id TEXT NOT NULL UNIQUE,
            event_type TEXT,
            resource_id TEXT,
            payment_reference_id TEXT,
            order_reference_id TEXT,
            payload TEXT NOT NULL,
            created_at TEXT NOT NULL
        )'
    );
}

