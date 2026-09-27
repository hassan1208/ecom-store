-- ================================================================
-- Step 9: Static content pages (About, Privacy Policy, Terms, FAQ, etc.)
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS pages (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    title             VARCHAR(200) NOT NULL,
    slug              VARCHAR(200) NOT NULL UNIQUE,
    content           LONGTEXT DEFAULT NULL,
    meta_title        VARCHAR(70) DEFAULT NULL,
    meta_description  VARCHAR(160) DEFAULT NULL,
    status            ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
