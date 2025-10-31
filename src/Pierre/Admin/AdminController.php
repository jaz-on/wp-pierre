<?php
/**
 * Pierre's admin controller - he manages the WordPress admin! 🪨
 * 
 * This class handles the WordPress admin interface for Pierre's
 * translation monitoring system with menus, pages, and settings.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Admin;

use Pierre\Teams\UserProjectLink;
use Pierre\Surveillance\ProjectWatcher;
use Pierre\Notifications\SlackNotifier;
use Pierre\Teams\RoleManager;
use Pierre\Security\SecurityManager;
use Pierre\Security\CSRFProtection;
use Pierre\Security\SecurityAuditor;

// Pierre imports WordPress functions! 🪨
use function __;
use function _e;
use function esc_html__;
use function esc_attr__;
use function sprintf;
use function add_action;
use function add_filter;
use function add_menu_page;
use function add_submenu_page;
use function current_user_can;
use function wp_die;
use function admin_url;
use function home_url;
use function get_current_screen;
use function get_users;
use function wp_verify_nonce;
use function wp_send_json_success;
use function wp_send_json_error;
use function absint;
use function sanitize_key;
use function sanitize_url;
use function error_log;
use function wp_create_nonce;

/**
 * Admin Controller class - Pierre's admin interface! 🪨
 * 
 * @since 1.0.0
 */
class AdminController {
    
    /**
     * Pierre's user project link - he manages assignments! 🪨
     * 
     * @var UserProjectLink
     */
    private UserProjectLink $user_project_link;
    
    /**
     * Pierre's project watcher - he monitors projects! 🪨
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
     * Pierre's security manager - he protects everything! 🪨
     * 
     * @var SecurityManager
     */
    private SecurityManager $security_manager;
    
    /**
     * Pierre's CSRF protection - he prevents attacks! 🪨
     * 
     * @var CSRFProtection
     */
    private CSRFProtection $csrf_protection;
    
    /**
     * Pierre's security auditor - he checks security! 🪨
     * 
     * @var SecurityAuditor
     */
    private SecurityAuditor $security_auditor;
    
    /**
     * Pierre's constructor - he prepares his admin interface! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->user_project_link = new UserProjectLink();
        $this->project_watcher = pierre()->get_project_watcher();
        $this->slack_notifier = pierre()->get_slack_notifier();
        $this->role_manager = new RoleManager();
        $this->security_manager = new SecurityManager();
        $this->csrf_protection = new CSRFProtection();
        $this->security_auditor = new SecurityAuditor();
    }
    
    /**
     * Pierre initializes his admin interface! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        try {
            // Menus are now registered early by Plugin::init_hooks()
            
            // Pierre sets up his admin hooks! 🪨
            $this->setup_admin_hooks();
            
            // Pierre sets up his AJAX handlers! 🪨
            $this->setup_admin_ajax_handlers();
        // Register locales refresh action hook (for cron/manual)
        $this->register_locales_refresh_hook();
            
            // Centralized AJAX tracing for all pierre_* admin-ajax actions (start/end), throttled by Plugin::handle_debug
            if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
                $act = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
                if (is_string($act) && strpos($act, 'pierre_') === 0) {
                    do_action('wp_pierre_debug', 'ajax start', ['scope'=>'admin','action'=>$act]);
                    register_shutdown_function(function() use ($act) {
                        $code = function_exists('http_response_code') ? (int) http_response_code() : 200;
                        do_action('wp_pierre_debug', 'ajax end', ['scope'=>'admin','action'=>$act,'code'=>$code]);
                    });
                }
            }
            
            $this->log_debug('Pierre initialized his admin interface! 🪨');
            
        } catch (\Exception $e) {
            $this->log_debug('Pierre encountered an error initializing admin interface: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre sets up his admin menu! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function setup_admin_menu(): void {
        // No-op (registered centrally by Plugin)
    }
    
    /**
     * Pierre adds his admin menu! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu(): void {
        // Debug logs removed
        // Pierre's main menu page! 🪨
        add_menu_page(
            'Pierre Dashboard',
            'Pierre',
            'pierre_view_dashboard',
            'pierre-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-translation',
            30
        );
        
        // Pierre's dashboard submenu! 🪨
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Dashboard',
            'Dashboard',
            'pierre_view_dashboard',
            'pierre-dashboard',
            [$this, 'render_dashboard_page']
        );
        
        // Pierre's locales submenu! (new, second)
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Locales',
            'Locales',
            'pierre_view_dashboard',
            'pierre-locales',
            [$this, 'render_locales_page']
        );
        
        // Pierre's locale view (register under main, then hide from menu)
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Locale',
            '',
            'pierre_view_dashboard',
            'pierre-locale-view',
            [$this, 'render_locale_view_page']
        );
        // Hide the submenu entry to keep it accessible via direct URL only
        remove_submenu_page('pierre-dashboard', 'pierre-locale-view');
        
        // Pierre's projects submenu! (third)
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Projects',
            'Projects',
            'pierre_view_dashboard',
            'pierre-projects',
            [$this, 'render_projects_page']
        );

        // Pierre's teams submenu! (fourth)
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Teams',
            'Teams',
            'pierre_view_dashboard',
            'pierre-teams',
            [$this, 'render_teams_page']
        );

        // Pierre's reports submenu! (fifth)
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Reports',
            'Reports',
            'pierre_view_dashboard',
            'pierre-reports',
            [$this, 'render_reports_page']
        );

        // Settings (last)
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Settings',
            'Settings',
            'pierre_manage_settings',
            'pierre-settings',
            [$this, 'render_settings_page']
        );

        // Settings are only under the Pierre menu for consistency
        // End debug
    }
    
    /**
     * Pierre adds his admin bar menu! 🪨
     * 
     * @since 1.0.0
     * @param \WP_Admin_Bar $wp_admin_bar The admin bar object
     * @return void
     */
    public function add_admin_bar_menu(\WP_Admin_Bar $wp_admin_bar): void {
        if (!current_user_can('pierre_view_dashboard')) {
            return;
        }
        
        // Pierre's main admin bar menu! 🪨
        $wp_admin_bar->add_node([
            'id' => 'pierre-admin',
            'title' => 'Pierre 🪨',
            'href' => admin_url('admin.php?page=pierre-dashboard'),
            'meta' => [
                'title' => 'Pierre Dashboard'
            ]
        ]);
        
        // Pierre's dashboard link! 🪨
        $wp_admin_bar->add_node([
            'id' => 'pierre-dashboard',
            'parent' => 'pierre-admin',
            'title' => 'Dashboard',
            'href' => admin_url('admin.php?page=pierre-dashboard'),
            'meta' => [
                'title' => 'Pierre Dashboard'
            ]
        ]);
        
        // Pierre's public dashboard link! 🪨
        $wp_admin_bar->add_node([
            'id' => 'pierre-public',
            'parent' => 'pierre-admin',
            'title' => 'Public Dashboard',
            'href' => home_url('/pierre/'),
            'meta' => [
                'title' => 'Pierre Public Dashboard',
                'target' => '_blank'
            ]
        ]);
    }
    
    /**
     * Pierre sets up his admin hooks! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function setup_admin_hooks(): void {
        // Pierre handles admin notices! 🪨
        add_action('admin_notices', [$this, 'show_admin_notices']);
        
        // Pierre handles admin footer! 🪨
        add_filter('admin_footer_text', [$this, 'modify_admin_footer']);
        
        // Pierre enqueues his admin scripts! 🪨
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Pierre adds contextual help tabs on his screens! 🪨
        add_action('current_screen', [$this, 'register_help_tabs']);
    }

    /**
     * Whether verbose debug logging is enabled.
     */
    private function is_debug(): bool {
        return defined('PIERRE_DEBUG') ? (bool) PIERRE_DEBUG : false;
    }

    /**
     * Log a debug message if debug is enabled.
     */
    private function log_debug(string $message): void {
        if ($this->is_debug()) {
            do_action('wp_pierre_debug', $message, ['source' => 'AdminController']);
        }
    }

    /**
     * Pierre registers contextual help tabs for his admin pages! 🪨
     *
     * @since 1.0.0
     * @return void
     */
    public function register_help_tabs(): void {
        $screen = get_current_screen();
        if (!$screen || !current_user_can('manage_options')) { return; }
        $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if (!in_array($current_page, ['pierre-dashboard','pierre-locales','pierre-locale-view','pierre-projects','pierre-teams','pierre-reports','pierre-settings'], true)) {
            return;
        }
        // Overview help
        $screen->add_help_tab([
            'id'      => 'pierre_overview',
            'title'   => __('Overview', 'wp-pierre'),
            'content' => '<p>' . esc_html__("Pierre monitors locales/projects and notifies via Slack. Use Settings for global rules and Locale view for overrides.", 'wp-pierre') . '</p>',
        ]);
        // Best practices help
        $screen->add_help_tab([
            'id'      => 'pierre_best_practices',
            'title'   => __('Best practices', 'wp-pierre'),
            'content' => '<ul><li>' . esc_html__("Start by adding locales, then add projects.", 'wp-pierre') . '</li><li>' . esc_html__("Prefer digest mode to reduce noise.", 'wp-pierre') . '</li></ul>',
        ]);
        // Sidebar resources
        $screen->set_help_sidebar(
            '<p><strong>' . esc_html__('Resources', 'wp-pierre') . '</strong></p>' .
            '<p><a href="' . esc_url(admin_url('admin.php?page=pierre-settings')) . '">' . esc_html__('Settings', 'wp-pierre') . '</a></p>' .
            '<p><a href="https://translate.wordpress.org/" target="_blank" rel="noopener">translate.wordpress.org</a></p>'
        );
    }
    
    /**
     * Pierre enqueues his admin scripts! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function enqueue_admin_scripts(): void {
        $screen = get_current_screen();
        $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $is_pierre_page = (
            ($screen && strpos($screen->id ?? '', 'pierre') !== false)
            || in_array($current_page, [
                'pierre-projects',
                'pierre-settings',
                'pierre-teams',
                'pierre-dashboard',
                'pierre-reports',
                'pierre-locales',
                'pierre-locale-view'
            ], true)
        );
        if (!$is_pierre_page) { return; }
        
        wp_enqueue_script(
            'wp-pierre-admin',
            PIERRE_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            PIERRE_VERSION,
            true
        );
        
        wp_localize_script('wp-pierre-admin', 'pierreAdminL10n', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pierre_admin_ajax'),
            'nonceAjax' => wp_create_nonce('pierre_ajax'), // For handlers using pierre_ajax
            'dryRunSuccess' => __('Dry run succeeded. You can now start surveillance.', 'wp-pierre'),
            'dryRunFailed' => __('Dry run failed. Check settings and try again.', 'wp-pierre'),
            'dryRunError' => __('An error occurred during dry run.', 'wp-pierre'),
        ]);
    }
    
    /**
     * Pierre sets up his admin AJAX handlers! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function setup_admin_ajax_handlers(): void {
        // Pierre handles admin AJAX requests! 🪨
        add_action('wp_ajax_pierre_admin_get_stats', [$this, 'ajax_get_admin_stats']);
        add_action('wp_ajax_pierre_admin_assign_user', [$this, 'ajax_assign_user']);
        add_action('wp_ajax_pierre_admin_remove_user', [$this, 'ajax_remove_user']);
        add_action('wp_ajax_pierre_admin_test_notification', [$this, 'ajax_test_notification']);
        add_action('wp_ajax_pierre_admin_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_pierre_run_surveillance_now', [$this, 'ajax_run_surveillance_now']);
        add_action('wp_ajax_pierre_save_locale_overrides', [$this, 'ajax_save_locale_overrides']);
        
        // Pierre handles project management AJAX! 🪨
        add_action('wp_ajax_pierre_start_surveillance', [$this, 'ajax_start_surveillance']);
        add_action('wp_ajax_pierre_stop_surveillance', [$this, 'ajax_stop_surveillance']);
        add_action('wp_ajax_pierre_test_surveillance', [$this, 'ajax_test_surveillance']);
        add_action('wp_ajax_pierre_add_project', [$this, 'ajax_add_project']);
        add_action('wp_ajax_pierre_save_locale_slack', [$this, 'ajax_save_locale_slack']);
        add_action('wp_ajax_pierre_save_locale_webhook', [$this, 'ajax_save_locale_webhook']);
        add_action('wp_ajax_pierre_remove_project', [$this, 'ajax_remove_project']);
        
        // Pierre handles locales AJAX! 🪨
        add_action('wp_ajax_pierre_add_locales', [$this, 'ajax_add_locales']);
        add_action('wp_ajax_pierre_fetch_locales', [$this, 'ajax_fetch_locales']);
        add_action('wp_ajax_pierre_save_projects_discovery', [$this, 'ajax_save_projects_discovery']);
        add_action('wp_ajax_pierre_bulk_add_from_discovery', [$this, 'ajax_bulk_add_from_discovery']);
        add_action('wp_ajax_pierre_bulk_preview_from_discovery', [$this, 'ajax_bulk_preview_from_discovery']);
        
        // Pierre handles settings AJAX! 🪨
        add_action('wp_ajax_pierre_flush_cache', [$this, 'ajax_flush_cache']);
        add_action('wp_ajax_pierre_reset_settings', [$this, 'ajax_reset_settings']);
        add_action('wp_ajax_pierre_clear_data', [$this, 'ajax_clear_data']);
        
        // Pierre handles reports AJAX! 🪨
        add_action('wp_ajax_pierre_export_report', [$this, 'ajax_export_report']);
        add_action('wp_ajax_pierre_export_all_reports', [$this, 'ajax_export_all_reports']);
        add_action('wp_ajax_pierre_schedule_reports', [$this, 'ajax_schedule_reports']);
        // Run now actions
        add_action('wp_ajax_pierre_run_surveillance_now', [$this, 'ajax_run_surveillance_now']);
        add_action('wp_ajax_pierre_run_cleanup_now', [$this, 'ajax_run_cleanup_now']);
        // Locales cache exports
        add_action('wp_ajax_pierre_export_locales_json', [$this, 'ajax_export_locales_json']);
        add_action('wp_ajax_pierre_export_locales_csv', [$this, 'ajax_export_locales_csv']);
        add_action('wp_ajax_pierre_check_locale_status', [$this, 'ajax_check_locale_status']);
        add_action('wp_ajax_pierre_clear_locale_log', [$this, 'ajax_clear_locale_log']);
        add_action('wp_ajax_pierre_export_locale_log', [$this, 'ajax_export_locale_log']);
        add_action('wp_ajax_pierre_abort_run', [$this, 'ajax_abort_run']);
        add_action('wp_ajax_pierre_get_progress', [$this, 'ajax_get_progress']);
        // Progress + abort controls
        add_action('wp_ajax_pierre_abort_surveillance_run', [$this, 'ajax_abort_surveillance_run']);
        

        // Pierre handles locale managers (admin-only) 🪨
        add_action('wp_ajax_pierre_save_locale_managers', [$this, 'ajax_save_locale_managers']);
    }

    /**
     * Uniform JSON error response for AJAX handlers
     */
    private function respond_error(string $code, string $message, int $status = 403, $details = null): void {
        // Map all admin errors to centralized debug logger (throttled at handler level)
        do_action('wp_pierre_debug', 'admin error', ['scope'=>'admin','action'=>$code,'code'=>$status]);
        $data = ['code' => $code, 'message' => $message];
        if ($details !== null) { $data['details'] = $details; }
        wp_send_json_error($data, $status);
    }

