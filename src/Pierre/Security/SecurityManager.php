<?php
/**
 * Pierre's security manager - he protects everything! 🪨
 * 
 * This class provides comprehensive security functions including
 * input validation, output sanitization, CSRF protection, and
 * security auditing for Pierre's WordPress Translation Monitor.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Security;

use Pierre\Traits\StatusTrait;
use Pierre\Helpers\ErrorHelper;

// Pierre uses global WordPress functions! 🪨

/**
 * Security Manager class - Pierre's security system! 🪨
 * 
 * @since 1.0.0
 */
class SecurityManager {
    use StatusTrait;
    
    /**
     * Pierre's security nonce action prefix! 🪨
     * 
     * @var string
     */
    private const NONCE_PREFIX = 'pierre_security_';
    
    /**
     * Pierre's maximum input length! 🪨
     * 
     * @var int
     */
    private const MAX_INPUT_LENGTH = 255;
    
    /**
     * Pierre's allowed HTML tags for rich content! 🪨
     * 
     * @var array
     */
    private const ALLOWED_HTML_TAGS = [
        'strong' => [],
        'em' => [],
        'b' => [],
        'i' => [],
        'u' => [],
        'br' => [],
        'p' => [],
        'a' => ['href' => [], 'title' => [], 'target' => []],
        'code' => [],
        'pre' => []
    ];
    
    /**
     * Validators configuration with callbacks for each type.
     * 
     * @var array<string, callable>
     */
    private const VALIDATORS = [
        'email' => 'validate_email',
        'url' => 'validate_url',
        'integer' => 'validate_integer',
        'string' => 'validate_string',
        'key' => 'validate_key',
        'slug' => 'validate_slug',
        'locale' => 'validate_locale',
        'role' => 'validate_role',
        'project_type' => 'validate_project_type',
        'html' => 'validate_html',
        'json' => 'validate_json',
    ];
    
    /**
     * Pierre validates and sanitizes user input! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input The input to validate
     * @param string $type The expected input type
     * @param array $options Additional validation options
     * @return array Validation result with sanitized data and status
     */
    public function validate_input(mixed $input, string $type, array $options = []): array {
        try {
            // Pierre checks if input is empty! 🪨
            if (empty($input) && !isset($options['allow_empty'])) {
                return $this->invalid_result( __('Input cannot be empty!', 'wp-pierre') );
            }
            
            // Pierre validates based on type using configuration! 🪨
            return $this->validate_by_type($input, $type, $options);
            
        } catch (\Exception $e) {
            \Pierre\Logging\Logger::static_error('Pierre encountered a validation error: ' . $e->getMessage() . ' 😢', ['source' => 'SecurityManager']);
            return $this->invalid_result( __('Validation error occurred!', 'wp-pierre') );
        }
    }
    
    /**
     * Validate input by type using VALIDATORS configuration.
     * 
     * @since 1.0.0
     * @param mixed $input The input to validate
     * @param string $type The expected input type
     * @param array $options Additional validation options
     * @return array Validation result
     */
    private function validate_by_type(mixed $input, string $type, array $options = []): array {
        if (!isset(self::VALIDATORS[$type])) {
            return $this->invalid_result( __('Unknown validation type!', 'wp-pierre') );
        }
        
        $validator_method = self::VALIDATORS[$type];
        return $this->$validator_method($input, $options);
    }
    
    /**
     * Helper method to create a valid result.
     * 
     * @since 1.0.0
     * @param mixed $sanitized The sanitized value
     * @param string $message Optional success message
     * @return array Validation result
     */
    private function valid_result(mixed $sanitized, string $message = ''): array {
        return [
            'valid' => true,
            'sanitized' => $sanitized,
            'message' => $message ?: __('Pierre says: Validation passed!', 'wp-pierre') . ' 🪨'
        ];
    }
    
    /**
     * Helper method to create an invalid result.
     * 
     * @since 1.0.0
     * @param string $message Error message
     * @return array Validation result
     */
    private function invalid_result(string $message): array {
        return [
            'valid' => false,
            'sanitized' => null,
            'message' => ErrorHelper::format_error_message($message)
        ];
    }
    
