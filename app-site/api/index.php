<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/lib.php';

$config = app_config();
$action = isset($_GET['action']) ? (string) $_GET['action'] : 'status';

function out_json($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function all_sites()
{
    global $config;
    $sites = isset($config['plugin_sites']) && is_array($config['plugin_sites']) ? $config['plugin_sites'] : [];
    return array_values(array_filter($sites, static function ($site) {
        return !empty($site['base_url']) && !empty($site['bridge_key']);
    }));
}

function site_by_id($siteId)
{
    foreach (all_sites() as $site) {
        if ((string) ($site['site_id'] ?? '') === (string) $siteId) {
            return $site;
        }
    }
    return null;
}

function connector_by_id($connectorId)
{
    $rows = app_read_json_file(app_storage_path('crm_connectors.json'), []);
    foreach ($rows as $row) {
        if ((string) ($row['connector_id'] ?? '') === (string) $connectorId) {
            return $row;
        }
    }
    return null;
}

function sanitize_connector_config($config)
{
    if (!is_array($config)) {
        return [];
    }
    $out = [];
    foreach ($config as $key => $value) {
        $cleanKey = preg_replace('/[^a-z0-9_\-]/i', '', (string) $key);
        if ($cleanKey === '') {
            continue;
        }
        if (is_scalar($value) || $value === null) {
            $out[$cleanKey] = (string) $value;
        }
    }
    return $out;
}

function mask_connector($connector)
{
    if (!is_array($connector)) {
        return [];
    }
    $masked = $connector;
    if (isset($masked['config']) && is_array($masked['config'])) {
        foreach (['access_token', 'api_key', 'secret', 'password'] as $secretKey) {
            if (!empty($masked['config'][$secretKey])) {
                $masked['config'][$secretKey] = '********';
            }
        }
    }
    return $masked;
}

function collect_approved_leads()
{
    $all = [];
    $siteSummaries = [];
    foreach (all_sites() as $site) {
        $res = app_bridge_request($site, 'POST', 'bridge/push-approved', []);
        $rows = [];
        if (!empty($res['ok']) && isset($res['data']['leads']) && is_array($res['data']['leads'])) {
            $rows = $res['data']['leads'];
        }
        $mapped = [];
        foreach ($rows as $lead) {
            if (!is_array($lead)) {
                continue;
            }
            $mapped[] = [
                'site_id' => (string) ($site['site_id'] ?? ''),
                'site_label' => (string) ($site['label'] ?? $site['base_url']),
                'lead_id' => (int) ($lead['id'] ?? 0),
                'business_name' => (string) ($lead['business_name'] ?? ''),
                'city' => (string) ($lead['city'] ?? ''),
                'category' => (string) ($lead['category'] ?? ''),
                'website' => (string) ($lead['website'] ?? ''),
                'phone' => (string) ($lead['phone'] ?? ''),
                'email' => (string) ($lead['email'] ?? ''),
                'status' => (string) ($lead['status'] ?? ''),
                'created_at' => (string) ($lead['created_at'] ?? ''),
            ];
        }
        $all = array_merge($all, $mapped);
        $siteSummaries[] = [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'connected' => !empty($res['ok']),
            'approved_count' => count($mapped),
        ];
    }

    return [
        'leads' => $all,
        'sites' => $siteSummaries,
    ];
}

function normalize_error_code($message)
{
    $m = strtolower((string) $message);
    if (strpos($m, 'missing access_token') !== false) {
        return 'missing_access_token';
    }
    if (strpos($m, 'missing webhook_url') !== false) {
        return 'missing_webhook_url';
    }
    if (strpos($m, 'missing valid bridge_site_id') !== false) {
        return 'missing_bridge_site';
    }
    if (strpos($m, 'http 401') !== false || strpos($m, 'http 403') !== false) {
        return 'auth_failed';
    }
    if (strpos($m, 'http 429') !== false) {
        return 'rate_limited';
    }
    if (strpos($m, 'http 5') !== false) {
        return 'provider_unavailable';
    }
    if (strpos($m, 'request failed') !== false || strpos($m, 'timed out') !== false) {
        return 'network_error';
    }
    if (strpos($m, 'no provider adapter') !== false) {
        return 'adapter_not_implemented';
    }
    return 'unknown_error';
}

function retry_queue_path()
{
    return app_storage_path('crm_retry_queue.json');
}

function enqueue_retry_item($item)
{
    $queue = app_read_json_file(retry_queue_path(), []);
    array_unshift($queue, $item);
    $queue = array_slice($queue, 0, 500);
    app_write_json_file(retry_queue_path(), $queue);
}

function execute_connector_sync($connector, $leads)
{
    $provider = strtolower((string) ($connector['provider'] ?? 'custom'));
    $type = strtolower((string) ($connector['type'] ?? 'external_api'));
    $config = isset($connector['config']) && is_array($connector['config']) ? $connector['config'] : [];
    $runMode = strtolower((string) ($config['run_mode'] ?? 'dry_run'));
    $result = [
        'connector_id' => (string) ($connector['connector_id'] ?? ''),
        'provider' => $provider,
        'type' => $type,
        'run_mode' => $runMode,
        'accepted' => 0,
        'rejected' => 0,
        'errors' => [],
        'error_codes' => [],
    ];

    if ($provider === 'hubspot' && $type === 'external_api') {
        $token = trim((string) ($config['access_token'] ?? ''));
        $endpoint = trim((string) ($config['endpoint_url'] ?? 'https://api.hubapi.com/crm/v3/objects/contacts'));
        if ($token === '') {
            $result['errors'][] = 'Missing access_token for HubSpot connector.';
            $result['error_codes'][] = normalize_error_code('Missing access_token for HubSpot connector.');
            $result['rejected'] = count($leads);
            return $result;
        }
        foreach ($leads as $lead) {
            if (!is_array($lead)) {
                continue;
            }
            if ($runMode !== 'live') {
                $result['accepted']++;
                continue;
            }
            $payload = [
                'properties' => [
                    'email' => (string) ($lead['email'] ?? ''),
                    'company' => (string) ($lead['business_name'] ?? ''),
                    'phone' => (string) ($lead['phone'] ?? ''),
                    'website' => (string) ($lead['website'] ?? ''),
                    'city' => (string) ($lead['city'] ?? ''),
                ],
            ];
            $res = app_http_json_request('POST', $endpoint, [
                'Authorization: Bearer ' . $token,
            ], $payload, 15);
            if (!empty($res['ok'])) {
                $result['accepted']++;
            } else {
                $result['rejected']++;
                $msg = 'HubSpot HTTP ' . (int) ($res['status'] ?? 0);
                $result['errors'][] = $msg;
                $result['error_codes'][] = normalize_error_code($msg);
            }
        }
        return $result;
    }

    if ($provider === 'fluentcrm' && $type === 'wordpress_plugin') {
        $targetSiteId = (string) ($config['bridge_site_id'] ?? ($connector['site_id'] ?? ''));
        $site = $targetSiteId !== '' ? site_by_id($targetSiteId) : null;
        if ($site === null) {
            $result['errors'][] = 'FluentCRM connector missing valid bridge_site_id.';
            $result['error_codes'][] = normalize_error_code('FluentCRM connector missing valid bridge_site_id.');
            $result['rejected'] = count($leads);
            return $result;
        }
        if ($runMode !== 'live') {
            $result['accepted'] = count($leads);
            return $result;
        }
        $res = app_bridge_request($site, 'POST', 'bridge/crm-intake', [
            'provider' => 'fluentcrm',
            'leads' => $leads,
            'connector_id' => (string) ($connector['connector_id'] ?? ''),
        ]);
        if (!empty($res['ok']) && isset($res['data']['accepted'])) {
            $result['accepted'] = (int) $res['data']['accepted'];
            $result['rejected'] = max(0, count($leads) - $result['accepted']);
        } else {
            $result['rejected'] = count($leads);
            $msg = 'FluentCRM bridge intake request failed.';
            $result['errors'][] = $msg;
            $result['error_codes'][] = normalize_error_code($msg);
        }
        return $result;
    }

    if ($provider === 'custom_webhook' && $type === 'external_api') {
        $webhook = trim((string) ($config['webhook_url'] ?? ''));
        if ($webhook === '') {
            $result['errors'][] = 'Missing webhook_url for custom_webhook connector.';
            $result['error_codes'][] = normalize_error_code('Missing webhook_url for custom_webhook connector.');
            $result['rejected'] = count($leads);
            return $result;
        }
        if ($runMode !== 'live') {
            $result['accepted'] = count($leads);
            return $result;
        }
        $res = app_http_json_request('POST', $webhook, [], [
            'connector_id' => (string) ($connector['connector_id'] ?? ''),
            'leads' => $leads,
        ], 15);
        if (!empty($res['ok'])) {
            $result['accepted'] = count($leads);
        } else {
            $result['rejected'] = count($leads);
            $msg = 'Webhook HTTP ' . (int) ($res['status'] ?? 0);
            $result['errors'][] = $msg;
            $result['error_codes'][] = normalize_error_code($msg);
        }
        return $result;
    }

    $result['accepted'] = count($leads);
    $msg = 'No provider adapter; treated as dry mapping only.';
    $result['errors'][] = $msg;
    $result['error_codes'][] = normalize_error_code($msg);
    return $result;
}

$publicActions = ['status'];
if (!in_array($action, $publicActions, true)) {
    app_require_auth();
}

if ($action === 'status') {
    out_json([
        'ok' => true,
        'service' => '5N2 App API',
        'phase' => '1.4-connector-test-retry',
        'modules' => [
            'leads' => 'active',
            'crm_email' => 'bootstrap',
            'social_forums' => 'bootstrap',
            'webops_security' => 'bootstrap',
            'seo_suite' => 'bootstrap',
        ],
        'sites_configured' => count(all_sites()),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'notifications') {
    out_json([
        'ok' => true,
        'items' => [
            ['type' => 'info', 'message' => 'Main platform API is reachable.'],
            ['type' => 'info', 'message' => 'Bridge-aware mode enabled.'],
        ],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'bridge.sites') {
    $rows = [];
    foreach (all_sites() as $site) {
        $res = app_bridge_request($site, 'GET', 'bridge/status');
        $rows[] = [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'base_url' => (string) ($site['base_url'] ?? ''),
            'connected' => !empty($res['ok']),
            'http_status' => (int) ($res['status'] ?? 0),
            'bridge_data' => $res['data'],
            'error' => (string) ($res['error'] ?? ''),
        ];
    }
    out_json([
        'ok' => true,
        'count' => count($rows),
        'sites' => $rows,
    ]);
}

if ($action === 'leads.summary') {
    $queuedRuns = 0;
    $payload = collect_approved_leads();
    $approved = count($payload['leads']);
    $bySite = $payload['sites'];

    out_json([
        'ok' => true,
        'module' => 'leads',
        'status' => 'active',
        'metrics' => [
            'queued_runs' => $queuedRuns,
            'approved_leads' => $approved,
            'pending_review' => 0,
        ],
        'sites' => $bySite,
    ]);
}

if ($action === 'crm.connectors.list') {
    $path = app_storage_path('crm_connectors.json');
    $rows = app_read_json_file($path, []);
    $publicRows = array_map('mask_connector', $rows);
    out_json([
        'ok' => true,
        'count' => count($publicRows),
        'items' => $publicRows,
    ]);
}

if ($action === 'crm.connectors.save') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $item = [
        'connector_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['connector_id'] ?? uniqid('connector_', false))),
        'provider' => strtolower(trim((string) ($data['provider'] ?? 'custom'))),
        'type' => strtolower(trim((string) ($data['type'] ?? 'external_api'))),
        'status' => strtolower(trim((string) ($data['status'] ?? 'planned'))),
        'auth_mode' => strtolower(trim((string) ($data['auth_mode'] ?? 'api_key'))),
        'capabilities' => isset($data['capabilities']) && is_array($data['capabilities']) ? array_values($data['capabilities']) : [],
        'site_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['site_id'] ?? '')),
        'config' => sanitize_connector_config($data['config'] ?? []),
        'updated_at' => gmdate('c'),
    ];
    $path = app_storage_path('crm_connectors.json');
    $rows = app_read_json_file($path, []);
    $rows = array_values(array_filter($rows, static function ($row) use ($item) {
        return (string) ($row['connector_id'] ?? '') !== $item['connector_id'];
    }));
    $rows[] = $item;
    app_write_json_file($path, $rows);
    out_json(['ok' => true, 'item' => $item]);
}

if ($action === 'crm.connectors.delete') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['connector_id'])) {
        out_json(['ok' => false, 'error' => 'connector_id is required.'], 400);
    }
    $connectorId = (string) $data['connector_id'];
    $path = app_storage_path('crm_connectors.json');
    $rows = app_read_json_file($path, []);
    $before = count($rows);
    $rows = array_values(array_filter($rows, static function ($row) use ($connectorId) {
        return (string) ($row['connector_id'] ?? '') !== $connectorId;
    }));
    app_write_json_file($path, $rows);
    out_json([
        'ok' => true,
        'deleted' => $before - count($rows),
        'connector_id' => $connectorId,
    ]);
}

if ($action === 'crm.connectors.test') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }

    $connector = null;
    if (!empty($data['connector_id'])) {
        $connector = connector_by_id((string) $data['connector_id']);
    }
    if (!is_array($connector)) {
        out_json(['ok' => false, 'error' => 'Connector not found.'], 404);
    }

    $sampleLead = [[
        'site_id' => 'test',
        'site_label' => 'test',
        'lead_id' => 0,
        'business_name' => 'Sample Lead Co',
        'city' => 'Sample City',
        'category' => 'Sample Category',
        'website' => 'https://example.com',
        'phone' => '0000000000',
        'email' => 'sample@example.com',
        'status' => 'Ready',
        'created_at' => gmdate('c'),
    ]];
    $result = execute_connector_sync($connector, $sampleLead);
    out_json([
        'ok' => true,
        'connector_id' => (string) ($connector['connector_id'] ?? ''),
        'result' => $result,
    ]);
}

