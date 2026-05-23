<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

$catId = isset($_GET['category']) ? (int) $_GET['category'] : 0;

try {
    $db = getDB();

    if ($catId > 0) {
        $sql  = 'SELECT p.id, p.title, p.short_desc, p.thumbnail,
                        p.is_programming, p.demo_url,
                        p.client_name, p.project_year,
                        c.name AS category_name,
                        c.icon AS category_icon,
                        c.slug AS category_slug
                   FROM projects p
                   JOIN categories c ON c.id = p.category_id
                  WHERE p.status = "active" AND p.category_id = ?
                  ORDER BY p.sort_order';
        $stmt = $db->prepare($sql);
        $stmt->execute([$catId]);
    } else {
        $sql  = 'SELECT p.id, p.title, p.short_desc, p.thumbnail,
                        p.is_programming, p.demo_url,
                        p.client_name, p.project_year,
                        c.name AS category_name,
                        c.icon AS category_icon,
                        c.slug AS category_slug
                   FROM projects p
                   JOIN categories c ON c.id = p.category_id
                  WHERE p.status = "active"
                  ORDER BY p.sort_order';
        $stmt = $db->query($sql);
    }

    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
