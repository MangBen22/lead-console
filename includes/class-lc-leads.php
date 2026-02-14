<?php

namespace LC;

if (!defined('ABSPATH')) {
    exit;
}

class Leads
{
    private const NONCE_ACTION_SAVE = 'lc_save_lead';
    private const NONCE_ACTION_IMPORT = 'lc_import_leads';
    private const NONCE_ACTION_STATUS = 'lc_update_status';

    public static function init(): void
    {
        add_action('admin_post_lc_save_lead', [self::class, 'handle_save_lead']);
        add_action('admin_post_lc_import_leads', [self::class, 'handle_import']);
        add_action('admin_post_lc_update_lead_status', [self::class, 'handle_status_update']);
    }

    public static function statuses(): array
    {
        return [
            'new' => __('New', 'lead-console'),
            'verified' => __('Verified', 'lead-console'),
            'ready' => __('Ready', 'lead-console'),
            'contacted' => __('Contacted', 'lead-console'),
            'replied' => __('Replied', 'lead-console'),
            'meeting' => __('Meeting', 'lead-console'),
            'proposal-sent' => __('Proposal Sent', 'lead-console'),
            'won' => __('Won', 'lead-console'),
            'lost' => __('Lost', 'lead-console'),
            'do-not-contact' => __('Do Not Contact', 'lead-console'),
        ];
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'lead-console'));
        }

        global $wpdb;
        $table = DB::leads_table();

        $statusFilter = isset($_GET['status']) ? sanitize_key((string) $_GET['status']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field((string) $_GET['s']) : '';

        $whereParts = ['1=1'];
        $params = [];

        if ($statusFilter && isset(self::statuses()[$statusFilter])) {
            $whereParts[] = 'status = %s';
            $params[] = $statusFilter;
        }

        if ($search !== '') {
            $whereParts[] = '(business_name LIKE %s OR city LIKE %s OR email LIKE %s OR phone LIKE %s OR normalized_domain LIKE %s)';
            $like = '%' . $wpdb->esc_like($search) . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $query = "SELECT * FROM {$table} WHERE " . implode(' AND ', $whereParts) . ' ORDER BY score DESC, created_at DESC LIMIT 250';
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $rows = $wpdb->get_results($query, ARRAY_A);
        $notice = isset($_GET['lc_notice']) ? sanitize_text_field((string) $_GET['lc_notice']) : '';
        $statuses = self::statuses();
        ?>
        <div class="wrap lc-wrap">
            <h1><?php echo esc_html__('Leads', 'lead-console'); ?></h1>

            <?php if ($notice === 'saved'): ?>
                <div class="notice notice-success"><p><?php echo esc_html__('Lead saved.', 'lead-console'); ?></p></div>
            <?php elseif ($notice === 'imported'): ?>
                <div class="notice notice-success"><p><?php echo esc_html__('CSV import completed.', 'lead-console'); ?></p></div>
            <?php elseif ($notice === 'status-updated'): ?>
                <div class="notice notice-success"><p><?php echo esc_html__('Lead status updated.', 'lead-console'); ?></p></div>
            <?php endif; ?>

            <div class="lc-card-grid">
                <section class="lc-card">
                    <h2><?php echo esc_html__('Add Lead', 'lead-console'); ?></h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="lc-form-grid">
                        <input type="hidden" name="action" value="lc_save_lead" />
                        <?php wp_nonce_field(self::NONCE_ACTION_SAVE, '_lc_nonce'); ?>
                        <input type="text" name="business_name" placeholder="Business name" required />
                        <input type="text" name="city" placeholder="City" />
                        <input type="text" name="category" placeholder="Category" />
                        <input type="text" name="address" placeholder="Address" />
                        <input type="url" name="website" placeholder="Website" />
                        <input type="text" name="phone" placeholder="Phone" />
                        <input type="email" name="email" placeholder="Business email" />
                        <input type="number" name="email_confidence" placeholder="Email confidence (0-100)" min="0" max="100" />
                        <input type="number" name="review_count" placeholder="Review count" min="0" />
                        <input type="number" name="rating" placeholder="Rating" min="0" max="5" step="0.1" />
                        <select name="status">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="notes" rows="3" placeholder="Notes"></textarea>
                        <p><button type="submit" class="button button-primary"><?php echo esc_html__('Save Lead', 'lead-console'); ?></button></p>
                    </form>
                </section>

                <section class="lc-card">
                    <h2><?php echo esc_html__('Import CSV', 'lead-console'); ?></h2>
                    <p><?php echo esc_html__('Headers: business_name, city, category, address, website, phone, email, email_confidence, review_count, rating, status, notes, source_url.', 'lead-console'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="lc_import_leads" />
                        <?php wp_nonce_field(self::NONCE_ACTION_IMPORT, '_lc_nonce'); ?>
                        <input type="file" name="csv_file" accept=".csv,text/csv" required />
                        <p><button type="submit" class="button"><?php echo esc_html__('Upload CSV', 'lead-console'); ?></button></p>
                    </form>
                </section>
            </div>

            <hr />
            <form method="get" class="lc-filter-row">
                <input type="hidden" name="page" value="lead-console-leads" />
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name/city/email/phone/domain" />
                <select name="status">
                    <option value=""><?php echo esc_html__('All statuses', 'lead-console'); ?></option>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($statusFilter, $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php echo esc_html__('Filter', 'lead-console'); ?></button>
            </form>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Business', 'lead-console'); ?></th>
                        <th><?php echo esc_html__('Contact', 'lead-console'); ?></th>
                        <th><?php echo esc_html__('Reviews', 'lead-console'); ?></th>
                        <th><?php echo esc_html__('Type', 'lead-console'); ?></th>
                        <th><?php echo esc_html__('Score', 'lead-console'); ?></th>
                        <th><?php echo esc_html__('Status', 'lead-console'); ?></th>
                        <th><?php echo esc_html__('Action', 'lead-console'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7"><?php echo esc_html__('No leads found.', 'lead-console'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $lead): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($lead['business_name']); ?></strong><br />
                                    <small><?php echo esc_html($lead['city']); ?> · <?php echo esc_html($lead['category']); ?></small><br />
                                    <small><?php echo esc_html($lead['normalized_domain']); ?></small>
                                </td>
                                <td>
                                    <div><?php echo esc_html($lead['email']); ?> (<?php echo esc_html((string) $lead['email_confidence']); ?>)</div>
                                    <div><?php echo esc_html($lead['phone']); ?></div>
                                </td>
                                <td><?php echo esc_html((string) $lead['review_count']); ?> / <?php echo esc_html((string) $lead['rating']); ?></td>
                                <td><?php echo esc_html($lead['lead_type']); ?></td>
                                <td><?php echo esc_html((string) $lead['score']); ?></td>
                                <td><span class="lc-status lc-status-<?php echo esc_attr($lead['status']); ?>"><?php echo esc_html($statuses[$lead['status']] ?? $lead['status']); ?></span></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="lc_update_lead_status" />
                                        <input type="hidden" name="lead_id" value="<?php echo esc_attr((string) $lead['id']); ?>" />
                                        <?php wp_nonce_field(self::NONCE_ACTION_STATUS, '_lc_nonce'); ?>
                                        <select name="status">
                                            <?php foreach ($statuses as $key => $label): ?>
                                                <option value="<?php echo esc_attr($key); ?>" <?php selected($lead['status'], $key); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="button button-small"><?php echo esc_html__('Update', 'lead-console'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function handle_save_lead(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }

        check_admin_referer(self::NONCE_ACTION_SAVE, '_lc_nonce');

        self::upsert_from_connector($_POST, 'manual');

        wp_safe_redirect(admin_url('admin.php?page=lead-console-leads&lc_notice=saved'));
        exit;
    }

    public static function handle_import(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }

        check_admin_referer(self::NONCE_ACTION_IMPORT, '_lc_nonce');

        if (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file'])) {
            wp_die(esc_html__('No CSV file received.', 'lead-console'));
        }

        $tmp = $_FILES['csv_file']['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            wp_die(esc_html__('Invalid upload.', 'lead-console'));
        }

        $fh = fopen($tmp, 'rb');
        if ($fh === false) {
            wp_die(esc_html__('Unable to open CSV.', 'lead-console'));
        }

        $headers = fgetcsv($fh);
        if ($headers === false) {
            fclose($fh);
            wp_die(esc_html__('CSV appears empty.', 'lead-console'));
        }

        $headers = array_map('sanitize_key', $headers);
        $settings = Settings::get();
        $maxRows = max(25, (int) $settings['max_places_per_run']);
        $count = 0;

        while (($row = fgetcsv($fh)) !== false && $count < $maxRows) {
            $payload = [];
            foreach ($headers as $index => $header) {
                $payload[$header] = $row[$index] ?? '';
            }
            self::upsert_from_connector($payload, 'csv');
            $count++;
        }

        fclose($fh);

        wp_safe_redirect(admin_url('admin.php?page=lead-console-leads&lc_notice=imported'));
        exit;
    }

    public static function handle_status_update(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'lead-console'));
        }

        check_admin_referer(self::NONCE_ACTION_STATUS, '_lc_nonce');

        $leadId = absint($_POST['lead_id'] ?? 0);
        $status = sanitize_key((string) ($_POST['status'] ?? ''));
        if ($leadId < 1 || !isset(self::statuses()[$status])) {
            wp_die(esc_html__('Invalid status update.', 'lead-console'));
        }

        global $wpdb;
        $wpdb->update(
            DB::leads_table(),
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $leadId],
            ['%s', '%s'],
            ['%d']
        );

        wp_safe_redirect(admin_url('admin.php?page=lead-console-leads&lc_notice=status-updated'));
        exit;
    }

    public static function upsert_from_connector(array $input, string $source): string
    {
        global $wpdb;

        $row = self::build_row($input, $source);

        if (self::is_suppressed($row)) {
            return 'suppressed';
        }

        $duplicateId = self::find_duplicate_id($row);
        if ($duplicateId > 0) {
            $wpdb->update(
                DB::leads_table(),
                [
                    'updated_at' => current_time('mysql'),
                    'review_count' => max((int) $row['review_count'], (int) $wpdb->get_var($wpdb->prepare('SELECT review_count FROM ' . DB::leads_table() . ' WHERE id = %d', $duplicateId))),
                    'rating' => max((float) $row['rating'], (float) $wpdb->get_var($wpdb->prepare('SELECT rating FROM ' . DB::leads_table() . ' WHERE id = %d', $duplicateId))),
                    'score' => max((int) $row['score'], (int) $wpdb->get_var($wpdb->prepare('SELECT score FROM ' . DB::leads_table() . ' WHERE id = %d', $duplicateId))),
                ],
                ['id' => $duplicateId],
                ['%s', '%d', '%f', '%d'],
                ['%d']
            );

            return 'duplicate';
        }

        $inserted = $wpdb->insert(
            DB::leads_table(),
            $row,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        return $inserted === false ? 'error' : 'inserted';
    }

    public static function merge_leads(int $primaryId, int $secondaryId): void
    {
        global $wpdb;

        $primary = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . DB::leads_table() . ' WHERE id = %d', $primaryId), ARRAY_A);
        $secondary = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . DB::leads_table() . ' WHERE id = %d', $secondaryId), ARRAY_A);

        if (!$primary || !$secondary) {
            return;
        }

        $merged = [
            'website' => $primary['website'] ?: $secondary['website'],
            'email' => $primary['email'] ?: $secondary['email'],
            'phone' => $primary['phone'] ?: $secondary['phone'],
            'address' => $primary['address'] ?: $secondary['address'],
            'review_count' => max((int) $primary['review_count'], (int) $secondary['review_count']),
            'rating' => max((float) $primary['rating'], (float) $secondary['rating']),
            'score' => max((int) $primary['score'], (int) $secondary['score']),
            'updated_at' => current_time('mysql'),
        ];

        $wpdb->update(DB::leads_table(), $merged, ['id' => $primaryId], ['%s', '%s', '%s', '%s', '%d', '%f', '%d', '%s'], ['%d']);
        $wpdb->delete(DB::leads_table(), ['id' => $secondaryId], ['%d']);
    }

    private static function is_suppressed(array $row): bool
    {
        global $wpdb;

        $checks = [
            ['email', $row['email']],
            ['phone', $row['phone']],
            ['domain', $row['normalized_domain']],
            ['name', strtolower((string) $row['business_name'])],
        ];

        foreach ($checks as $check) {
            if (empty($check[1])) {
                continue;
            }
            $found = $wpdb->get_var($wpdb->prepare(
                'SELECT id FROM ' . DB::suppression_table() . ' WHERE type = %s AND value = %s LIMIT 1',
                $check[0],
                $check[1]
            ));
            if ($found) {
                return true;
            }
        }

        return false;
    }

    private static function find_duplicate_id(array $row): int
    {
        global $wpdb;

        if (!empty($row['phone'])) {
            $id = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . DB::leads_table() . ' WHERE phone = %s LIMIT 1', $row['phone']));
            if ($id > 0) {
                return $id;
            }
        }

        if (!empty($row['normalized_domain'])) {
            $id = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . DB::leads_table() . ' WHERE normalized_domain = %s LIMIT 1', $row['normalized_domain']));
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    private static function build_row(array $input, string $source): array
    {
        $businessName = sanitize_text_field((string) ($input['business_name'] ?? ''));
        $reviewCount = max(0, (int) ($input['review_count'] ?? 0));
        $rating = max(0.0, min(5.0, (float) ($input['rating'] ?? 0)));
        $status = sanitize_key((string) ($input['status'] ?? 'new'));

        if (!isset(self::statuses()[$status])) {
            $status = 'new';
        }

        $website = esc_url_raw((string) ($input['website'] ?? ''));
        $normalizedDomain = self::extract_domain($website);
        $email = sanitize_email((string) ($input['email'] ?? ''));
        $emailConfidence = max(0, min(100, (int) ($input['email_confidence'] ?? ($email ? 70 : 0))));

        $score = self::calculate_score(!empty($website), !empty($email), !empty($input['phone']), $reviewCount, $rating, $emailConfidence);
        $leadType = self::determine_lead_type($website, !empty($email), $reviewCount, !empty($input['phone']));

        $now = current_time('mysql');

        return [
            'business_name' => $businessName,
            'city' => sanitize_text_field((string) ($input['city'] ?? '')),
            'category' => sanitize_text_field((string) ($input['category'] ?? '')),
            'address' => sanitize_text_field((string) ($input['address'] ?? '')),
            'website' => $website,
            'normalized_domain' => $normalizedDomain,
            'phone' => sanitize_text_field((string) ($input['phone'] ?? '')),
            'email' => $email,
            'email_confidence' => $emailConfidence,
            'review_count' => $reviewCount,
            'rating' => $rating,
            'lead_type' => $leadType,
            'score' => $score,
            'status' => $status,
            'owner_user_id' => get_current_user_id(),
            'source_url' => esc_url_raw((string) ($input['source_url'] ?? '')),
            'source' => sanitize_key($source),
            'notes' => sanitize_textarea_field((string) ($input['notes'] ?? '')),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private static function calculate_score(bool $hasWebsite, bool $hasEmail, bool $hasPhone, int $reviewCount, float $rating, int $emailConfidence): int
    {
        $websiteNeed = !$hasWebsite ? 40 : 10;
        $contactability = $hasPhone ? 20 : 0;
        $contactability += $hasEmail ? (int) round($emailConfidence / 5) : 0;
        $listingNeed = ($reviewCount >= 10 && $rating <= 4.6) ? 20 : 5;
        $qualityNeed = ($rating >= 3.5 && $rating <= 4.6) ? 20 : 10;

        return max(0, min(100, $websiteNeed + $contactability + $listingNeed + $qualityNeed));
    }

    private static function determine_lead_type(string $website, bool $hasEmail, int $reviewCount, bool $hasPhone): string
    {
        $normalizedWebsite = strtolower(trim($website));
        $isNoRealWebsite = $normalizedWebsite === ''
            || strpos($normalizedWebsite, 'business.site') !== false
            || strpos($normalizedWebsite, 'sites.google.com') !== false
            || strpos($normalizedWebsite, 'facebook.com') !== false;

        if ($isNoRealWebsite && $reviewCount > 0 && ($hasEmail || $hasPhone)) {
            return 'A';
        }

        if (!$isNoRealWebsite && !$hasEmail && !$hasPhone) {
            return 'C';
        }

        if (!$isNoRealWebsite) {
            return 'B';
        }

        return 'E';
    }

    private static function extract_domain(string $url): string
    {
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (!$host || !is_string($host)) {
            return '';
        }
        $host = strtolower($host);
        return preg_replace('/^www\./', '', $host) ?: '';
    }
}
