-- ================================================================
-- BuiltCo Sports — Database Schema
-- Step 1: users (auth) + categories (SEO-ready) + site_settings
-- More tables (products, orders, etc.) will be added in later steps.
-- ================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS builtco_sports_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE builtco_sports_new;

-- ================================================================
-- USERS  (admin + customers share one table, role decides access)
-- ================================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) DEFAULT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at   DATETIME DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- CATEGORIES  (SEO-first: slug, meta title/description, focus keyword,
-- canonical override, alt text on image, nav placement)
-- ================================================================
CREATE TABLE IF NOT EXISTS categories (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(150) NOT NULL,
    slug              VARCHAR(160) NOT NULL UNIQUE,
    parent_id         INT DEFAULT NULL,
    short_description VARCHAR(500) DEFAULT NULL,
    description       TEXT DEFAULT NULL COMMENT 'Long SEO content shown on category page',
    image             VARCHAR(500) DEFAULT NULL,
    image_alt         VARCHAR(255) DEFAULT NULL COMMENT 'alt attribute for category image (SEO)',
    meta_title        VARCHAR(70) DEFAULT NULL,
    meta_description  VARCHAR(160) DEFAULT NULL,
    focus_keyword     VARCHAR(150) DEFAULT NULL,
    canonical_url     VARCHAR(500) DEFAULT NULL COMMENT 'leave empty to use default category URL',
    show_in_nav       TINYINT(1) NOT NULL DEFAULT 1,
    nav_order         INT NOT NULL DEFAULT 0,
    status            ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id),
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- SITE SETTINGS  (key/value store used across admin + storefront)
-- ================================================================
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key    VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value  TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('site_name', 'BuiltCo Sports'),
    ('site_tagline', 'Gear Up. Play Hard.'),
    ('currency_symbol', 'PKR '),
    ('meta_title_suffix', ' | BuiltCo Sports')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ================================================================
-- Default admin login: admin@builtco.com / Admin@123
-- (hash generated with PHP password_hash, bcrypt)
-- ================================================================
INSERT INTO users (first_name, last_name, email, password, role, status)
VALUES ('Admin', 'User', 'admin@builtco.com', '$2y$10$bPMCJcxf0B2gnkAd7ysrFe1G8pa4ILiof6vbgQ.xEg/ZS8W4ijGuC', 'admin', 'active')
ON DUPLICATE KEY UPDATE email = email;
