// Copies third-party browser libraries from node_modules into assets/vendor so
// the storefront and admin never depend on a public CDN being reachable.
const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '..');
const nm = (p) => path.join(root, 'node_modules', p);
const out = (p) => path.join(root, 'assets', 'vendor', p);

function copy(src, dest) {
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.copyFileSync(src, dest);
}

copy(nm('three/build/three.min.js'), out('three/three.min.js'));
copy(nm('@fortawesome/fontawesome-free/css/all.min.css'), out('fontawesome/css/all.min.css'));
for (const f of fs.readdirSync(nm('@fortawesome/fontawesome-free/webfonts'))) {
  if (f.endsWith('.woff2')) copy(nm('@fortawesome/fontawesome-free/webfonts/' + f), out('fontawesome/webfonts/' + f));
}
// FA's CSS also lists .ttf fallbacks; every browser we support loads woff2 first.
const faCss = out('fontawesome/css/all.min.css');
fs.writeFileSync(faCss, fs.readFileSync(faCss, 'utf8').replace(/,url\(\.\.\/webfonts\/[^)]+\.ttf\) format\("truetype"\)/g, ''));
console.log('Vendor files copied to assets/vendor/');

copy(nm('quill/dist/quill.min.js'), out('quill/quill.min.js'));
copy(nm('quill/dist/quill.snow.css'), out('quill/quill.snow.css'));
