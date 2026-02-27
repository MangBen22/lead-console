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

function app_clean_text($value)
{
    return trim(strip_tags((string) $value));
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

function social_connectors_path()
{
    return app_storage_path('social_connectors.json');
}

function social_sync_log_path()
{
    return app_storage_path('social_sync_log.json');
}

function social_retry_queue_path()
{
    return app_storage_path('social_retry_queue.json');
}

function webops_monitors_path()
{
    return app_storage_path('webops_monitors.json');
}

function webops_log_path()
{
    return app_storage_path('webops_log.json');
}

function webops_retry_queue_path()
{
    return app_storage_path('webops_retry_queue.json');
}

function seo_projects_path()
{
    return app_storage_path('seo_projects.json');
}

function seo_audits_path()
{
    return app_storage_path('seo_audits.json');
}

function seo_extension_events_path()
{
    return app_storage_path('seo_extension_events.json');
}

function seo_project_by_id($projectId)
{
    $rows = app_read_json_file(seo_projects_path(), []);
    foreach ($rows as $row) {
        if ((string) ($row['project_id'] ?? '') === (string) $projectId) {
            return $row;
        }
    }
    return null;
}

function run_seo_audit_for_project($project)
{
    $domain = trim((string) ($project['domain'] ?? ''));
    $base = $domain;
    if ($base !== '' && stripos($base, 'http://') !== 0 && stripos($base, 'https://') !== 0) {
        $base = 'https://' . $base;
    }
    $checks = [];
    $critical = 0;
    $warnings = 0;
    $score = 100;

    if ($base === '') {
        $checks[] = ['check' => 'target_url', 'status' => 'fail', 'severity' => 'critical', 'message' => 'Project domain is missing.'];
        $critical++;
        $score -= 60;
    } else {
        $checks[] = ['check' => 'target_url', 'status' => 'pass', 'severity' => 'info', 'message' => 'Target URL prepared.'];

        if (stripos($base, 'https://') === 0) {
            $checks[] = ['check' => 'https', 'status' => 'pass', 'severity' => 'info', 'message' => 'HTTPS detected.'];
        } else {
            $checks[] = ['check' => 'https', 'status' => 'warn', 'severity' => 'warning', 'message' => 'HTTPS not detected.'];
            $warnings++;
            $score -= 20;
        }

        $homeRes = app_http_json_request('GET', $base, [], null, 12);
        $homeStatus = (int) ($homeRes['status'] ?? 0);
        if ($homeStatus >= 200 && $homeStatus < 400) {
            $checks[] = ['check' => 'home_reachable', 'status' => 'pass', 'severity' => 'info', 'message' => 'Homepage reachable (' . $homeStatus . ').'];
        } else {
            $checks[] = ['check' => 'home_reachable', 'status' => 'fail', 'severity' => 'critical', 'message' => 'Homepage unreachable (' . $homeStatus . ').'];
            $critical++;
            $score -= 40;
        }

        $robotsRes = app_http_json_request('GET', rtrim($base, '/') . '/robots.txt', [], null, 10);
        $robotsStatus = (int) ($robotsRes['status'] ?? 0);
        if ($robotsStatus >= 200 && $robotsStatus < 400) {
            $checks[] = ['check' => 'robots_txt', 'status' => 'pass', 'severity' => 'info', 'message' => 'robots.txt found.'];
        } else {
            $checks[] = ['check' => 'robots_txt', 'status' => 'warn', 'severity' => 'warning', 'message' => 'robots.txt missing or inaccessible (' . $robotsStatus . ').'];
            $warnings++;
            $score -= 10;
        }

        $sitemapRes = app_http_json_request('GET', rtrim($base, '/') . '/sitemap.xml', [], null, 10);
        $sitemapStatus = (int) ($sitemapRes['status'] ?? 0);
        if ($sitemapStatus >= 200 && $sitemapStatus < 400) {
            $checks[] = ['check' => 'sitemap_xml', 'status' => 'pass', 'severity' => 'info', 'message' => 'sitemap.xml found.'];
        } else {
            $checks[] = ['check' => 'sitemap_xml', 'status' => 'warn', 'severity' => 'warning', 'message' => 'sitemap.xml missing or inaccessible (' . $sitemapStatus . ').'];
            $warnings++;
            $score -= 10;
        }
    }

    $score = max(0, min(100, $score));
    return [
        'audit_id' => 'seo_audit_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'project_id' => (string) ($project['project_id'] ?? ''),
        'project_name' => (string) ($project['name'] ?? ''),
        'domain' => (string) ($project['domain'] ?? ''),
        'score' => $score,
        'critical_issues' => $critical,
        'warnings' => $warnings,
        'checks' => $checks,
        'source' => 'internal_runner',
        'created_at' => gmdate('c'),
    ];
}

function webops_monitor_by_id($monitorId)
{
    $rows = app_read_json_file(webops_monitors_path(), []);
    foreach ($rows as $row) {
        if ((string) ($row['monitor_id'] ?? '') === (string) $monitorId) {
            return $row;
        }
    }
    return null;
}

function enqueue_webops_retry_item($item)
{
    $queue = app_read_json_file(webops_retry_queue_path(), []);
    array_unshift($queue, $item);
    $queue = array_slice($queue, 0, 500);
    app_write_json_file(webops_retry_queue_path(), $queue);
}

function execute_webops_monitor($monitor)
{
    $type = strtolower((string) ($monitor['type'] ?? 'uptime_http'));
    $target = trim((string) ($monitor['target'] ?? ''));
    $config = isset($monitor['config']) && is_array($monitor['config']) ? $monitor['config'] : [];
    $runMode = strtolower((string) ($config['run_mode'] ?? 'live'));
    $result = [
        'monitor_id' => (string) ($monitor['monitor_id'] ?? ''),
        'type' => $type,
        'target' => $target,
        'run_mode' => $runMode,
        'ok' => false,
        'severity' => 'warning',
        'message' => '',
        'error_code' => '',
        'details' => [],
    ];

    if ($type === 'uptime_http') {
        if ($target === '') {
            $result['message'] = 'Missing target URL.';
            $result['error_code'] = 'missing_target_url';
            return $result;
        }
        $res = app_http_json_request('GET', $target, [], null, 10);
        $status = (int) ($res['status'] ?? 0);
        $result['details'] = ['http_status' => $status, 'url' => $target];
        if ($status >= 200 && $status < 400) {
            $result['ok'] = true;
            $result['severity'] = 'info';
            $result['message'] = 'Site is reachable.';
        } else {
            $result['ok'] = false;
            $result['severity'] = 'critical';
            $result['message'] = 'Site check failed with HTTP ' . $status . '.';
            $result['error_code'] = normalize_error_code('HTTP ' . $status);
        }
        return $result;
    }

    if ($type === 'bridge_site_health') {
        $siteId = (string) ($config['bridge_site_id'] ?? '');
        $site = $siteId !== '' ? site_by_id($siteId) : null;
        if ($site === null) {
            $result['message'] = 'Missing valid bridge_site_id.';
            $result['error_code'] = 'missing_bridge_site';
            return $result;
        }
        if ($runMode !== 'live') {
            $result['ok'] = true;
            $result['severity'] = 'info';
            $result['message'] = 'Dry run ok.';
            return $result;
        }
        $res = app_bridge_request($site, 'GET', 'bridge/site-health');
        if (!empty($res['ok']) && is_array($res['data'])) {
            $result['ok'] = true;
            $result['severity'] = 'info';
            $result['message'] = 'Bridge site health retrieved.';
            $result['details'] = $res['data'];
        } else {
            $result['ok'] = false;
            $result['severity'] = 'critical';
            $result['message'] = 'Bridge site health request failed.';
            $result['error_code'] = 'bridge_health_failed';
            $result['details'] = ['status' => (int) ($res['status'] ?? 0)];
        }
        return $result;
    }

    if ($type === 'webhook_check') {
        $webhook = trim((string) ($config['webhook_url'] ?? ''));
        if ($webhook === '') {
            $result['message'] = 'Missing webhook_url.';
            $result['error_code'] = 'missing_webhook_url';
            return $result;
        }
        if ($runMode !== 'live') {
            $result['ok'] = true;
            $result['severity'] = 'info';
            $result['message'] = 'Dry run ok.';
            return $result;
        }
        $res = app_http_json_request('POST', $webhook, [], ['event' => 'webops_check_ping'], 10);
        if (!empty($res['ok'])) {
            $result['ok'] = true;
            $result['severity'] = 'info';
            $result['message'] = 'Webhook check succeeded.';
        } else {
            $result['ok'] = false;
            $result['severity'] = 'critical';
            $result['message'] = 'Webhook check failed with HTTP ' . (int) ($res['status'] ?? 0);
            $result['error_code'] = normalize_error_code($result['message']);
        }
        return $result;
    }

    $result['message'] = 'Unknown monitor type.';
    $result['error_code'] = 'unknown_monitor_type';
    return $result;
}

function social_connector_by_id($connectorId)
{
    $rows = app_read_json_file(social_connectors_path(), []);
    foreach ($rows as $row) {
        if ((string) ($row['connector_id'] ?? '') === (string) $connectorId) {
            return $row;
        }
    }
    return null;
}

function enqueue_social_retry_item($item)
{
    $queue = app_read_json_file(social_retry_queue_path(), []);
    array_unshift($queue, $item);
    $queue = array_slice($queue, 0, 500);
    app_write_json_file(social_retry_queue_path(), $queue);
}

function collect_social_drafts()
{
    $payload = collect_approved_leads();
    $leads = isset($payload['leads']) && is_array($payload['leads']) ? $payload['leads'] : [];
    $drafts = [];
    foreach (array_slice($leads, 0, 30) as $lead) {
        if (!is_array($lead)) {
            continue;
        }
        $business = trim((string) ($lead['business_name'] ?? ''));
        if ($business === '') {
            continue;
        }
        $city = trim((string) ($lead['city'] ?? ''));
        $category = trim((string) ($lead['category'] ?? ''));
        $drafts[] = [
            'source_site_id' => (string) ($lead['site_id'] ?? ''),
            'lead_id' => (int) ($lead['lead_id'] ?? 0),
            'title' => $business,
            'message' => 'Spotlight: ' . $business . ($city !== '' ? ' in ' . $city : '') . ($category !== '' ? ' (' . $category . ')' : ''),
            'url' => (string) ($lead['website'] ?? ''),
        ];
    }
    return $drafts;
}

function execute_social_connector_sync($connector, $drafts)
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

    if ($provider === 'wordpress_social_bridge' && $type === 'wordpress_plugin') {
        $targetSiteId = (string) ($config['bridge_site_id'] ?? ($connector['site_id'] ?? ''));
        $site = $targetSiteId !== '' ? site_by_id($targetSiteId) : null;
        if ($site === null) {
            $msg = 'Social connector missing valid bridge_site_id.';
            $result['errors'][] = $msg;
            $result['error_codes'][] = normalize_error_code($msg);
            $result['rejected'] = count($drafts);
            return $result;
        }
        if ($runMode !== 'live') {
            $result['accepted'] = count($drafts);
            return $result;
        }
        $res = app_bridge_request($site, 'POST', 'bridge/social-intake', [
            'provider' => $provider,
            'connector_id' => (string) ($connector['connector_id'] ?? ''),
            'drafts' => $drafts,
        ]);
        if (!empty($res['ok']) && isset($res['data']['accepted'])) {
            $result['accepted'] = (int) $res['data']['accepted'];
            $result['rejected'] = max(0, count($drafts) - $result['accepted']);
        } else {
            $msg = 'Social bridge intake request failed.';
            $result['errors'][] = $msg;
            $result['error_codes'][] = normalize_error_code($msg);
            $result['rejected'] = count($drafts);
        }
        return $result;
    }

    if ($provider === 'social_webhook' && $type === 'external_api') {
        $webhook = trim((string) ($config['webhook_url'] ?? ''));
        if ($webhook === '') {
            $msg = 'Missing webhook_url for social_webhook connector.';
            $result['errors'][] = $msg;
            $result['error_codes'][] = normalize_error_code($msg);
            $result['rejected'] = count($drafts);
            return $result;
        }
        if ($runMode !== 'live') {
            $result['accepted'] = count($drafts);
            return $result;
        }
        $res = app_http_json_request('POST', $webhook, [], [
            'connector_id' => (string) ($connector['connector_id'] ?? ''),
            'drafts' => $drafts,
        ], 15);
        if (!empty($res['ok'])) {
            $result['accepted'] = count($drafts);
        } else {
            $msg = 'Social webhook HTTP ' . (int) ($res['status'] ?? 0);
            $result['errors'][] = $msg;
            $result['error_codes'][] = normalize_error_code($msg);
            $result['rejected'] = count($drafts);
        }
        return $result;
    }

    $result['accepted'] = count($drafts);
    $msg = 'No social adapter implementation; treated as dry mapping only.';
    $result['errors'][] = $msg;
    $result['error_codes'][] = normalize_error_code($msg);
    return $result;
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

$publicActions = ['status', 'seo.extension.intake'];
if (!in_array($action, $publicActions, true)) {
    app_require_auth();
}

if ($action === 'status') {
    out_json([
        'ok' => true,
        'service' => '5N2 App API',
        'phase' => '1.5-webops-monitoring-foundation',
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
    $connectors = app_read_json_file(social_connectors_path(), []);
    $active = array_values(array_filter($connectors, static function ($row) {
        $status = strtolower((string) ($row['status'] ?? ''));
        return in_array($status, ['active', 'enabled'], true);
    }));
    $logs = app_read_json_file(social_sync_log_path(), []);
    $lastSync = isset($logs[0]) && is_array($logs[0]) ? $logs[0] : null;
    $retryCount = count(app_read_json_file(social_retry_queue_path(), []));
    out_json([
        'ok' => true,
        'module' => 'social_forums',
        'status' => 'bootstrap',
        'metrics' => [
            'connected_accounts' => count($active),
            'scheduled_posts' => 0,
            'unread_conversations' => $retryCount,
        ],
        'last_sync' => $lastSync,
    ]);
}

if ($action === 'webops.summary') {
    $monitors = app_read_json_file(webops_monitors_path(), []);
    $active = array_values(array_filter($monitors, static function ($row) {
        $status = strtolower((string) ($row['status'] ?? ''));
        return in_array($status, ['active', 'enabled'], true);
    }));
    $logs = app_read_json_file(webops_log_path(), []);
    $lastRun = isset($logs[0]) && is_array($logs[0]) ? $logs[0] : null;
    $retryCount = count(app_read_json_file(webops_retry_queue_path(), []));
    $critical = 0;
    if (is_array($lastRun) && isset($lastRun['results']) && is_array($lastRun['results'])) {
        foreach ($lastRun['results'] as $row) {
            if (is_array($row) && (($row['severity'] ?? '') === 'critical')) {
                $critical++;
            }
        }
    }
    out_json([
        'ok' => true,
        'module' => 'webops_security',
        'status' => 'bootstrap',
        'metrics' => [
            'sites_monitored' => count($active),
            'active_incidents' => $critical + $retryCount,
            'uptime_percent' => 100,
        ],
        'last_run' => $lastRun,
    ]);
}

if ($action === 'seo.summary') {
    $projects = app_read_json_file(seo_projects_path(), []);
    $audits = app_read_json_file(seo_audits_path(), []);
    $lastAudit = isset($audits[0]) && is_array($audits[0]) ? $audits[0] : null;
    $criticalIssues = 0;
    if (is_array($lastAudit)) {
        $criticalIssues = (int) ($lastAudit['critical_issues'] ?? 0);
    }
    out_json([
        'ok' => true,
        'module' => 'seo_suite',
        'status' => 'bootstrap',
        'metrics' => [
            'audits_completed' => count($audits),
            'critical_issues' => $criticalIssues,
            'tracked_projects' => count($projects),
        ],
        'last_audit' => $lastAudit,
    ]);
}

if ($action === 'seo.projects.list') {
    $rows = app_read_json_file(seo_projects_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
    ]);
}

if ($action === 'seo.projects.save') {
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
        'project_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['project_id'] ?? uniqid('seo_project_', false))),
        'name' => trim((string) ($data['name'] ?? '')),
        'domain' => trim((string) ($data['domain'] ?? '')),
        'status' => strtolower(trim((string) ($data['status'] ?? 'active'))),
        'updated_at' => gmdate('c'),
    ];
    $rows = app_read_json_file(seo_projects_path(), []);
    $rows = array_values(array_filter($rows, static function ($row) use ($item) {
        return (string) ($row['project_id'] ?? '') !== $item['project_id'];
    }));
    $rows[] = $item;
    app_write_json_file(seo_projects_path(), $rows);
    out_json(['ok' => true, 'item' => $item]);
}

if ($action === 'seo.projects.delete') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['project_id'])) {
        out_json(['ok' => false, 'error' => 'project_id is required.'], 400);
    }
    $projectId = (string) $data['project_id'];
    $rows = app_read_json_file(seo_projects_path(), []);
    $before = count($rows);
    $rows = array_values(array_filter($rows, static function ($row) use ($projectId) {
        return (string) ($row['project_id'] ?? '') !== $projectId;
    }));
    app_write_json_file(seo_projects_path(), $rows);
    out_json([
        'ok' => true,
        'deleted' => $before - count($rows),
        'project_id' => $projectId,
    ]);
}

