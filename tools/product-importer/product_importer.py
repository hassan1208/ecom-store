"""
Product Importer — a standalone desktop tool for BuiltCo Sports.

Lets you build up a list of products (typed in directly, or loaded from a
text template) with their images, then insert all of them into the site's
database in one go when you click "Save All to Website".

Run it with:  python product_importer.py
Requires:     pip install pymysql Pillow pdfplumber   (already installed if you followed setup)

See template_example.txt in this folder for the text-template format that
"Load Template..." reads — it also accepts a .pdf written in that same
Name: / Category: / Price: ... format (e.g. a spec sheet you typed in Word
and exported to PDF, or one a supplier sent you in that layout).
"""

import os
import re
import io
import tkinter as tk
from tkinter import ttk, filedialog, messagebox, scrolledtext

import pymysql
from PIL import Image, ImageOps
import pdfplumber

# ============================================================
# CONFIG — matches includes/config.php in the site
# ============================================================
DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASSWORD = ''
DB_NAME = 'builtco_sports_new'

# This script lives in <project>/tools/product-importer/ — the uploads
# folder is two levels up, then into assets/uploads/products/.
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(SCRIPT_DIR, '..', '..'))
UPLOAD_DIR = os.path.join(PROJECT_ROOT, 'assets', 'uploads', 'products')
MAX_IMAGE_DIM = 1000  # matches upload_image($file, 'products', 1000, 1000, 'prod') in PHP

ALLOWED_EXTENSIONS = {'.jpg', '.jpeg', '.png', '.webp', '.gif'}


# ============================================================
# Helpers that mirror includes/config.php's PHP logic exactly,
# so slugs/filenames look identical to ones the website itself creates.
# ============================================================
def generate_slug(text):
    text = (text or '').strip().lower()
    text = re.sub(r'[^a-z0-9\s-]', '', text)
    text = re.sub(r'[\s-]+', '-', text)
    return text.strip('-')


def unique_slug(cursor, table, slug):
    original = slug or 'item'
    slug = original
    counter = 1
    while True:
        cursor.execute(f"SELECT id FROM `{table}` WHERE slug = %s", (slug,))
        if not cursor.fetchone():
            return slug
        slug = f"{original}-{counter}"
        counter += 1


def save_resized_image(src_path, prefix='prod'):
    """Resizes ANY incoming image — tiny or huge, wide or tall, any common
    format — to fit within the site's product-photo box (1000x1000, same as
    upload_image($file, 'products', 1000, 1000, 'prod') in PHP), preserving
    aspect ratio and never upscaling a smaller photo. Saves as WebP into
    assets/uploads/products/ and returns the relative path stored in the DB."""
    os.makedirs(UPLOAD_DIR, exist_ok=True)

    try:
        img = Image.open(src_path)
        img.load()  # force-read now, so a truncated/corrupt file fails here with a clear error
    except Exception as e:
        raise ValueError(f"Not a readable image file: {e}")

    # Phone photos often carry an EXIF "rotate me" tag instead of storing pixels
    # already upright — apply it now, before resizing, or the saved photo could
    # come out sideways on the site regardless of how big/small it started.
    img = ImageOps.exif_transpose(img)

    # WebP only supports RGB/RGBA — flatten any other mode (P, L, CMYK, LA, 1...)
    # into one of those first.
    if img.mode not in ('RGB', 'RGBA'):
        img = img.convert('RGBA') if 'transparency' in img.info or img.mode in ('P', 'LA') else img.convert('RGB')

    # Fit-within box, keep aspect ratio, never enlarge a photo smaller than the
    # box — identical behavior to the site's own resize_image() in PHP.
    if img.width > MAX_IMAGE_DIM or img.height > MAX_IMAGE_DIM:
        img.thumbnail((MAX_IMAGE_DIM, MAX_IMAGE_DIM), Image.LANCZOS)

    filename = f"{prefix}-{os.urandom(6).hex()}.webp"
    dest = os.path.join(UPLOAD_DIR, filename)
    img.save(dest, 'WEBP', quality=85)
    return f"products/{filename}"


