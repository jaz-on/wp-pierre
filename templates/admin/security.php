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
    <h1><?php echo esc_html__('Pierre 🪨 Security', 'wp-pierre'); ?></h1>
    
    <div class="pierre-security-dashboard">
        <!-- Security Status Cards -->
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
        
        <!-- Security Actions -->
        <div class="pierre-security-actions">
            <h2><?php echo esc_html__('Security Actions', 'wp-pierre'); ?></h2>
            
            <div class="pierre-action-buttons">
                <button type="button" id="pierre-run-security-audit" class="button button-primary">
                    <?php echo esc_html__('Run Security Audit', 'wp-pierre'); ?>
                </button>
                
                <button type="button" id="pierre-view-security-logs" class="button">
                    <?php echo esc_html__('View Security Logs', 'wp-pierre'); ?>
                </button>
                
                <button type="button" id="pierre-clear-security-logs" class="button button-secondary">
                    <?php echo esc_html__('Clear Security Logs', 'wp-pierre'); ?>
                </button>
            </div>
        </div>
        
        <!-- Security Audit Results -->
        <div id="pierre-security-audit-results" class="pierre-audit-results">
            <h2><?php echo esc_html__('Security Audit Results', 'wp-pierre'); ?></h2>
            <div id="pierre-audit-content"></div>
        </div>
        
        <!-- Security Logs -->
        <div id="pierre-security-logs" class="pierre-security-logs">
            <h2><?php echo esc_html__('Security Logs', 'wp-pierre'); ?></h2>
            <div id="pierre-logs-content"></div>
        </div>
        
        <!-- Recent Security Events -->
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
        
        <!-- Audit History -->
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
