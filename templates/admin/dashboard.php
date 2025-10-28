<?php
/**
 * Pierre's admin dashboard template - he shows his main admin page! 🪨
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
        <h1>Pierre Dashboard 🪨</h1>
        <p>WordPress Translation Monitor - Surveillance & Team Management</p>
    </div>

    <?php if (isset($data['stats']) && !empty($data['stats'])): ?>
    <div class="pierre-admin-stats">
        <h2>Pierre's Statistics</h2>
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
            <h2>Surveillance Status 🪨</h2>
            <?php if (isset($data['surveillance_status'])): ?>
            <div class="pierre-status-info">
                <p><strong>Status:</strong> <?php echo esc_html($data['surveillance_status']['active'] ? 'Active' : 'Inactive'); ?></p>
                <p><strong>Message:</strong> <?php echo esc_html($data['surveillance_status']['message'] ?? 'No status message'); ?></p>
                <?php if (isset($data['surveillance_status']['next_run'])): ?>
                <p><strong>Next Run:</strong> <?php echo esc_html($data['surveillance_status']['next_run']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="pierre-admin-card">
            <h2>Notification System 🪨</h2>
            <?php if (isset($data['notifier_status'])): ?>
            <div class="pierre-status-info">
                <p><strong>Ready:</strong> <?php echo esc_html($data['notifier_status']['ready'] ? 'Yes' : 'No'); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html($data['notifier_status']['message'] ?? 'No status message'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="pierre-admin-card">
            <h2>Your Assignments 🪨</h2>
            <?php if (isset($data['user_assignments']) && !empty($data['user_assignments'])): ?>
            <div class="pierre-assignments-list">
                <?php foreach ($data['user_assignments'] as $assignment): ?>
                <div class="pierre-assignment-item">
                    <strong><?php echo esc_html($assignment['project_name'] ?? 'Unknown Project'); ?></strong>
                    <span class="pierre-assignment-meta">
                        (<?php echo esc_html($assignment['locale_code'] ?? 'Unknown Locale'); ?> - 
                        <?php echo esc_html($assignment['role'] ?? 'Unknown Role'); ?>)
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p>Pierre says: You don't have any project assignments yet! 😢</p>
            <?php endif; ?>
        </div>

        <div class="pierre-admin-card">
            <h2>Watched Projects 🪨</h2>
            <?php if (isset($data['watched_projects']) && !empty($data['watched_projects'])): ?>
            <div class="pierre-projects-list">
                <?php foreach ($data['watched_projects'] as $project): ?>
                <div class="pierre-project-item">
                    <strong><?php echo esc_html($project['project_slug'] ?? 'Unknown Project'); ?></strong>
                    <span class="pierre-project-meta">
                        (<?php echo esc_html($project['locale_code'] ?? 'Unknown Locale'); ?>)
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p>Pierre says: No projects are being watched yet! 😢</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="pierre-admin-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-teams')); ?>" class="pierre-admin-button">
            Manage Teams 🪨
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-projects')); ?>" class="pierre-admin-button">
            Manage Projects 🪨
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-settings')); ?>" class="pierre-admin-button">
            Settings 🪨
        </a>
        <a href="<?php echo esc_url(home_url('/pierre/')); ?>" class="pierre-admin-button secondary" target="_blank">
            View Public Dashboard 🪨
        </a>
    </div>
</div>

<style>
.pierre-admin-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.pierre-status-info p {
    margin: 10px 0;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
}

.pierre-assignments-list,
.pierre-projects-list {
    max-height: 200px;
    overflow-y: auto;
}

.pierre-assignment-item,
.pierre-project-item {
    padding: 8px;
    margin: 5px 0;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #2271b1;
}

.pierre-assignment-meta,
.pierre-project-meta {
    color: #666;
    font-size: 0.9em;
}

.pierre-admin-actions {
    margin-top: 30px;
    text-align: center;
}

.pierre-admin-actions .pierre-admin-button {
    margin: 0 10px 10px 0;
}
</style>
