<?php
// admin/logout.php
require_once __DIR__ . '/../includes/config.php';
$_SESSION = [];
session_destroy();
header('Location: ' . ADMIN_URL . '/login.php');
exit;
