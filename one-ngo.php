<?php
/**
 * Plugin Name: One NGO Fundraising
 * Plugin URI: https://1-ngo.uppfy.com/docs/integrations/wordpress
 * Description: Serve 1 NGO donate pages, campaigns, events, and stories on your WordPress site. No 1 NGO DNS required.
 * Version: 1.2.4
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: 1 NGO
 * Author URI: https://1-ngo.uppfy.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: one-ngo-fundraising
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ONE_NGO_VERSION', '1.2.4');
define('ONE_NGO_PLUGIN_FILE', __FILE__);
define('ONE_NGO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ONE_NGO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ONE_NGO_DEFAULT_API_BASE', '__ONE_NGO_API_BASE__');
define('ONE_NGO_EMBED_ORIGIN', 'https://1-ngo.uppfy.com');
define('ONE_NGO_DOCS_URL', 'https://1-ngo.uppfy.com/docs/integrations/wordpress');
define('ONE_NGO_PRIVACY_URL', 'https://uppfy.com/1-ngo/privacy-policy');
define('ONE_NGO_TERMS_URL', 'https://uppfy.com/1-ngo/terms-of-use');

require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-api.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-heartbeat.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-routes.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-pages.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-public.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-shortcodes.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-admin.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-rest.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-blocks.php';
require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-elementor.php';

add_action('plugins_loaded', static function (): void {
    One_Ngo_Heartbeat::init();
    One_Ngo_Routes::init();
    One_Ngo_Public::init();
    One_Ngo_Shortcodes::init();
    One_Ngo_Admin::init();
    One_Ngo_Rest::init();
    One_Ngo_Blocks::init();
    One_Ngo_Elementor::init();
});

register_activation_hook(ONE_NGO_PLUGIN_FILE, static function (): void {
    One_Ngo_Routes::register();
    flush_rewrite_rules();
});

register_deactivation_hook(ONE_NGO_PLUGIN_FILE, static function (): void {
    flush_rewrite_rules();
});
