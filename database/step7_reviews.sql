-- ================================================================
-- Step 7: Product reviews
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS reviews (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    product_id     INT NOT NULL,
    customer_name  VARCHAR(150) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    rating         TINYINT NOT NULL,
    title          VARCHAR(200) DEFAULT NULL,
    comment        TEXT DEFAULT NULL,
    is_verified    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = reviewer has a completed order containing this product',
    status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_status (status),
    CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
