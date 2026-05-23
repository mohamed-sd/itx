<?php
$_NAV = [
  ['p'=>'dashboard',  'i'=>'fas fa-tachometer-alt', 'l'=>'لوحة التحكم',        's'=>null],
  ['p'=>null,         'i'=>null,                     'l'=>'المحتوى',            's'=>'section'],
  ['p'=>'settings',   'i'=>'fas fa-cog',             'l'=>'الإعدادات العامة',   's'=>null],
  ['p'=>'hero',       'i'=>'fas fa-rocket',           'l'=>'الصفحة الرئيسية',   's'=>null],
  ['p'=>'about',      'i'=>'fas fa-info-circle',      'l'=>'عن الشركة',          's'=>null],
  ['p'=>'services',   'i'=>'fas fa-tools',            'l'=>'الخدمات',            's'=>null],
  ['p'=>'statistics', 'i'=>'fas fa-chart-bar',        'l'=>'الإحصائيات',         's'=>null],
  ['p'=>'works',      'i'=>'fas fa-briefcase',        'l'=>'أعمالنا',            's'=>null],
  ['p'=>'testimonials','i'=>'fas fa-comments',        'l'=>'آراء العملاء',       's'=>null],
  ['p'=>'contact',    'i'=>'fas fa-phone-alt',        'l'=>'التواصل',            's'=>null],
  ['p'=>'social',     'i'=>'fas fa-share-alt',        'l'=>'السوشيال ميديا',    's'=>null],
  ['p'=>'pages',      'i'=>'fas fa-file-alt',         'l'=>'الصفحات (خصوصية/شروط)', 's'=>null],
];

function layout_start(string $title, string $currentPage): void {
    global $_NAV;
    $logo    = get_setting('site_logo', 'logo.jpeg');
    $siteName= get_setting('site_name', 'ITX');
    $adminName = $_SESSION['admin_name'] ?? 'مدير';
    $logoUrl = img_url($logo);
    $siteHref = site_url();
    $adminHref = admin_prefix();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> — لوحة تحكم ITX</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= $adminHref ?>/assets/admin.css">
</head>
<body>
<div class="admin-wrap">

  <!-- Sidebar -->
  <aside class="sidebar">
    <a class="sb-logo" href="<?= admin_url('dashboard') ?>">
      <?php if ($logoUrl): ?>
        <img src="<?= e($logoUrl) ?>" alt="logo" onerror="this.style.display='none'">
      <?php endif; ?>
      <div>
        <div class="sb-logo-text"><?= e($siteName) ?></div>
        <div class="sb-logo-sub">لوحة الإدارة</div>
      </div>
    </a>
    <nav class="sb-nav">
<?php foreach ($_NAV as $item):
      if ($item['s'] === 'section'): ?>
      <div class="sb-section-label"><?= e($item['l']) ?></div>
<?php   continue; endif; ?>
      <div class="sb-item">
        <a href="<?= admin_url($item['p']) ?>" class="<?= $currentPage === $item['p'] ? 'active' : '' ?>">
          <i class="<?= e($item['i']) ?>"></i>
          <?= e($item['l']) ?>
        </a>
      </div>
<?php endforeach; ?>
    </nav>
    <div class="sb-footer">
      <a href="<?= $siteHref ?>" target="_blank">
        <i class="fas fa-external-link-alt"></i> عرض الموقع
      </a>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:.75rem">
        <button class="mobile-toggle"><i class="fas fa-bars"></i></button>
        <h1><i class="<?= e($_NAV[array_search($currentPage, array_column($_NAV,'p'))] ['i'] ?? 'fas fa-circle') ?>"></i> <?= e($title) ?></h1>
      </div>
      <div class="admin-meta">
        <span><i class="fas fa-user-circle"></i> <?= e($adminName) ?></span>
        <a href="<?= $adminHref ?>/logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
      </div>
    </div>
    <div class="content">
<?php echo get_flash(); ?>
<?php } // end layout_start

function layout_end(): void {
    $adminHref = admin_prefix();
?>
    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /admin-wrap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
<script src="<?= $adminHref ?>/assets/admin.js"></script>
</body>
</html>
<?php } // end layout_end
