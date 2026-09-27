<?php
// admin/pages/ai-seo-generate.php — AJAX: generate SEO for one product via AI
require_once __DIR__ . '/../../includes/config.php';
require_admin();
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }

$id = (int)($_POST['id'] ?? 0);
$product = $id ? fetch_one("SELECT * FROM products WHERE id=?", 'i', $id) : null;
if (!$product) { echo json_encode(['error' => 'Product not found.']); exit; }

$result = generate_seo_via_ai($product);
if (isset($result['error'])) { echo json_encode(['error' => $result['error']]); exit; }

update_record('products', [
    'meta_title'       => $result['meta_title'],
    'meta_description' => $result['meta_description'],
    'focus_keyword'    => $result['focus_keyword'],
], 'id', $id);

$updated = fetch_one("SELECT * FROM products WHERE id=?", 'i', $id);
$score = calculate_seo_score($updated);

echo json_encode([
    'success'          => true,
    'score'            => $score,
    'meta_title'       => $result['meta_title'],
    'meta_description' => $result['meta_description'],
    'focus_keyword'    => $result['focus_keyword'],
]);
