<?php
// blog-feed.php — RSS 2.0 feed of published blog posts, served as /blog/feed
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/rss+xml; charset=utf-8');
$posts = fetch_all("SELECT * FROM blog_posts WHERE status='published' ORDER BY COALESCE(published_at, created_at) DESC LIMIT 30");
$site = setting('site_name', 'BuiltCo Sports');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
  <title><?= h($site) ?> Blog</title>
  <link><?= h(url('blog')) ?></link>
  <atom:link href="<?= h(url('blog/feed')) ?>" rel="self" type="application/rss+xml"/>
  <description><?= h(setting('site_tagline', '')) ?></description>
  <language>en</language>
  <?php if ($posts): ?><lastBuildDate><?= date(DATE_RSS, strtotime($posts[0]['updated_at'])) ?></lastBuildDate><?php endif; ?>
<?php foreach ($posts as $p): ?>
  <item>
    <title><?= h($p['title']) ?></title>
    <link><?= h(url('blog/' . $p['slug'])) ?></link>
    <guid isPermaLink="true"><?= h(url('blog/' . $p['slug'])) ?></guid>
    <pubDate><?= date(DATE_RSS, strtotime($p['published_at'] ?: $p['created_at'])) ?></pubDate>
    <description><?= h($p['excerpt'] ?: meta_text($p['content'], 300)) ?></description>
    <?php if ($p['featured_image']): ?><media:content url="<?= h(UPLOAD_URL . $p['featured_image']) ?>" medium="image"/><?php endif; ?>
  </item>
<?php endforeach; ?>
</channel>
</rss>
