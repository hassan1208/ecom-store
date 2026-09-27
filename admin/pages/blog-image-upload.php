<?php
// admin/pages/blog-image-upload.php — AJAX endpoint used by the Quill editor's
// image button to upload an in-content image and get back a real URL.
require_once __DIR__ . '/../../includes/config.php';
require_admin();
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }
if (empty($_FILES['image'])) { echo json_encode(['error' => 'No file received.']); exit; }

$res = upload_image($_FILES['image'], 'blog/content', 1200, 1200, 'content');
if (isset($res['error'])) { echo json_encode(['error' => $res['error']]); exit; }

echo json_encode(['url' => UPLOAD_URL . $res['filename']]);
