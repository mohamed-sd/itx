<?php
require_once __DIR__ . '/config/db.php';

function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

// ── Validate slug ────────────────────────────────────────
$slug = $_GET['slug'] ?? '';
if (!in_array($slug, ['privacy', 'terms'], true)) {
    header('Location: index.php');
    exit;
}

// ── Fetch page content ────────────────────────────────────
$page     = null;
$settings = [];
try {
    $pdo   = getDB();
    $stmt  = $pdo->prepare("SELECT * FROM content_pages WHERE slug = ?");
    $stmt->execute([$slug]);
    $page  = $stmt->fetch() ?: null;

    $rows     = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
    $settings = array_column($rows, 'setting_value', 'setting_key');
} catch (\Exception $ex) {}

$gs = fn($k, $d = '') => $settings[$k] ?? $d;

$site_name    = $gs('site_name',    'ITX');
$site_tagline = $gs('site_tagline', 'حلول برمجية وتركيب كاميرات أمان');
$logo_path    = $gs('site_logo',    'logo.jpeg');
$wa_number    = $gs('whatsapp_number', '966501234567');
$wa_msg       = $gs('whatsapp_msg',    'مرحباً، أود التواصل معكم');

// Page title fallbacks
$defaults = [
    'privacy' => ['title' => 'سياسة الخصوصية', 'content' => '<p>لم يتم إضافة محتوى سياسة الخصوصية بعد.</p>'],
    'terms'   => ['title' => 'شروط الاستخدام',  'content' => '<p>لم يتم إضافة محتوى شروط الاستخدام بعد.</p>'],
];

$page_title   = $page['title']   ?? $defaults[$slug]['title'];
$page_content = $page['content'] ?? $defaults[$slug]['content'];

