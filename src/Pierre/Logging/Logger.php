<?php
/**
 * Pierre's logger - he logs everything! 🪨
 *
 * This class provides centralized logging functionality for Pierre.
 * All debug, info, warning, and error logging should go through this class.
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Logging;

/**
 * Logger class - Pierre's centralized logging system! 🪨
 *
 * @since 1.0.0
 */
class Logger {
	/**
	 * Check if debug logging is enabled.
	 *
	 * @since 1.0.0
	 * @return bool True if debug is enabled.
	 */
	public static function is_debug(): bool {
		return defined( 'PIERRE_DEBUG' ) ? (bool) PIERRE_DEBUG : false;
	}

	/**
	 * Log a debug message.
	 *
	 * @since 1.0.0
	 * @param string $message The message to log.
	 * @param array  $context Additional context.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		self::static_debug( $message, $context );
	}

	/**
	 * Log a debug message (static method for convenience).
	 *
	 * @since 1.0.0
	 * @param string $message The message to log.
	 * @param array  $context Additional context.
	 * @return void
	 */
	public static function static_debug( string $message, array $context = array() ): void {
		if ( ! self::is_debug() ) {
			return;
		}
		// Centralize do_action('wp_pierre_debug') here
		do_action( 'wp_pierre_debug', $message, $context );
		// Also log to error_log for backward compatibility
		error_log( '[wp-pierre] ' . $message );
	}

	/**
	 * Log an info message.
	 *
	 * @since 1.0.0
	 * @param string $message The message to log.
	 * @param array  $context Additional context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		self::static_info( $message, $context );
	}

	/**
	 * Log an info message (static method for convenience).
	 *
	 * @since 1.0.0
	 * @param string $message The message to log.
	 * @param array  $context Additional context.
	 * @return void
	 */
	public static function static_info( string $message, array $context = array() ): void {
		do_action( 'wp_pierre_debug', '[INFO] ' . $message, $context );
		error_log( '[wp-pierre] [INFO] ' . $message );
	}

	/**
	 * Log a warning message.
	 *
	 * @since 1.0.0
	 * @param string $message The message to log.
	 * @param array  $context Additional context.
	 * @return void
	 */
	public function warning( string $message, array $context = array() ): void {
		self::static_warning( $message, $context );
	}

	/**
	 * Log a warning message (static method for convenience).
	 *
	 * @since 1.0.0
	 * @param string $message The message to log.
	 * @param array  $context Additional context.
	 * @return void
	 */
	public static function static_warning( string $message, array $context = array() ): void {
		error_log( '[wp-pierre] [WARNING] ' . $message );
		do_action( 'wp_pierre_debug', '[WARNING] ' . $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @since 1.0.0
	 * @param string $message The message to log.
	 * @param array  $context Additional context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		self::static_error( $message, $context );
	}

	/**
	 * Log an error message (static method for convenience).
	 *
	 * @since 1.0.0
	 * @param string $message The message to log.
	 * @param array  $context Additional context.
	 * @return void
	 */
	public static function static_error( string $message, array $context = array() ): void {
		error_log( '[wp-pierre] [ERROR] ' . $message );
		do_action( 'wp_pierre_debug', '[ERROR] ' . $message, $context );
	}
}

