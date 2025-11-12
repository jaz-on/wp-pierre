<?php
/**
 * Pierre's role manager - he manages permissions! 🪨
 * 
 * This class handles WordPress capabilities and user roles
 * for Pierre's translation monitoring system.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Teams;

use Pierre\Traits\StatusTrait;
use Pierre\Helpers\OptionHelper;

/**
 * Role Manager class - Pierre's permission system! 🪨
 * 
 * @since 1.0.0
 */
class RoleManager {
    use StatusTrait;
    
    /**
     * Pierre's capabilities - he defines what users can do! 🪨
     * 
     * @var array<string, array{
     *     name: string,
     *     description: string,
     *     roles: array<string>,
     *     meta_cap: bool
     * }>
     */
    private array $caps = [
        'pierre_view_dashboard' => [
            'name' => 'pierre_view_dashboard',
            'description' => 'View the Pierre dashboard and translation statistics.',
            'roles' => ['administrator', 'editor'],
            'meta_cap' => false,
        ],
        'pierre_manage_settings' => [
            'name' => 'pierre_manage_settings',
            'description' => 'Manage Pierre plugin settings, webhooks, and surveillance intervals.',
            'roles' => ['administrator'],
            'meta_cap' => false,
        ],
        'pierre_manage_projects' => [
            'name' => 'pierre_manage_projects',
            'description' => 'Add, remove, and manage watched translation projects.',
            'roles' => ['administrator'],
            'meta_cap' => false,
        ],
        'pierre_manage_teams' => [
            'name' => 'pierre_manage_teams',
            'description' => 'Manage team assignments and user roles for translation projects.',
            'roles' => ['administrator'],
            'meta_cap' => false,
        ],
        'pierre_manage_reports' => [
            'name' => 'pierre_manage_reports',
            'description' => 'Generate and manage translation reports.',
            'roles' => ['administrator', 'editor'],
            'meta_cap' => false,
        ],
        'pierre_manage_notifications' => [
            'name' => 'pierre_manage_notifications',
            'description' => 'Configure and manage Slack notifications.',
            'roles' => ['administrator'],
            'meta_cap' => false,
        ],
        'pierre_assign_projects' => [
            'name' => 'pierre_assign_projects',
            'description' => 'Assign users to translation projects (Locale Managers only).',
            'roles' => ['administrator'],
            'meta_cap' => false,
        ],
    ];

    /**
     * Meta capabilities - dynamically checked based on user assignments.
     * 
     * @var array<string, array{
     *     name: string,
     *     description: string,
     *     required_assignments: array<string>
     * }>
     */
    private array $meta_caps = [
        'pierre_manage_locale' => [
            'name' => 'pierre_manage_locale',
            'description' => 'Manage settings for a specific locale (Locale Manager or GTE).',
            'required_assignments' => ['locale_manager', 'gte'],
        ],
        'pierre_manage_project_locale' => [
            'name' => 'pierre_manage_project_locale',
            'description' => 'Manage a specific project for a locale (Locale Manager, GTE, or PTE).',
            'required_assignments' => ['locale_manager', 'gte', 'pte'],
        ],
        'pierre_assign_user_locale' => [
            'name' => 'pierre_assign_user_locale',
            'description' => 'Assign users to a locale (Locale Manager only).',
            'required_assignments' => ['locale_manager'],
        ],
        'pierre_view_reports_locale' => [
            'name' => 'pierre_view_reports_locale',
            'description' => 'View reports for a specific locale (Locale Manager, GTE, or PTE).',
            'required_assignments' => ['locale_manager', 'gte', 'pte'],
        ],
    ];

