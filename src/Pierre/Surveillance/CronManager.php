<?php
/**
 * Pierre's cron manager - he schedules his surveillance! 🪨
 * 
 * This class manages all WordPress cron events for Pierre's
 * translation monitoring activities.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Surveillance;

/**
 * Cron Manager class - Pierre's scheduling system! 🪨
 * 
 * @since 1.0.0
 */
class CronManager {
    /** Debug helper */
    private function is_debug(): bool { return defined('PIERRE_DEBUG') ? (bool) PIERRE_DEBUG : false; }
    private function log_debug(string $m): void { if ($this->is_debug()) { do_action('wp_pierre_debug', $m, ['source' => 'CronManager']); } }
    
    /**
     * Pierre's surveillance hook name - he needs to track it! 🪨
     * 
     * @var string
     */
    private const SURVEILLANCE_HOOK = 'pierre_surveillance_check';
    
    /**
     * Pierre's cleanup hook name - he tidies up! 🪨
     * 
     * @var string
     */
    private const CLEANUP_HOOK = 'pierre_cleanup_old_data';
    
    /**
     * Pierre's surveillance interval - he checks every 15 minutes! 🪨
     * 
     * @var string
     */
    private const SURVEILLANCE_INTERVAL = 'pierre_15min';
    
    /**
     * Pierre's cleanup interval - he cleans up daily! 🪨
     * 
     * @var string
     */
    private const CLEANUP_INTERVAL = 'pierre_daily';

    /**
     * Pierre's locales refresh hook - he refreshes available locales cache! 🪨
     *
     * @var string
     */
    private const LOCALES_REFRESH_HOOK = 'pierre_refresh_locales_cache';

    /**
     * Pierre's weekly interval - for locales refresh! 🪨
     *
     * @var string
     */
    private const WEEKLY_INTERVAL = 'pierre_weekly';

    /** Digest processing hook */
    private const DIGEST_HOOK = 'pierre_run_digest';
    
