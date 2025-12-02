-- Migration: Add image_url column to jobs table
-- Run this to add image support for job postings

ALTER TABLE jobs 
ADD COLUMN image_url VARCHAR(500) DEFAULT NULL AFTER description_ph;

