<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

function app_public_root(): string
{
    return dirname(__DIR__, 2);
}

function app_storage_root(): string
{
    $envStoragePath = env_value('PAYMENT_STORAGE_PATH');
    if ($envStoragePath !== null && $envStoragePath !== '') {
        return $envStoragePath;
    }

    return dirname(app_public_root()) . DIRECTORY_SEPARATOR . 'yorvision-secure';
}

function app_db_path(): string
{
    return app_storage_root() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'orders.sqlite';
}

function ensure_storage_directories(): void
{
    $paths = [
        app_storage_root(),
        app_storage_root() . DIRECTORY_SEPARATOR . 'database',
        app_storage_root() . DIRECTORY_SEPARATOR . 'logs',
    ];

    foreach ($paths as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}

function env_file_candidates(): array
{
    $publicRoot = app_public_root();

    return array_values(array_unique([
        $publicRoot . DIRECTORY_SEPARATOR . '.env',
        dirname($publicRoot) . DIRECTORY_SEPARATOR . '.env',
        dirname($publicRoot) . DIRECTORY_SEPARATOR . '.yorvision.env',
        dirname($publicRoot) . DIRECTORY_SEPARATOR . '.env.production',
    ]));
}

function load_env_file(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    foreach (env_file_candidates() as $candidate) {
        if (!is_file($candidate)) {
            continue;
        }

        $lines = file($candidate, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $trimmed, 2);
            $name = trim($name);
            $value = trim($value);

            if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            }

            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }

        break;
    }

    $loaded = true;
}

function env_value(string $key, ?string $default = null): ?string
{
    load_env_file();

    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return $value;
}

function base_url(): string
{
    $configured = env_value('APP_URL');
    if ($configured) {
        return rtrim($configured, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? null) === '443';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host;
}

function json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return $_POST;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function log_event(string $channel, array $context = []): void
{
    ensure_storage_directories();
    $line = json_encode([
        'timestamp' => gmdate('c'),
        'channel' => $channel,
        'context' => $context,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    file_put_contents(app_storage_root() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'payment.log', $line . PHP_EOL, FILE_APPEND);
}

function start_payment_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('yorvision_payment_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

function issue_csrf_token(): string
{
    start_payment_session();
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_expires_at'] = time() + 7200;

    return $token;
}

function verify_same_origin(): bool
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    foreach ([$origin, $referer] as $candidate) {
        if ($candidate === '') {
            continue;
        }

        $candidateHost = parse_url($candidate, PHP_URL_HOST);
        if ($candidateHost && strcasecmp($candidateHost, $host) === 0) {
            return true;
        }
    }

    return $origin === '' && $referer === '';
}

function verify_csrf_request(array $payload): bool
{
    start_payment_session();

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf_token'] ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? null;
    $expiresAt = $_SESSION['csrf_expires_at'] ?? 0;

    if (!$sessionToken || !is_string($token) || $token === '' || time() > (int) $expiresAt) {
        return false;
    }

    if (!hash_equals($sessionToken, $token)) {
        return false;
    }

    return verify_same_origin();
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '63')) {
        return '+' . $digits;
    }

    if (str_starts_with($digits, '0')) {
        return '+63' . substr($digits, 1);
    }

    if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
        return '+63' . $digits;
    }

    return '+' . $digits;
}

function plan_catalog(): array
{
    return [
        'starter' => [
            'label' => '1 Bottle - Starter Package',
            'short' => 'Starter',
            'qty' => 1,
            'regular' => 500,
            'price' => 500,
            'supply' => 'Best for first-time buyers',
        ],
        'popular' => [
            'label' => '2 Bottles - Recommended Value',
            'short' => 'Recommended Value',
            'qty' => 2,
            'regular' => 1000,
            'price' => 960,
            'supply' => 'Best for a consistent routine',
        ],
        'value' => [
            'label' => '3 Bottles - Premium Family Package',
            'short' => 'Premium Family',
            'qty' => 3,
            'regular' => 1500,
            'price' => 1428,
            'supply' => 'Best for families or stocking up',
        ],
    ];
}

function calculate_order_pricing(string $planKey, int $quantity): array
{
    $plans = plan_catalog();
    $plan = $plans[$planKey] ?? $plans['popular'];
    $effectiveQuantity = max(1, $quantity);
    $unitPrice = (int) round($plan['price'] / $plan['qty']);
    $regularUnit = (int) round($plan['regular'] / $plan['qty']);
    $total = (int) round($unitPrice * $effectiveQuantity);
    $regularTotal = (int) round($regularUnit * $effectiveQuantity);
    $discount = max(0, $regularTotal - $total);

    return [
        'plan_key' => $planKey,
        'plan_label' => $plan['label'],
        'plan_short' => $plan['short'],
        'plan_supply' => $plan['supply'],
        'quantity' => $effectiveQuantity,
        'unit_price' => $unitPrice,
        'regular_total' => $regularTotal,
        'subtotal' => $total,
        'discount' => $discount,
        'shipping' => 0,
        'total' => $total,
        'currency' => env_value('XENDIT_CURRENCY', 'PHP') ?? 'PHP',
    ];
}

function generate_order_reference(): string
{
    return sprintf('YOR-%s-%05d', date('Ymd'), random_int(10000, 99999));
}

function redirect_url_with_reference(string $baseUrl, string $orderReference, string $flow): string
{
    $separator = str_contains($baseUrl, '?') ? '&' : '?';
    return $baseUrl . $separator . http_build_query([
        'order_ref' => $orderReference,
        'payment_return' => $flow,
    ]);
}

function split_name(string $fullName): array
{
    $parts = preg_split('/\s+/', trim($fullName)) ?: ['Customer'];
    $given = array_shift($parts) ?: 'Customer';
    $surname = trim(implode(' ', $parts));

    return [$given, $surname];
}

