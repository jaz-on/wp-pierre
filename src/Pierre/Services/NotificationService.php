<?php
/**
 * Pierre's notification service - he coordinates notifications! 🪨
 *
 * This class coordinates notification building and sending.
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Services;

use Pierre\Notifications\MessageBuilder;
use Pierre\Notifications\SlackNotifier;

/**
 * Notification Service class - Pierre's notification coordinator! 🪨
 *
 * @since 1.0.0
 */
class NotificationService {
	/**
	 * Message builder instance.
	 *
	 * @var MessageBuilder
	 */
	private MessageBuilder $message_builder;

	/**
	 * Slack notifier instance.
	 *
	 * @var SlackNotifier
	 */
	private SlackNotifier $slack_notifier;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param SlackNotifier $slack_notifier Slack notifier instance.
	 * @param MessageBuilder $message_builder Message builder instance.
	 */
	public function __construct(
		SlackNotifier $slack_notifier,
		MessageBuilder $message_builder
	) {
		$this->slack_notifier  = $slack_notifier;
		$this->message_builder = $message_builder;
	}

	/**
	 * Build bulk update message.
	 *
	 * @since 1.0.0
	 * @param array $projects_data Array of project data.
	 * @return array Formatted message for Slack.
	 */
	public function build_bulk_update_message( array $projects_data ): array {
		return $this->message_builder->build_bulk_update_message( $projects_data );
	}

	/**
	 * Send message with webhook override.
	 *
	 * @since 1.0.0
	 * @param string $message The message text.
	 * @param string $webhook_url The webhook URL to use.
	 * @param array  $options Additional options.
	 * @return bool True if sent successfully.
	 */
	public function send_with_webhook_override( string $message, string $webhook_url, array $options = array() ): bool {
		return $this->slack_notifier->send_with_webhook_override( $message, $webhook_url, $options );
	}
}

