<?php
/**
 * Pierre's advanced cache manager - he caches everything efficiently! 🪨
 * 
 * This class provides advanced caching functionality including
 * object caching, fragment caching, and cache invalidation.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Performance;

/**
 * Cache Manager class - Pierre's caching system! 🪨
 * 
 * @since 1.0.0
 */
class CacheManager {
    
    /**
     * Pierre's cache group prefix! 🪨
     * 
     * @var string
     */
    private const CACHE_GROUP = 'pierre';
    
    /**
     * Pierre's default cache timeout! 🪨
     * 
     * @var int
     */
    private const DEFAULT_TIMEOUT = HOUR_IN_SECONDS;
    
    /**
     * Pierre's cache version for invalidation! 🪨
     * 
     * @var string
     */
    private $cache_version;
    
    /**
     * Pierre initializes his cache manager! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->cache_version = get_option('pierre_cache_version', '1.0.0');
    }
    
    /**
     * Pierre gets cached data! 🪨
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @param string $group Cache group
     * @return mixed Cached data or false if not found
     */
    public function get(string $key, string $group = self::CACHE_GROUP): mixed {
        $cache_key = $this->build_cache_key($key, $group);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            error_log("Pierre found cached data for {$cache_key}! 🪨");
            return $cached_data;
        }
        
