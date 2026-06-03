<?php
// ─────────────────────────────────────────────
//  Database configuration — edit these values
// ─────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');          // WAMP default: empty password
define('DB_NAME',    'itx_db');
define('DB_CHARSET', 'utf8mb4');

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
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
