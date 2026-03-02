<?php

// Copy this file to app-site/config.php on hosting and replace all placeholder values.
return [
    'app_name' => '5N2 App Control Center',
    'environment' => 'production',
    'session_key' => 'REPLACE_WITH_LONG_RANDOM_SESSION_KEY',
    'demo_admin_email' => 'REPLACE_WITH_PRIMARY_ADMIN_EMAIL',
    'demo_admin_password' => 'REPLACE_WITH_STRONG_PASSWORD',
    'notification_sound_enabled' => true,
    'notification_sound_mode' => 'critical_only',
    'automation_scheduler_key' => 'REPLACE_WITH_SECURE_SCHEDULER_KEY',
    'seo_extension_ingest_key' => 'REPLACE_WITH_SECURE_EXTENSION_INGEST_KEY',
    'plugin_sites' => [
        // [
        //     'site_id' => 'hq-main',
        //     'label' => '5N2 Main WP',
        //     'base_url' => 'https://your-wordpress-site.com',
        //     'bridge_key' => 'REPLACE_WITH_PLUGIN_BRIDGE_KEY',
        // ],
    ],
];
