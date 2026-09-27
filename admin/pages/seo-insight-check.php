<?php
// admin/pages/seo-insight-check.php — AJAX: crawlability check for one URL on this site
// (HTTP reachability + robots.txt allow/disallow + sitemap presence — not live Google
// index data, since that needs a connected Search Console API which isn't set up here.)
require_once __DIR__ . '/../../includes/config.php';
require_admin();
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }

$url = trim($_POST['url'] ?? '');
$parsed = parse_url($url);
$site_host = parse_url(SITE_URL, PHP_URL_HOST);

if (!$parsed || empty($parsed['host'])) { echo json_encode(['error' => 'Enter a full URL, e.g. ' . url('')]); exit; }
if ($parsed['host'] !== $site_host) { echo json_encode(['error' => 'You can only inspect URLs on your own site.']); exit; }

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY         => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 10,
]);
curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) { echo json_encode(['error' => 'Could not reach that URL: ' . $curl_error]); exit; }

// Strip the site's own base path (e.g. a /subfolder when not hosted at domain root)
// so robots.txt rules — always written relative to the site root — match correctly.
$base_path = parse_url(SITE_URL, PHP_URL_PATH) ?: '';
$path = $parsed['path'] ?? '/';
if ($base_path && strpos($path, $base_path) === 0) $path = substr($path, strlen($base_path)) ?: '/';

$robots = setting('robots_txt', '') ?: default_robots_txt();
$disallowed = false;
foreach (preg_split('/\r\n|\r|\n/', $robots) as $line) {
    if (preg_match('/^Disallow:\s*(\S+)/i', trim($line), $m)) {
        $rule = trim($m[1]);
        if ($rule !== '' && strpos($path, $rule) === 0) { $disallowed = true; break; }
    }
}

$in_sitemap = false;
$sitemap_ch = curl_init(url('sitemap.xml'));
curl_setopt_array($sitemap_ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$sitemap_xml = curl_exec($sitemap_ch);
curl_close($sitemap_ch);
if ($sitemap_xml && strpos($sitemap_xml, htmlspecialchars($url, ENT_XML1)) !== false) $in_sitemap = true;

echo json_encode([
    'success'    => true,
    'http_code'  => $http_code,
    'reachable'  => $http_code >= 200 && $http_code < 400,
    'disallowed' => $disallowed,
    'in_sitemap' => $in_sitemap,
]);
