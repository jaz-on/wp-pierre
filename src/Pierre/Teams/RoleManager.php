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

/**
 * Role Manager class - Pierre's permission system! 🪨
 * 
 * @since 1.0.0
 */
class RoleManager {
    
    /** @var array */
    private array $caps = [
        'pierre_view_dashboard',
        'pierre_manage_settings',
        'pierre_manage_projects',
        'pierre_manage_teams',
        'pierre_manage_reports',
        'pierre_manage_notifications',
        'pierre_assign_projects',
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
            foreach ($this->caps as $cap) {
                if (!$admin->has_cap($cap)) {
                    $admin->add_cap($cap);
                }
            }
        }

        add_filter('user_has_cap', function($allcaps, $caps, $args, $user){
            // IMPORTANT: ne jamais appeler user_can() ici (risque de récursion)
            // Si l'utilisateur a déjà la capacité 'administrator', lui accorder les caps Pierre
            if (!empty($allcaps['administrator'])) {
                foreach ($this->caps as $cap) {
                    $allcaps[$cap] = true;
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

            $locale  = sanitize_key($get('locale', ''));
            $type    = sanitize_key($get('project')['type'] ?? '');
            $slug    = sanitize_key($get('project')['slug'] ?? '');
            $projKey = ($type && $slug) ? ($type . ':' . $slug) : '';

            // Load assignments from options
            $lm_map  = (array) get_option('pierre_locale_managers', []);
            $gte_map = (array) get_option('pierre_gte', []);
            $pte_map = (array) get_option('pierre_pte', []);

            $LM  = (array) ($lm_map[$locale] ?? []);
            $GTE = (array) ($gte_map[$locale] ?? []);
            $PTE = (array) (($pte_map[$locale] ?? [])[$projKey] ?? []);

            $in = static function (int $uid, array $list): bool {
                return in_array($uid, $list, true);
            };

            switch ($cap) {
                case 'pierre_manage_locale':
                    // Locale Manager of the locale can manage (and WP admins already allowed above)
                    return ($locale && $in($user_id, $LM)) ? ['exist'] : ['do_not_allow'];

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
     * Get current status of roles/capabilities (stub for now)
     *
     * @return array
     */
    public function get_status(): array {
        return [
            'roles_registered' => true,
            'capabilities_registered' => true,
            'message' => 'Role manager ready'
        ];
    }

    /**
     * Get Pierre roles list (stub)
     *
     * @return array
     */
    public function get_roles(): array {
        return [
            'administrator' => 'Website admin (auto)'
        ];
    }

    /**
     * Get Pierre capabilities list (stub)
     *
     * @return array
     */
    public function get_capabilities(): array {
        return $this->caps;
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
        
        // Check locale managers mapping from options
        $map = get_option('pierre_locale_managers', []);
        if (!is_array($map)) {
            return false;
        }
        $managers = $map[$locale_code] ?? [];
        if (!is_array($managers)) {
            return false;
        }
        
        return in_array($user_id, $managers, true);
    }

    /**
     * Check if user can manage locale notification settings (Locale Manager or Admin).
     * GTE allowed per requirement.
     */
    public function user_can_manage_locale_settings(int $user_id, string $locale_code): bool {
        if (user_can($user_id, 'administrator')) { return true; }
        if ($locale_code === '') { return false; }
        // Locale Managers list
        $map = get_option('pierre_locale_managers', []);
        $is_manager = is_array($map) && in_array($user_id, (array)($map[$locale_code] ?? []), true);
        if ($is_manager) { return true; }
        // GTE: future mapping; for now, reuse pierre_manage_notifications if granted
        if (user_can($user_id, 'pierre_manage_notifications')) { return true; }
        return false;
    }
}