<?php
namespace Pierre\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use Pierre\Notifications\MessageBuilder;

class MessageBuilderTest extends TestCase {

    public function test_build_new_strings_message_includes_link(): void {
        $builder = new MessageBuilder();
        $project = [
            'project_type' => 'plugin',
            'project_slug' => 'woocommerce',
            'locale_code'  => 'fr',
            'project_name' => 'WooCommerce',
            'locale_name'  => 'French',
            'stats' => ['completion_percentage' => 42],
        ];
        $msg = $builder->build_new_strings_message($project, 25);
        $this->assertIsArray($msg);
        $this->assertArrayHasKey('text', $msg);
        $this->assertStringContainsString('translate.wordpress.org', $msg['text']);
    }

    public function test_build_milestone_message(): void {
        $builder = new MessageBuilder();
        $project = [
            'project_type' => 'theme',
            'project_slug' => 'twentytwentyfive',
            'locale_code'  => 'fr',
            'project_name' => 'Twenty Twenty-Five',
            'locale_name'  => 'French',
            'stats' => ['completion_percentage' => 80],
        ];
        $msg = $builder->build_milestone_message($project, 80);
        $this->assertIsArray($msg);
        $this->assertArrayHasKey('attachments', $msg);
    }
}


