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
        add_action('admin_post_nopriv_lc_frontend_forgot_password', [$this, 'handle_forgot_password']);
        add_action('admin_post_lc_frontend_forgot_password', [$this, 'handle_forgot_password']);
        add_action('admin_post_nopriv_lc_frontend_register', [$this, 'handle_register']);
        add_action('admin_post_lc_frontend_register', [$this, 'handle_register']);
        add_action('admin_post_nopriv_lc_frontend_reset_password', [$this, 'handle_reset_password']);
        add_action('admin_post_lc_frontend_reset_password', [$this, 'handle_reset_password']);
        add_action('admin_post_nopriv_lc_frontend_revoke_password_change', [$this, 'handle_revoke_password_change']);
        add_action('admin_post_lc_frontend_revoke_password_change', [$this, 'handle_revoke_password_change']);
        add_action('admin_post_lc_frontend_accept_gdpr', [$this, 'handle_accept_gdpr']);
        add_action('admin_post_lc_frontend_update_profile_photo', [$this, 'handle_update_profile_photo']);
        add_action('admin_post_lc_frontend_import_leads', [$this, 'handle_import_leads']);
        add_action('admin_post_lc_frontend_add_lead', [$this, 'handle_add_lead']);
        add_action('admin_post_lc_frontend_update_status', [$this, 'handle_update_status']);
        add_action('admin_post_lc_frontend_queue_run', [$this, 'handle_queue_run']);
        add_action('wp_ajax_lc_frontend_run_status', [$this, 'handle_ajax_run_status']);
        add_action('wp_ajax_lc_frontend_run_save_drafts', [$this, 'handle_ajax_run_save_drafts']);
        add_action('wp_ajax_lc_frontend_run_discard_drafts', [$this, 'handle_ajax_run_discard_drafts']);
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
        wp_localize_script('lc-frontend', 'lcFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'runNonce' => wp_create_nonce('lc_frontend_run_monitor'),
        ]);

        $user_id = get_current_user_id();
        $gdpr_accepted = $user_id ? (bool) get_user_meta($user_id, 'lc_gdpr_accepted', true) : false;

        ob_start();
        echo '<section class="lc-fe" data-user-id="' . esc_attr((string) $user_id) . '" data-onboarded="' . ($gdpr_accepted ? '1' : '0') . '" data-run-id="' . esc_attr((string) absint($_GET['lc_run_id'] ?? 0)) . '">';
        echo '<div class="lc-fe-bg"></div>';
        echo '<div class="lc-fe-shell">';

        $this->render_message();

        if ($this->has_reset_query()) {
            $this->render_reset_password_form();
        } elseif (!is_user_logged_in()) {
            $this->render_login_form();
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
            'user_locked' => 'This user is temporarily locked. Please contact the super admin.',
            'gdpr_required' => 'Please accept the GDPR notice to continue.',
            'settings_saved' => 'Settings saved successfully.',
            'user_reset_done' => 'User password reset completed.',
            'user_lock_done' => 'User locked successfully.',
            'user_unlock_done' => 'User unlocked successfully.',
            'user_profile_saved' => 'User profile updated successfully.',
            'user_created' => 'User created successfully.',
            'user_action_error' => 'User action failed.',
            'reset_email_not_found' => 'Email not found in user database.',
            'reset_sent' => 'Password reset email sent. The link is valid for 5 minutes.',
            'reset_link_invalid' => 'Reset link is invalid or expired.',
            'reset_success' => 'Password updated successfully. Please log in with your new password.',
            'reset_revoke_success' => 'Password change revoked. Account is now locked pending super admin reset.',
            'register_exists' => 'A user with that email already exists.',
            'register_password_short' => 'Password must be at least 8 characters.',
            'register_request_sent' => 'Registration request submitted. Please wait for admin approval.',
            'approval_pending' => 'Your account is pending admin approval.',
            'approval_rejected' => 'Your registration has been declined. Please contact admin.',
            'photo_updated' => 'Profile photo updated successfully.',
            'photo_invalid' => 'Please upload a valid image file for your profile photo.',
            'import_success' => 'Leads imported successfully.',
            'import_partial' => 'Import completed with some skipped rows.',
            'import_error' => 'Import failed. File could not be parsed or rows were incomplete.',
            'lead_added' => 'Lead added successfully.',
            'run_queued' => 'Run queued successfully.',
            'run_compliance_blocked' => 'Run blocked by automatic compliance review. Check Settings checklist and API configuration.',
            'run_saved' => 'Captured run leads were saved to the leads database.',
            'run_discarded' => 'Captured run leads were discarded.',
            'status_updated' => 'Lead status updated.',
            'not_allowed' => 'You are not authorized to perform that action.',
        ];

        if (isset($messages[$code])) {
            $detail = '';
            if (in_array($code, ['import_success', 'import_partial', 'import_error'], true)) {
                $imported = absint($_GET['lc_imported'] ?? 0);
                $failed = absint($_GET['lc_failed'] ?? 0);
                if ($imported > 0 || $failed > 0) {
                    $detail = ' Imported: ' . $imported . '. Skipped: ' . $failed . '.';
                }
            }
            echo '<div class="lc-fe-alert">' . esc_html($messages[$code] . $detail) . '</div>';
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
        echo '<label>Password<div class="lc-password-row"><input class="lc-password-input" type="password" name="pwd" required /><button type="button" class="lc-toggle-password" aria-label="Show password">Show</button></div></label>';
        echo '<label class="lc-check"><input type="checkbox" name="accept_gdpr" value="1" required /> I agree to GDPR-compliant processing for authorized business operations.</label>';
        echo '<button type="submit">Sign In</button>';
        echo '</form>';
        echo '<div class="lc-auth-switches">';
        echo '<button type="button" class="lc-auth-toggle" data-target="forgot">Forgot Password</button>';
        echo '<button type="button" class="lc-auth-toggle" data-target="register">Register</button>';
        echo '</div>';
        echo '<div class="lc-auth-panel" data-auth-panel="forgot" hidden>';
        echo '<hr class="lc-divider" />';
        echo '<h3>Forgot Password</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form">';
        wp_nonce_field('lc_frontend_forgot_password');
        echo '<input type="hidden" name="action" value="lc_frontend_forgot_password" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>Email<input type="email" name="email" required /></label>';
        echo '<button type="submit">Send Reset Link</button>';
        echo '</form>';
        echo '</div>';
        echo '<div class="lc-auth-panel" data-auth-panel="register" hidden>';
        echo '<hr class="lc-divider" />';
        echo '<h3>Register User</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form">';
        wp_nonce_field('lc_frontend_register');
        echo '<input type="hidden" name="action" value="lc_frontend_register" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>First Name<input type="text" name="first_name" required /></label>';
        echo '<label>Last Name<input type="text" name="last_name" required /></label>';
        echo '<label>Email<input type="email" name="email" required /></label>';
        echo '<label>Phone<input type="text" name="phone" /></label>';
        echo '<label>Company<input type="text" name="company" /></label>';
        echo '<label>Password<div class="lc-password-row"><input class="lc-password-input" type="password" name="password" minlength="8" required /><button type="button" class="lc-toggle-password" aria-label="Show password">Show</button></div></label>';
        echo '<button type="submit">Submit for Approval</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }

    private function render_reset_password_form()
    {
        $selector = sanitize_text_field($_GET['selector'] ?? '');
        $token = sanitize_text_field($_GET['token'] ?? '');

        if (!$this->is_valid_timed_token('lc_reset_token_', $selector, $token)) {
            echo '<div class="lc-fe-card lc-fe-login">';
            echo '<h2>Reset Link Expired</h2>';
            echo '<p>This reset link is invalid or older than 5 minutes. Please request a new one.</p>';
            echo '</div>';
            return;
        }

        echo '<div class="lc-fe-card lc-fe-login">';
        echo '<img class="lc-logo" src="https://5n2digital.com/wp-content/uploads/2024/01/Untitled-1-1.png" alt="5N2 Digital logo" />';
        echo '<p class="lc-badge">5N2 DIGITAL SOFTWARE</p>';
        echo '<h2>Reset Password</h2>';
        echo '<p>This reset link is valid for 5 minutes.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form">';
        wp_nonce_field('lc_frontend_reset_password');
        echo '<input type="hidden" name="action" value="lc_frontend_reset_password" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url(remove_query_arg(['lc_reset', 'selector', 'token'], $this->current_url())) . '" />';
        echo '<input type="hidden" name="selector" value="' . esc_attr($selector) . '" />';
        echo '<input type="hidden" name="token" value="' . esc_attr($token) . '" />';
        echo '<label>New Password<div class="lc-password-row"><input class="lc-password-input" type="password" name="new_password" minlength="8" required /><button type="button" class="lc-toggle-password" aria-label="Show password">Show</button></div></label>';
        echo '<button type="submit">Update Password</button>';
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

        $leads_per_page = absint($_GET['leads_per_page'] ?? 20);
        if ($leads_per_page < 10 || $leads_per_page > 100 || ($leads_per_page % 10) !== 0) {
            $leads_per_page = 20;
        }
        $leads_page = max(1, absint($_GET['leads_page'] ?? 1));
        $lead_total_pages = max(1, (int) ceil($total_leads / max(1, $leads_per_page)));
        if ($leads_page > $lead_total_pages) {
            $leads_page = $lead_total_pages;
        }
        $lead_offset = ($leads_page - 1) * $leads_per_page;
        $lead_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id,business_name,city,category,phone,email,score,lead_type,status,notes FROM {$leads_table} ORDER BY id DESC LIMIT %d OFFSET %d",
            $leads_per_page,
            $lead_offset
        ));
        $run_rows = $wpdb->get_results("SELECT id,query_text,city,state,country,radius_miles,niche,services,website_focus,min_rating,min_reviews,max_places,captured_leads,status,created_at FROM {$runs_table} ORDER BY id DESC LIMIT 10");
        $run_locations = $wpdb->get_results("SELECT DISTINCT country, city FROM {$runs_table} WHERE city <> '' ORDER BY country ASC, city ASC LIMIT 800");
        $fallback_city_map = $this->load_city_dataset();
        $location_hierarchy = $this->load_location_hierarchy();
        $run_city_map = [];
        foreach ($run_locations as $location_row) {
            $country_key = trim((string) ($location_row->country ?? ''));
            $city_name = trim((string) ($location_row->city ?? ''));
            if ($city_name === '') {
                continue;
            }
            if (!isset($run_city_map[$country_key])) {
                $run_city_map[$country_key] = [];
            }
            if (!in_array($city_name, $run_city_map[$country_key], true)) {
                $run_city_map[$country_key][] = $city_name;
            }
        }
        $country_names = array_unique(array_filter(array_merge(array_keys($location_hierarchy), array_keys($fallback_city_map), array_keys($run_city_map)), static function ($value) {
            return trim((string) $value) !== '';
        }));
        natcasesort($country_names);

        $settings = $this->settings();
        $user = wp_get_current_user();
        $first_name = $this->first_name($user);
        $full_name = $this->full_name($user);
        $is_primary_admin = $this->is_primary_admin($user);

        echo '<header class="lc-fe-hero">';
        echo '<div>';
        echo '<img class="lc-logo lc-logo-hero" src="https://5n2digital.com/wp-content/uploads/2024/01/Untitled-1-1.png" alt="5N2 Digital logo" />';
        echo '<h1>Hello, ' . esc_html($first_name) . '</h1>';
        echo '<p class="lc-hero-sub">We bridge creativity and structure into actionable systems.</p>';
        echo '<p>Welcome to 5N2 Digital Lead Console.</p>';
        echo '</div>';
        echo '<div class="lc-fe-actions">';
        echo '<div class="lc-account-chip">';
        echo $this->avatar_html((int) $user->ID, 42, $full_name);
        echo '<div class="lc-account-meta"><strong>' . esc_html($full_name) . '</strong></div>';
        echo '</div>';
        echo '<button type="button" class="lc-open-tutorial lc-btn-tutorial">Start Tutorial</button>';
        echo '<a href="' . esc_url(wp_logout_url($this->current_url())) . '">Log Out</a>';
        echo '</div>';
        echo '</header>';

        echo '<div class="lc-app-layout">';
        echo '<aside class="lc-side-tabs" aria-label="Console tabs">';
        echo '<button type="button" class="lc-tab-btn is-active" data-tab="dashboard">Dashboard</button>';
        echo '<button type="button" class="lc-tab-btn" data-tab="leads">Leads</button>';
        echo '<button type="button" class="lc-tab-btn" data-tab="knowledge">Knowledge Base</button>';
        echo '<button type="button" class="lc-tab-btn" data-tab="settings">Settings</button>';
        echo '</aside>';
        echo '<div class="lc-tab-content">';
        echo '<section class="lc-tab-panel is-active" data-tab="dashboard">';

        echo '<div class="lc-fe-metrics">';
        $this->metric('Total Leads', $total_leads);
        $this->metric('Ready Leads', $ready_leads);
        $this->metric('Won Leads', $won_leads);
        $this->metric('Queued Runs', $queued_runs);
        echo '</div>';

        echo '<div class="lc-runs-wrap">';
        echo '<article class="lc-fe-card" id="lc-section-runs">';
        echo '<p class="lc-card-kicker">// RUNS</p>';
        echo '<h3>Queue Discovery Run</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form lc-run-form">';
        wp_nonce_field('lc_frontend_queue_run');
        echo '<input type="hidden" name="action" value="lc_frontend_queue_run" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label class="lc-col-4">Search Query<input type="text" name="query_text" required /></label>';
        echo '<label class="lc-col-3">City<div class="lc-city-autocomplete"><input type="text" name="city" placeholder="Type city name (optional)" autocomplete="off" /><div class="lc-city-suggest-box" hidden></div></div></label>';
        echo '<label class="lc-col-2">State/Province<select name="state"><option value="">Select state/province (optional)</option></select></label>';
        echo '<label class="lc-col-3">Country<select name="country"><option value="">Select country</option>';
        foreach ($country_names as $country_name) {
            echo '<option value="' . esc_attr((string) $country_name) . '">' . esc_html((string) $country_name) . '</option>';
        }
        echo '</select></label>';
        echo '<label class="lc-col-2">Max Places<input type="number" min="1" max="' . esc_attr((string) $settings['max_places_per_run']) . '" name="max_places" /></label>';
        echo '<details class="lc-run-advanced-wrap lc-col-12">';
        echo '<summary><span>Advanced Options</span></summary>';
        echo '<div class="lc-run-advanced">';
        echo '<label class="lc-col-3">Radius (miles)<input type="number" min="0" name="radius_miles" placeholder="0 = city only" /></label>';
        echo '<label class="lc-col-6">Service Focus<input type="text" name="niche" placeholder="Cosmetic Dentistry" /></label>';
        echo '<label class="lc-col-12">Services<textarea name="services" rows="2" placeholder="implants, whitening, emergency"></textarea></label>';
        echo '<label class="lc-col-4">Website Focus<select name="website_focus"><option value="any">Any</option><option value="no_website">No Website Listed</option><option value="has_website">Has Website</option></select></label>';
        echo '<label class="lc-col-4">Minimum Rating<input type="number" min="0" max="5" step="0.1" name="min_rating" placeholder="0 to 5" /></label>';
        echo '<label class="lc-col-4">Minimum Reviews<input type="number" min="0" name="min_reviews" placeholder="0+" /></label>';
        echo '</div>';
        echo '</details>';
        echo '<button type="submit" class="lc-col-12">Queue Run</button>';
        echo '<div class="lc-run-inline-note lc-run-city-country-note" hidden>Selecting a country with the city improves targeting and search quality.</div>';
        echo '<div class="lc-run-inline-note lc-run-country-scope-note" hidden>Country-only search enabled. The system will run this with broader country coverage. Choose a state/province for better precision.</div>';
        echo '<div class="lc-run-live" hidden>';
        echo '<p class="lc-card-kicker">// LIVE RUN ACTIVITY</p>';
        echo '<div class="lc-run-live-status">Waiting for updates...</div>';
        echo '<pre class="lc-run-live-log" aria-live="polite"></pre>';
        echo '</div>';
        echo '<script type="application/json" class="lc-run-city-map-data">' . wp_json_encode($run_city_map) . '</script>';
        echo '<script type="application/json" class="lc-run-city-fallback-data">' . wp_json_encode($fallback_city_map) . '</script>';
        echo '<script type="application/json" class="lc-run-location-hierarchy-data">' . wp_json_encode($location_hierarchy) . '</script>';
        echo '</form>';
        echo '</article>';
        echo '</div>';

        echo '<div class="lc-fe-card">';
        echo '<p class="lc-card-kicker">// DISCOVERY HEALTH</p>';
        echo '<h3>Recent Runs</h3>';
        echo '<table><thead><tr><th>ID</th><th>Query</th><th>Location</th><th>Service Focus/Services</th><th>Filters</th><th>Captured</th><th>Status</th><th>Created</th></tr></thead><tbody>';
        foreach ($run_rows as $run) {
            $location = trim((string) $run->city);
            if (!empty($run->state)) {
                $location = trim($location . ', ' . (string) $run->state, ', ');
            }
            if (!empty($run->country)) {
                $location = trim($location . ', ' . (string) $run->country, ', ');
            }
            if ((int) ($run->radius_miles ?? 0) > 0) {
                $location .= ' within ' . (int) $run->radius_miles . ' miles';
            }
            if ($location === '') {
                $location = 'Unspecified';
            }
            echo '<tr><td>' . esc_html((string) $run->id) . '</td><td>' . esc_html($run->query_text) . '</td><td>' . esc_html($location) . '</td><td>' . esc_html((string) ($run->niche ?: '-')) . '<br/><small>' . esc_html((string) ($run->services ?: '-')) . '</small></td><td>Rating >= ' . esc_html(number_format((float) ($run->min_rating ?? 0), 1)) . '<br/><small>Reviews >= ' . esc_html((string) ($run->min_reviews ?? 0)) . '</small><br/><small>Website: ' . esc_html(ucwords(str_replace('_', ' ', (string) ($run->website_focus ?: 'any')))) . '</small></td><td>' . esc_html((string) absint($run->captured_leads ?? 0)) . '</td><td>' . esc_html($run->status) . '</td><td>' . esc_html($run->created_at) . '</td></tr>';
        }
        if (empty($run_rows)) {
            echo '<tr><td colspan="8">No runs queued yet.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';

        echo '</section>';
        echo '<section class="lc-tab-panel" data-tab="leads">';
        echo '<div class="lc-fe-grid lc-leads-io" id="lc-section-leads">';
        echo '<article class="lc-fe-card">';
        echo '<p class="lc-card-kicker">// EXPORT</p>';
        echo '<h3>Lead Export</h3>';
        echo '<p>Download outreach CSV for Ready and Verified leads.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
        wp_nonce_field('lc_export_ready');
        echo '<input type="hidden" name="action" value="lc_export_ready" />';
        echo '<button type="submit">Download CSV</button>';
        echo '</form>';
        echo '</article>';
        echo '<article class="lc-fe-card">';
        echo '<p class="lc-card-kicker">// IMPORT</p>';
        echo '<h3>Lead Import</h3>';
        echo '<p>Import CSV, TSV, TXT, JSON, or XLSX. The system maps fields automatically and skips unreadable rows.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form" enctype="multipart/form-data">';
        wp_nonce_field('lc_frontend_import_leads');
        echo '<input type="hidden" name="action" value="lc_frontend_import_leads" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>Import File<input type="file" name="import_file" accept=".csv,.tsv,.txt,.json,.xlsx" required /></label>';
        echo '<button type="submit">Import Leads</button>';
        echo '</form>';
        echo '<p><small>Supported fields: business_name, city, category, address, website, phone, email, rating, review_count, status, notes, source_url.</small></p>';
        echo '</article>';
        echo '</div>';
        echo '<div class="lc-fe-card">';
        echo '<h3>Recent Leads</h3>';
        echo '<table><thead><tr><th>Business</th><th>Contact</th><th>Score</th><th>Notes</th><th>Status</th><th>Update</th></tr></thead><tbody>';
        foreach ($lead_rows as $row) {
            $note_text = '';
            if (isset($row->notes)) {
                $note_text = (string) $row->notes;
            }
            echo '<tr>';
            echo '<td><strong>' . esc_html($row->business_name) . '</strong><br/><small>' . esc_html($row->city) . ' | ' . esc_html($row->category) . '</small></td>';
            echo '<td>' . esc_html($row->phone ?: '-') . '<br/>' . esc_html($row->email ?: '-') . '</td>';
            echo '<td>' . esc_html((string) $row->score) . ' / ' . esc_html($row->lead_type) . '</td>';
            echo '<td><small>' . esc_html($note_text !== '' ? substr($note_text, 0, 220) : '-') . '</small></td>';
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
            echo '<tr><td colspan="6">No leads available yet.</td></tr>';
        }
        echo '</tbody></table>';
        $base_recent_leads_url = remove_query_arg(['leads_page', 'leads_per_page', 'lc_tab'], $this->current_url());
        $prev_page = max(1, $leads_page - 1);
        $next_page = min($lead_total_pages, $leads_page + 1);
        $range_start = $total_leads > 0 ? ($lead_offset + 1) : 0;
        $range_end = min($lead_offset + $leads_per_page, $total_leads);
        echo '<div class="lc-recent-leads-controls">';
        echo '<form method="get" class="lc-inline-form">';
        echo '<input type="hidden" name="lc_tab" value="leads" />';
        echo '<input type="hidden" name="leads_page" value="1" />';
        echo '<label class="lc-inline-label">Show';
        echo '<select name="leads_per_page">';
        for ($n = 10; $n <= 100; $n += 10) {
            echo '<option value="' . esc_attr((string) $n) . '" ' . selected($leads_per_page, $n, false) . '>' . esc_html((string) $n) . '</option>';
        }
        echo '</select>';
        echo '</label>';
        echo '<button type="submit">Apply</button>';
        echo '</form>';
        echo '<p class="lc-inline-meta">Showing ' . esc_html((string) $range_start) . '-' . esc_html((string) $range_end) . ' of ' . esc_html((string) $total_leads) . '</p>';
        echo '<div class="lc-inline-pager">';
        if ($leads_page > 1) {
            echo '<a class="lc-btn" href="' . esc_url(add_query_arg(['lc_tab' => 'leads', 'leads_page' => $prev_page, 'leads_per_page' => $leads_per_page], $base_recent_leads_url)) . '">Prev</a>';
        }
        echo '<span>Page ' . esc_html((string) $leads_page) . ' / ' . esc_html((string) $lead_total_pages) . '</span>';
        if ($leads_page < $lead_total_pages) {
            echo '<a class="lc-btn" href="' . esc_url(add_query_arg(['lc_tab' => 'leads', 'leads_page' => $next_page, 'leads_per_page' => $leads_per_page], $base_recent_leads_url)) . '">Next</a>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
        $this->render_knowledge_base_panel();
        $this->render_admin_console_sections($settings);
        echo '</div>';
        echo '</div>';

        $this->tutorial_markup();
        $this->run_review_modal_markup();
    }

    private function run_review_modal_markup()
    {
        echo '<div class="lc-run-review-modal" hidden aria-hidden="true">';
        echo '<div class="lc-run-review-panel">';
        echo '<div class="lc-run-review-head"><h3>Run Result Review</h3><button type="button" class="lc-run-review-close" aria-label="Close">x</button></div>';
        echo '<p class="lc-run-review-sub">Review captured leads before saving to the database.</p>';
        echo '<div class="lc-run-review-body"></div>';
        echo '<div class="lc-run-review-actions">';
        echo '<button type="button" class="lc-run-save-leads">Save Leads</button>';
        echo '<button type="button" class="lc-run-rerun">Run Again</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function render_knowledge_base_panel()
    {
        $kb = $this->build_knowledge_base_payload();
        echo '<section class="lc-tab-panel" data-tab="knowledge">';
        echo '<div class="lc-fe-card" id="lc-section-knowledge">';
        echo '<p class="lc-card-kicker">// KNOWLEDGE BASE</p>';
        echo '<h3>Program Knowledge Base</h3>';
        echo '<div class="lc-kb-shell">';
        echo '<div class="lc-kb-search-wrap">';
        echo '<input type="text" class="lc-kb-search-input" placeholder="Search Dashboard, Leads, Settings, Glossary..." autocomplete="off" />';
        echo '<div class="lc-kb-search-suggest" hidden></div>';
        echo '</div>';
        echo '<div class="lc-kb-layout">';
        echo '<aside class="lc-kb-nav"></aside>';
        echo '<article class="lc-kb-content"></article>';
        echo '</div>';
        echo '<script type="application/json" class="lc-kb-data">' . wp_json_encode($kb) . '</script>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }

    private function build_knowledge_base_payload()
    {
        $sections = [
            [
                'id' => 'dashboard',
                'title' => 'Dashboard',
                'intro' => 'Everything in this section explains the Dashboard tab items: metrics, run queue fields, live stream, and discovery table.',
                'items' => [
                    ['name' => 'Search Query', 'type' => 'field', 'what' => 'Main keyword phrase used for discovery.', 'does' => 'Controls which business type is searched (for example dentist, plumber, salon).', 'example' => 'Dentist', 'outcome' => 'Run targets businesses related to dentist in selected location.', 'keywords' => ['search query', 'query', 'keyword', 'run']],
                    ['name' => 'City', 'type' => 'field', 'what' => 'City target input with suggestion support.', 'does' => 'Narrows search to specific city area.', 'example' => 'San Diego', 'outcome' => 'Results focus on San Diego listings.', 'keywords' => ['city', 'location', 'target']],
                    ['name' => 'State/Province', 'type' => 'field', 'what' => 'State/province filter linked to selected country.', 'does' => 'Improves precision before city-level targeting.', 'example' => 'California', 'outcome' => 'City suggestions and run scope are aligned to California.', 'keywords' => ['state', 'province', 'location']],
                    ['name' => 'Country', 'type' => 'field', 'what' => 'Country selector for regional targeting.', 'does' => 'Defines regional scope and affects state/city lists.', 'example' => 'United States', 'outcome' => 'Search limited to United States context.', 'keywords' => ['country', 'region']],
                    ['name' => 'Max Places', 'type' => 'field', 'what' => 'Maximum number of lead captures per run.', 'does' => 'Sets upper limit of records gathered by a run.', 'example' => '50', 'outcome' => 'Run stops adding new captures after 50.', 'keywords' => ['max places', 'limit', 'capture']],
                    ['name' => 'Advanced Options Button', 'type' => 'button', 'what' => 'Expands advanced filters.', 'does' => 'Shows radius, service focus, services, website focus, and quality thresholds.', 'example' => 'Click Advanced Options', 'outcome' => 'Additional filters become visible for tuning.', 'keywords' => ['advanced', 'filters']],
                    ['name' => 'Radius (miles)', 'type' => 'field', 'what' => 'Distance around location center.', 'does' => 'Extends search area beyond the exact city.', 'example' => '15', 'outcome' => 'Search includes nearby areas within 15 miles.', 'keywords' => ['radius', 'distance']],
                    ['name' => 'Service Focus', 'type' => 'field', 'what' => 'Business niche detail.', 'does' => 'Refines captures within broader query category.', 'example' => 'Cosmetic Dentistry', 'outcome' => 'Results prioritize cosmetic dentistry signals.', 'keywords' => ['service focus', 'niche']],
                    ['name' => 'Services', 'type' => 'field', 'what' => 'Comma-separated service list.', 'does' => 'Adds context to run and scoring analysis.', 'example' => 'implants, whitening, emergency', 'outcome' => 'Notes and relevance become more specific to listed services.', 'keywords' => ['services', 'comma', 'context']],
                    ['name' => 'Website Focus', 'type' => 'field', 'what' => 'Website presence filter.', 'does' => 'Includes any / no-website / has-website only businesses.', 'example' => 'No Website Listed', 'outcome' => 'Run filters businesses with no listed site.', 'keywords' => ['website focus', 'filter']],
                    ['name' => 'Minimum Rating', 'type' => 'field', 'what' => 'Lowest accepted rating.', 'does' => 'Rejects captures below this threshold.', 'example' => '4.2', 'outcome' => 'Only 4.2+ rating candidates remain.', 'keywords' => ['rating', 'minimum']],
                    ['name' => 'Minimum Reviews', 'type' => 'field', 'what' => 'Lowest accepted review count.', 'does' => 'Prevents low-signal listings from being captured.', 'example' => '20', 'outcome' => 'Only listings with 20+ reviews are included.', 'keywords' => ['reviews', 'minimum']],
                    ['name' => 'Queue Run', 'type' => 'button', 'what' => 'Starts a new discovery run.', 'does' => 'Saves run config and sends it to queue processing.', 'example' => 'Click Queue Run', 'outcome' => 'Run appears in Discovery Health and starts processing.', 'keywords' => ['queue run', 'start', 'submit']],
                    ['name' => 'Live Run Activity', 'type' => 'panel', 'what' => 'Real-time run progress stream.', 'does' => 'Shows step-by-step processing logs while waiting.', 'example' => 'Watch activity after queue', 'outcome' => 'User sees ongoing processing details, then panel auto-hides.', 'keywords' => ['live activity', 'logs', 'progress']],
                    ['name' => 'Run Review Popup', 'type' => 'modal', 'what' => 'Preview window for captured draft leads.', 'does' => 'Lets user save to leads table or rerun with edits.', 'example' => 'Review results and click Save Leads', 'outcome' => 'Draft captures become permanent leads only after approval.', 'keywords' => ['review popup', 'save leads', 'rerun']],
                    ['name' => 'Discovery Health Table', 'type' => 'table', 'what' => 'Recent runs status board.', 'does' => 'Displays query, location, filters, captured count, status, and created date.', 'example' => 'Check captured count for run #25', 'outcome' => 'User can compare performance across recent runs.', 'keywords' => ['discovery health', 'captured', 'status']],
                ],
            ],
            [
                'id' => 'leads',
                'title' => 'Leads',
                'intro' => 'This section covers each control in Leads tab: export, import, table actions, and pagination.',
                'items' => [
                    ['name' => 'Download CSV', 'type' => 'button', 'what' => 'Exports outreach-ready lead file.', 'does' => 'Generates CSV from ready/verified records.', 'example' => 'Click Download CSV', 'outcome' => 'CSV file downloads to browser.', 'keywords' => ['download csv', 'export', 'file']],
                    ['name' => 'Import File', 'type' => 'field', 'what' => 'Upload field for lead imports.', 'does' => 'Accepts csv/tsv/txt/json/xlsx and maps fields automatically.', 'example' => 'Upload leads.xlsx', 'outcome' => 'Rows are parsed and inserted/skipped based on validation.', 'keywords' => ['import', 'upload', 'xlsx', 'csv']],
                    ['name' => 'Import Leads', 'type' => 'button', 'what' => 'Submits selected import file.', 'does' => 'Runs import parser and reports imported/skipped counts.', 'example' => 'Click Import Leads', 'outcome' => 'Alert confirms import result.', 'keywords' => ['import leads', 'submit']],
                    ['name' => 'Recent Leads Table', 'type' => 'table', 'what' => 'Primary lead list viewer.', 'does' => 'Shows business details, contact, score/type, notes, and current status.', 'example' => 'Scan top 100 recent leads', 'outcome' => 'Team can review and update leads quickly.', 'keywords' => ['recent leads', 'table', 'list']],
                    ['name' => 'Status Dropdown', 'type' => 'field', 'what' => 'Per-row status selector.', 'does' => 'Changes lead stage in pipeline.', 'example' => 'Set status to Ready', 'outcome' => 'Lead moves to Ready stage immediately.', 'keywords' => ['status', 'pipeline', 'update']],
                    ['name' => 'Save (Row)', 'type' => 'button', 'what' => 'Commits selected status update.', 'does' => 'Writes selected status for that lead.', 'example' => 'Choose Won then click Save', 'outcome' => 'Lead status updates to Won.', 'keywords' => ['save row', 'status save']],
                    ['name' => 'Show (10-100)', 'type' => 'field', 'what' => 'Rows-per-page control in 10-step increments.', 'does' => 'Adjusts number of leads displayed per page.', 'example' => 'Show 100', 'outcome' => 'Table displays up to 100 leads per page.', 'keywords' => ['show', 'pagination', 'rows per page']],
                    ['name' => 'Apply', 'type' => 'button', 'what' => 'Applies selected rows-per-page value.', 'does' => 'Reloads leads table with selected page size.', 'example' => 'Set 50 and click Apply', 'outcome' => 'Page refreshes showing 50 leads.', 'keywords' => ['apply', 'rows']],
                    ['name' => 'Prev / Next', 'type' => 'button', 'what' => 'Pagination controls in Recent Leads footer.', 'does' => 'Moves between pages based on selected page size.', 'example' => 'Click Next', 'outcome' => 'Next lead set loads while staying on Leads tab.', 'keywords' => ['prev', 'next', 'page']],
                    ['name' => 'Showing X-Y of Z', 'type' => 'label', 'what' => 'Current table range summary.', 'does' => 'Shows current window and total lead count.', 'example' => 'Showing 1-50 of 420', 'outcome' => 'User knows exact progress through lead list.', 'keywords' => ['showing', 'count', 'range']],
                ],
            ],
            [
                'id' => 'settings',
                'title' => 'Settings',
                'intro' => 'Complete guide for all settings controls including system, users, logs, suppression, intelligence, and reports.',
                'items' => [
                    ['name' => 'Domain Fragment', 'type' => 'field', 'what' => 'Optional domain hint used for filtering context.', 'does' => 'Helps align captures with target domain patterns.', 'example' => 'dental', 'outcome' => 'Discovery and scoring use a clearer domain context.', 'keywords' => ['domain fragment', 'domain']],
                    ['name' => 'Max Places Per Run', 'type' => 'field', 'what' => 'Global hard cap for run capture volume.', 'does' => 'Prevents oversized runs and controls cost/load.', 'example' => '100', 'outcome' => 'No run can capture beyond 100 records.', 'keywords' => ['max places', 'global limit']],
                    ['name' => 'Enable Live API Calls', 'type' => 'toggle', 'what' => 'Master switch for external API use.', 'does' => 'Turns live API discovery on/off.', 'example' => 'Enable', 'outcome' => 'Google Places calls are allowed when key exists.', 'keywords' => ['live api', 'toggle']],
                    ['name' => 'Google Places API Key', 'type' => 'field', 'what' => 'Credential for Places API queries.', 'does' => 'Authenticates live discovery requests. Full setup is documented in API Setup Reference below.', 'example' => 'AIza...key', 'outcome' => 'Runs can fetch live Google Places results.', 'keywords' => ['google places', 'api key']],
                    ['name' => 'Discovery Mode', 'type' => 'field', 'what' => 'Source strategy selector.', 'does' => 'Sets hybrid, Google-only, or directory-only behavior.', 'example' => 'Hybrid', 'outcome' => 'Run tries API first, then fallback when needed.', 'keywords' => ['discovery mode', 'hybrid']],
                    ['name' => 'Social Discovery Mode', 'type' => 'field', 'what' => 'Social profile enrichment mode.', 'does' => 'Controls whether social URL discovery is off or enabled.', 'example' => 'URL discovery only', 'outcome' => 'System enriches social URLs without direct scraping.', 'keywords' => ['social', 'url discovery']],
                    ['name' => 'Google CSE API Key', 'type' => 'field', 'what' => 'Custom Search API key for social URL discovery.', 'does' => 'Authenticates programmable search requests.', 'example' => 'AIza...cse', 'outcome' => 'Social URL lookups can execute.', 'keywords' => ['cse key', 'search api']],
                    ['name' => 'Google CSE cx', 'type' => 'field', 'what' => 'Custom Search Engine ID.', 'does' => 'Defines configured search engine scope for enrichment.', 'example' => '12345:abcde', 'outcome' => 'Search requests run against correct engine.', 'keywords' => ['cx', 'custom search engine']],
                    [
                        'name' => 'API Setup Reference',
                        'type' => 'guide',
                        'what' => 'Complete setup checklist for all APIs used by the system.',
                        'does' => 'Provides exact steps to generate, capture, secure, and connect Google Places + Programmable Search credentials.',
                        'example' => 'Follow every step then save keys in System Settings.',
                        'outcome' => 'Discovery and enrichment APIs connect cleanly with fewer setup errors.',
                        'steps' => [
                            'Google Places API: Open Google Cloud Console and create/select a project.',
                            'Enable Places API from API Library.',
                            'Create API key in Credentials.',
                            'Restrict key to Places API and your allowed usage context (referrer/IP).',
                            'Paste key into Google Places API key field and Save Settings.',
                            'Programmable Search: Create search engine and copy cx value.',
                            'Enable Custom Search API in Google Cloud project.',
                            'Create API key for Custom Search and apply key restrictions.',
                            'Paste Google CSE API key + cx into settings and save.',
                            'Set Social Discovery Mode based on your enrichment policy.',
                        ],
                        'links' => [
                            ['label' => 'Google Cloud Console', 'url' => 'https://console.cloud.google.com/'],
                            ['label' => 'Google API Library', 'url' => 'https://console.cloud.google.com/apis/library'],
                            ['label' => 'Google Credentials', 'url' => 'https://console.cloud.google.com/apis/credentials'],
                            ['label' => 'Programmable Search Engine', 'url' => 'https://programmablesearchengine.google.com/'],
                            ['label' => 'Custom Search API (direct)', 'url' => 'https://console.cloud.google.com/apis/library/customsearch.googleapis.com'],
                        ],
                        'keywords' => ['api setup', 'google places', 'google cse', 'cx', 'credentials', 'step by step'],
                    ],
                    ['name' => 'Directory Sources', 'type' => 'field', 'what' => 'Approved fallback source checklist plus custom domain input.', 'does' => 'Lets you enable compliant preset directories and add custom domains that are compatibility-scanned before applying.', 'example' => 'Check Yelp + BBB, add: Example Directory|example.com', 'outcome' => 'Only compatible sources are saved and used by fallback discovery.', 'keywords' => ['directory sources', 'fallback', 'compatibility', 'custom domain']],
                    ['name' => 'Enable SMTP', 'type' => 'toggle', 'what' => 'Email transport switch.', 'does' => 'Routes system emails through configured SMTP server.', 'example' => 'Enable', 'outcome' => 'Password reset and notices use SMTP.', 'keywords' => ['smtp', 'email']],
                    ['name' => 'SMTP Host / Port / Encryption / Auth', 'type' => 'field', 'what' => 'SMTP connection settings.', 'does' => 'Defines server address, transport security, and auth behavior.', 'example' => 'smtp.mail.com / 587 / TLS / Auth On', 'outcome' => 'Reliable secure outbound email delivery.', 'keywords' => ['smtp host', 'port', 'tls', 'auth']],
                    ['name' => 'SMTP Username / Password', 'type' => 'field', 'what' => 'SMTP credentials.', 'does' => 'Authenticates mail sender account.', 'example' => 'noreply@company.com + app password', 'outcome' => 'Server accepts outgoing mail requests.', 'keywords' => ['smtp credentials', 'username', 'password']],
                    ['name' => 'SMTP From Email / Name', 'type' => 'field', 'what' => 'Default sender identity.', 'does' => 'Controls sender address/name shown to recipients.', 'example' => 'noreply@5n2digital.com / 5N2 Lead Console', 'outcome' => 'Emails look consistent and trusted.', 'keywords' => ['from email', 'from name']],
                    ['name' => 'Email Templates', 'type' => 'field', 'what' => 'All system notification subject/body templates.', 'does' => 'Customizes registration, reset, and alert messages.', 'example' => 'Reset Subject: Password Reset Requested', 'outcome' => 'Email copy matches your brand and policy language.', 'keywords' => ['email template', 'subject', 'body']],
                    ['name' => 'Run Checklist', 'type' => 'reference', 'what' => 'Compliance guidance block.', 'does' => 'Documents rules automatically enforced during run queueing.', 'example' => 'Review before launching campaigns', 'outcome' => 'Team follows compliant data capture practices.', 'keywords' => ['run checklist', 'compliance']],
                    ['name' => 'Users: Create User', 'type' => 'button', 'what' => 'Adds a new team user profile.', 'does' => 'Creates account with role/access level and metadata.', 'example' => 'Create manager-level user', 'outcome' => 'New user appears in management list.', 'keywords' => ['create user', 'access level']],
                    ['name' => 'Users: Approve / Reject', 'type' => 'button', 'what' => 'Registration approval controls.', 'does' => 'Approves pending registrations or rejects them.', 'example' => 'Approve pending registration', 'outcome' => 'User can sign in and operate based on access.', 'keywords' => ['approve', 'reject', 'registration']],
                    ['name' => 'Users: Reset / Lock / Unlock', 'type' => 'button', 'what' => 'Security controls for account maintenance.', 'does' => 'Forces password reset or account lock status changes.', 'example' => 'Lock suspicious account', 'outcome' => 'Account is prevented from login until unlocked.', 'keywords' => ['reset', 'lock', 'unlock']],
                    ['name' => 'Logs Table', 'type' => 'table', 'what' => 'System and user event records.', 'does' => 'Provides traceability for auth, runs, and operational actions.', 'example' => 'Check run warning logs', 'outcome' => 'Issues can be diagnosed quickly.', 'keywords' => ['logs', 'audit', 'events']],
                    ['name' => 'Suppression Management', 'type' => 'table', 'what' => 'Blocklist administration view.', 'does' => 'Adds/removes suppression entries by type and value.', 'example' => 'Add domain suppression', 'outcome' => 'Future captures skip blocked entries.', 'keywords' => ['suppression management', 'blocklist']],
                    ['name' => 'Lead Intelligence', 'type' => 'table', 'what' => 'Enrichment operation and profile scores.', 'does' => 'Runs enrichment and displays confidence/completeness outputs.', 'example' => 'Enrich 25 Recent Leads', 'outcome' => 'Profiles gain emails/social/context signals.', 'keywords' => ['intelligence', 'enrich', 'confidence']],
                    ['name' => 'Reports: Funnel and Run Health', 'type' => 'table', 'what' => 'Operational reporting views.', 'does' => 'Shows lead funnel distribution and run status totals.', 'example' => 'Open Reports section', 'outcome' => 'Team sees pipeline conversion and run performance snapshots.', 'keywords' => ['reports', 'funnel', 'run health']],
                    ['name' => 'Save Settings', 'type' => 'button', 'what' => 'Commits all settings changes.', 'does' => 'Writes updated settings values to plugin configuration.', 'example' => 'Update discovery and SMTP then Save Settings', 'outcome' => 'New settings apply to next runs and emails.', 'keywords' => ['save settings', 'apply']],
                ],
            ],
        ];

        $glossary_items = [];
        foreach ($sections as $section) {
            foreach ((array) ($section['items'] ?? []) as $item) {
                $term = sanitize_text_field((string) ($item['name'] ?? ''));
                if ($term === '') {
                    continue;
                }
                $glossary_items[] = [
                    'name' => $term,
                    'type' => 'glossary',
                    'what' => sanitize_text_field((string) ($item['what'] ?? '')),
                    'does' => sanitize_text_field((string) ($item['does'] ?? '')),
                    'example' => sanitize_text_field((string) ($item['example'] ?? '')),
                    'outcome' => sanitize_text_field((string) ($item['outcome'] ?? '')),
                    'keywords' => array_values(array_unique(array_filter(array_merge(
                        ['glossary', 'term', $term],
                        (array) ($item['keywords'] ?? [])
                    )))),
                ];
            }
        }

        $core_terms = [
            ['name' => 'Lead', 'what' => 'A business record used for outreach.', 'does' => 'Moves through status pipeline and scoring.', 'example' => 'Dental clinic record', 'outcome' => 'Can be contacted and tracked to won/lost.'],
            ['name' => 'Run', 'what' => 'Discovery job configuration and execution record.', 'does' => 'Captures candidate leads from configured sources.', 'example' => 'Query "Dentist" in California', 'outcome' => 'Returns captured lead candidates for review.'],
            ['name' => 'Draft Lead', 'what' => 'Temporary captured lead from run review mode.', 'does' => 'Waits for user confirmation before final save.', 'example' => 'Captured in review popup', 'outcome' => 'Saved to leads table or discarded.'],
            ['name' => 'Captured Leads', 'what' => 'Count of leads gathered by a run.', 'does' => 'Measures run productivity and tuning quality.', 'example' => 'Captured: 47', 'outcome' => 'Displayed in Discovery Health.'],
            ['name' => 'Review Status', 'what' => 'Run review result state.', 'does' => 'Tracks pending, saved, discarded, or n/a states.', 'example' => 'Pending', 'outcome' => 'Determines whether review popup should appear.'],
            ['name' => 'Suppression', 'what' => 'Do-not-capture filtering rule.', 'does' => 'Blocks matching emails, phones, domains, or names.', 'example' => 'Suppress domain example.com', 'outcome' => 'Matching captures are skipped.'],
            ['name' => 'Discovery Mode', 'what' => 'How sources are used during discovery.', 'does' => 'Controls hybrid/API-only/fallback-only execution.', 'example' => 'Hybrid', 'outcome' => 'API first, fallback when needed.'],
            ['name' => 'Lead Type', 'what' => 'Lead quality classification bucket.', 'does' => 'Marks lead class (A/B/C/E) from scoring logic.', 'example' => 'Type B', 'outcome' => 'Supports prioritization for outreach.'],
            ['name' => 'Readiness Score', 'what' => 'Calculated score for lead quality and contactability.', 'does' => 'Helps rank leads for immediate action.', 'example' => 'Score 78', 'outcome' => 'Higher score leads prioritized first.'],
            ['name' => 'Run Logs', 'what' => 'Time-ordered run progress messages.', 'does' => 'Shows what happened during queue processing.', 'example' => 'Run started in live mode', 'outcome' => 'Improves visibility and debugging.'],
        ];
        foreach ($core_terms as $term) {
            $glossary_items[] = array_merge($term, [
                'type' => 'glossary',
                'keywords' => ['glossary', 'definition', strtolower((string) $term['name'])],
            ]);
        }

        usort($glossary_items, static function ($a, $b) {
            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        $sections[] = [
            'id' => 'glossary',
            'title' => 'Glossary',
            'intro' => 'Reference dictionary for Dashboard, Leads, and Settings terms.',
            'items' => $glossary_items,
        ];

        foreach ($sections as &$section) {
            if (!isset($section['items']) || !is_array($section['items'])) {
                continue;
            }
            foreach ($section['items'] as &$item) {
                if (($section['id'] ?? '') !== 'glossary') {
                    $item['image'] = $this->kb_example_image_data_uri(
                        (string) ($item['name'] ?? 'Example'),
                        (string) ($item['outcome'] ?? '')
                    );
                } else {
                    $item['image'] = '';
                }
            }
            unset($item);
        }
        unset($section);

        return [
            'version' => LC_PLUGIN_VERSION,
            'generated_at' => current_time('mysql'),
            'sections' => $sections,
        ];
    }

    private function kb_example_image_data_uri($title, $outcome)
    {
        $title = sanitize_text_field($title);
        $outcome = sanitize_text_field($outcome);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="260" viewBox="0 0 900 260">'
            . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0%" stop-color="#111a2d"/><stop offset="100%" stop-color="#1e2c4d"/>'
            . '</linearGradient></defs>'
            . '<rect width="900" height="260" fill="url(#g)"/>'
            . '<rect x="18" y="18" width="864" height="224" rx="14" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.16)"/>'
            . '<text x="40" y="72" fill="#f5b031" font-size="24" font-family="Arial, sans-serif">Example Item</text>'
            . '<text x="40" y="112" fill="#f5f7ff" font-size="28" font-family="Arial, sans-serif">' . esc_html($title) . '</text>'
            . '<text x="40" y="162" fill="#c5d4f5" font-size="20" font-family="Arial, sans-serif">Outcome:</text>'
            . '<text x="130" y="162" fill="#e8f0ff" font-size="20" font-family="Arial, sans-serif">' . esc_html($outcome) . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    private function render_admin_console_sections($settings)
    {
        global $wpdb;

        $viewer = wp_get_current_user();
        $viewer_id = (int) $viewer->ID;
        $is_primary_admin = $this->is_primary_admin($viewer);
        $logs = $is_primary_admin
            ? $wpdb->get_results("SELECT id, level, category, message, created_at FROM {$this->table('lc_system_logs')} ORDER BY id DESC LIMIT 50")
            : $wpdb->get_results($wpdb->prepare("SELECT id, level, category, message, created_at FROM {$this->table('lc_system_logs')} WHERE user_id = %d ORDER BY id DESC LIMIT 50", $viewer_id));
        $users = get_users(['orderby' => 'registered', 'order' => 'DESC', 'number' => 100]);
        $suppression = $wpdb->get_results("SELECT * FROM {$this->table('lc_suppression')} ORDER BY id DESC LIMIT 50");
        $profiles = $wpdb->get_results("
            SELECT l.id, l.business_name, l.city, p.primary_email, p.completeness_score, p.confidence_score
            FROM {$this->table('lc_leads')} l
            LEFT JOIN {$this->table('lc_lead_profiles')} p ON p.lead_id = l.id
            ORDER BY l.id DESC
            LIMIT 50
        ");
        $status_rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$this->table('lc_leads')} GROUP BY status ORDER BY total DESC");
        $run_rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$this->table('lc_runs')} GROUP BY status ORDER BY total DESC");

        echo '<section class="lc-tab-panel" data-tab="settings"><div class="lc-fe-card lc-settings-nav-card" id="lc-section-settings-nav">';
        echo '<p class="lc-card-kicker">// SETTINGS MENU</p>';
        echo '<div class="lc-settings-nav" role="tablist" aria-label="Settings sections">';
        echo '<button type="button" class="lc-settings-btn is-active" data-settings-target="run-checklist">Run Checklist</button>';
        if ($is_primary_admin) {
            echo '<button type="button" class="lc-settings-btn" data-settings-target="system">System</button>';
        }
        echo '<button type="button" class="lc-settings-btn" data-settings-target="users">Users</button>';
        echo '<button type="button" class="lc-settings-btn" data-settings-target="logs">Logs</button>';
        if ($is_primary_admin) {
            echo '<button type="button" class="lc-settings-btn" data-settings-target="suppression">Suppression</button>';
            echo '<button type="button" class="lc-settings-btn" data-settings-target="intelligence">Intelligence</button>';
            echo '<button type="button" class="lc-settings-btn" data-settings-target="reports">Reports</button>';
        }
        echo '</div>';
        echo '</div></section>';

        echo '<section class="lc-tab-panel lc-settings-panel is-settings-active" data-tab="settings" data-settings-panel="run-checklist"><div class="lc-fe-card" id="lc-section-run-checklist">';
        echo '<p class="lc-card-kicker">// COMPLIANCE</p>';
        echo '<h3>Run Compliance Checklist</h3>';
        echo '<label class="lc-check"><input type="checkbox" checked disabled /> Use only lawful/public data sources and provider-approved APIs.</label>';
        echo '<label class="lc-check"><input type="checkbox" checked disabled /> Do not use prohibited scraping or unauthorized automation.</label>';
        echo '<label class="lc-check"><input type="checkbox" checked disabled /> Respect platform terms and privacy obligations (including GDPR and internal policy).</label>';
        echo '<label class="lc-check"><input type="checkbox" checked disabled /> Queue runs only for approved business workflows.</label>';
        echo '<p><small>The system automatically reviews this checklist on every queued run.</small></p>';
        echo '</div></section>';

        $directory_presets = LC_Plugin::directory_source_presets();
        $selected_directory_presets = [];
        if (isset($settings['directory_source_presets']) && is_array($settings['directory_source_presets'])) {
            $selected_directory_presets = array_values(array_map('sanitize_key', $settings['directory_source_presets']));
        } else {
            $selected_directory_presets = LC_Plugin::default_directory_source_preset_ids();
        }
        $selected_directory_map = array_fill_keys($selected_directory_presets, true);
        $directory_scan_report = get_option('lc_directory_source_scan_report', []);
        $directory_scan_rows = [];
        $directory_scan_updated = '';
        if (is_array($directory_scan_report)) {
            $directory_scan_rows = isset($directory_scan_report['rows']) && is_array($directory_scan_report['rows']) ? $directory_scan_report['rows'] : [];
            $directory_scan_updated = sanitize_text_field((string) ($directory_scan_report['updated_at'] ?? ''));
        }

        if ($is_primary_admin) {
        echo '<section class="lc-tab-panel lc-settings-panel" data-tab="settings" data-settings-panel="system"><div class="lc-fe-card" id="lc-section-settings">';
        echo '<p class="lc-card-kicker">// SETTINGS</p>';
        echo '<h3>System Settings</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form lc-settings-form">';
        wp_nonce_field('lc_frontend_save_settings');
        echo '<input type="hidden" name="action" value="lc_frontend_save_settings" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label class="lc-col-6">Domain fragment<input type="text" name="lc_settings[domain_fragment]" value="' . esc_attr((string) ($settings['domain_fragment'] ?? '')) . '" /></label>';
        echo '<label class="lc-col-3">Max places per run<input type="number" min="1" name="lc_settings[max_places_per_run]" value="' . esc_attr((string) ($settings['max_places_per_run'] ?? 25)) . '" /></label>';
        echo '<label class="lc-check lc-col-3"><input type="checkbox" name="lc_settings[enable_live_api_calls]" value="1" ' . checked(!empty($settings['enable_live_api_calls']), true, false) . ' /> Enable live API calls</label>';
        echo '<label class="lc-col-6">Google Places API key<input type="text" name="lc_settings[google_places_api_key]" value="' . esc_attr((string) ($settings['google_places_api_key'] ?? '')) . '" /></label>';
        echo '<label class="lc-col-3">Discovery mode<select name="lc_settings[discovery_mode]">';
        echo '<option value="hybrid" ' . selected($settings['discovery_mode'] ?? 'hybrid', 'hybrid', false) . '>Hybrid</option>';
        echo '<option value="google_only" ' . selected($settings['discovery_mode'] ?? 'hybrid', 'google_only', false) . '>Google only</option>';
        echo '<option value="directory_only" ' . selected($settings['discovery_mode'] ?? 'hybrid', 'directory_only', false) . '>Directory only</option>';
        echo '</select></label>';
        echo '<label class="lc-col-3">Social discovery mode<select name="lc_settings[social_discovery_mode]">';
        echo '<option value="off" ' . selected($settings['social_discovery_mode'] ?? 'off', 'off', false) . '>Off</option>';
        echo '<option value="url_discovery_only" ' . selected($settings['social_discovery_mode'] ?? 'off', 'url_discovery_only', false) . '>URL discovery only</option>';
        echo '<option value="official_api_enabled" ' . selected($settings['social_discovery_mode'] ?? 'off', 'official_api_enabled', false) . '>Official API enabled</option>';
        echo '</select></label>';
        echo '<label class="lc-col-6">Google CSE API key<input type="text" name="lc_settings[google_cse_api_key]" value="' . esc_attr((string) ($settings['google_cse_api_key'] ?? '')) . '" /></label>';
        echo '<label class="lc-col-6">Google CSE cx<input type="text" name="lc_settings[google_cse_cx]" value="' . esc_attr((string) ($settings['google_cse_cx'] ?? '')) . '" /></label>';
        echo '<div class="lc-col-12 lc-api-ref-mini">';
        echo '<button type="button" class="lc-api-ref-trigger">API Setup Reference</button>';
        echo '<div class="lc-api-ref-pop" hidden>';
        echo '<p>Need setup steps for API keys and cx? Open the full guide in Knowledge Base.</p>';
        echo '<button type="button" class="lc-api-ref-open-kb" data-kb-section="settings" data-kb-query="API Setup Reference">Open Full Guide</button>';
        echo '</div>';
        echo '</div>';
        echo '<div class="lc-col-12 lc-directory-source-card">';
        echo '<h4>Directory Sources</h4>';
        echo '<p>Select approved directory sources below. The system runs a compatibility scan before applying your changes.</p>';
        echo '<div class="lc-directory-source-grid">';
        foreach ($directory_presets as $preset) {
            $preset_id = sanitize_key((string) ($preset['id'] ?? ''));
            if ($preset_id === '') {
                continue;
            }
            $preset_host = wp_parse_url((string) ($preset['search_url'] ?? ''), PHP_URL_HOST);
            echo '<label class="lc-directory-source-item">';
            echo '<input type="checkbox" name="lc_settings[directory_source_presets][]" value="' . esc_attr($preset_id) . '" ' . checked(!empty($selected_directory_map[$preset_id]), true, false) . ' />';
            echo '<span><strong>' . esc_html((string) ($preset['name'] ?? $preset_id)) . '</strong>';
            echo '<small>' . esc_html((string) ($preset_host ?: '')) . ' | Quality ' . esc_html((string) absint($preset['quality_score'] ?? 0)) . '</small>';
            if (!empty($preset['compliance'])) {
                echo '<small>' . esc_html((string) $preset['compliance']) . '</small>';
            }
            echo '</span>';
            echo '</label>';
        }
        echo '</div>';
        echo '<label class="lc-col-12">Custom directory websites (one per line: Name|domain.com or domain.com)<textarea name="lc_settings[directory_custom_sites]" rows="4" placeholder="Example Directory|example.com&#10;another-example.com">' . esc_textarea((string) ($settings['directory_custom_sites'] ?? '')) . '</textarea></label>';
        echo '<p><small>Custom domains are verified before use. If a source fails compatibility checks, it will not be applied.</small></p>';
        if (!empty($directory_scan_rows)) {
            echo '<div class="lc-directory-source-report">';
            echo '<h5>Latest Compatibility Scan</h5>';
            if ($directory_scan_updated !== '') {
                echo '<p><small>Updated: ' . esc_html($directory_scan_updated) . '</small></p>';
            }
            echo '<table><thead><tr><th>Source</th><th>Type</th><th>Status</th><th>Details</th></tr></thead><tbody>';
            foreach ($directory_scan_rows as $scan_row) {
                $label = sanitize_text_field((string) ($scan_row['label'] ?? ''));
                $type = sanitize_text_field((string) ($scan_row['type'] ?? ''));
                $status = sanitize_text_field((string) ($scan_row['status'] ?? ''));
                $details = sanitize_text_field((string) ($scan_row['details'] ?? ''));
                echo '<tr>';
                echo '<td>' . esc_html($label) . '</td>';
                echo '<td>' . esc_html($type) . '</td>';
                echo '<td><span class="lc-scan-status lc-scan-status-' . esc_attr($status) . '">' . esc_html(ucfirst($status)) . '</span></td>';
                echo '<td>' . esc_html($details) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        }
        echo '</div>';
        echo '<label class="lc-check lc-col-3"><input type="checkbox" name="lc_settings[smtp_enabled]" value="1" ' . checked(!empty($settings['smtp_enabled']), true, false) . ' /> Enable SMTP</label>';
        echo '<label class="lc-col-5">SMTP Host<input type="text" name="lc_settings[smtp_host]" value="' . esc_attr((string) ($settings['smtp_host'] ?? '')) . '" /></label>';
        echo '<label class="lc-col-2">SMTP Port<input type="number" min="1" name="lc_settings[smtp_port]" value="' . esc_attr((string) ($settings['smtp_port'] ?? 587)) . '" /></label>';
        echo '<label class="lc-col-2">SMTP Encryption<select name="lc_settings[smtp_encryption]">';
        echo '<option value="tls" ' . selected($settings['smtp_encryption'] ?? 'tls', 'tls', false) . '>TLS</option>';
        echo '<option value="ssl" ' . selected($settings['smtp_encryption'] ?? 'tls', 'ssl', false) . '>SSL</option>';
        echo '<option value="none" ' . selected($settings['smtp_encryption'] ?? 'tls', 'none', false) . '>None</option>';
        echo '</select></label>';
        echo '<label class="lc-check lc-col-3"><input type="checkbox" name="lc_settings[smtp_auth]" value="1" ' . checked(!empty($settings['smtp_auth']), true, false) . ' /> Use SMTP auth</label>';
        echo '<label class="lc-col-3">SMTP Username<input type="text" name="lc_settings[smtp_username]" value="' . esc_attr((string) ($settings['smtp_username'] ?? '')) . '" /></label>';
        echo '<label class="lc-col-3">SMTP Password<input type="password" name="lc_settings[smtp_password]" value="' . esc_attr((string) ($settings['smtp_password'] ?? '')) . '" /></label>';
        echo '<label class="lc-col-3">SMTP From Email<input type="email" name="lc_settings[smtp_from_email]" value="' . esc_attr((string) ($settings['smtp_from_email'] ?? '')) . '" /></label>';
        echo '<label class="lc-col-3">SMTP From Name<input type="text" name="lc_settings[smtp_from_name]" value="' . esc_attr((string) ($settings['smtp_from_name'] ?? '')) . '" /></label>';
        echo '<label>Template: Reset Subject<input type="text" name="lc_settings[email_template_reset_subject]" value="' . esc_attr((string) ($settings['email_template_reset_subject'] ?? '')) . '" /></label>';
        echo '<label>Template: Reset Body<textarea name="lc_settings[email_template_reset_body]" rows="4">' . esc_textarea((string) ($settings['email_template_reset_body'] ?? '')) . '</textarea></label>';
        echo '<label>Template: Registration Admin Subject<input type="text" name="lc_settings[email_template_registration_admin_subject]" value="' . esc_attr((string) ($settings['email_template_registration_admin_subject'] ?? '')) . '" /></label>';
        echo '<label>Template: Registration Admin Body<textarea name="lc_settings[email_template_registration_admin_body]" rows="4">' . esc_textarea((string) ($settings['email_template_registration_admin_body'] ?? '')) . '</textarea></label>';
        echo '<label>Template: Registration Received Subject<input type="text" name="lc_settings[email_template_registration_received_subject]" value="' . esc_attr((string) ($settings['email_template_registration_received_subject'] ?? '')) . '" /></label>';
        echo '<label>Template: Registration Received Body<textarea name="lc_settings[email_template_registration_received_body]" rows="4">' . esc_textarea((string) ($settings['email_template_registration_received_body'] ?? '')) . '</textarea></label>';
        echo '<label>Template: Registration Approved Subject<input type="text" name="lc_settings[email_template_registration_approved_subject]" value="' . esc_attr((string) ($settings['email_template_registration_approved_subject'] ?? '')) . '" /></label>';
        echo '<label>Template: Registration Approved Body<textarea name="lc_settings[email_template_registration_approved_body]" rows="4">' . esc_textarea((string) ($settings['email_template_registration_approved_body'] ?? '')) . '</textarea></label>';
        echo '<label>Template: Registration Rejected Subject<input type="text" name="lc_settings[email_template_registration_rejected_subject]" value="' . esc_attr((string) ($settings['email_template_registration_rejected_subject'] ?? '')) . '" /></label>';
        echo '<label>Template: Registration Rejected Body<textarea name="lc_settings[email_template_registration_rejected_body]" rows="4">' . esc_textarea((string) ($settings['email_template_registration_rejected_body'] ?? '')) . '</textarea></label>';
        echo '<label>Template: Password Changed Subject<input type="text" name="lc_settings[email_template_password_changed_subject]" value="' . esc_attr((string) ($settings['email_template_password_changed_subject'] ?? '')) . '" /></label>';
        echo '<label>Template: Password Changed Body<textarea name="lc_settings[email_template_password_changed_body]" rows="4">' . esc_textarea((string) ($settings['email_template_password_changed_body'] ?? '')) . '</textarea></label>';
        echo '<label>Template: Admin Password Alert Subject<input type="text" name="lc_settings[email_template_admin_password_changed_subject]" value="' . esc_attr((string) ($settings['email_template_admin_password_changed_subject'] ?? '')) . '" /></label>';
        echo '<label>Template: Admin Password Alert Body<textarea name="lc_settings[email_template_admin_password_changed_body]" rows="4">' . esc_textarea((string) ($settings['email_template_admin_password_changed_body'] ?? '')) . '</textarea></label>';
        echo '<p>Email template shortcodes: {first_name}, {full_name}, {user_email}, {username}, {new_password}, {reset_link}, {revoke_link}, {company}, {phone}, {site_name}, {site_url}, {current_time}</p>';
        echo '<button type="submit">Save Settings</button>';
        echo '</form>';
        echo '</div></section>';
        }

        echo '<section class="lc-tab-panel lc-settings-panel" data-tab="settings" data-settings-panel="logs"><div class="lc-fe-card" id="lc-section-logs">';
        echo '<p class="lc-card-kicker">// LOGS</p>';
        echo '<h3>' . ($is_primary_admin ? 'Latest System Logs' : 'Your Activity Logs') . '</h3>';
        echo '<table><thead><tr><th>Time</th><th>Level</th><th>Category</th><th>Message</th></tr></thead><tbody>';
        foreach ($logs as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '<td>' . esc_html(strtoupper((string) $row->level)) . '</td>';
            echo '<td>' . esc_html($row->category) . '</td>';
            echo '<td>' . esc_html($row->message) . '</td>';
            echo '</tr>';
        }
        if (empty($logs)) {
            echo '<tr><td colspan="4">No logs yet.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div></section>';

        echo '<section class="lc-tab-panel lc-settings-panel" data-tab="settings" data-settings-panel="users"><div class="lc-fe-card" id="lc-section-users">';
        echo '<p class="lc-card-kicker">// USERS</p>';
        echo '<h3>User Settings</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data" class="lc-fe-form" style="margin-bottom:10px;">';
        wp_nonce_field('lc_frontend_update_profile_photo');
        echo '<input type="hidden" name="action" value="lc_frontend_update_profile_photo" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>Profile Photo<input type="file" name="profile_photo" accept="image/*" required /></label>';
        echo '<button type="submit">Save Photo</button>';
        echo '</form>';
        if (!$is_primary_admin) {
            echo '<p>Only profile customization and logs are available for your access level.</p>';
            echo '</div></section>';
            return;
        }
        echo '<h3>User Management</h3>';
        echo '<p>Only super admin can update important user information and access level. Email is immutable.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form" style="margin-bottom:10px;">';
        wp_nonce_field('lc_admin_create_user');
        echo '<input type="hidden" name="action" value="lc_admin_create_user" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>First Name<input type="text" name="first_name" required /></label>';
        echo '<label>Last Name<input type="text" name="last_name" required /></label>';
        echo '<label>Email (immutable)<input type="email" name="email" required /></label>';
        echo '<label>Phone<input type="text" name="phone" /></label>';
        echo '<label>Company<input type="text" name="company" /></label>';
        echo '<label>Password (optional)<input type="text" name="password" placeholder="Auto-generate if empty" /></label>';
        echo '<label>Access Level<select name="access_level"><option value="standard">Standard Access</option><option value="manager">Manager Access</option><option value="readonly">Read-Only Access</option></select></label>';
        echo '<button type="submit">Create User</button>';
        echo '</form>';

        echo '<table><thead><tr><th>Photo</th><th>User</th><th>Access</th><th>Status</th><th>Approval</th><th>Update Info</th><th>Reset</th><th>Lock</th></tr></thead><tbody>';
        foreach ($users as $u) {
            $locked = !empty(get_user_meta((int) $u->ID, 'lc_locked', true));
            $is_primary = $this->is_primary_admin($u);
            $is_pending = !empty(get_user_meta((int) $u->ID, 'lc_pending_approval', true));
            $reg_status = (string) get_user_meta((int) $u->ID, 'lc_registration_status', true);
            $access_level = sanitize_text_field((string) get_user_meta((int) $u->ID, 'lc_access_level', true));
            if (!in_array($access_level, ['standard', 'manager', 'readonly'], true)) {
                $access_level = 'standard';
            }
            $phone = (string) get_user_meta((int) $u->ID, 'lc_phone', true);
            $company = (string) get_user_meta((int) $u->ID, 'lc_company', true);
            $first_name = (string) get_user_meta((int) $u->ID, 'first_name', true);
            $last_name = (string) get_user_meta((int) $u->ID, 'last_name', true);
            echo '<tr>';
            echo '<td>' . $this->avatar_html((int) $u->ID, 34, (string) ($u->display_name ?: $u->user_login)) . '</td>';
            echo '<td><strong>' . esc_html($u->display_name ?: $u->user_login) . '</strong><br/><small>' . esc_html($u->user_email) . '</small></td>';
            echo '<td>' . esc_html(ucfirst($access_level)) . '</td>';
            echo '<td>' . ($locked ? 'Locked' : 'Active') . ($is_primary ? ' (Primary Admin)' : '') . '</td>';
            echo '<td>';
            if ($is_primary) {
                echo 'Auto-approved';
            } elseif ($is_pending || $reg_status === 'pending') {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
                wp_nonce_field('lc_admin_approve_user');
                echo '<input type="hidden" name="action" value="lc_admin_approve_user" />';
                echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
                echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $u->ID) . '" />';
                echo '<button type="submit">Approve</button>';
                echo '</form>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
                wp_nonce_field('lc_admin_reject_user');
                echo '<input type="hidden" name="action" value="lc_admin_reject_user" />';
                echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
                echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $u->ID) . '" />';
                echo '<button type="submit">Reject</button>';
                echo '</form>';
            } else {
                echo esc_html($reg_status !== '' ? ucfirst($reg_status) : 'Approved');
            }
            echo '</td>';
            echo '<td>';
            if ($is_primary) {
                echo 'Protected';
            } else {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
                wp_nonce_field('lc_admin_update_user_profile');
                echo '<input type="hidden" name="action" value="lc_admin_update_user_profile" />';
                echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
                echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $u->ID) . '" />';
                echo '<input type="text" name="first_name" value="' . esc_attr($first_name) . '" placeholder="First name" />';
                echo '<input type="text" name="last_name" value="' . esc_attr($last_name) . '" placeholder="Last name" />';
                echo '<input type="text" name="display_name" value="' . esc_attr((string) $u->display_name) . '" placeholder="Display name" />';
                echo '<input type="text" value="' . esc_attr((string) $u->user_email) . '" disabled />';
                echo '<input type="text" name="phone" value="' . esc_attr($phone) . '" placeholder="Phone" />';
                echo '<input type="text" name="company" value="' . esc_attr($company) . '" placeholder="Company" />';
                echo '<select name="access_level">';
                echo '<option value="standard" ' . selected($access_level, 'standard', false) . '>Standard</option>';
                echo '<option value="manager" ' . selected($access_level, 'manager', false) . '>Manager</option>';
                echo '<option value="readonly" ' . selected($access_level, 'readonly', false) . '>Read-Only</option>';
                echo '</select>';
                echo '<button type="submit">Save</button>';
                echo '</form>';
            }
            echo '</td>';
            echo '<td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
            wp_nonce_field('lc_admin_reset_user_password');
            echo '<input type="hidden" name="action" value="lc_admin_reset_user_password" />';
            echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
            echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $u->ID) . '" />';
            echo '<input type="text" name="new_password" placeholder="Optional password" />';
            echo '<button type="submit">Reset</button>';
            echo '</form></td>';
            echo '<td>';
            if ($is_primary) {
                echo 'Protected';
            } elseif ($locked) {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
                wp_nonce_field('lc_admin_unlock_user');
                echo '<input type="hidden" name="action" value="lc_admin_unlock_user" />';
                echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
                echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $u->ID) . '" />';
                echo '<button type="submit">Unlock</button>';
                echo '</form>';
            } else {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
                wp_nonce_field('lc_admin_lock_user');
                echo '<input type="hidden" name="action" value="lc_admin_lock_user" />';
                echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
                echo '<input type="hidden" name="user_id" value="' . esc_attr((string) $u->ID) . '" />';
                echo '<button type="submit">Lock</button>';
                echo '</form>';
            }
            echo '</td>';
            echo '</tr>';
        }
        if (empty($users)) {
            echo '<tr><td colspan="8">No users found.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div></section>';

        echo '<section class="lc-tab-panel lc-settings-panel" data-tab="settings" data-settings-panel="suppression"><div class="lc-fe-card" id="lc-section-suppression">';
        echo '<p class="lc-card-kicker">// SUPPRESSION</p>';
        echo '<h3>Suppression Management</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-fe-form">';
        wp_nonce_field('lc_add_suppression');
        echo '<input type="hidden" name="action" value="lc_add_suppression" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<label>Type<select name="type"><option value="email">Email</option><option value="phone">Phone</option><option value="domain">Domain</option><option value="name">Name</option></select></label>';
        echo '<label>Value<input type="text" name="value" required /></label>';
        echo '<label>Reason<input type="text" name="reason" /></label>';
        echo '<button type="submit">Add Suppression</button>';
        echo '</form>';
        echo '<table><thead><tr><th>ID</th><th>Type</th><th>Value</th><th>Reason</th></tr></thead><tbody>';
        foreach ($suppression as $s) {
            echo '<tr><td>' . esc_html((string) $s->id) . '</td><td>' . esc_html($s->type) . '</td><td>' . esc_html($s->value) . '</td><td>' . esc_html($s->reason) . '</td></tr>';
        }
        if (empty($suppression)) {
            echo '<tr><td colspan="4">No suppression entries yet.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div></section>';

        echo '<section class="lc-tab-panel lc-settings-panel" data-tab="settings" data-settings-panel="intelligence"><div class="lc-fe-card" id="lc-section-intelligence">';
        echo '<p class="lc-card-kicker">// INTELLIGENCE</p>';
        echo '<h3>Lead Intelligence</h3>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form" style="margin-bottom:10px;">';
        wp_nonce_field('lc_enrich_recent');
        echo '<input type="hidden" name="action" value="lc_enrich_recent" />';
        echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
        echo '<button type="submit">Enrich 25 Recent Leads</button>';
        echo '</form>';
        echo '<table><thead><tr><th>ID</th><th>Lead</th><th>Primary Email</th><th>Completeness</th><th>Confidence</th><th>Action</th></tr></thead><tbody>';
        foreach ($profiles as $p) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $p->id) . '</td>';
            echo '<td><strong>' . esc_html($p->business_name) . '</strong><br/><small>' . esc_html($p->city) . '</small></td>';
            echo '<td>' . esc_html((string) ($p->primary_email ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($p->completeness_score ?? 0)) . '%</td>';
            echo '<td>' . esc_html((string) ($p->confidence_score ?? 0)) . '%</td>';
            echo '<td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="lc-inline-form">';
            wp_nonce_field('lc_enrich_lead');
            echo '<input type="hidden" name="action" value="lc_enrich_lead" />';
            echo '<input type="hidden" name="redirect_to" value="' . esc_url($this->current_url()) . '" />';
            echo '<input type="hidden" name="lead_id" value="' . esc_attr((string) $p->id) . '" />';
            echo '<button type="submit">Enrich</button>';
            echo '</form></td>';
            echo '</tr>';
        }
        if (empty($profiles)) {
            echo '<tr><td colspan="6">No leads available.</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div></section>';

        echo '<section class="lc-tab-panel lc-settings-panel" data-tab="settings" data-settings-panel="reports"><div class="lc-fe-card" id="lc-section-reports">';
        echo '<p class="lc-card-kicker">// REPORTS</p>';
        echo '<h3>Funnel and Run Reports</h3>';
        echo '<div class="lc-fe-grid">';
        echo '<article class="lc-fe-card"><h4>Lead Funnel</h4><table><thead><tr><th>Status</th><th>Total</th></tr></thead><tbody>';
        foreach ($status_rows as $r) {
            echo '<tr><td>' . esc_html($r->status) . '</td><td>' . esc_html((string) $r->total) . '</td></tr>';
        }
        if (empty($status_rows)) {
            echo '<tr><td colspan="2">No lead data.</td></tr>';
        }
        echo '</tbody></table></article>';
        echo '<article class="lc-fe-card"><h4>Run Health</h4><table><thead><tr><th>Status</th><th>Total</th></tr></thead><tbody>';
        foreach ($run_rows as $r) {
            echo '<tr><td>' . esc_html($r->status) . '</td><td>' . esc_html((string) $r->total) . '</td></tr>';
        }
        if (empty($run_rows)) {
            echo '<tr><td colspan="2">No run data.</td></tr>';
        }
        echo '</tbody></table></article>';
        echo '</div>';
        echo '</div></section>';

    }

    private function tutorial_markup()
    {
        echo '<div class="lc-tutorial" hidden aria-hidden="true">';
        echo '<div class="lc-tutorial-panel">';
        echo '<div class="lc-tutorial-head">';
        echo '<h2>Quick Tutorial</h2>';
        echo '<button type="button" class="lc-tutorial-close" aria-label="Close tutorial">x</button>';
        echo '</div>';
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
        if (!is_user_logged_in()) {
            $this->redirect_with_msg('not_allowed');
        }

        if ($this->is_locked_user(get_current_user_id())) {
            wp_logout();
            $this->redirect_with_msg('user_locked');
        }

        if (!empty(get_user_meta(get_current_user_id(), 'lc_pending_approval', true))) {
            wp_logout();
            $this->redirect_with_msg('approval_pending');
        }
    }

    public function handle_login()
    {
        check_admin_referer('lc_frontend_login');

        if (empty($_POST['accept_gdpr'])) {
            $this->redirect_with_msg('gdpr_required');
        }

        $identifier = sanitize_text_field($_POST['log'] ?? '');
        if ($identifier === '') {
            $this->log_event('auth', 'warning', 'Login failed: empty username.', []);
            $this->redirect_with_msg('login_failed');
        }

        // Resolve email -> username for consistent authentication behavior.
        if (strpos($identifier, '@') !== false) {
            $email_user = get_user_by('email', $identifier);
            if ($email_user) {
                $identifier = (string) $email_user->user_login;
            }
        }

        $creds = [
            'user_login' => $identifier,
            'user_password' => (string) ($_POST['pwd'] ?? ''),
            'remember' => true,
        ];

        $user = wp_signon($creds, is_ssl());
        if (is_wp_error($user)) {
            $this->log_event('auth', 'warning', 'Login failed for identifier.', ['identifier' => $identifier]);
            $this->redirect_with_msg('login_failed');
        }

        if ($this->is_locked_user((int) $user->ID)) {
            wp_logout();
            $this->log_event('auth', 'warning', 'Login blocked for locked user.', ['user_id' => (int) $user->ID]);
            $this->redirect_with_msg('user_locked');
        }

        if (!empty(get_user_meta((int) $user->ID, 'lc_pending_approval', true)) || get_user_meta((int) $user->ID, 'lc_registration_status', true) === 'pending') {
            wp_logout();
            $this->log_event('auth', 'warning', 'Login blocked for pending approval.', ['user_id' => (int) $user->ID]);
            $this->redirect_with_msg('approval_pending');
        }

        if (get_user_meta((int) $user->ID, 'lc_registration_status', true) === 'rejected') {
            wp_logout();
            $this->log_event('auth', 'warning', 'Login blocked for rejected registration.', ['user_id' => (int) $user->ID]);
            $this->redirect_with_msg('approval_rejected');
        }

        update_user_meta((int) $user->ID, 'lc_gdpr_accepted', 1);
        update_user_meta((int) $user->ID, 'lc_gdpr_accepted_at', current_time('mysql'));
        $this->log_event('compliance', 'info', 'GDPR consent captured on login.', ['user_id' => (int) $user->ID]);
        $this->log_event('auth', 'info', 'Login successful.', ['user_id' => (int) $user->ID]);

        wp_safe_redirect($this->redirect_target());
        exit;
    }

    public function handle_register()
    {
        check_admin_referer('lc_frontend_register');

        $email = sanitize_email($_POST['email'] ?? '');
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $company = sanitize_text_field($_POST['company'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (!$email || email_exists($email)) {
            $this->redirect_with_msg('register_exists');
        }
        if (strlen($password) < 8) {
            $this->redirect_with_msg('register_password_short');
        }

        $base_login = sanitize_user(strstr($email, '@', true) ?: 'user', true);
        $login = $base_login ?: 'user';
        $suffix = 1;
        while (username_exists($login)) {
            $login = $base_login . $suffix;
            $suffix++;
        }

        $user_id = wp_create_user($login, $password, $email);
        if (is_wp_error($user_id) || !$user_id) {
            $this->log_event('auth', 'error', 'Registration failed during user creation.', ['email' => $email]);
            $this->redirect_with_msg('user_action_error');
        }

        wp_update_user([
            'ID' => (int) $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'role' => 'subscriber',
        ]);
        update_user_meta((int) $user_id, 'lc_phone', $phone);
        update_user_meta((int) $user_id, 'lc_company', $company);
        update_user_meta((int) $user_id, 'lc_access_level', 'standard');
        update_user_meta((int) $user_id, 'lc_pending_approval', 1);
        update_user_meta((int) $user_id, 'lc_registration_status', 'pending');
        update_user_meta((int) $user_id, 'lc_locked', 1);

        $full_name = trim($first_name . ' ' . $last_name);
        $this->notify_email($email, 'registration_received', [
            'first_name' => $first_name,
            'full_name' => $full_name,
            'user_email' => $email,
            'company' => $company,
            'phone' => $phone,
        ], 'Registration request received', "Hello {first_name},\n\nYour registration request was received and is pending admin approval.");

        $admin_email = defined('LC_PRIMARY_ADMIN_EMAIL') ? LC_PRIMARY_ADMIN_EMAIL : get_option('admin_email');
        $this->notify_email($admin_email, 'registration_admin', [
            'first_name' => $first_name,
            'full_name' => $full_name,
            'user_email' => $email,
            'company' => $company,
            'phone' => $phone,
        ], 'New registration request pending approval', "A new user registration request was submitted.\n\nName: {full_name}\nEmail: {user_email}\nCompany: {company}\nPhone: {phone}\n\nPlease review in Users tab.");

        $this->log_event('users', 'info', 'Registration request submitted from frontend.', ['user_id' => (int) $user_id, 'email' => $email]);
        $this->redirect_with_msg('register_request_sent');
    }

    public function handle_forgot_password()
    {
        check_admin_referer('lc_frontend_forgot_password');

        $email = sanitize_email($_POST['email'] ?? '');
        if (!$email) {
            $this->log_event('auth', 'warning', 'Forgot password failed: invalid email.', []);
            $this->redirect_with_msg('reset_email_not_found');
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            $this->log_event('auth', 'warning', 'Forgot password failed: email not found.', ['email' => $email]);
            $this->redirect_with_msg('reset_email_not_found');
        }

        $token_data = $this->create_timed_token('lc_reset_token_', (int) $user->ID, 300, []);
        $reset_link = add_query_arg([
            'lc_reset' => '1',
            'selector' => $token_data['selector'],
            'token' => $token_data['token'],
        ], remove_query_arg(['lc_msg', 'lc_reset', 'selector', 'token'], $this->redirect_target()));

        $this->notify_email(
            $user->user_email,
            'reset',
            [
                'first_name' => (string) get_user_meta((int) $user->ID, 'first_name', true),
                'user_email' => (string) $user->user_email,
                'reset_link' => esc_url_raw($reset_link),
            ],
            '5N2 Digital Lead Console Core Password Reset',
            "A password reset was requested for your account.\n\nReset link (valid for 5 minutes):\n{reset_link}\n\nIf you did not request this, ignore this email."
        );
        $this->log_event('auth', 'info', 'Forgot password email sent.', ['user_id' => (int) $user->ID]);

        $this->redirect_with_msg('reset_sent');
    }

    public function handle_reset_password()
    {
        check_admin_referer('lc_frontend_reset_password');

        $selector = sanitize_text_field($_POST['selector'] ?? '');
        $token = sanitize_text_field($_POST['token'] ?? '');
        $new_password = (string) ($_POST['new_password'] ?? '');

        if (strlen($new_password) < 8) {
            $this->log_event('auth', 'warning', 'Reset password failed: password too short.', []);
            $this->redirect_with_msg('reset_link_invalid');
        }

        $record = $this->consume_timed_token('lc_reset_token_', $selector, $token);
        if (!$record) {
            $this->log_event('auth', 'warning', 'Reset password failed: invalid or expired token.', []);
            $this->redirect_with_msg('reset_link_invalid');
        }

        $user_id = (int) ($record['user_id'] ?? 0);
        $user = get_user_by('id', $user_id);
        if (!$user) {
            $this->log_event('auth', 'error', 'Reset password failed: missing user record.', ['user_id' => $user_id]);
            $this->redirect_with_msg('reset_link_invalid');
        }

        wp_set_password($new_password, $user_id);
        update_user_meta($user_id, 'lc_locked', 0);

        $revoke = $this->create_timed_token('lc_revoke_token_', $user_id, 300, []);
        $revoke_link = add_query_arg([
            'action' => 'lc_frontend_revoke_password_change',
            'selector' => $revoke['selector'],
            'token' => $revoke['token'],
        ], admin_url('admin-post.php'));

        $this->notify_email(
            $user->user_email,
            'password_changed',
            [
                'first_name' => (string) get_user_meta($user_id, 'first_name', true),
                'user_email' => (string) $user->user_email,
                'revoke_link' => esc_url_raw($revoke_link),
            ],
            'Your 5N2 Digital Lead Console Core password was changed',
            "Your password was changed.\n\nIf this was NOT you, click this revoke link within 5 minutes:\n{revoke_link}\n\nAfter 5 minutes, the revoke link expires."
        );

        $admin_email = defined('LC_PRIMARY_ADMIN_EMAIL') ? LC_PRIMARY_ADMIN_EMAIL : get_option('admin_email');
        $this->notify_email(
            $admin_email,
            'admin_password_changed',
            [
                'user_email' => (string) $user->user_email,
                'current_time' => current_time('mysql'),
            ],
            'Lead Console Alert: Password changed',
            "User password changed:\nEmail: {user_email}\nTime: {current_time}\nRevoke window: 5 minutes."
        );
        $this->log_event('auth', 'info', 'Password reset successful.', ['user_id' => $user_id]);

        $this->redirect_with_msg('reset_success');
    }

    public function handle_revoke_password_change()
    {
        $selector = sanitize_text_field($_GET['selector'] ?? '');
        $token = sanitize_text_field($_GET['token'] ?? '');

        $record = $this->consume_timed_token('lc_revoke_token_', $selector, $token);
        if (!$record) {
            $this->log_event('auth', 'warning', 'Password revoke failed: invalid or expired token.', []);
            $target = add_query_arg('lc_msg', 'reset_link_invalid', home_url('/'));
            wp_safe_redirect($target);
            exit;
        }

        $user_id = (int) ($record['user_id'] ?? 0);
        $user = get_user_by('id', $user_id);
        if ($user) {
            update_user_meta($user_id, 'lc_locked', 1);
            wp_set_password(wp_generate_password(24, true, true), $user_id);

            $admin_email = defined('LC_PRIMARY_ADMIN_EMAIL') ? LC_PRIMARY_ADMIN_EMAIL : get_option('admin_email');
            $this->notify_email(
                $admin_email,
                'admin_password_changed',
                [
                    'user_email' => (string) $user->user_email,
                    'current_time' => current_time('mysql'),
                ],
                'Lead Console Alert: Password change revoked',
                "A password change was revoked by user confirmation.\nUser: {user_email}\nAccount locked. Super admin manual reset is required.\nTime: {current_time}"
            );
            $this->log_event('auth', 'critical', 'Password change revoked and account locked.', ['user_id' => $user_id]);
        }

        $target = add_query_arg('lc_msg', 'reset_revoke_success', home_url('/'));
        wp_safe_redirect($target);
        exit;
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
        $this->log_event('compliance', 'info', 'GDPR consent updated.', ['user_id' => get_current_user_id()]);
        wp_safe_redirect($this->redirect_target());
        exit;
    }

    public function handle_update_profile_photo()
    {
        $this->ensure_frontend_user();
        check_admin_referer('lc_frontend_update_profile_photo');

        if (empty($_FILES['profile_photo']) || !is_array($_FILES['profile_photo'])) {
            $this->redirect_with_msg('photo_invalid');
        }

        $file = $_FILES['profile_photo'];
        if (!empty($file['error'])) {
            $this->redirect_with_msg('photo_invalid');
        }

        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $this->redirect_with_msg('photo_invalid');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload('profile_photo', 0);
        if (is_wp_error($attachment_id) || !$attachment_id) {
            $this->log_event('users', 'warning', 'Profile photo upload failed.', ['error' => is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'unknown']);
            $this->redirect_with_msg('photo_invalid');
        }

        update_user_meta(get_current_user_id(), 'lc_profile_photo_id', (int) $attachment_id);
        $this->log_event('users', 'info', 'Profile photo updated.', ['user_id' => get_current_user_id(), 'attachment_id' => (int) $attachment_id]);
        $this->redirect_with_msg('photo_updated');
    }

    public function handle_import_leads()
    {
        $this->ensure_frontend_user();
        check_admin_referer('lc_frontend_import_leads');

        if (empty($_FILES['import_file']) || !is_array($_FILES['import_file'])) {
            $this->redirect_with_import_result('import_error', 0, 0);
        }

        $file = $_FILES['import_file'];
        if (!empty($file['error']) || empty($file['tmp_name'])) {
            $this->redirect_with_import_result('import_error', 0, 0);
        }

        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'tsv', 'txt', 'json', 'xlsx'], true)) {
            $this->log_event('leads', 'warning', 'Import rejected due to unsupported extension.', ['extension' => $ext]);
            $this->redirect_with_import_result('import_error', 0, 0);
        }

        $rows = $this->parse_import_rows((string) $file['tmp_name'], $ext);
        if (empty($rows)) {
            $this->log_event('leads', 'warning', 'Import parsed zero rows.', ['extension' => $ext]);
            $this->redirect_with_import_result('import_error', 0, 0);
        }

        global $wpdb;
        $imported = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $mapped = $this->map_import_row($row);
            if (!$mapped) {
                $failed++;
                continue;
            }

            if ($this->is_suppressed($mapped['email'], $mapped['phone'], $mapped['website'], $mapped['business_name'])) {
                $failed++;
                continue;
            }

            $score = $this->compute_score($mapped['website'], $mapped['phone'], $mapped['email'], $mapped['review_count'], $mapped['rating']);
            $result = $wpdb->insert($this->table('lc_leads'), [
                'business_name' => $mapped['business_name'],
                'city' => $mapped['city'],
                'category' => $mapped['category'],
                'address' => $mapped['address'],
                'website' => $mapped['website'],
                'phone' => $mapped['phone'],
                'email' => $mapped['email'],
                'status' => $this->normalize_status($mapped['status']),
                'score' => $score,
                'lead_type' => $this->compute_lead_type($mapped['website'], $score),
                'review_count' => absint($mapped['review_count']),
                'rating' => (float) $mapped['rating'],
                'source_url' => $mapped['source_url'],
                'notes' => $mapped['notes'],
            ]);

            if ($result) {
                $imported++;
            } else {
                $failed++;
            }
        }

        $message = 'import_error';
        if ($imported > 0 && $failed === 0) {
            $message = 'import_success';
        } elseif ($imported > 0 && $failed > 0) {
            $message = 'import_partial';
        }

        $this->log_event('leads', 'info', 'Lead import processed.', ['imported' => $imported, 'failed' => $failed, 'extension' => $ext]);
        $this->redirect_with_import_result($message, $imported, $failed);
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

        $this->log_event('leads', 'info', 'Lead added from frontend.', ['business_name' => sanitize_text_field($_POST['business_name'] ?? '')]);
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

        $this->log_event('leads', 'info', 'Lead status updated from frontend.', ['lead_id' => absint($_POST['lead_id'] ?? 0)]);
        $this->redirect_with_msg('status_updated');
    }

    public function handle_queue_run()
    {
        $this->ensure_frontend_user();
        check_admin_referer('lc_frontend_queue_run');

        global $wpdb;
        $settings = $this->settings();
        $query_text = sanitize_text_field($_POST['query_text'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $country = sanitize_text_field($_POST['country'] ?? '');
        $state = sanitize_text_field($_POST['state'] ?? '');
        $radius_miles = absint($_POST['radius_miles'] ?? 0);
        $service_focus = sanitize_text_field($_POST['niche'] ?? '');
        $services = sanitize_textarea_field($_POST['services'] ?? '');
        $min_rating = max(0, min(5, (float) ($_POST['min_rating'] ?? 0)));
        $min_reviews = absint($_POST['min_reviews'] ?? 0);
        $city_for_storage = $city;
        $requested_max = absint($_POST['max_places'] ?? 0);
        $max_places = min(max(1, $requested_max ?: (int) $settings['max_places_per_run']), (int) $settings['max_places_per_run']);
        $country_scope = ($country !== '' && $city === '' && $state === '');
        if ($country_scope) {
            $max_places = max(1, (int) $settings['max_places_per_run']);
        }
        $website_focus = sanitize_text_field((string) ($_POST['website_focus'] ?? 'any'));
        if (!in_array($website_focus, ['any', 'no_website', 'has_website'], true)) {
            $website_focus = 'any';
        }

        $review = $this->evaluate_run_compliance([
            'query_text' => $query_text,
            'city' => $city,
            'country' => $country,
            'state' => $state,
            'website_focus' => $website_focus,
            'min_rating' => $min_rating,
            'min_reviews' => $min_reviews,
            'max_places' => $max_places,
        ], $settings);

        if (!$review['ok']) {
            $this->log_event('compliance', 'warning', 'Run blocked by automatic compliance review.', [
                'query' => $query_text,
                'city' => $city,
                'issues' => $review['issues'],
            ]);
            $this->redirect_with_msg('run_compliance_blocked');
        }

        $wpdb->insert($this->table('lc_runs'), [
            'query_text' => $query_text,
            'city' => $city_for_storage,
            'state' => $state,
            'country' => $country,
            'radius_miles' => $radius_miles,
            'niche' => $service_focus,
            'services' => $services,
            'website_focus' => $website_focus,
            'min_rating' => $min_rating,
            'min_reviews' => $min_reviews,
            'max_places' => $max_places,
            'save_mode' => 'draft',
            'review_status' => 'pending',
            'captured_leads' => 0,
            'status' => 'queued',
        ]);
        $run_id = (int) $wpdb->insert_id;

        $this->log_event('runs', 'info', 'Run queued from frontend.', [
            'query' => $query_text,
            'city' => $city,
            'country' => $country,
            'state' => $state,
            'radius_miles' => $radius_miles,
            'niche' => $service_focus,
            'website_focus' => $website_focus,
            'scope' => $country_scope ? 'country_wide' : 'city_targeted',
            'compliance_review' => 'pass',
            'compliance_checks' => $review['checks'],
            'compliance_warnings' => $review['warnings'],
            'save_mode' => 'draft',
        ]);
        $this->redirect_with_msg('run_queued', ['lc_run_id' => $run_id]);
    }

    public function handle_ajax_run_status()
    {
        check_ajax_referer('lc_frontend_run_monitor', 'nonce');
        if (!$this->is_frontend_user_ready()) {
            wp_send_json_error(['message' => 'Not authorized.'], 403);
        }

        $run_id = absint($_POST['run_id'] ?? 0);
        if ($run_id <= 0) {
            wp_send_json_error(['message' => 'Invalid run id.'], 400);
        }

        global $wpdb;
        $run = $wpdb->get_row($wpdb->prepare("SELECT id, query_text, city, state, country, radius_miles, niche, services, website_focus, min_rating, min_reviews, max_places, captured_leads, save_mode, review_status, status, created_at, started_at, finished_at FROM {$this->table('lc_runs')} WHERE id = %d LIMIT 1", $run_id), ARRAY_A);
        if (!$run) {
            wp_send_json_error(['message' => 'Run not found.'], 404);
        }

        $logs = $wpdb->get_results($wpdb->prepare("SELECT level, message, created_at FROM {$this->table('lc_run_logs')} WHERE run_id = %d ORDER BY id ASC LIMIT 120", $run_id), ARRAY_A);
        $draft_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table('lc_run_drafts')} WHERE run_id = %d", $run_id));
        $draft_preview = $wpdb->get_results($wpdb->prepare("SELECT business_name, city, category, website, phone, email, score, lead_type, notes FROM {$this->table('lc_run_drafts')} WHERE run_id = %d ORDER BY id DESC LIMIT 40", $run_id), ARRAY_A);

        wp_send_json_success([
            'run' => [
                'id' => (int) $run['id'],
                'status' => (string) ($run['status'] ?? ''),
                'review_status' => (string) ($run['review_status'] ?? 'na'),
                'captured_leads' => (int) ($run['captured_leads'] ?? 0),
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
            ],
            'logs' => is_array($logs) ? $logs : [],
            'draft_count' => $draft_count,
            'draft_preview' => is_array($draft_preview) ? $draft_preview : [],
        ]);
    }

    public function handle_ajax_run_save_drafts()
    {
        check_ajax_referer('lc_frontend_run_monitor', 'nonce');
        if (!$this->is_frontend_user_ready()) {
            wp_send_json_error(['message' => 'Not authorized.'], 403);
        }
        $run_id = absint($_POST['run_id'] ?? 0);
        if ($run_id <= 0) {
            wp_send_json_error(['message' => 'Invalid run id.'], 400);
        }

        global $wpdb;
        $drafts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table('lc_run_drafts')} WHERE run_id = %d ORDER BY id ASC", $run_id), ARRAY_A);
        $saved = 0;
        foreach ($drafts as $draft) {
            $inserted = $wpdb->insert($this->table('lc_leads'), [
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

        $wpdb->delete($this->table('lc_run_drafts'), ['run_id' => $run_id], ['%d']);
        $wpdb->update($this->table('lc_runs'), ['review_status' => 'saved'], ['id' => $run_id], ['%s'], ['%d']);
        $this->log_event('runs', 'info', 'Draft run leads saved to leads database.', ['run_id' => $run_id, 'saved' => $saved]);

        wp_send_json_success(['saved' => $saved]);
    }

    public function handle_ajax_run_discard_drafts()
    {
        check_ajax_referer('lc_frontend_run_monitor', 'nonce');
        if (!$this->is_frontend_user_ready()) {
            wp_send_json_error(['message' => 'Not authorized.'], 403);
        }
        $run_id = absint($_POST['run_id'] ?? 0);
        if ($run_id <= 0) {
            wp_send_json_error(['message' => 'Invalid run id.'], 400);
        }

        global $wpdb;
        $wpdb->delete($this->table('lc_run_drafts'), ['run_id' => $run_id], ['%d']);
        $wpdb->update($this->table('lc_runs'), ['review_status' => 'discarded'], ['id' => $run_id], ['%s'], ['%d']);
        $this->log_event('runs', 'info', 'Draft run leads discarded.', ['run_id' => $run_id]);
        wp_send_json_success(['discarded' => true]);
    }

    private function evaluate_run_compliance($run, $settings)
    {
        $issues = [];
        $checks = [];
        $warnings = [];

        $query = trim((string) ($run['query_text'] ?? ''));
        $city = trim((string) ($run['city'] ?? ''));
        $country = trim((string) ($run['country'] ?? ''));
        $state = trim((string) ($run['state'] ?? ''));
        if ($query === '') {
            $issues[] = 'missing_query_text';
        } else {
            $checks[] = 'query_text_present';
        }
        if ($city === '' && $state === '' && $country === '') {
            $issues[] = 'missing_location';
        } else {
            $checks[] = 'location_present';
        }
        if ($city !== '' && $country === '') {
            $warnings[] = 'city_without_country';
        }
        if ($state !== '' && $country === '') {
            $warnings[] = 'state_without_country';
        }
        if ($country !== '' && $city === '' && $state === '') {
            $checks[] = 'country_wide_scope_enabled';
        }
        if ($country !== '' && $state !== '' && $city === '') {
            $checks[] = 'state_wide_scope_enabled';
        }

        $min_rating = (float) ($run['min_rating'] ?? 0);
        if ($min_rating < 0 || $min_rating > 5) {
            $issues[] = 'invalid_min_rating';
        } else {
            $checks[] = 'min_rating_in_range';
        }

        $min_reviews = (int) ($run['min_reviews'] ?? 0);
        if ($min_reviews < 0) {
            $issues[] = 'invalid_min_reviews';
        } else {
            $checks[] = 'min_reviews_non_negative';
        }

        $max_places = (int) ($run['max_places'] ?? 0);
        $max_allowed = max(1, absint($settings['max_places_per_run'] ?? 25));
        if ($max_places < 1 || $max_places > $max_allowed) {
            $issues[] = 'max_places_out_of_bounds';
        } else {
            $checks[] = 'max_places_within_limit';
        }

        $live_api_enabled = !empty($settings['enable_live_api_calls']);
        $discovery_mode = sanitize_text_field((string) ($settings['discovery_mode'] ?? 'hybrid'));
        if ($live_api_enabled && in_array($discovery_mode, ['hybrid', 'google_only'], true)) {
            if (trim((string) ($settings['google_places_api_key'] ?? '')) === '') {
                $issues[] = 'missing_google_places_api_key';
            } else {
                $checks[] = 'google_places_api_key_present';
            }
        }

        $social_mode = sanitize_text_field((string) ($settings['social_discovery_mode'] ?? 'off'));
        if ($social_mode !== 'off') {
            if (trim((string) ($settings['google_cse_api_key'] ?? '')) === '' || trim((string) ($settings['google_cse_cx'] ?? '')) === '') {
                $issues[] = 'missing_google_cse_configuration';
            } else {
                $checks[] = 'google_cse_configuration_present';
            }
        }

        return [
            'ok' => empty($issues),
            'issues' => $issues,
            'checks' => $checks,
            'warnings' => $warnings,
        ];
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
            'enable_live_api_calls' => 0,
            'google_places_api_key' => '',
            'discovery_mode' => 'hybrid',
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
            'email_template_reset_subject' => '',
            'email_template_reset_body' => '',
            'email_template_password_changed_subject' => '',
            'email_template_password_changed_body' => '',
            'email_template_admin_password_changed_subject' => '',
            'email_template_admin_password_changed_body' => '',
            'email_template_registration_received_subject' => '',
            'email_template_registration_received_body' => '',
            'email_template_registration_admin_subject' => '',
            'email_template_registration_admin_body' => '',
            'email_template_registration_approved_subject' => '',
            'email_template_registration_approved_body' => '',
            'email_template_registration_rejected_subject' => '',
            'email_template_registration_rejected_body' => '',
        ];
        return wp_parse_args(get_option('lc_settings', []), $defaults);
    }

    private function load_city_dataset()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [];
        $path = LC_PLUGIN_PATH . 'assets/cities-by-country.json';
        if (!file_exists($path)) {
            return $cache;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return $cache;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $cache;
        }

        foreach ($decoded as $country => $cities) {
            $country_name = sanitize_text_field((string) $country);
            if ($country_name === '' || !is_array($cities)) {
                continue;
            }

            $clean_cities = [];
            foreach ($cities as $city) {
                $city_name = sanitize_text_field((string) $city);
                if ($city_name !== '' && !in_array($city_name, $clean_cities, true)) {
                    $clean_cities[] = $city_name;
                }
            }

            if (!empty($clean_cities)) {
                $cache[$country_name] = $clean_cities;
            }
        }

        return $cache;
    }

    private function load_location_hierarchy()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $cache = [];
        $path = LC_PLUGIN_PATH . 'assets/location-hierarchy.json';
        if (!file_exists($path)) {
            return $cache;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return $cache;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $cache;
        }

        foreach ($decoded as $country => $states) {
            $country_name = sanitize_text_field((string) $country);
            if ($country_name === '' || !is_array($states)) {
                continue;
            }

            $clean_states = [];
            foreach ($states as $state => $cities) {
                $state_name = sanitize_text_field((string) $state);
                if ($state_name === '' || !is_array($cities)) {
                    continue;
                }
                $clean_cities = [];
                foreach ($cities as $city) {
                    $city_name = sanitize_text_field((string) $city);
                    if ($city_name !== '' && !in_array($city_name, $clean_cities, true)) {
                        $clean_cities[] = $city_name;
                    }
                }
                if (!empty($clean_cities)) {
                    $clean_states[$state_name] = $clean_cities;
                }
            }

            if (!empty($clean_states)) {
                $cache[$country_name] = $clean_states;
            }
        }

        return $cache;
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

    private function parse_import_rows($path, $ext)
    {
        if ($ext === 'json') {
            $json = file_get_contents($path);
            if ($json === false) {
                return [];
            }
            $decoded = json_decode($json, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($ext === 'xlsx') {
            return $this->parse_xlsx_rows($path);
        }

        $delimiter = ',';
        if ($ext === 'tsv') {
            $delimiter = "\t";
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $rows = [];
        $first = true;
        $header = [];
        $has_header = false;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($ext === 'txt' && $first) {
                $joined = implode(',', $data);
                if (substr_count($joined, "\t") > substr_count($joined, ',')) {
                    fclose($handle);
                    return $this->parse_import_rows_with_delimiter($path, "\t");
                }
            }

            if ($first) {
                $header = array_map([$this, 'normalize_import_header'], $data);
                $has_header = $this->looks_like_header($header);
                $first = false;
                if (!$has_header) {
                    $rows[] = $this->row_from_indexed($data);
                }
                continue;
            }

            if ($has_header) {
                $assoc = [];
                foreach ($data as $i => $value) {
                    $key = $header[$i] ?? ('col_' . $i);
                    $assoc[$key] = (string) $value;
                }
                $rows[] = $assoc;
            } else {
                $rows[] = $this->row_from_indexed($data);
            }
        }
        fclose($handle);

        return $rows;
    }

    private function parse_import_rows_with_delimiter($path, $delimiter)
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }
        $rows = [];
        $first = true;
        $header = [];
        $has_header = false;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($first) {
                $header = array_map([$this, 'normalize_import_header'], $data);
                $has_header = $this->looks_like_header($header);
                $first = false;
                if (!$has_header) {
                    $rows[] = $this->row_from_indexed($data);
                }
                continue;
            }
            if ($has_header) {
                $assoc = [];
                foreach ($data as $i => $value) {
                    $assoc[$header[$i] ?? ('col_' . $i)] = (string) $value;
                }
                $rows[] = $assoc;
            } else {
                $rows[] = $this->row_from_indexed($data);
            }
        }
        fclose($handle);
        return $rows;
    }

    private function parse_xlsx_rows($path)
    {
        if (!class_exists('ZipArchive')) {
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheet_xml === false) {
            $zip->close();
            return [];
        }

        $shared = [];
        $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($shared_xml !== false) {
            $sx = @simplexml_load_string($shared_xml);
            if ($sx) {
                $si_nodes = $sx->xpath('//*[local-name()="si"]');
                if (is_array($si_nodes)) {
                    foreach ($si_nodes as $si) {
                        $text_nodes = $si->xpath('.//*[local-name()="t"]');
                        $text = '';
                        if (is_array($text_nodes)) {
                            foreach ($text_nodes as $t_node) {
                                $text .= (string) $t_node;
                            }
                        }
                        $shared[] = $text;
                    }
                }
            }
        }

        $rows = [];
        $sx_sheet = @simplexml_load_string($sheet_xml);
        if (!$sx_sheet) {
            $zip->close();
            return [];
        }

        $row_nodes = $sx_sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]');
        if (!is_array($row_nodes)) {
            $zip->close();
            return [];
        }

        foreach ($row_nodes as $row) {
            $vals = [];
            $cell_nodes = $row->xpath('./*[local-name()="c"]');
            foreach ((array) $cell_nodes as $c) {
                $type = (string) ($c['t'] ?? '');
                $v_nodes = $c->xpath('./*[local-name()="v"]');
                $val = is_array($v_nodes) && isset($v_nodes[0]) ? (string) $v_nodes[0] : '';
                if ($type === 's') {
                    $idx = absint($val);
                    $val = $shared[$idx] ?? '';
                }
                $vals[] = $val;
            }
            if (!empty($vals)) {
                $rows[] = $vals;
            }
        }

        $zip->close();
        if (empty($rows)) {
            return [];
        }

        $header = array_map([$this, 'normalize_import_header'], $rows[0]);
        $has_header = $this->looks_like_header($header);
        $mapped = [];
        foreach ($rows as $idx => $vals) {
            if ($idx === 0 && $has_header) {
                continue;
            }
            if ($has_header) {
                $assoc = [];
                foreach ($vals as $i => $val) {
                    $assoc[$header[$i] ?? ('col_' . $i)] = (string) $val;
                }
                $mapped[] = $assoc;
            } else {
                $mapped[] = $this->row_from_indexed($vals);
            }
        }

        return $mapped;
    }

    private function normalize_import_header($header)
    {
        $header = strtolower(trim((string) $header));
        $header = str_replace(['-', ' ', '.'], '_', $header);
        $map = [
            'name' => 'business_name',
            'company' => 'business_name',
            'company_name' => 'business_name',
            'business' => 'business_name',
            'businessname' => 'business_name',
            'e_mail' => 'email',
            'mail' => 'email',
            'telephone' => 'phone',
            'mobile' => 'phone',
            'site' => 'website',
            'url' => 'website',
            'services_offered' => 'services',
            'service' => 'services',
            'reviews' => 'review_count',
            'user_ratings_total' => 'review_count',
            'stars' => 'rating',
            'lead_status' => 'status',
            'source' => 'source_url',
        ];
        return $map[$header] ?? $header;
    }

    private function looks_like_header($headers)
    {
        $known = ['business_name', 'city', 'category', 'address', 'website', 'phone', 'email', 'review_count', 'rating', 'status', 'notes', 'source_url'];
        foreach ($headers as $h) {
            if (in_array((string) $h, $known, true)) {
                return true;
            }
        }
        return false;
    }

    private function row_from_indexed($data)
    {
        return [
            'business_name' => (string) ($data[0] ?? ''),
            'city' => (string) ($data[1] ?? ''),
            'category' => (string) ($data[2] ?? ''),
            'address' => (string) ($data[3] ?? ''),
            'website' => (string) ($data[4] ?? ''),
            'phone' => (string) ($data[5] ?? ''),
            'email' => (string) ($data[6] ?? ''),
            'review_count' => (string) ($data[7] ?? '0'),
            'rating' => (string) ($data[8] ?? '0'),
            'status' => (string) ($data[9] ?? 'New'),
            'notes' => (string) ($data[10] ?? ''),
            'source_url' => (string) ($data[11] ?? ''),
        ];
    }

    private function map_import_row($row)
    {
        if (!is_array($row)) {
            return null;
        }

        $business_name = sanitize_text_field((string) ($row['business_name'] ?? $row['name'] ?? ''));
        $city = sanitize_text_field((string) ($row['city'] ?? ''));
        $website = esc_url_raw((string) ($row['website'] ?? ''));
        $phone = sanitize_text_field((string) ($row['phone'] ?? ''));
        $email = sanitize_email((string) ($row['email'] ?? ''));

        // Require business name plus at least two key fields to consider row complete.
        $completeness = 0;
        foreach ([$city, $website, $phone, $email] as $value) {
            if ($value !== '') {
                $completeness++;
            }
        }
        if ($business_name === '' || $completeness < 2) {
            return null;
        }

        return [
            'business_name' => $business_name,
            'city' => $city,
            'category' => sanitize_text_field((string) ($row['category'] ?? $row['niche'] ?? $row['services'] ?? '')),
            'address' => sanitize_text_field((string) ($row['address'] ?? '')),
            'website' => $website,
            'phone' => $phone,
            'email' => $email,
            'review_count' => absint($row['review_count'] ?? 0),
            'rating' => max(0, min(5, (float) ($row['rating'] ?? 0))),
            'status' => sanitize_text_field((string) ($row['status'] ?? 'New')),
            'notes' => sanitize_textarea_field((string) ($row['notes'] ?? '')),
            'source_url' => esc_url_raw((string) ($row['source_url'] ?? '')),
        ];
    }

    private function redirect_with_msg($msg, $extra_args = [])
    {
        $target = $this->redirect_target();
        $target = add_query_arg('lc_msg', $msg, $target);
        if (is_array($extra_args)) {
            foreach ($extra_args as $key => $value) {
                $target = add_query_arg(sanitize_key((string) $key), sanitize_text_field((string) $value), $target);
            }
        }
        wp_safe_redirect($target);
        exit;
    }

    private function redirect_with_import_result($msg, $imported, $failed)
    {
        $target = $this->redirect_target();
        $target = add_query_arg('lc_msg', $msg, $target);
        $target = add_query_arg('lc_imported', absint($imported), $target);
        $target = add_query_arg('lc_failed', absint($failed), $target);
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

    private function has_reset_query()
    {
        return !empty($_GET['lc_reset']) && !empty($_GET['selector']) && !empty($_GET['token']);
    }

    private function is_frontend_user_ready()
    {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return false;
        }
        if ($this->is_locked_user($user_id)) {
            return false;
        }
        if (!empty(get_user_meta($user_id, 'lc_pending_approval', true))) {
            return false;
        }
        return true;
    }

    private function create_timed_token($prefix, $user_id, $ttl_seconds, $extra)
    {
        $selector = wp_generate_password(20, false, false);
        $token = wp_generate_password(48, false, false);
        $record = array_merge($extra, [
            'user_id' => (int) $user_id,
            'token_hash' => hash('sha256', $token),
            'created_at' => time(),
        ]);
        set_transient($prefix . $selector, $record, $ttl_seconds);
        return [
            'selector' => $selector,
            'token' => $token,
        ];
    }

    private function consume_timed_token($prefix, $selector, $token)
    {
        if ($selector === '' || $token === '') {
            return null;
        }

        $record = get_transient($prefix . $selector);
        if (!$record || !is_array($record)) {
            return null;
        }

        $expected = (string) ($record['token_hash'] ?? '');
        $actual = hash('sha256', $token);
        if (!hash_equals($expected, $actual)) {
            return null;
        }

        delete_transient($prefix . $selector);
        return $record;
    }

    private function is_valid_timed_token($prefix, $selector, $token)
    {
        if ($selector === '' || $token === '') {
            return false;
        }

        $record = get_transient($prefix . $selector);
        if (!$record || !is_array($record)) {
            return false;
        }

        $expected = (string) ($record['token_hash'] ?? '');
        $actual = hash('sha256', $token);
        return hash_equals($expected, $actual);
    }

    private function is_locked_user($user_id)
    {
        return !empty(get_user_meta((int) $user_id, 'lc_locked', true));
    }

    private function is_primary_admin($user)
    {
        $email = strtolower((string) ($user->user_email ?? ''));
        $primary = defined('LC_PRIMARY_ADMIN_EMAIL') ? strtolower((string) LC_PRIMARY_ADMIN_EMAIL) : '';
        return $email !== '' && $email === $primary;
    }

    private function first_name($user)
    {
        $first = trim((string) get_user_meta((int) $user->ID, 'first_name', true));
        if ($first !== '') {
            return $first;
        }

        $display = trim((string) ($user->display_name ?? ''));
        if ($display !== '' && strpos($display, '@') === false) {
            $parts = preg_split('/\s+/', $display);
            return $parts[0] ?? $display;
        }

        $login = (string) ($user->user_login ?? 'User');
        return explode('@', $login)[0];
    }

    private function full_name($user)
    {
        $first = trim((string) get_user_meta((int) $user->ID, 'first_name', true));
        $last = trim((string) get_user_meta((int) $user->ID, 'last_name', true));
        $full = trim($first . ' ' . $last);
        if ($full !== '') {
            return $full;
        }

        $display = trim((string) ($user->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }

        return $this->first_name($user);
    }

    private function avatar_html($user_id, $size, $label)
    {
        $size = max(24, absint($size));
        $photo_id = absint(get_user_meta((int) $user_id, 'lc_profile_photo_id', true));
        if ($photo_id > 0) {
            $url = wp_get_attachment_image_url($photo_id, 'thumbnail');
            if ($url) {
                return '<img class="lc-user-avatar" src="' . esc_url($url) . '" alt="' . esc_attr($label) . '" width="' . esc_attr((string) $size) . '" height="' . esc_attr((string) $size) . '" />';
            }
        }

        return '<span class="lc-user-avatar lc-user-avatar-fallback" role="img" aria-label="' . esc_attr($label) . '" style="width:' . esc_attr((string) $size) . 'px;height:' . esc_attr((string) $size) . 'px;"><img class="lc-user-avatar-brand" src="https://5n2digital.com/wp-content/uploads/2024/01/Untitled-1-1.png" alt="5N2 emblem" /></span>';
    }

    private function log_event($category, $level, $message, $context = [])
    {
        do_action('lc_log_event', (string) $category, (string) $level, (string) $message, (array) $context);
    }

    private function notify_email($to, $template_key, $vars, $fallback_subject, $fallback_body)
    {
        if (class_exists('LC_Plugin')) {
            return LC_Plugin::instance()->send_templated_email($to, $template_key, $vars, $fallback_subject, $fallback_body);
        }

        $replace = [];
        foreach ((array) $vars as $key => $value) {
            $replace['{' . sanitize_key((string) $key) . '}'] = (string) $value;
        }
        $body = nl2br(strtr((string) $fallback_body, $replace));
        return wp_mail($to, $fallback_subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }

    private function current_url()
    {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'] ?? ''));
        $uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        return $scheme . $host . $uri;
    }
}
