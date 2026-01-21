-- Fix quote_number unique constraint to allow revisions
-- The constraint should be on (quote_number, revision) combination, not just quote_number

-- SQLite doesn't support dropping constraints, so we need to recreate the table
-- First, create a new table with the correct constraint

CREATE TABLE quotes_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_number TEXT NOT NULL,
    revision INTEGER DEFAULT 1,
    client_id INTEGER REFERENCES clients(id),
    project_id INTEGER REFERENCES projects(id),
    quote_date DATE NOT NULL,
    expiry_date DATE,
    special_instructions TEXT,
    status TEXT DEFAULT 'draft',
    previous_status TEXT DEFAULT NULL,
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
    updated_by INTEGER REFERENCES users(id),
    UNIQUE(quote_number, revision)
);

-- Copy data from old table to new table
INSERT INTO quotes_new SELECT
    id, quote_number, revision, client_id, project_id, quote_date, expiry_date,
    special_instructions, status, previous_status,
    labour_stripping, labour_patterns, labour_cutting, labour_sewing,
    labour_upholstery, labour_assembly, labour_handling,
    labour_rate_type, labour_rate,
    subtotal_materials, subtotal_misc, subtotal_labour,
    total_excl_gst, gst_amount, total_incl_gst, pdf_path,
    created_at, updated_at, created_by, updated_by
FROM quotes;

-- Drop old table
DROP TABLE quotes;

-- Rename new table to quotes
ALTER TABLE quotes_new RENAME TO quotes;

-- Recreate indexes
CREATE INDEX idx_quotes_client ON quotes(client_id);
CREATE INDEX idx_quotes_project ON quotes(project_id);
CREATE INDEX idx_quotes_status ON quotes(status);
CREATE INDEX idx_quotes_number ON quotes(quote_number);
CREATE INDEX idx_quotes_date ON quotes(quote_date);
