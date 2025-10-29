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
    <h1>Pierre 🪨 Reports</h1>
    <p><?php echo esc_html__('Work in progress.', 'wp-pierre'); ?></p>
</div>
