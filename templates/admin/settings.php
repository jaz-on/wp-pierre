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
    <div class="pierre-admin-header">
        <h1>Pierre Settings 🪨</h1>
        <p>Configure Pierre's Translation Monitoring System</p>
    </div>

    <div class="pierre-admin-cards">
        <div class="pierre-admin-card">
            <h2>Slack Integration 🪨</h2>
            <form class="pierre-admin-form" id="pierre-slack-settings">
                <div class="pierre-form-group">
                    <label for="slack_webhook_url">Slack Webhook URL:</label>
                    <input type="url" 
                           id="slack_webhook_url" 
                           name="slack_webhook_url" 
                           value="<?php echo esc_attr($settings['slack_webhook_url'] ?? ''); ?>"
                           placeholder="https://hooks.slack.com/services/..."
                           required>
                    <div class="pierre-form-help">
                        Enter your Slack webhook URL to enable notifications
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="notifications_enabled">
                        <input type="checkbox" 
                               id="notifications_enabled" 
                               name="notifications_enabled" 
                               <?php checked(!empty($settings['notifications_enabled'])); ?>>
                        Enable Notifications
                    </label>
                    <div class="pierre-form-help">
                        Enable or disable Pierre's notification system
                    </div>
                </div>
                
                <div class="pierre-form-actions">
                    <button type="submit" class="pierre-admin-button success">
                        Save Slack Settings 🪨
                    </button>
                    <button type="button" class="pierre-admin-button secondary" id="pierre-test-slack">
                        Test Slack Connection 🪨
                    </button>
                </div>
            </form>
            
            <?php if (isset($data['notifier_status'])): ?>
            <div class="pierre-status-info">
                <h3>Slack Status</h3>
                <p><strong>Ready:</strong> <?php echo esc_html($data['notifier_status']['ready'] ? 'Yes' : 'No'); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html($data['notifier_status']['message'] ?? 'No status message'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="pierre-admin-card">
            <h2>Surveillance Settings 🪨</h2>
            <form class="pierre-admin-form" id="pierre-surveillance-settings">
                <div class="pierre-form-group">
                    <label for="surveillance_interval">Surveillance Interval (minutes):</label>
                    <select id="surveillance_interval" name="surveillance_interval">
                        <option value="5" <?php selected(($settings['surveillance_interval'] ?? 15) == 5); ?>>5 minutes</option>
                        <option value="15" <?php selected(($settings['surveillance_interval'] ?? 15) == 15); ?>>15 minutes</option>
                        <option value="30" <?php selected(($settings['surveillance_interval'] ?? 15) == 30); ?>>30 minutes</option>
                        <option value="60" <?php selected(($settings['surveillance_interval'] ?? 15) == 60); ?>>1 hour</option>
                        <option value="120" <?php selected(($settings['surveillance_interval'] ?? 15) == 120); ?>>2 hours</option>
                    </select>
                    <div class="pierre-form-help">
                        How often Pierre checks for translation changes
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="auto_start_surveillance">
                        <input type="checkbox" 
                               id="auto_start_surveillance" 
                               name="auto_start_surveillance" 
                               <?php checked(!empty($settings['auto_start_surveillance'])); ?>>
                        Auto-start Surveillance
                    </label>
                    <div class="pierre-form-help">
                        Automatically start surveillance when plugin is activated
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="max_projects_per_check">Maximum Projects per Check:</label>
                    <input type="number" 
                           id="max_projects_per_check" 
                           name="max_projects_per_check" 
                           value="<?php echo esc_attr($settings['max_projects_per_check'] ?? 10); ?>"
                           min="1" 
                           max="50">
                    <div class="pierre-form-help">
                        Limit the number of projects checked in each surveillance cycle
                    </div>
                </div>
                
                <div class="pierre-form-actions">
                    <button type="submit" class="pierre-admin-button success">
                        Save Surveillance Settings 🪨
                    </button>
                </div>
            </form>
        </div>

        <div class="pierre-admin-card">
            <h2>Notification Settings 🪨</h2>
            <form class="pierre-admin-form" id="pierre-notification-settings">
                <div class="pierre-form-group">
                    <label for="notification_types">Notification Types:</label>
                    <div class="pierre-checkbox-group">
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="new_strings" 
                                   <?php checked(in_array('new_strings', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            New Strings Detected
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="completion_update" 
                                   <?php checked(in_array('completion_update', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            Completion Updates
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="needs_attention" 
                                   <?php checked(in_array('needs_attention', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            Needs Attention
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="errors" 
                                   <?php checked(in_array('errors', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            Errors
                        </label>
                    </div>
                    <div class="pierre-form-help">
                        Select which types of notifications to send
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="notification_threshold">Completion Threshold (%):</label>
                    <input type="number" 
                           id="notification_threshold" 
                           name="notification_threshold" 
                           value="<?php echo esc_attr($settings['notification_threshold'] ?? 80); ?>"
                           min="0" 
                           max="100">
                    <div class="pierre-form-help">
                        Only send notifications when completion is above this threshold
                    </div>
                </div>
                
                <div class="pierre-form-actions">
                    <button type="submit" class="pierre-admin-button success">
                        Save Notification Settings 🪨
                    </button>
                </div>
            </form>
        </div>

        <div class="pierre-admin-card">
            <h2>System Status 🪨</h2>
            <?php if (isset($data['cron_status'])): ?>
            <div class="pierre-system-status">
                <h3>Cron Status</h3>
                <p><strong>Active:</strong> <?php echo esc_html($data['cron_status']['active'] ? 'Yes' : 'No'); ?></p>
                <p><strong>Next Run:</strong> <?php echo esc_html($data['cron_status']['next_run'] ?? 'Not scheduled'); ?></p>
                <p><strong>Message:</strong> <?php echo esc_html($data['cron_status']['message'] ?? 'No status message'); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="pierre-system-actions">
                <button class="pierre-admin-button" id="pierre-flush-cache">
                    Flush Cache 🪨
                </button>
                <button class="pierre-admin-button secondary" id="pierre-reset-settings">
                    Reset to Defaults 🪨
                </button>
                <button class="pierre-admin-button danger" id="pierre-clear-all-data">
                    Clear All Data 🪨
                </button>
            </div>
        </div>
    </div>

    <div class="pierre-admin-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-dashboard')); ?>" class="pierre-admin-button">
            Back to Dashboard 🪨
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-projects')); ?>" class="pierre-admin-button">
            Manage Projects 🪨
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-reports')); ?>" class="pierre-admin-button">
            View Reports 🪨
        </a>
    </div>
</div>

<style>
.pierre-form-group {
    margin-bottom: 20px;
}

.pierre-form-group label {
    display: block;
    font-weight: 600;
    color: #2271b1;
    margin-bottom: 5px;
}

.pierre-form-group input,
.pierre-form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    font-size: 14px;
}

.pierre-form-group input:focus,
.pierre-form-group select:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

.pierre-form-help {
    font-size: 0.9em;
    color: #666;
    margin-top: 5px;
}

.pierre-checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.pierre-checkbox-group label {
    display: flex;
    align-items: center;
    font-weight: normal;
    color: #333;
}

.pierre-checkbox-group input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}

.pierre-form-actions {
    margin-top: 20px;
    text-align: center;
}

.pierre-form-actions .pierre-admin-button {
    margin: 0 10px 10px 0;
}

.pierre-status-info {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #2271b1;
}

.pierre-status-info h3 {
    margin-top: 0;
    color: #2271b1;
}

.pierre-system-status {
    margin-bottom: 20px;
}

.pierre-system-status h3 {
    color: #2271b1;
    margin-bottom: 10px;
}

.pierre-system-status p {
    margin: 8px 0;
    padding: 5px;
    background: #f8f9fa;
    border-radius: 4px;
}

.pierre-system-actions {
    text-align: center;
    padding: 20px;
}

.pierre-system-actions .pierre-admin-button {
    margin: 0 10px 10px 0;
}

.pierre-admin-actions {
    margin-top: 30px;
    text-align: center;
}

.pierre-admin-actions .pierre-admin-button {
    margin: 0 10px 10px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Pierre handles settings management! 🪨
    $('#pierre-slack-settings').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var button = form.find('button[type="submit"]');
        button.prop('disabled', true).text('Saving...');
        
        $.post(ajaxurl, {
            action: 'pierre_admin_save_settings',
            slack_webhook_url: $('#slack_webhook_url').val(),
            nonce: '<?php echo wp_create_nonce('pierre_admin_ajax'); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Pierre says: ' + (response.data.message || 'Failed to save settings!') + ' 😢');
            }
        }).always(function() {
            button.prop('disabled', false).text('Save Settings');
        });
    });
    
    $('#pierre-surveillance-settings').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var button = form.find('button[type="submit"]');
        button.prop('disabled', true).text('Saving...');
        
        $.post(ajaxurl, {
            action: 'pierre_admin_save_settings',
            surveillance_interval: $('#surveillance_interval').val(),
            max_projects_per_check: $('#max_projects_per_check').val(),
            nonce: '<?php echo wp_create_nonce('pierre_admin_ajax'); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Pierre says: ' + (response.data.message || 'Failed to save settings!') + ' 😢');
            }
        }).always(function() {
            button.prop('disabled', false).text('Save Settings');
        });
    });
    
    $('#pierre-notification-settings').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var button = form.find('button[type="submit"]');
        button.prop('disabled', true).text('Saving...');
        
        var notificationTypes = [];
        $('input[name="notification_types[]"]:checked').each(function() {
            notificationTypes.push($(this).val());
        });
        
        $.post(ajaxurl, {
            action: 'pierre_admin_save_settings',
            notification_types: notificationTypes,
            notification_threshold: $('#notification_threshold').val(),
            nonce: '<?php echo wp_create_nonce('pierre_admin_ajax'); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Pierre says: ' + (response.data.message || 'Failed to save settings!') + ' 😢');
            }
        }).always(function() {
            button.prop('disabled', false).text('Save Settings');
        });
    });
    
    $('#pierre-test-slack').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('Testing...');
        
        $.post(ajaxurl, {
            action: 'pierre_admin_test_notification',
            nonce: '<?php echo wp_create_nonce('pierre_admin_ajax'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Pierre says: Slack test successful! 🪨');
            } else {
                alert('Pierre says: ' + (response.data.message || 'Slack test failed!') + ' 😢');
            }
        }).always(function() {
            button.prop('disabled', false).text('Test Webhook');
        });
    });
    
    $('#pierre-flush-cache').on('click', function() {
        if (confirm('Pierre asks: Flush all cached data? 🪨')) {
            var button = $(this);
            button.prop('disabled', true).text('Flushing...');
            
            $.post(ajaxurl, {
                action: 'pierre_flush_cache',
                nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Pierre says: ' + (response.data.message || 'Failed to flush cache!') + ' 😢');
                }
            }).always(function() {
                button.prop('disabled', false).text('Flush Cache');
            });
        }
    });
    
    $('#pierre-reset-settings').on('click', function() {
        if (confirm('Pierre asks: Reset all settings to defaults? This cannot be undone! 😢')) {
            var button = $(this);
            button.prop('disabled', true).text('Resetting...');
            
            $.post(ajaxurl, {
                action: 'pierre_reset_settings',
                nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Pierre says: ' + (response.data.message || 'Failed to reset settings!') + ' 😢');
                }
            }).always(function() {
                button.prop('disabled', false).text('Reset to Defaults');
            });
        }
    });
    
    $('#pierre-clear-all-data').on('click', function() {
        if (confirm('Pierre asks: Clear ALL data? This will remove all projects, assignments, and settings! This cannot be undone! 😢')) {
            if (confirm('Pierre asks: Are you REALLY sure? This is your last chance! 😢')) {
                var button = $(this);
                button.prop('disabled', true).text('Clearing...');
                
                $.post(ajaxurl, {
                    action: 'pierre_clear_data',
                    nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Pierre says: ' + (response.data.message || 'Failed to clear data!') + ' 😢');
                    }
                }).always(function() {
                    button.prop('disabled', false).text('Clear All Data');
                });
            }
        }
    });
});
</script>
