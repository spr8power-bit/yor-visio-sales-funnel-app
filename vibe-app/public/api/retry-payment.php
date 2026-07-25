<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/xendit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['message' => 'Method not allowed.'], 405);
}

$payload = json_input();
if (!verify_csrf_request($payload)) {
    json_response(['message' => 'Security validation failed.'], 419);
}

$referenceId = trim((string) ($payload['reference_id'] ?? ''));
if ($referenceId === '') {
    json_response(['message' => 'Missing order reference.'], 422);
}

$order = find_order_by_reference($referenceId);
if (!$order) {
    json_response(['message' => 'Order not found.'], 404);
}

if ($order['payment_type'] !== 'xendit') {
    json_response(['message' => 'Retry is only available for online payments.'], 422);
}

if (!in_array($order['status'], ['failed', 'expired', 'pending_payment'], true)) {
    json_response(['message' => 'This order cannot be retried.'], 422);
}

$pricing = calculate_order_pricing((string) $order['plan_key'], (int) $order['quantity']);
$order['retry_count'] = (int) $order['retry_count'] + 1;

try {
    $checkout = xendit_create_checkout($order, $pricing);
    $updated = update_order($referenceId, [
        'status' => 'pending_payment',
        'retry_count' => (int) $order['retry_count'],
        'payment_reference_id' => $checkout['payment_reference_id'],
        'xendit_session_id' => $checkout['session_id'],
        'xendit_payment_request_id' => $checkout['payment_request_id'],
        'xendit_payment_id' => $checkout['payment_id'],
        'xendit_invoice_id' => $checkout['invoice_id'],
        'xendit_checkout_url' => $checkout['checkout_url'],
        'expires_at' => $checkout['expires_at'],
        'raw_latest_webhook' => json_encode($checkout['raw_response'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);

    log_event('checkout.retry_created', [
        'reference_id' => $referenceId,
        'payment_reference_id' => $checkout['payment_reference_id'],
    ]);

    json_response([
        'status' => $updated['status'] ?? 'pending_payment',
        'checkout_url' => $checkout['checkout_url'],
    ]);
} catch (Throwable $exception) {
    log_event('checkout.retry_failed', ['reference_id' => $referenceId, 'message' => $exception->getMessage()]);
    json_response(['message' => 'Unable to recreate the payment session. Please try again later.'], 502);
}

