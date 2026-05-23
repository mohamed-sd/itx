<?php
// ─────────────────────────────────────────────────────────────
//  Blog admin — Categories + Posts
// ─────────────────────────────────────────────────────────────

// ── Slug helpers ──────────────────────────────────────────────
function blog_slugify(string $text): string {
    $ar = ['أ','إ','آ','ا','ب','ت','ث','ج','ح','خ','د','ذ','ر','ز','س','ش','ص','ض','ط','ظ','ع','غ','ف','ق','ك','ل','م','ن','ه','و','ي','ة','ى','ئ','ء','ؤ','لا'];
    $en = ['a','i','a','a','b','t','th','j','h','kh','d','th','r','z','s','sh','s','d','t','z','a','gh','f','q','k','l','m','n','h','w','y','h','a','y','a','w','la'];
    $text = str_replace($ar, $en, $text);
    $text = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $text), '-'));
    return $text ?: 'post-' . time();
}
function blog_unique_slug(string $base, int $excludeId = 0): string {
    $orig = $base; $i = 1;
    while (true) {
        $row = $excludeId
            ? db_row("SELECT id FROM blog_posts WHERE slug=? AND id!=?", [$base, $excludeId])
            : db_row("SELECT id FROM blog_posts WHERE slug=?", [$base]);
        if (empty($row)) break;
        $base = $orig . '-' . $i++;
    }
    return $base;
}
function bcat_unique_slug(string $base, int $excludeId = 0): string {
    $orig = $base; $i = 1;
    while (true) {
        $row = $excludeId
            ? db_row("SELECT id FROM blog_categories WHERE slug=? AND id!=?", [$base, $excludeId])
            : db_row("SELECT id FROM blog_categories WHERE slug=?", [$base]);
        if (empty($row)) break;
        $base = $orig . '-' . $i++;
    }
    return $base;
}

$action = $_POST['action'] ?? '';

// ── Category actions ──────────────────────────────────────────
if ($action === 'save_bcat') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: blog_slugify($name);
    $slug = bcat_unique_slug($slug, $id);
    $d    = [$name, $slug, (int)($_POST['sort_order'] ?? 0), $_POST['status'] ?? 'active'];
    if ($id) db_exec("UPDATE blog_categories SET name=?,slug=?,sort_order=?,status=? WHERE id=?", array_merge($d, [$id]));
    else     db_exec("INSERT INTO blog_categories (name,slug,sort_order,status) VALUES (?,?,?,?)", $d);
    redirect_admin('blog&tab=categories', $id ? 'تم تعديل الفئة' : 'تمت إضافة الفئة');
}
if ($action === 'del_bcat') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && !db_count('blog_posts', 'category_id=?', [$id])) {
        db_exec("DELETE FROM blog_categories WHERE id=?", [$id]);
        redirect_admin('blog&tab=categories', 'تم حذف الفئة', 'danger');
    }
    redirect_admin('blog&tab=categories', 'لا يمكن حذف فئة تحتوي على مقالات', 'danger');
}

// ── Post actions ──────────────────────────────────────────────
if ($action === 'save_post') {
    $id       = (int)($_POST['id'] ?? 0);
    $title    = trim($_POST['title'] ?? '');
    $slug     = trim($_POST['slug']  ?? '') ?: blog_slugify($title);
    $slug     = blog_unique_slug($slug, $id);
    $existing = $id ? (db_row("SELECT thumbnail FROM blog_posts WHERE id=?", [$id])['thumbnail'] ?? '') : '';
    $thumb    = handle_image_input('thumb_file', 'thumb_url', $existing, 'uploads/blog');
    $d = [
        (int)($_POST['category_id'] ?? 0) ?: null,
        $title, $slug,
        trim($_POST['excerpt']          ?? ''),
        $_POST['content']               ?? '',
        $thumb,
        trim($_POST['author']           ?? 'فريق ITX'),
        trim($_POST['tags']             ?? ''),
        trim($_POST['meta_title']       ?? ''),
        trim($_POST['meta_description'] ?? ''),
        $_POST['status']                ?? 'published',
    ];
    if ($id) {
        db_exec("UPDATE blog_posts SET category_id=?,title=?,slug=?,excerpt=?,content=?,
                 thumbnail=?,author=?,tags=?,meta_title=?,meta_description=?,status=? WHERE id=?",
                 array_merge($d, [$id]));
        redirect_admin('blog', 'تم تعديل المقال');
    } else {
        db_exec("INSERT INTO blog_posts
                 (category_id,title,slug,excerpt,content,thumbnail,author,tags,meta_title,meta_description,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)", $d);
        redirect_admin('blog', 'تمت إضافة المقال');
    }
}
if ($action === 'del_post') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $post = db_row("SELECT thumbnail FROM blog_posts WHERE id=?", [$id]);
        if ($post) delete_old_image($post['thumbnail']);
        db_exec("DELETE FROM blog_posts WHERE id=?", [$id]);
    }
    redirect_admin('blog', 'تم حذف المقال', 'danger');
}

