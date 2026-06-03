<?php
/**
 * Dynamic XML sitemap — auto-generates URLs for all public pages,
 * blog posts, and blog category filters.
 */
require_once __DIR__ . '/config/db.php';

// ── Settings ──────────────────────────────────────────────
$settings = [];
try {
    $rows     = getDB()->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
    $settings = array_column($rows, 'setting_value', 'setting_key');
} catch (\Exception $ex) {}
$gs = fn($k, $d = '') => $settings[$k] ?? $d;

$base = rtrim($gs('site_url', get_base_url()), '/');

// ── Collect URLs ──────────────────────────────────────────
$urls = [];

// Static pages
$now = date('Y-m-d');
$urls[] = ['loc' => $base . '/',             'lastmod' => $now, 'changefreq' => 'weekly',  'priority' => '1.0'];
$urls[] = ['loc' => $base . '/blog.php',     'lastmod' => $now, 'changefreq' => 'daily',   'priority' => '0.8'];
$urls[] = ['loc' => $base . '/page.php?slug=privacy', 'lastmod' => $now, 'changefreq' => 'monthly', 'priority' => '0.3'];
$urls[] = ['loc' => $base . '/page.php?slug=terms',   'lastmod' => $now, 'changefreq' => 'monthly', 'priority' => '0.3'];

// Blog categories
try {
    $cats = getDB()->query("SELECT slug, updated_at FROM blog_categories WHERE status='active' ORDER BY sort_order")->fetchAll();
    foreach ($cats as $cat) {
        $lastmod = $cat['updated_at'] ? date('Y-m-d', strtotime($cat['updated_at'])) : $now;
        $urls[] = [
            'loc'        => $base . '/blog.php?cat=' . rawurlencode($cat['slug']),
            'lastmod'    => $lastmod,
            'changefreq' => 'weekly',
            'priority'   => '0.6',
        ];
    }
} catch (\Exception $ex) {}

// Blog posts
try {
    $posts = getDB()->query(
        "SELECT slug, updated_at, created_at FROM blog_posts WHERE status='published' ORDER BY created_at DESC"
    )->fetchAll();
    foreach ($posts as $post) {
        $ts      = $post['updated_at'] ?: $post['created_at'];
        $lastmod = $ts ? date('Y-m-d', strtotime($ts)) : $now;
        $urls[]  = [
            'loc'        => $base . '/blog-post.php?slug=' . rawurlencode($post['slug']),
            'lastmod'    => $lastmod,
            'changefreq' => 'monthly',
            'priority'   => '0.7',
        ];
    }
} catch (\Exception $ex) {}

// ── Output XML ────────────────────────────────────────────
header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
    echo '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
    echo '    <changefreq>' . htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>' . "\n";
    echo '    <priority>' . htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8') . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
