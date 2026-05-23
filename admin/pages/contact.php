<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $s = $db->prepare("INSERT INTO contact_info (id,phone,email,address,whatsapp,map_embed) VALUES (1,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE phone=VALUES(phone),email=VALUES(email),address=VALUES(address),
        whatsapp=VALUES(whatsapp),map_embed=VALUES(map_embed)");
    $s->execute([
        trim($_POST['phone']     ?? ''),
        trim($_POST['email']     ?? ''),
        trim($_POST['address']   ?? ''),
        trim($_POST['whatsapp']  ?? ''),
        trim($_POST['map_embed'] ?? ''),
    ]);
    redirect_admin('contact', 'تم حفظ معلومات التواصل');
}

$c = db_row("SELECT * FROM contact_info WHERE id=1") ?: [];

layout_start('التواصل', 'contact');
?>
<form method="POST">
<div class="card">
  <div class="card-head"><h2><i class="fas fa-phone-alt"></i> معلومات التواصل</h2></div>
  <div class="card-body">
    <div class="form-grid">
      <div class="fg">
        <label><i class="fas fa-phone"></i> رقم الهاتف</label>
        <input type="text" name="phone" value="<?= e($c['phone'] ?? '') ?>" placeholder="+966 50 123 4567">
      </div>
      <div class="fg">
        <label><i class="fas fa-envelope"></i> البريد الإلكتروني</label>
        <input type="email" name="email" value="<?= e($c['email'] ?? '') ?>">
      </div>
      <div class="fg">
        <label><i class="fab fa-whatsapp"></i> رقم واتساب (بدون + مع رمز الدولة)</label>
        <input type="text" name="whatsapp" value="<?= e($c['whatsapp'] ?? '') ?>" placeholder="966501234567">
      </div>
      <div class="fg">
        <label><i class="fas fa-map-marker-alt"></i> العنوان</label>
        <input type="text" name="address" value="<?= e($c['address'] ?? '') ?>" placeholder="الرياض، المملكة العربية السعودية">
      </div>
      <div class="fg full">
        <label><i class="fas fa-map"></i> كود تضمين الخريطة (iframe) — اختياري</label>
        <textarea name="map_embed" rows="4" placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'><?= e($c['map_embed'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
</div>
<div style="text-align:left">
  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
</div>
</form>
<?php layout_end(); ?>
