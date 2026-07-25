<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

function create_order(array $payload): array
{
    $pdo = payment_db();
    $now = gmdate('c');

    $statement = $pdo->prepare(
        'INSERT INTO orders (
            reference_id, payment_reference_id, status, payment_type, customer_name, phone, email, address, region,
            province, city, barangay, postal, notes, plan_key, plan_label, quantity, unit_price, subtotal, discount,
            shipping, total, currency, retry_count, created_at, updated_at
        ) VALUES (
            :reference_id, :payment_reference_id, :status, :payment_type, :customer_name, :phone, :email, :address, :region,
            :province, :city, :barangay, :postal, :notes, :plan_key, :plan_label, :quantity, :unit_price, :subtotal, :discount,
            :shipping, :total, :currency, :retry_count, :created_at, :updated_at
        )'
    );

    $statement->execute([
        'reference_id' => $payload['reference_id'],
        'payment_reference_id' => $payload['payment_reference_id'] ?? null,
        'status' => $payload['status'],
        'payment_type' => $payload['payment_type'],
        'customer_name' => $payload['customer_name'],
        'phone' => $payload['phone'],
        'email' => $payload['email'],
        'address' => $payload['address'],
        'region' => $payload['region'],
        'province' => $payload['province'],
        'city' => $payload['city'],
        'barangay' => $payload['barangay'],
        'postal' => $payload['postal'],
        'notes' => $payload['notes'] ?? '',
        'plan_key' => $payload['plan_key'],
        'plan_label' => $payload['plan_label'],
        'quantity' => $payload['quantity'],
        'unit_price' => $payload['unit_price'],
        'subtotal' => $payload['subtotal'],
        'discount' => $payload['discount'],
        'shipping' => $payload['shipping'],
        'total' => $payload['total'],
        'currency' => $payload['currency'],
        'retry_count' => $payload['retry_count'] ?? 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return find_order_by_reference($payload['reference_id']);
}

function update_order(string $referenceId, array $attributes): ?array
{
    if ($attributes === []) {
        return find_order_by_reference($referenceId);
    }

    $pdo = payment_db();
    $attributes['updated_at'] = gmdate('c');
    $pairs = [];

    foreach (array_keys($attributes) as $column) {
        $pairs[] = $column . ' = :' . $column;
    }

    $statement = $pdo->prepare('UPDATE orders SET ' . implode(', ', $pairs) . ' WHERE reference_id = :reference_id');
    $attributes['reference_id'] = $referenceId;
    $statement->execute($attributes);

    return find_order_by_reference($referenceId);
}

function find_order_by_reference(string $referenceId): ?array
{
    $pdo = payment_db();
    $statement = $pdo->prepare('SELECT * FROM orders WHERE reference_id = :reference_id LIMIT 1');
    $statement->execute(['reference_id' => $referenceId]);
    $order = $statement->fetch();

    return $order ?: null;
}

function find_order_by_payment_reference(string $paymentReferenceId): ?array
{
    $pdo = payment_db();
    $statement = $pdo->prepare('SELECT * FROM orders WHERE payment_reference_id = :payment_reference_id LIMIT 1');
    $statement->execute(['payment_reference_id' => $paymentReferenceId]);
    $order = $statement->fetch();

    return $order ?: null;
}

function reserve_webhook_event(string $webhookId, ?string $eventType, ?string $resourceId, ?string $paymentReferenceId, ?string $orderReferenceId, array $payload): bool
{
    $pdo = payment_db();
    $statement = $pdo->prepare(
        'INSERT OR IGNORE INTO webhook_events (webhook_id, event_type, resource_id, payment_reference_id, order_reference_id, payload, created_at)
         VALUES (:webhook_id, :event_type, :resource_id, :payment_reference_id, :order_reference_id, :payload, :created_at)'
    );

    $statement->execute([
        'webhook_id' => $webhookId,
        'event_type' => $eventType,
        'resource_id' => $resourceId,
        'payment_reference_id' => $paymentReferenceId,
        'order_reference_id' => $orderReferenceId,
        'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'created_at' => gmdate('c'),
    ]);

    return $statement->rowCount() > 0;
}

