-- Migration: Insert default misc materials
-- Phase 1: Database Foundation

INSERT OR IGNORE INTO misc_materials (name, fixed_price) VALUES ('Captain Tape', 4.50);
INSERT OR IGNORE INTO misc_materials (name, fixed_price) VALUES ('Cardboard', 7.50);
INSERT OR IGNORE INTO misc_materials (name, fixed_price) VALUES ('Thread', 2.50);
INSERT OR IGNORE INTO misc_materials (name, fixed_price) VALUES ('Glue', 5.00);
INSERT OR IGNORE INTO misc_materials (name, fixed_price) VALUES ('Staples', 5.00);
