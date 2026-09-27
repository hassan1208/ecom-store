<?php
// includes/site-footer.php — shared public storefront footer
$footer_columns = get_footer_columns();
$site_name      = setting('site_name', 'BuiltCo Sports');
$logo           = setting('site_logo', '');
$about_text     = setting('footer_about_text', '');
$copyright      = setting('footer_copyright_text', '') ?: ('© ' . date('Y') . ' ' . $site_name . '. All rights reserved.');
// Older installs saved the © sign in a legacy encoding — normalise it.
$copyright      = preg_replace('/^\x{FFFD}|^\xA9/u', '©', mb_check_encoding($copyright, 'UTF-8') ? $copyright : mb_convert_encoding($copyright, 'UTF-8', 'ISO-8859-1'));
$phone          = setting('contact_phone', '');
$email          = setting('contact_email', '');
$address        = setting('contact_address', '');
$socials        = ['facebook' => setting('social_facebook', ''), 'instagram' => setting('social_instagram', ''), 'twitter' => setting('social_twitter', ''), 'youtube' => setting('social_youtube', ''), 'tiktok' => setting('social_tiktok', '')];
$social_icons   = ['facebook' => 'fa-brands fa-facebook-f', 'instagram' => 'fa-brands fa-instagram', 'twitter' => 'fa-brands fa-x-twitter', 'youtube' => 'fa-brands fa-youtube', 'tiktok' => 'fa-brands fa-tiktok'];
$payment_icons  = ['fa-brands fa-cc-visa', 'fa-brands fa-cc-mastercard', 'fa-brands fa-cc-paypal', 'fa-solid fa-building-columns', 'fa-solid fa-money-bill-wave'];
?>
<footer class="relative bg-ink text-white/80 overflow-hidden" aria-labelledby="footer-heading">
  <h2 id="footer-heading" class="sr-only">Footer</h2>
  <div class="absolute inset-0 bg-grid opacity-40 pointer-events-none" aria-hidden="true"></div>
  <div class="absolute -top-40 -right-40 w-[520px] h-[520px] rounded-full bg-ignite/20 blur-3xl pointer-events-none" aria-hidden="true"></div>

  <!-- CTA band -->
  <div class="relative container-x pt-16">
    <div class="relative rounded-[28px] overflow-hidden bg-gradient-to-br from-ignite to-ignite-dark px-6 sm:px-12 py-10 sm:py-12 flex flex-col lg:flex-row lg:items-center justify-between gap-8 noise">
      <div class="relative">
        <p class="font-display uppercase tracking-[0.25em] text-xs text-white/80 mb-2">Team orders · Custom kits · Wholesale</p>
        <p class="font-display font-bold uppercase text-3xl sm:text-4xl text-white leading-none">Design it in 3D. <br class="hidden sm:block">We make it in Sialkot.</p>
      </div>
      <div class="relative flex flex-wrap gap-3">
        <a href="<?= url('kit-builder') ?>" class="btn-dark btn-shine"><i class="fa-solid fa-cube"></i> Open Kit Builder</a>
        <a href="<?= url('contact') ?>" class="btn-ghost-light">Get a Bulk Quote</a>
      </div>
    </div>
  </div>

  <div class="relative container-x py-16 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-12 gap-10">
    <div class="col-span-2 md:col-span-4 lg:col-span-4">
      <a href="<?= url('') ?>" class="inline-flex items-center gap-2" aria-label="<?= h($site_name) ?> — home">
        <?php if ($logo): ?>
          <img src="<?= UPLOAD_URL . h($logo) ?>" alt="<?= h($site_name) ?>" class="h-9 w-auto brightness-0 invert" loading="lazy" width="140" height="36">
        <?php else: $name_words = explode(' ', strtoupper($site_name)); $last_word = array_pop($name_words); ?>
          <span class="w-9 h-9 rounded-xl bg-ignite text-white flex items-center justify-center font-poster text-lg -rotate-6"><?= h(substr($site_name, 0, 1)) ?></span>
          <span class="font-display font-bold text-xl tracking-wide text-white"><?= h(implode(' ', $name_words)) ?> <span class="text-ignite"><?= h($last_word) ?></span></span>
        <?php endif; ?>
      </a>
      <?php if ($about_text): ?><p class="text-sm text-white/55 leading-relaxed mt-4 max-w-sm"><?= h($about_text) ?></p><?php endif; ?>

      <?php if (section_enabled('newsletter')): ?>
      <form method="POST" action="<?= url('') ?>#newsletter" class="mt-6 max-w-sm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="subscribe">
        <label for="footerEmail" class="text-xs font-bold uppercase tracking-wider text-white/60">Get drops &amp; deals first</label>
        <div class="mt-2 flex rounded-full bg-white/10 border border-white/15 focus-within:border-ignite transition p-1">
          <input id="footerEmail" type="email" name="email" required placeholder="you@club.com" autocomplete="email" class="flex-1 min-w-0 bg-transparent px-4 text-sm text-white placeholder:text-white/40 focus:outline-none">
          <button type="submit" class="rounded-full bg-ignite hover:bg-ignite-dark text-white w-10 h-10 flex items-center justify-center transition" aria-label="Subscribe"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </form>
      <?php endif; ?>

      <?php if (array_filter($socials)): ?>
      <div class="flex items-center gap-2 mt-6">
        <?php foreach ($socials as $key => $link): if (!$link) continue; ?>
        <a href="<?= h($link) ?>" target="_blank" rel="noopener me" aria-label="<?= h(ucfirst($key)) ?>"
           class="w-10 h-10 rounded-full border border-white/15 flex items-center justify-center hover:bg-ignite hover:border-ignite hover:-translate-y-0.5 transition">
          <i class="<?= $social_icons[$key] ?> text-sm"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php foreach ($footer_columns as $col): ?>
    <div class="lg:col-span-2">
      <h3 class="font-display font-semibold text-sm uppercase tracking-[0.18em] text-white mb-5"><?= h($col['title']) ?></h3>
      <ul class="space-y-3">
        <?php foreach ($col['links'] as $link): ?>
        <li><a href="<?= h($link['url']) ?>" class="text-sm text-white/55 hover:text-white hover:translate-x-1 inline-block transition"><?= h($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>

    <div class="col-span-2 md:col-span-2 lg:col-span-2">
      <h3 class="font-display font-semibold text-sm uppercase tracking-[0.18em] text-white mb-5">Contact</h3>
      <ul class="space-y-3 text-sm text-white/55">
        <?php if ($address): ?><li class="flex gap-2.5"><i class="fa-solid fa-location-dot mt-1 text-ignite"></i><span><?= h($address) ?></span></li><?php endif; ?>
        <?php if ($phone): ?><li class="flex gap-2.5 items-center"><i class="fa-solid fa-phone text-ignite"></i><a href="tel:<?= h(preg_replace('/\s+/', '', $phone)) ?>" class="hover:text-white"><?= h($phone) ?></a></li><?php endif; ?>
        <?php if ($email): ?><li class="flex gap-2.5 items-center"><i class="fa-solid fa-envelope text-ignite"></i><a href="mailto:<?= h($email) ?>" class="hover:text-white break-all"><?= h($email) ?></a></li><?php endif; ?>
        <li class="flex gap-2.5 items-center"><i class="fa-solid fa-truck-fast text-ignite"></i><a href="<?= url('track-order') ?>" class="hover:text-white">Track your order</a></li>
        <li class="flex gap-2.5 items-center"><i class="fa-solid fa-file-pdf text-ignite"></i><a href="<?= url('catalog') ?>" target="_blank" class="hover:text-white">Download catalog</a></li>
      </ul>
    </div>
  </div>

  <div class="relative border-t border-white/10">
    <div class="container-x py-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-white/45">
      <p><?= h($copyright) ?></p>
      <div class="flex items-center gap-4 text-xl text-white/50" aria-label="Accepted payment methods">
        <?php foreach ($payment_icons as $icon): ?><i class="<?= $icon ?>" aria-hidden="true"></i><?php endforeach; ?>
      </div>
      <p class="flex items-center gap-4">
        <a href="<?= url('sitemap.xml') ?>" class="hover:text-white">Sitemap</a>
        <a href="<?= url('blog') ?>" class="hover:text-white">Blog</a>
        <a href="<?= url('contact') ?>" class="hover:text-white">Contact</a>
      </p>
    </div>
  </div>
</footer>

<button type="button" id="backToTop" class="fixed bottom-5 left-5 z-40 w-11 h-11 rounded-full bg-white text-ink shadow-xl border border-black/5 flex items-center justify-center transition opacity-0 pointer-events-none hover:-translate-y-0.5" aria-label="Back to top">
  <i class="fa-solid fa-arrow-up"></i>
</button>

<?php $wa_link = whatsapp_link('Hi! I have a question about ' . $site_name . '.'); if ($wa_link): ?>
<a href="<?= h($wa_link) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
   class="fixed bottom-5 right-5 z-40 w-14 h-14 rounded-full bg-[#25D366] hover:bg-[#20bd5a] text-white flex items-center justify-center shadow-lg shadow-black/20 transition hover:scale-105">
  <i class="fa-brands fa-whatsapp text-3xl"></i>
</a>
<?php endif; ?>

<script src="<?= asset_url('assets/js/site.js') ?>" defer></script>
<?php $footer_scripts = setting('custom_footer_scripts', ''); if ($footer_scripts) echo $footer_scripts; ?>
</body>
</html>
