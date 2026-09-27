BuiltCo Sports — Product Importer
==================================

A small desktop tool (not part of the website) that lets you type up several
products — with their images — and insert them all into the site's database
at once.

HOW TO RUN
----------
1. Open a terminal in this folder (tools\product-importer).
2. Run:  python product_importer.py
3. A window opens. From there you can either:
     - Click "+ New Product" and fill in the form (pick images with
       "Add Images..." or "Add Folder..."), then "Save Product". Inside
       that form, "Load Details from PDF..." lets you pick a spec-sheet PDF
       and auto-fill Name/Price/Description/etc. from it — see PDF SUPPORT
       below. Images are always added separately, never pulled from a PDF.
     - Click "Load Template..." on the main window and pick a .txt OR .pdf
       file in the format below — this adds several products at once (still
       add their images per product afterward with "Edit Selected", unless
       the template's "Images:" line already points at a folder).
4. Repeat until every product you want is listed.
5. Click "Save All to Website" — this inserts everything into the database
   and copies/resizes the images into the site's assets\uploads\products\
   folder, exactly like uploading through the admin panel would.
6. New products are saved as DRAFTS by default (not visible on the site)
   unless you tick "Publish immediately" for that product — so it's safe to
   review them afterward in Admin -> Products before they go live.

TEMPLATE FILE FORMAT
---------------------
Plain text, one product per block, separated by a line containing only ---.
See template_example.txt in this folder for a working example. Fields:

  Name:               required. The product's name.
  Category:           optional. Matched by name (case-insensitive) against
                       existing categories, or created if it doesn't exist.
  SKU:                 optional.
  Price:               the regular price (just the number, no currency symbol).
  Sale Price:          optional discounted price.
  Stock:               optional whole number, defaults to 0.
  Tags:                optional, comma separated.
  Short Description:   optional, one line.
  Description:         optional, can span multiple lines (everything after
                       this label, up to the next field or the --- divider,
                       is included).
  Images:              optional. A folder path on your computer — every
                       image file in that folder is attached to this product
                       in filename order. Leave blank to add images manually
                       in the app instead.
  Meta Title:           optional. SEO page title, ideally 60 characters or
                       less (70 max) — this is what shows up as the blue
                       link in Google search results.
  Meta Description:    optional. SEO summary, ideally 155 characters or
                       less (160 max) — this is the grey snippet text under
                       the title in Google search results.
  Focus Keyword:        optional. The main search phrase this product should
                       rank for (used by the site's own SEO checker).

Example block:

  Name: Pro Boxing Gloves 12oz
  Category: Boxing
  SKU: BG-012
  Price: 4500
  Sale Price: 3999
  Stock: 20
  Tags: boxing, gloves, leather
  Short Description: Premium leather boxing gloves built for serious training.
  Description: Genuine leather shell with multi-layer foam padding...
  Meta Title: Pro Boxing Gloves 12oz | BuiltCo Sports
  Meta Description: Shop 12oz genuine leather boxing gloves with reinforced
    wrist support, built for serious training. Fast nationwide delivery.
  Focus Keyword: boxing gloves 12oz
  Images: C:\Users\ULC\Pictures\BoxingGloves
  ---

PDF SUPPORT
-----------
Anywhere you'd pick a .txt template, you can pick a .pdf instead — the tool
reads its text and parses it exactly the same way. This covers two cases:

  1. A PDF that follows the Name: / Category: / Price: ... layout above
     (e.g. you typed the template in Word and exported it, or a supplier
     sent you a spec sheet already in that layout). All matching fields are
     read straight in, no retyping needed.
  2. A PDF that DOESN'T follow that layout (a generic spec sheet, catalog
     page, etc.). Its text still isn't lost — in the "+ New Product" form,
     "Load Details from PDF..." drops the raw text into Description and
     fills Name from the PDF's filename, so you can quickly clean it up
     instead of retyping from scratch. ("Load Template..." on the main
     window only accepts the structured layout, since it needs to tell
     where one product ends and the next begins.)

The PDF must contain real text, not a scanned photo of a page — scanned/
image-only PDFs have no text for the tool to read.

GENERATING THIS CONTENT WITH CLAUDE
------------------------------------
See claude_prompt_template.txt in this folder — it's a ready-to-paste prompt
you can hand to Claude (with a product's raw name/specs) to get back a block
in exactly this format, including an SEO-optimized title, description, and a
recommended Category. Claude only *recommends* the category — nothing is
created automatically until you run it through this tool, so you stay in
control of your category list.

NOTES
-----
- This tool connects directly to the same MySQL database the website uses
  (builtco_sports_new on localhost), so XAMPP's MySQL must be running.
- It needs three Python packages: pymysql, Pillow, and pdfplumber. If any are
  missing, run:
      python -m pip install pymysql Pillow pdfplumber
- Categories are matched by name only — "Boxing" and "boxing" are treated as
  the same category.
- IMAGES — you can hand it any size or shape of photo and it always comes out
  right for the site:
    - Resized to fit within 1000x1000px (same box the admin panel itself
      uses), keeping its original proportions — a 3000x100 banner-shaped
      photo or a 4000x3000 camera photo both come out correctly, not
      squashed or cropped.
    - Never enlarged — a small 50x50 image stays 50x50 rather than getting
      blurry from being stretched up.
    - Phone photos that are stored sideways (with an EXIF "rotate me" tag)
      are automatically turned upright before resizing, so they don't show
      up sideways on the site.
    - Always saved as WebP (smaller file size, faster page loads) regardless
      of whether you gave it a JPG, PNG, GIF, or WebP to start with.
  This was tested against tiny/huge/very-wide/very-tall/transparent-PNG/
  sideways-phone-photo cases and every one comes out correctly sized.
