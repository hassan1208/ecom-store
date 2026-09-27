<?php
// admin/pages/design-requests.php — AI design requests captured from the storefront:
// a customer's reference photo waiting to be vectorized and mocked up.
require_once __DIR__ . '/../../includes/config.php';
require_admin();
$page_title = 'Design Requests';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    require_csrf_get();
    $row = fetch_one("SELECT * FROM design_requests WHERE id=?", 'i', (int)$_GET['id']);
    if ($row) {
        foreach ([$row['original_image_path'], $row['vector_svg_path'], $row['mockup_front_path'], $row['mockup_back_path'], $row['mockup_sleeve_path']] as $p) {
            if ($p && file_exists(UPLOAD_PATH . $p)) @unlink(UPLOAD_PATH . $p);
        }
        db()->query("DELETE FROM design_requests WHERE id=" . (int)$row['id']);
    }
    set_flash('success', 'Design request deleted.');
    header('Location: ' . ADMIN_URL . '/pages/design-requests.php'); exit;
}

$status_filter = $_GET['status'] ?? '';
$valid_statuses = ['new', 'vectorized', 'mockup_ready', 'approved', 'rejected'];
$where = '1=1'; $types = ''; $params = [];
if (in_array($status_filter, $valid_statuses, true)) { $where = 'status=?'; $types = 's'; $params = [$status_filter]; }

$requests = fetch_all("SELECT * FROM design_requests WHERE $where ORDER BY created_at DESC", $types, ...$params);
$counts = fetch_all("SELECT status, COUNT(*) c FROM design_requests GROUP BY status");
$count_map = array_column($counts, 'c', 'status');
$total = array_sum($count_map);

$status_colors = [
    'new' => 'bg-sky-100 text-sky-700', 'vectorized' => 'bg-amber-100 text-amber-700',
    'mockup_ready' => 'bg-violet-100 text-violet-700', 'approved' => 'bg-emerald-100 text-emerald-700',
    'rejected' => 'bg-slate-100 text-slate-500',
];
$status_labels = ['new' => 'New', 'vectorized' => 'Vectorized', 'mockup_ready' => 'Mockup Ready', 'approved' => 'Approved', 'rejected' => 'Rejected'];

include __DIR__ . '/../includes/admin-header.php';
?>
<div class="flex items-center gap-2 mb-5 overflow-x-auto">
  <a href="?status=" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition <?= $status_filter === '' ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
    All <span class="opacity-70">(<?= $total ?>)</span>
  </a>
  <?php foreach ($status_labels as $key => $label): ?>
  <a href="?status=<?= $key ?>" class="px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap transition <?= $status_filter === $key ? 'bg-brand text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
    <?= $label ?> <span class="opacity-70">(<?= $count_map[$key] ?? 0 ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<div class="card overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Photo</th>
          <th class="px-5 py-3 font-semibold">Customer</th>
          <th class="px-5 py-3 font-semibold">Product</th>
          <th class="px-5 py-3 font-semibold">Received</th>
          <th class="px-5 py-3 font-semibold">Status</th>
          <th class="px-5 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($requests as $req): ?>
        <tr class="hover:bg-slate-50/60 align-top">
          <td class="px-5 py-3">
            <a href="design-request-detail.php?id=<?= (int)$req['id'] ?>">
              <img src="<?= UPLOAD_URL . h($req['original_image_path']) ?>" class="w-12 h-12 rounded-lg object-cover border border-slate-200">
            </a>
          </td>
          <td class="px-5 py-3">
            <div class="font-semibold text-slate-800 text-[13px]"><?= h($req['customer_name']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><a href="mailto:<?= h($req['email']) ?>" class="hover:text-brand"><i class="fa-solid fa-envelope mr-1"></i><?= h($req['email']) ?></a></div>
            <?php if ($req['phone']): ?><div class="text-[11px] text-slate-500"><a href="tel:<?= h($req['phone']) ?>" class="hover:text-brand"><i class="fa-solid fa-phone mr-1"></i><?= h($req['phone']) ?></a></div><?php endif; ?>
          </td>
          <td class="px-5 py-3 text-slate-600"><?= h($req['product_name'] ?: '—') ?></td>
          <td class="px-5 py-3 text-slate-400 text-xs whitespace-nowrap"><?= format_date($req['created_at'], 'M d, Y') ?></td>
          <td class="px-5 py-3"><span class="text-xs font-semibold rounded-full px-2.5 py-1 <?= $status_colors[$req['status']] ?>"><?= $status_labels[$req['status']] ?></span></td>
          <td class="px-5 py-3 text-right whitespace-nowrap">
            <a href="design-request-detail.php?id=<?= (int)$req['id'] ?>" class="btn-outline btn-sm"><i class="fa-solid fa-wand-magic-sparkles"></i> Open</a>
            <a href="?action=delete&id=<?= (int)$req['id'] ?>&csrf_token=<?= csrf_token() ?>" data-confirm="Delete this design request?" class="btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($requests)): ?>
        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-400">No design requests yet. They'll show up here when a customer uploads a photo on a product page.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
