<?php
/**
 * Pierre's admin teams template - he manages translation teams! 🪨
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
        <h1>Pierre Teams 🪨</h1>
        <p>Manage Translation Teams & User Assignments</p>
    </div>

    <?php if (isset($data['stats']) && !empty($data['stats'])): ?>
    <div class="pierre-admin-stats">
        <h2>Team Statistics</h2>
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
            <h2>Available Users 🪨</h2>
            <?php if (isset($data['users']) && !empty($data['users'])): ?>
            <div class="pierre-users-list">
                <?php foreach ($data['users'] as $user): ?>
                <div class="pierre-user-item">
                    <div class="pierre-user-info">
                        <strong><?php echo esc_html($user->display_name ?? 'Unknown User'); ?></strong>
                        <span class="pierre-user-meta">(<?php echo esc_html($user->user_email ?? 'No email'); ?>)</span>
                    </div>
                    <div class="pierre-user-actions">
                        <button class="pierre-admin-button small" data-user-id="<?php echo esc_attr($user->ID); ?>">
                            Assign to Project 🪨
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p>Pierre says: No users found! 😢</p>
            <?php endif; ?>
        </div>

        <div class="pierre-admin-card">
            <h2>Pierre Roles 🪨</h2>
            <?php if (isset($data['roles']) && !empty($data['roles'])): ?>
            <div class="pierre-roles-list">
                <?php foreach ($data['roles'] as $role): ?>
                <div class="pierre-role-item">
                    <strong><?php echo esc_html($role['name'] ?? 'Unknown Role'); ?></strong>
                    <p><?php echo esc_html($role['description'] ?? 'No description'); ?></p>
                    <div class="pierre-role-capabilities">
                        <strong>Capabilities:</strong>
                        <?php if (isset($role['capabilities']) && is_array($role['capabilities'])): ?>
                            <?php echo esc_html(implode(', ', array_keys($role['capabilities']))); ?>
                        <?php else: ?>
                            No capabilities
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p>Pierre says: No roles found! 😢</p>
            <?php endif; ?>
        </div>

        <div class="pierre-admin-card">
            <h2>Pierre Capabilities 🪨</h2>
            <?php if (isset($data['capabilities']) && !empty($data['capabilities'])): ?>
            <div class="pierre-capabilities-list">
                <?php foreach ($data['capabilities'] as $capability): ?>
                <div class="pierre-capability-item">
                    <code><?php echo esc_html($capability); ?></code>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p>Pierre says: No capabilities found! 😢</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="pierre-admin-card">
        <h2>Quick Actions 🪨</h2>
        <div class="pierre-quick-actions">
            <button class="pierre-admin-button" id="pierre-refresh-teams">
                Refresh Teams Data 🪨
            </button>
            <button class="pierre-admin-button secondary" id="pierre-test-assignment">
                Test Assignment 🪨
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-dashboard')); ?>" class="pierre-admin-button">
                Back to Dashboard 🪨
            </a>
        </div>
    </div>
</div>

<style>
.pierre-users-list,
.pierre-roles-list,
.pierre-capabilities-list {
    max-height: 400px;
    overflow-y: auto;
}

.pierre-user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin: 10px 0;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #2271b1;
}

.pierre-user-info strong {
    display: block;
    margin-bottom: 5px;
}

.pierre-user-meta {
    color: #666;
    font-size: 0.9em;
}

.pierre-role-item {
    padding: 15px;
    margin: 10px 0;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #46b450;
}

.pierre-role-capabilities {
    margin-top: 10px;
    font-size: 0.9em;
    color: #666;
}

.pierre-capability-item {
    display: inline-block;
    padding: 5px 10px;
    margin: 5px;
    background: #e7f3ff;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9em;
}

.pierre-quick-actions {
    text-align: center;
    padding: 20px;
}

.pierre-quick-actions .pierre-admin-button {
    margin: 0 10px 10px 0;
}

.pierre-admin-button.small {
    padding: 8px 16px;
    font-size: 0.9em;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Pierre handles team management! 🪨
    $('#pierre-refresh-teams').on('click', function() {
        location.reload();
    });
    
    $('.pierre-user-item button').on('click', function() {
        var userId = $(this).data('user-id');
        alert('Pierre says: Assignment feature coming soon! User ID: ' + userId + ' 🪨');
    });
    
    $('#pierre-test-assignment').on('click', function() {
        alert('Pierre says: Test assignment feature coming soon! 🪨');
    });
});
</script>
