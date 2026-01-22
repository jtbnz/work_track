-- Migration: Insert default foam grades and products from foamcosting.csv
-- Phase: Foam Calculator Feature

-- Insert default grade
INSERT OR IGNORE INTO foam_grades (grade_code, description) VALUES
    ('38-200', 'Standard upholstery foam');

-- Insert foam products (using default sheet_area of 3.91)
INSERT OR IGNORE INTO foam_products (grade_id, thickness, sheet_cost, sheet_area)
SELECT id, '10mm', 33.68, 3.91 FROM foam_grades WHERE grade_code = '38-200';

INSERT OR IGNORE INTO foam_products (grade_id, thickness, sheet_cost, sheet_area)
SELECT id, '25mm', 84.18, 3.91 FROM foam_grades WHERE grade_code = '38-200';

INSERT OR IGNORE INTO foam_products (grade_id, thickness, sheet_cost, sheet_area)
SELECT id, '50mm', 168.36, 3.91 FROM foam_grades WHERE grade_code = '38-200';

INSERT OR IGNORE INTO foam_products (grade_id, thickness, sheet_cost, sheet_area)
SELECT id, '75mm', 252.52, 3.91 FROM foam_grades WHERE grade_code = '38-200';

INSERT OR IGNORE INTO foam_products (grade_id, thickness, sheet_cost, sheet_area)
SELECT id, '100mm', 336.70, 3.91 FROM foam_grades WHERE grade_code = '38-200';
