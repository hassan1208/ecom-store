<?php
// customizer-vectorize.php — AJAX endpoint used by the storefront customizer:
// when a shopper uploads a logo that isn't already a vector (SVG), this runs
// it through vtracer (the same local, offline tool the admin's AI Design
// Request page uses) automatically, so production always has a print-ready
// vector file even if the shopper never uploaded one themselves.
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }
if (empty($_FILES['logo']['tmp_name']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) { echo json_encode(['error' => 'No file received.']); exit; }

$tmp_path = $_FILES['logo']['tmp_name'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $tmp_path);
finfo_close($finfo);

$out_dir = UPLOAD_PATH . 'customizer-logos/vectors/';
if (!is_dir($out_dir)) mkdir($out_dir, 0755, true);

// The shopper already provided a vector file — store it as-is (sanitized),
// no need to auto-trace it.
if (in_array($mime, ['image/svg+xml', 'text/plain', 'text/xml'], true) && $_FILES['logo']['size'] <= 2 * 1024 * 1024) {
    $content = file_get_contents($tmp_path);
    if (stripos($content, '<svg') === false) { echo json_encode(['error' => 'That doesn\'t look like a valid SVG file.']); exit; }
    // Defense in depth: this file may later be opened directly in a browser tab
    // (e.g. by admin), where an SVG's embedded script WOULD execute — strip it.
    $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
    $content = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content);
    $out_rel = 'customizer-logos/vectors/vec-' . bin2hex(random_bytes(8)) . '.svg';
    file_put_contents(UPLOAD_PATH . $out_rel, $content);
    echo json_encode(['success' => true, 'vector_path' => $out_rel, 'source_path' => null, 'is_original_vector' => true]);
    exit;
}

// Otherwise: save the raster the shopper uploaded, then auto-trace a vector
// from it so production has a print-ready file even without one supplied.
$upload = upload_image($_FILES['logo'], 'customizer-logos', 1500, 1500, 'logo');
if (isset($upload['error'])) { echo json_encode(['error' => $upload['error']]); exit; }

$vtracer = realpath(__DIR__ . '/tools/vtracer/vtracer.exe');
$input_path = UPLOAD_PATH . $upload['filename'];
if (!$vtracer || !file_exists($input_path)) {
    echo json_encode(['success' => true, 'vector_path' => null, 'source_path' => $upload['filename'], 'is_original_vector' => false]);
    exit;
}

$out_rel = 'customizer-logos/vectors/vec-' . bin2hex(random_bytes(8)) . '.svg';
$out_path = UPLOAD_PATH . $out_rel;
$cmd = escapeshellarg($vtracer) . ' --input ' . escapeshellarg($input_path) . ' --output ' . escapeshellarg($out_path) . ' --preset photo';
exec($cmd . ' 2>&1', $output_lines, $return_code);

// A failed trace doesn't fail the upload — the shopper's raster logo still
// works fine in the live preview; production can vectorize it manually later.
if ($return_code !== 0 || !file_exists($out_path)) {
    error_log('customizer vtracer failed: ' . implode("\n", $output_lines));
    echo json_encode(['success' => true, 'vector_path' => null, 'source_path' => $upload['filename'], 'is_original_vector' => false]);
    exit;
}

echo json_encode(['success' => true, 'vector_path' => $out_rel, 'source_path' => $upload['filename'], 'is_original_vector' => false]);
