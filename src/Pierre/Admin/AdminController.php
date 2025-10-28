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
     * Pierre's constructor - he prepares his admin interface! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->user_project_link = new UserProjectLink();
        $this->project_watcher = pierre()->get_project_watcher();
        $this->slack_notifier = pierre()->get_slack_notifier();
        $this->role_manager = new RoleManager();
    }
    
    /**
     * Pierre initializes his admin interface! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        try {
            // Pierre sets up his admin menu! 🪨
            $this->setup_admin_menu();
            
            // Pierre sets up his admin hooks! 🪨
            $this->setup_admin_hooks();
            
            // Pierre sets up his AJAX handlers! 🪨
            $this->setup_admin_ajax_handlers();
            
            error_log('Pierre initialized his admin interface! 🪨');
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error initializing admin interface: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre sets up his admin menu! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function setup_admin_menu(): void {
        // Pierre adds his main menu! 🪨
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Pierre adds his admin bar menu! 🪨
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
    }
    
    /**
     * Pierre adds his admin menu! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu(): void {
        // Pierre's main menu page! 🪨
        add_menu_page(
            'Pierre Dashboard 🪨',
            'Pierre 🪨',
            'wpupdates_view_dashboard',
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
            'wpupdates_view_dashboard',
            'pierre-dashboard',
            [$this, 'render_dashboard_page']
        );
        
        // Pierre's teams submenu! 🪨
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Teams',
            'Teams',
            'wpupdates_manage_teams',
            'pierre-teams',
            [$this, 'render_teams_page']
        );
        
        // Pierre's projects submenu! 🪨
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Projects',
            'Projects',
            'wpupdates_manage_projects',
            'pierre-projects',
            [$this, 'render_projects_page']
        );
        
        // Pierre's settings submenu! 🪨
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Settings',
            'Settings',
            'wpupdates_manage_settings',
            'pierre-settings',
            [$this, 'render_settings_page']
        );
        
        // Pierre's reports submenu! 🪨
        add_submenu_page(
            'pierre-dashboard',
            'Pierre Reports',
            'Reports',
            'wpupdates_view_reports',
            'pierre-reports',
            [$this, 'render_reports_page']
        );
    }
    
    /**
     * Pierre adds his admin bar menu! 🪨
     * 
     * @since 1.0.0
     * @param \WP_Admin_Bar $wp_admin_bar The admin bar object
     * @return void
     */
    public function add_admin_bar_menu(\WP_Admin_Bar $wp_admin_bar): void {
        if (!current_user_can('wpupdates_view_dashboard')) {
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
        
        // Pierre handles admin head! 🪨
        add_action('admin_head', [$this, 'add_admin_styles']);
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
        
        // Pierre handles project management AJAX! 🪨
        add_action('wp_ajax_pierre_start_surveillance', [$this, 'ajax_start_surveillance']);
        add_action('wp_ajax_pierre_stop_surveillance', [$this, 'ajax_stop_surveillance']);
        add_action('wp_ajax_pierre_test_surveillance', [$this, 'ajax_test_surveillance']);
        add_action('wp_ajax_pierre_add_project', [$this, 'ajax_add_project']);
        add_action('wp_ajax_pierre_remove_project', [$this, 'ajax_remove_project']);
        
        // Pierre handles settings AJAX! 🪨
        add_action('wp_ajax_pierre_flush_cache', [$this, 'ajax_flush_cache']);
        add_action('wp_ajax_pierre_reset_settings', [$this, 'ajax_reset_settings']);
        add_action('wp_ajax_pierre_clear_data', [$this, 'ajax_clear_data']);
    }
    
    /**
     * Pierre renders his dashboard page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_dashboard_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_view_dashboard')) {
            wp_die('Pierre says: You don\'t have permission to view this page! 😢');
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
        if (!current_user_can('wpupdates_manage_teams')) {
            wp_die('Pierre says: You don\'t have permission to view this page! 😢');
        }
        
        // Pierre gets his teams data! 🪨
        $teams_data = $this->get_admin_teams_data();
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('teams', $teams_data);
    }
    
    /**
     * Pierre renders his projects page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_projects_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_projects')) {
            wp_die('Pierre says: You don\'t have permission to view this page! 😢');
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
        if (!current_user_can('wpupdates_manage_settings')) {
            wp_die('Pierre says: You don\'t have permission to view this page! 😢');
        }
        
        // Pierre gets his settings data! 🪨
        $settings_data = $this->get_admin_settings_data();
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('settings', $settings_data);
    }
    
    /**
     * Pierre renders his reports page! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_reports_page(): void {
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_view_reports')) {
            wp_die('Pierre says: You don\'t have permission to view this page! 😢');
        }
        
        // Pierre gets his reports data! 🪨
        $reports_data = $this->get_admin_reports_data();
        
        // Pierre renders his template! 🪨
        $this->render_admin_template('reports', $reports_data);
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
            <h1>Pierre Admin - <?php echo esc_html(ucfirst($template_name)); ?> 🪨</h1>
            
            <div class="notice notice-info">
                <p><strong>Pierre says:</strong> This is a simple admin template for <?php echo esc_html($template_name); ?>! 
                The full template will be implemented in the next phase. 🪨</p>
            </div>
            
            <?php if (isset($data['stats'])): ?>
            <div class="pierre-admin-stats">
                <h2>Pierre's Statistics</h2>
                <div class="pierre-stats-grid">
                    <?php foreach ($data['stats'] as $stat): ?>
                    <div class="pierre-stat-box">
                        <div class="pierre-stat-number"><?php echo esc_html($stat['value']); ?></div>
                        <div class="pierre-stat-label"><?php echo esc_html($stat['label']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="pierre-admin-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-dashboard')); ?>" class="button button-primary">
                    Back to Dashboard 🪨
                </a>
                <a href="<?php echo esc_url(home_url('/pierre/')); ?>" class="button" target="_blank">
                    View Public Dashboard 🪨
                </a>
            </div>
        </div>
        
        <style>
        .pierre-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .pierre-stat-box {
            background: white;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            text-align: center;
        }
        .pierre-stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2271b1;
        }
        .pierre-stat-label {
            color: #666;
            margin-top: 5px;
        }
        .pierre-admin-actions {
            margin-top: 30px;
        }
        .pierre-admin-actions .button {
            margin-right: 10px;
        }
        </style>
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
     * Pierre adds admin styles! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_admin_styles(): void {
        $screen = get_current_screen();
        
        if (!$screen || strpos($screen->id, 'pierre') === false) {
            return;
        }
        
        ?>
        <style>
        .pierre-admin-header {
            background: linear-gradient(135deg, #2271b1, #135e96);
            color: white;
            padding: 20px;
            margin: -20px -20px 20px -20px;
            border-radius: 0 0 8px 8px;
        }
        .pierre-admin-header h1 {
            color: white;
            margin: 0;
        }
        .pierre-admin-card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .pierre-admin-card h2 {
            margin-top: 0;
            color: #2271b1;
        }
        </style>
        <?php
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
        return [
            'users' => get_users(['number' => 50]),
            'roles' => $this->role_manager->get_roles(),
            'capabilities' => $this->role_manager->get_capabilities(),
            'stats' => $this->get_teams_stats()
        ];
    }
    
    /**
     * Pierre gets admin projects data! 🪨
     * 
     * @since 1.0.0
     * @return array Admin projects data
     */
    private function get_admin_projects_data(): array {
        return [
            'watched_projects' => $this->project_watcher->get_watched_projects(),
            'surveillance_status' => $this->project_watcher->get_surveillance_status(),
            'stats' => $this->get_projects_stats()
        ];
    }
    
    /**
     * Pierre gets admin settings data! 🪨
     * 
     * @since 1.0.0
     * @return array Admin settings data
     */
    private function get_admin_settings_data(): array {
        $settings = get_option('pierre_settings', []);
        
        return [
            'settings' => $settings,
            'notifier_status' => $this->slack_notifier->get_status(),
            'cron_status' => pierre()->get_cron_manager()->get_surveillance_status()
        ];
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_admin_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_view_dashboard')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_admin_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_assign_projects')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $user_id = absint($_POST['user_id'] ?? 0);
        $project_type = sanitize_key($_POST['project_type'] ?? '');
        $project_slug = sanitize_key($_POST['project_slug'] ?? '');
        $locale_code = sanitize_key($_POST['locale_code'] ?? '');
        $role = sanitize_key($_POST['role'] ?? '');
        $assigned_by = get_current_user_id();
        
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_admin_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_assign_projects')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $user_id = absint($_POST['user_id'] ?? 0);
        $project_slug = sanitize_key($_POST['project_slug'] ?? '');
        $locale_code = sanitize_key($_POST['locale_code'] ?? '');
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
        // Pierre checks nonce! 🪨
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_admin_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_notifications')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $result = $this->slack_notifier->test_notification();
        wp_send_json_success(['test_result' => $result]);
    }
    
    /**
     * Pierre handles AJAX save settings! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_save_settings(): void {
        // Pierre checks nonce! 🪨
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_admin_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_settings')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $settings = [
            'slack_webhook_url' => sanitize_url($_POST['slack_webhook_url'] ?? ''),
            'surveillance_interval' => absint($_POST['surveillance_interval'] ?? 15),
            'notifications_enabled' => !empty($_POST['notifications_enabled'])
        ];
        
        update_option('pierre_settings', $settings);
        
        // Pierre updates his webhook URL! 🪨
        if (!empty($settings['slack_webhook_url'])) {
            $this->slack_notifier->set_webhook_url($settings['slack_webhook_url']);
        }
        
        wp_send_json_success(['message' => 'Pierre saved his settings! 🪨']);
    }
    
    /**
     * Pierre handles AJAX start surveillance! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_start_surveillance(): void {
        // Pierre checks nonce! 🪨
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_projects')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_projects')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_projects')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $result = $this->project_watcher->test_surveillance();
        wp_send_json_success(['message' => 'Pierre tested surveillance! 🪨', 'result' => $result]);
    }
    
    /**
     * Pierre handles AJAX add project! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_add_project(): void {
        // Pierre checks nonce! 🪨
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_projects')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $project_slug = sanitize_key($_POST['project_slug'] ?? '');
        $locale_code = sanitize_key($_POST['locale_code'] ?? '');
        
        if (empty($project_slug) || empty($locale_code)) {
            wp_send_json_error(['message' => 'Pierre says: Project slug and locale code are required! 😢']);
            return;
        }
        
        $result = $this->project_watcher->add_project_to_watch($project_slug, $locale_code);
        
        if ($result) {
            wp_send_json_success(['message' => 'Pierre added project to watch list! 🪨', 'result' => $result]);
        } else {
            wp_send_json_error(['message' => 'Pierre says: Failed to add project! 😢']);
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_projects')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $project_slug = sanitize_key($_POST['project_slug'] ?? '');
        $locale_code = sanitize_key($_POST['locale_code'] ?? '');
        
        if (empty($project_slug) || empty($locale_code)) {
            wp_send_json_error(['message' => 'Pierre says: Project slug and locale code are required! 😢']);
            return;
        }
        
        $result = $this->project_watcher->remove_project_from_watch($project_slug, $locale_code);
        
        if ($result) {
            wp_send_json_success(['message' => 'Pierre removed project from watch list! 🪨', 'result' => $result]);
        } else {
            wp_send_json_error(['message' => 'Pierre says: Failed to remove project! 😢']);
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_settings')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_settings')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('wpupdates_manage_settings')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        // Pierre clears his data! 🪨
        $this->project_watcher->clear_all_data();
        $this->user_project_link->clear_all_data();
        
        wp_send_json_success(['message' => 'Pierre cleared all his data! 🪨']);
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