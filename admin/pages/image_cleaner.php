<?php
// ─────────────────────────────────────────────────────────────
//  Image Cleaner — scan uploads/ for orphaned files
// ─────────────────────────────────────────────────────────────

$action   = $_POST['action'] ?? '';
$orphaned = [];
$scanned  = false;
$deleted  = 0;

// ── Delete action ──────────────────────────────────────────────
if ($action === 'delete_orphans') {
    $files   = $_POST['files'] ?? [];
    $uploads = realpath(ROOT_PATH . '/uploads');
    foreach ($files as $rel) {
        $rel = ltrim(preg_replace('#\.\.+[/\\\\]#', '', str_replace('\\', '/', $rel)), '/');
        $abs = realpath(ROOT_PATH . '/' . $rel);
        if ($abs && $uploads && strpos($abs, $uploads) === 0 && is_file($abs)) {
            if (@unlink($abs)) $deleted++;
        }
    }
    redirect_admin('image_cleaner', "تم حذف {$deleted} صورة غير مستخدمة بنجاح", $deleted > 0 ? 'success' : 'warning');
}

// ── Scan action ────────────────────────────────────────────────
if ($action === 'scan') {
    $scanned = true;

    // 1. Collect all local image paths referenced in the DB
    $used = [];
    $add  = function($p) use (&$used) {
        if (!empty($p) && !preg_match('#^https?://#', $p))
            $used[] = ltrim(str_replace('\\', '/', $p), '/');
    };

    // site_settings
    try { $add(get_setting('site_logo', '')); } catch (\Exception $e) {}

    // about_section
    try { $row = db_row("SELECT image FROM about_section WHERE id=1"); $add($row['image'] ?? ''); } catch (\Exception $e) {}

    // projects thumbnails
    try {
        foreach (db_all("SELECT thumbnail FROM projects WHERE thumbnail IS NOT NULL AND thumbnail!=''") as $r)
            $add($r['thumbnail']);
    } catch (\Exception $e) {}

    // project_media
    try {
        foreach (db_all("SELECT type,url,thumbnail FROM project_media") as $r) {
            if ($r['type'] === 'image') $add($r['url']);
            $add($r['thumbnail'] ?? '');
        }
    } catch (\Exception $e) {}

    // blog_posts thumbnails
    try {
        foreach (db_all("SELECT thumbnail FROM blog_posts WHERE thumbnail IS NOT NULL AND thumbnail!=''") as $r)
            $add($r['thumbnail']);
    } catch (\Exception $e) {}

    $used = array_unique($used);

    // 2. Scan uploads/ recursively for image files
    $uploads_dir = ROOT_PATH . '/uploads/';
    $all_files   = [];
    if (is_dir($uploads_dir)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploads_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile() && preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $file->getFilename())) {
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(ROOT_PATH) + 1));
                $all_files[] = $rel;
            }
        }
    }

    // 3. Find orphaned = files not referenced in DB
    $orphaned = array_values(array_filter($all_files, fn($f) => !in_array($f, $used)));
}

