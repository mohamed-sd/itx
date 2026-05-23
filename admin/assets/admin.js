/* ── Image tab toggle ───────────────────────────────────── */
function initImgInput(wrapper) {
  const tabs    = wrapper.querySelectorAll('.img-tab-btn');
  const panels  = wrapper.querySelectorAll('.img-tab');
  const preview = wrapper.querySelector('.img-preview');
  const urlInp  = wrapper.querySelector('.img-url-inp');
  const fileInp = wrapper.querySelector('.img-file-inp');

  tabs.forEach(btn => {
    btn.addEventListener('click', () => {
      tabs.forEach(b => b.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      wrapper.querySelector(`.img-tab[data-tab="${btn.dataset.tab}"]`).classList.add('active');
    });
  });

  if (urlInp) {
    urlInp.addEventListener('input', () => showPreview(urlInp.value, preview));
  }
  if (fileInp) {
    fileInp.addEventListener('change', () => {
      const file = fileInp.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => showPreview(e.target.result, preview);
        reader.readAsDataURL(file);
      }
    });
  }
}

function showPreview(src, el) {
  if (!el) return;
  if (src) { el.src = src; el.classList.add('show'); }
  else      { el.classList.remove('show'); }
}

document.querySelectorAll('.img-wrap').forEach(w => initImgInput(w));

/* ── Modal helpers ──────────────────────────────────────── */
function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}

document.querySelectorAll('.modal-bg').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-bg.open').forEach(m => closeModal(m.id));
  }
});

/* ── Tab switching ──────────────────────────────────────── */
function switchTab(groupId, tabId) {
  const group = document.getElementById(groupId);
  if (!group) return;
  group.querySelectorAll('.pg-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tabId));
  group.querySelectorAll('.tab-pane').forEach(p => p.classList.toggle('active', p.id === tabId));
}

/* ── Sidebar toggle (mobile) ────────────────────────────── */
const sidebarEl = document.querySelector('.sidebar');
const toggleBtn = document.querySelector('.mobile-toggle');
if (toggleBtn && sidebarEl) {
  toggleBtn.addEventListener('click', () => sidebarEl.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (!e.target.closest('.sidebar') && !e.target.closest('.mobile-toggle')) {
      sidebarEl.classList.remove('open');
    }
  });
}

/* ── Confirm delete ─────────────────────────────────────── */
document.querySelectorAll('[data-confirm]').forEach(btn => {
  btn.addEventListener('click', e => {
    if (!confirm(btn.dataset.confirm || 'هل أنت متأكد من الحذف؟')) e.preventDefault();
  });
});

/* ── AJAX delete helper ─────────────────────────────────── */
async function ajaxDelete(url, onSuccess) {
  if (!confirm('هل أنت متأكد من الحذف؟')) return;
  try {
    const res  = await fetch(url, { method: 'POST' });
    const json = await res.json();
    if (json.success) { onSuccess && onSuccess(); showToast('تم الحذف بنجاح', 'success'); }
    else showToast(json.message || 'حدث خطأ', 'danger');
  } catch { showToast('خطأ في الاتصال', 'danger'); }
}

/* ── Toast notifications ────────────────────────────────── */
function showToast(msg, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position:fixed;bottom:1.5rem;left:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem';
    document.body.appendChild(container);
  }
  const icons = { success: 'fa-check-circle', danger: 'fa-exclamation-triangle', info: 'fa-info-circle' };
  const toast = document.createElement('div');
  toast.className = `alert alert-${type}`;
  toast.style.cssText = 'min-width:240px;box-shadow:0 4px 16px rgba(0,0,0,.15);animation:slideUp .3s ease';
  toast.innerHTML = `<i class="fas ${icons[type] || 'fa-info-circle'}"></i> ${msg}`;
  container.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .4s'; setTimeout(() => toast.remove(), 400); }, 3200);
}

/* ── is_programming toggle (works) ─────────────────────── */
const progToggle = document.getElementById('is_programming');
const demoUrlRow = document.getElementById('demo-url-row');
if (progToggle && demoUrlRow) {
  const toggle = () => demoUrlRow.style.display = progToggle.checked ? 'flex' : 'none';
  progToggle.addEventListener('change', toggle);
  toggle();
}

/* ── Icon class preview ─────────────────────────────────── */
const iconInps = document.querySelectorAll('.icon-inp');
iconInps.forEach(inp => {
  const preview = inp.closest('.fg')?.querySelector('.icon-preview');
  if (preview) {
    inp.addEventListener('input', () => {
      preview.className = 'icon-preview ' + inp.value;
    });
  }
});

/* ── Char counter ───────────────────────────────────────── */
document.querySelectorAll('[data-maxlen]').forEach(el => {
  const hint = document.createElement('span');
  hint.className = 'char-hint';
  el.parentNode.appendChild(hint);
  const update = () => hint.textContent = `${el.value.length} / ${el.dataset.maxlen}`;
  el.addEventListener('input', update);
  update();
});

/* ── Auto-dismiss alerts ────────────────────────────────── */
document.querySelectorAll('.alert-auto-dismiss').forEach(a => {
  setTimeout(() => { a.style.transition = 'opacity .5s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 500); }, 4000);
});

/* Inline style for slide-up toast animation */
const style = document.createElement('style');
style.textContent = `@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}`;
document.head.appendChild(style);
