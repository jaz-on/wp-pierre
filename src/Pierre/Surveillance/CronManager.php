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

use Pierre\Security\Encryption;
use Pierre\Traits\StatusTrait;
use Pierre\Logging\Logger;

/**
 * Cron Manager class - Pierre's scheduling system! 🪨
 *
 * @since 1.0.0
 */
class CronManager {
	use StatusTrait;

    /**
     * Pierre's project watcher - he monitors projects! 🪨
     *
     * @var ProjectWatcher
     */
    private ProjectWatcher $project_watcher;

    /**
     * Pierre's Slack notifier - he sends messages! 🪨
     *
     * @var \Pierre\Notifications\SlackNotifier
     */
    private \Pierre\Notifications\SlackNotifier $slack_notifier;

    /**
     * Pierre's notification service - he manages notifications! 🪨
     *
     * @var \Pierre\Services\NotificationService
     */
    private \Pierre\Services\NotificationService $notification_service;

    /**
     * Constructor with dependencies.
     */
    public function __construct(
        ?ProjectWatcher $project_watcher = null,
        ?\Pierre\Notifications\SlackNotifier $slack_notifier = null,
        ?\Pierre\Services\NotificationService $notification_service = null
    ) {
        // Backward compatibility: allow parameterless construction in tests
        if ( $project_watcher ) {
            $this->project_watcher = $project_watcher;
        } elseif ( function_exists( 'pierre' ) && method_exists( pierre(), 'get_project_watcher' ) ) {
            $this->project_watcher = pierre()->get_project_watcher();
        } else {
            // Test fallback: create instance without invoking heavy constructor
            $ref = new \ReflectionClass( ProjectWatcher::class );
            $this->project_watcher = $ref->newInstanceWithoutConstructor();
        }
        $this->slack_notifier      = $slack_notifier ?? new \Pierre\Notifications\SlackNotifier();
        $this->notification_service = $notification_service ?? new \Pierre\Services\NotificationService(
            $this->slack_notifier,
            new \Pierre\Notifications\MessageBuilder()
        );
    }

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
		if ( ! wp_next_scheduled( self::SURVEILLANCE_HOOK ) ) {
			$interval_slug = $this->get_selected_interval_slug();
			$offset        = wp_rand( 0, 300 ); // jitter first schedule up to 5 min
			wp_schedule_event(
				time() + $offset,
				$interval_slug,
				self::SURVEILLANCE_HOOK
			);
			Logger::static_debug( 'Pierre scheduled his surveillance check! 🪨', ['source' => 'CronManager'] );
		}

