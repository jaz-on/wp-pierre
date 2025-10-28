<?php
/**
 * Pierre's project watcher - he monitors translations! 🪨
 * 
 * This class implements the surveillance logic for monitoring
 * WordPress translation projects on translate.wordpress.org.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Surveillance;

use Pierre\Notifications\SlackNotifier;
use Pierre\Performance\CacheManager;
use Pierre\Performance\PerformanceOptimizer;

/**
 * Project Watcher class - Pierre's surveillance system! 🪨
 * 
 * @since 1.0.0
 */
class ProjectWatcher implements WatcherInterface {
    
    /**
     * Pierre's translation scraper - he collects data! 🪨
     * 
     * @var TranslationScraper
     */
    private TranslationScraper $scraper;
    
    /**
     * Pierre's Slack notifier - he sends messages! 🪨
     * 
     * @var SlackNotifier
     */
    private SlackNotifier $notifier;
    
    /**
     * Pierre's cache manager - he caches everything! 🪨
     * 
     * @var CacheManager
     */
    private CacheManager $cache_manager;
    
    /**
     * Pierre's performance optimizer - he makes everything faster! 🪨
     * 
     * @var PerformanceOptimizer
     */
    private PerformanceOptimizer $performance_optimizer;
    
    /**
     * Pierre's surveillance status - he tracks his state! 🪨
     * 
     * @var bool
     */
    private bool $surveillance_active = false;
    
    /**
     * Pierre's watched projects - he remembers what to watch! 🪨
     * 
     * @var array
     */
    private array $watched_projects = [];
    
    /**
     * Pierre's constructor - he prepares his surveillance system! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->scraper = new TranslationScraper();
        $this->notifier = pierre()->get_slack_notifier();
        $this->cache_manager = new CacheManager();
        $this->performance_optimizer = new PerformanceOptimizer();
        $this->load_watched_projects();
    }
    
    /**
     * Pierre starts his surveillance! 🪨
     * 
     * @since 1.0.0
     * @return bool True if surveillance started successfully, false otherwise
     */
    public function start_surveillance(): bool {
        try {
            if ($this->surveillance_active) {
                error_log('Pierre is already watching! 🪨');
                return true;
            }
            
            error_log('Pierre is starting his surveillance... 🪨');
            
            // Pierre gets his watched projects! 🪨
            $projects_to_watch = $this->get_watched_projects();
            
            if (empty($projects_to_watch)) {
                error_log('Pierre has no projects to watch! 😢');
                return false;
            }
            
            // Pierre scrapes data for all projects! 🪨
            $scraped_data = $this->scraper->scrape_multiple_projects($projects_to_watch);
            
            if (empty($scraped_data)) {
                error_log('Pierre failed to scrape any project data! 😢');
                return false;
            }
            
            // Pierre analyzes the data and sends notifications! 🪨
            $this->analyze_and_notify($scraped_data);
            
            $this->surveillance_active = true;
            error_log('Pierre started his surveillance successfully! 🪨');
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error starting surveillance: ' . $e->getMessage() . ' 😢');
            return false;
        }
    }
    
    /**
     * Pierre stops his surveillance! 🪨
     * 
     * @since 1.0.0
     * @return bool True if surveillance stopped successfully, false otherwise
     */
    public function stop_surveillance(): bool {
        try {
            if (!$this->surveillance_active) {
                error_log('Pierre is not currently watching! 🪨');
                return true;
            }
            
            $this->surveillance_active = false;
            error_log('Pierre stopped his surveillance! 🪨');
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error stopping surveillance: ' . $e->getMessage() . ' 😢');
            return false;
        }
    }
    
    /**
     * Pierre checks if he's currently watching! 🪨
     * 
     * @since 1.0.0
     * @return bool True if surveillance is active, false otherwise
     */
    public function is_surveillance_active(): bool {
        return $this->surveillance_active;
    }
    
    /**
     * Pierre gets his surveillance status! 🪨
     * 
     * @since 1.0.0
     * @return array Array containing surveillance status information
     */
    public function get_surveillance_status(): array {
        return [
            'active' => $this->surveillance_active,
            'watched_projects_count' => count($this->watched_projects),
            'watched_projects' => $this->watched_projects,
            'message' => $this->surveillance_active ? 'Pierre is actively watching! 🪨' : 'Pierre is ready to start surveillance! 🪨'
        ];
    }
    
