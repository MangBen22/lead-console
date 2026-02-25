<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$tables = [
    $wpdb->prefix . 'lc_leads',
    $wpdb->prefix . 'lc_lead_profiles',
    $wpdb->prefix . 'lc_runs',
    $wpdb->prefix . 'lc_run_logs',
    $wpdb->prefix . 'lc_suppression',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

delete_option('lc_settings');
delete_option('lc_schema_version');
