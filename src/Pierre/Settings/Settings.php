<?php
/**
 * Pierre's settings manager - he manages his configuration! 🪨
 *
 * This class provides a lightweight accessor for Pierre's settings
 * with per-request memoization.
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Settings;

use Pierre\Admin\SettingsFields;
use Pierre\Logging\Logger;
use Pierre\Helpers\ErrorHelper;
use Pierre\Helpers\OptionHelper;

use function current_user_can;
use function wp_verify_nonce;
use function wp_unslash;
use function is_wp_error;
use function get_option;
use function update_option;
use function get_transient;
use function set_transient;
use function get_current_user_id;
use function do_action;
use function sanitize_text_field;
use function add_settings_section;
use function add_settings_field;
use function add_settings_error;
use const MINUTE_IN_SECONDS;

/**
 * Settings class - Pierre's configuration accessor! 🪨
 *
 * @since 1.0.0
 */
class Settings {
	/**
	 * Current schema version for settings structure.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const SCHEMA_VERSION = 1;

	/**
	 * Option key for storing schema version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const SCHEMA_VERSION_OPTION = 'pierre_settings_schema_version';

	/**
	 * Deprecated keys mapping (old_key => new_key or null if removed).
	 *
	 * @since 1.0.0
	 * @var array<string, string|null>
	 */
	private static array $deprecated_keys = array(
		// Exemple: 'old_key' => 'new_key', ou null si supprimé
	);

	/**
	 * Cache group for settings.
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'pierre_settings';

	/**
	 * Cache key for all settings.
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'pierre_all_settings';

	/**
	 * Last sanitization error (stored for validate_callback).
	 *
	 * @var \WP_Error|null
	 */
	private static ?\WP_Error $last_sanitize_error = null;

	/**
	 * Flag to prevent multiple deprecated key notices in same request.
	 *
	 * @var bool
	 */
	private static bool $deprecated_notice_shown = false;

	/**
	 * Get all settings.
	 *
	 * @since 1.0.0
	 * @return array All settings
	 */
	public static function all(): array {
		$cached = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
		if ( $cached !== false && is_array( $cached ) ) {
			return $cached;
		}
		
		$settings = get_option( 'pierre_settings', [] );
		
		// Run migration if needed
		$settings = self::migrate( $settings );
		
		// Check for deprecated keys and warn
		self::check_deprecated_keys( $settings );
		
		// Cache settings (no expiration, invalidated manually)
		wp_cache_set( self::CACHE_KEY, $settings, self::CACHE_GROUP, 0 );
		return $settings;
	}

	/**
	 * Get a specific setting value.
	 *
	 * @since 1.0.0
	 * @param string $key Setting key (supports dot notation).
	 * @param mixed  $default Default value if not found.
	 * @return mixed Setting value or default.
	 */
	public static function get( string $key, $default = null ) {
		$settings = self::all();
		if ( strpos( $key, '.' ) === false ) {
			return $settings[ $key ] ?? $default;
		}
		$keys = explode( '.', $key );
		$value = $settings;
		foreach ( $keys as $k ) {
			if ( ! is_array( $value ) || ! isset( $value[ $k ] ) ) {
				return $default;
			}
			$value = $value[ $k ];
		}
		return $value;
	}

