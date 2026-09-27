-- ================================================================
-- Step 18: order status update email toggle (customer gets notified
-- when an admin changes an order's status, not just at checkout)
-- ================================================================
USE builtco_sports_new;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('email_order_status_update_enabled', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
