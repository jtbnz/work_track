-- Migration: Create document_sequences table for quote/invoice numbering
-- Phase 1: Database Foundation

CREATE TABLE IF NOT EXISTS document_sequences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_type TEXT NOT NULL,
    year INTEGER NOT NULL,
    last_number INTEGER DEFAULT 0,
    UNIQUE(document_type, year)
);
