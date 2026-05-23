<?php
$counts = [
  'خدمة'      => db_count('services'),
  'مشروع'     => db_count('projects'),
  'تقييم'     => db_count('testimonials'),
  'فئة أعمال' => db_count('categories'),
];
$icons  = ['fas fa-tools','fas fa-briefcase','fas fa-star','fas fa-th-large'];
$colors = ['#3F4D60','#2FA8B9','#f59e0b','#10b981'];

layout_start('لوحة التحكم', 'dashboard');
?>
<div class="dash-stats">
<?php $i=0; foreach ($counts as $label => $val): ?>
  <div class="dash-card">
    <div class="dc-icon" style="background:<?= $colors[$i] ?>">
      <i class="<?= $icons[$i] ?>"></i>
    </div>
    <div class="dc-val"><?= $val ?></div>
    <div class="dc-lbl"><?= e($label) ?></div>
  </div>
<?php $i++; endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

  <div class="card">
    <div class="card-head"><h2><i class="fas fa-clock"></i> آخر المشاريع</h2>
      <a href="<?= admin_url('works') ?>" class="btn btn-outline btn-sm">عرض الكل</a>
    </div>
    <div class="card-body p0">
      <table class="tbl">
        <thead><tr><th>المشروع</th><th>الفئة</th><th>السنة</th></tr></thead>
        <tbody>
        <?php $projects = db_all("SELECT p.title, c.name AS cat, p.project_year FROM projects p JOIN categories c ON c.id=p.category_id WHERE p.status='active' ORDER BY p.id DESC LIMIT 6");
        if ($projects): foreach ($projects as $r): ?>
          <tr><td><?= e($r['title']) ?></td><td><?= e($r['cat']) ?></td><td><?= e($r['project_year']) ?></td></tr>
        <?php endforeach; else: ?>
          <tr class="empty-row"><td colspan="3"><i class="fas fa-inbox"></i> لا توجد مشاريع</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h2><i class="fas fa-star"></i> آخر التقييمات</h2>
      <a href="<?= admin_url('testimonials') ?>" class="btn btn-outline btn-sm">عرض الكل</a>
    </div>
    <div class="card-body p0">
      <table class="tbl">
        <thead><tr><th>العميل</th><th>التقييم</th><th>الحالة</th></tr></thead>
        <tbody>
        <?php $testi = db_all("SELECT author_name, rating, status FROM testimonials ORDER BY id DESC LIMIT 6");
        if ($testi): foreach ($testi as $r): ?>
          <tr>
            <td><?= e($r['author_name']) ?></td>
            <td><?= str_repeat('★', (int)$r['rating']) ?></td>
            <td><span class="badge badge-<?= $r['status'] === 'active' ? 'active' : 'inactive' ?>"><?= $r['status'] === 'active' ? 'نشط' : 'مخفي' ?></span></td>
          </tr>
        <?php endforeach; else: ?>
          <tr class="empty-row"><td colspan="3"><i class="fas fa-inbox"></i> لا توجد تقييمات</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<div class="card mt-2">
  <div class="card-head"><h2><i class="fas fa-link"></i> روابط سريعة</h2></div>
  <div class="card-body">
    <div class="flex-row">
      <?php
      $quick = ['settings'=>'الإعدادات','hero'=>'الرئيسية','about'=>'عن الشركة',
                'services'=>'الخدمات','works'=>'أعمالنا','testimonials'=>'التقييمات'];
      foreach ($quick as $p => $l): ?>
        <a href="<?= admin_url($p) ?>" class="btn btn-outline btn-sm">
          <i class="fas fa-edit"></i> <?= e($l) ?>
        </a>
      <?php endforeach; ?>
      <a href="<?= site_url() ?>" target="_blank" class="btn btn-outline btn-sm">
        <i class="fas fa-external-link-alt"></i> الموقع
      </a>
    </div>
  </div>
</div>
<?php layout_end(); ?>