		// Pierre schedules his cleanup task! 🪨
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			$offset = wp_rand( 60, 600 );
			wp_schedule_event(
				time() + $offset,
				self::CLEANUP_INTERVAL,
				self::CLEANUP_HOOK
			);
			Logger::static_debug( 'Pierre scheduled his cleanup task! 🪨', ['source' => 'CronManager'] );
		}

		// Pierre schedules his locales refresh! 🪨
		if ( ! wp_next_scheduled( self::LOCALES_REFRESH_HOOK ) ) {
			wp_schedule_event(
				time(),
				self::WEEKLY_INTERVAL,
				self::LOCALES_REFRESH_HOOK
			);
			Logger::static_debug( 'Pierre scheduled his locales refresh task! 🪨', ['source' => 'CronManager'] );
		}

		// Digest runs every 15 minutes to check due locales
		if ( ! wp_next_scheduled( self::DIGEST_HOOK ) ) {
			$interval_slug = $this->get_selected_interval_slug();
			$offset        = wp_rand( 0, 300 );
			wp_schedule_event(
				time() + $offset,
				$interval_slug,
				self::DIGEST_HOOK
			);
			Logger::static_debug( 'Pierre scheduled his digest task! 🪨', ['source' => 'CronManager'] );
		}

		// Pierre hooks into his scheduled events! 🪨
		add_action( self::SURVEILLANCE_HOOK, array( $this, 'run_surveillance_check' ) );
		add_action( self::CLEANUP_HOOK, array( $this, 'run_cleanup_task' ) );
		add_action( self::LOCALES_REFRESH_HOOK, array( $this, 'run_locales_refresh' ) );
		add_action( self::DIGEST_HOOK, array( $this, 'run_digest' ) );

		Logger::static_debug( 'Pierre scheduled all his surveillance events! 🪨', ['source' => 'CronManager'] );
	}

	/**
	 * Pierre clears his scheduled events! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clear_events(): void {
		// Pierre clears his surveillance check! 🪨
		$timestamp = wp_next_scheduled( self::SURVEILLANCE_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::SURVEILLANCE_HOOK );
			Logger::static_debug( 'Pierre cleared his surveillance check! 🪨', ['source' => 'CronManager'] );
		}

		// Pierre clears his cleanup task! 🪨
		$timestamp = wp_next_scheduled( self::CLEANUP_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CLEANUP_HOOK );
			Logger::static_debug( 'Pierre cleared his cleanup task! 🪨', ['source' => 'CronManager'] );
		}

		// Pierre clears any orphaned events! 🪨
		wp_clear_scheduled_hook( self::SURVEILLANCE_HOOK );
		wp_clear_scheduled_hook( self::CLEANUP_HOOK );

		Logger::static_debug( 'Pierre cleared all his scheduled events! 🪨', ['source' => 'CronManager'] );
	}

	/**
	 * Pierre adds his custom cron intervals! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_schedules(): void {
		add_filter(
			'cron_schedules',
			function ( $schedules ) {
				// Pierre custom intervals (5/15/30/60/120)
				$schedules['pierre_5min']   = array(
					'interval' => 5 * MINUTE_IN_SECONDS,
					'display'  => __( 'Pierre 5 minutes', 'wp-pierre' ),
				);
				$schedules['pierre_15min']  = array(
					'interval' => 15 * MINUTE_IN_SECONDS,
					'display'  => __( 'Pierre 15 minutes', 'wp-pierre' ),
				);
				$schedules['pierre_30min']  = array(
					'interval' => 30 * MINUTE_IN_SECONDS,
					'display'  => __( 'Pierre 30 minutes', 'wp-pierre' ),
				);
				$schedules['pierre_60min']  = array(
					'interval' => 60 * MINUTE_IN_SECONDS,
					'display'  => __( 'Pierre 60 minutes', 'wp-pierre' ),
				);
				$schedules['pierre_120min'] = array(
					'interval' => 120 * MINUTE_IN_SECONDS,
					'display'  => __( 'Pierre 120 minutes', 'wp-pierre' ),
				);

				// Pierre's daily cleanup interval! 🪨
				$schedules[ self::CLEANUP_INTERVAL ] = array(
					'interval' => DAY_IN_SECONDS,
					'display'  => __( 'Pierre\'s Daily Cleanup', 'wp-pierre' ),
				);

				// Pierre's weekly interval (for locales refresh)! 🪨
				$schedules[ self::WEEKLY_INTERVAL ] = array(
					'interval' => WEEK_IN_SECONDS,
					'display'  => __( 'Pierre\'s Weekly Tasks', 'wp-pierre' ),
				);

				return $schedules;
			}
		);
	}

	/**
	 * Map settings to interval slug.
	 *
	 * @since 1.0.0
	 * @return string Interval slug (pierre_5min, pierre_15min, pierre_30min, pierre_60min, pierre_120min).
	 */
	private function get_selected_interval_slug(): string {
		$settings = \Pierre\Settings\Settings::all();
		$minutes  = (int) ( $settings['surveillance_interval'] ?? 15 );
		$map      = array(
			5   => 'pierre_5min',
			15  => 'pierre_15min',
			30  => 'pierre_30min',
			60  => 'pierre_60min',
			120 => 'pierre_120min',
		);
		return $map[ $minutes ] ?? 'pierre_15min';
	}

	/** Reschedule surveillance when interval changes */
	public function reschedule_surveillance(): void {
		$this->register_schedules();
		$ts = wp_next_scheduled( self::SURVEILLANCE_HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::SURVEILLANCE_HOOK ); }
		wp_clear_scheduled_hook( self::SURVEILLANCE_HOOK );
		wp_schedule_event( time(), $this->get_selected_interval_slug(), self::SURVEILLANCE_HOOK );

		// Align digest to same cadence
		$tsd = wp_next_scheduled( self::DIGEST_HOOK );
		if ( $tsd ) {
			wp_unschedule_event( $tsd, self::DIGEST_HOOK ); }
		wp_clear_scheduled_hook( self::DIGEST_HOOK );
		wp_schedule_event( time(), $this->get_selected_interval_slug(), self::DIGEST_HOOK );
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
			do_action( 'pierre_refresh_locales_cache' );
			Logger::static_debug( 'Pierre triggered locales cache refresh! 🪨', ['source' => 'CronManager'] );
		} catch ( \Exception $e ) {
			Logger::static_debug( 'Pierre encountered an error refreshing locales cache: ' . $e->getMessage() . ' 😢', ['source' => 'CronManager'] );
		}
	}

	/**
	 * Pierre runs his surveillance check! 🪨
	 *
	 * @since 1.0.0
	 * @param bool $force Force execution even if already running.
	 * @return void
	 */
	public function run_surveillance_check( bool $force = false ): void {
		try {
			$corr = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : (string) time();
			Logger::static_debug( 'Pierre is running his surveillance check... 🪨', ['source' => 'CronManager'] );
			do_action( 'wp_pierre_debug', 'run_surveillance_check:start', array( 'source' => 'CronManager', 'corr_id' => $corr ) );
			if ( ! $force ) {
				$settings = \Pierre\Settings\Settings::all();
				if ( empty( $settings['surveillance_enabled'] ) ) {
					return; }
			}
			// Abort flag
			$abort = (int) get_option( 'pierre_abort_run', 0 );
			if ( $abort ) {
				delete_option( 'pierre_abort_run' );
				Logger::static_debug( 'Abort flag detected, stopping run.', ['source' => 'CronManager'] );
				return; }
			$start = microtime( true );

            // Pierre starts his surveillance! 🪨
            if ( $this->project_watcher->start_surveillance() ) {
				Logger::static_debug( 'Pierre completed his surveillance check successfully! 🪨', ['source' => 'CronManager'] );
				update_option( 'pierre_last_surv_run', current_time( 'timestamp' ) );
			} else {
				Logger::static_debug( 'Pierre encountered issues during surveillance check! 😢', ['source' => 'CronManager'] );
			}
			$dur = max( 0, (int) round( ( microtime( true ) - $start ) * 1000 ) );
			update_option( 'pierre_last_surv_duration_ms', $dur );
			do_action( 'wp_pierre_debug', 'run_surveillance_check:end', array( 'source' => 'CronManager', 'corr_id' => $corr, 'duration_ms' => $dur ) );
		} catch ( \Exception $e ) {
			Logger::static_debug( 'Pierre encountered an error during surveillance: ' . $e->getMessage() . ' 😢', ['source' => 'CronManager'] );
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
			Logger::static_debug( 'Pierre is running his cleanup task... 🪨', ['source' => 'CronManager'] );

			// Pierre cleans up old transients! 🪨
			$this->cleanup_old_transients();

			// Pierre cleans up old surveillance errors! 🪨
			$this->cleanup_old_surveillance_errors();

			Logger::static_debug( 'Pierre completed his cleanup task! 🪨', ['source' => 'CronManager'] );
			update_option( 'pierre_last_cleanup_run', time() );
			// track duration if needed
			// (callers may compute duration; here we keep timestamp only to keep it light)

		} catch ( \Exception $e ) {
			Logger::static_debug( 'Pierre encountered an error during cleanup: ' . $e->getMessage() . ' 😢', ['source' => 'CronManager'] );
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
				time() - ( 7 * DAY_IN_SECONDS )
			)
		);

		// Pierre deletes old transients! 🪨
		foreach ( $old_transients as $transient ) {
			$transient_name = str_replace( '_transient_', '', $transient->option_name );
			delete_transient( $transient_name );
		}

		if ( ! empty( $old_transients ) ) {
			Logger::static_debug( 'Pierre cleaned up ' . count( $old_transients ) . ' old transients! 🪨', ['source' => 'CronManager'] ); }
	}

	/**
	 * Pierre cleans up old surveillance errors! 🪨
	 *
	 * Removes errors older than 24 hours from the surveillance errors transient.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function cleanup_old_surveillance_errors(): void {
		$errors = get_transient( 'pierre_last_surv_errors' );
		if ( ! is_array( $errors ) || empty( $errors ) ) {
			return;
		}

		$now           = time();
		$cleaned       = array();
		$removed_count = 0;

		foreach ( $errors as $key => $error ) {
			$age = $now - ( $error['timestamp'] ?? 0 );
			if ( $age <= 24 * HOUR_IN_SECONDS ) {
				$cleaned[ $key ] = $error;
			} else {
				++$removed_count;
			}
		}

		if ( $removed_count > 0 ) {
			if ( empty( $cleaned ) ) {
				delete_transient( 'pierre_last_surv_errors' );
			} else {
				set_transient( 'pierre_last_surv_errors', $cleaned, 24 * HOUR_IN_SECONDS );
			}
			Logger::static_debug( 'Pierre cleaned up ' . $removed_count . ' old surveillance errors! 🪨', ['source' => 'CronManager'] );
		}
	}

	/**
	 * Run digest: per-locale queues → send if due (interval/fixed time)
	 */
	public function run_digest(): void {
		try {
			$start    = microtime( true );
			$corr     = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : (string) time();
			do_action( 'wp_pierre_debug', 'run_digest:start', array( 'source' => 'CronManager', 'corr_id' => $corr ) );
			$settings = \Pierre\Settings\Settings::all();
			if ( empty( $settings['surveillance_enabled'] ) ) {
				return; }
			// Abort flag
			$abort = (int) get_option( 'pierre_abort_run', 0 );
			if ( $abort ) {
				delete_option( 'pierre_abort_run' );
				Logger::static_debug( 'Abort flag detected, stopping digest.', ['source' => 'CronManager'] );
				return; }
			$locales_cfg = (array) ( $settings['locales'] ?? array() );
			$global_cfg  = (array) ( $settings['global_webhook'] ?? array() );

			// Collect active locales from watched projects (fallback)
			$watched        = get_option( 'pierre_watched_projects', [] );
			$active_locales = array();
			if ( is_array( $watched ) ) {
				foreach ( $watched as $p ) {
					$lc = $p['locale'] ?? ( $p['locale_code'] ?? '' );
					if ( $lc && ! in_array( $lc, $active_locales, true ) ) {
						$active_locales[] = $lc; }
				}
			}
			foreach ( $locales_cfg as $lc => $cfg ) {
				if ( ! in_array( $lc, $active_locales, true ) ) {
					$active_locales[] = $lc; }
			}
			if ( empty( $active_locales ) ) {
				return; }

            // Global digest (if configured)
			if ( ! empty( $global_cfg ) && ! empty( $global_cfg['digest']['enabled'] ?? false ) ) {
				$g_digest = (array) ( $global_cfg['digest'] ?? array() );
				if ( $this->is_digest_due( $g_digest ) ) {
					$g_key   = 'pierre_digest_queue_global';
					$g_items = get_transient( $g_key );
					if ( is_array( $g_items ) && ! empty( $g_items ) ) {
						$projects = array();
						foreach ( $g_items as $it ) {
							if ( isset( $it['project_data'] ) && is_array( $it['project_data'] ) ) {
								$projects[] = $it['project_data']; }
						}
                        $raw_global_webhook = (string) ( $global_cfg['webhook_url'] ?? '' );
                        if ( ! empty( $projects ) && ! empty( $raw_global_webhook ) ) {
                            // Decrypt global webhook URL before using
                            $decrypted = Encryption::decrypt( $raw_global_webhook );
                            $global_webhook_url = ( $decrypted !== false ) ? $decrypted : $raw_global_webhook;
                            if ( ! empty( $global_webhook_url ) ) {
                                $max   = (int) apply_filters( 'pierre_digest_max_projects', 20 );
                                $chunk = (int) apply_filters( 'pierre_digest_chunk_size', 20 );
                                if ( $max > 0 && count( $projects ) > $max ) {
                                    $projects = array_slice( $projects, 0, $max );
                                }
                                foreach ( array_chunk( $projects, max( 1, $chunk ) ) as $proj_chunk ) {
                                    $message = $this->notification_service->build_bulk_update_message( $proj_chunk );
                                    $this->notification_service->send_with_webhook_override( (string) ( $message['text'] ?? '' ), $global_webhook_url, $message );
                                }
                            }
							do_action(
								'wp_pierre_debug',
								'digest_sent',
								array(
									'source' => 'CronManager',
									'action' => 'global',
									'code'   => 200,
								)
							);
						}
						delete_transient( $g_key );
					} else {
						do_action(
							'wp_pierre_debug',
							'digest_empty',
							array(
								'source' => 'CronManager',
								'action' => 'global',
							)
						);
					}
				}
			}

			foreach ( $active_locales as $locale ) {
				$digest = (array) ( $locales_cfg[ $locale ]['digest'] ?? ( $settings['notification_defaults']['digest'] ?? array() ) );
				if ( empty( $digest['enabled'] ) ) {
					continue; }
				if ( ! $this->is_digest_due( $digest ) ) {
					continue; }

				$queue_key = 'pierre_digest_queue_' . $locale;
				$items     = get_transient( $queue_key );
				if ( ! is_array( $items ) || empty( $items ) ) {
					continue; }

                // Build bulk message: expect items contain 'project_data'
                $projects = array();
				foreach ( $items as $it ) {
					if ( isset( $it['project_data'] ) && is_array( $it['project_data'] ) ) {
						$projects[] = $it['project_data'];
					}
				}
                if ( empty( $projects ) ) {
					delete_transient( $queue_key );
					continue; }

                // Limit and chunk projects for digest
                $max   = (int) apply_filters( 'pierre_digest_max_projects', 20 );
                $chunk = (int) apply_filters( 'pierre_digest_chunk_size', 20 );
                if ( $max > 0 && count( $projects ) > $max ) {
                    $projects = array_slice( $projects, 0, $max );
                }
                $chunks = array_chunk( $projects, max( 1, $chunk ) );

                // Send each chunk
                foreach ( $chunks as $proj_chunk ) {
                    $message  = $this->notification_service->build_bulk_update_message( $proj_chunk );
                    $this->send_digest_message( $message, $settings, $locale );
                }

                delete_transient( $queue_key );
			}
		} catch ( \Exception $e ) {
			Logger::static_error( 'Pierre encountered an error during digest: ' . $e->getMessage() . ' 😢', ['source' => 'CronManager'] );
		}
		update_option( 'pierre_last_digest_run', time() );
		$dur = max( 0, (int) round( ( microtime( true ) - $start ) * 1000 ) );
		update_option( 'pierre_last_digest_duration_ms', $dur );
		do_action( 'wp_pierre_debug', 'run_digest:end', array( 'source' => 'CronManager', 'corr_id' => $corr, 'duration_ms' => $dur ) );
	}

	/**
	 * Send digest message for a locale.
	 *
	 * @param array  $message Formatted message array.
	 * @param array  $settings Settings array.
	 * @param string $locale Locale code.
	 * @return void
	 */
	private function send_digest_message( array $message, array $settings, string $locale ): void {
		$locale_cfg = (array) ( $settings['locales'][ $locale ] ?? array() );
		$webhook   = (array) ( $locale_cfg['webhook'] ?? array() );

		// Use locale webhook if configured, otherwise fallback to global.
		$raw_webhook_url = (string) ( $webhook['webhook_url'] ?? '' );
		if ( empty( $raw_webhook_url ) ) {
			$global_cfg  = (array) ( $settings['global_webhook'] ?? array() );
			$raw_webhook_url = (string) ( $global_cfg['webhook_url'] ?? '' );
		}

		// Decrypt webhook URL before using
		if ( ! empty( $raw_webhook_url ) ) {
			$decrypted = Encryption::decrypt( $raw_webhook_url );
			$webhook_url = ( $decrypted !== false ) ? $decrypted : $raw_webhook_url;
			if ( ! empty( $webhook_url ) ) {
				$this->notification_service->send_with_webhook_override(
					(string) ( $message['text'] ?? '' ),
					$webhook_url,
					$message
				);
			}
		}
	}

	/**
	 * Decide if a digest should be sent now.
	 *
	 * @param array $digest Digest configuration.
	 * @return bool Whether digest is due.
	 */
	private function is_digest_due( array $digest ): bool {
		$type = (string) ( $digest['type'] ?? 'interval' ); // interval | fixed_time
		if ( $type === 'fixed_time' ) {
			$target = (string) ( $digest['fixed_time'] ?? '09:00' ); // HH:MM local time
        $now    = wp_date( 'H:i', current_time( 'timestamp' ) );
        // Allow 15-min window
        $limit  = wp_date( 'H:i', strtotime( $target . ' +15 minutes' ) );
        return $now >= $target && $now < $limit;
		}
		$interval = max( 15, (int) ( $digest['interval_minutes'] ?? 60 ) );
		$last     = (int) get_option( 'pierre_last_digest_run', 0 );
		if ( ! $last || ( time() - $last ) > ( $interval * 60 ) ) {
			update_option( 'pierre_last_digest_run', time() );
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
		$next_surveillance = wp_next_scheduled( self::SURVEILLANCE_HOOK );
		$next_cleanup      = wp_next_scheduled( self::CLEANUP_HOOK );

		return array(
			'active'                 => $next_surveillance !== false,
			'next_run'               => $next_surveillance ? gmdate( 'Y-m-d H:i:s', $next_surveillance ) : null,
			'surveillance_scheduled' => $next_surveillance !== false,
			'next_surveillance'      => $next_surveillance ? gmdate( 'Y-m-d H:i:s', $next_surveillance ) : null,
			'cleanup_scheduled'      => $next_cleanup !== false,
			'next_cleanup'           => $next_cleanup ? gmdate( 'Y-m-d H:i:s', $next_cleanup ) : null,
			'message'                => 'Pierre\'s surveillance system is ' . ( $next_surveillance ? 'active' : 'inactive' ) . '! 🪨',
		);
	}

	/**
	 * Pierre gets his cleanup status! 🪨
	 *
	 * @since 1.0.0
	 * @return array Array containing cleanup status information
	 */
	public function get_cleanup_status(): array {
		$next_cleanup = wp_next_scheduled( self::CLEANUP_HOOK );

		return array(
			'active'            => $next_cleanup !== false,
			'next_run'          => $next_cleanup ? gmdate( 'Y-m-d H:i:s', $next_cleanup ) : null,
			'cleanup_scheduled' => $next_cleanup !== false,
			'next_cleanup'      => $next_cleanup ? gmdate( 'Y-m-d H:i:s', $next_cleanup ) : null,
			'message'           => 'Pierre\'s cleanup system is ' . ( $next_cleanup ? 'active' : 'inactive' ) . '! 🪨',
		);
	}

	/**
	 * Pierre gets his cron manager status! 🪨
	 *
	 * @since 1.0.0
	 * @return array Array containing cron manager status information
	 */
	/**
	 * Get status message.
	 *
	 * @since 1.0.0
	 * @return string Status message
	 */
	protected function get_status_message(): string {
		$next_surveillance = wp_next_scheduled( self::SURVEILLANCE_HOOK );
		return 'Pierre\'s cron manager is ' . ( $next_surveillance ? 'active' : 'inactive' ) . '! 🪨';
	}

	/**
	 * Get status details.
	 *
	 * @since 1.0.0
	 * @return array Status details
	 */
	protected function get_status_details(): array {
		$next_surveillance = wp_next_scheduled( self::SURVEILLANCE_HOOK );
		$next_cleanup      = wp_next_scheduled( self::CLEANUP_HOOK );
		$next_digest       = wp_next_scheduled( self::DIGEST_HOOK );
		$last_digest       = (int) get_option( 'pierre_last_digest_run', 0 );

		return array(
			'surveillance_scheduled' => $next_surveillance !== false,
			'cleanup_scheduled'      => $next_cleanup !== false,
			'next_surveillance'      => $next_surveillance ? gmdate( 'Y-m-d H:i:s', $next_surveillance ) : null,
			'next_cleanup'           => $next_cleanup ? gmdate( 'Y-m-d H:i:s', $next_cleanup ) : null,
			'next_digest'            => $next_digest ? gmdate( 'Y-m-d H:i:s', $next_digest ) : null,
			'last_digest'            => $last_digest ? gmdate( 'Y-m-d H:i:s', $last_digest ) : null,
		);
	}
}
