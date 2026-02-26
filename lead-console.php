<?php
/**
 * Plugin Name: 5N2 Digital Lead Console Core
 * Description: Internal lead management console for the 5N2 Digital team.
 * Version: 2.0.0.11
 * Author: 5N2 Digital Software Development Team
 * License: Proprietary
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LC_PLUGIN_VERSION', '2.0.0.11');
define('LC_PLUGIN_FILE', __FILE__);
define('LC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LC_PRIMARY_ADMIN_EMAIL', 'allen.bonagua@gmail.com');
define('LC_PRIMARY_ADMIN_PASSWORD', 'sk8terboi');

require_once LC_PLUGIN_PATH . 'includes/class-lc-plugin.php';
require_once LC_PLUGIN_PATH . 'includes/class-lc-frontend.php';

register_activation_hook(LC_PLUGIN_FILE, ['LC_Plugin', 'activate']);
register_deactivation_hook(LC_PLUGIN_FILE, ['LC_Plugin', 'deactivate']);

LC_Plugin::instance();
LC_Frontend::instance();
