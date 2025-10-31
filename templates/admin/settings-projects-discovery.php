<?php
/**
 * Pierre's settings projects discovery template
 *
 * @package Pierre
 * @since 1.0.0
 */

if (!defined('ABSPATH')) { exit; }
?>

<div class="wrap">
    <div class="pierre-grid pierre-grid--single">
    <div class="pierre-card">
        <h2><?php echo esc_html__('Projects Discovery', 'wp-pierre'); ?> <span id="pierre-projects-discovery-count" class="tag-count"></span> <span id="pierre-projects-discovery-loader" class="spinner is-active" aria-hidden="true"></span></h2>
        <p class="description"><?php echo esc_html__('List projects to use as a library for quick add per locale. One project per line: "type,slug". Examples:', 'wp-pierre'); ?><br/>
        <code>plugin,woocommerce</code> · <code>theme,twentytwentyfive</code> · <code>meta,wp</code> · <code>app,android</code>
        </p>

        <?php $existing = get_option('pierre_projects_discovery', []); if (!is_array($existing)) { $existing = []; } ?>
        <form id="pierre-projects-discovery-form" class="pierre-form-wide">
            <div class="pierre-row pierre-mb-8">
                <label>
                    <?php echo esc_html__('Type', 'wp-pierre'); ?>
                    <input list="pierre-type-list" id="pierre-type-input" class="regular-text" placeholder="plugin" />
                    <datalist id="pierre-type-list">
                        <option value="plugin"></option>
                        <option value="theme"></option>
                        <option value="meta"></option>
                        <option value="app"></option>
                        <option value="core"></option>
                    </datalist>
                </label>
                <label>
                    <?php echo esc_html__('Slug', 'wp-pierre'); ?>
                    <input id="pierre-slug-input" class="regular-text" placeholder="woocommerce" />
                </label>
                <button type="button" class="button" id="pierre-add-line-btn"><?php echo esc_html__('Append line', 'wp-pierre'); ?></button>
            </div>
            <textarea id="projects_discovery" name="projects_discovery" rows="10" placeholder="plugin,woocommerce&#10;theme,twentytwentyfive&#10;meta,wp"><?php echo esc_textarea(implode("\n", array_map(function($it){ return ($it['type']??'').','.($it['slug']??''); }, $existing))); ?></textarea>
            <div id="pierre-projects-discovery-lint" class="pierre-help-box pierre-mt-8" aria-live="polite"></div>
            <div class="pierre-form-actions">
                <button type="submit" class="button button-primary" data-label-saving="<?php echo esc_attr__('Saving...', 'wp-pierre'); ?>"><?php echo esc_html__('Save Library', 'wp-pierre'); ?></button>
            </div>
        </form>
    </div>

    <div class="pierre-card">
        <h2><?php echo esc_html__('Bulk Add to Surveillance', 'wp-pierre'); ?></h2>
        <p class="description"><?php echo esc_html__('Select a locale and add selected library entries to surveillance.', 'wp-pierre'); ?></p>
        <?php $active_locales = $GLOBALS['pierre_admin_template_data']['active_locales'] ?? []; ?>
        <form id="pierre-bulk-add-form" class="pierre-form-wide">
            <div class="pierre-row">
                <label><?php echo esc_html__('Locale:', 'wp-pierre'); ?>
                    <select name="locale_code" id="bulk_locale" required>
                        <option value=""><?php echo esc_html__('— Select —','wp-pierre'); ?></option>
                        <?php foreach ($active_locales as $lc): ?>
                            <option value="<?php echo esc_attr($lc); ?>"><?php echo esc_html($lc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Add All From Library', 'wp-pierre'); ?></button>
            </div>
        </form>
    </div>
    </div>
</div>


<script>
(function(){
  const ajaxUrl = window.pierreAdminL10n?.ajaxUrl || ajaxurl;
  const nonce = window.pierreAdminL10n?.nonce || '';

  // Small tab loader removal when ready
  const tabLoader = document.getElementById('pierre-projects-discovery-loader');
  if (tabLoader) { if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', ()=>tabLoader.remove()); } else { tabLoader.remove(); } }

  const saveForm = document.getElementById('pierre-projects-discovery-form');
  const textarea = document.getElementById('projects_discovery');
  const lintBox = document.getElementById('pierre-projects-discovery-lint');
  const countBadge = document.getElementById('pierre-projects-discovery-count');
  const addType = document.getElementById('pierre-type-input');
  const addSlug = document.getElementById('pierre-slug-input');
  const addBtn  = document.getElementById('pierre-add-line-btn');

  const allowedTypes = ['plugin','theme','meta','app','core'];

  function normalizeLine(line){
    const t = line.trim();
    if (!t) return null;
    const parts = t.split(',').map(p=>p.trim()).filter(Boolean);
    if (parts.length < 2) return { invalid:true, raw: line };
    const type = (parts[0]||'').toLowerCase().replace(/[^a-z0-9_-]/g,'');
    const slug = (parts[1]||'').toLowerCase().replace(/[^a-z0-9_-]/g,'');
    const validType = allowedTypes.includes(type);
    const validSlug = slug.length > 0;
    if (!validType || !validSlug) return { invalid:true, type, slug, raw: line };
    return { invalid:false, type, slug, key: (type === 'core' ? 'meta' : type) + ':' + slug };
  }

  function lintAndRender(){
    if (!lintBox || !textarea) return;
    const lines = (textarea.value||'').split(/\r?\n/);
    const seen = new Set();
    const unique = [];
    const invalid = [];
    let duplicates = 0;
    lines.forEach((line, idx)=>{
      if (!line.trim()) return;
      const res = normalizeLine(line);
      if (!res || res.invalid) { invalid.push({ line: idx+1, value: line }); return; }
      if (seen.has(res.key)) { duplicates++; return; }
      seen.add(res.key);
      unique.push(res);
    });
    const total = lines.filter(l=>l.trim()).length;
    const validCount = unique.length;
    const invalidCount = invalid.length;
    const parts = [];
    parts.push('<p><strong><?php echo esc_js(__('Summary', 'wp-pierre')); ?>:</strong> ' + validCount + ' <?php echo esc_js(__('valid', 'wp-pierre')); ?> / ' + total + ' · ' + duplicates + ' <?php echo esc_js(__('duplicate(s) ignored', 'wp-pierre')); ?>' + (invalidCount?(' · ' + invalidCount + ' <?php echo esc_js(__('invalid', 'wp-pierre')); ?>'):'') + '</p>');
    if (invalidCount) {
      const items = invalid.slice(0,5).map(it=>('<a href="#" data-goto="'+it.line+'"><code>#'+it.line+': '+(it.value||'')+'</code></a>')).join('<br>');
      parts.push('<p class="description"><?php echo esc_js(__('Invalid lines (first 5):', 'wp-pierre')); ?><br>' + items + '</p>');
    }
    parts.push('<p><button type="button" class="button" id="pierre-dedupe-btn"><?php echo esc_js(__('Deduplicate & Normalize', 'wp-pierre')); ?></button> <span class="description"><?php echo esc_js(__('Removes duplicates and fixes type/core mapping to meta.', 'wp-pierre')); ?></span></p>');
    lintBox.innerHTML = parts.join('');

    const submitBtn = saveForm?.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = invalidCount > 0 && validCount === 0;

    if (countBadge) {
      countBadge.textContent = validCount + ' / ' + total;
    }

    lintBox.querySelectorAll('[data-goto]').forEach(a=>{
      a.addEventListener('click', function(ev){
        ev.preventDefault();
        const line = parseInt(this.getAttribute('data-goto')||'0',10);
        if (!line || !textarea) return;
        const text = textarea.value.split(/\r?\n/);
        let start = 0; for (let i=0;i<line-1;i++){ start += (text[i]||'').length + 1; }
        const end = start + (text[line-1]||'').length;
        textarea.focus();
        textarea.setSelectionRange(start, end);
      });
    });

    const dedupeBtn = document.getElementById('pierre-dedupe-btn');
    dedupeBtn && dedupeBtn.addEventListener('click', function(){
      const deduped = Array.from(seen).map(k=>{
        const [type, slug] = k.split(':');
        return (type+','+slug);
      }).join('\n');
      textarea.value = deduped;
      lintAndRender();
    }, { once: true });
  }

  if (textarea) {
    textarea.addEventListener('input', lintAndRender);
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', lintAndRender); } else { lintAndRender(); }
  }
  if (addBtn && addType && addSlug && textarea) {
    addBtn.addEventListener('click', function(){
      const t = (addType.value||'').trim().toLowerCase();
      const s = (addSlug.value||'').trim().toLowerCase();
      if (!t || !s) return;
      const line = t + ',' + s;
      textarea.value = (textarea.value ? (textarea.value.replace(/\s+$/,'') + '\n') : '') + line;
      lintAndRender();
      addSlug.value = '';
      addSlug.focus();
    });
  }
  if (saveForm) {
    saveForm.addEventListener('submit', function(e){
      e.preventDefault();
      const wrap = document.querySelector('.wrap');
      const btn = this.querySelector('button[type="submit"]');
      const original = btn.textContent; const savingLabel = btn.getAttribute('data-label-saving')||'Saving...'; btn.disabled = true; btn.textContent = savingLabel;
      // On submit, dedupe & normalize before sending
      if (textarea) {
        const lines = (textarea.value||'').split(/\r?\n/);
        const selStart = textarea.selectionStart; const selEnd = textarea.selectionEnd;
        const seen = new Set();
        const cleaned = [];
        lines.forEach((line)=>{
          const res = normalizeLine(line);
          if (!res || res.invalid) return;
          if (seen.has(res.key)) return;
          seen.add(res.key);
          const normType = res.key.split(':')[0];
          cleaned.push(normType + ',' + res.slug);
        });
        textarea.value = cleaned.join('\n');
        const newLen = textarea.value.length;
        const pos = Math.min(newLen, Math.floor(((selStart+selEnd)/2)));
        textarea.setSelectionRange(pos, pos);
      }
      const fd = new FormData(this);
      fd.append('action','pierre_save_projects_discovery');
      fd.append('nonce', nonce);
      fetch(ajaxUrl, {method:'POST', body: fd})
        .then(r=>r.json())
        .then(j=>{ 
          const msg = (j && (j.data?.message||j.message)) || (j.success?'Saved.':'Failed.');
          const count = (j && j.data && typeof j.data.count !== 'undefined') ? j.data.count : null;
          const dups = (j && j.data && typeof j.data.duplicates !== 'undefined') ? j.data.duplicates : null;
          const info = (count !== null || dups !== null)
            ? (' <span class="tag-count" style="margin-left:8px">' +
               (count !== null ? (count + ' <?php echo esc_js(__('saved', 'wp-pierre')); ?>') : '') +
               (dups !== null ? (' · ' + dups + ' <?php echo esc_js(__('duplicate(s) ignored', 'wp-pierre')); ?>') : '') +
               '</span>')
            : '';
          const n = document.createElement('div'); n.className = 'notice ' + (j && j.success ? 'notice-success' : 'notice-error') + ' is-dismissible'; n.setAttribute('role','status'); n.setAttribute('tabindex','-1');
          const viewLink = ' <a href="#projects_discovery" id="pierre-view-library-link"><?php echo esc_js(__('View library', 'wp-pierre')); ?></a>';
          n.innerHTML = '<p>' + msg + info + viewLink + '</p>';
          wrap && wrap.prepend(n); n.focus();
          const vl = document.getElementById('pierre-view-library-link');
          vl && vl.addEventListener('click', function(ev){ ev.preventDefault(); const ta = document.getElementById('projects_discovery'); if (ta) { ta.scrollIntoView({behavior:'smooth', block:'center'}); ta.focus(); } });
        })
        .catch(()=>{
          const n = document.createElement('div'); n.className = 'notice notice-error is-dismissible'; n.setAttribute('role','status'); n.setAttribute('tabindex','-1'); n.innerHTML = '<p>Network error.</p>'; wrap && wrap.prepend(n); n.focus();
        })
        .finally(()=>{ btn.disabled=false; btn.textContent=original; });
    });
  }

  const bulkForm = document.getElementById('pierre-bulk-add-form');
  if (bulkForm) {
    // Preview (dry-run)
    const localeSel = document.getElementById('bulk_locale');
    const previewWrap = document.createElement('div'); previewWrap.className = 'pierre-help-box'; previewWrap.id = 'pierre-bulk-preview';
    bulkForm.appendChild(previewWrap);
    const previewBtn = document.createElement('button'); previewBtn.type='button'; previewBtn.className='button'; previewBtn.id='pierre-bulk-preview-btn'; previewBtn.textContent='<?php echo esc_js(__('Preview', 'wp-pierre')); ?>';
    const actionsRow = bulkForm.querySelector('.pierre-row'); actionsRow && actionsRow.appendChild(previewBtn);

    function libraryValidCount(){
      if (!textarea) return 0; const lines=(textarea.value||'').split(/\r?\n/); let seen=new Set(), cnt=0; lines.forEach(l=>{ const r=normalizeLine(l); if (!r||r.invalid) return; if (seen.has(r.key)) return; seen.add(r.key); cnt++; }); return cnt;
    }
    function toggleBulkSubmit(){
      const btn = bulkForm.querySelector('button[type="submit"]'); if (!btn) return;
      btn.disabled = libraryValidCount() === 0;
      if (btn.disabled) { btn.setAttribute('aria-disabled','true'); } else { btn.removeAttribute('aria-disabled'); }
    }
    toggleBulkSubmit();
    textarea && textarea.addEventListener('input', toggleBulkSubmit);

    previewBtn.addEventListener('click', function(){
      previewWrap.innerHTML = '<p><span class="spinner is-active"></span> <?php echo esc_js(__('Computing preview…', 'wp-pierre')); ?></p>';
      const fd = new FormData();
      fd.append('action','pierre_bulk_preview_from_discovery');
      fd.append('nonce', nonce);
      fd.append('locale_code', localeSel ? (localeSel.value||'') : '');
      fetch(ajaxUrl, {method:'POST', body: fd})
        .then(r=>r.json())
        .then(j=>{
          if (!j || !j.success) { previewWrap.innerHTML = '<div class="notice notice-error"><p>' + ((j && (j.data?.message||j.message))||'') + '</p></div>'; return; }
          const d=j.data; previewWrap.innerHTML = '<p><strong><?php echo esc_js(__('Preview', 'wp-pierre')); ?>:</strong> ' + (d.to_add||0) + ' <?php echo esc_js(__('to add', 'wp-pierre')); ?> · ' + (d.already||0) + ' <?php echo esc_js(__('already present', 'wp-pierre')); ?>' + (d.invalid?(' · ' + d.invalid + ' <?php echo esc_js(__('invalid', 'wp-pierre')); ?>'):'') + '</p>';
        })
        .catch(()=>{ previewWrap.innerHTML = '<div class="notice notice-error"><p><?php echo esc_js(__('Network error.', 'wp-pierre')); ?></p></div>'; });
    });

    bulkForm.addEventListener('submit', function(e){
      e.preventDefault();
      const wrap = document.querySelector('.wrap');
      const btn = this.querySelector('button[type="submit"]');
      const original = btn.textContent; const addingLabel = btn.getAttribute('data-label-adding')||'Adding...'; btn.disabled = true; btn.textContent = addingLabel;
      const spinner = document.createElement('span'); spinner.className = 'spinner is-active'; btn.parentNode.insertBefore(spinner, btn.nextSibling);
      const fd = new FormData(this);
      fd.append('action','pierre_bulk_add_from_discovery');
      fd.append('nonce', nonce);
      fetch(ajaxUrl, {method:'POST', body: fd})
        .then(r=>r.json())
        .then(j=>{ 
          const msg = (j && (j.data?.message||j.message)) || (j.success?'Added.':'Failed.');
          const n = document.createElement('div'); n.className = 'notice ' + (j && j.success ? 'notice-success' : 'notice-error') + ' is-dismissible'; n.setAttribute('role','status'); n.setAttribute('tabindex','-1');
          n.innerHTML = '<p>' + msg + '</p>'; wrap && wrap.prepend(n); n.focus();
          if (j && j.success) location.reload();
        })
        .catch(()=>{ const n = document.createElement('div'); n.className = 'notice notice-error is-dismissible'; n.setAttribute('role','status'); n.setAttribute('tabindex','-1'); n.innerHTML = '<p>Network error.</p>'; wrap && wrap.prepend(n); n.focus(); })
        .finally(()=>{ btn.disabled=false; btn.textContent=original; spinner.remove(); });
    });
  }
})();
</script>