if ($action === 'seo.audits.list') {
    $rows = app_read_json_file(seo_audits_path(), []);
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    if ($projectId !== '') {
        $rows = array_values(array_filter($rows, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
    }
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
    ]);
}

if ($action === 'seo.audit.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['project_id'])) {
        out_json(['ok' => false, 'error' => 'project_id is required.'], 400);
    }
    $project = seo_project_by_id((string) $data['project_id']);
    if (!is_array($project)) {
        out_json(['ok' => false, 'error' => 'Project not found.'], 404);
    }
    $audit = run_seo_audit_for_project($project);
    $rows = app_read_json_file(seo_audits_path(), []);
    array_unshift($rows, $audit);
    $rows = array_slice($rows, 0, 500);
    app_write_json_file(seo_audits_path(), $rows);
    out_json([
        'ok' => true,
        'audit' => $audit,
    ]);
}

if ($action === 'seo.extension.intake') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    $expected = (string) ($config['seo_extension_ingest_key'] ?? '');
    $incoming = isset($_SERVER['HTTP_X_SEO_EXTENSION_KEY']) ? (string) $_SERVER['HTTP_X_SEO_EXTENSION_KEY'] : '';
    if ($expected === '' || $incoming === '' || !hash_equals($expected, $incoming)) {
        out_json(['ok' => false, 'error' => 'Invalid extension key.'], 403);
    }
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $event = [
        'event_id' => 'seo_ext_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'project_id' => app_clean_text((string) ($data['project_id'] ?? '')),
        'url' => app_clean_text((string) ($data['url'] ?? '')),
        'title' => app_clean_text((string) ($data['title'] ?? '')),
        'issues' => isset($data['issues']) && is_array($data['issues']) ? $data['issues'] : [],
        'score' => isset($data['score']) ? max(0, min(100, (int) $data['score'])) : 0,
        'source' => 'browser_extension',
        'created_at' => gmdate('c'),
    ];
    $events = app_read_json_file(seo_extension_events_path(), []);
    array_unshift($events, $event);
    $events = array_slice($events, 0, 500);
    app_write_json_file(seo_extension_events_path(), $events);

    if ($event['project_id'] !== '') {
        $audits = app_read_json_file(seo_audits_path(), []);
        array_unshift($audits, [
            'audit_id' => 'seo_audit_ext_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
            'project_id' => $event['project_id'],
            'project_name' => '',
            'domain' => $event['url'],
            'score' => (int) $event['score'],
            'critical_issues' => 0,
            'warnings' => is_array($event['issues']) ? count($event['issues']) : 0,
            'checks' => is_array($event['issues']) ? $event['issues'] : [],
            'source' => 'browser_extension',
            'created_at' => gmdate('c'),
        ]);
        $audits = array_slice($audits, 0, 500);
        app_write_json_file(seo_audits_path(), $audits);
    }

    out_json(['ok' => true, 'event_id' => $event['event_id']]);
}

