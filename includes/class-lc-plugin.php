<?php

namespace LC;

if (!defined('ABSPATH')) {
    exit;
}

require_once LC_PLUGIN_PATH . 'includes/class-lc-settings.php';
require_once LC_PLUGIN_PATH . 'includes/class-lc-db.php';
require_once LC_PLUGIN_PATH . 'includes/class-lc-leads.php';
require_once LC_PLUGIN_PATH . 'includes/class-lc-connectors.php';
require_once LC_PLUGIN_PATH . 'includes/class-lc-runs.php';
require_once LC_PLUGIN_PATH . 'includes/class-lc-duplicates.php';
require_once LC_PLUGIN_PATH . 'includes/class-lc-exports.php';

class Plugin
{
    public static function bootstrap(): void
    {
        $instance = new self();
        $instance->hooks();
    }

    private function hooks(): void
    {
        register_activation_hook(LC_PLUGIN_FILE, [$this, 'activate']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_notices', [$this, 'show_internal_notice']);

        Settings::init();
        Leads::init();
        Runs::init();
        Duplicates::init();
        Exports::init();
    }

    public function activate(): void
    {
        if (!get_option(Settings::OPTION_KEY)) {
            add_option(Settings::OPTION_KEY, Settings::default_values());
        }

        DB::install();
    }

    public function register_admin_menu(): void
    {
        add_menu_page(__('Lead Console', 'lead-console'), __('Lead Console', 'lead-console'), 'manage_options', 'lead-console', [$this, 'render_dashboard'], 'dashicons-filter', 26);
        add_submenu_page('lead-console', __('Leads', 'lead-console'), __('Leads', 'lead-console'), 'manage_options', 'lead-console-leads', [Leads::class, 'render_page']);
        add_submenu_page('lead-console', __('Runs', 'lead-console'), __('Runs', 'lead-console'), 'manage_options', 'lead-console-runs', [Runs::class, 'render_page']);
        add_submenu_page('lead-console', __('Duplicates', 'lead-console'), __('Duplicates', 'lead-console'), 'manage_options', 'lead-console-duplicates', [Duplicates::class, 'render_page']);
        add_submenu_page('lead-console', __('Exports', 'lead-console'), __('Exports', 'lead-console'), 'manage_options', 'lead-console-exports', [Exports::class, 'render_page']);
        add_submenu_page('lead-console', __('Settings', 'lead-console'), __('Settings', 'lead-console'), 'manage_options', 'lead-console-settings', [Settings::class, 'render_settings_page']);
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if (strpos($hook, 'lead-console') === false) {
            return;
        }

        wp_enqueue_style('lead-console-admin', LC_PLUGIN_URL . 'assets/admin.css', [], LC_PLUGIN_VERSION);
    }

    public function show_internal_notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || strpos($screen->id, 'lead-console') === false) {
            return;
        }

        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Internal use only.', 'lead-console') . '</strong> ' .
            esc_html__('This plugin is intended exclusively for 5N2 Digital operations and is not licensed for public distribution.', 'lead-console') .
            '</p></div>';
    }

    public function render_dashboard(): void
    {
        global $wpdb;

        $totalLeads = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . DB::leads_table());
        $readyLeads = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . DB::leads_table() . ' WHERE status = %s', 'ready'));
        $avgScore = (float) $wpdb->get_var('SELECT AVG(score) FROM ' . DB::leads_table());
        $runs = (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . DB::runs_table());
        $lastRunStatus = (string) $wpdb->get_var('SELECT status FROM ' . DB::runs_table() . ' ORDER BY id DESC LIMIT 1');
        $lastRunStatus = $lastRunStatus ?: 'n/a';

        $settings = Settings::get();
        ?>
        <div class="wrap lc-wrap">
            <h1><?php echo esc_html__('Lead Console', 'lead-console'); ?></h1>
            <p class="lc-brand"><?php echo esc_html__('5N2 Digital internal lead engine dashboard.', 'lead-console'); ?></p>

            <div class="lc-card-grid">
                <section class="lc-card">
                    <h2><?php echo esc_html__('Pipeline Snapshot', 'lead-console'); ?></h2>
                    <ul>
                        <li><?php echo esc_html(sprintf(__('Total leads: %d', 'lead-console'), $totalLeads)); ?></li>
                        <li><?php echo esc_html(sprintf(__('Ready leads: %d', 'lead-console'), $readyLeads)); ?></li>
                        <li><?php echo esc_html(sprintf(__('Average score: %.1f', 'lead-console'), $avgScore)); ?></li>
                    </ul>
                </section>

                <section class="lc-card">
                    <h2><?php echo esc_html__('Discovery Runs', 'lead-console'); ?></h2>
                    <ul>
                        <li><?php echo esc_html(sprintf(__('Total runs: %d', 'lead-console'), $runs)); ?></li>
                        <li><?php echo esc_html(sprintf(__('Last run status: %s', 'lead-console'), $lastRunStatus)); ?></li>
                    </ul>
                    <p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=lead-console-runs')); ?>"><?php echo esc_html__('Open Runs', 'lead-console'); ?></a></p>
                </section>

                <section class="lc-card">
                    <h2><?php echo esc_html__('Free-tier Guardrails', 'lead-console'); ?></h2>
                    <ul>
                        <li><?php echo esc_html(sprintf(__('Max places per run: %d', 'lead-console'), (int) $settings['max_places_per_run'])); ?></li>
                        <li><?php echo esc_html(sprintf(__('Global requests/minute: %d', 'lead-console'), (int) $settings['global_requests_per_minute'])); ?></li>
                        <li><?php echo esc_html(sprintf(__('Per-domain RPS: %d', 'lead-console'), (int) $settings['per_domain_rps'])); ?></li>
                        <li><?php echo esc_html(sprintf(__('Live APIs enabled: %s', 'lead-console'), !empty($settings['enable_live_apis']) ? 'yes' : 'no')); ?></li>
                    </ul>
                </section>
            </div>
        </div>
        <?php
    }
}
