<?php
// admin/pages/categories.php
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Categories';

// --- Toggle active/inactive -------------------------------------------------
if (($_GET['action'] ?? '') === 'toggle' && isset($_GET['id'])) {
    require_csrf_get();
    $id = (int)$_GET['id'];
    $cat = fetch_one("SELECT status FROM categories WHERE id=?", 'i', $id);
    if ($cat) {
        $new_status = $cat['status'] === 'active' ? 'inactive' : 'active';
        update_record('categories', ['status' => $new_status], 'id', $id);
        set_flash('success', 'Category ' . ($new_status === 'active' ? 'activated' : 'deactivated') . '.');
    }
    header('Location: ' . ADMIN_URL . '/pages/categories.php');
    exit;
}

// --- Create / Update ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $cid  = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');

    if ($name === '') {
        set_flash('error', 'Category name is required.');
        header('Location: ' . ADMIN_URL . '/pages/categories.php' . ($cid ? "?edit=$cid" : ''));
        exit;
    }

    $slug_input = trim($_POST['slug'] ?? '');
    $slug = unique_slug('categories', generate_slug($slug_input !== '' ? $slug_input : $name), $cid);

    $data = [
        'name'              => $name,
        'slug'              => $slug,
        'parent_id'         => (int)($_POST['parent_id'] ?? 0) ?: null,
        'short_description' => sanitize($_POST['short_description'] ?? ''),
        'description'       => sanitize($_POST['description'] ?? ''),
        'faq'               => sanitize($_POST['faq'] ?? '') ?: null,
        'image_alt'         => sanitize($_POST['image_alt'] ?? ''),
        'meta_title'        => sanitize($_POST['meta_title'] ?? ''),
        'meta_description'  => sanitize($_POST['meta_description'] ?? ''),
        'focus_keyword'     => sanitize($_POST['focus_keyword'] ?? ''),
        'canonical_url'     => sanitize($_POST['canonical_url'] ?? ''),
        'show_in_nav'       => isset($_POST['show_in_nav']) ? 1 : 0,
        'nav_order'         => (int)($_POST['nav_order'] ?? 0),
        'status'            => isset($_POST['status']) ? 'active' : 'inactive',
    ];

    if (!empty($_FILES['image']['name'])) {
        $res = upload_image($_FILES['image'], 'categories', 800, 800, 'cat');
        if (isset($res['error'])) {
            set_flash('error', $res['error']);
            header('Location: ' . ADMIN_URL . '/pages/categories.php' . ($cid ? "?edit=$cid" : ''));
            exit;
        }
        $data['image'] = $res['filename'];
    }

    if ($cid) {
        update_record('categories', $data, 'id', $cid);
        set_flash('success', 'Category updated successfully.');
    } else {
        insert('categories', $data);
        set_flash('success', 'Category created successfully.');
    }
    header('Location: ' . ADMIN_URL . '/pages/categories.php');
    exit;
}

// --- Data for the view --------------------------------------------------------
$edit = isset($_GET['edit']) ? fetch_one("SELECT * FROM categories WHERE id=?", 'i', (int)$_GET['edit']) : null;

$categories = fetch_all(
    "SELECT c.*, p.name AS parent_name,
            (SELECT COUNT(*) FROM categories cc WHERE cc.parent_id = c.id) AS child_count
     FROM categories c
     LEFT JOIN categories p ON p.id = c.parent_id
     ORDER BY c.parent_id IS NOT NULL, c.parent_id ASC, c.nav_order ASC, c.name ASC"
);

