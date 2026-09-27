/*!
 * site.js — storefront interactions for BuiltCo Sports.
 * Header state, mobile drawer, search overlay with live suggestions, scroll
 * reveal, 3D tilt cards, counters, hero slider and the lazy-loaded 3D hero.
 */
(function () {
  'use strict';
  var doc = document.documentElement;
  doc.classList.remove('no-js');
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var cfg = window.SITE_CONFIG || {};

  function qs(s, c) { return (c || document).querySelector(s); }
  function qsa(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

  // ---- Sticky header shadow / compact on scroll ----
  var header = qs('.site-header');
  var backTop = qs('#backToTop');
  var ticking = false;
  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      var y = window.scrollY;
      if (header) header.classList.toggle('is-scrolled', y > 24);
      if (backTop) backTop.classList.toggle('opacity-0', y < 600), backTop.classList.toggle('pointer-events-none', y < 600);
      ticking = false;
    });
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  if (backTop) backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }); });

  // ---- Mobile drawer ----
  var drawer = qs('#mobileDrawer');
  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('is-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    document.body.style.overflow = open ? 'hidden' : '';
    var btn = qs('#mobileMenuBtn');
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) { var f = qs('a, button', drawer.querySelector('.drawer-panel')); f && f.focus(); }
  }
  qsa('[data-drawer-open]').forEach(function (b) { b.addEventListener('click', function () { setDrawer(true); }); });
  qsa('[data-drawer-close]').forEach(function (b) { b.addEventListener('click', function () { setDrawer(false); }); });
  qsa('[data-accordion]').forEach(function (b) {
    b.addEventListener('click', function () {
      var panel = document.getElementById(b.getAttribute('aria-controls'));
      var open = b.getAttribute('aria-expanded') !== 'true';
      b.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (panel) panel.hidden = !open;
      var icon = b.querySelector('i'); if (icon) icon.style.transform = open ? 'rotate(180deg)' : '';
    });
  });

  // ---- Search overlay + live suggestions ----
  var overlay = qs('#searchOverlay');
  var input = overlay && qs('input[name=q]', overlay);
  var results = overlay && qs('#searchResults', overlay);
  function setSearch(open) {
    if (!overlay) return;
    overlay.classList.toggle('is-open', open);
    overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) setTimeout(function () { input && input.focus(); }, 60);
  }
  qsa('[data-search-open]').forEach(function (b) { b.addEventListener('click', function () { setSearch(true); }); });
  qsa('[data-search-close]').forEach(function (b) { b.addEventListener('click', function () { setSearch(false); }); });
  if (overlay) overlay.addEventListener('click', function (e) { if (e.target === overlay) setSearch(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { setSearch(false); setDrawer(false); }
    if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(e.target.tagName) && overlay && !document.documentElement.classList.contains('studio-open')) { e.preventDefault(); setSearch(true); }
  });
  var searchTimer = 0, lastQ = '';
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  if (input && results && cfg.suggestUrl) {
    input.addEventListener('input', function () {
      clearTimeout(searchTimer);
      var q = input.value.trim();
      if (q.length < 2) { results.innerHTML = ''; return; }
      searchTimer = setTimeout(function () {
        if (q === lastQ) return;
        lastQ = q;
        fetch(cfg.suggestUrl + (cfg.suggestUrl.indexOf('?') > -1 ? '&' : '?') + 'suggest=1&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (input.value.trim() !== q) return;
            var items = (data && data.products) || [];
            var cats = (data && data.categories) || [];
            if (!items.length && !cats.length) { results.innerHTML = '<p class="px-5 py-6 text-sm text-slate-500">No matches for “' + esc(q) + '”. Press Enter to search everything.</p>'; return; }
            var html = '';
            if (cats.length) html += '<div class="px-5 pt-4 pb-2 flex flex-wrap gap-2">' + cats.map(function (c) { return '<a class="chip" href="' + esc(c.url) + '">' + esc(c.name) + '</a>'; }).join('') + '</div>';
            html += items.map(function (p) {
              return '<a href="' + esc(p.url) + '" class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition">' +
                (p.image ? '<img src="' + esc(p.image) + '" alt="" class="w-12 h-12 rounded-xl object-cover bg-slate-100" loading="lazy">' : '<span class="w-12 h-12 rounded-xl bg-slate-100"></span>') +
                '<span class="flex-1 min-w-0"><span class="block font-semibold text-sm truncate">' + esc(p.name) + '</span><span class="block text-xs text-slate-500">' + esc(p.category || '') + '</span></span>' +
                '<span class="text-sm font-bold">' + esc(p.price) + '</span></a>';
            }).join('');
            results.innerHTML = html;
          }).catch(function () {});
      }, 180);
    });
  }

  // ---- Scroll reveal ----
  var revealEls = qsa('[data-reveal]');
  if ('IntersectionObserver' in window && !reduceMotion) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        en.target.classList.add('is-visible');
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    // Stagger siblings inside grids for a cascading entrance.
    revealEls.forEach(function (el) {
      var parent = el.parentElement;
      if (parent && !el.style.getPropertyValue('--reveal-delay')) {
        var idx = Array.prototype.indexOf.call(parent.children, el);
        el.style.setProperty('--reveal-delay', Math.min(idx, 8) * 70 + 'ms');
      }
      io.observe(el);
    });
  } else {
    revealEls.forEach(function (el) { el.classList.add('is-visible'); });
  }

  // ---- 3D tilt with glare ----
  if (!reduceMotion && window.matchMedia('(hover: hover)').matches) {
    qsa('.tilt-3d').forEach(function (el) {
      var max = parseFloat(el.dataset.tilt || '9');
      if (!el.querySelector('.tilt-glare')) { var g = document.createElement('span'); g.className = 'tilt-glare'; el.appendChild(g); }
      el.addEventListener('pointermove', function (e) {
        var r = el.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
        el.style.transform = 'perspective(900px) rotateX(' + ((0.5 - py) * max * 2).toFixed(2) + 'deg) rotateY(' + ((px - 0.5) * max * 2).toFixed(2) + 'deg) scale3d(1.02,1.02,1.02)';
        el.style.setProperty('--gx', (px * 100) + '%');
        el.style.setProperty('--gy', (py * 100) + '%');
      });
      el.addEventListener('pointerleave', function () { el.style.transform = ''; });
    });
  }

  // ---- Count-up numbers ----
  var counters = qsa('[data-count]');
  if (counters.length && 'IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target, target = parseFloat(el.dataset.count), suffix = el.dataset.suffix || '', start = performance.now();
        cio.unobserve(el);
        if (reduceMotion) { el.textContent = target + suffix; return; }
        (function step(now) {
          var t = Math.min(1, (now - start) / 1600), eased = 1 - Math.pow(1 - t, 3);
          el.textContent = Math.round(target * eased).toLocaleString() + suffix;
          if (t < 1) requestAnimationFrame(step);
        })(start);
      });
    }, { threshold: 0.4 });
    counters.forEach(function (c) { cio.observe(c); });
  }

  // ---- Hero slider ----
  var slides = qsa('.hero-slide');
  if (slides.length > 1) {
    var dots = qsa('.hero-dot'), bgs = qsa('.hero-bg'), idx = 0, timer = 0;
    var show = function (n) {
      idx = (n + slides.length) % slides.length;
      slides.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); s.setAttribute('aria-hidden', i === idx ? 'false' : 'true'); });
      bgs.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); });
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i === idx); d.setAttribute('aria-current', i === idx ? 'true' : 'false'); });
    };
    var play = function () { clearInterval(timer); if (!reduceMotion) timer = setInterval(function () { show(idx + 1); }, 6500); };
    dots.forEach(function (d) { d.addEventListener('click', function () { show(+d.dataset.dot); play(); }); });
    qsa('[data-hero-prev]').forEach(function (b) { b.addEventListener('click', function () { show(idx - 1); play(); }); });
    qsa('[data-hero-next]').forEach(function (b) { b.addEventListener('click', function () { show(idx + 1); play(); }); });
    play();
  }

  // ---- Horizontal scrollers with arrow buttons ----
  qsa('[data-scroller]').forEach(function (wrap) {
    var track = qs('[data-scroller-track]', wrap);
    if (!track) return;
    qsa('[data-scroller-prev]', wrap).forEach(function (b) { b.addEventListener('click', function () { track.scrollBy({ left: -track.clientWidth * 0.8, behavior: 'smooth' }); }); });
    qsa('[data-scroller-next]', wrap).forEach(function (b) { b.addEventListener('click', function () { track.scrollBy({ left: track.clientWidth * 0.8, behavior: 'smooth' }); }); });
  });

  // ---- Lazy 3D hero (three.js only loads after the page is interactive) ----
  var heroMount = qs('#hero3d');
  if (heroMount && cfg.threeUrl && cfg.garmentUrl) {
    var boot = function () {
      if (!window.matchMedia('(min-width: 768px)').matches && heroMount.dataset.mobile !== '1') return;
      var load = function (src) { return new Promise(function (res, rej) { var s = document.createElement('script'); s.src = src; s.onload = res; s.onerror = rej; document.head.appendChild(s); }); };
      (window.THREE ? Promise.resolve() : load(cfg.threeUrl))
        .then(function () { return window.Garment3D ? null : load(cfg.garmentUrl); })
        .then(function () { if (window.HeroScene) window.HeroScene(heroMount); })
        .catch(function () {});
    };
    if ('requestIdleCallback' in window) requestIdleCallback(boot, { timeout: 2500 }); else setTimeout(boot, 1200);
  }
})();

