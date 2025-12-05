<?php

declare(strict_types=1);

/**
 * Global configuration for database credentials and app constants.
 */
const DB_HOST = '127.0.0.1';
const DB_NAME = 'healthcare_db';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NAME = 'Healthcare Appointment Management System';
const DEFAULT_AVAILABLE_BEDS = 24;

// Admin email for notifications (update with your admin email)
const ADMIN_EMAIL = 'admin@healthcarecenter.com';

// Debug mode - set to false in production
const DEBUG_MODE = true;

/**
 * Maps system usernames to doctor IDs from the doctors table.
 * Update the doctor_id after seeding or when creating new doctor accounts.
 */
const DOCTOR_ACCOUNT_MAP = [
    'doctor1' => 1,
];


