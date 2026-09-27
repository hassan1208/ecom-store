<?php
// cron/send_abandoned_cart_reminders.php — sends a one-time "you left something
// in your cart" email for carts abandoned longer than the configured threshold.
//
// This is CLI-only on purpose: it sends real emails on a schedule, and a
// web-reachable version of this script would let anyone trigger a mass-email
// blast just by requesting the URL. Wire it up as an actual cron job, e.g. on
// cPanel: "Cron Jobs" -> run every 30 minutes ->
//   php /home/USER/public_html/cron/send_abandoned_cart_reminders.php
// (adjust the path to wherever this project is deployed).
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line (cron), not the web.');
}

require_once __DIR__ . '/../includes/config.php';

if (setting('abandoned_cart_reminder_enabled', '1') !== '1') {
    echo "Abandoned cart reminders are disabled in Settings. Nothing to do.\n";
    exit;
}

$hours = max(1, (int)setting('abandoned_cart_reminder_hours', '3'));
$carts = fetch_all(
    "SELECT * FROM abandoned_carts
     WHERE status='active' AND email IS NOT NULL AND email <> '' AND reminder_sent_at IS NULL
       AND updated_at <= DATE_SUB(NOW(), INTERVAL ? HOUR)",
    'i', $hours
);

echo count($carts) . " abandoned cart(s) due for a reminder.\n";

$sent = 0;
foreach ($carts as $cart) {
    if (empty($cart['restore_token'])) {
        $cart['restore_token'] = bin2hex(random_bytes(16));
        update_record('abandoned_carts', ['restore_token' => $cart['restore_token']], 'id', $cart['id']);
    }

    if (send_abandoned_cart_reminder_email($cart)) {
        update_record('abandoned_carts', ['reminder_sent_at' => date('Y-m-d H:i:s')], 'id', $cart['id']);
        $sent++;
        echo "  Sent to {$cart['email']} (cart #{$cart['id']})\n";
    } else {
        echo "  FAILED to send to {$cart['email']} (cart #{$cart['id']}) — will retry next run.\n";
    }
}

echo "$sent reminder(s) sent.\n";
