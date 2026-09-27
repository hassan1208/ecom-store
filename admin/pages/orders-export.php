<?php
// admin/pages/orders-export.php — CSV export respecting the current status/search filters
require_once __DIR__ . '/../../includes/config.php';
require_admin();

$status_filter = $_GET['status'] ?? '';
$search        = sanitize($_GET['q'] ?? '');
$statuses      = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed', 'refunded'];

$where = ['1=1']; $types = ''; $params = [];
if (in_array($status_filter, $statuses)) { $where[] = 'status=?'; $types .= 's'; $params[] = $status_filter; }
if ($search !== '') {
    $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)';
    $types .= 'sss'; $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like;
}
$where_sql = implode(' AND ', $where);

$orders = fetch_all("SELECT * FROM orders WHERE $where_sql ORDER BY created_at DESC", $types, ...$params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Order #', 'Customer', 'Email', 'Phone', 'Address', 'City', 'Country', 'Subtotal', 'Shipping', 'Total', 'Payment Method', 'Status', 'Date']);
foreach ($orders as $o) {
    fputcsv($out, [
        $o['order_number'], $o['customer_name'], $o['customer_email'], $o['customer_phone'],
        $o['shipping_address'], $o['shipping_city'], $o['shipping_country'],
        $o['subtotal'], $o['shipping_cost'], $o['total'], $o['payment_method'], $o['status'],
        $o['created_at'],
    ]);
}
fclose($out);
exit;
