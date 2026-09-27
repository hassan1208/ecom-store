-- ================================================================
-- Step 16: SMTP email settings
-- ================================================================
USE builtco_sports_new;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('smtp_host', ''),
    ('smtp_port', '587'),
    ('smtp_username', ''),
    ('smtp_password', ''),
    ('smtp_encryption', 'tls'),
    ('smtp_from_email', ''),
    ('smtp_from_name', ''),
    ('email_order_confirmation_enabled', '1'),
    ('email_admin_new_order_enabled', '1'),
    ('email_admin_bulk_inquiry_enabled', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
