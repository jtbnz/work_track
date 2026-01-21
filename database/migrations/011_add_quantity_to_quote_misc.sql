-- Add quantity column to quote_misc table
ALTER TABLE quote_misc ADD COLUMN quantity INTEGER DEFAULT 1;
