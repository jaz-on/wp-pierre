<?php
/**
 * Pierre's Slack notifier - he sends messages! 🪨
 * 
 * This class handles sending notifications to Slack channels
 * when Pierre detects changes in WordPress translations.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Notifications;

use Pierre\Security\Encryption;
use Pierre\Security\WebhookValidator;
use Pierre\Settings\Settings;
use Pierre\Traits\StatusTrait;
use Pierre\Logging\Logger;

/**
 * Slack Notifier class - Pierre's messaging system! 🪨
 * 
 * @since 1.0.0
 */
class SlackNotifier implements NotifierInterface {
    use StatusTrait;
    
    /**
     * Pierre's message builder - he crafts beautiful messages! 🪨
     * 
     * @var MessageBuilder
     */
    private MessageBuilder $message_builder;
    
    /**
     * Pierre's webhook URL - he knows where to send messages! 🪨
     * 
     * @var string|null
     */
    private ?string $webhook_url = null;

    /**
     * Pierre's last error message - he remembers his mistakes! 🪨
     *
     * @var string|null Last error message or null if no error occurred.
     */
    private ?string $last_error = null;
    
    /**
     * Pierre's request timeout - he doesn't wait forever! 🪨
     * 
     * @var int
     */
    private const REQUEST_TIMEOUT = 30;
    
