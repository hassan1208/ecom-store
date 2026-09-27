<?php
// manifest.php — served as /manifest.webmanifest (installable PWA metadata,
// brand colours for mobile browser chrome).
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=86400');
$icon = setting('site_favicon') ?: setting('site_logo');
echo json_encode([
    'name' => setting('site_name', 'BuiltCo Sports'),
    'short_name' => mb_substr(setting('site_name', 'BuiltCo'), 0, 12),
    'description' => setting('site_tagline', ''),
    'start_url' => url(''),
    'scope' => url(''),
    'display' => 'standalone',
    'background_color' => '#f6f5f2',
    'theme_color' => setting('theme_ink_color', '#0b0f14'),
    'icons' => $icon
        ? [['src' => UPLOAD_URL . $icon, 'sizes' => 'any', 'purpose' => 'any']]
        : [['src' => SITE_URL . '/assets/images/favicon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any']],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
