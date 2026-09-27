<?php
// includes/product-listing.php — shared filter / sort / paginate logic and UI
// for the Shop, Category and Search pages.
//
// SEO rules applied here:
//   * canonical = the base listing URL (+ ?page=N for N > 1); sort / filter
//     variants point their canonical at it and are marked "noindex, follow"
//     so crawlers never index hundreds of near-duplicate filtered pages.
//   * rel=prev / rel=next on paginated pages.
//   * ItemList structured data for the products on the current page.

const LISTING_PER_PAGE = 24;

function listing_params() {
    $sort = $_GET['sort'] ?? 'featured';
    if (!in_array($sort, ['featured', 'newest', 'price_asc', 'price_desc', 'popular', 'name'], true)) $sort = 'featured';
    return [
        'sort'      => $sort,
        'min'       => ($_GET['min'] ?? '') !== '' ? max(0, (float)$_GET['min']) : null,
        'max'       => ($_GET['max'] ?? '') !== '' ? max(0, (float)$_GET['max']) : null,
        'instock'   => !empty($_GET['instock']),
        'sale'      => !empty($_GET['sale']),
        'custom'    => !empty($_GET['custom']),
        'cat'       => (int)($_GET['cat'] ?? 0),
        'page'      => max(1, (int)($_GET['page'] ?? 1)),
    ];
}

function listing_is_filtered($f) {
    return $f['sort'] !== 'featured' || $f['min'] !== null || $f['max'] !== null || $f['instock'] || $f['sale'] || $f['custom'] || $f['cat'];
}

// $base_where: SQL fragment on alias p (e.g. "p.category_id IN (?,?)"), with its bind types/params.
function listing_query($base_where, $types, $params, $f) {
    $where = ["p.status='active'"];
    if ($base_where) $where[] = $base_where;
    $price = 'COALESCE(p.sale_price, p.base_price)';
    if ($f['min'] !== null) { $where[] = "$price >= ?"; $types .= 'd'; $params[] = $f['min']; }
    if ($f['max'] !== null) { $where[] = "$price <= ?"; $types .= 'd'; $params[] = $f['max']; }
    if ($f['instock']) $where[] = "(p.track_stock=0 OR p.stock_quantity>0 OR EXISTS (SELECT 1 FROM product_variations v WHERE v.product_id=p.id AND v.status='active' AND v.stock_quantity>0))";
    if ($f['sale']) $where[] = "p.sale_price IS NOT NULL AND p.sale_price < p.base_price";
    if ($f['custom']) $where[] = "p.is_customizable=1";
    if ($f['cat']) { $where[] = "(p.category_id=? OR p.category_id IN (SELECT id FROM categories WHERE parent_id=?))"; $types .= 'ii'; $params[] = $f['cat']; $params[] = $f['cat']; }
    $order = [
        'featured'   => 'p.is_featured DESC, p.created_at DESC',
        'newest'     => 'p.created_at DESC',
        'price_asc'  => "$price ASC",
        'price_desc' => "$price DESC",
        'popular'    => 'p.views DESC',
        'name'       => 'p.name ASC',
    ][$f['sort']];
    $sql_where = implode(' AND ', $where);
    $total = (int)(fetch_one("SELECT COUNT(*) c FROM products p WHERE $sql_where", $types, ...$params)['c'] ?? 0);
    $pages = max(1, (int)ceil($total / LISTING_PER_PAGE));
    $page = min($f['page'], $pages);
    $offset = ($page - 1) * LISTING_PER_PAGE;
    $items = fetch_all("SELECT p.* FROM products p WHERE $sql_where ORDER BY $order LIMIT " . LISTING_PER_PAGE . " OFFSET $offset", $types, ...$params);
    return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function listing_url($base, $overrides = []) {
    $q = array_merge($_GET, $overrides);
    unset($q['slug']);
    foreach ($q as $k => $v) if ($v === '' || $v === null || $v === false || ($k === 'page' && (int)$v <= 1) || ($k === 'sort' && $v === 'featured')) unset($q[$k]);
    return $base . ($q ? '?' . http_build_query($q) : '');
}

// Sets the SEO globals the header reads. Call before including site-header.php.
function listing_seo($base_url, $f, $result, $items_name) {
    global $canonical_url, $meta_robots, $pagination_prev, $pagination_next, $page_schema;
    $page = $result['page'];
    $canonical_url = $page > 1 && !listing_is_filtered($f) ? $base_url . '?page=' . $page : $base_url;
    if (listing_is_filtered($f)) $meta_robots = 'noindex, follow';
    if ($page > 1) $pagination_prev = listing_url($base_url, ['page' => $page - 1]);
    if ($page < $result['pages']) $pagination_next = listing_url($base_url, ['page' => $page + 1]);
    if ($result['items']) {
        $page_schema[] = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $items_name,
            'numberOfItems' => $result['total'],
            'itemListElement' => array_map(fn($p, $i) => [
                '@type' => 'ListItem', 'position' => ($page - 1) * LISTING_PER_PAGE + $i + 1,
                'url' => url('product/' . $p['slug']), 'name' => $p['name'],
            ], $result['items'], array_keys($result['items'])),
        ];
    }
}

