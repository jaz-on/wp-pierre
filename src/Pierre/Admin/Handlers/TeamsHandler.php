<?php
/**
 * Pierre's teams handler - he manages teams! 🪨
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Admin\Handlers;

use Pierre\Teams\RoleManager;

/**
 * TeamsHandler class - Pierre's teams handler! 🪨
 *
 * @since 1.0.0
 */
class TeamsHandler {
	/**
	 * Render teams page.
	 *
	 * @since 1.0.0
	 * @param callable $render_template Template renderer callback.
	 * @param array    $additional_data Additional data to merge (users, locales, etc.).
	 * @return void
	 */
	public function render_teams_page( callable $render_template, array $additional_data = array() ): void {
		$role_manager = new RoleManager();
		
		// Get roles with full documentation
		$roles = $role_manager->get_roles();
		
		// Get capabilities with full documentation
		$capabilities = $role_manager->get_capabilities( true ); // Include meta capabilities
		
		// Format roles for template (simple key => description format for backward compatibility)
		$roles_formatted = array();
		foreach ( $roles as $role_key => $role_data ) {
			if ( is_array( $role_data ) && isset( $role_data['display_name'] ) ) {
				$roles_formatted[ $role_data['display_name'] ] = $role_data['description'] ?? '';
			} else {
				$roles_formatted[ $role_key ] = is_string( $role_data ) ? $role_data : '';
			}
		}
		
		// Format capabilities for template (include descriptions)
		$capabilities_formatted = array();
		foreach ( $capabilities as $cap_key => $cap_data ) {
			if ( is_array( $cap_data ) && isset( $cap_data['name'] ) ) {
				$cap_name = $cap_data['name'];
				$cap_description = $cap_data['description'] ?? '';
				$cap_meta = $cap_data['meta_cap'] ?? false;
				$capabilities_formatted[ $cap_name ] = array(
					'description' => $cap_description,
					'meta_cap' => $cap_meta,
				);
			} else {
				$capabilities_formatted[ $cap_key ] = array(
					'description' => '',
					'meta_cap' => false,
				);
			}
		}
		
		// Merge handler data with additional data (users, locales, projects, etc.)
		// Note: additional_data (from get_admin_teams_data) contains roles in the correct format for the template
		// We merge handler data first, then additional_data so it overrides roles with the correct format
		$template_data = array_merge(
			array(
				'roles' => $roles_formatted,
				'roles_full' => $roles, // Full data for advanced display
				'capabilities' => $capabilities_formatted,
				'capabilities_full' => $capabilities, // Full data for advanced display
			),
			$additional_data // Overrides roles with correct format from get_admin_teams_data()
		);
		
		$render_template( 'teams', $template_data );
	}
}
