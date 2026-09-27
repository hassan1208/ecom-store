-- ================================================================
-- Step 25: 360° product spin viewer — an ordered sequence of photos
-- (taken while rotating the product) that the storefront lets shoppers
-- drag through to "spin" the product, in place of a real 3D model.
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS product_spin_frames (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    product_id   INT NOT NULL,
    image_path   VARCHAR(500) NOT NULL,
    frame_order  INT NOT NULL DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    CONSTRAINT fk_spinframe_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
