<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function app_config()
{
    $defaults = [
        'app_name' => '5N2 App Control Center',
        'environment' => 'development',
        'plugin_sites' => [],
    ];
    $configPath = dirname(__DIR__) . '/config.php';
    if (file_exists($configPath)) {
        $loaded = require $configPath;
        if (is_array($loaded)) {
            return array_merge($defaults, $loaded);
        }
    }
    return $defaults;
}

function app_storage_path($name)
{
    $base = dirname(__DIR__) . '/storage';
    if (!is_dir($base)) {
        @mkdir($base, 0775, true);
    }
    return $base . '/' . $name;
}

function app_read_json_file($path, $default = [])
{
    if (!file_exists($path)) {
        return $default;
    }
    $raw = file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return $default;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

function app_write_json_file($path, $data)
{
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function app_bridge_request($site, $method, $endpoint, $payload = null)
{
    $base = rtrim((string) ($site['base_url'] ?? ''), '/');
    $key = (string) ($site['bridge_key'] ?? '');
    $url = $base . '/wp-json/lc/v1/' . ltrim($endpoint, '/');
    $headers = [
        'Accept: application/json',
        'X-LC-Bridge-Key: ' . $key,
    ];
    $opts = [
        'http' => [
            'method' => strtoupper($method),
            'timeout' => 8,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers),
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ];
    if ($payload !== null) {
        $body = json_encode($payload);
        $opts['http']['header'] .= "\r\nContent-Type: application/json";
        $opts['http']['content'] = $body;
    }

    $ctx = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
            if (preg_match('#HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1];
                break;
            }
        }
    }
    $decoded = is_string($raw) ? json_decode($raw, true) : null;

    return [
        'ok' => $status >= 200 && $status < 300 && is_array($decoded),
        'status' => $status,
        'data' => is_array($decoded) ? $decoded : null,
        'error' => ($status === 0) ? 'Request failed or timed out.' : '',
        'url' => $url,
    ];
}

function app_http_json_request($method, $url, $headers = [], $payload = null, $timeout = 12)
{
    $normalizedHeaders = ['Accept: application/json'];
    foreach ($headers as $header) {
        if (is_string($header) && trim($header) !== '') {
            $normalizedHeaders[] = trim($header);
        }
    }

    $opts = [
        'http' => [
            'method' => strtoupper($method),
            'timeout' => max(1, (int) $timeout),
            'ignore_errors' => true,
            'header' => implode("\r\n", $normalizedHeaders),
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ];

    if ($payload !== null) {
        $opts['http']['header'] .= "\r\nContent-Type: application/json";
        $opts['http']['content'] = json_encode($payload);
    }

    $ctx = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $ctx);
    $status = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
            if (preg_match('#HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1];
                break;
            }
        }
    }

    $decoded = is_string($raw) ? json_decode($raw, true) : null;

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => is_array($decoded) ? $decoded : null,
        'raw' => is_string($raw) ? $raw : '',
        'url' => $url,
    ];
}

function app_current_user()
{
    $user = isset($_SESSION['app_user']) && is_array($_SESSION['app_user']) ? $_SESSION['app_user'] : null;
    return $user;
}

function app_is_authenticated()
{
    return app_current_user() !== null;
}

function app_is_owner()
{
    $user = app_current_user();
    return is_array($user) && (string) ($user['role'] ?? '') === 'owner';
}

function app_json_error($message, $code = 400, $extra = [])
{
    http_response_code($code);
    echo json_encode(array_merge([
        'ok' => false,
        'error' => $message,
    ], $extra));
    exit;
}

function app_require_auth()
{
    if (!app_is_authenticated()) {
        app_json_error('Authentication required.', 401);
    }
}

function app_require_owner()
{
    app_require_auth();
    if (!app_is_owner()) {
        app_json_error('Owner permission required.', 403);
    }
}

function app_read_json_body()
{
    $raw = file_get_contents('php://input');
    $data = json_decode((string) $raw, true);
    return is_array($data) ? $data : null;
}

function app_get_csrf_token()
{
    $token = isset($_SESSION['app_csrf_token']) ? (string) $_SESSION['app_csrf_token'] : '';
    if ($token === '') {
        $token = bin2hex(random_bytes(16));
        $_SESSION['app_csrf_token'] = $token;
    }
    return $token;
}

function app_require_csrf()
{
    $expected = isset($_SESSION['app_csrf_token']) ? (string) $_SESSION['app_csrf_token'] : '';
    $incoming = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? (string) $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    if ($expected === '' || $incoming === '' || !hash_equals($expected, $incoming)) {
        app_json_error('Invalid CSRF token.', 403);
    }
}
