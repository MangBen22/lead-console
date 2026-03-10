<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/lib.php';

$config = app_config();
$action = isset($_GET['action']) ? (string) $_GET['action'] : 'status';

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

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

function app_value_is_placeholder($value)
{
    $v = strtolower(trim((string) $value));
    if ($v === '') {
        return true;
    }
    $markers = [
        'replace_with',
        'replace-',
        'change_me',
        'changeme',
        'placeholder',
        'example.com',
        'your-',
        'your_',
        'demo',
    ];
    foreach ($markers as $marker) {
        if (strpos($v, $marker) !== false) {
            return true;
        }
    }
    return false;
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

function crm_smtp_sites_snapshot()
{
    $rows = [];
    $summary = [
        'site_count' => 0,
        'connected_sites' => 0,
        'pending_confirmation_sites' => 0,
        'confirmed_sites' => 0,
        'failed_sites' => 0,
    ];
    foreach (all_sites() as $site) {
        $res = app_bridge_request($site, 'GET', 'bridge/smtp-health');
        $health = (!empty($res['ok']) && is_array($res['data']) && isset($res['data']['smtp_health']) && is_array($res['data']['smtp_health']))
            ? $res['data']['smtp_health']
            : [];
        $confirmation = (!empty($res['ok']) && is_array($res['data']) && isset($res['data']['test_confirmation']) && is_array($res['data']['test_confirmation']))
            ? $res['data']['test_confirmation']
            : [];
        $connected = !empty($health['connected']);
        $pending = !empty($confirmation['pending']);
        $confirmed = !empty($confirmation['confirmed']);
        $summary['site_count']++;
        if ($connected) {
            $summary['connected_sites']++;
        } else {
            $summary['failed_sites']++;
        }
        if ($pending) {
            $summary['pending_confirmation_sites']++;
        }
        if ($confirmed) {
            $summary['confirmed_sites']++;
        }
        $rows[] = [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'base_url' => (string) ($site['base_url'] ?? ''),
            'smtp_health' => $health,
            'test_confirmation' => $confirmation,
            'bridge_ok' => !empty($res['ok']) ? 1 : 0,
            'bridge_status' => (int) ($res['status'] ?? 0),
            'bridge_error' => (string) ($res['error'] ?? ''),
        ];
    }
    return [
        'summary' => $summary,
        'sites' => $rows,
    ];
}

function crm_email_templates_fetch($site)
{
    $res = app_bridge_request($site, 'GET', 'bridge/email-templates');
    $data = (!empty($res['ok']) && is_array($res['data']) && isset($res['data']['email_templates']) && is_array($res['data']['email_templates']))
        ? $res['data']['email_templates']
        : ['templates' => [], 'placeholders' => []];
    return [
        'ok' => !empty($res['ok']),
        'status' => (int) ($res['status'] ?? 0),
        'error' => (string) ($res['error'] ?? ''),
        'email_templates' => $data,
    ];
}

function crm_email_template_preview_fetch($site, $templateKey, $subject = null, $body = null, $vars = [])
{
    $payload = [
        'template_key' => (string) $templateKey,
        'vars' => is_array($vars) ? $vars : [],
    ];
    if ($subject !== null) {
        $payload['subject'] = (string) $subject;
    }
    if ($body !== null) {
        $payload['body'] = (string) $body;
    }
    $res = app_bridge_request($site, 'POST', 'bridge/email-templates/preview', $payload);
    $data = (!empty($res['ok']) && is_array($res['data']) && isset($res['data']['preview']) && is_array($res['data']['preview']))
        ? $res['data']['preview']
        : [];
    return [
        'ok' => !empty($res['ok']),
        'status' => (int) ($res['status'] ?? 0),
        'error' => (string) ($res['error'] ?? ''),
        'preview' => $data,
    ];
}

function crm_email_template_test_log_fetch($site, $limit = 25)
{
    $limit = (int) $limit;
    if ($limit <= 0) {
        $limit = 25;
    }
    $res = app_bridge_request($site, 'GET', 'bridge/email-templates/test-log?limit=' . $limit);
    $items = (!empty($res['ok']) && is_array($res['data']) && isset($res['data']['items']) && is_array($res['data']['items']))
        ? $res['data']['items']
        : [];
    return [
        'ok' => !empty($res['ok']),
        'status' => (int) ($res['status'] ?? 0),
        'error' => (string) ($res['error'] ?? ''),
        'items' => $items,
        'count' => (!empty($res['ok']) && is_array($res['data'])) ? (int) ($res['data']['count'] ?? count($items)) : count($items),
    ];
}

function crm_smtp_watch_snapshot($source = 'manual', $emitNotifications = true)
{
    $snapshot = crm_smtp_sites_snapshot();
    $rows = isset($snapshot['sites']) && is_array($snapshot['sites']) ? $snapshot['sites'] : [];
    $state = app_read_json_file(crm_smtp_watch_state_path(), []);
    $previousSites = isset($state['sites']) && is_array($state['sites']) ? $state['sites'] : [];
    $items = [];
    $summary = [
        'site_count' => count($rows),
        'disconnected_sites' => 0,
        'pending_confirmation_sites' => 0,
        'confirmed_sites' => 0,
        'bridge_error_sites' => 0,
        'changed_sites' => 0,
    ];
    $nextSites = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $siteId = (string) ($row['site_id'] ?? '');
        $label = (string) ($row['label'] ?? $siteId);
        $smtpHealth = isset($row['smtp_health']) && is_array($row['smtp_health']) ? $row['smtp_health'] : [];
        $testConfirmation = isset($row['test_confirmation']) && is_array($row['test_confirmation']) ? $row['test_confirmation'] : [];
        $bridgeOk = !empty($row['bridge_ok']);
        $connected = !empty($smtpHealth['connected']) && $bridgeOk;
        $pending = !empty($testConfirmation['pending']);
        $confirmed = !empty($testConfirmation['confirmed']);
        $status = 'connected_unverified';
        if (!$bridgeOk || !$connected) {
            $status = 'disconnected';
        } elseif ($pending) {
            $status = 'pending_confirmation';
        } elseif ($confirmed) {
            $status = 'verified';
        }
        if ($status === 'disconnected') {
            $summary['disconnected_sites']++;
        }
        if ($pending) {
            $summary['pending_confirmation_sites']++;
        }
        if ($confirmed) {
            $summary['confirmed_sites']++;
        }
        if (!$bridgeOk) {
            $summary['bridge_error_sites']++;
        }

        $previous = isset($previousSites[$siteId]) && is_array($previousSites[$siteId]) ? $previousSites[$siteId] : [];
        $currentMessage = (string) ($smtpHealth['message'] ?? $row['bridge_error'] ?? '');
        $changed = false;
        if (!empty($previous)) {
            $previousStatus = (string) ($previous['status'] ?? '');
            $previousMessage = (string) ($previous['message'] ?? '');
            if ($previousStatus !== $status || $previousMessage !== $currentMessage) {
                $changed = true;
            }
            if ($emitNotifications) {
                if ($previousStatus !== 'disconnected' && $status === 'disconnected') {
                    push_notification('critical', 'CRM SMTP disconnected for ' . $label . '.', [
                        'site_id' => $siteId,
                        'source' => $source,
                        'message' => $currentMessage,
                    ]);
                } elseif ($previousStatus === 'disconnected' && $status !== 'disconnected') {
                    push_notification('success', 'CRM SMTP recovered for ' . $label . '.', [
                        'site_id' => $siteId,
                        'source' => $source,
                        'status' => $status,
                    ]);
                }
                if (empty($previous['pending']) && $pending) {
                    push_notification('info', 'CRM SMTP test email pending confirmation for ' . $label . '.', [
                        'site_id' => $siteId,
                        'source' => $source,
                    ]);
                }
                if (empty($previous['confirmed']) && $confirmed) {
                    push_notification('success', 'CRM SMTP confirmed for ' . $label . '.', [
                        'site_id' => $siteId,
                        'source' => $source,
                        'to_email' => (string) ($testConfirmation['to_email'] ?? ''),
                    ]);
                }
            }
        }
        if ($changed) {
            $summary['changed_sites']++;
        }

        $items[] = [
            'site_id' => $siteId,
            'label' => $label,
            'status' => $status,
            'bridge_ok' => $bridgeOk ? 1 : 0,
            'smtp_connected' => $connected ? 1 : 0,
            'pending_confirmation' => $pending ? 1 : 0,
            'confirmed' => $confirmed ? 1 : 0,
            'message' => $currentMessage,
            'checked_at' => (string) ($smtpHealth['checked_at'] ?? ''),
            'changed' => $changed ? 1 : 0,
        ];
        $nextSites[$siteId] = [
            'status' => $status,
            'connected' => $connected ? 1 : 0,
            'pending' => $pending ? 1 : 0,
            'confirmed' => $confirmed ? 1 : 0,
            'message' => $currentMessage,
            'checked_at' => (string) ($smtpHealth['checked_at'] ?? ''),
        ];
    }

    $run = [
        'run_id' => 'crm_smtp_watch_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'source' => (string) $source,
        'created_at' => gmdate('c'),
        'summary' => $summary,
        'items' => $items,
    ];
    $runs = app_read_json_file(crm_smtp_watch_runs_path(), []);
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 200);
    app_write_json_file(crm_smtp_watch_runs_path(), $runs);
    $nextState = [
        'last_checked_at' => gmdate('c'),
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => (string) $source,
        'summary' => $summary,
        'sites' => $nextSites,
    ];
    app_write_json_file(crm_smtp_watch_state_path(), $nextState);
    audit_event('crm', 'smtp.watch', [
        'run_id' => (string) ($run['run_id'] ?? ''),
        'source' => $source,
        'summary' => $summary,
    ]);
    return [
        'run' => $run,
        'state' => $nextState,
    ];
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

function leads_inventory_snapshot($limit = 10)
{
    $payload = collect_approved_leads();
    $leads = isset($payload['leads']) && is_array($payload['leads']) ? $payload['leads'] : [];
    $sites = isset($payload['sites']) && is_array($payload['sites']) ? $payload['sites'] : [];
    $statusMap = [];
    $categoryMap = [];
    $cityMap = [];
    $summary = [
        'total_leads' => count($leads),
        'site_count' => count($sites),
        'with_email' => 0,
        'with_phone' => 0,
        'with_website' => 0,
    ];

    foreach ($leads as $lead) {
        if (!is_array($lead)) {
            continue;
        }
        $status = trim((string) ($lead['status'] ?? 'unknown'));
        $category = trim((string) ($lead['category'] ?? 'Uncategorized'));
        $city = trim((string) ($lead['city'] ?? 'Unknown'));
        if (!isset($statusMap[$status])) {
            $statusMap[$status] = ['status' => $status, 'count' => 0];
        }
        if (!isset($categoryMap[$category])) {
            $categoryMap[$category] = ['category' => $category, 'count' => 0];
        }
        if (!isset($cityMap[$city])) {
            $cityMap[$city] = ['city' => $city, 'count' => 0];
        }
        $statusMap[$status]['count']++;
        $categoryMap[$category]['count']++;
        $cityMap[$city]['count']++;
        if (trim((string) ($lead['email'] ?? '')) !== '') {
            $summary['with_email']++;
        }
        if (trim((string) ($lead['phone'] ?? '')) !== '') {
            $summary['with_phone']++;
        }
        if (trim((string) ($lead['website'] ?? '')) !== '') {
            $summary['with_website']++;
        }
    }

    usort($leads, static function ($a, $b) {
        return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
    });
    $topCategories = array_values($categoryMap);
    usort($topCategories, static function ($a, $b) {
        return ((int) ($a['count'] ?? 0) < (int) ($b['count'] ?? 0)) ? 1 : -1;
    });
    $topCities = array_values($cityMap);
    usort($topCities, static function ($a, $b) {
        return ((int) ($a['count'] ?? 0) < (int) ($b['count'] ?? 0)) ? 1 : -1;
    });

    return [
        'summary' => $summary,
        'sites' => $sites,
        'statuses' => array_values($statusMap),
        'top_categories' => array_slice($topCategories, 0, $limit),
        'top_cities' => array_slice($topCities, 0, $limit),
        'recent_leads' => array_slice($leads, 0, $limit),
    ];
}

function leads_list_snapshot($query = [])
{
    $payload = collect_approved_leads();
    $rows = isset($payload['leads']) && is_array($payload['leads']) ? $payload['leads'] : [];
    $search = strtolower(trim((string) ($query['search'] ?? '')));
    $siteId = strtolower(trim((string) ($query['site_id'] ?? '')));
    $status = strtolower(trim((string) ($query['status'] ?? '')));
    $limit = isset($query['limit']) ? (int) $query['limit'] : 20;
    if ($limit <= 0) {
        $limit = 20;
    }
    $limit = min($limit, 100);
    $page = isset($query['page']) ? (int) $query['page'] : 1;
    if ($page <= 0) {
        $page = 1;
    }

    $filtered = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($siteId !== '' && strtolower((string) ($row['site_id'] ?? '')) !== $siteId) {
            continue;
        }
        if ($status !== '' && strtolower((string) ($row['status'] ?? '')) !== $status) {
            continue;
        }
        if ($search !== '') {
            $haystacks = [
                strtolower((string) ($row['business_name'] ?? '')),
                strtolower((string) ($row['city'] ?? '')),
                strtolower((string) ($row['category'] ?? '')),
                strtolower((string) ($row['website'] ?? '')),
                strtolower((string) ($row['email'] ?? '')),
                strtolower((string) ($row['phone'] ?? '')),
            ];
            $matched = false;
            foreach ($haystacks as $haystack) {
                if ($haystack !== '' && strpos($haystack, $search) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    usort($filtered, static function ($a, $b) {
        return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
    });
    $offset = ($page - 1) * $limit;

    return [
        'summary' => [
            'total_count' => count($rows),
            'filtered_count' => count($filtered),
            'page' => $page,
            'limit' => $limit,
        ],
        'items' => array_slice($filtered, $offset, $limit),
    ];
}

function leads_push_plan_snapshot($limit = 10)
{
    $payload = collect_approved_leads();
    $leads = isset($payload['leads']) && is_array($payload['leads']) ? $payload['leads'] : [];
    $sites = isset($payload['sites']) && is_array($payload['sites']) ? $payload['sites'] : [];
    $connectors = app_read_json_file(app_storage_path('crm_connectors.json'), []);
    $activeConnectors = [];
    foreach ($connectors as $connector) {
        if (!is_array($connector)) {
            continue;
        }
        $status = strtolower((string) ($connector['status'] ?? ''));
        if (!in_array($status, ['active', 'enabled'], true)) {
            continue;
        }
        $activeConnectors[] = mask_connector($connector);
    }

    $siteLeadMap = [];
    foreach ($leads as $lead) {
        if (!is_array($lead)) {
            continue;
        }
        $siteId = (string) ($lead['site_id'] ?? '');
        if (!isset($siteLeadMap[$siteId])) {
            $siteLeadMap[$siteId] = 0;
        }
        $siteLeadMap[$siteId]++;
    }

    $connectorItems = [];
    foreach ($activeConnectors as $connector) {
        $connectorItems[] = [
            'connector_id' => (string) ($connector['connector_id'] ?? ''),
            'provider' => (string) ($connector['provider'] ?? ''),
            'type' => (string) ($connector['type'] ?? ''),
            'status' => (string) ($connector['status'] ?? ''),
            'lead_count' => count($leads),
            'site_count' => count($sites),
        ];
    }

    $issues = [];
    if (empty($activeConnectors)) {
        $issues[] = 'no_active_crm_connectors';
    }
    if (empty($leads)) {
        $issues[] = 'no_approved_leads';
    }

    $lastSyncLog = app_read_json_file(app_storage_path('crm_sync_log.json'), []);
    $lastSync = isset($lastSyncLog[0]) && is_array($lastSyncLog[0]) ? $lastSyncLog[0] : null;

    return [
        'summary' => [
            'approved_total' => count($leads),
            'site_count' => count($sites),
            'active_connector_count' => count($activeConnectors),
            'delivery_pairs' => count($leads) * count($activeConnectors),
            'issues_count' => count($issues),
            'ready' => empty($issues) ? 1 : 0,
        ],
        'issues' => $issues,
        'connectors' => array_slice($connectorItems, 0, $limit),
        'sites' => array_slice(array_map(static function ($site) use ($siteLeadMap) {
            $siteId = (string) ($site['site_id'] ?? '');
            return [
                'site_id' => $siteId,
                'label' => (string) ($site['label'] ?? ''),
                'connected' => !empty($site['connected']) ? 1 : 0,
                'approved_count' => (int) ($site['approved_count'] ?? 0),
                'lead_count' => (int) ($siteLeadMap[$siteId] ?? 0),
            ];
        }, $sites), 0, $limit),
        'last_sync' => $lastSync,
    ];
}

function leads_quality_snapshot($limit = 10)
{
    $payload = collect_approved_leads();
    $rows = isset($payload['leads']) && is_array($payload['leads']) ? $payload['leads'] : [];
    $summary = [
        'total_leads' => count($rows),
        'missing_email' => 0,
        'missing_phone' => 0,
        'missing_website' => 0,
        'contact_ready' => 0,
        'profile_complete' => 0,
        'duplicate_groups' => 0,
    ];
    $duplicateBuckets = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $email = trim((string) ($row['email'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));
        $website = trim((string) ($row['website'] ?? ''));
        $business = strtolower(trim((string) ($row['business_name'] ?? '')));
        $city = strtolower(trim((string) ($row['city'] ?? '')));
        $category = trim((string) ($row['category'] ?? ''));

        if ($email === '') {
            $summary['missing_email']++;
        }
        if ($phone === '') {
            $summary['missing_phone']++;
        }
        if ($website === '') {
            $summary['missing_website']++;
        }
        if ($email !== '' || $phone !== '') {
            $summary['contact_ready']++;
        }
        if ($business !== '' && $city !== '' && $category !== '' && $website !== '' && ($email !== '' || $phone !== '')) {
            $summary['profile_complete']++;
        }

        $keys = [];
        if ($website !== '') {
            $keys[] = 'website:' . strtolower($website);
        }
        if ($email !== '') {
            $keys[] = 'email:' . strtolower($email);
        }
        if ($business !== '' && $city !== '') {
            $keys[] = 'business_city:' . $business . '|' . $city;
        }
        foreach ($keys as $key) {
            if (!isset($duplicateBuckets[$key])) {
                $duplicateBuckets[$key] = [
                    'key' => $key,
                    'count' => 0,
                    'leads' => [],
                ];
            }
            $duplicateBuckets[$key]['count']++;
            $duplicateBuckets[$key]['leads'][] = [
                'lead_id' => (int) ($row['lead_id'] ?? 0),
                'site_id' => (string) ($row['site_id'] ?? ''),
                'business_name' => (string) ($row['business_name'] ?? ''),
                'city' => (string) ($row['city'] ?? ''),
                'website' => $website,
                'email' => $email,
                'phone' => $phone,
                'status' => (string) ($row['status'] ?? ''),
            ];
        }
    }

    $duplicateGroups = array_values(array_filter($duplicateBuckets, static function ($bucket) {
        return (int) ($bucket['count'] ?? 0) > 1;
    }));
    usort($duplicateGroups, static function ($a, $b) {
        $left = (int) ($a['count'] ?? 0);
        $right = (int) ($b['count'] ?? 0);
        if ($left === $right) {
            return strcmp((string) ($a['key'] ?? ''), (string) ($b['key'] ?? ''));
        }
        return ($leftDebt < $rightDebt) ? 1 : -1;
    });
    $summary['duplicate_groups'] = count($duplicateGroups);

    return [
        'summary' => $summary,
        'duplicate_groups' => array_slice($duplicateGroups, 0, $limit),
    ];
}

function leads_delivery_history_snapshot($limit = 10)
{
    $syncLog = app_read_json_file(app_storage_path('crm_sync_log.json'), []);
    $retryQueue = app_read_json_file(retry_queue_path(), []);
    $items = [];
    foreach ($syncLog as $row) {
        if (!is_array($row)) {
            continue;
        }
        $items[] = [
            'sync_id' => (string) ($row['sync_id'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'approved_total' => (int) ($row['approved_total'] ?? 0),
            'connector_count' => (int) ($row['connector_count'] ?? 0),
            'site_count' => (int) ($row['site_count'] ?? 0),
        ];
    }

    return [
        'summary' => [
            'sync_count' => count($syncLog),
            'retry_queue_count' => count($retryQueue),
            'last_sync_id' => isset($items[0]) ? (string) ($items[0]['sync_id'] ?? '') : '',
            'last_sync_status' => isset($items[0]) ? (string) ($items[0]['status'] ?? '') : '',
        ],
        'items' => array_slice($items, 0, $limit),
        'retry_queue' => array_slice($retryQueue, 0, $limit),
    ];
}

function crm_delivery_summary_snapshot($limit = 20)
{
    $connectors = app_read_json_file(app_storage_path('crm_connectors.json'), []);
    $syncLog = app_read_json_file(app_storage_path('crm_sync_log.json'), []);
    $retryQueue = app_read_json_file(retry_queue_path(), []);

    $items = [];
    foreach ($connectors as $connector) {
        if (!is_array($connector)) {
            continue;
        }
        $connectorId = (string) ($connector['connector_id'] ?? '');
        if ($connectorId === '') {
            continue;
        }
        $items[$connectorId] = [
            'connector_id' => $connectorId,
            'provider' => (string) ($connector['provider'] ?? ''),
            'type' => (string) ($connector['type'] ?? ''),
            'status' => (string) ($connector['status'] ?? ''),
            'sync_count' => 0,
            'accepted_total' => 0,
            'rejected_total' => 0,
            'queued_retries' => 0,
            'retry_attempts' => 0,
            'last_sync_at' => '',
            'last_sync_status' => 'unknown',
            'last_retry_status' => '',
            'error_codes' => [],
        ];
    }

    foreach ($syncLog as $log) {
        if (!is_array($log) || empty($log['connector_results']) || !is_array($log['connector_results'])) {
            continue;
        }
        $createdAt = (string) ($log['created_at'] ?? '');
        foreach ($log['connector_results'] as $result) {
            if (!is_array($result)) {
                continue;
            }
            $connectorId = (string) ($result['connector_id'] ?? '');
            if ($connectorId === '') {
                continue;
            }
            if (!isset($items[$connectorId])) {
                $items[$connectorId] = [
                    'connector_id' => $connectorId,
                    'provider' => (string) ($result['provider'] ?? ''),
                    'type' => (string) ($result['type'] ?? ''),
                    'status' => 'unknown',
                    'sync_count' => 0,
                    'accepted_total' => 0,
                    'rejected_total' => 0,
                    'queued_retries' => 0,
                    'retry_attempts' => 0,
                    'last_sync_at' => '',
                    'last_sync_status' => 'unknown',
                    'last_retry_status' => '',
                    'error_codes' => [],
                ];
            }
            $items[$connectorId]['sync_count']++;
            $items[$connectorId]['accepted_total'] += (int) ($result['accepted'] ?? 0);
            $items[$connectorId]['rejected_total'] += (int) ($result['rejected'] ?? 0);
            if ($items[$connectorId]['last_sync_at'] === '' || strcmp($createdAt, $items[$connectorId]['last_sync_at']) > 0) {
                $items[$connectorId]['last_sync_at'] = $createdAt;
                $items[$connectorId]['last_sync_status'] = ((int) ($result['rejected'] ?? 0) > 0 || !empty($result['errors'])) ? 'degraded' : 'ok';
            }
            foreach ((array) ($result['error_codes'] ?? []) as $code) {
                $code = (string) $code;
                if ($code === '') {
                    continue;
                }
                if (!isset($items[$connectorId]['error_codes'][$code])) {
                    $items[$connectorId]['error_codes'][$code] = 0;
                }
                $items[$connectorId]['error_codes'][$code]++;
            }
        }
    }

    foreach ($retryQueue as $retry) {
        if (!is_array($retry)) {
            continue;
        }
        $connectorId = (string) ($retry['connector_id'] ?? '');
        if ($connectorId === '') {
            continue;
        }
        if (!isset($items[$connectorId])) {
            $items[$connectorId] = [
                'connector_id' => $connectorId,
                'provider' => (string) ($retry['provider'] ?? ''),
                'type' => (string) ($retry['type'] ?? ''),
                'status' => 'unknown',
                'sync_count' => 0,
                'accepted_total' => 0,
                'rejected_total' => 0,
                'queued_retries' => 0,
                'retry_attempts' => 0,
                'last_sync_at' => '',
                'last_sync_status' => 'unknown',
                'last_retry_status' => '',
                'error_codes' => [],
            ];
        }
        $items[$connectorId]['queued_retries']++;
        $items[$connectorId]['retry_attempts'] += (int) ($retry['attempts'] ?? 0);
        $items[$connectorId]['last_retry_status'] = (string) ($retry['status'] ?? '');
        foreach ((array) ($retry['error_codes'] ?? []) as $code) {
            $code = (string) $code;
            if ($code === '') {
                continue;
            }
            if (!isset($items[$connectorId]['error_codes'][$code])) {
                $items[$connectorId]['error_codes'][$code] = 0;
            }
            $items[$connectorId]['error_codes'][$code]++;
        }
    }

    $rows = array_values(array_map(static function ($item) {
        if (is_array($item['error_codes'])) {
            arsort($item['error_codes']);
        }
        return $item;
    }, $items));

    usort($rows, static function ($a, $b) {
        $leftDebt = (int) ($a['queued_retries'] ?? 0) + (int) ($a['rejected_total'] ?? 0);
        $rightDebt = (int) ($b['queued_retries'] ?? 0) + (int) ($b['rejected_total'] ?? 0);
        if ($leftDebt === $rightDebt) {
            return strcmp((string) ($a['connector_id'] ?? ''), (string) ($b['connector_id'] ?? ''));
        }
        return ($left < $right) ? 1 : -1;
    });

    return [
        'summary' => [
            'connector_count' => count($rows),
            'active_connector_count' => count(array_filter($rows, static function ($row) {
                return in_array(strtolower((string) ($row['status'] ?? '')), ['active', 'enabled'], true);
            })),
            'sync_count' => count($syncLog),
            'retry_queue_count' => count($retryQueue),
            'connectors_with_retries' => count(array_filter($rows, static function ($row) {
                return (int) ($row['queued_retries'] ?? 0) > 0;
            })),
            'connectors_with_rejections' => count(array_filter($rows, static function ($row) {
                return (int) ($row['rejected_total'] ?? 0) > 0;
            })),
        ],
        'items' => array_slice($rows, 0, max(1, $limit)),
    ];
}

function crm_delivery_connector_detail_snapshot($connectorId, $limit = 10)
{
    $id = trim((string) $connectorId);
    if ($id === '') {
        return ['ok' => false, 'error' => 'connector_id is required.'];
    }

    $connectors = app_read_json_file(app_storage_path('crm_connectors.json'), []);
    $connectorMeta = null;
    foreach ($connectors as $connector) {
        if (is_array($connector) && (string) ($connector['connector_id'] ?? '') === $id) {
            $connectorMeta = mask_connector($connector);
            break;
        }
    }

    $syncLog = app_read_json_file(app_storage_path('crm_sync_log.json'), []);
    $retryQueue = app_read_json_file(retry_queue_path(), []);
    $syncItems = [];
    foreach ($syncLog as $log) {
        if (!is_array($log) || empty($log['connector_results']) || !is_array($log['connector_results'])) {
            continue;
        }
        foreach ($log['connector_results'] as $result) {
            if (!is_array($result) || (string) ($result['connector_id'] ?? '') !== $id) {
                continue;
            }
            $syncItems[] = [
                'sync_id' => (string) ($log['sync_id'] ?? ''),
                'created_at' => (string) ($log['created_at'] ?? ''),
                'approved_total' => (int) ($log['approved_total'] ?? 0),
                'site_count' => (int) ($log['site_count'] ?? 0),
                'accepted' => (int) ($result['accepted'] ?? 0),
                'rejected' => (int) ($result['rejected'] ?? 0),
                'errors' => isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : [],
                'error_codes' => isset($result['error_codes']) && is_array($result['error_codes']) ? $result['error_codes'] : [],
                'run_mode' => (string) ($result['run_mode'] ?? ''),
            ];
        }
    }

    $retryItems = [];
    foreach ($retryQueue as $retry) {
        if (!is_array($retry) || (string) ($retry['connector_id'] ?? '') !== $id) {
            continue;
        }
        $retryItems[] = [
            'retry_id' => (string) ($retry['retry_id'] ?? ''),
            'created_at' => (string) ($retry['created_at'] ?? ''),
            'lead_count' => (int) ($retry['lead_count'] ?? 0),
            'attempts' => (int) ($retry['attempts'] ?? 0),
            'status' => (string) ($retry['status'] ?? ''),
            'error_codes' => isset($retry['error_codes']) && is_array($retry['error_codes']) ? $retry['error_codes'] : [],
            'errors' => isset($retry['errors']) && is_array($retry['errors']) ? $retry['errors'] : [],
        ];
    }

    return [
        'ok' => true,
        'connector' => $connectorMeta ?: ['connector_id' => $id],
        'summary' => [
            'sync_count' => count($syncItems),
            'retry_count' => count($retryItems),
            'accepted_total' => array_sum(array_map(static function ($item) {
                return (int) ($item['accepted'] ?? 0);
            }, $syncItems)),
            'rejected_total' => array_sum(array_map(static function ($item) {
                return (int) ($item['rejected'] ?? 0);
            }, $syncItems)),
        ],
        'sync_items' => array_slice($syncItems, 0, max(1, $limit)),
        'retry_items' => array_slice($retryItems, 0, max(1, $limit)),
    ];
}

function crm_delivery_watch_state_path()
{
    return app_storage_path('crm_delivery_watch_state.json');
}

function crm_delivery_watch_runs_path()
{
    return app_storage_path('crm_delivery_watch_runs.json');
}

function crm_delivery_watch_evaluate($source = 'manual', $emitNotification = false)
{
    $snapshot = crm_delivery_summary_snapshot(100);
    $items = isset($snapshot['items']) && is_array($snapshot['items']) ? $snapshot['items'] : [];
    $degraded = array_values(array_filter($items, static function ($item) {
        return (int) ($item['queued_retries'] ?? 0) > 0
            || (int) ($item['rejected_total'] ?? 0) > 0
            || (string) ($item['last_sync_status'] ?? '') === 'degraded';
    }));
    $run = [
        'watch_id' => 'crmwatch_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'source' => (string) $source,
        'summary' => [
            'connector_count' => isset($snapshot['summary']['connector_count']) ? (int) $snapshot['summary']['connector_count'] : count($items),
            'degraded_count' => count($degraded),
            'retry_backlog_connectors' => count(array_filter($degraded, static function ($item) {
                return (int) ($item['queued_retries'] ?? 0) > 0;
            })),
            'rejection_connectors' => count(array_filter($degraded, static function ($item) {
                return (int) ($item['rejected_total'] ?? 0) > 0;
            })),
            'attention_required' => !empty($degraded) ? 1 : 0,
        ],
        'items' => array_slice($degraded, 0, 25),
    ];

    app_write_json_file(crm_delivery_watch_state_path(), $run);
    $runs = app_read_json_file(crm_delivery_watch_runs_path(), []);
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 60);
    app_write_json_file(crm_delivery_watch_runs_path(), $runs);

    if ($emitNotification && !empty($degraded)) {
        push_notification('warning', 'CRM delivery watch detected degraded connectors.', [
            'watch_id' => (string) $run['watch_id'],
            'degraded_count' => (int) $run['summary']['degraded_count'],
        ]);
    }

    return $run;
}

function crm_delivery_watch_summary()
{
    $state = app_read_json_file(crm_delivery_watch_state_path(), []);
    $runs = app_read_json_file(crm_delivery_watch_runs_path(), []);
    if (!is_array($state) || empty($state)) {
        $state = crm_delivery_watch_evaluate('bootstrap', false);
    }
    return [
        'summary' => isset($state['summary']) && is_array($state['summary']) ? $state['summary'] : [],
        'latest' => $state,
        'runs' => array_slice(is_array($runs) ? $runs : [], 0, 10),
    ];
}

function collect_leads_review_queue($query = [])
{
    $limit = isset($query['limit']) ? (int) $query['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $limit = min($limit, 100);
    $page = isset($query['page']) ? (int) $query['page'] : 1;
    if ($page <= 0) {
        $page = 1;
    }
    $reviewStatus = isset($query['review_status']) ? strtolower(trim((string) $query['review_status'])) : 'pending';
    $siteFilter = strtolower(trim((string) ($query['site_id'] ?? '')));
    $rows = [];
    foreach (all_sites() as $site) {
        $query = ['limit' => min(max(1, $limit), 100)];
        if ($reviewStatus !== '' && $reviewStatus !== 'all') {
            $query['review_status'] = $reviewStatus;
        }
        $res = app_bridge_request($site, 'GET', 'bridge/run-reviews', [], $query);
        $items = (!empty($res['ok']) && isset($res['data']['items']) && is_array($res['data']['items'])) ? $res['data']['items'] : [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $rows[] = [
                'site_id' => (string) ($site['site_id'] ?? ''),
                'site_label' => (string) ($site['label'] ?? $site['base_url']),
                'run_id' => (int) ($item['run_id'] ?? 0),
                'query_text' => (string) ($item['query_text'] ?? ''),
                'review_status' => (string) ($item['review_status'] ?? ''),
                'status' => (string) ($item['status'] ?? ''),
                'draft_count' => (int) ($item['draft_count'] ?? 0),
                'captured_leads' => (int) ($item['captured_leads'] ?? 0),
                'created_at' => (string) ($item['created_at'] ?? ''),
                'finished_at' => (string) ($item['finished_at'] ?? ''),
            ];
        }
    }

    usort($rows, static function ($a, $b) {
        return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
    });

    $filtered = [];
    $pendingRuns = 0;
    $draftTotal = 0;
    foreach ($rows as $row) {
        if ($siteFilter !== '' && strtolower((string) ($row['site_id'] ?? '')) !== $siteFilter) {
            continue;
        }
        if ($reviewStatus !== '' && $reviewStatus !== 'all' && strtolower((string) ($row['review_status'] ?? '')) !== $reviewStatus) {
            continue;
        }
        $filtered[] = $row;
        if (strtolower((string) ($row['review_status'] ?? '')) === 'pending') {
            $pendingRuns++;
        }
        $draftTotal += (int) ($row['draft_count'] ?? 0);
    }
    $offset = ($page - 1) * $limit;

    return [
        'summary' => [
            'run_count' => count($rows),
            'filtered_count' => count($filtered),
            'pending_runs' => $pendingRuns,
            'draft_total' => $draftTotal,
            'page' => $page,
            'limit' => $limit,
            'review_status' => $reviewStatus,
            'site_id' => $siteFilter,
        ],
        'items' => array_slice($filtered, $offset, $limit),
    ];
}

function leads_review_detail_fetch($siteId, $runId)
{
    $site = site_by_id((string) $siteId);
    if (!is_array($site)) {
        return ['ok' => false, 'error' => 'Site not found.'];
    }
    $res = app_bridge_request($site, 'GET', 'bridge/run-review', [], ['run_id' => (int) $runId]);
    if (empty($res['ok']) || !is_array($res['data'])) {
        return [
            'ok' => false,
            'error' => (string) ($res['error'] ?? 'Bridge request failed.'),
            'status' => (int) ($res['status'] ?? 0),
        ];
    }

    return [
        'ok' => true,
        'site' => [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'base_url' => (string) ($site['base_url'] ?? ''),
        ],
        'review' => $res['data'],
    ];
}

function leads_review_action($siteId, $runId, $action)
{
    $site = site_by_id((string) $siteId);
    if (!is_array($site)) {
        return ['ok' => false, 'error' => 'Site not found.'];
    }
    $route = '';
    if ($action === 'save') {
        $route = 'bridge/run-review/save';
    } elseif ($action === 'discard') {
        $route = 'bridge/run-review/discard';
    }
    if ($route === '') {
        return ['ok' => false, 'error' => 'Invalid review action.'];
    }

    $res = app_bridge_request($site, 'POST', $route, ['run_id' => (int) $runId]);
    if (empty($res['ok']) || !is_array($res['data'])) {
        return [
            'ok' => false,
            'error' => (string) ($res['error'] ?? 'Bridge request failed.'),
            'status' => (int) ($res['status'] ?? 0),
        ];
    }

    return [
        'ok' => true,
        'site' => [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
        ],
        'result' => $res['data'],
    ];
}

function leads_review_queue_bulk_action($action, $query = [])
{
    $mode = strtolower(trim((string) $action));
    if ($mode !== 'save' && $mode !== 'discard') {
        return ['ok' => false, 'error' => 'Invalid bulk action.'];
    }
    $snapshot = collect_leads_review_queue($query);
    $items = isset($snapshot['items']) && is_array($snapshot['items']) ? $snapshot['items'] : [];
    $results = [];
    $processed = 0;
    $succeeded = 0;
    foreach ($items as $item) {
        $siteId = (string) ($item['site_id'] ?? '');
        $runId = (int) ($item['run_id'] ?? 0);
        if ($siteId === '' || $runId <= 0) {
            continue;
        }
        $processed++;
        $result = leads_review_action($siteId, $runId, $mode);
        if (!empty($result['ok'])) {
            $succeeded++;
        }
        $results[] = [
            'site_id' => $siteId,
            'run_id' => $runId,
            'ok' => !empty($result['ok']),
            'error' => !empty($result['ok']) ? '' : (string) ($result['error'] ?? 'Review action failed.'),
            'result' => $result,
        ];
    }

    return [
        'ok' => true,
        'action' => $mode,
        'processed' => $processed,
        'succeeded' => $succeeded,
        'failed' => max(0, $processed - $succeeded),
        'filters' => [
            'review_status' => isset($snapshot['summary']['review_status']) ? (string) $snapshot['summary']['review_status'] : 'pending',
            'site_id' => isset($snapshot['summary']['site_id']) ? (string) $snapshot['summary']['site_id'] : '',
            'page' => isset($snapshot['summary']['page']) ? (int) $snapshot['summary']['page'] : 1,
            'limit' => isset($snapshot['summary']['limit']) ? (int) $snapshot['summary']['limit'] : 10,
        ],
        'results' => $results,
    ];
}

function leads_review_draft_update($siteId, $runId, $draftId, $fields)
{
    $site = site_by_id((string) $siteId);
    if (!is_array($site)) {
        return ['ok' => false, 'error' => 'Site not found.'];
    }
    $payload = array_merge([
        'run_id' => (int) $runId,
        'draft_id' => (int) $draftId,
    ], is_array($fields) ? $fields : []);
    $res = app_bridge_request($site, 'POST', 'bridge/run-review/draft-update', $payload);
    if (empty($res['ok']) || !is_array($res['data'])) {
        return [
            'ok' => false,
            'error' => (string) ($res['error'] ?? 'Bridge request failed.'),
            'status' => (int) ($res['status'] ?? 0),
        ];
    }

    return [
        'ok' => true,
        'site' => [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
        ],
        'result' => $res['data'],
    ];
}

function leads_review_duplicates_snapshot($siteId, $runId, $limit = 25)
{
    $detail = leads_review_detail_fetch($siteId, $runId);
    if (empty($detail['ok']) || empty($detail['review']) || !is_array($detail['review'])) {
        return [
            'ok' => false,
            'error' => (string) ($detail['error'] ?? 'Review detail unavailable.'),
        ];
    }

    $drafts = isset($detail['review']['draft_preview']) && is_array($detail['review']['draft_preview'])
        ? $detail['review']['draft_preview']
        : [];
    $approvedPayload = collect_approved_leads();
    $approvedLeads = isset($approvedPayload['leads']) && is_array($approvedPayload['leads']) ? $approvedPayload['leads'] : [];

    $websiteMap = [];
    $emailMap = [];
    $businessCityMap = [];
    foreach ($approvedLeads as $lead) {
        if (!is_array($lead)) {
            continue;
        }
        $websiteKey = strtolower(trim((string) ($lead['website'] ?? '')));
        $emailKey = strtolower(trim((string) ($lead['email'] ?? '')));
        $businessKey = strtolower(trim((string) ($lead['business_name'] ?? '')));
        $cityKey = strtolower(trim((string) ($lead['city'] ?? '')));
        if ($websiteKey !== '') {
            $websiteMap[$websiteKey][] = $lead;
        }
        if ($emailKey !== '') {
            $emailMap[$emailKey][] = $lead;
        }
        if ($businessKey !== '' && $cityKey !== '') {
            $businessCityMap[$businessKey . '|' . $cityKey][] = $lead;
        }
    }

    $items = [];
    $summary = [
        'draft_count' => count($drafts),
        'matched_drafts' => 0,
        'match_total' => 0,
        'website_matches' => 0,
        'email_matches' => 0,
        'business_city_matches' => 0,
    ];

    foreach ($drafts as $draft) {
        if (!is_array($draft)) {
            continue;
        }
        $matches = [];
        $seen = [];
        $websiteKey = strtolower(trim((string) ($draft['website'] ?? '')));
        $emailKey = strtolower(trim((string) ($draft['email'] ?? '')));
        $businessKey = strtolower(trim((string) ($draft['business_name'] ?? '')));
        $cityKey = strtolower(trim((string) ($draft['city'] ?? '')));
        $businessCityKey = ($businessKey !== '' && $cityKey !== '') ? ($businessKey . '|' . $cityKey) : '';
        $sources = [];
        if ($websiteKey !== '' && isset($websiteMap[$websiteKey])) {
            $sources[] = ['type' => 'website', 'rows' => $websiteMap[$websiteKey]];
        }
        if ($emailKey !== '' && isset($emailMap[$emailKey])) {
            $sources[] = ['type' => 'email', 'rows' => $emailMap[$emailKey]];
        }
        if ($businessCityKey !== '' && isset($businessCityMap[$businessCityKey])) {
            $sources[] = ['type' => 'business_city', 'rows' => $businessCityMap[$businessCityKey]];
        }

        foreach ($sources as $source) {
            foreach ($source['rows'] as $lead) {
                $matchKey = (string) ($lead['site_id'] ?? '') . ':' . (string) ($lead['lead_id'] ?? 0);
                if (!isset($seen[$matchKey])) {
                    $seen[$matchKey] = [
                        'lead' => $lead,
                        'match_types' => [],
                    ];
                }
                if (!in_array($source['type'], $seen[$matchKey]['match_types'], true)) {
                    $seen[$matchKey]['match_types'][] = $source['type'];
                }
            }
        }

        foreach ($seen as $match) {
            $lead = $match['lead'];
            $types = $match['match_types'];
            if (in_array('website', $types, true)) {
                $summary['website_matches']++;
            }
            if (in_array('email', $types, true)) {
                $summary['email_matches']++;
            }
            if (in_array('business_city', $types, true)) {
                $summary['business_city_matches']++;
            }
            $matches[] = [
                'match_types' => $types,
                'site_id' => (string) ($lead['site_id'] ?? ''),
                'site_label' => (string) ($lead['site_label'] ?? ''),
                'lead_id' => (int) ($lead['lead_id'] ?? 0),
                'business_name' => (string) ($lead['business_name'] ?? ''),
                'city' => (string) ($lead['city'] ?? ''),
                'category' => (string) ($lead['category'] ?? ''),
                'website' => (string) ($lead['website'] ?? ''),
                'email' => (string) ($lead['email'] ?? ''),
                'phone' => (string) ($lead['phone'] ?? ''),
                'status' => (string) ($lead['status'] ?? ''),
            ];
        }

        if (!empty($matches)) {
            $summary['matched_drafts']++;
        }
        $summary['match_total'] += count($matches);
        $items[] = [
            'draft_id' => (int) ($draft['id'] ?? 0),
            'business_name' => (string) ($draft['business_name'] ?? ''),
            'city' => (string) ($draft['city'] ?? ''),
            'category' => (string) ($draft['category'] ?? ''),
            'website' => (string) ($draft['website'] ?? ''),
            'email' => (string) ($draft['email'] ?? ''),
            'phone' => (string) ($draft['phone'] ?? ''),
            'match_count' => count($matches),
            'matches' => array_slice($matches, 0, max(1, $limit)),
        ];
    }

    usort($items, static function ($a, $b) {
        $left = (int) ($a['match_count'] ?? 0);
        $right = (int) ($b['match_count'] ?? 0);
        if ($left === $right) {
            return strcmp((string) ($a['business_name'] ?? ''), (string) ($b['business_name'] ?? ''));
        }
        return ($left < $right) ? 1 : -1;
    });

    return [
        'ok' => true,
        'site_id' => (string) $siteId,
        'run_id' => (int) $runId,
        'summary' => $summary,
        'items' => array_slice($items, 0, max(1, $limit)),
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

function social_schedule_queue_path()
{
    return app_storage_path('social_schedule_queue.json');
}

function social_activity_feed_path()
{
    return app_storage_path('social_activity_feed.json');
}

function social_inbox_threads_path()
{
    return app_storage_path('social_inbox_threads.json');
}

function social_inbox_watch_state_path()
{
    return app_storage_path('social_inbox_watch_state.json');
}

function social_inbox_watch_runs_path()
{
    return app_storage_path('social_inbox_watch_runs.json');
}

function social_watch_state_path()
{
    return app_storage_path('social_watch_state.json');
}

function social_watch_runs_path()
{
    return app_storage_path('social_watch_runs.json');
}

function social_schedule_snapshot($limit = 10)
{
    $rows = app_read_json_file(social_schedule_queue_path(), []);
    $connectors = app_read_json_file(social_connectors_path(), []);
    $indexedConnectors = [];
    foreach ($connectors as $connector) {
        if (!is_array($connector)) {
            continue;
        }
        $indexedConnectors[(string) ($connector['connector_id'] ?? '')] = $connector;
    }
    $now = time();
    $summary = [
        'total' => count($rows),
        'queued' => 0,
        'due' => 0,
        'overdue' => 0,
        'upcoming' => 0,
        'sent' => 0,
        'failed' => 0,
    ];
    $dueItems = [];
    $upcomingItems = [];
    $failedItems = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $status = strtolower((string) ($row['status'] ?? 'queued'));
        $scheduledFor = trim((string) ($row['scheduled_for'] ?? ''));
        $scheduledTs = $scheduledFor !== '' ? strtotime($scheduledFor) : false;
        $connectorIds = isset($row['connector_ids']) && is_array($row['connector_ids']) ? $row['connector_ids'] : [];
        $resolvedConnectors = [];
        foreach ($connectorIds as $connectorId) {
            if (isset($indexedConnectors[(string) $connectorId])) {
                $resolvedConnectors[] = (string) $connectorId;
            }
        }
        if ($status === 'queued') {
            $summary['queued']++;
            if ($scheduledTs !== false && $scheduledTs <= $now) {
                $summary['due']++;
                if (($now - $scheduledTs) > 300) {
                    $summary['overdue']++;
                }
                $dueItems[] = [
                    'schedule_id' => (string) ($row['schedule_id'] ?? ''),
                    'title' => (string) ($row['title'] ?? ''),
                    'scheduled_for' => $scheduledFor,
                    'connector_count' => count($connectorIds),
                    'resolved_connector_count' => count($resolvedConnectors),
                    'status' => $status,
                ];
            } else {
                $summary['upcoming']++;
                $upcomingItems[] = [
                    'schedule_id' => (string) ($row['schedule_id'] ?? ''),
                    'title' => (string) ($row['title'] ?? ''),
                    'scheduled_for' => $scheduledFor,
                    'connector_count' => count($connectorIds),
                    'resolved_connector_count' => count($resolvedConnectors),
                    'status' => $status,
                ];
            }
        } elseif ($status === 'sent') {
            $summary['sent']++;
        } elseif ($status === 'failed') {
            $summary['failed']++;
            $failedItems[] = [
                'schedule_id' => (string) ($row['schedule_id'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'scheduled_for' => $scheduledFor,
                'attempts' => (int) ($row['attempts'] ?? 0),
                'last_run_at' => (string) ($row['last_run_at'] ?? ''),
                'status' => $status,
            ];
        }
    }

    usort($dueItems, static function ($a, $b) {
        return strcmp((string) ($a['scheduled_for'] ?? ''), (string) ($b['scheduled_for'] ?? ''));
    });
    usort($upcomingItems, static function ($a, $b) {
        return strcmp((string) ($a['scheduled_for'] ?? ''), (string) ($b['scheduled_for'] ?? ''));
    });

    return [
        'summary' => $summary,
        'next_due' => isset($dueItems[0]) ? $dueItems[0] : null,
        'next_upcoming' => isset($upcomingItems[0]) ? $upcomingItems[0] : null,
        'due_items' => array_slice($dueItems, 0, $limit),
        'upcoming_items' => array_slice($upcomingItems, 0, $limit),
        'failed_items' => array_slice($failedItems, 0, $limit),
    ];
}

function social_schedule_list_snapshot($query = [])
{
    $rows = app_read_json_file(social_schedule_queue_path(), []);
    $limit = isset($query['limit']) ? (int) $query['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $limit = min($limit, 100);
    $page = isset($query['page']) ? (int) $query['page'] : 1;
    if ($page <= 0) {
        $page = 1;
    }
    $status = strtolower(trim((string) ($query['status'] ?? '')));
    $connectorId = strtolower(trim((string) ($query['connector_id'] ?? '')));
    $search = strtolower(trim((string) ($query['search'] ?? '')));

    $filtered = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $item = $row;
        $item['connector_ids'] = isset($row['connector_ids']) && is_array($row['connector_ids']) ? array_values($row['connector_ids']) : [];
        if ($status !== '' && strtolower((string) ($item['status'] ?? 'queued')) !== $status) {
            continue;
        }
        if ($connectorId !== '') {
            $matchedConnector = false;
            foreach ($item['connector_ids'] as $value) {
                if (strpos(strtolower((string) $value), $connectorId) !== false) {
                    $matchedConnector = true;
                    break;
                }
            }
            if (!$matchedConnector) {
                continue;
            }
        }
        if ($search !== '') {
            $haystacks = [
                (string) ($item['schedule_id'] ?? ''),
                (string) ($item['title'] ?? ''),
                (string) ($item['message'] ?? ''),
                (string) ($item['url'] ?? ''),
            ];
            $matched = false;
            foreach ($haystacks as $haystack) {
                if (strpos(strtolower($haystack), $search) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                continue;
            }
        }
        $filtered[] = $item;
    }

    usort($filtered, static function ($a, $b) {
        return strcmp((string) ($a['scheduled_for'] ?? ''), (string) ($b['scheduled_for'] ?? ''));
    });
    $offset = ($page - 1) * $limit;

    return [
        'summary' => [
            'total_count' => count($rows),
            'filtered_count' => count($filtered),
            'page' => $page,
            'limit' => $limit,
            'status' => $status,
            'connector_id' => $connectorId,
            'search' => $search,
        ],
        'items' => array_slice($filtered, $offset, $limit),
    ];
}

function social_schedule_detail_snapshot($scheduleId)
{
    $id = trim((string) $scheduleId);
    if ($id === '') {
        return ['ok' => false, 'error' => 'schedule_id is required.'];
    }
    $rows = app_read_json_file(social_schedule_queue_path(), []);
    foreach ($rows as $row) {
        if (!is_array($row) || (string) ($row['schedule_id'] ?? '') !== $id) {
            continue;
        }
        $item = $row;
        $item['connector_ids'] = isset($row['connector_ids']) && is_array($row['connector_ids']) ? array_values($row['connector_ids']) : [];
        return [
            'ok' => true,
            'item' => $item,
            'meta' => [
                'connector_count' => count($item['connector_ids']),
                'is_due' => !empty($item['scheduled_for']) && strtotime((string) $item['scheduled_for']) <= time() ? 1 : 0,
            ],
        ];
    }
    return ['ok' => false, 'error' => 'Schedule item not found.'];
}

function social_schedule_bulk_update($payload)
{
    $data = is_array($payload) ? $payload : [];
    $snapshot = social_schedule_list_snapshot([
        'status' => isset($data['filter_status']) ? (string) $data['filter_status'] : '',
        'connector_id' => isset($data['filter_connector_id']) ? (string) $data['filter_connector_id'] : '',
        'search' => isset($data['filter_search']) ? (string) $data['filter_search'] : '',
        'page' => isset($data['filter_page']) ? (int) $data['filter_page'] : 1,
        'limit' => isset($data['filter_limit']) ? (int) $data['filter_limit'] : 10,
    ]);
    $items = isset($snapshot['items']) && is_array($snapshot['items']) ? $snapshot['items'] : [];
    $statusProvided = array_key_exists('status', $data);
    $timeProvided = array_key_exists('scheduled_for', $data);
    $nextStatus = strtolower(trim((string) ($data['status'] ?? '')));
    $nextScheduleFor = trim((string) ($data['scheduled_for'] ?? ''));
    if ($statusProvided && $nextStatus !== '' && !in_array($nextStatus, ['queued', 'sent', 'failed'], true)) {
        return ['ok' => false, 'error' => 'Invalid schedule status.'];
    }
    if (!$statusProvided && !$timeProvided) {
        return ['ok' => false, 'error' => 'At least one bulk update field is required.'];
    }

    $rows = app_read_json_file(social_schedule_queue_path(), []);
    $scheduleIds = array_flip(array_map(static function ($item) {
        return (string) ($item['schedule_id'] ?? '');
    }, $items));
    $updated = 0;
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $scheduleId = (string) ($row['schedule_id'] ?? '');
        if ($scheduleId === '' || !isset($scheduleIds[$scheduleId])) {
            continue;
        }
        $changed = false;
        if ($statusProvided && $nextStatus !== '' && $nextStatus !== (string) ($row['status'] ?? 'queued')) {
            $row['status'] = $nextStatus;
            $changed = true;
        }
        if ($timeProvided && $nextScheduleFor !== '' && $nextScheduleFor !== (string) ($row['scheduled_for'] ?? '')) {
            $row['scheduled_for'] = $nextScheduleFor;
            $changed = true;
        }
        if ($changed) {
            $row['updated_at'] = gmdate('c');
            $rows[$index] = $row;
            $updated++;
        }
    }
    usort($rows, static function ($a, $b) {
        return strcmp((string) ($a['scheduled_for'] ?? ''), (string) ($b['scheduled_for'] ?? ''));
    });
    app_write_json_file(social_schedule_queue_path(), array_slice($rows, 0, 500));
    return [
        'ok' => true,
        'processed' => count($items),
        'updated' => $updated,
        'filters' => isset($snapshot['summary']) && is_array($snapshot['summary']) ? $snapshot['summary'] : [],
        'items' => $items,
    ];
}

function social_inbox_summary_snapshot($limit = 10)
{
    $rows = social_inbox_threads_rows(true);
    $summary = [
        'total' => count($rows),
        'open' => 0,
        'pending' => 0,
        'replied' => 0,
        'closed' => 0,
        'backlog' => 0,
    ];
    $providerMap = [];
    $openThreads = [];
    $backlogThreads = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $row = social_normalize_inbox_thread($row);
        $status = strtolower((string) ($row['status'] ?? 'open'));
        $provider = (string) ($row['provider'] ?? 'unknown');
        if (!isset($providerMap[$provider])) {
            $providerMap[$provider] = [
                'provider' => $provider,
                'total' => 0,
                'open' => 0,
                'pending' => 0,
                'replied' => 0,
                'closed' => 0,
                'backlog' => 0,
            ];
        }
        $providerMap[$provider]['total']++;
        if ($status === 'replied') {
            $summary['replied']++;
            $providerMap[$provider]['replied']++;
        } elseif ($status === 'closed') {
            $summary['closed']++;
            $providerMap[$provider]['closed']++;
        } elseif ($status === 'pending') {
            $summary['pending']++;
            $summary['backlog']++;
            $providerMap[$provider]['pending']++;
            $providerMap[$provider]['backlog']++;
            $backlogThreads[] = [
                'thread_id' => (string) ($row['thread_id'] ?? ''),
                'provider' => $provider,
                'account_label' => (string) ($row['account_label'] ?? ''),
                'subject' => (string) ($row['subject'] ?? ''),
                'priority' => (string) ($row['priority'] ?? 'normal'),
                'owner' => (string) ($row['owner'] ?? ''),
                'status' => $status,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
        } else {
            $summary['open']++;
            $summary['backlog']++;
            $providerMap[$provider]['open']++;
            $providerMap[$provider]['backlog']++;
            $openThreads[] = [
                'thread_id' => (string) ($row['thread_id'] ?? ''),
                'provider' => $provider,
                'account_label' => (string) ($row['account_label'] ?? ''),
                'subject' => (string) ($row['subject'] ?? ''),
                'priority' => (string) ($row['priority'] ?? 'normal'),
                'owner' => (string) ($row['owner'] ?? ''),
                'status' => $status,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
            $backlogThreads[] = [
                'thread_id' => (string) ($row['thread_id'] ?? ''),
                'provider' => $provider,
                'account_label' => (string) ($row['account_label'] ?? ''),
                'subject' => (string) ($row['subject'] ?? ''),
                'priority' => (string) ($row['priority'] ?? 'normal'),
                'owner' => (string) ($row['owner'] ?? ''),
                'status' => $status,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
        }
    }
    usort($openThreads, static function ($a, $b) {
        return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''));
    });
    usort($backlogThreads, static function ($a, $b) {
        return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''));
    });
    return [
        'summary' => $summary,
        'providers' => array_values($providerMap),
        'oldest_open' => isset($openThreads[0]) ? $openThreads[0] : null,
        'oldest_backlog' => isset($backlogThreads[0]) ? $backlogThreads[0] : null,
        'open_threads' => array_slice($openThreads, 0, $limit),
        'backlog_threads' => array_slice($backlogThreads, 0, $limit),
    ];
}

function social_inbox_status_values()
{
    return ['open', 'pending', 'replied', 'closed'];
}

function social_inbox_priority_values()
{
    return ['low', 'normal', 'high'];
}

function social_normalize_inbox_thread($row)
{
    $row = is_array($row) ? $row : [];
    $createdAt = (string) ($row['created_at'] ?? gmdate('c'));
    $updatedAt = (string) ($row['updated_at'] ?? $createdAt);
    $status = strtolower(trim((string) ($row['status'] ?? 'open')));
    if (!in_array($status, social_inbox_status_values(), true)) {
        $status = 'open';
    }
    $priority = strtolower(trim((string) ($row['priority'] ?? 'normal')));
    if (!in_array($priority, social_inbox_priority_values(), true)) {
        $priority = 'normal';
    }

    return [
        'thread_id' => (string) ($row['thread_id'] ?? ''),
        'connector_id' => (string) ($row['connector_id'] ?? ''),
        'provider' => (string) ($row['provider'] ?? ''),
        'account_label' => (string) ($row['account_label'] ?? ''),
        'from_name' => (string) ($row['from_name'] ?? ''),
        'subject' => (string) ($row['subject'] ?? ''),
        'message' => (string) ($row['message'] ?? ''),
        'status' => $status,
        'priority' => $priority,
        'owner' => trim((string) ($row['owner'] ?? '')),
        'internal_note' => trim((string) ($row['internal_note'] ?? '')),
        'reply_message' => (string) ($row['reply_message'] ?? ''),
        'last_reply_at' => (string) ($row['last_reply_at'] ?? ''),
        'last_status_at' => (string) ($row['last_status_at'] ?? $updatedAt),
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
    ];
}

function social_operations_snapshot($limit = 5)
{
    $watchState = app_read_json_file(social_watch_state_path(), []);
    $watchRuns = app_read_json_file(social_watch_runs_path(), []);
    $schedule = social_schedule_snapshot($limit);
    $inbox = social_inbox_summary_snapshot($limit);
    $inboxWorkload = social_inbox_workload_snapshot($limit);
    $draftPlan = social_draft_delivery_plan(null, $limit);
    $draftValidation = social_draft_validation_snapshot(collect_social_drafts());
    $retryQueue = app_read_json_file(social_retry_queue_path(), []);
    $syncLog = app_read_json_file(social_sync_log_path(), []);

    return [
        'generated_at' => gmdate('c'),
        'summary' => [
            'watch_blocked_connectors' => (int) (($watchState['summary']['blocked_connectors'] ?? 0)),
            'watch_expired_connectors' => (int) (($watchState['summary']['expired_connectors'] ?? 0)),
            'draft_validation_blocked_connectors' => (int) (($draftValidation['summary']['blocked_connectors'] ?? 0)),
            'schedule_due_items' => (int) (($schedule['summary']['due'] ?? 0)),
            'schedule_failed_items' => (int) (($schedule['summary']['failed'] ?? 0)),
            'retry_backlog' => count($retryQueue),
            'draft_ready_pairs' => (int) (($draftPlan['summary']['ready_pairs'] ?? 0)),
            'draft_blocked_pairs' => (int) (($draftPlan['summary']['blocked_pairs'] ?? 0)),
            'open_inbox_threads' => (int) (($inbox['summary']['open'] ?? 0)),
            'pending_inbox_threads' => (int) (($inbox['summary']['pending'] ?? 0)),
            'inbox_backlog_threads' => (int) (($inbox['summary']['backlog'] ?? 0)),
            'high_priority_inbox_backlog' => (int) (($inboxWorkload['summary']['high_priority_backlog'] ?? 0)),
            'unassigned_inbox_backlog' => (int) (($inboxWorkload['summary']['unassigned_backlog'] ?? 0)),
        ],
        'latest_watch_run' => isset($watchRuns[0]) && is_array($watchRuns[0]) ? $watchRuns[0] : null,
        'latest_sync' => isset($syncLog[0]) && is_array($syncLog[0]) ? $syncLog[0] : null,
        'schedule' => $schedule,
        'inbox' => $inbox,
        'inbox_workload' => $inboxWorkload,
        'draft_delivery_plan' => $draftPlan,
        'draft_validation' => $draftValidation,
        'retry_queue_count' => count($retryQueue),
    ];
}

function social_inbox_workload_snapshot($limit = 10)
{
    $rows = social_inbox_threads_rows(true);
    $summary = [
        'backlog_total' => 0,
        'high_priority_backlog' => 0,
        'unassigned_backlog' => 0,
    ];
    $priorityCounts = [
        'high' => 0,
        'normal' => 0,
        'low' => 0,
    ];
    $ownerMap = [];
    $unassigned = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $row = social_normalize_inbox_thread($row);
        $status = (string) ($row['status'] ?? 'open');
        if (!in_array($status, ['open', 'pending'], true)) {
            continue;
        }
        $summary['backlog_total']++;
        $priority = (string) ($row['priority'] ?? 'normal');
        if (!isset($priorityCounts[$priority])) {
            $priorityCounts[$priority] = 0;
        }
        $priorityCounts[$priority]++;
        if ($priority === 'high') {
            $summary['high_priority_backlog']++;
        }

        $owner = trim((string) ($row['owner'] ?? ''));
        $ownerKey = $owner !== '' ? strtolower($owner) : '';
        if ($ownerKey === '') {
            $summary['unassigned_backlog']++;
            $unassigned[] = [
                'thread_id' => (string) ($row['thread_id'] ?? ''),
                'provider' => (string) ($row['provider'] ?? ''),
                'account_label' => (string) ($row['account_label'] ?? ''),
                'subject' => (string) ($row['subject'] ?? ''),
                'priority' => $priority,
                'status' => $status,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
            continue;
        }
        if (!isset($ownerMap[$ownerKey])) {
            $ownerMap[$ownerKey] = [
                'owner' => $owner,
                'backlog' => 0,
                'high_priority' => 0,
                'open' => 0,
                'pending' => 0,
            ];
        }
        $ownerMap[$ownerKey]['backlog']++;
        if ($priority === 'high') {
            $ownerMap[$ownerKey]['high_priority']++;
        }
        if ($status === 'pending') {
            $ownerMap[$ownerKey]['pending']++;
        } else {
            $ownerMap[$ownerKey]['open']++;
        }
    }

    $owners = array_values($ownerMap);
    usort($owners, static function ($a, $b) {
        $left = (int) ($a['backlog'] ?? 0);
        $right = (int) ($b['backlog'] ?? 0);
        if ($left === $right) {
            return strcmp((string) ($a['owner'] ?? ''), (string) ($b['owner'] ?? ''));
        }
        return ($left < $right) ? 1 : -1;
    });
    usort($unassigned, static function ($a, $b) {
        $leftPriority = (string) ($a['priority'] ?? 'normal');
        $rightPriority = (string) ($b['priority'] ?? 'normal');
        $priorityOrder = ['high' => 3, 'normal' => 2, 'low' => 1];
        $leftScore = isset($priorityOrder[$leftPriority]) ? $priorityOrder[$leftPriority] : 0;
        $rightScore = isset($priorityOrder[$rightPriority]) ? $priorityOrder[$rightPriority] : 0;
        if ($leftScore === $rightScore) {
            return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''));
        }
        return ($leftScore < $rightScore) ? 1 : -1;
    });

    return [
        'summary' => $summary,
        'priority_counts' => $priorityCounts,
        'owners' => array_slice($owners, 0, $limit),
        'unassigned_threads' => array_slice($unassigned, 0, $limit),
    ];
}

function social_inbox_list_snapshot($query = [])
{
    $rows = social_inbox_threads_rows(true);
    $limit = isset($query['limit']) ? (int) $query['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $limit = min($limit, 100);
    $page = isset($query['page']) ? (int) $query['page'] : 1;
    if ($page <= 0) {
        $page = 1;
    }
    $status = strtolower(trim((string) ($query['status'] ?? '')));
    $priority = strtolower(trim((string) ($query['priority'] ?? '')));
    $provider = strtolower(trim((string) ($query['provider'] ?? '')));
    $owner = strtolower(trim((string) ($query['owner'] ?? '')));
    $search = strtolower(trim((string) ($query['search'] ?? '')));

    $filtered = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $item = social_normalize_inbox_thread($row);
        if ($status !== '' && strtolower((string) ($item['status'] ?? '')) !== $status) {
            continue;
        }
        if ($priority !== '' && strtolower((string) ($item['priority'] ?? '')) !== $priority) {
            continue;
        }
        if ($provider !== '' && strpos(strtolower((string) ($item['provider'] ?? '')), $provider) === false) {
            continue;
        }
        if ($owner !== '' && strpos(strtolower((string) ($item['owner'] ?? '')), $owner) === false) {
            continue;
        }
        if ($search !== '') {
            $haystacks = [
                (string) ($item['thread_id'] ?? ''),
                (string) ($item['from_name'] ?? ''),
                (string) ($item['subject'] ?? ''),
                (string) ($item['message'] ?? ''),
                (string) ($item['account_label'] ?? ''),
                (string) ($item['owner'] ?? ''),
            ];
            $matched = false;
            foreach ($haystacks as $haystack) {
                if (strpos(strtolower($haystack), $search) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                continue;
            }
        }
        $filtered[] = $item;
    }

    usort($filtered, static function ($a, $b) {
        return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
    });
    $offset = ($page - 1) * $limit;

    return [
        'summary' => [
            'total_count' => count($rows),
            'filtered_count' => count($filtered),
            'page' => $page,
            'limit' => $limit,
            'status' => $status,
            'priority' => $priority,
            'provider' => $provider,
            'owner' => $owner,
            'search' => $search,
        ],
        'items' => array_slice($filtered, $offset, $limit),
    ];
}

function social_inbox_thread_detail_snapshot($threadId)
{
    $id = trim((string) $threadId);
    if ($id === '') {
        return ['ok' => false, 'error' => 'thread_id is required.'];
    }
    $rows = social_inbox_threads_rows(true);
    foreach ($rows as $row) {
        if (!is_array($row) || (string) ($row['thread_id'] ?? '') !== $id) {
            continue;
        }
        $thread = social_normalize_inbox_thread($row);
        $connector = social_connector_by_id((string) ($thread['connector_id'] ?? ''));
        $decorated = is_array($connector) ? decorate_social_connector($connector) : null;
        $createdAt = strtotime((string) ($thread['created_at'] ?? ''));
        $updatedAt = strtotime((string) ($thread['updated_at'] ?? ''));
        return [
            'ok' => true,
            'thread' => $thread,
            'connector' => $decorated,
            'meta' => [
                'age_hours' => $createdAt ? round(max(0, time() - $createdAt) / 3600, 2) : 0,
                'updated_hours' => $updatedAt ? round(max(0, time() - $updatedAt) / 3600, 2) : 0,
                'can_reply' => is_array($decorated) && in_array('can_reply_inbox', (array) ($decorated['capabilities_enabled'] ?? []), true) ? 1 : 0,
            ],
        ];
    }
    return ['ok' => false, 'error' => 'Inbox thread not found.'];
}

function social_inbox_bulk_update($payload)
{
    $data = is_array($payload) ? $payload : [];
    $snapshot = social_inbox_list_snapshot([
        'status' => isset($data['filter_status']) ? (string) $data['filter_status'] : '',
        'priority' => isset($data['filter_priority']) ? (string) $data['filter_priority'] : '',
        'provider' => isset($data['filter_provider']) ? (string) $data['filter_provider'] : '',
        'owner' => isset($data['filter_owner']) ? (string) $data['filter_owner'] : '',
        'search' => isset($data['filter_search']) ? (string) $data['filter_search'] : '',
        'page' => isset($data['filter_page']) ? (int) $data['filter_page'] : 1,
        'limit' => isset($data['filter_limit']) ? (int) $data['filter_limit'] : 10,
    ]);
    $items = isset($snapshot['items']) && is_array($snapshot['items']) ? $snapshot['items'] : [];
    $statusProvided = array_key_exists('status', $data);
    $priorityProvided = array_key_exists('priority', $data);
    $ownerProvided = array_key_exists('owner', $data);
    $noteProvided = array_key_exists('internal_note', $data);
    $nextStatus = strtolower(trim((string) ($data['status'] ?? '')));
    $nextPriority = strtolower(trim((string) ($data['priority'] ?? '')));
    if ($statusProvided && $nextStatus !== '' && !in_array($nextStatus, social_inbox_status_values(), true)) {
        return ['ok' => false, 'error' => 'Invalid inbox status.'];
    }
    if ($priorityProvided && $nextPriority !== '' && !in_array($nextPriority, social_inbox_priority_values(), true)) {
        return ['ok' => false, 'error' => 'Invalid inbox priority.'];
    }
    if (!$statusProvided && !$priorityProvided && !$ownerProvided && !$noteProvided) {
        return ['ok' => false, 'error' => 'At least one bulk update field is required.'];
    }

    $rows = social_inbox_threads_rows(true);
    $threadIds = array_flip(array_map(static function ($item) {
        return (string) ($item['thread_id'] ?? '');
    }, $items));
    $updated = 0;
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $threadId = (string) ($row['thread_id'] ?? '');
        if ($threadId === '' || !isset($threadIds[$threadId])) {
            continue;
        }
        $row = social_normalize_inbox_thread($row);
        $changed = false;
        if ($statusProvided && $nextStatus !== '' && $nextStatus !== (string) ($row['status'] ?? 'open')) {
            $row['status'] = $nextStatus;
            $row['last_status_at'] = gmdate('c');
            $changed = true;
        }
        if ($priorityProvided && $nextPriority !== '' && $nextPriority !== (string) ($row['priority'] ?? 'normal')) {
            $row['priority'] = $nextPriority;
            $changed = true;
        }
        if ($ownerProvided) {
            $ownerValue = substr(trim((string) ($data['owner'] ?? '')), 0, 120);
            if ($ownerValue !== (string) ($row['owner'] ?? '')) {
                $row['owner'] = $ownerValue;
                $changed = true;
            }
        }
        if ($noteProvided) {
            $noteValue = substr(trim((string) ($data['internal_note'] ?? '')), 0, 1000);
            if ($noteValue !== (string) ($row['internal_note'] ?? '')) {
                $row['internal_note'] = $noteValue;
                $changed = true;
            }
        }
        if ($changed) {
            $row['updated_at'] = gmdate('c');
            $rows[$index] = $row;
            $updated++;
        }
    }

    app_write_json_file(social_inbox_threads_path(), $rows);
    return [
        'ok' => true,
        'processed' => count($items),
        'updated' => $updated,
        'filters' => isset($snapshot['summary']) && is_array($snapshot['summary']) ? $snapshot['summary'] : [],
        'items' => $items,
    ];
}

function social_inbox_watch_snapshot($source = 'manual', $emitNotifications = true, $staleAfterHours = 24)
{
    $rows = social_inbox_threads_rows(true);
    $state = app_read_json_file(social_inbox_watch_state_path(), []);
    $previousSummary = isset($state['summary']) && is_array($state['summary']) ? $state['summary'] : [];
    $workload = social_inbox_workload_snapshot(10);
    $summary = [
        'backlog_total' => (int) ($workload['summary']['backlog_total'] ?? 0),
        'high_priority_backlog' => (int) ($workload['summary']['high_priority_backlog'] ?? 0),
        'unassigned_backlog' => (int) ($workload['summary']['unassigned_backlog'] ?? 0),
        'stale_backlog' => 0,
        'attention_required' => 0,
    ];
    $staleThreads = [];
    $thresholdSeconds = max(1, (int) $staleAfterHours) * HOUR_IN_SECONDS;
    $nowTs = time();

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $row = social_normalize_inbox_thread($row);
        $status = (string) ($row['status'] ?? 'open');
        if (!in_array($status, ['open', 'pending'], true)) {
            continue;
        }
        $referenceAt = (string) ($row['updated_at'] ?? ($row['created_at'] ?? ''));
        $referenceTs = $referenceAt !== '' ? strtotime($referenceAt) : false;
        if ($referenceTs === false) {
            continue;
        }
        $ageHours = (int) floor(max(0, $nowTs - $referenceTs) / HOUR_IN_SECONDS);
        if (($nowTs - $referenceTs) < $thresholdSeconds) {
            continue;
        }
        $summary['stale_backlog']++;
        $staleThreads[] = [
            'thread_id' => (string) ($row['thread_id'] ?? ''),
            'provider' => (string) ($row['provider'] ?? ''),
            'account_label' => (string) ($row['account_label'] ?? ''),
            'subject' => (string) ($row['subject'] ?? ''),
            'status' => $status,
            'priority' => (string) ($row['priority'] ?? 'normal'),
            'owner' => (string) ($row['owner'] ?? ''),
            'age_hours' => $ageHours,
            'updated_at' => $referenceAt,
        ];
    }

    usort($staleThreads, static function ($a, $b) {
        $left = (int) ($a['age_hours'] ?? 0);
        $right = (int) ($b['age_hours'] ?? 0);
        if ($left === $right) {
            return strcmp((string) ($a['thread_id'] ?? ''), (string) ($b['thread_id'] ?? ''));
        }
        return ($left < $right) ? 1 : -1;
    });
    $summary['attention_required'] = ($summary['high_priority_backlog'] > 0 || $summary['unassigned_backlog'] > 0 || $summary['stale_backlog'] > 0) ? 1 : 0;

    if ($emitNotifications) {
        $previousAttention = (int) ($previousSummary['attention_required'] ?? 0);
        $previousStale = (int) ($previousSummary['stale_backlog'] ?? 0);
        $previousUnassigned = (int) ($previousSummary['unassigned_backlog'] ?? 0);
        $previousHighPriority = (int) ($previousSummary['high_priority_backlog'] ?? 0);

        if ($previousAttention === 0 && $summary['attention_required'] === 1) {
            push_notification('warning', 'Social inbox requires attention.', [
                'source' => (string) $source,
                'summary' => $summary,
            ]);
        } elseif ($previousAttention === 1 && $summary['attention_required'] === 0) {
            push_notification('success', 'Social inbox attention queue cleared.', [
                'source' => (string) $source,
                'summary' => $summary,
            ]);
        }
        if ($previousStale === 0 && $summary['stale_backlog'] > 0) {
            push_notification('warning', 'Social inbox has stale backlog threads.', [
                'source' => (string) $source,
                'stale_backlog' => $summary['stale_backlog'],
                'threads' => array_slice($staleThreads, 0, 5),
            ]);
        }
        if ($previousUnassigned === 0 && $summary['unassigned_backlog'] > 0) {
            push_notification('info', 'Social inbox has unassigned backlog threads.', [
                'source' => (string) $source,
                'unassigned_backlog' => $summary['unassigned_backlog'],
            ]);
        }
        if ($previousHighPriority === 0 && $summary['high_priority_backlog'] > 0) {
            push_notification('warning', 'Social inbox has high priority backlog threads.', [
                'source' => (string) $source,
                'high_priority_backlog' => $summary['high_priority_backlog'],
            ]);
        }
    }

    $run = [
        'run_id' => 'social_inbox_watch_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'source' => (string) $source,
        'created_at' => gmdate('c'),
        'stale_after_hours' => max(1, (int) $staleAfterHours),
        'summary' => $summary,
        'stale_threads' => array_slice($staleThreads, 0, 25),
        'owners' => $workload['owners'],
        'priority_counts' => $workload['priority_counts'],
    ];
    $runs = app_read_json_file(social_inbox_watch_runs_path(), []);
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 200);
    app_write_json_file(social_inbox_watch_runs_path(), $runs);

    $nextState = [
        'last_checked_at' => gmdate('c'),
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => (string) $source,
        'summary' => $summary,
        'stale_after_hours' => max(1, (int) $staleAfterHours),
    ];
    app_write_json_file(social_inbox_watch_state_path(), $nextState);
    audit_event('social', 'inbox.watch', [
        'run_id' => (string) ($run['run_id'] ?? ''),
        'source' => (string) $source,
        'summary' => $summary,
    ]);

    return [
        'run' => $run,
        'state' => $nextState,
    ];
}

function social_schedule_target_validation_snapshot($connectorIds)
{
    $ids = [];
    if (is_array($connectorIds)) {
        foreach ($connectorIds as $value) {
            $id = preg_replace('/[^a-z0-9_\-]/i', '', (string) $value);
            if ($id !== '' && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
    }
    $summary = [
        'requested_connector_count' => count($ids),
        'valid_connector_count' => 0,
        'invalid_connector_count' => 0,
        'uses_default_targets' => empty($ids) ? 1 : 0,
    ];
    $items = [];
    $connectors = app_read_json_file(social_connectors_path(), []);
    $activeSchedulable = [];

    foreach ($connectors as $connector) {
        if (!is_array($connector)) {
            continue;
        }
        $decorated = decorate_social_connector($connector);
        $caps = isset($decorated['capabilities_enabled']) && is_array($decorated['capabilities_enabled']) ? $decorated['capabilities_enabled'] : [];
        $status = strtolower((string) ($decorated['status'] ?? 'planned'));
        if (in_array($status, ['active', 'enabled'], true) && in_array('can_schedule', $caps, true)) {
            $activeSchedulable[] = (string) ($decorated['connector_id'] ?? '');
        }
    }

    if (empty($ids)) {
        return [
            'ok' => !empty($activeSchedulable),
            'summary' => $summary + [
                'valid_connector_count' => count($activeSchedulable),
                'invalid_connector_count' => empty($activeSchedulable) ? 1 : 0,
            ],
            'items' => empty($activeSchedulable) ? [[
                'connector_id' => '',
                'provider' => '',
                'account_label' => 'Default active connectors',
                'valid' => 0,
                'issues' => ['no_active_schedulable_connectors'],
            ]] : [],
        ];
    }

    foreach ($ids as $connectorId) {
        $connector = social_connector_by_id($connectorId);
        $issues = [];
        $provider = '';
        $accountLabel = $connectorId;
        if (!is_array($connector)) {
            $issues[] = 'connector_not_found';
        } else {
            $decorated = decorate_social_connector($connector);
            $provider = (string) ($decorated['provider'] ?? '');
            $accountLabel = (string) ($decorated['account_label'] ?? $connectorId);
            $caps = isset($decorated['capabilities_enabled']) && is_array($decorated['capabilities_enabled']) ? $decorated['capabilities_enabled'] : [];
            $status = strtolower((string) ($decorated['status'] ?? 'planned'));
            if (!in_array($status, ['active', 'enabled'], true)) {
                $issues[] = 'connector_inactive';
            }
            if (!in_array('can_schedule', $caps, true)) {
                $issues[] = 'schedule_capability_missing';
            }
            $expiresAt = trim((string) ($decorated['expires_at'] ?? ''));
            if ($expiresAt !== '') {
                $expiryTs = strtotime($expiresAt);
                if ($expiryTs !== false && $expiryTs <= time()) {
                    $issues[] = 'credential_expired';
                }
            }
        }
        if (empty($issues)) {
            $summary['valid_connector_count']++;
        } else {
            $summary['invalid_connector_count']++;
        }
        $items[] = [
            'connector_id' => $connectorId,
            'provider' => $provider,
            'account_label' => $accountLabel,
            'valid' => empty($issues) ? 1 : 0,
            'issues' => $issues,
        ];
    }

    return [
        'ok' => $summary['invalid_connector_count'] === 0,
        'summary' => $summary,
        'items' => $items,
    ];
}

function social_platform_catalog()
{
    return [
        [
            'provider' => 'facebook',
            'label' => 'Facebook',
            'family' => 'meta',
            'default_type' => 'external_api',
            'auth_modes' => ['oauth2'],
            'capabilities' => ['can_publish_post', 'can_publish_video', 'can_schedule', 'can_read_inbox', 'can_reply_inbox', 'can_manage_ads', 'can_fetch_analytics'],
            'theme' => ['accent' => '#1877f2', 'surface' => 'facebook'],
        ],
        [
            'provider' => 'instagram',
            'label' => 'Instagram',
            'family' => 'meta',
            'default_type' => 'external_api',
            'auth_modes' => ['oauth2'],
            'capabilities' => ['can_publish_post', 'can_publish_video', 'can_schedule', 'can_read_inbox', 'can_reply_inbox', 'can_fetch_analytics'],
            'theme' => ['accent' => '#e4405f', 'surface' => 'instagram'],
        ],
        [
            'provider' => 'linkedin',
            'label' => 'LinkedIn',
            'family' => 'professional',
            'default_type' => 'external_api',
            'auth_modes' => ['oauth2'],
            'capabilities' => ['can_publish_post', 'can_schedule', 'can_read_inbox', 'can_reply_inbox', 'can_fetch_analytics'],
            'theme' => ['accent' => '#0a66c2', 'surface' => 'linkedin'],
        ],
        [
            'provider' => 'x',
            'label' => 'X',
            'family' => 'social',
            'default_type' => 'external_api',
            'auth_modes' => ['oauth2', 'token'],
            'capabilities' => ['can_publish_post', 'can_publish_video', 'can_schedule', 'can_read_inbox', 'can_reply_inbox', 'can_fetch_analytics'],
            'theme' => ['accent' => '#111111', 'surface' => 'x'],
        ],
        [
            'provider' => 'youtube',
            'label' => 'YouTube',
            'family' => 'video',
            'default_type' => 'external_api',
            'auth_modes' => ['oauth2'],
            'capabilities' => ['can_publish_video', 'can_schedule', 'can_fetch_analytics'],
            'theme' => ['accent' => '#ff0000', 'surface' => 'youtube'],
        ],
        [
            'provider' => 'tiktok',
            'label' => 'TikTok',
            'family' => 'video',
            'default_type' => 'external_api',
            'auth_modes' => ['oauth2'],
            'capabilities' => ['can_publish_video', 'can_schedule', 'can_read_inbox', 'can_reply_inbox', 'can_fetch_analytics'],
            'theme' => ['accent' => '#00f2ea', 'surface' => 'tiktok'],
        ],
        [
            'provider' => 'reddit',
            'label' => 'Reddit',
            'family' => 'forum',
            'default_type' => 'external_api',
            'auth_modes' => ['oauth2', 'token'],
            'capabilities' => ['can_publish_post', 'can_schedule', 'can_read_inbox', 'can_reply_inbox', 'can_fetch_analytics'],
            'theme' => ['accent' => '#ff4500', 'surface' => 'reddit'],
        ],
        [
            'provider' => 'discourse',
            'label' => 'Discourse',
            'family' => 'forum',
            'default_type' => 'external_api',
            'auth_modes' => ['api_key', 'token'],
            'capabilities' => ['can_publish_post', 'can_schedule', 'can_read_inbox', 'can_reply_inbox'],
            'theme' => ['accent' => '#3a5ea7', 'surface' => 'discourse'],
        ],
        [
            'provider' => 'wordpress_social_bridge',
            'label' => 'WordPress Social Bridge',
            'family' => 'bridge',
            'default_type' => 'wordpress_plugin',
            'auth_modes' => ['token'],
            'capabilities' => ['can_publish_post', 'can_schedule', 'can_read_inbox'],
            'theme' => ['accent' => '#21759b', 'surface' => 'wordpress'],
        ],
        [
            'provider' => 'social_webhook',
            'label' => 'Generic Social Webhook',
            'family' => 'generic',
            'default_type' => 'external_api',
            'auth_modes' => ['api_key', 'token'],
            'capabilities' => ['can_publish_post', 'can_publish_video', 'can_schedule'],
            'theme' => ['accent' => '#f59e0b', 'surface' => 'generic'],
        ],
    ];
}

function social_platform_profile($provider)
{
    $needle = strtolower(trim((string) $provider));
    foreach (social_platform_catalog() as $item) {
        if ((string) ($item['provider'] ?? '') === $needle) {
            return $item;
        }
    }

    $label = $needle !== '' ? ucwords(str_replace(['_', '-'], ' ', $needle)) : 'Custom';
    return [
        'provider' => $needle !== '' ? $needle : 'custom',
        'label' => $label,
        'family' => 'custom',
        'default_type' => 'external_api',
        'auth_modes' => ['api_key', 'oauth2', 'token'],
        'capabilities' => [],
        'theme' => ['accent' => '#64748b', 'surface' => 'custom'],
    ];
}

function normalize_social_capabilities($provider, $capabilities)
{
    $profile = social_platform_profile($provider);
    $supported = isset($profile['capabilities']) && is_array($profile['capabilities']) ? $profile['capabilities'] : [];
    $clean = [];
    if (is_array($capabilities)) {
        foreach ($capabilities as $value) {
            $name = preg_replace('/[^a-z0-9_\-]/i', '', strtolower(trim((string) $value)));
            if ($name !== '' && !in_array($name, $clean, true)) {
                $clean[] = $name;
            }
        }
    }
    if (empty($supported)) {
        return $clean;
    }
    if (empty($clean)) {
        return $supported;
    }
    return array_values(array_filter($supported, static function ($item) use ($clean) {
        return in_array((string) $item, $clean, true);
    }));
}

function decorate_social_connector($connector)
{
    $masked = mask_connector($connector);
    $profile = social_platform_profile((string) ($masked['provider'] ?? 'custom'));
    $enabled = normalize_social_capabilities((string) ($profile['provider'] ?? 'custom'), isset($masked['capabilities']) && is_array($masked['capabilities']) ? $masked['capabilities'] : []);
    $masked['account_label'] = trim((string) ($masked['account_label'] ?? '')) !== ''
        ? trim((string) $masked['account_label'])
        : (string) ($profile['label'] ?? 'Connector');
    $masked['capabilities_enabled'] = $enabled;
    $masked['capabilities_supported'] = isset($profile['capabilities']) && is_array($profile['capabilities']) ? $profile['capabilities'] : [];
    $masked['capability_gaps'] = array_values(array_diff($masked['capabilities_supported'], $enabled));
    $masked['profile'] = $profile;
    return $masked;
}

function social_capability_summary()
{
    $catalog = social_platform_catalog();
    $connectors = app_read_json_file(social_connectors_path(), []);
    $connectorMap = [];
    foreach ($connectors as $connector) {
        $decorated = decorate_social_connector($connector);
        $provider = strtolower((string) ($decorated['provider'] ?? 'custom'));
        if (!isset($connectorMap[$provider])) {
            $connectorMap[$provider] = [];
        }
        $connectorMap[$provider][] = $decorated;
    }

    $capabilityCoverage = [];
    $familySummary = [];
    $providers = [];
    $connectedProviders = 0;
    $connectedAccounts = 0;
    $activeAccounts = 0;

    foreach ($catalog as $item) {
        if (!is_array($item)) {
            continue;
        }
        $provider = strtolower((string) ($item['provider'] ?? 'custom'));
        $family = (string) ($item['family'] ?? 'custom');
        $supported = isset($item['capabilities']) && is_array($item['capabilities']) ? array_values($item['capabilities']) : [];
        $providerConnectors = isset($connectorMap[$provider]) && is_array($connectorMap[$provider]) ? $connectorMap[$provider] : [];
        $enabledUnion = [];
        $activeCount = 0;
        foreach ($providerConnectors as $connector) {
            $connectedAccounts++;
            $status = strtolower((string) ($connector['status'] ?? 'planned'));
            if (in_array($status, ['active', 'enabled'], true)) {
                $activeAccounts++;
                $activeCount++;
            }
            foreach ((array) ($connector['capabilities_enabled'] ?? []) as $capability) {
                $capability = (string) $capability;
                if ($capability !== '' && !in_array($capability, $enabledUnion, true)) {
                    $enabledUnion[] = $capability;
                }
            }
        }
        if (!empty($providerConnectors)) {
            $connectedProviders++;
        }
        foreach ($supported as $capability) {
            if (!isset($capabilityCoverage[$capability])) {
                $capabilityCoverage[$capability] = [
                    'capability' => (string) $capability,
                    'supported_by' => [],
                    'enabled_on' => [],
                ];
            }
            $capabilityCoverage[$capability]['supported_by'][] = $provider;
            if (in_array($capability, $enabledUnion, true)) {
                $capabilityCoverage[$capability]['enabled_on'][] = $provider;
            }
        }
        if (!isset($familySummary[$family])) {
            $familySummary[$family] = [
                'family' => $family,
                'providers' => 0,
                'connected_accounts' => 0,
                'active_accounts' => 0,
            ];
        }
        $familySummary[$family]['providers']++;
        $familySummary[$family]['connected_accounts'] += count($providerConnectors);
        $familySummary[$family]['active_accounts'] += $activeCount;
        $providers[] = [
            'provider' => $provider,
            'label' => (string) ($item['label'] ?? $provider),
            'family' => $family,
            'supported_capabilities' => $supported,
            'enabled_capabilities' => $enabledUnion,
            'missing_capabilities' => array_values(array_diff($supported, $enabledUnion)),
            'connector_count' => count($providerConnectors),
            'active_connector_count' => $activeCount,
            'theme' => isset($item['theme']) && is_array($item['theme']) ? $item['theme'] : [],
        ];
    }

    $capabilities = array_values($capabilityCoverage);
    usort($capabilities, static function ($a, $b) {
        return strcmp((string) ($a['capability'] ?? ''), (string) ($b['capability'] ?? ''));
    });

    return [
        'summary' => [
            'provider_count' => count($providers),
            'connected_providers' => $connectedProviders,
            'connected_accounts' => $connectedAccounts,
            'active_accounts' => $activeAccounts,
            'capability_count' => count($capabilities),
        ],
        'families' => array_values($familySummary),
        'providers' => $providers,
        'capabilities' => $capabilities,
    ];
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

function webops_incidents_path()
{
    return app_storage_path('webops_incidents.json');
}

function webops_actions_queue_path()
{
    return app_storage_path('webops_actions_queue.json');
}

function webops_actions_log_path()
{
    return app_storage_path('webops_actions_log.json');
}

function webops_monitor_catalog()
{
    return [
        [
            'type' => 'uptime_http',
            'label' => 'HTTP Uptime',
            'target_mode' => 'url',
            'uses_bridge' => 0,
        ],
        [
            'type' => 'ssl_expiry',
            'label' => 'SSL Expiry',
            'target_mode' => 'url',
            'uses_bridge' => 0,
        ],
        [
            'type' => 'dns_resolution',
            'label' => 'DNS Resolution',
            'target_mode' => 'url_or_host',
            'uses_bridge' => 0,
        ],
        [
            'type' => 'wp_heartbeat',
            'label' => 'WordPress Heartbeat',
            'target_mode' => 'bridge_site_or_url',
            'uses_bridge' => 1,
        ],
        [
            'type' => 'update_health',
            'label' => 'Update Health',
            'target_mode' => 'bridge_site',
            'uses_bridge' => 1,
        ],
        [
            'type' => 'bridge_site_health',
            'label' => 'Bridge Site Health',
            'target_mode' => 'bridge_site',
            'uses_bridge' => 1,
        ],
        [
            'type' => 'webhook_check',
            'label' => 'Webhook Check',
            'target_mode' => 'url',
            'uses_bridge' => 0,
        ],
    ];
}

function webops_target_host($target)
{
    $value = trim((string) $target);
    if ($value === '') {
        return '';
    }
    if (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0) {
        return (string) parse_url($value, PHP_URL_HOST);
    }
    $parts = explode('/', $value);
    return (string) ($parts[0] ?? '');
}

function webops_ssl_certificate_snapshot($target)
{
    $host = webops_target_host($target);
    if ($host === '') {
        return ['ok' => false, 'error' => 'missing_target_host'];
    }
    $port = 443;
    $context = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $client = @stream_socket_client('ssl://' . $host . ':' . $port, $errno, $errstr, 6, STREAM_CLIENT_CONNECT, $context);
    if (!is_resource($client)) {
        return ['ok' => false, 'error' => trim((string) $errstr) !== '' ? trim((string) $errstr) : 'ssl_connect_failed'];
    }
    $params = stream_context_get_params($client);
    fclose($client);
    $certificate = isset($params['options']['ssl']['peer_certificate']) ? $params['options']['ssl']['peer_certificate'] : null;
    if (!is_resource($certificate)) {
        return ['ok' => false, 'error' => 'missing_peer_certificate'];
    }
    $parsed = openssl_x509_parse($certificate);
    if (!is_array($parsed) || empty($parsed['validTo_time_t'])) {
        return ['ok' => false, 'error' => 'certificate_parse_failed'];
    }
    $expiresTs = (int) $parsed['validTo_time_t'];
    $daysRemaining = (int) floor(($expiresTs - time()) / 86400);
    return [
        'ok' => true,
        'host' => $host,
        'expires_at' => gmdate('c', $expiresTs),
        'days_remaining' => $daysRemaining,
    ];
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

function seo_extension_sessions_path()
{
    return app_storage_path('seo_extension_sessions.json');
}

function seo_regression_state_path()
{
    return app_storage_path('seo_regression_state.json');
}

function seo_regression_runs_path()
{
    return app_storage_path('seo_regression_runs.json');
}

function crm_smtp_watch_state_path()
{
    return app_storage_path('crm_smtp_watch_state.json');
}

function crm_smtp_watch_runs_path()
{
    return app_storage_path('crm_smtp_watch_runs.json');
}

function notifications_path()
{
    return app_storage_path('notifications.json');
}

function notification_settings_path()
{
    return app_storage_path('notification_settings.json');
}

function automation_runs_path()
{
    return app_storage_path('automation_runs.json');
}

function automation_settings_path()
{
    return app_storage_path('automation_settings.json');
}

function rate_limit_path()
{
    return app_storage_path('rate_limits.json');
}

function audit_log_path()
{
    return app_storage_path('audit_log.json');
}

function deployment_guard_path()
{
    return app_storage_path('deployment_guard.json');
}

function deployment_release_log_path()
{
    return app_storage_path('deployment_releases.json');
}

function deployment_pipeline_runs_path()
{
    return app_storage_path('deployment_pipeline_runs.json');
}

function deployment_smoke_runs_path()
{
    return app_storage_path('deployment_smoke_runs.json');
}

function deployment_release_gate_state_path()
{
    return app_storage_path('deployment_release_gate_state.json');
}

function deployment_release_gate_runs_path()
{
    return app_storage_path('deployment_release_gate_runs.json');
}

function deployment_cutover_signoff_integrity_state_path()
{
    return app_storage_path('deployment_cutover_signoff_integrity_state.json');
}

function deployment_cutover_signoff_integrity_runs_path()
{
    return app_storage_path('deployment_cutover_signoff_integrity_runs.json');
}

function deployment_watchdogs_state_path()
{
    return app_storage_path('deployment_watchdogs_state.json');
}

function deployment_watchdogs_runs_path()
{
    return app_storage_path('deployment_watchdogs_runs.json');
}

function deployment_watchdogs_policy_history_path()
{
    return app_storage_path('deployment_watchdogs_policy_history.json');
}

function deployment_watchdogs_policy_baseline_path()
{
    return app_storage_path('deployment_watchdogs_policy_baseline.json');
}

function deployment_watchdogs_policy_baseline_state_path()
{
    return app_storage_path('deployment_watchdogs_policy_baseline_state.json');
}

function deployment_watchdogs_policy_baseline_runs_path()
{
    return app_storage_path('deployment_watchdogs_policy_baseline_runs.json');
}

function deployment_cutover_signoffs_path()
{
    return app_storage_path('deployment_cutover_signoffs.json');
}

function deployment_bypass_log_path()
{
    return app_storage_path('deployment_bypass_log.json');
}

function deployment_incident_reports_path()
{
    return app_storage_path('deployment_incident_reports.json');
}

function deployment_incident_sla_state_path()
{
    return app_storage_path('deployment_incident_sla_state.json');
}

function deployment_incident_sla_runs_path()
{
    return app_storage_path('deployment_incident_sla_runs.json');
}

function module_storage_map()
{
    return [
        'crm_connectors' => app_storage_path('crm_connectors.json'),
        'crm_sync_log' => app_storage_path('crm_sync_log.json'),
        'crm_retry_queue' => app_storage_path('crm_retry_queue.json'),
        'crm_smtp_watch_state' => crm_smtp_watch_state_path(),
        'crm_smtp_watch_runs' => crm_smtp_watch_runs_path(),
        'social_connectors' => social_connectors_path(),
        'social_sync_log' => social_sync_log_path(),
        'social_retry_queue' => social_retry_queue_path(),
        'social_schedule_queue' => social_schedule_queue_path(),
        'social_activity_feed' => social_activity_feed_path(),
        'social_inbox_threads' => social_inbox_threads_path(),
        'social_inbox_watch_state' => social_inbox_watch_state_path(),
        'social_inbox_watch_runs' => social_inbox_watch_runs_path(),
        'social_watch_state' => social_watch_state_path(),
        'social_watch_runs' => social_watch_runs_path(),
        'webops_monitors' => webops_monitors_path(),
        'webops_log' => webops_log_path(),
        'webops_retry_queue' => webops_retry_queue_path(),
        'webops_incidents' => webops_incidents_path(),
        'webops_actions_queue' => webops_actions_queue_path(),
        'webops_actions_log' => webops_actions_log_path(),
        'seo_projects' => seo_projects_path(),
        'seo_audits' => seo_audits_path(),
        'seo_extension_events' => seo_extension_events_path(),
        'seo_extension_sessions' => seo_extension_sessions_path(),
        'seo_regression_state' => seo_regression_state_path(),
        'seo_regression_runs' => seo_regression_runs_path(),
        'notifications' => notifications_path(),
        'notification_settings' => notification_settings_path(),
        'automation_runs' => automation_runs_path(),
        'automation_settings' => automation_settings_path(),
        'deployment_release_gate_state' => deployment_release_gate_state_path(),
        'deployment_release_gate_runs' => deployment_release_gate_runs_path(),
        'deployment_cutover_signoff_integrity_state' => deployment_cutover_signoff_integrity_state_path(),
        'deployment_cutover_signoff_integrity_runs' => deployment_cutover_signoff_integrity_runs_path(),
        'deployment_watchdogs_state' => deployment_watchdogs_state_path(),
        'deployment_watchdogs_runs' => deployment_watchdogs_runs_path(),
        'deployment_watchdogs_policy_history' => deployment_watchdogs_policy_history_path(),
        'deployment_watchdogs_policy_baseline' => deployment_watchdogs_policy_baseline_path(),
        'deployment_watchdogs_policy_baseline_state' => deployment_watchdogs_policy_baseline_state_path(),
        'deployment_watchdogs_policy_baseline_runs' => deployment_watchdogs_policy_baseline_runs_path(),
        'deployment_cutover_signoffs' => deployment_cutover_signoffs_path(),
    ];
}

function current_actor()
{
    $user = app_current_user();
    if (is_array($user) && !empty($user['email'])) {
        return 'user:' . (string) $user['email'];
    }
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
    return 'ip:' . $ip;
}

function enforce_rate_limit($actionKey, $maxRequests, $windowSeconds)
{
    $path = rate_limit_path();
    $rows = app_read_json_file($path, []);
    $now = time();
    $actor = current_actor();
    $key = $actor . '|' . (string) $actionKey;
    $entry = isset($rows[$key]) && is_array($rows[$key]) ? $rows[$key] : ['count' => 0, 'window_start' => $now];
    $start = (int) ($entry['window_start'] ?? $now);
    $count = (int) ($entry['count'] ?? 0);
    if (($now - $start) >= $windowSeconds) {
        $start = $now;
        $count = 0;
    }
    $count++;
    $rows[$key] = [
        'count' => $count,
        'window_start' => $start,
        'updated_at' => gmdate('c'),
    ];
    foreach ($rows as $k => $v) {
        $s = is_array($v) ? (int) ($v['window_start'] ?? 0) : 0;
        if ($s > 0 && ($now - $s) > 86400) {
            unset($rows[$k]);
        }
    }
    app_write_json_file($path, $rows);
    if ($count > $maxRequests) {
        out_json([
            'ok' => false,
            'error' => 'Rate limit exceeded. Please retry later.',
            'action' => $actionKey,
        ], 429);
    }
}

function audit_event($category, $actionName, $details = [])
{
    $rows = app_read_json_file(audit_log_path(), []);
    array_unshift($rows, [
        'id' => 'audit_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'category' => (string) $category,
        'action' => (string) $actionName,
        'actor' => current_actor(),
        'details' => is_array($details) ? $details : [],
        'created_at' => gmdate('c'),
    ]);
    $rows = array_slice($rows, 0, 1000);
    app_write_json_file(audit_log_path(), $rows);
}

function app_parse_utc_datetime($value)
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return false;
    }
    if (preg_match('/^\d{4}\-\d{2}\-\d{2}T\d{2}\:\d{2}$/', $raw)) {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw, new DateTimeZone('UTC'));
        if ($dt instanceof DateTime) {
            return $dt->getTimestamp();
        }
    }
    try {
        $dt = new DateTime($raw, new DateTimeZone('UTC'));
        return $dt->getTimestamp();
    } catch (Exception $e) {
        return false;
    }
}

function app_format_utc_datetime($value)
{
    $ts = app_parse_utc_datetime($value);
    if ($ts === false) {
        return '';
    }
    return gmdate('Y-m-d\TH:i', (int) $ts);
}

function default_deployment_guard()
{
    return [
        'enforced' => 1,
        'unlocked' => 0,
        'launch_window_enabled' => 0,
        'launch_window_start' => '',
        'launch_window_end' => '',
        'emergency_bypass_enabled' => 0,
        'emergency_bypass_expires_at' => '',
        'emergency_bypass_reason' => '',
        'emergency_bypass_set_by' => '',
        'emergency_bypass_last_alert_at' => '',
        'checklist' => [
            'backup_verified' => 0,
            'cron_configured' => 0,
            'rollback_plan_ready' => 0,
            'dns_domain_ready' => 0,
        ],
        'last_install_check' => ['status' => '', 'time' => ''],
        'last_preflight' => ['status' => '', 'time' => ''],
        'last_verify' => ['status' => '', 'time' => ''],
        'release_gate_freshness_minutes' => 30,
        'release_gate_require_readiness' => 1,
        'release_gate_require_public_smoke' => 1,
        'release_gate_require_auth_smoke' => 1,
        'release_gate_require_cutover_signoff' => 0,
        'release_gate_require_signoff_integrity' => 1,
        'release_gate_require_signoff_integrity_watch' => 0,
        'release_gate_require_watchdogs_policy_baseline_match' => 0,
        'release_gate_require_watchdogs_policy_baseline_check' => 0,
        'watchdogs_auto_incident_threshold' => 2,
        'watchdogs_auto_resolve_ok_streak' => 2,
        'updated_at' => gmdate('c'),
    ];
}

function get_deployment_guard()
{
    $saved = app_read_json_file(deployment_guard_path(), []);
    if (!is_array($saved)) {
        $saved = [];
    }
    $defaults = default_deployment_guard();
    $guard = array_merge($defaults, $saved);
    $guard['checklist'] = array_merge($defaults['checklist'], is_array($guard['checklist'] ?? null) ? $guard['checklist'] : []);
    $guard['last_install_check'] = array_merge($defaults['last_install_check'], is_array($guard['last_install_check'] ?? null) ? $guard['last_install_check'] : []);
    $guard['last_preflight'] = array_merge($defaults['last_preflight'], is_array($guard['last_preflight'] ?? null) ? $guard['last_preflight'] : []);
    $guard['last_verify'] = array_merge($defaults['last_verify'], is_array($guard['last_verify'] ?? null) ? $guard['last_verify'] : []);
    return $guard;
}

function save_deployment_guard($guard)
{
    $current = get_deployment_guard();
    $merged = array_merge($current, is_array($guard) ? $guard : []);
    $merged['checklist'] = array_merge($current['checklist'], is_array($merged['checklist'] ?? null) ? $merged['checklist'] : []);
    $merged['last_install_check'] = array_merge($current['last_install_check'], is_array($merged['last_install_check'] ?? null) ? $merged['last_install_check'] : []);
    $merged['last_preflight'] = array_merge($current['last_preflight'], is_array($merged['last_preflight'] ?? null) ? $merged['last_preflight'] : []);
    $merged['last_verify'] = array_merge($current['last_verify'], is_array($merged['last_verify'] ?? null) ? $merged['last_verify'] : []);
    $merged['updated_at'] = gmdate('c');
    app_write_json_file(deployment_guard_path(), $merged);
    return $merged;
}

function deployment_watchdogs_policy_settings_from_guard($guard = null)
{
    $row = is_array($guard) ? $guard : get_deployment_guard();
    return [
        'auto_incident_threshold' => max(1, min(10, (int) ($row['auto_incident_threshold'] ?? $row['watchdogs_auto_incident_threshold'] ?? 2))),
        'auto_resolve_ok_streak' => max(1, min(10, (int) ($row['auto_resolve_ok_streak'] ?? $row['watchdogs_auto_resolve_ok_streak'] ?? 2))),
    ];
}

function deployment_watchdogs_policy_history_rows()
{
    $rows = app_read_json_file(deployment_watchdogs_policy_history_path(), []);
    if (!is_array($rows)) {
        return [];
    }
    return $rows;
}

function deployment_watchdogs_policy_history_select($rows, $historyId = '')
{
    if (!is_array($rows) || empty($rows)) {
        return null;
    }
    $needle = trim((string) $historyId);
    if ($needle === '') {
        return is_array($rows[0] ?? null) ? $rows[0] : null;
    }
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((string) ($row['history_id'] ?? '') === $needle) {
            return $row;
        }
    }
    return null;
}

function deployment_watchdogs_policy_diff_summary($current, $candidate)
{
    $base = deployment_watchdogs_policy_settings_from_guard($current);
    $next = deployment_watchdogs_policy_settings_from_guard($candidate);
    $fields = ['auto_incident_threshold', 'auto_resolve_ok_streak'];
    $changes = [];
    $changedCount = 0;
    foreach ($fields as $field) {
        $from = (int) ($base[$field] ?? 0);
        $to = (int) ($next[$field] ?? 0);
        $changed = ($from !== $to);
        if ($changed) {
            $changedCount++;
        }
        $changes[$field] = [
            'from' => $from,
            'to' => $to,
            'changed' => $changed ? 1 : 0,
            'direction' => ($to > $from) ? 'increase' : (($to < $from) ? 'decrease' : 'same'),
        ];
    }
    return [
        'current' => $base,
        'candidate' => $next,
        'changes' => $changes,
        'changed_count' => $changedCount,
        'has_changes' => ($changedCount > 0) ? 1 : 0,
    ];
}

function deployment_watchdogs_policy_baseline_snapshot($baseline = null)
{
    $row = is_array($baseline) ? $baseline : app_read_json_file(deployment_watchdogs_policy_baseline_path(), []);
    $current = deployment_watchdogs_policy_settings_from_guard();
    if (!is_array($row) || !isset($row['settings']) || !is_array($row['settings'])) {
        return [
            'has_baseline' => 0,
            'baseline' => null,
            'current' => $current,
            'delta' => [],
            'changed_count' => 0,
            'has_changes' => 0,
        ];
    }
    $baselineSettings = deployment_watchdogs_policy_settings_from_guard($row['settings']);
    $delta = deployment_watchdogs_policy_diff_summary($baselineSettings, $current);
    return [
        'has_baseline' => 1,
        'baseline' => [
            'settings' => $baselineSettings,
            'set_at' => (string) ($row['set_at'] ?? ''),
            'source' => (string) ($row['source'] ?? ''),
            'history_id' => (string) ($row['history_id'] ?? ''),
            'mode' => (string) ($row['mode'] ?? ''),
            'actor' => isset($row['actor']) && is_array($row['actor']) ? $row['actor'] : [],
        ],
        'current' => isset($delta['candidate']) && is_array($delta['candidate']) ? $delta['candidate'] : $current,
        'delta' => isset($delta['changes']) && is_array($delta['changes']) ? $delta['changes'] : [],
        'changed_count' => (int) ($delta['changed_count'] ?? 0),
        'has_changes' => !empty($delta['has_changes']) ? 1 : 0,
    ];
}

function deployment_watchdogs_policy_baseline_check_snapshot($source = 'manual')
{
    $src = trim((string) $source);
    if ($src === '') {
        $src = 'manual';
    }
    $snapshot = deployment_watchdogs_policy_baseline_snapshot();
    $hasBaseline = !empty($snapshot['has_baseline']) ? 1 : 0;
    $hasChanges = !empty($snapshot['has_changes']) ? 1 : 0;
    $changedCount = (int) ($snapshot['changed_count'] ?? 0);
    $status = $hasBaseline ? ($hasChanges ? 'drift' : 'ok') : 'missing_baseline';

    $state = app_read_json_file(deployment_watchdogs_policy_baseline_state_path(), []);
    if (!is_array($state)) {
        $state = [];
    }
    $prevStatus = strtolower(trim((string) ($state['last_status'] ?? '')));
    $statusChanged = ($prevStatus !== '' && $prevStatus !== $status) ? 1 : 0;
    $lastAlertAt = isset($state['last_alert_at']) ? (string) $state['last_alert_at'] : '';
    $lastAlertTs = $lastAlertAt !== '' ? strtotime($lastAlertAt) : false;
    $cooldownMinutes = 30;
    $cooldownActive = ($lastAlertTs !== false) ? ((time() - $lastAlertTs) < ($cooldownMinutes * 60)) : false;

    $alertSent = 0;
    if ($statusChanged === 1) {
        $alertType = 'info';
        $alertMessage = 'Watchdogs policy baseline check status changed to OK.';
        if ($status === 'drift') {
            $alertType = 'warning';
            $alertMessage = 'Watchdogs policy baseline check status changed to DRIFT.';
        } elseif ($status === 'missing_baseline') {
            $alertType = 'warning';
            $alertMessage = 'Watchdogs policy baseline check status changed to MISSING BASELINE.';
        }
        push_notification($alertType, $alertMessage, [
            'source' => $src,
            'status' => $status,
            'has_baseline' => $hasBaseline,
            'has_changes' => $hasChanges,
            'changed_count' => $changedCount,
        ]);
        $alertSent = 1;
    } elseif ($status === 'drift' && !$cooldownActive) {
        push_notification('warning', 'Watchdogs policy baseline drift remains active.', [
            'source' => $src,
            'status' => $status,
            'has_baseline' => $hasBaseline,
            'has_changes' => $hasChanges,
            'changed_count' => $changedCount,
        ]);
        $alertSent = 1;
    }

    $run = [
        'run_id' => 'watchdogs_policy_baseline_check_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'source' => $src,
        'status' => $status,
        'status_changed' => $statusChanged,
        'alert_sent' => $alertSent,
        'has_baseline' => $hasBaseline,
        'has_changes' => $hasChanges,
        'changed_count' => $changedCount,
        'delta' => isset($snapshot['delta']) && is_array($snapshot['delta']) ? $snapshot['delta'] : [],
    ];
    $runs = app_read_json_file(deployment_watchdogs_policy_baseline_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 400);
    app_write_json_file(deployment_watchdogs_policy_baseline_runs_path(), $runs);

    $now = gmdate('c');
    $nextState = [
        'last_checked_at' => $now,
        'last_status' => $status,
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => $src,
        'last_alert_at' => ($alertSent === 1) ? $now : (string) ($state['last_alert_at'] ?? ''),
        'last_alert_sent' => $alertSent,
        'has_baseline' => $hasBaseline,
        'has_changes' => $hasChanges,
        'changed_count' => $changedCount,
    ];
    app_write_json_file(deployment_watchdogs_policy_baseline_state_path(), $nextState);

    audit_event('deployment', 'watchdogs.policy.baseline.check', [
        'run_id' => (string) ($run['run_id'] ?? ''),
        'source' => $src,
        'status' => $status,
        'has_baseline' => $hasBaseline,
        'has_changes' => $hasChanges,
        'changed_count' => $changedCount,
        'alert_sent' => $alertSent,
    ]);

    return [
        'run' => $run,
        'state' => $nextState,
        'snapshot' => $snapshot,
    ];
}

function deployment_guard_from_payload($payload, $baseGuard = null)
{
    $base = is_array($baseGuard) ? $baseGuard : get_deployment_guard();
    $incomingChecklist = isset($payload['checklist']) && is_array($payload['checklist']) ? $payload['checklist'] : [];
    $merged = array_merge($base, [
        'enforced' => !empty($payload['enforced']) ? 1 : 0,
        'launch_window_enabled' => !empty($payload['launch_window_enabled']) ? 1 : 0,
        'launch_window_start' => app_format_utc_datetime((string) ($payload['launch_window_start'] ?? '')),
        'launch_window_end' => app_format_utc_datetime((string) ($payload['launch_window_end'] ?? '')),
        'checklist' => [
            'backup_verified' => !empty($incomingChecklist['backup_verified']) ? 1 : 0,
            'cron_configured' => !empty($incomingChecklist['cron_configured']) ? 1 : 0,
            'rollback_plan_ready' => !empty($incomingChecklist['rollback_plan_ready']) ? 1 : 0,
            'dns_domain_ready' => !empty($incomingChecklist['dns_domain_ready']) ? 1 : 0,
        ],
    ]);
    $merged['checklist'] = array_merge($base['checklist'], is_array($merged['checklist'] ?? null) ? $merged['checklist'] : []);
    $merged['last_install_check'] = array_merge($base['last_install_check'], is_array($merged['last_install_check'] ?? null) ? $merged['last_install_check'] : []);
    $merged['last_preflight'] = array_merge($base['last_preflight'], is_array($merged['last_preflight'] ?? null) ? $merged['last_preflight'] : []);
    $merged['last_verify'] = array_merge($base['last_verify'], is_array($merged['last_verify'] ?? null) ? $merged['last_verify'] : []);
    $merged['updated_at'] = gmdate('c');
    return $merged;
}

function deployment_guard_bypass_status($guard)
{
    if (empty($guard['emergency_bypass_enabled'])) {
        return [
            'active' => false,
            'valid' => true,
            'expires_at' => '',
            'reason' => '',
        ];
    }
    $expiresRaw = app_format_utc_datetime((string) ($guard['emergency_bypass_expires_at'] ?? ''));
    $expiresTs = app_parse_utc_datetime($expiresRaw);
    $reason = trim((string) ($guard['emergency_bypass_reason'] ?? ''));
    if ($expiresTs === false || $reason === '') {
        return [
            'active' => false,
            'valid' => false,
            'expires_at' => $expiresRaw,
            'reason' => $reason,
        ];
    }
    $now = time();
    return [
        'active' => ($now <= $expiresTs),
        'valid' => true,
        'expires_at' => $expiresRaw,
        'reason' => $reason,
    ];
}

function deployment_guard_watchdog($guard, $emit = true)
{
    if (!is_array($guard)) {
        $guard = get_deployment_guard();
    }
    if (empty($guard['emergency_bypass_enabled'])) {
        return $guard;
    }

    $status = deployment_guard_bypass_status($guard);
    $expiresAt = (string) ($status['expires_at'] ?? '');
    $expiresTs = app_parse_utc_datetime($expiresAt);
    $now = time();

    if (empty($status['valid']) || $expiresTs === false) {
        return $guard;
    }

    if ($now > $expiresTs) {
        $updated = save_deployment_guard([
            'emergency_bypass_enabled' => 0,
            'emergency_bypass_expires_at' => '',
            'emergency_bypass_reason' => '',
            'emergency_bypass_set_by' => '',
            'emergency_bypass_last_alert_at' => '',
        ]);
        append_bypass_log_event('auto_disable_expired', [
            'expired_at' => $expiresAt,
            'reason' => (string) ($guard['emergency_bypass_reason'] ?? ''),
            'set_by' => (string) ($guard['emergency_bypass_set_by'] ?? ''),
        ]);
        if ($emit) {
            audit_event('deployment', 'guard.bypass.auto_disable_expired', ['expired_at' => $expiresAt]);
            push_notification('critical', 'Emergency bypass expired and was auto-disabled.', ['expired_at' => $expiresAt]);
        }
        return $updated;
    }

    $remaining = $expiresTs - $now;
    $lastAlertTs = app_parse_utc_datetime((string) ($guard['emergency_bypass_last_alert_at'] ?? ''));
    if ($remaining <= 900 && ($lastAlertTs === false || ($now - $lastAlertTs) >= 600)) {
        $updated = save_deployment_guard(['emergency_bypass_last_alert_at' => gmdate('Y-m-d\TH:i', $now)]);
        if ($emit) {
            audit_event('deployment', 'guard.bypass.expiring_soon', [
                'expires_at' => $expiresAt,
                'remaining_seconds' => $remaining,
            ]);
            push_notification('critical', 'Emergency bypass expires soon at ' . $expiresAt . ' UTC.', [
                'remaining_seconds' => $remaining,
                'expires_at' => $expiresAt,
            ]);
        }
        return $updated;
    }

    return $guard;
}

function append_bypass_log_event($eventType, $details = [])
{
    $rows = app_read_json_file(deployment_bypass_log_path(), []);
    array_unshift($rows, [
        'event_id' => 'bypass_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'event_type' => (string) $eventType,
        'actor' => current_actor(),
        'details' => is_array($details) ? $details : [],
        'created_at' => gmdate('c'),
    ]);
    $rows = array_slice($rows, 0, 500);
    app_write_json_file(deployment_bypass_log_path(), $rows);
}

function deployment_guard_evaluate($requireUnlocked = true, $guardOverride = null)
{
    $guard = is_array($guardOverride) ? $guardOverride : get_deployment_guard();
    $guard = deployment_guard_watchdog($guard, !is_array($guardOverride));
    $reasons = [];

    if (empty($guard['enforced'])) {
        return [
            'allowed' => true,
            'guard' => $guard,
            'reasons' => [],
        ];
    }

    $bypass = deployment_guard_bypass_status($guard);
    if (!empty($bypass['active'])) {
        return [
            'allowed' => true,
            'guard' => $guard,
            'reasons' => [],
            'bypass_active' => true,
            'bypass' => $bypass,
        ];
    }
    if (empty($bypass['valid'])) {
        $reasons[] = 'Emergency bypass is enabled but invalid (reason or expiry missing).';
    } elseif (!empty($guard['emergency_bypass_enabled']) && empty($bypass['active'])) {
        $reasons[] = 'Emergency bypass expired at ' . (string) ($bypass['expires_at'] ?? '') . ' UTC.';
    }

    if ($requireUnlocked && empty($guard['unlocked'])) {
        $reasons[] = 'Deployment guard is locked.';
    }

    if (!empty($guard['launch_window_enabled'])) {
        $startRaw = app_format_utc_datetime((string) ($guard['launch_window_start'] ?? ''));
        $endRaw = app_format_utc_datetime((string) ($guard['launch_window_end'] ?? ''));
        $startTs = app_parse_utc_datetime($startRaw);
        $endTs = app_parse_utc_datetime($endRaw);
        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            $reasons[] = 'Launch window is enabled but start/end is invalid.';
        } else {
            $now = time();
            if ($now < $startTs || $now > $endTs) {
                $reasons[] = 'Current UTC time is outside launch window (' . $startRaw . ' to ' . $endRaw . ').';
            }
        }
    }

    $installStatus = (string) ($guard['last_install_check']['status'] ?? '');
    if ($installStatus === '' || $installStatus === 'critical') {
        $reasons[] = 'Install check is missing or critical.';
    }

    $preflightStatus = (string) ($guard['last_preflight']['status'] ?? '');
    if ($preflightStatus === '' || $preflightStatus === 'critical') {
        $reasons[] = 'Deployment preflight is missing or critical.';
    }

    $verifyStatus = (string) ($guard['last_verify']['status'] ?? '');
    if ($verifyStatus === '' || $verifyStatus === 'critical') {
        $reasons[] = 'Post-deploy verify is missing or critical.';
    }

    foreach (($guard['checklist'] ?? []) as $key => $value) {
        if (empty($value)) {
            $reasons[] = 'Checklist item incomplete: ' . (string) $key;
        }
    }

    return [
        'allowed' => empty($reasons),
        'guard' => $guard,
        'reasons' => $reasons,
        'bypass_active' => false,
        'bypass' => $bypass,
    ];
}

function healthcheck_snapshot()
{
    global $config;

    $checks = [];
    $status = 'ok';

    $storageDir = dirname(__DIR__) . '/storage';
    $storageWritable = is_dir($storageDir) ? is_writable($storageDir) : @mkdir($storageDir, 0775, true);
    $checks[] = [
        'check' => 'storage_writable',
        'ok' => !empty($storageWritable),
        'message' => !empty($storageWritable) ? 'Storage directory is writable.' : 'Storage directory is not writable.',
    ];
    if (empty($storageWritable)) {
        $status = 'critical';
    }

    $env = (string) ($config['environment'] ?? 'development');
    $checks[] = [
        'check' => 'environment',
        'ok' => $env !== '',
        'message' => 'Environment: ' . $env,
    ];
    if (strtolower(trim($env)) !== 'production' && $status !== 'critical') {
        $status = 'warning';
    }

    $requiredKeys = [
        ['key' => 'session_key', 'severity' => 'critical'],
        ['key' => 'automation_scheduler_key', 'severity' => 'critical'],
        ['key' => 'seo_extension_ingest_key', 'severity' => 'critical'],
        ['key' => 'demo_admin_email', 'severity' => 'critical'],
        ['key' => 'demo_admin_password', 'severity' => 'critical'],
    ];
    foreach ($requiredKeys as $spec) {
        $key = (string) ($spec['key'] ?? '');
        $severity = (string) ($spec['severity'] ?? 'warning');
        $value = trim((string) ($config[$key] ?? ''));
        $ok = !app_value_is_placeholder($value);
        $checks[] = [
            'check' => 'config_' . $key,
            'ok' => $ok,
            'message' => $ok ? ($key . ' is configured.') : ($key . ' is missing or placeholder.'),
        ];
        if (!$ok) {
            if ($severity === 'critical') {
                $status = 'critical';
            } elseif ($status !== 'critical') {
                $status = 'warning';
            }
        }
    }

    $pluginSites = isset($config['plugin_sites']) && is_array($config['plugin_sites']) ? $config['plugin_sites'] : [];
    $checks[] = [
        'check' => 'plugin_sites',
        'ok' => true,
        'message' => 'Configured plugin sites: ' . count($pluginSites),
    ];

    return [
        'status' => $status,
        'checks' => $checks,
        'time' => gmdate('c'),
    ];
}

function deployment_preflight_snapshot()
{
    $health = healthcheck_snapshot();
    $checks = [];
    $status = (string) ($health['status'] ?? 'warning');

    $settings = get_automation_settings();
    $automationEnabled = !empty($settings['enabled']);
    $checks[] = [
        'check' => 'automation_enabled',
        'ok' => $automationEnabled,
        'message' => $automationEnabled ? 'Automation is enabled.' : 'Automation is disabled.',
    ];
    if (!$automationEnabled && $status === 'ok') {
        $status = 'warning';
    }

    $bridgeRows = [];
    foreach (all_sites() as $site) {
        $res = app_bridge_request($site, 'GET', 'bridge/status');
        $ok = !empty($res['ok']);
        $bridgeRows[] = [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'ok' => $ok,
            'http_status' => (int) ($res['status'] ?? 0),
        ];
        if (!$ok && $status !== 'critical') {
            $status = 'warning';
        }
    }
    $checks[] = [
        'check' => 'bridge_connectivity',
        'ok' => empty($bridgeRows) ? false : array_reduce($bridgeRows, static function ($carry, $row) {
            return $carry && !empty($row['ok']);
        }, true),
        'message' => empty($bridgeRows) ? 'No plugin sites configured.' : 'Bridge check completed for ' . count($bridgeRows) . ' site(s).',
        'sites' => $bridgeRows,
    ];
    if (empty($bridgeRows) && $status === 'ok') {
        $status = 'warning';
    }

    $moduleCounts = [
        'crm_connectors' => count(app_read_json_file(app_storage_path('crm_connectors.json'), [])),
        'social_connectors' => count(app_read_json_file(social_connectors_path(), [])),
        'webops_monitors' => count(app_read_json_file(webops_monitors_path(), [])),
        'seo_projects' => count(app_read_json_file(seo_projects_path(), [])),
    ];
    $checks[] = [
        'check' => 'module_configuration',
        'ok' => ($moduleCounts['webops_monitors'] > 0 || $moduleCounts['seo_projects'] > 0 || $moduleCounts['crm_connectors'] > 0 || $moduleCounts['social_connectors'] > 0),
        'message' => 'Module config counts collected.',
        'counts' => $moduleCounts,
    ];

    return [
        'status' => $status,
        'health' => $health,
        'checks' => $checks,
        'time' => gmdate('c'),
    ];
}

function deployment_report_snapshot()
{
    global $config;

    $preflight = deployment_preflight_snapshot();
    $healthChecks = isset($preflight['health']['checks']) && is_array($preflight['health']['checks']) ? $preflight['health']['checks'] : [];
    $preflightChecks = isset($preflight['checks']) && is_array($preflight['checks']) ? $preflight['checks'] : [];

    $blockers = [];
    $warnings = [];
    foreach ($healthChecks as $check) {
        if (!is_array($check)) {
            continue;
        }
        $ok = !empty($check['ok']);
        if ($ok) {
            continue;
        }
        $msg = (string) ($check['message'] ?? 'Health check failed.');
        if ((string) ($preflight['status'] ?? 'warning') === 'critical') {
            $blockers[] = $msg;
        } else {
            $warnings[] = $msg;
        }
    }
    foreach ($preflightChecks as $check) {
        if (!is_array($check) || !array_key_exists('ok', $check) || !empty($check['ok'])) {
            continue;
        }
        $warnings[] = (string) ($check['message'] ?? 'Preflight warning.');
    }

    $sites = [];
    foreach (all_sites() as $site) {
        $sites[] = [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'base_url' => (string) ($site['base_url'] ?? ''),
        ];
    }

    return [
        'report_id' => 'deploy_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'generated_at' => gmdate('c'),
        'environment' => (string) ($config['environment'] ?? 'development'),
        'phase' => '1.10-deployment-report-export',
        'status' => (string) ($preflight['status'] ?? 'warning'),
        'summary' => [
            'blocker_count' => count($blockers),
            'warning_count' => count($warnings),
            'site_count' => count($sites),
        ],
        'blockers' => $blockers,
        'warnings' => $warnings,
        'plugin_sites' => $sites,
        'preflight' => $preflight,
    ];
}

function install_check_snapshot()
{
    global $config;

    $status = 'ok';
    $checks = [];

    $minPhp = '7.4.0';
    $phpOk = version_compare(PHP_VERSION, $minPhp, '>=');
    $checks[] = [
        'check' => 'php_version',
        'ok' => $phpOk,
        'message' => $phpOk ? ('PHP version is supported (' . PHP_VERSION . ').') : ('PHP ' . PHP_VERSION . ' is below required ' . $minPhp . '.'),
    ];
    if (!$phpOk) {
        $status = 'critical';
    }

    $requiredExtensions = ['curl', 'json', 'mbstring', 'openssl'];
    foreach ($requiredExtensions as $ext) {
        $ok = extension_loaded($ext);
        $checks[] = [
            'check' => 'ext_' . $ext,
            'ok' => $ok,
            'message' => $ok ? ($ext . ' extension loaded.') : ($ext . ' extension missing.'),
        ];
        if (!$ok && $status !== 'critical') {
            $status = 'warning';
        }
    }

    $storageDir = dirname(__DIR__) . '/storage';
    $storageWritable = is_dir($storageDir) ? is_writable($storageDir) : @mkdir($storageDir, 0775, true);
    $checks[] = [
        'check' => 'storage_writable',
        'ok' => !empty($storageWritable),
        'message' => !empty($storageWritable) ? 'Storage directory is writable.' : 'Storage directory is not writable.',
    ];
    if (empty($storageWritable)) {
        $status = 'critical';
    }

    $configPath = dirname(__DIR__) . '/config.php';
    $configExists = is_file($configPath);
    $checks[] = [
        'check' => 'config_file',
        'ok' => $configExists,
        'message' => $configExists ? 'app-site/config.php exists.' : 'app-site/config.php is missing.',
    ];
    if (!$configExists) {
        $status = 'critical';
    }

    $requiredSecrets = ['session_key', 'automation_scheduler_key', 'seo_extension_ingest_key'];
    foreach ($requiredSecrets as $key) {
        $ok = !app_value_is_placeholder((string) ($config[$key] ?? ''));
        $checks[] = [
            'check' => 'config_' . $key,
            'ok' => $ok,
            'message' => $ok ? ($key . ' is configured.') : ($key . ' is missing or placeholder.'),
        ];
        if (!$ok) {
            $status = 'critical';
        }
    }

    $summary = [
        'plugin_site_count' => count(all_sites()),
        'environment' => (string) ($config['environment'] ?? 'development'),
        'php_version' => PHP_VERSION,
    ];

    return [
        'status' => $status,
        'checks' => $checks,
        'summary' => $summary,
        'time' => gmdate('c'),
    ];
}

function deployment_verify_snapshot()
{
    $status = 'ok';
    $checks = [];

    $preflight = deployment_preflight_snapshot();
    $checks[] = [
        'check' => 'preflight_status',
        'ok' => (string) ($preflight['status'] ?? 'warning') !== 'critical',
        'message' => 'Preflight status: ' . (string) ($preflight['status'] ?? 'warning'),
    ];
    if ((string) ($preflight['status'] ?? 'warning') === 'critical') {
        $status = 'critical';
    } elseif ((string) ($preflight['status'] ?? 'warning') === 'warning' && $status !== 'critical') {
        $status = 'warning';
    }

    $storageDir = dirname(__DIR__) . '/storage';
    $probeFile = $storageDir . '/verify_probe_' . gmdate('Ymd_His') . '.tmp';
    $probeData = 'verify:' . gmdate('c');
    $writeOk = @file_put_contents($probeFile, $probeData) !== false;
    $readOk = $writeOk ? (@file_get_contents($probeFile) === $probeData) : false;
    if ($writeOk && is_file($probeFile)) {
        @unlink($probeFile);
    }
    $checks[] = [
        'check' => 'storage_read_write_probe',
        'ok' => ($writeOk && $readOk),
        'message' => ($writeOk && $readOk) ? 'Storage write/read probe passed.' : 'Storage write/read probe failed.',
    ];
    if ((!$writeOk || !$readOk) && $status !== 'critical') {
        $status = 'critical';
    }

    $automation = get_automation_settings();
    $autoEnabled = !empty($automation['enabled']);
    $checks[] = [
        'check' => 'automation_enabled',
        'ok' => $autoEnabled,
        'message' => $autoEnabled ? 'Automation is enabled.' : 'Automation is disabled.',
    ];
    if (!$autoEnabled && $status === 'ok') {
        $status = 'warning';
    }

    $notifications = app_read_json_file(notifications_path(), []);
    $unread = 0;
    foreach ($notifications as $row) {
        if (is_array($row) && empty($row['read'])) {
            $unread++;
        }
    }

    return [
        'status' => $status,
        'checks' => $checks,
        'summary' => [
            'unread_notifications' => $unread,
            'automation_enabled' => $autoEnabled ? 1 : 0,
            'plugin_site_count' => count(all_sites()),
        ],
        'preflight' => $preflight,
        'time' => gmdate('c'),
    ];
}

function deployment_handoff_bundle_snapshot()
{
    global $config;

    $install = install_check_snapshot();
    $preflight = deployment_preflight_snapshot();
    $verify = deployment_verify_snapshot();
    $report = deployment_report_snapshot();
    $guardEval = deployment_guard_evaluate();

    $checklistItems = [
        [
            'item' => 'config_file_present',
            'ok' => is_file(dirname(__DIR__) . '/config.php'),
            'message' => 'app-site/config.php exists on server.',
        ],
        [
            'item' => 'environment_production',
            'ok' => strtolower((string) ($config['environment'] ?? 'development')) === 'production',
            'message' => 'Environment is set to production.',
        ],
        [
            'item' => 'scheduler_key_configured',
            'ok' => !app_value_is_placeholder((string) ($config['automation_scheduler_key'] ?? '')),
            'message' => 'Automation scheduler key configured.',
        ],
        [
            'item' => 'seo_ingest_key_configured',
            'ok' => !app_value_is_placeholder((string) ($config['seo_extension_ingest_key'] ?? '')),
            'message' => 'SEO extension ingest key configured.',
        ],
        [
            'item' => 'plugin_sites_configured',
            'ok' => count(all_sites()) > 0,
            'message' => 'At least one plugin site is configured.',
        ],
    ];
    $failedChecklist = [];
    foreach ($checklistItems as $item) {
        if (empty($item['ok'])) {
            $failedChecklist[] = (string) ($item['item'] ?? 'unknown');
        }
    }

    $auditRows = app_read_json_file(audit_log_path(), []);
    $auditTail = array_slice($auditRows, 0, 50);

    return [
        'bundle_id' => 'handoff_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'generated_at' => gmdate('c'),
        'phase' => '1.13-hostinger-handoff-bundle',
        'environment' => (string) ($config['environment'] ?? 'development'),
        'status' => (!empty($guardEval['allowed']) && empty($failedChecklist)) ? 'ready' : 'review_required',
        'summary' => [
            'install_status' => (string) ($install['status'] ?? 'warning'),
            'preflight_status' => (string) ($preflight['status'] ?? 'warning'),
            'verify_status' => (string) ($verify['status'] ?? 'warning'),
            'guard_allowed' => !empty($guardEval['allowed']) ? 1 : 0,
            'checklist_failed_count' => count($failedChecklist),
            'plugin_site_count' => count(all_sites()),
        ],
        'environment_checklist' => $checklistItems,
        'failed_environment_checklist' => $failedChecklist,
        'install_check' => $install,
        'preflight' => $preflight,
        'post_deploy_verify' => $verify,
        'deployment_report' => $report,
        'deployment_guard' => [
            'allowed' => !empty($guardEval['allowed']),
            'reasons' => $guardEval['reasons'],
            'state' => $guardEval['guard'],
        ],
        'audit_tail' => $auditTail,
        'references' => [
            'runbook' => 'docs/DEPLOY_HOSTINGER_RUNBOOK.md',
            'phase_19' => 'docs/PHASE19_HOSTING_CUTOVER_TOOLKIT.md',
            'phase_20' => 'docs/PHASE20_DEPLOYMENT_GUARD_ENFORCEMENT.md',
        ],
    ];
}

function deployment_release_gate_snapshot($freshnessMinutes = null)
{
    $guardCfg = get_deployment_guard();
    $defaultWindow = isset($guardCfg['release_gate_freshness_minutes']) ? (int) $guardCfg['release_gate_freshness_minutes'] : 30;
    $windowInput = ($freshnessMinutes === null) ? $defaultWindow : (int) $freshnessMinutes;
    $window = max(5, min(1440, $windowInput));
    $requireReadiness = !empty($guardCfg['release_gate_require_readiness']) ? 1 : 0;
    $requirePublic = !empty($guardCfg['release_gate_require_public_smoke']) ? 1 : 0;
    $requireAuth = !empty($guardCfg['release_gate_require_auth_smoke']) ? 1 : 0;
    $requireSignoff = !empty($guardCfg['release_gate_require_cutover_signoff']) ? 1 : 0;
    $requireSignoffIntegrity = !empty($guardCfg['release_gate_require_signoff_integrity']) ? 1 : 0;
    $requireSignoffIntegrityWatch = !empty($guardCfg['release_gate_require_signoff_integrity_watch']) ? 1 : 0;
    $requirePolicyBaselineMatch = !empty($guardCfg['release_gate_require_watchdogs_policy_baseline_match']) ? 1 : 0;
    $requirePolicyBaselineCheck = !empty($guardCfg['release_gate_require_watchdogs_policy_baseline_check']) ? 1 : 0;
    $readiness = deployment_cutover_readiness_snapshot();
    $history = deployment_smoke_history_snapshot();
    $policyBaseline = deployment_watchdogs_policy_baseline_snapshot();
    $activeSignoff = deployment_cutover_signoff_active_snapshot();
    $activeSignoffRow = is_array($activeSignoff['active'] ?? null) ? $activeSignoff['active'] : null;
    $activeSignoffIntegrity = is_array($activeSignoffRow) ? deployment_cutover_signoff_verify_item($activeSignoffRow) : null;
    $signoffIntegrityWatchState = app_read_json_file(deployment_cutover_signoff_integrity_state_path(), []);
    if (!is_array($signoffIntegrityWatchState)) {
        $signoffIntegrityWatchState = [];
    }
    $watchLastCheckedAt = (string) ($signoffIntegrityWatchState['last_checked_at'] ?? '');
    $watchStatus = strtolower(trim((string) ($signoffIntegrityWatchState['last_status'] ?? '')));
    $watchActiveSignoffId = (string) ($signoffIntegrityWatchState['active_signoff_id'] ?? '');
    $now = time();
    $watchAgeMinutes = null;
    if ($watchLastCheckedAt !== '') {
        $watchTs = strtotime($watchLastCheckedAt);
        if ($watchTs !== false) {
            $watchAgeMinutes = max(0, (int) floor(($now - $watchTs) / 60));
        }
    }
    $activeSignoffId = is_array($activeSignoffRow) ? (string) ($activeSignoffRow['signoff_id'] ?? '') : '';
    $watchTargetsActiveSignoff = ($activeSignoffId !== '' && $watchActiveSignoffId !== '' && hash_equals($activeSignoffId, $watchActiveSignoffId));
    $signoffIntegrityWatch = [
        'status' => $watchStatus !== '' ? $watchStatus : 'unknown',
        'last_checked_at' => $watchLastCheckedAt,
        'age_minutes' => $watchAgeMinutes,
        'active_signoff_id' => $watchActiveSignoffId,
        'targets_active_signoff' => $watchTargetsActiveSignoff ? 1 : 0,
        'last_run_id' => (string) ($signoffIntegrityWatchState['last_run_id'] ?? ''),
        'last_source' => (string) ($signoffIntegrityWatchState['last_source'] ?? ''),
    ];
    $policyBaselineCheckState = app_read_json_file(deployment_watchdogs_policy_baseline_state_path(), []);
    if (!is_array($policyBaselineCheckState)) {
        $policyBaselineCheckState = [];
    }
    $policyBaselineCheckLastCheckedAt = (string) ($policyBaselineCheckState['last_checked_at'] ?? '');
    $policyBaselineCheckStatus = strtolower(trim((string) ($policyBaselineCheckState['last_status'] ?? '')));
    if ($policyBaselineCheckStatus === '') {
        $policyBaselineCheckStatus = 'unknown';
    }
    $policyBaselineCheckAgeMinutes = null;
    if ($policyBaselineCheckLastCheckedAt !== '') {
        $baselineCheckTs = strtotime($policyBaselineCheckLastCheckedAt);
        if ($baselineCheckTs !== false) {
            $policyBaselineCheckAgeMinutes = max(0, (int) floor(($now - $baselineCheckTs) / 60));
        }
    }
    $policyBaselineCheck = [
        'status' => $policyBaselineCheckStatus,
        'last_checked_at' => $policyBaselineCheckLastCheckedAt,
        'age_minutes' => $policyBaselineCheckAgeMinutes,
        'last_run_id' => (string) ($policyBaselineCheckState['last_run_id'] ?? ''),
        'last_source' => (string) ($policyBaselineCheckState['last_source'] ?? ''),
    ];
    $latestPublicPass = null;
    $latestAuthPass = null;
    $items = isset($history['items']) && is_array($history['items']) ? $history['items'] : [];
    foreach ($items as $row) {
        if (!is_array($row) || empty($row['ok'])) {
            continue;
        }
        $type = strtolower(trim((string) ($row['smoke_type'] ?? '')));
        if ($type === 'public' && $latestPublicPass === null) {
            $latestPublicPass = $row;
        }
        if ($type === 'auth' && $latestAuthPass === null) {
            $latestAuthPass = $row;
        }
        if ($latestPublicPass !== null && $latestAuthPass !== null) {
            break;
        }
    }
    $publicAge = null;
    $authAge = null;
    if (is_array($latestPublicPass)) {
        $ts = strtotime((string) ($latestPublicPass['created_at'] ?? ''));
        if ($ts !== false) {
            $publicAge = max(0, (int) floor(($now - $ts) / 60));
        }
    }
    if (is_array($latestAuthPass)) {
        $ts = strtotime((string) ($latestAuthPass['created_at'] ?? ''));
        if ($ts !== false) {
            $authAge = max(0, (int) floor(($now - $ts) / 60));
        }
    }

    $checks = [];
    $checks[] = [
        'item' => 'readiness_ready',
        'required' => $requireReadiness,
        'ok' => !$requireReadiness || ((string) ($readiness['status'] ?? 'review_required') === 'ready'),
        'message' => 'Readiness status: ' . (string) ($readiness['status'] ?? 'review_required'),
    ];
    $checks[] = [
        'item' => 'public_smoke_pass_fresh',
        'required' => $requirePublic,
        'ok' => !$requirePublic || (is_array($latestPublicPass) && $publicAge !== null && $publicAge <= $window),
        'message' => is_array($latestPublicPass)
            ? ('Latest passing public smoke age: ' . (int) $publicAge . ' minute(s).')
            : 'No passing public smoke run found.',
    ];
    $checks[] = [
        'item' => 'auth_smoke_pass_fresh',
        'required' => $requireAuth,
        'ok' => !$requireAuth || (is_array($latestAuthPass) && $authAge !== null && $authAge <= $window),
        'message' => is_array($latestAuthPass)
            ? ('Latest passing auth smoke age: ' . (int) $authAge . ' minute(s).')
            : 'No passing auth smoke run found.',
    ];
    $checks[] = [
        'item' => 'cutover_signoff_fresh',
        'required' => $requireSignoff,
        'ok' => !$requireSignoff || !empty($activeSignoff['is_fresh']),
        'message' => !empty($activeSignoff['active'])
            ? ('Active cutover signoff age: ' . (int) ($activeSignoff['age_minutes'] ?? 0) . ' minute(s).')
            : 'No active cutover signoff found.',
    ];
    $checks[] = [
        'item' => 'cutover_signoff_integrity_valid',
        'required' => ($requireSignoff && $requireSignoffIntegrity) ? 1 : 0,
        'ok' => !($requireSignoff && $requireSignoffIntegrity) || (is_array($activeSignoffIntegrity) && !empty($activeSignoffIntegrity['verifiable']) && !empty($activeSignoffIntegrity['valid'])),
        'message' => is_array($activeSignoffIntegrity)
            ? ('Active signoff integrity status: ' . (string) ($activeSignoffIntegrity['reason'] ?? 'unknown'))
            : 'No active signoff available for integrity validation.',
    ];
    $checks[] = [
        'item' => 'cutover_signoff_integrity_watch_valid_and_fresh',
        'required' => ($requireSignoff && $requireSignoffIntegrity && $requireSignoffIntegrityWatch) ? 1 : 0,
        'ok' => !($requireSignoff && $requireSignoffIntegrity && $requireSignoffIntegrityWatch)
            || (($watchStatus === 'valid') && ($watchAgeMinutes !== null) && ($watchAgeMinutes <= $window) && $watchTargetsActiveSignoff),
        'message' => ($watchAgeMinutes !== null)
            ? ('Signoff integrity watch status: ' . strtoupper($watchStatus !== '' ? $watchStatus : 'unknown') . '; age: ' . (int) $watchAgeMinutes . ' minute(s); active signoff match: ' . ($watchTargetsActiveSignoff ? 'yes' : 'no') . '.')
            : 'No signoff integrity watch run found.',
    ];
    $checks[] = [
        'item' => 'watchdogs_policy_baseline_match',
        'required' => $requirePolicyBaselineMatch,
        'ok' => !$requirePolicyBaselineMatch || (!empty($policyBaseline['has_baseline']) && empty($policyBaseline['has_changes'])),
        'message' => empty($policyBaseline['has_baseline'])
            ? 'No watchdog policy baseline configured.'
            : ('Watchdogs policy baseline drift count: ' . (int) ($policyBaseline['changed_count'] ?? 0) . '.'),
    ];
    $checks[] = [
        'item' => 'watchdogs_policy_baseline_check_ok_and_fresh',
        'required' => $requirePolicyBaselineCheck,
        'ok' => !$requirePolicyBaselineCheck
            || (($policyBaselineCheckStatus === 'ok') && ($policyBaselineCheckAgeMinutes !== null) && ($policyBaselineCheckAgeMinutes <= $window)),
        'message' => ($policyBaselineCheckAgeMinutes !== null)
            ? ('Watchdogs policy baseline check status: ' . strtoupper($policyBaselineCheckStatus) . '; age: ' . (int) $policyBaselineCheckAgeMinutes . ' minute(s).')
            : 'No watchdog policy baseline check run found.',
    ];

    $reasons = [];
    foreach ($checks as $check) {
        if (empty($check['ok'])) {
            $reasons[] = (string) ($check['message'] ?? 'Release gate check failed.');
        }
    }

    return [
        'generated_at' => gmdate('c'),
        'allowed' => empty($reasons),
        'freshness_window_minutes' => $window,
        'settings' => [
            'require_readiness' => $requireReadiness,
            'require_public_smoke' => $requirePublic,
            'require_auth_smoke' => $requireAuth,
            'require_cutover_signoff' => $requireSignoff,
            'require_signoff_integrity' => $requireSignoffIntegrity,
            'require_signoff_integrity_watch' => $requireSignoffIntegrityWatch,
            'require_watchdogs_policy_baseline_match' => $requirePolicyBaselineMatch,
            'require_watchdogs_policy_baseline_check' => $requirePolicyBaselineCheck,
        ],
        'reasons' => $reasons,
        'checks' => $checks,
        'latest_public_pass' => $latestPublicPass,
        'latest_auth_pass' => $latestAuthPass,
        'latest_public_pass_age_minutes' => $publicAge,
        'latest_auth_pass_age_minutes' => $authAge,
        'active_cutover_signoff' => $activeSignoff,
        'active_cutover_signoff_integrity' => $activeSignoffIntegrity,
        'active_cutover_signoff_integrity_watch' => $signoffIntegrityWatch,
        'watchdogs_policy_baseline' => $policyBaseline,
        'watchdogs_policy_baseline_check' => $policyBaselineCheck,
        'readiness' => [
            'status' => (string) ($readiness['status'] ?? 'review_required'),
            'summary' => isset($readiness['summary']) && is_array($readiness['summary']) ? $readiness['summary'] : [],
        ],
    ];
}

function deployment_release_candidate_snapshot($note = '', $freshnessMinutes = null)
{
    $bundle = deployment_handoff_bundle_snapshot();
    $guardEval = deployment_guard_evaluate();
    $gate = deployment_release_gate_snapshot($freshnessMinutes);

    $ready = ((string) ($bundle['status'] ?? 'review_required') === 'ready') && !empty($guardEval['allowed']) && !empty($gate['allowed']);
    $candidate = [
        'candidate_id' => 'release_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'note' => trim((string) $note),
        'status' => $ready ? 'ready' : 'blocked',
        'summary' => isset($bundle['summary']) && is_array($bundle['summary']) ? $bundle['summary'] : [],
        'guard_allowed' => !empty($guardEval['allowed']) ? 1 : 0,
        'guard_reasons' => $guardEval['reasons'],
        'release_gate_allowed' => !empty($gate['allowed']) ? 1 : 0,
        'release_gate_reasons' => isset($gate['reasons']) && is_array($gate['reasons']) ? $gate['reasons'] : [],
        'release_gate_window_minutes' => (int) ($gate['freshness_window_minutes'] ?? 30),
        'failed_environment_checklist' => isset($bundle['failed_environment_checklist']) && is_array($bundle['failed_environment_checklist']) ? $bundle['failed_environment_checklist'] : [],
        'bundle_id' => (string) ($bundle['bundle_id'] ?? ''),
    ];

    $rows = app_read_json_file(deployment_release_log_path(), []);
    array_unshift($rows, $candidate);
    $rows = array_slice($rows, 0, 200);
    app_write_json_file(deployment_release_log_path(), $rows);

    return [
        'candidate' => $candidate,
        'bundle' => $bundle,
        'release_gate' => $gate,
    ];
}

function deployment_release_gate_failed_items($gate)
{
    $checks = isset($gate['checks']) && is_array($gate['checks']) ? $gate['checks'] : [];
    $failed = [];
    foreach ($checks as $check) {
        if (!is_array($check)) {
            continue;
        }
        if (!empty($check['ok'])) {
            continue;
        }
        $item = trim((string) ($check['item'] ?? ''));
        if ($item !== '') {
            $failed[] = $item;
        }
    }
    return $failed;
}

function deployment_release_gate_sustained_state_snapshot($state)
{
    if (!is_array($state)) {
        $state = [];
    }
    return [
        'active' => !empty($state['sustained_blocked_active']) ? 1 : 0,
        'recent_ratio_percent' => isset($state['sustained_blocked_recent_ratio_percent']) ? (float) $state['sustained_blocked_recent_ratio_percent'] : 0.0,
        'recent_window_runs' => isset($state['sustained_blocked_recent_window_runs']) ? (int) $state['sustained_blocked_recent_window_runs'] : 0,
        'last_changed_at' => isset($state['sustained_blocked_last_changed_at']) ? (string) $state['sustained_blocked_last_changed_at'] : '',
        'last_alert_at' => isset($state['sustained_blocked_last_alert_at']) ? (string) $state['sustained_blocked_last_alert_at'] : '',
    ];
}

function deployment_release_gate_sustained_timeline_snapshot($runs, $limit = 12)
{
    if (!is_array($runs)) {
        $runs = [];
    }
    $safeLimit = max(1, min(50, (int) $limit));
    $timeline = [
        'current_active' => 0,
        'current_since' => '',
        'current_streak_runs' => 0,
        'transition_count' => 0,
        'latest_transition' => null,
        'transitions' => [],
    ];
    if (empty($runs) || !is_array($runs[0])) {
        return $timeline;
    }

    $currentActive = !empty($runs[0]['sustained_blocked_active']) ? 1 : 0;
    $timeline['current_active'] = $currentActive;
    $currentSince = '';
    foreach ($runs as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rowActive = !empty($row['sustained_blocked_active']) ? 1 : 0;
        if ($rowActive !== $currentActive) {
            break;
        }
        $timeline['current_streak_runs']++;
        $createdAt = (string) ($row['created_at'] ?? '');
        if ($createdAt !== '') {
            $currentSince = $createdAt;
        }
    }
    $timeline['current_since'] = $currentSince;

    $transitionCount = 0;
    $transitions = [];
    $runCount = count($runs);
    for ($i = 0; $i < ($runCount - 1); $i++) {
        $row = $runs[$i];
        $older = $runs[$i + 1];
        if (!is_array($row) || !is_array($older)) {
            continue;
        }
        $active = !empty($row['sustained_blocked_active']) ? 1 : 0;
        $olderActive = !empty($older['sustained_blocked_active']) ? 1 : 0;
        if ($active === $olderActive) {
            continue;
        }
        $transitionCount++;
        if (count($transitions) >= $safeLimit) {
            continue;
        }
        $transitions[] = [
            'run_id' => (string) ($row['run_id'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'source' => (string) ($row['source'] ?? ''),
            'transitioned_to' => $active === 1 ? 'active' : 'clear',
            'recent_ratio_percent' => isset($row['recent_blocked_ratio_percent']) ? (float) $row['recent_blocked_ratio_percent'] : 0.0,
            'recent_window_runs' => isset($row['recent_window_runs']) ? (int) $row['recent_window_runs'] : 0,
        ];
    }

    $timeline['transition_count'] = $transitionCount;
    $timeline['transitions'] = $transitions;
    if (!empty($transitions)) {
        $timeline['latest_transition'] = $transitions[0];
    }

    return $timeline;
}

function deployment_release_gate_sustained_transition_limit_from_query($query, $default = 12)
{
    $limit = isset($query['transition_limit']) ? (int) $query['transition_limit'] : (int) $default;
    return max(1, min(50, $limit));
}

function deployment_release_gate_quick_digest_snapshot($count, $summary, $sustainedState, $sustainedTimeline)
{
    if (!is_array($summary)) {
        $summary = [];
    }
    if (!is_array($sustainedState)) {
        $sustainedState = [];
    }
    if (!is_array($sustainedTimeline)) {
        $sustainedTimeline = [];
    }
    $blockedRatio = isset($summary['blocked_ratio_percent']) ? (float) $summary['blocked_ratio_percent'] : 0.0;
    $statusChangedRatio = isset($summary['status_changed_ratio_percent']) ? (float) $summary['status_changed_ratio_percent'] : 0.0;
    $sustainedActive = !empty($sustainedState['active']) ? 1 : 0;
    $sustainedRecentRatio = isset($sustainedState['recent_ratio_percent']) ? (float) $sustainedState['recent_ratio_percent'] : 0.0;
    $lines = [];
    $lines[] = 'count=' . (int) $count
        . ', blocked_ratio=' . $blockedRatio . '%'
        . ', status_changed_ratio=' . $statusChangedRatio . '%';
    $lines[] = 'sustained=' . ($sustainedActive === 1 ? 'active' : 'clear')
        . ', recent_ratio=' . $sustainedRecentRatio . '%'
        . ', transition_count=' . (int) ($sustainedTimeline['transition_count'] ?? 0);
    return implode("\n", $lines);
}

function deployment_release_gate_runs_quickstats_snapshot($query)
{
    $runs = app_read_json_file(deployment_release_gate_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    $quickQuery = is_array($query) ? $query : [];
    $quickQuery['limit'] = 400;
    $filter = deployment_release_gate_runs_apply_filters($runs, $quickQuery);
    $items = isset($filter['items']) && is_array($filter['items']) ? $filter['items'] : [];
    $summary = deployment_release_gate_runs_summary($items);
    $state = app_read_json_file(deployment_release_gate_state_path(), []);
    $sustainedState = deployment_release_gate_sustained_state_snapshot($state);
    $transitionLimit = deployment_release_gate_sustained_transition_limit_from_query($query, 12);
    $sustainedTimeline = deployment_release_gate_sustained_timeline_snapshot($items, $transitionLimit);
    $quickDigest = deployment_release_gate_quick_digest_snapshot(count($items), $summary, $sustainedState, $sustainedTimeline);
    return [
        'count' => count($items),
        'applied_filters' => isset($filter['applied_filters']) && is_array($filter['applied_filters']) ? $filter['applied_filters'] : [],
        'summary' => $summary,
        'sustained_state' => $sustainedState,
        'sustained_timeline' => $sustainedTimeline,
        'sustained_transition_limit' => $transitionLimit,
        'quick_digest' => $quickDigest,
    ];
}

function deployment_release_gate_runs_meta_snapshot($query)
{
    $runs = app_read_json_file(deployment_release_gate_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    $metaQuery = is_array($query) ? $query : [];
    $metaQuery['source'] = '';
    $metaQuery['source_contains'] = '';
    $metaQuery['failed_item'] = '';
    $metaQuery['failed_item_mode'] = 'exact';
    $metaQuery['limit'] = 400;
    $filter = deployment_release_gate_runs_apply_filters($runs, $metaQuery);
    $items = isset($filter['items']) && is_array($filter['items']) ? $filter['items'] : [];

    $sourceCounts = [];
    $failedCounts = [];
    $sourceGroupCounts = ['scheduler' => 0, 'manual' => 0];
    $statusChangeCounts = ['changed' => 0, 'stable' => 0];
    $transitionToCounts = ['to_blocked' => 0, 'to_allowed' => 0];
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }
        $sourceRaw = trim((string) ($row['source'] ?? ''));
        $sourceLower = strtolower($sourceRaw);
        $isScheduler = (strpos($sourceLower, 'scheduler_') === 0);
        $sourceGroupCounts[$isScheduler ? 'scheduler' : 'manual']++;
        $statusChanged = !empty($row['status_changed']) ? 1 : 0;
        $statusChangeCounts[$statusChanged === 1 ? 'changed' : 'stable']++;
        if ($statusChanged === 1) {
            $allowed = !empty($row['allowed']) ? 1 : 0;
            if ($allowed === 1) {
                $transitionToCounts['to_allowed']++;
            } else {
                $transitionToCounts['to_blocked']++;
            }
        }
        $source = $sourceRaw;
        if ($sourceRaw === '') {
            $source = '(unknown)';
        }
        if (!isset($sourceCounts[$source])) {
            $sourceCounts[$source] = 0;
        }
        $sourceCounts[$source]++;

        $failedItems = isset($row['failed_items']) && is_array($row['failed_items']) ? $row['failed_items'] : [];
        foreach ($failedItems as $item) {
            $key = trim((string) $item);
            if ($key === '') {
                continue;
            }
            if (!isset($failedCounts[$key])) {
                $failedCounts[$key] = 0;
            }
            $failedCounts[$key]++;
        }
    }
    if (!empty($sourceCounts)) {
        arsort($sourceCounts);
    }
    if (!empty($failedCounts)) {
        arsort($failedCounts);
    }

    return [
        'items_considered' => count($items),
        'applied_filters' => isset($filter['applied_filters']) && is_array($filter['applied_filters']) ? $filter['applied_filters'] : [],
        'source_options' => array_slice($sourceCounts, 0, 50, true),
        'failed_item_options' => array_slice($failedCounts, 0, 50, true),
        'source_group_counts' => $sourceGroupCounts,
        'status_change_counts' => $statusChangeCounts,
        'transition_to_counts' => $transitionToCounts,
        'source_group_options' => ['all', 'scheduler', 'manual'],
        'failed_item_mode_options' => ['exact', 'contains'],
    ];
}

function deployment_release_gate_runs_summary($runs)
{
    if (!is_array($runs)) {
        $runs = [];
    }
    $summary = [
        'total_runs' => 0,
        'allowed_runs' => 0,
        'blocked_runs' => 0,
        'status_changed_runs' => 0,
        'alert_sent_runs' => 0,
        'sustained_active_runs' => 0,
        'sustained_clear_runs' => 0,
        'sustained_alert_sent_runs' => 0,
        'scheduler_runs' => 0,
        'manual_runs' => 0,
        'scheduler_blocked_runs' => 0,
        'manual_blocked_runs' => 0,
        'baseline_match_blocked_runs' => 0,
        'baseline_check_blocked_runs' => 0,
        'signoff_integrity_watch_blocked_runs' => 0,
        'failed_item_counts' => [],
        'latest_blocked_run' => null,
        'latest_allowed_run' => null,
        'latest_status_change_run' => null,
    ];
    foreach ($runs as $row) {
        if (!is_array($row)) {
            continue;
        }
        $summary['total_runs']++;
        $allowed = !empty($row['allowed']) ? 1 : 0;
        if ($allowed === 1) {
            $summary['allowed_runs']++;
            if (!is_array($summary['latest_allowed_run'])) {
                $summary['latest_allowed_run'] = [
                    'run_id' => (string) ($row['run_id'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'source' => (string) ($row['source'] ?? ''),
                    'reason_count' => isset($row['reason_count']) ? (int) $row['reason_count'] : 0,
                ];
            }
        } else {
            $summary['blocked_runs']++;
            if (!is_array($summary['latest_blocked_run'])) {
                $summary['latest_blocked_run'] = [
                    'run_id' => (string) ($row['run_id'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'failed_items' => isset($row['failed_items']) && is_array($row['failed_items']) ? $row['failed_items'] : [],
                    'reason_count' => isset($row['reason_count']) ? (int) $row['reason_count'] : 0,
                ];
            }
        }
        $sourceValue = strtolower(trim((string) ($row['source'] ?? '')));
        $isScheduler = (strpos($sourceValue, 'scheduler_') === 0);
        if ($isScheduler) {
            $summary['scheduler_runs']++;
            if ($allowed === 0) {
                $summary['scheduler_blocked_runs']++;
            }
        } else {
            $summary['manual_runs']++;
            if ($allowed === 0) {
                $summary['manual_blocked_runs']++;
            }
        }
        if (!empty($row['status_changed'])) {
            $summary['status_changed_runs']++;
            if (!is_array($summary['latest_status_change_run'])) {
                $summary['latest_status_change_run'] = [
                    'run_id' => (string) ($row['run_id'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'source' => (string) ($row['source'] ?? ''),
                    'allowed' => $allowed,
                ];
            }
        }
        if (!empty($row['alert_sent'])) {
            $summary['alert_sent_runs']++;
        }
        if (!empty($row['sustained_blocked_active'])) {
            $summary['sustained_active_runs']++;
        } else {
            $summary['sustained_clear_runs']++;
        }
        if (!empty($row['sustained_blocked_alert_sent'])) {
            $summary['sustained_alert_sent_runs']++;
        }
        $failedItems = isset($row['failed_items']) && is_array($row['failed_items']) ? $row['failed_items'] : [];
        if (in_array('watchdogs_policy_baseline_match', $failedItems, true)) {
            $summary['baseline_match_blocked_runs']++;
        }
        if (in_array('watchdogs_policy_baseline_check_ok_and_fresh', $failedItems, true)) {
            $summary['baseline_check_blocked_runs']++;
        }
        if (in_array('cutover_signoff_integrity_watch_valid_and_fresh', $failedItems, true)) {
            $summary['signoff_integrity_watch_blocked_runs']++;
        }
        foreach ($failedItems as $item) {
            $key = trim((string) $item);
            if ($key === '') {
                continue;
            }
            if (!isset($summary['failed_item_counts'][$key])) {
                $summary['failed_item_counts'][$key] = 0;
            }
            $summary['failed_item_counts'][$key]++;
        }
    }
    if (!empty($summary['failed_item_counts'])) {
        arsort($summary['failed_item_counts']);
    }
    $summary['top_failed_items'] = array_slice($summary['failed_item_counts'], 0, 10, true);
    $total = max(1, (int) ($summary['total_runs'] ?? 0));
    $blocked = (int) ($summary['blocked_runs'] ?? 0);
    $summary['blocked_ratio_percent'] = round(($blocked / $total) * 100, 2);
    $summary['allowed_ratio_percent'] = round((((int) ($summary['allowed_runs'] ?? 0)) / $total) * 100, 2);
    $summary['status_changed_ratio_percent'] = round((((int) ($summary['status_changed_runs'] ?? 0)) / $total) * 100, 2);
    $summary['sustained_active_ratio_percent'] = round((((int) ($summary['sustained_active_runs'] ?? 0)) / $total) * 100, 2);
    $summary['sustained_alert_sent_ratio_percent'] = round((((int) ($summary['sustained_alert_sent_runs'] ?? 0)) / $total) * 100, 2);
    $schedulerTotal = max(1, (int) ($summary['scheduler_runs'] ?? 0));
    $manualTotal = max(1, (int) ($summary['manual_runs'] ?? 0));
    $summary['scheduler_blocked_ratio_percent'] = round((((int) ($summary['scheduler_blocked_runs'] ?? 0)) / $schedulerTotal) * 100, 2);
    $summary['manual_blocked_ratio_percent'] = round((((int) ($summary['manual_blocked_runs'] ?? 0)) / $manualTotal) * 100, 2);
    $denominator = max(1, $blocked);
    $summary['baseline_match_blocked_share_percent'] = round((((int) ($summary['baseline_match_blocked_runs'] ?? 0)) / $denominator) * 100, 2);
    $summary['baseline_check_blocked_share_percent'] = round((((int) ($summary['baseline_check_blocked_runs'] ?? 0)) / $denominator) * 100, 2);
    $summary['signoff_integrity_watch_blocked_share_percent'] = round((((int) ($summary['signoff_integrity_watch_blocked_runs'] ?? 0)) / $denominator) * 100, 2);
    return $summary;
}

function deployment_release_gate_runs_apply_filters($runs, $query)
{
    if (!is_array($runs)) {
        $runs = [];
    }
    $window = isset($query['window']) ? (int) $query['window'] : 0;
    $window = max(0, min(400, $window));
    $limit = isset($query['limit']) ? (int) $query['limit'] : 200;
    $limit = max(1, min(400, $limit));
    $allowedFilter = strtolower(trim((string) ($query['allowed'] ?? 'all')));
    if (!in_array($allowedFilter, ['all', 'allowed', 'blocked'], true)) {
        $allowedFilter = 'all';
    }
    $sustainedFilter = strtolower(trim((string) ($query['sustained'] ?? 'all')));
    if (!in_array($sustainedFilter, ['all', 'active', 'clear'], true)) {
        $sustainedFilter = 'all';
    }
    $sustainedAlertFilter = strtolower(trim((string) ($query['sustained_alert'] ?? 'all')));
    if (!in_array($sustainedAlertFilter, ['all', 'sent', 'not_sent'], true)) {
        $sustainedAlertFilter = 'all';
    }
    $statusChangeFilter = strtolower(trim((string) ($query['status_change'] ?? 'all')));
    if (!in_array($statusChangeFilter, ['all', 'changed', 'stable'], true)) {
        $statusChangeFilter = 'all';
    }
    $transitionToFilter = strtolower(trim((string) ($query['transition_to'] ?? 'all')));
    if (!in_array($transitionToFilter, ['all', 'to_blocked', 'to_allowed'], true)) {
        $transitionToFilter = 'all';
    }
    $sourceGroupFilter = strtolower(trim((string) ($query['source_group'] ?? 'all')));
    if (!in_array($sourceGroupFilter, ['all', 'scheduler', 'manual'], true)) {
        $sourceGroupFilter = 'all';
    }
    $sourceFilter = trim((string) ($query['source'] ?? ''));
    $sourceContainsRaw = trim((string) ($query['source_contains'] ?? ''));
    $sourceContains = strtolower($sourceContainsRaw);
    $failedItemFilterRaw = trim((string) ($query['failed_item'] ?? ''));
    $failedItemFilter = strtolower($failedItemFilterRaw);
    $failedItemMode = strtolower(trim((string) ($query['failed_item_mode'] ?? 'exact')));
    if (!in_array($failedItemMode, ['exact', 'contains'], true)) {
        $failedItemMode = 'exact';
    }
    $reasonCountMinRaw = isset($query['reason_count_min']) ? trim((string) $query['reason_count_min']) : '';
    $reasonCountMaxRaw = isset($query['reason_count_max']) ? trim((string) $query['reason_count_max']) : '';
    $reasonCountMin = ($reasonCountMinRaw === '') ? null : max(0, min(50, (int) $reasonCountMinRaw));
    $reasonCountMax = ($reasonCountMaxRaw === '') ? null : max(0, min(50, (int) $reasonCountMaxRaw));
    if ($reasonCountMin !== null && $reasonCountMax !== null && $reasonCountMin > $reasonCountMax) {
        $swap = $reasonCountMin;
        $reasonCountMin = $reasonCountMax;
        $reasonCountMax = $swap;
    }
    $recentRatioMinRaw = isset($query['recent_ratio_min']) ? trim((string) $query['recent_ratio_min']) : '';
    $recentRatioMin = ($recentRatioMinRaw === '') ? null : max(0, min(100, (float) $recentRatioMinRaw));

    $sourceRows = ($window > 0) ? array_slice($runs, 0, $window) : $runs;
    $filtered = [];
    foreach ($sourceRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $allowed = !empty($row['allowed']) ? 1 : 0;
        if ($allowedFilter === 'allowed' && $allowed !== 1) {
            continue;
        }
        if ($allowedFilter === 'blocked' && $allowed !== 0) {
            continue;
        }
        $sustainedActive = !empty($row['sustained_blocked_active']) ? 1 : 0;
        if ($sustainedFilter === 'active' && $sustainedActive !== 1) {
            continue;
        }
        if ($sustainedFilter === 'clear' && $sustainedActive !== 0) {
            continue;
        }
        $sustainedAlertSent = !empty($row['sustained_blocked_alert_sent']) ? 1 : 0;
        if ($sustainedAlertFilter === 'sent' && $sustainedAlertSent !== 1) {
            continue;
        }
        if ($sustainedAlertFilter === 'not_sent' && $sustainedAlertSent !== 0) {
            continue;
        }
        $statusChanged = !empty($row['status_changed']) ? 1 : 0;
        if ($statusChangeFilter === 'changed' && $statusChanged !== 1) {
            continue;
        }
        if ($statusChangeFilter === 'stable' && $statusChanged !== 0) {
            continue;
        }
        if ($transitionToFilter === 'to_blocked' && !($statusChanged === 1 && $allowed === 0)) {
            continue;
        }
        if ($transitionToFilter === 'to_allowed' && !($statusChanged === 1 && $allowed === 1)) {
            continue;
        }
        $sourceValue = strtolower(trim((string) ($row['source'] ?? '')));
        $isScheduler = (strpos($sourceValue, 'scheduler_') === 0);
        if ($sourceGroupFilter === 'scheduler' && !$isScheduler) {
            continue;
        }
        if ($sourceGroupFilter === 'manual' && $isScheduler) {
            continue;
        }
        if ($sourceFilter !== '' && !hash_equals(strtolower(trim((string) ($row['source'] ?? ''))), strtolower($sourceFilter))) {
            continue;
        }
        if ($sourceContains !== '' && strpos($sourceValue, $sourceContains) === false) {
            continue;
        }
        $reasonCount = isset($row['reason_count']) ? (int) $row['reason_count'] : 0;
        if ($reasonCountMin !== null && $reasonCount < $reasonCountMin) {
            continue;
        }
        if ($reasonCountMax !== null && $reasonCount > $reasonCountMax) {
            continue;
        }
        $recentRatio = isset($row['recent_blocked_ratio_percent']) ? (float) $row['recent_blocked_ratio_percent'] : 0.0;
        if ($recentRatioMin !== null && $recentRatio < $recentRatioMin) {
            continue;
        }
        if ($failedItemFilter !== '') {
            $failedItems = isset($row['failed_items']) && is_array($row['failed_items']) ? $row['failed_items'] : [];
            $matched = false;
            foreach ($failedItems as $item) {
                $candidate = strtolower(trim((string) $item));
                if ($candidate === '') {
                    continue;
                }
                if ($failedItemMode === 'exact' && hash_equals($candidate, $failedItemFilter)) {
                    $matched = true;
                    break;
                }
                if ($failedItemMode === 'contains' && strpos($candidate, $failedItemFilter) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return [
        'items' => array_slice($filtered, 0, $limit),
        'filtered_total_count' => count($filtered),
        'window_total_count' => count($sourceRows),
        'total_count' => count($runs),
        'applied_filters' => [
            'limit' => $limit,
            'window' => $window,
            'allowed' => $allowedFilter,
            'sustained' => $sustainedFilter,
            'sustained_alert' => $sustainedAlertFilter,
            'status_change' => $statusChangeFilter,
            'transition_to' => $transitionToFilter,
            'source_group' => $sourceGroupFilter,
            'source' => $sourceFilter,
            'source_contains' => $sourceContainsRaw,
            'failed_item' => $failedItemFilterRaw,
            'failed_item_mode' => $failedItemMode,
            'reason_count_min' => $reasonCountMin,
            'reason_count_max' => $reasonCountMax,
            'recent_ratio_min' => $recentRatioMin,
        ],
    ];
}

function deployment_release_gate_watch_snapshot($source = 'manual', $freshnessMinutes = null)
{
    $gate = deployment_release_gate_snapshot($freshnessMinutes);
    $state = app_read_json_file(deployment_release_gate_state_path(), []);
    if (!is_array($state)) {
        $state = [];
    }
    $failedItems = deployment_release_gate_failed_items($gate);
    sort($failedItems);
    $blockerDigest = empty($failedItems) ? 'none' : implode(', ', array_slice($failedItems, 0, 3));
    $previousFailedItems = isset($state['last_failed_items']) && is_array($state['last_failed_items']) ? $state['last_failed_items'] : [];
    sort($previousFailedItems);
    $failedItemsChanged = ($failedItems !== $previousFailedItems) ? 1 : 0;
    $baselineMatchBlocked = in_array('watchdogs_policy_baseline_match', $failedItems, true) ? 1 : 0;
    $baselineCheckBlocked = in_array('watchdogs_policy_baseline_check_ok_and_fresh', $failedItems, true) ? 1 : 0;
    $signoffIntegrityWatchBlocked = in_array('cutover_signoff_integrity_watch_valid_and_fresh', $failedItems, true) ? 1 : 0;
    $baselineBlockersActive = ($baselineMatchBlocked || $baselineCheckBlocked) ? 1 : 0;
    $prevBaselineBlockersActive = !empty($state['last_baseline_blockers_active']) ? 1 : 0;
    $baselineBlockersChanged = ($baselineBlockersActive !== $prevBaselineBlockersActive) ? 1 : 0;
    $previousAllowed = array_key_exists('last_allowed', $state) ? !empty($state['last_allowed']) : null;
    $allowed = !empty($gate['allowed']) ? 1 : 0;
    $statusChanged = ($previousAllowed !== null) ? (((int) $previousAllowed) !== $allowed) : false;
    $now = gmdate('c');
    $alertSent = 0;
    $alertType = '';
    $alertMessage = '';

    $lastAlertAt = isset($state['last_alert_at']) ? (string) $state['last_alert_at'] : '';
    $lastAlertTs = $lastAlertAt !== '' ? strtotime($lastAlertAt) : false;
    $cooldownMinutes = 30;
    $cooldownActive = ($lastAlertTs !== false) ? ((time() - $lastAlertTs) < ($cooldownMinutes * 60)) : false;

    if ($statusChanged) {
        if ($allowed === 1) {
            $alertType = 'success';
            $alertMessage = 'Release gate changed to ALLOWED.';
        } else {
            $alertType = 'critical';
            $alertMessage = 'Release gate changed to BLOCKED.';
        }
        $alertSent = 1;
    } elseif ($allowed === 0 && !$cooldownActive) {
        $alertType = 'warning';
        $alertMessage = 'Release gate remains BLOCKED.';
        $alertSent = 1;
    }

    if ($alertSent === 1) {
        push_notification($alertType, $alertMessage, [
            'source' => (string) $source,
            'freshness_window_minutes' => (int) ($gate['freshness_window_minutes'] ?? 30),
            'reasons' => isset($gate['reasons']) && is_array($gate['reasons']) ? $gate['reasons'] : [],
            'failed_items' => $failedItems,
            'blocker_digest' => $blockerDigest,
            'blocker_flags' => [
                'baseline_match_blocked' => $baselineMatchBlocked,
                'baseline_check_blocked' => $baselineCheckBlocked,
                'signoff_integrity_watch_blocked' => $signoffIntegrityWatchBlocked,
            ],
        ]);
    }

    if ($allowed === 0 && $baselineBlockersActive === 1 && ($baselineBlockersChanged === 1 || $failedItemsChanged === 1)) {
        push_notification('warning', 'Release gate blocked by watchdogs policy baseline requirements.', [
            'source' => (string) $source,
            'freshness_window_minutes' => (int) ($gate['freshness_window_minutes'] ?? 30),
            'failed_items' => $failedItems,
            'blocker_digest' => $blockerDigest,
            'blocker_flags' => [
                'baseline_match_blocked' => $baselineMatchBlocked,
                'baseline_check_blocked' => $baselineCheckBlocked,
            ],
        ]);
    }
    if ($failedItemsChanged === 1) {
        audit_event('deployment', 'release.gate.watch.blockers', [
            'source' => (string) $source,
            'allowed' => $allowed,
            'failed_items' => $failedItems,
            'previous_failed_items' => $previousFailedItems,
            'baseline_blockers_active' => $baselineBlockersActive,
        ]);
    }

    $run = [
        'run_id' => 'gate_watch_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => $now,
        'source' => (string) $source,
        'allowed' => $allowed,
        'status_changed' => $statusChanged ? 1 : 0,
        'alert_sent' => $alertSent,
        'freshness_window_minutes' => (int) ($gate['freshness_window_minutes'] ?? 30),
        'reason_count' => isset($gate['reasons']) && is_array($gate['reasons']) ? count($gate['reasons']) : 0,
        'reasons' => isset($gate['reasons']) && is_array($gate['reasons']) ? $gate['reasons'] : [],
        'failed_items' => $failedItems,
        'blocker_digest' => $blockerDigest,
        'failed_items_changed' => $failedItemsChanged,
        'blocker_flags' => [
            'baseline_match_blocked' => $baselineMatchBlocked,
            'baseline_check_blocked' => $baselineCheckBlocked,
            'signoff_integrity_watch_blocked' => $signoffIntegrityWatchBlocked,
        ],
    ];
    $runs = app_read_json_file(deployment_release_gate_runs_path(), []);
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 400);
    app_write_json_file(deployment_release_gate_runs_path(), $runs);

    $recentWindowRuns = array_slice($runs, 0, 20);
    $recentSummary = deployment_release_gate_runs_summary($recentWindowRuns);
    $recentBlockedRatio = (float) ($recentSummary['blocked_ratio_percent'] ?? 0.0);
    $recentWindowTotal = (int) ($recentSummary['total_runs'] ?? 0);
    $sustainedBlockedActive = ($recentWindowTotal >= 10 && $recentBlockedRatio >= 70.0) ? 1 : 0;
    $prevSustainedBlockedActive = !empty($state['sustained_blocked_active']) ? 1 : 0;
    $sustainedBlockedChanged = ($sustainedBlockedActive !== $prevSustainedBlockedActive) ? 1 : 0;
    $sustainedBlockedAlertSent = 0;
    $lastSustainedAlertAt = isset($state['sustained_blocked_last_alert_at']) ? (string) $state['sustained_blocked_last_alert_at'] : '';
    $lastSustainedAlertTs = $lastSustainedAlertAt !== '' ? strtotime($lastSustainedAlertAt) : false;
    $sustainedCooldownActive = ($lastSustainedAlertTs !== false) ? ((time() - $lastSustainedAlertTs) < (30 * 60)) : false;

    if ($sustainedBlockedChanged === 1) {
        if ($sustainedBlockedActive === 1) {
            push_notification('warning', 'Release gate blocked trend is sustained over recent runs.', [
                'source' => (string) $source,
                'recent_window_runs' => $recentWindowTotal,
                'recent_blocked_ratio_percent' => $recentBlockedRatio,
                'blocker_digest' => $blockerDigest,
            ]);
        } else {
            push_notification('success', 'Release gate sustained blocked trend cleared.', [
                'source' => (string) $source,
                'recent_window_runs' => $recentWindowTotal,
                'recent_blocked_ratio_percent' => $recentBlockedRatio,
            ]);
        }
        $sustainedBlockedAlertSent = 1;
    } elseif ($sustainedBlockedActive === 1 && !$sustainedCooldownActive) {
        push_notification('warning', 'Release gate remains in sustained blocked trend.', [
            'source' => (string) $source,
            'recent_window_runs' => $recentWindowTotal,
            'recent_blocked_ratio_percent' => $recentBlockedRatio,
            'blocker_digest' => $blockerDigest,
        ]);
        $sustainedBlockedAlertSent = 1;
    }
    if ($sustainedBlockedChanged === 1 || $sustainedBlockedAlertSent === 1) {
        audit_event('deployment', 'release.gate.watch.sustained_blocked', [
            'source' => (string) $source,
            'active' => $sustainedBlockedActive,
            'changed' => $sustainedBlockedChanged,
            'recent_window_runs' => $recentWindowTotal,
            'recent_blocked_ratio_percent' => $recentBlockedRatio,
            'alert_sent' => $sustainedBlockedAlertSent,
        ]);
    }

    $run['recent_blocked_ratio_percent'] = $recentBlockedRatio;
    $run['recent_window_runs'] = $recentWindowTotal;
    $run['sustained_blocked_active'] = $sustainedBlockedActive;
    $run['sustained_blocked_alert_sent'] = $sustainedBlockedAlertSent;
    if (!empty($runs) && is_array($runs[0])) {
        $runs[0] = $run;
        app_write_json_file(deployment_release_gate_runs_path(), $runs);
    }

    $nextState = [
        'last_checked_at' => $now,
        'last_allowed' => $allowed,
        'last_reasons' => isset($gate['reasons']) && is_array($gate['reasons']) ? $gate['reasons'] : [],
        'last_failed_items' => $failedItems,
        'last_blocker_digest' => $blockerDigest,
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => (string) $source,
        'last_alert_at' => $alertSent === 1 ? $now : (string) ($state['last_alert_at'] ?? ''),
        'last_alert_sent' => $alertSent,
        'last_baseline_blockers_active' => $baselineBlockersActive,
        'sustained_blocked_active' => $sustainedBlockedActive,
        'sustained_blocked_recent_ratio_percent' => $recentBlockedRatio,
        'sustained_blocked_recent_window_runs' => $recentWindowTotal,
        'sustained_blocked_last_alert_at' => $sustainedBlockedAlertSent === 1 ? $now : (string) ($state['sustained_blocked_last_alert_at'] ?? ''),
        'sustained_blocked_last_alert_sent' => $sustainedBlockedAlertSent,
        'sustained_blocked_last_changed_at' => $sustainedBlockedChanged === 1 ? $now : (string) ($state['sustained_blocked_last_changed_at'] ?? ''),
        'last_blocker_flags' => [
            'baseline_match_blocked' => $baselineMatchBlocked,
            'baseline_check_blocked' => $baselineCheckBlocked,
            'signoff_integrity_watch_blocked' => $signoffIntegrityWatchBlocked,
        ],
    ];
    app_write_json_file(deployment_release_gate_state_path(), $nextState);

    return [
        'run' => $run,
        'gate' => $gate,
        'state' => $nextState,
    ];
}

function deployment_watchdogs_status_snapshot($freshnessMinutes = null)
{
    $guardCfg = get_deployment_guard();
    $defaultWindow = isset($guardCfg['release_gate_freshness_minutes']) ? (int) $guardCfg['release_gate_freshness_minutes'] : 30;
    $windowInput = ($freshnessMinutes === null) ? $defaultWindow : (int) $freshnessMinutes;
    $window = max(5, min(1440, $windowInput));
    $nowTs = time();

    $gateState = app_read_json_file(deployment_release_gate_state_path(), []);
    if (!is_array($gateState)) {
        $gateState = [];
    }
    $signoffWatchState = app_read_json_file(deployment_cutover_signoff_integrity_state_path(), []);
    if (!is_array($signoffWatchState)) {
        $signoffWatchState = [];
    }

    $gateCheckedAt = (string) ($gateState['last_checked_at'] ?? '');
    $signoffCheckedAt = (string) ($signoffWatchState['last_checked_at'] ?? '');
    $gateAge = null;
    $signoffAge = null;
    if ($gateCheckedAt !== '') {
        $ts = strtotime($gateCheckedAt);
        if ($ts !== false) {
            $gateAge = max(0, (int) floor(($nowTs - $ts) / 60));
        }
    }
    if ($signoffCheckedAt !== '') {
        $ts = strtotime($signoffCheckedAt);
        if ($ts !== false) {
            $signoffAge = max(0, (int) floor(($nowTs - $ts) / 60));
        }
    }

    $gateAllowed = array_key_exists('last_allowed', $gateState) ? (!empty($gateState['last_allowed']) ? 1 : 0) : null;
    $signoffStatus = strtolower(trim((string) ($signoffWatchState['last_status'] ?? 'unknown')));
    if ($signoffStatus === '') {
        $signoffStatus = 'unknown';
    }

    $gateAllowedSeverity = ($gateAllowed === 0) ? 'critical' : 'warning';
    $signoffStatusSeverity = ($signoffStatus === 'invalid') ? 'critical' : 'warning';
    $checks = [
        [
            'item' => 'release_gate_watch_recent',
            'ok' => ($gateAge !== null) && ($gateAge <= $window),
            'severity' => 'warning',
            'message' => ($gateAge !== null)
                ? ('Release gate watch age: ' . (int) $gateAge . ' minute(s).')
                : 'Release gate watch has not run yet.',
        ],
        [
            'item' => 'release_gate_watch_allowed',
            'ok' => $gateAllowed === 1,
            'severity' => $gateAllowedSeverity,
            'message' => $gateAllowed === null
                ? 'Release gate watch allowed state is unknown.'
                : ('Release gate current allowed state: ' . ($gateAllowed === 1 ? 'ALLOWED' : 'BLOCKED') . '.'),
        ],
        [
            'item' => 'signoff_integrity_watch_recent',
            'ok' => ($signoffAge !== null) && ($signoffAge <= $window),
            'severity' => 'warning',
            'message' => ($signoffAge !== null)
                ? ('Signoff integrity watch age: ' . (int) $signoffAge . ' minute(s).')
                : 'Signoff integrity watch has not run yet.',
        ],
        [
            'item' => 'signoff_integrity_watch_valid',
            'ok' => $signoffStatus === 'valid',
            'severity' => $signoffStatusSeverity,
            'message' => 'Signoff integrity watch status: ' . strtoupper($signoffStatus) . '.',
        ],
    ];

    $criticalFailed = 0;
    $warningFailed = 0;
    foreach ($checks as $check) {
        if (!empty($check['ok'])) {
            continue;
        }
        if ((string) ($check['severity'] ?? 'warning') === 'critical') {
            $criticalFailed++;
        } else {
            $warningFailed++;
        }
    }

    $status = 'ok';
    if ($criticalFailed > 0) {
        $status = 'critical';
    } elseif ($warningFailed > 0) {
        $status = 'warning';
    }

    return [
        'generated_at' => gmdate('c'),
        'status' => $status,
        'freshness_window_minutes' => $window,
        'summary' => [
            'critical_failed' => $criticalFailed,
            'warning_failed' => $warningFailed,
            'check_count' => count($checks),
            'gate_watch_age_minutes' => $gateAge,
            'signoff_watch_age_minutes' => $signoffAge,
            'gate_allowed' => $gateAllowed,
            'signoff_watch_status' => $signoffStatus,
        ],
        'checks' => $checks,
        'release_gate_watch_state' => $gateState,
        'signoff_integrity_watch_state' => $signoffWatchState,
    ];
}

function deployment_find_open_watchdogs_incident()
{
    $rows = app_read_json_file(deployment_incident_reports_path(), []);
    if (!is_array($rows)) {
        return null;
    }
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $status = strtolower(trim((string) ($row['incident_status'] ?? 'open')));
        if (!in_array($status, ['open', 'reopened'], true)) {
            continue;
        }
        $origin = strtolower(trim((string) ($row['incident_origin'] ?? '')));
        if ($origin === 'watchdogs_check') {
            return $row;
        }
    }
    return null;
}

function deployment_find_latest_watchdogs_incident($statuses = [])
{
    $rows = app_read_json_file(deployment_incident_reports_path(), []);
    if (!is_array($rows)) {
        return null;
    }
    $statusFilter = [];
    if (is_array($statuses) && !empty($statuses)) {
        foreach ($statuses as $status) {
            $statusFilter[] = strtolower(trim((string) $status));
        }
    }
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $origin = strtolower(trim((string) ($row['incident_origin'] ?? '')));
        if ($origin !== 'watchdogs_check') {
            continue;
        }
        $status = strtolower(trim((string) ($row['incident_status'] ?? 'open')));
        if (!empty($statusFilter) && !in_array($status, $statusFilter, true)) {
            continue;
        }
        return $row;
    }
    return null;
}

function deployment_watchdogs_incident_summary_snapshot()
{
    $rows = app_read_json_file(deployment_incident_reports_path(), []);
    if (!is_array($rows)) {
        $rows = [];
    }
    $counts = [
        'open' => 0,
        'reopened' => 0,
        'resolved' => 0,
        'unknown' => 0,
    ];
    $latest = null;
    $latestOpen = null;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $origin = strtolower(trim((string) ($row['incident_origin'] ?? '')));
        if ($origin !== 'watchdogs_check') {
            continue;
        }
        if ($latest === null) {
            $latest = $row;
        }
        $status = strtolower(trim((string) ($row['incident_status'] ?? 'open')));
        if (!isset($counts[$status])) {
            $counts['unknown']++;
        } else {
            $counts[$status]++;
        }
        if ($latestOpen === null && in_array($status, ['open', 'reopened'], true)) {
            $latestOpen = $row;
        }
    }
    return [
        'generated_at' => gmdate('c'),
        'total' => $counts['open'] + $counts['reopened'] + $counts['resolved'] + $counts['unknown'],
        'counts' => $counts,
        'has_open' => ($counts['open'] + $counts['reopened']) > 0 ? 1 : 0,
        'latest_incident' => $latest,
        'latest_open_incident' => $latestOpen,
    ];
}

function deployment_watchdogs_policy_history_append($previous, $current, $source = 'dashboard_manual')
{
    $src = trim((string) $source);
    if ($src === '') {
        $src = 'dashboard_manual';
    }
    $entry = [
        'history_id' => 'watchdogs_policy_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'actor' => current_actor(),
        'source' => $src,
        'previous' => is_array($previous) ? $previous : [],
        'current' => is_array($current) ? $current : [],
    ];
    $rows = app_read_json_file(deployment_watchdogs_policy_history_path(), []);
    if (!is_array($rows)) {
        $rows = [];
    }
    array_unshift($rows, $entry);
    $rows = array_slice($rows, 0, 400);
    app_write_json_file(deployment_watchdogs_policy_history_path(), $rows);
    return $entry;
}

function deployment_watchdogs_check_snapshot($source = 'manual', $freshnessMinutes = null)
{
    $src = trim((string) $source);
    if ($src === '') {
        $src = 'manual';
    }
    $watchdogs = deployment_watchdogs_status_snapshot($freshnessMinutes);
    $guardCfg = get_deployment_guard();
    $status = strtolower(trim((string) ($watchdogs['status'] ?? 'warning')));
    if ($status === '') {
        $status = 'warning';
    }

    $state = app_read_json_file(deployment_watchdogs_state_path(), []);
    if (!is_array($state)) {
        $state = [];
    }
    $baseline = deployment_watchdogs_policy_baseline_snapshot();
    $baselineHasBaseline = !empty($baseline['has_baseline']) ? 1 : 0;
    $baselineHasChanges = ($baselineHasBaseline && !empty($baseline['has_changes'])) ? 1 : 0;
    $baselineChangedCount = (int) ($baseline['changed_count'] ?? 0);
    $prevBaselineHasBaseline = !empty($state['baseline_has_baseline']) ? 1 : 0;
    $prevBaselineHasChanges = !empty($state['baseline_has_changes']) ? 1 : 0;
    $baselinePresenceChanged = ($prevBaselineHasBaseline !== $baselineHasBaseline) ? 1 : 0;
    $baselineDriftStatusChanged = ($prevBaselineHasChanges !== $baselineHasChanges) ? 1 : 0;
    $baselineAlertSent = 0;
    $prevStatus = isset($state['last_status']) ? strtolower(trim((string) $state['last_status'])) : '';
    $statusChanged = ($prevStatus !== '' && $prevStatus !== $status) ? 1 : 0;
    $prevCriticalStreak = isset($state['critical_streak']) ? (int) $state['critical_streak'] : 0;
    $criticalStreak = ($status === 'critical') ? ($prevCriticalStreak + 1) : 0;
    $prevOkStreak = isset($state['ok_streak']) ? (int) $state['ok_streak'] : 0;
    $okStreak = ($status === 'ok') ? ($prevOkStreak + 1) : 0;
    $autoIncidentThreshold = max(1, min(10, (int) ($guardCfg['watchdogs_auto_incident_threshold'] ?? 2)));
    $autoResolveThreshold = max(1, min(10, (int) ($guardCfg['watchdogs_auto_resolve_ok_streak'] ?? 2)));
    $autoIncidentCreated = 0;
    $autoIncidentReopened = 0;
    $autoIncidentResolved = 0;
    $autoIncidentReportId = '';
    $autoIncidentExistingOpenId = '';
    $autoIncidentResolvedReportId = '';
    $autoIncidentAction = 'none';

    $lastAlertAt = isset($state['last_alert_at']) ? (string) $state['last_alert_at'] : '';
    $lastAlertTs = $lastAlertAt !== '' ? strtotime($lastAlertAt) : false;
    $cooldownMinutes = 30;
    $cooldownActive = ($lastAlertTs !== false) ? ((time() - $lastAlertTs) < ($cooldownMinutes * 60)) : false;

    $alertSent = 0;
    $alertType = '';
    $alertMessage = '';
    if ($statusChanged === 1) {
        if ($status === 'ok') {
            $alertType = 'success';
            $alertMessage = 'Deployment watchdogs status changed to OK.';
        } elseif ($status === 'critical') {
            $alertType = 'critical';
            $alertMessage = 'Deployment watchdogs status changed to CRITICAL.';
        } else {
            $alertType = 'warning';
            $alertMessage = 'Deployment watchdogs status changed to WARNING.';
        }
        $alertSent = 1;
    } elseif ($status === 'critical' && !$cooldownActive) {
        $alertType = 'critical';
        $alertMessage = 'Deployment watchdogs remain CRITICAL.';
        $alertSent = 1;
    }

    if ($alertSent === 1) {
        push_notification($alertType, $alertMessage, [
            'source' => $src,
            'status' => $status,
            'summary' => isset($watchdogs['summary']) && is_array($watchdogs['summary']) ? $watchdogs['summary'] : [],
        ]);
    }

    if ($baselinePresenceChanged === 1 || ($baselineHasBaseline === 1 && $baselineDriftStatusChanged === 1)) {
        $baselineAlertType = 'info';
        $baselineAlertMessage = 'Watchdogs policy baseline is configured.';
        if ($baselineHasBaseline === 0) {
            $baselineAlertType = 'warning';
            $baselineAlertMessage = 'Watchdogs policy baseline is missing.';
        } elseif ($baselineHasChanges === 1) {
            $baselineAlertType = 'warning';
            $baselineAlertMessage = 'Watchdogs policy baseline drift detected.';
        } elseif ($baselineHasChanges === 0 && $baselineDriftStatusChanged === 1) {
            $baselineAlertType = 'success';
            $baselineAlertMessage = 'Watchdogs policy baseline drift cleared.';
        }
        push_notification($baselineAlertType, $baselineAlertMessage, [
            'source' => $src,
            'has_baseline' => $baselineHasBaseline,
            'has_changes' => $baselineHasChanges,
            'changed_count' => $baselineChangedCount,
            'delta' => isset($baseline['delta']) && is_array($baseline['delta']) ? $baseline['delta'] : [],
        ]);
        audit_event('deployment', 'watchdogs.policy.baseline.drift', [
            'source' => $src,
            'has_baseline' => $baselineHasBaseline,
            'has_changes' => $baselineHasChanges,
            'changed_count' => $baselineChangedCount,
            'presence_changed' => $baselinePresenceChanged,
            'drift_status_changed' => $baselineDriftStatusChanged,
        ]);
        $baselineAlertSent = 1;
    }

    $runId = 'watchdogs_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6);
    if ($status === 'critical' && $criticalStreak >= $autoIncidentThreshold) {
        $openAutoIncident = deployment_find_open_watchdogs_incident();
        if (is_array($openAutoIncident)) {
            $autoIncidentExistingOpenId = (string) ($openAutoIncident['report_id'] ?? '');
            $autoIncidentAction = 'linked_existing_open';
        } else {
            $resolvedAutoIncident = deployment_find_latest_watchdogs_incident(['resolved']);
            if (is_array($resolvedAutoIncident)) {
                $resolvedId = (string) ($resolvedAutoIncident['report_id'] ?? '');
                $reopenNote = 'Auto-reopened after repeated critical watchdog checks.';
                $updated = update_incident_report_status($resolvedId, 'reopened', $reopenNote);
                if (is_array($updated)) {
                    audit_event('deployment', 'incident.report.reopen.watchdogs', [
                        'report_id' => (string) ($updated['report_id'] ?? $resolvedId),
                        'run_id' => $runId,
                        'critical_streak' => $criticalStreak,
                        'source' => $src,
                    ]);
                    push_notification('critical', 'Watchdog incident reopened due to critical status.', [
                        'report_id' => (string) ($updated['report_id'] ?? $resolvedId),
                        'run_id' => $runId,
                        'critical_streak' => $criticalStreak,
                        'source' => $src,
                    ]);
                    $autoIncidentReopened = 1;
                    $autoIncidentReportId = (string) ($updated['report_id'] ?? $resolvedId);
                    $autoIncidentAction = 'reopened';
                }
            } else {
                $note = 'Auto incident created after repeated critical watchdog checks.';
                $report = deployment_incident_report_snapshot($note);
                $report['incident_origin'] = 'watchdogs_check';
                $report['incident_origin_status'] = $status;
                $report['incident_origin_run_id'] = $runId;
                $report['incident_origin_source'] = $src;
                $report['incident_origin_critical_streak'] = $criticalStreak;
                $report['incident_origin_watchdogs_summary'] = isset($watchdogs['summary']) && is_array($watchdogs['summary']) ? $watchdogs['summary'] : [];
                $report['incident_note'] = 'Auto-created after ' . (int) $criticalStreak . ' consecutive critical watchdog checks.';
                if (!isset($report['summary']) || !is_array($report['summary'])) {
                    $report['summary'] = [];
                }
                $report['summary']['watchdogs_status'] = $status;
                $report['summary']['watchdogs_critical_failed'] = (int) ($watchdogs['summary']['critical_failed'] ?? 0);
                $report['summary']['watchdogs_warning_failed'] = (int) ($watchdogs['summary']['warning_failed'] ?? 0);
                save_incident_report($report);
                audit_event('deployment', 'incident.report.auto.watchdogs', [
                    'report_id' => (string) ($report['report_id'] ?? ''),
                    'run_id' => $runId,
                    'critical_streak' => $criticalStreak,
                    'source' => $src,
                ]);
                push_notification('critical', 'Auto incident created from watchdogs critical status.', [
                    'report_id' => (string) ($report['report_id'] ?? ''),
                    'run_id' => $runId,
                    'critical_streak' => $criticalStreak,
                    'source' => $src,
                ]);
                $autoIncidentCreated = 1;
                $autoIncidentReportId = (string) ($report['report_id'] ?? '');
                $autoIncidentAction = 'created';
            }
        }
    }
    if ($status === 'ok' && $okStreak >= $autoResolveThreshold) {
        $openAutoIncident = deployment_find_open_watchdogs_incident();
        if (is_array($openAutoIncident)) {
            $reportId = (string) ($openAutoIncident['report_id'] ?? '');
            $resolveNote = 'Auto-resolved after consecutive healthy watchdog checks.';
            $updated = update_incident_report_status($reportId, 'resolved', $resolveNote);
            if (is_array($updated)) {
                audit_event('deployment', 'incident.report.auto_resolve.watchdogs', [
                    'report_id' => (string) ($updated['report_id'] ?? $reportId),
                    'run_id' => $runId,
                    'ok_streak' => $okStreak,
                    'source' => $src,
                ]);
                push_notification('success', 'Watchdog incident auto-resolved after healthy checks.', [
                    'report_id' => (string) ($updated['report_id'] ?? $reportId),
                    'run_id' => $runId,
                    'ok_streak' => $okStreak,
                    'source' => $src,
                ]);
                $autoIncidentResolved = 1;
                $autoIncidentResolvedReportId = (string) ($updated['report_id'] ?? $reportId);
                $autoIncidentAction = 'auto_resolved';
            }
        }
    }

    $run = [
        'run_id' => $runId,
        'created_at' => gmdate('c'),
        'source' => $src,
        'status' => $status,
        'status_changed' => $statusChanged,
        'alert_sent' => $alertSent,
        'critical_streak' => $criticalStreak,
        'ok_streak' => $okStreak,
        'auto_incident_threshold' => $autoIncidentThreshold,
        'auto_resolve_threshold' => $autoResolveThreshold,
        'auto_incident_action' => $autoIncidentAction,
        'auto_incident_created' => $autoIncidentCreated,
        'auto_incident_reopened' => $autoIncidentReopened,
        'auto_incident_resolved' => $autoIncidentResolved,
        'auto_incident_report_id' => $autoIncidentReportId,
        'auto_incident_existing_open_id' => $autoIncidentExistingOpenId,
        'auto_incident_resolved_report_id' => $autoIncidentResolvedReportId,
        'baseline_has_baseline' => $baselineHasBaseline,
        'baseline_has_changes' => $baselineHasChanges,
        'baseline_changed_count' => $baselineChangedCount,
        'baseline_alert_sent' => $baselineAlertSent,
        'baseline_delta' => isset($baseline['delta']) && is_array($baseline['delta']) ? $baseline['delta'] : [],
        'freshness_window_minutes' => (int) ($watchdogs['freshness_window_minutes'] ?? 30),
        'critical_failed' => (int) ($watchdogs['summary']['critical_failed'] ?? 0),
        'warning_failed' => (int) ($watchdogs['summary']['warning_failed'] ?? 0),
    ];
    $runs = app_read_json_file(deployment_watchdogs_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 400);
    app_write_json_file(deployment_watchdogs_runs_path(), $runs);

    $now = gmdate('c');
    $nextState = [
        'last_checked_at' => $now,
        'last_status' => $status,
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => $src,
        'last_alert_at' => ($alertSent === 1) ? $now : (string) ($state['last_alert_at'] ?? ''),
        'last_alert_sent' => $alertSent,
        'critical_streak' => $criticalStreak,
        'ok_streak' => $okStreak,
        'auto_incident_threshold' => $autoIncidentThreshold,
        'auto_resolve_threshold' => $autoResolveThreshold,
        'last_auto_incident_report_id' => $autoIncidentReportId !== '' ? $autoIncidentReportId : (string) ($state['last_auto_incident_report_id'] ?? ''),
        'last_auto_incident_action' => $autoIncidentAction,
        'last_auto_incident_at' => ($autoIncidentCreated === 1 || $autoIncidentReopened === 1) ? $now : (string) ($state['last_auto_incident_at'] ?? ''),
        'last_auto_incident_reopened_at' => $autoIncidentReopened === 1 ? $now : (string) ($state['last_auto_incident_reopened_at'] ?? ''),
        'last_auto_incident_resolved_report_id' => $autoIncidentResolvedReportId !== '' ? $autoIncidentResolvedReportId : (string) ($state['last_auto_incident_resolved_report_id'] ?? ''),
        'last_auto_incident_resolved_at' => $autoIncidentResolved === 1 ? $now : (string) ($state['last_auto_incident_resolved_at'] ?? ''),
        'baseline_has_baseline' => $baselineHasBaseline,
        'baseline_has_changes' => $baselineHasChanges,
        'baseline_changed_count' => $baselineChangedCount,
        'baseline_last_alert_sent' => $baselineAlertSent,
        'baseline_last_alert_at' => ($baselineAlertSent === 1) ? $now : (string) ($state['baseline_last_alert_at'] ?? ''),
        'baseline_last_change_at' => ($baselinePresenceChanged === 1 || $baselineDriftStatusChanged === 1) ? $now : (string) ($state['baseline_last_change_at'] ?? ''),
        'baseline_source' => isset($baseline['baseline']['source']) ? (string) $baseline['baseline']['source'] : '',
    ];
    app_write_json_file(deployment_watchdogs_state_path(), $nextState);

    return [
        'run' => $run,
        'watchdogs' => $watchdogs,
        'baseline' => $baseline,
        'state' => $nextState,
    ];
}

function deployment_artifact_manifest_snapshot()
{
    $root = dirname(__DIR__, 2);
    $files = [
        'lead-console.php',
        'app-site/index.php',
        'app-site/api/index.php',
        'app-site/api/lib.php',
        'app-site/assets/app.js',
        'app-site/config.production.example.php',
        'docs/DEPLOY_HOSTINGER_RUNBOOK.md',
    ];
    $manifest = [];
    foreach ($files as $rel) {
        $full = $root . '/' . str_replace('\\', '/', $rel);
        if (!is_file($full)) {
            $manifest[] = [
                'path' => $rel,
                'exists' => 0,
                'size_bytes' => 0,
                'sha256' => '',
            ];
            continue;
        }
        $manifest[] = [
            'path' => $rel,
            'exists' => 1,
            'size_bytes' => (int) filesize($full),
            'sha256' => (string) hash_file('sha256', $full),
        ];
    }

    return [
        'manifest_id' => 'artifact_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'generated_at' => gmdate('c'),
        'phase' => '1.15-deployment-artifact-manifest',
        'items' => $manifest,
        'item_count' => count($manifest),
    ];
}

function deployment_artifact_verify_snapshot($baseline)
{
    $current = deployment_artifact_manifest_snapshot();
    $currentItems = isset($current['items']) && is_array($current['items']) ? $current['items'] : [];
    $baselineItems = [];
    if (is_array($baseline) && isset($baseline['items']) && is_array($baseline['items'])) {
        $baselineItems = $baseline['items'];
    } elseif (is_array($baseline)) {
        $baselineItems = $baseline;
    }

    $currentMap = [];
    foreach ($currentItems as $row) {
        $path = (string) ($row['path'] ?? '');
        if ($path !== '') {
            $currentMap[$path] = $row;
        }
    }
    $baselineMap = [];
    foreach ($baselineItems as $row) {
        if (!is_array($row)) {
            continue;
        }
        $path = (string) ($row['path'] ?? '');
        if ($path !== '') {
            $baselineMap[$path] = $row;
        }
    }

    $mismatches = [];
    $missingOnServer = [];
    $newOnServer = [];

    foreach ($baselineMap as $path => $baseRow) {
        if (!isset($currentMap[$path])) {
            $missingOnServer[] = $path;
            continue;
        }
        $curRow = $currentMap[$path];
        $baseExists = !empty($baseRow['exists']);
        $curExists = !empty($curRow['exists']);
        $baseHash = (string) ($baseRow['sha256'] ?? '');
        $curHash = (string) ($curRow['sha256'] ?? '');
        $baseSize = (int) ($baseRow['size_bytes'] ?? 0);
        $curSize = (int) ($curRow['size_bytes'] ?? 0);

        if ($baseExists !== $curExists || $baseHash !== $curHash || $baseSize !== $curSize) {
            $mismatches[] = [
                'path' => $path,
                'baseline' => [
                    'exists' => $baseExists ? 1 : 0,
                    'size_bytes' => $baseSize,
                    'sha256' => $baseHash,
                ],
                'current' => [
                    'exists' => $curExists ? 1 : 0,
                    'size_bytes' => $curSize,
                    'sha256' => $curHash,
                ],
            ];
        }
    }

    foreach ($currentMap as $path => $curRow) {
        if (!isset($baselineMap[$path])) {
            $newOnServer[] = $path;
        }
    }

    $ok = empty($mismatches) && empty($missingOnServer);
    return [
        'verify_id' => 'verify_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'generated_at' => gmdate('c'),
        'ok' => $ok,
        'status' => $ok ? 'match' : 'mismatch',
        'summary' => [
            'baseline_count' => count($baselineMap),
            'current_count' => count($currentMap),
            'mismatch_count' => count($mismatches),
            'missing_on_server_count' => count($missingOnServer),
            'new_on_server_count' => count($newOnServer),
        ],
        'missing_on_server' => $missingOnServer,
        'new_on_server' => $newOnServer,
        'mismatches' => $mismatches,
        'current_manifest' => $current,
    ];
}

function deployment_incident_report_snapshot($note = '')
{
    $guardEval = deployment_guard_evaluate();
    $goLive = deployment_handoff_bundle_snapshot();
    $bypassLog = app_read_json_file(deployment_bypass_log_path(), []);
    $auditLog = app_read_json_file(audit_log_path(), []);
    $notifications = app_read_json_file(notifications_path(), []);

    $criticalNotifications = [];
    foreach ($notifications as $row) {
        if (!is_array($row)) {
            continue;
        }
        $type = (string) ($row['type'] ?? '');
        if (in_array($type, ['critical', 'warning'], true)) {
            $criticalNotifications[] = $row;
        }
        if (count($criticalNotifications) >= 100) {
            break;
        }
    }

    $recentDeploymentAudit = [];
    foreach ($auditLog as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((string) ($row['category'] ?? '') === 'deployment') {
            $recentDeploymentAudit[] = $row;
        }
        if (count($recentDeploymentAudit) >= 200) {
            break;
        }
    }

    return [
        'report_id' => 'incident_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'generated_at' => gmdate('c'),
        'phase' => '1.21-incident-report-export',
        'note' => trim((string) $note),
        'incident_status' => 'open',
        'incident_updated_at' => gmdate('c'),
        'incident_updated_by' => current_actor(),
        'incident_note' => '',
        'summary' => [
            'guard_allowed' => !empty($guardEval['allowed']) ? 1 : 0,
            'go_live_status' => (string) ($goLive['status'] ?? 'review_required'),
            'bypass_log_events' => count($bypassLog),
            'deployment_audit_events' => count($recentDeploymentAudit),
            'critical_notifications' => count($criticalNotifications),
        ],
        'deployment_guard' => [
            'allowed' => !empty($guardEval['allowed']),
            'reasons' => $guardEval['reasons'],
            'state' => $guardEval['guard'],
            'bypass' => $guardEval['bypass'] ?? [],
            'bypass_active' => !empty($guardEval['bypass_active']) ? 1 : 0,
        ],
        'go_live' => [
            'status' => (string) ($goLive['status'] ?? 'review_required'),
            'summary' => isset($goLive['summary']) && is_array($goLive['summary']) ? $goLive['summary'] : [],
            'failed_environment_checklist' => isset($goLive['failed_environment_checklist']) && is_array($goLive['failed_environment_checklist']) ? $goLive['failed_environment_checklist'] : [],
        ],
        'bypass_log_tail' => array_slice($bypassLog, 0, 200),
        'deployment_audit_tail' => $recentDeploymentAudit,
        'critical_notification_tail' => $criticalNotifications,
    ];
}

function save_incident_report($report)
{
    $rows = app_read_json_file(deployment_incident_reports_path(), []);
    array_unshift($rows, $report);
    $rows = array_slice($rows, 0, 200);
    app_write_json_file(deployment_incident_reports_path(), $rows);
    return $rows;
}

function update_incident_report_status($reportId, $status, $incidentNote = '')
{
    $rows = app_read_json_file(deployment_incident_reports_path(), []);
    $updated = null;
    foreach ($rows as &$row) {
        if (!is_array($row)) {
            continue;
        }
        if ((string) ($row['report_id'] ?? '') !== (string) $reportId) {
            continue;
        }
        $row['incident_status'] = (string) $status;
        $row['incident_updated_at'] = gmdate('c');
        $row['incident_updated_by'] = current_actor();
        $note = trim((string) $incidentNote);
        if ($note !== '') {
            $row['incident_note'] = $note;
        }
        $updated = $row;
        break;
    }
    unset($row);
    app_write_json_file(deployment_incident_reports_path(), $rows);
    return $updated;
}

function deployment_incident_summary_snapshot()
{
    $rows = app_read_json_file(deployment_incident_reports_path(), []);
    $counts = [
        'open' => 0,
        'resolved' => 0,
        'reopened' => 0,
        'unknown' => 0,
    ];
    $resolutionSamples = [];
    $latestOpen = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $status = strtolower(trim((string) ($row['incident_status'] ?? 'open')));
        if (!isset($counts[$status])) {
            $counts['unknown']++;
        } else {
            $counts[$status]++;
        }

        if ($status === 'open' || $status === 'reopened') {
            $latestOpen[] = [
                'report_id' => (string) ($row['report_id'] ?? ''),
                'generated_at' => (string) ($row['generated_at'] ?? ''),
                'status' => $status,
                'note' => (string) ($row['note'] ?? ''),
            ];
        }

        if ($status === 'resolved') {
            $createdTs = strtotime((string) ($row['generated_at'] ?? ''));
            $resolvedTs = strtotime((string) ($row['incident_updated_at'] ?? ''));
            if ($createdTs !== false && $resolvedTs !== false && $resolvedTs >= $createdTs) {
                $resolutionSamples[] = (int) round(($resolvedTs - $createdTs) / 60);
            }
        }
    }

    $avgResolution = 0;
    $maxResolution = 0;
    if (!empty($resolutionSamples)) {
        $avgResolution = (int) round(array_sum($resolutionSamples) / count($resolutionSamples));
        $maxResolution = (int) max($resolutionSamples);
    }

    return [
        'generated_at' => gmdate('c'),
        'counts' => $counts,
        'total_reports' => count($rows),
        'open_total' => (int) ($counts['open'] + $counts['reopened']),
        'resolution_metrics' => [
            'sample_count' => count($resolutionSamples),
            'avg_resolution_minutes' => $avgResolution,
            'max_resolution_minutes' => $maxResolution,
        ],
        'latest_open_items' => array_slice($latestOpen, 0, 20),
    ];
}

function deployment_incident_sla_snapshot($thresholdMinutes = 120)
{
    $threshold = max(5, min(10080, (int) $thresholdMinutes));
    $rows = app_read_json_file(deployment_incident_reports_path(), []);
    $now = time();
    $breaches = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $status = strtolower(trim((string) ($row['incident_status'] ?? 'open')));
        if (!in_array($status, ['open', 'reopened'], true)) {
            continue;
        }
        $createdTs = strtotime((string) ($row['generated_at'] ?? ''));
        if ($createdTs === false) {
            continue;
        }
        $ageMinutes = (int) floor(($now - $createdTs) / 60);
        if ($ageMinutes < $threshold) {
            continue;
        }
        $breaches[] = [
            'report_id' => (string) ($row['report_id'] ?? ''),
            'status' => $status,
            'age_minutes' => $ageMinutes,
            'generated_at' => (string) ($row['generated_at'] ?? ''),
            'incident_note' => (string) ($row['incident_note'] ?? ''),
            'note' => (string) ($row['note'] ?? ''),
        ];
    }

    return [
        'generated_at' => gmdate('c'),
        'threshold_minutes' => $threshold,
        'breach_count' => count($breaches),
        'breaches' => array_slice($breaches, 0, 200),
    ];
}

function deployment_incident_sla_check_snapshot($thresholdMinutes = 120, $cooldownMinutes = 30)
{
    $threshold = max(5, min(10080, (int) $thresholdMinutes));
    $cooldown = max(1, min(1440, (int) $cooldownMinutes));
    $sla = deployment_incident_sla_snapshot($threshold);
    $breachCount = (int) ($sla['breach_count'] ?? 0);

    $state = app_read_json_file(deployment_incident_sla_state_path(), []);
    if (!is_array($state)) {
        $state = [];
    }
    $lastAlertAt = trim((string) ($state['last_alert_at'] ?? ''));
    $lastAlertTs = $lastAlertAt !== '' ? strtotime($lastAlertAt) : false;
    $now = time();
    $cooldownActive = ($lastAlertTs !== false) ? (($now - $lastAlertTs) < ($cooldown * 60)) : false;

    $alertSent = false;
    $alertMessage = '';
    if ($breachCount > 0 && !$cooldownActive) {
        $alertSent = true;
        $alertMessage = 'Incident SLA breaches detected: ' . $breachCount . ' item(s).';
        push_notification('critical', $alertMessage, [
            'breach_count' => $breachCount,
            'threshold_minutes' => $threshold,
        ]);
        $state['last_alert_at'] = gmdate('c');
    }

    $state['last_check_at'] = gmdate('c');
    $state['last_breach_count'] = $breachCount;
    $state['last_threshold_minutes'] = $threshold;
    $state['last_cooldown_minutes'] = $cooldown;
    app_write_json_file(deployment_incident_sla_state_path(), $state);

    $run = [
        'run_id' => 'sla_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'threshold_minutes' => $threshold,
        'cooldown_minutes' => $cooldown,
        'breach_count' => $breachCount,
        'alert_sent' => $alertSent ? 1 : 0,
        'cooldown_active' => $cooldownActive ? 1 : 0,
    ];
    $runs = app_read_json_file(deployment_incident_sla_runs_path(), []);
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 300);
    app_write_json_file(deployment_incident_sla_runs_path(), $runs);

    return [
        'run' => $run,
        'sla' => $sla,
        'state' => $state,
        'alert_message' => $alertMessage,
    ];
}

function deployment_smoke_record_snapshot($smokeType, $ok, $source = 'dashboard_manual', $note = '', $details = [])
{
    $type = strtolower(trim((string) $smokeType));
    if (!in_array($type, ['public', 'auth'], true)) {
        $type = 'public';
    }
    $isOk = !empty($ok) ? 1 : 0;
    $payloadDetails = is_array($details) ? $details : [];
    $run = [
        'run_id' => 'smoke_' . $type . '_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'smoke_type' => $type,
        'ok' => $isOk,
        'status' => $isOk === 1 ? 'pass' : 'fail',
        'source' => trim((string) $source) !== '' ? trim((string) $source) : 'dashboard_manual',
        'note' => trim((string) $note),
        'details' => $payloadDetails,
        'actor' => current_actor(),
        'created_at' => gmdate('c'),
    ];
    $rows = app_read_json_file(deployment_smoke_runs_path(), []);
    array_unshift($rows, $run);
    $rows = array_slice($rows, 0, 500);
    app_write_json_file(deployment_smoke_runs_path(), $rows);
    return $run;
}

function deployment_smoke_history_snapshot()
{
    $rows = app_read_json_file(deployment_smoke_runs_path(), []);
    $summary = [
        'total_runs' => count($rows),
        'pass_count' => 0,
        'fail_count' => 0,
        'by_type' => [
            'public' => ['pass' => 0, 'fail' => 0],
            'auth' => ['pass' => 0, 'fail' => 0],
        ],
    ];
    $latestByType = ['public' => null, 'auth' => null];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $type = strtolower(trim((string) ($row['smoke_type'] ?? 'public')));
        if (!isset($summary['by_type'][$type])) {
            continue;
        }
        $isOk = !empty($row['ok']);
        if ($isOk) {
            $summary['pass_count']++;
            $summary['by_type'][$type]['pass']++;
        } else {
            $summary['fail_count']++;
            $summary['by_type'][$type]['fail']++;
        }
        if ($latestByType[$type] === null) {
            $latestByType[$type] = $row;
        }
    }

    return [
        'generated_at' => gmdate('c'),
        'summary' => $summary,
        'latest_by_type' => $latestByType,
        'items' => $rows,
    ];
}

function deployment_smoke_suite_snapshot($note = '')
{
    $health = healthcheck_snapshot();
    $install = install_check_snapshot();
    $preflight = deployment_preflight_snapshot();
    $guard = deployment_guard_evaluate();
    $pipelineRuns = app_read_json_file(deployment_pipeline_runs_path(), []);
    $incidentSummary = deployment_incident_summary_snapshot();
    $slaState = app_read_json_file(deployment_incident_sla_state_path(), []);
    $threshold = is_array($slaState) && isset($slaState['last_threshold_minutes'])
        ? max(5, min(10080, (int) $slaState['last_threshold_minutes']))
        : 120;
    $incidentSla = deployment_incident_sla_snapshot($threshold);

    $publicChecks = [
        [
            'check' => 'status_endpoint',
            'ok' => true,
            'message' => 'API status endpoint is available.',
        ],
        [
            'check' => 'healthcheck_not_critical',
            'ok' => (string) ($health['status'] ?? 'warning') !== 'critical',
            'message' => 'Healthcheck status: ' . (string) ($health['status'] ?? 'warning'),
        ],
        [
            'check' => 'install_check_not_critical',
            'ok' => (string) ($install['status'] ?? 'warning') !== 'critical',
            'message' => 'Install check status: ' . (string) ($install['status'] ?? 'warning'),
        ],
        [
            'check' => 'deployment_preflight_not_critical',
            'ok' => (string) ($preflight['status'] ?? 'warning') !== 'critical',
            'message' => 'Preflight status: ' . (string) ($preflight['status'] ?? 'warning'),
        ],
    ];

    $authChecks = [
        [
            'check' => 'deployment_guard_status_access',
            'ok' => true,
            'message' => 'Deployment guard status loaded.',
        ],
        [
            'check' => 'deployment_pipeline_runs_access',
            'ok' => is_array($pipelineRuns),
            'message' => 'Pipeline runs loaded: ' . count($pipelineRuns),
        ],
        [
            'check' => 'deployment_incident_summary_access',
            'ok' => is_array($incidentSummary),
            'message' => 'Incident summary loaded.',
        ],
        [
            'check' => 'deployment_incident_sla_runs_access',
            'ok' => is_array(app_read_json_file(deployment_incident_sla_runs_path(), [])),
            'message' => 'Incident SLA runs loaded.',
        ],
        [
            'check' => 'deployment_guard_currently_allows',
            'ok' => !empty($guard['allowed']),
            'message' => !empty($guard['allowed']) ? 'Guard currently allows writes.' : 'Guard currently blocks writes.',
        ],
    ];

    $publicOk = true;
    foreach ($publicChecks as $row) {
        if (empty($row['ok'])) {
            $publicOk = false;
            break;
        }
    }
    $authOk = true;
    foreach ($authChecks as $row) {
        if (empty($row['ok'])) {
            $authOk = false;
            break;
        }
    }

    $runPublic = deployment_smoke_record_snapshot('public', $publicOk ? 1 : 0, 'dashboard_suite', $note, [
        'checks' => $publicChecks,
        'install_status' => (string) ($install['status'] ?? 'warning'),
        'preflight_status' => (string) ($preflight['status'] ?? 'warning'),
    ]);
    $runAuth = deployment_smoke_record_snapshot('auth', $authOk ? 1 : 0, 'dashboard_suite', $note, [
        'checks' => $authChecks,
        'guard_allowed' => !empty($guard['allowed']) ? 1 : 0,
        'open_incidents' => (int) ($incidentSummary['open_total'] ?? 0),
        'sla_breach_count' => (int) ($incidentSla['breach_count'] ?? 0),
    ]);

    return [
        'suite_id' => 'smoke_suite_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'note' => trim((string) $note),
        'public' => [
            'ok' => $publicOk ? 1 : 0,
            'checks' => $publicChecks,
            'run' => $runPublic,
        ],
        'auth' => [
            'ok' => $authOk ? 1 : 0,
            'checks' => $authChecks,
            'run' => $runAuth,
        ],
        'overall_ok' => ($publicOk && $authOk) ? 1 : 0,
    ];
}

function deployment_cutover_readiness_snapshot()
{
    $install = install_check_snapshot();
    $preflight = deployment_preflight_snapshot();
    $goLive = deployment_handoff_bundle_snapshot();
    $guard = deployment_guard_evaluate();
    $incidents = deployment_incident_summary_snapshot();
    $threshold = 120;
    $slaState = app_read_json_file(deployment_incident_sla_state_path(), []);
    if (is_array($slaState) && isset($slaState['last_threshold_minutes'])) {
        $threshold = max(5, min(10080, (int) $slaState['last_threshold_minutes']));
    }
    $sla = deployment_incident_sla_snapshot($threshold);
    $pipelineRuns = app_read_json_file(deployment_pipeline_runs_path(), []);
    $latestPipelineRun = (!empty($pipelineRuns) && is_array($pipelineRuns[0])) ? $pipelineRuns[0] : null;
    $smokeRuns = app_read_json_file(deployment_smoke_runs_path(), []);
    $latestSmokeByType = ['public' => null, 'auth' => null];
    foreach ($smokeRuns as $row) {
        if (!is_array($row)) {
            continue;
        }
        $type = strtolower(trim((string) ($row['smoke_type'] ?? '')));
        if (!isset($latestSmokeByType[$type])) {
            continue;
        }
        if ($latestSmokeByType[$type] !== null) {
            continue;
        }
        $latestSmokeByType[$type] = $row;
    }

    $checklist = [];
    $checklist[] = [
        'item' => 'install_check_ok',
        'ok' => (string) ($install['status'] ?? 'warning') !== 'critical',
        'severity' => 'critical',
        'message' => 'Install check status: ' . (string) ($install['status'] ?? 'warning'),
    ];
    $checklist[] = [
        'item' => 'preflight_not_critical',
        'ok' => (string) ($preflight['status'] ?? 'warning') !== 'critical',
        'severity' => 'critical',
        'message' => 'Preflight status: ' . (string) ($preflight['status'] ?? 'warning'),
    ];
    $checklist[] = [
        'item' => 'go_live_ready',
        'ok' => (string) ($goLive['status'] ?? 'review_required') === 'ready',
        'severity' => 'critical',
        'message' => 'Go-live status: ' . (string) ($goLive['status'] ?? 'review_required'),
    ];
    $checklist[] = [
        'item' => 'deployment_guard_allows_writes',
        'ok' => !empty($guard['allowed']),
        'severity' => 'critical',
        'message' => !empty($guard['allowed']) ? 'Deployment guard allows writes.' : 'Deployment guard currently blocks writes.',
    ];
    $checklist[] = [
        'item' => 'public_smoke_latest_passed',
        'ok' => is_array($latestSmokeByType['public']) && !empty($latestSmokeByType['public']['ok']),
        'severity' => 'warning',
        'message' => is_array($latestSmokeByType['public'])
            ? ('Latest public smoke status: ' . (string) ($latestSmokeByType['public']['status'] ?? 'unknown'))
            : 'No public smoke run has been recorded.',
    ];
    $checklist[] = [
        'item' => 'auth_smoke_latest_passed',
        'ok' => is_array($latestSmokeByType['auth']) && !empty($latestSmokeByType['auth']['ok']),
        'severity' => 'warning',
        'message' => is_array($latestSmokeByType['auth'])
            ? ('Latest auth smoke status: ' . (string) ($latestSmokeByType['auth']['status'] ?? 'unknown'))
            : 'No auth smoke run has been recorded.',
    ];
    $checklist[] = [
        'item' => 'open_incidents_under_control',
        'ok' => ((int) ($incidents['open_total'] ?? 0)) === 0,
        'severity' => 'warning',
        'message' => 'Open incident count: ' . (int) ($incidents['open_total'] ?? 0),
    ];
    $checklist[] = [
        'item' => 'incident_sla_breaches_under_threshold',
        'ok' => ((int) ($sla['breach_count'] ?? 0)) === 0,
        'severity' => 'warning',
        'message' => 'SLA breaches at ' . $threshold . ' min threshold: ' . (int) ($sla['breach_count'] ?? 0),
    ];

    $criticalFailed = 0;
    $warningFailed = 0;
    foreach ($checklist as $row) {
        if (!empty($row['ok'])) {
            continue;
        }
        if ((string) ($row['severity'] ?? 'warning') === 'critical') {
            $criticalFailed++;
        } else {
            $warningFailed++;
        }
    }
    $status = 'ready';
    if ($criticalFailed > 0) {
        $status = 'blocked';
    } elseif ($warningFailed > 0) {
        $status = 'review_required';
    }

    return [
        'generated_at' => gmdate('c'),
        'status' => $status,
        'summary' => [
            'critical_failed' => $criticalFailed,
            'warning_failed' => $warningFailed,
            'check_count' => count($checklist),
            'install_status' => (string) ($install['status'] ?? 'warning'),
            'preflight_status' => (string) ($preflight['status'] ?? 'warning'),
            'go_live_status' => (string) ($goLive['status'] ?? 'review_required'),
            'guard_allowed' => !empty($guard['allowed']) ? 1 : 0,
            'open_incidents' => (int) ($incidents['open_total'] ?? 0),
            'sla_breach_count' => (int) ($sla['breach_count'] ?? 0),
            'last_pipeline_run_id' => is_array($latestPipelineRun) ? (string) ($latestPipelineRun['run_id'] ?? '') : '',
            'last_pipeline_status' => is_array($latestPipelineRun) ? (string) ($latestPipelineRun['status'] ?? '') : '',
        ],
        'checklist' => $checklist,
        'latest_pipeline_run' => $latestPipelineRun,
        'latest_smoke_runs' => $latestSmokeByType,
        'incident_summary' => $incidents,
        'incident_sla' => $sla,
        'guard' => [
            'allowed' => !empty($guard['allowed']),
            'reasons' => isset($guard['reasons']) && is_array($guard['reasons']) ? $guard['reasons'] : [],
        ],
    ];
}

function deployment_cutover_evidence_bundle_snapshot($note = '')
{
    $readiness = deployment_cutover_readiness_snapshot();
    $gate = deployment_release_gate_snapshot(null);
    $gateState = app_read_json_file(deployment_release_gate_state_path(), []);
    $gateRuns = app_read_json_file(deployment_release_gate_runs_path(), []);
    $gateRunsSummary = deployment_release_gate_runs_summary($gateRuns);
    $signoffIntegrityState = app_read_json_file(deployment_cutover_signoff_integrity_state_path(), []);
    $signoffIntegrityRuns = app_read_json_file(deployment_cutover_signoff_integrity_runs_path(), []);
    $watchdogsState = app_read_json_file(deployment_watchdogs_state_path(), []);
    $watchdogsRuns = app_read_json_file(deployment_watchdogs_runs_path(), []);
    $watchdogsPolicyBaseline = deployment_watchdogs_policy_baseline_snapshot();
    $watchdogsPolicyBaselineState = app_read_json_file(deployment_watchdogs_policy_baseline_state_path(), []);
    $watchdogsPolicyBaselineRuns = app_read_json_file(deployment_watchdogs_policy_baseline_runs_path(), []);
    $smokeHistory = deployment_smoke_history_snapshot();
    $pipelineRuns = app_read_json_file(deployment_pipeline_runs_path(), []);
    $releaseLog = app_read_json_file(deployment_release_log_path(), []);
    $signoffRows = deployment_cutover_signoff_list_snapshot();
    $latestSignoff = (!empty($signoffRows) && is_array($signoffRows[0])) ? $signoffRows[0] : null;
    $activeSignoff = deployment_cutover_signoff_active_snapshot();
    $incidentSummary = deployment_incident_summary_snapshot();
    $slaState = app_read_json_file(deployment_incident_sla_state_path(), []);
    $threshold = is_array($slaState) && isset($slaState['last_threshold_minutes'])
        ? max(5, min(10080, (int) $slaState['last_threshold_minutes']))
        : 120;
    $incidentSla = deployment_incident_sla_snapshot($threshold);
    $guard = deployment_guard_evaluate();
    $automation = get_automation_settings();
    $nowTs = time();
    $lastTs = $automation['last_run_at'] !== '' ? strtotime((string) $automation['last_run_at']) : 0;
    $intervalSecs = max(5, (int) ($automation['interval_minutes'] ?? 30)) * 60;
    $elapsed = $lastTs > 0 ? ($nowTs - $lastTs) : null;
    $dueNow = ($lastTs === 0) ? true : ($elapsed >= $intervalSecs);
    $nextDueIn = ($lastTs === 0) ? 0 : max(0, $intervalSecs - max(0, (int) $elapsed));

    return [
        'bundle_id' => 'cutover_evidence_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'generated_at' => gmdate('c'),
        'phase' => '2.87-social-drafts-export',
        'note' => trim((string) $note),
        'summary' => [
            'readiness_status' => (string) ($readiness['status'] ?? 'review_required'),
            'release_gate_allowed' => !empty($gate['allowed']) ? 1 : 0,
            'smoke_total_runs' => (int) (($smokeHistory['summary']['total_runs'] ?? 0)),
            'pipeline_runs' => count($pipelineRuns),
            'release_candidates' => count($releaseLog),
            'cutover_signoffs' => count($signoffRows),
            'active_signoff_id' => is_array($activeSignoff['active'] ?? null) ? (string) (($activeSignoff['active']['signoff_id'] ?? '')) : '',
            'open_incidents' => (int) ($incidentSummary['open_total'] ?? 0),
            'sla_breach_count' => (int) ($incidentSla['breach_count'] ?? 0),
        ],
        'readiness' => $readiness,
        'release_gate' => $gate,
        'release_gate_watch' => [
            'state' => is_array($gateState) ? $gateState : [],
            'runs' => array_slice(is_array($gateRuns) ? $gateRuns : [], 0, 200),
            'summary' => $gateRunsSummary,
        ],
        'signoff_integrity_watch' => [
            'state' => is_array($signoffIntegrityState) ? $signoffIntegrityState : [],
            'runs' => array_slice(is_array($signoffIntegrityRuns) ? $signoffIntegrityRuns : [], 0, 200),
        ],
        'watchdogs_check' => [
            'state' => is_array($watchdogsState) ? $watchdogsState : [],
            'runs' => array_slice(is_array($watchdogsRuns) ? $watchdogsRuns : [], 0, 200),
        ],
        'watchdogs_policy_baseline' => $watchdogsPolicyBaseline,
        'watchdogs_policy_baseline_check' => [
            'state' => is_array($watchdogsPolicyBaselineState) ? $watchdogsPolicyBaselineState : [],
            'runs' => array_slice(is_array($watchdogsPolicyBaselineRuns) ? $watchdogsPolicyBaselineRuns : [], 0, 200),
        ],
        'smoke_history' => $smokeHistory,
        'pipeline_runs' => array_slice(is_array($pipelineRuns) ? $pipelineRuns : [], 0, 100),
        'release_log' => array_slice(is_array($releaseLog) ? $releaseLog : [], 0, 100),
        'cutover_signoff' => [
            'latest' => $latestSignoff,
            'active' => $activeSignoff,
            'items' => array_slice(is_array($signoffRows) ? $signoffRows : [], 0, 100),
        ],
        'incidents' => [
            'summary' => $incidentSummary,
            'sla' => $incidentSla,
            'sla_threshold_minutes' => $threshold,
        ],
        'deployment_guard' => [
            'allowed' => !empty($guard['allowed']),
            'reasons' => isset($guard['reasons']) && is_array($guard['reasons']) ? $guard['reasons'] : [],
            'guard' => isset($guard['guard']) && is_array($guard['guard']) ? $guard['guard'] : [],
        ],
        'automation_scheduler' => [
            'enabled' => !empty($automation['enabled']) ? 1 : 0,
            'last_run_at' => (string) ($automation['last_run_at'] ?? ''),
            'interval_minutes' => (int) ($automation['interval_minutes'] ?? 30),
            'due_now' => $dueNow,
            'next_due_in_seconds' => $nextDueIn,
            'modules' => isset($automation['modules']) && is_array($automation['modules']) ? $automation['modules'] : [],
        ],
    ];
}

function deployment_cutover_signoff_create_snapshot($note = '')
{
    $evidence = deployment_cutover_evidence_bundle_snapshot($note);
    $gate = isset($evidence['release_gate']) && is_array($evidence['release_gate']) ? $evidence['release_gate'] : [];
    if (empty($gate['allowed'])) {
        return [
            'ok' => false,
            'error' => 'Release gate blocked cutover signoff.',
            'reasons' => isset($gate['reasons']) && is_array($gate['reasons']) ? $gate['reasons'] : [],
            'evidence_bundle' => $evidence,
        ];
    }

    $signoff = [
        'signoff_id' => 'signoff_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'actor' => current_actor(),
        'status' => 'approved',
        'active' => 1,
        'note' => trim((string) $note),
        'revoked_at' => '',
        'revoked_by' => '',
        'revocation_reason' => '',
        'evidence_bundle_id' => (string) ($evidence['bundle_id'] ?? ''),
        'evidence_digest_version' => 'sha256_json_v1',
        'evidence_sha256' => hash('sha256', json_encode($evidence, JSON_UNESCAPED_SLASHES)),
        'evidence_bundle' => $evidence,
        'summary' => [
            'readiness_status' => (string) ($evidence['summary']['readiness_status'] ?? 'review_required'),
            'release_gate_allowed' => !empty($evidence['summary']['release_gate_allowed']) ? 1 : 0,
            'open_incidents' => (int) ($evidence['summary']['open_incidents'] ?? 0),
            'sla_breach_count' => (int) ($evidence['summary']['sla_breach_count'] ?? 0),
            'last_pipeline_status' => (string) ($evidence['readiness']['summary']['last_pipeline_status'] ?? ''),
        ],
    ];

    $rows = app_read_json_file(deployment_cutover_signoffs_path(), []);
    foreach ($rows as &$row) {
        if (!is_array($row)) {
            continue;
        }
        $row['active'] = 0;
    }
    unset($row);
    array_unshift($rows, $signoff);
    $rows = array_slice($rows, 0, 300);
    app_write_json_file(deployment_cutover_signoffs_path(), $rows);

    return [
        'ok' => true,
        'signoff' => $signoff,
        'evidence_bundle' => $evidence,
    ];
}

function deployment_cutover_signoff_list_snapshot()
{
    $rows = app_read_json_file(deployment_cutover_signoffs_path(), []);
    if (!is_array($rows)) {
        return [];
    }
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (!array_key_exists('active', $row)) {
            $row['active'] = 0;
        }
        if (!array_key_exists('revoked_at', $row)) {
            $row['revoked_at'] = '';
        }
        if (!array_key_exists('revoked_by', $row)) {
            $row['revoked_by'] = '';
        }
        if (!array_key_exists('revocation_reason', $row)) {
            $row['revocation_reason'] = '';
        }
        if (!array_key_exists('status', $row) || trim((string) $row['status']) === '') {
            $row['status'] = 'approved';
        }
        $out[] = $row;
    }
    return $out;
}

function deployment_cutover_signoff_active_snapshot()
{
    $rows = deployment_cutover_signoff_list_snapshot();
    $active = null;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (!empty($row['active']) && strtolower(trim((string) ($row['status'] ?? 'approved'))) !== 'revoked') {
            $active = $row;
            break;
        }
    }

    $guard = get_deployment_guard();
    $window = max(5, min(1440, (int) ($guard['release_gate_freshness_minutes'] ?? 30)));
    $age = null;
    $isFresh = false;
    if (is_array($active)) {
        $createdTs = strtotime((string) ($active['created_at'] ?? ''));
        if ($createdTs !== false) {
            $age = max(0, (int) floor((time() - $createdTs) / 60));
            $isFresh = $age <= $window;
        }
    }
    return [
        'active' => $active,
        'freshness_window_minutes' => $window,
        'age_minutes' => $age,
        'is_fresh' => $isFresh ? 1 : 0,
    ];
}

function deployment_cutover_signoff_update_snapshot($signoffId, $operation, $reason = '')
{
    $id = trim((string) $signoffId);
    if ($id === '') {
        return ['ok' => false, 'error' => 'signoff_id is required.'];
    }
    $op = strtolower(trim((string) $operation));
    if (!in_array($op, ['activate', 'revoke'], true)) {
        return ['ok' => false, 'error' => 'Unsupported signoff operation.'];
    }

    $rows = deployment_cutover_signoff_list_snapshot();
    $foundIndex = -1;
    for ($i = 0; $i < count($rows); $i++) {
        if ((string) ($rows[$i]['signoff_id'] ?? '') === $id) {
            $foundIndex = $i;
            break;
        }
    }
    if ($foundIndex < 0) {
        return ['ok' => false, 'error' => 'Cutover signoff not found.'];
    }

    $updated = null;
    if ($op === 'activate') {
        if (strtolower(trim((string) ($rows[$foundIndex]['status'] ?? 'approved'))) === 'revoked') {
            return ['ok' => false, 'error' => 'Revoked signoff cannot be activated.'];
        }
        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }
            $row['active'] = ((string) ($row['signoff_id'] ?? '') === $id) ? 1 : 0;
        }
        unset($row);
        $rows[$foundIndex]['status'] = 'approved';
        $rows[$foundIndex]['active'] = 1;
        $updated = $rows[$foundIndex];
    } else {
        $revReason = trim((string) $reason);
        if (strlen($revReason) < 8) {
            return ['ok' => false, 'error' => 'Revocation reason must be at least 8 characters.'];
        }
        $rows[$foundIndex]['status'] = 'revoked';
        $rows[$foundIndex]['active'] = 0;
        $rows[$foundIndex]['revoked_at'] = gmdate('c');
        $rows[$foundIndex]['revoked_by'] = current_actor();
        $rows[$foundIndex]['revocation_reason'] = $revReason;
        $updated = $rows[$foundIndex];
    }

    app_write_json_file(deployment_cutover_signoffs_path(), $rows);
    return [
        'ok' => true,
        'item' => $updated,
        'active' => deployment_cutover_signoff_active_snapshot(),
        'latest' => deployment_cutover_signoff_latest_snapshot(),
    ];
}

function deployment_cutover_signoff_latest_snapshot()
{
    $rows = deployment_cutover_signoff_list_snapshot();
    $latest = (!empty($rows) && is_array($rows[0])) ? $rows[0] : null;
    $guard = get_deployment_guard();
    $window = max(5, min(1440, (int) ($guard['release_gate_freshness_minutes'] ?? 30)));
    $age = null;
    $isFresh = false;
    if (is_array($latest)) {
        $createdTs = strtotime((string) ($latest['created_at'] ?? ''));
        if ($createdTs !== false) {
            $age = max(0, (int) floor((time() - $createdTs) / 60));
            $isFresh = $age <= $window;
        }
    }
    return [
        'latest' => $latest,
        'freshness_window_minutes' => $window,
        'age_minutes' => $age,
        'is_fresh' => $isFresh ? 1 : 0,
    ];
}

function deployment_cutover_signoff_verify_item($row)
{
    if (!is_array($row)) {
        return [
            'signoff_id' => '',
            'created_at' => '',
            'verifiable' => 0,
            'valid' => 0,
            'reason' => 'invalid_signoff_row',
            'bundle_id_match' => 0,
            'hash_match' => 0,
        ];
    }
    $signoffId = (string) ($row['signoff_id'] ?? '');
    $createdAt = (string) ($row['created_at'] ?? '');
    $expectedHash = (string) ($row['evidence_sha256'] ?? '');
    $expectedBundleId = (string) ($row['evidence_bundle_id'] ?? '');
    $evidence = isset($row['evidence_bundle']) && is_array($row['evidence_bundle']) ? $row['evidence_bundle'] : null;

    if (!is_array($evidence)) {
        return [
            'signoff_id' => $signoffId,
            'created_at' => $createdAt,
            'verifiable' => 0,
            'valid' => 0,
            'reason' => 'legacy_signoff_missing_embedded_evidence',
            'bundle_id_match' => 0,
            'hash_match' => 0,
        ];
    }

    $actualHash = hash('sha256', json_encode($evidence, JSON_UNESCAPED_SLASHES));
    $actualBundleId = (string) ($evidence['bundle_id'] ?? '');
    $bundleIdMatch = ($expectedBundleId !== '' && $actualBundleId !== '' && $expectedBundleId === $actualBundleId) ? 1 : 0;
    $hashMatch = ($expectedHash !== '' && hash_equals($expectedHash, $actualHash)) ? 1 : 0;
    $valid = ($bundleIdMatch === 1 && $hashMatch === 1) ? 1 : 0;
    $reason = $valid === 1 ? 'ok' : (($bundleIdMatch === 0) ? 'bundle_id_mismatch' : 'hash_mismatch');

    return [
        'signoff_id' => $signoffId,
        'created_at' => $createdAt,
        'verifiable' => 1,
        'valid' => $valid,
        'reason' => $reason,
        'bundle_id_match' => $bundleIdMatch,
        'hash_match' => $hashMatch,
        'expected_bundle_id' => $expectedBundleId,
        'actual_bundle_id' => $actualBundleId,
        'expected_sha256' => $expectedHash,
        'actual_sha256' => $actualHash,
    ];
}

function deployment_cutover_signoff_verify_snapshot($signoffId = '')
{
    $rows = deployment_cutover_signoff_list_snapshot();
    $target = null;
    if ($signoffId === '') {
        $target = (!empty($rows) && is_array($rows[0])) ? $rows[0] : null;
    } else {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ((string) ($row['signoff_id'] ?? '') === (string) $signoffId) {
                $target = $row;
                break;
            }
        }
    }
    if (!is_array($target)) {
        return [
            'ok' => false,
            'error' => 'Cutover signoff not found.',
            'signoff_id' => (string) $signoffId,
        ];
    }
    return [
        'ok' => true,
        'check' => deployment_cutover_signoff_verify_item($target),
    ];
}

function deployment_cutover_signoff_verify_all_snapshot($limit = 100)
{
    $max = max(1, min(500, (int) $limit));
    $rows = array_slice(deployment_cutover_signoff_list_snapshot(), 0, $max);
    $items = [];
    $summary = [
        'total' => 0,
        'valid' => 0,
        'invalid' => 0,
        'legacy_unverifiable' => 0,
    ];
    foreach ($rows as $row) {
        $check = deployment_cutover_signoff_verify_item($row);
        $items[] = $check;
        $summary['total']++;
        if (empty($check['verifiable'])) {
            $summary['legacy_unverifiable']++;
            continue;
        }
        if (!empty($check['valid'])) {
            $summary['valid']++;
        } else {
            $summary['invalid']++;
        }
    }
    return [
        'summary' => $summary,
        'items' => $items,
    ];
}

function deployment_cutover_signoff_integrity_watch_snapshot($source = 'manual')
{
    $src = trim((string) $source);
    if ($src === '') {
        $src = 'manual';
    }

    $active = deployment_cutover_signoff_active_snapshot();
    $activeRow = isset($active['active']) && is_array($active['active']) ? $active['active'] : null;
    $status = 'no_active';
    $reason = 'No active cutover signoff found.';
    $check = null;
    if (is_array($activeRow)) {
        $check = deployment_cutover_signoff_verify_item($activeRow);
        if (empty($check['verifiable'])) {
            $status = 'unverifiable';
            $reason = 'Active signoff is legacy/unverifiable.';
        } elseif (!empty($check['valid'])) {
            $status = 'valid';
            $reason = 'Active signoff integrity is valid.';
        } else {
            $status = 'invalid';
            $reason = 'Active signoff integrity is invalid.';
        }
    }

    $state = app_read_json_file(deployment_cutover_signoff_integrity_state_path(), []);
    if (!is_array($state)) {
        $state = [];
    }
    $prevStatus = isset($state['last_status']) ? (string) $state['last_status'] : '';
    $statusChanged = ($prevStatus !== '' && $prevStatus !== $status) ? 1 : 0;

    $lastAlertAt = isset($state['last_alert_at']) ? (string) $state['last_alert_at'] : '';
    $lastAlertTs = $lastAlertAt !== '' ? strtotime($lastAlertAt) : false;
    $cooldownMinutes = 60;
    $cooldownActive = ($lastAlertTs !== false) ? ((time() - $lastAlertTs) < ($cooldownMinutes * 60)) : false;

    $alertSent = 0;
    $alertType = '';
    $alertMessage = '';
    if ($statusChanged === 1) {
        if ($status === 'valid') {
            $alertType = 'success';
            $alertMessage = 'Cutover signoff integrity changed to VALID.';
        } elseif ($status === 'invalid') {
            $alertType = 'critical';
            $alertMessage = 'Cutover signoff integrity changed to INVALID.';
        } elseif ($status === 'unverifiable') {
            $alertType = 'warning';
            $alertMessage = 'Cutover signoff integrity changed to UNVERIFIABLE.';
        } else {
            $alertType = 'warning';
            $alertMessage = 'Cutover signoff integrity changed to NO_ACTIVE.';
        }
        $alertSent = 1;
    } elseif ($status !== 'valid' && !$cooldownActive) {
        $alertType = ($status === 'invalid') ? 'critical' : 'warning';
        $alertMessage = 'Cutover signoff integrity remains ' . strtoupper($status) . '.';
        $alertSent = 1;
    }

    if ($alertSent === 1) {
        push_notification($alertType, $alertMessage, [
            'source' => $src,
            'status' => $status,
            'reason' => $reason,
            'active_signoff_id' => is_array($activeRow) ? (string) ($activeRow['signoff_id'] ?? '') : '',
        ]);
    }

    $run = [
        'run_id' => 'signoff_integrity_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'source' => $src,
        'status' => $status,
        'reason' => $reason,
        'status_changed' => $statusChanged,
        'alert_sent' => $alertSent,
        'active_signoff_id' => is_array($activeRow) ? (string) ($activeRow['signoff_id'] ?? '') : '',
        'verification' => is_array($check) ? $check : [],
    ];
    $runs = app_read_json_file(deployment_cutover_signoff_integrity_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 400);
    app_write_json_file(deployment_cutover_signoff_integrity_runs_path(), $runs);

    $nextState = [
        'last_checked_at' => gmdate('c'),
        'last_status' => $status,
        'last_reason' => $reason,
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => $src,
        'last_alert_at' => ($alertSent === 1) ? gmdate('c') : (string) ($state['last_alert_at'] ?? ''),
        'active_signoff_id' => is_array($activeRow) ? (string) ($activeRow['signoff_id'] ?? '') : '',
    ];
    app_write_json_file(deployment_cutover_signoff_integrity_state_path(), $nextState);

    return [
        'run' => $run,
        'state' => $nextState,
    ];
}

function deployment_pipeline_run_snapshot($note = '')
{
    $install = install_check_snapshot();
    $preflight = deployment_preflight_snapshot();
    $verify = deployment_verify_snapshot();
    $goLive = deployment_handoff_bundle_snapshot();
    $guardEval = deployment_guard_evaluate();
    $watchdogWindow = isset($guardEval['guard']['release_gate_freshness_minutes'])
        ? (int) $guardEval['guard']['release_gate_freshness_minutes']
        : null;
    $watchdogs = deployment_watchdogs_status_snapshot($watchdogWindow);

    $status = 'ready';
    if ((string) ($install['status'] ?? 'warning') === 'critical') {
        $status = 'blocked';
    }
    if ((string) ($preflight['status'] ?? 'warning') === 'critical') {
        $status = 'blocked';
    }
    if ((string) ($verify['status'] ?? 'warning') === 'critical') {
        $status = 'blocked';
    }
    if ((string) ($goLive['status'] ?? 'review_required') !== 'ready') {
        $status = 'blocked';
    }
    if (empty($guardEval['allowed'])) {
        $status = 'blocked';
    }
    if ((string) ($watchdogs['status'] ?? 'warning') === 'critical') {
        $status = 'blocked';
    }

    $run = [
        'run_id' => 'pipeline_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'note' => trim((string) $note),
        'status' => $status,
        'summary' => [
            'install_status' => (string) ($install['status'] ?? 'warning'),
            'preflight_status' => (string) ($preflight['status'] ?? 'warning'),
            'verify_status' => (string) ($verify['status'] ?? 'warning'),
            'go_live_status' => (string) ($goLive['status'] ?? 'review_required'),
            'guard_allowed' => !empty($guardEval['allowed']) ? 1 : 0,
            'watchdogs_status' => (string) ($watchdogs['status'] ?? 'warning'),
        ],
        'guard_reasons' => $guardEval['reasons'],
        'watchdogs' => [
            'status' => (string) ($watchdogs['status'] ?? 'warning'),
            'summary' => isset($watchdogs['summary']) && is_array($watchdogs['summary']) ? $watchdogs['summary'] : [],
        ],
    ];

    $rows = app_read_json_file(deployment_pipeline_runs_path(), []);
    array_unshift($rows, $run);
    $rows = array_slice($rows, 0, 200);
    app_write_json_file(deployment_pipeline_runs_path(), $rows);

    return [
        'run' => $run,
        'install' => $install,
        'preflight' => $preflight,
        'verify' => $verify,
        'go_live' => $goLive,
        'guard' => $guardEval,
        'watchdogs' => $watchdogs,
    ];
}

function push_notification($type, $message, $meta = [])
{
    $rows = app_read_json_file(notifications_path(), []);
    array_unshift($rows, [
        'id' => 'notif_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'type' => (string) $type,
        'message' => (string) $message,
        'meta' => is_array($meta) ? $meta : [],
        'read' => 0,
        'created_at' => gmdate('c'),
    ]);
    $rows = array_slice($rows, 0, 500);
    app_write_json_file(notifications_path(), $rows);
}

function default_notification_settings()
{
    return [
        'sound_enabled' => !empty($GLOBALS['config']['notification_sound_enabled']) ? 1 : 0,
        'sound_mode' => in_array((string) ($GLOBALS['config']['notification_sound_mode'] ?? 'critical_only'), ['off', 'critical_only', 'all'], true)
            ? (string) ($GLOBALS['config']['notification_sound_mode'] ?? 'critical_only')
            : 'critical_only',
    ];
}

function get_notification_settings()
{
    $stored = app_read_json_file(notification_settings_path(), []);
    if (!is_array($stored)) {
        $stored = [];
    }
    $defaults = default_notification_settings();
    $merged = array_merge($defaults, $stored);
    $merged['sound_enabled'] = !empty($merged['sound_enabled']) ? 1 : 0;
    if (!in_array((string) ($merged['sound_mode'] ?? 'critical_only'), ['off', 'critical_only', 'all'], true)) {
        $merged['sound_mode'] = 'critical_only';
    }
    return $merged;
}

function save_notification_settings($settings)
{
    $payload = [
        'sound_enabled' => !empty($settings['sound_enabled']) ? 1 : 0,
        'sound_mode' => in_array((string) ($settings['sound_mode'] ?? 'critical_only'), ['off', 'critical_only', 'all'], true)
            ? (string) ($settings['sound_mode'] ?? 'critical_only')
            : 'critical_only',
    ];
    app_write_json_file(notification_settings_path(), $payload);
    return $payload;
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

function default_automation_settings()
{
    return [
        'enabled' => 0,
        'interval_minutes' => 30,
        'last_run_at' => '',
        'modules' => [
            'crm' => 1,
            'social' => 1,
            'webops' => 1,
            'seo' => 1,
        ],
    ];
}

function get_automation_settings()
{
    $stored = app_read_json_file(automation_settings_path(), []);
    if (!is_array($stored)) {
        $stored = [];
    }
    $defaults = default_automation_settings();
    $merged = array_merge($defaults, $stored);
    $modules = isset($merged['modules']) && is_array($merged['modules']) ? $merged['modules'] : [];
    $merged['modules'] = array_merge($defaults['modules'], $modules);
    $merged['enabled'] = !empty($merged['enabled']) ? 1 : 0;
    $merged['interval_minutes'] = max(5, min(1440, (int) ($merged['interval_minutes'] ?? 30)));
    $merged['last_run_at'] = (string) ($merged['last_run_at'] ?? '');
    return $merged;
}

function save_automation_settings($settings)
{
    $payload = default_automation_settings();
    $payload['enabled'] = !empty($settings['enabled']) ? 1 : 0;
    $payload['interval_minutes'] = max(5, min(1440, (int) ($settings['interval_minutes'] ?? 30)));
    $payload['last_run_at'] = (string) ($settings['last_run_at'] ?? '');
    $modules = isset($settings['modules']) && is_array($settings['modules']) ? $settings['modules'] : [];
    $payload['modules'] = [
        'crm' => !empty($modules['crm']) ? 1 : 0,
        'social' => !empty($modules['social']) ? 1 : 0,
        'webops' => !empty($modules['webops']) ? 1 : 0,
        'seo' => !empty($modules['seo']) ? 1 : 0,
    ];
    app_write_json_file(automation_settings_path(), $payload);
    return $payload;
}

function seo_default_page_signals()
{
    return [
        'title' => '',
        'title_length' => 0,
        'meta_description_length' => 0,
        'canonical_url' => '',
        'robots' => '',
        'h1_count' => 0,
        'images_missing_alt' => 0,
        'word_count' => 0,
        'heading_counts' => [
            'h1' => 0,
            'h2' => 0,
            'h3' => 0,
            'h4' => 0,
            'h5' => 0,
            'h6' => 0,
        ],
        'internal_link_count' => 0,
        'external_link_count' => 0,
        'unique_external_hosts' => 0,
        'schema_count' => 0,
        'schema_types' => [],
    ];
}

function seo_normalize_page_signals($signals)
{
    $defaults = seo_default_page_signals();
    if (!is_array($signals)) {
        return $defaults;
    }
    $normalized = array_merge($defaults, $signals);
    $headingCounts = isset($signals['heading_counts']) && is_array($signals['heading_counts']) ? $signals['heading_counts'] : [];
    $normalized['heading_counts'] = array_merge($defaults['heading_counts'], $headingCounts);
    foreach (['title_length', 'meta_description_length', 'h1_count', 'images_missing_alt', 'word_count', 'internal_link_count', 'external_link_count', 'unique_external_hosts', 'schema_count'] as $key) {
        $normalized[$key] = max(0, (int) ($normalized[$key] ?? 0));
    }
    foreach (array_keys($defaults['heading_counts']) as $tag) {
        $normalized['heading_counts'][$tag] = max(0, (int) ($normalized['heading_counts'][$tag] ?? 0));
    }
    $schemaTypes = isset($normalized['schema_types']) && is_array($normalized['schema_types']) ? $normalized['schema_types'] : [];
    $normalized['schema_types'] = array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string) $value);
    }, $schemaTypes), static function ($value) {
        return $value !== '';
    })));
    $normalized['title'] = trim((string) ($normalized['title'] ?? ''));
    $normalized['canonical_url'] = trim((string) ($normalized['canonical_url'] ?? ''));
    $normalized['robots'] = trim((string) ($normalized['robots'] ?? ''));
    return $normalized;
}

function seo_extract_title($html)
{
    if (!is_string($html) || $html === '') {
        return '';
    }
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
        return trim(html_entity_decode(strip_tags((string) ($matches[1] ?? '')), ENT_QUOTES | ENT_HTML5));
    }
    return '';
}

function seo_extract_meta_content($html, $name)
{
    if (!is_string($html) || $html === '' || trim((string) $name) === '') {
        return '';
    }
    $pattern = '/<meta[^>]+(?:name|property)=["\']' . preg_quote((string) $name, '/') . '["\'][^>]+content=["\']([^"\']*)["\']/i';
    if (preg_match($pattern, $html, $matches)) {
        return trim(html_entity_decode((string) ($matches[1] ?? ''), ENT_QUOTES | ENT_HTML5));
    }
    $patternReverse = '/<meta[^>]+content=["\']([^"\']*)["\'][^>]+(?:name|property)=["\']' . preg_quote((string) $name, '/') . '["\']/i';
    if (preg_match($patternReverse, $html, $matches)) {
        return trim(html_entity_decode((string) ($matches[1] ?? ''), ENT_QUOTES | ENT_HTML5));
    }
    return '';
}

function seo_extract_link_href($html, $rel)
{
    if (!is_string($html) || $html === '' || trim((string) $rel) === '') {
        return '';
    }
    $pattern = '/<link[^>]+rel=["\']' . preg_quote((string) $rel, '/') . '["\'][^>]+href=["\']([^"\']*)["\']/i';
    if (preg_match($pattern, $html, $matches)) {
        return trim((string) ($matches[1] ?? ''));
    }
    $patternReverse = '/<link[^>]+href=["\']([^"\']*)["\'][^>]+rel=["\']' . preg_quote((string) $rel, '/') . '["\']/i';
    if (preg_match($patternReverse, $html, $matches)) {
        return trim((string) ($matches[1] ?? ''));
    }
    return '';
}

function seo_count_tag($html, $tag)
{
    if (!is_string($html) || $html === '' || trim((string) $tag) === '') {
        return 0;
    }
    if (preg_match_all('/<' . preg_quote((string) $tag, '/') . '\b/i', $html, $matches)) {
        return count($matches[0]);
    }
    return 0;
}

function seo_count_images_missing_alt($html)
{
    if (!is_string($html) || $html === '') {
        return 0;
    }
    if (!preg_match_all('/<img\b[^>]*>/i', $html, $matches)) {
        return 0;
    }
    $missing = 0;
    foreach ($matches[0] as $imgTag) {
        if (!preg_match('/\balt\s*=\s*(["\']).*?\1/i', (string) $imgTag)) {
            $missing++;
        }
    }
    return $missing;
}

function seo_heading_counts($html)
{
    $counts = [];
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
        $counts[$tag] = seo_count_tag($html, $tag);
    }
    return $counts;
}

function seo_visible_word_count($html)
{
    if (!is_string($html) || $html === '') {
        return 0;
    }
    $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
    $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', (string) $clean);
    $text = html_entity_decode(strip_tags((string) $clean), ENT_QUOTES | ENT_HTML5);
    $text = preg_replace('/\s+/u', ' ', trim((string) $text));
    if ($text === '') {
        return 0;
    }
    $parts = preg_split('/\s+/u', $text);
    return is_array($parts) ? count(array_filter($parts, static function ($part) {
        return trim((string) $part) !== '';
    })) : 0;
}

function seo_link_summary($html, $baseUrl)
{
    $summary = [
        'internal_link_count' => 0,
        'external_link_count' => 0,
        'unique_external_hosts' => 0,
        'external_hosts' => [],
    ];
    if (!is_string($html) || $html === '') {
        return $summary;
    }
    $baseHost = strtolower((string) parse_url((string) $baseUrl, PHP_URL_HOST));
    if (!preg_match_all('/<a\b[^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
        return $summary;
    }
    $externalHosts = [];
    foreach ((array) ($matches[1] ?? []) as $hrefRaw) {
        $href = trim(html_entity_decode((string) $hrefRaw, ENT_QUOTES | ENT_HTML5));
        if ($href === '' || strpos($href, '#') === 0) {
            continue;
        }
        $lowerHref = strtolower($href);
        if (strpos($lowerHref, 'javascript:') === 0 || strpos($lowerHref, 'mailto:') === 0 || strpos($lowerHref, 'tel:') === 0) {
            continue;
        }
        $hrefHost = strtolower((string) parse_url($href, PHP_URL_HOST));
        $isAbsolute = preg_match('/^https?:\/\//i', $href) === 1;
        $isInternal = false;
        if (!$isAbsolute) {
            $isInternal = true;
        } elseif ($hrefHost !== '' && $baseHost !== '' && $hrefHost === $baseHost) {
            $isInternal = true;
        }

        if ($isInternal) {
            $summary['internal_link_count']++;
            continue;
        }

        $summary['external_link_count']++;
        if ($hrefHost !== '') {
            $externalHosts[$hrefHost] = true;
        }
    }
    $summary['external_hosts'] = array_values(array_keys($externalHosts));
    $summary['unique_external_hosts'] = count($summary['external_hosts']);
    return $summary;
}

function seo_schema_summary($html)
{
    $summary = [
        'schema_count' => 0,
        'schema_types' => [],
    ];
    if (!is_string($html) || $html === '') {
        return $summary;
    }
    $types = [];
    if (preg_match_all('/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
        $summary['schema_count'] += count($matches[0]);
        foreach ((array) ($matches[1] ?? []) as $payload) {
            if (preg_match_all('/"@type"\s*:\s*"([^"]+)"/i', (string) $payload, $typeMatches)) {
                foreach ((array) ($typeMatches[1] ?? []) as $type) {
                    $types[] = trim((string) $type);
                }
            }
        }
    }
    if (preg_match_all('/\bitemscope\b/i', $html, $microdataMatches)) {
        $summary['schema_count'] += count($microdataMatches[0]);
    }
    $summary['schema_types'] = array_values(array_unique(array_filter($types, static function ($value) {
        return trim((string) $value) !== '';
    })));
    return $summary;
}

function seo_check_priority($check)
{
    $severity = strtolower((string) ($check['severity'] ?? 'info'));
    $status = strtolower((string) ($check['status'] ?? 'pass'));
    if ($severity === 'critical' || $status === 'fail') {
        return 'critical';
    }
    if ($severity === 'warning' || $status === 'warn') {
        return 'fix_soon';
    }
    return 'nice_to_have';
}

function seo_issue_rollup_from_checks($checks)
{
    $summary = [
        'critical' => 0,
        'fix_soon' => 0,
        'nice_to_have' => 0,
    ];
    $issues = [];
    if (!is_array($checks)) {
        return [
            'priority_summary' => $summary,
            'issues' => [],
        ];
    }

    foreach ($checks as $check) {
        if (!is_array($check)) {
            if (is_scalar($check) && trim((string) $check) !== '') {
                $check = [
                    'check' => preg_replace('/[^a-z0-9_\-]/i', '_', strtolower(trim((string) $check))),
                    'status' => 'warn',
                    'severity' => 'warning',
                    'message' => trim((string) $check),
                ];
            } else {
                continue;
            }
        }
        $priority = seo_check_priority($check);
        $summary[$priority]++;
        $key = (string) ($check['check'] ?? 'unknown');
        if (!isset($issues[$key])) {
            $issues[$key] = [
                'check' => $key,
                'priority' => $priority,
                'occurrences' => 0,
                'statuses' => [],
                'messages' => [],
            ];
        }
        $issues[$key]['occurrences']++;
        $status = strtolower((string) ($check['status'] ?? 'unknown'));
        if ($status !== '' && !in_array($status, $issues[$key]['statuses'], true)) {
            $issues[$key]['statuses'][] = $status;
        }
        $message = trim((string) ($check['message'] ?? ''));
        if ($message !== '' && !in_array($message, $issues[$key]['messages'], true)) {
            $issues[$key]['messages'][] = $message;
        }
        if ($priority === 'critical' || ($priority === 'fix_soon' && $issues[$key]['priority'] !== 'critical')) {
            $issues[$key]['priority'] = $priority;
        }
    }

    return [
        'priority_summary' => $summary,
        'issues' => array_values($issues),
    ];
}

function seo_priority_rank($priority)
{
    $key = strtolower(trim((string) $priority));
    if ($key === 'critical') {
        return 3;
    }
    if ($key === 'fix_soon') {
        return 2;
    }
    return 1;
}

function seo_issue_playbook($check)
{
    $catalog = [
        'title_tag' => [
            'title' => 'Repair the page title',
            'recommendation' => 'Write a unique title in the recommended range so the page can compete in search results.',
            'owner' => 'content',
        ],
        'meta_description' => [
            'title' => 'Improve the meta description',
            'recommendation' => 'Add a descriptive summary that supports click-through from search results.',
            'owner' => 'content',
        ],
        'canonical' => [
            'title' => 'Set a canonical URL',
            'recommendation' => 'Publish a canonical tag that points to the preferred URL version.',
            'owner' => 'technical_seo',
        ],
        'robots_meta' => [
            'title' => 'Remove blocking robots directives',
            'recommendation' => 'Confirm the page is not using noindex or other indexing blockers unless the block is intentional.',
            'owner' => 'technical_seo',
        ],
        'robots_txt' => [
            'title' => 'Publish a robots.txt file',
            'recommendation' => 'Add a valid robots.txt so crawlers can discover the site rules cleanly.',
            'owner' => 'technical_seo',
        ],
        'sitemap_xml' => [
            'title' => 'Publish a sitemap',
            'recommendation' => 'Generate and expose sitemap.xml to improve crawler discovery.',
            'owner' => 'technical_seo',
        ],
        'h1' => [
            'title' => 'Fix the H1 structure',
            'recommendation' => 'Keep exactly one primary H1 for the page topic unless there is a specific template reason not to.',
            'owner' => 'content',
        ],
        'heading_structure' => [
            'title' => 'Improve section headings',
            'recommendation' => 'Add H2 sections so the page has clear topical structure and better scanability.',
            'owner' => 'content',
        ],
        'image_alt' => [
            'title' => 'Add image alt text',
            'recommendation' => 'Fill missing alt attributes with descriptive, non-spammy text for meaningful images.',
            'owner' => 'content',
        ],
        'content_depth' => [
            'title' => 'Increase content depth',
            'recommendation' => 'Expand the page with useful supporting copy, FAQs, or service details to avoid thin content.',
            'owner' => 'content',
        ],
        'internal_links' => [
            'title' => 'Strengthen internal linking',
            'recommendation' => 'Add relevant links from and to important site pages so crawlers and users can reach related content.',
            'owner' => 'technical_seo',
        ],
        'schema_markup' => [
            'title' => 'Add structured data',
            'recommendation' => 'Implement schema markup that matches the page intent, such as Organization, LocalBusiness, or Service.',
            'owner' => 'technical_seo',
        ],
        'https' => [
            'title' => 'Move the site to HTTPS',
            'recommendation' => 'Serve the target over HTTPS and redirect insecure variants.',
            'owner' => 'engineering',
        ],
        'home_reachable' => [
            'title' => 'Restore homepage availability',
            'recommendation' => 'Resolve the page availability issue before deeper SEO work; crawlers cannot evaluate a broken page.',
            'owner' => 'engineering',
        ],
        'target_url' => [
            'title' => 'Set the correct project URL',
            'recommendation' => 'Configure a valid project domain so audits point at the correct website.',
            'owner' => 'ops',
        ],
    ];
    $key = strtolower(trim((string) $check));
    if (isset($catalog[$key])) {
        return $catalog[$key];
    }
    return [
        'title' => 'Review SEO issue: ' . ($key !== '' ? $key : 'unknown'),
        'recommendation' => 'Inspect the latest audit evidence and resolve the issue based on the page context.',
        'owner' => 'technical_seo',
    ];
}

function seo_issue_map_from_audit($audit)
{
    $map = [];
    if (!is_array($audit)) {
        return $map;
    }
    $issues = isset($audit['issue_rollup']) && is_array($audit['issue_rollup']) ? $audit['issue_rollup'] : [];
    if (!empty($issues)) {
        foreach ($issues as $issue) {
            if (!is_array($issue) || empty($issue['check'])) {
                continue;
            }
            $check = (string) $issue['check'];
            $map[$check] = [
                'check' => $check,
                'priority' => (string) ($issue['priority'] ?? 'nice_to_have'),
                'occurrences' => (int) ($issue['occurrences'] ?? 0),
                'messages' => isset($issue['messages']) && is_array($issue['messages']) ? array_values($issue['messages']) : [],
                'statuses' => isset($issue['statuses']) && is_array($issue['statuses']) ? array_values($issue['statuses']) : [],
            ];
        }
        return $map;
    }

    foreach ((array) ($audit['checks'] ?? []) as $check) {
        if (!is_array($check)) {
            continue;
        }
        $key = (string) ($check['check'] ?? 'unknown');
        if (!isset($map[$key])) {
            $map[$key] = [
                'check' => $key,
                'priority' => seo_check_priority($check),
                'occurrences' => 0,
                'messages' => [],
                'statuses' => [],
            ];
        }
        $map[$key]['occurrences']++;
        $message = trim((string) ($check['message'] ?? ''));
        if ($message !== '' && !in_array($message, $map[$key]['messages'], true)) {
            $map[$key]['messages'][] = $message;
        }
        $status = trim((string) ($check['status'] ?? ''));
        if ($status !== '' && !in_array($status, $map[$key]['statuses'], true)) {
            $map[$key]['statuses'][] = $status;
        }
    }
    return $map;
}

function seo_action_plan_from_audits($project, $latestAudit, $previousAudit)
{
    $latestIssues = seo_issue_map_from_audit($latestAudit);
    $previousIssues = seo_issue_map_from_audit($previousAudit);
    $summary = [
        'new' => 0,
        'persistent' => 0,
        'monitor' => 0,
    ];
    $actions = [];

    foreach ($latestIssues as $check => $issue) {
        $playbook = seo_issue_playbook($check);
        $status = isset($previousIssues[$check]) ? 'persistent' : 'new';
        $summary[$status]++;
        $actions[] = [
            'action_id' => 'seo_action_' . preg_replace('/[^a-z0-9_\-]/i', '_', strtolower($check)),
            'check' => $check,
            'priority' => (string) ($issue['priority'] ?? 'nice_to_have'),
            'status' => $status,
            'title' => (string) ($playbook['title'] ?? ''),
            'recommendation' => (string) ($playbook['recommendation'] ?? ''),
            'suggested_owner' => (string) ($playbook['owner'] ?? 'technical_seo'),
            'occurrences' => (int) ($issue['occurrences'] ?? 0),
            'latest_messages' => array_slice(isset($issue['messages']) && is_array($issue['messages']) ? $issue['messages'] : [], 0, 3),
            'previous_priority' => isset($previousIssues[$check]) ? (string) ($previousIssues[$check]['priority'] ?? '') : '',
        ];
    }

    foreach ($previousIssues as $check => $issue) {
        if (isset($latestIssues[$check])) {
            continue;
        }
        $playbook = seo_issue_playbook($check);
        $summary['monitor']++;
        $actions[] = [
            'action_id' => 'seo_action_' . preg_replace('/[^a-z0-9_\-]/i', '_', strtolower($check)) . '_monitor',
            'check' => $check,
            'priority' => 'nice_to_have',
            'status' => 'monitor',
            'title' => 'Monitor cleared issue: ' . (string) ($playbook['title'] ?? $check),
            'recommendation' => 'This issue cleared in the latest audit. Monitor the next crawl to confirm it stays resolved.',
            'suggested_owner' => (string) ($playbook['owner'] ?? 'technical_seo'),
            'occurrences' => (int) ($issue['occurrences'] ?? 0),
            'latest_messages' => [],
            'previous_priority' => (string) ($issue['priority'] ?? 'nice_to_have'),
        ];
    }

    usort($actions, static function ($left, $right) {
        $priorityDiff = seo_priority_rank((string) ($right['priority'] ?? 'nice_to_have')) <=> seo_priority_rank((string) ($left['priority'] ?? 'nice_to_have'));
        if ($priorityDiff !== 0) {
            return $priorityDiff;
        }
        $statusOrder = ['persistent' => 3, 'new' => 2, 'monitor' => 1];
        $leftStatus = (int) ($statusOrder[(string) ($left['status'] ?? 'monitor')] ?? 0);
        $rightStatus = (int) ($statusOrder[(string) ($right['status'] ?? 'monitor')] ?? 0);
        if ($rightStatus !== $leftStatus) {
            return $rightStatus <=> $leftStatus;
        }
        return strcmp((string) ($left['check'] ?? ''), (string) ($right['check'] ?? ''));
    });

    return [
        'project' => is_array($project) ? $project : null,
        'summary' => $summary,
        'actions' => $actions,
    ];
}

function seo_normalize_history_url($url)
{
    $raw = trim((string) $url);
    if ($raw === '') {
        return '';
    }
    if (!preg_match('/^https?:\/\//i', $raw)) {
        $raw = 'https://' . ltrim($raw, '/');
    }
    $parts = parse_url($raw);
    if (!is_array($parts) || empty($parts['host'])) {
        return trim((string) $url);
    }
    $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
    $host = strtolower((string) ($parts['host'] ?? ''));
    $path = isset($parts['path']) ? (string) $parts['path'] : '/';
    if ($path === '') {
        $path = '/';
    }
    if ($path !== '/') {
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }
    }
    $query = isset($parts['query']) && trim((string) $parts['query']) !== '' ? '?' . trim((string) $parts['query']) : '';
    return $scheme . '://' . $host . $path . $query;
}

function seo_project_url_history_snapshot($project, $audits, $urlFilter = '')
{
    $items = [];
    $timeline = [];
    $normalizedProjectDomain = seo_normalize_history_url((string) ($project['domain'] ?? ''));

    foreach ((array) $audits as $audit) {
        if (!is_array($audit)) {
            continue;
        }
        $candidateUrl = seo_normalize_history_url((string) ($audit['domain'] ?? ''));
        if ($candidateUrl === '') {
            $candidateUrl = $normalizedProjectDomain;
        }
        if ($candidateUrl === '') {
            continue;
        }
        if ($urlFilter !== '' && $candidateUrl !== $urlFilter) {
            continue;
        }
        if (!isset($items[$candidateUrl])) {
            $items[$candidateUrl] = [
                'url' => $candidateUrl,
                'audit_count' => 0,
                'latest_audit_id' => '',
                'latest_audited_at' => '',
                'latest_score' => null,
                'previous_score' => null,
                'score_delta' => null,
                'latest_source' => '',
                'latest_priority_summary' => ['critical' => 0, 'fix_soon' => 0, 'nice_to_have' => 0],
                'latest_page_signals' => seo_default_page_signals(),
                'sources' => [],
            ];
        }
        $item = $items[$candidateUrl];
        if ($item['audit_count'] === 0) {
            $item['latest_audit_id'] = (string) ($audit['audit_id'] ?? '');
            $item['latest_audited_at'] = (string) ($audit['created_at'] ?? '');
            $item['latest_score'] = isset($audit['score']) ? (int) $audit['score'] : null;
            $item['latest_source'] = (string) ($audit['source'] ?? '');
            $item['latest_priority_summary'] = isset($audit['priority_summary']) && is_array($audit['priority_summary'])
                ? $audit['priority_summary']
                : seo_issue_rollup_from_checks(isset($audit['checks']) && is_array($audit['checks']) ? $audit['checks'] : [])['priority_summary'];
            $item['latest_page_signals'] = seo_normalize_page_signals(isset($audit['page_signals']) && is_array($audit['page_signals']) ? $audit['page_signals'] : []);
        } elseif ($item['audit_count'] === 1) {
            $item['previous_score'] = isset($audit['score']) ? (int) $audit['score'] : null;
            if ($item['latest_score'] !== null && $item['previous_score'] !== null) {
                $item['score_delta'] = $item['latest_score'] - $item['previous_score'];
            }
        }
        $sourceKey = (string) ($audit['source'] ?? 'unknown');
        if (!isset($item['sources'][$sourceKey])) {
            $item['sources'][$sourceKey] = 0;
        }
        $item['sources'][$sourceKey]++;
        $item['audit_count']++;
        $items[$candidateUrl] = $item;

        if ($urlFilter !== '') {
            $timeline[] = [
                'audit_id' => (string) ($audit['audit_id'] ?? ''),
                'url' => $candidateUrl,
                'score' => isset($audit['score']) ? (int) $audit['score'] : null,
                'source' => (string) ($audit['source'] ?? ''),
                'critical_issues' => (int) ($audit['critical_issues'] ?? 0),
                'warnings' => (int) ($audit['warnings'] ?? 0),
                'priority_summary' => isset($audit['priority_summary']) && is_array($audit['priority_summary'])
                    ? $audit['priority_summary']
                    : seo_issue_rollup_from_checks(isset($audit['checks']) && is_array($audit['checks']) ? $audit['checks'] : [])['priority_summary'],
                'page_signals' => seo_normalize_page_signals(isset($audit['page_signals']) && is_array($audit['page_signals']) ? $audit['page_signals'] : []),
                'created_at' => (string) ($audit['created_at'] ?? ''),
            ];
        }
    }

    $items = array_values($items);
    usort($items, static function ($left, $right) {
        return strcmp((string) ($right['latest_audited_at'] ?? ''), (string) ($left['latest_audited_at'] ?? ''));
    });

    return [
        'items' => $items,
        'timeline' => array_slice($timeline, 0, 25),
    ];
}

function seo_project_regression_snapshot($project, $audits)
{
    $latest = isset($audits[0]) && is_array($audits[0]) ? $audits[0] : null;
    $previous = isset($audits[1]) && is_array($audits[1]) ? $audits[1] : null;
    if (!is_array($latest)) {
        return [
            'project_id' => (string) ($project['project_id'] ?? ''),
            'project_name' => (string) ($project['name'] ?? ''),
            'status' => 'no_data',
            'latest_audit_id' => '',
            'previous_audit_id' => '',
            'score_delta' => null,
            'current_critical_checks' => [],
            'new_critical_checks' => [],
            'cleared_critical_checks' => [],
            'reasons' => ['No SEO audit available yet.'],
            'created_at' => gmdate('c'),
        ];
    }

    $latestIssues = seo_issue_map_from_audit($latest);
    $previousIssues = seo_issue_map_from_audit($previous);
    $currentCritical = [];
    foreach ($latestIssues as $check => $issue) {
        if ((string) ($issue['priority'] ?? 'nice_to_have') === 'critical') {
            $currentCritical[] = $check;
        }
    }
    $previousCritical = [];
    foreach ($previousIssues as $check => $issue) {
        if ((string) ($issue['priority'] ?? 'nice_to_have') === 'critical') {
            $previousCritical[] = $check;
        }
    }
    sort($currentCritical);
    sort($previousCritical);
    $newCritical = array_values(array_diff($currentCritical, $previousCritical));
    $clearedCritical = array_values(array_diff($previousCritical, $currentCritical));
    $latestScore = isset($latest['score']) ? (int) $latest['score'] : null;
    $previousScore = is_array($previous) && isset($previous['score']) ? (int) $previous['score'] : null;
    $scoreDelta = ($latestScore !== null && $previousScore !== null) ? ($latestScore - $previousScore) : null;
    $reasons = [];
    $status = 'stable';

    if ($scoreDelta !== null && $scoreDelta <= -5) {
        $status = 'regression';
        $reasons[] = 'Score dropped by ' . abs($scoreDelta) . ' points.';
    }
    if (!empty($newCritical)) {
        $status = 'regression';
        $reasons[] = 'New critical checks detected: ' . implode(', ', $newCritical) . '.';
    }
    if ($status !== 'regression' && $scoreDelta !== null && $scoreDelta >= 5) {
        $status = 'improved';
        $reasons[] = 'Score improved by ' . $scoreDelta . ' points.';
    }
    if ($status !== 'regression' && !empty($clearedCritical)) {
        $status = 'improved';
        $reasons[] = 'Critical checks cleared: ' . implode(', ', $clearedCritical) . '.';
    }
    if (empty($reasons)) {
        $reasons[] = 'No significant regression detected.';
    }

    return [
        'project_id' => (string) ($project['project_id'] ?? ''),
        'project_name' => (string) ($project['name'] ?? ''),
        'status' => $status,
        'latest_audit_id' => (string) ($latest['audit_id'] ?? ''),
        'previous_audit_id' => is_array($previous) ? (string) ($previous['audit_id'] ?? '') : '',
        'latest_score' => $latestScore,
        'previous_score' => $previousScore,
        'score_delta' => $scoreDelta,
        'current_critical_checks' => $currentCritical,
        'new_critical_checks' => $newCritical,
        'cleared_critical_checks' => $clearedCritical,
        'reasons' => $reasons,
        'created_at' => gmdate('c'),
    ];
}

function seo_regression_watch_snapshot($source = 'manual', $emitNotifications = true)
{
    $projects = app_read_json_file(seo_projects_path(), []);
    $audits = app_read_json_file(seo_audits_path(), []);
    $activeProjects = array_values(array_filter($projects, static function ($row) {
        $status = strtolower((string) ($row['status'] ?? ''));
        return in_array($status, ['active', 'enabled'], true);
    }));
    $state = app_read_json_file(seo_regression_state_path(), []);
    $previousProjects = isset($state['projects']) && is_array($state['projects']) ? $state['projects'] : [];
    $items = [];
    $summary = [
        'regression' => 0,
        'improved' => 0,
        'stable' => 0,
        'no_data' => 0,
    ];
    $nextProjects = [];
    foreach ($activeProjects as $project) {
        $projectId = (string) ($project['project_id'] ?? '');
        $projectAudits = array_values(array_filter($audits, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
        $snapshot = seo_project_regression_snapshot($project, $projectAudits);
        $items[] = $snapshot;
        $status = (string) ($snapshot['status'] ?? 'stable');
        if (!isset($summary[$status])) {
            $summary[$status] = 0;
        }
        $summary[$status]++;
        $signature = sha1(json_encode([
            'status' => $status,
            'score_delta' => $snapshot['score_delta'],
            'new_critical_checks' => $snapshot['new_critical_checks'],
            'cleared_critical_checks' => $snapshot['cleared_critical_checks'],
        ]));
        $previous = isset($previousProjects[$projectId]) && is_array($previousProjects[$projectId]) ? $previousProjects[$projectId] : [];
        $previousStatus = (string) ($previous['status'] ?? '');
        $previousSignature = (string) ($previous['signature'] ?? '');
        if ($emitNotifications) {
            if ($status === 'regression' && ($previousStatus !== 'regression' || $previousSignature !== $signature)) {
                push_notification('warning', 'SEO regression detected for ' . (string) ($snapshot['project_name'] ?? $projectId) . '.', [
                    'project_id' => $projectId,
                    'source' => $source,
                    'score_delta' => $snapshot['score_delta'],
                    'new_critical_checks' => $snapshot['new_critical_checks'],
                ]);
            } elseif ($previousStatus === 'regression' && in_array($status, ['stable', 'improved'], true)) {
                push_notification('success', 'SEO regression cleared for ' . (string) ($snapshot['project_name'] ?? $projectId) . '.', [
                    'project_id' => $projectId,
                    'source' => $source,
                    'score_delta' => $snapshot['score_delta'],
                    'cleared_critical_checks' => $snapshot['cleared_critical_checks'],
                ]);
            }
        }
        $nextProjects[$projectId] = [
            'status' => $status,
            'signature' => $signature,
            'checked_at' => gmdate('c'),
        ];
    }

    $run = [
        'run_id' => 'seo_regression_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'source' => (string) $source,
        'created_at' => gmdate('c'),
        'summary' => $summary,
        'items' => $items,
    ];
    $runs = app_read_json_file(seo_regression_runs_path(), []);
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 200);
    app_write_json_file(seo_regression_runs_path(), $runs);
    app_write_json_file(seo_regression_state_path(), [
        'last_checked_at' => gmdate('c'),
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => (string) $source,
        'summary' => $summary,
        'projects' => $nextProjects,
    ]);
    audit_event('seo', 'regression.watch', [
        'run_id' => (string) ($run['run_id'] ?? ''),
        'source' => $source,
        'summary' => $summary,
    ]);
    return [
        'run' => $run,
        'state' => app_read_json_file(seo_regression_state_path(), []),
    ];
}

function seo_extension_mask_session($session)
{
    if (!is_array($session)) {
        return [];
    }
    $masked = $session;
    $token = (string) ($masked['token'] ?? '');
    if ($token !== '') {
        $masked['token'] = substr($token, 0, 6) . str_repeat('*', max(4, strlen($token) - 10)) . substr($token, -4);
    }
    return $masked;
}

function seo_extension_session_by_token($token)
{
    $needle = trim((string) $token);
    if ($needle === '') {
        return null;
    }
    $rows = app_read_json_file(seo_extension_sessions_path(), []);
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((string) ($row['token'] ?? '') === $needle && strtolower((string) ($row['status'] ?? 'active')) === 'active') {
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
    $pageSignals = seo_default_page_signals();

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
            $html = (string) ($homeRes['raw'] ?? '');
            $pageSignals['title'] = seo_extract_title($html);
            $pageSignals['title_length'] = strlen($pageSignals['title']);
            $metaDescription = seo_extract_meta_content($html, 'description');
            $pageSignals['meta_description_length'] = strlen($metaDescription);
            $pageSignals['canonical_url'] = seo_extract_link_href($html, 'canonical');
            $pageSignals['robots'] = strtolower(seo_extract_meta_content($html, 'robots'));
            $pageSignals['h1_count'] = seo_count_tag($html, 'h1');
            $pageSignals['images_missing_alt'] = seo_count_images_missing_alt($html);
            $pageSignals['heading_counts'] = seo_heading_counts($html);
            $pageSignals['word_count'] = seo_visible_word_count($html);
            $linkSummary = seo_link_summary($html, $base);
            $pageSignals['internal_link_count'] = (int) ($linkSummary['internal_link_count'] ?? 0);
            $pageSignals['external_link_count'] = (int) ($linkSummary['external_link_count'] ?? 0);
            $pageSignals['unique_external_hosts'] = (int) ($linkSummary['unique_external_hosts'] ?? 0);
            $schemaSummary = seo_schema_summary($html);
            $pageSignals['schema_count'] = (int) ($schemaSummary['schema_count'] ?? 0);
            $pageSignals['schema_types'] = isset($schemaSummary['schema_types']) && is_array($schemaSummary['schema_types']) ? $schemaSummary['schema_types'] : [];

            if ($pageSignals['title_length'] === 0) {
                $checks[] = ['check' => 'title_tag', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Title tag missing.'];
                $warnings++;
                $score -= 12;
            } elseif ($pageSignals['title_length'] < 15 || $pageSignals['title_length'] > 65) {
                $checks[] = ['check' => 'title_tag', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Title length outside recommended range.'];
                $warnings++;
                $score -= 8;
            } else {
                $checks[] = ['check' => 'title_tag', 'status' => 'pass', 'severity' => 'info', 'message' => 'Title tag present.'];
            }

            if ($pageSignals['meta_description_length'] === 0) {
                $checks[] = ['check' => 'meta_description', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Meta description missing.'];
                $warnings++;
                $score -= 10;
            } elseif ($pageSignals['meta_description_length'] < 70 || $pageSignals['meta_description_length'] > 170) {
                $checks[] = ['check' => 'meta_description', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Meta description length outside recommended range.'];
                $warnings++;
                $score -= 6;
            } else {
                $checks[] = ['check' => 'meta_description', 'status' => 'pass', 'severity' => 'info', 'message' => 'Meta description present.'];
            }

            if ($pageSignals['canonical_url'] === '') {
                $checks[] = ['check' => 'canonical', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Canonical tag missing.'];
                $warnings++;
                $score -= 6;
            } else {
                $checks[] = ['check' => 'canonical', 'status' => 'pass', 'severity' => 'info', 'message' => 'Canonical tag present.'];
            }

            if (strpos($pageSignals['robots'], 'noindex') !== false) {
                $checks[] = ['check' => 'robots_meta', 'status' => 'fail', 'severity' => 'critical', 'message' => 'Robots meta contains noindex.'];
                $critical++;
                $score -= 20;
            } else {
                $checks[] = ['check' => 'robots_meta', 'status' => 'pass', 'severity' => 'info', 'message' => 'Robots meta does not block indexing.'];
            }

            if ($pageSignals['h1_count'] === 0) {
                $checks[] = ['check' => 'h1', 'status' => 'warn', 'severity' => 'warning', 'message' => 'No H1 tag detected.'];
                $warnings++;
                $score -= 8;
            } elseif ($pageSignals['h1_count'] > 1) {
                $checks[] = ['check' => 'h1', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Multiple H1 tags detected.'];
                $warnings++;
                $score -= 5;
            } else {
                $checks[] = ['check' => 'h1', 'status' => 'pass', 'severity' => 'info', 'message' => 'H1 tag detected.'];
            }

            if ((int) ($pageSignals['heading_counts']['h2'] ?? 0) === 0) {
                $checks[] = ['check' => 'heading_structure', 'status' => 'warn', 'severity' => 'warning', 'message' => 'No H2 tags detected for section structure.'];
                $warnings++;
                $score -= 6;
            } else {
                $checks[] = ['check' => 'heading_structure', 'status' => 'pass', 'severity' => 'info', 'message' => 'Heading structure includes H2 sections.'];
            }

            if ($pageSignals['images_missing_alt'] > 0) {
                $checks[] = ['check' => 'image_alt', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Images without alt text detected: ' . $pageSignals['images_missing_alt'] . '.'];
                $warnings++;
                $score -= min(12, $pageSignals['images_missing_alt']);
            } else {
                $checks[] = ['check' => 'image_alt', 'status' => 'pass', 'severity' => 'info', 'message' => 'Image alt coverage looks good.'];
            }

            if ($pageSignals['word_count'] < 250) {
                $checks[] = ['check' => 'content_depth', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Visible content appears thin (' . $pageSignals['word_count'] . ' words).'];
                $warnings++;
                $score -= 8;
            } else {
                $checks[] = ['check' => 'content_depth', 'status' => 'pass', 'severity' => 'info', 'message' => 'Visible content depth looks acceptable.'];
            }

            if ($pageSignals['internal_link_count'] < 3) {
                $checks[] = ['check' => 'internal_links', 'status' => 'warn', 'severity' => 'warning', 'message' => 'Low internal link coverage detected (' . $pageSignals['internal_link_count'] . ').'];
                $warnings++;
                $score -= 6;
            } else {
                $checks[] = ['check' => 'internal_links', 'status' => 'pass', 'severity' => 'info', 'message' => 'Internal link coverage detected.'];
            }

            if ($pageSignals['schema_count'] === 0) {
                $checks[] = ['check' => 'schema_markup', 'status' => 'warn', 'severity' => 'warning', 'message' => 'No structured data detected on the page.'];
                $warnings++;
                $score -= 6;
            } else {
                $checks[] = ['check' => 'schema_markup', 'status' => 'pass', 'severity' => 'info', 'message' => 'Structured data detected (' . $pageSignals['schema_count'] . ').'];
            }
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
    $rollup = seo_issue_rollup_from_checks($checks);
    return [
        'audit_id' => 'seo_audit_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'project_id' => (string) ($project['project_id'] ?? ''),
        'project_name' => (string) ($project['name'] ?? ''),
        'domain' => (string) ($project['domain'] ?? ''),
        'score' => $score,
        'critical_issues' => $critical,
        'warnings' => $warnings,
        'page_signals' => $pageSignals,
        'priority_summary' => $rollup['priority_summary'],
        'issue_rollup' => $rollup['issues'],
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

function webops_record_incident($monitor, $result, $source = 'run')
{
    $rows = app_read_json_file(webops_incidents_path(), []);
    $monitorId = (string) ($monitor['monitor_id'] ?? $result['monitor_id'] ?? '');
    $severity = (string) ($result['severity'] ?? 'warning');
    $ok = !empty($result['ok']);
    $updatedIncident = null;

    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((string) ($row['monitor_id'] ?? '') !== $monitorId || (string) ($row['status'] ?? '') !== 'open') {
            continue;
        }
        if ($ok) {
            $row['status'] = 'resolved';
            $row['resolved_at'] = gmdate('c');
            $row['updated_at'] = gmdate('c');
            $row['resolution_source'] = $source;
            $row['resolution_message'] = (string) ($result['message'] ?? '');
            $rows[$index] = $row;
            $updatedIncident = $row;
            push_notification('info', 'WebOps incident resolved for monitor ' . $monitorId . '.', ['monitor_id' => $monitorId, 'incident_id' => (string) ($row['incident_id'] ?? '')]);
        } else {
            $row['severity'] = $severity;
            $row['message'] = (string) ($result['message'] ?? '');
            $row['error_code'] = (string) ($result['error_code'] ?? '');
            $row['occurrences'] = (int) ($row['occurrences'] ?? 0) + 1;
            $row['last_seen_at'] = gmdate('c');
            $row['updated_at'] = gmdate('c');
            $row['source'] = $source;
            $rows[$index] = $row;
            $updatedIncident = $row;
        }
        app_write_json_file(webops_incidents_path(), $rows);
        return $updatedIncident;
    }

    if ($ok) {
        return null;
    }

    $incident = [
        'incident_id' => 'webops_incident_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'monitor_id' => $monitorId,
        'monitor_name' => (string) ($monitor['name'] ?? ''),
        'type' => (string) ($monitor['type'] ?? $result['type'] ?? ''),
        'severity' => $severity,
        'status' => 'open',
        'message' => (string) ($result['message'] ?? ''),
        'error_code' => (string) ($result['error_code'] ?? ''),
        'occurrences' => 1,
        'source' => $source,
        'created_at' => gmdate('c'),
        'last_seen_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
    ];
    array_unshift($rows, $incident);
    $rows = array_slice($rows, 0, 500);
    app_write_json_file(webops_incidents_path(), $rows);
    push_notification('critical', 'WebOps incident opened for monitor ' . $monitorId . '.', ['monitor_id' => $monitorId, 'incident_id' => (string) ($incident['incident_id'] ?? '')]);
    return $incident;
}

function execute_webops_action($item)
{
    $siteId = (string) ($item['site_id'] ?? '');
    $site = $siteId !== '' ? site_by_id($siteId) : null;
    $actionType = (string) ($item['action_type'] ?? '');
    $runMode = (string) ($item['run_mode'] ?? 'dry_run');
    $result = [
        'action_id' => (string) ($item['action_id'] ?? ''),
        'site_id' => $siteId,
        'action_type' => $actionType,
        'run_mode' => $runMode,
        'ok' => false,
        'simulated' => 0,
        'message' => '',
        'details' => [],
    ];

    if ($site === null) {
        $result['message'] = 'Missing valid bridge site.';
        return $result;
    }

    if ($runMode !== 'live') {
        $result['ok'] = true;
        $result['simulated'] = 1;
        $result['message'] = 'Dry run action simulated.';
        $result['details'] = $item;
        return $result;
    }

    if ($actionType === 'update_check') {
        $res = app_bridge_request($site, 'GET', 'bridge/update-health');
        $result['ok'] = !empty($res['ok']);
        $result['message'] = !empty($res['ok']) ? 'Update health retrieved.' : 'Update health request failed.';
        $result['details'] = is_array($res['data']) ? $res['data'] : ['status' => (int) ($res['status'] ?? 0)];
        return $result;
    }

    $payload = [
        'action_type' => $actionType,
        'plugin_file' => (string) ($item['plugin_file'] ?? ''),
        'desired_state' => (string) ($item['desired_state'] ?? ''),
        'run_mode' => $runMode,
    ];
    $res = app_bridge_request($site, 'POST', 'bridge/ops-action', $payload);
    $result['ok'] = !empty($res['ok']);
    $result['message'] = !empty($res['ok']) ? 'Bridge action completed.' : 'Bridge action failed.';
    $result['details'] = is_array($res['data']) ? $res['data'] : ['status' => (int) ($res['status'] ?? 0)];
    return $result;
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

    if ($type === 'ssl_expiry') {
        if ($target === '') {
            $result['message'] = 'Missing target URL.';
            $result['error_code'] = 'missing_target_url';
            return $result;
        }
        $ssl = webops_ssl_certificate_snapshot($target);
        $result['details'] = $ssl;
        if (empty($ssl['ok'])) {
            $result['message'] = 'SSL certificate check failed.';
            $result['error_code'] = 'ssl_check_failed';
            $result['severity'] = 'critical';
            return $result;
        }
        $daysRemaining = (int) ($ssl['days_remaining'] ?? 0);
        if ($daysRemaining < 0) {
            $result['message'] = 'SSL certificate already expired.';
            $result['error_code'] = 'ssl_expired';
            $result['severity'] = 'critical';
            return $result;
        }
        $result['ok'] = true;
        if ($daysRemaining <= 14) {
            $result['severity'] = 'warning';
            $result['message'] = 'SSL certificate expires in ' . $daysRemaining . ' day(s).';
        } else {
            $result['severity'] = 'info';
            $result['message'] = 'SSL certificate valid for ' . $daysRemaining . ' day(s).';
        }
        return $result;
    }

    if ($type === 'dns_resolution') {
        $host = webops_target_host($target);
        if ($host === '') {
            $result['message'] = 'Missing target host.';
            $result['error_code'] = 'missing_target_host';
            return $result;
        }
        $resolved = gethostbyname($host);
        $result['details'] = ['host' => $host, 'resolved_ip' => $resolved];
        if ($resolved === $host) {
            $result['message'] = 'DNS resolution failed.';
            $result['error_code'] = 'dns_resolution_failed';
            $result['severity'] = 'critical';
            return $result;
        }
        $result['ok'] = true;
        $result['severity'] = 'info';
        $result['message'] = 'DNS resolution succeeded.';
        return $result;
    }

    if ($type === 'wp_heartbeat') {
        $siteId = (string) ($config['bridge_site_id'] ?? '');
        $site = $siteId !== '' ? site_by_id($siteId) : null;
        if ($site !== null) {
            if ($runMode !== 'live') {
                $result['ok'] = true;
                $result['severity'] = 'info';
                $result['message'] = 'Dry run ok.';
                return $result;
            }
            $res = app_bridge_request($site, 'GET', 'bridge/wp-heartbeat');
            $result['details'] = is_array($res['data']) ? $res['data'] : ['status' => (int) ($res['status'] ?? 0)];
            if (!empty($res['ok']) && is_array($res['data'])) {
                $result['ok'] = true;
                $result['severity'] = 'info';
                $result['message'] = 'WordPress heartbeat retrieved.';
            } else {
                $result['message'] = 'WordPress heartbeat request failed.';
                $result['error_code'] = 'wp_heartbeat_failed';
                $result['severity'] = 'critical';
            }
            return $result;
        }
        if ($target === '') {
            $result['message'] = 'Missing bridge site or target URL.';
            $result['error_code'] = 'missing_target_url';
            return $result;
        }
        $heartbeatUrl = rtrim($target, '/') . '/wp-json/';
        $res = app_http_json_request('GET', $heartbeatUrl, [], null, 10);
        $result['details'] = ['status' => (int) ($res['status'] ?? 0), 'url' => $heartbeatUrl];
        if (!empty($res['ok'])) {
            $result['ok'] = true;
            $result['severity'] = 'info';
            $result['message'] = 'WordPress REST heartbeat reachable.';
        } else {
            $result['message'] = 'WordPress REST heartbeat failed.';
            $result['error_code'] = 'wp_heartbeat_failed';
            $result['severity'] = 'critical';
        }
        return $result;
    }

    if ($type === 'update_health') {
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
        $res = app_bridge_request($site, 'GET', 'bridge/update-health');
        $result['details'] = is_array($res['data']) ? $res['data'] : ['status' => (int) ($res['status'] ?? 0)];
        if (!empty($res['ok']) && is_array($res['data'])) {
            $pluginUpdates = (int) ($res['data']['updates']['plugins'] ?? 0);
            $themeUpdates = (int) ($res['data']['updates']['themes'] ?? 0);
            $coreUpdates = (int) ($res['data']['updates']['core'] ?? 0);
            $totalUpdates = $pluginUpdates + $themeUpdates + $coreUpdates;
            $result['ok'] = true;
            if ($totalUpdates > 0) {
                $result['severity'] = 'warning';
                $result['message'] = 'Updates pending: ' . $totalUpdates . '.';
            } else {
                $result['severity'] = 'info';
                $result['message'] = 'No pending updates.';
            }
        } else {
            $result['message'] = 'Update health request failed.';
            $result['error_code'] = 'update_health_failed';
            $result['severity'] = 'critical';
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

function sanitize_social_retry_drafts($drafts)
{
    if (!is_array($drafts)) {
        return [];
    }
    $rows = [];
    foreach ($drafts as $draft) {
        if (!is_array($draft)) {
            continue;
        }
        $rows[] = [
            'source_site_id' => trim((string) ($draft['source_site_id'] ?? '')),
            'lead_id' => (int) ($draft['lead_id'] ?? 0),
            'title' => trim((string) ($draft['title'] ?? '')),
            'message' => trim((string) ($draft['message'] ?? '')),
            'url' => trim((string) ($draft['url'] ?? '')),
        ];
    }
    return $rows;
}

function build_social_retry_item($result, $drafts, $context = [])
{
    $result = is_array($result) ? $result : [];
    $context = is_array($context) ? $context : [];
    return [
        'retry_id' => 'social_retry_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'connector_id' => (string) ($result['connector_id'] ?? ''),
        'provider' => (string) ($result['provider'] ?? ''),
        'type' => (string) ($result['type'] ?? ''),
        'error_codes' => isset($result['error_codes']) && is_array($result['error_codes']) ? array_values(array_unique($result['error_codes'])) : [],
        'errors' => isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : [],
        'draft_count' => count($drafts),
        'drafts' => sanitize_social_retry_drafts($drafts),
        'attempts' => 0,
        'status' => 'queued',
        'source' => trim((string) ($context['source'] ?? 'social_sync')),
        'schedule_id' => trim((string) ($context['schedule_id'] ?? '')),
        'last_result' => $result,
        'last_attempt_at' => '',
    ];
}

function append_social_sync_log($item)
{
    $rows = app_read_json_file(social_sync_log_path(), []);
    array_unshift($rows, $item);
    $rows = array_slice($rows, 0, 100);
    app_write_json_file(social_sync_log_path(), $rows);
}

function record_social_activity($type, $message, $meta = [])
{
    $rows = app_read_json_file(social_activity_feed_path(), []);
    array_unshift($rows, [
        'activity_id' => 'social_activity_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'type' => (string) $type,
        'message' => (string) $message,
        'meta' => is_array($meta) ? $meta : [],
        'created_at' => gmdate('c'),
    ]);
    $rows = array_slice($rows, 0, 300);
    app_write_json_file(social_activity_feed_path(), $rows);
}

function social_inbox_threads_rows($seedIfEmpty = false)
{
    $rows = app_read_json_file(social_inbox_threads_path(), []);
    if (!empty($rows) || !$seedIfEmpty) {
        $normalized = [];
        $changed = false;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = social_normalize_inbox_thread($row);
            if ($item !== $row) {
                $changed = true;
            }
            $normalized[] = $item;
        }
        if ($changed) {
            app_write_json_file(social_inbox_threads_path(), $normalized);
        }
        return $normalized;
    }

    $connectors = app_read_json_file(social_connectors_path(), []);
    $seeded = [];
    foreach ($connectors as $connector) {
        $decorated = decorate_social_connector($connector);
        $status = strtolower((string) ($decorated['status'] ?? 'planned'));
        $capabilities = isset($decorated['capabilities_enabled']) && is_array($decorated['capabilities_enabled']) ? $decorated['capabilities_enabled'] : [];
        if (!in_array($status, ['active', 'enabled'], true) || !in_array('can_read_inbox', $capabilities, true)) {
            continue;
        }
        $seeded[] = [
            'thread_id' => 'social_thread_' . substr(sha1((string) mt_rand()), 0, 10),
            'connector_id' => (string) ($decorated['connector_id'] ?? ''),
            'provider' => (string) ($decorated['provider'] ?? ''),
            'account_label' => (string) ($decorated['account_label'] ?? ''),
            'from_name' => 'Prospect Inquiry',
            'subject' => 'Question about services',
            'message' => 'Can you share more details about your service offer?',
            'status' => 'open',
            'priority' => 'normal',
            'owner' => '',
            'internal_note' => '',
            'reply_message' => '',
            'last_reply_at' => '',
            'last_status_at' => gmdate('c'),
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
        ];
    }

    if (!empty($seeded)) {
        app_write_json_file(social_inbox_threads_path(), $seeded);
        return $seeded;
    }

    return [];
}

function social_delivery_summary_snapshot($limit = 20)
{
    $connectors = app_read_json_file(social_connectors_path(), []);
    $syncLog = app_read_json_file(social_sync_log_path(), []);
    $retryQueue = app_read_json_file(social_retry_queue_path(), []);
    $items = [];

    foreach ($connectors as $connector) {
        if (!is_array($connector)) {
            continue;
        }
        $connectorId = (string) ($connector['connector_id'] ?? '');
        if ($connectorId === '') {
            continue;
        }
        $items[$connectorId] = [
            'connector_id' => $connectorId,
            'provider' => (string) ($connector['provider'] ?? ''),
            'type' => (string) ($connector['type'] ?? ''),
            'status' => (string) ($connector['status'] ?? ''),
            'sync_count' => 0,
            'accepted_total' => 0,
            'rejected_total' => 0,
            'queued_retries' => 0,
            'retry_attempts' => 0,
            'last_sync_at' => '',
            'last_sync_status' => 'unknown',
            'last_retry_status' => '',
            'error_codes' => [],
        ];
    }

    foreach ($syncLog as $log) {
        if (!is_array($log) || empty($log['connector_results']) || !is_array($log['connector_results'])) {
            continue;
        }
        $createdAt = (string) ($log['created_at'] ?? '');
        foreach ($log['connector_results'] as $result) {
            if (!is_array($result)) {
                continue;
            }
            $connectorId = (string) ($result['connector_id'] ?? '');
            if ($connectorId === '') {
                continue;
            }
            if (!isset($items[$connectorId])) {
                $items[$connectorId] = [
                    'connector_id' => $connectorId,
                    'provider' => (string) ($result['provider'] ?? ''),
                    'type' => (string) ($result['type'] ?? ''),
                    'status' => 'unknown',
                    'sync_count' => 0,
                    'accepted_total' => 0,
                    'rejected_total' => 0,
                    'queued_retries' => 0,
                    'retry_attempts' => 0,
                    'last_sync_at' => '',
                    'last_sync_status' => 'unknown',
                    'last_retry_status' => '',
                    'error_codes' => [],
                ];
            }
            $items[$connectorId]['sync_count']++;
            $items[$connectorId]['accepted_total'] += (int) ($result['accepted'] ?? 0);
            $items[$connectorId]['rejected_total'] += (int) ($result['rejected'] ?? 0);
            if ($items[$connectorId]['last_sync_at'] === '' || strcmp($createdAt, $items[$connectorId]['last_sync_at']) > 0) {
                $items[$connectorId]['last_sync_at'] = $createdAt;
                $items[$connectorId]['last_sync_status'] = ((int) ($result['rejected'] ?? 0) > 0 || !empty($result['errors'])) ? 'degraded' : 'ok';
            }
            foreach ((array) ($result['error_codes'] ?? []) as $code) {
                $code = (string) $code;
                if ($code === '') {
                    continue;
                }
                if (!isset($items[$connectorId]['error_codes'][$code])) {
                    $items[$connectorId]['error_codes'][$code] = 0;
                }
                $items[$connectorId]['error_codes'][$code]++;
            }
        }
    }

    foreach ($retryQueue as $retry) {
        if (!is_array($retry)) {
            continue;
        }
        $connectorId = (string) ($retry['connector_id'] ?? '');
        if ($connectorId === '') {
            continue;
        }
        if (!isset($items[$connectorId])) {
            $items[$connectorId] = [
                'connector_id' => $connectorId,
                'provider' => (string) ($retry['provider'] ?? ''),
                'type' => (string) ($retry['type'] ?? ''),
                'status' => 'unknown',
                'sync_count' => 0,
                'accepted_total' => 0,
                'rejected_total' => 0,
                'queued_retries' => 0,
                'retry_attempts' => 0,
                'last_sync_at' => '',
                'last_sync_status' => 'unknown',
                'last_retry_status' => '',
                'error_codes' => [],
            ];
        }
        $items[$connectorId]['queued_retries']++;
        $items[$connectorId]['retry_attempts'] += (int) ($retry['attempts'] ?? 0);
        $items[$connectorId]['last_retry_status'] = (string) ($retry['status'] ?? '');
        foreach ((array) ($retry['error_codes'] ?? []) as $code) {
            $code = (string) $code;
            if ($code === '') {
                continue;
            }
            if (!isset($items[$connectorId]['error_codes'][$code])) {
                $items[$connectorId]['error_codes'][$code] = 0;
            }
            $items[$connectorId]['error_codes'][$code]++;
        }
    }

    $rows = array_values(array_map(static function ($item) {
        if (is_array($item['error_codes'])) {
            arsort($item['error_codes']);
        }
        return $item;
    }, $items));

    usort($rows, static function ($a, $b) {
        $leftDebt = (int) ($a['queued_retries'] ?? 0) + (int) ($a['rejected_total'] ?? 0);
        $rightDebt = (int) ($b['queued_retries'] ?? 0) + (int) ($b['rejected_total'] ?? 0);
        if ($leftDebt === $rightDebt) {
            return strcmp((string) ($a['connector_id'] ?? ''), (string) ($b['connector_id'] ?? ''));
        }
        return ($leftDebt < $rightDebt) ? 1 : -1;
    });

    return [
        'summary' => [
            'connector_count' => count($rows),
            'active_connector_count' => count(array_filter($rows, static function ($row) {
                return in_array(strtolower((string) ($row['status'] ?? '')), ['active', 'enabled'], true);
            })),
            'sync_count' => count($syncLog),
            'retry_queue_count' => count($retryQueue),
            'connectors_with_retries' => count(array_filter($rows, static function ($row) {
                return (int) ($row['queued_retries'] ?? 0) > 0;
            })),
            'connectors_with_rejections' => count(array_filter($rows, static function ($row) {
                return (int) ($row['rejected_total'] ?? 0) > 0;
            })),
        ],
        'items' => array_slice($rows, 0, max(1, $limit)),
    ];
}

function social_delivery_connector_detail_snapshot($connectorId, $limit = 10)
{
    $id = trim((string) $connectorId);
    if ($id === '') {
        return ['ok' => false, 'error' => 'connector_id is required.'];
    }

    $connector = social_connector_by_id($id);
    $connectorMeta = is_array($connector) ? decorate_social_connector($connector) : null;
    $syncLog = app_read_json_file(social_sync_log_path(), []);
    $retryQueue = app_read_json_file(social_retry_queue_path(), []);
    $syncItems = [];
    foreach ($syncLog as $log) {
        if (!is_array($log) || empty($log['connector_results']) || !is_array($log['connector_results'])) {
            continue;
        }
        foreach ($log['connector_results'] as $result) {
            if (!is_array($result) || (string) ($result['connector_id'] ?? '') !== $id) {
                continue;
            }
            $syncItems[] = [
                'sync_id' => (string) ($log['sync_id'] ?? ''),
                'created_at' => (string) ($log['created_at'] ?? ''),
                'draft_total' => (int) ($log['draft_total'] ?? 0),
                'accepted' => (int) ($result['accepted'] ?? 0),
                'rejected' => (int) ($result['rejected'] ?? 0),
                'errors' => isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : [],
                'error_codes' => isset($result['error_codes']) && is_array($result['error_codes']) ? $result['error_codes'] : [],
                'run_mode' => (string) ($result['run_mode'] ?? ''),
            ];
        }
    }

    $retryItems = [];
    foreach ($retryQueue as $retry) {
        if (!is_array($retry) || (string) ($retry['connector_id'] ?? '') !== $id) {
            continue;
        }
        $retryItems[] = [
            'retry_id' => (string) ($retry['retry_id'] ?? ''),
            'created_at' => (string) ($retry['created_at'] ?? ''),
            'draft_id' => (string) ($retry['draft_id'] ?? ''),
            'attempts' => (int) ($retry['attempts'] ?? 0),
            'status' => (string) ($retry['status'] ?? ''),
            'error_codes' => isset($retry['error_codes']) && is_array($retry['error_codes']) ? $retry['error_codes'] : [],
            'errors' => isset($retry['errors']) && is_array($retry['errors']) ? $retry['errors'] : [],
        ];
    }

    return [
        'ok' => true,
        'connector' => $connectorMeta ?: ['connector_id' => $id],
        'summary' => [
            'sync_count' => count($syncItems),
            'retry_count' => count($retryItems),
            'accepted_total' => array_sum(array_map(static function ($item) {
                return (int) ($item['accepted'] ?? 0);
            }, $syncItems)),
            'rejected_total' => array_sum(array_map(static function ($item) {
                return (int) ($item['rejected'] ?? 0);
            }, $syncItems)),
        ],
        'sync_items' => array_slice($syncItems, 0, max(1, $limit)),
        'retry_items' => array_slice($retryItems, 0, max(1, $limit)),
    ];
}

function social_delivery_watch_state_path()
{
    return app_storage_path('social_delivery_watch_state.json');
}

function social_delivery_watch_runs_path()
{
    return app_storage_path('social_delivery_watch_runs.json');
}

function social_delivery_watch_evaluate($source = 'manual', $emitNotification = false)
{
    $snapshot = social_delivery_summary_snapshot(100);
    $items = isset($snapshot['items']) && is_array($snapshot['items']) ? $snapshot['items'] : [];
    $degraded = array_values(array_filter($items, static function ($item) {
        return (int) ($item['queued_retries'] ?? 0) > 0
            || (int) ($item['rejected_total'] ?? 0) > 0
            || (string) ($item['last_sync_status'] ?? '') === 'degraded';
    }));
    $run = [
        'watch_id' => 'socialwatch_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'created_at' => gmdate('c'),
        'source' => (string) $source,
        'summary' => [
            'connector_count' => isset($snapshot['summary']['connector_count']) ? (int) $snapshot['summary']['connector_count'] : count($items),
            'degraded_count' => count($degraded),
            'retry_backlog_connectors' => count(array_filter($degraded, static function ($item) {
                return (int) ($item['queued_retries'] ?? 0) > 0;
            })),
            'rejection_connectors' => count(array_filter($degraded, static function ($item) {
                return (int) ($item['rejected_total'] ?? 0) > 0;
            })),
            'attention_required' => !empty($degraded) ? 1 : 0,
        ],
        'items' => array_slice($degraded, 0, 25),
    ];

    app_write_json_file(social_delivery_watch_state_path(), $run);
    $runs = app_read_json_file(social_delivery_watch_runs_path(), []);
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 60);
    app_write_json_file(social_delivery_watch_runs_path(), $runs);

    if ($emitNotification && !empty($degraded)) {
        push_notification('warning', 'Social delivery watch detected degraded connectors.', [
            'watch_id' => (string) $run['watch_id'],
            'degraded_count' => (int) $run['summary']['degraded_count'],
        ]);
    }

    return $run;
}

function social_delivery_watch_summary()
{
    $state = app_read_json_file(social_delivery_watch_state_path(), []);
    $runs = app_read_json_file(social_delivery_watch_runs_path(), []);
    if (!is_array($state) || empty($state)) {
        $state = social_delivery_watch_evaluate('bootstrap', false);
    }
    return [
        'summary' => isset($state['summary']) && is_array($state['summary']) ? $state['summary'] : [],
        'latest' => $state,
        'runs' => array_slice(is_array($runs) ? $runs : [], 0, 10),
    ];
}

function social_connector_readiness($connector, $drafts)
{
    $decorated = decorate_social_connector($connector);
    $status = strtolower((string) ($decorated['status'] ?? 'planned'));
    $enabledCapabilities = isset($decorated['capabilities_enabled']) && is_array($decorated['capabilities_enabled']) ? $decorated['capabilities_enabled'] : [];
    $config = isset($decorated['config']) && is_array($decorated['config']) ? $decorated['config'] : [];
    $state = 'ready';
    $reason = '';

    if (!in_array($status, ['active', 'enabled'], true)) {
        $state = 'blocked';
        $reason = 'connector_inactive';
    } elseif (!in_array('can_publish_post', $enabledCapabilities, true) && !in_array('can_publish_video', $enabledCapabilities, true)) {
        $state = 'blocked';
        $reason = 'publish_capability_missing';
    } elseif ((string) ($decorated['provider'] ?? '') === 'wordpress_social_bridge') {
        $siteId = trim((string) ($config['bridge_site_id'] ?? ($decorated['site_id'] ?? '')));
        if ($siteId === '' || site_by_id($siteId) === null) {
            $state = 'blocked';
            $reason = 'bridge_site_missing';
        }
    } elseif ((string) ($decorated['provider'] ?? '') === 'social_webhook') {
        if (trim((string) ($config['webhook_url'] ?? '')) === '') {
            $state = 'blocked';
            $reason = 'webhook_missing';
        }
    }

    return [
        'connector_id' => (string) ($decorated['connector_id'] ?? ''),
        'account_label' => (string) ($decorated['account_label'] ?? ''),
        'provider' => (string) ($decorated['provider'] ?? ''),
        'state' => $state,
        'reason' => $reason,
        'draft_count' => count($drafts),
        'capabilities_enabled' => $enabledCapabilities,
        'profile' => isset($decorated['profile']) && is_array($decorated['profile']) ? $decorated['profile'] : [],
    ];
}

function social_validate_draft_for_connector($connector, $draft)
{
    $decorated = decorate_social_connector($connector);
    $profile = isset($decorated['profile']) && is_array($decorated['profile']) ? $decorated['profile'] : [];
    $family = (string) ($profile['family'] ?? 'custom');
    $capabilities = isset($decorated['capabilities_enabled']) && is_array($decorated['capabilities_enabled']) ? $decorated['capabilities_enabled'] : [];
    $config = isset($decorated['config']) && is_array($decorated['config']) ? $decorated['config'] : [];
    $draft = is_array($draft) ? $draft : [];
    $issues = [];
    $warnings = [];

    $message = trim((string) ($draft['message'] ?? ''));
    $title = trim((string) ($draft['title'] ?? ''));
    $url = trim((string) ($draft['url'] ?? ''));
    $status = strtolower((string) ($decorated['status'] ?? 'planned'));

    if (!in_array($status, ['active', 'enabled'], true)) {
        $issues[] = 'connector_inactive';
    }
    if (!in_array('can_publish_post', $capabilities, true) && !in_array('can_publish_video', $capabilities, true)) {
        $issues[] = 'publish_capability_missing';
    }
    if ($message === '') {
        $issues[] = 'message_missing';
    }
    if ($family === 'forum' && $title === '') {
        $issues[] = 'title_missing_for_forum';
    }
    if ($family === 'video' && $url === '') {
        $issues[] = 'url_missing_for_video';
    }
    if ((string) ($decorated['provider'] ?? '') === 'wordpress_social_bridge') {
        $siteId = trim((string) ($config['bridge_site_id'] ?? ($decorated['site_id'] ?? '')));
        if ($siteId === '' || site_by_id($siteId) === null) {
            $issues[] = 'bridge_site_missing';
        }
    }
    if ((string) ($decorated['provider'] ?? '') === 'social_webhook' && trim((string) ($config['webhook_url'] ?? '')) === '') {
        $issues[] = 'webhook_missing';
    }
    if ($url === '' && in_array($family, ['professional', 'generic'], true)) {
        $warnings[] = 'url_missing';
    }
    if ($title === '' && !in_array($family, ['forum'], true)) {
        $warnings[] = 'title_missing';
    }

    return [
        'connector_id' => (string) ($decorated['connector_id'] ?? ''),
        'provider' => (string) ($decorated['provider'] ?? ''),
        'family' => $family,
        'draft_title' => $title,
        'draft_message_length' => strlen($message),
        'draft_url' => $url,
        'ready' => empty($issues) ? 1 : 0,
        'issues' => array_values(array_unique($issues)),
        'warnings' => array_values(array_unique($warnings)),
    ];
}

function social_draft_validation_snapshot($drafts = null)
{
    $draftRows = is_array($drafts) ? $drafts : collect_social_drafts();
    $connectors = app_read_json_file(social_connectors_path(), []);
    $items = [];
    $summary = [
        'connector_count' => count($connectors),
        'draft_count' => count($draftRows),
        'ready_connectors' => 0,
        'blocked_connectors' => 0,
        'warning_connectors' => 0,
        'issue_total' => 0,
        'warning_total' => 0,
    ];

    foreach ($connectors as $connector) {
        if (!is_array($connector)) {
            continue;
        }
        $decorated = decorate_social_connector($connector);
        $connectorIssues = [];
        $connectorWarnings = [];
        foreach ($draftRows as $draft) {
            $validation = social_validate_draft_for_connector($connector, $draft);
            foreach ((array) ($validation['issues'] ?? []) as $issue) {
                if (!in_array($issue, $connectorIssues, true)) {
                    $connectorIssues[] = $issue;
                }
            }
            foreach ((array) ($validation['warnings'] ?? []) as $warning) {
                if (!in_array($warning, $connectorWarnings, true)) {
                    $connectorWarnings[] = $warning;
                }
            }
        }
        if (empty($connectorIssues)) {
            $summary['ready_connectors']++;
        } else {
            $summary['blocked_connectors']++;
        }
        if (!empty($connectorWarnings)) {
            $summary['warning_connectors']++;
        }
        $summary['issue_total'] += count($connectorIssues);
        $summary['warning_total'] += count($connectorWarnings);
        $items[] = [
            'connector_id' => (string) ($decorated['connector_id'] ?? ''),
            'account_label' => (string) ($decorated['account_label'] ?? ''),
            'provider' => (string) ($decorated['provider'] ?? ''),
            'family' => (string) (($decorated['profile']['family'] ?? 'custom')),
            'ready' => empty($connectorIssues) ? 1 : 0,
            'issues' => $connectorIssues,
            'warnings' => $connectorWarnings,
            'draft_count' => count($draftRows),
        ];
    }

    return [
        'summary' => $summary,
        'items' => $items,
    ];
}

function social_draft_delivery_plan($drafts = null, $limit = 10)
{
    $draftRows = is_array($drafts) ? $drafts : collect_social_drafts();
    $connectors = app_read_json_file(social_connectors_path(), []);
    $items = [];
    $summary = [
        'connector_count' => 0,
        'ready_connectors' => 0,
        'blocked_connectors' => 0,
        'draft_target_pairs' => 0,
        'ready_pairs' => 0,
        'blocked_pairs' => 0,
    ];

    foreach ($connectors as $connector) {
        if (!is_array($connector)) {
            continue;
        }
        $decorated = decorate_social_connector($connector);
        $summary['connector_count']++;
        $pairResults = [];
        $connectorReady = 0;
        $connectorBlocked = 0;
        $connectorWarnings = [];
        $connectorIssues = [];

        foreach ($draftRows as $index => $draft) {
            $validation = social_validate_draft_for_connector($connector, $draft);
            $summary['draft_target_pairs']++;
            $draftLabel = trim((string) (($draft['title'] ?? '') ?: ($draft['message'] ?? '')));
            if ($draftLabel === '') {
                $draftLabel = 'Draft ' . ($index + 1);
            }

            if (!empty($validation['ready'])) {
                $summary['ready_pairs']++;
                $connectorReady++;
            } else {
                $summary['blocked_pairs']++;
                $connectorBlocked++;
            }
            foreach ((array) ($validation['issues'] ?? []) as $issue) {
                if (!in_array($issue, $connectorIssues, true)) {
                    $connectorIssues[] = $issue;
                }
            }
            foreach ((array) ($validation['warnings'] ?? []) as $warning) {
                if (!in_array($warning, $connectorWarnings, true)) {
                    $connectorWarnings[] = $warning;
                }
            }
            $pairResults[] = [
                'draft_index' => $index,
                'draft_label' => substr($draftLabel, 0, 120),
                'ready' => !empty($validation['ready']) ? 1 : 0,
                'issues' => isset($validation['issues']) && is_array($validation['issues']) ? $validation['issues'] : [],
                'warnings' => isset($validation['warnings']) && is_array($validation['warnings']) ? $validation['warnings'] : [],
            ];
        }

        if ($connectorBlocked > 0) {
            $summary['blocked_connectors']++;
        } else {
            $summary['ready_connectors']++;
        }

        $items[] = [
            'connector_id' => (string) ($decorated['connector_id'] ?? ''),
            'provider' => (string) ($decorated['provider'] ?? ''),
            'account_label' => (string) ($decorated['account_label'] ?? ''),
            'status' => (string) ($decorated['status'] ?? ''),
            'family' => (string) (($decorated['profile']['family'] ?? 'custom')),
            'capabilities_enabled' => isset($decorated['capabilities_enabled']) && is_array($decorated['capabilities_enabled']) ? $decorated['capabilities_enabled'] : [],
            'ready_draft_count' => $connectorReady,
            'blocked_draft_count' => $connectorBlocked,
            'issues' => $connectorIssues,
            'warnings' => $connectorWarnings,
            'draft_results' => array_slice($pairResults, 0, $limit),
        ];
    }

    usort($items, static function ($a, $b) {
        $left = (int) ($a['blocked_draft_count'] ?? 0);
        $right = (int) ($b['blocked_draft_count'] ?? 0);
        if ($left === $right) {
            return strcmp((string) ($a['account_label'] ?? ''), (string) ($b['account_label'] ?? ''));
        }
        return ($left < $right) ? 1 : -1;
    });

    return [
        'summary' => $summary,
        'items' => $items,
    ];
}

function social_validation_label_map()
{
    return [
        'connector_inactive' => 'Connector is not active.',
        'publish_capability_missing' => 'Connector is missing publish capability.',
        'message_missing' => 'Draft message is missing.',
        'title_missing_for_forum' => 'Forum-style providers require a title.',
        'url_missing_for_video' => 'Video-style providers require a URL or media source.',
        'bridge_site_missing' => 'Bridge site is missing or invalid.',
        'webhook_missing' => 'Webhook URL is missing.',
        'url_missing' => 'URL is missing.',
        'title_missing' => 'Title is missing.',
    ];
}

function social_validate_drafts_for_connector($connector, $drafts)
{
    $issues = [];
    $warnings = [];
    foreach ((array) $drafts as $draft) {
        $validation = social_validate_draft_for_connector($connector, is_array($draft) ? $draft : []);
        foreach ((array) ($validation['issues'] ?? []) as $issue) {
            if (!in_array($issue, $issues, true)) {
                $issues[] = $issue;
            }
        }
        foreach ((array) ($validation['warnings'] ?? []) as $warning) {
            if (!in_array($warning, $warnings, true)) {
                $warnings[] = $warning;
            }
        }
    }

    $labels = social_validation_label_map();
    $issueMessages = [];
    foreach ($issues as $issue) {
        $issueMessages[] = (string) ($labels[$issue] ?? $issue);
    }
    $warningMessages = [];
    foreach ($warnings as $warning) {
        $warningMessages[] = (string) ($labels[$warning] ?? $warning);
    }

    return [
        'ready' => empty($issues) ? 1 : 0,
        'issues' => $issues,
        'warnings' => $warnings,
        'issue_messages' => $issueMessages,
        'warning_messages' => $warningMessages,
    ];
}

function social_watch_snapshot($source = 'manual', $emitNotifications = true)
{
    $connectors = app_read_json_file(social_connectors_path(), []);
    $drafts = collect_social_drafts();
    $state = app_read_json_file(social_watch_state_path(), []);
    $previousConnectors = isset($state['connectors']) && is_array($state['connectors']) ? $state['connectors'] : [];
    $items = [];
    $summary = [
        'connector_count' => count($connectors),
        'ready_connectors' => 0,
        'blocked_connectors' => 0,
        'expired_connectors' => 0,
        'expiring_soon_connectors' => 0,
        'changed_connectors' => 0,
    ];
    $nextConnectors = [];
    $nowTs = time();

    foreach ($connectors as $connector) {
        if (!is_array($connector)) {
            continue;
        }
        $decorated = decorate_social_connector($connector);
        $readiness = social_connector_readiness($connector, $drafts);
        $connectorId = (string) ($decorated['connector_id'] ?? '');
        $label = (string) ($decorated['account_label'] ?? $connectorId);
        $status = (string) (($readiness['state'] ?? '') === 'blocked' ? 'blocked' : 'ready');
        $reason = (string) ($readiness['reason'] ?? '');
        $expiresAt = trim((string) ($decorated['expires_at'] ?? ''));
        $daysUntilExpiry = null;
        if ($expiresAt !== '') {
            $expiryTs = strtotime($expiresAt);
            if ($expiryTs !== false) {
                $daysUntilExpiry = (int) floor(($expiryTs - $nowTs) / 86400);
                if ($expiryTs <= $nowTs) {
                    $status = 'expired';
                    $reason = 'credential_expired';
                } elseif ($expiryTs <= ($nowTs + (7 * DAY_IN_SECONDS)) && $status === 'ready') {
                    $status = 'expiring_soon';
                    $reason = 'credential_expiring_soon';
                }
            }
        }

        if ($status === 'ready') {
            $summary['ready_connectors']++;
        } elseif ($status === 'blocked') {
            $summary['blocked_connectors']++;
        } elseif ($status === 'expired') {
            $summary['expired_connectors']++;
        } elseif ($status === 'expiring_soon') {
            $summary['expiring_soon_connectors']++;
        }

        $previous = isset($previousConnectors[$connectorId]) && is_array($previousConnectors[$connectorId]) ? $previousConnectors[$connectorId] : [];
        $changed = false;
        if (!empty($previous)) {
            $previousStatus = (string) ($previous['status'] ?? '');
            $previousReason = (string) ($previous['reason'] ?? '');
            if ($previousStatus !== $status || $previousReason !== $reason) {
                $changed = true;
            }
            if ($emitNotifications) {
                if ($previousStatus !== 'expired' && $status === 'expired') {
                    push_notification('critical', 'Social connector expired for ' . $label . '.', [
                        'connector_id' => $connectorId,
                        'provider' => (string) ($decorated['provider'] ?? ''),
                        'source' => $source,
                    ]);
                } elseif ($previousStatus === 'expired' && $status !== 'expired') {
                    push_notification('success', 'Social connector recovered for ' . $label . '.', [
                        'connector_id' => $connectorId,
                        'provider' => (string) ($decorated['provider'] ?? ''),
                        'source' => $source,
                        'status' => $status,
                    ]);
                }
                if ($previousStatus !== 'blocked' && $status === 'blocked') {
                    push_notification('warning', 'Social connector blocked for ' . $label . '.', [
                        'connector_id' => $connectorId,
                        'provider' => (string) ($decorated['provider'] ?? ''),
                        'source' => $source,
                        'reason' => $reason,
                    ]);
                } elseif ($previousStatus === 'blocked' && in_array($status, ['ready', 'expiring_soon'], true)) {
                    push_notification('info', 'Social connector unblocked for ' . $label . '.', [
                        'connector_id' => $connectorId,
                        'provider' => (string) ($decorated['provider'] ?? ''),
                        'source' => $source,
                        'status' => $status,
                    ]);
                }
                if ($previousStatus !== 'expiring_soon' && $status === 'expiring_soon') {
                    push_notification('warning', 'Social connector expiring soon for ' . $label . '.', [
                        'connector_id' => $connectorId,
                        'provider' => (string) ($decorated['provider'] ?? ''),
                        'source' => $source,
                        'expires_at' => $expiresAt,
                    ]);
                }
            }
        }
        if ($changed) {
            $summary['changed_connectors']++;
        }

        $items[] = [
            'connector_id' => $connectorId,
            'account_label' => $label,
            'provider' => (string) ($decorated['provider'] ?? ''),
            'status' => $status,
            'reason' => $reason,
            'expires_at' => $expiresAt,
            'days_until_expiry' => $daysUntilExpiry,
            'capability_count' => count((array) ($readiness['capabilities_enabled'] ?? [])),
            'draft_count' => (int) ($readiness['draft_count'] ?? 0),
            'changed' => $changed ? 1 : 0,
        ];
        $nextConnectors[$connectorId] = [
            'status' => $status,
            'reason' => $reason,
            'expires_at' => $expiresAt,
        ];
    }

    $run = [
        'run_id' => 'social_watch_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'source' => (string) $source,
        'created_at' => gmdate('c'),
        'summary' => $summary,
        'items' => $items,
    ];
    $runs = app_read_json_file(social_watch_runs_path(), []);
    array_unshift($runs, $run);
    $runs = array_slice($runs, 0, 200);
    app_write_json_file(social_watch_runs_path(), $runs);
    $nextState = [
        'last_checked_at' => gmdate('c'),
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => (string) $source,
        'summary' => $summary,
        'connectors' => $nextConnectors,
    ];
    app_write_json_file(social_watch_state_path(), $nextState);
    audit_event('social', 'watch', [
        'run_id' => (string) ($run['run_id'] ?? ''),
        'source' => $source,
        'summary' => $summary,
    ]);
    return [
        'run' => $run,
        'state' => $nextState,
    ];
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
    $validation = social_validate_drafts_for_connector($connector, $drafts);
    $result = [
        'connector_id' => (string) ($connector['connector_id'] ?? ''),
        'provider' => $provider,
        'type' => $type,
        'run_mode' => $runMode,
        'accepted' => 0,
        'rejected' => 0,
        'errors' => [],
        'error_codes' => [],
        'warnings' => isset($validation['warning_messages']) && is_array($validation['warning_messages']) ? $validation['warning_messages'] : [],
        'warning_codes' => isset($validation['warnings']) && is_array($validation['warnings']) ? $validation['warnings'] : [],
        'validation' => $validation,
    ];

    if (empty($validation['ready'])) {
        $result['errors'] = isset($validation['issue_messages']) && is_array($validation['issue_messages']) ? $validation['issue_messages'] : ['Draft validation failed.'];
        $result['error_codes'] = isset($validation['issues']) && is_array($validation['issues']) ? $validation['issues'] : [normalize_error_code('Draft validation failed.')];
        $result['rejected'] = count($drafts);
        return $result;
    }

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

function execute_social_schedule_queue($source = 'manual')
{
    $rows = app_read_json_file(social_schedule_queue_path(), []);
    if (empty($rows)) {
        return [
            'run_id' => 'social_schedule_run_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
            'source' => (string) $source,
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'items' => [],
        ];
    }
    $connectors = app_read_json_file(social_connectors_path(), []);
    $indexedConnectors = [];
    foreach ($connectors as $connector) {
        $indexedConnectors[(string) ($connector['connector_id'] ?? '')] = $connector;
    }
    $now = time();
    $processed = 0;
    $sent = 0;
    $updatedRows = [];
    $items = [];
    foreach ($rows as $item) {
        $scheduledTs = trim((string) ($item['scheduled_for'] ?? '')) !== '' ? strtotime((string) $item['scheduled_for']) : 0;
        $status = strtolower((string) ($item['status'] ?? 'queued'));
        if ($status !== 'queued' || ($scheduledTs > 0 && $scheduledTs > $now)) {
            $updatedRows[] = $item;
            continue;
        }

        $processed++;
        $draft = [[
            'source_site_id' => 'schedule_queue',
            'lead_id' => 0,
            'title' => (string) ($item['title'] ?? ''),
            'message' => (string) ($item['message'] ?? ''),
            'url' => (string) ($item['url'] ?? ''),
        ]];

        $targetConnectors = [];
        $connectorIds = isset($item['connector_ids']) && is_array($item['connector_ids']) ? $item['connector_ids'] : [];
        if (!empty($connectorIds)) {
            foreach ($connectorIds as $connectorId) {
                if (isset($indexedConnectors[(string) $connectorId]) && is_array($indexedConnectors[(string) $connectorId])) {
                    $targetConnectors[] = $indexedConnectors[(string) $connectorId];
                }
            }
        } else {
            foreach ($connectors as $connector) {
                $connectorStatus = strtolower((string) ($connector['status'] ?? 'planned'));
                if (in_array($connectorStatus, ['active', 'enabled'], true)) {
                    $targetConnectors[] = $connector;
                }
            }
        }

        $connectorResults = [];
        $hasErrors = false;
        if (empty($targetConnectors)) {
            $hasErrors = true;
            $connectorResults[] = ['error' => 'No target connectors resolved for scheduled item.'];
        } else {
            foreach ($targetConnectors as $connector) {
                $res = execute_social_connector_sync($connector, $draft);
                $connectorResults[] = $res;
                if ((int) ($res['rejected'] ?? 0) > 0 || !empty($res['errors'])) {
                    $hasErrors = true;
                    enqueue_social_retry_item(build_social_retry_item($res, $draft, [
                        'source' => 'schedule_run',
                        'schedule_id' => (string) ($item['schedule_id'] ?? ''),
                    ]));
                }
            }
        }

        $item['attempts'] = (int) ($item['attempts'] ?? 0) + 1;
        $item['last_run_at'] = gmdate('c');
        $item['updated_at'] = gmdate('c');
        $item['status'] = $hasErrors ? 'failed' : 'sent';
        $item['connector_results'] = $connectorResults;
        $updatedRows[] = $item;
        $items[] = [
            'schedule_id' => (string) ($item['schedule_id'] ?? ''),
            'status' => (string) ($item['status'] ?? ''),
            'connector_count' => count($targetConnectors),
            'connector_results' => $connectorResults,
        ];

        append_social_sync_log([
            'sync_id' => 'social_schedule_sync_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
            'created_at' => gmdate('c'),
            'connector_count' => count($targetConnectors),
            'draft_total' => 1,
            'connector_results' => $connectorResults,
            'connectors' => array_map(static function ($row) {
                return [
                    'connector_id' => (string) ($row['connector_id'] ?? ''),
                    'provider' => (string) ($row['provider'] ?? ''),
                    'type' => (string) ($row['type'] ?? ''),
                ];
            }, $targetConnectors),
            'status' => $hasErrors ? 'schedule_failed' : 'schedule_sent',
            'source' => (string) $source,
            'schedule_id' => (string) ($item['schedule_id'] ?? ''),
        ]);

        if (!$hasErrors) {
            $sent++;
        }
    }
    app_write_json_file(social_schedule_queue_path(), array_slice($updatedRows, 0, 500));
    record_social_activity('schedule_run', 'Scheduled social queue processed.', [
        'source' => (string) $source,
        'processed' => $processed,
        'sent' => $sent,
        'failed' => max(0, $processed - $sent),
    ]);
    return [
        'run_id' => 'social_schedule_run_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'source' => (string) $source,
        'processed' => $processed,
        'sent' => $sent,
        'failed' => max(0, $processed - $sent),
        'items' => $items,
    ];
}

function execute_social_retry_queue($source = 'manual')
{
    $queue = app_read_json_file(social_retry_queue_path(), []);
    if (empty($queue)) {
        return [
            'run_id' => 'social_retry_run_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
            'source' => (string) $source,
            'processed' => 0,
            'succeeded' => 0,
            'remaining' => 0,
            'items' => [],
        ];
    }
    $fallbackDrafts = collect_social_drafts();
    $processed = 0;
    $succeeded = 0;
    $remaining = [];
    $items = [];
    foreach ($queue as $item) {
        $processed++;
        $connector = social_connector_by_id((string) ($item['connector_id'] ?? ''));
        if (!is_array($connector)) {
            $item['status'] = 'failed_missing_connector';
            $item['attempts'] = (int) ($item['attempts'] ?? 0) + 1;
            $item['last_attempt_at'] = gmdate('c');
            $remaining[] = $item;
            $items[] = [
                'retry_id' => (string) ($item['retry_id'] ?? ''),
                'status' => (string) ($item['status'] ?? ''),
                'connector_id' => (string) ($item['connector_id'] ?? ''),
            ];
            continue;
        }
        $retryDrafts = isset($item['drafts']) && is_array($item['drafts']) && !empty($item['drafts'])
            ? sanitize_social_retry_drafts($item['drafts'])
            : $fallbackDrafts;
        $res = execute_social_connector_sync($connector, $retryDrafts);
        if ((int) ($res['rejected'] ?? 0) === 0 && empty($res['errors'])) {
            $succeeded++;
            append_social_sync_log([
                'sync_id' => 'social_retry_sync_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
                'created_at' => gmdate('c'),
                'connector_count' => 1,
                'draft_total' => count($retryDrafts),
                'connector_results' => [$res],
                'connectors' => [[
                    'connector_id' => (string) ($res['connector_id'] ?? ''),
                    'provider' => (string) ($res['provider'] ?? ''),
                    'type' => (string) ($res['type'] ?? ''),
                ]],
                'status' => 'retry_sent',
                'source' => (string) $source,
                'retry_id' => (string) ($item['retry_id'] ?? ''),
                'schedule_id' => (string) ($item['schedule_id'] ?? ''),
            ]);
            $items[] = [
                'retry_id' => (string) ($item['retry_id'] ?? ''),
                'status' => 'sent',
                'connector_id' => (string) ($res['connector_id'] ?? ''),
            ];
            continue;
        }
        $item['status'] = 'queued';
        $item['attempts'] = (int) ($item['attempts'] ?? 0) + 1;
        $item['errors'] = isset($res['errors']) && is_array($res['errors']) ? $res['errors'] : [];
        $item['error_codes'] = isset($res['error_codes']) && is_array($res['error_codes']) ? array_values(array_unique($res['error_codes'])) : [];
        $item['draft_count'] = count($retryDrafts);
        $item['drafts'] = sanitize_social_retry_drafts($retryDrafts);
        $item['last_result'] = $res;
        $item['last_attempt_at'] = gmdate('c');
        $remaining[] = $item;
        $items[] = [
            'retry_id' => (string) ($item['retry_id'] ?? ''),
            'status' => (string) ($item['status'] ?? ''),
            'connector_id' => (string) ($item['connector_id'] ?? ''),
        ];
    }
    app_write_json_file(social_retry_queue_path(), $remaining);
    record_social_activity('retry_run', 'Social retry queue processed.', [
        'source' => (string) $source,
        'processed' => $processed,
        'succeeded' => $succeeded,
        'remaining' => count($remaining),
    ]);
    return [
        'run_id' => 'social_retry_run_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'source' => (string) $source,
        'processed' => $processed,
        'succeeded' => $succeeded,
        'remaining' => count($remaining),
        'items' => $items,
    ];
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

function execute_automation_run($settings, $source = 'manual')
{
    $automationId = 'auto_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6);
    $summary = [
        'automation_id' => $automationId,
        'created_at' => gmdate('c'),
        'source' => (string) $source,
        'settings_snapshot' => $settings,
        'crm' => ['processed' => 0, 'failed' => 0, 'smtp_disconnected_sites' => 0, 'smtp_watch_run_id' => '', 'delivery_degraded_connectors' => 0, 'delivery_watch_id' => ''],
        'social' => ['processed' => 0, 'failed' => 0, 'blocked_connectors' => 0, 'expired_connectors' => 0, 'watch_run_id' => '', 'delivery_degraded_connectors' => 0, 'delivery_watch_id' => '', 'scheduled_processed' => 0, 'scheduled_sent' => 0, 'scheduled_failed' => 0, 'schedule_run_id' => '', 'retry_processed' => 0, 'retry_succeeded' => 0, 'retry_remaining' => 0, 'retry_run_id' => '', 'inbox_watch_run_id' => '', 'inbox_stale_backlog' => 0, 'inbox_unassigned_backlog' => 0, 'inbox_attention_required' => 0],
        'webops' => ['processed' => 0, 'failed' => 0],
        'seo' => ['processed' => 0, 'failed' => 0, 'regressions' => 0, 'regression_run_id' => ''],
    ];

    if (!empty($settings['modules']['crm'])) {
        $crmConnectors = app_read_json_file(app_storage_path('crm_connectors.json'), []);
        $crmActive = array_values(array_filter($crmConnectors, static function ($row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            return in_array($status, ['active', 'enabled'], true);
        }));
        $leadPayload = collect_approved_leads();
        $leads = $leadPayload['leads'];
        foreach ($crmActive as $connector) {
            $summary['crm']['processed']++;
            $res = execute_connector_sync($connector, $leads);
            if ((int) ($res['rejected'] ?? 0) > 0 || !empty($res['errors'])) {
                $summary['crm']['failed']++;
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
        $smtpWatch = crm_smtp_watch_snapshot('automation_' . (string) $source, true);
        $summary['crm']['smtp_disconnected_sites'] = (int) (($smtpWatch['run']['summary']['disconnected_sites'] ?? 0));
        $summary['crm']['smtp_watch_run_id'] = (string) ($smtpWatch['run']['run_id'] ?? '');
        $deliveryWatch = crm_delivery_watch_evaluate('automation_' . (string) $source, true);
        $summary['crm']['delivery_degraded_connectors'] = (int) (($deliveryWatch['summary']['degraded_count'] ?? 0));
        $summary['crm']['delivery_watch_id'] = (string) ($deliveryWatch['watch_id'] ?? '');
    }

    if (!empty($settings['modules']['social'])) {
        $socialConnectors = app_read_json_file(social_connectors_path(), []);
        $socialActive = array_values(array_filter($socialConnectors, static function ($row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            return in_array($status, ['active', 'enabled'], true);
        }));
        $drafts = collect_social_drafts();
        foreach ($socialActive as $connector) {
            $summary['social']['processed']++;
            $res = execute_social_connector_sync($connector, $drafts);
            if ((int) ($res['rejected'] ?? 0) > 0 || !empty($res['errors'])) {
                $summary['social']['failed']++;
                enqueue_social_retry_item(build_social_retry_item($res, $drafts, [
                    'source' => 'automation',
                ]));
            }
        }
        $scheduleRun = execute_social_schedule_queue('automation_schedule');
        $summary['social']['scheduled_processed'] = (int) ($scheduleRun['processed'] ?? 0);
        $summary['social']['scheduled_sent'] = (int) ($scheduleRun['sent'] ?? 0);
        $summary['social']['scheduled_failed'] = (int) ($scheduleRun['failed'] ?? 0);
        $summary['social']['schedule_run_id'] = (string) ($scheduleRun['run_id'] ?? '');
        $retryRun = execute_social_retry_queue('automation_retry');
        $summary['social']['retry_processed'] = (int) ($retryRun['processed'] ?? 0);
        $summary['social']['retry_succeeded'] = (int) ($retryRun['succeeded'] ?? 0);
        $summary['social']['retry_remaining'] = (int) ($retryRun['remaining'] ?? 0);
        $summary['social']['retry_run_id'] = (string) ($retryRun['run_id'] ?? '');
        $socialWatch = social_watch_snapshot('automation_' . (string) $source, true);
        $summary['social']['blocked_connectors'] = (int) (($socialWatch['run']['summary']['blocked_connectors'] ?? 0));
        $summary['social']['expired_connectors'] = (int) (($socialWatch['run']['summary']['expired_connectors'] ?? 0));
        $summary['social']['watch_run_id'] = (string) ($socialWatch['run']['run_id'] ?? '');
        $deliveryWatch = social_delivery_watch_evaluate('automation_' . (string) $source, true);
        $summary['social']['delivery_degraded_connectors'] = (int) (($deliveryWatch['summary']['degraded_count'] ?? 0));
        $summary['social']['delivery_watch_id'] = (string) ($deliveryWatch['watch_id'] ?? '');
        $inboxWatch = social_inbox_watch_snapshot('automation_' . (string) $source, true);
        $summary['social']['inbox_watch_run_id'] = (string) (($inboxWatch['run']['run_id'] ?? ''));
        $summary['social']['inbox_stale_backlog'] = (int) (($inboxWatch['run']['summary']['stale_backlog'] ?? 0));
        $summary['social']['inbox_unassigned_backlog'] = (int) (($inboxWatch['run']['summary']['unassigned_backlog'] ?? 0));
        $summary['social']['inbox_attention_required'] = (int) (($inboxWatch['run']['summary']['attention_required'] ?? 0));
    }

    if (!empty($settings['modules']['webops'])) {
        $webopsMonitors = app_read_json_file(webops_monitors_path(), []);
        $webopsActive = array_values(array_filter($webopsMonitors, static function ($row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            return in_array($status, ['active', 'enabled'], true);
        }));
        foreach ($webopsActive as $monitor) {
            $summary['webops']['processed']++;
            $res = execute_webops_monitor($monitor);
            if (empty($res['ok'])) {
                $summary['webops']['failed']++;
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
    }

    if (!empty($settings['modules']['seo'])) {
        $seoProjects = app_read_json_file(seo_projects_path(), []);
        $seoActive = array_values(array_filter($seoProjects, static function ($row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            return in_array($status, ['active', 'enabled'], true);
        }));
        $seoAudits = app_read_json_file(seo_audits_path(), []);
        foreach ($seoActive as $project) {
            $summary['seo']['processed']++;
            $audit = run_seo_audit_for_project($project);
            if ((int) ($audit['critical_issues'] ?? 0) > 0) {
                $summary['seo']['failed']++;
            }
            array_unshift($seoAudits, $audit);
        }
        $seoAudits = array_slice($seoAudits, 0, 500);
        app_write_json_file(seo_audits_path(), $seoAudits);
        $watch = seo_regression_watch_snapshot('automation_' . (string) $source, true);
        $summary['seo']['regressions'] = (int) (($watch['run']['summary']['regression'] ?? 0));
        $summary['seo']['regression_run_id'] = (string) ($watch['run']['run_id'] ?? '');
    }

    $runs = app_read_json_file(automation_runs_path(), []);
    array_unshift($runs, $summary);
    $runs = array_slice($runs, 0, 200);
    app_write_json_file(automation_runs_path(), $runs);

    $failTotal = $summary['crm']['failed']
        + (int) ($summary['crm']['smtp_disconnected_sites'] ?? 0)
        + $summary['social']['failed']
        + (int) ($summary['social']['inbox_attention_required'] ?? 0)
        + $summary['webops']['failed']
        + $summary['seo']['failed'];
    if ($failTotal > 0) {
        push_notification('critical', 'Automation run completed with issues.', [
            'automation_id' => $automationId,
            'failed_total' => $failTotal,
            'summary' => $summary,
        ]);
    } else {
        push_notification('success', 'Automation run completed successfully.', [
            'automation_id' => $automationId,
            'summary' => $summary,
        ]);
    }

    return $summary;
}

$publicActions = ['status', 'healthcheck', 'install.check', 'deployment.preflight', 'seo.extension.intake', 'automation.scheduler.tick'];
if (!in_array($action, $publicActions, true)) {
    app_require_auth();
}

$rateLimitedWriteActions = [
    'crm.connectors.save', 'crm.connectors.delete', 'crm.connectors.test', 'crm.push.sync', 'crm.retry.run',
    'social.connectors.save', 'social.connectors.delete', 'social.connectors.test', 'social.push.sync', 'social.retry.run', 'social.watch.run',
    'webops.monitors.save', 'webops.monitors.delete', 'webops.monitors.test', 'webops.run', 'webops.retry.run',
    'seo.projects.save', 'seo.projects.delete', 'seo.audit.run', 'seo.extension.intake', 'seo.extension.session.create', 'seo.extension.session.revoke', 'seo.regressions.run',
    'crm.smtp.probe', 'crm.smtp.send_test', 'crm.smtp.confirm',
    'crm.email_templates.save',
    'crm.smtp.watch.run',
    'crm.email_templates.preview', 'crm.email_templates.send_test',
    'notifications.read_all', 'notifications.settings.save',
    'automation.settings.save', 'automation.run_all', 'automation.scheduler.tick',
    'deployment.release.candidate',
    'deployment.pipeline.run',
    'deployment.artifact.verify',
    'deployment.guard.preview',
    'deployment.guard.bypass.enable',
    'deployment.guard.bypass.extend',
    'deployment.guard.bypass.disable',
    'deployment.incident.resolve',
    'deployment.incident.sla.check',
    'deployment.smoke.report',
    'deployment.smoke.suite',
    'deployment.cutover.signoff.create',
    'deployment.cutover.signoff.activate',
    'deployment.cutover.signoff.revoke',
    'deployment.cutover.signoff.integrity.watch',
    'deployment.watchdogs.check',
    'deployment.watchdogs.incident.resolve',
    'deployment.watchdogs.incident.reopen',
    'deployment.watchdogs.policy.save',
    'deployment.watchdogs.policy.restore',
    'deployment.watchdogs.policy.baseline.set',
    'deployment.watchdogs.policy.baseline.clear',
    'deployment.watchdogs.policy.baseline.check',
    'deployment.release.gate.watch',
    'deployment.release.gate.settings.save',
    'deployment.verify',
    'backup.import',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, $rateLimitedWriteActions, true)) {
    $max = ($action === 'automation.scheduler.tick') ? 240 : 60;
    $window = ($action === 'automation.scheduler.tick') ? 3600 : 60;
    enforce_rate_limit($action, $max, $window);
}

$deploymentGuardedActions = [
    'crm.connectors.save', 'crm.connectors.delete', 'crm.connectors.test', 'crm.push.sync', 'crm.retry.run',
    'social.connectors.save', 'social.connectors.delete', 'social.connectors.test', 'social.push.sync', 'social.retry.run', 'social.watch.run',
    'webops.monitors.save', 'webops.monitors.delete', 'webops.monitors.test', 'webops.run', 'webops.retry.run',
    'seo.projects.save', 'seo.projects.delete', 'seo.audit.run', 'seo.extension.session.create', 'seo.extension.session.revoke', 'seo.regressions.run',
    'crm.smtp.probe', 'crm.smtp.send_test', 'crm.smtp.confirm',
    'crm.email_templates.save',
    'crm.smtp.watch.run',
    'crm.email_templates.preview', 'crm.email_templates.send_test',
    'automation.settings.save', 'automation.run_all',
    'backup.import',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, $deploymentGuardedActions, true)) {
    $gate = deployment_guard_evaluate();
    if (empty($gate['allowed'])) {
        push_notification('critical', 'Deployment guard blocked action: ' . $action, [
            'action' => $action,
            'reasons' => $gate['reasons'],
        ]);
        audit_event('deployment', 'guard.blocked', [
            'action' => $action,
            'reasons' => $gate['reasons'],
            'guard' => $gate['guard'],
        ]);
        out_json([
            'ok' => false,
            'error' => 'Deployment guard blocked this action.',
            'action' => $action,
            'reasons' => $gate['reasons'],
            'guard' => $gate['guard'],
        ], 423);
    }
}

if ($action === 'status') {
    $automationSettings = get_automation_settings();
    $health = healthcheck_snapshot();
    $guard = deployment_guard_evaluate();
    out_json([
        'ok' => true,
        'service' => '5N2 App API',
        'phase' => '2.87-social-drafts-export',
        'modules' => [
            'leads' => 'active',
            'crm_email' => 'bootstrap',
            'social_forums' => 'active',
            'webops_security' => 'active',
            'seo_suite' => 'active',
        ],
        'automation' => $automationSettings,
        'health' => [
            'status' => (string) ($health['status'] ?? 'warning'),
        ],
        'deployment_guard' => [
            'allowed' => !empty($guard['allowed']),
            'reasons' => $guard['reasons'],
            'enforced' => !empty($guard['guard']['enforced']) ? 1 : 0,
            'unlocked' => !empty($guard['guard']['unlocked']) ? 1 : 0,
        ],
        'sites_configured' => count(all_sites()),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'install.check') {
    $snap = install_check_snapshot();
    save_deployment_guard([
        'last_install_check' => [
            'status' => (string) ($snap['status'] ?? 'warning'),
            'time' => (string) ($snap['time'] ?? gmdate('c')),
        ],
    ]);
    $http = ($snap['status'] === 'critical') ? 503 : 200;
    out_json([
        'ok' => $snap['status'] !== 'critical',
        'status' => $snap['status'],
        'checks' => $snap['checks'],
        'summary' => $snap['summary'],
        'time' => $snap['time'],
    ], $http);
}

if ($action === 'healthcheck') {
    $snap = healthcheck_snapshot();
    $http = ($snap['status'] === 'critical') ? 503 : 200;
    out_json([
        'ok' => $snap['status'] !== 'critical',
        'status' => $snap['status'],
        'checks' => $snap['checks'],
        'time' => $snap['time'],
    ], $http);
}

if ($action === 'deployment.preflight') {
    $snap = deployment_preflight_snapshot();
    save_deployment_guard([
        'last_preflight' => [
            'status' => (string) ($snap['status'] ?? 'warning'),
            'time' => (string) ($snap['time'] ?? gmdate('c')),
        ],
    ]);
    $http = ($snap['status'] === 'critical') ? 503 : 200;
    out_json([
        'ok' => $snap['status'] !== 'critical',
        'status' => $snap['status'],
        'health' => $snap['health'],
        'checks' => $snap['checks'],
        'time' => $snap['time'],
    ], $http);
}

if ($action === 'deployment.report') {
    $report = deployment_report_snapshot();
    audit_event('deployment', 'report.export', ['report_id' => (string) $report['report_id'], 'status' => (string) $report['status']]);
    out_json([
        'ok' => true,
        'report' => $report,
    ]);
}

if ($action === 'deployment.handoff.bundle') {
    $bundle = deployment_handoff_bundle_snapshot();
    audit_event('deployment', 'handoff.bundle.export', ['bundle_id' => (string) $bundle['bundle_id'], 'status' => (string) $bundle['status']]);
    out_json([
        'ok' => true,
        'bundle' => $bundle,
    ]);
}

if ($action === 'deployment.go_live_status') {
    $bundle = deployment_handoff_bundle_snapshot();
    $guardEval = deployment_guard_evaluate();
    $freshness = isset($_GET['freshness_minutes']) ? (int) $_GET['freshness_minutes'] : null;
    $gate = deployment_release_gate_snapshot($freshness);
    $status = (string) ($bundle['status'] ?? 'review_required');
    if (empty($gate['allowed'])) {
        $status = 'blocked';
    }
    $headline = $status === 'ready'
        ? 'Go-live checks passed. System is ready for controlled launch.'
        : 'Go-live checks need attention before launch.';
    out_json([
        'ok' => true,
        'status' => $status,
        'headline' => $headline,
        'guard_allowed' => !empty($guardEval['allowed']),
        'guard_reasons' => $guardEval['reasons'],
        'release_gate' => $gate,
        'summary' => $bundle['summary'],
        'failed_environment_checklist' => $bundle['failed_environment_checklist'],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate') {
    $freshness = isset($_GET['freshness_minutes']) ? (int) $_GET['freshness_minutes'] : null;
    $gate = deployment_release_gate_snapshot($freshness);
    out_json([
        'ok' => true,
        'gate' => $gate,
        'time' => gmdate('c'),
    ], !empty($gate['allowed']) ? 200 : 409);
}

if ($action === 'deployment.release.gate.settings.get') {
    $guard = get_deployment_guard();
    $settings = [
        'freshness_minutes' => max(5, min(1440, (int) ($guard['release_gate_freshness_minutes'] ?? 30))),
        'require_readiness' => !empty($guard['release_gate_require_readiness']) ? 1 : 0,
        'require_public_smoke' => !empty($guard['release_gate_require_public_smoke']) ? 1 : 0,
        'require_auth_smoke' => !empty($guard['release_gate_require_auth_smoke']) ? 1 : 0,
        'require_cutover_signoff' => !empty($guard['release_gate_require_cutover_signoff']) ? 1 : 0,
        'require_signoff_integrity' => !empty($guard['release_gate_require_signoff_integrity']) ? 1 : 0,
        'require_signoff_integrity_watch' => !empty($guard['release_gate_require_signoff_integrity_watch']) ? 1 : 0,
        'require_watchdogs_policy_baseline_match' => !empty($guard['release_gate_require_watchdogs_policy_baseline_match']) ? 1 : 0,
        'require_watchdogs_policy_baseline_check' => !empty($guard['release_gate_require_watchdogs_policy_baseline_check']) ? 1 : 0,
    ];
    out_json([
        'ok' => true,
        'settings' => $settings,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.settings.save') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $settings = [
        'release_gate_freshness_minutes' => max(5, min(1440, (int) ($data['freshness_minutes'] ?? 30))),
        'release_gate_require_readiness' => !empty($data['require_readiness']) ? 1 : 0,
        'release_gate_require_public_smoke' => !empty($data['require_public_smoke']) ? 1 : 0,
        'release_gate_require_auth_smoke' => !empty($data['require_auth_smoke']) ? 1 : 0,
        'release_gate_require_cutover_signoff' => !empty($data['require_cutover_signoff']) ? 1 : 0,
        'release_gate_require_signoff_integrity' => !empty($data['require_signoff_integrity']) ? 1 : 0,
        'release_gate_require_signoff_integrity_watch' => !empty($data['require_signoff_integrity_watch']) ? 1 : 0,
        'release_gate_require_watchdogs_policy_baseline_match' => !empty($data['require_watchdogs_policy_baseline_match']) ? 1 : 0,
        'release_gate_require_watchdogs_policy_baseline_check' => !empty($data['require_watchdogs_policy_baseline_check']) ? 1 : 0,
    ];
    $saved = save_deployment_guard($settings);
    push_notification('info', 'Release gate settings updated.', [
        'freshness_minutes' => (int) ($saved['release_gate_freshness_minutes'] ?? 30),
    ]);
    audit_event('deployment', 'release.gate.settings.save', [
        'freshness_minutes' => (int) ($saved['release_gate_freshness_minutes'] ?? 30),
        'require_readiness' => !empty($saved['release_gate_require_readiness']) ? 1 : 0,
        'require_public_smoke' => !empty($saved['release_gate_require_public_smoke']) ? 1 : 0,
        'require_auth_smoke' => !empty($saved['release_gate_require_auth_smoke']) ? 1 : 0,
        'require_cutover_signoff' => !empty($saved['release_gate_require_cutover_signoff']) ? 1 : 0,
        'require_signoff_integrity' => !empty($saved['release_gate_require_signoff_integrity']) ? 1 : 0,
        'require_signoff_integrity_watch' => !empty($saved['release_gate_require_signoff_integrity_watch']) ? 1 : 0,
        'require_watchdogs_policy_baseline_match' => !empty($saved['release_gate_require_watchdogs_policy_baseline_match']) ? 1 : 0,
        'require_watchdogs_policy_baseline_check' => !empty($saved['release_gate_require_watchdogs_policy_baseline_check']) ? 1 : 0,
    ]);
    out_json([
        'ok' => true,
        'settings' => [
            'freshness_minutes' => (int) ($saved['release_gate_freshness_minutes'] ?? 30),
            'require_readiness' => !empty($saved['release_gate_require_readiness']) ? 1 : 0,
            'require_public_smoke' => !empty($saved['release_gate_require_public_smoke']) ? 1 : 0,
            'require_auth_smoke' => !empty($saved['release_gate_require_auth_smoke']) ? 1 : 0,
            'require_cutover_signoff' => !empty($saved['release_gate_require_cutover_signoff']) ? 1 : 0,
            'require_signoff_integrity' => !empty($saved['release_gate_require_signoff_integrity']) ? 1 : 0,
            'require_signoff_integrity_watch' => !empty($saved['release_gate_require_signoff_integrity_watch']) ? 1 : 0,
            'require_watchdogs_policy_baseline_match' => !empty($saved['release_gate_require_watchdogs_policy_baseline_match']) ? 1 : 0,
            'require_watchdogs_policy_baseline_check' => !empty($saved['release_gate_require_watchdogs_policy_baseline_check']) ? 1 : 0,
        ],
        'gate' => deployment_release_gate_snapshot(null),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.watch') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $source = isset($data['source']) ? (string) $data['source'] : 'manual';
    $freshness = isset($data['freshness_minutes']) ? (int) $data['freshness_minutes'] : null;
    $snap = deployment_release_gate_watch_snapshot($source, $freshness);
    audit_event('deployment', 'release.gate.watch', [
        'run_id' => (string) ($snap['run']['run_id'] ?? ''),
        'allowed' => (int) ($snap['run']['allowed'] ?? 0),
        'status_changed' => (int) ($snap['run']['status_changed'] ?? 0),
        'alert_sent' => (int) ($snap['run']['alert_sent'] ?? 0),
        'source' => (string) ($snap['run']['source'] ?? 'manual'),
    ]);
    out_json([
        'ok' => true,
        'watch' => $snap,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.runs') {
    $runs = app_read_json_file(deployment_release_gate_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    $filter = deployment_release_gate_runs_apply_filters($runs, $_GET);
    $transitionLimit = deployment_release_gate_sustained_transition_limit_from_query($_GET, 12);
    $items = isset($filter['items']) && is_array($filter['items']) ? $filter['items'] : [];
    $state = app_read_json_file(deployment_release_gate_state_path(), []);
    $sustainedState = deployment_release_gate_sustained_state_snapshot($state);
    $sustainedTimeline = deployment_release_gate_sustained_timeline_snapshot($items, $transitionLimit);
    $summary = deployment_release_gate_runs_summary($items);
    out_json([
        'ok' => true,
        'count' => count($items),
        'filtered_total_count' => (int) ($filter['filtered_total_count'] ?? count($items)),
        'window_total_count' => (int) ($filter['window_total_count'] ?? count($items)),
        'total_count' => (int) ($filter['total_count'] ?? count($runs)),
        'items' => $items,
        'summary' => $summary,
        'applied_filters' => isset($filter['applied_filters']) && is_array($filter['applied_filters']) ? $filter['applied_filters'] : [],
        'state' => is_array($state) ? $state : [],
        'sustained_state' => $sustainedState,
        'sustained_timeline' => $sustainedTimeline,
        'sustained_transition_limit' => $transitionLimit,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.runs.meta') {
    $meta = deployment_release_gate_runs_meta_snapshot($_GET);
    out_json([
        'ok' => true,
        'items_considered' => (int) ($meta['items_considered'] ?? 0),
        'applied_filters' => isset($meta['applied_filters']) && is_array($meta['applied_filters']) ? $meta['applied_filters'] : [],
        'source_options' => isset($meta['source_options']) && is_array($meta['source_options']) ? $meta['source_options'] : [],
        'failed_item_options' => isset($meta['failed_item_options']) && is_array($meta['failed_item_options']) ? $meta['failed_item_options'] : [],
        'source_group_counts' => isset($meta['source_group_counts']) && is_array($meta['source_group_counts']) ? $meta['source_group_counts'] : [],
        'status_change_counts' => isset($meta['status_change_counts']) && is_array($meta['status_change_counts']) ? $meta['status_change_counts'] : [],
        'transition_to_counts' => isset($meta['transition_to_counts']) && is_array($meta['transition_to_counts']) ? $meta['transition_to_counts'] : [],
        'source_group_options' => isset($meta['source_group_options']) && is_array($meta['source_group_options']) ? $meta['source_group_options'] : ['all', 'scheduler', 'manual'],
        'failed_item_mode_options' => isset($meta['failed_item_mode_options']) && is_array($meta['failed_item_mode_options']) ? $meta['failed_item_mode_options'] : ['exact', 'contains'],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.runs.quickstats') {
    $quickstats = deployment_release_gate_runs_quickstats_snapshot($_GET);
    out_json([
        'ok' => true,
        'count' => (int) ($quickstats['count'] ?? 0),
        'applied_filters' => isset($quickstats['applied_filters']) && is_array($quickstats['applied_filters']) ? $quickstats['applied_filters'] : [],
        'summary' => isset($quickstats['summary']) && is_array($quickstats['summary']) ? $quickstats['summary'] : [],
        'sustained_state' => isset($quickstats['sustained_state']) && is_array($quickstats['sustained_state']) ? $quickstats['sustained_state'] : [],
        'sustained_timeline' => isset($quickstats['sustained_timeline']) && is_array($quickstats['sustained_timeline']) ? $quickstats['sustained_timeline'] : [],
        'sustained_transition_limit' => isset($quickstats['sustained_transition_limit']) ? (int) $quickstats['sustained_transition_limit'] : 12,
        'quick_digest' => (string) ($quickstats['quick_digest'] ?? ''),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.runs.quickstats.export') {
    $quickstats = deployment_release_gate_runs_quickstats_snapshot($_GET);
    out_json([
        'ok' => true,
        'filename' => 'release-gate-quickstats-' . gmdate('Ymd-His') . '.json',
        'quickstats' => $quickstats,
        'exported_at' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.operations.snapshot') {
    $quickstats = deployment_release_gate_runs_quickstats_snapshot($_GET);
    $meta = deployment_release_gate_runs_meta_snapshot($_GET);
    out_json([
        'ok' => true,
        'quickstats' => $quickstats,
        'meta' => $meta,
        'generated_at' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.operations.snapshot.export') {
    $quickstats = deployment_release_gate_runs_quickstats_snapshot($_GET);
    $meta = deployment_release_gate_runs_meta_snapshot($_GET);
    out_json([
        'ok' => true,
        'filename' => 'release-gate-operations-snapshot-' . gmdate('Ymd-His') . '.json',
        'snapshot' => [
            'quickstats' => $quickstats,
            'meta' => $meta,
            'generated_at' => gmdate('c'),
        ],
        'exported_at' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.runs.export') {
    $runs = app_read_json_file(deployment_release_gate_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    $filter = deployment_release_gate_runs_apply_filters($runs, $_GET);
    $transitionLimit = deployment_release_gate_sustained_transition_limit_from_query($_GET, 20);
    $items = isset($filter['items']) && is_array($filter['items']) ? $filter['items'] : [];
    $state = app_read_json_file(deployment_release_gate_state_path(), []);
    $sustainedState = deployment_release_gate_sustained_state_snapshot($state);
    $sustainedTimeline = deployment_release_gate_sustained_timeline_snapshot($items, $transitionLimit);
    $summary = deployment_release_gate_runs_summary($items);
    out_json([
        'ok' => true,
        'filename' => 'release-gate-runs-' . gmdate('Ymd-His') . '.json',
        'count' => count($items),
        'filtered_total_count' => (int) ($filter['filtered_total_count'] ?? count($items)),
        'window_total_count' => (int) ($filter['window_total_count'] ?? count($items)),
        'total_count' => (int) ($filter['total_count'] ?? count($runs)),
        'applied_filters' => isset($filter['applied_filters']) && is_array($filter['applied_filters']) ? $filter['applied_filters'] : [],
        'summary' => $summary,
        'state' => is_array($state) ? $state : [],
        'sustained_state' => $sustainedState,
        'sustained_timeline' => $sustainedTimeline,
        'sustained_transition_limit' => $transitionLimit,
        'items' => $items,
        'exported_at' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.blockers.report') {
    $runs = app_read_json_file(deployment_release_gate_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    $filter = deployment_release_gate_runs_apply_filters($runs, $_GET);
    $transitionLimit = deployment_release_gate_sustained_transition_limit_from_query($_GET, 20);
    $items = isset($filter['items']) && is_array($filter['items']) ? $filter['items'] : [];
    $summary = deployment_release_gate_runs_summary($items);
    $freshness = isset($_GET['freshness_minutes']) ? (int) $_GET['freshness_minutes'] : null;
    $gate = deployment_release_gate_snapshot($freshness);
    $state = app_read_json_file(deployment_release_gate_state_path(), []);
    if (!is_array($state)) {
        $state = [];
    }
    $sustainedState = deployment_release_gate_sustained_state_snapshot($state);
    $sustainedTimeline = deployment_release_gate_sustained_timeline_snapshot($items, $transitionLimit);
    $report = [
        'report_id' => 'release_gate_blockers_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'generated_at' => gmdate('c'),
        'release_gate' => $gate,
        'release_gate_state' => $state,
        'release_gate_sustained_state' => $sustainedState,
        'release_gate_sustained_timeline' => $sustainedTimeline,
        'release_gate_sustained_transition_limit' => $transitionLimit,
        'runs_summary' => $summary,
        'runs_filters' => isset($filter['applied_filters']) && is_array($filter['applied_filters']) ? $filter['applied_filters'] : [],
        'runs_count' => count($items),
        'runs_filtered_total_count' => (int) ($filter['filtered_total_count'] ?? count($items)),
        'runs_window_total_count' => (int) ($filter['window_total_count'] ?? count($items)),
        'runs_total_count' => (int) ($filter['total_count'] ?? count($runs)),
        'runs' => $items,
    ];
    out_json([
        'ok' => true,
        'filename' => 'release-gate-blockers-report-' . gmdate('Ymd-His') . '.json',
        'report' => $report,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.status') {
    $freshness = isset($_GET['freshness_minutes']) ? (int) $_GET['freshness_minutes'] : null;
    $watchdogs = deployment_watchdogs_status_snapshot($freshness);
    out_json([
        'ok' => true,
        'watchdogs' => $watchdogs,
        'time' => gmdate('c'),
    ], ((string) ($watchdogs['status'] ?? 'ok') === 'critical') ? 409 : 200);
}

if ($action === 'deployment.watchdogs.check') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $source = isset($data['source']) ? (string) $data['source'] : 'dashboard_manual';
    $freshness = isset($data['freshness_minutes']) ? (int) $data['freshness_minutes'] : null;
    $snap = deployment_watchdogs_check_snapshot($source, $freshness);
    audit_event('deployment', 'watchdogs.check', [
        'run_id' => (string) ($snap['run']['run_id'] ?? ''),
        'status' => (string) ($snap['run']['status'] ?? ''),
        'source' => (string) ($snap['run']['source'] ?? ''),
        'status_changed' => (int) ($snap['run']['status_changed'] ?? 0),
        'alert_sent' => (int) ($snap['run']['alert_sent'] ?? 0),
    ]);
    out_json([
        'ok' => true,
        'check' => $snap,
        'time' => gmdate('c'),
    ], ((string) ($snap['run']['status'] ?? 'ok') === 'critical') ? 409 : 200);
}

if ($action === 'deployment.watchdogs.runs') {
    $runs = app_read_json_file(deployment_watchdogs_runs_path(), []);
    $state = app_read_json_file(deployment_watchdogs_state_path(), []);
    out_json([
        'ok' => true,
        'count' => is_array($runs) ? count($runs) : 0,
        'items' => is_array($runs) ? $runs : [],
        'state' => is_array($state) ? $state : [],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.get') {
    $settings = deployment_watchdogs_policy_settings_from_guard();
    out_json([
        'ok' => true,
        'settings' => $settings,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.history') {
    $rows = deployment_watchdogs_policy_history_rows();
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.preview') {
    $historyId = trim((string) ($_GET['history_id'] ?? ''));
    $mode = strtolower(trim((string) ($_GET['mode'] ?? 'previous')));
    if (!in_array($mode, ['previous', 'current'], true)) {
        $mode = 'previous';
    }
    $rows = deployment_watchdogs_policy_history_rows();
    if (empty($rows)) {
        out_json(['ok' => false, 'error' => 'No watchdog policy history available.'], 404);
    }
    $target = deployment_watchdogs_policy_history_select($rows, $historyId);
    if (!is_array($target)) {
        out_json(['ok' => false, 'error' => 'History entry not found.'], 404);
    }
    $candidateRaw = isset($target[$mode]) && is_array($target[$mode]) ? $target[$mode] : [];
    $current = deployment_watchdogs_policy_settings_from_guard();
    $candidate = deployment_watchdogs_policy_settings_from_guard($candidateRaw);
    $delta = deployment_watchdogs_policy_diff_summary($current, $candidate);
    out_json([
        'ok' => true,
        'mode' => $mode,
        'target' => [
            'history_id' => (string) ($target['history_id'] ?? ''),
            'created_at' => (string) ($target['created_at'] ?? ''),
            'source' => (string) ($target['source'] ?? ''),
            'actor' => isset($target['actor']) && is_array($target['actor']) ? $target['actor'] : [],
        ],
        'current' => $delta['current'],
        'candidate' => $delta['candidate'],
        'delta' => $delta['changes'],
        'changed_count' => (int) ($delta['changed_count'] ?? 0),
        'has_changes' => !empty($delta['has_changes']) ? 1 : 0,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.baseline.get') {
    $snapshot = deployment_watchdogs_policy_baseline_snapshot();
    out_json([
        'ok' => true,
        'has_baseline' => !empty($snapshot['has_baseline']) ? 1 : 0,
        'baseline' => $snapshot['baseline'],
        'current' => isset($snapshot['current']) && is_array($snapshot['current']) ? $snapshot['current'] : deployment_watchdogs_policy_settings_from_guard(),
        'delta' => isset($snapshot['delta']) && is_array($snapshot['delta']) ? $snapshot['delta'] : [],
        'changed_count' => (int) ($snapshot['changed_count'] ?? 0),
        'has_changes' => !empty($snapshot['has_changes']) ? 1 : 0,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.baseline.set') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $historyId = trim((string) ($data['history_id'] ?? ''));
    $mode = strtolower(trim((string) ($data['mode'] ?? 'previous')));
    if (!in_array($mode, ['previous', 'current'], true)) {
        $mode = 'previous';
    }
    $baselineSettings = deployment_watchdogs_policy_settings_from_guard();
    $resolvedHistoryId = '';
    if ($historyId !== '') {
        $rows = deployment_watchdogs_policy_history_rows();
        if (empty($rows)) {
            out_json(['ok' => false, 'error' => 'No watchdog policy history available.'], 404);
        }
        $target = deployment_watchdogs_policy_history_select($rows, $historyId);
        if (!is_array($target)) {
            out_json(['ok' => false, 'error' => 'History entry not found.'], 404);
        }
        $candidateRaw = isset($target[$mode]) && is_array($target[$mode]) ? $target[$mode] : [];
        $baselineSettings = deployment_watchdogs_policy_settings_from_guard($candidateRaw);
        $resolvedHistoryId = (string) ($target['history_id'] ?? '');
    }
    $source = isset($data['source']) ? (string) $data['source'] : 'dashboard_baseline';
    $user = app_current_user();
    $payload = [
        'settings' => $baselineSettings,
        'set_at' => gmdate('c'),
        'source' => $source,
        'history_id' => $resolvedHistoryId,
        'mode' => ($resolvedHistoryId !== '') ? $mode : '',
        'actor' => [
            'email' => isset($user['email']) ? (string) $user['email'] : '',
            'role' => isset($user['role']) ? (string) $user['role'] : '',
        ],
    ];
    app_write_json_file(deployment_watchdogs_policy_baseline_path(), $payload);
    $snapshot = deployment_watchdogs_policy_baseline_snapshot($payload);
    audit_event('deployment', 'watchdogs.policy.baseline.set', [
        'source' => $source,
        'history_id' => $resolvedHistoryId,
        'mode' => ($resolvedHistoryId !== '') ? $mode : '',
        'baseline' => $baselineSettings,
    ]);
    push_notification('info', 'Watchdogs policy baseline saved.', [
        'source' => $source,
        'history_id' => $resolvedHistoryId,
        'mode' => ($resolvedHistoryId !== '') ? $mode : '',
        'has_changes' => !empty($snapshot['has_changes']) ? 1 : 0,
    ]);
    $baselineCheck = deployment_watchdogs_policy_baseline_check_snapshot('policy_baseline_set');
    out_json([
        'ok' => true,
        'has_baseline' => !empty($snapshot['has_baseline']) ? 1 : 0,
        'baseline' => $snapshot['baseline'],
        'current' => isset($snapshot['current']) && is_array($snapshot['current']) ? $snapshot['current'] : deployment_watchdogs_policy_settings_from_guard(),
        'delta' => isset($snapshot['delta']) && is_array($snapshot['delta']) ? $snapshot['delta'] : [],
        'changed_count' => (int) ($snapshot['changed_count'] ?? 0),
        'has_changes' => !empty($snapshot['has_changes']) ? 1 : 0,
        'baseline_check' => $baselineCheck,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.baseline.clear') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $source = isset($data['source']) ? (string) $data['source'] : 'dashboard_baseline_clear';
    app_write_json_file(deployment_watchdogs_policy_baseline_path(), []);
    $snapshot = deployment_watchdogs_policy_baseline_snapshot([]);
    audit_event('deployment', 'watchdogs.policy.baseline.clear', [
        'source' => $source,
    ]);
    push_notification('warning', 'Watchdogs policy baseline cleared.', [
        'source' => $source,
    ]);
    $baselineCheck = deployment_watchdogs_policy_baseline_check_snapshot('policy_baseline_clear');
    out_json([
        'ok' => true,
        'has_baseline' => !empty($snapshot['has_baseline']) ? 1 : 0,
        'baseline' => $snapshot['baseline'],
        'current' => isset($snapshot['current']) && is_array($snapshot['current']) ? $snapshot['current'] : deployment_watchdogs_policy_settings_from_guard(),
        'delta' => isset($snapshot['delta']) && is_array($snapshot['delta']) ? $snapshot['delta'] : [],
        'changed_count' => (int) ($snapshot['changed_count'] ?? 0),
        'has_changes' => !empty($snapshot['has_changes']) ? 1 : 0,
        'baseline_check' => $baselineCheck,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.baseline.runs') {
    $runs = app_read_json_file(deployment_watchdogs_policy_baseline_runs_path(), []);
    $state = app_read_json_file(deployment_watchdogs_policy_baseline_state_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    if (!is_array($state)) {
        $state = [];
    }
    out_json([
        'ok' => true,
        'count' => count($runs),
        'items' => $runs,
        'state' => $state,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.baseline.check') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $source = isset($data['source']) ? (string) $data['source'] : 'dashboard_manual';
    $result = deployment_watchdogs_policy_baseline_check_snapshot($source);
    out_json([
        'ok' => true,
        'check' => $result,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.save') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $guardBefore = get_deployment_guard();
    $previous = deployment_watchdogs_policy_settings_from_guard($guardBefore);
    $settings = [
        'watchdogs_auto_incident_threshold' => max(1, min(10, (int) ($data['auto_incident_threshold'] ?? 2))),
        'watchdogs_auto_resolve_ok_streak' => max(1, min(10, (int) ($data['auto_resolve_ok_streak'] ?? 2))),
    ];
    $saved = save_deployment_guard($settings);
    $response = deployment_watchdogs_policy_settings_from_guard($saved);
    $source = isset($data['source']) ? (string) $data['source'] : 'dashboard_manual';
    $history = deployment_watchdogs_policy_history_append($previous, $response, $source);
    audit_event('deployment', 'watchdogs.policy.save', [
        'previous' => $previous,
        'current' => $response,
        'history_id' => (string) ($history['history_id'] ?? ''),
        'source' => (string) ($history['source'] ?? ''),
    ]);
    push_notification('info', 'Watchdogs policy settings updated.', $response);
    $baselineCheck = deployment_watchdogs_policy_baseline_check_snapshot('policy_save');
    out_json([
        'ok' => true,
        'settings' => $response,
        'history' => $history,
        'baseline_check' => $baselineCheck,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.policy.restore') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $historyId = trim((string) ($data['history_id'] ?? ''));
    $mode = strtolower(trim((string) ($data['mode'] ?? 'previous')));
    if (!in_array($mode, ['previous', 'current'], true)) {
        $mode = 'previous';
    }
    $rows = deployment_watchdogs_policy_history_rows();
    if (empty($rows)) {
        out_json(['ok' => false, 'error' => 'No watchdog policy history available.'], 404);
    }
    $target = deployment_watchdogs_policy_history_select($rows, $historyId);
    if (!is_array($target)) {
        out_json(['ok' => false, 'error' => 'History entry not found.'], 404);
    }
    $candidateRaw = isset($target[$mode]) && is_array($target[$mode]) ? $target[$mode] : [];
    $current = deployment_watchdogs_policy_settings_from_guard();
    $candidate = deployment_watchdogs_policy_settings_from_guard($candidateRaw);
    $delta = deployment_watchdogs_policy_diff_summary($current, $candidate);
    if (empty($delta['has_changes'])) {
        $baselineCheck = deployment_watchdogs_policy_baseline_check_snapshot('policy_restore_no_change');
        out_json([
            'ok' => true,
            'no_change' => 1,
            'message' => 'Selected policy snapshot already matches current settings.',
            'settings' => $current,
            'restored_from' => [
                'history_id' => (string) ($target['history_id'] ?? ''),
                'mode' => $mode,
            ],
            'delta' => isset($delta['changes']) && is_array($delta['changes']) ? $delta['changes'] : [],
            'baseline_check' => $baselineCheck,
            'time' => gmdate('c'),
        ]);
    }
    $settings = [
        'watchdogs_auto_incident_threshold' => max(1, min(10, (int) ($candidate['auto_incident_threshold'] ?? 2))),
        'watchdogs_auto_resolve_ok_streak' => max(1, min(10, (int) ($candidate['auto_resolve_ok_streak'] ?? 2))),
    ];
    $previous = $current;
    $saved = save_deployment_guard($settings);
    $response = deployment_watchdogs_policy_settings_from_guard($saved);
    $source = isset($data['source']) ? (string) $data['source'] : ('dashboard_restore_' . $mode);
    $history = deployment_watchdogs_policy_history_append($previous, $response, $source);
    audit_event('deployment', 'watchdogs.policy.restore', [
        'restored_from_history_id' => (string) ($target['history_id'] ?? ''),
        'mode' => $mode,
        'previous' => $previous,
        'current' => $response,
        'delta' => isset($delta['changes']) && is_array($delta['changes']) ? $delta['changes'] : [],
        'history_id' => (string) ($history['history_id'] ?? ''),
        'source' => (string) ($history['source'] ?? ''),
    ]);
    push_notification('info', 'Watchdogs policy restored from history.', [
        'restored_from_history_id' => (string) ($target['history_id'] ?? ''),
        'mode' => $mode,
    ]);
    $baselineCheck = deployment_watchdogs_policy_baseline_check_snapshot('policy_restore');
    out_json([
        'ok' => true,
        'settings' => $response,
        'history' => $history,
        'restored_from' => [
            'history_id' => (string) ($target['history_id'] ?? ''),
            'mode' => $mode,
        ],
        'delta' => isset($delta['changes']) && is_array($delta['changes']) ? $delta['changes'] : [],
        'baseline_check' => $baselineCheck,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.incident.open') {
    $open = deployment_find_open_watchdogs_incident();
    out_json([
        'ok' => true,
        'has_open_incident' => is_array($open),
        'incident' => is_array($open) ? $open : null,
        'summary' => deployment_watchdogs_incident_summary_snapshot(),
        'time' => gmdate('c'),
    ], is_array($open) ? 200 : 404);
}

if ($action === 'deployment.watchdogs.incident.latest') {
    $latest = deployment_find_latest_watchdogs_incident();
    out_json([
        'ok' => true,
        'has_watchdogs_incident' => is_array($latest),
        'incident' => is_array($latest) ? $latest : null,
        'summary' => deployment_watchdogs_incident_summary_snapshot(),
        'time' => gmdate('c'),
    ], is_array($latest) ? 200 : 404);
}

if ($action === 'deployment.watchdogs.incident.summary') {
    $summary = deployment_watchdogs_incident_summary_snapshot();
    out_json([
        'ok' => true,
        'summary' => $summary,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.incident.resolve') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $open = deployment_find_open_watchdogs_incident();
    if (!is_array($open)) {
        out_json(['ok' => false, 'error' => 'No open watchdog incident found.'], 404);
    }
    $note = trim((string) ($data['note'] ?? ''));
    if ($note === '') {
        $note = 'Resolved via Go-Live watchdog incident quick action.';
    }
    $reportId = (string) ($open['report_id'] ?? '');
    $updated = update_incident_report_status($reportId, 'resolved', $note);
    if (!is_array($updated)) {
        out_json(['ok' => false, 'error' => 'Failed to resolve watchdog incident.'], 500);
    }
    audit_event('deployment', 'watchdogs.incident.resolve', [
        'report_id' => (string) ($updated['report_id'] ?? $reportId),
        'origin' => (string) ($updated['incident_origin'] ?? ''),
    ]);
    push_notification('success', 'Watchdog incident resolved: ' . (string) ($updated['report_id'] ?? $reportId), [
        'report_id' => (string) ($updated['report_id'] ?? $reportId),
        'origin' => 'watchdogs_check',
    ]);
    out_json([
        'ok' => true,
        'incident' => $updated,
        'summary' => deployment_incident_summary_snapshot(),
        'watchdogs_summary' => deployment_watchdogs_incident_summary_snapshot(),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.watchdogs.incident.reopen') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $open = deployment_find_open_watchdogs_incident();
    if (is_array($open)) {
        out_json([
            'ok' => false,
            'error' => 'An open watchdog incident already exists.',
            'incident' => $open,
            'time' => gmdate('c'),
        ], 409);
    }
    $resolved = deployment_find_latest_watchdogs_incident(['resolved']);
    if (!is_array($resolved)) {
        out_json(['ok' => false, 'error' => 'No resolved watchdog incident found.'], 404);
    }
    $reportId = (string) ($resolved['report_id'] ?? '');
    $note = trim((string) ($data['note'] ?? ''));
    if ($note === '') {
        $note = 'Reopened via Go-Live watchdog incident quick action.';
    }
    $updated = update_incident_report_status($reportId, 'reopened', $note);
    if (!is_array($updated)) {
        out_json(['ok' => false, 'error' => 'Failed to reopen watchdog incident.'], 500);
    }
    audit_event('deployment', 'watchdogs.incident.reopen', [
        'report_id' => (string) ($updated['report_id'] ?? $reportId),
        'origin' => (string) ($updated['incident_origin'] ?? ''),
    ]);
    push_notification('warning', 'Watchdog incident reopened: ' . (string) ($updated['report_id'] ?? $reportId), [
        'report_id' => (string) ($updated['report_id'] ?? $reportId),
        'origin' => 'watchdogs_check',
    ]);
    out_json([
        'ok' => true,
        'incident' => $updated,
        'summary' => deployment_incident_summary_snapshot(),
        'watchdogs_summary' => deployment_watchdogs_incident_summary_snapshot(),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.log') {
    $rows = app_read_json_file(deployment_release_log_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.pipeline.runs') {
    $rows = app_read_json_file(deployment_pipeline_runs_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.pipeline.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $note = trim((string) ($data['note'] ?? ''));
    $snap = deployment_pipeline_run_snapshot($note);
    save_deployment_guard([
        'last_install_check' => [
            'status' => (string) ($snap['install']['status'] ?? 'warning'),
            'time' => (string) ($snap['install']['time'] ?? gmdate('c')),
        ],
        'last_preflight' => [
            'status' => (string) ($snap['preflight']['status'] ?? 'warning'),
            'time' => (string) ($snap['preflight']['time'] ?? gmdate('c')),
        ],
        'last_verify' => [
            'status' => (string) ($snap['verify']['status'] ?? 'warning'),
            'time' => (string) ($snap['verify']['time'] ?? gmdate('c')),
        ],
    ]);
    $run = $snap['run'];
    if ((string) ($run['status'] ?? 'blocked') === 'ready') {
        push_notification('success', 'Cutover pipeline passed: ' . (string) ($run['run_id'] ?? ''), ['run' => $run]);
    } else {
        push_notification('critical', 'Cutover pipeline blocked: ' . (string) ($run['run_id'] ?? ''), ['run' => $run]);
    }
    audit_event('deployment', 'pipeline.run', [
        'run_id' => (string) ($run['run_id'] ?? ''),
        'status' => (string) ($run['status'] ?? 'blocked'),
        'summary' => $run['summary'] ?? [],
    ]);
    out_json([
        'ok' => ((string) ($run['status'] ?? 'blocked') === 'ready'),
        'pipeline' => $snap,
    ], ((string) ($run['status'] ?? 'blocked') === 'ready') ? 200 : 409);
}

if ($action === 'deployment.release.candidate') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $note = trim((string) ($data['note'] ?? ''));
    $freshness = isset($data['freshness_minutes']) ? (int) $data['freshness_minutes'] : null;
    $snap = deployment_release_candidate_snapshot($note, $freshness);
    $candidate = $snap['candidate'];
    $bundle = $snap['bundle'];
    $gate = isset($snap['release_gate']) && is_array($snap['release_gate']) ? $snap['release_gate'] : [];

    if ((string) ($candidate['status'] ?? 'blocked') === 'ready') {
        push_notification('success', 'Release candidate ready: ' . (string) ($candidate['candidate_id'] ?? ''), [
            'candidate' => $candidate,
        ]);
    } else {
        push_notification('critical', 'Release candidate blocked: ' . (string) ($candidate['candidate_id'] ?? ''), [
            'candidate' => $candidate,
        ]);
    }
    audit_event('deployment', 'release.candidate', [
        'candidate_id' => (string) ($candidate['candidate_id'] ?? ''),
        'status' => (string) ($candidate['status'] ?? 'blocked'),
        'bundle_id' => (string) ($candidate['bundle_id'] ?? ''),
        'release_gate_allowed' => !empty($gate['allowed']) ? 1 : 0,
    ]);
    out_json([
        'ok' => true,
        'candidate' => $candidate,
        'bundle' => $bundle,
        'release_gate' => $gate,
    ], ((string) ($candidate['status'] ?? 'blocked') === 'ready') ? 200 : 409);
}

if ($action === 'deployment.artifact.manifest') {
    $manifest = deployment_artifact_manifest_snapshot();
    audit_event('deployment', 'artifact.manifest.export', ['manifest_id' => (string) $manifest['manifest_id']]);
    out_json([
        'ok' => true,
        'manifest' => $manifest,
    ]);
}

if ($action === 'deployment.artifact.verify') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || !isset($data['baseline'])) {
        out_json(['ok' => false, 'error' => 'baseline manifest is required.'], 400);
    }
    $result = deployment_artifact_verify_snapshot($data['baseline']);
    audit_event('deployment', 'artifact.verify', [
        'verify_id' => (string) ($result['verify_id'] ?? ''),
        'status' => (string) ($result['status'] ?? 'mismatch'),
        'mismatch_count' => (int) ($result['summary']['mismatch_count'] ?? 0),
        'missing_on_server_count' => (int) ($result['summary']['missing_on_server_count'] ?? 0),
    ]);
    if (!empty($result['ok'])) {
        push_notification('success', 'Artifact verification matched baseline.', ['verify_id' => (string) ($result['verify_id'] ?? '')]);
    } else {
        push_notification('critical', 'Artifact verification mismatch detected.', ['verify_id' => (string) ($result['verify_id'] ?? '')]);
    }
    out_json([
        'ok' => !empty($result['ok']),
        'verify' => $result,
    ], !empty($result['ok']) ? 200 : 409);
}

if ($action === 'deployment.verify') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $snap = deployment_verify_snapshot();
    save_deployment_guard([
        'last_verify' => [
            'status' => (string) ($snap['status'] ?? 'warning'),
            'time' => (string) ($snap['time'] ?? gmdate('c')),
        ],
    ]);
    audit_event('deployment', 'verify.run', ['status' => (string) $snap['status'], 'summary' => $snap['summary']]);
    out_json([
        'ok' => $snap['status'] !== 'critical',
        'status' => $snap['status'],
        'checks' => $snap['checks'],
        'summary' => $snap['summary'],
        'preflight' => $snap['preflight'],
        'time' => $snap['time'],
    ], ($snap['status'] === 'critical') ? 503 : 200);
}

if ($action === 'deployment.guard.status') {
    $eval = deployment_guard_evaluate();
    out_json([
        'ok' => true,
        'allowed' => !empty($eval['allowed']),
        'reasons' => $eval['reasons'],
        'bypass_active' => !empty($eval['bypass_active']) ? 1 : 0,
        'bypass' => $eval['bypass'] ?? [],
        'guard' => $eval['guard'],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.guard.save') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $candidate = deployment_guard_from_payload($data);
    $saved = save_deployment_guard($candidate);
    $eval = deployment_guard_evaluate();
    audit_event('deployment', 'guard.save', [
        'enforced' => (int) $saved['enforced'],
        'launch_window_enabled' => (int) $saved['launch_window_enabled'],
        'launch_window_start' => (string) $saved['launch_window_start'],
        'launch_window_end' => (string) $saved['launch_window_end'],
        'checklist' => $saved['checklist'],
    ]);
    out_json([
        'ok' => true,
        'allowed' => !empty($eval['allowed']),
        'reasons' => $eval['reasons'],
        'bypass_active' => !empty($eval['bypass_active']) ? 1 : 0,
        'bypass' => $eval['bypass'] ?? [],
        'guard' => $saved,
    ]);
}

if ($action === 'deployment.guard.preview') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $candidate = deployment_guard_from_payload($data);
    $eval = deployment_guard_evaluate(true, $candidate);
    out_json([
        'ok' => true,
        'allowed' => !empty($eval['allowed']),
        'reasons' => $eval['reasons'],
        'bypass_active' => !empty($eval['bypass_active']) ? 1 : 0,
        'bypass' => $eval['bypass'] ?? [],
        'guard' => $candidate,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.guard.bypass.enable') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $reason = trim((string) ($data['reason'] ?? ''));
    $duration = (int) ($data['duration_minutes'] ?? 30);
    if (strlen($reason) < 8) {
        out_json(['ok' => false, 'error' => 'Bypass reason must be at least 8 characters.'], 400);
    }
    $duration = max(5, min(240, $duration));
    $expiresAt = gmdate('Y-m-d\TH:i', time() + ($duration * 60));
    $saved = save_deployment_guard([
        'emergency_bypass_enabled' => 1,
        'emergency_bypass_expires_at' => $expiresAt,
        'emergency_bypass_reason' => $reason,
        'emergency_bypass_set_by' => current_actor(),
        'emergency_bypass_last_alert_at' => '',
    ]);
    append_bypass_log_event('enable', [
        'duration_minutes' => $duration,
        'expires_at' => $expiresAt,
        'reason' => $reason,
        'set_by' => current_actor(),
    ]);
    audit_event('deployment', 'guard.bypass.enable', [
        'duration_minutes' => $duration,
        'expires_at' => $expiresAt,
        'reason' => $reason,
    ]);
    push_notification('critical', 'Emergency bypass enabled until ' . $expiresAt . ' UTC.', [
        'duration_minutes' => $duration,
        'reason' => $reason,
    ]);
    out_json([
        'ok' => true,
        'message' => 'Emergency bypass enabled.',
        'guard' => $saved,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.guard.bypass.extend') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $guard = get_deployment_guard();
    $status = deployment_guard_bypass_status($guard);
    if (empty($guard['emergency_bypass_enabled']) || empty($status['active'])) {
        out_json(['ok' => false, 'error' => 'Active emergency bypass is required to extend.'], 400);
    }
    $duration = (int) ($data['duration_minutes'] ?? 30);
    $duration = max(5, min(240, $duration));
    $reasonRaw = trim((string) ($data['reason'] ?? ''));
    $reason = $reasonRaw !== '' ? $reasonRaw : (string) ($guard['emergency_bypass_reason'] ?? '');
    if (strlen($reason) < 8) {
        out_json(['ok' => false, 'error' => 'Bypass reason must be at least 8 characters.'], 400);
    }
    $expiresAt = gmdate('Y-m-d\TH:i', time() + ($duration * 60));
    $saved = save_deployment_guard([
        'emergency_bypass_enabled' => 1,
        'emergency_bypass_expires_at' => $expiresAt,
        'emergency_bypass_reason' => $reason,
        'emergency_bypass_set_by' => current_actor(),
        'emergency_bypass_last_alert_at' => '',
    ]);
    append_bypass_log_event('extend', [
        'duration_minutes' => $duration,
        'expires_at' => $expiresAt,
        'reason' => $reason,
        'set_by' => current_actor(),
    ]);
    audit_event('deployment', 'guard.bypass.extend', [
        'duration_minutes' => $duration,
        'expires_at' => $expiresAt,
        'reason' => $reason,
    ]);
    push_notification('critical', 'Emergency bypass extended until ' . $expiresAt . ' UTC.', [
        'duration_minutes' => $duration,
        'reason' => $reason,
    ]);
    out_json([
        'ok' => true,
        'message' => 'Emergency bypass extended.',
        'guard' => $saved,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.guard.bypass.disable') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $saved = save_deployment_guard([
        'emergency_bypass_enabled' => 0,
        'emergency_bypass_expires_at' => '',
        'emergency_bypass_reason' => '',
        'emergency_bypass_set_by' => '',
        'emergency_bypass_last_alert_at' => '',
    ]);
    append_bypass_log_event('disable', []);
    audit_event('deployment', 'guard.bypass.disable', []);
    push_notification('info', 'Emergency bypass disabled.', []);
    out_json([
        'ok' => true,
        'message' => 'Emergency bypass disabled.',
        'guard' => $saved,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.guard.bypass.log') {
    $rows = app_read_json_file(deployment_bypass_log_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.incident.report') {
    $note = isset($_GET['note']) ? (string) $_GET['note'] : '';
    $report = deployment_incident_report_snapshot($note);
    save_incident_report($report);
    audit_event('deployment', 'incident.report.export', [
        'report_id' => (string) ($report['report_id'] ?? ''),
        'guard_allowed' => (int) ($report['summary']['guard_allowed'] ?? 0),
        'go_live_status' => (string) ($report['summary']['go_live_status'] ?? 'review_required'),
    ]);
    out_json([
        'ok' => true,
        'report' => $report,
    ]);
}

if ($action === 'deployment.incident.reports') {
    $rows = app_read_json_file(deployment_incident_reports_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.incident.summary') {
    $summary = deployment_incident_summary_snapshot();
    out_json([
        'ok' => true,
        'summary' => $summary,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.incident.sla') {
    $threshold = isset($_GET['threshold_minutes']) ? (int) $_GET['threshold_minutes'] : 120;
    $sla = deployment_incident_sla_snapshot($threshold);
    out_json([
        'ok' => true,
        'sla' => $sla,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.incident.sla.check') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $threshold = isset($data['threshold_minutes']) ? (int) $data['threshold_minutes'] : 120;
    $cooldown = isset($data['cooldown_minutes']) ? (int) $data['cooldown_minutes'] : 30;
    $snap = deployment_incident_sla_check_snapshot($threshold, $cooldown);
    audit_event('deployment', 'incident.sla.check', [
        'run_id' => (string) ($snap['run']['run_id'] ?? ''),
        'breach_count' => (int) ($snap['run']['breach_count'] ?? 0),
        'alert_sent' => (int) ($snap['run']['alert_sent'] ?? 0),
        'threshold_minutes' => (int) ($snap['run']['threshold_minutes'] ?? 0),
        'cooldown_minutes' => (int) ($snap['run']['cooldown_minutes'] ?? 0),
    ]);
    out_json([
        'ok' => true,
        'check' => $snap,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.incident.sla.runs') {
    $rows = app_read_json_file(deployment_incident_sla_runs_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.smoke.report') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $smokeType = isset($data['smoke_type']) ? (string) $data['smoke_type'] : 'public';
    $ok = !empty($data['ok']) ? 1 : 0;
    $source = isset($data['source']) ? (string) $data['source'] : 'dashboard_manual';
    $note = isset($data['note']) ? (string) $data['note'] : '';
    $details = isset($data['details']) && is_array($data['details']) ? $data['details'] : [];
    $run = deployment_smoke_record_snapshot($smokeType, $ok, $source, $note, $details);
    $msg = ((int) ($run['ok'] ?? 0) === 1 ? 'Smoke check passed: ' : 'Smoke check failed: ') . (string) ($run['smoke_type'] ?? 'unknown');
    push_notification(((int) ($run['ok'] ?? 0) === 1) ? 'success' : 'warning', $msg, [
        'run_id' => (string) ($run['run_id'] ?? ''),
        'smoke_type' => (string) ($run['smoke_type'] ?? ''),
        'source' => (string) ($run['source'] ?? ''),
    ]);
    audit_event('deployment', 'smoke.report', [
        'run_id' => (string) ($run['run_id'] ?? ''),
        'smoke_type' => (string) ($run['smoke_type'] ?? ''),
        'ok' => (int) ($run['ok'] ?? 0),
        'source' => (string) ($run['source'] ?? ''),
    ]);
    out_json([
        'ok' => true,
        'run' => $run,
        'readiness' => deployment_cutover_readiness_snapshot(),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.smoke.suite') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $note = isset($data['note']) ? (string) $data['note'] : '';
    $suite = deployment_smoke_suite_snapshot($note);
    $overallOk = !empty($suite['overall_ok']);
    push_notification($overallOk ? 'success' : 'warning', $overallOk ? 'Smoke suite passed.' : 'Smoke suite failed.', [
        'suite_id' => (string) ($suite['suite_id'] ?? ''),
        'overall_ok' => $overallOk ? 1 : 0,
    ]);
    audit_event('deployment', 'smoke.suite.run', [
        'suite_id' => (string) ($suite['suite_id'] ?? ''),
        'overall_ok' => $overallOk ? 1 : 0,
    ]);
    out_json([
        'ok' => true,
        'suite' => $suite,
        'history' => deployment_smoke_history_snapshot(),
        'readiness' => deployment_cutover_readiness_snapshot(),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.smoke.history') {
    $history = deployment_smoke_history_snapshot();
    out_json([
        'ok' => true,
        'history' => $history,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.evidence.bundle') {
    $note = isset($_GET['note']) ? (string) $_GET['note'] : '';
    $bundle = deployment_cutover_evidence_bundle_snapshot($note);
    audit_event('deployment', 'cutover.evidence.bundle.export', [
        'bundle_id' => (string) ($bundle['bundle_id'] ?? ''),
        'readiness_status' => (string) ($bundle['summary']['readiness_status'] ?? 'review_required'),
        'release_gate_allowed' => (int) ($bundle['summary']['release_gate_allowed'] ?? 0),
    ]);
    out_json([
        'ok' => true,
        'bundle' => $bundle,
    ]);
}

if ($action === 'deployment.cutover.signoff.create') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $note = isset($data['note']) ? (string) $data['note'] : '';
    $snap = deployment_cutover_signoff_create_snapshot($note);
    if (empty($snap['ok'])) {
        audit_event('deployment', 'cutover.signoff.blocked', [
            'reasons' => isset($snap['reasons']) && is_array($snap['reasons']) ? $snap['reasons'] : [],
        ]);
        push_notification('critical', 'Cutover signoff blocked by release gate.', [
            'reasons' => isset($snap['reasons']) && is_array($snap['reasons']) ? $snap['reasons'] : [],
        ]);
        out_json([
            'ok' => false,
            'error' => (string) ($snap['error'] ?? 'Cutover signoff blocked.'),
            'reasons' => isset($snap['reasons']) && is_array($snap['reasons']) ? $snap['reasons'] : [],
            'evidence_bundle' => isset($snap['evidence_bundle']) && is_array($snap['evidence_bundle']) ? $snap['evidence_bundle'] : [],
        ], 409);
    }
    $signoff = isset($snap['signoff']) && is_array($snap['signoff']) ? $snap['signoff'] : [];
    audit_event('deployment', 'cutover.signoff.create', [
        'signoff_id' => (string) ($signoff['signoff_id'] ?? ''),
        'evidence_bundle_id' => (string) ($signoff['evidence_bundle_id'] ?? ''),
        'status' => (string) ($signoff['status'] ?? 'approved'),
    ]);
    push_notification('success', 'Cutover signoff created: ' . (string) ($signoff['signoff_id'] ?? ''), [
        'signoff_id' => (string) ($signoff['signoff_id'] ?? ''),
        'evidence_bundle_id' => (string) ($signoff['evidence_bundle_id'] ?? ''),
    ]);
    out_json([
        'ok' => true,
        'signoff' => $signoff,
        'evidence_bundle' => isset($snap['evidence_bundle']) && is_array($snap['evidence_bundle']) ? $snap['evidence_bundle'] : [],
        'latest' => deployment_cutover_signoff_latest_snapshot(),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.signoff.list') {
    $rows = deployment_cutover_signoff_list_snapshot();
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
        'latest' => deployment_cutover_signoff_latest_snapshot(),
        'active' => deployment_cutover_signoff_active_snapshot(),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.signoff.latest') {
    out_json([
        'ok' => true,
        'latest' => deployment_cutover_signoff_latest_snapshot(),
        'active' => deployment_cutover_signoff_active_snapshot(),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.signoff.active') {
    out_json([
        'ok' => true,
        'active' => deployment_cutover_signoff_active_snapshot(),
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.signoff.activate') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $signoffId = isset($data['signoff_id']) ? (string) $data['signoff_id'] : '';
    $snap = deployment_cutover_signoff_update_snapshot($signoffId, 'activate', '');
    if (empty($snap['ok'])) {
        out_json([
            'ok' => false,
            'error' => (string) ($snap['error'] ?? 'Signoff activate failed.'),
        ], 400);
    }
    audit_event('deployment', 'cutover.signoff.activate', [
        'signoff_id' => (string) ($snap['item']['signoff_id'] ?? ''),
    ]);
    push_notification('info', 'Cutover signoff activated: ' . (string) ($snap['item']['signoff_id'] ?? ''), [
        'signoff_id' => (string) ($snap['item']['signoff_id'] ?? ''),
    ]);
    out_json([
        'ok' => true,
        'item' => $snap['item'],
        'active' => $snap['active'],
        'latest' => $snap['latest'],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.signoff.revoke') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $signoffId = isset($data['signoff_id']) ? (string) $data['signoff_id'] : '';
    $reason = isset($data['reason']) ? (string) $data['reason'] : '';
    $snap = deployment_cutover_signoff_update_snapshot($signoffId, 'revoke', $reason);
    if (empty($snap['ok'])) {
        out_json([
            'ok' => false,
            'error' => (string) ($snap['error'] ?? 'Signoff revoke failed.'),
        ], 400);
    }
    audit_event('deployment', 'cutover.signoff.revoke', [
        'signoff_id' => (string) ($snap['item']['signoff_id'] ?? ''),
        'reason' => (string) ($snap['item']['revocation_reason'] ?? ''),
    ]);
    push_notification('warning', 'Cutover signoff revoked: ' . (string) ($snap['item']['signoff_id'] ?? ''), [
        'signoff_id' => (string) ($snap['item']['signoff_id'] ?? ''),
    ]);
    out_json([
        'ok' => true,
        'item' => $snap['item'],
        'active' => $snap['active'],
        'latest' => $snap['latest'],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.signoff.verify') {
    $signoffId = isset($_GET['signoff_id']) ? (string) $_GET['signoff_id'] : '';
    $snap = deployment_cutover_signoff_verify_snapshot($signoffId);
    if (empty($snap['ok'])) {
        out_json([
            'ok' => false,
            'error' => (string) ($snap['error'] ?? 'Verification failed.'),
            'signoff_id' => (string) ($snap['signoff_id'] ?? $signoffId),
            'time' => gmdate('c'),
        ], 404);
    }
    out_json([
        'ok' => true,
        'check' => $snap['check'],
        'time' => gmdate('c'),
    ], !empty($snap['check']['valid']) ? 200 : 409);
}

if ($action === 'deployment.cutover.signoff.verify_all') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    $snap = deployment_cutover_signoff_verify_all_snapshot($limit);
    out_json([
        'ok' => true,
        'verification' => $snap,
        'time' => gmdate('c'),
    ], ((int) ($snap['summary']['invalid'] ?? 0) === 0) ? 200 : 409);
}

if ($action === 'deployment.cutover.signoff.integrity.watch') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        $data = [];
    }
    $source = isset($data['source']) ? (string) $data['source'] : 'dashboard_manual';
    $snap = deployment_cutover_signoff_integrity_watch_snapshot($source);
    audit_event('deployment', 'cutover.signoff.integrity.watch', [
        'run_id' => (string) ($snap['run']['run_id'] ?? ''),
        'status' => (string) ($snap['run']['status'] ?? ''),
        'source' => (string) ($snap['run']['source'] ?? ''),
        'status_changed' => (int) ($snap['run']['status_changed'] ?? 0),
        'alert_sent' => (int) ($snap['run']['alert_sent'] ?? 0),
    ]);
    out_json([
        'ok' => true,
        'watch' => $snap,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.signoff.integrity.runs') {
    $runs = app_read_json_file(deployment_cutover_signoff_integrity_runs_path(), []);
    $state = app_read_json_file(deployment_cutover_signoff_integrity_state_path(), []);
    out_json([
        'ok' => true,
        'count' => is_array($runs) ? count($runs) : 0,
        'items' => is_array($runs) ? $runs : [],
        'state' => is_array($state) ? $state : [],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.cutover.readiness') {
    $snap = deployment_cutover_readiness_snapshot();
    out_json([
        'ok' => true,
        'readiness' => $snap,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.incident.resolve') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['report_id'])) {
        out_json(['ok' => false, 'error' => 'report_id is required.'], 400);
    }
    $reportId = (string) $data['report_id'];
    $status = strtolower(trim((string) ($data['status'] ?? 'resolved')));
    if (!in_array($status, ['resolved', 'reopened'], true)) {
        out_json(['ok' => false, 'error' => 'status must be resolved or reopened.'], 400);
    }
    $note = trim((string) ($data['note'] ?? ''));
    $updated = update_incident_report_status($reportId, $status, $note);
    if (!is_array($updated)) {
        out_json(['ok' => false, 'error' => 'Incident report not found.'], 404);
    }
    audit_event('deployment', 'incident.report.status', [
        'report_id' => $reportId,
        'status' => $status,
        'note' => $note,
    ]);
    push_notification($status === 'resolved' ? 'success' : 'warning', 'Incident report ' . $status . ': ' . $reportId, [
        'report_id' => $reportId,
        'status' => $status,
    ]);
    out_json([
        'ok' => true,
        'item' => $updated,
    ]);
}

if ($action === 'deployment.guard.unlock') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $evalBefore = deployment_guard_evaluate(false);
    if (!empty($evalBefore['reasons'])) {
        out_json([
            'ok' => false,
            'error' => 'Cannot unlock deployment guard yet.',
            'reasons' => $evalBefore['reasons'],
            'guard' => $evalBefore['guard'],
        ], 400);
    }
    $saved = save_deployment_guard(['unlocked' => 1]);
    audit_event('deployment', 'guard.unlock', ['enforced' => (int) $saved['enforced']]);
    out_json([
        'ok' => true,
        'message' => 'Deployment guard unlocked.',
        'guard' => $saved,
    ]);
}

if ($action === 'deployment.guard.lock') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $saved = save_deployment_guard(['unlocked' => 0]);
    audit_event('deployment', 'guard.lock', ['enforced' => (int) $saved['enforced']]);
    out_json([
        'ok' => true,
        'message' => 'Deployment guard locked.',
        'guard' => $saved,
    ]);
}

if ($action === 'notifications') {
    $rows = app_read_json_file(notifications_path(), []);
    $unread = 0;
    $criticalUnread = 0;
    foreach ($rows as $row) {
        if (is_array($row) && empty($row['read'])) {
            $unread++;
            if (in_array((string) ($row['type'] ?? ''), ['critical'], true)) {
                $criticalUnread++;
            }
        }
    }
    if (empty($rows)) {
        $rows = [
            ['id' => 'bootstrap_1', 'type' => 'info', 'message' => 'Main platform API is reachable.', 'read' => 0, 'created_at' => gmdate('c')],
            ['id' => 'bootstrap_2', 'type' => 'info', 'message' => 'Bridge-aware mode enabled.', 'read' => 0, 'created_at' => gmdate('c')],
        ];
        $unread = 2;
    }
    out_json([
        'ok' => true,
        'unread' => $unread,
        'critical_unread' => $criticalUnread,
        'settings' => get_notification_settings(),
        'items' => $rows,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'notifications.settings.get') {
    out_json([
        'ok' => true,
        'settings' => get_notification_settings(),
    ]);
}

if ($action === 'notifications.settings.save') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $saved = save_notification_settings([
        'sound_enabled' => !empty($data['sound_enabled']) ? 1 : 0,
        'sound_mode' => (string) ($data['sound_mode'] ?? 'critical_only'),
    ]);
    audit_event('notifications', 'settings.save', ['sound_enabled' => (int) $saved['sound_enabled'], 'sound_mode' => (string) $saved['sound_mode']]);
    out_json([
        'ok' => true,
        'settings' => $saved,
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

if ($action === 'leads.inventory') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = leads_inventory_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'sites' => $snapshot['sites'],
        'statuses' => $snapshot['statuses'],
        'top_categories' => $snapshot['top_categories'],
        'top_cities' => $snapshot['top_cities'],
        'recent_leads' => $snapshot['recent_leads'],
    ]);
}

if ($action === 'leads.list') {
    $snapshot = leads_list_snapshot($_GET);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
    ]);
}

if ($action === 'leads.push.plan') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = leads_push_plan_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'issues' => $snapshot['issues'],
        'connectors' => $snapshot['connectors'],
        'sites' => $snapshot['sites'],
        'last_sync' => $snapshot['last_sync'],
    ]);
}

if ($action === 'leads.quality') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = leads_quality_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'duplicate_groups' => $snapshot['duplicate_groups'],
    ]);
}

if ($action === 'leads.export') {
    $snapshot = leads_list_snapshot($_GET);
    $payload = [
        'exported_at' => gmdate('c'),
        'filters' => [
            'search' => isset($_GET['search']) ? (string) $_GET['search'] : '',
            'site_id' => isset($_GET['site_id']) ? (string) $_GET['site_id'] : '',
            'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
            'limit' => isset($snapshot['summary']['limit']) ? (int) $snapshot['summary']['limit'] : 20,
            'page' => isset($snapshot['summary']['page']) ? (int) $snapshot['summary']['page'] : 1,
        ],
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
    ];
    audit_event('leads', 'export', [
        'filtered_count' => (int) ($snapshot['summary']['filtered_count'] ?? 0),
        'site_id' => isset($_GET['site_id']) ? (string) $_GET['site_id'] : '',
        'status' => isset($_GET['status']) ? (string) $_GET['status'] : '',
    ]);
    out_json([
        'ok' => true,
        'filename' => 'leads_export_' . gmdate('Ymd_His') . '.json',
        'export' => $payload,
    ]);
}

if ($action === 'leads.delivery.history') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = leads_delivery_history_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
        'retry_queue' => $snapshot['retry_queue'],
    ]);
}

if ($action === 'leads.review.queue') {
    $snapshot = collect_leads_review_queue($_GET);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
    ]);
}

if ($action === 'leads.review.queue.export') {
    $snapshot = collect_leads_review_queue($_GET);
    $payload = [
        'exported_at' => gmdate('c'),
        'filters' => [
            'review_status' => isset($_GET['review_status']) ? (string) $_GET['review_status'] : 'pending',
            'site_id' => isset($_GET['site_id']) ? (string) $_GET['site_id'] : '',
            'limit' => isset($snapshot['summary']['limit']) ? (int) $snapshot['summary']['limit'] : 10,
            'page' => isset($snapshot['summary']['page']) ? (int) $snapshot['summary']['page'] : 1,
        ],
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
    ];
    audit_event('leads', 'review.queue.export', [
        'filtered_count' => (int) ($snapshot['summary']['filtered_count'] ?? 0),
        'site_id' => isset($_GET['site_id']) ? (string) $_GET['site_id'] : '',
        'review_status' => isset($_GET['review_status']) ? (string) $_GET['review_status'] : 'pending',
    ]);
    out_json([
        'ok' => true,
        'filename' => 'leads_review_queue_' . gmdate('Ymd_His') . '.json',
        'export' => $payload,
    ]);
}

if ($action === 'leads.review.detail') {
    $siteId = isset($_GET['site_id']) ? (string) $_GET['site_id'] : '';
    $runId = isset($_GET['run_id']) ? (int) $_GET['run_id'] : 0;
    if ($siteId === '' || $runId <= 0) {
        out_json(['ok' => false, 'error' => 'site_id and run_id are required.'], 400);
    }
    $snapshot = leads_review_detail_fetch($siteId, $runId);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 502);
}

if ($action === 'leads.review.duplicates') {
    $siteId = isset($_GET['site_id']) ? (string) $_GET['site_id'] : '';
    $runId = isset($_GET['run_id']) ? (int) $_GET['run_id'] : 0;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 25;
    if ($siteId === '' || $runId <= 0) {
        out_json(['ok' => false, 'error' => 'site_id and run_id are required.'], 400);
    }
    if ($limit <= 0) {
        $limit = 25;
    }
    $snapshot = leads_review_duplicates_snapshot($siteId, $runId, $limit);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 502);
}

if ($action === 'leads.review.save' || $action === 'leads.review.discard') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['site_id']) || empty($data['run_id'])) {
        out_json(['ok' => false, 'error' => 'site_id and run_id are required.'], 400);
    }
    $mode = ($action === 'leads.review.save') ? 'save' : 'discard';
    $snapshot = leads_review_action((string) $data['site_id'], (int) $data['run_id'], $mode);
    audit_event('leads', 'review.' . $mode, [
        'site_id' => (string) ($data['site_id'] ?? ''),
        'run_id' => (int) ($data['run_id'] ?? 0),
        'ok' => !empty($snapshot['ok']) ? 1 : 0,
    ]);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 502);
}

if ($action === 'leads.review.draft.update') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['site_id']) || empty($data['run_id']) || empty($data['draft_id'])) {
        out_json(['ok' => false, 'error' => 'site_id, run_id, and draft_id are required.'], 400);
    }
    $fields = [];
    foreach (['business_name', 'city', 'category', 'website', 'phone', 'email', 'status', 'notes'] as $field) {
        if (array_key_exists($field, $data)) {
            $fields[$field] = (string) $data[$field];
        }
    }
    $snapshot = leads_review_draft_update((string) $data['site_id'], (int) $data['run_id'], (int) $data['draft_id'], $fields);
    audit_event('leads', 'review.draft.update', [
        'site_id' => (string) ($data['site_id'] ?? ''),
        'run_id' => (int) ($data['run_id'] ?? 0),
        'draft_id' => (int) ($data['draft_id'] ?? 0),
        'ok' => !empty($snapshot['ok']) ? 1 : 0,
    ]);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 502);
}

if ($action === 'leads.review.queue.bulk_action') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    $snapshot = leads_review_queue_bulk_action((string) ($data['action'] ?? ''), is_array($data) ? $data : []);
    audit_event('leads', 'review.queue.bulk_action', [
        'action' => (string) ($data['action'] ?? ''),
        'processed' => (int) ($snapshot['processed'] ?? 0),
        'succeeded' => (int) ($snapshot['succeeded'] ?? 0),
    ]);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 400);
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
    audit_event('crm', 'connectors.save', ['connector_id' => (string) $item['connector_id'], 'provider' => (string) $item['provider']]);
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
    audit_event('crm', 'connectors.delete', ['connector_id' => $connectorId]);
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
    audit_event('crm', 'push.sync', ['sync_id' => $syncId, 'connector_count' => count($activeConnectors), 'approved_total' => $totalApproved]);

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

if ($action === 'crm.delivery.summary') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    if ($limit <= 0) {
        $limit = 20;
    }
    $snapshot = crm_delivery_summary_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
    ]);
}

if ($action === 'crm.delivery.connector_detail') {
    $connectorId = isset($_GET['connector_id']) ? (string) $_GET['connector_id'] : '';
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = crm_delivery_connector_detail_snapshot($connectorId, $limit);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 400);
}

if ($action === 'crm.delivery.export') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    if ($limit <= 0) {
        $limit = 20;
    }
    $connectorId = isset($_GET['connector_id']) ? trim((string) $_GET['connector_id']) : '';
    $payload = [
        'exported_at' => gmdate('c'),
        'summary' => crm_delivery_summary_snapshot($limit),
    ];
    if ($connectorId !== '') {
        $payload['connector_detail'] = crm_delivery_connector_detail_snapshot($connectorId, $limit);
    }
    audit_event('crm', 'delivery.export', [
        'connector_id' => $connectorId,
        'limit' => $limit,
    ]);
    out_json([
        'ok' => true,
        'filename' => 'crm_delivery_export_' . gmdate('Ymd_His') . '.json',
        'export' => $payload,
    ]);
}

if ($action === 'crm.delivery.watch.summary') {
    $snapshot = crm_delivery_watch_summary();
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'latest' => $snapshot['latest'],
        'runs' => $snapshot['runs'],
    ]);
}

if ($action === 'crm.delivery.watch.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $run = crm_delivery_watch_evaluate('manual', true);
    audit_event('crm', 'delivery.watch.run', [
        'watch_id' => (string) ($run['watch_id'] ?? ''),
        'degraded_count' => (int) ($run['summary']['degraded_count'] ?? 0),
    ]);
    out_json([
        'ok' => true,
        'run' => $run,
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

if ($action === 'crm.smtp.summary') {
    $snapshot = crm_smtp_sites_snapshot();
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'sites' => $snapshot['sites'],
    ]);
}

if ($action === 'crm.smtp.probe') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['site_id'])) {
        out_json(['ok' => false, 'error' => 'site_id is required.'], 400);
    }
    $site = site_by_id((string) $data['site_id']);
    if (!is_array($site)) {
        out_json(['ok' => false, 'error' => 'Site not found.'], 404);
    }
    $res = app_bridge_request($site, 'POST', 'bridge/smtp-probe', []);
    $watch = crm_smtp_watch_snapshot('smtp_probe', true);
    audit_event('crm', 'smtp.probe', ['site_id' => (string) ($site['site_id'] ?? ''), 'ok' => !empty($res['ok']) ? 1 : 0]);
    out_json([
        'ok' => !empty($res['ok']),
        'site_id' => (string) ($site['site_id'] ?? ''),
        'response' => $res,
        'smtp_watch' => $watch['run'],
    ], !empty($res['ok']) ? 200 : 502);
}

if ($action === 'crm.smtp.send_test') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['site_id']) || empty($data['to_email'])) {
        out_json(['ok' => false, 'error' => 'site_id and to_email are required.'], 400);
    }
    $site = site_by_id((string) $data['site_id']);
    if (!is_array($site)) {
        out_json(['ok' => false, 'error' => 'Site not found.'], 404);
    }
    $toEmail = trim((string) $data['to_email']);
    $res = app_bridge_request($site, 'POST', 'bridge/smtp-send-test', ['to_email' => $toEmail]);
    $watch = crm_smtp_watch_snapshot('smtp_send_test', true);
    audit_event('crm', 'smtp.send_test', [
        'site_id' => (string) ($site['site_id'] ?? ''),
        'to_email' => $toEmail,
        'ok' => !empty($res['ok']) ? 1 : 0,
    ]);
    out_json([
        'ok' => !empty($res['ok']),
        'site_id' => (string) ($site['site_id'] ?? ''),
        'response' => $res,
        'smtp_watch' => $watch['run'],
    ], !empty($res['ok']) ? 200 : 502);
}

if ($action === 'crm.smtp.confirm') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['site_id'])) {
        out_json(['ok' => false, 'error' => 'site_id is required.'], 400);
    }
    $site = site_by_id((string) $data['site_id']);
    if (!is_array($site)) {
        out_json(['ok' => false, 'error' => 'Site not found.'], 404);
    }
    $payload = [
        'received' => !empty($data['received']) ? 1 : 0,
        'to_email' => trim((string) ($data['to_email'] ?? '')),
    ];
    $res = app_bridge_request($site, 'POST', 'bridge/smtp-confirm', $payload);
    $watch = crm_smtp_watch_snapshot('smtp_confirm', true);
    audit_event('crm', 'smtp.confirm', [
        'site_id' => (string) ($site['site_id'] ?? ''),
        'received' => !empty($payload['received']) ? 1 : 0,
        'ok' => !empty($res['ok']) ? 1 : 0,
    ]);
    out_json([
        'ok' => !empty($res['ok']),
        'site_id' => (string) ($site['site_id'] ?? ''),
        'response' => $res,
        'smtp_watch' => $watch['run'],
    ], !empty($res['ok']) ? 200 : 502);
}

if ($action === 'crm.smtp.watch.summary') {
    $state = app_read_json_file(crm_smtp_watch_state_path(), []);
    $runs = app_read_json_file(crm_smtp_watch_runs_path(), []);
    out_json([
        'ok' => true,
        'state' => is_array($state) ? $state : [],
        'latest_run' => isset($runs[0]) && is_array($runs[0]) ? $runs[0] : null,
        'runs' => array_slice(is_array($runs) ? $runs : [], 0, 25),
    ]);
}

if ($action === 'crm.smtp.watch.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $watch = crm_smtp_watch_snapshot('manual_run', true);
    out_json([
        'ok' => true,
        'state' => $watch['state'],
        'run' => $watch['run'],
    ]);
}

if ($action === 'crm.email_templates.get') {
    $siteId = isset($_GET['site_id']) ? (string) $_GET['site_id'] : '';
    $site = $siteId !== '' ? site_by_id($siteId) : null;
    if (!is_array($site)) {
        $sites = all_sites();
        $site = isset($sites[0]) && is_array($sites[0]) ? $sites[0] : null;
    }
    if (!is_array($site)) {
        out_json(['ok' => true, 'site' => null, 'templates' => ['templates' => [], 'placeholders' => []], 'message' => 'No bridge site configured.']);
    }
    $snapshot = crm_email_templates_fetch($site);
    out_json([
        'ok' => !empty($snapshot['ok']),
        'site' => [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'base_url' => (string) ($site['base_url'] ?? ''),
        ],
        'templates' => $snapshot['email_templates'],
        'bridge_status' => $snapshot['status'],
        'bridge_error' => $snapshot['error'],
    ], !empty($snapshot['ok']) ? 200 : 502);
}

if ($action === 'crm.email_templates.test_log') {
    $siteId = isset($_GET['site_id']) ? (string) $_GET['site_id'] : '';
    $site = $siteId !== '' ? site_by_id($siteId) : null;
    if (!is_array($site)) {
        $sites = all_sites();
        $site = isset($sites[0]) ? $sites[0] : null;
    }
    if (!is_array($site)) {
        out_json(['ok' => true, 'site' => null, 'items' => [], 'count' => 0, 'message' => 'No bridge site configured.']);
    }
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 25;
    $snapshot = crm_email_template_test_log_fetch($site, $limit);
    out_json([
        'ok' => !empty($snapshot['ok']),
        'site' => [
            'site_id' => (string) ($site['site_id'] ?? ''),
            'label' => (string) ($site['label'] ?? $site['base_url']),
            'base_url' => (string) ($site['base_url'] ?? ''),
        ],
        'items' => $snapshot['items'],
        'count' => (int) ($snapshot['count'] ?? count($snapshot['items'])),
        'bridge_status' => $snapshot['status'],
        'bridge_error' => $snapshot['error'],
    ], !empty($snapshot['ok']) ? 200 : 502);
}

if ($action === 'crm.email_templates.save') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['site_id']) || !isset($data['templates']) || !is_array($data['templates'])) {
        out_json(['ok' => false, 'error' => 'site_id and templates are required.'], 400);
    }
    $site = site_by_id((string) $data['site_id']);
    if (!is_array($site)) {
        out_json(['ok' => false, 'error' => 'Site not found.'], 404);
    }
    $res = app_bridge_request($site, 'POST', 'bridge/email-templates', ['templates' => $data['templates']]);
    audit_event('crm', 'email_templates.save', [
        'site_id' => (string) ($site['site_id'] ?? ''),
        'template_count' => count((array) ($data['templates'] ?? [])),
        'ok' => !empty($res['ok']) ? 1 : 0,
    ]);
    out_json([
        'ok' => !empty($res['ok']),
        'site_id' => (string) ($site['site_id'] ?? ''),
        'response' => $res,
    ], !empty($res['ok']) ? 200 : 502);
}

if ($action === 'crm.email_templates.preview') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['site_id']) || empty($data['template_key'])) {
        out_json(['ok' => false, 'error' => 'site_id and template_key are required.'], 400);
    }
    $site = site_by_id((string) $data['site_id']);
    if (!is_array($site)) {
        out_json(['ok' => false, 'error' => 'Site not found.'], 404);
    }
    $preview = crm_email_template_preview_fetch(
        $site,
        (string) $data['template_key'],
        array_key_exists('subject', $data) ? (string) ($data['subject'] ?? '') : null,
        array_key_exists('body', $data) ? (string) ($data['body'] ?? '') : null,
        isset($data['vars']) && is_array($data['vars']) ? $data['vars'] : []
    );
    out_json([
        'ok' => !empty($preview['ok']),
        'site_id' => (string) ($site['site_id'] ?? ''),
        'preview' => $preview['preview'],
        'bridge_status' => $preview['status'],
        'bridge_error' => $preview['error'],
    ], !empty($preview['ok']) ? 200 : 502);
}

if ($action === 'crm.email_templates.send_test') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['site_id']) || empty($data['template_key']) || empty($data['to_email'])) {
        out_json(['ok' => false, 'error' => 'site_id, template_key, and to_email are required.'], 400);
    }
    $site = site_by_id((string) $data['site_id']);
    if (!is_array($site)) {
        out_json(['ok' => false, 'error' => 'Site not found.'], 404);
    }
    $payload = [
        'template_key' => (string) $data['template_key'],
        'to_email' => trim((string) $data['to_email']),
        'vars' => isset($data['vars']) && is_array($data['vars']) ? $data['vars'] : [],
    ];
    $res = app_bridge_request($site, 'POST', 'bridge/email-templates/test-send', $payload);
    audit_event('crm', 'email_templates.send_test', [
        'site_id' => (string) ($site['site_id'] ?? ''),
        'template_key' => (string) $payload['template_key'],
        'to_email' => (string) $payload['to_email'],
        'ok' => !empty($res['ok']) ? 1 : 0,
    ]);
    out_json([
        'ok' => !empty($res['ok']),
        'site_id' => (string) ($site['site_id'] ?? ''),
        'response' => $res,
    ], !empty($res['ok']) ? 200 : 502);
}

if ($action === 'crm.summary') {
    $connectors = app_read_json_file(app_storage_path('crm_connectors.json'), []);
    $smtpSnapshot = crm_smtp_sites_snapshot();
    $smtpConnected = ((int) ($smtpSnapshot['summary']['connected_sites'] ?? 0)) > 0;
    $smtpBySite = $smtpSnapshot['sites'];
    $templateSitesConfigured = 0;
    foreach (all_sites() as $site) {
        $snapshot = crm_email_templates_fetch($site);
        if (!empty($snapshot['ok']) && !empty($snapshot['email_templates']['templates'])) {
            $templateSitesConfigured++;
        }
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
            'smtp_pending_confirmation_sites' => (int) ($smtpSnapshot['summary']['pending_confirmation_sites'] ?? 0),
            'smtp_confirmed_sites' => (int) ($smtpSnapshot['summary']['confirmed_sites'] ?? 0),
            'email_template_sites' => $templateSitesConfigured,
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
    $platforms = [];
    foreach ($active as $row) {
        $provider = strtolower((string) ($row['provider'] ?? 'custom'));
        if ($provider !== '' && !in_array($provider, $platforms, true)) {
            $platforms[] = $provider;
        }
    }
    $logs = app_read_json_file(social_sync_log_path(), []);
    $lastSync = isset($logs[0]) && is_array($logs[0]) ? $logs[0] : null;
    $activity = app_read_json_file(social_activity_feed_path(), []);
    $lastActivity = isset($activity[0]) && is_array($activity[0]) ? $activity[0] : null;
    $retryCount = count(app_read_json_file(social_retry_queue_path(), []));
    $threads = social_inbox_threads_rows(true);
    $openThreads = 0;
    $pendingThreads = 0;
    foreach ($threads as $thread) {
        if (!is_array($thread)) {
            continue;
        }
        $status = strtolower((string) ($thread['status'] ?? 'open'));
        if ($status === 'open') {
            $openThreads++;
        } elseif ($status === 'pending') {
            $pendingThreads++;
        }
    }
    $scheduledCount = 0;
    foreach (app_read_json_file(social_schedule_queue_path(), []) as $row) {
        if (is_array($row) && strtolower((string) ($row['status'] ?? 'queued')) === 'queued') {
            $scheduledCount++;
        }
    }
    out_json([
        'ok' => true,
        'module' => 'social_forums',
        'status' => 'active',
        'metrics' => [
            'connected_accounts' => count($active),
            'active_platforms' => count($platforms),
            'scheduled_posts' => $scheduledCount,
            'unread_conversations' => $openThreads,
            'pending_conversations' => $pendingThreads,
            'conversation_backlog' => ($openThreads + $pendingThreads),
            'retry_backlog' => $retryCount,
        ],
        'last_sync' => $lastSync,
        'last_activity' => $lastActivity,
    ]);
}

if ($action === 'social.operations.snapshot') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
    if ($limit <= 0) {
        $limit = 5;
    }
    $snapshot = social_operations_snapshot($limit);
    out_json([
        'ok' => true,
        'snapshot' => $snapshot,
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
    $queuedActions = 0;
    foreach (app_read_json_file(webops_actions_queue_path(), []) as $actionRow) {
        if (is_array($actionRow) && strtolower((string) ($actionRow['status'] ?? 'queued')) === 'queued') {
            $queuedActions++;
        }
    }
    $incidentRows = app_read_json_file(webops_incidents_path(), []);
    $openIncidents = 0;
    foreach ($incidentRows as $incidentRow) {
        if (is_array($incidentRow) && strtolower((string) ($incidentRow['status'] ?? 'open')) === 'open') {
            $openIncidents++;
        }
    }
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
        'status' => 'active',
        'metrics' => [
            'sites_monitored' => count($active),
            'active_incidents' => $openIncidents,
            'retry_backlog' => $retryCount,
            'queued_actions' => $queuedActions,
            'uptime_percent' => 100,
        ],
        'last_run' => $lastRun,
    ]);
}

if ($action === 'webops.types.list') {
    $rows = webops_monitor_catalog();
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
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
        'status' => 'active',
        'metrics' => [
            'audits_completed' => count($audits),
            'critical_issues' => $criticalIssues,
            'tracked_projects' => count($projects),
        ],
        'last_audit' => $lastAudit,
    ]);
}

if ($action === 'seo.issues.summary') {
    $rows = app_read_json_file(seo_audits_path(), []);
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    if ($projectId !== '') {
        $rows = array_values(array_filter($rows, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
    }
    $prioritySummary = [
        'critical' => 0,
        'fix_soon' => 0,
        'nice_to_have' => 0,
    ];
    $issueMap = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rollup = isset($row['issue_rollup']) && is_array($row['issue_rollup'])
            ? $row['issue_rollup']
            : seo_issue_rollup_from_checks(isset($row['checks']) && is_array($row['checks']) ? $row['checks'] : [])['issues'];
        $priorities = isset($row['priority_summary']) && is_array($row['priority_summary'])
            ? $row['priority_summary']
            : seo_issue_rollup_from_checks(isset($row['checks']) && is_array($row['checks']) ? $row['checks'] : [])['priority_summary'];
        foreach (['critical', 'fix_soon', 'nice_to_have'] as $key) {
            $prioritySummary[$key] += (int) ($priorities[$key] ?? 0);
        }
        foreach ($rollup as $issue) {
            if (!is_array($issue)) {
                continue;
            }
            $check = (string) ($issue['check'] ?? 'unknown');
            if (!isset($issueMap[$check])) {
                $issueMap[$check] = [
                    'check' => $check,
                    'priority' => (string) ($issue['priority'] ?? 'nice_to_have'),
                    'occurrences' => 0,
                    'messages' => [],
                    'statuses' => [],
                ];
            }
            $issueMap[$check]['occurrences'] += (int) ($issue['occurrences'] ?? 0);
            foreach ((array) ($issue['messages'] ?? []) as $message) {
                $msg = trim((string) $message);
                if ($msg !== '' && !in_array($msg, $issueMap[$check]['messages'], true)) {
                    $issueMap[$check]['messages'][] = $msg;
                }
            }
            foreach ((array) ($issue['statuses'] ?? []) as $status) {
                $state = trim((string) $status);
                if ($state !== '' && !in_array($state, $issueMap[$check]['statuses'], true)) {
                    $issueMap[$check]['statuses'][] = $state;
                }
            }
            $currentPriority = (string) ($issueMap[$check]['priority'] ?? 'nice_to_have');
            $nextPriority = (string) ($issue['priority'] ?? 'nice_to_have');
            if ($currentPriority !== 'critical' && ($nextPriority === 'critical' || ($currentPriority === 'nice_to_have' && $nextPriority === 'fix_soon'))) {
                $issueMap[$check]['priority'] = $nextPriority;
            }
        }
    }
    $issues = array_values($issueMap);
    usort($issues, static function ($a, $b) {
        $rank = ['critical' => 0, 'fix_soon' => 1, 'nice_to_have' => 2];
        $aRank = $rank[(string) ($a['priority'] ?? 'nice_to_have')] ?? 9;
        $bRank = $rank[(string) ($b['priority'] ?? 'nice_to_have')] ?? 9;
        if ($aRank !== $bRank) {
            return $aRank <=> $bRank;
        }
        return (int) ($b['occurrences'] ?? 0) <=> (int) ($a['occurrences'] ?? 0);
    });
    out_json([
        'ok' => true,
        'audit_count' => count($rows),
        'priority_summary' => $prioritySummary,
        'issues' => $issues,
        'project_id' => $projectId,
    ]);
}

if ($action === 'seo.history.summary') {
    $rows = app_read_json_file(seo_audits_path(), []);
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    if ($projectId !== '') {
        $rows = array_values(array_filter($rows, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
    }
    $scores = [];
    $projects = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $score = (int) ($row['score'] ?? 0);
        $scores[] = $score;
        $pid = (string) ($row['project_id'] ?? '');
        if ($pid !== '') {
            if (!isset($projects[$pid])) {
                $projects[$pid] = [
                    'project_id' => $pid,
                    'project_name' => (string) ($row['project_name'] ?? ''),
                    'audit_count' => 0,
                    'latest_score' => $score,
                    'best_score' => $score,
                    'worst_score' => $score,
                ];
            }
            $projects[$pid]['audit_count']++;
            $projects[$pid]['latest_score'] = $score;
            $projects[$pid]['best_score'] = max((int) $projects[$pid]['best_score'], $score);
            $projects[$pid]['worst_score'] = min((int) $projects[$pid]['worst_score'], $score);
        }
    }
    $average = empty($scores) ? 0 : round(array_sum($scores) / count($scores), 2);
    $latestScore = isset($rows[0]) && is_array($rows[0]) ? (int) ($rows[0]['score'] ?? 0) : 0;
    $previousScore = isset($rows[1]) && is_array($rows[1]) ? (int) ($rows[1]['score'] ?? 0) : null;
    $trend = 'flat';
    if ($previousScore !== null) {
        if ($latestScore > $previousScore) {
            $trend = 'up';
        } elseif ($latestScore < $previousScore) {
            $trend = 'down';
        }
    }
    out_json([
        'ok' => true,
        'audit_count' => count($rows),
        'summary' => [
            'average_score' => $average,
            'latest_score' => $latestScore,
            'previous_score' => $previousScore,
            'best_score' => empty($scores) ? 0 : max($scores),
            'worst_score' => empty($scores) ? 0 : min($scores),
            'trend' => $trend,
        ],
        'recent_scores' => array_values(array_map(static function ($row) {
            return [
                'audit_id' => (string) ($row['audit_id'] ?? ''),
                'project_id' => (string) ($row['project_id'] ?? ''),
                'score' => (int) ($row['score'] ?? 0),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'source' => (string) ($row['source'] ?? ''),
            ];
        }, array_slice($rows, 0, 12))),
        'projects' => array_values($projects),
        'project_id' => $projectId,
    ]);
}

if ($action === 'seo.extension.sessions.list') {
    $rows = app_read_json_file(seo_extension_sessions_path(), []);
    $items = array_map('seo_extension_mask_session', $rows);
    out_json([
        'ok' => true,
        'count' => count($items),
        'items' => $items,
    ]);
}

if ($action === 'social.platforms.list') {
    $items = social_platform_catalog();
    out_json([
        'ok' => true,
        'count' => count($items),
        'items' => $items,
    ]);
}

if ($action === 'social.capabilities.summary') {
    $snapshot = social_capability_summary();
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'families' => $snapshot['families'],
        'providers' => $snapshot['providers'],
        'capabilities' => $snapshot['capabilities'],
    ]);
}

if ($action === 'social.watch.summary') {
    $state = app_read_json_file(social_watch_state_path(), []);
    $runs = app_read_json_file(social_watch_runs_path(), []);
    out_json([
        'ok' => true,
        'state' => is_array($state) ? $state : [],
        'latest_run' => isset($runs[0]) && is_array($runs[0]) ? $runs[0] : null,
        'runs' => array_slice(is_array($runs) ? $runs : [], 0, 25),
    ]);
}

if ($action === 'social.watch.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $watch = social_watch_snapshot('manual_run', true);
    out_json([
        'ok' => true,
        'state' => $watch['state'],
        'run' => $watch['run'],
    ]);
}

if ($action === 'social.drafts.preview') {
    $drafts = collect_social_drafts();
    $connectors = app_read_json_file(social_connectors_path(), []);
    $readiness = [];
    $readyCount = 0;
    $blockedCount = 0;
    foreach ($connectors as $connector) {
        $row = social_connector_readiness($connector, $drafts);
        $readiness[] = $row;
        if (($row['state'] ?? '') === 'ready') {
            $readyCount++;
        } else {
            $blockedCount++;
        }
    }
    out_json([
        'ok' => true,
        'draft_count' => count($drafts),
        'connector_count' => count($connectors),
        'summary' => [
            'ready_connectors' => $readyCount,
            'blocked_connectors' => $blockedCount,
        ],
        'drafts' => $drafts,
        'connector_readiness' => $readiness,
    ]);
}

if ($action === 'social.drafts.validation') {
    $drafts = collect_social_drafts();
    $snapshot = social_draft_validation_snapshot($drafts);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
        'draft_count' => count($drafts),
    ]);
}

if ($action === 'social.drafts.plan') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $drafts = collect_social_drafts();
    $snapshot = social_draft_delivery_plan($drafts, $limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
        'draft_count' => count($drafts),
    ]);
}

if ($action === 'social.drafts.export') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    if ($limit <= 0) {
        $limit = 20;
    }
    $drafts = collect_social_drafts();
    $validation = social_draft_validation_snapshot($drafts);
    $plan = social_draft_delivery_plan($drafts, $limit);
    audit_event('social', 'drafts.export', [
        'draft_count' => count($drafts),
        'limit' => $limit,
    ]);
    out_json([
        'ok' => true,
        'filename' => 'social_drafts_export_' . gmdate('Ymd_His') . '.json',
        'export' => [
            'exported_at' => gmdate('c'),
            'draft_count' => count($drafts),
            'drafts' => $drafts,
            'validation' => $validation,
            'delivery_plan' => $plan,
        ],
    ]);
}

if ($action === 'social.schedule.summary') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = social_schedule_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'next_due' => $snapshot['next_due'],
        'next_upcoming' => $snapshot['next_upcoming'],
        'due_items' => $snapshot['due_items'],
        'upcoming_items' => $snapshot['upcoming_items'],
        'failed_items' => $snapshot['failed_items'],
    ]);
}

if ($action === 'social.schedule.targets.validate') {
    $connectorIds = isset($_GET['connector_ids']) ? explode(',', (string) $_GET['connector_ids']) : [];
    $snapshot = social_schedule_target_validation_snapshot($connectorIds);
    out_json([
        'ok' => !empty($snapshot['ok']),
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
    ], !empty($snapshot['ok']) ? 200 : 400);
}

if ($action === 'social.schedule.list') {
    $snapshot = social_schedule_list_snapshot($_GET);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'count' => (int) ($snapshot['summary']['filtered_count'] ?? 0),
        'items' => $snapshot['items'],
    ]);
}

if ($action === 'social.schedule.detail') {
    $scheduleId = isset($_GET['schedule_id']) ? (string) $_GET['schedule_id'] : '';
    $snapshot = social_schedule_detail_snapshot($scheduleId);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 404);
}

if ($action === 'social.schedule.export') {
    $snapshot = social_schedule_list_snapshot($_GET);
    $scheduleId = isset($_GET['schedule_id']) ? trim((string) $_GET['schedule_id']) : '';
    $payload = [
        'exported_at' => gmdate('c'),
        'filters' => $snapshot['summary'],
        'items' => $snapshot['items'],
    ];
    if ($scheduleId !== '') {
        $payload['schedule_detail'] = social_schedule_detail_snapshot($scheduleId);
    }
    audit_event('social', 'schedule.export', [
        'filtered_count' => (int) ($snapshot['summary']['filtered_count'] ?? 0),
        'schedule_id' => $scheduleId,
    ]);
    out_json([
        'ok' => true,
        'filename' => 'social_schedule_export_' . gmdate('Ymd_His') . '.json',
        'export' => $payload,
    ]);
}

if ($action === 'social.activity.list') {
    $rows = app_read_json_file(social_activity_feed_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
    ]);
}

if ($action === 'social.inbox.list') {
    $snapshot = social_inbox_list_snapshot($_GET);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'count' => (int) ($snapshot['summary']['filtered_count'] ?? 0),
        'items' => $snapshot['items'],
    ]);
}

if ($action === 'social.inbox.detail') {
    $threadId = isset($_GET['thread_id']) ? (string) $_GET['thread_id'] : '';
    $snapshot = social_inbox_thread_detail_snapshot($threadId);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 404);
}

if ($action === 'social.inbox.export') {
    $snapshot = social_inbox_list_snapshot($_GET);
    $threadId = isset($_GET['thread_id']) ? trim((string) $_GET['thread_id']) : '';
    $payload = [
        'exported_at' => gmdate('c'),
        'filters' => $snapshot['summary'],
        'threads' => $snapshot['items'],
    ];
    if ($threadId !== '') {
        $payload['thread_detail'] = social_inbox_thread_detail_snapshot($threadId);
    }
    audit_event('social', 'inbox.export', [
        'filtered_count' => (int) ($snapshot['summary']['filtered_count'] ?? 0),
        'thread_id' => $threadId,
    ]);
    out_json([
        'ok' => true,
        'filename' => 'social_inbox_export_' . gmdate('Ymd_His') . '.json',
        'export' => $payload,
    ]);
}

if ($action === 'social.inbox.summary') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = social_inbox_summary_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'providers' => $snapshot['providers'],
        'oldest_open' => $snapshot['oldest_open'],
        'oldest_backlog' => $snapshot['oldest_backlog'],
        'open_threads' => $snapshot['open_threads'],
        'backlog_threads' => $snapshot['backlog_threads'],
    ]);
}

if ($action === 'social.inbox.workload') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = social_inbox_workload_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'priority_counts' => $snapshot['priority_counts'],
        'owners' => $snapshot['owners'],
        'unassigned_threads' => $snapshot['unassigned_threads'],
    ]);
}

if ($action === 'social.inbox.watch.summary') {
    $state = app_read_json_file(social_inbox_watch_state_path(), []);
    $runs = app_read_json_file(social_inbox_watch_runs_path(), []);
    out_json([
        'ok' => true,
        'state' => $state,
        'latest_run' => isset($runs[0]) && is_array($runs[0]) ? $runs[0] : null,
        'runs_count' => count($runs),
    ]);
}

if ($action === 'social.inbox.watch.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    $staleAfterHours = isset($data['stale_after_hours']) ? (int) $data['stale_after_hours'] : 24;
    $watch = social_inbox_watch_snapshot('manual_run', true, $staleAfterHours);
    out_json([
        'ok' => true,
        'run' => $watch['run'],
        'state' => $watch['state'],
    ]);
}

if ($action === 'social.inbox.reply') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['thread_id']) || trim((string) ($data['message'] ?? '')) === '') {
        out_json(['ok' => false, 'error' => 'thread_id and message are required.'], 400);
    }
    $threadId = (string) $data['thread_id'];
    $replyMessage = trim((string) $data['message']);
    $rows = social_inbox_threads_rows(true);
    $updated = null;
    foreach ($rows as $index => $row) {
        if (!is_array($row) || (string) ($row['thread_id'] ?? '') !== $threadId) {
            continue;
        }
        $row = social_normalize_inbox_thread($row);
        $connector = social_connector_by_id((string) ($row['connector_id'] ?? ''));
        $decorated = is_array($connector) ? decorate_social_connector($connector) : null;
        $capabilities = is_array($decorated) && isset($decorated['capabilities_enabled']) && is_array($decorated['capabilities_enabled'])
            ? $decorated['capabilities_enabled']
            : [];
        if (!in_array('can_reply_inbox', $capabilities, true)) {
            out_json(['ok' => false, 'error' => 'Connector cannot reply to inbox threads.'], 400);
        }
        $previousStatus = (string) ($row['status'] ?? 'open');
        $row['status'] = 'replied';
        $row['reply_message'] = $replyMessage;
        $row['last_reply_at'] = gmdate('c');
        if ($previousStatus !== 'replied') {
            $row['last_status_at'] = gmdate('c');
        }
        $row['updated_at'] = gmdate('c');
        $rows[$index] = $row;
        $updated = $row;
        break;
    }
    if (!is_array($updated)) {
        out_json(['ok' => false, 'error' => 'Inbox thread not found.'], 404);
    }
    app_write_json_file(social_inbox_threads_path(), $rows);
    audit_event('social', 'inbox.reply', ['thread_id' => $threadId]);
    record_social_activity('inbox_reply', 'Social inbox reply recorded.', ['thread_id' => $threadId]);
    out_json([
        'ok' => true,
        'thread' => $updated,
    ]);
}

if ($action === 'social.inbox.update') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['thread_id'])) {
        out_json(['ok' => false, 'error' => 'thread_id is required.'], 400);
    }

    $threadId = trim((string) $data['thread_id']);
    $statusProvided = array_key_exists('status', $data);
    $priorityProvided = array_key_exists('priority', $data);
    $ownerProvided = array_key_exists('owner', $data);
    $noteProvided = array_key_exists('internal_note', $data);
    if (!$statusProvided && !$priorityProvided && !$ownerProvided && !$noteProvided) {
        out_json(['ok' => false, 'error' => 'At least one update field is required.'], 400);
    }

    $nextStatus = strtolower(trim((string) ($data['status'] ?? '')));
    if ($statusProvided && !in_array($nextStatus, social_inbox_status_values(), true)) {
        out_json(['ok' => false, 'error' => 'Invalid inbox status.'], 400);
    }
    $nextPriority = strtolower(trim((string) ($data['priority'] ?? '')));
    if ($priorityProvided && !in_array($nextPriority, social_inbox_priority_values(), true)) {
        out_json(['ok' => false, 'error' => 'Invalid inbox priority.'], 400);
    }

    $rows = social_inbox_threads_rows(true);
    $updated = null;
    $changes = [];
    foreach ($rows as $index => $row) {
        if (!is_array($row) || (string) ($row['thread_id'] ?? '') !== $threadId) {
            continue;
        }
        $row = social_normalize_inbox_thread($row);
        $changed = false;
        if ($statusProvided && $nextStatus !== '' && $nextStatus !== (string) ($row['status'] ?? 'open')) {
            $changes['status'] = [
                'from' => (string) ($row['status'] ?? 'open'),
                'to' => $nextStatus,
            ];
            $row['status'] = $nextStatus;
            $row['last_status_at'] = gmdate('c');
            $changed = true;
        }
        if ($priorityProvided && $nextPriority !== '' && $nextPriority !== (string) ($row['priority'] ?? 'normal')) {
            $changes['priority'] = [
                'from' => (string) ($row['priority'] ?? 'normal'),
                'to' => $nextPriority,
            ];
            $row['priority'] = $nextPriority;
            $changed = true;
        }
        if ($ownerProvided) {
            $ownerValue = substr(trim((string) ($data['owner'] ?? '')), 0, 120);
            if ($ownerValue !== (string) ($row['owner'] ?? '')) {
                $changes['owner'] = [
                    'from' => (string) ($row['owner'] ?? ''),
                    'to' => $ownerValue,
                ];
                $row['owner'] = $ownerValue;
                $changed = true;
            }
        }
        if ($noteProvided) {
            $noteValue = substr(trim((string) ($data['internal_note'] ?? '')), 0, 1000);
            if ($noteValue !== (string) ($row['internal_note'] ?? '')) {
                $changes['internal_note'] = [
                    'from' => (string) ($row['internal_note'] ?? ''),
                    'to' => $noteValue,
                ];
                $row['internal_note'] = $noteValue;
                $changed = true;
            }
        }
        if ($changed) {
            $row['updated_at'] = gmdate('c');
        }
        $rows[$index] = $row;
        $updated = $row;
        break;
    }
    if (!is_array($updated)) {
        out_json(['ok' => false, 'error' => 'Inbox thread not found.'], 404);
    }

    app_write_json_file(social_inbox_threads_path(), $rows);
    audit_event('social', 'inbox.update', [
        'thread_id' => $threadId,
        'changes' => $changes,
    ]);
    record_social_activity('inbox_update', 'Social inbox thread updated.', [
        'thread_id' => $threadId,
        'changes' => $changes,
    ]);
    out_json([
        'ok' => true,
        'thread' => $updated,
        'changes' => $changes,
    ]);
}

if ($action === 'social.inbox.bulk_update') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    $snapshot = social_inbox_bulk_update($data);
    audit_event('social', 'inbox.bulk_update', [
        'processed' => (int) ($snapshot['processed'] ?? 0),
        'updated' => (int) ($snapshot['updated'] ?? 0),
    ]);
    record_social_activity('inbox_bulk_update', 'Social inbox bulk update applied.', [
        'processed' => (int) ($snapshot['processed'] ?? 0),
        'updated' => (int) ($snapshot['updated'] ?? 0),
    ]);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 400);
}

if ($action === 'social.schedule.save') {
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
        'schedule_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['schedule_id'] ?? uniqid('social_schedule_', false))),
        'title' => trim((string) ($data['title'] ?? '')),
        'message' => trim((string) ($data['message'] ?? '')),
        'url' => trim((string) ($data['url'] ?? '')),
        'connector_ids' => [],
        'scheduled_for' => trim((string) ($data['scheduled_for'] ?? '')),
        'status' => strtolower(trim((string) ($data['status'] ?? 'queued'))),
        'attempts' => isset($data['attempts']) ? max(0, (int) $data['attempts']) : 0,
        'last_run_at' => trim((string) ($data['last_run_at'] ?? '')),
        'created_at' => trim((string) ($data['created_at'] ?? '')) !== '' ? trim((string) $data['created_at']) : gmdate('c'),
        'updated_at' => gmdate('c'),
    ];
    if ($item['message'] === '') {
        out_json(['ok' => false, 'error' => 'message is required.'], 400);
    }
    $connectorIds = isset($data['connector_ids']) && is_array($data['connector_ids']) ? $data['connector_ids'] : [];
    foreach ($connectorIds as $value) {
        $id = preg_replace('/[^a-z0-9_\-]/i', '', (string) $value);
        if ($id !== '' && !in_array($id, $item['connector_ids'], true)) {
            $item['connector_ids'][] = $id;
        }
    }
    $targetValidation = social_schedule_target_validation_snapshot($item['connector_ids']);
    if (empty($targetValidation['ok'])) {
        out_json([
            'ok' => false,
            'error' => 'Schedule target validation failed.',
            'validation' => $targetValidation,
        ], 400);
    }
    $rows = app_read_json_file(social_schedule_queue_path(), []);
    $rows = array_values(array_filter($rows, static function ($row) use ($item) {
        return (string) ($row['schedule_id'] ?? '') !== $item['schedule_id'];
    }));
    $rows[] = $item;
    usort($rows, static function ($a, $b) {
        return strcmp((string) ($a['scheduled_for'] ?? ''), (string) ($b['scheduled_for'] ?? ''));
    });
    app_write_json_file(social_schedule_queue_path(), array_slice($rows, 0, 500));
    audit_event('social', 'schedule.save', ['schedule_id' => (string) $item['schedule_id'], 'connector_count' => count($item['connector_ids'])]);
    record_social_activity('schedule_saved', 'Scheduled social post saved.', ['schedule_id' => (string) $item['schedule_id'], 'connector_count' => count($item['connector_ids'])]);
    out_json(['ok' => true, 'item' => $item]);
}

if ($action === 'social.schedule.delete') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['schedule_id'])) {
        out_json(['ok' => false, 'error' => 'schedule_id is required.'], 400);
    }
    $scheduleId = (string) $data['schedule_id'];
    $rows = app_read_json_file(social_schedule_queue_path(), []);
    $before = count($rows);
    $rows = array_values(array_filter($rows, static function ($row) use ($scheduleId) {
        return (string) ($row['schedule_id'] ?? '') !== $scheduleId;
    }));
    app_write_json_file(social_schedule_queue_path(), $rows);
    audit_event('social', 'schedule.delete', ['schedule_id' => $scheduleId]);
    record_social_activity('schedule_deleted', 'Scheduled social post deleted.', ['schedule_id' => $scheduleId]);
    out_json([
        'ok' => true,
        'deleted' => $before - count($rows),
        'schedule_id' => $scheduleId,
    ]);
}

if ($action === 'social.schedule.bulk_update') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    $snapshot = social_schedule_bulk_update($data);
    audit_event('social', 'schedule.bulk_update', [
        'processed' => (int) ($snapshot['processed'] ?? 0),
        'updated' => (int) ($snapshot['updated'] ?? 0),
    ]);
    record_social_activity('schedule_bulk_update', 'Social schedule bulk update applied.', [
        'processed' => (int) ($snapshot['processed'] ?? 0),
        'updated' => (int) ($snapshot['updated'] ?? 0),
    ]);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 400);
}

if ($action === 'social.schedule.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $run = execute_social_schedule_queue('scheduled_queue');
    audit_event('social', 'schedule.run', [
        'processed' => (int) ($run['processed'] ?? 0),
        'sent' => (int) ($run['sent'] ?? 0),
        'run_id' => (string) ($run['run_id'] ?? ''),
    ]);
    out_json([
        'ok' => true,
        'processed' => (int) ($run['processed'] ?? 0),
        'sent' => (int) ($run['sent'] ?? 0),
        'failed' => (int) ($run['failed'] ?? 0),
        'run_id' => (string) ($run['run_id'] ?? ''),
        'items' => isset($run['items']) && is_array($run['items']) ? $run['items'] : [],
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
    audit_event('seo', 'projects.save', ['project_id' => (string) $item['project_id'], 'domain' => (string) $item['domain']]);
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
    audit_event('seo', 'projects.delete', ['project_id' => $projectId]);
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
    $watch = seo_regression_watch_snapshot('manual_audit', true);
    audit_event('seo', 'audit.run', ['project_id' => (string) $project['project_id'], 'audit_id' => (string) $audit['audit_id']]);
    out_json([
        'ok' => true,
        'audit' => $audit,
        'regression_watch' => $watch['run'],
    ]);
}

if ($action === 'seo.extension.session.create') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $session = [
        'session_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['session_id'] ?? uniqid('seo_ext_session_', false))),
        'project_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['project_id'] ?? '')),
        'label' => trim((string) ($data['label'] ?? 'Extension Session')),
        'token' => bin2hex(random_bytes(24)),
        'status' => 'active',
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'last_used_at' => '',
    ];
    $rows = app_read_json_file(seo_extension_sessions_path(), []);
    $rows = array_values(array_filter($rows, static function ($row) use ($session) {
        return (string) ($row['session_id'] ?? '') !== $session['session_id'];
    }));
    array_unshift($rows, $session);
    $rows = array_slice($rows, 0, 300);
    app_write_json_file(seo_extension_sessions_path(), $rows);
    audit_event('seo', 'extension.session.create', ['session_id' => (string) $session['session_id'], 'project_id' => (string) $session['project_id']]);
    out_json([
        'ok' => true,
        'session' => $session,
    ]);
}

if ($action === 'seo.extension.session.revoke') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['session_id'])) {
        out_json(['ok' => false, 'error' => 'session_id is required.'], 400);
    }
    $sessionId = (string) $data['session_id'];
    $rows = app_read_json_file(seo_extension_sessions_path(), []);
    $updated = null;
    foreach ($rows as $index => $row) {
        if (!is_array($row) || (string) ($row['session_id'] ?? '') !== $sessionId) {
            continue;
        }
        $row['status'] = 'revoked';
        $row['updated_at'] = gmdate('c');
        $rows[$index] = $row;
        $updated = seo_extension_mask_session($row);
        break;
    }
    if (!is_array($updated)) {
        out_json(['ok' => false, 'error' => 'Extension session not found.'], 404);
    }
    app_write_json_file(seo_extension_sessions_path(), $rows);
    audit_event('seo', 'extension.session.revoke', ['session_id' => $sessionId]);
    out_json([
        'ok' => true,
        'session' => $updated,
    ]);
}

if ($action === 'seo.extension.intake') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    $expected = (string) ($config['seo_extension_ingest_key'] ?? '');
    $incoming = isset($_SERVER['HTTP_X_SEO_EXTENSION_KEY']) ? (string) $_SERVER['HTTP_X_SEO_EXTENSION_KEY'] : '';
    $session = seo_extension_session_by_token($incoming);
    $masterOk = ($expected !== '' && $incoming !== '' && hash_equals($expected, $incoming));
    if (!$masterOk && !is_array($session)) {
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
        'session_id' => is_array($session) ? (string) ($session['session_id'] ?? '') : '',
        'source' => 'browser_extension',
        'created_at' => gmdate('c'),
    ];
    $event['page_signals'] = seo_normalize_page_signals(isset($data['page_signals']) && is_array($data['page_signals']) ? $data['page_signals'] : []);
    if ($event['title'] !== '' && (string) ($event['page_signals']['title'] ?? '') === '') {
        $event['page_signals']['title'] = $event['title'];
        $event['page_signals']['title_length'] = strlen($event['title']);
    }
    $eventRollup = seo_issue_rollup_from_checks(is_array($event['issues']) ? $event['issues'] : []);
    $event['priority_summary'] = $eventRollup['priority_summary'];
    $event['issue_rollup'] = $eventRollup['issues'];
    if (is_array($session)) {
        $rows = app_read_json_file(seo_extension_sessions_path(), []);
        foreach ($rows as $index => $row) {
            if (!is_array($row) || (string) ($row['session_id'] ?? '') !== (string) ($session['session_id'] ?? '')) {
                continue;
            }
            $row['last_used_at'] = gmdate('c');
            $row['updated_at'] = gmdate('c');
            $rows[$index] = $row;
            break;
        }
        app_write_json_file(seo_extension_sessions_path(), $rows);
        if ($event['project_id'] === '' && !empty($session['project_id'])) {
            $event['project_id'] = (string) $session['project_id'];
        }
    }
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
            'critical_issues' => (int) ($eventRollup['priority_summary']['critical'] ?? 0),
            'warnings' => is_array($event['issues']) ? count($event['issues']) : 0,
            'page_signals' => $event['page_signals'],
            'priority_summary' => $eventRollup['priority_summary'],
            'issue_rollup' => $eventRollup['issues'],
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

if ($action === 'seo.extension.events.summary') {
    $events = app_read_json_file(seo_extension_events_path(), []);
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    if ($projectId !== '') {
        $events = array_values(array_filter($events, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
    }
    $scoreTotal = 0;
    $projectMap = [];
    $issueMap = [];
    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $scoreTotal += (int) ($event['score'] ?? 0);
        $pid = (string) ($event['project_id'] ?? '');
        if ($pid === '') {
            $pid = 'unassigned';
        }
        if (!isset($projectMap[$pid])) {
            $projectMap[$pid] = [
                'project_id' => $pid,
                'event_count' => 0,
                'average_score' => 0,
                'total_score' => 0,
            ];
        }
        $projectMap[$pid]['event_count']++;
        $projectMap[$pid]['total_score'] += (int) ($event['score'] ?? 0);
        $rollup = isset($event['issue_rollup']) && is_array($event['issue_rollup'])
            ? $event['issue_rollup']
            : seo_issue_rollup_from_checks(isset($event['issues']) && is_array($event['issues']) ? $event['issues'] : [])['issues'];
        foreach ($rollup as $issue) {
            if (!is_array($issue)) {
                continue;
            }
            $check = (string) ($issue['check'] ?? 'unknown');
            if (!isset($issueMap[$check])) {
                $issueMap[$check] = [
                    'check' => $check,
                    'occurrences' => 0,
                    'priority' => (string) ($issue['priority'] ?? 'nice_to_have'),
                ];
            }
            $issueMap[$check]['occurrences'] += (int) ($issue['occurrences'] ?? 0);
            $currentPriority = (string) ($issueMap[$check]['priority'] ?? 'nice_to_have');
            $nextPriority = (string) ($issue['priority'] ?? 'nice_to_have');
            if ($currentPriority !== 'critical' && ($nextPriority === 'critical' || ($currentPriority === 'nice_to_have' && $nextPriority === 'fix_soon'))) {
                $issueMap[$check]['priority'] = $nextPriority;
            }
        }
    }
    foreach ($projectMap as $key => $row) {
        $projectMap[$key]['average_score'] = $row['event_count'] > 0 ? round($row['total_score'] / $row['event_count'], 2) : 0;
        unset($projectMap[$key]['total_score']);
    }
    $issues = array_values($issueMap);
    usort($issues, static function ($a, $b) {
        return (int) ($b['occurrences'] ?? 0) <=> (int) ($a['occurrences'] ?? 0);
    });
    out_json([
        'ok' => true,
        'event_count' => count($events),
        'average_score' => count($events) > 0 ? round($scoreTotal / count($events), 2) : 0,
        'projects' => array_values($projectMap),
        'top_issues' => array_slice($issues, 0, 20),
        'latest_event' => isset($events[0]) && is_array($events[0]) ? $events[0] : null,
        'project_id' => $projectId,
    ]);
}

if ($action === 'seo.report.export') {
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    $projects = app_read_json_file(seo_projects_path(), []);
    $audits = app_read_json_file(seo_audits_path(), []);
    $events = app_read_json_file(seo_extension_events_path(), []);
    if ($projectId !== '') {
        $projects = array_values(array_filter($projects, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
        $audits = array_values(array_filter($audits, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
        $events = array_values(array_filter($events, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
    }

    $prioritySummary = ['critical' => 0, 'fix_soon' => 0, 'nice_to_have' => 0];
    foreach ($audits as $audit) {
        if (!is_array($audit)) {
            continue;
        }
        $summary = isset($audit['priority_summary']) && is_array($audit['priority_summary'])
            ? $audit['priority_summary']
            : seo_issue_rollup_from_checks(isset($audit['checks']) && is_array($audit['checks']) ? $audit['checks'] : [])['priority_summary'];
        foreach (['critical', 'fix_soon', 'nice_to_have'] as $key) {
            $prioritySummary[$key] += (int) ($summary[$key] ?? 0);
        }
    }

    $averageAuditScore = count($audits) > 0
        ? round(array_sum(array_map(static function ($row) {
            return (int) ($row['score'] ?? 0);
        }, $audits)) / count($audits), 2)
        : 0;

    $payload = [
        'generated_at' => gmdate('c'),
        'project_id' => $projectId,
        'summary' => [
            'project_count' => count($projects),
            'audit_count' => count($audits),
            'extension_event_count' => count($events),
            'average_audit_score' => $averageAuditScore,
            'priority_summary' => $prioritySummary,
        ],
        'projects' => $projects,
        'latest_audits' => array_slice($audits, 0, 25),
        'latest_extension_events' => array_slice($events, 0, 25),
    ];

    out_json([
        'ok' => true,
        'filename' => 'seo-report-' . ($projectId !== '' ? $projectId . '-' : '') . gmdate('Ymd-His') . '.json',
        'export' => $payload,
    ]);
}

if ($action === 'seo.project.snapshot') {
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    $projects = app_read_json_file(seo_projects_path(), []);
    $project = null;
    if ($projectId !== '') {
        foreach ($projects as $row) {
            if (is_array($row) && (string) ($row['project_id'] ?? '') === $projectId) {
                $project = $row;
                break;
            }
        }
    }
    if (!is_array($project)) {
        $project = isset($projects[0]) && is_array($projects[0]) ? $projects[0] : null;
    }
    if (!is_array($project)) {
        out_json([
            'ok' => true,
            'project' => null,
            'message' => 'No SEO project configured.',
        ]);
    }
    $selectedProjectId = (string) ($project['project_id'] ?? '');
    $audits = array_values(array_filter(app_read_json_file(seo_audits_path(), []), static function ($row) use ($selectedProjectId) {
        return (string) ($row['project_id'] ?? '') === $selectedProjectId;
    }));
    $events = array_values(array_filter(app_read_json_file(seo_extension_events_path(), []), static function ($row) use ($selectedProjectId) {
        return (string) ($row['project_id'] ?? '') === $selectedProjectId;
    }));
    $sessions = array_values(array_filter(app_read_json_file(seo_extension_sessions_path(), []), static function ($row) use ($selectedProjectId) {
        return (string) ($row['project_id'] ?? '') === $selectedProjectId || ((string) ($row['project_id'] ?? '') === '' && $selectedProjectId === '');
    }));
    $issueSummary = ['critical' => 0, 'fix_soon' => 0, 'nice_to_have' => 0];
    foreach ($audits as $audit) {
        if (!is_array($audit)) {
            continue;
        }
        $priorities = isset($audit['priority_summary']) && is_array($audit['priority_summary'])
            ? $audit['priority_summary']
            : seo_issue_rollup_from_checks(isset($audit['checks']) && is_array($audit['checks']) ? $audit['checks'] : [])['priority_summary'];
        foreach (['critical', 'fix_soon', 'nice_to_have'] as $key) {
            $issueSummary[$key] += (int) ($priorities[$key] ?? 0);
        }
    }
    $latestAudit = isset($audits[0]) && is_array($audits[0]) ? $audits[0] : null;
    $latestEvent = isset($events[0]) && is_array($events[0]) ? $events[0] : null;
    $averageScore = count($audits) > 0 ? round(array_sum(array_map(static function ($row) {
        return (int) ($row['score'] ?? 0);
    }, $audits)) / count($audits), 2) : 0;
    out_json([
        'ok' => true,
        'project' => $project,
        'audit_count' => count($audits),
        'extension_event_count' => count($events),
        'extension_session_count' => count($sessions),
        'average_score' => $averageScore,
        'issue_summary' => $issueSummary,
        'latest_audit' => $latestAudit,
        'latest_extension_event' => $latestEvent,
    ]);
}

if ($action === 'seo.actions.plan') {
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    $projects = app_read_json_file(seo_projects_path(), []);
    $project = null;
    if ($projectId !== '') {
        foreach ($projects as $row) {
            if (is_array($row) && (string) ($row['project_id'] ?? '') === $projectId) {
                $project = $row;
                break;
            }
        }
    }
    if (!is_array($project)) {
        $project = isset($projects[0]) && is_array($projects[0]) ? $projects[0] : null;
    }
    if (!is_array($project)) {
        out_json([
            'ok' => true,
            'project' => null,
            'message' => 'No SEO project configured.',
            'summary' => ['new' => 0, 'persistent' => 0, 'monitor' => 0],
            'actions' => [],
        ]);
    }
    $selectedProjectId = (string) ($project['project_id'] ?? '');
    $audits = array_values(array_filter(app_read_json_file(seo_audits_path(), []), static function ($row) use ($selectedProjectId) {
        return (string) ($row['project_id'] ?? '') === $selectedProjectId;
    }));
    $latest = isset($audits[0]) && is_array($audits[0]) ? $audits[0] : null;
    $previous = isset($audits[1]) && is_array($audits[1]) ? $audits[1] : null;
    $plan = seo_action_plan_from_audits($project, $latest, $previous);
    out_json([
        'ok' => true,
        'project' => $project,
        'generated_at' => gmdate('c'),
        'latest_audit_id' => is_array($latest) ? (string) ($latest['audit_id'] ?? '') : '',
        'previous_audit_id' => is_array($previous) ? (string) ($previous['audit_id'] ?? '') : '',
        'score_delta' => (is_array($latest) && is_array($previous)) ? ((int) ($latest['score'] ?? 0) - (int) ($previous['score'] ?? 0)) : null,
        'summary' => $plan['summary'],
        'actions' => $plan['actions'],
        'message' => is_array($latest) ? '' : 'No SEO audit available yet for action planning.',
    ]);
}

if ($action === 'seo.url.history') {
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    $urlFilter = isset($_GET['url']) ? seo_normalize_history_url((string) $_GET['url']) : '';
    $projects = app_read_json_file(seo_projects_path(), []);
    $project = null;
    if ($projectId !== '') {
        foreach ($projects as $row) {
            if (is_array($row) && (string) ($row['project_id'] ?? '') === $projectId) {
                $project = $row;
                break;
            }
        }
    }
    if (!is_array($project)) {
        $project = isset($projects[0]) && is_array($projects[0]) ? $projects[0] : null;
    }
    if (!is_array($project)) {
        out_json([
            'ok' => true,
            'project' => null,
            'summary' => ['url_count' => 0, 'audit_count' => 0],
            'items' => [],
            'timeline' => [],
            'message' => 'No SEO project configured.',
        ]);
    }

    $selectedProjectId = (string) ($project['project_id'] ?? '');
    $audits = array_values(array_filter(app_read_json_file(seo_audits_path(), []), static function ($row) use ($selectedProjectId) {
        return (string) ($row['project_id'] ?? '') === $selectedProjectId;
    }));
    $snapshot = seo_project_url_history_snapshot($project, $audits, $urlFilter);
    $items = $snapshot['items'];
    $timeline = $snapshot['timeline'];

    out_json([
        'ok' => true,
        'project' => $project,
        'filter_url' => $urlFilter,
        'summary' => [
            'url_count' => count($items),
            'audit_count' => array_sum(array_map(static function ($item) {
                return (int) ($item['audit_count'] ?? 0);
            }, $items)),
        ],
        'items' => $items,
        'timeline' => array_slice($timeline, 0, 25),
        'message' => empty($items) ? 'No matching URL history found yet.' : '',
    ]);
}

if ($action === 'seo.opportunities.summary') {
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    $projects = app_read_json_file(seo_projects_path(), []);
    $project = null;
    if ($projectId !== '') {
        foreach ($projects as $row) {
            if (is_array($row) && (string) ($row['project_id'] ?? '') === $projectId) {
                $project = $row;
                break;
            }
        }
    }
    if (!is_array($project)) {
        $project = isset($projects[0]) && is_array($projects[0]) ? $projects[0] : null;
    }
    if (!is_array($project)) {
        out_json([
            'ok' => true,
            'project' => null,
            'summary' => ['recurring_issue_count' => 0, 'url_count' => 0, 'audit_count' => 0],
            'recurring_issues' => [],
            'lowest_scoring_urls' => [],
            'declining_urls' => [],
            'message' => 'No SEO project configured.',
        ]);
    }

    $selectedProjectId = (string) ($project['project_id'] ?? '');
    $audits = array_values(array_filter(app_read_json_file(seo_audits_path(), []), static function ($row) use ($selectedProjectId) {
        return (string) ($row['project_id'] ?? '') === $selectedProjectId;
    }));
    $historySnapshot = seo_project_url_history_snapshot($project, $audits, '');
    $recurring = [];

    foreach ($audits as $audit) {
        if (!is_array($audit)) {
            continue;
        }
        $url = seo_normalize_history_url((string) ($audit['domain'] ?? ''));
        if ($url === '') {
            $url = seo_normalize_history_url((string) ($project['domain'] ?? ''));
        }
        $issues = seo_issue_map_from_audit($audit);
        foreach ($issues as $check => $issue) {
            $playbook = seo_issue_playbook($check);
            if (!isset($recurring[$check])) {
                $recurring[$check] = [
                    'check' => $check,
                    'title' => (string) ($playbook['title'] ?? $check),
                    'priority' => (string) ($issue['priority'] ?? 'nice_to_have'),
                    'occurrences' => 0,
                    'affected_urls' => [],
                    'latest_seen_at' => '',
                    'recommendation' => (string) ($playbook['recommendation'] ?? ''),
                    'suggested_owner' => (string) ($playbook['owner'] ?? 'technical_seo'),
                ];
            }
            $recurring[$check]['occurrences'] += max(1, (int) ($issue['occurrences'] ?? 0));
            if ($url !== '') {
                $recurring[$check]['affected_urls'][$url] = true;
            }
            $createdAt = (string) ($audit['created_at'] ?? '');
            if ($createdAt !== '' && ((string) ($recurring[$check]['latest_seen_at'] ?? '') === '' || strcmp($createdAt, (string) $recurring[$check]['latest_seen_at']) > 0)) {
                $recurring[$check]['latest_seen_at'] = $createdAt;
            }
            if (seo_priority_rank((string) ($issue['priority'] ?? 'nice_to_have')) > seo_priority_rank((string) ($recurring[$check]['priority'] ?? 'nice_to_have'))) {
                $recurring[$check]['priority'] = (string) ($issue['priority'] ?? 'nice_to_have');
            }
        }
    }

    $recurring = array_values(array_map(static function ($item) {
        $item['affected_url_count'] = count((array) ($item['affected_urls'] ?? []));
        unset($item['affected_urls']);
        return $item;
    }, $recurring));
    usort($recurring, static function ($left, $right) {
        $priorityDiff = seo_priority_rank((string) ($right['priority'] ?? 'nice_to_have')) <=> seo_priority_rank((string) ($left['priority'] ?? 'nice_to_have'));
        if ($priorityDiff !== 0) {
            return $priorityDiff;
        }
        $urlDiff = (int) ($right['affected_url_count'] ?? 0) <=> (int) ($left['affected_url_count'] ?? 0);
        if ($urlDiff !== 0) {
            return $urlDiff;
        }
        return (int) ($right['occurrences'] ?? 0) <=> (int) ($left['occurrences'] ?? 0);
    });

    $lowestUrls = $historySnapshot['items'];
    usort($lowestUrls, static function ($left, $right) {
        $leftScore = isset($left['latest_score']) && $left['latest_score'] !== null ? (int) $left['latest_score'] : 999;
        $rightScore = isset($right['latest_score']) && $right['latest_score'] !== null ? (int) $right['latest_score'] : 999;
        if ($leftScore !== $rightScore) {
            return $leftScore <=> $rightScore;
        }
        return strcmp((string) ($left['url'] ?? ''), (string) ($right['url'] ?? ''));
    });

    $decliningUrls = array_values(array_filter($historySnapshot['items'], static function ($item) {
        return isset($item['score_delta']) && $item['score_delta'] !== null && (int) $item['score_delta'] < 0;
    }));
    usort($decliningUrls, static function ($left, $right) {
        return (int) ($left['score_delta'] ?? 0) <=> (int) ($right['score_delta'] ?? 0);
    });

    out_json([
        'ok' => true,
        'project' => $project,
        'summary' => [
            'recurring_issue_count' => count($recurring),
            'url_count' => count($historySnapshot['items']),
            'audit_count' => count($audits),
        ],
        'recurring_issues' => array_slice($recurring, 0, 20),
        'lowest_scoring_urls' => array_slice($lowestUrls, 0, 10),
        'declining_urls' => array_slice($decliningUrls, 0, 10),
        'message' => empty($audits) ? 'No SEO audits available yet for opportunity analysis.' : '',
    ]);
}

if ($action === 'seo.regressions.summary') {
    $state = app_read_json_file(seo_regression_state_path(), []);
    $runs = app_read_json_file(seo_regression_runs_path(), []);
    out_json([
        'ok' => true,
        'state' => is_array($state) ? $state : [],
        'latest_run' => isset($runs[0]) && is_array($runs[0]) ? $runs[0] : null,
        'runs' => array_slice(is_array($runs) ? $runs : [], 0, 25),
    ]);
}

if ($action === 'seo.regressions.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $watch = seo_regression_watch_snapshot('manual_run', true);
    out_json([
        'ok' => true,
        'state' => $watch['state'],
        'run' => $watch['run'],
    ]);
}

if ($action === 'seo.compare.latest') {
    $projectId = isset($_GET['project_id']) ? (string) $_GET['project_id'] : '';
    $audits = app_read_json_file(seo_audits_path(), []);
    if ($projectId !== '') {
        $audits = array_values(array_filter($audits, static function ($row) use ($projectId) {
            return (string) ($row['project_id'] ?? '') === $projectId;
        }));
    }
    $latest = isset($audits[0]) && is_array($audits[0]) ? $audits[0] : null;
    $previous = isset($audits[1]) && is_array($audits[1]) ? $audits[1] : null;
    if (!is_array($latest)) {
        out_json([
            'ok' => true,
            'message' => 'No SEO audit available for comparison.',
            'latest' => null,
            'previous' => null,
        ]);
    }
    $latestChecks = [];
    foreach ((array) ($latest['issue_rollup'] ?? []) as $issue) {
        if (is_array($issue) && !empty($issue['check'])) {
            $latestChecks[(string) $issue['check']] = (string) ($issue['priority'] ?? 'nice_to_have');
        }
    }
    if (empty($latestChecks)) {
        foreach ((array) ($latest['checks'] ?? []) as $check) {
            if (!is_array($check)) {
                continue;
            }
            $latestChecks[(string) ($check['check'] ?? 'unknown')] = seo_check_priority($check);
        }
    }
    $previousChecks = [];
    if (is_array($previous)) {
        foreach ((array) ($previous['issue_rollup'] ?? []) as $issue) {
            if (is_array($issue) && !empty($issue['check'])) {
                $previousChecks[(string) $issue['check']] = (string) ($issue['priority'] ?? 'nice_to_have');
            }
        }
        if (empty($previousChecks)) {
            foreach ((array) ($previous['checks'] ?? []) as $check) {
                if (!is_array($check)) {
                    continue;
                }
                $previousChecks[(string) ($check['check'] ?? 'unknown')] = seo_check_priority($check);
            }
        }
    }
    $added = array_values(array_diff(array_keys($latestChecks), array_keys($previousChecks)));
    $cleared = array_values(array_diff(array_keys($previousChecks), array_keys($latestChecks)));
    $changed = [];
    foreach ($latestChecks as $check => $priority) {
        if (isset($previousChecks[$check]) && $previousChecks[$check] !== $priority) {
            $changed[] = [
                'check' => $check,
                'from' => $previousChecks[$check],
                'to' => $priority,
            ];
        }
    }
    out_json([
        'ok' => true,
        'project_id' => (string) ($latest['project_id'] ?? $projectId),
        'latest' => $latest,
        'previous' => $previous,
        'score_delta' => is_array($previous) ? ((int) ($latest['score'] ?? 0) - (int) ($previous['score'] ?? 0)) : null,
        'added_checks' => $added,
        'cleared_checks' => $cleared,
        'changed_priorities' => $changed,
    ]);
}

if ($action === 'social.connectors.list') {
    $rows = app_read_json_file(social_connectors_path(), []);
    $publicRows = array_map('decorate_social_connector', $rows);
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
        'account_label' => trim((string) ($data['account_label'] ?? '')),
        'expires_at' => trim((string) ($data['expires_at'] ?? '')),
        'type' => strtolower(trim((string) ($data['type'] ?? 'external_api'))),
        'status' => strtolower(trim((string) ($data['status'] ?? 'planned'))),
        'auth_mode' => strtolower(trim((string) ($data['auth_mode'] ?? 'api_key'))),
        'capabilities' => [],
        'site_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['site_id'] ?? '')),
        'config' => sanitize_connector_config($data['config'] ?? []),
        'updated_at' => gmdate('c'),
    ];
    $profile = social_platform_profile((string) $item['provider']);
    if ($item['type'] === '') {
        $item['type'] = (string) ($profile['default_type'] ?? 'external_api');
    }
    if ($item['account_label'] === '') {
        $item['account_label'] = (string) ($profile['label'] ?? 'Connector');
    }
    $allowedAuthModes = isset($profile['auth_modes']) && is_array($profile['auth_modes']) ? $profile['auth_modes'] : [];
    if (!empty($allowedAuthModes) && !in_array($item['auth_mode'], $allowedAuthModes, true)) {
        $item['auth_mode'] = (string) $allowedAuthModes[0];
    }
    $item['capabilities'] = normalize_social_capabilities((string) $item['provider'], $data['capabilities'] ?? []);
    $rows = app_read_json_file(social_connectors_path(), []);
    $rows = array_values(array_filter($rows, static function ($row) use ($item) {
        return (string) ($row['connector_id'] ?? '') !== $item['connector_id'];
    }));
    $rows[] = $item;
    app_write_json_file(social_connectors_path(), $rows);
    audit_event('social', 'connectors.save', ['connector_id' => (string) $item['connector_id'], 'provider' => (string) $item['provider'], 'capability_count' => count($item['capabilities'])]);
    record_social_activity('connector_saved', 'Social connector saved.', ['connector_id' => (string) $item['connector_id'], 'provider' => (string) $item['provider']]);
    out_json(['ok' => true, 'item' => decorate_social_connector($item)]);
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
    audit_event('social', 'connectors.delete', ['connector_id' => $connectorId]);
    record_social_activity('connector_deleted', 'Social connector deleted.', ['connector_id' => $connectorId]);
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
    record_social_activity('connector_tested', 'Social connector test executed.', ['connector_id' => (string) ($connector['connector_id'] ?? ''), 'provider' => (string) ($connector['provider'] ?? '')]);
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
            enqueue_social_retry_item(build_social_retry_item($res, $drafts, [
                'source' => 'push_sync',
            ]));
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
    append_social_sync_log($logItem);
    audit_event('social', 'push.sync', ['sync_id' => $syncId, 'connector_count' => count($activeConnectors), 'draft_total' => count($drafts)]);
    record_social_activity('push_sync', 'Social push sync queued.', ['sync_id' => $syncId, 'connector_count' => count($activeConnectors), 'draft_total' => count($drafts)]);
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

if ($action === 'social.delivery.summary') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    if ($limit <= 0) {
        $limit = 20;
    }
    $snapshot = social_delivery_summary_snapshot($limit);
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'items' => $snapshot['items'],
    ]);
}

if ($action === 'social.delivery.connector_detail') {
    $connectorId = isset($_GET['connector_id']) ? (string) $_GET['connector_id'] : '';
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    if ($limit <= 0) {
        $limit = 10;
    }
    $snapshot = social_delivery_connector_detail_snapshot($connectorId, $limit);
    out_json($snapshot, !empty($snapshot['ok']) ? 200 : 400);
}

if ($action === 'social.delivery.export') {
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    if ($limit <= 0) {
        $limit = 20;
    }
    $connectorId = isset($_GET['connector_id']) ? trim((string) $_GET['connector_id']) : '';
    $payload = [
        'exported_at' => gmdate('c'),
        'summary' => social_delivery_summary_snapshot($limit),
    ];
    if ($connectorId !== '') {
        $payload['connector_detail'] = social_delivery_connector_detail_snapshot($connectorId, $limit);
    }
    audit_event('social', 'delivery.export', [
        'connector_id' => $connectorId,
        'limit' => $limit,
    ]);
    out_json([
        'ok' => true,
        'filename' => 'social_delivery_export_' . gmdate('Ymd_His') . '.json',
        'export' => $payload,
    ]);
}

if ($action === 'social.delivery.watch.summary') {
    $snapshot = social_delivery_watch_summary();
    out_json([
        'ok' => true,
        'summary' => $snapshot['summary'],
        'latest' => $snapshot['latest'],
        'runs' => $snapshot['runs'],
    ]);
}

if ($action === 'social.delivery.watch.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $run = social_delivery_watch_evaluate('manual', true);
    audit_event('social', 'delivery.watch.run', [
        'watch_id' => (string) ($run['watch_id'] ?? ''),
        'degraded_count' => (int) ($run['summary']['degraded_count'] ?? 0),
    ]);
    out_json([
        'ok' => true,
        'run' => $run,
    ]);
}

if ($action === 'social.delivery.watch.export') {
    $snapshot = social_delivery_watch_summary();
    audit_event('social', 'delivery.watch.export', [
        'degraded_count' => (int) (($snapshot['summary']['degraded_count'] ?? 0)),
    ]);
    out_json([
        'ok' => true,
        'filename' => 'social_delivery_watch_' . gmdate('Ymd_His') . '.json',
        'export' => [
            'exported_at' => gmdate('c'),
            'summary' => $snapshot['summary'],
            'latest' => $snapshot['latest'],
            'runs' => $snapshot['runs'],
        ],
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
    $run = execute_social_retry_queue('retry_queue');
    out_json([
        'ok' => true,
        'processed' => (int) ($run['processed'] ?? 0),
        'succeeded' => (int) ($run['succeeded'] ?? 0),
        'remaining' => (int) ($run['remaining'] ?? 0),
        'run_id' => (string) ($run['run_id'] ?? ''),
        'items' => isset($run['items']) && is_array($run['items']) ? $run['items'] : [],
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
    audit_event('webops', 'monitors.save', ['monitor_id' => (string) $item['monitor_id'], 'type' => (string) $item['type']]);
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
    audit_event('webops', 'monitors.delete', ['monitor_id' => $monitorId]);
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
    webops_record_incident($monitor, $result, 'test');
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
        webops_record_incident($monitor, $res, 'run');
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
    audit_event('webops', 'run', ['run_id' => (string) $runItem['run_id'], 'monitor_count' => count($active), 'critical_count' => $critical]);
    out_json(['ok' => true, 'run' => $runItem]);
}

if ($action === 'webops.log') {
    $logs = app_read_json_file(webops_log_path(), []);
    out_json(['ok' => true, 'count' => count($logs), 'items' => $logs]);
}

if ($action === 'webops.incidents.list') {
    $rows = app_read_json_file(webops_incidents_path(), []);
    out_json(['ok' => true, 'count' => count($rows), 'items' => $rows]);
}

if ($action === 'webops.incidents.resolve') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['incident_id'])) {
        out_json(['ok' => false, 'error' => 'incident_id is required.'], 400);
    }
    $incidentId = (string) $data['incident_id'];
    $rows = app_read_json_file(webops_incidents_path(), []);
    $updated = null;
    foreach ($rows as $index => $row) {
        if (!is_array($row) || (string) ($row['incident_id'] ?? '') !== $incidentId) {
            continue;
        }
        $row['status'] = 'resolved';
        $row['resolved_at'] = gmdate('c');
        $row['updated_at'] = gmdate('c');
        $row['resolution_source'] = 'manual';
        $row['resolution_note'] = trim((string) ($data['note'] ?? ''));
        $rows[$index] = $row;
        $updated = $row;
        break;
    }
    if (!is_array($updated)) {
        out_json(['ok' => false, 'error' => 'Incident not found.'], 404);
    }
    app_write_json_file(webops_incidents_path(), $rows);
    audit_event('webops', 'incidents.resolve', ['incident_id' => $incidentId]);
    push_notification('info', 'WebOps incident manually resolved.', ['incident_id' => $incidentId]);
    out_json(['ok' => true, 'incident' => $updated]);
}

if ($action === 'webops.actions.list') {
    $rows = app_read_json_file(webops_actions_queue_path(), []);
    out_json(['ok' => true, 'count' => count($rows), 'items' => $rows]);
}

if ($action === 'webops.actions.enqueue') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || empty($data['action_type'])) {
        out_json(['ok' => false, 'error' => 'action_type is required.'], 400);
    }
    $item = [
        'action_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['action_id'] ?? uniqid('webops_action_', false))),
        'site_id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($data['site_id'] ?? '')),
        'action_type' => preg_replace('/[^a-z0-9_\-]/i', '', strtolower((string) ($data['action_type'] ?? ''))),
        'plugin_file' => trim((string) ($data['plugin_file'] ?? '')),
        'desired_state' => preg_replace('/[^a-z_]/i', '', strtolower((string) ($data['desired_state'] ?? ''))),
        'run_mode' => in_array((string) ($data['run_mode'] ?? 'dry_run'), ['dry_run', 'live'], true) ? (string) ($data['run_mode'] ?? 'dry_run') : 'dry_run',
        'status' => 'queued',
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
    ];
    $rows = app_read_json_file(webops_actions_queue_path(), []);
    array_unshift($rows, $item);
    $rows = array_slice($rows, 0, 300);
    app_write_json_file(webops_actions_queue_path(), $rows);
    audit_event('webops', 'actions.enqueue', ['action_id' => (string) $item['action_id'], 'action_type' => (string) $item['action_type'], 'site_id' => (string) $item['site_id']]);
    push_notification('info', 'WebOps action queued: ' . (string) $item['action_type'] . '.', ['action_id' => (string) $item['action_id'], 'site_id' => (string) $item['site_id']]);
    out_json(['ok' => true, 'item' => $item]);
}

if ($action === 'webops.actions.log') {
    $rows = app_read_json_file(webops_actions_log_path(), []);
    out_json(['ok' => true, 'count' => count($rows), 'items' => $rows]);
}

if ($action === 'webops.actions.run') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $rows = app_read_json_file(webops_actions_queue_path(), []);
    if (empty($rows)) {
        out_json(['ok' => true, 'message' => 'WebOps action queue is empty.', 'processed' => 0]);
    }
    $processed = 0;
    $completed = 0;
    $logs = app_read_json_file(webops_actions_log_path(), []);
    foreach ($rows as $index => $row) {
        if (!is_array($row) || strtolower((string) ($row['status'] ?? 'queued')) !== 'queued') {
            continue;
        }
        $processed++;
        $result = execute_webops_action($row);
        $row['status'] = !empty($result['ok']) ? 'completed' : 'failed';
        $row['result'] = $result;
        $row['updated_at'] = gmdate('c');
        $rows[$index] = $row;
        array_unshift($logs, [
            'log_id' => 'webops_action_log_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
            'created_at' => gmdate('c'),
            'action_id' => (string) ($row['action_id'] ?? ''),
            'action_type' => (string) ($row['action_type'] ?? ''),
            'site_id' => (string) ($row['site_id'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'result' => $result,
        ]);
        if (!empty($result['ok'])) {
            $completed++;
        }
    }
    $logs = array_slice($logs, 0, 300);
    app_write_json_file(webops_actions_queue_path(), array_slice($rows, 0, 300));
    app_write_json_file(webops_actions_log_path(), $logs);
    audit_event('webops', 'actions.run', ['processed' => $processed, 'completed' => $completed]);
    push_notification('info', 'WebOps action queue processed.', ['processed' => $processed, 'completed' => $completed]);
    out_json([
        'ok' => true,
        'processed' => $processed,
        'completed' => $completed,
        'failed' => max(0, $processed - $completed),
    ]);
}

if ($action === 'webops.posture.snapshot') {
    $monitors = app_read_json_file(webops_monitors_path(), []);
    $incidents = app_read_json_file(webops_incidents_path(), []);
    $queue = app_read_json_file(webops_actions_queue_path(), []);
    $actionLog = app_read_json_file(webops_actions_log_path(), []);
    $runs = app_read_json_file(webops_log_path(), []);
    $summaryMetrics = [
        'active_monitors' => 0,
        'open_incidents' => 0,
        'queued_actions' => 0,
        'completed_actions' => 0,
    ];
    foreach ($monitors as $row) {
        if (is_array($row) && in_array(strtolower((string) ($row['status'] ?? 'planned')), ['active', 'enabled'], true)) {
            $summaryMetrics['active_monitors']++;
        }
    }
    foreach ($incidents as $row) {
        if (is_array($row) && strtolower((string) ($row['status'] ?? 'open')) === 'open') {
            $summaryMetrics['open_incidents']++;
        }
    }
    foreach ($queue as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (strtolower((string) ($row['status'] ?? 'queued')) === 'queued') {
            $summaryMetrics['queued_actions']++;
        } elseif (strtolower((string) ($row['status'] ?? '')) === 'completed') {
            $summaryMetrics['completed_actions']++;
        }
    }
    out_json([
        'ok' => true,
        'generated_at' => gmdate('c'),
        'summary' => $summaryMetrics,
        'monitors' => [
            'count' => count($monitors),
            'latest' => isset($monitors[0]) && is_array($monitors[0]) ? $monitors[0] : null,
        ],
        'incidents' => [
            'count' => count($incidents),
            'latest' => isset($incidents[0]) && is_array($incidents[0]) ? $incidents[0] : null,
        ],
        'actions_queue' => [
            'count' => count($queue),
            'latest' => isset($queue[0]) && is_array($queue[0]) ? $queue[0] : null,
        ],
        'actions_log' => [
            'count' => count($actionLog),
            'latest' => isset($actionLog[0]) && is_array($actionLog[0]) ? $actionLog[0] : null,
        ],
        'last_run' => isset($runs[0]) && is_array($runs[0]) ? $runs[0] : null,
    ]);
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
        webops_record_incident($monitor, $res, 'retry');
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

if ($action === 'notifications.read_all') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $rows = app_read_json_file(notifications_path(), []);
    $count = 0;
    foreach ($rows as &$row) {
        if (is_array($row) && empty($row['read'])) {
            $row['read'] = 1;
            $count++;
        }
    }
    unset($row);
    app_write_json_file(notifications_path(), $rows);
    audit_event('notifications', 'read_all', ['updated' => $count]);
    out_json(['ok' => true, 'updated' => $count]);
}

if ($action === 'automation.runs') {
    $rows = app_read_json_file(automation_runs_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
    ]);
}

if ($action === 'automation.settings.get') {
    $settings = get_automation_settings();
    out_json([
        'ok' => true,
        'settings' => $settings,
    ]);
}

if ($action === 'automation.settings.save') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data)) {
        out_json(['ok' => false, 'error' => 'Invalid JSON body.'], 400);
    }
    $settings = [
        'enabled' => !empty($data['enabled']) ? 1 : 0,
        'interval_minutes' => max(5, min(1440, (int) ($data['interval_minutes'] ?? 30))),
        'last_run_at' => (string) ($data['last_run_at'] ?? ''),
        'modules' => [
            'crm' => !empty($data['modules']['crm']) ? 1 : 0,
            'social' => !empty($data['modules']['social']) ? 1 : 0,
            'webops' => !empty($data['modules']['webops']) ? 1 : 0,
            'seo' => !empty($data['modules']['seo']) ? 1 : 0,
        ],
    ];
    $saved = save_automation_settings($settings);
    audit_event('automation', 'settings.save', ['enabled' => (int) $saved['enabled'], 'interval_minutes' => (int) $saved['interval_minutes'], 'modules' => $saved['modules']]);
    out_json([
        'ok' => true,
        'settings' => $saved,
    ]);
}

if ($action === 'automation.scheduler.status') {
    $settings = get_automation_settings();
    $nowTs = time();
    $lastTs = $settings['last_run_at'] !== '' ? strtotime((string) $settings['last_run_at']) : 0;
    $intervalSecs = max(5, (int) ($settings['interval_minutes'] ?? 30)) * 60;
    $elapsed = $lastTs > 0 ? ($nowTs - $lastTs) : null;
    $due = ($lastTs === 0) ? true : ($elapsed >= $intervalSecs);
    $nextDueIn = ($lastTs === 0) ? 0 : max(0, $intervalSecs - max(0, (int) $elapsed));
    $gateWatchState = app_read_json_file(deployment_release_gate_state_path(), []);
    $gateWatchRuns = app_read_json_file(deployment_release_gate_runs_path(), []);
    $gateWatchSummary = deployment_release_gate_runs_summary($gateWatchRuns);
    $gateWatchSustainedState = deployment_release_gate_sustained_state_snapshot($gateWatchState);
    $gateWatchSustainedTimeline = deployment_release_gate_sustained_timeline_snapshot($gateWatchRuns, 12);
    $gateWatchRunCount = is_array($gateWatchRuns) ? count($gateWatchRuns) : 0;
    $gateWatchQuickstats = [
        'count' => $gateWatchRunCount,
        'summary' => $gateWatchSummary,
        'sustained_state' => $gateWatchSustainedState,
        'sustained_timeline' => $gateWatchSustainedTimeline,
        'quick_digest' => deployment_release_gate_quick_digest_snapshot($gateWatchRunCount, $gateWatchSummary, $gateWatchSustainedState, $gateWatchSustainedTimeline),
    ];
    $signoffIntegrityState = app_read_json_file(deployment_cutover_signoff_integrity_state_path(), []);
    $signoffIntegrityRuns = app_read_json_file(deployment_cutover_signoff_integrity_runs_path(), []);
    $watchdogsCheckState = app_read_json_file(deployment_watchdogs_state_path(), []);
    $watchdogsCheckRuns = app_read_json_file(deployment_watchdogs_runs_path(), []);
    $watchdogsPolicyBaselineState = app_read_json_file(deployment_watchdogs_policy_baseline_state_path(), []);
    $watchdogsPolicyBaselineRuns = app_read_json_file(deployment_watchdogs_policy_baseline_runs_path(), []);
    $watchdogsIncidentSummary = deployment_watchdogs_incident_summary_snapshot();
    $lastSignoffIntegrityRun = [];
    if (is_array($signoffIntegrityRuns) && !empty($signoffIntegrityRuns[0]) && is_array($signoffIntegrityRuns[0])) {
        $lastSignoffIntegrityRun = $signoffIntegrityRuns[0];
    }
    $lastWatchdogsCheckRun = [];
    if (is_array($watchdogsCheckRuns) && !empty($watchdogsCheckRuns[0]) && is_array($watchdogsCheckRuns[0])) {
        $lastWatchdogsCheckRun = $watchdogsCheckRuns[0];
    }
    $lastWatchdogsPolicyBaselineRun = [];
    if (is_array($watchdogsPolicyBaselineRuns) && !empty($watchdogsPolicyBaselineRuns[0]) && is_array($watchdogsPolicyBaselineRuns[0])) {
        $lastWatchdogsPolicyBaselineRun = $watchdogsPolicyBaselineRuns[0];
    }
    out_json([
        'ok' => true,
        'enabled' => !empty($settings['enabled']),
        'last_run_at' => $settings['last_run_at'],
        'interval_minutes' => (int) $settings['interval_minutes'],
        'due_now' => $due,
        'next_due_in_seconds' => $nextDueIn,
        'modules' => $settings['modules'],
        'release_gate_watch_state' => is_array($gateWatchState) ? $gateWatchState : [],
        'release_gate_watch_summary' => $gateWatchSummary,
        'release_gate_sustained_state' => $gateWatchSustainedState,
        'release_gate_sustained_timeline' => $gateWatchSustainedTimeline,
        'release_gate_quickstats' => $gateWatchQuickstats,
        'signoff_integrity_watch_state' => is_array($signoffIntegrityState) ? $signoffIntegrityState : [],
        'signoff_integrity_watch_last_run' => $lastSignoffIntegrityRun,
        'watchdogs_check_state' => is_array($watchdogsCheckState) ? $watchdogsCheckState : [],
        'watchdogs_check_last_run' => $lastWatchdogsCheckRun,
        'watchdogs_policy_baseline_check_state' => is_array($watchdogsPolicyBaselineState) ? $watchdogsPolicyBaselineState : [],
        'watchdogs_policy_baseline_check_last_run' => $lastWatchdogsPolicyBaselineRun,
        'watchdogs_incident_summary' => $watchdogsIncidentSummary,
        'time' => gmdate('c'),
    ]);
}

if ($action === 'automation.scheduler.cron_help') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $baseUrl = rtrim($scheme . '://' . $host, '/');
    $tickUrl = $baseUrl . '/api/index.php?action=automation.scheduler.tick';
    $schedulerKey = (string) ($config['automation_scheduler_key'] ?? '');
    $safeKey = $schedulerKey !== '' ? $schedulerKey : 'REPLACE_WITH_AUTOMATION_SCHEDULER_KEY';
    out_json([
        'ok' => true,
        'base_url' => $baseUrl,
        'tick_url' => $tickUrl,
        'linux_cron_example' => "*/5 * * * * curl -s -X POST -H \"X-AUTOMATION-KEY: {$safeKey}\" \"{$tickUrl}\" > /dev/null 2>&1",
        'windows_task_example' => "powershell -Command \"Invoke-RestMethod -Method Post -Headers @{ 'X-AUTOMATION-KEY'='{$safeKey}' } -Uri '{$tickUrl}'\"",
        'note' => 'Use a secure scheduler key in app-site/config.php and restrict server access where possible.',
    ]);
}

if ($action === 'automation.run_all') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $settings = get_automation_settings();
    $summary = execute_automation_run($settings, 'manual');
    $settings['last_run_at'] = (string) ($summary['created_at'] ?? gmdate('c'));
    save_automation_settings($settings);
    audit_event('automation', 'run_all', ['automation_id' => (string) $summary['automation_id'], 'source' => 'manual']);
    out_json([
        'ok' => true,
        'run' => $summary,
    ]);
}

if ($action === 'automation.scheduler.tick') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    $authorized = false;
    if (app_is_authenticated() && app_is_owner()) {
        $authorized = true;
    } else {
        $expected = (string) ($config['automation_scheduler_key'] ?? '');
        $incoming = isset($_SERVER['HTTP_X_AUTOMATION_KEY']) ? (string) $_SERVER['HTTP_X_AUTOMATION_KEY'] : '';
        if ($expected !== '' && $incoming !== '' && hash_equals($expected, $incoming)) {
            $authorized = true;
        }
    }
    if (!$authorized) {
        out_json(['ok' => false, 'error' => 'Invalid scheduler credentials.'], 403);
    }
    $settings = get_automation_settings();
    if (empty($settings['enabled'])) {
        $watch = deployment_release_gate_watch_snapshot('scheduler_tick_disabled', null);
        $signoffWatch = deployment_cutover_signoff_integrity_watch_snapshot('scheduler_tick_disabled');
        $watchdogsCheck = deployment_watchdogs_check_snapshot('scheduler_tick_disabled', null);
        $watchdogsPolicyBaselineCheck = deployment_watchdogs_policy_baseline_check_snapshot('scheduler_tick_disabled');
        $watchdogsIncidentSummary = deployment_watchdogs_incident_summary_snapshot();
        out_json([
            'ok' => true,
            'skipped' => true,
            'reason' => 'Automation disabled in settings.',
            'release_gate_watch' => $watch,
            'signoff_integrity_watch' => $signoffWatch,
            'watchdogs_check' => $watchdogsCheck,
            'watchdogs_policy_baseline_check' => $watchdogsPolicyBaselineCheck,
            'watchdogs_incident_summary' => $watchdogsIncidentSummary,
        ]);
    }
    $nowTs = time();
    $lastTs = $settings['last_run_at'] !== '' ? strtotime((string) $settings['last_run_at']) : 0;
    $interval = max(5, (int) ($settings['interval_minutes'] ?? 30)) * 60;
    if ($lastTs > 0 && ($nowTs - $lastTs) < $interval) {
        $watch = deployment_release_gate_watch_snapshot('scheduler_tick_interval_skip', null);
        $signoffWatch = deployment_cutover_signoff_integrity_watch_snapshot('scheduler_tick_interval_skip');
        $watchdogsCheck = deployment_watchdogs_check_snapshot('scheduler_tick_interval_skip', null);
        $watchdogsPolicyBaselineCheck = deployment_watchdogs_policy_baseline_check_snapshot('scheduler_tick_interval_skip');
        $watchdogsIncidentSummary = deployment_watchdogs_incident_summary_snapshot();
        out_json([
            'ok' => true,
            'skipped' => true,
            'reason' => 'Interval not reached.',
            'next_due_in_seconds' => $interval - ($nowTs - $lastTs),
            'release_gate_watch' => $watch,
            'signoff_integrity_watch' => $signoffWatch,
            'watchdogs_check' => $watchdogsCheck,
            'watchdogs_policy_baseline_check' => $watchdogsPolicyBaselineCheck,
            'watchdogs_incident_summary' => $watchdogsIncidentSummary,
        ]);
    }
    $summary = execute_automation_run($settings, 'scheduler');
    $watch = deployment_release_gate_watch_snapshot('scheduler_tick_run', null);
    $signoffWatch = deployment_cutover_signoff_integrity_watch_snapshot('scheduler_tick_run');
    $watchdogsCheck = deployment_watchdogs_check_snapshot('scheduler_tick_run', null);
    $watchdogsPolicyBaselineCheck = deployment_watchdogs_policy_baseline_check_snapshot('scheduler_tick_run');
    $watchdogsIncidentSummary = deployment_watchdogs_incident_summary_snapshot();
    $settings['last_run_at'] = (string) ($summary['created_at'] ?? gmdate('c'));
    save_automation_settings($settings);
    audit_event('automation', 'scheduler.tick', ['automation_id' => (string) $summary['automation_id'], 'source' => 'scheduler']);
    out_json([
        'ok' => true,
        'run' => $summary,
        'release_gate_watch' => $watch,
        'signoff_integrity_watch' => $signoffWatch,
        'watchdogs_check' => $watchdogsCheck,
        'watchdogs_policy_baseline_check' => $watchdogsPolicyBaselineCheck,
        'watchdogs_incident_summary' => $watchdogsIncidentSummary,
    ]);
}

if ($action === 'audit.log') {
    $rows = app_read_json_file(audit_log_path(), []);
    out_json([
        'ok' => true,
        'count' => count($rows),
        'items' => $rows,
    ]);
}

if ($action === 'backup.export') {
    app_require_owner();
    $map = module_storage_map();
    $payload = [];
    foreach ($map as $name => $path) {
        $payload[$name] = app_read_json_file($path, []);
    }
    audit_event('backup', 'export', ['keys' => array_keys($payload)]);
    out_json([
        'ok' => true,
        'version' => 1,
        'exported_at' => gmdate('c'),
        'data' => $payload,
    ]);
}

if ($action === 'backup.import') {
    app_require_owner();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        out_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    app_require_csrf();
    $data = app_read_json_body();
    if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
        out_json(['ok' => false, 'error' => 'Invalid backup payload.'], 400);
    }
    $incoming = $data['data'];
    $map = module_storage_map();
    $written = [];
    foreach ($map as $name => $path) {
        if (array_key_exists($name, $incoming)) {
            $value = is_array($incoming[$name]) ? $incoming[$name] : [];
            app_write_json_file($path, $value);
            $written[] = $name;
        }
    }
    audit_event('backup', 'import', ['keys' => $written]);
    out_json([
        'ok' => true,
        'imported_keys' => $written,
    ]);
}

out_json([
    'ok' => false,
    'error' => 'Unknown action.',
    'action' => $action,
], 404);
