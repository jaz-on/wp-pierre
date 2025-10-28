<?php
/**
 * Pierre's public project template - he shows project-specific details! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_template_data'] ?? [];
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pierre Dashboard - <?php echo esc_html($data['project_name'] ?? $data['project'] ?? 'Unknown'); ?> (<?php echo esc_html($data['locale'] ?? 'Unknown'); ?>) 🪨</title>
    <link rel="stylesheet" href="<?php echo esc_url(PIERRE_PLUGIN_URL . 'assets/css/public.css'); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="pierre-public-dashboard">
    <div class="pierre-container">
        <div class="pierre-header">
            <h1><?php echo esc_html($data['project_name'] ?? $data['project'] ?? 'Unknown Project'); ?> 🪨</h1>
            <p><?php echo esc_html($data['locale_name'] ?? $data['locale'] ?? 'Unknown'); ?> Translation Project</p>
        </div>

        <div class="pierre-breadcrumb">
            <a href="<?php echo esc_url(home_url('/pierre/')); ?>">Pierre Dashboard</a> 
            &gt; 
            <a href="<?php echo esc_url(home_url('/pierre/' . $data['locale'] . '/')); ?>"><?php echo esc_html($data['locale_name'] ?? $data['locale'] ?? 'Unknown'); ?></a>
            &gt; 
            <?php echo esc_html($data['project_name'] ?? $data['project'] ?? 'Unknown'); ?>
        </div>

        <?php if (isset($data['stats']) && !empty($data['stats'])): ?>
        <div class="pierre-stats">
            <?php foreach ($data['stats'] as $stat): ?>
            <div class="pierre-stat-card">
                <div class="pierre-stat-number"><?php echo esc_html($stat['value']); ?></div>
                <div class="pierre-stat-label"><?php echo esc_html($stat['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="pierre-message">
            <strong>Pierre says:</strong> Here are the details for 
            <?php echo esc_html($data['project_name'] ?? $data['project'] ?? 'this project'); ?> 
            in <?php echo esc_html($data['locale_name'] ?? $data['locale'] ?? 'Unknown'); ?>! 🪨
        </div>

        <?php if (isset($data['assignments']) && !empty($data['assignments'])): ?>
        <div class="pierre-project-details">
            <h2>Team Members 🪨</h2>
            <div class="pierre-team-list">
                <?php foreach ($data['assignments'] as $assignment): ?>
                <div class="pierre-team-member">
                    <div class="pierre-member-info">
                        <strong><?php echo esc_html($assignment['user_name'] ?? 'Unknown User'); ?></strong>
                        <span class="pierre-member-role"><?php echo esc_html($assignment['role'] ?? 'Unknown Role'); ?></span>
                    </div>
                    <div class="pierre-member-meta">
                        <span>Assigned: <?php echo esc_html($assignment['assigned_at'] ?? 'Unknown'); ?></span>
                        <?php if (isset($assignment['assigned_by_name'])): ?>
                        <span>by <?php echo esc_html($assignment['assigned_by_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="pierre-message warning">
            <strong>Pierre says:</strong> No team members assigned to this project yet! 😢
        </div>
        <?php endif; ?>

        <div class="pierre-project-info">
            <h2>Project Information 🪨</h2>
            <div class="pierre-info-grid">
                <div class="pierre-info-item">
                    <strong>Project:</strong> <?php echo esc_html($data['project'] ?? 'Unknown'); ?>
                </div>
                <div class="pierre-info-item">
                    <strong>Locale:</strong> <?php echo esc_html($data['locale'] ?? 'Unknown'); ?>
                </div>
                <div class="pierre-info-item">
                    <strong>Project Name:</strong> <?php echo esc_html($data['project_name'] ?? 'Unknown'); ?>
                </div>
                <div class="pierre-info-item">
                    <strong>Locale Name:</strong> <?php echo esc_html($data['locale_name'] ?? 'Unknown'); ?>
                </div>
            </div>
        </div>

        <div class="pierre-project-actions">
            <h2>Quick Actions 🪨</h2>
            <div class="pierre-actions-grid">
                <button class="pierre-button" id="pierre-refresh-project">
                    Refresh Project Data 🪨
                </button>
                <button class="pierre-button secondary" id="pierre-view-translations">
                    View on Translate.WordPress.org 🪨
                </button>
                <button class="pierre-button secondary" id="pierre-test-notification">
                    Test Notification 🪨
                </button>
                <?php if (current_user_can('wpupdates_manage_projects')): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-projects')); ?>" class="pierre-button">
                    Manage in Admin 🪨
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="pierre-project-history">
            <h2>Recent Activity 🪨</h2>
            <div class="pierre-activity-list">
                <div class="pierre-activity-item">
                    <div class="pierre-activity-icon">📈</div>
                    <div class="pierre-activity-content">
                        <strong>Translation Progress</strong>
                        <p>Project completion increased to <?php echo esc_html($data['completion'] ?? '0'); ?>%</p>
                        <span class="pierre-activity-time">Last updated: <?php echo current_time('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
                
                <div class="pierre-activity-item">
                    <div class="pierre-activity-icon">👥</div>
                    <div class="pierre-activity-content">
                        <strong>Team Assignment</strong>
                        <p><?php echo count($data['assignments'] ?? []); ?> team members assigned to this project</p>
                        <span class="pierre-activity-time">Team management active</span>
                    </div>
                </div>
                
                <div class="pierre-activity-item">
                    <div class="pierre-activity-icon">🔍</div>
                    <div class="pierre-activity-content">
                        <strong>Surveillance Active</strong>
                        <p>Pierre is monitoring this project for changes</p>
                        <span class="pierre-activity-time">Surveillance system running</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pierre-actions">
            <a href="<?php echo esc_url(home_url('/pierre/' . $data['locale'] . '/')); ?>" class="pierre-button">
                Back to <?php echo esc_html($data['locale_name'] ?? $data['locale'] ?? 'Locale'); ?> 🪨
            </a>
            <a href="<?php echo esc_url(home_url('/pierre/')); ?>" class="pierre-button">
                Back to Dashboard 🪨
            </a>
            <?php if (current_user_can('wpupdates_view_dashboard')): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-dashboard')); ?>" class="pierre-button">
                Admin Dashboard 🪨
            </a>
            <?php endif; ?>
        </div>

        <div class="pierre-footer">
            <p>
                <strong>Pierre</strong> - WordPress Translation Monitor 🪨 | 
                <a href="<?php echo esc_url(home_url()); ?>">Back to Site</a>
            </p>
        </div>
    </div>

    <script>
    // Pierre's public JavaScript variables! 🪨
    var pierre_ajax = {
        ajax_url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo wp_create_nonce('pierre_ajax'); ?>'
    };
    </script>
    <script src="<?php echo esc_url(PIERRE_PLUGIN_URL . 'assets/js/public.js'); ?>"></script>
</body>
</html>
