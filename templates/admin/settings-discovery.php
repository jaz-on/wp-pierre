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
    <div class="pierre-grid pierre-grid--single">
    <!-- Settings Discovery page content -->

    <div class="pierre-card">
        <h2><?php echo esc_html__('Locales Discovery', 'wp-pierre'); ?> <span id="pierre-locales-discovery-tab-loader" class="spinner is-active pierre-va-middle" aria-hidden="true"></span></h2>
        <p><?php echo esc_html__('Discover and add locales from wordpress.org. Only site administrators can add locales.', 'wp-pierre'); ?></p>
        <p class="description">
            <?php echo esc_html__('This lists locales fetched from WordPress.org. Add the ones you need to monitoring here; ongoing refreshes and maintenance live in General → “Locales Discovery options”.', 'wp-pierre'); ?>
            <?php echo esc_html__('To manage active locales, go to the Locales page.', 'wp-pierre'); ?>
        </p>
        <?php 
            $cache = get_option('pierre_locales_cache'); 
            $last = is_array($cache)?($cache['last_fetched']??0):0; 
            $cached_list = (is_array($cache) && !empty($cache['data']) && is_array($cache['data'])) ? $cache['data'] : [];
        ?>
        <p class="description" id="pierre-locales-last-fetched">
            <?php echo esc_html__('Last refresh:', 'wp-pierre'); ?>
            <strong><?php echo $last ? esc_html(date_i18n(get_option('date_format').' '.get_option('time_format'), (int)$last)) : esc_html__('never', 'wp-pierre'); ?></strong>
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
        <div class="pierre-locales-actions pierre-mt-8">
            <a class="button" href="<?php echo esc_url( admin_url('admin-ajax.php?action=pierre_export_locales_json&nonce=' . wp_create_nonce('pierre_admin_ajax')) ); ?>"><?php echo esc_html__('Export Locales (JSON)', 'wp-pierre'); ?></a>
            <a class="button" href="<?php echo esc_url( admin_url('admin-ajax.php?action=pierre_export_locales_csv&nonce=' . wp_create_nonce('pierre_admin_ajax')) ); ?>"><?php echo esc_html__('Export Locales (CSV)', 'wp-pierre'); ?></a>
        </div>
    </div>

    <!-- Projects Discovery moved to its own tab -->

    <!-- Inline Locales Discovery (no modal) -->
    <div class="pierre-card">
        <h2><?php echo esc_html__('Discover Locales from WordPress.org', 'wp-pierre'); ?></h2>
        <p><?php echo esc_html__('Select locales to add to monitoring:', 'wp-pierre'); ?></p>
        <p class="pierre-row">
            <input type="search" id="pierre-locales-search" class="regular-text" placeholder="<?php echo esc_attr__('Search locales (code or name)…', 'wp-pierre'); ?>" aria-label="<?php echo esc_attr__('Search locales', 'wp-pierre'); ?>">
            <span id="pierre-locales-count" class="description pierre-ml-8"></span>
            <button type="button" class="button button-primary" id="pierre-add-selected-locales-top" disabled>
                <?php echo esc_html__('Add Selected Locales', 'wp-pierre'); ?>
            </button>
        </p>
        <div id="pierre-locales-grid" data-active-locales='<?php echo esc_attr( wp_json_encode($data['active_locales'] ?? []) ); ?>' data-manage-base="<?php echo esc_attr( admin_url('admin.php?page=pierre-locale-view&locale=') ); ?>">
            <?php if (empty($cached_list)) : ?>
                <div class="notice notice-info"><p>
                    <?php echo esc_html__('No cached locales available. Go to General → “Global Locales Discovery Settings” to fetch and schedule refreshes.', 'wp-pierre'); ?>
                    <a class="button pierre-ml-8" href="<?php echo esc_url( admin_url('admin.php?page=pierre-settings#general') ); ?>"><?php echo esc_html__('Open General settings', 'wp-pierre'); ?></a>
                </p></div>
            <?php endif; ?>
        </div>
        <?php if (!empty($cached_list)) : ?>
            <script id="pierre-locales-initial" type="application/json"><?php echo wp_json_encode($cached_list); ?></script>
        <?php endif; ?>
        <p id="pierre-locales-status" class="pierre-help"></p>
        
        <div class="pierre-locales-actions">
            <button type="button" class="button button-primary" id="pierre-add-selected-locales" disabled>
                <?php echo esc_html__('Add Selected Locales', 'wp-pierre'); ?>
            </button>
        </div>
        <input type="hidden" id="pierre-ajax-nonce" value="<?php echo esc_attr( wp_create_nonce('pierre_admin_ajax') ); ?>" />
    </div>

    <script>
    (function(){
        // Remove the tab-level loader once DOM is ready (before fetch completes, grid shows its own loader)
        const tabLoader = document.getElementById('pierre-locales-discovery-tab-loader');
        if (tabLoader) { if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', ()=>tabLoader.remove()); } else { tabLoader.remove(); } }

        const grid = document.getElementById('pierre-locales-grid');
        const addBtn = document.getElementById('pierre-add-selected-locales');
        const addBtnTop = document.getElementById('pierre-add-selected-locales-top');
        const searchInput = document.getElementById('pierre-locales-search');
        const countEl = document.getElementById('pierre-locales-count');
        const statusEl = document.getElementById('pierre-locales-status');
        let isFetching = false;
        
        const ajaxUrl = window.pierreAdminL10n?.ajaxUrl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo esc_js(admin_url('admin-ajax.php')); ?>');
        const nonce = window.pierreAdminL10n?.nonce || document.getElementById('pierre-ajax-nonce')?.value || '';
        const activeLocales = <?php echo wp_json_encode($data['active_locales'] ?? []); ?>;

        function updateAddButton() {
            const checked = grid.querySelectorAll('input[type="checkbox"]:checked:not([disabled])').length;
            const disabled = checked === 0;
            if (addBtn) addBtn.disabled = disabled;
            if (addBtnTop) addBtnTop.disabled = disabled;
        }

        function renderLocales(list) {
            const manageBase = '<?php echo esc_js(admin_url('admin.php?page=pierre-locale-view&locale=')); ?>';
            const slackTeamsUrl = 'https://make.wordpress.org/polyglots/handbook/translating/teams/local-slacks/';
            let html = '<div class="pierre-locales-grid">';
            list.forEach(function(locale){
                const isActive = activeLocales.includes(locale.code);
                const slug = (locale.slug || (locale.code || '').toLowerCase().replace('_','-'));
                const translateSlug = (locale.translate_slug || slug);
                const translateUrl = 'https://translate.wordpress.org/locale/' + encodeURIComponent(translateSlug);
                const rosettaHost = (locale.rosetta || (slug + '.wordpress.org'));
                const rosettaUrl = 'https://' + rosettaHost;
                const slackDirect = locale.slack_url || '';
                html += '<label class="pierre-locale-card' + (isActive ? ' is-disabled' : '') + '" data-code="' + locale.code + '" data-label="' + (locale.label || '') + '">';
                html += '<input type="checkbox" name="locale[]" value="' + locale.code + '" ' + (isActive ? 'disabled' : '') + ' /> ';
                html += '<strong data-copy="' + locale.code + '" title="<?php echo esc_js(__('Click to copy', 'wp-pierre')); ?>">' + locale.code + '</strong><br><small>' + (locale.label || locale.code) + '</small>';
                if (isActive) {
                    html += '<div class="pierre-locale-note"><?php echo esc_js(__('Already added', 'wp-pierre')); ?></div>';
                }
                html += '<div class="pierre-locale-actions pierre-locale-actions--row">'
                    + '<a href="' + translateUrl + '" target="_blank" rel="noopener"><?php echo esc_js(__('Translate', 'wp-pierre')); ?></a>'
                    + ' · <a href="' + rosettaUrl + '" target="_blank" rel="noopener"><?php echo esc_js(__('Rosetta', 'wp-pierre')); ?></a>'
                    + ' · <a href="https://make.wordpress.org/polyglots/teams/?locale=' + encodeURIComponent(locale.team_locale || locale.code) + '" target="_blank" rel="noopener"><?php echo esc_js(__('Team', 'wp-pierre')); ?></a>'
                    + ' · ' + (slackDirect
                        ? ('<a href="' + slackDirect + '" target="_blank" rel="noopener"><?php echo esc_js(__('Slack', 'wp-pierre')); ?></a>')
                        : ('<span class="is-muted" title="<?php echo esc_js(__('Non renseigné', 'wp-pierre')); ?>"><?php echo esc_js(__('Slack', 'wp-pierre')); ?></span>'))
                    + '</div>';
                html += '</label>';
            });
            html += '</div>';
            grid.innerHTML = html;
            if (countEl) { countEl.textContent = list.length + ' <?php echo esc_js(__('total', 'wp-pierre')); ?>'; }
            grid.querySelectorAll('[data-copy]').forEach(function(el){
                el.addEventListener('click', function(){
                    const value = this.getAttribute('data-copy');
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(value);
                        this.classList.add('is-copied');
                        setTimeout(()=>this.classList.remove('is-copied'), 1000);
                    }
                });
            });
            grid.querySelectorAll('input[type="checkbox"]:not([disabled])').forEach(function(cb){
                cb.addEventListener('change', updateAddButton);
            });
            updateAddButton();
        }

        function renderError(message, code) {
            const btnId = 'pierre-retry-locales';
            grid.innerHTML = '<div class="notice notice-error"><p>' +
                (message || '<?php echo esc_js(__('An error occurred while fetching locales.', 'wp-pierre')); ?>') +
                (code ? ' (HTTP: ' + code + ')' : '') +
                '</p><p><button type="button" class="button" id="' + btnId + '"><?php echo esc_js(__('Retry', 'wp-pierre')); ?></button></p></div>';
            const retry = document.getElementById(btnId);
            retry && retry.addEventListener('click', () => { grid.innerHTML = '<p><span class="spinner is-active"></span> <?php echo esc_js(__('Loading locales...', 'wp-pierre')); ?></p>'; loadLocales(); });
        }

        function loadLocales() {
            if (isFetching) return; isFetching = true;
            if (statusEl) { statusEl.textContent = '<?php echo esc_js(__('Fetching locales… please wait.', 'wp-pierre')); ?>'; }
            searchInput && (searchInput.disabled = true);
            if (!nonce) { grid.innerHTML = '<p><?php echo esc_js(__('Error: Nonce not found. Please refresh the page.', 'wp-pierre')); ?></p>'; return; }
            const formData = new FormData();
            formData.append('action', 'pierre_fetch_locales');
            formData.append('nonce', nonce);
            fetch(ajaxUrl, { method:'POST', body: formData })
                .then(r=>{
                    const status = r.status;
                    return r.json().then(json => ({ json, status }));
                })
                .then(({json, status})=>{
                    const locales = (json && json.success && Array.isArray(json.data?.locales)) ? json.data.locales : [];
                    if (!locales.length) { renderError((json && (json.data?.message||json.message)) || '', status); return; }
                    renderLocales(locales);
                    function applySearch(){
                        const q = (searchInput?.value || '').toLowerCase().trim();
                        let visible = 0; let total = 0;
                        grid.querySelectorAll('.pierre-locale-card').forEach(function(card){
                            total++;
                            const hay = ((card.getAttribute('data-code')||'') + ' ' + (card.getAttribute('data-label')||'')).toLowerCase();
                            const show = q ? hay.includes(q) : true;
                            card.style.display = show ? '' : 'none';
                            if (show) visible++;
                        });
                        if (countEl) { countEl.textContent = (q ? (visible + ' / ') : '') + total + ' <?php echo esc_js(__('total', 'wp-pierre')); ?>'; }
                    }
                    searchInput?.addEventListener('input', applySearch);
                    applySearch();
                })
                .catch(()=>{ renderError('<?php echo esc_js(__('Network error. Please check your connection or server logs (debug.log), then retry.', 'wp-pierre')); ?>', 0); })
                .finally(()=>{ isFetching = false; searchInput && (searchInput.disabled=false); if (statusEl) statusEl.textContent = ''; });
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
                .then(json=>{ if (json && json.success) { location.reload(); } else { var m = (json && (json.data?.message||json.message)) || ''; window.pierreNotice ? window.pierreNotice('error', m || '<?php echo esc_js(__('Failed.', 'wp-pierre')); ?>') : alert(m); } })
                .catch(()=> { var m = '<?php echo esc_js(__('Network error. Please check your connection or server logs (debug.log), then retry.', 'wp-pierre')); ?>'; window.pierreNotice ? window.pierreNotice('error', m) : alert(m); })
                .finally(()=>{ addBtn.disabled = false; });
        });

        // Top button mirrors bottom button
        if (addBtnTop) {
            addBtnTop.addEventListener('click', function(){ if (!this.disabled && addBtn) { addBtn.click(); } });
        }

        // Render cached locales if present; do not auto-fetch to avoid background calls on every load.
        const initialEl = document.getElementById('pierre-locales-initial');
        if (initialEl) {
            try {
                const parsed = JSON.parse(initialEl.textContent || '[]');
                if (Array.isArray(parsed) && parsed.length) {
                    renderLocales(parsed);
                    const applySearch = function(){
                        const q = (searchInput?.value || '').toLowerCase().trim();
                        let visible = 0; let total = 0;
                        grid.querySelectorAll('.pierre-locale-card').forEach(function(card){
                            total++;
                            const hay = ((card.getAttribute('data-code')||'') + ' ' + (card.getAttribute('data-label')||'')).toLowerCase();
                            const show = q ? hay.includes(q) : true;
                            card.style.display = show ? '' : 'none';
                            if (show) visible++;
                        });
                        if (countEl) { countEl.textContent = (q ? (visible + ' / ') : '') + total + ' <?php echo esc_js(__('total', 'wp-pierre')); ?>'; }
                    };
                    searchInput?.addEventListener('input', applySearch);
                    applySearch();
                }
            } catch(e) {}
        }
        // No auto-fetch here; use General → "Global Locales Discovery Settings" to (re)build the cache
    })();
    </script>
    </div>
    </div>

