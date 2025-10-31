<?php
/**
 * Pierre's admin projects template - he manages translation projects! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_admin_template_data'] ?? [];
$watched_projects = $data['watched_projects'] ?? [];
$locales_labels = $data['locales_labels'] ?? [];
$all_locales = $data['locales'] ?? [];

// Get active locales for filter dropdown
$active_locales = [];
foreach ($watched_projects as $project) {
    $locale = $project['locale'] ?? ($project['locale_code'] ?? '');
    if (!empty($locale) && !in_array($locale, $active_locales, true)) {
        $active_locales[] = $locale;
    }
}
sort($active_locales);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Projects', 'wp-pierre'); ?></h1>
    <?php if ( current_user_can('pierre_manage_projects') ) : ?>
        <a href="#" class="page-title-action" id="pierre-add-project-toggle"><?php echo esc_html__('Add Project', 'wp-pierre'); ?></a>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php 
    // Check if any locales are active - workflow "locale d'abord"
    if (empty($active_locales)): 
    ?>
        <div class="notice notice-warning">
            <p><strong><?php echo esc_html__('No locales configured yet.', 'wp-pierre'); ?></strong></p>
            <p><?php echo esc_html__('Add locales from Settings > Locales Discovery, then return here to add projects.', 'wp-pierre'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-settings#discovery')); ?>" class="button button-primary">
                    <?php echo esc_html__('Open Locales Discovery →', 'wp-pierre'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Add Project Form (initially hidden, toggled) -->
    <?php if ( current_user_can('pierre_manage_projects') ) : ?>
    <div id="pierre-add-project-form-container" class="pierre-card" style="display:none;margin-bottom:20px;">
        <h2><?php echo esc_html__('Add Project to Watch', 'wp-pierre'); ?></h2>
        <form id="pierre-add-project-form" class="pierre-form-compact">
            <div class="pierre-form-group">
                <label for="project_type"><?php echo esc_html__('Project Type:', 'wp-pierre'); ?></label>
                <select id="project_type" name="project_type" class="wp-core-ui" required>
                    <option value="meta"><?php echo esc_html__('Core/Meta', 'wp-pierre'); ?></option>
                    <option value="plugin"><?php echo esc_html__('Plugin', 'wp-pierre'); ?></option>
                    <option value="theme"><?php echo esc_html__('Theme', 'wp-pierre'); ?></option>
                    <option value="app"><?php echo esc_html__('App', 'wp-pierre'); ?></option>
                </select>
            </div>
            <div class="pierre-form-group">
                 <label for="project_slug"><?php echo esc_html__('Project Slug:', 'wp-pierre'); ?></label>
                 <input type="text" class="regular-text" id="project_slug" name="project_slug" placeholder="<?php echo esc_attr__('e.g., wp, woocommerce', 'wp-pierre'); ?>" required>
            </div>
            <div class="pierre-form-group">
                 <label for="locale_code_add"><?php echo esc_html__('Locale:', 'wp-pierre'); ?></label>
                <select id="locale_code_add" name="locale_code" class="wp-core-ui" required>
                    <option value=""><?php echo esc_html__('— Select locale —', 'wp-pierre'); ?></option>
                    <?php foreach ($active_locales as $loc): 
                        $label = $locales_labels[$loc] ?? $loc;
                    ?>
                        <option value="<?php echo esc_attr($loc); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($active_locales)): ?>
                     <p class="description" style="color:#d63638;">
                         <?php echo esc_html__('No active locales. Add locales first.', 'wp-pierre'); ?>
                     </p>
                <?php endif; ?>
            </div>
            <div class="pierre-form-actions">
                <button type="submit" class="button button-primary" <?php echo empty($active_locales) ? 'disabled' : ''; ?>>
                    <?php echo esc_html__('Add to Surveillance', 'wp-pierre'); ?>
                </button>
                <button type="button" class="button" id="pierre-cancel-add-project"><?php echo esc_html__('Cancel', 'wp-pierre'); ?></button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Surveillance Controls -->
    <div class="pierre-card">
        <h2><?php echo esc_html__('Surveillance Status', 'wp-pierre'); ?></h2>
        <?php if (isset($data['surveillance_status'])): ?>
        <div class="pierre-surveillance-status">
            <p><strong><?php echo esc_html__('Status:', 'wp-pierre'); ?></strong> <?php echo $data['surveillance_status']['active'] ? esc_html__('Active', 'wp-pierre') : esc_html__('Inactive', 'wp-pierre'); ?></p>
            <p><strong><?php echo esc_html__('Message:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['surveillance_status']['message'] ?? esc_html__('No status message', 'wp-pierre')); ?></p>
            <ul style="margin:8px 0 0 18px;list-style:disc;">
                <li><?php echo !empty($active_locales) ? '✅' : '⚠️'; ?> <?php echo esc_html__('Active locales', 'wp-pierre'); ?>: <?php echo esc_html(count($active_locales)); ?></li>
                <li><?php $wp_count = count($watched_projects); echo $wp_count>0 ? '✅' : '⚠️'; ?> <?php echo esc_html__('Watched projects', 'wp-pierre'); ?>: <?php echo esc_html($wp_count); ?></li>
                <?php if (!empty($data['cron_status'])): ?>
                <li>⏰ <?php echo esc_html__('Next run', 'wp-pierre'); ?>: <?php echo esc_html($data['cron_status']['next_run'] ?? __('N/A','wp-pierre')); ?></li>
                <?php endif; ?>
                <li>ℹ️ <?php echo esc_html__('Notifications are managed per locale (see Locale → Notifications).', 'wp-pierre'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-locales')); ?>"><?php echo esc_html__('Manage locales', 'wp-pierre'); ?></a></li>
            </ul>
        </div>
        <?php endif; ?>
        <div class="pierre-surveillance-controls">
            <?php if (!empty($data['surveillance_status']['active'])): ?>
                <button class="button" id="pierre-stop-surveillance">
                    <?php echo esc_html__('Stop Surveillance', 'wp-pierre'); ?>
                </button>
            <?php else: ?>
                <button class="button button-primary" id="pierre-start-surveillance" disabled="disabled" aria-disabled="true">
                    <?php echo esc_html__('Start Surveillance', 'wp-pierre'); ?>
                </button>
            <?php endif; ?>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pierre-locales')); ?>">
                <?php echo esc_html__('Go to Locales to test per-locale', 'wp-pierre'); ?>
            </a>
        </div>
        <input type="hidden" id="pierre-ajax-url" value="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>" />
        <input type="hidden" id="pierre-ajax-nonce" value="<?php echo esc_attr( wp_create_nonce('pierre_ajax') ); ?>" />
    </div>

    <!-- Projects Table -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <label for="filter-locale" class="screen-reader-text"><?php echo esc_html__('Filter by locale', 'wp-pierre'); ?></label>
            <select id="filter-locale" class="wp-core-ui">
                <option value=""><?php echo esc_html__('All Locales', 'wp-pierre'); ?></option>
                <?php foreach ($active_locales as $loc): 
                    $label = $locales_labels[$loc] ?? $loc;
                ?>
                    <option value="<?php echo esc_attr($loc); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="filter-type" class="screen-reader-text"><?php echo esc_html__('Filter by type', 'wp-pierre'); ?></label>
            <select id="filter-type" class="wp-core-ui" style="margin-left:6px;">
                <option value=""><?php echo esc_html__('All Types', 'wp-pierre'); ?></option>
                <option value="plugin"><?php echo esc_html__('Plugin', 'wp-pierre'); ?></option>
                <option value="theme"><?php echo esc_html__('Theme', 'wp-pierre'); ?></option>
                <option value="meta"><?php echo esc_html__('Core/Meta', 'wp-pierre'); ?></option>
                <option value="app"><?php echo esc_html__('App', 'wp-pierre'); ?></option>
            </select>
        </div>
        <div class="alignright">
             <label for="search-projects" class="screen-reader-text"><?php echo esc_html__('Search projects', 'wp-pierre'); ?></label>
            <input type="search" id="search-projects" class="search-input" placeholder="<?php echo esc_attr__('Search projects...', 'wp-pierre'); ?>" />
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <td scope="col" class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-projects" />
                </td>
                <th scope="col" class="manage-column column-project"><?php echo esc_html__('Project', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-locale"><?php echo esc_html__('Locale', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-type"><?php echo esc_html__('Type', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-status"><?php echo esc_html__('Status', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-last-check"><?php echo esc_html__('Last Check', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-next-check"><?php echo esc_html__('Next Check', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Actions', 'wp-pierre'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (!empty($watched_projects)): ?>
                <?php foreach ($watched_projects as $project): 
                    $slug = $project['slug'] ?? ($project['project_slug'] ?? '');
                    $locale = $project['locale'] ?? ($project['locale_code'] ?? '');
                    $type = $project['type'] ?? 'meta';
                    $locale_label = $locales_labels[$locale] ?? $locale;
                ?>
                <tr data-project="<?php echo esc_attr($slug); ?>" data-locale="<?php echo esc_attr($locale); ?>" data-type="<?php echo esc_attr($type); ?>">
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="project[]" value="<?php echo esc_attr($slug . '_' . $locale); ?>" />
                    </th>
                    <td class="column-project column-primary">
                        <strong><?php echo esc_html($slug); ?></strong>
                        <div class="row-actions">
                            <span class="view">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-locale-view&locale=' . esc_attr($locale))); ?>">
                                    <?php echo esc_html__('View locale', 'wp-pierre'); ?>
                                </a>
                            </span>
                            <?php if ( current_user_can('pierre_manage_projects') ) : ?>
                                <span class="delete">
                                    <a href="#" class="pierre-remove-project-link" 
                                        data-project="<?php echo esc_attr($slug); ?>"
                                        data-locale="<?php echo esc_attr($locale); ?>">
                                        <?php echo esc_html__('Remove', 'wp-pierre'); ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="column-locale">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-locale-view&locale=' . esc_attr($locale))); ?>">
                            <?php echo esc_html($locale_label); ?>
                        </a>
                    </td>
                    <td class="column-type">
                        <?php echo esc_html(ucfirst($type)); ?>
                    </td>
                    <td class="column-status">
                        <span class="status-active"><?php echo esc_html__('Active', 'wp-pierre'); ?></span>
                    </td>
                    <td class="column-last-check">
                        <?php 
                        $last_checked = $project['last_checked'] ?? null;
                        if ($last_checked) {
                            /* translators: %s: human time diff (e.g., "5 mins") */
                            printf(esc_html__('%s ago', 'wp-pierre'), esc_html(human_time_diff($last_checked, current_time('timestamp'))));
                        } else {
                            echo esc_html__('Never', 'wp-pierre');
                        }
                        ?>
                    </td>
                    <td class="column-next-check">
                        <?php 
                        $next_check = $project['next_check'] ?? null;
                        if ($next_check) {
                            /* translators: %s: human time diff (e.g., "in 5 mins") */
                            printf(esc_html__('%s from now', 'wp-pierre'), esc_html(human_time_diff(current_time('timestamp'), $next_check)));
                        } else {
                            echo esc_html__('N/A', 'wp-pierre');
                        }
                        ?>
                    </td>
                    <td class="column-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-locale-view&locale=' . esc_attr($locale))); ?>" class="button button-small">
                            <?php echo esc_html__('Manage Locale', 'wp-pierre'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="no-items">
                    <td class="colspanchange" colspan="7">
                        <?php echo esc_html__('No projects are being watched yet.', 'wp-pierre'); ?>
                        <?php if (!empty($active_locales)): ?>
                            <a href="#" id="pierre-add-project-link"><?php echo esc_html__('Add your first project', 'wp-pierre'); ?></a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-locales')); ?>"><?php echo esc_html__('Add a locale first', 'wp-pierre'); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td scope="col" class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-projects-2" />
                </td>
                <th scope="col" class="manage-column column-project"><?php echo esc_html__('Project', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-locale"><?php echo esc_html__('Locale', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-type"><?php echo esc_html__('Type', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-status"><?php echo esc_html__('Status', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-last-check"><?php echo esc_html__('Last Check', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-next-check"><?php echo esc_html__('Next Check', 'wp-pierre'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Actions', 'wp-pierre'); ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <div class="alignright">
            <span class="displaying-num"><?php echo esc_html(count($watched_projects)); ?> <?php echo esc_html__('project(s)', 'wp-pierre'); ?></span>
        </div>
    </div>
</div>

<script>
(function(){
    // Toggle add project form
    const addToggle = document.getElementById('pierre-add-project-toggle');
    const addForm = document.getElementById('pierre-add-project-form-container');
    const cancelBtn = document.getElementById('pierre-cancel-add-project');
    const addLink = document.getElementById('pierre-add-project-link');
    
    if (addToggle && addForm) {
        addToggle.addEventListener('click', function(e) {
            e.preventDefault();
            addForm.style.display = addForm.style.display === 'none' ? 'block' : 'none';
        });
    }
    if (cancelBtn && addForm) {
        cancelBtn.addEventListener('click', function() {
            addForm.style.display = 'none';
        });
    }
    if (addLink && addForm) {
        addLink.addEventListener('click', function(e) {
            e.preventDefault();
            addForm.style.display = 'block';
            addForm.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Filters
    const filterLocale = document.getElementById('filter-locale');
    const filterType = document.getElementById('filter-type');
    const searchInput = document.getElementById('search-projects');
    const tbody = document.getElementById('the-list');
    
    function applyFilters() {
        if (!tbody) return;
        const localeFilter = filterLocale?.value || '';
        const typeFilter = filterType?.value || '';
        const searchQuery = searchInput?.value.toLowerCase().trim() || '';
        
        const rows = tbody.querySelectorAll('tr[data-project]');
        rows.forEach(function(row) {
            if (row.classList.contains('no-items')) return;
            const rowLocale = row.getAttribute('data-locale') || '';
            const rowType = row.getAttribute('data-type') || '';
            const rowText = row.textContent.toLowerCase();
            
            const localeMatch = !localeFilter || rowLocale === localeFilter;
            const typeMatch = !typeFilter || rowType === typeFilter;
            const searchMatch = !searchQuery || rowText.includes(searchQuery);
            
            row.style.display = (localeMatch && typeMatch && searchMatch) ? '' : 'none';
        });
    }
    
    filterLocale?.addEventListener('change', applyFilters);
    filterType?.addEventListener('change', applyFilters);
    searchInput?.addEventListener('input', applyFilters);

    // Add project form submission
    const addProjectForm = document.getElementById('pierre-add-project-form');
    if (addProjectForm) {
        addProjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = '<?php echo esc_js(__('Adding...', 'wp-pierre')); ?>';
            
            const formData = new FormData(this);
            formData.append('action', 'pierre_add_project');
            formData.append('nonce', document.getElementById('pierre-ajax-nonce')?.value || '');
            
            fetch(document.getElementById('pierre-ajax-url')?.value || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? '<?php echo esc_js(__('Project added!', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Failed to add project.', 'wp-pierre')); ?>');
                    if (json && json.success) {
                        if (window.pierreNotice) { window.pierreNotice('success', msg); setTimeout(()=>location.reload(), 600); }
                        else { alert(msg); location.reload(); }
                    } else {
                        if (window.pierreNotice) { window.pierreNotice('error', msg); }
                        else { alert(msg); }
                    }
                })
                .catch(() => {
                    const m = '<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>';
                    if (window.pierreNotice) { window.pierreNotice('error', m); } else { alert(m); }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = original;
                });
        });
    }

    // Remove project links
    document.querySelectorAll('.pierre-remove-project-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('<?php echo esc_js(__('Remove this project from surveillance?', 'wp-pierre')); ?>')) {
                return;
            }
            const formData = new FormData();
            formData.append('action', 'pierre_remove_project');
            formData.append('nonce', document.getElementById('pierre-ajax-nonce')?.value || '');
            formData.append('project_slug', this.getAttribute('data-project'));
            formData.append('locale_code', this.getAttribute('data-locale'));
            
            fetch(document.getElementById('pierre-ajax-url')?.value || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    if (json && json.success) {
                        if (window.pierreNotice) { window.pierreNotice('success', '<?php echo esc_js(__('Project removed.', 'wp-pierre')); ?>'); setTimeout(()=>location.reload(), 600); }
                        else { location.reload(); }
                    } else {
                        const m = '<?php echo esc_js(__('Failed to remove project.', 'wp-pierre')); ?>';
                        if (window.pierreNotice) { window.pierreNotice('error', m); } else { alert(m); }
                    }
                })
                .catch(() => {
                    const m = '<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>';
                    if (window.pierreNotice) { window.pierreNotice('error', m); } else { alert(m); }
                });
        });
    });
})();
</script>

