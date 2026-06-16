<?php
// ── Bootstrap (DB + helpers + shared brand/contact/social vars) ──
$is_home = true;
require_once __DIR__ . '/partials/bootstrap.php';

// ── Page-specific sections ────────────────────────────────
$hero = $about = [];
$services = $statistics = $testimonials = [];
$blog_preview = [];
if ($db_ok) {
    try { $hero         = $pdo->query("SELECT * FROM hero_section WHERE id=1")->fetch() ?: [];                       } catch (\Throwable $ex) {}
    try { $about        = $pdo->query("SELECT * FROM about_section WHERE id=1")->fetch() ?: [];                      } catch (\Throwable $ex) {}
    try { $services     = $pdo->query("SELECT * FROM services WHERE status='active' ORDER BY sort_order")->fetchAll();     } catch (\Throwable $ex) {}
    try { $statistics   = $pdo->query("SELECT * FROM statistics WHERE status='active' ORDER BY sort_order")->fetchAll();   } catch (\Throwable $ex) {}
    try { $testimonials = $pdo->query("SELECT * FROM testimonials WHERE status='active' ORDER BY sort_order")->fetchAll(); } catch (\Throwable $ex) {}
    try { $blog_preview = $pdo->query("SELECT p.*, c.name AS cat_name, c.name_en AS cat_name_en, c.slug AS cat_slug
                                        FROM blog_posts p
                                        LEFT JOIN blog_categories c ON c.id=p.category_id
                                        WHERE p.status='published'
                                        ORDER BY p.created_at DESC LIMIT 6")->fetchAll(); } catch (\Throwable $ex) {}
}

// ── Settings used only here ───────────────────────────────
$site_desc = $gs('site_description', 'شركة ' . $site_name . ' للحلول الرقمية - متخصصون في تطوير المواقع والتطبيقات وتركيب أنظمة كاميرات الأمان');
$site_kws  = $gs('site_keywords',    'تطوير المواقع, تطبيقات الجوال, كاميرات المراقبة, أنظمة الأمان');

// ── Hero ──────────────────────────────────────────────────
$h_title = $hero['title']     ?? ('شركة ' . $site_name . ' للحلول الرقمية');
$h_sub   = $hero['subtitle']  ?? 'متخصصون في تطوير المواقع والتطبيقات وتركيب أنظمة كاميرات الأمان';
$h_note  = $hero['note']      ?? 'نقدم حلولاً تقنية متكاملة لأمان ورقمنة أعمالك';
$h_btn1t = $hero['btn1_text'] ?? 'عرض الأعمال';
$h_btn1l = $hero['btn1_link'] ?? '#our-works';
$h_btn2t = $hero['btn2_text'] ?? 'تواصل معنا';
$h_btn2l = $hero['btn2_link'] ?? '#contact';

// English siblings
$h_title_en = $hero['title_en']     ?? '';
$h_sub_en   = $hero['subtitle_en']  ?? '';
$h_note_en  = $hero['note_en']      ?? '';
$h_btn1t_en = $hero['btn1_text_en'] ?? '';
$h_btn2t_en = $hero['btn2_text_en'] ?? '';

// Highlight the brand name inside the headline with the orange gradient.
$needle  = e($site_name);
$grad = function (string $title) use ($needle): string {
    $html = e($title);
    if ($needle !== '' && ($pos = mb_strpos($html, $needle)) !== false) {
        $html = mb_substr($html, 0, $pos)
              . '<span class="grad">' . $needle . '</span>'
              . mb_substr($html, $pos + mb_strlen($needle));
    }
    return $html;
};
$h1_html    = $grad($h_title);
$h1_html_en = $h_title_en !== '' ? $grad($h_title_en) : '';

// ── About ─────────────────────────────────────────────────
$ab_heading   = $about['heading'] ?? ('مرحباً بك في ' . $site_name);
$ab_content   = $about['content'] ?? '';
$ab_image     = $about['image']   ?? 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&h=450&fit=crop';
$ab_image_url = site_media_url($ab_image);
$ab_skills    = array_filter(array_map('trim', explode(',', $about['skills'] ?? '')));
// English siblings
$ab_heading_en = $about['heading_en'] ?? '';
$ab_content_en = $about['content_en'] ?? '';
$ab_skills_en  = array_filter(array_map('trim', explode(',', $about['skills_en'] ?? '')));

// ── Stats helper: split "500+" / "99%" / "24/7" → number + suffix ──
function stat_parts($value): array {
    $v = trim((string)$value);
    if (preg_match('/^\s*([\d.,]+)(.*)$/u', $v, $m)) {
        return ['target' => str_replace(',', '', $m[1]), 'suffix' => trim($m[2])];
    }
    return ['target' => '0', 'suffix' => $v];
}

// ── SEO ───────────────────────────────────────────────────
$canonical_url = $base_url . '/';
$og_image_url  = preg_match('#^https?://#', $logo_url) ? $logo_url : $base_url . '/' . ltrim($logo_url, '/');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
    <meta name="description" content="<?= e($site_desc) ?>">
    <meta name="keywords"    content="<?= e($site_kws) ?>">
    <meta name="author"      content="<?= e($site_name) ?>">
    <link rel="canonical"    href="<?= e($canonical_url) ?>">
    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= e($canonical_url) ?>">
    <meta property="og:site_name"   content="<?= e($site_name) ?>">
    <meta property="og:locale"      content="ar_AR">
    <meta property="og:title"       content="<?= e($site_name) ?> | <?= e($site_tagline) ?>">
    <meta property="og:description" content="<?= e($site_desc) ?>">
    <meta property="og:image"       content="<?= e($og_image_url) ?>">
    <meta property="og:image:alt"   content="<?= e($site_name) ?> logo">
    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= e($site_name) ?> | <?= e($site_tagline) ?>">
    <meta name="twitter:description" content="<?= e($site_desc) ?>">
    <meta name="twitter:image"       content="<?= e($og_image_url) ?>">
    <title><?= e($site_name) ?> | <?= e($site_tagline) ?></title>
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "<?= addslashes(e($site_name)) ?>",
      "description": "<?= addslashes(e($site_desc)) ?>",
      "url": "<?= e($canonical_url) ?>",
      "logo": "<?= e($og_image_url) ?>",
      <?php if ($ct_phone): ?>"telephone": "<?= addslashes(e($ct_phone)) ?>",<?php endif; ?>
      <?php if ($ct_email): ?>"email": "<?= addslashes(e($ct_email)) ?>",<?php endif; ?>
      <?php if ($ct_address): ?>
      "address": { "@type": "PostalAddress", "addressLocality": "<?= addslashes(e($ct_address)) ?>" },
      <?php endif; ?>
      "sameAs": [
        <?= implode(",\n        ", array_map(fn($s) => '"' . e($s['url']) . '"', array_filter($socials, fn($s) => !empty($s['url']) && $s['url'] !== '#'))) ?>
      ]
    }
    </script>
</head>
<body>

<?php require __DIR__ . '/partials/nav.php'; ?>

<!-- ============================ HERO ============================ -->
<section class="hero" id="home">
  <div class="hero-bg">
    <div class="hero-grid-lines"></div>
    <div class="hero-blob b1"></div>
    <div class="hero-blob b2"></div>
  </div>
  <div class="container hero-inner">
    <div class="hero-copy">
      <span class="eyebrow reveal" data-ar="شركة تقنية رائدة" data-en="A leading technology company">شركة تقنية رائدة</span>
      <h1 class="reveal d1"<?= bi_html($h1_html, $h1_html_en) ?>><?= $h1_html ?></h1>
      <p class="lede reveal d2"<?= bi_text($h_sub, $h_sub_en) ?>><?= e($h_sub) ?></p>
      <?php if ($h_note): ?><p class="hero-note reveal d2"<?= bi_text($h_note, $h_note_en) ?>><?= e($h_note) ?></p><?php endif; ?>
      <div class="hero-actions reveal d3">
        <a href="<?= e($h_btn1l) ?>" class="btn btn-dark btn-lg"<?= bi_text($h_btn1t, $h_btn1t_en) ?>><?= e($h_btn1t) ?></a>
        <a href="<?= e($h_btn2l) ?>" class="btn btn-ghost btn-lg">
          <span<?= bi_text($h_btn2t, $h_btn2t_en) ?>><?= e($h_btn2t) ?></span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      </div>
      <?php if ($statistics): ?>
      <div class="hero-trust reveal d4">
        <?php foreach (array_slice($statistics, 0, 3) as $i => $stat): ?>
          <?php if ($i > 0): ?><div class="divider"></div><?php endif; ?>
          <div class="ht"><b><?= e($stat['value']) ?></b><span<?= bi_text($stat['label'], $stat['label_en'] ?? '') ?>><?= e($stat['label']) ?></span></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="hero-visual reveal d2">
      <div class="mark-stage">
        <div class="mark-ring"></div>
        <div class="mark-ring r2"></div>
        <div class="mark-glow"></div>
        <svg class="mark-svg" viewBox="0 0 200 200" aria-label="ITX mark">
          <g transform="rotate(0 100 100)"><polygon class="tri" points="100,22 140,44 106,78" fill="#0E2150" stroke="#0E2150" stroke-width="7" stroke-linejoin="round"/></g>
          <g transform="rotate(60 100 100)"><polygon class="tri" points="100,22 140,44 106,78" fill="#FF7A1A" stroke="#FF7A1A" stroke-width="7" stroke-linejoin="round"/></g>
          <g transform="rotate(120 100 100)"><polygon class="tri" points="100,22 140,44 106,78" fill="#0E2150" stroke="#0E2150" stroke-width="7" stroke-linejoin="round"/></g>
          <g transform="rotate(180 100 100)"><polygon class="tri" points="100,22 140,44 106,78" fill="#FF7A1A" stroke="#FF7A1A" stroke-width="7" stroke-linejoin="round"/></g>
          <g transform="rotate(240 100 100)"><polygon class="tri" points="100,22 140,44 106,78" fill="#0E2150" stroke="#0E2150" stroke-width="7" stroke-linejoin="round"/></g>
          <g transform="rotate(300 100 100)"><polygon class="tri" points="100,22 140,44 106,78" fill="#FF7A1A" stroke="#FF7A1A" stroke-width="7" stroke-linejoin="round"/></g>
        </svg>
        <div class="mark-orbit">
          <div class="orbit-chip c1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg><span data-ar="تطوير" data-en="Develop">تطوير</span></div>
          <div class="orbit-chip c2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6z"/></svg><span data-ar="أمان" data-en="Secure">أمان</span></div>
          <div class="orbit-chip c3"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3 8 3"/><circle cx="12" cy="14" r="3"/></svg><span data-ar="مراقبة" data-en="Monitor">مراقبة</span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="container hero-marquee reveal">
    <p data-ar="موثوق به من قِبل شركات ومؤسسات رائدة" data-en="Trusted by leading companies & institutions">موثوق به من قِبل شركات ومؤسسات رائدة</p>
    <div class="marquee">
      <span>NILE BANK</span><span>SUDATEL</span><span>ZAIN</span><span>DAL GROUP</span><span>KENANA</span>
    </div>
  </div>
</section>

<!-- ============================ ABOUT ============================ -->
<section class="pad" id="about">
  <div class="container about-grid">
    <div class="about-media reveal">
      <div class="shot<?= $ab_image_url ? ' has-photo' : '' ?>">
        <img src="<?= e($ab_image_url ?: 'assets/itx-mark.png') ?>" alt="<?= e($ab_heading) ?>" loading="lazy" decoding="async">
      </div>
      <div class="about-badge">
        <div class="ab-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
        <div><b>99.9%</b><span data-ar="نسبة رضا العملاء" data-en="Client satisfaction">نسبة رضا العملاء</span></div>
      </div>
    </div>
    <div class="about-copy reveal d1">
      <span class="eyebrow" data-ar="من نحن" data-en="About us">من نحن</span>
      <h2<?= bi_text($ab_heading, $ab_heading_en) ?>><?= e($ab_heading) ?></h2>
      <?php if ($ab_content): ?>
        <div class="about-rich"<?= bi_html(nl2p($ab_content), nl2p($ab_content_en)) ?>><?= nl2p($ab_content) ?></div>
      <?php else: ?>
        <p>شركة <?= e($site_name) ?> متخصصة في تقديم حلول رقمية وتقنية متكاملة.</p>
      <?php endif; ?>
      <div class="skills">
        <div class="skill"><div class="skill-top"><span data-ar="تطوير الويب والتطبيقات" data-en="Web & app development">تطوير الويب والتطبيقات</span><span>96%</span></div><div class="bar"><i data-val="96"></i></div></div>
        <div class="skill"><div class="skill-top"><span data-ar="أنظمة المراقبة والأمان" data-en="Surveillance & security">أنظمة المراقبة والأمان</span><span>92%</span></div><div class="bar"><i data-val="92"></i></div></div>
        <div class="skill"><div class="skill-top"><span data-ar="تجربة وواجهة المستخدم UI/UX" data-en="UI/UX design">تجربة وواجهة المستخدم UI/UX</span><span>90%</span></div><div class="bar"><i data-val="90"></i></div></div>
      </div>
      <?php if ($ab_skills): ?>
      <?php
        $skills_ar_html = implode('', array_map(fn($s) => '<span>' . e($s) . '</span>', $ab_skills));
        $skills_en_html = $ab_skills_en ? implode('', array_map(fn($s) => '<span>' . e($s) . '</span>', $ab_skills_en)) : '';
      ?>
      <div class="about-tags"<?= bi_html($skills_ar_html, $skills_en_html) ?>><?= $skills_ar_html ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============================ SERVICES ============================ -->
<section class="pad services" id="services">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow" data-ar="خدماتنا" data-en="Our services">خدماتنا</span>
      <h2 data-ar="حلول متكاملة من الفكرة إلى التشغيل" data-en="Complete solutions, from idea to operation">حلول متكاملة من الفكرة إلى التشغيل</h2>
      <p data-ar="نغطّي رحلتك الرقمية بالكامل — تصميماً، برمجةً، وحمايةً." data-en="We cover your entire digital journey — design, code, and protection.">نغطّي رحلتك الرقمية بالكامل — تصميماً، برمجةً، وحمايةً.</p>
    </div>
    <div class="cards">
      <?php if ($services): foreach ($services as $i => $svc): ?>
      <article class="card reveal<?= $i % 3 ? ' d' . ($i % 3) : '' ?>">
        <div class="c-icon"><i class="<?= e($svc['icon'] ?: 'fas fa-gear') ?>"></i></div>
        <h3<?= bi_text($svc['title'], $svc['title_en'] ?? '') ?>><?= e($svc['title']) ?></h3>
        <p<?= bi_text($svc['description'], $svc['description_en'] ?? '') ?>><?= e($svc['description']) ?></p>
        <a href="#contact" class="c-link">
          <span data-ar="اعرف أكثر" data-en="Learn more">اعرف أكثر</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        </a>
      </article>
      <?php endforeach; else: ?>
      <article class="card reveal"><div class="c-icon"><i class="fas fa-globe"></i></div><h3>تطوير المواقع</h3><p>إنشاء مواقع إلكترونية احترافية وسريعة وآمنة بأحدث التقنيات.</p></article>
      <article class="card reveal d1"><div class="c-icon"><i class="fas fa-mobile-alt"></i></div><h3>تطبيقات الجوال</h3><p>تطوير تطبيقات جوال ذكية لأنظمة iOS و Android.</p></article>
      <article class="card reveal d2"><div class="c-icon"><i class="fas fa-video"></i></div><h3>كاميرات المراقبة</h3><p>تركيب وتجهيز أنظمة كاميرات أمان حديثة عالية الدقة.</p></article>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============================ STATS ============================ -->
<section class="pad stats">
  <div class="container">
    <div class="stats-grid">
      <?php if ($statistics): foreach ($statistics as $i => $stat): $sp = stat_parts($stat['value']); ?>
      <div class="stat reveal<?= $i ? ' d' . min($i, 4) : '' ?>">
        <div class="num" data-target="<?= e($sp['target']) ?>"><span class="val">0</span><?php if ($sp['suffix'] !== ''): ?><span class="suffix"><?= e($sp['suffix']) ?></span><?php endif; ?></div>
        <div class="label"<?= bi_text($stat['label'], $stat['label_en'] ?? '') ?>><?= e($stat['label']) ?></div>
        <div class="line"></div>
      </div>
      <?php endforeach; else: ?>
      <div class="stat reveal"><div class="num" data-target="500"><span class="val">0</span><span class="suffix">+</span></div><div class="label">مشروع منجز</div><div class="line"></div></div>
      <div class="stat reveal d1"><div class="num" data-target="15"><span class="val">0</span><span class="suffix">+</span></div><div class="label">سنوات خبرة</div><div class="line"></div></div>
      <div class="stat reveal d2"><div class="num" data-target="300"><span class="val">0</span><span class="suffix">+</span></div><div class="label">عميل راضٍ</div><div class="line"></div></div>
      <div class="stat reveal d3"><div class="num" data-target="99"><span class="val">0</span><span class="suffix">%</span></div><div class="label">معدل الرضا</div><div class="line"></div></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============================ OUR WORKS (AJAX) ============================ -->
<section class="pad" id="our-works">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow" data-ar="أعمالنا" data-en="Our work">أعمالنا</span>
      <h2 data-ar="مشاريع نفخر بتسليمها" data-en="Projects we're proud to ship">مشاريع نفخر بتسليمها</h2>
      <p data-ar="نماذج من أبرز مشاريعنا المنجزة في مختلف المجالات." data-en="A selection of solutions we've built across industries.">نماذج من أبرز مشاريعنا المنجزة في مختلف المجالات.</p>
    </div>

    <div class="works-categories" id="worksCategories">
      <button class="cat-btn active" data-cat="0" onclick="filterWorks(0)">
        <i class="fas fa-th"></i> <span data-ar="كل الأعمال" data-en="All work">كل الأعمال</span>
      </button>
    </div>

    <div class="works-grid" id="worksGrid">
      <div class="works-loading"><i class="fas fa-spinner"></i> جاري تحميل الأعمال…</div>
    </div>
  </div>
</section>

<!-- ── Project Modal ── -->
<div class="modal-overlay" id="projectModal" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-header-info">
        <h2 id="modalTitle">…</h2>
        <div class="modal-header-badges" id="modalBadges"></div>
      </div>
      <button class="modal-close" onclick="closeModal()" aria-label="إغلاق"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-media" id="modalMedia"><div class="no-media-msg"><i class="fas fa-spinner fa-spin"></i></div></div>
      <div class="modal-details" id="modalDetails"><div class="works-loading"><i class="fas fa-spinner"></i>جاري التحميل…</div></div>
    </div>
  </div>
</div>

<!-- ============================ TESTIMONIALS ============================ -->
<section class="pad testimonials" id="testimonials">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow" data-ar="آراء العملاء" data-en="Testimonials">آراء العملاء</span>
      <h2 data-ar="ثقة عملائنا هي أكبر إنجازاتنا" data-en="Our clients' trust is our greatest achievement">ثقة عملائنا هي أكبر إنجازاتنا</h2>
    </div>
    <div class="t-grid">
      <?php if ($testimonials): foreach ($testimonials as $i => $t): $stars = max(1, min(5, (int)$t['rating'])); ?>
      <article class="t-card reveal<?= $i % 3 ? ' d' . ($i % 3) : '' ?>">
        <div class="t-quote">”</div>
        <div class="t-stars"><?php for ($s = 0; $s < $stars; $s++): ?><i class="fas fa-star"></i><?php endfor; ?></div>
        <p<?= bi_text($t['content'], $t['content_en'] ?? '') ?>><?= e($t['content']) ?></p>
        <div class="t-author">
          <div class="av"><?= e(mb_substr($t['author_name'] ?? 'ا', 0, 1)) ?></div>
          <div><b><?= e($t['author_name']) ?></b><span<?= bi_text($t['author_role'], $t['author_role_en'] ?? '') ?>><?= e($t['author_role']) ?></span></div>
        </div>
      </article>
      <?php endforeach; else: ?>
      <article class="t-card reveal">
        <div class="t-quote">”</div>
        <div class="t-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
        <p>خدمة احترافية جداً، فريق <?= e($site_name) ?> متميز وجودة عالية.</p>
        <div class="t-author"><div class="av">ع</div><div><b>عميل راضٍ</b><span>صاحب أعمال</span></div></div>
      </article>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============================ BLOG ============================ -->
<?php if ($blog_preview): ?>
<section class="pad" id="blog">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow" data-ar="المدونة" data-en="Blog">المدونة</span>
      <h2 data-ar="أحدث المقالات والأفكار" data-en="Latest articles & insights">أحدث المقالات والأفكار</h2>
      <p data-ar="نشارك خبرتنا في التقنية والأمان لنبقيك في المقدمة." data-en="We share our tech & security expertise to keep you ahead.">نشارك خبرتنا في التقنية والأمان لنبقيك في المقدمة.</p>
    </div>
    <div class="blog-grid">
      <?php foreach ($blog_preview as $i => $bp): ?>
      <a class="post reveal<?= $i % 3 ? ' d' . ($i % 3) : '' ?>" href="blog-post.php?slug=<?= urlencode($bp['slug']) ?>">
        <div class="post-media <?= $bp['thumbnail'] ? 'has-photo' : 'pg' . (($i % 3) + 1) ?>">
          <?php if ($bp['thumbnail']): ?>
            <img src="<?= e(site_media_url($bp['thumbnail'])) ?>" alt="<?= e($bp['title']) ?>" loading="lazy" decoding="async">
          <?php else: ?>
            <img src="assets/itx-mark.png" alt="<?= e($bp['title']) ?>" loading="lazy" decoding="async">
          <?php endif; ?>
        </div>
        <div class="post-body">
          <div class="post-meta">
            <?php if ($bp['cat_name']): ?><span class="post-tag"<?= bi_text($bp['cat_name'], $bp['cat_name_en'] ?? '') ?>><?= e($bp['cat_name']) ?></span><?php endif; ?>
            <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($bp['created_at'])) ?></span>
            <span><i class="fas fa-eye"></i> <?= number_format((int)$bp['views']) ?></span>
          </div>
          <h3<?= bi_text($bp['title'], $bp['title_en'] ?? '') ?>><?= e($bp['title']) ?></h3>
          <p<?= bi_text($bp['excerpt'], $bp['excerpt_en'] ?? '') ?>><?= e($bp['excerpt']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:48px;">
      <a href="blog.php" class="btn btn-dark btn-lg"><span data-ar="عرض جميع المقالات" data-en="View all articles">عرض جميع المقالات</span></a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================ CONTACT ============================ -->
<section class="pad contact" id="contact">
  <div class="container contact-grid">
    <div class="contact-info reveal">
      <span class="eyebrow" data-ar="تواصل معنا" data-en="Contact us">تواصل معنا</span>
      <h2 data-ar="لنبدأ مشروعك القادم معاً" data-en="Let's start your next project together">لنبدأ مشروعك القادم معاً</h2>
      <p data-ar="أخبرنا عن فكرتك وسيتواصل معك فريقنا في أقرب وقت لمناقشة التفاصيل وتقديم استشارة مجانية." data-en="Tell us about your idea and our team will reach out to discuss details and offer a free consultation.">أخبرنا عن فكرتك وسيتواصل معك فريقنا في أقرب وقت لمناقشة التفاصيل وتقديم استشارة مجانية.</p>

      <?php if ($ct_phone): ?>
      <div class="info-item"><div class="ii-icon"><i class="fas fa-phone"></i></div><div><b data-ar="الهاتف / واتساب" data-en="Phone / WhatsApp">الهاتف / واتساب</b><span dir="ltr"><a href="tel:<?= e($ct_phone) ?>"><?= e($ct_phone) ?></a></span></div></div>
      <?php endif; ?>
      <?php if ($ct_email): ?>
      <div class="info-item"><div class="ii-icon"><i class="fas fa-envelope"></i></div><div><b data-ar="البريد الإلكتروني" data-en="Email">البريد الإلكتروني</b><span dir="ltr"><a href="mailto:<?= e($ct_email) ?>"><?= e($ct_email) ?></a></span></div></div>
      <?php endif; ?>
      <?php if ($ct_address): ?>
      <div class="info-item"><div class="ii-icon"><i class="fas fa-map-marker-alt"></i></div><div><b data-ar="العنوان" data-en="Address">العنوان</b><span<?= bi_text($ct_address, $ct_address_en) ?>><?= e($ct_address) ?></span></div></div>
      <?php endif; ?>

      <?php if ($ct_map): ?>
      <div class="contact-map"><?= $ct_map ?></div>
      <?php else: ?>
      <div class="map-embed"><div class="pin"></div></div>
      <?php endif; ?>
    </div>

    <form class="contact-form reveal d1" id="contactForm">
      <h3 data-ar="أرسل لنا رسالة" data-en="Send us a message">أرسل لنا رسالة</h3>
      <p class="cf-sub" data-ar="الحقول المعلّمة بـ * مطلوبة." data-en="Fields marked * are required.">الحقول المعلّمة بـ * مطلوبة.</p>
      <div class="field-row">
        <div class="field"><label data-ar="الاسم *" data-en="Name *">الاسم *</label><input type="text" name="name" required data-attr="placeholder" data-ar="اسمك الكامل" data-en="Your full name" placeholder="اسمك الكامل"></div>
        <div class="field"><label data-ar="الهاتف" data-en="Phone">الهاتف</label><input type="tel" name="phone" data-attr="placeholder" data-ar="رقم هاتفك" data-en="Your phone" placeholder="رقم هاتفك" dir="ltr"></div>
      </div>
      <div class="field"><label data-ar="البريد الإلكتروني *" data-en="Email *">البريد الإلكتروني *</label><input type="email" name="email" required data-attr="placeholder" data-ar="you@email.com" data-en="you@email.com" placeholder="you@email.com" dir="ltr"></div>
      <div class="field"><label data-ar="الخدمة المطلوبة" data-en="Service needed">الخدمة المطلوبة</label>
        <select name="service">
          <option data-ar="تطوير موقع" data-en="Web development">تطوير موقع</option>
          <option data-ar="تطبيق موبايل" data-en="Mobile app">تطبيق موبايل</option>
          <option data-ar="كاميرات مراقبة" data-en="CCTV cameras">كاميرات مراقبة</option>
          <option data-ar="نظام أمان" data-en="Security system">نظام أمان</option>
          <option data-ar="أخرى" data-en="Other">أخرى</option>
        </select>
      </div>
      <div class="field"><label data-ar="رسالتك *" data-en="Your message *">رسالتك *</label><textarea name="message" rows="4" required data-attr="placeholder" data-ar="أخبرنا عن مشروعك…" data-en="Tell us about your project…" placeholder="أخبرنا عن مشروعك…"></textarea></div>
      <button type="submit" class="btn btn-primary btn-lg" style="width:100%"><span data-ar="إرسال الطلب" data-en="Send request">إرسال الطلب</span></button>
      <p class="form-note" id="formNote" data-ar="نحترم خصوصيتك — لن نشارك بياناتك مع أي جهة." data-en="We respect your privacy — your data is never shared.">نحترم خصوصيتك — لن نشارك بياناتك مع أي جهة.</p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>window.CONTACT_EMAIL = '<?= e(addslashes($ct_email ?: 'info@itx.sd')) ?>';</script>
<script src="assets/js/home.js"></script>
</body>
</html>
