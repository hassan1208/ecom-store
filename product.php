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
            $customization = sanitize_customization_spec($cdata) + [
                'front_number'      => mb_substr(sanitize($cdata['front_number'] ?? ''), 0, 3),
                'back_name'         => mb_substr(sanitize($cdata['back_name'] ?? ''), 0, 20),
                'back_number'       => mb_substr(sanitize($cdata['back_number'] ?? ''), 0, 3),
                'preview_path'      => valid_custom_upload_path($_POST['customization_preview'] ?? ''),
                'preview_back_path' => valid_custom_upload_path($_POST['customization_preview_back'] ?? ''),
            ];
            $customization['email'] = $cust_email;
            $customization['whatsapp'] = $cust_whatsapp;
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

    $shared_front_preview = valid_custom_upload_path($tdata['front_preview_path'] ?? '');
    $spec = sanitize_customization_spec($tdata);
    $front_number_enabled = $spec['front_number_enabled'];
    $logo_vectors = $spec['logo_vectors'];
    $shared_design_front = $spec['design_front_path'];
    $added = 0;
    $skipped = 0;
    $roster_summary = [];
    foreach ($players as $p) {
        $p_name = sanitize($p['name'] ?? '');
        $p_number = sanitize($p['number'] ?? '');
        if ($p_name === '' && $p_number === '') continue;

        // When the number also appears on the front, each player's front design
        // is unique (carries their own number) — otherwise everyone shares one.
        $p_front_preview = $front_number_enabled ? valid_custom_upload_path($p['preview_front_path'] ?? '') : $shared_front_preview;

        $variation_id = !empty($p['variation_id']) ? (int)$p['variation_id'] : null;
        $customization = $spec;
        $customization['front_number']      = $front_number_enabled ? mb_substr($p_number, 0, 3) : '';
        $customization['back_name']         = mb_substr($p_name, 0, 20);
        $customization['back_number']       = mb_substr($p_number, 0, 3);
        $customization['preview_path']      = $p_front_preview;
        $customization['preview_back_path'] = valid_custom_upload_path($p['preview_back_path'] ?? '');
        $customization['design_front_path'] = $front_number_enabled ? valid_custom_upload_path($p['design_front_path'] ?? '') : $shared_design_front;
        $customization['design_back_path']  = valid_custom_upload_path($p['design_back_path'] ?? '');
        $customization['email']             = $cust_email;
        $customization['whatsapp']          = $cust_whatsapp;
        $customization['team_order']        = true;
        $res = add_to_cart($product['id'], $variation_id, 1, $customization);
        if (isset($res['error'])) { $skipped++; continue; }
        $added++;
        $roster_summary[] = trim($p_name . ' ' . $p_number) . (!empty($p['size']) ? ' (' . sanitize($p['size']) . ')' : '');
    }

    if ($added > 0) {
        send_admin_new_team_order_email($product['name'], $cust_email, $cust_whatsapp, $roster_summary, $shared_front_preview, $spec['font'], $logo_vectors);
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


$display_price   = $product['sale_price'] ?: $product['base_price'];
$display_compare = $product['sale_price'] ? $product['base_price'] : null;
$currency        = setting('currency_code', 'USD');
$studio_model    = !empty($product['is_customizable']) ? customizer_model_for($product, $category['name'] ?? '') : null;
$studio_3d       = $studio_model && setting('studio_enabled_3d', '1') === '1';
$in_stock        = !$product['track_stock'] || (int)$product['stock_quantity'] > 0 || array_filter($variations, fn($v) => (int)$v['stock_quantity'] > 0);

// ---------------------------------------------------------------------------
// SEO
// ---------------------------------------------------------------------------
$meta_title       = $product['meta_title'] ?: ($product['name'] . ($category ? ' — ' . $category['name'] : '') . ' | ' . setting('site_name'));
// Fall back through short description, then the full description, so the
// meta description is never empty (Google would auto-generate a worse one).
$meta_description = $product['meta_description']
    ?: $product['short_description']
    ?: meta_text($product['description'], 160)
    ?: ('Buy ' . $product['name'] . ' from ' . setting('site_name') . '. Wholesale & custom orders available.');
$canonical_url    = $product['canonical_url'] ?: url('product/' . $product['slug']);
$og_image         = !empty($images) ? UPLOAD_URL . $images[0]['image_path'] : null;
$og_image_alt     = !empty($images) ? ($images[0]['alt_text'] ?: $product['name']) : null;
$og_type          = 'product';
$og_extra         = [
    'product:price:amount'   => number_format((float)$display_price, 2, '.', ''),
    'product:price:currency' => $currency,
    'product:availability'   => $in_stock ? 'in stock' : 'out of stock',
    'product:condition'      => 'new',
    'product:brand'          => product_brand($product),
    'product:retailer_item_id' => $product['sku'] ?: null,
];

$price_valid_until = date('Y-m-d', strtotime('+' . max(30, (int)setting('seo_price_valid_days', '365')) . ' days'));
$offer_common = [
    'priceCurrency'   => $currency,
    'priceValidUntil' => $price_valid_until,
    'itemCondition'   => 'https://schema.org/NewCondition',
    'url'             => $canonical_url,
    'seller'          => ['@id' => SITE_URL . '/#organization'],
    'shippingDetails' => [
        '@type' => 'OfferShippingDetails',
        'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => number_format((float)setting('shipping_cost', '0'), 2, '.', ''), 'currency' => $currency],
        'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => setting('seo_shipping_country', 'PK')],
        'deliveryTime' => [
            '@type' => 'ShippingDeliveryTime',
            'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => (int)setting('seo_handling_days_max', '3'), 'unitCode' => 'DAY'],
            'transitTime'  => ['@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => (int)setting('seo_transit_days_max', '7'), 'unitCode' => 'DAY'],
        ],
    ],
    'hasMerchantReturnPolicy' => [
        '@type' => 'MerchantReturnPolicy',
        'applicableCountry' => setting('seo_shipping_country', 'PK'),
        'returnPolicyCategory' => (int)setting('seo_return_days', '14') > 0 ? 'https://schema.org/MerchantReturnFiniteReturnWindow' : 'https://schema.org/MerchantReturnNotPermitted',
        'merchantReturnDays' => (int)setting('seo_return_days', '14') ?: null,
        'returnMethod' => 'https://schema.org/ReturnByMail',
        'returnFees' => 'https://schema.org/' . (setting('seo_return_fees', 'FreeReturn') ?: 'FreeReturn'),
    ],
];
$variant_prices = array_map(fn($v) => (float)($v['sale_price'] ?? $v['price']), $variation_map);
if (count($variant_prices) > 1 && min($variant_prices) != max($variant_prices)) {
    $offers = ['@type' => 'AggregateOffer', 'lowPrice' => number_format(min($variant_prices), 2, '.', ''), 'highPrice' => number_format(max($variant_prices), 2, '.', ''), 'offerCount' => count($variant_prices)] + $offer_common;
} else {
    $offers = ['@type' => 'Offer', 'price' => number_format((float)$display_price, 2, '.', '')] + $offer_common;
}
$offers['availability'] = $in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

$product_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    '@id' => $canonical_url . '#product',
    'name' => $product['name'],
    'description' => meta_text($product['description'] ?: ($product['short_description'] ?: $meta_description), 5000),
    'image' => array_map(fn($i) => UPLOAD_URL . $i['image_path'], $images),
    'sku' => $product['sku'],
    'mpn' => $product['mpn'] ?? null,
    'gtin' => $product['gtin'] ?? null,
    'brand' => ['@type' => 'Brand', 'name' => product_brand($product)],
    'category' => $category['name'] ?? null,
    'weight' => $product['weight_kg'] ? ['@type' => 'QuantitativeValue', 'value' => (float)$product['weight_kg'], 'unitCode' => 'KGM'] : null,
    'offers' => $offers,
];
if ($rating['count'] > 0) {
    $product_schema['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => (string)$rating['avg'], 'reviewCount' => (string)$rating['count'], 'bestRating' => '5', 'worstRating' => '1'];
    $product_schema['review'] = array_map(fn($rv) => [
        '@type' => 'Review',
        'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (string)$rv['rating'], 'bestRating' => '5'],
        'author' => ['@type' => 'Person', 'name' => $rv['customer_name']],
        'datePublished' => date('Y-m-d', strtotime($rv['created_at'])),
        'name' => $rv['title'] ?: null,
        'reviewBody' => $rv['comment'],
    ], array_slice($reviews, 0, 5));
}
$page_schema = [
    $product_schema,
    [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('')],
            $category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $category['name'], 'item' => url('category/' . $category['slug'])] : null,
            ['@type' => 'ListItem', 'position' => $category ? 3 : 2, 'name' => $product['name'], 'item' => $canonical_url],
        ])),
    ],
];
if ($og_image) $extra_head = '<link rel="preload" as="image" href="' . h($og_image) . '" fetchpriority="high">';
if ($studio_model) {
    $extra_head = ($extra_head ?? '') . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Graduate&family=Black+Ops+One&family=Russo+One&display=swap" media="print" onload="this.media=\'all\'">';
}

