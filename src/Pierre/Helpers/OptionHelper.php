<?php
/**
 * Option Helper - Standardizes option access and sanitization
 *
 * This class provides helper methods for accessing WordPress options
 * and sanitizing locale codes consistently across the plugin.
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Helpers;

/**
 * Option Helper class
 *
 * @since 1.0.0
 */
class OptionHelper {
	/**
	 * Get an option value as an array with automatic validation.
	 *
	 * Wrapper for get_option() that ensures the returned value is always an array.
	 * Validates the result and returns an empty array if invalid.
	 *
	 * @param string $name Option name.
	 * @param array  $default Default value (default: empty array).
	 * @return array Option value as array, or default if invalid/not found.
	 */
	public static function get_option_array( string $name, array $default = [] ): array {
		$value = get_option( $name, $default );
		return is_array( $value ) ? $value : $default;
	}

	/**
	 * Sanitize a locale code.
	 *
	 * Standardizes locale code sanitization across the plugin.
	 * Validates format: "fr" or "fr_FR" (2 lowercase letters, optional underscore and 2 uppercase letters).
	 *
	 * @param string $locale Locale code to sanitize.
	 * @return string Sanitized locale code, or empty string if invalid.
	 */
	public static function sanitize_locale_code( string $locale ): string {
		$sanitized = sanitize_key( $locale );
		
		// Validate format: "fr" or "fr_FR"
		if ( ! preg_match( '/^[a-z]{2}(_[a-z]{2})?$/', $sanitized ) ) {
			return '';
		}
		
		// Convert second part to uppercase if present (e.g., "fr_fr" -> "fr_FR")
		if ( strpos( $sanitized, '_' ) !== false ) {
			$parts = explode( '_', $sanitized, 2 );
			if ( count( $parts ) === 2 && strlen( $parts[1] ) === 2 ) {
				$sanitized = $parts[0] . '_' . strtoupper( $parts[1] );
			}
		}
		
		return $sanitized;
	}
}

