-- ================================================================
-- Step 6: Payment methods (PayFast + Remitly), coupons, order additions
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS payment_methods (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    code                  VARCHAR(50) NOT NULL UNIQUE,
    name                  VARCHAR(100) NOT NULL,
    instructions          TEXT DEFAULT NULL COMMENT 'Shown to the customer at checkout',
    youtube_url           VARCHAR(500) DEFAULT NULL COMMENT 'Tutorial video on how to pay',
    account_details       TEXT DEFAULT NULL COMMENT 'e.g. account name/number to send payment to',
    requires_screenshot   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Customer must upload proof of payment',
    status                ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
    sort_order            INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO payment_methods (code, name, instructions, requires_screenshot, status, sort_order) VALUES
    ('payfast', 'PayFast', 'Pay securely online via PayFast.', 0, 'inactive', 1),
    ('remitly', 'Remitly', 'Send your payment via Remitly, then upload a screenshot of the confirmation below.', 1, 'active', 2)
ON DUPLICATE KEY UPDATE code = code;

CREATE TABLE IF NOT EXISTS coupons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(50) NOT NULL UNIQUE,
    type        ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    value       DECIMAL(10,2) NOT NULL,
    min_order   DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_uses    INT DEFAULT NULL,
    used_count  INT NOT NULL DEFAULT 0,
    expires_at  DATE DEFAULT NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE orders
    ADD COLUMN payment_screenshot VARCHAR(500) DEFAULT NULL AFTER payment_method,
    ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL AFTER discount;

-- PayFast merchant credentials (fill in once a real merchant account is ready)
INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('payfast_merchant_id', ''),
    ('payfast_merchant_key', ''),
    ('payfast_passphrase', ''),
    ('payfast_mode', 'sandbox')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
