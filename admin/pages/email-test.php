<?php
// admin/pages/email-test.php — AJAX: send a test email using the saved SMTP settings
require_once __DIR__ . '/../../includes/config.php';
require_admin();
header('Content-Type: application/json');

if (!verify_csrf($_POST['csrf_token'] ?? '')) { echo json_encode(['error' => 'Invalid request.']); exit; }

$to = trim($_POST['to'] ?? '');
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { echo json_encode(['error' => 'Enter a valid email address.']); exit; }

// Use whatever is currently typed in the form (even if not saved yet) so admins
// can verify new settings before committing to them.
$overrides = [];
foreach (['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name'] as $key) {
    if (isset($_POST[$key]) && $_POST[$key] !== '') $overrides[$key] = sanitize($_POST[$key]);
}

$body = email_layout('<h2 style="margin:0 0 8px;color:#111">Test Email</h2><p style="color:#64748b">If you\'re reading this, your SMTP settings are working correctly.</p>');
$ok = send_smtp_email($to, 'Test Email from ' . setting('site_name', 'your store'), $body, $overrides);

echo $ok ? json_encode(['success' => true]) : json_encode(['error' => 'Could not send. Check your SMTP host, port, username, password, and error log for details.']);
