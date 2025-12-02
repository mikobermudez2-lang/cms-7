<?php

/**
 * Detailed sync test with step-by-step debugging
 */

require_once __DIR__ . '/includes/init.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Detailed Sync Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        pre { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        .step { margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Detailed Sync Test</h1>";

if (!function_exists('fetch_jobs_from_api')) {
    echo "<p class='error'>fetch_jobs_from_api() function not found</p>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='step'>";
echo "<h2>Step 1: Fetch from API</h2>";
$apiResponse = fetch_jobs_from_api();

if (isset($apiResponse['status']) && $apiResponse['status'] === 'error') {
    echo "<p class='error'>✗ API Error: " . htmlspecialchars($apiResponse['message'] ?? 'Unknown') . "</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='success'>✓ API Response received</p>";
echo "<p><strong>Response keys:</strong> " . implode(', ', array_keys($apiResponse)) . "</p>";
echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 2: Extract Jobs</h2>";

$externalJobs = [];
if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
    $externalJobs = $apiResponse['data'];
    echo "<p class='success'>✓ Found jobs in 'data' key: " . count($externalJobs) . " jobs</p>";
} elseif (isset($apiResponse['jobs']) && is_array($apiResponse['jobs'])) {
    $externalJobs = $apiResponse['jobs'];
    echo "<p class='success'>✓ Found jobs in 'jobs' key: " . count($externalJobs) . " jobs</p>";
} elseif (isset($apiResponse['job_postings']) && is_array($apiResponse['job_postings'])) {
    $externalJobs = $apiResponse['job_postings'];
    echo "<p class='success'>✓ Found jobs in 'job_postings' key: " . count($externalJobs) . " jobs</p>";
} elseif (is_array($apiResponse) && isset($apiResponse[0]) && is_array($apiResponse[0])) {
    $externalJobs = $apiResponse;
    echo "<p class='success'>✓ Response is directly an array: " . count($externalJobs) . " jobs</p>";
} else {
    echo "<p class='error'>✗ No jobs found in response</p>";
    echo "<pre>" . htmlspecialchars(json_encode($apiResponse, JSON_PRETTY_PRINT)) . "</pre>";
    echo "</div></body></html>";
    exit;
}

if (empty($externalJobs)) {
    echo "<p class='warning'>⚠ Jobs array is empty</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p><strong>Total jobs found:</strong> " . count($externalJobs) . "</p>";
echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 3: Filter by Status</h2>";

$filteredJobs = array_filter($externalJobs, function($job) {
    $status = $job['status'] ?? $job['job_status'] ?? $job['is_open'] ?? null;
    
    if ($status !== null) {
        $statusLower = strtolower((string) $status);
        $result = in_array($statusLower, ['open', 'active', '1', 'true']) || $status === true || $status === 1;
        if (!$result) {
            echo "<p>Filtered out job (status: " . htmlspecialchars(var_export($status, true)) . ")</p>";
        }
        return $result;
    }
    
    return true; // No status = assume open
});

echo "<p><strong>Jobs after filtering:</strong> " . count($filteredJobs) . "</p>";
echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 4: Map Fields</h2>";

if (!function_exists('map_external_job_fields')) {
    echo "<p class='error'>map_external_job_fields() function not found</p>";
    echo "</div></body></html>";
    exit;
}

$mappedJobs = [];
$skipped = 0;

foreach ($filteredJobs as $index => $job) {
    $mapped = map_external_job_fields($job);
    
    if (empty($mapped['external_id'])) {
        $skipped++;
        echo "<p class='warning'>⚠ Job #{$index} skipped - missing external_id</p>";
        echo "<p><strong>Original job:</strong></p>";
        echo "<pre>" . htmlspecialchars(json_encode($job, JSON_PRETTY_PRINT)) . "</pre>";
        echo "<p><strong>Mapped data:</strong></p>";
        echo "<pre>" . htmlspecialchars(json_encode($mapped, JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        $mappedJobs[] = $mapped;
        if ($index < 2) { // Show first 2 for reference
            echo "<p class='success'>✓ Job #{$index} mapped successfully</p>";
            echo "<p><strong>External ID:</strong> " . htmlspecialchars($mapped['external_id']) . "</p>";
            echo "<p><strong>Title:</strong> " . htmlspecialchars($mapped['title'] ?? 'N/A') . "</p>";
        }
    }
}

echo "<p><strong>Successfully mapped:</strong> " . count($mappedJobs) . " jobs</p>";
echo "<p><strong>Skipped:</strong> {$skipped} jobs</p>";
echo "</div>";

echo "<div class='step'>";
echo "<h2>Step 5: Test Full Sync</h2>";

if (function_exists('sync_external_jobs')) {
    $syncResult = sync_external_jobs();
    
    echo "<p><strong>Success:</strong> " . ($syncResult['success'] ? '<span class="success">Yes</span>' : '<span class="error">No</span>') . "</p>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($syncResult['message'] ?? 'N/A') . "</p>";
    echo "<p><strong>Synced:</strong> " . ($syncResult['synced'] ?? 0) . " job(s)</p>";
    
    if (!empty($syncResult['errors'])) {
        echo "<p><strong>Errors:</strong></p>";
        echo "<ul>";
        foreach ($syncResult['errors'] as $error) {
            echo "<li class='error'>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>sync_external_jobs() function not found</p>";
}

echo "</div>";

echo "</div></body></html>";