if ($action === 'seo.extension.events.list') {
    $events = app_read_json_file(seo_extension_events_path(), []);
    out_json([
        'ok' => true,
        'count' => count($events),
        'items' => $events,
    ]);
}

if ($action === 'social.connectors.list') {
    $rows = app_read_json_file(social_connectors_path(), []);
    $publicRows = array_map('mask_connector', $rows);
    out_json([
        'ok' => true,
        'count' => count($publicRows),
        'items' => $publicRows,
    ]);
}

if ($action === 'social.connectors.save') {
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
        'connector_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['connector_id'] ?? uniqid('social_', false))),
        'provider' => strtolower(trim((string) ($data['provider'] ?? 'custom'))),
        'type' => strtolower(trim((string) ($data['type'] ?? 'external_api'))),
        'status' => strtolower(trim((string) ($data['status'] ?? 'planned'))),
        'auth_mode' => strtolower(trim((string) ($data['auth_mode'] ?? 'api_key'))),
        'capabilities' => isset($data['capabilities']) && is_array($data['capabilities']) ? array_values($data['capabilities']) : [],
        'site_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['site_id'] ?? '')),
        'config' => sanitize_connector_config($data['config'] ?? []),
        'updated_at' => gmdate('c'),
    ];
    $rows = app_read_json_file(social_connectors_path(), []);
    $rows = array_values(array_filter($rows, static function ($row) use ($item) {
        return (string) ($row['connector_id'] ?? '') !== $item['connector_id'];
    }));
    $rows[] = $item;
    app_write_json_file(social_connectors_path(), $rows);
    out_json(['ok' => true, 'item' => $item]);
}

