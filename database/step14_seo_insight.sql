-- ================================================================
-- Step 14: SEO Insight (GSC property URL — verification code already exists)
-- ================================================================
USE builtco_sports_new;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('gsc_property_url', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
