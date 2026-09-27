<?php
// includes/studio-markup.php — the full-screen 3D Design Studio overlay.
// Included inside product.php's #addToCartForm (none of its inputs carry a
// name, so nothing here is submitted directly — customizer-studio.js packs
// the design into the form's hidden fields). Expects $product, $studio_model,
// $studio_3d.

$studio_fonts = [
    ['value' => 'Oswald, Impact, sans-serif', 'label' => 'Oswald'],
    ['value' => 'Anton, Impact, sans-serif', 'label' => 'Anton'],
    ['value' => "'Bebas Neue', Impact, sans-serif", 'label' => 'Bebas'],
    ['value' => 'Graduate, Georgia, serif', 'label' => 'Varsity'],
    ['value' => "'Black Ops One', Impact, sans-serif", 'label' => 'Stencil'],
    ['value' => "'Russo One', Arial, sans-serif", 'label' => 'Russo'],
    ['value' => "'Arial Black', Arial, sans-serif", 'label' => 'Arial Black'],
    ['value' => 'Impact, sans-serif', 'label' => 'Impact'],
];
$studio_presets = [
    ['name' => 'Classic Red',   'base' => '#c8102e', 'trim' => '#ffffff', 'pattern' => 'none',       'text' => '#ffffff', 'outline' => '#7f0a1d'],
    ['name' => 'Royal Stripes', 'base' => '#1e3a8a', 'trim' => '#ffffff', 'pattern' => 'stripes',    'patternColor' => '#f8fafc', 'text' => '#ffffff', 'outline' => '#1e3a8a'],
    ['name' => 'Midnight Gold', 'base' => '#0b1026', 'trim' => '#d4af37', 'pattern' => 'pinstripes', 'patternColor' => '#d4af37', 'text' => '#d4af37', 'outline' => '#0b1026'],
    ['name' => 'Forest Hoops',  'base' => '#14532d', 'trim' => '#f5f5dc', 'pattern' => 'hoops',      'patternColor' => '#f5f5dc', 'text' => '#f5f5dc', 'outline' => '#14532d'],
    ['name' => 'Volt Black',    'base' => '#111111', 'sleeve' => '#c6ff00', 'trim' => '#c6ff00', 'pattern' => 'none', 'text' => '#c6ff00', 'outline' => '#111111'],
    ['name' => 'Sky Sash',      'base' => '#38bdf8', 'trim' => '#0c4a6e', 'pattern' => 'sash',       'patternColor' => '#0c4a6e', 'text' => '#ffffff', 'outline' => '#0c4a6e'],
    ['name' => 'Sunset Fade',   'base' => '#f97316', 'trim' => '#ffffff', 'pattern' => 'gradient',   'patternColor' => '#7c2d12', 'text' => '#ffffff', 'outline' => '#7c2d12'],
    ['name' => 'Urban Camo',    'base' => '#3f4a3c', 'trim' => '#111111', 'pattern' => 'camo',       'patternColor' => '#252b22', 'text' => '#ffffff', 'outline' => '#111111'],
];
if ($studio_model === 'ball') {
    array_unshift($studio_presets, ['name' => 'Match Classic', 'base' => '#ffffff', 'trim' => '#111111', 'pattern' => 'panels', 'patternColor' => '#111111', 'text' => '#111111', 'outline' => '#ffffff']);
}
$patterns = [
    'none'       => ['None', 'background:#1e293b'],
    'stripes'    => ['Stripes', 'background:repeating-linear-gradient(90deg,#1e293b 0 8px,#e2e8f0 8px 16px)'],
    'pinstripes' => ['Pinstripe', 'background:repeating-linear-gradient(90deg,#1e293b 0 7px,#e2e8f0 7px 8px)'],
    'hoops'      => ['Hoops', 'background:repeating-linear-gradient(0deg,#1e293b 0 7px,#e2e8f0 7px 14px)'],
    'sash'       => ['Sash', 'background:linear-gradient(115deg,#1e293b 38%,#e2e8f0 38% 55%,#1e293b 55%)'],
    'halves'     => ['Halves', 'background:linear-gradient(90deg,#1e293b 50%,#e2e8f0 50%)'],
    'gradient'   => ['Fade', 'background:linear-gradient(180deg,#1e293b,#e2e8f0)'],
    'chevron'    => ['Chevron', 'background:linear-gradient(160deg,transparent 45%,#e2e8f0 45% 58%,transparent 58%) 0 0/50% 100% no-repeat,linear-gradient(200deg,transparent 45%,#e2e8f0 45% 58%,transparent 58%) 100% 0/50% 100% no-repeat,#1e293b'],
    'halftone'   => ['Halftone', 'background:radial-gradient(#e2e8f0 30%,transparent 32%) 0 0/8px 8px,#1e293b'],
    'camo'       => ['Camo', 'background:radial-gradient(circle at 20% 30%,#475569 18%,transparent 19%),radial-gradient(circle at 70% 60%,#0f172a 20%,transparent 21%),radial-gradient(circle at 40% 80%,#64748b 14%,transparent 15%),#1e293b'],
    'panels'     => ['Panels', 'background:radial-gradient(circle,#1e293b 30%,transparent 32%) 0 0/14px 14px,#e2e8f0'],
];
$swatches = ['#ffffff', '#111111', '#c8102e', '#f97316', '#facc15', '#16a34a', '#0ea5e9', '#1e3a8a', '#7c3aed', '#db2777'];
if (!function_exists('studio_swatches')) { function studio_swatches($for, $colors) {
    $out = '<div class="studio-swatches">';
    foreach ($colors as $c) $out .= '<button type="button" data-swatch-for="' . h($for) . '" data-color="' . h($c) . '" style="background:' . h($c) . '" aria-label="' . h($c) . '"></button>';
    return $out . '</div>';
} }
$is_jersey = $studio_model === 'jersey';
?>
<div id="studio" class="studio" role="dialog" aria-modal="true" aria-labelledby="studioTitle" hidden>
  <!-- Top bar -->
  <div class="studio-top">
    <span class="w-8 h-8 rounded-lg bg-ignite flex items-center justify-center shrink-0"><i class="fa-solid fa-cube"></i></span>
    <div class="min-w-0 flex-1">
      <p id="studioTitle" class="font-display font-semibold uppercase tracking-wider text-sm leading-tight">Design Studio</p>
      <p class="text-[11px] text-white/50 truncate"><?= h($product['name']) ?></p>
    </div>
    <div class="flex items-center gap-1">
      <button type="button" data-act="undo" class="w-9 h-9 rounded-lg hover:bg-white/10 disabled:opacity-30" aria-label="Undo (Ctrl+Z)" title="Undo"><i class="fa-solid fa-rotate-left"></i></button>
      <button type="button" data-act="redo" class="w-9 h-9 rounded-lg hover:bg-white/10 disabled:opacity-30" aria-label="Redo (Ctrl+Y)" title="Redo"><i class="fa-solid fa-rotate-right"></i></button>
      <button type="button" data-act="download" class="w-9 h-9 rounded-lg hover:bg-white/10 hidden sm:inline-block" aria-label="Download preview image" title="Download PNG"><i class="fa-solid fa-download"></i></button>
      <button type="button" data-act="reset" class="w-9 h-9 rounded-lg hover:bg-white/10 hidden sm:inline-block" aria-label="Start over" title="Start over"><i class="fa-solid fa-trash-can"></i></button>
      <button type="button" data-act="close" class="ml-1 h-9 px-3 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-bold uppercase tracking-wider" aria-label="Close studio"><i class="fa-solid fa-xmark sm:mr-1"></i><span class="hidden sm:inline">Close</span></button>
    </div>
  </div>

  <div class="studio-body">
    <!-- Stage -->
    <div class="studio-stage" id="studioStage">
      <div class="studio-toolbar">
        <div class="studio-seg" role="group" aria-label="Preview mode">
          <?php if ($studio_3d): ?><button type="button" data-view="3d" class="is-active"><i class="fa-solid fa-cube"></i> 3D</button><?php endif; ?>
          <button type="button" data-view="photo"<?= $studio_3d ? '' : ' class="is-active"' ?>><i class="fa-regular fa-image"></i> Photo</button>
        </div>
        <div class="studio-seg" role="group" aria-label="Side">
          <button type="button" data-side="front" class="is-active">Front</button>
          <button type="button" data-side="back">Back</button>
        </div>
        <div class="studio-seg studio-3d-only" role="group" aria-label="3D controls">
          <button type="button" data-act="spin" aria-label="Auto-rotate" title="Auto-rotate"><i class="fa-solid fa-arrows-spin"></i></button>
          <button type="button" data-act="zoom-out" aria-label="Zoom out"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
          <button type="button" data-act="zoom-in" aria-label="Zoom in"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
          <button type="button" data-act="reset-view" aria-label="Reset view"><i class="fa-solid fa-expand"></i></button>
        </div>
      </div>

      <div id="stage3d" class="studio-3d<?= $studio_3d ? '' : ' hidden' ?>">
        <div class="studio-loading"><div class="relative"><div class="cube-loader"><span></span><span></span><span></span></div></div>Loading 3D studio…</div>
      </div>
      <div id="stagePhoto" class="studio-photo<?= $studio_3d ? ' hidden' : '' ?>">
        <canvas id="studioPhotoFront" width="800" height="800" aria-label="Front preview on product photo"></canvas>
        <canvas id="studioPhotoBack" width="800" height="800" class="hidden" aria-label="Back preview on product photo"></canvas>
      </div>

      <div id="restoreBanner" class="absolute top-16 left-1/2 -translate-x-1/2 z-10 flex items-center gap-3 rounded-2xl bg-white shadow-xl px-4 py-3 text-sm" hidden>
        <i class="fa-solid fa-clock-rotate-left text-ignite"></i>
        <span class="font-medium">Continue your saved design?</span>
        <button type="button" data-act="restore" class="font-bold text-ignite">Restore</button>
        <button type="button" data-act="dismiss-restore" class="text-slate-400" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <div id="studioSelection" class="studio-selection" hidden>
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-bold uppercase tracking-wider"><i class="fa-solid fa-arrow-pointer text-ignite mr-1"></i><span id="selLabel">Logo</span></p>
          <button type="button" data-sel="close" class="text-slate-400 hover:text-ink" aria-label="Deselect"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <label class="text-[11px] font-semibold text-slate-500">Size</label>
        <input type="range" id="selSize" min="20" max="600" class="studio-range">
        <label class="text-[11px] font-semibold text-slate-500 flex justify-between">Rotation <span id="selRotateVal">0°</span></label>
        <input type="range" id="selRotate" min="-180" max="180" value="0" class="studio-range">
        <div class="grid grid-cols-3 gap-1.5 mt-2">
          <button type="button" data-sel="center" class="studio-icon-btn !w-full text-xs" title="Center horizontally"><i class="fa-solid fa-arrows-left-right-to-line"></i></button>
          <button type="button" data-sel="duplicate" class="studio-icon-btn !w-full text-xs" title="Duplicate"><i class="fa-regular fa-clone"></i></button>
          <button type="button" data-sel="delete" class="studio-icon-btn is-danger !w-full text-xs" title="Remove"><i class="fa-solid fa-trash"></i></button>
        </div>
        <p class="text-[10px] text-slate-400 mt-2">Tip: arrow keys nudge (Shift = faster)</p>
      </div>

      <p id="stageHint" class="studio-hint">Drag to spin · drag a logo or text on the model to move it</p>
      <div id="studioToast" class="studio-toast" role="status" hidden></div>
    </div>

    <!-- Tools -->
    <aside class="studio-panel" aria-label="Design tools">
      <div class="studio-tabs" role="tablist">
        <button type="button" class="studio-tab is-active" data-tab="colors" role="tab"><i class="fa-solid fa-palette"></i>Colours</button>
        <button type="button" class="studio-tab" data-tab="logos" role="tab"><i class="fa-solid fa-shield-halved"></i>Logos</button>
        <button type="button" class="studio-tab" data-tab="text" role="tab"><i class="fa-solid fa-font"></i>Text</button>
        <button type="button" class="studio-tab" data-tab="team" role="tab"><i class="fa-solid fa-users"></i>Team</button>
        <button type="button" class="studio-tab" data-tab="finish" role="tab"><i class="fa-solid fa-flag-checkered"></i>Finish</button>
      </div>

      <div class="studio-scroll">
        <!-- COLOURS -->
        <section data-panel="colors">
          <h3 class="studio-h">Kit presets</h3>
          <div class="grid grid-cols-2 gap-2">
            <?php foreach ($studio_presets as $i => $p): ?>
            <button type="button" class="studio-preset" data-preset="<?= $i ?>">
              <span class="dots"><span style="background:<?= h($p['base']) ?>"></span><span style="background:<?= h($p['sleeve'] ?? $p['patternColor'] ?? $p['trim']) ?>"></span><span style="background:<?= h($p['trim']) ?>"></span></span>
              <span class="text-xs font-semibold"><?= h($p['name']) ?></span>
            </button>
            <?php endforeach; ?>
          </div>

          <h3 class="studio-h">Colours</h3>
          <label class="studio-color"><input type="color" id="stBase" value="#1d4ed8"><span class="text-sm font-semibold"><?= $studio_model === 'ball' ? 'Ball' : 'Body' ?></span><?= studio_swatches('stBase', $swatches) ?></label>
          <?php if ($is_jersey): ?>
          <label class="studio-color"><input type="color" id="stSleeve" value="#1d4ed8"><span class="text-sm font-semibold flex items-center gap-2">Sleeves <input type="checkbox" id="stSleeveOn" class="accent-ignite" aria-label="Contrast sleeves"></span><?= studio_swatches('stSleeve', array_slice($swatches, 0, 6)) ?></label>
          <label class="studio-color"><input type="color" id="stTrim" value="#ffffff"><span class="text-sm font-semibold">Collar &amp; cuffs</span><?= studio_swatches('stTrim', array_slice($swatches, 0, 6)) ?></label>
          <?php else: ?>
          <input type="color" id="stSleeve" value="#1d4ed8" hidden><input type="checkbox" id="stSleeveOn" hidden><input type="color" id="stTrim" value="#ffffff" hidden>
          <?php endif; ?>

          <h3 class="studio-h">Pattern</h3>
          <div class="studio-grid">
            <?php foreach ($patterns as $key => [$label, $css]): if ($key === 'panels' && $studio_model !== 'ball') continue; ?>
            <button type="button" class="studio-option" data-pattern="<?= $key ?>"><span class="pat" style="<?= $css ?>"></span><?= $label ?></button>
            <?php endforeach; ?>
          </div>
          <label class="studio-color mt-3"><input type="color" id="stPatternColor" value="#ffffff"><span class="text-sm font-semibold">Pattern colour</span><?= studio_swatches('stPatternColor', array_slice($swatches, 0, 6)) ?></label>

          <label class="flex items-start gap-3 mt-4 text-sm text-slate-600 cursor-pointer">
            <input type="checkbox" id="stTintPhoto" class="accent-ignite mt-1">
            <span><strong class="text-ink">Tint the product photo too</strong><br><span class="text-xs">Applies the body colour to the real photo preview.</span></span>
          </label>
        </section>

        <!-- LOGOS -->
        <section data-panel="logos" hidden>
          <h3 class="studio-h">Logos &amp; crests</h3>
          <p class="studio-sub">Add up to 8 logos to the front or back. PNG with transparency or SVG looks best — we auto-create a print-ready vector for every upload.</p>
          <button type="button" data-act="add-logo" class="studio-dropzone mb-4">
            <i class="fa-solid fa-cloud-arrow-up text-2xl text-ignite"></i>
            <span class="font-semibold text-sm">Upload logo to the <span class="lowercase">current side</span></span>
            <span class="text-xs text-slate-400">or drag &amp; drop onto the preview · PNG, JPG, SVG · max 10MB</span>
          </button>
          <input type="file" id="stLogoInput" accept="image/*,.svg" multiple hidden>
          <div id="logoLayers"></div>
          <p id="logoEmpty" class="text-sm text-slate-400 text-center py-4">No logos yet.</p>
          <p class="text-xs text-slate-400 mt-2"><i class="fa-solid fa-hand-pointer mr-1"></i>Click a logo on the preview to resize, rotate or duplicate it.</p>
        </section>

        <!-- TEXT -->
        <section data-panel="text" hidden>
          <h3 class="studio-h">Font</h3>
          <div class="grid grid-cols-4 gap-2">
            <?php foreach ($studio_fonts as $f): ?>
            <button type="button" class="studio-font" data-font="<?= h($f['value']) ?>" style="font-family:<?= h($f['value']) ?>">10<small><?= h($f['label']) ?></small></button>
            <?php endforeach; ?>
          </div>

          <h3 class="studio-h">Back name</h3>
          <input type="text" id="stName" maxlength="15" placeholder="e.g. RONALDO" class="studio-input w-full uppercase" autocomplete="off">
          <div class="grid grid-cols-2 gap-3 mt-2">
            <label class="text-[11px] font-semibold text-slate-500">Size<input type="range" id="stNameSize" min="30" max="160" class="studio-range"></label>
            <label class="text-[11px] font-semibold text-slate-500">Arch<input type="range" id="stNameArc" min="0" max="100" class="studio-range"></label>
          </div>

          <h3 class="studio-h">Number</h3>
          <div class="grid grid-cols-2 gap-3">
            <input type="text" id="stNumber" maxlength="3" inputmode="numeric" placeholder="7" class="studio-input w-full text-center font-bold text-lg">
            <label class="text-[11px] font-semibold text-slate-500">Back size<input type="range" id="stNumberSize" min="80" max="420" class="studio-range"></label>
          </div>
          <label class="flex items-center gap-2 mt-3 text-sm font-semibold cursor-pointer"><input type="checkbox" id="stFrontNumberOn" class="accent-ignite"> Also show the number on the front</label>
          <div id="stFrontNumberWrap" class="mt-2" hidden>
            <label class="text-[11px] font-semibold text-slate-500">Front size<input type="range" id="stFrontNumberSize" min="40" max="300" class="studio-range"></label>
          </div>

          <h3 class="studio-h">Text colours</h3>
          <label class="studio-color"><input type="color" id="stFill" value="#ffffff"><span class="text-sm font-semibold">Fill</span><?= studio_swatches('stFill', array_slice($swatches, 0, 6)) ?></label>
          <label class="studio-color"><input type="color" id="stOutline" value="#0b0f14"><span class="text-sm font-semibold">Outline</span><?= studio_swatches('stOutline', array_slice($swatches, 0, 6)) ?></label>
          <label class="text-[11px] font-semibold text-slate-500">Outline thickness<input type="range" id="stOutlineWidth" min="0" max="12" class="studio-range"></label>

          <h3 class="studio-h">Extra text <button type="button" data-act="add-text" class="text-ignite normal-case tracking-normal text-xs font-bold"><i class="fa-solid fa-plus mr-1"></i>Add</button></h3>
          <p class="studio-sub">Club name, sponsor, motto… drag it into place on the preview.</p>
          <div id="customTexts"></div>
        </section>

        <!-- TEAM -->
        <section data-panel="team" hidden>
          <h3 class="studio-h">Team roster <span id="rosterCount" class="text-slate-400 normal-case tracking-normal font-semibold">0 players</span></h3>
          <p class="studio-sub">Everyone gets this design with their own name &amp; number<?= $is_jersey ? ' (and size)' : '' ?>. Tap <i class="fa-solid fa-eye"></i> to preview a player.</p>
          <div id="rosterRows"></div>
          <div class="flex gap-2 mt-2">
            <button type="button" data-act="add-player" class="studio-icon-btn !w-auto px-3 text-xs font-bold"><i class="fa-solid fa-user-plus mr-1.5"></i>Add player</button>
            <button type="button" data-act="paste-roster" class="studio-icon-btn !w-auto px-3 text-xs font-bold"><i class="fa-solid fa-paste mr-1.5"></i>Paste list</button>
          </div>
          <div id="rosterPaste" class="mt-3" hidden>
            <textarea rows="5" class="studio-input w-full font-mono text-xs" placeholder="One player per line: Name, Number, Size&#10;KHAN, 10, M&#10;ALI, 7, L"></textarea>
            <button type="button" data-act="import-roster" class="btn-dark btn-sm w-full mt-2">Import players</button>
          </div>
          <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-xs text-slate-500">
            <p class="font-bold text-ink mb-1">Before you add the team to cart</p>
            Add your email &amp; WhatsApp on the <button type="button" data-tab="finish" class="text-ignite font-bold underline">Finish</button> tab so we can confirm the design with you.
          </div>
          <button type="button" data-act="team-cart" class="btn-primary w-full mt-4" disabled><i class="fa-solid fa-users"></i> Add whole team to cart</button>
        </section>

        <!-- FINISH -->
        <section data-panel="finish" hidden>
          <h3 class="studio-h">Your details</h3>
          <p class="studio-sub">We'll send you a proof of your design before it goes into production.</p>
          <label class="block mb-3"><span class="text-xs font-semibold text-slate-500">Email *</span><input type="email" id="stEmail" autocomplete="email" placeholder="you@example.com" class="studio-input w-full mt-1"></label>
          <label class="block mb-3"><span class="text-xs font-semibold text-slate-500">WhatsApp *</span><input type="tel" id="stWhatsapp" autocomplete="tel" placeholder="+92 3xx xxxxxxx" class="studio-input w-full mt-1"></label>
          <label class="block mb-3"><span class="text-xs font-semibold text-slate-500">Notes for our designers</span><textarea id="stNotes" rows="3" maxlength="500" placeholder="Pantone colours, placement notes, deadline…" class="studio-input w-full mt-1"></textarea></label>
          <label class="block mb-4"><span class="text-xs font-semibold text-slate-500">Quantity</span><input type="number" id="stQty" min="1" value="1" class="studio-input w-24 mt-1 block"></label>
          <ul class="text-xs text-slate-500 space-y-1.5 mb-4">
            <li><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i>Print-ready artwork generated automatically</li>
            <li><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i>Free design proof before production</li>
            <li><i class="fa-solid fa-check text-emerald-500 mr-1.5"></i>Your design is auto-saved on this device</li>
          </ul>
        </section>
      </div>

      <div class="studio-bottom">
        <div class="min-w-0 flex-1">
          <p id="studioPrice" class="font-display font-bold text-xl leading-none"></p>
          <p id="studioSummary" class="text-[11px] text-slate-500 truncate mt-1"></p>
        </div>
        <button type="button" data-act="cart" class="btn-primary btn-sm !py-3"><i class="fa-solid fa-bag-shopping"></i> Add to cart</button>
      </div>
    </aside>
  </div>
</div>