if ($action === 'social.connectors.delete') {
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
    $rows = app_read_json_file(social_connectors_path(), []);
    $before = count($rows);
    $rows = array_values(array_filter($rows, static function ($row) use ($connectorId) {
        return (string) ($row['connector_id'] ?? '') !== $connectorId;
    }));
    app_write_json_file(social_connectors_path(), $rows);
    out_json([
        'ok' => true,
        'deleted' => $before - count($rows),
        'connector_id' => $connectorId,
    ]);
}

if ($action === 'social.connectors.test') {
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
        $connector = social_connector_by_id((string) $data['connector_id']);
    }
    if (!is_array($connector)) {
        out_json(['ok' => false, 'error' => 'Social connector not found.'], 404);
    }
    $sampleDrafts = [[
        'source_site_id' => 'test',
        'lead_id' => 0,
        'title' => 'Sample Social Draft',
        'message' => 'Sample social connector test message.',
        'url' => 'https://example.com',
    ]];
    $result = execute_social_connector_sync($connector, $sampleDrafts);
    out_json([
        'ok' => true,
        'connector_id' => (string) ($connector['connector_id'] ?? ''),
        'result' => $result,
    ]);
}

if ($action === 'social.push.sync') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $connectors = app_read_json_file(social_connectors_path(), []);
    $activeConnectors = array_values(array_filter($connectors, static function ($row) {
        $status = strtolower((string) ($row['status'] ?? ''));
        return in_array($status, ['active', 'enabled'], true);
    }));
    if (empty($activeConnectors)) {
        out_json(['ok' => false, 'error' => 'No active social connectors.'], 400);
    }
    $drafts = collect_social_drafts();
    $connectorResults = [];
    foreach ($activeConnectors as $connector) {
        $res = execute_social_connector_sync($connector, $drafts);
        $connectorResults[] = $res;
        if ((int) ($res['rejected'] ?? 0) > 0 || !empty($res['errors'])) {
            enqueue_social_retry_item([
                'retry_id' => 'social_retry_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
                'created_at' => gmdate('c'),
                'connector_id' => (string) ($res['connector_id'] ?? ''),
                'provider' => (string) ($res['provider'] ?? ''),
                'type' => (string) ($res['type'] ?? ''),
                'error_codes' => isset($res['error_codes']) && is_array($res['error_codes']) ? array_values(array_unique($res['error_codes'])) : [],
                'errors' => isset($res['errors']) && is_array($res['errors']) ? $res['errors'] : [],
                'draft_count' => count($drafts),
                'attempts' => 0,
                'status' => 'queued',
            ]);
        }
    }
    $syncId = 'social_sync_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6);
    $logItem = [
        'sync_id' => $syncId,
        'created_at' => gmdate('c'),
        'connector_count' => count($activeConnectors),
        'draft_total' => count($drafts),
        'connector_results' => $connectorResults,
        'connectors' => array_map(static function ($row) {
            return [
                'connector_id' => (string) ($row['connector_id'] ?? ''),
                'provider' => (string) ($row['provider'] ?? ''),
                'type' => (string) ($row['type'] ?? ''),
            ];
        }, $activeConnectors),
        'status' => 'queued_to_connectors',
    ];
    $logs = app_read_json_file(social_sync_log_path(), []);
    array_unshift($logs, $logItem);
    $logs = array_slice($logs, 0, 100);
    app_write_json_file(social_sync_log_path(), $logs);
    out_json([
        'ok' => true,
        'sync' => $logItem,
        'message' => 'Social push sync queued to active connectors.',
    ]);
}

