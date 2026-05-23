<?php
// ── Bootstrap ─────────────────────────────────────────────
require_once __DIR__ . '/config/db.php';

function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

// ── DB connection ─────────────────────────────────────────
$pdo   = null;
$db_ok = false;
try { $pdo = getDB(); $db_ok = true; } catch (\Exception $ex) {}

// ── Fetch settings ────────────────────────────────────────
$settings = [];
if ($db_ok) {
    try {
        $rows     = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
        $settings = array_column($rows, 'setting_value', 'setting_key');
    } catch (\Exception $ex) {}
}
$gs = fn($k, $d = '') => $settings[$k] ?? $d;

// ── Fetch all sections ────────────────────────────────────
$hero = $about = $contact = [];
$services = $statistics = $testimonials = $socials = [];
if ($db_ok) {
    try { $hero         = $pdo->query("SELECT * FROM hero_section WHERE id=1")->fetch() ?: [];             } catch (\Exception $ex) {}
    try { $about        = $pdo->query("SELECT * FROM about_section WHERE id=1")->fetch() ?: [];            } catch (\Exception $ex) {}
    try { $services     = $pdo->query("SELECT * FROM services WHERE status='active' ORDER BY sort_order")->fetchAll();     } catch (\Exception $ex) {}
    try { $statistics   = $pdo->query("SELECT * FROM statistics WHERE status='active' ORDER BY sort_order")->fetchAll();   } catch (\Exception $ex) {}
    try { $testimonials = $pdo->query("SELECT * FROM testimonials WHERE status='active' ORDER BY sort_order")->fetchAll(); } catch (\Exception $ex) {}
    try { $contact      = $pdo->query("SELECT * FROM contact_info WHERE id=1")->fetch() ?: [];             } catch (\Exception $ex) {}
    try { $socials      = $pdo->query("SELECT * FROM social_media WHERE status='active' ORDER BY sort_order")->fetchAll(); } catch (\Exception $ex) {}
    try { $blog_preview = $pdo->query("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
                                        FROM blog_posts p
                                        LEFT JOIN blog_categories c ON c.id=p.category_id
                                        WHERE p.status='published'
                                        ORDER BY p.created_at DESC LIMIT 6")->fetchAll(); } catch (\Exception $ex) {}
}
$blog_preview = $blog_preview ?? [];

// ── Computed values ───────────────────────────────────────
$site_name    = $gs('site_name',        'ITX');
$site_tagline = $gs('site_tagline',     'حلول برمجية وتركيب كاميرات أمان');
$site_desc    = $gs('site_description', 'شركة ' . $site_name . ' للحلول الرقمية - متخصصون في تطوير المواقع والتطبيقات وتركيب أنظمة كاميرات الأمان');
$site_kws     = $gs('site_keywords',    'تطوير المواقع, تطبيقات الجوال, كاميرات المراقبة, أنظمة الأمان');
$logo_path    = $gs('site_logo',        'logo.jpeg');
$logo_url     = site_media_url($logo_path, 'logo.jpeg');
$wa_number    = $gs('whatsapp_number',  '') ?: ($contact['whatsapp'] ?? '966501234567');
$wa_msg       = $gs('whatsapp_msg',     'مرحباً، أود التواصل معكم');
$footer_text  = $gs('footer_text',      'جميع الحقوق محفوظة | شركة ' . $site_name . ' للحلول الرقمية');

$h_title = $hero['title']     ?? ('شركة ' . $site_name . ' للحلول الرقمية');
$h_sub   = $hero['subtitle']  ?? 'متخصصون في تطوير المواقع والتطبيقات وتركيب أنظمة كاميرات الأمان';
$h_note  = $hero['note']      ?? 'نقدم حلولاً تقنية متكاملة لأمان ورقمنة أعمالك';
$h_btn1t = $hero['btn1_text'] ?? 'عرض المشاريع';
$h_btn1l = $hero['btn1_link'] ?? '#our-works';
$h_btn2t = $hero['btn2_text'] ?? 'تواصل معنا';
$h_btn2l = $hero['btn2_link'] ?? '#contact';

$ab_heading = $about['heading'] ?? ('مرحباً بك في ' . $site_name);
$ab_content = $about['content'] ?? '';
$ab_image   = $about['image']   ?? 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500&h=400&fit=crop';
$ab_image_url = site_media_url($ab_image);
$ab_skills  = array_filter(array_map('trim', explode(',', $about['skills'] ?? '')));

$ct_phone   = $contact['phone']     ?? '';
$ct_email   = $contact['email']     ?? '';
$ct_address = $contact['address']   ?? '';
$ct_wa      = $contact['whatsapp']  ?? $wa_number;
$ct_map     = $contact['map_embed'] ?? '';

// ── Helpers ───────────────────────────────────────────────
function nl2p(string $text): string {
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    return implode('', array_map(fn($l) => '<p>' . e($l) . '</p>', $lines));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($site_desc) ?>">
    <meta name="keywords"    content="<?= e($site_kws) ?>">
    <meta name="author"      content="<?= e($site_name) ?>">
    <meta property="og:title"       content="<?= e($site_name) ?> | <?= e($site_tagline) ?>">
    <meta property="og:description" content="<?= e($site_desc) ?>">
    <meta property="og:image"       content="<?= e($logo_url) ?>">
    <title><?= e($site_name) ?> | <?= e($site_tagline) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; color: #333; line-height: 1.6; }
        html { scroll-behavior: smooth; }

        /* ── Header ── */
        header {
            background: linear-gradient(135deg, #3F4D60 0%, #2FA8B9 100%);
            color: white; padding: 1rem 0; position: sticky; top: 0;
            z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }
        nav {
            max-width: 1200px; margin: 0 auto;
            display: flex; justify-content: space-between; align-items: center; padding: 0 2rem;
        }
        .logo { font-size: 1.8rem; font-weight: 900; display: flex; align-items: center; gap: .5rem; }
        .logo img {
            height: 55px; width: 55px; border-radius: 50%; object-fit: cover;
            box-shadow: 0 4px 15px rgba(15,236,193,.3); border: 2px solid #0FECC1;
            transition: all .3s ease; padding: 2px; background: white;
        }
        .logo img:hover { transform: scale(1.1) rotate(5deg); box-shadow: 0 6px 20px rgba(15,236,193,.5); }
        .nav-links { display: flex; list-style: none; gap: 2rem; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-weight: 600; transition: all .3s ease; position: relative; }
        .nav-links a:hover { color: #0FECC1; transform: translateY(-2px); }
        .nav-links a::after { content:''; position: absolute; bottom: -5px; right: 0; width: 0; height: 2px; background: #0FECC1; transition: width .3s ease; }
        .nav-links a:hover::after { width: 100%; }
        .mobile-menu { display: none; background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; z-index: 1001; }
        .nav-links.active { display: flex !important; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, #3F4D60 0%, #2FA8B9 100%);
            color: white; padding: 6rem 2rem; text-align: center;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .hero::before { content:''; position: absolute; width: 400px; height: 400px; background: rgba(255,255,255,.1); border-radius: 50%; top: -100px; right: -100px; animation: float 6s ease-in-out infinite; }
        .hero::after  { content:''; position: absolute; width: 300px; height: 300px; background: rgba(255,255,255,.05); border-radius: 50%; bottom: -50px; left: -50px; animation: float 8s ease-in-out infinite reverse; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(30px)} }
        .hero-content { max-width: 800px; z-index: 1; animation: slideInUp .8s ease-out; }
        @keyframes slideInUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        .hero h1 { font-size: 3.5rem; margin-bottom: 1rem; font-weight: 900; text-shadow: 2px 2px 4px rgba(0,0,0,.2); }
        .hero p  { font-size: 1.3rem; margin-bottom: 2rem; opacity: .95; line-height: 1.8; }
        .cta-buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

        /* ── Buttons ── */
        .btn {
            padding: 1rem 2rem; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all .3s ease; text-decoration: none;
            display: inline-flex; align-items: center; gap: .5rem; font-family: 'Cairo', sans-serif;
        }
        .btn-primary  { background: linear-gradient(135deg,#0FECC1 0%,#2FA8B9 100%); color: white; box-shadow: 0 4px 15px rgba(15,236,193,.3); }
        .btn-primary:hover  { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(15,236,193,.4); }
        .btn-secondary{ background: rgba(255,255,255,.2); color: white; border: 2px solid white; }
        .btn-secondary:hover{ background: white; color: #667eea; transform: translateY(-3px); }
        .btn-whatsapp {
            position: fixed; bottom: 2rem; left: 2rem; width: 60px; height: 60px;
            background: #25d366; color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 1.8rem;
            box-shadow: 0 4px 12px rgba(37,211,102,.4); transition: all .3s ease;
            text-decoration: none; z-index: 999;
        }
        .btn-whatsapp:hover { transform: scale(1.15) translateY(-5px); box-shadow: 0 6px 20px rgba(37,211,102,.6); }

        /* ── About ── */
        .about { max-width: 1200px; margin: 0 auto; padding: 6rem 2rem; }
        .section-title { text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #333; position: relative; display: inline-block; width: 100%; }
        .section-title::after { content:''; position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 100px; height: 4px; background: linear-gradient(135deg,#3F4D60 0%,#0FECC1 100%); border-radius: 2px; }
        .about-content { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; margin-top: 2rem; }
        .about-text h3 { font-size: 1.8rem; margin-bottom: 1rem; color: #3F4D60; }
        .about-text p  { margin-bottom: 1rem; line-height: 1.8; color: #666; }
        .skills-list   { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 2rem; }
        .skill-tag     { background: linear-gradient(135deg,#3F4D60 0%,#0FECC1 100%); color: white; padding: .5rem 1.5rem; border-radius: 50px; font-weight: 600; font-size: .95rem; }
        .about-image   { position: relative; height: 400px; background: linear-gradient(135deg,#3F4D60 0%,#0FECC1 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(63,77,96,.2); }
        .about-image img { width: 100%; height: 100%; object-fit: cover; }
        .about-image::before { content:''; position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(63,77,96,.1); z-index:1; }

        /* ── Services ── */
        .services { background: #f8f9fa; padding: 6rem 2rem; }
        .services-container { max-width: 1200px; margin: 0 auto; }
        .services-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap: 2rem; margin-top: 2rem; }
        .service-card { background: white; padding: 2rem; border-radius: 15px; text-align: center; transition: all .3s ease; box-shadow: 0 5px 15px rgba(0,0,0,.08); border: 2px solid transparent; }
        .service-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(63,77,96,.2); border-color: #3F4D60; }
        .service-icon { font-size: 3rem; color: #3F4D60; margin-bottom: 1rem; transition: transform .3s ease; }
        .service-card:hover .service-icon { transform: scale(1.2) rotate(10deg); }
        .service-card h3 { font-size: 1.3rem; margin-bottom: 1rem; color: #333; }
        .service-card p  { color: #666; line-height: 1.6; }

        /* ── Stats ── */
        .stats { background: linear-gradient(135deg,#3F4D60 0%,#2FA8B9 100%); color: white; padding: 4rem 2rem; }
        .stats-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 2rem; text-align: center; }
        .stat-item h4 { font-size: 2.5rem; margin-bottom: .5rem; }
        .stat-item p  { font-size: 1.1rem; opacity: .9; }

        /* ── Testimonials ── */
        .testimonials { background: #f8f9fa; padding: 6rem 2rem; }
        .testimonials-container { max-width: 1200px; margin: 0 auto; }
        .testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(300px,1fr)); gap: 2rem; margin-top: 2rem; }
        .testimonial-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,.08); text-align: center; transition: all .3s ease; border-top: 4px solid #0FECC1; }
        .testimonial-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0,252,193,.2); }
        .testimonial-stars  { color: #ffc107; font-size: 1.2rem; margin-bottom: 1rem; letter-spacing: 2px; }
        .testimonial-text   { color: #666; margin-bottom: 1.5rem; font-style: italic; line-height: 1.8; }
        .testimonial-author { font-weight: 600; color: #3F4D60; margin-bottom: .3rem; }
        .testimonial-role   { color: #999; font-size: .9rem; }

        /* ── Contact ── */
        .contact { background: #f8f9fa; padding: 6rem 2rem; }
        .contact-container { max-width: 1200px; margin: 0 auto; }
        .contact-content { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-top: 2rem; }
        .contact-info    { display: flex; flex-direction: column; gap: 2rem; }
        .contact-item    { display: flex; gap: 1.5rem; align-items: flex-start; }
        .contact-icon    { width: 50px; height: 50px; background: linear-gradient(135deg,#3F4D60 0%,#0FECC1 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; flex-shrink: 0; }
        .contact-item h4 { color: #333; margin-bottom: .3rem; }
        .contact-item p  { color: #666; }
        .contact-item a  { color: #3F4D60; text-decoration: none; font-weight: 600; transition: color .3s ease; }
        .contact-item a:hover { color: #0FECC1; }
        .contact-form  { display: flex; flex-direction: column; gap: 1rem; }
        .form-group    { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: .5rem; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea { padding: .8rem; border: 2px solid #ddd; border-radius: 8px; font-family: 'Cairo', sans-serif; font-size: 1rem; transition: border-color .3s ease; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #3F4D60; box-shadow: 0 0 0 3px rgba(63,77,96,.1); }
        .form-group textarea { resize: vertical; min-height: 150px; }
        .submit-btn { padding: 1rem; background: linear-gradient(135deg,#3F4D60 0%,#0FECC1 100%); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all .3s ease; font-family: 'Cairo', sans-serif; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(63,77,96,.3); }
        .form-message { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: none; text-align: center; font-weight: 600; }
        .form-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: block; }
        .form-message.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; display: block; }
        .submit-btn.loading   { opacity: .7; pointer-events: none; }
        .submit-btn.loading::after { content:''; display: inline-block; width: 1rem; height: 1rem; border: 2px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: white; animation: spin .8s linear infinite; margin-left: .5rem; }
        @keyframes spin { to{transform:rotate(360deg)} }
        .contact-map { grid-column: 1 / -1; border-radius: 12px; overflow: hidden; margin-top: 1rem; }
        .contact-map iframe { width: 100%; height: 350px; border: 0; display: block; }

        /* ── Footer ── */
        footer { background: #222; color: white; text-align: center; padding: 2rem; }
        .social-links { display: flex; justify-content: center; gap: 1.5rem; margin-bottom: 1rem; }
        .social-links a { width: 45px; height: 45px; background: linear-gradient(135deg,#3F4D60 0%,#0FECC1 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all .3s ease; }
        .social-links a:hover { transform: translateY(-5px) scale(1.1); }
        .footer-links a { color: #0FECC1; text-decoration: none; margin: 0 1rem; font-size: .9rem; transition: opacity .2s; }
        .footer-links a:hover { opacity: .75; }

        /* ── Blog Preview ── */
        .blog-preview { padding: 6rem 2rem; background: #f8f9fa; }
        .blog-container { max-width: 1200px; margin: 0 auto; }
        .blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px,1fr)); gap: 2rem; margin-top: 2rem; }
        .blog-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.07); transition: all .3s ease; text-decoration: none; color: inherit; display: block; border: 1px solid #f0f0f0; }
        .blog-card:hover { transform: translateY(-6px); box-shadow: 0 14px 40px rgba(63,77,96,.15); }
        .blog-card-img { height: 200px; overflow: hidden; position: relative; background: linear-gradient(135deg,#3F4D60,#2FA8B9); }
        .blog-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease; }
        .blog-card:hover .blog-card-img img { transform: scale(1.06); }
        .blog-cat-badge { position: absolute; top: 10px; right: 10px; background: rgba(15,236,193,.92); color: #1a2535; padding: 3px 10px; border-radius: 50px; font-size: .72rem; font-weight: 700; }
        .blog-no-img { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,.4); font-size: 2.5rem; }
        .blog-card-body { padding: 1.3rem; }
        .blog-card-body h3 { font-size: 1rem; font-weight: 700; color: #222; margin-bottom: .5rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .blog-card-body p  { color: #777; font-size: .87rem; line-height: 1.65; margin-bottom: .8rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .blog-card-meta { display: flex; justify-content: space-between; align-items: center; font-size: .8rem; color: #bbb; }
        .blog-more { text-align: center; margin-top: 2.5rem; }
        @media(max-width:768px){ .blog-grid { grid-template-columns: 1fr; } .blog-preview { padding: 4rem 1rem; } }

        /* ── Back to top ── */
        .back-to-top { position: fixed; bottom: 2rem; right: 2rem; width: 50px; height: 50px; background: linear-gradient(135deg,#3F4D60 0%,#0FECC1 100%); color: white; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 1.5rem; cursor: pointer; z-index: 998; box-shadow: 0 4px 12px rgba(63,77,96,.3); transition: all .3s ease; border: none; }
        .back-to-top.show { display: flex; }
        .back-to-top:hover { transform: translateY(-5px); box-shadow: 0 6px 20px rgba(0,252,193,.4); }

        /* ============================
           Our Works Section
           ============================ */
        .our-works { padding: 6rem 2rem; background: #fff; }
        .works-container { max-width: 1200px; margin: 0 auto; }
        .section-subtitle { text-align: center; color: #888; margin-top: -1.8rem; margin-bottom: 3rem; font-size: 1.05rem; }
        .works-categories { display: flex; gap: .7rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem; }
        .cat-btn { padding: .55rem 1.4rem; border: 2px solid #ddd; background: white; border-radius: 50px; cursor: pointer; font-family: 'Cairo', sans-serif; font-size: .92rem; font-weight: 600; color: #666; transition: all .25s ease; display: inline-flex; align-items: center; gap: .4rem; }
        .cat-btn:hover  { border-color: #3F4D60; color: #3F4D60; }
        .cat-btn.active { background: linear-gradient(135deg,#3F4D60 0%,#2FA8B9 100%); border-color: transparent; color: white; box-shadow: 0 4px 14px rgba(63,77,96,.3); }
        .works-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(320px,1fr)); gap: 2rem; min-height: 200px; }
        .work-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); transition: all .3s ease; cursor: pointer; border: 1px solid #f0f0f0; }
        .work-card:hover { transform: translateY(-8px); box-shadow: 0 14px 40px rgba(63,77,96,.18); }
        .work-card-image { position: relative; height: 210px; overflow: hidden; }
        .work-card-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s ease; }
        .work-card:hover .work-card-image img { transform: scale(1.08); }
        .work-badges { position: absolute; top: 10px; right: 10px; display: flex; flex-direction: column; align-items: flex-end; gap: 5px; }
        .badge-category    { background: rgba(30,40,55,.82); color: white; padding: 3px 10px; border-radius: 50px; font-size: .75rem; font-weight: 600; backdrop-filter: blur(4px); }
        .badge-programming { background: linear-gradient(135deg,#0FECC1,#2FA8B9); color: white; padding: 3px 10px; border-radius: 50px; font-size: .75rem; font-weight: 600; }
        .work-card-body    { padding: 1.4rem; }
        .work-card-body h3 { font-size: 1.05rem; color: #222; margin-bottom: .45rem; font-weight: 700; }
        .work-card-body p  { color: #777; font-size: .88rem; line-height: 1.65; margin-bottom: 1rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .work-card-footer  { display: flex; justify-content: space-between; align-items: center; }
        .work-meta         { font-size: .82rem; color: #aaa; }
        .btn-view-work     { padding: .45rem 1.1rem; background: linear-gradient(135deg,#3F4D60 0%,#2FA8B9 100%); color: white; border: none; border-radius: 50px; font-family: 'Cairo', sans-serif; font-size: .85rem; font-weight: 600; cursor: pointer; transition: all .25s ease; display: inline-flex; align-items: center; gap: .35rem; }
        .btn-view-work:hover { box-shadow: 0 4px 12px rgba(63,77,96,.35); }
        .works-loading, .works-empty { grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; color: #aaa; }
        .works-loading i { font-size: 2rem; color: #3F4D60; display: block; margin-bottom: 1rem; animation: spin 1s linear infinite; }
        .works-empty i   { font-size: 3rem; display: block; margin-bottom: 1rem; }

        /* ── Project Modal ── */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.72); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 1rem; opacity: 0; visibility: hidden; transition: opacity .3s ease, visibility .3s ease; backdrop-filter: blur(5px); }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-box { background: white; border-radius: 20px; width: 100%; max-width: 960px; max-height: 92vh; overflow-y: auto; transform: translateY(24px) scale(.97); transition: transform .3s ease; scrollbar-width: thin; }
        .modal-overlay.active .modal-box { transform: translateY(0) scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 1.4rem 2rem; border-bottom: 1px solid #eee; position: sticky; top: 0; background: white; z-index: 10; border-radius: 20px 20px 0 0; }
        .modal-header-info h2 { font-size: 1.3rem; color: #222; margin-bottom: .45rem; }
        .modal-header-badges  { display: flex; gap: .45rem; flex-wrap: wrap; }
        .modal-close { width: 34px; height: 34px; background: #f2f2f2; border: none; border-radius: 50%; cursor: pointer; font-size: 1rem; color: #555; flex-shrink: 0; transition: all .2s; display: flex; align-items: center; justify-content: center; margin-right: 1rem; }
        .modal-close:hover { background: #ff4757; color: white; }
        .modal-body    { display: grid; grid-template-columns: 1fr 1fr; }
        .modal-media   { background: #0d0d0d; border-radius: 0 0 0 20px; overflow: hidden; display: flex; flex-direction: column; min-height: 380px; }
        .modal-media-main { flex: 1; position: relative; display: flex; align-items: center; justify-content: center; min-height: 280px; background: #000; }
        .modal-media-main img    { width: 100%; max-height: 340px; object-fit: contain; }
        .modal-media-main iframe { width: 100%; height: 300px; border: none; }
        .media-nav-btn  { position: absolute; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; background: rgba(255,255,255,.15); border: none; border-radius: 50%; color: white; cursor: pointer; font-size: .9rem; display: flex; align-items: center; justify-content: center; transition: background .2s; z-index: 5; }
        .media-nav-btn:hover { background: rgba(255,255,255,.3); }
        .media-nav-prev { left: 10px; }
        .media-nav-next { right: 10px; }
        .modal-media-caption { padding: 6px 12px; background: rgba(0,0,0,.55); color: rgba(255,255,255,.75); font-size: .82rem; text-align: center; }
        .modal-media-thumbs  { display: flex; gap: 6px; padding: 8px 10px; background: rgba(0,0,0,.45); overflow-x: auto; scrollbar-width: thin; }
        .media-thumb { width: 68px; height: 48px; border-radius: 6px; overflow: hidden; flex-shrink: 0; cursor: pointer; border: 2px solid transparent; position: relative; opacity: .6; transition: all .2s; }
        .media-thumb:hover { opacity: .9; }
        .media-thumb.active { border-color: #0FECC1; opacity: 1; }
        .media-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .media-thumb-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; background: rgba(0,0,0,.35); }
        .no-media-msg { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: rgba(255,255,255,.35); gap: .75rem; font-size: .9rem; }
        .no-media-msg i { font-size: 2.5rem; }
        .modal-details { padding: 2rem; display: flex; flex-direction: column; gap: 1.4rem; overflow-y: auto; }
        .modal-description { color: #555; line-height: 1.85; font-size: .93rem; }
        .modal-info-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .modal-info-item label { display: block; font-size: .75rem; color: #bbb; margin-bottom: .2rem; letter-spacing: .4px; text-transform: uppercase; }
        .modal-info-item span  { font-weight: 700; color: #333; font-size: .93rem; }
        .modal-tech-label  { font-size: .8rem; color: #aaa; margin-bottom: .5rem; }
        .modal-technologies{ display: flex; flex-wrap: wrap; gap: .45rem; }
        .tech-tag { background: #eef2f6; color: #3F4D60; padding: .28rem .8rem; border-radius: 50px; font-size: .8rem; font-weight: 600; }
        .modal-actions { display: flex; gap: .7rem; flex-wrap: wrap; padding-top: .75rem; border-top: 1px solid #f0f0f0; }
        .btn-demo { padding: .7rem 1.5rem; background: linear-gradient(135deg,#0FECC1 0%,#2FA8B9 100%); color: white; border: none; border-radius: 50px; font-family: 'Cairo', sans-serif; font-size: .95rem; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: .5rem; transition: all .3s ease; box-shadow: 0 4px 14px rgba(15,236,193,.3); }
        .btn-demo:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(15,236,193,.45); color: white; text-decoration: none; }
        .skel { background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius: 8px; }
        @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

        /* ── Responsive ── */
        @media(max-width:768px){
            nav { flex-wrap: wrap; }
            .logo img { height: 50px; width: 50px; }
            .nav-links { position: absolute; top: 100%; right: 0; left: 0; background: linear-gradient(135deg,#3F4D60 0%,#2FA8B9 100%); flex-direction: column; gap: 0; display: none !important; padding: 1rem 0; list-style: none; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
            .nav-links li { width: 100%; }
            .nav-links a  { display: block; padding: 1rem 2rem; border-bottom: 1px solid rgba(255,255,255,.1); }
            .nav-links a:hover { background: rgba(255,255,255,.1); color: #0FECC1; transform: none; padding-right: 2.5rem; }
            .nav-links a::after { display: none; }
            .mobile-menu { display: block; transition: transform .3s ease; }
            .mobile-menu.active { transform: rotate(90deg); }
            .nav-links.active { display: flex !important; }
            .hero h1 { font-size: 2rem; }
            .hero p  { font-size: 1rem; }
            .cta-buttons { flex-direction: column; align-items: center; }
            .about-content  { grid-template-columns: 1fr; }
            .about-image    { height: 300px; }
            .contact-content{ grid-template-columns: 1fr; }
            .services-grid  { grid-template-columns: 1fr; }
            .section-title  { font-size: 2rem; }
            .btn-whatsapp   { width: 50px; height: 50px; font-size: 1.5rem; bottom: 1.5rem; left: 1.5rem; }
            .testimonials   { padding: 3rem 1rem; }
            .back-to-top    { width: 45px; height: 45px; font-size: 1.2rem; bottom: 5.5rem; right: 1.5rem; }
            .modal-body       { grid-template-columns: 1fr; }
            .modal-media      { border-radius: 0; min-height: 240px; }
            .modal-media-main { min-height: 200px; }
            .modal-media-main img    { max-height: 220px; }
            .modal-media-main iframe { height: 200px; }
            .works-grid       { grid-template-columns: 1fr; }
            .works-categories { gap: .45rem; }
            .cat-btn          { padding: .45rem .9rem; font-size: .82rem; }
            .modal-box        { max-height: 95vh; border-radius: 14px; }
            .modal-header     { border-radius: 14px 14px 0 0; padding: 1rem 1.2rem; }
            .modal-details    { padding: 1.2rem; }
            .modal-info-grid  { grid-template-columns: 1fr; }
            .our-works        { padding: 4rem 1rem; }
        }
        @media(max-width:480px){
            nav { padding: 0 1rem; }
            .logo { font-size: 1.3rem; }
            .hero { padding: 4rem 1rem; min-height: auto; }
            .hero h1 { font-size: 1.5rem; }
            .hero p  { font-size: .95rem; }
            .btn { padding: .8rem 1.5rem; font-size: .9rem; }
            .about, .services, .contact { padding: 3rem 1rem; }
            .skills-list { gap: .5rem; }
            .skill-tag   { padding: .4rem 1rem; font-size: .85rem; }
            .stats-container { gap: 1.5rem; }
            .stat-item h4    { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <!-- ── Header ── -->
    <header>
        <nav>
            <div class="logo">
                <img src="<?= e($logo_url) ?>" alt="<?= e($site_name) ?> Logo" title="<?= e($site_name) ?> - <?= e($site_tagline) ?>" loading="eager" decoding="async" fetchpriority="high">
            </div>
            <ul class="nav-links">
                <li><a href="#home">الرئيسية</a></li>
                <li><a href="#about">عنا</a></li>
                <li><a href="#services">الخدمات</a></li>
                <li><a href="#our-works">أعمالنا</a></li>
                <li><a href="blog.php">المدونة</a></li>
                <li><a href="#contact">التواصل</a></li>
            </ul>
            <button class="mobile-menu"><i class="fas fa-bars"></i></button>
        </nav>
    </header>

    <!-- ── Hero ── -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1><?= e($h_title) ?></h1>
            <p><?= e($h_sub) ?></p>
            <?php if ($h_note): ?><p style="font-size:1rem;opacity:.9;"><?= e($h_note) ?></p><?php endif; ?>
            <div class="cta-buttons">
                <a href="<?= e($h_btn1l) ?>" class="btn btn-primary">
                    <i class="fas fa-briefcase"></i> <?= e($h_btn1t) ?>
                </a>
                <a href="<?= e($h_btn2l) ?>" class="btn btn-secondary">
                    <i class="fas fa-envelope"></i> <?= e($h_btn2t) ?>
                </a>
            </div>
        </div>
    </section>

    <!-- ── About ── -->
    <section class="about" id="about">
        <h2 class="section-title">عن الشركة</h2>
        <div class="about-content">
            <div class="about-text">
                <h3><?= e($ab_heading) ?></h3>
                <?php if ($ab_content): ?>
                    <?= nl2p($ab_content) ?>
                <?php else: ?>
                    <p>شركة <?= e($site_name) ?> متخصصة في تقديم حلول رقمية وتقنية متكاملة.</p>
                <?php endif; ?>
                <?php if ($ab_skills): ?>
                <div class="skills-list">
                    <?php foreach ($ab_skills as $skill): ?>
                        <span class="skill-tag"><?= e($skill) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="about-image">
                <img src="<?= e($ab_image_url) ?>" alt="<?= e($ab_heading) ?>" loading="lazy" decoding="async">
            </div>
        </div>
    </section>

    <!-- ── Services ── -->
    <section class="services" id="services">
        <div class="services-container">
            <h2 class="section-title">الخدمات</h2>
            <div class="services-grid">
                <?php if ($services): foreach ($services as $svc): ?>
                <div class="service-card">
                    <div class="service-icon"><i class="<?= e($svc['icon']) ?>"></i></div>
                    <h3><?= e($svc['title']) ?></h3>
                    <p><?= e($svc['description']) ?></p>
                </div>
                <?php endforeach; else: ?>
                <div class="service-card">
                    <div class="service-icon"><i class="fas fa-globe"></i></div>
                    <h3>تطوير المواقع</h3>
                    <p>إنشاء مواقع إلكترونية احترافية وسريعة وآمنة بأحدث التقنيات</p>
                </div>
                <div class="service-card">
                    <div class="service-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3>تطبيقات الجوال</h3>
                    <p>تطوير تطبيقات جوال ذكية لأنظمة iOS و Android</p>
                </div>
                <div class="service-card">
                    <div class="service-icon"><i class="fas fa-video"></i></div>
                    <h3>كاميرات المراقبة</h3>
                    <p>تركيب وتجهيز أنظمة كاميرات أمان حديثة عالية الدقة</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ── Stats ── -->
    <section class="stats">
        <div class="stats-container">
            <?php if ($statistics): foreach ($statistics as $stat): ?>
            <div class="stat-item">
                <h4><?= e($stat['value']) ?></h4>
                <p><?= e($stat['label']) ?></p>
            </div>
            <?php endforeach; else: ?>
            <div class="stat-item"><h4>500+</h4><p>مشروع منجز</p></div>
            <div class="stat-item"><h4>15+</h4><p>سنوات خبرة</p></div>
            <div class="stat-item"><h4>300+</h4><p>عميل راضي</p></div>
            <div class="stat-item"><h4>99%</h4><p>معدل الرضا</p></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Our Works (AJAX-driven) ── -->
    <section class="our-works" id="our-works">
        <div class="works-container">
            <h2 class="section-title">أعمالنا</h2>
            <p class="section-subtitle">نماذج من أبرز مشاريعنا المنجزة في مختلف المجالات</p>

            <div class="works-categories" id="worksCategories">
                <button class="cat-btn active" data-cat="0" onclick="filterWorks(0)">
                    <i class="fas fa-th"></i> كل الأعمال
                </button>
            </div>

            <div class="works-grid" id="worksGrid">
                <div class="works-loading">
                    <i class="fas fa-spinner"></i>
                    جاري تحميل الأعمال…
                </div>
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
                <button class="modal-close" onclick="closeModal()" aria-label="إغلاق">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-media" id="modalMedia">
                    <div class="no-media-msg"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
                <div class="modal-details" id="modalDetails">
                    <div class="works-loading"><i class="fas fa-spinner"></i>جاري التحميل…</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Blog Preview ── -->
    <?php if ($blog_preview): ?>
    <section class="blog-preview" id="blog">
        <div class="blog-container">
            <h2 class="section-title">آخر المقالات</h2>
            <div class="blog-grid">
                <?php foreach ($blog_preview as $bp): ?>
                <a href="blog-post.php?slug=<?= urlencode($bp['slug']) ?>" class="blog-card">
                    <div class="blog-card-img">
                        <?php if ($bp['thumbnail']): ?>
                            <img src="<?= e(site_media_url($bp['thumbnail'])) ?>" alt="<?= e($bp['title']) ?>" loading="lazy" decoding="async"
                                 onerror="this.parentElement.innerHTML='<div class=\'blog-no-img\'><i class=\'fas fa-newspaper\'></i></div>'">
                        <?php else: ?>
                            <div class="blog-no-img"><i class="fas fa-newspaper"></i></div>
                        <?php endif; ?>
                        <?php if ($bp['cat_name']): ?>
                            <span class="blog-cat-badge"><?= e($bp['cat_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="blog-card-body">
                        <h3><?= e($bp['title']) ?></h3>
                        <p><?= e($bp['excerpt']) ?></p>
                        <div class="blog-card-meta">
                            <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($bp['created_at'])) ?></span>
                            <span><i class="fas fa-eye"></i> <?= number_format((int)$bp['views']) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="blog-more">
                <a href="blog.php" class="btn btn-primary">
                    <i class="fas fa-newspaper"></i> عرض جميع المقالات
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── Testimonials ── -->
    <section class="testimonials" id="testimonials">
        <div class="testimonials-container">
            <h2 class="section-title">آراء عملائنا</h2>
            <div class="testimonials-grid">
                <?php if ($testimonials): foreach ($testimonials as $t): ?>
                <div class="testimonial-card">
                    <div class="testimonial-stars"><?= str_repeat('★', max(1, min(5, (int)$t['rating']))) ?></div>
                    <p class="testimonial-text">"<?= e($t['content']) ?>"</p>
                    <p class="testimonial-author"><?= e($t['author_name']) ?></p>
                    <p class="testimonial-role"><?= e($t['author_role']) ?></p>
                </div>
                <?php endforeach; else: ?>
                <div class="testimonial-card">
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-text">"خدمة احترافية جداً، فريق <?= e($site_name) ?> متميز"</p>
                    <p class="testimonial-author">عميل راضٍ</p>
                    <p class="testimonial-role">صاحب أعمال</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ── Contact ── -->
    <section class="contact" id="contact">
        <div class="contact-container">
            <h2 class="section-title">تواصل مع <?= e($site_name) ?></h2>
            <div class="contact-content">
                <div class="contact-info">
                    <h3 style="color:#333;margin-bottom:1rem;">معلومات الشركة</h3>

                    <?php if ($ct_phone): ?>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <h4>رقم الهاتف</h4>
                            <p><a href="tel:<?= e($ct_phone) ?>"><?= e($ct_phone) ?></a></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($ct_email): ?>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h4>البريد الإلكتروني</h4>
                            <p><a href="mailto:<?= e($ct_email) ?>"><?= e($ct_email) ?></a></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($ct_address): ?>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h4>الموقع</h4>
                            <p><?= e($ct_address) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($ct_wa): ?>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fab fa-whatsapp"></i></div>
                        <div>
                            <h4>واتساب</h4>
                            <p><a href="https://wa.me/<?= e($ct_wa) ?>">تواصل عبر واتساب</a></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <form class="contact-form" id="contact-form">
                    <div class="form-group">
                        <label for="name">الاسم *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">الموضوع *</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">الرسالة *</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> إرسال الرسالة
                    </button>
                    <div class="form-message" id="formMessage"></div>
                </form>

                <?php if ($ct_map): ?>
                <div class="contact-map"><?= $ct_map ?></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ── Footer ── -->
    <footer>
        <?php if ($socials): ?>
        <div class="social-links">
            <?php foreach ($socials as $s): ?>
            <a href="<?= e($s['url'] ?: '#') ?>" title="<?= e($s['platform']) ?>" target="_blank" rel="noopener">
                <i class="<?= e($s['icon']) ?>"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="social-links">
            <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        </div>
        <?php endif; ?>

        <p>&copy; <?= date('Y') ?> <?= e($footer_text) ?></p>
        <div class="footer-links" style="margin-top:1rem;">
            <a href="page.php?slug=privacy">سياسة الخصوصية</a>
            <a href="page.php?slug=terms">شروط الاستخدام</a>
        </div>
    </footer>

    <!-- ── WhatsApp Float ── -->
    <a href="https://wa.me/<?= e($wa_number) ?>?text=<?= rawurlencode($wa_msg) ?>"
       class="btn-whatsapp" title="تواصل عبر واتساب" target="_blank" rel="noopener">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- ── Back to Top ── -->
    <button class="back-to-top" id="backToTop" title="العودة للأعلى">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Dynamic email for contact form
        const CONTACT_EMAIL = '<?= e(addslashes($ct_email ?: 'contact@example.com')) ?>';

        // Back to top
        const backToTopBtn = document.getElementById('backToTop');
        window.addEventListener('scroll', () =>
            backToTopBtn.classList.toggle('show', window.pageYOffset > 300));
        backToTopBtn.addEventListener('click', () =>
            window.scrollTo({ top: 0, behavior: 'smooth' }));

        // Mobile menu
        const mobileMenu = document.querySelector('.mobile-menu');
        const navLinks   = document.querySelector('.nav-links');
        mobileMenu.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
        document.querySelectorAll('.nav-links a').forEach(link =>
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                navLinks.classList.remove('active');
            })
        );
        document.addEventListener('click', e => {
            if (!e.target.closest('nav')) {
                mobileMenu.classList.remove('active');
                navLinks.classList.remove('active');
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                navLinks.style.display = 'flex';
                mobileMenu.classList.remove('active');
                navLinks.classList.remove('active');
            } else {
                navLinks.style.display = '';
            }
        });

        // Contact form
        const contactForm  = document.getElementById('contact-form');
        const submitBtn    = contactForm.querySelector('.submit-btn');
        const formMessage  = document.getElementById('formMessage');

        contactForm.addEventListener('submit', e => {
            e.preventDefault();
            const name    = document.getElementById('name').value.trim();
            const email   = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();

            if (!name || !email || !subject || !message) {
                showMessage('من فضلك ملء جميع الحقول المطلوبة', 'error'); return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showMessage('من فضلك أدخل بريد إلكتروني صحيح', 'error'); return;
            }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            setTimeout(() => {
                const body = `الاسم: ${name}\nالبريد الإلكتروني: ${email}\n\nالرسالة:\n${message}`;
                window.location.href = `mailto:${CONTACT_EMAIL}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
                showMessage('تم إرسال رسالتك بنجاح! شكراً لتواصلك معنا', 'success');
                setTimeout(() => {
                    contactForm.reset();
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }, 2000);
            }, 1000);
        });

        function showMessage(msg, type) {
            formMessage.textContent = msg;
            formMessage.className = `form-message ${type}`;
            setTimeout(() => formMessage.className = 'form-message', 5000);
        }

        // Scroll animations
        const observer = new IntersectionObserver(entries =>
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity    = '1';
                    entry.target.style.transform  = 'translateY(0)';
                }
            }), { threshold: .1, rootMargin: '0px 0px -50px 0px' }
        );
        document.querySelectorAll('.service-card, .testimonial-card').forEach(el => {
            el.style.opacity   = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all .6s ease-out';
            observer.observe(el);
        });

        // ============================================================
        //  Our Works — load, filter, modal
        // ============================================================
        let currentMedia    = [];
        let currentMediaIdx = 0;

        async function loadCategories() {
            try {
                const res  = await fetch('api/get_categories.php');
                const json = await res.json();
                if (!json.success) return;
                const container = document.getElementById('worksCategories');
                json.data.forEach(cat => {
                    const btn = document.createElement('button');
                    btn.className   = 'cat-btn';
                    btn.dataset.cat = cat.id;
                    btn.innerHTML   = `<i class="${cat.icon}"></i> ${cat.name}`;
                    btn.onclick     = () => filterWorks(cat.id);
                    container.appendChild(btn);
                });
            } catch (e) {}
        }

        function filterWorks(catId) {
            document.querySelectorAll('.cat-btn').forEach(b =>
                b.classList.toggle('active', b.dataset.cat == catId));
            loadProjects(catId);
        }

        async function loadProjects(catId = 0) {
            const grid = document.getElementById('worksGrid');
            grid.innerHTML = '<div class="works-loading"><i class="fas fa-spinner"></i>جاري التحميل…</div>';
            try {
                const url  = catId > 0 ? `api/get_projects.php?category=${catId}` : 'api/get_projects.php';
                const res  = await fetch(url);
                const json = await res.json();
                if (json.success && json.data.length > 0) renderProjects(json.data);
                else grid.innerHTML = '<div class="works-empty"><i class="fas fa-folder-open"></i>لا توجد أعمال في هذا القسم حالياً</div>';
            } catch (e) {
                grid.innerHTML = '<div class="works-empty"><i class="fas fa-exclamation-triangle"></i>تعذّر تحميل الأعمال</div>';
            }
        }

        function renderProjects(projects) {
            const grid = document.getElementById('worksGrid');
            grid.innerHTML = projects.map(p => `
                <div class="work-card" onclick="openModal(${p.id})">
                    <div class="work-card-image">
                        <img src="${p.thumbnail}" alt="${p.title}" loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&h=400&fit=crop'">
                        <div class="work-badges">
                            <span class="badge-category"><i class="${p.category_icon}"></i> ${p.category_name}</span>
                            ${p.is_programming == 1 ? '<span class="badge-programming"><i class="fas fa-code"></i> برمجية</span>' : ''}
                        </div>
                    </div>
                    <div class="work-card-body">
                        <h3>${p.title}</h3>
                        <p>${p.short_desc || ''}</p>
                        <div class="work-card-footer">
                            <span class="work-meta">${p.client_name || ''}${p.project_year ? ' · ' + p.project_year : ''}</span>
                            <button class="btn-view-work"><i class="fas fa-eye"></i> التفاصيل</button>
                        </div>
                    </div>
                </div>
            `).join('');
            grid.querySelectorAll('.work-card').forEach((card, i) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(22px)';
                setTimeout(() => {
                    card.style.transition = 'opacity .45s ease, transform .45s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, i * 75);
            });
        }

        async function openModal(projectId) {
            const overlay = document.getElementById('projectModal');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            document.getElementById('modalTitle').textContent  = '…';
            document.getElementById('modalBadges').innerHTML   = '';
            document.getElementById('modalMedia').innerHTML    = '<div class="no-media-msg"><i class="fas fa-spinner fa-spin"></i></div>';
            document.getElementById('modalDetails').innerHTML  = '<div class="works-loading"><i class="fas fa-spinner"></i>جاري التحميل…</div>';
            try {
                const res  = await fetch(`api/get_project.php?id=${projectId}`);
                const json = await res.json();
                if (!json.success) return;
                const p = json.data;
                document.getElementById('modalTitle').textContent = p.title;
                document.getElementById('modalBadges').innerHTML  = `
                    <span class="badge-category"><i class="${p.category_icon}"></i> ${p.category_name}</span>
                    ${p.is_programming == 1 ? '<span class="badge-programming"><i class="fas fa-code"></i> برمجية</span>' : ''}
                `;
                currentMedia    = p.media || [];
                currentMediaIdx = 0;
                renderMedia();
                const techHTML = p.technologies
                    ? p.technologies.split(',').map(t => `<span class="tech-tag">${t.trim()}</span>`).join('') : '';
                const demoBtn  = (p.is_programming == 1 && p.demo_url)
                    ? `<a href="${p.demo_url}" target="_blank" rel="noopener" class="btn-demo"><i class="fas fa-rocket"></i> تجربة الديمو</a>` : '';
                document.getElementById('modalDetails').innerHTML = `
                    <p class="modal-description">${p.description || ''}</p>
                    <div class="modal-info-grid">
                        ${p.client_name  ? `<div class="modal-info-item"><label>العميل</label><span>${p.client_name}</span></div>`  : ''}
                        ${p.project_year ? `<div class="modal-info-item"><label>السنة</label><span>${p.project_year}</span></div>`   : ''}
                    </div>
                    ${techHTML ? `<div><p class="modal-tech-label">التقنيات المستخدمة</p><div class="modal-technologies">${techHTML}</div></div>` : ''}
                    ${demoBtn  ? `<div class="modal-actions">${demoBtn}</div>` : ''}
                `;
            } catch (e) {
                document.getElementById('modalDetails').innerHTML =
                    '<p style="color:#aaa;text-align:center;padding:2rem;">تعذّر تحميل تفاصيل المشروع</p>';
            }
        }

        function renderMedia() {
            const el = document.getElementById('modalMedia');
            if (!currentMedia.length) {
                el.innerHTML = '<div class="no-media-msg"><i class="fas fa-images"></i><span>لا توجد صور أو فيديوهات لهذا المشروع</span></div>';
                return;
            }
            const cur = currentMedia[currentMediaIdx];
            const mainContent = cur.type === 'video'
                ? `<iframe src="${cur.url}" allowfullscreen allow="autoplay; encrypted-media"></iframe>`
                : `<img src="${cur.url}" alt="${cur.caption || ''}">`;
            const navBtns = currentMedia.length > 1 ? `
                <button class="media-nav-btn media-nav-prev" onclick="event.stopPropagation();goMedia(${(currentMediaIdx - 1 + currentMedia.length) % currentMedia.length})"><i class="fas fa-chevron-right"></i></button>
                <button class="media-nav-btn media-nav-next" onclick="event.stopPropagation();goMedia(${(currentMediaIdx + 1) % currentMedia.length})"><i class="fas fa-chevron-left"></i></button>` : '';
            const thumbStrip = currentMedia.length > 1 ? `
                <div class="modal-media-thumbs">
                    ${currentMedia.map((m, i) => `
                        <div class="media-thumb ${i === currentMediaIdx ? 'active' : ''}" onclick="goMedia(${i})">
                            <img src="${m.thumbnail || m.url}" alt="">
                            ${m.type === 'video' ? '<div class="media-thumb-play"><i class="fas fa-play"></i></div>' : ''}
                        </div>`).join('')}
                </div>` : '';
            const caption = cur.caption ? `<div class="modal-media-caption">${cur.caption}</div>` : '';
            el.innerHTML = `<div class="modal-media-main">${mainContent}${navBtns}</div>${caption}${thumbStrip}`;
        }

        function goMedia(idx) { currentMediaIdx = idx; renderMedia(); }

        function closeModal() {
            document.getElementById('projectModal').classList.remove('active');
            document.body.style.overflow = '';
            currentMedia = []; currentMediaIdx = 0;
        }

        document.getElementById('projectModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeModal();
        });
        document.addEventListener('keydown', e => {
            if (!document.getElementById('projectModal').classList.contains('active')) return;
            if (e.key === 'Escape') { closeModal(); return; }
            if (currentMedia.length < 2) return;
            if (e.key === 'ArrowRight') goMedia((currentMediaIdx - 1 + currentMedia.length) % currentMedia.length);
            if (e.key === 'ArrowLeft')  goMedia((currentMediaIdx + 1) % currentMedia.length);
        });

        // Bootstrap works section
        loadCategories();
        loadProjects();

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && href !== '#' && document.querySelector(href)) {
                    e.preventDefault();
                    document.querySelector(href).scrollIntoView({ behavior: 'smooth', block: 'start' });
                    if (navLinks.classList.contains('active')) {
                        navLinks.classList.remove('active');
                        mobileMenu.classList.remove('active');
                    }
                }
            });
        });
    </script>
</body>
</html>
