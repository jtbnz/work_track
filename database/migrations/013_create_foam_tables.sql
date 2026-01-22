-- Migration: Create foam_grades, foam_products, and quote_foam tables
-- Phase: Foam Calculator Feature

-- Foam Grades (e.g., 38-200, 38-150, etc.)
CREATE TABLE IF NOT EXISTS foam_grades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grade_code TEXT NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Foam Products (grade + thickness combinations)
CREATE TABLE IF NOT EXISTS foam_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grade_id INTEGER NOT NULL REFERENCES foam_grades(id) ON DELETE CASCADE,
    thickness TEXT NOT NULL,
    sheet_cost DECIMAL(10,2) NOT NULL,
    sheet_area DECIMAL(10,4) DEFAULT 3.91,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(grade_id, thickness)
);

-- Quote Foam Items (foam added to quotes)
CREATE TABLE IF NOT EXISTS quote_foam (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_id INTEGER NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
    foam_product_id INTEGER REFERENCES foam_products(id),
    grade_code TEXT NOT NULL,
    thickness TEXT NOT NULL,
    sheet_cost DECIMAL(10,2) NOT NULL,
    sheet_area DECIMAL(10,4) NOT NULL,
    square_meters DECIMAL(10,2) NOT NULL,
    cutting_required BOOLEAN DEFAULT 0,
    unit_cost DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    sort_order INTEGER DEFAULT 0
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_foam_products_grade ON foam_products(grade_id);
CREATE INDEX IF NOT EXISTS idx_quote_foam_quote ON quote_foam(quote_id);
