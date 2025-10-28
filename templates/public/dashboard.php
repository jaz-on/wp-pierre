<?php
/**
 * Pierre's public dashboard template - he shows his public interface! 🪨
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
    <title>Pierre Dashboard - WordPress Translation Monitor 🪨</title>
    <link rel="stylesheet" href="<?php echo esc_url(PIERRE_PLUGIN_URL . 'assets/css/public.css'); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="pierre-public-dashboard">
    <div class="pierre-container">
        <div class="pierre-header">
            <h1>Pierre Dashboard 🪨</h1>
            <p>WordPress Translation Monitor - Public Interface</p>
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
            <strong>Pierre says:</strong> Welcome to the public translation monitoring dashboard! 
            Here you can view translation progress and project status. 🪨
        </div>

        <?php if (isset($data['user_assignments']) && !empty($data['user_assignments'])): ?>
        <div class="pierre-projects">
            <h2>Your Translation Projects 🪨</h2>
            <?php foreach ($data['user_assignments'] as $assignment): ?>
            <div class="pierre-project-card" 
                 data-project="<?php echo esc_attr($assignment['project_slug'] ?? ''); ?>"
                 data-locale="<?php echo esc_attr($assignment['locale_code'] ?? ''); ?>">
                <div class="pierre-project-title">
                    <?php echo esc_html($assignment['project_name'] ?? 'Unknown Project'); ?>
                </div>
                <div class="pierre-project-meta">
                    Locale: <?php echo esc_html($assignment['locale_code'] ?? 'Unknown'); ?> | 
                    Role: <?php echo esc_html($assignment['role'] ?? 'Unknown'); ?>
                </div>
                <div class="pierre-project-stats">
                    <div class="pierre-project-stat">
                        <div class="pierre-project-stat-value"><?php echo esc_html($assignment['completion'] ?? '0'); ?>%</div>
                        <div class="pierre-project-stat-label">Complete</div>
                    </div>
                    <div class="pierre-project-stat">
                        <div class="pierre-project-stat-value"><?php echo esc_html($assignment['translated'] ?? '0'); ?></div>
                        <div class="pierre-project-stat-label">Translated</div>
                    </div>
                    <div class="pierre-project-stat">
                        <div class="pierre-project-stat-value"><?php echo esc_html($assignment['total'] ?? '0'); ?></div>
                        <div class="pierre-project-stat-label">Total</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="pierre-message warning">
            <strong>Pierre says:</strong> You don't have any project assignments yet! 
            Contact your administrator to get assigned to translation projects. 😢
        </div>
        <?php endif; ?>

        <?php if (isset($data['watched_projects']) && !empty($data['watched_projects'])): ?>
        <div class="pierre-projects">
            <h2>All Monitored Projects 🪨</h2>
            <?php foreach ($data['watched_projects'] as $project): ?>
            <div class="pierre-project-card" 
                 data-project="<?php echo esc_attr($project['project_slug'] ?? ''); ?>"
                 data-locale="<?php echo esc_attr($project['locale_code'] ?? ''); ?>">
                <div class="pierre-project-title">
                    <?php echo esc_html($project['project_slug'] ?? 'Unknown Project'); ?>
                </div>
                <div class="pierre-project-meta">
                    Locale: <?php echo esc_html($project['locale_code'] ?? 'Unknown'); ?>
                </div>
                <div class="pierre-project-stats">
                    <div class="pierre-project-stat">
                        <div class="pierre-project-stat-value"><?php echo esc_html($project['completion'] ?? '0'); ?>%</div>
                        <div class="pierre-project-stat-label">Complete</div>
                    </div>
                    <div class="pierre-project-stat">
                        <div class="pierre-project-stat-value"><?php echo esc_html($project['translated'] ?? '0'); ?></div>
                        <div class="pierre-project-stat-label">Translated</div>
                    </div>
                    <div class="pierre-project-stat">
                        <div class="pierre-project-stat-value"><?php echo esc_html($project['total'] ?? '0'); ?></div>
                        <div class="pierre-project-stat-label">Total</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="pierre-actions">
            <button class="pierre-button" id="pierre-refresh-stats">
                Refresh Statistics 🪨
            </button>
            <button class="pierre-button secondary" id="pierre-test-notification">
                Test Notification 🪨
            </button>
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
