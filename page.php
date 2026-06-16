<?php
require_once __DIR__ . '/partials/bootstrap.php';

// ── Validate slug ────────────────────────────────────────
$slug = $_GET['slug'] ?? '';
if (!in_array($slug, ['privacy', 'terms'], true)) {
    header('Location: index.php');
    exit;
}

// ── Fetch page content ────────────────────────────────────
$page = null;
try {
    $stmt = getDB()->prepare("SELECT * FROM content_pages WHERE slug = ?");
    $stmt->execute([$slug]);
    $page = $stmt->fetch() ?: null;
} catch (\Exception $ex) {}

// Page title fallbacks
$defaults = [
    'privacy' => ['title' => 'سياسة الخصوصية', 'content' => '<p>لم يتم إضافة محتوى سياسة الخصوصية بعد.</p>'],
    'terms'   => ['title' => 'شروط الاستخدام',  'content' => '<p>لم يتم إضافة محتوى شروط الاستخدام بعد.</p>'],
];

$page_title   = $page['title']   ?? $defaults[$slug]['title'];
$page_content = $page['content'] ?? $defaults[$slug]['content'];
$page_title_en   = $page['title_en']   ?? '';
$page_content_en = $page['content_en'] ?? '';

// ── SEO ───────────────────────────────────────────────────
$canonical_url = $base_url . '/page.php?slug=' . rawurlencode($slug);
$logo_url_abs  = preg_match('#^https?://#', $logo_url) ? $logo_url : $base_url . '/' . ltrim($logo_url, '/');
$meta_desc     = $page['meta_description'] ?? strip_tags(mb_substr($page_content, 0, 160));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
    <meta name="description" content="<?= e($meta_desc) ?>">
    <meta name="robots" content="noindex,follow">
    <link rel="canonical" href="<?= e($canonical_url) ?>">
    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= e($canonical_url) ?>">
    <meta property="og:site_name"   content="<?= e($site_name) ?>">
    <meta property="og:locale"      content="ar_AR">
    <meta property="og:title"       content="<?= e($page_title) ?> | <?= e($site_name) ?>">
    <meta property="og:description" content="<?= e($meta_desc) ?>">
    <meta property="og:image"       content="<?= e($logo_url_abs) ?>">
    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary">
    <meta name="twitter:title"       content="<?= e($page_title) ?> | <?= e($site_name) ?>">
    <meta name="twitter:description" content="<?= e($meta_desc) ?>">
    <meta name="twitter:image"       content="<?= e($logo_url_abs) ?>">
    <title><?= e($page_title) ?> | <?= e($site_name) ?></title>
</head>
<body>

<?php require __DIR__ . '/partials/nav.php'; ?>

<section class="subhero">
  <div class="container">
    <span class="eyebrow"><?= e($site_name) ?></span>
    <h1<?= bi_text($page_title, $page_title_en) ?>><?= e($page_title) ?></h1>
    <p<?= bi_text($site_tagline, $site_tagline_en) ?>><?= e($site_tagline) ?></p>
  </div>
</section>

<section class="pad" style="padding-block:56px 90px">
  <div class="container">
    <div class="page-body">
      <div class="page-rich"<?= bi_html($page_content, $page_content_en) ?>><?= $page_content ?></div>
      <p class="last-updated"><i class="fas fa-clock"></i> <span data-ar="آخر تحديث:" data-en="Last updated:">آخر تحديث:</span> <?= date('d/m/Y') ?></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
