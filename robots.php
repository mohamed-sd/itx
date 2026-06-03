<?php
/**
 * Serves robots.txt dynamically so the Sitemap URL always matches
 * the current host/path without manual edits.
 */
require_once __DIR__ . '/config/db.php';

$settings = [];
try {
    $rows     = getDB()->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
    $settings = array_column($rows, 'setting_value', 'setting_key');
} catch (\Exception $ex) {}
$gs = fn($k, $d = '') => $settings[$k] ?? $d;

$base    = rtrim($gs('site_url', get_base_url()), '/');
$sitemap = $base . '/sitemap.php';

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "\n";
echo "# Block admin and internal areas\n";
echo "Disallow: /admin/\n";
echo "Disallow: /config/\n";
echo "Disallow: /database/\n";
echo "Disallow: /api/\n";
echo "\n";
echo "Sitemap: {$sitemap}\n";
