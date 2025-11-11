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
use Pierre\Security\Encryption;
use Pierre\Settings\Settings;
use Pierre\Logging\Logger;

/**
 * Project Watcher class - Pierre's surveillance system! 🪨
 * 
 * @since 1.0.0
 */
class ProjectWatcher implements WatcherInterface {
    /**
     * Check if debug mode is enabled.
     *
     * @since 1.0.0
     * @return bool True if PIERRE_DEBUG constant is defined and truthy, false otherwise.
     */
    private function is_debug(): bool { return defined('PIERRE_DEBUG') ? (bool) PIERRE_DEBUG : false; }

    /**
     * Log a debug message if debug is enabled.
     *
     * @since 1.0.0
     * @param string $m Debug message to log.
     * @return void
     */
    private function log_debug(string $m): void { if ($this->is_debug()) { do_action('wp_pierre_debug', $m, ['source' => 'ProjectWatcher']); } }
    
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
                Logger::static_debug('Pierre is already watching! 🪨', ['source' => 'ProjectWatcher']);
                return true;
            }
            
            Logger::static_debug('Pierre is starting his surveillance... 🪨', ['source' => 'ProjectWatcher']);
            
            // Pierre gets his watched projects! 🪨
            $projects = $this->get_watched_projects();

            // Initialize next_check field if missing and apply jitter
            $now = time();
            foreach ($projects as $k => $p) {
                if (empty($p['next_check'])) {
                    $projects[$k]['next_check'] = $now + wp_rand(0, 300);
                }
            }

            // Only process projects due for a check (staggered by next_check)
            $projects_to_watch = array_values(array_filter($projects, function($p) use ($now) {
                return (int)($p['next_check'] ?? 0) <= $now;
            }));

            if (empty($projects_to_watch)) {
                Logger::static_debug('Pierre has no projects to watch! 😢', ['source' => 'ProjectWatcher']);
                return false;
            }

            // Shuffle to distribute load over time
            shuffle($projects_to_watch);

            // Max per check (from settings, default 10); acts as pagination window
            $settings = Settings::all();
            $max = (int) ($settings['max_projects_per_check'] ?? 50);
            if ($max > 0) {
                $projects_to_watch = array_slice($projects_to_watch, 0, $max);
            }

            // Pierre scrapes data for all projects! 🪨
            $scraped_data = $this->scraper->scrape_multiple_projects($projects_to_watch);
            
            if (empty($scraped_data)) {
                Logger::static_debug('Pierre failed to scrape any project data! 😢', ['source' => 'ProjectWatcher']);
                return false;
            }
            
            // Pierre analyzes the data and sends notifications! 🪨
            $this->analyze_and_notify($scraped_data);
            
            $this->surveillance_active = true;
            Logger::static_debug('Pierre started his surveillance successfully! 🪨', ['source' => 'ProjectWatcher']);
            
            return true;
            
        } catch (\Exception $e) {
            Logger::static_debug('Pierre encountered an error starting surveillance: ' . $e->getMessage() . ' 😢', ['source' => 'ProjectWatcher']);
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
                Logger::static_debug('Pierre is not currently watching! 🪨', ['source' => 'ProjectWatcher']);
                return true;
            }
            
            $this->surveillance_active = false;
            Logger::static_debug('Pierre stopped his surveillance! 🪨', ['source' => 'ProjectWatcher']);
            
            return true;
            
        } catch (\Exception $e) {
            Logger::static_debug('Pierre encountered an error stopping surveillance: ' . $e->getMessage() . ' 😢', ['source' => 'ProjectWatcher']);
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
     * Flush caches and runtime data related to surveillance.
     *
     * @since 1.0.0
     * @return void
     */
    public function flush_cache(): void {
        // Invalidate all plugin cache groups at once
        try {
            $this->cache_manager->flush_all_plugin_groups();
        } catch (\Exception $e) {
            // Fallback: invalidate groups individually
            try { $this->cache_manager->flush_group('pierre'); } catch (\Exception $e2) {}
            try { $this->cache_manager->flush_group('surveillance'); } catch (\Exception $e2) {}
            try { $this->cache_manager->flush_group('api'); } catch (\Exception $e2) {}
            try { $this->cache_manager->flush_group('database'); } catch (\Exception $e2) {}
        }

        // Clear options used for discovery cache/logs so the next run rebuilds
        delete_option('pierre_locales_cache');
        delete_option('pierre_selected_locales');
        delete_option('pierre_locales_log');
    }

    /**
     * Clear all persisted surveillance data (keeps settings).
     *
     * @since 1.0.0
     * @return void
     */
    public function clear_all_data(): void {
        // Reset watched projects store
        $this->watched_projects = [];
        update_option('pierre_watched_projects', $this->watched_projects);

        // Clear discovery caches/logs
        delete_option('pierre_locales_cache');
        delete_option('pierre_selected_locales');
        delete_option('pierre_locales_log');

        // Clear last run trackers
        delete_option('pierre_last_surv_run');
        delete_option('pierre_last_surv_duration_ms');
        delete_option('pierre_last_digest_run');
        delete_option('pierre_last_digest_duration_ms');

        // Flush transient/object caches
        $this->flush_cache();
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
    public function watch_project(string $project_slug, string $locale_code, string $project_type = 'meta'): bool {
        try {
            // Pierre sanitizes his inputs! 🪨
            $project_slug = sanitize_key($project_slug);
            // Normaliser le code locale (ex: fr_FR) using helper
            $locale_code = \Pierre\Helpers\OptionHelper::sanitize_locale_code(trim((string) $locale_code));
            if (empty($locale_code)) {
                Logger::static_warning("Invalid locale code provided for project {$project_slug}", ['source' => 'ProjectWatcher']);
                return false;
            }
            
            $project_key = "{$project_slug}_{$locale_code}";
            $project_type = sanitize_key($project_type ?: 'meta');
            
            // Pierre checks if he's already watching this project! 🪨
            if (isset($this->watched_projects[$project_key])) {
                Logger::static_debug("Pierre is already watching {$project_slug} ({$locale_code})! 🪨", ['source' => 'ProjectWatcher']);
                return true;
            }
            
            // Pierre tests if he can scrape this project! 🪨
            $test_data = $this->scraper->test_scraping($project_slug, $locale_code);
            
            if (!$test_data['success']) {
                Logger::static_debug("Pierre cannot watch {$project_slug} ({$locale_code}) - scraping failed! 😢", ['source' => 'ProjectWatcher']);
                return false;
            }
            
            // Pierre adds the project to his watch list! 🪨
            $this->watched_projects[$project_key] = [
                'slug' => $project_slug,
                'locale' => $locale_code,
                'type' => $this->watched_projects[$project_key]['type'] ?? $project_type,
                'added_at' => time(),
                'last_checked' => null,
                'last_data' => $test_data['data'] ?? null,
                // next check time with small jitter to avoid thundering herd
                'next_check' => time() + wp_rand(0, 300)
            ];
            
            // Pierre saves his watch list! 🪨
            $this->save_watched_projects();
            
            // Pierre invalidates his cache! 🪨
            $this->cache_manager->delete('watched_projects', 'surveillance');
            
            Logger::static_debug("Pierre is now watching {$project_slug} ({$locale_code})! 🪨", ['source' => 'ProjectWatcher']);
            return true;
            
        } catch (\Exception $e) {
            Logger::static_debug("Pierre encountered an error watching {$project_slug} ({$locale_code}): " . $e->getMessage() . ' 😢', ['source' => 'ProjectWatcher']);
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
            // Normaliser le code locale (ex: fr_FR)
            $locale_code = preg_replace_callback(
                '/^([a-z]{2})(?:_([a-zA-Z]{2}))?$/',
                static function ($m) {
                    return isset($m[2]) ? strtolower($m[1]) . '_' . strtoupper($m[2]) : strtolower($m[1]);
                },
                trim((string) $locale_code)
            );
            
            $project_key = "{$project_slug}_{$locale_code}";
            
            // Pierre checks if he's watching this project! 🪨
            if (!isset($this->watched_projects[$project_key])) {
                Logger::static_debug("Pierre is not watching {$project_slug} ({$locale_code})! 🪨", ['source' => 'ProjectWatcher']);
                return true;
            }
            
            // Pierre removes the project from his watch list! 🪨
            unset($this->watched_projects[$project_key]);
            
            // Pierre saves his watch list! 🪨
            $this->save_watched_projects();
            
            Logger::static_debug("Pierre stopped watching {$project_slug} ({$locale_code})! 🪨", ['source' => 'ProjectWatcher']);
            return true;
            
        } catch (\Exception $e) {
            Logger::static_debug("Pierre encountered an error unwatching {$project_slug} ({$locale_code}): " . $e->getMessage() . ' 😢', ['source' => 'ProjectWatcher']);
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
            
            // Pierre updates his stored data and schedules next_check with jitter! 🪨
            $this->watched_projects[$project_key]['last_data'] = $project_data;
            $this->watched_projects[$project_key]['last_checked'] = time();
            $interval_minutes = (int)($settings['surveillance_interval'] ?? 15);
            $this->watched_projects[$project_key]['next_check'] = time() + max(60, $interval_minutes * 60) + wp_rand(0, 300);
        }
        
        // Pierre saves his updated data! 🪨
        $this->save_watched_projects();
        
        if ($notifications_sent > 0) { Logger::static_debug("Pierre sent {$notifications_sent} notifications! 🪨", ['source' => 'ProjectWatcher']); }
        else { Logger::static_debug('Pierre found no changes to report! 🪨', ['source' => 'ProjectWatcher']); }
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

        // Milestones crossed (e.g., 50/80/100)
        $locale_for_cfg = $current_data['locale_code'] ?? ($current_data['locale'] ?? '');
        $milestones = $this->get_milestones($locale_for_cfg);
        foreach ($milestones as $m) {
            if ($previous_completion < $m && $current_completion >= $m) {
                $changes[] = [
                    'type' => 'milestone',
                    'message' => 'Milestone reached',
                    'data' => [
                        'current' => $current_data,
                        'milestone' => $m
                    ]
                ];
            }
        }
        
        // Pierre checks for new strings! 🪨
        $current_total = $current_stats['total'] ?? 0;
        $previous_total = $previous_stats['total'] ?? 0;
        
        if ($current_total > $previous_total) {
            $new_strings = $current_total - $previous_total;
            $threshold = $this->get_new_strings_threshold($locale_for_cfg);
            if ($new_strings >= $threshold) {
                $changes[] = [
                    'type' => 'new_strings',
                    'message' => 'New strings detected',
                    'data' => [
                        'current' => $current_data,
                        'previous' => $previous_data,
                        'new_strings_count' => $new_strings
                    ]
                ];
            }
        }
        
        // Pierre checks for approvals (translations approved) 🪨
        $curr_translated = (int)($current_stats['translated'] ?? 0);
        $prev_translated = (int)($previous_stats['translated'] ?? 0);
        $approved = $curr_translated - $prev_translated;
        if ($approved > 0) {
            $changes[] = [
                'type' => 'approval',
                'message' => 'Recent approvals',
                'data' => [
                    'current' => $current_data,
                    'approved_count' => $approved
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
                case 'approval':
                    if (method_exists($message_builder, 'build_approval_message')) {
                        $message = $message_builder->build_approval_message(
                            $change['data']['current'],
                            (int) ($change['data']['approved_count'] ?? 0)
                        );
                    } else {
                        continue 2;
                    }
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
                case 'milestone':
                    if (method_exists($message_builder, 'build_milestone_message')) {
                        $message = $message_builder->build_milestone_message(
                            $change['data']['current'],
                            (int) ($change['data']['milestone'] ?? 0)
                        );
                    } else {
                        continue 2;
                    }
                    break;
                    
                default:
                    continue 2;
            }
            
            $locale = $project_data['locale_code'] ?? ($project_data['locale'] ?? '');

            // Context for scope filtering
            $context = [
                'event_type' => $change['type'],
                'locale' => $locale,
                'project' => [
                    'type' => (string)($project_data['project_type'] ?? ($project_data['type'] ?? 'meta')),
                    'slug' => (string)($project_data['project_slug'] ?? ($project_data['slug'] ?? '')),
                ],
                'metrics' => [
                    'new_strings_count' => (int)($change['data']['new_strings_count'] ?? 0),
                    'milestone' => (int)($change['data']['milestone'] ?? 0),
                ],
            ];

            // Dispatch to global webhook (unified config)
            $gw = $this->get_global_webhook_config();
            if (!empty($gw['enabled']) && !empty($gw['webhook_url'])) {
                $this->evaluate_and_dispatch_webhook($gw, $context, $message, 'global');
            }

            // Dispatch to locale webhook (unified config)
            $lw = $this->get_locale_webhook_config($locale);
            if (!empty($lw['enabled']) && !empty($lw['webhook_url'])) {
                // Implicit scope: this locale only
                $lw['scopes'] = $lw['scopes'] ?? ['locales'=>[$locale],'projects'=>[]];
                if (empty($lw['scopes']['locales'])) { $lw['scopes']['locales'] = [$locale]; }
                $this->evaluate_and_dispatch_webhook($lw, $context, $message, 'locale');
            }
        }
    }

    /**
     * Get unified global webhook configuration.
     *
     * @since 1.0.0
     * @return array Global webhook configuration array with keys: enabled, webhook_url, types, threshold, milestones, mode, digest, scopes.
     */
    private function get_global_webhook_config(): array {
        $settings = Settings::all();
        $gw = (array)($settings['global_webhook'] ?? []);
        // Decrypt webhook URL if present
        if (!empty($gw['webhook_url'])) {
            $decrypted = Encryption::decrypt($gw['webhook_url']);
            $gw['webhook_url'] = ($decrypted !== false) ? $decrypted : $gw['webhook_url'];
        }
        // Back-compat: map legacy if needed
        if (empty($gw) && !empty($settings['slack_webhook_url'])) {
            $raw_legacy = $settings['slack_webhook_url'];
            $decrypted_legacy = Encryption::decrypt($raw_legacy);
            $legacy_url = ($decrypted_legacy !== false) ? $decrypted_legacy : $raw_legacy;
            $gw = [
                'enabled' => !empty($settings['notifications_enabled']),
                'webhook_url' => $legacy_url,
                'types' => (array)($settings['notification_types'] ?? ['new_strings','completion_update','needs_attention','milestone']),
                'threshold' => (int)(($settings['notification_defaults']['new_strings_threshold'] ?? 20)),
                'milestones' => (array)(($settings['notification_defaults']['milestones'] ?? [50,80,100])),
                'mode' => (string)(($settings['notification_defaults']['mode'] ?? 'immediate')),
                'digest' => (array)(($settings['notification_defaults']['digest'] ?? ['type'=>'interval','interval_minutes'=>60,'fixed_time'=>'09:00'])),
                'scopes' => [ 'locales' => [], 'projects' => [] ],
            ];
        }
        return $gw;
    }

    /**
     * Get unified locale webhook configuration.
     *
     * @since 1.0.0
     * @param string $locale Locale code (e.g., 'fr_FR').
     * @return array Locale webhook configuration array with keys: enabled, webhook_url, types, threshold, milestones, mode, digest, scopes.
     */
    private function get_locale_webhook_config(string $locale): array {
        $settings = Settings::all();
        $lw = (array)($settings['locales'][$locale]['webhook'] ?? []);
        // Decrypt webhook URL if present
        if (!empty($lw['webhook_url'])) {
            $decrypted = Encryption::decrypt($lw['webhook_url']);
            $lw['webhook_url'] = ($decrypted !== false) ? $decrypted : $lw['webhook_url'];
        }
        // Back-compat: map legacy single URL if present
        if (empty($lw) && !empty($settings['locales_slack'][$locale] ?? '')) {
            $raw_legacy = $settings['locales_slack'][$locale];
            $decrypted_legacy = Encryption::decrypt($raw_legacy);
            $legacy_url = ($decrypted_legacy !== false) ? $decrypted_legacy : $raw_legacy;
            $lw = [
                'enabled' => true,
                'webhook_url' => $legacy_url,
                'types' => (array)($settings['notification_types'] ?? ['new_strings','completion_update','needs_attention','milestone']),
            ];
        }
        return $lw;
    }

    /**
     * Evaluate types/scopes/thresholds and send (or enqueue) webhook notification.
     *
     * @since 1.0.0
     * @param array  $webhook Webhook configuration array.
     * @param array  $context Context array with keys: event_type, locale, project, metrics.
     * @param array  $message Formatted message array.
     * @param string $channel Channel identifier ('global' or 'locale').
     * @return void
     */
    private function evaluate_and_dispatch_webhook(array $webhook, array $context, array $message, string $channel): void {
        $type = (string)$context['event_type'];
        $allowed = (array)($webhook['types'] ?? []);
        if (!in_array($type, $allowed, true)) { return; }

        // Scope filtering
        $sc = (array)($webhook['scopes'] ?? []);
        $sc_locales = (array)($sc['locales'] ?? []);
        $sc_projects = (array)($sc['projects'] ?? []);
        if (!empty($sc_locales) && !in_array($context['locale'], $sc_locales, true)) { return; }
        if (!empty($sc_projects)) {
            $hit = false;
            foreach ($sc_projects as $p) {
                if (($p['type'] ?? '') === ($context['project']['type'] ?? '') && ($p['slug'] ?? '') === ($context['project']['slug'] ?? '')) { $hit = true; break; }
            }
            if (!$hit) { return; }
        }

        // Per-webhook thresholds
        if ($type === 'new_strings') {
            $min = isset($webhook['threshold']) ? (int)$webhook['threshold'] : 0;
            if (($context['metrics']['new_strings_count'] ?? 0) < $min) { return; }
        }
        if ($type === 'milestone') {
            $req = (array)($webhook['milestones'] ?? []);
            if (!empty($req) && !in_array((int)($context['metrics']['milestone'] ?? 0), array_map('intval', $req), true)) { return; }
        }

        // Dispatch mode
        $mode = (string)($webhook['mode'] ?? '');
        if ($mode === 'digest') {
            $queue_key = $channel === 'global' ? 'pierre_digest_queue_global' : ('pierre_digest_queue_' . $context['locale']);
            $queue = get_transient($queue_key);
            if (!is_array($queue)) { $queue = []; }
            $queue[] = [
                'type' => $type,
                'project_data' => $context['project'] + ['locale_code' => $context['locale']],
                'message' => $message,
                'ts' => time(),
                'channel' => $channel,
            ];
            set_transient($queue_key, $queue, 12 * HOUR_IN_SECONDS);
            return;
        }

        // Immediate send
        $url = (string) ($webhook['webhook_url'] ?? '');
        if ($url !== '') {
            $this->notifier->send_with_webhook_override($message['text'], $url, ['formatted_message' => $message]);
        }
    }
    
    /**
     * Pierre loads his watched projects from storage! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function load_watched_projects(): void {
        $this->watched_projects = \Pierre\Helpers\OptionHelper::get_option_array('pierre_watched_projects', []);
        Logger::static_debug('Pierre loaded ' . count($this->watched_projects) . ' watched projects! 🪨', ['source' => 'ProjectWatcher']);
    }
    
    /**
     * Pierre saves his watched projects to storage! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function save_watched_projects(): void {
        update_option('pierre_watched_projects', $this->watched_projects);
        Logger::static_debug('Pierre saved his watched projects! 🪨', ['source' => 'ProjectWatcher']);
    }

    /**
     * Pierre tests his surveillance system! 🪨
     *
     * This performs a dry run to verify Slack readiness and API scraping
     * for at least one project/locale.
     *
     * @since 1.0.0
     * @return array { 'success':bool, 'reason':string, 'message':string, 'details':mixed }
     */
    public function test_surveillance(): array {
        try {
            // 1) Stricte: refuser le test s'il n'y a aucun projet surveillé
            $projects = $this->get_watched_projects();
            if (empty($projects)) {
                return [
                    'success' => false,
                    'reason' => 'no_projects',
                    'message' => __('No watched projects yet. Add a locale and a project before testing.', 'wp-pierre'),
                ];
            }

            // 2) Prendre le premier projet réel
            $candidate = [
                'slug' => $projects[0]['slug'] ?? '',
                'locale' => $projects[0]['locale'] ?? '',
            ];
            if (empty($candidate['slug']) || empty($candidate['locale'])) {
                return [
                    'success' => false,
                    'reason' => 'no_projects',
                    'message' => __('Invalid watched project data. Please re-add the project.', 'wp-pierre'),
                ];
            }

            // 3) Test scraping API
            $scrape = $this->scraper->test_scraping($candidate['slug'], $candidate['locale']);
            if (empty($scrape['success'])) {
                return [
                    'success' => false,
                    'reason' => 'api_error',
                    'message' => sprintf(
                        // translators: 1: project slug, 2: project type
                        __('Failed to scrape %1$s (%2$s). Check the locale code matches wordpress.org locales.', 'wp-pierre'),
                        $candidate['slug'],
                        $candidate['locale']
                    ),
                    'details' => $scrape,
                ];
            }

            // 4) Ping Slack (dry run) – use locale override if configured, else global
            $override = $this->get_locale_webhook($candidate['locale']);
            if (empty($override) && !$this->notifier->is_ready()) {
                return [
                    'success' => false,
                    'reason' => 'slack_not_ready',
                    'message' => __('Slack webhook is not configured (neither global nor per-locale).', 'wp-pierre'),
                ];
            }

            $ok = !empty($override)
                ? $this->notifier->test_notification_for_webhook($override, __('Pierre dry run: notifications OK (locale).', 'wp-pierre'))
                : $this->notifier->test_notification(__('Pierre dry run: notifications OK.', 'wp-pierre'));
            if (!$ok) {
                return [
                    'success' => false,
                    'reason' => 'slack_send_error',
                    'message' => __('Slack test failed. Verify webhook URL and Slack response.', 'wp-pierre'),
                ];
            }

            return [
                'success' => true,
                'reason' => 'ok',
                'message' => __('Dry run succeeded. You can now start surveillance.', 'wp-pierre'),
                'details' => [ 'project' => $candidate, 'scrape' => $scrape ],
            ];
        } catch (\Exception $e) {
            Logger::static_debug('Pierre encountered an error during test surveillance: ' . $e->getMessage() . ' 😢', ['source' => 'ProjectWatcher']);
            return [
                'success' => false,
                'reason' => 'unexpected_error',
                'message' => __('Unexpected error during test surveillance.', 'wp-pierre'),
            ];
        }
    }

    /**
     * Get Slack webhook URL configured for a given locale (if any)
     *
     * @since 1.0.0
     */
    private function get_locale_webhook(string $locale_code): ?string {
        $cfg = $this->get_locale_webhook_config($locale_code);
        $url = (string)($cfg['webhook_url'] ?? '');
        return $url !== '' ? $url : null;
    }

    private function get_settings(): array {
        return Settings::all();
    }

    private function get_locale_config(string $locale): array {
        $settings = $this->get_settings();
        $local = (array) ($settings['locales'][$locale] ?? []);
        $defaults = (array) ($settings['notification_defaults'] ?? []);
        return [$defaults, $local];
    }

    private function get_new_strings_threshold(string $locale): int {
        [$defaults, $local] = $this->get_locale_config($locale);
        if (isset($local['new_strings_threshold'])) { return max(0, (int) $local['new_strings_threshold']); }
        return max(0, (int) ($defaults['new_strings_threshold'] ?? 20));
    }

    private function get_milestones(string $locale): array {
        [$defaults, $local] = $this->get_locale_config($locale);
        $list = $local['milestones'] ?? ($defaults['milestones'] ?? [50,80,100]);
        if (!is_array($list)) { return [50,80,100]; }
        $list = array_values(array_unique(array_map('intval', $list)));
        sort($list);
        return $list;
    }

    private function get_locale_mode(string $locale_code): string {
        $settings = $this->get_settings();
        $local = (array) ($settings['locales'][$locale_code] ?? []);
        if (!empty($local['mode'])) { return (string) $local['mode']; }
        return (string) ($settings['notification_defaults']['mode'] ?? 'immediate');
    }

    private function enqueue_digest(string $locale_code, array $item): void {
        if (empty($locale_code)) { return; }
        $key = 'pierre_digest_queue_' . $locale_code;
        $queue = get_transient($key);
        if (!is_array($queue)) { $queue = []; }
        $queue[] = $item;
        set_transient($key, $queue, 12 * HOUR_IN_SECONDS);
    }
}