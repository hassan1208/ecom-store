-- ================================================================
-- Step 2: Homepage CMS — banners, navbar menu, USP features, footer
-- ================================================================
USE builtco_sports_new;

-- Hero slider banners
CREATE TABLE IF NOT EXISTS banners (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) DEFAULT NULL,
    subtitle    VARCHAR(300) DEFAULT NULL,
    image       VARCHAR(500) NOT NULL,
    image_alt   VARCHAR(255) DEFAULT NULL,
    button_text VARCHAR(100) DEFAULT NULL,
    button_url  VARCHAR(500) DEFAULT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Navbar / menu builder (mixes category links and custom links, with dropdowns)
CREATE TABLE IF NOT EXISTS menu_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    parent_id     INT DEFAULT NULL,
    label         VARCHAR(100) NOT NULL,
    link_type     ENUM('category','custom') NOT NULL DEFAULT 'custom',
    category_id   INT DEFAULT NULL,
    custom_url    VARCHAR(500) DEFAULT NULL,
    open_new_tab  TINYINT(1) NOT NULL DEFAULT 0,
    sort_order    INT NOT NULL DEFAULT 0,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id),
    CONSTRAINT fk_menu_parent FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- USP / trust strip (Free Shipping, Secure Payment, etc.)
CREATE TABLE IF NOT EXISTS features (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    icon        VARCHAR(50) NOT NULL DEFAULT 'fa-star',
    title       VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Footer columns + links
CREATE TABLE IF NOT EXISTS footer_columns (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(100) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS footer_links (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    column_id   INT NOT NULL,
    label       VARCHAR(150) NOT NULL,
    url         VARCHAR(500) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    CONSTRAINT fk_footer_link_column FOREIGN KEY (column_id) REFERENCES footer_columns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extra site-wide settings used by the public homepage
INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('site_logo', ''),
    ('site_favicon', ''),
    ('og_image', ''),
    ('contact_phone', ''),
    ('contact_email', ''),
    ('contact_address', ''),
    ('social_facebook', ''),
    ('social_instagram', ''),
    ('social_twitter', ''),
    ('social_youtube', ''),
    ('social_tiktok', ''),
    ('homepage_meta_title', 'BuiltCo Sports | Premium Sportswear & Custom Gear'),
    ('homepage_meta_description', 'Shop premium sportswear, boxing gear and custom manufactured apparel from BuiltCo Sports.'),
    ('homepage_intro_title', 'Built For Performance'),
    ('homepage_intro_content', 'BuiltCo Sports designs and manufactures premium sportswear, boxing gear, and custom apparel built to perform under pressure.'),
    ('footer_about_text', 'BuiltCo Sports manufactures premium sportswear and custom apparel, built for performance and made to last.'),
    ('footer_copyright_text', '© 2026 BuiltCo Sports. All rights reserved.')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
