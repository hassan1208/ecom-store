<?php
// includes/page-hero.php — compact dark page header used by utility pages
// (cart, checkout, account, contact, blog…). Set before including:
// $hero_title (required), $hero_eyebrow, $hero_sub, $hero_crumbs ([label => url|null]).
$hero_crumbs = $hero_crumbs ?? [];
?>
<section class="relative bg-ink text-white overflow-hidden">
  <div class="absolute inset-0 bg-grid opacity-50" aria-hidden="true"></div>
  <div class="absolute -right-32 -top-32 w-96 h-96 rounded-full bg-ignite/25 blur-[100px]" aria-hidden="true"></div>
  <div class="container-x relative py-12 sm:py-16">
    <?php if ($hero_crumbs): ?>
    <nav class="text-xs text-white/50 mb-4" aria-label="Breadcrumb">
      <a href="<?= url('') ?>" class="hover:text-white">Home</a>
      <?php foreach ($hero_crumbs as $label => $href): ?>
      <i class="fa-solid fa-chevron-right text-[8px] mx-1.5 opacity-60"></i><?php if ($href): ?><a href="<?= h($href) ?>" class="hover:text-white"><?= h($label) ?></a><?php else: ?><span class="text-white/80" aria-current="page"><?= h($label) ?></span><?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if (!empty($hero_eyebrow)): ?><p class="eyebrow mb-3"><?= h($hero_eyebrow) ?></p><?php endif; ?>
    <h1 class="font-display font-bold uppercase text-4xl sm:text-5xl leading-none"><?= h($hero_title) ?></h1>
    <?php if (!empty($hero_sub)): ?><p class="text-white/60 mt-3 max-w-xl"><?= h($hero_sub) ?></p><?php endif; ?>
  </div>
</section>
