-- ================================================================
-- Step 12: Integrations + Advanced settings (for the tabbed Settings page)
-- ================================================================
USE builtco_sports_new;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('ga_measurement_id', ''),
    ('fb_pixel_id', ''),
    ('gsc_verification', ''),
    ('custom_header_scripts', ''),
    ('custom_footer_scripts', ''),
    ('robots_txt', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
