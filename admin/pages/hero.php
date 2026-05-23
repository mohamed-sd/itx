<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO hero_section (id,title,subtitle,note,btn1_text,btn1_link,btn2_text,btn2_link)
        VALUES (1,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),subtitle=VALUES(subtitle),note=VALUES(note),
        btn1_text=VALUES(btn1_text),btn1_link=VALUES(btn1_link),
        btn2_text=VALUES(btn2_text),btn2_link=VALUES(btn2_link)");
    $stmt->execute([
        trim($_POST['title']    ?? ''),
        trim($_POST['subtitle'] ?? ''),
        trim($_POST['note']     ?? ''),
        trim($_POST['btn1_text']?? ''),
        trim($_POST['btn1_link']?? ''),
        trim($_POST['btn2_text']?? ''),
        trim($_POST['btn2_link']?? ''),
    ]);
    redirect_admin('hero', 'تم حفظ قسم الرئيسية بنجاح');
}

$h = db_row("SELECT * FROM hero_section WHERE id=1") ?: [];

layout_start('الصفحة الرئيسية (Hero)', 'hero');
?>
<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-rocket"></i> محتوى قسم الرئيسية</h2>
    <a href="<?= site_url() ?>#home" target="_blank" class="site-preview-link">
      <i class="fas fa-eye"></i> معاينة
    </a>
  </div>
  <div class="card-body">
    <form method="POST">
      <div class="form-grid col1">
        <div class="fg">
          <label>العنوان الرئيسي <span class="req">*</span></label>
          <input type="text" name="title" value="<?= e($h['title'] ?? 'شركة ITX للحلول الرقمية') ?>"
                 data-maxlen="80" required>
        </div>
        <div class="fg">
          <label>العنوان الفرعي</label>
          <textarea name="subtitle" rows="2"><?= e($h['subtitle'] ?? '') ?></textarea>
        </div>
        <div class="fg">
          <label>ملاحظة إضافية (نص صغير تحت العنوان الفرعي)</label>
          <input type="text" name="note" value="<?= e($h['note'] ?? '') ?>" data-maxlen="120">
        </div>
      </div>

      <div class="sep"></div>
      <p style="font-weight:700;color:var(--primary);margin-bottom:1rem"><i class="fas fa-mouse-pointer"></i> أزرار الدعوة للتصرف</p>
      <div class="form-grid">
        <div class="fg">
          <label>نص الزر الأول</label>
          <input type="text" name="btn1_text" value="<?= e($h['btn1_text'] ?? 'عرض أعمالنا') ?>">
        </div>
        <div class="fg">
          <label>رابط الزر الأول</label>
          <input type="text" name="btn1_link" value="<?= e($h['btn1_link'] ?? '#our-works') ?>"
                 placeholder="#our-works أو رابط خارجي">
        </div>
        <div class="fg">
          <label>نص الزر الثاني</label>
          <input type="text" name="btn2_text" value="<?= e($h['btn2_text'] ?? 'تواصل معنا') ?>">
        </div>
        <div class="fg">
          <label>رابط الزر الثاني</label>
          <input type="text" name="btn2_link" value="<?= e($h['btn2_link'] ?? '#contact') ?>"
                 placeholder="#contact أو رابط خارجي">
        </div>
      </div>

      <div style="text-align:left;margin-top:1.5rem">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
      </div>
    </form>
  </div>
</div>
<?php layout_end(); ?>
