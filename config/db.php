<?php
// ─────────────────────────────────────────────
//  Database configuration — shared defaults
//
//  These values are committed to git, so they must stay the same
//  for everyone. To change any of them on YOUR machine only,
//  create this git-ignored file:
//
//      config/db.local.php
//
//  returning just the keys you need to override, e.g. on XAMPP
//  (whose MariaDB listens on 3306 instead of WAMP's 3307):
//
//      <?php return ['DB_PORT' => 3306];
//
//  Never edit the defaults below to suit one machine — that is
//  what keeps breaking the other developer's setup after a pull.
// ─────────────────────────────────────────────
$db_local = is_file(__DIR__ . '/db.local.php') ? require __DIR__ . '/db.local.php' : [];
if (!is_array($db_local)) $db_local = [];

define('DB_HOST',    $db_local['DB_HOST']    ?? '127.0.0.1');
define('DB_PORT',    (int)($db_local['DB_PORT'] ?? 3307));  // WAMP MariaDB port (XAMPP default is 3306)
define('DB_USER',    $db_local['DB_USER']    ?? 'root');
define('DB_PASS',    $db_local['DB_PASS']    ?? '');        // WAMP/XAMPP default: empty password
define('DB_NAME',    $db_local['DB_NAME']    ?? 'itx_db');
define('DB_CHARSET', $db_local['DB_CHARSET'] ?? 'utf8mb4');

unset($db_local);

/**
 * Apply baseline security headers for all public/admin pages that include this file.
 */
function apply_security_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    if (function_exists('header_remove')) {
        @header_remove('X-Powered-By');
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Cross-Origin-Opener-Policy: same-origin');

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    $csp = "default-src 'self'; "
         . "img-src 'self' data: https:; "
         . "media-src 'self' https: blob:; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
         . "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
         . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
         . "connect-src 'self'; "
         . "frame-src https://www.google.com https://maps.google.com https://www.youtube.com; "
         . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
    header("Content-Security-Policy: {$csp}");
}

/**
 * Normalize media paths from DB to a browser-safe URL.
 */
function site_media_url(?string $path, string $fallback = ''): string
{
    $value = trim((string)$path);
    if ($value === '') {
        $value = trim($fallback);
    }
    if ($value === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, 'data:')) {
        return $value;
    }

    return ltrim(str_replace('\\', '/', $value), '/');
}

apply_security_headers();

/**
 * Returns the base URL of the site (scheme + host + sub-directory path).
 * Works whether the site lives at the domain root or in a sub-folder.
 * Result never has a trailing slash.  e.g. "https://example.com/itx"
 */
function get_base_url(): string
{
    static $base = null;
    if ($base === null) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir    = dirname($script);
        $path   = ($dir === '/' || $dir === '.' || $dir === '\\') ? '' : rtrim($dir, '/');
        $base   = $scheme . '://' . $host . $path;
    }
    return $base;
}

/**
 * Returns a singleton PDO connection.
 * Throws PDOException on failure (caught by callers).
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
