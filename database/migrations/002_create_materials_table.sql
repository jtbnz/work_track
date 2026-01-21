-- Migration: Create materials table
-- Phase 1: Database Foundation

CREATE TABLE IF NOT EXISTS materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER REFERENCES suppliers(id),
    manufacturers_code TEXT,
    item_name TEXT NOT NULL,
    cost_excl DECIMAL(10,2) NOT NULL DEFAULT 0,
    gst DECIMAL(10,2) DEFAULT 0,
    cost_incl DECIMAL(10,2) DEFAULT 0,
    sell_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    comments TEXT,
    stock_on_hand DECIMAL(10,2) DEFAULT 0,
    reorder_quantity DECIMAL(10,2) DEFAULT 0,
    reorder_level DECIMAL(10,2) DEFAULT 5,
    unit_of_measure TEXT DEFAULT 'each',
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);