# ============================================================
# PDF text extraction — lets a supplier/spec-sheet PDF (or one you typed in
# Word and exported) be read the same way a .txt template is, as long as it
# uses the same Name: / Category: / Price: ... layout.
# ============================================================
def extract_pdf_text(path):
    text_parts = []
    with pdfplumber.open(path) as pdf:
        for page in pdf.pages:
            page_text = page.extract_text() or ''
            text_parts.append(page_text)
    return '\n'.join(text_parts)


# ============================================================
# Template parser (key: value blocks separated by a line of ---) — works on
# raw text pulled from either a .txt file or a PDF.
# ============================================================
def parse_blocks(content):
    blocks = re.split(r'^\s*---\s*$', content, flags=re.MULTILINE)
    products = []
    for block in blocks:
        block = block.strip()
        if not block:
            continue
        fields = {}
        current_key = None
        for line in block.splitlines():
            m = re.match(r'^([A-Za-z ]+):\s?(.*)$', line)
            if m and m.group(1).strip().lower() in (
                'name', 'category', 'sku', 'price', 'sale price', 'stock',
                'tags', 'short description', 'description', 'images',
                'meta title', 'meta description', 'focus keyword'
            ):
                current_key = m.group(1).strip().lower()
                fields[current_key] = m.group(2).strip()
            elif current_key:
                # Continuation line (e.g. a multi-line Description) — append.
                fields[current_key] += ('\n' if fields[current_key] else '') + line
        if not fields.get('name'):
            continue
        images = []
        images_field = fields.get('images', '').strip()
        if images_field and os.path.isdir(images_field):
            for fn in sorted(os.listdir(images_field)):
                if os.path.splitext(fn)[1].lower() in ALLOWED_EXTENSIONS:
                    images.append(os.path.join(images_field, fn))
        products.append({
            'name': fields.get('name', '').strip(),
            'category': fields.get('category', '').strip(),
            'sku': fields.get('sku', '').strip(),
            'price': fields.get('price', '').strip(),
            'sale_price': fields.get('sale price', '').strip(),
            'stock': fields.get('stock', '').strip(),
            'tags': fields.get('tags', '').strip(),
            'short_description': fields.get('short description', '').strip(),
            'description': fields.get('description', '').strip(),
            'meta_title': fields.get('meta title', '').strip(),
            'meta_description': fields.get('meta description', '').strip(),
            'focus_keyword': fields.get('focus keyword', '').strip(),
            'images': images,
            'active': False,
        })
    return products


def parse_template(path):
    """Reads a .txt template directly, or extracts text from a .pdf first —
    either way, the content must follow the Name: / Category: / ... layout."""
    ext = os.path.splitext(path)[1].lower()
    if ext == '.pdf':
        content = extract_pdf_text(path)
    else:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    return parse_blocks(content)


