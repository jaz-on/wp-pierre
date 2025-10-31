<?php
/**
 * Pierre's performance optimizer - he makes everything faster! 🪨
 * 
 * This class handles performance optimizations including caching,
 * database query optimization, and memory management.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Performance;

/**
 * Performance Optimizer class - Pierre's speed booster! 🪨
 * 
 * @since 1.0.0
 */
class PerformanceOptimizer {
    
    /**
     * Pierre's cache timeout for API responses! 🪨
     * 
     * @var int
     */
    private const API_CACHE_TIMEOUT = 15 * MINUTE_IN_SECONDS; // 15 minutes
    
    /**
     * Pierre's cache timeout for database queries! 🪨
     * 
     * @var int
     */
    private const DB_CACHE_TIMEOUT = 5 * MINUTE_IN_SECONDS; // 5 minutes
    
    /**
     * Pierre's cache timeout for dashboard data! 🪨
     * 
     * @var int
     */
    private const DASHBOARD_CACHE_TIMEOUT = 2 * MINUTE_IN_SECONDS; // 2 minutes
    
    /**
     * Pierre's batch size for bulk operations! 🪨
     * 
     * @var int
     */
    private const BATCH_SIZE = 10;
    
    /**
     * Pierre optimizes API requests with intelligent caching! 🪨
     * 
     * @since 1.0.0
     * @param string $cache_key Cache key for the request
     * @param callable $api_callback Function to call if cache miss
     * @param int $timeout Cache timeout in seconds
     * @return mixed Cached or fresh data
     */
    public function cached_api_request(string $cache_key, callable $api_callback, int $timeout = self::API_CACHE_TIMEOUT): mixed {
        // Pierre checks his cache first! 🪨
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            do_action('wp_pierre_debug', 'Cache hit (API): ' . $cache_key, ['source' => 'PerformanceOptimizer']);
            return $cached_data;
        }
        
        // Pierre makes the API request! 🪨
        $fresh_data = $api_callback();
        
        if ($fresh_data !== null) {
            // Pierre caches the fresh data! 🪨
            set_transient($cache_key, $fresh_data, $timeout);
            do_action('wp_pierre_debug', 'Cache set (API): ' . $cache_key, ['source' => 'PerformanceOptimizer']);
        }
        
