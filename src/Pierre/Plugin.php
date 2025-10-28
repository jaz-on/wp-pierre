<?php
/**
 * Pierre's main class - the heart of the operation! 🪨
 * 
 * This class orchestrates all of Pierre's surveillance activities,
 * from monitoring translations to sending Slack notifications.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre;

use Pierre\Surveillance\CronManager;
use Pierre\Surveillance\ProjectWatcher;
use Pierre\Notifications\SlackNotifier;
use Pierre\Teams\RoleManager;
use Pierre\Teams\TeamRepository;
use Pierre\Admin\AdminController;
use Pierre\Frontend\DashboardController;

/**
 * Main Plugin class - Pierre's command center! 🪨
 * 
 * @since 1.0.0
 */
class Plugin {
    
    /**
     * Pierre's surveillance manager - he watches everything! 🪨
     * 
     * @var CronManager
     */
    private CronManager $cron_manager;
    
    /**
     * Pierre's project watcher - he monitors translations! 🪨
     * 
     * @var ProjectWatcher
     */
    private ProjectWatcher $project_watcher;
    
    /**
     * Pierre's Slack notifier - he sends messages! 🪨
     * 
     * @var SlackNotifier
     */
    private SlackNotifier $slack_notifier;
    
    /**
     * Pierre's role manager - he manages permissions! 🪨
     * 
     * @var RoleManager
     */
    private RoleManager $role_manager;
    
    /**
     * Pierre's team repository - he stores team data! 🪨
     * 
     * @var TeamRepository
     */
    private TeamRepository $team_repository;
    
    /**
     * Pierre's admin controller - he manages the admin! 🪨
     * 
     * @var AdminController
     */
    private AdminController $admin_controller;
    
    /**
     * Pierre's frontend controller - he manages the public! 🪨
     * 
     * @var DashboardController
     */
    private DashboardController $frontend_controller;
    
    /**
     * Pierre's initialization flag - he tracks his state! 🪨
     * 
     * @var bool
     */
    private bool $initialized = false;
    
