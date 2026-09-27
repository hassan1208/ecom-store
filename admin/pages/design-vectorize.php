<?php
// admin/pages/design-vectorize.php — AJAX endpoint: runs the customer's uploaded
// photo through vtracer (a local, offline raster-to-SVG tool) and stores the result.
require_once __DIR__ . '/../../includes/config.php';
require_admin();
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }

$id = (int)($_POST['id'] ?? 0);
$req = fetch_one("SELECT * FROM design_requests WHERE id=?", 'i', $id);
if (!$req) { echo json_encode(['error' => 'Design request not found.']); exit; }

$preset = in_array($_POST['preset'] ?? '', ['photo', 'poster', 'bw'], true) ? $_POST['preset'] : 'photo';

$vtracer = PHP_OS_FAMILY === 'Windows' ? realpath(__DIR__ . '/../../tools/vtracer/vtracer.exe') : false;
if (!$vtracer && function_exists('shell_exec')) {
    $found = trim((string)@shell_exec('command -v vtracer 2>/dev/null'));
    $vtracer = $found !== '' ? $found : false;
}
$input_path = UPLOAD_PATH . $req['original_image_path'];
if (!$vtracer || !file_exists($input_path)) { echo json_encode(['error' => 'Vectorizer tool or source image missing.']); exit; }

$out_dir = UPLOAD_PATH . 'design-vectors/';
if (!is_dir($out_dir)) mkdir($out_dir, 0755, true);
$out_rel = 'design-vectors/vec-' . bin2hex(random_bytes(8)) . '.svg';
$out_path = UPLOAD_PATH . $out_rel;

$cmd = escapeshellarg($vtracer) . ' --input ' . escapeshellarg($input_path) . ' --output ' . escapeshellarg($out_path) . ' --preset ' . escapeshellarg($preset);
exec($cmd . ' 2>&1', $output_lines, $return_code);

if ($return_code !== 0 || !file_exists($out_path)) {
    error_log('vtracer failed: ' . implode("\n", $output_lines));
    echo json_encode(['error' => 'Vectorization failed. Try a different preset or a clearer image.']);
    exit;
}

update_record('design_requests', ['vector_svg_path' => $out_rel, 'status' => 'vectorized'], 'id', $id);

echo json_encode(['success' => true, 'path' => $out_rel, 'url' => UPLOAD_URL . $out_rel]);
