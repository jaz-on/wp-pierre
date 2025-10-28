<?php
/**
 * Plugin Name: Pierre - WordPress Translation Monitor
 * Plugin URI: https://github.com/jaz-on/wp-pierre
 * Description: Pierre monitors WordPress Polyglots translations and notifies teams via Slack 🪨
 * Version: 1.0.0
 * Author: Jason Rouet
 * Author URI: https://profiles.wordpress.org/jaz_on/
 * Author Email: bonjour@jasonrouet.com
 * Author Website: https://jasonrouet.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-pierre
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.3
 * Network: false
 *
 * @package Pierre
 * @since 1.0.0
 */

// Pierre says: No direct access allowed! 🪨
if (!defined('ABSPATH')) {
    exit;
}

// Define Pierre's constants - he needs them to work properly! 🪨
define('PIERRE_VERSION', '1.0.0');
define('PIERRE_PLUGIN_FILE', __FILE__);
define('PIERRE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PIERRE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PIERRE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Pierre's autoloader - he's quite organized! 🪨
require_once PIERRE_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Pierre's main function - where the magic begins! 🪨
 * 
 * @since 1.0.0
 * @return Pierre\Plugin
 */
function pierre(): Pierre\Plugin {
    static $instance = null;
    
    if (null === $instance) {
        $instance = new Pierre\Plugin();
    }
    
    return $instance;
}

// Pierre starts working as soon as WordPress is ready! 🪨
add_action('plugins_loaded', function() {
    pierre()->init();
});

// Pierre's activation hook - he prepares everything! 🪨
register_activation_hook(__FILE__, function() {
    pierre()->activate();
});

// Pierre's deactivation hook - he cleans up after himself! 🪨
register_deactivation_hook(__FILE__, function() {
    pierre()->deactivate();
});

// Pierre's uninstall hook - he removes everything when asked! 🪨
register_uninstall_hook(__FILE__, 'pierre_uninstall_hook');

/**
 * Pierre's uninstall hook function - he removes everything! 🪨
 * 
 * @since 1.0.0
 * @return void
 */
function pierre_uninstall_hook(): void {
    if (function_exists('pierre')) {
        pierre()->uninstall();
    }
}