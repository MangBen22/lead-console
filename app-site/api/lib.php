<?php

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
