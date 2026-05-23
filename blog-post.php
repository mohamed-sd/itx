<?php
require_once __DIR__ . '/config/db.php';
function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

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

// ── Load post ─────────────────────────────────────────────
$slug = trim($_GET['slug'] ?? '');
$post = null;
if ($slug) {
    try {
        $stmt = getDB()->prepare(
            "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
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
        "SELECT id,title,slug,thumbnail,excerpt,created_at,cat_name
         FROM (
             SELECT p.id,p.title,p.slug,p.thumbnail,p.excerpt,p.created_at,c.name AS cat_name
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
            "SELECT p.id,p.title,p.slug,p.thumbnail,p.excerpt,p.created_at,c.name AS cat_name
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

$meta_title = $post['meta_title'] ?: ($post['title'] . ' | ' . $site_name);
$meta_desc  = $post['meta_description'] ?: $post['excerpt'] ?: $gs('site_description', '');
$og_image   = site_media_url($post['thumbnail'] ?: $logo_path, 'logo.jpeg');
$tags_arr   = array_filter(array_map('trim', explode(',', $post['tags'] ?? '')));
$post_html  = sanitize_post_content((string)($post['content'] ?? ''));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($meta_desc) ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <?php if ($tags_arr): ?>
    <meta name="keywords" content="<?= e(implode(', ', $tags_arr)) ?>">
    <?php endif; ?>
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= e($meta_title) ?>">
    <meta property="og:description" content="<?= e($meta_desc) ?>">
    <meta property="og:image" content="<?= e($og_image) ?>">
    <meta property="article:published_time" content="<?= e($post['created_at']) ?>">
    <?php if ($tags_arr): foreach ($tags_arr as $tag): ?>
    <meta property="article:tag" content="<?= e($tag) ?>">
    <?php endforeach; endif; ?>
    <title><?= e($meta_title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root{
            --brand-ink:#3F4D60;
            --brand-cyan:#2FA8B9;
            --brand-mint:#0FECC1;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Cairo',sans-serif;background:#f6f8fb;color:#1f2937;line-height:1.6}
        html{scroll-behavior:smooth}

        header{background:linear-gradient(135deg,var(--brand-ink) 0%,var(--brand-cyan) 100%);color:white;padding:1rem 0;position:sticky;top:0;z-index:1000;box-shadow:0 2px 10px rgba(0,0,0,.1)}
        nav{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;padding:0 2rem;position:relative}
        .logo{font-size:1.6rem;font-weight:900;display:flex;align-items:center;gap:.5rem;text-decoration:none;color:white}
        .logo img{height:45px;width:45px;border-radius:50%;object-fit:cover;border:2px solid var(--brand-mint);background:white;padding:2px}
        .nav-right{display:flex;gap:1.5rem;list-style:none;align-items:center}
        .nav-right a{color:rgba(255,255,255,.85);text-decoration:none;font-weight:600;font-size:.9rem;transition:color .2s}
        .nav-right a:hover{color:var(--brand-mint)}
        .mobile-menu{display:none;background:none;border:none;color:white;font-size:1.35rem;cursor:pointer}
        .nav-right.active{display:flex!important}

        .breadcrumb{background:white;padding:.7rem 2rem;border-bottom:1px solid #eceff3}
        .breadcrumb-inner{max-width:1200px;margin:0 auto;font-size:.82rem;color:#9ca3af;display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
        .breadcrumb-inner a{color:var(--brand-ink);text-decoration:none}
        .breadcrumb-inner a:hover{text-decoration:underline}

        .post-hero{background:linear-gradient(135deg,var(--brand-ink) 0%,var(--brand-cyan) 100%);color:white;padding:3.5rem 2rem;text-align:center;position:relative;overflow:hidden}
        .post-hero::after{content:'';position:absolute;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.08);top:-120px;left:-80px}
        .post-hero > *{position:relative;z-index:1}
        .post-hero .cat-link{display:inline-block;background:rgba(15,236,193,.2);color:var(--brand-mint);padding:.3rem 1rem;border-radius:50px;font-size:.8rem;font-weight:700;text-decoration:none;margin-bottom:1rem;border:1px solid rgba(15,236,193,.4)}
        .post-hero .cat-link:hover{background:rgba(15,236,193,.35)}
        .post-hero h1{font-size:2.2rem;font-weight:900;line-height:1.4;max-width:840px;margin:0 auto .8rem}
        .post-meta-bar{display:flex;gap:1.5rem;justify-content:center;flex-wrap:wrap;opacity:.9;font-size:.88rem;margin-top:.5rem}
        .post-meta-bar span{display:flex;align-items:center;gap:.3rem}

        .post-layout{max-width:1200px;margin:0 auto;padding:3rem 2rem;display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:3rem;align-items:start}
        .post-thumbnail{width:100%;max-height:450px;object-fit:cover;border-radius:16px;margin-bottom:2rem;box-shadow:0 8px 30px rgba(0,0,0,.1)}

        .post-content{background:white;padding:2.2rem;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);border:1px solid #e8edf3}
        .post-content h2{font-size:1.35rem;color:var(--brand-ink);margin:1.8rem 0 .75rem;font-weight:700}
        .post-content h3{font-size:1.12rem;color:var(--brand-ink);margin:1.4rem 0 .5rem;font-weight:700}
        .post-content p{color:#4b5563;line-height:1.9;margin-bottom:1rem}
        .post-content ul,.post-content ol{padding-right:1.5rem;margin-bottom:1rem}
        .post-content li{color:#4b5563;line-height:1.85;margin-bottom:.4rem}
        .post-content strong{color:#111827}
        .post-content a{color:var(--brand-cyan);text-decoration:none}
        .post-content a:hover{text-decoration:underline}
        .post-content blockquote{border-right:4px solid var(--brand-mint);background:#f0fffe;padding:1rem 1.5rem;border-radius:0 8px 8px 0;margin:1rem 0;color:var(--brand-ink);font-style:italic}
        .post-content img{max-width:100%;border-radius:10px;margin:1rem 0;height:auto}
        .post-content pre,.post-content code{font-family:Consolas,monospace;background:#f3f4f6;border-radius:6px;padding:.2rem .5rem;font-size:.88rem}
        .post-content pre{padding:1rem;overflow-x:auto;display:block}

        .post-tags{margin-top:2rem;padding-top:1.5rem;border-top:1px solid #f0f0f0;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center}
        .post-tags strong{color:#555;font-size:.9rem}
        .tag-link{background:#eef2f6;color:var(--brand-ink);padding:.25rem .8rem;border-radius:50px;font-size:.8rem;font-weight:600;text-decoration:none}
        .tag-link:hover{background:var(--brand-ink);color:white}

        .post-share{margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid #f0f0f0;display:flex;gap:.75rem;align-items:center;flex-wrap:wrap}
        .post-share strong{color:#555;font-size:.9rem}
        .share-btn{padding:.45rem 1rem;border-radius:50px;text-decoration:none;font-size:.82rem;font-weight:700;display:inline-flex;align-items:center;gap:.3rem;transition:all .2s}
        .share-wa{background:#25d366;color:white}
        .share-wa:hover{background:#1fac57;color:white}
        .share-tw{background:#1da1f2;color:white}
        .share-tw:hover{background:#0d8fd8;color:white}
        .share-cp{background:#f0f4f8;color:var(--brand-ink);cursor:pointer;border:none;font-family:'Cairo',sans-serif}
        .share-cp:hover{background:var(--brand-ink);color:white}

        .sidebar-card{background:white;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.07);overflow:hidden;margin-bottom:1.5rem;border:1px solid #e8edf3}
        .sidebar-card-head{padding:.85rem 1.2rem;background:linear-gradient(135deg,var(--brand-ink),var(--brand-cyan));color:white;font-weight:700;font-size:.9rem}
        .sidebar-card-body{padding:1.2rem}

        .related-item{display:flex;gap:.8rem;align-items:center;padding:.65rem 0;border-bottom:1px solid #f5f5f5;text-decoration:none;color:inherit;transition:all .2s}
        .related-item:last-child{border-bottom:none}
        .related-item:hover h4{color:var(--brand-cyan)}
        .related-img{width:60px;height:45px;object-fit:cover;border-radius:7px;flex-shrink:0;background:#f0f4f8}
        .related-item h4{font-size:.82rem;color:#222;font-weight:700;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
        .related-item small{color:#9ca3af;font-size:.74rem}

        .author-box{display:flex;gap:1rem;align-items:center;margin-bottom:1rem}
        .author-icon{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,var(--brand-ink),var(--brand-mint));display:flex;align-items:center;justify-content:center;color:white;font-size:1.3rem;flex-shrink:0}
        .author-name{font-weight:700;color:#333}
        .author-role{font-size:.8rem;color:#9ca3af}

        footer{background:#222;color:white;text-align:center;padding:1.5rem;margin-top:3rem}
        footer a{color:var(--brand-mint);text-decoration:none;margin:0 .75rem}

        .btn-wa{position:fixed;bottom:2rem;left:2rem;width:55px;height:55px;background:#25d366;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.7rem;box-shadow:0 4px 12px rgba(37,211,102,.4);text-decoration:none;z-index:999;transition:all .3s}
        .btn-wa:hover{transform:scale(1.12)}

        @media(max-width:900px){
            .post-layout{grid-template-columns:1fr}
            .post-sidebar{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
        }
        @media(max-width:760px){
            nav{padding:0 1rem}
            .mobile-menu{display:block}
            .nav-right{display:none;position:absolute;top:100%;right:0;left:0;background:linear-gradient(135deg,var(--brand-ink) 0%,var(--brand-cyan) 100%);padding:.7rem 1rem;gap:.2rem;flex-direction:column}
            .nav-right li{width:100%}
            .nav-right a{display:block;padding:.72rem .5rem;border-bottom:1px solid rgba(255,255,255,.12)}
            .post-hero h1{font-size:1.5rem}
            .post-layout{padding:1.5rem 1rem}
            .post-content{padding:1.4rem}
            .post-sidebar{grid-template-columns:1fr}
            .post-thumbnail{max-height:260px;margin-bottom:1rem}
            .breadcrumb{padding:.6rem 1rem}
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
            <li><a href="index.php#services">الخدمات</a></li>
            <li><a href="index.php#our-works">أعمالنا</a></li>
            <li><a href="blog.php" style="color:#0FECC1">المدونة</a></li>
            <li><a href="index.php#contact">تواصل</a></li>
        </ul>
    </nav>
</header>

<div class="breadcrumb">
    <div class="breadcrumb-inner">
        <a href="index.php">الرئيسية</a>
        <i class="fas fa-chevron-left" style="font-size:.7rem"></i>
        <a href="blog.php">المدونة</a>
        <?php if ($post['cat_name']): ?>
        <i class="fas fa-chevron-left" style="font-size:.7rem"></i>
        <a href="blog.php?cat=<?= urlencode($post['cat_slug'] ?? '') ?>"><?= e($post['cat_name']) ?></a>
        <?php endif; ?>
        <i class="fas fa-chevron-left" style="font-size:.7rem"></i>
        <span style="color:#555"><?= e(mb_substr($post['title'], 0, 40)) ?>…</span>
    </div>
</div>

<div class="post-hero">
    <?php if ($post['cat_name']): ?>
    <a href="blog.php?cat=<?= urlencode($post['cat_slug'] ?? '') ?>" class="cat-link">
        <i class="fas fa-tag"></i> <?= e($post['cat_name']) ?>
    </a>
    <?php endif; ?>
    <h1><?= e($post['title']) ?></h1>
    <div class="post-meta-bar">
        <span><i class="fas fa-user"></i> <?= e($post['author']) ?></span>
        <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
        <span><i class="fas fa-eye"></i> <?= number_format((int)$post['views']) ?> مشاهدة</span>
        <?php if ($post['updated_at'] !== $post['created_at']): ?>
        <span><i class="fas fa-sync"></i> آخر تحديث: <?= date('d/m/Y', strtotime($post['updated_at'])) ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="post-layout">
    <div>
        <?php if ($post['thumbnail']): ?>
        <img src="<?= e(site_media_url($post['thumbnail'])) ?>" alt="<?= e($post['title']) ?>" class="post-thumbnail" loading="lazy" decoding="async" onerror="this.style.display='none'">
        <?php endif; ?>

        <div class="post-content">
            <?= $post_html ?>

            <?php if ($tags_arr): ?>
            <div class="post-tags">
                <strong><i class="fas fa-tags"></i> الوسوم:</strong>
                <?php foreach ($tags_arr as $tag): ?>
                    <a href="blog.php?tag=<?= urlencode($tag) ?>" class="tag-link"><?= e($tag) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="post-share">
                <strong><i class="fas fa-share-alt"></i> مشاركة:</strong>
                <?php
                $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
                $pageUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
                $shareTitle = urlencode($post['title']);
                $shareUrl   = urlencode($pageUrl);
                ?>
                <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" class="share-btn share-wa" target="_blank" rel="noopener">
                    <i class="fab fa-whatsapp"></i> واتساب
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?= $shareTitle ?>&url=<?= $shareUrl ?>" class="share-btn share-tw" target="_blank" rel="noopener">
                    <i class="fab fa-twitter"></i> تويتر
                </a>
                <button class="share-btn share-cp" onclick="copyLink()">
                    <i class="fas fa-link" id="copyIcon"></i> نسخ الرابط
                </button>
            </div>
        </div>
    </div>

    <aside class="post-sidebar">
        <div class="sidebar-card">
            <div class="sidebar-card-head"><i class="fas fa-user-circle"></i> عن الكاتب</div>
            <div class="sidebar-card-body">
                <div class="author-box">
                    <div class="author-icon"><i class="fas fa-user"></i></div>
                    <div>
                        <div class="author-name"><?= e($post['author']) ?></div>
                        <div class="author-role">فريق <?= e($site_name) ?></div>
                    </div>
                </div>
                <a href="blog.php" style="color:#2FA8B9;font-size:.85rem;text-decoration:none"><i class="fas fa-newspaper"></i> المزيد من المقالات</a>
            </div>
        </div>

        <?php if ($related): ?>
        <div class="sidebar-card">
            <div class="sidebar-card-head"><i class="fas fa-bookmark"></i> مقالات ذات صلة</div>
            <div class="sidebar-card-body" style="padding:.8rem 1.2rem">
                <?php foreach ($related as $rel): ?>
                <a href="blog-post.php?slug=<?= urlencode($rel['slug']) ?>" class="related-item">
                    <?php if ($rel['thumbnail']): ?>
                    <img src="<?= e(site_media_url($rel['thumbnail'])) ?>" class="related-img" alt="" loading="lazy" decoding="async">
                    <?php else: ?>
                    <div class="related-img" style="background:linear-gradient(135deg,#3F4D60,#2FA8B9);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.4)">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <?php endif; ?>
                    <div>
                        <h4><?= e($rel['title']) ?></h4>
                        <small><?= date('d/m/Y', strtotime($rel['created_at'])) ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
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
        if ($sidebarCats): ?>
        <div class="sidebar-card">
            <div class="sidebar-card-head"><i class="fas fa-folder"></i> الفئات</div>
            <div class="sidebar-card-body" style="padding:.5rem 1.2rem">
                <?php foreach ($sidebarCats as $sc): ?>
                <a href="blog.php?cat=<?= urlencode($sc['slug']) ?>" style="display:flex;justify-content:space-between;align-items:center;padding:.55rem 0;border-bottom:1px solid #f5f5f5;text-decoration:none;color:#333;font-size:.9rem;font-weight:600;transition:color .2s" onmouseover="this.style.color='#2FA8B9'" onmouseout="this.style.color='#333'">
                    <span><?= e($sc['name']) ?></span>
                    <span style="background:#eef2f6;color:#3F4D60;padding:.1rem .55rem;border-radius:50px;font-size:.75rem"><?= $sc['post_count'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </aside>
</div>

<footer>
    <p>
        &copy; <?= date('Y') ?> <?= e($site_name) ?> &mdash;
        <a href="index.php">الرئيسية</a>
        <a href="blog.php">المدونة</a>
        <a href="page.php?slug=privacy">سياسة الخصوصية</a>
    </p>
</footer>

<a href="https://wa.me/<?= e($wa_number) ?>?text=<?= rawurlencode($wa_msg) ?>" class="btn-wa" target="_blank" rel="noopener" title="واتساب">
    <i class="fab fa-whatsapp"></i>
</a>

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
