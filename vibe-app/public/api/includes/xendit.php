<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function xendit_secret_key(): string
{
    $secret = env_value('XENDIT_SECRET_KEY', '');
    if ($secret === '') {
        throw new RuntimeException('Missing XENDIT_SECRET_KEY.');
    }

    return $secret;
}

function xendit_currency(): string
{
    return env_value('XENDIT_CURRENCY', 'PHP') ?? 'PHP';
}

function xendit_api_base(): string
{
    return 'https://api.xendit.co';
}

function xendit_request(string $method, string $path, array $payload = []): array
{
    $ch = curl_init();
    $url = rtrim(xendit_api_base(), '/') . $path;
    $headers = [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(xendit_secret_key() . ':'),
        'Content-Type: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $payload !== [] ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException('Failed to reach Xendit: ' . $error);
    }

    $decoded = json_decode($raw, true);
    if ($status >= 400) {
        log_event('xendit.http_error', [
            'path' => $path,
            'status' => $status,
            'response' => $decoded ?: $raw,
        ]);
        throw new RuntimeException('Xendit request failed with status ' . $status . '.');
    }

    return is_array($decoded) ? $decoded : [];
}

function xendit_create_payment_session(array $order, array $pricing): array
{
    [$givenName, $surname] = split_name($order['customer_name']);
    $customerReference = preg_replace('/[^A-Za-z0-9]/', '', $order['reference_id'] . substr(md5($order['email']), 0, 8)) ?: preg_replace('/[^A-Za-z0-9]/', '', $order['reference_id']);
    $paymentReference = sprintf('%s-P%s', $order['reference_id'], $order['retry_count'] + 1);

    $payload = [
        'reference_id' => $paymentReference,
        'session_type' => 'PAY',
        'mode' => 'PAYMENT_LINK',
        'amount' => $pricing['total'],
        'currency' => $pricing['currency'],
        'country' => 'PH',
        'customer' => [
            'reference_id' => substr($customerReference, 0, 64),
            'type' => 'INDIVIDUAL',
            'email' => $order['email'],
            'mobile_number' => normalize_phone($order['phone']),
            'individual_detail' => array_filter([
                'given_names' => substr($givenName, 0, 50),
                'surname' => $surname !== '' ? substr($surname, 0, 50) : null,
            ]),
        ],
        'items' => [[
            'reference_id' => 'yorvision-mineral-drops',
            'name' => 'YOR Vision Mineral Drops',
            'description' => $pricing['plan_label'],
            'type' => 'PHYSICAL_PRODUCT',
            'category' => 'HEALTH_BEAUTY',
            'net_unit_amount' => $pricing['unit_price'],
            'quantity' => $pricing['quantity'],
            'currency' => $pricing['currency'],
            'url' => base_url(),
        ]],
        'capture_method' => 'AUTOMATIC',
        'locale' => 'en',
        'description' => 'YOR Vision order ' . $order['reference_id'],
        'success_return_url' => redirect_url_with_reference(env_value('XENDIT_SUCCESS_REDIRECT_URL', base_url()) ?? base_url(), $order['reference_id'], 'success'),
        'cancel_return_url' => redirect_url_with_reference(env_value('XENDIT_FAILURE_REDIRECT_URL', base_url()) ?? base_url(), $order['reference_id'], 'failure'),
        'metadata' => [
            'order_reference' => $order['reference_id'],
            'plan_key' => $pricing['plan_key'],
            'quantity' => (string) $pricing['quantity'],
        ],
    ];

    $response = xendit_request('POST', '/sessions', $payload);

    return [
        'driver' => 'payment_session',
        'payment_reference_id' => $paymentReference,
        'session_id' => $response['payment_session_id'] ?? null,
        'payment_request_id' => $response['payment_request_id'] ?? null,
        'payment_id' => $response['payment_id'] ?? null,
        'invoice_id' => null,
        'checkout_url' => $response['payment_link_url'] ?? null,
        'expires_at' => $response['expires_at'] ?? null,
        'raw_response' => $response,
    ];
}

function xendit_create_legacy_invoice(array $order, array $pricing): array
{
    $paymentReference = sprintf('%s-P%s', $order['reference_id'], $order['retry_count'] + 1);
    [$givenName] = split_name($order['customer_name']);

    $payload = [
        'external_id' => $paymentReference,
        'amount' => $pricing['total'],
        'description' => 'YOR Vision order ' . $order['reference_id'],
        'currency' => $pricing['currency'],
        'payer_email' => $order['email'],
        'success_redirect_url' => redirect_url_with_reference(env_value('XENDIT_SUCCESS_REDIRECT_URL', base_url()) ?? base_url(), $order['reference_id'], 'success'),
        'failure_redirect_url' => redirect_url_with_reference(env_value('XENDIT_FAILURE_REDIRECT_URL', base_url()) ?? base_url(), $order['reference_id'], 'failure'),
        'customer' => [
            'given_names' => $givenName,
            'email' => $order['email'],
            'mobile_number' => normalize_phone($order['phone']),
        ],
        'items' => [[
            'name' => 'YOR Vision Mineral Drops',
            'quantity' => $pricing['quantity'],
            'price' => $pricing['unit_price'],
            'category' => 'HEALTH_BEAUTY',
        ]],
        'metadata' => [
            'order_reference' => $order['reference_id'],
        ],
    ];

    $response = xendit_request('POST', '/v2/invoices', $payload);

    return [
        'driver' => 'invoice',
        'payment_reference_id' => $paymentReference,
        'session_id' => null,
        'payment_request_id' => null,
        'payment_id' => null,
        'invoice_id' => $response['id'] ?? null,
        'checkout_url' => $response['invoice_url'] ?? null,
        'expires_at' => $response['expiry_date'] ?? null,
        'raw_response' => $response,
    ];
}

function xendit_create_checkout(array $order, array $pricing): array
{
    try {
        return xendit_create_payment_session($order, $pricing);
    } catch (Throwable $exception) {
        log_event('xendit.payment_session_fallback', [
            'order_reference' => $order['reference_id'],
            'message' => $exception->getMessage(),
        ]);

        return xendit_create_legacy_invoice($order, $pricing);
    }
}

