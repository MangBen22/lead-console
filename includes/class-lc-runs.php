<?php

namespace LC;

if (!defined('ABSPATH')) {
    exit;
}

class Runs
{
    private const NONCE_START = 'lc_start_run';

    public static function init(): void
    {
        add_action('admin_post_lc_start_run', [self::class, 'handle_start_run']);
        add_action('lc_process_run', [self::class, 'process_run']);
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }

        global $wpdb;
        $runs = $wpdb->get_results('SELECT * FROM ' . DB::runs_table() . ' ORDER BY id DESC LIMIT 50', ARRAY_A);

        ?>
        <div class="wrap lc-wrap">
            <h1><?php echo esc_html__('Discovery Runs', 'lead-console'); ?></h1>
            <div class="lc-card-grid">
                <section class="lc-card">
                    <h2><?php echo esc_html__('Start Run', 'lead-console'); ?></h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="lc-form-grid">
                        <input type="hidden" name="action" value="lc_start_run" />
                        <?php wp_nonce_field(self::NONCE_START, '_lc_nonce'); ?>
                        <select name="provider">
                            <option value="google_places">Google Places</option>
                        </select>
                        <input type="text" name="query" placeholder="Keyword or niche (e.g. plumber)" required />
                        <input type="text" name="city" placeholder="City (e.g. Cebu)" required />
                        <input type="number" name="limit" min="25" max="1000" value="200" />
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Queue Run', 'lead-console'); ?></button>
                    </form>
                </section>
            </div>

            <h2><?php echo esc_html__('Recent Runs', 'lead-console'); ?></h2>
            <table class="widefat fixed striped">
                <thead><tr><th>ID</th><th>Provider</th><th>Query</th><th>Status</th><th>Processed</th><th>Inserted</th><th>Duplicates</th><th>Errors</th><th>Created</th></tr></thead>
                <tbody>
                <?php if (empty($runs)): ?>
                    <tr><td colspan="9"><?php echo esc_html__('No runs yet.', 'lead-console'); ?></td></tr>
                <?php else: foreach ($runs as $run): ?>
                    <tr>
                        <td><?php echo esc_html((string) $run['id']); ?></td>
                        <td><?php echo esc_html($run['provider']); ?></td>
                        <td><?php echo esc_html(trim($run['query_text'] . ' ' . $run['city'])); ?></td>
                        <td><?php echo esc_html($run['status']); ?></td>
                        <td><?php echo esc_html((string) $run['processed_count']); ?></td>
                        <td><?php echo esc_html((string) $run['inserted_count']); ?></td>
                        <td><?php echo esc_html((string) $run['duplicate_count']); ?></td>
                        <td><?php echo esc_html((string) $run['error_count']); ?></td>
                        <td><?php echo esc_html($run['created_at']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function handle_start_run(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }

        check_admin_referer(self::NONCE_START, '_lc_nonce');

        $provider = sanitize_key((string) ($_POST['provider'] ?? 'google_places'));
        $query = sanitize_text_field((string) ($_POST['query'] ?? ''));
        $city = sanitize_text_field((string) ($_POST['city'] ?? ''));
        $requestedLimit = max(25, (int) ($_POST['limit'] ?? 200));

        $settings = Settings::get();
        $limit = min($requestedLimit, (int) $settings['max_places_per_run']);

        global $wpdb;
        $now = current_time('mysql');

        $wpdb->insert(
            DB::runs_table(),
            [
                'provider' => $provider,
                'query_text' => $query,
                'city' => $city,
                'status' => 'queued',
                'requested_limit' => $limit,
                'budget_limited' => $limit < $requestedLimit ? 1 : 0,
                'payload' => wp_json_encode(['query' => $query, 'city' => $city]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );

        $runId = (int) $wpdb->insert_id;

        wp_schedule_single_event(time() + 2, 'lc_process_run', [$runId]);

        wp_safe_redirect(admin_url('admin.php?page=lead-console-runs'));
        exit;
    }

    public static function process_run(int $runId): void
    {
        global $wpdb;
        $run = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . DB::runs_table() . ' WHERE id = %d', $runId), ARRAY_A);

        if (!$run) {
            return;
        }

        $wpdb->update(DB::runs_table(), ['status' => 'running', 'updated_at' => current_time('mysql')], ['id' => $runId], ['%s', '%s'], ['%d']);

        $payload = json_decode((string) $run['payload'], true);
        $payload = is_array($payload) ? $payload : [];

        $items = Connectors::discover((string) $run['provider'], $payload, (int) $run['requested_limit']);

        $processed = 0;
        $inserted = 0;
        $duplicates = 0;
        $errors = 0;

        foreach ($items as $item) {
            $processed++;
            $result = Leads::upsert_from_connector($item, (string) $run['provider']);
            if ($result === 'inserted') {
                $inserted++;
            } elseif ($result === 'duplicate') {
                $duplicates++;
            } else {
                $errors++;
            }
        }

        if (empty($items)) {
            self::log($runId, 'warning', __('Run returned no results. Check API keys or enable live APIs in settings.', 'lead-console'));
        }

        $wpdb->update(
            DB::runs_table(),
            [
                'status' => 'completed',
                'processed_count' => $processed,
                'inserted_count' => $inserted,
                'duplicate_count' => $duplicates,
                'error_count' => $errors,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $runId],
            ['%s', '%d', '%d', '%d', '%d', '%s'],
            ['%d']
        );
    }

    private static function log(int $runId, string $level, string $message): void
    {
        global $wpdb;
        $wpdb->insert(
            DB::run_logs_table(),
            [
                'run_id' => $runId,
                'level' => sanitize_key($level),
                'message' => sanitize_text_field($message),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s']
        );
    }
}
