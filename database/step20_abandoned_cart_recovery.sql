-- ================================================================
-- Step 20: abandoned cart recovery emails — a one-time reminder sent
-- after N hours of inactivity, with a token that restores the exact
-- cart (since the customer may open the email on a different device
-- / after their original session cookie is gone).
-- ================================================================
USE builtco_sports_new;

SET @col1 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'abandoned_carts' AND COLUMN_NAME = 'reminder_sent_at');
SET @sql1 = IF(@col1 = 0, 'ALTER TABLE abandoned_carts ADD COLUMN reminder_sent_at DATETIME DEFAULT NULL AFTER status', 'SELECT 1');
PREPARE stmt FROM @sql1; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'abandoned_carts' AND COLUMN_NAME = 'restore_token');
SET @sql2 = IF(@col2 = 0, 'ALTER TABLE abandoned_carts ADD COLUMN restore_token VARCHAR(64) DEFAULT NULL AFTER reminder_sent_at, ADD UNIQUE INDEX idx_restore_token (restore_token)', 'SELECT 1');
PREPARE stmt FROM @sql2; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('abandoned_cart_reminder_enabled', '1'),
    ('abandoned_cart_reminder_hours', '3')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