if ($action === 'crm.push.sync') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();

    $connectors = app_read_json_file(app_storage_path('crm_connectors.json'), []);
    $activeConnectors = array_values(array_filter($connectors, static function ($row) {
        $status = strtolower((string) ($row['status'] ?? ''));
        return in_array($status, ['active', 'enabled'], true);
    }));

    if (empty($activeConnectors)) {
        out_json(['ok' => false, 'error' => 'No active CRM connectors.'], 400);
    }

    $payload = collect_approved_leads();
    $sitePayloads = $payload['sites'];
    $leads = $payload['leads'];
    $totalApproved = count($leads);
    $connectorResults = [];
    foreach ($activeConnectors as $connector) {
        $res = execute_connector_sync($connector, $leads);
        $connectorResults[] = $res;
        if ((int) ($res['rejected'] ?? 0) > 0 || !empty($res['errors'])) {
            enqueue_retry_item([
                'retry_id' => 'retry_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
                'created_at' => gmdate('c'),
                'connector_id' => (string) ($res['connector_id'] ?? ''),
                'provider' => (string) ($res['provider'] ?? ''),
                'type' => (string) ($res['type'] ?? ''),
                'error_codes' => isset($res['error_codes']) && is_array($res['error_codes']) ? array_values(array_unique($res['error_codes'])) : [],
                'errors' => isset($res['errors']) && is_array($res['errors']) ? $res['errors'] : [],
                'lead_count' => count($leads),
                'attempts' => 0,
                'status' => 'queued',
            ]);
        }
    }

    $syncId = 'sync_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6);
    $logItem = [
        'sync_id' => $syncId,
        'created_at' => gmdate('c'),
        'connector_count' => count($activeConnectors),
        'site_count' => count($sitePayloads),
        'approved_total' => $totalApproved,
        'connector_results' => $connectorResults,
        'connectors' => array_map(static function ($row) {
            return [
                'connector_id' => (string) ($row['connector_id'] ?? ''),
                'provider' => (string) ($row['provider'] ?? ''),
                'type' => (string) ($row['type'] ?? ''),
            ];
        }, $activeConnectors),
        'sites' => $sitePayloads,
        'status' => 'queued_to_connectors',
    ];

    $logPath = app_storage_path('crm_sync_log.json');
    $logs = app_read_json_file($logPath, []);
    array_unshift($logs, $logItem);
    $logs = array_slice($logs, 0, 100);
    app_write_json_file($logPath, $logs);

    out_json([
        'ok' => true,
        'sync' => $logItem,
        'message' => 'CRM push sync queued to active connectors.',
    ]);
}

