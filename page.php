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
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | <?= e($site_name) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; color: #333; line-height: 1.6; }
        html { scroll-behavior: smooth; }

        /* ── Header ── */
        header { background: linear-gradient(135deg,#3F4D60 0%,#2FA8B9 100%); color: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
        nav    { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; }
        .logo  { font-size: 1.8rem; font-weight: 900; display: flex; align-items: center; gap: .5rem; text-decoration: none; color: white; }
        .logo img { height: 50px; width: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #0FECC1; background: white; padding: 2px; }
        .back-link { color: rgba(255,255,255,.85); text-decoration: none; font-weight: 600; font-size: .95rem; display: flex; align-items: center; gap: .4rem; transition: color .2s; }
        .back-link:hover { color: #0FECC1; }

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
            nav             { padding: 0 1rem; }
        }
    </style>
</head>
<body>

    <header>
        <nav>
            <a href="index.php" class="logo">
                <img src="<?= e($logo_path) ?>" alt="<?= e($site_name) ?>">
                <span><?= e($site_name) ?></span>
            </a>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-right"></i> الرئيسية
            </a>
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

</body>
</html>
