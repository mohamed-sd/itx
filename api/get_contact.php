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
    $db = getDB();

    $contact = $db->query('SELECT phone, email, address, address_en, whatsapp, map_embed FROM contact_info WHERE id = 1 LIMIT 1')->fetch();

    $socials = $db
        ->query("SELECT id, platform, icon, url FROM social_media WHERE status = 'active' ORDER BY sort_order")
        ->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => [
            'contact' => $contact ?: (object)[],
            'socials' => $socials,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
}
