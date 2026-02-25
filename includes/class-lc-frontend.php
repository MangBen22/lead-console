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
        add_action('admin_post_nopriv_lc_frontend_reset_password', [$this, 'handle_reset_password']);
        add_action('admin_post_lc_frontend_reset_password', [$this, 'handle_reset_password']);
        add_action('admin_post_nopriv_lc_frontend_revoke_password_change', [$this, 'handle_revoke_password_change']);
        add_action('admin_post_lc_frontend_revoke_password_change', [$this, 'handle_revoke_password_change']);
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
            'reset_email_not_found' => 'Email not found in user database.',
            'reset_sent' => 'Password reset email sent. The link is valid for 5 minutes.',
            'reset_link_invalid' => 'Reset link is invalid or expired.',
            'reset_success' => 'Password updated successfully. Please log in with your new password.',
            'reset_revoke_success' => 'Password change revoked. Account is now locked pending super admin reset.',
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
        echo '<label>Password<div class="lc-password-row"><input class="lc-password-input" type="password" name="pwd" required /><button type="button" class="lc-toggle-password" aria-label="Show password">Show</button></div></label>';
        echo '<label class="lc-check"><input type="checkbox" name="accept_gdpr" value="1" required /> I agree to GDPR-compliant processing for authorized business operations.</label>';
        echo '<button type="submit">Sign In</button>';
        echo '</form>';
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

        $lead_rows = $wpdb->get_results("SELECT id,business_name,city,category,phone,email,score,lead_type,status FROM {$leads_table} ORDER BY id DESC LIMIT 20");
        $run_rows = $wpdb->get_results("SELECT id,query_text,city,max_places,status,created_at FROM {$runs_table} ORDER BY id DESC LIMIT 10");

        $settings = $this->settings();
        $user = wp_get_current_user();
        $first_name = $this->first_name($user);

        echo '<header class="lc-fe-hero">';
        echo '<div>';
        echo '<img class="lc-logo lc-logo-hero" src="https://5n2digital.com/wp-content/uploads/2024/01/Untitled-1-1.png" alt="5N2 Digital logo" />';
        echo '<h1>Hello, ' . esc_html($first_name) . '</h1>';
        echo '<p class="lc-hero-sub">We bridge creativity and structure into actionable systems.</p>';
        echo '<p>Welcome to 5N2 Digital Lead Console.</p>';
        echo '</div>';
        echo '<div class="lc-fe-actions">';
        echo '<button type="button" class="lc-open-tutorial lc-btn-tutorial">Start Tutorial</button>';
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
        echo '<article class="lc-fe-card" id="lc-section-leads">';
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

        echo '<article class="lc-fe-card" id="lc-section-runs">';
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

        echo '<div class="lc-fe-card" id="lc-section-compliance" style="margin-bottom:10px;">';
        echo '<p class="lc-card-kicker">// COMPLIANCE</p>';
        echo '<h3>Compliance Guidance</h3>';
        echo '<p>Use only lawful/public data and provider-approved APIs. Do not use prohibited scraping or unauthorized automation on third-party platforms.</p>';
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

        update_user_meta((int) $user->ID, 'lc_gdpr_accepted', 1);
        update_user_meta((int) $user->ID, 'lc_gdpr_accepted_at', current_time('mysql'));
        $this->log_event('compliance', 'info', 'GDPR consent captured on login.', ['user_id' => (int) $user->ID]);
        $this->log_event('auth', 'info', 'Login successful.', ['user_id' => (int) $user->ID]);

        wp_safe_redirect($this->redirect_target());
        exit;
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

        $subject = '5N2 Lead Console Password Reset';
        $message = "A password reset was requested for your account.\n\n";
        $message .= "Reset link (valid for 5 minutes):\n" . esc_url_raw($reset_link) . "\n\n";
        $message .= "If you did not request this, ignore this email.";
        wp_mail($user->user_email, $subject, $message);
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

        $subject_user = '5N2 Lead Console Password Changed';
        $message_user = "Your password was changed.\n\n";
        $message_user .= "If this was NOT you, click this revoke link within 5 minutes:\n" . esc_url_raw($revoke_link) . "\n\n";
        $message_user .= "After 5 minutes, the revoke link expires.";
        wp_mail($user->user_email, $subject_user, $message_user);

        $admin_email = defined('LC_PRIMARY_ADMIN_EMAIL') ? LC_PRIMARY_ADMIN_EMAIL : get_option('admin_email');
        $subject_admin = 'Lead Console Alert: Password changed';
        $message_admin = "User password changed:\nEmail: {$user->user_email}\nTime: " . current_time('mysql') . "\n";
        $message_admin .= "Revoke window: 5 minutes.";
        wp_mail($admin_email, $subject_admin, $message_admin);
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
            $subject = 'Lead Console Alert: Password change revoked';
            $message = "A password change was revoked by user confirmation.\n";
            $message .= "User: {$user->user_email}\n";
            $message .= "Account locked. Super admin manual reset is required.";
            wp_mail($admin_email, $subject, $message);
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
        $requested_max = absint($_POST['max_places'] ?? 0);
        $max_places = min(max(1, $requested_max ?: (int) $settings['max_places_per_run']), (int) $settings['max_places_per_run']);

        $wpdb->insert($this->table('lc_runs'), [
            'query_text' => sanitize_text_field($_POST['query_text'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'max_places' => $max_places,
            'status' => 'queued',
        ]);

        $this->log_event('runs', 'info', 'Run queued from frontend.', ['query' => sanitize_text_field($_POST['query_text'] ?? ''), 'city' => sanitize_text_field($_POST['city'] ?? '')]);
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

    private function has_reset_query()
    {
        return !empty($_GET['lc_reset']) && !empty($_GET['selector']) && !empty($_GET['token']);
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

    private function log_event($category, $level, $message, $context = [])
    {
        do_action('lc_log_event', (string) $category, (string) $level, (string) $message, (array) $context);
    }

    private function current_url()
    {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'] ?? ''));
        $uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        return $scheme . $host . $uri;
    }
}
