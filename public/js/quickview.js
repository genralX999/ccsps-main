(function(){
  if (!window.CECOE_BASE) window.CECOE_BASE = '';

  window.showQuickViewModal = async function(html, id){
    const existing = document.getElementById('quickViewModal'); if (existing) existing.remove();
    const wrap = document.createElement('div');
    wrap.id = 'quickViewModal';
    wrap.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-4';
    wrap.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="p-3 border-b bg-white z-10 flex justify-end" style="flex:0 0 auto;">
          <button id="closeModal" class="text-gray-600 px-3 py-1 rounded hover:bg-gray-100">Close</button>
        </div>
        <div id="quickViewContent" class="p-4 overflow-auto" style="flex:1 1 auto; max-height: calc(90vh - 56px);">${html}</div>
      </div>
    `;
    document.body.appendChild(wrap);

    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const close = () => {
      if (wrap && wrap.parentNode) wrap.parentNode.removeChild(wrap);
      document.body.style.overflow = prevOverflow || '';
      document.removeEventListener('keydown', onKey);
    };
    const onKey = (e) => { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', onKey);

    const btn = wrap.querySelector('#closeModal'); if (btn) btn.addEventListener('click', close);
    wrap.addEventListener('click', (ev) => { if (ev.target === wrap) close(); });

    // helper to populate sub-event select
    const content = wrap.querySelector('#quickViewContent');
    const evtSel = content ? content.querySelector('#event_type') : null;
    const subSel = content ? content.querySelector('#sub_event_type') : null;

    async function populate(eventTypeId) {
      if (!subSel) return;
      subSel.innerHTML = '<option>Loading...</option>';
      try {
        const res = await fetch(window.CECOE_BASE + '/api/taxonomy.php?type=sub_event_types&event_type_id=' + encodeURIComponent(eventTypeId));
        if (!res.ok) { subSel.innerHTML = '<option value="">(failed)</option>'; return; }
        const rows = await res.json();
        const dataCurrent = subSel.getAttribute('data-current');
        const current = (dataCurrent !== null && dataCurrent !== '') ? dataCurrent : subSel.value;
        subSel.innerHTML = '<option value="">Select</option>';
        if (!rows || rows.length === 0) { subSel.innerHTML = '<option value="">(no sub-events)</option>'; return; }
        rows.forEach(r => { const o = document.createElement('option'); o.value = r.id; o.textContent = r.name; if (String(r.id) === String(current)) o.selected = true; subSel.appendChild(o); });
        subSel.removeAttribute('data-current');
      } catch (err) {
        subSel.innerHTML = '<option value="">(error)</option>';
      }
    }

    if (evtSel && subSel) {
      evtSel.addEventListener('change', async (e) => { await populate(evtSel.value); });
      if (evtSel.value) {
        if (!subSel.querySelector('option[value]') || subSel.querySelectorAll('option').length <= 1) {
          populate(evtSel.value).catch(()=>{});
        }
      }
    }

    // AJAX form handling
    const form = content ? content.querySelector('form') : null;
    if (form) {
      form.querySelectorAll('button[type=submit]').forEach(b => b.addEventListener('click', (ev) => { form._lastAct = ev.target.value || null; }));
      form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const fd = new FormData(form);
        if (form._lastAct) fd.set('act', form._lastAct);
        const res = await fetch(window.CECOE_BASE + '/public/monitored_view.php?id=' + encodeURIComponent(id) + '&modal=1&ajax=1', { method: 'POST', body: fd });
        let data;
        try { data = await res.json(); } catch (e) { showToast && showToast('Server error', 'error'); return; }
        if (!data || !data.success) { showToast && showToast(data?.message || 'Save failed', 'error'); return; }
        if (data.deleted) { close(); document.dispatchEvent(new CustomEvent('quickview:deleted')); return; }
        const content2 = document.getElementById('quickViewContent');
        if (content2) {
          content2.innerHTML = data.html;
          // scroll modal content to top and bring server success message into view if present
          try {
            // smooth scroll to top
            content2.scrollTop = 0;
            const successEl = content2.querySelector('.bg-green-100, .alert-success, .success-message');
            if (successEl && successEl.scrollIntoView) {
              successEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
              // ensure top is visible as fallback
              content2.scrollTop = 0;
            }
          } catch (e) {
            content2.scrollTop = 0;
          }
        }
      });
    }

    return;
  };

  // Delegated handler: catch event_type changes inside modal even if content is replaced
  document.addEventListener('change', async (e) => {
    if (!e.target.matches('#quickViewModal #event_type')) return;
    const evtSel = e.target;
    const wrap = document.getElementById('quickViewModal'); if (!wrap) return;
    const subSel = wrap.querySelector('#sub_event_type'); if (!subSel) return;
    subSel.innerHTML = '<option>Loading...</option>';
    try {
      const res = await fetch(window.CECOE_BASE + '/api/taxonomy.php?type=sub_event_types&event_type_id=' + encodeURIComponent(evtSel.value));
      if (!res.ok) { subSel.innerHTML = '<option value="">(failed to load)</option>'; return; }
      const rows = await res.json();
      const dataCurrent = subSel.getAttribute('data-current');
      const current = (dataCurrent !== null && dataCurrent !== '') ? dataCurrent : subSel.value;
      subSel.innerHTML = '<option value="">Select</option>';
      if (!rows || rows.length === 0) { subSel.innerHTML = '<option value="">(no sub-events)</option>'; return; }
      rows.forEach(r => { const o = document.createElement('option'); o.value = r.id; o.textContent = r.name; if (String(r.id) === String(current)) o.selected = true; subSel.appendChild(o); });
      subSel.removeAttribute('data-current');
    } catch (err) {
      subSel.innerHTML = '<option value="">(error)</option>';
    }
  });

})();
