<?php
/* ============================================================
   ITX — Visitor analytics tracker
   Included from partials/bootstrap.php on every PUBLIC page.
   Records one row per pageview in `page_visits`.
   Fail-safe: the caller wraps this in try/catch, and geo lookup
   is deferred to shutdown so it never blocks page rendering.
   ============================================================ */

if (!isset($pdo) || !($pdo instanceof PDO)) return;

/* Only track real page views (GET), never admin or API endpoints. */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
$script = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($script, '/api/') !== false || strpos($script, '/admin/') !== false) return;

/* ── Identify the page ── */
$base  = basename($script);
$type  = 'other';
$title = '';
switch ($base) {
    case 'index.php':     $type = 'home';      $title = 'الصفحة الرئيسية'; break;
    case 'blog.php':      $type = 'blog';      $title = 'المدونة';         break;
    case 'blog-post.php':
        $type  = 'blog_post';
        $s     = trim($_GET['slug'] ?? '');
        $title = $s !== '' ? 'مقال: ' . $s : 'مقال';
        break;
    case 'page.php':
        $type  = 'page';
        $s     = trim($_GET['slug'] ?? '');
        $labels = ['privacy' => 'سياسة الخصوصية', 'terms' => 'الشروط والأحكام'];
        $title = $labels[$s] ?? ($s !== '' ? 'صفحة: ' . $s : 'صفحة');
        break;
    default:              $title = $base;
}

$path = substr($_SERVER['REQUEST_URI'] ?? $script, 0, 255);

/* ── Parse the User-Agent ── */
$ua    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
$isBot = (bool) preg_match(
    '/bot|crawl|spider|slurp|bing|yandex|baidu|duckduck|facebookexternalhit|whatsapp|telegram|'
    . 'embedly|quora|pinterest|preview|monitor|curl|wget|python-requests|go-http|java\/|headless|'
    . 'phantom|lighthouse|pingdom|uptime|ahrefs|semrush|mj12|dotbot|petalbot|bytespider/i',
    $ua
);

if (preg_match('/iPad|Tablet|PlayBook|Silk/i', $ua))                                          $device = 'tablet';
elseif (preg_match('/Mobile|Android|iPhone|iPod|IEMobile|BlackBerry|Opera Mini/i', $ua))      $device = 'mobile';
else                                                                                          $device = 'desktop';

if     (preg_match('/Edg|Edge/i', $ua))          $browser = 'Edge';
elseif (preg_match('/OPR|Opera/i', $ua))         $browser = 'Opera';
elseif (preg_match('/SamsungBrowser/i', $ua))    $browser = 'Samsung Internet';
elseif (preg_match('/Chrome|CriOS/i', $ua))      $browser = 'Chrome';
elseif (preg_match('/Firefox|FxiOS/i', $ua))     $browser = 'Firefox';
elseif (preg_match('/Safari/i', $ua))            $browser = 'Safari';
elseif (preg_match('/MSIE|Trident/i', $ua))      $browser = 'Internet Explorer';
else                                             $browser = 'أخرى';

if     (preg_match('/Windows/i', $ua))                 $os = 'Windows';
elseif (preg_match('/Android/i', $ua))                 $os = 'Android';
elseif (preg_match('/iPhone|iPad|iPod|iOS/i', $ua))    $os = 'iOS';
elseif (preg_match('/Mac OS X|Macintosh/i', $ua))      $os = 'macOS';
elseif (preg_match('/Linux|X11/i', $ua))               $os = 'Linux';
else                                                   $os = 'أخرى';

/* ── Visitor & session cookies (skip issuing to bots) ── */
$visitorId = null;
$sessionId = null;
$isNew     = 0;
$secure    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

$validId = fn($v) => is_string($v) && preg_match('/^[a-f0-9]{32}$/', $v);

if (!empty($_COOKIE['itx_vid']) && $validId($_COOKIE['itx_vid'])) {
    $visitorId = $_COOKIE['itx_vid'];
} elseif (!$isBot && !headers_sent()) {
    $visitorId = bin2hex(random_bytes(16));
    $isNew     = 1;
    setcookie('itx_vid', $visitorId, [
        'expires' => time() + 31536000, 'path' => '/',
        'secure'  => $secure, 'httponly' => true, 'samesite' => 'Lax',
    ]);
}

