<?php

if (!defined('ABSPATH')) {
    exit;
}

class LC_Frontend
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

    private function __construct()
    {
        add_shortcode('lc_frontend_console', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_post_nopriv_lc_frontend_login', [$this, 'handle_login']);
        add_action('admin_post_lc_frontend_login', [$this, 'handle_login']);
        add_action('admin_post_lc_frontend_accept_gdpr', [$this, 'handle_accept_gdpr']);
        add_action('admin_post_lc_frontend_add_lead', [$this, 'handle_add_lead']);
        add_action('admin_post_lc_frontend_update_status', [$this, 'handle_update_status']);
        add_action('admin_post_lc_frontend_queue_run', [$this, 'handle_queue_run']);
    }

    public function register_assets()
    {
        wp_register_style('lc-frontend', LC_PLUGIN_URL . 'assets/frontend.css', [], LC_PLUGIN_VERSION);
        wp_register_script('lc-frontend', LC_PLUGIN_URL . 'assets/frontend.js', [], LC_PLUGIN_VERSION, true);
    }

    public function render_shortcode()
    {
        wp_enqueue_style('lc-frontend');
        wp_enqueue_script('lc-frontend');

        $user_id = get_current_user_id();
        $gdpr_accepted = $user_id ? (bool) get_user_meta($user_id, 'lc_gdpr_accepted', true) : false;

        ob_start();
        echo '<section class="lc-fe" data-user-id="' . esc_attr((string) $user_id) . '" data-onboarded="' . ($gdpr_accepted ? '1' : '0') . '">';
        echo '<div class="lc-fe-bg"></div>';
        echo '<div class="lc-fe-shell">';

        $this->render_message();

        if (!is_user_logged_in()) {
            $this->render_login_form();
        } elseif (!$this->is_allowed_user_email()) {
            echo '<div class="lc-fe-card"><h2>Access Restricted</h2><p>This console is restricted to authorized accounts.</p></div>';
        } elseif (!$gdpr_accepted) {
            $this->render_gdpr_gate();
        } else {
            $this->render_console();
        }

        echo '</div></section>';
        return ob_get_clean();
    }

    private function render_message()
    {
        $code = sanitize_text_field($_GET['lc_msg'] ?? '');
        if (!$code) {
            return;
        }

        $messages = [
            'login_failed' => 'Login failed. Please check your username and password.',
            'gdpr_required' => 'Please accept the GDPR notice to continue.',
            'gdpr_saved' => 'GDPR consent recorded.',
            'lead_added' => 'Lead added successfully.',
            'run_queued' => 'Run queued successfully.',
            'status_updated' => 'Lead status updated.',
            'not_allowed' => 'You are not authorized to perform that action.',
        ];

        if (isset($messages[$code])) {
            echo '<div class="lc-fe-alert">' . esc_html($messages[$code]) . '</div>';
        }
    }

    private function render_login_form()
    {
        echo '<div class="lc-fe-card lc-fe-login">';
        echo '<img class="lc-logo" src="https://5n2digital.com/wp-content/uploads/2024/01/Untitled-1-1.png" alt="5N2 Digital logo" />';
        echo '<p class="lc-badge">5N2 DIGITAL SOFTWARE</p>';
        echo '<h2>Welcome to the 5N2 Lead Console</h2>';
        echo '<p>Secure frontend workspace for lead management and discovery operations.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form">';
        wp_nonce_field('lc_frontend_login');
        echo '<input type="hidden" name="action" value="lc_frontend_login" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>Username<input type="text" name="log" required /></label>';
        echo '<label>Password<input type="password" name="pwd" required /></label>';
        echo '<label class="lc-check"><input type="checkbox" name="accept_gdpr" value="1" required /> I agree to GDPR-compliant processing for authorized business operations.</label>';
        echo '<button type="submit">Sign In</button>';
        echo '</form>';
        echo '</div>';
    }

    private function render_gdpr_gate()
    {
        echo '<div class="lc-fe-card">';
        echo '<h2>GDPR Confirmation Required</h2>';
        echo '<p>Before using the console, confirm that you understand data privacy obligations and lawful processing requirements.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form">';
        wp_nonce_field('lc_frontend_accept_gdpr');
        echo '<input type="hidden" name="action" value="lc_frontend_accept_gdpr" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label class="lc-check"><input type="checkbox" name="accept_gdpr" value="1" required /> I accept GDPR obligations and will only process data for legitimate approved purposes.</label>';
        echo '<button type="submit">Continue</button>';
        echo '</form>';
        echo '</div>';
    }

    private function render_console()
    {
        global $wpdb;

        $leads_table = $this->table('lc_leads');
        $runs_table = $this->table('lc_runs');

        $total_leads = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table}");
        $ready_leads = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE status='Ready'");
        $won_leads = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE status='Won'");
        $queued_runs = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$runs_table} WHERE status='queued'");

        $lead_rows = $wpdb->get_results("SELECT id,business_name,city,category,phone,email,score,lead_type,status FROM {$leads_table} ORDER BY id DESC LIMIT 20");
        $run_rows = $wpdb->get_results("SELECT id,query_text,city,max_places,status,created_at FROM {$runs_table} ORDER BY id DESC LIMIT 10");

        $settings = $this->settings();
        $user = wp_get_current_user();

        echo '<header class="lc-fe-hero">';
        echo '<div>';
        echo '<img class="lc-logo lc-logo-hero" src="https://5n2digital.com/wp-content/uploads/2024/01/Untitled-1-1.png" alt="5N2 Digital logo" />';
        echo '<p class="lc-badge">// FRONTEND CONSOLE</p>';
        echo '<h1>Hello, ' . esc_html($user->display_name ?: $user->user_login) . '</h1>';
        echo '<p class="lc-hero-sub">w e  b r i d g e  c r e a t i v i t y  a n d  s t r u c t u r e  i n t o  a c t i o n a b l e  s y s t e m s.</p>';
        echo '<p>Welcome to your frontend lead operations console.</p>';
        echo '</div>';
        echo '<div class="lc-fe-actions">';
        echo '<button type="button" class="lc-open-tutorial">Start Tutorial</button>';
        echo '<a href="' . esc_url(wp_logout_url($this->current_url())) . '">Log Out</a>';
        echo '</div>';
        echo '</header>';

        echo '<div class="lc-fe-metrics">';
        $this->metric('Total Leads', $total_leads);
        $this->metric('Ready Leads', $ready_leads);
        $this->metric('Won Leads', $won_leads);
        $this->metric('Queued Runs', $queued_runs);
        echo '</div>';

        echo '<div class="lc-fe-grid">';
        echo '<article class="lc-fe-card">';
        echo '<p class="lc-card-kicker">// LEADS</p>';
        echo '<h3>Add Lead</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form">';
        wp_nonce_field('lc_frontend_add_lead');
        echo '<input type="hidden" name="action" value="lc_frontend_add_lead" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>Business Name<input type="text" name="business_name" required /></label>';
        echo '<label>City<input type="text" name="city" /></label>';
        echo '<label>Category<input type="text" name="category" /></label>';
        echo '<label>Website<input type="url" name="website" /></label>';
        echo '<label>Phone<input type="text" name="phone" /></label>';
        echo '<label>Email<input type="email" name="email" /></label>';
        echo '<button type="submit">Save Lead</button>';
        echo '</form>';
        echo '</article>';

        echo '<article class="lc-fe-card">';
        echo '<p class="lc-card-kicker">// RUNS</p>';
        echo '<h3>Queue Discovery Run</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form">';
        wp_nonce_field('lc_frontend_queue_run');
        echo '<input type="hidden" name="action" value="lc_frontend_queue_run" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>Search Query<input type="text" name="query_text" required /></label>';
        echo '<label>City<input type="text" name="city" required /></label>';
        echo '<label>Max Places<input type="number" min="1" max="' . esc_attr((string) $settings['max_places_per_run']) . '" name="max_places" /></label>';
        echo '<button type="submit">Queue Run</button>';
        echo '</form>';
        echo '</article>';
        echo '</div>';

        echo '<div class="lc-fe-card">';
        echo '<p class="lc-card-kicker">// PIPELINE</p>';
        echo '<h3>Recent Leads</h3>';
        echo '<table><thead><tr><th>Business</th><th>Contact</th><th>Score</th><th>Status</th><th>Update</th></tr></thead><tbody>';
        foreach ($lead_rows as $row) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($row->business_name) . '</strong><br/><small>' . esc_html($row->city) . ' | ' . esc_html($row->category) . '</small></td>';
            echo '<td>' . esc_html($row->phone ?: '-') . '<br/>' . esc_html($row->email ?: '-') . '</td>';
            echo '<td>' . esc_html((string) $row->score) . ' / ' . esc_html($row->lead_type) . '</td>';
            echo '<td>' . esc_html($row->status) . '</td>';
            echo '<td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
            wp_nonce_field('lc_frontend_update_status');
            echo '<input type="hidden" name="action" value="lc_frontend_update_status" />';
            echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
            echo '<input type="hidden" name="lead_id" value="' . esc_attr((string) $row->id) . '" />';
            echo '<select name="status">';
            foreach ($this->statuses as $status) {
                echo '<option value="' . esc_attr($status) . '" ' . selected($row->status, $status, false) . '>' . esc_html($status) . '</option>';
            }
            echo '</select><button type="submit">Save</button></form></td>';
            echo '</tr>';
        }
        if (empty($lead_rows)) {
            echo '<tr><td colspan="5">No leads available yet.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';

        echo '<div class="lc-fe-card">';
        echo '<p class="lc-card-kicker">// DISCOVERY HEALTH</p>';
        echo '<h3>Recent Runs</h3>';
        echo '<table><thead><tr><th>ID</th><th>Query</th><th>City</th><th>Status</th><th>Created</th></tr></thead><tbody>';
        foreach ($run_rows as $run) {
            echo '<tr><td>' . esc_html((string) $run->id) . '</td><td>' . esc_html($run->query_text) . '</td><td>' . esc_html($run->city) . '</td><td>' . esc_html($run->status) . '</td><td>' . esc_html($run->created_at) . '</td></tr>';
        }
        if (empty($run_rows)) {
            echo '<tr><td colspan="5">No runs queued yet.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';

        $this->tutorial_markup();
    }

    private function tutorial_markup()
    {
        echo '<div class="lc-tutorial" hidden aria-hidden="true">';
        echo '<div class="lc-tutorial-panel">';
        echo '<button type="button" class="lc-tutorial-close" aria-label="Close tutorial">x</button>';
        echo '<h2>Quick Tutorial</h2>';
        echo '<p class="lc-step-copy"></p>';
        echo '<label class="lc-check"><input type="checkbox" class="lc-step-check" /> I understand this step.</label>';
        echo '<div class="lc-tutorial-progress"></div>';
        echo '<div class="lc-tutorial-nav">';
        echo '<button type="button" class="lc-prev-step">Back</button>';
        echo '<button type="button" class="lc-next-step">Next</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function metric($label, $value)
    {
        echo '<article><p>' . esc_html($label) . '</p><strong>' . esc_html((string) $value) . '</strong></article>';
    }

    private function ensure_frontend_user()
    {
        if (!is_user_logged_in() || !$this->is_allowed_user_email()) {
            $this->redirect_with_msg('not_allowed');
        }
    }

    private function allowed_email()
    {
        return defined('LC_PRIMARY_ADMIN_EMAIL') ? strtolower((string) LC_PRIMARY_ADMIN_EMAIL) : 'allen.bonagua@gmail.com';
    }

    private function is_allowed_user_email($user = null)
    {
        $user = $user ?: wp_get_current_user();
        $email = strtolower((string) ($user->user_email ?? ''));
        return $email === $this->allowed_email();
    }

    public function handle_login()
    {
        check_admin_referer('lc_frontend_login');

        if (empty($_POST['accept_gdpr'])) {
            $this->redirect_with_msg('gdpr_required');
        }

        $creds = [
            'user_login' => sanitize_text_field($_POST['log'] ?? ''),
            'user_password' => (string) ($_POST['pwd'] ?? ''),
            'remember' => true,
        ];

        $user = wp_signon($creds, is_ssl());
        if (is_wp_error($user)) {
            $this->redirect_with_msg('login_failed');
        }

        if (!$this->is_allowed_user_email($user)) {
            wp_logout();
            $this->redirect_with_msg('not_allowed');
        }

        update_user_meta((int) $user->ID, 'lc_gdpr_accepted', 1);
        update_user_meta((int) $user->ID, 'lc_gdpr_accepted_at', current_time('mysql'));

        $this->redirect_with_msg('gdpr_saved');
    }

    public function handle_accept_gdpr()
    {
        $this->ensure_frontend_user();
        check_admin_referer('lc_frontend_accept_gdpr');

        if (empty($_POST['accept_gdpr'])) {
            $this->redirect_with_msg('gdpr_required');
        }

        update_user_meta(get_current_user_id(), 'lc_gdpr_accepted', 1);
        update_user_meta(get_current_user_id(), 'lc_gdpr_accepted_at', current_time('mysql'));
        $this->redirect_with_msg('gdpr_saved');
    }

    public function handle_add_lead()
    {
        $this->ensure_frontend_user();
        check_admin_referer('lc_frontend_add_lead');

        global $wpdb;

        $website = esc_url_raw($_POST['website'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if ($this->is_suppressed($email, $phone, $website, $_POST['business_name'] ?? '')) {
            $this->redirect_with_msg('not_allowed');
        }

        $score = $this->compute_score($website, $phone, $email, 0, 0);

        $wpdb->insert($this->table('lc_leads'), [
            'business_name' => sanitize_text_field($_POST['business_name'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'website' => $website,
            'phone' => $phone,
            'email' => $email,
            'status' => 'New',
            'score' => $score,
            'lead_type' => $this->compute_lead_type($website, $score),
        ]);

        $this->redirect_with_msg('lead_added');
    }

    public function handle_update_status()
    {
        $this->ensure_frontend_user();
        check_admin_referer('lc_frontend_update_status');

        global $wpdb;
        $wpdb->update(
            $this->table('lc_leads'),
            ['status' => $this->normalize_status($_POST['status'] ?? 'New')],
            ['id' => absint($_POST['lead_id'] ?? 0)]
        );

        $this->redirect_with_msg('status_updated');
    }

    public function handle_queue_run()
    {
        $this->ensure_frontend_user();
        check_admin_referer('lc_frontend_queue_run');

        global $wpdb;
        $settings = $this->settings();
        $requested_max = absint($_POST['max_places'] ?? 0);
        $max_places = min(max(1, $requested_max ?: (int) $settings['max_places_per_run']), (int) $settings['max_places_per_run']);

        $wpdb->insert($this->table('lc_runs'), [
            'query_text' => sanitize_text_field($_POST['query_text'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'max_places' => $max_places,
            'status' => 'queued',
        ]);

        $this->redirect_with_msg('run_queued');
    }

    private function table($name)
    {
        global $wpdb;
        return $wpdb->prefix . $name;
    }

    private function settings()
    {
        $defaults = [
            'max_places_per_run' => 25,
        ];
        return wp_parse_args(get_option('lc_settings', []), $defaults);
    }

    private function normalize_status($status)
    {
        $status = sanitize_text_field($status);
        return in_array($status, $this->statuses, true) ? $status : 'New';
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
        if ((int) $review_count > 0) {
            $score += 10;
        }
        if ((float) $rating >= 4) {
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

    private function is_suppressed($email, $phone, $website, $name)
    {
        global $wpdb;

        $checks = [
            'email' => sanitize_email($email),
            'phone' => sanitize_text_field($phone),
            'domain' => $this->normalize_domain($website),
            'name' => sanitize_text_field($name),
        ];

        foreach ($checks as $type => $value) {
            if (!$value) {
                continue;
            }
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->table('lc_suppression')} WHERE type = %s AND value = %s LIMIT 1", $type, $value));
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

    private function redirect_with_msg($msg)
    {
        $target = $this->redirect_target();
        $target = add_query_arg('lc_msg', $msg, $target);
        wp_safe_redirect($target);
        exit;
    }

    private function redirect_target()
    {
        $raw = esc_url_raw($_POST['redirect_to'] ?? '');
        if ($raw) {
            return $raw;
        }
        return $this->current_url();
    }

    private function current_url()
    {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'] ?? ''));
        $uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        return $scheme . $host . $uri;
    }
}
