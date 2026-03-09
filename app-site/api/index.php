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
        'social_connectors' => social_connectors_path(),
        'social_sync_log' => social_sync_log_path(),
        'social_retry_queue' => social_retry_queue_path(),
        'webops_monitors' => webops_monitors_path(),
        'webops_log' => webops_log_path(),
        'webops_retry_queue' => webops_retry_queue_path(),
        'seo_projects' => seo_projects_path(),
        'seo_audits' => seo_audits_path(),
        'seo_extension_events' => seo_extension_events_path(),
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
        'baseline_match_blocked_runs' => 0,
        'baseline_check_blocked_runs' => 0,
        'signoff_integrity_watch_blocked_runs' => 0,
        'failed_item_counts' => [],
        'latest_blocked_run' => null,
    ];
    foreach ($runs as $row) {
        if (!is_array($row)) {
            continue;
        }
        $summary['total_runs']++;
        $allowed = !empty($row['allowed']) ? 1 : 0;
        if ($allowed === 1) {
            $summary['allowed_runs']++;
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
        if (!empty($row['status_changed'])) {
            $summary['status_changed_runs']++;
        }
        if (!empty($row['alert_sent'])) {
            $summary['alert_sent_runs']++;
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
    return $summary;
}

function deployment_release_gate_runs_apply_filters($runs, $query)
{
    if (!is_array($runs)) {
        $runs = [];
    }
    $limit = isset($query['limit']) ? (int) $query['limit'] : 200;
    $limit = max(1, min(400, $limit));
    $allowedFilter = strtolower(trim((string) ($query['allowed'] ?? 'all')));
    if (!in_array($allowedFilter, ['all', 'allowed', 'blocked'], true)) {
        $allowedFilter = 'all';
    }
    $sourceFilter = trim((string) ($query['source'] ?? ''));
    $failedItemFilterRaw = trim((string) ($query['failed_item'] ?? ''));
    $failedItemFilter = strtolower($failedItemFilterRaw);

    $filtered = [];
    foreach ($runs as $row) {
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
        if ($sourceFilter !== '' && !hash_equals(strtolower(trim((string) ($row['source'] ?? ''))), strtolower($sourceFilter))) {
            continue;
        }
        if ($failedItemFilter !== '') {
            $failedItems = isset($row['failed_items']) && is_array($row['failed_items']) ? $row['failed_items'] : [];
            $matched = false;
            foreach ($failedItems as $item) {
                $candidate = strtolower(trim((string) $item));
                if ($candidate !== '' && hash_equals($candidate, $failedItemFilter)) {
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
        'total_count' => count($runs),
        'applied_filters' => [
            'limit' => $limit,
            'allowed' => $allowedFilter,
            'source' => $sourceFilter,
            'failed_item' => $failedItemFilterRaw,
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

    $nextState = [
        'last_checked_at' => $now,
        'last_allowed' => $allowed,
        'last_reasons' => isset($gate['reasons']) && is_array($gate['reasons']) ? $gate['reasons'] : [],
        'last_failed_items' => $failedItems,
        'last_run_id' => (string) ($run['run_id'] ?? ''),
        'last_source' => (string) $source,
        'last_alert_at' => $alertSent === 1 ? $now : (string) ($state['last_alert_at'] ?? ''),
        'last_alert_sent' => $alertSent,
        'last_baseline_blockers_active' => $baselineBlockersActive,
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
        'phase' => '1.70-release-gate-digest-panel',
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

function execute_automation_run($settings, $source = 'manual')
{
    $automationId = 'auto_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6);
    $summary = [
        'automation_id' => $automationId,
        'created_at' => gmdate('c'),
        'source' => (string) $source,
        'settings_snapshot' => $settings,
        'crm' => ['processed' => 0, 'failed' => 0],
        'social' => ['processed' => 0, 'failed' => 0],
        'webops' => ['processed' => 0, 'failed' => 0],
        'seo' => ['processed' => 0, 'failed' => 0],
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
    }

    $runs = app_read_json_file(automation_runs_path(), []);
    array_unshift($runs, $summary);
    $runs = array_slice($runs, 0, 200);
    app_write_json_file(automation_runs_path(), $runs);

    $failTotal = $summary['crm']['failed'] + $summary['social']['failed'] + $summary['webops']['failed'] + $summary['seo']['failed'];
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
    'social.connectors.save', 'social.connectors.delete', 'social.connectors.test', 'social.push.sync', 'social.retry.run',
    'webops.monitors.save', 'webops.monitors.delete', 'webops.monitors.test', 'webops.run', 'webops.retry.run',
    'seo.projects.save', 'seo.projects.delete', 'seo.audit.run', 'seo.extension.intake',
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
    'social.connectors.save', 'social.connectors.delete', 'social.connectors.test', 'social.push.sync', 'social.retry.run',
    'webops.monitors.save', 'webops.monitors.delete', 'webops.monitors.test', 'webops.run', 'webops.retry.run',
    'seo.projects.save', 'seo.projects.delete', 'seo.audit.run',
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
        'phase' => '1.70-release-gate-digest-panel',
        'modules' => [
            'leads' => 'active',
            'crm_email' => 'bootstrap',
            'social_forums' => 'bootstrap',
            'webops_security' => 'bootstrap',
            'seo_suite' => 'bootstrap',
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
    $items = isset($filter['items']) && is_array($filter['items']) ? $filter['items'] : [];
    $state = app_read_json_file(deployment_release_gate_state_path(), []);
    $summary = deployment_release_gate_runs_summary($items);
    out_json([
        'ok' => true,
        'count' => count($items),
        'filtered_total_count' => (int) ($filter['filtered_total_count'] ?? count($items)),
        'total_count' => (int) ($filter['total_count'] ?? count($runs)),
        'items' => $items,
        'summary' => $summary,
        'applied_filters' => isset($filter['applied_filters']) && is_array($filter['applied_filters']) ? $filter['applied_filters'] : [],
        'state' => is_array($state) ? $state : [],
        'time' => gmdate('c'),
    ]);
}

if ($action === 'deployment.release.gate.runs.export') {
    $runs = app_read_json_file(deployment_release_gate_runs_path(), []);
    if (!is_array($runs)) {
        $runs = [];
    }
    $filter = deployment_release_gate_runs_apply_filters($runs, $_GET);
    $items = isset($filter['items']) && is_array($filter['items']) ? $filter['items'] : [];
    $summary = deployment_release_gate_runs_summary($items);
    out_json([
        'ok' => true,
        'filename' => 'release-gate-runs-' . gmdate('Ymd-His') . '.json',
        'count' => count($items),
        'filtered_total_count' => (int) ($filter['filtered_total_count'] ?? count($items)),
        'total_count' => (int) ($filter['total_count'] ?? count($runs)),
        'applied_filters' => isset($filter['applied_filters']) && is_array($filter['applied_filters']) ? $filter['applied_filters'] : [],
        'summary' => $summary,
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
    $items = isset($filter['items']) && is_array($filter['items']) ? $filter['items'] : [];
    $summary = deployment_release_gate_runs_summary($items);
    $freshness = isset($_GET['freshness_minutes']) ? (int) $_GET['freshness_minutes'] : null;
    $gate = deployment_release_gate_snapshot($freshness);
    $state = app_read_json_file(deployment_release_gate_state_path(), []);
    if (!is_array($state)) {
        $state = [];
    }
    $report = [
        'report_id' => 'release_gate_blockers_' . gmdate('Ymd_His') . '_' . substr(sha1((string) mt_rand()), 0, 6),
        'generated_at' => gmdate('c'),
        'release_gate' => $gate,
        'release_gate_state' => $state,
        'runs_summary' => $summary,
        'runs_filters' => isset($filter['applied_filters']) && is_array($filter['applied_filters']) ? $filter['applied_filters'] : [],
        'runs_count' => count($items),
        'runs_filtered_total_count' => (int) ($filter['filtered_total_count'] ?? count($items)),
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
    audit_event('seo', 'audit.run', ['project_id' => (string) $project['project_id'], 'audit_id' => (string) $audit['audit_id']]);
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
    audit_event('social', 'connectors.save', ['connector_id' => (string) $item['connector_id'], 'provider' => (string) $item['provider']]);
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
    audit_event('social', 'connectors.delete', ['connector_id' => $connectorId]);
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
    audit_event('social', 'push.sync', ['sync_id' => $syncId, 'connector_count' => count($activeConnectors), 'draft_total' => count($drafts)]);
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
    audit_event('webops', 'run', ['run_id' => (string) $runItem['run_id'], 'monitor_count' => count($active), 'critical_count' => $critical]);
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
