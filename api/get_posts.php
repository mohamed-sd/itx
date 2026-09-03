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
    $db     = getDB();
    $catId  = isset($_GET['category']) ? (int) $_GET['category'] : 0;
    $q      = isset($_GET['q'])        ? trim($_GET['q'])         : '';
    $page   = max(1, (int) ($_GET['page']  ?? 1));
    $limit  = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $where  = ["p.status = 'published'"];
    $params = [];

    if ($catId > 0) {
        $where[]  = 'p.category_id = ?';
        $params[] = $catId;
    }
    if ($q !== '') {
        $where[]  = '(p.title LIKE ? OR p.title_en LIKE ? OR p.content LIKE ?)';
        $like     = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    // Total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM blog_posts p $whereClause");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Posts
    $sql = "SELECT p.id, p.slug, p.title, p.title_en, p.excerpt, p.excerpt_en,
                   p.thumbnail, p.author, p.views, p.created_at,
                   c.id AS category_id, c.name AS category_name, c.name_en AS category_name_en, c.slug AS category_slug
              FROM blog_posts p
              LEFT JOIN blog_categories c ON c.id = p.category_id
             $whereClause
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?";

    $stmt = $db->prepare($sql);
    $stmt->execute([...$params, $limit, $offset]);
    $posts = $stmt->fetchAll();

    $base = rtrim(dirname(get_base_url()), '/');
    foreach ($posts as &$post) {
        $post['thumbnail'] = to_abs($base, $post['thumbnail']);
        $post['thumbnail'] = $post['thumbnail'] ?? null;
        $post['views'] = (int) $post['views'];
    }
    unset($post);

    echo json_encode([
        'success' => true,
        'data'    => $posts,
        'total'   => $total,
        'page'    => $page,
        'limit'   => $limit,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
