<?php

declare(strict_types=1);

/**
 * Global configuration for database credentials and app constants.
 */
const DB_HOST = '127.0.0.1';
const DB_NAME = 'healthcare_db';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NAME = 'Healthcare Content Management';

// Admin email for notifications (update with your admin email)
const ADMIN_EMAIL = 'admin@healthcarecenter.com';

// Debug mode - set to false in production
const DEBUG_MODE = true;

// ============================================
// EXTERNAL JOBS INTEGRATION CONFIGURATION
// ============================================
// Set to true to enable external jobs integration
const EXTERNAL_JOBS_ENABLED = true;

// HR API Configuration (for jobs integration)
// For Radmin VPN testing: Use the IP address of the PC running the HR API
// Example: 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API'
const HR_API_BASE = 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API';
const HR_API_JOB_POSTINGS = '/get_job_postings.php';

// Field mapping: API field => local field
// Based on actual HR API response structure
const EXTERNAL_JOBS_FIELD_MAP = [
    'jobID' => 'external_id',        // API uses 'jobID' (case-sensitive)
    'id' => 'external_id',           // Fallback
    'job_id' => 'external_id',       // Fallback
    'job_title' => 'title',          // API uses 'job_title'
    'title' => 'title',               // Fallback
    'job_description' => 'description', // API uses 'job_description'
    'description' => 'description',   // Fallback
    'department' => 'department',     // API returns department ID (number)
    'location' => 'location',       // API may return null
    'employment_type' => 'employment_type', // API returns employment_type ID (number)
    'date_posted' => 'posted_at',    // API uses 'date_posted'
    'posted_at' => 'posted_at',      // Fallback
    'closing_date' => 'closes_at',   // API provides closing_date
    'expected_salary' => 'salary_range', // Map to salary_range if needed
    // Note: API doesn't provide 'summary' or 'status' fields
];
