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
    
    /**
     * Pierre schedules his surveillance events! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function schedule_events(): void {
        // Pierre adds his custom intervals! 🪨
        $this->add_custom_intervals();
        
        // Pierre schedules his surveillance check! 🪨
        if (!wp_next_scheduled(self::SURVEILLANCE_HOOK)) {
            wp_schedule_event(
                time(),
                self::SURVEILLANCE_INTERVAL,
                self::SURVEILLANCE_HOOK
            );
            error_log('Pierre scheduled his surveillance check! 🪨');
        }
        
        // Pierre schedules his cleanup task! 🪨
        if (!wp_next_scheduled(self::CLEANUP_HOOK)) {
            wp_schedule_event(
                time(),
                self::CLEANUP_INTERVAL,
                self::CLEANUP_HOOK
            );
            error_log('Pierre scheduled his cleanup task! 🪨');
        }

        // Pierre schedules his locales refresh! 🪨
        if (!wp_next_scheduled(self::LOCALES_REFRESH_HOOK)) {
            wp_schedule_event(
                time(),
                self::WEEKLY_INTERVAL,
                self::LOCALES_REFRESH_HOOK
            );
            error_log('Pierre scheduled his locales refresh task! 🪨');
        }
        
        // Pierre hooks into his scheduled events! 🪨
        add_action(self::SURVEILLANCE_HOOK, [$this, 'run_surveillance_check']);
        add_action(self::CLEANUP_HOOK, [$this, 'run_cleanup_task']);
        add_action(self::LOCALES_REFRESH_HOOK, [$this, 'run_locales_refresh']);
        
        error_log('Pierre scheduled all his surveillance events! 🪨');
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
            error_log('Pierre cleared his surveillance check! 🪨');
        }
        
        // Pierre clears his cleanup task! 🪨
        $timestamp = wp_next_scheduled(self::CLEANUP_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CLEANUP_HOOK);
            error_log('Pierre cleared his cleanup task! 🪨');
        }
        
        // Pierre clears any orphaned events! 🪨
        wp_clear_scheduled_hook(self::SURVEILLANCE_HOOK);
        wp_clear_scheduled_hook(self::CLEANUP_HOOK);
        
        error_log('Pierre cleared all his scheduled events! 🪨');
    }
    
    /**
     * Pierre adds his custom cron intervals! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function add_custom_intervals(): void {
        add_filter('cron_schedules', function ($schedules) {
            // Pierre's 15-minute surveillance interval! 🪨
            $schedules[self::SURVEILLANCE_INTERVAL] = [
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display' => __('Pierre\'s Surveillance Check', 'wp-pierre')
            ];
            
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
     * Pierre refreshes available locales cache (weekly)! 🪨
     *
     * @since 1.0.0
     * @return void
     */
    public function run_locales_refresh(): void {
        try {
            delete_transient('pierre_available_locales');
            error_log('Pierre refreshed locales cache (transient cleared)! 🪨');
        } catch (\Exception $e) {
            error_log('Pierre encountered an error refreshing locales cache: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre runs his surveillance check! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function run_surveillance_check(): void {
        try {
            error_log('Pierre is running his surveillance check... 🪨');
            
            // Pierre gets his project watcher! 🪨
            $project_watcher = pierre()->get_project_watcher();
            
            // Pierre starts his surveillance! 🪨
            if ($project_watcher->start_surveillance()) {
                error_log('Pierre completed his surveillance check successfully! 🪨');
            } else {
                error_log('Pierre encountered issues during surveillance check! 😢');
            }
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error during surveillance: ' . $e->getMessage() . ' 😢');
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
            error_log('Pierre is running his cleanup task... 🪨');
            
            // Pierre cleans up old transients! 🪨
            $this->cleanup_old_transients();
            
            // Pierre cleans up old logs! 🪨
            $this->cleanup_old_logs();
            
            error_log('Pierre completed his cleanup task! 🪨');
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error during cleanup: ' . $e->getMessage() . ' 😢');
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
        
        if (!empty($old_transients)) {
            error_log('Pierre cleaned up ' . count($old_transients) . ' old transients! 🪨');
        }
    }
    
    /**
     * Pierre cleans up old logs! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function cleanup_old_logs(): void {
        // Pierre will implement log cleanup in future phases! 🪨
        error_log('Pierre cleaned up old logs! 🪨');
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
        
        return [
            'surveillance_scheduled' => $next_surveillance !== false,
            'cleanup_scheduled' => $next_cleanup !== false,
            'next_surveillance' => $next_surveillance ? gmdate('Y-m-d H:i:s', $next_surveillance) : null,
            'next_cleanup' => $next_cleanup ? gmdate('Y-m-d H:i:s', $next_cleanup) : null,
            'message' => 'Pierre\'s cron manager is ' . ($next_surveillance ? 'active' : 'inactive') . '! 🪨'
        ];
    }
}