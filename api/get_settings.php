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
    $rows     = getDB()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    $siteBase = rtrim(dirname(get_base_url()), '/');

    $data = [];
    foreach ($rows as $row) {
        $key = $row['setting_key'];
        $val = $row['setting_value'];
        if ($key === 'site_logo') {
            $val = to_abs($siteBase, $val) ?? ($siteBase . '/assets/itx-logo-full.png');
        }
        $data[$key] = $val;
    }

    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