        return $fresh_data;
    }
    
    /**
     * Pierre optimizes database queries with caching! 🪨
     * 
     * @since 1.0.0
     * @param string $cache_key Cache key for the query
     * @param callable $query_callback Function to execute if cache miss
     * @param int $timeout Cache timeout in seconds
     * @return mixed Cached or fresh query results
     */
    public function cached_db_query(string $cache_key, callable $query_callback, int $timeout = self::DB_CACHE_TIMEOUT): mixed {
        // Pierre checks his cache first! 🪨
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            do_action('wp_pierre_debug', 'Cache hit (DB): ' . $cache_key, ['source' => 'PerformanceOptimizer']);
            return $cached_data;
        }
        
        // Pierre executes the query! 🪨
        $fresh_data = $query_callback();
        
        if ($fresh_data !== null) {
            // Pierre caches the fresh data! 🪨
            set_transient($cache_key, $fresh_data, $timeout);
            do_action('wp_pierre_debug', 'Cache set (DB): ' . $cache_key, ['source' => 'PerformanceOptimizer']);
        }
        
        return $fresh_data;
    }
    
    /**
     * Pierre optimizes dashboard data loading! 🪨
     * 
     * @since 1.0.0
     * @param int $user_id User ID for personalized cache
     * @param callable $data_callback Function to get dashboard data
     * @return array Dashboard data
     */
    public function get_cached_dashboard_data(int $user_id, callable $data_callback): array {
        $cache_key = "pierre_dashboard_data_{$user_id}";
        
        return $this->cached_db_query($cache_key, $data_callback, self::DASHBOARD_CACHE_TIMEOUT) ?? [];
    }
    
    /**
     * Pierre processes data in batches to avoid memory issues! 🪨
     * 
     * @since 1.0.0
     * @param array $data Data to process
     * @param callable $processor Function to process each batch
     * @param int $batch_size Size of each batch
     * @return array Processed results
     */
    public function process_in_batches(array $data, callable $processor, int $batch_size = self::BATCH_SIZE): array {
        $results = [];
        $total_items = count($data);
        
        do_action('wp_pierre_debug', 'Batch processing start', ['source' => 'PerformanceOptimizer', 'total' => $total_items, 'batch' => $batch_size]);
        
        for ($i = 0; $i < $total_items; $i += $batch_size) {
            $batch = array_slice($data, $i, $batch_size);
            $batch_results = $processor($batch);
            
            if (is_array($batch_results)) {
                $results = array_merge($results, $batch_results);
            }
            
            // Pierre takes a small break between batches! 🪨
            if ($i + $batch_size < $total_items) {
                usleep(10000); // 10ms pause
            }
        }
        
        do_action('wp_pierre_debug', 'Batch processing complete', ['source' => 'PerformanceOptimizer']);
        return $results;
    }
    
    /**
     * Pierre optimizes database queries with prepared statements! 🪨
     * 
     * @since 1.0.0
     * @param string $query SQL query with placeholders
     * @param array $params Query parameters
     * @return array Query results
     */
    public function optimized_query(string $query, array $params = []): array {
        global $wpdb;
        
        try {
            if (empty($params)) {
                $results = $wpdb->get_results($query);
            } else {
                $prepared_query = $wpdb->prepare($query, $params);
                $results = $wpdb->get_results($prepared_query);
            }
            
            if ($wpdb->last_error) {
                do_action('wp_pierre_debug', 'DB error: ' . $wpdb->last_error, ['source' => 'PerformanceOptimizer']);
                return [];
            }
            
            return $results ?: [];
            
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'Query exception: ' . $e->getMessage(), ['source' => 'PerformanceOptimizer']);
            return [];
        }
    }
    
    /**
     * Pierre flushes specific cache entries! 🪨
     * 
     * @since 1.0.0
     * @param string $pattern Cache key pattern to flush
     * @return int Number of cache entries flushed
     */
    public function flush_cache_pattern(string $pattern): int {
        global $wpdb;
        
        try {
            // Pierre finds cache entries matching the pattern! 🪨
            $cache_entries = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} 
                     WHERE option_name LIKE %s",
                    $pattern
                )
            );
            
            $flushed_count = 0;
            
            foreach ($cache_entries as $entry) {
                $cache_key = str_replace(['_transient_', '_transient_timeout_'], '', $entry->option_name);
                if (delete_transient($cache_key)) {
                    $flushed_count++;
                }
            }
            
            do_action('wp_pierre_debug', 'Flushed cache by pattern', ['source' => 'PerformanceOptimizer', 'pattern' => $pattern, 'count' => $flushed_count]);
            return $flushed_count;
            
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'Flush cache exception: ' . $e->getMessage(), ['source' => 'PerformanceOptimizer']);
            return 0;
        }
    }
    
    /**
     * Pierre flushes all his cache! 🪨
     * 
     * @since 1.0.0
     * @return int Total number of cache entries flushed
     */
    public function flush_all_cache(): int {
        $patterns = [
            '_transient_pierre_%',
            '_transient_timeout_pierre_%'
        ];
        
        $total_flushed = 0;
        
        foreach ($patterns as $pattern) {
            $total_flushed += $this->flush_cache_pattern($pattern);
        }
        
        do_action('wp_pierre_debug', 'Flushed total cache entries', ['source' => 'PerformanceOptimizer', 'count' => $total_flushed]);
        return $total_flushed;
    }
    
    /**
     * Pierre monitors memory usage! 🪨
     * 
     * @since 1.0.0
     * @return array Memory usage statistics
     */
    public function get_memory_stats(): array {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit'),
            'message' => 'Pierre is monitoring memory usage! 🪨'
        ];
    }
    
    /**
     * Pierre optimizes WordPress queries! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function optimize_wordpress_queries(): void {
        // Pierre disables unnecessary queries! 🪨
        add_action('init', function() {
            // Disable unnecessary WordPress features for better performance
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wp_shortlink_wp_head');
            
            do_action('wp_pierre_debug', 'Optimized WordPress queries', ['source' => 'PerformanceOptimizer']);
        });
    }
    
    /**
     * Pierre gets his performance status! 🪨
     * 
     * @since 1.0.0
     * @return array Performance status information
     */
    public function get_status(): array {
        return [
            'cache_enabled' => true,
            'batch_processing_enabled' => true,
            'query_optimization_enabled' => true,
            'memory_stats' => $this->get_memory_stats(),
            'message' => 'Pierre\'s performance optimizer is active! 🪨'
        ];
    }
}
