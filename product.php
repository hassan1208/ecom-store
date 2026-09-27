<?php
// product.php — public product detail page
require_once __DIR__ . '/includes/config.php';

$slug = sanitize($_GET['slug'] ?? '');
$product = $slug ? fetch_one("SELECT * FROM products WHERE slug=? AND status='active'", 's', $slug) : null;

if (!$product) {
    http_response_code(404);
    $meta_title = 'Product Not Found | ' . setting('site_name');
    include __DIR__ . '/includes/site-header.php';
    echo '<main class="max-w-3xl mx-auto px-4 py-24 text-center">
            <h1 class="font-display font-bold text-3xl mb-3">Product Not Found</h1>
            <p class="text-slate-500 mb-6">This product may have been removed or is no longer available.</p>
            <a href="' . url('') . '" class="text-ignite font-semibold">&larr; Back to Home</a>
          </main>';
    include __DIR__ . '/includes/site-footer.php';
    exit;
}

// --- Notify me when back in stock -----------------------------------------------
$notify_submitted = false;
$notify_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'notify_stock') {
    require_csrf();
    $notify_email = sanitize($_POST['email'] ?? '');
    $variation_key = sanitize($_POST['variation_key'] ?? '') ?: null;
    if (!filter_var($notify_email, FILTER_VALIDATE_EMAIL)) {
        $notify_error = 'Please enter a valid email address.';
    } else {
        $exists = fetch_one(
            "SELECT id FROM stock_notifications WHERE product_id=? AND email=? AND notified_at IS NULL AND " . ($variation_key ? "variation_key=?" : "variation_key IS NULL"),
            $variation_key ? 'iss' : 'is',
            ...($variation_key ? [$product['id'], $notify_email, $variation_key] : [$product['id'], $notify_email])
        );
        if (!$exists) {
            insert('stock_notifications', ['product_id' => $product['id'], 'variation_key' => $variation_key, 'email' => $notify_email]);
        }
        $notify_submitted = true;
    }
}

// Keeps only well-formed entries from the shopper's logo_vectors payload
// (each logo's auto-generated or self-supplied vector file, if any).
function sanitize_logo_vectors($arr) {
    $out = [];
    if (!is_array($arr)) return $out;
    foreach ($arr as $v) {
        if (!is_array($v)) continue;
        $out[] = [
            'vector_path'        => sanitize($v['vector_path'] ?? '') ?: null,
            'is_original_vector' => !empty($v['is_original_vector']),
        ];
    }
    return $out;
}

// --- Add to cart ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    require_csrf();
    $variation_id = (int)($_POST['variation_id'] ?? 0) ?: null;
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    $customization = null;
    if (!empty($product['is_customizable']) && ($_POST['customization_data'] ?? '') !== '') {
        $cdata = json_decode($_POST['customization_data'], true);
        if (is_array($cdata)) {
            $cust_email = sanitize($cdata['email'] ?? '');
            $cust_whatsapp = sanitize($cdata['whatsapp'] ?? '');
            if (!filter_var($cust_email, FILTER_VALIDATE_EMAIL)) {
                set_flash('error', 'Please enter a valid email so we can confirm your custom design.');
                header('Location: ' . url('product/' . $product['slug']));
                exit;
            }
            if ($cust_whatsapp === '') {
                set_flash('error', 'Please enter your WhatsApp number so we can confirm your custom design.');
                header('Location: ' . url('product/' . $product['slug']));
                exit;
            }
            $customization = [
                'color'                => sanitize($cdata['color'] ?? ''),
                'garment_color'        => sanitize($cdata['garment_color'] ?? ''),
                'font'                 => sanitize($cdata['font'] ?? ''),
                'front_logo_count'     => (int)($cdata['front_logo_count'] ?? 0),
                'logo_vectors'         => sanitize_logo_vectors($cdata['logo_vectors'] ?? []),
                'front_number_enabled' => !empty($cdata['front_number_enabled']),
                'front_number'         => sanitize($cdata['front_number'] ?? ''),
                'back_name'            => sanitize($cdata['back_name'] ?? ''),
                'back_name_size'       => (int)($cdata['back_name_size'] ?? 0),
                'back_number'          => sanitize($cdata['back_number'] ?? ''),
                'back_number_size'     => (int)($cdata['back_number_size'] ?? 0),
                'text_color'           => sanitize($cdata['text_color'] ?? ''),
                'preview_path'         => sanitize($_POST['customization_preview'] ?? ''),
                'preview_back_path'    => sanitize($_POST['customization_preview_back'] ?? ''),
                'email'                => $cust_email,
                'whatsapp'             => $cust_whatsapp,
            ];
        }
    }

    $res = add_to_cart($product['id'], $variation_id, $qty, $customization);
    if (isset($res['error'])) {
        set_flash('error', $res['error']);
        header('Location: ' . url('product/' . $product['slug']));
    } else {
        if ($customization) send_admin_new_customization_email($product['name'], $customization);
        set_flash('success', 'Added to cart.');
        header('Location: ' . url('cart'));
    }
    exit;
}

// --- Team order: one shared front design, one cart item per roster player --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'team_add_to_cart' && !empty($product['is_customizable'])) {
    require_csrf();
    $tdata = json_decode($_POST['team_data'] ?? '', true);
    $players = is_array($tdata) ? ($tdata['players'] ?? []) : [];

    $cust_email = sanitize($tdata['email'] ?? '');
    $cust_whatsapp = sanitize($tdata['whatsapp'] ?? '');
    if (!filter_var($cust_email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please enter a valid email so we can confirm your team order.');
        header('Location: ' . url('product/' . $product['slug'])); exit;
    }
    if ($cust_whatsapp === '') {
        set_flash('error', 'Please enter your WhatsApp number so we can confirm your team order.');
        header('Location: ' . url('product/' . $product['slug'])); exit;
    }
    if (!is_array($players) || !$players) {
        set_flash('error', 'Please add at least one player to the roster.');
        header('Location: ' . url('product/' . $product['slug'])); exit;
    }

    $shared_front_preview = sanitize($tdata['front_preview_path'] ?? '');
    $front_number_enabled = !empty($tdata['front_number_enabled']);
    $logo_vectors = sanitize_logo_vectors($tdata['logo_vectors'] ?? []);
    $added = 0;
    $skipped = 0;
    $roster_summary = [];
    foreach ($players as $p) {
        $p_name = sanitize($p['name'] ?? '');
        $p_number = sanitize($p['number'] ?? '');
        if ($p_name === '' && $p_number === '') continue;

        // When the number also appears on the front, each player's front design
        // is unique (carries their own number) — otherwise everyone shares one.
        $p_front_preview = $front_number_enabled ? sanitize($p['preview_front_path'] ?? '') : $shared_front_preview;

        $variation_id = !empty($p['variation_id']) ? (int)$p['variation_id'] : null;
        $customization = [
            'color'                => sanitize($tdata['color'] ?? ''),
            'garment_color'        => sanitize($tdata['garment_color'] ?? ''),
            'font'                 => sanitize($tdata['font'] ?? ''),
            'front_logo_count'     => (int)($tdata['front_logo_count'] ?? 0),
            'logo_vectors'         => $logo_vectors,
            'front_number_enabled' => $front_number_enabled,
            'front_number'         => $front_number_enabled ? $p_number : '',
            'back_name'            => $p_name,
            'back_name_size'       => (int)($tdata['back_name_size'] ?? 0),
            'back_number'          => $p_number,
            'back_number_size'     => (int)($tdata['back_number_size'] ?? 0),
            'text_color'           => sanitize($tdata['text_color'] ?? ''),
            'preview_path'         => $p_front_preview,
            'preview_back_path'    => sanitize($p['preview_back_path'] ?? ''),
            'email'                => $cust_email,
            'whatsapp'             => $cust_whatsapp,
            'team_order'           => true,
        ];
        $res = add_to_cart($product['id'], $variation_id, 1, $customization);
        if (isset($res['error'])) { $skipped++; continue; }
        $added++;
        $roster_summary[] = trim($p_name . ' ' . $p_number) . (!empty($p['size']) ? ' (' . sanitize($p['size']) . ')' : '');
    }

    if ($added > 0) {
        send_admin_new_team_order_email($product['name'], $cust_email, $cust_whatsapp, $roster_summary, $shared_front_preview, sanitize($tdata['font'] ?? ''), $logo_vectors);
        set_flash('success', $added . ' player' . ($added === 1 ? '' : 's') . ' added to cart.' . ($skipped ? " ($skipped could not be added — check stock.)" : ''));
        header('Location: ' . url('cart'));
    } else {
        set_flash('error', 'Could not add any players — please check stock and try again.');
        header('Location: ' . url('product/' . $product['slug']));
    }
    exit;
}

