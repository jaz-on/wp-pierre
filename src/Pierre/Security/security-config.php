<?php
/**
 * Pierre's security configuration - he protects everything! 🪨
 * 
 * This file contains security-related constants and configuration
 * for Pierre's WordPress Translation Monitor.
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pierre's security constants! 🪨
 */

// Pierre's security nonce lifetime! 🪨
if (!defined('PIERRE_NONCE_LIFETIME')) {
    define('PIERRE_NONCE_LIFETIME', 12 * HOUR_IN_SECONDS);
}

// Pierre's rate limiting window! 🪨
if (!defined('PIERRE_RATE_LIMIT_WINDOW')) {
    define('PIERRE_RATE_LIMIT_WINDOW', 15 * MINUTE_IN_SECONDS);
}

// Pierre's maximum requests per window! 🪨
if (!defined('PIERRE_MAX_REQUESTS_PER_WINDOW')) {
    define('PIERRE_MAX_REQUESTS_PER_WINDOW', 100);
}

// Pierre's security log retention! 🪨
if (!defined('PIERRE_SECURITY_LOG_RETENTION')) {
    define('PIERRE_SECURITY_LOG_RETENTION', 1000);
}

// Pierre's audit history retention! 🪨
if (!defined('PIERRE_AUDIT_HISTORY_RETENTION')) {
    define('PIERRE_AUDIT_HISTORY_RETENTION', 10);
}

// Pierre's maximum input length! 🪨
if (!defined('PIERRE_MAX_INPUT_LENGTH')) {
    define('PIERRE_MAX_INPUT_LENGTH', 255);
}

// Pierre's allowed file extensions! 🪨
if (!defined('PIERRE_ALLOWED_FILE_EXTENSIONS')) {
    define('PIERRE_ALLOWED_FILE_EXTENSIONS', ['php', 'js', 'css', 'json', 'txt', 'md']);
}

// Pierre's blocked file extensions! 🪨
if (!defined('PIERRE_BLOCKED_FILE_EXTENSIONS')) {
    define('PIERRE_BLOCKED_FILE_EXTENSIONS', ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js']);
}

// Pierre's security headers! 🪨
if (!defined('PIERRE_SECURITY_HEADERS')) {
    define('PIERRE_SECURITY_HEADERS', [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'"
    ]);
}

/**
 * Pierre's security utility functions! 🪨
 */

/**
 * Pierre validates file uploads! 🪨
 * 
 * @since 1.0.0
 * @param array $file The uploaded file array
 * @return array Validation result
 */
function pierre_validate_file_upload(array $file): array {
    // Pierre checks if file was uploaded! 🪨
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return [
            'valid' => false,
            'message' => __('Pierre says: Invalid file upload!', 'wp-pierre') . ' 😢'
        ];
    }
    
    // Pierre checks file size! 🪨
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return [
            'valid' => false,
            'message' => __('Pierre says: File too large! Maximum 5MB allowed.', 'wp-pierre') . ' 😢'
        ];
    }
    
    // Pierre checks file extension! 🪨
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = explode(',', PIERRE_ALLOWED_FILE_EXTENSIONS);
    $blocked_extensions = explode(',', PIERRE_BLOCKED_FILE_EXTENSIONS);
    
    if (in_array($file_extension, $blocked_extensions, true)) {
        return [
            'valid' => false,
            'message' => __('Pierre says: File type not allowed!', 'wp-pierre') . ' 😢'
        ];
    }
    
    if (!in_array($file_extension, $allowed_extensions, true)) {
        return [
            'valid' => false,
            'message' => __('Pierre says: File extension not allowed!', 'wp-pierre') . ' 😢'
        ];
    }
    
    // Pierre checks MIME type! 🪨
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mime_types = [
        'text/plain',
        'text/css',
        'text/javascript',
        'application/json',
        'application/x-php',
        'text/x-php',
        'text/html'
    ];
    
    if (!in_array($mime_type, $allowed_mime_types, true)) {
        return [
            'valid' => false,
            'message' => __('Pierre says: MIME type not allowed!', 'wp-pierre') . ' 😢'
        ];
    }
    
    return [
        'valid' => true,
        'message' => __('Pierre says: File upload is valid!', 'wp-pierre') . ' 🪨'
    ];
}

