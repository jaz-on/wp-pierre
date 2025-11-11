<?php
/**
 * Pierre's user project link - he manages assignments! 🪨
 * 
 * This class handles the business logic for linking users to projects
 * and managing their roles in Pierre's translation monitoring system.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Teams;

use Pierre\Traits\StatusTrait;

use Pierre\Surveillance\ProjectWatcher;

/**
 * User Project Link class - Pierre's assignment logic! 🪨
 * 
 * @since 1.0.0
 */
class UserProjectLink {
    use StatusTrait;
    
    /**
     * Pierre's team repository - he stores data! 🪨
     * 
     * @var TeamRepository
     */
    private TeamRepository $team_repository;
    
    /**
     * Pierre's role manager - he manages permissions! 🪨
     * 
     * @var RoleManager
     */
    private RoleManager $role_manager;
    
    /**
     * Pierre's project watcher - he monitors projects! 🪨
     * 
     * @var ProjectWatcher
     */
    private ProjectWatcher $project_watcher;

	/**
	 * In-memory cache for user assignments during a single request
	 * @var array<int,array>
	 */
	private array $user_assignments_cache = [];
    
    /**
     * Pierre's constructor - he prepares his assignment system! 🪨
     * 
     * @since 1.0.0
     */
    public function __construct() {
        $this->team_repository = new TeamRepository();
        $this->role_manager = new RoleManager();
        $this->project_watcher = pierre()->get_project_watcher();
    }
    
    /**
     * Pierre assigns a user to a project with full validation! 🪨
     * 
     * @since 1.0.0
     * @param int $user_id The user ID to assign
     * @param string $project_type The project type (plugin, theme, meta, app)
     * @param string $project_slug The project slug
     * @param string $locale_code The locale code
     * @param string $role The user's role in the project
     * @param int $assigned_by The user ID who is making the assignment
     * @return array Assignment result with success status and message
     */
    public function assign_user_to_project(
        int $user_id,
        string $project_type,
        string $project_slug,
        string $locale_code,
        string $role,
        int $assigned_by
    ): array {
        try {
            // Pierre validates permissions! 🪨
            // Locale Manager can assign, GTE cannot
            if (!$this->role_manager->user_can_assign_projects($assigned_by, $locale_code)) {
                return [
                    'success' => false,
                    'message' => 'Pierre says: You don\'t have permission to assign projects! Only Locale Managers and site administrators can assign users. 😢'
                ];
            }
            
            // Pierre validates the assignment! 🪨
            $validation_result = $this->validate_assignment($user_id, $project_type, $project_slug, $locale_code, $role, $assigned_by);
            if (!$validation_result['valid']) {
                return [
                    'success' => false,
                    'message' => $validation_result['message']
                ];
            }
            
            // Pierre creates the assignment! 🪨
            $assignment_success = $this->team_repository->assign_user_to_project(
                $user_id,
                $project_type,
                $project_slug,
                $locale_code,
                $role,
                $assigned_by
            );
            
            if (!$assignment_success) {
                return [
                    'success' => false,
                    'message' => 'Pierre failed to create the assignment! 😢'
                ];
            }
            
            // Pierre adds the project to his surveillance! 🪨
            $this->project_watcher->watch_project($project_slug, $locale_code);

			// Invalidate cache for this user
			unset($this->user_assignments_cache[$user_id]);
            
            // Pierre gets user and project info for the response! 🪨
            $user = get_user_by('id', $user_id);
            $project_name = $this->get_project_display_name($project_slug, $locale_code);
            
            return [
                'success' => true,
                'message' => "Pierre assigned {$user->display_name} to {$project_name} as {$role}! 🪨",
                'assignment' => [
                    'user_id' => $user_id,
                    'user_name' => $user->display_name,
                    'project_slug' => $project_slug,
                    'project_name' => $project_name,
                    'locale_code' => $locale_code,
                    'role' => $role,
                    'assigned_by' => $assigned_by
                ]
            ];
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error assigning user to project: ' . $e->getMessage() . ' 😢');
            return [
                'success' => false,
                'message' => 'Pierre encountered an unexpected error! 😢'
            ];
        }
    }
    
