<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['site_name','site_tagline','site_description','site_keywords',
             'whatsapp_number','whatsapp_msg','footer_text'];
    foreach ($keys as $k) save_setting($k, trim($_POST[$k] ?? ''));

    // Logo
    $existing = get_setting('site_logo','logo.jpeg');
    $logo = handle_image_input('logo_file','logo_url', $existing, 'uploads');
    save_setting('site_logo', $logo);

    redirect_admin('settings', 'تم حفظ الإعدادات بنجاح');
}

layout_start('الإعدادات العامة','settings');
?>
<form method="POST" enctype="multipart/form-data">
<div class="card">
  <div class="card-head"><h2><i class="fas fa-building"></i> هوية الموقع</h2></div>
  <div class="card-body">
    <div class="form-grid">
      <div class="fg">
        <label>اسم الشركة / الموقع <span class="req">*</span></label>
        <input type="text" name="site_name" value="<?= e(get_setting('site_name','ITX')) ?>" required>
      </div>
      <div class="fg">
        <label>الشعار الفرعي (Tagline)</label>
        <input type="text" name="site_tagline" value="<?= e(get_setting('site_tagline')) ?>" placeholder="حلول رقمية">
      </div>
      <div class="fg full">
        <label>وصف الموقع (meta description)</label>
        <textarea name="site_description"><?= e(get_setting('site_description')) ?></textarea>
      </div>
      <div class="fg full">
        <label>الكلمات المفتاحية (meta keywords)</label>
        <input type="text" name="site_keywords" value="<?= e(get_setting('site_keywords')) ?>"
               placeholder="كلمة1, كلمة2, ...">
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2><i class="fas fa-image"></i> الشعار (Logo)</h2></div>
  <div class="card-body">
    <?php $currentLogo = get_setting('site_logo','logo.jpeg'); ?>
    <div class="img-wrap">
      <div class="img-tabs">
        <button type="button" class="img-tab-btn active" data-tab="url">رابط URL</button>
        <button type="button" class="img-tab-btn" data-tab="file">رفع من الجهاز</button>
      </div>
      <div class="img-tab active" data-tab="url">
        <input type="text" name="logo_url" class="img-url-inp"
               value="<?= e($currentLogo) ?>" placeholder="https://... أو اسم الملف">
      </div>
      <div class="img-tab" data-tab="file">
        <input type="file" name="logo_file" class="img-file-inp" accept="image/*">
      </div>
      <div class="img-preview-wrap">
        <img src="<?= e(img_url($currentLogo)) ?>" class="img-preview show" alt="logo" style="max-height:70px">
        <span class="img-current-label">الشعار الحالي</span>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2><i class="fab fa-whatsapp"></i> واتساب</h2></div>
  <div class="card-body">
    <div class="form-grid">
      <div class="fg">
        <label>رقم واتساب (بدون + مع رمز الدولة)</label>
        <input type="text" name="whatsapp_number" value="<?= e(get_setting('whatsapp_number')) ?>" placeholder="966501234567">
      </div>
      <div class="fg">
        <label>رسالة الترحيب الافتراضية</label>
        <input type="text" name="whatsapp_msg" value="<?= e(get_setting('whatsapp_msg','مرحباً')) ?>">
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2><i class="fas fa-shoe-prints"></i> الفوتر</h2></div>
  <div class="card-body">
    <div class="fg">
      <label>نص حقوق النشر في الفوتر</label>
      <input type="text" name="footer_text" value="<?= e(get_setting('footer_text')) ?>"
             placeholder="جميع الحقوق محفوظة | شركة ITX">
    </div>
  </div>
</div>

<div style="text-align:left">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الإعدادات</button>
</div>
</form>
<?php layout_end(); ?>
