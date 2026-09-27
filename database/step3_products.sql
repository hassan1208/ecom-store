-- ================================================================
-- Step 3: Products, variations (Etsy-style), and bulk order inquiries
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS products (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    slug                VARCHAR(255) NOT NULL UNIQUE,
    sku                 VARCHAR(100) DEFAULT NULL,
    category_id         INT DEFAULT NULL,
    short_description   VARCHAR(500) DEFAULT NULL,
    description         LONGTEXT,
    tags                VARCHAR(500) DEFAULT NULL,
    status              ENUM('active','draft','archived') NOT NULL DEFAULT 'draft',
    is_featured         TINYINT(1) NOT NULL DEFAULT 0,
    is_new_arrival      TINYINT(1) NOT NULL DEFAULT 0,

    base_price          DECIMAL(10,2) NOT NULL DEFAULT 0,
    sale_price          DECIMAL(10,2) DEFAULT NULL,
    stock_quantity      INT NOT NULL DEFAULT 0,
    track_stock         TINYINT(1) NOT NULL DEFAULT 1,
    weight_kg           DECIMAL(10,3) DEFAULT NULL,

    -- Bulk / wholesale pricing
    qty_price_rules     TEXT DEFAULT NULL COMMENT 'JSON: [{"min_qty":10,"price":9.5}, ...]',
    show_bulk_dm        TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Show "Request Bulk Quote" CTA on product page',

    -- SEO
    meta_title          VARCHAR(70) DEFAULT NULL,
    meta_description    VARCHAR(160) DEFAULT NULL,
    focus_keyword       VARCHAR(150) DEFAULT NULL,
    canonical_url       VARCHAR(500) DEFAULT NULL,

    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    FULLTEXT KEY ft_search (name, tags, short_description),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_images (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    image_path  VARCHAR(500) NOT NULL,
    alt_text    VARCHAR(255) DEFAULT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    is_primary  TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_product (product_id),
    CONSTRAINT fk_pimg_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Etsy-style variation builder: a product can have multiple variation "types"
-- (Size, Color...), each with its own option values, and every real
-- combination of those options becomes one row in product_variations.
CREATE TABLE IF NOT EXISTS variation_types (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    name        VARCHAR(100) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    INDEX idx_product (product_id),
    CONSTRAINT fk_vtype_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS variation_options (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    type_id     INT NOT NULL,
    value       VARCHAR(100) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_voption_type FOREIGN KEY (type_id) REFERENCES variation_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_variations (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    product_id        INT NOT NULL,
    combination_key   VARCHAR(255) NOT NULL COMMENT 'e.g. "Red / Large"',
    price             DECIMAL(10,2) DEFAULT NULL COMMENT 'overrides product base_price when set',
    sale_price        DECIMAL(10,2) DEFAULT NULL,
    stock_quantity    INT NOT NULL DEFAULT 0,
    sku               VARCHAR(100) DEFAULT NULL,
    sort_order        INT NOT NULL DEFAULT 0,
    status            ENUM('active','inactive') NOT NULL DEFAULT 'active',
    INDEX idx_product (product_id),
    CONSTRAINT fk_pvar_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bulk order inquiries — the "bulk queries" leads captured from the storefront
-- and reviewed in the admin panel.
CREATE TABLE IF NOT EXISTS bulk_inquiries (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    product_id      INT DEFAULT NULL,
    product_name    VARCHAR(255) DEFAULT NULL COMMENT 'snapshot, survives product deletion',
    customer_name   VARCHAR(150) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    phone           VARCHAR(50) DEFAULT NULL,
    company         VARCHAR(150) DEFAULT NULL,
    quantity_needed INT DEFAULT NULL,
    message         TEXT DEFAULT NULL,
    status          ENUM('new','contacted','quoted','closed') NOT NULL DEFAULT 'new',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    CONSTRAINT fk_bulk_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
