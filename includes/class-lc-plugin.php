<?php

if (!defined('ABSPATH')) {
    exit;
}

class LC_Plugin
{
    private static $instance = null;

    private $statuses = [
        'New',
        'Verified',
        'Ready',
        'Contacted',
        'Replied',
        'Meeting',
        'Proposal Sent',
        'Won',
        'Lost',
        'Do Not Contact',
    ];

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        $sql_leads = "CREATE TABLE {$prefix}lc_leads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            business_name VARCHAR(190) NOT NULL,
            city VARCHAR(120) DEFAULT '',
            category VARCHAR(120) DEFAULT '',
            address VARCHAR(255) DEFAULT '',
            website VARCHAR(255) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            email VARCHAR(190) DEFAULT '',
            email_confidence VARCHAR(20) DEFAULT '',
            review_count INT DEFAULT 0,
            rating DECIMAL(3,1) DEFAULT 0,
            status VARCHAR(30) DEFAULT 'New',
            score TINYINT UNSIGNED DEFAULT 0,
            lead_type CHAR(1) DEFAULT 'C',
            linkedin_url VARCHAR(255) DEFAULT '',
            facebook_url VARCHAR(255) DEFAULT '',
            instagram_url VARCHAR(255) DEFAULT '',
            x_url VARCHAR(255) DEFAULT '',
            youtube_url VARCHAR(255) DEFAULT '',
            social_confidence TINYINT UNSIGNED DEFAULT 0,
            social_source VARCHAR(80) DEFAULT '',
            source_url VARCHAR(255) DEFAULT '',
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY phone (phone),
            KEY website (website)
        ) {$charset};";

        $sql_runs = "CREATE TABLE {$prefix}lc_runs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            query_text VARCHAR(255) NOT NULL,
            city VARCHAR(120) DEFAULT '',
            state VARCHAR(120) DEFAULT '',
            country VARCHAR(120) DEFAULT '',
            radius_miles INT DEFAULT 0,
            niche VARCHAR(190) DEFAULT '',
            services TEXT NULL,
            website_focus VARCHAR(20) DEFAULT 'any',
            min_rating DECIMAL(3,1) DEFAULT 0,
            min_reviews INT DEFAULT 0,
            max_places INT DEFAULT 25,
            save_mode VARCHAR(20) DEFAULT 'direct',
            review_status VARCHAR(20) DEFAULT 'na',
            captured_leads INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'queued',
            started_at DATETIME NULL,
            finished_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) {$charset};";

        $sql_run_drafts = "CREATE TABLE {$prefix}lc_run_drafts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            run_id BIGINT UNSIGNED NOT NULL,
            business_name VARCHAR(190) NOT NULL,
            city VARCHAR(120) DEFAULT '',
            category VARCHAR(120) DEFAULT '',
            address VARCHAR(255) DEFAULT '',
            website VARCHAR(255) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            email VARCHAR(190) DEFAULT '',
            review_count INT DEFAULT 0,
            rating DECIMAL(3,1) DEFAULT 0,
            status VARCHAR(30) DEFAULT 'New',
            score TINYINT UNSIGNED DEFAULT 0,
            lead_type CHAR(1) DEFAULT 'C',
            source_url VARCHAR(255) DEFAULT '',
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY run_id (run_id)
        ) {$charset};";

        $sql_logs = "CREATE TABLE {$prefix}lc_run_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            run_id BIGINT UNSIGNED NOT NULL,
            level VARCHAR(20) DEFAULT 'info',
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY run_id (run_id)
        ) {$charset};";

        $sql_suppression = "CREATE TABLE {$prefix}lc_suppression (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            value VARCHAR(255) NOT NULL,
            reason VARCHAR(255) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY value (value)
        ) {$charset};";

        $sql_profiles = "CREATE TABLE {$prefix}lc_lead_profiles (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT UNSIGNED NOT NULL,
            possible_emails LONGTEXT NULL,
            primary_email VARCHAR(190) DEFAULT '',
            socials_json LONGTEXT NULL,
            people_json LONGTEXT NULL,
            company_json LONGTEXT NULL,
            reviews_json LONGTEXT NULL,
            jobs_json LONGTEXT NULL,
            completeness_score TINYINT UNSIGNED DEFAULT 0,
            confidence_score TINYINT UNSIGNED DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY lead_id (lead_id)
        ) {$charset};";

        $sql_system_logs = "CREATE TABLE {$prefix}lc_system_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            category VARCHAR(40) DEFAULT 'system',
            level VARCHAR(20) DEFAULT 'info',
            message TEXT NOT NULL,
            context_json LONGTEXT NULL,
            user_id BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY category (category),
            KEY user_id (user_id)
        ) {$charset};";

        dbDelta($sql_leads);
        dbDelta($sql_runs);
        dbDelta($sql_run_drafts);
        dbDelta($sql_logs);
        dbDelta($sql_suppression);
        dbDelta($sql_profiles);
        dbDelta($sql_system_logs);

        if (!wp_next_scheduled('lc_process_run')) {
            wp_schedule_event(time(), 'hourly', 'lc_process_run');
        }
        if (!wp_next_scheduled('lc_smtp_health_check')) {
            wp_schedule_event(time() + 120, 'lc_every_30_minutes', 'lc_smtp_health_check');
        }

        add_option('lc_settings', [
            'domain_fragment' => '',
            'max_places_per_run' => 25,
            'platform_mode' => 'single_tenant',
            'notifications_web' => 1,
            'notifications_email' => 1,
            'notifications_sound' => 0,
            'crm_connectors' => '',
            'social_connectors' => '',
            'webops_monitors' => '',
            'seo_extension_enabled' => 0,
            'human_support_email' => '',
            'bridge_enabled' => 0,
            'bridge_shared_key' => '',
            'enable_live_api_calls' => 0,
            'google_places_api_key' => '',
            'discovery_mode' => 'hybrid',
            'directory_sources' => '',
            'directory_source_presets' => self::default_directory_source_preset_ids(),
            'directory_custom_sites' => '',
            'social_discovery_mode' => 'off',
            'google_cse_api_key' => '',
            'google_cse_cx' => '',
            'smtp_enabled' => 0,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_auth' => 1,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => '',
            'email_template_reset_subject' => '5N2 Digital Lead Console Core Password Reset',
            'email_template_reset_body' => "Hello {first_name},\n\nA password reset was requested for your account.\n\nReset link (valid for 5 minutes):\n{reset_link}\n\nIf you did not request this, ignore this email.\n\n{site_name}",
            'email_template_password_changed_subject' => 'Your 5N2 Digital Lead Console Core password was changed',
            'email_template_password_changed_body' => "Hello {first_name},\n\nYour password was changed.\n\nIf this was not you, click this revoke link within 5 minutes:\n{revoke_link}\n\n{site_name}",
            'email_template_admin_password_changed_subject' => 'Lead Console Alert: Password changed',
            'email_template_admin_password_changed_body' => "User password changed.\n\nUser: {user_email}\nTime: {current_time}\n\nRevoke window: 5 minutes.",
            'email_template_registration_received_subject' => 'Registration request received',
            'email_template_registration_received_body' => "Hello {first_name},\n\nYour registration request was received and is pending admin approval.\n\n{site_name}",
            'email_template_registration_admin_subject' => 'New registration request pending approval',
            'email_template_registration_admin_body' => "A new user registration request was submitted.\n\nName: {full_name}\nEmail: {user_email}\nCompany: {company}\nPhone: {phone}\n\nPlease review in Users tab.",
            'email_template_registration_approved_subject' => 'Registration approved',
            'email_template_registration_approved_body' => "Hello {first_name},\n\nYour account is now approved. You can sign in.\n\n{site_name}",
            'email_template_registration_rejected_subject' => 'Registration declined',
            'email_template_registration_rejected_body' => "Hello {first_name},\n\nYour registration request was declined. Please contact admin for details.\n\n{site_name}",
        ]);
        add_option('lc_smtp_health_status', [
            'connected' => false,
            'status_color' => 'red',
            'message' => 'Not checked yet.',
            'checked_at' => '',
        ]);
        add_option('lc_email_template_test_log', []);
        update_option('lc_schema_version', '7');
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('lc_process_run');
        wp_clear_scheduled_hook('lc_smtp_health_check');
    }

    private function __construct()
    {
        add_action('init', [$this, 'maybe_upgrade_schema']);
        add_action('init', [$this, 'ensure_smtp_health_schedule']);
        add_action('init', [$this, 'bootstrap_primary_admin_user']);
        add_action('init', [$this, 'disable_admin_bar_for_logged_in_users']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'enforce_frontend_only_mode']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('map_meta_cap', [$this, 'restrict_user_creation_capability'], 10, 4);
        add_action('admin_post_lc_add_lead', [$this, 'handle_add_lead']);
        add_action('admin_post_lc_update_lead_status', [$this, 'handle_update_lead_status']);
        add_action('admin_post_lc_import_csv', [$this, 'handle_import_csv']);
        add_action('admin_post_lc_queue_run', [$this, 'handle_queue_run']);
        add_action('admin_post_lc_add_suppression', [$this, 'handle_add_suppression']);
        add_action('admin_post_lc_export_ready', [$this, 'handle_export_ready']);
        add_action('admin_post_lc_enrich_lead', [$this, 'handle_enrich_lead']);
        add_action('admin_post_lc_enrich_recent', [$this, 'handle_enrich_recent']);
        add_action('admin_post_lc_admin_reset_user_password', [$this, 'handle_admin_reset_user_password']);
        add_action('admin_post_lc_admin_lock_user', [$this, 'handle_admin_lock_user']);
        add_action('admin_post_lc_admin_unlock_user', [$this, 'handle_admin_unlock_user']);
        add_action('admin_post_lc_admin_approve_user', [$this, 'handle_admin_approve_user']);
        add_action('admin_post_lc_admin_reject_user', [$this, 'handle_admin_reject_user']);
        add_action('admin_post_lc_admin_update_user_profile', [$this, 'handle_admin_update_user_profile']);
        add_action('admin_post_lc_admin_create_user', [$this, 'handle_admin_create_user']);
        add_action('admin_post_lc_frontend_save_settings', [$this, 'handle_frontend_save_settings']);
        add_action('phpmailer_init', [$this, 'configure_smtp_mailer']);
        add_filter('cron_schedules', [$this, 'register_cron_schedules']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('lc_log_event', [$this, 'handle_external_log_event'], 10, 4);
        add_action('shutdown', [$this, 'capture_shutdown_errors']);
        add_action('lc_process_run', [$this, 'process_run_queue']);
        add_action('lc_smtp_health_check', [$this, 'run_smtp_health_check_cron']);
    }

    public function ensure_smtp_health_schedule()
    {
        if (!wp_next_scheduled('lc_smtp_health_check')) {
            wp_schedule_event(time() + 120, 'lc_every_30_minutes', 'lc_smtp_health_check');
        }
    }

    public function register_cron_schedules($schedules)
    {
        if (!isset($schedules['lc_every_30_minutes'])) {
            $schedules['lc_every_30_minutes'] = [
                'interval' => 30 * MINUTE_IN_SECONDS,
                'display' => 'Every 30 Minutes (Lead Console)',
            ];
        }
        return $schedules;
    }

    public function register_admin_menu()
    {
        if (!$this->is_allowed_admin_user()) {
            return;
        }

        $capability = 'manage_options';

        add_menu_page('5N2 Digital Lead Console Core', '5N2 Lead Console Core', $capability, 'lc_dashboard', [$this, 'render_dashboard'], 'dashicons-chart-line', 30);
        add_submenu_page('lc_dashboard', 'Dashboard', 'Dashboard', $capability, 'lc_dashboard', [$this, 'render_dashboard']);
        add_submenu_page('lc_dashboard', 'Leads', 'Leads', $capability, 'lc_leads', [$this, 'render_leads']);
        add_submenu_page('lc_dashboard', 'Runs', 'Runs', $capability, 'lc_runs', [$this, 'render_runs']);
        add_submenu_page('lc_dashboard', 'Duplicates', 'Duplicates', $capability, 'lc_duplicates', [$this, 'render_duplicates']);
        add_submenu_page('lc_dashboard', 'Exports', 'Exports', $capability, 'lc_exports', [$this, 'render_exports']);
        add_submenu_page('lc_dashboard', 'Suppression', 'Suppression', $capability, 'lc_suppression', [$this, 'render_suppression']);
        add_submenu_page('lc_dashboard', 'Intelligence', 'Intelligence', $capability, 'lc_intelligence', [$this, 'render_intelligence']);
        add_submenu_page('lc_dashboard', 'Users', 'Users', $capability, 'lc_users', [$this, 'render_users']);
        add_submenu_page('lc_dashboard', 'Logs', 'Logs', $capability, 'lc_logs', [$this, 'render_logs']);
        add_submenu_page('lc_dashboard', 'Reports', 'Reports', $capability, 'lc_reports', [$this, 'render_reports']);
        add_submenu_page('lc_dashboard', 'Platform', 'Platform', $capability, 'lc_platform', [$this, 'render_platform']);
        add_submenu_page('lc_dashboard', 'Settings', 'Settings', $capability, 'lc_settings', [$this, 'render_settings']);
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'lc_') === false) {
            return;
        }

        wp_enqueue_style('lc-admin', LC_PLUGIN_URL . 'assets/admin.css', [], LC_PLUGIN_VERSION);
    }

    public function register_settings()
    {
        register_setting('lc_settings_group', 'lc_settings', [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($settings)
    {
        $mode = sanitize_text_field($settings['discovery_mode'] ?? 'hybrid');
        if (!in_array($mode, ['hybrid', 'google_only', 'directory_only'], true)) {
            $mode = 'hybrid';
        }
        $social_mode = sanitize_text_field($settings['social_discovery_mode'] ?? 'off');
        if (!in_array($social_mode, ['off', 'url_discovery_only', 'official_api_enabled'], true)) {
            $social_mode = 'off';
        }

        $existing_settings = $this->get_settings();
        $presets = self::directory_source_presets();
        $selected_presets = [];
        $directory_presets_present = !empty($settings['directory_source_presets_present']);
        if (isset($settings['directory_source_presets']) && is_array($settings['directory_source_presets'])) {
            $selected_presets = $settings['directory_source_presets'];
        } elseif ($directory_presets_present) {
            $selected_presets = [];
        } elseif (isset($existing_settings['directory_source_presets']) && is_array($existing_settings['directory_source_presets'])) {
            $selected_presets = $existing_settings['directory_source_presets'];
        } else {
            $selected_presets = self::default_directory_source_preset_ids();
        }
        $selected_map = array_fill_keys(array_map('sanitize_key', $selected_presets), true);
        $active_sources = [];
        $scan_report = [];

        foreach ($presets as $preset) {
            $preset_id = sanitize_key((string) ($preset['id'] ?? ''));
            if ($preset_id === '' || empty($selected_map[$preset_id])) {
                continue;
            }
            $scan = $this->scan_directory_source_compatibility((string) ($preset['search_url'] ?? ''));
            $scan_report[] = [
                'label' => sanitize_text_field((string) ($preset['name'] ?? $preset_id)),
                'type' => 'preset',
                'status' => $scan['ok'] ? 'compatible' : 'incompatible',
                'details' => sanitize_text_field((string) ($scan['reason'] ?? '')),
            ];
            if ($scan['ok']) {
                $active_sources[] = [
                    'name' => sanitize_text_field((string) ($preset['name'] ?? '')),
                    'search_url' => esc_url_raw((string) ($preset['search_url'] ?? '')),
                    'quality_score' => absint($preset['quality_score'] ?? 70),
                ];
            }
        }

        $custom_raw = (string) ($settings['directory_custom_sites'] ?? ($existing_settings['directory_custom_sites'] ?? ''));
        $custom_lines = preg_split('/\r\n|\r|\n/', $custom_raw);
        if (is_array($custom_lines)) {
            foreach ($custom_lines as $line) {
                $line = trim((string) $line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line));
                $custom_name = '';
                $custom_domain = '';
                if (count($parts) >= 2) {
                    $custom_name = sanitize_text_field((string) $parts[0]);
                    $custom_domain = sanitize_text_field((string) $parts[1]);
                } else {
                    $custom_domain = sanitize_text_field((string) $parts[0]);
                    $custom_name = ucwords(str_replace(['-', '.'], ' ', $custom_domain));
                }
                $custom_domain = strtolower(preg_replace('#^https?://#', '', $custom_domain));
                $custom_domain = trim($custom_domain, '/');
                if ($custom_domain === '' || strpos($custom_domain, '.') === false) {
                    $scan_report[] = [
                        'label' => $custom_name !== '' ? $custom_name : $custom_domain,
                        'type' => 'custom',
                        'status' => 'invalid',
                        'details' => 'Invalid domain format.',
                    ];
                    continue;
                }
                $custom_url = 'https://' . $custom_domain . '/search?q={query}+{city}';
                $scan = $this->scan_directory_source_compatibility($custom_url);
                $scan_report[] = [
                    'label' => $custom_name,
                    'type' => 'custom',
                    'status' => $scan['ok'] ? 'compatible' : 'incompatible',
                    'details' => sanitize_text_field((string) ($scan['reason'] ?? '')),
                ];
                if ($scan['ok']) {
                    $active_sources[] = [
                        'name' => $custom_name,
                        'search_url' => esc_url_raw($custom_url),
                        'quality_score' => 60,
                    ];
                }
            }
        }

        if (empty($active_sources)) {
            $legacy_raw = trim((string) ($settings['directory_sources'] ?? ''));
            if ($legacy_raw !== '') {
                $legacy_lines = preg_split('/\r\n|\r|\n/', $legacy_raw);
                if (is_array($legacy_lines)) {
                    foreach ($legacy_lines as $line) {
                        $line = trim((string) $line);
                        if ($line === '') {
                            continue;
                        }
                        $parts = array_map('trim', explode('|', $line));
                        if (count($parts) < 3) {
                            continue;
                        }
                        $legacy_name = sanitize_text_field((string) $parts[0]);
                        $legacy_url = esc_url_raw((string) $parts[1]);
                        $legacy_quality = max(1, absint($parts[2]));
                        $scan = $this->scan_directory_source_compatibility($legacy_url);
                        $scan_report[] = [
                            'label' => $legacy_name,
                            'type' => 'legacy',
                            'status' => $scan['ok'] ? 'compatible' : 'incompatible',
                            'details' => sanitize_text_field((string) ($scan['reason'] ?? '')),
                        ];
                        if ($scan['ok']) {
                            $active_sources[] = [
                                'name' => $legacy_name,
                                'search_url' => $legacy_url,
                                'quality_score' => $legacy_quality,
                            ];
                        }
                    }
                }
            }
        }

        $compiled_sources = [];
        foreach ($active_sources as $source) {
            $compiled_sources[] = $source['name'] . '|' . $source['search_url'] . '|' . (int) $source['quality_score'];
        }
        update_option('lc_directory_source_scan_report', [
            'updated_at' => current_time('mysql'),
            'rows' => $scan_report,
        ]);

        return [
            'domain_fragment' => sanitize_text_field($settings['domain_fragment'] ?? ''),
            'max_places_per_run' => max(1, absint($settings['max_places_per_run'] ?? 25)),
            'platform_mode' => in_array(($settings['platform_mode'] ?? 'single_tenant'), ['single_tenant', 'multi_tenant_ready'], true) ? sanitize_text_field($settings['platform_mode']) : 'single_tenant',
            'notifications_web' => !empty($settings['notifications_web']) ? 1 : 0,
            'notifications_email' => !empty($settings['notifications_email']) ? 1 : 0,
            'notifications_sound' => !empty($settings['notifications_sound']) ? 1 : 0,
            'crm_connectors' => sanitize_textarea_field($settings['crm_connectors'] ?? ''),
            'social_connectors' => sanitize_textarea_field($settings['social_connectors'] ?? ''),
            'webops_monitors' => sanitize_textarea_field($settings['webops_monitors'] ?? ''),
            'seo_extension_enabled' => !empty($settings['seo_extension_enabled']) ? 1 : 0,
            'human_support_email' => sanitize_email($settings['human_support_email'] ?? ''),
            'bridge_enabled' => !empty($settings['bridge_enabled']) ? 1 : 0,
            'bridge_shared_key' => sanitize_text_field($settings['bridge_shared_key'] ?? ''),
            'enable_live_api_calls' => !empty($settings['enable_live_api_calls']) ? 1 : 0,
            'google_places_api_key' => sanitize_text_field($settings['google_places_api_key'] ?? ''),
            'discovery_mode' => $mode,
            'directory_sources' => sanitize_textarea_field(implode("\n", $compiled_sources)),
            'directory_source_presets' => array_values(array_map('sanitize_key', $selected_presets)),
            'directory_custom_sites' => sanitize_textarea_field($custom_raw),
            'social_discovery_mode' => $social_mode,
            'google_cse_api_key' => sanitize_text_field($settings['google_cse_api_key'] ?? ''),
            'google_cse_cx' => sanitize_text_field($settings['google_cse_cx'] ?? ''),
            'smtp_enabled' => !empty($settings['smtp_enabled']) ? 1 : 0,
            'smtp_host' => sanitize_text_field($settings['smtp_host'] ?? ''),
            'smtp_port' => max(1, absint($settings['smtp_port'] ?? 587)),
            'smtp_encryption' => in_array(($settings['smtp_encryption'] ?? 'tls'), ['none', 'ssl', 'tls'], true) ? sanitize_text_field($settings['smtp_encryption']) : 'tls',
            'smtp_auth' => !empty($settings['smtp_auth']) ? 1 : 0,
            'smtp_username' => sanitize_text_field($settings['smtp_username'] ?? ''),
            'smtp_password' => sanitize_text_field($settings['smtp_password'] ?? ''),
            'smtp_from_email' => sanitize_email($settings['smtp_from_email'] ?? ''),
            'smtp_from_name' => sanitize_text_field($settings['smtp_from_name'] ?? ''),
            'email_template_reset_subject' => sanitize_text_field($settings['email_template_reset_subject'] ?? ''),
            'email_template_reset_body' => wp_kses_post($settings['email_template_reset_body'] ?? ''),
            'email_template_password_changed_subject' => sanitize_text_field($settings['email_template_password_changed_subject'] ?? ''),
            'email_template_password_changed_body' => wp_kses_post($settings['email_template_password_changed_body'] ?? ''),
            'email_template_admin_password_changed_subject' => sanitize_text_field($settings['email_template_admin_password_changed_subject'] ?? ''),
            'email_template_admin_password_changed_body' => wp_kses_post($settings['email_template_admin_password_changed_body'] ?? ''),
            'email_template_registration_received_subject' => sanitize_text_field($settings['email_template_registration_received_subject'] ?? ''),
            'email_template_registration_received_body' => wp_kses_post($settings['email_template_registration_received_body'] ?? ''),
            'email_template_registration_admin_subject' => sanitize_text_field($settings['email_template_registration_admin_subject'] ?? ''),
            'email_template_registration_admin_body' => wp_kses_post($settings['email_template_registration_admin_body'] ?? ''),
            'email_template_registration_approved_subject' => sanitize_text_field($settings['email_template_registration_approved_subject'] ?? ''),
            'email_template_registration_approved_body' => wp_kses_post($settings['email_template_registration_approved_body'] ?? ''),
            'email_template_registration_rejected_subject' => sanitize_text_field($settings['email_template_registration_rejected_subject'] ?? ''),
            'email_template_registration_rejected_body' => wp_kses_post($settings['email_template_registration_rejected_body'] ?? ''),
        ];
    }

    public static function directory_source_presets()
    {
        return [
            [
                'id' => 'google_maps_public',
                'name' => 'Google Maps Public Search',
                'search_url' => 'https://www.google.com/maps/search/{query}+{city}',
                'quality_score' => 90,
                'compliance' => 'Public search URL only; follow Google terms and approved API policy where applicable.',
                'country' => 'Global',
                'source_type' => 'maps',
            ],
            [
                'id' => 'yelp',
                'name' => 'Yelp',
                'search_url' => 'https://www.yelp.com/search?find_desc={query}&find_loc={city}',
                'quality_score' => 88,
                'compliance' => 'Use listing metadata from public pages and comply with Yelp platform terms.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'yellow_pages',
                'name' => 'Yellow Pages',
                'search_url' => 'https://www.yellowpages.com/search?search_terms={query}&geo_location_terms={city}',
                'quality_score' => 82,
                'compliance' => 'Public listing directory source; respect site terms and robots policies.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'bbb',
                'name' => 'Better Business Bureau',
                'search_url' => 'https://www.bbb.org/search?find_text={query}&find_loc={city}',
                'quality_score' => 85,
                'compliance' => 'Public accreditation/listing lookup; respect BBB usage policies.',
                'country' => 'United States',
                'source_type' => 'verification',
            ],
            [
                'id' => 'chamber_of_commerce',
                'name' => 'Chamber of Commerce',
                'search_url' => 'https://www.chamberofcommerce.com/search?what={query}&where={city}',
                'quality_score' => 80,
                'compliance' => 'Public business listing directory; use only lawful contact data processing.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'manta',
                'name' => 'Manta',
                'search_url' => 'https://www.manta.com/search?search={query}+{city}',
                'quality_score' => 78,
                'compliance' => 'Public directory search source; follow Manta terms of use.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'hotfrog',
                'name' => 'Hotfrog',
                'search_url' => 'https://www.hotfrog.com/search/{query}/{city}',
                'quality_score' => 74,
                'compliance' => 'Public listing search endpoint with standard robots/terms checks.',
                'country' => 'Global',
                'source_type' => 'directory',
            ],
            [
                'id' => 'cylex',
                'name' => 'Cylex',
                'search_url' => 'https://www.cylex.us.com/s?q={query}+{city}',
                'quality_score' => 72,
                'compliance' => 'Public business listings source; usage must stay within published terms.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'merchantcircle',
                'name' => 'MerchantCircle',
                'search_url' => 'https://www.merchantcircle.com/search?what={query}&where={city}',
                'quality_score' => 70,
                'compliance' => 'Public profile directory source; automated scraping restrictions may apply.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'superpages',
                'name' => 'Superpages',
                'search_url' => 'https://www.superpages.com/search?C={query}&T={city}',
                'quality_score' => 76,
                'compliance' => 'Public directory source with HTTPS and robots checks required.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'dexknows',
                'name' => 'DexKnows',
                'search_url' => 'https://www.dexknows.com/search?query={query}&where={city}',
                'quality_score' => 73,
                'compliance' => 'Public local listings source; use only for approved business workflows.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'citysearch',
                'name' => 'Citysearch',
                'search_url' => 'https://www.citysearch.com/search?what={query}&where={city}',
                'quality_score' => 69,
                'compliance' => 'Public search listings source; always respect robots and provider terms.',
                'country' => 'United States',
                'source_type' => 'directory',
            ],
            [
                'id' => 'canada411',
                'name' => 'Canada411',
                'search_url' => 'https://www.canada411.ca/search/si/1/{query}/{city}',
                'quality_score' => 78,
                'compliance' => 'Canadian public listing source; use only approved business workflow capture.',
                'country' => 'Canada',
                'source_type' => 'directory',
            ],
            [
                'id' => 'yellowpages_ca',
                'name' => 'YellowPages Canada',
                'search_url' => 'https://www.yellowpages.ca/search/si/1/{query}/{city}',
                'quality_score' => 80,
                'compliance' => 'Public Canadian directory source; respect provider terms and robots rules.',
                'country' => 'Canada',
                'source_type' => 'directory',
            ],
            [
                'id' => 'yell_uk',
                'name' => 'Yell UK',
                'search_url' => 'https://www.yell.com/ucs/UcsSearchAction.do?keywords={query}&location={city}',
                'quality_score' => 82,
                'compliance' => 'UK business listing source; only use lawful and approved data collection.',
                'country' => 'United Kingdom',
                'source_type' => 'directory',
            ],
            [
                'id' => 'thomsonlocal_uk',
                'name' => 'Thomson Local UK',
                'search_url' => 'https://www.thomsonlocal.com/search/{query}/{city}',
                'quality_score' => 76,
                'compliance' => 'UK public listings source; follow terms and robots constraints.',
                'country' => 'United Kingdom',
                'source_type' => 'directory',
            ],
            [
                'id' => '192_uk',
                'name' => '192.com',
                'search_url' => 'https://www.192.com/results/?q={query}&location={city}',
                'quality_score' => 72,
                'compliance' => 'Public UK listing/search source; verify terms for commercial usage.',
                'country' => 'United Kingdom',
                'source_type' => 'directory',
            ],
            [
                'id' => 'truelocal_au',
                'name' => 'True Local Australia',
                'search_url' => 'https://www.truelocal.com.au/search/{query}/{city}',
                'quality_score' => 78,
                'compliance' => 'Australian public listing source; respect local privacy and usage policies.',
                'country' => 'Australia',
                'source_type' => 'directory',
            ],
            [
                'id' => 'yellowpages_au',
                'name' => 'Yellow Pages Australia',
                'search_url' => 'https://www.yellowpages.com.au/search/listings?clue={query}&locationClue={city}',
                'quality_score' => 80,
                'compliance' => 'Australian directory source with standard compliance checks required.',
                'country' => 'Australia',
                'source_type' => 'directory',
            ],
            [
                'id' => 'hotfrog_au',
                'name' => 'Hotfrog Australia',
                'search_url' => 'https://www.hotfrog.com.au/search/{query}/{city}',
                'quality_score' => 74,
                'compliance' => 'Public AU listing source; usage must remain within provider terms.',
                'country' => 'Australia',
                'source_type' => 'directory',
            ],
            [
                'id' => 'justdial_in',
                'name' => 'Justdial India',
                'search_url' => 'https://www.justdial.com/{city}/{query}',
                'quality_score' => 82,
                'compliance' => 'Indian local listings source; process only business-public data lawfully.',
                'country' => 'India',
                'source_type' => 'directory',
            ],
            [
                'id' => 'indiamart_in',
                'name' => 'IndiaMART',
                'search_url' => 'https://dir.indiamart.com/search.mp?ss={query}+{city}',
                'quality_score' => 75,
                'compliance' => 'B2B supplier directory source; usage subject to IndiaMART terms.',
                'country' => 'India',
                'source_type' => 'service_marketplace',
            ],
            [
                'id' => 'sulekha_in',
                'name' => 'Sulekha',
                'search_url' => 'https://www.sulekha.com/{query}/{city}',
                'quality_score' => 73,
                'compliance' => 'Local service marketplace source; follow platform terms and privacy obligations.',
                'country' => 'India',
                'source_type' => 'service_marketplace',
            ],
            [
                'id' => 'yellowpages_ph',
                'name' => 'Yellow Pages Philippines',
                'search_url' => 'https://www.yellow-pages.ph/search/{query}/{city}',
                'quality_score' => 74,
                'compliance' => 'PH public listings source; only approved and lawful business use.',
                'country' => 'Philippines',
                'source_type' => 'directory',
            ],
            [
                'id' => 'infobel_ph',
                'name' => 'Infobel Philippines',
                'search_url' => 'https://www.infobel.com/en/philippines/search/{query}/{city}',
                'quality_score' => 69,
                'compliance' => 'Public business directory source; must pass compatibility and policy checks.',
                'country' => 'Philippines',
                'source_type' => 'directory',
            ],
            [
                'id' => 'streetdirectory_sg',
                'name' => 'Streetdirectory Singapore',
                'search_url' => 'https://www.streetdirectory.com/businessfinder/company/{query}/{city}',
                'quality_score' => 71,
                'compliance' => 'Singapore public business source; usage must align with site policies.',
                'country' => 'Singapore',
                'source_type' => 'directory',
            ],
            [
                'id' => 'yellowpages_sg',
                'name' => 'Yellow Pages Singapore',
                'search_url' => 'https://www.yellowpages.com.sg/search?keywords={query}&location={city}',
                'quality_score' => 76,
                'compliance' => 'Singapore listing source; comply with published terms and legal obligations.',
                'country' => 'Singapore',
                'source_type' => 'directory',
            ],
            [
                'id' => 'yellowpages_ae',
                'name' => 'Yellow Pages UAE',
                'search_url' => 'https://www.yellowpages-uae.com/uae/{city}/{query}',
                'quality_score' => 74,
                'compliance' => 'UAE directory source; use only public business information lawfully.',
                'country' => 'United Arab Emirates',
                'source_type' => 'directory',
            ],
            [
                'id' => 'connect_ae',
                'name' => 'Connect.ae',
                'search_url' => 'https://connect.ae/search?keyword={query}&location={city}',
                'quality_score' => 72,
                'compliance' => 'UAE local business source; processing must remain policy-compliant.',
                'country' => 'United Arab Emirates',
                'source_type' => 'directory',
            ],
        ];
    }

    public static function default_directory_source_preset_ids()
    {
        return [
            'google_maps_public',
            'hotfrog',
            'yelp',
            'yellow_pages',
            'bbb',
            'canada411',
            'yell_uk',
            'yellowpages_au',
            'justdial_in',
            'yellowpages_sg',
            'yellowpages_ae',
        ];
    }

    private function scan_directory_source_compatibility($search_url)
    {
        $url = esc_url_raw((string) $search_url);
        if ($url === '' || strpos($url, '{query}') === false || strpos($url, '{city}') === false) {
            return ['ok' => false, 'reason' => 'Missing required placeholders {query} and {city}.'];
        }

        $host = wp_parse_url(str_replace(['{query}', '{city}'], ['sample', 'sample'], $url), PHP_URL_HOST);
        if (!$host) {
            return ['ok' => false, 'reason' => 'Could not parse source domain.'];
        }

        $probe_url = 'https://' . $host . '/';
        $head = wp_remote_head($probe_url, ['timeout' => 8]);
        if (is_wp_error($head)) {
            return ['ok' => false, 'reason' => 'Host is not reachable over HTTPS.'];
        }
        $code = (int) wp_remote_retrieve_response_code($head);
        if ($code < 200 || $code >= 400) {
            return ['ok' => false, 'reason' => 'Host response code indicates incompatibility: ' . $code];
        }

        $robots_url = 'https://' . $host . '/robots.txt';
        $robots = wp_remote_get($robots_url, ['timeout' => 8]);
        if (!is_wp_error($robots) && (int) wp_remote_retrieve_response_code($robots) < 400) {
            $body = strtolower((string) wp_remote_retrieve_body($robots));
            if (strpos($body, 'user-agent: *') !== false && strpos($body, 'disallow: /') !== false) {
                return ['ok' => false, 'reason' => 'robots.txt disallows general crawling.'];
            }
        }

        return ['ok' => true, 'reason' => 'Compatible: HTTPS reachable and no broad robots block detected.'];
    }

    private function ensure_permissions()
    {
        if (!current_user_can('manage_options') || !$this->is_allowed_admin_user()) {
            wp_die('Insufficient permissions.');
        }
    }

    public function disable_admin_bar_for_logged_in_users()
    {
        if (is_user_logged_in()) {
            show_admin_bar(false);
        }
    }

    public function enforce_frontend_only_mode()
    {
        if (!is_user_logged_in()) {
            return;
        }

        if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('WP_CLI') && WP_CLI)) {
            return;
        }

        $script = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
        if (in_array($script, ['admin-post.php', 'async-upload.php'], true)) {
            return;
        }

        // Allow the primary admin to access wp-admin for plugin/site maintenance.
        if ($this->is_allowed_admin_user()) {
            return;
        }

        if (is_admin()) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }

    private function get_primary_admin_email()
    {
        $configured = defined('LC_PRIMARY_ADMIN_EMAIL') ? strtolower(trim((string) LC_PRIMARY_ADMIN_EMAIL)) : '';
        if ($configured !== '') {
            return $configured;
        }
        return strtolower((string) get_option('admin_email'));
    }

    private function get_primary_admin_password()
    {
        return defined('LC_PRIMARY_ADMIN_PASSWORD') ? (string) LC_PRIMARY_ADMIN_PASSWORD : '';
    }

    private function is_allowed_admin_user()
    {
        if (!is_user_logged_in()) {
            return false;
        }
        $user = wp_get_current_user();
        $email = strtolower((string) ($user->user_email ?? ''));
        return $email === $this->get_primary_admin_email();
    }

    public function bootstrap_primary_admin_user()
    {
        if (!function_exists('wp_create_user')) {
            return;
        }

        $email = $this->get_primary_admin_email();
        $password = $this->get_primary_admin_password();
        if (empty($email) || empty($password)) {
            return;
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            $base_login = sanitize_user(strstr($email, '@', true) ?: 'leadconsoleadmin', true);
            $login = $base_login ?: 'leadconsoleadmin';
            $suffix = 1;
            while (username_exists($login)) {
                $login = $base_login . $suffix;
                $suffix++;
            }
            $user_id = wp_create_user($login, $password, $email);
            if (!is_wp_error($user_id) && $user_id) {
                $user = get_user_by('id', $user_id);
            }
        }

        if (!$user) {
            return;
        }

        $wp_user = new WP_User($user->ID);
        if (!$wp_user->has_cap('administrator')) {
            $wp_user->set_role('administrator');
        }

        if (is_multisite() && !is_super_admin($user->ID)) {
            grant_super_admin($user->ID);
        }

        // Keep the configured primary admin password in sync for recovery/login reliability.
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_set_password($password, $user->ID);
            $user = get_user_by('id', $user->ID);
        }

        update_user_meta($user->ID, 'lc_primary_admin', 1);
    }

    public function restrict_user_creation_capability($caps, $cap, $user_id, $args)
    {
        if ($cap !== 'create_users') {
            return $caps;
        }

        $user = get_user_by('id', $user_id);
        $email = strtolower((string) ($user->user_email ?? ''));
        if ($email !== $this->get_primary_admin_email()) {
            return ['do_not_allow'];
        }

        return $caps;
    }

    private function db_table($name)
    {
        global $wpdb;
        return $wpdb->prefix . $name;
    }

    public function handle_external_log_event($category, $level, $message, $context = [])
    {
        $this->log_system_event((string) $category, (string) $level, (string) $message, (array) $context);
    }

    private function log_system_event($category, $level, $message, $context = [])
    {
        global $wpdb;

        $allowed_levels = ['debug', 'info', 'warning', 'error', 'critical'];
        if (!in_array($level, $allowed_levels, true)) {
            $level = 'info';
        }

        $wpdb->insert($this->db_table('lc_system_logs'), [
            'category' => sanitize_text_field($category ?: 'system'),
            'level' => sanitize_text_field($level),
            'message' => sanitize_textarea_field($message),
            'context_json' => wp_json_encode($context),
            'user_id' => get_current_user_id() ? (int) get_current_user_id() : 0,
        ]);
    }

    public function capture_shutdown_errors()
    {
        $error = error_get_last();
        if (!$error || !is_array($error)) {
            return;
        }

        $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int) ($error['type'] ?? 0), $fatal_types, true)) {
            return;
        }

        $this->log_system_event('system', 'critical', 'Fatal runtime error captured.', [
            'type' => (int) ($error['type'] ?? 0),
            'message' => (string) ($error['message'] ?? ''),
            'file' => (string) ($error['file'] ?? ''),
            'line' => (int) ($error['line'] ?? 0),
        ]);
    }

    public function maybe_upgrade_schema()
    {
        $version = get_option('lc_schema_version', '1');
        if ($version === '7') {
            return;
        }

        global $wpdb;
        $table = $this->db_table('lc_leads');
        $columns = $wpdb->get_col("DESC {$table}", 0);
        if (empty($columns) || !is_array($columns)) {
            return;
        }

        $column_sql = [
            'linkedin_url' => "ALTER TABLE {$table} ADD COLUMN linkedin_url VARCHAR(255) DEFAULT ''",
            'facebook_url' => "ALTER TABLE {$table} ADD COLUMN facebook_url VARCHAR(255) DEFAULT ''",
            'instagram_url' => "ALTER TABLE {$table} ADD COLUMN instagram_url VARCHAR(255) DEFAULT ''",
            'x_url' => "ALTER TABLE {$table} ADD COLUMN x_url VARCHAR(255) DEFAULT ''",
            'youtube_url' => "ALTER TABLE {$table} ADD COLUMN youtube_url VARCHAR(255) DEFAULT ''",
            'social_confidence' => "ALTER TABLE {$table} ADD COLUMN social_confidence TINYINT UNSIGNED DEFAULT 0",
            'social_source' => "ALTER TABLE {$table} ADD COLUMN social_source VARCHAR(80) DEFAULT ''",
        ];

        foreach ($column_sql as $column => $sql) {
            if (!in_array($column, $columns, true)) {
                $wpdb->query($sql);
            }
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $profiles_table = $this->db_table('lc_lead_profiles');
        $sql_profiles = "CREATE TABLE {$profiles_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT UNSIGNED NOT NULL,
            possible_emails LONGTEXT NULL,
            primary_email VARCHAR(190) DEFAULT '',
            socials_json LONGTEXT NULL,
            people_json LONGTEXT NULL,
            company_json LONGTEXT NULL,
            reviews_json LONGTEXT NULL,
            jobs_json LONGTEXT NULL,
            completeness_score TINYINT UNSIGNED DEFAULT 0,
            confidence_score TINYINT UNSIGNED DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY lead_id (lead_id)
        ) {$charset};";
        dbDelta($sql_profiles);

        $system_logs_table = $this->db_table('lc_system_logs');
        $sql_system_logs = "CREATE TABLE {$system_logs_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            category VARCHAR(40) DEFAULT 'system',
            level VARCHAR(20) DEFAULT 'info',
            message TEXT NOT NULL,
            context_json LONGTEXT NULL,
            user_id BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY category (category),
            KEY user_id (user_id)
        ) {$charset};";
        dbDelta($sql_system_logs);

        $runs_table = $this->db_table('lc_runs');
        $run_columns = $wpdb->get_col("DESC {$runs_table}", 0);
        if (!empty($run_columns) && is_array($run_columns)) {
            $run_column_sql = [
                'state' => "ALTER TABLE {$runs_table} ADD COLUMN state VARCHAR(120) DEFAULT ''",
                'country' => "ALTER TABLE {$runs_table} ADD COLUMN country VARCHAR(120) DEFAULT ''",
                'radius_miles' => "ALTER TABLE {$runs_table} ADD COLUMN radius_miles INT DEFAULT 0",
                'niche' => "ALTER TABLE {$runs_table} ADD COLUMN niche VARCHAR(190) DEFAULT ''",
                'services' => "ALTER TABLE {$runs_table} ADD COLUMN services TEXT NULL",
                'website_focus' => "ALTER TABLE {$runs_table} ADD COLUMN website_focus VARCHAR(20) DEFAULT 'any'",
                'min_rating' => "ALTER TABLE {$runs_table} ADD COLUMN min_rating DECIMAL(3,1) DEFAULT 0",
                'min_reviews' => "ALTER TABLE {$runs_table} ADD COLUMN min_reviews INT DEFAULT 0",
                'save_mode' => "ALTER TABLE {$runs_table} ADD COLUMN save_mode VARCHAR(20) DEFAULT 'direct'",
                'review_status' => "ALTER TABLE {$runs_table} ADD COLUMN review_status VARCHAR(20) DEFAULT 'na'",
                'captured_leads' => "ALTER TABLE {$runs_table} ADD COLUMN captured_leads INT DEFAULT 0",
            ];
            foreach ($run_column_sql as $column => $sql) {
                if (!in_array($column, $run_columns, true)) {
                    $wpdb->query($sql);
                }
            }
        }

        $drafts_table = $this->db_table('lc_run_drafts');
        $sql_run_drafts = "CREATE TABLE {$drafts_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            run_id BIGINT UNSIGNED NOT NULL,
            business_name VARCHAR(190) NOT NULL,
            city VARCHAR(120) DEFAULT '',
            category VARCHAR(120) DEFAULT '',
            address VARCHAR(255) DEFAULT '',
            website VARCHAR(255) DEFAULT '',
            phone VARCHAR(50) DEFAULT '',
            email VARCHAR(190) DEFAULT '',
            review_count INT DEFAULT 0,
            rating DECIMAL(3,1) DEFAULT 0,
            status VARCHAR(30) DEFAULT 'New',
            score TINYINT UNSIGNED DEFAULT 0,
            lead_type CHAR(1) DEFAULT 'C',
            source_url VARCHAR(255) DEFAULT '',
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY run_id (run_id)
        ) {$charset};";
        dbDelta($sql_run_drafts);

        update_option('lc_schema_version', '7');
    }

    private function get_settings()
    {
        $defaults = [
            'domain_fragment' => '',
            'max_places_per_run' => 25,
            'platform_mode' => 'single_tenant',
            'notifications_web' => 1,
            'notifications_email' => 1,
            'notifications_sound' => 0,
            'crm_connectors' => '',
            'social_connectors' => '',
            'webops_monitors' => '',
            'seo_extension_enabled' => 0,
            'human_support_email' => '',
            'bridge_enabled' => 0,
            'bridge_shared_key' => '',
            'enable_live_api_calls' => 0,
            'google_places_api_key' => '',
            'discovery_mode' => 'hybrid',
            'directory_sources' => '',
            'directory_source_presets' => self::default_directory_source_preset_ids(),
            'directory_custom_sites' => '',
            'social_discovery_mode' => 'off',
            'google_cse_api_key' => '',
            'google_cse_cx' => '',
            'smtp_enabled' => 0,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_auth' => 1,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => '',
            'email_template_reset_subject' => '5N2 Digital Lead Console Core Password Reset',
            'email_template_reset_body' => "Hello {first_name},\n\nA password reset was requested for your account.\n\nReset link (valid for 5 minutes):\n{reset_link}\n\nIf you did not request this, ignore this email.\n\n{site_name}",
            'email_template_password_changed_subject' => 'Your 5N2 Digital Lead Console Core password was changed',
            'email_template_password_changed_body' => "Hello {first_name},\n\nYour password was changed.\n\nIf this was not you, click this revoke link within 5 minutes:\n{revoke_link}\n\n{site_name}",
            'email_template_admin_password_changed_subject' => 'Lead Console Alert: Password changed',
            'email_template_admin_password_changed_body' => "User password changed.\n\nUser: {user_email}\nTime: {current_time}\n\nRevoke window: 5 minutes.",
            'email_template_registration_received_subject' => 'Registration request received',
            'email_template_registration_received_body' => "Hello {first_name},\n\nYour registration request was received and is pending admin approval.\n\n{site_name}",
            'email_template_registration_admin_subject' => 'New registration request pending approval',
            'email_template_registration_admin_body' => "A new user registration request was submitted.\n\nName: {full_name}\nEmail: {user_email}\nCompany: {company}\nPhone: {phone}\n\nPlease review in Users tab.",
            'email_template_registration_approved_subject' => 'Registration approved',
            'email_template_registration_approved_body' => "Hello {first_name},\n\nYour account is now approved. You can sign in.\n\n{site_name}",
            'email_template_registration_rejected_subject' => 'Registration declined',
            'email_template_registration_rejected_body' => "Hello {first_name},\n\nYour registration request was declined. Please contact admin for details.\n\n{site_name}",
        ];

        return wp_parse_args(get_option('lc_settings', []), $defaults);
    }

    public function get_settings_snapshot()
    {
        return $this->get_settings();
    }

    public function register_rest_routes()
    {
        register_rest_route('lc/v1', '/bridge/status', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_status'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/push-approved', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_push_approved'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/run-reviews', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_run_reviews'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/run-review', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_run_review'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/run-review/save', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_run_review_save'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/run-review/discard', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_run_review_discard'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/run-review/draft-update', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_run_review_draft_update'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/smtp-health', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_smtp_health'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/smtp-probe', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_smtp_probe'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/smtp-send-test', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_smtp_send_test'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/smtp-confirm', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_smtp_confirm'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/email-templates', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_email_templates'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/email-templates', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_email_templates_save'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/email-templates/preview', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_email_templates_preview'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/email-templates/test-send', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_email_templates_test_send'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/email-templates/test-log', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_email_templates_test_log'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/crm-intake', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_crm_intake'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/social-intake', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_social_intake'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/site-health', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_site_health'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/wp-heartbeat', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_wp_heartbeat'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/update-health', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_bridge_update_health'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);

        register_rest_route('lc/v1', '/bridge/ops-action', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_bridge_ops_action'],
            'permission_callback' => [$this, 'rest_bridge_permission'],
        ]);
    }

    public function rest_bridge_permission($request)
    {
        $settings = $this->get_settings();
        if (empty($settings['bridge_enabled'])) {
            return false;
        }
        $expected = trim((string) ($settings['bridge_shared_key'] ?? ''));
        if ($expected === '') {
            return false;
        }
        $incoming = trim((string) $request->get_header('x-lc-bridge-key'));
        return hash_equals($expected, $incoming);
    }

    public function rest_bridge_status($request)
    {
        $settings = $this->get_settings();
        return rest_ensure_response([
            'ok' => true,
            'service' => 'lead-console-plugin-bridge',
            'site_url' => home_url('/'),
            'plugin_version' => LC_PLUGIN_VERSION,
            'bridge_enabled' => !empty($settings['bridge_enabled']),
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_push_approved($request)
    {
        global $wpdb;

        $leads = $wpdb->get_results(
            "SELECT id, business_name, city, category, website, phone, email, status, created_at
             FROM {$this->db_table('lc_leads')}
             WHERE status IN ('Ready','Verified')
             ORDER BY id DESC
             LIMIT 100",
            ARRAY_A
        );

        $this->log_system_event('bridge', 'info', 'Bridge approved leads sync requested.', [
            'count' => count($leads),
        ]);

        return rest_ensure_response([
            'ok' => true,
            'count' => count($leads),
            'leads' => $leads,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_run_reviews($request)
    {
        global $wpdb;

        $limit = absint($request->get_param('limit'));
        if ($limit <= 0) {
            $limit = 20;
        }
        $limit = min($limit, 100);
        $review_status = sanitize_text_field((string) ($request->get_param('review_status') ?? 'pending'));
        $runs_table = $this->db_table('lc_runs');
        $drafts_table = $this->db_table('lc_run_drafts');

        if ($review_status !== '') {
            $runs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, query_text, city, state, country, niche, services, captured_leads, save_mode, review_status, status, created_at, started_at, finished_at
                     FROM {$runs_table}
                     WHERE review_status = %s
                     ORDER BY id DESC
                     LIMIT %d",
                    $review_status,
                    $limit
                ),
                ARRAY_A
            );
        } else {
            $runs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, query_text, city, state, country, niche, services, captured_leads, save_mode, review_status, status, created_at, started_at, finished_at
                     FROM {$runs_table}
                     ORDER BY id DESC
                     LIMIT %d",
                    $limit
                ),
                ARRAY_A
            );
        }

        $items = [];
        foreach ((array) $runs as $run) {
            $run_id = absint($run['id'] ?? 0);
            $draft_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$drafts_table} WHERE run_id = %d", $run_id));
            $items[] = [
                'run_id' => $run_id,
                'query_text' => (string) ($run['query_text'] ?? ''),
                'city' => (string) ($run['city'] ?? ''),
                'state' => (string) ($run['state'] ?? ''),
                'country' => (string) ($run['country'] ?? ''),
                'niche' => (string) ($run['niche'] ?? ''),
                'services' => (string) ($run['services'] ?? ''),
                'captured_leads' => (int) ($run['captured_leads'] ?? 0),
                'draft_count' => $draft_count,
                'save_mode' => (string) ($run['save_mode'] ?? ''),
                'review_status' => (string) ($run['review_status'] ?? 'na'),
                'status' => (string) ($run['status'] ?? ''),
                'created_at' => (string) ($run['created_at'] ?? ''),
                'started_at' => (string) ($run['started_at'] ?? ''),
                'finished_at' => (string) ($run['finished_at'] ?? ''),
            ];
        }

        $this->log_system_event('bridge', 'info', 'Bridge run reviews requested.', [
            'review_status' => $review_status,
            'count' => count($items),
        ]);

        return rest_ensure_response([
            'ok' => true,
            'count' => count($items),
            'items' => $items,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_run_review($request)
    {
        global $wpdb;

        $run_id = absint($request->get_param('run_id'));
        if ($run_id <= 0) {
            return new WP_REST_Response(['ok' => false, 'error' => 'run_id is required.'], 400);
        }

        $run = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, query_text, city, state, country, radius_miles, niche, services, website_focus, min_rating, min_reviews, max_places, captured_leads, save_mode, review_status, status, created_at, started_at, finished_at
                 FROM {$this->db_table('lc_runs')}
                 WHERE id = %d
                 LIMIT 1",
                $run_id
            ),
            ARRAY_A
        );
        if (!$run) {
            return new WP_REST_Response(['ok' => false, 'error' => 'Run not found.'], 404);
        }

        $logs = $wpdb->get_results($wpdb->prepare("SELECT level, message, created_at FROM {$this->db_table('lc_run_logs')} WHERE run_id = %d ORDER BY id ASC LIMIT 120", $run_id), ARRAY_A);
        $draft_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->db_table('lc_run_drafts')} WHERE run_id = %d", $run_id));
        $draft_preview = $wpdb->get_results($wpdb->prepare("SELECT id, business_name, city, category, website, phone, email, score, lead_type, notes, status, created_at FROM {$this->db_table('lc_run_drafts')} WHERE run_id = %d ORDER BY id DESC LIMIT 80", $run_id), ARRAY_A);

        $this->log_system_event('bridge', 'info', 'Bridge run review requested.', [
            'run_id' => $run_id,
            'draft_count' => $draft_count,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'run' => [
                'id' => (int) $run['id'],
                'status' => (string) ($run['status'] ?? ''),
                'review_status' => (string) ($run['review_status'] ?? 'na'),
                'captured_leads' => (int) ($run['captured_leads'] ?? 0),
                'draft_count' => $draft_count,
                'save_mode' => (string) ($run['save_mode'] ?? ''),
                'params' => [
                    'query_text' => (string) ($run['query_text'] ?? ''),
                    'city' => (string) ($run['city'] ?? ''),
                    'state' => (string) ($run['state'] ?? ''),
                    'country' => (string) ($run['country'] ?? ''),
                    'niche' => (string) ($run['niche'] ?? ''),
                    'services' => (string) ($run['services'] ?? ''),
                    'radius_miles' => (int) ($run['radius_miles'] ?? 0),
                    'min_rating' => (float) ($run['min_rating'] ?? 0),
                    'min_reviews' => (int) ($run['min_reviews'] ?? 0),
                    'max_places' => (int) ($run['max_places'] ?? 0),
                    'website_focus' => (string) ($run['website_focus'] ?? 'any'),
                ],
                'created_at' => (string) ($run['created_at'] ?? ''),
                'started_at' => (string) ($run['started_at'] ?? ''),
                'finished_at' => (string) ($run['finished_at'] ?? ''),
            ],
            'logs' => is_array($logs) ? $logs : [],
            'draft_preview' => is_array($draft_preview) ? $draft_preview : [],
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_run_review_save($request)
    {
        global $wpdb;

        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $run_id = absint($payload['run_id'] ?? 0);
        if ($run_id <= 0) {
            return new WP_REST_Response(['ok' => false, 'error' => 'run_id is required.'], 400);
        }

        $drafts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->db_table('lc_run_drafts')} WHERE run_id = %d ORDER BY id ASC", $run_id), ARRAY_A);
        $saved = 0;
        foreach ($drafts as $draft) {
            $inserted = $wpdb->insert($this->db_table('lc_leads'), [
                'business_name' => sanitize_text_field((string) ($draft['business_name'] ?? '')),
                'city' => sanitize_text_field((string) ($draft['city'] ?? '')),
                'category' => sanitize_text_field((string) ($draft['category'] ?? '')),
                'address' => sanitize_text_field((string) ($draft['address'] ?? '')),
                'website' => esc_url_raw((string) ($draft['website'] ?? '')),
                'phone' => sanitize_text_field((string) ($draft['phone'] ?? '')),
                'email' => sanitize_email((string) ($draft['email'] ?? '')),
                'email_confidence' => '',
                'review_count' => absint($draft['review_count'] ?? 0),
                'rating' => (float) ($draft['rating'] ?? 0),
                'status' => $this->normalize_status((string) ($draft['status'] ?? 'New')),
                'score' => absint($draft['score'] ?? 0),
                'lead_type' => sanitize_text_field((string) ($draft['lead_type'] ?? 'C')),
                'source_url' => esc_url_raw((string) ($draft['source_url'] ?? '')),
                'notes' => sanitize_textarea_field((string) ($draft['notes'] ?? '')),
            ]);
            if ($inserted) {
                $saved++;
            }
        }

        $wpdb->delete($this->db_table('lc_run_drafts'), ['run_id' => $run_id], ['%d']);
        $wpdb->update($this->db_table('lc_runs'), ['review_status' => 'saved'], ['id' => $run_id], ['%s'], ['%d']);
        $this->log_system_event('bridge', 'info', 'Bridge run review drafts saved.', [
            'run_id' => $run_id,
            'saved' => $saved,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'run_id' => $run_id,
            'saved' => $saved,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_run_review_discard($request)
    {
        global $wpdb;

        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $run_id = absint($payload['run_id'] ?? 0);
        if ($run_id <= 0) {
            return new WP_REST_Response(['ok' => false, 'error' => 'run_id is required.'], 400);
        }

        $wpdb->delete($this->db_table('lc_run_drafts'), ['run_id' => $run_id], ['%d']);
        $wpdb->update($this->db_table('lc_runs'), ['review_status' => 'discarded'], ['id' => $run_id], ['%s'], ['%d']);
        $this->log_system_event('bridge', 'info', 'Bridge run review drafts discarded.', [
            'run_id' => $run_id,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'run_id' => $run_id,
            'discarded' => true,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_run_review_draft_update($request)
    {
        global $wpdb;

        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $run_id = absint($payload['run_id'] ?? 0);
        $draft_id = absint($payload['draft_id'] ?? 0);
        if ($run_id <= 0 || $draft_id <= 0) {
            return new WP_REST_Response(['ok' => false, 'error' => 'run_id and draft_id are required.'], 400);
        }

        $update = [];
        $format = [];
        $fieldMap = [
            'business_name' => 'sanitize_text_field',
            'city' => 'sanitize_text_field',
            'category' => 'sanitize_text_field',
            'website' => 'esc_url_raw',
            'phone' => 'sanitize_text_field',
            'email' => 'sanitize_email',
            'status' => 'sanitize_text_field',
            'notes' => 'sanitize_textarea_field',
        ];
        foreach ($fieldMap as $field => $sanitizer) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }
            $value = call_user_func($sanitizer, (string) $payload[$field]);
            if ($field === 'status') {
                $value = $this->normalize_status($value);
            }
            $update[$field] = $value;
            $format[] = '%s';
        }
        if (empty($update)) {
            return new WP_REST_Response(['ok' => false, 'error' => 'No draft fields provided.'], 400);
        }

        $updated = $wpdb->update(
            $this->db_table('lc_run_drafts'),
            $update,
            ['id' => $draft_id, 'run_id' => $run_id],
            $format,
            ['%d', '%d']
        );
        if ($updated === false) {
            return new WP_REST_Response(['ok' => false, 'error' => 'Draft update failed.'], 500);
        }

        $draft = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, run_id, business_name, city, category, website, phone, email, score, lead_type, notes, status, created_at
                 FROM {$this->db_table('lc_run_drafts')}
                 WHERE id = %d AND run_id = %d
                 LIMIT 1",
                $draft_id,
                $run_id
            ),
            ARRAY_A
        );

        $this->log_system_event('bridge', 'info', 'Bridge run review draft updated.', [
            'run_id' => $run_id,
            'draft_id' => $draft_id,
            'fields' => array_keys($update),
        ]);

        return rest_ensure_response([
            'ok' => true,
            'run_id' => $run_id,
            'draft_id' => $draft_id,
            'draft' => $draft,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_smtp_health($request)
    {
        $status = $this->get_smtp_health_status();
        $confirmation = $this->get_smtp_test_confirmation();
        $this->log_system_event('bridge', 'info', 'Bridge SMTP health requested.', [
            'connected' => !empty($status['connected']),
            'checked_at' => (string) ($status['checked_at'] ?? ''),
        ]);

        return rest_ensure_response([
            'ok' => true,
            'smtp_health' => $status,
            'test_confirmation' => $confirmation,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_smtp_probe($request)
    {
        $result = $this->smtp_connection_probe();
        $status = $this->update_smtp_health_status($result);
        $this->log_system_event('bridge', !empty($status['connected']) ? 'info' : 'error', 'Bridge SMTP connection probe executed.', [
            'status' => $status,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'smtp_health' => $status,
            'test_confirmation' => $this->get_smtp_test_confirmation(),
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_smtp_send_test($request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $to_email = sanitize_email((string) ($payload['to_email'] ?? ''));
        $result = $this->smtp_send_test_email($to_email);
        if (!empty($result['success'])) {
            $this->set_smtp_test_confirmation_state([
                'confirmed' => 0,
                'pending' => 1,
                'to_email' => $to_email,
                'sent_at' => current_time('mysql'),
                'confirmed_at' => '',
                'last_result' => 'sent',
                'last_error_code' => '',
            ]);
        } else {
            $this->update_smtp_health_status([
                'connected' => false,
                'message' => 'SMTP send test failed: ' . (string) ($result['message'] ?? 'Unknown send error.'),
                'checked_at' => current_time('mysql'),
            ]);
            $this->set_smtp_test_confirmation_state([
                'confirmed' => 0,
                'pending' => 0,
                'to_email' => $to_email,
                'sent_at' => current_time('mysql'),
                'confirmed_at' => '',
                'last_result' => 'failed',
                'last_error_code' => (string) ($result['error_code'] ?? 'send_failed'),
            ]);
        }
        $this->log_system_event('bridge', !empty($result['success']) ? 'info' : 'error', 'Bridge SMTP test email executed.', [
            'to_email' => $to_email,
            'result' => $result,
        ]);

        return rest_ensure_response([
            'ok' => !empty($result['success']),
            'result' => $result,
            'smtp_health' => $this->get_smtp_health_status(),
            'test_confirmation' => $this->get_smtp_test_confirmation(),
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_smtp_confirm($request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $received = !empty($payload['received']) ? 1 : 0;
        $to_email = sanitize_email((string) ($payload['to_email'] ?? ''));
        $state = $this->set_smtp_test_confirmation_state([
            'confirmed' => $received,
            'pending' => 0,
            'to_email' => $to_email,
            'confirmed_at' => $received ? current_time('mysql') : '',
            'last_result' => $received ? 'confirmed_yes' : 'confirmed_no',
            'last_error_code' => '',
        ]);
        $this->log_system_event('bridge', $received ? 'info' : 'warning', 'Bridge SMTP test email confirmation received.', [
            'received' => $received,
            'to_email' => $to_email,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'received' => (bool) $received,
            'test_confirmation' => $state,
            'smtp_health' => $this->get_smtp_health_status(),
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_email_templates($request)
    {
        $templates = $this->get_email_templates_snapshot();
        $this->log_system_event('bridge', 'info', 'Bridge email templates requested.', [
            'template_count' => count((array) ($templates['templates'] ?? [])),
        ]);

        return rest_ensure_response([
            'ok' => true,
            'email_templates' => $templates,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_email_templates_save($request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $templates = isset($payload['templates']) && is_array($payload['templates']) ? $payload['templates'] : [];
        $saved = $this->save_email_templates_snapshot($templates);
        $this->log_system_event('bridge', 'info', 'Bridge email templates updated.', [
            'template_count' => count((array) ($saved['templates'] ?? [])),
        ]);

        return rest_ensure_response([
            'ok' => true,
            'email_templates' => $saved,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_email_templates_preview($request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $template_key = sanitize_key((string) ($payload['template_key'] ?? ''));
        $vars = isset($payload['vars']) && is_array($payload['vars']) ? $payload['vars'] : [];
        $subject_override = array_key_exists('subject', $payload) ? (string) ($payload['subject'] ?? '') : null;
        $body_override = array_key_exists('body', $payload) ? (string) ($payload['body'] ?? '') : null;
        $preview = $this->get_email_template_preview($template_key, $vars, $subject_override, $body_override);
        $this->log_system_event('bridge', 'info', 'Bridge email template preview requested.', [
            'template_key' => $template_key,
        ]);

        return rest_ensure_response([
            'ok' => !empty($preview['ok']),
            'preview' => $preview,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_email_templates_test_send($request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $template_key = sanitize_key((string) ($payload['template_key'] ?? ''));
        $to_email = sanitize_email((string) ($payload['to_email'] ?? ''));
        $vars = isset($payload['vars']) && is_array($payload['vars']) ? $payload['vars'] : [];
        $result = $this->send_templated_test_email($to_email, $template_key, $vars);
        $log = $this->append_email_template_test_log($template_key, $to_email, $result);
        $this->log_system_event('bridge', !empty($result['success']) ? 'info' : 'error', 'Bridge email template test send executed.', [
            'template_key' => $template_key,
            'to_email' => $to_email,
            'result' => $result,
        ]);

        return rest_ensure_response([
            'ok' => !empty($result['success']),
            'result' => $result,
            'log' => $log,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_email_templates_test_log($request)
    {
        $limit = (int) $request->get_param('limit');
        if ($limit <= 0) {
            $limit = 25;
        }
        $items = $this->get_email_template_test_log($limit);
        $this->log_system_event('bridge', 'info', 'Bridge email template test log requested.', [
            'count' => count($items),
            'limit' => $limit,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'items' => $items,
            'count' => count($items),
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_crm_intake($request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $leads = isset($payload['leads']) && is_array($payload['leads']) ? $payload['leads'] : [];
        $provider = sanitize_text_field((string) ($payload['provider'] ?? 'unknown'));
        $connector_id = sanitize_text_field((string) ($payload['connector_id'] ?? ''));

        $accepted = 0;
        foreach ($leads as $lead) {
            if (!is_array($lead)) {
                continue;
            }
            $name = trim((string) ($lead['business_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $accepted++;
        }

        $this->log_system_event('bridge', 'info', 'Bridge CRM intake received.', [
            'provider' => $provider,
            'connector_id' => $connector_id,
            'received' => count($leads),
            'accepted' => $accepted,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'provider' => $provider,
            'connector_id' => $connector_id,
            'received' => count($leads),
            'accepted' => $accepted,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_social_intake($request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }
        $drafts = isset($payload['drafts']) && is_array($payload['drafts']) ? $payload['drafts'] : [];
        $provider = sanitize_text_field((string) ($payload['provider'] ?? 'unknown'));
        $connector_id = sanitize_text_field((string) ($payload['connector_id'] ?? ''));

        $accepted = 0;
        foreach ($drafts as $draft) {
            if (!is_array($draft)) {
                continue;
            }
            $message = trim((string) ($draft['message'] ?? ''));
            if ($message === '') {
                continue;
            }
            $accepted++;
        }

        $this->log_system_event('bridge', 'info', 'Bridge social intake received.', [
            'provider' => $provider,
            'connector_id' => $connector_id,
            'received' => count($drafts),
            'accepted' => $accepted,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'provider' => $provider,
            'connector_id' => $connector_id,
            'received' => count($drafts),
            'accepted' => $accepted,
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_site_health($request)
    {
        global $wpdb;
        $leads_table = $this->db_table('lc_leads');
        $runs_table = $this->db_table('lc_runs');
        $logs_table = $this->db_table('lc_system_logs');
        $total_leads = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table}");
        $ready_leads = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE status IN ('Ready','Verified')");
        $queued_runs = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$runs_table} WHERE status = 'queued'");
        $recent_errors = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$logs_table} WHERE level = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $smtp = $this->get_smtp_health_status();
        $overall = $recent_errors > 0 ? 'warning' : 'ok';
        if (empty($smtp['connected'])) {
            $overall = 'warning';
        }

        $this->log_system_event('bridge', 'info', 'Bridge site health requested.', [
            'overall' => $overall,
            'queued_runs' => $queued_runs,
            'recent_errors' => $recent_errors,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'overall' => $overall,
            'metrics' => [
                'total_leads' => $total_leads,
                'ready_leads' => $ready_leads,
                'queued_runs' => $queued_runs,
                'recent_errors_24h' => $recent_errors,
                'smtp_connected' => !empty($smtp['connected']),
            ],
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_wp_heartbeat($request)
    {
        global $wp_version;

        $payload = [
            'ok' => true,
            'site_url' => home_url('/'),
            'wp_version' => (string) $wp_version,
            'php_version' => PHP_VERSION,
            'time' => current_time('mysql'),
        ];

        $this->log_system_event('bridge', 'info', 'Bridge WordPress heartbeat requested.', [
            'wp_version' => (string) $wp_version,
        ]);

        return rest_ensure_response($payload);
    }

    public function rest_bridge_update_health($request)
    {
        if (!function_exists('get_core_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $core_updates = get_core_updates(['dismissed' => false]);
        $plugin_updates = get_site_transient('update_plugins');
        $theme_updates = get_site_transient('update_themes');

        $core_count = is_array($core_updates) ? count($core_updates) : 0;
        $plugin_count = (is_object($plugin_updates) && !empty($plugin_updates->response) && is_array($plugin_updates->response))
            ? count($plugin_updates->response)
            : 0;
        $theme_count = (is_object($theme_updates) && !empty($theme_updates->response) && is_array($theme_updates->response))
            ? count($theme_updates->response)
            : 0;

        $this->log_system_event('bridge', 'info', 'Bridge update health requested.', [
            'core_updates' => $core_count,
            'plugin_updates' => $plugin_count,
            'theme_updates' => $theme_count,
        ]);

        return rest_ensure_response([
            'ok' => true,
            'updates' => [
                'core' => $core_count,
                'plugins' => $plugin_count,
                'themes' => $theme_count,
            ],
            'time' => current_time('mysql'),
        ]);
    }

    public function rest_bridge_ops_action($request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }

        $action_type = sanitize_text_field((string) ($payload['action_type'] ?? ''));
        $plugin_file = sanitize_text_field((string) ($payload['plugin_file'] ?? ''));
        $desired_state = sanitize_text_field((string) ($payload['desired_state'] ?? ''));
        $run_mode = sanitize_text_field((string) ($payload['run_mode'] ?? 'dry_run'));

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ($action_type === 'update_check') {
            return $this->rest_bridge_update_health($request);
        }

        if ($action_type === 'plugin_toggle') {
            $active = ($plugin_file !== '' && function_exists('is_plugin_active')) ? is_plugin_active($plugin_file) : false;
            $response = [
                'ok' => true,
                'action_type' => $action_type,
                'plugin_file' => $plugin_file,
                'desired_state' => $desired_state,
                'run_mode' => $run_mode,
                'previous_state' => $active ? 'active' : 'inactive',
                'executed' => false,
                'simulated' => ($run_mode !== 'live'),
            ];

            if ($plugin_file === '') {
                $response['ok'] = false;
                $response['message'] = 'plugin_file is required.';
                return rest_ensure_response($response);
            }

            if ($run_mode !== 'live') {
                $response['message'] = 'Dry run plugin toggle simulated.';
                $this->log_system_event('bridge', 'info', 'Bridge ops action simulated.', $response);
                return rest_ensure_response($response);
            }

            if ($desired_state === 'deactivate' && $active) {
                deactivate_plugins($plugin_file, true);
                $response['executed'] = true;
            } elseif ($desired_state === 'activate' && !$active) {
                $activation = activate_plugin($plugin_file, '', false, true);
                if (is_wp_error($activation)) {
                    $response['ok'] = false;
                    $response['message'] = $activation->get_error_message();
                    $this->log_system_event('bridge', 'error', 'Bridge ops action failed.', $response);
                    return rest_ensure_response($response);
                }
                $response['executed'] = true;
            }

            $response['current_state'] = is_plugin_active($plugin_file) ? 'active' : 'inactive';
            $response['message'] = 'Plugin toggle processed.';
            $this->log_system_event('bridge', 'info', 'Bridge ops action processed.', $response);
            return rest_ensure_response($response);
        }

        $response = [
            'ok' => true,
            'action_type' => $action_type,
            'run_mode' => $run_mode,
            'executed' => false,
            'simulated' => 1,
            'message' => 'Action acknowledged but not implemented for live execution yet.',
        ];
        $this->log_system_event('bridge', 'info', 'Bridge ops action acknowledged.', $response);
        return rest_ensure_response($response);
    }

    public function get_smtp_health_status()
    {
        $status = get_option('lc_smtp_health_status', []);
        if (!is_array($status)) {
            $status = [];
        }
        return wp_parse_args($status, [
            'connected' => false,
            'status_color' => 'red',
            'message' => 'Not checked yet.',
            'checked_at' => '',
        ]);
    }

    public function get_smtp_test_confirmation()
    {
        $state = get_option('lc_smtp_test_confirmed', []);
        if (!is_array($state)) {
            $state = [];
        }
        return wp_parse_args($state, [
            'confirmed' => 0,
            'pending' => 0,
            'to_email' => '',
            'sent_at' => '',
            'confirmed_at' => '',
            'last_result' => '',
            'last_error_code' => '',
        ]);
    }

    public function set_smtp_test_confirmation_state($state)
    {
        $current = $this->get_smtp_test_confirmation();
        $next = wp_parse_args((array) $state, $current);
        $next['confirmed'] = !empty($next['confirmed']) ? 1 : 0;
        $next['pending'] = !empty($next['pending']) ? 1 : 0;
        $next['to_email'] = sanitize_email((string) ($next['to_email'] ?? ''));
        $next['sent_at'] = sanitize_text_field((string) ($next['sent_at'] ?? ''));
        $next['confirmed_at'] = sanitize_text_field((string) ($next['confirmed_at'] ?? ''));
        $next['last_result'] = sanitize_text_field((string) ($next['last_result'] ?? ''));
        $next['last_error_code'] = sanitize_text_field((string) ($next['last_error_code'] ?? ''));
        update_option('lc_smtp_test_confirmed', $next);
        return $next;
    }

    public function get_email_templates_snapshot()
    {
        $settings = $this->get_settings();
        $definitions = $this->email_template_definitions();
        $templates = [];
        foreach ($definitions as $template_key => $definition) {
            $subject_key = 'email_template_' . $template_key . '_subject';
            $body_key = 'email_template_' . $template_key . '_body';
            $templates[$template_key] = [
                'label' => (string) ($definition['label'] ?? $template_key),
                'subject' => (string) ($settings[$subject_key] ?? ''),
                'body' => (string) ($settings[$body_key] ?? ''),
                'placeholders' => isset($definition['placeholders']) && is_array($definition['placeholders']) ? array_values($definition['placeholders']) : [],
            ];
        }
        return [
            'templates' => $templates,
            'placeholders' => $this->email_template_placeholders(),
        ];
    }

    public function get_email_template_preview($template_key, $vars = [], $subject_override = null, $body_override = null)
    {
        $template_key = sanitize_key((string) $template_key);
        $definitions = $this->email_template_definitions();
        if ($template_key === '' || !isset($definitions[$template_key])) {
            return [
                'ok' => false,
                'error' => 'invalid_template_key',
                'message' => 'Template key is invalid.',
            ];
        }

        $settings = $this->get_settings();
        $subject_key = 'email_template_' . $template_key . '_subject';
        $body_key = 'email_template_' . $template_key . '_body';
        $subject = $subject_override !== null ? (string) $subject_override : (string) ($settings[$subject_key] ?? '');
        $body = $body_override !== null ? (string) $body_override : (string) ($settings[$body_key] ?? '');
        $merged_vars = $this->email_template_preview_vars($vars);

        return [
            'ok' => true,
            'template_key' => $template_key,
            'label' => (string) ($definitions[$template_key]['label'] ?? $template_key),
            'subject' => $this->render_email_template($subject, $merged_vars),
            'body_text' => $this->render_email_template($body, $merged_vars),
            'body_html' => nl2br($this->render_email_template($body, $merged_vars)),
            'vars' => $merged_vars,
        ];
    }

    public function save_email_templates_snapshot($templates)
    {
        $current = $this->get_settings();
        $updates = [];
        $definitions = $this->email_template_definitions();
        foreach ($definitions as $template_key => $definition) {
            if (!isset($templates[$template_key]) || !is_array($templates[$template_key])) {
                continue;
            }
            $template = $templates[$template_key];
            $subject_key = 'email_template_' . $template_key . '_subject';
            $body_key = 'email_template_' . $template_key . '_body';
            if (array_key_exists('subject', $template)) {
                $updates[$subject_key] = (string) ($template['subject'] ?? '');
            }
            if (array_key_exists('body', $template)) {
                $updates[$body_key] = (string) ($template['body'] ?? '');
            }
        }
        $sanitized = $this->sanitize_settings(array_merge($current, $updates));
        update_option('lc_settings', $sanitized);
        return $this->get_email_templates_snapshot();
    }

    public function get_email_template_test_log($limit = 25)
    {
        $rows = get_option('lc_email_template_test_log', []);
        if (!is_array($rows)) {
            $rows = [];
        }
        $rows = array_values(array_filter($rows, static function ($row) {
            return is_array($row);
        }));
        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }
        return $rows;
    }

    public function send_templated_test_email($to_email, $template_key, $vars = [])
    {
        $to = sanitize_email((string) $to_email);
        if ($to === '') {
            return [
                'success' => false,
                'error_code' => 'invalid_email',
                'message' => 'Recipient email is invalid.',
            ];
        }
        $preview = $this->get_email_template_preview($template_key, $vars);
        if (empty($preview['ok'])) {
            return [
                'success' => false,
                'error_code' => (string) ($preview['error'] ?? 'invalid_template_key'),
                'message' => (string) ($preview['message'] ?? 'Template preview failed.'),
            ];
        }
        $result = $this->smtp_send_custom_email($to, (string) ($preview['subject'] ?? ''), (string) ($preview['body_text'] ?? ''));
        $result['template_key'] = sanitize_key((string) $template_key);
        $result['preview'] = $preview;
        return $result;
    }

    private function append_email_template_test_log($template_key, $to_email, $result)
    {
        $rows = get_option('lc_email_template_test_log', []);
        if (!is_array($rows)) {
            $rows = [];
        }
        $preview = (isset($result['preview']) && is_array($result['preview'])) ? $result['preview'] : [];
        $entry = [
            'log_id' => 'email_tpl_test_' . gmdate('Ymd_His') . '_' . substr(wp_generate_password(8, false, false), 0, 6),
            'created_at' => current_time('mysql'),
            'template_key' => sanitize_key((string) $template_key),
            'label' => sanitize_text_field((string) ($preview['label'] ?? $template_key)),
            'to_email' => sanitize_email((string) $to_email),
            'success' => !empty($result['success']),
            'error_code' => sanitize_key((string) ($result['error_code'] ?? '')),
            'message' => sanitize_text_field((string) ($result['message'] ?? '')),
            'subject' => sanitize_text_field((string) ($preview['subject'] ?? '')),
        ];
        array_unshift($rows, $entry);
        $rows = array_slice($rows, 0, 50);
        update_option('lc_email_template_test_log', $rows);
        return $entry;
    }

    private function email_template_placeholders()
    {
        return [
            '{first_name}',
            '{full_name}',
            '{user_email}',
            '{reset_link}',
            '{revoke_link}',
            '{current_time}',
            '{company}',
            '{phone}',
            '{site_name}',
            '{site_url}',
        ];
    }

    private function email_template_definitions()
    {
        $placeholders = $this->email_template_placeholders();
        return [
            'reset' => [
                'label' => 'Password Reset',
                'placeholders' => ['{first_name}', '{reset_link}', '{site_name}'],
            ],
            'password_changed' => [
                'label' => 'Password Changed',
                'placeholders' => ['{first_name}', '{revoke_link}', '{site_name}'],
            ],
            'admin_password_changed' => [
                'label' => 'Admin Password Alert',
                'placeholders' => ['{user_email}', '{current_time}'],
            ],
            'registration_received' => [
                'label' => 'Registration Received',
                'placeholders' => ['{first_name}', '{site_name}'],
            ],
            'registration_admin' => [
                'label' => 'Registration Admin Alert',
                'placeholders' => ['{full_name}', '{user_email}', '{company}', '{phone}'],
            ],
            'registration_approved' => [
                'label' => 'Registration Approved',
                'placeholders' => ['{first_name}', '{site_name}'],
            ],
            'registration_rejected' => [
                'label' => 'Registration Rejected',
                'placeholders' => ['{first_name}', '{site_name}'],
            ],
        ];
    }

    private function email_template_preview_vars($vars = [])
    {
        $defaults = [
            'first_name' => 'Alex',
            'full_name' => 'Alex Sample',
            'user_email' => 'alex@example.com',
            'reset_link' => home_url('/reset-password/?token=demo'),
            'revoke_link' => home_url('/revoke-password/?token=demo'),
            'current_time' => current_time('mysql'),
            'company' => '5N2 Digital',
            'phone' => '+1 555-0100',
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url('/'),
        ];
        $clean = [];
        foreach ((array) $vars as $key => $value) {
            $clean[sanitize_key((string) $key)] = is_scalar($value) ? (string) $value : '';
        }
        return array_merge($defaults, $clean);
    }

    public function run_smtp_health_check_cron()
    {
        $result = $this->smtp_connection_probe();
        $this->update_smtp_health_status($result);
        if (empty($result['connected'])) {
            $this->log_system_event('settings', 'error', 'SMTP health check failed.', [
                'message' => (string) ($result['message'] ?? 'Unknown SMTP error.'),
            ]);
        }
    }

    public function smtp_connection_probe($settings_override = [])
    {
        $settings = wp_parse_args((array) $settings_override, $this->get_settings());
        if (empty($settings['smtp_enabled'])) {
            return [
                'connected' => false,
                'status_color' => 'red',
                'message' => 'SMTP is disabled.',
                'checked_at' => current_time('mysql'),
            ];
        }
        $host = trim((string) ($settings['smtp_host'] ?? ''));
        if ($host === '') {
            return [
                'connected' => false,
                'status_color' => 'red',
                'message' => 'SMTP host is missing.',
                'checked_at' => current_time('mysql'),
            ];
        }

        try {
            $mailer = $this->build_smtp_mailer($settings);
            $connected = $mailer->smtpConnect();
            if ($connected) {
                $mailer->smtpClose();
                return [
                    'connected' => true,
                    'status_color' => 'green',
                    'message' => 'SMTP connection verified.',
                    'checked_at' => current_time('mysql'),
                ];
            }
            return [
                'connected' => false,
                'status_color' => 'red',
                'message' => 'SMTP connection failed: ' . (string) $mailer->ErrorInfo,
                'checked_at' => current_time('mysql'),
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'status_color' => 'red',
                'message' => 'SMTP error: ' . sanitize_text_field($e->getMessage()),
                'checked_at' => current_time('mysql'),
            ];
        }
    }

    public function smtp_send_test_email($to_email, $settings_override = [])
    {
        $to = sanitize_email((string) $to_email);
        if ($to === '') {
            return [
                'success' => false,
                'error_code' => 'invalid_email',
                'message' => 'Recipient email is invalid.',
            ];
        }

        $settings = wp_parse_args((array) $settings_override, $this->get_settings());
        try {
            $mailer = $this->build_smtp_mailer($settings);
            $from_email = sanitize_email((string) ($settings['smtp_from_email'] ?? ''));
            if ($from_email === '') {
                $from_email = sanitize_email((string) get_option('admin_email'));
            }
            $from_name = sanitize_text_field((string) ($settings['smtp_from_name'] ?? get_bloginfo('name')));
            $mailer->setFrom($from_email, $from_name, false);
            $mailer->addAddress($to);
            $mailer->Subject = 'SMTP Test Email - 5N2 Lead Console';
            $mailer->Body = "This is a test email from 5N2 Lead Console.\nSent at: " . current_time('mysql');
            $mailer->send();
            return [
                'success' => true,
                'error_code' => '',
                'message' => 'Test email sent successfully.',
            ];
        } catch (Throwable $e) {
            $message = sanitize_text_field($e->getMessage());
            $error_code = 'send_failed';
            if (stripos($message, 'daemon') !== false || stripos($message, 'undeliver') !== false || stripos($message, 'mailbox unavailable') !== false) {
                $error_code = 'mailer_daemon';
            }
            return [
                'success' => false,
                'error_code' => $error_code,
                'message' => $message !== '' ? $message : 'SMTP send failed.',
            ];
        }
    }

    public function smtp_send_custom_email($to_email, $subject, $body_text, $settings_override = [])
    {
        $to = sanitize_email((string) $to_email);
        if ($to === '') {
            return [
                'success' => false,
                'error_code' => 'invalid_email',
                'message' => 'Recipient email is invalid.',
            ];
        }

        $settings = wp_parse_args((array) $settings_override, $this->get_settings());
        try {
            $mailer = $this->build_smtp_mailer($settings);
            $from_email = sanitize_email((string) ($settings['smtp_from_email'] ?? ''));
            if ($from_email === '') {
                $from_email = sanitize_email((string) get_option('admin_email'));
            }
            $from_name = sanitize_text_field((string) ($settings['smtp_from_name'] ?? get_bloginfo('name')));
            $mailer->setFrom($from_email, $from_name, false);
            $mailer->addAddress($to);
            $mailer->Subject = (string) $subject;
            $mailer->Body = (string) $body_text;
            $mailer->send();
            return [
                'success' => true,
                'error_code' => '',
                'message' => 'Template test email sent successfully.',
            ];
        } catch (Throwable $e) {
            $message = sanitize_text_field($e->getMessage());
            $error_code = 'send_failed';
            if (stripos($message, 'daemon') !== false || stripos($message, 'undeliver') !== false || stripos($message, 'mailbox unavailable') !== false) {
                $error_code = 'mailer_daemon';
            }
            return [
                'success' => false,
                'error_code' => $error_code,
                'message' => $message !== '' ? $message : 'SMTP send failed.',
            ];
        }
    }

    public function update_smtp_health_status($status)
    {
        $status = wp_parse_args((array) $status, [
            'connected' => false,
            'status_color' => 'red',
            'message' => 'Unknown SMTP state.',
            'checked_at' => current_time('mysql'),
        ]);
        $status['connected'] = !empty($status['connected']);
        $status['status_color'] = $status['connected'] ? 'green' : 'red';
        $status['message'] = sanitize_text_field((string) $status['message']);
        $status['checked_at'] = sanitize_text_field((string) $status['checked_at']);
        update_option('lc_smtp_health_status', $status);
        return $status;
    }

    private function build_smtp_mailer($settings)
    {
        if (!class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        }

        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = (string) ($settings['smtp_host'] ?? '');
        $mailer->Port = max(1, (int) ($settings['smtp_port'] ?? 587));
        $mailer->SMTPAuth = !empty($settings['smtp_auth']);
        $mailer->Username = (string) ($settings['smtp_username'] ?? '');
        $mailer->Password = (string) ($settings['smtp_password'] ?? '');
        $mailer->Timeout = 12;
        $mailer->SMTPDebug = 0;
        $encryption = (string) ($settings['smtp_encryption'] ?? 'tls');
        if ($encryption === 'ssl') {
            $mailer->SMTPSecure = 'ssl';
        } elseif ($encryption === 'none') {
            $mailer->SMTPSecure = '';
            $mailer->SMTPAutoTLS = false;
        } else {
            $mailer->SMTPSecure = 'tls';
        }
        return $mailer;
    }

    public function configure_smtp_mailer($phpmailer)
    {
        $settings = $this->get_settings();
        if (empty($settings['smtp_enabled']) || empty($settings['smtp_host'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = (string) $settings['smtp_host'];
        $phpmailer->Port = (int) $settings['smtp_port'];
        $phpmailer->SMTPAuth = !empty($settings['smtp_auth']);
        $phpmailer->Username = (string) ($settings['smtp_username'] ?? '');
        $phpmailer->Password = (string) ($settings['smtp_password'] ?? '');

        $encryption = (string) ($settings['smtp_encryption'] ?? 'tls');
        if ($encryption === 'none') {
            $phpmailer->SMTPSecure = '';
            $phpmailer->SMTPAutoTLS = false;
        } elseif ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        } else {
            $phpmailer->SMTPSecure = 'tls';
        }

        $from_email = sanitize_email((string) ($settings['smtp_from_email'] ?? ''));
        $from_name = sanitize_text_field((string) ($settings['smtp_from_name'] ?? get_bloginfo('name')));
        if ($from_email !== '') {
            $phpmailer->setFrom($from_email, $from_name, false);
        }
    }

    public function send_templated_email($to, $template_key, $vars = [], $fallback_subject = '', $fallback_body = '')
    {
        $to = sanitize_email((string) $to);
        if ($to === '') {
            return false;
        }

        $settings = $this->get_settings();
        $subject_key = 'email_template_' . $template_key . '_subject';
        $body_key = 'email_template_' . $template_key . '_body';
        $subject = (string) ($settings[$subject_key] ?? '');
        $body = (string) ($settings[$body_key] ?? '');
        if ($subject === '') {
            $subject = $fallback_subject;
        }
        if ($body === '') {
            $body = $fallback_body;
        }

        $subject = $this->render_email_template($subject, $vars);
        $body = nl2br($this->render_email_template($body, $vars));
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        return wp_mail($to, $subject, $body, $headers);
    }

    private function render_email_template($template, $vars = [])
    {
        $base = [
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url('/'),
            'current_time' => current_time('mysql'),
        ];
        $all = array_merge($base, is_array($vars) ? $vars : []);
        $replace = [];
        foreach ($all as $k => $v) {
            $replace['{' . sanitize_key((string) $k) . '}'] = (string) $v;
        }
        return strtr((string) $template, $replace);
    }

    private function render_wrap_start($title)
    {
        echo '<div class="wrap lc-wrap">';
        echo '<h1><span class="lc-brand">5N2 Digital Lead Console Core</span> - ' . esc_html($title) . '</h1>';
    }

    private function render_wrap_end()
    {
        echo '</div>';
    }

    public function handle_add_lead()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_add_lead');

        global $wpdb;

        $table = $this->db_table('lc_leads');

        $website = esc_url_raw($_POST['website'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if ($this->is_suppressed($email, $phone, $website, $_POST['business_name'] ?? '')) {
            $this->log_system_event('leads', 'warning', 'Lead blocked by suppression rule.', [
                'business_name' => sanitize_text_field($_POST['business_name'] ?? ''),
            ]);
            wp_safe_redirect(admin_url('admin.php?page=lc_leads&message=suppressed'));
            exit;
        }

        $score = $this->compute_score($website, $phone, $email, (int) ($_POST['review_count'] ?? 0), (float) ($_POST['rating'] ?? 0));
        $lead_type = $this->compute_lead_type($website, $score);

        $wpdb->insert($table, [
            'business_name' => sanitize_text_field($_POST['business_name'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'website' => $website,
            'phone' => $phone,
            'email' => $email,
            'email_confidence' => sanitize_text_field($_POST['email_confidence'] ?? ''),
            'review_count' => absint($_POST['review_count'] ?? 0),
            'rating' => (float) ($_POST['rating'] ?? 0),
            'status' => $this->normalize_status($_POST['status'] ?? 'New'),
            'score' => $score,
            'lead_type' => $lead_type,
            'source_url' => esc_url_raw($_POST['source_url'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        ]);

        $this->log_system_event('leads', 'info', 'Lead added from admin.', [
            'business_name' => sanitize_text_field($_POST['business_name'] ?? ''),
        ]);

        wp_safe_redirect(admin_url('admin.php?page=lc_leads&message=lead_added'));
        exit;
    }

    public function handle_update_lead_status()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_update_lead_status');

        global $wpdb;
        $wpdb->update(
            $this->db_table('lc_leads'),
            ['status' => $this->normalize_status($_POST['status'] ?? 'New')],
            ['id' => absint($_POST['lead_id'] ?? 0)]
        );

        wp_safe_redirect(admin_url('admin.php?page=lc_leads&message=status_updated'));
        exit;
    }

    public function handle_import_csv()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_import_csv');

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_safe_redirect(admin_url('admin.php?page=lc_leads&message=missing_file'));
            exit;
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$file) {
            wp_safe_redirect(admin_url('admin.php?page=lc_leads&message=import_error'));
            exit;
        }

        $headers = fgetcsv($file);
        if (!$headers) {
            fclose($file);
            wp_safe_redirect(admin_url('admin.php?page=lc_leads&message=import_error'));
            exit;
        }

        $expected = [
            'business_name', 'city', 'category', 'address', 'website', 'phone', 'email', 'email_confidence',
            'review_count', 'rating', 'status', 'notes', 'source_url',
        ];

        $normalized = array_map('trim', $headers);
        if ($normalized !== $expected) {
            fclose($file);
            wp_safe_redirect(admin_url('admin.php?page=lc_leads&message=invalid_headers'));
            exit;
        }

        global $wpdb;
        $table = $this->db_table('lc_leads');
        $count = 0;

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($expected, $row);
            if (!$data || empty($data['business_name'])) {
                continue;
            }

            if ($this->is_suppressed($data['email'], $data['phone'], $data['website'], $data['business_name'])) {
                continue;
            }

            if ($this->find_duplicate_lead_id($data['phone'], $data['website'])) {
                continue;
            }

            $score = $this->compute_score($data['website'], $data['phone'], $data['email'], (int) $data['review_count'], (float) $data['rating']);
            $lead_type = $this->compute_lead_type($data['website'], $score);

            $wpdb->insert($table, [
                'business_name' => sanitize_text_field($data['business_name']),
                'city' => sanitize_text_field($data['city']),
                'category' => sanitize_text_field($data['category']),
                'address' => sanitize_text_field($data['address']),
                'website' => esc_url_raw($data['website']),
                'phone' => sanitize_text_field($data['phone']),
                'email' => sanitize_email($data['email']),
                'email_confidence' => sanitize_text_field($data['email_confidence']),
                'review_count' => absint($data['review_count']),
                'rating' => (float) $data['rating'],
                'status' => $this->normalize_status($data['status']),
                'score' => $score,
                'lead_type' => $lead_type,
                'source_url' => esc_url_raw($data['source_url']),
                'notes' => sanitize_textarea_field($data['notes']),
            ]);

            $count++;
        }

        fclose($file);

        wp_safe_redirect(admin_url('admin.php?page=lc_leads&message=imported&count=' . $count));
        exit;
    }

    public function handle_queue_run()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_queue_run');

        $settings = $this->get_settings();
        $requested_max = absint($_POST['max_places'] ?? 0);

        global $wpdb;
        $wpdb->insert($this->db_table('lc_runs'), [
            'query_text' => sanitize_text_field($_POST['query_text'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'state' => sanitize_text_field($_POST['state'] ?? ''),
            'country' => sanitize_text_field($_POST['country'] ?? ''),
            'radius_miles' => absint($_POST['radius_miles'] ?? 0),
            'niche' => sanitize_text_field($_POST['niche'] ?? ''),
            'services' => sanitize_textarea_field($_POST['services'] ?? ''),
            'website_focus' => $this->normalize_website_focus($_POST['website_focus'] ?? 'any'),
            'min_rating' => max(0, min(5, (float) ($_POST['min_rating'] ?? 0))),
            'min_reviews' => absint($_POST['min_reviews'] ?? 0),
            'max_places' => min(max(1, $requested_max ?: (int) $settings['max_places_per_run']), (int) $settings['max_places_per_run']),
            'save_mode' => 'direct',
            'review_status' => 'na',
            'status' => 'queued',
        ]);

        $this->log_system_event('runs', 'info', 'Run queued from admin.', [
            'query_text' => sanitize_text_field($_POST['query_text'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'state' => sanitize_text_field($_POST['state'] ?? ''),
            'country' => sanitize_text_field($_POST['country'] ?? ''),
            'radius_miles' => absint($_POST['radius_miles'] ?? 0),
            'niche' => sanitize_text_field($_POST['niche'] ?? ''),
            'website_focus' => $this->normalize_website_focus($_POST['website_focus'] ?? 'any'),
        ]);

        wp_safe_redirect(admin_url('admin.php?page=lc_runs&message=queued'));
        exit;
    }

    public function handle_add_suppression()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_add_suppression');

        global $wpdb;
        $wpdb->insert($this->db_table('lc_suppression'), [
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'value' => sanitize_text_field($_POST['value'] ?? ''),
            'reason' => sanitize_text_field($_POST['reason'] ?? ''),
        ]);

        $this->log_system_event('suppression', 'info', 'Suppression entry added.', [
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'value' => sanitize_text_field($_POST['value'] ?? ''),
        ]);
        $this->redirect_after_action('added', 'lc_suppression');
    }

    public function handle_export_ready()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_export_ready');

        global $wpdb;

        $rows = $wpdb->get_results("SELECT business_name, city, category, address, website, phone, email, email_confidence, review_count, rating, status, notes, source_url FROM {$this->db_table('lc_leads')} WHERE status IN ('Ready','Verified') ORDER BY id DESC", ARRAY_A);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=lead-console-export-' . gmdate('Ymd-His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['business_name', 'city', 'category', 'address', 'website', 'phone', 'email', 'email_confidence', 'review_count', 'rating', 'status', 'notes', 'source_url']);

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }

    public function process_run_queue()
    {
        global $wpdb;
        $runs_table = $this->db_table('lc_runs');
        $logs_table = $this->db_table('lc_run_logs');

        $run = $wpdb->get_row("SELECT * FROM {$runs_table} WHERE status = 'queued' ORDER BY id ASC LIMIT 1");
        if (!$run) {
            return;
        }

        $this->log_system_event('runs', 'info', 'Run processing started.', [
            'run_id' => (int) $run->id,
            'query_text' => (string) $run->query_text,
            'city' => (string) $run->city,
            'state' => (string) ($run->state ?? ''),
            'country' => (string) ($run->country ?? ''),
            'radius_miles' => (int) ($run->radius_miles ?? 0),
            'niche' => (string) ($run->niche ?? ''),
        ]);

        $wpdb->update($runs_table, [
            'status' => 'processing',
            'started_at' => current_time('mysql'),
        ], ['id' => $run->id]);

        $settings = $this->get_settings();
        $mode = !empty($settings['enable_live_api_calls']) ? 'live' : 'safe';

        $wpdb->insert($logs_table, [
            'run_id' => $run->id,
            'level' => 'info',
            'message' => 'Run started in ' . $mode . ' mode. Query: ' . $this->run_search_text($run) . ' | Location: ' . $this->run_location_text($run),
        ]);

        $created = 0;
        $used_api = false;
        $can_use_api = !empty($settings['enable_live_api_calls']) && !empty($settings['google_places_api_key']);
        $mode_setting = $settings['discovery_mode'] ?? 'hybrid';

        if ($can_use_api && $mode_setting !== 'directory_only') {
            $used_api = true;
            $created += $this->discover_with_google_places_api($run, $settings, $logs_table);
        } elseif (empty($settings['google_places_api_key'])) {
            $wpdb->insert($logs_table, [
                'run_id' => $run->id,
                'level' => 'warning',
                'message' => 'Google Places API key is empty. Capture it in Lead Console > Settings > Google Places API key.',
            ]);
        }

        if (($mode_setting === 'directory_only') || ($mode_setting === 'hybrid' && (!$can_use_api || $created === 0))) {
            $created += $this->discover_with_directory_fallback($run, $settings, $logs_table);
        }

        $social_updated = $this->enrich_leads_with_social_urls($run, $settings, $logs_table);

        $summary = $used_api ? 'Google API + fallback processing complete.' : 'Fallback directory processing complete.';
        $save_mode = sanitize_text_field((string) ($run->save_mode ?? 'direct'));
        $review_status = ($save_mode === 'draft' && $created > 0) ? 'pending' : 'na';
        $wpdb->insert($logs_table, [
            'run_id' => $run->id,
            'level' => 'info',
            'message' => sprintf('%s Leads created: %d. Social profiles updated: %d.', $summary, $created, $social_updated),
        ]);

        $wpdb->update($runs_table, [
            'status' => 'completed',
            'finished_at' => current_time('mysql'),
            'captured_leads' => (int) $created,
            'review_status' => $review_status,
        ], ['id' => $run->id]);

        $this->log_system_event('runs', 'info', 'Run processing completed.', [
            'run_id' => (int) $run->id,
            'leads_created' => (int) $created,
            'social_updated' => (int) $social_updated,
            'save_mode' => $save_mode,
            'review_status' => $review_status,
        ]);
    }

    private function discover_with_google_places_api($run, $settings, $logs_table)
    {
        global $wpdb;

        $api_key = $settings['google_places_api_key'] ?? '';
        $max_places = max(1, (int) $run->max_places);
        $query = trim($this->run_search_text($run) . ' ' . $this->run_location_text($run));
        $min_rating = max(0, min(5, (float) ($run->min_rating ?? 0)));
        $min_reviews = max(0, (int) ($run->min_reviews ?? 0));
        $website_focus = $this->normalize_website_focus($run->website_focus ?? 'any');

        $url = add_query_arg([
            'query' => $query,
            'key' => $api_key,
        ], 'https://maps.googleapis.com/maps/api/place/textsearch/json');

        $response = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($response)) {
            $wpdb->insert($logs_table, [
                'run_id' => $run->id,
                'level' => 'error',
                'message' => 'Google Places API request failed: ' . $response->get_error_message(),
            ]);
            return 0;
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if (empty($payload['results']) || !is_array($payload['results'])) {
            $wpdb->insert($logs_table, [
                'run_id' => $run->id,
                'level' => 'warning',
                'message' => 'Google Places API returned no results.',
            ]);
            return 0;
        }

        $added = 0;
        foreach ($payload['results'] as $item) {
            if ($added >= $max_places) {
                break;
            }

            $website = '';
            $phone = '';
            $email = '';
            $name = sanitize_text_field($item['name'] ?? '');
            $address = sanitize_text_field($item['formatted_address'] ?? '');
            $rating = (float) ($item['rating'] ?? 0);
            $review_count = absint($item['user_ratings_total'] ?? 0);

            if (!$name) {
                continue;
            }
            if ($rating < $min_rating || $review_count < $min_reviews) {
                continue;
            }

            if (!empty($item['place_id'])) {
                $details = $this->fetch_google_place_details((string) $item['place_id'], $api_key);
                $website = $details['website'];
                $phone = $details['phone'];
            }

            if ($website_focus === 'no_website' && $website !== '') {
                continue;
            }
            if ($website_focus === 'has_website' && $website === '') {
                continue;
            }

            $inserted = $this->insert_discovered_lead([
                'run_id' => (int) $run->id,
                'save_mode' => (string) ($run->save_mode ?? 'direct'),
                'business_name' => $name,
                'city' => sanitize_text_field($run->city),
                'category' => sanitize_text_field((string) ($run->niche ?: $run->query_text)),
                'address' => $address,
                'website' => $website,
                'phone' => $phone,
                'email' => $email,
                'review_count' => $review_count,
                'rating' => $rating,
                'source_url' => 'https://maps.google.com/?q=' . rawurlencode($name . ' ' . $this->run_location_text($run)),
                'notes' => 'Imported from Google Places API',
                'run_focus' => $website_focus,
            ]);

            if ($inserted) {
                $added++;
            }
        }

        $wpdb->insert($logs_table, [
            'run_id' => $run->id,
            'level' => 'info',
            'message' => sprintf('Google Places API discovery added %d leads.', $added),
        ]);

        return $added;
    }

    private function discover_with_directory_fallback($run, $settings, $logs_table)
    {
        global $wpdb;

        $sources = $this->get_directory_sources($settings);
        $max_places = max(1, (int) $run->max_places);
        $added = 0;
        $location = $this->run_location_text($run);
        $min_rating = max(0, min(5, (float) ($run->min_rating ?? 0)));
        $min_reviews = max(0, (int) ($run->min_reviews ?? 0));
        $website_focus = $this->normalize_website_focus($run->website_focus ?? 'any');

        foreach ($sources as $source) {
            if ($added >= $max_places) {
                break;
            }

            $search_url = str_replace(
                ['{query}', '{city}'],
                [rawurlencode($this->run_search_text($run)), rawurlencode($location)],
                $source['search_url']
            );

            $response = wp_remote_get($search_url, ['timeout' => 15, 'user-agent' => 'LeadConsoleBot/0.1']);
            if (is_wp_error($response)) {
                $wpdb->insert($logs_table, [
                    'run_id' => $run->id,
                    'level' => 'warning',
                    'message' => 'Directory request failed for ' . $source['name'] . ': ' . $response->get_error_message(),
                ]);
                continue;
            }

            $html = (string) wp_remote_retrieve_body($response);
            if (!$html) {
                continue;
            }

            preg_match_all('/<a[^>]*>([^<]{3,120})<\/a>/i', $html, $matches);
            if (empty($matches[1])) {
                continue;
            }

            $added_from_source = 0;
            foreach ($matches[1] as $candidate) {
                if ($added >= $max_places || $added_from_source >= 3) {
                    break;
                }

                $name = trim(wp_strip_all_tags($candidate));
                if (strlen($name) < 4 || stripos($name, 'cookie') !== false) {
                    continue;
                }

                $inserted = $this->insert_discovered_lead([
                    'run_id' => (int) $run->id,
                    'save_mode' => (string) ($run->save_mode ?? 'direct'),
                    'business_name' => $name,
                    'city' => sanitize_text_field($run->city),
                    'category' => sanitize_text_field((string) ($run->niche ?: $run->query_text)),
                    'address' => '',
                    'website' => '',
                    'phone' => '',
                    'email' => '',
                    'review_count' => 0,
                    'rating' => 0,
                    'source_url' => esc_url_raw($search_url),
                    'notes' => sprintf('Imported via directory fallback: %s (quality:%d, requested filters rating>=%.1f, reviews>=%d)', $source['name'], (int) $source['quality_score'], $min_rating, $min_reviews),
                    'run_focus' => $website_focus,
                ]);

                if ($inserted) {
                    $added++;
                    $added_from_source++;
                }
            }

            $wpdb->insert($logs_table, [
                'run_id' => $run->id,
                'level' => 'info',
                'message' => sprintf('Directory fallback source %s added %d leads.', $source['name'], $added_from_source),
            ]);
        }

        if ($added === 0) {
            $wpdb->insert($logs_table, [
                'run_id' => $run->id,
                'level' => 'warning',
                'message' => 'Directory fallback added 0 leads. Consider setting Google Places API key in Settings.',
            ]);
        }

        return $added;
    }

    private function insert_discovered_lead($data)
    {
        global $wpdb;

        $website = esc_url_raw($data['website'] ?? '');
        $phone = sanitize_text_field($data['phone'] ?? '');
        $email = sanitize_email($data['email'] ?? '');
        $name = sanitize_text_field($data['business_name'] ?? '');

        if (!$name) {
            return false;
        }

        if ($this->is_suppressed($email, $phone, $website, $name)) {
            return false;
        }

        if ($this->find_duplicate_lead_id($phone, $website)) {
            return false;
        }

        $score = $this->compute_score($website, $phone, $email, (int) ($data['review_count'] ?? 0), (float) ($data['rating'] ?? 0));
        $lead_type = $this->compute_lead_type($website, $score);
        $category = sanitize_text_field($data['category'] ?? '');
        $qualification = $this->analyze_website_presence($website);
        $seo_audit = $website !== '' ? $this->audit_website_basic_seo($website) : ['checked' => false, 'issues' => []];
        $opportunity_notes = $this->build_service_opportunity_notes($category, $qualification, $seo_audit);
        $base_notes = sanitize_textarea_field($data['notes'] ?? '');
        $notes = trim($base_notes . "\n" . $opportunity_notes);

        $save_mode = sanitize_text_field((string) ($data['save_mode'] ?? 'direct'));
        $run_id = absint($data['run_id'] ?? 0);
        if ($save_mode === 'draft' && $run_id > 0) {
            $inserted = $wpdb->insert($this->db_table('lc_run_drafts'), [
                'run_id' => $run_id,
                'business_name' => $name,
                'city' => sanitize_text_field($data['city'] ?? ''),
                'category' => $category,
                'address' => sanitize_text_field($data['address'] ?? ''),
                'website' => $website,
                'phone' => $phone,
                'email' => $email,
                'review_count' => absint($data['review_count'] ?? 0),
                'rating' => (float) ($data['rating'] ?? 0),
                'status' => 'New',
                'score' => $score,
                'lead_type' => $lead_type,
                'source_url' => esc_url_raw($data['source_url'] ?? ''),
                'notes' => $notes,
            ]);
        } else {
            $inserted = $wpdb->insert($this->db_table('lc_leads'), [
                'business_name' => $name,
                'city' => sanitize_text_field($data['city'] ?? ''),
                'category' => $category,
                'address' => sanitize_text_field($data['address'] ?? ''),
                'website' => $website,
                'phone' => $phone,
                'email' => $email,
                'email_confidence' => '',
                'review_count' => absint($data['review_count'] ?? 0),
                'rating' => (float) ($data['rating'] ?? 0),
                'status' => 'New',
                'score' => $score,
                'lead_type' => $lead_type,
                'source_url' => esc_url_raw($data['source_url'] ?? ''),
                'notes' => $notes,
            ]);
        }

        return !empty($inserted);
    }

    private function get_directory_sources($settings)
    {
        $custom = trim((string) ($settings['directory_sources'] ?? ''));
        $sources = [];

        if ($custom !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $custom);
            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) < 3) {
                    continue;
                }

                $sources[] = [
                    'name' => sanitize_text_field($parts[0]),
                    'search_url' => esc_url_raw($parts[1]),
                    'quality_score' => absint($parts[2]),
                ];
            }
        }

        if (!empty($sources)) {
            return $sources;
        }

        return [
            ['name' => 'Google Maps', 'search_url' => 'https://www.google.com/maps/search/{query}+{city}', 'quality_score' => 95],
            ['name' => 'Yelp', 'search_url' => 'https://www.yelp.com/search?find_desc={query}&find_loc={city}', 'quality_score' => 90],
            ['name' => 'Yellow Pages', 'search_url' => 'https://www.yellowpages.com/search?search_terms={query}&geo_location_terms={city}', 'quality_score' => 84],
            ['name' => 'Better Business Bureau', 'search_url' => 'https://www.bbb.org/search?find_text={query}&find_loc={city}', 'quality_score' => 86],
            ['name' => 'Chamber of Commerce', 'search_url' => 'https://www.chamberofcommerce.com/search?what={query}&where={city}', 'quality_score' => 79],
            ['name' => 'Manta', 'search_url' => 'https://www.manta.com/search?search={query}+{city}', 'quality_score' => 72],
        ];
    }

    private function enrich_leads_with_social_urls($run, $settings, $logs_table)
    {
        global $wpdb;

        $mode = $settings['social_discovery_mode'] ?? 'off';
        if ($mode === 'off') {
            return 0;
        }

        if ($mode === 'official_api_enabled') {
            $wpdb->insert($logs_table, [
                'run_id' => $run->id,
                'level' => 'warning',
                'message' => 'Official social APIs mode selected but provider-specific OAuth integration is not configured yet. Falling back to URL discovery only.',
            ]);
        }

        $cse_key = trim((string) ($settings['google_cse_api_key'] ?? ''));
        $cse_cx = trim((string) ($settings['google_cse_cx'] ?? ''));
        if ($cse_key === '' || $cse_cx === '') {
            $wpdb->insert($logs_table, [
                'run_id' => $run->id,
                'level' => 'warning',
                'message' => 'Social discovery is enabled but Google Programmable Search API key/cx is missing. Set it in Lead Console > Settings.',
            ]);
            return 0;
        }

        $table = $this->db_table('lc_leads');
        $max = max(1, (int) $run->max_places);
        $leads = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, business_name, city, category, website, notes, linkedin_url, facebook_url, instagram_url, x_url, youtube_url
                 FROM {$table}
                 WHERE city = %s
                 ORDER BY id DESC
                 LIMIT %d",
                $run->city,
                $max
            )
        );

        $updated = 0;
        foreach ($leads as $lead) {
            $social = $this->discover_social_profiles_for_lead($lead, $cse_key, $cse_cx);
            $update_data = [];
            if (!empty($social['data'])) {
                $update_data = $social['data'];
                $update_data['social_confidence'] = $social['confidence'];
                $update_data['social_source'] = 'google_cse';
            }

            $website = trim((string) ($lead->website ?? ''));
            if ($website === '') {
                $website_candidate = $this->find_candidate_website_for_lead($lead, $cse_key, $cse_cx);
                if ($website_candidate !== '') {
                    $update_data['website'] = esc_url_raw($website_candidate);
                    $website_info = $this->analyze_website_presence($website_candidate);
                    $seo = $this->audit_website_basic_seo($website_candidate);
                    $extra_note = "Cross-match: Candidate website found: {$website_candidate}\n";
                    $extra_note .= $this->build_service_opportunity_notes((string) ($lead->category ?? ''), $website_info, $seo);
                    $update_data['notes'] = $this->append_note((string) ($lead->notes ?? ''), $extra_note);
                } else {
                    $extra_note = 'Cross-match: No official website found across search/social signals. Treat as no-website lead.';
                    $update_data['notes'] = $this->append_note((string) ($lead->notes ?? ''), $extra_note);
                }
            } elseif (!empty($update_data)) {
                $website_info = $this->analyze_website_presence($website);
                $seo = $this->audit_website_basic_seo($website);
                $extra_note = $this->build_service_opportunity_notes((string) ($lead->category ?? ''), $website_info, $seo);
                $update_data['notes'] = $this->append_note((string) ($lead->notes ?? ''), $extra_note);
            }

            if (!empty($update_data)) {
                $did_update = $wpdb->update($table, $update_data, ['id' => (int) $lead->id]);
                if ($did_update !== false) {
                    $updated++;
                }
            }
        }

        $wpdb->insert($logs_table, [
            'run_id' => $run->id,
            'level' => 'info',
            'message' => sprintf('Social URL discovery completed. Leads updated: %d.', $updated),
        ]);

        return $updated;
    }

    private function discover_social_profiles_for_lead($lead, $cse_key, $cse_cx)
    {
        if (trim((string) $cse_key) === '' || trim((string) $cse_cx) === '') {
            return ['data' => [], 'confidence' => 0];
        }

        $business = sanitize_text_field($lead->business_name ?? '');
        $city = sanitize_text_field($lead->city ?? '');
        if ($business === '') {
            return ['data' => [], 'confidence' => 0];
        }

        $platforms = [
            'linkedin_url' => ['site' => 'linkedin.com/company', 'domain' => 'linkedin.com'],
            'facebook_url' => ['site' => 'facebook.com', 'domain' => 'facebook.com'],
            'instagram_url' => ['site' => 'instagram.com', 'domain' => 'instagram.com'],
            'x_url' => ['site' => 'x.com', 'domain' => 'x.com'],
            'youtube_url' => ['site' => 'youtube.com', 'domain' => 'youtube.com'],
        ];

        $data = [];
        $confidence_scores = [];

        foreach ($platforms as $field => $platform) {
            if (!empty($lead->{$field})) {
                continue;
            }

            $query = sprintf('site:%s "%s" "%s"', $platform['site'], $business, $city);
            $result = $this->google_cse_search($query, $cse_key, $cse_cx);
            if (empty($result['link'])) {
                continue;
            }

            $host = strtolower((string) wp_parse_url($result['link'], PHP_URL_HOST));
            if (strpos($host, $platform['domain']) === false) {
                continue;
            }

            $data[$field] = esc_url_raw($result['link']);
            $confidence_scores[] = $this->score_social_match_confidence($result, $business, $city);
        }

        if (empty($data)) {
            return ['data' => [], 'confidence' => 0];
        }

        $confidence = (int) round(array_sum($confidence_scores) / count($confidence_scores));
        return ['data' => $data, 'confidence' => max(0, min(100, $confidence))];
    }

    private function google_cse_search($query, $key, $cx)
    {
        $url = add_query_arg([
            'key' => $key,
            'cx' => $cx,
            'q' => $query,
            'num' => 3,
        ], 'https://www.googleapis.com/customsearch/v1');

        $response = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($response)) {
            return [];
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if (empty($payload['items'][0])) {
            return [];
        }

        return [
            'link' => esc_url_raw($payload['items'][0]['link'] ?? ''),
            'title' => sanitize_text_field($payload['items'][0]['title'] ?? ''),
            'snippet' => sanitize_textarea_field($payload['items'][0]['snippet'] ?? ''),
        ];
    }

    private function find_candidate_website_for_lead($lead, $key, $cx)
    {
        $business = sanitize_text_field((string) ($lead->business_name ?? ''));
        $city = sanitize_text_field((string) ($lead->city ?? ''));
        if ($business === '') {
            return '';
        }

        $query = sprintf('"%s" "%s" official website', $business, $city);
        $url = add_query_arg([
            'key' => $key,
            'cx' => $cx,
            'q' => $query,
            'num' => 5,
        ], 'https://www.googleapis.com/customsearch/v1');

        $response = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($response)) {
            return '';
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if (empty($payload['items']) || !is_array($payload['items'])) {
            return '';
        }

        $blocked_hosts = [
            'linkedin.com',
            'facebook.com',
            'instagram.com',
            'x.com',
            'twitter.com',
            'youtube.com',
            'yelp.com',
            'yellowpages.com',
            'bbb.org',
            'chamberofcommerce.com',
            'manta.com',
            'maps.google.com',
            'google.com',
        ];

        foreach ($payload['items'] as $item) {
            $link = esc_url_raw((string) ($item['link'] ?? ''));
            if ($link === '') {
                continue;
            }
            $host = strtolower((string) wp_parse_url($link, PHP_URL_HOST));
            if ($host === '') {
                continue;
            }

            $blocked = false;
            foreach ($blocked_hosts as $blocked_host) {
                if ($host === $blocked_host || substr($host, -strlen('.' . $blocked_host)) === '.' . $blocked_host) {
                    $blocked = true;
                    break;
                }
            }
            if ($blocked) {
                continue;
            }

            return $link;
        }

        return '';
    }

    private function append_note($existing, $addition)
    {
        $existing = trim((string) $existing);
        $addition = trim((string) $addition);
        if ($addition === '') {
            return $existing;
        }
        if ($existing === '') {
            return sanitize_textarea_field($addition);
        }
        if (strpos($existing, $addition) !== false) {
            return sanitize_textarea_field($existing);
        }
        return sanitize_textarea_field($existing . "\n\n" . $addition);
    }

    private function score_social_match_confidence($result, $business, $city)
    {
        $score = 50;
        $title = strtolower((string) ($result['title'] ?? ''));
        $snippet = strtolower((string) ($result['snippet'] ?? ''));
        $business_l = strtolower($business);
        $city_l = strtolower($city);

        if ($business_l && strpos($title, $business_l) !== false) {
            $score += 25;
        }
        if ($city_l && strpos($snippet, $city_l) !== false) {
            $score += 15;
        }
        if ($business_l && strpos($snippet, $business_l) !== false) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    private function find_duplicate_lead_id($phone, $website)
    {
        global $wpdb;

        $phone = sanitize_text_field($phone);
        $domain = $this->normalize_domain($website);

        if (empty($phone) && empty($domain)) {
            return null;
        }

        $table = $this->db_table('lc_leads');

        if (!empty($phone)) {
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE phone = %s LIMIT 1", $phone));
            if ($existing) {
                return (int) $existing;
            }
        }

        if (!empty($domain)) {
            $rows = $wpdb->get_results("SELECT id, website FROM {$table}");
            foreach ($rows as $row) {
                if ($this->normalize_domain($row->website) === $domain) {
                    return (int) $row->id;
                }
            }
        }

        return null;
    }

    private function is_suppressed($email, $phone, $website, $name)
    {
        global $wpdb;
        $table = $this->db_table('lc_suppression');

        $checks = [
            'email' => sanitize_email($email),
            'phone' => sanitize_text_field($phone),
            'domain' => $this->normalize_domain($website),
            'name' => sanitize_text_field($name),
        ];

        foreach ($checks as $type => $value) {
            if (empty($value)) {
                continue;
            }

            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE type = %s AND value = %s LIMIT 1", $type, $value));
            if ($exists) {
                return true;
            }
        }

        return false;
    }

    private function normalize_domain($url)
    {
        if (empty($url)) {
            return '';
        }

        $host = wp_parse_url(trim($url), PHP_URL_HOST);
        if (!$host) {
            return '';
        }

        return preg_replace('/^www\./', '', strtolower($host));
    }

    private function compute_score($website, $phone, $email, $review_count, $rating)
    {
        $score = 0;

        if (!empty($website)) {
            $score += 35;
        }
        if (!empty($phone)) {
            $score += 25;
        }
        if (!empty($email)) {
            $score += 20;
        }
        if ($review_count > 0) {
            $score += 10;
        }
        if ($rating >= 4) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    private function compute_lead_type($website, $score)
    {
        if (empty($website)) {
            return 'E';
        }
        if ($score >= 80) {
            return 'A';
        }
        if ($score >= 60) {
            return 'B';
        }
        return 'C';
    }

    private function normalize_status($status)
    {
        $status = sanitize_text_field($status);
        return in_array($status, $this->statuses, true) ? $status : 'New';
    }

    private function normalize_access_level($access)
    {
        $access = sanitize_text_field((string) $access);
        $allowed = ['standard', 'manager', 'readonly'];
        return in_array($access, $allowed, true) ? $access : 'standard';
    }

    private function run_location_text($run)
    {
        $city = trim((string) ($run->city ?? ''));
        $state = trim((string) ($run->state ?? ''));
        $country = trim((string) ($run->country ?? ''));
        $radius = max(0, (int) ($run->radius_miles ?? 0));

        $location = $city;
        if ($state !== '') {
            $location = trim($location . ', ' . $state, ', ');
        }
        if ($country !== '') {
            $location = trim($location . ', ' . $country, ', ');
        }
        if ($location === '') {
            $location = $country;
        }
        if ($location === '') {
            $location = 'unspecified location';
        }
        if ($radius > 0) {
            $location .= ' within ' . $radius . ' miles';
        }

        return $location;
    }

    private function run_search_text($run)
    {
        $query = trim((string) ($run->query_text ?? ''));
        $niche = trim((string) ($run->niche ?? ''));
        $services = trim((string) ($run->services ?? ''));

        $parts = array_filter([$query, $niche, $services], static function ($v) {
            return trim((string) $v) !== '';
        });

        return implode(' ', $parts);
    }

    private function normalize_website_focus($focus)
    {
        $focus = sanitize_text_field((string) $focus);
        $allowed = ['any', 'no_website', 'has_website'];
        return in_array($focus, $allowed, true) ? $focus : 'any';
    }

    private function fetch_google_place_details($place_id, $api_key)
    {
        $result = ['website' => '', 'phone' => '', 'maps_url' => ''];
        $place_id = trim((string) $place_id);
        $api_key = trim((string) $api_key);
        if ($place_id === '' || $api_key === '') {
            return $result;
        }

        $url = add_query_arg([
            'place_id' => $place_id,
            'fields' => 'website,formatted_phone_number,url',
            'key' => $api_key,
        ], 'https://maps.googleapis.com/maps/api/place/details/json');

        $response = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($response)) {
            return $result;
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        $details = $payload['result'] ?? [];
        $result['website'] = esc_url_raw((string) ($details['website'] ?? ''));
        $result['phone'] = sanitize_text_field((string) ($details['formatted_phone_number'] ?? ''));
        $result['maps_url'] = esc_url_raw((string) ($details['url'] ?? ''));
        return $result;
    }

    private function analyze_website_presence($website)
    {
        $website = esc_url_raw((string) $website);
        if ($website === '') {
            return [
                'has_website' => false,
                'is_subdomain_service' => false,
                'host' => '',
                'classification' => 'no_website_listed',
            ];
        }

        $host = strtolower((string) wp_parse_url($website, PHP_URL_HOST));
        $service_domains = [
            'sites.google.com',
            'wixsite.com',
            'wordpress.com',
            'weebly.com',
            'squarespace.com',
            'webnode.com',
            'myshopify.com',
            'godaddysites.com',
            'yolasite.com',
            'strikingly.com',
            'jimdosite.com',
        ];
        $is_subdomain_service = false;
        foreach ($service_domains as $domain) {
            if ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain) {
                $is_subdomain_service = true;
                break;
            }
        }

        return [
            'has_website' => true,
            'is_subdomain_service' => $is_subdomain_service,
            'host' => $host,
            'classification' => $is_subdomain_service ? 'service_subdomain' : 'custom_domain',
        ];
    }

    private function audit_website_basic_seo($website)
    {
        $result = [
            'checked' => false,
            'issues' => [],
            'title' => '',
            'meta_description' => '',
        ];

        $response = wp_remote_get($website, ['timeout' => 15, 'redirection' => 4]);
        if (is_wp_error($response)) {
            $result['issues'][] = 'Website is unreachable during SEO check.';
            return $result;
        }

        $html = (string) wp_remote_retrieve_body($response);
        if ($html === '') {
            $result['issues'][] = 'Website returned empty HTML.';
            return $result;
        }

        $result['checked'] = true;
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
            $result['title'] = trim(wp_strip_all_tags((string) $m[1]));
        } else {
            $result['issues'][] = 'Missing meta title.';
        }

        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $result['meta_description'] = trim((string) $m[1]);
            if ($result['meta_description'] === '') {
                $result['issues'][] = 'Empty meta description.';
            }
        } else {
            $result['issues'][] = 'Missing meta description.';
        }

        if (!preg_match('/<h1[\s>]/i', $html)) {
            $result['issues'][] = 'Missing H1 tag.';
        }
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/i', $html)) {
            $result['issues'][] = 'Page appears noindex.';
        }

        return $result;
    }

    private function build_service_opportunity_notes($category, $website_info, $seo_audit)
    {
        $notes = [];
        $category_l = strtolower((string) $category);

        if (empty($website_info['has_website'])) {
            $notes[] = 'Qualification: No website listed. Possible social-only or directory-only business.';
            $notes[] = 'Opportunity: Website design/development, branding system, local SEO setup, digital marketing foundation.';
        } elseif (!empty($website_info['is_subdomain_service'])) {
            $notes[] = 'Qualification: Website appears hosted on service subdomain (' . (string) ($website_info['host'] ?? '') . ').';
            $notes[] = 'Opportunity: Migrate to branded custom domain with stronger SEO architecture.';
        } else {
            $notes[] = 'Qualification: Custom domain website detected (' . (string) ($website_info['host'] ?? '') . ').';
        }

        if (!empty($seo_audit['issues'])) {
            $notes[] = 'SEO Findings: ' . implode(' ', array_map('sanitize_text_field', $seo_audit['issues']));
            $notes[] = 'Opportunity: Technical SEO cleanup, on-page optimization, metadata improvement.';
        } elseif (!empty($website_info['has_website'])) {
            $notes[] = 'SEO Findings: Basic metadata signals look present. Deeper SEO audit recommended.';
        }

        if ($category_l !== '' && (strpos($category_l, 'marketing') !== false || strpos($category_l, 'agency') !== false || strpos($category_l, 'design') !== false)) {
            $notes[] = 'Service Angle: Position conversion-focused UX, branding consistency, and performance/SEO reporting.';
        } else {
            $notes[] = 'Service Angle: Offer branding, graphics, social media management, and full-funnel digital marketing.';
        }

        return implode("\n", $notes);
    }

    private function user_avatar_html($user_id, $size, $label)
    {
        $size = max(24, absint($size));
        $photo_id = absint(get_user_meta((int) $user_id, 'lc_profile_photo_id', true));
        if ($photo_id > 0) {
            $url = wp_get_attachment_image_url($photo_id, 'thumbnail');
            if ($url) {
                return '<img src="' . esc_url($url) . '" alt="' . esc_attr($label) . '" width="' . esc_attr((string) $size) . '" height="' . esc_attr((string) $size) . '" style="border-radius:999px;object-fit:cover;" />';
            }
        }

        $initial = strtoupper(substr(trim((string) $label), 0, 1));
        if ($initial === '') {
            $initial = 'U';
        }
        return '<span style="display:inline-flex;align-items:center;justify-content:center;width:' . esc_attr((string) $size) . 'px;height:' . esc_attr((string) $size) . 'px;border-radius:999px;background:#32415f;color:#fff;font-weight:700;">' . esc_html($initial) . '</span>';
    }

    private function message_notice()
    {
        if (empty($_GET['message'])) {
            return;
        }

        $message = sanitize_text_field(wp_unslash($_GET['message']));
        $count = absint($_GET['count'] ?? 0);

        $map = [
            'lead_added' => 'Lead added successfully.',
            'status_updated' => 'Lead status updated.',
            'queued' => 'Run queued successfully.',
            'added' => 'Suppression entry added.',
            'enriched' => 'Lead intelligence profile updated.',
            'enriched_recent' => 'Recent leads enriched successfully.',
            'user_reset_done' => 'User password reset completed.',
            'user_lock_done' => 'User locked successfully.',
            'user_unlock_done' => 'User unlocked successfully.',
            'user_profile_saved' => 'User profile updated successfully.',
            'user_created' => 'User created successfully.',
            'user_action_error' => 'User action failed.',
            'invalid_headers' => 'CSV headers do not match expected format.',
            'missing_file' => 'Please select a CSV file to import.',
            'import_error' => 'CSV import failed.',
            'suppressed' => 'Lead matched suppression rules and was blocked.',
        ];

        if ($message === 'imported') {
            $text = sprintf('CSV imported. %d leads added.', $count);
        } else {
            $text = $map[$message] ?? 'Action completed.';
        }

        echo '<div class="notice notice-success"><p>' . esc_html($text) . '</p></div>';
    }

    public function render_dashboard()
    {
        global $wpdb;

        $leads_table = $this->db_table('lc_leads');
        $runs_table = $this->db_table('lc_runs');

        $total_leads = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table}");
        $ready_leads = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE status='Ready'");
        $won_leads = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE status='Won'");
        $queued_runs = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$runs_table} WHERE status='queued'");
        $completed_runs = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$runs_table} WHERE status='completed'");

        $this->render_wrap_start('Dashboard');
        $this->message_notice();

        echo '<div class="lc-card-grid">';
        $this->metric_card('Total Leads', $total_leads);
        $this->metric_card('Ready Leads', $ready_leads);
        $this->metric_card('Won Leads', $won_leads);
        $this->metric_card('Queued Runs', $queued_runs);
        $this->metric_card('Completed Runs', $completed_runs);
        echo '</div>';

        $this->render_wrap_end();
    }

    private function metric_card($label, $value)
    {
        echo '<div class="lc-card">';
        echo '<h3>' . esc_html($label) . '</h3>';
        echo '<p><strong>' . esc_html((string) $value) . '</strong></p>';
        echo '</div>';
    }

    public function render_leads()
    {
        global $wpdb;

        $leads_table = $this->db_table('lc_leads');
        $search = sanitize_text_field($_GET['s'] ?? '');
        $status_filter = sanitize_text_field($_GET['status'] ?? '');

        $query = "SELECT * FROM {$leads_table} WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (business_name LIKE %s OR city LIKE %s OR category LIKE %s)";
            $term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($status_filter) && in_array($status_filter, $this->statuses, true)) {
            $query .= " AND status = %s";
            $params[] = $status_filter;
        }

        $query .= ' ORDER BY id DESC LIMIT 100';
        $sql = !empty($params) ? $wpdb->prepare($query, $params) : $query;

        $rows = $wpdb->get_results($sql);

        $this->render_wrap_start('Leads');
        $this->message_notice();

        echo '<div class="lc-card-grid">';
        echo '<div class="lc-card">';
        echo '<h2>Add Lead</h2>';
        echo '<form class="lc-form-grid" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lc_add_lead');
        echo '<input type="hidden" name="action" value="lc_add_lead" />';

        echo '<input type="text" name="business_name" placeholder="Business name" required />';
        echo '<input type="text" name="city" placeholder="City" />';
        echo '<input type="text" name="category" placeholder="Category" />';
        echo '<input type="text" name="address" placeholder="Address" />';
        echo '<input type="url" name="website" placeholder="Website" />';
        echo '<input type="text" name="phone" placeholder="Phone" />';
        echo '<input type="email" name="email" placeholder="Email" />';
        echo '<input type="text" name="email_confidence" placeholder="Email confidence" />';
        echo '<input type="number" name="review_count" placeholder="Review count" min="0" />';
        echo '<input type="number" step="0.1" name="rating" placeholder="Rating" min="0" max="5" />';

        echo '<select name="status">';
        foreach ($this->statuses as $status) {
            echo '<option value="' . esc_attr($status) . '">' . esc_html($status) . '</option>';
        }
        echo '</select>';

        echo '<textarea name="notes" placeholder="Notes"></textarea>';
        echo '<input type="url" name="source_url" placeholder="Source URL" />';
        submit_button('Save Lead', 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';

        echo '<div class="lc-card">';
        echo '<h2>Import CSV</h2>';
        echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lc_import_csv');
        echo '<input type="hidden" name="action" value="lc_import_csv" />';
        echo '<input type="file" name="csv_file" accept=".csv" required />';
        submit_button('Import CSV', 'secondary', 'submit', false);
        echo '</form>';
        echo '</div>';
        echo '</div>';

        echo '<h2>Lead List</h2>';
        echo '<form method="get" class="lc-filter-row">';
        echo '<input type="hidden" name="page" value="lc_leads" />';
        echo '<input type="text" name="s" placeholder="Search business/city/category" value="' . esc_attr($search) . '" />';

        echo '<select name="status">';
        echo '<option value="">All statuses</option>';
        foreach ($this->statuses as $status) {
            $selected = selected($status_filter, $status, false);
            echo '<option value="' . esc_attr($status) . '" ' . $selected . '>' . esc_html($status) . '</option>';
        }
        echo '</select>';

        submit_button('Filter', 'secondary', 'submit', false);
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>ID</th><th>Business</th><th>Contact</th><th>Score</th><th>Type</th><th>Status</th><th>Update</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $row->id) . '</td>';
            echo '<td><strong>' . esc_html($row->business_name) . '</strong><br/><small>' . esc_html($row->city) . ' | ' . esc_html($row->category) . '</small></td>';
            echo '<td>' . esc_html($row->phone ?: '-') . '<br/>' . esc_html($row->email ?: '-') . '</td>';
            echo '<td>' . esc_html((string) $row->score) . '</td>';
            echo '<td>' . esc_html($row->lead_type) . '</td>';
            echo '<td><span class="lc-status lc-status-' . esc_attr(sanitize_title($row->status)) . '">' . esc_html($row->status) . '</span></td>';

            echo '<td>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lc_update_lead_status');
            echo '<input type="hidden" name="action" value="lc_update_lead_status" />';
            echo '<input type="hidden" name="lead_id" value="' . esc_attr((string) $row->id) . '" />';
            echo '<select name="status">';
            foreach ($this->statuses as $status) {
                $selected = selected($row->status, $status, false);
                echo '<option value="' . esc_attr($status) . '" ' . $selected . '>' . esc_html($status) . '</option>';
            }
            echo '</select> ';
            submit_button('Save', 'small', 'submit', false);
            echo '</form>';
            echo '</td>';

            echo '</tr>';
        }

        if (empty($rows)) {
            echo '<tr><td colspan="7">No leads found.</td></tr>';
        }

        echo '</tbody></table>';
        $this->render_wrap_end();
    }

    public function render_runs()
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->db_table('lc_runs')} ORDER BY id DESC LIMIT 100");

        $this->render_wrap_start('Runs');
        $this->message_notice();

        $settings = $this->get_settings();

        echo '<div class="lc-card">';
        echo '<h2>Queue Discovery Run</h2>';
        if (empty($settings['google_places_api_key'])) {
            echo '<p><strong>Note:</strong> Google Places API key is not configured. Run will use directory fallback mode based on Settings.</p>';
        } else {
            echo '<p><strong>Google Places API key detected.</strong> Run can use Google Places API depending on discovery mode.</p>';
        }
        if (($settings['social_discovery_mode'] ?? 'off') !== 'off' && (empty($settings['google_cse_api_key']) || empty($settings['google_cse_cx']))) {
            echo '<p><strong>Note:</strong> Social discovery is enabled but Google Programmable Search API key/cx is missing. Configure both in Settings to enrich LinkedIn/social URLs safely.</p>';
        }
        echo '<form class="lc-form-grid" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lc_queue_run');
        echo '<input type="hidden" name="action" value="lc_queue_run" />';
        echo '<input type="text" name="query_text" placeholder="Search query (e.g. dentists)" required />';
        echo '<input type="text" name="city" placeholder="City" required />';
        echo '<input type="text" name="country" placeholder="Country (e.g. United States)" />';
        echo '<input type="number" min="0" name="radius_miles" placeholder="Radius miles (0 = city only)" />';
        echo '<input type="text" name="niche" placeholder="Niche (e.g. Cosmetic Dentistry)" />';
        echo '<textarea name="services" rows="2" placeholder="Services (comma separated: implants, whitening, emergency)"></textarea>';
        echo '<select name="website_focus"><option value="any">Website Focus: Any</option><option value="no_website">Website Focus: No Website Listed</option><option value="has_website">Website Focus: Has Website</option></select>';
        echo '<input type="number" min="0" max="5" step="0.1" name="min_rating" placeholder="Min rating (0-5)" />';
        echo '<input type="number" min="0" name="min_reviews" placeholder="Min reviews" />';
        echo '<input type="number" min="1" max="' . esc_attr((string) $settings['max_places_per_run']) . '" name="max_places" placeholder="Max places" />';
        submit_button('Queue Run', 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';

        echo '<h2>Run Queue</h2>';
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Query</th><th>Location</th><th>Niche/Services</th><th>Filters</th><th>Max</th><th>Status</th><th>Created</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $row->id) . '</td>';
            echo '<td>' . esc_html($row->query_text) . '</td>';
            echo '<td>' . esc_html($this->run_location_text($row)) . '</td>';
            echo '<td>' . esc_html((string) ($row->niche ?: '-')) . '<br/><small>' . esc_html((string) ($row->services ?: '-')) . '</small></td>';
            echo '<td>Rating >= ' . esc_html(number_format((float) ($row->min_rating ?? 0), 1)) . '<br/><small>Reviews >= ' . esc_html((string) ($row->min_reviews ?? 0)) . '</small><br/><small>Website: ' . esc_html(ucwords(str_replace('_', ' ', (string) ($row->website_focus ?? 'any')))) . '</small></td>';
            echo '<td>' . esc_html((string) $row->max_places) . '</td>';
            echo '<td>' . esc_html($row->status) . '</td>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '</tr>';
        }

        if (empty($rows)) {
            echo '<tr><td colspan="8">No runs queued yet.</td></tr>';
        }

        echo '</tbody></table>';
        $this->render_wrap_end();
    }

    public function render_duplicates()
    {
        global $wpdb;

        $leads_table = $this->db_table('lc_leads');

        $dupe_phone = $wpdb->get_results("SELECT phone, COUNT(*) AS c FROM {$leads_table} WHERE phone <> '' GROUP BY phone HAVING COUNT(*) > 1 ORDER BY c DESC LIMIT 50");
        $rows = $wpdb->get_results("SELECT id, business_name, website, phone FROM {$leads_table} ORDER BY id DESC LIMIT 300");

        $domain_counts = [];
        foreach ($rows as $row) {
            $domain = $this->normalize_domain($row->website);
            if (!$domain) {
                continue;
            }
            if (!isset($domain_counts[$domain])) {
                $domain_counts[$domain] = 0;
            }
            $domain_counts[$domain]++;
        }

        $this->render_wrap_start('Duplicates');

        echo '<div class="lc-card-grid">';
        echo '<div class="lc-card">';
        echo '<h2>Likely Duplicates by Phone</h2>';
        echo '<ul>';
        foreach ($dupe_phone as $item) {
            echo '<li>' . esc_html($item->phone) . ' (' . esc_html((string) $item->c) . ')</li>';
        }
        if (empty($dupe_phone)) {
            echo '<li>No phone duplicates detected.</li>';
        }
        echo '</ul>';
        echo '</div>';

        echo '<div class="lc-card">';
        echo '<h2>Likely Duplicates by Domain</h2>';
        echo '<ul>';
        $found = false;
        foreach ($domain_counts as $domain => $count) {
            if ($count > 1) {
                $found = true;
                echo '<li>' . esc_html($domain) . ' (' . esc_html((string) $count) . ')</li>';
            }
        }
        if (!$found) {
            echo '<li>No domain duplicates detected.</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';

        echo '<p>Merge workflow can be added on top of this detector if needed.</p>';

        $this->render_wrap_end();
    }

    public function render_exports()
    {
        $this->render_wrap_start('Exports');

        echo '<div class="lc-card">';
        echo '<p>Download outreach-ready CSV using leads currently marked Ready or Verified.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lc_export_ready');
        echo '<input type="hidden" name="action" value="lc_export_ready" />';
        submit_button('Download CSV', 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';

        $this->render_wrap_end();
    }

    public function render_suppression()
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->db_table('lc_suppression')} ORDER BY id DESC LIMIT 200");

        $this->render_wrap_start('Suppression');
        $this->message_notice();

        echo '<div class="lc-card">';
        echo '<h2>Add Suppression Entry</h2>';
        echo '<form class="lc-form-grid" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('lc_add_suppression');
        echo '<input type="hidden" name="action" value="lc_add_suppression" />';
        echo '<select name="type">';
        echo '<option value="email">Email</option>';
        echo '<option value="phone">Phone</option>';
        echo '<option value="domain">Domain</option>';
        echo '<option value="name">Name</option>';
        echo '</select>';
        echo '<input type="text" name="value" placeholder="Value" required />';
        echo '<input type="text" name="reason" placeholder="Reason" />';
        submit_button('Add', 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';

        echo '<h2>Suppression List</h2>';
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Type</th><th>Value</th><th>Reason</th><th>Created</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $row->id) . '</td>';
            echo '<td>' . esc_html($row->type) . '</td>';
            echo '<td>' . esc_html($row->value) . '</td>';
            echo '<td>' . esc_html($row->reason) . '</td>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '</tr>';
        }

        if (empty($rows)) {
            echo '<tr><td colspan="5">No suppression entries yet.</td></tr>';
        }

        echo '</tbody></table>';

        $this->render_wrap_end();
    }

    public function handle_enrich_lead()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_enrich_lead');

        $lead_id = absint($_POST['lead_id'] ?? 0);
        if ($lead_id > 0) {
            $this->enrich_single_lead_profile($lead_id);
        }

        $this->redirect_after_action('enriched', 'lc_intelligence');
    }

    public function handle_enrich_recent()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_enrich_recent');

        global $wpdb;
        $ids = $wpdb->get_col("SELECT id FROM {$this->db_table('lc_leads')} ORDER BY id DESC LIMIT 25");
        foreach ($ids as $lead_id) {
            $this->enrich_single_lead_profile((int) $lead_id);
        }

        $this->redirect_after_action('enriched_recent', 'lc_intelligence');
    }

    public function render_intelligence()
    {
        global $wpdb;

        $rows = $wpdb->get_results("
            SELECT l.id, l.business_name, l.city, l.category, l.website, l.phone, l.email, l.status,
                   p.primary_email, p.completeness_score, p.confidence_score, p.updated_at
            FROM {$this->db_table('lc_leads')} l
            LEFT JOIN {$this->db_table('lc_lead_profiles')} p ON p.lead_id = l.id
            ORDER BY l.id DESC
            LIMIT 100
        ");

        $this->render_wrap_start('Intelligence');
        $this->message_notice();

        echo '<div class="lc-card" style="margin-bottom:16px;">';
        echo '<h2>Profile Enrichment</h2>';
        echo '<p>Build richer lead dossiers with social profiles, possible emails, selected primary email, likely roles/owners, review signals, and job-posting signals.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-top:8px;">';
        wp_nonce_field('lc_enrich_recent');
        echo '<input type="hidden" name="action" value="lc_enrich_recent" />';
        submit_button('Enrich 25 Most Recent Leads', 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>ID</th><th>Lead</th><th>Primary Email</th><th>Completeness</th><th>Confidence</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $row->id) . '</td>';
            echo '<td><strong>' . esc_html($row->business_name) . '</strong><br/><small>' . esc_html($row->city) . ' | ' . esc_html($row->category) . ' | ' . esc_html($row->status) . '</small></td>';
            echo '<td>' . esc_html($row->primary_email ?: '-') . '</td>';
            echo '<td>' . esc_html((string) ($row->completeness_score ?? 0)) . '%</td>';
            echo '<td>' . esc_html((string) ($row->confidence_score ?? 0)) . '%</td>';
            echo '<td>' . esc_html($row->updated_at ?: '-') . '</td>';
            echo '<td>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('lc_enrich_lead');
            echo '<input type="hidden" name="action" value="lc_enrich_lead" />';
            echo '<input type="hidden" name="lead_id" value="' . esc_attr((string) $row->id) . '" />';
            submit_button('Enrich', 'small', 'submit', false);
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        if (empty($rows)) {
            echo '<tr><td colspan="7">No leads available.</td></tr>';
        }

        echo '</tbody></table>';
        $this->render_wrap_end();
    }

    public function handle_admin_reset_user_password()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_admin_reset_user_password');

        $user_id = absint($_POST['user_id'] ?? 0);
        $user = get_user_by('id', $user_id);
        if (!$user) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        $new_password = (string) ($_POST['new_password'] ?? '');
        if ($new_password === '') {
            $new_password = wp_generate_password(14, true, true);
        }

        wp_set_password($new_password, $user_id);
        update_user_meta($user_id, 'lc_locked', 0);

        $this->send_templated_email(
            $user->user_email,
            'password_changed',
            [
                'first_name' => (string) get_user_meta($user_id, 'first_name', true),
                'user_email' => $user->user_email,
                'new_password' => $new_password,
                'username' => $user->user_login,
            ],
            '5N2 Digital Lead Console Core Password Reset by Admin',
            "Your password was reset by an administrator.\n\nUsername: {username}\nTemporary/New Password: {new_password}\n\nPlease log in and change it immediately."
        );

        $this->redirect_after_action('user_reset_done', 'lc_users');
    }

    public function handle_admin_lock_user()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_admin_lock_user');

        $user_id = absint($_POST['user_id'] ?? 0);
        $user = get_user_by('id', $user_id);
        if (!$user) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        if (strtolower((string) $user->user_email) === $this->get_primary_admin_email()) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        update_user_meta($user_id, 'lc_locked', 1);
        wp_set_password(wp_generate_password(24, true, true), $user_id);

        $this->redirect_after_action('user_lock_done', 'lc_users');
    }

    public function handle_admin_unlock_user()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_admin_unlock_user');

        $user_id = absint($_POST['user_id'] ?? 0);
        $user = get_user_by('id', $user_id);
        if (!$user) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        update_user_meta($user_id, 'lc_locked', 0);
        $this->redirect_after_action('user_unlock_done', 'lc_users');
    }

    public function handle_admin_approve_user()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_admin_approve_user');

        $user_id = absint($_POST['user_id'] ?? 0);
        $user = get_user_by('id', $user_id);
        if (!$user) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        update_user_meta($user_id, 'lc_pending_approval', 0);
        update_user_meta($user_id, 'lc_registration_status', 'approved');
        update_user_meta($user_id, 'lc_locked', 0);

        $this->send_templated_email(
            $user->user_email,
            'registration_approved',
            [
                'first_name' => (string) get_user_meta($user_id, 'first_name', true),
                'user_email' => $user->user_email,
            ],
            'Registration approved',
            "Hello {first_name},\n\nYour account is now approved. You can sign in."
        );

        $this->log_system_event('users', 'info', 'User registration approved by admin.', ['user_id' => $user_id]);
        $this->redirect_after_action('user_unlock_done', 'lc_users');
    }

    public function handle_admin_reject_user()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_admin_reject_user');

        $user_id = absint($_POST['user_id'] ?? 0);
        $user = get_user_by('id', $user_id);
        if (!$user) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        update_user_meta($user_id, 'lc_pending_approval', 0);
        update_user_meta($user_id, 'lc_registration_status', 'rejected');
        update_user_meta($user_id, 'lc_locked', 1);

        $this->send_templated_email(
            $user->user_email,
            'registration_rejected',
            [
                'first_name' => (string) get_user_meta($user_id, 'first_name', true),
                'user_email' => $user->user_email,
            ],
            'Registration declined',
            "Hello {first_name},\n\nYour registration request was declined. Please contact admin."
        );

        $this->log_system_event('users', 'warning', 'User registration rejected by admin.', ['user_id' => $user_id]);
        $this->redirect_after_action('user_lock_done', 'lc_users');
    }

    public function handle_admin_update_user_profile()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_admin_update_user_profile');

        $user_id = absint($_POST['user_id'] ?? 0);
        $user = get_user_by('id', $user_id);
        if (!$user) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $display_name = sanitize_text_field($_POST['display_name'] ?? trim($first_name . ' ' . $last_name));
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $company = sanitize_text_field($_POST['company'] ?? '');
        $access_level = $this->normalize_access_level($_POST['access_level'] ?? 'standard');

        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name !== '' ? $display_name : $user->user_login,
        ]);

        update_user_meta($user_id, 'lc_phone', $phone);
        update_user_meta($user_id, 'lc_company', $company);
        update_user_meta($user_id, 'lc_access_level', $access_level);

        $this->log_system_event('users', 'info', 'User profile updated by super admin.', ['user_id' => $user_id, 'access_level' => $access_level]);
        $this->redirect_after_action('user_profile_saved', 'lc_users');
    }

    public function handle_admin_create_user()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_admin_create_user');

        $email = sanitize_email($_POST['email'] ?? '');
        if ($email === '' || email_exists($email)) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $company = sanitize_text_field($_POST['company'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if ($password === '') {
            $password = wp_generate_password(14, true, true);
        }
        $access_level = $this->normalize_access_level($_POST['access_level'] ?? 'standard');

        $base_login = sanitize_user(strstr($email, '@', true) ?: 'user', true);
        $login = $base_login ?: 'user';
        $suffix = 1;
        while (username_exists($login)) {
            $login = $base_login . $suffix;
            $suffix++;
        }

        $user_id = wp_create_user($login, $password, $email);
        if (is_wp_error($user_id) || !$user_id) {
            $this->redirect_after_action('user_action_error', 'lc_users');
        }

        wp_update_user([
            'ID' => (int) $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name) ?: $login,
            'role' => 'subscriber',
        ]);

        update_user_meta((int) $user_id, 'lc_phone', $phone);
        update_user_meta((int) $user_id, 'lc_company', $company);
        update_user_meta((int) $user_id, 'lc_access_level', $access_level);
        update_user_meta((int) $user_id, 'lc_pending_approval', 0);
        update_user_meta((int) $user_id, 'lc_registration_status', 'approved');
        update_user_meta((int) $user_id, 'lc_locked', 0);

        $this->send_templated_email(
            $email,
            'registration_approved',
            [
                'first_name' => $first_name,
                'full_name' => trim($first_name . ' ' . $last_name),
                'user_email' => $email,
                'username' => $login,
                'new_password' => $password,
            ],
            'Your account has been created',
            "Hello {first_name},\n\nYour account has been created by the super admin.\nUsername: {username}\nPassword: {new_password}\n\nPlease log in and update your password."
        );

        $this->log_system_event('users', 'info', 'User created by super admin.', ['user_id' => (int) $user_id, 'access_level' => $access_level]);
        $this->redirect_after_action('user_created', 'lc_users');
    }

    public function handle_frontend_save_settings()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_frontend_save_settings');

        $input = $_POST['lc_settings'] ?? [];
        if (!is_array($input)) {
            $input = [];
        }
        $input = wp_parse_args($input, $this->get_settings());
        $sanitized = $this->sanitize_settings($input);
        update_option('lc_settings', $sanitized);
        $this->log_system_event('settings', 'info', 'Settings updated from frontend.', []);
        $this->redirect_after_action('settings_saved', 'lc_settings');
    }

    private function redirect_after_action($message, $fallback_page)
    {
        $target = esc_url_raw($_POST['redirect_to'] ?? '');
        if (!$target) {
            $target = admin_url('admin.php?page=' . $fallback_page);
        }
        $target = add_query_arg('lc_msg', $message, $target);
        $target = add_query_arg('message', $message, $target);
        wp_safe_redirect($target);
        exit;
    }

    public function render_users()
    {
        $this->ensure_permissions();

        $users = get_users([
            'orderby' => 'registered',
            'order' => 'DESC',
            'number' => 200,
        ]);

        $this->render_wrap_start('Users');
        $this->message_notice();

        echo '<div class="lc-card" style="margin-bottom:16px;">';
        echo '<h2>User Administration</h2>';
        echo '<p>Only super admin can create users, update important profile information, and set access level. Email remains fixed and cannot be changed.</p>';
        echo '</div>';

        echo '<div class="lc-card" style="margin-bottom:16px;">';
        echo '<h2>Create User</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-form-grid">';
        wp_nonce_field('lc_admin_create_user');
        echo '<input type="hidden" name="action" value="lc_admin_create_user" />';
        echo '<input type="text" name="first_name" placeholder="First name" required />';
        echo '<input type="text" name="last_name" placeholder="Last name" required />';
        echo '<input type="email" name="email" placeholder="Email (immutable)" required />';
        echo '<input type="text" name="phone" placeholder="Phone" />';
        echo '<input type="text" name="company" placeholder="Company" />';
        echo '<input type="text" name="password" placeholder="Optional password (auto-generate if empty)" />';
        echo '<select name="access_level"><option value="standard">Standard Access</option><option value="manager">Manager Access</option><option value="readonly">Read-Only Access</option></select>';
        submit_button('Create User', 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Photo</th><th>User</th><th>Access</th><th>Status</th><th>Approval</th><th>Update Info</th><th>Manual Password Reset</th><th>Lock Controls</th></tr></thead><tbody>';

        foreach ($users as $user) {
            $locked = !empty(get_user_meta($user->ID, 'lc_locked', true));
            $is_primary = strtolower((string) $user->user_email) === $this->get_primary_admin_email();
            $is_pending = !empty(get_user_meta($user->ID, 'lc_pending_approval', true));
            $reg_status = (string) get_user_meta($user->ID, 'lc_registration_status', true);
            $access_level = $this->normalize_access_level(get_user_meta($user->ID, 'lc_access_level', true));
            $phone = (string) get_user_meta($user->ID, 'lc_phone', true);
            $company = (string) get_user_meta($user->ID, 'lc_company', true);
            $first_name = (string) get_user_meta($user->ID, 'first_name', true);
            $last_name = (string) get_user_meta($user->ID, 'last_name', true);

            echo '<tr>';
            echo '<td>' . $this->user_avatar_html((int) $user->ID, 48, (string) ($user->display_name ?: $user->user_login)) . '</td>';
            echo '<td><strong>' . esc_html($user->display_name ?: $user->user_login) . '</strong><br/>';
            echo '<small>Username: ' . esc_html($user->user_login) . '</small><br/>';
            echo '<small>Email: ' . esc_html($user->user_email) . '</small></td>';
            echo '<td>' . esc_html(ucfirst($access_level)) . '</td>';
            echo '<td>' . ($locked ? '<span style="color:#b10000;font-weight:600;">Locked</span>' : '<span style="color:#0a7a0a;font-weight:600;">Active</span>') . ($is_primary ? '<br/><small>Primary Admin</small>' : '') . '</td>';

            echo '<td>';
            if ($is_primary) {
                echo '<em>Auto-approved</em>';
            } elseif ($is_pending || $reg_status === 'pending') {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-right:6px;">';
                wp_nonce_field('lc_admin_approve_user');
                echo '<input type="hidden" name="action" value="lc_admin_approve_user" />';
                echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $user->ID) . '" />';
                submit_button('Approve', 'small', 'submit', false);
                echo '</form>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;">';
                wp_nonce_field('lc_admin_reject_user');
                echo '<input type="hidden" name="action" value="lc_admin_reject_user" />';
                echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $user->ID) . '" />';
                submit_button('Reject', 'small', 'submit', false);
                echo '</form>';
            } else {
                echo '<em>' . esc_html($reg_status !== '' ? ucfirst($reg_status) : 'Approved') . '</em>';
            }
            echo '</td>';

            echo '<td>';
            if ($is_primary) {
                echo '<em>Protected</em>';
            } else {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-form-grid">';
                wp_nonce_field('lc_admin_update_user_profile');
                echo '<input type="hidden" name="action" value="lc_admin_update_user_profile" />';
                echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $user->ID) . '" />';
                echo '<input type="text" name="first_name" value="' . esc_attr($first_name) . '" placeholder="First name" />';
                echo '<input type="text" name="last_name" value="' . esc_attr($last_name) . '" placeholder="Last name" />';
                echo '<input type="text" name="display_name" value="' . esc_attr((string) $user->display_name) . '" placeholder="Display name" />';
                echo '<input type="text" value="' . esc_attr($user->user_email) . '" disabled />';
                echo '<input type="text" name="phone" value="' . esc_attr($phone) . '" placeholder="Phone" />';
                echo '<input type="text" name="company" value="' . esc_attr($company) . '" placeholder="Company" />';
                echo '<select name="access_level">';
                echo '<option value="standard" ' . selected($access_level, 'standard', false) . '>Standard Access</option>';
                echo '<option value="manager" ' . selected($access_level, 'manager', false) . '>Manager Access</option>';
                echo '<option value="readonly" ' . selected($access_level, 'readonly', false) . '>Read-Only Access</option>';
                echo '</select>';
                submit_button('Save Info', 'small', 'submit', false);
                echo '</form>';
            }
            echo '</td>';

            echo '<td>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-form-grid">';
            wp_nonce_field('lc_admin_reset_user_password');
            echo '<input type="hidden" name="action" value="lc_admin_reset_user_password" />';
            echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $user->ID) . '" />';
            echo '<input type="text" name="new_password" placeholder="Optional custom password" />';
            submit_button('Reset Password', 'secondary', 'submit', false);
            echo '</form>';
            echo '</td>';

            echo '<td>';
            if (!$is_primary) {
                if ($locked) {
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                    wp_nonce_field('lc_admin_unlock_user');
                    echo '<input type="hidden" name="action" value="lc_admin_unlock_user" />';
                    echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $user->ID) . '" />';
                    submit_button('Unlock', 'small', 'submit', false);
                    echo '</form>';
                } else {
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                    wp_nonce_field('lc_admin_lock_user');
                    echo '<input type="hidden" name="action" value="lc_admin_lock_user" />';
                    echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $user->ID) . '" />';
                    submit_button('Lock', 'small', 'submit', false);
                    echo '</form>';
                }
            } else {
                echo '<em>Protected</em>';
            }
            echo '</td>';

            echo '</tr>';
        }

        if (empty($users)) {
            echo '<tr><td colspan="8">No users found.</td></tr>';
        }

        echo '</tbody></table>';
        $this->render_wrap_end();
    }

    public function render_logs()
    {
        $this->ensure_permissions();
        global $wpdb;

        $rows = $wpdb->get_results("SELECT * FROM {$this->db_table('lc_system_logs')} ORDER BY id DESC LIMIT 500");

        $this->render_wrap_start('Logs');
        echo '<div class="lc-card" style="margin-bottom:16px;">';
        echo '<h2>System and Behavior Logs</h2>';
        echo '<p>This page captures auth events, compliance confirmations, run/activity changes, and runtime errors. Visible to super admin only.</p>';
        echo '</div>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Time</th><th>Level</th><th>Category</th><th>User ID</th><th>Message</th><th>Context</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '<td>' . esc_html(strtoupper((string) $row->level)) . '</td>';
            echo '<td>' . esc_html($row->category) . '</td>';
            echo '<td>' . esc_html((string) $row->user_id) . '</td>';
            echo '<td>' . esc_html($row->message) . '</td>';
            echo '<td><code style="white-space:pre-wrap;">' . esc_html((string) $row->context_json) . '</code></td>';
            echo '</tr>';
        }
        if (empty($rows)) {
            echo '<tr><td colspan="6">No logs yet.</td></tr>';
        }
        echo '</tbody></table>';
        $this->render_wrap_end();
    }

    private function enrich_single_lead_profile($lead_id)
    {
        global $wpdb;
        $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->db_table('lc_leads')} WHERE id = %d", $lead_id));
        if (!$lead) {
            return false;
        }

        $settings = $this->get_settings();
        $website = trim((string) $lead->website);

        $emails = [];
        $socials = [
            'linkedin_url' => (string) ($lead->linkedin_url ?? ''),
            'facebook_url' => (string) ($lead->facebook_url ?? ''),
            'instagram_url' => (string) ($lead->instagram_url ?? ''),
            'x_url' => (string) ($lead->x_url ?? ''),
            'youtube_url' => (string) ($lead->youtube_url ?? ''),
        ];
        $people = [];
        $company = [
            'business_name' => $lead->business_name,
            'city' => $lead->city,
            'category' => $lead->category,
            'website' => $lead->website,
        ];
        $reviews = [];
        $jobs = [];

        if (!empty($website)) {
            $crawl = $this->crawl_website_profile_signals($website);
            $emails = array_merge($emails, $crawl['emails']);
            $socials = array_merge($socials, $crawl['socials']);
            $people = array_merge($people, $crawl['people']);
            $company = array_merge($company, $crawl['company']);
        }

        $social_discovery = $this->discover_social_profiles_for_lead(
            (object) [
                'business_name' => $lead->business_name,
                'city' => $lead->city,
                'linkedin_url' => $socials['linkedin_url'] ?? '',
                'facebook_url' => $socials['facebook_url'] ?? '',
                'instagram_url' => $socials['instagram_url'] ?? '',
                'x_url' => $socials['x_url'] ?? '',
                'youtube_url' => $socials['youtube_url'] ?? '',
            ],
            (string) ($settings['google_cse_api_key'] ?? ''),
            (string) ($settings['google_cse_cx'] ?? '')
        );
        if (!empty($social_discovery['data'])) {
            $socials = array_merge($socials, $social_discovery['data']);
        }

        $emails = array_values(array_unique(array_filter(array_map('sanitize_email', $emails))));
        $primary_email = $this->select_primary_email($emails, $website);

        $review_signal = $this->discover_review_signals($lead, $settings);
        if (!empty($review_signal)) {
            $reviews[] = $review_signal;
        }

        $jobs = $this->discover_job_signals($lead, $settings);

        $completeness = $this->compute_profile_completeness($emails, $primary_email, $socials, $people, $reviews, $jobs);
        $confidence = $this->compute_profile_confidence($primary_email, $social_discovery['confidence'] ?? 0, $reviews);

        $profile_data = [
            'possible_emails' => wp_json_encode($emails),
            'primary_email' => $primary_email,
            'socials_json' => wp_json_encode($socials),
            'people_json' => wp_json_encode($people),
            'company_json' => wp_json_encode($company),
            'reviews_json' => wp_json_encode($reviews),
            'jobs_json' => wp_json_encode($jobs),
            'completeness_score' => $completeness,
            'confidence_score' => $confidence,
        ];

        $profiles_table = $this->db_table('lc_lead_profiles');
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$profiles_table} WHERE lead_id = %d", $lead_id));
        if ($exists) {
            $wpdb->update($profiles_table, $profile_data, ['lead_id' => $lead_id]);
        } else {
            $profile_data['lead_id'] = $lead_id;
            $wpdb->insert($profiles_table, $profile_data);
        }

        $wpdb->update($this->db_table('lc_leads'), [
            'email' => $primary_email ?: $lead->email,
            'linkedin_url' => esc_url_raw($socials['linkedin_url'] ?? ''),
            'facebook_url' => esc_url_raw($socials['facebook_url'] ?? ''),
            'instagram_url' => esc_url_raw($socials['instagram_url'] ?? ''),
            'x_url' => esc_url_raw($socials['x_url'] ?? ''),
            'youtube_url' => esc_url_raw($socials['youtube_url'] ?? ''),
            'social_confidence' => $confidence,
            'social_source' => 'website+cse',
        ], ['id' => $lead_id]);

        return true;
    }

    private function crawl_website_profile_signals($website)
    {
        $result = [
            'emails' => [],
            'socials' => [
                'linkedin_url' => '',
                'facebook_url' => '',
                'instagram_url' => '',
                'x_url' => '',
                'youtube_url' => '',
            ],
            'people' => [],
            'company' => [],
        ];

        $response = wp_remote_get($website, ['timeout' => 15]);
        if (is_wp_error($response)) {
            return $result;
        }

        $html = (string) wp_remote_retrieve_body($response);
        if ($html === '') {
            return $result;
        }

        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $html, $emails);
        $result['emails'] = $emails[0] ?? [];

        $social_patterns = [
            'linkedin_url' => '/https?:\/\/(?:www\.)?linkedin\.com\/[^\s"\'<>]+/i',
            'facebook_url' => '/https?:\/\/(?:www\.)?facebook\.com\/[^\s"\'<>]+/i',
            'instagram_url' => '/https?:\/\/(?:www\.)?instagram\.com\/[^\s"\'<>]+/i',
            'x_url' => '/https?:\/\/(?:www\.)?(?:x\.com|twitter\.com)\/[^\s"\'<>]+/i',
            'youtube_url' => '/https?:\/\/(?:www\.)?youtube\.com\/[^\s"\'<>]+/i',
        ];
        foreach ($social_patterns as $field => $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $result['socials'][$field] = esc_url_raw($m[0]);
            }
        }

        if (preg_match('/<title>(.*?)<\/title>/is', $html, $title)) {
            $result['company']['page_title'] = sanitize_text_field(wp_strip_all_tags($title[1]));
        }
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $meta)) {
            $result['company']['meta_description'] = sanitize_text_field($meta[1]);
        }

        $role_patterns = [
            '/(Founder|Owner|CEO|President|Director)\s*[:\-]\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})/i',
            '/([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})\s*,\s*(Founder|Owner|CEO|President|Director)/i',
        ];
        foreach ($role_patterns as $pattern) {
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $person = [
                        'name' => sanitize_text_field($match[2] ?? $match[1] ?? ''),
                        'position' => sanitize_text_field($match[1] ?? $match[2] ?? ''),
                        'source' => 'website',
                    ];
                    if (!empty($person['name'])) {
                        $result['people'][] = $person;
                    }
                }
            }
        }

        return $result;
    }

    private function select_primary_email($emails, $website)
    {
        if (empty($emails)) {
            return '';
        }

        $domain = $this->normalize_domain($website);
        $best = '';
        $best_score = -1;
        foreach ($emails as $email) {
            $score = 10;
            $parts = explode('@', $email);
            $local = strtolower($parts[0] ?? '');
            $email_domain = strtolower($parts[1] ?? '');

            if ($domain && strpos($email_domain, $domain) !== false) {
                $score += 40;
            }
            if (in_array($local, ['info', 'contact', 'hello', 'support', 'sales', 'admin'], true)) {
                $score += 25;
            }
            if (strpos($local, 'noreply') !== false || strpos($local, 'no-reply') !== false) {
                $score -= 30;
            }
            if (preg_match('/\d{4,}/', $local)) {
                $score -= 10;
            }

            if ($score > $best_score) {
                $best_score = $score;
                $best = $email;
            }
        }

        return $best;
    }

    private function discover_review_signals($lead, $settings)
    {
        if (empty($settings['google_places_api_key'])) {
            return [];
        }

        $query = trim($lead->business_name . ' ' . $lead->city);
        $url = add_query_arg([
            'query' => $query,
            'key' => $settings['google_places_api_key'],
        ], 'https://maps.googleapis.com/maps/api/place/textsearch/json');
        $response = wp_remote_get($url, ['timeout' => 15]);
        if (is_wp_error($response)) {
            return [];
        }
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if (empty($payload['results'][0])) {
            return [];
        }
        $top = $payload['results'][0];
        return [
            'provider' => 'google_places',
            'rating' => (float) ($top['rating'] ?? 0),
            'review_count' => absint($top['user_ratings_total'] ?? 0),
            'source_url' => 'https://maps.google.com/?q=' . rawurlencode($query),
        ];
    }

    private function discover_job_signals($lead, $settings)
    {
        $key = trim((string) ($settings['google_cse_api_key'] ?? ''));
        $cx = trim((string) ($settings['google_cse_cx'] ?? ''));
        if ($key === '' || $cx === '') {
            return [];
        }

        $query = sprintf('site:indeed.com "%s" "%s"', $lead->business_name, $lead->city);
        $first = $this->google_cse_search($query, $key, $cx);
        if (empty($first['link'])) {
            return [];
        }

        return [
            [
                'platform' => 'Indeed',
                'url' => esc_url_raw($first['link']),
                'title' => sanitize_text_field($first['title'] ?? ''),
            ],
        ];
    }

    private function compute_profile_completeness($emails, $primary_email, $socials, $people, $reviews, $jobs)
    {
        $score = 0;
        if (!empty($emails)) {
            $score += 20;
        }
        if (!empty($primary_email)) {
            $score += 20;
        }
        if (!empty(array_filter($socials))) {
            $score += 20;
        }
        if (!empty($people)) {
            $score += 20;
        }
        if (!empty($reviews)) {
            $score += 10;
        }
        if (!empty($jobs)) {
            $score += 10;
        }
        return max(0, min(100, $score));
    }

    private function compute_profile_confidence($primary_email, $social_confidence, $reviews)
    {
        $score = 35;
        if (!empty($primary_email)) {
            $score += 25;
        }
        $score += (int) min(30, max(0, $social_confidence / 3));
        if (!empty($reviews)) {
            $score += 10;
        }
        return max(0, min(100, $score));
    }

    public function render_reports()
    {
        global $wpdb;

        $status_rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$this->db_table('lc_leads')} GROUP BY status ORDER BY total DESC");
        $runs = $wpdb->get_results("SELECT id, query_text, status, created_at FROM {$this->db_table('lc_runs')} ORDER BY id DESC LIMIT 10");

        $this->render_wrap_start('Reports');

        echo '<div class="lc-card-grid">';

        echo '<div class="lc-card">';
        echo '<h2>Lead Funnel</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Status</th><th>Total</th></tr></thead><tbody>';
        foreach ($status_rows as $row) {
            echo '<tr><td>' . esc_html($row->status) . '</td><td>' . esc_html((string) $row->total) . '</td></tr>';
        }
        if (empty($status_rows)) {
            echo '<tr><td colspan="2">No lead data yet.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="lc-card">';
        echo '<h2>Recent Run Health</h2>';
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Query</th><th>Status</th><th>Created</th></tr></thead><tbody>';
        foreach ($runs as $run) {
            echo '<tr><td>' . esc_html((string) $run->id) . '</td><td>' . esc_html($run->query_text) . '</td><td>' . esc_html($run->status) . '</td><td>' . esc_html($run->created_at) . '</td></tr>';
        }
        if (empty($runs)) {
            echo '<tr><td colspan="4">No run data yet.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';

        echo '</div>';

        $this->render_wrap_end();
    }

    public function render_platform()
    {
        $settings = $this->get_settings();

        $this->render_wrap_start('Platform');

        $modules = [
            [
                'name' => 'Leads Engine',
                'status' => 'Active',
                'scope' => 'Queue, discovery, review, enrichment, export',
                'next' => 'CRM handoff pipeline and quality scoring improvements.',
            ],
            [
                'name' => 'CRM + Email',
                'status' => 'Planning',
                'scope' => 'Connector framework, SMTP health, automation templates',
                'next' => 'Build connector contracts for WordPress CRM plugins and external APIs.',
            ],
            [
                'name' => 'Social + Forums',
                'status' => 'Planning',
                'scope' => 'Per-platform connectors, capability flags, publishing and inbox',
                'next' => 'Ship MVP connectors with platform-specific feature gates.',
            ],
            [
                'name' => 'WebOps Security',
                'status' => 'Planning',
                'scope' => 'Uptime, health checks, remote actions, incident alerts',
                'next' => 'Implement site heartbeat worker and alert routing.',
            ],
            [
                'name' => 'SEO + Extension',
                'status' => 'Planning',
                'scope' => 'Audit suite, rank tracking, browser extension sync',
                'next' => 'Define extension API contracts and SEO audit baseline.',
            ],
        ];

        echo '<div class="lc-card">';
        echo '<h2>Program Overview</h2>';
        echo '<p>Single platform operating mode: <strong>' . esc_html($settings['platform_mode']) . '</strong>.</p>';
        echo '<p>This page is the execution board for the five-module roadmap on branch <code>app</code>.</p>';
        echo '</div>';

        echo '<div class="lc-card" style="margin-top:16px;">';
        echo '<h2>Module Status</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Module</th><th>Status</th><th>Current Scope</th><th>Next Milestone</th></tr></thead><tbody>';
        foreach ($modules as $module) {
            echo '<tr>';
            echo '<td>' . esc_html($module['name']) . '</td>';
            echo '<td>' . esc_html($module['status']) . '</td>';
            echo '<td>' . esc_html($module['scope']) . '</td>';
            echo '<td>' . esc_html($module['next']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="lc-card" style="margin-top:16px;">';
        echo '<h2>Default Guardrails</h2>';
        echo '<ul>';
        echo '<li>Use official APIs/connectors whenever available.</li>';
        echo '<li>Respect platform terms and permissions before enabling advanced actions.</li>';
        echo '<li>Store action logs and connector events in system logs for auditability.</li>';
        echo '<li>Enable web/email notifications by default; sound alerts are optional.</li>';
        echo '</ul>';
        echo '</div>';

        $this->render_wrap_end();
    }

    public function render_settings()
    {
        $settings = $this->get_settings();

        $this->render_wrap_start('Settings');

        echo '<form method="post" action="options.php" class="lc-card">';
        settings_fields('lc_settings_group');

        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label for="lc_domain_fragment">Domain fragment</label></th><td><input id="lc_domain_fragment" type="text" name="lc_settings[domain_fragment]" value="' . esc_attr($settings['domain_fragment']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_max_places">Max places per run</label></th><td><input id="lc_max_places" type="number" min="1" name="lc_settings[max_places_per_run]" value="' . esc_attr((string) $settings['max_places_per_run']) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_platform_mode">Platform mode</label></th><td><select id="lc_platform_mode" name="lc_settings[platform_mode]">';
        echo '<option value="single_tenant" ' . selected($settings['platform_mode'], 'single_tenant', false) . '>Single tenant (5N2 internal)</option>';
        echo '<option value="multi_tenant_ready" ' . selected($settings['platform_mode'], 'multi_tenant_ready', false) . '>Multi-tenant ready (future SaaS)</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="lc_notifications_web">Web notifications</label></th><td><label><input id="lc_notifications_web" type="checkbox" name="lc_settings[notifications_web]" value="1" ' . checked(!empty($settings['notifications_web']), true, false) . ' /> Enabled</label></td></tr>';
        echo '<tr><th scope="row"><label for="lc_notifications_email">Email notifications</label></th><td><label><input id="lc_notifications_email" type="checkbox" name="lc_settings[notifications_email]" value="1" ' . checked(!empty($settings['notifications_email']), true, false) . ' /> Enabled</label></td></tr>';
        echo '<tr><th scope="row"><label for="lc_notifications_sound">Sound notifications</label></th><td><label><input id="lc_notifications_sound" type="checkbox" name="lc_settings[notifications_sound]" value="1" ' . checked(!empty($settings['notifications_sound']), true, false) . ' /> Enabled</label></td></tr>';
        echo '<tr><th scope="row"><label for="lc_human_support_email">Human support email</label></th><td><input id="lc_human_support_email" type="email" name="lc_settings[human_support_email]" value="' . esc_attr($settings['human_support_email']) . '" class="regular-text" /><p class="description">Fallback escalation destination when automated support cannot resolve a case.</p></td></tr>';
        echo '<tr><th scope="row"><label for="lc_bridge_enabled">Plugin bridge API</label></th><td><label><input id="lc_bridge_enabled" type="checkbox" name="lc_settings[bridge_enabled]" value="1" ' . checked(!empty($settings['bridge_enabled']), true, false) . ' /> Enabled</label><p class="description">Allows app.5n2digital.com to connect through secure REST endpoints.</p></td></tr>';
        echo '<tr><th scope="row"><label for="lc_bridge_shared_key">Bridge shared key</label></th><td><input id="lc_bridge_shared_key" type="password" name="lc_settings[bridge_shared_key]" value="' . esc_attr((string) $settings['bridge_shared_key']) . '" class="regular-text code" /><p class="description">Send this value as <code>X-LC-Bridge-Key</code> header from the main app.</p></td></tr>';
        echo '<tr><th scope="row"><label for="lc_crm_connectors">CRM connectors</label></th><td><textarea id="lc_crm_connectors" name="lc_settings[crm_connectors]" class="large-text code" rows="4" placeholder="fluentcrm|plugin|enabled&#10;hubspot|api|planned">' . esc_textarea((string) $settings['crm_connectors']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_social_connectors">Social/forum connectors</label></th><td><textarea id="lc_social_connectors" name="lc_settings[social_connectors]" class="large-text code" rows="4" placeholder="facebook|api|planned&#10;reddit|api|planned">' . esc_textarea((string) $settings['social_connectors']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_webops_monitors">WebOps monitors</label></th><td><textarea id="lc_webops_monitors" name="lc_settings[webops_monitors]" class="large-text code" rows="4" placeholder="uptime_ping|enabled&#10;ssl_expiry|enabled">' . esc_textarea((string) $settings['webops_monitors']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_seo_extension_enabled">SEO browser extension</label></th><td><label><input id="lc_seo_extension_enabled" type="checkbox" name="lc_settings[seo_extension_enabled]" value="1" ' . checked(!empty($settings['seo_extension_enabled']), true, false) . ' /> Enable extension sync API (preparation mode)</label></td></tr>';
        echo '<tr><th scope="row"><label for="lc_live_api">Enable live API calls</label></th><td><label><input id="lc_live_api" type="checkbox" name="lc_settings[enable_live_api_calls]" value="1" ' . checked(!empty($settings['enable_live_api_calls']), true, false) . ' /> Enabled</label></td></tr>';
        echo '<tr><th scope="row"><label for="lc_google_key">Google Places API key</label></th><td><input id="lc_google_key" type="text" name="lc_settings[google_places_api_key]" value="' . esc_attr($settings['google_places_api_key']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_discovery_mode">Discovery mode</label></th><td><select id="lc_discovery_mode" name="lc_settings[discovery_mode]">';
        echo '<option value="hybrid" ' . selected($settings['discovery_mode'], 'hybrid', false) . '>Hybrid (API first, fallback directories)</option>';
        echo '<option value="google_only" ' . selected($settings['discovery_mode'], 'google_only', false) . '>Google Places API only</option>';
        echo '<option value="directory_only" ' . selected($settings['discovery_mode'], 'directory_only', false) . '>Directory fallback only</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="lc_directory_sources">Directory sources</label></th><td>';
        echo '<textarea id="lc_directory_sources" name="lc_settings[directory_sources]" class="large-text code" rows="8" placeholder="Source Name|https://example.com/search?q={query}&loc={city}|80">' . esc_textarea($settings['directory_sources']) . '</textarea>';
        echo '<p class="description">Optional custom fallback list. One source per line in format: <code>Name|URL-with-{query}-and-{city}|QualityScore</code>.</p>';
        echo '</td></tr>';
        echo '<tr><th scope="row"><label for="lc_social_mode">Social discovery mode</label></th><td><select id="lc_social_mode" name="lc_settings[social_discovery_mode]">';
        echo '<option value="off" ' . selected($settings['social_discovery_mode'], 'off', false) . '>Off</option>';
        echo '<option value="url_discovery_only" ' . selected($settings['social_discovery_mode'], 'url_discovery_only', false) . '>URL discovery only (recommended)</option>';
        echo '<option value="official_api_enabled" ' . selected($settings['social_discovery_mode'], 'official_api_enabled', false) . '>Official API enabled (requires custom OAuth integration)</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="lc_google_cse_key">Google Programmable Search API key</label></th><td><input id="lc_google_cse_key" type="text" name="lc_settings[google_cse_api_key]" value="' . esc_attr($settings['google_cse_api_key']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_google_cse_cx">Google Programmable Search Engine ID (cx)</label></th><td><input id="lc_google_cse_cx" type="text" name="lc_settings[google_cse_cx]" value="' . esc_attr($settings['google_cse_cx']) . '" class="regular-text code" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_enabled">Enable SMTP</label></th><td><label><input id="lc_smtp_enabled" type="checkbox" name="lc_settings[smtp_enabled]" value="1" ' . checked(!empty($settings['smtp_enabled']), true, false) . ' /> Enabled</label></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_host">SMTP host</label></th><td><input id="lc_smtp_host" type="text" name="lc_settings[smtp_host]" value="' . esc_attr($settings['smtp_host']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_port">SMTP port</label></th><td><input id="lc_smtp_port" type="number" min="1" name="lc_settings[smtp_port]" value="' . esc_attr((string) $settings['smtp_port']) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_encryption">SMTP encryption</label></th><td><select id="lc_smtp_encryption" name="lc_settings[smtp_encryption]">';
        echo '<option value="tls" ' . selected($settings['smtp_encryption'], 'tls', false) . '>TLS</option>';
        echo '<option value="ssl" ' . selected($settings['smtp_encryption'], 'ssl', false) . '>SSL</option>';
        echo '<option value="none" ' . selected($settings['smtp_encryption'], 'none', false) . '>None</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_auth">SMTP auth</label></th><td><label><input id="lc_smtp_auth" type="checkbox" name="lc_settings[smtp_auth]" value="1" ' . checked(!empty($settings['smtp_auth']), true, false) . ' /> Use username/password</label></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_username">SMTP username</label></th><td><input id="lc_smtp_username" type="text" name="lc_settings[smtp_username]" value="' . esc_attr($settings['smtp_username']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_password">SMTP password</label></th><td><input id="lc_smtp_password" type="password" name="lc_settings[smtp_password]" value="' . esc_attr($settings['smtp_password']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_from_email">From email</label></th><td><input id="lc_smtp_from_email" type="email" name="lc_settings[smtp_from_email]" value="' . esc_attr($settings['smtp_from_email']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_smtp_from_name">From name</label></th><td><input id="lc_smtp_from_name" type="text" name="lc_settings[smtp_from_name]" value="' . esc_attr($settings['smtp_from_name']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reset_subject">Reset email subject</label></th><td><input id="lc_tmpl_reset_subject" type="text" name="lc_settings[email_template_reset_subject]" value="' . esc_attr($settings['email_template_reset_subject']) . '" class="large-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reset_body">Reset email body</label></th><td><textarea id="lc_tmpl_reset_body" name="lc_settings[email_template_reset_body]" class="large-text code" rows="5">' . esc_textarea((string) $settings['email_template_reset_body']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reg_admin_subject">Admin registration alert subject</label></th><td><input id="lc_tmpl_reg_admin_subject" type="text" name="lc_settings[email_template_registration_admin_subject]" value="' . esc_attr($settings['email_template_registration_admin_subject']) . '" class="large-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reg_admin_body">Admin registration alert body</label></th><td><textarea id="lc_tmpl_reg_admin_body" name="lc_settings[email_template_registration_admin_body]" class="large-text code" rows="5">' . esc_textarea((string) $settings['email_template_registration_admin_body']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reg_recv_subject">Registration received subject</label></th><td><input id="lc_tmpl_reg_recv_subject" type="text" name="lc_settings[email_template_registration_received_subject]" value="' . esc_attr($settings['email_template_registration_received_subject']) . '" class="large-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reg_recv_body">Registration received body</label></th><td><textarea id="lc_tmpl_reg_recv_body" name="lc_settings[email_template_registration_received_body]" class="large-text code" rows="5">' . esc_textarea((string) $settings['email_template_registration_received_body']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reg_ok_subject">Registration approved subject</label></th><td><input id="lc_tmpl_reg_ok_subject" type="text" name="lc_settings[email_template_registration_approved_subject]" value="' . esc_attr($settings['email_template_registration_approved_subject']) . '" class="large-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reg_ok_body">Registration approved body</label></th><td><textarea id="lc_tmpl_reg_ok_body" name="lc_settings[email_template_registration_approved_body]" class="large-text code" rows="5">' . esc_textarea((string) $settings['email_template_registration_approved_body']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reg_no_subject">Registration rejected subject</label></th><td><input id="lc_tmpl_reg_no_subject" type="text" name="lc_settings[email_template_registration_rejected_subject]" value="' . esc_attr($settings['email_template_registration_rejected_subject']) . '" class="large-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_reg_no_body">Registration rejected body</label></th><td><textarea id="lc_tmpl_reg_no_body" name="lc_settings[email_template_registration_rejected_body]" class="large-text code" rows="5">' . esc_textarea((string) $settings['email_template_registration_rejected_body']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_pwd_changed_subject">Password changed subject</label></th><td><input id="lc_tmpl_pwd_changed_subject" type="text" name="lc_settings[email_template_password_changed_subject]" value="' . esc_attr($settings['email_template_password_changed_subject']) . '" class="large-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_pwd_changed_body">Password changed body</label></th><td><textarea id="lc_tmpl_pwd_changed_body" name="lc_settings[email_template_password_changed_body]" class="large-text code" rows="5">' . esc_textarea((string) $settings['email_template_password_changed_body']) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_pwd_admin_subject">Admin password alert subject</label></th><td><input id="lc_tmpl_pwd_admin_subject" type="text" name="lc_settings[email_template_admin_password_changed_subject]" value="' . esc_attr($settings['email_template_admin_password_changed_subject']) . '" class="large-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_tmpl_pwd_admin_body">Admin password alert body</label></th><td><textarea id="lc_tmpl_pwd_admin_body" name="lc_settings[email_template_admin_password_changed_body]" class="large-text code" rows="5">' . esc_textarea((string) $settings['email_template_admin_password_changed_body']) . '</textarea></td></tr>';
        echo '</tbody></table>';

        submit_button('Save Settings');
        echo '</form>';

        echo '<div class="lc-card" style="margin-top:16px;">';
        echo '<h2>Access Control</h2>';
        echo '<p><strong>Primary admin email:</strong> ' . esc_html($this->get_primary_admin_email()) . '</p>';
        echo '<p>Only this email can access Lead Console and create users from WordPress admin.</p>';
        echo '</div>';

        echo '<div class="lc-card" style="margin-top:16px;">';
        echo '<h2>Google Places API Setup (Step by Step)</h2>';
        echo '<ol>';
        echo '<li>Go to Google Cloud Console and select/create a project.</li>';
        echo '<li>Enable <strong>Places API</strong>.</li>';
        echo '<li>Create an API key under <strong>APIs & Services -> Credentials</strong>.</li>';
        echo '<li>Restrict key usage to your WordPress domain and Places API.</li>';
        echo '<li>Paste key into <strong>Lead Console -> Settings -> Google Places API key</strong>.</li>';
        echo '<li>Enable <strong>Live API calls</strong>.</li>';
        echo '<li>Set Discovery mode to <strong>Hybrid</strong> or <strong>Google Places API only</strong>.</li>';
        echo '<li>Save Settings, then queue a run in <strong>Lead Console -> Runs</strong>.</li>';
        echo '</ol>';
        echo '<p><strong>If key is missing:</strong> plugin automatically logs and uses directory fallback when mode allows it.</p>';
        echo '</div>';

        echo '<div class="lc-card" style="margin-top:16px;">';
        echo '<h2>Fallback Directory Scan Guidance</h2>';
        echo '<p>Default fallback sources include Google Maps search URL capture, Yelp, Yellow Pages, BBB, Chamber of Commerce, and Manta. You can override sources in Directory sources field.</p>';
        echo '<p>Recommended practice is to prioritize high-authority directories and review each source quality score before outreach.</p>';
        echo '</div>';

        echo '<div class="lc-card" style="margin-top:16px;">';
        echo '<h2>Social Discovery (Low-Risk Setup)</h2>';
        echo '<ol>';
        echo '<li>Set <strong>Social discovery mode</strong> to <strong>URL discovery only</strong>.</li>';
        echo '<li>Create a Google Programmable Search Engine that includes web-wide search.</li>';
        echo '<li>Generate Google Programmable Search API key.</li>';
        echo '<li>Paste API key into <strong>Google Programmable Search API key</strong>.</li>';
        echo '<li>Paste search engine ID into <strong>Google Programmable Search Engine ID (cx)</strong>.</li>';
        echo '<li>Save settings and queue a run.</li>';
        echo '</ol>';
        echo '<p><strong>Compliance:</strong> This mode only stores discovered public profile URLs and confidence scores. It does not scrape LinkedIn or automate social platform actions.</p>';
        echo '</div>';

        echo '<p><strong>Ownership:</strong> This software is proprietary and owned by 5N2 Digital.</p>';
        echo '<p><strong>Email template shortcodes:</strong> {first_name}, {full_name}, {user_email}, {username}, {new_password}, {reset_link}, {revoke_link}, {company}, {phone}, {site_name}, {site_url}, {current_time}</p>';

        $this->render_wrap_end();
    }
}
