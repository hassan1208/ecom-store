-- ================================================================
-- Step 13: AI SEO settings (provider, API key, model)
-- ================================================================
USE builtco_sports_new;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('ai_provider', 'openai'),
    ('ai_api_key', ''),
    ('ai_model', 'gpt-4o-mini')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
