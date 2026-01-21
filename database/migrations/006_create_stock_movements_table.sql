-- Migration: Create stock_movements table for audit trail
-- Phase 1: Database Foundation
-- TODO: Phase 5 - Stock tracking functionality

CREATE TABLE IF NOT EXISTS stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER NOT NULL REFERENCES materials(id),
    movement_type TEXT NOT NULL,
    quantity_change DECIMAL(10,2) NOT NULL,
    stock_before DECIMAL(10,2),
    stock_after DECIMAL(10,2),
    reference_type TEXT,
    reference_id INTEGER,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id)
);
