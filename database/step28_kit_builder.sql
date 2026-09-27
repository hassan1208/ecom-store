-- ================================================================
-- Step 28: Kit Builder — a standalone customizer (separate from the
-- per-product "Customize Yours" panel) for full team kits: jersey
-- front, jersey back, AND shorts, each with their own logo placement.
-- A product qualifies for the Kit Builder's picker automatically once
-- it has a photo tagged "shorts".
-- ================================================================
USE builtco_sports_new;

ALTER TABLE product_images
    MODIFY COLUMN mockup_view ENUM('front','back','sleeve','shorts') DEFAULT NULL;
