<?php
/**
 * Pierre's notification interface - he defines how to notify! 🪨
 * 
 * This interface defines the contract for all notification components
 * that Pierre uses to send messages to teams via Slack.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Notifications;

/**
 * Notifier interface - Pierre's notification contract! 🪨
 * 
 * @since 1.0.0
 */
interface NotifierInterface {
    
    /**
     * Pierre sends a notification! 🪨
     * 
     * @since 1.0.0
     * @param string $message The message to send
     * @param array $recipients Array of recipient information
     * @param array $options Additional options for the notification
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function send_notification(string $message, array $recipients, array $options = []): bool;
    
    /**
     * Pierre sends a bulk notification! 🪨
     * 
     * @since 1.0.0
     * @param array $messages Array of messages to send
     * @param array $recipients Array of recipient information
     * @param array $options Additional options for the notifications
     * @return array Array of results for each message sent
     */
    public function send_bulk_notifications(array $messages, array $recipients, array $options = []): array;
    
    /**
     * Pierre tests his notification system! 🪨
     * 
     * @since 1.0.0
     * @param string $test_message The test message to send
     * @return bool True if test notification was sent successfully, false otherwise
     */
    public function test_notification(string $test_message = 'Pierre is testing his notification system! 🪨'): bool;
    
    /**
     * Pierre checks if his notification system is ready! 🪨
     * 
     * @since 1.0.0
     * @return bool True if notification system is ready, false otherwise
     */
    public function is_ready(): bool;
    
    /**
     * Pierre gets his notification status! 🪨
     * 
     * @since 1.0.0
     * @return array Array containing notification system status information
     */
    public function get_status(): array;
    
    /**
     * Pierre formats a message for sending! 🪨
     * 
     * @since 1.0.0
     * @param string $message The raw message to format
     * @param array $context Additional context for formatting
     * @return array The formatted message payload
     */
    public function format_message(string $message, array $context = []): array;
}