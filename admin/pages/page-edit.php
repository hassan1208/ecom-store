<?php
// admin/pages/page-edit.php — add/edit a static content page
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$edit = $id ? fetch_one("SELECT * FROM pages WHERE id=?", 'i', $id) : null;
if ($id && !$edit) { set_flash('error', 'Page not found.'); header('Location: ' . ADMIN_URL . '/pages/pages.php'); exit; }

$reserved_slugs = ['contact', 'cart', 'checkout', 'order-success', 'category', 'product', 'admin', 'includes', 'database', 'logs', 'assets', 'robots.txt', 'sitemap.xml', 'llms.txt', 'index', ''];

if (($_GET['action'] ?? '') === 'remove_image' && $id && in_array($_GET['field'] ?? '', ['banner_image', 'side_image'])) {
    require_csrf_get();
    $field = $_GET['field'];
    $current = fetch_one("SELECT `$field` AS img FROM pages WHERE id=?", 'i', $id);
    if ($current && $current['img'] && file_exists(UPLOAD_PATH . $current['img'])) @unlink(UPLOAD_PATH . $current['img']);
    update_record('pages', [$field => null, $field . '_alt' => null], 'id', $id);
    header('Location: ' . ADMIN_URL . '/pages/page-edit.php?id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $title = sanitize($_POST['title'] ?? '');
    if ($title === '') {
        set_flash('error', 'Title is required.');
        header('Location: ' . ADMIN_URL . '/pages/page-edit.php' . ($id ? "?id=$id" : ''));
        exit;
    }

    $slug_input = trim($_POST['slug'] ?? '');
    $slug = generate_slug($slug_input !== '' ? $slug_input : $title);
    if (in_array($slug, $reserved_slugs, true)) {
        set_flash('error', "\"$slug\" is a reserved URL and can't be used for a page. Please choose a different slug.");
        header('Location: ' . ADMIN_URL . '/pages/page-edit.php' . ($id ? "?id=$id" : ''));
        exit;
    }
    $slug = unique_slug('pages', $slug, $id);

    $data = [
        'title'             => $title,
        'slug'              => $slug,
        'content'           => sanitize_html($_POST['content'] ?? ''),
        'banner_image_alt'  => sanitize($_POST['banner_image_alt'] ?? ''),
        'side_image_alt'    => sanitize($_POST['side_image_alt'] ?? ''),
        'meta_title'        => sanitize($_POST['meta_title'] ?? ''),
        'meta_description'  => sanitize($_POST['meta_description'] ?? ''),
        'status'            => ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft',
    ];

    // Banner: wide crop-to-fill (no letterboxing) for a clean full-width header.
    if (!empty($_FILES['banner_image']['name'])) {
        $res = upload_image($_FILES['banner_image'], 'pages', 1920, 600, 'page-banner', true);
        if (isset($res['error'])) { set_flash('error', $res['error']); header('Location: ' . ADMIN_URL . '/pages/page-edit.php' . ($id ? "?id=$id" : '')); exit; }
        $data['banner_image'] = $res['filename'];
    }
    // Side image: portrait crop-to-fill for the two-column layout.
    if (!empty($_FILES['side_image']['name'])) {
        $res = upload_image($_FILES['side_image'], 'pages', 800, 1000, 'page-side', true);
        if (isset($res['error'])) { set_flash('error', $res['error']); header('Location: ' . ADMIN_URL . '/pages/page-edit.php' . ($id ? "?id=$id" : '')); exit; }
        $data['side_image'] = $res['filename'];
    }

    if ($id) { update_record('pages', $data, 'id', $id); set_flash('success', 'Page updated.'); }
    else { $id = insert('pages', $data); set_flash('success', 'Page created.'); }

    header('Location: ' . ADMIN_URL . '/pages/page-edit.php?id=' . $id);
    exit;
}

$page_title = $edit ? 'Edit Page' : 'New Page';
include __DIR__ . '/../includes/admin-header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<style>.ql-editor{min-height:300px;font-size:14px}.ql-toolbar.ql-snow{border-color:#cbd5e1;border-radius:8px 8px 0 0}.ql-container.ql-snow{border-color:#cbd5e1;border-radius:0 0 8px 8px}</style>

<a href="pages.php" class="text-sm font-semibold text-slate-400 hover:text-slate-600 inline-block mb-5"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Pages</a>

<form method="POST" enctype="multipart/form-data" id="pageForm">
  <?= csrf_field() ?>
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <div class="flex items-center justify-between mb-5">
    <h1 class="text-lg font-bold text-slate-800"><?= $edit ? 'Edit: ' . h($edit['title']) : 'New Page' ?></h1>
    <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Page</button>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
      <div class="card p-6">
        <div class="mb-4">
          <label class="f-label">Title *</label>
          <input type="text" name="title" id="pgTitle" required class="f-input" value="<?= h($edit['title'] ?? '') ?>" placeholder="e.g. Privacy Policy">
        </div>
        <div class="mb-4">
          <label class="f-label">URL Slug</label>
          <div class="flex items-center gap-0 rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-brand/30 focus-within:border-brand">
            <span class="px-3 py-2.5 bg-slate-50 text-slate-400 text-sm border-r border-slate-200 whitespace-nowrap"><?= SITE_URL ?>/</span>
            <input type="text" name="slug" id="pgSlug" class="flex-1 px-3 py-2.5 text-sm outline-none" value="<?= h($edit['slug'] ?? '') ?>">
          </div>
        </div>
        <div>
          <label class="f-label">Content</label>
          <div id="contentEditor" class="bg-white border border-slate-300 rounded-lg"></div>
          <textarea name="content" id="contentHidden" class="hidden"></textarea>
        </div>
      </div>

      <div class="card p-6">
        <h2 class="font-bold text-slate-800 mb-1">Images</h2>
        <p class="f-hint mb-4">Both are auto-cropped to fill their exact shape — no stretching or empty space, whatever photo you upload.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="f-label">Banner Image <span class="text-slate-400 font-normal">(page header)</span></label>
            <?php if (!empty($edit['banner_image'])): ?>
            <div class="relative mb-2">
              <img src="<?= UPLOAD_URL . h($edit['banner_image']) ?>" class="w-full h-24 object-cover rounded-lg border border-slate-200">
              <a href="?id=<?= (int)$edit['id'] ?>&action=remove_image&field=banner_image&csrf_token=<?= csrf_token() ?>" data-confirm="Remove the banner image?" class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-red-600"><i class="fa-solid fa-xmark text-xs"></i></a>
            </div>
            <?php endif; ?>
            <input type="file" name="banner_image" accept="image/*" class="f-input mb-2">
            <input type="text" name="banner_image_alt" class="f-input" placeholder="Alt text (SEO)" value="<?= h($edit['banner_image_alt'] ?? '') ?>">
            <p class="f-hint">Cropped to 1920×600 (wide).</p>
          </div>

          <div>
            <label class="f-label">Side Image <span class="text-slate-400 font-normal">(next to content)</span></label>
            <?php if (!empty($edit['side_image'])): ?>
            <div class="relative mb-2">
              <img src="<?= UPLOAD_URL . h($edit['side_image']) ?>" class="w-full h-24 object-cover rounded-lg border border-slate-200">
              <a href="?id=<?= (int)$edit['id'] ?>&action=remove_image&field=side_image&csrf_token=<?= csrf_token() ?>" data-confirm="Remove the side image?" class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-red-600"><i class="fa-solid fa-xmark text-xs"></i></a>
            </div>
            <?php endif; ?>
            <input type="file" name="side_image" accept="image/*" class="f-input mb-2">
            <input type="text" name="side_image_alt" class="f-input" placeholder="Alt text (SEO)" value="<?= h($edit['side_image_alt'] ?? '') ?>">
            <p class="f-hint">Cropped to 800×1000 (portrait).</p>
          </div>
        </div>
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

    <div>
      <div class="card p-5">
        <h2 class="font-bold text-slate-800 mb-4">Status</h2>
        <select name="status" class="f-select">
          <option value="draft" <?= (($edit['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= (($edit['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
        </select>
        <p class="f-hint mt-2">Only published pages are visible on the site.</p>
      </div>
    </div>
  </div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
const quillContent = new Quill('#contentEditor', {
  theme: 'snow',
  placeholder: 'Write the page content…',
  modules: {
    toolbar: [
      [{ header: [2, 3, false] }],
      ['bold', 'italic', 'underline'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['link', 'blockquote'],
      ['clean'],
    ],
  },
});
quillContent.root.innerHTML = <?= json_encode($edit['content'] ?? '') ?>;

(function () {
  const nameEl = document.getElementById('pgTitle'), slugEl = document.getElementById('pgSlug');
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

document.getElementById('pageForm').addEventListener('submit', function () {
  document.getElementById('contentHidden').value = quillContent.root.innerHTML;
});
</script>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