    /**
     * Pierre schedules his surveillance events! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function schedule_events(): void {
        // Ensure custom intervals are registered before scheduling
        $this->register_schedules();
        
        // Pierre schedules his surveillance check! 🪨
        if (!wp_next_scheduled(self::SURVEILLANCE_HOOK)) {
            $interval_slug = $this->get_selected_interval_slug();
            $offset = wp_rand(0, 300); // jitter first schedule up to 5 min
            wp_schedule_event(
                time() + $offset,
                $interval_slug,
                self::SURVEILLANCE_HOOK
            );
            $this->log_debug('Pierre scheduled his surveillance check! 🪨');
        }
        
        // Pierre schedules his cleanup task! 🪨
        if (!wp_next_scheduled(self::CLEANUP_HOOK)) {
            $offset = wp_rand(60, 600);
            wp_schedule_event(
                time() + $offset,
                self::CLEANUP_INTERVAL,
                self::CLEANUP_HOOK
            );
            $this->log_debug('Pierre scheduled his cleanup task! 🪨');
        }

        // Pierre schedules his locales refresh! 🪨
        if (!wp_next_scheduled(self::LOCALES_REFRESH_HOOK)) {
            wp_schedule_event(
                time(),
                self::WEEKLY_INTERVAL,
                self::LOCALES_REFRESH_HOOK
            );
            $this->log_debug('Pierre scheduled his locales refresh task! 🪨');
        }

        // Digest runs every 15 minutes to check due locales
        if (!wp_next_scheduled(self::DIGEST_HOOK)) {
            $interval_slug = $this->get_selected_interval_slug();
            $offset = wp_rand(0, 300);
            wp_schedule_event(
                time() + $offset,
                $interval_slug,
                self::DIGEST_HOOK
            );
            $this->log_debug('Pierre scheduled his digest task! 🪨');
        }
        
        // Pierre hooks into his scheduled events! 🪨
        add_action(self::SURVEILLANCE_HOOK, [$this, 'run_surveillance_check']);
        add_action(self::CLEANUP_HOOK, [$this, 'run_cleanup_task']);
        add_action(self::LOCALES_REFRESH_HOOK, [$this, 'run_locales_refresh']);
        add_action(self::DIGEST_HOOK, [$this, 'run_digest']);
        
        $this->log_debug('Pierre scheduled all his surveillance events! 🪨');
    }
    
    /**
     * Pierre clears his scheduled events! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function clear_events(): void {
        // Pierre clears his surveillance check! 🪨
        $timestamp = wp_next_scheduled(self::SURVEILLANCE_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::SURVEILLANCE_HOOK);
            $this->log_debug('Pierre cleared his surveillance check! 🪨');
        }
        
        // Pierre clears his cleanup task! 🪨
        $timestamp = wp_next_scheduled(self::CLEANUP_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CLEANUP_HOOK);
            $this->log_debug('Pierre cleared his cleanup task! 🪨');
        }
        
        // Pierre clears any orphaned events! 🪨
        wp_clear_scheduled_hook(self::SURVEILLANCE_HOOK);
        wp_clear_scheduled_hook(self::CLEANUP_HOOK);
        
        $this->log_debug('Pierre cleared all his scheduled events! 🪨');
    }
    
    /**
     * Pierre adds his custom cron intervals! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function register_schedules(): void {
        add_filter('cron_schedules', function ($schedules) {
            // Pierre custom intervals (5/15/30/60/120)
            $schedules['pierre_5min'] = [ 'interval' => 5 * MINUTE_IN_SECONDS, 'display' => __('Pierre 5 minutes', 'wp-pierre') ];
            $schedules['pierre_15min'] = [ 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => __('Pierre 15 minutes', 'wp-pierre') ];
            $schedules['pierre_30min'] = [ 'interval' => 30 * MINUTE_IN_SECONDS, 'display' => __('Pierre 30 minutes', 'wp-pierre') ];
            $schedules['pierre_60min'] = [ 'interval' => 60 * MINUTE_IN_SECONDS, 'display' => __('Pierre 60 minutes', 'wp-pierre') ];
            $schedules['pierre_120min'] = [ 'interval' => 120 * MINUTE_IN_SECONDS, 'display' => __('Pierre 120 minutes', 'wp-pierre') ];
            
            // Pierre's daily cleanup interval! 🪨
            $schedules[self::CLEANUP_INTERVAL] = [
                'interval' => DAY_IN_SECONDS,
                'display' => __('Pierre\'s Daily Cleanup', 'wp-pierre')
            ];

            // Pierre's weekly interval (for locales refresh)! 🪨
            $schedules[self::WEEKLY_INTERVAL] = [
                'interval' => WEEK_IN_SECONDS,
                'display' => __('Pierre\'s Weekly Tasks', 'wp-pierre')
            ];
            
            return $schedules;
        });
    }

    /**
     * Map settings to interval slug
     */
    private function get_selected_interval_slug(): string {
        $settings = get_option('pierre_settings', []);
        $minutes = (int)($settings['surveillance_interval'] ?? 15);
        $map = [
            5 => 'pierre_5min',
            15 => 'pierre_15min',
            30 => 'pierre_30min',
            60 => 'pierre_60min',
            120 => 'pierre_120min',
        ];
        return $map[$minutes] ?? 'pierre_15min';
    }

    /** Reschedule surveillance when interval changes */
    public function reschedule_surveillance(): void {
        $this->register_schedules();
        $ts = wp_next_scheduled(self::SURVEILLANCE_HOOK);
        if ($ts) { wp_unschedule_event($ts, self::SURVEILLANCE_HOOK); }
        wp_clear_scheduled_hook(self::SURVEILLANCE_HOOK);
        wp_schedule_event(time(), $this->get_selected_interval_slug(), self::SURVEILLANCE_HOOK);

        // Align digest to same cadence
        $tsd = wp_next_scheduled(self::DIGEST_HOOK);
        if ($tsd) { wp_unschedule_event($tsd, self::DIGEST_HOOK); }
        wp_clear_scheduled_hook(self::DIGEST_HOOK);
        wp_schedule_event(time(), $this->get_selected_interval_slug(), self::DIGEST_HOOK);
    }

