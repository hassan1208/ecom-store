<?php
// catalog.php — printable/downloadable product catalog for wholesale & export
// buyers. Standalone page (own <html>, no site header/footer) styled the same
// way invoice.php is: a "Print / Save as PDF" toolbar plus @media print rules,
// since this project has no PDF library and the browser's print-to-PDF gives
// an identical result without one.
require_once __DIR__ . '/includes/config.php';

$site_name = setting('site_name', '');
$logo      = setting('site_logo', '');
$tagline   = setting('site_tagline', '');
$phone     = setting('contact_phone', '');
$email     = setting('contact_email', '');
$address   = setting('contact_address', '');
$primary   = setting('theme_primary_color', '#ff4d2e');

$certifications = get_certifications();

$categories = fetch_all("SELECT * FROM categories WHERE status='active' ORDER BY nav_order ASC, name ASC");
$products_by_category = [];
$uncategorized = [];
foreach ($categories as $cat) {
    $products_by_category[$cat['id']] = fetch_all(
        "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS thumb
         FROM products p WHERE p.category_id=? AND p.status='active' ORDER BY p.name ASC",
        'i', $cat['id']
    );
}
$uncategorized = fetch_all(
    "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS thumb
     FROM products p WHERE p.category_id IS NULL AND p.status='active' ORDER BY p.name ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, follow">
<title>Product Catalog | <?= h($site_name) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Inter', Arial, sans-serif; color: #1e293b; margin: 0; background: #f1f5f9; }
  .toolbar { background: #0b0f14; padding: 14px 20px; display: flex; justify-content: center; gap: 10px; position: sticky; top: 0; z-index: 10; }
  .toolbar button { background: <?= h($primary) ?>; color: #fff; border: none; padding: 10px 20px; border-radius: 999px; font-weight: 600; font-size: 13px; cursor: pointer; }
  .sheet { max-width: 960px; margin: 0 auto; background: #fff; padding: 40px; }
  .cover { text-align: center; padding: 30px 0 40px; border-bottom: 3px solid <?= h($primary) ?>; margin-bottom: 30px; }
  .cover img.logo { height: 48px; margin-bottom: 14px; }
  .cover h1 { font-size: 30px; margin: 0 0 6px; letter-spacing: 1px; }
  .cover p { color: #64748b; font-size: 14px; margin: 2px 0; }
  .cover .meta { margin-top: 16px; font-size: 12.5px; color: #94a3b8; }
  .certs { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
  .cert-badge { display: flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 999px; padding: 6px 12px; font-size: 11.5px; color: #475569; }
  .cert-badge img { width: 18px; height: 18px; object-fit: cover; border-radius: 50%; }
  .cat-title { font-size: 18px; font-weight: 700; margin: 30px 0 14px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; break-after: avoid; }
  .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  .prod { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; break-inside: avoid; }
  .prod img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 6px; margin-bottom: 8px; background: #f1f5f9; }
  .prod .noimg { width: 100%; aspect-ratio: 1; border-radius: 6px; margin-bottom: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 11px; }
  .prod .name { font-weight: 600; font-size: 13px; margin-bottom: 2px; }
  .prod .sku { font-size: 10.5px; color: #94a3b8; margin-bottom: 4px; }
  .prod .price { font-weight: 700; font-size: 13.5px; color: <?= h($primary) ?>; }
  .prod .bulk { font-size: 10.5px; color: #64748b; margin-top: 4px; }
  .foot { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center; }
  @media print {
    .toolbar { display: none; }
    body { background: #fff; }
    .sheet { padding: 0; max-width: none; }
    .cat-title { break-before: auto; }
  }
</style>
</head>
<body>
<div class="toolbar">
  <button onclick="window.print()">🖨 Print / Save as PDF</button>
</div>
<div class="sheet">
  <div class="cover">
    <?php if ($logo): ?><img class="logo" src="<?= UPLOAD_URL . h($logo) ?>" alt="<?= h($site_name) ?>"><?php else: ?><h1><?= h($site_name) ?></h1><?php endif; ?>
    <?php if ($tagline): ?><p><?= h($tagline) ?></p><?php endif; ?>
    <h1 style="font-size:20px;margin-top:14px">Product Catalog</h1>
    <div class="meta">
      <?php if ($address): ?><?= h($address) ?><br><?php endif; ?>
      <?php echo trim(implode(' &middot; ', array_filter([$phone, $email]))); ?>
    </div>
    <?php if ($certifications): ?>
    <div class="certs">
      <?php foreach ($certifications as $c): ?>
      <div class="cert-badge">
        <?php if ($c['image']): ?><img src="<?= UPLOAD_URL . h($c['image']) ?>" alt=""><?php endif; ?>
        <span><?= h($c['title']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php foreach ($categories as $cat): $prods = $products_by_category[$cat['id']]; if (!$prods) continue; ?>
  <div class="cat-title"><?= h($cat['name']) ?></div>
  <div class="grid">
    <?php foreach ($prods as $p): $rules = $p['qty_price_rules'] ? json_decode($p['qty_price_rules'], true) : []; ?>
    <div class="prod">
      <?php if ($p['thumb']): ?><img src="<?= UPLOAD_URL . h($p['thumb']) ?>" alt="<?= h($p['name']) ?>"><?php else: ?><div class="noimg">No Image</div><?php endif; ?>
      <div class="name"><?= h($p['name']) ?></div>
      <?php if ($p['sku']): ?><div class="sku">SKU: <?= h($p['sku']) ?></div><?php endif; ?>
      <div class="price"><?= format_price($p['sale_price'] ?: $p['base_price']) ?></div>
      <?php if ($rules): ?>
      <div class="bulk">Bulk: <?php foreach ($rules as $r): ?><?= (int)$r['min_qty'] ?>+ @ <?= format_price($r['price']) ?>&nbsp; <?php endforeach; ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <?php if ($uncategorized): ?>
  <div class="cat-title">Other Products</div>
  <div class="grid">
    <?php foreach ($uncategorized as $p): $rules = $p['qty_price_rules'] ? json_decode($p['qty_price_rules'], true) : []; ?>
    <div class="prod">
      <?php if ($p['thumb']): ?><img src="<?= UPLOAD_URL . h($p['thumb']) ?>" alt="<?= h($p['name']) ?>"><?php else: ?><div class="noimg">No Image</div><?php endif; ?>
      <div class="name"><?= h($p['name']) ?></div>
      <?php if ($p['sku']): ?><div class="sku">SKU: <?= h($p['sku']) ?></div><?php endif; ?>
      <div class="price"><?= format_price($p['sale_price'] ?: $p['base_price']) ?></div>
      <?php if ($rules): ?>
      <div class="bulk">Bulk: <?php foreach ($rules as $r): ?><?= (int)$r['min_qty'] ?>+ @ <?= format_price($r['price']) ?>&nbsp; <?php endforeach; ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="foot">
    Prices subject to change without notice. For bulk/wholesale quotes, contact us at <?= h($email ?: $phone) ?>.
    Generated on <?= date('M j, Y') ?> &middot; <?= h(SITE_URL) ?>
  </div>
</div>
</body>
</html>