    /** Abort current run (flag checked by cron/tasks) */
    public function ajax_abort_run(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce', __('Invalid nonce.', 'wp-pierre')); }
        if (!current_user_can('pierre_manage_projects')) { $this->respond_error('forbidden', __('Permission denied.', 'wp-pierre')); }
        update_option('pierre_abort_run', time(), false);
        do_action('wp_pierre_debug', 'abort requested', ['scope'=>'cron','action'=>'abort']);
        wp_send_json_success(['message' => __('Abort requested. Ongoing run will stop shortly.', 'wp-pierre')]);
    }

    /** Get current progress of surveillance (processed/total) */
    public function ajax_get_progress(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce', __('Invalid nonce.', 'wp-pierre')); }
        if (!current_user_can('pierre_manage_projects')) { $this->respond_error('forbidden', __('Permission denied.', 'wp-pierre')); }
        $progress = get_transient('pierre_surv_progress');
        $abort = (int) get_option('pierre_abort_run', 0) ? true : false;
        if (!is_array($progress)) { $progress = ['processed'=>0,'total'=>0,'ts'=>0]; }
        $dur = (int) get_option('pierre_last_surv_duration_ms', 0);
        wp_send_json_success(['progress'=>$progress,'aborting'=>$abort,'duration_ms'=>$dur]);
    }

    /** Abort current surveillance run */
    public function ajax_abort_surveillance_run(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce', __('Invalid nonce', 'wp-pierre'), 403); }
        if (!current_user_can('pierre_manage_settings')) { $this->respond_error('forbidden', __('Permission denied.', 'wp-pierre'), 403); }
        set_transient('pierre_surv_abort', 1, 5 * MINUTE_IN_SECONDS);
        wp_send_json_success(['message' => __('Abort signal set.', 'wp-pierre')]);
    }

    

    /** Export locales cache as JSON */
    public function ajax_export_locales_json(): void {
        if (!current_user_can('pierre_manage_reports')) { $this->respond_error('forbidden', __('Permission denied', 'wp-pierre'), 403); }
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce', __('Invalid nonce', 'wp-pierre'), 403); }
        $cache = get_option('pierre_locales_cache');
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="pierre_locales_cache.json"');
        echo wp_json_encode($cache, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Export locales cache as CSV */
    public function ajax_export_locales_csv(): void {
        if (!current_user_can('pierre_manage_reports')) { $this->respond_error('forbidden', __('Permission denied', 'wp-pierre'), 403); }
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce', __('Invalid nonce', 'wp-pierre'), 403); }
        $cache = get_option('pierre_locales_cache');
        $rows = is_array($cache) && !empty($cache['data']) ? (array)$cache['data'] : [];
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pierre_locales_cache.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['code','label','slug','translate_slug','team_locale','rosetta','slack_url']);
        foreach ($rows as $r) {
            fputcsv($out, [
                (string)($r['code']??''),(string)($r['label']??''),(string)($r['slug']??''),(string)($r['translate_slug']??''),(string)($r['team_locale']??''),(string)($r['rosetta']??''),(string)($r['slack_url']??'')
            ]);
        }
        fclose($out);
        exit;
    }
    
    /**
     * Pierre renders his dashboard page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_dashboard_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die(esc_html__('Pierre says: You don\'t have permission to view this page!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre gets his dashboard data! 🪨
        $dashboard_data = $this->get_admin_dashboard_data();
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('dashboard', $dashboard_data);
    }
    
    /**
     * Pierre renders his teams page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_teams_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die(esc_html__('Pierre says: You don\'t have permission to view this page!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre gets his teams data! 🪨
        $teams_data = $this->get_admin_teams_data();
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('teams', $teams_data);
    }
    
    /**
     * Pierre renders his locales page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_locales_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die(esc_html__('Pierre says: You don\'t have permission to view this page!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre gets his locales data! 🪨
        $locales_data = $this->get_admin_locales_data();
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('locales', $locales_data);
    }
    
    /**
     * Pierre renders his locale view page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_locale_view_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die(esc_html__('Pierre says: You don\'t have permission to view this page!', 'wp-pierre') . ' 😢');
        }
        
        // Get locale from URL
        $locale_code = sanitize_key(wp_unslash($_GET['locale'] ?? ''));
        if (empty($locale_code)) {
            wp_die(esc_html__('Pierre says: Locale parameter is required!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre gets his locale view data! 🪨
        $locale_view_data = $this->get_admin_locale_view_data($locale_code);
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('locale-view', $locale_view_data);
    }
    
    /**
     * Pierre renders his projects page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_projects_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die(esc_html__('Pierre says: You don\'t have permission to view this page!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre gets his projects data! 🪨
        $projects_data = $this->get_admin_projects_data();
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('projects', $projects_data);
    }
    
    /**
     * Pierre renders his settings page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_settings')) {
            wp_die(esc_html__('Pierre says: You don\'t have permission to view this page!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre gets his settings data! 🪨
        $settings_data = $this->get_admin_settings_data();

        // Capture templates output to display in tabs
        ob_start();
        $this->render_admin_template('settings', $settings_data);
        $settings_html = ob_get_clean();

        ob_start();
        $this->render_admin_template('settings-global-webhook', $settings_data);
        $global_webhook_html = ob_get_clean();

        ob_start();
        $this->render_admin_template('settings-discovery', $settings_data);
        $discovery_html = ob_get_clean();

        ob_start();
        $this->render_admin_template('settings-projects-discovery', $settings_data);
        $projects_discovery_html = ob_get_clean();

        // Output simple admin tabs (native look)
        echo '<div class="wrap pierre-settings">';
        echo '<h1>Pierre 🪨 Settings</h1>';
        echo '<h2 class="nav-tab-wrapper">'
            . '<a href="#general" class="nav-tab nav-tab-active">' . esc_html__('General', 'wp-pierre') . '</a>'
            . '<a href="#discovery" class="nav-tab">' . esc_html__('Locales Discovery', 'wp-pierre') . '</a>'
            . '<a href="#projects-discovery" class="nav-tab">' . esc_html__('Projects Discovery', 'wp-pierre') . '</a>'
            . '<a href="#global-webhook" class="nav-tab">' . esc_html__('Global Webhook', 'wp-pierre') . '</a>'
            . '</h2>';
        echo '<div id="pierre-tab-general" class="pierre-tab-section">' . $settings_html . '</div>';
        echo '<div id="pierre-tab-discovery" class="pierre-tab-section" style="display:none;">' . $discovery_html . '</div>';
        echo '<div id="pierre-tab-projects-discovery" class="pierre-tab-section" style="display:none;">' . $projects_discovery_html . '</div>';
        echo '<div id="pierre-tab-global-webhook" class="pierre-tab-section" style="display:none;">' . $global_webhook_html . '</div>';
        echo '</div>';

        ?>
        <script>
        (function(){
            const tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
            const sections = {
                '#general': document.getElementById('pierre-tab-general'),
                '#global-webhook': document.getElementById('pierre-tab-global-webhook'),
                '#discovery': document.getElementById('pierre-tab-discovery'),
                '#projects-discovery': document.getElementById('pierre-tab-projects-discovery')
            };
            function activate(hash){
                tabs.forEach(t=>t.classList.remove('nav-tab-active'));
                for (const key in sections){ 
                    if (sections[key]) sections[key].style.display = 'none';
                }
                const target = hash && sections[hash] ? hash : '#general';
                const targetTab = document.querySelector('.nav-tab[href="'+target+'"]');
                if (targetTab) targetTab.classList.add('nav-tab-active');
                if (sections[target]) sections[target].style.display = 'block';
            }
            tabs.forEach(t=>t.addEventListener('click', function(e){ e.preventDefault(); activate(this.getAttribute('href')); history.replaceState(null,'',this.getAttribute('href')); }));
            activate(location.hash);
        })();
        </script>
        <?php
    }
    
    /**
     * Pierre renders his reports page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_reports_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die(esc_html__('Pierre says: You don\'t have permission to view this page!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre gets his reports data! 🪨
        $reports_data = $this->get_admin_reports_data();
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('reports', $reports_data);
    }
    
    /**
     * Pierre renders his security page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_security_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die(esc_html__('Pierre says: You don\'t have permission to view this page!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre renders his security template! 🪨
        $this->render_admin_template('security', []);
    }
    
    /**
     * Pierre renders an admin template! 🪨
     * 
     * @since 1.0.0
     * @param string $template_name The template name
     * @param array $data The template data
     * @return void
     */
    private function render_admin_template(string $template_name, array $data): void {
        // Pierre sets up his template data! 🪨
        $GLOBALS['pierre_admin_template_data'] = $data;
        
        // Pierre includes his template! 🪨
        $template_path = PIERRE_PLUGIN_DIR . "templates/admin/{$template_name}.php";
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Pierre creates a simple admin template! 🪨
            $this->render_simple_admin_template($template_name, $data);
        }
    }
    
