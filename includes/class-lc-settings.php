<?php

namespace LC;

if (!defined('ABSPATH')) {
    exit;
}

class Settings
{
    public const OPTION_KEY = 'lc_settings';

    public static function init(): void
    {
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function default_values(): array
    {
        return [
            'internal_only' => 1,
            'brand_name' => '5N2 Digital',
            'install_domain' => '5n2digital.com',
            'free_tier_mode' => 1,
            'enable_live_apis' => 0,
            'google_places_api_key' => '',
            'pagespeed_api_key' => '',
            'max_places_per_run' => 200,
            'global_requests_per_minute' => 60,
            'per_domain_rps' => 1,
            'cache_ttl_days' => 30,
        ];
    }

    public static function get(): array
    {
        return wp_parse_args((array) get_option(self::OPTION_KEY, []), self::default_values());
    }

    public static function register_settings(): void
    {
        register_setting(
            'lc_settings_group',
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize'],
                'default' => self::default_values(),
            ]
        );

        add_settings_section('lc_general_section', __('Lead Console Controls', 'lead-console'), '__return_false', 'lead-console-settings');

        self::add_field('internal_only', __('Internal only mode', 'lead-console'), 'checkbox');
        self::add_field('brand_name', __('Brand display name', 'lead-console'), 'text');
        self::add_field('install_domain', __('Allowed domain fragment', 'lead-console'), 'text');
        self::add_field('free_tier_mode', __('Free-tier protection mode', 'lead-console'), 'checkbox');
        self::add_field('enable_live_apis', __('Enable live API calls', 'lead-console'), 'checkbox');
        self::add_field('google_places_api_key', __('Google Places API key', 'lead-console'), 'password');
        self::add_field('pagespeed_api_key', __('PageSpeed API key', 'lead-console'), 'password');
        self::add_field('max_places_per_run', __('Max places per run', 'lead-console'), 'number');
        self::add_field('global_requests_per_minute', __('Global requests per minute', 'lead-console'), 'number');
        self::add_field('per_domain_rps', __('Per-domain requests/second', 'lead-console'), 'number');
        self::add_field('cache_ttl_days', __('Cache TTL (days)', 'lead-console'), 'number');
    }

    private static function add_field(string $key, string $label, string $type): void
    {
        add_settings_field(
            $key,
            $label,
            [self::class, 'render_field'],
            'lead-console-settings',
            'lc_general_section',
            ['key' => $key, 'type' => $type]
        );
    }

    public static function sanitize(array $input): array
    {
        $defaults = self::default_values();

        return [
            'internal_only' => !empty($input['internal_only']) ? 1 : 0,
            'brand_name' => sanitize_text_field($input['brand_name'] ?? $defaults['brand_name']),
            'install_domain' => sanitize_text_field($input['install_domain'] ?? $defaults['install_domain']),
            'free_tier_mode' => !empty($input['free_tier_mode']) ? 1 : 0,
            'enable_live_apis' => !empty($input['enable_live_apis']) ? 1 : 0,
            'google_places_api_key' => sanitize_text_field($input['google_places_api_key'] ?? ''),
            'pagespeed_api_key' => sanitize_text_field($input['pagespeed_api_key'] ?? ''),
            'max_places_per_run' => max(25, min(1000, (int) ($input['max_places_per_run'] ?? $defaults['max_places_per_run']))),
            'global_requests_per_minute' => max(10, min(120, (int) ($input['global_requests_per_minute'] ?? $defaults['global_requests_per_minute']))),
            'per_domain_rps' => max(1, min(5, (int) ($input['per_domain_rps'] ?? $defaults['per_domain_rps']))),
            'cache_ttl_days' => max(1, min(60, (int) ($input['cache_ttl_days'] ?? $defaults['cache_ttl_days']))),
        ];
    }

    public static function render_field(array $args): void
    {
        $settings = self::get();
        $key = $args['key'];
        $type = $args['type'];
        $name = self::OPTION_KEY . '[' . $key . ']';
        $value = $settings[$key] ?? '';

        if ($type === 'checkbox') {
            echo '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked(1, (int) $value, false) . '/> ' . esc_html__('Enabled', 'lead-console') . '</label>';
            return;
        }

        printf(
            '<input type="%1$s" class="regular-text" name="%2$s" value="%3$s" autocomplete="off" />',
            esc_attr($type),
            esc_attr($name),
            esc_attr((string) $value)
        );
    }

    public static function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $siteUrl = wp_parse_url(home_url(), PHP_URL_HOST);
        $settings = self::get();
        $allowed = strpos((string) $siteUrl, (string) $settings['install_domain']) !== false;
        ?>
        <div class="wrap lc-wrap">
            <h1><?php echo esc_html__('Lead Console Settings', 'lead-console'); ?></h1>

            <?php if (!$allowed): ?>
                <div class="notice notice-error"><p><?php echo esc_html__('Warning: this site domain does not match your configured internal domain fragment.', 'lead-console'); ?></p></div>
            <?php endif; ?>

            <?php if (empty($settings['enable_live_apis'])): ?>
                <div class="notice notice-warning"><p><?php echo esc_html__('Live APIs are disabled. Runs will stay local/safe and avoid external API costs.', 'lead-console'); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('lc_settings_group');
                do_settings_sections('lead-console-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
