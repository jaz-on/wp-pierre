<?php
/**
 * Pierre's admin locales template - he manages locales! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_admin_template_data'] ?? [];
$all_locales = $data['all_locales'] ?? [];
$locales_labels = $data['locales_labels'] ?? [];
$active_locales = $data['active_locales'] ?? [];
$locale_stats = $data['locale_stats'] ?? [];
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Locales', 'wp-pierre'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-settings&tab=discovery')); ?>" class="page-title-action"><?php echo esc_html__('Discover from WP.org', 'wp-pierre'); ?></a>
    <hr class="wp-header-end">

    <?php if (empty($active_locales)): ?>
        <div class="notice notice-warning">
            <p><strong><?php echo esc_html__('No locales configured yet.', 'wp-pierre'); ?></strong></p>
            <p><?php echo esc_html__('Add locales from Settings > Locales Discovery. You can add projects after locales exist.', 'wp-pierre'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-settings&tab=discovery')); ?>" class="button button-primary"><?php echo esc_html__('Open Locales Discovery →', 'wp-pierre'); ?></a>
            </p>
        </div>
    <?php endif; ?>

    <div class="tablenav top">
        <div class="alignleft actions">
            <label for="locale-search-input" class="screen-reader-text"><?php echo esc_html__('Search locales', 'wp-pierre'); ?></label>
            <input type="search" id="locale-search-input" class="search-input" placeholder="<?php echo esc_attr__('Search locales...', 'wp-pierre'); ?>" />
        </div>
        <div class="alignright">
            <span class="displaying-num"><?php echo esc_html(count($active_locales)); ?> <?php echo esc_html__('active locale(s)', 'wp-pierre'); ?></span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <td scope="col" class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all" />
                </td>
                <th scope="col" class="manage-column column-locale"><?php echo esc_html__('Locale', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-projects"><?php echo esc_html__('Projects', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-status"><?php echo esc_html__('Status', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-last-check"><?php echo esc_html__('Last Check', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Actions', 'wp-pierre'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (!empty($active_locales)): ?>
                <?php foreach ($active_locales as $locale): 
                    $label = $locales_labels[$locale] ?? $locale;
                    $stats = $locale_stats[$locale] ?? ['projects_count' => 0, 'last_check' => __('Never', 'wp-pierre')];
                    $manage_url = admin_url('admin.php?page=pierre-locale-view&locale=' . esc_attr($locale));
                ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="locale[]" value="<?php echo esc_attr($locale); ?>" />
                    </th>
                    <td class="column-locale column-primary">
                        <strong><a href="<?php echo esc_url($manage_url); ?>"><?php echo esc_html($label); ?></a></strong>
                    </td>
                    <td class="column-projects">
                        <?php echo esc_html($stats['projects_count']); ?>
                    </td>
                    <td class="column-status">
                        <span class="status-active"><?php echo esc_html__('Active', 'wp-pierre'); ?></span>
                    </td>
                    <td class="column-last-check">
                        <?php echo esc_html($stats['last_check']); ?>
                    </td>
                    <td class="column-actions">
                        <a href="<?php echo esc_url($manage_url); ?>" class="button button-small"><?php echo esc_html__('Manage', 'wp-pierre'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="no-items">
                    <td class="colspanchange" colspan="6">
                        <?php echo esc_html__('No active locales found.', 'wp-pierre'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-settings&tab=discovery')); ?>"><?php echo esc_html__('Add your first locale from Settings', 'wp-pierre'); ?></a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td scope="col" class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-2" />
                </td>
                <th scope="col" class="manage-column column-locale"><?php echo esc_html__('Locale', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-projects"><?php echo esc_html__('Projects', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-status"><?php echo esc_html__('Status', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-last-check"><?php echo esc_html__('Last Check', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Actions', 'wp-pierre'); ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <div class="alignright">
            <span class="displaying-num"><?php echo esc_html(count($active_locales)); ?> <?php echo esc_html__('active locale(s)', 'wp-pierre'); ?></span>
        </div>
    </div>
</div>

<script>
(function(){
    const searchInput = document.getElementById('locale-search-input');
    const tbody = document.getElementById('the-list');
    if (!searchInput || !tbody) return;
    
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(function(row) {
            if (row.classList.contains('no-items')) return;
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
})();
</script>