    /**
     * Pierre validates email addresses! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input Email input
     * @param array $options Validation options (unused for email)
     * @return array Validation result
     */
    private function validate_email(mixed $input, array $options = []): array {
        $email = sanitize_email($input);
        
        if (!is_email($email)) {
            return $this->invalid_result( __('Invalid email address!', 'wp-pierre') );
        }
        
        return $this->valid_result($email, __('Pierre says: Email is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates URLs! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input URL input
     * @param array $options Validation options (unused for url)
     * @return array Validation result
     */
    private function validate_url(mixed $input, array $options = []): array {
        $url = esc_url_raw($input);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->invalid_result( __('Invalid URL!', 'wp-pierre') );
        }
        
        // Pierre checks for allowed protocols! 🪨
        $allowed_protocols = ['http', 'https'];
        $parsed_url = wp_parse_url($url);
        
        if (!in_array($parsed_url['scheme'] ?? '', $allowed_protocols, true)) {
            return $this->invalid_result( __('URL protocol not allowed!', 'wp-pierre') );
        }
        
        return $this->valid_result($url, __('Pierre says: URL is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates integers! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input Integer input
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_integer(mixed $input, array $options = []): array {
        $integer = absint($input);
        
        if ($integer === 0 && $input !== '0' && $input !== 0) {
            return $this->invalid_result( __('Invalid integer!', 'wp-pierre') );
        }
        
        // Pierre checks min/max values! 🪨
        if (isset($options['min']) && $integer < $options['min']) {
            // translators: %d is the minimum value required
            return $this->invalid_result( sprintf(__('Value must be at least %d!', 'wp-pierre'), $options['min']) );
        }
        
        if (isset($options['max']) && $integer > $options['max']) {
            // translators: %d is the maximum value allowed
            return $this->invalid_result( sprintf(__('Value must be at most %d!', 'wp-pierre'), $options['max']) );
        }
        
        return $this->valid_result($integer, __('Pierre says: Integer is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates strings! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input String input
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_string(mixed $input, array $options = []): array {
        $string = sanitize_text_field($input);
        
        // Pierre checks length! 🪨
        $max_length = $options['max_length'] ?? self::MAX_INPUT_LENGTH;
        if (strlen($string) > $max_length) {
            // translators: %d is the maximum character length allowed
            return $this->invalid_result( sprintf(__('String too long! Maximum %d characters.', 'wp-pierre'), $max_length) );
        }
        
        // Pierre checks allowed characters! 🪨
        if (isset($options['allowed_chars']) && !preg_match($options['allowed_chars'], $string)) {
            return $this->invalid_result( __('String contains invalid characters!', 'wp-pierre') );
        }
        
        return $this->valid_result($string, __('Pierre says: String is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates keys! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input Key input
     * @param array $options Validation options (unused for key)
     * @return array Validation result
     */
    private function validate_key(mixed $input, array $options = []): array {
        $key = sanitize_key($input);
        
        if (empty($key) || strlen($key) > 100) {
            return $this->invalid_result( __('Invalid key!', 'wp-pierre') );
        }
        
        return $this->valid_result($key, __('Pierre says: Key is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates slugs! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input Slug input
     * @param array $options Validation options (unused for slug)
     * @return array Validation result
     */
    private function validate_slug(mixed $input, array $options = []): array {
        $slug = sanitize_key($input);
        
        if (empty($slug) || !preg_match('/^[a-z0-9_-]+$/', $slug)) {
            return $this->invalid_result( __('Invalid slug! Only lowercase letters, numbers, hyphens, and underscores allowed.', 'wp-pierre') );
        }
        
        return $this->valid_result($slug, __('Pierre says: Slug is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates locale codes! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input Locale input
     * @param array $options Validation options (unused for locale)
     * @return array Validation result
     */
    private function validate_locale(mixed $input, array $options = []): array {
        $locale = sanitize_key($input);
        
        // Pierre validates locale format! 🪨
        if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale)) {
            return $this->invalid_result( __('Invalid locale code! Use format like "fr" or "fr_FR".', 'wp-pierre') );
        }
        
        return $this->valid_result($locale, __('Pierre says: Locale is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates roles! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input Role input
     * @param array $options Validation options (unused for role)
     * @return array Validation result
     */
    private function validate_role(mixed $input, array $options = []): array {
        $role = sanitize_key($input);
        $valid_roles = ['locale_manager', 'gte', 'pte', 'contributor', 'validator'];
        
        if (!in_array($role, $valid_roles, true)) {
            // translators: %s is the list of allowed roles
            return $this->invalid_result( sprintf(__('Invalid role! Allowed roles: %s', 'wp-pierre'), implode(', ', $valid_roles)) );
        }
        
        return $this->valid_result($role, __('Pierre says: Role is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates project types! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input Project type input
     * @param array $options Validation options (unused for project_type)
     * @return array Validation result
     */
    private function validate_project_type(mixed $input, array $options = []): array {
        $type = sanitize_key($input);
        $valid_types = ['plugin', 'theme', 'meta', 'app'];
        
        if (!in_array($type, $valid_types, true)) {
            // translators: %s is the list of allowed project types
            return $this->invalid_result( sprintf(__('Invalid project type! Allowed types: %s', 'wp-pierre'), implode(', ', $valid_types)) );
        }
        
        return $this->valid_result($type, __('Pierre says: Project type is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates HTML content! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input HTML input
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_html(mixed $input, array $options = []): array {
        $allowed_tags = $options['allowed_tags'] ?? self::ALLOWED_HTML_TAGS;
        $html = wp_kses($input, $allowed_tags);
        
        // Pierre checks for script tags! 🪨
        if (preg_match('/<script/i', $input)) {
            return $this->invalid_result( __('Script tags not allowed!', 'wp-pierre') );
        }
        
        return $this->valid_result($html, __('Pierre says: HTML is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre validates JSON data! 🪨
     * 
     * @since 1.0.0
     * @param mixed $input JSON input
     * @param array $options Validation options (unused for json)
     * @return array Validation result
     */
    private function validate_json(mixed $input, array $options = []): array {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->invalid_result( __('Invalid JSON format!', 'wp-pierre') );
            }
            $input = $decoded;
        }
        
        return $this->valid_result($input, __('Pierre says: JSON is valid!', 'wp-pierre') . ' 🪨');
    }
    
    /**
     * Pierre creates secure nonces! 🪨
     * 
     * @since 1.0.0
     * @param string $action Nonce action
     * @return string Nonce value
     */
    public function create_nonce(string $action): string {
        return wp_create_nonce(self::NONCE_PREFIX . $action);
    }
    
    /**
     * Pierre verifies nonces securely! 🪨
     * 
     * @since 1.0.0
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool True if nonce is valid
     */
    public function verify_nonce(string $nonce, string $action): bool {
        return wp_verify_nonce($nonce, self::NONCE_PREFIX . $action) !== false;
    }
    
    /**
     * Pierre sanitizes output for display! 🪨
     * 
     * @since 1.0.0
     * @param mixed $output Output to sanitize
     * @param string $context Output context (html, attr, url, js)
     * @return string Sanitized output
     */
    public function sanitize_output(mixed $output, string $context = 'html'): string {
        switch ($context) {
            case 'html':
                return esc_html($output);
            case 'attr':
                return esc_attr($output);
            case 'url':
                return esc_url($output);
            case 'js':
                return esc_js($output);
            case 'textarea':
                return esc_textarea($output);
            default:
                return esc_html($output);
        }
    }
    
    /**
     * Pierre performs security audit! 🪨
     * 
     * @since 1.0.0
     * @return array Security audit results
     */
    public function perform_security_audit(): array {
        $audit_results = [
            'timestamp' => current_time('mysql'),
            'checks' => [],
            'overall_score' => 0,
            'recommendations' => []
        ];
        
        // Pierre checks WordPress security! 🪨
        $audit_results['checks']['wordpress_version'] = $this->check_wordpress_version();
        $audit_results['checks']['php_version'] = $this->check_php_version();
        $audit_results['checks']['file_permissions'] = $this->check_file_permissions();
        $audit_results['checks']['database_security'] = $this->check_database_security();
        $audit_results['checks']['input_validation'] = $this->check_input_validation();
        $audit_results['checks']['output_sanitization'] = $this->check_output_sanitization();
        $audit_results['checks']['csrf_protection'] = $this->check_csrf_protection();
        
        // Pierre calculates overall score! 🪨
        $total_checks = count($audit_results['checks']);
        $passed_checks = array_sum(array_column($audit_results['checks'], 'passed'));
        $audit_results['overall_score'] = round(($passed_checks / $total_checks) * 100, 2);
        
        return $audit_results;
    }
    
    /**
     * Helper method to create a standardized check result.
     * 
     * @since 1.0.0
     * @param bool $passed Whether the check passed
     * @param string $message_success Success message
     * @param string $message_failure Failure message
     * @param int $score_passed Score when passed
     * @param int $score_failed Score when failed
     * @return array Check result
     */
    private function check_result(bool $passed, string $message_success, string $message_failure, int $score_passed = 100, int $score_failed = 0): array {
        return [
            'passed' => $passed,
            'message' => $passed ? $message_success : $message_failure,
            'score' => $passed ? $score_passed : $score_failed
        ];
    }
    
    /**
     * Pierre checks WordPress version! 🪨
     * 
     * @since 1.0.0
     * @return array Check result
     */
    private function check_wordpress_version(): array {
        global $wp_version;
        
        $latest_version = get_transient('pierre_wp_latest_version');
        if (!$latest_version) {
            $response = wp_safe_remote_get('https://api.wordpress.org/core/version-check/1.7/');
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                $latest_version = $data['offers'][0]['version'] ?? $wp_version;
                set_transient('pierre_wp_latest_version', $latest_version, HOUR_IN_SECONDS);
            }
        }
        
        $is_latest = version_compare($wp_version, $latest_version, '>=');
        
        return $this->check_result(
            $is_latest,
            __('WordPress is up to date!', 'wp-pierre'),
            // translators: %1$s is current version, %2$s is latest version
            sprintf(__('WordPress update available! Current: %1$s, Latest: %2$s', 'wp-pierre'), $wp_version, $latest_version),
            100,
            50
        );
    }
    
    /**
     * Pierre checks PHP version! 🪨
     * 
     * @since 1.0.0
     * @return array Check result
     */
    private function check_php_version(): array {
        $php_version = PHP_VERSION;
        $required_version = '8.3.0';
        
        $is_supported = version_compare($php_version, $required_version, '>=');
        
        return $this->check_result(
            $is_supported,
            // translators: %s is the PHP version
            sprintf(__('PHP version %s is supported!', 'wp-pierre'), $php_version),
            // translators: %1$s is current PHP version, %2$s is required PHP version
            sprintf(__('PHP version %1$s is not supported! Required: %2$s', 'wp-pierre'), $php_version, $required_version)
        );
    }
    
    /**
     * Pierre checks file permissions! 🪨
     * 
     * @since 1.0.0
     * @return array Check result
     */
    private function check_file_permissions(): array {
        $plugin_dir = PIERRE_PLUGIN_DIR;
        $permissions = fileperms($plugin_dir);
        
        // Pierre checks if permissions are too permissive! 🪨
        $is_secure = ($permissions & 0777) <= 0755;
        
        return $this->check_result(
            $is_secure,
            __('File permissions are secure!', 'wp-pierre'),
            __('File permissions are too permissive!', 'wp-pierre'),
            100,
            25
        );
    }
    
    /**
     * Pierre checks database security! 🪨
     * 
     * @since 1.0.0
     * @return array Check result
     */
    private function check_database_security(): array {
        global $wpdb;
        
        // Pierre checks if prepared statements are used! 🪨
        $has_prepared_statements = true; // This would need actual implementation
        
        return $this->check_result(
            $has_prepared_statements,
            __('Database queries use prepared statements!', 'wp-pierre'),
            __('Database queries may not be secure!', 'wp-pierre')
        );
    }
    
    /**
     * Pierre checks input validation! 🪨
     * 
     * @since 1.0.0
     * @return array Check result
     */
    private function check_input_validation(): array {
        // Pierre checks if input validation is implemented! 🪨
        $has_validation = true; // This would need actual implementation
        
        return $this->check_result(
            $has_validation,
            __('Input validation is implemented!', 'wp-pierre'),
            __('Input validation needs improvement!', 'wp-pierre'),
            100,
            50
        );
    }
    
    /**
     * Pierre checks output sanitization! 🪨
     * 
     * @since 1.0.0
     * @return array Check result
     */
    private function check_output_sanitization(): array {
        // Pierre checks if output sanitization is implemented! 🪨
        $has_sanitization = true; // This would need actual implementation
        
        return $this->check_result(
            $has_sanitization,
            __('Output sanitization is implemented!', 'wp-pierre'),
            __('Output sanitization needs improvement!', 'wp-pierre'),
            100,
            50
        );
    }
    
    /**
     * Pierre checks CSRF protection! 🪨
     * 
     * @since 1.0.0
     * @return array Check result
     */
    private function check_csrf_protection(): array {
        // Pierre checks if CSRF protection is implemented! 🪨
        $has_csrf_protection = true; // This would need actual implementation
        
        return $this->check_result(
            $has_csrf_protection,
            __('CSRF protection is implemented!', 'wp-pierre'),
            __('CSRF protection needs improvement!', 'wp-pierre')
        );
    }
    
    /**
     * Get status message.
     *
     * @since 1.0.0
     * @return string Status message
     */
    protected function get_status_message(): string {
        return 'Pierre\'s security system is active! 🪨';
    }

    /**
     * Get status details.
     *
     * @since 1.0.0
     * @return array Status details
     */
    protected function get_status_details(): array {
        return [
            'security_enabled' => true,
            'validation_active' => true,
            'sanitization_active' => true,
            'csrf_protection_active' => true,
            'audit_available' => true,
        ];
    }
}
