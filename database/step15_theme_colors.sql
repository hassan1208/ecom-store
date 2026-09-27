-- ================================================================
-- Step 15: Storefront theme colors (admin-editable)
-- ================================================================
USE builtco_sports_new;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('theme_primary_color', '#ff4d2e'),
    ('theme_primary_dark',  '#e0391d'),
    ('theme_ink_color',     '#0b0f14')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
