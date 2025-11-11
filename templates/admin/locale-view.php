<?php
/**
 * Pierre's admin locale view template - he manages a specific locale! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_admin_template_data'] ?? [];
$locale_code = $data['locale_code'] ?? '';
$locale_label = $data['locale_label'] ?? $locale_code;
$projects = $data['projects'] ?? [];
$raw_slack_webhook = $data['slack_webhook'] ?? '';
$slack_webhook = !empty($raw_slack_webhook) ? pierre_decrypt_webhook($raw_slack_webhook) : '';
$stats = $data['stats'] ?? [];
$current_tab = sanitize_key(wp_unslash($_GET['tab'] ?? 'overview'));
$base_url = admin_url('admin.php?page=pierre-locale-view&locale=' . esc_attr($locale_code));
?>

<div class="wrap">
    <h1>
        <?php echo esc_html($locale_label); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-locales')); ?>" class="page-title-action"><?php echo esc_html__('← Back to Locales', 'wp-pierre'); ?></a>
    </h1>

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo esc_url(add_query_arg('tab', 'overview', $base_url)); ?>" class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__('Overview', 'wp-pierre'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'projects', $base_url)); ?>" class="nav-tab <?php echo $current_tab === 'projects' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__('Projects', 'wp-pierre'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'notifications', $base_url)); ?>" class="nav-tab <?php echo $current_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__('Notifications', 'wp-pierre'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'team', $base_url)); ?>" class="nav-tab <?php echo $current_tab === 'team' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__('Team', 'wp-pierre'); ?>
        </a>
    </nav>

    <?php if ($current_tab === 'overview'): ?>
        <div class="pierre-card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Statistics', 'wp-pierre'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Projects Watched', 'wp-pierre'); ?></th>
                    <td><?php echo esc_html($stats['projects_count'] ?? 0); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Last Check', 'wp-pierre'); ?></th>
                    <td><?php echo esc_html($stats['last_check'] ?? __('Never', 'wp-pierre')); ?></td>
                </tr>
            </table>
        </div>

        <div class="pierre-card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Quick Actions', 'wp-pierre'); ?></h2>
            <p>
                <a href="<?php echo esc_url(add_query_arg('tab', 'projects', $base_url)); ?>" class="button button-primary"><?php echo esc_html__('Add Project', 'wp-pierre'); ?></a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'notifications', $base_url)); ?>" class="button"><?php echo esc_html__('Configure Notifications', 'wp-pierre'); ?></a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'team', $base_url)); ?>" class="button"><?php echo esc_html__('Manage Team', 'wp-pierre'); ?></a>
            </p>
        </div>

    <?php elseif ($current_tab === 'projects'): ?>
        <div class="pierre-card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Add Project to Watch', 'wp-pierre'); ?></h2>
            <form id="pierre-add-project-locale-form" class="pierre-form-compact">
                <?php wp_nonce_field('wp_pierre_action'); ?>
                <input type="hidden" name="locale_code" value="<?php echo esc_attr($locale_code); ?>" />
                <div class="pierre-form-group">
                    <label for="project_type"><?php echo esc_html__('Project Type:', 'wp-pierre'); ?></label>
                    <select id="project_type" name="project_type" class="wp-core-ui" required>
                        <option value="plugin"><?php echo esc_html__('Plugin', 'wp-pierre'); ?></option>
                        <option value="theme"><?php echo esc_html__('Theme', 'wp-pierre'); ?></option>
                        <option value="meta" selected><?php echo esc_html__('Core/Meta', 'wp-pierre'); ?></option>
                        <option value="app"><?php echo esc_html__('App', 'wp-pierre'); ?></option>
                    </select>
                </div>
                <div class="pierre-form-group">
                    <label for="project_slug"><?php echo esc_html__('Project Slug:', 'wp-pierre'); ?></label>
                    <input type="text" id="project_slug" name="project_slug" class="regular-text" placeholder="e.g., wp, woocommerce" required />
                </div>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Add to Surveillance', 'wp-pierre'); ?></button>
            </form>
        </div>

        <div class="pierre-card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Watched Projects', 'wp-pierre'); ?></h2>
            <?php if (!empty($projects)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php echo esc_html__('Project', 'wp-pierre'); ?></th>
                            <th scope="col"><?php echo esc_html__('Type', 'wp-pierre'); ?></th>
                            <th scope="col"><?php echo esc_html__('Last Check', 'wp-pierre'); ?></th>
                            <th scope="col"><?php echo esc_html__('Next Check', 'wp-pierre'); ?></th>
                            <th scope="col"><?php echo esc_html__('Actions', 'wp-pierre'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): 
                            $slug = $project['slug'] ?? ($project['project_slug'] ?? '');
                        ?>
                        <tr>
                            <td><?php echo esc_html($slug); ?></td>
                            <td><?php echo esc_html($project['type'] ?? __('Unknown', 'wp-pierre')); ?></td>
                            <td><?php echo !empty($project['last_checked']) ? esc_html(human_time_diff($project['last_checked'], current_time('timestamp')) . ' ago') : esc_html__('Never', 'wp-pierre'); ?></td>
                            <td><?php $next_check = $project['next_check'] ?? null; echo $next_check ? esc_html(human_time_diff(current_time('timestamp'), $next_check) . ' from now') : esc_html__('N/A', 'wp-pierre'); ?></td>
                            <td>
                                <button class="button button-small pierre-remove-project" 
                                    data-project="<?php echo esc_attr($slug); ?>"
                                    data-locale="<?php echo esc_attr($locale_code); ?>">
                                    <?php echo esc_html__('Remove', 'wp-pierre'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php echo esc_html__('No projects are being watched for this locale yet.', 'wp-pierre'); ?></p>
            <?php endif; ?>
        </div>

    <?php elseif ($current_tab === 'notifications'): ?>
        <div class="pierre-card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Locale Slack WebHook', 'wp-pierre'); ?></h2>
            <?php 
            $lw = $data['locale_webhook'] ?? []; 
            $lw_types = $lw['types'] ?? ['new_strings','completion_update','needs_attention','milestone']; 
            $lwd = $lw['digest'] ?? [];
            // Decrypt locale webhook URL if present
            $raw_lw_webhook = $lw['webhook_url'] ?? '';
            $lw_webhook = !empty($raw_lw_webhook) ? pierre_decrypt_webhook($raw_lw_webhook) : '';
            $display_webhook = !empty($lw_webhook) ? $lw_webhook : $slack_webhook;
            ?>
            <form id="pierre-locale-webhook-form" class="pierre-form-compact">
                <input type="hidden" name="locale_code" value="<?php echo esc_attr($locale_code); ?>" />
                <div class="pierre-form-group">
                    <label for="locale_webhook_url"><?php echo esc_html__('Webhook URL (this locale)', 'wp-pierre'); ?></label>
                    <input type="url" id="locale_webhook_url" name="locale_webhook_url" class="regular-text" 
                        value="<?php echo esc_attr($display_webhook); ?>"
                        placeholder="https://hooks.slack.com/services/..." />
                </div>
                <div class="pierre-form-group">
                    <label for="locale_webhook_enabled">
                        <input type="checkbox" id="locale_webhook_enabled" name="locale_webhook_enabled" <?php checked(!empty($lw['enabled'] ?? true)); ?> />
                        <?php echo esc_html__('Enable', 'wp-pierre'); ?>
                    </label>
                </div>
                <fieldset class="pierre-form-group pierre-fieldset">
                    <legend><?php echo esc_html__('Types', 'wp-pierre'); ?></legend>
                    <label><input type="checkbox" name="locale_webhook_types[]" value="new_strings" <?php checked(in_array('new_strings',$lw_types,true)); ?> /> new_strings</label>
                    <label><input type="checkbox" name="locale_webhook_types[]" value="completion_update" <?php checked(in_array('completion_update',$lw_types,true)); ?> /> completion_update</label>
                    <label><input type="checkbox" name="locale_webhook_types[]" value="needs_attention" <?php checked(in_array('needs_attention',$lw_types,true)); ?> /> needs_attention</label>
                    <label><input type="checkbox" name="locale_webhook_types[]" value="milestone" <?php checked(in_array('milestone',$lw_types,true)); ?> /> milestone</label>
                </fieldset>
                <fieldset class="pierre-form-group pierre-fieldset">
                    <legend><?php echo esc_html__('Thresholds & Digest', 'wp-pierre'); ?></legend>
                    <p><label><?php echo esc_html__('New strings threshold', 'wp-pierre'); ?>
                        <input type="number" name="locale_webhook_threshold" min="0" value="<?php echo esc_attr($lw['threshold'] ?? ''); ?>" placeholder="—" />
                    </label></p>
                    <p><label><?php echo esc_html__('Milestones (comma-separated)', 'wp-pierre'); ?>
                        <input type="text" name="locale_webhook_milestones" value="<?php echo esc_attr(isset($lw['milestones']) ? implode(',', (array)$lw['milestones']) : ''); ?>" placeholder="— (uses global defaults)" />
                    </label></p>
                    <?php $lwmode = $lw['mode'] ?? ''; ?>
                    <p><label><?php echo esc_html__('Mode', 'wp-pierre'); ?>
                        <select name="locale_webhook_mode">
                            <option value="">—</option>
                            <option value="immediate" <?php selected($lwmode,'immediate'); ?>>immediate</option>
                            <option value="digest" <?php selected($lwmode,'digest'); ?>>digest</option>
                        </select>
                    </label></p>
                    <?php $lwdt = $lwd['type'] ?? ''; ?>
                    <p><label><?php echo esc_html__('Digest Type', 'wp-pierre'); ?>
                        <select name="locale_webhook_digest_type">
                            <option value="">—</option>
                            <option value="interval" <?php selected($lwdt,'interval'); ?>>interval</option>
                            <option value="fixed_time" <?php selected($lwdt,'fixed_time'); ?>>fixed_time</option>
                        </select>
                    </label></p>
                    <p><label><?php echo esc_html__('Interval (minutes)', 'wp-pierre'); ?>
                        <input type="number" min="15" name="locale_webhook_digest_interval_minutes" value="<?php echo esc_attr($lwd['interval_minutes'] ?? ''); ?>" placeholder="—" />
                    </label></p>
                    <p><label><?php echo esc_html__('Fixed time (HH:MM)', 'wp-pierre'); ?>
                        <input type="time" name="locale_webhook_digest_fixed_time" value="<?php echo esc_attr($lwd['fixed_time'] ?? ''); ?>" />
                    </label></p>
                </fieldset>
                <div class="pierre-form-actions">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Save Webhook', 'wp-pierre'); ?></button>
                </div>
            </form>
            <p class="description"><?php echo esc_html__('Leave empty values to inherit global defaults.', 'wp-pierre'); ?></p>
        </div>

    <?php elseif ($current_tab === 'team'): ?>
        <div class="pierre-card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Team Members', 'wp-pierre'); ?></h2>
            <p><?php echo esc_html__('Team management for this locale. Locale Managers can assign users to this locale.', 'wp-pierre'); ?></p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('User', 'wp-pierre'); ?></th>
                        <th scope="col"><?php echo esc_html__('Role', 'wp-pierre'); ?></th>
                        <th scope="col"><?php echo esc_html__('Projects', 'wp-pierre'); ?></th>
                        <th scope="col"><?php echo esc_html__('Actions', 'wp-pierre'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="4"><?php echo esc_html__('No team members assigned yet.', 'wp-pierre'); ?></td>
                    </tr>
                </tbody>
            </table>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-teams')); ?>" class="button"><?php echo esc_html__('Assign Users from Teams', 'wp-pierre'); ?></a>
            </p>
        </div>

        <?php if (current_user_can('manage_options')): ?>
        <div class="pierre-card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Locale Managers', 'wp-pierre'); ?></h2>
            <form id="pierre-locale-managers-form" class="pierre-form-compact">
                <input type="hidden" name="locale_code" value="<?php echo esc_attr($locale_code); ?>" />
                <p class="description">
                    <?php echo esc_html__('Only site administrators can add or remove Locale Managers for this locale.', 'wp-pierre'); ?>
                    <br />
                    <?php echo esc_html__('Permissions model (summary): Admin only for managers; then Admin + Locale Manager manage GTE and below; Admin + Locale Manager + GTE manage PTE.', 'wp-pierre'); ?>
                </p>
                <div style="max-height:300px;overflow:auto;border:1px solid #ddd;padding:8px;">
                    <?php foreach (($data['all_users'] ?? []) as $user): 
                        $is_manager = in_array($user->ID, $data['locale_managers'] ?? [], true);
                    ?>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="checkbox" name="user_ids[]" value="<?php echo esc_attr($user->ID); ?>" <?php checked($is_manager); ?> />
                            <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="pierre-form-actions" style="margin-top:10px;">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Save Managers', 'wp-pierre'); ?></button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="pierre-card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Locale Managers', 'wp-pierre'); ?></h2>
            <?php 
                $all_users = $data['all_users'] ?? [];
                $managers = $data['locale_managers'] ?? [];
                $manager_names = [];
                if (!empty($all_users) && !empty($managers)) {
                    $by_id = [];
                    foreach ($all_users as $u) { $by_id[$u->ID] = $u; }
                    foreach ($managers as $uid) {
                        if (isset($by_id[$uid])) { $manager_names[] = $by_id[$uid]->display_name . ' (' . $by_id[$uid]->user_login . ')'; }
                    }
                }
            ?>
            <?php if (!empty($manager_names)): ?>
                <ul>
                    <?php foreach ($manager_names as $name): ?>
                        <li><?php echo esc_html($name); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="description"><?php echo esc_html__('No Locale Managers assigned yet.', 'wp-pierre'); ?></p>
            <?php endif; ?>
            <p class="description" style="margin-top:8px;">
                <?php echo esc_html__('Permissions model: Locale Managers can help manage this locale (GTE/PTE flow). Contact a site administrator to modify managers.', 'wp-pierre'); ?>
            </p>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
(function(){
    // Add project form
    const addProjectForm = document.getElementById('pierre-add-project-locale-form');
    if (addProjectForm) {
        addProjectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = '<?php echo esc_js(__('Adding...', 'wp-pierre')); ?>';
            
            const formData = new FormData(this);
            formData.append('action', 'pierre_add_project');
            formData.append('nonce', window.pierreAdminL10n?.nonce || document.querySelector('input[type="hidden"][id*="nonce"]')?.value || '');
            
            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? '<?php echo esc_js(__('Project added!', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Failed to add project.', 'wp-pierre')); ?>');
                    alert(msg);
                    if (json && json.success) {
                        location.reload();
                    }
                })
                .catch(() => {
                    alert('<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = original;
                });
        });
    }

    // Remove project buttons
    document.querySelectorAll('.pierre-remove-project').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('<?php echo esc_js(__('Remove this project from surveillance?', 'wp-pierre')); ?>')) {
                return;
            }
            const formData = new FormData();
            formData.append('action', 'pierre_remove_project');
            formData.append('nonce', window.pierreAdminL10n?.nonce || document.querySelector('input[type="hidden"][id*="nonce"]')?.value || '');
            formData.append('project_slug', this.getAttribute('data-project'));
            formData.append('locale_code', this.getAttribute('data-locale'));
            
            this.disabled = true;
            const original = this.textContent;
            this.textContent = '<?php echo esc_js(__('Removing...', 'wp-pierre')); ?>';
            
            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    if (json && json.success) {
                        location.reload();
                    } else {
                        alert('<?php echo esc_js(__('Failed to remove project.', 'wp-pierre')); ?>');
                        this.disabled = false;
                        this.textContent = original;
                    }
                })
                .catch(() => {
                    alert('<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>');
                    this.disabled = false;
                    this.textContent = original;
                });
        });
    });

    // Locale Slack form
    const slackForm = document.getElementById('pierre-locale-slack-form');
    const testBtn = document.getElementById('pierre-test-locale-slack');
    
    if (slackForm) {
        slackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = '<?php echo esc_js(__('Saving...', 'wp-pierre')); ?>';
            
            const formData = new FormData(this);
            formData.append('action', 'pierre_save_locale_slack');
            formData.append('nonce', window.pierreAdminL10n?.nonce || '');
            
            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? '<?php echo esc_js(__('Saved!', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Failed to save.', 'wp-pierre')); ?>');
                    alert(msg);
                })
                .catch(() => {
                    alert('<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = original;
                });
        });
    }

    if (testBtn && slackForm) {
        testBtn.addEventListener('click', function(e) {
            e.preventDefault();
            this.disabled = true;
            const original = this.textContent;
            this.textContent = '<?php echo esc_js(__('Testing...', 'wp-pierre')); ?>';
            
            const webhookUrl = document.getElementById('slack_webhook_url')?.value || '';
            if (!webhookUrl) {
                alert('<?php echo esc_js(__('Please enter a webhook URL first.', 'wp-pierre')); ?>');
                this.disabled = false;
                this.textContent = original;
                return;
            }
            
            // Test via AJAX (we could add a dedicated endpoint, but for now reuse the global test)
            const formData = new FormData();
            formData.append('action', 'pierre_admin_test_notification');
            formData.append('nonce', window.pierreAdminL10n?.nonce || '');
            formData.append('slack_webhook_url', webhookUrl);
            
            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? '<?php echo esc_js(__('Test succeeded!', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Test failed.', 'wp-pierre')); ?>');
                    alert(msg);
                })
                .catch(() => {
                    alert('<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>');
                })
                .finally(() => {
                    this.disabled = false;
                    this.textContent = original;
                });
        });
    }

    // Locale overrides form
    const overridesForm = document.getElementById('pierre-locale-overrides-form');
    if (overridesForm) {
        overridesForm.addEventListener('submit', function(e){
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = '<?php echo esc_js(__('Saving...', 'wp-pierre')); ?>';

            const formData = new FormData(this);
            formData.append('action', 'pierre_save_locale_overrides');
            formData.append('nonce', window.pierreAdminL10n?.nonce || '');

            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? '<?php echo esc_js(__('Saved!', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Failed to save.', 'wp-pierre')); ?>');
                    alert(msg);
                })
                .catch(() => { alert('<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>'); })
                .finally(() => { btn.disabled = false; btn.textContent = original; });
        });
    }

    // Locale Managers form (admin-only)
    const managersForm = document.getElementById('pierre-locale-managers-form');
    if (managersForm) {
        managersForm.addEventListener('submit', function(e){
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = '<?php echo esc_js(__('Saving...', 'wp-pierre')); ?>';

            const formData = new FormData(this);
            formData.append('action', 'pierre_save_locale_managers');
            formData.append('nonce', window.pierreAdminL10n?.nonce || '');

            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? '<?php echo esc_js(__('Saved!', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Failed to save.', 'wp-pierre')); ?>');
                    alert(msg);
                })
                .catch(() => {
                    alert('<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = original;
                });
        });
    }
})();
</script>

