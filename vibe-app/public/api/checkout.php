<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/orders.php';
require_once __DIR__ . '/includes/xendit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['message' => 'Method not allowed.'], 405);
}

$payload = json_input();

if (!verify_csrf_request($payload)) {
    log_event('checkout.csrf_failed', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    json_response(['message' => 'Security validation failed. Please refresh and try again.'], 419);
}

$requiredFields = [
    'name', 'phone', 'email', 'address', 'region', 'province', 'city', 'barangay', 'postal',
    'plan_key', 'quantity', 'payment',
];

$errors = [];
foreach ($requiredFields as $field) {
    if (!isset($payload[$field]) || trim((string) $payload[$field]) === '') {
        $errors[$field] = 'This field is required.';
    }
}

if (!filter_var((string) ($payload['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}

$normalizedPhone = normalize_phone((string) ($payload['phone'] ?? ''));
if (!preg_match('/^\+639\d{9}$/', $normalizedPhone)) {
    $errors['phone'] = 'Please enter a valid Philippine mobile number.';
}

$quantity = max(1, (int) ($payload['quantity'] ?? 1));
$planKey = (string) ($payload['plan_key'] ?? 'popular');
$pricing = calculate_order_pricing($planKey, $quantity);

$paymentType = (string) ($payload['payment'] ?? 'cod');
if (!in_array($paymentType, ['cod', 'xendit'], true)) {
    $errors['payment'] = 'Please select a supported payment method.';
}

if ($errors !== []) {
    json_response(['message' => 'Validation failed.', 'errors' => $errors], 422);
}

$referenceId = generate_order_reference();
$baseOrder = [
    'reference_id' => $referenceId,
    'payment_reference_id' => null,
    'status' => $paymentType === 'cod' ? 'cod_pending' : 'pending_payment',
    'payment_type' => $paymentType,
    'customer_name' => trim((string) $payload['name']),
    'phone' => trim((string) $payload['phone']),
    'email' => trim((string) $payload['email']),
    'address' => trim((string) $payload['address']),
    'region' => trim((string) $payload['region']),
    'province' => trim((string) $payload['province']),
    'city' => trim((string) $payload['city']),
    'barangay' => trim((string) $payload['barangay']),
    'postal' => trim((string) $payload['postal']),
    'notes' => trim((string) ($payload['notes'] ?? '')),
    'plan_key' => $pricing['plan_key'],
    'plan_label' => $pricing['plan_label'],
    'quantity' => $pricing['quantity'],
    'unit_price' => $pricing['unit_price'],
    'subtotal' => $pricing['subtotal'],
    'discount' => $pricing['discount'],
    'shipping' => $pricing['shipping'],
    'total' => $pricing['total'],
    'currency' => $pricing['currency'],
    'retry_count' => 0,
];

$order = create_order($baseOrder);

if ($paymentType === 'cod') {
    log_event('checkout.cod_created', ['reference_id' => $referenceId, 'total' => $pricing['total']]);

    json_response([
        'status' => 'cod_pending',
        'reference_id' => $referenceId,
        'order' => [
            'reference_id' => $referenceId,
            'status' => 'cod_pending',
            'payment' => 'Cash on Delivery',
            'name' => $order['customer_name'],
            'address' => $order['address'],
            'plan' => $order['plan_label'],
            'quantity' => (int) $order['quantity'],
            'total' => (int) $order['total'],
            'currency' => $order['currency'],
        ],
    ]);
}

try {
    $checkout = xendit_create_checkout($order, $pricing);
    $order = update_order($referenceId, [
        'payment_reference_id' => $checkout['payment_reference_id'],
        'xendit_session_id' => $checkout['session_id'],
        'xendit_payment_request_id' => $checkout['payment_request_id'],
        'xendit_payment_id' => $checkout['payment_id'],
        'xendit_invoice_id' => $checkout['invoice_id'],
        'xendit_checkout_url' => $checkout['checkout_url'],
        'expires_at' => $checkout['expires_at'],
        'raw_latest_webhook' => json_encode($checkout['raw_response'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);

    log_event('checkout.online_created', [
        'reference_id' => $referenceId,
        'payment_reference_id' => $checkout['payment_reference_id'],
        'driver' => $checkout['driver'],
        'checkout_url' => $checkout['checkout_url'],
    ]);

    json_response([
        'status' => 'pending_payment',
        'reference_id' => $referenceId,
        'checkout_url' => $checkout['checkout_url'],
    ]);
} catch (Throwable $exception) {
    update_order($referenceId, ['status' => 'failed']);
    log_event('checkout.online_failed', ['reference_id' => $referenceId, 'message' => $exception->getMessage()]);

    json_response([
        'message' => 'Unable to create the online payment session right now. Please try again or use Cash on Delivery.',
    ], 502);
}

