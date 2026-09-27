<?php
// contact.php — public Contact Us page. General inquiries (incl. bulk order
// requests with no specific product) are captured into bulk_inquiries so the
// admin sees everything in one place under "Bulk Inquiries".
require_once __DIR__ . '/includes/config.php';

$errors = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $company  = sanitize($_POST['company'] ?? '');
    $qty      = (int)($_POST['quantity_needed'] ?? 0) ?: null;
    $message  = sanitize($_POST['message'] ?? '');

    if ($name === '') $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($message === '') $errors[] = 'Please enter a message.';

    if (!$errors) {
        $inquiry_id = insert('bulk_inquiries', [
            'product_id' => null, 'product_name' => null,
            'customer_name' => $name, 'email' => $email, 'phone' => $phone,
            'company' => $company, 'quantity_needed' => $qty, 'message' => $message,
            'status' => 'new',
        ]);
        send_admin_bulk_inquiry_email($inquiry_id);
        $submitted = true;
    }
}

$meta_title       = 'Contact Us | ' . setting('site_name', 'BuiltCo Sports');
$meta_description = 'Get in touch with ' . setting('site_name', 'BuiltCo Sports') . ' for bulk orders, wholesale pricing, or general inquiries.';

include __DIR__ . '/includes/site-header.php';
?>
<main>
  <section class="bg-ink py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <p class="text-ignite font-display font-semibold uppercase tracking-[0.2em] text-xs mb-3">Get In Touch</p>
      <h1 class="font-display font-bold text-white text-3xl sm:text-4xl">Contact Us</h1>
      <p class="text-white/50 mt-2 max-w-lg">Have a bulk order, wholesale request, or general question? Send us a message and we'll get back to you.</p>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-4 sm:px-6 py-16 grid grid-cols-1 lg:grid-cols-5 gap-10">
    <div class="lg:col-span-3">
      <?php if ($submitted): ?>
      <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 p-6 flex items-start gap-3">
        <i class="fa-solid fa-circle-check text-lg mt-0.5"></i>
        <div>
          <p class="font-semibold">Thanks, <?= h($name ?? '') ?>! Your message has been received.</p>
          <p class="text-sm mt-1 text-emerald-700">Our team will get back to you shortly — especially for bulk/wholesale requests.</p>
        </div>
      </div>
      <?php else: ?>
      <?php if ($errors): ?>
      <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 p-4 mb-5 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <form method="POST" class="card-form space-y-4">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name *</label>
            <input type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email *</label>
            <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Phone</label>
            <input type="text" name="phone" value="<?= h($_POST['phone'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Company</label>
            <input type="text" name="company" value="<?= h($_POST['company'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite">
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">Quantity Needed <span class="text-slate-400 font-normal">(for bulk orders)</span></label>
          <input type="number" min="1" name="quantity_needed" value="<?= h($_POST['quantity_needed'] ?? '') ?>" placeholder="e.g. 100" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite">
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1.5">Message *</label>
          <textarea name="message" rows="5" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-ignite/20 focus:border-ignite"><?= h($_POST['message'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-ignite hover:bg-ignite-dark text-white font-display font-semibold uppercase tracking-wide text-sm px-7 py-3.5 rounded-full transition">
          Send Message <i class="fa-solid fa-paper-plane"></i>
        </button>
      </form>
      <?php endif; ?>
    </div>

    <div class="lg:col-span-2">
      <div class="rounded-2xl bg-slate-50 border border-slate-200 p-6 space-y-5">
        <?php $phone = setting('contact_phone', ''); $email = setting('contact_email', ''); $address = setting('contact_address', ''); ?>
        <?php if ($address): ?>
        <div class="flex gap-3"><i class="fa-solid fa-location-dot text-ignite mt-1"></i><span class="text-sm text-slate-600"><?= h($address) ?></span></div>
        <?php endif; ?>
        <?php if ($phone): ?>
        <div class="flex gap-3"><i class="fa-solid fa-phone text-ignite mt-1"></i><a href="tel:<?= h(preg_replace('/\s+/', '', $phone)) ?>" class="text-sm text-slate-600 hover:text-ignite"><?= h($phone) ?></a></div>
        <?php endif; ?>
        <?php if ($email): ?>
        <div class="flex gap-3"><i class="fa-solid fa-envelope text-ignite mt-1"></i><a href="mailto:<?= h($email) ?>" class="text-sm text-slate-600 hover:text-ignite"><?= h($email) ?></a></div>
        <?php endif; ?>
        <?php if (!$address && !$phone && !$email): ?>
        <p class="text-sm text-slate-400">Contact details haven't been added yet — set them in Admin → Settings.</p>
        <?php endif; ?>
        <a href="<?= url('catalog') ?>" target="_blank" class="flex items-center justify-center gap-2 w-full bg-ink hover:bg-slate-800 text-white font-display font-semibold uppercase tracking-wide text-xs px-5 py-3 rounded-full transition">
          <i class="fa-solid fa-file-pdf"></i> Download Product Catalog
        </a>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/site-footer.php'; ?>