if ($action === 'social.push.log') {
    $logs = app_read_json_file(social_sync_log_path(), []);
    out_json([
        'ok' => true,
        'count' => count($logs),
        'items' => $logs,
    ]);
}

if ($action === 'social.retry.list') {
    $queue = app_read_json_file(social_retry_queue_path(), []);
    out_json([
        'ok' => true,
        'count' => count($queue),
        'items' => $queue,
    ]);
}

if ($action === 'social.retry.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $queue = app_read_json_file(social_retry_queue_path(), []);
    if (empty($queue)) {
        out_json(['ok' => true, 'message' => 'Social retry queue is empty.', 'processed' => 0]);
    }
    $drafts = collect_social_drafts();
    $processed = 0;
    $succeeded = 0;
    $remaining = [];
    foreach ($queue as $item) {
        $processed++;
        $connector = social_connector_by_id((string) ($item['connector_id'] ?? ''));
        if (!is_array($connector)) {
            $item['status'] = 'failed_missing_connector';
            $item['attempts'] = (int) ($item['attempts'] ?? 0) + 1;
            $remaining[] = $item;
            continue;
        }
        $res = execute_social_connector_sync($connector, $drafts);
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
    app_write_json_file(social_retry_queue_path(), $remaining);
    out_json([
        'ok' => true,
        'processed' => $processed,
        'succeeded' => $succeeded,
        'remaining' => count($remaining),
    ]);
}

if ($action === 'webops.monitors.list') {
    $rows = app_read_json_file(webops_monitors_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
    ]);
}

