<?php
/**
 * Plugin Name: Lead Console (5N2 Digital Internal)
 * Description: Internal-only lead console for 5N2 Digital. Not for public distribution.
 * Version: 0.1.0
 * Author: 5N2 Digital
 * License: Proprietary
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LC_PLUGIN_VERSION', '0.1.0');
define('LC_PLUGIN_FILE', __FILE__);
define('LC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LC_INTERNAL_BRAND_NAME', '5N2 Digital');

require_once LC_PLUGIN_PATH . 'includes/class-lc-plugin.php';

\LC\Plugin::bootstrap();
