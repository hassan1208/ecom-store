-- ================================================================
-- Step 23: Product Customizer (color-matched photos, logo upload,
-- custom number/text) — customer-facing customizer + order record.
-- ================================================================
USE builtco_sports_new;

ALTER TABLE products
    ADD COLUMN is_customizable TINYINT(1) NOT NULL DEFAULT 0 AFTER show_bulk_dm;

-- Lets an admin tag a gallery photo with the variation option value it depicts
-- (e.g. "Red"), so the customizer can swap the base photo when the shopper
-- picks a different color, instead of always showing one fixed photo.
ALTER TABLE product_images
    ADD COLUMN variation_value VARCHAR(100) DEFAULT NULL AFTER alt_text;

-- JSON snapshot of what the shopper customized (logo path, text/number, color,
-- composited preview image path) so fulfillment can see exactly what to print
-- without depending on the session cart, which is gone by checkout.
ALTER TABLE order_items
    ADD COLUMN customization TEXT DEFAULT NULL AFTER sku;
