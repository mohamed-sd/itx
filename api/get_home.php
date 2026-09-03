<?php
require_once __DIR__ . '/../config/db.php';

while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function to_abs(string $siteBase, ?string $path): ?string {
    $url = site_media_url((string)$path);
    if ($url === '') return null;
    return preg_match('#^https?://#i', $url) ? $url : $siteBase . '/' . $url;
}

try {
    $db   = getDB();
    $base = rtrim(dirname(get_base_url()), '/');

    // Hero
    $hero = $db->query('SELECT * FROM hero_section WHERE id = 1 LIMIT 1')->fetch();

    // About
    $about = $db->query('SELECT * FROM about_section WHERE id = 1 LIMIT 1')->fetch();
    if ($about) {
        $about['image'] = to_abs($base, $about['image'] ?? '');
    }

    // Services
    $services = $db
        ->query("SELECT id, icon, title, title_en, description, description_en
                   FROM services WHERE status = 'active' ORDER BY sort_order")
        ->fetchAll();

    // Statistics
    $statistics = $db
        ->query("SELECT id, value, label, label_en FROM statistics WHERE status = 'active' ORDER BY sort_order")
        ->fetchAll();

    // Featured projects (first 4 active)
    $projStmt = $db->query(
        "SELECT p.id, p.title, p.title_en, p.short_desc, p.short_desc_en, p.thumbnail,
                p.is_programming, p.demo_url, p.client_name, p.project_year,
                c.name AS category_name, c.name_en AS category_name_en,
                c.icon AS category_icon, c.slug AS category_slug
           FROM projects p
           JOIN categories c ON c.id = p.category_id
          WHERE p.status = 'active'
          ORDER BY p.sort_order
          LIMIT 4"
    );
    $featured = $projStmt->fetchAll();
    foreach ($featured as &$p) {
        $p['thumbnail'] = to_abs($base, $p['thumbnail']);
        $p['is_programming'] = (bool) $p['is_programming'];
    }
    unset($p);

    // Latest posts (3)
    $postStmt = $db->query(
        "SELECT p.id, p.slug, p.title, p.title_en, p.excerpt, p.excerpt_en,
                p.thumbnail, p.author, p.created_at,
                c.name AS category_name, c.name_en AS category_name_en
           FROM blog_posts p
           LEFT JOIN blog_categories c ON c.id = p.category_id
          WHERE p.status = 'published'
          ORDER BY p.created_at DESC
          LIMIT 3"
    );
    $latest_posts = $postStmt->fetchAll();
    foreach ($latest_posts as &$post) {
        $post['thumbnail'] = to_abs($base, $post['thumbnail']);
    }
    unset($post);

    echo json_encode([
        'success' => true,
        'data'    => [
            'hero'             => $hero ?: (object)[],
            'about'            => $about ?: (object)[],
            'services'         => $services,
            'statistics'       => $statistics,
            'featured_projects'=> $featured,
            'latest_posts'     => $latest_posts,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
