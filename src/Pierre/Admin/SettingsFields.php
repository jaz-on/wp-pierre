<?php
/**
 * Pierre's settings fields renderer - he renders form fields! 🪨
 *
 * This class provides callback methods for rendering settings fields
 * using the WordPress Settings API.
 *
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Admin;

use Pierre\Settings\Settings;

use function get_settings_errors;

/**
 * SettingsFields class - Pierre's form field renderer! 🪨
 *
 * @since 1.0.0
 */
class SettingsFields {
	/**
	 * Get current settings value for a field.
	 *
	 * @since 1.0.0
	 * @param string $key Setting key (supports dot notation).
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	private static function get_value( string $key, $default = null ) {
		return Settings::get( $key, $default );
	}

	/**
	 * Get field-specific error messages.
	 *
	 * Maps error codes from Settings API to field IDs using the following rules:
	 * 1. Exact match: error code === field ID
	 * 2. Suffix match: error code === field_id + '_' + suffix (invalid, missing, empty, duplicates, required)
	 * 3. Normalized match: converts dots to underscores for matching (e.g., "notification_defaults.milestones" → "notification_defaults_milestones")
	 *
	 * Examples:
	 * - Field ID "surveillance_interval" matches error code "surveillance_interval_invalid"
	 * - Field ID "notification_defaults.milestones" matches error code "notification_defaults_milestones_duplicates"
	 * - Field ID "max_projects_per_check" matches error code "max_projects_per_check_invalid"
	 *
	 * Note: Field keys use dot notation (e.g., "notification_defaults.milestones") for nested settings,
	 * but field IDs use underscores (e.g., "notification_defaults_milestones") for HTML ID attributes.
	 * This method handles both formats automatically.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID to check for errors (may contain dots or underscores).
	 * @return array Array of error messages for this field.
	 */
	private static function get_field_errors( string $field_id ): array {
		$errors = get_settings_errors( 'pierre_settings_group' );
		$field_errors = array();

		// Normalize field ID: convert dots to underscores for matching
		// (e.g., "notification_defaults.milestones" → "notification_defaults_milestones")
		$normalized_field_id = str_replace( '.', '_', $field_id );

		// Suffixes d'erreur courants
		$error_suffixes = array( 'invalid', 'missing', 'empty', 'duplicates', 'required' );

		foreach ( $errors as $error ) {
			if ( ! isset( $error['code'] ) ) {
				continue;
			}

			$error_code = $error['code'];

			// Correspondance exacte
			if ( $error_code === $field_id || $error_code === $normalized_field_id ) {
				$field_errors[] = $error['message'];
				continue;
			}

			// Correspondance avec suffixe (_invalid, _missing, etc.)
			// Exemple: "surveillance_interval_invalid" → "surveillance_interval"
			foreach ( $error_suffixes as $suffix ) {
				$pattern = '/^' . preg_quote( $field_id, '/' ) . '_' . preg_quote( $suffix, '/' ) . '$/';
				if ( preg_match( $pattern, $error_code ) ) {
					$field_errors[] = $error['message'];
					continue 2; // Continue to next error
				}

				// Essayer aussi avec la version normalisée
				$pattern_normalized = '/^' . preg_quote( $normalized_field_id, '/' ) . '_' . preg_quote( $suffix, '/' ) . '$/';
				if ( preg_match( $pattern_normalized, $error_code ) ) {
					$field_errors[] = $error['message'];
					continue 2; // Continue to next error
				}
			}
		}

		return $field_errors;
	}

