<?php
// ─────────────────────────────────────────────────────────────
//  Works page — Categories + Projects + Media management
// ─────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

// ── Category actions ──────────────────────────────────────────
if ($action === 'save_cat') {
    $id = (int)($_POST['id'] ?? 0);
    $d  = [trim($_POST['name'] ?? ''), trim($_POST['icon'] ?? 'fas fa-folder'),
           trim($_POST['slug'] ?? ''), (int)($_POST['sort_order'] ?? 0)];
    if (empty($d[0])) { set_flash('اسم الفئة مطلوب','danger'); header('Location: '.admin_url('works')); exit; }
    if (empty($d[2])) $d[2] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $d[0]));
    if ($id) db_exec("UPDATE categories SET name=?,icon=?,slug=?,sort_order=? WHERE id=?", array_merge($d,[$id]));
    else     db_exec("INSERT INTO categories (name,icon,slug,sort_order) VALUES (?,?,?,?)", $d);
    redirect_admin('works', $id ? 'تم تعديل الفئة' : 'تمت إضافة الفئة');
}
if ($action === 'del_cat') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && !db_count('projects','category_id=?',[$id])) {
        db_exec("DELETE FROM categories WHERE id=?",[$id]);
        redirect_admin('works','تم حذف الفئة','danger');
    }
    redirect_admin('works','لا يمكن حذف فئة تحتوي على مشاريع','danger');
}

// ── Project actions ───────────────────────────────────────────
if ($action === 'save_proj') {
    $id       = (int)($_POST['id'] ?? 0);
    $existing = $id ? (db_row("SELECT thumbnail FROM projects WHERE id=?",[$id])['thumbnail'] ?? '') : '';
    $thumb    = handle_image_input('thumb_file','thumb_url', $existing, 'uploads/thumbnails');
    $isProg   = isset($_POST['is_programming']) ? 1 : 0;
    $demoUrl  = $isProg ? trim($_POST['demo_url'] ?? '') : null;

    $d = [
        (int)$_POST['category_id'],
        trim($_POST['title']       ?? ''),
        trim($_POST['description'] ?? ''),
        trim($_POST['short_desc']  ?? ''),
        $thumb,
        $isProg,
        $demoUrl,
        trim($_POST['client_name']  ?? ''),
        (int)($_POST['project_year'] ?? date('Y')),
        trim($_POST['technologies'] ?? ''),
        $_POST['status']     ?? 'active',
        (int)($_POST['sort_order'] ?? 0),
    ];

    if ($id) {
        db_exec("UPDATE projects SET category_id=?,title=?,description=?,short_desc=?,thumbnail=?,
                 is_programming=?,demo_url=?,client_name=?,project_year=?,technologies=?,
                 status=?,sort_order=? WHERE id=?", array_merge($d,[$id]));
        redirect_admin('works&tab=projects','تم تعديل المشروع');
    } else {
        $newId = db_exec("INSERT INTO projects (category_id,title,description,short_desc,thumbnail,
                 is_programming,demo_url,client_name,project_year,technologies,status,sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)", $d);
        redirect_admin('works&tab=projects','تمت إضافة المشروع');
    }
}
if ($action === 'del_proj') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        // Delete all associated media files first
        $medias = db_all("SELECT type,url,thumbnail FROM project_media WHERE project_id=?", [$id]);
        foreach ($medias as $m) {
            if ($m['type'] === 'image') delete_old_image($m['url']);
            delete_old_image($m['thumbnail']);
        }
        // Delete thumbnail
        $proj = db_row("SELECT thumbnail FROM projects WHERE id=?", [$id]);
        if ($proj) delete_old_image($proj['thumbnail']);
        db_exec("DELETE FROM projects WHERE id=?", [$id]);
    }
    redirect_admin('works&tab=projects','تم حذف المشروع','danger');
}

