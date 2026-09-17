<?php
/* ============================================================
   ITX CRM — employee authentication, paths & shared helpers.
   Separate session namespace from the admin panel.
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('itx_crm');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

/* ── Paths ── */
define('CRM_ROOT',   dirname(dirname(dirname(__FILE__))));  // /itx
define('CRM_PATH',   dirname(dirname(__FILE__)));           // /itx/crm
require_once CRM_ROOT . '/config/db.php';

function crm_prefix(): string {
    static $p = null;
    if ($p === null) {
        $script  = $_SERVER['SCRIPT_NAME'] ?? '/crm/index.php';
        $crmDir  = dirname($script);          // /itx/crm  (or /crm)
        $p       = $crmDir;
        if ($p === '/' || $p === '\\' || $p === '.') $p = '';
    }
    return $p;
}
function crm_url(string $page = 'dashboard', array $q = []): string {
    $u = crm_prefix() . '/index.php?page=' . $page;
    foreach ($q as $k => $v) $u .= '&' . urlencode($k) . '=' . urlencode((string)$v);
    return $u;
}
function crm_asset(string $f): string {
    $rel = '/assets/' . ltrim($f, '/');
    $v   = @filemtime(CRM_PATH . $rel);
    return crm_prefix() . $rel . ($v ? '?v=' . $v : '');
}

/* ── Idle timeout (2h) + live status check ── */
if (!empty($_SESSION['emp_id'])) {
    $maxIdle = 7200;
    $last    = (int)($_SESSION['emp_last'] ?? time());
    if ((time() - $last) > $maxIdle) {
        crm_kill_session('انتهت الجلسة بسبب عدم النشاط. الرجاء تسجيل الدخول مجدداً.');
    } else {
        $_SESSION['emp_last'] = time();
    }
}

function crm_kill_session(string $msg = ''): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
    if ($msg !== '') { $_SESSION['crm_flash'] = $msg; $_SESSION['crm_flash_type'] = 'danger'; }
}

/* ── Auth ── */
function crm_logged_in(): bool { return !empty($_SESSION['emp_id']); }

function require_emp_login(): void {
    if (!crm_logged_in()) {
        header('Location: ' . crm_prefix() . '/login.php');
        exit;
    }
    // Deactivated / deleted mid-session → log out immediately.
    static $checked = false;
    if (!$checked) {
        $checked = true;
        $row = db_row("SELECT status FROM employees WHERE id = ?", [$_SESSION['emp_id']]);
        if (!$row || $row['status'] !== 'active') {
            crm_kill_session('تم إيقاف حسابك. تواصل مع الإدارة.');
            header('Location: ' . crm_prefix() . '/login.php');
            exit;
        }
    }
}

function current_emp(): array {
    return [
        'id'   => $_SESSION['emp_id']   ?? 0,
        'name' => $_SESSION['emp_name'] ?? 'موظف',
    ];
}

