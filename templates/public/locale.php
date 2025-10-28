<?php
/**
 * Pierre's public locale template - he shows locale-specific projects! 🪨
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
    <title><?php 
    // translators: %s is the locale name
    echo esc_html(sprintf(__('Pierre Dashboard - %s', 'wp-pierre'), $data['locale'] ?? 'Unknown')); 
    ?> 🪨</title>
    <?php wp_head(); ?>
</head>
<body class="pierre-public-dashboard">
    <div class="pierre-container">
        <div class="pierre-header">
            <h1><?php 
            // translators: %s is the locale name
            echo esc_html(sprintf(__('Pierre Dashboard - %s', 'wp-pierre'), $data['locale_name'] ?? $data['locale'] ?? 'Unknown')); 
            ?> 🪨</h1>
            <p><?php 
            // translators: %s is the locale name
            echo esc_html(sprintf(__('Translation Projects for %s', 'wp-pierre'), $data['locale_name'] ?? $data['locale'] ?? 'Unknown')); 
            ?></p>
        </div>

        <div class="pierre-breadcrumb">
            <a href="<?php echo esc_url(home_url('/pierre/')); ?>"><?php echo esc_html__('Pierre Dashboard', 'wp-pierre'); ?></a> 
            &gt; 
            <?php echo esc_html($data['locale_name'] ?? $data['locale'] ?? 'Unknown'); ?>
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
            <strong>Pierre says:</strong> <?php 
            // translators: %s is the locale name
            echo esc_html(sprintf(__('Here are all the translation projects for %s!', 'wp-pierre'), $data['locale_name'] ?? $data['locale'] ?? 'this locale')); 
            ?> 🪨
        </div>

        <?php if (isset($data['projects']) && !empty($data['projects'])): ?>
        <div class="pierre-projects">
            <h2><?php 
            // translators: %s is the locale name
            echo esc_html(sprintf(__('Projects for %s', 'wp-pierre'), $data['locale_name'] ?? $data['locale'] ?? 'Unknown')); 
            ?> 🪨</h2>
            <?php foreach ($data['projects'] as $project): ?>
            <div class="pierre-project-card" 
                 data-project="<?php echo esc_attr($project['project_slug'] ?? ''); ?>"
                 data-locale="<?php echo esc_attr($data['locale'] ?? ''); ?>">
                <div class="pierre-project-title">
                    <?php echo esc_html($project['project_name'] ?? $project['project_slug'] ?? 'Unknown Project'); ?>
                </div>
                <div class="pierre-project-meta">
                    Project: <?php echo esc_html($project['project_slug'] ?? 'Unknown'); ?> | 
                    Locale: <?php echo esc_html($data['locale'] ?? 'Unknown'); ?>
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
                    <div class="pierre-project-stat">
                        <div class="pierre-project-stat-value"><?php echo esc_html($project['waiting'] ?? '0'); ?></div>
                        <div class="pierre-project-stat-label">Waiting</div>
                    </div>
                </div>
                <div class="pierre-project-actions">
                    <button class="pierre-button small" 
                            onclick="window.location.href='<?php echo esc_url(home_url('/pierre/' . $data['locale'] . '/' . ($project['project_slug'] ?? '') . '/')); ?>'">
                        View Details 🪨
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="pierre-message warning">
            <strong>Pierre says:</strong> No projects found for 
            <?php echo esc_html($data['locale_name'] ?? $data['locale'] ?? 'this locale'); ?>! 😢
        </div>
        <?php endif; ?>

        <div class="pierre-actions">
            <a href="<?php echo esc_url(home_url('/pierre/')); ?>" class="pierre-button">
                <?php echo esc_html__('Back to Dashboard', 'wp-pierre'); ?> 🪨
            </a>
            <button class="pierre-button" id="pierre-refresh-stats">
                Refresh Statistics 🪨
            </button>
            <?php if (current_user_can('wpupdates_view_dashboard')): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-dashboard')); ?>" class="pierre-button">
                <?php echo esc_html__('Admin Dashboard', 'wp-pierre'); ?> 🪨
            </a>
            <?php endif; ?>
        </div>

        <div class="pierre-footer">
            <p>
                <strong>Pierre</strong> - WordPress Translation Monitor 🪨 | 
                <a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_html__('Back to Site', 'wp-pierre'); ?></a>
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
    <?php wp_footer(); ?>
</body>
</html>
