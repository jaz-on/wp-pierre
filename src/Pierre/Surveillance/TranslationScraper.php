<?php
/**
 * Pierre's translation scraper - he scrapes translate.wordpress.org! 🪨
 * 
 * This class handles scraping translation data from the WordPress
 * Polyglots translation API at translate.wordpress.org.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Surveillance;

/**
 * Translation Scraper class - Pierre's data collection system! 🪨
 * 
 * @since 1.0.0
 */
class TranslationScraper {
    
    /**
     * Pierre's base API URL - he knows where to look! 🪨
     * 
     * @var string
     */
    private const API_BASE_URL = 'https://translate.wordpress.org/api/projects';

    // Type → segment mapping (best effort; fallback to 'meta')
    private const TYPE_SEGMENTS = [
        'core'   => 'wp',
        'plugin' => 'wp-plugins',
        'theme'  => 'wp-themes',
        'meta'   => 'meta',
        'app'    => 'apps',
    ];
    
    /**
     * Pierre's cache timeout - he caches for 1 hour! 🪨
     * 
     * @var int
     */
    private const CACHE_TIMEOUT = HOUR_IN_SECONDS;
    
    /**
     * Pierre's request timeout - he doesn't wait forever! 🪨
     * 
     * @var int
     */
    private const REQUEST_TIMEOUT = 30;

    // Backoff simple en cas d'erreurs API
    private const BACKOFF_SECONDS = 300;

    /** Last response metadata for backoff decisions */
    private int $last_response_code = 0;
    private int $last_retry_after = 0;
    
    /**
     * Pierre scrapes project data from the API! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug to scrape
     * @param string $locale_code The locale code to check
     * @return array|null Project data or null if failed
     */
    public function scrape_project_data(string $project_slug, string $locale_code): ?array {
        // Backward-compat: route vers type 'meta'
        return $this->scrape_typed_project('meta', $project_slug, $locale_code, 'default');
    }

    /**
     * Scrape typed project (core/plugin/theme/meta/app)
     */
    public function scrape_typed_project(string $type, string $project_slug, string $locale_code, string $set = 'default'): ?array {
        try {
            if ($this->is_in_backoff()) {
                error_log('Pierre is in API backoff, skipping request for now. 🪨');
                return null;
            }

            [$cache_key, $prev_key] = $this->get_snapshot_keys($type, $project_slug, $locale_code);

            $cached_data = get_transient($cache_key);
            if ($cached_data !== false) {
                error_log("Pierre found cached data for {$type}:{$project_slug} ({$locale_code})! 🪨");
                return $cached_data;
            }

            // Resolve best segment for this project (cached)
            $resolved_type = $this->resolve_segment_for($type, $project_slug, $locale_code, $set) ?? $type;
            // Per-project backoff
            if ($this->is_in_backoff_for($resolved_type, $project_slug)) {
                return null;
            }
            $api_url = $this->build_typed_api_url($resolved_type, $project_slug, $locale_code, $set);
            $response = $this->make_api_request($api_url);
            if ($response === null) {
                $secs = $this->last_retry_after > 0 ? min($this->last_retry_after, 600) : self::BACKOFF_SECONDS;
                $this->start_backoff_for($resolved_type, $project_slug, $secs);
                error_log("Pierre failed to get data for {$type}:{$project_slug} ({$locale_code})! 😢");
                return null;
            }

            $project_data = $this->process_api_response($response, $project_slug, $locale_code, $type);
            if ($project_data === null) {
                error_log("Pierre failed to process data for {$type}:{$project_slug} ({$locale_code})! 😢");
                return null;
            }

            // Store previous snapshot too (for external deltas if needed)
            $prev = get_transient($prev_key);
            set_transient($prev_key, $project_data, self::CACHE_TIMEOUT);
            set_transient($cache_key, $project_data, self::CACHE_TIMEOUT);

            error_log("Pierre successfully scraped data for {$type}:{$project_slug} ({$locale_code})! 🪨");
            return $project_data;

        } catch (\Exception $e) {
            error_log("Pierre encountered an error scraping {$type}:{$project_slug} ({$locale_code}): " . $e->getMessage() . ' 😢');
            return null;
        }
    }
    
