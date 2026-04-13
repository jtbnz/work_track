-- Migration: Add labour_glue_prep column to quotes table for glue/prep time tracking

ALTER TABLE quotes ADD COLUMN labour_glue_prep INTEGER DEFAULT 0;
