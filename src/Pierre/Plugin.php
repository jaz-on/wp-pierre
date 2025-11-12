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
use Pierre\Settings\Settings;
use Pierre\Container;
use function __;
use function admin_url;
use function wp_create_nonce;

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
     * Pierre's dependency injection container - he manages dependencies! 🪨
     * 
     * @var Container
     */
    private Container $container;
    
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
        $this->log_debug('Pierre is being constructed... 🪨');
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
            
            // Pierre initializes his components! 🪨
            $this->init_components();
            
            // Register cron schedules as early as possible (before any reschedule)
            if (isset($this->cron_manager)) {
                $this->cron_manager->register_schedules();
            }

            // Pierre sets up his hooks! 🪨
            $this->init_hooks();
            
            $this->initialized = true;
            $this->log_debug('Pierre is fully initialized and ready to work! 🪨');
            
        } catch (\Exception $e) {
            $this->log_debug('Pierre encountered an error during initialization: ' . $e->getMessage() . ' 😢');
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
            // Auto-start surveillance immediately if configured (default: ON if unset) 🪨
            $settings = Settings::all();
            if (!array_key_exists('auto_start_surveillance', $settings) || !empty($settings['auto_start_surveillance'])) {
                $this->project_watcher->start_surveillance();
            }
            
            // Pierre flushes rewrite rules! 🪨
            flush_rewrite_rules();
            
            $this->log_debug('Pierre has been activated successfully! 🪨');
            
        } catch (\Exception $e) {
            $this->log_debug('Pierre encountered an error during activation: ' . $e->getMessage() . ' 😢');
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
            
            $this->log_debug('Pierre has been deactivated! 🪨');
            
        } catch (\Exception $e) {
            $this->log_debug('Pierre encountered an error during deactivation: ' . $e->getMessage() . ' 😢');
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
            
            $this->log_debug('Pierre has been completely uninstalled! 🪨');
            
        } catch (\Exception $e) {
            $this->log_debug('Pierre encountered an error during uninstall: ' . $e->getMessage() . ' 😢');
        }
    }
      
    /**
     * Pierre initializes all his components! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function init_components(): void {
        // Pierre creates his dependency injection container first! 🪨
        $this->container = new Container();
        
        // Pierre creates his surveillance components! 🪨
        $this->slack_notifier = new SlackNotifier();
        $this->project_watcher = new ProjectWatcher();
        $this->cron_manager = new CronManager($this->project_watcher);
        
        // Pierre creates his team management components! 🪨
        $this->role_manager = new RoleManager();
        $this->team_repository = new TeamRepository();
        
        // Register services in container for dependency injection! 🪨
        $this->container->set(SlackNotifier::class, $this->slack_notifier);
        $this->container->set(ProjectWatcher::class, $this->project_watcher);
        $this->container->set(CronManager::class, $this->cron_manager);
        $this->container->set(RoleManager::class, $this->role_manager);
        $this->container->set(TeamRepository::class, $this->team_repository);
        
        // Pierre creates his interface components! 🪨
        $this->admin_controller = new AdminController($this->container);
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
        // Only bind public hooks on front (avoid admin-ajax/heartbeat noise)
        if (!is_admin() && !(function_exists('wp_doing_ajax') && wp_doing_ajax())) {
            add_action('init', [$this, 'init_public_hooks']);
        }
        add_action('admin_init', [$this, 'init_admin_hooks']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Centralized debug logger (state-change/throttled). Any component can do: do_action('wp_pierre_debug', 'message', ['scope'=>'X'])
        add_action('wp_pierre_debug', [$this, 'handle_debug'], 10, 2);

        // (debug hook removed)

        // Register admin menus early, before admin_menu fires
        if (isset($this->admin_controller)) {
            add_action('admin_menu', [$this->admin_controller, 'add_admin_menu']);
            add_action('network_admin_menu', [$this->admin_controller, 'add_admin_menu']);
            add_action('user_admin_menu', [$this->admin_controller, 'add_admin_menu']);
            add_action('admin_bar_menu', [$this->admin_controller, 'add_admin_bar_menu'], 100);
        }
    }
    
    /**
     * Pierre initializes his public hooks! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function init_public_hooks(): void {
        // Skip when running in admin or ajax contexts
        if (is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax())) { return; }
        // Pierre sets up his public routing! 🪨
        $this->frontend_controller->init();
    }

    /**
     * Centralized throttled debug handler. Avoids log storms while keeping useful traces.
     *
     * @since 1.0.0
     * @param string $message Debug message to log.
     * @param array  $context Optional context array with keys: scope, action, code.
     * @return void
     */
    public function handle_debug(string $message, array $context = []): void {
        if (!\Pierre\Logging\Logger::is_debug()) { return; }
        // Build a signature from message + important context keys
        $scope = isset($context['scope']) ? (string)$context['scope'] : 'general';
        $sigBase = $message . '|' . $scope;
        if (isset($context['action'])) { $sigBase .= '|a:' . (string)$context['action']; }
        if (isset($context['code'])) { $sigBase .= '|c:' . (string)$context['code']; }
        $sig = 'pierre_log_' . md5($sigBase);
        // Throttle: 60s per unique signature
        if (get_transient($sig)) { return; }
        set_transient($sig, 1, 60);
        // Compose enriched line (include scope and selected context keys)
        $line = '[wp-pierre][' . $scope . '] ' . $message;
        if (isset($context['action'])) { $line .= ' action=' . $context['action']; }
        if (isset($context['code'])) { $line .= ' code=' . (string)$context['code']; }
        error_log($line);
    }
    
    /**
     * Pierre initializes his admin hooks! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
	public function init_admin_hooks(): void {
		// Prevent double-binding within the same request
		$cache_key = 'pierre_admin_booted';
		$cache_group = 'pierre_plugin';
		// Use wp_cache_add() which only sets if key doesn't exist (atomic operation)
		if ( wp_cache_add( $cache_key, true, $cache_group, 0 ) === false ) {
			// Already booted in this request
			return;
		}

		// Register settings with WordPress Settings API
		Settings::register();

		// Pierre sets up his admin interface! 🪨
		// Add capabilities only once (persisted flag); activation already seeds caps
		if (isset($this->role_manager) && !get_option('pierre_caps_initialized')) {
			$this->role_manager->add_capabilities();
			update_option('pierre_caps_initialized', time());
		}
		if (defined('PIERRE_DEBUG') && PIERRE_DEBUG) {
			error_log('PIERRE Plugin::init_admin_hooks fired');
		}
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
    }
    
    /**
     * Pierre enqueues his admin scripts and styles! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function enqueue_admin_scripts(): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || strpos($screen->id, 'pierre') === false) {
            return; // only enqueue on Pierre admin screens
        }
        wp_enqueue_style(
            'pierre-admin',
            PIERRE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            PIERRE_VERSION
        );
        // Enqueue admin interactivity script and localize runtime data
        wp_enqueue_script(
            'pierre-admin',
            PIERRE_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            PIERRE_VERSION,
            true
        );
        wp_localize_script(
            'pierre-admin',
            'pierreAdminL10n',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                // Two nonce contexts exist in handlers; expose both for consistency
                'nonce' => wp_create_nonce('pierre_admin_ajax'),
                'nonceAjax' => wp_create_nonce('pierre_ajax'),
                'dismiss' => __('Dismiss this notice.', 'wp-pierre'),
                'saving' => __('Saving...', 'wp-pierre'),
                'testing' => __('Testing...', 'wp-pierre'),
                'saveSuccess' => __('Settings saved successfully!', 'wp-pierre'),
                'saveError' => __('An error occurred while saving settings.', 'wp-pierre'),
                'testSuccess' => __('Test succeeded!', 'wp-pierre'),
                'testFailed' => __('Test failed!', 'wp-pierre'),
                'testError' => __('An error occurred during test.', 'wp-pierre'),
                'dryRunSuccess' => __('Dry run succeeded. You can now start surveillance.', 'wp-pierre'),
                'dryRunFailed' => __('Dry run failed. Check settings and try again.', 'wp-pierre'),
                'dryRunError' => __('An error occurred during dry run.', 'wp-pierre'),
                'progressIdle' => __('Progress: idle', 'wp-pierre'),
                'progressAborting' => __('Progress: Aborting…', 'wp-pierre'),
                'progressLabel' => __('Progress: %1$s/%2$s', 'wp-pierre'),
            ]
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
        
        $table_name = $wpdb->prefix . 'pierre_user_projects';
        
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
        // If table already exists, skip dbDelta to avoid noisy primary key adjustments
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
        if (!$exists) {
            dbDelta($sql);
        }
        
        $this->log_debug('Pierre created his database tables! 🪨');
    }
    
    /**
     * Pierre sets up his capabilities! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function setup_capabilities(): void {
        $this->role_manager->add_capabilities();
        $this->log_debug('Pierre set up his capabilities! 🪨');
    }
    
    /**
     * Pierre schedules his surveillance events! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function schedule_cron_events(): void {
        $this->cron_manager->schedule_events();
        $this->log_debug('Pierre scheduled his surveillance events! 🪨');
    }
    
    /**
     * Pierre clears his scheduled events! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function clear_cron_events(): void {
        if (isset($this->cron_manager)) {
            $this->cron_manager->clear_events();
            $this->log_debug('Pierre cleared his scheduled events! 🪨');
        }
    }
    
    /**
     * Pierre removes his database tables! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function remove_database_tables(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pierre_user_projects';
        $table_name = esc_sql($table_name);
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        $this->log_debug('Pierre removed his database tables! 🪨');
    }
    
    /**
     * Pierre removes his options! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function remove_options(): void {
        global $wpdb;
        
        // Remove main options
        delete_option('pierre_settings');
        delete_option('pierre_version');
        delete_option('pierre_caps_initialized');
        delete_option('pierre_cache_version');
        delete_option('pierre_last_run_now_surveillance');
        delete_option('pierre_last_run_now_cleanup');
        delete_option('pierre_projects_catalog_meta');
        delete_option('pierre_projects_catalog_errors');
        delete_option('pierre_projects_discovery');
        delete_option('pierre_security_logs');
        
        // Remove options with patterns using LIKE
        $patterns = array(
            $wpdb->esc_like('pierre_last_forced_scan_') . '%',
            $wpdb->esc_like('pierre_catalog_fetch_') . '%',
        );
        
        foreach ( $patterns as $pattern ) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $pattern
                )
            );
        }
        
        // Remove catalog progress transient
        delete_transient('pierre_catalog_progress');
        
        // Remove catalog page options (pierre_projects_catalog_plugin_1, etc.)
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('pierre_projects_catalog_') . '%'
            )
        );
        
        $this->log_debug('Pierre removed his options! 🪨');
    }

    /**
     * Log a debug message if debug is enabled.
     *
     * @since 1.0.0
     * @param string $message Debug message to log.
     * @return void
     */
    private function log_debug(string $message): void {
        \Pierre\Logging\Logger::static_debug($message, ['source' => 'Plugin']);
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
    
    /**
     * Pierre gets his dependency injection container! 🪨
     * 
     * @since 1.0.0
     * @return Container
     */
    public function get_container(): Container {
        return $this->container;
    }

    /**
     * Centralized HTTP defaults (timeout, UA, headers) for all outbound requests.
     *
     * @since 1.0.0
     * @return array HTTP request arguments array with keys: timeout, redirection, user-agent, headers.
     */
    public static function get_http_defaults(): array {
        $settings = Settings::all();
        $timeout = isset($settings['request_timeout']) ? max(3, (int) $settings['request_timeout']) : 30;
        $ua = 'wp-pierre/' . (defined('PIERRE_VERSION') ? PIERRE_VERSION : '1.0.0') . '; ' . home_url('/');
        return [
            'timeout' => $timeout,
            'redirection' => 3,
            'user-agent' => $ua,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];
    }
}

/**
 * Helper function to check if debug is enabled.
 * 
 * @since 1.0.0
 * @return bool True if debug is enabled
 */
function pierre_is_debug(): bool {
	return \Pierre\Logging\Logger::is_debug();
}

/**
 * Helper function to decrypt webhook URLs in templates
 * 
 * @since 1.0.0
 * @param string $encrypted_webhook Encrypted webhook URL
 * @return string Decrypted webhook URL or empty string
 */
function pierre_decrypt_webhook( string $encrypted_webhook ): string {
	if ( empty( $encrypted_webhook ) ) {
		return '';
	}
	$decrypted = \Pierre\Security\Encryption::decrypt( $encrypted_webhook );
	return ( $decrypted !== false ) ? $decrypted : $encrypted_webhook;
}