    /**
     * Pierre adds his capabilities! 🪨
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_capabilities(): void {
        // Grant all custom caps to Administrator role explicitly at activation/runtime safeguard
        $admin = get_role('administrator');
        if ($admin) {
            foreach ($this->caps as $cap_key => $cap_data) {
                $cap_name = is_array($cap_data) ? $cap_data['name'] : $cap_key;
                if (!$admin->has_cap($cap_name)) {
                    $admin->add_cap($cap_name);
                }
            }
        }

        add_filter('user_has_cap', function($allcaps, $caps, $args, $user){
            // IMPORTANT: ne jamais appeler user_can() ici (risque de récursion)
            // Si l'utilisateur a déjà la capacité 'administrator', lui accorder les caps Pierre
            if (!empty($allcaps['administrator'])) {
                foreach ($this->caps as $cap_key => $cap_data) {
                    $cap_name = is_array($cap_data) ? $cap_data['name'] : $cap_key;
                    $allcaps[$cap_name] = true;
                }
            }
            return $allcaps;
        }, 10, 4);

        if (defined('PIERRE_DEBUG') && PIERRE_DEBUG) {
            error_log('Pierre added his capabilities! 🪨');
        }

        // Map meta capabilities to dynamic decisions based on Teams assignments (LM/GTE/PTE)
        add_filter('map_meta_cap', function(array $required, string $cap, int $user_id, array $args) {
            // IMPORTANT: do not call user_can()/current_user_can() here (recursion)
            // Short-circuit for site administrators by inspecting roles/caps directly
            $user = get_userdata($user_id);
            if ($user && (in_array('administrator', (array) $user->roles, true) || !empty($user->allcaps['manage_options']))) {
                return ['exist'];
            }

            // Small helpers to read context
            $get = static function (string $key, $default = '') use ($args) {
                return $args[$key] ?? $default;
            };

            $locale  = OptionHelper::sanitize_locale_code($get('locale', ''));
            $type    = sanitize_key($get('project')['type'] ?? '');
            $slug    = sanitize_key($get('project')['slug'] ?? '');
            $projKey = ($type && $slug) ? ($type . ':' . $slug) : '';

            // Load assignments from options using helper method
            $LM  = $this->get_team_mapping('lm', $locale);
            $GTE = $this->get_team_mapping('gte', $locale);
            $PTE = $this->get_team_mapping('pte', $locale, $projKey);

            $in = static function (int $uid, array $list): bool {
                return in_array($uid, $list, true);
            };

            switch ($cap) {
                case 'pierre_manage_locale':
                    // Locale Manager or GTE of the locale can manage (and WP admins already allowed above)
                    return ($locale && ($in($user_id, $LM) || $in($user_id, $GTE))) ? ['exist'] : ['do_not_allow'];

                case 'pierre_manage_project_locale':
                    // LM or GTE of locale, or PTE of that specific project
                    return ($locale && ($in($user_id, $LM) || $in($user_id, $GTE) || ($projKey && $in($user_id, $PTE))))
                        ? ['exist'] : ['do_not_allow'];

                case 'pierre_assign_user_locale':
                    // Only Locale Managers can assign users for a locale
                    return ($locale && $in($user_id, $LM)) ? ['exist'] : ['do_not_allow'];

                case 'pierre_view_reports_locale':
                    // Any of LM/GTE/PTE for the context can view locale-level reports
                    return ($locale && ($in($user_id, $LM) || $in($user_id, $GTE) || $in($user_id, $PTE)))
                        ? ['exist'] : ['do_not_allow'];
            }

            return $required;
        }, 10, 4);
    }

    /**
     * Get current status of roles/capabilities.
     *
     * @since 1.0.0
     * @return array{
     *     roles_registered: bool,
     *     capabilities_registered: bool,
     *     total_capabilities: int,
     *     total_meta_capabilities: int,
     *     administrator_has_all: bool,
     *     message: string
     * }
     */
    /**
     * Get status message.
     *
     * @since 1.0.0
     * @return string Status message
     */
    protected function get_status_message(): string {
        return 'Role manager ready';
    }

    /**
     * Get status details.
     *
     * @since 1.0.0
     * @return array Status details
     */
    protected function get_status_details(): array {
        $admin = get_role('administrator');
        $admin_has_all = true;
        
        if ($admin) {
            foreach ($this->caps as $cap_key => $cap_data) {
                $cap_name = is_array($cap_data) ? $cap_data['name'] : $cap_key;
                if (!$admin->has_cap($cap_name)) {
                    $admin_has_all = false;
                    break;
                }
            }
        }
        
        return [
            'roles_registered' => true,
            'capabilities_registered' => true,
            'total_capabilities' => count($this->caps),
            'total_meta_capabilities' => count($this->meta_caps),
            'administrator_has_all' => $admin_has_all,
        ];
    }

    /**
     * Get Pierre roles list with descriptions.
     *
     * @since 1.0.0
     * @return array<string, array{
     *     name: string,
     *     display_name: string,
     *     description: string,
     *     capabilities: array<string>
     * }> Array of roles with their capabilities.
     */
    public function get_roles(): array {
        $roles = [
            'administrator' => [
                'name' => 'administrator',
                'display_name' => __('Administrator', 'wp-pierre'),
                'description' => __('Website administrator with full access to all Pierre features.', 'wp-pierre'),
                'capabilities' => array_keys($this->caps),
            ],
        ];
        
        // Add information about translation team roles
        $roles['locale_manager'] = [
            'name' => 'locale_manager',
            'display_name' => __('Locale Manager', 'wp-pierre'),
            'description' => __('Manages a specific locale and can assign users to projects.', 'wp-pierre'),
            'capabilities' => ['pierre_manage_locale', 'pierre_assign_user_locale', 'pierre_view_reports_locale'],
        ];
        
        $roles['gte'] = [
            'name' => 'gte',
            'display_name' => __('General Translation Editor', 'wp-pierre'),
            'description' => __('Can manage projects for a locale but cannot assign users.', 'wp-pierre'),
            'capabilities' => ['pierre_manage_project_locale', 'pierre_view_reports_locale'],
        ];
        
        $roles['pte'] = [
            'name' => 'pte',
            'display_name' => __('Project Translation Editor', 'wp-pierre'),
            'description' => __('Can manage a specific project for a locale.', 'wp-pierre'),
            'capabilities' => ['pierre_manage_project_locale', 'pierre_view_reports_locale'],
        ];
        
        return $roles;
    }

