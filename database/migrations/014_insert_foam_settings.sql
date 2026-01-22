-- Migration: Insert foam-related settings
-- Phase: Foam Calculator Feature

INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES
    ('foam_default_sheet_area', '3.91'),
    ('foam_markup_multiplier', '2'),
    ('foam_cutting_fee_percent', '15');
