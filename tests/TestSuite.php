<?php
/**
 * Pierre's test suite - he tests everything thoroughly! 🪨
 * 
 * This is Pierre's main test suite configuration for PHPUnit.
 * He makes sure everything works perfectly!
 * 
 * @package Pierre\Tests
 * @since 1.0.0
 */

namespace Pierre\Tests;

use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\TestCase;

/**
 * Pierre's Test Suite class - he organizes all his tests! 🪨
 * 
 * @since 1.0.0
 */
class TestSuite extends TestSuite {
    
    /**
     * Pierre constructs his test suite! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct('Pierre Test Suite 🪨');
        
        // Pierre adds all his test cases! 🪨
        $this->addTestSuite(Surveillance\CronManagerTest::class);
        $this->addTestSuite(Surveillance\TranslationScraperTest::class);
        $this->addTestSuite(Surveillance\ProjectWatcherTest::class);
        $this->addTestSuite(Notifications\MessageBuilderTest::class);
        $this->addTestSuite(Notifications\SlackNotifierTest::class);
        $this->addTestSuite(Teams\RoleManagerTest::class);
        $this->addTestSuite(Teams\TeamRepositoryTest::class);
        $this->addTestSuite(Teams\UserProjectLinkTest::class);
        $this->addTestSuite(Admin\AdminControllerTest::class);
        $this->addTestSuite(Frontend\DashboardControllerTest::class);
        $this->addTestSuite(PluginTest::class);
        
        error_log('Pierre created his test suite! 🪨');
    }
}