    /**
     * Pierre scrapes multiple projects at once! 🪨
     * 
     * @since 1.0.0
     * @param array $projects Array of project data with 'slug' and 'locale' keys
     * @return array Array of scraped project data
     */
    public function scrape_multiple_projects(array $projects): array {
        $results = [];

        $total = count($projects);
        error_log('Pierre is scraping ' . $total . ' projects! 🪨');

        $i = 0;
        foreach ($projects as $project) {
            if ((int) get_transient('pierre_surv_abort') === 1) {
                break;
            }
            $slug = $project['slug'] ?? ($project['project_slug'] ?? null);
            $locale = $project['locale'] ?? ($project['locale_code'] ?? null);
            $type = $project['type'] ?? 'meta';
            if (!$slug || !$locale) {
                error_log('Pierre found invalid project data! 😢');
                continue;
            }

            $data = $this->scrape_typed_project($type, $slug, $locale, 'default');
            if ($data !== null) {
                $results[] = $data;
            }
            $i++;
            set_transient('pierre_surv_progress', [ 'processed' => $i, 'total' => $total, 'ts' => time() ], 15 * MINUTE_IN_SECONDS);
        }

        error_log('Pierre scraped ' . count($results) . ' projects successfully! 🪨');
        delete_transient('pierre_surv_progress');
        delete_transient('pierre_surv_abort');
        return $results;
    }
    
    /**
     * Pierre builds his API URL! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug
     * @param string $locale_code The locale code
     * @return string The complete API URL
     */
    private function build_api_url(string $project_slug, string $locale_code): string {
        // Deprecated in favor of build_typed_api_url; keep for BC
        $project_slug = sanitize_key($project_slug);
        // Normaliser le code locale (ex: fr_FR)
        $locale_code = preg_replace_callback(
            '/^([a-z]{2})(?:_([a-zA-Z]{2}))?$/',
            static function ($m) {
                return isset($m[2]) ? strtolower($m[1]) . '_' . strtoupper($m[2]) : strtolower($m[1]);
            },
            trim((string) $locale_code)
        );
        return self::API_BASE_URL . "/{$project_slug}/{$locale_code}/";
    }

    private function build_typed_api_url(string $type, string $project_slug, string $locale_code, string $set = 'default'): string {
        // Pierre sanitizes his inputs! 🪨
        $type = sanitize_key($type);
        $project_slug = sanitize_key($project_slug);
        // Normaliser le code locale (ex: fr_FR)
        $locale_code = preg_replace_callback(
            '/^([a-z]{2})(?:_([a-zA-Z]{2}))?$/',
            static function ($m) {
                return isset($m[2]) ? strtolower($m[1]) . '_' . strtoupper($m[2]) : strtolower($m[1]);
            },
            trim((string) $locale_code)
        );
        $set = sanitize_key($set);

        $segment = self::TYPE_SEGMENTS[$type] ?? self::TYPE_SEGMENTS['meta'];
        return trailingslashit(self::API_BASE_URL . "/{$segment}/{$project_slug}/{$locale_code}/{$set}");
    }
    
