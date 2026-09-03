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

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id   = isset($_GET['id'])   ? (int) $_GET['id']   : 0;

if ($slug === '' && $id < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Provide slug or id'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDB();

    if ($slug !== '') {
        $stmt = $db->prepare(
            "SELECT p.*, c.id AS category_id, c.name AS category_name, c.name_en AS category_name_en, c.slug AS category_slug
               FROM blog_posts p
               LEFT JOIN blog_categories c ON c.id = p.category_id
              WHERE p.slug = ? AND p.status = 'published'
              LIMIT 1"
        );
        $stmt->execute([$slug]);
    } else {
        $stmt = $db->prepare(
            "SELECT p.*, c.id AS category_id, c.name AS category_name, c.name_en AS category_name_en, c.slug AS category_slug
               FROM blog_posts p
               LEFT JOIN blog_categories c ON c.id = p.category_id
              WHERE p.id = ? AND p.status = 'published'
              LIMIT 1"
        );
        $stmt->execute([$id]);
    }

    $post = $stmt->fetch();
    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Increment views
    $db->prepare('UPDATE blog_posts SET views = views + 1 WHERE id = ?')->execute([$post['id']]);
    $post['views'] = (int) $post['views'] + 1;

    $base = rtrim(dirname(get_base_url()), '/');
    $post['thumbnail'] = to_abs($base, $post['thumbnail']);

    // Related posts (same category, excluding self)
    $relStmt = $db->prepare(
        "SELECT p.id, p.slug, p.title, p.title_en, p.excerpt, p.excerpt_en,
                p.thumbnail, p.author, p.created_at,
                c.name AS category_name, c.name_en AS category_name_en
           FROM blog_posts p
           LEFT JOIN blog_categories c ON c.id = p.category_id
          WHERE p.category_id = ? AND p.id != ? AND p.status = 'published'
          ORDER BY p.created_at DESC
          LIMIT 4"
    );
    $relStmt->execute([$post['category_id'], $post['id']]);
    $related = $relStmt->fetchAll();

    foreach ($related as &$r) {
        $r['thumbnail'] = to_abs($base, $r['thumbnail']);
    }
    unset($r);

    $post['related'] = $related;

    echo json_encode(['success' => true, 'data' => $post], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
