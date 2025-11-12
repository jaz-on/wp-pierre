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

use Pierre\Settings\Settings;
use Pierre\Admin\Handlers\ProjectsHandler;
use Pierre\Admin\Handlers\CatalogHandler;
use Pierre\Admin\Handlers\LocalesHandler;
use Pierre\Admin\Handlers\TeamsHandler;
use Pierre\Admin\Handlers\SettingsHandler;
use Pierre\Admin\Handlers\DashboardHandler;

use Pierre\Teams\UserProjectLink;
use Pierre\Surveillance\ProjectWatcher;
use Pierre\Notifications\SlackNotifier;
use Pierre\Teams\RoleManager;
use Pierre\Security\SecurityManager;
use Pierre\Security\CSRFProtection;
use Pierre\Security\SecurityAuditor;
use Pierre\Container;

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
	 * Projects handler.
	 *
	 * @var ProjectsHandler
	 */
	private ProjectsHandler $projects_handler;

	/**
	 * Catalog handler.
	 *
	 * @var CatalogHandler
	 */
	private CatalogHandler $catalog_handler;

	/**
	 * Locales handler.
	 *
	 * @var LocalesHandler
	 */
	private LocalesHandler $locales_handler;

	/**
	 * Teams handler.
	 *
	 * @var TeamsHandler
	 */
	private TeamsHandler $teams_handler;

	/**
	 * Settings handler.
	 *
	 * @var SettingsHandler
	 */
	private SettingsHandler $settings_handler;

	/**
	 * Dashboard handler.
	 *
	 * @var DashboardHandler
	 */
	private DashboardHandler $dashboard_handler;

	/**
	 * Container for dependency injection.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Pierre's constructor - he prepares his admin interface! 🪨
	 *
	 * @since 1.0.0
	 * @param Container|null        $container Dependency injection container.
	 * @param ProjectsHandler|null  $projects_handler Projects handler (optional, resolved from container if null).
	 * @param CatalogHandler|null   $catalog_handler Catalog handler (optional, resolved from container if null).
	 * @param LocalesHandler|null   $locales_handler Locales handler (optional, resolved from container if null).
	 * @param TeamsHandler|null     $teams_handler Teams handler (optional, resolved from container if null).
	 * @param SettingsHandler|null  $settings_handler Settings handler (optional, resolved from container if null).
	 * @param DashboardHandler|null $dashboard_handler Dashboard handler (optional, resolved from container if null).
	 */
	public function __construct(
		?Container $container = null,
		?ProjectsHandler $projects_handler = null,
		?CatalogHandler $catalog_handler = null,
		?LocalesHandler $locales_handler = null,
		?TeamsHandler $teams_handler = null,
		?SettingsHandler $settings_handler = null,
		?DashboardHandler $dashboard_handler = null
	) {
		// Use container from Plugin if available, otherwise create a new one.
		$this->container = $container ?? ( function_exists( 'pierre' ) && method_exists( pierre(), 'get_container' ) ? pierre()->get_container() : new Container() );

		// Resolve dependencies from container or use provided instances.
		$this->security_manager = $this->container->get( SecurityManager::class );
		$this->csrf_protection  = $this->container->get( CSRFProtection::class );

		// Resolve legacy dependencies via container for DI.
		$this->user_project_link = $this->container->get( UserProjectLink::class );
		$this->project_watcher   = $this->container->get( ProjectWatcher::class );
		$this->slack_notifier    = $this->container->get( SlackNotifier::class );
		$this->role_manager      = $this->container->get( RoleManager::class );
		$this->security_auditor  = $this->container->get( SecurityAuditor::class );

		// Initialize handlers with dependencies from container.
		$this->projects_handler  = $projects_handler ?? $this->container->get( ProjectsHandler::class );
		$this->catalog_handler   = $catalog_handler ?? $this->container->get( CatalogHandler::class );
		$this->locales_handler   = $locales_handler ?? $this->container->get( LocalesHandler::class );
		$this->teams_handler     = $teams_handler ?? $this->container->get( TeamsHandler::class );
		$this->settings_handler  = $settings_handler ?? $this->container->get( SettingsHandler::class );
		$this->dashboard_handler = $dashboard_handler ?? $this->container->get( DashboardHandler::class );
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
			if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
				$act = isset( $_REQUEST['action'] ) ? (string) wp_unslash( $_REQUEST['action'] ) : '';
				if ( is_string( $act ) && strpos( $act, 'pierre_' ) === 0 ) {
					do_action(
						'wp_pierre_debug',
						'ajax start',
						array(
							'scope'  => 'admin',
							'action' => $act,
						)
					);
					register_shutdown_function(
						function () use ( $act ) {
							$code = function_exists( 'http_response_code' ) ? (int) http_response_code() : 200;
							do_action(
								'wp_pierre_debug',
								'ajax end',
								array(
									'scope'  => 'admin',
									'action' => $act,
									'code'   => $code,
								)
							);
						}
					);
				}
			}

			$this->log_debug( 'Pierre initialized his admin interface! 🪨' );
		} catch ( \Exception $e ) {
			$this->log_debug( 'Pierre encountered an error initializing admin interface: ' . $e->getMessage() . ' 😢' );
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
		// Pierre's main menu page! 🪨
		$ui_settings = Settings::all();
		$plugin_name = isset( $ui_settings['ui']['plugin_name'] ) && is_string( $ui_settings['ui']['plugin_name'] ) && $ui_settings['ui']['plugin_name'] !== '' ? (string) $ui_settings['ui']['plugin_name'] : 'Pierre';
		add_menu_page(
			esc_html( $plugin_name . ' Dashboard' ),
			esc_html( $plugin_name ),
			'pierre_view_dashboard',
			'pierre-dashboard',
			array( $this, 'render_dashboard_page' ),
			'dashicons-translation',
			30
		);

		// Pierre's dashboard submenu! 🪨
		add_submenu_page(
			'pierre-dashboard',
			esc_html( $plugin_name . ' Dashboard' ),
			esc_html__( 'Dashboard', 'wp-pierre' ),
			'pierre_view_dashboard',
			'pierre-dashboard',
			array( $this, 'render_dashboard_page' )
		);

		// Pierre's locales submenu! (new, second)
		add_submenu_page(
			'pierre-dashboard',
			esc_html( $plugin_name . ' Locales' ),
			esc_html__( 'Locales', 'wp-pierre' ),
			'pierre_view_dashboard',
			'pierre-locales',
			array( $this, 'render_locales_page' )
		);

		// Pierre's locale view page (hidden from menu but accessible via URL)
		add_submenu_page(
			'pierre-dashboard',
			esc_html( $plugin_name . ' Locale View' ),
			esc_html__( 'Locale View', 'wp-pierre' ), // Hidden via CSS
			'pierre_view_dashboard',
			'pierre-locale-view',
			array( $this, 'render_locale_view_page' )
		);

		// Pierre's projects submenu! (third)
		add_submenu_page(
			'pierre-dashboard',
			esc_html( $plugin_name . ' Projects' ),
			esc_html__( 'Projects', 'wp-pierre' ),
			'pierre_view_dashboard',
			'pierre-projects',
			array( $this, 'render_projects_page' )
		);

		// Pierre's teams submenu! (fourth)
		add_submenu_page(
			'pierre-dashboard',
			esc_html( $plugin_name . ' Teams' ),
			esc_html__( 'Teams', 'wp-pierre' ),
			'pierre_view_dashboard',
			'pierre-teams',
			array( $this, 'render_teams_page' )
		);

		// Pierre's reports submenu! (fifth)
		add_submenu_page(
			'pierre-dashboard',
			esc_html( $plugin_name . ' Reports' ),
			esc_html__( 'Reports', 'wp-pierre' ),
			'pierre_view_dashboard',
			'pierre-reports',
			array( $this, 'render_reports_page' )
		);

		// Settings (last)
		add_submenu_page(
			'pierre-dashboard',
			esc_html( $plugin_name . ' Settings' ),
			esc_html__( 'Settings', 'wp-pierre' ),
			'pierre_manage_settings',
			'pierre-settings',
			array( $this, 'render_settings_page' )
		);

		// Settings are only under the Pierre menu for consistency
	}

	/**
	 * Hide locale-view page from menu visually (but keep it in system for access)
	 * Uses CSS to hide instead of removing from menu system
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function hide_locale_view_menu(): void {
		// Add CSS to hide the menu item visually without removing it from system
		add_action(
			'admin_head',
			function () {
				echo '<style>#toplevel_page_pierre-dashboard .wp-submenu li a[href*="page=pierre-locale-view"] { display: none !important; }</style>';
			},
			999
		);
	}

	/**
	 * Pierre adds his admin bar menu! 🪨
	 *
	 * @since 1.0.0
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar object.
	 * @return void
	 */
	public function add_admin_bar_menu( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! current_user_can( 'pierre_view_dashboard' ) ) {
			return;
		}

		// Pierre's main admin bar menu! 🪨
		$settings    = Settings::all();
		$icon_choice = isset( $settings['ui']['menu_icon'] ) ? (string) $settings['ui']['menu_icon'] : 'emoji';
		$plugin_name = isset( $settings['ui']['plugin_name'] ) && is_string( $settings['ui']['plugin_name'] ) && $settings['ui']['plugin_name'] !== '' ? (string) $settings['ui']['plugin_name'] : 'Pierre';
		$menu_title  = ( $icon_choice === 'emoji' )
			? ( '🪨 ' . esc_html( $plugin_name ) )
			: ( '<span class="ab-icon dashicons-translation" aria-hidden="true"></span><span class="ab-label">' . esc_html( $plugin_name ) . '</span>' );
		$menu_meta   = array( 'title' => esc_html( $plugin_name . ' Dashboard' ) );
		if ( $icon_choice === 'dashicons' ) {
			$menu_meta['html'] = true; }
		$wp_admin_bar->add_node(
			array(
				'id'    => 'pierre-admin',
				'title' => $menu_title,
				'href'  => admin_url( 'admin.php?page=pierre-dashboard' ),
				'meta'  => $menu_meta,
			)
		);

		// Pierre's dashboard link! 🪨
		$wp_admin_bar->add_node(
			array(
				'id'     => 'pierre-dashboard',
				'parent' => 'pierre-admin',
				'title'  => __( 'Dashboard', 'wp-pierre' ),
				'href'   => admin_url( 'admin.php?page=pierre-dashboard' ),
				'meta'   => array(
					'title' => esc_html( $plugin_name . ' Dashboard' ),
				),
			)
		);

		// Pierre's public dashboard link! 🪨
		$wp_admin_bar->add_node(
			array(
				'id'     => 'pierre-public',
				'parent' => 'pierre-admin',
				'title'  => 'Public Dashboard',
				'href'   => home_url( '/pierre/' ),
				'meta'   => array(
					'title'  => esc_html( $plugin_name . ' Public Dashboard' ),
					'target' => '_blank',
				),
			)
		);
	}

	/**
	 * Pierre sets up his admin hooks! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_admin_hooks(): void {
		// Pierre handles admin notices! 🪨
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );

		// Pierre handles admin footer! 🪨
		add_filter( 'admin_footer_text', array( $this, 'modify_admin_footer' ) );

		// Pierre enqueues his admin scripts! 🪨
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Pierre adds contextual help tabs on his screens! 🪨
		add_action( 'current_screen', array( $this, 'register_help_tabs' ) );
	}

	/**
	 * Whether verbose debug logging is enabled.
	 */
	private function is_debug(): bool {
		return defined( 'PIERRE_DEBUG' ) ? (bool) PIERRE_DEBUG : false;
	}

	/**
	 * Log a debug message if debug is enabled.
	 *
	 * @param string $message Debug message.
	 * @return void
	 */
	private function log_debug( string $message ): void {
		if ( $this->is_debug() ) {
			do_action( 'wp_pierre_debug', $message, array( 'source' => 'AdminController' ) );
		}
	}

	/**
	 * Require nonce and capability for state-changing AJAX actions.
	 *
	 * @return void
	 */
	private function require_manage_permission(): void {
		if ( function_exists( 'check_admin_referer' ) ) {
			check_admin_referer( 'pierre_action_nonce' );
		}
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
	}

	/**
	 * Get cached list of available translations (memo via transient to avoid remote calls).
	 * Cache TTL: 12 hours.
	 */
	private function get_cached_translations(): array {
		$cache_key    = 'pierre_wp_available_translations';
		$translations = get_transient( $cache_key );
		if ( $translations === false ) {
			$translations = function_exists( 'wp_get_available_translations' ) ? wp_get_available_translations() : array();
			set_transient( $cache_key, is_array( $translations ) ? $translations : array(), 12 * HOUR_IN_SECONDS );
		}
		return is_array( $translations ) ? $translations : array();
	}

	/**
	 * Pierre registers contextual help tabs for his admin pages! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_help_tabs(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! current_user_can( 'pierre_view_dashboard' ) ) {
			return; }
		$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! in_array( $current_page, array( 'pierre-dashboard', 'pierre-locales', 'pierre-locale-view', 'pierre-projects', 'pierre-teams', 'pierre-reports', 'pierre-settings' ), true ) ) {
			return;
		}
		// Overview help
		$screen->add_help_tab(
			array(
				'id'      => 'pierre_overview',
				'title'   => __( 'Overview', 'wp-pierre' ),
				'content' => '<p>' . esc_html__( 'Pierre monitors locales/projects and notifies via Slack. Use Settings for global rules and Locale view for overrides.', 'wp-pierre' ) . '</p>',
			)
		);
		// Best practices help
		$screen->add_help_tab(
			array(
				'id'      => 'pierre_best_practices',
				'title'   => __( 'Best practices', 'wp-pierre' ),
				'content' => '<ul><li>' . esc_html__( 'Start by adding locales, then add projects.', 'wp-pierre' ) . '</li><li>' . esc_html__( 'Prefer digest mode to reduce noise.', 'wp-pierre' ) . '</li></ul>',
			)
		);
		// Sidebar resources
		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'Resources', 'wp-pierre' ) . '</strong></p>' .
			'<p><a href="' . esc_url( admin_url( 'admin.php?page=pierre-settings' ) ) . '">' . esc_html__( 'Settings', 'wp-pierre' ) . '</a></p>' .
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
		$screen         = get_current_screen();
		$current_page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$is_pierre_page = (
			( $screen && strpos( $screen->id ?? '', 'pierre' ) !== false )
			|| in_array(
				$current_page,
				array(
					'pierre-projects',
					'pierre-settings',
					'pierre-teams',
					'pierre-dashboard',
					'pierre-reports',
					'pierre-locales',
					'pierre-locale-view',
				),
				true
			)
		);
		if ( ! $is_pierre_page ) {
			return; }

		wp_enqueue_script(
			'wp-pierre-admin',
			PIERRE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			PIERRE_VERSION,
			true
		);

		wp_localize_script(
			'wp-pierre-admin',
			'pierreAdminL10n',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'pierre_admin_ajax' ),
				'nonceAjax'     => wp_create_nonce( 'pierre_ajax' ), // For handlers using pierre_ajax
				'dryRunSuccess' => __( 'Dry run succeeded. You can now start surveillance.', 'wp-pierre' ),
				'dryRunFailed'  => __( 'Dry run failed. Check settings and try again.', 'wp-pierre' ),
				'dryRunError'   => __( 'An error occurred during dry run.', 'wp-pierre' ),
				'catalog'       => array(
					'loading'        => __( 'Loading…', 'wp-pierre' ),
					'total'          => __( 'Total', 'wp-pierre' ),
					'page'           => __( 'Page', 'wp-pierre' ),
					'ok'             => __( 'OK', 'wp-pierre' ),
					'fail'           => __( 'Failed.', 'wp-pierre' ),
					'neterr'         => __( 'Network error.', 'wp-pierre' ),
					'nores'          => __( 'No results', 'wp-pierre' ),
					'chooseLocale'   => __( 'Choose a locale first.', 'wp-pierre' ),
					'alreadyWatched' => __( 'Already watched', 'wp-pierre' ),
					'watched'        => __( 'watched', 'wp-pierre' ),
				),
			)
		);
	}

	/**
	 * Pierre sets up his admin AJAX handlers! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_admin_ajax_handlers(): void {
		// Pierre handles admin AJAX requests! 🪨
		add_action( 'wp_ajax_pierre_admin_get_stats', array( $this->dashboard_handler, 'ajax_get_admin_stats' ) );
		add_action( 'wp_ajax_pierre_admin_assign_user', array( $this->teams_handler, 'ajax_assign_user' ) );
		add_action( 'wp_ajax_pierre_admin_remove_user', array( $this->teams_handler, 'ajax_remove_user' ) );
		add_action( 'wp_ajax_pierre_admin_test_notification', array( $this->settings_handler, 'ajax_test_notification' ) );
		add_action( 'wp_ajax_pierre_admin_save_settings', array( $this->settings_handler, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_pierre_run_surveillance_now', array( $this, 'ajax_run_surveillance_now' ) );
		add_action( 'wp_ajax_pierre_save_locale_overrides', array( $this->settings_handler, 'ajax_save_locale_overrides' ) );

		// Pierre handles project management AJAX! 🪨
		add_action( 'wp_ajax_pierre_start_surveillance', array( $this->projects_handler, 'ajax_start_surveillance' ) );
		add_action( 'wp_ajax_pierre_stop_surveillance', array( $this->projects_handler, 'ajax_stop_surveillance' ) );
		add_action( 'wp_ajax_pierre_test_surveillance', array( $this->projects_handler, 'ajax_test_surveillance' ) );
		add_action( 'wp_ajax_pierre_add_project', array( $this->projects_handler, 'ajax_add_project' ) );
		add_action( 'wp_ajax_pierre_save_locale_slack', array( $this->locales_handler, 'ajax_save_locale_slack' ) );
		add_action( 'wp_ajax_pierre_save_locale_webhook', array( $this->locales_handler, 'ajax_save_locale_webhook' ) );
		add_action( 'wp_ajax_pierre_remove_project', array( $this->projects_handler, 'ajax_remove_project' ) );

		// Pierre handles locales AJAX! 🪨
		add_action( 'wp_ajax_pierre_add_locales', array( $this->locales_handler, 'ajax_add_locales' ) );
		add_action( 'wp_ajax_pierre_fetch_locales', array( $this->locales_handler, 'ajax_fetch_locales' ) );
		add_action( 'wp_ajax_pierre_save_projects_discovery', array( $this, 'ajax_save_projects_discovery' ) );
		add_action( 'wp_ajax_pierre_bulk_add_from_discovery', array( $this, 'ajax_bulk_add_from_discovery' ) );
		add_action( 'wp_ajax_pierre_bulk_preview_from_discovery', array( $this, 'ajax_bulk_preview_from_discovery' ) );
		add_action( 'wp_ajax_pierre_bulk_add_projects_to_locale', array( $this, 'ajax_bulk_add_projects_to_locale' ) );

		// Pierre handles settings AJAX! 🪨
		add_action( 'wp_ajax_pierre_flush_cache', array( $this, 'ajax_flush_cache' ) );
		add_action( 'wp_ajax_pierre_reset_settings', array( $this, 'ajax_reset_settings' ) );
		add_action( 'wp_ajax_pierre_clear_data', array( $this, 'ajax_clear_data' ) );

		// Pierre handles reports AJAX! 🪨
		add_action( 'wp_ajax_pierre_export_report', array( $this, 'ajax_export_report' ) );
		add_action( 'wp_ajax_pierre_export_all_reports', array( $this, 'ajax_export_all_reports' ) );
		add_action( 'wp_ajax_pierre_schedule_reports', array( $this, 'ajax_schedule_reports' ) );
		// Run now actions
		add_action( 'wp_ajax_pierre_run_surveillance_now', array( $this->projects_handler, 'ajax_run_surveillance_now' ) );
		add_action( 'wp_ajax_pierre_run_cleanup_now', array( $this, 'ajax_run_cleanup_now' ) );
		// Locales cache exports
		add_action( 'wp_ajax_pierre_export_locales_json', array( $this, 'ajax_export_locales_json' ) );
		add_action( 'wp_ajax_pierre_export_locales_csv', array( $this, 'ajax_export_locales_csv' ) );
		add_action( 'wp_ajax_pierre_check_locale_status', array( $this->locales_handler, 'ajax_check_locale_status' ) );
		add_action( 'wp_ajax_pierre_clear_locale_log', array( $this->locales_handler, 'ajax_clear_locale_log' ) );
		add_action( 'wp_ajax_pierre_export_locale_log', array( $this->locales_handler, 'ajax_export_locale_log' ) );
		add_action( 'wp_ajax_pierre_abort_run', array( $this, 'ajax_abort_run' ) );
		add_action( 'wp_ajax_pierre_get_progress', array( $this->projects_handler, 'ajax_get_progress' ) );
		// Progress + abort controls
		add_action( 'wp_ajax_pierre_abort_surveillance_run', array( $this->projects_handler, 'ajax_abort_surveillance_run' ) );
		add_action( 'wp_ajax_pierre_get_surveillance_errors', array( $this->projects_handler, 'ajax_get_surveillance_errors' ) );
		add_action( 'wp_ajax_pierre_clear_surveillance_errors', array( $this->projects_handler, 'ajax_clear_surveillance_errors' ) );
		add_action( 'wp_ajax_pierre_export_errors_json', array( $this->projects_handler, 'ajax_export_errors_json' ) );
		add_action( 'wp_ajax_pierre_export_errors_csv', array( $this->projects_handler, 'ajax_export_errors_csv' ) );
		add_action( 'wp_ajax_pierre_get_error_stats', array( $this->projects_handler, 'ajax_get_error_stats' ) );
		// Projects catalog (admin)
		add_action( 'wp_ajax_pierre_admin_rebuild_catalog', array( $this->catalog_handler, 'ajax_rebuild_catalog' ) );
		add_action( 'wp_ajax_pierre_admin_fetch_catalog', array( $this->catalog_handler, 'ajax_fetch_catalog' ) );
		add_action( 'wp_ajax_pierre_admin_save_catalog_settings', array( $this->catalog_handler, 'ajax_save_catalog_settings' ) );
		add_action( 'wp_ajax_pierre_admin_get_catalog_status', array( $this->catalog_handler, 'ajax_get_catalog_status' ) );
		add_action( 'wp_ajax_pierre_admin_schedule_catalog', array( $this->catalog_handler, 'ajax_schedule_catalog' ) );
		add_action( 'wp_ajax_pierre_admin_inspect_catalog', array( $this->catalog_handler, 'ajax_inspect_catalog' ) );
		add_action( 'wp_ajax_pierre_add_from_catalog', array( $this->catalog_handler, 'ajax_add_from_catalog' ) );
		add_action( 'wp_ajax_pierre_admin_get_catalog_stats', array( $this->catalog_handler, 'ajax_get_catalog_stats' ) );
		add_action( 'wp_ajax_pierre_admin_export_catalog_json', array( $this->catalog_handler, 'ajax_export_catalog_json' ) );
		add_action( 'wp_ajax_pierre_admin_export_catalog_csv', array( $this->catalog_handler, 'ajax_export_catalog_csv' ) );
		add_action( 'wp_ajax_pierre_admin_purge_catalog', array( $this->catalog_handler, 'ajax_purge_catalog' ) );
		add_action( 'wp_ajax_pierre_admin_get_catalog_progress', array( $this->catalog_handler, 'ajax_get_catalog_progress' ) );
		add_action( 'wp_ajax_pierre_admin_get_catalog_errors', array( $this->catalog_handler, 'ajax_get_catalog_errors' ) );
		// Lazy-load Settings: Projects Catalog Browser markup
		add_action(
			'wp_ajax_pierre_admin_render_catalog_browser',
			function () {
				$this->catalog_handler->ajax_render_catalog_browser( array( $this, 'get_admin_settings_data' ) );
			}
		);
		// Locale view: users search/pagination for managers
		add_action( 'wp_ajax_pierre_search_users_for_locale', array( $this->teams_handler, 'ajax_search_users_for_locale' ) );
		add_action( 'wp_ajax_pierre_admin_export_catalog_errors_json', array( $this->catalog_handler, 'ajax_export_catalog_errors_json' ) );
		add_action( 'wp_ajax_pierre_admin_export_catalog_errors_csv', array( $this->catalog_handler, 'ajax_export_catalog_errors_csv' ) );
		add_action( 'wp_ajax_pierre_admin_reset_catalog', array( $this->catalog_handler, 'ajax_reset_catalog' ) );
		add_action( 'wp_ajax_pierre_admin_catalog_export_to_library', array( $this->catalog_handler, 'ajax_catalog_export_to_library' ) );
		add_action( 'wp_ajax_pierre_admin_catalog_import_from_library', array( $this->catalog_handler, 'ajax_catalog_import_from_library' ) );

		// Pierre handles locale managers (admin-only) 🪨
		add_action( 'wp_ajax_pierre_save_locale_managers', array( $this, 'ajax_save_locale_managers' ) );
	}
	private function rate_limit( string $key, int $limitPerMinute = 30 ): void {
		$uid = get_current_user_id() ?: 0;
		$k   = 'pierre_rl_' . md5( $key . '|' . $uid . '|' . $_SERVER['REMOTE_ADDR'] );
		$c   = (int) get_transient( $k );
		if ( $c >= $limitPerMinute ) {
			$this->respond_error( 'rate_limited', __( 'Too many requests. Please try again later.', 'wp-pierre' ), 429 ); }
		set_transient( $k, $c + 1, MINUTE_IN_SECONDS );
	}

	/** Catalog stats */
	public function ajax_get_catalog_stats(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$meta  = get_option( 'pierre_projects_catalog_meta', array() );
		$index = is_array( $meta['index'] ?? null ) ? $meta['index'] : array();
		$stats = array(
			'last_built' => (int) ( $meta['last_built'] ?? 0 ),
			'next_build' => (int) ( $meta['next_build'] ?? 0 ),
			'entries'    => array(),
		);
		foreach ( $index as $k => $st ) {
			$stats['entries'][ $k ] = array(
				'last_page' => (int) ( $st['last_page'] ?? 0 ),
				'per_page'  => (int) ( $st['per_page'] ?? 0 ),
				'total'     => (int) ( $st['total'] ?? 0 ),
			);
		}
		$errs                  = get_option( 'pierre_projects_catalog_errors', array() );
		$stats['errors_count'] = is_array( $errs ) ? count( $errs ) : 0;
		wp_send_json_success( $stats );
	}

	/** Export JSON */
	public function ajax_export_catalog_json(): void {
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied.', 'wp-pierre' ) ); }
		$args  = array(
			'type'     => isset( $_POST['type'] ) ? (array) wp_unslash( $_POST['type'] ) : array( 'plugin' ),
			'search'   => (string) ( wp_unslash( $_POST['search'] ?? '' ) ),
			'page'     => (int) ( wp_unslash( $_POST['page'] ?? 1 ) ),
			'per_page' => (int) ( wp_unslash( $_POST['per_page'] ?? 100 ) ),
			'sort'     => (string) ( wp_unslash( $_POST['sort'] ?? '' ) ),
			'source'   => (string) ( wp_unslash( $_POST['source'] ?? 'popular' ) ),
		);
		$svc   = new \Pierre\Discovery\ProjectsCatalog();
		$out   = $svc->fetch( $args );
		$t     = (array) ( $args['type'] ?? array( 'plugin' ) );
		$tstr  = implode( '-', array_map( 'sanitize_key', $t ) );
		$src   = sanitize_key( $args['source'] ?? 'popular' );
		$date  = gmdate( 'Y-m-d' );
		$fname = sprintf( 'pierre_catalog_%s_%s_%s.json', $tstr ?: 'all', $src ?: 'popular', $date );
		nocache_headers();
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '"' );
		echo wp_json_encode( $out );
		exit;
	}

	/** Export CSV */
	public function ajax_export_catalog_csv(): void {
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied.', 'wp-pierre' ) ); }
		$args  = array(
			'type'     => isset( $_POST['type'] ) ? (array) wp_unslash( $_POST['type'] ) : array( 'plugin' ),
			'search'   => (string) ( wp_unslash( $_POST['search'] ?? '' ) ),
			'page'     => (int) ( wp_unslash( $_POST['page'] ?? 1 ) ),
			'per_page' => (int) ( wp_unslash( $_POST['per_page'] ?? 100 ) ),
			'sort'     => (string) ( wp_unslash( $_POST['sort'] ?? '' ) ),
			'source'   => (string) ( wp_unslash( $_POST['source'] ?? 'popular' ) ),
		);
		$svc   = new \Pierre\Discovery\ProjectsCatalog();
		$out   = $svc->fetch( $args );
		$items = (array) ( $out['items'] ?? array() );
		$t     = (array) ( $args['type'] ?? array( 'plugin' ) );
		$tstr  = implode( '-', array_map( 'sanitize_key', $t ) );
		$src   = sanitize_key( $args['source'] ?? 'popular' );
		$date  = gmdate( 'Y-m-d' );
		$fname = sprintf( 'pierre_catalog_%s_%s_%s.csv', $tstr ?: 'all', $src ?: 'popular', $date );
		nocache_headers();
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '"' );
		$f = fopen( 'php://output', 'w' );
		fputcsv( $f, array( 'type', 'slug', 'name', 'tags', 'installs', 'updated' ) );
		foreach ( $items as $it ) {
			fputcsv( $f, array( $it['type'] ?? '', $it['slug'] ?? '', $it['name'] ?? '', implode( '|', (array) ( $it['tags'] ?? array() ) ), (int) ( $it['active_installs'] ?? 0 ), (int) ( $it['last_updated'] ?? 0 ) ) ); }
		fclose( $f );
		exit;
	}

	/** Purge chunks by type/source/page */
	public function ajax_purge_catalog(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$this->rate_limit( 'purge_catalog', 10 );
		global $wpdb;
		$type   = sanitize_key( wp_unslash( $_POST['type'] ?? '' ) );
		$source = sanitize_key( wp_unslash( $_POST['source'] ?? '' ) );
		$page   = (int) ( wp_unslash( $_POST['page'] ?? 0 ) );
		if ( $type === '' && $source === '' && $page === 0 ) {
			$this->respond_error( 'invalid', __( 'Specify at least one selector.', 'wp-pierre' ), 400 ); }
		$like = 'pierre_projects_catalog_';
		if ( $type ) {
			$like .= $type . '_';
		} else {
			$like .= '%_'; }
		if ( $source ) {
			$like .= $source . '_';
		} else {
			$like .= '%_'; }
		if ( $page > 0 ) {
			$like .= $page;
		} else {
			$like .= '%'; }
		$like  = $wpdb->esc_like( $like );
		$rows  = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		$count = 0;
		foreach ( (array) $rows as $name ) {
			delete_option( $name );
			++$count; }
		wp_send_json_success( array( 'message' => sprintf( __( 'Purged %d option(s).', 'wp-pierre' ), $count ) ) );
	}

	/** Inspector: liste des options du catalogue */
	public function ajax_inspect_catalog(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		global $wpdb;
		$like = $wpdb->esc_like( 'pierre_projects_catalog_' ) . '%';
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, LENGTH(option_value) AS bytes FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name ASC", $like ), ARRAY_A );
		wp_send_json_success( array( 'options' => $rows ) );
	}

	/**
	 * Uniform JSON error response for AJAX handlers.
	 *
	 * @param string     $code Error code.
	 * @param string     $message Error message.
	 * @param int        $status HTTP status code.
	 * @param mixed|null $details Additional error details.
	 * @return void
	 */
	private function respond_error( string $code, string $message, int $status = 403, $details = null ): void {
		// Map all admin errors to centralized debug logger (throttled at handler level)
		do_action(
			'wp_pierre_debug',
			'admin error',
			array(
				'scope'  => 'admin',
				'action' => $code,
				'code'   => $status,
			)
		);
		$data = array(
			'code'    => $code,
			'message' => $message,
		);
		if ( $details !== null ) {
			$data['details'] = $details; }
		wp_send_json_error( $data, $status );
	}

	/** Rebuild projects catalog (admin) */
	public function ajax_rebuild_catalog(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		try {
			$svc = new \Pierre\Discovery\ProjectsCatalog();
			$res = $svc->rebuild();
			if ( ! empty( $res['success'] ) ) {
				// Invalidate fetch memoization
				global $wpdb;
				$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'pierre_catalog_fetch_%'" );
				wp_send_json_success( array( 'message' => __( 'Catalog rebuild scheduled/done.', 'wp-pierre' ) ) );
			}
			$msg = ( $res['errors'][0]['message'] ?? __( 'Unknown error', 'wp-pierre' ) );
			$this->respond_error( 'catalog_error', $msg, 500 );
		} catch ( \Throwable $e ) {
			$this->respond_error( 'catalog_exception', $e->getMessage(), 500 );
		}
	}

	/** Add selected catalog items to a locale */
	public function ajax_add_from_catalog(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$locale = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		if ( $locale === '' ) {
			$this->respond_error( 'missing_locale', __( 'Locale code is required.', 'wp-pierre' ), 400 ); }
		$items = isset( $_POST['items'] ) ? (array) wp_unslash( $_POST['items'] ) : array();
		if ( empty( $items ) ) {
			$this->respond_error( 'empty_selection', __( 'No items selected.', 'wp-pierre' ), 400 ); }
		$ok  = 0;
		$err = 0;
		foreach ( $items as $raw ) {
			$parts = array_map( 'sanitize_key', array_map( 'trim', explode( ',', (string) $raw ) ) );
			if ( count( $parts ) < 2 ) {
				++$err;
				continue; }
			list( $type, $slug ) = $parts;
			if ( $slug === '' ) {
				++$err;
				continue; }
			$added = $this->project_watcher->watch_project( $slug, $locale, $type ?: 'meta' );
			if ( $added ) {
				try {
					( new \Pierre\Discovery\ProjectsCatalog() )->mark_known( $type ?: 'meta', $slug ); } catch ( \Throwable $e ) {
					}
					++$ok;
			} else {
				++$err; }
		}
		wp_send_json_success( array( 'message' => sprintf( __( 'Added %1$d item(s), %2$d error(s).', 'wp-pierre' ), $ok, $err ) ) );
	}

	/** Fetch projects catalog (paged) */
	public function ajax_fetch_catalog(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$this->rate_limit( 'catalog_fetch', 60 );
		$args             = array(
			'type'     => isset( $_POST['type'] ) ? (array) wp_unslash( $_POST['type'] ) : array(),
			'tags'     => isset( $_POST['tags'] ) ? (array) wp_unslash( $_POST['tags'] ) : array(),
			'search'   => (string) ( wp_unslash( $_POST['search'] ?? '' ) ),
			'page'     => (int) ( wp_unslash( $_POST['page'] ?? 1 ) ),
			'per_page' => (int) ( wp_unslash( $_POST['per_page'] ?? 24 ) ),
			'sort'     => (string) ( wp_unslash( $_POST['sort'] ?? '' ) ),
			'source'   => (string) ( wp_unslash( $_POST['source'] ?? 'popular' ) ),
		);
		$args['type']     = array_values( array_intersect( array_map( 'sanitize_key', (array) $args['type'] ), array( 'core', 'plugin', 'theme', 'meta', 'app' ) ) );
		$args['tags']     = array_values( array_map( 'sanitize_key', (array) $args['tags'] ) );
		$args['search']   = substr( sanitize_text_field( $args['search'] ), 0, 100 );
		$args['page']     = max( 1, (int) $args['page'] );
		$args['per_page'] = min( 100, max( 1, (int) $args['per_page'] ) );
		if ( count( $args['tags'] ) > 50 ) {
			$args['tags'] = array_slice( $args['tags'], 0, 50 ); }
		$whitelist      = array( 'popular', 'updated', 'slug', 'name', 'active' );
		$args['sort']   = in_array( $args['sort'], $whitelist, true ) ? $args['sort'] : '';
		$args['source'] = in_array( $args['source'], array( 'popular', 'featured', 'updated', 'new' ), true ) ? $args['source'] : 'popular';
		$locale         = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		try {
			$svc = new \Pierre\Discovery\ProjectsCatalog();
			$out = $svc->fetch( $args );
			if ( $locale !== '' && ! empty( $out['items'] ) ) {
				$watched = get_option( 'pierre_watched_projects', array() );
				foreach ( $out['items'] as &$it ) {
					$slug          = (string) ( $it['slug'] ?? '' );
					$key           = $slug . '_' . $locale;
					$it['watched'] = isset( $watched[ $key ] );
				}
				unset( $it );
			}
			wp_send_json_success( $out );
		} catch ( \Throwable $e ) {
			$this->respond_error( 'catalog_exception', $e->getMessage(), 500 );
		}
	}

	/** Save catalog settings (interval/limits/sources) */
	public function ajax_save_catalog_settings(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$interval         = max( 60, (int) ( wp_unslash( $_POST['interval_minutes'] ?? 1440 ) ) );
		$max_per_run      = max( 10, min( 500, (int) ( wp_unslash( $_POST['max_per_run'] ?? 200 ) ) ) );
		$plugins_popular  = ! empty( wp_unslash( $_POST['plugins_popular'] ?? '' ) ) ? 1 : 0;
		$plugins_featured = ! empty( wp_unslash( $_POST['plugins_featured'] ?? '' ) ) ? 1 : 0;
		$themes_popular   = ! empty( wp_unslash( $_POST['themes_popular'] ?? '' ) ) ? 1 : 0;
		$themes_featured  = ! empty( wp_unslash( $_POST['themes_featured'] ?? '' ) ) ? 1 : 0;
		$meta             = get_option( 'pierre_projects_catalog_meta', array() );
		if ( ! is_array( $meta ) ) {
			$meta = array(); }
		$meta['schedule'] = array(
			'interval_minutes' => $interval,
			'max_per_run'      => $max_per_run,
		);
		$meta['sources']  = array(
			'plugins' => array(
				'popular'  => (bool) $plugins_popular,
				'featured' => (bool) $plugins_featured,
			),
			'themes'  => array(
				'popular'  => (bool) $themes_popular,
				'featured' => (bool) $themes_featured,
			),
		);
		if ( false === get_option( 'pierre_projects_catalog_meta', false ) ) {
			add_option( 'pierre_projects_catalog_meta', $meta, '', 'no' );
		} else {
			update_option( 'pierre_projects_catalog_meta', $meta, false ); }
		wp_send_json_success( array( 'message' => __( 'Saved.', 'wp-pierre' ) ) );
	}

	/** Get projects catalog meta/status */
	public function ajax_get_catalog_status(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		try {
			$svc = new \Pierre\Discovery\ProjectsCatalog();
			wp_send_json_success( $svc->get_status() );
		} catch ( \Throwable $e ) {
			$this->respond_error( 'catalog_exception', $e->getMessage(), 500 );
		}
	}

	/** Get current catalog build progress */
	public function ajax_get_catalog_progress(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$p = get_transient( 'pierre_catalog_progress' );
		if ( ! is_array( $p ) ) {
			$p = array(
				'processed' => 0,
				'total'     => 0,
				'phase'     => '',
				'ts'        => 0,
			); }
		wp_send_json_success( $p );
	}

	/** Errors listing */
	public function ajax_get_catalog_errors(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$list = get_option( 'pierre_projects_catalog_errors', array() );
		if ( ! is_array( $list ) ) {
			$list = array(); }
		wp_send_json_success(
			array(
				'errors' => array_reverse( $list ),
				'count'  => count( $list ),
			)
		);
	}
	public function ajax_export_catalog_errors_json(): void {
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'forbidden' ); }
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="pierre-catalog-errors.json"' );
		$list = get_option( 'pierre_projects_catalog_errors', array() );
		echo wp_json_encode( is_array( $list ) ? $list : array() );
		exit;
	}
	public function ajax_export_catalog_errors_csv(): void {
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'forbidden' ); }
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="pierre-catalog-errors.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'ts', 'code', 'message' ) );
		$list = get_option( 'pierre_projects_catalog_errors', array() );
		if ( ! is_array( $list ) ) {
			$list = array(); }
		foreach ( $list as $e ) {
			fputcsv( $out, array( (int) ( $e['ts'] ?? 0 ), (int) ( $e['code'] ?? 0 ), (string) ( $e['message'] ?? '' ) ) ); }
		fclose( $out );
		exit;
	}

	/** Reset catalog index and chunks */
	public function ajax_reset_catalog(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$deleted = 0;
		global $wpdb;
		$like = $wpdb->esc_like( 'pierre_projects_catalog_' ) . '%';
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		foreach ( (array) $rows as $on ) {
			if ( delete_option( $on ) ) {
				++$deleted; }
		}
		delete_option( 'pierre_projects_catalog_errors' );
		wp_send_json_success( array( 'message' => sprintf( __( 'Reset done (%d entries).', 'wp-pierre' ), $deleted ) ) );
	}

	/** Export current catalog to library */
	public function ajax_catalog_export_to_library(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$svc  = new \Pierre\Discovery\ProjectsCatalog();
		$meta = $svc->get_status();
		$idx  = is_array( $meta['index'] ?? null ) ? $meta['index'] : array();
		$acc  = array();
		foreach ( array( 'plugin', 'theme' ) as $t ) {
			$pages = (int) ( $idx[ $t ]['last_page'] ?? 1 );
			for ( $p = 1;$p <= $pages;$p++ ) {
				$chunk = get_option( 'pierre_projects_catalog_' . $t . '_' . $p, array() );
				if ( is_array( $chunk ) ) {
					foreach ( $chunk as $it ) {
						$acc[] = array(
							'type' => $t,
							'slug' => (string) ( $it['slug'] ?? '' ),
						); }
				}
			}
		}
		$acc = array_values(
			array_filter(
				$acc,
				function ( $it ) {
					return ! empty( $it['slug'] );
				}
			)
		);
		// Dedup
		$seen = array();
		$out  = array();
		foreach ( $acc as $i ) {
			$k = $i['type'] . ':' . $i['slug'];
			if ( isset( $seen[ $k ] ) ) {
				continue;
			} $seen[ $k ] = 1;
			$out[]        = $i; }
		update_option( 'pierre_projects_discovery', $out, false );
		wp_send_json_success( array( 'message' => sprintf( __( 'Exported %d items to Library.', 'wp-pierre' ), count( $out ) ) ) );
	}

	/** Import library to catalog (mark known and upsert into page 1) */
	public function ajax_catalog_import_from_library(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$lib = get_option( 'pierre_projects_discovery', array() );
		if ( ! is_array( $lib ) ) {
			$lib = array(); }
		$svc = new \Pierre\Discovery\ProjectsCatalog();
		$n   = 0;
		foreach ( $lib as $it ) {
			$type = sanitize_key( $it['type'] ?? 'meta' );
			$slug = sanitize_key( $it['slug'] ?? '' );
			if ( $slug === '' ) {
				continue;
			} $svc->mark_known( $type, $slug );
		}
		wp_send_json_success( array( 'message' => __( 'Library imported into Known projects (will be crawled with priority).', 'wp-pierre' ) ) );
	}

	/** Schedule catalog build soon (single event within 2 minutes) */
	public function ajax_schedule_catalog(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_catalog' ) && ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$ts = time() + 120;
		wp_schedule_single_event( $ts, 'pierre_build_projects_catalog' );
		// Also reflect in meta next_build
		try {
			$meta = get_option( 'pierre_projects_catalog_meta', array() );
			if ( ! is_array( $meta ) ) {
				$meta = array(); }
			$meta['next_build'] = $ts;
			if ( false === get_option( 'pierre_projects_catalog_meta', false ) ) {
				add_option( 'pierre_projects_catalog_meta', $meta, '', 'no' );
			} else {
				update_option( 'pierre_projects_catalog_meta', $meta, false ); }
		} catch ( \Throwable $e ) {
		}
		wp_send_json_success( array( 'message' => __( 'Scheduled.', 'wp-pierre' ) ) );
	}

	/** Abort current run (flag checked by cron/tasks) */
	public function ajax_abort_run(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		update_option( 'pierre_abort_run', time(), false );
		do_action(
			'wp_pierre_debug',
			'abort requested',
			array(
				'scope'  => 'cron',
				'action' => 'abort',
			)
		);
		wp_send_json_success( array( 'message' => __( 'Abort requested. Ongoing run will stop shortly.', 'wp-pierre' ) ) );
	}

	/** Get current progress of surveillance (processed/total) */
	public function ajax_get_progress(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$progress = get_transient( 'pierre_surv_progress' );
		$abort    = (int) get_option( 'pierre_abort_run', 0 ) ? true : false;
		if ( ! is_array( $progress ) ) {
			$progress = array(
				'processed' => 0,
				'total'     => 0,
				'ts'        => 0,
			); }
		$dur = (int) get_option( 'pierre_last_surv_duration_ms', 0 );
		wp_send_json_success(
			array(
				'progress'    => $progress,
				'aborting'    => $abort,
				'duration_ms' => $dur,
			)
		);
	}

	/** Abort current surveillance run */
	public function ajax_abort_surveillance_run(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce', 'wp-pierre' ), 403 ); }
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ), 403 ); }
		set_transient( 'pierre_surv_abort', 1, 5 * MINUTE_IN_SECONDS );
		wp_send_json_success( array( 'message' => __( 'Abort signal set.', 'wp-pierre' ) ) );
	}

	/** Get surveillance errors via AJAX */
	public function ajax_get_surveillance_errors(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce', 'wp-pierre' ), 403 ); }
		if ( ! current_user_can( 'pierre_view_dashboard' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ), 403 ); }

		// Get filters from POST if provided
		$filters = isset( $_POST['filter_type'] ) || isset( $_POST['filter_slug'] ) || isset( $_POST['filter_locale'] ) || isset( $_POST['filter_code_min'] ) || isset( $_POST['filter_code_max'] ) || isset( $_POST['filter_hours'] )
			? $this->parse_error_filters( wp_unslash( $_POST ) )
			: array( 'hours_max' => 24 );

		$errors = $this->get_filtered_errors( $filters );

		wp_send_json_success(
			array(
				'errors' => $errors,
				'count'  => count( $errors ),
			)
		);
	}

	/** Clear surveillance errors via AJAX */
	public function ajax_clear_surveillance_errors(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce', 'wp-pierre' ), 403 ); }
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ), 403 ); }

		delete_transient( 'pierre_last_surv_errors' );
		wp_send_json_success( array( 'message' => __( 'Surveillance errors cleared.', 'wp-pierre' ) ) );
	}

	/** Export surveillance errors as JSON */
	public function ajax_export_errors_json(): void {
		if ( ! current_user_can( 'pierre_manage_reports' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied', 'wp-pierre' ), 403 );
		}
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce', 'wp-pierre' ), 403 );
		}

		// Get filters from POST
		$filters = $this->parse_error_filters( wp_unslash( $_POST ) );
		$errors  = $this->get_filtered_errors( $filters );

		// Format for export
		$export_data = array(
			'exported_at' => current_time( 'mysql' ),
			'exported_by' => get_current_user_id(),
			'filters'     => $filters,
			'total_count' => count( $errors ),
			'errors'      => array_map(
				function ( $error ) {
					return array(
						'timestamp'    => $error['timestamp'] ?? 0,
						'datetime'     => date( 'Y-m-d H:i:s', $error['timestamp'] ?? 0 ),
						'project_type' => $error['type'] ?? 'meta',
						'project_slug' => $error['slug'] ?? '',
						'locale'       => $error['locale'] ?? '',
						'http_code'    => $error['code'] ?? 0,
						'run_id'       => $error['run_id'] ?? '',
						'age_hours'    => isset( $error['timestamp'] ) ? round( ( time() - $error['timestamp'] ) / 3600, 2 ) : 0,
					);
				},
				$errors
			),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="pierre_errors_' . date( 'Y-m-d_His' ) . '.json"' );
		echo wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/** Export surveillance errors as CSV */
	public function ajax_export_errors_csv(): void {
		if ( ! current_user_can( 'pierre_manage_reports' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied', 'wp-pierre' ), 403 );
		}
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce', 'wp-pierre' ), 403 );
		}

		// Get filters from POST
		$filters = $this->parse_error_filters( wp_unslash( $_POST ) );
		$errors  = $this->get_filtered_errors( $filters );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="pierre_errors_' . date( 'Y-m-d_His' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );

		// BOM for Excel UTF-8 compatibility
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers
		fputcsv(
			$out,
			array(
				__( 'Date/Time', 'wp-pierre' ),
				__( 'Project Type', 'wp-pierre' ),
				__( 'Project Slug', 'wp-pierre' ),
				__( 'Locale', 'wp-pierre' ),
				__( 'HTTP Code', 'wp-pierre' ),
				__( 'Run ID', 'wp-pierre' ),
				__( 'Age (hours)', 'wp-pierre' ),
			)
		);

		// Data rows
		foreach ( $errors as $error ) {
			fputcsv(
				$out,
				array(
					date( 'Y-m-d H:i:s', $error['timestamp'] ?? 0 ),
					$error['type'] ?? 'meta',
					$error['slug'] ?? '',
					$error['locale'] ?? '',
					$error['code'] ?? 0,
					$error['run_id'] ?? '',
					isset( $error['timestamp'] ) ? round( ( time() - $error['timestamp'] ) / 3600, 2 ) : 0,
				)
			);
		}

		fclose( $out );
		exit;
	}

	/** Get error trends/statistics */
	public function ajax_get_error_stats(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce', 'wp-pierre' ), 403 );
		}
		if ( ! current_user_can( 'pierre_view_dashboard' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ), 403 );
		}

		$errors = get_transient( 'pierre_last_surv_errors' );
		if ( ! is_array( $errors ) ) {
			$errors = array();
		}

		$now   = time();
		$stats = array(
			'total'               => count( $errors ),
			'by_type'             => array(),
			'by_locale'           => array(),
			'by_http_code'        => array(),
			'by_project'          => array(),
			'timeline'            => array(),
			'hourly_distribution' => array(),
		);

		// Group by type
		foreach ( $errors as $error ) {
			$type                      = $error['type'] ?? 'meta';
			$stats['by_type'][ $type ] = ( $stats['by_type'][ $type ] ?? 0 ) + 1;
		}
		arsort( $stats['by_type'] );

		// Group by locale
		foreach ( $errors as $error ) {
			$locale                        = $error['locale'] ?? 'unknown';
			$stats['by_locale'][ $locale ] = ( $stats['by_locale'][ $locale ] ?? 0 ) + 1;
		}
		arsort( $stats['by_locale'] );

		// Group by HTTP code
		foreach ( $errors as $error ) {
			$code = (int) ( $error['code'] ?? 0 );
			if ( $code > 0 ) {
				$code_range                           = $this->get_http_code_range( $code );
				$stats['by_http_code'][ $code_range ] = ( $stats['by_http_code'][ $code_range ] ?? 0 ) + 1;
			}
		}
		arsort( $stats['by_http_code'] );

		// Group by project (top 10)
		foreach ( $errors as $error ) {
			$project_key                         = ( $error['type'] ?? 'meta' ) . ':' . ( $error['slug'] ?? 'unknown' );
			$stats['by_project'][ $project_key ] = ( $stats['by_project'][ $project_key ] ?? 0 ) + 1;
		}
		arsort( $stats['by_project'] );
		$stats['by_project'] = array_slice( $stats['by_project'], 0, 10, true );

		// Timeline: errors per hour (last 24 hours)
		$timeline = array();
		for ( $i = 23; $i >= 0; $i-- ) {
			$hour_start = $now - ( $i * HOUR_IN_SECONDS );
			$hour_end   = $hour_start + HOUR_IN_SECONDS;
			$count      = 0;
			foreach ( $errors as $error ) {
				$ts = $error['timestamp'] ?? 0;
				if ( $ts >= $hour_start && $ts < $hour_end ) {
					++$count;
				}
			}
			$timeline[] = array(
				'hour'      => date( 'H:00', $hour_start ),
				'timestamp' => $hour_start,
				'count'     => $count,
			);
		}
		$stats['timeline'] = $timeline;

		// Hourly distribution (0-23)
		$hourly = array_fill( 0, 24, 0 );
		foreach ( $errors as $error ) {
			$ts = $error['timestamp'] ?? 0;
			if ( $ts > 0 ) {
				$hour = (int) date( 'G', $ts );
				++$hourly[ $hour ];
			}
		}
		$stats['hourly_distribution'] = $hourly;

		// Trends: compare last 12h vs previous 12h
		$last_12h       = $now - ( 12 * HOUR_IN_SECONDS );
		$last_24h       = $now - ( 24 * HOUR_IN_SECONDS );
		$recent_count   = 0;
		$previous_count = 0;
		foreach ( $errors as $error ) {
			$ts = $error['timestamp'] ?? 0;
			if ( $ts >= $last_12h ) {
				++$recent_count;
			} elseif ( $ts >= $last_24h ) {
				++$previous_count;
			}
		}
		$stats['trend'] = array(
			'last_12h'       => $recent_count,
			'previous_12h'   => $previous_count,
			'change_percent' => $previous_count > 0
				? round( ( ( $recent_count - $previous_count ) / $previous_count ) * 100, 1 )
				: ( $recent_count > 0 ? 100 : 0 ),
			'direction'      => $recent_count > $previous_count ? 'up' : ( $recent_count < $previous_count ? 'down' : 'stable' ),
		);

		wp_send_json_success( $stats );
	}

	/**
	 * Parse error filters from POST data.
	 *
	 * @param array $post_data POST data array.
	 * @return array Parsed filters.
	 */
	private function parse_error_filters( array $post_data ): array {
		return array(
			'project_type'  => sanitize_key( wp_unslash( $post_data['filter_type'] ?? '' ) ),
			'project_slug'  => sanitize_text_field( wp_unslash( $post_data['filter_slug'] ?? '' ) ),
			'locale'        => sanitize_key( wp_unslash( $post_data['filter_locale'] ?? '' ) ),
			'http_code_min' => isset( $post_data['filter_code_min'] ) && $post_data['filter_code_min'] !== '' ? absint( wp_unslash( $post_data['filter_code_min'] ) ) : null,
			'http_code_max' => isset( $post_data['filter_code_max'] ) && $post_data['filter_code_max'] !== '' ? absint( wp_unslash( $post_data['filter_code_max'] ) ) : null,
			'hours_max'     => isset( $post_data['filter_hours'] ) && $post_data['filter_hours'] !== '' ? absint( wp_unslash( $post_data['filter_hours'] ) ) : 24,
		);
	}

	/**
	 * Get filtered errors.
	 *
	 * @param array $filters Filter array.
	 * @return array Filtered errors.
	 */
	private function get_filtered_errors( array $filters ): array {
		$errors = get_transient( 'pierre_last_surv_errors' );
		if ( ! is_array( $errors ) ) {
			$errors = array();
		}

		$now      = time();
		$filtered = array();

		foreach ( $errors as $key => $error ) {
			// Age filter (default 24h)
			$age = $now - ( $error['timestamp'] ?? 0 );
			if ( $filters['hours_max'] > 0 && $age > ( $filters['hours_max'] * HOUR_IN_SECONDS ) ) {
				continue;
			}

			// Type filter
			if ( ! empty( $filters['project_type'] ) && ( $error['type'] ?? '' ) !== $filters['project_type'] ) {
				continue;
			}

			// Slug filter (partial match)
			if ( ! empty( $filters['project_slug'] ) ) {
				$slug = $error['slug'] ?? '';
				if ( stripos( $slug, $filters['project_slug'] ) === false ) {
					continue;
				}
			}

			// Locale filter
			if ( ! empty( $filters['locale'] ) && ( $error['locale'] ?? '' ) !== $filters['locale'] ) {
				continue;
			}

			// HTTP code range filter
			$code = (int) ( $error['code'] ?? 0 );
			if ( $filters['http_code_min'] !== null && $code < $filters['http_code_min'] ) {
				continue;
			}
			if ( $filters['http_code_max'] !== null && $code > $filters['http_code_max'] ) {
				continue;
			}

			$filtered[ $key ] = $error;
		}

		// Sort by timestamp (most recent first)
		uasort(
			$filtered,
			function ( $a, $b ) {
				return ( $b['timestamp'] ?? 0 ) - ( $a['timestamp'] ?? 0 );
			}
		);

		return array_values( $filtered );
	}

	/**
	 * Get HTTP code range for statistics.
	 *
	 * @param int $code HTTP status code.
	 * @return string Code range.
	 */
	private function get_http_code_range( int $code ): string {
		if ( $code >= 500 ) {
			return '5xx';
		}
		if ( $code >= 400 ) {
			return '4xx';
		}
		if ( $code >= 300 ) {
			return '3xx';
		}
		if ( $code >= 200 ) {
			return '2xx';
		}
		return 'other';
	}

	/** Export locales cache as JSON */
	public function ajax_export_locales_json(): void {
		if ( ! current_user_can( 'pierre_manage_reports' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied', 'wp-pierre' ), 403 ); }
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce', 'wp-pierre' ), 403 ); }
		$cache = get_option( 'pierre_locales_cache' );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="pierre_locales_cache.json"' );
		echo wp_json_encode( $cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/** Export locales cache as CSV */
	public function ajax_export_locales_csv(): void {
		if ( ! current_user_can( 'pierre_manage_reports' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied', 'wp-pierre' ), 403 ); }
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce', 'wp-pierre' ), 403 ); }
		$cache = get_option( 'pierre_locales_cache' );
		$rows  = is_array( $cache ) && ! empty( $cache['data'] ) ? (array) $cache['data'] : array();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="pierre_locales_cache.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'code', 'label', 'slug', 'translate_slug', 'team_locale', 'rosetta', 'slack_url' ) );
		foreach ( $rows as $r ) {
			fputcsv(
				$out,
				array(
					(string) ( $r['code'] ?? '' ),
					(string) ( $r['label'] ?? '' ),
					(string) ( $r['slug'] ?? '' ),
					(string) ( $r['translate_slug'] ?? '' ),
					(string) ( $r['team_locale'] ?? '' ),
					(string) ( $r['rosetta'] ?? '' ),
					(string) ( $r['slack_url'] ?? '' ),
				)
			);
		}
		fclose( $out );
		exit;
	}

	/**
	 * Pierre renders his dashboard page! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_dashboard_page(): void {
		$this->dashboard_handler->render_dashboard_page(
			function ( string $template_name, array $data ): void {
				$this->render_admin_template( $template_name, $data );
			}
		);
	}

	/**
	 * Pierre renders his teams page! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_teams_page(): void {
		$this->teams_handler->render_teams_page(
			function ( string $template_name, array $data ): void {
				$this->render_admin_template( $template_name, $data );
			}
		);
	}

	/**
	 * Pierre renders his locales page! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_locales_page(): void {
		$this->locales_handler->render_locales_page(
			function ( string $template_name, array $data ): void {
				$this->render_admin_template( $template_name, $data );
			}
		);
	}

	/**
	 * Pierre renders his locale view page! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_locale_view_page(): void {
		// Get locale from URL - preserve case for locale codes (fr_FR, not fr_fr)
		$raw_locale = trim( (string) ( isset( $_GET['locale'] ) ? sanitize_text_field( wp_unslash( $_GET['locale'] ) ) : '' ) );
		if ( empty( $raw_locale ) ) {
			wp_die( esc_html__( 'Pierre says: Locale parameter is required!', 'wp-pierre' ) . ' 😢' );
		}
		// Normalize locale code to WordPress format (e.g., fr_FR, pt_BR, en_US, or fr)
		$locale_code = preg_replace_callback(
			'/^([a-z]{2})(?:_([a-zA-Z]{2}))?$/',
			static function ( $m ) {
				return isset( $m[2] ) ? strtolower( $m[1] ) . '_' . strtoupper( $m[2] ) : strtolower( $m[1] );
			},
			$raw_locale
		);
		// Validate locale format
		if ( ! preg_match( '/^[a-z]{2}(?:_[A-Z]{2})?$/', $locale_code ) ) {
			wp_die( esc_html__( 'Pierre says: Invalid locale code format!', 'wp-pierre' ) . ' 😢' );
		}

		$this->locales_handler->render_locale_view_page(
			function ( string $template_name, array $data ): void {
				$this->render_admin_template( $template_name, $data );
			},
			$locale_code
		);
	}

	/**
	 * Pierre renders his projects page! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_projects_page(): void {
		$this->projects_handler->render_projects_page(
			function ( string $template_name, array $data ): void {
				$this->render_admin_template( $template_name, $data );
			}
		);
	}

	/**
	 * Pierre renders his settings page! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page(): void {
		$this->settings_handler->render_settings_page(
			function ( string $template_name, array $data ): void {
				$this->render_admin_template( $template_name, $data );
			}
		);
	}

	/**
	 * Pierre renders his reports page! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_reports_page(): void {
		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_view_dashboard' ) ) {
			wp_die( esc_html__( 'Pierre says: You don\'t have permission to view this page!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre gets his reports data! 🪨
		$reports_data = $this->get_admin_reports_data();

		// Pierre renders his template! 🪨
		$this->render_admin_template( 'reports', $reports_data );
	}

	/**
	 * Pierre renders his security page! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_security_page(): void {
		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_view_dashboard' ) ) {
			wp_die( esc_html__( 'Pierre says: You don\'t have permission to view this page!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre renders his security template! 🪨
		$this->render_admin_template( 'security', array() );
	}

	/**
	 * Pierre renders an admin template! 🪨
	 *
	 * @since 1.0.0
	 * @param string $template_name The template name.
	 * @param array  $data The template data.
	 * @return void
	 */
	private function render_admin_template( string $template_name, array $data ): void {
		// Pierre sets up his template data! 🪨
		$GLOBALS['pierre_admin_template_data'] = $data;
		$template_path                         = PIERRE_PLUGIN_DIR . "templates/admin/{$template_name}.php";
		$t0                                    = microtime( true );
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			$this->render_simple_admin_template( $template_name, $data );
		}
		if ( $this->is_debug() ) {
			$ms = (int) round( ( microtime( true ) - $t0 ) * 1000 );
			do_action(
				'wp_pierre_debug',
				'render_template',
				array(
					'source' => 'AdminController',
					'tpl'    => $template_name,
					'ms'     => $ms,
				)
			);
		}
	}

	/**
	 * Pierre renders a simple admin template! 🪨
	 *
	 * @since 1.0.0
	 * @param string $template_name The template name.
	 * @param array  $data The template data.
	 * @return void
	 */
	private function render_simple_admin_template( string $template_name, array $data ): void {
		?>
		<div class="wrap">
			<h1>Pierre 🪨 <?php echo esc_html( ucfirst( $template_name ) ); ?></h1>
			
			<div class="notice notice-info is-dismissible">
				<p><strong><?php echo esc_html__( 'Pierre says:', 'wp-pierre' ); ?></strong> <?php echo esc_html( sprintf( __( 'This is a simple admin template for %s. The full template will be implemented in the next phase.', 'wp-pierre' ), $template_name ) ); ?></p>
			</div>
			
			<?php if ( isset( $data['stats'] ) ) : ?>
			<div class="pierre-card">
				<h2><?php echo esc_html__( 'Pierre\'s Statistics', 'wp-pierre' ); ?></h2>
				<div class="pierre-grid">
					<?php foreach ( $data['stats'] as $stat ) : ?>
					<div class="pierre-stat-box">
						<div class="pierre-stat-number"><?php echo esc_html( $stat['value'] ); ?></div>
						<div class="pierre-stat-label"><?php echo esc_html( $stat['label'] ); ?></div>
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
		// Notices are centrally rendered by NoticeManager; consume legacy transients silently to avoid duplicates
		if ( get_transient( 'pierre_admin_notice' ) ) {
			delete_transient( 'pierre_admin_notice' ); }
		if ( get_transient( 'pierre_admin_error' ) ) {
			delete_transient( 'pierre_admin_error' ); }
	}

	/**
	 * Pierre modifies admin footer! 🪨
	 *
	 * @since 1.0.0
	 * @param string $text The footer text.
	 * @return string Modified footer text
	 */
	public function modify_admin_footer( string $text ): string {
		$screen = get_current_screen();

		if ( $screen && strpos( $screen->id, 'pierre' ) !== false ) {
			return __( 'Pierre says: Thank you for using WordPress Translation Monitor! 🪨', 'wp-pierre' );
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

		return array(
			'user_id'             => $current_user_id,
			'user_name'           => wp_get_current_user()->display_name,
			'surveillance_status' => $this->project_watcher->get_surveillance_status(),
			'notifier_status'     => $this->slack_notifier->get_status(),
			'role_manager_status' => $this->role_manager->get_status(),
			'user_assignments'    => $current_user_id ? $this->user_project_link->get_user_assignments_with_details( $current_user_id ) : array(),
			'watched_projects'    => $this->project_watcher->get_watched_projects(),
			'stats'               => $this->get_admin_stats(),
		);
	}

	/**
	 * Pierre gets admin teams data! 🪨
	 *
	 * @since 1.0.0
	 * @return array Admin teams data
	 */
	private function get_admin_teams_data(): array {
		// Get locales with labels for UI (cached)
		$translations   = $this->get_cached_translations();
		$locales_labels = array();
		foreach ( $translations as $slug => $t ) {
			if ( ! empty( $t['language'] ) ) {
				$code                    = $t['language'];
				$native                  = $t['native_name'] ?? ( $t['english_name'] ?? '' );
				$locales_labels[ $code ] = trim( $code . ' — ' . $native );
			}
		}
		$locales = array_keys( $locales_labels );
		if ( empty( $locales ) ) {
			$locales        = array( 'fr_FR', 'en_US' );
			$locales_labels = array(
				'fr_FR' => 'fr_FR — ' . __( 'French', 'wp-pierre' ),
				'en_US' => 'en_US — ' . __( 'English', 'wp-pierre' ),
			);
		}

		// Get watched projects grouped by locale
		$watched            = $this->project_watcher->get_watched_projects();
		$projects_by_locale = array();
		foreach ( $watched as $project ) {
			$locale = $project['locale_code'] ?? ( $project['locale'] ?? '' );
			if ( ! empty( $locale ) ) {
				if ( ! isset( $projects_by_locale[ $locale ] ) ) {
					$projects_by_locale[ $locale ] = array();
				}
				$slug = $project['project_slug'] ?? ( $project['slug'] ?? '' );
				if ( ! empty( $slug ) ) {
					$projects_by_locale[ $locale ][] = $slug;
				}
			}
		}

		// Get all users and mark admins
		$users           = get_users( array( 'number' => 50 ) );
		$users_with_meta = array();
		foreach ( $users as $user ) {
			$is_admin          = user_can( $user->ID, 'administrator' );
			$users_with_meta[] = array(
				'user'        => $user,
				'is_admin'    => $is_admin,
				'assignments' => $is_admin ? array() : $this->user_project_link->get_user_assignments_with_details( $user->ID ),
			);
		}

		return array(
			'users'              => $users_with_meta,
			'roles'              => array(
				'locale_manager' => __( 'Locale Manager', 'wp-pierre' ),
				'gte'            => __( 'GTE (General Translation Editor)', 'wp-pierre' ),
				'pte'            => __( 'PTE (Plugin Translation Editor)', 'wp-pierre' ),
				'contributor'    => __( 'Contributor', 'wp-pierre' ),
				'validator'      => __( 'Validator', 'wp-pierre' ),
			),
			'capabilities'       => $this->role_manager->get_capabilities(),
			'stats'              => $this->get_teams_stats(),
			'locales'            => $locales,
			'locales_labels'     => $locales_labels,
			'projects_by_locale' => $projects_by_locale,
		);
	}

	/**
	 * Pierre gets admin locales data! 🪨
	 *
	 * @since 1.0.0
	 * @return array Admin locales data
	 */
	private function get_admin_locales_data(): array {
		// Get all available translations (cached)
		$translations   = $this->get_cached_translations();
		$all_locales    = array();
		$locales_labels = array();
		foreach ( $translations as $slug => $t ) {
			if ( ! empty( $t['language'] ) ) {
				$code                    = $t['language'];
				$native                  = $t['native_name'] ?? ( $t['english_name'] ?? '' );
				$locales_labels[ $code ] = trim( $code . ' — ' . $native );
				$all_locales[]           = $code;
			}
		}
		if ( empty( $all_locales ) ) {
			$all_locales    = array( 'fr_FR', 'en_US', 'de_DE', 'es_ES' );
			$locales_labels = array(
				'fr_FR' => 'fr_FR — ' . __( 'French', 'wp-pierre' ),
				'en_US' => 'en_US — ' . __( 'English', 'wp-pierre' ),
				'de_DE' => 'de_DE — ' . __( 'German', 'wp-pierre' ),
				'es_ES' => 'es_ES — ' . __( 'Spanish', 'wp-pierre' ),
			);
		}

		// Get active locales (from watched projects)
		$watched        = $this->project_watcher->get_watched_projects();
		$active_locales = array();
		foreach ( $watched as $project ) {
			$locale = $project['locale'] ?? ( $project['locale_code'] ?? '' );
			if ( ! empty( $locale ) && ! in_array( $locale, $active_locales, true ) ) {
				$active_locales[] = $locale;
			}
		}
		// Include previously selected locales (added via Discovery, even if no project yet)
		$selected_locales = get_option( 'pierre_selected_locales', array() );
		if ( is_array( $selected_locales ) ) {
			foreach ( $selected_locales as $loc ) {
				if ( ! empty( $loc ) && ! in_array( $loc, $active_locales, true ) ) {
					$active_locales[] = $loc;
				}
			}
		}

		// Get stats per locale
		$locale_stats = array();
		foreach ( $active_locales as $locale ) {
			$projects_count = 0;
			$last_check     = null;
			foreach ( $watched as $project ) {
				$proj_locale = $project['locale'] ?? ( $project['locale_code'] ?? '' );
				if ( $proj_locale === $locale ) {
					++$projects_count;
					$checked = $project['last_checked'] ?? null;
					if ( $checked && ( ! $last_check || $checked > $last_check ) ) {
						$last_check = $checked;
					}
				}
			}
			$locale_stats[ $locale ] = array(
				'projects_count' => $projects_count,
				'last_check'     => $last_check ? human_time_diff( $last_check, current_time( 'timestamp' ) ) . ' ago' : __( 'Never', 'wp-pierre' ),
			);
		}

		return array(
			'all_locales'    => $all_locales,
			'locales_labels' => $locales_labels,
			'active_locales' => $active_locales,
			'locale_stats'   => $locale_stats,
		);
	}

	/**
	 * Pierre gets admin locale view data! 🪨
	 *
	 * @since 1.0.0
	 * @param string $locale_code The locale code.
	 * @return array Admin locale view data
	 */
	private function get_admin_locale_view_data( string $locale_code ): array {
		// Get locale label (cached)
		$translations = $this->get_cached_translations();
		$locale_label = $locale_code;
		foreach ( $translations as $t ) {
			if ( ( $t['language'] ?? '' ) === $locale_code ) {
				$native       = $t['native_name'] ?? ( $t['english_name'] ?? '' );
				$locale_label = trim( $locale_code . ' — ' . $native );
				break;
			}
		}

		// Get projects for this locale
		$watched         = $this->project_watcher->get_watched_projects();
		$locale_projects = array();
		foreach ( $watched as $project ) {
			$proj_locale = $project['locale'] ?? ( $project['locale_code'] ?? '' );
			if ( $proj_locale === $locale_code ) {
				$locale_projects[] = $project;
			}
		}

		// Get locale-specific Slack webhook
		$settings = Settings::all();
		// Migrate legacy global webhook to unified model once
		if ( ! empty( $settings['slack_webhook_url'] ) && empty( $settings['global_webhook'] ) ) {
			$settings['global_webhook'] = array(
				'enabled'     => true,
				'webhook_url' => $settings['slack_webhook_url'],
				'types'       => $settings['notification_types'] ?? array( 'new_strings', 'completion_update', 'needs_attention', 'milestone' ),
				'threshold'   => (int) ( $settings['notification_defaults']['new_strings_threshold'] ?? 20 ),
				'milestones'  => (array) ( $settings['notification_defaults']['milestones'] ?? array( 50, 80, 100 ) ),
				'mode'        => (string) ( $settings['notification_defaults']['mode'] ?? 'immediate' ),
				'digest'      => (array) ( $settings['notification_defaults']['digest'] ?? array(
					'type'             => 'interval',
					'interval_minutes' => 60,
					'fixed_time'       => '09:00',
				) ),
				'scopes'      => array(
					'locales'  => array(),
					'projects' => array(),
				),
			);
			Settings::update( $settings );
		}
		$locale_slack   = $settings['locales_slack'][ $locale_code ] ?? '';
		$locale_webhook = (array) ( $settings['locales'][ $locale_code ]['webhook'] ?? array() );

		// Current locale managers (admin manages this list via AJAX search)
		$managers_map    = get_option( 'pierre_locale_managers', array() );
		$locale_managers = is_array( $managers_map[ $locale_code ] ?? null ) ? $managers_map[ $locale_code ] : array();

		return array(
			'locale_code'     => $locale_code,
			'locale_label'    => $locale_label,
			'projects'        => $locale_projects,
			'slack_webhook'   => $locale_slack,
			'locale_webhook'  => $locale_webhook,
			// Users list is loaded on demand via AJAX to avoid heavy loads
			'all_users'       => array(),
			'locale_managers' => $locale_managers,
			'stats'           => array(
				'projects_count' => count( $locale_projects ),
				'last_check'     => ! empty( $locale_projects ) ? __( 'Recent', 'wp-pierre' ) : __( 'Never', 'wp-pierre' ),
			),
		);
	}

	/**
	 * Save locale managers list (admin-only)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_save_locale_managers(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			do_action( 'wp_pierre_debug', 'fetch_locales: invalid nonce', array( 'source' => 'AdminController' ) );
			wp_send_json_error( array( 'message' => __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}
		if ( ! current_user_can( 'pierre_manage_teams' ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}
		$locale_code = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		$user_ids    = wp_unslash( $_POST['user_ids'] ?? array() );
		if ( empty( $locale_code ) || ! is_array( $user_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid payload.', 'wp-pierre' ) . ' 😢' ) );
			return;
		}
		$user_ids = array_values( array_filter( array_map( 'absint', $user_ids ) ) );
		$map      = get_option( 'pierre_locale_managers', array() );
		if ( ! is_array( $map ) ) {
			$map = array(); }
		$map[ $locale_code ] = $user_ids;
		update_option( 'pierre_locale_managers', $map );
		wp_send_json_success( array( 'message' => __( 'Locale managers saved.', 'wp-pierre' ) . ' 🪨' ) );
	}

	/**
	 * Pierre gets admin projects data! 🪨
	 *
	 * @since 1.0.0
	 * @return array Admin projects data
	 */
	private function get_admin_projects_data(): array {
		// Extraire locales WordPress si dispo (cached)
		$translations = $this->get_cached_translations();
		$locales      = array_values(
			array_unique(
				array_filter(
					array_map(
						function ( $t ) {
							return isset( $t['language'] ) ? $t['language'] : null; },
						$translations
					)
				)
			)
		);
		$labels       = array();
		foreach ( $translations as $slug => $t ) {
			if ( ! empty( $t['language'] ) ) {
				$code            = $t['language'];
				$native          = $t['native_name'] ?? ( $t['english_name'] ?? '' );
				$labels[ $code ] = trim( $code . ' — ' . $native );
			}
		}
		$settings      = Settings::all();
		$locales_slack = isset( $settings['locales_slack'] ) && is_array( $settings['locales_slack'] ) ? $settings['locales_slack'] : array();

		return array(
			'watched_projects'    => $this->project_watcher->get_watched_projects(),
			'surveillance_status' => $this->project_watcher->get_surveillance_status(),
			'stats'               => $this->get_projects_stats(),
			'locales'             => ! empty( $locales ) ? $locales : array( 'fr_FR', 'en_US' ),
			'locales_labels'      => $labels,
			'locales_slack'       => $locales_slack,
			'notifier_status'     => $this->slack_notifier->get_status(),
			'cron_status'         => $this->container->get( \Pierre\Surveillance\CronManager::class )->get_surveillance_status(),
		);
	}

	/**
	 * Add locales to monitoring via AJAX
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_add_locales(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			return;
		}
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			$this->respond_error( 'forbidden', __( 'Pierre says: You don\'t have permission! Only site administrators can add locales.', 'wp-pierre' ) . ' 😢' );
			return;
		}

		$locales = wp_unslash( $_POST['locales'] ?? array() );
		if ( ! is_array( $locales ) || empty( $locales ) ) {
			$this->respond_error( 'invalid_payload', __( 'No locales selected.', 'wp-pierre' ) . ' 😢', 400 );
			return;
		}

		$added  = array();
		$errors = array();

		foreach ( $locales as $locale_code ) {
			// Ne pas utiliser sanitize_key() (qui force en minuscules et casse fr_FR).
			$raw = (string) wp_unslash( $locale_code );
			$raw = trim( $raw );
			// Validation tolérante: langue en minuscule, région insensible à la casse
			if ( $raw === '' || ! preg_match( '/^[a-z]{2}(?:_[a-zA-Z]{2})?$/', $raw ) ) {
				// translators: %s is the invalid locale code
				$errors[] = sprintf( __( 'Invalid locale code: %s', 'wp-pierre' ), $raw );
				continue;
			}
			// Normaliser en forme canonique WP (ex: fr_FR, pt_BR, en_US, ou fr)
			$locale_code = preg_replace_callback(
				'/^([a-z]{2})(?:_([a-zA-Z]{2}))?$/',
				static function ( $m ) {
					return isset( $m[2] ) ? strtolower( $m[1] ) . '_' . strtoupper( $m[2] ) : strtolower( $m[1] );
				},
				$raw
			);

			// Validate locale exists using cached list or fallback fetch (works even if
			// wp_get_available_translations() is not loaded in admin-ajax context)
			$cache = get_option( 'pierre_locales_cache' );
			$known = array();
			if ( is_array( $cache ) && ! empty( $cache['data'] ) ) {
				foreach ( $cache['data'] as $row ) {
					$code = isset( $row['code'] ) ? (string) $row['code'] : '';
					if ( $code !== '' ) {
						$known[ $code ] = true; }
				}
			} else {
				// Fall back to building a fresh list via helper (includes API fallback)
				$base_list = $this->fetch_base_locales_list();
				foreach ( $base_list as $row ) {
					$code = isset( $row['code'] ) ? (string) $row['code'] : '';
					if ( $code !== '' ) {
						$known[ $code ] = true; }
				}
			}
			$locale_exists = isset( $known[ $locale_code ] );

			if ( ! $locale_exists ) {
				// translators: %s is the locale code
				$errors[] = sprintf( __( 'Locale %s not found in WordPress.org translations.', 'wp-pierre' ), $locale_code );
				continue;
			}

			// Check if already active (via watched projects or previously selected)
			$watched   = $this->project_watcher->get_watched_projects();
			$is_active = false;
			foreach ( $watched as $project ) {
				$proj_locale = $project['locale'] ?? ( $project['locale_code'] ?? '' );
				if ( $proj_locale === $locale_code ) {
					$is_active = true;
					break;
				}
			}
			// Also check selected locales
			if ( ! $is_active ) {
				$selected_locales = get_option( 'pierre_selected_locales', array() );
				if ( is_array( $selected_locales ) && in_array( $locale_code, $selected_locales, true ) ) {
					$is_active = true;
				}
			}

			if ( $is_active ) {
				continue; // Already active
			}

			// Locale is valid and not yet active - it will become active when first project is added
			// For now, we just mark it as "ready to use"
			$added[] = $locale_code;
		}

		if ( ! empty( $errors ) ) {
			$this->respond_error(
				'partial_failure',
				__( 'Some locales could not be added.', 'wp-pierre' ),
				400,
				array(
					'errors' => $errors,
					'added'  => $added,
				)
			);
			return;
		}

		if ( empty( $added ) ) {
			$this->respond_error( 'no_changes', __( 'No new locales to add (they may already be active).', 'wp-pierre' ), 400 );
			return;
		}

		// Persist selection so Discovery can show them as active candidates
		$selected = get_option( 'pierre_selected_locales', array() );
		if ( ! is_array( $selected ) ) {
			$selected = array(); }
		$selected = array_values( array_unique( array_merge( $selected, $added ) ) );
		update_option( 'pierre_selected_locales', $selected );

		wp_send_json_success(
			array(
				// translators: %s is a comma-separated list of locales
				'message' => sprintf( __( 'Locales added: %s', 'wp-pierre' ), implode( ', ', $added ) ),
				'added'   => $added,
			)
		);
	}

	/**
	 * Save per-locale Slack webhook via AJAX
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_save_locale_slack(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			return;
		}
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			$this->respond_error( 'forbidden', __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
			return;
		}
		$locale_code = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		$webhook     = trim( (string) wp_unslash( $_POST['slack_webhook_url'] ?? '' ) );
		if ( empty( $locale_code ) ) {
			$this->respond_error( 'missing_locale', __( 'Locale code is required.', 'wp-pierre' ) . ' 😢', 400 );
			return;
		}
		$settings = Settings::all();
		$map      = isset( $settings['locales_slack'] ) && is_array( $settings['locales_slack'] ) ? $settings['locales_slack'] : array();
		if ( $webhook === '' ) {
			unset( $map[ $locale_code ] );
		} else {
			if ( ! filter_var( $webhook, FILTER_VALIDATE_URL ) || strpos( $webhook, 'hooks.slack.com' ) === false ) {
				$this->respond_error( 'invalid_webhook', __( 'Invalid Slack webhook URL.', 'wp-pierre' ) . ' 😢', 400 );
				return;
			}
			$map[ $locale_code ] = esc_url_raw( $webhook );
		}
		$settings['locales_slack'] = $map;
		update_option( 'pierre_settings', $settings );
		wp_send_json_success( array( 'message' => __( 'Locale Slack webhook saved.', 'wp-pierre' ) . ' 🪨' ) );
	}

	/**
	 * Pierre gets admin settings data! 🪨
	 *
	 * @since 1.0.0
	 * @return array Admin settings data
	 */
	private function get_admin_settings_data(): array {
		$settings           = Settings::all();
		$projects_discovery = get_option( 'pierre_projects_discovery', array() );

		// Get active or selected locales for Discovery
		$watched        = $this->project_watcher->get_watched_projects();
		$active_locales = array();
		foreach ( $watched as $project ) {
			$locale = $project['locale'] ?? ( $project['locale_code'] ?? '' );
			if ( ! empty( $locale ) && ! in_array( $locale, $active_locales, true ) ) {
				$active_locales[] = $locale;
			}
		}
		// Include previously selected locales (added via Discovery, even if no project yet)
		$selected_locales = get_option( 'pierre_selected_locales', array() );
		if ( is_array( $selected_locales ) ) {
			foreach ( $selected_locales as $loc ) {
				if ( ! empty( $loc ) && ! in_array( $loc, $active_locales, true ) ) {
					$active_locales[] = $loc;
				}
			}
		}

		return array(
			'settings'           => $settings,
			'notifier_status'    => $this->slack_notifier->get_status(),
			'cron_status'        => $this->container->get( \Pierre\Surveillance\CronManager::class )->get_surveillance_status(),
			'active_locales'     => $active_locales,
			'projects_discovery' => $projects_discovery,
		);
	}

	/**
	 * Save unified locale webhook configuration
	 */
	public function ajax_save_locale_webhook(): void {
		$this->require_manage_permission();
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
		}
		$user_id = get_current_user_id();
		if ( ! $this->role_manager->user_can_manage_locale_settings( $user_id, sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) ) ) ) {
			$this->respond_error( 'forbidden', __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}
		$locale = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		if ( $locale === '' ) {
			$this->respond_error( 'missing_locale', __( 'Locale code is required.', 'wp-pierre' ) . ' 😢', 400 );
		}
		$settings = Settings::all();
		if ( ! is_array( $settings ) ) {
			$settings = array(); }
		if ( ! isset( $settings['locales'] ) || ! is_array( $settings['locales'] ) ) {
			$settings['locales'] = array(); }

		$lw                = (array) ( $settings['locales'][ $locale ]['webhook'] ?? array() );
		$lw['enabled']     = ! empty( wp_unslash( $_POST['locale_webhook_enabled'] ?? ( $lw['enabled'] ?? false ) ) );
		$lw['webhook_url'] = sanitize_url( wp_unslash( $_POST['locale_webhook_url'] ?? ( $lw['webhook_url'] ?? '' ) ) );
		if ( empty( $lw['webhook_url'] ) ) {
			$lw['enabled'] = false; }
		$lw['types'] = isset( $_POST['locale_webhook_types'] ) && is_array( $_POST['locale_webhook_types'] )
			? array_values( array_intersect( array_map( 'sanitize_key', wp_unslash( $_POST['locale_webhook_types'] ) ), array( 'new_strings', 'completion_update', 'needs_attention', 'milestone' ) ) )
			: (array) ( $lw['types'] ?? array( 'new_strings', 'completion_update', 'needs_attention', 'milestone' ) );
		$th          = wp_unslash( $_POST['locale_webhook_threshold'] ?? '' );
		if ( $th !== '' ) {
			$lw['threshold'] = absint( $th );
		} else {
			unset( $lw['threshold'] ); }
		$mil_raw = (string) wp_unslash( $_POST['locale_webhook_milestones'] ?? '' );
		if ( $mil_raw !== '' ) {
			$vals = array_map( 'intval', array_map( 'trim', explode( ',', $mil_raw ) ) );
			$vals = array_map(
				function ( $v ) {
					return max( 0, min( 100, (int) $v ) );
				},
				$vals
			);
			$vals = array_values(
				array_unique(
					array_filter(
						$vals,
						function ( $v ) {
							return $v >= 0 && $v <= 100;
						}
					)
				)
			);
			sort( $vals );
			$lw['milestones'] = $vals;
		} else {
			unset( $lw['milestones'] ); }
		// Modes split for locale
		$lw['immediate_enabled'] = ! empty( $_POST['locale_webhook_immediate_enabled'] );
		$lw['digest']            = $lw['digest'] ?? array();
		$lw['digest']['enabled'] = ! empty( $_POST['locale_webhook_digest_enabled'] );
		$dt                      = sanitize_key( wp_unslash( $_POST['locale_webhook_digest_type'] ?? '' ) );
		if ( in_array( $dt, array( 'interval', 'fixed_time' ), true ) ) {
			$lw['digest']['type'] = $dt;
		} else {
			unset( $lw['digest']['type'] ); }
		$di = wp_unslash( $_POST['locale_webhook_digest_interval_minutes'] ?? '' );
		if ( $di !== '' ) {
			$lw['digest']['interval_minutes'] = max( 15, absint( $di ) );
		} else {
			unset( $lw['digest']['interval_minutes'] ); }
		$df = (string) wp_unslash( $_POST['locale_webhook_digest_fixed_time'] ?? '' );
		if ( $df !== '' ) {
			$df                         = preg_replace( '/[^0-9:]/', '', $df );
			$lw['digest']['fixed_time'] = preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $df ) ? $df : '09:00';
		} else {
			unset( $lw['digest']['fixed_time'] ); }

		// Enforce Slack allowlist for locale webhook URL
		if ( ! empty( $lw['webhook_url'] ) ) {
			$host = wp_parse_url( $lw['webhook_url'], PHP_URL_HOST );
			if ( ! is_string( $host ) || ! preg_match( '/(^|\.)hooks\.slack\.com$/i', $host ) ) {
				$lw['webhook_url'] = '';
			}
		}

		$settings['locales'][ $locale ]['webhook'] = $lw;
		Settings::update( $settings );
		wp_send_json_success( array( 'message' => __( 'Locale webhook saved.', 'wp-pierre' ) . ' 🪨' ) );
	}

	/** Save projects discovery library */
	public function ajax_save_projects_discovery(): void {
		$this->require_manage_permission();
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) );
		}
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			$this->respond_error( 'forbidden', __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) );
		}
		$raw        = (string) wp_unslash( $_POST['projects_discovery'] ?? '' );
		$lines      = array_filter( array_map( 'trim', preg_split( '/\r?\n/', $raw ) ) );
		$out        = array();
		$seen       = array();
		$duplicates = 0;
		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( ',', $line ) );
			if ( count( $parts ) < 2 ) {
				continue; }
			$type = sanitize_key( $parts[0] );
			$slug = sanitize_key( $parts[1] );
			if ( $type === '' || $slug === '' ) {
				continue; }
			if ( ! in_array( $type, array( 'plugin', 'theme', 'meta', 'app', 'core' ), true ) ) {
				continue; }
			$key = ( $type === 'core' ? 'meta' : $type ) . ':' . $slug;
			if ( isset( $seen[ $key ] ) ) {
				++$duplicates;
				continue; }
			$seen[ $key ] = true;
			$out[]        = array(
				'type' => $type === 'core' ? 'meta' : $type,
				'slug' => $slug,
			);
		}
		update_option( 'pierre_projects_discovery', $out );
		wp_send_json_success(
			array(
				'message'    => __( 'Projects discovery library saved.', 'wp-pierre' ) . ' 🪨',
				'count'      => count( $out ),
				'duplicates' => $duplicates,
			)
		);
	}

	/** Bulk add discovery entries to a locale */
	public function ajax_bulk_add_from_discovery(): void {
		$this->require_manage_permission();
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) );
		}
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			$this->respond_error( 'forbidden', __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) );
		}
		$locale = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		if ( $locale === '' ) {
			$this->respond_error( 'missing_locale', __( 'Locale code is required.', 'wp-pierre' ), 400 );
		}
		$lib = get_option( 'pierre_projects_discovery', array() );
		if ( ! is_array( $lib ) || empty( $lib ) ) {
			$this->respond_error( 'empty_library', __( 'Library is empty.', 'wp-pierre' ), 400 );
		}
		$added  = 0;
		$errors = 0;
		foreach ( $lib as $item ) {
			$type = sanitize_key( $item['type'] ?? 'meta' );
			$slug = sanitize_key( $item['slug'] ?? '' );
			if ( $slug === '' ) {
				++$errors;
				continue; }
			$ok = $this->project_watcher->watch_project( $slug, $locale );
			if ( $ok ) {
				// set type into watched option
				$opt = get_option( 'pierre_watched_projects', array() );
				$key = $slug . '_' . $locale;
				if ( isset( $opt[ $key ] ) ) {
					$opt[ $key ]['type'] = $type;
					update_option( 'pierre_watched_projects', $opt ); }
				// also mark as known for projects catalog prioritization
				try {
					( new \Pierre\Discovery\ProjectsCatalog() )->mark_known( $type, $slug ); } catch ( \Throwable $e ) {
					}
					++$added;
			} else {
				++$errors; }
		}
		// Maintain consistency: add locale to selected locales if not already there
		if ( $added > 0 ) {
			$selected_locales = get_option( 'pierre_selected_locales', array() );
			if ( ! is_array( $selected_locales ) ) {
				$selected_locales = array();
			}
			if ( ! in_array( $locale, $selected_locales, true ) ) {
				$selected_locales[] = $locale;
				update_option( 'pierre_selected_locales', $selected_locales );
			}
		}
		// translators: 1: number of projects added, 2: number of errors
		wp_send_json_success( array( 'message' => sprintf( __( 'Added %1$d project(s), %2$d error(s).', 'wp-pierre' ), $added, $errors ) ) );
	}

	/**
	 * Bulk add projects to a locale (from Projects page)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_bulk_add_projects_to_locale(): void {
		$this->require_manage_permission();
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'wp-pierre' ) ) );
				return;
			}
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-pierre' ) ) );
			return;
		}

		$locale = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		if ( empty( $locale ) ) {
			wp_send_json_error( array( 'message' => __( 'Locale code is required.', 'wp-pierre' ) ) );
			return;
		}

		// Validate locale exists
		$translations = $this->get_cached_translations();
		$locale_valid = false;
		foreach ( $translations as $t ) {
			if ( ( $t['language'] ?? '' ) === $locale ) {
				$locale_valid = true;
				break;
			}
		}

		if ( ! $locale_valid ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Locale %s is not valid.', 'wp-pierre' ), $locale ) ) );
			return;
		}

		// Parse projects array from POST
		$projects = array();
		if ( isset( $_POST['projects'] ) && is_array( $_POST['projects'] ) ) {
			foreach ( $_POST['projects'] as $project ) {
				$slug = sanitize_key( $project['slug'] ?? '' );
				$type = sanitize_key( $project['type'] ?? 'meta' );
				if ( ! empty( $slug ) ) {
					$projects[] = array(
						'slug' => $slug,
						'type' => $type,
					);
				}
			}
		}

		if ( empty( $projects ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid projects provided.', 'wp-pierre' ) ) );
			return;
		}

		$added           = 0;
		$errors          = 0;
		$error_details   = array();
		$already_watched = array();
		$valid_types     = array( 'meta', 'plugin', 'theme', 'app' );

		// Check which projects are already watched
		$watched      = $this->project_watcher->get_watched_projects();
		$watched_keys = array();
		foreach ( $watched as $wp ) {
			$w_slug   = $wp['slug'] ?? ( $wp['project_slug'] ?? '' );
			$w_locale = $wp['locale'] ?? ( $wp['locale_code'] ?? '' );
			if ( $w_slug && $w_locale ) {
				$watched_keys[ "{$w_slug}_{$w_locale}" ] = true;
			}
		}

		foreach ( $projects as $project ) {
			$slug = $project['slug'];
			$type = $project['type'];

			// Validate type
			if ( ! in_array( $type, $valid_types, true ) ) {
				$type = 'meta';
			}

			// Check if already watched
			$project_key = "{$slug}_{$locale}";
			if ( isset( $watched_keys[ $project_key ] ) ) {
				$already_watched[] = $slug;
				++$errors;
				continue;
			}

			$result = $this->project_watcher->watch_project( $slug, $locale, $type );

			if ( $result ) {
				// Ensure type is stored
				$watched = $this->project_watcher->get_watched_projects();
				$opt     = get_option( 'pierre_watched_projects', array() );
				if ( isset( $opt[ $project_key ] ) ) {
					$opt[ $project_key ]['type'] = $type;
					update_option( 'pierre_watched_projects', $opt );
				}
				// Mark as known for catalog prioritization
				try {
					( new \Pierre\Discovery\ProjectsCatalog() )->mark_known( $type, $slug );
				} catch ( \Throwable $e ) {
					// Silent fail
				}
				++$added;
			} else {
				++$errors;
				$error_details[] = sprintf( __( '%s (scraping failed or project not accessible)', 'wp-pierre' ), $slug );
			}
		}

		// Maintain consistency: add locale to selected locales if not already there
		if ( $added > 0 ) {
			$selected_locales = get_option( 'pierre_selected_locales', array() );
			if ( ! is_array( $selected_locales ) ) {
				$selected_locales = array();
			}
			if ( ! in_array( $locale, $selected_locales, true ) ) {
				$selected_locales[] = $locale;
				update_option( 'pierre_selected_locales', $selected_locales );
			}
		}

		// Build detailed message
		$message_parts = array();
		if ( $added > 0 ) {
			// translators: %d: number of projects
			$message_parts[] = sprintf( _n( 'Added %d project', 'Added %d projects', $added, 'wp-pierre' ), $added );
		}
		if ( ! empty( $already_watched ) ) {
			// translators: %s: comma-separated list of project slugs
			$message_parts[] = sprintf( __( '%s already watched', 'wp-pierre' ), implode( ', ', $already_watched ) );
		}
		if ( $errors > count( $already_watched ) ) {
			// translators: %d: number of errors
			$message_parts[] = sprintf( _n( '%d error', '%d errors', $errors - count( $already_watched ), 'wp-pierre' ), $errors - count( $already_watched ) );
			if ( ! empty( $error_details ) ) {
				$message_parts[] = '(' . implode( '; ', array_slice( $error_details, 0, 3 ) ) . ')';
			}
		}

		$message = ! empty( $message_parts ) ? implode( '. ', $message_parts ) : __( 'No projects were added.', 'wp-pierre' );

		if ( $added > 0 && $errors === 0 ) {
			wp_send_json_success( array( 'message' => $message ) );
		} elseif ( $added > 0 ) {
			wp_send_json_success(
				array(
					'message' => $message,
					'warning' => true,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => $message ) );
		}
	}

	/** Preview bulk add: counts what will be added vs already present */
	public function ajax_bulk_preview_from_discovery(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) );
		}
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			$this->respond_error( 'forbidden', __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) );
		}
		$locale = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		if ( $locale === '' ) {
			$this->respond_error( 'missing_locale', __( 'Locale code is required.', 'wp-pierre' ), 400 );
		}
		$lib = get_option( 'pierre_projects_discovery', array() );
		if ( ! is_array( $lib ) || empty( $lib ) ) {
			$this->respond_error( 'empty_library', __( 'Library is empty.', 'wp-pierre' ), 400 );
		}
		$watched = get_option( 'pierre_watched_projects', array() );
		$already = 0;
		$to_add  = 0;
		$invalid = 0;
		foreach ( $lib as $item ) {
			$type = sanitize_key( $item['type'] ?? 'meta' );
			$slug = sanitize_key( $item['slug'] ?? '' );
			if ( $slug === '' ) {
				++$invalid;
				continue; }
			$key = $slug . '_' . $locale;
			if ( isset( $watched[ $key ] ) ) {
				++$already;
			} else {
				++$to_add; }
		}
		wp_send_json_success(
			array(
				'already' => $already,
				'to_add'  => $to_add,
				'invalid' => $invalid,
			)
		);
	}

	/**
	 * Fetch locales from WordPress.org (via wp_get_available_translations)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_fetch_locales(): void {
		$t0 = microtime( true );
		// Mark as running (15 min TTL)
		set_transient( 'pierre_locales_fetch_running', time(), 15 * MINUTE_IN_SECONDS );
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			delete_transient( 'pierre_locales_fetch_running' );
			update_option( 'pierre_locales_fetch_error', 'invalid_nonce:' . time() );
			$this->respond_error( 'invalid_nonce', __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			return;
		}
		$user_id = get_current_user_id();
		if ( ! $this->role_manager->user_can_manage_locale_settings( $user_id, '' ) ) {
			do_action(
				'wp_pierre_debug',
				'fetch_locales: permission denied',
				array(
					'source' => 'AdminController',
					'user'   => (int) $user_id,
				)
			);
			delete_transient( 'pierre_locales_fetch_running' );
			update_option( 'pierre_locales_fetch_error', 'forbidden:' . time() );
			$this->respond_error( 'forbidden', __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
			return;
		}
		// Optional force refresh
		$force = ! empty( $_POST['force'] ) && (int) $_POST['force'] === 1;
		if ( $force ) {
			delete_transient( 'pierre_available_locales' );
		}
		// Prefer persistent cache option with hash/last_fetched
		$cache = get_option( 'pierre_locales_cache' );
		if ( ! $force && is_array( $cache ) && ! empty( $cache['data'] ) ) {
			delete_transient( 'pierre_locales_fetch_running' );
			delete_option( 'pierre_locales_fetch_error' );
			wp_send_json_success( array( 'locales' => $cache['data'] ) );
		}

		$list         = array();
		$translations = $this->get_cached_translations();
		if ( is_array( $translations ) && ! empty( $translations ) ) {
			foreach ( $translations as $t ) {
				if ( ! empty( $t['language'] ) ) {
					$list[] = array(
						'code'  => $t['language'],
						'label' => trim( ( $t['native_name'] ?? ( $t['english_name'] ?? $t['language'] ) ) . ' (' . $t['language'] . ')' ),
					);
				}
			}
		}

		// Fallback to WP.org API if empty
		if ( empty( $list ) ) {
			global $wp_version;
			$url       = 'https://api.wordpress.org/translations/core/1.0/';
			$args      = array(
				'timeout'    => 10,
				'user-agent' => 'wp-pierre/' . ( defined( 'PIERRE_VERSION' ) ? PIERRE_VERSION : '1.0.0' ) . '; ' . home_url( '/' ),
			);
			$response  = wp_safe_remote_get( add_query_arg( array( 'version' => $wp_version ), $url ), $args );
			$resp_code = is_wp_error( $response ) ? $response->get_error_code() : wp_remote_retrieve_response_code( $response );
			if ( ! is_wp_error( $response ) && $resp_code === 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( is_array( $body ) && ! empty( $body['translations'] ) ) {
					foreach ( $body['translations'] as $t ) {
						if ( ! empty( $t['language'] ) ) {
							$list[] = array(
								'code'  => $t['language'],
								'label' => trim( ( ( $t['native_name'] ?? '' ) ?: ( $t['english_name'] ?? $t['language'] ) ) . ' (' . $t['language'] . ')' ),
							);
						}
					}
				}
			} else {
				do_action(
					'wp_pierre_debug',
					'fetch_locales: WP.org API error',
					array(
						'source' => 'AdminController',
						'code'   => (int) $resp_code,
					)
				);
			}
		}

		if ( empty( $list ) ) {
			do_action( 'wp_pierre_debug', 'fetch_locales: empty list after attempts', array( 'source' => 'AdminController' ) );
			delete_transient( 'pierre_locales_fetch_running' );
			update_option( 'pierre_locales_fetch_error', 'upstream_empty:' . time() );
			$this->respond_error( 'upstream_empty', __( 'Pierre says: No locales found from WordPress.org. Please check outgoing HTTP.', 'wp-pierre' ) . ' 😢', 502 );
		}

		// Build and persist normalized cache (lightweight; defer heavy enrich to per-locale checks)
		$enriched = $this->build_locales_cache_from_list( $list, false );
		$this->persist_locales_cache( $enriched );
		delete_transient( 'pierre_locales_fetch_running' );
		delete_option( 'pierre_locales_fetch_error' );
		do_action(
			'wp_pierre_debug',
			'locales cache refreshed',
			array(
				'scope'  => 'locales',
				'action' => 'refresh',
			)
		);
		wp_send_json_success( array( 'locales' => $enriched ) );
	}

	/**
	 * Persist locales cache with hash and timestamp.
	 *
	 * @param array $data Locales data to cache.
	 * @return void
	 */
	private function persist_locales_cache( array $data ): void {
		$payload = array(
			'data'         => $data,
			'hash'         => hash( 'sha256', wp_json_encode( $data ) ),
			'last_fetched' => time(),
		);
		update_option( 'pierre_locales_cache', $payload, false );
	}

	/** Public hook to refresh locales cache (cron/manual) */
	public function register_locales_refresh_hook(): void {
		add_action(
			'pierre_refresh_locales_cache',
			function () {
				try {
					$list = $this->fetch_base_locales_list();
					// Strong enrich on scheduled refresh (translate_slug + rosetta)
					$data     = $this->build_locales_cache_from_list( $list, true );
					$existing = get_option( 'pierre_locales_cache' );
					$new_hash = hash( 'sha256', wp_json_encode( $data ) );
					$old_hash = is_array( $existing ) ? ( $existing['hash'] ?? '' ) : '';
					if ( $new_hash !== $old_hash ) {
						$this->persist_locales_cache( $data );
					}
				} catch ( \Exception $e ) {
					do_action( 'wp_pierre_debug', 'locales refresh failed: ' . $e->getMessage(), array( 'source' => 'AdminController' ) );
				}
			}
		);
	}

	/** Fetch base locales list using WP functions / API */
	private function fetch_base_locales_list(): array {
		$list         = array();
		$translations = $this->get_cached_translations();
		if ( is_array( $translations ) && ! empty( $translations ) ) {
			foreach ( $translations as $t ) {
				if ( ! empty( $t['language'] ) ) {
					$list[] = array(
						'code'  => $t['language'],
						'label' => trim( ( $t['native_name'] ?? ( $t['english_name'] ?? $t['language'] ) ) . ' (' . $t['language'] . ')' ),
					);
				}
			}
		}
		if ( empty( $list ) ) {
			global $wp_version;
			$url  = 'https://api.wordpress.org/translations/core/1.0/';
			$resp = wp_remote_get(
				add_query_arg( array( 'version' => $wp_version ), $url ),
				array(
					'timeout'    => 10,
					'user-agent' => 'wp-pierre/' . ( defined( 'PIERRE_VERSION' ) ? PIERRE_VERSION : '1.0.0' ) . '; ' . home_url( '/' ),
				)
			);
			if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) === 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $resp ), true );
				if ( is_array( $body ) && ! empty( $body['translations'] ) ) {
					foreach ( $body['translations'] as $t ) {
						if ( ! empty( $t['language'] ) ) {
							$list[] = array(
								'code'  => $t['language'],
								'label' => trim( ( ( $t['native_name'] ?? '' ) ?: ( $t['english_name'] ?? $t['language'] ) ) . ' (' . $t['language'] . ')' ),
							);
						}
					}
				}
			}
		}
		return $list;
	}

	/**
	 * Build normalized locales with translate_slug/team_locale/rosetta and optional enrich (translate slug, rosetta check, slack).
	 *
	 * @param array $list Locales list.
	 * @param bool  $resolve_translate_slug Whether to resolve translate slug.
	 * @return array Normalized locales cache.
	 */
	private function build_locales_cache_from_list( array $list, bool $resolve_translate_slug = false ): array {
		$data = array();
		foreach ( $list as $item ) {
			$code = (string) ( $item['code'] ?? '' );
			if ( $code === '' ) {
				continue; }
			$label       = (string) ( $item['label'] ?? $code );
			$slug_source = str_replace( '_', '-', $code );
			$slug        = is_string( $slug_source ) ? strtolower( $slug_source ) : '';
			if ( $slug === '' ) {
				$slug = strtolower( $code ); }
			$rosetta        = $slug !== '' ? ( $slug . '.wordpress.org' ) : '';
			$translate_slug = $slug;
			$slack          = '';
			if ( $resolve_translate_slug ) {
				$ts = $this->detect_translate_slug_from_team_page( $code );
				if ( is_string( $ts ) && $ts !== '' ) {
					$translate_slug = $ts; }
				// Resolve Rosetta host from candidates (robust across fr vs fr-fr)
				$candidates = $this->find_rosetta_host_candidates( $code );
				$picked     = $this->pick_active_rosetta( $candidates );
				$rosetta    = $picked ?: '';
				$slack      = $this->detect_slack_from_team_page( $code );
			}
			$data[] = array(
				'code'           => $code,
				'label'          => $label,
				'slug'           => $slug,
				'translate_slug' => $translate_slug,
				'team_locale'    => $code,
				'rosetta'        => $rosetta,
				'slack_url'      => $slack ?: null,
			);
		}
		return $data;
	}

	/**
	 * Try to extract translate.wordpress.org slug from the team page.
	 *
	 * @param string $code Locale code.
	 * @return string Translate slug.
	 */
	private function detect_translate_slug_from_team_page( string $code ): string {
		$url       = 'https://make.wordpress.org/polyglots/teams/?locale=' . rawurlencode( $code );
		$cache_key = 'pierre_team_page_' . strtolower( $code );
		$html      = get_transient( $cache_key );
		if ( ! is_string( $html ) ) {
			$resp = $this->http_get_with_retries( $url, 2, 12 );
			if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
				return ''; }
			$html = wp_remote_retrieve_body( $resp );
			if ( is_string( $html ) && $html !== '' ) {
				set_transient( $cache_key, $html, DAY_IN_SECONDS ); }
		}
		if ( ! is_string( $html ) || $html === '' ) {
			return ''; }
		if ( preg_match( '#translate\.WordPress\.org/locale/([a-z0-9\-]+)/?#i', $html, $m ) ) {
			return strtolower( $m[1] );
		}
		return '';
	}

	/**
	 * Check if a rosetta host responds with non-404.
	 *
	 * @param string $host Rosetta host.
	 * @return bool Whether host is active.
	 */
	private function is_rosetta_host_active( string $host ): bool {
		$url  = 'https://' . ltrim( $host, '/' );
		$resp = $this->http_head_with_retries( $url, 2, 8 );
		if ( ! is_wp_error( $resp ) ) {
			$code = wp_remote_retrieve_response_code( $resp );
			if ( $code && $code !== 404 ) {
				return true; }
		}
		$resp = $this->http_get_with_retries( $url, 1, 8 );
		if ( is_wp_error( $resp ) ) {
			return false; }
		$code = wp_remote_retrieve_response_code( $resp );
		return (int) $code !== 404 && (int) $code !== 0;
	}

	/**
	 * Build Rosetta host candidates for a given locale code.
	 *
	 * @param string $code Locale code.
	 * @return array Rosetta host candidates.
	 */
	private function find_rosetta_host_candidates( string $code ): array {
		// Normalize: fr_FR → fr-fr, fr_CA → fr-ca, fi → fi
		$norm = strtolower( str_replace( '_', '-', $code ) );
		$lang = substr( $norm, 0, 2 );
		$c    = array();

		// Always try base language (e.g., fr.wordpress.org for fr_FR, fr-CA, fr_CA)
		if ( $lang && strlen( $lang ) === 2 ) {
			$c[] = $lang . '.wordpress.org';
		}

		// Try normalized code (e.g., fr-fr.wordpress.org, fr-ca.wordpress.org)
		if ( $norm && $norm !== $lang ) {
			$c[] = $norm . '.wordpress.org';
		}

		// For codes with variant (xx-YY), also try base (handled above, but ensure no duplicates)
		// Special case: if code is xx-YY format, prioritize xx-YY then xx
		if ( preg_match( '/^([a-z]{2})-([a-z]{2,})$/i', $norm, $m ) ) {
			// Already added: lang (xx) and norm (xx-YY)
			// This covers fr-ca.wordpress.org and fr.wordpress.org for fr_CA
		}

		return array_values( array_unique( $c ) );
	}

	/**
	 * Pick first active Rosetta among candidates.
	 *
	 * @param array $hosts Host candidates array.
	 * @return string Active rosetta host.
	 */
	private function pick_active_rosetta( array $hosts ): string {
		foreach ( $hosts as $h ) {
			if ( ! is_string( $h ) || $h === '' ) {
				continue; }
			$url  = 'https://' . ltrim( $h, '/' );
			$resp = $this->http_head_with_retries( $url, 2, 6 );
			$code = is_wp_error( $resp ) ? 0 : (int) wp_remote_retrieve_response_code( $resp );
			if ( $code && $code !== 404 ) {
				return $h; }
			$resp = $this->http_get_with_retries( $url, 1, 6 );
			$code = is_wp_error( $resp ) ? 0 : (int) wp_remote_retrieve_response_code( $resp );
			if ( $code && $code !== 404 ) {
				return $h; }
		}
		return '';
	}

	/**
	 * Extract Slack URL from the locale team page if present.
	 *
	 * @param string $code Locale code.
	 * @return string Slack URL.
	 */
	private function detect_slack_from_team_page( string $code ): string {
		$url       = 'https://make.wordpress.org/polyglots/teams/?locale=' . rawurlencode( $code );
		$cache_key = 'pierre_team_page_' . strtolower( $code );
		$html      = get_transient( $cache_key );
		if ( ! is_string( $html ) ) {
			$resp = $this->http_get_with_retries( $url, 2, 12 );
			if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
				return ''; }
			$html = wp_remote_retrieve_body( $resp );
			if ( is_string( $html ) && $html !== '' ) {
				set_transient( $cache_key, $html, DAY_IN_SECONDS ); }
		}
		if ( ! is_string( $html ) || $html === '' ) {
			return ''; }
		if ( preg_match( '#https?://([a-z0-9\-]+\.slack\.com)(?:/[\w\-\./%]*)?#i', $html, $m ) ) {
			return 'https://' . strtolower( $m[1] );
		}
		return '';
	}

	private function http_get_with_retries( string $url, int $retries, int $timeout ) {
		$defaults = \Pierre\Plugin::get_http_defaults();
		$args     = array(
			'timeout'     => $timeout > 0 ? $timeout : ( $defaults['timeout'] ?? 30 ),
			'redirection' => $defaults['redirection'] ?? 3,
			'user-agent'  => $defaults['user-agent'] ?? 'wp-pierre/1.0.0; ' . home_url( '/' ),
		);
		$resp     = wp_remote_get( $url, $args );
		for ( $i = 0; $i < $retries && ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) >= 500 ); $i++ ) {
			usleep( 500000 );
			$resp = wp_remote_get( $url, $args );
		}
		return $resp;
	}
	private function http_head_with_retries( string $url, int $retries, int $timeout ) {
		$defaults = \Pierre\Plugin::get_http_defaults();
		$args     = array(
			'timeout'     => $timeout > 0 ? $timeout : ( $defaults['timeout'] ?? 30 ),
			'redirection' => $defaults['redirection'] ?? 3,
			'user-agent'  => $defaults['user-agent'] ?? 'wp-pierre/1.0.0; ' . home_url( '/' ),
		);
		$resp     = wp_remote_head( $url, $args );
		for ( $i = 0; $i < $retries && ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) >= 500 ); $i++ ) {
			usleep( 500000 );
			$resp = wp_remote_head( $url, $args );
		}
		return $resp;
	}

	public function ajax_check_locale_status(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', 'denied' ); }
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', 'bad_nonce' ); }
		$code = sanitize_key( wp_unslash( $_POST['code'] ?? '' ) );
		if ( $code === '' ) {
			$this->respond_error( 'invalid_payload', 'missing code', 400 ); }
		$cache   = get_option( 'pierre_locales_cache' );
		$rows    = is_array( $cache ) && ! empty( $cache['data'] ) ? (array) $cache['data'] : array();
		$updated = false;
		$result  = array();
		foreach ( $rows as &$r ) {
			if ( ( $r['code'] ?? '' ) !== $code ) {
				continue; }
			$slug           = (string) ( $r['slug'] ?? '' );
			$translate_slug = (string) ( $r['translate_slug'] ?? $slug );
			$ts             = $this->detect_translate_slug_from_team_page( $code );
			if ( is_string( $ts ) && $ts !== '' ) {
				$translate_slug = $ts; }
			$rosetta = (string) ( $r['rosetta'] ?? '' );
			if ( $rosetta === '' && $slug ) {
				$candidates = $this->find_rosetta_host_candidates( $code );
				$rosetta    = $this->pick_active_rosetta( $candidates ) ?: '';
			}
			$rosetta_ok = $rosetta !== '' ? $this->is_rosetta_host_active( $rosetta ) : false;
			$issues     = array();
			if ( strtolower( str_replace( '_', '-', $code ) ) !== strtolower( $translate_slug ) ) {
				$issues[] = 'translate_slug≠code'; }
			if ( ! $rosetta_ok ) {
				$issues[] = 'rosetta_inactive_or_missing';
				$rosetta  = ''; }
			$r['translate_slug'] = $translate_slug;
			$r['rosetta']        = $rosetta;
			$r['checked_at']     = time();
			$updated             = true;
			$result              = array(
				'translate_slug' => $translate_slug,
				'rosetta'        => $rosetta,
				'issues'         => $issues,
			);
			if ( ! empty( $issues ) ) {
				$this->append_locale_log( $code, $issues ); }
			break;
		}
		unset( $r );
		if ( $updated ) {
			$cache['data'] = $rows;
			$this->persist_locales_cache( $rows ); }
		wp_send_json_success(
			array(
				'code'   => $code,
				'status' => $result,
			)
		);
	}

	private function append_locale_log( string $code, array $issues ): void {
		$log = get_option( 'pierre_locales_log' );
		if ( ! is_array( $log ) ) {
			$log = array(); }
		$log[] = array(
			'code'   => $code,
			'issues' => $issues,
			'time'   => time(),
		);
		if ( count( $log ) > 500 ) {
			$log = array_slice( $log, -500 ); }
		update_option( 'pierre_locales_log', $log, false );
	}

	/** Clear anomalies log */
	public function ajax_clear_locale_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->respond_error( 'forbidden', 'denied' ); }
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', 'bad_nonce' ); }
		update_option( 'pierre_locales_log', array(), false );
		wp_send_json_success( array( 'message' => __( 'Anomalies log cleared.', 'wp-pierre' ) ) );
	}

	/** Export anomalies log JSON */
	public function ajax_export_locale_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied', 'wp-pierre' ) ); }
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			wp_die( __( 'Invalid nonce', 'wp-pierre' ) ); }
		$log = get_option( 'pierre_locales_log' );
		if ( ! is_array( $log ) ) {
			$log = array(); }
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="pierre_locales_anomalies_log.json"' );
		echo wp_json_encode( $log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/**
	 * Fetch and cache Slack workspace hosts from the Polyglots handbook page.
	 * Returns array of hosts like ['wpfr.slack.com', ...]
	 */
	private function get_local_slack_workspaces(): array {
		$cached = get_transient( 'pierre_slack_workspaces' );
		if ( is_array( $cached ) ) {
			return $cached; }
		$url   = 'https://make.wordpress.org/polyglots/handbook/translating/teams/local-slacks/';
		$resp  = wp_safe_remote_get(
			$url,
			array(
				'timeout'    => 12,
				'user-agent' => 'wp-pierre/' . ( defined( 'PIERRE_VERSION' ) ? PIERRE_VERSION : '1.0.0' ),
			)
		);
		$hosts = array();
		if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) === 200 ) {
			$html = wp_remote_retrieve_body( $resp );
			if ( is_string( $html ) && $html !== '' ) {
				if ( preg_match_all( '#https?://([a-z0-9\-]+\.slack\.com)(?:/[^\s\"\']*)?#i', $html, $m ) ) {
					foreach ( $m[1] as $host ) {
						$hosts[ $host ] = true; }
				}
			}
		}
		$list = array_keys( $hosts );
		set_transient( 'pierre_slack_workspaces', $list, 12 * HOUR_IN_SECONDS );
		return $list;
	}

	/**
	 * Best-effort mapping from locale slug/lang to a Slack host.
	 *
	 * @param string $slug Locale slug.
	 * @param string $lang Language code.
	 * @param array  $hosts Hosts array.
	 * @return string Best Slack host.
	 */
	private function find_best_slack_for_slug( string $slug, string $lang, array $hosts ): string {
		$slug = strtolower( $slug );
		$lang = strtolower( $lang );
		foreach ( $hosts as $h ) {
			if ( ! is_string( $h ) ) {
				continue;
			} if ( $slug !== '' && strpos( $h, $slug ) !== false ) {
				return $h;
			}
		}
		foreach ( $hosts as $h ) {
			if ( ! is_string( $h ) ) {
				continue;
			} if ( $lang !== '' && str_starts_with( $h, $lang ) ) {
				return $h;
			}
		}
		foreach ( $hosts as $h ) {
			if ( ! is_string( $h ) ) {
				continue;
			} if ( $lang !== '' && preg_match( '#(^|\-)' . preg_quote( $lang, '#' ) . '(\-|\.)#', $h ) ) {
				return $h;
			}
		}
		foreach ( array( 'wp' . $lang, 'wp-' . $lang, 'wp' . $slug, 'wp-' . $slug ) as $needle ) {
			foreach ( $hosts as $h ) {
				if ( ! is_string( $h ) ) {
					continue;
				} if ( $needle !== '' && strpos( $h, $needle ) !== false ) {
					return $h;
				}
			}
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
		// Get locales data for filters (cached)
		$translations   = $this->get_cached_translations();
		$locales_labels = array();
		foreach ( $translations as $slug => $t ) {
			if ( ! empty( $t['language'] ) ) {
				$code                    = $t['language'];
				$native                  = $t['native_name'] ?? ( $t['english_name'] ?? '' );
				$locales_labels[ $code ] = trim( $code . ' — ' . $native );
			}
		}

		return array(
			'stats'            => $this->get_reports_stats(),
			'watched_projects' => $this->project_watcher->get_watched_projects(),
			'locales_labels'   => $locales_labels,
		);
	}

	/**
	 * Pierre gets admin statistics! 🪨
	 *
	 * @since 1.0.0
	 * @return array Admin statistics
	 */
	private function get_admin_stats(): array {
		$watched_projects = $this->project_watcher->get_watched_projects();
		$current_user_id  = get_current_user_id();
		$user_assignments = $current_user_id ? $this->user_project_link->get_user_assignments_with_details( $current_user_id ) : array();

		// Surveillance statistics
		$last_run_ts       = (int) get_option( 'pierre_last_surv_run', 0 );
		$last_run_duration = (int) get_option( 'pierre_last_surv_duration_ms', 0 );
		$progress          = get_transient( 'pierre_surv_progress' );
		$processed_count   = is_array( $progress ) ? (int) ( $progress['processed'] ?? 0 ) : 0;
		$total_count       = is_array( $progress ) ? (int) ( $progress['total'] ?? 0 ) : 0;

		// Count projects in backoff
		$backoff_count = 0;
		global $wpdb;
		$backoff_keys  = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value > %d",
				$wpdb->esc_like( '_transient_pierre_scraper_backoff_until_' ) . '%',
				time()
			)
		);
		$backoff_count = count( $backoff_keys );

		// Get last errors (stored in transient or option)
		$last_errors  = get_transient( 'pierre_last_surv_errors' );
		$errors_count = is_array( $last_errors ) ? count( $last_errors ) : 0;

		$stats = array(
			array(
				'label' => 'Watched Projects',
				'value' => count( $watched_projects ),
			),
			array(
				'label' => 'Your Assignments',
				'value' => count( $user_assignments ),
			),
			array(
				'label' => 'Surveillance Active',
				'value' => $this->project_watcher->is_surveillance_active() ? 'Yes' : 'No',
			),
			array(
				'label' => 'Notifications Ready',
				'value' => $this->slack_notifier->is_ready() ? 'Yes' : 'No',
			),
		);

		// Add surveillance details if available
		if ( $last_run_ts > 0 ) {
			$stats[] = array(
				'label' => 'Last Run',
				'value' => $last_run_ts > 0 ? human_time_diff( $last_run_ts ) . ' ago' : 'Never',
			);
		}

		if ( $last_run_duration > 0 ) {
			$stats[] = array(
				'label' => 'Last Run Duration',
				'value' => round( $last_run_duration / 1000, 2 ) . 's',
			);
		}

		if ( $total_count > 0 ) {
			$stats[] = array(
				'label' => 'Processed (Current Run)',
				'value' => $processed_count . ' / ' . $total_count,
			);
		}

		if ( $backoff_count > 0 ) {
			$stats[] = array(
				'label' => 'Projects in Backoff',
				'value' => $backoff_count,
			);
		}

		if ( $errors_count > 0 ) {
			$stats[] = array(
				'label' => 'Last Errors Count',
				'value' => $errors_count,
			);
		}

		return $stats;
	}

	/**
	 * Pierre gets teams statistics! 🪨
	 *
	 * @since 1.0.0
	 * @return array Teams statistics
	 */
	private function get_teams_stats(): array {
		return array(
			array(
				'label' => 'Total Users',
				'value' => count( get_users() ),
			),
			array(
				'label' => 'Pierre Roles',
				'value' => count( $this->role_manager->get_roles() ),
			),
		);
	}

	/**
	 * Pierre gets projects statistics! 🪨
	 *
	 * @since 1.0.0
	 * @return array Projects statistics
	 */
	private function get_projects_stats(): array {
		$watched_projects = $this->project_watcher->get_watched_projects();

		return array(
			array(
				'label' => 'Watched Projects',
				'value' => count( $watched_projects ),
			),
			array(
				'label' => 'Surveillance Status',
				'value' => $this->project_watcher->is_surveillance_active() ? 'Active' : 'Inactive',
			),
		);
	}

	/**
	 * Pierre gets reports statistics! 🪨
	 *
	 * @since 1.0.0
	 * @return array Reports statistics
	 */
	private function get_reports_stats(): array {
		return array(
			array(
				'label' => 'Reports Available',
				'value' => 'Coming Soon',
			),
		);
	}

	/**
	 * Pierre handles AJAX admin stats request! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_get_admin_stats(): void {
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_view_dashboard' ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		$stats = $this->get_admin_stats();
		wp_send_json_success( $stats );
	}

	/**
	 * Pierre handles AJAX assign user! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_assign_user(): void {
		$this->require_manage_permission();
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre checks permissions! 🪨
		// Locale Manager can assign, GTE cannot
		$current_user_id = get_current_user_id();
		$locale_code     = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		if ( ! $this->role_manager->user_can_assign_projects( $current_user_id, $locale_code ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission! Only Locale Managers and site administrators can assign users.', 'wp-pierre' ) . ' 😢' );
		}

		$user_id      = absint( wp_unslash( $_POST['user_id'] ) ?? 0 );
		$project_type = sanitize_key( wp_unslash( $_POST['project_type'] ?? '' ) );
		$project_slug = sanitize_key( wp_unslash( $_POST['project_slug'] ?? '' ) );
		$role         = sanitize_key( wp_unslash( $_POST['role'] ?? '' ) );
		$assigned_by  = $current_user_id;

		$result = $this->user_project_link->assign_user_to_project(
			$user_id,
			$project_type,
			$project_slug,
			$locale_code,
			$role,
			$assigned_by
		);

		wp_send_json_success( $result );
	}

	/**
	 * Pierre handles AJAX remove user! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_remove_user(): void {
		$this->require_manage_permission();
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_assign_projects' ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		$user_id      = absint( wp_unslash( $_POST['user_id'] ) ?? 0 );
		$project_slug = sanitize_key( wp_unslash( $_POST['project_slug'] ?? '' ) );
		$locale_code  = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		$removed_by   = get_current_user_id();

		$result = $this->user_project_link->remove_user_from_project(
			$user_id,
			$project_slug,
			$locale_code,
			$removed_by
		);

		wp_send_json_success( $result );
	}

	/**
	 * Pierre handles AJAX test notification! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_test_notification(): void {
		// Pierre checks nonce! 🪨 (accept admin or generic nonce)
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) && ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}

		// Pierre checks permissions! 🪨 (fallback to manage_options until custom caps are wired)
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}

		// Pierre gets webhook URL from form if provided! 🪨 (accept both legacy and global field names)
		$webhook_url = sanitize_url( wp_unslash( $_POST['slack_webhook_url'] ?? '' ) );
		if ( empty( $webhook_url ) ) {
			$webhook_url = sanitize_url( wp_unslash( $_POST['global_webhook_url'] ?? '' ) );
		}
		if ( ! empty( $webhook_url ) ) {
			$this->slack_notifier->set_webhook_url( $webhook_url );
		}

		$result = $this->slack_notifier->test_notification();
		// Persist last test outcome/time
		update_option(
			'pierre_last_global_webhook_test',
			array(
				'time'    => current_time( 'timestamp' ),
				'success' => (bool) $result,
			)
		);
		do_action(
			'wp_pierre_debug',
			'webhook test completed',
			array(
				'scope'  => 'webhook',
				'action' => 'test',
				'code'   => $result ? 'ok' : 'fail',
			)
		);
		if ( $result ) {
			wp_send_json_success(
				array(
					'message'     => __( 'Slack webhook test succeeded! Check your Slack channel.', 'wp-pierre' ) . ' 🪨',
					'test_result' => $result,
				)
			);
		} else {
			$detail = method_exists( $this->slack_notifier, 'get_last_error' ) ? (string) ( $this->slack_notifier->get_last_error() ?? '' ) : '';
			$this->respond_error( 'slack_test_failed', __( 'Slack webhook test failed. Verify the webhook URL is correct.', 'wp-pierre' ) . ' 😢', 400, array( 'error' => $detail ) );
		}
	}

	/** Run surveillance now with cooldown */
	public function ajax_run_surveillance_now(): void {
		$t0 = microtime( true );
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$last = (int) get_option( 'pierre_last_run_now_surveillance', 0 );
		if ( $last && ( time() - $last ) < 60 ) {
			$this->respond_error( 'cooldown', __( 'Please wait before running again (cooldown 60s).', 'wp-pierre' ), 429 ); }
		update_option( 'pierre_last_run_now_surveillance', time() );
		// Force run bypasses the global enabled switch
		$this->container->get( \Pierre\Surveillance\CronManager::class )->run_surveillance_check( true );
		do_action(
			'wp_pierre_debug',
			'surveillance run triggered',
			array(
				'scope'  => 'cron',
				'action' => 'run_now',
			)
		);
		wp_send_json_success( array( 'message' => __( 'Surveillance run triggered.', 'wp-pierre' ) ) );
	}

	/** Run cleanup now with cooldown */
	public function ajax_run_cleanup_now(): void {
		$t0 = microtime( true );
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Invalid nonce.', 'wp-pierre' ) ); }
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			$this->respond_error( 'forbidden', __( 'Permission denied.', 'wp-pierre' ) ); }
		$last = (int) get_option( 'pierre_last_run_now_cleanup', 0 );
		if ( $last && ( time() - $last ) < 60 ) {
			$this->respond_error( 'cooldown', __( 'Please wait before running again (cooldown 60s).', 'wp-pierre' ), 429 ); }
		update_option( 'pierre_last_run_now_cleanup', time() );
		$this->container->get( \Pierre\Surveillance\CronManager::class )->run_cleanup_task();
		do_action(
			'wp_pierre_debug',
			'cleanup run triggered',
			array(
				'scope'  => 'cron',
				'action' => 'cleanup_now',
			)
		);
		wp_send_json_success( array( 'message' => __( 'Cleanup run triggered.', 'wp-pierre' ) ) );
	}

	/**
	 * Pierre handles AJAX save settings! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_save_settings(): void {
		$this->require_manage_permission();
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			$this->respond_error( 'invalid_nonce', __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			return;
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			$this->respond_error( 'forbidden', __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
			return;
		}

		// Pierre gets existing settings to merge! 🪨
		$existing_settings = Settings::all();

		// Handle notification_types array properly
		$notification_types = array();
		if ( isset( $_POST['notification_types'] ) && is_array( $_POST['notification_types'] ) ) {
			$raw_types          = array_map( 'sanitize_key', wp_unslash( $_POST['notification_types'] ) );
			$valid_types        = array( 'new_strings', 'completion_update', 'needs_attention', 'errors' );
			$notification_types = array_intersect( $raw_types, $valid_types );
		}
		if ( empty( $notification_types ) ) {
			$notification_types = $existing_settings['notification_types'] ?? array( 'new_strings', 'completion_update' );
		}

		// Notification defaults (global)
		$defaults_new_strings    = absint( wp_unslash( $_POST['new_strings_threshold'] ?? ( $existing_settings['notification_defaults']['new_strings_threshold'] ?? 20 ) ) );
		$defaults_milestones_raw = (string) wp_unslash( $_POST['milestones'] ?? '' );
		$defaults_milestones     = array();
		if ( $defaults_milestones_raw !== '' ) {
			foreach ( explode( ',', $defaults_milestones_raw ) as $p ) {
				$p = trim( $p );
				if ( $p === '' ) {
					continue; }
				$defaults_milestones[] = (int) $p;
			}
		} else {
			$defaults_milestones = $existing_settings['notification_defaults']['milestones'] ?? array( 50, 80, 100 );
		}
		sort( $defaults_milestones );
		$defaults_mode = sanitize_key( wp_unslash( $_POST['mode'] ?? ( $existing_settings['notification_defaults']['mode'] ?? 'immediate' ) ) );
		if ( ! in_array( $defaults_mode, array( 'immediate', 'digest' ), true ) ) {
			$defaults_mode = 'immediate'; }
		$defaults_digest_type = sanitize_key( wp_unslash( $_POST['digest_type'] ?? ( $existing_settings['notification_defaults']['digest']['type'] ?? 'interval' ) ) );
		if ( ! in_array( $defaults_digest_type, array( 'interval', 'fixed_time' ), true ) ) {
			$defaults_digest_type = 'interval'; }
		$defaults_digest_interval = max( 15, absint( wp_unslash( $_POST['digest_interval_minutes'] ?? ( $existing_settings['notification_defaults']['digest']['interval_minutes'] ?? 60 ) ) ) );
		$defaults_digest_fixed    = preg_replace( '/[^0-9:]/', '', (string) wp_unslash( $_POST['digest_fixed_time'] ?? ( $existing_settings['notification_defaults']['digest']['fixed_time'] ?? '09:00' ) ) );
		if ( ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $defaults_digest_fixed ) ) {
			$defaults_digest_fixed = '09:00'; }

		$settings = array_merge(
			$existing_settings,
			array(
				'slack_webhook_url'       => sanitize_url( wp_unslash( $_POST['slack_webhook_url'] ?? $existing_settings['slack_webhook_url'] ?? '' ) ),
				'surveillance_interval'   => absint( wp_unslash( $_POST['surveillance_interval'] ?? $existing_settings['surveillance_interval'] ?? 15 ) ),
				'notifications_enabled'   => ! empty( wp_unslash( $_POST['notifications_enabled'] ?? $existing_settings['notifications_enabled'] ?? false ) ),
				'auto_start_surveillance' => ! empty( wp_unslash( $_POST['auto_start_surveillance'] ?? $existing_settings['auto_start_surveillance'] ?? false ) ),
				'max_projects_per_check'  => absint( wp_unslash( $_POST['max_projects_per_check'] ?? $existing_settings['max_projects_per_check'] ?? 10 ) ),
				'request_timeout'         => max( 3, absint( wp_unslash( $_POST['request_timeout'] ?? ( $existing_settings['request_timeout'] ?? 30 ) ) ) ),
				'notification_types'      => $notification_types,
				'notification_threshold'  => absint( wp_unslash( $_POST['notification_threshold'] ?? $existing_settings['notification_threshold'] ?? 80 ) ),
				'notification_defaults'   => array(
					'new_strings_threshold' => $defaults_new_strings,
					'milestones'            => $defaults_milestones,
					'mode'                  => $defaults_mode,
					'digest'                => array(
						'type'             => $defaults_digest_type,
						'interval_minutes' => $defaults_digest_interval,
						'fixed_time'       => $defaults_digest_fixed,
					),
				),
			)
		);

		// UI preferences
		$ui        = isset( $existing_settings['ui'] ) && is_array( $existing_settings['ui'] ) ? $existing_settings['ui'] : array();
		$menu_icon = sanitize_key( wp_unslash( $_POST['menu_icon'] ?? ( $ui['menu_icon'] ?? 'emoji' ) ) );
		if ( ! in_array( $menu_icon, array( 'emoji', 'dashicons' ), true ) ) {
			$menu_icon = 'emoji'; }
		$ui['menu_icon'] = $menu_icon;
		$settings['ui']  = $ui;

		// Global webhook unified model (optional fields)
		$gw                = $settings['global_webhook'] ?? array();
		$gw['enabled']     = ! empty( wp_unslash( $_POST['global_webhook_enabled'] ?? ( $gw['enabled'] ?? false ) ) );
		$gw['webhook_url'] = sanitize_url( wp_unslash( $_POST['global_webhook_url'] ?? ( $gw['webhook_url'] ?? '' ) ) );
		if ( $gw['webhook_url'] === '' ) {
			$gw['enabled'] = false; }
		// Event types: fallback to all if empty/invalid
		$gw['types'] = isset( $_POST['global_webhook_types'] ) && is_array( $_POST['global_webhook_types'] )
			? array_values( array_intersect( array_map( 'sanitize_key', wp_unslash( $_POST['global_webhook_types'] ) ), array( 'new_strings', 'completion_update', 'needs_attention', 'milestone' ) ) )
			: (array) ( $gw['types'] ?? array() );
		if ( empty( $gw['types'] ) ) {
			$gw['types'] = array( 'new_strings', 'completion_update', 'needs_attention', 'milestone' );
		}
		$gw['threshold'] = absint( wp_unslash( $_POST['global_webhook_threshold'] ?? ( $gw['threshold'] ?? $defaults_new_strings ) ) );
		$mil_raw         = (string) wp_unslash( $_POST['global_webhook_milestones'] ?? '' );
		if ( $mil_raw !== '' ) {
			$gw['milestones'] = array_values( array_filter( array_map( 'intval', array_map( 'trim', explode( ',', $mil_raw ) ) ) ) );
			sort( $gw['milestones'] );
		} else {
			$gw['milestones'] = (array) ( $gw['milestones'] ?? $defaults_milestones ); }
		// Modes split: immediate_enabled + digest.enabled (back-compat with legacy 'mode')
		$incoming_mode                    = sanitize_key( wp_unslash( $_POST['global_webhook_mode'] ?? '' ) );
		$gw['immediate_enabled']          = ! empty( $_POST['global_webhook_immediate_enabled'] ) || ( $incoming_mode === 'immediate' && empty( $_POST['global_webhook_digest_enabled'] ) );
		$gw['digest']                     = $gw['digest'] ?? array();
		$gw['digest']['enabled']          = ! empty( $_POST['global_webhook_digest_enabled'] ) || ( $incoming_mode === 'digest' );
		$gw['digest']['type']             = in_array( ( $dt = sanitize_key( wp_unslash( $_POST['global_webhook_digest_type'] ?? ( $gw['digest']['type'] ?? $defaults_digest_type ) ) ) ), array( 'interval', 'fixed_time' ), true ) ? $dt : 'interval';
		$gw['digest']['interval_minutes'] = max( 15, absint( wp_unslash( $_POST['global_webhook_digest_interval_minutes'] ?? ( $gw['digest']['interval_minutes'] ?? $defaults_digest_interval ) ) ) );
		$gw['digest']['fixed_time']       = preg_replace( '/[^0-9:]/', '', (string) wp_unslash( $_POST['global_webhook_digest_fixed_time'] ?? ( $gw['digest']['fixed_time'] ?? $defaults_digest_fixed ) ) );
		if ( ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $gw['digest']['fixed_time'] ) ) {
			$gw['digest']['fixed_time'] = '09:00'; }
		// quotas & limits
		$gw['rate_limit_per_sec'] = max( 1, min( 5, absint( wp_unslash( $_POST['global_webhook_rate_limit_per_sec'] ?? ( $gw['rate_limit_per_sec'] ?? 1 ) ) ) ) );
		$gw['max_log_chars']      = max( 500, min( 3000, absint( wp_unslash( $_POST['global_webhook_max_log_chars'] ?? ( $gw['max_log_chars'] ?? 3000 ) ) ) ) );
		// scopes
		$gw['scopes'] = $gw['scopes'] ?? array(
			'locales'  => array(),
			'projects' => array(),
		);
		// Locales: if not provided or empty, treat as [] to allow clearing
		if ( isset( $_POST['global_webhook_scopes_locales'] ) ) {
			$gw['scopes']['locales'] = is_array( $_POST['global_webhook_scopes_locales'] )
				? array_values( array_map( 'sanitize_key', wp_unslash( $_POST['global_webhook_scopes_locales'] ) ) )
				: array();
		} else {
			$gw['scopes']['locales'] = array();
		}
		// Projects textarea: always provided by the form; empty string means clear
		if ( isset( $_POST['global_webhook_scopes_projects'] ) ) {
			$proj_raw                 = (string) wp_unslash( $_POST['global_webhook_scopes_projects'] );
			$gw['scopes']['projects'] = array();
			foreach ( array_filter( array_map( 'trim', preg_split( '/\r?\n/', $proj_raw ) ) ) as $line ) {
				$parts = array_map( 'trim', explode( ',', $line ) );
				if ( count( $parts ) >= 2 ) {
					$gw['scopes']['projects'][] = array(
						'type' => sanitize_key( $parts[0] ),
						'slug' => sanitize_key( $parts[1] ),
					); }
			}
		}
		// Enforce Slack allowlist: only hooks.slack.com accepted
		$allowSlack                    = static function ( $url ) {
			if ( ! is_string( $url ) || $url === '' ) {
				return ''; }
			$host = wp_parse_url( $url, PHP_URL_HOST );
			return ( is_string( $host ) && preg_match( '/(^|\.)hooks\.slack\.com$/i', $host ) ) ? $url : '';
		};
		$settings['slack_webhook_url'] = $allowSlack( $settings['slack_webhook_url'] ?? '' );
		$gw['webhook_url']             = $allowSlack( $gw['webhook_url'] ?? '' );
		$settings['global_webhook']    = $gw;
		// Global surveillance enable/disable
		$settings['surveillance_enabled'] = ! empty( wp_unslash( $_POST['surveillance_enabled'] ?? ( $existing_settings['surveillance_enabled'] ?? false ) ) );

		$old_interval = (int) ( $existing_settings['surveillance_interval'] ?? 15 );
		Settings::update( $settings );

		// Pierre updates his webhook URL! 🪨 Prefer the new Global Webhook URL if present
		$gw_url = trim( (string) ( $settings['global_webhook']['webhook_url'] ?? '' ) );
		if ( $gw_url !== '' ) {
			$this->slack_notifier->set_webhook_url( $gw_url );
		} elseif ( ! empty( $settings['slack_webhook_url'] ) ) {
			$this->slack_notifier->set_webhook_url( $settings['slack_webhook_url'] );
		}

		// If surveillance interval changed, reschedule cron
		$new_interval = (int) ( $settings['surveillance_interval'] ?? 15 );
		if ( $new_interval !== $old_interval ) {
			try {
				$this->container->get( \Pierre\Surveillance\CronManager::class )->reschedule_surveillance();
			} catch ( \Exception $e ) {
				do_action( 'wp_pierre_debug', 'failed to reschedule surveillance: ' . $e->getMessage(), array( 'source' => 'AdminController' ) );
			}
		}

		do_action(
			'wp_pierre_debug',
			'settings saved',
			array(
				'scope'  => 'admin',
				'action' => 'save_settings',
			)
		);
		wp_send_json_success(
			array(
				'message' => __( 'Settings saved successfully!', 'wp-pierre' ) . ' 🪨',
			)
		);
	}

	/**
	 * Save per-locale overrides for notifications
	 */
	public function ajax_save_locale_overrides(): void {
		$this->require_manage_permission();
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}
		$locale = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		if ( $locale === '' ) {
			wp_send_json_error( array( 'message' => __( 'Locale code is required.', 'wp-pierre' ) . ' 😢' ) );
			return;
		}
		$settings = Settings::all();
		if ( ! is_array( $settings ) ) {
			$settings = array(); }
		if ( ! isset( $settings['locales'] ) || ! is_array( $settings['locales'] ) ) {
			$settings['locales'] = array(); }

		$new_strings     = wp_unslash( $_POST['new_strings_threshold'] ?? '' );
		$milestones_raw  = (string) wp_unslash( $_POST['milestones'] ?? '' );
		$mode            = sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) );
		$digest_type     = sanitize_key( wp_unslash( $_POST['digest_type'] ?? '' ) );
		$digest_interval = wp_unslash( $_POST['digest_interval_minutes'] ?? '' );
		$digest_fixed    = (string) wp_unslash( $_POST['digest_fixed_time'] ?? '' );

		$over = $settings['locales'][ $locale ] ?? array();
		if ( $new_strings !== '' ) {
			$over['new_strings_threshold'] = absint( $new_strings ); }
		if ( $milestones_raw !== '' ) {
			$ms = array();
			foreach ( explode( ',', $milestones_raw ) as $p ) {
				$p = trim( $p );
				if ( $p === '' ) {
					continue;
				} $ms[] = (int) $p; }
			sort( $ms );
			$over['milestones'] = $ms;
		}
		if ( in_array( $mode, array( 'immediate', 'digest' ), true ) ) {
			$over['mode'] = $mode; }
		if ( ! isset( $over['digest'] ) || ! is_array( $over['digest'] ) ) {
			$over['digest'] = array(); }
		if ( in_array( $digest_type, array( 'interval', 'fixed_time' ), true ) ) {
			$over['digest']['type'] = $digest_type; }
		if ( $digest_interval !== '' ) {
			$over['digest']['interval_minutes'] = max( 15, absint( $digest_interval ) ); }
		if ( $digest_fixed !== '' ) {
			$over['digest']['fixed_time'] = preg_replace( '/[^0-9:]/', '', $digest_fixed ); }
		$over['override'] = true;

		$settings['locales'][ $locale ] = $over;
		update_option( 'pierre_settings', $settings );
		wp_send_json_success( array( 'message' => __( 'Locale overrides saved.', 'wp-pierre' ) . ' 🪨' ) );
	}

	/**
	 * Pierre handles AJAX start surveillance! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_start_surveillance(): void {
		$this->require_manage_permission();
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
				wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			}
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		// Optional per-entity cooldown (locale/project) with fallback to global (2 minutes)
		$locale  = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		$project = sanitize_key( wp_unslash( $_POST['project_slug'] ?? '' ) );
		if ( $locale && $project ) {
			$key  = 'pierre_last_forced_scan_' . $locale . '_' . $project;
			$last = (int) get_option( $key, 0 );
			if ( $last && ( time() - $last ) < 120 ) {
				wp_send_json_error( array( 'message' => __( 'Please wait before forcing another scan for this project/locale (cooldown 2 min).', 'wp-pierre' ) . ' 😢' ) );
			}
			update_option( $key, time() );
		} elseif ( $locale ) {
			$key  = 'pierre_last_forced_scan_' . $locale;
			$last = (int) get_option( $key, 0 );
			if ( $last && ( time() - $last ) < 120 ) {
				wp_send_json_error( array( 'message' => __( 'Please wait before forcing another scan for this locale (cooldown 2 min).', 'wp-pierre' ) . ' 😢' ) );
			}
			update_option( $key, time() );
		} else {
			$last = (int) get_option( 'pierre_last_forced_scan_global', 0 );
			if ( $last && ( time() - $last ) < 120 ) {
				wp_send_json_error( array( 'message' => __( 'Please wait before forcing another scan (cooldown 2 min).', 'wp-pierre' ) . ' 😢' ) );
			}
			update_option( 'pierre_last_forced_scan_global', time() );
		}

		$result = $this->project_watcher->start_surveillance();
		wp_send_json_success(
			array(
				'message' => 'Pierre started surveillance! 🪨',
				'result'  => $result,
			)
		);
	}

	/**
	 * Pierre handles AJAX stop surveillance! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_stop_surveillance(): void {
		$this->require_manage_permission();
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
				wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			}
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		$result = $this->project_watcher->stop_surveillance();
		wp_send_json_success(
			array(
				'message' => 'Pierre stopped surveillance! 🪨',
				'result'  => $result,
			)
		);
	}

	/**
	 * Pierre handles AJAX test surveillance! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_test_surveillance(): void {
		// Pierre checks nonce! 🪨 (accept admin nonce as well)
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' ) );
			}
		}

		// Pierre checks permissions! 🪨 use dedicated capability
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' ) );
		}

		$result = $this->project_watcher->test_surveillance();
		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		}
		wp_send_json_error( $result );
	}

	/**
	 * Pierre handles AJAX add project! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_add_project(): void {
		$this->require_manage_permission();
		// Pierre checks nonce! 🪨 (accept admin nonce as well)
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
				wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			}
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		$project_slug = sanitize_key( wp_unslash( $_POST['project_slug'] ?? '' ) );
		$locale_code  = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );
		$project_type = sanitize_key( wp_unslash( $_POST['project_type'] ?? 'meta' ) );

		// Validate project_type
		$valid_types = array( 'meta', 'plugin', 'theme', 'app' );
		if ( ! in_array( $project_type, $valid_types, true ) ) {
			$project_type = 'meta';
		}

		if ( empty( $project_slug ) || empty( $locale_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: Project slug and locale code are required!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}

		// Check workflow "locale d'abord" - validate locale exists in WP.org (cached)
		$translations = $this->get_cached_translations();
		$locale_valid = false;
		foreach ( $translations as $t ) {
			if ( ( $t['language'] ?? '' ) === $locale_code ) {
				$locale_valid = true;
				break;
			}
		}

		if ( ! $locale_valid ) {
			// translators: %s is the locale code
			wp_send_json_error( array( 'message' => sprintf( __( 'Locale %s is not valid or not found in WordPress.org translations.', 'wp-pierre' ), $locale_code ) . ' 😢' ) );
			return;
		}

		// Check if already watching
		$watched     = $this->project_watcher->get_watched_projects();
		$project_key = "{$project_slug}_{$locale_code}";
		foreach ( $watched as $wp ) {
			$w_slug   = $wp['slug'] ?? ( $wp['project_slug'] ?? '' );
			$w_locale = $wp['locale'] ?? ( $wp['locale_code'] ?? '' );
			if ( $w_slug === $project_slug && $w_locale === $locale_code ) {
				wp_send_json_error( array( 'message' => sprintf( __( 'Project %1$s is already being watched for locale %2$s.', 'wp-pierre' ), $project_slug, $locale_code ) ) );
				return;
			}
		}

		$result = $this->project_watcher->watch_project( $project_slug, $locale_code, $project_type );

		// Store project type if watch_project succeeded
		if ( $result ) {
			$watched = $this->project_watcher->get_watched_projects();
			if ( isset( $watched[ $project_key ] ) ) {
				// Update project with type if not already set
				$watched_projects_option = get_option( 'pierre_watched_projects', array() );
				if ( isset( $watched_projects_option[ $project_key ] ) ) {
					$watched_projects_option[ $project_key ]['type'] = $project_type;
					update_option( 'pierre_watched_projects', $watched_projects_option );
				}
			}
			// mark as known for projects catalog prioritization
			try {
				( new \Pierre\Discovery\ProjectsCatalog() )->mark_known( $project_type, $project_slug ); } catch ( \Throwable $e ) {
				}
				// Maintain consistency: add locale to selected locales if not already there
				$selected_locales = get_option( 'pierre_selected_locales', array() );
				if ( ! is_array( $selected_locales ) ) {
					$selected_locales = array();
				}
				if ( ! in_array( $locale_code, $selected_locales, true ) ) {
					$selected_locales[] = $locale_code;
					update_option( 'pierre_selected_locales', $selected_locales );
				}
				// translators: 1: project slug, 2: project type, 3: locale code
				wp_send_json_success( array( 'message' => sprintf( __( 'Project %1$s (%2$s) added to surveillance for locale %3$s!', 'wp-pierre' ), $project_slug, $project_type, $locale_code ) . ' 🪨' ) );
		} else {
			// Detailed error message
			wp_send_json_error( array( 'message' => sprintf( __( 'Failed to add project %1$s. The project may not exist, be inaccessible, or scraping failed. Please verify the project slug and try again.', 'wp-pierre' ), $project_slug ) ) );
		}
	}

	/**
	 * Pierre handles AJAX remove project! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_remove_project(): void {
		$this->require_manage_permission();
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'wp-pierre' ) ) );
				return;
			}
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_projects' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied. You do not have permission to remove projects.', 'wp-pierre' ) ) );
			return;
		}

		$project_slug = sanitize_key( wp_unslash( $_POST['project_slug'] ?? '' ) );
		$locale_code  = sanitize_key( wp_unslash( $_POST['locale_code'] ?? '' ) );

		if ( empty( $project_slug ) || empty( $locale_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Project slug and locale code are required.', 'wp-pierre' ) ) );
			return;
		}

		// Check if project is actually being watched
		$watched     = $this->project_watcher->get_watched_projects();
		$project_key = "{$project_slug}_{$locale_code}";
		$found       = false;
		foreach ( $watched as $wp ) {
			$w_slug   = $wp['slug'] ?? ( $wp['project_slug'] ?? '' );
			$w_locale = $wp['locale'] ?? ( $wp['locale_code'] ?? '' );
			if ( $w_slug === $project_slug && $w_locale === $locale_code ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Project %1$s is not currently being watched for locale %2$s.', 'wp-pierre' ), $project_slug, $locale_code ) ) );
			return;
		}

		$result = $this->project_watcher->unwatch_project( $project_slug, $locale_code );

		if ( $result ) {
			wp_send_json_success( array( 'message' => sprintf( __( 'Project %1$s removed from surveillance for locale %2$s.', 'wp-pierre' ), $project_slug, $locale_code ) ) );
		} else {
			wp_send_json_error( array( 'message' => sprintf( __( 'Failed to remove project %1$s. Please try again or contact support if the problem persists.', 'wp-pierre' ), $project_slug ) ) );
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
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
				wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			}
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre flushes his cache! 🪨
		$this->project_watcher->flush_cache();

		wp_send_json_success( array( 'message' => 'Pierre flushed his cache! 🪨' ) );
	}

	/**
	 * Pierre handles AJAX reset settings! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_reset_settings(): void {
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
				wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
			}
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre resets his settings! 🪨
		delete_option( 'pierre_settings' );

		wp_send_json_success( array( 'message' => 'Pierre reset his settings! 🪨' ) );
	}

	/**
	 * Pierre handles AJAX clear data! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_clear_data(): void {
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			wp_die( esc_html__( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			wp_die( __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre clears his data! 🪨
		$this->project_watcher->clear_all_data();
		$this->user_project_link->clear_all_data();

		wp_send_json_success( array( 'message' => 'Pierre cleared all his data! 🪨' ) );
	}

	/**
	 * Pierre handles AJAX export report! 🪨
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_export_report(): void {
		// Pierre checks nonce! 🪨
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			wp_die( esc_html__( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_reports' ) ) {
			wp_die( esc_html__( 'Pierre says: You\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		$report_type = sanitize_key( wp_unslash( $_POST['report_type'] ?? '' ) );

		if ( empty( $report_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: Report type is required!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}

		// Pierre generates his report! 🪨
		$report_data = $this->generate_report( $report_type );

		if ( $report_data ) {
			wp_send_json_success(
				array(
					// translators: %s is the report type (e.g., "projects", "teams")
					'message' => sprintf( __( 'Pierre exported %s report successfully!', 'wp-pierre' ), $report_type ) . ' 🪨',
					'data'    => $report_data,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Pierre says: Failed to generate report!', 'wp-pierre' ) . ' 😢' ) );
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
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			wp_die( esc_html__( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_reports' ) ) {
			wp_die( esc_html__( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre generates all his reports! 🪨
		$report_types = array( 'projects', 'teams', 'surveillance', 'notifications' );
		$all_reports  = array();

		foreach ( $report_types as $type ) {
			$report_data = $this->generate_report( $type );
			if ( $report_data ) {
				$all_reports[ $type ] = $report_data;
			}
		}

		if ( ! empty( $all_reports ) ) {
			wp_send_json_success(
				array(
					'message' => esc_html__( 'Pierre exported all reports successfully!', 'wp-pierre' ) . ' 🪨',
					'data'    => $all_reports,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Pierre says: Failed to generate reports!', 'wp-pierre' ) . ' 😢' ) );
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
		if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
			wp_die( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
		}

		// Pierre checks permissions! 🪨
		if ( ! current_user_can( 'pierre_manage_reports' ) ) {
			wp_die( esc_html__( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_freq           = isset( $_POST['schedule_frequency'] ) ? wp_unslash( $_POST['schedule_frequency'] ) : 'weekly';
		$schedule_frequency = sanitize_key( (string) $raw_freq );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_types    = isset( $_POST['report_types'] ) ? wp_unslash( $_POST['report_types'] ) : array();
		$report_types = is_array( $raw_types ) ? array_map( 'sanitize_key', $raw_types ) : array();

		// Pierre schedules his reports! 🪨
		$result = $this->schedule_reports( $schedule_frequency, $report_types );

		if ( $result ) {
			wp_send_json_success(
				array(
					// translators: %s is the schedule frequency (e.g., "daily", "weekly")
					'message' => sprintf( esc_html__( 'Pierre scheduled reports for %s!', 'wp-pierre' ), esc_html( $schedule_frequency ) ) . ' 🪨',
					'data'    => $result,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Pierre says: Failed to schedule reports!', 'wp-pierre' ) . ' 😢' ) );
		}
	}

	/**
	 * Pierre generates a report! 🪨
	 *
	 * @since 1.0.0
	 * @param string $report_type Type of report to generate.
	 * @return array|false Report data or false on failure
	 */
	private function generate_report( string $report_type ): array|false {
		try {
			switch ( $report_type ) {
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
		} catch ( \Exception $e ) {
			do_action( 'wp_pierre_debug', 'error generating report: ' . $e->getMessage(), array( 'source' => 'AdminController' ) );
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
		$projects    = $this->project_watcher->get_all_projects();
		$report_data = array(
			'generated_at'   => current_time( 'mysql' ),
			'total_projects' => count( $projects ),
			'projects'       => array(),
		);

		foreach ( $projects as $project ) {
			$report_data['projects'][] = array(
				'project_slug'          => $project['project_slug'],
				'locale_code'           => $project['locale_code'],
				'completion_percentage' => $project['completion_percentage'] ?? 0,
				'last_updated'          => $project['last_updated'] ?? null,
				'status'                => $project['status'] ?? 'unknown',
			);
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
		$report_data = array(
			'generated_at'      => current_time( 'mysql' ),
			'total_assignments' => count( $assignments ),
			'assignments'       => array(),
		);

		foreach ( $assignments as $assignment ) {
			$report_data['assignments'][] = array(
				'user_id'      => $assignment['user_id'],
				'project_slug' => $assignment['project_slug'],
				'locale_code'  => $assignment['locale_code'],
				'role'         => $assignment['role'],
				'assigned_by'  => $assignment['assigned_by'],
				'assigned_at'  => $assignment['assigned_at'],
			);
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
		$report_data         = array(
			'generated_at'        => current_time( 'mysql' ),
			'surveillance_active' => $surveillance_status['active'] ?? false,
			'last_check'          => $surveillance_status['last_check'] ?? null,
			'next_check'          => $surveillance_status['next_check'] ?? null,
			'total_checks'        => $surveillance_status['total_checks'] ?? 0,
			'successful_checks'   => $surveillance_status['successful_checks'] ?? 0,
			'failed_checks'       => $surveillance_status['failed_checks'] ?? 0,
		);

		return $report_data;
	}

	/**
	 * Pierre generates notifications report! 🪨
	 *
	 * @since 1.0.0
	 * @return array Notifications report data
	 */
	private function generate_notifications_report(): array {
		$settings    = Settings::all();
		$report_data = array(
			'generated_at'             => current_time( 'mysql' ),
			'slack_configured'         => ! empty( $settings['slack_webhook_url'] ),
			'notification_types'       => $settings['notification_types'] ?? array(),
			'notification_threshold'   => $settings['notification_threshold'] ?? 5,
			'last_notification'        => $settings['last_notification'] ?? null,
			'total_notifications_sent' => $settings['total_notifications_sent'] ?? 0,
		);

		return $report_data;
	}

	/**
	 * Pierre schedules reports! 🪨
	 *
	 * @since 1.0.0
	 * @param string $frequency Schedule frequency.
	 * @param array  $report_types Types of reports to schedule.
	 * @return array|false Schedule result or false on failure
	 */
	private function schedule_reports( string $frequency, array $report_types ): array|false {
		try {
			$schedule_data = array(
				'frequency'    => $frequency,
				'report_types' => $report_types,
				'scheduled_at' => current_time( 'mysql' ),
				'next_run'     => $this->calculate_next_run( $frequency ),
			);

			// Pierre saves his schedule! 🪨
			update_option( 'pierre_report_schedule', $schedule_data );

			return $schedule_data;
		} catch ( \Exception $e ) {
			do_action( 'wp_pierre_debug', 'error scheduling reports: ' . $e->getMessage(), array( 'source' => 'AdminController' ) );
			return false;
		}
	}

	/**
	 * Pierre calculates next run time! 🪨
	 *
	 * @since 1.0.0
	 * @param string $frequency Schedule frequency.
	 * @return string Next run time
	 */
	private function calculate_next_run( string $frequency ): string {
		$intervals = array(
			'daily'   => DAY_IN_SECONDS,
			'weekly'  => WEEK_IN_SECONDS,
			'monthly' => MONTH_IN_SECONDS,
		);

		$interval = $intervals[ $frequency ] ?? WEEK_IN_SECONDS;
		$next_run = time() + $interval;

		return gmdate( 'Y-m-d H:i:s', $next_run );
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
			if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
				wp_send_json_error( esc_html__( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
				return;
			}

			// Pierre checks permissions! 🪨
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( esc_html__( 'Pierre says: Insufficient permissions!', 'wp-pierre' ) . ' 😢' );
				return;
			}

			// Pierre performs comprehensive security audit! 🪨
			$audit_results = $this->security_auditor->perform_comprehensive_audit();

			wp_send_json_success(
				array(
					'message'       => __( 'Pierre completed security audit!', 'wp-pierre' ) . ' 🪨',
					'audit_results' => $audit_results,
				)
			);
		} catch ( \Exception $e ) {
			do_action( 'wp_pierre_debug', 'security audit error: ' . $e->getMessage(), array( 'source' => 'AdminController' ) );
			wp_send_json_error( __( 'Pierre says: Security audit failed!', 'wp-pierre' ) . ' 😢' );
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
			if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
				wp_send_json_error( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
				return;
			}

			// Pierre checks permissions! 🪨
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Pierre says: Insufficient permissions!', 'wp-pierre' ) . ' 😢' );
				return;
			}

			// Pierre gets security logs! 🪨
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_limit  = isset( $_POST['limit'] ) ? wp_unslash( $_POST['limit'] ) : 100;
			$limit      = max( 1, absint( $raw_limit ) );
			$event_type = sanitize_key( wp_unslash( $_POST['event_type'] ?? '' ) );

			$security_logs = $this->csrf_protection->get_security_logs( $limit, $event_type );

			wp_send_json_success(
				array(
					'message'       => esc_html__( 'Pierre retrieved security logs!', 'wp-pierre' ) . ' 🪨',
					'security_logs' => $security_logs,
				)
			);
		} catch ( \Exception $e ) {
			do_action( 'wp_pierre_debug', 'retrieve security logs error: ' . $e->getMessage(), array( 'source' => 'AdminController' ) );
			wp_send_json_error( esc_html__( 'Pierre says: Failed to retrieve security logs!', 'wp-pierre' ) . ' 😢' );
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
			if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
				wp_send_json_error( __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' );
				return;
			}

			// Pierre checks permissions! 🪨
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Pierre says: Insufficient permissions!', 'wp-pierre' ) . ' 😢' );
				return;
			}

			// Pierre clears security logs! 🪨
			$event_type = sanitize_key( wp_unslash( $_POST['event_type'] ?? '' ) );
			$success    = $this->csrf_protection->clear_security_logs( $event_type );

			if ( $success ) {
				wp_send_json_success(
					array(
						'message' => __( 'Pierre cleared security logs!', 'wp-pierre' ) . ' 🪨',
					)
				);
			} else {
				wp_send_json_error( __( 'Pierre says: Failed to clear security logs!', 'wp-pierre' ) . ' 😢' );
			}
		} catch ( \Exception $e ) {
			do_action( 'wp_pierre_debug', 'clear security logs error: ' . $e->getMessage(), array( 'source' => 'AdminController' ) );
			wp_send_json_error( __( 'Pierre says: Failed to clear security logs!', 'wp-pierre' ) . ' 😢' );
		}
	}

	/**
	 * Render the Projects Catalog Browser card markup (lazy-loaded).
	 */
	public function ajax_render_catalog_browser(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}
		if ( ! current_user_can( 'pierre_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' ) );
			return;
		}
		// Reuse existing settings data for selects
		$data                                  = $this->get_admin_settings_data();
		$GLOBALS['pierre_admin_template_data'] = $data;
		ob_start();
		$template_path = PIERRE_PLUGIN_DIR . 'templates/admin/settings-catalog-browser.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Missing template.', 'wp-pierre' ) . '</p></div>';
		}
		$html = ob_get_clean();
		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Search/paginate users for Locale Managers selection
	 */
	public function ajax_search_users_for_locale(): void {
		if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: Invalid nonce!', 'wp-pierre' ) . ' 😢' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Pierre says: You don\'t have permission!', 'wp-pierre' ) . ' 😢' ) );
		}
		$q        = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
		$page     = max( 1, absint( wp_unslash( $_POST['page'] ?? 1 ) ) );
		$per_page = min( 100, max( 10, absint( wp_unslash( $_POST['per_page'] ?? 20 ) ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		$args  = array(
			'number'         => $per_page,
			'offset'         => $offset,
			'orderby'        => 'display_name',
			'order'          => 'ASC',
			'search'         => $q !== '' ? '*' . $q . '*' : '',
			'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
			'fields'         => array( 'ID', 'user_login', 'display_name' ),
		);
		$users = get_users( $args );
		$total = (int) count_users()['total_users'] ?? 0;

		$items = array();
		foreach ( $users as $u ) {
			$items[] = array(
				'id'    => (int) $u->ID,
				'login' => (string) $u->user_login,
				'name'  => (string) $u->display_name,
			);
		}
		wp_send_json_success(
			array(
				'items'    => $items,
				'page'     => $page,
				'per_page' => $per_page,
				'total'    => $total,
			)
		);
	}

	/**
	 * Pierre gets his admin controller status! 🪨
	 *
	 * @since 1.0.0
	 * @return array Admin controller status
	 */
	public function get_status(): array {
		return array(
			'menu_setup'          => true,
			'ajax_handlers_setup' => true,
			'admin_hooks_setup'   => true,
			'message'             => 'Pierre\'s admin controller is ready! 🪨',
		);
	}
}