-- ================================================================
-- Step 4: Simple self-hosted analytics — page visits + product views
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS page_visits (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    url         VARCHAR(500) NOT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    referrer    VARCHAR(500) DEFAULT NULL,
    is_unique   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = first visit of this browser session today',
    visited_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_visited_at (visited_at),
    INDEX idx_url (url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE products ADD COLUMN views INT NOT NULL DEFAULT 0 AFTER stock_quantity;
