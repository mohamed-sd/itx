<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'لم يتم إرسال ملف']);
    exit;
}

try {
    $subdir = preg_replace('#[^a-z0-9/_-]#i', '', $_POST['dir'] ?? 'uploads');
    $path   = upload_file($_FILES['file'], $subdir);
    echo json_encode([
        'success'  => true,
        'path'     => $path,
        'url'      => img_url($path),
        'message'  => 'تم الرفع بنجاح',
    ]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