    /**
     * Pierre's constructor - he prepares for action! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        // Pierre is ready to work! 🪨
        error_log('Pierre is being constructed... 🪨');
    }
    
    /**
     * Pierre initializes his surveillance system! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        if ($this->initialized) {
            error_log('Pierre is already initialized! 🪨');
            return;
        }
        
        try {
            // Pierre loads his text domain for translations! 🪨
            $this->load_textdomain();
            
            // Pierre initializes his components! 🪨
            $this->init_components();
            
            // Pierre sets up his hooks! 🪨
            $this->init_hooks();
            
            $this->initialized = true;
            error_log('Pierre is fully initialized and ready to work! 🪨');
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error during initialization: ' . $e->getMessage() . ' 😢');
            wp_die('Pierre says: Something went wrong during initialization! Please check the error logs. 🪨');
        }
    }
    
    /**
     * Pierre activates his surveillance system! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function activate(): void {
        try {
            // Pierre initializes his components first! 🪨
            $this->init_components();
            
            // Pierre creates his database tables! 🪨
            $this->create_database_tables();
            
            // Pierre sets up his capabilities! 🪨
            $this->setup_capabilities();
            
            // Pierre schedules his surveillance! 🪨
            $this->schedule_cron_events();
            
            // Pierre flushes rewrite rules! 🪨
            flush_rewrite_rules();
            
            error_log('Pierre has been activated successfully! 🪨');
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error during activation: ' . $e->getMessage() . ' 😢');
            wp_die('Pierre says: Activation failed! Please check the error logs. 🪨');
        }
    }
    
    /**
     * Pierre deactivates his surveillance system! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function deactivate(): void {
        try {
            // Pierre clears his scheduled events! 🪨
            $this->clear_cron_events();
            
            // Pierre flushes rewrite rules! 🪨
            flush_rewrite_rules();
            
            error_log('Pierre has been deactivated! 🪨');
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error during deactivation: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre uninstalls his surveillance system! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function uninstall(): void {
        try {
            // Pierre removes his database tables! 🪨
            $this->remove_database_tables();
            
            // Pierre removes his options! 🪨
            $this->remove_options();
            
            error_log('Pierre has been completely uninstalled! 🪨');
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error during uninstall: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre loads his text domain for translations! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function load_textdomain(): void {
        load_plugin_textdomain(
            'wp-pierre',
            false,
            dirname(PIERRE_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Pierre initializes all his components! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function init_components(): void {
        // Pierre creates his surveillance components! 🪨
        $this->slack_notifier = new SlackNotifier();
        $this->cron_manager = new CronManager();
        $this->project_watcher = new ProjectWatcher();
        
        // Pierre creates his team management components! 🪨
        $this->role_manager = new RoleManager();
        $this->team_repository = new TeamRepository();
        
        // Pierre creates his interface components! 🪨
        $this->admin_controller = new AdminController();
        $this->frontend_controller = new DashboardController();
    }
    
    /**
     * Pierre sets up all his WordPress hooks! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function init_hooks(): void {
        // Pierre hooks into WordPress actions! 🪨
        add_action('init', [$this, 'init_public_hooks']);
        add_action('admin_init', [$this, 'init_admin_hooks']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Pierre initializes his public hooks! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function init_public_hooks(): void {
        // Pierre sets up his public routing! 🪨
        $this->frontend_controller->init();
    }
    
    /**
     * Pierre initializes his admin hooks! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function init_admin_hooks(): void {
        // Pierre sets up his admin interface! 🪨
        $this->admin_controller->init();
    }
    
    /**
     * Pierre enqueues his public scripts and styles! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function enqueue_public_scripts(): void {
        wp_enqueue_style(
            'pierre-public',
            PIERRE_PLUGIN_URL . 'assets/css/public.css',
            [],
            PIERRE_VERSION
        );
        
        wp_enqueue_script(
            'pierre-public',
            PIERRE_PLUGIN_URL . 'assets/js/public.js',
            ['jquery'],
            PIERRE_VERSION,
            true
        );
    }
    
    /**
     * Pierre enqueues his admin scripts and styles! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function enqueue_admin_scripts(): void {
        wp_enqueue_style(
            'pierre-admin',
            PIERRE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            PIERRE_VERSION
        );
        
        wp_enqueue_script(
            'pierre-admin',
            PIERRE_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            PIERRE_VERSION,
            true
        );
    }
    
    /**
     * Pierre creates his database tables! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function create_database_tables(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpupdates_user_projects';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            project_type ENUM('plugin','theme','meta','app') NOT NULL,
            project_slug VARCHAR(200) NOT NULL,
            locale_code VARCHAR(10) NOT NULL,
            role ENUM('locale_manager','gte','pte','contributor','validator') NOT NULL,
            assigned_by BIGINT NOT NULL,
            assigned_at DATETIME NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            KEY user_id (user_id),
            KEY project_slug (project_slug),
            KEY locale_code (locale_code)
        ) {$charset_collate};";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        error_log('Pierre created his database tables! 🪨');
    }
    
    /**
     * Pierre sets up his capabilities! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function setup_capabilities(): void {
        $this->role_manager->add_capabilities();
        error_log('Pierre set up his capabilities! 🪨');
    }
    
    /**
     * Pierre schedules his surveillance events! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function schedule_cron_events(): void {
        $this->cron_manager->schedule_events();
        error_log('Pierre scheduled his surveillance events! 🪨');
    }
    
    /**
     * Pierre clears his scheduled events! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function clear_cron_events(): void {
        $this->cron_manager->clear_events();
        error_log('Pierre cleared his scheduled events! 🪨');
    }
    
    /**
     * Pierre removes his database tables! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function remove_database_tables(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wpupdates_user_projects';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        error_log('Pierre removed his database tables! 🪨');
    }
    
    /**
     * Pierre removes his options! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function remove_options(): void {
        delete_option('pierre_settings');
        delete_option('pierre_version');
        
        error_log('Pierre removed his options! 🪨');
    }
    
    /**
     * Pierre gets his cron manager! 🪨
     * 
     * @since 1.0.0
     * @return CronManager
     */
    public function get_cron_manager(): CronManager {
        return $this->cron_manager;
    }
    
    /**
     * Pierre gets his project watcher! 🪨
     * 
     * @since 1.0.0
     * @return ProjectWatcher
     */
    public function get_project_watcher(): ProjectWatcher {
        return $this->project_watcher;
    }
    
    /**
     * Pierre gets his Slack notifier! 🪨
     * 
     * @since 1.0.0
     * @return SlackNotifier
     */
    public function get_slack_notifier(): SlackNotifier {
        return $this->slack_notifier;
    }
}