function render_listing($base_url, $f, $result, $opts = []) {
    $sorts = ['featured' => 'Featured', 'newest' => 'Newest', 'popular' => 'Most popular', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name A–Z'];
    $categories = $opts['categories'] ?? [];
    $cur = setting('currency_symbol', '$');
    ob_start(); ?>
    <div class="flex flex-col lg:flex-row gap-8" id="listing">
      <!-- Filters -->
      <aside class="lg:w-64 shrink-0">
        <form method="GET" action="<?= h($base_url) ?>" class="lg:sticky lg:top-40 space-y-6" id="filterForm">
          <?php if (isset($_GET['q'])): ?><input type="hidden" name="q" value="<?= h($_GET['q']) ?>"><?php endif; ?>
          <input type="hidden" name="sort" value="<?= h($f['sort']) ?>">
          <details class="group lg:open" open>
            <summary class="flex items-center justify-between cursor-pointer list-none font-display font-semibold uppercase tracking-wider text-sm mb-4">Filters <i class="fa-solid fa-sliders text-slate-400"></i></summary>
            <?php if ($categories): ?>
            <fieldset class="mb-6">
              <legend class="label">Category</legend>
              <div class="flex flex-wrap lg:flex-col gap-2">
                <label class="chip cursor-pointer <?= !$f['cat'] ? 'is-active' : '' ?>"><input type="radio" name="cat" value="" class="sr-only" <?= !$f['cat'] ? 'checked' : '' ?> onchange="this.form.submit()">All</label>
                <?php foreach ($categories as $c): ?>
                <label class="chip cursor-pointer <?= $f['cat'] === (int)$c['id'] ? 'is-active' : '' ?>"><input type="radio" name="cat" value="<?= (int)$c['id'] ?>" class="sr-only" <?= $f['cat'] === (int)$c['id'] ? 'checked' : '' ?> onchange="this.form.submit()"><?= h($c['name']) ?></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <?php endif; ?>
            <fieldset class="mb-6">
              <legend class="label">Price (<?= h($cur) ?>)</legend>
              <div class="flex items-center gap-2">
                <input type="number" name="min" min="0" step="1" value="<?= h($f['min'] ?? '') ?>" placeholder="Min" aria-label="Minimum price" class="input !py-2 !px-3">
                <span class="text-slate-400">–</span>
                <input type="number" name="max" min="0" step="1" value="<?= h($f['max'] ?? '') ?>" placeholder="Max" aria-label="Maximum price" class="input !py-2 !px-3">
              </div>
            </fieldset>
            <fieldset class="space-y-3 mb-6">
              <legend class="label">Show only</legend>
              <?php foreach (['instock' => ['In stock', 'fa-box'], 'sale' => ['On sale', 'fa-tag'], 'custom' => ['Customizable', 'fa-cube']] as $k => [$lbl, $icon]): ?>
              <label class="flex items-center gap-3 text-sm font-medium cursor-pointer">
                <input type="checkbox" name="<?= $k ?>" value="1" class="w-4 h-4 rounded accent-ignite" <?= $f[$k] ? 'checked' : '' ?> onchange="this.form.submit()">
                <i class="fa-solid <?= $icon ?> text-slate-400 w-4 text-center"></i><?= $lbl ?>
              </label>
              <?php endforeach; ?>
            </fieldset>
            <div class="flex gap-2">
              <button type="submit" class="btn-dark btn-sm flex-1">Apply</button>
              <?php if (listing_is_filtered($f)): ?><a href="<?= h($base_url . (isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : '')) ?>" class="btn-outline btn-sm">Reset</a><?php endif; ?>
            </div>
          </details>
        </form>
      </aside>

      <!-- Results -->
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6 pb-4 border-b border-black/5">
          <p class="text-sm text-slate-500"><strong class="text-ink"><?= number_format($result['total']) ?></strong> product<?= $result['total'] === 1 ? '' : 's' ?></p>
          <label class="flex items-center gap-2 text-sm">
            <span class="text-slate-500">Sort by</span>
            <select class="input !w-auto !py-2 !pr-8 font-semibold" onchange="location.href=this.value" aria-label="Sort products">
              <?php foreach ($sorts as $k => $lbl): ?><option value="<?= h(listing_url($base_url, ['sort' => $k, 'page' => 1])) ?>" <?= $f['sort'] === $k ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?>
            </select>
          </label>
        </div>

        <?php if ($result['items']): ?>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-5 gap-y-10">
          <?php foreach ($result['items'] as $p) echo render_product_card($p, !empty($p['is_new_arrival']) ? 'new' : (!empty($p['is_coming_soon']) ? 'coming_soon' : null)); ?>
        </div>

        <?php if ($result['pages'] > 1): ?>
        <nav class="flex items-center justify-center gap-2 mt-14" aria-label="Pagination">
          <?php if ($result['page'] > 1): ?><a href="<?= h(listing_url($base_url, ['page' => $result['page'] - 1])) ?>" rel="prev" class="w-11 h-11 rounded-full border border-ink/15 flex items-center justify-center hover:bg-ink hover:text-white transition" aria-label="Previous page"><i class="fa-solid fa-arrow-left"></i></a><?php endif; ?>
          <?php for ($i = 1; $i <= $result['pages']; $i++): if ($i > 2 && $i < $result['pages'] - 1 && abs($i - $result['page']) > 1) { if ($i === 3 || $i === $result['pages'] - 2) echo '<span class="px-1 text-slate-400">…</span>'; continue; } ?>
          <a href="<?= h(listing_url($base_url, ['page' => $i])) ?>" class="w-11 h-11 rounded-full flex items-center justify-center font-semibold text-sm transition <?= $i === $result['page'] ? 'bg-ink text-white' : 'border border-ink/15 hover:border-ink' ?>" <?= $i === $result['page'] ? 'aria-current="page"' : '' ?>><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($result['page'] < $result['pages']): ?><a href="<?= h(listing_url($base_url, ['page' => $result['page'] + 1])) ?>" rel="next" class="w-11 h-11 rounded-full border border-ink/15 flex items-center justify-center hover:bg-ink hover:text-white transition" aria-label="Next page"><i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php else: ?>
        <div class="rounded-3xl border-2 border-dashed border-slate-300 p-12 text-center">
          <i class="fa-solid fa-box-open text-4xl text-slate-300 mb-4"></i>
          <p class="font-display font-semibold uppercase text-lg"><?= h($opts['empty'] ?? 'No products match these filters') ?></p>
          <p class="text-sm text-slate-500 mt-1">Try removing a filter, or <a href="<?= url('contact') ?>" class="text-ignite font-semibold">ask us for a custom quote</a>.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php return ob_get_clean();
}
