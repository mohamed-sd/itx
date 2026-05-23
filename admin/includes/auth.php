<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Path constants ────────────────────────────────────────
// __FILE__ = /itx/admin/includes/auth.php
define('ROOT_PATH',  dirname(dirname(dirname(__FILE__))));   // /itx
define('ADMIN_PATH', dirname(dirname(__FILE__)));            // /itx/admin

require_once ROOT_PATH . '/config/db.php';

// ── URL helpers ───────────────────────────────────────────
function site_prefix(): string {
    static $p = null;
    if ($p === null) {
        $script   = $_SERVER['SCRIPT_NAME']; // /itx/admin/index.php
        $adminDir = dirname($script);        // /itx/admin
        $p        = dirname($adminDir);      // /itx
        if ($p === '/') $p = '';
    }
    return $p;
}

function admin_prefix(): string {
    static $p = null;
    if ($p === null) $p = site_prefix() . '/admin';
    return $p;
}

function admin_url(string $page = 'dashboard'): string {
    return admin_prefix() . '/index.php?page=' . $page;
}

function site_url(string $path = ''): string {
    return site_prefix() . ($path ? '/' . ltrim($path, '/') : '/');
}

function img_url(string $path): string {
    if (empty($path)) return '';
    if (preg_match('#^https?://#', $path)) return $path;
    return site_prefix() . '/' . ltrim($path, '/');
}

// ── Auth ─────────────────────────────────────────────────
function is_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . admin_prefix() . '/login.php');
        exit;
    }
}

// ── Flash messages ────────────────────────────────────────
function set_flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash_msg']  = $msg;
    $_SESSION['flash_type'] = $type;
}

function get_flash(): string {
    if (empty($_SESSION['flash_msg'])) return '';
    $msg  = htmlspecialchars($_SESSION['flash_msg'], ENT_QUOTES, 'UTF-8');
    $type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    $icon = $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    return "<div class='alert alert-{$type} alert-auto-dismiss'><i class='fas {$icon}'></i> {$msg}</div>";
}

function redirect_admin(string $page, string $msg = '', string $type = 'success'): void {
    if ($msg) set_flash($msg, $type);
    header('Location: ' . admin_url($page));
    exit;
}

// ── HTML escape ───────────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function nl2p(string $s): string {
    $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $paragraphs = array_filter(explode("\n", $s));
    return implode('', array_map(fn($p) => "<p>{$p}</p>", $paragraphs));
}

// ── Settings helpers ──────────────────────────────────────
function get_setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            $rows = getDB()->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
            $cache = array_column($rows, 'setting_value', 'setting_key');
        } catch (\Exception $e) { $cache = []; }
    }
    return $cache[$key] ?? $default;
}

function save_setting(string $key, string $value): void {
    $db   = getDB();
    $stmt = $db->prepare(
        "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = ?"
    );
    $stmt->execute([$key, $value, $value]);
}

function save_settings(array $kv): void {
    foreach ($kv as $k => $v) save_setting($k, (string)$v);
}

// ── Image upload ──────────────────────────────────────────
function handle_image_input(
    string $fileKey,
    string $urlKey,
    string $existingVal,
    string $subdir = 'uploads'
): string {
    // 1. File upload takes priority
    if (!empty($_FILES[$fileKey]['name']) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        return upload_file($_FILES[$fileKey], $subdir);
    }
    // 2. URL input
    $url = trim($_POST[$urlKey] ?? '');
    if ($url !== '') return $url;
    // 3. Keep existing
    return $existingVal;
}

function upload_file(array $file, string $subdir = 'uploads'): string {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    $finfo   = new \finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed)) throw new \RuntimeException('نوع الملف غير مسموح به');
    if ($file['size'] > 6 * 1024 * 1024) throw new \RuntimeException('حجم الملف يتجاوز 6MB');

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = uniqid('img_', true) . '.' . $ext;
    $dir  = ROOT_PATH . '/' . trim($subdir, '/') . '/';

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        throw new \RuntimeException('فشل في رفع الملف');
    }
    return trim($subdir, '/') . '/' . $name;
}

// ── DB single-row helpers ─────────────────────────────────
function db_row(string $sql, array $params = []): array {
    $s = getDB()->prepare($sql);
    $s->execute($params);
    return $s->fetch() ?: [];
}

function db_all(string $sql, array $params = []): array {
    $s = getDB()->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

function db_exec(string $sql, array $params = []): int {
    $s = getDB()->prepare($sql);
    $s->execute($params);
    return (int)getDB()->lastInsertId();
}

function db_count(string $table, string $where = '1', array $params = []): int {
    $row = db_row("SELECT COUNT(*) as c FROM `$table` WHERE $where", $params);
    return (int)($row['c'] ?? 0);
}
