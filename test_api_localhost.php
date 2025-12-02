<?php

/**
 * Quick test script for localhost API connection via Radmin VPN
 * Run this from your localhost to test the HR API connection
 */

require_once __DIR__ . '/includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>HR API Localhost Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>HR API Connection Test (Localhost via Radmin VPN)</h1>";

echo "<h2>Configuration</h2>";
echo "<p><strong>API Base:</strong> " . (defined('HR_API_BASE') ? HR_API_BASE : 'Not defined') . "</p>";
echo "<p><strong>Endpoint:</strong> " . (defined('HR_API_JOB_POSTINGS') ? HR_API_JOB_POSTINGS : 'Not defined') . "</p>";
echo "<p><strong>Integration Enabled:</strong> " . (EXTERNAL_JOBS_ENABLED ? 'Yes' : 'No') . "</p>";

if (!EXTERNAL_JOBS_ENABLED) {
    echo "<p class='error'>❌ External jobs integration is disabled in config.php</p>";
    echo "</body></html>";
    exit;
}

$base = defined('HR_API_BASE') ? HR_API_BASE : 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API';
$endpoint = defined('HR_API_JOB_POSTINGS') ? HR_API_JOB_POSTINGS : '/get_job_postings.php';
$url = $base . $endpoint;

echo "<p><strong>Full URL:</strong> <code>{$url}</code></p>";

echo "<h2>Test 1: Basic Connectivity</h2>";
$testUrl = parse_url($url);
$host = $testUrl['host'] ?? 'unknown';
$port = $testUrl['port'] ?? 80;

$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "<p class='success'>✓ Can connect to {$host}:{$port}</p>";
    fclose($connection);
} else {
    echo "<p class='error'>✗ Cannot connect to {$host}:{$port}</p>";
    echo "<p class='error'>Error: {$errstr} ({$errno})</p>";
    echo "<p class='info'>⚠ Make sure:</p>";
    echo "<ul>";
    echo "<li>Radmin VPN is connected on both PCs</li>";
    echo "<li>The HR API server is running on the other PC</li>";
    echo "<li>The IP address (26.137.144.53) is correct</li>";
    echo "</ul>";
}

echo "<h2>Test 2: DNS Resolution</h2>";
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "<p class='error'>✗ DNS resolution failed for {$host}</p>";
} else {
    echo "<p class='success'>✓ DNS resolved: {$host} -> {$ip}</p>";
}

echo "<h2>Test 3: API Request</h2>";
$data = [
    'search' => '',
    'department_id' => 0,
    'employment_type_id' => 0,
    'created_from' => '',
    'created_to' => ''
];

$payload = json_encode($data);

echo "<p><strong>Request Payload:</strong></p>";
echo "<pre>" . htmlspecialchars($payload) . "</pre>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

$startTime = microtime(true);
$res = curl_exec($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$info = curl_getinfo($ch);
curl_close($ch);

echo "<p><strong>Response Time:</strong> {$duration}ms</p>";

if ($err) {
    echo "<p class='error'>✗ cURL Error: {$err}</p>";
    
    if (strpos($err, 'timeout') !== false || strpos($err, 'Timeout') !== false) {
        echo "<div class='info'>";
        echo "<h3>Timeout Troubleshooting:</h3>";
        echo "<ul>";
        echo "<li>Check if Radmin VPN is connected</li>";
        echo "<li>Verify the API server IP address is correct</li>";
        echo "<li>Make sure the HR API server is running on the other PC</li>";
        echo "<li>Check Windows Firewall on the API server PC</li>";
        echo "<li>Try pinging the IP: <code>ping 26.137.144.53</code></li>";
        echo "</ul>";
        echo "</div>";
    }
} else {
    echo "<p class='success'>✓ cURL request completed</p>";
    echo "<p><strong>HTTP Code:</strong> {$code}</p>";
    echo "<p><strong>Response Length:</strong> " . strlen($res) . " bytes</p>";
    
    if ($code === 200) {
        $response = json_decode($res, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p class='success'>✓ Valid JSON response received</p>";
            echo "<h3>Response Structure:</h3>";
            echo "<pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)) . "</pre>";
            
            if (isset($response['data']) || isset($response['jobs'])) {
                $jobs = $response['data'] ?? $response['jobs'] ?? [];
                echo "<p><strong>Jobs Count:</strong> " . count($jobs) . "</p>";
                
                if (!empty($jobs)) {
                    echo "<h3>Sample Job:</h3>";
                    echo "<pre>" . htmlspecialchars(json_encode($jobs[0], JSON_PRETTY_PRINT)) . "</pre>";
                }
            }
        } else {
            echo "<p class='error'>✗ Invalid JSON: " . json_last_error_msg() . "</p>";
            echo "<p><strong>Response Preview:</strong></p>";
            echo "<pre>" . htmlspecialchars(substr($res, 0, 500)) . "...</pre>";
        }
    } else {
        echo "<p class='error'>✗ HTTP Error: {$code}</p>";
        echo "<p><strong>Response:</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($res, 0, 500)) . "...</pre>";
    }
}

echo "<h2>Test 4: Sync Function Test</h2>";
if (function_exists('sync_external_jobs')) {
    echo "<p>Testing sync_external_jobs() function...</p>";
    $syncResult = sync_external_jobs();
    
    echo "<p><strong>Success:</strong> " . ($syncResult['success'] ? 'Yes' : 'No') . "</p>";
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
    echo "<p class='error'>✗ sync_external_jobs() function not found</p>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If connection works, you can test the full integration on the careers page</li>";
echo "<li>If timeout occurs, check Radmin VPN connection and firewall settings</li>";
echo "<li>Verify the API server is accessible: <code>http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API/get_job_postings.php</code></li>";
echo "</ul>";

echo "</body></html>";

