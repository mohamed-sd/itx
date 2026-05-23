<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $existing = db_row("SELECT image FROM about_section WHERE id=1")['image'] ?? '';
    $image    = handle_image_input('img_file', 'img_url', $existing, 'uploads');

    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO about_section (id,heading,content,image,skills)
        VALUES (1,?,?,?,?)
        ON DUPLICATE KEY UPDATE heading=VALUES(heading),content=VALUES(content),
        image=VALUES(image),skills=VALUES(skills)");
    $stmt->execute([
        trim($_POST['heading'] ?? ''),
        trim($_POST['content'] ?? ''),
        $image,
        trim($_POST['skills']  ?? ''),
    ]);
    redirect_admin('about', 'تم حفظ قسم "عن الشركة" بنجاح');
}

$a = db_row("SELECT * FROM about_section WHERE id=1") ?: [];

layout_start('عن الشركة', 'about');
?>
<form method="POST" enctype="multipart/form-data">
<div class="card">
  <div class="card-head"><h2><i class="fas fa-info-circle"></i> محتوى قسم "عن الشركة"</h2></div>
  <div class="card-body">
    <div class="form-grid col1">
      <div class="fg">
        <label>العنوان</label>
        <input type="text" name="heading" value="<?= e($a['heading'] ?? 'مرحباً بك في ITX') ?>">
      </div>
      <div class="fg">
        <label>النص (كل سطر = فقرة)</label>
        <textarea name="content" rows="8"><?= e($a['content'] ?? '') ?></textarea>
      </div>
      <div class="fg">
        <label>المهارات والتخصصات (مفصولة بفاصلة)</label>
        <input type="text" name="skills" value="<?= e($a['skills'] ?? '') ?>"
               placeholder="تطوير المواقع,تطبيقات الجوال,...">
        <small>مثال: تطوير المواقع,تطبيقات الجوال,كاميرات مراقبة</small>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2><i class="fas fa-image"></i> صورة قسم "عن الشركة"</h2></div>
  <div class="card-body">
    <?php $currentImg = $a['image'] ?? ''; ?>
    <div class="img-wrap">
      <div class="img-tabs">
        <button type="button" class="img-tab-btn active" data-tab="url">رابط URL</button>
        <button type="button" class="img-tab-btn" data-tab="file">رفع من الجهاز</button>
      </div>
      <div class="img-tab active" data-tab="url">
        <input type="text" name="img_url" class="img-url-inp"
               value="<?= e($currentImg) ?>" placeholder="https://...">
      </div>
      <div class="img-tab" data-tab="file">
        <input type="file" name="img_file" class="img-file-inp" accept="image/*">
      </div>
      <div class="img-preview-wrap">
        <?php if ($currentImg): ?>
          <img src="<?= e(img_url($currentImg)) ?>" class="img-preview show" style="max-height:100px">
        <?php else: ?>
          <img class="img-preview" style="max-height:100px">
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div style="text-align:left">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
</div>
</form>
<?php layout_end(); ?>
