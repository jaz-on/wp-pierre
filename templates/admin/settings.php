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

<div class="wrap">
    <div class="pierre-visually-hidden" role="status" aria-live="polite" id="pierre-aria-live"></div>
    <!-- Settings page header is handled by render_settings_page() -->

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
            <h2><?php echo esc_html__('Global Surveillance Settings', 'wp-pierre'); ?></h2>
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
                <div class="pierre-form-group">
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
                
                <div class="pierre-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Save Settings', 'wp-pierre'); ?>
                    </button>
                    <button type="button" class="button" id="pierre-run-now" title="<?php echo esc_attr__('Force a surveillance check now (ignores the global enable switch).', 'wp-pierre'); ?>"><?php echo esc_html__('Run surveillance now', 'wp-pierre'); ?></button>
                    <button type="button" class="button pierre-button-danger" id="pierre-abort-run"><?php echo esc_html__('Stop current run', 'wp-pierre'); ?></button>
                </div>
            </form>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Global Webhook Settings', 'wp-pierre'); ?></h2>
            <?php 
            $global_webhook = $settings['global_webhook']['webhook_url'] ?? ($settings['slack_webhook_url'] ?? '');
            $global_hook_empty = empty($global_webhook);
            $settings_opt = get_option('pierre_settings', []);
            $local_hooks = (array)($settings_opt['locales_slack'] ?? []);
            $all_empty = $global_hook_empty && (empty(array_filter($local_hooks)));
            if ($all_empty): ?>
                <div class="notice notice-warning">
                    <p><strong><?php echo esc_html__('No Slack webhook configured (global or per-locale). Notifications will not be delivered.', 'wp-pierre'); ?></strong></p>
                </div>
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
                </fieldset>
                <details class="pierre-mt-16">
                    <summary><?php echo esc_html__('Help: Digest mode', 'wp-pierre'); ?></summary>
                    <p class="description"><?php echo esc_html__('Digest mode groups notifications and sends them periodically (by interval or fixed time).', 'wp-pierre'); ?></p>
                </details>
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
            <h2><?php echo esc_html__('Global Locales Discovery Settings', 'wp-pierre'); ?></h2>
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
            <p class="pierre-mt-8 pierre-mb-8"><?php echo esc_html(sprintf(__('Anomalies detected: %d', 'wp-pierre'), (int)$count)); ?></p>
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
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Code', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Label', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Translate Slug', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Rosetta', 'wp-pierre'); ?></th>
                            <th><?php echo esc_html__('Issues', 'wp-pierre'); ?></th>
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
            <h2><?php echo esc_html__('Global Projects Discovery Settings', 'wp-pierre'); ?></h2>
            <p class="description"><?php echo esc_html__('Options for projects discovery will appear here (sources, mapping rules, auto-mapping, bulk behaviors).', 'wp-pierre'); ?></p>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Global System Status', 'wp-pierre'); ?></h2>
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

</div>