if ($action === 'crm.push.log') {
    $logs = app_read_json_file(app_storage_path('crm_sync_log.json'), []);
    out_json([
        'ok' => true,
        'count' => count($logs),
        'items' => $logs,
    ]);
}

if ($action === 'crm.retry.list') {
    $queue = app_read_json_file(retry_queue_path(), []);
    out_json([
        'ok' => true,
        'count' => count($queue),
        'items' => $queue,
    ]);
}

if ($action === 'crm.retry.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();

    $queue = app_read_json_file(retry_queue_path(), []);
    if (empty($queue)) {
        out_json(['ok' => true, 'message' => 'Retry queue is empty.', 'processed' => 0]);
    }

    $payload = collect_approved_leads();
    $leads = $payload['leads'];
    $processed = 0;
    $succeeded = 0;
    $remaining = [];
    foreach ($queue as $item) {
        $processed++;
        $connector = connector_by_id((string) ($item['connector_id'] ?? ''));
        if (!is_array($connector)) {
            $item['status'] = 'failed_missing_connector';
            $item['attempts'] = (int) ($item['attempts'] ?? 0) + 1;
            $remaining[] = $item;
            continue;
        }
        $res = execute_connector_sync($connector, $leads);
        if ((int) ($res['rejected'] ?? 0) === 0 && empty($res['errors'])) {
            $succeeded++;
            continue;
        }
        $item['status'] = 'queued';
        $item['attempts'] = (int) ($item['attempts'] ?? 0) + 1;
        $item['errors'] = isset($res['errors']) && is_array($res['errors']) ? $res['errors'] : [];
        $item['error_codes'] = isset($res['error_codes']) && is_array($res['error_codes']) ? array_values(array_unique($res['error_codes'])) : [];
        $remaining[] = $item;
    }
    app_write_json_file(retry_queue_path(), $remaining);
    out_json([
        'ok' => true,
        'processed' => $processed,
        'succeeded' => $succeeded,
        'remaining' => count($remaining),
    ]);
}