layout_start('منظف الصور', 'image_cleaner');
?>
<style>
.cleaner-info{background:var(--bg);border:1.5px solid var(--border);border-radius:10px;padding:1.2rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:1rem}
.cleaner-info i{font-size:1.6rem;color:var(--secondary);margin-top:.1rem;flex-shrink:0}
.orphan-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;margin-top:1.2rem}
.orphan-card{border:2px solid var(--border);border-radius:10px;overflow:hidden;background:#fff;transition:border-color .2s;position:relative}
.orphan-card.selected{border-color:var(--danger);background:#fff5f5}
.orphan-card label{cursor:pointer;display:block}
.orphan-card img{width:100%;height:120px;object-fit:cover;display:block;background:#f0f0f0}
.orphan-card .orphan-name{padding:.5rem .7rem;font-size:.72rem;color:var(--muted);word-break:break-all}
.orphan-card .orphan-size{padding:0 .7rem .5rem;font-size:.75rem;color:var(--txt);font-weight:700}
.orphan-chk{position:absolute;top:8px;right:8px;width:20px;height:20px;accent-color:var(--danger)}
.select-bar{display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem;padding:.75rem;background:var(--bg);border-radius:8px;border:1px solid var(--border)}
.count-badge{background:var(--danger);color:#fff;padding:.15rem .6rem;border-radius:50px;font-size:.78rem;font-weight:700}
</style>

<div class="cleaner-info">
  <i class="fas fa-broom"></i>
  <div>
    <strong>منظف الصور غير المستخدمة</strong><br>
    <span style="font-size:.88rem;color:var(--muted)">
      يفحص هذا الأداة جميع الصور في مجلد <code>uploads/</code> ويعرض الصور التي لا تُستخدم في أي مكان بالموقع
      (اللوقو، عن الشركة، المشاريع، الوسائط، المدونة). ثم يتيح لك حذف الصور الغير مستخدمة بعد موافقتك.
    </span>
  </div>
</div>

<!-- Scan form -->
<form method="POST">
  <input type="hidden" name="action" value="scan">
  <button type="submit" class="btn btn-primary" style="margin-bottom:1.5rem">
    <i class="fas fa-search"></i> فحص مجلد الصور الآن
  </button>
</form>

<?php if ($scanned): ?>
  <div class="card">
    <div class="card-head">
      <h2><i class="fas fa-images"></i> نتيجة الفحص</h2>
      <span style="font-size:.85rem;color:var(--muted)">
        <?= count($orphaned) ?> صورة غير مستخدمة من أصل <?= count($all_files ?? []) ?> صورة
      </span>
    </div>
    <div class="card-body">
      <?php if (empty($orphaned)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> ممتاز! لا توجد صور غير مستخدمة في مجلد الرفع.</div>
      <?php else: ?>
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i>
          وُجدت <strong><?= count($orphaned) ?></strong> صورة غير مستخدمة.
          راجع الصور أدناه وحدد ما تريد حذفه ثم اضغط "حذف المحدد".
        </div>

        <form method="POST" id="deleteForm" onsubmit="return confirmDelete()">
          <input type="hidden" name="action" value="delete_orphans">

          <div class="select-bar">
            <button type="button" class="btn btn-sm btn-outline" onclick="toggleAll(true)">
              <i class="fas fa-check-square"></i> تحديد الكل
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="toggleAll(false)">
              <i class="fas fa-square"></i> إلغاء التحديد
            </button>
            <span id="selCount" class="count-badge">0</span> محدد
            <button type="submit" class="btn btn-danger btn-sm" style="margin-right:auto">
              <i class="fas fa-trash-alt"></i> حذف المحدد
            </button>
          </div>

          <div class="orphan-grid">
            <?php foreach ($orphaned as $rel): ?>
              <?php
                $abs     = ROOT_PATH . '/' . $rel;
                $size    = is_file($abs) ? round(filesize($abs) / 1024, 1) . ' KB' : '—';
                $imgSrc  = img_url($rel);
                $fname   = basename($rel);
              ?>
              <div class="orphan-card" id="card-<?= md5($rel) ?>">
                <label for="chk-<?= md5($rel) ?>">
                  <img src="<?= e($imgSrc) ?>" alt="<?= e($fname) ?>"
                       onerror="this.src='https://placehold.co/180x120?text=IMG'">
                  <div class="orphan-name"><?= e($fname) ?></div>
                  <div class="orphan-size"><?= e($size) ?></div>
                </label>
                <input type="checkbox" class="orphan-chk file-chk" name="files[]"
                       value="<?= e($rel) ?>" id="chk-<?= md5($rel) ?>"
                       onchange="updateCard(this)">
              </div>
            <?php endforeach; ?>
          </div>

        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<script>
function toggleAll(state) {
    document.querySelectorAll('.file-chk').forEach(c => {
        c.checked = state;
        updateCard(c);
    });
}
function updateCard(chk) {
    const card = document.getElementById('card-' + chk.id.replace('chk-', ''));
    if (card) card.classList.toggle('selected', chk.checked);
    const cnt = document.querySelectorAll('.file-chk:checked').length;
    const badge = document.getElementById('selCount');
    if (badge) badge.textContent = cnt;
}
function confirmDelete() {
    const cnt = document.querySelectorAll('.file-chk:checked').length;
    if (cnt === 0) { alert('لم تحدد أي صورة للحذف.'); return false; }
    return confirm(`هل أنت متأكد من حذف ${cnt} صورة؟ لا يمكن التراجع عن هذه العملية.`);
}
</script>

<?php layout_end(); ?>