    /**
     * Pierre watches a specific project! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug to watch
     * @param string $locale_code The locale code to monitor
     * @return bool True if project is now being watched, false otherwise
     */
    public function watch_project(string $project_slug, string $locale_code): bool {
        try {
            // Pierre sanitizes his inputs! 🪨
            $project_slug = sanitize_key($project_slug);
            $locale_code = sanitize_key($locale_code);
            
            $project_key = "{$project_slug}_{$locale_code}";
            
            // Pierre checks if he's already watching this project! 🪨
            if (isset($this->watched_projects[$project_key])) {
                error_log("Pierre is already watching {$project_slug} ({$locale_code})! 🪨");
                return true;
            }
            
            // Pierre tests if he can scrape this project! 🪨
            $test_data = $this->scraper->test_scraping($project_slug, $locale_code);
            
            if (!$test_data['success']) {
                error_log("Pierre cannot watch {$project_slug} ({$locale_code}) - scraping failed! 😢");
                return false;
            }
            
            // Pierre adds the project to his watch list! 🪨
            $this->watched_projects[$project_key] = [
                'slug' => $project_slug,
                'locale' => $locale_code,
                'added_at' => time(),
                'last_checked' => null,
                'last_data' => null
            ];
            
            // Pierre saves his watch list! 🪨
            $this->save_watched_projects();
            
            // Pierre invalidates his cache! 🪨
            $this->cache_manager->delete('watched_projects', 'surveillance');
            
            error_log("Pierre is now watching {$project_slug} ({$locale_code})! 🪨");
            return true;
            
        } catch (\Exception $e) {
            error_log("Pierre encountered an error watching {$project_slug} ({$locale_code}): " . $e->getMessage() . ' 😢');
            return false;
        }
    }
    
    /**
     * Pierre stops watching a specific project! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug to stop watching
     * @param string $locale_code The locale code to stop monitoring
     * @return bool True if project is no longer being watched, false otherwise
     */
    public function unwatch_project(string $project_slug, string $locale_code): bool {
        try {
            // Pierre sanitizes his inputs! 🪨
            $project_slug = sanitize_key($project_slug);
            $locale_code = sanitize_key($locale_code);
            
            $project_key = "{$project_slug}_{$locale_code}";
            
            // Pierre checks if he's watching this project! 🪨
            if (!isset($this->watched_projects[$project_key])) {
                error_log("Pierre is not watching {$project_slug} ({$locale_code})! 🪨");
                return true;
            }
            
            // Pierre removes the project from his watch list! 🪨
            unset($this->watched_projects[$project_key]);
            
            // Pierre saves his watch list! 🪨
            $this->save_watched_projects();
            
            error_log("Pierre stopped watching {$project_slug} ({$locale_code})! 🪨");
            return true;
            
        } catch (\Exception $e) {
            error_log("Pierre encountered an error unwatching {$project_slug} ({$locale_code}): " . $e->getMessage() . ' 😢');
            return false;
        }
    }
    
    /**
     * Pierre gets all projects he's currently watching! 🪨
     * 
     * @since 1.0.0
     * @return array Array of projects being watched
     */
    public function get_watched_projects(): array {
        return $this->cache_manager->remember(
            'watched_projects',
            function() {
                return array_values($this->watched_projects);
            },
            5 * MINUTE_IN_SECONDS, // Cache for 5 minutes
            'surveillance'
        );
    }
    
    /**
     * Pierre analyzes scraped data and sends notifications! 🪨
     * 
     * @since 1.0.0
     * @param array $scraped_data Array of scraped project data
     * @return void
     */
    private function analyze_and_notify(array $scraped_data): void {
        $notifications_sent = 0;
        
        foreach ($scraped_data as $project_data) {
            $project_key = "{$project_data['project_slug']}_{$project_data['locale_code']}";
            
            // Pierre gets his previous data! 🪨
            $previous_data = $this->watched_projects[$project_key]['last_data'] ?? null;
            
            // Pierre analyzes the changes! 🪨
            $changes = $this->analyze_changes($project_data, $previous_data);
            
            if (!empty($changes)) {
                // Pierre sends notifications for changes! 🪨
                $this->send_change_notifications($project_data, $changes);
                $notifications_sent++;
            }
            
            // Pierre updates his stored data! 🪨
            $this->watched_projects[$project_key]['last_data'] = $project_data;
            $this->watched_projects[$project_key]['last_checked'] = time();
        }
        
        // Pierre saves his updated data! 🪨
        $this->save_watched_projects();
        
        if ($notifications_sent > 0) {
            error_log("Pierre sent {$notifications_sent} notifications! 🪨");
        } else {
            error_log('Pierre found no changes to report! 🪨');
        }
    }
    