// ── Data ──────────────────────────────────────────────────────
$bcats    = db_all("SELECT * FROM blog_categories ORDER BY sort_order");
$posts    = db_all("SELECT p.*, c.name AS cat_name
                    FROM blog_posts p
                    LEFT JOIN blog_categories c ON c.id=p.category_id
                    ORDER BY p.created_at DESC");
$editPost = !empty($_GET['edit_post']) ? db_row("SELECT * FROM blog_posts WHERE id=?", [(int)$_GET['edit_post']]) : null;
$editCat  = !empty($_GET['edit_cat'])  ? db_row("SELECT * FROM blog_categories WHERE id=?", [(int)$_GET['edit_cat']]) : null;
$activeTab = $_GET['tab'] ?? 'posts';

layout_start('المدونة', 'blog');
?>
<style>
.blog-thumb-sm{width:60px;height:42px;object-fit:cover;border-radius:6px;border:1px solid var(--border)}
.blog-excerpt-cell{max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--muted);font-size:.83rem}
.slug-row{display:flex;gap:.5rem;align-items:center}
.slug-row input{flex:1}
.slug-gen{padding:.45rem .8rem;background:var(--bg);border:1.5px solid var(--border);border-radius:7px;cursor:pointer;font-size:.78rem;font-weight:700;color:var(--secondary);white-space:nowrap;font-family:'Cairo',sans-serif}
.slug-gen:hover{background:var(--secondary);color:#fff}
.draft-badge{background:#fef3c7;color:#92400e;padding:.2rem .6rem;border-radius:50px;font-size:.72rem;font-weight:700}
.pub-badge{background:#dcfce7;color:#166534;padding:.2rem .6rem;border-radius:50px;font-size:.72rem;font-weight:700}
</style>

<div id="blogTabGroup">
  <div class="pg-tabs">
    <button class="pg-tab <?= $activeTab==='posts'      ? 'active':'' ?>"
            data-tab="tab-posts" onclick="switchTab('blogTabGroup','tab-posts')">
      <i class="fas fa-newspaper"></i> المقالات (<?= count($posts) ?>)
    </button>
    <button class="pg-tab <?= $activeTab==='categories' ? 'active':'' ?>"
            data-tab="tab-categories" onclick="switchTab('blogTabGroup','tab-categories')">
      <i class="fas fa-tags"></i> الفئات (<?= count($bcats) ?>)
    </button>
  </div>

  <!-- ═══════════════ POSTS TAB ═══════════════ -->
  <div id="tab-posts" class="tab-pane <?= $activeTab==='posts' ? 'active':'' ?>">
    <div class="card">
      <div class="card-head">
        <h2><i class="fas fa-newspaper"></i> إدارة المقالات</h2>
        <button class="btn btn-primary btn-sm" onclick="openModal('postModal')">
          <i class="fas fa-plus"></i> مقال جديد
        </button>
      </div>
      <div class="card-body p0">
        <table class="tbl">
          <thead>
            <tr>
              <th>الصورة</th><th>العنوان</th><th>الفئة</th>
              <th>الكاتب</th><th>الحالة</th><th>المشاهدات</th><th>التاريخ</th><th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($posts): foreach ($posts as $p): ?>
            <tr>
              <td>
                <?php if ($p['thumbnail']): ?>
                  <img src="<?= e(img_url($p['thumbnail'])) ?>" class="blog-thumb-sm"
                       onerror="this.src='https://placehold.co/60x42?text=IMG'">
                <?php else: ?>
                  <span style="color:var(--muted);font-size:.8rem">—</span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= e($p['title']) ?></strong><br>
                <small style="color:var(--muted)"><?= e($p['slug']) ?></small>
              </td>
              <td><?= e($p['cat_name'] ?? '—') ?></td>
              <td><?= e($p['author']) ?></td>
              <td>
                <?php if ($p['status']==='published'): ?>
                  <span class="pub-badge"><i class="fas fa-circle" style="font-size:.5rem"></i> منشور</span>
                <?php else: ?>
                  <span class="draft-badge"><i class="fas fa-pencil-alt" style="font-size:.5rem"></i> مسودة</span>
                <?php endif; ?>
              </td>
              <td><?= number_format((int)$p['views']) ?></td>
              <td style="font-size:.82rem;color:var(--muted)"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
              <td>
                <div class="actions">
                  <a href="?page=blog&edit_post=<?= $p['id'] ?>" class="btn btn-warning btn-xs btn-icon" title="تعديل"><i class="fas fa-edit"></i></a>
                  <a href="<?= site_url('blog-post.php?slug='.urlencode($p['slug'])) ?>" target="_blank" class="btn btn-outline btn-xs btn-icon" title="معاينة"><i class="fas fa-eye"></i></a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="del_post">
                    <input type="hidden" name="id"     value="<?= $p['id'] ?>">
                    <button class="btn btn-danger btn-xs btn-icon" data-confirm="حذف المقال نهائياً؟"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr class="empty-row"><td colspan="8"><i class="fas fa-newspaper"></i> لا توجد مقالات بعد</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══════════════ CATEGORIES TAB ═══════════════ -->
  <div id="tab-categories" class="tab-pane <?= $activeTab==='categories' ? 'active':'' ?>">
    <div class="card">
      <div class="card-head">
        <h2><i class="fas fa-tags"></i> فئات المدونة</h2>
        <button class="btn btn-primary btn-sm" onclick="openModal('bcatModal')">
          <i class="fas fa-plus"></i> إضافة فئة
        </button>
      </div>
      <div class="card-body p0">
        <table class="tbl">
          <thead><tr><th>الاسم</th><th>الـ Slug</th><th>الترتيب</th><th>المقالات</th><th>الحالة</th><th>إجراءات</th></tr></thead>
          <tbody>
          <?php if ($bcats): foreach ($bcats as $c): ?>
            <tr>
              <td><strong><?= e($c['name']) ?></strong></td>
              <td><code style="font-size:.8rem"><?= e($c['slug']) ?></code></td>
              <td><?= (int)$c['sort_order'] ?></td>
              <td><?= db_count('blog_posts','category_id=?',[$c['id']]) ?></td>
              <td><span class="badge badge-<?= $c['status']==='active'?'active':'inactive' ?>"><?= $c['status']==='active'?'نشطة':'مخفية' ?></span></td>
              <td>
                <div class="actions">
                  <a href="?page=blog&tab=categories&edit_cat=<?= $c['id'] ?>" class="btn btn-warning btn-xs btn-icon"><i class="fas fa-edit"></i></a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="del_bcat">
                    <input type="hidden" name="id"     value="<?= $c['id'] ?>">
                    <button class="btn btn-danger btn-xs btn-icon" data-confirm="حذف الفئة؟"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr class="empty-row"><td colspan="6"><i class="fas fa-tags"></i> لا توجد فئات</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════ POST MODAL (Add / Edit) ═══════════════ -->
<div class="modal-bg <?= $editPost ? 'open':'' ?>" id="postModal">
  <div class="modal-dlg xl">
    <div class="modal-hd">
      <h3><?= $editPost ? 'تعديل المقال' : 'إضافة مقال جديد' ?></h3>
      <button class="modal-close" onclick="closeModal('postModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save_post">
      <input type="hidden" name="id"     value="<?= (int)($editPost['id'] ?? 0) ?>">
      <div class="modal-bd">
        <div class="form-grid">

          <!-- Title -->
          <div class="fg full">
            <label>عنوان المقال <span class="req">*</span></label>
            <input type="text" name="title" id="postTitle" value="<?= e($editPost['title'] ?? '') ?>"
                   placeholder="اكتب عنواناً جذاباً…" required oninput="autoSlug(this.value)">
          </div>

          <!-- Slug -->
          <div class="fg full">
            <label>الرابط الدائم (Slug)</label>
            <div class="slug-row">
              <input type="text" name="slug" id="postSlug" value="<?= e($editPost['slug'] ?? '') ?>"
                     placeholder="your-post-slug" dir="ltr">
              <button type="button" class="slug-gen" onclick="autoSlug(document.getElementById('postTitle').value, true)">
                <i class="fas fa-magic"></i> توليد تلقائي
              </button>
            </div>
            <small>يُستخدم في رابط المقال — أحرف إنجليزية وأرقام وشرطات فقط</small>
          </div>

          <!-- Category + Author -->
          <div class="fg">
            <label>الفئة</label>
            <select name="category_id">
              <option value="">— بدون فئة —</option>
              <?php foreach ($bcats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($editPost['category_id'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label>اسم الكاتب</label>
            <input type="text" name="author" value="<?= e($editPost['author'] ?? 'فريق ITX') ?>">
          </div>

          <!-- Status -->
          <div class="fg">
            <label>حالة النشر</label>
            <select name="status">
              <option value="published" <?= ($editPost['status'] ?? 'published')==='published'?'selected':'' ?>>منشور</option>
              <option value="draft"     <?= ($editPost['status'] ?? '')==='draft'    ?'selected':'' ?>>مسودة</option>
            </select>
          </div>

          <!-- Tags -->
          <div class="fg">
            <label>الوسوم (Tags)</label>
            <input type="text" name="tags" value="<?= e($editPost['tags'] ?? '') ?>"
                   placeholder="برمجة, تقنية, أمن (مفصولة بفاصلة)">
          </div>

          <!-- Thumbnail -->
          <div class="fg full">
            <label>صورة المقال البارزة</label>
            <?php $curThumb = $editPost['thumbnail'] ?? ''; ?>
            <div class="img-wrap">
              <div class="img-tabs">
                <button type="button" class="img-tab-btn active" data-tab="url">رابط URL</button>
                <button type="button" class="img-tab-btn" data-tab="file">رفع من الجهاز</button>
              </div>
              <div class="img-tab active" data-tab="url">
                <input type="text" name="thumb_url" class="img-url-inp" value="<?= e($curThumb) ?>"
                       placeholder="https://... أو مسار الملف">
              </div>
              <div class="img-tab" data-tab="file">
                <input type="file" name="thumb_file" class="img-file-inp" accept="image/*">
              </div>
              <?php if ($curThumb): ?>
              <div class="img-preview-wrap">
                <img src="<?= e(img_url($curThumb)) ?>" class="img-preview show" alt="">
                <span class="img-current-label">الصورة الحالية</span>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Excerpt -->
          <div class="fg full">
            <label>مقتطف / ملخص المقال</label>
            <textarea name="excerpt" rows="3" placeholder="نص مختصر يظهر في قوائم المقالات (2-3 جمل)"><?= e($editPost['excerpt'] ?? '') ?></textarea>
          </div>

          <!-- Content -->
          <div class="fg full">
            <label>محتوى المقال <span class="req">*</span></label>
            <textarea name="content" rows="18" style="font-family:monospace;font-size:.84rem"><?= htmlspecialchars($editPost['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <small>يمكنك استخدام HTML: &lt;h2&gt; &lt;h3&gt; &lt;p&gt; &lt;ul&gt; &lt;li&gt; &lt;strong&gt; &lt;a&gt; &lt;img&gt;</small>
          </div>

          <!-- SEO -->
          <div style="grid-column:1/-1"><div class="sep"></div><p style="font-size:.8rem;font-weight:700;color:var(--muted)"><i class="fas fa-search"></i> إعدادات محركات البحث (SEO) — اختياري</p></div>
          <div class="fg">
            <label>عنوان SEO (meta title)</label>
            <input type="text" name="meta_title" value="<?= e($editPost['meta_title'] ?? '') ?>"
                   placeholder="عنوان يظهر في نتائج البحث (50-60 حرف)">
          </div>
          <div class="fg">
            <label>وصف SEO (meta description)</label>
            <input type="text" name="meta_description" value="<?= e($editPost['meta_description'] ?? '') ?>"
                   placeholder="وصف موجز لمحركات البحث (120-160 حرف)">
          </div>

        </div>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn btn-outline" onclick="closeModal('postModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ المقال</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════════ CATEGORY MODAL ═══════════════ -->
<div class="modal-bg <?= $editCat ? 'open':'' ?>" id="bcatModal">
  <div class="modal-dlg">
    <div class="modal-hd">
      <h3><?= $editCat ? 'تعديل الفئة' : 'إضافة فئة' ?></h3>
      <button class="modal-close" onclick="closeModal('bcatModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_bcat">
      <input type="hidden" name="id"     value="<?= (int)($editCat['id'] ?? 0) ?>">
      <div class="modal-bd">
        <div class="form-grid">
          <div class="fg full">
            <label>اسم الفئة <span class="req">*</span></label>
            <input type="text" name="name" id="bcatName" value="<?= e($editCat['name'] ?? '') ?>"
                   placeholder="مثال: تقنية وبرمجة" required oninput="autoBcatSlug(this.value)">
          </div>
          <div class="fg full">
            <label>الـ Slug (رابط الفئة)</label>
            <input type="text" name="slug" id="bcatSlug" value="<?= e($editCat['slug'] ?? '') ?>"
                   placeholder="tech-category" dir="ltr">
          </div>
          <div class="fg">
            <label>الترتيب</label>
            <input type="number" name="sort_order" value="<?= (int)($editCat['sort_order'] ?? 0) ?>" min="0">
          </div>
          <div class="fg">
            <label>الحالة</label>
            <select name="status">
              <option value="active"   <?= ($editCat['status'] ?? 'active')==='active'  ?'selected':'' ?>>نشطة</option>
              <option value="inactive" <?= ($editCat['status'] ?? '')==='inactive'?'selected':'' ?>>مخفية</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn btn-outline" onclick="closeModal('bcatModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
      </div>
    </form>
  </div>
</div>

<script>
// Arabic → Latin slug generator
const AR = 'أإآابتثجحخدذرزسشصضطظعغفقكلمنهويةىئءؤ'.split('');
const EN = ['a','i','a','a','b','t','th','j','h','kh','d','th','r','z','s','sh','s','d','t','z','a','gh','f','q','k','l','m','n','h','w','y','h','a','y','a','w'];
function toSlug(txt) {
    let s = txt;
    AR.forEach((a,i)=>{ s = s.replaceAll(a, EN[i]||''); });
    s = s.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    return s || 'post-' + Date.now();
}
function autoSlug(title, force=false) {
    const sl = document.getElementById('postSlug');
    if (sl && (force || !sl.value)) sl.value = toSlug(title);
}
function autoBcatSlug(name) {
    const sl = document.getElementById('bcatSlug');
    if (sl && !sl.value) sl.value = toSlug(name);
}
<?php if ($editPost): ?>
// Pre-open the post modal if editing
document.addEventListener('DOMContentLoaded', () => openModal('postModal'));
<?php elseif ($editCat): ?>
document.addEventListener('DOMContentLoaded', () => openModal('bcatModal'));
<?php endif; ?>
</script>

<?php layout_end(); ?>