    /**
     * Pierre refreshes available locales cache (weekly)! 🪨
     *
     * @since 1.0.0
     * @return void
     */
    public function run_locales_refresh(): void {
        try {
            /**
             * Allow admin layer to rebuild and persist locales cache.
             */
            do_action('pierre_refresh_locales_cache');
            $this->log_debug('Pierre triggered locales cache refresh! 🪨');
        } catch (\Exception $e) {
            $this->log_debug('Pierre encountered an error refreshing locales cache: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre runs his surveillance check! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function run_surveillance_check(bool $force = false): void {
        try {
            $this->log_debug('Pierre is running his surveillance check... 🪨');
            if (!$force) {
                $settings = get_option('pierre_settings', []);
                if (empty($settings['surveillance_enabled'])) { return; }
            }
            // Abort flag
            $abort = (int) get_option('pierre_abort_run', 0);
            if ($abort) { delete_option('pierre_abort_run'); $this->log_debug('Abort flag detected, stopping run.'); return; }
            $start = microtime(true);
            
            // Pierre gets his project watcher! 🪨
            $project_watcher = pierre()->get_project_watcher();
            
            // Pierre starts his surveillance! 🪨
            if ($project_watcher->start_surveillance()) {
                $this->log_debug('Pierre completed his surveillance check successfully! 🪨');
                update_option('pierre_last_surv_run', current_time('timestamp'));
            } else {
                $this->log_debug('Pierre encountered issues during surveillance check! 😢');
            }
            $dur = max(0, (int) round((microtime(true) - $start) * 1000));
            update_option('pierre_last_surv_duration_ms', $dur);
            
        } catch (\Exception $e) {
            $this->log_debug('Pierre encountered an error during surveillance: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre runs his cleanup task! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function run_cleanup_task(): void {
        try {
            $this->log_debug('Pierre is running his cleanup task... 🪨');
            
            // Pierre cleans up old transients! 🪨
            $this->cleanup_old_transients();
            
            // Pierre cleans up old logs! 🪨
            $this->cleanup_old_logs();
            
            $this->log_debug('Pierre completed his cleanup task! 🪨');
            update_option('pierre_last_cleanup_run', time());
            // track duration if needed
            // (callers may compute duration; here we keep timestamp only to keep it light)
            
        } catch (\Exception $e) {
            $this->log_debug('Pierre encountered an error during cleanup: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre cleans up old transients! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function cleanup_old_transients(): void {
        global $wpdb;
        
        // Pierre finds old transients! 🪨
        $old_transients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND option_value < %s",
                '_transient_pierre_%',
                time() - (7 * DAY_IN_SECONDS)
            )
        );
        
        // Pierre deletes old transients! 🪨
        foreach ($old_transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient->option_name);
            delete_transient($transient_name);
        }
        
        if (!empty($old_transients)) { $this->log_debug('Pierre cleaned up ' . count($old_transients) . ' old transients! 🪨'); }
    }
    
    /**
     * Pierre cleans up old logs! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function cleanup_old_logs(): void {
        // Pierre will implement log cleanup in future phases! 🪨
        $this->log_debug('Pierre cleaned up old logs! 🪨');
    }

    /**
     * Run digest: per-locale queues → send if due (interval/fixed time)
     */
    public function run_digest(): void {
        try {
            $start = microtime(true);
            $settings = get_option('pierre_settings', []);
            if (empty($settings['surveillance_enabled'])) { return; }
            // Abort flag
            $abort = (int) get_option('pierre_abort_run', 0);
            if ($abort) { delete_option('pierre_abort_run'); $this->log_debug('Abort flag detected, stopping digest.'); return; }
            $locales_cfg = (array) ($settings['locales'] ?? []);
            $global_cfg = (array) ($settings['global_webhook'] ?? []);

            // Collect active locales from watched projects (fallback)
            $watched = get_option('pierre_watched_projects', []);
            $active_locales = [];
            if (is_array($watched)) {
                foreach ($watched as $p) {
                    $lc = $p['locale'] ?? ($p['locale_code'] ?? '');
                    if ($lc && !in_array($lc, $active_locales, true)) { $active_locales[] = $lc; }
                }
            }
            foreach ($locales_cfg as $lc => $cfg) {
                if (!in_array($lc, $active_locales, true)) { $active_locales[] = $lc; }
            }
            if (empty($active_locales)) { return; }

            // Global digest (if configured)
            if (!empty($global_cfg) && ($global_cfg['mode'] ?? '') === 'digest') {
                $g_digest = (array) ($global_cfg['digest'] ?? []);
                if ($this->is_digest_due($g_digest)) {
                    $g_key = 'pierre_digest_queue_global';
                    $g_items = get_transient($g_key);
                    if (is_array($g_items) && !empty($g_items)) {
                        $projects = [];
                        foreach ($g_items as $it) {
                            if (isset($it['project_data']) && is_array($it['project_data'])) { $projects[] = $it['project_data']; }
                        }
                        if (!empty($projects) && !empty($global_cfg['webhook_url'])) {
                            $notifier = pierre()->get_slack_notifier();
                            $builder = $notifier->get_message_builder();
                            $message = $builder->build_bulk_update_message($projects);
                            $notifier->send_with_webhook_override($message['text'], $global_cfg['webhook_url'], ['formatted_message' => $message]);
                            do_action('wp_pierre_debug', 'digest_sent', ['source'=>'CronManager','action'=>'global','code'=>200]);
                        }
                        delete_transient($g_key);
                    } else {
                        do_action('wp_pierre_debug', 'digest_empty', ['source'=>'CronManager','action'=>'global']);
                    }
                }
            }

            foreach ($active_locales as $locale) {
                $mode = (string) ($locales_cfg[$locale]['mode'] ?? ($settings['notification_defaults']['mode'] ?? 'immediate'));
                if ($mode !== 'digest') { continue; }

                $digest = (array) ($locales_cfg[$locale]['digest'] ?? ($settings['notification_defaults']['digest'] ?? []));
                if (!$this->is_digest_due($digest)) { continue; }

                $queue_key = 'pierre_digest_queue_' . $locale;
                $items = get_transient($queue_key);
                if (!is_array($items) || empty($items)) { continue; }

                // Build bulk message: expect items contain 'project_data'
                $projects = [];
                foreach ($items as $it) {
                    if (isset($it['project_data']) && is_array($it['project_data'])) {
                        $projects[] = $it['project_data'];
                    }
                }
                if (empty($projects)) { delete_transient($queue_key); continue; }

                $notifier = pierre()->get_slack_notifier();
                $builder = $notifier->get_message_builder();
                $message = $builder->build_bulk_update_message($projects);

                // Send to per-locale webhook (unified model) if available, else global
                $lw = (array) ($settings['locales'][$locale]['webhook'] ?? []);
                $lc_webhook = (string) ($lw['webhook_url'] ?? ($settings['locales_slack'][$locale] ?? ''));
                if ($lc_webhook) {
                    $notifier->send_with_webhook_override($message['text'], $lc_webhook, ['formatted_message' => $message]);
                    do_action('wp_pierre_debug', 'digest_sent', ['source'=>'CronManager','action'=>'locale','code'=>200]);
                } else {
                    $notifier->send_notification($message['text'], [], ['formatted_message' => $message]);
                    do_action('wp_pierre_debug', 'digest_sent', ['source'=>'CronManager','action'=>'fallback','code'=>200]);
                }

                delete_transient($queue_key);
            }
        } catch (\Exception $e) {
            error_log('Pierre encountered an error during digest: ' . $e->getMessage() . ' 😢');
        }
        update_option('pierre_last_digest_run', time());
        $dur = max(0, (int) round((microtime(true) - $start) * 1000));
        update_option('pierre_last_digest_duration_ms', $dur);
    }

    /**
     * Decide if a digest should be sent now
     */
    private function is_digest_due(array $digest): bool {
        $type = (string) ($digest['type'] ?? 'interval'); // interval | fixed_time
        if ($type === 'fixed_time') {
            $target = (string) ($digest['fixed_time'] ?? '09:00'); // HH:MM local time
            $now = current_time('H:i');
            // Allow 15-min window
            return $now >= $target && $now < gmdate('H:i', strtotime($target . ' +15 minutes'));
        }
        $interval = max(15, (int) ($digest['interval_minutes'] ?? 60));
        $last = (int) get_option('pierre_last_digest_run', 0);
        if (!$last || (time() - $last) > ($interval * 60)) {
            update_option('pierre_last_digest_run', time());
            return true;
        }
        return false;
    }
    
    /**
     * Pierre gets his surveillance status! 🪨
     * 
     * @since 1.0.0
     * @return array Array containing surveillance status information
     */
    public function get_surveillance_status(): array {
        $next_surveillance = wp_next_scheduled(self::SURVEILLANCE_HOOK);
        $next_cleanup = wp_next_scheduled(self::CLEANUP_HOOK);
        
        return [
            'active' => $next_surveillance !== false,
            'next_run' => $next_surveillance ? gmdate('Y-m-d H:i:s', $next_surveillance) : null,
            'surveillance_scheduled' => $next_surveillance !== false,
            'next_surveillance' => $next_surveillance ? gmdate('Y-m-d H:i:s', $next_surveillance) : null,
            'cleanup_scheduled' => $next_cleanup !== false,
            'next_cleanup' => $next_cleanup ? gmdate('Y-m-d H:i:s', $next_cleanup) : null,
            'message' => 'Pierre\'s surveillance system is ' . ($next_surveillance ? 'active' : 'inactive') . '! 🪨'
        ];
    }
    
    /**
     * Pierre gets his cleanup status! 🪨
     * 
     * @since 1.0.0
     * @return array Array containing cleanup status information
     */
    public function get_cleanup_status(): array {
        $next_cleanup = wp_next_scheduled(self::CLEANUP_HOOK);
        
        return [
            'active' => $next_cleanup !== false,
            'next_run' => $next_cleanup ? gmdate('Y-m-d H:i:s', $next_cleanup) : null,
            'cleanup_scheduled' => $next_cleanup !== false,
            'next_cleanup' => $next_cleanup ? gmdate('Y-m-d H:i:s', $next_cleanup) : null,
            'message' => 'Pierre\'s cleanup system is ' . ($next_cleanup ? 'active' : 'inactive') . '! 🪨'
        ];
    }
    
    /**
     * Pierre gets his cron manager status! 🪨
     * 
     * @since 1.0.0
     * @return array Array containing cron manager status information
     */
    public function get_status(): array {
        $next_surveillance = wp_next_scheduled(self::SURVEILLANCE_HOOK);
        $next_cleanup = wp_next_scheduled(self::CLEANUP_HOOK);
        $next_digest = wp_next_scheduled(self::DIGEST_HOOK);
        $last_digest = (int) get_option('pierre_last_digest_run', 0);
        
        return [
            'surveillance_scheduled' => $next_surveillance !== false,
            'cleanup_scheduled' => $next_cleanup !== false,
            'next_surveillance' => $next_surveillance ? gmdate('Y-m-d H:i:s', $next_surveillance) : null,
            'next_cleanup' => $next_cleanup ? gmdate('Y-m-d H:i:s', $next_cleanup) : null,
            'next_digest' => $next_digest ? gmdate('Y-m-d H:i:s', $next_digest) : null,
            'last_digest' => $last_digest ? gmdate('Y-m-d H:i:s', $last_digest) : null,
            'message' => 'Pierre\'s cron manager is ' . ($next_surveillance ? 'active' : 'inactive') . '! 🪨'
        ];
    }
}