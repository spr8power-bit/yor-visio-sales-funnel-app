<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/orders.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['message' => 'Method not allowed.'], 405);
}

$callbackToken = env_value('XENDIT_CALLBACK_TOKEN', '');
$requestToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
$webhookId = $_SERVER['HTTP_WEBHOOK_ID'] ?? ('legacy-' . sha1(file_get_contents('php://input') ?: uniqid('', true)));

if ($callbackToken === '' || !hash_equals($callbackToken, $requestToken)) {
    log_event('xendit.webhook.invalid_token', ['webhook_id' => $webhookId]);
    json_response(['message' => 'Forbidden.'], 403);
}

$payload = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($payload)) {
    json_response(['message' => 'Invalid payload.'], 400);
}

$event = (string) ($payload['event'] ?? '');
$data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
$resourceId = $data['payment_session_id'] ?? $data['payment_id'] ?? $data['id'] ?? $data['payment_request_id'] ?? null;
$paymentReferenceId = $data['reference_id'] ?? $data['external_id'] ?? null;
$orderReference = $data['metadata']['order_reference'] ?? null;

if (!reserve_webhook_event($webhookId, $event, is_string($resourceId) ? $resourceId : null, is_string($paymentReferenceId) ? $paymentReferenceId : null, is_string($orderReference) ? $orderReference : null, $payload)) {
    json_response(['message' => 'Duplicate webhook ignored.'], 200);
}

$order = null;
if (is_string($orderReference) && $orderReference !== '') {
    $order = find_order_by_reference($orderReference);
}

if (!$order && is_string($paymentReferenceId) && $paymentReferenceId !== '') {
    $order = find_order_by_payment_reference($paymentReferenceId);
}

if (!$order) {
    log_event('xendit.webhook.order_not_found', ['event' => $event, 'payment_reference_id' => $paymentReferenceId, 'order_reference' => $orderReference]);
    json_response(['message' => 'Accepted.'], 202);
}

$statusUpdates = [
    'raw_latest_webhook' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    'xendit_webhook_status' => $event,
];

if (isset($data['payment_session_id'])) {
    $statusUpdates['xendit_session_id'] = $data['payment_session_id'];
}
if (isset($data['payment_request_id'])) {
    $statusUpdates['xendit_payment_request_id'] = $data['payment_request_id'];
}
if (isset($data['payment_id'])) {
    $statusUpdates['xendit_payment_id'] = $data['payment_id'];
}
if (isset($data['payment_method'])) {
    $statusUpdates['xendit_payment_method'] = is_array($data['payment_method']) ? json_encode($data['payment_method']) : (string) $data['payment_method'];
}
if (isset($data['channel_code'])) {
    $statusUpdates['xendit_payment_channel'] = (string) $data['channel_code'];
}
if (isset($data['amount'])) {
    $statusUpdates['paid_amount'] = (int) round((float) $data['amount']);
}
if (isset($data['expires_at'])) {
    $statusUpdates['expires_at'] = (string) $data['expires_at'];
}

switch ($event) {
    case 'payment_session.completed':
    case 'payment.succeeded':
        $statusUpdates['status'] = 'paid';
        $statusUpdates['paid_at'] = gmdate('c');
        break;

    case 'payment_session.expired':
        $statusUpdates['status'] = 'expired';
        break;

    case 'payment.failed':
    case 'payment.failure':
        $statusUpdates['status'] = 'failed';
        break;
}

update_order((string) $order['reference_id'], $statusUpdates);
log_event('xendit.webhook.processed', ['reference_id' => $order['reference_id'], 'event' => $event, 'webhook_id' => $webhookId]);

json_response(['message' => 'OK'], 200);

