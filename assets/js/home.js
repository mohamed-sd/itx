/* ============================================================
   ITX — Homepage only (index.php)
   Dynamic "Our Works" (AJAX + filter + modal) and the contact form.
   Bilingual: renders the active language (window.currentLang) and
   re-renders when the language toggle fires `itx:langchange`.
   Loaded after assets/js/script.js.
   ============================================================ */

/* ---------- i18n helpers ---------- */
function itxLangIdx() { return window.currentLang === 'en' ? 1 : 0; }
// pick AR or EN (falls back to AR when EN missing)
function L(ar, en) { return (itxLangIdx() === 1 && en) ? en : (ar || ''); }
const WORK_T = {
  loading:    ['جاري التحميل…', 'Loading…'],
  loadingW:   ['جاري تحميل الأعمال…', 'Loading our work…'],
  empty:      ['لا توجد أعمال في هذا القسم حالياً', 'No work in this category yet'],
  error:      ['تعذّر تحميل الأعمال', 'Failed to load our work'],
  programming:['برمجية', 'Software'],
  details:    ['التفاصيل', 'Details'],
  client:     ['العميل', 'Client'],
  year:       ['السنة', 'Year'],
  tech:       ['التقنيات المستخدمة', 'Technologies used'],
  demo:       ['تجربة الديمو', 'Live demo'],
  noMedia:    ['لا توجد صور أو فيديوهات لهذا المشروع', 'No images or videos for this project'],
  detailFail: ['تعذّر تحميل تفاصيل المشروع', 'Failed to load project details'],
};
function WT(k) { return WORK_T[k][itxLangIdx()]; }

/* ---------- Our Works state ---------- */
let currentMedia = [];
let currentMediaIdx = 0;
let WORK_CATS = [];
let currentCat = 0;
let lastProjects = null;
let openProjectId = null;

async function loadCategories() {
  try {
    const res = await fetch('api/get_categories.php');
    const json = await res.json();
    if (!json.success) return;
    WORK_CATS = json.data;
    renderCategories();
  } catch (e) {}
}

function renderCategories() {
  const container = document.getElementById('worksCategories');
  if (!container) return;
  container.querySelectorAll('.cat-btn[data-dyn]').forEach((b) => b.remove());
  WORK_CATS.forEach((cat) => {
    const btn = document.createElement('button');
    btn.className = 'cat-btn';
    btn.dataset.cat = cat.id;
    btn.dataset.dyn = '1';
    btn.innerHTML = `<i class="${cat.icon}"></i> ${L(cat.name, cat.name_en)}`;
    btn.onclick = () => filterWorks(cat.id);
    container.appendChild(btn);
  });
  container.querySelectorAll('.cat-btn').forEach((b) =>
    b.classList.toggle('active', String(b.dataset.cat) === String(currentCat))
  );
}

function filterWorks(catId) {
  currentCat = catId;
  document.querySelectorAll('.cat-btn').forEach((b) =>
    b.classList.toggle('active', String(b.dataset.cat) === String(catId))
  );
  loadProjects(catId);
}

async function loadProjects(catId = 0) {
  const grid = document.getElementById('worksGrid');
  grid.innerHTML = `<div class="works-loading"><i class="fas fa-spinner"></i> ${WT('loading')}</div>`;
  try {
    const url = catId > 0 ? `api/get_projects.php?category=${catId}` : 'api/get_projects.php';
    const res = await fetch(url);
    const json = await res.json();
    if (json.success && json.data.length > 0) { lastProjects = json.data; renderProjects(json.data); }
    else { lastProjects = []; grid.innerHTML = `<div class="works-empty"><i class="fas fa-folder-open"></i>${WT('empty')}</div>`; }
  } catch (e) {
    grid.innerHTML = `<div class="works-empty"><i class="fas fa-exclamation-triangle"></i>${WT('error')}</div>`;
  }
}