// ── Media actions ─────────────────────────────────────────────
if ($action === 'add_media') {
    $pid  = (int)($_POST['proj_id'] ?? 0);
    $type = $_POST['media_type'] === 'video' ? 'video' : 'image';
    $url  = '';
    if ($type === 'image') {
        $url = handle_image_input('media_file','media_url', '', 'uploads/media');
    } else {
        $url = trim($_POST['media_url'] ?? '');
    }
    if ($pid && $url) {
        db_exec("INSERT INTO project_media (project_id,type,url,thumbnail,caption,sort_order) VALUES (?,?,?,?,?,?)",
            [$pid, $type, $url,
             trim($_POST['media_thumb'] ?? '') ?: ($type==='image' ? $url : ''),
             trim($_POST['media_caption'] ?? ''),
             (int)($_POST['media_order'] ?? 0)]);
        redirect_admin("works&tab=projects&media=$pid",'تمت إضافة الوسائط');
    }
    redirect_admin("works&tab=projects&media=$pid",'بيانات غير مكتملة','danger');
}
if ($action === 'del_media') {
    $id  = (int)($_POST['id']  ?? 0);
    $pid = (int)($_POST['pid'] ?? 0);
    if ($id) {
        $m = db_row("SELECT type,url,thumbnail FROM project_media WHERE id=?", [$id]);
        if ($m) {
            if ($m['type'] === 'image') delete_old_image($m['url']);
            delete_old_image($m['thumbnail']);
        }
        db_exec("DELETE FROM project_media WHERE id=?", [$id]);
    }
    redirect_admin("works&tab=projects&media=$pid",'تم حذف الوسائط','danger');
}

