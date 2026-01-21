-- Migration: Insert default quoting settings
-- Phase 1: Database Foundation

-- Ensure settings table exists (may not exist if Settings model hasn't been instantiated)
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER REFERENCES users(id)
);

INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('labour_rate_standard', '75.00');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('labour_rate_premium', '95.00');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('quote_validity_days', '30');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('gst_rate', '15');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('invoice_payment_terms', 'Net 14');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('company_name', '');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('company_address', '');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('company_phone', '');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('company_email', '');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('company_logo_path', '');
INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('low_stock_threshold', '5');
