<?php
/**
 * Pierre's performance configuration - he optimizes everything! 🪨
 * 
 * This file contains performance optimization settings and configurations
 * for Pierre's WordPress Translation Monitor plugin.
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pierre's performance configuration constants! 🪨
 */

// Cache timeouts (in seconds)
define('PIERRE_CACHE_API_TIMEOUT', 15 * MINUTE_IN_SECONDS);      // 15 minutes
define('PIERRE_CACHE_DB_TIMEOUT', 5 * MINUTE_IN_SECONDS);        // 5 minutes
define('PIERRE_CACHE_DASHBOARD_TIMEOUT', 2 * MINUTE_IN_SECONDS); // 2 minutes
define('PIERRE_CACHE_REPORTS_TIMEOUT', HOUR_IN_SECONDS);         // 1 hour

// Batch processing settings
define('PIERRE_BATCH_SIZE_SMALL', 5);   // For small operations
define('PIERRE_BATCH_SIZE_MEDIUM', 10); // For medium operations
define('PIERRE_BATCH_SIZE_LARGE', 20);  // For large operations

// Memory optimization settings
define('PIERRE_MEMORY_LIMIT_MB', 256);  // Memory limit for operations
define('PIERRE_MEMORY_CHECK_INTERVAL', 10); // Check memory every N operations

// Database optimization settings
define('PIERRE_DB_QUERY_LIMIT', 100);   // Max queries per operation
define('PIERRE_DB_CONNECTION_TIMEOUT', 30); // Connection timeout in seconds

// API optimization settings
define('PIERRE_API_RATE_LIMIT', 10);    // Max API calls per minute
define('PIERRE_API_TIMEOUT', 30);       // API request timeout in seconds
define('PIERRE_API_RETRY_ATTEMPTS', 3); // Number of retry attempts

// Surveillance optimization settings
define('PIERRE_SURVEILLANCE_INTERVAL_MIN', 5);  // Minimum interval in minutes
define('PIERRE_SURVEILLANCE_INTERVAL_MAX', 60); // Maximum interval in minutes
define('PIERRE_SURVEILLANCE_BATCH_SIZE', 5);    // Projects per batch

// Notification optimization settings
define('PIERRE_NOTIFICATION_BATCH_SIZE', 5);    // Notifications per batch
define('PIERRE_NOTIFICATION_DELAY_MS', 1000);   // Delay between notifications

/**
 * Pierre's performance optimization functions! 🪨
 */

/**
 * Pierre checks if performance optimizations are enabled! 🪨
 * 
 * @since 1.0.0
 * @return bool True if optimizations are enabled
 */
function pierre_performance_enabled(): bool {
    return get_option('pierre_performance_enabled', true);
}

/**
 * Pierre gets his performance settings! 🪨
 * 
 * @since 1.0.0
 * @return array Performance settings
 */
function pierre_get_performance_settings(): array {
    return [
        'cache_enabled' => get_option('pierre_cache_enabled', true),
        'batch_processing_enabled' => get_option('pierre_batch_processing_enabled', true),
        'memory_optimization_enabled' => get_option('pierre_memory_optimization_enabled', true),
        'db_optimization_enabled' => get_option('pierre_db_optimization_enabled', true),
        'api_optimization_enabled' => get_option('pierre_api_optimization_enabled', true),
        'surveillance_optimization_enabled' => get_option('pierre_surveillance_optimization_enabled', true),
        'notification_optimization_enabled' => get_option('pierre_notification_optimization_enabled', true)
    ];
}

/**
 * Pierre updates his performance settings! 🪨
 * 
 * @since 1.0.0
 * @param array $settings Performance settings to update
 * @return bool True on success, false on failure
 */
function pierre_update_performance_settings(array $settings): bool {
    $valid_settings = [
        'cache_enabled',
        'batch_processing_enabled',
        'memory_optimization_enabled',
        'db_optimization_enabled',
        'api_optimization_enabled',
        'surveillance_optimization_enabled',
        'notification_optimization_enabled'
    ];
    
    $updated = 0;
    
    foreach ($settings as $key => $value) {
        if (in_array($key, $valid_settings) && is_bool($value)) {
            if (update_option("pierre_{$key}", $value)) {
                $updated++;
            }
        }
    }
    
    return $updated > 0;
}

/**
 * Pierre gets his cache statistics! 🪨
 * 
 * @since 1.0.0
 * @return array Cache statistics
 */
function pierre_get_cache_stats(): array {
    global $wpdb;
    
    try {
        $total_entries = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                 WHERE option_name LIKE %s",
                '_transient_pierre_%'
            )
        );
        
        $expired_entries = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND option_value < %s",
                '_transient_timeout_pierre_%',
                time()
            )
        );
        
        return [
            'total_entries' => (int) $total_entries,
            'expired_entries' => (int) $expired_entries,
            'active_entries' => (int) $total_entries - (int) $expired_entries,
            'cache_hit_ratio' => 0, // Will be calculated by CacheManager
            'message' => 'Pierre\'s cache statistics! 🪨'
        ];
        
    } catch (\Exception $e) {
        do_action('wp_pierre_debug', 'Error getting cache stats: ' . $e->getMessage(), ['source' => 'performance-config']);
        return [
            'total_entries' => 0,
            'expired_entries' => 0,
            'active_entries' => 0,
            'cache_hit_ratio' => 0,
            'message' => 'Pierre couldn\'t get cache stats! 😢'
        ];
    }
}

