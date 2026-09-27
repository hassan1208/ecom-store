<?php
// admin/pages/design-request-detail.php — vectorize a customer's uploaded photo
// (via the local vtracer tool) and generate front/back/sleeve mockups by placing
// the resulting SVG onto the product's tagged blank-template photos.
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$req = fetch_one("SELECT * FROM design_requests WHERE id=?", 'i', $id);
if (!$req) { set_flash('error', 'Design request not found.'); header('Location: ' . ADMIN_URL . '/pages/design-requests.php'); exit; }

if (($_GET['action'] ?? '') === 'status' && isset($_GET['to'])) {
    require_csrf_get();
    $valid_statuses = ['new', 'vectorized', 'mockup_ready', 'approved', 'rejected'];
    if (in_array($_GET['to'], $valid_statuses, true)) {
        update_record('design_requests', ['status' => $_GET['to']], 'id', $id);
    }
    header('Location: ' . ADMIN_URL . '/pages/design-request-detail.php?id=' . $id); exit;
}

$page_title = 'Design Request #' . $id;

$mockup_templates = [];
if ($req['product_id']) {
    $imgs = fetch_all("SELECT * FROM product_images WHERE product_id=? AND mockup_view IS NOT NULL", 'i', $req['product_id']);
    foreach ($imgs as $img) $mockup_templates[$img['mockup_view']] = UPLOAD_URL . $img['image_path'];
}

