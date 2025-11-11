<?php
/**
 * Pierre's admin settings template - he manages his configuration! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */


// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_admin_template_data'] ?? [];
$settings = $data['settings'] ?? [];
?>

<div class="wrap pierre-settings">
    <div class="pierre-visually-hidden" role="status" aria-live="polite" id="pierre-global-aria-live"></div>
    <?php $ui_name = (string) (($settings['ui']['plugin_name'] ?? 'Pierre') ?: 'Pierre'); ?>
    <h1><?php echo esc_html($ui_name . ' 🪨 Settings'); ?></h1>
    <!-- Settings page header is handled by render_settings_page() -->

    <?php
    $current_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'general' ) );
    $base_url = admin_url( 'admin.php?page=pierre-settings' );
    ?>

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'general', $base_url ) ); ?>" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__( 'General', 'wp-pierre' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'discovery', $base_url ) ); ?>" class="nav-tab <?php echo $current_tab === 'discovery' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__( 'Locales Discovery', 'wp-pierre' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'global-webhook', $base_url ) ); ?>" class="nav-tab <?php echo $current_tab === 'global-webhook' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__( 'Global Webhook', 'wp-pierre' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'projects-discovery', $base_url ) ); ?>" class="nav-tab <?php echo $current_tab === 'projects-discovery' ? 'nav-tab-active' : ''; ?>">
            <?php echo esc_html__( 'Projects Discovery', 'wp-pierre' ); ?>
        </a>
    </nav>

    <?php if (defined('PIERRE_COMPOSER_MISSING') && PIERRE_COMPOSER_MISSING && defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')): ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong><?php echo esc_html__('Developer tip:', 'wp-pierre'); ?></strong> <?php echo esc_html__('Composer autoload is missing. Run "composer dump-autoload" in the plugin folder for better performance.', 'wp-pierre'); ?></p>
            <p class="description"><?php echo esc_html__('How these settings work.', 'wp-pierre'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( $current_tab === 'general' ): ?>
    <div class="pierre-card pierre-mb-16">
        <h2><?php echo esc_html__('How these settings work', 'wp-pierre'); ?></h2>
        <p class="description">
            <?php echo esc_html__('Pierre monitors translation projects and notifies your team. Use the tabs below to configure the scan cadence, discover locales and projects, and control notifications & webhooks.', 'wp-pierre'); ?>
        </p>
        <ul class="ul-disc pierre-list">
            <li><?php echo esc_html__('General: global behaviour, defaults for notifications, locales discovery options (exports/refresh), overall system status.', 'wp-pierre'); ?></li>
            <li><?php echo esc_html__('Locales Discovery: browse locales from WordPress.org and add them to monitoring.', 'wp-pierre'); ?></li>
            <li><?php echo esc_html__('Projects Discovery: manage a library of projects and bulk-add to surveillance (coming options).', 'wp-pierre'); ?></li>
            <li><?php echo esc_html__('Global Webhook: set the destination, event types, thresholds/digest mode, optional scopes; preview and test payloads.', 'wp-pierre'); ?></li>
        </ul>
        
        <p class="description">
            <?php echo esc_html__('Docs:', 'wp-pierre'); ?>
            <a href="https://github.com/jaz-on/wp-pierre" target="_blank" rel="noopener">GitHub</a>
            · <a href="https://github.com/jaz-on/wp-pierre/wiki" target="_blank" rel="noopener">Wiki</a>
        </p>
    </div>

    <div class="pierre-grid pierre-grid--cards">
        <div class="pierre-card">
            <h2><?php echo esc_html__('Plugin Surveillance Settings', 'wp-pierre'); ?></h2>
            <form class="pierre-form-wide" id="pierre-surveillance-settings" method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
                <?php settings_fields( 'pierre_settings_group' ); ?>
                <?php 
                // Afficher la description de la section
                \Pierre\Settings\Settings::render_section_surveillance();
                // Afficher les champs de la section dans un tableau
                ?>
                <table class="form-table" role="presentation">
                    <?php do_settings_fields( 'pierre-settings', 'pierre_section_surveillance' ); ?>
                </table>
                <div class="pierre-form-actions" id="surv-actions-group">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Save Settings', 'wp-pierre'); ?>
                    </button>
                    <button type="button" class="button" id="pierre-run-now" title="<?php echo esc_attr__('Force a surveillance check now (ignores the global enable switch).', 'wp-pierre'); ?>"><?php echo esc_html__('Run surveillance now', 'wp-pierre'); ?></button>
                    <button type="button" class="button pierre-button-danger" id="pierre-abort-run"><?php echo esc_html__('Stop current run', 'wp-pierre'); ?></button>
                </div>
            </form>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Plugin Webhook Settings', 'wp-pierre'); ?></h2>
            <?php 
            $raw_global_webhook = $settings['global_webhook']['webhook_url'] ?? ($settings['slack_webhook_url'] ?? '');
            $global_webhook = !empty($raw_global_webhook) ? pierre_decrypt_webhook($raw_global_webhook) : '';
            $global_hook_empty = empty($global_webhook);
            $settings_opt = \Pierre\Settings\Settings::all();
            $local_hooks = (array)($settings_opt['locales_slack'] ?? []);
            $all_empty = $global_hook_empty && (empty(array_filter($local_hooks)));
            if ($all_empty): ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong><?php echo esc_html__('No Slack webhook configured (global or per-locale). Notifications will not be delivered.', 'wp-pierre'); ?></strong><br/>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-settings&tab=global-webhook')); ?>"><?php echo esc_html__('Add a Global Webhook', 'wp-pierre'); ?></a>
                        · 
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-locales')); ?>"><?php echo esc_html__('Or per-locale: go to Locales → Manage → Slack Webhook', 'wp-pierre'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
            <form class="pierre-form-compact" id="pierre-notification-settings" method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
                <?php settings_fields( 'pierre_settings_group' ); ?>
                <?php 
                // Afficher la description de la section
                \Pierre\Settings\Settings::render_section_notifications();
                // Afficher les champs de la section dans un tableau
                ?>
                <table class="form-table" role="presentation">
                    <?php do_settings_fields( 'pierre-settings', 'pierre_section_notifications' ); ?>
                </table>
                <div class="pierre-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Save Settings', 'wp-pierre'); ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Plugin Locales Discovery Settings', 'wp-pierre'); ?></h2>
            <?php 
            $cache = get_option('pierre_locales_cache');
            $rows = [];
            if (is_array($cache) && !empty($cache['data']) && is_array($cache['data'])) {
                foreach ($cache['data'] as $loc) {
                    $code = (string)($loc['code'] ?? '');
                    $slug = (string)($loc['slug'] ?? '');
                    $tSlug = (string)($loc['translate_slug'] ?? '');
                    $rosetta = (string)($loc['rosetta'] ?? '');
                    $issues = [];
                    if ($code !== '' && $tSlug !== '' && strtolower(str_replace('_','-',$code)) !== strtolower($tSlug)) {
                        $issues[] = 'translate_slug≠code';
                    }
                    if ($rosetta === '') {
                        $issues[] = 'rosetta_inactive_or_missing';
                    }
                    if (!empty($issues)) {
                        $rows[] = [
                            'code' => $code,
                            'label' => (string)($loc['label'] ?? $code),
                            'translate_slug' => $tSlug,
                            'rosetta' => $rosetta,
                            'issues' => implode(', ', $issues)
                        ];
                    }
                }
            }
            ?>
            <h3><?php echo esc_html__('Logs', 'wp-pierre'); ?></h3>
            <?php $count = is_array($rows)? count($rows):0; ?>
            <p class="pierre-mt-8 pierre-mb-8">
                <span class="<?php echo $count === 0 ? 'status-ok' : 'status-ko'; ?>">
                    <?php echo esc_html(sprintf(__('Anomalies detected: %d', 'wp-pierre'), (int)$count)); ?>
                </span>
            </p>
            <div class="pierre-row pierre-mt-8 pierre-mb-8">
                <button type="button" class="button" id="pierre-check-all-locales" data-nonce="<?php echo esc_attr( wp_create_nonce('pierre_admin_ajax') ); ?>"><?php echo esc_html__('Check all now', 'wp-pierre'); ?></button>
                <button type="button" class="button" id="pierre-clear-locale-log"><?php echo esc_html__('Purge log', 'wp-pierre'); ?></button>
                <a class="button" href="<?php echo esc_url( admin_url('admin-ajax.php?action=pierre_export_locale_log&nonce=' . wp_create_nonce('pierre_admin_ajax')) ); ?>"><?php echo esc_html__('Export log (JSON)', 'wp-pierre'); ?></a>
            </div>

            <h3><?php echo esc_html__('Cache & schedule', 'wp-pierre'); ?></h3>
            <?php $last = is_array($cache)?($cache['last_fetched']??0):0; $next = wp_next_scheduled('pierre_refresh_locales_cache'); $running = (int) get_transient('pierre_locales_fetch_running'); $err = get_option('pierre_locales_fetch_error'); ?>
            <p class="description pierre-mt-8 pierre-mb-8" id="pierre-locales-last-fetched">
                <?php echo esc_html__('Last refresh:', 'wp-pierre'); ?>
                <strong><?php echo $last ? esc_html(date_i18n(get_option('date_format').' '.get_option('time_format'), (int)$last)) : esc_html__('never', 'wp-pierre'); ?></strong>
                <br />
                <?php echo esc_html__('Next refresh:', 'wp-pierre'); ?>
                <strong><?php echo $next ? esc_html(date_i18n(get_option('date_format').' '.get_option('time_format'), (int)$next)) : esc_html__('unscheduled', 'wp-pierre'); ?></strong>
                <br />
                <?php if ($running): ?>
                    <span class="pierre-status-ok"><?php echo esc_html__('Fetch in progress…', 'wp-pierre'); ?></span>
                    <br />
                <?php endif; ?>
                <?php if (!empty($err)): ?>
                    <span class="pierre-danger"><?php echo esc_html__('Last fetch error:', 'wp-pierre'); ?></span>
                    <code><?php echo esc_html($err); ?></code>
                    <br />
                <?php endif; ?>
                <button type="button" class="button" id="pierre-force-refresh-locales"><?php echo esc_html__('Force refresh', 'wp-pierre'); ?></button>
                <span id="pierre-force-refresh-spinner" class="spinner pierre-va-middle" aria-hidden="true"></span>
            </p>
            <?php if (empty($rows)): ?>
                <p><?php echo esc_html__('No anomalies detected.', 'wp-pierre'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <caption class="screen-reader-text"><?php echo esc_html__('Locales anomalies', 'wp-pierre'); ?></caption>
                    <thead>
                        <tr>
                            <th scope="col"><?php echo esc_html__('Code', 'wp-pierre'); ?></th>
                            <th scope="col"><?php echo esc_html__('Label', 'wp-pierre'); ?></th>
                            <th scope="col"><?php echo esc_html__('Translate Slug', 'wp-pierre'); ?></th>
                            <th scope="col"><?php echo esc_html__('Rosetta', 'wp-pierre'); ?></th>
                            <th scope="col"><?php echo esc_html__('Issues', 'wp-pierre'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="pierre-anomalies-body">
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo esc_html($r['code']); ?></td>
                                <td><?php echo esc_html($r['label']); ?></td>
                                <td class="col-translate-slug"><?php echo esc_html($r['translate_slug']); ?></td>
                                <td class="col-rosetta"><?php echo $r['rosetta'] ? '<a href="' . esc_url('https://' . $r['rosetta']) . '" target="_blank" rel="noopener">' . esc_html($r['rosetta']) . '</a>' : '<em>' . esc_html__('None', 'wp-pierre') . '</em>'; ?></td>
                                <td class="col-issues"><?php echo esc_html($r['issues']); ?> <button type="button" class="button button-small pierre-check-locale" data-code="<?php echo esc_attr($r['code']); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce('pierre_admin_ajax') ); ?>"><?php echo esc_html__('Check', 'wp-pierre'); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Plugin Projects Discovery Settings', 'wp-pierre'); ?></h2>
            <p class="description"><?php echo esc_html__('Planifier le crawl du catalogue projets et consulter le statut. Première version minimale.', 'wp-pierre'); ?></p>
            <?php $meta = get_option('pierre_projects_catalog_meta', []); $last = (int)($meta['last_built']??0); $next=(int)($meta['next_build']??0); $dur=(int)($meta['last_duration_ms']??0); $err=$meta['last_error']??null; ?>
            <ul class="pierre-list">
                <li><strong><?php echo esc_html__('Last run:', 'wp-pierre'); ?></strong> <?php echo $last?esc_html(date_i18n(get_option('date_format').' '.get_option('time_format'), $last)):esc_html__('N/A','wp-pierre'); ?></li>
                <li><strong><?php echo esc_html__('Next run:', 'wp-pierre'); ?></strong> <?php echo $next?esc_html(date_i18n(get_option('date_format').' '.get_option('time_format'), $next)):esc_html__('N/A','wp-pierre'); ?></li>
                <li><strong><?php echo esc_html__('Last duration:', 'wp-pierre'); ?></strong> <?php echo $dur?esc_html($dur.' ms'):esc_html__('N/A','wp-pierre'); ?></li>
                <?php if (is_array($err)&&!empty($err['message'])): ?>
                <li><strong><?php echo esc_html__('Last error:', 'wp-pierre'); ?></strong> <code><?php echo esc_html((string)$err['message']); ?></code> <?php if(!empty($err['code'])) echo '(' . (int)$err['code'] . ')'; ?></li>
                <?php endif; ?>
            </ul>
            <?php $schedule = (array)($meta['schedule']??[]); $interval = (int)($schedule['interval_minutes']??1440); $mpr=(int)($schedule['max_per_run']??200); $sources=(array)($meta['sources']??[]); ?>
            <form id="pierre-catalog-settings-form" class="pierre-form-wide pierre-mt-8">
                <div class="pierre-row">
                    <label><?php echo esc_html__('Interval (minutes)', 'wp-pierre'); ?>
                        <input type="number" min="60" step="60" name="interval_minutes" value="<?php echo esc_attr($interval); ?>" />
                    </label>
                    <label><?php echo esc_html__('Max per run', 'wp-pierre'); ?>
                        <input type="number" min="10" max="500" step="10" name="max_per_run" value="<?php echo esc_attr($mpr); ?>" />
                    </label>
                </div>
                <fieldset class="pierre-mt-8">
                    <legend><?php echo esc_html__('Sources', 'wp-pierre'); ?></legend>
                    <label><input type="checkbox" name="plugins_popular" <?php echo !empty($sources['plugins']['popular'])?'checked':''; ?> /> Plugins · Popular</label>
                    <label><input type="checkbox" name="plugins_featured" <?php echo !empty($sources['plugins']['featured'])?'checked':''; ?> /> Plugins · Featured</label>
                    <label><input type="checkbox" name="themes_popular" <?php echo !empty($sources['themes']['popular'])?'checked':''; ?> /> Themes · Popular</label>
                    <label><input type="checkbox" name="themes_featured" <?php echo !empty($sources['themes']['featured'])?'checked':''; ?> /> Themes · Featured</label>
                </fieldset>
                <div class="pierre-form-actions">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Save settings', 'wp-pierre'); ?></button>
                    <span id="pierre-catalog-save-status" class="description"></span>
                </div>
            </form>
            <div class="pierre-form-actions">
                <button type="button" class="button" id="pierre-rebuild-catalog-btn"><?php echo esc_html__('Rebuild index now', 'wp-pierre'); ?></button>
                <button type="button" class="button" id="pierre-schedule-catalog-btn"><?php echo esc_html__('Schedule now', 'wp-pierre'); ?></button>
                <button type="button" class="button pierre-button-danger" id="pierre-reset-catalog-btn"><?php echo esc_html__('Reset index', 'wp-pierre'); ?></button>
                <span id="pierre-rebuild-catalog-status" class="description"></span>
                <span id="pierre-rebuild-spinner" class="spinner"></span>
            </div>
            <input type="hidden" id="pierre-ajax-nonce" value="<?php echo esc_attr( wp_create_nonce('pierre_admin_ajax') ); ?>" />
        </div>

        <script>
        (function(){
            const btn = document.getElementById('pierre-rebuild-catalog-btn');
            const sched = document.getElementById('pierre-schedule-catalog-btn');
            const status = document.getElementById('pierre-rebuild-catalog-status');
            const resetBtn = document.getElementById('pierre-reset-catalog-btn');
            const nonce = document.getElementById('pierre-ajax-nonce')?.value || '';
            const ajaxUrl = window.pierreAdminL10n?.ajaxUrl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo esc_js(admin_url('admin-ajax.php')); ?>');
            const spinner = document.getElementById('pierre-rebuild-spinner');

            function setBusy(b){ if (spinner) { if (b) spinner.classList.remove('is-hidden'); else spinner.classList.add('is-hidden'); } if (btn) btn.disabled=!!b; if (sched) sched.disabled=!!b; }
            function fmt(ts){ if(!ts) return '<?php echo esc_js(__('N/A','wp-pierre')); ?>'; try { return new Date(parseInt(ts,10)*1000).toLocaleString(); } catch(e){ return '<?php echo esc_js(__('N/A','wp-pierre')); ?>'; } }
            function refreshStatus(){
                const fd = new FormData(); fd.append('action','pierre_admin_get_catalog_status'); fd.append('nonce', nonce);
                return fetch(ajaxUrl, {method:'POST', body: fd}).then(r=>r.json()).then(j=>{
                    if (j && j.success && j.data) {
                        const meta = j.data; const list = document.querySelector('.pierre-card h2+ .pierre-list');
                        if (list) {
                            const lis = list.querySelectorAll('li');
                            if (lis[0]) lis[0].innerHTML = '<strong><?php echo esc_js(__('Last run:', 'wp-pierre')); ?></strong> ' + fmt(meta.last_built||0);
                            if (lis[1]) lis[1].innerHTML = '<strong><?php echo esc_js(__('Next run:', 'wp-pierre')); ?></strong> ' + fmt(meta.next_build||0);
                            if (lis[2]) lis[2].innerHTML = '<strong><?php echo esc_js(__('Last duration:', 'wp-pierre')); ?></strong> ' + ((meta.last_duration_ms? (parseInt(meta.last_duration_ms,10)+' ms') : '<?php echo esc_js(__('N/A','wp-pierre')); ?>'));
                        }
                    }
                }).catch(()=>{});
            }

            if (btn) {
                btn.addEventListener('click', function(){
                    if (!nonce) return; setBusy(true);
                    status.textContent = '<?php echo esc_js(__('Rebuilding…', 'wp-pierre')); ?>';
                    const fd = new FormData(); fd.append('action','pierre_admin_rebuild_catalog'); fd.append('nonce', nonce);
                    fetch(ajaxUrl, { method:'POST', body: fd })
                        .then(r=>r.json())
                        .then(j=>{ status.textContent = (j && j.success) ? '<?php echo esc_js(__('Done.', 'wp-pierre')); ?>' : ((j && (j.data?.message||j.message))||'<?php echo esc_js(__('Failed.', 'wp-pierre')); ?>'); return refreshStatus(); })
                        .catch(()=>{ status.textContent = '<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>'; })
                        .finally(()=> setBusy(false));
                    const poll = setInterval(function(){
                        const fd2 = new FormData(); fd2.append('action','pierre_admin_get_catalog_progress'); fd2.append('nonce', nonce);
                        fetch(ajaxUrl,{method:'POST', body: fd2}).then(r=>r.json()).then(j=>{
                            if (j && j.success && j.data) { status.textContent = '<?php echo esc_js(__('Progress','wp-pierre')); ?>: ' + (j.data.processed||0) + ' / ' + (j.data.total||0) + (j.data.phase?(' ('+j.data.phase+')'):''); }
                        }).catch(()=>{});
                    }, 1000);
                    setTimeout(()=>clearInterval(poll), 120000);
                });
            }
            if (resetBtn) {
                resetBtn.addEventListener('click', function(){
                    if (!nonce) return; if (!confirm('<?php echo esc_js(__('Reset the catalog? This will purge cached pages and index.','wp-pierre')); ?>')) return;
                    setBusy(true);
                    const fd = new FormData(); fd.append('action','pierre_admin_reset_catalog'); fd.append('nonce', nonce);
                    fetch(ajaxUrl, { method:'POST', body: fd })
                        .then(r=>r.json())
                        .then(j=>{ status.textContent = (j && j.success) ? ((j.data?.message)||'<?php echo esc_js(__('Done.','wp-pierre')); ?>') : ((j && (j.data?.message||j.message))||'<?php echo esc_js(__('Failed.', 'wp-pierre')); ?>'); return refreshStatus(); })
                        .catch(()=>{ status.textContent = '<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>'; })
                        .finally(()=> setBusy(false));
                });
            }
            if (sched) {
                sched.addEventListener('click', function(){
                    if (!nonce) return; setBusy(true);
                    const fd = new FormData(); fd.append('action','pierre_admin_schedule_catalog'); fd.append('nonce', nonce);
                    fetch(ajaxUrl, { method:'POST', body: fd })
                        .then(r=>r.json())
                        .then(j=>{ status.textContent = (j && j.success) ? '<?php echo esc_js(__('Scheduled.', 'wp-pierre')); ?>' : ((j && (j.data?.message||j.message))||'<?php echo esc_js(__('Failed.', 'wp-pierre')); ?>'); return refreshStatus(); })
                        .catch(()=>{ status.textContent = '<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>'; })
                        .finally(()=> setBusy(false));
                });
            }
            const form = document.getElementById('pierre-catalog-settings-form');
            const saveStatus = document.getElementById('pierre-catalog-save-status');
            if (form) {
                form.addEventListener('submit', function(ev){
                    ev.preventDefault(); if (!nonce) return;
                    saveStatus.textContent = '<?php echo esc_js(__('Saving…', 'wp-pierre')); ?>';
                    const fd = new FormData(form); fd.append('action','pierre_admin_save_catalog_settings'); fd.append('nonce', nonce);
                    fetch(ajaxUrl, { method:'POST', body: fd })
                        .then(r=>r.json())
                        .then(j=>{ saveStatus.textContent = (j && j.success) ? '<?php echo esc_js(__('Saved.', 'wp-pierre')); ?>' : ((j && (j.data?.message||j.message))||'<?php echo esc_js(__('Failed.', 'wp-pierre')); ?>'); })
                        .catch(()=>{ saveStatus.textContent = '<?php echo esc_js(__('Network error.', 'wp-pierre')); ?>'; });
                });
            }
        })();
        </script>

        
    </div>

    <div class="columns-2">
        <div class="pierre-card">
            <h2><?php echo esc_html__('Plugin Admin UI', 'wp-pierre'); ?></h2>
            <form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" id="pierre-ui-settings">
                <?php settings_fields( 'pierre_settings_group' ); ?>
                <?php 
                // Afficher la description de la section
                \Pierre\Settings\Settings::render_section_ui();
                // Afficher les champs de la section dans un tableau
                ?>
                <table class="form-table" role="presentation">
                    <?php do_settings_fields( 'pierre-settings', 'pierre_section_ui' ); ?>
                </table>
                <div class="pierre-form-actions pierre-mt-8">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Save Admin UI', 'wp-pierre'); ?></button>
                </div>
            </form>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Plugin System Status', 'wp-pierre'); ?></h2>
            <?php if (isset($data['cron_status'])): ?>
            <div class="pierre-system-status">
                <p><strong><?php echo esc_html__('Active:', 'wp-pierre'); ?></strong> 
                    <?php $is_active = !empty($data['cron_status']['active']); ?>
                    <span class="<?php echo $is_active ? 'pierre-status-ok' : 'pierre-status-ko'; ?>"><?php echo $is_active ? esc_html__('Yes','wp-pierre') : esc_html__('No','wp-pierre'); ?></span>
                </p>
                <p><strong><?php echo esc_html__('Last Run:', 'wp-pierre'); ?></strong> 
                    <?php $t = (int) get_option('pierre_last_surv_run'); echo $t ? esc_html( date_i18n( get_option('date_format').' '.get_option('time_format'), $t ) ) : esc_html__('N/A','wp-pierre'); ?>
                </p>
                <p><strong><?php echo esc_html__('Last Run Duration:', 'wp-pierre'); ?></strong> 
                    <?php $d = (int) get_option('pierre_last_surv_duration_ms'); echo $d ? esc_html( $d . ' ms' ) : esc_html__('N/A','wp-pierre'); ?>
                </p>
                <p><strong><?php echo esc_html__('Next Run:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['cron_status']['next_run'] ?? esc_html__('Not scheduled', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Next cleanup run:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['cron_status']['next_cleanup'] ?? esc_html__('Not scheduled', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Next digest run:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['cron_status']['next_digest'] ?? esc_html__('Not scheduled', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Last digest run:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['cron_status']['last_digest'] ?? esc_html__('N/A', 'wp-pierre')); ?></p>
                <p><strong><?php echo esc_html__('Last cleanup run:', 'wp-pierre'); ?></strong> 
                    <?php $lc = (int) get_option('pierre_last_cleanup_run', 0); echo $lc ? esc_html( date_i18n( get_option('date_format').' '.get_option('time_format'), $lc ) ) : esc_html__('N/A','wp-pierre'); ?>
                </p>
                <p><strong><?php echo esc_html__('Last Digest Duration:', 'wp-pierre'); ?></strong> 
                    <?php $dd = (int) get_option('pierre_last_digest_duration_ms'); echo $dd ? esc_html( $dd . ' ms' ) : esc_html__('N/A','wp-pierre'); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="pierre-system-actions">
                <div class="pierre-row">
                    <button class="button" id="pierre-flush-cache" title="<?php echo esc_attr__('Clear internal caches (locales/projects). Safe operation.', 'wp-pierre'); ?>">
                        <?php echo esc_html__('Flush Cache', 'wp-pierre'); ?>
                    </button>
                    <button class="button" id="pierre-run-cleanup-now" title="<?php echo esc_attr__('Run cleanup now (cooldown applies).', 'wp-pierre'); ?>"><?php echo esc_html__('Run cleanup now', 'wp-pierre'); ?></button>
                </div>
                <p id="pierre-progress-line" class="description pierre-mt-8"><?php echo esc_html__('Progress: idle', 'wp-pierre'); ?></p>
                <div class="pierre-row pierre-mt-8">
                    <button class="button pierre-button-danger" id="pierre-reset-settings" title="<?php echo esc_attr__('Restore Pierre’s settings to factory defaults.', 'wp-pierre'); ?>">
                        <?php echo esc_html__('Reset to Defaults', 'wp-pierre'); ?>
                    </button>
                    <button class="button pierre-button-danger" id="pierre-clear-all-data" title="<?php echo esc_attr__('Erase ALL Pierre data (irreversible).', 'wp-pierre'); ?>">
                        <?php echo esc_html__('Clear All Data', 'wp-pierre'); ?>
                    </button>
                </div>
            </div>
            <div class="pierre-help pierre-mt-8">
                <p><strong><?php echo esc_html__('About actions', 'wp-pierre'); ?>:</strong></p>
                <ul class="ul-disc pierre-list">
                    <li><?php echo esc_html__('“Flush Cache” clears internal caches (locales/projects).', 'wp-pierre'); ?></li>
                    <li><?php echo esc_html__('“Run cleanup now” executes the scheduled maintenance cleanup immediately.', 'wp-pierre'); ?></li>
                    <li class="pierre-danger"><?php echo esc_html__('“Reset to Defaults” restores Pierre’s settings to factory values.', 'wp-pierre'); ?></li>
                    <li class="pierre-danger"><?php echo esc_html__('“Clear All Data” removes all stored data (irreversible).', 'wp-pierre'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    (function(){
        // Optional: Enhance UI form with AJAX (progressive enhancement)
        // Form works without JS via native POST
        const uiForm = document.getElementById('pierre-ui-settings');
        if (uiForm) {
            uiForm.addEventListener('submit', function(e){
                // Allow native POST to work, but can enhance with AJAX here if needed
                // For now, let native POST handle it
            });
        }
    })();
    </script>

    <?php elseif ( $current_tab === 'discovery' ): ?>
        <?php include PIERRE_PLUGIN_DIR . 'templates/admin/settings-discovery.php'; ?>
    <?php elseif ( $current_tab === 'global-webhook' ): ?>
        <?php include PIERRE_PLUGIN_DIR . 'templates/admin/settings-global-webhook.php'; ?>
    <?php elseif ( $current_tab === 'projects-discovery' ): ?>
        <?php include PIERRE_PLUGIN_DIR . 'templates/admin/settings-projects-discovery.php'; ?>
    <?php endif; ?>

</div>
