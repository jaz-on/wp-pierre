<?php
/**
 * Pierre's CSRF protection - he prevents cross-site attacks! 🪨
 * 
 * This class provides enhanced CSRF protection with multiple layers
 * of security including nonce verification, referrer checking, and
 * rate limiting for Pierre's WordPress Translation Monitor.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Security;

// Pierre imports WordPress functions! 🪨
use function __;
use function _e;
use function esc_html__;
use function esc_attr__;
use function sprintf;
use function wp_verify_nonce;
use function wp_create_nonce;
use function current_user_can;
use function get_current_user_id;
use function get_site_url;
use function admin_url;
use function current_time;
use function wp_generate_password;
use function get_transient;
use function set_transient;
use function delete_transient;
use function get_option;
use function update_option;
use function delete_option;
use function error_log;

// Pierre imports WordPress constants! 🪨
use const HOUR_IN_SECONDS;
use const MINUTE_IN_SECONDS;

/**
 * CSRF Protection class - Pierre's attack prevention! 🪨
 * 
 * @since 1.0.0
 */
class CSRFProtection {
    
    /**
     * Pierre's CSRF token lifetime! 🪨
     * 
     * @var int
     */
    private const TOKEN_LIFETIME = 12 * HOUR_IN_SECONDS; // 12 hours
    
    /**
     * Pierre's rate limiting window! 🪨
     * 
     * @var int
     */
    private const RATE_LIMIT_WINDOW = 15 * MINUTE_IN_SECONDS; // 15 minutes
    
    /**
     * Pierre's maximum requests per window! 🪨
     * 
     * @var int
     */
    private const MAX_REQUESTS_PER_WINDOW = 100;
    