/**
 * Pierre sanitizes file content! 🪨
 * 
 * @since 1.0.0
 * @param string $content File content
 * @param string $file_extension File extension
 * @return string Sanitized content
 */
function pierre_sanitize_file_content(string $content, string $file_extension): string {
    switch ($file_extension) {
        case 'php':
            // Pierre removes dangerous PHP functions! 🪨
            $dangerous_functions = [
                'eval', 'exec', 'system', 'shell_exec', 'passthru',
                'file_get_contents', 'file_put_contents', 'fopen', 'fwrite',
                'include', 'require', 'include_once', 'require_once'
            ];
            
            foreach ($dangerous_functions as $function) {
                $content = preg_replace('/\b' . preg_quote($function, '/') . '\s*\(/', '/* ' . $function . ' */(', $content);
            }
            break;
            
        case 'js':
            // Pierre removes dangerous JavaScript! 🪨
            $content = preg_replace('/eval\s*\(/', '/* eval */(', $content);
            $content = preg_replace('/document\.write\s*\(/', '/* document.write */(', $content);
            break;
            
        case 'css':
            // Pierre removes dangerous CSS! 🪨
            $content = preg_replace('/expression\s*\(/', '/* expression */(', $content);
            $content = preg_replace('/javascript\s*:/', '/* javascript */:', $content);
            break;
            
        default:
            // Pierre removes HTML tags for text files! 🪨
            $content = wp_strip_all_tags($content);
            break;
    }
    
    return $content;
}

/**
 * Pierre validates user input! 🪨
 * 
 * @since 1.0.0
 * @param mixed $input Input to validate
 * @param string $type Input type
 * @param array $options Validation options
 * @return array Validation result
 */
function pierre_validate_user_input(mixed $input, string $type, array $options = []): array {
    $security_manager = new \Pierre\Security\SecurityManager();
    return $security_manager->validate_input($input, $type, $options);
}

/**
 * Pierre sanitizes output! 🪨
 * 
 * @since 1.0.0
 * @param mixed $output Output to sanitize
 * @param string $context Output context
 * @return string Sanitized output
 */
function pierre_sanitize_output(mixed $output, string $context = 'html'): string {
    $security_manager = new \Pierre\Security\SecurityManager();
    return $security_manager->sanitize_output($output, $context);
}

/**
 * Pierre creates secure nonce! 🪨
 * 
 * @since 1.0.0
 * @param string $action Nonce action
 * @return string Nonce value
 */
function pierre_create_nonce(string $action): string {
    $security_manager = new \Pierre\Security\SecurityManager();
    return $security_manager->create_nonce($action);
}

/**
 * Pierre verifies nonce! 🪨
 * 
 * @since 1.0.0
 * @param string $nonce Nonce value
 * @param string $action Nonce action
 * @return bool True if nonce is valid
 */
function pierre_verify_nonce(string $nonce, string $action): bool {
    $security_manager = new \Pierre\Security\SecurityManager();
    return $security_manager->verify_nonce($nonce, $action);
}

/**
 * Pierre logs security event! 🪨
 * 
 * @since 1.0.0
 * @param string $event_type Event type
 * @param array $event_data Event data
 * @return void
 */
function pierre_log_security_event(string $event_type, array $event_data): void {
    $csrf_protection = new \Pierre\Security\CSRFProtection();
    $csrf_protection->log_security_event($event_type, $event_data);
}

/**
 * Pierre performs security audit! 🪨
 * 
 * @since 1.0.0
 * @param array $options Audit options
 * @return array Audit results
 */
function pierre_perform_security_audit(array $options = []): array {
    $security_auditor = new \Pierre\Security\SecurityAuditor();
    return $security_auditor->perform_comprehensive_audit($options);
}

/**
 * Pierre gets security status! 🪨
 * 
 * @since 1.0.0
 * @return array Security status
 */
function pierre_get_security_status(): array {
    return [
        'security_manager' => (new \Pierre\Security\SecurityManager())->get_status(),
        'csrf_protection' => (new \Pierre\Security\CSRFProtection())->get_status(),
        'security_auditor' => (new \Pierre\Security\SecurityAuditor())->get_status(),
        'message' => 'Pierre\'s security system is active! 🪨'
    ];
}

// Pierre logs his security configuration loading! 🪨
error_log('Pierre loaded his security configuration! 🪨');
