<?php
// sitemap.xml.php — served as /sitemap.xml via .htaccess rewrite
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/xml; charset=utf-8');

$urls = [];
$urls[] = ['loc' => url(''), 'priority' => '1.0', 'changefreq' => 'daily'];
$urls[] = ['loc' => url('contact'), 'priority' => '0.5', 'changefreq' => 'monthly'];

$categories = fetch_all("SELECT slug, updated_at FROM categories WHERE status='active'");
foreach ($categories as $cat) {
    $urls[] = [
        'loc'        => url('category/' . $cat['slug']),
        'priority'   => '0.8',
        'changefreq' => 'weekly',
        'lastmod'    => date('Y-m-d', strtotime($cat['updated_at'] ?? 'now')),
    ];
}

$products = fetch_all("SELECT slug, updated_at FROM products WHERE status='active'");
foreach ($products as $prod) {
    $urls[] = [
        'loc'        => url('product/' . $prod['slug']),
        'priority'   => '0.9',
        'changefreq' => 'weekly',
        'lastmod'    => date('Y-m-d', strtotime($prod['updated_at'] ?? 'now')),
    ];
}

$pages = fetch_all("SELECT slug, updated_at FROM pages WHERE status='published'");
foreach ($pages as $pg) {
    $urls[] = [
        'loc'        => url($pg['slug']),
        'priority'   => '0.4',
        'changefreq' => 'monthly',
        'lastmod'    => date('Y-m-d', strtotime($pg['updated_at'] ?? 'now')),
    ];
}

$blog_posts = fetch_all("SELECT slug, updated_at FROM blog_posts WHERE status='published'");
if ($blog_posts) $urls[] = ['loc' => url('blog'), 'priority' => '0.6', 'changefreq' => 'weekly'];
foreach ($blog_posts as $bp) {
    $urls[] = [
        'loc'        => url('blog/' . $bp['slug']),
        'priority'   => '0.6',
        'changefreq' => 'monthly',
        'lastmod'    => date('Y-m-d', strtotime($bp['updated_at'] ?? 'now')),
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= h($u['loc']) ?></loc>
    <?php if (!empty($u['lastmod'])): ?><lastmod><?= $u['lastmod'] ?></lastmod><?php endif; ?>
    <changefreq><?= $u['changefreq'] ?></changefreq>
    <priority><?= $u['priority'] ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