    /**
     * Pierre removes a user from a project! 🪨
     * 
     * @since 1.0.0
     * @param int $user_id The user ID to remove
     * @param string $project_slug The project slug
     * @param string $locale_code The locale code
     * @param int $removed_by The user ID who is removing the assignment
     * @return array Removal result with success status and message
     */
    public function remove_user_from_project(int $user_id, string $project_slug, string $locale_code, int $removed_by): array {
        try {
            // Pierre validates permissions! 🪨
            if (!$this->role_manager->user_has_capability($removed_by, 'pierre_assign_projects')) {
                return [
                    'success' => false,
                    'message' => 'Pierre says: You don\'t have permission to remove project assignments! 😢'
                ];
            }
            
            // Pierre checks if assignment exists! 🪨
            if (!$this->team_repository->assignment_exists($user_id, $project_slug, $locale_code)) {
                return [
                    'success' => false,
                    'message' => 'Pierre says: Assignment does not exist! 😢'
                ];
            }
            
            // Pierre removes the assignment! 🪨
            $removal_success = $this->team_repository->remove_user_from_project($user_id, $project_slug, $locale_code);
            
            if (!$removal_success) {
                return [
                    'success' => false,
                    'message' => 'Pierre failed to remove the assignment! 😢'
                ];
            }
            
            // Pierre gets user and project info for the response! 🪨
            $user = get_user_by('id', $user_id);
            $project_name = $this->get_project_display_name($project_slug, $locale_code);

			// Invalidate cache for this user
			unset($this->user_assignments_cache[$user_id]);
            
            return [
                'success' => true,
                'message' => "Pierre removed {$user->display_name} from {$project_name}! 🪨",
                'removal' => [
                    'user_id' => $user_id,
                    'user_name' => $user->display_name,
                    'project_slug' => $project_slug,
                    'project_name' => $project_name,
                    'locale_code' => $locale_code,
                    'removed_by' => $removed_by
                ]
            ];
            
        } catch (\Exception $e) {
            error_log('Pierre encountered an error removing user from project: ' . $e->getMessage() . ' 😢');
            return [
                'success' => false,
                'message' => 'Pierre encountered an unexpected error! 😢'
            ];
        }
    }
    