function renderProjects(projects) {
  const grid = document.getElementById('worksGrid');
  grid.innerHTML = projects
    .map(
      (p) => `
      <div class="work-card" onclick="openModal(${p.id})">
        <div class="work-card-image">
          <img src="${p.thumbnail}" alt="${L(p.title, p.title_en)}" loading="lazy"
               onerror="this.src='assets/itx-mark.png';this.classList.add('work-card-fallback')">
          <div class="work-badges">
            <span class="badge-category"><i class="${p.category_icon}"></i> ${L(p.category_name, p.category_name_en)}</span>
            ${p.is_programming == 1 ? `<span class="badge-programming"><i class="fas fa-code"></i> ${WT('programming')}</span>` : ''}
          </div>
        </div>
        <div class="work-card-body">
          <h3>${L(p.title, p.title_en)}</h3>
          <p>${L(p.short_desc, p.short_desc_en)}</p>
          <div class="work-card-footer">
            <span class="work-meta">${p.client_name || ''}${p.project_year ? ' · ' + p.project_year : ''}</span>
            <button class="btn-view-work"><i class="fas fa-eye"></i> ${WT('details')}</button>
          </div>
        </div>
      </div>
    `
    )
    .join('');
  grid.querySelectorAll('.work-card').forEach((card, i) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(22px)';
    setTimeout(() => {
      card.style.transition = 'opacity .45s ease, transform .45s ease';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, i * 75);
  });
}

async function openModal(projectId) {
  const overlay = document.getElementById('projectModal');
  openProjectId = projectId;
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
  document.getElementById('modalTitle').textContent = '…';
  document.getElementById('modalBadges').innerHTML = '';
  document.getElementById('modalMedia').innerHTML = '<div class="no-media-msg"><i class="fas fa-spinner fa-spin"></i></div>';
  document.getElementById('modalDetails').innerHTML = `<div class="works-loading"><i class="fas fa-spinner"></i>${WT('loading')}</div>`;
  try {
    const res = await fetch(`api/get_project.php?id=${projectId}`);
    const json = await res.json();
    if (!json.success) return;
    const p = json.data;
    document.getElementById('modalTitle').textContent = L(p.title, p.title_en);
    document.getElementById('modalBadges').innerHTML = `
      <span class="badge-category"><i class="${p.category_icon}"></i> ${L(p.category_name, p.category_name_en)}</span>
      ${p.is_programming == 1 ? `<span class="badge-programming"><i class="fas fa-code"></i> ${WT('programming')}</span>` : ''}
    `;
    currentMedia = p.media || [];
    currentMediaIdx = 0;
    renderMedia();
    const techHTML = p.technologies
      ? p.technologies.split(',').map((t) => `<span class="tech-tag">${t.trim()}</span>`).join('')
      : '';
    const demoBtn =
      p.is_programming == 1 && p.demo_url
        ? `<a href="${p.demo_url}" target="_blank" rel="noopener" class="btn-demo"><i class="fas fa-rocket"></i> ${WT('demo')}</a>`
        : '';
    document.getElementById('modalDetails').innerHTML = `
      <p class="modal-description">${L(p.description, p.description_en)}</p>
      <div class="modal-info-grid">
        ${p.client_name ? `<div class="modal-info-item"><label>${WT('client')}</label><span>${p.client_name}</span></div>` : ''}
        ${p.project_year ? `<div class="modal-info-item"><label>${WT('year')}</label><span>${p.project_year}</span></div>` : ''}
      </div>
      ${techHTML ? `<div><p class="modal-tech-label">${WT('tech')}</p><div class="modal-technologies">${techHTML}</div></div>` : ''}
      ${demoBtn ? `<div class="modal-actions">${demoBtn}</div>` : ''}
    `;
  } catch (e) {
    document.getElementById('modalDetails').innerHTML =
      `<p style="color:var(--muted);text-align:center;padding:2rem;">${WT('detailFail')}</p>`;
  }
}