    /**
     * Pierre validates CSRF protection! 🪨
     * 
     * @since 1.0.0
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @param array $options Additional validation options
     * @return array Validation result
     */
    public function validate_csrf(string $nonce, string $action, array $options = []): array {
        try {
            // Pierre checks nonce! 🪨
            $nonce_valid = wp_verify_nonce($nonce, $action);
            if (!$nonce_valid) {
                return [
                    'valid' => false,
                    'message' => __('Pierre says: Invalid nonce! CSRF attack detected!', 'wp-pierre') . ' 😢',
                    'action' => 'log_security_event'
                ];
            }
            
            // Pierre checks referrer! 🪨
            if (!isset($options['skip_referrer_check'])) {
                $referrer_valid = $this->check_referrer();
                if (!$referrer_valid) {
                    return [
                        'valid' => false,
                        'message' => __('Pierre says: Invalid referrer! CSRF attack detected!', 'wp-pierre') . ' 😢',
                        'action' => 'log_security_event'
                    ];
                }
            }
            
            // Pierre checks rate limiting! 🪨
            if (!isset($options['skip_rate_limit'])) {
                $rate_limit_valid = $this->check_rate_limit($action);
                if (!$rate_limit_valid) {
                    return [
                        'valid' => false,
                        'message' => __('Pierre says: Rate limit exceeded! Too many requests!', 'wp-pierre') . ' 😢',
                        'action' => 'rate_limit_exceeded'
                    ];
                }
            }
            
            // Pierre checks user capabilities! 🪨
            if (isset($options['required_capability'])) {
                $capability_valid = current_user_can($options['required_capability']);
                if (!$capability_valid) {
                    return [
                        'valid' => false,
                        'message' => __('Pierre says: Insufficient permissions!', 'wp-pierre') . ' 😢',
                        'action' => 'log_security_event'
                    ];
                }
            }
            
            // Pierre logs successful validation! 🪨
            $this->log_security_event('csrf_validation_success', [
                'action' => $action,
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'Unknown'
            ]);
            
            return [
                'valid' => true,
                'message' => __('Pierre says: CSRF protection passed!', 'wp-pierre') . ' 🪨',
                'action' => 'success'
            ];
            
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'CSRF validation error: ' . $e->getMessage(), ['source' => 'CSRFProtection']);
            return [
                'valid' => false,
                'message' => __('Pierre says: CSRF validation error!', 'wp-pierre') . ' 😢',
                'action' => 'log_security_event'
            ];
        }
    }
    
    /**
     * Pierre checks referrer! 🪨
     * 
     * @since 1.0.0
     * @return bool True if referrer is valid
     */
    private function check_referrer(): bool {
        $referrer = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        
        if (empty($referrer)) {
            return false;
        }
        
        $site_url = get_site_url();
        $admin_url = admin_url();
        
        // Pierre checks if referrer is from the same site! 🪨
        return strpos($referrer, $site_url) === 0 || strpos($referrer, $admin_url) === 0;
    }
    
    /**
     * Pierre checks rate limiting! 🪨
     * 
     * @since 1.0.0
     * @param string $action Action being performed
     * @return bool True if rate limit is not exceeded
     */
    private function check_rate_limit(string $action): bool {
        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        $rate_limit_key = "pierre_rate_limit_{$action}_{$user_id}_{$ip_address}";
        
        $current_requests = get_transient($rate_limit_key) ?: 0;
        
        if ($current_requests >= self::MAX_REQUESTS_PER_WINDOW) {
            $this->log_security_event('rate_limit_exceeded', [
                'action' => $action,
                'user_id' => $user_id,
                'ip_address' => $ip_address,
                'requests' => $current_requests
            ]);
            return false;
        }
        
        // Pierre increments request count! 🪨
        set_transient($rate_limit_key, $current_requests + 1, self::RATE_LIMIT_WINDOW);
        
        return true;
    }
    
    /**
     * Pierre gets client IP address! 🪨
     * 
     * @since 1.0.0
     * @return string Client IP address
     */
    private function get_client_ip(): string {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', sanitize_text_field(wp_unslash($_SERVER[$key]))) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    }
    
    /**
     * Pierre logs security events! 🪨
     * 
     * @since 1.0.0
     * @param string $event_type Type of security event
     * @param array $event_data Event data
     * @return void
     */
    private function log_security_event(string $event_type, array $event_data): void {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'user_id' => $event_data['user_id'] ?? 0,
            'ip_address' => $event_data['ip_address'] ?? 'Unknown',
            'user_agent' => $event_data['user_agent'] ?? 'Unknown',
            'data' => $event_data
        ];
        
        // Pierre stores security logs! 🪨
        $security_logs = get_option('pierre_security_logs', []);
        $security_logs[] = $log_entry;
        
        // Pierre keeps only last 1000 entries! 🪨
        if (count($security_logs) > 1000) {
            $security_logs = array_slice($security_logs, -1000);
        }
        
        update_option('pierre_security_logs', $security_logs);
        
        // Pierre also exposes a debug hook for immediate monitoring (no default logger)
        do_action('wp_pierre_debug', 'Security Event: ' . $event_type, ['source' => 'CSRFProtection', 'entry' => $log_entry]);
    }
    
    /**
     * Pierre generates secure tokens! 🪨
     * 
     * @since 1.0.0
     * @param string $action Token action
     * @param int $user_id User ID (optional)
     * @return string Secure token
     */
    public function generate_secure_token(string $action, int $user_id = 0): string {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        $token_data = [
            'action' => $action,
            'user_id' => $user_id,
            'timestamp' => time(),
            'random' => wp_generate_password(32, false)
        ];
        
        $token = base64_encode(json_encode($token_data));
        
        // Pierre stores token for validation! 🪨
        $token_key = "pierre_token_" . md5($token);
        set_transient($token_key, $token_data, self::TOKEN_LIFETIME);
        
        return $token;
    }
    
    /**
     * Pierre validates secure tokens! 🪨
     * 
     * @since 1.0.0
     * @param string $token Token to validate
     * @param string $expected_action Expected action
     * @return array Validation result
     */
    public function validate_secure_token(string $token, string $expected_action): array {
        try {
            $token_data = json_decode(base64_decode($token), true);
            
            if (!$token_data || !isset($token_data['action'], $token_data['user_id'], $token_data['timestamp'])) {
                return [
                    'valid' => false,
                    'message' => __('Pierre says: Invalid token format!', 'wp-pierre') . ' 😢'
                ];
            }
            
            // Pierre checks token action! 🪨
            if ($token_data['action'] !== $expected_action) {
                return [
                    'valid' => false,
                    'message' => __('Pierre says: Token action mismatch!', 'wp-pierre') . ' 😢'
                ];
            }
            
            // Pierre checks token age! 🪨
            if (time() - $token_data['timestamp'] > self::TOKEN_LIFETIME) {
                return [
                    'valid' => false,
                    'message' => __('Pierre says: Token expired!', 'wp-pierre') . ' 😢'
                ];
            }
            
            // Pierre checks if token exists in storage! 🪨
            $token_key = "pierre_token_" . md5($token);
            $stored_data = get_transient($token_key);
            
            if (!$stored_data) {
                return [
                    'valid' => false,
                    'message' => __('Pierre says: Token not found or expired!', 'wp-pierre') . ' 😢'
                ];
            }
            
            // Pierre deletes used token! 🪨
            delete_transient($token_key);
            
            return [
                'valid' => true,
                'message' => __('Pierre says: Token is valid!', 'wp-pierre') . ' 🪨',
                'user_id' => $token_data['user_id']
            ];
            
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'Token validation error: ' . $e->getMessage(), ['source' => 'CSRFProtection']);
            return [
                'valid' => false,
                'message' => __('Pierre says: Token validation error!', 'wp-pierre') . ' 😢'
            ];
        }
    }
    
    /**
     * Pierre gets security logs! 🪨
     * 
     * @since 1.0.0
     * @param int $limit Number of logs to retrieve
     * @param string $event_type Filter by event type (optional)
     * @return array Security logs
     */
    public function get_security_logs(int $limit = 100, string $event_type = ''): array {
        $security_logs = get_option('pierre_security_logs', []);
        
        // Pierre ensures security_logs is always an array! 🪨
        if (!is_array($security_logs)) {
            $security_logs = [];
        }
        
        if (!empty($event_type)) {
            $security_logs = array_filter($security_logs, function($log) use ($event_type) {
                return $log['event_type'] === $event_type;
            });
        }
        
        return array_slice(array_reverse($security_logs), 0, $limit);
    }
    
    /**
     * Pierre clears security logs! 🪨
     * 
     * @since 1.0.0
     * @param string $event_type Clear specific event type (optional)
     * @return bool True on success
     */
    public function clear_security_logs(string $event_type = ''): bool {
        if (empty($event_type)) {
            return delete_option('pierre_security_logs');
        }
        
        $security_logs = get_option('pierre_security_logs', []);
        
        // Pierre ensures security_logs is always an array! 🪨
        if (!is_array($security_logs)) {
            $security_logs = [];
        }
        
        $filtered_logs = array_filter($security_logs, function($log) use ($event_type) {
            return $log['event_type'] !== $event_type;
        });
        
        return update_option('pierre_security_logs', array_values($filtered_logs));
    }
    
    /**
     * Pierre gets his CSRF protection status! 🪨
     * 
     * @since 1.0.0
     * @return array CSRF protection status
     */
    public function get_status(): array {
        return [
            'csrf_protection_enabled' => true,
            'nonce_verification_active' => true,
            'referrer_checking_active' => true,
            'rate_limiting_active' => true,
            'secure_tokens_enabled' => true,
            'security_logging_active' => true,
            'message' => 'Pierre\'s CSRF protection is active! 🪨'
        ];
    }
}
