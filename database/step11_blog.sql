-- ================================================================
-- Step 11: Blog posts (with featured image, SEO, views)
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS blog_posts (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    title               VARCHAR(200) NOT NULL,
    slug                VARCHAR(200) NOT NULL UNIQUE,
    excerpt             VARCHAR(500) DEFAULT NULL,
    content             LONGTEXT DEFAULT NULL,
    featured_image      VARCHAR(500) DEFAULT NULL,
    featured_image_alt  VARCHAR(255) DEFAULT NULL,
    author              VARCHAR(150) DEFAULT NULL,
    meta_title          VARCHAR(70) DEFAULT NULL,
    meta_description    VARCHAR(160) DEFAULT NULL,
    views               INT NOT NULL DEFAULT 0,
    status              ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at        DATETIME DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
