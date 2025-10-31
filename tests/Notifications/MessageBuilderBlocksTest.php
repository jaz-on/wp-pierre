<?php

use PHPUnit\Framework\TestCase;
use Pierre\Notifications\MessageBuilder;

class MessageBuilderBlocksTest extends TestCase {
    public function test_build_new_strings_uses_blocks(): void {
        $b = new MessageBuilder();
        $msg = $b->build_new_strings_message([
            'project_type' => 'plugin',
            'project_slug' => 'example',
            'locale_code' => 'fr',
            'project_name' => 'Example',
            'locale_name' => 'Français',
            'stats' => ['completion_percentage' => 10]
        ], 5);
        $this->assertIsArray($msg);
        $this->assertArrayHasKey('blocks', $msg);
        $this->assertIsArray($msg['blocks']);
        $this->assertNotEmpty($msg['blocks']);
    }
}


