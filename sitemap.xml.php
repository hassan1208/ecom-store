<?php
// sitemap.xml.php — served as /sitemap.xml (a sitemap index) and
// /sitemap-{pages|categories|products|blog}.xml via .htaccess rewrites.
// Product and category sitemaps include <image:image> entries so Google
// Images can discover every product photo.
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$type = $_GET['type'] ?? '';
$lastmod = fn($d) => date('c', strtotime($d ?: 'now'));
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

if ($type === '') {
    $parts = [
        'pages'      => fetch_one("SELECT MAX(updated_at) m FROM pages WHERE status='published'")['m'] ?? null,
        'categories' => fetch_one("SELECT MAX(updated_at) m FROM categories WHERE status='active'")['m'] ?? null,
        'products'   => fetch_one("SELECT MAX(updated_at) m FROM products WHERE status='active'")['m'] ?? null,
        'blog'       => fetch_one("SELECT MAX(updated_at) m FROM blog_posts WHERE status='published'")['m'] ?? null,
    ];
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($parts as $name => $m) {
        echo "  <sitemap><loc>" . h(url("sitemap-$name.xml")) . "</loc><lastmod>" . $lastmod($m) . "</lastmod></sitemap>\n";
    }
    echo "</sitemapindex>\n";
    exit;
}

$urls = [];
switch ($type) {
    case 'pages':
        $urls[] = ['loc' => url(''), 'priority' => '1.0', 'changefreq' => 'daily'];
        $urls[] = ['loc' => url('shop'), 'priority' => '0.9', 'changefreq' => 'daily'];
        $urls[] = ['loc' => url('kit-builder'), 'priority' => '0.7', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => url('contact'), 'priority' => '0.5', 'changefreq' => 'monthly'];
        foreach (fetch_all("SELECT slug, updated_at FROM pages WHERE status='published'") as $pg) {
            $urls[] = ['loc' => url($pg['slug']), 'priority' => '0.4', 'changefreq' => 'monthly', 'lastmod' => $pg['updated_at']];
        }
        break;
    case 'categories':
        foreach (fetch_all("SELECT slug, name, image, image_alt, updated_at FROM categories WHERE status='active'") as $c) {
            $urls[] = ['loc' => url('category/' . $c['slug']), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $c['updated_at'],
                       'images' => $c['image'] ? [[UPLOAD_URL . $c['image'], $c['image_alt'] ?: $c['name']]] : []];
        }
        break;
    case 'products':
        $imgs = [];
        foreach (fetch_all("SELECT pi.product_id, pi.image_path, pi.alt_text FROM product_images pi JOIN products p ON p.id=pi.product_id WHERE p.status='active' ORDER BY pi.is_primary DESC, pi.sort_order ASC") as $i) {
            $imgs[$i['product_id']][] = [UPLOAD_URL . $i['image_path'], $i['alt_text']];
        }
        foreach (fetch_all("SELECT id, slug, name, canonical_url, updated_at FROM products WHERE status='active'") as $p) {
            // Skip products whose canonical points elsewhere — only canonical URLs belong in a sitemap.
            if ($p['canonical_url'] && rtrim($p['canonical_url'], '/') !== rtrim(url('product/' . $p['slug']), '/')) continue;
            $urls[] = ['loc' => url('product/' . $p['slug']), 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => $p['updated_at'],
                       'images' => array_map(fn($i) => [$i[0], $i[1] ?: $p['name']], $imgs[$p['id']] ?? [])];
        }
        break;
    case 'blog':
        $posts = fetch_all("SELECT slug, title, featured_image, updated_at FROM blog_posts WHERE status='published'");
        if ($posts) $urls[] = ['loc' => url('blog'), 'priority' => '0.6', 'changefreq' => 'weekly'];
        foreach ($posts as $bp) {
            $urls[] = ['loc' => url('blog/' . $bp['slug']), 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $bp['updated_at'],
                       'images' => $bp['featured_image'] ? [[UPLOAD_URL . $bp['featured_image'], $bp['title']]] : []];
        }
        break;
    default:
        http_response_code(404);
}
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= h($u['loc']) ?></loc>
<?php if (!empty($u['lastmod'])): ?>    <lastmod><?= $lastmod($u['lastmod']) ?></lastmod>
<?php endif; ?>
    <changefreq><?= $u['changefreq'] ?></changefreq>
    <priority><?= $u['priority'] ?></priority>
<?php foreach ($u['images'] ?? [] as [$src, $title]): ?>
    <image:image><image:loc><?= h($src) ?></image:loc><image:title><?= h($title) ?></image:title></image:image>
<?php endforeach; ?>
  </url>
<?php endforeach; ?>
</urlset>
