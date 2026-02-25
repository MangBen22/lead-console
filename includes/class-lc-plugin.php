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

        dbDelta($sql_leads);
        dbDelta($sql_runs);
        dbDelta($sql_logs);
        dbDelta($sql_suppression);

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
        ]);
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('lc_process_run');
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_lc_add_lead', [$this, 'handle_add_lead']);
        add_action('admin_post_lc_update_lead_status', [$this, 'handle_update_lead_status']);
        add_action('admin_post_lc_import_csv', [$this, 'handle_import_csv']);
        add_action('admin_post_lc_queue_run', [$this, 'handle_queue_run']);
        add_action('admin_post_lc_add_suppression', [$this, 'handle_add_suppression']);
        add_action('admin_post_lc_export_ready', [$this, 'handle_export_ready']);
        add_action('lc_process_run', [$this, 'process_run_queue']);
    }

    public function register_admin_menu()
    {
        $capability = 'manage_options';

        add_menu_page('Lead Console', 'Lead Console', $capability, 'lc_dashboard', [$this, 'render_dashboard'], 'dashicons-chart-line', 30);
        add_submenu_page('lc_dashboard', 'Dashboard', 'Dashboard', $capability, 'lc_dashboard', [$this, 'render_dashboard']);
        add_submenu_page('lc_dashboard', 'Leads', 'Leads', $capability, 'lc_leads', [$this, 'render_leads']);
        add_submenu_page('lc_dashboard', 'Runs', 'Runs', $capability, 'lc_runs', [$this, 'render_runs']);
        add_submenu_page('lc_dashboard', 'Duplicates', 'Duplicates', $capability, 'lc_duplicates', [$this, 'render_duplicates']);
        add_submenu_page('lc_dashboard', 'Exports', 'Exports', $capability, 'lc_exports', [$this, 'render_exports']);
        add_submenu_page('lc_dashboard', 'Suppression', 'Suppression', $capability, 'lc_suppression', [$this, 'render_suppression']);
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

        return [
            'domain_fragment' => sanitize_text_field($settings['domain_fragment'] ?? ''),
            'max_places_per_run' => max(1, absint($settings['max_places_per_run'] ?? 25)),
            'enable_live_api_calls' => !empty($settings['enable_live_api_calls']) ? 1 : 0,
            'google_places_api_key' => sanitize_text_field($settings['google_places_api_key'] ?? ''),
            'discovery_mode' => $mode,
            'directory_sources' => sanitize_textarea_field($settings['directory_sources'] ?? ''),
        ];
    }

    private function ensure_permissions()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }
    }

    private function db_table($name)
    {
        global $wpdb;
        return $wpdb->prefix . $name;
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

        wp_safe_redirect(admin_url('admin.php?page=lc_suppression&message=added'));
        exit;
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

        $summary = $used_api ? 'Google API + fallback processing complete.' : 'Fallback directory processing complete.';
        $wpdb->insert($logs_table, [
            'run_id' => $run->id,
            'level' => 'info',
            'message' => sprintf('%s Leads created: %d.', $summary, $created),
        ]);

        $wpdb->update($runs_table, [
            'status' => 'completed',
            'finished_at' => current_time('mysql'),
        ], ['id' => $run->id]);
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
        echo '</tbody></table>';

        submit_button('Save Settings');
        echo '</form>';

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

        echo '<p><strong>Ownership:</strong> This software is proprietary and owned by 5N2 Digital.</p>';

        $this->render_wrap_end();
    }
}