    /**
     * Pierre validates an assignment before creating it! 🪨
     * 
     * @since 1.0.0
     * @param int $user_id The user ID
     * @param string $project_type The project type
     * @param string $project_slug The project slug
     * @param string $locale_code The locale code
     * @param string $role The user's role
     * @param int $assigned_by The user ID who is making the assignment
     * @return array Validation result with valid status and message
     */
    private function validate_assignment(int $user_id, string $project_type, string $project_slug, string $locale_code, string $role, int $assigned_by): array {
        // Pierre validates user IDs! 🪨
        if ($user_id <= 0 || $assigned_by <= 0) {
            return [
                'valid' => false,
                'message' => 'Pierre says: Invalid user IDs! 😢'
            ];
        }
        
        // Pierre validates that users exist! 🪨
        if (!get_user_by('id', $user_id)) {
            return [
                'valid' => false,
                'message' => "Pierre says: User {$user_id} does not exist! 😢"
            ];
        }
        
        if (!get_user_by('id', $assigned_by)) {
            return [
                'valid' => false,
                'message' => "Pierre says: User {$assigned_by} does not exist! 😢"
            ];
        }
        
        // Pierre validates project type! 🪨
        $valid_types = ['plugin', 'theme', 'meta', 'app'];
        if (!in_array($project_type, $valid_types, true)) {
            return [
                'valid' => false,
                'message' => "Pierre says: Invalid project type {$project_type}! 😢"
            ];
        }
        
        // Pierre validates role! 🪨
        $valid_roles = ['locale_manager', 'gte', 'pte', 'contributor', 'validator'];
        if (!in_array($role, $valid_roles, true)) {
            return [
                'valid' => false,
                'message' => "Pierre says: Invalid role {$role}! 😢"
            ];
        }
        
        // Pierre checks if assignment already exists! 🪨
        if ($this->team_repository->assignment_exists($user_id, $project_slug, $locale_code)) {
            return [
                'valid' => false,
                'message' => 'Pierre says: Assignment already exists! 😢'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Pierre says: Assignment is valid! 🪨'
        ];
    }
    
    /**
     * Pierre gets a project's display name! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug
     * @param string $locale_code The locale code
     * @return string The project display name
     */
    private function get_project_display_name(string $project_slug, string $locale_code): string {
        // Pierre creates a display name! 🪨
        return ucfirst($project_slug) . " ({$locale_code})";
    }
    
    /**
     * Get status message.
     *
     * @since 1.0.0
     * @return string Status message
     */
    protected function get_status_message(): string {
        return 'Pierre\'s assignment system is ready! 🪨';
    }

    /**
     * Get status details.
     *
     * @since 1.0.0
     * @return array Status details
     */
    protected function get_status_details(): array {
        return [];
    }

    /**
     * Get all assignments for a user with details (stub)
     *
     * @param int $user_id
     * @return array
     */
    public function get_user_assignments_with_details(int $user_id): array {
        // Return from in-memory cache if available for this request
        if (isset($this->user_assignments_cache[$user_id])) {
            return $this->user_assignments_cache[$user_id];
        }

        $rows = $this->team_repository->get_user_assignments($user_id);
        if (!is_array($rows) || empty($rows)) { return []; }

        $watched = \Pierre\Helpers\OptionHelper::get_option_array('pierre_watched_projects', []);
        $details = [];
        foreach ($rows as $r) {
            $key = ($r['project_slug'] ?? '') . '_' . ($r['locale_code'] ?? '');
            $watched_item = is_array($watched) && isset($watched[$key]) ? $watched[$key] : null;
            $details[] = [
                'user_id'      => (int) ($r['user_id'] ?? 0),
                'project_type' => (string) ($r['project_type'] ?? ''),
                'project_slug' => (string) ($r['project_slug'] ?? ''),
                'locale_code'  => (string) ($r['locale_code'] ?? ''),
                'role'         => (string) ($r['role'] ?? ''),
                'assigned_at'  => (string) ($r['assigned_at'] ?? ''),
                'is_active'    => (int) ($r['is_active'] ?? 0),
                'watched'      => $watched_item,
            ];
        }
        // Store in in-memory cache for this request
        $this->user_assignments_cache[$user_id] = $details;
        return $details;
    }

    /**
     * Get all assignments for a project/locale with details (stub)
     *
     * @param string $project_slug
     * @param string $locale_code
     * @return array
     */
    public function get_project_assignments_with_details(string $project_slug, string $locale_code): array {
        $rows = $this->team_repository->get_project_assignments($project_slug, $locale_code);
        if (!is_array($rows) || empty($rows)) { return []; }

        $details = [];
        foreach ($rows as $r) {
            $user = get_user_by('id', (int) ($r['user_id'] ?? 0));
            $details[] = [
                'user_id'      => (int) ($r['user_id'] ?? 0),
                'user_name'    => $user ? $user->display_name : '',
                'project_type' => (string) ($r['project_type'] ?? ''),
                'project_slug' => (string) ($r['project_slug'] ?? ''),
                'locale_code'  => (string) ($r['locale_code'] ?? ''),
                'role'         => (string) ($r['role'] ?? ''),
                'assigned_at'  => (string) ($r['assigned_at'] ?? ''),
                'is_active'    => (int) ($r['is_active'] ?? 0),
            ];
        }
        return $details;
    }

    /**
     * Get all assignments (stub)
     *
     * @return array
     */
    public function get_all_assignments(): array {
        return $this->team_repository->get_all_assignments();
    }

    /**
     * Clear all assignments data (stub)
     *
     * @return void
     */
    public function clear_all_data(): void {
        $this->team_repository->clear_all();
    }
}