if (!empty($_COOKIE['itx_sid']) && $validId($_COOKIE['itx_sid'])) {
    $sessionId = $_COOKIE['itx_sid'];
} elseif (!$isBot && !headers_sent()) {
    $sessionId = bin2hex(random_bytes(16));
    setcookie('itx_sid', $sessionId, [
        'expires' => 0, 'path' => '/',
        'secure'  => $secure, 'httponly' => true, 'samesite' => 'Lax',
    ]);
}

/* ── IP (respect a single reverse-proxy hop if present) ── */
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $fwd = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    if (filter_var($fwd, FILTER_VALIDATE_IP)) $ip = $fwd;
}
$ipHash = $ip !== '' ? hash('sha256', $ip . '|itx-analytics') : null;

/* ── Referrer ── */
$ref     = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255);
$refHost = null;
if ($ref !== '') {
    $h = parse_url($ref, PHP_URL_HOST);
    if ($h && $h !== ($_SERVER['HTTP_HOST'] ?? '')) $refHost = substr($h, 0, 120);
}

$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 10) ?: null;

/* ── Insert the visit ──
   Timestamps come from MySQL (NOW()/CURDATE()) so every NOW()-based
   query (online now, "today", 5-minute window) uses one consistent
   clock, regardless of the PHP timezone. DAYOFWEEK()-1 → 0=Sunday. */
try {
    $stmt = $pdo->prepare(
        "INSERT INTO page_visits
          (visited_at, visit_date, visit_hour, weekday, page_type, page_path, page_title,
           visitor_id, session_id, is_new_visitor, ip_hash, ip_addr,
           referrer, referrer_host, device_type, browser, os, is_bot, lang)
         VALUES (NOW(), CURDATE(), HOUR(NOW()), (DAYOFWEEK(NOW())-1),
                 ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $type, $path, $title,
        $visitorId, $sessionId, $isNew,
        $ipHash, ($ip !== '' ? $ip : null),
        ($ref !== '' ? $ref : null), $refHost,
        $device, $browser, $os, ($isBot ? 1 : 0), $lang,
    ]);
    $visitId = (int) $pdo->lastInsertId();
} catch (\Throwable $ex) {
    return;
}

/* ── Geolocation — resolved after the response, cached per IP ── */
if ($ip !== '' && !$isBot && $visitId > 0) {
    register_shutdown_function(function () use ($pdo, $ip, $visitId) {
        try {
            // Private / loopback / reserved → mark as local, no external call.
            $public = filter_var(
                $ip, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
            if ($public === false) {
                $pdo->prepare("UPDATE page_visits SET country=?, country_code=?, city=? WHERE id=?")
                    ->execute(['شبكة محلية', 'LO', 'محلي', $visitId]);
                return;
            }

            // Cache first.
            $c = $pdo->prepare("SELECT country, country_code, city FROM ip_geo WHERE ip_addr = ?");
            $c->execute([$ip]);
            $geo = $c->fetch();

            if (!$geo) {
                $ctx = stream_context_create(['http' => ['timeout' => 1.5, 'ignore_errors' => true]]);
                $url = 'http://ip-api.com/json/' . urlencode($ip)
                     . '?fields=status,country,countryCode,city&lang=ar';
                $raw = @file_get_contents($url, false, $ctx);
                $j   = $raw ? json_decode($raw, true) : null;
                if (is_array($j) && ($j['status'] ?? '') === 'success') {
                    $geo = [
                        'country'      => $j['country']     ?? null,
                        'country_code' => $j['countryCode'] ?? null,
                        'city'         => $j['city']        ?? null,
                    ];
                    $pdo->prepare(
                        "INSERT INTO ip_geo (ip_addr, country, country_code, city, resolved_at)
                         VALUES (?,?,?,?,NOW())
                         ON DUPLICATE KEY UPDATE country=VALUES(country),
                           country_code=VALUES(country_code), city=VALUES(city), resolved_at=NOW()"
                    )->execute([$ip, $geo['country'], $geo['country_code'], $geo['city']]);
                }
            }

            if ($geo) {
                $pdo->prepare("UPDATE page_visits SET country=?, country_code=?, city=? WHERE id=?")
                    ->execute([$geo['country'], $geo['country_code'], $geo['city'], $visitId]);
            }
        } catch (\Throwable $ex) { /* silent */ }
    });
}
