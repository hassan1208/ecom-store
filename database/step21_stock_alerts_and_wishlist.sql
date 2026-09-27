-- ================================================================
-- Step 21: back-in-stock email alerts + customer wishlists
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS stock_notifications (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    product_id    INT NOT NULL,
    variation_key VARCHAR(255) DEFAULT NULL COMMENT 'NULL = whole (simple) product, else matches product_variations.combination_key',
    email         VARCHAR(255) NOT NULL,
    notified_at   DATETIME DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_pending (product_id, variation_key, notified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wishlists (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id  INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_customer_product (customer_id, product_id),
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
