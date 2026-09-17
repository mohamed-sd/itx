<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    // ── Background video: upload (priority) / URL / remove / keep. Old file is deleted. ──
    $existingVideo = db_row("SELECT bg_video FROM hero_section WHERE id=1")['bg_video'] ?? '';
    $bg_video = $existingVideo;
    try {
        if (!empty($_POST['remove_video'])) {
            delete_old_image($existingVideo);          // only deletes local files inside /uploads/
            $bg_video = '';
        } elseif (!empty($_FILES['bg_video_file']['name']) && $_FILES['bg_video_file']['error'] === UPLOAD_ERR_OK) {
            $new = upload_video($_FILES['bg_video_file']);
            if ($new !== $existingVideo) delete_old_image($existingVideo);
            $bg_video = $new;
        } else {
            $url = trim($_POST['bg_video_url'] ?? '');
            if ($url !== '' && $url !== $existingVideo) {
                delete_old_image($existingVideo);       // switching to a URL → drop old uploaded file
                $bg_video = $url;
            }
        }
    } catch (\Throwable $ex) {
        redirect_admin('hero', 'خطأ في الفيديو: ' . $ex->getMessage(), 'danger');
    }

    $stmt = $db->prepare("INSERT INTO hero_section
        (id,title,subtitle,note,bg_video,btn1_text,btn1_link,btn2_text,btn2_link,
         title_en,subtitle_en,note_en,btn1_text_en,btn2_text_en)
        VALUES (1,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),subtitle=VALUES(subtitle),note=VALUES(note),
        bg_video=VALUES(bg_video),
        btn1_text=VALUES(btn1_text),btn1_link=VALUES(btn1_link),
        btn2_text=VALUES(btn2_text),btn2_link=VALUES(btn2_link),
        title_en=VALUES(title_en),subtitle_en=VALUES(subtitle_en),note_en=VALUES(note_en),
        btn1_text_en=VALUES(btn1_text_en),btn2_text_en=VALUES(btn2_text_en)");
    $stmt->execute([
        trim($_POST['title']    ?? ''),
        trim($_POST['subtitle'] ?? ''),
        trim($_POST['note']     ?? ''),
        $bg_video,
        trim($_POST['btn1_text']?? ''),
        trim($_POST['btn1_link']?? ''),
        trim($_POST['btn2_text']?? ''),
        trim($_POST['btn2_link']?? ''),
        trim($_POST['title_en']    ?? ''),
        trim($_POST['subtitle_en'] ?? ''),
        trim($_POST['note_en']     ?? ''),
        trim($_POST['btn1_text_en']?? ''),
        trim($_POST['btn2_text_en']?? ''),
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
    <form method="POST" enctype="multipart/form-data">
      <div class="form-grid col1">
        <div class="fg">
          <label>العنوان الرئيسي <span class="req">*</span></label>
          <input type="text" name="title" value="<?= e($h['title'] ?? 'شركة ITX للحلول الرقمية') ?>"
                 data-maxlen="80" required>
        </div>
        <div class="fg">
          <label><i class="fas fa-language"></i> العنوان الرئيسي — English</label>
          <input type="text" name="title_en" value="<?= e($h['title_en'] ?? '') ?>" placeholder="ITX Digital Solutions" dir="ltr">
        </div>
        <div class="fg">
          <label>العنوان الفرعي</label>
          <textarea name="subtitle" rows="2"><?= e($h['subtitle'] ?? '') ?></textarea>
        </div>
        <div class="fg">
          <label><i class="fas fa-language"></i> العنوان الفرعي — English</label>
          <textarea name="subtitle_en" rows="2" dir="ltr"><?= e($h['subtitle_en'] ?? '') ?></textarea>
        </div>
        <div class="fg">
          <label>ملاحظة إضافية (نص صغير تحت العنوان الفرعي)</label>
          <input type="text" name="note" value="<?= e($h['note'] ?? '') ?>" data-maxlen="120">
        </div>
        <div class="fg">
          <label><i class="fas fa-language"></i> ملاحظة إضافية — English</label>
          <input type="text" name="note_en" value="<?= e($h['note_en'] ?? '') ?>" dir="ltr">
        </div>
      </div>

      <div class="sep"></div>
      <p style="font-weight:700;color:var(--primary);margin-bottom:1rem"><i class="fas fa-film"></i> فيديو خلفية الهيدر (Hero)</p>
      <?php $curVid = $h['bg_video'] ?? ''; $isUrlVid = $curVid && preg_match('#^https?://#', $curVid); ?>
      <?php if ($curVid): ?>
        <div style="margin-bottom:1rem;padding:.75rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
          <i class="fas fa-circle-play" style="color:var(--accent);font-size:1.1rem"></i>
          <span style="font-size:.85rem">الفيديو الحالي: <code style="direction:ltr;display:inline-block"><?= e($curVid) ?></code></span>
          <a href="<?= e($isUrlVid ? $curVid : img_url($curVid)) ?>" target="_blank" class="btn btn-outline btn-xs"><i class="fas fa-eye"></i> عرض</a>
          <label style="font-size:.82rem;color:var(--danger);display:inline-flex;align-items:center;gap:.35rem;margin-inline-start:auto;cursor:pointer">
            <input type="checkbox" name="remove_video" value="1"> إزالة الفيديو والعودة للخلفية الافتراضية
          </label>
        </div>
      <?php endif; ?>
      <div class="form-grid">
        <div class="fg">
          <label><i class="fas fa-upload"></i> رفع فيديو جديد (mp4 / webm — حتى 60MB)</label>
          <input type="file" name="bg_video_file" accept="video/mp4,video/webm">
          <small style="color:var(--warning)"><i class="fas fa-triangle-exclamation"></i> عند رفع فيديو جديد يُحذف الفيديو القديم تلقائياً للحفاظ على المساحة.</small>
        </div>
        <div class="fg">
          <label><i class="fas fa-link"></i> أو رابط فيديو مباشر (mp4)</label>
          <input type="text" name="bg_video_url" dir="ltr" placeholder="https://example.com/video.mp4"
                 value="<?= e($isUrlVid ? $curVid : '') ?>">
          <small>إن رفعت ملفاً فله الأولوية على الرابط.</small>
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
          <label><i class="fas fa-language"></i> نص الزر الأول — English</label>
          <input type="text" name="btn1_text_en" value="<?= e($h['btn1_text_en'] ?? '') ?>" placeholder="View our work" dir="ltr">
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
          <label><i class="fas fa-language"></i> نص الزر الثاني — English</label>
          <input type="text" name="btn2_text_en" value="<?= e($h['btn2_text_en'] ?? '') ?>" placeholder="Get in touch" dir="ltr">
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
