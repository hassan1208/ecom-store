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

echo "\n## Notes\n";
echo "- This is an e-commerce store. Product data is not yet available via this file.\n";
echo "- Sitemap: " . url('sitemap.xml') . "\n";
