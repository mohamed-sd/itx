<?php
/* ITX CRM — shared page chrome (sidebar + topbar). */

function crm_nav_items(): array {
    return [
        ['p' => 'dashboard', 'i' => 'fas fa-chart-pie',  'l' => 'لوحة المعلومات'],
        ['p' => 'customers', 'i' => 'fas fa-users',       'l' => 'العملاء'],
        ['p' => 'followups', 'i' => 'fas fa-bell',        'l' => 'المتابعات'],
        ['p' => 'reports',   'i' => 'fas fa-chart-column','l' => 'التقارير'],
    ];
}

function crm_layout_start(string $title, string $current, string $icon = 'fas fa-circle'): void {
    $GLOBALS['crm_current'] = $current;
    $emp     = current_emp();
    $logo    = '';
    try { $logo = get_setting('site_logo', 'logo.jpeg'); } catch (\Throwable $ex) {}
    if (!$logo) {
        try { $r = db_row("SELECT setting_value v FROM site_settings WHERE setting_key='site_logo'"); $logo = $r['v'] ?? 'logo.jpeg'; }
        catch (\Throwable $ex) { $logo = 'logo.jpeg'; }
    }
    $logoUrl = preg_match('#^https?://#', $logo) ? $logo : (crm_prefix() ? dirname(crm_prefix()) . '/' . ltrim($logo, '/') : '/' . ltrim($logo, '/'));
    $siteRoot = dirname(crm_prefix()) ?: '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> — CRM | ITX</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= crm_asset('crm.css') ?>">
</head>
<body>
<div class="crm-wrap">
  <div class="crm-overlay" id="crmOverlay" onclick="crmDrawer(false)"></div>
  <aside class="crm-sb" id="crmSb">
    <div class="crm-sb-logo">
      <img src="<?= e($logoUrl) ?>" alt="" onerror="this.style.display='none'">
      <div><b>ITX CRM</b><span>إدارة العملاء</span></div>
    </div>
    <nav class="crm-nav">
      <?php foreach (crm_nav_items() as $it): ?>
        <a href="<?= crm_url($it['p']) ?>" class="<?= $current === $it['p'] ? 'active' : '' ?>">
          <i class="<?= e($it['i']) ?>"></i> <?= e($it['l']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="crm-sb-foot">
      <a href="<?= crm_prefix() ?>/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
    </div>
  </aside>

  <div class="crm-main">
    <div class="crm-top">
      <div class="flex-wrap">
        <button class="crm-toggle" onclick="crmDrawer(true)" aria-label="القائمة"><i class="fas fa-bars"></i></button>
        <h1><i class="<?= e($icon) ?>"></i> <?= e($title) ?></h1>
      </div>
      <div class="who">
        <span><i class="fas fa-user-circle"></i> <?= e($emp['name']) ?></span>
        <a class="logout" href="<?= crm_prefix() ?>/logout.php" title="تسجيل الخروج"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </div>
    <div class="crm-content">
      <?= crm_flash() ?>
<?php }

function crm_layout_end(): void {
    $current = $GLOBALS['crm_current'] ?? 'dashboard'; ?>
    </div>
  </div>

  <!-- Bottom nav (phones) -->
  <nav class="crm-bottom">
    <div class="crm-bottom-inner">
      <?php foreach (crm_nav_items() as $it): ?>
        <a href="<?= crm_url($it['p']) ?>" class="<?= ($current === $it['p'] || ($current === 'customer' && $it['p'] === 'customers')) ? 'active' : '' ?>">
          <i class="<?= e($it['i']) ?>"></i><span><?= e($it['l']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </nav>
</div>
<script>
function crmDrawer(open){
  document.getElementById('crmSb').classList.toggle('open', open);
  document.getElementById('crmOverlay').classList.toggle('show', open);
  document.body.style.overflow = open ? 'hidden' : '';
}
</script>
</body>
</html>
<?php }
