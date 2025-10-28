<?php
/**
 * Pierre's security admin template - he protects everything! 🪨
 * 
 * This template provides the admin interface for Pierre's security
 * features including audit, logs, and security status monitoring.
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

// Pierre gets his security data! 🪨
$security_manager = new \Pierre\Security\SecurityManager();
$csrf_protection = new \Pierre\Security\CSRFProtection();
$security_auditor = new \Pierre\Security\SecurityAuditor();

$security_status = $security_manager->get_status();
$csrf_status = $csrf_protection->get_status();
$auditor_status = $security_auditor->get_status();

// Pierre gets recent security logs! 🪨
$recent_logs = $csrf_protection->get_security_logs(10);
$audit_history = $security_auditor->get_audit_history(5);
?>

<div class="wrap">
    <h1><?php echo esc_html__('Pierre Security Center', 'wp-pierre'); ?> 🪨</h1>
    
    <div class="pierre-security-dashboard">
        <!-- Pierre's Security Status Cards! 🪨 -->
        <div class="pierre-security-cards">
            <div class="pierre-card">
                <h3><?php echo esc_html__('Security Manager', 'wp-pierre'); ?></h3>
                <div class="pierre-status">
                    <span class="pierre-status-indicator <?php echo $security_status['security_enabled'] ? 'active' : 'inactive'; ?>"></span>
                    <span class="pierre-status-text">
                        <?php echo $security_status['security_enabled'] ? 
                            esc_html__('Active', 'wp-pierre') : 
                            esc_html__('Inactive', 'wp-pierre'); ?>
                    </span>
                </div>
                <p><?php echo esc_html($security_status['message']); ?></p>
            </div>
            
            <div class="pierre-card">
                <h3><?php echo esc_html__('CSRF Protection', 'wp-pierre'); ?></h3>
                <div class="pierre-status">
                    <span class="pierre-status-indicator <?php echo $csrf_status['csrf_protection_enabled'] ? 'active' : 'inactive'; ?>"></span>
                    <span class="pierre-status-text">
                        <?php echo $csrf_status['csrf_protection_enabled'] ? 
                            esc_html__('Active', 'wp-pierre') : 
                            esc_html__('Inactive', 'wp-pierre'); ?>
                    </span>
                </div>
                <p><?php echo esc_html($csrf_status['message']); ?></p>
            </div>
            
            <div class="pierre-card">
                <h3><?php echo esc_html__('Security Auditor', 'wp-pierre'); ?></h3>
                <div class="pierre-status">
                    <span class="pierre-status-indicator <?php echo $auditor_status['security_auditor_enabled'] ? 'active' : 'inactive'; ?>"></span>
                    <span class="pierre-status-text">
                        <?php echo $auditor_status['security_auditor_enabled'] ? 
                            esc_html__('Active', 'wp-pierre') : 
                            esc_html__('Inactive', 'wp-pierre'); ?>
                    </span>
                </div>
                <p><?php echo esc_html($auditor_status['message']); ?></p>
            </div>
        </div>
        
        <!-- Pierre's Security Actions! 🪨 -->
        <div class="pierre-security-actions">
            <h2><?php echo esc_html__('Security Actions', 'wp-pierre'); ?></h2>
            
            <div class="pierre-action-buttons">
                <button type="button" id="pierre-run-security-audit" class="button button-primary">
                    <?php echo esc_html__('Run Security Audit', 'wp-pierre'); ?> 🔍
                </button>
                
                <button type="button" id="pierre-view-security-logs" class="button">
                    <?php echo esc_html__('View Security Logs', 'wp-pierre'); ?> 📋
                </button>
                
                <button type="button" id="pierre-clear-security-logs" class="button button-secondary">
                    <?php echo esc_html__('Clear Security Logs', 'wp-pierre'); ?> 🗑️
                </button>
            </div>
        </div>
        
        <!-- Pierre's Security Audit Results! 🪨 -->
        <div id="pierre-security-audit-results" class="pierre-audit-results" style="display: none;">
            <h2><?php echo esc_html__('Security Audit Results', 'wp-pierre'); ?></h2>
            <div id="pierre-audit-content"></div>
        </div>
        
        <!-- Pierre's Security Logs! 🪨 -->
        <div id="pierre-security-logs" class="pierre-security-logs" style="display: none;">
            <h2><?php echo esc_html__('Security Logs', 'wp-pierre'); ?></h2>
            <div id="pierre-logs-content"></div>
        </div>
        
        <!-- Pierre's Recent Security Events! 🪨 -->
        <div class="pierre-recent-events">
            <h2><?php echo esc_html__('Recent Security Events', 'wp-pierre'); ?></h2>
            
            <?php if (empty($recent_logs)): ?>
                <p><?php echo esc_html__('No recent security events.', 'wp-pierre'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Timestamp', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Event Type', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('User ID', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('IP Address', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Details', 'wp-pierre'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log['timestamp']); ?></td>
                                <td>
                                    <span class="pierre-event-type pierre-event-<?php echo esc_attr($log['event_type']); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $log['event_type']))); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log['user_id']); ?></td>
                                <td><?php echo esc_html($log['ip_address']); ?></td>
                                <td>
                                    <details>
                                        <summary><?php echo esc_html__('View Details', 'wp-pierre'); ?></summary>
                                        <pre><?php echo esc_html(json_encode($log['data'], JSON_PRETTY_PRINT)); ?></pre>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Pierre's Audit History! 🪨 -->
        <div class="pierre-audit-history">
            <h2><?php echo esc_html__('Audit History', 'wp-pierre'); ?></h2>
            
            <?php if (empty($audit_history)): ?>
                <p><?php echo esc_html__('No audit history available.', 'wp-pierre'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Date', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Overall Score', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Critical Issues', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Warnings', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Actions', 'wp-pierre'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_history as $audit): ?>
                            <tr>
                                <td><?php echo esc_html($audit['timestamp']); ?></td>
                                <td>
                                    <span class="pierre-score pierre-score-<?php echo $audit['overall_score'] >= 80 ? 'good' : ($audit['overall_score'] >= 60 ? 'warning' : 'critical'); ?>">
                                        <?php echo esc_html($audit['overall_score']); ?>%
                                    </span>
                                </td>
                                <td><?php echo esc_html(count($audit['critical_issues'])); ?></td>
                                <td><?php echo esc_html(count($audit['warnings'])); ?></td>
                                <td>
                                    <button type="button" class="button button-small pierre-view-audit" data-audit-id="<?php echo esc_attr($audit['audit_id']); ?>">
                                        <?php echo esc_html__('View Details', 'wp-pierre'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.pierre-security-dashboard {
    max-width: 1200px;
}

.pierre-security-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.pierre-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.pierre-card h3 {
    margin-top: 0;
    color: #23282d;
}

.pierre-status {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.pierre-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.pierre-status-indicator.active {
    background-color: #46b450;
}

.pierre-status-indicator.inactive {
    background-color: #dc3232;
}

.pierre-security-actions {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.pierre-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.pierre-audit-results,
.pierre-security-logs {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.pierre-recent-events,
.pierre-audit-history {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.pierre-event-type {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.pierre-event-csrf_validation_success {
    background-color: #d4edda;
    color: #155724;
}

.pierre-event-rate_limit_exceeded {
    background-color: #f8d7da;
    color: #721c24;
}

.pierre-score {
    padding: 4px 8px;
    border-radius: 3px;
    font-weight: bold;
}

.pierre-score-good {
    background-color: #d4edda;
    color: #155724;
}

.pierre-score-warning {
    background-color: #fff3cd;
    color: #856404;
}

.pierre-score-critical {
    background-color: #f8d7da;
    color: #721c24;
}

details summary {
    cursor: pointer;
    font-weight: bold;
}

details pre {
    background: #f1f1f1;
    padding: 10px;
    border-radius: 3px;
    font-size: 12px;
    overflow-x: auto;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Pierre handles security audit! 🪨
    $('#pierre-run-security-audit').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        button.prop('disabled', true).text('<?php echo esc_js(__('Running Audit...', 'wp-pierre')); ?>');
        
        $.post(ajaxurl, {
            action: 'pierre_security_audit',
            nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
        }, function(response) {
            if (response.success) {
                $('#pierre-audit-content').html('<pre>' + JSON.stringify(response.data.audit_results, null, 2) + '</pre>');
                $('#pierre-security-audit-results').show();
                
                // Pierre shows success message! 🪨
                $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                    .insertAfter('.wrap h1')
                    .delay(5000)
                    .fadeOut();
            } else {
                alert('Pierre says: ' + response.data + ' 😢');
            }
        }).fail(function() {
            alert('Pierre says: Security audit failed! 😢');
        }).always(function() {
            button.prop('disabled', false).text(originalText);
        });
    });
    
    // Pierre handles security logs! 🪨
    $('#pierre-view-security-logs').on('click', function() {
        $.post(ajaxurl, {
            action: 'pierre_security_logs',
            nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>',
            limit: 50
        }, function(response) {
            if (response.success) {
                let logsHtml = '<table class="wp-list-table widefat fixed striped"><thead><tr><th><?php echo esc_js(__('Timestamp', 'wp-pierre')); ?></th><th><?php echo esc_js(__('Event Type', 'wp-pierre')); ?></th><th><?php echo esc_js(__('User ID', 'wp-pierre')); ?></th><th><?php echo esc_js(__('IP Address', 'wp-pierre')); ?></th><th><?php echo esc_js(__('Details', 'wp-pierre')); ?></th></tr></thead><tbody>';
                
                response.data.security_logs.forEach(function(log) {
                    logsHtml += '<tr>';
                    logsHtml += '<td>' + log.timestamp + '</td>';
                    logsHtml += '<td><span class="pierre-event-type pierre-event-' + log.event_type + '">' + log.event_type.replace(/_/g, ' ').toUpperCase() + '</span></td>';
                    logsHtml += '<td>' + log.user_id + '</td>';
                    logsHtml += '<td>' + log.ip_address + '</td>';
                    logsHtml += '<td><details><summary><?php echo esc_js(__('View Details', 'wp-pierre')); ?></summary><pre>' + JSON.stringify(log.data, null, 2) + '</pre></details></td>';
                    logsHtml += '</tr>';
                });
                
                logsHtml += '</tbody></table>';
                
                $('#pierre-logs-content').html(logsHtml);
                $('#pierre-security-logs').show();
            } else {
                alert('Pierre says: ' + response.data + ' 😢');
            }
        }).fail(function() {
            alert('Pierre says: Failed to retrieve security logs! 😢');
        });
    });
    
    // Pierre handles clear security logs! 🪨
    $('#pierre-clear-security-logs').on('click', function() {
        if (confirm('<?php echo esc_js(__('Pierre says: Are you sure you want to clear all security logs?', 'wp-pierre')); ?> 🪨')) {
            $.post(ajaxurl, {
                action: 'pierre_clear_security_logs',
                nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Pierre says: Security logs cleared! 🪨');
                    location.reload();
                } else {
                    alert('Pierre says: ' + response.data + ' 😢');
                }
            }).fail(function() {
                alert('Pierre says: Failed to clear security logs! 😢');
            });
        }
    });
    
    // Pierre handles view audit details! 🪨
    $('.pierre-view-audit').on('click', function() {
        const auditId = $(this).data('audit-id');
        // Pierre would show audit details in a modal or new page! 🪨
        alert('Pierre says: Audit details for ' + auditId + ' would be shown here! 🪨');
    });
});
</script>