    /**
     * Pierre renders a simple admin template! 🪨
     * 
     * @since 1.0.0
     * @param string $template_name The template name
     * @param array $data The template data
     * @return void
     */
    private function render_simple_admin_template(string $template_name, array $data): void {
        ?>
        <div class="wrap">
            <h1>Pierre 🪨 <?php echo esc_html(ucfirst($template_name)); ?></h1>
            
            <div class="notice notice-info is-dismissible">
                <p><strong>Pierre says:</strong> This is a simple admin template for <?php echo esc_html($template_name); ?>. The full template will be implemented in the next phase.</p>
            </div>
            
            <?php if (isset($data['stats'])): ?>
            <div class="pierre-card">
                <h2>Pierre's Statistics</h2>
                <div class="pierre-grid">
                    <?php foreach ($data['stats'] as $stat): ?>
                    <div class="pierre-stat-box">
                        <div class="pierre-stat-number"><?php echo esc_html($stat['value']); ?></div>
                        <div class="pierre-stat-label"><?php echo esc_html($stat['label']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            
        </div>
        
        <?php
    }
    
    /**
     * Pierre shows admin notices! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function show_admin_notices(): void {
        $screen = get_current_screen();
        
        if (!$screen || strpos($screen->id, 'pierre') === false) {
            return;
        }
        
        // Pierre shows his notices! 🪨
        $notice = get_transient('pierre_admin_notice');
        if ($notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
            delete_transient('pierre_admin_notice');
        }
        
        $error = get_transient('pierre_admin_error');
        if ($error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            delete_transient('pierre_admin_error');
        }
    }
    
    /**
     * Pierre modifies admin footer! 🪨
     * 
     * @since 1.0.0
     * @param string $text The footer text
     * @return string Modified footer text
     */
    public function modify_admin_footer(string $text): string {
        $screen = get_current_screen();
        
        if ($screen && strpos($screen->id, 'pierre') !== false) {
            return 'Pierre says: Thank you for using WordPress Translation Monitor! 🪨';
        }
        
        return $text;
    }
    
    
    /**
     * Pierre gets admin dashboard data! 🪨
     * 
     * @since 1.0.0
     * @return array Admin dashboard data
     */
    private function get_admin_dashboard_data(): array {
        $current_user_id = get_current_user_id();
        
        return [
            'user_id' => $current_user_id,
            'user_name' => wp_get_current_user()->display_name,
            'surveillance_status' => $this->project_watcher->get_surveillance_status(),
            'notifier_status' => $this->slack_notifier->get_status(),
            'role_manager_status' => $this->role_manager->get_status(),
            'user_assignments' => $current_user_id ? $this->user_project_link->get_user_assignments_with_details($current_user_id) : [],
            'watched_projects' => $this->project_watcher->get_watched_projects(),
            'stats' => $this->get_admin_stats()
        ];
    }
    
    /**
     * Pierre gets admin teams data! 🪨
     * 
     * @since 1.0.0
     * @return array Admin teams data
     */
    private function get_admin_teams_data(): array {
        // Get locales with labels for UI
        $translations = function_exists('wp_get_available_translations') ? wp_get_available_translations() : [];
        $locales_labels = [];
        foreach ($translations as $slug => $t) {
            if (!empty($t['language'])) {
                $code = $t['language'];
                $native = $t['native_name'] ?? ($t['english_name'] ?? '');
                $locales_labels[$code] = trim($code . ' — ' . $native);
            }
        }
        $locales = array_keys($locales_labels);
        if (empty($locales)) {
            $locales = ['fr_FR', 'en_US'];
            $locales_labels = ['fr_FR' => 'fr_FR — Français', 'en_US' => 'en_US — English'];
        }
        
        // Get watched projects grouped by locale
        $watched = $this->project_watcher->get_watched_projects();
        $projects_by_locale = [];
        foreach ($watched as $project) {
            $locale = $project['locale_code'] ?? ($project['locale'] ?? '');
            if (!empty($locale)) {
                if (!isset($projects_by_locale[$locale])) {
                    $projects_by_locale[$locale] = [];
                }
                $slug = $project['project_slug'] ?? ($project['slug'] ?? '');
                if (!empty($slug)) {
                    $projects_by_locale[$locale][] = $slug;
                }
            }
        }
        
        // Get all users and mark admins
        $users = get_users(['number' => 50]);
        $users_with_meta = [];
        foreach ($users as $user) {
            $is_admin = user_can($user->ID, 'administrator');
            $users_with_meta[] = [
                'user' => $user,
                'is_admin' => $is_admin,
                'assignments' => $is_admin ? [] : $this->user_project_link->get_user_assignments_with_details($user->ID),
            ];
        }
        
        return [
            'users' => $users_with_meta,
            'roles' => [
                'locale_manager' => __('Locale Manager', 'wp-pierre'),
                'gte' => __('GTE (General Translation Editor)', 'wp-pierre'),
                'pte' => __('PTE (Plugin Translation Editor)', 'wp-pierre'),
                'contributor' => __('Contributor', 'wp-pierre'),
                'validator' => __('Validator', 'wp-pierre'),
            ],
            'capabilities' => $this->role_manager->get_capabilities(),
            'stats' => $this->get_teams_stats(),
            'locales' => $locales,
            'locales_labels' => $locales_labels,
            'projects_by_locale' => $projects_by_locale,
        ];
    }
    
    /**
     * Pierre gets admin locales data! 🪨
     * 
     * @since 1.0.0
     * @return array Admin locales data
     */
    private function get_admin_locales_data(): array {
        // Get all available translations from WP.org
        $translations = function_exists('wp_get_available_translations') ? wp_get_available_translations() : [];
        $all_locales = [];
        $locales_labels = [];
        foreach ($translations as $slug => $t) {
            if (!empty($t['language'])) {
                $code = $t['language'];
                $native = $t['native_name'] ?? ($t['english_name'] ?? '');
                $locales_labels[$code] = trim($code . ' — ' . $native);
                $all_locales[] = $code;
            }
        }
        if (empty($all_locales)) {
            $all_locales = ['fr_FR', 'en_US', 'de_DE', 'es_ES'];
            $locales_labels = [
                'fr_FR' => 'fr_FR — Français',
                'en_US' => 'en_US — English',
                'de_DE' => 'de_DE — Deutsch',
                'es_ES' => 'es_ES — Español'
            ];
        }
        
        // Get active locales (from watched projects)
        $watched = $this->project_watcher->get_watched_projects();
        $active_locales = [];
        foreach ($watched as $project) {
            $locale = $project['locale'] ?? ($project['locale_code'] ?? '');
            if (!empty($locale) && !in_array($locale, $active_locales, true)) {
                $active_locales[] = $locale;
            }
        }
        
        // Get stats per locale
        $locale_stats = [];
        foreach ($active_locales as $locale) {
            $projects_count = 0;
            $last_check = null;
            foreach ($watched as $project) {
                $proj_locale = $project['locale'] ?? ($project['locale_code'] ?? '');
                if ($proj_locale === $locale) {
                    $projects_count++;
                    $checked = $project['last_checked'] ?? null;
                    if ($checked && (!$last_check || $checked > $last_check)) {
                        $last_check = $checked;
                    }
                }
            }
            $locale_stats[$locale] = [
                'projects_count' => $projects_count,
                'last_check' => $last_check ? human_time_diff($last_check, current_time('timestamp')) . ' ago' : __('Never', 'wp-pierre'),
            ];
        }
        
        return [
            'all_locales' => $all_locales,
            'locales_labels' => $locales_labels,
            'active_locales' => $active_locales,
            'locale_stats' => $locale_stats,
        ];
    }
    
    /**
     * Pierre gets admin locale view data! 🪨
     * 
     * @since 1.0.0
     * @param string $locale_code The locale code
     * @return array Admin locale view data
     */
    private function get_admin_locale_view_data(string $locale_code): array {
        // Get locale label
        $translations = function_exists('wp_get_available_translations') ? wp_get_available_translations() : [];
        $locale_label = $locale_code;
        foreach ($translations as $t) {
            if (($t['language'] ?? '') === $locale_code) {
                $native = $t['native_name'] ?? ($t['english_name'] ?? '');
                $locale_label = trim($locale_code . ' — ' . $native);
                break;
            }
        }
        
        // Get projects for this locale
        $watched = $this->project_watcher->get_watched_projects();
        $locale_projects = [];
        foreach ($watched as $project) {
            $proj_locale = $project['locale'] ?? ($project['locale_code'] ?? '');
            if ($proj_locale === $locale_code) {
                $locale_projects[] = $project;
            }
        }
        
        // Get locale-specific Slack webhook
        $settings = get_option('pierre_settings', []);
        // Migrate legacy global webhook to unified model once
        if (!empty($settings['slack_webhook_url']) && empty($settings['global_webhook'])) {
            $settings['global_webhook'] = [
                'enabled' => true,
                'webhook_url' => $settings['slack_webhook_url'],
                'types' => $settings['notification_types'] ?? ['new_strings','completion_update','needs_attention','milestone'],
                'threshold' => (int)($settings['notification_defaults']['new_strings_threshold'] ?? 20),
                'milestones' => (array)($settings['notification_defaults']['milestones'] ?? [50,80,100]),
                'mode' => (string)($settings['notification_defaults']['mode'] ?? 'immediate'),
                'digest' => (array)($settings['notification_defaults']['digest'] ?? ['type'=>'interval','interval_minutes'=>60,'fixed_time'=>'09:00']),
                'scopes' => [ 'locales' => [], 'projects' => [] ],
            ];
            update_option('pierre_settings', $settings);
        }
        $locale_slack = $settings['locales_slack'][$locale_code] ?? '';
        $locale_webhook = (array) ($settings['locales'][$locale_code]['webhook'] ?? []);
        
        // Get all users and current locale managers (admin manages this list)
        $all_users = get_users(['number' => 200]);
        $managers_map = get_option('pierre_locale_managers', []);
        $locale_managers = is_array($managers_map[$locale_code] ?? null) ? $managers_map[$locale_code] : [];
        
        return [
            'locale_code' => $locale_code,
            'locale_label' => $locale_label,
            'projects' => $locale_projects,
            'slack_webhook' => $locale_slack,
            'locale_webhook' => $locale_webhook,
            'all_users' => $all_users,
            'locale_managers' => $locale_managers,
            'stats' => [
                'projects_count' => count($locale_projects),
                'last_check' => !empty($locale_projects) ? __('Recent', 'wp-pierre') : __('Never', 'wp-pierre'),
            ],
        ];
    }

    /**
     * Save locale managers list (admin-only)
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_save_locale_managers(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            do_action('wp_pierre_debug', 'fetch_locales: invalid nonce', ['source' => 'AdminController']);
            wp_send_json_error(['message' => __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢']);
            return;
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢']);
            return;
        }
        $locale_code = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        $user_ids = wp_unslash($_POST['user_ids'] ?? []);
        if (empty($locale_code) || !is_array($user_ids)) {
            wp_send_json_error(['message' => __('Invalid payload.', 'wp-pierre') . ' 😢']);
            return;
        }
        $user_ids = array_values(array_filter(array_map('absint', $user_ids)));
        $map = get_option('pierre_locale_managers', []);
        if (!is_array($map)) { $map = []; }
        $map[$locale_code] = $user_ids;
        update_option('pierre_locale_managers', $map);
        wp_send_json_success(['message' => __('Locale managers saved.', 'wp-pierre') . ' 🪨']);
    }
    
    /**
     * Pierre gets admin projects data! 🪨
     * 
     * @since 1.0.0
     * @return array Admin projects data
     */
    private function get_admin_projects_data(): array {
        // Extraire locales WordPress si dispo
		$translations = function_exists('wp_get_available_translations') ? wp_get_available_translations() : [];
		$locales = array_values(array_unique(array_filter(array_map(
			function($t){ return isset($t['language']) ? $t['language'] : null; },
			$translations
		))));
		$labels = [];
		foreach ($translations as $slug => $t) {
			if (!empty($t['language'])) {
				$code = $t['language'];
				$native = $t['native_name'] ?? ($t['english_name'] ?? '');
				$labels[$code] = trim($code . ' — ' . $native);
			}
		}
		$settings = get_option('pierre_settings', []);
		$locales_slack = isset($settings['locales_slack']) && is_array($settings['locales_slack']) ? $settings['locales_slack'] : [];

        return [
            'watched_projects' => $this->project_watcher->get_watched_projects(),
            'surveillance_status' => $this->project_watcher->get_surveillance_status(),
            'stats' => $this->get_projects_stats(),
			'locales' => !empty($locales) ? $locales : ['fr_FR','en_US'],
			'locales_labels' => $labels,
			'locales_slack' => $locales_slack,
			'notifier_status' => $this->slack_notifier->get_status(),
			'cron_status' => pierre()->get_cron_manager()->get_surveillance_status(),
        ];
    }

	/**
	 * Add locales to monitoring via AJAX
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_add_locales(): void {
		if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
			$this->respond_error('invalid_nonce', __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
			return;
		}
		if (!current_user_can('manage_options')) {
			$this->respond_error('forbidden', __('Pierre says: You don\'t have permission! Only site administrators can add locales.', 'wp-pierre') . ' 😢');
			return;
		}
		
		$locales = wp_unslash($_POST['locales'] ?? []);
		if (!is_array($locales) || empty($locales)) {
			$this->respond_error('invalid_payload', __('No locales selected.', 'wp-pierre') . ' 😢', 400);
			return;
		}
		
		$added = [];
		$errors = [];
		
		foreach ($locales as $locale_code) {
			// Ne pas utiliser sanitize_key() (qui force en minuscules et casse fr_FR).
			$raw = (string) wp_unslash($locale_code);
			$raw = trim($raw);
			// Validation tolérante: langue en minuscule, région insensible à la casse
			if ($raw === '' || !preg_match('/^[a-z]{2}(?:_[a-zA-Z]{2})?$/', $raw)) {
				// translators: %s is the invalid locale code
				$errors[] = sprintf(__('Invalid locale code: %s', 'wp-pierre'), $raw);
				continue;
			}
			// Normaliser en forme canonique WP (ex: fr_FR, pt_BR, en_US, ou fr)
			$locale_code = preg_replace_callback(
				'/^([a-z]{2})(?:_([a-zA-Z]{2}))?$/',
				static function ($m) {
					return isset($m[2]) ? strtolower($m[1]) . '_' . strtoupper($m[2]) : strtolower($m[1]);
				},
				$raw
			);
			
			// Validate locale exists using cached list or fallback fetch (works even if
			// wp_get_available_translations() is not loaded in admin-ajax context)
			$cache = get_option('pierre_locales_cache');
			$known = [];
			if (is_array($cache) && !empty($cache['data'])) {
				foreach ($cache['data'] as $row) {
					$code = isset($row['code']) ? (string) $row['code'] : '';
					if ($code !== '') { $known[$code] = true; }
				}
			} else {
				// Fall back to building a fresh list via helper (includes API fallback)
				$base_list = $this->fetch_base_locales_list();
				foreach ($base_list as $row) {
					$code = isset($row['code']) ? (string) $row['code'] : '';
					if ($code !== '') { $known[$code] = true; }
				}
			}
			$locale_exists = isset($known[$locale_code]);
			
			if (!$locale_exists) {
                // translators: %s is the locale code
                $errors[] = sprintf(__('Locale %s not found in WordPress.org translations.', 'wp-pierre'), $locale_code);
				continue;
			}
			
			// Check if already active (via watched projects)
			$watched = $this->project_watcher->get_watched_projects();
			$is_active = false;
			foreach ($watched as $project) {
				$proj_locale = $project['locale'] ?? ($project['locale_code'] ?? '');
				if ($proj_locale === $locale_code) {
					$is_active = true;
					break;
				}
			}
			
			if ($is_active) {
				continue; // Already active
			}
			
			// Locale is valid and not yet active - it will become active when first project is added
			// For now, we just mark it as "ready to use"
			$added[] = $locale_code;
		}
		
		if (!empty($errors)) {
			$this->respond_error('partial_failure', __('Some locales could not be added.', 'wp-pierre'), 400, ['errors' => $errors, 'added' => $added]);
			return;
		}
		
		if (empty($added)) {
			$this->respond_error('no_changes', __('No new locales to add (they may already be active).', 'wp-pierre'), 400);
			return;
		}
		
        // Persist selection so Discovery can show them as active candidates
        $selected = get_option('pierre_selected_locales', []);
        if (!is_array($selected)) { $selected = []; }
        $selected = array_values(array_unique(array_merge($selected, $added)));
        update_option('pierre_selected_locales', $selected);

        wp_send_json_success([
            // translators: %s is a comma-separated list of locales
            'message' => sprintf(__('Locales added: %s', 'wp-pierre'), implode(', ', $added)),
			'added' => $added,
		]);
	}

	/**
	 * Save per-locale Slack webhook via AJAX
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_save_locale_slack(): void {
		if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
			$this->respond_error('invalid_nonce', __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
			return;
		}
		if (!current_user_can('manage_options')) {
			$this->respond_error('forbidden', __('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
			return;
		}
		$locale_code = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
		$webhook = trim((string) wp_unslash($_POST['slack_webhook_url'] ?? ''));
		if (empty($locale_code)) {
			$this->respond_error('missing_locale', __('Locale code is required.', 'wp-pierre') . ' 😢', 400);
			return;
		}
		$settings = get_option('pierre_settings', []);
		$map = isset($settings['locales_slack']) && is_array($settings['locales_slack']) ? $settings['locales_slack'] : [];
		if ($webhook === '') {
			unset($map[$locale_code]);
		} else {
			if (!filter_var($webhook, FILTER_VALIDATE_URL) || strpos($webhook, 'hooks.slack.com') === false) {
				$this->respond_error('invalid_webhook', __('Invalid Slack webhook URL.', 'wp-pierre') . ' 😢', 400);
				return;
			}
			$map[$locale_code] = esc_url_raw($webhook);
		}
		$settings['locales_slack'] = $map;
		update_option('pierre_settings', $settings);
		wp_send_json_success(['message' => __('Locale Slack webhook saved.', 'wp-pierre') . ' 🪨']);
	}
    
    /**
     * Pierre gets admin settings data! 🪨
     * 
     * @since 1.0.0
     * @return array Admin settings data
     */
    private function get_admin_settings_data(): array {
        $settings = get_option('pierre_settings', []);
        $projects_discovery = get_option('pierre_projects_discovery', []);
        
        // Get active or selected locales for Discovery
        $watched = $this->project_watcher->get_watched_projects();
        $active_locales = [];
        foreach ($watched as $project) {
            $locale = $project['locale'] ?? ($project['locale_code'] ?? '');
            if (!empty($locale) && !in_array($locale, $active_locales, true)) {
                $active_locales[] = $locale;
            }
        }
        // Include previously selected locales (added via Discovery, even if no project yet)
        $selected_locales = get_option('pierre_selected_locales', []);
        if (is_array($selected_locales)) {
            foreach ($selected_locales as $loc) {
                if (!empty($loc) && !in_array($loc, $active_locales, true)) {
                    $active_locales[] = $loc;
                }
            }
        }
        
        return [
            'settings' => $settings,
            'notifier_status' => $this->slack_notifier->get_status(),
            'cron_status' => pierre()->get_cron_manager()->get_surveillance_status(),
            'active_locales' => $active_locales,
            'projects_discovery' => $projects_discovery,
        ];
    }

    /**
     * Save unified locale webhook configuration
     */
    public function ajax_save_locale_webhook(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            $this->respond_error('invalid_nonce', __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        $user_id = get_current_user_id();
        if (!$this->role_manager->user_can_manage_locale_settings($user_id, sanitize_key(wp_unslash($_POST['locale_code'] ?? '')))) {
            $this->respond_error('forbidden', __('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        $locale = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        if ($locale === '') {
            $this->respond_error('missing_locale', __('Locale code is required.', 'wp-pierre') . ' 😢', 400);
        }
        $settings = get_option('pierre_settings', []);
        if (!is_array($settings)) { $settings = []; }
        if (!isset($settings['locales']) || !is_array($settings['locales'])) { $settings['locales'] = []; }

        $lw = (array)($settings['locales'][$locale]['webhook'] ?? []);
        $lw['enabled'] = !empty(wp_unslash($_POST['locale_webhook_enabled'] ?? ($lw['enabled'] ?? false)));
        $lw['webhook_url'] = sanitize_url(wp_unslash($_POST['locale_webhook_url'] ?? ($lw['webhook_url'] ?? '')));
        $lw['types'] = isset($_POST['locale_webhook_types']) && is_array($_POST['locale_webhook_types'])
            ? array_values(array_intersect(array_map('sanitize_key', wp_unslash($_POST['locale_webhook_types'])), ['new_strings','completion_update','needs_attention','milestone']))
            : (array)($lw['types'] ?? ['new_strings','completion_update','needs_attention','milestone']);
        $th = wp_unslash($_POST['locale_webhook_threshold'] ?? '');
        if ($th !== '') { $lw['threshold'] = absint($th); } else { unset($lw['threshold']); }
        $mil_raw = (string) wp_unslash($_POST['locale_webhook_milestones'] ?? '');
        if ($mil_raw !== '') {
            $lw['milestones'] = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $mil_raw)))));
            sort($lw['milestones']);
        } else { unset($lw['milestones']); }
        $mode = sanitize_key(wp_unslash($_POST['locale_webhook_mode'] ?? ''));
        if (in_array($mode, ['immediate','digest'], true)) { $lw['mode'] = $mode; } else { unset($lw['mode']); }
        $lw['digest'] = $lw['digest'] ?? [];
        $dt = sanitize_key(wp_unslash($_POST['locale_webhook_digest_type'] ?? ''));
        if (in_array($dt, ['interval','fixed_time'], true)) { $lw['digest']['type'] = $dt; } else { unset($lw['digest']['type']); }
        $di = wp_unslash($_POST['locale_webhook_digest_interval_minutes'] ?? '');
        if ($di !== '') { $lw['digest']['interval_minutes'] = max(15, absint($di)); } else { unset($lw['digest']['interval_minutes']); }
        $df = (string) wp_unslash($_POST['locale_webhook_digest_fixed_time'] ?? '');
        if ($df !== '') { $lw['digest']['fixed_time'] = preg_replace('/[^0-9:]/', '', $df); } else { unset($lw['digest']['fixed_time']); }

