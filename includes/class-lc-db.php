<?php

namespace LC;

if (!defined('ABSPATH')) {
    exit;
}

class DB
{
    public static function leads_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'lc_leads';
    }

    public static function runs_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'lc_runs';
    }

    public static function run_logs_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'lc_run_logs';
    }

    public static function suppression_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'lc_suppression';
    }

    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();
        $leads = self::leads_table();
        $runs = self::runs_table();
        $logs = self::run_logs_table();
        $suppression = self::suppression_table();

        dbDelta("CREATE TABLE {$leads} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            business_name VARCHAR(191) NOT NULL,
            city VARCHAR(120) DEFAULT '' NOT NULL,
            category VARCHAR(120) DEFAULT '' NOT NULL,
            address VARCHAR(255) DEFAULT '' NOT NULL,
            website VARCHAR(255) DEFAULT '' NOT NULL,
            normalized_domain VARCHAR(191) DEFAULT '' NOT NULL,
            phone VARCHAR(40) DEFAULT '' NOT NULL,
            email VARCHAR(191) DEFAULT '' NOT NULL,
            email_confidence TINYINT UNSIGNED DEFAULT 0 NOT NULL,
            review_count INT UNSIGNED DEFAULT 0 NOT NULL,
            rating DECIMAL(3,2) DEFAULT 0.00 NOT NULL,
            lead_type VARCHAR(32) DEFAULT '' NOT NULL,
            score TINYINT UNSIGNED DEFAULT 0 NOT NULL,
            status VARCHAR(32) DEFAULT 'new' NOT NULL,
            owner_user_id BIGINT UNSIGNED DEFAULT 0 NOT NULL,
            source_url VARCHAR(255) DEFAULT '' NOT NULL,
            source VARCHAR(80) DEFAULT 'manual' NOT NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_score (score),
            KEY idx_city (city),
            KEY idx_phone (phone),
            KEY idx_domain (normalized_domain),
            KEY idx_email (email)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$runs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            provider VARCHAR(40) NOT NULL,
            query_text VARCHAR(191) DEFAULT '' NOT NULL,
            city VARCHAR(120) DEFAULT '' NOT NULL,
            status VARCHAR(20) DEFAULT 'queued' NOT NULL,
            requested_limit INT UNSIGNED DEFAULT 0 NOT NULL,
            processed_count INT UNSIGNED DEFAULT 0 NOT NULL,
            inserted_count INT UNSIGNED DEFAULT 0 NOT NULL,
            duplicate_count INT UNSIGNED DEFAULT 0 NOT NULL,
            error_count INT UNSIGNED DEFAULT 0 NOT NULL,
            budget_limited TINYINT(1) DEFAULT 0 NOT NULL,
            payload LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_provider (provider)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$logs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            run_id BIGINT UNSIGNED NOT NULL,
            level VARCHAR(16) DEFAULT 'info' NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_run_id (run_id)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$suppression} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            value VARCHAR(191) NOT NULL,
            reason VARCHAR(255) DEFAULT '' NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_type_value (type, value)
        ) {$charsetCollate};");
    }
}
