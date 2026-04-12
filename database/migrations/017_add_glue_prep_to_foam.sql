-- Migration: Add glue/prep required column to quote_foam and setting for fee percentage

ALTER TABLE quote_foam ADD COLUMN glue_prep_required BOOLEAN DEFAULT 0;

INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('foam_glue_prep_fee_percent', '10');
