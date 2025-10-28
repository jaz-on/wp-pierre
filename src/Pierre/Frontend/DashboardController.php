<?php
/**
 * Pierre's dashboard controller - he manages the public interface! 🪨
 * 
 * This class handles the public-facing dashboard for Pierre's
 * translation monitoring system with routing and template management.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Frontend;

use Pierre\Teams\UserProjectLink;
use Pierre\Surveillance\ProjectWatcher;
use Pierre\Notifications\SlackNotifier;

/**
 * Dashboard Controller class - Pierre's public interface! 🪨
 * 
 * @since 1.0.0
 */
class DashboardController {
    
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
     * Pierre's constructor - he prepares his public interface! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->user_project_link = new UserProjectLink();
        $this->project_watcher = pierre()->get_project_watcher();
        $this->slack_notifier = pierre()->get_slack_notifier();
    }
    
    /**
     * Pierre initializes his public interface! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function init(): void {
        try {
            // Pierre enqueues his public assets! 🪨
            add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
            
            // Pierre sets up his public routing! 🪨
            $this->setup_routing();
            
            // Pierre sets up his AJAX handlers! 🪨
            $this->setup_ajax_handlers();
            
            error_log('Pierre initialized his public interface! 🪨');
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error initializing public interface: ' . $e->getMessage() . ' 😢');
        }
    }
    
    /**
     * Pierre enqueues his public assets! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function enqueue_public_assets(): void {
        // Pierre enqueues his CSS! 🪨
        wp_enqueue_style(
            'pierre-public-css',
            PIERRE_PLUGIN_URL . 'assets/css/public.css',
            [],
            PIERRE_VERSION
        );
        
        // Pierre enqueues jQuery from WordPress! 🪨
        wp_enqueue_script('jquery');
        
        // Pierre enqueues his JavaScript! 🪨
        wp_enqueue_script(
            'pierre-public-js',
            PIERRE_PLUGIN_URL . 'assets/js/public.js',
            ['jquery'],
            PIERRE_VERSION,
            true
        );
        
        // Pierre localizes his script! 🪨
        wp_localize_script('pierre-public-js', 'pierreAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pierre_public_nonce'),
            'strings' => [
                'loading' => __('Loading...', 'wp-pierre'),
                'error' => __('An error occurred', 'wp-pierre'),
                'success' => __('Success!', 'wp-pierre')
            ]
        ]);
    }
    
    /**
     * Pierre sets up his public routing! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function setup_routing(): void {
        // Pierre adds his rewrite rules! 🪨
        add_action('init', [$this, 'add_rewrite_rules']);
        
        // Pierre handles his template redirect! 🪨
        add_action('template_redirect', [$this, 'handle_template_redirect']);
        
        // Pierre flushes rewrite rules on activation! 🪨
        add_action('wp_loaded', [$this, 'maybe_flush_rewrite_rules']);
    }
    
    /**
     * Pierre adds his rewrite rules! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_rewrite_rules(): void {
        // Pierre's main dashboard! 🪨
        add_rewrite_rule(
            '^pierre/?$',
            'index.php?pierre_page=dashboard',
            'top'
        );
        
        // Pierre's locale-specific dashboard! 🪨
        add_rewrite_rule(
            '^pierre/([^/]+)/?$',
            'index.php?pierre_page=locale&pierre_locale=$matches[1]',
            'top'
        );
        
        // Pierre's project-specific dashboard! 🪨
        add_rewrite_rule(
            '^pierre/([^/]+)/([^/]+)/?$',
            'index.php?pierre_page=project&pierre_locale=$matches[1]&pierre_project=$matches[2]',
            'top'
        );
    }
    
    /**
     * Pierre adds his query vars! 🪨
     * 
     * @since 1.0.0
     * @param array $vars Existing query vars
     * @return array Modified query vars
     */
    public function add_query_vars(array $vars): array {
        $vars[] = 'pierre_page';
        $vars[] = 'pierre_locale';
        $vars[] = 'pierre_project';
        return $vars;
    }
    
    /**
     * Pierre handles his template redirect! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function handle_template_redirect(): void {
        $pierre_page = get_query_var('pierre_page');
        
        if (!$pierre_page) {
            return;
        }
        
        // Pierre checks permissions! 🪨
        if (!$this->check_view_permissions()) {
            wp_die('Pierre says: You don\'t have permission to view this page! 😢', 'Access Denied', ['response' => 403]);
        }
        
        // Pierre handles different page types! 🪨
        switch ($pierre_page) {
            case 'dashboard':
                $this->render_dashboard();
                break;
                
            case 'locale':
                $locale = sanitize_key(get_query_var('pierre_locale'));
                $this->render_locale_dashboard($locale);
                break;
                
            case 'project':
                $locale = sanitize_key(get_query_var('pierre_locale'));
                $project = sanitize_key(get_query_var('pierre_project'));
                $this->render_project_dashboard($locale, $project);
                break;
                
            default:
                wp_die('Pierre says: Page not found! 😢', 'Page Not Found', ['response' => 404]);
        }
    }
    
    /**
     * Pierre renders his main dashboard! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function render_dashboard(): void {
        // Pierre gets his dashboard data! 🪨
        $dashboard_data = $this->get_dashboard_data();
        
        // Pierre sets his page title! 🪨
        add_filter('wp_title', function() {
            return 'Pierre Dashboard - WordPress Translation Monitor 🪨';
        });
        
        // Pierre renders his template! 🪨
        $this->render_template('dashboard', $dashboard_data);
    }
    
    /**
     * Pierre renders his locale dashboard! 🪨
     * 
     * @since 1.0.0
     * @param string $locale The locale code
     * @return void
     */
    private function render_locale_dashboard(string $locale): void {
        // Pierre gets his locale data! 🪨
        $locale_data = $this->get_locale_data($locale);
        
        if (!$locale_data) {
            wp_die('Pierre says: Locale not found! 😢', 'Locale Not Found', ['response' => 404]);
        }
        
        // Pierre sets his page title! 🪨
        add_filter('wp_title', function() use ($locale) {
            return "Pierre Dashboard - {$locale} 🪨";
        });
        
        // Pierre renders his template! 🪨
        $this->render_template('locale', $locale_data);
    }
    
    /**
     * Pierre renders his project dashboard! 🪨
     * 
     * @since 1.0.0
     * @param string $locale The locale code
     * @param string $project The project slug
     * @return void
     */
    private function render_project_dashboard(string $locale, string $project): void {
        // Pierre gets his project data! 🪨
        $project_data = $this->get_project_data($locale, $project);
        
        if (!$project_data) {
            wp_die('Pierre says: Project not found! 😢', 'Project Not Found', ['response' => 404]);
        }
        
        // Pierre sets his page title! 🪨
        add_filter('wp_title', function() use ($locale, $project) {
            return "Pierre Dashboard - {$project} ({$locale}) 🪨";
        });
        
        // Pierre renders his template! 🪨
        $this->render_template('project', $project_data);
    }
    
    /**
     * Pierre gets his dashboard data! 🪨
     * 
     * @since 1.0.0
     * @return array Dashboard data
     */
    private function get_dashboard_data(): array {
        $current_user_id = get_current_user_id();
        
        return [
            'user_id' => $current_user_id,
            'user_name' => wp_get_current_user()->display_name,
            'surveillance_status' => $this->project_watcher->get_surveillance_status(),
            'notifier_status' => $this->slack_notifier->get_status(),
            'user_assignments' => $current_user_id ? $this->user_project_link->get_user_assignments_with_details($current_user_id) : [],
            'watched_projects' => $this->project_watcher->get_watched_projects(),
            'stats' => $this->get_dashboard_stats()
        ];
    }
    
    /**
     * Pierre gets his locale data! 🪨
     * 
     * @since 1.0.0
     * @param string $locale The locale code
     * @return array|null Locale data or null if not found
     */
    private function get_locale_data(string $locale): ?array {
        $current_user_id = get_current_user_id();
        
        // Pierre gets projects for this locale! 🪨
        $projects = $this->get_projects_for_locale($locale);
        
        if (empty($projects)) {
            return null;
        }
        
        return [
            'locale' => $locale,
            'locale_name' => $this->get_locale_display_name($locale),
            'user_id' => $current_user_id,
            'user_name' => wp_get_current_user()->display_name,
            'projects' => $projects,
            'stats' => $this->get_locale_stats($projects)
        ];
    }
    
    /**
     * Pierre gets his project data! 🪨
     * 
     * @since 1.0.0
     * @param string $locale The locale code
     * @param string $project The project slug
     * @return array|null Project data or null if not found
     */
    private function get_project_data(string $locale, string $project): ?array {
        $current_user_id = get_current_user_id();
        
        // Pierre gets project assignments! 🪨
        $assignments = $this->user_project_link->get_project_assignments_with_details($project, $locale);
        
        if (empty($assignments)) {
            return null;
        }
        
        return [
            'project' => $project,
            'project_name' => $this->get_project_display_name($project, $locale),
            'locale' => $locale,
            'locale_name' => $this->get_locale_display_name($locale),
            'user_id' => $current_user_id,
            'user_name' => wp_get_current_user()->display_name,
            'assignments' => $assignments,
            'stats' => $this->get_project_stats($assignments)
        ];
    }
    
    /**
     * Pierre renders a template! 🪨
     * 
     * @since 1.0.0
     * @param string $template_name The template name
     * @param array $data The template data
     * @return void
     */
    private function render_template(string $template_name, array $data): void {
        // Pierre sets up his template data! 🪨
        $GLOBALS['pierre_template_data'] = $data;
        
        // Pierre includes his template! 🪨
        $template_path = PIERRE_PLUGIN_DIR . "templates/public/{$template_name}.php";
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Pierre creates a simple template! 🪨
            $this->render_simple_template($template_name, $data);
        }
        
        exit;
    }
    
    /**
     * Pierre renders a simple template! 🪨
     * 
     * @since 1.0.0
     * @param string $template_name The template name
     * @param array $data The template data
     * @return void
     */
    private function render_simple_template(string $template_name, array $data): void {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($data['page_title'] ?? 'Pierre Dashboard 🪨'); ?></title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f1f1f1; }
                .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #2271b1; margin: 0; }
                .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
                .stat-card { background: #f8f9fa; padding: 20px; border-radius: 6px; text-align: center; }
                .stat-number { font-size: 2em; font-weight: bold; color: #2271b1; }
                .stat-label { color: #666; margin-top: 5px; }
                .message { background: #e7f3ff; border: 1px solid #2271b1; padding: 15px; border-radius: 6px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Pierre Dashboard 🪨</h1>
                    <p>WordPress Translation Monitor</p>
                </div>
                
                <div class="message">
                    <strong>Pierre says:</strong> This is a simple template for <?php echo esc_html($template_name); ?>! 
                    The full template will be implemented in the next phase. 🪨
                </div>
                
                <?php if (isset($data['stats'])): ?>
                <div class="stats">
                    <?php foreach ($data['stats'] as $stat): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo esc_html($stat['value']); ?></div>
                        <div class="stat-label"><?php echo esc_html($stat['label']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="message">
                    <strong>Pierre's Status:</strong> 
                    <?php echo esc_html($data['surveillance_status']['message'] ?? 'Pierre is ready! 🪨'); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Pierre sets up his AJAX handlers! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    private function setup_ajax_handlers(): void {
        // Pierre handles AJAX requests! 🪨
        add_action('wp_ajax_pierre_get_stats', [$this, 'ajax_get_stats']);
        add_action('wp_ajax_pierre_get_projects', [$this, 'ajax_get_projects']);
        add_action('wp_ajax_pierre_test_notification', [$this, 'ajax_test_notification']);
    }
    
    /**
     * Pierre handles AJAX stats request! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_get_stats(): void {
        // Pierre checks nonce! 🪨
        if (!wp_verify_nonce(wp_unslash($_POST['nonce'] ?? ''), 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $stats = $this->get_dashboard_stats();
        wp_send_json_success($stats);
    }
    
    /**
     * Pierre handles AJAX projects request! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_get_projects(): void {
        // Pierre checks nonce! 🪨
        if (!wp_verify_nonce(wp_unslash($_POST['nonce'] ?? ''), 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_view_dashboard')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $projects = $this->project_watcher->get_watched_projects();
        wp_send_json_success($projects);
    }
    
    /**
     * Pierre handles AJAX test notification! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function ajax_test_notification(): void {
        // Pierre checks nonce! 🪨
        if (!wp_verify_nonce(wp_unslash($_POST['nonce'] ?? ''), 'pierre_ajax')) {
            wp_die('Pierre says: Invalid nonce! 😢');
        }
        
        // Pierre checks permissions! 🪨
        if (!current_user_can('pierre_manage_notifications')) {
            wp_die('Pierre says: You don\'t have permission! 😢');
        }
        
        $result = $this->slack_notifier->test_notification();
        wp_send_json_success(['test_result' => $result]);
    }
    
    /**
     * Pierre checks view permissions! 🪨
     * 
     * @since 1.0.0
     * @return bool True if user can view, false otherwise
     */
    private function check_view_permissions(): bool {
        return current_user_can('pierre_view_dashboard');
    }
    
    /**
     * Pierre gets dashboard statistics! 🪨
     * 
     * @since 1.0.0
     * @return array Dashboard statistics
     */
    private function get_dashboard_stats(): array {
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
     * Pierre gets projects for a locale! 🪨
     * 
     * @since 1.0.0
     * @param string $locale The locale code
     * @return array Projects for the locale
     */
    private function get_projects_for_locale(string $locale): array {
        // Pierre will implement this in future phases! 🪨
        return [];
    }
    
    /**
     * Pierre gets locale statistics! 🪨
     * 
     * @since 1.0.0
     * @param array $projects The projects for the locale
     * @return array Locale statistics
     */
    private function get_locale_stats(array $projects): array {
        return [
            [
                'label' => 'Projects',
                'value' => count($projects)
            ]
        ];
    }
    
    /**
     * Pierre gets project statistics! 🪨
     * 
     * @since 1.0.0
     * @param array $assignments The project assignments
     * @return array Project statistics
     */
    private function get_project_stats(array $assignments): array {
        return [
            [
                'label' => 'Team Members',
                'value' => count($assignments)
            ]
        ];
    }
    
    /**
     * Pierre gets locale display name! 🪨
     * 
     * @since 1.0.0
     * @param string $locale The locale code
     * @return string The locale display name
     */
    private function get_locale_display_name(string $locale): string {
        return strtoupper($locale);
    }
    
    /**
     * Pierre gets project display name! 🪨
     * 
     * @since 1.0.0
     * @param string $project The project slug
     * @param string $locale The locale code
     * @return string The project display name
     */
    private function get_project_display_name(string $project, string $locale): string {
        return ucfirst($project) . " ({$locale})";
    }
    
    /**
     * Pierre flushes rewrite rules if needed! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function maybe_flush_rewrite_rules(): void {
        if (get_option('pierre_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('pierre_flush_rewrite_rules');
        }
    }
    
    /**
     * Pierre gets his dashboard controller status! 🪨
     * 
     * @since 1.0.0
     * @return array Dashboard controller status
     */
    public function get_status(): array {
        return [
            'routing_setup' => true,
            'ajax_handlers_setup' => true,
            'message' => 'Pierre\'s dashboard controller is ready! 🪨'
        ];
    }
}