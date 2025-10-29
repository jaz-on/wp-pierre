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
    <h1><?php echo esc_html__('Pierre 🪨 Dashboard', 'wp-pierre'); ?></h1>

    <?php if (isset($data['stats']) && !empty($data['stats'])): ?>
    <div class="pierre-card">
        <h2><?php echo esc_html__('Pierre\'s Statistics', 'wp-pierre'); ?></h2>
        <div class="pierre-grid">
            <?php foreach ($data['stats'] as $stat): ?>
            <div class="pierre-stat-box">
                <div class="pierre-stat-number"><?php echo esc_html($stat['value']); ?></div>
                <div class="pierre-stat-label"><?php echo esc_html($stat['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="pierre-grid">
        <div class="pierre-card">
            <h2><?php echo esc_html__('Surveillance Status', 'wp-pierre'); ?></h2>
            <?php if (isset($data['surveillance_status'])): ?>
            <div class="pierre-status-info">
                <p><strong><?php echo esc_html__('Status:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['surveillance_status']['active'] ? esc_html__('Active', 'wp-pierre') : esc_html__('Inactive', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Message:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['surveillance_status']['message'] ?? esc_html__('No status message', 'wp-pierre')); ?></p>
                <?php if (isset($data['surveillance_status']['next_run'])): ?>
                <p><strong><?php echo esc_html__('Next Run:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['surveillance_status']['next_run']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Notification System', 'wp-pierre'); ?></h2>
            <?php if (isset($data['notifier_status'])): ?>
            <div class="pierre-status-info">
                <p><strong><?php echo esc_html__('Ready:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['notifier_status']['ready'] ? esc_html__('Yes', 'wp-pierre') : esc_html__('No', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Status:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['notifier_status']['message'] ?? esc_html__('No status message', 'wp-pierre')); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Your Assignments', 'wp-pierre'); ?></h2>
            <?php if (isset($data['user_assignments']) && !empty($data['user_assignments'])): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Project', 'wp-pierre'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Locale', 'wp-pierre'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Role', 'wp-pierre'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['user_assignments'] as $assignment): ?>
                    <tr>
                        <td><?php echo esc_html($assignment['project_name'] ?? esc_html__('Unknown Project', 'wp-pierre')); ?></td>
                        <td><?php echo esc_html($assignment['locale_code'] ?? esc_html__('Unknown Locale', 'wp-pierre')); ?></td>
                        <td><?php echo esc_html($assignment['role'] ?? esc_html__('Unknown Role', 'wp-pierre')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php echo esc_html__('Pierre says: You don\'t have any project assignments yet!', 'wp-pierre'); ?></p>
            <?php endif; ?>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Watched Projects', 'wp-pierre'); ?></h2>
            <?php if (isset($data['watched_projects']) && !empty($data['watched_projects'])): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Project', 'wp-pierre'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Locale', 'wp-pierre'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['watched_projects'] as $project): ?>
                    <tr>
                        <td><?php echo esc_html($project['project_slug'] ?? esc_html__('Unknown Project', 'wp-pierre')); ?></td>
                        <td><?php echo esc_html($project['locale_code'] ?? esc_html__('Unknown Locale', 'wp-pierre')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php echo esc_html__('Pierre says: No projects are being watched yet!', 'wp-pierre'); ?></p>
            <?php endif; ?>
        </div>
    </div>

</div>

