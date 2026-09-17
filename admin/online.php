<?php
/* ============================================================
   ITX Admin — "Who is online now" JSON endpoint
   Returns visitors active in the last 5 minutes. Auth-protected,
   polled by the analytics page for live updates.
   ============================================================ */
require_once __DIR__ . '/includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$WINDOW = 5; // minutes

$flag = function (?string $cc): string {
    $cc = strtoupper(trim((string)$cc));
    if ($cc === 'LO') return '🏠';
    if (strlen($cc) !== 2 || !ctype_alpha($cc)) return '🌐';
    $a = 0x1F1E6;
    return mb_convert_encoding('&#' . ($a + ord($cc[0]) - 65) . ';&#' . ($a + ord($cc[1]) - 65) . ';', 'UTF-8', 'HTML-ENTITIES');
};
$devLabels  = ['desktop' => 'حاسوب', 'mobile' => 'جوال', 'tablet' => 'لوحي'];
$typeLabels = ['home' => 'الرئيسية', 'blog' => 'المدونة', 'blog_post' => 'مقال', 'page' => 'صفحة'];

try {
    $rows = db_all(
        "SELECT pv.visited_at, pv.page_title, pv.page_path, pv.page_type,
                pv.country, pv.country_code, pv.city,
                pv.device_type, pv.browser, pv.os, pv.referrer_host,
                TIMESTAMPDIFF(SECOND, pv.visited_at, NOW()) ago
         FROM page_visits pv
         JOIN (SELECT MAX(id) mid FROM page_visits
               WHERE visited_at >= NOW() - INTERVAL {$WINDOW} MINUTE
                 AND is_bot = 0 AND visitor_id IS NOT NULL
               GROUP BY visitor_id) t ON t.mid = pv.id
         ORDER BY pv.visited_at DESC"
    );

    $pv = (int) (db_row(
        "SELECT COUNT(*) c FROM page_visits
         WHERE visited_at >= NOW() - INTERVAL {$WINDOW} MINUTE AND is_bot = 0"
    )['c'] ?? 0);

    $visitors = [];
    foreach ($rows as $r) {
        $loc = $flag($r['country_code']) . ' ' . ($r['country'] ?: 'غير معروف');
        if (!empty($r['city']) && $r['city'] !== $r['country']) $loc .= ' — ' . $r['city'];
        $visitors[] = [
            'ago'    => (int) $r['ago'],
            'page'   => $r['page_title'] ?: $r['page_path'],
            'type'   => $typeLabels[$r['page_type']] ?? $r['page_type'],
            'loc'    => $loc,
            'device' => $devLabels[$r['device_type']] ?? ($r['device_type'] ?: '—'),
            'tech'   => trim(($r['browser'] ?: '') . ($r['os'] ? ' · ' . $r['os'] : '')),
            'src'    => $r['referrer_host'] ?: 'مباشر',
        ];
    }

    echo json_encode([
        'ok'        => true,
        'count'     => count($visitors),
        'pageviews' => $pv,
        'window'    => $WINDOW,
        'updated'   => date('H:i:s'),
        'visitors'  => $visitors,
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $ex) {
    echo json_encode(['ok' => false, 'count' => 0, 'pageviews' => 0, 'visitors' => [], 'error' => 'db'], JSON_UNESCAPED_UNICODE);
}
