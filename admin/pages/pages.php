<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug    = in_array($_POST['slug'] ?? '', ['privacy','terms']) ? $_POST['slug'] : 'privacy';
    $title   = trim($_POST['title']   ?? '');
    $content = $_POST['content']      ?? '';
    db_exec("INSERT INTO content_pages (slug,title,content) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content)",
            [$slug, $title, $content]);
    redirect_admin('pages', 'تم حفظ الصفحة بنجاح');
}

$privacy = db_row("SELECT * FROM content_pages WHERE slug='privacy'") ?: ['slug'=>'privacy','title'=>'سياسة الخصوصية','content'=>''];
$terms   = db_row("SELECT * FROM content_pages WHERE slug='terms'")   ?: ['slug'=>'terms',  'title'=>'شروط الاستخدام',  'content'=>''];

$activeTab = $_GET['tab'] ?? 'privacy';

layout_start('الصفحات (خصوصية / شروط)', 'pages');
?>
<div id="pagesTabGroup">
  <div class="pg-tabs">
    <button class="pg-tab <?= $activeTab === 'privacy' ? 'active' : '' ?>"
            data-tab="tab-privacy" onclick="switchTab('pagesTabGroup','tab-privacy')">
      <i class="fas fa-shield-alt"></i> سياسة الخصوصية
    </button>
    <button class="pg-tab <?= $activeTab === 'terms' ? 'active' : '' ?>"
            data-tab="tab-terms" onclick="switchTab('pagesTabGroup','tab-terms')">
      <i class="fas fa-file-contract"></i> شروط الاستخدام
    </button>
  </div>

  <!-- Privacy -->
  <div id="tab-privacy" class="tab-pane <?= $activeTab === 'privacy' ? 'active' : '' ?>">
    <div class="card">
      <div class="card-head">
        <h2><i class="fas fa-shield-alt"></i> سياسة الخصوصية</h2>
        <a href="<?= site_url('page.php?slug=privacy') ?>" target="_blank" class="site-preview-link">
          <i class="fas fa-eye"></i> معاينة
        </a>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="slug" value="privacy">
          <div class="form-grid col1">
            <div class="fg">
              <label>عنوان الصفحة</label>
              <input type="text" name="title" value="<?= e($privacy['title']) ?>">
            </div>
            <div class="fg">
              <label>المحتوى (HTML مسموح)</label>
              <textarea name="content" rows="18" class="rich-editor" style="font-family:monospace;font-size:.85rem"><?= htmlspecialchars($privacy['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
              <small>يمكنك استخدام وسوم HTML مثل &lt;h2&gt; &lt;h3&gt; &lt;p&gt; &lt;ul&gt; &lt;li&gt; &lt;strong&gt;</small>
            </div>
          </div>
          <div style="text-align:left;margin-top:1rem">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Terms -->
  <div id="tab-terms" class="tab-pane <?= $activeTab === 'terms' ? 'active' : '' ?>">
    <div class="card">
      <div class="card-head">
        <h2><i class="fas fa-file-contract"></i> شروط الاستخدام</h2>
        <a href="<?= site_url('page.php?slug=terms') ?>" target="_blank" class="site-preview-link">
          <i class="fas fa-eye"></i> معاينة
        </a>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="slug" value="terms">
          <div class="form-grid col1">
            <div class="fg">
              <label>عنوان الصفحة</label>
              <input type="text" name="title" value="<?= e($terms['title']) ?>">
            </div>
            <div class="fg">
              <label>المحتوى (HTML مسموح)</label>
              <textarea name="content" rows="18" class="rich-editor" style="font-family:monospace;font-size:.85rem"><?= htmlspecialchars($terms['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
          </div>
          <div style="text-align:left;margin-top:1rem">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php layout_end(); ?>
