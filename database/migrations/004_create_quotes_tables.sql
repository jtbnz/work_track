-- Migration: Create quotes, quote_materials, and quote_misc tables
-- Phase 1: Database Foundation

CREATE TABLE IF NOT EXISTS quotes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_number TEXT NOT NULL UNIQUE,
    revision INTEGER DEFAULT 1,
    client_id INTEGER REFERENCES clients(id),
    project_id INTEGER REFERENCES projects(id),
    quote_date DATE NOT NULL,
    expiry_date DATE,
    special_instructions TEXT,
    status TEXT DEFAULT 'draft',
    labour_stripping INTEGER DEFAULT 0,
    labour_patterns INTEGER DEFAULT 0,
    labour_cutting INTEGER DEFAULT 0,
    labour_sewing INTEGER DEFAULT 0,
    labour_upholstery INTEGER DEFAULT 0,
    labour_assembly INTEGER DEFAULT 0,
    labour_handling INTEGER DEFAULT 0,
    labour_rate_type TEXT DEFAULT 'standard',
    labour_rate DECIMAL(10,2),
    subtotal_materials DECIMAL(10,2) DEFAULT 0,
    subtotal_misc DECIMAL(10,2) DEFAULT 0,
    subtotal_labour DECIMAL(10,2) DEFAULT 0,
    total_excl_gst DECIMAL(10,2) DEFAULT 0,
    gst_amount DECIMAL(10,2) DEFAULT 0,
    total_incl_gst DECIMAL(10,2) DEFAULT 0,
    pdf_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS quote_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_id INTEGER NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
    material_id INTEGER REFERENCES materials(id),
    item_description TEXT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_cost DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS quote_misc (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_id INTEGER NOT NULL REFERENCES quotes(id) ON DELETE CASCADE,
    misc_material_id INTEGER REFERENCES misc_materials(id),
    name TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    included BOOLEAN DEFAULT 1
);
