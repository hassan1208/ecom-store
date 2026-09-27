<?php
// admin/pages/design-mockup-upload.php — AJAX endpoint: saves the admin's
// composited front/back/sleeve mockup canvas (base64 PNG) against a design request.
require_once __DIR__ . '/../../includes/config.php';
require_admin();
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }

$id = (int)($_POST['id'] ?? 0);
$view = $_POST['view'] ?? '';
if (!in_array($view, ['front', 'back', 'sleeve'], true)) { echo json_encode(['error' => 'Invalid view.']); exit; }

$req = fetch_one("SELECT * FROM design_requests WHERE id=?", 'i', $id);
if (!$req) { echo json_encode(['error' => 'Design request not found.']); exit; }

$data_url = $_POST['image_data'] ?? '';
if (!preg_match('/^data:image\/png;base64,(.+)$/', $data_url, $m)) { echo json_encode(['error' => 'Invalid image data.']); exit; }

$binary = base64_decode($m[1], true);
if ($binary === false || strlen($binary) > 8 * 1024 * 1024) { echo json_encode(['error' => 'Invalid or oversized image.']); exit; }

$info = @getimagesizefromstring($binary);
if (!$info || $info['mime'] !== 'image/png') { echo json_encode(['error' => 'Invalid image content.']); exit; }

$dir = UPLOAD_PATH . 'design-mockups/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Replace the previous mockup for this view, if any, instead of accumulating orphans.
$col = 'mockup_' . $view . '_path';
if (!empty($req[$col]) && file_exists(UPLOAD_PATH . $req[$col])) @unlink(UPLOAD_PATH . $req[$col]);

$filename = 'design-mockups/mockup-' . $view . '-' . bin2hex(random_bytes(8)) . '.png';
if (file_put_contents(UPLOAD_PATH . $filename, $binary) === false) { echo json_encode(['error' => 'Could not save image.']); exit; }

$new_status = $req['status'] === 'new' ? 'vectorized' : $req['status'];
update_record('design_requests', [$col => $filename, 'status' => $new_status], 'id', $id);

echo json_encode(['success' => true, 'path' => $filename, 'url' => UPLOAD_URL . $filename]);
