<?php
// ============================================================
// CONFIG.PHP — Core configuration, DB connection, helpers
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'builtco_sports_new');

// Auto-detect base URL from filesystem paths (works no matter which script/depth
// included this file — deriving it from SCRIPT_NAME depth breaks on nested pages).
$__scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$__host        = $_SERVER['HTTP_HOST'] ?? 'localhost';
$__doc_root    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$__project_dir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/'); // parent of /includes
$__base        = (strpos($__project_dir, $__doc_root) === 0) ? substr($__project_dir, strlen($__doc_root)) : '';
define('SITE_URL', $__scheme . '://' . $__host . $__base);
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
if (!is_dir(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0755, true);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

date_default_timezone_set('Asia/Karachi');

// ============================================================
// DATABASE CONNECTION (singleton)
// ============================================================
class DB {
    private static $instance = null;
    public $conn;

    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database connection error.']);
            error_log('DB connect failed: ' . $this->conn->connect_error);
            exit;
        }
        $this->conn->set_charset('utf8mb4');
    }

    public static function getInstance() {
        if (self::$instance === null) self::$instance = new DB();
        return self::$instance->conn;
    }
}

function db() { return DB::getInstance(); }

function fetch_one($sql, $types = '', ...$params) {
    if (!empty($params)) {
        $stmt = db()->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    $result = db()->query($sql);
    return $result ? $result->fetch_assoc() : null;
}

function fetch_all($sql, $types = '', ...$params) {
    if (!empty($params)) {
        $stmt = db()->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $result = db()->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function insert($table, $data) {
    $keys = implode('`, `', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $types = ''; $values = [];
    foreach ($data as $val) {
        if (is_int($val)) $types .= 'i';
        elseif (is_float($val)) $types .= 'd';
        else $types .= 's';
        $values[] = $val;
    }
    $stmt = db()->prepare("INSERT INTO `$table` (`$keys`) VALUES ($placeholders)");
    $stmt->bind_param($types, ...$values);
    if ($stmt->execute()) return db()->insert_id;
    return false;
}

function update_record($table, $data, $where_col, $where_val) {
    $set = ''; $types = ''; $values = [];
    foreach ($data as $col => $val) {
        $set .= "`$col` = ?, ";
        if (is_int($val)) $types .= 'i';
        elseif (is_float($val)) $types .= 'd';
        else $types .= 's';
        $values[] = $val;
    }
    $set = rtrim($set, ', ');
    $types .= is_int($where_val) ? 'i' : 's';
    $values[] = $where_val;
    $stmt = db()->prepare("UPDATE `$table` SET $set WHERE `$where_col` = ?");
    $stmt->bind_param($types, ...$values);
    return $stmt->execute();
}

// ============================================================
// SITE SETTINGS
// ============================================================
function get_settings() {
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        $result = db()->query("SELECT setting_key, setting_value FROM site_settings");
        if ($result) while ($row = $result->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}
function setting($key, $default = '') {
    $s = get_settings();
    return $s[$key] ?? $default;
}

// ============================================================
// SECURITY HELPERS
// ============================================================
function sanitize($input) {
    return strip_tags(trim((string)$input));
}

// For rich-text fields (e.g. product description) written via a WYSIWYG editor.
// Keeps a small whitelist of safe formatting tags and strips event handlers / js: links.
function sanitize_html($input) {
    $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><h2><h3><h4><a><blockquote><img>';
    $clean = strip_tags(trim((string)$input), $allowed);
    // Strip on*= handlers in all three HTML attribute-value forms — quoted (double/single)
    // AND unquoted (e.g. onerror=alert(1)), which is valid HTML and was previously missed.
    $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    $clean = preg_replace('/(href\s*=\s*["\'])\s*javascript:/i', '$1#', $clean);
    $clean = preg_replace('/(src\s*=\s*["\'])\s*javascript:/i', '$1#', $clean);
    return trim($clean);
}
function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
function require_csrf() {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        // A POST that exceeded post_max_size arrives here with $_POST silently
        // emptied by PHP itself — surface that specifically, since "invalid
        // form submission" is misleading for what's really an upload-too-large.
        $content_length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if (empty($_POST) && empty($_FILES) && $content_length > 0) {
            http_response_code(413);
            die('That upload was too large for the server to accept (limit: ' . ini_get('post_max_size') . '). Please try again with fewer or smaller files.');
        }
        http_response_code(403);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}
// For state-changing admin actions triggered via GET links (toggle/delete/move),
// so they can't be forged by a third-party page (e.g. an <img> tag) while an admin is logged in.
function require_csrf_get() {
    if (!verify_csrf($_GET['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Invalid or expired link. Please refresh the page and try again.');
    }
}

// ============================================================
// AUTH HELPERS
// ============================================================
function is_logged_in() { return !empty($_SESSION['user_id']); }
function is_admin() { return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin'; }
function require_admin() {
    if (!is_admin()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}
function current_user_id() { return $_SESSION['user_id'] ?? null; }

function is_customer() { return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'customer'; }
function require_customer() {
    if (!is_customer()) {
        header('Location: ' . url('login') . '?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
}
function current_customer() {
    if (!is_customer()) return null;
    static $cached = null;
    if ($cached === null) $cached = fetch_one("SELECT * FROM users WHERE id=? AND role='customer'", 'i', current_user_id()) ?: false;
    return $cached ?: null;
}

// ============================================================
// URL HELPERS
// ============================================================
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

function current_full_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
}

// ============================================================
// SLUGS
// ============================================================
function generate_slug($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}
function unique_slug($table, $slug, $exclude_id = 0) {
    $original = $slug ?: 'item';
    $counter = 1;
    while (true) {
        $exists = fetch_one("SELECT id FROM `$table` WHERE slug = ? AND id != ?", 'si', $slug, $exclude_id);
        if (!$exists) break;
        $slug = $original . '-' . $counter++;
    }
    return $slug;
}

// ============================================================
// IMAGE UPLOAD (resize + convert to WebP when possible)
// ============================================================
function upload_image($file, $target_dir, $width = 800, $height = 800, $prefix = '', $crop = false) {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return ['error' => 'Upload failed.'];

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) return ['error' => 'Invalid file type. Only JPG, PNG, WebP, GIF allowed.'];
    if ($file['size'] > 10 * 1024 * 1024) return ['error' => 'File too large. Max 10MB.'];

    $full_dir = UPLOAD_PATH . $target_dir . '/';
    if (!is_dir($full_dir)) mkdir($full_dir, 0755, true);

    // Extension is always derived from the verified MIME type, never from the
    // client-supplied filename — otherwise an image-polyglot file (valid image
    // bytes with a disguised name like "x.php") could be stored with an
    // executable extension if GD is unavailable or resizing fails.
    $mime_ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $use_webp = extension_loaded('gd') && function_exists('imagewebp');
    $ext = $use_webp ? 'webp' : $mime_ext[$mime];
    $filename = ($prefix ? $prefix . '-' : '') . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $full_dir . $filename;

    $resized = extension_loaded('gd') ? resize_image($file['tmp_name'], $dest, $width, $height, $mime, $use_webp, $crop) : false;

    if (!$resized) {
        $ext = $mime_ext[$mime];
        $filename = ($prefix ? $prefix . '-' : '') . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $full_dir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return ['error' => 'Could not save uploaded file.'];
    }

    return ['filename' => $target_dir . '/' . $filename];
}

function resize_image($src_path, $dest_path, $target_w, $target_h, $mime, $to_webp, $crop = false) {
    $info = @getimagesize($src_path);
    if (!$info) return false;
    [$src_w, $src_h] = $info;

    switch ($mime) {
        case 'image/jpeg': $src_img = @imagecreatefromjpeg($src_path); break;
        case 'image/png':  $src_img = @imagecreatefrompng($src_path); break;
        case 'image/webp': $src_img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src_path) : false; break;
        case 'image/gif':  $src_img = @imagecreatefromgif($src_path); break;
        default: return false;
    }
    if (!$src_img) return false;

    if ($crop) {
        // Fill the exact target box: scale so the image fully covers it, then centre-crop the overflow.
        $ratio_src = $src_w / $src_h;
        $ratio_tgt = $target_w / $target_h;
        if ($ratio_src > $ratio_tgt) {
            $scale_h = $target_h;
            $scale_w = (int)round($src_w * $target_h / $src_h);
        } else {
            $scale_w = $target_w;
            $scale_h = (int)round($src_h * $target_w / $src_w);
        }
        $offset_x = (int)round(($scale_w - $target_w) / 2);
        $offset_y = (int)round(($scale_h - $target_h) / 2);

        $scaled = imagecreatetruecolor($scale_w, $scale_h);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagecopyresampled($scaled, $src_img, 0, 0, 0, 0, $scale_w, $scale_h, $src_w, $src_h);

        $canvas = imagecreatetruecolor($target_w, $target_h);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopy($canvas, $scaled, 0, 0, $offset_x, $offset_y, $target_w, $target_h);
        imagedestroy($scaled);
    } else {
        // Fit within target box, keep aspect ratio (no crop, no upscale past original)
        $scale = min($target_w / $src_w, $target_h / $src_h, 1);
        $new_w = max(1, (int)round($src_w * $scale));
        $new_h = max(1, (int)round($src_h * $scale));

        $canvas = imagecreatetruecolor($new_w, $new_h);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefilledrectangle($canvas, 0, 0, $new_w, $new_h, $transparent);
        imagecopyresampled($canvas, $src_img, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
    }

    $result = $to_webp && function_exists('imagewebp')
        ? imagewebp($canvas, $dest_path, 85)
        : (in_array($mime, ['image/png', 'image/gif']) ? imagepng($canvas, $dest_path, 7) : imagejpeg($canvas, $dest_path, 88));

    imagedestroy($src_img);
    imagedestroy($canvas);
    return $result;
}

// Builds a wa.me chat link, or null when no WhatsApp number is configured.
function whatsapp_link($message = '') {
    $number = preg_replace('/\D/', '', setting('whatsapp_number', ''));
    if ($number === '') return null;
    return 'https://wa.me/' . $number . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

function format_price($amount) {
    return setting('currency_symbol', '$') . number_format((float)$amount, 2);
}

function format_date($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function time_ago($date) {
    $diff = time() - strtotime($date);
    if ($diff < 3600) return max(1, round($diff / 60)) . 'm ago';
    if ($diff < 86400) return round($diff / 3600) . 'h ago';
    return round($diff / 86400) . 'd ago';
}

// ============================================================
// SIMPLE ANALYTICS (self-hosted — page visits + product views)
// ============================================================
function track_visit($url) {
    if (is_admin()) return; // don't let admin's own browsing skew the numbers

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    foreach (['bot', 'crawl', 'spider', 'slurp', 'preview', 'facebookexternalhit'] as $needle) {
        if (stripos($ua, $needle) !== false) return;
    }

    $today = date('Y-m-d');
    $is_unique = ($_SESSION['last_visit_date'] ?? '') !== $today;
    if ($is_unique) $_SESSION['last_visit_date'] = $today;

    insert('page_visits', [
        'url'        => mb_substr($url, 0, 500),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'referrer'   => mb_substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
        'is_unique'  => $is_unique ? 1 : 0,
    ]);
}

function track_product_view($product_id) {
    if (is_admin()) return;
    db()->query("UPDATE products SET views = views + 1 WHERE id=" . (int)$product_id);
}

function track_blog_view($post_id) {
    if (is_admin()) return;
    db()->query("UPDATE blog_posts SET views = views + 1 WHERE id=" . (int)$post_id);
}

// ============================================================
// CART (session-based guest cart)
// ============================================================
function cart_key($product_id, $variation_id) {
    return $product_id . '-' . ($variation_id ?: 0);
}

function add_to_cart($product_id, $variation_id, $qty, $customization = null) {
    $product = fetch_one("SELECT * FROM products WHERE id=? AND status='active'", 'i', $product_id);
    if (!$product) return ['error' => 'Product not available.'];

    $variation = $variation_id ? fetch_one("SELECT * FROM product_variations WHERE id=? AND product_id=? AND status='active'", 'ii', $variation_id, $product_id) : null;
    if ($variation_id && !$variation) return ['error' => 'Selected option is not available.'];

    $price = ($variation && $variation['price'] !== null)
        ? (float)($variation['sale_price'] ?: $variation['price'])
        : (float)($product['sale_price'] ?: $product['base_price']);

    $stock = $variation ? (int)$variation['stock_quantity'] : (int)$product['stock_quantity'];
    // A customized item is one-of-a-kind (its own logo/text/color), so it never
    // merges quantity with another cart line — each customization gets its own key.
    $key = $customization ? cart_key($product_id, $variation_id) . '-' . uniqid() : cart_key($product_id, $variation_id);
    $current_qty = $customization ? 0 : (int)($_SESSION['cart'][$key]['qty'] ?? 0);

    if ($product['track_stock'] && ($current_qty + $qty) > $stock) {
        return ['error' => $stock > 0 ? "Only $stock left in stock." : 'This item is out of stock.'];
    }

    $image = fetch_one("SELECT image_path FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order ASC LIMIT 1", 'i', $product_id)['image_path'] ?? '';

    if (!$customization && isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id'    => $product_id,
            'variation_id'  => $variation_id,
            'name'          => $product['name'],
            'slug'          => $product['slug'],
            'variation_key' => $variation['combination_key'] ?? null,
            'sku'           => $variation['sku'] ?? $product['sku'],
            'price'         => $price,
            'image'         => $image,
            'qty'           => $qty,
            'customization' => $customization,
        ];
    }
    track_abandoned_cart();
    return ['success' => true];
}

function get_cart() { return $_SESSION['cart'] ?? []; }

function cart_count() {
    return array_sum(array_column(get_cart(), 'qty'));
}

function cart_total() {
    $total = 0;
    foreach (get_cart() as $item) $total += $item['price'] * $item['qty'];
    return $total;
}

function update_cart_qty($key, $qty) {
    if ($qty <= 0) { remove_from_cart($key); return; }
    if (isset($_SESSION['cart'][$key])) { $_SESSION['cart'][$key]['qty'] = $qty; track_abandoned_cart(); }
}

function remove_from_cart($key) {
    unset($_SESSION['cart'][$key]);
    track_abandoned_cart();
}

function clear_cart() { $_SESSION['cart'] = []; }

// Upserts an "active" abandoned-cart row for this browser session, or marks it
// recovered once the cart is emptied (checkout completed, or items removed).
function track_abandoned_cart($email = null, $name = null, $phone = null) {
    $sid  = session_id();
    $cart = get_cart();

    if (empty($cart)) {
        db()->query("UPDATE abandoned_carts SET status='recovered' WHERE session_id='" . db()->real_escape_string($sid) . "' AND status='active'");
        return;
    }

    $existing = fetch_one("SELECT id, email, customer_name, phone FROM abandoned_carts WHERE session_id=? AND status='active'", 's', $sid);
    $data = [
        'session_id'    => $sid,
        'cart_data'     => json_encode($cart),
        'cart_total'    => cart_total(),
        'item_count'    => count($cart),
        'email'         => $email ?: ($existing['email'] ?? null),
        'customer_name' => $name ?: ($existing['customer_name'] ?? null),
        'phone'         => $phone ?: ($existing['phone'] ?? null),
    ];
    if ($existing) update_record('abandoned_carts', $data, 'id', $existing['id']);
    else insert('abandoned_carts', array_merge($data, ['status' => 'active']));
}

// ============================================================
// ORDERS
// ============================================================
function generate_order_number() {
    // random_bytes, not uniqid() — uniqid() is time-based and guessable, and this
    // number is the only thing standing between order-success.php and someone
    // else's name/address/phone (see the session check there).
    return 'ORD-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('Ymd');
}

// ============================================================
// REVIEWS
// ============================================================
function get_product_reviews($product_id) {
    return fetch_all("SELECT * FROM reviews WHERE product_id=? AND status='approved' ORDER BY created_at DESC", 'i', $product_id);
}

function get_product_rating_summary($product_id) {
    $r = fetch_one("SELECT COUNT(*) c, AVG(rating) avg_rating FROM reviews WHERE product_id=? AND status='approved'", 'i', $product_id);
    return ['count' => (int)($r['c'] ?? 0), 'avg' => round((float)($r['avg_rating'] ?? 0), 1)];
}

function star_html($rating, $size = 'text-sm') {
    $html = "<span class=\"$size\" style=\"color:#fbbf24;letter-spacing:1px\">";
    for ($i = 1; $i <= 5; $i++) $html .= $i <= round($rating) ? '★' : '☆';
    return $html . '</span>';
}

// Renders a visual progress timeline for an order's status. Cancelled/failed/
// refunded orders break out of the normal progression (a real order never
// reaches "Delivered" after being cancelled), so they get their own banner
// instead of a step being force-fit into the happy-path sequence.
function render_order_timeline($status) {
    $stopped = ['cancelled' => 'This order was cancelled.', 'failed' => 'This order failed.', 'refunded' => 'This order was refunded.'];
    if (isset($stopped[$status])) {
        return '<div class="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm font-medium flex items-center gap-2">'
             . '<i class="fa-solid fa-circle-xmark"></i> ' . h($stopped[$status]) . '</div>';
    }

    $steps = [
        'pending'    => ['icon' => 'fa-receipt',        'label' => 'Order Placed'],
        'paid'       => ['icon' => 'fa-credit-card',    'label' => 'Payment Confirmed'],
        'processing' => ['icon' => 'fa-boxes-stacked',  'label' => 'Processing'],
        'shipped'    => ['icon' => 'fa-truck',          'label' => 'Shipped'],
        'delivered'  => ['icon' => 'fa-circle-check',   'label' => 'Delivered'],
    ];
    $keys = array_keys($steps);
    $current_index = array_search($status, $keys, true);
    if ($current_index === false) $current_index = 0;

    ob_start(); ?>
    <div class="flex items-start">
      <?php foreach ($keys as $i => $key): $step = $steps[$key]; $done = $i <= $current_index; ?>
      <div class="flex-1 flex flex-col items-center text-center relative">
        <?php if ($i > 0): ?><div class="absolute top-4 right-1/2 w-full h-0.5 <?= $i <= $current_index ? 'bg-ignite' : 'bg-slate-200' ?>" style="z-index:0"></div><?php endif; ?>
        <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-xs <?= $done ? 'bg-ignite text-white' : 'bg-slate-100 text-slate-400' ?>">
          <i class="fa-solid <?= $step['icon'] ?>"></i>
        </div>
        <span class="text-[11px] mt-2 font-medium <?= $done ? 'text-ink' : 'text-slate-400' ?>"><?= $step['label'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
}

function default_robots_txt() {
    $base = rtrim(parse_url(SITE_URL, PHP_URL_PATH) ?? '', '/');
    $rules = ['/admin/', '/includes/', '/database/', '/logs/', '/cron/', '/tools/', '/cart', '/checkout', '/account', '/wishlist', '/login', '/register', '/order-success', '/invoice/', '/cart-restore/', '/search', '/*?*sort=', '/*?*min=', '/*?*max=', '/customizer-upload.php', '/customizer-vectorize.php'];
    $out = "User-agent: *\nAllow: /\n";
    foreach ($rules as $r) $out .= 'Disallow: ' . $base . $r . "\n";
    // Let crawlers fetch the CSS/JS/images they need to render pages.
    $out .= 'Allow: ' . $base . "/assets/\n";
    return $out . "\nSitemap: " . url('sitemap.xml') . "\n";
}

// ============================================================
// EMAIL (raw SMTP socket client — no external library needed)
// ============================================================
function send_smtp_email($to, $subject, $body_html, $overrides = []) {
    $host = $overrides['smtp_host'] ?? setting('smtp_host', '');
    $port = (int)($overrides['smtp_port'] ?? setting('smtp_port', 587));
    $user = $overrides['smtp_username'] ?? setting('smtp_username', '');
    $pass = $overrides['smtp_password'] ?? setting('smtp_password', '');
    $enc  = $overrides['smtp_encryption'] ?? setting('smtp_encryption', 'tls');
    $from_email = ($overrides['smtp_from_email'] ?? setting('smtp_from_email', '')) ?: setting('contact_email', '');
    $from_name  = ($overrides['smtp_from_name'] ?? setting('smtp_from_name', '')) ?: setting('site_name', '');

    if (!$host || !$from_email) {
        error_log("Email not sent (SMTP not configured): $subject -> $to");
        return false;
    }

    $prefix = ($enc === 'ssl') ? 'ssl://' : '';
    $sock = @fsockopen($prefix . $host, $port, $errno, $errstr, 15);
    if (!$sock) { error_log("SMTP connect failed: $errstr"); return false; }
    stream_set_timeout($sock, 15);

    $read = function () use ($sock) {
        $data = '';
        while (($line = fgets($sock, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };
    $cmd = function ($command) use ($sock, $read) {
        fwrite($sock, $command . "\r\n");
        return $read();
    };

    $read();
    $cmd('EHLO ' . (gethostname() ?: 'localhost'));
    if ($enc === 'tls') {
        $cmd('STARTTLS');
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $cmd('EHLO ' . (gethostname() ?: 'localhost'));
    }
    if ($user) {
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $auth = $cmd(base64_encode($pass));
        if (strpos($auth, '235') === false) {
            error_log('SMTP auth failed: ' . trim($auth));
            fwrite($sock, "QUIT\r\n"); fclose($sock);
            return false;
        }
    }
    $cmd("MAIL FROM:<$from_email>");
    $cmd("RCPT TO:<$to>");
    $cmd('DATA');

    $message  = 'From: =?UTF-8?B?' . base64_encode($from_name) . "?= <$from_email>\r\n";
    $message .= "To: <$to>\r\n";
    $message .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
    $message .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($body_html)) . "\r\n.";

    $result = $cmd($message);
    $cmd('QUIT');
    fclose($sock);

    return strpos($result, '250') !== false;
}

function email_layout($body_html) {
    $site_name = setting('site_name', '');
    $logo = setting('site_logo', '');
    $primary = setting('theme_primary_color', '#ff4d2e');
    $logo_html = $logo
        ? '<img src="' . UPLOAD_URL . h($logo) . '" style="height:38px" alt="' . h($site_name) . '">'
        : '<span style="font-size:20px;font-weight:700;color:#111">' . h($site_name) . '</span>';

    return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:28px 16px;">
      <div style="text-align:center;padding-bottom:20px;">' . $logo_html . '</div>
      <div style="background:#fff;border-radius:12px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.08);color:#334155;font-size:14px;line-height:1.6;">
        ' . $body_html . '
      </div>
      <p style="text-align:center;color:#94a3b8;font-size:12px;margin-top:20px;">' . h(setting('footer_copyright_text', $site_name)) . '</p>
    </div>
    </body></html>';
}

function email_button($url, $label) {
    $primary = setting('theme_primary_color', '#ff4d2e');
    return '<a href="' . h($url) . '" style="display:inline-block;background:' . h($primary) . ';color:#fff;padding:12px 26px;border-radius:999px;text-decoration:none;font-weight:700;font-size:13px;">' . h($label) . '</a>';
}

function send_order_confirmation_email($order, $items) {
    if (setting('email_order_confirmation_enabled', '1') !== '1') return false;
    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr><td style="padding:8px 0;border-bottom:1px solid #eee">' . h($item['product_name']) . ($item['variation_key'] ? ' (' . h($item['variation_key']) . ')' : '') . ' &times;' . (int)$item['quantity'] . '</td>'
               . '<td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right">' . format_price($item['subtotal']) . '</td></tr>';
    }
    $invoice_url = url('invoice/' . $order['order_number'] . '?email=' . urlencode($order['customer_email']));
    $body = '<h2 style="margin:0 0 6px;color:#111">Thank you, ' . h($order['customer_name']) . '!</h2>'
          . '<p style="color:#64748b;margin:0 0 20px">Your order <strong>' . h($order['order_number']) . '</strong> has been received and is being processed.</p>'
          . '<table style="width:100%;border-collapse:collapse;margin-bottom:16px">' . $rows
          . '<tr><td style="padding:10px 0 0;font-weight:700">Total</td><td style="padding:10px 0 0;text-align:right;font-weight:700">' . format_price($order['total']) . '</td></tr></table>'
          . '<p style="margin:20px 0">' . email_button($invoice_url, 'View &amp; Print Invoice') . '</p>';

    return send_smtp_email($order['customer_email'], 'Order Confirmation - ' . $order['order_number'], email_layout($body));
}

function send_order_status_email($order, $new_status) {
    if (setting('email_order_status_update_enabled', '1') !== '1') return false;
    if (!in_array($new_status, ['paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])) return false;
    $messages = [
        'paid'       => 'We\'ve confirmed your payment for this order.',
        'processing' => 'Your order is now being prepared.',
        'shipped'    => 'Your order is on its way!',
        'delivered'  => 'Your order has been delivered. We hope you love it!',
        'cancelled'  => 'Your order has been cancelled.',
        'refunded'   => 'Your order has been refunded.',
    ];
    $invoice_url = url('invoice/' . $order['order_number'] . '?email=' . urlencode($order['customer_email']));
    $body = '<h2 style="margin:0 0 6px;color:#111">Order Update</h2>'
          . '<p style="color:#64748b;margin:0 0 20px">Order <strong>' . h($order['order_number']) . '</strong> is now <strong>' . h(ucfirst($new_status)) . '</strong>.</p>'
          . '<p style="margin:0 0 20px">' . h($messages[$new_status]) . '</p>'
          . '<p style="margin:20px 0">' . email_button($invoice_url, 'View Order') . '</p>';
    return send_smtp_email($order['customer_email'], 'Order ' . h(ucfirst($new_status)) . ' - ' . $order['order_number'], email_layout($body));
}

function send_admin_new_order_email($order) {
    if (setting('email_admin_new_order_enabled', '1') !== '1') return false;
    $admin_email = setting('contact_email', '');
    if (!$admin_email) return false;
    $body = '<h2 style="margin:0 0 10px;color:#111">New Order Received</h2>'
          . '<p style="margin:0 0 4px"><strong>' . h($order['order_number']) . '</strong> from ' . h($order['customer_name']) . '</p>'
          . '<p style="margin:0 0 20px;color:#64748b">' . format_price($order['total']) . ' &middot; ' . h($order['customer_email']) . '</p>'
          . email_button(ADMIN_URL . '/pages/order-detail.php?id=' . (int)$order['id'], 'View Order');
    return send_smtp_email($admin_email, 'New Order: ' . $order['order_number'], email_layout($body));
}

// Renders "Download Vector" links for every logo that has one (either the
// shopper's own SVG or one vtracer auto-generated), for the production team.
function customization_vector_links_html($logo_vectors) {
    if (empty($logo_vectors) || !is_array($logo_vectors)) return '';
    $links = [];
    foreach ($logo_vectors as $i => $lv) {
        if (empty($lv['vector_path'])) continue;
        $label = 'Logo ' . ($i + 1) . ' Vector' . (!empty($lv['is_original_vector']) ? ' (customer-supplied)' : ' (auto-generated)');
        $links[] = '<a href="' . h(UPLOAD_URL . $lv['vector_path']) . '" target="_blank" style="color:#4f46e5">' . h($label) . '</a>';
    }
    return $links ? '<p style="margin:0 0 4px;color:#64748b">' . implode('<br>', $links) . '</p>' : '';
}

// Fires the moment a customer finishes a product customization and adds it to
// cart — independent of checkout, so the admin can follow up even if the
// shopper never completes the order (the highest-intent kind of abandoned cart).
function send_admin_new_customization_email($product_name, $customization) {
    if (setting('email_admin_new_customization_enabled', '1') !== '1') return false;
    $admin_email = setting('contact_email', '');
    if (!$admin_email) return false;

    $wa_number = preg_replace('/\D/', '', $customization['whatsapp'] ?? '');
    $wa_link = $wa_number !== '' ? 'https://wa.me/' . $wa_number : null;
    $front_url = !empty($customization['preview_path']) ? UPLOAD_URL . $customization['preview_path'] : null;
    $back_url  = !empty($customization['preview_back_path']) ? UPLOAD_URL . $customization['preview_back_path'] : null;
    $back_text = trim(($customization['back_name'] ?? '') . ' ' . ($customization['back_number'] ?? ''));

    $body = '<h2 style="margin:0 0 10px;color:#111">New Custom Design</h2>'
          . '<p style="margin:0 0 4px"><strong>' . h($product_name) . '</strong></p>'
          . ($customization['color'] ? '<p style="margin:0 0 4px;color:#64748b">Variation Color: ' . h($customization['color']) . '</p>' : '')
          . ($customization['garment_color'] ? '<p style="margin:0 0 4px;color:#64748b">Recolored To: ' . h($customization['garment_color']) . '</p>' : '')
          . (!empty($customization['font']) ? '<p style="margin:0 0 4px;color:#64748b">Font: ' . h($customization['font']) . '</p>' : '')
          . (!empty($customization['front_logo_count']) ? '<p style="margin:0 0 4px;color:#64748b">Front Logos: ' . (int)$customization['front_logo_count'] . '</p>' : '')
          . (!empty($customization['front_number_enabled']) && $customization['front_number'] !== '' ? '<p style="margin:0 0 4px;color:#64748b">Front Number: "' . h($customization['front_number']) . '"</p>' : '')
          . ($back_text !== '' ? '<p style="margin:0 0 4px;color:#64748b">Back: "' . h($back_text) . '"</p>' : '')
          . customization_vector_links_html($customization['logo_vectors'] ?? [])
          . implode('', array_map(fn($l) => '<p style="margin:0 0 4px;color:#64748b">' . h($l[0]) . ': ' . h($l[1]) . '</p>', array_filter(customization_detail_lines($customization), fn($l) => in_array($l[0], ['Studio', 'Base colour', 'Sleeves', 'Collar / trim', 'Pattern', 'Notes'], true))))
          . implode('', array_map(fn($label, $path) => '<p style="margin:0 0 4px"><a href="' . h(UPLOAD_URL . $path) . '" style="color:#4f46e5">' . h($label) . '</a></p>', array_keys($r = array_diff_key(customization_images($customization), ['Front mockup' => 1, 'Back mockup' => 1])), $r))
          . '<p style="margin:0 0 4px;color:#64748b">Email: ' . h($customization['email'] ?? '') . '</p>'
          . '<p style="margin:0 0 20px;color:#64748b">WhatsApp: ' . h($customization['whatsapp'] ?? '') . '</p>'
          . ($front_url ? '<p style="margin:0 0 8px"><img src="' . h($front_url) . '" style="max-width:220px;border-radius:8px;border:1px solid #eee"></p>' : '')
          . ($back_url ? '<p style="margin:0 0 20px"><img src="' . h($back_url) . '" style="max-width:220px;border-radius:8px;border:1px solid #eee"></p>' : '')
          . ($wa_link ? email_button($wa_link, 'Chat on WhatsApp') : '');

    return send_smtp_email($admin_email, 'New Custom Design: ' . $product_name, email_layout($body));
}

// One consolidated email for a whole team roster order, instead of one email
// per player — the admin sees the full roster and shared design at a glance.
function send_admin_new_team_order_email($product_name, $email, $whatsapp, $roster, $front_preview_path, $font = '', $logo_vectors = []) {
    if (setting('email_admin_new_customization_enabled', '1') !== '1') return false;
    $admin_email = setting('contact_email', '');
    if (!$admin_email) return false;

    $wa_number = preg_replace('/\D/', '', $whatsapp);
    $wa_link = $wa_number !== '' ? 'https://wa.me/' . $wa_number : null;
    $front_url = $front_preview_path ? UPLOAD_URL . $front_preview_path : null;
    $roster_html = '<ul style="margin:0 0 16px;padding-left:18px">' . implode('', array_map(fn($r) => '<li>' . h($r) . '</li>', $roster)) . '</ul>';

    $body = '<h2 style="margin:0 0 10px;color:#111">New Team Order — ' . count($roster) . ' Player' . (count($roster) === 1 ? '' : 's') . '</h2>'
          . '<p style="margin:0 0 4px"><strong>' . h($product_name) . '</strong></p>'
          . ($font ? '<p style="margin:0 0 4px;color:#64748b">Font: ' . h($font) . '</p>' : '')
          . '<p style="margin:0 0 4px;color:#64748b">Email: ' . h($email) . '</p>'
          . '<p style="margin:0 0 16px;color:#64748b">WhatsApp: ' . h($whatsapp) . '</p>'
          . $roster_html
          . customization_vector_links_html($logo_vectors)
          . ($front_url ? '<p style="margin:0 0 20px"><img src="' . h($front_url) . '" style="max-width:220px;border-radius:8px;border:1px solid #eee"></p>' : '')
          . ($wa_link ? email_button($wa_link, 'Chat on WhatsApp') : '');

    return send_smtp_email($admin_email, 'New Team Order: ' . $product_name . ' (' . count($roster) . ')', email_layout($body));
}

function send_back_in_stock_email($product, $variation_key, $to_email) {
    $body = '<h2 style="margin:0 0 6px;color:#111">Good news — it\'s back!</h2>'
          . '<p style="color:#64748b;margin:0 0 20px">' . h($product['name']) . ($variation_key ? ' (' . h($variation_key) . ')' : '') . ' is back in stock.</p>'
          . '<p style="margin:20px 0">' . email_button(url('product/' . $product['slug']), 'Shop Now') . '</p>';
    return send_smtp_email($to_email, h($product['name']) . ' is back in stock!', email_layout($body));
}

// Call this right after a product's (or its variations') stock levels are
// saved in admin — checks for anyone waiting on a "notify me" subscription
// and, if the item is now in stock, emails them once and marks it done.
function process_stock_notifications($product_id) {
    $product = fetch_one("SELECT * FROM products WHERE id=?", 'i', $product_id);
    if (!$product || !$product['track_stock']) return;

    if ($product['stock_quantity'] > 0) {
        $pending = fetch_all("SELECT * FROM stock_notifications WHERE product_id=? AND variation_key IS NULL AND notified_at IS NULL", 'i', $product_id);
        foreach ($pending as $sub) {
            if (send_back_in_stock_email($product, null, $sub['email'])) {
                update_record('stock_notifications', ['notified_at' => date('Y-m-d H:i:s')], 'id', $sub['id']);
            }
        }
    }

    $variations = fetch_all("SELECT combination_key, stock_quantity FROM product_variations WHERE product_id=? AND stock_quantity > 0", 'i', $product_id);
    foreach ($variations as $v) {
        $pending = fetch_all("SELECT * FROM stock_notifications WHERE product_id=? AND variation_key=? AND notified_at IS NULL", 'is', $product_id, $v['combination_key']);
        foreach ($pending as $sub) {
            if (send_back_in_stock_email($product, $v['combination_key'], $sub['email'])) {
                update_record('stock_notifications', ['notified_at' => date('Y-m-d H:i:s')], 'id', $sub['id']);
            }
        }
    }
}

function send_admin_bulk_inquiry_email($inquiry_id) {
    if (setting('email_admin_bulk_inquiry_enabled', '1') !== '1') return false;
    $admin_email = setting('contact_email', '');
    if (!$admin_email) return false;
    $inq = fetch_one("SELECT * FROM bulk_inquiries WHERE id=?", 'i', $inquiry_id);
    if (!$inq) return false;
    $body = '<h2 style="margin:0 0 10px;color:#111">New Bulk Inquiry</h2>'
          . '<p style="margin:0 0 4px"><strong>' . h($inq['customer_name']) . '</strong> (' . h($inq['email']) . ')</p>'
          . ($inq['quantity_needed'] ? '<p style="margin:0 0 4px;color:#64748b">Quantity: ' . (int)$inq['quantity_needed'] . '</p>' : '')
          . '<p style="margin:0 0 20px;color:#64748b">' . nl2br(h($inq['message'] ?: '')) . '</p>'
          . email_button(ADMIN_URL . '/pages/bulk-inquiries.php', 'View in Admin');
    return send_smtp_email($admin_email, 'New Bulk Inquiry', email_layout($body));
}

// Sends the one-time "you left something in your cart" reminder. $cart_row is a
// row from abandoned_carts (must have an email); the link carries a restore
// token so clicking it repopulates the cart even from a different device.
function send_abandoned_cart_reminder_email($cart_row) {
    if (setting('abandoned_cart_reminder_enabled', '1') !== '1') return false;
    if (empty($cart_row['email'])) return false;

    $items = json_decode($cart_row['cart_data'], true) ?: [];
    if (!$items) return false;

    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr><td style="padding:8px 0;border-bottom:1px solid #eee">' . h($item['name']) . ($item['variation_key'] ? ' (' . h($item['variation_key']) . ')' : '') . ' &times;' . (int)$item['qty'] . '</td>'
               . '<td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right">' . format_price($item['price'] * $item['qty']) . '</td></tr>';
    }
    $restore_url = url('cart-restore/' . $cart_row['restore_token']);
    $name = $cart_row['customer_name'] ? h($cart_row['customer_name']) : 'there';
    $body = '<h2 style="margin:0 0 6px;color:#111">You left something behind, ' . $name . '!</h2>'
          . '<p style="color:#64748b;margin:0 0 20px">Your cart is still saved — pick up right where you left off.</p>'
          . '<table style="width:100%;border-collapse:collapse;margin-bottom:16px">' . $rows
          . '<tr><td style="padding:10px 0 0;font-weight:700">Total</td><td style="padding:10px 0 0;text-align:right;font-weight:700">' . format_price($cart_row['cart_total']) . '</td></tr></table>'
          . '<p style="margin:20px 0">' . email_button($restore_url, 'Complete Your Order') . '</p>';

    return send_smtp_email($cart_row['email'], 'You left something in your cart', email_layout($body));
}

// ============================================================
// AI SEO
// ============================================================

// Rule-based SEO score (0-100) — no AI needed to compute this.
// $has_alt_map: optional [product_id => true] lookup to avoid an extra query per product.
function calculate_seo_score($product, $has_alt_map = null) {
    $score = 0;
    $title = $product['meta_title'] ?? '';
    $desc  = $product['meta_description'] ?? '';
    $title_len = mb_strlen($title);
    $desc_len  = mb_strlen($desc);
    $keyword = mb_strtolower(trim($product['focus_keyword'] ?? ''));

    if ($title_len > 0) $score += ($title_len >= 30 && $title_len <= 65) ? 25 : 12;
    if ($desc_len > 0)  $score += ($desc_len >= 110 && $desc_len <= 160) ? 25 : 12;
    if ($keyword !== '') {
        $score += 10;
        if (mb_stripos($title, $keyword) !== false) $score += 15;
        if (mb_stripos($desc, $keyword) !== false) $score += 10;
    }

    $has_alt = $has_alt_map !== null
        ? !empty($has_alt_map[$product['id']])
        : (bool)fetch_one("SELECT id FROM product_images WHERE product_id=? AND alt_text IS NOT NULL AND alt_text<>'' LIMIT 1", 'i', $product['id']);
    if ($has_alt) $score += 10;

    if (!empty($product['short_description'])) $score += 5;

    return min(100, $score);
}

function seo_score_band($score) {
    if ($score >= 80) return ['label' => 'good', 'color' => 'emerald'];
    if ($score >= 50) return ['label' => 'ok', 'color' => 'amber'];
    return ['label' => 'poor', 'color' => 'red'];
}

// Calls the configured AI provider (OpenAI) with the admin's own API key to
// draft a meta title / description / focus keyword for one product.
function generate_seo_via_ai($product) {
    $api_key = setting('ai_api_key', '');
    if (!$api_key) return ['error' => 'No AI API key configured. Add one under the "AI Settings" tab.'];

    $model = setting('ai_model', 'gpt-4o-mini');
    $category = $product['category_id'] ? fetch_one("SELECT name FROM categories WHERE id=?", 'i', $product['category_id']) : null;
    $desc = trim(strip_tags($product['description'] ?: $product['short_description'] ?: ''));
    $desc = mb_substr($desc, 0, 600);
    $site_name = setting('site_name', '');

    $prompt = "Write SEO metadata for this e-commerce product page.\n"
        . "Site: {$site_name}\n"
        . "Product name: {$product['name']}\n"
        . "Category: " . ($category['name'] ?? 'N/A') . "\n"
        . "Product description: " . ($desc ?: 'N/A') . "\n\n"
        . "Respond with ONLY a JSON object with these exact keys:\n"
        . "meta_title (max 60 characters, compelling, includes the product name),\n"
        . "meta_description (max 160 characters, persuasive, ends with a soft call to action),\n"
        . "focus_keyword (a realistic 2-4 word buyer search phrase for this product).";

    $payload = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You are an expert e-commerce SEO copywriter. Reply with strictly valid JSON only — no markdown, no commentary.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'response_format' => ['type' => 'json_object'],
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 40,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) return ['error' => 'Connection error: ' . $curl_error];
    $data = json_decode($response, true);
    if ($http_code !== 200) return ['error' => $data['error']['message'] ?? "AI request failed (HTTP $http_code)."];

    $content = $data['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode($content, true);
    if (!$parsed || empty($parsed['meta_title'])) return ['error' => 'Could not parse the AI response.'];

    return [
        'success'          => true,
        'meta_title'       => mb_substr(trim($parsed['meta_title']), 0, 70),
        'meta_description' => mb_substr(trim($parsed['meta_description'] ?? ''), 0, 160),
        'focus_keyword'    => trim($parsed['focus_keyword'] ?? ''),
    ];
}

// ============================================================
// PAYMENT METHODS
// ============================================================
function get_active_payment_methods() {
    return fetch_all("SELECT * FROM payment_methods WHERE status='active' ORDER BY sort_order ASC, id ASC");
}

function get_payment_method($code) {
    return fetch_one("SELECT * FROM payment_methods WHERE code=?", 's', $code);
}

// ============================================================
// COUPONS
// ============================================================
function validate_coupon($code, $subtotal) {
    $code = trim($code);
    if ($code === '') return ['error' => 'Please enter a coupon code.'];

    $coupon = fetch_one("SELECT * FROM coupons WHERE code=? AND status='active'", 's', $code);
    if (!$coupon) return ['error' => 'Invalid coupon code.'];
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < strtotime(date('Y-m-d'))) return ['error' => 'This coupon has expired.'];
    if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) return ['error' => 'This coupon has reached its usage limit.'];
    if ($subtotal < $coupon['min_order']) return ['error' => 'Minimum order of ' . format_price($coupon['min_order']) . ' required for this coupon.'];

    $discount = $coupon['type'] === 'percentage' ? round($subtotal * $coupon['value'] / 100, 2) : min((float)$coupon['value'], $subtotal);
    return ['success' => true, 'discount' => $discount, 'coupon' => $coupon];
}

// ============================================================
// HOMEPAGE / STOREFRONT HELPERS
// ============================================================
function section_enabled($key) {
    return setting("section_{$key}_enabled", '1') === '1';
}

function get_active_banners($placement = 'hero') {
    return fetch_all("SELECT * FROM banners WHERE status='active' AND placement=? ORDER BY sort_order ASC, id ASC", 's', $placement);
}

// The curated, ordered list of categories chosen for the homepage grid
// (falls back to nav categories if nothing has been curated yet).
function get_homepage_categories() {
    $ids = array_filter(array_map('intval', explode(',', setting('homepage_category_ids', ''))));
    if (!$ids) return get_nav_categories();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $cats = fetch_all("SELECT * FROM categories WHERE id IN ($placeholders) AND status='active'", str_repeat('i', count($ids)), ...$ids);
    $map = array_column($cats, null, 'id');
    return array_values(array_filter(array_map(fn($id) => $map[$id] ?? null, $ids)));
}

function render_product_card($p, $badge = null) {
    $badges = ['new' => ['New', 'bg-emerald-500'], 'featured' => ['Featured', 'bg-ignite'], 'coming_soon' => ['Coming Soon', 'bg-slate-700']];
    $imgs = fetch_all("SELECT image_path, alt_text FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order ASC LIMIT 2", 'i', $p['id']);
    $thumb = $imgs[0] ?? null;
    $hover = $imgs[1] ?? null;
    $in_wishlist = is_in_wishlist($p['id']);
    $rating = get_product_rating_summary($p['id']);
    $price = $p['sale_price'] ?: $p['base_price'];
    $off = ($p['sale_price'] && $p['base_price'] > 0) ? (int)round(100 - ($p['sale_price'] / $p['base_price']) * 100) : 0;
    $model = !empty($p['is_customizable']) ? customizer_model_for($p) : null;
    ob_start(); ?>
    <article class="product-card group relative" data-reveal>
      <form method="POST" action="<?= url('wishlist-toggle') ?>" class="absolute top-3 right-3 z-20">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
        <input type="hidden" name="redirect" value="<?= h(current_full_url()) ?>">
        <button type="submit" aria-label="<?= $in_wishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>" title="<?= $in_wishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>" class="w-9 h-9 rounded-full bg-white/90 backdrop-blur shadow-sm flex items-center justify-center hover:scale-110 transition">
          <i class="fa-<?= $in_wishlist ? 'solid' : 'regular' ?> fa-heart text-sm <?= $in_wishlist ? 'text-ignite' : 'text-slate-500' ?>"></i>
        </button>
      </form>
      <a href="<?= url('product/' . $p['slug']) ?>" class="block">
        <div class="tilt-3d product-card-media aspect-[4/5] rounded-2xl overflow-hidden bg-slate-100 mb-4 relative">
          <?php if ($thumb): ?>
          <img src="<?= UPLOAD_URL . h($thumb['image_path']) ?>" alt="<?= h($thumb['alt_text'] ?: $p['name']) ?>" loading="lazy" decoding="async" width="600" height="750" class="absolute inset-0 w-full h-full object-cover transition duration-700 group-hover:scale-105<?= $hover ? ' group-hover:opacity-0' : '' ?>">
          <?php if ($hover): ?><img src="<?= UPLOAD_URL . h($hover['image_path']) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async" width="600" height="750" class="absolute inset-0 w-full h-full object-cover opacity-0 transition duration-700 group-hover:opacity-100 group-hover:scale-105"><?php endif; ?>
          <?php else: ?>
          <div class="absolute inset-0 flex items-center justify-center text-slate-300"><i class="fa-solid fa-image text-3xl"></i></div>
          <?php endif; ?>
          <div class="absolute top-3 left-3 flex flex-col gap-1.5 items-start z-10">
            <?php if ($badge && isset($badges[$badge])): ?>
            <span class="<?= $badges[$badge][1] ?> text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider"><?= $badges[$badge][0] ?></span>
            <?php endif; ?>
            <?php if ($off > 0): ?><span class="bg-ink text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">-<?= $off ?>%</span><?php endif; ?>
          </div>
          <?php if ($model && $model !== 'flat'): ?>
          <span class="absolute bottom-3 left-3 z-10 inline-flex items-center gap-1.5 bg-white/90 backdrop-blur text-ink text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider"><i class="fa-solid fa-cube text-ignite"></i> 3D Customizable</span>
          <?php endif; ?>
          <span class="product-card-cta absolute bottom-3 right-3 z-10 w-10 h-10 rounded-full bg-ignite text-white flex items-center justify-center shadow-glow"><i class="fa-solid fa-arrow-right -rotate-45 group-hover:rotate-0 transition duration-300"></i></span>
        </div>
        <h3 class="font-display font-semibold text-[15px] leading-snug text-ink group-hover:text-ignite transition line-clamp-2"><?= h($p['name']) ?></h3>
        <div class="flex items-center justify-between gap-2 mt-1.5">
          <div class="text-sm font-bold text-ink">
            <?= format_price($price) ?>
            <?php if ($p['sale_price']): ?><span class="text-xs font-medium text-slate-400 line-through ml-1"><?= format_price($p['base_price']) ?></span><?php endif; ?>
          </div>
          <?php if ($rating['count'] > 0): ?>
          <div class="text-xs text-slate-500 flex items-center gap-1" aria-label="Rated <?= $rating['avg'] ?> out of 5"><i class="fa-solid fa-star text-amber-400"></i><?= $rating['avg'] ?> <span class="text-slate-400">(<?= $rating['count'] ?>)</span></div>
          <?php endif; ?>
        </div>
      </a>
    </article>
    <?php return ob_get_clean();
}

// ============================================================
// WISHLIST (logged-in customers only)
// ============================================================
function is_in_wishlist($product_id) {
    if (!is_customer()) return false;
    return (bool)fetch_one("SELECT id FROM wishlists WHERE customer_id=? AND product_id=?", 'ii', current_user_id(), $product_id);
}
function get_wishlist_count() {
    if (!is_customer()) return 0;
    return (int)(fetch_one("SELECT COUNT(*) c FROM wishlists WHERE customer_id=?", 'i', current_user_id())['c'] ?? 0);
}

function get_testimonials() {
    return fetch_all("SELECT * FROM testimonials WHERE status='active' ORDER BY sort_order ASC, id ASC");
}

function get_certifications() {
    return fetch_all("SELECT * FROM certifications WHERE status='active' ORDER BY sort_order ASC, id ASC");
}

function subscribe_newsletter($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $stmt = db()->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE status='subscribed'");
    $stmt->bind_param('s', $email);
    return $stmt->execute();
}

function get_active_features() {
    return fetch_all("SELECT * FROM features WHERE status='active' ORDER BY sort_order ASC, id ASC");
}

function get_menu_tree() {
    $items = fetch_all(
        "SELECT m.*, c.slug AS cat_slug, c.name AS cat_name
         FROM menu_items m LEFT JOIN categories c ON c.id = m.category_id
         WHERE m.parent_id IS NULL AND m.status='active' ORDER BY m.sort_order ASC, m.id ASC"
    );
    foreach ($items as &$item) {
        $item['url'] = menu_item_url($item);
        $item['children'] = fetch_all(
            "SELECT m.*, c.slug AS cat_slug, c.name AS cat_name
             FROM menu_items m LEFT JOIN categories c ON c.id = m.category_id
             WHERE m.parent_id=? AND m.status='active' ORDER BY m.sort_order ASC, m.id ASC", 'i', $item['id']
        );
        foreach ($item['children'] as &$child) $child['url'] = menu_item_url($child);
        unset($child);
    }
    unset($item);
    return $items;
}

function menu_item_url($item) {
    if ($item['link_type'] === 'category' && !empty($item['cat_slug'])) {
        return url('category/' . $item['cat_slug']);
    }
    return $item['custom_url'] ?: '#';
}

function get_footer_columns() {
    $cols = fetch_all("SELECT * FROM footer_columns WHERE status='active' ORDER BY sort_order ASC, id ASC");
    foreach ($cols as &$col) {
        $col['links'] = fetch_all("SELECT * FROM footer_links WHERE column_id=? AND status='active' ORDER BY sort_order ASC, id ASC", 'i', $col['id']);
    }
    unset($col);
    return $cols;
}

// Admin view: all columns/links regardless of status, so nothing is hidden from management.
function get_footer_columns_full() {
    $cols = fetch_all("SELECT * FROM footer_columns ORDER BY sort_order ASC, id ASC");
    foreach ($cols as &$col) {
        $col['links'] = fetch_all("SELECT * FROM footer_links WHERE column_id=? ORDER BY sort_order ASC, id ASC", 'i', $col['id']);
    }
    unset($col);
    return $cols;
}

function get_nav_categories() {
    return fetch_all("SELECT * FROM categories WHERE parent_id IS NULL AND show_in_nav=1 AND status='active' ORDER BY nav_order ASC, name ASC");
}


// ============================================================
// DESIGN SYSTEM / ASSETS
// ============================================================
// "#ff4d2e" → "255 77 46" (space-separated, for rgb(var(--x) / alpha) in CSS).
function hex_to_rgb_triplet($hex, $fallback = '255 77 46') {
    $hex = ltrim(trim((string)$hex), '#');
    if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) return $fallback;
    return hexdec(substr($hex, 0, 2)) . ' ' . hexdec(substr($hex, 2, 2)) . ' ' . hexdec(substr($hex, 4, 2));
}

// Versioned URL for a local static file, so browsers cache it for a year but
// pick up changes immediately after a deploy.
function asset_url($path) {
    $path = ltrim($path, '/');
    $file = __DIR__ . '/../' . $path;
    return SITE_URL . '/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

// ============================================================
// SEO HELPERS
// ============================================================
// Prints a JSON-LD block. JSON_HEX_TAG keeps "</script>" in any value from
// breaking out of the tag; nulls/empty values are dropped for cleaner markup.
function json_ld($data) {
    $clean = function ($v) use (&$clean) {
        if (!is_array($v)) return $v;
        $out = [];
        foreach ($v as $k => $item) {
            $item = $clean($item);
            if ($item === null || $item === '' || $item === []) continue;
            $out[$k] = $item;
        }
        return array_keys($out) === range(0, count($out) - 1) ? array_values($out) : $out;
    };
    return '<script type="application/ld+json">' . json_encode($clean($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n";
}

// Plain-text, whitespace-collapsed, word-boundary-truncated meta text.
function meta_text($text, $max = 160) {
    // Tags become spaces first so "<p>One?</p><p>Two" doesn't collapse into "One?Two".
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('/<[^>]+>/', ' $0', (string)$text)), ENT_QUOTES, 'UTF-8')));
    if (mb_strlen($text) <= $max) return $text;
    $cut = mb_substr($text, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $max * 0.6) $cut = mb_substr($cut, 0, $space);
    return rtrim($cut, " ,.;:-") . '…';
}

// Canonical URL for the current page without tracking / sorting parameters,
// so filtered and sorted variants consolidate onto one indexable URL.
function clean_canonical($keep = ['page']) {
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $query = [];
    foreach ($keep as $k) if (isset($_GET[$k]) && $_GET[$k] !== '' && !($k === 'page' && (int)$_GET[$k] <= 1)) $query[$k] = $_GET[$k];
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path . ($query ? '?' . http_build_query($query) : '');
}

function product_brand($product) {
    return ($product['brand'] ?? '') ?: (setting('seo_default_brand', '') ?: setting('site_name', 'BuiltCo Sports'));
}

// ============================================================
// 3D DESIGN STUDIO
// ============================================================
// Which 3D model the studio shows for a product: explicit admin choice, else
// detected from the product/category name (jersey → shirt, ball → sphere,
// everything else → a 3D card of the real product photo).
function customizer_model_for($product, $category_name = null) {
    $model = $product['customizer_model'] ?? 'auto';
    if (in_array($model, ['jersey', 'ball', 'flat'], true)) return $model;
    if ($category_name === null && !empty($product['category_id'])) {
        static $cat_names = [];
        if (!isset($cat_names[$product['category_id']])) {
            $cat_names[$product['category_id']] = fetch_one("SELECT name FROM categories WHERE id=?", 'i', $product['category_id'])['name'] ?? '';
        }
        $category_name = $cat_names[$product['category_id']];
    }
    $name = strtolower(($product['name'] ?? '') . ' ' . ($product['tags'] ?? ''));
    if (preg_match('/\b(jersey|kit|uniform|shirt|t-shirt|tee|bibs?|singlet|vest|hoodie|polo|top)\b/', $name)) return 'jersey';
    if (preg_match('/\b(glove|gloves|helmet|bat|bag|belt|pad|pads|band|bands|dumbbell|shoe|boot)s?\b/', $name)) return 'flat';
    if (preg_match('/\b(ball|football|soccer|futsal|volleyball|basketball|rugby|netball|handball)\b/', $name)) return 'ball';
    if (preg_match('/\b(uniform|jersey|kit|apparel|clothing|wear)\b/', strtolower((string)$category_name))) return 'jersey';
    return 'flat';
}

function valid_hex_color($v) {
    $v = trim((string)$v);
    return preg_match('/^#[0-9a-f]{6}$/i', $v) ? strtolower($v) : '';
}

// Upload paths coming back from the browser must point at a file the
// customizer endpoint itself created — never an arbitrary path.
function valid_custom_upload_path($v) {
    $v = trim((string)$v);
    return preg_match('#^customizations/custom-[a-f0-9]{16}\.png$#', $v) ? $v : '';
}

// Normalizes the design spec posted by the 3D studio (single item or team).
function sanitize_customization_spec($d) {
    if (!is_array($d)) $d = [];
    $patterns = ['none', 'stripes', 'pinstripes', 'hoops', 'sash', 'halves', 'gradient', 'chevron', 'halftone', 'camo', 'panels'];
    $extra = [];
    foreach ((array)($d['extra_texts'] ?? []) as $t) {
        if (!is_array($t)) continue;
        $txt = mb_substr(sanitize($t['text'] ?? ''), 0, 24);
        if ($txt === '') continue;
        $extra[] = ['side' => ($t['side'] ?? '') === 'back' ? 'back' : 'front', 'text' => $txt];
        if (count($extra) >= 10) break;
    }
    $vectors = [];
    foreach ((array)($d['logo_vectors'] ?? []) as $v) {
        if (!is_array($v)) continue;
        $path = sanitize($v['vector_path'] ?? '');
        $vectors[] = [
            'vector_path'        => preg_match('#^customizer-logos/vectors/vec-[a-f0-9]{16}\.svg$#', $path) ? $path : null,
            'is_original_vector' => !empty($v['is_original_vector']),
            'side'               => ($v['side'] ?? '') === 'back' ? 'back' : 'front',
        ];
        if (count($vectors) >= 12) break;
    }
    return [
        'model'                => in_array($d['model'] ?? '', ['jersey', 'ball', 'flat'], true) ? $d['model'] : '',
        'color'                => mb_substr(sanitize($d['color'] ?? ''), 0, 60),
        'garment_color'        => valid_hex_color($d['garment_color'] ?? ''),
        'base_color'           => valid_hex_color($d['base_color'] ?? ''),
        'sleeve_color'         => valid_hex_color($d['sleeve_color'] ?? ''),
        'trim_color'           => valid_hex_color($d['trim_color'] ?? ''),
        'pattern'              => in_array($d['pattern'] ?? '', $patterns, true) ? $d['pattern'] : 'none',
        'pattern_color'        => valid_hex_color($d['pattern_color'] ?? ''),
        'font'                 => mb_substr(sanitize($d['font'] ?? ''), 0, 80),
        'text_color'           => valid_hex_color($d['text_color'] ?? ''),
        'outline_color'        => valid_hex_color($d['outline_color'] ?? ''),
        'outline_width'        => max(0, min(12, (int)($d['outline_width'] ?? 0))),
        'name_arc'             => max(0, min(100, (int)($d['name_arc'] ?? 0))),
        'front_logo_count'     => max(0, (int)($d['front_logo_count'] ?? 0)),
        'back_logo_count'      => max(0, (int)($d['back_logo_count'] ?? 0)),
        'logo_vectors'         => $vectors,
        'front_number_enabled' => !empty($d['front_number_enabled']),
        'back_name_size'       => (int)($d['back_name_size'] ?? 0),
        'back_number_size'     => (int)($d['back_number_size'] ?? 0),
        'extra_texts'          => $extra,
        'notes'                => mb_substr(sanitize($d['notes'] ?? ''), 0, 500),
        'design_front_path'    => valid_custom_upload_path($d['design_front_path'] ?? ''),
        'design_back_path'     => valid_custom_upload_path($d['design_back_path'] ?? ''),
        'render_front_path'    => valid_custom_upload_path($d['render_front_path'] ?? ''),
        'render_back_path'     => valid_custom_upload_path($d['render_back_path'] ?? ''),
        'email'                => sanitize($d['email'] ?? ''),
        'whatsapp'             => mb_substr(sanitize($d['whatsapp'] ?? ''), 0, 30),
    ];
}

// Human-readable lines describing a saved customization (cart, admin, emails).
function customization_detail_lines($cz) {
    if (!is_array($cz)) return [];
    $lines = [];
    $labels = ['jersey' => '3D Jersey', 'ball' => '3D Ball', 'flat' => 'Photo mockup'];
    if (!empty($cz['model'])) $lines[] = ['Studio', $labels[$cz['model']] ?? $cz['model']];
    if (!empty($cz['color'])) $lines[] = ['Variation', $cz['color']];
    if (!empty($cz['base_color'])) $lines[] = ['Base colour', $cz['base_color'], $cz['base_color']];
    if (!empty($cz['sleeve_color'])) $lines[] = ['Sleeves', $cz['sleeve_color'], $cz['sleeve_color']];
    if (!empty($cz['trim_color'])) $lines[] = ['Collar / trim', $cz['trim_color'], $cz['trim_color']];
    if (!empty($cz['pattern']) && $cz['pattern'] !== 'none') $lines[] = ['Pattern', ucfirst($cz['pattern']) . (!empty($cz['pattern_color']) ? ' (' . $cz['pattern_color'] . ')' : ''), $cz['pattern_color'] ?? null];
    if (!empty($cz['garment_color'])) $lines[] = ['Photo recolor', $cz['garment_color'], $cz['garment_color']];
    if (!empty($cz['font'])) $lines[] = ['Font', trim(explode(',', $cz['font'])[0], "'\" ")];
    if (!empty($cz['text_color'])) $lines[] = ['Text colour', $cz['text_color'] . (!empty($cz['outline_color']) ? ' / outline ' . $cz['outline_color'] : ''), $cz['text_color']];
    $back_text = trim(($cz['back_name'] ?? '') . ' ' . ($cz['back_number'] ?? ''));
    if ($back_text !== '') $lines[] = ['Back', '"' . $back_text . '"' . (!empty($cz['name_arc']) ? ' (arched)' : '')];
    if (!empty($cz['front_number_enabled']) && ($cz['front_number'] ?? '') !== '') $lines[] = ['Front number', $cz['front_number']];
    $logo_count = (int)($cz['front_logo_count'] ?? 0) + (int)($cz['back_logo_count'] ?? 0);
    if ($logo_count) $lines[] = ['Logos', $logo_count . ' (' . (int)($cz['front_logo_count'] ?? 0) . ' front, ' . (int)($cz['back_logo_count'] ?? 0) . ' back)'];
    foreach ((array)($cz['extra_texts'] ?? []) as $t) $lines[] = ['Text (' . $t['side'] . ')', '"' . $t['text'] . '"'];
    if (!empty($cz['notes'])) $lines[] = ['Notes', $cz['notes']];
    return $lines;
}

// [label => path] of every image stored for a customization.
function customization_images($cz) {
    $map = [
        'preview_path' => 'Front mockup', 'preview_back_path' => 'Back mockup',
        'render_front_path' => '3D front', 'render_back_path' => '3D back',
        'design_front_path' => 'Print art front', 'design_back_path' => 'Print art back',
    ];
    $out = [];
    foreach ($map as $k => $label) if (!empty($cz[$k])) $out[$label] = $cz[$k];
    return $out;
}

// ============================================================
// FLASH MESSAGES
// ============================================================
function set_flash($type, $message) { $_SESSION['flash'][$type] = $message; }
function get_flash($type) {
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}
