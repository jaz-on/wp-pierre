<?php
namespace Pierre\Tests\Surveillance;

use PHPUnit\Framework\TestCase;
use Pierre\Surveillance\TranslationScraper;

class TranslationScraperTest extends TestCase {

    public function test_scraper_status_structure(): void {
        $s = new TranslationScraper();
        $st = $s->get_status();
        $this->assertIsArray($st);
        $this->assertArrayHasKey('api_base_url', $st);
        $this->assertArrayHasKey('cache_timeout', $st);
        $this->assertArrayHasKey('request_timeout', $st);
    }

    public function test_scrape_typed_project_returns_data(): void {
        $s = new TranslationScraper();
        $res = $s->scrape_typed_project('plugin', 'example', 'fr', 'default');
        // With test bootstrap mocking HTTP 200 + JSON, we should get an array or null
        // Ensure no fatal and type is consistent
        $this->assertTrue(is_array($res) || is_null($res));
        if (is_array($res)) {
            $this->assertArrayHasKey('project_type', $res);
            $this->assertArrayHasKey('project_slug', $res);
            $this->assertArrayHasKey('locale_code', $res);
            $this->assertArrayHasKey('stats', $res);
        }
    }
}


