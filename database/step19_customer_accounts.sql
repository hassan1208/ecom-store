-- ================================================================
-- Step 19: customer accounts — reuses the existing `users` table
-- (already has role ENUM('customer','admin') from step 1, just never
-- used for customer registration yet). Guest checkout stays available;
-- an account is optional and, when logged in, links orders to it.
-- ================================================================
USE builtco_sports_new;

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_id'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE orders ADD COLUMN customer_id INT DEFAULT NULL AFTER customer_phone, ADD INDEX idx_customer_id (customer_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
