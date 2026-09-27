<?php
// admin/pages/product-edit.php — add/edit product, with Etsy-style variations
// and bulk/wholesale quantity pricing.
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$pid = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// --- Image delete / set-primary (quick GET actions) --------------------------
if (($_GET['action'] ?? '') === 'delete_image' && isset($_GET['img'])) {
    require_csrf_get();
    $img = fetch_one("SELECT * FROM product_images WHERE id=? AND product_id=?", 'ii', (int)$_GET['img'], $pid);
    if ($img) {
        db()->query("DELETE FROM product_images WHERE id=" . (int)$img['id']);
        if (file_exists(UPLOAD_PATH . $img['image_path'])) @unlink(UPLOAD_PATH . $img['image_path']);
        if ($img['is_primary']) {
            $next = fetch_one("SELECT id FROM product_images WHERE product_id=? ORDER BY sort_order ASC LIMIT 1", 'i', $pid);
            if ($next) update_record('product_images', ['is_primary' => 1], 'id', $next['id']);
        }
    }
    header('Location: ' . ADMIN_URL . '/pages/product-edit.php?id=' . $pid); exit;
}
if (($_GET['action'] ?? '') === 'set_primary' && isset($_GET['img'])) {
    require_csrf_get();
    db()->query("UPDATE product_images SET is_primary=0 WHERE product_id=" . $pid);
    update_record('product_images', ['is_primary' => 1], 'id', (int)$_GET['img']);
    header('Location: ' . ADMIN_URL . '/pages/product-edit.php?id=' . $pid); exit;
}
if (($_GET['action'] ?? '') === 'delete_spin_frame' && isset($_GET['frame'])) {
    require_csrf_get();
    $frame = fetch_one("SELECT * FROM product_spin_frames WHERE id=? AND product_id=?", 'ii', (int)$_GET['frame'], $pid);
    if ($frame) {
        db()->query("DELETE FROM product_spin_frames WHERE id=" . (int)$frame['id']);
        if (file_exists(UPLOAD_PATH . $frame['image_path'])) @unlink(UPLOAD_PATH . $frame['image_path']);
    }
    header('Location: ' . ADMIN_URL . '/pages/product-edit.php?id=' . $pid); exit;
}

