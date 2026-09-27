<?php
// admin/pages/blog-edit.php — add/edit a blog post
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$edit = $id ? fetch_one("SELECT * FROM blog_posts WHERE id=?", 'i', $id) : null;
if ($id && !$edit) { set_flash('error', 'Post not found.'); header('Location: ' . ADMIN_URL . '/pages/blog.php'); exit; }

if (($_GET['action'] ?? '') === 'remove_image' && $id) {
    require_csrf_get();
    $current = fetch_one("SELECT featured_image FROM blog_posts WHERE id=?", 'i', $id);
    if ($current && $current['featured_image'] && file_exists(UPLOAD_PATH . $current['featured_image'])) @unlink(UPLOAD_PATH . $current['featured_image']);
    update_record('blog_posts', ['featured_image' => null, 'featured_image_alt' => null], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/blog-edit.php?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $title = sanitize($_POST['title'] ?? '');
    if ($title === '') {
        set_flash('error', 'Title is required.');
        header('Location: ' . ADMIN_URL . '/pages/blog-edit.php' . ($id ? "?id=$id" : ''));
        exit;
    }

    $slug_input = trim($_POST['slug'] ?? '');
    $slug = unique_slug('blog_posts', generate_slug($slug_input !== '' ? $slug_input : $title), $id);

    $new_status = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    $data = [
        'title'              => $title,
        'slug'               => $slug,
        'excerpt'            => sanitize($_POST['excerpt'] ?? ''),
        'content'            => sanitize_html($_POST['content'] ?? ''),
        'featured_image_alt' => sanitize($_POST['featured_image_alt'] ?? ''),
        'author'             => sanitize($_POST['author'] ?? ''),
        'meta_title'         => sanitize($_POST['meta_title'] ?? ''),
        'meta_description'   => sanitize($_POST['meta_description'] ?? ''),
        'status'             => $new_status,
    ];

    if ($new_status === 'published' && empty($edit['published_at'])) {
        $data['published_at'] = date('Y-m-d H:i:s');
    }

    if (!empty($_FILES['featured_image']['name'])) {
        $res = upload_image($_FILES['featured_image'], 'blog', 1200, 630, 'blog', true);
        if (isset($res['error'])) { set_flash('error', $res['error']); header('Location: ' . ADMIN_URL . '/pages/blog-edit.php' . ($id ? "?id=$id" : '')); exit; }
        $data['featured_image'] = $res['filename'];
    }

    if ($id) { update_record('blog_posts', $data, 'id', $id); set_flash('success', 'Post updated.'); }
    else { $id = insert('blog_posts', $data); set_flash('success', 'Post created.'); }

    header('Location: ' . ADMIN_URL . '/pages/blog-edit.php?id=' . $id);
    exit;
}

$page_title = $edit ? 'Edit Post' : 'New Post';
include __DIR__ . '/../includes/admin-header.php';
?>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/vendor/quill/quill.snow.css">
<style>.ql-editor{min-height:320px;font-size:14px}.ql-toolbar.ql-snow{border-color:#cbd5e1;border-radius:8px 8px 0 0}.ql-container.ql-snow{border-color:#cbd5e1;border-radius:0 0 8px 8px}</style>

<a href="blog.php" class="text-sm font-semibold text-slate-400 hover:text-slate-600 inline-block mb-5"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Blog Posts</a>

<form method="POST" enctype="multipart/form-data" id="blogForm">
  <?= csrf_field() ?>
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <div class="flex items-center justify-between mb-5">
    <h1 class="text-lg font-bold text-slate-800"><?= $edit ? 'Edit: ' . h($edit['title']) : 'New Post' ?></h1>
    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Post</button>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
      <div class="card p-6">
        <div class="mb-4">
          <label class="f-label">Title *</label>
          <input type="text" name="title" id="blgTitle" required class="f-input" value="<?= h($edit['title'] ?? '') ?>" placeholder="e.g. 5 Tips for Choosing Boxing Gloves">
        </div>
        <div class="mb-4">
          <label class="f-label">URL Slug</label>
          <div class="flex items-center gap-0 rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-brand/30 focus-within:border-brand">
            <span class="px-3 py-2.5 bg-slate-50 text-slate-400 text-sm border-r border-slate-200 whitespace-nowrap"><?= SITE_URL ?>/blog/</span>
            <input type="text" name="slug" id="blgSlug" class="flex-1 px-3 py-2.5 text-sm outline-none" value="<?= h($edit['slug'] ?? '') ?>">
          </div>
        </div>
        <div class="mb-4">
          <label class="f-label">Excerpt <span class="text-slate-400 font-normal">(shown on the blog listing)</span></label>
          <textarea name="excerpt" rows="2" maxlength="500" class="f-textarea"><?= h($edit['excerpt'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="f-label">Content</label>
          <div id="contentEditor" class="bg-white border border-slate-300 rounded-lg"></div>
          <textarea name="content" id="contentHidden" class="hidden"></textarea>
        </div>
      </div>

      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-1">Featured Image</h2>
        <p class="f-hint mb-4">Auto-cropped to 1200×630 — shown on the listing, the post header, and social shares.</p>
        <?php if (!empty($edit['featured_image'])): ?>
        <div class="relative mb-3 max-w-sm">
          <img src="<?= UPLOAD_URL . h($edit['featured_image']) ?>" class="w-full aspect-[1200/630] object-cover rounded-lg border border-slate-200">
          <a href="?id=<?= (int)$edit['id'] ?>&action=remove_image&csrf_token=<?= csrf_token() ?>" data-confirm="Remove the featured image?" class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-red-600"><i class="fa-solid fa-xmark text-xs"></i></a>
        </div>
        <?php endif; ?>
        <input type="file" name="featured_image" accept="image/*" class="f-input mb-2">
        <input type="text" name="featured_image_alt" class="f-input" placeholder="Alt text (SEO)" value="<?= h($edit['featured_image_alt'] ?? '') ?>">
      </div>

      <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-6">
        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-3 flex items-center gap-1.5"><i class="fa-solid fa-magnifying-glass-chart"></i> SEO Settings</h3>
        <div class="mb-3">
          <div class="flex items-center justify-between"><label class="f-label mb-1">Meta Title</label><span id="metaTitleCount" class="text-xs font-semibold"></span></div>
          <input type="text" name="meta_title" id="metaTitle" class="f-input" maxlength="70" value="<?= h($edit['meta_title'] ?? '') ?>">
        </div>
        <div>
          <div class="flex items-center justify-between"><label class="f-label mb-1">Meta Description</label><span id="metaDescCount" class="text-xs font-semibold"></span></div>
          <textarea name="meta_description" id="metaDesc" rows="2" class="f-textarea" maxlength="160"><?= h($edit['meta_description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="space-y-6">
      <div class="card p-5">
        <h2 class="font-bold text-slate-800 mb-4">Status</h2>
        <select name="status" class="f-select mb-2">
          <option value="draft" <?= (($edit['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= (($edit['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
        </select>
        <p class="f-hint">Only published posts are visible on the site.</p>
      </div>
      <div class="card p-5">
        <h2 class="font-bold text-slate-800 mb-4">Author</h2>
        <input type="text" name="author" class="f-input" value="<?= h($edit['author'] ?? setting('site_name', '')) ?>" placeholder="e.g. BuiltCo Sports Team">
      </div>
    </div>
  </div>
</form>

<script src="<?= SITE_URL ?>/assets/vendor/quill/quill.min.js"></script>
<script>
function blogImageHandler() {
  const input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/*');
  input.click();
  input.onchange = async () => {
    const file = input.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', <?= json_encode(csrf_token()) ?>);
    const range = quillContent.getSelection(true);
    quillContent.insertText(range.index, 'Uploading image…', 'italic', true);
    try {
      const res = await fetch('blog-image-upload.php', { method: 'POST', body: formData });
      const data = await res.json();
      quillContent.deleteText(range.index, 'Uploading image…'.length);
      if (data.url) quillContent.insertEmbed(range.index, 'image', data.url);
      else alert(data.error || 'Upload failed.');
    } catch (e) {
      quillContent.deleteText(range.index, 'Uploading image…'.length);
      alert('Upload failed.');
    }
  };
}

const quillContent = new Quill('#contentEditor', {
  theme: 'snow',
  placeholder: 'Write the post…',
  modules: {
    toolbar: {
      container: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link', 'blockquote', 'image'],
        ['clean'],
      ],
      handlers: { image: blogImageHandler },
    },
  },
});
quillContent.root.innerHTML = <?= json_encode($edit['content'] ?? '') ?>;

(function () {
  const nameEl = document.getElementById('blgTitle'), slugEl = document.getElementById('blgSlug');
  let touched = <?= $edit ? 'true' : 'false' ?>;
  const slugify = s => s.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '');
  nameEl.addEventListener('input', () => { if (!touched) slugEl.value = slugify(nameEl.value); });
  slugEl.addEventListener('input', () => touched = true);
})();

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

document.getElementById('blogForm').addEventListener('submit', function () {
  document.getElementById('contentHidden').value = quillContent.root.innerHTML;
});
</script>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
