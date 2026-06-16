<?php
require_once __DIR__ . '/partials/bootstrap.php';
$active_nav = 'blog';

// ── Filters / Pagination ──────────────────────────────────
$search   = trim($_GET['q'] ?? '');
$catSlug  = trim($_GET['cat'] ?? '');
$tagSlug  = trim($_GET['tag'] ?? '');
$page_num = max(1, (int)($_GET['p'] ?? 1));
$per_page = 9;
$offset   = ($page_num - 1) * $per_page;

// ── Fetch categories ──────────────────────────────────────
$bcats = [];
try {
    $bcats = getDB()->query("SELECT * FROM blog_categories WHERE status='active' ORDER BY sort_order")->fetchAll();
} catch (\Exception $ex) {}

// ── Category counts (avoid N+1 queries) ───────────────────
$categoryCounts = [];
try {
    $rows = getDB()->query(
        "SELECT category_id, COUNT(*) AS cnt
         FROM blog_posts
         WHERE status='published' AND category_id IS NOT NULL
         GROUP BY category_id"
    )->fetchAll();
    foreach ($rows as $r) {
        $categoryCounts[(int)$r['category_id']] = (int)$r['cnt'];
    }
} catch (\Exception $ex) {}

// ── Build query ───────────────────────────────────────────
$where  = ["p.status='published'"];
$params = [];

