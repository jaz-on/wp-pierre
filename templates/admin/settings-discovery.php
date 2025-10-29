<?php
/**
 * Pierre's admin settings discovery template - he discovers resources! 🪨
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
    <!-- Settings Discovery page content -->

    <div class="pierre-card">
        <h2><?php echo esc_html__('Locales Discovery', 'wp-pierre'); ?></h2>
        <p><?php echo esc_html__('Discover and add locales from wordpress.org. Only site administrators can add locales.', 'wp-pierre'); ?></p>
        <p class="description">
            <?php echo esc_html__('This will fetch available locales from WordPress.org. You can then add them to monitoring.', 'wp-pierre'); ?>
            <?php echo esc_html__('To manage active locales, go to the Locales page.', 'wp-pierre'); ?>
        </p>
        <div class="pierre-help-box">
            <details>
                <summary>
                    <?php echo esc_html__('Sources & Help', 'wp-pierre'); ?>
                </summary>
                <ul>
                    <li>
                        <a href="https://translate.wordpress.org/" target="_blank" rel="noopener">
                            <?php echo esc_html__('translate.wordpress.org', 'wp-pierre'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://gist.github.com/danielbachhuber/14af08c5faac07d5c0c182eb66b19b3e" target="_blank" rel="noopener">
                            <?php echo esc_html__('Available translations (reference gist)', 'wp-pierre'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://make.wordpress.org/polyglots/teams/" target="_blank" rel="noopener">
                            <?php echo esc_html__('Polyglots Teams', 'wp-pierre'); ?>
                        </a>
                        —
                        <a href="https://make.wordpress.org/polyglots/handbook/translating/teams/local-slacks/" target="_blank" rel="noopener">
                            <?php echo esc_html__('Local Slack Teams', 'wp-pierre'); ?>
                        </a>
                    </li>
                </ul>
            </details>
        </div>
    </div>

    <!-- Projects Discovery moved to its own tab -->

    <!-- Inline Locales Discovery (no modal) -->
    <div class="pierre-card">
        <h2><?php echo esc_html__('Discover Locales from WordPress.org', 'wp-pierre'); ?></h2>
        <p><?php echo esc_html__('Select locales to add to monitoring:', 'wp-pierre'); ?></p>
        <p>
            <input type="search" id="pierre-locales-search" class="regular-text" placeholder="<?php echo esc_attr__('Search locales (code or name)…', 'wp-pierre'); ?>" aria-label="<?php echo esc_attr__('Search locales', 'wp-pierre'); ?>">
        </p>
        <div id="pierre-locales-grid">
            <p><?php echo esc_html__('Loading locales...', 'wp-pierre'); ?></p>
        </div>
        <div class="pierre-locales-actions">
            <button type="button" class="button button-primary" id="pierre-add-selected-locales" disabled>
                <?php echo esc_html__('Add Selected Locales', 'wp-pierre'); ?>
            </button>
        </div>
        <input type="hidden" id="pierre-ajax-nonce" value="<?php echo esc_attr( wp_create_nonce('pierre_admin_ajax') ); ?>" />
    </div>

    <script>
    (function(){
        const grid = document.getElementById('pierre-locales-grid');
        const addBtn = document.getElementById('pierre-add-selected-locales');
        const searchInput = document.getElementById('pierre-locales-search');
        const ajaxUrl = window.pierreAdminL10n?.ajaxUrl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo esc_js(admin_url('admin-ajax.php')); ?>');
        const nonce = window.pierreAdminL10n?.nonce || document.getElementById('pierre-ajax-nonce')?.value || '';
        const activeLocales = <?php echo wp_json_encode($data['active_locales'] ?? []); ?>;

        function updateAddButton() {
            const checked = grid.querySelectorAll('input[type="checkbox"]:checked:not([disabled])').length;
            addBtn.disabled = checked === 0;
        }

        function renderLocales(list) {
            const manageBase = '<?php echo esc_js(admin_url('admin.php?page=pierre-locale-view&locale=')); ?>';
            const slackTeamsUrl = 'https://make.wordpress.org/polyglots/handbook/translating/teams/local-slacks/';
            let html = '<div class="pierre-locales-grid">';
            list.forEach(function(locale){
                const isActive = activeLocales.includes(locale.code);
                const slug = (locale.slug || (locale.code || '').toLowerCase().replace('_','-'));
                const translateUrl = 'https://translate.wordpress.org/locale/' + encodeURIComponent(slug);
                const rosettaHost = (locale.rosetta || (slug + '.wordpress.org'));
                const rosettaUrl = 'https://' + rosettaHost;
                const slackDirect = locale.slack_url || '';
                html += '<label class="pierre-locale-card" data-code="' + locale.code + '" data-label="' + (locale.label || '') + '">';
                html += '<input type="checkbox" name="locale[]" value="' + locale.code + '" ' + (isActive ? 'disabled' : '') + ' /> ';
                html += '<strong>' + locale.code + '</strong><br><small>' + (locale.label || locale.code) + '</small>';
                html += '<div class="pierre-locale-badges">'
                    + '<span class="pierre-badge ' + (isActive ? 'is-active' : 'is-inactive') + '">' + (isActive ? '<?php echo esc_js(__('Active', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Inactive', 'wp-pierre')); ?>') + '</span>'
                    + ' '
                    + '<span class="pierre-badge ' + (slackDirect ? 'is-slack-direct' : 'is-slack-directory') + '">' + (slackDirect ? '<?php echo esc_js(__('Slack direct', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Slack directory', 'wp-pierre')); ?>') + '</span>'
                    + '</div>';
                html += '<div class="pierre-locale-actions">'
                    + '<a href="' + translateUrl + '" target="_blank" rel="noopener"><?php echo esc_js(__('Translate', 'wp-pierre')); ?></a>'
                    + ' · <a href="' + (slackDirect || slackTeamsUrl) + '" target="_blank" rel="noopener">' + (slackDirect ? '<?php echo esc_js(__('Slack', 'wp-pierre')); ?>' : '<?php echo esc_js(__('Local Slack Teams', 'wp-pierre')); ?>') + '</a>'
                    + ' · <a href="' + rosettaUrl + '" target="_blank" rel="noopener"><?php echo esc_js(__('Rosetta', 'wp-pierre')); ?></a>'
                    + ' · <a href="' + manageBase + encodeURIComponent(locale.code) + '" class="button-link" target="_blank" rel="noopener"><?php echo esc_js(__('Manage', 'wp-pierre')); ?></a>'
                    + ' · <button type="button" class="button button-small" data-copy="' + locale.code + '"><?php echo esc_js(__('Copy code', 'wp-pierre')); ?></button>'
                    + '</div>';
                html += '</label>';
            });
            html += '</div>';
            grid.innerHTML = html;
            grid.querySelectorAll('button[data-copy]').forEach(function(btn){
                btn.addEventListener('click', function(){
                    const value = this.getAttribute('data-copy');
                    navigator.clipboard && navigator.clipboard.writeText && navigator.clipboard.writeText(value);
                });
            });
            grid.querySelectorAll('input[type="checkbox"]:not([disabled])').forEach(function(cb){
                cb.addEventListener('change', updateAddButton);
            });
            updateAddButton();
        }

        function loadLocales() {
            if (!nonce) { grid.innerHTML = '<p><?php echo esc_js(__('Error: Nonce not found. Please refresh the page.', 'wp-pierre')); ?></p>'; return; }
            const formData = new FormData();
            formData.append('action', 'pierre_fetch_locales');
            formData.append('nonce', nonce);
            fetch(ajaxUrl, { method:'POST', body: formData })
                .then(r=>r.json())
                .then(json=>{
                    const locales = (json && json.success && Array.isArray(json.data?.locales)) ? json.data.locales : [];
                    renderLocales(locales);
                    function applySearch(){
                        const q = (searchInput?.value || '').toLowerCase().trim();
                        grid.querySelectorAll('.pierre-locale-card').forEach(function(card){
                            const hay = ((card.getAttribute('data-code')||'') + ' ' + (card.getAttribute('data-label')||'')).toLowerCase();
                            card.style.display = q ? (hay.includes(q) ? '' : 'none') : '';
                        });
                    }
                    searchInput?.addEventListener('input', applySearch);
                    applySearch();
                })
                .catch(()=>{ grid.innerHTML = '<p><?php echo esc_js(__('Network error. Check console for details.', 'wp-pierre')); ?></p>'; });
        }

        addBtn.addEventListener('click', function(){
            const selected = Array.from(grid.querySelectorAll('input[type="checkbox"]:checked:not([disabled])')).map(cb=>cb.value);
            if (!selected.length) return;
            const formData = new FormData();
            formData.append('action', 'pierre_add_locales');
            formData.append('nonce', nonce);
            selected.forEach(function(loc){ formData.append('locales[]', loc); });
            addBtn.disabled = true;
            fetch(ajaxUrl, { method:'POST', body: formData })
                .then(r=>r.json())
                .then(json=>{ if (json && json.success) { location.reload(); } else { alert((json && (json.data?.message||json.message)) || ''); } })
                .catch(()=> alert('<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>'))
                .finally(()=>{ addBtn.disabled = false; });
        });

        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', loadLocales); } else { loadLocales(); }
    })();
    </script>
</div>