// ── Data ──────────────────────────────────────────────────────
$categories = db_all("SELECT * FROM categories ORDER BY sort_order");
$catFilter  = (int)($_GET['cat'] ?? 0);
$projects   = $catFilter
    ? db_all("SELECT p.*,c.name AS cat_name FROM projects p JOIN categories c ON c.id=p.category_id
               WHERE p.category_id=? ORDER BY p.sort_order"   ,[$catFilter])
    : db_all("SELECT p.*,c.name AS cat_name FROM projects p JOIN categories c ON c.id=p.category_id
               ORDER BY p.sort_order");

$editCat  = !empty($_GET['edit_cat'])  ? db_row("SELECT * FROM categories WHERE id=?",[(int)$_GET['edit_cat']])  : null;
$editProj = !empty($_GET['edit_proj']) ? db_row("SELECT * FROM projects  WHERE id=?",[(int)$_GET['edit_proj']]) : null;
$mediaProj= !empty($_GET['media'])     ? (int)$_GET['media'] : 0;
$mediaList= $mediaProj ? db_all("SELECT * FROM project_media WHERE project_id=? ORDER BY sort_order",[$mediaProj]) : [];
$mediaProjName = $mediaProj ? (db_row("SELECT title FROM projects WHERE id=?",[$mediaProj])['title'] ?? '') : '';

$activeTab = $_GET['tab'] ?? 'categories';

layout_start('أعمالنا — الفئات والمشاريع', 'works');
?>
<!-- ── Tabs ─────────────────────────────────────────────────── -->
<div id="worksGroup">
<div class="pg-tabs">
  <button class="pg-tab <?= $activeTab !== 'projects' ? 'active' : '' ?>"
          data-tab="tab-cats" onclick="switchTab('worksGroup','tab-cats')">
    <i class="fas fa-th-large"></i> الفئات
  </button>
  <button class="pg-tab <?= $activeTab === 'projects' ? 'active' : '' ?>"
          data-tab="tab-projs" onclick="switchTab('worksGroup','tab-projs')">
    <i class="fas fa-briefcase"></i> المشاريع
  </button>
</div>

<!-- ═══ CATEGORIES ══════════════════════════════════════════ -->
<div id="tab-cats" class="tab-pane <?= $activeTab !== 'projects' ? 'active' : '' ?>">
<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-th-large"></i> الفئات</h2>
    <button class="btn btn-primary btn-sm" onclick="openModal('catModal')"><i class="fas fa-plus"></i> إضافة فئة</button>
  </div>
  <div class="card-body p0">
    <table class="tbl">
      <thead><tr><th>الأيقونة</th><th>الاسم</th><th>Slug</th><th>الترتيب</th><th>المشاريع</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php if ($categories): foreach ($categories as $c): ?>
        <tr>
          <td><i class="<?= e($c['icon']) ?>" style="font-size:1.4rem;color:var(--secondary)"></i></td>
          <td><strong><?= e($c['name']) ?></strong></td>
          <td><code><?= e($c['slug']) ?></code></td>
          <td><?= (int)$c['sort_order'] ?></td>
          <td><span class="badge badge-cat"><?= db_count('projects','category_id=?',[$c['id']]) ?> مشروع</span></td>
          <td>
            <div class="actions">
              <a href="?page=works&edit_cat=<?= $c['id'] ?>" class="btn btn-warning btn-xs btn-icon"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="del_cat">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button class="btn btn-danger btn-xs btn-icon" data-confirm="حذف '<?= e($c['name']) ?>'؟ (لا يمكن حذف فئة تحتوي مشاريع)"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="6"><i class="fas fa-inbox"></i> لا توجد فئات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div><!-- /tab-cats -->

<!-- ═══ PROJECTS ════════════════════════════════════════════ -->
<div id="tab-projs" class="tab-pane <?= $activeTab === 'projects' ? 'active' : '' ?>">

<?php if ($mediaProj): // ── Media management sub-view ──── ?>
<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-images"></i> وسائط مشروع: <?= e($mediaProjName) ?></h2>
    <a href="?page=works&tab=projects" class="btn btn-outline btn-sm"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
  <div class="card-body">
    <!-- Existing media -->
    <?php if ($mediaList): ?>
    <div class="media-grid" style="margin-bottom:1.5rem">
      <?php foreach ($mediaList as $m): ?>
      <div class="media-item">
        <span class="media-item-type"><?= $m['type'] === 'video' ? '▶ فيديو' : '🖼 صورة' ?></span>
        <?php if ($m['type'] === 'image'): ?>
          <img src="<?= e(img_url($m['url'])) ?>" onerror="this.src='https://via.placeholder.com/140x90?text=Error'">
        <?php else: ?>
          <div class="media-video-thumb"><i class="fas fa-play-circle"></i></div>
        <?php endif; ?>
        <div class="media-item-caption"><?= e($m['caption'] ?: '—') ?></div>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="del_media">
          <input type="hidden" name="id" value="<?= $m['id'] ?>">
          <input type="hidden" name="pid" value="<?= $mediaProj ?>">
          <button class="media-item-del" data-confirm="حذف الوسائط؟"><i class="fas fa-times"></i></button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p class="text-muted text-sm" style="margin-bottom:1rem"><i class="fas fa-info-circle"></i> لا توجد وسائط بعد</p>
    <?php endif; ?>

    <!-- Add media form -->
    <div class="add-media-form">
      <p style="font-weight:700;color:var(--primary);margin-bottom:1rem"><i class="fas fa-plus-circle"></i> إضافة وسائط جديدة</p>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_media">
        <input type="hidden" name="proj_id" value="<?= $mediaProj ?>">
        <div class="form-grid">
          <div class="fg">
            <label>نوع الوسائط</label>
            <select name="media_type" id="mediaTypeSelect" onchange="toggleMediaInput(this.value)">
              <option value="image">صورة</option>
              <option value="video">فيديو (YouTube embed)</option>
            </select>
          </div>
          <div class="fg">
            <label>التسمية التوضيحية</label>
            <input type="text" name="media_caption" placeholder="وصف مختصر للصورة/الفيديو">
          </div>

          <!-- Image input -->
          <div class="fg full" id="media-img-input">
            <label>الصورة</label>
            <div class="img-wrap">
              <div class="img-tabs">
                <button type="button" class="img-tab-btn active" data-tab="url">رابط URL</button>
                <button type="button" class="img-tab-btn" data-tab="file">رفع من الجهاز</button>
              </div>
              <div class="img-tab active" data-tab="url">
                <input type="text" name="media_url" class="img-url-inp" placeholder="https://...">
              </div>
              <div class="img-tab" data-tab="file">
                <input type="file" name="media_file" class="img-file-inp" accept="image/*">
              </div>
              <img class="img-preview" style="max-height:80px">
            </div>
          </div>

          <!-- Video input -->
          <div class="fg full" id="media-vid-input" style="display:none">
            <label>رابط تضمين YouTube (embed URL)</label>
            <input type="text" name="media_url" placeholder="https://www.youtube.com/embed/VIDEO_ID">
            <small>مثال: https://www.youtube.com/embed/dQw4w9WgXcQ</small>
          </div>

          <div class="fg" id="media-thumb-row" style="display:none">
            <label>صورة مصغّرة للفيديو (اختياري)</label>
            <input type="text" name="media_thumb" placeholder="https://... أو اتركه فارغاً">
          </div>

          <div class="fg">
            <label>الترتيب</label>
            <input type="number" name="media_order" value="0" min="0">
          </div>
        </div>
        <div style="margin-top:1rem">
          <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> إضافة</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php else: // ── Projects list ──────────────────────────────── ?>
<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-briefcase"></i> المشاريع</h2>
    <div class="flex-row">
      <select onchange="window.location='?page=works&tab=projects&cat='+this.value" style="padding:.4rem .8rem;border-radius:7px;border:1.5px solid var(--border);font-family:Cairo,sans-serif;font-size:.85rem">
        <option value="0" <?= !$catFilter?'selected':'' ?>>كل الفئات</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catFilter===$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-sm" onclick="openModal('projModal')"><i class="fas fa-plus"></i> إضافة مشروع</button>
    </div>
  </div>
  <div class="card-body p0">
    <table class="tbl">
      <thead><tr><th>الصورة</th><th>المشروع</th><th>الفئة</th><th>العميل</th><th>السنة</th><th>نوع</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php if ($projects): foreach ($projects as $p): ?>
        <tr>
          <td><img class="thumb" src="<?= e(img_url($p['thumbnail'])) ?>"
               onerror="this.src='https://via.placeholder.com/55x40?text=?'"></td>
          <td><strong><?= e($p['title']) ?></strong></td>
          <td><span class="badge badge-cat"><?= e($p['cat_name']) ?></span></td>
          <td><?= e($p['client_name'] ?: '—') ?></td>
          <td><?= e($p['project_year'] ?: '—') ?></td>
          <td>
            <?php if ($p['is_programming']): ?>
              <span class="badge badge-prog"><i class="fas fa-code"></i> برمجية</span>
              <?php if ($p['demo_url']): ?>
                <a href="<?= e($p['demo_url']) ?>" target="_blank" class="btn btn-xs" style="background:#e0f2fe;color:#0369a1;margin-right:.3rem">Demo</a>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge badge-cat">عادي</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-<?= $p['status']==='active'?'active':'inactive' ?>"><?= $p['status']==='active'?'نشط':'مخفي' ?></span></td>
          <td>
            <div class="actions">
              <a href="?page=works&tab=projects&edit_proj=<?= $p['id'] ?>" class="btn btn-warning btn-xs btn-icon" title="تعديل"><i class="fas fa-edit"></i></a>
              <a href="?page=works&tab=projects&media=<?= $p['id'] ?>" class="btn btn-xs btn-icon" style="background:#e0f2fe;color:#0369a1" title="الوسائط"><i class="fas fa-images"></i></a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="del_proj">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button class="btn btn-danger btn-xs btn-icon" data-confirm="حذف '<?= e($p['title']) ?>'؟"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="8"><i class="fas fa-inbox"></i> لا توجد مشاريع</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; // end media/projects check ?>
</div><!-- /tab-projs -->
</div><!-- /worksGroup -->

<!-- ═══ CATEGORY MODAL ══════════════════════════════════════ -->
<div class="modal-bg <?= $editCat ? 'open' : '' ?>" id="catModal">
  <div class="modal-dlg">
    <div class="modal-hd">
      <h3><?= $editCat ? 'تعديل الفئة' : 'إضافة فئة' ?></h3>
      <button class="modal-close" onclick="closeModal('catModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_cat">
      <input type="hidden" name="id" value="<?= (int)($editCat['id'] ?? 0) ?>">
      <div class="modal-bd">
        <div class="form-grid">
          <div class="fg full">
            <label>اسم الفئة <span class="req">*</span></label>
            <input type="text" name="name" value="<?= e($editCat['name'] ?? '') ?>" required>
          </div>
          <div class="fg full">
            <label>أيقونة FontAwesome</label>
            <div style="display:flex;gap:.5rem;align-items:center">
              <input type="text" name="icon" class="icon-inp" value="<?= e($editCat['icon'] ?? 'fas fa-folder') ?>"
                     placeholder="fas fa-code" style="flex:1">
              <span class="icon-preview <?= e($editCat['icon'] ?? 'fas fa-folder') ?>"></span>
            </div>
          </div>
          <div class="fg">
            <label>Slug (بالإنجليزية)</label>
            <input type="text" name="slug" value="<?= e($editCat['slug'] ?? '') ?>" placeholder="programming">
            <small>اتركه فارغاً للتوليد التلقائي</small>
          </div>
          <div class="fg">
            <label>ترتيب العرض</label>
            <input type="number" name="sort_order" value="<?= (int)($editCat['sort_order'] ?? 0) ?>" min="0">
          </div>
        </div>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn btn-outline" onclick="closeModal('catModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ PROJECT MODAL ════════════════════════════════════════ -->
<div class="modal-bg <?= $editProj ? 'open' : '' ?>" id="projModal">
  <div class="modal-dlg xl">
    <div class="modal-hd">
      <h3><i class="fas fa-briefcase"></i> <?= $editProj ? 'تعديل المشروع' : 'إضافة مشروع' ?></h3>
      <button class="modal-close" onclick="closeModal('projModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_proj">
      <input type="hidden" name="id" value="<?= (int)($editProj['id'] ?? 0) ?>">
      <div class="modal-bd">
        <div class="form-grid">
          <!-- Basic info -->
          <div class="fg full">
            <label>عنوان المشروع <span class="req">*</span></label>
            <input type="text" name="title" value="<?= e($editProj['title'] ?? '') ?>" required>
          </div>
          <div class="fg">
            <label>الفئة <span class="req">*</span></label>
            <select name="category_id" required>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($editProj['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label>اسم العميل</label>
            <input type="text" name="client_name" value="<?= e($editProj['client_name'] ?? '') ?>">
          </div>
          <div class="fg">
            <label>سنة المشروع</label>
            <input type="number" name="project_year" value="<?= e($editProj['project_year'] ?? date('Y')) ?>" min="2000" max="2099">
          </div>
          <div class="fg">
            <label>الترتيب</label>
            <input type="number" name="sort_order" value="<?= (int)($editProj['sort_order'] ?? 0) ?>" min="0">
          </div>
          <div class="fg full">
            <label>وصف مختصر (يظهر على البطاقة)</label>
            <input type="text" name="short_desc" value="<?= e($editProj['short_desc'] ?? '') ?>" data-maxlen="180">
          </div>
          <div class="fg full">
            <label>الوصف التفصيلي</label>
            <textarea name="description" rows="5"><?= e($editProj['description'] ?? '') ?></textarea>
          </div>
          <div class="fg full">
            <label>التقنيات المستخدمة (مفصولة بفاصلة)</label>
            <input type="text" name="technologies" value="<?= e($editProj['technologies'] ?? '') ?>"
                   placeholder="PHP, Laravel, MySQL, Vue.js">
          </div>

          <!-- Thumbnail -->
          <div class="fg full">
            <label>الصورة المصغّرة للبطاقة</label>
            <?php $curThumb = $editProj['thumbnail'] ?? ''; ?>
            <div class="img-wrap">
              <div class="img-tabs">
                <button type="button" class="img-tab-btn active" data-tab="url">رابط URL</button>
                <button type="button" class="img-tab-btn" data-tab="file">رفع من الجهاز</button>
              </div>
              <div class="img-tab active" data-tab="url">
                <input type="text" name="thumb_url" class="img-url-inp" value="<?= e($curThumb) ?>" placeholder="https://...">
              </div>
              <div class="img-tab" data-tab="file">
                <input type="file" name="thumb_file" class="img-file-inp" accept="image/*">
              </div>
              <div class="img-preview-wrap">
                <?php if ($curThumb): ?>
                  <img src="<?= e(img_url($curThumb)) ?>" class="img-preview show" style="max-height:70px">
                <?php else: ?>
                  <img class="img-preview" style="max-height:70px">
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Programming toggle -->
          <div class="fg full">
            <div class="toggle-wrap">
              <label class="toggle">
                <input type="checkbox" name="is_programming" id="is_programming"
                       <?= !empty($editProj['is_programming']) ? 'checked' : '' ?>>
                <span class="toggle-slider"></span>
              </label>
              <span class="toggle-label"><i class="fas fa-code"></i> مشروع برمجي</span>
            </div>
          </div>

          <!-- Demo URL (shown only when is_programming is checked) -->
          <div class="fg full" id="demo-url-row" style="display:<?= !empty($editProj['is_programming']) ? 'flex' : 'none' ?>;flex-direction:column;gap:.4rem">
            <label><i class="fas fa-rocket"></i> رابط الديمو (اختياري)</label>
            <input type="text" name="demo_url" value="<?= e($editProj['demo_url'] ?? '') ?>"
                   placeholder="https://demo.example.com">
            <small>اتركه فارغاً إذا لم يكن هناك ديمو. يظهر زر "تجربة الديمو" فقط عند وجود رابط.</small>
          </div>

          <div class="fg">
            <label>الحالة</label>
            <select name="status">
              <option value="active"   <?= ($editProj['status'] ?? 'active')==='active'  ?'selected':'' ?>>نشط</option>
              <option value="inactive" <?= ($editProj['status'] ?? '')==='inactive'?'selected':'' ?>>مخفي</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn btn-outline" onclick="closeModal('projModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ المشروع</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleMediaInput(type) {
  document.getElementById('media-img-input').style.display = type === 'image' ? 'block' : 'none';
  document.getElementById('media-vid-input').style.display = type === 'video' ? 'block' : 'none';
  document.getElementById('media-thumb-row').style.display = type === 'video' ? 'flex' : 'none';
}
// Re-init img inputs inside modals
document.querySelectorAll('.modal-dlg .img-wrap').forEach(w => {
  const tabs = w.querySelectorAll('.img-tab-btn');
  const panels = w.querySelectorAll('.img-tab');
  const preview = w.querySelector('.img-preview');
  const urlInp = w.querySelector('.img-url-inp');
  const fileInp = w.querySelector('.img-file-inp');
  tabs.forEach(btn => {
    btn.addEventListener('click', () => {
      tabs.forEach(b => b.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      w.querySelector(`.img-tab[data-tab="${btn.dataset.tab}"]`).classList.add('active');
    });
  });
  if (urlInp && preview) urlInp.addEventListener('input', () => { if (urlInp.value) { preview.src = urlInp.value; preview.classList.add('show'); } });
  if (fileInp && preview) fileInp.addEventListener('change', () => { const f = fileInp.files[0]; if (f) { const r = new FileReader(); r.onload = e => { preview.src = e.target.result; preview.classList.add('show'); }; r.readAsDataURL(f); } });
});
</script>
<?php layout_end(); ?>
