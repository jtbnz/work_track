-- Migration: Create invoices, invoice_materials, and invoice_misc tables
-- Phase 1: Database Foundation
-- TODO: Phase 5 - Invoice functionality

CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number TEXT NOT NULL UNIQUE,
    quote_id INTEGER REFERENCES quotes(id),
    client_id INTEGER NOT NULL REFERENCES clients(id),
    project_id INTEGER REFERENCES projects(id),
    invoice_date DATE NOT NULL,
    due_date DATE,
    payment_terms TEXT,
    status TEXT DEFAULT 'draft',
    subtotal_materials DECIMAL(10,2) DEFAULT 0,
    subtotal_misc DECIMAL(10,2) DEFAULT 0,
    subtotal_labour DECIMAL(10,2) DEFAULT 0,
    total_excl_gst DECIMAL(10,2) DEFAULT 0,
    gst_amount DECIMAL(10,2) DEFAULT 0,
    total_incl_gst DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    pdf_path TEXT,
    paid_date DATE,
    paid_amount DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS invoice_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    material_id INTEGER REFERENCES materials(id),
    item_description TEXT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_cost DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS invoice_misc (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL
);
