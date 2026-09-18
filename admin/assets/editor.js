/* ============================================================
   ITX Admin — visual (WYSIWYG) editor for article content
   ------------------------------------------------------------
   Zero dependencies on purpose: the site ships a strict CSP
   (script-src 'self' + cdnjs, connect-src 'self'), and the common
   editor libraries either fetch assets at runtime (blocked) or emit
   markup the public .article-body styles do not cover. Everything
   here is self-hosted and same-origin.

   Attaches to:  <textarea data-editor ...>
   On submit it writes clean, whitelisted HTML back into that same
   textarea, so admin/pages/blog.php keeps working untouched.
   If JavaScript fails the plain textarea simply stays usable.
   ============================================================ */
(function () {
  'use strict';

  var UPLOAD_URL = (window.ITX_ED && window.ITX_ED.upload) || '';

  /* ── Output whitelist — mirrors assets/css/app.css .article-body ── */
  var BLOCK  = ['P','H2','H3','H4','UL','OL','LI','BLOCKQUOTE','PRE','HR'];
  var INLINE = ['STRONG','EM','U','A','CODE','BR','IMG'];
  var RENAME = { B:'STRONG', I:'EM', DIV:'P', H1:'H2', H5:'H4', H6:'H4' };
  var DROP   = ['SCRIPT','STYLE','IFRAME','OBJECT','EMBED','FORM','INPUT','BUTTON',
                'SELECT','TEXTAREA','LINK','META','NOSCRIPT','SVG','VIDEO','AUDIO','TITLE'];

  function has(list, v) { return list.indexOf(v) !== -1; }

  /* ── Sanitize a subtree in place ───────────────────────────── */
  function sanitize(container) {
    Array.prototype.slice.call(container.childNodes).forEach(function (node) {
      if (node.nodeType === 3) return;                       // text — keep
      if (node.nodeType !== 1) { node.parentNode.removeChild(node); return; }

      var tag = node.tagName;
      if (has(DROP, tag)) { node.parentNode.removeChild(node); return; }

      // b → strong, i → em, div → p, h1/h5/h6 → h2/h4
      if (RENAME[tag]) {
        var swap = document.createElement(RENAME[tag].toLowerCase());
        while (node.firstChild) swap.appendChild(node.firstChild);
        node.parentNode.replaceChild(swap, node);
        node = swap;
        tag  = swap.tagName;
      }

      // Anything not whitelisted (span, font, table, …) → unwrap, keep text
      if (!has(BLOCK, tag) && !has(INLINE, tag)) {
        var frag = document.createDocumentFragment();
        while (node.firstChild) frag.appendChild(node.firstChild);
        sanitize(frag);
        node.parentNode.replaceChild(frag, node);
        return;
      }

      // Strip every attribute except the few that carry meaning
      Array.prototype.slice.call(node.attributes).forEach(function (attr) {
        var keep = (tag === 'A'   && ['href', 'target', 'rel'].indexOf(attr.name) !== -1)
                || (tag === 'IMG' && ['src', 'alt'].indexOf(attr.name) !== -1);
        if (!keep) node.removeAttribute(attr.name);
      });

      if (tag === 'A') {
        var href = node.getAttribute('href') || '';
        if (/^\s*javascript:/i.test(href)) node.setAttribute('href', '#');
        if (/^https?:\/\//i.test(href)) {
          node.setAttribute('target', '_blank');
          node.setAttribute('rel', 'noopener');
        }
      }
      if (tag === 'IMG') {
        if (/^\s*javascript:/i.test(node.getAttribute('src') || '')) node.parentNode.removeChild(node);
        return;                                              // void element
      }

      sanitize(node);
    });
  }

  /* Wrap loose text sitting directly in the root inside <p>. */
  function wrapStrayText(root) {
    Array.prototype.slice.call(root.childNodes).forEach(function (node) {
      var isStray = (node.nodeType === 3 && node.textContent.trim() !== '')
                 || (node.nodeType === 1 && has(INLINE, node.tagName) && node.tagName !== 'IMG');
      if (!isStray) return;
      var p = document.createElement('p');
      root.replaceChild(p, node);
      p.appendChild(node);
    });
  }

  /* Drop blocks that carry nothing — <p></p>, <p><br></p>, … */
  function dropEmpty(root) {
    Array.prototype.slice.call(root.querySelectorAll('p,h2,h3,h4,li,blockquote')).forEach(function (el) {
      if (el.querySelector('img')) return;
      if (el.textContent.replace(/ /g, ' ').trim() === '') el.parentNode.removeChild(el);
    });
  }

  /* Clean HTML string → clean HTML string, one block per line. */
  function clean(html) {
    var box = document.createElement('div');
    box.innerHTML = html;
    sanitize(box);
    wrapStrayText(box);
    dropEmpty(box);
    return Array.prototype.slice.call(box.children)
      .map(function (el) { return el.outerHTML; })
      .join('\n');
  }

  /* ── Toolbar definition ────────────────────────────────────── */
  var TOOLS = [
    { cmd: 'formatBlock', val: 'p',  icon: 'fa-paragraph',   title: 'فقرة عادية' },
    { cmd: 'formatBlock', val: 'h2', label: 'H2',            title: 'عنوان رئيسي' },
    { cmd: 'formatBlock', val: 'h3', label: 'H3',            title: 'عنوان فرعي' },
    { sep: true },
    { cmd: 'bold',                   icon: 'fa-bold',        title: 'عريض' },
    { cmd: 'italic',                 icon: 'fa-italic',      title: 'مائل' },
    { cmd: 'code',                   icon: 'fa-code',        title: 'كود مضمّن' },
    { sep: true },
    { cmd: 'insertUnorderedList',    icon: 'fa-list-ul',     title: 'قائمة نقطية' },
    { cmd: 'insertOrderedList',      icon: 'fa-list-ol',     title: 'قائمة مرقّمة' },
    { cmd: 'formatBlock', val: 'blockquote', icon: 'fa-quote-right', title: 'اقتباس' },
    { sep: true },
    { cmd: 'link',                   icon: 'fa-link',        title: 'إضافة رابط' },
    { cmd: 'unlink',                 icon: 'fa-unlink',      title: 'إزالة الرابط' },
    { cmd: 'image',                  icon: 'fa-image',       title: 'إدراج صورة' },
    { sep: true },
    { cmd: 'removeFormat',           icon: 'fa-eraser',      title: 'مسح التنسيق' },
    { cmd: 'undo',                   icon: 'fa-rotate-left', title: 'تراجع' },
    { cmd: 'redo',                   icon: 'fa-rotate-right',title: 'إعادة' },
    { spacer: true },
    { cmd: 'source', icon: 'fa-file-code', label: 'HTML', title: 'تحرير الكود مباشرة', cls: 'src-btn' }
  ];

  function toast(msg, type) {
    if (typeof window.showToast === 'function') window.showToast(msg, type || 'success');
    else if (type === 'danger') alert(msg);
  }

  /* ── Build one editor over one textarea ────────────────────── */
  function build(textarea) {
    if (textarea.dataset.edReady) return;
    textarea.dataset.edReady = '1';

    var rtl = (textarea.getAttribute('dir') || 'rtl') !== 'ltr';

    var wrap = document.createElement('div');
    wrap.className = 'itx-ed';

    var bar = document.createElement('div');
    bar.className = 'itx-ed-bar';

    var area = document.createElement('div');
    area.className = 'itx-ed-area';
    area.contentEditable = 'true';
    area.spellcheck = false;
    area.dir = rtl ? 'rtl' : 'ltr';
    area.setAttribute('data-placeholder', rtl ? 'ابدأ كتابة المقال هنا…' : 'Start writing here…');

    var src = document.createElement('textarea');
    src.className = 'itx-ed-src';
    src.spellcheck = false;

    var foot = document.createElement('div');
    foot.className = 'itx-ed-foot';
    var hint  = document.createElement('span');
    hint.textContent = 'اكتب بشكل طبيعي — التنسيق يُحفظ تلقائياً. زر HTML يعرض الكود الناتج.';
    var count = document.createElement('span');
    foot.appendChild(hint);
    foot.appendChild(count);

    textarea.classList.add('itx-ed-source-hidden');
    textarea.parentNode.insertBefore(wrap, textarea);
    wrap.appendChild(bar);
    wrap.appendChild(area);
    wrap.appendChild(src);
    wrap.appendChild(foot);
    wrap.appendChild(textarea);

    // Seed from whatever is already saved. Plain text (no tags) gets
    // its blank-line paragraphs turned into <p>, which is exactly the
    // case that was rendering as one unbroken block on the site.
    var initial = textarea.value.trim();
    if (initial !== '' && !/<[a-z][\s\S]*>/i.test(initial)) {
      initial = initial.split(/\n\s*\n/).map(function (para) {
        var d = document.createElement('div');
        d.textContent = para.trim();
        return '<p>' + d.innerHTML.replace(/\n/g, '<br>') + '</p>';
      }).join('\n');
    }
    area.innerHTML = initial;

    function updateCount() {
      var words = area.textContent.trim().split(/\s+/).filter(Boolean).length;
      count.textContent = words ? words + ' كلمة' : '';
    }

    function sync() {
      textarea.value = wrap.classList.contains('src-mode') ? src.value.trim() : clean(area.innerHTML);
    }

    function refreshStates() {
      if (wrap.classList.contains('src-mode')) return;
      var block = '';
      try { block = (document.queryCommandValue('formatBlock') || '').toLowerCase(); } catch (e) {}
      bar.querySelectorAll('button[data-cmd]').forEach(function (btn) {
        var cmd = btn.dataset.cmd, val = btn.dataset.val || '', on = false;
        if (cmd === 'formatBlock') on = (block === val);
        else if (['bold','italic','insertUnorderedList','insertOrderedList'].indexOf(cmd) !== -1) {
          try { on = document.queryCommandState(cmd); } catch (e) {}
        }
        btn.classList.toggle('on', !!on);
      });
    }

    /* ── Commands ── */
    function wrapCode() {
      var sel = window.getSelection();
      if (!sel || !sel.rangeCount || sel.isCollapsed) { toast('حدّد النص أولاً', 'danger'); return; }
      var range = sel.getRangeAt(0), code = document.createElement('code');
      try { range.surroundContents(code); }
      catch (e) { code.appendChild(range.extractContents()); range.insertNode(code); }
      sel.removeAllRanges();
    }

    function insertImageFile() {
      var picker = document.createElement('input');
      picker.type = 'file';
      picker.accept = 'image/*';
      picker.addEventListener('change', function () {
        var file = picker.files && picker.files[0];
        if (!file) return;
        if (!UPLOAD_URL) { toast('مسار الرفع غير مُعرّف', 'danger'); return; }
        var fd = new FormData();
        fd.append('file', file);
        fd.append('dir', 'uploads/blog');
        toast('جارٍ رفع الصورة…');
        fetch(UPLOAD_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            if (!j.success) { toast(j.message || 'فشل الرفع', 'danger'); return; }
            area.focus();
            // Store the RELATIVE path so the article keeps working whether
            // the site lives at the domain root or in a sub-folder.
            document.execCommand('insertHTML', false,
              '<img src="' + j.path.replace(/"/g, '&quot;') + '" alt="">');
            sync(); updateCount();
            toast('تم إدراج الصورة');
          })
          .catch(function () { toast('تعذّر الاتصال بالخادم', 'danger'); });
      });
      picker.click();
    }

    function run(cmd, val) {
      if (cmd === 'source') {
        if (wrap.classList.toggle('src-mode')) { src.value = clean(area.innerHTML); src.focus(); }
        else { area.innerHTML = clean(src.value); area.focus(); }
        bar.querySelector('.src-btn').classList.toggle('on', wrap.classList.contains('src-mode'));
        sync(); updateCount();
        return;
      }
      area.focus();
      if (cmd === 'code')  { wrapCode(); }
      else if (cmd === 'image') { insertImageFile(); return; }
      else if (cmd === 'link') {
        var url = window.prompt('رابط الوجهة:', 'https://');
        if (!url) return;
        if (/^\s*javascript:/i.test(url)) { toast('رابط غير مسموح', 'danger'); return; }
        document.execCommand('createLink', false, url);
      }
      else if (cmd === 'formatBlock') { document.execCommand('formatBlock', false, '<' + val + '>'); }
      else { document.execCommand(cmd, false, null); }
      sync(); updateCount(); refreshStates();
    }

    /* ── Render the toolbar ── */
    TOOLS.forEach(function (t) {
      if (t.sep)    { var s = document.createElement('span'); s.className = 'sep';    bar.appendChild(s); return; }
      if (t.spacer) { var x = document.createElement('span'); x.className = 'spacer'; bar.appendChild(x); return; }
      var b = document.createElement('button');
      b.type = 'button';
      b.title = t.title;
      b.dataset.cmd = t.cmd;
      if (t.val) b.dataset.val = t.val;
      if (t.cls) b.className = t.cls;
      b.innerHTML = (t.icon ? '<i class="fas ' + t.icon + '"></i>' : '')
                  + (t.label ? '<span>' + t.label + '</span>' : '');
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });   // keep the selection
      b.addEventListener('click', function () { run(t.cmd, t.val); });
      bar.appendChild(b);
    });

    /* ── Editing behaviour ── */
    try {
      document.execCommand('defaultParagraphSeparator', false, 'p');
      document.execCommand('styleWithCSS', false, false);   // <b>/<i>, not inline styles
    } catch (e) {}

    // Paste: strip Word/site markup down to the whitelist.
    area.addEventListener('paste', function (e) {
      var cb = e.clipboardData;
      if (!cb) return;
      e.preventDefault();
      var html = cb.getData('text/html');
      var out;
      if (html) {
        out = clean(html);
      } else {
        out = cb.getData('text/plain').split(/\n\s*\n/).map(function (para) {
          var d = document.createElement('div');
          d.textContent = para.trim();
          return d.innerHTML ? '<p>' + d.innerHTML.replace(/\n/g, '<br>') + '</p>' : '';
        }).join('');
      }
      document.execCommand('insertHTML', false, out);
      sync(); updateCount();
    });

    area.addEventListener('input', function () { sync(); updateCount(); });
    area.addEventListener('keyup', refreshStates);
    area.addEventListener('mouseup', refreshStates);
    area.addEventListener('focus', function () { wrap.classList.add('is-focus'); });
    area.addEventListener('blur',  function () { wrap.classList.remove('is-focus'); sync(); });
    src.addEventListener('input',  function () { sync(); });

    // Ctrl/Cmd + B / I
    area.addEventListener('keydown', function (e) {
      if (!(e.ctrlKey || e.metaKey)) return;
      var k = e.key.toLowerCase();
      if (k === 'b' || k === 'i') { e.preventDefault(); run(k === 'b' ? 'bold' : 'italic'); }
    });

    // Final guarantee: whatever happens, POST the cleaned HTML.
    var form = textarea.closest('form');
    if (form) form.addEventListener('submit', sync);

    sync();
    updateCount();
  }

  function init() {
    document.querySelectorAll('textarea[data-editor]').forEach(build);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