    /**
     * Pierre's constructor - he prepares his messaging system! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->message_builder = new MessageBuilder();
        $this->load_webhook_url();
    }
    
    /**
     * Pierre sends a notification! 🪨
     * 
     * @since 1.0.0
     * @param string $message The message to send
     * @param array $recipients Array of recipient information
     * @param array $options Additional options for the notification
     * @return bool True if notification was sent successfully, false otherwise
     */
    public function send_notification(string $message, array $recipients, array $options = []): bool|\WP_Error {
        try {
            // Pierre checks if he's ready! 🪨
            if (!$this->is_ready()) {
                Logger::static_debug('Pierre\'s notification system is not ready! 😢');
                return new \WP_Error(
                    'pierre_notifier_not_ready',
                    __('Notification system is not ready. Webhook URL not configured.', 'wp-pierre')
                );
            }
            
            // Pierre formats his message! 🪨
            $formatted_message = $this->format_message($message, $options);
            
            // Pierre sends his message! 🪨
            $result = $this->send_to_webhook($formatted_message);
            
            if ($result === true) {
                Logger::static_debug('Pierre sent notification successfully! 🪨');
                return true;
            } elseif (is_wp_error($result)) {
                Logger::static_debug('Pierre failed to send notification: ' . $result->get_error_message() . ' 😢');
                return $result;
            } else {
                Logger::static_debug('Pierre failed to send notification! 😢');
                return new \WP_Error(
                    'pierre_notification_failed',
                    __('Failed to send notification.', 'wp-pierre'),
                    ['last_error' => $this->last_error]
                );
            }
            
        } catch (\Exception $e) {
            Logger::static_debug('Pierre encountered an error sending notification: ' . $e->getMessage() . ' 😢');
            return new \WP_Error(
                'pierre_notification_exception',
                __('An exception occurred while sending notification.', 'wp-pierre'),
                ['message' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Pierre sends a bulk notification! 🪨
     * 
     * @since 1.0.0
     * @param array $messages Array of messages to send
     * @param array $recipients Array of recipient information
     * @param array $options Additional options for the notifications
     * @return array Array of results for each message sent
     */
    public function send_bulk_notifications(array $messages, array $recipients, array $options = []): array {
        $results = [];
        
        Logger::static_debug('Pierre is sending ' . count($messages) . ' bulk notifications! 🪨');
        
        foreach ($messages as $index => $message) {
            $result = $this->send_notification($message, $recipients, $options);
            $success = !is_wp_error($result) && $result === true;
            $results[] = [
                'index' => $index,
                'success' => $success,
                'error' => is_wp_error($result) ? $result->get_error_message() : null,
                'message' => $success ? 'Pierre sent message ' . ($index + 1) . '! 🪨' : 'Pierre failed to send message ' . ($index + 1) . '! 😢'
            ];
        }
        
        $success_count = count(array_filter($results, fn($r) => $r['success']));
        Logger::static_debug("Pierre sent {$success_count}/" . count($messages) . " bulk notifications! 🪨");
        
        return $results;
    }
    
    /**
     * Pierre tests his notification system! 🪨
     * 
     * @since 1.0.0
     * @param string $test_message The test message to send
     * @return bool True if test notification was sent successfully, false otherwise
     */
    public function test_notification(string $test_message = 'Pierre is testing his notification system! 🪨'): bool|\WP_Error {
        try {
            Logger::static_debug('Pierre is testing his notification system! 🪨');
            
            // Pierre builds a test message! 🪨
            $test_data = $this->message_builder->build_test_message('testing');
            $test_data['text'] = $test_message;
            
            // Pierre sends his test message! 🪨
            $result = $this->send_to_webhook($test_data);
            
            if ($result === true) {
                Logger::static_debug('Pierre\'s test notification was sent successfully! 🪨');
                return true;
            } elseif (is_wp_error($result)) {
                Logger::static_debug('Pierre\'s test notification failed: ' . $result->get_error_message() . ' 😢', ['source' => 'SlackNotifier']);
                return $result;
            } else {
                Logger::static_debug('Pierre\'s test notification failed! 😢', ['source' => 'SlackNotifier']);
                return new \WP_Error(
                    'pierre_test_failed',
                    __('Test notification failed.', 'wp-pierre'),
                    ['last_error' => $this->last_error]
                );
            }
            
        } catch (\Exception $e) {
            $this->last_error = $e->getMessage();
            Logger::static_debug('Pierre encountered an error during test: ' . $e->getMessage() . ' 😢', ['source' => 'SlackNotifier']);
            return new \WP_Error(
                'pierre_test_exception',
                __('An exception occurred during test.', 'wp-pierre'),
                ['message' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Pierre checks if his notification system is ready! 🪨
     * 
     * @since 1.0.0
     * @return bool True if notification system is ready, false otherwise
     */
    public function is_ready(): bool {
        return $this->webhook_url !== null && !empty($this->webhook_url);
    }
    
    /**
     * Get status message.
     *
     * @since 1.0.0
     * @return string Status message
     */
    protected function get_status_message(): string {
        return $this->is_ready() ? 'Pierre\'s notification system is ready! 🪨' : 'Pierre\'s notification system needs configuration! 😢';
    }

    /**
     * Get status details.
     *
     * @since 1.0.0
     * @return array Status details
     */
    protected function get_status_details(): array {
        return [
            'ready' => $this->is_ready(),
            'webhook_configured' => $this->webhook_url !== null,
            'webhook_url' => $this->webhook_url ? 'configured' : 'not configured',
        ];
    }
    
    /**
     * Pierre formats a message for sending! 🪨
     * 
     * @since 1.0.0
     * @param string $message The raw message to format
     * @param array $context Additional context for formatting
     * @return array The formatted Slack message
     */
    public function format_message(string $message, array $context = []): array {
        // Pierre uses his message builder! 🪨
        if (isset($context['template']) && isset($context['data'])) {
            $method_name = 'build_' . $context['template'] . '_message';
            if (method_exists($this->message_builder, $method_name)) {
                $formatted = call_user_func([$this->message_builder, $method_name], $context['data']);
            } else {
                $formatted = [
                    'text' => $message,
                    'attachments' => [
                        [
                            'color' => $context['color'] ?? 'good',
                            'footer' => 'Pierre - WordPress Translation Monitor',
                            'footer_icon' => 'https://s.w.org/images/wmark.png',
                            'ts' => time()
                        ]
                    ]
                ];
            }
        } else {
            // Pierre creates a simple message! 🪨
            $formatted = [
                'text' => $message,
                'attachments' => [
                    [
                        'color' => $context['color'] ?? 'good',
                        'footer' => 'Pierre - WordPress Translation Monitor',
                        'footer_icon' => 'https://s.w.org/images/wmark.png',
                        'ts' => time()
                    ]
                ]
            ];
        }
        
        /**
         * Filter notification message before sending.
         *
         * @since 1.0.0
         * @param array $formatted The formatted Slack message data.
         * @param string $message  The original message text.
         * @param array $context   Additional context for formatting.
         */
        return apply_filters('pierre_notification_message', $formatted, $message, $context);
    }
    
    /**
     * Pierre loads his webhook URL from settings! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function load_webhook_url(): void {
        $settings = Settings::all();
        // Prefer new global webhook if set, fallback to legacy key
        $gw = is_array($settings['global_webhook'] ?? null) ? $settings['global_webhook'] : [];
        $raw_url = ($gw['webhook_url'] ?? null) ?: ($settings['slack_webhook_url'] ?? null);
        
        // Decrypt webhook URL if encrypted
        if ( ! empty( $raw_url ) ) {
            $decrypted = Encryption::decrypt( $raw_url );
            $this->webhook_url = ( $decrypted !== false ) ? $decrypted : $raw_url;
        } else {
            $this->webhook_url = null;
        }
        // No routine logging here to avoid polluting debug.log
    }
    
    /**
     * Pierre sends a message to Slack webhook! 🪨
     * 
     * Unified method to send messages to Slack webhook.
     * Uses instance webhook_url if $webhook_url is not provided.
     * 
     * @since 1.0.0
     * @param array $message_data The formatted message data
     * @param string|null $webhook_url Optional webhook URL (uses instance webhook_url if null)
     * @return bool|\WP_Error True if message was sent successfully, WP_Error otherwise
     */
    private function send_to_webhook(array $message_data, ?string $webhook_url = null): bool|\WP_Error {
        // Use provided webhook or instance webhook
        $target_webhook = $webhook_url ?? $this->webhook_url;
        
        if (empty($target_webhook)) {
            $this->last_error = $webhook_url ? 'Webhook URL empty' : 'Webhook URL not configured';
            return new \WP_Error(
                $webhook_url ? 'pierre_webhook_empty' : 'pierre_webhook_not_configured',
                $webhook_url ? __('Webhook URL is empty.', 'wp-pierre') : __('Webhook URL not configured.', 'wp-pierre')
            );
        }
        
        // Pierre prepares his request! 🪨
        $defaults = \Pierre\Plugin::get_http_defaults();
        $args = [
            'timeout' => $defaults['timeout'] ?? self::REQUEST_TIMEOUT,
            'user-agent' => 'Pierre-WordPress-Translation-Monitor/1.0.0',
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($message_data)
        ];
        
        /**
         * Filter API request arguments before sending to Slack.
         *
         * @since 1.0.0
         * @param array $args Request arguments.
         * @param string $webhook_url The Slack webhook URL.
         */
        $args = apply_filters('pierre_api_request_args', $args, $target_webhook);
        
        // Pierre sends his request! 🪨
        $response = wp_remote_post($target_webhook, $args);
        
        if (is_wp_error($response)) {
            $this->last_error = 'WP_Error: ' . $response->get_error_message();
            Logger::static_debug('Pierre encountered a WP error: ' . $response->get_error_message() . ' 😢', ['source' => 'SlackNotifier']);
            return new \WP_Error(
                'pierre_slack_request_failed',
                __('Failed to send request to Slack.', 'wp-pierre'),
                ['error' => $response->get_error_message(), 'error_code' => $response->get_error_code()]
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $this->last_error = 'HTTP ' . $response_code . ': ' . wp_remote_retrieve_body($response);
            Logger::static_debug("Pierre got HTTP {$response_code} from Slack! 😢", ['source' => 'SlackNotifier']);
            return new \WP_Error(
                'pierre_slack_http_error',
                __('Slack API returned an error.', 'wp-pierre'),
                ['code' => $response_code, 'body' => wp_remote_retrieve_body($response)]
            );
        }
        
        $response_body = wp_remote_retrieve_body($response);
        if ($response_body !== 'ok') {
            $this->last_error = 'Slack replied: ' . $response_body;
            Logger::static_debug('Pierre got unexpected response from Slack: ' . $response_body . ' 😢', ['source' => 'SlackNotifier']);
            return new \WP_Error(
                'pierre_slack_unexpected_response',
                __('Slack returned an unexpected response.', 'wp-pierre'),
                ['body' => $response_body]
            );
        }
        
        return true;
    }

    /**
     * Public helper to send a simple text with a specific webhook override
     *
     * @since 1.0.0
     */
    public function send_with_webhook_override(string $message, string $webhook_url, array $options = []): bool|\WP_Error {
        // If already formatted message is provided, use it directly
        if (!empty($options['formatted_message']) && is_array($options['formatted_message'])) {
            $formatted_message = $options['formatted_message'];
        } else {
            $formatted_message = $this->format_message($message, $options);
        }
        return $this->send_to_webhook($formatted_message, $webhook_url);
    }

    /**
     * Test notification for a specific webhook URL
     *
     * @since 1.0.0
     */
    public function test_notification_for_webhook(string $webhook_url, string $test_message = 'Pierre is testing his notification system! 🪨'): bool|\WP_Error {
        $test_data = $this->message_builder->build_test_message('testing');
        $test_data['text'] = $test_message;
        return $this->send_to_webhook($test_data, $webhook_url);
    }
    
    /**
     * Pierre sets his webhook URL! 🪨
     * 
     * @since 1.0.0
     * @param string $webhook_url The Slack webhook URL
     * @return bool True if URL was set successfully, false otherwise
     */
    public function set_webhook_url(string $webhook_url): bool {
        // Pierre validates his webhook URL! 🪨
        if (!WebhookValidator::validate($webhook_url)) {
            Logger::static_debug('Pierre says: Invalid webhook URL! 😢', ['source' => 'SlackNotifier']);
            return false;
        }
        
        $this->webhook_url = $webhook_url;
        
        // Pierre saves his webhook URL encrypted! 🪨
        $settings = Settings::all();
        $encrypted_url = Encryption::encrypt( $webhook_url );
        $settings['slack_webhook_url'] = ( $encrypted_url !== false ) ? $encrypted_url : $webhook_url;
        // Called from already-secured contexts (AJAX handlers), skip security checks
        $update_result = Settings::update($settings, array(
            'skip_nonce_check' => true,
            'skip_permission_check' => true,
            'skip_rate_limit' => false, // Keep rate limiting active
        ));
        
        // Check for validation errors
        if ( is_wp_error( $update_result ) ) {
            $error_messages = $update_result->get_error_messages();
            Logger::static_debug(
                sprintf(
                    'Pierre failed to save webhook URL: %s',
                    implode( '; ', $error_messages )
                ),
                ['source' => 'SlackNotifier']
            );
            return false;
        }
        
        // Check for database update failure
        if ( $update_result === false ) {
            Logger::static_debug('Pierre failed to save webhook URL: database update failed', ['source' => 'SlackNotifier']);
            return false;
        }
        
        Logger::static_debug('Pierre set his webhook URL (encrypted)! 🪨', ['source' => 'SlackNotifier']);
        return true;
    }

    /**
     * Get last error detail if any
     */
    public function get_last_error(): ?string {
        return $this->last_error;
    }
    
    /**
     * Pierre gets his message builder! 🪨
     * 
     * @since 1.0.0
     * @return MessageBuilder Pierre's message builder
     */
    public function get_message_builder(): MessageBuilder {
        return $this->message_builder;
    }
}