/* ── Flash ── */
function crm_set_flash(string $m, string $t = 'success'): void { $_SESSION['crm_flash'] = $m; $_SESSION['crm_flash_type'] = $t; }
function crm_flash(): string {
    if (empty($_SESSION['crm_flash'])) return '';
    $m = htmlspecialchars($_SESSION['crm_flash'], ENT_QUOTES, 'UTF-8');
    $t = $_SESSION['crm_flash_type'] ?? 'success';
    unset($_SESSION['crm_flash'], $_SESSION['crm_flash_type']);
    $i = $t === 'success' ? 'fa-check-circle' : ($t === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle');
    return "<div class='c-alert c-alert-{$t}'><i class='fas {$i}'></i> {$m}</div>";
}
function crm_redirect(string $page, string $m = '', string $t = 'success', array $q = []): void {
    if ($m) crm_set_flash($m, $t);
    header('Location: ' . crm_url($page, $q));
    exit;
}

/* ── Shared small helpers (guarded to avoid clashes) ── */
if (!function_exists('e')) {
    function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('db_row')) {
    function db_row(string $sql, array $p = []): array { $s = getDB()->prepare($sql); $s->execute($p); return $s->fetch() ?: []; }
}
if (!function_exists('db_all')) {
    function db_all(string $sql, array $p = []): array { $s = getDB()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
}
if (!function_exists('db_exec')) {
    function db_exec(string $sql, array $p = []): int { $s = getDB()->prepare($sql); $s->execute($p); return (int)getDB()->lastInsertId(); }
}

/* ── CRM domain constants ── */
function crm_statuses(): array {
    return [
        'new'         => ['label' => 'جديد',          'color' => '#6f7fa6'],
        'contacted'   => ['label' => 'تم التواصل',    'color' => '#24437f'],
        'interested'  => ['label' => 'مهتم',          'color' => '#16306e'],
        'proposal'    => ['label' => 'عرض سعر',       'color' => '#ff924a'],
        'negotiation' => ['label' => 'تفاوض',         'color' => '#FF7A1A'],
        'won'         => ['label' => 'عميل ناجح',     'color' => '#10b981'],
        'lost'        => ['label' => 'مغلق / خسارة',  'color' => '#ef4444'],
    ];
}
function crm_status_label(string $k): string { return crm_statuses()[$k]['label'] ?? $k; }
function crm_status_color(string $k): string { return crm_statuses()[$k]['color'] ?? '#6b7280'; }
function crm_is_status(string $k): bool { return isset(crm_statuses()[$k]); }

function crm_sources(): array {
    return [
        'website'  => 'الموقع الإلكتروني', 'referral' => 'توصية / إحالة',
        'social'   => 'وسائل التواصل',     'ad'       => 'إعلان مموّل',
        'whatsapp' => 'واتساب',            'call'     => 'اتصال مباشر',
        'visit'    => 'زيارة',             'other'    => 'أخرى',
    ];
}
function crm_source_label(string $k): string { return crm_sources()[$k] ?? $k; }

function crm_currencies(): array {
    return ['SAR' => 'ر.س', 'SDG' => 'ج.س', 'USD' => '$', 'EUR' => '€',
            'AED' => 'د.إ', 'EGP' => 'ج.م', 'QAR' => 'ر.ق', 'KWD' => 'د.ك'];
}
function crm_currency_symbol(string $c): string { return crm_currencies()[$c] ?? $c; }

function crm_activity_types(): array {
    return [
        'note'          => ['label' => 'ملاحظة',       'icon' => 'fa-sticky-note', 'color' => '#6b7280'],
        'call'          => ['label' => 'مكالمة',       'icon' => 'fa-phone',       'color' => '#06b6d4'],
        'meeting'       => ['label' => 'اجتماع',       'icon' => 'fa-handshake',   'color' => '#8b5cf6'],
        'email'         => ['label' => 'بريد',         'icon' => 'fa-envelope',    'color' => '#f59e0b'],
        'whatsapp'      => ['label' => 'واتساب',       'icon' => 'fa-whatsapp',    'color' => '#10b981'],
        'status_change' => ['label' => 'تغيير الحالة', 'icon' => 'fa-exchange-alt','color' => '#3b82f6'],
    ];
}

function crm_money(float $v, string $cur): string {
    return number_format($v, ($v == (int)$v ? 0 : 2)) . ' ' . crm_currency_symbol($cur);
}

/* Log an activity row. */
function crm_log(int $customerId, string $type, ?string $content = null, ?string $old = null, ?string $new = null): void {
    $emp = current_emp();
    $s = getDB()->prepare(
        "INSERT INTO customer_activities (customer_id, employee_id, type, content, old_status, new_status, created_at)
         VALUES (?,?,?,?,?,?,NOW())"
    );
    $s->execute([$customerId, ($emp['id'] ?: null), $type, $content, $old, $new]);
}