if ($action === 'webops.monitors.save') {
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
        'monitor_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['monitor_id'] ?? uniqid('monitor_', false))),
        'name' => trim((string) ($data['name'] ?? '')),
        'type' => strtolower(trim((string) ($data['type'] ?? 'uptime_http'))),
        'status' => strtolower(trim((string) ($data['status'] ?? 'active'))),
        'target' => trim((string) ($data['target'] ?? '')),
        'config' => sanitize_connector_config($data['config'] ?? []),
        'updated_at' => gmdate('c'),
    ];
    $rows = app_read_json_file(webops_monitors_path(), []);
    $rows = array_values(array_filter($rows, static function ($row) use ($item) {
        return (string) ($row['monitor_id'] ?? '') !== $item['monitor_id'];
    }));
    $rows[] = $item;
    app_write_json_file(webops_monitors_path(), $rows);
    out_json(['ok' => true, 'item' => $item]);
}

if ($action === 'webops.monitors.delete') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['monitor_id'])) {
        out_json(['ok' => false, 'error' => 'monitor_id is required.'], 400);
    }
    $monitorId = (string) $data['monitor_id'];
    $rows = app_read_json_file(webops_monitors_path(), []);
    $before = count($rows);
    $rows = array_values(array_filter($rows, static function ($row) use ($monitorId) {
        return (string) ($row['monitor_id'] ?? '') !== $monitorId;
    }));
    app_write_json_file(webops_monitors_path(), $rows);
    out_json([
        'ok' => true,
        'deleted' => $before - count($rows),
        'monitor_id' => $monitorId,
    ]);
}

