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
    
    /**
     * Pierre scrapes project data from the API! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug to scrape
     * @param string $locale_code The locale code to check
     * @return array|null Project data or null if failed
     */
    public function scrape_project_data(string $project_slug, string $locale_code): ?array {
        try {
            // Pierre checks his cache first! 🪨
            $cache_key = "pierre_project_{$project_slug}_{$locale_code}";
            $cached_data = get_transient($cache_key);
            
            if ($cached_data !== false) {
                error_log("Pierre found cached data for {$project_slug} ({$locale_code})! 🪨");
                return $cached_data;
            }
            
            // Pierre makes his API request! 🪨
            $api_url = $this->build_api_url($project_slug, $locale_code);
            $response = $this->make_api_request($api_url);
            
            if ($response === null) {
                error_log("Pierre failed to get data for {$project_slug} ({$locale_code})! 😢");
                return null;
            }
            
            // Pierre processes the response! 🪨
            $project_data = $this->process_api_response($response, $project_slug, $locale_code);
            
            if ($project_data === null) {
                error_log("Pierre failed to process data for {$project_slug} ({$locale_code})! 😢");
                return null;
            }
            
            // Pierre caches the data! 🪨
            set_transient($cache_key, $project_data, self::CACHE_TIMEOUT);
            
            error_log("Pierre successfully scraped data for {$project_slug} ({$locale_code})! 🪨");
            return $project_data;
            
        } catch (\Exception $e) {
            error_log("Pierre encountered an error scraping {$project_slug} ({$locale_code}): " . $e->getMessage() . ' 😢');
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
        
        error_log('Pierre is scraping ' . count($projects) . ' projects! 🪨');
        
        foreach ($projects as $project) {
            if (!isset($project['slug']) || !isset($project['locale'])) {
                error_log('Pierre found invalid project data! 😢');
                continue;
            }
            
            $data = $this->scrape_project_data($project['slug'], $project['locale']);
            if ($data !== null) {
                $results[] = $data;
            }
        }
        
        error_log('Pierre scraped ' . count($results) . ' projects successfully! 🪨');
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
        // Pierre sanitizes his inputs! 🪨
        $project_slug = sanitize_key($project_slug);
        $locale_code = sanitize_key($locale_code);
        
        return self::API_BASE_URL . "/{$project_slug}/{$locale_code}/";
    }
    
    /**
     * Pierre makes his API request! 🪨
     * 
     * @since 1.0.0
     * @param string $url The API URL to request
     * @return array|null Response data or null if failed
     */
    private function make_api_request(string $url): ?array {
        // Pierre prepares his request arguments! 🪨
        $args = [
            'timeout' => self::REQUEST_TIMEOUT,
            'user-agent' => 'Pierre-WordPress-Translation-Monitor/1.0.0 (https://github.com/your-org/wp-pierre)',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ];
        
        // Pierre makes his request! 🪨
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Pierre encountered a WP error: ' . $response->get_error_message() . ' 😢');
            return null;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log("Pierre got HTTP {$response_code} from API! 😢");
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Pierre failed to decode JSON: ' . json_last_error_msg() . ' 😢');
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
    private function process_api_response(array $response, string $project_slug, string $locale_code): ?array {
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