// --- Save (create or update) --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = sanitize($_POST['name'] ?? '');
    if ($name === '') {
        set_flash('error', 'Product name is required.');
        header('Location: ' . ADMIN_URL . '/pages/product-edit.php' . ($pid ? "?id=$pid" : ''));
        exit;
    }

    $slug_input = trim($_POST['slug'] ?? '');
    $slug = unique_slug('products', generate_slug($slug_input !== '' ? $slug_input : $name), $pid);

    // Bulk quantity pricing tiers
    $qty_rules_raw = json_decode($_POST['qty_price_rules_json'] ?? '[]', true) ?: [];
    $qty_rules = [];
    foreach ($qty_rules_raw as $r) {
        $minq = (int)($r['min_qty'] ?? 0);
        $price = (float)($r['price'] ?? 0);
        if ($minq > 0 && $price >= 0) $qty_rules[] = ['min_qty' => $minq, 'price' => $price];
    }
    usort($qty_rules, fn($a, $b) => $a['min_qty'] <=> $b['min_qty']);

    $data = [
        'name'              => $name,
        'slug'              => $slug,
        'sku'               => sanitize($_POST['sku'] ?? ''),
        'category_id'       => (int)($_POST['category_id'] ?? 0) ?: null,
        'short_description' => sanitize($_POST['short_description'] ?? ''),
        'description'       => sanitize_html($_POST['description'] ?? ''),
        'tags'              => sanitize($_POST['tags'] ?? ''),
        'status'            => in_array($_POST['status'] ?? '', ['active', 'draft', 'archived']) ? $_POST['status'] : 'draft',
        'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
        'is_new_arrival'    => isset($_POST['is_new_arrival']) ? 1 : 0,
        'is_coming_soon'    => isset($_POST['is_coming_soon']) ? 1 : 0,
        'base_price'        => (float)($_POST['base_price'] ?? 0),
        'sale_price'        => ($_POST['sale_price'] ?? '') !== '' ? (float)$_POST['sale_price'] : null,
        'stock_quantity'    => (int)($_POST['stock_quantity'] ?? 0),
        'track_stock'       => isset($_POST['track_stock']) ? 1 : 0,
        'weight_kg'         => ($_POST['weight_kg'] ?? '') !== '' ? (float)$_POST['weight_kg'] : null,
        'qty_price_rules'   => $qty_rules ? json_encode($qty_rules) : null,
        'show_bulk_dm'      => isset($_POST['show_bulk_dm']) ? 1 : 0,
        'is_customizable'   => isset($_POST['is_customizable']) ? 1 : 0,
        'accepts_design_requests' => isset($_POST['accepts_design_requests']) ? 1 : 0,
        'meta_title'        => sanitize($_POST['meta_title'] ?? ''),
        'meta_description'  => sanitize($_POST['meta_description'] ?? ''),
        'focus_keyword'     => sanitize($_POST['focus_keyword'] ?? ''),
        'canonical_url'     => sanitize($_POST['canonical_url'] ?? ''),
    ];

    if ($pid) {
        update_record('products', $data, 'id', $pid);
    } else {
        $pid = insert('products', $data);
    }

    // Images (append any newly uploaded files)
    if (!empty($_FILES['images']['name'][0])) {
        $has_existing = (int)(fetch_one("SELECT COUNT(*) c FROM product_images WHERE product_id=?", 'i', $pid)['c'] ?? 0) > 0;
        $next_order = (int)(fetch_one("SELECT MAX(sort_order) m FROM product_images WHERE product_id=?", 'i', $pid)['m'] ?? -1) + 1;
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if (empty($tmp)) continue;
            $file = ['name' => $_FILES['images']['name'][$i], 'type' => $_FILES['images']['type'][$i], 'tmp_name' => $tmp, 'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i]];
            $res = upload_image($file, 'products', 1000, 1000, 'prod');
            if (!isset($res['error'])) {
                insert('product_images', [
                    'product_id' => $pid, 'image_path' => $res['filename'], 'alt_text' => $name,
                    'sort_order' => $next_order++, 'is_primary' => (!$has_existing && $next_order === 1) ? 1 : 0,
                ]);
                $has_existing = true;
            }
        }
    }

    // Variations (Etsy-style: replace-all on every save — simplest & reliable)
    $vdata = json_decode($_POST['variations_json'] ?? '', true);
    if (is_array($vdata)) {
        db()->query("DELETE FROM variation_types WHERE product_id=" . $pid);      // cascades variation_options
        db()->query("DELETE FROM product_variations WHERE product_id=" . $pid);

        foreach (($vdata['types'] ?? []) as $ti => $type) {
            $tname = sanitize($type['name'] ?? '');
            if ($tname === '') continue;
            $type_id = insert('variation_types', ['product_id' => $pid, 'name' => $tname, 'sort_order' => $ti]);
            foreach (($type['options'] ?? []) as $oi => $opt) {
                $opt = sanitize($opt);
                if ($opt === '') continue;
                insert('variation_options', ['type_id' => $type_id, 'value' => $opt, 'sort_order' => $oi]);
            }
        }
        foreach (($vdata['combinations'] ?? []) as $ci => $combo) {
            $key = sanitize($combo['key'] ?? '');
            if ($key === '') continue;
            insert('product_variations', [
                'product_id'     => $pid,
                'combination_key'=> $key,
                'price'          => (($combo['price'] ?? '') !== '') ? (float)$combo['price'] : null,
                'sale_price'     => (($combo['sale_price'] ?? '') !== '') ? (float)$combo['sale_price'] : null,
                'stock_quantity' => (int)($combo['stock'] ?? 0),
                'sku'            => sanitize($combo['sku'] ?? ''),
                'sort_order'     => $ci,
            ]);
        }
    }

    // 360° spin frames (append any newly uploaded photos, in the order picked)
    if (!empty($_FILES['spin_frames']['name'][0])) {
        $next_frame = (int)(fetch_one("SELECT MAX(frame_order) m FROM product_spin_frames WHERE product_id=?", 'i', $pid)['m'] ?? -1) + 1;
        foreach ($_FILES['spin_frames']['tmp_name'] as $i => $tmp) {
            if (empty($tmp)) continue;
            $file = ['name' => $_FILES['spin_frames']['name'][$i], 'type' => $_FILES['spin_frames']['type'][$i], 'tmp_name' => $tmp, 'error' => $_FILES['spin_frames']['error'][$i], 'size' => $_FILES['spin_frames']['size'][$i]];
            $res = upload_image($file, 'spin-frames', 1200, 1200, 'spin');
            if (!isset($res['error'])) {
                insert('product_spin_frames', ['product_id' => $pid, 'image_path' => $res['filename'], 'frame_order' => $next_frame++]);
            }
        }
    }

    // Per-image color tag (which variation option value this photo shows —
    // lets the storefront customizer swap the base photo when the shopper picks a color).
    foreach (($_POST['image_variation'] ?? []) as $img_id => $val) {
        update_record('product_images', ['variation_value' => sanitize($val) ?: null], 'id', (int)$img_id);
    }
    // Per-image mockup view tag (front/back/sleeve/shorts) — which blank template
    // photo the mockup generator / Kit Builder should place a design onto.
    foreach (($_POST['image_mockup_view'] ?? []) as $img_id => $val) {
        $val = in_array($val, ['front', 'back', 'sleeve', 'shorts'], true) ? $val : null;
        update_record('product_images', ['mockup_view' => $val], 'id', (int)$img_id);
    }

    process_stock_notifications($pid);

    set_flash('success', 'Product saved successfully.');
    header('Location: ' . ADMIN_URL . '/pages/product-edit.php?id=' . $pid);
    exit;
}

// --- Load data for the form ---------------------------------------------------
$page_title = $pid ? 'Edit Product' : 'Add Product';
$edit = $pid ? fetch_one("SELECT * FROM products WHERE id=?", 'i', $pid) : null;
if ($pid && !$edit) { set_flash('error', 'Product not found.'); header('Location: ' . ADMIN_URL . '/pages/products.php'); exit; }

$categories = fetch_all("SELECT id, name FROM categories WHERE status='active' ORDER BY name ASC");
$images = $pid ? fetch_all("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC", 'i', $pid) : [];
$spin_frames = $pid ? fetch_all("SELECT * FROM product_spin_frames WHERE product_id=? ORDER BY frame_order ASC", 'i', $pid) : [];

