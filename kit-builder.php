<?php
// kit-builder.php — standalone team kit customizer, separate from the simple
// per-product "Customize Yours" panel. Lets a shopper pick which kit (jersey
// set) to build, upload their own design, drag/resize logo(s) + sponsor logo,
// add name/number, and place a logo on the shorts too. Only products tagged
// with a "shorts" template photo show up here.
require_once __DIR__ . '/includes/config.php';

$slug = sanitize($_GET['slug'] ?? '');

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

$product = $slug ? fetch_one(
    "SELECT p.* FROM products p WHERE p.slug=? AND p.status='active' AND p.is_customizable=1
     AND EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id=p.id AND pi.mockup_view='shorts')",
    's', $slug
) : null;

if ($slug && !$product) {
    set_flash('error', 'That kit isn\'t available to customize right now.');
    header('Location: ' . url('kit-builder'));
    exit;
}

// --- Add customized kit to cart ---------------------------------------------------
if ($product && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    require_csrf();
    $variation_id = (int)($_POST['variation_id'] ?? 0) ?: null;
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    $cdata = json_decode($_POST['customization_data'] ?? '', true);
    if (!is_array($cdata)) {
        set_flash('error', 'Please finish designing before adding to cart.');
        header('Location: ' . url('kit-builder/' . $product['slug'])); exit;
    }
    $cust_email = sanitize($cdata['email'] ?? '');
    $cust_whatsapp = sanitize($cdata['whatsapp'] ?? '');
    if (!filter_var($cust_email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please enter a valid email so we can confirm your kit design.');
        header('Location: ' . url('kit-builder/' . $product['slug'])); exit;
    }
    if ($cust_whatsapp === '') {
        set_flash('error', 'Please enter your WhatsApp number so we can confirm your kit design.');
        header('Location: ' . url('kit-builder/' . $product['slug'])); exit;
    }
    $customization = [
        'color'                => sanitize($cdata['color'] ?? ''),
        'garment_color'        => sanitize($cdata['garment_color'] ?? ''),
        'font'                 => sanitize($cdata['font'] ?? ''),
        'front_logo_count'     => (int)($cdata['front_logo_count'] ?? 0),
        'logo_vectors'         => sanitize_logo_vectors($cdata['logo_vectors'] ?? []),
        'shorts_logo_count'    => (int)($cdata['shorts_logo_count'] ?? 0),
        'shorts_logo_vectors'  => sanitize_logo_vectors($cdata['shorts_logo_vectors'] ?? []),
        'front_number_enabled' => !empty($cdata['front_number_enabled']),
        'front_number'         => sanitize($cdata['front_number'] ?? ''),
        'back_name'            => sanitize($cdata['back_name'] ?? ''),
        'back_name_size'       => (int)($cdata['back_name_size'] ?? 0),
        'back_number'          => sanitize($cdata['back_number'] ?? ''),
        'back_number_size'     => (int)($cdata['back_number_size'] ?? 0),
        'text_color'           => sanitize($cdata['text_color'] ?? ''),
        'preview_path'         => sanitize($_POST['customization_preview'] ?? ''),
        'preview_back_path'    => sanitize($_POST['customization_preview_back'] ?? ''),
        'preview_shorts_path'  => sanitize($_POST['customization_preview_shorts'] ?? ''),
        'email'                => $cust_email,
        'whatsapp'             => $cust_whatsapp,
        'is_kit'               => true,
    ];

    $res = add_to_cart($product['id'], $variation_id, $qty, $customization);
    if (isset($res['error'])) {
        set_flash('error', $res['error']);
        header('Location: ' . url('kit-builder/' . $product['slug']));
    } else {
        send_admin_new_customization_email($product['name'], $customization);
        set_flash('success', 'Added to cart.');
        header('Location: ' . url('cart'));
    }
    exit;
}

// --- Team order: one shared front/shorts design, one cart item per roster player --
if ($product && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'team_add_to_cart') {
    require_csrf();
    $tdata = json_decode($_POST['team_data'] ?? '', true);
    $players = is_array($tdata) ? ($tdata['players'] ?? []) : [];

    $cust_email = sanitize($tdata['email'] ?? '');
    $cust_whatsapp = sanitize($tdata['whatsapp'] ?? '');
    if (!filter_var($cust_email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please enter a valid email so we can confirm your team order.');
        header('Location: ' . url('kit-builder/' . $product['slug'])); exit;
    }
    if ($cust_whatsapp === '') {
        set_flash('error', 'Please enter your WhatsApp number so we can confirm your team order.');
        header('Location: ' . url('kit-builder/' . $product['slug'])); exit;
    }
    if (!is_array($players) || !$players) {
        set_flash('error', 'Please add at least one player to the roster.');
        header('Location: ' . url('kit-builder/' . $product['slug'])); exit;
    }

    $shared_front_preview = sanitize($tdata['front_preview_path'] ?? '');
    $shared_shorts_preview = sanitize($tdata['shorts_preview_path'] ?? '');
    $front_number_enabled = !empty($tdata['front_number_enabled']);
    $logo_vectors = sanitize_logo_vectors($tdata['logo_vectors'] ?? []);
    $shorts_logo_vectors = sanitize_logo_vectors($tdata['shorts_logo_vectors'] ?? []);
    $added = 0;
    $skipped = 0;
    $roster_summary = [];
    foreach ($players as $p) {
        $p_name = sanitize($p['name'] ?? '');
        $p_number = sanitize($p['number'] ?? '');
        if ($p_name === '' && $p_number === '') continue;

        // When the number also appears on the front, each player's front design
        // is unique (carries their own number) — otherwise everyone shares one.
        // Shorts never carry a number, so that design is always shared.
        $p_front_preview = $front_number_enabled ? sanitize($p['preview_front_path'] ?? '') : $shared_front_preview;

        $variation_id = !empty($p['variation_id']) ? (int)$p['variation_id'] : null;
        $customization = [
            'color'                => sanitize($tdata['color'] ?? ''),
            'garment_color'        => sanitize($tdata['garment_color'] ?? ''),
            'font'                 => sanitize($tdata['font'] ?? ''),
            'front_logo_count'     => (int)($tdata['front_logo_count'] ?? 0),
            'logo_vectors'         => $logo_vectors,
            'shorts_logo_count'    => (int)($tdata['shorts_logo_count'] ?? 0),
            'shorts_logo_vectors'  => $shorts_logo_vectors,
            'front_number_enabled' => $front_number_enabled,
            'front_number'         => $front_number_enabled ? $p_number : '',
            'back_name'            => $p_name,
            'back_name_size'       => (int)($tdata['back_name_size'] ?? 0),
            'back_number'          => $p_number,
            'back_number_size'     => (int)($tdata['back_number_size'] ?? 0),
            'text_color'           => sanitize($tdata['text_color'] ?? ''),
            'preview_path'         => $p_front_preview,
            'preview_back_path'    => sanitize($p['preview_back_path'] ?? ''),
            'preview_shorts_path'  => $shared_shorts_preview,
            'email'                => $cust_email,
            'whatsapp'             => $cust_whatsapp,
            'team_order'           => true,
            'is_kit'               => true,
        ];
        $res = add_to_cart($product['id'], $variation_id, 1, $customization);
        if (isset($res['error'])) { $skipped++; continue; }
        $added++;
        $roster_summary[] = trim($p_name . ' ' . $p_number) . (!empty($p['size']) ? ' (' . sanitize($p['size']) . ')' : '');
    }

    if ($added > 0) {
        send_admin_new_team_order_email($product['name'], $cust_email, $cust_whatsapp, $roster_summary, $shared_front_preview, sanitize($tdata['font'] ?? ''), array_merge($logo_vectors, $shorts_logo_vectors));
        set_flash('success', $added . ' player' . ($added === 1 ? '' : 's') . ' added to cart.' . ($skipped ? " ($skipped could not be added — check stock.)" : ''));
        header('Location: ' . url('cart'));
    } else {
        set_flash('error', 'Could not add any players — please check stock and try again.');
        header('Location: ' . url('kit-builder/' . $product['slug']));
    }
    exit;
}

// ====================================================================
// Step 1: no product chosen yet — show the kit picker
// ====================================================================
if (!$product) {
    // Full kits (jersey + shorts templates) open the 2D kit builder below; every
    // other customizable product opens the 3D Design Studio on its product page.
    $kits = fetch_all(
        "SELECT DISTINCT p.* FROM products p JOIN product_images pi ON pi.product_id=p.id
         WHERE p.status='active' AND p.is_customizable=1 AND pi.mockup_view='shorts'
         ORDER BY p.name ASC"
    );
    $kit_ids = array_column($kits, 'id');
    $studio_products = array_values(array_filter(
        fetch_all("SELECT * FROM products WHERE status='active' AND is_customizable=1 ORDER BY is_featured DESC, name ASC"),
        fn($p) => !in_array($p['id'], $kit_ids) && customizer_model_for($p) !== 'flat'
    ));
    $meta_title = 'Design Your Team Kit in 3D — Custom Jerseys & Balls | ' . setting('site_name');
    $meta_description = 'Design custom team jerseys and footballs in 3D: pick colours and patterns, add your crest, player names and numbers, and order for the whole squad.';
    $canonical_url = url('kit-builder');
    include __DIR__ . '/includes/site-header.php';
    ?>
    <main id="main">
      <section class="relative bg-ink text-white overflow-hidden">
        <div class="absolute inset-0 bg-grid opacity-50" aria-hidden="true"></div>
        <div class="absolute -right-40 top-0 w-[520px] h-[520px] rounded-full bg-ignite/30 blur-[120px]" aria-hidden="true"></div>
        <div class="container-x relative grid lg:grid-cols-2 gap-10 items-center py-16 sm:py-20">
          <div>
            <p class="eyebrow mb-4">3D Kit Builder</p>
            <h1 class="font-display font-bold uppercase text-4xl sm:text-6xl leading-[0.95] mb-5">Design your <span class="text-stroke">team kit</span> in 3D</h1>
            <p class="text-white/65 max-w-lg">Pick a product, spin it in 3D, choose colours and patterns, drop in your crest and sponsors, then add every player's name, number and size. We send you a free proof before production.</p>
          </div>
          <div id="hero3d" data-model="jersey" data-mobile="1" data-label="<?= h(explode(' ', setting('site_name', 'Team'))[0]) ?>" class="relative h-[340px] sm:h-[420px] cursor-grab" aria-label="3D kit preview"></div>
        </div>
      </section>

      <section class="container-x section">
        <?php if (!$kits && !$studio_products): ?>
        <div class="text-center py-16 text-slate-400"><i class="fa-solid fa-shirt text-4xl mb-4"></i><p>No products are set up for customization yet.</p></div>
        <?php endif; ?>

        <?php if ($studio_products): ?>
        <div class="section-head"><div><p class="eyebrow mb-3">Step 1</p><h2 class="section-title">Choose what to design</h2></div></div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-16">
          <?php foreach ($studio_products as $k): $thumb = fetch_one("SELECT image_path, alt_text FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order ASC LIMIT 1", 'i', $k['id']); $m = customizer_model_for($k); ?>
          <a href="<?= url('product/' . $k['slug']) ?>?customize" class="group card overflow-hidden block hover:-translate-y-1 transition duration-300" data-reveal>
            <div class="aspect-square bg-slate-100 overflow-hidden relative">
              <?php if ($thumb): ?><img src="<?= UPLOAD_URL . h($thumb['image_path']) ?>" alt="<?= h($thumb['alt_text'] ?: $k['name']) ?>" loading="lazy" width="500" height="500" class="w-full h-full object-cover group-hover:scale-105 transition duration-500"><?php endif; ?>
              <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 bg-ink text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider"><i class="fa-solid fa-cube text-ignite"></i> 3D <?= $m === 'ball' ? 'Ball' : 'Jersey' ?></span>
            </div>
            <div class="p-4">
              <h3 class="font-display font-semibold uppercase leading-tight mb-2"><?= h($k['name']) ?></h3>
              <span class="text-xs font-bold uppercase tracking-wider text-ignite inline-flex items-center gap-1">Open 3D studio <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition"></i></span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($kits): ?>
        <div class="section-head"><div><p class="eyebrow mb-3">Full kits</p><h2 class="section-title">Jersey + shorts builder</h2></div></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($kits as $k): $thumb = fetch_one("SELECT image_path FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order ASC LIMIT 1", 'i', $k['id']); ?>
          <a href="<?= url('kit-builder/' . $k['slug']) ?>" class="group card overflow-hidden block hover:-translate-y-1 transition" data-reveal>
            <div class="aspect-square bg-slate-100 overflow-hidden">
              <?php if ($thumb): ?><img src="<?= UPLOAD_URL . h($thumb['image_path']) ?>" alt="<?= h($k['name']) ?>" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300"><?php endif; ?>
            </div>
            <div class="p-4">
              <h3 class="font-display font-semibold uppercase mb-1"><?= h($k['name']) ?></h3>
              <span class="text-xs font-bold text-ignite inline-flex items-center gap-1 uppercase tracking-wider">Design this kit <i class="fa-solid fa-arrow-right text-[10px]"></i></span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </section>
    </main>
    <?php include __DIR__ . '/includes/site-footer.php'; ?>
    <?php exit; ?>
<?php } ?>

<?php
// ====================================================================
// Step 2: product chosen — the full builder
// ====================================================================
$images = fetch_all("SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order ASC", 'i', $product['id']);
$vtypes = fetch_all("SELECT * FROM variation_types WHERE product_id=? ORDER BY sort_order ASC", 'i', $product['id']);
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

// Candidate base photos, each optionally tagged with the color and/or the
// garment view (front/back/shorts) it shows.
$customizer_images = array_map(fn($img) => [
    'url'   => UPLOAD_URL . $img['image_path'],
    'color' => $img['variation_value'] ?: null,
    'view'  => $img['mockup_view'] ?: null,
], $images);
$customizer_default_image = $images ? UPLOAD_URL . $images[0]['image_path'] : '';

$display_price = $product['sale_price'] ?: $product['base_price'];
$display_compare = $product['sale_price'] ? $product['base_price'] : null;

$meta_title = 'Build Your Kit: ' . $product['name'] . ' | ' . setting('site_name');
$meta_robots = 'noindex, follow';

include __DIR__ . '/includes/site-header.php';
?>
<main id="main" class="max-w-6xl mx-auto px-4 sm:px-6 py-10">
  <nav class="text-xs text-slate-400 mb-6" aria-label="Breadcrumb">
    <a href="<?= url('') ?>" class="hover:text-ignite">Home</a> /
    <a href="<?= url('kit-builder') ?>" class="hover:text-ignite">Kit Builder</a> /
    <span class="text-slate-600"><?= h($product['name']) ?></span>
  </nav>

  <?php $error = get_flash('error'); ?>
  <?php if ($error): ?><div class="mb-6 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"><?= h($error) ?></div><?php endif; ?>

  <div class="flex items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="font-display font-bold text-2xl sm:text-3xl">Design: <?= h($product['name']) ?></h1>
      <div class="flex items-baseline gap-3 mt-1">
        <span id="priceDisplay" class="text-xl font-bold text-ink"><?= format_price($display_price) ?></span>
        <?php if ($display_compare): ?><span id="compareDisplay" class="text-sm text-slate-400 line-through"><?= format_price($display_compare) ?></span><?php endif; ?>
      </div>
    </div>
    <a href="<?= url('kit-builder') ?>" class="text-xs font-semibold text-slate-400 hover:text-slate-600 shrink-0"><i class="fa-solid fa-arrow-left mr-1"></i> Choose Different Kit</a>
  </div>

  <form method="POST" id="addToCartForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="formActionInput" value="add_to_cart">
    <input type="hidden" name="variation_id" id="variationIdInput" value="">
    <input type="hidden" name="customization_preview" id="customizationPreviewInput" value="">
    <input type="hidden" name="customization_preview_back" id="customizationPreviewBackInput" value="">
    <input type="hidden" name="customization_preview_shorts" id="customizationPreviewShortsInput" value="">
    <input type="hidden" name="customization_data" id="customizationDataInput" value="">
    <input type="hidden" name="team_data" id="teamDataInput" value="">

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
      <!-- Live preview -->
      <div class="lg:col-span-3">
        <div class="flex gap-1 mb-3 max-w-[420px]">
          <button type="button" id="tabFrontBtn" class="flex-1 text-sm font-semibold py-2 rounded-lg bg-indigo-500 text-white transition">Jersey Front</button>
          <button type="button" id="tabBackBtn" class="flex-1 text-sm font-semibold py-2 rounded-lg bg-white text-slate-500 border border-slate-200 transition">Jersey Back</button>
          <button type="button" id="tabShortsBtn" class="flex-1 text-sm font-semibold py-2 rounded-lg bg-white text-slate-500 border border-slate-200 transition">Shorts</button>
        </div>
        <div class="relative w-full max-w-[420px] aspect-square rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
          <canvas id="customizerCanvasFront" width="800" height="800" class="absolute inset-0 w-full h-full cursor-move"></canvas>
          <canvas id="customizerCanvasBack" width="800" height="800" class="absolute inset-0 w-full h-full cursor-move hidden"></canvas>
          <canvas id="customizerCanvasShorts" width="800" height="800" class="absolute inset-0 w-full h-full cursor-move hidden"></canvas>
        </div>
        <p class="text-xs text-slate-400 mt-2 max-w-[420px]">Drag your logo(s) / name / number directly on the preview to position them.</p>
      </div>

      <!-- Controls -->
      <div class="lg:col-span-2 space-y-4">
        <?php foreach ($vtypes as $vt): ?>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5"><?= h($vt['name']) ?></label>
          <select class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite variation-select" data-type="<?= h($vt['name']) ?>" onchange="updateVariation()">
            <?php foreach ($vt['options'] as $opt): ?>
            <option value="<?= h($opt) ?>"><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endforeach; ?>

        <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4">
          <!-- Garment color -->
          <div class="flex items-center gap-2 mb-3 bg-white rounded-lg border border-slate-200 px-2.5 py-1.5">
            <input type="checkbox" id="custColorToggle" class="w-3.5 h-3.5 rounded accent-indigo-500">
            <label for="custColorToggle" class="text-xs font-semibold text-slate-600 flex-1">Recolor garment</label>
            <input type="color" id="custGarmentColor" value="#ff4d2e" class="w-7 h-6 rounded border border-slate-300 p-0">
          </div>

          <!-- Font -->
          <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Font</label>
            <select id="custFont" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
              <option value="'Arial Black', Arial, sans-serif">Arial Black</option>
              <option value="Impact, sans-serif">Impact</option>
              <option value="Oswald, sans-serif" selected>Oswald</option>
              <option value="Anton, sans-serif">Anton</option>
              <option value="'Bebas Neue', sans-serif">Bebas Neue</option>
            </select>
          </div>

          <!-- Front: multiple logos + optional front number -->
          <div id="frontControls">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Jersey Front — Logos</label>
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

          <!-- Shorts: logo placement -->
          <div id="shortsControls" class="hidden">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Shorts — Logo Placement</label>
            <div id="shortsLogosList" class="space-y-1.5 mb-2"></div>
            <button type="button" id="addShortsLogoBtn" class="text-xs font-semibold text-indigo-600"><i class="fa-solid fa-circle-plus mr-1"></i>Add Logo to Shorts</button>
            <input type="file" id="shortsLogoInput" accept="image/*,.svg" class="hidden">
            <p class="text-[10px] text-slate-400 mt-1">Drag to position on the leg, resize with the slider.</p>
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
            <p class="text-[11px] text-slate-400 mb-2">Same design for everyone — the position/size you set on the Jersey Back tab is used for each player's name &amp; number.</p>
            <div id="rosterRows" class="space-y-1.5 mb-2"></div>
            <button type="button" id="addPlayerBtn" class="text-xs font-semibold text-indigo-600 mb-3"><i class="fa-solid fa-circle-plus mr-1"></i>Add Player</button>
            <button type="button" id="teamAddToCartBtn" class="w-full inline-flex items-center justify-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3 rounded-full transition">
              <i class="fa-solid fa-cart-plus"></i> Add Whole Team to Cart
            </button>
          </div>
        </div>

        <div id="stockNote" class="text-xs font-medium"></div>

        <div id="singleOrderControls">
          <div class="flex items-center gap-3">
            <label class="text-sm font-semibold text-slate-700">Qty</label>
            <input type="number" name="quantity" id="qtyInput" value="1" min="1" class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm text-center">
          </div>
          <button type="submit" id="addToCartBtn" class="w-full mt-3 inline-flex items-center justify-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full transition">
            <i class="fa-solid fa-cart-plus"></i> Add to Cart
          </button>
        </div>
      </div>
    </div>
  </form>
</main>

<script>
const variationMap = <?= json_encode($variation_map, JSON_UNESCAPED_SLASHES) ?>;
const basePrice = <?= json_encode((float)$display_price) ?>;
const currencySymbol = <?= json_encode(setting('currency_symbol', '$')) ?>;

function fmtPrice(n) { return currencySymbol + Number(n).toFixed(2); }

function updateVariation() {
  const selects = document.querySelectorAll('.variation-select');
  const btn = document.getElementById('addToCartBtn');
  const note = document.getElementById('stockNote');
  const qtyInput = document.getElementById('qtyInput');
  if (!selects.length) { note.textContent = ''; return; }
  const key = Array.from(selects).map(s => s.value).join(' / ');
  const match = variationMap[key];
  if (match) {
    document.getElementById('variationIdInput').value = match.id;
    document.getElementById('priceDisplay').textContent = fmtPrice(match.sale_price ?? match.price);
    if (match.stock <= 0) {
      note.textContent = 'Out of stock for this option.'; note.className = 'text-xs font-medium text-red-600';
      btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed'); qtyInput.max = 0;
    } else if (match.stock <= 5) {
      note.textContent = `Only ${match.stock} left in stock.`; note.className = 'text-xs font-medium text-amber-600';
      btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed'); qtyInput.max = match.stock;
    } else {
      note.textContent = ''; btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed'); qtyInput.removeAttribute('max');
    }
  } else {
    document.getElementById('variationIdInput').value = '';
    document.getElementById('priceDisplay').textContent = fmtPrice(basePrice);
  }
}
updateVariation();
</script>

<script>
(function () {
  const SIDES = ['front', 'back', 'shorts'];
  const canvases = {
    front: document.getElementById('customizerCanvasFront'),
    back: document.getElementById('customizerCanvasBack'),
    shorts: document.getElementById('customizerCanvasShorts'),
  };
  const ctxs = { front: canvases.front.getContext('2d'), back: canvases.back.getContext('2d'), shorts: canvases.shorts.getContext('2d') };
  const W = canvases.front.width, H = canvases.front.height;

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
      baseImg: null, baseSrc: '', logos: [],
      numberEnabled: false, number: '', numberPos: { x: W / 2, y: H * 0.72 },
    },
    back: { baseImg: null, baseSrc: '', name: '', number: '', nameSize: 50, numberSize: 150, textColor: '#ffffff', namePos: { x: W / 2, y: H * 0.3 }, numberPos: { x: W / 2, y: H * 0.58 } },
    shorts: { baseImg: null, baseSrc: '', logos: [] },
  };

  function fitCover(img, w, h) {
    const ir = img.width / img.height, cr = w / h;
    let sw, sh, sx, sy;
    if (ir > cr) { sh = img.height; sw = sh * cr; sx = (img.width - sw) / 2; sy = 0; }
    else { sw = img.width; sh = sw / cr; sx = 0; sy = (img.height - sh) / 2; }
    return { sx, sy, sw, sh };
  }

  function redraw(side) {
    const ctx = ctxs[side];
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
    if (side === 'front' || side === 'shorts') {
      s.logos.forEach(l => {
        const lw = W * (l.sizePct / 100);
        const lh = lw * (l.img.height / l.img.width);
        l.w = lw; l.h = lh;
        ctx.drawImage(l.img, l.x - lw / 2, l.y - lh / 2, lw, lh);
      });
      if (side === 'front' && s.numberEnabled) drawStrokedText(ctx, s.number, s.numberPos, state.back.numberSize, state.back.textColor);
    } else {
      drawStrokedText(ctx, s.name, s.namePos, s.nameSize, s.textColor);
      drawStrokedText(ctx, s.number, s.numberPos, s.numberSize, s.textColor);
    }
  }
  function redrawAll() { SIDES.forEach(redraw); }

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
  SIDES.forEach(loadBase);
  if (csel) csel.addEventListener('change', () => SIDES.forEach(loadBase));
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(redrawAll);

  // ---- Garment recolor ----
  const colorToggle = document.getElementById('custColorToggle');
  const colorInput = document.getElementById('custGarmentColor');
  colorToggle.addEventListener('change', () => { state.garmentColorOn = colorToggle.checked; redrawAll(); });
  colorInput.addEventListener('input', () => { state.garmentColor = colorInput.value; if (state.garmentColorOn) redrawAll(); });

  // ---- Tabs ----
  const tabBtns = { front: document.getElementById('tabFrontBtn'), back: document.getElementById('tabBackBtn'), shorts: document.getElementById('tabShortsBtn') };
  const controlPanels = { front: document.getElementById('frontControls'), back: document.getElementById('backControls'), shorts: document.getElementById('shortsControls') };
  function setTab(side) {
    state.activeSide = side;
    SIDES.forEach(s => {
      canvases[s].classList.toggle('hidden', s !== side);
      controlPanels[s].classList.toggle('hidden', s !== side);
      tabBtns[s].className = 'flex-1 text-sm font-semibold py-2 rounded-lg transition ' + (s === side ? 'bg-indigo-500 text-white' : 'bg-white text-slate-500 border border-slate-200');
    });
  }
  tabBtns.front.addEventListener('click', () => setTab('front'));
  tabBtns.back.addEventListener('click', () => setTab('back'));
  tabBtns.shorts.addEventListener('click', () => setTab('shorts'));

  // ---- Logo management (shared logic for Front and Shorts) ----
  function vectorizeLogo(file, logo, onDone) {
    const body = new FormData();
    body.set('csrf_token', csrfTokenValue);
    body.set('logo', file);
    logo.vectorPromise = fetch(vectorizeUrl, { method: 'POST', body })
      .then(r => r.json())
      .then(res => {
        logo.vectorStatus = res.success && res.vector_path ? 'ready' : 'failed';
        logo.vectorPath = res.vector_path || null;
        logo.isOriginalVector = !!res.is_original_vector;
        onDone();
      })
      .catch(() => { logo.vectorStatus = 'failed'; onDone(); });
  }
  function waitForLogoVectors(logos) { return Promise.all(logos.map(l => l.vectorPromise || Promise.resolve())); }
  function logoVectorsSummary(logos) { return logos.map(l => ({ vector_path: l.vectorPath, is_original_vector: l.isOriginalVector })); }

  function setupLogoManager(side, listEl, addBtn, fileInput) {
    addBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', e => {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = ev => {
        const img = new Image();
        img.onload = () => {
          const logos = state[side].logos;
          const logo = { img, x: W / 2, y: H * (0.3 + logos.length * 0.12), sizePct: 22, w: 0, h: 0, vectorStatus: 'pending', vectorPath: null, isOriginalVector: false };
          logos.push(logo);
          renderList(); redraw(side);
          vectorizeLogo(file, logo, renderList);
        };
        img.src = ev.target.result;
      };
      reader.readAsDataURL(file);
      fileInput.value = '';
    });
    function renderList() {
      listEl.innerHTML = '';
      state[side].logos.forEach((l, i) => {
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
        row.querySelector('input[type=range]').addEventListener('input', e => { l.sizePct = +e.target.value; redraw(side); });
        row.querySelector('button').addEventListener('click', () => { state[side].logos.splice(i, 1); renderList(); redraw(side); });
        listEl.appendChild(row);
      });
    }
  }
  setupLogoManager('front', document.getElementById('frontLogosList'), document.getElementById('addLogoBtn'), document.getElementById('custLogoInput'));
  setupLogoManager('shorts', document.getElementById('shortsLogosList'), document.getElementById('addShortsLogoBtn'), document.getElementById('shortsLogoInput'));

  // ---- Font (applies to name/number everywhere) ----
  function loadAndRedrawFont() {
    const spec = `900 100px ${state.font}`;
    if (document.fonts && document.fonts.load) {
      document.fonts.load(spec).then(redrawAll).catch(redrawAll);
    } else { redrawAll(); }
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

  // ---- Drag to position ----
  function canvasPoint(canvas, e) {
    const rect = canvas.getBoundingClientRect();
    const cx = e.touches ? e.touches[0].clientX : e.clientX;
    const cy = e.touches ? e.touches[0].clientY : e.clientY;
    return { x: (cx - rect.left) * (W / rect.width), y: (cy - rect.top) * (H / rect.height) };
  }
  let dragging = null;
  function startDrag(e) {
    const side = state.activeSide;
    const p = canvasPoint(canvases[side], e);
    if (side === 'front' || side === 'shorts') {
      const s = state[side];
      if (side === 'front' && s.numberEnabled && s.number) {
        const numSize = state.back.numberSize;
        if (Math.abs(p.y - s.numberPos.y) < numSize / 2 && Math.abs(p.x - s.numberPos.x) < numSize * 1.2) {
          dragging = { side, target: s.numberPos };
        }
      }
      if (!dragging) {
        for (let i = s.logos.length - 1; i >= 0; i--) {
          const l = s.logos[i];
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
    const p = canvasPoint(canvases[dragging.side], e);
    dragging.target.x = p.x; dragging.target.y = p.y;
    redraw(dragging.side);
  }
  function endDrag() { dragging = null; }
  SIDES.forEach(s => {
    canvases[s].addEventListener('mousedown', startDrag);
    canvases[s].addEventListener('touchstart', startDrag, { passive: false });
  });
  window.addEventListener('mousemove', moveDrag);
  window.addEventListener('touchmove', e => { moveDrag(e); if (dragging) e.preventDefault(); }, { passive: false });
  window.addEventListener('mouseup', endDrag);
  window.addEventListener('touchend', endDrag);

  // ---- Composite + upload all 3 sides before the item is actually added ----
  const form = document.getElementById('addToCartForm');
  function uploadCanvas(canvas) {
    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
    const body = new URLSearchParams();
    body.set('csrf_token', csrfToken);
    body.set('image_data', canvas.toDataURL('image/png'));
    return fetch(uploadUrl, { method: 'POST', body }).then(r => r.json());
  }

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

    Promise.all([
      uploadCanvas(canvases.front), uploadCanvas(canvases.back), uploadCanvas(canvases.shorts),
      waitForLogoVectors(state.front.logos), waitForLogoVectors(state.shorts.logos),
    ]).then(([frontRes, backRes, shortsRes]) => {
      if (frontRes.success) document.getElementById('customizationPreviewInput').value = frontRes.path;
      if (backRes.success) document.getElementById('customizationPreviewBackInput').value = backRes.path;
      if (shortsRes.success) document.getElementById('customizationPreviewShortsInput').value = shortsRes.path;
      document.getElementById('customizationDataInput').value = JSON.stringify({
        color: csel ? csel.value : '',
        garment_color: state.garmentColorOn ? state.garmentColor : '',
        font: state.font,
        front_logo_count: state.front.logos.length,
        logo_vectors: logoVectorsSummary(state.front.logos),
        shorts_logo_count: state.shorts.logos.length,
        shorts_logo_vectors: logoVectorsSummary(state.shorts.logos),
        front_number_enabled: state.front.numberEnabled,
        front_number: state.front.numberEnabled ? state.front.number : '',
        back_name: state.back.name,
        back_name_size: state.back.nameSize,
        back_number: state.back.number,
        back_number_size: state.back.numberSize,
        text_color: state.back.textColor,
        email: emailEl.value.trim(), whatsapp: waEl.value.trim(),
      });
    }).catch(() => {}).finally(() => { form.submit(); });
  });

  // ---- Team order: same front/shorts design, one roster row per player ----
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

    const savedBackName = state.back.name, savedBackNumber = state.back.number, savedFrontNumber = state.front.number;
    function finishTeamSubmit(players, sharedFrontPath, sharedShortsPath) {
      state.back.name = savedBackName; state.back.number = savedBackNumber; redraw('back');
      state.front.number = savedFrontNumber; redraw('front');
      document.getElementById('teamDataInput').value = JSON.stringify({
        color: csel ? csel.value : '',
        garment_color: state.garmentColorOn ? state.garmentColor : '',
        font: state.font,
        front_logo_count: state.front.logos.length,
        logo_vectors: logoVectorsSummary(state.front.logos),
        shorts_logo_count: state.shorts.logos.length,
        shorts_logo_vectors: logoVectorsSummary(state.shorts.logos),
        front_number_enabled: state.front.numberEnabled,
        back_name_size: state.back.nameSize,
        back_number_size: state.back.numberSize,
        text_color: state.back.textColor,
        front_preview_path: sharedFrontPath || '',
        shorts_preview_path: sharedShortsPath || '',
        email: emailEl.value.trim(), whatsapp: waEl.value.trim(),
        players,
      });
      document.getElementById('formActionInput').value = 'team_add_to_cart';
      form.submit();
    }

    Promise.all([waitForLogoVectors(state.front.logos), waitForLogoVectors(state.shorts.logos)]).then(() => {
      return uploadCanvas(canvases.shorts).then(shortsRes => {
        const shortsPath = shortsRes.success ? shortsRes.path : '';
        if (!state.front.numberEnabled) {
          // Front is identical for the whole team — upload once. Back differs
          // per player, so temporarily swap name/number into the shared back
          // canvas, export it, then restore what the shopper had typed.
          return uploadCanvas(canvases.front).then(frontRes => {
            const frontPath = frontRes.success ? frontRes.path : '';
            const backUploads = rows.map(r => {
              state.back.name = r.name; state.back.number = r.number;
              redraw('back');
              return uploadCanvas(canvases.back).then(res => ({ ...r, preview_back_path: res.success ? res.path : '', variation_id: resolveVariationId(r.size) }));
            });
            return Promise.all(backUploads).then(players => finishTeamSubmit(players, frontPath, shortsPath));
          });
        }
        // Front carries this player's own number too — composite front+back per player.
        const perPlayer = rows.map(r => {
          state.back.name = r.name; state.back.number = r.number; redraw('back');
          state.front.number = r.number; redraw('front');
          return Promise.all([uploadCanvas(canvases.front), uploadCanvas(canvases.back)]).then(([fr, br]) => ({
            ...r,
            preview_front_path: fr.success ? fr.path : '',
            preview_back_path: br.success ? br.path : '',
            variation_id: resolveVariationId(r.size),
          }));
        });
        return Promise.all(perPlayer).then(players => finishTeamSubmit(players, '', shortsPath));
      });
    }).catch(() => {
      teamAddBtn.disabled = false; teamAddBtn.innerHTML = originalLabel;
      alert('Something went wrong uploading the designs. Please try again.');
    });
  });
})();
</script>

<?php include __DIR__ . '/includes/site-footer.php'; ?>
