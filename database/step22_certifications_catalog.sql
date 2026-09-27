-- ================================================================
-- Step 22: Certifications / Chamber Membership (homepage trust
-- section) — the product catalog PDF page needs no new table, it
-- just reads existing products/categories.
-- ================================================================
USE builtco_sports_new;

CREATE TABLE IF NOT EXISTS certifications (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    title              VARCHAR(200) NOT NULL,
    issuer             VARCHAR(200) DEFAULT NULL,
    certificate_number VARCHAR(100) DEFAULT NULL,
    issued_date        DATE DEFAULT NULL,
    image              VARCHAR(500) DEFAULT NULL,
    status             ENUM('active','inactive') NOT NULL DEFAULT 'active',
    sort_order         INT NOT NULL DEFAULT 0,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('section_certifications_enabled', '1'),
    ('section_certifications_title', 'Certifications & Memberships')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
