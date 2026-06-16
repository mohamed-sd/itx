<?php
require_once __DIR__ . '/partials/bootstrap.php';
$active_nav = 'blog';

function sanitize_post_content(string $html): string
{
    if ($html === '') {
        return '';
    }

    $clean = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html);
    $clean = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $clean);
    $clean = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $clean);
    $clean = preg_replace('/\s(href|src)\s*=\s*"\s*javascript:[^"]*"/i', ' $1="#"', $clean);
    $clean = preg_replace("/\s(href|src)\s*=\s*'\s*javascript:[^']*'/i", " $1='#'", $clean);

    return $clean ?? '';
}

// ── Load post ─────────────────────────────────────────────
$slug = trim($_GET['slug'] ?? '');
$post = null;
if ($slug) {
    try {
        $stmt = getDB()->prepare(
            "SELECT p.*, c.name AS cat_name, c.name_en AS cat_name_en, c.slug AS cat_slug
             FROM blog_posts p
             LEFT JOIN blog_categories c ON c.id=p.category_id
             WHERE p.slug=? AND p.status='published'"
        );
        $stmt->execute([$slug]);
        $post = $stmt->fetch() ?: null;
    } catch (\Exception $ex) {}
}

if (!$post) {
    header('Location: blog.php');
    exit;
}

try {
    getDB()->prepare("UPDATE blog_posts SET views=views+1 WHERE id=?")->execute([$post['id']]);
} catch (\Exception $ex) {}

$related = [];
try {
    $stmt = getDB()->prepare(
        "SELECT id,title,title_en,slug,thumbnail,excerpt,excerpt_en,created_at,cat_name,cat_name_en
         FROM (
             SELECT p.id,p.title,p.title_en,p.slug,p.thumbnail,p.excerpt,p.excerpt_en,p.created_at,c.name AS cat_name,c.name_en AS cat_name_en
             FROM blog_posts p
             LEFT JOIN blog_categories c ON c.id=p.category_id
             WHERE p.status='published' AND p.id!=? AND p.category_id=?
             ORDER BY p.created_at DESC LIMIT 3
         ) t"
    );
    $stmt->execute([$post['id'], $post['category_id'] ?: 0]);
    $related = $stmt->fetchAll();
    if (count($related) < 3) {
        $stmt2 = getDB()->prepare(
            "SELECT p.id,p.title,p.title_en,p.slug,p.thumbnail,p.excerpt,p.excerpt_en,p.created_at,c.name AS cat_name,c.name_en AS cat_name_en
             FROM blog_posts p
             LEFT JOIN blog_categories c ON c.id=p.category_id
             WHERE p.status='published' AND p.id!=? AND p.id NOT IN (" .
             (count($related) ? implode(',', array_column($related, 'id')) : '0') . ")
             ORDER BY p.created_at DESC LIMIT " . (3 - count($related))
        );
        $stmt2->execute([$post['id']]);
        $related = array_merge($related, $stmt2->fetchAll());
    }
} catch (\Exception $ex) {}

$sidebarCats = [];
try {
    $sidebarCats = getDB()->query(
        "SELECT c.*, COUNT(p.id) AS post_count
         FROM blog_categories c
         LEFT JOIN blog_posts p ON p.category_id=c.id AND p.status='published'
         WHERE c.status='active'
         GROUP BY c.id ORDER BY c.sort_order"
    )->fetchAll();
} catch (\Exception $ex) {}

$meta_title = $post['meta_title'] ?: ($post['title'] . ' | ' . $site_name);
$meta_desc  = $post['meta_description'] ?: $post['excerpt'] ?: $gs('site_description', '');
$og_image   = site_media_url($post['thumbnail'] ?: $logo_path, 'assets/itx-logo-full.png');
$tags_arr     = array_filter(array_map('trim', explode(',', $post['tags'] ?? '')));
$post_html    = sanitize_post_content((string)($post['content'] ?? ''));
$post_html_en = sanitize_post_content((string)($post['content_en'] ?? ''));

// ── SEO ───────────────────────────────────────────────────
$canonical_url = $base_url . '/blog-post.php?slug=' . rawurlencode($post['slug']);
$og_image_abs  = preg_match('#^https?://#', $og_image) ? $og_image : $base_url . '/' . ltrim($og_image, '/');
$published_iso = $post['created_at'] ? date('c', strtotime($post['created_at'])) : '';
$modified_iso  = $post['updated_at'] ? date('c', strtotime($post['updated_at'])) : $published_iso;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
    <meta name="description" content="<?= e($meta_desc) ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <?php if ($tags_arr): ?><meta name="keywords" content="<?= e(implode(', ', $tags_arr)) ?>"><?php endif; ?>
    <link rel="canonical" href="<?= e($canonical_url) ?>">
    <!-- Open Graph -->
    <meta property="og:type"        content="article">
    <meta property="og:url"         content="<?= e($canonical_url) ?>">
    <meta property="og:site_name"   content="<?= e($site_name) ?>">
    <meta property="og:locale"      content="ar_AR">
    <meta property="og:title"       content="<?= e($meta_title) ?>">
    <meta property="og:description" content="<?= e($meta_desc) ?>">
    <meta property="og:image"       content="<?= e($og_image_abs) ?>">
    <meta property="og:image:alt"   content="<?= e($post['title']) ?>">
    <meta property="article:published_time" content="<?= e($published_iso) ?>">
    <?php if ($modified_iso): ?><meta property="article:modified_time" content="<?= e($modified_iso) ?>"><?php endif; ?>
    <?php if ($post['cat_name']): ?><meta property="article:section" content="<?= e($post['cat_name']) ?>"><?php endif; ?>
    <?php foreach ($tags_arr as $tag): ?><meta property="article:tag" content="<?= e($tag) ?>"><?php endforeach; ?>
    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= e($meta_title) ?>">
    <meta name="twitter:description" content="<?= e($meta_desc) ?>">
    <meta name="twitter:image"       content="<?= e($og_image_abs) ?>">
    <title><?= e($meta_title) ?></title>
    <!-- JSON-LD Article -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": "<?= addslashes(e($post['title'])) ?>",
      "description": "<?= addslashes(e($meta_desc)) ?>",
      "url": "<?= e($canonical_url) ?>",
      "image": "<?= e($og_image_abs) ?>",
      "datePublished": "<?= e($published_iso) ?>",
      "dateModified": "<?= e($modified_iso ?: $published_iso) ?>",
      <?php if ($post['cat_name']): ?>"articleSection": "<?= addslashes(e($post['cat_name'])) ?>",<?php endif; ?>
      "publisher": {
        "@type": "Organization",
        "name": "<?= addslashes(e($site_name)) ?>",
        "url": "<?= e($base_url) ?>/",
        "logo": { "@type": "ImageObject", "url": "<?= e(preg_match('#^https?://#', $logo_url) ? $logo_url : $base_url . '/' . ltrim($logo_url, '/')) ?>" }
      }
    }
    </script>
