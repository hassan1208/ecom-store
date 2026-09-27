-- ================================================================
-- Step 10: Banner image + side image for static pages (cropped-to-fill)
-- ================================================================
USE builtco_sports_new;

ALTER TABLE pages
    ADD COLUMN banner_image VARCHAR(500) DEFAULT NULL AFTER content,
    ADD COLUMN banner_image_alt VARCHAR(255) DEFAULT NULL AFTER banner_image,
    ADD COLUMN side_image VARCHAR(500) DEFAULT NULL AFTER banner_image_alt,
    ADD COLUMN side_image_alt VARCHAR(255) DEFAULT NULL AFTER side_image;
