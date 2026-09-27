-- ================================================================
-- Step 24: AI Design Requests — customer uploads a reference image,
-- admin vectorizes it and generates front/back/sleeve mockups.
-- ================================================================
USE builtco_sports_new;

ALTER TABLE products
    ADD COLUMN accepts_design_requests TINYINT(1) NOT NULL DEFAULT 0 AFTER is_customizable;

-- Lets an admin tag a product photo as a blank mockup template for a given
-- garment view, so the mockup generator knows which photo to place the
-- customer's vectorized design onto for that view.
ALTER TABLE product_images
    ADD COLUMN mockup_view ENUM('front','back','sleeve') DEFAULT NULL AFTER variation_value;

CREATE TABLE IF NOT EXISTS design_requests (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    product_id          INT DEFAULT NULL,
    product_name        VARCHAR(255) DEFAULT NULL COMMENT 'snapshot, survives product deletion',
    customer_name       VARCHAR(150) NOT NULL,
    email               VARCHAR(255) NOT NULL,
    phone               VARCHAR(50) DEFAULT NULL,
    message              TEXT DEFAULT NULL,
    original_image_path VARCHAR(500) NOT NULL,
    vector_svg_path     VARCHAR(500) DEFAULT NULL,
    mockup_front_path   VARCHAR(500) DEFAULT NULL,
    mockup_back_path    VARCHAR(500) DEFAULT NULL,
    mockup_sleeve_path  VARCHAR(500) DEFAULT NULL,
    status              ENUM('new','vectorized','mockup_ready','approved','rejected') NOT NULL DEFAULT 'new',
    admin_notes         TEXT DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    CONSTRAINT fk_designreq_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
