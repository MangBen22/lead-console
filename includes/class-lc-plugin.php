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
            max_places INT DEFAULT 25,
            status VARCHAR(20) DEFAULT 'queued',
            started_at DATETIME NULL,
            finished_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
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
        dbDelta($sql_logs);
        dbDelta($sql_suppression);
        dbDelta($sql_profiles);
        dbDelta($sql_system_logs);

        if (!wp_next_scheduled('lc_process_run')) {
            wp_schedule_event(time(), 'hourly', 'lc_process_run');
        }

        add_option('lc_settings', [
            'domain_fragment' => '',
            'max_places_per_run' => 25,
            'enable_live_api_calls' => 0,
            'google_places_api_key' => '',
            'discovery_mode' => 'hybrid',
            'directory_sources' => '',
            'social_discovery_mode' => 'off',
            'google_cse_api_key' => '',
            'google_cse_cx' => '',
        ]);
        update_option('lc_schema_version', '4');
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('lc_process_run');
    }

    private function __construct()
    {
        add_action('init', [$this, 'maybe_upgrade_schema']);
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
        add_action('admin_post_lc_frontend_save_settings', [$this, 'handle_frontend_save_settings']);
        add_action('lc_log_event', [$this, 'handle_external_log_event'], 10, 4);
        add_action('shutdown', [$this, 'capture_shutdown_errors']);
        add_action('lc_process_run', [$this, 'process_run_queue']);
    }

    public function register_admin_menu()
    {
        if (!$this->is_allowed_admin_user()) {
            return;
        }

        $capability = 'manage_options';

        add_menu_page('Lead Console', 'Lead Console', $capability, 'lc_dashboard', [$this, 'render_dashboard'], 'dashicons-chart-line', 30);
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

        return [
            'domain_fragment' => sanitize_text_field($settings['domain_fragment'] ?? ''),
            'max_places_per_run' => max(1, absint($settings['max_places_per_run'] ?? 25)),
            'enable_live_api_calls' => !empty($settings['enable_live_api_calls']) ? 1 : 0,
            'google_places_api_key' => sanitize_text_field($settings['google_places_api_key'] ?? ''),
            'discovery_mode' => $mode,
            'directory_sources' => sanitize_textarea_field($settings['directory_sources'] ?? ''),
            'social_discovery_mode' => $social_mode,
            'google_cse_api_key' => sanitize_text_field($settings['google_cse_api_key'] ?? ''),
            'google_cse_cx' => sanitize_text_field($settings['google_cse_cx'] ?? ''),
        ];
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

        if (is_admin()) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }

    private function get_primary_admin_email()
    {
        return defined('LC_PRIMARY_ADMIN_EMAIL') ? strtolower((string) LC_PRIMARY_ADMIN_EMAIL) : 'allen.bonagua@gmail.com';
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
        if ($version === '4') {
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

        update_option('lc_schema_version', '4');
    }

    private function get_settings()
    {
        $defaults = [
            'domain_fragment' => '',
            'max_places_per_run' => 25,
            'enable_live_api_calls' => 0,
            'google_places_api_key' => '',
            'discovery_mode' => 'hybrid',
            'directory_sources' => '',
            'social_discovery_mode' => 'off',
            'google_cse_api_key' => '',
            'google_cse_cx' => '',
        ];

        return wp_parse_args(get_option('lc_settings', []), $defaults);
    }

    private function render_wrap_start($title)
    {
        echo '<div class="wrap lc-wrap">';
        echo '<h1><span class="lc-brand">5N2 Lead Console</span> - ' . esc_html($title) . '</h1>';
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
            'max_places' => min(max(1, $requested_max ?: (int) $settings['max_places_per_run']), (int) $settings['max_places_per_run']),
            'status' => 'queued',
        ]);

        $this->log_system_event('runs', 'info', 'Run queued from admin.', [
            'query_text' => sanitize_text_field($_POST['query_text'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
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
            'message' => 'Run started in ' . $mode . ' mode. Query: ' . $run->query_text,
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
        $wpdb->insert($logs_table, [
            'run_id' => $run->id,
            'level' => 'info',
            'message' => sprintf('%s Leads created: %d. Social profiles updated: %d.', $summary, $created, $social_updated),
        ]);

        $wpdb->update($runs_table, [
            'status' => 'completed',
            'finished_at' => current_time('mysql'),
        ], ['id' => $run->id]);

        $this->log_system_event('runs', 'info', 'Run processing completed.', [
            'run_id' => (int) $run->id,
            'leads_created' => (int) $created,
            'social_updated' => (int) $social_updated,
        ]);
    }

    private function discover_with_google_places_api($run, $settings, $logs_table)
    {
        global $wpdb;

        $api_key = $settings['google_places_api_key'] ?? '';
        $max_places = max(1, (int) $run->max_places);
        $query = trim($run->query_text . ' ' . $run->city);

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

            $inserted = $this->insert_discovered_lead([
                'business_name' => $name,
                'city' => sanitize_text_field($run->city),
                'category' => sanitize_text_field($run->query_text),
                'address' => $address,
                'website' => $website,
                'phone' => $phone,
                'email' => $email,
                'review_count' => $review_count,
                'rating' => $rating,
                'source_url' => 'https://maps.google.com/?q=' . rawurlencode($name . ' ' . $run->city),
                'notes' => 'Imported from Google Places API',
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

        foreach ($sources as $source) {
            if ($added >= $max_places) {
                break;
            }

            $search_url = str_replace(
                ['{query}', '{city}'],
                [rawurlencode($run->query_text), rawurlencode($run->city)],
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
                    'business_name' => $name,
                    'city' => sanitize_text_field($run->city),
                    'category' => sanitize_text_field($run->query_text),
                    'address' => '',
                    'website' => '',
                    'phone' => '',
                    'email' => '',
                    'review_count' => 0,
                    'rating' => 0,
                    'source_url' => esc_url_raw($search_url),
                    'notes' => sprintf('Imported via directory fallback: %s (quality:%d)', $source['name'], (int) $source['quality_score']),
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

        $inserted = $wpdb->insert($this->db_table('lc_leads'), [
            'business_name' => $name,
            'city' => sanitize_text_field($data['city'] ?? ''),
            'category' => sanitize_text_field($data['category'] ?? ''),
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
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        ]);

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
                "SELECT id, business_name, city, category, linkedin_url, facebook_url, instagram_url, x_url, youtube_url
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
            if (empty($social['data'])) {
                continue;
            }

            $update_data = $social['data'];
            $update_data['social_confidence'] = $social['confidence'];
            $update_data['social_source'] = 'google_cse';

            $did_update = $wpdb->update($table, $update_data, ['id' => (int) $lead->id]);
            if ($did_update !== false) {
                $updated++;
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
        echo '<input type="number" min="1" max="' . esc_attr((string) $settings['max_places_per_run']) . '" name="max_places" placeholder="Max places" />';
        submit_button('Queue Run', 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';

        echo '<h2>Run Queue</h2>';
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Query</th><th>City</th><th>Max</th><th>Status</th><th>Created</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $row->id) . '</td>';
            echo '<td>' . esc_html($row->query_text) . '</td>';
            echo '<td>' . esc_html($row->city) . '</td>';
            echo '<td>' . esc_html((string) $row->max_places) . '</td>';
            echo '<td>' . esc_html($row->status) . '</td>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '</tr>';
        }

        if (empty($rows)) {
            echo '<tr><td colspan="6">No runs queued yet.</td></tr>';
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

        $subject = '5N2 Lead Console Password Reset by Admin';
        $message = "Your password was reset by an administrator.\n\n";
        $message .= "Username: {$user->user_login}\n";
        $message .= "Temporary/New Password: {$new_password}\n\n";
        $message .= "Please log in and change it immediately.";
        wp_mail($user->user_email, $subject, $message);

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

    public function handle_frontend_save_settings()
    {
        $this->ensure_permissions();
        check_admin_referer('lc_frontend_save_settings');

        $input = $_POST['lc_settings'] ?? [];
        if (!is_array($input)) {
            $input = [];
        }
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
        echo '<p>Manual password reset and account lock/unlock are available only to primary admin (' . esc_html($this->get_primary_admin_email()) . ').</p>';
        echo '</div>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Photo</th><th>User</th><th>Role</th><th>Status</th><th>Manual Password Reset</th><th>Lock Controls</th></tr></thead><tbody>';

        foreach ($users as $user) {
            $roles = implode(', ', array_map('sanitize_text_field', $user->roles));
            $locked = !empty(get_user_meta($user->ID, 'lc_locked', true));
            $is_primary = strtolower((string) $user->user_email) === $this->get_primary_admin_email();

            echo '<tr>';
            echo '<td>' . get_avatar($user->ID, 48) . '</td>';
            echo '<td><strong>' . esc_html($user->display_name ?: $user->user_login) . '</strong><br/>';
            echo '<small>Username: ' . esc_html($user->user_login) . '</small><br/>';
            echo '<small>Email: ' . esc_html($user->user_email) . '</small></td>';
            echo '<td>' . esc_html($roles ?: 'subscriber') . '</td>';
            echo '<td>' . ($locked ? '<span style="color:#b10000;font-weight:600;">Locked</span>' : '<span style="color:#0a7a0a;font-weight:600;">Active</span>') . ($is_primary ? '<br/><small>Primary Admin</small>' : '') . '</td>';

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
            echo '<tr><td colspan="6">No users found.</td></tr>';
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

    public function render_settings()
    {
        $settings = $this->get_settings();

        $this->render_wrap_start('Settings');

        echo '<form method="post" action="options.php" class="lc-card">';
        settings_fields('lc_settings_group');

        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label for="lc_domain_fragment">Domain fragment</label></th><td><input id="lc_domain_fragment" type="text" name="lc_settings[domain_fragment]" value="' . esc_attr($settings['domain_fragment']) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="lc_max_places">Max places per run</label></th><td><input id="lc_max_places" type="number" min="1" name="lc_settings[max_places_per_run]" value="' . esc_attr((string) $settings['max_places_per_run']) . '" /></td></tr>';
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

        $this->render_wrap_end();
    }
}
