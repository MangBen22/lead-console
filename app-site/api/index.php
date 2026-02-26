<?php
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? (string) $_GET['action'] : 'status';

if ($action === 'status') {
    echo json_encode([
        'ok' => true,
        'service' => '5N2 App API',
        'phase' => '1.1-main-website-scaffold',
        'modules' => [
            'leads' => 'active',
            'crm_email' => 'planning',
            'social_forums' => 'planning',
            'webops_security' => 'planning',
            'seo_suite' => 'planning',
        ],
        'time' => gmdate('c'),
    ]);
    exit;
}

if ($action === 'notifications') {
    echo json_encode([
        'ok' => true,
        'items' => [
            ['type' => 'info', 'message' => 'Main platform API is reachable.'],
            ['type' => 'info', 'message' => 'No incidents in current scaffold state.'],
        ],
        'time' => gmdate('c'),
    ]);
    exit;
}

if ($action === 'leads.summary') {
    echo json_encode([
        'ok' => true,
        'module' => 'leads',
        'status' => 'active',
        'metrics' => [
            'queued_runs' => 0,
            'approved_leads' => 0,
            'pending_review' => 0,
        ],
    ]);
    exit;
}

if ($action === 'crm.summary') {
    echo json_encode([
        'ok' => true,
        'module' => 'crm_email',
        'status' => 'bootstrap',
        'metrics' => [
            'active_connectors' => 0,
            'smtp_connected' => false,
            'failed_deliveries' => 0,
        ],
    ]);
    exit;
}

if ($action === 'social.summary') {
    echo json_encode([
        'ok' => true,
        'module' => 'social_forums',
        'status' => 'bootstrap',
        'metrics' => [
            'connected_accounts' => 0,
            'scheduled_posts' => 0,
            'unread_conversations' => 0,
        ],
    ]);
    exit;
}

if ($action === 'webops.summary') {
    echo json_encode([
        'ok' => true,
        'module' => 'webops_security',
        'status' => 'bootstrap',
        'metrics' => [
            'sites_monitored' => 0,
            'active_incidents' => 0,
            'uptime_percent' => 100,
        ],
    ]);
    exit;
}

if ($action === 'seo.summary') {
    echo json_encode([
        'ok' => true,
        'module' => 'seo_suite',
        'status' => 'bootstrap',
        'metrics' => [
            'audits_completed' => 0,
            'critical_issues' => 0,
            'tracked_projects' => 0,
        ],
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    'ok' => false,
    'error' => 'Unknown action.',
    'action' => $action,
]);
