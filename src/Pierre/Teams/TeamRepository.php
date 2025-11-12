<?php
/**
 * Pierre's team repository - DB access for assignments 🪨
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Teams;

class TeamRepository {

    /** @var string */
    private string $table;

	/** @var string */
	private string $cache_group = 'pierre';

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'pierre_user_projects';
    }

    /**
     * Assign a user to a project/locale with a specific role.
     *
     * @since 1.0.0
     * @param int    $user_id      WordPress user ID.
     * @param string $project_type Project type (plugin, theme, meta, app).
     * @param string $project_slug Project slug identifier.
     * @param string $locale_code  Locale code (e.g., 'fr_FR').
     * @param string $role         User role (locale_manager, gte, pte, contributor, validator).
     * @param int    $assigned_by  User ID who created this assignment.
     * @return bool True on success, false on failure.
     */
    public function assign_user_to_project(
        int $user_id,
        string $project_type,
        string $project_slug,
        string $locale_code,
        string $role,
        int $assigned_by
    ): bool {
        global $wpdb;

        $data = [
            'user_id'      => $user_id,
            'project_type' => $project_type,
            'project_slug' => $project_slug,
            'locale_code'  => $locale_code,
            'role'         => $role,
            'assigned_by'  => $assigned_by,
            'assigned_at'  => current_time('mysql'),
            'is_active'    => 1,
        ];

        $formats = [ '%d','%s','%s','%s','%s','%d','%s','%d' ];
        $ok = (bool) $wpdb->insert( $this->table, $data, $formats );
        if ($ok) {
            // Invalidate per-user cache and per-project cache
            wp_cache_delete('user_assignments_' . $user_id, $this->cache_group);
            $project_key = 'project_assignments_' . $project_slug . '_' . $locale_code;
            wp_cache_delete($project_key, $this->cache_group);
            // Hint: could also invalidate a global list if introduced later
        }
        return $ok;
    }

    /**
     * Check if an active assignment exists for a user/project/locale combination.
     *
     * @since 1.0.0
     * @param int    $user_id     WordPress user ID.
     * @param string $project_slug Project slug identifier.
     * @param string $locale_code Locale code (e.g., 'fr_FR').
     * @return bool True if active assignment exists, false otherwise.
     */
    public function assignment_exists( int $user_id, string $project_slug, string $locale_code ): bool {
        global $wpdb;
        $sql = "SELECT id FROM {$this->table}
                WHERE user_id = %d AND project_slug = %s AND locale_code = %s AND is_active = 1
                LIMIT 1";
        $id = $wpdb->get_var( $wpdb->prepare( $sql, $user_id, $project_slug, $locale_code ) );
        return ! empty( $id );
    }

    /**
     * Remove a user assignment by soft-deactivating it (sets is_active to 0).
     *
     * @since 1.0.0
     * @param int    $user_id     WordPress user ID.
     * @param string $project_slug Project slug identifier.
     * @param string $locale_code Locale code (e.g., 'fr_FR').
     * @return bool True on success, false on failure.
     */
    public function remove_user_from_project( int $user_id, string $project_slug, string $locale_code ): bool {
        global $wpdb;
        $ok = (bool) $wpdb->update(
            $this->table,
            [ 'is_active' => 0 ],
            [
                'user_id'     => $user_id,
                'project_slug'=> $project_slug,
                'locale_code' => $locale_code,
            ],
            [ '%d' ],
            [ '%d','%s','%s' ]
        );
        if ($ok) {
            // Invalidate per-user cache and per-project cache
            wp_cache_delete('user_assignments_' . $user_id, $this->cache_group);
            $project_key = 'project_assignments_' . $project_slug . '_' . $locale_code;
            wp_cache_delete($project_key, $this->cache_group);
        }
        return $ok;
    }

    /**
     * Get all active assignments for a specific user.
     *
     * @since 1.0.0
     * @param int $user_id WordPress user ID.
     * @return array Array of assignment rows with keys: id, user_id, project_type, project_slug, locale_code, role, assigned_by, assigned_at, is_active.
     */
    public function get_user_assignments( int $user_id ): array {
        // Try runtime/persistent cache first
        $cache_key = 'user_assignments_' . $user_id;
        $cached = wp_cache_get($cache_key, $this->cache_group);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $sql = "SELECT * FROM {$this->table} WHERE user_id = %d AND is_active = 1 ORDER BY assigned_at DESC";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );
        $rows = is_array( $rows ) ? $rows : [];
        // Store in cache (per-request or persistent if object cache present)
        wp_cache_set($cache_key, $rows, $this->cache_group);
        return $rows;
    }

    /**
     * Get all active assignments for a specific project/locale combination.
     *
     * @since 1.0.0
     * @param string $project_slug Project slug identifier.
     * @param string $locale_code  Locale code (e.g., 'fr_FR').
     * @return array Array of assignment rows with keys: id, user_id, project_type, project_slug, locale_code, role, assigned_by, assigned_at, is_active.
     */
    public function get_project_assignments( string $project_slug, string $locale_code ): array {
        // Try cache first
        $cache_key = 'project_assignments_' . $project_slug . '_' . $locale_code;
        $cached = wp_cache_get($cache_key, $this->cache_group);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $sql = "SELECT * FROM {$this->table} WHERE project_slug = %s AND locale_code = %s AND is_active = 1 ORDER BY assigned_at DESC";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $project_slug, $locale_code ), ARRAY_A );
        $rows = is_array( $rows ) ? $rows : [];
        wp_cache_set($cache_key, $rows, $this->cache_group);
        return $rows;
    }

    /**
     * Get all active assignments across all users, projects, and locales.
     *
     * @since 1.0.0
     * @return array Array of assignment rows with keys: id, user_id, project_type, project_slug, locale_code, role, assigned_by, assigned_at, is_active.
     */
    public function get_all_assignments(): array {
        // Try cache first
        $cache_key = 'all_assignments_active';
        $cached = wp_cache_get($cache_key, $this->cache_group);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $table = esc_sql($this->table);
        $sql = "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY assigned_at DESC";
        $rows = $wpdb->get_results( $sql, ARRAY_A );
        $rows = is_array( $rows ) ? $rows : [];
        wp_cache_set($cache_key, $rows, $this->cache_group);
        return $rows;
    }

    /**
     * Hard delete all assignments from the database (admin operation).
     *
     * @since 1.0.0
     * @return bool True on success, false on failure.
     */
    public function clear_all(): bool {
        global $wpdb;
        $table = esc_sql($this->table);
        $ok = (bool) $wpdb->query( "DELETE FROM {$table}" );
        if ($ok) {
            // Best-effort: flush group if supported, else do nothing
            if (function_exists('wp_cache_flush_group')) {
                wp_cache_flush_group($this->cache_group);
            }
            // Invalidate known keys
            wp_cache_delete('all_assignments_active', $this->cache_group);
        }
        return $ok;
    }
}