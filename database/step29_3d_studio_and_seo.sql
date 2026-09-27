-- ================================================================
-- Step 29: 3D Design Studio + extended SEO settings
--   * products.customizer_model   — which 3D model the studio uses
--       auto   = detect from product/category name (jersey, ball, else card)
--       jersey = 3D shirt, ball = 3D ball, flat = 3D photo card
--   * products.customizer_base_color — starting kit colour in the studio
--   * products.brand / gtin / mpn — richer Product structured data
--   * site_settings for SEO / merchant listings / 3D toggles
-- Safe to run more than once on MariaDB 10.4+ (IF NOT EXISTS).
-- ================================================================
USE builtco_sports_new;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS customizer_model ENUM('auto','jersey','ball','flat') NOT NULL DEFAULT 'auto' AFTER is_customizable,
    ADD COLUMN IF NOT EXISTS customizer_base_color VARCHAR(7) DEFAULT NULL AFTER customizer_model,
    ADD COLUMN IF NOT EXISTS brand VARCHAR(120) DEFAULT NULL AFTER sku,
    ADD COLUMN IF NOT EXISTS gtin VARCHAR(20) DEFAULT NULL AFTER brand,
    ADD COLUMN IF NOT EXISTS mpn VARCHAR(64) DEFAULT NULL AFTER gtin;

ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS faq TEXT DEFAULT NULL;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('seo_default_brand', ''),
    ('seo_twitter_handle', ''),
    ('seo_business_type', 'Organization'),
    ('seo_founding_year', ''),
    ('seo_price_valid_days', '365'),
    ('seo_return_days', '14'),
    ('seo_return_fees', 'FreeReturn'),
    ('seo_shipping_country', 'PK'),
    ('seo_handling_days_max', '3'),
    ('seo_transit_days_max', '7'),
    ('seo_noindex_site', '0'),
    ('seo_bing_verification', ''),
    ('seo_pinterest_verification', ''),
    ('seo_yandex_verification', ''),
    ('announcement_text', 'Looking to buy in bulk? Contact us for wholesale & custom pricing'),
    ('studio_enabled_3d', '1'),
    ('hero_3d_enabled', '1'),
    ('hero_3d_model', 'jersey')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
