<?php

/**
 * Test script to diagnose HR API connection issues
 * Run this on your hosted server to test API connectivity
 */

require_once __DIR__ . '/includes/config.php';

echo "=== HR API Connection Test ===\n\n";

echo "Configuration:\n";
echo "  API Base: " . (defined('HR_API_BASE') ? HR_API_BASE : 'Not defined') . "\n";
echo "  Endpoint: " . (defined('HR_API_JOB_POSTINGS') ? HR_API_JOB_POSTINGS : 'Not defined') . "\n";
echo "  Integration Enabled: " . (EXTERNAL_JOBS_ENABLED ? 'Yes' : 'No') . "\n\n";

if (!EXTERNAL_JOBS_ENABLED) {
    echo "❌ External jobs integration is disabled in config.php\n";
    exit(1);
}

$base = defined('HR_API_BASE') ? HR_API_BASE : 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API';
$endpoint = defined('HR_API_JOB_POSTINGS') ? HR_API_JOB_POSTINGS : '/get_job_postings.php';
$url = $base . $endpoint;

echo "Full URL: {$url}\n\n";

// Test 1: Basic connectivity
echo "Test 1: Testing basic connectivity...\n";
$testUrl = parse_url($url);
$host = $testUrl['host'] ?? 'unknown';
$port = $testUrl['port'] ?? 80;

$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "✓ Can connect to {$host}:{$port}\n";
    fclose($connection);
} else {
    echo "✗ Cannot connect to {$host}:{$port}\n";
    echo "  Error: {$errstr} ({$errno})\n";
    echo "  This suggests the server is unreachable or firewall is blocking.\n\n";
}

// Test 2: DNS Resolution
echo "\nTest 2: Testing DNS resolution...\n";
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "✗ DNS resolution failed for {$host}\n";
    echo "  Cannot resolve hostname to IP address.\n";
} else {
    echo "✓ DNS resolved: {$host} -> {$ip}\n";
}

// Test 3: cURL request
echo "\nTest 3: Testing cURL request...\n";
$data = [
    'search' => '',
    'department_id' => 0,
    'employment_type_id' => 0,
    'created_from' => '',
    'created_to' => ''
];

$payload = json_encode($data);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$info = curl_getinfo($ch);
curl_close($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

if ($err) {
    echo "✗ cURL Error: {$err}\n";
    echo "\nVerbose output:\n{$verboseLog}\n";
    
    if (strpos($err, 'timeout') !== false || strpos($err, 'Timeout') !== false) {
        echo "\n⚠ Timeout Issue:\n";
        echo "  - The API server may be on a private network\n";
        echo "  - Your hosting server may not have access to this IP\n";
        echo "  - The API may require VPN or whitelisted IP access\n";
        echo "  - Firewall may be blocking the connection\n";
    }
} else {
    echo "✓ cURL request completed\n";
    echo "  HTTP Code: {$code}\n";
    echo "  Response length: " . strlen($res) . " bytes\n";
    
    if ($code === 200) {
        $response = json_decode($res, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✓ Valid JSON response received\n";
            echo "\nResponse structure:\n";
            echo "  Keys: " . implode(', ', array_keys($response)) . "\n";
            if (isset($response['data']) || isset($response['jobs'])) {
                $jobs = $response['data'] ?? $response['jobs'] ?? [];
                echo "  Jobs count: " . count($jobs) . "\n";
            }
        } else {
            echo "✗ Invalid JSON: " . json_last_error_msg() . "\n";
            echo "  Response preview: " . substr($res, 0, 200) . "...\n";
        }
    } else {
        echo "✗ HTTP Error: {$code}\n";
        echo "  Response: " . substr($res, 0, 200) . "...\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nTroubleshooting:\n";
echo "1. If connection fails: The API server may require VPN or network access\n";
echo "2. If timeout: Check if the API IP is whitelisted or accessible from your hosting\n";
echo "3. Contact your integration partner to verify:\n";
echo "   - API server is running\n";
echo "   - Your hosting IP is whitelisted\n";
echo "   - VPN/network access is configured\n";
echo "   - API endpoint URL is correct\n";

