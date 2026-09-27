-- ================================================================
-- Step 17: Currency settings (symbol + ISO code, used across pricing,
-- invoices, and product structured data)
-- ================================================================
USE builtco_sports_new;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('currency_symbol', 'PKR'),
    ('currency_code', 'PKR')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
