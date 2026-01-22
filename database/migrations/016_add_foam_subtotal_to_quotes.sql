-- Migration: Add subtotal_foam column to quotes table
-- Phase: Foam Calculator Feature

ALTER TABLE quotes ADD COLUMN subtotal_foam DECIMAL(10,2) DEFAULT 0;