if ($action === 'webops.monitors.test') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['monitor_id'])) {
        out_json(['ok' => false, 'error' => 'monitor_id is required.'], 400);
    }
    $monitor = webops_monitor_by_id((string) $data['monitor_id']);
    if (!is_array($monitor)) {
        out_json(['ok' => false, 'error' => 'Monitor not found.'], 404);
    }
    $result = execute_webops_monitor($monitor);
    out_json([
        'ok' => true,
        'monitor_id' => (string) ($monitor['monitor_id'] ?? ''),
        'result' => $result,
    ]);
}

if ($action === 'webops.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $monitors = app_read_json_file(webops_monitors_path(), []);
    $active = array_values(array_filter($monitors, static function ($row) {
        $status = strtolower((string) ($row['status'] ?? ''));
        return in_array($status, ['active', 'enabled'], true);
    }));
    if (empty($active)) {
        out_json(['ok' => false, 'error' => 'No active WebOps monitors.'], 400);
    }
    $results = [];
    $critical = 0;
    foreach ($active as $monitor) {
        $res = execute_webops_monitor($monitor);
        $results[] = $res;
        if (($res['severity'] ?? '') === 'critical') {
            $critical++;
            enqueue_webops_retry_item([
                'retry_id' => 'webops_retry_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
                'created_at' => gmdate('c'),
                'monitor_id' => (string) ($monitor['monitor_id'] ?? ''),
                'type' => (string) ($monitor['type'] ?? ''),
                'error_code' => (string) ($res['error_code'] ?? ''),
                'message' => (string) ($res['message'] ?? ''),
                'attempts' => 0,
                'status' => 'queued',
            ]);
        }
    }
    $runItem = [
        'run_id' => 'webops_run_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'monitor_count' => count($active),
        'critical_count' => $critical,
        'results' => $results,
    ];
    $logs = app_read_json_file(webops_log_path(), []);
    array_unshift($logs, $runItem);
    $logs = array_slice($logs, 0, 200);
    app_write_json_file(webops_log_path(), $logs);
    out_json(['ok' => true, 'run' => $runItem]);
}

