<?php
/**
 * Pierre's webhook validator - he validates webhook URLs! 🪨
 * 
 * This class provides centralized validation for Slack webhook URLs
 * used across the plugin.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Security;

/**
 * Webhook Validator class - Pierre's webhook validation system! 🪨
 * 
 * @since 1.0.0
 */
class WebhookValidator {
	/**
	 * Validate a Slack webhook URL.
	 * 
	 * @since 1.0.0
	 * @param string $webhook_url The webhook URL to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate( string $webhook_url ): bool {
		if ( empty( $webhook_url ) ) {
			return false;
		}
		
		// Validate URL format
		if ( ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		
		// Check if it's a Slack webhook URL
		if ( strpos( $webhook_url, 'hooks.slack.com' ) === false ) {
			return false;
		}
		
		return true;
	}
}

