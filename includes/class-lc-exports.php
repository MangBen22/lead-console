<?php

namespace LC;

if (!defined('ABSPATH')) {
    exit;
}

class Exports
{
    private const NONCE_EXPORT = 'lc_export_ready';

    public static function init(): void
    {
        add_action('admin_post_lc_export_ready', [self::class, 'handle_export']);
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }
        ?>
        <div class="wrap lc-wrap">
            <h1><?php echo esc_html__('Outreach Export', 'lead-console'); ?></h1>
            <div class="lc-card">
                <p><?php echo esc_html__('Export ready/contactable leads as CSV for outreach tools.', 'lead-console'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="lc_export_ready" />
                    <?php wp_nonce_field(self::NONCE_EXPORT, '_lc_nonce'); ?>
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Download CSV', 'lead-console'); ?></button>
                </form>
            </div>
        </div>
        <?php
    }

    public static function handle_export(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }

        check_admin_referer(self::NONCE_EXPORT, '_lc_nonce');

        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT business_name, city, category, website, phone, email, score, lead_type, status, source_url
             FROM " . DB::leads_table() . "
             WHERE status IN ('ready', 'verified', 'contacted')
               AND (email <> '' OR phone <> '')
             ORDER BY score DESC, id DESC",
            ARRAY_A
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=lead-console-ready-' . gmdate('Ymd-His') . '.csv');

        $out = fopen('php://output', 'wb');
        fputcsv($out, ['business_name', 'city', 'category', 'website', 'phone', 'email', 'score', 'lead_type', 'status', 'source_url']);

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }
}
