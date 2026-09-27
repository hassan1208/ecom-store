<?php
// admin/pages/products.php — product catalog list
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Products';

if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $p = fetch_one("SELECT status FROM products WHERE id=?", 'i', $id);
    if ($p) update_record('products', ['status' => $p['status'] === 'active' ? 'draft' : 'active'], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/products.php'); exit;
}
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $images = fetch_all("SELECT image_path FROM product_images WHERE product_id=?", 'i', $id);
    db()->query("DELETE FROM products WHERE id=" . $id); // cascades images/variations
    foreach ($images as $img) { if (file_exists(UPLOAD_PATH . $img['image_path'])) @unlink(UPLOAD_PATH . $img['image_path']); }
    set_flash('success', 'Product deleted.');
    header('Location: ' . ADMIN_URL . '/pages/products.php'); exit;
}

$search   = sanitize($_GET['q'] ?? '');
$cat_filt = (int)($_GET['category'] ?? 0);
$where = ['1=1']; $types = ''; $params = [];
if ($search !== '') { $where[] = "p.name LIKE ?"; $types .= 's'; $params[] = "%$search%"; }
if ($cat_filt) { $where[] = "p.category_id = ?"; $types .= 'i'; $params[] = $cat_filt; }
$where_sql = implode(' AND ', $where);

$products = fetch_all(
    "SELECT p.*, c.name AS category_name,
            (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS thumb,
            (SELECT COUNT(*) FROM product_variations WHERE product_id=p.id) AS variation_count
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE $where_sql ORDER BY p.created_at DESC",
    $types, ...$params
);
$categories = fetch_all("SELECT id, name FROM categories ORDER BY name ASC");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
  <form method="GET" class="flex items-center gap-2 flex-1 max-w-lg">
    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search products…" class="f-input">
    <select name="category" class="f-select w-48" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= $cat_filt == $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-outline shrink-0"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>
  <a href="<?= ADMIN_URL ?>/pages/product-edit.php" class="btn-primary shrink-0"><i class="fa-solid fa-plus"></i> Add Product</a>
</div>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Product</th>
          <th class="px-5 py-3 font-semibold">Category</th>
          <th class="px-5 py-3 font-semibold">Price</th>
          <th class="px-5 py-3 font-semibold">Stock</th>
          <th class="px-5 py-3 font-semibold">Views</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($products as $p): ?>
        <tr class="hover:bg-slate-50/60">
          <td class="px-5 py-3">
            <div class="flex items-center gap-3">
              <?php if ($p['thumb']): ?>
                <img src="<?= UPLOAD_URL . h($p['thumb']) ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200">
              <?php else: ?>
                <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-300 flex items-center justify-center"><i class="fa-solid fa-image"></i></div>
              <?php endif; ?>
              <div>
                <div class="font-semibold text-slate-800 text-[13px] flex items-center gap-1.5">
                  <?= h($p['name']) ?>
                  <?php if ($p['is_featured']): ?><i class="fa-solid fa-star text-amber-400 text-[10px]" title="Featured"></i><?php endif; ?>
                </div>
                <div class="text-[11px] text-slate-400"><?= $p['variation_count'] ? $p['variation_count'] . ' variations' : ($p['sku'] ? 'SKU: ' . h($p['sku']) : '') ?></div>
              </div>
            </div>
          </td>
          <td class="px-5 py-3 text-slate-500"><?= h($p['category_name'] ?? '—') ?></td>
          <td class="px-5 py-3">
            <?php if ($p['sale_price']): ?>
              <span class="font-semibold text-slate-800"><?= format_price($p['sale_price']) ?></span>
              <span class="text-xs text-slate-400 line-through ml-1"><?= format_price($p['base_price']) ?></span>
            <?php else: ?>
              <span class="font-semibold text-slate-800"><?= format_price($p['base_price']) ?></span>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3">
            <?php if (!$p['track_stock']): ?><span class="text-xs text-slate-400">Not tracked</span>
            <?php elseif ($p['stock_quantity'] <= 0): ?><span class="badge bg-red-100 text-red-600">Out of stock</span>
            <?php elseif ($p['stock_quantity'] <= 5): ?><span class="badge bg-amber-100 text-amber-600"><?= $p['stock_quantity'] ?> left</span>
            <?php else: ?><span class="text-slate-600"><?= $p['stock_quantity'] ?></span>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3 text-slate-500"><i class="fa-solid fa-eye text-slate-300 mr-1 text-xs"></i><?= (int)$p['views'] ?></td>
          <td class="px-5 py-3">
            <span class="<?= $p['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($p['status']) ?></span>
          </td>
          <td class="px-5 py-3">
            <div class="flex items-center justify-end gap-2">
              <a href="product-edit.php?id=<?= (int)$p['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
              <a href="?action=toggle&id=<?= (int)$p['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-outline btn-sm"><i class="fa-solid <?= $p['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i></a>
              <a href="?action=delete&id=<?= (int)$p['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this product permanently? This cannot be undone." class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
        <tr><td colspan="7" class="px-5 py-14 text-center text-slate-400">No products yet. <a href="product-edit.php" class="text-brand font-semibold">Add your first product</a>.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
