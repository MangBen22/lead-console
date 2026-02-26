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

if ($action === 'status') {
    out_json([
        'ok' => true,
        'service' => '5N2 App API',
        'phase' => '1.2-bridge-data-foundation',
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
    $approved = 0;
    $bySite = [];
    foreach (all_sites() as $site) {
        $res = app_bridge_request($site, 'POST', 'bridge/push-approved', []);
        $count = 0;
        if (!empty($res['ok']) && isset($res['data']['count'])) {
            $count = (int) $res['data']['count'];
            $approved += $count;
        }
        $bySite[] = [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'approved_leads' => $count,
            'connected' => !empty($res['ok']),
        ];
    }

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
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
    ]);
}

if ($action === 'crm.connectors.save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    $raw = file_get_contents('php://input');
    $data = json_decode((string) $raw, true);
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

    out_json([
        'ok' => true,
        'module' => 'crm_email',
        'status' => 'bootstrap',
        'metrics' => [
            'active_connectors' => count($connectors),
            'smtp_connected' => $smtpConnected,
            'failed_deliveries' => 0,
        ],
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
