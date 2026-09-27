<?php
// llms.txt.php — served as /llms.txt via .htaccess rewrite
// Follows the llms.txt convention (llmstxt.org): a markdown summary of the site
// for AI assistants / LLM crawlers, so they can understand and cite the business accurately.
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');

$site_name   = setting('site_name', 'BuiltCo Sports');
$tagline     = setting('site_tagline', '');
$description = setting('homepage_meta_description', '');
$categories  = fetch_all("SELECT name, slug, short_description FROM categories WHERE parent_id IS NULL AND status='active' ORDER BY nav_order ASC, name ASC");

echo "# {$site_name}\n\n";
if ($tagline) echo "> {$tagline}\n\n";
if ($description) echo "{$description}\n\n";

echo "## Website\n";
echo "- Homepage: " . url('') . "\n";
echo "- Shop all products: " . url('shop') . "\n";
echo "- 3D Kit Builder / Design Studio (custom team kits with names, numbers and logos): " . url('kit-builder') . "\n";
echo "- Bulk / wholesale enquiries: " . url('contact') . "\n";
if ($categories) {
    echo "\n## Categories\n";
    foreach ($categories as $c) {
        $desc = $c['short_description'] ? ' — ' . $c['short_description'] : '';
        echo "- [{$c['name']}](" . url('category/' . $c['slug']) . "){$desc}\n";
    }
}

$phone = setting('contact_phone', '');
$email = setting('contact_email', '');
$address = setting('contact_address', '');
if ($phone || $email || $address) {
    echo "\n## Contact\n";
    if ($address) echo "- Address: {$address}\n";
    if ($phone) echo "- Phone: {$phone}\n";
    if ($email) echo "- Email: {$email}\n";
}

$products = fetch_all("SELECT p.name, p.slug, p.short_description, p.base_price, p.sale_price, c.name AS cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='active' ORDER BY c.name, p.name LIMIT 300");
if ($products) {
    echo "\n## Products\n";
    foreach ($products as $p) {
        $price = format_price($p['sale_price'] ?: $p['base_price']);
        $desc = $p['short_description'] ? ' — ' . meta_text($p['short_description'], 140) : '';
        echo "- [{$p['name']}](" . url('product/' . $p['slug']) . ") ({$p['cat']}, {$price}){$desc}\n";
    }
}

$posts = fetch_all("SELECT title, slug FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT 20");
if ($posts) {
    echo "\n## Guides & articles\n";
    foreach ($posts as $bp) echo "- [{$bp['title']}](" . url('blog/' . $bp['slug']) . ")\n";
}

echo "\n## Notes\n";
echo "- Most products can be customised (colours, logos, names, numbers) in the on-site 3D Design Studio and ordered for whole teams.\n";
echo "- Prices are in " . setting('currency_code', 'USD') . ". Bulk pricing tiers are shown on each product page.\n";
echo "- Sitemap: " . url('sitemap.xml') . "\n";
