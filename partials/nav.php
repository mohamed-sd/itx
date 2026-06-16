<?php
/* Shared navbar (new ITX design). Requires $logo_url, $site_name, $nav_prefix,
   $is_home. Optional: $active_nav ('blog' to highlight the blog link). */
$active_nav = $active_nav ?? '';
?>
<header class="nav" id="nav">
  <div class="container nav-inner">
    <a href="<?= $is_home ? '#home' : 'index.php' ?>" class="brand" aria-label="<?= e($site_name) ?>">
      <img src="<?= e($logo_url) ?>" alt="<?= e($site_name) ?>" loading="eager" decoding="async" fetchpriority="high">
    </a>
    <nav class="nav-links" id="navLinks">
      <a href="<?= $nav_prefix ?>#about"        data-ar="من نحن"        data-en="About">من نحن</a>
      <a href="<?= $nav_prefix ?>#services"     data-ar="خدماتنا"       data-en="Services">خدماتنا</a>
      <a href="<?= $nav_prefix ?>#our-works"    data-ar="أعمالنا"       data-en="Work">أعمالنا</a>
      <a href="<?= $nav_prefix ?>#testimonials" data-ar="آراء العملاء"  data-en="Clients">آراء العملاء</a>
      <a href="blog.php" class="<?= $active_nav === 'blog' ? 'active' : '' ?>" data-ar="المدونة" data-en="Blog">المدونة</a>
      <a href="<?= $nav_prefix ?>#contact"      data-ar="تواصل معنا"    data-en="Contact">تواصل معنا</a>
    </nav>
    <div class="nav-actions">
      <?php if (!empty($enable_english)): ?>
      <button class="lang-toggle" id="langToggle" aria-label="Switch language">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
        <span class="lang-label">EN</span>
      </button>
      <?php else: ?>
      <script>try{localStorage.setItem('itx_lang','ar')}catch(e){}</script>
      <?php endif; ?>
      <a href="<?= $nav_prefix ?>#contact" class="btn btn-primary nav-cta" data-ar="ابدأ مشروعك" data-en="Start a project">ابدأ مشروعك</a>
      <button class="burger" id="burger" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>
