<?php
require_once __DIR__ . '/../config/db.php';

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $rows = getDB()
        ->query('SELECT id, name, icon, slug FROM categories ORDER BY sort_order')
        ->fetchAll();

    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
