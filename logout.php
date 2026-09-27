<?php
// logout.php — customer logout (POST only, CSRF-protected; does not touch the cart)
require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}
unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name']);
header('Location: ' . url(''));
exit;
