/*!
 * garment3d.js — lightweight real-time 3D product viewer for BuiltCo Sports.
 *
 * Builds procedural 3D models (no external model files needed):
 *   - "jersey": a padded, torso-shaped shirt with a collar ring
 *   - "ball":   a sphere (front/back design wrap around it)
 *   - "flat":   a rounded 3D card showing the front/back photo mockups
 *
 * Every model is textured from plain 2D <canvas> elements, so whatever the
 * customizer paints on its design canvases shows up live on the 3D model.
 * Design space is always the canvas' own pixel space (e.g. 800x800), which
 * means a logo placed at (400, 300) on the flat editor sits at the same spot
 * on the 3D shirt, and a pointer hit on the 3D shirt maps straight back.
 *
 * Requires the global THREE build (assets/vendor/three/three.min.js).
 */
(function (global) {
  'use strict';

  // Shirt silhouette in normalized design space (0..1, y pointing down like a
  // canvas). Shared with the 2D editor via Garment3D.SHIRT so both agree on
  // where sleeves, collar and hem are.
  var SHIRT = {
    // [x, y] points; consecutive "curve" entries are quadratic control points.
    outline: [
      ['M', 0.385, 0.075],
      ['Q', 0.5, 0.175, 0.615, 0.075],   // front neckline scoop
      ['L', 0.735, 0.105],               // right shoulder
      ['L', 0.955, 0.305],               // right sleeve top edge
      ['L', 0.865, 0.43],                // right sleeve cuff
      ['L', 0.745, 0.335],               // right armpit
      ['Q', 0.735, 0.62, 0.75, 0.94],    // right side seam
      ['Q', 0.5, 0.965, 0.25, 0.94],     // hem
      ['Q', 0.265, 0.62, 0.255, 0.335],  // left side seam
      ['L', 0.135, 0.43],                // left sleeve cuff
      ['L', 0.045, 0.305],               // left sleeve top edge
      ['L', 0.265, 0.105],               // left shoulder
      ['L', 0.385, 0.075],
    ],
    leftSleeve: [[0.265, 0.105], [0.045, 0.305], [0.135, 0.43], [0.255, 0.335]],
    rightSleeve: [[0.735, 0.105], [0.955, 0.305], [0.865, 0.43], [0.745, 0.335]],
    torso: { x0: 0.25, x1: 0.75, y0: 0.075, y1: 0.955 },
  };

  // Traces the shirt outline into a 2D canvas path (for clipping / guides).
  function traceShirtPath(ctx, W, H) {
    ctx.beginPath();
    SHIRT.outline.forEach(function (seg) {
      if (seg[0] === 'M') ctx.moveTo(seg[1] * W, seg[2] * H);
      else if (seg[0] === 'L') ctx.lineTo(seg[1] * W, seg[2] * H);
      else ctx.quadraticCurveTo(seg[1] * W, seg[2] * H, seg[3] * W, seg[4] * H);
    });
    ctx.closePath();
  }

  function tracePoly(ctx, pts, W, H) {
    ctx.beginPath();
    pts.forEach(function (p, i) { i ? ctx.lineTo(p[0] * W, p[1] * H) : ctx.moveTo(p[0] * W, p[1] * H); });
    ctx.closePath();
  }

  function hasWebGL() {
    try {
      var c = document.createElement('canvas');
      return !!(global.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl')));
    } catch (e) { return false; }
  }

  // A soft round shadow that sits under the model.
  function makeShadowTexture(THREE) {
    var c = document.createElement('canvas');
    c.width = c.height = 128;
    var g = c.getContext('2d');
    var grd = g.createRadialGradient(64, 64, 4, 64, 64, 62);
    grd.addColorStop(0, 'rgba(0,0,0,0.45)');
    grd.addColorStop(1, 'rgba(0,0,0,0)');
    g.fillStyle = grd;
    g.fillRect(0, 0, 128, 128);
    return new THREE.CanvasTexture(c);
  }

  // Tiny procedural knit pattern used as a bump map so fabric catches light
  // like mesh polyester instead of looking like plastic.
  function makeKnitTexture(THREE) {
    var c = document.createElement('canvas');
    c.width = c.height = 64;
    var g = c.getContext('2d');
    g.fillStyle = '#808080';
    g.fillRect(0, 0, 64, 64);
    for (var y = 0; y < 64; y += 4) {
      for (var x = 0; x < 64; x += 4) {
        var off = (y / 4) % 2 ? 2 : 0;
        g.fillStyle = '#b0b0b0';
        g.fillRect(x + off, y, 2, 2);
        g.fillStyle = '#5a5a5a';
        g.fillRect(x + off + 1, y + 2, 1, 1);
      }
    }
    var t = new THREE.CanvasTexture(c);
    t.wrapS = t.wrapT = THREE.RepeatWrapping;
    return t;
  }

  function buildShirtShape(THREE, S) {
    var shape = new THREE.Shape();
    function X(x) { return (x - 0.5) * S; }
    function Y(y) { return (0.5 - y) * S; }
    SHIRT.outline.forEach(function (seg) {
      if (seg[0] === 'M') shape.moveTo(X(seg[1]), Y(seg[2]));
      else if (seg[0] === 'L') shape.lineTo(X(seg[1]), Y(seg[2]));
      else shape.quadraticCurveTo(X(seg[1]), Y(seg[2]), X(seg[3]), Y(seg[4]));
    });
    return shape;
  }

  // Splits ExtrudeGeometry's single "caps" group (back lid + front lid) into
  // two groups so front and back can carry different textures.
  // Resulting material indices: 0 = back, 1 = sides, 2 = front.
  function splitLids(geo) {
    var groups = geo.groups.slice();
    geo.clearGroups();
    groups.forEach(function (g) {
      if (g.materialIndex === 0) {
        var half = g.count / 2;
        geo.addGroup(g.start, half, 0);
        geo.addGroup(g.start + half, half, 2);
      } else {
        geo.addGroup(g.start, g.count, 1);
      }
    });
  }

  function planarUV(S) {
    return {
      generateTopUV: function (geometry, vertices, a, b, c) {
        return [a, b, c].map(function (i) {
          return new global.THREE.Vector2(vertices[i * 3] / S + 0.5, vertices[i * 3 + 1] / S + 0.5);
        });
      },
      generateSideWallUV: function (geometry, vertices, a, b, c, d) {
        var V = global.THREE.Vector2;
        return [new V(0, 0), new V(1, 0), new V(1, 1), new V(0, 1)];
      },
    };
  }

  // Flattens SHIRT.outline (with its quadratic curves) into a polyline.
  function shirtPolyline(steps) {
    var pts = [], cur = null;
    SHIRT.outline.forEach(function (seg) {
      if (seg[0] === 'M' || seg[0] === 'L') { cur = [seg[1], seg[2]]; pts.push(cur); return; }
      for (var i = 1; i <= steps; i++) {
        var t = i / steps, a = 1 - t;
        cur = [a * a * cur[0] + 2 * a * t * seg[1] + t * t * seg[3], a * a * cur[1] + 2 * a * t * seg[2] + t * t * seg[4]];
        pts.push(cur);
      }
      cur = [seg[3], seg[4]];
    });
    return pts;
  }

  function insidePoly(x, y, poly) {
    var inside = false;
    for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
      var xi = poly[i][0], yi = poly[i][1], xj = poly[j][0], yj = poly[j][1];
      if (((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi)) inside = !inside;
    }
    return inside;
  }

  function distToPoly(x, y, poly) {
    var best = Infinity;
    for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
      var ax = poly[j][0], ay = poly[j][1], bx = poly[i][0], by = poly[i][1];
      var dx = bx - ax, dy = by - ay, l2 = dx * dx + dy * dy;
      var t = l2 ? Math.max(0, Math.min(1, ((x - ax) * dx + (y - ay) * dy) / l2)) : 0;
      var ex = ax + t * dx - x, ey = ay + t * dy - y;
      var d = ex * ex + ey * ey;
      if (d < best) best = d;
    }
    return Math.sqrt(best);
  }

  // One side (front or back) of the "inflated" shirt: a dense grid whose
  // height follows the distance to the silhouette, so front and back meet
  // cleanly at the outline like a real garment seam. The silhouette itself is
  // cut out by the design texture's alpha channel.
  function shirtSurface(THREE, S, heights, N) {
    var geo = new THREE.PlaneGeometry(S, S, N, N);
    var pos = geo.attributes.position;
    for (var i = 0; i < pos.count; i++) pos.setZ(i, heights[i]);
    // Drop triangles that lie completely outside the shirt (saves fill-rate).
    var idx = geo.index.array, keep = [];
    for (var k = 0; k < idx.length; k += 3) {
      if (heights[idx[k]] > 0 || heights[idx[k + 1]] > 0 || heights[idx[k + 2]] > 0) keep.push(idx[k], idx[k + 1], idx[k + 2]);
    }
    geo.setIndex(keep);
    geo.computeVertexNormals();
    return geo;
  }

  function buildJersey(THREE, mats) {
    var S = 4, N = 150;
    var poly = shirtPolyline(20);
    var heights = new Float32Array((N + 1) * (N + 1));
    var H = 0.5, D = 0.5 / S; // plateau height and rounding distance (normalized)
    for (var r = 0; r <= N; r++) {
      for (var c = 0; c <= N; c++) {
        // PlaneGeometry rows run top (v=1) to bottom; design y points down.
        var u = c / N, yDown = r / N;
        var h = 0;
        if (insidePoly(u, yDown, poly)) {
          var t = Math.min(1, distToPoly(u, yDown, poly) / D);
          var round = Math.sqrt(1 - (1 - t) * (1 - t));
          var chest = Math.max(0, 1 - Math.pow((u - 0.5) / 0.3, 2)) * 0.22;
          h = round * (H + chest * t);
        }
        heights[r * (N + 1) + c] = h;
      }
    }

    var group = new THREE.Group();
    var front = new THREE.Mesh(shirtSurface(THREE, S, heights, N), mats.front);
    front.userData.side = 'front';
    var back = new THREE.Mesh(shirtSurface(THREE, S, heights, N), mats.back);
    back.rotation.y = Math.PI;
    back.userData.side = 'back';
    group.add(front, back);

    // Ribbed trims (collar, cuffs, hem) as tubes along the seams.
    function X(x) { return (x - 0.5) * S; }
    function Y(y) { return (0.5 - y) * S; }
    var V3 = THREE.Vector3;
    var trims = [
      new THREE.QuadraticBezierCurve3(new V3(X(0.385), Y(0.075), 0), new V3(X(0.5), Y(0.175), 0), new V3(X(0.615), Y(0.075), 0)),
      new THREE.LineCurve3(new V3(X(0.955), Y(0.305), 0), new V3(X(0.865), Y(0.43), 0)),
      new THREE.LineCurve3(new V3(X(0.045), Y(0.305), 0), new V3(X(0.135), Y(0.43), 0)),
      new THREE.QuadraticBezierCurve3(new V3(X(0.75), Y(0.94), 0), new V3(X(0.5), Y(0.965), 0), new V3(X(0.25), Y(0.94), 0)),
    ];
    trims.forEach(function (curve, i) {
      var tube = new THREE.Mesh(new THREE.TubeGeometry(curve, 40, i === 0 ? 0.075 : 0.05, 10, false), i === 0 ? mats.collar : mats.trim);
      group.add(tube);
    });

    return { group: group, meshes: [front, back], size: S, kind: 'jersey' };
  }

  function buildBall(THREE, mats) {
    var geo = new THREE.SphereGeometry(1.55, 72, 54);
    var mesh = new THREE.Mesh(geo, mats.ball);
    var group = new THREE.Group();
    group.add(mesh);
    return { group: group, meshes: [mesh], size: 3.1, kind: 'ball' };
  }

  function buildCard(THREE, mats) {
    var S = 3.4, r = 0.28, half = S / 2;
    var shape = new THREE.Shape();
    shape.moveTo(-half + r, -half);
    shape.lineTo(half - r, -half); shape.quadraticCurveTo(half, -half, half, -half + r);
    shape.lineTo(half, half - r); shape.quadraticCurveTo(half, half, half - r, half);
    shape.lineTo(-half + r, half); shape.quadraticCurveTo(-half, half, -half, half - r);
    shape.lineTo(-half, -half + r); shape.quadraticCurveTo(-half, -half, -half + r, -half);
    var geo = new THREE.ExtrudeGeometry(shape, {
      depth: 0.12, bevelEnabled: true, bevelThickness: 0.05, bevelSize: 0.05,
      bevelSegments: 4, curveSegments: 12, UVGenerator: planarUV(S),
    });
    geo.translate(0, 0, -0.06);
    splitLids(geo);
    var mesh = new THREE.Mesh(geo, [mats.back, mats.side, mats.front]);
    var group = new THREE.Group();
    group.add(mesh);
    return { group: group, meshes: [mesh], size: S, kind: 'flat' };
  }

  /**
   * Garment3D.create(mount, options)
   *  options.model      'jersey' | 'ball' | 'flat'
   *  options.front      <canvas> for the front texture
   *  options.back       <canvas> for the back texture
   *  options.baseColor  colour for the side walls / fallback
   *  options.collarColor
   *  options.autoRotate true|false (default true)
   *  options.interactive (default true) drag to rotate, wheel/pinch to zoom
   *  options.onPick(side, x, y) → truthy to start dragging a design element
   *  options.onDrag(side, x, y)
   *  options.onDragEnd()
   */
  function create(mount, options) {
    var THREE = global.THREE;
    if (!THREE || !mount || !hasWebGL()) return null;
    options = options || {};
    var reduceMotion = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, preserveDrawingBuffer: true });
    } catch (e) { return null; }
    renderer.setPixelRatio(Math.min(global.devicePixelRatio || 1, 2));
    if ('outputColorSpace' in renderer && THREE.SRGBColorSpace) renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.domElement.style.display = 'block';
    renderer.domElement.style.width = '100%';
    renderer.domElement.style.height = '100%';
    renderer.domElement.style.touchAction = 'none';
    renderer.domElement.setAttribute('aria-label', options.label || '3D product preview');
    renderer.domElement.setAttribute('role', 'img');
    mount.appendChild(renderer.domElement);

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(32, 1, 0.1, 100);
    var baseDistance = options.distance || 10;
    camera.position.set(0, 0.2, baseDistance);

    scene.add(new THREE.HemisphereLight(0xffffff, 0x3a3f4b, 1.05));
    var key = new THREE.DirectionalLight(0xffffff, 1.6);
    key.position.set(3.5, 4, 6);
    scene.add(key);
    var rim = new THREE.DirectionalLight(0xffffff, 0.9);
    rim.position.set(-5, 2, -5);
    scene.add(rim);
    var fill = new THREE.DirectionalLight(0xffe7da, 0.45);
    fill.position.set(-4, -2, 4);
    scene.add(fill);

    var knit = makeKnitTexture(THREE);
    knit.repeat.set(28, 28);

    function canvasTexture(canvas, mirror) {
      if (!canvas) return null;
      var t = new THREE.CanvasTexture(canvas);
      if ('colorSpace' in t && THREE.SRGBColorSpace) t.colorSpace = THREE.SRGBColorSpace;
      t.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
      if (mirror) { t.wrapS = THREE.RepeatWrapping; t.repeat.x = -1; t.offset.x = 1; }
      return t;
    }

    var model = options.model || 'jersey';
    var fabric = model !== 'flat';
    function fabricMat(map) {
      return new THREE.MeshStandardMaterial({
        map: map, color: map ? 0xffffff : new THREE.Color(options.baseColor || '#dddddd'),
        roughness: fabric ? 0.82 : 0.55, metalness: 0.0,
        bumpMap: fabric ? knit : null, bumpScale: fabric ? 0.012 : 0,
        // Jersey silhouettes are cut from the texture's transparent pixels.
        alphaTest: model === 'jersey' ? 0.5 : 0,
        alphaToCoverage: model === 'jersey',
        side: THREE.FrontSide,
      });
    }

    var textures = {};
    var mats = {};
    var ballCanvas = null;
    if (model === 'ball') {
      // Front + back designs laid side by side → one equirectangular wrap.
      ballCanvas = document.createElement('canvas');
      ballCanvas.width = 2048; ballCanvas.height = 1024;
      textures.ball = canvasTexture(ballCanvas, false);
      mats.ball = new THREE.MeshStandardMaterial({ map: textures.ball, roughness: 0.45, metalness: 0.02, bumpMap: knit, bumpScale: 0.004 });
    } else {
      textures.front = canvasTexture(options.front, false);
      // The jersey's back surface is a rotated copy, so its UVs already read
      // correctly from behind; the flat card's back lid needs mirroring.
      textures.back = canvasTexture(options.back, model === 'flat');
      mats.front = fabricMat(textures.front);
      mats.back = fabricMat(textures.back);
      mats.trim = new THREE.MeshStandardMaterial({ color: new THREE.Color(options.trimColor || options.collarColor || '#ffffff'), roughness: 0.75, bumpMap: knit, bumpScale: 0.02 });
      mats.side = new THREE.MeshStandardMaterial({ color: new THREE.Color(options.baseColor || '#e5e7eb'), roughness: 0.85 });
      mats.collar = new THREE.MeshStandardMaterial({ color: new THREE.Color(options.collarColor || '#ffffff'), roughness: 0.7, bumpMap: knit, bumpScale: 0.01 });
    }

    var built = model === 'ball' ? buildBall(THREE, mats) : model === 'flat' ? buildCard(THREE, mats) : buildJersey(THREE, mats);
    var pivot = new THREE.Group();
    pivot.add(built.group);
    scene.add(pivot);

    var shadow = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), new THREE.MeshBasicMaterial({ map: makeShadowTexture(THREE), transparent: true, depthWrite: false }));
    shadow.rotation.x = -Math.PI / 2;
    shadow.position.y = -built.size * 0.56;
    shadow.scale.set(built.size * 0.9, built.size * 0.32, 1);
    scene.add(shadow);

    function paintBall() {
      if (!ballCanvas) return;
      var g = ballCanvas.getContext('2d');
      var w = ballCanvas.width / 2, h = ballCanvas.height;
      g.clearRect(0, 0, ballCanvas.width, h);
      // Sphere UVs put the camera-facing side at u = 0.25 (left half) and
      // the far side at u = 0.75 (right half).
      if (options.front) g.drawImage(options.front, 0, 0, w, h);
      if (options.back) g.drawImage(options.back, w, 0, w, h);
    }
    paintBall();

    // ---- sizing ----
    function resize() {
      var w = mount.clientWidth || 300, h = mount.clientHeight || 300;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      // Keep the whole model in frame on tall/narrow viewports.
      var fit = built.size * 1.25 / (2 * Math.tan((camera.fov * Math.PI / 180) / 2));
      baseDistance = Math.max(options.distance || 0, camera.aspect < 1 ? fit / camera.aspect : fit);
      camera.updateProjectionMatrix();
      dirty = true;
    }
    var ro = global.ResizeObserver ? new ResizeObserver(resize) : null;
    if (ro) ro.observe(mount); else global.addEventListener('resize', resize);

    // ---- interaction ----
    var rotY = options.initialRotation || 0, rotX = 0.05, targetY = rotY, targetX = rotX, velY = 0;
    var zoom = 1, targetZoom = 1;
    var autoRotate = options.autoRotate !== false && !reduceMotion;
    var lastInteract = 0;
    var dragging = false, draggingElement = false, lastX = 0, lastY = 0, pointers = {}, pinchStart = 0;
    var raycaster = new THREE.Raycaster();
    var ndc = new THREE.Vector2();
    var dirty = true;

    function designHit(e) {
      var rect = renderer.domElement.getBoundingClientRect();
      ndc.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
      ndc.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
      raycaster.setFromCamera(ndc, camera);
      var hit = raycaster.intersectObjects(built.meshes, false)[0];
      if (!hit || !hit.uv) return null;
      var u = hit.uv.x, v = hit.uv.y;
      var W = (options.front && options.front.width) || 800, H = (options.front && options.front.height) || 800;
      if (built.kind === 'ball') {
        var side = u < 0.5 ? 'front' : 'back';
        var lu = side === 'front' ? u * 2 : (u - 0.5) * 2;
        return { side: side, x: lu * W, y: (1 - v) * H };
      }
      if (hit.object.userData.side) return { side: hit.object.userData.side, x: u * W, y: (1 - v) * H };
      var mi = hit.face ? hit.face.materialIndex : 2;
      if (mi === 1) return null;
      if (mi === 0) return { side: 'back', x: (1 - u) * W, y: (1 - v) * H };
      return { side: 'front', x: u * W, y: (1 - v) * H };
    }

    var el = renderer.domElement;
    if (options.interactive !== false) {
      el.style.cursor = 'grab';
      el.addEventListener('pointerdown', function (e) {
        pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        el.setPointerCapture && el.setPointerCapture(e.pointerId);
        lastInteract = performance.now();
        var ids = Object.keys(pointers);
        if (ids.length === 2) {
          var a = pointers[ids[0]], b = pointers[ids[1]];
          pinchStart = Math.hypot(a.x - b.x, a.y - b.y) / targetZoom;
          dragging = false; draggingElement = false;
          return;
        }
        if (options.onPick) {
          var h = designHit(e);
          if (h && options.onPick(h.side, h.x, h.y)) { draggingElement = true; el.style.cursor = 'grabbing'; return; }
        }
        dragging = true; velY = 0; lastX = e.clientX; lastY = e.clientY; el.style.cursor = 'grabbing';
      });
      el.addEventListener('pointermove', function (e) {
        if (pointers[e.pointerId]) pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
        var ids = Object.keys(pointers);
        if (ids.length === 2 && pinchStart) {
          var a = pointers[ids[0]], b = pointers[ids[1]];
          targetZoom = Math.min(1.9, Math.max(0.7, Math.hypot(a.x - b.x, a.y - b.y) / pinchStart));
          dirty = true;
          return;
        }
        if (draggingElement && options.onDrag) {
          var h = designHit(e);
          if (h) options.onDrag(h.side, h.x, h.y);
          dirty = true;
          return;
        }
        if (!dragging) {
          if (options.onHover) { var hv = designHit(e); el.style.cursor = hv && options.onHover(hv.side, hv.x, hv.y) ? 'move' : 'grab'; }
          return;
        }
        var dx = e.clientX - lastX, dy = e.clientY - lastY;
        lastX = e.clientX; lastY = e.clientY;
        targetY += dx * 0.011; velY = dx * 0.011;
        targetX = Math.max(-0.6, Math.min(0.6, targetX + dy * 0.006));
        lastInteract = performance.now();
        dirty = true;
      });
      function end(e) {
        delete pointers[e.pointerId];
        if (Object.keys(pointers).length < 2) pinchStart = 0;
        if (draggingElement && options.onDragEnd) options.onDragEnd();
        dragging = false; draggingElement = false; el.style.cursor = 'grab';
        lastInteract = performance.now();
      }
      el.addEventListener('pointerup', end);
      el.addEventListener('pointercancel', end);
      el.addEventListener('wheel', function (e) {
        if (!options.wheelZoom && !e.ctrlKey) return; // don't hijack page scroll unless asked
        e.preventDefault();
        targetZoom = Math.min(1.9, Math.max(0.7, targetZoom * (e.deltaY < 0 ? 1.08 : 0.93)));
        lastInteract = performance.now();
        dirty = true;
      }, { passive: false });
    }

    // Hover/scroll parallax for decorative (non-interactive) use, e.g. the hero.
    var parallax = { x: 0, y: 0 };
    function setParallax(x, y) { parallax.x = x; parallax.y = y; dirty = true; }

    // ---- render loop (paused when off-screen or tab hidden) ----
    var visible = true, running = true, rafId = 0, t0 = performance.now();
    if (global.IntersectionObserver) {
      new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; if (visible) loop(); }, { threshold: 0.01 }).observe(mount);
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) loop(); });

    function loop() {
      if (rafId || !running) return;
      rafId = requestAnimationFrame(tick);
    }
    function tick(now) {
      rafId = 0;
      if (!running || !visible || document.hidden) return;
      var t = (now - t0) / 1000;
      var idle = now - lastInteract > 2500;
      if (autoRotate && idle && !dragging && !draggingElement) targetY += 0.0045;
      if (!dragging && Math.abs(velY) > 0.0001) { targetY += velY; velY *= 0.92; }
      rotY += (targetY - rotY) * 0.12;
      rotX += (targetX + parallax.y * 0.25 - rotX) * 0.12;
      zoom += (targetZoom - zoom) * 0.15;
      pivot.rotation.y = rotY + parallax.x * 0.5;
      pivot.rotation.x = rotX;
      if (options.float !== false && !reduceMotion) {
        built.group.position.y = Math.sin(t * 1.3) * 0.06;
        shadow.material.opacity = 0.85 - Math.sin(t * 1.3) * 0.12;
      }
      camera.position.z = baseDistance / zoom;
      camera.lookAt(0, 0, 0);
      renderer.render(scene, camera);
      dirty = false;
      loop();
    }

    resize();
    loop();

    function setView(side) {
      var twoPi = Math.PI * 2;
      var goal = side === 'back' ? Math.PI : 0;
      // Rotate the short way round from wherever the model currently is.
      var cur = targetY % twoPi;
      var delta = ((goal - cur + Math.PI * 3) % twoPi) - Math.PI;
      targetY += delta; targetX = 0.05; velY = 0;
      lastInteract = performance.now() + 4000;
      dirty = true;
    }

    return {
      renderer: renderer,
      scene: scene,
      kind: built.kind,
      refresh: function () {
        if (ballCanvas) { paintBall(); textures.ball.needsUpdate = true; }
        if (textures.front) textures.front.needsUpdate = true;
        if (textures.back) textures.back.needsUpdate = true;
        dirty = true;
      },
      setColors: function (c) {
        if (mats.side && c.base) mats.side.color.set(c.base);
        if (mats.collar && c.collar) mats.collar.color.set(c.collar);
        if (mats.trim && (c.trim || c.collar)) mats.trim.color.set(c.trim || c.collar);
        dirty = true;
      },
      setView: setView,
      setAutoRotate: function (on) { autoRotate = !!on && !reduceMotion; },
      isAutoRotating: function () { return autoRotate; },
      zoomBy: function (f) { targetZoom = Math.min(1.9, Math.max(0.7, targetZoom * f)); lastInteract = performance.now(); },
      resetView: function () { targetZoom = 1; setView('front'); },
      setParallax: setParallax,
      snapshot: function (type) {
        renderer.render(scene, camera);
        return renderer.domElement.toDataURL(type || 'image/png');
      },
      // Renders one clean, straight-on shot of a side (for order records)
      // without disturbing whatever angle the shopper is looking from.
      snapshotSide: function (side, type) {
        var saved = { y: pivot.rotation.y, x: pivot.rotation.x, gy: built.group.position.y, z: camera.position.z };
        pivot.rotation.set(0.04, side === 'back' ? Math.PI + 0.35 : -0.35, 0);
        built.group.position.y = 0;
        camera.position.z = baseDistance;
        camera.lookAt(0, 0, 0);
        renderer.render(scene, camera);
        var url = renderer.domElement.toDataURL(type || 'image/png');
        pivot.rotation.set(saved.x, saved.y, 0);
        built.group.position.y = saved.gy;
        camera.position.z = saved.z;
        return url;
      },
      destroy: function () {
        running = false;
        if (rafId) cancelAnimationFrame(rafId);
        if (ro) ro.disconnect();
        renderer.dispose();
        if (renderer.domElement.parentNode) renderer.domElement.parentNode.removeChild(renderer.domElement);
      },
    };
  }

  global.Garment3D = {
    create: create,
    supported: hasWebGL,
    SHIRT: SHIRT,
    traceShirtPath: traceShirtPath,
    tracePoly: tracePoly,
  };
})(window);
