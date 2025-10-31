<?php

use PHPUnit\Framework\TestCase;
use Pierre\Notifications\SlackNotifier;

class SlackNotifierReadyTest extends TestCase {
    protected function setUp(): void {
        // Minimal shim for get_option/update_option used in SlackNotifier
        if (!function_exists('get_option')) {
            function get_option($name, $default = false) {
                global $___test_options;
                return $___test_options[$name] ?? $default;
            }
        }
        if (!function_exists('update_option')) {
            function update_option($name, $value) {
                global $___test_options;
                $___test_options[$name] = $value;
                return true;
            }
        }
        if (!function_exists('error_log')) {
            function error_log($msg) { /* noop */ }
        }
        global $___test_options;
        $___test_options = [];
    }

    public function test_not_ready_without_webhook(): void {
        $notifier = new SlackNotifier();
        $this->assertFalse($notifier->is_ready());
    }

    public function test_ready_with_webhook(): void {
        global $___test_options;
        $___test_options['pierre_settings'] = [ 'slack_webhook_url' => 'https://hooks.slack.com/services/T000/B000/XXX' ];
        $notifier = new SlackNotifier();
        $this->assertTrue($notifier->is_ready());
    }
}


