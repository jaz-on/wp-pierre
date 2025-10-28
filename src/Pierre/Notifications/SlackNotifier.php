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

/**
 * Slack Notifier class - Pierre's messaging system! 🪨
 * 
 * @since 1.0.0
 */
class SlackNotifier implements NotifierInterface {
    
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
    public function send_notification(string $message, array $recipients, array $options = []): bool {
        try {
            // Pierre checks if he's ready! 🪨
            if (!$this->is_ready()) {
                error_log('Pierre\'s notification system is not ready! 😢');
                return false;
            }
            
            // Pierre formats his message! 🪨
            $formatted_message = $this->format_message($message, $options);
            
            // Pierre sends his message! 🪨
            $result = $this->send_to_slack($formatted_message, $recipients);
            
            if ($result) {
                error_log('Pierre sent notification successfully! 🪨');
            } else {
                error_log('Pierre failed to send notification! 😢');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error sending notification: ' . $e->getMessage() . ' 😢');
            return false;
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
        
        error_log('Pierre is sending ' . count($messages) . ' bulk notifications! 🪨');
        
        foreach ($messages as $index => $message) {
            $result = $this->send_notification($message, $recipients, $options);
            $results[] = [
                'index' => $index,
                'success' => $result,
                'message' => $result ? 'Pierre sent message ' . ($index + 1) . '! 🪨' : 'Pierre failed to send message ' . ($index + 1) . '! 😢'
            ];
        }
        
        $success_count = count(array_filter($results, fn($r) => $r['success']));
        error_log("Pierre sent {$success_count}/" . count($messages) . " bulk notifications! 🪨");
        
        return $results;
    }
    
    /**
     * Pierre tests his notification system! 🪨
     * 
     * @since 1.0.0
     * @param string $test_message The test message to send
     * @return bool True if test notification was sent successfully, false otherwise
     */
    public function test_notification(string $test_message = 'Pierre is testing his notification system! 🪨'): bool {
        try {
            error_log('Pierre is testing his notification system! 🪨');
            
            // Pierre builds a test message! 🪨
            $test_data = $this->message_builder->build_test_message('testing');
            $test_data['text'] = $test_message;
            
            // Pierre sends his test message! 🪨
            $result = $this->send_to_slack($test_data, []);
            
            if ($result) {
                error_log('Pierre\'s test notification was sent successfully! 🪨');
            } else {
                error_log('Pierre\'s test notification failed! 😢');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error during test: ' . $e->getMessage() . ' 😢');
            return false;
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
     * Pierre gets his notification status! 🪨
     * 
     * @since 1.0.0
     * @return array Array containing notification system status information
     */
    public function get_status(): array {
        return [
            'ready' => $this->is_ready(),
            'webhook_configured' => $this->webhook_url !== null,
            'webhook_url' => $this->webhook_url ? 'configured' : 'not configured',
            'message' => $this->is_ready() ? 'Pierre\'s notification system is ready! 🪨' : 'Pierre\'s notification system needs configuration! 😢'
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
                return call_user_func([$this->message_builder, $method_name], $context['data']);
            }
        }
        
        // Pierre creates a simple message! 🪨
        return [
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
     * Pierre loads his webhook URL from settings! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function load_webhook_url(): void {
        $settings = get_option('pierre_settings', []);
        $this->webhook_url = $settings['slack_webhook_url'] ?? null;
        
        if ($this->webhook_url) {
            error_log('Pierre loaded his webhook URL! 🪨');
        } else {
            error_log('Pierre needs to configure his webhook URL! 😢');
        }
    }
    
    /**
     * Pierre sends a message to Slack! 🪨
     * 
     * @since 1.0.0
     * @param array $message_data The formatted message data
     * @param array $recipients The recipient information
     * @return bool True if message was sent successfully, false otherwise
     */
    private function send_to_slack(array $message_data, array $recipients): bool {
        if (!$this->is_ready()) {
            return false;
        }
        
        // Pierre prepares his request! 🪨
        $args = [
            'timeout' => self::REQUEST_TIMEOUT,
            'user-agent' => 'Pierre-WordPress-Translation-Monitor/1.0.0',
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($message_data)
        ];
        
        // Pierre sends his request! 🪨
        $response = wp_remote_post($this->webhook_url, $args);
        
        if (is_wp_error($response)) {
            error_log('Pierre encountered a WP error: ' . $response->get_error_message() . ' 😢');
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log("Pierre got HTTP {$response_code} from Slack! 😢");
            return false;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        if ($response_body !== 'ok') {
            error_log('Pierre got unexpected response from Slack: ' . $response_body . ' 😢');
            return false;
        }
        
        return true;
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
        if (!filter_var($webhook_url, FILTER_VALIDATE_URL)) {
            error_log('Pierre says: Invalid webhook URL! 😢');
            return false;
        }
        
        if (strpos($webhook_url, 'hooks.slack.com') === false) {
            error_log('Pierre says: URL must be a Slack webhook! 😢');
            return false;
        }
        
        $this->webhook_url = $webhook_url;
        
        // Pierre saves his webhook URL! 🪨
        $settings = get_option('pierre_settings', []);
        $settings['slack_webhook_url'] = $webhook_url;
        update_option('pierre_settings', $settings);
        
        error_log('Pierre set his webhook URL! 🪨');
        return true;
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