<?php
/**
 * Pierre's admin settings template - he manages his configuration! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

use Pierre\Admin\UI;

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

    <?php if (defined('PIERRE_COMPOSER_MISSING') && PIERRE_COMPOSER_MISSING && defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')): ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong><?php echo esc_html__('Developer tip:', 'wp-pierre'); ?></strong> <?php echo esc_html__('Composer autoload is missing. Run "composer dump-autoload" in the plugin folder for better performance.', 'wp-pierre'); ?></p>
            <p class="description"><?php echo esc_html__('How these settings work.', 'wp-pierre'); ?></p>
        </div>
    <?php endif; ?>

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
            <form class="pierre-form-wide" id="pierre-surveillance-settings">
                <?php $enabled_default = array_key_exists('surveillance_enabled', $settings) ? !empty($settings['surveillance_enabled']) : true; ?>
                <h3><?php echo esc_html__('Global toggle', 'wp-pierre'); ?></h3>
                <div class="pierre-form-group pierre-row pierre-mb-8">
                    <label for="surveillance_enabled">
                        <input type="checkbox" 
                               id="surveillance_enabled" 
                               name="surveillance_enabled" 
                               <?php checked($enabled_default); ?>>
                        <?php echo esc_html__('Enable Surveillance', 'wp-pierre'); ?>
                    </label>
                    <span class="<?php echo $enabled_default ? 'pierre-status-ok' : 'pierre-status-ko'; ?>">
                        <?php echo $enabled_default ? esc_html__('Enabled', 'wp-pierre') : esc_html__('Disabled', 'wp-pierre'); ?>
                    </span>
                    <div class="pierre-help">
                        <?php echo esc_html__('Pause/resume scheduled checks globally. You can still run “Force surveillance now” from System Status when paused.', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-group pierre-mb-8">
                    <label for="auto_start_surveillance">
                        <input type="checkbox" 
                               id="auto_start_surveillance" 
                               name="auto_start_surveillance" 
                               <?php checked(!empty($settings['auto_start_surveillance'])); ?>>
                        <?php echo esc_html__('Run a first check right after activation', 'wp-pierre'); ?>
                    </label>
                    <div class="pierre-help">
                        <?php echo esc_html__('Useful to validate the setup right after install. If no locales/projects exist yet, the check exits quickly. Scheduled checks still apply.', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <h3 class="pierre-mt-16"><?php echo esc_html__('Scheduling', 'wp-pierre'); ?></h3>
                <div class="pierre-form-group" id="surv-scheduling-group">
                    <label for="surveillance_interval"><?php echo esc_html__('Surveillance interval (minutes):', 'wp-pierre'); ?></label>
                    <select id="surveillance_interval" name="surveillance_interval" class="wp-core-ui">
                        <option value="5" <?php selected(($settings['surveillance_interval'] ?? 15) == 5); ?>><?php echo esc_html__('5 minutes', 'wp-pierre'); ?></option>
                        <option value="15" <?php selected(($settings['surveillance_interval'] ?? 15) == 15); ?>><?php echo esc_html__('15 minutes', 'wp-pierre'); ?></option>
                        <option value="30" <?php selected(($settings['surveillance_interval'] ?? 15) == 30); ?>><?php echo esc_html__('30 minutes', 'wp-pierre'); ?></option>
                        <option value="60" <?php selected(($settings['surveillance_interval'] ?? 15) == 60); ?>><?php echo esc_html__('1 hour', 'wp-pierre'); ?></option>
                        <option value="120" <?php selected(($settings['surveillance_interval'] ?? 15) == 120); ?>><?php echo esc_html__('2 hours', 'wp-pierre'); ?></option>
                    </select>
                    <div class="pierre-help">
                        <?php echo esc_html__('How often Pierre checks for changes. Shorter = faster detection but more load. Recommended: 15–60 min for production; 5–15 min for testing.', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="request_timeout"><?php echo esc_html__('HTTP Request Timeout (seconds):', 'wp-pierre'); ?></label>
                    <input type="number" class="regular-text"
                           id="request_timeout" 
                           name="request_timeout" 
                           value="<?php echo esc_attr($settings['request_timeout'] ?? 30); ?>"
                           min="3" 
                           max="120">
                    <div class="pierre-help">
                        <?php echo esc_html__('Timeout for outbound HTTP calls to WP.org APIs (default 30s).', 'wp-pierre'); ?>
                    </div>
                </div>

                <div class="pierre-form-group">
                    <label for="max_projects_per_check"><?php echo esc_html__('Maximum Projects per Check:', 'wp-pierre'); ?></label>
                    <input type="number" class="regular-text"
                           id="max_projects_per_check" 
                           name="max_projects_per_check" 
                           value="<?php echo esc_attr($settings['max_projects_per_check'] ?? 50); ?>"
                           min="1">
                    <div class="pierre-help">
                        <?php echo esc_html__('Caps the number of projects processed per run to spread load. Higher = faster catch‑up but heavier bursts. Recommended: 20–100 depending on server size (default 50).', 'wp-pierre'); ?>
                    </div>
                </div>
                
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
            $global_webhook = $settings['global_webhook']['webhook_url'] ?? ($settings['slack_webhook_url'] ?? '');
            $global_hook_empty = empty($global_webhook);
            $settings_opt = \Pierre\Settings\Settings::all();
            $local_hooks = (array)($settings_opt['locales_slack'] ?? []);
            $all_empty = $global_hook_empty && (empty(array_filter($local_hooks)));
            if ($all_empty): ?>
                <?php echo UI::notice('warning', '<strong>' . esc_html__('No Slack webhook configured (global or per-locale). Notifications will not be delivered.', 'wp-pierre') . '</strong><br/>'
                    . '<a href="' . esc_url(admin_url('admin.php?page=pierre-settings#global-webhook')) . '">' . esc_html__('Add a Global Webhook', 'wp-pierre') . '</a>'
                    . ' · '
                    . '<a href="' . esc_url(admin_url('admin.php?page=pierre-locales')) . '">' . esc_html__('Or per-locale: go to Locales → Manage → Slack Webhook', 'wp-pierre') . '</a>'
                    , true);
                ?>
            <?php endif; ?>
            <form class="pierre-form-compact" id="pierre-notification-settings">
                <fieldset class="pierre-form-group pierre-fieldset">
                    <legend><?php echo esc_html__('Defaults (Global)', 'wp-pierre'); ?></legend>
                    <p>
                        <label for="new_strings_threshold"><?php echo esc_html__('New strings threshold (default):', 'wp-pierre'); ?></label>
                        <input type="number" id="new_strings_threshold" name="new_strings_threshold" min="0" value="<?php echo esc_attr($settings['notification_defaults']['new_strings_threshold'] ?? 20); ?>" />
                    </p>
                    <p>
                        <label for="milestones"><?php echo esc_html__('Milestones (comma-separated):', 'wp-pierre'); ?></label>
                        <input type="text" id="milestones" name="milestones" value="<?php echo esc_attr(implode(',', $settings['notification_defaults']['milestones'] ?? [50,80,100])); ?>" />
                    </p>
                    <p>
                    <label for="mode"><?php echo esc_html__('Mode:', 'wp-pierre'); ?></label>
                        <?php $mode = $settings['notification_defaults']['mode'] ?? 'immediate'; ?>
                        <select id="mode" name="mode">
                            <option value="immediate" <?php selected($mode, 'immediate'); ?>>immediate</option>
                            <option value="digest" <?php selected($mode, 'digest'); ?>>digest</option>
                        </select>
                        <div class="pierre-help">
                            <?php echo esc_html__('Immediate: send notifications as events occur. Digest: group and send at intervals or a fixed time.', 'wp-pierre'); ?>
                        </div>
                    </p>
                    <p>
                    <label for="digest_type"><?php echo esc_html__('Digest Type:', 'wp-pierre'); ?></label>
                        <?php $dtype = $settings['notification_defaults']['digest']['type'] ?? 'interval'; ?>
                        <select id="digest_type" name="digest_type">
                            <option value="interval" <?php selected($dtype, 'interval'); ?>>interval</option>
                            <option value="fixed_time" <?php selected($dtype, 'fixed_time'); ?>>fixed_time</option>
                        </select>
                        <div class="pierre-help">
                            <?php echo esc_html__('Interval: every N minutes. Fixed time: once per day at HH:MM (site timezone).', 'wp-pierre'); ?>
                        </div>
                    </p>
                    <p>
                    <label for="digest_interval_minutes"><?php echo esc_html__('Interval (minutes):', 'wp-pierre'); ?></label>
                        <input type="number" id="digest_interval_minutes" name="digest_interval_minutes" min="15" value="<?php echo esc_attr((int)($settings['notification_defaults']['digest']['interval_minutes'] ?? 60)); ?>" />
                        <div class="pierre-help">
                            <?php echo esc_html__('Used only with Digest/Interval. Minimum 15 minutes.', 'wp-pierre'); ?>
                        </div>
                    </p>
                    <p>
                    <label for="digest_fixed_time"><?php echo esc_html__('Fixed time (HH:MM):', 'wp-pierre'); ?></label>
                        <input type="time" id="digest_fixed_time" name="digest_fixed_time" value="<?php echo esc_attr($settings['notification_defaults']['digest']['fixed_time'] ?? '09:00'); ?>" />
                        <div class="pierre-help">
                            <?php echo esc_html__('Used only with Digest/Fixed time. Notification is sent once per day at the set time.', 'wp-pierre'); ?>
                        </div>
                    </p>
                <details class="pierre-mt-16">
                    <summary><?php echo esc_html__('Help: Digest mode', 'wp-pierre'); ?></summary>
                    <p class="description"><?php echo esc_html__('Digest mode groups notifications and sends them periodically (by interval or fixed time).', 'wp-pierre'); ?></p>
                </details>
                </fieldset>
                
                <div class="pierre-form-group">
                    <label for="notification_types"><?php echo esc_html__('Notification Types:', 'wp-pierre'); ?></label>
                    <div class="pierre-checkbox-group">
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="new_strings" 
                                   <?php checked(in_array('new_strings', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            <?php echo esc_html__('New Strings Detected', 'wp-pierre'); ?>
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="completion_update" 
                                   <?php checked(in_array('completion_update', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            <?php echo esc_html__('Completion Updates', 'wp-pierre'); ?>
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="needs_attention" 
                                   <?php checked(in_array('needs_attention', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            <?php echo esc_html__('Needs Attention', 'wp-pierre'); ?>
                        </label>
                        <label>
                            <input type="checkbox" 
                                   name="notification_types[]" 
                                   value="errors" 
                                   <?php checked(in_array('errors', $settings['notification_types'] ?? ['new_strings', 'completion_update'])); ?>>
                            <?php echo esc_html__('Errors', 'wp-pierre'); ?>
                        </label>
                    </div>
                    <div class="pierre-help">
                        <?php echo esc_html__('Select which types of notifications to send', 'wp-pierre'); ?>
                    </div>
                </div>
                
                <div class="pierre-form-group">
                    <label for="notification_threshold"><?php echo esc_html__('Completion Threshold (%):', 'wp-pierre'); ?></label>
                    <input type="number" class="regular-text"
                           id="notification_threshold" 
                           name="notification_threshold" 
                           value="<?php echo esc_attr($settings['notification_threshold'] ?? 80); ?>"
                           min="0" 
                           max="100">
                    <div class="pierre-help">
                        <?php echo esc_html__('Only send notifications when completion is above this threshold', 'wp-pierre'); ?>
                    </div>
                </div>
                
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
                    <?php echo UI::tableCaption(esc_html__('Locales anomalies', 'wp-pierre')); ?>
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
            <div class="pierre-form-group">
                <?php $menu_icon = $settings['ui']['menu_icon'] ?? 'emoji'; ?>
                <h3 class="pierre-mt-8"><?php echo esc_html__('Menu icon', 'wp-pierre'); ?></h3>
                <fieldset class="pierre-form-group" role="radiogroup" aria-label="<?php echo esc_attr__('Menu icon', 'wp-pierre'); ?>">
                    <label class="pierre-ml-8">
                        <input type="radio" name="menu_icon_choice" value="emoji" <?php checked($menu_icon === 'emoji'); ?>>
                        <span aria-hidden="true" class="fs-18 va-middle">🪨</span>
                        <span class="pierre-ml-8"><?php echo esc_html__('Emoji (default)', 'wp-pierre'); ?></span>
                    </label>
                    <label class="pierre-ml-8">
                        <input type="radio" name="menu_icon_choice" value="dashicons" <?php checked($menu_icon === 'dashicons'); ?>>
                        <span class="dashicons dashicons-translation va-middle" aria-hidden="true"></span>
                        <span class="pierre-ml-8"><?php echo esc_html__('Dashicons: translation', 'wp-pierre'); ?></span>
                    </label>
                </fieldset>
                <h3 class="pierre-mt-8"><?php echo esc_html__('Plugin name', 'wp-pierre'); ?></h3>
                <?php $plugin_name = isset($settings['ui']['plugin_name']) ? (string)$settings['ui']['plugin_name'] : 'Pierre'; ?>
                <div class="pierre-form-group">
                    <label for="plugin_name_choice" class="pierre-ml-8"><?php echo esc_html__('Displayed name in UI:', 'wp-pierre'); ?></label>
                    <select id="plugin_name_choice" name="plugin_name_choice" class="wp-core-ui">
                        <?php $names = array('Pierre','Pieter','Peter','Peio','Pedro','Πέτρος','Pier','Pietro','Piotr');
                        foreach ($names as $n): ?>
                            <option value="<?php echo esc_attr($n); ?>" <?php selected($plugin_name === $n); ?>><?php echo esc_html($n); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pierre-help">
                    <?php echo esc_html__('Emoji rendering can vary by platform. Use Dashicons for consistent appearance. “Clear All Data” removes all stored data (irreversible).', 'wp-pierre'); ?>
                </div>
                <div class="pierre-form-actions pierre-mt-8">
                    <button type="button" class="button button-primary" id="pierre-save-admin-ui"><?php echo esc_html__('Save Admin UI', 'wp-pierre'); ?></button>
                </div>
            </div>
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
        const btn = document.getElementById('pierre-save-admin-ui');
        if (!btn) return;
        btn.addEventListener('click', function(){
            const selected = document.querySelector('input[name="menu_icon_choice"]:checked');
            if (!selected) return;
            const fd = new FormData();
            fd.append('action','pierre_admin_save_settings');
            fd.append('nonce', (window.pierreAdminL10n && window.pierreAdminL10n.nonce) ? window.pierreAdminL10n.nonce : '');
            fd.append('menu_icon', selected.value);
            const nameSel = document.getElementById('plugin_name_choice');
            if (nameSel && nameSel.value) { fd.append('plugin_name', nameSel.value); }
            btn.disabled = true;
            const original = btn.textContent; btn.textContent = 'Saving...';
            fetch((window.pierreAdminL10n && window.pierreAdminL10n.ajaxUrl) ? window.pierreAdminL10n.ajaxUrl : (window.ajaxurl || ''), { method:'POST', body: fd })
              .then(r=>r.json())
              .then(j=>{
                const ok = j && j.success;
                const msg = (j && (j.data?.message||j.message)) || (ok ? 'Saved.' : 'Failed.');
                if (window.pierreNotice) { window.pierreNotice(ok ? 'success':'error', msg); } else { alert(msg); }
                if (ok) { setTimeout(()=>location.reload(), 300); }
              })
              .catch(()=>{ if (window.pierreNotice) { window.pierreNotice('error','Network error'); } else { alert('Network error'); } })
              .finally(()=>{ btn.disabled=false; btn.textContent=original; });
        });
    })();
    </script>

</div>