# ============================================================
# Product editor dialog — add or edit one product's details + images
# ============================================================
class ProductDialog(tk.Toplevel):
    def __init__(self, parent, categories, product=None):
        super().__init__(parent)
        self.title('Product' if product is None else f"Edit: {product['name']}")
        self.geometry('540x820')
        self.resizable(False, True)
        self.result = None
        self.images = list(product['images']) if product else []

        pad = {'padx': 12, 'pady': 4}

        tk.Button(self, text='📄 Load Details from PDF...', command=self.load_from_pdf).pack(fill='x', padx=12, pady=(10, 2))
        tk.Label(self, text="Reads a spec-sheet PDF written in the Name: / Price: / ... layout and fills in the fields below — add images separately.", wraplength=490, fg='#666', justify='left').pack(fill='x', padx=12, pady=(0, 8))

        tk.Label(self, text='Name *', anchor='w').pack(fill='x', **pad)
        self.name_var = tk.StringVar(value=product['name'] if product else '')
        tk.Entry(self, textvariable=self.name_var).pack(fill='x', **pad)

        tk.Label(self, text='Category (pick existing or type a new one)', anchor='w').pack(fill='x', **pad)
        self.category_var = tk.StringVar(value=product['category'] if product else '')
        ttk.Combobox(self, textvariable=self.category_var, values=categories).pack(fill='x', **pad)

        row = tk.Frame(self); row.pack(fill='x', **pad)
        tk.Label(row, text='SKU').pack(side='left')
        self.sku_var = tk.StringVar(value=product['sku'] if product else '')
        tk.Entry(row, textvariable=self.sku_var, width=18).pack(side='left', padx=(6, 20))
        tk.Label(row, text='Stock').pack(side='left')
        self.stock_var = tk.StringVar(value=product['stock'] if product else '0')
        tk.Entry(row, textvariable=self.stock_var, width=10).pack(side='left', padx=6)

        row2 = tk.Frame(self); row2.pack(fill='x', **pad)
        tk.Label(row2, text='Price *').pack(side='left')
        self.price_var = tk.StringVar(value=product['price'] if product else '')
        tk.Entry(row2, textvariable=self.price_var, width=14).pack(side='left', padx=(6, 20))
        tk.Label(row2, text='Sale Price').pack(side='left')
        self.sale_price_var = tk.StringVar(value=product['sale_price'] if product else '')
        tk.Entry(row2, textvariable=self.sale_price_var, width=14).pack(side='left', padx=6)

        tk.Label(self, text='Tags (comma separated)', anchor='w').pack(fill='x', **pad)
        self.tags_var = tk.StringVar(value=product['tags'] if product else '')
        tk.Entry(self, textvariable=self.tags_var).pack(fill='x', **pad)

        tk.Label(self, text='Short Description', anchor='w').pack(fill='x', **pad)
        self.short_desc_var = tk.StringVar(value=product['short_description'] if product else '')
        tk.Entry(self, textvariable=self.short_desc_var).pack(fill='x', **pad)

        tk.Label(self, text='Full Description', anchor='w').pack(fill='x', **pad)
        self.desc_text = tk.Text(self, height=5)
        self.desc_text.pack(fill='x', **pad)
        if product:
            self.desc_text.insert('1.0', product['description'])

        tk.Label(self, text='SEO', anchor='w', font=('', 9, 'bold')).pack(fill='x', **pad)
        tk.Label(self, text='Meta Title (ideal ≤60 chars)', anchor='w').pack(fill='x', **pad)
        self.meta_title_var = tk.StringVar(value=product.get('meta_title', '') if product else '')
        tk.Entry(self, textvariable=self.meta_title_var).pack(fill='x', **pad)
        tk.Label(self, text='Meta Description (ideal ≤155 chars)', anchor='w').pack(fill='x', **pad)
        self.meta_desc_var = tk.StringVar(value=product.get('meta_description', '') if product else '')
        tk.Entry(self, textvariable=self.meta_desc_var).pack(fill='x', **pad)
        tk.Label(self, text='Focus Keyword', anchor='w').pack(fill='x', **pad)
        self.focus_keyword_var = tk.StringVar(value=product.get('focus_keyword', '') if product else '')
        tk.Entry(self, textvariable=self.focus_keyword_var).pack(fill='x', **pad)

        self.active_var = tk.BooleanVar(value=product['active'] if product else False)
        tk.Checkbutton(self, text='Publish immediately (unchecked = saved as draft)', variable=self.active_var).pack(anchor='w', **pad)

        tk.Label(self, text='Images', anchor='w', font=('', 9, 'bold')).pack(fill='x', **pad)
        img_frame = tk.Frame(self); img_frame.pack(fill='both', expand=True, **pad)
        self.img_listbox = tk.Listbox(img_frame, height=6)
        self.img_listbox.pack(side='left', fill='both', expand=True)
        for p in self.images:
            self.img_listbox.insert('end', os.path.basename(p))
        btns = tk.Frame(img_frame); btns.pack(side='left', padx=(8, 0))
        tk.Button(btns, text='Add Images...', command=self.add_images).pack(fill='x', pady=2)
        tk.Button(btns, text='Add Folder...', command=self.add_folder).pack(fill='x', pady=2)
        tk.Button(btns, text='Remove Selected', command=self.remove_image).pack(fill='x', pady=2)

        save_row = tk.Frame(self); save_row.pack(fill='x', pady=12)
        tk.Button(save_row, text='Cancel', command=self.destroy).pack(side='right', padx=12)
        tk.Button(save_row, text='Save Product', command=self.on_save, bg='#ff4d2e', fg='white').pack(side='right')

    def load_from_pdf(self):
        path = filedialog.askopenfilename(title='Select a product spec-sheet PDF', filetypes=[('PDF files', '*.pdf')], parent=self)
        if not path:
            return
        try:
            text = extract_pdf_text(path)
            blocks = parse_blocks(text)
        except Exception as e:
            messagebox.showerror('Could not read PDF', str(e), parent=self)
            return

        if blocks:
            # Structured PDF (follows the Name: / Price: ... layout) — fill every field it found.
            p = blocks[0]
            self.name_var.set(p['name'])
            self.category_var.set(p['category'])
            self.sku_var.set(p['sku'])
            self.price_var.set(p['price'])
            self.sale_price_var.set(p['sale_price'])
            self.stock_var.set(p['stock'] or '0')
            self.tags_var.set(p['tags'])
            self.short_desc_var.set(p['short_description'])
            self.desc_text.delete('1.0', 'end')
            self.desc_text.insert('1.0', p['description'])
            self.meta_title_var.set(p['meta_title'])
            self.meta_desc_var.set(p['meta_description'])
            self.focus_keyword_var.set(p['focus_keyword'])
            if len(blocks) > 1:
                messagebox.showinfo(
                    'Multiple products found',
                    f"This PDF had {len(blocks)} product blocks — only the first one was loaded here. "
                    "Use \"Load Template...\" on the main window instead to import all of them at once.",
                    parent=self
                )
        else:
            # Unstructured PDF — don't lose the text. Drop it into Description
            # and let the shopper fill in Name/Price themselves.
            self.desc_text.delete('1.0', 'end')
            self.desc_text.insert('1.0', text.strip())
            if not self.name_var.get().strip():
                self.name_var.set(os.path.splitext(os.path.basename(path))[0])
            messagebox.showinfo(
                'Loaded as plain text',
                "This PDF didn't follow the Name: / Price: ... layout, so its text was placed in "
                "Description for you to tidy up — please fill in Name/Price/Category yourself.",
                parent=self
            )

    def add_images(self):
        paths = filedialog.askopenfilenames(title='Select images', filetypes=[('Images', '*.jpg *.jpeg *.png *.webp *.gif')])
        for p in paths:
            self.images.append(p)
            self.img_listbox.insert('end', os.path.basename(p))

    def add_folder(self):
        folder = filedialog.askdirectory(title='Select a folder of images')
        if not folder:
            return
        for fn in sorted(os.listdir(folder)):
            if os.path.splitext(fn)[1].lower() in ALLOWED_EXTENSIONS:
                p = os.path.join(folder, fn)
                self.images.append(p)
                self.img_listbox.insert('end', os.path.basename(p))

    def remove_image(self):
        sel = list(self.img_listbox.curselection())
        for i in reversed(sel):
            self.img_listbox.delete(i)
            del self.images[i]

    def on_save(self):
        name = self.name_var.get().strip()
        if not name:
            messagebox.showerror('Missing name', 'Please enter a product name.', parent=self)
            return
        price = self.price_var.get().strip()
        try:
            float(price) if price else 0.0
        except ValueError:
            messagebox.showerror('Invalid price', 'Price must be a number.', parent=self)
            return
        self.result = {
            'name': name,
            'category': self.category_var.get().strip(),
            'sku': self.sku_var.get().strip(),
            'price': price,
            'sale_price': self.sale_price_var.get().strip(),
            'stock': self.stock_var.get().strip() or '0',
            'tags': self.tags_var.get().strip(),
            'short_description': self.short_desc_var.get().strip(),
            'description': self.desc_text.get('1.0', 'end').strip(),
            'meta_title': self.meta_title_var.get().strip(),
            'meta_description': self.meta_desc_var.get().strip(),
            'focus_keyword': self.focus_keyword_var.get().strip(),
            'images': self.images,
            'active': self.active_var.get(),
        }
        self.destroy()


