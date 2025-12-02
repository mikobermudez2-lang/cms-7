# Jobs Integration Setup Guide

This guide explains how to set up the external jobs integration system.

## Overview

The integration system allows you to sync job listings from an external database into your local healthcare system. Jobs are automatically synced when users visit the careers page, and can also be manually synced from the admin panel.

## Database Setup

### 1. Add `external_id` Column

If you already have a `jobs` table, run this migration:

```sql
ALTER TABLE jobs 
ADD COLUMN external_id VARCHAR(255) DEFAULT NULL AFTER id;

ALTER TABLE jobs 
ADD INDEX idx_external_id (external_id);
```

Or use the provided migration file:
```bash
# Import sql/add_external_id_to_jobs.sql via phpMyAdmin or MySQL client
```

### 2. Configure External Database

The external database credentials are already configured in `includes/config.php`:

```php
const EXTERNAL_DB_HOST = 'sql305.infinityfree.com';
const EXTERNAL_DB_NAME = 'if0_40459647_healthcare_db';
const EXTERNAL_DB_USER = 'if0_40459647';
const EXTERNAL_DB_PASS = 'ukWqeGvZQ2Q0i';
const EXTERNAL_JOBS_TABLE = 'jobs';
```

**Important:** Update these values if your external database credentials are different.

## Field Mapping

The system maps external database fields to local fields. The default mapping is in `includes/config.php`:

```php
const EXTERNAL_JOBS_FIELD_MAP = [
    'id' => 'external_id',           // External ID stored separately
    'title' => 'title',
    'department' => 'department',
    'location' => 'location',
    'employment_type' => 'employment_type',
    'summary' => 'summary',
    'description' => 'description',
    'status' => 'status',
    'posted_at' => 'posted_at',
];
```

**If your external table has different column names**, update this mapping in `includes/config.php`.

### Example: Different Column Names

If your external table uses `job_title` instead of `title`:

```php
const EXTERNAL_JOBS_FIELD_MAP = [
    'id' => 'external_id',
    'job_title' => 'title',  // Map external 'job_title' to local 'title'
    'dept' => 'department',  // Map external 'dept' to local 'department'
    // ... etc
];
```

## How It Works

### Automatic Sync

Jobs are automatically synced when:
- Users visit the public careers page (`/public/careers.php`)
- The sync runs silently in the background

### Manual Sync

Admins can manually sync jobs:
1. Go to Admin Panel → Jobs
2. Click the "Sync External" button
3. View the sync result message

### Sync Behavior

- **New Jobs**: Jobs that don't exist locally (based on `external_id`) are inserted
- **Existing Jobs**: Jobs with matching `external_id` are updated
- **Status Filter**: Only jobs with `status = 'open'` are synced
- **Local Jobs**: Jobs created manually (without `external_id`) are preserved

## External Jobs Management

### Protection

- External jobs (those with `external_id`) **cannot be edited** via the admin panel
- External jobs **cannot be deleted** via the admin panel
- They are marked with an "External" badge in the admin jobs list

### Local Jobs

- Jobs created manually in the admin panel work normally
- They can be edited and deleted as usual
- They are not affected by the sync process

## Troubleshooting

### Connection Issues

If sync fails with "Connection failed":
1. Verify external database credentials in `includes/config.php`
2. Check if the external database server is accessible
3. Verify firewall/network settings allow connections

### Field Mapping Issues

If jobs sync but fields are empty or incorrect:
1. Check the external table structure
2. Update `EXTERNAL_JOBS_FIELD_MAP` in `includes/config.php`
3. Ensure external column names match the mapping keys

### Table Not Found

If you get "Table doesn't exist" errors:
1. Verify `EXTERNAL_JOBS_TABLE` constant matches the actual table name
2. Check table name case sensitivity (MySQL on Linux is case-sensitive)

### Enable/Disable Integration

To temporarily disable the integration:

```php
// In includes/config.php
const EXTERNAL_JOBS_ENABLED = false;
```

## Testing

1. **Test Connection**: Visit `/public/careers.php` - it should sync without errors
2. **Check Admin Panel**: Go to Admin → Jobs, verify external jobs appear
3. **Verify Protection**: Try to edit/delete an external job (should be blocked)
4. **Manual Sync**: Click "Sync External" button, verify success message

## Support

If you encounter issues:
1. Check PHP error logs
2. Check database connection logs
3. Verify external database table structure matches expectations
4. Contact your integration partner for external database details