        $settings['locales'][$locale]['webhook'] = $lw;
        update_option('pierre_settings', $settings);
        wp_send_json_success(['message' => __('Locale webhook saved.', 'wp-pierre') . ' 🪨']);
    }

    /** Save projects discovery library */
    public function ajax_save_projects_discovery(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            $this->respond_error('invalid_nonce', __('Pierre says: Invalid nonce!', 'wp-pierre'));
        }
        if (!current_user_can('manage_options')) {
            $this->respond_error('forbidden', __('Pierre says: You don\'t have permission!', 'wp-pierre'));
        }
        $raw = (string) wp_unslash($_POST['projects_discovery'] ?? '');
        $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $raw)));
        $out = [];
        $seen = [];
        $duplicates = 0;
        foreach ($lines as $line) {
            $parts = array_map('trim', explode(',', $line));
            if (count($parts) < 2) { continue; }
            $type = sanitize_key($parts[0]);
            $slug = sanitize_key($parts[1]);
            if ($type === '' || $slug === '') { continue; }
            if (!in_array($type, ['plugin','theme','meta','app','core'], true)) { continue; }
            $key = ($type === 'core' ? 'meta' : $type) . ':' . $slug;
            if (isset($seen[$key])) { $duplicates++; continue; }
            $seen[$key] = true;
            $out[] = ['type' => $type === 'core' ? 'meta' : $type, 'slug' => $slug];
        }
        update_option('pierre_projects_discovery', $out);
        wp_send_json_success(['message' => __('Projects discovery library saved.', 'wp-pierre') . ' 🪨', 'count' => count($out), 'duplicates' => $duplicates]);
    }

    /** Bulk add discovery entries to a locale */
    public function ajax_bulk_add_from_discovery(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            $this->respond_error('invalid_nonce', __('Pierre says: Invalid nonce!', 'wp-pierre'));
        }
        if (!current_user_can('pierre_manage_projects')) {
            $this->respond_error('forbidden', __('Pierre says: You don\'t have permission!', 'wp-pierre'));
        }
        $locale = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        if ($locale === '') {
            $this->respond_error('missing_locale', __('Locale code is required.', 'wp-pierre'), 400);
        }
        $lib = get_option('pierre_projects_discovery', []);
        if (!is_array($lib) || empty($lib)) {
            $this->respond_error('empty_library', __('Library is empty.', 'wp-pierre'), 400);
        }
        $added = 0; $errors = 0;
        foreach ($lib as $item) {
            $type = sanitize_key($item['type'] ?? 'meta');
            $slug = sanitize_key($item['slug'] ?? '');
            if ($slug === '') { $errors++; continue; }
            $ok = $this->project_watcher->watch_project($slug, $locale);
            if ($ok) {
                // set type into watched option
                $opt = get_option('pierre_watched_projects', []);
                $key = $slug . '_' . $locale;
                if (isset($opt[$key])) { $opt[$key]['type'] = $type; update_option('pierre_watched_projects', $opt); }
                $added++;
            } else { $errors++; }
        }
        // translators: 1: number of projects added, 2: number of errors
        wp_send_json_success(['message' => sprintf(__('Added %1$d project(s), %2$d error(s).', 'wp-pierre'), $added, $errors)]);
    }

    /** Preview bulk add: counts what will be added vs already present */
    public function ajax_bulk_preview_from_discovery(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            $this->respond_error('invalid_nonce', __('Pierre says: Invalid nonce!', 'wp-pierre'));
        }
        if (!current_user_can('pierre_manage_projects')) {
            $this->respond_error('forbidden', __('Pierre says: You don\'t have permission!', 'wp-pierre'));
        }
        $locale = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        if ($locale === '') {
            $this->respond_error('missing_locale', __('Locale code is required.', 'wp-pierre'), 400);
        }
        $lib = get_option('pierre_projects_discovery', []);
        if (!is_array($lib) || empty($lib)) {
            $this->respond_error('empty_library', __('Library is empty.', 'wp-pierre'), 400);
        }
        $watched = get_option('pierre_watched_projects', []);
        $already = 0; $to_add = 0; $invalid = 0;
        foreach ($lib as $item) {
            $type = sanitize_key($item['type'] ?? 'meta');
            $slug = sanitize_key($item['slug'] ?? '');
            if ($slug === '') { $invalid++; continue; }
            $key = $slug . '_' . $locale;
            if (isset($watched[$key])) { $already++; } else { $to_add++; }
        }
        wp_send_json_success(['already' => $already, 'to_add' => $to_add, 'invalid' => $invalid]);
    }

    /**
     * Fetch locales from WordPress.org (via wp_get_available_translations)
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_fetch_locales(): void {
        $t0 = microtime(true);
        // Mark as running (15 min TTL)
        set_transient('pierre_locales_fetch_running', time(), 15 * MINUTE_IN_SECONDS);
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            delete_transient('pierre_locales_fetch_running');
            update_option('pierre_locales_fetch_error', 'invalid_nonce:' . time());
            $this->respond_error('invalid_nonce', __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
            return;
        }
        $user_id = get_current_user_id();
        if (!$this->role_manager->user_can_manage_locale_settings($user_id, '')) {
            do_action('wp_pierre_debug', 'fetch_locales: permission denied', ['source' => 'AdminController', 'user' => (int) $user_id]);
            delete_transient('pierre_locales_fetch_running');
            update_option('pierre_locales_fetch_error', 'forbidden:' . time());
            $this->respond_error('forbidden', __('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
            return;
        }
        // Optional force refresh
        $force = !empty($_POST['force']) && (int) $_POST['force'] === 1;
        if ($force) {
            delete_transient('pierre_available_locales');
        }
        // Prefer persistent cache option with hash/last_fetched
        $cache = get_option('pierre_locales_cache');
        if (!$force && is_array($cache) && !empty($cache['data'])) {
            delete_transient('pierre_locales_fetch_running');
            delete_option('pierre_locales_fetch_error');
            wp_send_json_success(['locales' => $cache['data']]);
        }

		$list = [];
		$translations = function_exists('wp_get_available_translations') ? wp_get_available_translations() : [];
		if (is_array($translations) && !empty($translations)) {
			foreach ($translations as $t) {
				if (!empty($t['language'])) {
					$list[] = [
						'code' => $t['language'],
						'label' => trim(($t['native_name'] ?? ($t['english_name'] ?? $t['language'])) . ' (' . $t['language'] . ')'),
					];
				}
			}
		}

		// Fallback to WP.org API if empty
		if (empty($list)) {
			global $wp_version;
			$url = 'https://api.wordpress.org/translations/core/1.0/';
			$args = [
				'timeout' => 10,
				'user-agent' => 'wp-pierre/' . (defined('PIERRE_VERSION') ? PIERRE_VERSION : '1.0.0') . '; ' . home_url('/'),
			];
            $response = wp_safe_remote_get(add_query_arg(['version' => $wp_version], $url), $args);
            $resp_code = is_wp_error($response) ? $response->get_error_code() : wp_remote_retrieve_response_code($response);
            if (!is_wp_error($response) && $resp_code === 200) {
				$body = json_decode(wp_remote_retrieve_body($response), true);
				if (is_array($body) && !empty($body['translations'])) {
					foreach ($body['translations'] as $t) {
						if (!empty($t['language'])) {
							$list[] = [
								'code' => $t['language'],
								'label' => trim((($t['native_name'] ?? '') ?: ($t['english_name'] ?? $t['language'])) . ' (' . $t['language'] . ')'),
							];
						}
					}
				}
            } else {
                do_action('wp_pierre_debug', 'fetch_locales: WP.org API error', ['source' => 'AdminController', 'code' => (int) $resp_code]);
			}
		}

        if (empty($list)) {
            do_action('wp_pierre_debug', 'fetch_locales: empty list after attempts', ['source' => 'AdminController']);
            delete_transient('pierre_locales_fetch_running');
            update_option('pierre_locales_fetch_error', 'upstream_empty:' . time());
            $this->respond_error('upstream_empty', __('Pierre says: No locales found from WordPress.org. Please check outgoing HTTP.', 'wp-pierre') . ' 😢', 502);
		}

        // Build and persist normalized cache (lightweight; defer heavy enrich to per-locale checks)
        $enriched = $this->build_locales_cache_from_list($list, false);
        $this->persist_locales_cache($enriched);
        delete_transient('pierre_locales_fetch_running');
        delete_option('pierre_locales_fetch_error');
        do_action('wp_pierre_debug', 'locales cache refreshed', ['scope'=>'locales','action'=>'refresh']);
        wp_send_json_success(['locales' => $enriched]);
    }

    /** Persist locales cache with hash and timestamp */
    private function persist_locales_cache(array $data): void {
        $payload = [
            'data' => $data,
            'hash' => hash('sha256', wp_json_encode($data)),
            'last_fetched' => time(),
        ];
        update_option('pierre_locales_cache', $payload, false);
    }

    /** Public hook to refresh locales cache (cron/manual) */
    public function register_locales_refresh_hook(): void {
        add_action('pierre_refresh_locales_cache', function () {
            try {
                $list = $this->fetch_base_locales_list();
                // Strong enrich on scheduled refresh (translate_slug + rosetta)
                $data = $this->build_locales_cache_from_list($list, true);
                $existing = get_option('pierre_locales_cache');
                $new_hash = hash('sha256', wp_json_encode($data));
                $old_hash = is_array($existing) ? ($existing['hash'] ?? '') : '';
                if ($new_hash !== $old_hash) {
                    $this->persist_locales_cache($data);
                }
            } catch (\Exception $e) {
                do_action('wp_pierre_debug', 'locales refresh failed: ' . $e->getMessage(), ['source' => 'AdminController']);
            }
        });
    }

    /** Fetch base locales list using WP functions / API */
    private function fetch_base_locales_list(): array {
        $list = [];
        $translations = function_exists('wp_get_available_translations') ? wp_get_available_translations() : [];
        if (is_array($translations) && !empty($translations)) {
            foreach ($translations as $t) {
                if (!empty($t['language'])) {
                    $list[] = [
                        'code' => $t['language'],
                        'label' => trim(($t['native_name'] ?? ($t['english_name'] ?? $t['language'])) . ' (' . $t['language'] . ')'),
                    ];
                }
            }
        }
        if (empty($list)) {
            global $wp_version;
            $url = 'https://api.wordpress.org/translations/core/1.0/';
            $resp = wp_remote_get(add_query_arg(['version'=>$wp_version], $url), [ 'timeout'=>10, 'user-agent'=>'wp-pierre/' . (defined('PIERRE_VERSION') ? PIERRE_VERSION : '1.0.0') . '; ' . home_url('/') ]);
            if (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200) {
                $body = json_decode(wp_remote_retrieve_body($resp), true);
                if (is_array($body) && !empty($body['translations'])) {
                    foreach ($body['translations'] as $t) {
                        if (!empty($t['language'])) {
                            $list[] = [
                                'code' => $t['language'],
                                'label' => trim((($t['native_name'] ?? '') ?: ($t['english_name'] ?? $t['language'])) . ' (' . $t['language'] . ')'),
                            ];
                        }
                    }
                }
            }
        }
        return $list;
    }

    /** Build normalized locales with translate_slug/team_locale/rosetta and optional enrich (translate slug, rosetta check, slack) */
    private function build_locales_cache_from_list(array $list, bool $resolve_translate_slug = false): array {
        $data = [];
        foreach ($list as $item) {
            $code = (string)($item['code'] ?? '');
            if ($code === '') { continue; }
            $label = (string)($item['label'] ?? $code);
            $slug_source = str_replace('_', '-', $code);
            $slug = is_string($slug_source) ? strtolower($slug_source) : '';
            if ($slug === '') { $slug = strtolower($code); }
            $rosetta = $slug !== '' ? ($slug . '.wordpress.org') : '';
            $translate_slug = $slug;
            $slack = '';
            if ($resolve_translate_slug) {
                $ts = $this->detect_translate_slug_from_team_page($code);
                if (is_string($ts) && $ts !== '') { $translate_slug = $ts; }
                // Resolve Rosetta host from candidates (robust across fr vs fr-fr)
                $candidates = $this->find_rosetta_host_candidates($code);
                $picked = $this->pick_active_rosetta($candidates);
                $rosetta = $picked ?: '';
                $slack = $this->detect_slack_from_team_page($code);
            }
            $data[] = [
                'code' => $code,
                'label' => $label,
                'slug' => $slug,
                'translate_slug' => $translate_slug,
                'team_locale' => $code,
                'rosetta' => $rosetta,
                'slack_url' => $slack ?: null,
            ];
        }
        return $data;
    }

    /** Try to extract translate.wordpress.org slug from the team page */
    private function detect_translate_slug_from_team_page(string $code): string {
        $url = 'https://make.wordpress.org/polyglots/teams/?locale=' . rawurlencode($code);
        $cache_key = 'pierre_team_page_' . strtolower($code);
        $html = get_transient($cache_key);
        if (!is_string($html)) {
            $resp = $this->http_get_with_retries($url, 2, 12);
            if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) { return ''; }
            $html = wp_remote_retrieve_body($resp);
            if (is_string($html) && $html !== '') { set_transient($cache_key, $html, DAY_IN_SECONDS); }
        }
        if (!is_string($html) || $html === '') { return ''; }
        if (preg_match('#translate\.wordpress\.org/locale/([a-z0-9\-]+)/?#i', $html, $m)) {
            return strtolower($m[1]);
        }
        return '';
    }

    /** Check if a rosetta host responds with non-404 */
    private function is_rosetta_host_active(string $host): bool {
        $url = 'https://' . ltrim($host, '/');
        $resp = $this->http_head_with_retries($url, 2, 8);
        if (!is_wp_error($resp)) {
            $code = wp_remote_retrieve_response_code($resp);
            if ($code && $code !== 404) { return true; }
        }
        $resp = $this->http_get_with_retries($url, 1, 8);
        if (is_wp_error($resp)) { return false; }
        $code = wp_remote_retrieve_response_code($resp);
        return (int)$code !== 404 && (int)$code !== 0;
    }

    /** Build Rosetta host candidates for a given locale code */
    private function find_rosetta_host_candidates(string $code): array {
        $norm = strtolower(str_replace('_','-',$code));
        $lang = substr($norm, 0, 2);
        $c = [];
        if ($lang) { $c[] = $lang . '.wordpress.org'; }
        if ($norm) { $c[] = $norm . '.wordpress.org'; }
        if ($lang && strpos($norm, '-') !== false) { $c[] = $lang . '.wordpress.org'; }
        return array_values(array_unique($c));
    }

    /** Pick first active Rosetta among candidates */
    private function pick_active_rosetta(array $hosts): string {
        foreach ($hosts as $h) {
            if (!is_string($h) || $h === '') { continue; }
            $url = 'https://' . ltrim($h, '/');
            $resp = $this->http_head_with_retries($url, 2, 6);
            $code = is_wp_error($resp) ? 0 : (int) wp_remote_retrieve_response_code($resp);
            if ($code && $code !== 404) { return $h; }
            $resp = $this->http_get_with_retries($url, 1, 6);
            $code = is_wp_error($resp) ? 0 : (int) wp_remote_retrieve_response_code($resp);
            if ($code && $code !== 404) { return $h; }
        }
        return '';
    }

    /** Extract Slack URL from the locale team page if present */
    private function detect_slack_from_team_page(string $code): string {
        $url = 'https://make.wordpress.org/polyglots/teams/?locale=' . rawurlencode($code);
        $cache_key = 'pierre_team_page_' . strtolower($code);
        $html = get_transient($cache_key);
        if (!is_string($html)) {
            $resp = $this->http_get_with_retries($url, 2, 12);
            if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) { return ''; }
            $html = wp_remote_retrieve_body($resp);
            if (is_string($html) && $html !== '') { set_transient($cache_key, $html, DAY_IN_SECONDS); }
        }
        if (!is_string($html) || $html === '') { return ''; }
        if (preg_match('#https?://([a-z0-9\-]+\.slack\.com)(?:/[\w\-\./%]*)?#i', $html, $m)) {
            return 'https://' . strtolower($m[1]);
        }
        return '';
    }

    private function http_get_with_retries(string $url, int $retries, int $timeout) {
        $defaults = \Pierre\Plugin::get_http_defaults();
        $args = [
            'timeout' => $timeout > 0 ? $timeout : ($defaults['timeout'] ?? 30),
            'redirection' => $defaults['redirection'] ?? 3,
            'user-agent' => $defaults['user-agent'] ?? 'wp-pierre/1.0.0; ' . home_url('/'),
        ];
        $resp = wp_remote_get($url, $args);
        for ($i=0; $i<$retries && (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) >= 500); $i++) {
            usleep(500000);
            $resp = wp_remote_get($url, $args);
        }
        return $resp;
    }
    private function http_head_with_retries(string $url, int $retries, int $timeout) {
        $defaults = \Pierre\Plugin::get_http_defaults();
        $args = [
            'timeout' => $timeout > 0 ? $timeout : ($defaults['timeout'] ?? 30),
            'redirection' => $defaults['redirection'] ?? 3,
            'user-agent' => $defaults['user-agent'] ?? 'wp-pierre/1.0.0; ' . home_url('/'),
        ];
        $resp = wp_remote_head($url, $args);
        for ($i=0; $i<$retries && (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) >= 500); $i++) {
            usleep(500000);
            $resp = wp_remote_head($url, $args);
        }
        return $resp;
    }

    public function ajax_check_locale_status(): void {
        if (!current_user_can('manage_options')) { $this->respond_error('forbidden','denied'); }
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce','bad_nonce'); }
        $code = sanitize_key(wp_unslash($_POST['code'] ?? ''));
        if ($code === '') { $this->respond_error('invalid_payload','missing code',400); }
        $cache = get_option('pierre_locales_cache');
        $rows = is_array($cache) && !empty($cache['data']) ? (array)$cache['data'] : [];
        $updated = false; $result = [];
        foreach ($rows as &$r) {
            if (($r['code'] ?? '') !== $code) { continue; }
            $slug = (string)($r['slug'] ?? '');
            $translate_slug = (string)($r['translate_slug'] ?? $slug);
            $ts = $this->detect_translate_slug_from_team_page($code);
            if (is_string($ts) && $ts !== '') { $translate_slug = $ts; }
            $rosetta = (string)($r['rosetta'] ?? '');
            if ($rosetta === '' && $slug) {
                $candidates = $this->find_rosetta_host_candidates($code);
                $rosetta = $this->pick_active_rosetta($candidates) ?: '';
            }
            $rosetta_ok = $rosetta !== '' ? $this->is_rosetta_host_active($rosetta) : false;
            $issues = [];
            if (strtolower(str_replace('_','-',$code)) !== strtolower($translate_slug)) { $issues[] = 'translate_slug≠code'; }
            if (!$rosetta_ok) { $issues[] = 'rosetta_inactive_or_missing'; $rosetta = ''; }
            $r['translate_slug'] = $translate_slug;
            $r['rosetta'] = $rosetta;
            $r['checked_at'] = time();
            $updated = true;
            $result = [ 'translate_slug' => $translate_slug, 'rosetta' => $rosetta, 'issues' => $issues ];
            if (!empty($issues)) { $this->append_locale_log($code, $issues); }
            break;
        }
        unset($r);
        if ($updated) { $cache['data'] = $rows; $this->persist_locales_cache($rows); }
        wp_send_json_success([ 'code' => $code, 'status' => $result ]);
    }

    private function append_locale_log(string $code, array $issues): void {
        $log = get_option('pierre_locales_log');
        if (!is_array($log)) { $log = []; }
        $log[] = [ 'code'=>$code, 'issues'=>$issues, 'time'=>time() ];
        if (count($log) > 500) { $log = array_slice($log, -500); }
        update_option('pierre_locales_log', $log, false);
    }

    /** Clear anomalies log */
    public function ajax_clear_locale_log(): void {
        if (!current_user_can('manage_options')) { $this->respond_error('forbidden','denied'); }
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce','bad_nonce'); }
        update_option('pierre_locales_log', [], false);
        wp_send_json_success(['message'=>__('Anomalies log cleared.', 'wp-pierre')]);
    }

    /** Export anomalies log JSON */
    public function ajax_export_locale_log(): void {
        if (!current_user_can('manage_options')) { wp_die(__('Permission denied', 'wp-pierre')); }
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { wp_die(__('Invalid nonce', 'wp-pierre')); }
        $log = get_option('pierre_locales_log');
        if (!is_array($log)) { $log = []; }
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="pierre_locales_anomalies_log.json"');
        echo wp_json_encode($log, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Fetch and cache Slack workspace hosts from the Polyglots handbook page.
     * Returns array of hosts like ['wpfr.slack.com', ...]
     */
    private function get_local_slack_workspaces(): array {
        $cached = get_transient('pierre_slack_workspaces');
        if (is_array($cached)) { return $cached; }
        $url = 'https://make.wordpress.org/polyglots/handbook/translating/teams/local-slacks/';
        $resp = wp_safe_remote_get($url, [ 'timeout' => 12, 'user-agent' => 'wp-pierre/' . (defined('PIERRE_VERSION') ? PIERRE_VERSION : '1.0.0') ]);
        $hosts = [];
        if (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200) {
            $html = wp_remote_retrieve_body($resp);
            if (is_string($html) && $html !== '') {
                if (preg_match_all('#https?://([a-z0-9\-]+\.slack\.com)(?:/[^\s\"\']*)?#i', $html, $m)) {
                    foreach ($m[1] as $host) { $hosts[$host] = true; }
                }
            }
        }
        $list = array_keys($hosts);
        set_transient('pierre_slack_workspaces', $list, 12 * HOUR_IN_SECONDS);
        return $list;
    }

    /** Best-effort mapping from locale slug/lang to a Slack host. */
    private function find_best_slack_for_slug(string $slug, string $lang, array $hosts): string {
        $slug = strtolower($slug);
        $lang = strtolower($lang);
        foreach ($hosts as $h) { if (!is_string($h)) continue; if ($slug !== '' && strpos($h, $slug) !== false) return $h; }
        foreach ($hosts as $h) { if (!is_string($h)) continue; if ($lang !== '' && str_starts_with($h, $lang)) return $h; }
        foreach ($hosts as $h) { if (!is_string($h)) continue; if ($lang !== '' && preg_match('#(^|\-)'.preg_quote($lang,'#').'(\-|\.)#', $h)) return $h; }
        foreach (['wp'.$lang, 'wp-'.$lang, 'wp'.$slug, 'wp-'.$slug] as $needle) {
            foreach ($hosts as $h) { if (!is_string($h)) continue; if ($needle !== '' && strpos($h, $needle) !== false) return $h; }
        }
        return '';
    }
    
    /**
     * Pierre gets admin reports data! 🪨
     * 
     * @since 1.0.0
     * @return array Admin reports data
     */
    private function get_admin_reports_data(): array {
        return [
            'stats' => $this->get_reports_stats()
        ];
    }
    
    /**
     * Pierre gets admin statistics! 🪨
     * 
     * @since 1.0.0
     * @return array Admin statistics
     */
    private function get_admin_stats(): array {
        $watched_projects = $this->project_watcher->get_watched_projects();
        $current_user_id = get_current_user_id();
        $user_assignments = $current_user_id ? $this->user_project_link->get_user_assignments_with_details($current_user_id) : [];
        
        return [
            [
                'label' => 'Watched Projects',
                'value' => count($watched_projects)
            ],
            [
                'label' => 'Your Assignments',
                'value' => count($user_assignments)
            ],
            [
                'label' => 'Surveillance Active',
                'value' => $this->project_watcher->is_surveillance_active() ? 'Yes' : 'No'
            ],
            [
                'label' => 'Notifications Ready',
                'value' => $this->slack_notifier->is_ready() ? 'Yes' : 'No'
            ]
        ];
    }
    
    /**
     * Pierre gets teams statistics! 🪨
     * 
     * @since 1.0.0
     * @return array Teams statistics
     */
    private function get_teams_stats(): array {
        return [
            [
                'label' => 'Total Users',
                'value' => count(get_users())
            ],
            [
                'label' => 'Pierre Roles',
                'value' => count($this->role_manager->get_roles())
            ]
        ];
    }
    
    /**
     * Pierre gets projects statistics! 🪨
     * 
     * @since 1.0.0
     * @return array Projects statistics
     */
    private function get_projects_stats(): array {
        $watched_projects = $this->project_watcher->get_watched_projects();
        
        return [
            [
                'label' => 'Watched Projects',
                'value' => count($watched_projects)
            ],
            [
                'label' => 'Surveillance Status',
                'value' => $this->project_watcher->is_surveillance_active() ? 'Active' : 'Inactive'
            ]
        ];
    }
    
    /**
     * Pierre gets reports statistics! 🪨
     * 
     * @since 1.0.0
     * @return array Reports statistics
     */
    private function get_reports_stats(): array {
        return [
            [
                'label' => 'Reports Available',
                'value' => 'Coming Soon'
            ]
        ];
    }
    
    /**
     * Pierre handles AJAX admin stats request! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_get_admin_stats(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        $stats = $this->get_admin_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * Pierre handles AJAX assign user! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_assign_user(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        // Locale Manager can assign, GTE cannot
        $current_user_id = get_current_user_id();
        $locale_code = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        if (!$this->role_manager->user_can_assign_projects($current_user_id, $locale_code)) {
            wp_die(__('Pierre says: You don\'t have permission! Only Locale Managers and site administrators can assign users.', 'wp-pierre') . ' 😢');
        }
        
        $user_id = absint(wp_unslash($_POST['user_id']) ?? 0);
        $project_type = sanitize_key(wp_unslash($_POST['project_type'] ?? ''));
        $project_slug = sanitize_key(wp_unslash($_POST['project_slug'] ?? ''));
        $role = sanitize_key(wp_unslash($_POST['role'] ?? ''));
        $assigned_by = $current_user_id;
        
        $result = $this->user_project_link->assign_user_to_project(
            $user_id,
            $project_type,
            $project_slug,
            $locale_code,
            $role,
            $assigned_by
        );
        
        wp_send_json_success($result);
    }
    
    /**
     * Pierre handles AJAX remove user! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_remove_user(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_assign_projects')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        $user_id = absint(wp_unslash($_POST['user_id']) ?? 0);
        $project_slug = sanitize_key(wp_unslash($_POST['project_slug'] ?? ''));
        $locale_code = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        $removed_by = get_current_user_id();
        
        $result = $this->user_project_link->remove_user_from_project(
            $user_id,
            $project_slug,
            $locale_code,
            $removed_by
        );
        
        wp_send_json_success($result);
    }
    
    /**
     * Pierre handles AJAX test notification! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_test_notification(): void {
        // Pierre checks nonce! 🪨 (accept admin or generic nonce)
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false) && !check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢']);
            return;
        }
        
        // Pierre checks permissions! 🪨 (fallback to manage_options until custom caps are wired)
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢']);
            return;
        }
        
        // Pierre gets webhook URL from form if provided! 🪨 (accept both legacy and global field names)
        $webhook_url = sanitize_url(wp_unslash($_POST['slack_webhook_url'] ?? ''));
        if (empty($webhook_url)) {
            $webhook_url = sanitize_url(wp_unslash($_POST['global_webhook_url'] ?? ''));
        }
        if (!empty($webhook_url)) {
            $this->slack_notifier->set_webhook_url($webhook_url);
        }
        
        $result = $this->slack_notifier->test_notification();
        // Persist last test outcome/time
        update_option('pierre_last_global_webhook_test', [
            'time' => current_time('timestamp'),
            'success' => (bool) $result,
        ]);
        do_action('wp_pierre_debug', 'webhook test completed', ['scope'=>'webhook','action'=>'test','code'=>$result?'ok':'fail']);
        if ($result) {
            wp_send_json_success([
                'message' => __('Slack webhook test succeeded! Check your Slack channel.', 'wp-pierre') . ' 🪨',
                'test_result' => $result
            ]);
        } else {
            $detail = method_exists($this->slack_notifier, 'get_last_error') ? (string) ($this->slack_notifier->get_last_error() ?? '') : '';
            $this->respond_error('slack_test_failed', __('Slack webhook test failed. Verify the webhook URL is correct.', 'wp-pierre') . ' 😢', 400, ['error' => $detail]);
        }
    }

    /** Run surveillance now with cooldown */
    public function ajax_run_surveillance_now(): void {
        $t0 = microtime(true);
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce', __('Invalid nonce.', 'wp-pierre')); }
        if (!current_user_can('pierre_manage_projects')) { $this->respond_error('forbidden', __('Permission denied.', 'wp-pierre')); }
        $last = (int) get_option('pierre_last_run_now_surveillance', 0);
        if ($last && (time() - $last) < 60) { $this->respond_error('cooldown', __('Please wait before running again (cooldown 60s).', 'wp-pierre'), 429); }
        update_option('pierre_last_run_now_surveillance', time());
        // Force run bypasses the global enabled switch
        pierre()->get_cron_manager()->run_surveillance_check(true);
        do_action('wp_pierre_debug', 'surveillance run triggered', ['scope'=>'cron','action'=>'run_now']);
        wp_send_json_success(['message' => __('Surveillance run triggered.', 'wp-pierre')]);
    }

    /** Run cleanup now with cooldown */
    public function ajax_run_cleanup_now(): void {
        $t0 = microtime(true);
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) { $this->respond_error('invalid_nonce', __('Invalid nonce.', 'wp-pierre')); }
        if (!current_user_can('pierre_manage_projects')) { $this->respond_error('forbidden', __('Permission denied.', 'wp-pierre')); }
        $last = (int) get_option('pierre_last_run_now_cleanup', 0);
        if ($last && (time() - $last) < 60) { $this->respond_error('cooldown', __('Please wait before running again (cooldown 60s).', 'wp-pierre'), 429); }
        update_option('pierre_last_run_now_cleanup', time());
        pierre()->get_cron_manager()->run_cleanup_task();
        do_action('wp_pierre_debug', 'cleanup run triggered', ['scope'=>'cron','action'=>'cleanup_now']);
        wp_send_json_success(['message' => __('Cleanup run triggered.', 'wp-pierre')]);
    }
    
    /**
     * Pierre handles AJAX save settings! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_save_settings(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            $this->respond_error('invalid_nonce', __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
            return;
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_settings')) {
            $this->respond_error('forbidden', __('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
            return;
        }
        
        // Pierre gets existing settings to merge! 🪨
        $existing_settings = get_option('pierre_settings', []);
        
        // Handle notification_types array properly
        $notification_types = [];
        if (isset($_POST['notification_types']) && is_array($_POST['notification_types'])) {
            $raw_types = array_map('sanitize_key', wp_unslash($_POST['notification_types']));
            $valid_types = ['new_strings', 'completion_update', 'needs_attention', 'errors'];
            $notification_types = array_intersect($raw_types, $valid_types);
        }
        if (empty($notification_types)) {
            $notification_types = $existing_settings['notification_types'] ?? ['new_strings', 'completion_update'];
        }
        
        // Notification defaults (global)
        $defaults_new_strings = absint(wp_unslash($_POST['new_strings_threshold'] ?? ($existing_settings['notification_defaults']['new_strings_threshold'] ?? 20)));
        $defaults_milestones_raw = (string) wp_unslash($_POST['milestones'] ?? '');
        $defaults_milestones = [];
        if ($defaults_milestones_raw !== '') {
            foreach (explode(',', $defaults_milestones_raw) as $p) {
                $p = trim($p);
                if ($p === '') { continue; }
                $defaults_milestones[] = (int) $p;
            }
        } else {
            $defaults_milestones = $existing_settings['notification_defaults']['milestones'] ?? [50,80,100];
        }
        sort($defaults_milestones);
        $defaults_mode = sanitize_key(wp_unslash($_POST['mode'] ?? ($existing_settings['notification_defaults']['mode'] ?? 'immediate')));
        if (!in_array($defaults_mode, ['immediate','digest'], true)) { $defaults_mode = 'immediate'; }
        $defaults_digest_type = sanitize_key(wp_unslash($_POST['digest_type'] ?? ($existing_settings['notification_defaults']['digest']['type'] ?? 'interval')));
        if (!in_array($defaults_digest_type, ['interval','fixed_time'], true)) { $defaults_digest_type = 'interval'; }
        $defaults_digest_interval = max(15, absint(wp_unslash($_POST['digest_interval_minutes'] ?? ($existing_settings['notification_defaults']['digest']['interval_minutes'] ?? 60))));
        $defaults_digest_fixed = preg_replace('/[^0-9:]/', '', (string) wp_unslash($_POST['digest_fixed_time'] ?? ($existing_settings['notification_defaults']['digest']['fixed_time'] ?? '09:00')));

        $settings = array_merge($existing_settings, [
            'slack_webhook_url' => sanitize_url(wp_unslash($_POST['slack_webhook_url'] ?? $existing_settings['slack_webhook_url'] ?? '')),
            'surveillance_interval' => absint(wp_unslash($_POST['surveillance_interval'] ?? $existing_settings['surveillance_interval'] ?? 15)),
            'notifications_enabled' => !empty(wp_unslash($_POST['notifications_enabled'] ?? $existing_settings['notifications_enabled'] ?? false)),
            'auto_start_surveillance' => !empty(wp_unslash($_POST['auto_start_surveillance'] ?? $existing_settings['auto_start_surveillance'] ?? false)),
            'max_projects_per_check' => absint(wp_unslash($_POST['max_projects_per_check'] ?? $existing_settings['max_projects_per_check'] ?? 10)),
            'request_timeout' => max(3, absint(wp_unslash($_POST['request_timeout'] ?? ($existing_settings['request_timeout'] ?? 30)))),
            'notification_types' => $notification_types,
            'notification_threshold' => absint(wp_unslash($_POST['notification_threshold'] ?? $existing_settings['notification_threshold'] ?? 80)),
            'notification_defaults' => [
                'new_strings_threshold' => $defaults_new_strings,
                'milestones' => $defaults_milestones,
                'mode' => $defaults_mode,
                'digest' => [
                    'type' => $defaults_digest_type,
                    'interval_minutes' => $defaults_digest_interval,
                    'fixed_time' => $defaults_digest_fixed,
                ],
            ],
        ]);

        // Global webhook unified model (optional fields)
        $gw = $settings['global_webhook'] ?? [];
        $gw['enabled'] = !empty(wp_unslash($_POST['global_webhook_enabled'] ?? ($gw['enabled'] ?? false)));
        $gw['webhook_url'] = sanitize_url(wp_unslash($_POST['global_webhook_url'] ?? ($gw['webhook_url'] ?? '')));
        $gw['types'] = isset($_POST['global_webhook_types']) && is_array($_POST['global_webhook_types'])
            ? array_values(array_intersect(array_map('sanitize_key', wp_unslash($_POST['global_webhook_types'])), ['new_strings','completion_update','needs_attention','milestone']))
            : (array)($gw['types'] ?? ['new_strings','completion_update']);
        $gw['threshold'] = absint(wp_unslash($_POST['global_webhook_threshold'] ?? ($gw['threshold'] ?? $defaults_new_strings)));
        $mil_raw = (string) wp_unslash($_POST['global_webhook_milestones'] ?? '');
        if ($mil_raw !== '') {
            $gw['milestones'] = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $mil_raw)))));
            sort($gw['milestones']);
        } else { $gw['milestones'] = (array)($gw['milestones'] ?? $defaults_milestones); }
        $gw['mode'] = in_array(($m= sanitize_key(wp_unslash($_POST['global_webhook_mode'] ?? ($gw['mode'] ?? $defaults_mode)))), ['immediate','digest'], true) ? $m : 'immediate';
        $gw['digest'] = $gw['digest'] ?? [];
        $gw['digest']['type'] = in_array(($dt = sanitize_key(wp_unslash($_POST['global_webhook_digest_type'] ?? ($gw['digest']['type'] ?? $defaults_digest_type)))), ['interval','fixed_time'], true) ? $dt : 'interval';
        $gw['digest']['interval_minutes'] = max(15, absint(wp_unslash($_POST['global_webhook_digest_interval_minutes'] ?? ($gw['digest']['interval_minutes'] ?? $defaults_digest_interval))));
        $gw['digest']['fixed_time'] = preg_replace('/[^0-9:]/', '', (string) wp_unslash($_POST['global_webhook_digest_fixed_time'] ?? ($gw['digest']['fixed_time'] ?? $defaults_digest_fixed)));
        // scopes
        $gw['scopes'] = $gw['scopes'] ?? ['locales'=>[],'projects'=>[]];
        $gw['scopes']['locales'] = isset($_POST['global_webhook_scopes_locales']) && is_array($_POST['global_webhook_scopes_locales'])
            ? array_values(array_map('sanitize_key', wp_unslash($_POST['global_webhook_scopes_locales']))) : (array)($gw['scopes']['locales'] ?? []);
        $proj_raw = (string) wp_unslash($_POST['global_webhook_scopes_projects'] ?? '');
        if ($proj_raw !== '') {
            $gw['scopes']['projects'] = [];
            foreach (array_filter(array_map('trim', preg_split('/\r?\n/', $proj_raw))) as $line) {
                $parts = array_map('trim', explode(',', $line));
                if (count($parts) >= 2) { $gw['scopes']['projects'][] = ['type'=>sanitize_key($parts[0]), 'slug'=>sanitize_key($parts[1])]; }
            }
        }
        $settings['global_webhook'] = $gw;
        // Global surveillance enable/disable
        $settings['surveillance_enabled'] = !empty(wp_unslash($_POST['surveillance_enabled'] ?? ($existing_settings['surveillance_enabled'] ?? false)));
        
        $old_interval = (int)($existing_settings['surveillance_interval'] ?? 15);
        update_option('pierre_settings', $settings);
        
        // Pierre updates his webhook URL! 🪨 Prefer the new Global Webhook URL if present
        $gw_url = trim((string)($settings['global_webhook']['webhook_url'] ?? ''));
        if ($gw_url !== '') {
            $this->slack_notifier->set_webhook_url($gw_url);
        } elseif (!empty($settings['slack_webhook_url'])) {
            $this->slack_notifier->set_webhook_url($settings['slack_webhook_url']);
        }

        // If surveillance interval changed, reschedule cron
        $new_interval = (int)($settings['surveillance_interval'] ?? 15);
        if ($new_interval !== $old_interval) {
            try {
                pierre()->get_cron_manager()->reschedule_surveillance();
            } catch (\Exception $e) {
                do_action('wp_pierre_debug', 'failed to reschedule surveillance: ' . $e->getMessage(), ['source' => 'AdminController']);
            }
        }
        
        do_action('wp_pierre_debug', 'settings saved', ['scope'=>'admin','action'=>'save_settings']);
        wp_send_json_success([
            'message' => __('Settings saved successfully!', 'wp-pierre') . ' 🪨'
        ]);
    }

    /**
     * Save per-locale overrides for notifications
     */
    public function ajax_save_locale_overrides(): void {
        if (!check_ajax_referer('pierre_admin_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢']);
            return;
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢']);
            return;
        }
        $locale = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        if ($locale === '') {
            wp_send_json_error(['message' => __('Locale code is required.', 'wp-pierre') . ' 😢']);
            return;
        }
        $settings = get_option('pierre_settings', []);
        if (!is_array($settings)) { $settings = []; }
        if (!isset($settings['locales']) || !is_array($settings['locales'])) { $settings['locales'] = []; }

        $new_strings = wp_unslash($_POST['new_strings_threshold'] ?? '');
        $milestones_raw = (string) wp_unslash($_POST['milestones'] ?? '');
        $mode = sanitize_key(wp_unslash($_POST['mode'] ?? ''));
        $digest_type = sanitize_key(wp_unslash($_POST['digest_type'] ?? ''));
        $digest_interval = wp_unslash($_POST['digest_interval_minutes'] ?? '');
        $digest_fixed = (string) wp_unslash($_POST['digest_fixed_time'] ?? '');

        $over = $settings['locales'][$locale] ?? [];
        if ($new_strings !== '') { $over['new_strings_threshold'] = absint($new_strings); }
        if ($milestones_raw !== '') {
            $ms = [];
            foreach (explode(',', $milestones_raw) as $p) { $p = trim($p); if ($p === '') continue; $ms[] = (int) $p; }
            sort($ms);
            $over['milestones'] = $ms;
        }
        if (in_array($mode, ['immediate','digest'], true)) { $over['mode'] = $mode; }
        if (!isset($over['digest']) || !is_array($over['digest'])) { $over['digest'] = []; }
        if (in_array($digest_type, ['interval','fixed_time'], true)) { $over['digest']['type'] = $digest_type; }
        if ($digest_interval !== '') { $over['digest']['interval_minutes'] = max(15, absint($digest_interval)); }
        if ($digest_fixed !== '') { $over['digest']['fixed_time'] = preg_replace('/[^0-9:]/', '', $digest_fixed); }
        $over['override'] = true;

        $settings['locales'][$locale] = $over;
        update_option('pierre_settings', $settings);
        wp_send_json_success(['message' => __('Locale overrides saved.', 'wp-pierre') . ' 🪨']);
    }
    
    /**
     * Pierre handles AJAX start surveillance! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_start_surveillance(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_projects')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        // Optional per-entity cooldown (locale/project) with fallback to global (2 minutes)
        $locale = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        $project = sanitize_key(wp_unslash($_POST['project_slug'] ?? ''));
        if ($locale && $project) {
            $key = 'pierre_last_forced_scan_' . $locale . '_' . $project;
            $last = (int) get_option($key, 0);
            if ($last && (time() - $last) < 120) {
                wp_send_json_error(['message' => __('Please wait before forcing another scan for this project/locale (cooldown 2 min).', 'wp-pierre') . ' 😢']);
            }
            update_option($key, time());
        } elseif ($locale) {
            $key = 'pierre_last_forced_scan_' . $locale;
            $last = (int) get_option($key, 0);
            if ($last && (time() - $last) < 120) {
                wp_send_json_error(['message' => __('Please wait before forcing another scan for this locale (cooldown 2 min).', 'wp-pierre') . ' 😢']);
            }
            update_option($key, time());
        } else {
            $last = (int) get_option('pierre_last_forced_scan_global', 0);
            if ($last && (time() - $last) < 120) {
                wp_send_json_error(['message' => __('Please wait before forcing another scan (cooldown 2 min).', 'wp-pierre') . ' 😢']);
            }
            update_option('pierre_last_forced_scan_global', time());
        }

        $result = $this->project_watcher->start_surveillance();
        wp_send_json_success(['message' => 'Pierre started surveillance! 🪨', 'result' => $result]);
    }
    
    /**
     * Pierre handles AJAX stop surveillance! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_stop_surveillance(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_projects')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        $result = $this->project_watcher->stop_surveillance();
        wp_send_json_success(['message' => 'Pierre stopped surveillance! 🪨', 'result' => $result]);
    }
    
    /**
     * Pierre handles AJAX test surveillance! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_test_surveillance(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => __('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢']);
        }
        
        // Pierre checks permissions! 🪨 (fallback to manage_options until custom caps are wired)
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢']);
        }
        
        $result = $this->project_watcher->test_surveillance();
        if (!empty($result['success'])) {
            wp_send_json_success($result);
        }
        wp_send_json_error($result);
    }
    
    /**
     * Pierre handles AJAX add project! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_add_project(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_projects')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        $project_slug = sanitize_key(wp_unslash($_POST['project_slug'] ?? ''));
        $locale_code = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        $project_type = sanitize_key(wp_unslash($_POST['project_type'] ?? 'meta'));
        
        // Validate project_type
        $valid_types = ['meta', 'plugin', 'theme', 'app'];
        if (!in_array($project_type, $valid_types, true)) {
            $project_type = 'meta';
        }
        
        if (empty($project_slug) || empty($locale_code)) {
            wp_send_json_error(['message' => __('Pierre says: Project slug and locale code are required!', 'wp-pierre') . ' 😢']);
            return;
        }
        
        // Check workflow "locale d'abord" - validate locale exists in WP.org
        $translations = function_exists('wp_get_available_translations') ? wp_get_available_translations() : [];
        $locale_valid = false;
        foreach ($translations as $t) {
            if (($t['language'] ?? '') === $locale_code) {
                $locale_valid = true;
                break;
            }
        }
        
        if (!$locale_valid) {
            // translators: %s is the locale code
            wp_send_json_error(['message' => sprintf(__('Locale %s is not valid or not found in WordPress.org translations.', 'wp-pierre'), $locale_code) . ' 😢']);
            return;
        }
        
        $result = $this->project_watcher->watch_project($project_slug, $locale_code, $project_type);
        
        // Store project type if watch_project succeeded
        if ($result) {
            $watched = $this->project_watcher->get_watched_projects();
            $project_key = "{$project_slug}_{$locale_code}";
            if (isset($watched[$project_key])) {
                // Update project with type if not already set
                $watched_projects_option = get_option('pierre_watched_projects', []);
                if (isset($watched_projects_option[$project_key])) {
                    $watched_projects_option[$project_key]['type'] = $project_type;
                    update_option('pierre_watched_projects', $watched_projects_option);
                }
            }
            // translators: 1: project slug, 2: project type, 3: locale code
            wp_send_json_success(['message' => sprintf(__('Project %1$s (%2$s) added to surveillance for locale %3$s!', 'wp-pierre'), $project_slug, $project_type, $locale_code) . ' 🪨']);
        } else {
            wp_send_json_error(['message' => __('Pierre says: Failed to add project! Check the project slug and locale code.', 'wp-pierre') . ' 😢']);
        }
    }
    
    /**
     * Pierre handles AJAX remove project! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_remove_project(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_projects')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        $project_slug = sanitize_key(wp_unslash($_POST['project_slug'] ?? ''));
        $locale_code = sanitize_key(wp_unslash($_POST['locale_code'] ?? ''));
        
        if (empty($project_slug) || empty($locale_code)) {
            wp_send_json_error(['message' => __('Pierre says: Project slug and locale code are required!', 'wp-pierre') . ' 😢']);
            return;
        }
        
        $result = $this->project_watcher->unwatch_project($project_slug, $locale_code);
        
        if ($result) {
            wp_send_json_success(['message' => 'Pierre removed project from watch list! 🪨', 'result' => $result]);
        } else {
            wp_send_json_error(['message' => __('Pierre says: Failed to remove project!', 'wp-pierre') . ' 😢']);
        }
    }
    
    /**
     * Pierre handles AJAX flush cache! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_flush_cache(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_settings')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre flushes his cache! 🪨
        $this->project_watcher->flush_cache();
        
        wp_send_json_success(['message' => 'Pierre flushed his cache! 🪨']);
    }
    
    /**
     * Pierre handles AJAX reset settings! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_reset_settings(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_settings')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre resets his settings! 🪨
        delete_option('pierre_settings');
        
        wp_send_json_success(['message' => 'Pierre reset his settings! 🪨']);
    }
    
    /**
     * Pierre handles AJAX clear data! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_clear_data(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_settings')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre clears his data! 🪨
        $this->project_watcher->clear_all_data();
        $this->user_project_link->clear_all_data();
        
        wp_send_json_success(['message' => 'Pierre cleared all his data! 🪨']);
    }
    
    /**
     * Pierre handles AJAX export report! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_export_report(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_reports')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        $report_type = sanitize_key(wp_unslash($_POST['report_type'] ?? ''));
        
        if (empty($report_type)) {
            wp_send_json_error(['message' => __('Pierre says: Report type is required!', 'wp-pierre') . ' 😢']);
            return;
        }
        
        // Pierre generates his report! 🪨
        $report_data = $this->generate_report($report_type);
        
        if ($report_data) {
            wp_send_json_success([
                // translators: %s is the report type (e.g., "projects", "teams")
                'message' => sprintf(__('Pierre exported %s report successfully!', 'wp-pierre'), $report_type) . ' 🪨',
                'data' => $report_data
            ]);
        } else {
            wp_send_json_error(['message' => __('Pierre says: Failed to generate report!', 'wp-pierre') . ' 😢']);
        }
    }
    
    /**
     * Pierre handles AJAX export all reports! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_export_all_reports(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_reports')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre generates all his reports! 🪨
        $report_types = ['projects', 'teams', 'surveillance', 'notifications'];
        $all_reports = [];
        
        foreach ($report_types as $type) {
            $report_data = $this->generate_report($type);
            if ($report_data) {
                $all_reports[$type] = $report_data;
            }
        }
        
        if (!empty($all_reports)) {
            wp_send_json_success([
                'message' => __('Pierre exported all reports successfully!', 'wp-pierre') . ' 🪨',
                'data' => $all_reports
            ]);
        } else {
            wp_send_json_error(['message' => __('Pierre says: Failed to generate reports!', 'wp-pierre') . ' 😢']);
        }
    }
    
    /**
     * Pierre handles AJAX schedule reports! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_schedule_reports(): void {
        // Pierre checks nonce! 🪨
        if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
            wp_die(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_reports')) {
            wp_die(__('Pierre says: You don\'t have permission!', 'wp-pierre') . ' 😢');
        }
        
        $schedule_frequency = sanitize_key(wp_unslash($_POST['schedule_frequency']) ?? 'weekly');
        $report_types = wp_unslash($_POST['report_types']) ?? [];
        
        // Pierre schedules his reports! 🪨
        $result = $this->schedule_reports($schedule_frequency, $report_types);
        
        if ($result) {
            wp_send_json_success([
                // translators: %s is the schedule frequency (e.g., "daily", "weekly")
                'message' => sprintf(__('Pierre scheduled reports for %s!', 'wp-pierre'), $schedule_frequency) . ' 🪨',
                'data' => $result
            ]);
        } else {
            wp_send_json_error(['message' => __('Pierre says: Failed to schedule reports!', 'wp-pierre') . ' 😢']);
        }
    }
    
    /**
     * Pierre generates a report! 🪨
     * 
     * @since 1.0.0
     * @param string $report_type Type of report to generate
     * @return array|false Report data or false on failure
     */
    private function generate_report(string $report_type): array|false {
        try {
            switch ($report_type) {
                case 'projects':
                    return $this->generate_projects_report();
                case 'teams':
                    return $this->generate_teams_report();
                case 'surveillance':
                    return $this->generate_surveillance_report();
                case 'notifications':
                    return $this->generate_notifications_report();
                default:
                    return false;
            }
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'error generating report: ' . $e->getMessage(), ['source' => 'AdminController']);
            return false;
        }
    }
    
    /**
     * Pierre generates projects report! 🪨
     * 
     * @since 1.0.0
     * @return array Projects report data
     */
    private function generate_projects_report(): array {
        $projects = $this->project_watcher->get_all_projects();
        $report_data = [
            'generated_at' => current_time('mysql'),
            'total_projects' => count($projects),
            'projects' => []
        ];
        
        foreach ($projects as $project) {
            $report_data['projects'][] = [
                'project_slug' => $project['project_slug'],
                'locale_code' => $project['locale_code'],
                'completion_percentage' => $project['completion_percentage'] ?? 0,
                'last_updated' => $project['last_updated'] ?? null,
                'status' => $project['status'] ?? 'unknown'
            ];
        }
        
        return $report_data;
    }
    
    /**
     * Pierre generates teams report! 🪨
     * 
     * @since 1.0.0
     * @return array Teams report data
     */
    private function generate_teams_report(): array {
        $assignments = $this->user_project_link->get_all_assignments();
        $report_data = [
            'generated_at' => current_time('mysql'),
            'total_assignments' => count($assignments),
            'assignments' => []
        ];
        
        foreach ($assignments as $assignment) {
            $report_data['assignments'][] = [
                'user_id' => $assignment['user_id'],
                'project_slug' => $assignment['project_slug'],
                'locale_code' => $assignment['locale_code'],
                'role' => $assignment['role'],
                'assigned_by' => $assignment['assigned_by'],
                'assigned_at' => $assignment['assigned_at']
            ];
        }
        
        return $report_data;
    }
    
    /**
     * Pierre generates surveillance report! 🪨
     * 
     * @since 1.0.0
     * @return array Surveillance report data
     */
    private function generate_surveillance_report(): array {
        $surveillance_status = $this->project_watcher->get_surveillance_status();
        $report_data = [
            'generated_at' => current_time('mysql'),
            'surveillance_active' => $surveillance_status['active'] ?? false,
            'last_check' => $surveillance_status['last_check'] ?? null,
            'next_check' => $surveillance_status['next_check'] ?? null,
            'total_checks' => $surveillance_status['total_checks'] ?? 0,
            'successful_checks' => $surveillance_status['successful_checks'] ?? 0,
            'failed_checks' => $surveillance_status['failed_checks'] ?? 0
        ];
        
        return $report_data;
    }
    
    /**
     * Pierre generates notifications report! 🪨
     * 
     * @since 1.0.0
     * @return array Notifications report data
     */
    private function generate_notifications_report(): array {
        $settings = get_option('pierre_settings', []);
        $report_data = [
            'generated_at' => current_time('mysql'),
            'slack_configured' => !empty($settings['slack_webhook_url']),
            'notification_types' => $settings['notification_types'] ?? [],
            'notification_threshold' => $settings['notification_threshold'] ?? 5,
            'last_notification' => $settings['last_notification'] ?? null,
            'total_notifications_sent' => $settings['total_notifications_sent'] ?? 0
        ];
        
        return $report_data;
    }
    
    /**
     * Pierre schedules reports! 🪨
     * 
     * @since 1.0.0
     * @param string $frequency Schedule frequency
     * @param array $report_types Types of reports to schedule
     * @return array|false Schedule result or false on failure
     */
    private function schedule_reports(string $frequency, array $report_types): array|false {
        try {
            $schedule_data = [
                'frequency' => $frequency,
                'report_types' => $report_types,
                'scheduled_at' => current_time('mysql'),
                'next_run' => $this->calculate_next_run($frequency)
            ];
            
            // Pierre saves his schedule! 🪨
            update_option('pierre_report_schedule', $schedule_data);
            
            return $schedule_data;
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'error scheduling reports: ' . $e->getMessage(), ['source' => 'AdminController']);
            return false;
        }
    }
    
    /**
     * Pierre calculates next run time! 🪨
     * 
     * @since 1.0.0
     * @param string $frequency Schedule frequency
     * @return string Next run time
     */
    private function calculate_next_run(string $frequency): string {
        $intervals = [
            'daily' => DAY_IN_SECONDS,
            'weekly' => WEEK_IN_SECONDS,
            'monthly' => MONTH_IN_SECONDS
        ];
        
        $interval = $intervals[$frequency] ?? WEEK_IN_SECONDS;
        $next_run = time() + $interval;
        
        return gmdate('Y-m-d H:i:s', $next_run);
    }
    
    /**
     * Pierre performs security audit via AJAX! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_security_audit(): void {
        try {
            // Pierre validates nonce! 🪨
            if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
                wp_send_json_error(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
                return;
            }
            
            // Pierre checks permissions! 🪨
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Pierre says: Insufficient permissions!', 'wp-pierre') . ' 😢');
                return;
            }
            
            // Pierre performs comprehensive security audit! 🪨
            $audit_results = $this->security_auditor->perform_comprehensive_audit();
            
            wp_send_json_success([
                'message' => __('Pierre completed security audit!', 'wp-pierre') . ' 🪨',
                'audit_results' => $audit_results
            ]);
            
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'security audit error: ' . $e->getMessage(), ['source' => 'AdminController']);
            wp_send_json_error(__('Pierre says: Security audit failed!', 'wp-pierre') . ' 😢');
        }
    }
    
    /**
     * Pierre gets security logs via AJAX! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_security_logs(): void {
        try {
            // Pierre validates nonce! 🪨
            if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
                wp_send_json_error(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
                return;
            }
            
            // Pierre checks permissions! 🪨
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Pierre says: Insufficient permissions!', 'wp-pierre') . ' 😢');
                return;
            }
            
            // Pierre gets security logs! 🪨
            $limit = absint(wp_unslash($_POST['limit']) ?? 100);
            $event_type = sanitize_key(wp_unslash($_POST['event_type'] ?? ''));
            
            $security_logs = $this->csrf_protection->get_security_logs($limit, $event_type);
            
            wp_send_json_success([
                'message' => __('Pierre retrieved security logs!', 'wp-pierre') . ' 🪨',
                'security_logs' => $security_logs
            ]);
            
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'retrieve security logs error: ' . $e->getMessage(), ['source' => 'AdminController']);
            wp_send_json_error(__('Pierre says: Failed to retrieve security logs!', 'wp-pierre') . ' 😢');
        }
    }
    
    /**
     * Pierre clears security logs via AJAX! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_clear_security_logs(): void {
        try {
            // Pierre validates nonce! 🪨
            if (!check_ajax_referer('pierre_ajax', 'nonce', false)) {
                wp_send_json_error(__('Pierre says: Invalid nonce!', 'wp-pierre') . ' 😢');
                return;
            }
            
            // Pierre checks permissions! 🪨
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Pierre says: Insufficient permissions!', 'wp-pierre') . ' 😢');
                return;
            }
            
            // Pierre clears security logs! 🪨
            $event_type = sanitize_key(wp_unslash($_POST['event_type'] ?? ''));
            $success = $this->csrf_protection->clear_security_logs($event_type);
            
            if ($success) {
                wp_send_json_success([
                    'message' => __('Pierre cleared security logs!', 'wp-pierre') . ' 🪨'
                ]);
            } else {
                wp_send_json_error(__('Pierre says: Failed to clear security logs!', 'wp-pierre') . ' 😢');
            }
            
        } catch (\Exception $e) {
            do_action('wp_pierre_debug', 'clear security logs error: ' . $e->getMessage(), ['source' => 'AdminController']);
            wp_send_json_error(__('Pierre says: Failed to clear security logs!', 'wp-pierre') . ' 😢');
        }
    }
    
    /**
     * Pierre gets his admin controller status! 🪨
     * 
     * @since 1.0.0
     * @return array Admin controller status
     */
    public function get_status(): array {
        return [
            'menu_setup' => true,
            'ajax_handlers_setup' => true,
            'admin_hooks_setup' => true,
            'message' => 'Pierre\'s admin controller is ready! 🪨'
        ];
    }
}