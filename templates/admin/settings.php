<?php
/**
 * Pierre's admin settings template - he manages his configuration! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_admin_template_data'] ?? [];
$settings = $data['settings'] ?? [];
?>

<div class="wrap">
    <!-- Settings page header is handled by render_settings_page() -->

    <div class="pierre-grid">
        <div class="pierre-card">
            <h2><?php echo esc_html__('Admin Slack Integration for Global Testing', 'wp-pierre'); ?></h2>
            <form class="pierre-form-compact" id="pierre-slack-settings">
                <div class="pierre-form-group">
                    <label for="slack_webhook_url"><?php echo esc_html__('Slack Webhook URL:', 'wp-pierre'); ?></label>
                    <input type="url" class="regular-text"
                           id="slack_webhook_url" 
                           name="slack_webhook_url" 
                           value="<?php echo esc_attr($settings['slack_webhook_url'] ?? ''); ?>"
                           placeholder="https://hooks.slack.com/services/..."
                           required>
                    <div class="pierre-help">
                        <?php echo esc_html__('Route ALL notifications globally here (admin/debug). Per-locale overrides take precedence.', 'wp-pierre'); ?>
                        <?php
                        printf(
                            /* translators: 1: Link to Slack API documentation, 2: Link to GitHub repository */
                            esc_html__('Need help? See %1$s or check the %2$s.', 'wp-pierre'),
                            '<a href="https://api.slack.com/messaging/webhooks" target="_blank" rel="noopener noreferrer">' . esc_html__('Slack webhook documentation', 'wp-pierre') . '</a>',
                            '<a href="https://github.com/jaz-on/wp-pierre/wiki/" target="_blank" rel="noopener noreferrer">' . esc_html__('plugin documentation', 'wp-pierre') . '</a>'
                        );
                        ?>
                    </div>
                    <p class="description">
                        <?php echo esc_html__('Use this only if you need to route ALL locales/projects to a single Slack endpoint (admin testing/logging). To override per locale, go to Projects or Locale View > Notifications.', 'wp-pierre'); ?>
                    </p>
                </div>
                
                <div class="pierre-form-group">
                    <label for="notifications_enabled">
                        <input type="checkbox" 
                               id="notifications_enabled" 
                               name="notifications_enabled" 
                               <?php checked(!empty($settings['notifications_enabled'])); ?>>
                        <?php echo esc_html__('Enable Notifications', 'wp-pierre'); ?>
                    </label>
                    <div class="pierre-help">
                        <?php echo esc_html__('Enable or disable Pierre\'s notification system', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Save/Activate Webhook', 'wp-pierre'); ?>
                    </button>
                    <button type="button" class="button" id="pierre-test-slack">
                        <?php echo esc_html__('Test Webhook', 'wp-pierre'); ?>
                    </button>
                </div>
            </form>
            
            <?php if (isset($data['notifier_status'])): ?>
            <div class="pierre-status-info">
                <h3><?php echo esc_html__('Slack Status', 'wp-pierre'); ?></h3>
                <p><strong><?php echo esc_html__('Ready:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['notifier_status']['ready'] ? esc_html__('Yes', 'wp-pierre') : esc_html__('No', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Status:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['notifier_status']['message'] ?? esc_html__('No status message', 'wp-pierre')); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Surveillance Settings', 'wp-pierre'); ?></h2>
            <form class="pierre-form-compact" id="pierre-surveillance-settings">
                <div class="pierre-form-group">
                    <label for="surveillance_interval"><?php echo esc_html__('Surveillance Interval (minutes):', 'wp-pierre'); ?></label>
                    <select id="surveillance_interval" name="surveillance_interval" class="wp-core-ui">
                        <option value="5" <?php selected(($settings['surveillance_interval'] ?? 15) == 5); ?>><?php echo esc_html__('5 minutes', 'wp-pierre'); ?></option>
                        <option value="15" <?php selected(($settings['surveillance_interval'] ?? 15) == 15); ?>><?php echo esc_html__('15 minutes', 'wp-pierre'); ?></option>
                        <option value="30" <?php selected(($settings['surveillance_interval'] ?? 15) == 30); ?>><?php echo esc_html__('30 minutes', 'wp-pierre'); ?></option>
                        <option value="60" <?php selected(($settings['surveillance_interval'] ?? 15) == 60); ?>><?php echo esc_html__('1 hour', 'wp-pierre'); ?></option>
                        <option value="120" <?php selected(($settings['surveillance_interval'] ?? 15) == 120); ?>><?php echo esc_html__('2 hours', 'wp-pierre'); ?></option>
                    </select>
                    <div class="pierre-help">
                        <?php echo esc_html__('How often Pierre checks for translation changes', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="auto_start_surveillance">
                        <input type="checkbox" 
                               id="auto_start_surveillance" 
                               name="auto_start_surveillance" 
                               <?php checked(!empty($settings['auto_start_surveillance'])); ?>>
                        <?php echo esc_html__('Auto-start Surveillance', 'wp-pierre'); ?>
                    </label>
                    <div class="pierre-help">
                        <?php echo esc_html__('Automatically start surveillance when plugin is activated', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="max_projects_per_check"><?php echo esc_html__('Maximum Projects per Check:', 'wp-pierre'); ?></label>
                    <input type="number" class="regular-text"
                           id="max_projects_per_check" 
                           name="max_projects_per_check" 
                           value="<?php echo esc_attr($settings['max_projects_per_check'] ?? 10); ?>"
                           min="1" 
                           max="50">
                    <div class="pierre-help">
                        <?php echo esc_html__('Limit the number of projects checked in each surveillance cycle', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Save Settings', 'wp-pierre'); ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Notification Settings', 'wp-pierre'); ?></h2>
            <form class="pierre-form-compact" id="pierre-notification-settings">
                <div class="pierre-form-group">
                    <label for="notification_types"><?php echo esc_html__('Notification Types:', 'wp-pierre'); ?></label>
                    <div class="pierre-checkbox-group">
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="new_strings" 
                                   <?php checked(in_array('new_strings', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            <?php echo esc_html__('New Strings Detected', 'wp-pierre'); ?>
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="completion_update" 
                                   <?php checked(in_array('completion_update', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            <?php echo esc_html__('Completion Updates', 'wp-pierre'); ?>
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="needs_attention" 
                                   <?php checked(in_array('needs_attention', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            <?php echo esc_html__('Needs Attention', 'wp-pierre'); ?>
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="errors" 
                                   <?php checked(in_array('errors', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            <?php echo esc_html__('Errors', 'wp-pierre'); ?>
                        </label>
                    </div>
                    <div class="pierre-help">
                        <?php echo esc_html__('Select which types of notifications to send', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="notification_threshold"><?php echo esc_html__('Completion Threshold (%):', 'wp-pierre'); ?></label>
                    <input type="number" class="regular-text"
                           id="notification_threshold" 
                           name="notification_threshold" 
                           value="<?php echo esc_attr($settings['notification_threshold'] ?? 80); ?>"
                           min="0" 
                           max="100">
                    <div class="pierre-help">
                        <?php echo esc_html__('Only send notifications when completion is above this threshold', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Save Settings', 'wp-pierre'); ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('System Status', 'wp-pierre'); ?></h2>
            <?php if (isset($data['cron_status'])): ?>
            <div class="pierre-system-status">
                <h3><?php echo esc_html__('Cron Status', 'wp-pierre'); ?></h3>
                <p><strong><?php echo esc_html__('Active:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['cron_status']['active'] ? esc_html__('Yes', 'wp-pierre') : esc_html__('No', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Next Run:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['cron_status']['next_run'] ?? esc_html__('Not scheduled', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Message:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['cron_status']['message'] ?? esc_html__('No status message', 'wp-pierre')); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="pierre-system-actions">
                <button class="button" id="pierre-flush-cache">
                    <?php echo esc_html__('Flush Cache', 'wp-pierre'); ?>
                </button>
                <button class="button" id="pierre-reset-settings">
                    <?php echo esc_html__('Reset to Defaults', 'wp-pierre'); ?>
                </button>
                <button class="button button-link-delete" id="pierre-clear-all-data">
                    <?php echo esc_html__('Clear All Data', 'wp-pierre'); ?>
                </button>
            </div>
        </div>
    </div>

</div>