    /**
     * Pierre makes his API request! 🪨
     * 
     * @since 1.0.0
     * @param string $url The API URL to request
     * @return array|null Response data or null if failed
     */
    private function make_api_request(string $url): ?array {
        $defaults = \Pierre\Plugin::get_http_defaults();
        $args = [
            'timeout' => $defaults['timeout'] ?? self::REQUEST_TIMEOUT,
            'redirection' => $defaults['redirection'] ?? 3,
            'user-agent' => $defaults['user-agent'] ?? 'wp-pierre/1.0.0; ' . home_url('/'),
            'headers' => $defaults['headers'] ?? [ 'Accept' => 'application/json', 'Content-Type' => 'application/json' ],
        ];

        $this->last_response_code = 0;
        $this->last_retry_after = 0;

        $t0 = microtime(true);
        $response = wp_remote_get($url, $args);
        $ms = (int) round((microtime(true) - $t0) * 1000);

        if (is_wp_error($response)) {
            do_action('wp_pierre_debug', 'api_call', ['url'=>$url,'code'=>0,'ms'=>$ms,'error'=>$response->get_error_message(),'source'=>'TranslationScraper']);
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $this->last_response_code = $code;
        if ($code >= 500 || $code === 0) {
            // Retry once on server/network errors
            usleep(250000);
            $t1 = microtime(true);
            $response = wp_remote_get($url, $args);
            $ms2 = (int) round((microtime(true) - $t1) * 1000);
            $code = (int) wp_remote_retrieve_response_code($response);
            $this->last_response_code = $code;
            do_action('wp_pierre_debug', 'api_call', ['url'=>$url,'code'=>$code,'ms'=>$ms2,'source'=>'TranslationScraper','action'=>'retry']);
        } else {
            do_action('wp_pierre_debug', 'api_call', ['url'=>$url,'code'=>$code,'ms'=>$ms,'source'=>'TranslationScraper']);
        }

        if ($code === 429) {
            $ra = wp_remote_retrieve_header($response, 'retry-after');
            $this->last_retry_after = is_numeric($ra) ? (int) $ra : 0;
        }
        if ($code !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $data;
    }
    
    /**
     * Pierre processes the API response! 🪨
     * 
     * @since 1.0.0
     * @param array $response The API response data
     * @param string $project_slug The project slug
     * @param string $locale_code The locale code
     * @return array|null Processed project data or null if failed
     */
    private function process_api_response(array $response, string $project_slug, string $locale_code, string $project_type = 'meta'): ?array {
        try {
            // Pierre extracts the translation data! 🪨
            $translation_data = $response['translation_sets'][0] ?? null;
            
            if ($translation_data === null) {
                error_log("Pierre found no translation data for {$project_slug} ({$locale_code})! 😢");
                return null;
            }
            
            // Pierre calculates the statistics! 🪨
            $stats = $this->calculate_translation_stats($translation_data);
            
            // Pierre builds his project data! 🪨
            $project_data = [
                'project_type' => $project_type,
                'project_slug' => $project_slug,
                'locale_code' => $locale_code,
                'project_name' => $translation_data['name'] ?? $project_slug,
                'locale_name' => $translation_data['locale'] ?? $locale_code,
                'stats' => $stats,
                'last_updated' => current_time('mysql'),
                'scraped_at' => time()
            ];
            
            return $project_data;
            
        } catch (\Exception $e) {
            error_log('Pierre failed to process API response: ' . $e->getMessage() . ' 😢');
            return null;
        }
    }
    
    /**
     * Pierre calculates translation statistics! 🪨
     * 
     * @since 1.0.0
     * @param array $translation_data The translation data from API
     * @return array Calculated statistics
     */
    private function calculate_translation_stats(array $translation_data): array {
        $waiting = (int) ($translation_data['waiting'] ?? 0);
        $fuzzy = (int) ($translation_data['fuzzy'] ?? 0);
        $translated = (int) ($translation_data['translated'] ?? 0);
        $untranslated = (int) ($translation_data['untranslated'] ?? 0);
        
        $total = $waiting + $fuzzy + $translated + $untranslated;
        $completed = $translated;
        $completion_percentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0;
        
        return [
            'total' => $total,
            'translated' => $translated,
            'untranslated' => $untranslated,
            'fuzzy' => $fuzzy,
            'waiting' => $waiting,
            'completion_percentage' => $completion_percentage,
            'needs_attention' => $waiting + $fuzzy
        ];
    }

    private function get_snapshot_keys(string $type, string $slug, string $locale): array {
        $base = 'pierre_project_' . sanitize_key($type) . '_' . sanitize_key($slug) . '_' . sanitize_key($locale);
        return [$base, $base . '_prev'];
    }

    /**
     * Cache helpers for segment resolution per (type,slug)
     */
    private function get_segment_cache(): array {
        $cache = get_option('pierre_segments_cache');
        return is_array($cache) ? $cache : [];
    }
    private function set_segment_cache(array $map): void {
        update_option('pierre_segments_cache', $map, false);
    }
    private function cache_segment_for(string $type, string $slug, string $resolved): void {
        $map = $this->get_segment_cache();
        $key = strtolower($type . ':' . $slug);
        if (!isset($map[$key]) || $map[$key] !== $resolved) {
            $map[$key] = $resolved;
            $this->set_segment_cache($map);
        }
    }
    private function get_cached_segment_for(string $type, string $slug): ?string {
        $map = $this->get_segment_cache();
        $key = strtolower($type . ':' . $slug);
        return isset($map[$key]) && is_string($map[$key]) ? $map[$key] : null;
    }

    /**
     * Try to resolve the correct segment for a project by probing HEAD/GET.
     * Returns the 'type' (key for TYPE_SEGMENTS) if found, else null.
     */
    private function resolve_segment_for(string $type, string $slug, string $locale, string $set): ?string {
        $type = sanitize_key($type);
        $slug = sanitize_key($slug);
        $locale = sanitize_key($locale);
        $set = sanitize_key($set);

        // Check cache first
        $cached = $this->get_cached_segment_for($type, $slug);
        if (is_string($cached) && $cached !== '') { return $cached; }

        $candidates = array_values(array_unique(array_merge([$type], array_keys(self::TYPE_SEGMENTS))));
        $defaults = \Pierre\Plugin::get_http_defaults();
        $args = [
            'timeout' => $defaults['timeout'] ?? 30,
            'redirection' => $defaults['redirection'] ?? 3,
            'user-agent' => $defaults['user-agent'] ?? 'wp-pierre/1.0.0; ' . home_url('/'),
        ];
        foreach ($candidates as $t) {
            $url = $this->build_typed_api_url($t, $slug, $locale, $set);
            $resp = wp_remote_head($url, $args);
            $code = is_wp_error($resp) ? 0 : (int) wp_remote_retrieve_response_code($resp);
            if ($code === 200 || $code === 405) { // 405: HEAD not allowed → try GET
                if ($code === 405) {
                    $resp2 = wp_remote_get($url, $args);
                    $code2 = is_wp_error($resp2) ? 0 : (int) wp_remote_retrieve_response_code($resp2);
                    if ($code2 !== 200) { continue; }
                }
                $this->cache_segment_for($type, $slug, $t);
                return $t;
            }
        }
        return null;
    }

    private function is_in_backoff(): bool {
        $until = (int) get_transient('pierre_scraper_backoff_until');
        return $until && time() < $until;
    }

    private function start_backoff(): void {
        set_transient('pierre_scraper_backoff_until', time() + self::BACKOFF_SECONDS, self::BACKOFF_SECONDS);
    }

    /** Per-project backoff helpers */
    private function get_backoff_key(string $type, string $slug): string {
        return 'pierre_scraper_backoff_until_' . sanitize_key($type . '_' . $slug);
    }
    private function is_in_backoff_for(string $type, string $slug): bool {
        $until = (int) get_transient($this->get_backoff_key($type, $slug));
        return $until && time() < $until;
    }
    private function start_backoff_for(string $type, string $slug, int $seconds): void {
        $seconds = max(60, (int) $seconds);
        set_transient($this->get_backoff_key($type, $slug), time() + $seconds, $seconds);
        do_action('wp_pierre_debug', 'backoff_set', ['source'=>'TranslationScraper','action'=>'set','code'=>$this->last_response_code]);
    }
    
    /**
     * Pierre tests his scraping system! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug to test
     * @param string $locale_code The locale code to test
     * @return array Test results
     */
    public function test_scraping(string $project_slug = 'wp', string $locale_code = 'fr'): array {
        error_log("Pierre is testing his scraping system with {$project_slug} ({$locale_code})! 🪨");
        
        $start_time = microtime(true);
        $data = $this->scrape_project_data($project_slug, $locale_code);
        $end_time = microtime(true);
        
        $response_time = round(($end_time - $start_time) * 1000, 2);
        
        return [
            'success' => $data !== null,
            'response_time_ms' => $response_time,
            'data' => $data,
            'message' => $data ? 'Pierre\'s scraping test passed! 🪨' : 'Pierre\'s scraping test failed! 😢'
        ];
    }
    
    /**
     * Pierre gets his scraping status! 🪨
     * 
     * @since 1.0.0
     * @return array Scraping system status
     */
    public function get_status(): array {
        return [
            'api_base_url' => self::API_BASE_URL,
            'cache_timeout' => self::CACHE_TIMEOUT,
            'request_timeout' => self::REQUEST_TIMEOUT,
            'message' => 'Pierre\'s scraping system is ready! 🪨'
        ];
    }
}