	/**
	 * Update settings.
	 *
	 * @since 1.0.0
	 * @param array $settings Settings array to save.
	 * @param array $options Optional. Security options: 'skip_nonce_check' (bool), 'skip_permission_check' (bool), 'skip_rate_limit' (bool), 'nonce' (string), 'nonce_action' (string).
	 * @return bool|WP_Error True on success, false on failure, WP_Error if validation fails.
	 */
	public static function update( array $settings, array $options = array() ) {
		// Security checks (can be bypassed if already verified, e.g., in AJAX handlers)
		$skip_nonce_check = $options['skip_nonce_check'] ?? false;
		$skip_permission_check = $options['skip_permission_check'] ?? false;
		$skip_rate_limit = $options['skip_rate_limit'] ?? false;

		// 1. Nonce verification (if called directly, not via AJAX)
		if ( ! $skip_nonce_check ) {
			$nonce = $options['nonce'] ?? ( isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '' );
			$nonce_action = $options['nonce_action'] ?? 'pierre_settings_update';

			if ( ! empty( $nonce ) ) {
				$nonce_valid = wp_verify_nonce( $nonce, $nonce_action );
				if ( ! $nonce_valid ) {
					$error = new \WP_Error(
						'invalid_nonce',
						ErrorHelper::format_error_message( __( 'Invalid nonce! CSRF attack detected!', 'wp-pierre' ) )
					);
					do_action( 'wp_pierre_debug', 'Settings update failed: invalid nonce', array( 'source' => 'Settings::update' ) );
					return $error;
				}
			}
		}

		// 2. Permission check
		if ( ! $skip_permission_check ) {
			if ( ! current_user_can( 'pierre_manage_settings' ) ) {
				$error = new \WP_Error(
					'insufficient_permissions',
					ErrorHelper::format_error_message( __( 'You don\'t have permission to update settings!', 'wp-pierre' ) )
				);
				do_action( 'wp_pierre_debug', 'Settings update failed: insufficient permissions', array( 'source' => 'Settings::update' ) );
				return $error;
			}
		}

		// 3. Rate limiting
		if ( ! $skip_rate_limit ) {
			$user_id = get_current_user_id();
			$ip_address = self::get_client_ip();
			$rate_limit_key = "pierre_rate_limit_settings_update_{$user_id}_{$ip_address}";
			$rate_limit_window = 15 * MINUTE_IN_SECONDS;
			$max_requests_per_window = 20; // More restrictive for settings updates

			$current_requests = get_transient( $rate_limit_key ) ?: 0;

			if ( $current_requests >= $max_requests_per_window ) {
				$error = new \WP_Error(
					'rate_limit_exceeded',
					ErrorHelper::format_error_message( __( 'Rate limit exceeded! Too many settings updates! Please wait a few minutes.', 'wp-pierre' ) )
				);
				do_action( 'wp_pierre_debug', 'Settings update failed: rate limit exceeded', array( 'source' => 'Settings::update', 'requests' => $current_requests ) );
				return $error;
			}

			// Increment request count
			set_transient( $rate_limit_key, $current_requests + 1, $rate_limit_window );
		}

		// Sanitize settings before saving
		$sanitized = self::sanitize( $settings );
		
		// Check for validation errors from sanitize()
		if ( is_wp_error( $sanitized ) ) {
			// Add field-specific errors to Settings API
			foreach ( $sanitized->get_error_codes() as $code ) {
				$messages = $sanitized->get_error_messages( $code );
				foreach ( $messages as $message ) {
					add_settings_error(
						'pierre_settings_group',
						$code,
						$message,
						'error'
					);
				}
			}

			// Log validation errors
			do_action(
				'wp_pierre_debug',
				'validation failed in sanitize',
				array(
					'scope'  => 'settings',
					'action' => 'update',
					'errors' => $sanitized->get_error_messages(),
					'data'   => $settings,
				)
			);
			
			// Log to WordPress error log
			Logger::static_error(
				sprintf(
					'[WP Pierre] Validation failed in sanitize: %s | Data: %s',
					implode( '; ', $sanitized->get_error_messages() ),
					wp_json_encode( $settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
				)
			);
			
			return $sanitized;
		}

		// Validate business logic
		$validation = self::validate( $sanitized );
		if ( is_wp_error( $validation ) ) {
			// Add field-specific errors to Settings API
			foreach ( $validation->get_error_codes() as $code ) {
				$messages = $validation->get_error_messages( $code );
				foreach ( $messages as $message ) {
					add_settings_error(
						'pierre_settings_group',
						$code,
						$message,
						'error'
					);
				}
			}

			// Log validation errors
			do_action(
				'wp_pierre_debug',
				'validation failed in validate',
				array(
					'scope'  => 'settings',
					'action' => 'update',
					'errors' => $validation->get_error_messages(),
					'data'   => $sanitized,
				)
			);
			
			// Log to WordPress error log
			Logger::static_error(
				sprintf(
					'[WP Pierre] Validation failed in validate: %s | Data: %s',
					implode( '; ', $validation->get_error_messages() ),
					wp_json_encode( $sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
				)
			);
			
			return $validation;
		}

		$old_value = get_option( 'pierre_settings', [] );
		$result = update_option( 'pierre_settings', $sanitized );
		if ( $result ) {
			self::clear_cache();

			/**
			 * Fires after Pierre settings are updated.
			 *
			 * @since 1.0.0
			 * @param array $sanitized New settings value.
			 * @param array $old_value Previous settings value.
			 * @param string $option_name Option name (always 'pierre_settings').
			 */
			do_action( 'update_option_pierre_settings', $sanitized, $old_value, 'pierre_settings' );
		}
		return $result;
	}

	/**
	 * Clear memoized cache (useful after settings update).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function clear_cache(): void {
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Validate settings array for business logic consistency.
	 *
	 * This method performs strict business validation beyond basic sanitization.
	 * It checks for logical consistency and business rules:
	 * - Surveillance: if enabled, interval must be valid
	 * - Webhooks: if enabled, URL must be provided (unless encrypted)
	 * - Notifications: threshold must be coherent with milestones
	 * - Locales: override logic consistency
	 *
	 * @since 1.0.0
	 * @param array $settings Sanitized settings array to validate.
	 * @return true|WP_Error True if valid, WP_Error with validation errors otherwise.
	 */
	public static function validate( array $settings ) {
		// Check for sanitization errors first
		if ( self::$last_sanitize_error !== null && self::$last_sanitize_error->has_errors() ) {
			return self::$last_sanitize_error;
		}
		
		$errors = new \WP_Error();

		// Validate surveillance settings
		if ( ! empty( $settings['surveillance_enabled'] ) ) {
				if ( ! isset( $settings['surveillance_interval'] ) || $settings['surveillance_interval'] < 1 ) {
				$errors->add(
					'surveillance_interval_invalid',
					__( 'L\'intervalle de surveillance doit être d\'au moins 1 minute lorsque la surveillance est activée.', 'wp-pierre' )
				);
			}
			if ( ! isset( $settings['max_projects_per_check'] ) || $settings['max_projects_per_check'] < 1 ) {
				$errors->add(
					'max_projects_per_check_invalid',
					__( 'Le nombre maximum de projets par vérification doit être d\'au moins 1.', 'wp-pierre' )
				);
			}
		}

		// Validate global webhook
		// Note: Some errors like 'global_webhook_url_missing' and 'notification_types_empty' are not linked
		// to specific Settings API fields. These errors are displayed via settings_errors() at the top of
		// the page, not under a specific field. This is normal because the global webhook is managed
		// separately in the template and doesn't have a dedicated Settings API field.
		if ( ! empty( $settings['global_webhook']['enabled'] ) ) {
			if ( empty( $settings['global_webhook']['webhook_url'] ) ) {
				$errors->add(
					'global_webhook_url_missing',
					__( 'L\'URL du webhook global est requise lorsque le webhook global est activé.', 'wp-pierre' )
				);
			}
			if ( empty( $settings['global_webhook']['types'] ) || ! is_array( $settings['global_webhook']['types'] ) || count( $settings['global_webhook']['types'] ) === 0 ) {
				$errors->add(
					'notification_types_empty',
					__( 'Au moins un type de notification doit être sélectionné pour le webhook global.', 'wp-pierre' )
				);
			}
		}

		// Validate notification defaults
		if ( isset( $settings['notification_defaults']['milestones'] ) && is_array( $settings['notification_defaults']['milestones'] ) ) {
			$milestones = $settings['notification_defaults']['milestones'];
			if ( ! empty( $milestones ) ) {
				$duplicates = array_diff_assoc( $milestones, array_unique( $milestones ) );
				if ( ! empty( $duplicates ) ) {
					$errors->add(
						'notification_defaults_milestones_duplicates',
						__( 'Les jalons de notification ne peuvent pas contenir de doublons.', 'wp-pierre' )
					);
				}
			}
		}

		// Validate locales webhooks
		if ( isset( $settings['locales'] ) && is_array( $settings['locales'] ) ) {
			foreach ( $settings['locales'] as $locale_code => $locale_data ) {
				if ( ! empty( $locale_data['webhook']['enabled'] ) ) {
					if ( empty( $locale_data['webhook']['webhook_url'] ) ) {
						$errors->add(
							'locale_webhook_url_missing',
							sprintf(
								/* translators: %s: locale code */
								__( 'L\'URL du webhook est requise pour la locale %s lorsque le webhook est activé.', 'wp-pierre' ),
								$locale_code
							)
						);
					}
				}
			}
		}

		// Validate digest settings
		if ( isset( $settings['global_webhook']['digest']['enabled'] ) && ! empty( $settings['global_webhook']['digest']['enabled'] ) ) {
			if ( isset( $settings['global_webhook']['digest']['type'] ) && $settings['global_webhook']['digest']['type'] === 'interval' ) {
				if ( empty( $settings['global_webhook']['digest']['interval_minutes'] ) || $settings['global_webhook']['digest']['interval_minutes'] < 15 ) {
					$errors->add(
						'global_webhook_digest_interval_invalid',
						__( 'L\'intervalle de digest doit être d\'au moins 15 minutes.', 'pierre' )
					);
				}
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Sanitize settings array before saving.
	 *
	 * This method validates and sanitizes all settings fields before they are saved to the database.
	 * It ensures data integrity and security by:
	 * - Validating data types and ranges
	 * - Sanitizing text fields and URLs
	 * - Preserving encrypted webhook URLs (base64-like strings > 50 chars)
	 * - Maintaining backward compatibility by preserving unknown keys
	 *
	 * Sanitized fields include:
	 * - UI settings: plugin_name, menu_icon
	 * - Surveillance: interval, enabled, auto_start, max_projects_per_check, request_timeout
	 * - Notifications: enabled, types, threshold, defaults (milestones, mode, digest)
	 * - Global webhook: enabled, webhook_url, types, threshold, milestones, mode, digest, scopes (locales, projects)
	 * - Legacy webhook: slack_webhook_url
	 * - Locales: webhook structure (enabled, webhook_url, types, threshold, milestones, immediate_enabled, digest), override flag
	 * - Locales Slack: webhook URLs per locale
	 *
	 * @since 1.0.0
	 * @param array $settings Raw settings array from user input or API.
	 * @return array|\WP_Error Sanitized settings array ready for database storage, or WP_Error on validation failure.
	 *
	 * @example
	 * ```php
	 * $raw = [
	 *   'surveillance_interval' => '15',
	 *   'global_webhook' => [
	 *     'enabled' => true,
	 *     'webhook_url' => 'https://hooks.slack.com/...',
	 *     'scopes' => [
	 *       'locales' => ['fr_FR', 'en_US'],
	 *       'projects' => [['type' => 'plugin', 'slug' => 'my-plugin']]
	 *     ]
	 *   ],
	 *   'locales' => [
	 *     'fr_FR' => [
	 *       'webhook' => ['enabled' => true, 'types' => ['new_strings']],
	 *       'override' => true
	 *     ]
	 *   ]
	 * ];
	 * $sanitized = Settings::sanitize($raw);
	 * if ( is_wp_error( $sanitized ) ) {
	 *     // Handle error
	 * }
	 * ```
	 */
	
	/**
	 * Sanitize webhook URL (handles encrypted URLs).
	 * 
	 * @since 1.0.0
	 * @param string $webhook_url Webhook URL (may be encrypted).
	 * @param \WP_Error $errors Errors object to add validation errors.
	 * @param string $error_code Error code for validation errors.
	 * @param string $error_message Error message for validation errors.
	 * @return string|null Sanitized webhook URL or null if invalid.
	 */
	private static function sanitize_webhook_url( string $webhook_url, \WP_Error $errors, string $error_code, string $error_message ): ?string {
		if ( empty( $webhook_url ) ) {
			return null;
		}
		
		// If it looks like an encrypted string (base64-like), preserve it
		if ( preg_match( '/^[A-Za-z0-9+\/]+=*$/', $webhook_url ) && strlen( $webhook_url ) > 50 ) {
			return $webhook_url;
		}
		
		// Sanitize plain URL
		$sanitized_url = esc_url_raw( $webhook_url );
		if ( $sanitized_url === '' && $webhook_url !== '' ) {
			$errors->add( $error_code, $error_message );
			return null;
		}
		
		return $sanitized_url;
	}
	
	/**
	 * Sanitize digest configuration.
	 * 
	 * @since 1.0.0
	 * @param array $digest Digest configuration array.
	 * @return array Sanitized digest configuration.
	 */
	private static function sanitize_digest_config( array $digest ): array {
		$sanitized = array();
		
		if ( isset( $digest['type'] ) ) {
			$type = sanitize_key( $digest['type'] );
			$sanitized['type'] = in_array( $type, array( 'interval', 'fixed_time' ), true ) ? $type : 'interval';
		}
		
		if ( isset( $digest['interval_minutes'] ) ) {
			$sanitized['interval_minutes'] = max( 15, absint( $digest['interval_minutes'] ) );
		}
		
		if ( isset( $digest['fixed_time'] ) ) {
			$fixed_time = preg_replace( '/[^0-9:]/', '', (string) $digest['fixed_time'] );
			$sanitized['fixed_time'] = preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fixed_time ) ? $fixed_time : '09:00';
		}
		
		if ( isset( $digest['enabled'] ) ) {
			$sanitized['enabled'] = ! empty( $digest['enabled'] );
		}
		
		return $sanitized;
	}
	
	/**
	 * Sanitize milestones array.
	 * 
	 * @since 1.0.0
	 * @param mixed $milestones Milestones (array or comma-separated string).
	 * @return array Sanitized milestones array.
	 */
	private static function sanitize_milestones( mixed $milestones ): array {
		// Handle both array and comma-separated string
		if ( is_string( $milestones ) ) {
			$milestones = array_map( 'trim', explode( ',', $milestones ) );
			$milestones = array_filter( $milestones, function( $m ) { return $m !== ''; } );
		} elseif ( ! is_array( $milestones ) ) {
			$milestones = array();
		}
		
		$milestones = array_map( 'absint', $milestones );
		$milestones = array_filter( $milestones, function( $m ) { return $m >= 0 && $m <= 100; } );
		sort( $milestones );
		
		return array_values( $milestones );
	}
	
	/**
	 * Sanitize notification types array.
	 * 
	 * @since 1.0.0
	 * @param array $types Notification types array.
	 * @return array Sanitized notification types array.
	 */
	private static function sanitize_notification_types( array $types ): array {
		$allowed_types = array( 'new_strings', 'completion_update', 'needs_attention', 'milestone' );
		return array_intersect( $types, $allowed_types );
	}

	public static function sanitize( array $settings ): array|\WP_Error {
		$errors = new \WP_Error();
		$sanitized = array();

		// UI settings
		if ( isset( $settings['ui'] ) && is_array( $settings['ui'] ) ) {
			$sanitized['ui'] = array();
			if ( isset( $settings['ui']['plugin_name'] ) ) {
				$sanitized['ui']['plugin_name'] = sanitize_text_field( $settings['ui']['plugin_name'] );
			}
			if ( isset( $settings['ui']['menu_icon'] ) ) {
				$menu_icon = sanitize_key( $settings['ui']['menu_icon'] );
				$sanitized['ui']['menu_icon'] = in_array( $menu_icon, array( 'emoji', 'dashicons' ), true ) ? $menu_icon : 'emoji';
			}
		}

		// Surveillance settings
		if ( isset( $settings['surveillance_interval'] ) ) {
			$sanitized['surveillance_interval'] = max( 1, absint( $settings['surveillance_interval'] ) );
		}
		if ( isset( $settings['surveillance_enabled'] ) ) {
			$sanitized['surveillance_enabled'] = ! empty( $settings['surveillance_enabled'] );
		}
		if ( isset( $settings['auto_start_surveillance'] ) ) {
			$sanitized['auto_start_surveillance'] = ! empty( $settings['auto_start_surveillance'] );
		}
		if ( isset( $settings['max_projects_per_check'] ) ) {
			$max_projects = absint( $settings['max_projects_per_check'] );
			if ( $max_projects < 1 ) {
				$errors->add(
					'max_projects_per_check_invalid',
					__( 'Le nombre maximum de projets par vérification doit être d\'au moins 1.', 'wp-pierre' )
				);
			} elseif ( $max_projects > 100 ) {
				$errors->add(
					'max_projects_per_check_invalid',
					__( 'Le nombre maximum de projets par vérification ne peut pas dépasser 100.', 'wp-pierre' )
				);
			} else {
				$sanitized['max_projects_per_check'] = $max_projects;
			}
		}
		if ( isset( $settings['request_timeout'] ) ) {
			$timeout = absint( $settings['request_timeout'] );
			if ( $timeout < 3 ) {
				$errors->add(
					'request_timeout_invalid',
					__( 'Le délai d\'attente des requêtes doit être d\'au moins 3 secondes.', 'wp-pierre' )
				);
			} elseif ( $timeout > 300 ) {
				$errors->add(
					'request_timeout_invalid',
					__( 'Le délai d\'attente des requêtes ne peut pas dépasser 300 secondes (5 minutes).', 'wp-pierre' )
				);
			} else {
				$sanitized['request_timeout'] = $timeout;
			}
		}

		// Notification settings
		if ( isset( $settings['notifications_enabled'] ) ) {
			$sanitized['notifications_enabled'] = ! empty( $settings['notifications_enabled'] );
		}
		if ( isset( $settings['notification_types'] ) && is_array( $settings['notification_types'] ) ) {
			$sanitized['notification_types'] = self::sanitize_notification_types( $settings['notification_types'] );
		}
		if ( isset( $settings['notification_threshold'] ) ) {
			$threshold = absint( $settings['notification_threshold'] );
			if ( $threshold < 0 || $threshold > 100 ) {
				$errors->add(
					'notification_threshold_invalid',
					__( 'Le seuil de notification doit être entre 0 et 100.', 'wp-pierre' )
				);
			} else {
				$sanitized['notification_threshold'] = $threshold;
			}
		}

		// Notification defaults
		if ( isset( $settings['notification_defaults'] ) && is_array( $settings['notification_defaults'] ) ) {
			$sanitized['notification_defaults'] = array();
			if ( isset( $settings['notification_defaults']['new_strings_threshold'] ) ) {
				$sanitized['notification_defaults']['new_strings_threshold'] = max( 0, absint( $settings['notification_defaults']['new_strings_threshold'] ) );
			}
			if ( isset( $settings['notification_defaults']['milestones'] ) ) {
				$sanitized['notification_defaults']['milestones'] = self::sanitize_milestones( $settings['notification_defaults']['milestones'] );
			}
			if ( isset( $settings['notification_defaults']['mode'] ) ) {
				$mode = sanitize_key( $settings['notification_defaults']['mode'] );
				$sanitized['notification_defaults']['mode'] = in_array( $mode, array( 'immediate', 'digest' ), true ) ? $mode : 'immediate';
			}
			if ( isset( $settings['notification_defaults']['digest'] ) && is_array( $settings['notification_defaults']['digest'] ) ) {
				$sanitized['notification_defaults']['digest'] = self::sanitize_digest_config( $settings['notification_defaults']['digest'] );
			}
		}

		// Global webhook (webhook_url may be encrypted, so we preserve it as-is but validate structure)
		if ( isset( $settings['global_webhook'] ) && is_array( $settings['global_webhook'] ) ) {
			$sanitized['global_webhook'] = array();
			if ( isset( $settings['global_webhook']['enabled'] ) ) {
				$sanitized['global_webhook']['enabled'] = ! empty( $settings['global_webhook']['enabled'] );
			}
			if ( isset( $settings['global_webhook']['webhook_url'] ) && is_string( $settings['global_webhook']['webhook_url'] ) ) {
				$sanitized_url = self::sanitize_webhook_url(
					$settings['global_webhook']['webhook_url'],
					$errors,
					'invalid_global_webhook_url',
					__( 'L\'URL du webhook global n\'est pas valide.', 'wp-pierre' )
				);
				if ( $sanitized_url !== null ) {
					$sanitized['global_webhook']['webhook_url'] = $sanitized_url;
				}
			}
			if ( isset( $settings['global_webhook']['types'] ) && is_array( $settings['global_webhook']['types'] ) ) {
				$sanitized['global_webhook']['types'] = self::sanitize_notification_types( $settings['global_webhook']['types'] );
			}
			if ( isset( $settings['global_webhook']['threshold'] ) ) {
				$threshold = absint( $settings['global_webhook']['threshold'] );
				if ( $threshold < 0 || $threshold > 100 ) {
					$errors->add(
						'global_webhook_threshold_invalid',
						__( 'Le seuil du webhook global doit être entre 0 et 100.', 'wp-pierre' )
					);
				} else {
					$sanitized['global_webhook']['threshold'] = $threshold;
				}
			}
			if ( isset( $settings['global_webhook']['milestones'] ) && is_array( $settings['global_webhook']['milestones'] ) ) {
				$milestones = array_map( 'absint', $settings['global_webhook']['milestones'] );
				$milestones = array_filter( $milestones, function( $m ) { return $m >= 0 && $m <= 100; } );
				sort( $milestones );
				$sanitized['global_webhook']['milestones'] = array_values( $milestones );
			}
			if ( isset( $settings['global_webhook']['mode'] ) ) {
				$mode = sanitize_key( $settings['global_webhook']['mode'] );
				$sanitized['global_webhook']['mode'] = in_array( $mode, array( 'immediate', 'digest' ), true ) ? $mode : 'immediate';
			}
			if ( isset( $settings['global_webhook']['digest'] ) && is_array( $settings['global_webhook']['digest'] ) ) {
				$sanitized['global_webhook']['digest'] = array();
				if ( isset( $settings['global_webhook']['digest']['type'] ) ) {
					$type = sanitize_key( $settings['global_webhook']['digest']['type'] );
					$sanitized['global_webhook']['digest']['type'] = in_array( $type, array( 'interval', 'fixed_time' ), true ) ? $type : 'interval';
				}
				if ( isset( $settings['global_webhook']['digest']['interval_minutes'] ) ) {
					$sanitized['global_webhook']['digest']['interval_minutes'] = max( 15, absint( $settings['global_webhook']['digest']['interval_minutes'] ) );
				}
				if ( isset( $settings['global_webhook']['digest']['fixed_time'] ) ) {
					$fixed_time = preg_replace( '/[^0-9:]/', '', (string) $settings['global_webhook']['digest']['fixed_time'] );
					$sanitized['global_webhook']['digest']['fixed_time'] = preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fixed_time ) ? $fixed_time : '09:00';
				}
			}
			// Scopes (locales and projects)
			if ( isset( $settings['global_webhook']['scopes'] ) && is_array( $settings['global_webhook']['scopes'] ) ) {
				$sanitized['global_webhook']['scopes'] = array();
				// Sanitize locales array
				if ( isset( $settings['global_webhook']['scopes']['locales'] ) && is_array( $settings['global_webhook']['scopes']['locales'] ) ) {
					$sanitized['global_webhook']['scopes']['locales'] = array_values( array_map( 'sanitize_key', $settings['global_webhook']['scopes']['locales'] ) );
				}
				// Sanitize projects array (each project must have type and slug)
				if ( isset( $settings['global_webhook']['scopes']['projects'] ) && is_array( $settings['global_webhook']['scopes']['projects'] ) ) {
					$sanitized['global_webhook']['scopes']['projects'] = array();
					foreach ( $settings['global_webhook']['scopes']['projects'] as $project ) {
						if ( is_array( $project ) && isset( $project['type'] ) && isset( $project['slug'] ) ) {
							$sanitized['global_webhook']['scopes']['projects'][] = array(
								'type' => sanitize_key( $project['type'] ),
								'slug' => sanitize_key( $project['slug'] ),
							);
						}
					}
				}
			}
		}

		// Legacy webhook URL (may be encrypted)
		if ( isset( $settings['slack_webhook_url'] ) ) {
			$webhook_url = $settings['slack_webhook_url'];
			if ( is_string( $webhook_url ) && $webhook_url !== '' ) {
				// If it looks like an encrypted string, preserve it
				if ( preg_match( '/^[A-Za-z0-9+\/]+=*$/', $webhook_url ) && strlen( $webhook_url ) > 50 ) {
					$sanitized['slack_webhook_url'] = $webhook_url;
				} else {
					$sanitized_url = esc_url_raw( $webhook_url );
					// Validate URL format if not encrypted
					if ( $sanitized_url === '' && $webhook_url !== '' ) {
						$errors->add(
							'invalid_slack_webhook_url',
							__( 'L\'URL du webhook Slack n\'est pas valide.', 'wp-pierre' )
						);
					} else {
						$sanitized['slack_webhook_url'] = $sanitized_url;
					}
				}
			}
		}

		// Locales (complex nested structure with webhook and override)
		if ( isset( $settings['locales'] ) && is_array( $settings['locales'] ) ) {
			$sanitized['locales'] = array();
			foreach ( $settings['locales'] as $locale_code => $locale_data ) {
				if ( ! is_string( $locale_code ) || ! is_array( $locale_data ) ) {
					continue;
				}
				$sanitized_locale = array();
				$locale_code = OptionHelper::sanitize_locale_code( $locale_code );

				// Sanitize webhook structure
				if ( isset( $locale_data['webhook'] ) && is_array( $locale_data['webhook'] ) ) {
					$sanitized_locale['webhook'] = array();
					$webhook = $locale_data['webhook'];

					if ( isset( $webhook['enabled'] ) ) {
						$sanitized_locale['webhook']['enabled'] = ! empty( $webhook['enabled'] );
					}
					if ( isset( $webhook['webhook_url'] ) && is_string( $webhook['webhook_url'] ) ) {
						$sanitized_url = self::sanitize_webhook_url(
							$webhook['webhook_url'],
							$errors,
							'invalid_locale_webhook_url',
							sprintf(
								/* translators: %s: locale code */
								__( 'L\'URL du webhook pour la locale %s n\'est pas valide.', 'wp-pierre' ),
								$locale_code
							)
						);
						if ( $sanitized_url !== null ) {
							$sanitized_locale['webhook']['webhook_url'] = $sanitized_url;
						}
					}
					if ( isset( $webhook['types'] ) && is_array( $webhook['types'] ) ) {
						$allowed_types = array( 'new_strings', 'completion_update', 'needs_attention', 'milestone' );
						$sanitized_locale['webhook']['types'] = array_intersect( $webhook['types'], $allowed_types );
					}
					if ( isset( $webhook['threshold'] ) ) {
						$threshold = absint( $webhook['threshold'] );
					if ( $threshold < 0 || $threshold > 100 ) {
						$errors->add(
							'locale_webhook_threshold_invalid',
							sprintf(
								/* translators: %s: locale code */
								__( 'Le seuil du webhook pour la locale %s doit être entre 0 et 100.', 'wp-pierre' ),
								$locale_code
							)
						);
					} else {
						$sanitized_locale['webhook']['threshold'] = $threshold;
					}
					}
					if ( isset( $webhook['milestones'] ) ) {
						$sanitized_locale['webhook']['milestones'] = self::sanitize_milestones( $webhook['milestones'] );
					}
					if ( isset( $webhook['immediate_enabled'] ) ) {
						$sanitized_locale['webhook']['immediate_enabled'] = ! empty( $webhook['immediate_enabled'] );
					}
					if ( isset( $webhook['digest'] ) && is_array( $webhook['digest'] ) ) {
						$sanitized_locale['webhook']['digest'] = self::sanitize_digest_config( $webhook['digest'] );
					}
				}

				// Sanitize override flag
				if ( isset( $locale_data['override'] ) ) {
					$sanitized_locale['override'] = ! empty( $locale_data['override'] );
				}

				// Preserve other unknown keys for backward compatibility
				foreach ( $locale_data as $key => $value ) {
					if ( ! in_array( $key, array( 'webhook', 'override' ), true ) ) {
						$sanitized_locale[ $key ] = $value;
					}
				}

				$sanitized['locales'][ $locale_code ] = $sanitized_locale;
			}
		}

		// Locales Slack (webhook URLs may be encrypted)
		if ( isset( $settings['locales_slack'] ) && is_array( $settings['locales_slack'] ) ) {
			$sanitized['locales_slack'] = array();
			foreach ( $settings['locales_slack'] as $locale_code => $webhook_url ) {
				if ( ! is_string( $locale_code ) ) {
					continue;
				}
				$locale_code = OptionHelper::sanitize_locale_code( $locale_code );
				if ( is_string( $webhook_url ) && $webhook_url !== '' ) {
					// If it looks like an encrypted string, preserve it
					if ( preg_match( '/^[A-Za-z0-9+\/]+=*$/', $webhook_url ) && strlen( $webhook_url ) > 50 ) {
						$sanitized['locales_slack'][ $locale_code ] = $webhook_url;
					} else {
						$sanitized['locales_slack'][ $locale_code ] = esc_url_raw( $webhook_url );
					}
				}
			}
		}

		// Merge any other settings that might exist (preserve unknown keys)
		$known_keys = array(
			'ui', 'surveillance_interval', 'surveillance_enabled', 'auto_start_surveillance',
			'max_projects_per_check', 'request_timeout', 'notifications_enabled',
			'notification_types', 'notification_threshold', 'notification_defaults',
			'global_webhook', 'slack_webhook_url', 'locales', 'locales_slack'
		);
		foreach ( $settings as $key => $value ) {
			if ( ! in_array( $key, $known_keys, true ) ) {
				// Preserve unknown keys (for backward compatibility)
				$sanitized[ $key ] = $value;
			}
		}

		// Check for errors before applying filter
		if ( $errors->has_errors() ) {
			return $errors;
		}

		/**
		 * Filter sanitized settings before they are saved.
		 *
		 * Allows external code to modify sanitized settings before they are stored.
		 * This hook is automatically registered by WordPress when using sanitize_callback
		 * in register_setting().
		 *
		 * @since 1.0.0
		 * @param array $sanitized Sanitized settings array.
		 * @param array $settings  Original raw settings array.
		 * @return array Filtered sanitized settings.
		 */
		$sanitized = apply_filters( 'sanitize_option_pierre_settings', $sanitized, $settings );

		return $sanitized;
	}

	/**
	 * Migrate settings from older schema versions to current version.
	 *
	 * This method detects and converts old settings structures to the current format.
	 * It runs automatically when settings are loaded if the schema version is outdated.
	 *
	 * @since 1.0.0
	 * @param array $settings Current settings array.
	 * @return array Migrated settings array.
	 */
	public static function migrate( array $settings ): array {
		$current_version = (int) get_option( self::SCHEMA_VERSION_OPTION, 0 );

		// If already at current version, no migration needed
		if ( $current_version >= self::SCHEMA_VERSION ) {
			return $settings;
		}

		$migrated = $settings;
		$migration_performed = false;

		// Migration from version 0 to 1 (initial migration)
		if ( $current_version < 1 ) {
			$migrated = self::migrate_to_v1( $migrated );
			$migration_performed = true;
		}

		// Add future migrations here:
		// if ( $current_version < 2 ) {
		//     $migrated = self::migrate_to_v2( $migrated );
		//     $migration_performed = true;
		// }

		// Save migrated settings and update schema version
		if ( $migration_performed ) {
			update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION );
			update_option( 'pierre_settings', $migrated );
			
			// Clear cache to force reload
			self::clear_cache();
			
			// Log migration for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				Logger::static_debug( sprintf(
					'[Pierre] Settings migrated from version %d to %d',
					$current_version,
					self::SCHEMA_VERSION
				), ['source' => 'Settings'] );
			}
		}