    /**
     * Pierre analyzes changes between current and previous data! 🪨
     * 
     * @since 1.0.0
     * @param array $current_data Current project data
     * @param array|null $previous_data Previous project data
     * @return array Array of detected changes
     */
    private function analyze_changes(array $current_data, ?array $previous_data): array {
        $changes = [];
        
        if ($previous_data === null) {
            // Pierre found new project data! 🪨
            $changes[] = [
                'type' => 'new_project',
                'message' => 'New project added to surveillance',
                'data' => $current_data
            ];
            return $changes;
        }
        
        $current_stats = $current_data['stats'] ?? [];
        $previous_stats = $previous_data['stats'] ?? [];
        
        // Pierre checks for completion changes! 🪨
        $current_completion = $current_stats['completion_percentage'] ?? 0;
        $previous_completion = $previous_stats['completion_percentage'] ?? 0;
        
        if (abs($current_completion - $previous_completion) >= 1) {
            $changes[] = [
                'type' => 'completion_update',
                'message' => 'Translation completion changed',
                'data' => [
                    'current' => $current_data,
                    'previous' => $previous_data,
                    'change' => $current_completion - $previous_completion
                ]
            ];
        }
        
        // Pierre checks for new strings! 🪨
        $current_total = $current_stats['total'] ?? 0;
        $previous_total = $previous_stats['total'] ?? 0;
        
        if ($current_total > $previous_total) {
            $changes[] = [
                'type' => 'new_strings',
                'message' => 'New strings detected',
                'data' => [
                    'current' => $current_data,
                    'previous' => $previous_data,
                    'new_strings_count' => $current_total - $previous_total
                ]
            ];
        }
        
        // Pierre checks for strings needing attention! 🪨
        $current_needs_attention = ($current_stats['waiting'] ?? 0) + ($current_stats['fuzzy'] ?? 0);
        $previous_needs_attention = ($previous_stats['waiting'] ?? 0) + ($previous_stats['fuzzy'] ?? 0);
        
        if ($current_needs_attention > 0 && $current_needs_attention !== $previous_needs_attention) {
            $changes[] = [
                'type' => 'needs_attention',
                'message' => 'Strings need attention',
                'data' => [
                    'current' => $current_data,
                    'previous' => $previous_data,
                    'needs_attention_count' => $current_needs_attention
                ]
            ];
        }
        
        return $changes;
    }
    
    /**
     * Pierre sends notifications for detected changes! 🪨
     * 
     * @since 1.0.0
     * @param array $project_data Current project data
     * @param array $changes Array of detected changes
     * @return void
     */
    private function send_change_notifications(array $project_data, array $changes): void {
        foreach ($changes as $change) {
            $message_builder = $this->notifier->get_message_builder();
            
            switch ($change['type']) {
                case 'new_strings':
                    $message = $message_builder->build_new_strings_message(
                        $change['data']['current'],
                        $change['data']['new_strings_count']
                    );
                    break;
                    
                case 'completion_update':
                    $message = $message_builder->build_completion_update_message(
                        $change['data']['current'],
                        $change['data']['previous']
                    );
                    break;
                    
                case 'needs_attention':
                    $message = $message_builder->build_needs_attention_message(
                        $change['data']['current']
                    );
                    break;
                    
                default:
                    continue 2;
            }
            
            // Pierre sends his notification! 🪨
            $this->notifier->send_notification(
                $message['text'],
                [],
                ['formatted_message' => $message]
            );
        }
    }
    
    /**
     * Pierre loads his watched projects from storage! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function load_watched_projects(): void {
        $this->watched_projects = get_option('pierre_watched_projects', []);
        error_log('Pierre loaded ' . count($this->watched_projects) . ' watched projects! 🪨');
    }
    
    /**
     * Pierre saves his watched projects to storage! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function save_watched_projects(): void {
        update_option('pierre_watched_projects', $this->watched_projects);
        error_log('Pierre saved his watched projects! 🪨');
    }
}