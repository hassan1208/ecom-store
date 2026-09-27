<?php
// robots.php — served as /robots.txt via .htaccess rewrite
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo setting('robots_txt', '') ?: default_robots_txt();
