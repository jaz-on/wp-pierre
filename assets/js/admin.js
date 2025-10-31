/**
 * Pierre's admin JavaScript - he makes his admin interface interactive! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

(function($) {
    'use strict';
    // Global WP notice utility
    if (!window.pierreNotice) {
        window.pierreNotice = function(type, message, autoDismissMs) {
            try {
                var wrap = document.querySelector('.wrap') || document.body;
                var notice = document.createElement('div');
                notice.className = 'notice notice-' + type + ' is-dismissible';
                notice.innerHTML = '<p>' + message + '</p>';
                var dismiss = document.createElement('button');
                dismiss.type = 'button';
                dismiss.className = 'notice-dismiss';
                dismiss.innerHTML = '<span class="screen-reader-text">' + ((window.pierreAdminL10n && window.pierreAdminL10n.dismiss) || 'Dismiss this notice.') + '</span>';
                dismiss.addEventListener('click', function(){ try { notice.remove(); } catch(e){} });
                notice.appendChild(dismiss);
                wrap && wrap.prepend(notice);
                var ttl = (typeof autoDismissMs === 'number') ? autoDismissMs : 8000;
                if (ttl > 0) setTimeout(function(){ try { notice.remove(); } catch(e){} }, ttl);
            } catch(e) {}
        };
    }
    // Inject Pierre nonce automatically if missing
    $.ajaxPrefilter(function(options, originalOptions, jqXHR){
        try {
            // Only POSTs to admin-ajax
            var url = (options && options.url) || '';
            if (!/admin-ajax\.php/i.test(url)) { return; }
            if ((options.type || options.method || 'GET').toUpperCase() !== 'POST') { return; }
            // If data is FormData, do nothing (handlers already append nonce)
            if (originalOptions && originalOptions.data instanceof FormData) { return; }
            // Try to parse action from data string
            var dataStr = typeof originalOptions.data === 'string' ? originalOptions.data : '';
            var hasNonce = /(?:^|&|=)nonce=/.test(dataStr);
            if (hasNonce) { return; }
            var actionMatch = dataStr.match(/(?:^|&)action=([^&]+)/);
            var action = actionMatch ? decodeURIComponent(actionMatch[1]) : '';
            // Prefer pierreAdminL10n.nonce for admin actions; fallback to nonceAjax
            var nonce = (window.pierreAdminL10n && window.pierreAdminL10n.nonce) || (window.pierreAdminL10n && window.pierreAdminL10n.nonceAjax) || '';
            if (!nonce) { return; }
            // Append nonce to data string
            options.data = (dataStr ? dataStr + '&' : '') + 'nonce=' + encodeURIComponent(nonce);
        } catch (e) {}
    });
    // Settings: Slack (global) save + test
    (function(){
        const slackForm = document.getElementById('pierre-slack-settings');
        const testBtn = document.getElementById('pierre-test-slack');
        const saveTestBtn = document.getElementById('pierre-save-test-webhook');
        const globalUrlInput = document.getElementById('global_webhook_url');
        const statusSpan = document.getElementById('pierre-slack-url-status');
        const wrap = document.querySelector('.wrap');
        function showNotice(message, type) {
            const notice = document.createElement('div');
            notice.className = `notice notice-${type} is-dismissible`;
            notice.innerHTML = `<p>${message}</p>`;
            wrap && wrap.prepend(notice);
            setTimeout(()=>{ try { notice.remove(); } catch(e){} }, 8000);
        }
        if (slackForm && slackForm.dataset.bound !== '1') { slackForm.dataset.bound = '1';
            slackForm.addEventListener('submit', function(e){
                e.preventDefault();
                const fd = new FormData(slackForm);
                fd.append('action', 'pierre_admin_save_settings');
                fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(json=>{
                        const msg = (json && (json.data?.message || json.message)) || (json.success ? 'Saved.' : 'Failed.');
                        showNotice(msg, json && json.success ? 'success' : 'error');
                    })
                    .catch(()=>showNotice('Network error.', 'error'));
            });
        }
        // Save & Test URL
        if (saveTestBtn && globalUrlInput) {
            if (saveTestBtn.dataset.bound !== '1') { saveTestBtn.dataset.bound = '1';
                saveTestBtn.addEventListener('click', function(){
                    const url = (globalUrlInput.value||'').trim();
                    if (!url) { showNotice('Please enter a webhook URL.', 'error'); return; }
                    saveTestBtn.disabled = true; if (statusSpan) { statusSpan.className=''; statusSpan.textContent=''; }
                    const fd = new FormData();
                    fd.append('action', 'pierre_admin_test_notification');
                    fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                    fd.append('global_webhook_url', url);
                    fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                        .then(r=>r.json())
                        .then(json=>{
                            const ok = json && json.success;
                            showNotice((json && (json.data?.message||json.message)) || (ok?'Saved & working.':'Failed.'), ok?'success':'error');
                            if (statusSpan) { statusSpan.className = ok ? 'pierre-status-ok' : 'pierre-status-ko'; statusSpan.textContent = ok ? 'Working' : 'Not working'; }
                            if (testBtn) { testBtn.disabled = !ok; }
                        })
                    .catch(()=>showNotice('Network error. Please check your connection or server logs (debug.log), then retry.', 'error'))
                        .finally(()=>{ saveTestBtn.disabled = false; });
                });
            }
        }
    })();

    // Settings: Force refresh locales (General tab) - moved from template to avoid kses escaping
    (function(){
        const btn = document.getElementById('pierre-force-refresh-locales');
        const spin = document.getElementById('pierre-force-refresh-spinner');
        if (!btn || btn.dataset.bound === '1') return; btn.dataset.bound = '1';
        btn.addEventListener('click', function(){
            if (spin) { spin.classList.add('is-active'); }
            const fd = new FormData();
            fd.append('action','pierre_fetch_locales');
            fd.append('nonce', window.pierreAdminL10n?.nonce || '');
            fd.append('force','1');
            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                .then(r=>r.json())
                .then(j=>{
                    const ok = j && j.success;
                    const msg = (j && (j.data?.message || j.message)) || (ok ? 'Refreshed.' : 'Failed.');
                    const n = document.createElement('div'); n.className = `notice ${ok?'notice-success':'notice-error'} is-dismissible`; n.innerHTML = `<p>${msg}</p>`; document.querySelector('.wrap')?.prepend(n);
                })
                .catch(()=>{ const n = document.createElement('div'); n.className = 'notice notice-error is-dismissible'; n.innerHTML = '<p>Network error. Please check your connection or server logs (debug.log), then retry.</p>'; document.querySelector('.wrap')?.prepend(n); })
                .finally(()=>{ if (spin) { spin.classList.remove('is-active'); } });
        });
    })();

    // Settings: Surveillance and Notification forms save
    (function(){
        function wireForm(id){
            const form = document.getElementById(id);
            if (!form || form.dataset.bound === '1') return; form.dataset.bound = '1';
            form.addEventListener('submit', function(e){
                e.preventDefault();
                const fd = new FormData(form);
                fd.append('action', 'pierre_admin_save_settings');
                fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(json=>{
                        const msg = (json && (json.data?.message || json.message)) || (json.success ? 'Saved.' : 'Failed.');
                        (function(){const wrap=document.querySelector('.wrap');if(!wrap)return;const n=document.createElement('div');n.className=`notice notice-${(json&&json.success)?'success':'error'} is-dismissible`;n.innerHTML=`<p>${msg}</p>`;wrap.prepend(n);})();
                    })
                    .catch(()=>{ const wrap=document.querySelector('.wrap'); if(!wrap)return; const n=document.createElement('div'); n.className='notice notice-error is-dismissible'; n.innerHTML='<p>Network error. Please check your connection or server logs (debug.log), then retry.</p>'; wrap.prepend(n); });
            });
        }
        wireForm('pierre-surveillance-settings');
        wireForm('pierre-notification-settings');

        // (presets removed)

        // Progress polling + abort
        (function(){
            const line = document.getElementById('pierre-progress-line');
            const abortBtn = document.getElementById('pierre-abort-run');
            if (!line && !abortBtn) return;
            let timer = null;
            function poll(){
                try {
                    const fd = new FormData();
                    fd.append('action','pierre_get_progress');
                    fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                    fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                        .then(r=>r.json())
                        .then(j=>{
                            const p = (j&&j.success&&j.data&&j.data.progress) ? j.data.progress : null;
                            if (!line) return;
                            if (p && (p.total||0) > 0) {
                                line.textContent = `Progress: ${p.processed||0} / ${p.total||0}`;
                            } else {
                                line.textContent = 'Progress: idle';
                            }
                        })
                        .catch(()=>{})
                        .finally(()=>{ timer = setTimeout(poll, 2000); });
                } catch(e) { timer = setTimeout(poll, 2000); }
            }
            poll();
            if (abortBtn && abortBtn.dataset.bound !== '1') { abortBtn.dataset.bound = '1';
                abortBtn.addEventListener('click', function(){
                    const fd = new FormData(); fd.append('action','pierre_abort_surveillance_run'); fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                    abortBtn.disabled = true;
                    fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                        .then(r=>r.json()).then(j=>{ window.pierreNotice && window.pierreNotice(j&&j.success?'success':'error', (j&&(j.data?.message||j.message))||'Done.'); })
                        .finally(()=>{ setTimeout(()=>{ abortBtn.disabled=false; }, 1000); });
                });
            }
        })();
    })();

    // Presets + abort run
    (function(){
        const btnSobre = document.getElementById('pierre-apply-preset-sobre');
        const btnActive = document.getElementById('pierre-apply-preset-active');
        const form = document.getElementById('pierre-surveillance-settings');
        const wrap = document.querySelector('.wrap');
        const progressLine = document.getElementById('pierre-progress-line');
        const btnRunNow = document.getElementById('pierre-run-now');
        function setVal(id, val){ const el = document.getElementById(id); if (el) { el.value = String(val); } }
        function notice(msg,type){ const n=document.createElement('div'); n.className=`notice notice-${type} is-dismissible`; n.innerHTML=`<p>${msg}</p>`; wrap?.prepend(n); }
        if (btnSobre && form && btnSobre.dataset.bound !== '1') { btnSobre.dataset.bound='1';
            btnSobre.addEventListener('click', function(){
                setVal('surveillance_interval','60');
                setVal('max_projects_per_check','30');
                setVal('request_timeout','20');
                notice('Applied “sobre” preset (interval=60, max=30, timeout=20s).','info');
            });
        }
        if (btnActive && form && btnActive.dataset.bound !== '1') { btnActive.dataset.bound='1';
            btnActive.addEventListener('click', function(){
                setVal('surveillance_interval','15');
                setVal('max_projects_per_check','100');
                setVal('request_timeout','45');
                notice('Applied “active” preset (interval=15, max=100, timeout=45s).','info');
            });
        }
        const btnAbort = document.getElementById('pierre-abort-run');
        if (btnAbort && btnAbort.dataset.bound !== '1') { btnAbort.dataset.bound='1';
            btnAbort.addEventListener('click', function(){
                const fd=new FormData(); fd.append('action','pierre_abort_run'); fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                btnAbort.disabled=true;
                if (btnRunNow) { btnRunNow.disabled = true; }
                if (progressLine) { progressLine.textContent = (window.pierreAdminL10n?.progressAborting || 'Progress: Aborting…'); }
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json()).then(j=>{ notice((j&&(j.data?.message||j.message))||'Abort requested.','info'); })
                    .catch(()=> notice('Network error. Please check your connection or server logs (debug.log), then retry.','error'))
                    .finally(()=>{ btnAbort.disabled=false; });
            });
        }

        // Poll progress periodically while page visible
        function pollProgress(){
            try {
                const fd = new FormData(); fd.append('action','pierre_get_progress'); fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(j=>{
                        if (!j || !j.success) return;
                        const pr = j.data?.progress || {};
                        const processed = pr.processed||0, total = pr.total||0;
                        const aborting = !!j.data?.aborting;
                        const dur = typeof j.data?.duration_ms === 'number' ? j.data.duration_ms : 0;
                        if (progressLine) {
                            if (processed>0 || total>0 || aborting) {
                                progressLine.textContent = aborting
                                    ? (window.pierreAdminL10n?.progressAborting || 'Progress: Aborting…')
                                    : (window.pierreAdminL10n?.progressLabel
                                        ? (window.pierreAdminL10n.progressLabel.replace('%1$s', processed).replace('%2$s', total))
                                        : `Progress: ${processed}/${total}`);
                                if (!aborting && total>0 && processed>=total && dur>0) {
                                    progressLine.textContent += ` — ${dur} ms`;
                                }
                            } else {
                                progressLine.textContent = (window.pierreAdminL10n?.progressIdle || 'Progress: idle');
                            }
                        }
                        // Disable/enable action buttons according to state
                        const running = aborting || (total>0 && processed<total);
                        if (btnRunNow) { btnRunNow.disabled = running; }
                        if (btnAbort) { btnAbort.disabled = aborting; }
                    })
                    .catch(()=>{})
            } catch(e) {}
        }
        let progressTimer = setInterval(()=>{ if (!document.hidden) { pollProgress(); } }, 5000);
        document.addEventListener('visibilitychange', function(){ if (!document.hidden) { pollProgress(); } });
    })();

    // Settings: Security actions (General tab)
    (function(){
        function requireDoubleConfirm(kind){
            try {
                if (kind === 'clear') {
                    if (!confirm('DANGER: This will permanently delete ALL Pierre data. This cannot be undone.\n\nAre you absolutely sure?')) return false;
                    const v = prompt('Final confirmation: type CLEAR to proceed.');
                    return v === 'CLEAR';
                }
                if (kind === 'reset') {
                    if (!confirm('Warning: Reset ALL Pierre settings to factory defaults. This will overwrite current configuration.\n\nProceed?')) return false;
                    const v = prompt('Final confirmation: type RESET to proceed.');
                    return v === 'RESET';
                }
            } catch(e) {}
            return true;
        }

        function bindBtn(id, action, nonceToUse){
            const btn = document.getElementById(id);
            if (!btn || btn.dataset.bound === '1') return; btn.dataset.bound = '1';
            btn.addEventListener('click', function(){
                if (id === 'pierre-clear-all-data' && !requireDoubleConfirm('clear')) return;
                if (id === 'pierre-reset-settings' && !requireDoubleConfirm('reset')) return;
                const fd = new FormData();
                fd.append('action', action);
                fd.append('nonce', nonceToUse || window.pierreAdminL10n?.nonce || '');
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(json=>{
                        const msg = (json && (json.data?.message || json.message)) || (json.success ? 'Done.' : 'Failed.');
                        const nType = json && json.success ? 'success' : 'error';
                        const n = document.createElement('div'); n.className = `notice notice-${nType} is-dismissible`; n.innerHTML = `<p>${msg}</p>`; document.querySelector('.wrap')?.prepend(n);
                    })
                    .catch(()=>{ const n = document.createElement('div'); n.className = 'notice notice-error is-dismissible'; n.innerHTML = '<p>Network error. Please check your connection or server logs (debug.log), then retry.</p>'; document.querySelector('.wrap')?.prepend(n); });
            });
        }
        bindBtn('pierre-flush-cache', 'pierre_flush_cache', window.pierreAdminL10n?.nonceAjax);
        bindBtn('pierre-reset-settings', 'pierre_reset_settings', window.pierreAdminL10n?.nonceAjax);
        bindBtn('pierre-clear-all-data', 'pierre_clear_data', window.pierreAdminL10n?.nonceAjax);
        // Locales options: Check all / Clear log
        (function(){
            const checkAll = document.getElementById('pierre-check-all-locales');
            const clearLog = document.getElementById('pierre-clear-locale-log');
            const wrap = document.querySelector('.wrap');
            function notice(msg, type){ const n=document.createElement('div'); n.className=`notice notice-${type} is-dismissible`; n.innerHTML=`<p>${msg}</p>`; wrap?.prepend(n); }
            if (clearLog && clearLog.dataset.bound !== '1') { clearLog.dataset.bound = '1';
                clearLog.addEventListener('click', function(){
                    const fd = new FormData(); fd.append('action','pierre_clear_locale_log'); fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                    fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                        .then(r=>r.json()).then(j=>{ notice((j&&(j.data?.message||j.message))||'Done.', j&&j.success?'success':'error'); });
                });
            }
            if (checkAll && checkAll.dataset.bound !== '1') { checkAll.dataset.bound = '1';
                checkAll.addEventListener('click', function(){
                    const nonce = checkAll.getAttribute('data-nonce') || (window.pierreAdminL10n?.nonce || '');
                    const codes = Array.from(document.querySelectorAll('#pierre-anomalies-body tr td:first-child')).map(td=>td.textContent.trim()).filter(Boolean);
                    if (!codes.length) { notice('No locales to check.', 'info'); return; }
                    let done=0, errors=0; checkAll.disabled = true;
                    const next = ()=>{
                        const code = codes.shift(); if (!code) { checkAll.disabled=false; notice(`Checked ${done} locale(s)${errors?`, ${errors} error(s)`:''}.`,'success'); return; }
                        const fd = new FormData(); fd.append('action','pierre_check_locale_status'); fd.append('nonce', nonce); fd.append('code', code);
                        fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                            .then(r=>r.json()).then(j=>{ if (j&&j.success){ done++; } else { errors++; } next(); })
                            .catch(()=>{ errors++; next(); });
                    };
                    next();
                });
            }
        })();
        (function(){
            const btn = document.getElementById('pierre-run-now');
            if (!btn) return;
            if (btn.dataset.bound === '1') return; btn.dataset.bound = '1';
            btn.addEventListener('click', function(){
                const fd = new FormData();
                fd.append('action','pierre_run_surveillance_now');
                fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                btn.disabled = true;
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(j=>{
                        const n = document.createElement('div'); n.className = `notice ${j&&j.success?'notice-success':'notice-error'} is-dismissible`; n.innerHTML = `<p>${(j&&(j.data?.message||j.message))||(j&&j.success?'Triggered.':'Failed.')}</p>`; document.querySelector('.wrap')?.prepend(n);
                    })
                    .catch(()=>{ const n = document.createElement('div'); n.className = 'notice notice-error is-dismissible'; n.innerHTML = '<p>Network error. Please check your connection or server logs (debug.log), then retry.</p>'; document.querySelector('.wrap')?.prepend(n); })
                    .finally(()=>{ btn.disabled = false; });
            });
        })();
        // Cleanup-now
        (function(){
            const btn = document.getElementById('pierre-run-cleanup-now');
            if (!btn) return;
            if (btn.dataset.bound === '1') return; btn.dataset.bound = '1';
            btn.addEventListener('click', function(){
                const fd = new FormData();
                fd.append('action','pierre_run_cleanup_now');
                fd.append('nonce', window.pierreAdminL10n?.nonce || '');
                btn.disabled = true;
                fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                    .then(r=>r.json())
                    .then(j=>{
                        const n = document.createElement('div'); n.className = `notice ${j&&j.success?'notice-success':'notice-error'} is-dismissible`; n.innerHTML = `<p>${(j&&(j.data?.message||j.message))||(j&&j.success?'Triggered.':'Failed.')}</p>`; document.querySelector('.wrap')?.prepend(n);
                    })
                    .catch(()=>{ const n = document.createElement('div'); n.className = 'notice notice-error is-dismissible'; n.innerHTML = '<p>Network error. Please check your connection or server logs (debug.log), then retry.</p>'; document.querySelector('.wrap')?.prepend(n); })
                    .finally(()=>{ btn.disabled = false; });
            });
        })();
        // Toggle status label auto-refresh
        (function(){
            const chk = document.getElementById('surveillance_enabled');
            if (!chk) return;
            function sync(){
                const on = chk.checked;
                const span = chk.closest('.pierre-form-group')?.querySelector('span');
                if (span){ span.classList.remove('pierre-status-ok','pierre-status-ko'); span.classList.add(on?'pierre-status-ok':'pierre-status-ko'); span.textContent = on?'Enabled':'Disabled'; }
            }
            chk.addEventListener('change', sync);
            sync();
        })();
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

        // Cooldown UX for Start button
        if (startBtn) {
            startBtn.addEventListener('click', function(e){
                // Let server handle action via PHP, only decorate UX post-click
                setTimeout(function(){
                    try {
                        startBtn.disabled = true;
                        startBtn.setAttribute('aria-disabled','true');
                        const parent = startBtn.parentElement;
                        if (parent) {
                            const info = document.createElement('span');
                            info.style.marginLeft = '8px';
                            info.textContent = 'Cooldown 2 min…';
                            parent.appendChild(info);
                            let left = 120;
                            const timer = setInterval(function(){
                                left -= 1;
                                if (left <= 0) {
                                    clearInterval(timer);
                                    startBtn.disabled = false;
                                    startBtn.removeAttribute('aria-disabled');
                                    info.remove();
                                } else if (left % 10 === 0) {
                                    info.textContent = 'Cooldown ' + Math.ceil(left/60) + ' min…';
                                }
                            }, 1000);
                        }
                    } catch (e) {}
                }, 250);
            }, { once: false });
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

    // Locale View: unified webhook form
    (function(){
        const form = document.getElementById('pierre-locale-webhook-form');
        if (!form) return;
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = (window.pierreAdminL10n?.saving || 'Saving...');
            const fd = new FormData(form);
            fd.append('action', 'pierre_save_locale_webhook');
            fd.append('nonce', window.pierreAdminL10n?.nonce || '');
            fetch(window.pierreAdminL10n?.ajaxUrl || ajaxurl, { method:'POST', body: fd })
                .then(r=>r.json())
                .then(json=>{
                    const msg = (json && (json.data?.message || json.message)) || (json.success ? 'Saved.' : 'Failed.');
                    const nType = json && json.success ? 'success' : 'error';
                    const n = document.createElement('div'); n.className = `notice notice-${nType} is-dismissible`; n.innerHTML = `<p>${msg}</p>`; document.querySelector('.wrap')?.prepend(n);
                })
                .catch(()=>{ const n = document.createElement('div'); n.className = 'notice notice-error is-dismissible'; n.innerHTML = '<p>Network error.</p>'; document.querySelector('.wrap')?.prepend(n); })
                .finally(()=>{ btn.disabled = false; btn.textContent = original; });
        });
    })();
    
    // Pierre's Slack settings form handler - on Settings page! 🪨
    (function() {
        const slackForm = document.getElementById('pierre-slack-settings');
        const testBtn = document.getElementById('pierre-test-slack');
        const previewBtn = document.getElementById('pierre-preview-slack');
        const previewBox = document.getElementById('pierre-slack-preview');
        const globalUrlInput = document.getElementById('global_webhook_url');
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
        // Validation: disable test buttons if URL is empty/invalid (global page)
        function isValidSlackUrl(val){ return typeof val === 'string' && /^https:\/\/hooks\.slack\.com\//.test(val.trim()); }
        function syncTestButtonsState(){
            if (!globalUrlInput) return;
            const ok = isValidSlackUrl(globalUrlInput.value);
            if (testBtn) { testBtn.disabled = !ok; }
            if (testBtnInline) { testBtnInline.disabled = !ok; }
        }
        if (globalUrlInput) {
            globalUrlInput.addEventListener('input', syncTestButtonsState);
            syncTestButtonsState();
        }

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
                            const detail = (json && (json.data?.error || json.error)) ? `\n${(json.data?.error || json.error)}` : '';
                            showNotice(msg + detail, 'error');
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

        // Inline test button triggers same logic
        if (testBtnInline) {
            testBtnInline.addEventListener('click', function(e){
                e.preventDefault();
                if (testBtn) { testBtn.click(); }
            });
        }

        // Pierre builds a quick preview message 🪨
        if (previewBtn && previewBox && slackForm) {
            // Enable/disable digest-dependent fields based on selections
            (function(){
                const modeSel = slackForm.querySelector('select[name="global_webhook_mode"]');
                const dTypeSel = slackForm.querySelector('select[name="global_webhook_digest_type"]');
                const dIntInp = slackForm.querySelector('input[name="global_webhook_digest_interval_minutes"]');
                const dFixInp = slackForm.querySelector('input[name="global_webhook_digest_fixed_time"]');
                function syncDigestState(){
                    const isDigest = (modeSel?.value||'immediate') === 'digest';
                    if (dTypeSel) dTypeSel.disabled = !isDigest;
                    if (dIntInp) dIntInp.disabled = !isDigest || (dTypeSel?.value||'interval') !== 'interval';
                    if (dFixInp) dFixInp.disabled = !isDigest || (dTypeSel?.value||'interval') !== 'fixed_time';
                }
                modeSel && modeSel.addEventListener('change', syncDigestState);
                dTypeSel && dTypeSel.addEventListener('change', syncDigestState);
                syncDigestState();
            })();

            function buildPreview(){
                const types = Array.from(slackForm.querySelectorAll('input[name="global_webhook_types[]"]:checked')).map(i=>i.value);
                const threshold = slackForm.querySelector('input[name="global_webhook_threshold"]').value || '—';
                const milestones = slackForm.querySelector('input[name="global_webhook_milestones"]').value || '50,80,100';
                const mode = slackForm.querySelector('select[name="global_webhook_mode"]').value || 'immediate';
                const dType = slackForm.querySelector('select[name="global_webhook_digest_type"]').value || 'interval';
                const dInt = slackForm.querySelector('input[name="global_webhook_digest_interval_minutes"]').value || '60';
                const dFix = slackForm.querySelector('input[name="global_webhook_digest_fixed_time"]').value || '09:00';
                const scopeLocales = Array.from(slackForm.querySelectorAll('select[name="global_webhook_scopes_locales[]"] option:checked')).map(o=>o.value);
                const scopeProjects = (slackForm.querySelector('textarea[name="global_webhook_scopes_projects"]').value||'').trim();
                const sample = {
                    preview: 'Pierre preview 🪨',
                    types,
                    thresholds: { new_strings: Number(threshold)||0, milestones: milestones.split(',').map(s=>Number(s.trim())).filter(Boolean) },
                    mode,
                    digest: { type: dType, interval_minutes: Number(dInt)||60, fixed_time: dFix },
                    scopes: { locales: scopeLocales, projects: scopeProjects.split(/\r?\n/).filter(Boolean) },
                    example_event: { type: types[0]||'new_strings', locale: 'fr_FR', project:{ type:'plugin', slug:'woocommerce' }, metrics:{ new_strings_count: 12, milestone: 80 } }
                };
                try { previewBox.textContent = JSON.stringify(sample, null, 2); } catch(e){ previewBox.textContent = String(sample); }
            }
            previewBtn.addEventListener('click', function(e){ e.preventDefault(); buildPreview(); });
        }
    })();
    
})(jQuery);