</head>
<body>

<?php require __DIR__ . '/partials/nav.php'; ?>

<section class="pad" style="padding-block:120px 40px">
  <div class="container">
    <nav class="crumbs" aria-label="breadcrumb">
      <a href="index.php" data-ar="الرئيسية" data-en="Home">الرئيسية</a>
      <i class="fas fa-chevron-left"></i>
      <a href="blog.php" data-ar="المدونة" data-en="Blog">المدونة</a>
      <?php if ($post['cat_name']): ?>
      <i class="fas fa-chevron-left"></i>
      <a href="blog.php?cat=<?= urlencode($post['cat_slug'] ?? '') ?>"<?= bi_text($post['cat_name'], $post['cat_name_en'] ?? '') ?>><?= e($post['cat_name']) ?></a>
      <?php endif; ?>
      <i class="fas fa-chevron-left"></i>
      <span class="current"<?= bi_text(mb_strimwidth($post['title'], 0, 42, '…'), ($post['title_en'] ?? '') !== '' ? mb_strimwidth($post['title_en'], 0, 42, '…') : '') ?>><?= e(mb_strimwidth($post['title'], 0, 42, '…')) ?></span>
    </nav>

    <article class="article">
      <header class="article-head">
        <?php if ($post['cat_name']): ?>
        <a href="blog.php?cat=<?= urlencode($post['cat_slug'] ?? '') ?>" class="eyebrow"<?= bi_text($post['cat_name'], $post['cat_name_en'] ?? '') ?>><?= e($post['cat_name']) ?></a>
        <?php endif; ?>
        <h1<?= bi_text($post['title'], $post['title_en'] ?? '') ?>><?= e($post['title']) ?></h1>
        <div class="article-meta">
          <span><i class="fas fa-user"></i> <?= e($post['author']) ?></span>
          <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
          <span><i class="fas fa-eye"></i> <?= number_format((int)$post['views']) ?> مشاهدة</span>
          <?php if ($post['updated_at'] !== $post['created_at']): ?>
          <span><i class="fas fa-sync"></i> آخر تحديث: <?= date('d/m/Y', strtotime($post['updated_at'])) ?></span>
          <?php endif; ?>
        </div>
      </header>

      <?php if ($post['thumbnail']): ?>
      <div class="article-cover">
        <img src="<?= e(site_media_url($post['thumbnail'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy" decoding="async" onerror="this.parentElement.style.display='none'">
      </div>
      <?php endif; ?>

      <div class="article-body"<?= bi_html($post_html, $post_html_en) ?>><?= $post_html ?></div>

      <?php if ($tags_arr): ?>
      <div class="article-tags">
        <strong style="color:var(--muted);font-size:.85rem;align-self:center"><i class="fas fa-tags"></i> <span data-ar="الوسوم:" data-en="Tags:">الوسوم:</span></strong>
        <?php foreach ($tags_arr as $tag): ?><a href="blog.php?tag=<?= urlencode($tag) ?>" class="bc-tag"><?= e($tag) ?></a><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php
      $scheme  = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
      $pageUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
      $shareTitle = urlencode($post['title']);
      $shareUrl   = urlencode($pageUrl);
      ?>
      <div class="article-share">
        <span data-ar="مشاركة:" data-en="Share:">مشاركة:</span>
        <a class="share-wa" href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        <a href="https://twitter.com/intent/tweet?text=<?= $shareTitle ?>&url=<?= $shareUrl ?>" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <button type="button" onclick="copyLink()" aria-label="نسخ الرابط"><i class="fas fa-link" id="copyIcon"></i></button>
      </div>
    </article>
  </div>