if ($action === 'crm.summary') {
    $connectors = app_read_json_file(app_storage_path('crm_connectors.json'), []);
    $smtpConnected = false;
    $smtpBySite = [];
    foreach (all_sites() as $site) {
        $res = app_bridge_request($site, 'GET', 'bridge/smtp-health');
        $connected = false;
        if (!empty($res['ok']) && is_array($res['data']) && isset($res['data']['smtp_health']['connected'])) {
            $connected = !empty($res['data']['smtp_health']['connected']);
            if ($connected) {
                $smtpConnected = true;
            }
        }
        $smtpBySite[] = [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'connected' => $connected,
        ];
    }

    $logs = app_read_json_file(app_storage_path('crm_sync_log.json'), []);
    $lastSync = isset($logs[0]) && is_array($logs[0]) ? $logs[0] : null;

    out_json([
        'ok' => true,
        'module' => 'crm_email',
        'status' => 'bootstrap',
        'metrics' => [
            'active_connectors' => count($connectors),
            'smtp_connected' => $smtpConnected,
            'failed_deliveries' => count(app_read_json_file(retry_queue_path(), [])),
        ],
        'last_sync' => $lastSync,
        'smtp_sites' => $smtpBySite,
    ]);
}

if ($action === 'social.summary') {
    out_json([
        'ok' => true,
        'module' => 'social_forums',
        'status' => 'bootstrap',
        'metrics' => [
            'connected_accounts' => 0,
            'scheduled_posts' => 0,
            'unread_conversations' => 0,
        ],
    ]);
}

if ($action === 'webops.summary') {
    out_json([
        'ok' => true,
        'module' => 'webops_security',
        'status' => 'bootstrap',
        'metrics' => [
            'sites_monitored' => count(all_sites()),
            'active_incidents' => 0,
            'uptime_percent' => 100,
        ],
    ]);
}

if ($action === 'seo.summary') {
    out_json([
        'ok' => true,
        'module' => 'seo_suite',
        'status' => 'bootstrap',
        'metrics' => [
            'audits_completed' => 0,
            'critical_issues' => 0,
            'tracked_projects' => 0,
        ],
    ]);
}

out_json([
    'ok' => false,
    'error' => 'Unknown action.',
    'action' => $action,
], 404);
