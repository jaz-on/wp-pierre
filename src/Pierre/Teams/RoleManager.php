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
        add_filter('user_has_cap', function($allcaps, $caps, $args, $user){
            // Administrateurs WordPress ont tous les droits Pierre
            if ($user && user_can($user->ID, 'administrator')) {
                foreach ($this->caps as $cap) {
                    $allcaps[$cap] = true;
                }
            }
            return $allcaps;
        }, 10, 4);

        error_log('Pierre added his capabilities! 🪨');
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
}