if ($action === 'webops.log') {
    $logs = app_read_json_file(webops_log_path(), []);
    out_json(['ok' => true, 'count' => count($logs), 'items' => $logs]);
}

if ($action === 'webops.retry.list') {
    $queue = app_read_json_file(webops_retry_queue_path(), []);
    out_json(['ok' => true, 'count' => count($queue), 'items' => $queue]);
}

if ($action === 'webops.retry.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $queue = app_read_json_file(webops_retry_queue_path(), []);
    if (empty($queue)) {
        out_json(['ok' => true, 'message' => 'WebOps retry queue is empty.', 'processed' => 0]);
    }
    $processed = 0;
    $succeeded = 0;
    $remaining = [];
    foreach ($queue as $item) {
        $processed++;
        $monitor = webops_monitor_by_id((string) ($item['monitor_id'] ?? ''));
        if (!is_array($monitor)) {
            $item['status'] = 'failed_missing_monitor';
            $item['attempts'] = (int) ($item['attempts'] ?? 0) + 1;
            $remaining[] = $item;
            continue;
        }
        $res = execute_webops_monitor($monitor);
        if (!empty($res['ok'])) {
            $succeeded++;
            continue;
        }
        $item['status'] = 'queued';
        $item['attempts'] = (int) ($item['attempts'] ?? 0) + 1;
        $item['error_code'] = (string) ($res['error_code'] ?? '');
        $item['message'] = (string) ($res['message'] ?? '');
        $remaining[] = $item;
    }
    app_write_json_file(webops_retry_queue_path(), $remaining);
    out_json([
        'ok' => true,
        'processed' => $processed,
        'succeeded' => $succeeded,
        'remaining' => count($remaining),
    ]);
}

out_json([
    'ok' => false,
    'error' => 'Unknown action.',
    'action' => $action,
], 404);