</section>

<?php if ($related): ?>
<section class="pad" style="padding-block:0 90px">
  <div class="container">
    <div class="section-head reveal" style="text-align:start;max-width:none;margin-bottom:28px">
      <h2 style="font-size:1.6rem" data-ar="مقالات ذات صلة" data-en="Related articles">مقالات ذات صلة</h2>
    </div>
    <div class="related-grid">
      <?php foreach ($related as $rel): ?>
      <a href="blog-post.php?slug=<?= urlencode($rel['slug']) ?>" class="blog-card">
        <div class="bc-img">
          <?php if ($rel['thumbnail']): ?>
            <img src="<?= e(site_media_url($rel['thumbnail'])) ?>" alt="<?= e($rel['title']) ?>" loading="lazy" decoding="async"
                 onerror="this.parentElement.innerHTML='<div class=\'bc-no-img\'><i class=\'fas fa-newspaper\'></i></div>'">
          <?php else: ?>
            <div class="bc-no-img"><i class="fas fa-newspaper"></i></div>
          <?php endif; ?>
          <?php if ($rel['cat_name']): ?><span class="bc-cat"<?= bi_text($rel['cat_name'], $rel['cat_name_en'] ?? '') ?>><?= e($rel['cat_name']) ?></span><?php endif; ?>
        </div>
        <div class="bc-body">
          <h2<?= bi_text($rel['title'], $rel['title_en'] ?? '') ?>><?= e($rel['title']) ?></h2>
          <p<?= bi_text($rel['excerpt'], $rel['excerpt_en'] ?? '') ?>><?= e($rel['excerpt']) ?></p>
          <div class="bc-meta"><span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($rel['created_at'])) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($sidebarCats): ?>
    <div class="blog-filters" style="justify-content:flex-start;margin-top:44px">
      <span style="align-self:center;color:var(--muted);font-size:.88rem;font-weight:600"><i class="fas fa-folder"></i> <span data-ar="تصفّح الفئات:" data-en="Browse categories:">تصفّح الفئات:</span></span>
      <?php foreach ($sidebarCats as $sc): ?>
      <a href="blog.php?cat=<?= urlencode($sc['slug']) ?>" class="filter-btn"><span<?= bi_text($sc['name'], $sc['name_en'] ?? '') ?>><?= e($sc['name']) ?></span> <small>(<?= (int)$sc['post_count'] ?>)</small></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
function copyLink() {
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(window.location.href).then(() => {
        const icon = document.getElementById('copyIcon');
        if (!icon) return;
        icon.className = 'fas fa-check';
        setTimeout(() => icon.className = 'fas fa-link', 2000);
    });
}
</script>
</body>
</html>
