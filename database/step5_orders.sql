-- ================================================================
-- Step 5: Orders, order items, and abandoned cart tracking
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS orders (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    order_number      VARCHAR(50) NOT NULL UNIQUE,
    customer_name     VARCHAR(150) NOT NULL,
    customer_email    VARCHAR(255) NOT NULL,
    customer_phone    VARCHAR(50) DEFAULT NULL,
    shipping_address  VARCHAR(500) DEFAULT NULL,
    shipping_city     VARCHAR(100) DEFAULT NULL,
    shipping_country  VARCHAR(100) DEFAULT NULL,
    notes             TEXT DEFAULT NULL,
    subtotal          DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_cost     DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount          DECIMAL(10,2) NOT NULL DEFAULT 0,
    total             DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method    VARCHAR(50) NOT NULL DEFAULT 'cod',
    status            ENUM('pending','paid','processing','shipped','delivered','cancelled','failed','refunded') NOT NULL DEFAULT 'pending',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT NOT NULL,
    product_id     INT DEFAULT NULL,
    product_name   VARCHAR(255) NOT NULL,
    variation_key  VARCHAR(255) DEFAULT NULL,
    sku            VARCHAR(100) DEFAULT NULL,
    price          DECIMAL(10,2) NOT NULL,
    quantity       INT NOT NULL DEFAULT 1,
    subtotal       DECIMAL(10,2) NOT NULL,
    INDEX idx_order (order_id),
    CONSTRAINT fk_orderitem_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_orderitem_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Carts that were started (an item added) but never turned into an order.
CREATE TABLE IF NOT EXISTS abandoned_carts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    session_id     VARCHAR(191) NOT NULL,
    customer_name  VARCHAR(150) DEFAULT NULL,
    email          VARCHAR(255) DEFAULT NULL,
    phone          VARCHAR(50) DEFAULT NULL,
    cart_data      TEXT DEFAULT NULL COMMENT 'JSON snapshot of cart items',
    cart_total     DECIMAL(10,2) NOT NULL DEFAULT 0,
    item_count     INT NOT NULL DEFAULT 0,
    status         ENUM('active','recovered') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('shipping_cost', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
