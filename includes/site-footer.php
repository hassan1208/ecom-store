<?php
// includes/site-footer.php — shared public storefront footer
$footer_columns = get_footer_columns();
$site_name      = setting('site_name', 'BuiltCo Sports');
$logo           = setting('site_logo', '');
$about_text     = setting('footer_about_text', '');
$copyright      = setting('footer_copyright_text', '© ' . date('Y') . ' ' . $site_name . '. All rights reserved.');
$phone          = setting('contact_phone', '');
$email          = setting('contact_email', '');
$address        = setting('contact_address', '');
$socials        = ['facebook' => setting('social_facebook', ''), 'instagram' => setting('social_instagram', ''), 'twitter' => setting('social_twitter', ''), 'youtube' => setting('social_youtube', ''), 'tiktok' => setting('social_tiktok', '')];
$social_icons   = ['facebook' => 'fa-brands fa-facebook-f', 'instagram' => 'fa-brands fa-instagram', 'twitter' => 'fa-brands fa-x-twitter', 'youtube' => 'fa-brands fa-youtube', 'tiktok' => 'fa-brands fa-tiktok'];
?>
<footer class="bg-ink text-white/80">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
    <div class="lg:col-span-2">
      <?php if ($logo): ?>
        <img src="<?= UPLOAD_URL . h($logo) ?>" alt="<?= h($site_name) ?>" class="h-9 w-auto mb-4 brightness-0 invert">
      <?php else: $name_words = explode(' ', strtoupper($site_name)); $last_word = array_pop($name_words); ?>
        <span class="font-display font-bold text-xl tracking-wide text-white">
          <?= h(implode(' ', $name_words)) ?> <span class="text-ignite"><?= h($last_word) ?></span>
        </span>
      <?php endif; ?>
      <p class="text-sm text-white/50 leading-relaxed mt-3 max-w-sm"><?= h($about_text) ?></p>
      <?php if (array_filter($socials)): ?>
      <div class="flex items-center gap-3 mt-5">
        <?php foreach ($socials as $key => $link): if (!$link) continue; ?>
        <a href="<?= h($link) ?>" target="_blank" rel="noopener"
           class="w-9 h-9 rounded-full border border-white/15 flex items-center justify-center hover:bg-ignite hover:border-ignite transition">
          <i class="<?= $social_icons[$key] ?> text-sm"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php foreach ($footer_columns as $col): ?>
    <div>
      <h3 class="font-display font-semibold text-sm uppercase tracking-wider text-white mb-4"><?= h($col['title']) ?></h3>
      <ul class="space-y-2.5">
        <?php foreach ($col['links'] as $link): ?>
        <li><a href="<?= h($link['url']) ?>" class="text-sm text-white/50 hover:text-ignite transition"><?= h($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>

    <?php if ($phone || $email || $address): ?>
    <div>
      <h3 class="font-display font-semibold text-sm uppercase tracking-wider text-white mb-4">Contact</h3>
      <ul class="space-y-3 text-sm text-white/50">
        <?php if ($address): ?><li class="flex gap-2"><i class="fa-solid fa-location-dot mt-1 text-ignite"></i><span><?= h($address) ?></span></li><?php endif; ?>
        <?php if ($phone): ?><li class="flex gap-2 items-center"><i class="fa-solid fa-phone text-ignite"></i><a href="tel:<?= h(preg_replace('/\s+/', '', $phone)) ?>" class="hover:text-white"><?= h($phone) ?></a></li><?php endif; ?>
        <?php if ($email): ?><li class="flex gap-2 items-center"><i class="fa-solid fa-envelope text-ignite"></i><a href="mailto:<?= h($email) ?>" class="hover:text-white"><?= h($email) ?></a></li><?php endif; ?>
        <li class="flex gap-2 items-center"><i class="fa-solid fa-truck-fast text-ignite"></i><a href="<?= url('track-order') ?>" class="hover:text-white">Track Your Order</a></li>
        <li class="flex gap-2 items-center"><i class="fa-solid fa-file-pdf text-ignite"></i><a href="<?= url('catalog') ?>" target="_blank" class="hover:text-white">Download Catalog</a></li>
      </ul>
    </div>
    <?php endif; ?>
  </div>

  <div class="border-t border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5 text-xs text-white/40 text-center">
      <?= h($copyright) ?>
    </div>
  </div>
</footer>

<?php $wa_link = whatsapp_link('Hi! I have a question about ' . $site_name . '.'); if ($wa_link): ?>
<a href="<?= h($wa_link) ?>" target="_blank" rel="noopener" title="Chat on WhatsApp"
   class="fixed bottom-5 right-5 z-50 w-14 h-14 rounded-full bg-[#25D366] hover:bg-[#20bd5a] text-white flex items-center justify-center shadow-lg shadow-black/20 transition hover:scale-105">
  <i class="fa-brands fa-whatsapp text-3xl"></i>
</a>
<?php endif; ?>

<style>
.tilt-3d { transition: transform 0.2s ease-out; will-change: transform; }
</style>
<script>
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  var MAX_TILT = 8; // degrees
  document.querySelectorAll('.tilt-3d').forEach(function (el) {
    el.addEventListener('mousemove', function (e) {
      var rect = el.getBoundingClientRect();
      var px = (e.clientX - rect.left) / rect.width;
      var py = (e.clientY - rect.top) / rect.height;
      var rotateY = (px - 0.5) * MAX_TILT * 2;
      var rotateX = (0.5 - py) * MAX_TILT * 2;
      el.style.transform = 'perspective(800px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) scale(1.03)';
    });
    el.addEventListener('mouseleave', function () { el.style.transform = ''; });
  });
})();
</script>

<?php $footer_scripts = setting('custom_footer_scripts', ''); if ($footer_scripts) echo $footer_scripts; ?>
</body>
</html>
