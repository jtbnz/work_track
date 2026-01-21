-- Migration: Create indexes for quoting module tables
-- Phase 1: Database Foundation

CREATE INDEX IF NOT EXISTS idx_materials_supplier ON materials(supplier_id);
CREATE INDEX IF NOT EXISTS idx_materials_stock ON materials(stock_on_hand, reorder_level);
CREATE INDEX IF NOT EXISTS idx_materials_active ON materials(is_active);

CREATE INDEX IF NOT EXISTS idx_quotes_client ON quotes(client_id);
CREATE INDEX IF NOT EXISTS idx_quotes_project ON quotes(project_id);
CREATE INDEX IF NOT EXISTS idx_quotes_status ON quotes(status);
CREATE INDEX IF NOT EXISTS idx_quotes_number ON quotes(quote_number);
CREATE INDEX IF NOT EXISTS idx_quotes_date ON quotes(quote_date);

CREATE INDEX IF NOT EXISTS idx_quote_materials_quote ON quote_materials(quote_id);
CREATE INDEX IF NOT EXISTS idx_quote_misc_quote ON quote_misc(quote_id);

CREATE INDEX IF NOT EXISTS idx_invoices_client ON invoices(client_id);
CREATE INDEX IF NOT EXISTS idx_invoices_quote ON invoices(quote_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);
CREATE INDEX IF NOT EXISTS idx_invoices_due_date ON invoices(due_date);
CREATE INDEX IF NOT EXISTS idx_invoices_number ON invoices(invoice_number);

CREATE INDEX IF NOT EXISTS idx_invoice_materials_invoice ON invoice_materials(invoice_id);
CREATE INDEX IF NOT EXISTS idx_invoice_misc_invoice ON invoice_misc(invoice_id);

CREATE INDEX IF NOT EXISTS idx_stock_movements_material ON stock_movements(material_id);
CREATE INDEX IF NOT EXISTS idx_stock_movements_date ON stock_movements(created_at);

CREATE INDEX IF NOT EXISTS idx_suppliers_active ON suppliers(is_active);
CREATE INDEX IF NOT EXISTS idx_suppliers_name ON suppliers(name);
