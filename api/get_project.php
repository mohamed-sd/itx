<?php
require_once __DIR__ . '/../config/db.php';

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDB();

    // Project detail
    $stmt = $db->prepare(
        'SELECT p.*,
                c.name AS category_name,
                c.icon AS category_icon,
                c.slug AS category_slug
           FROM projects p
           JOIN categories c ON c.id = p.category_id
          WHERE p.id = ? AND p.status = "active"
          LIMIT 1'
    );
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Media
    $mStmt = $db->prepare(
        'SELECT id, type, url, thumbnail, caption
           FROM project_media
          WHERE project_id = ?
          ORDER BY sort_order'
    );
    $mStmt->execute([$id]);
    $project['media'] = $mStmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $project], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
