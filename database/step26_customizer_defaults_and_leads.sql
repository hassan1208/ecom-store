-- ================================================================
-- Step 26: Customizer on by default for every product, and admin gets
-- notified by email the moment a customer finishes a customization
-- (not only after they complete checkout) — includes the customer's
-- email and a click-to-chat WhatsApp link.
-- ================================================================
USE builtco_sports_new;

ALTER TABLE products MODIFY COLUMN is_customizable TINYINT(1) NOT NULL DEFAULT 1;
UPDATE products SET is_customizable = 1 WHERE status = 'active';

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('email_admin_new_customization_enabled', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
