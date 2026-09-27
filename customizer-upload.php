<?php
// customizer-upload.php — AJAX endpoint: saves the customer's composited
// customizer canvas (base64 PNG) as a real file and hands back its path, so
// the design can be attached to the cart item and later to the order.
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }

$data_url = $_POST['image_data'] ?? '';
if (!preg_match('/^data:image\/png;base64,(.+)$/', $data_url, $m)) {
    echo json_encode(['error' => 'Invalid image data.']); exit;
}

$binary = base64_decode($m[1], true);
if ($binary === false || strlen($binary) > 8 * 1024 * 1024) {
    echo json_encode(['error' => 'Invalid or oversized image.']); exit;
}

// Confirm the decoded bytes are really a PNG (never trust the declared mime alone).
$info = @getimagesizefromstring($binary);
if (!$info || $info['mime'] !== 'image/png') {
    echo json_encode(['error' => 'Invalid image content.']); exit;
}

$dir = UPLOAD_PATH . 'customizations/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$filename = 'custom-' . bin2hex(random_bytes(8)) . '.png';
if (file_put_contents($dir . $filename, $binary) === false) {
    echo json_encode(['error' => 'Could not save image.']); exit;
}

echo json_encode(['success' => true, 'path' => 'customizations/' . $filename, 'url' => UPLOAD_URL . 'customizations/' . $filename]);
