<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
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
        echo json_encode(['success' => false, 'message' => 'Not found']);
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

    echo json_encode(['success' => true, 'data' => $project]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
