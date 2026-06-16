<?php
/* ============================================================
   ITX — Shared bootstrap
   DB connection, helpers, and the common variables used by the
   shared header/footer partials. Each page requires this once at
   the very top, then runs its own page-specific queries.
   ============================================================ */

require_once __DIR__ . '/../config/db.php';

if (!function_exists('e')) {
    function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('nl2p')) {
    function nl2p(string $text): string {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        return implode('', array_map(fn($l) => '<p>' . e($l) . '</p>', $lines));
    }
}

/* ── Bilingual output helpers ───────────────────────────────
   Emit data-ar/data-en attributes that the front-end language
   toggle (assets/js/script.js → applyLang) swaps via innerHTML.
   data-en is omitted when empty so the element keeps its Arabic
   value as a fallback. The visible (initial) content is Arabic. */
if (!function_exists('bi_text')) {
    // For plain text. Double-escaped so the innerHTML swap renders literal text.
    function bi_text($ar, $en = null): string {
        $out = ' data-ar="' . e(e((string)$ar)) . '"';
        $en  = trim((string)$en);
        if ($en !== '') $out .= ' data-en="' . e(e($en)) . '"';
        return $out;
    }
}
if (!function_exists('bi_html')) {
    // For HTML content (kept as HTML when swapped into innerHTML).
    function bi_html($ar_html, $en_html = null): string {
        $out = ' data-ar="' . e((string)$ar_html) . '"';
        if (trim((string)$en_html) !== '') $out .= ' data-en="' . e((string)$en_html) . '"';
        return $out;
    }
}

/* ── DB connection ─────────────────────────────────────────── */
$pdo   = null;
$db_ok = false;
try { $pdo = getDB(); $db_ok = true; } catch (\Throwable $ex) {}

/* ── Settings ──────────────────────────────────────────────── */
$settings = [];
if ($db_ok) {
    try {
        $rows     = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
        $settings = array_column($rows, 'setting_value', 'setting_key');
    } catch (\Throwable $ex) {}
}
$gs = fn($k, $d = '') => $settings[$k] ?? $d;

/* ── Common contact + socials (used by shared footer) ──────── */
$contact = [];
$socials = [];
if ($db_ok) {
    try { $contact = $pdo->query("SELECT * FROM contact_info WHERE id=1")->fetch() ?: []; } catch (\Throwable $ex) {}
    try { $socials = $pdo->query("SELECT * FROM social_media WHERE status='active' ORDER BY sort_order")->fetchAll(); } catch (\Throwable $ex) {}
}

/* ── Computed brand values ─────────────────────────────────── */
$site_name    = $gs('site_name',        'ITX');
$site_tagline = $gs('site_tagline',     'حلول برمجية وتركيب كاميرات أمان');
$logo_path    = $gs('site_logo',        'assets/itx-logo-full.png');
$logo_url     = site_media_url($logo_path, 'assets/itx-logo-full.png');
$footer_text  = $gs('footer_text',      'جميع الحقوق محفوظة | شركة ' . $site_name . ' للحلول الرقمية');

/* English siblings (managed from admin) */
$enable_english  = $gs('enable_english', '1') !== '0';
$site_tagline_en = $gs('site_tagline_en', '');
$footer_text_en  = $gs('footer_text_en', '');

$ct_phone      = $contact['phone']      ?? '';
$ct_email      = $contact['email']      ?? '';
$ct_address    = $contact['address']    ?? '';
$ct_address_en = $contact['address_en'] ?? '';
$ct_wa         = $contact['whatsapp']   ?? '';
$ct_map        = $contact['map_embed']  ?? '';

$wa_number  = $gs('whatsapp_number', '') ?: ($ct_wa ?: '966501234567');
$wa_msg     = $gs('whatsapp_msg',    'مرحباً، أود التواصل معكم');

/* ── URLs ──────────────────────────────────────────────────── */
$base_url   = rtrim($gs('site_url', get_base_url()), '/');

/* Pages set $is_home = true before including partials. On sub-pages the
   in-page nav links must point back to index.php. */
$is_home    = $is_home ?? false;
$nav_prefix = $is_home ? '' : 'index.php';