// --- Submit an AI design request -------------------------------------------------
$design_request_errors = [];
$design_request_submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_design_request' && !empty($product['accepts_design_requests'])) {
    require_csrf();
    $dr_name  = sanitize($_POST['dr_name'] ?? '');
    $dr_email = sanitize($_POST['dr_email'] ?? '');
    $dr_phone = sanitize($_POST['dr_phone'] ?? '');
    $dr_msg   = sanitize($_POST['dr_message'] ?? '');

    if ($dr_name === '') $design_request_errors[] = 'Please enter your name.';
    if (!filter_var($dr_email, FILTER_VALIDATE_EMAIL)) $design_request_errors[] = 'Please enter a valid email.';
    if (empty($_FILES['dr_image']['tmp_name']) || $_FILES['dr_image']['error'] !== UPLOAD_ERR_OK) $design_request_errors[] = 'Please upload an image.';

    if (!$design_request_errors) {
        $upload = upload_image($_FILES['dr_image'], 'design-requests', 2000, 2000, 'design');
        if (isset($upload['error'])) {
            $design_request_errors[] = $upload['error'];
        } else {
            insert('design_requests', [
                'product_id'          => $product['id'],
                'product_name'        => $product['name'],
                'customer_name'       => $dr_name,
                'email'                => $dr_email,
                'phone'               => $dr_phone,
                'message'             => $dr_msg,
                'original_image_path' => $upload['filename'],
            ]);
            $design_request_submitted = true;
        }
    }
}

// --- Submit a review -------------------------------------------------------------
$review_errors = [];
$review_submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    require_csrf();
    $r_name    = sanitize($_POST['customer_name'] ?? '');
    $r_email   = sanitize($_POST['customer_email'] ?? '');
    $r_rating  = (int)($_POST['rating'] ?? 0);
    $r_title   = sanitize($_POST['title'] ?? '');
    $r_comment = sanitize($_POST['comment'] ?? '');

    if ($r_name === '') $review_errors[] = 'Please enter your name.';
    if (!filter_var($r_email, FILTER_VALIDATE_EMAIL)) $review_errors[] = 'Please enter a valid email.';
    if ($r_rating < 1 || $r_rating > 5) $review_errors[] = 'Please choose a star rating.';
    if ($r_comment === '') $review_errors[] = 'Please write a short review.';

    if (!$review_errors) {
        $is_verified = (bool)fetch_one(
            "SELECT oi.id FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=? AND o.customer_email=? LIMIT 1",
            'is', $product['id'], $r_email
        );
        insert('reviews', [
            'product_id' => $product['id'], 'customer_name' => $r_name, 'customer_email' => $r_email,
            'rating' => $r_rating, 'title' => $r_title, 'comment' => $r_comment,
            'is_verified' => $is_verified ? 1 : 0, 'status' => 'pending',
        ]);
        $review_submitted = true;
    }
}

track_product_view($product['id']);

$category = $product['category_id'] ? fetch_one("SELECT name, slug FROM categories WHERE id=?", 'i', $product['category_id']) : null;
$related_products = $product['category_id'] ? fetch_all(
    "SELECT * FROM products WHERE category_id=? AND status='active' AND id!=? ORDER BY RAND() LIMIT 8",
    'ii', $product['category_id'], $product['id']
) : [];
$product_wa_link = whatsapp_link('Hi! I\'m interested in "' . $product['name'] . '" — ' . url('product/' . $product['slug']));
$images   = fetch_all("SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order ASC", 'i', $product['id']);
$spin_frames = fetch_all("SELECT image_path FROM product_spin_frames WHERE product_id=? ORDER BY frame_order ASC", 'i', $product['id']);
$spin_frame_urls = array_map(fn($f) => UPLOAD_URL . $f['image_path'], $spin_frames);
$reviews  = get_product_reviews($product['id']);
$rating   = get_product_rating_summary($product['id']);
$vtypes   = fetch_all("SELECT * FROM variation_types WHERE product_id=? ORDER BY sort_order ASC", 'i', $product['id']);
foreach ($vtypes as &$vt) {
    $vt['options'] = fetch_all("SELECT value FROM variation_options WHERE type_id=? ORDER BY sort_order ASC", 'i', $vt['id']);
    $vt['options'] = array_column($vt['options'], 'value');
}
unset($vt);
$variations = fetch_all("SELECT * FROM product_variations WHERE product_id=? AND status='active'", 'i', $product['id']);
$variation_map = [];
foreach ($variations as $v) {
    $variation_map[$v['combination_key']] = [
        'id' => $v['id'],
        'price' => $v['price'] !== null ? (float)$v['price'] : (float)($product['sale_price'] ?: $product['base_price']),
        'compare_price' => $v['price'] !== null ? (float)$v['price'] : (float)$product['base_price'],
        'sale_price' => $v['sale_price'] !== null ? (float)$v['sale_price'] : null,
        'stock' => (int)$v['stock_quantity'],
        'sku' => $v['sku'],
    ];
}

// --- Customizer: candidate base photos, each optionally tagged with the color
// and/or the garment view (front/back) it shows, so the live preview can pick
// the best-matching photo for whichever side + color the shopper has chosen.
$customizer_images = array_map(fn($img) => [
    'url'   => UPLOAD_URL . $img['image_path'],
    'color' => $img['variation_value'] ?: null,
    'view'  => $img['mockup_view'] ?: null,
], $images);
$customizer_default_image = $images ? UPLOAD_URL . $images[0]['image_path'] : '';

$qty_rules = $product['qty_price_rules'] ? json_decode($product['qty_price_rules'], true) : [];
$is_simple_out_of_stock = empty($vtypes) && $product['track_stock'] && (int)$product['stock_quantity'] <= 0;

$meta_title       = $product['meta_title'] ?: ($product['name'] . ' | ' . setting('site_name'));
// Fall back through short description, then a trimmed plain-text version of the
// full description, before ever leaving the meta description empty (an empty
// <meta name="description"> lets Google auto-generate a worse snippet).
$meta_description = $product['meta_description']
    ?: $product['short_description']
    ?: mb_substr(trim(strip_tags((string)$product['description'])), 0, 160);
$canonical_url    = $product['canonical_url'] ?: url('product/' . $product['slug']);
$og_image         = !empty($images) ? UPLOAD_URL . $images[0]['image_path'] : null;

include __DIR__ . '/includes/site-header.php';