    /**
     * Get Pierre capabilities list with full documentation.
     *
     * @since 1.0.0
     * @param bool $include_meta Whether to include meta capabilities.
     * @return array<string, array{
     *     name: string,
     *     description: string,
     *     roles: array<string>,
     *     meta_cap: bool,
     *     required_assignments?: array<string>
     * }> Array of capabilities with documentation.
     */
    public function get_capabilities(bool $include_meta = false): array {
        $caps = $this->caps;
        
        if ($include_meta) {
            // Add meta capabilities with meta_cap flag
            foreach ($this->meta_caps as $meta_cap_key => $meta_cap_data) {
                $caps[$meta_cap_key] = array_merge($meta_cap_data, [
                    'roles' => [],
                    'meta_cap' => true,
                ]);
            }
        }
        
        return $caps;
    }

    /**
     * Get detailed information about a specific capability.
     *
     * @since 1.0.0
     * @param string $capability The capability name.
     * @return array<string, mixed>|null Capability information or null if not found.
     */
    public function get_capability_info(string $capability): ?array {
        // Check regular capabilities
        if (isset($this->caps[$capability])) {
            return $this->caps[$capability];
        }
        
        // Check meta capabilities
        if (isset($this->meta_caps[$capability])) {
            return array_merge($this->meta_caps[$capability], [
                'roles' => [],
                'meta_cap' => true,
            ]);
        }
        
        return null;
    }

    /**
     * Get all meta capabilities with their documentation.
     *
     * @since 1.0.0
     * @return array<string, array{
     *     name: string,
     *     description: string,
     *     required_assignments: array<string>
     * }> Array of meta capabilities.
     */
    public function get_meta_capabilities(): array {
        return $this->meta_caps;
    }

    /**
     * Get team mapping for a specific type and locale.
     *
     * Helper method to retrieve and validate team mappings (LM/GTE/PTE) consistently.
     *
     * @param string $type Team type: 'lm', 'gte', or 'pte'
     * @param string $locale Locale code
     * @param string $project_key Optional project key (required for PTE, format: 'type:slug')
     * @return array Array of user IDs for the specified team type and locale
     */
    private function get_team_mapping(string $type, string $locale, string $project_key = ''): array {
        $option_name = match ($type) {
            'lm' => 'pierre_locale_managers',
            'gte' => 'pierre_gte',
            'pte' => 'pierre_pte',
            default => '',
        };

        if (empty($option_name)) {
            return [];
        }

        $map = get_option($option_name, []);
        if (!is_array($map)) {
            return [];
        }

        if ($type === 'pte') {
            // PTE mapping is nested: [locale][project_key] => [user_ids]
            if (empty($project_key) || empty($locale)) {
                return [];
            }
            $locale_map = $map[$locale] ?? [];
            if (!is_array($locale_map)) {
                return [];
            }
            $user_ids = $locale_map[$project_key] ?? [];
            return is_array($user_ids) ? $user_ids : [];
        }

        // LM and GTE mapping: [locale] => [user_ids]
        if (empty($locale)) {
            return [];
        }
        $user_ids = $map[$locale] ?? [];
        return is_array($user_ids) ? $user_ids : [];
    }

    /**
     * Check if user has a specific capability
     *
     * @param int $user_id User ID
     * @param string $capability Capability to check
     * @return bool
     */
    public function user_has_capability(int $user_id, string $capability): bool {
        // Administrators have all capabilities
        if (user_can($user_id, 'administrator')) {
            return true;
        }
        
        // Check if user has the capability
        return user_can($user_id, $capability);
    }
    
    /**
     * Check if user can assign projects based on their role
     * Locale Manager can assign, GTE cannot
     *
     * @param int $user_id User ID
     * @param string $locale_code Optional locale code for locale-specific checks
     * @return bool
     */
    public function user_can_assign_projects(int $user_id, string $locale_code = ''): bool {
        // Administrators can always assign
        if (user_can($user_id, 'administrator')) {
            return true;
        }
        
        // Check if user has pierre_assign_projects capability
        if (!user_can($user_id, 'pierre_assign_projects')) {
            return false;
        }
        
        // Must have a locale to check mapping
        if ($locale_code === '') {
            return false;
        }
        
        // Check locale managers mapping from options using helper method
        $managers = $this->get_team_mapping('lm', $locale_code);
        return in_array($user_id, $managers, true);
    }

    /**
     * Check if user can manage locale notification settings (Locale Manager, GTE, or Admin).
     */
    public function user_can_manage_locale_settings(int $user_id, string $locale_code): bool {
        if (user_can($user_id, 'administrator')) { return true; }
        if ($locale_code === '') { return false; }
        // Locale Managers list using helper method
        $managers = $this->get_team_mapping('lm', $locale_code);
        if (in_array($user_id, $managers, true)) { return true; }
        // GTE mapping: check if user is GTE for this locale using helper method
        $gte_list = $this->get_team_mapping('gte', $locale_code);
        if (in_array($user_id, $gte_list, true)) { return true; }
        return false;
    }
}