		return $migrated;
	}

	/**
	 * Migrate settings to version 1 schema.
	 *
	 * @since 1.0.0
	 * @param array $settings Settings array to migrate.
	 * @return array Migrated settings array.
	 */
	private static function migrate_to_v1( array $settings ): array {
		$migrated = $settings;

		// Example migrations (adjust based on actual old structures):
		
		// Convert old flat structure to nested structure if needed
		// if ( isset( $settings['old_flat_key'] ) && ! isset( $settings['new']['nested']['key'] ) ) {
		//     $migrated['new']['nested']['key'] = $settings['old_flat_key'];
		//     unset( $migrated['old_flat_key'] );
		// }

		// Convert old webhook structure if needed
		// if ( isset( $settings['old_webhook'] ) && ! isset( $settings['global_webhook'] ) ) {
		//     $migrated['global_webhook'] = array(
		//         'enabled' => ! empty( $settings['old_webhook'] ),
		//         'webhook_url' => $settings['old_webhook'],
		//     );
		//     unset( $migrated['old_webhook'] );
		// }

		// Ensure schema version is set in settings (for tracking)
		if ( ! isset( $migrated['_schema_version'] ) ) {
			$migrated['_schema_version'] = 1;
		}

		return $migrated;
	}

	/**
	 * Check for deprecated keys and trigger warnings.
	 *
	 * @since 1.0.0
	 * @param array $settings Settings array to check.
	 * @return void
	 */
	private static function check_deprecated_keys( array $settings ): void {
		if ( empty( self::$deprecated_keys ) ) {
			return;
		}

		$found_deprecated = array();

		foreach ( self::$deprecated_keys as $old_key => $new_key ) {
			if ( isset( $settings[ $old_key ] ) ) {
				$found_deprecated[] = array(
					'old' => $old_key,
					'new' => $new_key,
				);
			}
		}

		if ( ! empty( $found_deprecated ) ) {
			// Trigger WordPress admin notice if in admin (only once, not in loop)
			if ( is_admin() && current_user_can( 'manage_options' ) ) {
				// Build messages list
				$messages = array();
				foreach ( $found_deprecated as $deprecated ) {
					$message = sprintf(
						/* translators: 1: Old key name, 2: New key name or 'removed' */
						__( 'La clé de configuration "%1$s" est dépréciée. %2$s', 'wp-pierre' ),
						$deprecated['old'],
						$deprecated['new'] 
							? sprintf( __( 'Utilisez "%s" à la place.', 'wp-pierre' ), $deprecated['new'] )
							: __( 'Cette clé a été supprimée et ne sera plus supportée dans une future version.', 'wp-pierre' )
					);
					$messages[] = esc_html( $message );
				}

				// Prevent multiple notices in same request
				if ( ! self::$deprecated_notice_shown ) {
					add_action( 'admin_notices', function() use ( $messages ) {
						printf(
							'<div class="notice notice-warning is-dismissible"><p><strong>%s:</strong><br>%s</p></div>',
							esc_html__( 'Pierre - Clés dépréciées détectées', 'wp-pierre' ),
							implode( '<br>', $messages )
						);
					}, 10 );
					self::$deprecated_notice_shown = true;
				}
			}

			// Log to error log if debug is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				foreach ( $found_deprecated as $deprecated ) {
					$message = sprintf(
						/* translators: 1: Old key name, 2: New key name or 'removed' */
						__( 'La clé de configuration "%1$s" est dépréciée. %2$s', 'wp-pierre' ),
						$deprecated['old'],
						$deprecated['new'] 
							? sprintf( __( 'Utilisez "%s" à la place.', 'wp-pierre' ), $deprecated['new'] )
							: __( 'Cette clé a été supprimée et ne sera plus supportée dans une future version.', 'wp-pierre' )
					);
					Logger::static_warning( sprintf( '[Pierre] Deprecated key warning: %s', $message ), ['source' => 'Settings'] );
				}
			}
		}
	}

	/**
	 * Wrapper for sanitize_callback to handle WP_Error.
	 * 
	 * WordPress sanitize_callback should return sanitized value, not WP_Error.
	 * This wrapper stores the error for validate_callback to handle.
	 *
	 * @since 1.0.0
	 * @param array $settings Raw settings array.
	 * @return array Sanitized settings array (or original if WP_Error occurred).
	 */
	public static function sanitize_callback( array $settings ): array {
		// Reset last error
		self::$last_sanitize_error = null;
		
		$result = self::sanitize( $settings );
		// If sanitize() returned WP_Error, store it for validate_callback
		if ( is_wp_error( $result ) ) {
			self::$last_sanitize_error = $result;
			return $settings;
		}
		return $result;
	}

	/**
	 * Register the setting with WordPress Settings API.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register(): void {
		register_setting(
			'pierre_settings_group',
			'pierre_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_callback' ),
				'validate_callback' => array( self::class, 'validate' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		// Register operational options
		self::register_operational_options();
	}

	/**
	 * Register settings sections with WordPress Settings API.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_sections(): void {
		// Section: Surveillance
		add_settings_section(
			'pierre_section_surveillance',
			__( 'Surveillance Settings', 'wp-pierre' ),
			array( self::class, 'render_section_surveillance' ),
			'pierre-settings'
		);

		// Section: Notifications
		add_settings_section(
			'pierre_section_notifications',
			__( 'Notification Settings', 'wp-pierre' ),
			array( self::class, 'render_section_notifications' ),
			'pierre-settings'
		);

		// Section: UI Admin
		add_settings_section(
			'pierre_section_ui',
			__( 'Admin UI Settings', 'wp-pierre' ),
			array( self::class, 'render_section_ui' ),
			'pierre-settings'
		);
	}

	/**
	 * Register settings fields with WordPress Settings API.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_fields(): void {
		// Note: Field keys use dot notation for nested settings (e.g., "notification_defaults.milestones")
		// but field IDs use underscores for HTML compatibility (e.g., "notification_defaults_milestones").
		// The get_field_errors() method in SettingsFields handles the conversion automatically.
		
		// Surveillance fields
		add_settings_field(
			'surveillance_enabled',
			__( 'Enable Surveillance', 'wp-pierre' ),
			array( SettingsFields::class, 'render_checkbox' ),
			'pierre-settings',
			'pierre_section_surveillance',
			array(
				'key'          => 'surveillance_enabled',
				'default'      => true,
				'label'        => __( 'Enable Surveillance', 'wp-pierre' ),
				'help'         => __( 'Pause/resume scheduled checks globally. You can still run "Force surveillance now" from System Status when paused.', 'wp-pierre' ),
				'show_status'  => true,
				'wrapper_class' => 'pierre-row pierre-mb-8',
			)
		);

		add_settings_field(
			'auto_start_surveillance',
			__( 'Auto Start Surveillance', 'wp-pierre' ),
			array( SettingsFields::class, 'render_checkbox' ),
			'pierre-settings',
			'pierre_section_surveillance',
			array(
				'key'           => 'auto_start_surveillance',
				'default'       => false,
				'label'         => __( 'Run a first check right after activation', 'wp-pierre' ),
				'help'          => __( 'Useful to validate the setup right after install. If no locales/projects exist yet, the check exits quickly. Scheduled checks still apply.', 'wp-pierre' ),
				'wrapper_class' => 'pierre-mb-8',
			)
		);

		// Add scheduling subtitle before interval field
		add_settings_field(
			'surveillance_scheduling_title',
			'',
			function() {
				echo '<h3 class="pierre-mt-16">' . esc_html__( 'Scheduling', 'wp-pierre' ) . '</h3>';
			},
			'pierre-settings',
			'pierre_section_surveillance',
			array()
		);

		add_settings_field(
			'surveillance_interval',
			__( 'Surveillance Interval', 'wp-pierre' ),
			array( SettingsFields::class, 'render_select' ),
			'pierre-settings',
			'pierre_section_surveillance',
			array(
				'key'     => 'surveillance_interval',
				'default' => 15,
				'id'      => 'surveillance_interval',
				'label'   => __( 'Surveillance interval (minutes):', 'wp-pierre' ),
				'help'    => __( 'How often Pierre checks for changes. Shorter = faster detection but more load. Recommended: 15–60 min for production; 5–15 min for testing.', 'wp-pierre' ),
				'options' => array(
					'5'   => __( '5 minutes', 'wp-pierre' ),
					'15'  => __( '15 minutes', 'wp-pierre' ),
					'30'  => __( '30 minutes', 'wp-pierre' ),
					'60'  => __( '1 hour', 'wp-pierre' ),
					'120' => __( '2 hours', 'wp-pierre' ),
				),
			)
		);

		add_settings_field(
			'request_timeout',
			__( 'HTTP Request Timeout', 'wp-pierre' ),
			array( SettingsFields::class, 'render_number' ),
			'pierre-settings',
			'pierre_section_surveillance',
			array(
				'key'     => 'request_timeout',
				'default' => 30,
				'id'      => 'request_timeout',
				'label'   => __( 'HTTP Request Timeout (seconds):', 'wp-pierre' ),
				'help'    => __( 'Timeout for outbound HTTP calls to WP.org APIs (default 30s).', 'wp-pierre' ),
				'min'     => 3,
				'max'     => 120,
			)
		);

		add_settings_field(
			'max_projects_per_check',
			__( 'Maximum Projects per Check', 'wp-pierre' ),
			array( SettingsFields::class, 'render_number' ),
			'pierre-settings',
			'pierre_section_surveillance',
			array(
				'key'     => 'max_projects_per_check',
				'default' => 50,
				'id'      => 'max_projects_per_check',
				'label'   => __( 'Maximum Projects per Check:', 'wp-pierre' ),
				'help'    => __( 'Caps the number of projects processed per run to spread load. Higher = faster catch‑up but heavier bursts. Recommended: 20–100 depending on server size (default 50).', 'wp-pierre' ),
				'min'     => 1,
			)
		);

		// Notification fields
		add_settings_field(
			'notification_defaults_new_strings_threshold',
			__( 'New Strings Threshold', 'wp-pierre' ),
			array( SettingsFields::class, 'render_number' ),
			'pierre-settings',
			'pierre_section_notifications',
			array(
				'key'     => 'notification_defaults.new_strings_threshold',
				'default' => 20,
				'id'      => 'notification_defaults_new_strings_threshold',
				'label'   => __( 'New strings threshold (default):', 'wp-pierre' ),
				'min'     => 0,
			)
		);

		add_settings_field(
			'notification_defaults_milestones',
			__( 'Milestones', 'wp-pierre' ),
			array( SettingsFields::class, 'render_milestones' ),
			'pierre-settings',
			'pierre_section_notifications',
			array(
				'key'     => 'notification_defaults.milestones',
				'default' => array( 50, 80, 100 ),
				'id'      => 'notification_defaults_milestones',
				'label'   => __( 'Milestones (comma-separated):', 'wp-pierre' ),
				'name'    => 'notification_defaults[milestones]',
			)
		);

		add_settings_field(
			'notification_defaults_mode',
			__( 'Notification Mode', 'wp-pierre' ),
			array( SettingsFields::class, 'render_select' ),
			'pierre-settings',
			'pierre_section_notifications',
			array(
				'key'     => 'notification_defaults.mode',
				'default' => 'immediate',
				'id'      => 'notification_defaults_mode',
				'label'   => __( 'Mode:', 'wp-pierre' ),
				'help'    => __( 'Immediate: send notifications as events occur. Digest: group and send at intervals or a fixed time.', 'wp-pierre' ),
				'name'    => 'notification_defaults[mode]',
				'options' => array(
					'immediate' => __( 'immediate', 'wp-pierre' ),
					'digest'    => __( 'digest', 'wp-pierre' ),
				),
			)
		);

		add_settings_field(
			'notification_defaults_digest_type',
			__( 'Digest Type', 'wp-pierre' ),
			array( SettingsFields::class, 'render_select' ),
			'pierre-settings',
			'pierre_section_notifications',
			array(
				'key'     => 'notification_defaults.digest.type',
				'default' => 'interval',
				'id'      => 'notification_defaults_digest_type',
				'label'   => __( 'Digest Type:', 'wp-pierre' ),
				'help'    => __( 'Interval: every N minutes. Fixed time: once per day at HH:MM (site timezone).', 'wp-pierre' ),
				'name'    => 'notification_defaults[digest][type]',
				'options' => array(
					'interval'   => __( 'interval', 'wp-pierre' ),
					'fixed_time' => __( 'fixed_time', 'wp-pierre' ),
				),
			)
		);

		add_settings_field(
			'notification_defaults_digest_interval_minutes',
			__( 'Digest Interval', 'wp-pierre' ),
			array( SettingsFields::class, 'render_number' ),
			'pierre-settings',
			'pierre_section_notifications',
			array(
				'key'     => 'notification_defaults.digest.interval_minutes',
				'default' => 60,
				'id'      => 'notification_defaults_digest_interval_minutes',
				'label'   => __( 'Interval (minutes):', 'wp-pierre' ),
				'help'    => __( 'Used only with Digest/Interval. Minimum 15 minutes.', 'wp-pierre' ),
				'name'    => 'notification_defaults[digest][interval_minutes]',
				'min'     => 15,
			)
		);

		add_settings_field(
			'notification_defaults_digest_fixed_time',
			__( 'Digest Fixed Time', 'wp-pierre' ),
			array( SettingsFields::class, 'render_time' ),
			'pierre-settings',
			'pierre_section_notifications',
			array(
				'key'     => 'notification_defaults.digest.fixed_time',
				'default' => '09:00',
				'id'      => 'notification_defaults_digest_fixed_time',
				'label'   => __( 'Fixed time (HH:MM):', 'wp-pierre' ),
				'help'    => __( 'Used only with Digest/Fixed time. Notification is sent once per day at the set time.', 'wp-pierre' ),
				'name'    => 'notification_defaults[digest][fixed_time]',
			)
		);

		add_settings_field(
			'notification_types',
			__( 'Notification Types', 'wp-pierre' ),
			array( SettingsFields::class, 'render_notification_types' ),
			'pierre-settings',
			'pierre_section_notifications',
			array(
				'key'     => 'notification_types',
				'default' => array( 'new_strings', 'completion_update' ),
				'id'      => 'notification_types',
				'label'   => __( 'Notification Types:', 'wp-pierre' ),
				'help'    => __( 'Select which types of notifications to send', 'wp-pierre' ),
			)
		);

		add_settings_field(
			'notification_threshold',
			__( 'Completion Threshold', 'wp-pierre' ),
			array( SettingsFields::class, 'render_number' ),
			'pierre-settings',
			'pierre_section_notifications',
			array(
				'key'     => 'notification_threshold',
				'default' => 80,
				'id'      => 'notification_threshold',
				'label'   => __( 'Completion Threshold (%):', 'wp-pierre' ),
				'help'    => __( 'Only send notifications when completion is above this threshold', 'wp-pierre' ),
				'min'     => 0,
				'max'     => 100,
			)
		);

		// UI Admin fields
		add_settings_field(
			'ui_menu_icon',
			__( 'Menu Icon', 'wp-pierre' ),
			array( SettingsFields::class, 'render_radio' ),
			'pierre-settings',
			'pierre_section_ui',
			array(
				'key'     => 'ui.menu_icon',
				'default' => 'emoji',
				'id'      => 'ui_menu_icon',
				'label'   => __( 'Menu icon', 'wp-pierre' ),
				'name'    => 'ui[menu_icon]',
				'options' => array(
					'emoji'    => array(
						'label' => __( 'Emoji (default)', 'wp-pierre' ),
						'html'  => '<span aria-hidden="true" class="fs-18 va-middle">🪨</span>',
					),
					'dashicons' => array(
						'label' => __( 'Dashicons: translation', 'wp-pierre' ),
						'html'  => '<span class="dashicons dashicons-translation va-middle" aria-hidden="true"></span>',
					),
				),
				'help'    => __( 'Emoji rendering can vary by platform. Use Dashicons for consistent appearance.', 'wp-pierre' ),
			)
		);

		add_settings_field(
			'ui_plugin_name',
			__( 'Plugin Name', 'wp-pierre' ),
			array( SettingsFields::class, 'render_select' ),
			'pierre-settings',
			'pierre_section_ui',
			array(
				'key'     => 'ui.plugin_name',
				'default' => 'Pierre',
				'id'      => 'ui_plugin_name',
				'label'   => __( 'Displayed name in UI:', 'wp-pierre' ),
				'name'    => 'ui[plugin_name]',
				'options' => array(
					'Pierre'  => 'Pierre',
					'Pieter'  => 'Pieter',
					'Peter'   => 'Peter',
					'Peio'    => 'Peio',
					'Pedro'   => 'Pedro',
					'Πέτρος' => 'Πέτρος',
					'Pier'    => 'Pier',
					'Pietro'  => 'Pietro',
					'Piotr'   => 'Piotr',
				),
			)
		);
	}

	/**
	 * Render surveillance section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_section_surveillance(): void {
		echo '<p class="description">' . esc_html__( 'Configure how often Pierre checks for translation updates and manages surveillance scheduling.', 'wp-pierre' ) . '</p>';
		echo '<h3>' . esc_html__( 'Global toggle', 'wp-pierre' ) . '</h3>';
	}

	/**
	 * Render notifications section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_section_notifications(): void {
		echo '<p class="description">' . esc_html__( 'Configure notification defaults, types, and thresholds for translation updates.', 'wp-pierre' ) . '</p>';
	}

	/**
	 * Render UI section description.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_section_ui(): void {
		echo '<p class="description">' . esc_html__( 'Customize the admin interface appearance and plugin name.', 'wp-pierre' ) . '</p>';
	}

	/**
	 * Register operational options with WordPress Settings API.
	 *
	 * These options are data-driven rather than user-configurable settings,
	 * but they still benefit from register_setting() for sanitization and security.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function register_operational_options(): void {
		// pierre_projects_discovery - Library of discovered projects
		register_setting(
			'pierre_operational_group',
			'pierre_projects_discovery',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_projects_discovery' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		// pierre_watched_projects - List of watched projects
		register_setting(
			'pierre_operational_group',
			'pierre_watched_projects',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_watched_projects' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		// pierre_selected_locales - Selected locales
		register_setting(
			'pierre_operational_group',
			'pierre_selected_locales',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_selected_locales' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		// pierre_locale_managers - Locale managers mapping
		register_setting(
			'pierre_operational_group',
			'pierre_locale_managers',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_locale_managers' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		// pierre_gte - GTE (General Translation Editor) assignments by locale
		// Same format as pierre_locale_managers
		register_setting(
			'pierre_operational_group',
			'pierre_gte',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_locale_managers' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		// pierre_pte - PTE (Project Translation Editor) assignments by locale and project
		register_setting(
			'pierre_operational_group',
			'pierre_pte',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_pte' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize pierre_projects_discovery option.
	 *
	 * Expected format: array of projects with 'type' and 'slug' keys.
	 *
	 * @since 1.0.0
	 * @param mixed $value Raw option value.
	 * @return array Sanitized array of projects.
	 */
	public static function sanitize_projects_discovery( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $project ) {
			if ( ! is_array( $project ) ) {
				continue;
			}

			$sanitized_project = array();
			if ( isset( $project['type'] ) ) {
				$sanitized_project['type'] = sanitize_key( $project['type'] );
			}
			if ( isset( $project['slug'] ) ) {
				$sanitized_project['slug'] = sanitize_key( $project['slug'] );
			}

			// Preserve other keys for backward compatibility
			foreach ( $project as $key => $val ) {
				if ( ! in_array( $key, array( 'type', 'slug' ), true ) ) {
					$sanitized_project[ $key ] = $val;
				}
			}

			if ( ! empty( $sanitized_project['type'] ) && ! empty( $sanitized_project['slug'] ) ) {
				$sanitized[] = $sanitized_project;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize pierre_watched_projects option.
	 *
	 * Expected format: array of watched projects with various metadata.
	 *
	 * @since 1.0.0
	 * @param mixed $value Raw option value.
	 * @return array Sanitized array of watched projects.
	 */
	public static function sanitize_watched_projects( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $key => $project ) {
			if ( ! is_array( $project ) ) {
				continue;
			}

			$sanitized_project = array();
			if ( isset( $project['type'] ) ) {
				$sanitized_project['type'] = sanitize_key( $project['type'] );
			}
			if ( isset( $project['slug'] ) ) {
				$sanitized_project['slug'] = sanitize_key( $project['slug'] );
			}
			if ( isset( $project['locale'] ) ) {
				$sanitized_project['locale'] = OptionHelper::sanitize_locale_code( $project['locale'] );
			}
			if ( isset( $project['locale_code'] ) ) {
				$sanitized_project['locale_code'] = OptionHelper::sanitize_locale_code( $project['locale_code'] );
			}
			if ( isset( $project['added_at'] ) ) {
				$sanitized_project['added_at'] = absint( $project['added_at'] );
			}
			if ( isset( $project['last_checked'] ) ) {
				$sanitized_project['last_checked'] = is_numeric( $project['last_checked'] ) ? absint( $project['last_checked'] ) : null;
			}
			if ( isset( $project['next_check'] ) ) {
				$sanitized_project['next_check'] = absint( $project['next_check'] );
			}
			if ( isset( $project['last_data'] ) ) {
				// Preserve last_data as-is (can be array or null)
				$sanitized_project['last_data'] = $project['last_data'];
			}

			// Preserve other keys for backward compatibility
			foreach ( $project as $k => $val ) {
				if ( ! in_array( $k, array( 'type', 'slug', 'locale', 'locale_code', 'added_at', 'last_checked', 'next_check', 'last_data' ), true ) ) {
					$sanitized_project[ $k ] = $val;
				}
			}

			// Preserve array keys if they are project keys (e.g., 'plugin:slug:locale')
			if ( is_string( $key ) ) {
				$sanitized[ $key ] = $sanitized_project;
			} else {
				$sanitized[] = $sanitized_project;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize pierre_selected_locales option.
	 *
	 * Expected format: array of locale codes (strings).
	 *
	 * @since 1.0.0
	 * @param mixed $value Raw option value.
	 * @return array Sanitized array of locale codes.
	 */
	public static function sanitize_selected_locales( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $locale ) {
			if ( is_string( $locale ) && ! empty( $locale ) ) {
				$sanitized_locale = OptionHelper::sanitize_locale_code( $locale );
				if ( ! empty( $sanitized_locale ) ) {
					$sanitized[] = $sanitized_locale;
				}
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Sanitize pierre_locale_managers option.
	 *
	 * Expected format: associative array where keys are locale codes
	 * and values are arrays of user IDs.
	 *
	 * @since 1.0.0
	 * @param mixed $value Raw option value.
	 * @return array Sanitized locale managers mapping.
	 */
	public static function sanitize_locale_managers( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $locale_code => $user_ids ) {
			if ( ! is_string( $locale_code ) || empty( $locale_code ) ) {
				continue;
			}

			$sanitized_locale = OptionHelper::sanitize_locale_code( $locale_code );
			if ( empty( $sanitized_locale ) ) {
				continue;
			}
			if ( is_array( $user_ids ) ) {
				$sanitized_user_ids = array();
				foreach ( $user_ids as $user_id ) {
					$user_id = absint( $user_id );
					if ( $user_id > 0 ) {
						$sanitized_user_ids[] = $user_id;
					}
				}
				$sanitized[ $sanitized_locale ] = array_values( array_unique( $sanitized_user_ids ) );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize pierre_pte option.
	 *
	 * Expected format: nested associative array where first level keys are locale codes,
	 * second level keys are project keys (e.g., 'plugin:slug'), and values are arrays of user IDs.
	 *
	 * @since 1.0.0
	 * @param mixed $value Raw option value.
	 * @return array Sanitized PTE mapping.
	 */
	public static function sanitize_pte( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $locale_code => $projects ) {
			if ( ! is_string( $locale_code ) || empty( $locale_code ) ) {
				continue;
			}

			$sanitized_locale = OptionHelper::sanitize_locale_code( $locale_code );
			if ( empty( $sanitized_locale ) ) {
				continue;
			}
			if ( ! is_array( $projects ) ) {
				continue;
			}

			$sanitized_projects = array();
			foreach ( $projects as $project_key => $user_ids ) {
				if ( ! is_string( $project_key ) || empty( $project_key ) ) {
					continue;
				}

				$sanitized_project_key = sanitize_text_field( $project_key );
				if ( is_array( $user_ids ) ) {
					$sanitized_user_ids = array();
					foreach ( $user_ids as $user_id ) {
						$user_id = absint( $user_id );
						if ( $user_id > 0 ) {
							$sanitized_user_ids[] = $user_id;
						}
					}
					$sanitized_projects[ $sanitized_project_key ] = array_values( array_unique( $sanitized_user_ids ) );
				}
			}

			if ( ! empty( $sanitized_projects ) ) {
				$sanitized[ $sanitized_locale ] = $sanitized_projects;
			}
		}

		return $sanitized;
	}

	/**
	 * Get client IP address for rate limiting.
	 *
	 * @since 1.0.0
	 * @return string Client IP address
	 */
	private static function get_client_ip(): string {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP',     // Cloudflare
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) ) as $ip ) {
					$ip = trim( $ip );

					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}
}

