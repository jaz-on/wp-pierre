/**
 * Pierre's admin JavaScript - he makes his admin interface interactive! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

(function($) {
    'use strict';
    // Settings: Slack (global) save + test
    (function(){
        const slackForm = document.getElementById('pierre-slack-settings');
        const testBtn = document.getElementById('pierre-test-slack');
        if (slackForm) {
            slackForm.addEventListener('submit', function(e){
                e.preventDefault();
                const fd = new FormData(slackForm);
                fd.append('action', 'pierre_admin_save_settings');
                fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(json=>{
                        const msg = (json && (json.data?.message || json.message)) || (json.success ? 'Saved.' : 'Failed.');
                        alert(msg);
                    })
                    .catch(()=>alert('Network error.'));
            });
        }
        // Test button handled in dedicated Slack handler below
    })();

    // Settings: Surveillance and Notification forms save
    (function(){
        function wireForm(id){
            const form = document.getElementById(id);
            if (!form) return;
            form.addEventListener('submit', function(e){
                e.preventDefault();
                const fd = new FormData(form);
                fd.append('action', 'pierre_admin_save_settings');
                fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(json=>{
                        const msg = (json && (json.data?.message || json.message)) || (json.success ? 'Saved.' : 'Failed.');
                        alert(msg);
                    })
                    .catch(()=>alert('Network error.'));
            });
        }
        wireForm('pierre-surveillance-settings');
        wireForm('pierre-notification-settings');
    })();

    // Settings: Security actions (General tab)
    (function(){
        function bindBtn(id, action, nonceToUse){
            const btn = document.getElementById(id);
            if (!btn) return;
            btn.addEventListener('click', function(){
                if (id === 'pierre-clear-all-data' && !confirm('Clear ALL Pierre data?')) return;
                const fd = new FormData();
                fd.append('action', action);
                fd.append('nonce', nonceToUse || window.pierreAdminL10n?.nonce || '');
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(json=>{
                        const msg = (json && (json.data?.message || json.message)) || (json.success ? 'Done.' : 'Failed.');
                        alert(msg);
                    })
                    .catch(()=>alert('Network error.'));
            });
        }
        bindBtn('pierre-flush-cache', 'pierre_flush_cache', window.pierreAdminL10n?.nonceAjax);
        bindBtn('pierre-reset-settings', 'pierre_reset_settings', window.pierreAdminL10n?.nonceAjax);
        bindBtn('pierre-clear-all-data', 'pierre_clear_data', window.pierreAdminL10n?.nonceAjax);
    })();

    // Security tab: audit, logs, clear logs
    (function(){
        const wrap = document.querySelector('.wrap');
        function showNotice(msg, type) {
            if (!wrap) { alert(msg); return; }
            const notice = document.createElement('div');
            notice.className = `notice notice-${type} is-dismissible`;
            notice.innerHTML = `<p>${msg}</p>`;
            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'notice-dismiss';
            dismiss.innerHTML = '<span class="screen-reader-text">Dismiss</span>';
            dismiss.addEventListener('click', ()=>notice.remove());
            notice.appendChild(dismiss);
            wrap.prepend(notice);
            setTimeout(()=>{ try { notice.remove(); } catch(e){} }, 8000);
        }
        function handleAction(id, action, confirmMsg){
            const btn = document.getElementById(id);
            if (!btn) return;
            btn.addEventListener('click', function(){
                if (confirmMsg && !confirm(confirmMsg)) return;
                const original = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Processing...';
                const fd = new FormData();
                fd.append('action', action);
                fd.append('nonce', window.pierreAdminL10n?.nonceAjax || window.pierreAdminL10n?.nonce || '');
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(json=>{
                        if (json && json.success) {
                            if (action === 'pierre_security_logs' && json.data?.security_logs) {
                                const logsDiv = document.getElementById('pierre-logs-content');
                                if (logsDiv) {
                                    const logs = json.data.security_logs;
                                    logsDiv.innerHTML = logs.length ? 
                                        '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Timestamp</th><th>Event Type</th><th>Details</th></tr></thead><tbody>' +
                                        logs.map(l=>`<tr><td>${l.timestamp||''}</td><td>${l.event_type||''}</td><td>${JSON.stringify(l.data||{})}</td></tr>`).join('') +
                                        '</tbody></table>' : '<p>No logs found.</p>';
                                }
                                showNotice('Logs loaded.', 'success');
                            } else if (action === 'pierre_security_audit' && json.data?.audit_results) {
                                const auditDiv = document.getElementById('pierre-audit-content');
                                if (auditDiv) {
                                    const audit = json.data.audit_results;
                                    auditDiv.innerHTML = `<div><strong>Score:</strong> ${audit.overall_score||0}%<br/>
                                        <strong>Critical:</strong> ${(audit.critical_issues||[]).length}<br/>
                                        <strong>Warnings:</strong> ${(audit.warnings||[]).length}<br/>
                                        <pre>${JSON.stringify(audit, null, 2)}</pre></div>`;
                                }
                                showNotice('Audit completed.', 'success');
                            } else {
                                showNotice(json.data?.message || json.message || 'Done.', 'success');
                            }
                        } else {
                            showNotice(json.data?.message || json.message || 'Failed.', 'error');
                        }
                    })
                    .catch(()=>showNotice('Network error.', 'error'))
                    .finally(()=>{
                        btn.disabled = false;
                        btn.textContent = original;
                    });
            });
        }
        handleAction('pierre-run-security-audit', 'pierre_security_audit');
        handleAction('pierre-view-security-logs', 'pierre_security_logs');
        handleAction('pierre-clear-security-logs', 'pierre_clear_security_logs', 'Clear all security logs?');
    })();
    
    // Pierre's surveillance test functionality (dry run) - only on Projects page! 🪨
    (function() {
        const testBtn = document.getElementById('pierre-test-surveillance');
        if (!testBtn) return;
        
        const ajaxUrl = document.getElementById('pierre-ajax-url').value;
        const nonce = document.getElementById('pierre-ajax-nonce').value;
        const startBtn = document.getElementById('pierre-start-surveillance');
        const wrap = document.querySelector('.wrap');

        function showNotice(message, type) {
            const notice = document.createElement('div');
            notice.className = `notice notice-${type} is-dismissible`;
            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'notice-dismiss';
            dismiss.innerHTML = `<span class="screen-reader-text">${(window.pierreAdminL10n && window.pierreAdminL10n.dismiss) || 'Dismiss this notice.'}</span>`;
            dismiss.addEventListener('click', () => {
                notice.remove();
            });
            notice.innerHTML = `<p>${message}</p>`;
            notice.appendChild(dismiss);
            wrap && wrap.prepend(notice);
        }

        testBtn.addEventListener('click', function(e) {
            e.preventDefault();
            testBtn.disabled = true;
            const form = new FormData();
            form.append('action', 'pierre_test_surveillance');
            form.append('nonce', nonce);
            fetch(ajaxUrl, { method: 'POST', body: form })
                .then(r => r.json())
                .then(json => {
                    if (json && json.success) {
                        if (startBtn) {
                            startBtn.disabled = false;
                            startBtn.removeAttribute('aria-disabled');
                        }
                        const msg = (json && (json.data?.message || json.message)) || (window.pierreAdminL10n?.dryRunSuccess || 'Dry run succeeded. You can now start surveillance.');
                        showNotice(msg, 'success');
                    } else {
                        let base = (json && (json.data?.message || json.message)) || (window.pierreAdminL10n?.dryRunFailed || 'Dry run failed. Check settings and try again.');
                        const reason = (json && (json.data?.reason || json.reason)) ? `\nReason: ${json.data?.reason || json.reason}` : '';
                        const details = (json && (json.data?.details || json.details)) ? `\nDetails: ${typeof (json.data?.details || json.details) === 'string' ? (json.data?.details || json.details) : JSON.stringify(json.data?.details || json.details)}` : '';
                        showNotice(base + reason + details, 'error');
                    }
                })
                .catch(() => {
                    showNotice(window.pierreAdminL10n?.dryRunError || 'An error occurred during dry run.', 'error');
                })
                .finally(() => {
                    testBtn.disabled = false;
                });
        });
    })();

    // Pierre's per-locale Slack webhook management on Projects page 🪨
    (function(){
        const form = document.getElementById('pierre-locale-slack-form');
        if (!form) return;
        const input = document.getElementById('pierre-locale-slack-webhook');
        const localeSelect = document.getElementById('pierre-locale-select');
        const wrap = document.querySelector('.wrap');

        function showNotice(message, type) {
            const notice = document.createElement('div');
            notice.className = `notice notice-${type} is-dismissible`;
            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'notice-dismiss';
            dismiss.innerHTML = `<span class="screen-reader-text">${(window.pierreAdminL10n && window.pierreAdminL10n.dismiss) || 'Dismiss this notice.'}</span>`;
            dismiss.addEventListener('click', () => { notice.remove(); });
            notice.innerHTML = `<p>${message}</p>`;
            notice.appendChild(dismiss);
            wrap && wrap.prepend(notice);
            setTimeout(()=>{ try { notice.remove(); } catch(e){} }, 8000);
        }

        function syncInputFromSelect(){
            const opt = localeSelect.options[localeSelect.selectedIndex];
            if (!opt) return;
            const hook = opt.getAttribute('data-slack-webhook') || '';
            input.value = hook;
        }

        localeSelect && localeSelect.addEventListener('change', syncInputFromSelect);
        syncInputFromSelect();

        form.addEventListener('submit', function(e){
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = (window.pierreAdminL10n?.saving || 'Saving...');

            const fd = new FormData(form);
            fd.append('action', 'pierre_save_locale_slack');
            fd.append('nonce', window.pierreAdminL10n?.nonce || '');

            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                .then(r=>r.json())
                .then(json=>{
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? 'Saved.' : 'Failed.');
                    if (json && json.success) {
                        // Persist on current option for UX coherence
                        const opt = localeSelect.options[localeSelect.selectedIndex];
                        if (opt) { opt.setAttribute('data-slack-webhook', input.value); }
                        showNotice(msg, 'success');
                    } else {
                        showNotice(msg, 'error');
                    }
                })
                .catch(()=>{
                    showNotice('Network error while saving locale Slack webhook.', 'error');
                })
                .finally(()=>{
                    btn.disabled = false;
                    btn.textContent = original;
                });
        });
    })();
    
    // Pierre's Slack settings form handler - on Settings page! 🪨
    (function() {
        const slackForm = document.getElementById('pierre-slack-settings');
        const testBtn = document.getElementById('pierre-test-slack');
        const wrap = document.querySelector('.wrap');
        
        if (!wrap) return;
        
        function showNotice(message, type) {
            const notice = document.createElement('div');
            notice.className = `notice notice-${type} is-dismissible`;
            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'notice-dismiss';
            dismiss.innerHTML = `<span class="screen-reader-text">${(window.pierreAdminL10n && window.pierreAdminL10n.dismiss) || 'Dismiss this notice.'}</span>`;
            dismiss.addEventListener('click', () => {
                notice.remove();
            });
            notice.innerHTML = `<p>${message}</p>`;
            notice.appendChild(dismiss);
            wrap.prepend(notice);
            setTimeout(() => { try { notice.remove(); } catch(e) {} }, 8000);
        }
        
        // Pierre handles Slack form submission! 🪨
        if (slackForm) {
            slackForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = window.pierreAdminL10n?.saving || 'Saving...';
                
                const formData = new FormData(this);
                formData.append('action', 'pierre_admin_save_settings');
                formData.append('nonce', window.pierreAdminL10n?.nonce || '');
                
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(json => {
                        const msg = (json && (json.data?.message || json.message)) || (window.pierreAdminL10n?.saveSuccess || 'Settings saved successfully!');
                        if (json && json.success) {
                            showNotice(msg, 'success');
                        } else {
                            showNotice(msg, 'error');
                        }
                    })
                    .catch(() => {
                        showNotice(window.pierreAdminL10n?.saveError || 'An error occurred while saving settings.', 'error');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });
        }
        
        // Pierre handles Slack test button! 🪨
        if (testBtn) {
            testBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const slackForm = document.getElementById('pierre-slack-settings');
                if (!slackForm) return;
                
                testBtn.disabled = true;
                const originalText = testBtn.textContent;
                testBtn.textContent = window.pierreAdminL10n?.testing || 'Testing...';
                
                const formData = new FormData(slackForm);
                formData.append('action', 'pierre_admin_test_notification');
                formData.append('nonce', window.pierreAdminL10n?.nonce || '');
                
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(json => {
                        const msg = (json && (json.data?.message || json.message)) || (json.success ? (window.pierreAdminL10n?.testSuccess || 'Test succeeded!') : (window.pierreAdminL10n?.testFailed || 'Test failed!'));
                        if (json && json.success) {
                            showNotice(msg, 'success');
                        } else {
                            showNotice(msg, 'error');
                        }
                    })
                    .catch(() => {
                        showNotice(window.pierreAdminL10n?.testError || 'An error occurred during test.', 'error');
                    })
                    .finally(() => {
                        testBtn.disabled = false;
                        testBtn.textContent = originalText;
                    });
            });
        }
    })();
    
})(jQuery);
