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
<main id="main">
<?php $hero_title = 'Contact Us'; $hero_eyebrow = 'Get in touch'; $hero_sub = "Bulk order, wholesale request or a custom kit? Send us a message — we reply within one business day."; $hero_crumbs = ['Contact' => null]; include __DIR__ . '/includes/page-hero.php'; ?>

  <section class="container-x py-16 grid grid-cols-1 lg:grid-cols-5 gap-10">
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
            <label class="label">Full Name *</label>
            <input type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>" class="input">
          </div>
          <div>
            <label class="label">Email *</label>
            <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>" class="input">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label">Phone</label>
            <input type="text" name="phone" value="<?= h($_POST['phone'] ?? '') ?>" class="input">
          </div>
          <div>
            <label class="label">Company</label>
            <input type="text" name="company" value="<?= h($_POST['company'] ?? '') ?>" class="input">
          </div>
        </div>
        <div>
          <label class="label">Quantity Needed <span class="text-slate-400 font-normal">(for bulk orders)</span></label>
          <input type="number" min="1" name="quantity_needed" value="<?= h($_POST['quantity_needed'] ?? '') ?>" placeholder="e.g. 100" class="input">
        </div>
        <div>
          <label class="label">Message *</label>
          <textarea name="message" rows="5" required class="input"><?= h($_POST['message'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn-primary btn-shine">
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
