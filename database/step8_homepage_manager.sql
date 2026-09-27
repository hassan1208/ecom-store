-- ================================================================
-- Step 8: Homepage Manager — section toggles, testimonials, newsletter,
-- promo banners (reuses banners table), coming-soon product flag
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS testimonials (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    role        VARCHAR(150) DEFAULT NULL COMMENT 'e.g. job title / company',
    quote       TEXT NOT NULL,
    rating      TINYINT NOT NULL DEFAULT 5,
    photo       VARCHAR(500) DEFAULT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255) NOT NULL UNIQUE,
    status      ENUM('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE banners ADD COLUMN placement ENUM('hero','promo') NOT NULL DEFAULT 'hero' AFTER button_url;
ALTER TABLE products ADD COLUMN is_coming_soon TINYINT(1) NOT NULL DEFAULT 0 AFTER is_new_arrival;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('section_hero_enabled', '1'),
    ('section_trustbar_enabled', '1'),
    ('section_category_enabled', '1'),
    ('section_category_title', 'Shop by Category'),
    ('homepage_category_ids', ''),
    ('section_new_arrivals_enabled', '1'),
    ('section_new_arrivals_title', 'New Arrivals'),
    ('section_featured_enabled', '1'),
    ('section_featured_title', 'Featured Products'),
    ('section_promo_enabled', '1'),
    ('section_bestsellers_enabled', '1'),
    ('section_bestsellers_title', 'Best Sellers'),
    ('section_comingsoon_enabled', '1'),
    ('section_comingsoon_title', 'Coming Soon'),
    ('section_why_enabled', '1'),
    ('section_why_title', 'Why Choose Us'),
    ('section_testimonials_enabled', '1'),
    ('section_testimonials_title', 'What Our Customers Say'),
    ('section_newsletter_enabled', '1'),
    ('section_newsletter_title', 'Join Our Newsletter'),
    ('section_newsletter_subtitle', 'Get exclusive deals and updates delivered to your inbox')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
