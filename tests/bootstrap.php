<?php
/**
 * Pierre's test bootstrap - he prepares everything for testing! 🪨
 * 
 * This file sets up the testing environment for Pierre's plugin.
 * He makes sure everything is ready for testing!
 * 
 * @package Pierre\Tests
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../');
}

// Pierre defines his constants! 🪨
if (!defined('PIERRE_VERSION')) {
    define('PIERRE_VERSION', '1.0.0');
}

if (!defined('PIERRE_PLUGIN_FILE')) {
    define('PIERRE_PLUGIN_FILE', dirname(__FILE__) . '/../wp-pierre.php');
}

if (!defined('PIERRE_PLUGIN_DIR')) {
    define('PIERRE_PLUGIN_DIR', dirname(__FILE__) . '/../');
}

if (!defined('PIERRE_PLUGIN_URL')) {
    define('PIERRE_PLUGIN_URL', 'http://localhost/wp-content/plugins/wp-pierre/');
}

if (!defined('PIERRE_PLUGIN_BASENAME')) {
    define('PIERRE_PLUGIN_BASENAME', 'wp-pierre/wp-pierre.php');
}

// Pierre includes his autoloader! 🪨
require_once PIERRE_PLUGIN_DIR . 'vendor/autoload.php';

// Pierre mocks WordPress functions! 🪨
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = []) {
        throw new Exception('wp_die called: ' . $message);
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        // Pierre logs to console in tests! 🪨
        echo "Pierre's Log: " . $message . "\n";
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient($transient) {
        return false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        return true;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = []) {
        return true;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = []) {
        return time() + 3600; // Pierre returns future time! 🪨
    }
}

if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event($timestamp, $hook, $args = []) {
        return true;
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook, $args = []) {
        return true;
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        return [
            'body' => '{"test": "data"}',
            'response' => ['code' => 200],
            'headers' => []
        ];
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = []) {
        return [
            'body' => '{"ok": true}',
            'response' => ['code' => 200],
            'headers' => []
        ];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return 200;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'] ?? '';
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        return (object) [
            'ID' => 1,
            'display_name' => 'Pierre Test User 🪨',
            'user_email' => 'pierre@test.com'
        ];
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true; // Pierre allows everything in tests! 🪨
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key) {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true; // Pierre trusts nonces in tests! 🪨
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {
        echo json_encode(['success' => false, 'data' => $data]);
        exit;
    }
}

// Pierre logs his bootstrap completion! 🪨
error_log('Pierre bootstrapped his test environment! 🪨');
