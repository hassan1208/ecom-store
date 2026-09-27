# BuiltCo Sports — E-commerce Store

PHP + MySQL storefront with a full admin panel, a **3D Design Studio** for custom kits, and built-in technical SEO.

## Setup (XAMPP / any PHP 8.1+ host)

1. Copy the project into your web root (e.g. `htdocs/builtco`). Apache needs `mod_rewrite` (plus `mod_headers`, `mod_expires` and `mod_deflate` if you want the caching and compression rules).
2. Create the database by importing `database/builtco_sports_new.sql`. It already includes the step-29 migration.
   - **Existing install?** Run only `database/step29_3d_studio_and_seo.sql` on your current database.
3. Set your DB credentials at the top of `includes/config.php`.
4. Admin panel: `/admin/`.

Compiled CSS and vendor libraries are committed, so the site runs without Node.js.

## Rebuilding CSS (only after editing templates)

Tailwind is compiled to a static file instead of using the Play CDN. This is faster, avoids layout shift and is better for Core Web Vitals and SEO.

```bash
npm install
npm run build        # storefront CSS + admin CSS + vendor copy
npm run watch:css    # while developing the storefront
```

- Storefront source: `assets/src/site.css` → `assets/css/site.css`
- Admin source: `assets/src/admin.css` → `admin/assets/css/admin.css`
- Brand colours come from **Admin → Settings → Branding** at runtime (CSS variables), so changing them doesn't need a rebuild.

## 3D Design Studio

This works on any customizable product. Open it with the **Customize** button on the product page, or link to `/product/<slug>?customize`.

| Model | Used for | Set in admin |
|---|---|---|
| 3D Jersey | kits, shirts, bibs, uniforms | Product → Product Customizer → 3D model |
| 3D Ball | footballs, futsal, volleyball… | (Auto-detect picks one from the product name) |
| Photo mockup | gloves, bags, helmets, everything else | |

Features:
- Kit presets and body/sleeve/collar colours
- 10 patterns: stripes, pinstripe, hoops, sash, halves, fade, chevron, halftone, camo, ball panels
- Up to 8 logos (drag directly on the 3D model, resize, rotate, layer order, duplicate)
- Arched back names, numbers front and back, 8 fonts, fill and outline colours, extra text lines
- Undo/redo, autosave with restore, and PNG download
- Team roster with CSV paste, adding the whole squad to the cart in one click

What the admin receives per order item (Orders → order detail, and the new-design email):
- photo mockups
- clean 3D renders (front/back)
- **transparent print-ready artwork** (front/back)
- logo vector files
- the full colour/font/pattern spec and customer notes

Settings: **Admin → Settings → 3D & Design** (turn 3D on/off, homepage 3D hero, announcement bar).

Files: `assets/js/garment3d.js` (3D engine), `assets/js/customizer-studio.js` (studio), `includes/studio-markup.php`.

## SEO

- Per-page title, description, canonical, `robots` (max-image-preview), Open Graph (product/article), Twitter cards, hreflang
- JSON-LD:
  - Organization and WebSite + SearchAction
  - Product: brand, GTIN/MPN, offers, shipping details, return policy, aggregate rating, reviews
  - BreadcrumbList on every page
  - ItemList, CollectionPage, BlogPosting
  - FAQPage, detected automatically from FAQ pages and set per category
- Sitemap index (`/sitemap.xml`) split into pages / categories / products / blog, with image sitemaps
- `robots.txt` blocks cart, checkout, account, internal search and filter URLs
- `llms.txt` lists products and articles
- Blog RSS at `/blog/feed`, and `/manifest.webmanifest`
- Filtered/sorted listings are `noindex, follow` with a clean canonical; paginated pages use `rel=prev/next`
- Branded 404 page with a real 404 status
- Bing, Pinterest and Yandex verification tags
- Site-wide "noindex" switch for staging copies
- Self-hosted fonts/icons/JS, versioned assets with 1-year caching, gzip, and security headers (`.htaccess`)

Configure in **Admin → Settings → SEO & Schema**. Per-product brand/GTIN/MPN are on the product edit page, and category FAQs are on the category edit page.

## Useful URLs

`/shop` · `/kit-builder` · `/search?q=` · `/sitemap.xml` · `/robots.txt` · `/llms.txt` · `/blog/feed`
