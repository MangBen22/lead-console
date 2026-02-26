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

http_response_code(404);
echo json_encode([
    'ok' => false,
    'error' => 'Unknown action.',
    'action' => $action,
]);
