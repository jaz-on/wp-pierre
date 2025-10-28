<?php
/**
 * Pierre's admin reports template - he shows his surveillance reports! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_admin_template_data'] ?? [];
?>

<div class="wrap">
    <div class="pierre-admin-header">
        <h1>Pierre Reports 🪨</h1>
        <p>Translation Monitoring Reports & Analytics</p>
    </div>

    <?php if (isset($data['stats']) && !empty($data['stats'])): ?>
    <div class="pierre-admin-stats">
        <h2>Report Statistics</h2>
        <div class="pierre-stats-grid">
            <?php foreach ($data['stats'] as $stat): ?>
            <div class="pierre-stat-box">
                <div class="pierre-stat-number"><?php echo esc_html($stat['value']); ?></div>
                <div class="pierre-stat-label"><?php echo esc_html($stat['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="pierre-admin-cards">
        <div class="pierre-admin-card">
            <h2>Surveillance Activity 🪨</h2>
            <div class="pierre-activity-chart">
                <div class="pierre-chart-placeholder">
                    <p>Pierre says: Activity chart coming soon! 📊</p>
                    <div class="pierre-chart-mock">
                        <div class="pierre-chart-bar" style="height: 60%;" title="Monday: 12 checks"></div>
                        <div class="pierre-chart-bar" style="height: 80%;" title="Tuesday: 16 checks"></div>
                        <div class="pierre-chart-bar" style="height: 45%;" title="Wednesday: 9 checks"></div>
                        <div class="pierre-chart-bar" style="height: 90%;" title="Thursday: 18 checks"></div>
                        <div class="pierre-chart-bar" style="height: 70%;" title="Friday: 14 checks"></div>
                        <div class="pierre-chart-bar" style="height: 30%;" title="Saturday: 6 checks"></div>
                        <div class="pierre-chart-bar" style="height: 25%;" title="Sunday: 5 checks"></div>
                    </div>
                </div>
            </div>
            
            <div class="pierre-activity-summary">
                <div class="pierre-summary-item">
                    <span class="pierre-summary-label">Total Checks:</span>
                    <span class="pierre-summary-value">1,247</span>
                </div>
                <div class="pierre-summary-item">
                    <span class="pierre-summary-label">Notifications Sent:</span>
                    <span class="pierre-summary-value">89</span>
                </div>
                <div class="pierre-summary-item">
                    <span class="pierre-summary-label">Projects Monitored:</span>
                    <span class="pierre-summary-value">12</span>
                </div>
            </div>
        </div>

        <div class="pierre-admin-card">
            <h2>Translation Progress 🪨</h2>
            <div class="pierre-progress-list">
                <div class="pierre-progress-item">
                    <div class="pierre-progress-header">
                        <strong>WordPress Core (fr)</strong>
                        <span class="pierre-progress-percentage">87%</span>
                    </div>
                    <div class="pierre-progress-bar">
                        <div class="pierre-progress-fill" style="width: 87%;"></div>
                    </div>
                    <div class="pierre-progress-details">
                        <span>2,847 / 3,273 strings</span>
                        <span class="pierre-progress-change">+2.3% this week</span>
                    </div>
                </div>
                
                <div class="pierre-progress-item">
                    <div class="pierre-progress-header">
                        <strong>WooCommerce (fr)</strong>
                        <span class="pierre-progress-percentage">92%</span>
                    </div>
                    <div class="pierre-progress-bar">
                        <div class="pierre-progress-fill" style="width: 92%;"></div>
                    </div>
                    <div class="pierre-progress-details">
                        <span>1,156 / 1,256 strings</span>
                        <span class="pierre-progress-change">+1.8% this week</span>
                    </div>
                </div>
                
                <div class="pierre-progress-item">
                    <div class="pierre-progress-header">
                        <strong>Elementor (fr)</strong>
                        <span class="pierre-progress-percentage">74%</span>
                    </div>
                    <div class="pierre-progress-bar">
                        <div class="pierre-progress-fill" style="width: 74%;"></div>
                    </div>
                    <div class="pierre-progress-details">
                        <span>892 / 1,205 strings</span>
                        <span class="pierre-progress-change">+5.2% this week</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pierre-admin-card">
            <h2>Recent Notifications 🪨</h2>
            <div class="pierre-notifications-list">
                <div class="pierre-notification-item success">
                    <div class="pierre-notification-icon">📈</div>
                    <div class="pierre-notification-content">
                        <strong>Completion Update</strong>
                        <p>WordPress Core (fr) reached 87% completion</p>
                        <span class="pierre-notification-time">2 hours ago</span>
                    </div>
                </div>
                
                <div class="pierre-notification-item warning">
                    <div class="pierre-notification-icon">⚠️</div>
                    <div class="pierre-notification-content">
                        <strong>Needs Attention</strong>
                        <p>Elementor (fr) has 45 strings waiting for review</p>
                        <span class="pierre-notification-time">5 hours ago</span>
                    </div>
                </div>
                
                <div class="pierre-notification-item info">
                    <div class="pierre-notification-icon">🆕</div>
                    <div class="pierre-notification-content">
                        <strong>New Strings</strong>
                        <p>WooCommerce (fr) has 12 new strings to translate</p>
                        <span class="pierre-notification-time">1 day ago</span>
                    </div>
                </div>
                
                <div class="pierre-notification-item success">
                    <div class="pierre-notification-icon">✅</div>
                    <div class="pierre-notification-content">
                        <strong>Project Complete</strong>
                        <p>Contact Form 7 (fr) reached 100% completion!</p>
                        <span class="pierre-notification-time">2 days ago</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pierre-admin-card">
            <h2>Export Reports 🪨</h2>
            <div class="pierre-export-options">
                <h3>Available Reports</h3>
                <div class="pierre-export-list">
                    <div class="pierre-export-item">
                        <strong>Weekly Summary</strong>
                        <p>Translation progress and activity for the past week</p>
                        <button class="pierre-admin-button small" data-report="weekly">
                            Export CSV 🪨
                        </button>
                    </div>
                    
                    <div class="pierre-export-item">
                        <strong>Project Status</strong>
                        <p>Current status of all monitored projects</p>
                        <button class="pierre-admin-button small" data-report="project-status">
                            Export CSV 🪨
                        </button>
                    </div>
                    
                    <div class="pierre-export-item">
                        <strong>Notification History</strong>
                        <p>Complete history of all notifications sent</p>
                        <button class="pierre-admin-button small" data-report="notifications">
                            Export CSV 🪨
                        </button>
                    </div>
                </div>
                
                <div class="pierre-export-actions">
                    <button class="pierre-admin-button" id="pierre-export-all">
                        Export All Reports 🪨
                    </button>
                    <button class="pierre-admin-button secondary" id="pierre-schedule-reports">
                        Schedule Reports 🪨
                    </button>
                </div>
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
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-settings')); ?>" class="pierre-admin-button">
            Settings 🪨
        </a>
    </div>
</div>

<style>
.pierre-activity-chart {
    margin-bottom: 20px;
}

.pierre-chart-placeholder {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
}

.pierre-chart-mock {
    display: flex;
    justify-content: space-around;
    align-items: end;
    height: 150px;
    margin-top: 20px;
    padding: 0 20px;
}

.pierre-chart-bar {
    width: 30px;
    background: #2271b1;
    border-radius: 4px 4px 0 0;
    transition: height 0.3s ease;
}

.pierre-chart-bar:hover {
    background: #135e96;
}

.pierre-activity-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.pierre-summary-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #2271b1;
}

.pierre-summary-label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.pierre-summary-value {
    display: block;
    font-size: 1.5em;
    font-weight: bold;
    color: #2271b1;
}

.pierre-progress-list {
    max-height: 400px;
    overflow-y: auto;
}

.pierre-progress-item {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #2271b1;
}

.pierre-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.pierre-progress-percentage {
    font-weight: bold;
    color: #2271b1;
    font-size: 1.1em;
}

.pierre-progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.pierre-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #46b450);
    transition: width 0.3s ease;
}

.pierre-progress-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.9em;
    color: #666;
}

.pierre-progress-change {
    color: #46b450;
    font-weight: 500;
}

.pierre-notifications-list {
    max-height: 400px;
    overflow-y: auto;
}

.pierre-notification-item {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    margin: 10px 0;
    border-radius: 6px;
    border-left: 4px solid;
}

.pierre-notification-item.success {
    background: #d4edda;
    border-left-color: #46b450;
}

.pierre-notification-item.warning {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.pierre-notification-item.info {
    background: #d1ecf1;
    border-left-color: #17a2b8;
}

.pierre-notification-icon {
    font-size: 1.5em;
    margin-right: 15px;
    margin-top: 2px;
}

.pierre-notification-content {
    flex: 1;
}

.pierre-notification-content strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.pierre-notification-content p {
    margin: 0 0 5px 0;
    color: #666;
    font-size: 0.9em;
}

.pierre-notification-time {
    font-size: 0.8em;
    color: #999;
}

.pierre-export-list {
    margin: 20px 0;
}

.pierre-export-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin: 10px 0;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #2271b1;
}

.pierre-export-item strong {
    display: block;
    margin-bottom: 5px;
    color: #2271b1;
}

.pierre-export-item p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.pierre-export-actions {
    text-align: center;
    margin-top: 20px;
    padding: 20px;
}

.pierre-export-actions .pierre-admin-button {
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
    // Pierre handles reports! 🪨
    $('.pierre-export-item button').on('click', function() {
        var reportType = $(this).data('report');
        var button = $(this);
        button.prop('disabled', true).text('Exporting...');
        
        $.post(ajaxurl, {
            action: 'pierre_export_report',
            report_type: reportType,
            nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                // Pierre could download the report data here! 🪨
                console.log('Report data:', response.data.data);
            } else {
                alert('Pierre says: ' + (response.data.message || 'Failed to export report!') + ' 😢');
            }
        }).always(function() {
            button.prop('disabled', false).text('Export');
        });
    });
    
    $('#pierre-export-all').on('click', function() {
        if (confirm('Pierre asks: Export all reports? This may take a moment! 🪨')) {
            var button = $(this);
            button.prop('disabled', true).text('Exporting All...');
            
            $.post(ajaxurl, {
                action: 'pierre_export_all_reports',
                nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Pierre could download all reports here! 🪨
                    console.log('All reports data:', response.data.data);
                } else {
                    alert('Pierre says: ' + (response.data.message || 'Failed to export reports!') + ' 😢');
                }
            }).always(function() {
                button.prop('disabled', false).text('Export All Reports');
            });
        }
    });
    
    $('#pierre-schedule-reports').on('click', function() {
        var frequency = prompt('Pierre asks: How often should reports be generated?\n\nEnter: daily, weekly, or monthly', 'weekly');
        
        if (frequency && ['daily', 'weekly', 'monthly'].includes(frequency)) {
            var button = $(this);
            button.prop('disabled', true).text('Scheduling...');
            
            $.post(ajaxurl, {
                action: 'pierre_schedule_reports',
                schedule_frequency: frequency,
                report_types: ['projects', 'teams', 'surveillance', 'notifications'],
                nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    console.log('Schedule data:', response.data.data);
                } else {
                    alert('Pierre says: ' + (response.data.message || 'Failed to schedule reports!') + ' 😢');
                }
            }).always(function() {
                button.prop('disabled', false).text('Schedule Reports');
            });
        } else if (frequency) {
            alert('Pierre says: Please enter a valid frequency (daily, weekly, or monthly)! 😢');
        }
    });
    
    // Pierre animates progress bars! 🪨
    $('.pierre-progress-fill').each(function() {
        var $this = $(this);
        var width = $this.css('width');
        $this.css('width', '0%');
        setTimeout(function() {
            $this.css('width', width);
        }, 500);
    });
});
</script>