$initial_types = [];
if ($pid) {
    $vtypes = fetch_all("SELECT * FROM variation_types WHERE product_id=? ORDER BY sort_order ASC", 'i', $pid);
    foreach ($vtypes as $vt) {
        $opts = fetch_all("SELECT value FROM variation_options WHERE type_id=? ORDER BY sort_order ASC", 'i', $vt['id']);
        $initial_types[] = ['name' => $vt['name'], 'options' => array_column($opts, 'value')];
    }
}
$initial_combos = $pid ? fetch_all("SELECT combination_key AS `key`, price, sale_price, stock_quantity AS stock, sku FROM product_variations WHERE product_id=? ORDER BY sort_order ASC", 'i', $pid) : [];
// Existing products saved before the toggle UI always had per-combination price/stock — default the toggles on for them.
$had_price_data = (bool)array_filter($initial_combos, fn($c) => $c['price'] !== null && $c['price'] !== '');
$had_stock_data = (bool)array_filter($initial_combos, fn($c) => (int)$c['stock'] > 0);
$initial_qty_rules = $edit && $edit['qty_price_rules'] ? json_decode($edit['qty_price_rules'], true) : [];

include __DIR__ . '/../includes/admin-header.php';
?>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/vendor/quill/quill.snow.css">
<style>.ql-editor{min-height:190px;font-size:14px}.ql-toolbar.ql-snow{border-color:#cbd5e1;border-radius:8px 8px 0 0}.ql-container.ql-snow{border-color:#cbd5e1;border-radius:0 0 8px 8px}</style>
<form method="POST" enctype="multipart/form-data" id="productForm">
  <?= csrf_field() ?>
  <?php if ($pid): ?><input type="hidden" name="id" value="<?= $pid ?>"><?php endif; ?>
  <input type="hidden" name="qty_price_rules_json" id="qtyRulesJson">
  <input type="hidden" name="variations_json" id="variationsJson">

  <div class="flex items-center justify-between mb-5">
    <a href="<?= ADMIN_URL ?>/pages/products.php" class="text-sm font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Products</a>
    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Product</button>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">

      <!-- Basic info -->
      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-4">Basic Information</h2>
        <div class="mb-4">
          <label class="f-label">Product Name *</label>
          <input type="text" name="name" id="pName" required class="f-input" value="<?= h($edit['name'] ?? '') ?>" placeholder="e.g. Pro Leather Boxing Gloves">
        </div>
        <div class="mb-4">
          <label class="f-label">URL Slug</label>
          <div class="flex items-center gap-0 rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-brand/30 focus-within:border-brand">
            <span class="px-3 py-2.5 bg-slate-50 text-slate-400 text-sm border-r border-slate-200 whitespace-nowrap">/product/</span>
            <input type="text" name="slug" id="pSlug" class="flex-1 px-3 py-2.5 text-sm outline-none" value="<?= h($edit['slug'] ?? '') ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="f-label">Category</label>
            <select name="category_id" class="f-select">
              <option value="">— None —</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (($edit['category_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="f-label">SKU</label>
            <input type="text" name="sku" id="pSku" class="f-input" value="<?= h($edit['sku'] ?? '') ?>" placeholder="e.g. BG-001">
          </div>
        </div>
        <div class="mb-4">
          <label class="f-label">Tags <span class="text-slate-400 font-normal">(comma separated)</span></label>
          <input type="text" name="tags" class="f-input" value="<?= h($edit['tags'] ?? '') ?>" placeholder="boxing, gloves, leather">
        </div>
        <div class="mb-4">
          <label class="f-label">Short Description</label>
          <input type="text" name="short_description" maxlength="500" class="f-input" value="<?= h($edit['short_description'] ?? '') ?>">
        </div>
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="f-label mb-0">Full Description</label>
            <span class="text-[11px] text-slate-400">Rich text — bold, lists, headings, links</span>
          </div>
          <div id="descEditor" class="bg-white border border-slate-300 rounded-lg" style="min-height:220px"></div>
          <textarea name="description" id="descHidden" class="hidden"></textarea>
        </div>
      </div>

      <!-- Images -->
      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-1">Product Images</h2>
        <p class="f-hint mb-4">First image (or the starred one) is used as the main thumbnail.</p>
        <?php if ($images): ?>
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-4">
          <?php foreach ($images as $img): ?>
          <div>
            <div class="relative group rounded-lg overflow-hidden border border-slate-200 aspect-square">
              <img src="<?= UPLOAD_URL . h($img['image_path']) ?>" class="w-full h-full object-cover">
              <?php if ($img['is_primary']): ?><span class="absolute top-1.5 left-1.5 bg-brand text-white text-[10px] font-bold px-2 py-0.5 rounded-full">Main</span><?php endif; ?>
              <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                <?php if (!$img['is_primary']): ?><a href="?id=<?= $pid ?>&action=set_primary&img=<?= (int)$img['id'] ?>&csrf_token=<?= csrf_token() ?>" class="w-8 h-8 rounded-full bg-white/90 flex items-center justify-center text-slate-700" title="Set as main"><i class="fa-solid fa-star text-xs"></i></a><?php endif; ?>
                <a href="?id=<?= $pid ?>&action=delete_image&img=<?= (int)$img['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this image?" class="w-8 h-8 rounded-full bg-white/90 flex items-center justify-center text-red-600" title="Delete"><i class="fa-solid fa-trash text-xs"></i></a>
              </div>
            </div>
            <input type="text" name="image_variation[<?= (int)$img['id'] ?>]" value="<?= h($img['variation_value'] ?? '') ?>" placeholder="Color e.g. Red" class="f-input mt-1.5 text-xs" style="padding:5px 8px">
            <select name="image_mockup_view[<?= (int)$img['id'] ?>]" class="f-select mt-1.5 text-xs" style="padding:5px 8px">
              <option value="">Mockup view: none</option>
              <option value="front" <?= (($img['mockup_view'] ?? '') === 'front') ? 'selected' : '' ?>>Front template</option>
              <option value="back" <?= (($img['mockup_view'] ?? '') === 'back') ? 'selected' : '' ?>>Back template</option>
              <option value="sleeve" <?= (($img['mockup_view'] ?? '') === 'sleeve') ? 'selected' : '' ?>>Sleeve template</option>
              <option value="shorts" <?= (($img['mockup_view'] ?? '') === 'shorts') ? 'selected' : '' ?>>Shorts template</option>
            </select>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <input type="file" name="images[]" accept="image/*" multiple class="f-input">
        <p class="f-hint">You can select multiple images at once. Recommended: square, at least 1000×1000px. If this product uses the Customizer with a "Color" variation, tag each photo with the color it shows so the customizer can switch photos.</p>
      </div>

      <!-- 360° Spin Viewer -->
      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-arrows-spin text-sky-500 mr-1.5"></i>360° Spin Viewer</h2>
        <p class="f-hint mb-4">Upload 8+ photos taken while rotating the product in even steps (e.g. every 15°) on a turntable. The storefront lets shoppers drag to spin through them. Fewer than 8 photos won't activate the viewer.</p>
        <?php if ($spin_frames): ?>
        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-4">
          <?php foreach ($spin_frames as $i => $sf): ?>
          <div class="relative group rounded-lg overflow-hidden border border-slate-200 aspect-square">
            <img src="<?= UPLOAD_URL . h($sf['image_path']) ?>" class="w-full h-full object-cover">
            <span class="absolute top-1 left-1 bg-black/60 text-white text-[10px] font-bold px-1.5 py-0.5 rounded"><?= $i + 1 ?></span>
            <a href="?id=<?= $pid ?>&action=delete_spin_frame&frame=<?= (int)$sf['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this spin frame?" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-red-400"><i class="fa-solid fa-trash"></i></a>
          </div>
          <?php endforeach; ?>
        </div>
        <p class="f-hint mb-3"><?= count($spin_frames) ?> frame<?= count($spin_frames) === 1 ? '' : 's' ?> — <?= count($spin_frames) >= 8 ? 'viewer is active on the product page.' : 'need ' . (8 - count($spin_frames)) . ' more to activate the viewer.' ?></p>
        <?php endif; ?>
        <input type="file" name="spin_frames[]" accept="image/*" multiple class="f-input">
        <p class="f-hint">Select all frames at once, in rotation order — they'll be added in the order your file picker lists them.</p>
      </div>

      <!-- Variations -->
      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-1">Variations</h2>
        <p class="f-hint mb-4">Add option types like Size or Color. Every combination gets its own price, stock &amp; SKU.</p>

        <div id="typesList" class="space-y-3 mb-4"></div>

        <div class="flex items-end gap-2 p-3 rounded-lg bg-slate-50 border border-slate-200">
          <div class="flex-1">
            <label class="f-label text-xs">Type Name</label>
            <input type="text" id="newTypeName" class="f-input" placeholder="e.g. Size">
          </div>
          <div class="flex-1">
            <label class="f-label text-xs">Options <span class="text-slate-400 font-normal">(comma separated)</span></label>
            <input type="text" id="newTypeOptions" class="f-input" placeholder="Small, Medium, Large">
          </div>
          <button type="button" onclick="addVariationType()" class="btn-outline shrink-0"><i class="fa-solid fa-plus"></i> Add Type</button>
        </div>

        <div id="varyToggles" class="mt-5 space-y-2 hidden">
          <div class="p-3 rounded-lg border border-slate-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <input type="checkbox" id="priceVariesToggle" class="w-4 h-4 rounded accent-brand" onchange="onVariesToggle('price')" <?= $had_price_data ? 'checked' : '' ?>>
                <label for="priceVariesToggle" class="text-sm font-semibold text-slate-700">Price varies by variation</label>
              </div>
            </div>
            <div id="priceBasisWrap" class="hidden mt-2.5 pl-6">
              <p class="f-hint mb-1.5">Base the price on:</p>
              <div id="priceBasisChips" class="flex flex-wrap items-center gap-1.5"></div>
              <div id="priceGroupsWrap" class="hidden mt-3 space-y-2"></div>
            </div>
          </div>
          <div class="p-3 rounded-lg border border-slate-200">
            <div class="flex items-center gap-2">
              <input type="checkbox" id="qtyVariesToggle" class="w-4 h-4 rounded accent-brand" onchange="onVariesToggle('qty')" <?= $had_stock_data ? 'checked' : '' ?>>
              <label for="qtyVariesToggle" class="text-sm font-semibold text-slate-700">Quantity varies by variation</label>
            </div>
            <div id="qtyBasisWrap" class="hidden mt-2.5 pl-6">
              <p class="f-hint mb-1.5">Track stock by:</p>
              <div id="qtyBasisChips" class="flex flex-wrap items-center gap-1.5"></div>
              <div id="qtyGroupsWrap" class="hidden mt-3 space-y-2"></div>
            </div>
          </div>
        </div>

        <div id="comboWrap" class="mt-5 hidden">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Combinations &amp; SKU</h3>
          <p class="f-hint mb-2">Every option combination still gets its own SKU here, regardless of how price/stock are grouped above.</p>
          <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-sm">
              <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wider text-slate-400" id="comboHeadRow">
                  <th class="px-3 py-2.5 font-semibold">Combination</th>
                  <th class="px-3 py-2.5 font-semibold w-32">SKU</th>
                </tr>
              </thead>
              <tbody id="comboBody" class="divide-y divide-slate-100"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SEO -->
      <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-6">
        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-3 flex items-center gap-1.5">
          <i class="fa-solid fa-magnifying-glass-chart"></i> SEO Settings
        </h3>
        <div class="mb-3">
          <div class="flex items-center justify-between"><label class="f-label mb-1">Meta Title</label><span id="metaTitleCount" class="text-xs font-semibold"></span></div>
          <input type="text" name="meta_title" id="metaTitle" class="f-input" maxlength="70" value="<?= h($edit['meta_title'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <div class="flex items-center justify-between"><label class="f-label mb-1">Meta Description</label><span id="metaDescCount" class="text-xs font-semibold"></span></div>
          <textarea name="meta_description" id="metaDesc" rows="2" class="f-textarea" maxlength="160"><?= h($edit['meta_description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="f-label">Focus Keyword</label>
          <input type="text" name="focus_keyword" class="f-input" value="<?= h($edit['focus_keyword'] ?? '') ?>">
        </div>
        <div>
          <label class="f-label">Canonical URL <span class="text-slate-400 font-normal">(optional)</span></label>
          <input type="text" name="canonical_url" class="f-input" value="<?= h($edit['canonical_url'] ?? '') ?>">
        </div>
      </div>
    </div>

    <div class="space-y-6">
      <!-- Pricing & inventory -->
      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-4">Pricing &amp; Inventory</h2>
        <div class="grid grid-cols-2 gap-3 mb-4">
          <div>
            <label class="f-label">Base Price *</label>
            <input type="number" step="0.01" min="0" name="base_price" required class="f-input" value="<?= h($edit['base_price'] ?? '0') ?>">
          </div>
          <div>
            <label class="f-label">Sale Price</label>
            <input type="number" step="0.01" min="0" name="sale_price" class="f-input" value="<?= h($edit['sale_price'] ?? '') ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
          <div>
            <label class="f-label">Stock Qty</label>
            <input type="number" min="0" name="stock_quantity" class="f-input" value="<?= h($edit['stock_quantity'] ?? '0') ?>">
          </div>
          <div>
            <label class="f-label">Weight (kg)</label>
            <input type="number" step="0.01" min="0" name="weight_kg" class="f-input" value="<?= h($edit['weight_kg'] ?? '') ?>">
          </div>
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" name="track_stock" id="trackStock" value="1" class="w-4 h-4 rounded accent-brand" <?= (!$edit || $edit['track_stock']) ? 'checked' : '' ?>>
          <label for="trackStock" class="text-sm font-medium text-slate-700">Track stock quantity</label>
        </div>
      </div>

      <!-- Bulk / wholesale pricing -->
      <div class="card p-6 border-amber-100 bg-amber-50/30">
        <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-boxes-stacked text-amber-500 mr-1.5"></i>Bulk Order Pricing</h2>
        <p class="f-hint mb-4">Offer a lower per-unit price at quantity breaks — shown automatically on the product page.</p>

        <div id="qtyRulesList" class="space-y-2 mb-3"></div>
        <button type="button" onclick="addQtyRule()" class="btn-outline btn-sm w-full mb-4"><i class="fa-solid fa-plus"></i> Add Quantity Tier</button>

        <div class="flex items-center gap-2 pt-3 border-t border-amber-100">
          <input type="checkbox" name="show_bulk_dm" id="showBulkDm" value="1" class="w-4 h-4 rounded accent-brand" <?= (!$edit || $edit['show_bulk_dm']) ? 'checked' : '' ?>>
          <label for="showBulkDm" class="text-sm font-medium text-slate-700">Show "Request Bulk Quote" button</label>
        </div>
        <p class="f-hint">Lets buyers submit a custom bulk quantity inquiry — you'll see it under <a href="<?= ADMIN_URL ?>/pages/bulk-inquiries.php" class="text-brand font-semibold">Bulk Inquiries</a>.</p>
      </div>

      <!-- Status & visibility -->
      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-4">Status &amp; Visibility</h2>
        <div class="mb-4">
          <label class="f-label">Status</label>
          <select name="status" class="f-select">
            <option value="draft" <?= (($edit['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
            <option value="active" <?= (($edit['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active (published)</option>
            <option value="archived" <?= (($edit['status'] ?? '') === 'archived') ? 'selected' : '' ?>>Archived</option>
          </select>
        </div>
        <div class="flex items-center gap-2 mb-3">
          <input type="checkbox" name="is_featured" id="isFeatured" value="1" class="w-4 h-4 rounded accent-brand" <?= !empty($edit['is_featured']) ? 'checked' : '' ?>>
          <label for="isFeatured" class="text-sm font-medium text-slate-700">Featured on homepage</label>
        </div>
        <div class="flex items-center gap-2 mb-3">
          <input type="checkbox" name="is_new_arrival" id="isNew" value="1" class="w-4 h-4 rounded accent-brand" <?= !empty($edit['is_new_arrival']) ? 'checked' : '' ?>>
          <label for="isNew" class="text-sm font-medium text-slate-700">Mark as new arrival</label>
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_coming_soon" id="isComingSoon" value="1" class="w-4 h-4 rounded accent-brand" <?= !empty($edit['is_coming_soon']) ? 'checked' : '' ?>>
          <label for="isComingSoon" class="text-sm font-medium text-slate-700">Mark as coming soon</label>
        </div>
      </div>

      <!-- Customizer -->
      <div class="card p-6 border-indigo-100 bg-indigo-50/30">
        <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-shirt text-indigo-500 mr-1.5"></i>Product Customizer</h2>
        <p class="f-hint mb-4">Let shoppers pick a color, upload their own logo, and add a custom name/number on the product page.</p>
        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_customizable" id="isCustomizable" value="1" class="w-4 h-4 rounded accent-brand" <?= !empty($edit['is_customizable']) ? 'checked' : '' ?>>
          <label for="isCustomizable" class="text-sm font-medium text-slate-700">Enable customizer on this product</label>
        </div>
        <p class="f-hint mt-2">Tag each photo above with its color (via the field under the thumbnail) so the preview swaps correctly when a color is chosen.</p>
      </div>

      <!-- AI Design Requests -->
      <div class="card p-6 border-purple-100 bg-purple-50/30">
        <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-image text-purple-500 mr-1.5"></i>AI Design Requests</h2>
        <p class="f-hint mb-4">Let shoppers upload their own reference photo — you vectorize it and generate front/back/sleeve mockups from the <a href="<?= ADMIN_URL ?>/pages/design-requests.php" class="text-brand font-semibold">Design Requests</a> page.</p>
        <div class="flex items-center gap-2">
          <input type="checkbox" name="accepts_design_requests" id="acceptsDesignRequests" value="1" class="w-4 h-4 rounded accent-brand" <?= !empty($edit['accepts_design_requests']) ? 'checked' : '' ?>>
          <label for="acceptsDesignRequests" class="text-sm font-medium text-slate-700">Show "Get a Custom Design" upload form on this product</label>
        </div>
        <p class="f-hint mt-2">Tag photos above as "Front template" / "Back template" / "Sleeve template" (plain, blank garment shots) so mockups can be generated for those views.</p>
      </div>
    </div>
  </div>
</form>

<script src="<?= SITE_URL ?>/assets/vendor/quill/quill.min.js"></script>
<script>
/* ---------- Rich text description editor ---------- */
const quillDesc = new Quill('#descEditor', {
  theme: 'snow',
  placeholder: 'Describe the product in detail…',
  modules: {
    toolbar: [
      [{ header: [2, 3, false] }],
      ['bold', 'italic', 'underline'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['link', 'blockquote'],
      ['clean'],
    ],
  },
});
quillDesc.root.innerHTML = <?= json_encode($edit['description'] ?? '') ?>;

/* ---------- Slug auto-fill ---------- */
(function () {
  const nameEl = document.getElementById('pName'), slugEl = document.getElementById('pSlug');
  let touched = <?= $edit ? 'true' : 'false' ?>;
  const slugify = s => s.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '');
  nameEl.addEventListener('input', () => { if (!touched) slugEl.value = slugify(nameEl.value); });
  slugEl.addEventListener('input', () => touched = true);
})();

/* ---------- SEO counters ---------- */
(function () {
  const titleEl = document.getElementById('metaTitle'), titleCount = document.getElementById('metaTitleCount');
  const descEl = document.getElementById('metaDesc'), descCount = document.getElementById('metaDescCount');
  function paint(el, len, ideal) {
    el.textContent = len + ' / ' + ideal;
    el.className = 'text-xs font-semibold ' + (len === 0 ? 'text-slate-300' : len <= ideal ? 'seo-count-ok' : (len <= ideal + 15 ? 'seo-count-warn' : 'seo-count-bad'));
  }
  function update() { paint(titleCount, titleEl.value.length, 60); paint(descCount, descEl.value.length, 155); }
  titleEl.addEventListener('input', update); descEl.addEventListener('input', update); update();
})();

/* ---------- Bulk quantity pricing tiers ---------- */
let qtyRules = <?= json_encode($initial_qty_rules ?: []) ?>;
function renderQtyRules() {
  const wrap = document.getElementById('qtyRulesList');
  wrap.innerHTML = qtyRules.length ? '' : '<p class="text-xs text-slate-400">No bulk tiers yet — e.g. "Buy 10+, pay $9.50 each".</p>';
  qtyRules.forEach((r, i) => {
    const row = document.createElement('div');
    row.className = 'flex items-center gap-2';
    row.innerHTML = `
      <span class="text-xs text-slate-500 whitespace-nowrap">Qty ≥</span>
      <input type="number" min="1" class="f-input" style="padding:8px 10px" value="${r.min_qty}" oninput="qtyRules[${i}].min_qty=this.value">
      <span class="text-xs text-slate-500">@ $</span>
      <input type="number" step="0.01" min="0" class="f-input" style="padding:8px 10px" value="${r.price}" oninput="qtyRules[${i}].price=this.value">
      <button type="button" onclick="removeQtyRule(${i})" class="text-slate-300 hover:text-red-500 shrink-0"><i class="fa-solid fa-xmark"></i></button>`;
    wrap.appendChild(row);
  });
}
function addQtyRule() { qtyRules.push({ min_qty: '', price: '' }); renderQtyRules(); }
function removeQtyRule(i) { qtyRules.splice(i, 1); renderQtyRules(); }
renderQtyRules();

/* ---------- Etsy-style variation builder ---------- */
let vTypes = <?= json_encode($initial_types) ?>;
let vCombos = <?= json_encode($initial_combos) ?>;
// Combos loaded from a saved product already have a real SKU (or were
// deliberately left blank) — never let the auto-suggest overwrite those.
vCombos.forEach(c => { c.autoSku = false; });
let priceVaries = <?= $had_price_data ? 'true' : 'false' ?>, qtyVaries = <?= $had_stock_data ? 'true' : 'false' ?>;
let priceBasis = [], qtyBasis = [];

function escHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
function validTypes() { return vTypes.filter(t => t.options.length); }

function renderTypes() {
  const wrap = document.getElementById('typesList');
  wrap.innerHTML = '';
  vTypes.forEach((t, ti) => {
    const card = document.createElement('div');
    card.className = 'flex items-start gap-3 p-3 rounded-lg border border-slate-200';
    card.innerHTML = `
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-2">
          <span class="font-semibold text-sm text-slate-700">${escHtml(t.name)}</span>
        </div>
        <div class="flex flex-wrap gap-1.5">
          ${t.options.map((o, oi) => `<span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 text-xs font-medium px-2.5 py-1 rounded-full">${escHtml(o)} <a href="#" onclick="removeOption(${ti},${oi});return false" class="text-slate-400 hover:text-red-500">&times;</a></span>`).join('')}
        </div>
      </div>
      <button type="button" onclick="removeType(${ti})" class="text-slate-300 hover:text-red-500"><i class="fa-solid fa-trash text-sm"></i></button>`;
    wrap.appendChild(card);
  });
  // Drop basis selections that reference a type that no longer exists.
  const names = validTypes().map(t => t.name);
  priceBasis = priceBasis.filter(n => names.includes(n));
  qtyBasis = qtyBasis.filter(n => names.includes(n));
  regenerateCombinations();
}

function addVariationType() {
  const nameInput = document.getElementById('newTypeName');
  const optsInput = document.getElementById('newTypeOptions');
  const name = nameInput.value.trim();
  const options = optsInput.value.split(',').map(s => s.trim()).filter(Boolean);
  if (!name || !options.length) { alert('Enter a type name and at least one option.'); return; }
  vTypes.push({ name, options });
  nameInput.value = ''; optsInput.value = '';
  renderTypes();
}
function removeType(ti) { vTypes.splice(ti, 1); renderTypes(); }
function removeOption(ti, oi) { vTypes[ti].options.splice(oi, 1); renderTypes(); }

function cartesian(arrays) {
  return arrays.reduce((acc, curr) => acc.flatMap(a => curr.map(c => [...a, c])), [[]]);
}

// Suggests a SKU for a new combination by extending the product's own SKU with
// its variation values, e.g. main SKU "BG-001" + "Red / Large" -> "BG-001-RED-LARGE".
// Only used to pre-fill brand-new rows — existing/edited SKUs are never touched.
function suggestVariationSku(comboKey) {
  const mainSku = (document.getElementById('pSku').value || '').trim();
  const suffix = comboKey.split(' / ').map(v => v.trim().toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-+|-+$/g, '')).filter(Boolean).join('-');
  if (!mainSku) return suffix;
  return suffix ? `${mainSku}-${suffix}` : mainSku;
}

function regenerateCombinations() {
  const types = validTypes();
  const toggles = document.getElementById('varyToggles');
  const wrapDiv = document.getElementById('comboWrap');
  if (!types.length) {
    toggles.classList.add('hidden'); wrapDiv.classList.add('hidden');
    vCombos = []; document.getElementById('comboBody').innerHTML = ''; return;
  }

  const combos = cartesian(types.map(t => t.options)).map(parts => parts.join(' / '));
  const existing = {};
  vCombos.forEach(c => existing[c.key] = c);
  vCombos = combos.map(key => existing[key] || { key, price: '', sale_price: '', stock: 0, sku: suggestVariationSku(key), autoSku: true });

  document.getElementById('priceBasisWrap').classList.toggle('hidden', !priceVaries);
  document.getElementById('qtyBasisWrap').classList.toggle('hidden', !qtyVaries);

  toggles.classList.remove('hidden');
  wrapDiv.classList.remove('hidden');
  renderBasisChips('price');
  renderBasisChips('qty');
  renderPriceGroups();
  renderStockGroups();
  renderComboTable();
}

/* Turning a toggle on just reveals the option chips — no price/stock boxes appear
   until the admin actually picks which option(s) to base them on. */
function onVariesToggle(kind) {
  const on = document.getElementById(kind + 'VariesToggle').checked;
  if (kind === 'price') {
    priceVaries = on;
    if (!on) priceBasis = [];
    document.getElementById('priceBasisWrap').classList.toggle('hidden', !on);
    renderBasisChips('price'); renderPriceGroups();
  } else {
    qtyVaries = on;
    if (!on) qtyBasis = [];
    document.getElementById('qtyBasisWrap').classList.toggle('hidden', !on);
    renderBasisChips('qty'); renderStockGroups();
  }
}

function renderBasisChips(kind) {
  const wrap = document.getElementById(kind + 'BasisChips');
  const basis = kind === 'price' ? priceBasis : qtyBasis;
  wrap.innerHTML = validTypes().map(t => {
    const active = basis.includes(t.name);
    return `<button type="button" onclick="toggleBasis('${kind}','${t.name.replace(/'/g, "\\'")}')"
      class="text-xs font-semibold px-3 py-1.5 rounded-full border transition ${active ? 'bg-brand text-white border-brand' : 'bg-white text-slate-500 border-slate-300 hover:border-slate-400'}">
      ${active ? '<i class="fa-solid fa-check mr-1"></i>' : ''}${escHtml(t.name)}
    </button>`;
  }).join('') + (!basis.length ? `<span class="text-xs text-slate-400 italic self-center">Pick an option — e.g. just "Color" — and its price/stock boxes appear below, without Size involved.</span>` : '');
}

function toggleBasis(kind, name) {
  const basis = kind === 'price' ? priceBasis : qtyBasis;
  const idx = basis.indexOf(name);
  if (idx > -1) basis.splice(idx, 1);
  else basis.push(name);
  renderBasisChips(kind);
  if (kind === 'price') renderPriceGroups(); else renderStockGroups();
}

/* Key built only from the types selected as the basis — every combination sharing this
   key (e.g. every row where Color = "Red") is the SAME group and gets ONE shared value. */
function groupKey(comboKey, basisNames) {
  const types = validTypes();
  const parts = comboKey.split(' / ');
  const idxs = basisNames.map(n => types.findIndex(t => t.name === n)).filter(i => i >= 0);
  return idxs.map(i => parts[i]).join(' / ');
}

/* One representative combo per unique group (in basis order), so the UI shows exactly
   one input per selected option value — e.g. basis=["Color"] → just "Red" and "Black". */
function uniqueGroups(basisNames) {
  const map = new Map();
  vCombos.forEach(c => { const k = groupKey(c.key, basisNames); if (!map.has(k)) map.set(k, c); });
  return map;
}

function renderPriceGroups() {
  const outer = document.getElementById('priceGroupsWrap');
  if (!priceVaries || !priceBasis.length) { outer.classList.add('hidden'); outer.innerHTML = ''; return; }
  outer.classList.remove('hidden');
  outer.innerHTML = Array.from(uniqueGroups(priceBasis).entries()).map(([key, c]) => `
    <div class="flex items-center gap-3 p-2.5 rounded-lg border border-slate-200 bg-white">
      <span class="text-sm font-medium text-slate-700 w-28 shrink-0">${escHtml(key)}</span>
      <div class="flex-1"><input type="number" step="0.01" min="0" class="f-input" style="padding:6px 8px" placeholder="Price" value="${c.price}" oninput="setGroupValue('${key.replace(/'/g, "\\'")}','price',this.value)"></div>
      <div class="flex-1"><input type="number" step="0.01" min="0" class="f-input" style="padding:6px 8px" placeholder="Sale price" value="${c.sale_price}" oninput="setGroupValue('${key.replace(/'/g, "\\'")}','sale_price',this.value)"></div>
    </div>`).join('');
}

function renderStockGroups() {
  const outer = document.getElementById('qtyGroupsWrap');
  if (!qtyVaries || !qtyBasis.length) { outer.classList.add('hidden'); outer.innerHTML = ''; return; }
  outer.classList.remove('hidden');
  outer.innerHTML = Array.from(uniqueGroups(qtyBasis).entries()).map(([key, c]) => `
    <div class="flex items-center gap-3 p-2.5 rounded-lg border border-slate-200 bg-white">
      <span class="text-sm font-medium text-slate-700 w-28 shrink-0">${escHtml(key)}</span>
      <input type="number" min="0" class="f-input flex-1" style="padding:6px 8px" placeholder="Stock" value="${c.stock}" oninput="setGroupValue('${key.replace(/'/g, "\\'")}','stock',this.value)">
    </div>`).join('');
}

/* Writes one grouped value into every underlying combination that belongs to that group. */
function setGroupValue(key, field, value) {
  const basis = field === 'stock' ? qtyBasis : priceBasis;
  vCombos.forEach(c => { if (groupKey(c.key, basis) === key) c[field] = value; });
}

function renderComboTable() {
  const body = document.getElementById('comboBody');
  body.innerHTML = vCombos.map((c, i) => `
    <tr>
      <td class="px-3 py-2 font-medium text-sm text-slate-700">${escHtml(c.key)}</td>
      <td class="px-3 py-2"><input type="text" class="f-input" style="padding:6px 8px" value="${escHtml(c.sku)}" oninput="vCombos[${i}].sku=this.value; vCombos[${i}].autoSku=false"></td>
    </tr>`).join('');
}

// If the admin fills in / edits the main SKU after variations already exist,
// refresh the suggestion for any combo SKU they haven't hand-edited yet —
// combos they've already typed a custom SKU into are left alone.
document.getElementById('pSku').addEventListener('input', () => {
  let changed = false;
  vCombos.forEach(c => {
    if (c.autoSku !== false) { c.sku = suggestVariationSku(c.key); c.autoSku = true; changed = true; }
  });
  if (changed) renderComboTable();
});

// One-time hydration for products saved before this toggle UI existed — they already
// have real per-combination price/stock, so default the basis to every type so nothing
// looks blank. A fresh product (or one where a toggle is off) starts with no basis picked.
if (priceVaries) priceBasis = validTypes().map(t => t.name);
if (qtyVaries) qtyBasis = validTypes().map(t => t.name);
renderTypes();

/* ---------- Serialize on submit ---------- */
document.getElementById('productForm').addEventListener('submit', function () {
  document.getElementById('descHidden').value = quillDesc.root.innerHTML;
  document.getElementById('qtyRulesJson').value = JSON.stringify(qtyRules.filter(r => r.min_qty && r.price !== ''));
  document.getElementById('variationsJson').value = JSON.stringify({ types: vTypes, combinations: vCombos });
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
