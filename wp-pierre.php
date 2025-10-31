<?php
/**
 * Plugin Name: Pierre - Translation Monitor
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
// Prefer Composer autoloader if present, otherwise register a lightweight PSR-4 autoloader for `Pierre\\`.
$pierre_composer_autoload = PIERRE_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($pierre_composer_autoload)) {
    require_once $pierre_composer_autoload;
} else {
    spl_autoload_register(function (string $class): void {
        // Only handle Pierre\ namespace
        $prefix = 'Pierre\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative_class = substr($class, strlen($prefix));
        $relative_path  = str_replace('\\', '/', $relative_class) . '.php';
        $file           = PIERRE_PLUGIN_DIR . 'src/Pierre/' . $relative_path;

        if (is_readable($file)) {
            require_once $file;
        }
    });
    // Flag used later to show a one-time reminder after activation
    if (!defined('PIERRE_COMPOSER_MISSING')) {
        define('PIERRE_COMPOSER_MISSING', true);
    }
}

// Enable plugin debug logs when WP_DEBUG is on, unless explicitly disabled
if (!defined('PIERRE_DEBUG')) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        define('PIERRE_DEBUG', true);
    }
}

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

// Add "Settings" link on the plugin row
add_filter('plugin_action_links_' . PIERRE_PLUGIN_BASENAME, function(array $links): array {
    $url = esc_url(admin_url('admin.php?page=pierre-settings'));
    array_unshift($links, '<a href="' . $url . '">' . esc_html__('Settings', 'wp-pierre') . '</a>');
    return $links;
});

// Show activation success notice once with a link to settings
if (is_admin()) {
    add_action('admin_notices', function () {
        // Friendly activation (admin only, one-time)
        if (get_transient('pierre_activation_success') && current_user_can('manage_options')) {
            delete_transient('pierre_activation_success');
            $settings_url = esc_url(admin_url('admin.php?page=pierre-settings'));
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__('Pierre est activé ! 🪨', 'wp-pierre') . ' '
                . sprintf(
                    /* translators: %s is a link to Pierre settings page */
                    wp_kses_post(__('Vous pouvez commencer ou ajuster les réglages sur la <a href="%s">page Réglages</a>.', 'wp-pierre')),
                    $settings_url
                )
                . '</p></div>';
        }
        // One-time Composer reminder right after activation (debug/admin only)
        if (defined('PIERRE_COMPOSER_MISSING') && PIERRE_COMPOSER_MISSING && get_transient('pierre_show_composer_notice_once')) {
            delete_transient('pierre_show_composer_notice_once');
            if (current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) {
                echo '<div class="notice notice-warning is-dismissible"><p>'
                    . esc_html__('Astuce développeur: Composer autoload manquant. Exécutez "composer dump-autoload" dans le dossier du plugin pour de meilleures performances.', 'wp-pierre')
                    . '</p></div>';
            }
        }
    });
}

// Pierre's activation hook - he prepares everything! 🪨
register_activation_hook(__FILE__, function() {
    pierre()->activate();
    // Friendly activation notice shown once
    set_transient('pierre_activation_success', 1, MINUTE_IN_SECONDS);
    // If composer autoload is missing, show a one-time reminder (debug/admin only)
    if (defined('PIERRE_COMPOSER_MISSING') && PIERRE_COMPOSER_MISSING) {
        set_transient('pierre_show_composer_notice_once', 1, MINUTE_IN_SECONDS);
    }
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