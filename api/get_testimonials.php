<?php
require_once __DIR__ . '/../config/db.php';

while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

try {
    $rows = getDB()
        ->query("SELECT id, author_name, author_role, author_role_en, rating, content, content_en
                   FROM testimonials
                  WHERE status = 'active'
                  ORDER BY sort_order")
        ->fetchAll();

    // Cast rating to int
    foreach ($rows as &$r) {
        $r['rating'] = (int) $r['rating'];
    }
    unset($r);

    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
