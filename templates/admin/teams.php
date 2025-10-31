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
    <h1><?php echo esc_html__('Pierre 🪨 Teams', 'wp-pierre'); ?></h1>
    
    <?php /* Filter by Locale card removed for now to simplify UI */ ?>

    <?php /* Team Statistics card removed to avoid redundancy with lists below */ ?>

    <div class="pierre-card">
        <h2><?php echo esc_html__('Sources & Help', 'wp-pierre'); ?></h2>
        <p class="description">
            <?php echo esc_html__('Roles, responsibilities and current translation teams are documented in the Polyglots handbook:', 'wp-pierre'); ?>
            <a href="https://make.wordpress.org/polyglots/handbook/translating/teams/" target="_blank" rel="noopener">make.wordpress.org/polyglots/handbook/translating/teams/</a>
        </p>
    </div>

    <div class="pierre-card">
            <h2><?php echo esc_html__('Available Users', 'wp-pierre'); ?></h2>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <span class="displaying-num"><?php echo esc_html(count($data['users'] ?? [])); ?> <?php echo esc_html__('items', 'wp-pierre'); ?></span>
                </div>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Name', 'wp-pierre'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Email', 'wp-pierre'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('WP Role', 'wp-pierre'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Locales', 'wp-pierre'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Projects', 'wp-pierre'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Actions', 'wp-pierre'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['users'])): ?>
                        <?php foreach ($data['users'] as $item): 
                            $user = $item['user'];
                            $is_admin = !empty($item['is_admin']);
                            $assignments = $item['assignments'] ?? [];
                            // Build locales & projects summaries from assignments
                            $locales_summary = [];
                            $projects_summary = [];
                            if (is_array($assignments)) {
                                foreach ($assignments as $a) {
                                    $loc = $a['locale_code'] ?? '';
                                    $slug = $a['project_slug'] ?? '';
                                    if ($loc && !in_array($loc, $locales_summary, true)) { $locales_summary[] = $loc; }
                                    if ($slug) { $projects_summary[] = $slug; }
                                }
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($user->display_name ?? esc_html__('Unknown User', 'wp-pierre')); ?></strong>
                                <br />
                                <a href="<?php echo esc_url( admin_url('user-edit.php?user_id=' . absint($user->ID)) ); ?>" target="_blank" rel="noopener"><?php echo esc_html__('View WP User', 'wp-pierre'); ?></a>
                            </td>
                            <td><?php echo esc_html($user->user_email ?? ''); ?></td>
                            <td><?php echo !empty($user->roles[0]) ? esc_html($user->roles[0]) : esc_html__('—','wp-pierre'); ?></td>
                            <td><?php echo $is_admin ? esc_html__('All','wp-pierre') : (!empty($locales_summary) ? esc_html(implode(', ', $locales_summary)) : esc_html__('—','wp-pierre')); ?></td>
                            <td><?php echo !empty($projects_summary) ? esc_html(implode(', ', array_unique($projects_summary))) : esc_html__('—','wp-pierre'); ?></td>
                            <td>
                                <?php if (!$is_admin): ?>
                                    <button class="button button-small pierre-assign-user-btn" data-user-id="<?php echo esc_attr($user->ID); ?>" data-user-name="<?php echo esc_attr($user->display_name); ?>">
                                        <?php echo esc_html__('Assign to Locale/Project', 'wp-pierre'); ?>
                                    </button>
                                <?php else: ?>
                                    <span class="description"><?php echo esc_html__('No action needed', 'wp-pierre'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="no-items">
                            <td class="colspanchange" colspan="4"><?php echo esc_html__('No users found.', 'wp-pierre'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="tablenav bottom">
                <div class="alignleft actions">
                    <span class="displaying-num"><?php echo esc_html(count($data['users'] ?? [])); ?> <?php echo esc_html__('items', 'wp-pierre'); ?></span>
                </div>
            </div>
    </div>

    <!-- Modal/Inline form for user assignment -->
    <div id="pierre-assign-modal" class="pierre-modal">
        <h2><?php echo esc_html__('Assign User to Locale/Project', 'wp-pierre'); ?></h2>
        <p><strong><?php echo esc_html__('User:', 'wp-pierre'); ?></strong> <span id="pierre-assign-user-name"></span></p>
        <form id="pierre-assign-form" class="pierre-form-compact">
            <input type="hidden" id="pierre-assign-user-id" name="user_id" value="" />
            <div class="pierre-form-group">
                <label for="pierre-assign-locale"><?php echo esc_html__('Locale:', 'wp-pierre'); ?></label>
                <select id="pierre-assign-locale" name="locale_code" class="wp-core-ui" required>
                    <option value=""><?php echo esc_html__('— Select locale —', 'wp-pierre'); ?></option>
                    <?php 
                    $locales = $data['locales'] ?? [];
                    $labels = $data['locales_labels'] ?? [];
                    foreach ($locales as $loc): 
                    ?>
                        <option value="<?php echo esc_attr($loc); ?>"><?php echo esc_html($labels[$loc] ?? $loc); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pierre-form-group">
                <label for="pierre-assign-role"><?php echo esc_html__('Role:', 'wp-pierre'); ?></label>
                <select id="pierre-assign-role" name="role" class="wp-core-ui" required>
                    <option value=""><?php echo esc_html__('— Select role —', 'wp-pierre'); ?></option>
                    <?php 
                    $roles = $data['roles'] ?? [];
                    foreach ($roles as $key => $label): 
                    ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pierre-form-group">
                <label for="pierre-assign-project-type"><?php echo esc_html__('Project Type:', 'wp-pierre'); ?></label>
                <select id="pierre-assign-project-type" name="project_type" class="wp-core-ui" required>
                    <option value="plugin"><?php echo esc_html__('Plugin', 'wp-pierre'); ?></option>
                    <option value="theme"><?php echo esc_html__('Theme', 'wp-pierre'); ?></option>
                    <option value="meta"><?php echo esc_html__('Meta', 'wp-pierre'); ?></option>
                    <option value="app"><?php echo esc_html__('App', 'wp-pierre'); ?></option>
                </select>
            </div>
            <div class="pierre-form-group">
                <label for="pierre-assign-project-slug"><?php echo esc_html__('Project Slug:', 'wp-pierre'); ?></label>
                <input type="text" id="pierre-assign-project-slug" name="project_slug" class="regular-text" placeholder="e.g., wp, woocommerce" required />
                <div class="pierre-help" id="pierre-assign-projects-hint" style="display:none;">
                    <?php echo esc_html__('Projects watched for selected locale:', 'wp-pierre'); ?>
                    <ul id="pierre-assign-projects-list" style="margin-top:4px;margin-left:20px;"></ul>
                </div>
            </div>
            <div class="pierre-form-actions">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Assign', 'wp-pierre'); ?></button>
                <button type="button" class="button" id="pierre-assign-cancel"><?php echo esc_html__('Cancel', 'wp-pierre'); ?></button>
            </div>
        </form>
    </div>
    <div id="pierre-assign-overlay" class="pierre-overlay"></div>

    <script>
    (function(){
        const assignBtns = document.querySelectorAll('.pierre-assign-user-btn');
        const modal = document.getElementById('pierre-assign-modal');
        const overlay = document.getElementById('pierre-assign-overlay');
        const form = document.getElementById('pierre-assign-form');
        const cancelBtn = document.getElementById('pierre-assign-cancel');
        const localeSelect = document.getElementById('pierre-assign-locale');
        const projectsHint = document.getElementById('pierre-assign-projects-hint');
        const projectsList = document.getElementById('pierre-assign-projects-list');
        const userNameSpan = document.getElementById('pierre-assign-user-name');
        const userIdInput = document.getElementById('pierre-assign-user-id');

        const projectsByLocale = <?php echo wp_json_encode($data['projects_by_locale'] ?? []); ?>;

        function showModal(userId, userName) {
            userIdInput.value = userId;
            userNameSpan.textContent = userName;
            modal.style.display = 'block';
            overlay.style.display = 'block';
        }

        function hideModal() {
            modal.style.display = 'none';
            overlay.style.display = 'none';
            form.reset();
            projectsHint.style.display = 'none';
        }

        localeSelect.addEventListener('change', function() {
            const locale = this.value;
            projectsList.innerHTML = '';
            if (projectsByLocale[locale] && projectsByLocale[locale].length > 0) {
                projectsByLocale[locale].forEach(function(slug) {
                    const li = document.createElement('li');
                    li.textContent = slug;
                    projectsList.appendChild(li);
                });
                projectsHint.style.display = 'block';
            } else {
                projectsHint.style.display = 'none';
            }
        });

        assignBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                showModal(userId, userName);
            });
        });

        cancelBtn.addEventListener('click', hideModal);
        overlay.addEventListener('click', hideModal);

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = '<?php echo esc_js(__('Assigning...', 'wp-pierre')); ?>';

            const formData = new FormData(form);
            formData.append('action', 'pierre_admin_assign_user');
            formData.append('nonce', window.pierreAdminL10n?.nonce || '');

            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? '<?php echo esc_js(__('Assignment successful!', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Assignment failed.', 'wp-pierre')); ?>');
                    if (json && json.success) {
                        alert(msg);
                        location.reload();
                    } else {
                        alert(msg);
                    }
                })
                .catch(() => {
                    alert('<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        });
    })();
    </script>

    <div class="pierre-card">
            <h2><?php echo esc_html__('Roles & Capabilities', 'wp-pierre'); ?></h2>
            <div class="pierre-grid" style="grid-template-columns: 1fr 1fr; gap:16px;">
                <div>
                    <h3 style="margin-top:0;"><?php echo esc_html__('Pierre Roles', 'wp-pierre'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column"><?php echo esc_html__('Role', 'wp-pierre'); ?></th>
                                <th scope="col" class="manage-column"><?php echo esc_html__('Description', 'wp-pierre'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data['roles'])): ?>
                                <?php foreach ($data['roles'] as $key => $label): ?>
                                <tr>
                                    <td><?php echo esc_html($key); ?></td>
                                    <td><?php echo esc_html($label); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="no-items">
                                    <td class="colspanchange" colspan="2"><?php echo esc_html__('No roles found.', 'wp-pierre'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 style="margin-top:0;"><?php echo esc_html__('Pierre Capabilities', 'wp-pierre'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column"><?php echo esc_html__('Capability', 'wp-pierre'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data['capabilities'])): ?>
                                <?php foreach ($data['capabilities'] as $capability): ?>
                                <tr>
                                    <td><code><?php echo esc_html($capability); ?></code></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="no-items">
                                    <td class="colspanchange" colspan="1"><?php echo esc_html__('No capabilities found.', 'wp-pierre'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>

</div>