$status_colors = [
    'new' => 'bg-sky-100 text-sky-700', 'vectorized' => 'bg-amber-100 text-amber-700',
    'mockup_ready' => 'bg-violet-100 text-violet-700', 'approved' => 'bg-emerald-100 text-emerald-700',
    'rejected' => 'bg-slate-100 text-slate-500',
];
$status_labels = ['new' => 'New', 'vectorized' => 'Vectorized', 'mockup_ready' => 'Mockup Ready', 'approved' => 'Approved', 'rejected' => 'Rejected'];

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center justify-between mb-5">
  <a href="design-requests.php" class="text-sm font-semibold text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Design Requests</a>
  <select onchange="location.href='?id=<?= $id ?>&action=status&to='+this.value+'&csrf_token=<?= csrf_token() ?>'" class="text-xs font-semibold rounded-full px-3 py-1.5 border-0 <?= $status_colors[$req['status']] ?>">
    <?php foreach ($status_labels as $key => $label): ?>
    <option value="<?= $key ?>" <?= $req['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Customer & original photo -->
  <div class="card p-6">
    <h2 class="font-bold text-slate-800 mb-4">Customer</h2>
    <div class="text-sm space-y-1.5 mb-5">
      <div class="font-semibold text-slate-800"><?= h($req['customer_name']) ?></div>
      <div class="text-slate-500"><a href="mailto:<?= h($req['email']) ?>" class="hover:text-brand"><i class="fa-solid fa-envelope mr-1.5"></i><?= h($req['email']) ?></a></div>
      <?php if ($req['phone']): ?><div class="text-slate-500"><a href="tel:<?= h($req['phone']) ?>" class="hover:text-brand"><i class="fa-solid fa-phone mr-1.5"></i><?= h($req['phone']) ?></a></div><?php endif; ?>
      <div class="text-slate-500"><i class="fa-solid fa-shirt mr-1.5"></i><?= h($req['product_name'] ?: 'No product linked') ?></div>
    </div>
    <?php if ($req['message']): ?>
    <div class="mb-5 p-3 rounded-lg bg-slate-50 text-sm text-slate-600"><?= nl2br(h($req['message'])) ?></div>
    <?php endif; ?>
    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Original Photo</h3>
    <a href="<?= UPLOAD_URL . h($req['original_image_path']) ?>" target="_blank">
      <img src="<?= UPLOAD_URL . h($req['original_image_path']) ?>" class="w-full rounded-lg border border-slate-200">
    </a>
  </div>

  <!-- Vectorize -->
  <div class="card p-6">
    <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-bezier-curve text-indigo-500 mr-1.5"></i>Vectorize</h2>
    <p class="f-hint mb-4">Runs locally (no external service, no per-image cost). "Photo" works best for detailed AI art; try "Poster" or "B&amp;W" for simple logos.</p>
    <div class="flex items-center gap-2 mb-4">
      <select id="vecPreset" class="f-select flex-1">
        <option value="photo">Photo (detailed)</option>
        <option value="poster">Poster (flat colors)</option>
        <option value="bw">Black &amp; White</option>
      </select>
      <button type="button" id="vecBtn" class="btn-primary shrink-0"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button>
    </div>
    <div id="vecResult">
      <?php if ($req['vector_svg_path']): ?>
      <img id="vecPreviewImg" src="<?= UPLOAD_URL . h($req['vector_svg_path']) ?>?t=<?= time() ?>" class="w-full rounded-lg border border-slate-200 bg-[url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAG0lEQVQ4T2NkYGD4z0AEYBxVSF+FjHRXAAADqwEZ9v6nRQAAAABJRU5ErkJggg==')] bg-repeat mb-2">
      <a href="<?= UPLOAD_URL . h($req['vector_svg_path']) ?>" target="_blank" class="text-xs font-semibold text-brand">Download SVG</a>
      <?php else: ?>
      <p class="text-sm text-slate-400">Not vectorized yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Mockups -->
  <div class="card p-6">
    <h2 class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-shirt text-purple-500 mr-1.5"></i>Front / Back / Sleeve Mockups</h2>
    <?php if (!$req['product_id']): ?>
    <p class="f-hint">No product linked to this request.</p>
    <?php elseif (empty($mockup_templates)): ?>
    <p class="f-hint">This product has no tagged mockup template photos yet. Go to the product's edit page and tag a blank garment photo as "Front template" / "Back template" / "Sleeve template".</p>
    <?php elseif (!$req['vector_svg_path']): ?>
    <p class="f-hint">Generate the vector first, then mockups can be built.</p>
    <?php else: ?>
    <p class="f-hint mb-3">Drag to position, use the slider to resize, then save each view.</p>
    <div id="mockupViews" class="space-y-5"></div>
    <?php endif; ?>
  </div>
</div>

<?php if ($req['product_id'] && !empty($mockup_templates) && $req['vector_svg_path']): ?>
<script>
const requestId = <?= (int)$id ?>;
const csrfToken = <?= json_encode(csrf_token()) ?>;
const vectorUrl = <?= json_encode(UPLOAD_URL . $req['vector_svg_path'] . '?t=' . time()) ?>;
const uploadUrl = <?= json_encode(ADMIN_URL . '/pages/design-mockup-upload.php') ?>;
const templates = <?= json_encode($mockup_templates, JSON_UNESCAPED_SLASHES) ?>;
const savedMockups = <?= json_encode(['front' => $req['mockup_front_path'] ? UPLOAD_URL . $req['mockup_front_path'] : null, 'back' => $req['mockup_back_path'] ? UPLOAD_URL . $req['mockup_back_path'] : null, 'sleeve' => $req['mockup_sleeve_path'] ? UPLOAD_URL . $req['mockup_sleeve_path'] : null], JSON_UNESCAPED_SLASHES) ?>;

const viewLabels = { front: 'Front', back: 'Back', sleeve: 'Sleeve' };
const wrap = document.getElementById('mockupViews');

Object.keys(templates).forEach(view => {
  const W = 600, H = 600;
  const block = document.createElement('div');
  block.className = 'p-3 rounded-lg border border-slate-200';
  block.innerHTML = `
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-semibold text-slate-700">${viewLabels[view]}</span>
      <span class="text-xs text-emerald-600 font-semibold hidden" data-saved-label><i class="fa-solid fa-circle-check mr-1"></i>Saved</span>
    </div>
    <canvas width="${W}" height="${H}" class="w-full aspect-square rounded-lg border border-slate-200 bg-white cursor-move mb-2"></canvas>
    <label class="f-label text-xs">Design Size</label>
    <input type="range" min="10" max="70" value="30" class="w-full mb-2" data-size-slider>
    <button type="button" class="btn-outline btn-sm w-full" data-save-btn><i class="fa-solid fa-floppy-disk"></i> Save ${viewLabels[view]} Mockup</button>
  `;
  wrap.appendChild(block);

  const canvas = block.querySelector('canvas');
  const ctx = canvas.getContext('2d');
  const sizeSlider = block.querySelector('[data-size-slider]');
  const saveBtn = block.querySelector('[data-save-btn]');
  const savedLabel = block.querySelector('[data-saved-label]');
  if (savedMockups[view]) savedLabel.classList.remove('hidden');

  let baseImg = null, designImg = null;
  let designSizePct = 30;
  let pos = { x: W / 2, y: H / 2 };
  let designW = 0, designH = 0;

  function fitCover(img, w, h) {
    const ir = img.width / img.height, cr = w / h;
    let sw, sh, sx, sy;
    if (ir > cr) { sh = img.height; sw = sh * cr; sx = (img.width - sw) / 2; sy = 0; }
    else { sw = img.width; sh = sw / cr; sx = 0; sy = (img.height - sh) / 2; }
    return { sx, sy, sw, sh };
  }

  function redraw() {
    ctx.clearRect(0, 0, W, H);
    if (baseImg) {
      const f = fitCover(baseImg, W, H);
      ctx.drawImage(baseImg, f.sx, f.sy, f.sw, f.sh, 0, 0, W, H);
    } else { ctx.fillStyle = '#f1f5f9'; ctx.fillRect(0, 0, W, H); }
    if (designImg) {
      designW = W * (designSizePct / 100);
      designH = designW * (designImg.height / designImg.width);
      ctx.drawImage(designImg, pos.x - designW / 2, pos.y - designH / 2, designW, designH);
    }
  }

  const bImg = new Image(); bImg.onload = () => { baseImg = bImg; redraw(); }; bImg.src = templates[view];
  const dImg = new Image(); dImg.onload = () => { designImg = dImg; redraw(); }; dImg.src = vectorUrl;

  sizeSlider.addEventListener('input', e => { designSizePct = +e.target.value; redraw(); });

  let dragging = false, dragOffset = { x: 0, y: 0 };
  function canvasPoint(e) {
    const rect = canvas.getBoundingClientRect();
    const cx = e.touches ? e.touches[0].clientX : e.clientX;
    const cy = e.touches ? e.touches[0].clientY : e.clientY;
    return { x: (cx - rect.left) * (W / rect.width), y: (cy - rect.top) * (H / rect.height) };
  }
  function hit(p) { return designImg && p.x >= pos.x - designW / 2 && p.x <= pos.x + designW / 2 && p.y >= pos.y - designH / 2 && p.y <= pos.y + designH / 2; }
  canvas.addEventListener('mousedown', e => { const p = canvasPoint(e); if (hit(p)) { dragging = true; dragOffset = { x: p.x - pos.x, y: p.y - pos.y }; } });
  canvas.addEventListener('mousemove', e => { if (!dragging) return; const p = canvasPoint(e); pos = { x: p.x - dragOffset.x, y: p.y - dragOffset.y }; redraw(); });
  window.addEventListener('mouseup', () => dragging = false);
  canvas.addEventListener('touchstart', e => { const p = canvasPoint(e); if (hit(p)) { dragging = true; dragOffset = { x: p.x - pos.x, y: p.y - pos.y }; e.preventDefault(); } }, { passive: false });
  canvas.addEventListener('touchmove', e => { if (!dragging) return; const p = canvasPoint(e); pos = { x: p.x - dragOffset.x, y: p.y - dragOffset.y }; redraw(); e.preventDefault(); }, { passive: false });
  window.addEventListener('touchend', () => dragging = false);

  saveBtn.addEventListener('click', () => {
    saveBtn.disabled = true;
    const original = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    const body = new URLSearchParams();
    body.set('csrf_token', csrfToken);
    body.set('id', requestId);
    body.set('view', view);
    body.set('image_data', canvas.toDataURL('image/png'));
    fetch(uploadUrl, { method: 'POST', body })
      .then(r => r.json())
      .then(res => {
        saveBtn.disabled = false; saveBtn.innerHTML = original;
        if (res.success) savedLabel.classList.remove('hidden');
        else alert(res.error || 'Failed to save mockup.');
      })
      .catch(() => { saveBtn.disabled = false; saveBtn.innerHTML = original; alert('Failed to save mockup.'); });
  });
});
</script>
<?php endif; ?>

<script>
document.getElementById('vecBtn')?.addEventListener('click', function () {
  const btn = this;
  const original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
  const body = new URLSearchParams();
  body.set('csrf_token', <?= json_encode(csrf_token()) ?>);
  body.set('id', <?= (int)$id ?>);
  body.set('preset', document.getElementById('vecPreset').value);
  fetch(<?= json_encode(ADMIN_URL . '/pages/design-vectorize.php') ?>, { method: 'POST', body })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false; btn.innerHTML = original;
      if (res.success) { location.reload(); }
      else alert(res.error || 'Vectorization failed.');
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = original; alert('Vectorization failed.'); });
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
