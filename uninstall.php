<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('lc_settings');

global $wpdb;
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'lc_leads');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'lc_runs');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'lc_run_logs');
$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'lc_suppression');