/**
 * Pierre flushes all his cache! 🪨
 * 
 * @since 1.0.0
 * @return int Number of cache entries flushed
 */
function pierre_flush_all_cache(): int {
    // Try to use CacheManager if available (preferred method)
    if (class_exists('\Pierre\Performance\CacheManager')) {
        try {
            $cache_manager = new \Pierre\Performance\CacheManager();
            $flushed = $cache_manager->flush_all_plugin_groups();
            do_action('wp_pierre_debug', 'Flushed all plugin cache groups via CacheManager', ['source' => 'performance-config', 'count' => $flushed]);
            return $flushed;
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'CacheManager unavailable, using direct method: ' . $e->getMessage(), ['source' => 'performance-config']);
        }
    }
    
    // Use wp_cache_flush_group if object cache is available
    if (function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache()) {
        if (function_exists('wp_cache_flush_group')) {
            $groups = ['pierre', 'api', 'database', 'surveillance'];
            $flushed_count = 0;
            foreach ($groups as $group) {
                wp_cache_flush_group($group);
                $flushed_count++;
            }
            do_action('wp_pierre_debug', 'Flushed object cache groups', ['source' => 'performance-config', 'groups' => $groups, 'count' => $flushed_count]);
            return $flushed_count;
        }
        // Fallback: flush entire cache if group-specific flush not available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            do_action('wp_pierre_debug', 'Flushed entire object cache (fallback)', ['source' => 'performance-config']);
            return 1;
        }
    }
    
    // Transient-based fallback - flush all plugin groups
    global $wpdb;
    
    $groups = ['pierre', 'api', 'database', 'surveillance'];
    $total_flushed = 0;
    
    foreach ($groups as $group) {
        try {
            $cache_entries = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} 
                     WHERE option_name LIKE %s",
                    '_transient_' . $group . '_%'
                )
            );
            
            foreach ($cache_entries as $entry) {
                $cache_key = str_replace('_transient_', '', $entry->option_name);
                if (delete_transient($cache_key)) {
                    $total_flushed++;
                }
            }
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'Error flushing cache group: ' . $group . ' - ' . $e->getMessage(), ['source' => 'performance-config']);
        }
    }
    
    do_action('wp_pierre_debug', 'Flushed cache entries via transients', ['source' => 'performance-config', 'count' => $total_flushed]);
    return $total_flushed;
}

/**
 * Pierre gets his memory usage! 🪨
 * 
 * @since 1.0.0
 * @return array Memory usage information
 */
function pierre_get_memory_usage(): array {
    return [
        'current_usage' => memory_get_usage(true),
        'peak_usage' => memory_get_peak_usage(true),
        'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'limit' => ini_get('memory_limit'),
        'usage_percentage' => round((memory_get_usage(true) / wp_convert_hr_to_bytes(ini_get('memory_limit'))) * 100, 2),
        'message' => 'Pierre\'s memory usage! 🪨'
    ];
}

/**
 * Pierre checks if memory usage is high! 🪨
 * 
 * @since 1.0.0
 * @param int $threshold_percentage Memory usage threshold percentage
 * @return bool True if memory usage is high
 */
function pierre_is_memory_usage_high(int $threshold_percentage = 80): bool {
    $memory_usage = pierre_get_memory_usage();
    return $memory_usage['usage_percentage'] > $threshold_percentage;
}

/**
 * Pierre optimizes WordPress for better performance! 🪨
 * 
 * @since 1.0.0
 * @return void
 */
function pierre_optimize_wordpress(): void {
    if (!pierre_performance_enabled()) {
        return;
    }
    
    // Pierre disables unnecessary WordPress features! 🪨
    add_action('init', function() {
        // Remove unnecessary WordPress features
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        remove_action('wp_head', 'wp_resource_hints', 2);
        
        // Optimize WordPress queries
        if (get_option('pierre_db_optimization_enabled', true)) {
            // Disable unnecessary queries
            add_filter('pre_get_posts', function($query) {
                if (!is_admin() && $query->is_main_query()) {
                    // Optimize main query
                    $query->set('no_found_rows', true);
                    $query->set('update_post_meta_cache', false);
                    $query->set('update_post_term_cache', false);
                }
                return $query;
            });
        }
        
        do_action('wp_pierre_debug', 'Optimized WordPress for better performance', ['source' => 'performance-config']);
    });
}

/**
 * Pierre initializes his performance optimizations! 🪨
 * 
 * @since 1.0.0
 * @return void
 */
function pierre_init_performance_optimizations(): void {
    if (!pierre_performance_enabled()) {
        return;
    }
    
    // Pierre optimizes WordPress! 🪨
    pierre_optimize_wordpress();
    
    // Pierre sets up his performance monitoring! 🪨
    add_action('wp_footer', function() {
        if (current_user_can('manage_options') && get_option('pierre_debug_mode', false)) {
            $memory_usage = pierre_get_memory_usage();
			printf(
				"<!-- Pierre's Performance Stats: Memory: %sMB, Peak: %sMB -->",
				esc_html((string) $memory_usage['current_usage_mb']),
				esc_html((string) $memory_usage['peak_usage_mb'])
			);
        }
    });
    
    do_action('wp_pierre_debug', 'Initialized performance optimizations', ['source' => 'performance-config']);
}

// Pierre starts his performance optimizations! 🪨
add_action('init', 'pierre_init_performance_optimizations', 1);