# ============================================================
# Main application window
# ============================================================
class ImporterApp(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title('BuiltCo Sports — Product Importer')
        self.geometry('820x560')
        self.products = []  # list of product dicts (same shape as ProductDialog.result)

        toolbar = tk.Frame(self, pady=8)
        toolbar.pack(fill='x', padx=10)
        tk.Button(toolbar, text='Load Template...', command=self.load_template).pack(side='left', padx=4)
        tk.Button(toolbar, text='+ New Product', command=self.new_product).pack(side='left', padx=4)
        tk.Button(toolbar, text='Edit Selected', command=self.edit_selected).pack(side='left', padx=4)
        tk.Button(toolbar, text='Remove Selected', command=self.remove_selected).pack(side='left', padx=4)
        tk.Button(toolbar, text='Save All to Website', command=self.save_all, bg='#ff4d2e', fg='white').pack(side='right', padx=4)

        columns = ('name', 'category', 'price', 'images', 'status')
        self.tree = ttk.Treeview(self, columns=columns, show='headings', height=14)
        for col, label, w in [('name', 'Name', 220), ('category', 'Category', 140), ('price', 'Price', 90), ('images', 'Images', 70), ('status', 'Publish As', 100)]:
            self.tree.heading(col, text=label)
            self.tree.column(col, width=w)
        self.tree.pack(fill='both', expand=True, padx=10, pady=4)
        self.tree.bind('<Double-1>', lambda e: self.edit_selected())

        tk.Label(self, text='Log', anchor='w').pack(fill='x', padx=10)
        self.log = scrolledtext.ScrolledText(self, height=8, state='disabled')
        self.log.pack(fill='both', padx=10, pady=(0, 10))

        self.categories = self.fetch_categories()

    def log_line(self, text):
        self.log.configure(state='normal')
        self.log.insert('end', text + '\n')
        self.log.see('end')
        self.log.configure(state='disabled')
        self.update_idletasks()

    def fetch_categories(self):
        try:
            conn = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME)
            with conn.cursor() as cur:
                cur.execute("SELECT name FROM categories ORDER BY name")
                names = [r[0] for r in cur.fetchall()]
            conn.close()
            return names
        except Exception as e:
            self.log_line(f"Could not load categories (will still work — just no autocomplete): {e}")
            return []

    def refresh_tree(self):
        self.tree.delete(*self.tree.get_children())
        for i, p in enumerate(self.products):
            self.tree.insert('', 'end', iid=str(i), values=(
                p['name'], p['category'] or '—', p['price'] or '0', len(p['images']),
                'Active' if p['active'] else 'Draft'
            ))

    def load_template(self):
        path = filedialog.askopenfilename(
            title='Select template file',
            filetypes=[('Template files', '*.txt *.pdf'), ('Text files', '*.txt'), ('PDF files', '*.pdf')]
        )
        if not path:
            return
        try:
            parsed = parse_template(path)
        except Exception as e:
            messagebox.showerror('Could not read template', str(e))
            return
        if not parsed:
            messagebox.showwarning(
                'No products found',
                "Couldn't find any recognizable \"Name: ...\" fields in that file.\n\n"
                "If it's a PDF, make sure it's text (not a scanned image) and follows "
                "the Name: / Category: / Price: ... layout from template_example.txt."
            )
            return
        self.products.extend(parsed)
        self.refresh_tree()
        self.log_line(f"Loaded {len(parsed)} product(s) from template.")

    def new_product(self):
        dlg = ProductDialog(self, self.categories)
        self.wait_window(dlg)
        if dlg.result:
            self.products.append(dlg.result)
            self.refresh_tree()

    def edit_selected(self):
        sel = self.tree.selection()
        if not sel:
            return
        idx = int(sel[0])
        dlg = ProductDialog(self, self.categories, product=self.products[idx])
        self.wait_window(dlg)
        if dlg.result:
            self.products[idx] = dlg.result
            self.refresh_tree()

    def remove_selected(self):
        sel = self.tree.selection()
        for iid in sorted(sel, key=int, reverse=True):
            del self.products[int(iid)]
        self.refresh_tree()

    def save_all(self):
        if not self.products:
            messagebox.showinfo('Nothing to save', 'Add at least one product first.')
            return
        if not messagebox.askyesno('Confirm', f"Insert {len(self.products)} product(s) into the website database now?"):
            return

        try:
            conn = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME, autocommit=False)
        except Exception as e:
            messagebox.showerror('Database connection failed', str(e))
            return

        ok_count = 0
        fail_count = 0
        try:
            with conn.cursor() as cur:
                for p in self.products:
                    try:
                        category_id = None
                        if p['category']:
                            cur.execute("SELECT id FROM categories WHERE LOWER(name)=LOWER(%s)", (p['category'],))
                            row = cur.fetchone()
                            if row:
                                category_id = row[0]
                            else:
                                cat_slug = unique_slug(cur, 'categories', generate_slug(p['category']))
                                cur.execute(
                                    "INSERT INTO categories (name, slug, status) VALUES (%s, %s, 'active')",
                                    (p['category'], cat_slug)
                                )
                                category_id = cur.lastrowid

                        slug = unique_slug(cur, 'products', generate_slug(p['name']))
                        price = float(p['price']) if p['price'] else 0.0
                        sale_price = float(p['sale_price']) if p['sale_price'] else None
                        stock = int(p['stock']) if p['stock'] else 0

                        cur.execute(
                            """INSERT INTO products
                               (name, slug, sku, category_id, short_description, description, tags,
                                status, base_price, sale_price, stock_quantity,
                                meta_title, meta_description, focus_keyword)
                               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                            (p['name'], slug, p['sku'] or None, category_id,
                             p['short_description'] or None, p['description'] or None, p['tags'] or None,
                             'active' if p['active'] else 'draft', price, sale_price, stock,
                             p.get('meta_title') or None, p.get('meta_description') or None, p.get('focus_keyword') or None)
                        )
                        product_id = cur.lastrowid

                        for i, img_path in enumerate(p['images']):
                            try:
                                rel_path = save_resized_image(img_path)
                                cur.execute(
                                    """INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary)
                                       VALUES (%s, %s, %s, %s, %s)""",
                                    (product_id, rel_path, p['name'], i, 1 if i == 0 else 0)
                                )
                            except Exception as img_err:
                                self.log_line(f"  ⚠ {p['name']}: image '{os.path.basename(img_path)}' failed — {img_err}")

                        conn.commit()
                        ok_count += 1
                        self.log_line(f"✔ Created: {p['name']} ({len(p['images'])} image(s), {'active' if p['active'] else 'draft'})")
                    except Exception as prod_err:
                        conn.rollback()
                        fail_count += 1
                        self.log_line(f"✘ Failed: {p['name']} — {prod_err}")
        finally:
            conn.close()

        self.log_line(f"Done. {ok_count} created, {fail_count} failed.")
        messagebox.showinfo('Import complete', f"{ok_count} product(s) created, {fail_count} failed.\nSee the log for details.")
        if ok_count:
            self.products = []
            self.refresh_tree()


if __name__ == '__main__':
    app = ImporterApp()
    app.mainloop()
