<?php

/**
 * Debug script to see the exact API response structure
 * This will help us understand how to parse the jobs correctly
 */

require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>HR API Response Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        pre { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px; overflow-x: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>HR API Response Debug</h1>";

$base = defined('HR_API_BASE') ? HR_API_BASE : 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API';
$endpoint = defined('HR_API_JOB_POSTINGS') ? HR_API_JOB_POSTINGS : '/get_job_postings.php';
$url = $base . $endpoint;

echo "<h2>Request Details</h2>";
echo "<p><strong>URL:</strong> <code>{$url}</code></p>";

$data = [
    'search' => '',
    'department_id' => 0,
    'employment_type_id' => 0,
    'created_from' => '',
    'created_to' => ''
];

$payload = json_encode($data);
echo "<p><strong>Payload:</strong></p>";
echo "<pre>" . htmlspecialchars($payload) . "</pre>";

echo "<h2>API Response</h2>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    echo "<p class='error'>✗ Error: {$err}</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p><strong>HTTP Code:</strong> {$code}</p>";
echo "<p><strong>Response Length:</strong> " . strlen($res) . " bytes</p>";

if ($code !== 200) {
    echo "<p class='error'>HTTP Error: {$code}</p>";
    echo "<pre>" . htmlspecialchars($res) . "</pre>";
    echo "</div></body></html>";
    exit;
}

$response = json_decode($res, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "<p class='error'>JSON Decode Error: " . json_last_error_msg() . "</p>";
    echo "<h3>Raw Response:</h3>";
    echo "<pre>" . htmlspecialchars($res) . "</pre>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='success'>✓ Valid JSON Response</p>";

echo "<h2>Response Structure</h2>";
echo "<p><strong>Top-level Keys:</strong> " . implode(', ', array_keys($response)) . "</p>";

echo "<h3>Full Response (Formatted):</h3>";
echo "<pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

// Try to find jobs
echo "<h2>Job Extraction Analysis</h2>";

$possibleKeys = ['data', 'jobs', 'job_postings', 'results', 'items'];
$foundJobs = [];

foreach ($possibleKeys as $key) {
    if (isset($response[$key]) && is_array($response[$key])) {
        $foundJobs[$key] = $response[$key];
        echo "<p class='success'>✓ Found '{$key}' with " . count($response[$key]) . " items</p>";
    }
}

// Check if response is directly an array
if (is_array($response) && isset($response[0]) && is_array($response[0])) {
    $foundJobs['direct_array'] = $response;
    echo "<p class='success'>✓ Response is directly an array with " . count($response) . " items</p>";
}

if (empty($foundJobs)) {
    echo "<p class='error'>⚠ No jobs found in expected locations</p>";
    echo "<p class='info'>The API response structure may be different. Check the full response above.</p>";
} else {
    echo "<h3>Sample Job Structure:</h3>";
    $firstKey = array_key_first($foundJobs);
    $firstJob = $foundJobs[$firstKey][0] ?? null;
    
    if ($firstJob) {
        echo "<p><strong>From key:</strong> '{$firstKey}'</p>";
        echo "<p><strong>Job Keys:</strong> " . implode(', ', array_keys($firstJob)) . "</p>";
        echo "<pre>" . htmlspecialchars(json_encode($firstJob, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        
        // Check for ID field
        $idFields = ['id', 'job_id', 'external_id', 'ID', 'JobID', 'jobId'];
        $foundId = null;
        foreach ($idFields as $field) {
            if (isset($firstJob[$field])) {
                $foundId = $field;
                break;
            }
        }
        
        if ($foundId) {
            echo "<p class='success'>✓ ID field found: '{$foundId}' = " . $firstJob[$foundId] . "</p>";
        } else {
            echo "<p class='error'>✗ No ID field found in job (tried: " . implode(', ', $idFields) . ")</p>";
        }
        
        // Check for status field
        $statusFields = ['status', 'job_status', 'is_open', 'active'];
        $foundStatus = null;
        foreach ($statusFields as $field) {
            if (isset($firstJob[$field])) {
                $foundStatus = $field;
                break;
            }
        }
        
        if ($foundStatus) {
            echo "<p class='success'>✓ Status field found: '{$foundStatus}' = " . var_export($firstJob[$foundStatus], true) . "</p>";
        } else {
            echo "<p class='info'>ℹ No status field found (will assume all jobs are open)</p>";
        }
    }
}

echo "<h2>Test Sync Function</h2>";
if (function_exists('sync_external_jobs')) {
    echo "<p>Running sync_external_jobs()...</p>";
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

echo "</div></body></html>";

