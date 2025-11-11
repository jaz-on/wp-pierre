<?php
/**
 * Error Helper - Standardizes error message formatting
 *
 * This class provides helper methods for formatting error messages
 * consistently across the plugin.
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Helpers;

/**
 * Error Helper class
 *
 * @since 1.0.0
 */
class ErrorHelper {
	/**
	 * Format an error message with optional emoji.
	 *
	 * Standardizes error message format: "Pierre says: {message}" with optional emoji.
	 *
	 * @param string $message The error message to format.
	 * @param bool   $include_emoji Whether to include the sad emoji (default: true).
	 * @return string Formatted error message.
	 */
	public static function format_error_message( string $message, bool $include_emoji = true ): string {
		// If message already starts with "Pierre says:", use it as-is
		if ( strpos( $message, 'Pierre says:' ) === 0 ) {
			return $include_emoji ? $message . ' 😢' : $message;
		}

		// Otherwise, prepend "Pierre says:"
		$formatted = sprintf( __( 'Pierre says: %s', 'wp-pierre' ), $message );
		return $include_emoji ? $formatted . ' 😢' : $formatted;
	}
}

