<?php
require_once __DIR__ . '/config/db.php';
function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

// ── Settings ──────────────────────────────────────────────
$settings = [];
try {
    $rows     = getDB()->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
    $settings = array_column($rows, 'setting_value', 'setting_key');
} catch (\Exception $ex) {}
$gs = fn($k, $d = '') => $settings[$k] ?? $d;

$site_name    = $gs('site_name', 'ITX');
$site_tagline = $gs('site_tagline', 'حلول برمجية وتركيب كاميرات أمان');
$logo_path    = $gs('site_logo', 'logo.jpeg');
$logo_url     = site_media_url($logo_path, 'logo.jpeg');
$wa_number    = $gs('whatsapp_number', '966501234567');
$wa_msg       = $gs('whatsapp_msg', 'مرحباً، أود التواصل معكم');

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
        "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
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

function cat_count(array $map, int $catId): int {
    return $map[$catId] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($meta_desc) ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <title><?= e($page_title) ?> | <?= e($site_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root{
            --brand-ink:#3F4D60;
            --brand-cyan:#2FA8B9;
            --brand-mint:#0FECC1;
            --muted:#6b7280;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Cairo',sans-serif;background:#f6f8fb;color:#1f2937;line-height:1.6}
        html{scroll-behavior:smooth}

        header{background:linear-gradient(135deg,var(--brand-ink) 0%,var(--brand-cyan) 100%);color:white;padding:1rem 0;position:sticky;top:0;z-index:1000;box-shadow:0 2px 10px rgba(0,0,0,.1)}
        nav{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;padding:0 2rem;position:relative}
        .logo{font-size:1.6rem;font-weight:900;display:flex;align-items:center;gap:.5rem;text-decoration:none;color:white}
        .logo img{height:45px;width:45px;border-radius:50%;object-fit:cover;border:2px solid var(--brand-mint);background:white;padding:2px}
        .nav-right{display:flex;gap:1.5rem;list-style:none;align-items:center}
        .nav-right a{color:rgba(255,255,255,.9);text-decoration:none;font-weight:600;font-size:.9rem;transition:color .2s}
        .nav-right a:hover{color:var(--brand-mint)}
        .mobile-menu{display:none;background:none;border:none;color:white;font-size:1.35rem;cursor:pointer}
        .nav-right.active{display:flex!important}

        .blog-hero{background:linear-gradient(135deg,var(--brand-ink) 0%,var(--brand-cyan) 100%);color:white;padding:4rem 2rem 3.4rem;text-align:center;position:relative;overflow:hidden}
        .blog-hero::before{content:'';position:absolute;width:340px;height:340px;border-radius:50%;background:rgba(255,255,255,.08);top:-120px;right:-90px}
        .blog-hero::after{content:'';position:absolute;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.06);bottom:-100px;left:-80px}
        .blog-hero > *{position:relative;z-index:1}
        .blog-hero h1{font-size:2.8rem;font-weight:900;margin-bottom:.5rem}
        .blog-hero p{opacity:.95;font-size:1.05rem}

        .blog-search-wrap{max-width:600px;margin:2rem auto 0;position:relative}
        .blog-search-wrap input{width:100%;padding:.95rem 1.5rem .95rem 3.5rem;border-radius:50px;border:none;font-family:'Cairo',sans-serif;font-size:1rem;box-shadow:0 8px 30px rgba(0,0,0,.18)}
        .blog-search-wrap button{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--brand-ink);cursor:pointer;font-size:1.1rem}

        .blog-filters{max-width:1200px;margin:0 auto;padding:2rem 2rem 0;display:flex;gap:.6rem;flex-wrap:wrap}
        .filter-btn{padding:.45rem 1.2rem;border:2px solid #d8e0ea;background:white;border-radius:50px;cursor:pointer;font-family:'Cairo',sans-serif;font-size:.85rem;font-weight:600;color:#566171;text-decoration:none;transition:all .2s;display:inline-flex;align-items:center;gap:.3rem}
        .filter-btn:hover{border-color:var(--brand-ink);color:var(--brand-ink)}
        .filter-btn.active{background:linear-gradient(135deg,var(--brand-ink),var(--brand-cyan));border-color:transparent;color:white}

        .blog-main{max-width:1200px;margin:0 auto;padding:2rem}
        .blog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:2rem;align-items:stretch}
        .blog-card{background:white;border-radius:16px;overflow:hidden;box-shadow:0 6px 24px rgba(31,41,55,.08);transition:all .28s ease;text-decoration:none;color:inherit;display:flex;flex-direction:column;height:100%;border:1px solid #e8edf3;will-change:transform}
        .blog-card:hover{transform:translateY(-6px);box-shadow:0 14px 40px rgba(63,77,96,.16);border-color:#d7e1ec}
        .bc-img{height:200px;overflow:hidden;position:relative;background:linear-gradient(135deg,var(--brand-ink),var(--brand-cyan));flex-shrink:0}
        .bc-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
        .blog-card:hover .bc-img img{transform:scale(1.06)}
        .bc-cat{position:absolute;top:10px;right:10px;background:rgba(15,236,193,.92);color:#1a2535;padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:700}
        .bc-no-img{width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.35);font-size:3rem}
        .bc-body{padding:1.3rem;display:flex;flex-direction:column;gap:.65rem;flex:1}
        .bc-body h2{font-size:1rem;font-weight:700;color:#222;margin-bottom:.5rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .bc-body p{color:var(--muted);font-size:.87rem;line-height:1.7;margin-bottom:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.95em}
        .bc-meta{display:flex;justify-content:space-between;align-items:center;font-size:.78rem;color:#9ca3af;flex-wrap:wrap;gap:.4rem;margin-top:auto}
        .bc-meta i{margin-left:.2rem}
        .bc-tags{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.6rem}
        .bc-tag{background:#eef2f6;color:var(--brand-ink);padding:.15rem .6rem;border-radius:50px;font-size:.72rem;font-weight:600;display:inline-flex;align-items:center}

        .blog-empty{text-align:center;padding:4rem 2rem;color:#9ca3af}
        .blog-empty i{font-size:3rem;display:block;margin-bottom:1rem}

        .pagination{display:flex;justify-content:center;gap:.5rem;margin-top:3rem;flex-wrap:wrap}
        .page-btn{padding:.5rem .9rem;border:2px solid #ddd;background:white;border-radius:8px;cursor:pointer;text-decoration:none;color:#555;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:600;transition:all .2s}
        .page-btn:hover,.page-btn.active{background:var(--brand-ink);border-color:var(--brand-ink);color:white}
        .page-btn.disabled{opacity:.4;pointer-events:none}

        footer{background:#222;color:white;text-align:center;padding:1.5rem;margin-top:4rem}
        footer a{color:var(--brand-mint);text-decoration:none;margin:0 .75rem}

        .btn-wa{position:fixed;bottom:2rem;left:2rem;width:55px;height:55px;background:#25d366;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.7rem;box-shadow:0 4px 12px rgba(37,211,102,.4);text-decoration:none;z-index:999;transition:all .3s}
        .btn-wa:hover{transform:scale(1.12) translateY(-3px)}

        @media(max-width:768px){
            .blog-hero h1{font-size:1.8rem}
            .blog-grid{grid-template-columns:1fr}
            .blog-main{padding:1.5rem 1rem}
            nav{padding:0 1rem}
            .mobile-menu{display:block}
            .nav-right{display:none;position:absolute;top:100%;right:0;left:0;background:linear-gradient(135deg,var(--brand-ink) 0%,var(--brand-cyan) 100%);padding:.7rem 1rem;gap:.2rem;flex-direction:column}
            .nav-right li{width:100%}
            .nav-right a{display:block;padding:.72rem .5rem;border-bottom:1px solid rgba(255,255,255,.12)}
            .bc-img{height:190px}
            .bc-body{padding:1rem}
            .bc-meta{font-size:.75rem}
        }
    </style>
</head>
<body>

<header>
    <nav>
        <a href="index.php" class="logo">
            <img src="<?= e($logo_url) ?>" alt="<?= e($site_name) ?>" loading="eager" decoding="async" fetchpriority="high">
            <?= e($site_name) ?>
        </a>
        <button class="mobile-menu" id="mobileMenuBtn" aria-label="فتح القائمة"><i class="fas fa-bars"></i></button>
        <ul class="nav-right" id="mobileNav">
            <li><a href="index.php#home">الرئيسية</a></li>
            <li><a href="index.php#about">عنا</a></li>
            <li><a href="index.php#services">الخدمات</a></li>
            <li><a href="index.php#our-works">أعمالنا</a></li>
            <li><a href="blog.php" style="color:#0FECC1">المدونة</a></li>
            <li><a href="index.php#contact">تواصل</a></li>
        </ul>
    </nav>
</header>

<div class="blog-hero">
    <h1><i class="fas fa-newspaper"></i> المدونة</h1>
    <p>مقالات ونصائح تقنية من فريق <?= e($site_name) ?></p>
    <form class="blog-search-wrap" method="GET" action="blog.php">
        <?php if ($catSlug): ?><input type="hidden" name="cat" value="<?= e($catSlug) ?>"><?php endif; ?>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="ابحث في المقالات…">
        <button type="submit" aria-label="بحث"><i class="fas fa-search"></i></button>
    </form>
</div>

<?php if ($bcats): ?>
<div class="blog-filters">
    <a href="blog.php<?= $search ? '?q='.urlencode($search) : '' ?>" class="filter-btn <?= !$catSlug && !$tagSlug ? 'active' : '' ?>">
        <i class="fas fa-th"></i> الكل
    </a>
    <?php foreach ($bcats as $c): ?>
    <a href="blog.php?cat=<?= urlencode($c['slug']) ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-btn <?= $catSlug === $c['slug'] ? 'active' : '' ?>">
        <?= e($c['name']) ?>
        <small style="opacity:.7">(<?= cat_count($categoryCounts, (int)$c['id']) ?>)</small>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="blog-main">
    <?php if ($search || $catSlug): ?>
    <div style="margin-bottom:1rem;color:#6b7280;font-size:.9rem">
        <?php if ($search): ?><span>نتائج البحث عن: <strong><?= e($search) ?></strong> — </span><?php endif; ?>
        <strong><?= $total ?></strong> مقال
        <a href="blog.php" style="color:#ef4444;margin-right:.5rem;font-size:.85rem"><i class="fas fa-times"></i> مسح</a>
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
                <?php if ($post['cat_name']): ?>
                    <span class="bc-cat"><?= e($post['cat_name']) ?></span>
                <?php endif; ?>
            </div>
            <div class="bc-body">
                <h2><?= e($post['title']) ?></h2>
                <p><?= e($post['excerpt']) ?></p>
                <div class="bc-meta">
                    <span><i class="fas fa-user"></i> <?= e($post['author']) ?></span>
                    <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
                    <span><i class="fas fa-eye"></i> <?= number_format((int)$post['views']) ?></span>
                </div>
                <?php
                $tags = array_filter(array_map('trim', explode(',', $post['tags'] ?? '')));
                if ($tags): ?>
                <div class="bc-tags">
                    <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
                        <span class="bc-tag"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php
        $base = 'blog.php?' . http_build_query(array_filter(['q'=>$search,'cat'=>$catSlug,'tag'=>$tagSlug]));
        $base .= $base !== 'blog.php?' ? '&' : '';
        ?>
        <a href="<?= $base ?>p=<?= max(1, $page_num - 1) ?>" class="page-btn <?= $page_num <= 1 ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-right"></i>
        </a>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="<?= $base ?>p=<?= $i ?>" class="page-btn <?= $i === $page_num ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a href="<?= $base ?>p=<?= min($total_pages, $page_num + 1) ?>" class="page-btn <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-left"></i>
        </a>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="blog-empty">
        <i class="fas fa-search"></i>
        <?= $search ? 'لا توجد نتائج للبحث عن "'.e($search).'"' : 'لا توجد مقالات منشورة بعد' ?>
        <?php if ($search || $catSlug): ?>
        <br><a href="blog.php" style="color:#3F4D60;font-weight:700;margin-top:.5rem;display:inline-block">← عرض كل المقالات</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<footer>
    <p>
        &copy; <?= date('Y') ?> <?= e($site_name) ?> &mdash;
        <a href="index.php">الرئيسية</a>
        <a href="page.php?slug=privacy">سياسة الخصوصية</a>
        <a href="page.php?slug=terms">شروط الاستخدام</a>
    </p>
</footer>

<a href="https://wa.me/<?= e($wa_number) ?>?text=<?= rawurlencode($wa_msg) ?>" class="btn-wa" target="_blank" rel="noopener" title="واتساب">
    <i class="fab fa-whatsapp"></i>
</a>

<script>
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileNav = document.getElementById('mobileNav');
if (mobileMenuBtn && mobileNav) {
    mobileMenuBtn.addEventListener('click', () => {
        mobileNav.classList.toggle('active');
    });
    document.addEventListener('click', (ev) => {
        if (!ev.target.closest('nav')) {
            mobileNav.classList.remove('active');
        }
    });
}
</script>

</body>
</html>