$display_price = $product['sale_price'] ?: $product['base_price'];
$display_compare = $product['sale_price'] ? $product['base_price'] : null;
?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $product['short_description'] ?: $meta_description,
    'image' => array_map(fn($i) => UPLOAD_URL . $i['image_path'], $images),
    'sku' => $product['sku'],
    'offers' => [
        '@type' => 'Offer',
        'url' => $canonical_url,
        'priceCurrency' => setting('currency_code', 'USD'),
        'price' => (string)$display_price,
        'availability' => (!$product['track_stock'] || $product['stock_quantity'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ],
] + ($rating['count'] > 0 ? [
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => (string)$rating['avg'],
        'reviewCount' => (string)$rating['count'],
    ],
] : []), JSON_UNESCAPED_SLASHES) ?>
</script>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array_values(array_filter([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('')],
        $category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $category['name'], 'item' => url('category/' . $category['slug'])] : null,
        ['@type' => 'ListItem', 'position' => $category ? 3 : 2, 'name' => $product['name']],
    ])),
], JSON_UNESCAPED_SLASHES) ?>
</script>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
  <nav class="text-xs text-slate-400 mb-6" aria-label="Breadcrumb">
    <a href="<?= url('') ?>" class="hover:text-ignite">Home</a>
    <?php if ($category): ?> / <a href="<?= url('category/' . $category['slug']) ?>" class="hover:text-ignite"><?= h($category['name']) ?></a><?php endif; ?>
    / <span class="text-slate-600"><?= h($product['name']) ?></span>
  </nav>

  <?php $success = get_flash('success'); $error = get_flash('error'); ?>
  <?php if ($error): ?><div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"><?= h($error) ?></div><?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    <!-- Gallery -->
    <div>
      <?php if (count($spin_frame_urls) >= 8): ?>
      <div id="spinViewer" data-frames="<?= h(json_encode($spin_frame_urls)) ?>" class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 mb-3 select-none" style="cursor:grab">
        <img id="spinImg" src="<?= h($spin_frame_urls[0]) ?>" alt="<?= h($product['name']) ?>" class="w-full h-full object-cover pointer-events-none" draggable="false">
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-black/60 text-white text-xs font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5 pointer-events-none">
          <i class="fa-solid fa-arrows-left-right"></i> Drag to rotate 360Â°
        </div>
      </div>
      <?php else: ?>
      <div class="tilt-3d aspect-square rounded-2xl overflow-hidden bg-slate-100 mb-3">
        <?php if ($images): ?>
        <img id="mainImage" src="<?= UPLOAD_URL . h($images[0]['image_path']) ?>" alt="<?= h($images[0]['alt_text'] ?: $product['name']) ?>" class="w-full h-full object-cover">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-image text-5xl"></i></div>
        <?php endif; ?>
      </div>
      <?php if (count($images) > 1): ?>
      <div class="grid grid-cols-5 gap-2">
        <?php foreach ($images as $img): ?>
        <button type="button" onclick="document.getElementById('mainImage').src=this.querySelector('img').src" class="aspect-square rounded-lg overflow-hidden border border-slate-200 hover:border-ignite transition">
          <img src="<?= UPLOAD_URL . h($img['image_path']) ?>" alt="" class="w-full h-full object-cover">
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div>
      <div class="flex items-start justify-between gap-3 mb-2">
        <h1 class="font-display font-bold text-3xl"><?= h($product['name']) ?></h1>
        <form method="POST" action="<?= url('wishlist-toggle') ?>" class="shrink-0">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <input type="hidden" name="redirect" value="<?= h($canonical_url) ?>">
          <?php $product_in_wishlist = is_in_wishlist($product['id']); ?>
          <button type="submit" title="<?= $product_in_wishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>" class="w-11 h-11 rounded-full border border-slate-200 flex items-center justify-center hover:border-ignite transition">
            <i class="fa-<?= $product_in_wishlist ? 'solid' : 'regular' ?> fa-heart <?= $product_in_wishlist ? 'text-ignite' : 'text-slate-400' ?>"></i>
          </button>
        </form>
      </div>
      <?php if ($rating['count'] > 0): ?>
      <a href="#reviews" class="flex items-center gap-2 mb-3 text-sm">
        <?= star_html($rating['avg']) ?>
        <span class="font-semibold text-slate-700"><?= $rating['avg'] ?></span>
        <span class="text-slate-400">(<?= $rating['count'] ?> review<?= $rating['count'] != 1 ? 's' : '' ?>)</span>
      </a>
      <?php endif; ?>
      <?php if ($product['short_description']): ?><p class="text-slate-500 mb-4"><?= h($product['short_description']) ?></p><?php endif; ?>

      <div class="flex items-baseline gap-3 mb-6">
        <span id="priceDisplay" class="text-2xl font-bold text-ink"><?= format_price($display_price) ?></span>
        <?php if ($display_compare): ?><span id="compareDisplay" class="text-base text-slate-400 line-through"><?= format_price($display_compare) ?></span><?php endif; ?>
      </div>

      <form method="POST" id="addToCartForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="formActionInput" value="add_to_cart">
        <input type="hidden" name="variation_id" id="variationIdInput" value="">
        <?php if (!empty($product['is_customizable'])): ?>
        <input type="hidden" name="customization_preview" id="customizationPreviewInput" value="">
        <input type="hidden" name="customization_preview_back" id="customizationPreviewBackInput" value="">
        <input type="hidden" name="customization_data" id="customizationDataInput" value="">
        <input type="hidden" name="team_data" id="teamDataInput" value="">
        <?php endif; ?>

        <?php foreach ($vtypes as $vt): ?>
        <div class="mb-4">
          <label class="block text-sm font-semibold text-slate-700 mb-1.5"><?= h($vt['name']) ?></label>
          <select class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite variation-select" data-type="<?= h($vt['name']) ?>" onchange="updateVariation()">
            <?php foreach ($vt['options'] as $opt): ?>
            <option value="<?= h($opt) ?>"><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($product['is_customizable'])): ?>
        <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50/40 p-3 max-w-[320px]">
          <h3 class="text-sm font-bold text-slate-800 mb-1"><i class="fa-solid fa-wand-magic-sparkles text-indigo-500 mr-1"></i> Customize Yours</h3>
          <p class="text-xs text-slate-500 mb-2">Front: your logo(s). Back: name &amp; number. Drag to position.</p>

          <!-- Garment color -->
          <div class="flex items-center gap-2 mb-2 bg-white rounded-lg border border-slate-200 px-2.5 py-1.5">
            <input type="checkbox" id="custColorToggle" class="w-3.5 h-3.5 rounded accent-indigo-500">
            <label for="custColorToggle" class="text-xs font-semibold text-slate-600 flex-1">Recolor garment</label>
            <input type="color" id="custGarmentColor" value="#ff4d2e" class="w-7 h-6 rounded border border-slate-300 p-0">
          </div>

          <!-- Font (applies to name/number everywhere) -->
          <div class="mb-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Font</label>
            <select id="custFont" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
              <option value="'Arial Black', Arial, sans-serif">Arial Black</option>
              <option value="Impact, sans-serif">Impact</option>
              <option value="Oswald, sans-serif" selected>Oswald</option>
              <option value="Anton, sans-serif">Anton</option>
              <option value="'Bebas Neue', sans-serif">Bebas Neue</option>
            </select>
          </div>

          <!-- Front / Back tabs -->
          <div class="flex gap-1 mb-2">
            <button type="button" id="tabFrontBtn" class="flex-1 text-xs font-semibold py-1.5 rounded-lg bg-indigo-500 text-white transition">Front</button>
            <button type="button" id="tabBackBtn" class="flex-1 text-xs font-semibold py-1.5 rounded-lg bg-white text-slate-500 border border-slate-200 transition">Back</button>
          </div>

          <div class="relative w-full max-w-[220px] mx-auto aspect-square rounded-lg border border-slate-200 bg-white mb-3 overflow-hidden">
            <canvas id="customizerCanvasFront" width="800" height="800" class="absolute inset-0 w-full h-full cursor-move"></canvas>
            <canvas id="customizerCanvasBack" width="800" height="800" class="absolute inset-0 w-full h-full cursor-move hidden"></canvas>
          </div>

          <!-- Front: multiple logos + optional front number -->
          <div id="frontControls">
            <div id="frontLogosList" class="space-y-1.5 mb-2"></div>
            <button type="button" id="addLogoBtn" class="text-xs font-semibold text-indigo-600"><i class="fa-solid fa-circle-plus mr-1"></i>Add Logo (team, sponsor…)</button>
            <input type="file" id="custLogoInput" accept="image/*,.svg" class="hidden">
            <p class="text-[10px] text-slate-400 mt-1 mb-3">Have a vector (.svg)? Upload it directly for the sharpest print. Otherwise we'll auto-generate a print-ready vector from your image.</p>

            <div class="flex items-center gap-2 bg-white rounded-lg border border-slate-200 px-2.5 py-1.5">
              <input type="checkbox" id="custFrontNumberToggle" class="w-3.5 h-3.5 rounded accent-indigo-500">
              <label for="custFrontNumberToggle" class="text-xs font-semibold text-slate-600">Also put the number on the front</label>
            </div>
            <div id="custFrontNumberWrap" class="hidden mt-2">
              <input type="text" id="custFrontNumber" maxlength="3" placeholder="7" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
          </div>

          <!-- Back: name + number -->
          <div id="backControls" class="hidden space-y-2">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Name</label>
              <input type="text" id="custBackName" maxlength="15" placeholder="e.g. RONALDO" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Name Size</label>
              <input type="range" id="custNameSize" min="20" max="90" value="50" class="w-full">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Number</label>
              <input type="text" id="custBackNumber" maxlength="3" placeholder="7" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Number Size</label>
              <input type="range" id="custNumberSize" min="60" max="220" value="150" class="w-full">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Text Color</label>
              <input type="color" id="custTextColor" value="#ffffff" class="w-full h-8 rounded-lg border border-slate-300 p-0">
            </div>
          </div>

          <div class="grid grid-cols-1 gap-2 mt-3 pt-3 border-t border-indigo-200">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Your Email *</label>
              <input type="email" id="custEmail" placeholder="you@example.com" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Your WhatsApp *</label>
              <input type="text" id="custWhatsapp" placeholder="03xx-xxxxxxx" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
          </div>
          <p class="text-[11px] text-slate-400 mt-1.5">So we can confirm your design with you before it goes into production.</p>

          <button type="button" id="teamOrderToggleBtn" class="text-xs font-semibold text-indigo-600 underline mt-3"><i class="fa-solid fa-users mr-1"></i>Ordering for a team? Add multiple players →</button>

          <div id="teamOrderPanel" class="hidden mt-3 pt-3 border-t border-indigo-200">
            <h4 class="text-xs font-bold text-slate-700 mb-1">Team Roster</h4>
            <p class="text-[11px] text-slate-400 mb-2">Same design for everyone — the position/size you set on the Back tab is used for each player's name &amp; number.</p>
            <div id="rosterRows" class="space-y-1.5 mb-2"></div>
            <button type="button" id="addPlayerBtn" class="text-xs font-semibold text-indigo-600 mb-3"><i class="fa-solid fa-circle-plus mr-1"></i>Add Player</button>
            <button type="button" id="teamAddToCartBtn" class="w-full inline-flex items-center justify-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3 rounded-full transition">
              <i class="fa-solid fa-cart-plus"></i> Add Whole Team to Cart
            </button>
          </div>
        </div>
        <?php endif; ?>

        <div id="stockNote" class="text-xs font-medium mb-4"></div>

        <div id="singleOrderControls">
          <div class="flex items-center gap-3 mb-6">
            <label class="text-sm font-semibold text-slate-700">Qty</label>
            <input type="number" name="quantity" id="qtyInput" value="1" min="1" class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm text-center">
          </div>

          <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" id="addToCartBtn" class="flex-1 inline-flex items-center justify-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full transition">
              <i class="fa-solid fa-cart-plus"></i> Add to Cart
            </button>
            <?php if ($product['show_bulk_dm']): ?>
            <a href="<?= url('contact') ?>" class="flex-1 inline-flex items-center justify-center gap-2 border-2 border-ink text-ink font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full hover:bg-ink hover:text-white transition">
              <i class="fa-solid fa-boxes-stacked"></i> Request Bulk Quote
            </a>
            <?php endif; ?>
          </div>
          <?php if ($product_wa_link): ?>
          <a href="<?= h($product_wa_link) ?>" target="_blank" rel="noopener" class="mt-3 w-full inline-flex items-center justify-center gap-2 border-2 border-[#25D366] text-[#128C4A] font-display font-semibold uppercase tracking-wide text-sm px-7 py-3 rounded-full hover:bg-[#25D366] hover:text-white transition">
            <i class="fa-brands fa-whatsapp text-lg"></i> Chat on WhatsApp
          </a>
          <?php endif; ?>
        </div>
      </form>

      <div id="notifyStockWrap" class="<?= $is_simple_out_of_stock ? '' : 'hidden' ?> mt-3">
        <?php if ($notify_submitted): ?>
        <p class="text-sm text-emerald-600 font-medium"><i class="fa-solid fa-circle-check mr-1"></i>We'll email you when it's back in stock.</p>
        <?php else: ?>
        <p class="text-xs font-semibold text-slate-500 mb-2">Out of stock — get notified when it's back:</p>
        <form method="POST" class="flex gap-2">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="notify_stock">
          <input type="hidden" name="variation_key" id="notifyVariationKey" value="">
          <input type="email" name="email" required placeholder="Your email" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite">
          <button type="submit" class="px-4 rounded-lg bg-ink hover:bg-slate-800 text-white text-sm font-semibold whitespace-nowrap">Notify Me</button>
        </form>
        <?php if ($notify_error): ?><p class="text-xs text-red-600 mt-1.5"><?= h($notify_error) ?></p><?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if ($qty_rules): ?>
      <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50/50 p-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-amber-700 mb-3"><i class="fa-solid fa-boxes-stacked mr-1"></i>Bulk Pricing</h3>
        <table class="w-full text-sm">
          <?php foreach ($qty_rules as $r): ?>
          <tr class="border-t border-amber-100 first:border-0">
            <td class="py-1.5 text-slate-600">Buy <?= (int)$r['min_qty'] ?>+</td>
            <td class="py-1.5 text-right font-semibold text-slate-800"><?= format_price($r['price']) ?> / unit</td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php endif; ?>

      <?php if ($product['sku']): ?><p class="text-xs text-slate-400 mt-6">SKU: <span id="skuDisplay"><?= h($product['sku']) ?></span></p><?php endif; ?>
    </div>
  </div>

  <?php if (!empty($product['accepts_design_requests'])): ?>
  <div class="mt-16 max-w-2xl rounded-2xl border border-indigo-200 bg-indigo-50/40 p-6">
    <h2 class="font-display font-bold text-xl mb-1"><i class="fa-solid fa-image text-indigo-500 mr-1.5"></i>Get a Custom Design From Your Photo</h2>
    <p class="text-sm text-slate-500 mb-5">Upload a reference photo or AI-generated design — our team will vectorize it and send you front, back &amp; sleeve mockups before you order.</p>

    <?php if ($design_request_submitted): ?>
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm">
      <i class="fa-solid fa-circle-check mr-1"></i>Thanks! We received your design. We'll email you the mockups shortly.
    </div>
    <?php else: ?>
    <?php if ($design_request_errors): ?>
    <div class="rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm mb-4">
      <ul class="list-disc pl-4 space-y-0.5"><?php foreach ($design_request_errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="space-y-3">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="submit_design_request">
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Your Photo / Design *</label>
        <input type="file" name="dr_image" accept="image/*" required class="w-full text-sm">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input type="text" name="dr_name" placeholder="Your name" required value="<?= h($_POST['dr_name'] ?? '') ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input type="email" name="dr_email" placeholder="Your email" required value="<?= h($_POST['dr_email'] ?? '') ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
      </div>
      <input type="text" name="dr_phone" placeholder="Phone (optional)" value="<?= h($_POST['dr_phone'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
      <textarea name="dr_message" rows="2" placeholder="Anything we should know? (colors, placement, style...)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= h($_POST['dr_message'] ?? '') ?></textarea>
      <button type="submit" class="inline-flex items-center gap-2 bg-ink hover:bg-slate-800 text-white font-display font-semibold uppercase tracking-wide text-xs px-6 py-3 rounded-full transition">
        <i class="fa-solid fa-paper-plane"></i> Send My Design
      </button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($product['description']): ?>
  <div class="mt-16 max-w-3xl">
    <h2 class="font-display font-bold text-xl mb-4">Description</h2>
    <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed"><?= $product['description'] ?></div>
  </div>
  <?php endif; ?>

  <?php if ($related_products): ?>
  <div class="mt-16">
    <h2 class="font-display font-bold text-xl mb-6">You May Also Like</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
      <?php foreach ($related_products as $rp): ?>
      <?= render_product_card($rp) ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Reviews -->
  <div id="reviews" class="mt-16 max-w-3xl">
    <div class="flex items-center gap-4 mb-8">
      <h2 class="font-display font-bold text-xl">Customer Reviews</h2>
      <?php if ($rating['count'] > 0): ?>
      <div class="flex items-center gap-2 text-sm"><?= star_html($rating['avg']) ?> <span class="font-semibold"><?= $rating['avg'] ?></span> <span class="text-slate-400">(<?= $rating['count'] ?>)</span></div>
      <?php endif; ?>
    </div>

    <?php if ($reviews): ?>
    <div class="space-y-6 mb-10">
      <?php foreach ($reviews as $rv): ?>
      <div class="border-b border-slate-100 pb-6 last:border-0">
        <div class="flex items-center gap-2 mb-1">
          <?= star_html($rv['rating']) ?>
          <?php if ($rv['is_verified']): ?><span class="text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Verified Purchase</span><?php endif; ?>
        </div>
        <?php if ($rv['title']): ?><h3 class="font-semibold text-sm text-slate-800 mb-1"><?= h($rv['title']) ?></h3><?php endif; ?>
        <p class="text-sm text-slate-600 leading-relaxed mb-2"><?= h($rv['comment']) ?></p>
        <div class="text-xs text-slate-400"><?= h($rv['customer_name']) ?> &middot; <?= format_date($rv['created_at']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-sm text-slate-400 mb-10">No reviews yet. Be the first to share your experience.</p>
    <?php endif; ?>

    <div class="rounded-2xl border border-slate-200 p-6 max-w-lg">
      <h3 class="font-display font-semibold text-lg mb-4">Write a Review</h3>
      <?php if ($review_submitted): ?>
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm">Thanks! Your review has been submitted and will appear once approved.</div>
      <?php else: ?>
      <?php if ($review_errors): ?>
      <div class="rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm mb-4">
        <ul class="list-disc pl-4 space-y-0.5"><?php foreach ($review_errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>
      <form method="POST" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="submit_review">
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Your Rating</label>
          <div class="flex gap-1" id="starPicker">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="star-btn text-2xl text-slate-300" data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)">â˜…</button>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="ratingInput" value="0">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input type="text" name="customer_name" placeholder="Your name" required value="<?= h($_POST['customer_name'] ?? '') ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <input type="email" name="customer_email" placeholder="Your email" required value="<?= h($_POST['customer_email'] ?? '') ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <input type="text" name="title" placeholder="Review title (optional)" value="<?= h($_POST['title'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <textarea name="comment" rows="3" placeholder="Share your experience..." required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= h($_POST['comment'] ?? '') ?></textarea>
        <button type="submit" class="inline-flex items-center gap-2 bg-ink hover:bg-slate-800 text-white font-display font-semibold uppercase tracking-wide text-xs px-6 py-3 rounded-full transition">Submit Review</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
function setRating(v) {
  document.getElementById('ratingInput').value = v;
  document.querySelectorAll('.star-btn').forEach(b => b.classList.toggle('text-amber-400', +b.dataset.val <= v) || b.classList.toggle('text-slate-300', +b.dataset.val > v));
}
</script>

<script>
const variationMap = <?= json_encode($variation_map, JSON_UNESCAPED_SLASHES) ?>;
const basePrice = <?= json_encode((float)$display_price) ?>;
const baseCompare = <?= json_encode($display_compare ? (float)$display_compare : null) ?>;
const baseSku = <?= json_encode($product['sku'] ?: '') ?>;
const trackStock = <?= $product['track_stock'] ? 'true' : 'false' ?>;
const baseStock = <?= (int)$product['stock_quantity'] ?>;
const currencySymbol = <?= json_encode(setting('currency_symbol', '$')) ?>;

function fmtPrice(n) { return currencySymbol + Number(n).toFixed(2); }

function updateVariation() {
  const selects = document.querySelectorAll('.variation-select');
  if (!selects.length) {
    // Simple product with no variations — check stock against the base product itself.
    const btn = document.getElementById('addToCartBtn');
    const note = document.getElementById('stockNote');
    const qtyInput = document.getElementById('qtyInput');
    if (trackStock && baseStock <= 0) {
      note.textContent = 'Out of stock.'; note.className = 'text-xs font-medium mb-4 text-red-600';
      btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed');
      qtyInput.max = 0;
    } else if (trackStock && baseStock <= 5) {
      note.textContent = `Only ${baseStock} left in stock.`; note.className = 'text-xs font-medium mb-4 text-amber-600';
      btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed');
      qtyInput.max = baseStock;
    } else {
      note.textContent = ''; btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed');
      qtyInput.removeAttribute('max');
    }
    return;
  }
  const key = Array.from(selects).map(s => s.value).join(' / ');
  const match = variationMap[key];
  const btn = document.getElementById('addToCartBtn');
  const note = document.getElementById('stockNote');
  const qtyInput = document.getElementById('qtyInput');

  if (match) {
    document.getElementById('variationIdInput').value = match.id;
    document.getElementById('priceDisplay').textContent = fmtPrice(match.sale_price ?? match.price);
    const compareEl = document.getElementById('compareDisplay');
    if (match.sale_price && match.sale_price < match.price) {
      if (compareEl) { compareEl.textContent = fmtPrice(match.price); compareEl.style.display = ''; }
    } else if (compareEl) { compareEl.style.display = 'none'; }
    if (document.getElementById('skuDisplay') && match.sku) document.getElementById('skuDisplay').textContent = match.sku;

    const notifyWrap = document.getElementById('notifyStockWrap');
    const notifyKeyInput = document.getElementById('notifyVariationKey');
    if (trackStock && match.stock <= 0) {
      note.textContent = 'Out of stock for this option.'; note.className = 'text-xs font-medium mb-4 text-red-600';
      btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed');
      qtyInput.max = 0;
      if (notifyWrap) { notifyWrap.classList.remove('hidden'); notifyKeyInput.value = key; }
    } else if (trackStock && match.stock <= 5) {
      note.textContent = `Only ${match.stock} left in stock.`; note.className = 'text-xs font-medium mb-4 text-amber-600';
      btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed');
      qtyInput.max = match.stock;
      if (notifyWrap) notifyWrap.classList.add('hidden');
    } else {
      note.textContent = ''; btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed');
      qtyInput.removeAttribute('max');
      if (notifyWrap) notifyWrap.classList.add('hidden');
    }
  } else {
    document.getElementById('variationIdInput').value = '';
    document.getElementById('priceDisplay').textContent = fmtPrice(basePrice);
    const notifyWrap = document.getElementById('notifyStockWrap');
    if (notifyWrap) notifyWrap.classList.add('hidden');
  }
}
updateVariation();
</script>

<?php if (count($spin_frame_urls) >= 8): ?>
<script>
(function () {
  const el = document.getElementById('spinViewer');
  const img = document.getElementById('spinImg');
  const frames = JSON.parse(el.dataset.frames);
  frames.forEach(src => { const im = new Image(); im.src = src; }); // preload

  let current = 0, dragging = false, startX = 0, startFrame = 0, userInteracted = false;
  const sensitivity = 6; // px of drag per frame step

  function setFrame(i) {
    current = ((i % frames.length) + frames.length) % frames.length;
    img.src = frames[current];
  }
  function pointerX(e) { return e.touches ? e.touches[0].clientX : e.clientX; }
  function start(e) {
    dragging = true; userInteracted = true; startX = pointerX(e); startFrame = current;
    el.style.cursor = 'grabbing';
  }
  function move(e) {
    if (!dragging) return;
    const delta = Math.round((pointerX(e) - startX) / sensitivity);
    setFrame(startFrame - delta);
  }
  function end() { dragging = false; el.style.cursor = 'grab'; }

  el.addEventListener('mousedown', start);
  window.addEventListener('mousemove', move);
  window.addEventListener('mouseup', end);
  el.addEventListener('touchstart', start, { passive: true });
  el.addEventListener('touchmove', e => { move(e); e.preventDefault(); }, { passive: false });
  window.addEventListener('touchend', end);

  // A brief one-time auto-spin hints that the product is draggable.
  setTimeout(() => {
    if (userInteracted) return;
    let steps = 0;
    const iv = setInterval(() => {
      if (userInteracted) { clearInterval(iv); return; }
      setFrame(current + 1); steps++;
      if (steps >= frames.length) clearInterval(iv);
    }, 55);
  }, 700);
})();
</script>
<?php endif; ?>

<?php if (!empty($product['is_customizable'])): ?>
<script>
(function () {
  const canvasFront = document.getElementById('customizerCanvasFront');
  if (!canvasFront) return;
  const canvasBack = document.getElementById('customizerCanvasBack');
  const ctxFront = canvasFront.getContext('2d');
  const ctxBack = canvasBack.getContext('2d');
  const W = canvasFront.width, H = canvasFront.height;

  const customizerImages = <?= json_encode($customizer_images, JSON_UNESCAPED_SLASHES) ?>;
  const defaultImage = <?= json_encode($customizer_default_image, JSON_UNESCAPED_SLASHES) ?>;
  const uploadUrl = <?= json_encode(url('customizer-upload.php')) ?>;
  const vectorizeUrl = <?= json_encode(url('customizer-vectorize.php')) ?>;
  const csrfTokenValue = <?= json_encode(csrf_token()) ?>;

  function colorSelect() {
    return Array.from(document.querySelectorAll('.variation-select')).find(s => (s.dataset.type || '').toLowerCase() === 'color');
  }
  const csel = colorSelect();

  // Picks the best-matching photo for a side: exact color+view tag, then
  // view-only tag, then color-only tag, then whatever the default photo is.
  function resolveImage(view, color) {
    let match = customizerImages.find(im => im.view === view && color && im.color === color);
    if (!match) match = customizerImages.find(im => im.view === view);
    if (!match && color) match = customizerImages.find(im => im.color === color);
    return match ? match.url : defaultImage;
  }

  const state = {
    activeSide: 'front',
    garmentColorOn: false,
    garmentColor: '#ff4d2e',
    font: document.getElementById('custFont').value,
    front: {
      baseImg: null, baseSrc: '', logos: [], // logos: [{img, x, y, sizePct, vectorPath, sourcePath, isOriginalVector}]
      numberEnabled: false, number: '', numberPos: { x: W / 2, y: H * 0.72 },
    },
    back: { baseImg: null, baseSrc: '', name: '', number: '', nameSize: 50, numberSize: 150, textColor: '#ffffff', namePos: { x: W / 2, y: H * 0.3 }, numberPos: { x: W / 2, y: H * 0.58 } },
  };

  function fitCover(img, w, h) {
    const ir = img.width / img.height, cr = w / h;
    let sw, sh, sx, sy;
    if (ir > cr) { sh = img.height; sw = sh * cr; sx = (img.width - sw) / 2; sy = 0; }
    else { sw = img.width; sh = sw / cr; sx = 0; sy = (img.height - sh) / 2; }
    return { sx, sy, sw, sh };
  }

  function redraw(side) {
    const ctx = side === 'front' ? ctxFront : ctxBack;
    const s = state[side];
    ctx.clearRect(0, 0, W, H);
    if (s.baseImg) {
      const f = fitCover(s.baseImg, W, H);
      ctx.drawImage(s.baseImg, f.sx, f.sy, f.sw, f.sh, 0, 0, W, H);
    } else {
      ctx.fillStyle = '#f1f5f9'; ctx.fillRect(0, 0, W, H);
    }
    if (state.garmentColorOn) {
      ctx.save();
      ctx.globalCompositeOperation = 'hue';
      ctx.fillStyle = state.garmentColor;
      ctx.fillRect(0, 0, W, H);
      ctx.restore();
    }
    if (side === 'front') {
      s.logos.forEach(l => {
        const lw = W * (l.sizePct / 100);
        const lh = lw * (l.img.height / l.img.width);
        l.w = lw; l.h = lh;
        ctx.drawImage(l.img, l.x - lw / 2, l.y - lh / 2, lw, lh);
      });
      if (s.numberEnabled) drawStrokedText(ctx, s.number, s.numberPos, state.back.numberSize, state.back.textColor);
    } else {
      drawStrokedText(ctx, s.name, s.namePos, s.nameSize, s.textColor);
      drawStrokedText(ctx, s.number, s.numberPos, s.numberSize, s.textColor);
    }
  }

  function drawStrokedText(ctx, text, pos, size, color) {
    if (!text) return;
    ctx.font = `900 ${size}px ${state.font}`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.lineWidth = Math.max(2, size / 18);
    ctx.strokeStyle = 'rgba(0,0,0,0.35)';
    ctx.strokeText(text, pos.x, pos.y);
    ctx.fillStyle = color;
    ctx.fillText(text, pos.x, pos.y);
  }

  function loadBase(side) {
    const s = state[side];
    const src = resolveImage(side, csel ? csel.value : '');
    if (src === s.baseSrc && s.baseImg) { redraw(side); return; }
    s.baseSrc = src;
    const img = new Image();
    img.onload = () => { s.baseImg = img; redraw(side); };
    img.onerror = () => { s.baseImg = null; redraw(side); };
    img.src = src;
  }
  loadBase('front'); loadBase('back');
  if (csel) csel.addEventListener('change', () => { loadBase('front'); loadBase('back'); });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => { redraw('front'); redraw('back'); });

  // ---- Garment recolor ----
  const colorToggle = document.getElementById('custColorToggle');
  const colorInput = document.getElementById('custGarmentColor');
  colorToggle.addEventListener('change', () => { state.garmentColorOn = colorToggle.checked; redraw('front'); redraw('back'); });
  colorInput.addEventListener('input', () => { state.garmentColor = colorInput.value; if (state.garmentColorOn) { redraw('front'); redraw('back'); } });

  // ---- Front / Back tabs ----
  const tabFrontBtn = document.getElementById('tabFrontBtn'), tabBackBtn = document.getElementById('tabBackBtn');
  const frontControls = document.getElementById('frontControls'), backControls = document.getElementById('backControls');
  function setTab(side) {
    state.activeSide = side;
    canvasFront.classList.toggle('hidden', side !== 'front');
    canvasBack.classList.toggle('hidden', side !== 'back');
    frontControls.classList.toggle('hidden', side !== 'front');
    backControls.classList.toggle('hidden', side !== 'back');
    tabFrontBtn.className = 'flex-1 text-xs font-semibold py-1.5 rounded-lg transition ' + (side === 'front' ? 'bg-indigo-500 text-white' : 'bg-white text-slate-500 border border-slate-200');
    tabBackBtn.className = 'flex-1 text-xs font-semibold py-1.5 rounded-lg transition ' + (side === 'back' ? 'bg-indigo-500 text-white' : 'bg-white text-slate-500 border border-slate-200');
  }
  tabFrontBtn.addEventListener('click', () => setTab('front'));
  tabBackBtn.addEventListener('click', () => setTab('back'));

  // ---- Front: multiple logos ----
  const logoInput = document.getElementById('custLogoInput');
  const addLogoBtn = document.getElementById('addLogoBtn');
  const frontLogosList = document.getElementById('frontLogosList');
  addLogoBtn.addEventListener('click', () => logoInput.click());
  logoInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      const img = new Image();
      img.onload = () => {
        const logo = { img, x: W / 2, y: H * (0.3 + state.front.logos.length * 0.12), sizePct: 22, w: 0, h: 0, vectorStatus: 'pending', vectorPath: null, isOriginalVector: false };
        state.front.logos.push(logo);
        renderLogosList(); redraw('front');
        vectorizeLogo(file, logo);
      };
      img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
    logoInput.value = '';
  });

  // Uploads the raw file in the background: if it's already an SVG it's stored
  // as-is; otherwise it's auto-traced into a vector via vtracer. Either way the
  // live canvas already shows the raster preview — this doesn't block the UI.
  function vectorizeLogo(file, logo) {
    const body = new FormData();
    body.set('csrf_token', csrfTokenValue);
    body.set('logo', file);
    logo.vectorPromise = fetch(vectorizeUrl, { method: 'POST', body })
      .then(r => r.json())
      .then(res => {
        logo.vectorStatus = res.success && res.vector_path ? 'ready' : 'failed';
        logo.vectorPath = res.vector_path || null;
        logo.isOriginalVector = !!res.is_original_vector;
        renderLogosList();
      })
      .catch(() => { logo.vectorStatus = 'failed'; renderLogosList(); });
  }
  // Waits for any still-in-flight vectorization calls before the design is
  // finalized, so a hasty "Add to Cart" click right after uploading doesn't
  // miss the auto-generated vector.
  function waitForLogoVectors(logos) {
    return Promise.all(logos.map(l => l.vectorPromise || Promise.resolve()));
  }
  function logoVectorsSummary(logos) {
    return logos.map(l => ({ vector_path: l.vectorPath, is_original_vector: l.isOriginalVector }));
  }

  function renderLogosList() {
    frontLogosList.innerHTML = '';
    state.front.logos.forEach((l, i) => {
      const row = document.createElement('div');
      row.className = 'flex items-center gap-2 p-1.5 rounded-lg bg-white border border-slate-200';
      const statusHtml = l.vectorStatus === 'pending'
        ? '<i class="fa-solid fa-spinner fa-spin text-slate-300" title="Preparing vector..."></i>'
        : l.vectorStatus === 'ready'
          ? `<i class="fa-solid fa-circle-check text-emerald-500" title="${l.isOriginalVector ? 'Your vector file' : 'Auto-vectorized'}"></i>`
          : '<i class="fa-solid fa-triangle-exclamation text-amber-400" title="Could not vectorize — raster only"></i>';
      row.innerHTML = `<img src="${l.img.src}" class="w-7 h-7 rounded object-contain border border-slate-100">
        <input type="range" min="8" max="50" value="${l.sizePct}" class="flex-1">
        <span class="shrink-0 text-xs">${statusHtml}</span>
        <button type="button" class="text-red-400 hover:text-red-600 text-xs shrink-0"><i class="fa-solid fa-xmark"></i></button>`;
      row.querySelector('input[type=range]').addEventListener('input', e => { l.sizePct = +e.target.value; redraw('front'); });
      row.querySelector('button').addEventListener('click', () => { state.front.logos.splice(i, 1); renderLogosList(); redraw('front'); });
      frontLogosList.appendChild(row);
    });
  }

  // ---- Font (applies to name/number on both sides) ----
  function loadAndRedrawFont() {
    const spec = `900 100px ${state.font}`;
    if (document.fonts && document.fonts.load) {
      document.fonts.load(spec).then(() => { redraw('front'); redraw('back'); }).catch(() => { redraw('front'); redraw('back'); });
    } else { redraw('front'); redraw('back'); }
  }
  document.getElementById('custFont').addEventListener('change', e => { state.font = e.target.value; loadAndRedrawFont(); });

  // ---- Back: name / number / color ----
  document.getElementById('custBackName').addEventListener('input', e => { state.back.name = e.target.value; redraw('back'); });
  document.getElementById('custNameSize').addEventListener('input', e => { state.back.nameSize = +e.target.value; redraw('back'); });
  document.getElementById('custBackNumber').addEventListener('input', e => { state.back.number = e.target.value; redraw('back'); });
  document.getElementById('custNumberSize').addEventListener('input', e => { state.back.numberSize = +e.target.value; redraw('back'); redraw('front'); });
  document.getElementById('custTextColor').addEventListener('input', e => { state.back.textColor = e.target.value; redraw('back'); redraw('front'); });

  // ---- Front: optional number ----
  const frontNumberToggle = document.getElementById('custFrontNumberToggle');
  const frontNumberWrap = document.getElementById('custFrontNumberWrap');
  frontNumberToggle.addEventListener('change', () => {
    state.front.numberEnabled = frontNumberToggle.checked;
    frontNumberWrap.classList.toggle('hidden', !frontNumberToggle.checked);
    redraw('front');
  });
  document.getElementById('custFrontNumber').addEventListener('input', e => { state.front.number = e.target.value; redraw('front'); });

  // ---- Drag to position (front: logos + optional number, back: name/number) ----
  function canvasPoint(canvas, e) {
    const rect = canvas.getBoundingClientRect();
    const cx = e.touches ? e.touches[0].clientX : e.clientX;
    const cy = e.touches ? e.touches[0].clientY : e.clientY;
    return { x: (cx - rect.left) * (W / rect.width), y: (cy - rect.top) * (H / rect.height) };
  }
  let dragging = null;
  function startDrag(e) {
    const side = state.activeSide;
    const canvas = side === 'front' ? canvasFront : canvasBack;
    const p = canvasPoint(canvas, e);
    if (side === 'front') {
      const s = state.front;
      const numSize = state.back.numberSize;
      if (s.numberEnabled && s.number && Math.abs(p.y - s.numberPos.y) < numSize / 2 && Math.abs(p.x - s.numberPos.x) < numSize * 1.2) {
        dragging = { side, target: s.numberPos };
      } else {
        for (let i = state.front.logos.length - 1; i >= 0; i--) {
          const l = state.front.logos[i];
          if (p.x >= l.x - l.w / 2 && p.x <= l.x + l.w / 2 && p.y >= l.y - l.h / 2 && p.y <= l.y + l.h / 2) {
            dragging = { side, target: l }; break;
          }
        }
      }
    } else {
      const s = state.back;
      if (s.number && Math.abs(p.y - s.numberPos.y) < s.numberSize / 2 && Math.abs(p.x - s.numberPos.x) < s.numberSize * 1.2) dragging = { side, target: s.numberPos };
      else if (s.name && Math.abs(p.y - s.namePos.y) < s.nameSize / 2 && Math.abs(p.x - s.namePos.x) < s.nameSize * 3) dragging = { side, target: s.namePos };
    }
    if (dragging) e.preventDefault();
  }
  function moveDrag(e) {
    if (!dragging) return;
    const canvas = dragging.side === 'front' ? canvasFront : canvasBack;
    const p = canvasPoint(canvas, e);
    dragging.target.x = p.x; dragging.target.y = p.y;
    redraw(dragging.side);
  }
  function endDrag() { dragging = null; }
  [canvasFront, canvasBack].forEach(c => {
    c.addEventListener('mousedown', startDrag);
    c.addEventListener('touchstart', startDrag, { passive: false });
  });
  window.addEventListener('mousemove', moveDrag);
  window.addEventListener('touchmove', e => { moveDrag(e); if (dragging) e.preventDefault(); }, { passive: false });
  window.addEventListener('mouseup', endDrag);
  window.addEventListener('touchend', endDrag);

  // ---- Composite + upload both sides before the item is actually added ----
  const form = document.getElementById('addToCartForm');
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const emailEl = document.getElementById('custEmail');
    const waEl = document.getElementById('custWhatsapp');
    if (!emailEl.value.trim() || !emailEl.checkValidity()) { alert('Please enter a valid email so we can confirm your custom design.'); emailEl.focus(); return; }
    if (!waEl.value.trim()) { alert('Please enter your WhatsApp number so we can confirm your custom design.'); waEl.focus(); return; }

    const btn = document.getElementById('addToCartBtn');
    const originalLabel = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparing your design...';

    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
    function uploadCanvas(canvas) {
      const body = new URLSearchParams();
      body.set('csrf_token', csrfToken);
      body.set('image_data', canvas.toDataURL('image/png'));
      return fetch(uploadUrl, { method: 'POST', body }).then(r => r.json());
    }

    Promise.all([uploadCanvas(canvasFront), uploadCanvas(canvasBack), waitForLogoVectors(state.front.logos)])
      .then(([frontRes, backRes]) => {
        if (frontRes.success) document.getElementById('customizationPreviewInput').value = frontRes.path;
        if (backRes.success) document.getElementById('customizationPreviewBackInput').value = backRes.path;
        document.getElementById('customizationDataInput').value = JSON.stringify({
          color: csel ? csel.value : '',
          garment_color: state.garmentColorOn ? state.garmentColor : '',
          font: state.font,
          front_logo_count: state.front.logos.length,
          logo_vectors: logoVectorsSummary(state.front.logos),
          front_number_enabled: state.front.numberEnabled,
          front_number: state.front.numberEnabled ? state.front.number : '',
          back_name: state.back.name,
          back_name_size: state.back.nameSize,
          back_number: state.back.number,
          back_number_size: state.back.numberSize,
          text_color: state.back.textColor,
          email: emailEl.value.trim(), whatsapp: waEl.value.trim(),
        });
      })
      .catch(() => {})
      .finally(() => { form.submit(); });
  });

  // ---- Team order: same front design, one roster row per player ----
  const teamToggleBtn = document.getElementById('teamOrderToggleBtn');
  const teamPanel = document.getElementById('teamOrderPanel');
  const singleControls = document.getElementById('singleOrderControls');
  const rosterRows = document.getElementById('rosterRows');
  const addPlayerBtn = document.getElementById('addPlayerBtn');
  const teamAddBtn = document.getElementById('teamAddToCartBtn');

  function sizeSelect() {
    return Array.from(document.querySelectorAll('.variation-select')).find(s => (s.dataset.type || '').toLowerCase() === 'size');
  }
  const ssel = sizeSelect();

  let teamMode = false;
  teamToggleBtn.addEventListener('click', () => {
    teamMode = !teamMode;
    teamPanel.classList.toggle('hidden', !teamMode);
    singleControls.classList.toggle('hidden', teamMode);
    teamToggleBtn.textContent = teamMode ? '← Back to single item' : 'Ordering for a team? Add multiple players →';
    if (teamMode && !rosterRows.children.length) { addPlayerRow(); addPlayerRow(); }
  });

  function addPlayerRow() {
    const row = document.createElement('div');
    row.className = 'flex items-center gap-1.5';
    row.innerHTML = `<input type="text" placeholder="Name" class="flex-1 min-w-0 rounded-lg border border-slate-300 px-2 py-1.5 text-xs" data-roster-name>
      <input type="text" placeholder="No." maxlength="3" class="w-12 rounded-lg border border-slate-300 px-2 py-1.5 text-xs" data-roster-number>
      ${ssel ? `<select class="w-20 rounded-lg border border-slate-300 px-1 py-1.5 text-xs" data-roster-size>${ssel.innerHTML}</select>` : ''}
      <button type="button" class="text-red-400 hover:text-red-600 text-xs shrink-0"><i class="fa-solid fa-xmark"></i></button>`;
    row.querySelector('button').addEventListener('click', () => row.remove());
    rosterRows.appendChild(row);
  }
  addPlayerBtn.addEventListener('click', addPlayerRow);

  // Resolves the variation_id for a roster row: same combination the shopper
  // picked everywhere, except Size is swapped for this player's own choice.
  function resolveVariationId(rowSize) {
    const selects = Array.from(document.querySelectorAll('.variation-select'));
    if (!selects.length) return null;
    const key = selects.map(s => ((s.dataset.type || '').toLowerCase() === 'size' && rowSize) ? rowSize : s.value).join(' / ');
    const match = variationMap[key];
    return match ? match.id : null;
  }

  teamAddBtn.addEventListener('click', function () {
    const emailEl = document.getElementById('custEmail');
    const waEl = document.getElementById('custWhatsapp');
    if (!emailEl.value.trim() || !emailEl.checkValidity()) { alert('Please enter a valid email so we can confirm your team order.'); emailEl.focus(); return; }
    if (!waEl.value.trim()) { alert('Please enter your WhatsApp number so we can confirm your team order.'); waEl.focus(); return; }

    const rows = Array.from(rosterRows.children).map(row => ({
      name: row.querySelector('[data-roster-name]').value.trim(),
      number: row.querySelector('[data-roster-number]').value.trim(),
      size: row.querySelector('[data-roster-size]') ? row.querySelector('[data-roster-size]').value : '',
    })).filter(r => r.name || r.number);
    if (!rows.length) { alert('Please add at least one player (name or number).'); return; }

    const originalLabel = teamAddBtn.innerHTML;
    teamAddBtn.disabled = true;
    teamAddBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding team...';

    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
    function uploadCanvas(canvas) {
      const body = new URLSearchParams();
      body.set('csrf_token', csrfToken);
      body.set('image_data', canvas.toDataURL('image/png'));
      return fetch(uploadUrl, { method: 'POST', body }).then(r => r.json());
    }

    const savedBackName = state.back.name, savedBackNumber = state.back.number, savedFrontNumber = state.front.number;
    function finishTeamSubmit(players, sharedFrontPath) {
      state.back.name = savedBackName; state.back.number = savedBackNumber; redraw('back');
      state.front.number = savedFrontNumber; redraw('front');
      document.getElementById('teamDataInput').value = JSON.stringify({
        color: csel ? csel.value : '',
        garment_color: state.garmentColorOn ? state.garmentColor : '',
        font: state.font,
        front_logo_count: state.front.logos.length,
        logo_vectors: logoVectorsSummary(state.front.logos),
        front_number_enabled: state.front.numberEnabled,
        back_name_size: state.back.nameSize,
        back_number_size: state.back.numberSize,
        text_color: state.back.textColor,
        front_preview_path: sharedFrontPath || '',
        email: emailEl.value.trim(), whatsapp: waEl.value.trim(),
        players,
      });
      document.getElementById('formActionInput').value = 'team_add_to_cart';
      form.submit();
    }

    waitForLogoVectors(state.front.logos).then(() => {
      if (!state.front.numberEnabled) {
        // Front is identical for the whole team — upload once. Back differs per
        // player, so temporarily swap the name/number into the shared back
        // canvas, export it, then restore what the shopper had typed.
        return uploadCanvas(canvasFront).then(frontRes => {
          const frontPath = frontRes.success ? frontRes.path : '';
          const backUploads = rows.map(r => {
            state.back.name = r.name; state.back.number = r.number;
            redraw('back');
            return uploadCanvas(canvasBack).then(res => ({ ...r, preview_back_path: res.success ? res.path : '', variation_id: resolveVariationId(r.size) }));
          });
          return Promise.all(backUploads).then(players => finishTeamSubmit(players, frontPath));
        });
      }
      // Front carries this player's own number too — composite both sides per player.
      const perPlayer = rows.map(r => {
        state.back.name = r.name; state.back.number = r.number; redraw('back');
        state.front.number = r.number; redraw('front');
        return Promise.all([uploadCanvas(canvasFront), uploadCanvas(canvasBack)]).then(([fr, br]) => ({
          ...r,
          preview_front_path: fr.success ? fr.path : '',
          preview_back_path: br.success ? br.path : '',
          variation_id: resolveVariationId(r.size),
        }));
      });
      return Promise.all(perPlayer).then(players => finishTeamSubmit(players, ''));
    }).catch(() => {
      teamAddBtn.disabled = false; teamAddBtn.innerHTML = originalLabel;
      alert('Something went wrong uploading the designs. Please try again.');
    });
  });
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
