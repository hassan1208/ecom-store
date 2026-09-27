<?php
// admin/pages/ai-seo-test.php — AJAX: verify the configured OpenAI API key works
require_once __DIR__ . '/../../includes/config.php';
require_admin();
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }

$api_key = trim($_POST['api_key'] ?? setting('ai_api_key', ''));
if (!$api_key) { echo json_encode(['error' => 'Enter an API key first.']); exit; }

$ch = curl_init('https://api.openai.com/v1/models');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $api_key],
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) { echo json_encode(['error' => 'Connection error: ' . $curl_error]); exit; }
if ($http_code === 200) { echo json_encode(['success' => true]); exit; }

$data = json_decode($response, true);
echo json_encode(['error' => $data['error']['message'] ?? "Key check failed (HTTP $http_code)."]);