	/**
	 * Render a checkbox field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_checkbox( array $args ): void {
		$key     = $args['key'] ?? '';
		$default = $args['default'] ?? false;
		$value   = self::get_value( $key, $default );
		$id      = $args['id'] ?? $key;
		$name    = $args['name'] ?? $key;
		$label   = $args['label'] ?? '';
		$help    = $args['help'] ?? '';
		$show_status = $args['show_status'] ?? false;
		$wrapper_class = $args['wrapper_class'] ?? '';

		$checked = ! empty( $value );
		$field_errors = self::get_field_errors( $id );
		$has_errors = ! empty( $field_errors );
		?>
		<div class="pierre-form-group <?php echo esc_attr( $wrapper_class ); ?><?php echo $has_errors ? ' pierre-form-group--error' : ''; ?>">
			<label for="<?php echo esc_attr( $id ); ?>">
				<input type="checkbox" 
					   id="<?php echo esc_attr( $id ); ?>" 
					   name="<?php echo esc_attr( $name ); ?>" 
					   value="1"
					   class="<?php echo $has_errors ? 'pierre-field-error' : ''; ?>"
					   <?php checked( $checked ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php if ( $show_status ) : ?>
				<span class="<?php echo $checked ? 'pierre-status-ok' : 'pierre-status-ko'; ?>">
					<?php echo $checked ? esc_html__( 'Enabled', 'wp-pierre' ) : esc_html__( 'Disabled', 'wp-pierre' ); ?>
				</span>
			<?php endif; ?>
			<?php if ( $has_errors ) : ?>
				<div class="pierre-field-error-message" role="alert">
					<?php foreach ( $field_errors as $error_msg ) : ?>
						<p class="pierre-error-text"><?php echo esc_html( $error_msg ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $help ) ) : ?>
				<div class="pierre-help">
					<?php echo esc_html( $help ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a select field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_select( array $args ): void {
		$key      = $args['key'] ?? '';
		$default  = $args['default'] ?? '';
		$value    = self::get_value( $key, $default );
		$id       = $args['id'] ?? $key;
		$name     = $args['name'] ?? $key;
		$label    = $args['label'] ?? '';
		$help     = $args['help'] ?? '';
		$options  = $args['options'] ?? array();
		$class    = $args['class'] ?? 'wp-core-ui';

		$field_errors = self::get_field_errors( $id );
		$has_errors = ! empty( $field_errors );
		?>
		<div class="pierre-form-group<?php echo $has_errors ? ' pierre-form-group--error' : ''; ?>">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="<?php echo esc_attr( $class ); ?><?php echo $has_errors ? ' pierre-field-error' : ''; ?>">
				<?php foreach ( $options as $opt_value => $opt_label ) : ?>
					<option value="<?php echo esc_attr( $opt_value ); ?>" <?php selected( $value, $opt_value ); ?>>
						<?php echo esc_html( $opt_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( $has_errors ) : ?>
				<div class="pierre-field-error-message" role="alert">
					<?php foreach ( $field_errors as $error_msg ) : ?>
						<p class="pierre-error-text"><?php echo esc_html( $error_msg ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $help ) ) : ?>
				<div class="pierre-help">
					<?php echo esc_html( $help ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a number input field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_number( array $args ): void {
		$key     = $args['key'] ?? '';
		$default = $args['default'] ?? 0;
		$value   = self::get_value( $key, $default );
		$id      = $args['id'] ?? $key;
		$name    = $args['name'] ?? $key;
		$label   = $args['label'] ?? '';
		$help    = $args['help'] ?? '';
		$min     = $args['min'] ?? null;
		$max     = $args['max'] ?? null;
		$step    = $args['step'] ?? null;
		$class   = $args['class'] ?? 'regular-text';

		$field_errors = self::get_field_errors( $id );
		$has_errors = ! empty( $field_errors );
		?>
		<div class="pierre-form-group<?php echo $has_errors ? ' pierre-form-group--error' : ''; ?>">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="number" 
				   class="<?php echo esc_attr( $class ); ?><?php echo $has_errors ? ' pierre-field-error' : ''; ?>"
				   id="<?php echo esc_attr( $id ); ?>" 
				   name="<?php echo esc_attr( $name ); ?>" 
				   value="<?php echo esc_attr( $value ); ?>"
				   <?php if ( $min !== null ) : ?>min="<?php echo esc_attr( $min ); ?>"<?php endif; ?>
				   <?php if ( $max !== null ) : ?>max="<?php echo esc_attr( $max ); ?>"<?php endif; ?>
				   <?php if ( $step !== null ) : ?>step="<?php echo esc_attr( $step ); ?>"<?php endif; ?>>
			<?php if ( $has_errors ) : ?>
				<div class="pierre-field-error-message" role="alert">
					<?php foreach ( $field_errors as $error_msg ) : ?>
						<p class="pierre-error-text"><?php echo esc_html( $error_msg ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $help ) ) : ?>
				<div class="pierre-help">
					<?php echo esc_html( $help ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a text input field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_text( array $args ): void {
		$key     = $args['key'] ?? '';
		$default = $args['default'] ?? '';
		$value   = self::get_value( $key, $default );
		$id      = $args['id'] ?? $key;
		$name    = $args['name'] ?? $key;
		$label   = $args['label'] ?? '';
		$help    = $args['help'] ?? '';
		$class   = $args['class'] ?? 'regular-text';

		$field_errors = self::get_field_errors( $id );
		$has_errors = ! empty( $field_errors );
		?>
		<div class="pierre-form-group<?php echo $has_errors ? ' pierre-form-group--error' : ''; ?>">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="text" 
				   class="<?php echo esc_attr( $class ); ?><?php echo $has_errors ? ' pierre-field-error' : ''; ?>"
				   id="<?php echo esc_attr( $id ); ?>" 
				   name="<?php echo esc_attr( $name ); ?>" 
				   value="<?php echo esc_attr( $value ); ?>">
			<?php if ( $has_errors ) : ?>
				<div class="pierre-field-error-message" role="alert">
					<?php foreach ( $field_errors as $error_msg ) : ?>
						<p class="pierre-error-text"><?php echo esc_html( $error_msg ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $help ) ) : ?>
				<div class="pierre-help">
					<?php echo esc_html( $help ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render radio buttons.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_radio( array $args ): void {
		$key      = $args['key'] ?? '';
		$default  = $args['default'] ?? '';
		$value    = self::get_value( $key, $default );
		$id       = $args['id'] ?? $key;
		$name     = $args['name'] ?? $key;
		$label    = $args['label'] ?? '';
		$help     = $args['help'] ?? '';
		$options  = $args['options'] ?? array();

		$field_errors = self::get_field_errors( $id );
		$has_errors = ! empty( $field_errors );
		?>
		<div class="pierre-form-group<?php echo $has_errors ? ' pierre-form-group--error' : ''; ?>">
			<?php if ( ! empty( $label ) ) : ?>
				<h3><?php echo esc_html( $label ); ?></h3>
			<?php endif; ?>
			<fieldset class="pierre-form-group" role="radiogroup" aria-label="<?php echo esc_attr( $label ); ?>">
				<?php foreach ( $options as $opt_value => $opt_data ) : ?>
					<?php
					$opt_label = is_array( $opt_data ) ? ( $opt_data['label'] ?? '' ) : $opt_data;
					$opt_html  = is_array( $opt_data ) ? ( $opt_data['html'] ?? '' ) : '';
					?>
					<label class="pierre-ml-8">
						<input type="radio" 
							   name="<?php echo esc_attr( $name ); ?>" 
							   value="<?php echo esc_attr( $opt_value ); ?>"
							   class="<?php echo $has_errors ? 'pierre-field-error' : ''; ?>"
							   <?php checked( $value, $opt_value ); ?>>
						<?php if ( ! empty( $opt_html ) ) : ?>
							<?php echo $opt_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
						<span class="pierre-ml-8"><?php echo esc_html( $opt_label ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<?php if ( $has_errors ) : ?>
				<div class="pierre-field-error-message" role="alert">
					<?php foreach ( $field_errors as $error_msg ) : ?>
						<p class="pierre-error-text"><?php echo esc_html( $error_msg ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $help ) ) : ?>
				<div class="pierre-help">
					<?php echo esc_html( $help ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a time input field.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_time( array $args ): void {
		$key     = $args['key'] ?? '';
		$default = $args['default'] ?? '09:00';
		$value   = self::get_value( $key, $default );
		$id      = $args['id'] ?? $key;
		$name    = $args['name'] ?? $key;
		$label   = $args['label'] ?? '';
		$help    = $args['help'] ?? '';

		$field_errors = self::get_field_errors( $id );
		$has_errors = ! empty( $field_errors );
		?>
		<div class="pierre-form-group<?php echo $has_errors ? ' pierre-form-group--error' : ''; ?>">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="time" 
				   id="<?php echo esc_attr( $id ); ?>" 
				   name="<?php echo esc_attr( $name ); ?>" 
				   class="<?php echo $has_errors ? 'pierre-field-error' : ''; ?>"
				   value="<?php echo esc_attr( $value ); ?>">
			<?php if ( $has_errors ) : ?>
				<div class="pierre-field-error-message" role="alert">
					<?php foreach ( $field_errors as $error_msg ) : ?>
						<p class="pierre-error-text"><?php echo esc_html( $error_msg ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $help ) ) : ?>
				<div class="pierre-help">
					<?php echo esc_html( $help ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render milestones field (comma-separated text that becomes array).
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_milestones( array $args ): void {
		$key     = $args['key'] ?? '';
		$default = $args['default'] ?? array( 50, 80, 100 );
		$value   = self::get_value( $key, $default );
		$id      = $args['id'] ?? $key;
		$name    = $args['name'] ?? $key;
		$label   = $args['label'] ?? '';
		$help    = $args['help'] ?? '';

		// Convert array to comma-separated string for display.
		$display_value = is_array( $value ) ? implode( ',', $value ) : $value;

		$field_errors = self::get_field_errors( $id );
		$has_errors = ! empty( $field_errors );
		?>
		<div class="pierre-form-group<?php echo $has_errors ? ' pierre-form-group--error' : ''; ?>">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="text" 
				   id="<?php echo esc_attr( $id ); ?>" 
				   name="<?php echo esc_attr( $name ); ?>" 
				   class="<?php echo $has_errors ? 'pierre-field-error' : ''; ?>"
				   value="<?php echo esc_attr( $display_value ); ?>">
			<?php if ( $has_errors ) : ?>
				<div class="pierre-field-error-message" role="alert">
					<?php foreach ( $field_errors as $error_msg ) : ?>
						<p class="pierre-error-text"><?php echo esc_html( $error_msg ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $help ) ) : ?>
				<div class="pierre-help">
					<?php echo esc_html( $help ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render notification types checkboxes.
	 *
	 * @since 1.0.0
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_notification_types( array $args ): void {
		$key     = $args['key'] ?? 'notification_types';
		$default = $args['default'] ?? array( 'new_strings', 'completion_update' );
		$value   = self::get_value( $key, $default );
		$id      = $args['id'] ?? $key;
		$label   = $args['label'] ?? '';
		$help    = $args['help'] ?? '';

		$allowed_types = array(
			'new_strings'      => __( 'New Strings Detected', 'wp-pierre' ),
			'completion_update' => __( 'Completion Updates', 'wp-pierre' ),
			'needs_attention'  => __( 'Needs Attention', 'wp-pierre' ),
			'errors'            => __( 'Errors', 'wp-pierre' ),
		);

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$field_errors = self::get_field_errors( $id );
		$has_errors = ! empty( $field_errors );
		?>
		<div class="pierre-form-group<?php echo $has_errors ? ' pierre-form-group--error' : ''; ?>">
			<label><?php echo esc_html( $label ); ?></label>
			<div class="pierre-checkbox-group">
				<?php foreach ( $allowed_types as $type_value => $type_label ) : ?>
					<label>
						<input type="checkbox" 
							   name="notification_types[]" 
							   value="<?php echo esc_attr( $type_value ); ?>"
							   class="<?php echo $has_errors ? 'pierre-field-error' : ''; ?>"
							   <?php checked( in_array( $type_value, $value, true ) ); ?>>
						<?php echo esc_html( $type_label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<?php if ( $has_errors ) : ?>
				<div class="pierre-field-error-message" role="alert">
					<?php foreach ( $field_errors as $error_msg ) : ?>
						<p class="pierre-error-text"><?php echo esc_html( $error_msg ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $help ) ) : ?>
				<div class="pierre-help">
					<?php echo esc_html( $help ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

