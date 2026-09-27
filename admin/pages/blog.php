<?php
// admin/pages/blog.php — blog post list
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Blog Posts';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $p = fetch_one("SELECT featured_image FROM blog_posts WHERE id=?", 'i', $id);
    db()->query("DELETE FROM blog_posts WHERE id=" . $id);
    if ($p && $p['featured_image'] && file_exists(UPLOAD_PATH . $p['featured_image'])) @unlink(UPLOAD_PATH . $p['featured_image']);
    set_flash('success', 'Post deleted.');
    header('Location: ' . ADMIN_URL . '/pages/blog.php'); exit;
}
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $p = fetch_one("SELECT status FROM blog_posts WHERE id=?", 'i', $id);
    if ($p) {
        $new_status = $p['status'] === 'published' ? 'draft' : 'published';
        $data = ['status' => $new_status];
        if ($new_status === 'published') {
            $already = fetch_one("SELECT published_at FROM blog_posts WHERE id=?", 'i', $id);
            if (empty($already['published_at'])) $data['published_at'] = date('Y-m-d H:i:s');
        }
        update_record('blog_posts', $data, 'id', $id);
    }
    header('Location: ' . ADMIN_URL . '/pages/blog.php'); exit;
}

$posts = fetch_all("SELECT * FROM blog_posts ORDER BY created_at DESC");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center justify-between mb-5">
  <p class="text-slate-500 text-sm"><?= count($posts) ?> post<?= count($posts) !== 1 ? 's' : '' ?></p>
  <a href="blog-edit.php" class="btn-primary"><i class="fa-solid fa-plus"></i> New Post</a>
</div>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Post</th>
          <th class="px-5 py-3 font-semibold">Author</th>
          <th class="px-5 py-3 font-semibold">Views</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold">Date</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($posts as $p): ?>
        <tr class="hover:bg-slate-50/60">
          <td class="px-5 py-3">
            <div class="flex items-center gap-3">
              <?php if ($p['featured_image']): ?>
              <img src="<?= UPLOAD_URL . h($p['featured_image']) ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200">
              <?php else: ?>
              <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-300 flex items-center justify-center"><i class="fa-solid fa-image"></i></div>
              <?php endif; ?>
              <div>
                <div class="font-semibold text-slate-800 text-[13px]"><?= h($p['title']) ?></div>
                <div class="text-[11px] text-slate-400">/blog/<?= h($p['slug']) ?></div>
              </div>
            </div>
          </td>
          <td class="px-5 py-3 text-slate-500"><?= h($p['author'] ?: '—') ?></td>
          <td class="px-5 py-3 text-slate-500"><i class="fa-solid fa-eye text-slate-300 mr-1 text-xs"></i><?= (int)$p['views'] ?></td>
          <td class="px-5 py-3"><a href="?action=toggle&id=<?= (int)$p['id'] ?>&csrf_token=<?= csrf_token() ?>" class="<?= $p['status'] === 'published' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($p['status']) ?></a></td>
          <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap"><?= $p['published_at'] ? format_date($p['published_at']) : format_date($p['created_at']) ?></td>
          <td class="px-5 py-3 text-right">
            <div class="inline-flex items-center gap-2">
              <a href="blog-edit.php?id=<?= (int)$p['id'] ?>" class="btn-outline btn-sm">Edit</a>
              <?php if ($p['status'] === 'published'): ?><a href="<?= SITE_URL ?>/blog/<?= h($p['slug']) ?>" target="_blank" class="btn-outline btn-sm">View</a><?php endif; ?>
              <a href="?action=delete&id=<?= (int)$p['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this post?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($posts)): ?>
        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-400">No blog posts yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