        return false;
    }
    
    /**
     * Pierre sets cached data! 🪨
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $timeout Cache timeout in seconds
     * @param string $group Cache group
     * @return bool True on success, false on failure
     */
    public function set(string $key, mixed $data, int $timeout = self::DEFAULT_TIMEOUT, string $group = self::CACHE_GROUP): bool {
        $cache_key = $this->build_cache_key($key, $group);
        $result = set_transient($cache_key, $data, $timeout);
        
        if ($result) {
            error_log("Pierre cached data for {$cache_key}! 🪨");
        } else {
            error_log("Pierre failed to cache data for {$cache_key}! 😢");
        }
        
        return $result;
    }
    
    /**
     * Pierre deletes cached data! 🪨
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @param string $group Cache group
     * @return bool True on success, false on failure
     */
    public function delete(string $key, string $group = self::CACHE_GROUP): bool {
        $cache_key = $this->build_cache_key($key, $group);
        $result = delete_transient($cache_key);
        
        if ($result) {
            error_log("Pierre deleted cached data for {$cache_key}! 🪨");
        }
        
        return $result;
    }
    
    /**
     * Pierre flushes cache by group! 🪨
     * 
     * @since 1.0.0
     * @param string $group Cache group to flush
     * @return int Number of cache entries flushed
     */
    public function flush_group(string $group = self::CACHE_GROUP): int {
        global $wpdb;
        
        try {
            $pattern = "_transient_{$group}_%";
            $cache_entries = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} 
                     WHERE option_name LIKE %s",
                    $pattern
                )
            );
            
            $flushed_count = 0;
            
            foreach ($cache_entries as $entry) {
                $cache_key = str_replace('_transient_', '', $entry->option_name);
                if (delete_transient($cache_key)) {
                    $flushed_count++;
                }
            }
            
            error_log("Pierre flushed {$flushed_count} cache entries from group {$group}! 🪨");
            return $flushed_count;
            
        } catch (\Exception $e) {
            error_log("Pierre encountered an error flushing cache group: " . $e->getMessage() . " 😢");
            return 0;
        }
    }
    
    /**
     * Pierre flushes all his cache! 🪨
     * 
     * @since 1.0.0
     * @return int Total number of cache entries flushed
     */
    public function flush_all(): int {
        return $this->flush_group(self::CACHE_GROUP);
    }
    
    /**
     * Pierre remembers data with automatic caching! 🪨
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @param callable $callback Function to call if cache miss
     * @param int $timeout Cache timeout in seconds
     * @param string $group Cache group
     * @return mixed Cached or fresh data
     */
    public function remember(string $key, callable $callback, int $timeout = self::DEFAULT_TIMEOUT, string $group = self::CACHE_GROUP): mixed {
        $cached_data = $this->get($key, $group);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $fresh_data = $callback();
        
        if ($fresh_data !== null) {
            $this->set($key, $fresh_data, $timeout, $group);
        }
        
        return $fresh_data;
    }
    
    /**
     * Pierre caches API responses intelligently! 🪨
     * 
     * @since 1.0.0
     * @param string $endpoint API endpoint
     * @param array $params API parameters
     * @param callable $api_callback Function to call API
     * @param int $timeout Cache timeout in seconds
     * @return mixed Cached or fresh API response
     */
    public function cache_api_response(string $endpoint, array $params, callable $api_callback, int $timeout = self::DEFAULT_TIMEOUT): mixed {
        $cache_key = 'api_' . md5($endpoint . serialize($params));
        
        return $this->remember($cache_key, $api_callback, $timeout, 'api');
    }
    
    /**
     * Pierre caches database query results! 🪨
     * 
     * @since 1.0.0
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param callable $query_callback Function to execute query
     * @param int $timeout Cache timeout in seconds
     * @return mixed Cached or fresh query results
     */
    public function cache_db_query(string $query, array $params, callable $query_callback, int $timeout = self::DEFAULT_TIMEOUT): mixed {
        $cache_key = 'db_' . md5($query . serialize($params));
        
        return $this->remember($cache_key, $query_callback, $timeout, 'database');
    }
    
    /**
     * Pierre invalidates cache by pattern! 🪨
     * 
     * @since 1.0.0
     * @param string $pattern Cache key pattern
     * @return int Number of cache entries invalidated
     */
    public function invalidate_pattern(string $pattern): int {
        global $wpdb;
        
        try {
            $cache_entries = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} 
                     WHERE option_name LIKE %s",
                    $pattern
                )
            );
            
            $invalidated_count = 0;
            
            foreach ($cache_entries as $entry) {
                $cache_key = str_replace('_transient_', '', $entry->option_name);
                if (delete_transient($cache_key)) {
                    $invalidated_count++;
                }
            }
            
            error_log("Pierre invalidated {$invalidated_count} cache entries matching {$pattern}! 🪨");
            return $invalidated_count;
            
        } catch (\Exception $e) {
            error_log("Pierre encountered an error invalidating cache: " . $e->getMessage() . " 😢");
            return 0;
        }
    }
    
    /**
     * Pierre increments cache version for global invalidation! 🪨
     * 
     * @since 1.0.0
     * @return string New cache version
     */
    public function increment_cache_version(): string {
        $new_version = time();
        update_option('pierre_cache_version', $new_version);
        $this->cache_version = $new_version;
        
        error_log("Pierre incremented cache version to {$new_version}! 🪨");
        return $new_version;
    }
    
    /**
     * Pierre builds cache key with version! 🪨
     * 
     * @since 1.0.0
     * @param string $key Cache key
     * @param string $group Cache group
     * @return string Built cache key
     */
    private function build_cache_key(string $key, string $group): string {
        return "{$group}_{$this->cache_version}_{$key}";
    }
    
    /**
     * Pierre gets cache statistics! 🪨
     * 
     * @since 1.0.0
     * @return array Cache statistics
     */
    public function get_stats(): array {
        global $wpdb;
        
        try {
            $total_entries = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->options} 
                     WHERE option_name LIKE %s",
                    '_transient_' . self::CACHE_GROUP . '_%'
                )
            );
            
            $expired_entries = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->options} 
                     WHERE option_name LIKE %s 
                     AND option_value < %s",
                    '_transient_timeout_' . self::CACHE_GROUP . '_%',
                    time()
                )
            );
            
            return [
                'total_entries' => (int) $total_entries,
                'expired_entries' => (int) $expired_entries,
                'active_entries' => (int) $total_entries - (int) $expired_entries,
                'cache_version' => $this->cache_version,
                'message' => 'Pierre\'s cache statistics! 🪨'
            ];
            
        } catch (\Exception $e) {
            error_log("Pierre encountered an error getting cache stats: " . $e->getMessage() . " 😢");
            return [
                'total_entries' => 0,
                'expired_entries' => 0,
                'active_entries' => 0,
                'cache_version' => $this->cache_version,
                'message' => 'Pierre couldn\'t get cache stats! 😢'
            ];
        }
    }
    
    /**
     * Pierre gets his cache manager status! 🪨
     * 
     * @since 1.0.0
     * @return array Cache manager status
     */
    public function get_status(): array {
        return [
            'cache_enabled' => true,
            'cache_version' => $this->cache_version,
            'stats' => $this->get_stats(),
            'message' => 'Pierre\'s cache manager is active! 🪨'
        ];
    }
}
