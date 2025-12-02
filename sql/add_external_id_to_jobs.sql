-- Migration: Add external_id column to jobs table for external integration
-- Run this if you already have a jobs table and need to add external integration support

ALTER TABLE jobs 
ADD COLUMN external_id VARCHAR(255) DEFAULT NULL AFTER id;

ALTER TABLE jobs 
ADD INDEX idx_external_id (external_id);