/* Hero scene: a spinning custom kit + ball rendered with Garment3D. */
window.HeroScene = function (mount) {
  if (!window.Garment3D || !window.Garment3D.supported()) return;
  var brand = getComputedStyle(document.documentElement).getPropertyValue('--c-ignite').trim().split(/\s+/).map(Number);
  var hex = '#' + brand.map(function (n) { return ('0' + (n | 0).toString(16)).slice(-2); }).join('');
  var model = mount.dataset.model || 'jersey';
  var S = 1024;

  function paint(side) {
    var c = document.createElement('canvas'); c.width = c.height = S;
    var g = c.getContext('2d');
    g.save();
    if (model === 'jersey') { window.Garment3D.traceShirtPath(g, S, S); g.clip(); }
    g.fillStyle = '#0f141b'; g.fillRect(0, 0, S, S);
    if (model === 'ball') {
      var s = S / 9, hh = s * Math.sqrt(3) / 2;
      g.fillStyle = '#ffffff'; g.fillRect(0, 0, S, S);
      g.fillStyle = '#0f141b'; g.strokeStyle = '#0f141b'; g.lineWidth = 6;
      for (var row = -1; row * hh * 2 < S + s; row++) for (var col = -1; col * s * 1.5 < S + s; col++) {
        var hx = col * s * 1.5, hy = row * hh * 2 + (col % 2 ? hh : 0);
        g.beginPath();
        for (var i = 0; i < 6; i++) { var a = Math.PI / 3 * i; var vx = hx + Math.cos(a) * s * 0.98, vy = hy + Math.sin(a) * s * 0.98; i ? g.lineTo(vx, vy) : g.moveTo(vx, vy); }
        g.closePath();
        if ((row + col * 2) % 3 === 0) { g.fillStyle = (row + col) % 2 ? hex : '#0f141b'; g.fill(); } else g.stroke();
      }
    } else {
      // Diagonal speed sash + chest band in the brand colour.
      g.fillStyle = hex;
      g.beginPath(); g.moveTo(S * 0.2, 0); g.lineTo(S * 0.34, 0); g.lineTo(S * 0.86, S); g.lineTo(S * 0.72, S); g.closePath(); g.fill();
      g.globalAlpha = 0.18;
      for (var y = 0; y < S; y += 22) g.fillRect(0, y, S, 6);
      g.globalAlpha = 1;
      g.fillStyle = hex;
      window.Garment3D.tracePoly(g, window.Garment3D.SHIRT.leftSleeve, S, S); g.fill();
      window.Garment3D.tracePoly(g, window.Garment3D.SHIRT.rightSleeve, S, S); g.fill();
      g.fillStyle = '#ffffff'; g.textAlign = 'center'; g.textBaseline = 'middle';
      if (side === 'front') {
        g.font = '900 64px Oswald, Impact, sans-serif'; g.fillText((mount.dataset.label || 'BUILTCO').toUpperCase(), S * 0.5, S * 0.28);
        g.font = '900 150px Oswald, Impact, sans-serif'; g.fillText('10', S * 0.5, S * 0.52);
      } else {
        g.font = '900 72px Oswald, Impact, sans-serif'; g.fillText((mount.dataset.name || 'YOUR NAME').toUpperCase(), S * 0.5, S * 0.26);
        g.font = '900 300px Oswald, Impact, sans-serif'; g.lineWidth = 10; g.strokeStyle = hex; g.strokeText('10', S * 0.5, S * 0.52); g.fillText('10', S * 0.5, S * 0.52);
      }
    }
    g.restore();
    return c;
  }

  var draw = function () {
    var viewer = window.Garment3D.create(mount, {
      model: model, front: paint('front'), back: paint('back'),
      baseColor: '#0f141b', collarColor: hex, trimColor: hex,
      autoRotate: true, interactive: true, initialRotation: -0.5, label: '3D preview of a custom team kit',
    });
    if (!viewer) return;
    mount.classList.add('is-ready');
    var section = mount.closest('section') || document;
    section.addEventListener('pointermove', function (e) {
      var r = section.getBoundingClientRect ? section.getBoundingClientRect() : { left: 0, top: 0, width: innerWidth, height: innerHeight };
      viewer.setParallax(((e.clientX - r.left) / r.width - 0.5) * 0.6, ((e.clientY - r.top) / r.height - 0.5) * 0.6);
    });
  };
  if (document.fonts && document.fonts.load) document.fonts.load('900 64px Oswald').then(draw, draw); else draw();
};
