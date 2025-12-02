<?php

/**
 * Quick test to see if field mapping works
 */

require_once __DIR__ . '/includes/init.php';

// Simulate an API job response
$testJob = [
    "jobID" => 36,
    "job_title" => "Hematology Lab Manager",
    "job_description" => "SDSFG",
    "department" => 9,
    "qualification" => null,
    "educational_level" => "BSA",
    "skills" => "Asdfg",
    "expected_salary" => "1234",
    "experience_years" => 5,
    "employment_type" => 5,
    "location" => null,
    "date_posted" => "2025-11-21",
    "closing_date" => "2025-11-26",
    "vacancy_count" => "5"
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mapping Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Field Mapping Test</h1>";

echo "<h2>Original Job Data</h2>";
echo "<pre>" . htmlspecialchars(json_encode($testJob, JSON_PRETTY_PRINT)) . "</pre>";

if (!function_exists('map_external_job_fields')) {
    echo "<p class='error'>map_external_job_fields() function not found</p>";
    echo "</body></html>";
    exit;
}

echo "<h2>Mapped Data</h2>";
$mapped = map_external_job_fields($testJob);
echo "<pre>" . htmlspecialchars(json_encode($mapped, JSON_PRETTY_PRINT)) . "</pre>";

echo "<h2>Results</h2>";
if (!empty($mapped['external_id'])) {
    echo "<p class='success'>✓ external_id found: " . htmlspecialchars($mapped['external_id']) . "</p>";
} else {
    echo "<p class='error'>✗ external_id is empty!</p>";
    echo "<p>This is why jobs are being skipped.</p>";
}

if (!empty($mapped['title'])) {
    echo "<p class='success'>✓ title found: " . htmlspecialchars($mapped['title']) . "</p>";
} else {
    echo "<p class='error'>✗ title is empty!</p>";
}

if (!empty($mapped['description'])) {
    echo "<p class='success'>✓ description found: " . htmlspecialchars(substr($mapped['description'], 0, 50)) . "...</p>";
} else {
    echo "<p class='error'>✗ description is empty!</p>";
}

echo "<h2>Field Mapping Configuration</h2>";
echo "<pre>";
if (defined('EXTERNAL_JOBS_FIELD_MAP')) {
    echo htmlspecialchars(json_encode(EXTERNAL_JOBS_FIELD_MAP, JSON_PRETTY_PRINT));
} else {
    echo "EXTERNAL_JOBS_FIELD_MAP not defined";
}
echo "</pre>";

echo "<h2>Debug: Checking jobID field</h2>";
echo "<p>jobID exists: " . (isset($testJob['jobID']) ? 'Yes' : 'No') . "</p>";
echo "<p>jobID value: " . (isset($testJob['jobID']) ? $testJob['jobID'] : 'N/A') . "</p>";
echo "<p>jobID type: " . (isset($testJob['jobID']) ? gettype($testJob['jobID']) : 'N/A') . "</p>";

// Check if it's in the mapping
if (defined('EXTERNAL_JOBS_FIELD_MAP')) {
    echo "<p>jobID in mapping: " . (isset(EXTERNAL_JOBS_FIELD_MAP['jobID']) ? 'Yes' : 'No') . "</p>";
    if (isset(EXTERNAL_JOBS_FIELD_MAP['jobID'])) {
        echo "<p>Maps to: " . EXTERNAL_JOBS_FIELD_MAP['jobID'] . "</p>";
    }
}

echo "</body></html>";