$top_level_parents = fetch_all("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC");

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

  <!-- Form -->
  <div class="lg:col-span-2">
    <div class="card p-6 sticky top-20">
      <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-slate-800"><?= $edit ? 'Edit Category' : 'Add New Category' ?></h2>
        <?php if ($edit): ?>
          <a href="<?= ADMIN_URL ?>/pages/categories.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600">
            <i class="fa-solid fa-xmark"></i> Cancel edit
          </a>
        <?php endif; ?>
      </div>

      <form method="POST" enctype="multipart/form-data" id="catForm">
        <?= csrf_field() ?>
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="f-label">Category Name *</label>
          <input type="text" name="name" id="catName" required class="f-input"
                 value="<?= h($edit['name'] ?? '') ?>" placeholder="e.g. Boxing Gloves">
        </div>

        <div class="mb-4">
          <label class="f-label">URL Slug</label>
          <div class="flex items-center gap-0 rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-brand/30 focus-within:border-brand">
            <span class="px-3 py-2.5 bg-slate-50 text-slate-400 text-sm border-r border-slate-200 whitespace-nowrap">/category/</span>
            <input type="text" name="slug" id="catSlug" class="flex-1 px-3 py-2.5 text-sm outline-none"
                   value="<?= h($edit['slug'] ?? '') ?>" placeholder="auto-generated-from-name">
          </div>
          <p class="f-hint">Used in the page URL. Keep it short and keyword-rich for SEO.</p>
        </div>

        <div class="mb-4">
          <label class="f-label">Parent Category</label>
          <select name="parent_id" class="f-select">
            <option value="">— Top Level —</option>
            <?php foreach ($top_level_parents as $p): if ($edit && $p['id'] == $edit['id']) continue; ?>
            <option value="<?= (int)$p['id'] ?>" <?= (($edit['parent_id'] ?? 0) == $p['id']) ? 'selected' : '' ?>><?= h($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-4">
          <label class="f-label">Short Description</label>
          <input type="text" name="short_description" class="f-input" maxlength="500"
                 value="<?= h($edit['short_description'] ?? '') ?>" placeholder="One line shown under the category title">
        </div>

        <div class="mb-4">
          <label class="f-label">Category Content</label>
          <textarea name="description" rows="3" class="f-textarea" placeholder="Longer SEO-friendly content shown at the bottom of the category page..."><?= h($edit['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-4">
          <label class="f-label">FAQs <span class="text-slate-400 font-normal">(one per line: Question? | Answer)</span></label>
          <textarea name="faq" rows="4" class="f-textarea font-mono text-xs" placeholder="Do you offer bulk pricing? | Yes — tiered pricing starts at 10 units.&#10;Can I add my club logo? | Yes, use the 3D Design Studio on any product."><?= h($edit['faq'] ?? '') ?></textarea>
          <p class="f-hint">Shown on the category page and added as FAQPage structured data (eligible for FAQ rich results).</p>
        </div>

        <div class="mb-5 grid grid-cols-2 gap-3">
          <div>
            <div class="flex items-center gap-2 pt-6">
              <input type="checkbox" name="show_in_nav" id="showNav" value="1" class="w-4 h-4 rounded accent-brand"
                     <?= (!$edit || $edit['show_in_nav']) ? 'checked' : '' ?>>
              <label for="showNav" class="text-sm font-medium text-slate-700">Show in menu</label>
            </div>
          </div>
          <div>
            <label class="f-label">Menu Order</label>
            <input type="number" name="nav_order" class="f-input" value="<?= (int)($edit['nav_order'] ?? 0) ?>" min="0">
          </div>
        </div>

        <div class="mb-5">
          <label class="f-label">Category Image</label>
          <?php if (!empty($edit['image'])): ?>
            <img src="<?= UPLOAD_URL . h($edit['image']) ?>" class="w-24 h-24 object-cover rounded-lg border border-slate-200 mb-2" alt="">
          <?php endif; ?>
          <input type="file" name="image" accept="image/*" class="f-input">
          <p class="f-hint">Recommended: square image, at least 800×800px.</p>
        </div>

        <div class="mb-6">
          <label class="f-label">Image Alt Text <span class="text-slate-400 font-normal">(SEO)</span></label>
          <input type="text" name="image_alt" class="f-input" maxlength="255"
                 value="<?= h($edit['image_alt'] ?? '') ?>" placeholder="Describe the image for search engines & accessibility">
        </div>

        <!-- SEO block -->
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 mb-6">
          <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-3 flex items-center gap-1.5">
            <i class="fa-solid fa-magnifying-glass-chart"></i> SEO Settings
          </h3>

          <div class="mb-3">
            <div class="flex items-center justify-between">
              <label class="f-label mb-1">Meta Title</label>
              <span id="metaTitleCount" class="text-xs font-semibold"></span>
            </div>
            <input type="text" name="meta_title" id="metaTitle" class="f-input" maxlength="70"
                   value="<?= h($edit['meta_title'] ?? '') ?>" placeholder="Defaults to category name if left blank">
          </div>

          <div class="mb-3">
            <div class="flex items-center justify-between">
              <label class="f-label mb-1">Meta Description</label>
              <span id="metaDescCount" class="text-xs font-semibold"></span>
            </div>
            <textarea name="meta_description" id="metaDesc" rows="2" class="f-textarea" maxlength="160"
                      placeholder="A compelling 150-160 character summary shown in Google results"><?= h($edit['meta_description'] ?? '') ?></textarea>
          </div>

          <div class="mb-3">
            <label class="f-label">Focus Keyword</label>
            <input type="text" name="focus_keyword" class="f-input"
                   value="<?= h($edit['focus_keyword'] ?? '') ?>" placeholder="e.g. custom boxing gloves">
          </div>

          <!-- Google preview -->
          <div class="rounded-lg bg-white border border-slate-200 p-3 mt-3">
            <p class="text-[11px] text-slate-400 mb-1 font-medium">Search Preview</p>
            <div id="serpUrl" class="text-[13px] text-emerald-700 truncate">builtcosports.com › category › <span id="serpSlug">category-slug</span></div>
            <div id="serpTitle" class="text-[#1a0dab] text-base leading-snug truncate">Category Title</div>
            <div id="serpDesc" class="text-[13px] text-slate-600 leading-snug line-clamp-2">Meta description preview appears here as you type…</div>
          </div>

          <div class="mt-3">
            <label class="f-label">Canonical URL <span class="text-slate-400 font-normal">(optional override)</span></label>
            <input type="text" name="canonical_url" class="f-input"
                   value="<?= h($edit['canonical_url'] ?? '') ?>" placeholder="Leave blank to use the default category URL">
          </div>
        </div>

        <div class="flex items-center gap-2 mb-6">
          <input type="checkbox" name="status" id="catStatus" value="1" class="w-4 h-4 rounded accent-brand"
                 <?= (!$edit || $edit['status'] === 'active') ? 'checked' : '' ?>>
          <label for="catStatus" class="text-sm font-medium text-slate-700">Active (visible on the storefront)</label>
        </div>

        <button type="submit" class="btn-primary w-full">
          <i class="fa-solid <?= $edit ? 'fa-floppy-disk' : 'fa-plus' ?>"></i>
          <?= $edit ? 'Update Category' : 'Add Category' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- List -->
  <div class="lg:col-span-3">
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-bold text-slate-800">All Categories <span class="text-slate-400 font-normal">(<?= count($categories) ?>)</span></h2>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
              <th class="px-5 py-3 font-semibold">Category</th>
              <th class="px-5 py-3 font-semibold">Menu</th>
              <th class="px-5 py-3 font-semibold">SEO</th>
              <th class="px-5 py-3 font-semibold">Status</th>
              <th class="px-5 py-3 font-semibold text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($categories as $cat): ?>
            <?php
              $seo_ok = !empty($cat['meta_title']) && !empty($cat['meta_description']);
            ?>
            <tr class="hover:bg-slate-50/60">
              <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                  <?php if (!empty($cat['image'])): ?>
                    <img src="<?= UPLOAD_URL . h($cat['image']) ?>" class="w-9 h-9 rounded-lg object-cover border border-slate-200" alt="<?= h($cat['image_alt'] ?? '') ?>">
                  <?php else: ?>
                    <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-300 flex items-center justify-center"><i class="fa-solid fa-image"></i></div>
                  <?php endif; ?>
                  <div>
                    <div class="font-semibold text-slate-800 text-[13px]">
                      <?= $cat['parent_id'] ? '<span class="text-slate-300">↳</span> ' : '' ?><?= h($cat['name']) ?>
                    </div>
                    <div class="text-[11px] text-slate-400">/<?= h($cat['slug']) ?><?= $cat['parent_name'] ? ' · under ' . h($cat['parent_name']) : '' ?></div>
                  </div>
                </div>
              </td>
              <td class="px-5 py-3 text-slate-500">
                <?= $cat['show_in_nav'] ? '<i class="fa-solid fa-check text-emerald-500"></i>' : '<i class="fa-solid fa-minus text-slate-300"></i>' ?>
              </td>
              <td class="px-5 py-3">
                <?php if ($seo_ok): ?>
                  <span class="badge-active"><i class="fa-solid fa-circle-check mr-1"></i>Optimized</span>
                <?php else: ?>
                  <span class="badge-inactive text-amber-600 bg-amber-50"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Incomplete</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3">
                <span class="<?= $cat['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($cat['status']) ?></span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-end gap-2">
                  <a href="?edit=<?= (int)$cat['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                  <a href="?action=toggle&id=<?= (int)$cat['id'] ?>&csrf_token=<?= csrf_token() ?>"
                     data-confirm="<?= $cat['status'] === 'active' ? 'Deactivate' : 'Activate' ?> this category?"
                     class="btn-danger-outline btn-sm">
                    <i class="fa-solid <?= $cat['status'] === 'active' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                    <?= $cat['status'] === 'active' ? 'Disable' : 'Enable' ?>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No categories yet. Add your first one using the form.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const nameEl = document.getElementById('catName');
  const slugEl = document.getElementById('catSlug');
  let slugTouched = <?= $edit ? 'true' : 'false' ?>;

  const slugify = (s) => s.toLowerCase().trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/[\s-]+/g, '-')
    .replace(/^-+|-+$/g, '');

  nameEl.addEventListener('input', () => {
    if (!slugTouched) slugEl.value = slugify(nameEl.value);
    updatePreview();
  });
  slugEl.addEventListener('input', () => { slugTouched = true; updatePreview(); });

  // SEO character counters
  const titleEl = document.getElementById('metaTitle');
  const titleCount = document.getElementById('metaTitleCount');
  const descEl = document.getElementById('metaDesc');
  const descCount = document.getElementById('metaDescCount');

  function paintCount(el, len, ideal) {
    el.textContent = len + ' / ' + ideal;
    el.className = 'text-xs font-semibold ' + (len === 0 ? 'text-slate-300' : len <= ideal ? 'seo-count-ok' : (len <= ideal + 15 ? 'seo-count-warn' : 'seo-count-bad'));
  }

  function updatePreview() {
    document.getElementById('serpSlug').textContent = slugEl.value || 'category-slug';
    document.getElementById('serpTitle').textContent = titleEl.value || nameEl.value || 'Category Title';
    document.getElementById('serpDesc').textContent = descEl.value || 'Meta description preview appears here as you type…';
    paintCount(titleCount, titleEl.value.length, 60);
    paintCount(descCount, descEl.value.length, 155);
  }

  titleEl.addEventListener('input', updatePreview);
  descEl.addEventListener('input', updatePreview);
  updatePreview();
})();
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
