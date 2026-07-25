<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/orders.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['message' => 'Method not allowed.'], 405);
}

$referenceId = trim((string) ($_GET['reference'] ?? $_GET['order_ref'] ?? ''));
if ($referenceId === '') {
    json_response(['message' => 'Missing order reference.'], 422);
}

$order = find_order_by_reference($referenceId);
if (!$order) {
    json_response(['message' => 'Order not found.'], 404);
}

if ($order['status'] === 'pending_payment' && !empty($order['expires_at']) && strtotime((string) $order['expires_at']) < time()) {
    $order = update_order($referenceId, ['status' => 'expired']) ?? $order;
}

json_response([
    'reference_id' => $order['reference_id'],
    'status' => $order['status'],
    'payment_type' => $order['payment_type'],
    'customer_name' => $order['customer_name'],
    'address' => $order['address'],
    'plan_label' => $order['plan_label'],
    'quantity' => (int) $order['quantity'],
    'total' => (int) $order['total'],
    'currency' => $order['currency'],
    'payment_method' => $order['xendit_payment_method'] ?: ($order['payment_type'] === 'cod' ? 'Cash on Delivery' : 'Xendit Hosted Checkout'),
    'paid_amount' => $order['paid_amount'] !== null ? (int) $order['paid_amount'] : null,
    'retry_allowed' => in_array($order['status'], ['failed', 'expired', 'pending_payment'], true),
]);

