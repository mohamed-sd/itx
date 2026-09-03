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
    $row = getDB()->query('SELECT * FROM about_section WHERE id = 1 LIMIT 1')->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Convert image to absolute URL
    $siteBase     = rtrim(dirname(get_base_url()), '/');
    $row['image'] = to_abs($siteBase, $row['image'] ?? '');

    echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