// ── SEO ───────────────────────────────────────────────────
$logo_url      = site_media_url($logo_path, 'logo.jpeg');
$base_url      = rtrim($gs('site_url', get_base_url()), '/');
$canonical_url = $base_url . '/page.php?slug=' . rawurlencode($slug);
$logo_url_abs  = preg_match('#^https?://#', $logo_url) ? $logo_url : $base_url . '/' . ltrim($logo_url, '/');
$meta_desc     = $page['meta_description'] ?? strip_tags(mb_substr($page_content, 0, 160));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($meta_desc) ?>">
    <meta name="robots" content="noindex,follow">
    <link rel="canonical" href="<?= e($canonical_url) ?>">
    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= e($canonical_url) ?>">
    <meta property="og:site_name"   content="<?= e($site_name) ?>">
    <meta property="og:locale"      content="ar_SA">
    <meta property="og:title"       content="<?= e($page_title) ?> | <?= e($site_name) ?>">
    <meta property="og:description" content="<?= e($meta_desc) ?>">
    <meta property="og:image"       content="<?= e($logo_url_abs) ?>">
    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary">
    <meta name="twitter:title"       content="<?= e($page_title) ?> | <?= e($site_name) ?>">
    <meta name="twitter:description" content="<?= e($meta_desc) ?>">
    <meta name="twitter:image"       content="<?= e($logo_url_abs) ?>">
    <title><?= e($page_title) ?> | <?= e($site_name) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; color: #333; line-height: 1.6; }
        html { scroll-behavior: smooth; }

        /* ── Header ── */
        header { position: sticky; top: 0; z-index: 1000; background: rgba(40,55,74,.92); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-bottom: 1px solid rgba(255,255,255,.07); transition: background .4s ease, box-shadow .4s ease; }
        header.scrolled { background: rgba(26,37,53,.97); box-shadow: 0 4px 28px rgba(0,0,0,.28); }
        nav { max-width: 1200px; margin: 0 auto; position: relative; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; height: 68px; }
        .logo { display: flex; align-items: center; gap: .7rem; text-decoration: none; color: white; flex-shrink: 0; }
        .logo img { height: 46px; width: 46px; border-radius: 50%; object-fit: cover; border: 2px solid #0FECC1; background: white; padding: 2px; transition: transform .35s ease, box-shadow .35s ease; box-shadow: 0 0 0 0 rgba(15,236,193,0); }
        .logo:hover img { transform: rotate(8deg) scale(1.07); box-shadow: 0 0 0 6px rgba(15,236,193,.2); }
        .logo-text { line-height: 1.25; }
        .logo-text strong { display: block; font-size: 1.2rem; font-weight: 900; letter-spacing: .4px; }
        .logo-text small { display: block; font-size: .63rem; font-weight: 400; color: rgba(255,255,255,.5); margin-top: .1rem; }
        .nav-links { display: flex; list-style: none; gap: .2rem; align-items: center; margin: 0; padding: 0; }
        .nav-links a { color: rgba(255,255,255,.8); text-decoration: none; font-weight: 600; font-size: .88rem; padding: .45rem .8rem; border-radius: 8px; position: relative; transition: color .25s ease, background .25s ease; white-space: nowrap; }
        .nav-links a:hover { color: #0FECC1; background: rgba(15,236,193,.1); }
        .nav-links a.active { color: #0FECC1; background: rgba(15,236,193,.12); }
        .nav-links a.active::after { content: ''; position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; background: #0FECC1; border-radius: 50%; }
        .nav-cta { background: linear-gradient(135deg,#0FECC1 0%,#2FA8B9 100%) !important; color: #1a2535 !important; border-radius: 50px !important; padding: .45rem 1.2rem !important; font-weight: 700 !important; box-shadow: 0 4px 14px rgba(15,236,193,.3); margin-right: .5rem; }
        .nav-cta:hover { background: linear-gradient(135deg,#2af5d2 0%,#3bbfce 100%) !important; color: #1a2535 !important; transform: translateY(-2px) !important; box-shadow: 0 6px 22px rgba(15,236,193,.5) !important; }
        .nav-cta.active::after { display: none !important; }
        .mobile-menu { display: none; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2); border-radius: 8px; color: white; font-size: 1.1rem; cursor: pointer; flex-shrink: 0; transition: background .25s ease, border-color .25s ease; }
        .mobile-menu:hover { background: rgba(255,255,255,.18); }
        .mobile-menu.active { background: rgba(15,236,193,.2); border-color: rgba(15,236,193,.4); color: #0FECC1; }
        .nav-links.active { display: flex !important; }

        /* ── Page Content ── */
        .page-wrapper { max-width: 860px; margin: 0 auto; padding: 3rem 2rem 5rem; }

        .page-hero {
            background: linear-gradient(135deg,#3F4D60 0%,#2FA8B9 100%);
            color: white; padding: 3.5rem 2rem; text-align: center; margin-bottom: 0;
        }
        .page-hero h1 { font-size: 2.4rem; font-weight: 900; }
        .page-hero p  { opacity: .85; margin-top: .5rem; font-size: 1rem; }

        .page-body {
            background: white; border-radius: 0 0 16px 16px;
            box-shadow: 0 4px 30px rgba(0,0,0,.08); padding: 2.5rem 3rem;
            margin: 0 auto; max-width: 860px;
        }

        /* ── Typography for dynamic HTML content ── */
        .page-body h2 { font-size: 1.4rem; color: #3F4D60; margin: 2rem 0 .75rem; font-weight: 700; }
        .page-body h3 { font-size: 1.15rem; color: #3F4D60; margin: 1.5rem 0 .5rem; font-weight: 700; }
        .page-body p  { color: #555; line-height: 1.85; margin-bottom: 1rem; }
        .page-body ul, .page-body ol { padding-right: 1.5rem; margin-bottom: 1rem; }
        .page-body li { color: #555; line-height: 1.85; margin-bottom: .4rem; }
        .page-body strong { color: #333; }
        .page-body a  { color: #2FA8B9; text-decoration: none; }
        .page-body a:hover { text-decoration: underline; }

        .last-updated { font-size: .82rem; color: #bbb; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #f0f0f0; }

        /* ── Footer ── */
        footer { background: #222; color: white; text-align: center; padding: 1.5rem; font-size: .9rem; margin-top: 4rem; }
        footer a { color: #0FECC1; text-decoration: none; margin: 0 .75rem; }
        footer a:hover { opacity: .75; }

        /* ── WhatsApp ── */
        .btn-whatsapp { position: fixed; bottom: 2rem; left: 2rem; width: 56px; height: 56px; background: #25d366; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; box-shadow: 0 4px 12px rgba(37,211,102,.4); text-decoration: none; z-index: 999; transition: all .3s ease; }
        .btn-whatsapp:hover { transform: scale(1.12) translateY(-4px); }

        @media(max-width:768px){
            .page-hero h1   { font-size: 1.7rem; }
            .page-body      { padding: 1.5rem; border-radius: 0 0 12px 12px; }
            nav { flex-wrap: nowrap; height: 60px; padding: 0 1rem; }
            .logo-text strong { font-size: 1rem; }
            .logo-text small  { display: none; }
            .mobile-menu { display: flex; }
            .nav-links { position: absolute; top: 60px; right: 0; left: 0; background: rgba(18,27,40,.98); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); flex-direction: column; gap: .3rem; display: none !important; padding: 1rem 1.25rem 1.5rem; list-style: none; border-bottom: 1px solid rgba(255,255,255,.08); box-shadow: 0 8px 28px rgba(0,0,0,.35); }
            .nav-links li { width: 100%; }
            .nav-links a { display: flex; align-items: center; gap: .6rem; padding: .85rem 1rem; border-radius: 10px; font-size: .93rem; color: rgba(255,255,255,.82); }
            .nav-links a.active::after { display: none; }
            .nav-links a:hover, .nav-links a.active { background: rgba(15,236,193,.13); color: #0FECC1; }
            .nav-cta { justify-content: center; margin-right: 0; margin-top: .3rem; box-shadow: none; }
            .nav-links.active { display: flex !important; }
        }
    </style>
</head>
<body>

    <header id="site-header">
        <nav>
            <a href="index.php" class="logo">
                <img src="<?= e($logo_url) ?>" alt="<?= e($site_name) ?> Logo" loading="eager" decoding="async">
                <div class="logo-text">
                    <strong><?= e($site_name) ?></strong>
                    <small><?= e($site_tagline) ?></small>
                </div>
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php#home"><i class="fas fa-house"></i> الرئيسية</a></li>
                <li><a href="index.php#about"><i class="fas fa-circle-info"></i> من نحن</a></li>
                <li><a href="index.php#services"><i class="fas fa-gears"></i> الخدمات</a></li>
                <li><a href="index.php#our-works"><i class="fas fa-briefcase"></i> أعمالنا</a></li>
                <li><a href="blog.php"><i class="fas fa-newspaper"></i> المدونة</a></li>
                <li><a href="index.php#contact" class="nav-cta"><i class="fas fa-envelope"></i> تواصل معنا</a></li>
            </ul>
            <button class="mobile-menu" id="mobileMenu" aria-label="فتح القائمة" aria-expanded="false">
                <i class="fas fa-bars" id="menuIcon"></i>
            </button>
        </nav>
    </header>

    <div class="page-hero">
        <h1><?= e($page_title) ?></h1>
        <p><?= e($site_name) ?> | <?= e($site_tagline) ?></p>
    </div>

    <div style="max-width:860px;margin:0 auto;padding:0 2rem;">
        <div class="page-body">
            <?= $page_content ?>
            <p class="last-updated"><i class="fas fa-clock"></i> آخر تحديث: <?= date('d/m/Y') ?></p>
        </div>
    </div>

    <footer>
        <p>
            &copy; <?= date('Y') ?> <?= e($site_name) ?> &mdash;
            <a href="page.php?slug=privacy">سياسة الخصوصية</a>
            <a href="page.php?slug=terms">شروط الاستخدام</a>
            <a href="index.php">الرئيسية</a>
        </p>
    </footer>

    <a href="https://wa.me/<?= e($wa_number) ?>?text=<?= rawurlencode($wa_msg) ?>"
       class="btn-whatsapp" title="واتساب" target="_blank" rel="noopener">
        <i class="fab fa-whatsapp"></i>
    </a>

    <script>
        // ── Navbar ────────────────────────────────────────────────
        const siteHeader = document.getElementById('site-header');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon   = document.getElementById('menuIcon');
        const navLinks   = document.getElementById('navLinks');
        window.addEventListener('scroll', () => {
            siteHeader.classList.toggle('scrolled', window.pageYOffset > 60);
        }, { passive: true });
        function closeNav() {
            navLinks.classList.remove('active');
            mobileMenu.classList.remove('active');
            mobileMenu.setAttribute('aria-expanded', 'false');
            menuIcon.className = 'fas fa-bars';
            document.body.style.overflow = '';
        }
        mobileMenu.addEventListener('click', () => {
            const isOpen = navLinks.classList.toggle('active');
            mobileMenu.classList.toggle('active', isOpen);
            mobileMenu.setAttribute('aria-expanded', String(isOpen));
            menuIcon.className = isOpen ? 'fas fa-xmark' : 'fas fa-bars';
            if (window.innerWidth <= 768) document.body.style.overflow = isOpen ? 'hidden' : '';
        });
        document.querySelectorAll('.nav-links a').forEach(link =>
            link.addEventListener('click', closeNav)
        );
        document.addEventListener('click', e => {
            if (!e.target.closest('nav')) closeNav();
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) { navLinks.style.display = ''; closeNav(); }
        });
    </script>

</body>
</html>
