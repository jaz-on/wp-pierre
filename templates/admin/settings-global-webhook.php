<?php
/**
 * Pierre's settings: Global Webhook tab 🪨
 *
 * @package Pierre
 * @since 1.0.0
 */

if (!defined('ABSPATH')) { exit; }
$data = $GLOBALS['pierre_admin_template_data'] ?? [];
$settings = $data['settings'] ?? [];
?>

<div class="wrap">
    <div class="pierre-grid pierre-grid--cards">
        <div class="pierre-card">
            <h2><?php echo esc_html__('Global Webhook', 'wp-pierre'); ?></h2>
            <?php if (isset($data['notifier_status'])): ?>
                <?php $is_ready = !empty($data['notifier_status']['ready']); ?>
                <p>
                    <strong><?php echo esc_html__('Ready:', 'wp-pierre'); ?></strong>
                    <span class="<?php echo $is_ready ? 'pierre-status-ok' : 'pierre-status-ko'; ?>"><?php echo esc_html($is_ready ? __('Yes', 'wp-pierre') : __('No', 'wp-pierre')); ?></span>
                </p>
                <p><strong><?php echo esc_html__('Status:', 'wp-pierre'); ?></strong> <?php echo esc_html($data['notifier_status']['message'] ?? esc_html__('No status message', 'wp-pierre')); ?></p>
                <?php $last = get_option('pierre_last_global_webhook_test', []); if (!empty($last) && !empty($last['time'])): ?>
                    <p class="pierre-help">
                        <?php echo esc_html__('Last test:', 'wp-pierre'); ?>
                        <?php echo esc_html( gmdate('Y-m-d H:i:s', (int) $last['time']) ); ?> (<?php echo !empty($last['success']) ? esc_html__('success', 'wp-pierre') : esc_html__('failed', 'wp-pierre'); ?>)
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Add a global webhook', 'wp-pierre'); ?></h2>
            <div class="pierre-form-wide">
                <div class="pierre-form-group">
                    <label for="global_webhook_url"><?php echo esc_html__('Webhook URL', 'wp-pierre'); ?></label>
                    <input type="url" class="regular-text" id="global_webhook_url" name="global_webhook_url" value="<?php echo esc_attr($settings['global_webhook']['webhook_url'] ?? ($settings['slack_webhook_url'] ?? '')); ?>" placeholder="https://hooks.slack.com/services/..." required>
                    <div class="pierre-help"><?php echo esc_html__('Destination URL. Optionally restrict by scopes below (leave empty for ALL).', 'wp-pierre'); ?></div>
                </div>

                <div class="pierre-form-group">
                    <label><input type="checkbox" id="global_webhook_enabled" name="global_webhook_enabled" <?php checked(!empty($settings['global_webhook']['enabled'] ?? true)); ?>> <?php echo esc_html__('Enable Global Webhook', 'wp-pierre'); ?></label>
                </div>

                <div class="pierre-form-actions">
                    <button type="button" class="button button-primary" id="pierre-save-test-webhook"><?php echo esc_html__('Save & test URL', 'wp-pierre'); ?></button>
                    <span id="pierre-slack-url-status" class="pierre-ml-8"></span>
                </div>
            </div>
        </div>

        <div class="pierre-card">
            <h2><?php echo esc_html__('Global webhook settings and scope', 'wp-pierre'); ?></h2>
            <form class="pierre-form-wide" id="pierre-slack-settings">
                <h3><?php echo esc_html__('Event types', 'wp-pierre'); ?></h3>
                <div class="pierre-form-group pierre-fieldset">
                    <?php $gwtypes = $settings['global_webhook']['types'] ?? ['new_strings','completion_update','needs_attention','milestone']; ?>
                    <label><input type="checkbox" name="global_webhook_types[]" value="new_strings" <?php checked(in_array('new_strings',$gwtypes,true)); ?> /> new_strings</label>
                    <label><input type="checkbox" name="global_webhook_types[]" value="completion_update" <?php checked(in_array('completion_update',$gwtypes,true)); ?> /> completion_update</label>
                    <label><input type="checkbox" name="global_webhook_types[]" value="needs_attention" <?php checked(in_array('needs_attention',$gwtypes,true)); ?> /> needs_attention</label>
                    <label><input type="checkbox" name="global_webhook_types[]" value="milestone" <?php checked(in_array('milestone',$gwtypes,true)); ?> /> milestone</label>
                </div>

                <h3><?php echo esc_html__('Thresholds & Digest', 'wp-pierre'); ?></h3>
                <div class="pierre-form-group pierre-fieldset">
                    <p><label><?php echo esc_html__('New strings threshold', 'wp-pierre'); ?>
                        <input type="number" name="global_webhook_threshold" min="0" value="<?php echo esc_attr($settings['global_webhook']['threshold'] ?? ($settings['notification_defaults']['new_strings_threshold'] ?? 20)); ?>" />
                    </label></p>
                    <p><label><?php echo esc_html__('Milestones (comma-separated)', 'wp-pierre'); ?>
                        <input type="text" name="global_webhook_milestones" value="<?php echo esc_attr(implode(',', $settings['global_webhook']['milestones'] ?? ($settings['notification_defaults']['milestones'] ?? [50,80,100]))); ?>" />
                    </label></p>
                    <?php $gwmode = $settings['global_webhook']['mode'] ?? 'immediate'; ?>
                    <p><label><?php echo esc_html__('Mode', 'wp-pierre'); ?>
                        <select name="global_webhook_mode">
                            <option value="immediate" <?php selected($gwmode,'immediate'); ?>>immediate</option>
                            <option value="digest" <?php selected($gwmode,'digest'); ?>>digest</option>
                        </select>
                    </label></p>
                    <?php $gwd = $settings['global_webhook']['digest'] ?? []; $gwdt = $gwd['type'] ?? 'interval'; ?>
                    <p><label><?php echo esc_html__('Digest Type', 'wp-pierre'); ?>
                        <select name="global_webhook_digest_type">
                            <option value="interval" <?php selected($gwdt,'interval'); ?>>interval</option>
                            <option value="fixed_time" <?php selected($gwdt,'fixed_time'); ?>>fixed_time</option>
                        </select>
                    </label></p>
                    <p><label><?php echo esc_html__('Interval (minutes)', 'wp-pierre'); ?>
                        <input type="number" min="15" name="global_webhook_digest_interval_minutes" value="<?php echo esc_attr((int)($gwd['interval_minutes'] ?? 60)); ?>" />
                    </label></p>
                    <p><label><?php echo esc_html__('Fixed time (HH:MM)', 'wp-pierre'); ?>
                        <input type="time" name="global_webhook_digest_fixed_time" value="<?php echo esc_attr($gwd['fixed_time'] ?? '09:00'); ?>" />
                    </label></p>
                    <p class="description"><?php echo esc_html__('Digest fields are enabled only when Mode is set to "digest". Choose interval or fixed time.', 'wp-pierre'); ?></p>
                </div>

                <h3><?php echo esc_html__('Scopes (optional)', 'wp-pierre'); ?></h3>
                <div class="pierre-form-group pierre-fieldset">
                    <p class="description"><?php echo esc_html__('Restrict delivery to specific locales and/or projects. Leave empty for all.', 'wp-pierre'); ?></p>
                    <?php $actloc = $data['active_locales'] ?? []; $sc_loc = $settings['global_webhook']['scopes']['locales'] ?? []; ?>
                    <p><label><?php echo esc_html__('Locales', 'wp-pierre'); ?><br/>
                        <select name="global_webhook_scopes_locales[]" multiple size="5">
                            <?php foreach ($actloc as $lc): ?>
                                <option value="<?php echo esc_attr($lc); ?>" <?php selected(in_array($lc, $sc_loc, true)); ?>><?php echo esc_html($lc); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label></p>
                    <?php $sc_proj = $settings['global_webhook']['scopes']['projects'] ?? []; ?>
                    <p><label><?php echo esc_html__('Projects (type,slug per line)', 'wp-pierre'); ?><br/>
                        <textarea name="global_webhook_scopes_projects" rows="5"><?php echo esc_textarea(implode("\n", array_map(function($p){ return ($p['type']??'').','.($p['slug']??''); }, $sc_proj))); ?></textarea>
                    </label></p>
                </div>

                <h3 class="pierre-mt-16"><?php echo esc_html__('Preview', 'wp-pierre'); ?></h3>
                <pre id="pierre-slack-preview" class="pierre-preview-box"></pre>

                <div class="pierre-form-actions">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Save settings and scope', 'wp-pierre'); ?></button>
                    <button type="button" class="button" id="pierre-preview-slack"><?php echo esc_html__('Preview message', 'wp-pierre'); ?></button>
                    <button type="button" class="button" id="pierre-test-slack" <?php echo empty($settings['global_webhook']['webhook_url'] ?? ($settings['slack_webhook_url'] ?? '')) ? 'disabled' : ''; ?>><?php echo esc_html__('Send test via Webhook', 'wp-pierre'); ?></button>
                </div>
                <p class="description pierre-mt-16">
                    <?php
                    $gwtypes = $settings['global_webhook']['types'] ?? ['new_strings','completion_update','needs_attention','milestone'];
                    $types = implode(', ', $gwtypes);
                    $sc_loc = $settings['global_webhook']['scopes']['locales'] ?? [];
                    $sc_proj = $settings['global_webhook']['scopes']['projects'] ?? [];
                    $scopeLoc = !empty($sc_loc) ? implode(', ', $sc_loc) : __('All locales', 'wp-pierre');
                    $scopeProj = !empty($sc_proj) ? __('Some projects', 'wp-pierre') : __('All projects', 'wp-pierre');
                    echo esc_html(
                      sprintf(
                        // translators: 1: whether active (Yes/No), 2: types list, 3: locales scope, 4: projects scope
                        __('Active: %1$s | Types: %2$s | Scope: %3$s / %4$s', 'wp-pierre'),
                        !empty($settings['global_webhook']['enabled']) ? __('Yes','wp-pierre') : __('No','wp-pierre'),
                        $types, $scopeLoc, $scopeProj
                      )
                    );
                    ?>
                </p>
                <details class="pierre-mt-16">
                    <summary><?php echo esc_html__('Help: Scopes', 'wp-pierre'); ?></summary>
                    <p class="description"><?php echo esc_html__('Leave empty to apply to all locales/projects. Otherwise, restrict to the listed values.', 'wp-pierre'); ?></p>
                </details>
            </form>
        </div>
    </div>

</div>