function renderMedia() {
  const el = document.getElementById('modalMedia');
  if (!currentMedia.length) {
    el.innerHTML = `<div class="no-media-msg"><i class="fas fa-images"></i><span>${WT('noMedia')}</span></div>`;
    return;
  }
  const cur = currentMedia[currentMediaIdx];
  const cap = L(cur.caption, cur.caption_en);
  const mainContent =
    cur.type === 'video'
      ? `<iframe src="${cur.url}" allowfullscreen allow="autoplay; encrypted-media"></iframe>`
      : `<img src="${cur.url}" alt="${cap || ''}">`;
  const navBtns =
    currentMedia.length > 1
      ? `
      <button class="media-nav-btn media-nav-prev" onclick="event.stopPropagation();goMedia(${(currentMediaIdx - 1 + currentMedia.length) % currentMedia.length})"><i class="fas fa-chevron-right"></i></button>
      <button class="media-nav-btn media-nav-next" onclick="event.stopPropagation();goMedia(${(currentMediaIdx + 1) % currentMedia.length})"><i class="fas fa-chevron-left"></i></button>`
      : '';
  const thumbStrip =
    currentMedia.length > 1
      ? `
      <div class="modal-media-thumbs">
        ${currentMedia
          .map(
            (m, i) => `
          <div class="media-thumb ${i === currentMediaIdx ? 'active' : ''}" onclick="goMedia(${i})">
            <img src="${m.thumbnail || m.url}" alt="">
            ${m.type === 'video' ? '<div class="media-thumb-play"><i class="fas fa-play"></i></div>' : ''}
          </div>`
          )
          .join('')}
      </div>`
      : '';
  const caption = cap ? `<div class="modal-media-caption">${cap}</div>` : '';
  el.innerHTML = `<div class="modal-media-main">${mainContent}${navBtns}</div>${caption}${thumbStrip}`;
}

function goMedia(idx) {
  currentMediaIdx = idx;
  renderMedia();
}

function closeModal() {
  document.getElementById('projectModal').classList.remove('active');
  document.body.style.overflow = '';
  currentMedia = [];
  currentMediaIdx = 0;
  openProjectId = null;
}

(function () {
  'use strict';

  const modal = document.getElementById('projectModal');
  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === e.currentTarget) closeModal();
    });
  }
  document.addEventListener('keydown', (e) => {
    const overlay = document.getElementById('projectModal');
    if (!overlay || !overlay.classList.contains('active')) return;
    if (e.key === 'Escape') { closeModal(); return; }
    if (currentMedia.length < 2) return;
    if (e.key === 'ArrowRight') goMedia((currentMediaIdx - 1 + currentMedia.length) % currentMedia.length);
    if (e.key === 'ArrowLeft') goMedia((currentMediaIdx + 1) % currentMedia.length);
  });

  // Re-render the works section (and any open modal) when language changes
  document.addEventListener('itx:langchange', () => {
    if (!document.getElementById('worksGrid')) return;
    renderCategories();
    if (lastProjects) renderProjects(lastProjects);
    const overlay = document.getElementById('projectModal');
    if (overlay && overlay.classList.contains('active') && openProjectId) openModal(openProjectId);
  });

  // Bootstrap the works section
  if (document.getElementById('worksGrid')) {
    loadCategories();
    loadProjects();
  }

  /* ---------- Contact form (mailto) ---------- */
  const form = document.getElementById('contactForm');
  if (form) {
    const labels = {
      ar: { sending: 'جارٍ الإرسال…', sent: '✓ تم فتح بريدك', fill: 'من فضلك أكمل الحقول المطلوبة', email: 'بريد إلكتروني غير صحيح' },
      en: { sending: 'Sending…', sent: '✓ Mail opened', fill: 'Please fill the required fields', email: 'Invalid email address' },
    };
    const t = () => labels[window.currentLang || 'ar'] || labels.ar;
    const note = document.getElementById('formNote');
    const showNote = (msg, ok) => {
      if (!note) return;
      note.textContent = msg;
      note.style.color = ok ? '#1F8A5B' : '#d33';
    };

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const get = (n) => (form.querySelector(`[name="${n}"]`)?.value || '').trim();
      const name = get('name');
      const phone = get('phone');
      const email = get('email');
      const service = get('service');
      const message = get('message');

      if (!name || !email || !message) { showNote(t().fill, false); return; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showNote(t().email, false); return; }

      const btn = form.querySelector('button[type=submit]');
      const orig = btn.innerHTML;
      btn.disabled = true;
      btn.textContent = t().sending;

      const to = window.CONTACT_EMAIL || 'info@itx.sd';
      const subject = `طلب من الموقع — ${name}` + (service ? ` (${service})` : '');
      const body =
        `الاسم: ${name}\n` +
        `الهاتف: ${phone}\n` +
        `البريد: ${email}\n` +
        (service ? `الخدمة: ${service}\n` : '') +
        `\nالرسالة:\n${message}`;
      window.location.href = `mailto:${to}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;

      setTimeout(() => {
        btn.innerHTML = orig;
        btn.disabled = false;
        showNote(t().sent, true);
        form.reset();
      }, 1200);
    });
  }
})();