include __DIR__ . '/includes/site-header.php';
$success = get_flash('success'); $error = get_flash('error');
?>
<main id="main" class="container-x pt-6 pb-24">
  <nav class="text-xs text-slate-500 mb-6 flex flex-wrap items-center gap-2" aria-label="Breadcrumb">
    <a href="<?= url('') ?>" class="hover:text-ignite">Home</a>
    <?php if ($category): ?><i class="fa-solid fa-chevron-right text-[8px] opacity-50"></i><a href="<?= url('category/' . $category['slug']) ?>" class="hover:text-ignite"><?= h($category['name']) ?></a><?php endif; ?>
    <i class="fa-solid fa-chevron-right text-[8px] opacity-50"></i><span class="text-ink font-medium" aria-current="page"><?= h($product['name']) ?></span>
  </nav>

  <?php if ($error): ?><div class="mb-6 rounded-2xl border border-red-200 bg-red-50 text-red-700 px-5 py-4 text-sm font-medium" role="alert"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= h($error) ?></div><?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-14">
    <!-- ================= Gallery ================= -->
    <div class="lg:col-span-7">
      <div class="lg:sticky lg:top-40">
        <?php if (count($spin_frame_urls) >= 8): ?>
        <div id="spinViewer" data-frames="<?= h(json_encode($spin_frame_urls)) ?>" class="relative aspect-square rounded-[28px] overflow-hidden bg-white select-none border border-black/5" style="cursor:grab">
          <img id="spinImg" src="<?= h($spin_frame_urls[0]) ?>" alt="<?= h($product['name']) ?> — 360° view" class="w-full h-full object-cover pointer-events-none" draggable="false" width="1000" height="1000">
          <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-ink/80 backdrop-blur text-white text-xs font-semibold px-4 py-2 rounded-full flex items-center gap-2 pointer-events-none">
            <i class="fa-solid fa-arrows-left-right"></i> Drag to rotate 360°
          </div>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-[76px_1fr] gap-3">
          <?php if (count($images) > 1): ?>
          <div class="order-2 sm:order-1 flex sm:flex-col gap-2 overflow-x-auto sm:overflow-visible scrollbar-none" role="tablist" aria-label="Product images">
            <?php foreach ($images as $i => $img): ?>
            <button type="button" class="gallery-thumb shrink-0 w-[72px] sm:w-full aspect-square rounded-2xl overflow-hidden border-2 <?= $i === 0 ? 'border-ink' : 'border-transparent' ?> bg-white hover:border-ignite transition" data-src="<?= UPLOAD_URL . h($img['image_path']) ?>" data-alt="<?= h($img['alt_text'] ?: $product['name']) ?>" aria-label="View image <?= $i + 1 ?>">
              <img src="<?= UPLOAD_URL . h($img['image_path']) ?>" alt="" loading="lazy" width="120" height="120" class="w-full h-full object-cover">
            </button>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <div class="order-1 sm:order-2 <?= count($images) > 1 ? '' : 'sm:col-span-2' ?>">
            <div id="zoomBox" class="relative aspect-square rounded-[28px] overflow-hidden bg-white border border-black/5 cursor-zoom-in group">
              <?php if ($images): ?>
              <img id="mainImage" src="<?= UPLOAD_URL . h($images[0]['image_path']) ?>" alt="<?= h($images[0]['alt_text'] ?: $product['name']) ?>" fetchpriority="high" width="1000" height="1000" class="w-full h-full object-cover transition-transform duration-300">
              <?php else: ?>
              <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-image text-5xl"></i></div>
              <?php endif; ?>
              <div class="absolute top-4 left-4 flex flex-col gap-2 items-start pointer-events-none">
                <?php if ($display_compare): ?><span class="bg-ignite text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Save <?= (int)round(100 - $display_price / $display_compare * 100) ?>%</span><?php endif; ?>
                <?php if (!empty($product['is_new_arrival'])): ?><span class="bg-emerald-500 text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">New</span><?php endif; ?>
              </div>
              <?php if ($studio_3d && $studio_model !== 'flat'): ?>
              <button type="button" data-open-studio class="absolute bottom-4 right-4 inline-flex items-center gap-2 rounded-full bg-ink/90 backdrop-blur text-white text-xs font-bold uppercase tracking-wider px-4 py-2.5 hover:bg-ignite transition shadow-xl">
                <i class="fa-solid fa-cube"></i> View &amp; customize in 3D
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ================= Buy box ================= -->
    <div class="lg:col-span-5">
      <?php if ($category): ?><a href="<?= url('category/' . $category['slug']) ?>" class="eyebrow mb-3 hover:opacity-80"><?= h($category['name']) ?></a><?php endif; ?>
      <div class="flex items-start justify-between gap-3 mb-3">
        <h1 class="font-display font-bold uppercase text-3xl sm:text-4xl leading-[1.05] tracking-tight"><?= h($product['name']) ?></h1>
        <form method="POST" action="<?= url('wishlist-toggle') ?>" class="shrink-0">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <input type="hidden" name="redirect" value="<?= h($canonical_url) ?>">
          <?php $product_in_wishlist = is_in_wishlist($product['id']); ?>
          <button type="submit" aria-label="<?= $product_in_wishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>" class="w-12 h-12 rounded-full border border-slate-200 bg-white flex items-center justify-center hover:border-ignite transition">
            <i class="fa-<?= $product_in_wishlist ? 'solid' : 'regular' ?> fa-heart <?= $product_in_wishlist ? 'text-ignite' : 'text-slate-500' ?>"></i>
          </button>
        </form>
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-5 text-sm">
        <?php if ($rating['count'] > 0): ?>
        <a href="#reviews" class="flex items-center gap-2"><?= star_html($rating['avg']) ?><span class="font-semibold"><?= $rating['avg'] ?></span><span class="text-slate-400">(<?= $rating['count'] ?> review<?= $rating['count'] != 1 ? 's' : '' ?>)</span></a>
        <?php endif; ?>
        <?php if ($product['sku']): ?><span class="text-slate-400">SKU: <span id="skuDisplay" class="text-slate-600"><?= h($product['sku']) ?></span></span><?php endif; ?>
      </div>

      <div class="flex items-baseline gap-3 mb-5">
        <span id="priceDisplay" class="font-display font-bold text-4xl text-ink"><?= format_price($display_price) ?></span>
        <?php if ($display_compare): ?><span id="compareDisplay" class="text-lg text-slate-400 line-through"><?= format_price($display_compare) ?></span><?php endif; ?>
      </div>

      <?php if ($product['short_description']): ?><p class="text-slate-600 leading-relaxed mb-6"><?= h($product['short_description']) ?></p><?php endif; ?>

      <form method="POST" id="addToCartForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="formActionInput" value="add_to_cart">
        <input type="hidden" name="variation_id" id="variationIdInput" value="">
        <?php if ($studio_model): ?>
        <input type="hidden" name="customization_preview" id="customizationPreviewInput" value="">
        <input type="hidden" name="customization_preview_back" id="customizationPreviewBackInput" value="">
        <input type="hidden" name="customization_data" id="customizationDataInput" value="">
        <input type="hidden" name="team_data" id="teamDataInput" value="">
        <?php endif; ?>

        <?php foreach ($vtypes as $vt): $is_color = in_array(strtolower($vt['name']), ['color', 'colour'], true); ?>
        <fieldset class="mb-5">
          <legend class="label mb-2"><?= h($vt['name']) ?>: <span class="variation-current normal-case tracking-normal text-ink font-semibold ml-1"></span></legend>
          <select class="sr-only variation-select" data-type="<?= h($vt['name']) ?>" onchange="updateVariation()" aria-label="<?= h($vt['name']) ?>">
            <?php foreach ($vt['options'] as $opt): ?><option value="<?= h($opt) ?>"><?= h($opt) ?></option><?php endforeach; ?>
          </select>
          <div class="flex flex-wrap gap-2 variation-pills" data-for="<?= h($vt['name']) ?>">
            <?php foreach ($vt['options'] as $i => $opt): ?>
            <button type="button" data-value="<?= h($opt) ?>" class="variation-pill min-w-[52px] h-11 px-4 rounded-xl border-2 text-sm font-semibold transition <?= $i === 0 ? 'border-ink bg-ink text-white' : 'border-slate-200 bg-white hover:border-ink' ?>">
              <?php if ($is_color): ?><span class="inline-block w-3.5 h-3.5 rounded-full border border-black/10 mr-1.5 align-[-2px]" style="background: <?= h(strtolower(preg_replace('/[^a-z]/i', '', $opt))) ?>"></span><?php endif; ?><?= h($opt) ?>
            </button>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <?php endforeach; ?>

        <?php if ($studio_model): ?>
        <!-- 3D studio entry card -->
        <div class="relative rounded-3xl overflow-hidden bg-ink text-white p-5 mb-6 noise">
          <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-ignite/40 blur-3xl" aria-hidden="true"></div>
          <div class="relative flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center shrink-0 overflow-hidden">
              <img id="studioEntryThumb" src="" alt="" class="w-full h-full object-contain hidden" onload="this.classList.remove('hidden');this.nextElementSibling.classList.add('hidden')">
              <i class="fa-solid fa-cube text-2xl text-ignite"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-display font-semibold uppercase tracking-wide">Make it yours<?= $studio_3d && $studio_model !== 'flat' ? ' — in 3D' : '' ?></p>
              <p class="text-xs text-white/60">Colours, patterns, logos, names &amp; numbers<?= $studio_model === 'jersey' ? ', whole-team rosters' : '' ?>.</p>
              <p id="studioEntryStatus" hidden class="text-xs font-semibold text-emerald-400 mt-1"><i class="fa-solid fa-circle-check mr-1"></i>Your design will be added with this item</p>
            </div>
          </div>
          <div class="relative grid grid-cols-2 gap-2 mt-4">
            <button type="button" data-open-studio class="btn-primary btn-sm btn-shine"><i class="fa-solid fa-wand-magic-sparkles"></i> Customize</button>
            <button type="button" data-open-studio="team" class="btn-ghost-light btn-sm"><i class="fa-solid fa-users"></i> Team order</button>
          </div>
        </div>
        <?php endif; ?>

        <div id="stockNote" class="text-sm font-semibold mb-3" role="status"></div>

        <div id="singleOrderControls" class="flex gap-3 mb-3">
          <div class="flex items-center rounded-full border-2 border-slate-200 bg-white h-[52px]">
            <button type="button" class="w-11 h-full text-lg" onclick="stepQty(-1)" aria-label="Decrease quantity">−</button>
            <label for="qtyInput" class="sr-only">Quantity</label>
            <input type="number" name="quantity" id="qtyInput" value="1" min="1" class="w-12 text-center font-semibold bg-transparent focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
            <button type="button" class="w-11 h-full text-lg" onclick="stepQty(1)" aria-label="Increase quantity">+</button>
          </div>
          <button type="submit" id="addToCartBtn" class="btn-primary btn-shine flex-1"><i class="fa-solid fa-bag-shopping"></i> Add to Cart</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <?php if ($product['show_bulk_dm']): ?>
          <a href="<?= url('contact') ?>" class="btn-outline btn-sm !py-3"><i class="fa-solid fa-boxes-stacked"></i> Bulk quote</a>
          <?php endif; ?>
          <?php if ($product_wa_link): ?>
          <a href="<?= h($product_wa_link) ?>" target="_blank" rel="noopener" class="btn btn-sm !py-3 border-2 border-[#25D366] text-[#128C4A] hover:bg-[#25D366] hover:text-white"><i class="fa-brands fa-whatsapp text-base"></i> WhatsApp</a>
          <?php endif; ?>
        </div>

        <?php if ($studio_model) include __DIR__ . '/includes/studio-markup.php'; ?>
      </form>

      <div id="notifyStockWrap" class="<?= $is_simple_out_of_stock ? '' : 'hidden' ?> mt-4 rounded-2xl border border-slate-200 bg-white p-4">
        <?php if ($notify_submitted): ?>
        <p class="text-sm text-emerald-600 font-medium"><i class="fa-solid fa-circle-check mr-1"></i>We'll email you when it's back in stock.</p>
        <?php else: ?>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2"><i class="fa-regular fa-bell mr-1"></i>Get notified when it's back</p>
        <form method="POST" class="flex gap-2">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="notify_stock">
          <input type="hidden" name="variation_key" id="notifyVariationKey" value="">
          <input type="email" name="email" required placeholder="Your email" aria-label="Your email" class="input flex-1 !py-2.5">
          <button type="submit" class="btn-dark btn-sm">Notify me</button>
        </form>
        <?php if ($notify_error): ?><p class="text-xs text-red-600 mt-1.5"><?= h($notify_error) ?></p><?php endif; ?>
        <?php endif; ?>
      </div>

      <ul class="grid grid-cols-3 gap-2 mt-6 text-center text-[11px] font-semibold text-slate-600">
        <li class="rounded-2xl bg-white border border-black/5 px-2 py-3"><i class="fa-solid fa-truck-fast text-ignite text-base block mb-1"></i>Ships in <?= (int)setting('seo_handling_days_max', '3') ?>–<?= (int)setting('seo_handling_days_max', '3') + (int)setting('seo_transit_days_max', '7') ?> days</li>
        <li class="rounded-2xl bg-white border border-black/5 px-2 py-3"><i class="fa-solid fa-rotate-left text-ignite text-base block mb-1"></i><?= (int)setting('seo_return_days', '14') ?>-day returns</li>
        <li class="rounded-2xl bg-white border border-black/5 px-2 py-3"><i class="fa-solid fa-lock text-ignite text-base block mb-1"></i>Secure checkout</li>
      </ul>

      <?php if ($qty_rules): ?>
      <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
        <h2 class="text-xs font-bold uppercase tracking-wider text-amber-700 mb-3"><i class="fa-solid fa-boxes-stacked mr-1"></i>Bulk pricing</h2>
        <table class="w-full text-sm">
          <?php foreach ($qty_rules as $r): ?>
          <tr class="border-t border-amber-100 first:border-0">
            <td class="py-2 text-slate-600">Buy <?= (int)$r['min_qty'] ?>+</td>
            <td class="py-2 text-right font-bold"><?= format_price($r['price']) ?> / unit</td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php endif; ?>

      <div class="mt-6 flex items-center gap-3 text-sm text-slate-500">
        <span class="font-semibold">Share:</span>
        <?php $share = urlencode($canonical_url); $share_t = urlencode($product['name']); ?>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $share ?>" target="_blank" rel="noopener" class="w-9 h-9 rounded-full bg-white border border-black/5 flex items-center justify-center hover:text-ignite" aria-label="Share on Facebook"><i class="fa-brands fa-facebook-f"></i></a>
        <a href="https://twitter.com/intent/tweet?url=<?= $share ?>&text=<?= $share_t ?>" target="_blank" rel="noopener" class="w-9 h-9 rounded-full bg-white border border-black/5 flex items-center justify-center hover:text-ignite" aria-label="Share on X"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="https://wa.me/?text=<?= $share_t ?>%20<?= $share ?>" target="_blank" rel="noopener" class="w-9 h-9 rounded-full bg-white border border-black/5 flex items-center justify-center hover:text-ignite" aria-label="Share on WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        <a href="https://pinterest.com/pin/create/button/?url=<?= $share ?>&media=<?= urlencode((string)$og_image) ?>&description=<?= $share_t ?>" target="_blank" rel="noopener" class="w-9 h-9 rounded-full bg-white border border-black/5 flex items-center justify-center hover:text-ignite" aria-label="Pin on Pinterest"><i class="fa-brands fa-pinterest-p"></i></a>
      </div>
    </div>
  </div>

  <!-- ================= Details ================= -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 mt-20">
    <div class="lg:col-span-7 space-y-12">
      <?php if ($product['description']): ?>
      <section aria-labelledby="desc-title">
        <h2 id="desc-title" class="font-display font-bold uppercase text-2xl mb-5">Description</h2>
        <div class="prose-site text-slate-600"><?= $product['description'] ?></div>
      </section>
      <?php endif; ?>

      <!-- Reviews -->
      <section id="reviews" aria-labelledby="reviews-title">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
          <h2 id="reviews-title" class="font-display font-bold uppercase text-2xl">Customer Reviews</h2>
          <?php if ($rating['count'] > 0): ?>
          <div class="flex items-center gap-3"><span class="font-display font-bold text-4xl"><?= $rating['avg'] ?></span><div><?= star_html($rating['avg']) ?><p class="text-xs text-slate-400"><?= $rating['count'] ?> review<?= $rating['count'] != 1 ? 's' : '' ?></p></div></div>
          <?php endif; ?>
        </div>
        <?php if ($reviews): ?>
        <div class="space-y-4 mb-10">
          <?php foreach ($reviews as $rv): ?>
          <article class="card p-6">
            <div class="flex items-center gap-2 mb-2">
              <?= star_html($rv['rating']) ?>
              <?php if ($rv['is_verified']): ?><span class="text-[10px] font-bold uppercase tracking-wide bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full"><i class="fa-solid fa-circle-check mr-0.5"></i>Verified purchase</span><?php endif; ?>
            </div>
            <?php if ($rv['title']): ?><h3 class="font-semibold text-ink mb-1"><?= h($rv['title']) ?></h3><?php endif; ?>
            <p class="text-sm text-slate-600 leading-relaxed mb-3"><?= h($rv['comment']) ?></p>
            <p class="text-xs text-slate-400"><?= h($rv['customer_name']) ?> · <time datetime="<?= h(date('Y-m-d', strtotime($rv['created_at']))) ?>"><?= format_date($rv['created_at']) ?></time></p>
          </article>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-slate-500 mb-8">No reviews yet. Be the first to share your experience.</p>
        <?php endif; ?>
      </section>
    </div>

    <aside class="lg:col-span-5 space-y-6">
      <div class="card p-6">
        <h3 class="font-display font-semibold uppercase text-lg mb-4">Write a review</h3>
        <?php if ($review_submitted): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm" role="status">Thanks! Your review will appear once approved.</div>
        <?php else: ?>
        <?php if ($review_errors): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm mb-4" role="alert"><ul class="list-disc pl-4 space-y-0.5"><?php foreach ($review_errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" class="space-y-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="submit_review">
          <div>
            <span class="label">Your rating</span>
            <div class="flex gap-1" id="starPicker" role="radiogroup" aria-label="Rating">
              <?php for ($i = 1; $i <= 5; $i++): ?>
              <button type="button" class="star-btn text-3xl text-slate-300 hover:scale-110 transition" data-val="<?= $i ?>" onclick="setRating(<?= $i ?>)" aria-label="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</button>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="0">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="customer_name" placeholder="Your name" aria-label="Your name" required value="<?= h($_POST['customer_name'] ?? '') ?>" class="input">
            <input type="email" name="customer_email" placeholder="Your email" aria-label="Your email" required value="<?= h($_POST['customer_email'] ?? '') ?>" class="input">
          </div>
          <input type="text" name="title" placeholder="Review title (optional)" aria-label="Review title" value="<?= h($_POST['title'] ?? '') ?>" class="input">
          <textarea name="comment" rows="3" placeholder="Share your experience…" aria-label="Your review" required class="input"><?= h($_POST['comment'] ?? '') ?></textarea>
          <button type="submit" class="btn-dark btn-sm w-full">Submit review</button>
        </form>
        <?php endif; ?>
      </div>

      <?php if (!empty($product['accepts_design_requests'])): ?>
      <div class="card p-6 border-indigo-100">
        <h3 class="font-display font-semibold uppercase text-lg mb-1"><i class="fa-solid fa-image text-indigo-500 mr-1"></i>Custom design from your photo</h3>
        <p class="text-sm text-slate-500 mb-4">Upload a reference photo or AI design — we'll vectorize it and email you front, back &amp; sleeve mockups before you order.</p>
        <?php if ($design_request_submitted): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm" role="status"><i class="fa-solid fa-circle-check mr-1"></i>Thanks! We received your design. We'll email you the mockups shortly.</div>
        <?php else: ?>
        <?php if ($design_request_errors): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm mb-4" role="alert"><ul class="list-disc pl-4 space-y-0.5"><?php foreach ($design_request_errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="submit_design_request">
          <label class="studio-dropzone cursor-pointer"><i class="fa-solid fa-cloud-arrow-up text-2xl text-indigo-500"></i><span class="text-sm font-semibold">Choose your photo / design *</span><input type="file" name="dr_image" accept="image/*" required class="text-xs"></label>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" name="dr_name" placeholder="Your name" aria-label="Your name" required value="<?= h($_POST['dr_name'] ?? '') ?>" class="input">
            <input type="email" name="dr_email" placeholder="Your email" aria-label="Your email" required value="<?= h($_POST['dr_email'] ?? '') ?>" class="input">
          </div>
          <input type="text" name="dr_phone" placeholder="Phone (optional)" aria-label="Phone" value="<?= h($_POST['dr_phone'] ?? '') ?>" class="input">
          <textarea name="dr_message" rows="2" placeholder="Colours, placement, style…" aria-label="Message" class="input"><?= h($_POST['dr_message'] ?? '') ?></textarea>
          <button type="submit" class="btn-dark btn-sm w-full"><i class="fa-solid fa-paper-plane"></i> Send my design</button>
        </form>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </aside>
  </div>

  <?php if ($related_products): ?>
  <section class="mt-24" aria-labelledby="related-title">
    <div class="section-head">
      <div><p class="eyebrow mb-3">Complete the kit</p><h2 id="related-title" class="section-title">You may also like</h2></div>
      <?php if ($category): ?><a href="<?= url('category/' . $category['slug']) ?>" class="link-arrow">View all <i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-10">
      <?php foreach (array_slice($related_products, 0, 4) as $rp) echo render_product_card($rp); ?>
    </div>
  </section>
  <?php endif; ?>