if ($catSlug) {
    $where[]  = "c.slug = ?";
    $params[] = $catSlug;
}
if ($tagSlug) {
    $where[]  = "FIND_IN_SET(?, REPLACE(REPLACE(p.tags,' ',''),', ',','))";
    $params[] = $tagSlug;
}
if ($search) {
    $where[]  = "(p.title LIKE ? OR p.excerpt LIKE ? OR p.tags LIKE ?)";
    $like      = "%$search%";
    $params    = array_merge($params, [$like, $like, $like]);
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

try {
    $pdo = getDB();
    $totalRow  = $pdo->prepare("SELECT COUNT(*) FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id $whereSQL");
    $totalRow->execute($params);
    $total = (int)$totalRow->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS cat_name, c.name_en AS cat_name_en, c.slug AS cat_slug
         FROM blog_posts p
         LEFT JOIN blog_categories c ON c.id=p.category_id
         $whereSQL
         ORDER BY p.created_at DESC
         LIMIT $per_page OFFSET $offset"
    );
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch (\Exception $ex) {
    $total = 0;
    $posts = [];
}

$total_pages = max(1, (int)ceil($total / $per_page));

// ── Meta ──────────────────────────────────────────────────
$page_title = 'المدونة';
$meta_desc  = 'مقالات ونصائح تقنية من فريق ' . $site_name;
if ($catSlug) {
    $curCat = array_values(array_filter($bcats, fn($c) => $c['slug'] === $catSlug))[0] ?? null;
    if ($curCat) {
        $page_title = 'المدونة — ' . $curCat['name'];
        $meta_desc  = 'مقالات في فئة ' . $curCat['name'];
    }
}
if ($search) {
    $page_title = 'نتائج البحث: ' . $search;
    $meta_desc  = "نتائج البحث عن: $search";
}

// ── SEO ───────────────────────────────────────────────────
$logo_url_abs = preg_match('#^https?://#', $logo_url) ? $logo_url : $base_url . '/' . ltrim($logo_url, '/');
$is_filtered   = $search || $tagSlug;
$is_paginated  = $page_num > 1;
$canonical_url = $base_url . '/blog.php' . ($catSlug ? '?cat=' . rawurlencode($catSlug) : '');
$robots_value  = ($is_filtered || $is_paginated) ? 'noindex,follow' : 'index,follow,max-image-preview:large';

function cat_count(array $map, int $catId): int {
    return $map[$catId] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php require __DIR__ . '/partials/head.php'; ?>
    <meta name="description" content="<?= e($meta_desc) ?>">
    <meta name="robots" content="<?= e($robots_value) ?>">
    <?php if (!$is_filtered && !$is_paginated): ?>
    <link rel="canonical" href="<?= e($canonical_url) ?>">
    <?php endif; ?>
    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= e($canonical_url) ?>">
    <meta property="og:site_name"   content="<?= e($site_name) ?>">
    <meta property="og:locale"      content="ar_AR">
    <meta property="og:title"       content="<?= e($page_title) ?> | <?= e($site_name) ?>">
    <meta property="og:description" content="<?= e($meta_desc) ?>">
    <meta property="og:image"       content="<?= e($logo_url_abs) ?>">
    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= e($page_title) ?> | <?= e($site_name) ?>">
    <meta name="twitter:description" content="<?= e($meta_desc) ?>">
    <meta name="twitter:image"       content="<?= e($logo_url_abs) ?>">
    <title><?= e($page_title) ?> | <?= e($site_name) ?></title>
</head>
<body>

<?php require __DIR__ . '/partials/nav.php'; ?>

<section class="subhero">
  <div class="container">
    <span class="eyebrow" data-ar="المدونة" data-en="Blog">المدونة</span>
    <?php if ($search || $catSlug): ?>
    <h1><?= e($page_title) ?></h1>
    <p><?= e($meta_desc) ?></p>
    <?php else: ?>
    <h1 data-ar="مقالات وأفكار تقنية" data-en="Articles &amp; tech insights">مقالات وأفكار تقنية</h1>
    <p data-ar="مقالات ونصائح تقنية من فريق <?= e($site_name) ?>" data-en="Tech articles &amp; tips from the <?= e($site_name) ?> team">مقالات ونصائح تقنية من فريق <?= e($site_name) ?></p>
    <?php endif; ?>
    <form class="blog-search-wrap" method="GET" action="blog.php">
      <?php if ($catSlug): ?><input type="hidden" name="cat" value="<?= e($catSlug) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="ابحث في المقالات…" aria-label="بحث">
      <button type="submit" aria-label="بحث"><i class="fas fa-search"></i></button>
    </form>
  </div>
</section>

<section class="pad">
  <div class="container">

    <?php if ($bcats): ?>
    <div class="blog-filters">
      <a href="blog.php<?= $search ? '?q=' . urlencode($search) : '' ?>" class="filter-btn <?= !$catSlug && !$tagSlug ? 'active' : '' ?>">
        <i class="fas fa-th"></i> <span data-ar="الكل" data-en="All">الكل</span>
      </a>
      <?php foreach ($bcats as $c): ?>
      <a href="blog.php?cat=<?= urlencode($c['slug']) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="filter-btn <?= $catSlug === $c['slug'] ? 'active' : '' ?>">
        <span<?= bi_text($c['name'], $c['name_en'] ?? '') ?>><?= e($c['name']) ?></span> <small>(<?= cat_count($categoryCounts, (int)$c['id']) ?>)</small>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($search || $catSlug): ?>
    <div class="blog-toolbar">
      <?php if ($search): ?><span>نتائج البحث عن: <strong><?= e($search) ?></strong> — </span><?php endif; ?>
      <strong><?= $total ?></strong> مقال
      <a href="blog.php" class="clear"><i class="fas fa-times"></i> مسح</a>
    </div>
    <?php endif; ?>

    <?php if ($posts): ?>
    <div class="blog-grid">
      <?php foreach ($posts as $post): ?>
      <a href="blog-post.php?slug=<?= urlencode($post['slug']) ?>" class="blog-card">
        <div class="bc-img">
          <?php if ($post['thumbnail']): ?>
            <img src="<?= e(site_media_url($post['thumbnail'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy" decoding="async"
                 onerror="this.parentElement.innerHTML='<div class=\'bc-no-img\'><i class=\'fas fa-newspaper\'></i></div>'">
          <?php else: ?>
            <div class="bc-no-img"><i class="fas fa-newspaper"></i></div>
          <?php endif; ?>
          <?php if ($post['cat_name']): ?><span class="bc-cat"<?= bi_text($post['cat_name'], $post['cat_name_en'] ?? '') ?>><?= e($post['cat_name']) ?></span><?php endif; ?>
        </div>
        <div class="bc-body">
          <h2<?= bi_text($post['title'], $post['title_en'] ?? '') ?>><?= e($post['title']) ?></h2>
          <p<?= bi_text($post['excerpt'], $post['excerpt_en'] ?? '') ?>><?= e($post['excerpt']) ?></p>
          <div class="bc-meta">
            <span><i class="fas fa-user"></i> <?= e($post['author']) ?></span>
            <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
            <span><i class="fas fa-eye"></i> <?= number_format((int)$post['views']) ?></span>
          </div>
          <?php
          $tags = array_filter(array_map('trim', explode(',', $post['tags'] ?? '')));
          if ($tags): ?>
          <div class="bc-tags">
            <?php foreach (array_slice($tags, 0, 4) as $tag): ?><span class="bc-tag"><?= e($tag) ?></span><?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php
      $base = 'blog.php?' . http_build_query(array_filter(['q' => $search, 'cat' => $catSlug, 'tag' => $tagSlug]));
      $base .= $base !== 'blog.php?' ? '&' : '';
      ?>
      <a href="<?= $base ?>p=<?= max(1, $page_num - 1) ?>" class="page-btn <?= $page_num <= 1 ? 'disabled' : '' ?>"><i class="fas fa-chevron-right"></i></a>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <a href="<?= $base ?>p=<?= $i ?>" class="page-btn <?= $i === $page_num ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <a href="<?= $base ?>p=<?= min($total_pages, $page_num + 1) ?>" class="page-btn <?= $page_num >= $total_pages ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i></a>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="blog-empty">
      <i class="fas fa-search"></i>
      <?= $search ? 'لا توجد نتائج للبحث عن "' . e($search) . '"' : 'لا توجد مقالات منشورة بعد' ?>
      <?php if ($search || $catSlug): ?>
      <br><a href="blog.php">← عرض كل المقالات</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
