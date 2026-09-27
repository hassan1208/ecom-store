/*!
 * customizer-studio.js — BuiltCo Sports 3D Design Studio.
 *
 * A full-screen product customizer that renders the shopper's design live on
 * a 3D model (assets/js/garment3d.js) and on the real product photo.
 *
 * Features: kit colour presets, base/sleeve/trim colours, 10 pattern styles,
 * multiple logos (drag, resize, rotate, layer order, duplicate), name with
 * arch, numbers front/back, extra custom text, fonts, fill + outline colours,
 * undo/redo, autosave/restore, PNG download, keyboard nudging, team roster
 * with CSV paste, and one-click "Add to cart" for a single item or a team.
 *
 * On add-to-cart it uploads (via customizer-upload.php):
 *   - photo mockups (front/back)        → preview_path / preview_back_path
 *   - transparent print art (front/back) → design_front_path / design_back_path
 *   - clean 3D renders (front/back)      → render_front_path / render_back_path
 * and posts the full spec as customization_data (or team_data).
 *
 * Page contract: product.php prints window.STUDIO_CONFIG and the studio markup
 * (#studio …) inside #addToCartForm. Variation selects keep class
 * .variation-select and the global variationMap / updateVariation().
 */
(function () {
  'use strict';

  var cfg = window.STUDIO_CONFIG;
  var root = document.getElementById('studio');
  if (!cfg || !root) return;

  var $ = function (sel, ctx) { return (ctx || root).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || root).querySelectorAll(sel)); };
  var form = document.getElementById('addToCartForm');

  var DESIGN = 1024;          // texture / print-art resolution
  var PHOTO = 800;            // photo mockup resolution
  var MODEL = cfg.model || 'jersey';
  var SIDES = ['front', 'back'];

  // ------------------------------------------------------------------
  // State
  // ------------------------------------------------------------------
  var logos = {};             // id → { img, src, vectorStatus, vectorPath, isOriginalVector, vectorPromise, name }
  var uid = 0;
  function nextId(p) { uid += 1; return (p || 'el') + '-' + Date.now().toString(36) + '-' + uid; }

  function defaultState() {
    var d = cfg.defaults || {};
    return {
      colors: {
        base: d.base || '#1d4ed8', sleeve: d.sleeve || '', trim: d.trim || '#ffffff',
        pattern: d.pattern || (MODEL === 'ball' ? 'panels' : 'none'), patternColor: d.patternColor || '#ffffff',
        tintPhoto: false,
      },
      text: { font: cfg.fonts && cfg.fonts[0] ? cfg.fonts[0].value : 'Oswald, sans-serif', fill: '#ffffff', outline: '#0b0f14', outlineWidth: 4 },
      elements: [
        { id: 'name', side: 'back', type: 'text', role: 'name', text: '', x: 0.5, y: 0.27, size: 0.085, rotation: 0, arc: 35 },
        { id: 'number', side: 'back', type: 'text', role: 'number', text: '', x: 0.5, y: 0.52, size: 0.26, rotation: 0, arc: 0 },
        { id: 'frontNumber', side: 'front', type: 'text', role: 'frontNumber', text: '', x: 0.36, y: 0.3, size: 0.11, rotation: 0, arc: 0, hidden: true },
      ],
    };
  }
  var state = defaultState();
  var ui = { side: 'front', view: '3d', selected: null, dirty: false };

  function el(id) { for (var i = 0; i < state.elements.length; i++) if (state.elements[i].id === id) return state.elements[i]; return null; }
  function logoElements(side) { return state.elements.filter(function (e) { return e.type === 'logo' && (!side || e.side === side); }); }

  // ------------------------------------------------------------------
  // Canvases
  // ------------------------------------------------------------------
  function makeCanvas(w, h) { var c = document.createElement('canvas'); c.width = w; c.height = h || w; return c; }
  var designCanvas = { front: makeCanvas(DESIGN), back: makeCanvas(DESIGN) };
  var photoCanvas = { front: $('#studioPhotoFront'), back: $('#studioPhotoBack') };
  var photoBase = { front: null, back: null };
  var photoBaseSrc = { front: '', back: '' };
  var measureCtx = makeCanvas(8).getContext('2d');

  // ------------------------------------------------------------------
  // Patterns
  // ------------------------------------------------------------------
  function seeded(seed) { return function () { seed = (seed * 16807) % 2147483647; return (seed - 1) / 2147483646; }; }

  function drawPattern(ctx, w, h, type, color, base) {
    ctx.save();
    ctx.fillStyle = color; ctx.strokeStyle = color;
    var i;
    switch (type) {
      case 'stripes':
        for (i = 0; i < 12; i++) if (i % 2) ctx.fillRect(i * w / 12, 0, w / 12, h);
        break;
      case 'pinstripes':
        for (i = 1; i < 30; i++) ctx.fillRect(i * w / 30 - w * 0.0025, 0, w * 0.005, h);
        break;
      case 'hoops':
        for (i = 0; i < 12; i++) if (i % 2) ctx.fillRect(0, i * h / 12, w, h / 12);
        break;
      case 'sash':
        ctx.beginPath();
        ctx.moveTo(w * 0.22, 0); ctx.lineTo(w * 0.38, 0); ctx.lineTo(w * 0.86, h); ctx.lineTo(w * 0.7, h);
        ctx.closePath(); ctx.fill();
        break;
      case 'halves':
        ctx.fillRect(w / 2, 0, w / 2, h);
        break;
      case 'gradient':
        var g = ctx.createLinearGradient(0, 0, 0, h);
        g.addColorStop(0, base); g.addColorStop(1, color);
        ctx.fillStyle = g; ctx.fillRect(0, 0, w, h);
        break;
      case 'chevron':
        ctx.beginPath();
        ctx.moveTo(0, h * 0.26); ctx.lineTo(w / 2, h * 0.46); ctx.lineTo(w, h * 0.26);
        ctx.lineTo(w, h * 0.36); ctx.lineTo(w / 2, h * 0.56); ctx.lineTo(0, h * 0.36);
        ctx.closePath(); ctx.fill();
        break;
      case 'halftone':
        for (var y = 0; y < h; y += w / 40) {
          for (var x = (y / (w / 40)) % 2 ? w / 80 : 0; x < w; x += w / 40) {
            var r = (w / 95) * Math.max(0, (y / h) - 0.25) * 1.4;
            if (r > 0.3) { ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2); ctx.fill(); }
          }
        }
        break;
      case 'camo':
        var rnd = seeded(1234);
        ctx.globalAlpha = 0.85;
        for (i = 0; i < 70; i++) {
          var cx = rnd() * w, cy = rnd() * h, rr = w * (0.03 + rnd() * 0.06);
          ctx.beginPath();
          for (var a = 0; a < Math.PI * 2; a += Math.PI / 5) {
            var rad = rr * (0.7 + rnd() * 0.6);
            var px = cx + Math.cos(a) * rad, py = cy + Math.sin(a) * rad * 0.8;
            a === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py);
          }
          ctx.closePath(); ctx.fill();
        }
        break;
      case 'panels':
        // Classic truncated-icosahedron look: hex outlines + filled pentagon spots.
        var s = w / 9, hh = s * Math.sqrt(3) / 2;
        ctx.lineWidth = w * 0.006;
        for (var row = -1; row * hh * 2 < h + s; row++) {
          for (var col = -1; col * s * 1.5 < w + s; col++) {
            var hx = col * s * 1.5, hy = row * hh * 2 + (col % 2 ? hh : 0);
            ctx.beginPath();
            for (i = 0; i < 6; i++) {
              var ang = Math.PI / 3 * i;
              var vx = hx + Math.cos(ang) * s * 0.98, vy = hy + Math.sin(ang) * s * 0.98;
              i ? ctx.lineTo(vx, vy) : ctx.moveTo(vx, vy);
            }
            ctx.closePath();
            if ((row + col * 2) % 3 === 0) ctx.fill(); else ctx.stroke();
          }
        }
        break;
    }
    ctx.restore();
  }

  // ------------------------------------------------------------------
  // Elements (logos + text)
  // ------------------------------------------------------------------
  function textFont(size) { return '900 ' + Math.round(size) + 'px ' + state.text.font; }

  function drawTextElement(ctx, e, w, h) {
    if (!e.text || e.hidden) return;
    var size = e.size * w;
    ctx.save();
    ctx.translate(e.x * w, e.y * h);
    ctx.rotate((e.rotation || 0) * Math.PI / 180);
    ctx.font = textFont(size);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.lineJoin = 'round';
    var outlineW = (state.text.outlineWidth || 0) * size / 60;
    function paintGlyphs(fn) {
      if (!e.arc) { fn(e.text, 0, 0); return; }
      // Arched text: lay each glyph along a circle whose curvature follows e.arc.
      var total = ctx.measureText(e.text).width;
      var angle = (e.arc / 100) * 1.4;
      var R = total / angle;
      var start = -angle / 2;
      ctx.save();
      ctx.translate(0, R);
      var acc = 0;
      for (var i = 0; i < e.text.length; i++) {
        var ch = e.text[i], cw = ctx.measureText(ch).width;
        var a = start + ((acc + cw / 2) / total) * angle;
        ctx.save();
        ctx.rotate(a);
        fn(ch, 0, -R);
        ctx.restore();
        acc += cw;
      }
      ctx.restore();
    }
    if (outlineW > 0) {
      ctx.lineWidth = outlineW * 2;
      ctx.strokeStyle = state.text.outline;
      paintGlyphs(function (t, x, y) { ctx.strokeText(t, x, y); });
    }
    ctx.fillStyle = state.text.fill;
    paintGlyphs(function (t, x, y) { ctx.fillText(t, x, y); });
    ctx.restore();
  }

  function drawLogoElement(ctx, e, w, h) {
    var L = logos[e.logoId];
    if (!L || !L.img || !L.img.width) return;
    var lw = e.size * w, lh = lw * (L.img.height / L.img.width);
    ctx.save();
    ctx.translate(e.x * w, e.y * h);
    ctx.rotate((e.rotation || 0) * Math.PI / 180);
    ctx.drawImage(L.img, -lw / 2, -lh / 2, lw, lh);
    ctx.restore();
  }

  function drawElements(ctx, side, w, h) {
    state.elements.forEach(function (e) {
      if (e.side !== side) return;
      if (e.type === 'logo') drawLogoElement(ctx, e, w, h);
      else drawTextElement(ctx, e, w, h);
    });
  }

  // Bounding box in normalized design space (ignores rotation; good enough to grab).
  function bounds(e) {
    if (e.type === 'logo') {
      var L = logos[e.logoId];
      var ar = L && L.img && L.img.width ? L.img.height / L.img.width : 1;
      return { x0: e.x - e.size / 2, x1: e.x + e.size / 2, y0: e.y - e.size * ar / 2, y1: e.y + e.size * ar / 2 };
    }
    if (!e.text || e.hidden) return null;
    measureCtx.font = textFont(e.size * 1000);
    var tw = measureCtx.measureText(e.text).width / 1000;
    var th = e.size * (e.arc ? 1.3 : 0.9);
    return { x0: e.x - tw / 2, x1: e.x + tw / 2, y0: e.y - th / 2, y1: e.y + th / 2 };
  }

  function hitTest(side, nx, ny) {
    for (var i = state.elements.length - 1; i >= 0; i--) {
      var e = state.elements[i];
      if (e.side !== side) continue;
      var b = bounds(e);
      var pad = 0.015;
      if (b && nx >= b.x0 - pad && nx <= b.x1 + pad && ny >= b.y0 - pad && ny <= b.y1 + pad) return e;
    }
    return null;
  }

  // ------------------------------------------------------------------
  // Painting
  // ------------------------------------------------------------------
  function paintDesign(side, target) {
    var c = target || designCanvas[side];
    var ctx = c.getContext('2d'), w = c.width, h = c.height;
    var col = state.colors;
    ctx.clearRect(0, 0, w, h);
    ctx.save();
    if (MODEL === 'jersey') {
      window.Garment3D.traceShirtPath(ctx, w, h);
      ctx.clip();
    }
    ctx.fillStyle = col.base;
    ctx.fillRect(0, 0, w, h);
    if (col.pattern && col.pattern !== 'none') drawPattern(ctx, w, h, col.pattern, col.patternColor, col.base);
    if (MODEL === 'jersey' && col.sleeve) {
      ctx.fillStyle = col.sleeve;
      window.Garment3D.tracePoly(ctx, window.Garment3D.SHIRT.leftSleeve, w, h); ctx.fill();
      window.Garment3D.tracePoly(ctx, window.Garment3D.SHIRT.rightSleeve, w, h); ctx.fill();
    }
    drawElements(ctx, side, w, h);
    ctx.restore();
    return c;
  }

  function fitCover(img, w, h) {
    var ir = img.width / img.height, cr = w / h, sw, sh, sx, sy;
    if (ir > cr) { sh = img.height; sw = sh * cr; sx = (img.width - sw) / 2; sy = 0; }
    else { sw = img.width; sh = sw / cr; sx = 0; sy = (img.height - sh) / 2; }
    return { sx: sx, sy: sy, sw: sw, sh: sh };
  }

  function paintPhoto(side, target, withSelection) {
    var c = target || photoCanvas[side];
    if (!c) return null;
    var ctx = c.getContext('2d'), w = c.width, h = c.height;
    ctx.clearRect(0, 0, w, h);
    var img = photoBase[side];
    if (img) {
      var f = fitCover(img, w, h);
      ctx.drawImage(img, f.sx, f.sy, f.sw, f.sh, 0, 0, w, h);
    } else {
      ctx.fillStyle = '#f1f5f9'; ctx.fillRect(0, 0, w, h);
    }
    if (state.colors.tintPhoto) {
      ctx.save();
      ctx.globalCompositeOperation = 'hue';
      ctx.fillStyle = state.colors.base;
      ctx.fillRect(0, 0, w, h);
      ctx.restore();
    }
    drawElements(ctx, side, w, h);
    if (withSelection && ui.selected) {
      var e = el(ui.selected);
      var b = e && e.side === side ? bounds(e) : null;
      if (b) {
        ctx.save();
        ctx.setLineDash([12, 8]);
        ctx.lineWidth = 3;
        ctx.strokeStyle = cfg.brandColor || '#ff4d2e';
        ctx.strokeRect(b.x0 * w - 6, b.y0 * h - 6, (b.x1 - b.x0) * w + 12, (b.y1 - b.y0) * h + 12);
        ctx.restore();
      }
    }
    return c;
  }

  var viewer = null;
  var raf = 0;
  function render() {
    if (raf) return;
    raf = requestAnimationFrame(function () {
      raf = 0;
      SIDES.forEach(function (s) {
        if (MODEL === 'flat') paintPhoto(s, designCanvas[s], false);
        else paintDesign(s);
        paintPhoto(s, null, true);
      });
      if (viewer) {
        viewer.setColors({ base: state.colors.base, collar: state.colors.trim, trim: state.colors.trim });
        viewer.refresh();
      }
      syncSummary();
    });
  }

  // ------------------------------------------------------------------
  // Photo base images (match the chosen colour variation + side)
  // ------------------------------------------------------------------
  function colorSelect() {
    return Array.prototype.slice.call(document.querySelectorAll('.variation-select')).filter(function (s) {
      return (s.dataset.type || '').toLowerCase() === 'color' || (s.dataset.type || '').toLowerCase() === 'colour';
    })[0];
  }
  function sizeSelect() {
    return Array.prototype.slice.call(document.querySelectorAll('.variation-select')).filter(function (s) {
      return (s.dataset.type || '').toLowerCase() === 'size';
    })[0];
  }
  function resolveImage(view, color) {
    var imgs = cfg.images || [];
    var m = imgs.filter(function (im) { return im.view === view && color && im.color === color; })[0]
      || imgs.filter(function (im) { return im.view === view; })[0]
      || (color && imgs.filter(function (im) { return im.color === color; })[0]);
    return m ? m.url : cfg.defaultImage;
  }
  function loadPhotoBases() {
    var csel = colorSelect();
    SIDES.forEach(function (side) {
      var src = resolveImage(side, csel ? csel.value : '');
      if (!src) { photoBase[side] = null; render(); return; }
      if (src === photoBaseSrc[side] && photoBase[side]) return;
      photoBaseSrc[side] = src;
      var img = new Image();
      img.onload = function () { photoBase[side] = img; render(); };
      img.onerror = function () { photoBase[side] = null; render(); };
      img.src = src;
    });
  }

  // ------------------------------------------------------------------
  // History (undo / redo) + autosave
  // ------------------------------------------------------------------
  var history = [], future = [], histTimer = 0, lastSnap = '';
  function serialize() { return JSON.stringify({ colors: state.colors, text: state.text, elements: state.elements }); }
  function commit(immediate) {
    ui.dirty = true;
    clearTimeout(histTimer);
    var run = function () {
      var snap = serialize();
      if (snap === lastSnap) return;
      if (lastSnap) history.push(lastSnap);
      if (history.length > 60) history.shift();
      lastSnap = snap; future = [];
      updateHistoryButtons();
      autosave();
    };
    immediate ? run() : (histTimer = setTimeout(run, 350));
  }
  function applySnap(snap) {
    var s = JSON.parse(snap);
    state.colors = s.colors; state.text = s.text; state.elements = s.elements;
    lastSnap = snap;
    if (ui.selected && !el(ui.selected)) ui.selected = null;
    syncControls(); render(); updateHistoryButtons(); autosave();
  }
  function undo() { if (!history.length) return; future.push(lastSnap); applySnap(history.pop()); }
  function redo() { if (!future.length) return; history.push(lastSnap); applySnap(future.pop()); }
  function updateHistoryButtons() {
    var u = $('[data-act="undo"]'), r = $('[data-act="redo"]');
    if (u) u.disabled = !history.length;
    if (r) r.disabled = !future.length;
  }

  var storeKey = 'builtco-design-' + cfg.productId;
  function autosave() {
    try {
      var logoSrc = {};
      logoElements().forEach(function (e) { if (logos[e.logoId]) logoSrc[e.logoId] = logos[e.logoId].src; });
      localStorage.setItem(storeKey, JSON.stringify({ v: 1, t: Date.now(), state: JSON.parse(serialize()), logos: logoSrc }));
    } catch (err) {
      // Quota exceeded (large logos) — keep everything except the images.
      try { localStorage.setItem(storeKey, JSON.stringify({ v: 1, t: Date.now(), state: JSON.parse(serialize()), logos: {} })); } catch (e2) {}
    }
  }
  function restoreSaved() {
    var raw;
    try { raw = JSON.parse(localStorage.getItem(storeKey) || 'null'); } catch (e) { raw = null; }
    if (!raw || !raw.state) return false;
    var pending = 0;
    Object.keys(raw.logos || {}).forEach(function (id) {
      pending++;
      var img = new Image();
      img.onload = function () { logos[id] = { img: img, src: raw.logos[id], vectorStatus: 'failed', vectorPath: null, isOriginalVector: false }; if (--pending === 0) render(); };
      img.onerror = function () { if (--pending === 0) render(); };
      img.src = raw.logos[id];
      // Re-upload for vectorization so the restored logo still reaches production.
      fetch(raw.logos[id]).then(function (r) { return r.blob(); }).then(function (b) { vectorizeLogo(new File([b], 'logo.png', { type: b.type || 'image/png' }), id); }).catch(function () {});
    });
    state.colors = raw.state.colors || state.colors;
    state.text = raw.state.text || state.text;
    state.elements = (raw.state.elements || state.elements).filter(function (e) { return e.type !== 'logo' || (raw.logos && raw.logos[e.logoId]); });
    lastSnap = serialize(); history = []; future = [];
    ui.dirty = true;
    syncControls(); render(); updateHistoryButtons();
    return true;
  }

  // ------------------------------------------------------------------
  // Logos
  // ------------------------------------------------------------------
  function vectorizeLogo(file, logoId) {
    var L = logos[logoId];
    if (!L) return;
    L.vectorStatus = 'pending';
    var body = new FormData();
    body.set('csrf_token', cfg.csrf);
    body.set('logo', file);
    L.vectorPromise = fetch(cfg.vectorizeUrl, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        L.vectorStatus = res.success && res.vector_path ? 'ready' : 'failed';
        L.vectorPath = res.vector_path || null;
        L.isOriginalVector = !!res.is_original_vector;
        renderLayers();
      })
      .catch(function () { L.vectorStatus = 'failed'; renderLayers(); });
  }

  function addLogoFromFile(file) {
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) { toast('That file is over 10MB — please use a smaller logo.'); return; }
    if (logoElements().length >= (cfg.maxLogos || 8)) { toast('You can add up to ' + (cfg.maxLogos || 8) + ' logos.'); return; }
    var reader = new FileReader();
    reader.onload = function (ev) {
      var img = new Image();
      img.onload = function () {
        var id = nextId('logo');
        logos[id] = { img: img, src: ev.target.result, name: file.name, vectorStatus: 'pending', vectorPath: null, isOriginalVector: false };
        var onSide = logoElements(ui.side).length;
        var e = { id: nextId('el'), side: ui.side, type: 'logo', logoId: id, x: ui.side === 'front' ? (onSide ? 0.62 : 0.5) : 0.5, y: ui.side === 'front' ? 0.27 + onSide * 0.06 : 0.16 + onSide * 0.05, size: onSide ? 0.1 : 0.16, rotation: 0 };
        state.elements.push(e);
        select(e.id);
        renderLayers(); render(); commit(true);
        vectorizeLogo(file, id);
      };
      img.onerror = function () { toast('Sorry, that image could not be read.'); };
      img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  }

  // ------------------------------------------------------------------
  // Selection + element controls
  // ------------------------------------------------------------------
  function select(id) {
    ui.selected = id;
    var e = id ? el(id) : null;
    var box = $('#studioSelection');
    if (box) {
      box.hidden = !e;
      if (e) {
        $('#selLabel').textContent = e.type === 'logo' ? 'Logo' : e.role === 'name' ? 'Name' : e.role === 'number' ? 'Back number' : e.role === 'frontNumber' ? 'Front number' : 'Text';
        $('#selSize').value = Math.round(e.size * 1000);
        $('#selRotate').value = e.rotation || 0;
        $('#selRotateVal').textContent = (e.rotation || 0) + '°';
        $('[data-sel="delete"]').hidden = e.type !== 'logo' && e.role !== 'custom';
        $('[data-sel="duplicate"]').hidden = e.type !== 'logo';
      }
    }
    if (e && e.side !== ui.side) setSide(e.side);
    renderLayers();
    render();
  }

  function moveSelected(dx, dy) {
    var e = el(ui.selected);
    if (!e) return;
    e.x = Math.min(0.98, Math.max(0.02, e.x + dx));
    e.y = Math.min(0.98, Math.max(0.02, e.y + dy));
    render(); commit();
  }

  function renderLayers() {
    var list = $('#logoLayers');
    if (!list) return;
    list.innerHTML = '';
    var items = logoElements();
    $('#logoEmpty').hidden = items.length > 0;
    items.forEach(function (e) {
      var L = logos[e.logoId] || {};
      var status = L.vectorStatus === 'pending'
        ? '<span class="studio-pill" title="Preparing a print-ready vector…"><i class="fa-solid fa-spinner fa-spin"></i> Vector</span>'
        : L.vectorStatus === 'ready'
          ? '<span class="studio-pill is-ok" title="' + (L.isOriginalVector ? 'Your vector file' : 'Auto-vectorized for print') + '"><i class="fa-solid fa-circle-check"></i> Print-ready</span>'
          : '<span class="studio-pill is-warn" title="We\'ll vectorize this by hand before printing"><i class="fa-solid fa-image"></i> Raster</span>';
      var row = document.createElement('div');
      row.className = 'studio-layer' + (ui.selected === e.id ? ' is-active' : '');
      row.innerHTML = '<img alt="" src="' + (L.src || '') + '">' +
        '<div class="min-w-0 flex-1"><div class="studio-layer-title">' + (e.side === 'front' ? 'Front' : 'Back') + ' logo</div>' + status + '</div>' +
        '<button type="button" data-l="up" title="Bring forward" aria-label="Bring forward"><i class="fa-solid fa-arrow-up"></i></button>' +
        '<button type="button" data-l="down" title="Send backward" aria-label="Send backward"><i class="fa-solid fa-arrow-down"></i></button>' +
        '<button type="button" data-l="side" title="Move to other side" aria-label="Move to other side"><i class="fa-solid fa-right-left"></i></button>' +
        '<button type="button" data-l="del" title="Remove" aria-label="Remove logo" class="is-danger"><i class="fa-solid fa-trash"></i></button>';
      row.addEventListener('click', function (ev) {
        var act = ev.target.closest('button') && ev.target.closest('button').dataset.l;
        var idx = state.elements.indexOf(e);
        if (act === 'up' && idx < state.elements.length - 1) { state.elements.splice(idx, 1); state.elements.splice(idx + 1, 0, e); }
        else if (act === 'down' && idx > 0) { state.elements.splice(idx, 1); state.elements.splice(idx - 1, 0, e); }
        else if (act === 'side') { e.side = e.side === 'front' ? 'back' : 'front'; setSide(e.side); }
        else if (act === 'del') { state.elements.splice(idx, 1); if (ui.selected === e.id) ui.selected = null; select(ui.selected); commit(true); return; }
        select(e.id); commit(true);
      });
      list.appendChild(row);
    });
  }

  // ------------------------------------------------------------------
  // UI: view, side, tabs
  // ------------------------------------------------------------------
  function setSide(side) {
    ui.side = side;
    $$('[data-side]').forEach(function (b) { b.classList.toggle('is-active', b.dataset.side === side); b.setAttribute('aria-pressed', b.dataset.side === side); });
    photoCanvas.front && photoCanvas.front.classList.toggle('hidden', side !== 'front');
    photoCanvas.back && photoCanvas.back.classList.toggle('hidden', side !== 'back');
    if (viewer) viewer.setView(side);
  }

  function setView(view) {
    if (view === '3d-pending') { $('#stage3d').classList.remove('hidden'); $('#stagePhoto').classList.add('hidden'); return; }
    if (view === '3d' && !viewer) view = 'photo';
    ui.view = view;
    $$('[data-view]').forEach(function (b) { b.classList.toggle('is-active', b.dataset.view === view); b.setAttribute('aria-pressed', b.dataset.view === view); });
    $('#stage3d').classList.toggle('hidden', view !== '3d');
    $('#stagePhoto').classList.toggle('hidden', view !== 'photo');
    $$('.studio-3d-only').forEach(function (n) { n.classList.toggle('hidden', view !== '3d'); });
    $('#stageHint').textContent = view === '3d'
      ? 'Drag to spin · drag a logo or text on the model to move it · pinch / Ctrl+scroll to zoom'
      : 'Drag logos and text on the photo to position them';
  }

  function setTab(tab) {
    $$('[data-tab]').forEach(function (b) { var on = b.dataset.tab === tab; b.classList.toggle('is-active', on); b.setAttribute('aria-selected', on); });
    $$('[data-panel]').forEach(function (p) { p.hidden = p.dataset.panel !== tab; });
  }

  // ------------------------------------------------------------------
  // Controls → state
  // ------------------------------------------------------------------
  function syncControls() {
    var c = state.colors, t = state.text;
    $('#stBase').value = c.base;
    $('#stSleeve').value = c.sleeve || c.base;
    $('#stSleeveOn').checked = !!c.sleeve;
    $('#stTrim').value = c.trim;
    $('#stPatternColor').value = c.patternColor;
    $('#stTintPhoto').checked = !!c.tintPhoto;
    $$('[data-pattern]').forEach(function (b) { b.classList.toggle('is-active', b.dataset.pattern === c.pattern); });
    $$('[data-font]').forEach(function (b) { b.classList.toggle('is-active', b.dataset.font === t.font); });
    $('#stFill').value = t.fill;
    $('#stOutline').value = t.outline;
    $('#stOutlineWidth').value = t.outlineWidth;
    var name = el('name'), num = el('number'), fnum = el('frontNumber');
    $('#stName').value = name.text;
    $('#stNameSize').value = Math.round(name.size * 1000);
    $('#stNameArc').value = name.arc;
    $('#stNumber').value = num.text;
    $('#stNumberSize').value = Math.round(num.size * 1000);
    $('#stFrontNumberOn').checked = !fnum.hidden;
    $('#stFrontNumberWrap').hidden = !!fnum.hidden;
    $('#stFrontNumberSize').value = Math.round(fnum.size * 1000);
    renderCustomTexts();
    renderLayers();
  }

  function bindColor(id, fn) {
    var input = $(id);
    if (input) input.addEventListener('input', function () { fn(input.value); render(); commit(); });
  }
  bindColor('#stBase', function (v) { state.colors.base = v; if (!$('#stSleeveOn').checked) $('#stSleeve').value = v; });
  bindColor('#stSleeve', function (v) { state.colors.sleeve = v; $('#stSleeveOn').checked = true; });
  bindColor('#stTrim', function (v) { state.colors.trim = v; });
  bindColor('#stPatternColor', function (v) { state.colors.patternColor = v; });
  bindColor('#stFill', function (v) { state.text.fill = v; });
  bindColor('#stOutline', function (v) { state.text.outline = v; });
  $('#stSleeveOn').addEventListener('change', function () { state.colors.sleeve = this.checked ? $('#stSleeve').value : ''; render(); commit(); });
  $('#stTintPhoto').addEventListener('change', function () { state.colors.tintPhoto = this.checked; render(); commit(); });
  $('#stOutlineWidth').addEventListener('input', function () { state.text.outlineWidth = +this.value; render(); commit(); });

  // Swatch buttons set the colour input next to them.
  $$('[data-swatch-for]').forEach(function (b) {
    b.addEventListener('click', function () {
      var input = $('#' + b.dataset.swatchFor);
      input.value = b.dataset.color;
      input.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });

  $$('[data-pattern]').forEach(function (b) {
    b.addEventListener('click', function () {
      state.colors.pattern = b.dataset.pattern;
      $$('[data-pattern]').forEach(function (x) { x.classList.toggle('is-active', x === b); });
      render(); commit(true);
    });
  });

  $$('[data-preset]').forEach(function (b) {
    b.addEventListener('click', function () {
      var p = (cfg.presets || [])[+b.dataset.preset];
      if (!p) return;
      state.colors.base = p.base; state.colors.sleeve = p.sleeve || ''; state.colors.trim = p.trim;
      state.colors.pattern = p.pattern || 'none'; state.colors.patternColor = p.patternColor || p.trim;
      state.text.fill = p.text || '#ffffff'; state.text.outline = p.outline || p.base;
      syncControls(); render(); commit(true);
    });
  });

  $$('[data-font]').forEach(function (b) {
    b.addEventListener('click', function () {
      state.text.font = b.dataset.font;
      $$('[data-font]').forEach(function (x) { x.classList.toggle('is-active', x === b); });
      var spec = '900 100px ' + state.text.font;
      if (document.fonts && document.fonts.load) document.fonts.load(spec).then(render, render); else render();
      commit(true);
    });
  });

  function bindText(id, elId, prop, transform) {
    var input = $(id);
    input.addEventListener('input', function () {
      var e = el(elId);
      e[prop] = transform ? transform(input.value) : input.value;
      if (prop === 'text' && elId === 'frontNumber') e.hidden = !$('#stFrontNumberOn').checked;
      render(); commit();
    });
    input.addEventListener('focus', function () { if (prop === 'text') { var e = el(elId); if (e.side !== ui.side) setSide(e.side); } });
  }
  var upper = function (v) { return v.toUpperCase(); };
  var digits = function (v) { return v.replace(/[^0-9]/g, '').slice(0, 3); };
  bindText('#stName', 'name', 'text', upper);
  bindText('#stNameSize', 'name', 'size', function (v) { return v / 1000; });
  bindText('#stNameArc', 'name', 'arc', function (v) { return +v; });
  bindText('#stNumber', 'number', 'text', digits);
  bindText('#stNumberSize', 'number', 'size', function (v) { return v / 1000; });
  bindText('#stFrontNumberSize', 'frontNumber', 'size', function (v) { return v / 1000; });
  $('#stNumber').addEventListener('input', function () {
    this.value = digits(this.value);
    var f = el('frontNumber'); f.text = this.value; render();
  });
  $('#stName').addEventListener('input', function () { var p = this.selectionStart; this.value = this.value.toUpperCase(); this.setSelectionRange(p, p); });
  $('#stFrontNumberOn').addEventListener('change', function () {
    var f = el('frontNumber');
    f.hidden = !this.checked; f.text = el('number').text;
    $('#stFrontNumberWrap').hidden = !this.checked;
    if (this.checked) setSide('front');
    render(); commit(true);
  });

  // Extra custom text lines (club name, sponsor, motto…)
  function renderCustomTexts() {
    var wrap = $('#customTexts');
    wrap.innerHTML = '';
    state.elements.filter(function (e) { return e.role === 'custom'; }).forEach(function (e) {
      var row = document.createElement('div');
      row.className = 'studio-field-row';
      row.innerHTML = '<input type="text" maxlength="24" class="studio-input flex-1" placeholder="Your text">' +
        '<select class="studio-input w-24" aria-label="Side"><option value="front">Front</option><option value="back">Back</option></select>' +
        '<button type="button" class="studio-icon-btn is-danger" aria-label="Remove text"><i class="fa-solid fa-xmark"></i></button>';
      var input = row.querySelector('input'), side = row.querySelector('select');
      input.value = e.text; side.value = e.side;
      input.addEventListener('input', function () { e.text = input.value; render(); commit(); });
      input.addEventListener('focus', function () { select(e.id); });
      side.addEventListener('change', function () { e.side = side.value; setSide(e.side); render(); commit(true); });
      row.querySelector('button').addEventListener('click', function () {
        state.elements.splice(state.elements.indexOf(e), 1);
        if (ui.selected === e.id) select(null);
        renderCustomTexts(); render(); commit(true);
      });
      wrap.appendChild(row);
    });
  }
  $('[data-act="add-text"]').addEventListener('click', function () {
    var e = { id: nextId('txt'), side: ui.side, type: 'text', role: 'custom', text: 'YOUR TEXT', x: 0.5, y: ui.side === 'front' ? 0.6 : 0.8, size: 0.06, rotation: 0, arc: 0 };
    state.elements.push(e);
    renderCustomTexts(); select(e.id); commit(true);
    var inputs = $$('#customTexts input[type=text]');
    if (inputs.length) { inputs[inputs.length - 1].focus(); inputs[inputs.length - 1].select(); }
  });

  // Logo upload (button, drag & drop onto the stage)
  var logoInput = $('#stLogoInput');
  $('[data-act="add-logo"]').addEventListener('click', function () { logoInput.click(); });
  logoInput.addEventListener('change', function () { Array.prototype.slice.call(logoInput.files || []).forEach(addLogoFromFile); logoInput.value = ''; });
  var stage = $('#studioStage');
  ['dragenter', 'dragover'].forEach(function (t) { stage.addEventListener(t, function (e) { e.preventDefault(); stage.classList.add('is-drop'); }); });
  ['dragleave', 'drop'].forEach(function (t) { stage.addEventListener(t, function (e) { e.preventDefault(); stage.classList.remove('is-drop'); }); });
  stage.addEventListener('drop', function (e) {
    var files = e.dataTransfer && e.dataTransfer.files;
    if (files && files.length) { setTab('logos'); Array.prototype.slice.call(files).forEach(addLogoFromFile); }
  });

  // Selected element controls
  $('#selSize').addEventListener('input', function () { var e = el(ui.selected); if (!e) return; e.size = this.value / 1000; render(); commit(); });
  $('#selRotate').addEventListener('input', function () { var e = el(ui.selected); if (!e) return; e.rotation = +this.value; $('#selRotateVal').textContent = this.value + '°'; render(); commit(); });
  $('[data-sel="center"]').addEventListener('click', function () { var e = el(ui.selected); if (!e) return; e.x = 0.5; render(); commit(true); });
  $('[data-sel="duplicate"]').addEventListener('click', function () {
    var e = el(ui.selected); if (!e || e.type !== 'logo') return;
    var copy = JSON.parse(JSON.stringify(e)); copy.id = nextId('el'); copy.x = Math.min(0.9, e.x + 0.08); copy.y = Math.min(0.9, e.y + 0.05);
    state.elements.push(copy); select(copy.id); commit(true);
  });
  $('[data-sel="delete"]').addEventListener('click', function () {
    var e = el(ui.selected); if (!e) return;
    state.elements.splice(state.elements.indexOf(e), 1);
    select(null); renderCustomTexts(); commit(true);
  });
  $('[data-sel="close"]').addEventListener('click', function () { select(null); });

  // ------------------------------------------------------------------
  // Pointer interaction on the photo canvases
  // ------------------------------------------------------------------
  var drag = null;
  function canvasPoint(canvas, e) {
    var r = canvas.getBoundingClientRect();
    return { x: (e.clientX - r.left) / r.width, y: (e.clientY - r.top) / r.height };
  }
  SIDES.forEach(function (side) {
    var c = photoCanvas[side];
    if (!c) return;
    c.addEventListener('pointerdown', function (e) {
      var p = canvasPoint(c, e);
      var hit = hitTest(side, p.x, p.y);
      if (!hit) { select(null); return; }
      drag = { el: hit, dx: hit.x - p.x, dy: hit.y - p.y, canvas: c };
      select(hit.id);
      c.setPointerCapture && c.setPointerCapture(e.pointerId);
      e.preventDefault();
    });
    c.addEventListener('pointermove', function (e) {
      var p = canvasPoint(c, e);
      if (!drag) { c.style.cursor = hitTest(side, p.x, p.y) ? 'move' : 'default'; return; }
      drag.el.x = Math.min(0.98, Math.max(0.02, p.x + drag.dx));
      drag.el.y = Math.min(0.98, Math.max(0.02, p.y + drag.dy));
      render();
    });
    var end = function () { if (drag) { drag = null; commit(); } };
    c.addEventListener('pointerup', end);
    c.addEventListener('pointercancel', end);
  });

  // ------------------------------------------------------------------
  // 3D viewer (lazy — three.js is only downloaded when the studio opens)
  // ------------------------------------------------------------------
  var threeLoading = null;
  function loadThree() {
    if (window.THREE && window.Garment3D) return Promise.resolve();
    if (threeLoading) return threeLoading;
    threeLoading = new Promise(function (resolve, reject) {
      function add(src, cb) { var s = document.createElement('script'); s.src = src; s.onload = cb; s.onerror = reject; document.head.appendChild(s); }
      var next = function () { window.Garment3D ? resolve() : add(cfg.garmentUrl, resolve); };
      window.THREE ? next() : add(cfg.threeUrl, next);
    });
    return threeLoading;
  }

  var drag3d = null;
  function initViewer() {
    if (viewer || !window.Garment3D || !window.Garment3D.supported()) return;
    render();
    viewer = window.Garment3D.create($('#stage3d'), {
      model: MODEL, front: designCanvas.front, back: designCanvas.back,
      baseColor: state.colors.base, collarColor: state.colors.trim, trimColor: state.colors.trim,
      autoRotate: false, wheelZoom: false, label: '3D preview of your custom ' + (cfg.productName || 'design'),
      onPick: function (side, x, y) {
        var nx = x / designCanvas.front.width, ny = y / designCanvas.front.height;
        var hit = hitTest(side, nx, ny);
        if (!hit) return false;
        drag3d = { el: hit, dx: hit.x - nx, dy: hit.y - ny };
        ui.selected = hit.id; select(hit.id);
        return true;
      },
      onDrag: function (side, x, y) {
        if (!drag3d || side !== drag3d.el.side) return;
        drag3d.el.x = Math.min(0.98, Math.max(0.02, x / designCanvas.front.width + drag3d.dx));
        drag3d.el.y = Math.min(0.98, Math.max(0.02, y / designCanvas.front.height + drag3d.dy));
        render();
      },
      onDragEnd: function () { drag3d = null; commit(); },
      onHover: function (side, x, y) { return !!hitTest(side, x / designCanvas.front.width, y / designCanvas.front.height); },
    });
    if (viewer) {
      var loader = $('#stage3d .studio-loading');
      if (loader) loader.remove();
      setView(MODEL === 'flat' ? 'photo' : '3d');
      viewer.setView(ui.side);
    }
  }

  // ------------------------------------------------------------------
  // Toolbar actions
  // ------------------------------------------------------------------
  $$('[data-view]').forEach(function (b) { b.addEventListener('click', function () { setView(b.dataset.view); }); });
  $$('[data-side]').forEach(function (b) { b.addEventListener('click', function () { setSide(b.dataset.side); }); });
  $$('[data-tab]').forEach(function (b) { b.addEventListener('click', function () { setTab(b.dataset.tab); }); });

  root.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act]');
    if (!b) return;
    var act = b.dataset.act;
    if (act === 'undo') undo();
    else if (act === 'redo') redo();
    else if (act === 'close') close();
    else if (act === 'zoom-in' && viewer) viewer.zoomBy(1.15);
    else if (act === 'zoom-out' && viewer) viewer.zoomBy(0.87);
    else if (act === 'spin' && viewer) { viewer.setAutoRotate(!viewer.isAutoRotating()); b.classList.toggle('is-active', viewer.isAutoRotating()); }
    else if (act === 'reset-view' && viewer) viewer.resetView();
    else if (act === 'download') downloadPreview();
    else if (act === 'restore') { restoreSaved(); $('#restoreBanner').hidden = true; }
    else if (act === 'dismiss-restore') { $('#restoreBanner').hidden = true; }
    else if (act === 'reset') {
      if (!confirm('Start over? This clears your colours, logos and text.')) return;
      state = defaultState(); logos = {}; ui.selected = null;
      syncControls(); render(); commit(true);
      try { localStorage.removeItem(storeKey); } catch (err) {}
    }
  });

  function downloadPreview() {
    var url = ui.view === '3d' && viewer ? viewer.snapshot() : paintPhoto(ui.side, makeCanvas(PHOTO), false).toDataURL('image/png');
    var a = document.createElement('a');
    a.href = url;
    a.download = (cfg.productSlug || 'my-design') + '-' + ui.side + '.png';
    document.body.appendChild(a); a.click(); a.remove();
  }

  document.addEventListener('keydown', function (e) {
    if (root.hidden) return;
    var typing = /INPUT|TEXTAREA|SELECT/.test((e.target && e.target.tagName) || '');
    if (e.key === 'Escape') { close(); return; }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !typing) { e.preventDefault(); e.shiftKey ? redo() : undo(); return; }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y' && !typing) { e.preventDefault(); redo(); return; }
    if (typing || !ui.selected) return;
    var step = e.shiftKey ? 0.02 : 0.005;
    if (e.key === 'ArrowLeft') { e.preventDefault(); moveSelected(-step, 0); }
    else if (e.key === 'ArrowRight') { e.preventDefault(); moveSelected(step, 0); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); moveSelected(0, -step); }
    else if (e.key === 'ArrowDown') { e.preventDefault(); moveSelected(0, step); }
    else if ((e.key === 'Delete' || e.key === 'Backspace')) {
      var sel = el(ui.selected);
      if (sel && (sel.type === 'logo' || sel.role === 'custom')) { e.preventDefault(); $('[data-sel="delete"]').click(); }
    }
  });

  var toastTimer = 0;
  function toast(msg) {
    var t = $('#studioToast');
    t.textContent = msg; t.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.hidden = true; }, 3200);
  }

  // ------------------------------------------------------------------
  // Summary / price in the bottom bar
  // ------------------------------------------------------------------
  function syncSummary() {
    var chips = [];
    var name = el('name'), num = el('number');
    if (logoElements().length) chips.push(logoElements().length + ' logo' + (logoElements().length > 1 ? 's' : ''));
    if (name.text) chips.push('“' + name.text + '”');
    if (num.text) chips.push('#' + num.text);
    if (state.colors.pattern && state.colors.pattern !== 'none') chips.push(state.colors.pattern);
    $('#studioSummary').textContent = chips.length ? chips.join(' · ') : 'Start by choosing colours, then add your logo, name & number.';
    var price = document.getElementById('priceDisplay');
    if (price) $('#studioPrice').textContent = price.textContent;
  }

  // ------------------------------------------------------------------
  // Open / close
  // ------------------------------------------------------------------
  var lastFocus = null;
  function open(tab) {
    lastFocus = document.activeElement;
    root.hidden = false;
    document.documentElement.classList.add('studio-open');
    if (tab) setTab(tab);
    loadPhotoBases();
    render();
    if (cfg.threeUrl) {
      if (!viewer) setView(MODEL === 'flat' ? 'photo' : '3d-pending');
      loadThree().then(initViewer).then(function () { if (!viewer) setView('photo'); }).catch(function () { setView('photo'); });
    } else {
      setView('photo');
    }
    try {
      var saved = JSON.parse(localStorage.getItem(storeKey) || 'null');
      $('#restoreBanner').hidden = !(saved && saved.state && !ui.dirty);
    } catch (e) {}
    setTimeout(function () { var f = $('.studio-tab.is-active'); f && f.focus(); }, 50);
  }
  function close() {
    root.hidden = true;
    document.documentElement.classList.remove('studio-open');
    if (viewer) viewer.setAutoRotate(false);
    updateEntryState();
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  function isCustomized() {
    var d = cfg.defaults || {};
    return logoElements().length > 0 || state.elements.some(function (e) { return e.type === 'text' && e.text && !e.hidden; })
      || state.colors.base !== (d.base || '#1d4ed8') || state.colors.pattern !== (d.pattern || (MODEL === 'ball' ? 'panels' : 'none')) || !!state.colors.sleeve || state.colors.tintPhoto;
  }
  // Mirror the design status on the product page so the shopper knows their
  // design will be attached when they press the regular "Add to Cart".
  function updateEntryState() {
    var badge = document.getElementById('studioEntryStatus');
    if (!badge) return;
    var on = isCustomized();
    badge.hidden = !on;
    var thumb = document.getElementById('studioEntryThumb');
    if (on && thumb) thumb.src = (viewer ? viewer.snapshotSide('front', 'image/png') : paintPhoto('front', makeCanvas(300), false).toDataURL('image/png'));
  }

  document.querySelectorAll('[data-open-studio]').forEach(function (b) {
    b.addEventListener('click', function (e) { e.preventDefault(); open(b.dataset.openStudio || null); });
    // Warm the 3D library up as soon as the shopper shows intent.
    b.addEventListener('pointerenter', function () { loadThree().catch(function () {}); }, { once: true });
  });
  var csel = colorSelect();
  if (csel) csel.addEventListener('change', function () { loadPhotoBases(); });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(render);

  // ------------------------------------------------------------------
  // Team roster
  // ------------------------------------------------------------------
  var rosterWrap = $('#rosterRows');
  var ssel = sizeSelect();
  function addPlayerRow(p) {
    p = p || {};
    var row = document.createElement('div');
    row.className = 'studio-roster-row';
    row.innerHTML = '<span class="studio-roster-idx"></span>' +
      '<input type="text" class="studio-input flex-1 min-w-0" placeholder="Name" maxlength="15" data-roster-name>' +
      '<input type="text" class="studio-input w-16" placeholder="No." maxlength="3" inputmode="numeric" data-roster-number>' +
      (ssel ? '<select class="studio-input w-24" data-roster-size>' + ssel.innerHTML + '</select>' : '') +
      '<button type="button" class="studio-icon-btn" data-roster-preview title="Preview on the back" aria-label="Preview this player"><i class="fa-solid fa-eye"></i></button>' +
      '<button type="button" class="studio-icon-btn is-danger" data-roster-del aria-label="Remove player"><i class="fa-solid fa-xmark"></i></button>';
    var n = row.querySelector('[data-roster-name]'), no = row.querySelector('[data-roster-number]'), sz = row.querySelector('[data-roster-size]');
    n.value = (p.name || '').toUpperCase(); no.value = p.number || '';
    if (sz && p.size) { Array.prototype.forEach.call(sz.options, function (o) { if (o.value.toLowerCase() === String(p.size).toLowerCase()) sz.value = o.value; }); }
    n.addEventListener('input', function () { var c = n.selectionStart; n.value = n.value.toUpperCase(); n.setSelectionRange(c, c); updateRosterCount(); });
    no.addEventListener('input', function () { no.value = digits(no.value); updateRosterCount(); });
    row.querySelector('[data-roster-del]').addEventListener('click', function () { row.remove(); updateRosterCount(); });
    row.querySelector('[data-roster-preview]').addEventListener('click', function () {
      el('name').text = n.value; el('number').text = no.value; el('frontNumber').text = no.value;
      syncControls(); setSide('back'); render(); commit(true);
    });
    rosterWrap.appendChild(row);
    updateRosterCount();
    return row;
  }
  function rosterRows() {
    return Array.prototype.slice.call(rosterWrap.children).map(function (row) {
      var sz = row.querySelector('[data-roster-size]');
      return { name: row.querySelector('[data-roster-name]').value.trim(), number: row.querySelector('[data-roster-number]').value.trim(), size: sz ? sz.value : '' };
    }).filter(function (r) { return r.name || r.number; });
  }
  function updateRosterCount() {
    Array.prototype.forEach.call(rosterWrap.children, function (row, i) { row.querySelector('.studio-roster-idx').textContent = i + 1; });
    var n = rosterRows().length;
    $('#rosterCount').textContent = n + ' player' + (n === 1 ? '' : 's');
    $('[data-act="team-cart"]').disabled = n === 0;
  }
  $('[data-act="add-player"]').addEventListener('click', function () { addPlayerRow().querySelector('input').focus(); });
  $('[data-act="paste-roster"]').addEventListener('click', function () {
    var box = $('#rosterPaste');
    box.hidden = !box.hidden;
    if (!box.hidden) box.querySelector('textarea').focus();
  });
  $('[data-act="import-roster"]').addEventListener('click', function () {
    var ta = $('#rosterPaste textarea');
    var lines = ta.value.split(/\r?\n/).map(function (l) { return l.trim(); }).filter(Boolean);
    var added = 0;
    lines.forEach(function (line) {
      var parts = line.split(/[,;\t]/).map(function (s) { return s.trim(); });
      if (/^name$/i.test(parts[0])) return; // header row
      if (parts.length === 1) { var m = parts[0].match(/^(.*?)\s+(\d{1,3})$/); parts = m ? [m[1], m[2]] : parts; }
      addPlayerRow({ name: parts[0] || '', number: digits(parts[1] || ''), size: parts[2] || '' });
      added++;
    });
    ta.value = '';
    $('#rosterPaste').hidden = true;
    toast(added + ' player' + (added === 1 ? '' : 's') + ' added.');
  });
  addPlayerRow(); addPlayerRow();

  // ------------------------------------------------------------------
  // Submit
  // ------------------------------------------------------------------
  function uploadDataUrl(dataUrl) {
    var body = new URLSearchParams();
    body.set('csrf_token', cfg.csrf);
    body.set('image_data', dataUrl);
    return fetch(cfg.uploadUrl, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) { return res && res.success ? res.path : ''; })
      .catch(function () { return ''; });
  }
  function uploadCanvas(c) { return uploadDataUrl(c.toDataURL('image/png')); }

  function contactValid() {
    var email = $('#stEmail'), wa = $('#stWhatsapp');
    if (!email.value.trim() || !email.checkValidity()) { setTab('finish'); email.focus(); toast('Please enter a valid email so we can confirm your design.'); return false; }
    if (!wa.value.trim()) { setTab('finish'); wa.focus(); toast('Please enter your WhatsApp number so we can confirm your design.'); return false; }
    return true;
  }

  function specBase() {
    var name = el('name'), num = el('number'), fnum = el('frontNumber');
    return {
      model: MODEL,
      color: colorSelect() ? colorSelect().value : '',
      garment_color: state.colors.tintPhoto ? state.colors.base : '',
      base_color: state.colors.base,
      sleeve_color: state.colors.sleeve,
      trim_color: state.colors.trim,
      pattern: state.colors.pattern,
      pattern_color: state.colors.patternColor,
      font: state.text.font,
      text_color: state.text.fill,
      outline_color: state.text.outline,
      outline_width: state.text.outlineWidth,
      name_arc: name.arc,
      front_logo_count: logoElements('front').length,
      back_logo_count: logoElements('back').length,
      logo_vectors: logoElements().map(function (e) { var L = logos[e.logoId] || {}; return { vector_path: L.vectorPath || null, is_original_vector: !!L.isOriginalVector, side: e.side }; }),
      front_number_enabled: !fnum.hidden,
      back_name_size: Math.round(name.size * 1000),
      back_number_size: Math.round(num.size * 1000),
      extra_texts: state.elements.filter(function (e) { return e.role === 'custom' && e.text; }).map(function (e) { return { side: e.side, text: e.text }; }),
      notes: $('#stNotes').value.trim(),
      email: $('#stEmail').value.trim(),
      whatsapp: $('#stWhatsapp').value.trim(),
    };
  }

  function waitForVectors() {
    return Promise.all(logoElements().map(function (e) { return (logos[e.logoId] && logos[e.logoId].vectorPromise) || Promise.resolve(); }));
  }

  function busy(btn, label) {
    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + label;
    return function () { btn.disabled = false; btn.innerHTML = orig; };
  }

  // Front/back photo mockups + print art + 3D renders for the current state.
  function exportSides() {
    var photoF = paintPhoto('front', makeCanvas(PHOTO), false), photoB = paintPhoto('back', makeCanvas(PHOTO), false);
    var artF = MODEL === 'flat' ? null : paintDesign('front', makeCanvas(DESIGN));
    var artB = MODEL === 'flat' ? null : paintDesign('back', makeCanvas(DESIGN));
    if (viewer) viewer.refresh();
    var renderF = viewer ? viewer.snapshotSide('front') : null, renderB = viewer ? viewer.snapshotSide('back') : null;
    return Promise.all([
      uploadCanvas(photoF), uploadCanvas(photoB),
      artF ? uploadCanvas(artF) : '', artB ? uploadCanvas(artB) : '',
      renderF ? uploadDataUrl(renderF) : '', renderB ? uploadDataUrl(renderB) : '',
    ]).then(function (r) {
      return { preview_path: r[0], preview_back_path: r[1], design_front_path: r[2], design_back_path: r[3], render_front_path: r[4], render_back_path: r[5] };
    });
  }

  function addSingle(btn) {
    if (!contactValid()) return;
    var done = busy(btn, 'Preparing your design…');
    waitForVectors().then(exportSides).then(function (paths) {
      var name = el('name'), num = el('number'), fnum = el('frontNumber');
      var data = specBase();
      data.front_number = fnum.hidden ? '' : num.text;
      data.back_name = name.text;
      data.back_number = num.text;
      Object.keys(paths).forEach(function (k) { data[k] = paths[k]; });
      document.getElementById('customizationPreviewInput').value = paths.preview_path;
      document.getElementById('customizationPreviewBackInput').value = paths.preview_back_path;
      document.getElementById('customizationDataInput').value = JSON.stringify(data);
      document.getElementById('formActionInput').value = 'add_to_cart';
      var qty = document.getElementById('qtyInput');
      var sq = $('#stQty');
      if (qty && sq) qty.value = Math.max(1, parseInt(sq.value, 10) || 1);
      try { localStorage.removeItem(storeKey); } catch (e) {}
      form.submit();
    }).catch(function () { done(); toast('Something went wrong saving your design. Please try again.'); });
  }

  function resolveVariationId(rowSize) {
    var selects = Array.prototype.slice.call(document.querySelectorAll('.variation-select'));
    if (!selects.length || !window.variationMap) return null;
    var key = selects.map(function (s) { return ((s.dataset.type || '').toLowerCase() === 'size' && rowSize) ? rowSize : s.value; }).join(' / ');
    var m = window.variationMap[key];
    return m ? m.id : null;
  }

  function addTeam(btn) {
    var rows = rosterRows();
    if (!rows.length) { toast('Add at least one player (name or number).'); return; }
    if (!contactValid()) return;
    var done = busy(btn, 'Preparing ' + rows.length + ' designs…');
    var name = el('name'), num = el('number'), fnum = el('frontNumber');
    var saved = { name: name.text, number: num.text, fnum: fnum.text };
    var frontVaries = !fnum.hidden;

    waitForVectors().then(function () {
      // Shared front (unless each player's number is also printed on the front).
      var shared = frontVaries ? Promise.resolve({}) : Promise.all([
        uploadCanvas(paintPhoto('front', makeCanvas(PHOTO), false)),
        MODEL === 'flat' ? '' : uploadCanvas(paintDesign('front', makeCanvas(DESIGN))),
      ]).then(function (r) { return { front_preview_path: r[0], design_front_path: r[1] }; });

      return shared.then(function (sharedPaths) {
        // Players are rendered one after another (the canvases are shared) but
        // their uploads run in parallel.
        var uploads = rows.map(function (r) {
          name.text = r.name; num.text = r.number; fnum.text = r.number;
          var ups = [
            uploadCanvas(paintPhoto('back', makeCanvas(PHOTO), false)),
            MODEL === 'flat' ? '' : uploadCanvas(paintDesign('back', makeCanvas(DESIGN))),
          ];
          if (frontVaries) {
            ups.push(uploadCanvas(paintPhoto('front', makeCanvas(PHOTO), false)));
            ups.push(MODEL === 'flat' ? '' : uploadCanvas(paintDesign('front', makeCanvas(DESIGN))));
          }
          return Promise.all(ups).then(function (p) {
            return {
              name: r.name, number: r.number, size: r.size,
              variation_id: resolveVariationId(r.size),
              preview_back_path: p[0], design_back_path: p[1],
              preview_front_path: frontVaries ? p[2] : '', design_front_path: frontVaries ? p[3] : '',
            };
          });
        });
        name.text = saved.name; num.text = saved.number; fnum.text = saved.fnum;
        render();
        var renders = viewer ? Promise.all([uploadDataUrl(viewer.snapshotSide('front')), uploadDataUrl(viewer.snapshotSide('back'))]) : Promise.resolve(['', '']);
        return Promise.all([Promise.all(uploads), renders]).then(function (res) {
          var data = specBase();
          data.players = res[0];
          data.front_preview_path = sharedPaths.front_preview_path || '';
          data.design_front_path = sharedPaths.design_front_path || '';
          data.render_front_path = res[1][0];
          data.render_back_path = res[1][1];
          document.getElementById('teamDataInput').value = JSON.stringify(data);
          document.getElementById('formActionInput').value = 'team_add_to_cart';
          try { localStorage.removeItem(storeKey); } catch (e) {}
          form.submit();
        });
      });
    }).catch(function () {
      name.text = saved.name; num.text = saved.number; fnum.text = saved.fnum; render();
      done(); toast('Something went wrong uploading the designs. Please try again.');
    });
  }

  $('[data-act="cart"]').addEventListener('click', function () { addSingle(this); });
  $('[data-act="team-cart"]').addEventListener('click', function () { addTeam(this); });

  // The product page's own "Add to Cart" attaches the design when there is one.
  form.addEventListener('submit', function (e) {
    if (document.getElementById('formActionInput').value !== 'add_to_cart') return;
    if (!isCustomized() || document.getElementById('customizationDataInput').value) return;
    e.preventDefault();
    open('finish');
  });

  // ------------------------------------------------------------------
  // Boot
  // ------------------------------------------------------------------
  syncControls();
  setSide('front');
  setTab('colors');
  lastSnap = serialize();
  updateHistoryButtons();
  if (/[?&#]customize\b/.test(location.search + location.hash)) open();
})();
