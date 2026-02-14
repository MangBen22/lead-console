<?php

namespace LC;

if (!defined('ABSPATH')) {
    exit;
}

class Duplicates
{
    private const NONCE_MERGE = 'lc_merge_duplicate';

    public static function init(): void
    {
        add_action('admin_post_lc_merge_duplicate', [self::class, 'handle_merge']);
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }

        global $wpdb;
        $table = DB::leads_table();

        $rows = $wpdb->get_results(
            "SELECT a.id AS a_id, b.id AS b_id, a.business_name AS a_name, b.business_name AS b_name, a.phone, a.normalized_domain
             FROM {$table} a
             INNER JOIN {$table} b ON a.id < b.id
             WHERE (a.phone <> '' AND a.phone = b.phone)
                OR (a.normalized_domain <> '' AND a.normalized_domain = b.normalized_domain)
             ORDER BY a.id DESC
             LIMIT 100",
            ARRAY_A
        );
        ?>
        <div class="wrap lc-wrap">
            <h1><?php echo esc_html__('Possible Duplicates', 'lead-console'); ?></h1>
            <table class="widefat fixed striped">
                <thead><tr><th>Lead A</th><th>Lead B</th><th>Match</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="4"><?php echo esc_html__('No likely duplicates found.', 'lead-console'); ?></td></tr>
                <?php else: foreach ($rows as $row): ?>
                    <tr>
                        <td>#<?php echo esc_html((string) $row['a_id']); ?> — <?php echo esc_html($row['a_name']); ?></td>
                        <td>#<?php echo esc_html((string) $row['b_id']); ?> — <?php echo esc_html($row['b_name']); ?></td>
                        <td><?php echo esc_html($row['phone'] ?: $row['normalized_domain']); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="lc_merge_duplicate" />
                                <input type="hidden" name="primary_id" value="<?php echo esc_attr((string) $row['a_id']); ?>" />
                                <input type="hidden" name="secondary_id" value="<?php echo esc_attr((string) $row['b_id']); ?>" />
                                <?php wp_nonce_field(self::NONCE_MERGE, '_lc_nonce'); ?>
                                <button type="submit" class="button button-small"><?php echo esc_html__('Merge', 'lead-console'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function handle_merge(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }

        check_admin_referer(self::NONCE_MERGE, '_lc_nonce');

        $primary = absint($_POST['primary_id'] ?? 0);
        $secondary = absint($_POST['secondary_id'] ?? 0);

        if ($primary < 1 || $secondary < 1 || $primary === $secondary) {
            wp_die(esc_html__('Invalid merge request.', 'lead-console'));
        }

        Leads::merge_leads($primary, $secondary);

        wp_safe_redirect(admin_url('admin.php?page=lead-console-duplicates'));
        exit;
    }
}
