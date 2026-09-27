<?php
// includes/auth-shell.php — split-screen layout for Sign in / Register.
// auth_shell_open() before the form markup, auth_shell_close() after it.
function auth_shell_open() { ?>
<main id="main" class="container-x py-12 sm:py-16">
  <div class="grid lg:grid-cols-2 rounded-[32px] overflow-hidden bg-white border border-black/5 shadow-card max-w-5xl mx-auto">
    <div class="relative hidden lg:flex flex-col justify-between bg-ink text-white p-10 overflow-hidden">
      <div class="absolute inset-0 bg-grid opacity-60" aria-hidden="true"></div>
      <div class="absolute -left-24 -bottom-24 w-80 h-80 rounded-full bg-ignite/40 blur-[90px]" aria-hidden="true"></div>
      <p class="relative eyebrow">Members</p>
      <div class="relative">
        <p class="font-display font-bold uppercase text-4xl leading-[0.95] mb-6">Track orders.<br>Save designs.<br><span class="text-ignite">Reorder fast.</span></p>
        <ul class="space-y-3 text-sm text-white/70">
          <li><i class="fa-solid fa-truck-fast text-ignite w-5"></i> Live order status &amp; invoices</li>
          <li><i class="fa-solid fa-heart text-ignite w-5"></i> Wishlist across devices</li>
          <li><i class="fa-solid fa-cube text-ignite w-5"></i> Custom kits designed in 3D</li>
        </ul>
      </div>
      <p class="relative text-xs text-white/40"><?= h(setting('site_name')) ?> · <?= h(setting('site_tagline')) ?></p>
    </div>
    <div class="p-8 sm:p-12">
<?php }
function auth_shell_close() { ?>
    </div>
  </div>
</main>
<?php }