</main>

<!-- Sticky mobile buy bar -->
<div id="mobileBuyBar" class="lg:hidden fixed inset-x-0 bottom-0 z-30 bg-white/95 backdrop-blur border-t border-black/5 px-4 py-3 flex items-center gap-3 translate-y-full transition-transform duration-300">
  <div class="min-w-0 flex-1">
    <p class="text-xs text-slate-500 truncate"><?= h($product['name']) ?></p>
    <p id="mobilePrice" class="font-display font-bold text-lg leading-none"><?= format_price($display_price) ?></p>
  </div>
  <?php if ($studio_model): ?><button type="button" data-open-studio class="btn-outline btn-sm !px-4" aria-label="Customize"><i class="fa-solid fa-wand-magic-sparkles"></i></button><?php endif; ?>
  <button type="button" onclick="document.getElementById('addToCartBtn').click()" class="btn-primary btn-sm">Add to cart</button>
</div>

<script>
window.variationMap = <?= json_encode($variation_map, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
const basePrice = <?= json_encode((float)$display_price) ?>;
const baseSku = <?= json_encode($product['sku'] ?: '', JSON_HEX_TAG) ?>;
const trackStock = <?= $product['track_stock'] ? 'true' : 'false' ?>;
const baseStock = <?= (int)$product['stock_quantity'] ?>;
const currencySymbol = <?= json_encode(setting('currency_symbol', '$'), JSON_HEX_TAG) ?>;
function fmtPrice(n) { return currencySymbol + Number(n).toFixed(2); }

function setRating(v) {
  document.getElementById('ratingInput').value = v;
  document.querySelectorAll('.star-btn').forEach(b => { b.classList.toggle('text-amber-400', +b.dataset.val <= v); b.classList.toggle('text-slate-300', +b.dataset.val > v); });
}
function stepQty(d) {
  const q = document.getElementById('qtyInput');
  const max = q.max ? +q.max : Infinity;
  q.value = Math.max(1, Math.min(max, (+q.value || 1) + d));
}

// Pill buttons drive the (visually hidden) variation <select>s.
document.querySelectorAll('.variation-pills').forEach(group => {
  const select = group.previousElementSibling;
  const current = group.parentElement.querySelector('.variation-current');
  const sync = () => {
    group.querySelectorAll('.variation-pill').forEach(b => {
      const on = b.dataset.value === select.value;
      b.classList.toggle('border-ink', on); b.classList.toggle('bg-ink', on); b.classList.toggle('text-white', on);
      b.classList.toggle('border-slate-200', !on); b.classList.toggle('bg-white', !on);
      b.setAttribute('aria-pressed', on);
    });
    if (current) current.textContent = select.value;
  };
  group.addEventListener('click', e => {
    const b = e.target.closest('.variation-pill'); if (!b) return;
    select.value = b.dataset.value; select.dispatchEvent(new Event('change', { bubbles: true }));
    sync();
  });
  sync();
});

function setStock(state, text) {
  const btn = document.getElementById('addToCartBtn'), note = document.getElementById('stockNote');
  note.textContent = text || '';
  note.className = 'text-sm font-semibold mb-3 ' + (state === 'out' ? 'text-red-600' : state === 'low' ? 'text-amber-600' : 'text-emerald-600');
  btn.disabled = state === 'out';
}
function updateVariation() {
  const selects = document.querySelectorAll('.variation-select');
  const qtyInput = document.getElementById('qtyInput');
  const notifyWrap = document.getElementById('notifyStockWrap');
  if (!selects.length) {
    if (trackStock && baseStock <= 0) { setStock('out', 'Out of stock'); qtyInput.max = 0; }
    else if (trackStock && baseStock <= 5) { setStock('low', `Only ${baseStock} left in stock — order soon`); qtyInput.max = baseStock; }
    else { setStock('ok', trackStock ? '● In stock' : ''); qtyInput.removeAttribute('max'); }
    return;
  }
  const key = Array.from(selects).map(s => s.value).join(' / ');
  const match = window.variationMap[key];
  if (match) {
    document.getElementById('variationIdInput').value = match.id;
    const price = fmtPrice(match.sale_price ?? match.price);
    document.getElementById('priceDisplay').textContent = price;
    document.getElementById('mobilePrice').textContent = price;
    const compareEl = document.getElementById('compareDisplay');
    if (compareEl) { if (match.sale_price && match.sale_price < match.price) { compareEl.textContent = fmtPrice(match.price); compareEl.style.display = ''; } else compareEl.style.display = 'none'; }
    const sku = document.getElementById('skuDisplay'); if (sku) sku.textContent = match.sku || baseSku;
    const notifyKey = document.getElementById('notifyVariationKey');
    if (trackStock && match.stock <= 0) { setStock('out', 'Out of stock for this option'); qtyInput.max = 0; if (notifyWrap) { notifyWrap.classList.remove('hidden'); notifyKey.value = key; } }
    else if (trackStock && match.stock <= 5) { setStock('low', `Only ${match.stock} left — order soon`); qtyInput.max = match.stock; if (notifyWrap) notifyWrap.classList.add('hidden'); }
    else { setStock('ok', trackStock ? '● In stock' : ''); qtyInput.removeAttribute('max'); if (notifyWrap) notifyWrap.classList.add('hidden'); }
  } else {
    document.getElementById('variationIdInput').value = '';
    document.getElementById('priceDisplay').textContent = fmtPrice(basePrice);
    if (notifyWrap) notifyWrap.classList.add('hidden');
  }
}
updateVariation();

// Gallery: thumbnails + hover zoom
(function () {
  const main = document.getElementById('mainImage'), box = document.getElementById('zoomBox');
  document.querySelectorAll('.gallery-thumb').forEach(t => t.addEventListener('click', () => {
    main.src = t.dataset.src; main.alt = t.dataset.alt;
    document.querySelectorAll('.gallery-thumb').forEach(x => { x.classList.toggle('border-ink', x === t); x.classList.toggle('border-transparent', x !== t); });
  }));
  if (!box || !main || !matchMedia('(hover: hover)').matches) return;
  box.addEventListener('pointermove', e => {
    if (e.target.closest('button')) { main.style.transform = ''; return; }
    const r = box.getBoundingClientRect();
    main.style.transformOrigin = `${(e.clientX - r.left) / r.width * 100}% ${(e.clientY - r.top) / r.height * 100}%`;
    main.style.transform = 'scale(1.8)';
  });
  box.addEventListener('pointerleave', () => { main.style.transform = ''; });
})();

// Sticky buy bar appears once the main button scrolls out of view.
(function () {
  const bar = document.getElementById('mobileBuyBar'), btn = document.getElementById('addToCartBtn');
  if (!bar || !btn || !('IntersectionObserver' in window)) return;
  new IntersectionObserver(([e]) => bar.classList.toggle('translate-y-full', e.isIntersecting || e.boundingClientRect.top > 0)).observe(btn);
})();
</script>

<?php if (count($spin_frame_urls) >= 8): ?>
<script>
(function () {
  const el = document.getElementById('spinViewer'), img = document.getElementById('spinImg');
  const frames = JSON.parse(el.dataset.frames);
  frames.forEach(src => { const im = new Image(); im.src = src; });
  let current = 0, dragging = false, startX = 0, startFrame = 0, userInteracted = false;
  const setFrame = i => { current = ((i % frames.length) + frames.length) % frames.length; img.src = frames[current]; };
  el.addEventListener('pointerdown', e => { dragging = true; userInteracted = true; startX = e.clientX; startFrame = current; el.style.cursor = 'grabbing'; el.setPointerCapture(e.pointerId); });
  el.addEventListener('pointermove', e => { if (dragging) setFrame(startFrame - Math.round((e.clientX - startX) / 6)); });
  el.addEventListener('pointerup', () => { dragging = false; el.style.cursor = 'grab'; });
  el.style.touchAction = 'pan-y';
  setTimeout(() => {
    if (userInteracted) return;
    let steps = 0;
    const iv = setInterval(() => { if (userInteracted || ++steps > frames.length) return clearInterval(iv); setFrame(current + 1); }, 55);
  }, 700);
})();
</script>
<?php endif; ?>

<?php if ($studio_model): ?>
<script>
window.STUDIO_CONFIG = <?= json_encode([
    'model'        => $studio_model,
    'productId'    => (int)$product['id'],
    'productName'  => $product['name'],
    'productSlug'  => $product['slug'],
    'images'       => $customizer_images,
    'defaultImage' => $customizer_default_image,
    'uploadUrl'    => url('customizer-upload.php'),
    'vectorizeUrl' => url('customizer-vectorize.php'),
    'csrf'         => csrf_token(),
    'brandColor'   => setting('theme_primary_color', '#ff4d2e'),
    'threeUrl'     => $studio_3d ? asset_url('assets/vendor/three/three.min.js') : '',
    'garmentUrl'   => $studio_3d ? asset_url('assets/js/garment3d.js') : '',
    'maxLogos'     => 8,
    'fonts'        => $studio_fonts,
    'presets'      => $studio_presets,
    'defaults'     => [
        'base' => valid_hex_color($product['customizer_base_color'] ?? '') ?: ($studio_model === 'ball' ? '#ffffff' : '#1d4ed8'),
        'pattern' => $studio_model === 'ball' ? 'panels' : 'none',
        'patternColor' => $studio_model === 'ball' ? '#0f172a' : '#ffffff',
    ],
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
</script>
<script src="<?= asset_url('assets/js/garment3d.js') ?>" defer></script>
<script src="<?= asset_url('assets/js/customizer-studio.js') ?>" defer></script>
<?php endif; ?>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
