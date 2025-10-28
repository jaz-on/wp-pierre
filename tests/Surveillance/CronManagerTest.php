<?php
/**
 * Pierre's CronManager test - he tests his cron functionality! 🪨
 * 
 * This class tests Pierre's CronManager functionality
 * to ensure surveillance scheduling works perfectly.
 * 
 * @package Pierre\Tests\Surveillance
 * @since 1.0.0
 */

namespace Pierre\Tests\Surveillance;

use PHPUnit\Framework\TestCase;
use Pierre\Surveillance\CronManager;

/**
 * CronManager Test class - Pierre tests his cron manager! 🪨
 * 
 * @since 1.0.0
 */
class CronManagerTest extends TestCase {
    
    /**
     * Pierre's cron manager instance! 🪨
     * 
     * @var CronManager
     */
    private CronManager $cron_manager;
    
    /**
     * Pierre sets up his test! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Pierre creates his cron manager! 🪨
        $this->cron_manager = new CronManager();
        
        error_log('Pierre set up his CronManager test! 🪨');
    }
    
    /**
     * Pierre tests cron manager creation! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function test_cron_manager_creation(): void {
        // Pierre checks if cron manager exists! 🪨
        $this->assertInstanceOf(CronManager::class, $this->cron_manager);
        
        error_log('Pierre tested cron manager creation! 🪨');
    }
    
    /**
     * Pierre tests surveillance status! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function test_get_surveillance_status(): void {
        // Pierre gets surveillance status! 🪨
        $status = $this->cron_manager->get_surveillance_status();
        
        // Pierre checks if status is array! 🪨
        $this->assertIsArray($status);
        
        // Pierre checks if status has required keys! 🪨
        $this->assertArrayHasKey('active', $status);
        $this->assertArrayHasKey('next_run', $status);
        $this->assertArrayHasKey('message', $status);
        
        error_log('Pierre tested surveillance status! 🪨');
    }
    
    /**
     * Pierre tests cleanup status! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function test_get_cleanup_status(): void {
        // Pierre gets cleanup status! 🪨
        $status = $this->cron_manager->get_cleanup_status();
        
        // Pierre checks if status is array! 🪨
        $this->assertIsArray($status);
        
        // Pierre checks if status has required keys! 🪨
        $this->assertArrayHasKey('active', $status);
        $this->assertArrayHasKey('next_run', $status);
        $this->assertArrayHasKey('message', $status);
        
        error_log('Pierre tested cleanup status! 🪨');
    }
    
    /**
     * Pierre tests cron manager status! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function test_get_status(): void {
        // Pierre gets cron manager status! 🪨
        $status = $this->cron_manager->get_status();
        
        // Pierre checks if status is array! 🪨
        $this->assertIsArray($status);
        
        // Pierre checks if status has required keys! 🪨
        $this->assertArrayHasKey('surveillance_scheduled', $status);
        $this->assertArrayHasKey('cleanup_scheduled', $status);
        $this->assertArrayHasKey('message', $status);
        
        error_log('Pierre tested cron manager status! 🪨');
    }
    
    /**
     * Pierre tears down his test! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        
        error_log('Pierre tore down his CronManager test! 🪨');
    }
}
