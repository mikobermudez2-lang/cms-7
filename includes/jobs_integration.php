<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

/**
 * Fetches job postings from HR API
 * 
 * @param array $filters Optional filters (search, department_id, employment_type_id, created_from, created_to)
 * @return array API response data
 */
function fetch_jobs_from_api(array $filters = []): array
{
    if (!EXTERNAL_JOBS_ENABLED) {
        return ['status' => 'error', 'message' => 'External jobs integration is disabled'];
    }

    $base = defined('HR_API_BASE') ? HR_API_BASE : 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API';
    $endpoint = defined('HR_API_JOB_POSTINGS') ? HR_API_JOB_POSTINGS : '/get_job_postings.php';
    $url = $base . $endpoint;

    $data = [
        'search' => $filters['search'] ?? '',
        'department_id' => isset($filters['department_id']) ? (int) $filters['department_id'] : 0,
        'employment_type_id' => isset($filters['employment_type_id']) ? (int) $filters['employment_type_id'] : 0,
        'created_from' => $filters['created_from'] ?? '',
        'created_to' => $filters['created_to'] ?? ''
    ];

    $payload = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Increased to 15 seconds
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8); // Increased to 8 seconds
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // In case API uses HTTPS
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        $errorMsg = "Connection error: {$err}";
        error_log('HR API cURL error: ' . $err . ' | URL: ' . $url);
        
        // Provide more helpful error messages
        if (strpos($err, 'Timeout') !== false || strpos($err, 'timeout') !== false) {
            $errorMsg = "API server timeout. The HR API may be unreachable or require VPN/network access.";
        } elseif (strpos($err, 'resolve') !== false || strpos($err, 'getaddrinfo') !== false) {
            $errorMsg = "Cannot resolve API hostname. Please verify the API URL is correct.";
        } elseif (strpos($err, 'Connection refused') !== false) {
            $errorMsg = "Connection refused. The API server may be down or blocking connections.";
        }
        
        return ['status' => 'error', 'message' => $errorMsg];
    }

    if ($code !== 200) {
        error_log('HR API HTTP error: ' . $code . ' | URL: ' . $url);
        return ['status' => 'error', 'message' => "HTTP {$code} - API returned an error"];
    }

    $response = json_decode($res, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('HR API JSON decode error: ' . json_last_error_msg());
        return ['status' => 'error', 'message' => 'Invalid JSON response'];
    }

    return $response;
}

/**
 * Syncs jobs from HR API to local database.
 * 
 * @return array ['success' => bool, 'message' => string, 'synced' => int, 'errors' => array]
 */
function sync_external_jobs(): array
{
    if (!EXTERNAL_JOBS_ENABLED) {
        return [
            'success' => false,
            'message' => 'External jobs integration is disabled.',
            'synced' => 0,
            'errors' => []
        ];
    }

    // Fetch jobs from API with retry logic
    $maxRetries = 2;
    $apiResponse = null;
    $lastError = null;
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $apiResponse = fetch_jobs_from_api();
        
        if (isset($apiResponse['status']) && $apiResponse['status'] !== 'error') {
            break; // Success, exit retry loop
        }
        
        $lastError = $apiResponse['message'] ?? 'Unknown error';
        
        if ($attempt < $maxRetries) {
            // Wait before retry (exponential backoff)
            sleep(1 * $attempt);
            error_log("HR API attempt {$attempt} failed, retrying...");
        }
    }
    
    if (!isset($apiResponse['status']) || $apiResponse['status'] === 'error') {
        $errorMessage = $lastError ?? 'Unknown error';
        
        // Check if it's a timeout/connection issue
        if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'Timeout') !== false) {
            $errorMessage = "API connection timeout. The HR API server may be unreachable from this network. " .
                          "Please verify network connectivity or contact your integration partner.";
        }
        
        return [
            'success' => false,
            'message' => 'Failed to fetch jobs from HR API: ' . $errorMessage,
            'synced' => 0,
            'errors' => [$errorMessage]
        ];
    }

    // Extract jobs from API response (adjust based on actual API response structure)
    $externalJobs = [];
    
    // Log the response structure for debugging
    if (DEBUG_MODE) {
        error_log('HR API Response structure: ' . json_encode(array_keys($apiResponse)));
    }
    
    // Try multiple possible response structures
    if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
        $externalJobs = $apiResponse['data'];
    } elseif (isset($apiResponse['jobs']) && is_array($apiResponse['jobs'])) {
        $externalJobs = $apiResponse['jobs'];
    } elseif (isset($apiResponse['job_postings']) && is_array($apiResponse['job_postings'])) {
        $externalJobs = $apiResponse['job_postings'];
    } elseif (isset($apiResponse['results']) && is_array($apiResponse['results'])) {
        $externalJobs = $apiResponse['results'];
    } elseif (is_array($apiResponse) && isset($apiResponse[0]) && is_array($apiResponse[0])) {
        // Response is directly an array of jobs
        $externalJobs = $apiResponse;
    } elseif (isset($apiResponse['status']) && $apiResponse['status'] === 'success' && isset($apiResponse['data'])) {
        // Nested success response
        $externalJobs = is_array($apiResponse['data']) ? $apiResponse['data'] : [];
    }

    // Log how many jobs were found before filtering
    if (DEBUG_MODE) {
        error_log('HR API Jobs found (before filtering): ' . count($externalJobs));
    }

    // Filter only open jobs (but be more lenient - accept if status is missing or 'open')
    $externalJobs = array_filter($externalJobs, function($job) {
        $status = $job['status'] ?? $job['job_status'] ?? $job['is_open'] ?? null;
        
        // If status is explicitly set, check it
        if ($status !== null) {
            $statusLower = strtolower((string) $status);
            // Accept: 'open', 'active', '1', 'true', true, or empty string
            return in_array($statusLower, ['open', 'active', '1', 'true']) || $status === true || $status === 1;
        }
        
        // If no status field, assume it's open (many APIs don't include status)
        return true;
    });
    
    // Log how many jobs remain after filtering
    if (DEBUG_MODE) {
        error_log('HR API Jobs after filtering: ' . count($externalJobs));
        if (!empty($externalJobs)) {
            error_log('Sample job structure: ' . json_encode($externalJobs[0]));
        } else {
            error_log('WARNING: No jobs after filtering! Original count: ' . count($externalJobs));
            // Log a sample of the unfiltered jobs to see what we're working with
            $allJobs = [];
            if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
                $allJobs = $apiResponse['data'];
            } elseif (isset($apiResponse['jobs']) && is_array($apiResponse['jobs'])) {
                $allJobs = $apiResponse['jobs'];
            }
            if (!empty($allJobs)) {
                error_log('Sample unfiltered job: ' . json_encode($allJobs[0]));
            }
        }
    }

    if (empty($externalJobs)) {
        return [
            'success' => true,
            'message' => 'No open jobs found in HR API.',
            'synced' => 0,
            'errors' => []
        ];
    }

    $localDb = get_db();
    $synced = 0;
    $errors = [];

    try {
        // Start transaction
        $localDb->beginTransaction();
        
        // Clean up any duplicate external jobs (keep the oldest one, delete newer duplicates)
        try {
            $localDb->exec(
                "DELETE j1 FROM jobs j1
                 INNER JOIN jobs j2 
                 WHERE j1.external_id IS NOT NULL 
                 AND j1.external_id = j2.external_id 
                 AND j1.id < j2.id"
            );
        } catch (PDOException $e) {
            // Ignore cleanup errors, continue with sync
            error_log('Duplicate cleanup warning: ' . $e->getMessage());
        }

        foreach ($externalJobs as $externalJob) {
            try {
                // Map external fields to local fields
                $mappedData = map_external_job_fields($externalJob);
                
                // Ensure ALL required SQL parameters are present (even if null)
                $requiredParams = [
                    'external_id' => null,
                    'title' => '',
                    'department' => null,
                    'location' => null,
                    'employment_type' => null,
                    'summary' => null,
                    'description' => null,
                    'status' => 'open',
                    'posted_at' => null,
                    'closes_at' => null
                ];
                
                foreach ($requiredParams as $param => $default) {
                    if (!array_key_exists($param, $mappedData)) {
                        $mappedData[$param] = $default;
                    }
                }
                
                // Skip if external_id is not set (required for duplicate detection)
                if (empty($mappedData['external_id'])) {
                    $jobTitle = $mappedData['title'] ?? 'Unknown';
                    $errors[] = "Skipping job '{$jobTitle}' - missing external_id";
                    
                    // Log the original job data for debugging
                    error_log("Job missing external_id. Title: {$jobTitle}");
                    error_log("Original job data: " . json_encode($externalJob));
                    error_log("Mapped data: " . json_encode($mappedData));
                    continue;
                }
                
                // Ensure external_id is a string for proper comparison
                $externalId = (string) $mappedData['external_id'];
                
                // Update mappedData to use the string external_id
                $mappedData['external_id'] = $externalId;
                
                // Check if job already exists by external_id (use strict comparison)
                $checkStmt = $localDb->prepare('SELECT id FROM jobs WHERE external_id = ? LIMIT 1');
                $checkStmt->execute([$externalId]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    // Update existing job
                    // Ensure all required fields are present
                    $updateData = array_merge([
                        'summary' => null,
                        'closes_at' => null,
                    ], $mappedData);
                    
                    $updateStmt = $localDb->prepare(
                        'UPDATE jobs SET
                            title = :title,
                            department = :department,
                            location = :location,
                            employment_type = :employment_type,
                            summary = :summary,
                            description = :description,
                            status = :status,
                            posted_at = :posted_at,
                            closes_at = :closes_at,
                            updated_at = NOW()
                        WHERE external_id = :external_id'
                    );
                    $updateStmt->execute($updateData);
                } else {
                    // Insert new job only if it doesn't exist
                    // Double-check to prevent race conditions
                    $doubleCheckStmt = $localDb->prepare('SELECT id FROM jobs WHERE external_id = ? LIMIT 1');
                    $doubleCheckStmt->execute([$externalId]);
                    $doubleCheck = $doubleCheckStmt->fetch();
                    
                    if ($doubleCheck) {
                        // Job was inserted between checks, update it instead
                        // Ensure all required parameters are present
                        $updateData = $mappedData;
                        if (!isset($updateData['closes_at'])) {
                            $updateData['closes_at'] = null;
                        }
                        if (!isset($updateData['summary'])) {
                            $updateData['summary'] = null;
                        }
                        
                        $updateStmt = $localDb->prepare(
                            'UPDATE jobs SET
                                title = :title,
                                department = :department,
                                location = :location,
                                employment_type = :employment_type,
                                summary = :summary,
                                description = :description,
                                status = :status,
                                posted_at = :posted_at,
                                closes_at = :closes_at,
                                updated_at = NOW()
                            WHERE external_id = :external_id'
                        );
                        $updateStmt->execute($updateData);
                    } else {
                        // Insert new job
                        $newId = generate_id();
                        // Ensure all required parameters are present
                        $insertData = $mappedData;
                        $insertData['id'] = $newId;
                        if (!isset($insertData['closes_at'])) {
                            $insertData['closes_at'] = null;
                        }
                        if (!isset($insertData['summary'])) {
                            $insertData['summary'] = null;
                        }
                        
                        $insertStmt = $localDb->prepare(
                            'INSERT INTO jobs (
                                id, external_id, title, department, location, 
                                employment_type, summary, description, status, posted_at, closes_at
                            ) VALUES (
                                :id, :external_id, :title, :department, :location,
                                :employment_type, :summary, :description, :status, :posted_at, :closes_at
                            )'
                        );
                        $insertStmt->execute($insertData);
                    }
                }

                $synced++;
            } catch (PDOException $e) {
                $jobTitle = $externalJob['title'] ?? $externalJob['job_title'] ?? 'Unknown';
                $errorMsg = $e->getMessage();
                $errors[] = "Error syncing job '{$jobTitle}': " . $errorMsg;
                
                // Log detailed error information
                error_log('Job sync error: ' . $errorMsg);
                error_log('Job data: ' . json_encode($externalJob));
                error_log('Mapped data keys: ' . implode(', ', array_keys($mappedData ?? [])));
                error_log('Mapped data: ' . json_encode($mappedData ?? []));
                
                // Check which parameters are missing
                $requiredParams = ['external_id', 'title', 'department', 'location', 'employment_type', 'summary', 'description', 'status', 'posted_at', 'closes_at'];
                $missing = [];
                foreach ($requiredParams as $param) {
                    if (!isset($mappedData[$param])) {
                        $missing[] = $param;
                    }
                }
                if (!empty($missing)) {
                    error_log('Missing parameters: ' . implode(', ', $missing));
                }
            }
        }

        $localDb->commit();

        return [
            'success' => true,
            'message' => "Successfully synced {$synced} job(s) from HR API.",
            'synced' => $synced,
            'errors' => $errors
        ];

    } catch (PDOException $e) {
        if ($localDb->inTransaction()) {
            $localDb->rollBack();
        }
        error_log('External jobs sync failed: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to sync jobs: ' . $e->getMessage(),
            'synced' => $synced,
            'errors' => array_merge($errors, [$e->getMessage()])
        ];
    }
}

/**
 * Maps external job fields to local job fields based on EXTERNAL_JOBS_FIELD_MAP.
 */
function map_external_job_fields(array $externalJob): array
{
    $mapped = [
        'external_id' => null,
        'title' => '',
        'department' => null,
        'location' => null,
        'employment_type' => null,
        'summary' => null,
        'description' => null,
        'status' => 'open',
        'posted_at' => null,
        'closes_at' => null,
    ];

    // Try to map fields using EXTERNAL_JOBS_FIELD_MAP
    foreach (EXTERNAL_JOBS_FIELD_MAP as $externalField => $localField) {
        if (isset($externalJob[$externalField])) {
            $value = $externalJob[$externalField];
            
            // Handle special cases
            if ($externalField === 'id' || $externalField === 'job_id' || $externalField === 'jobID') {
                $mapped['external_id'] = (string) $value;
            } elseif ($externalField === 'status') {
                // Normalize status values
                $status = strtolower((string) $value);
                $mapped['status'] = in_array($status, ['open', 'closed', 'draft']) ? $status : 'open';
            } elseif (in_array($externalField, ['posted_at', 'created_at', 'date_posted'])) {
                // Ensure proper datetime format (API returns Y-m-d, convert to datetime)
                if ($value) {
                    $timestamp = strtotime((string) $value);
                    $mapped['posted_at'] = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                } else {
                    $mapped['posted_at'] = null;
                }
            } elseif ($externalField === 'closing_date' || $localField === 'closes_at') {
                // Map closing_date to closes_at
                if ($value) {
                    $timestamp = strtotime((string) $value);
                    $mapped['closes_at'] = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
                } else {
                    $mapped['closes_at'] = null;
                }
            } elseif ($externalField === 'department' && is_numeric($value)) {
                // Department is an ID, we'll store it as-is (you may want to look up the name)
                $mapped['department'] = (string) $value; // Store as string for now
            } elseif ($externalField === 'employment_type' && is_numeric($value)) {
                // Employment type is an ID, we'll store it as-is (you may want to look up the name)
                $mapped['employment_type'] = (string) $value; // Store as string for now
            } else {
                // Only set if the local field is in our expected fields
                if (in_array($localField, ['title', 'department', 'location', 'employment_type', 'summary', 'description', 'status', 'posted_at', 'closes_at', 'external_id'])) {
                    $mapped[$localField] = $value;
                }
            }
        }
    }

    // Ensure external_id is set - try multiple possible field names (case-sensitive check)
    if (empty($mapped['external_id'])) {
        // Try common ID field names (including case variations)
        $idFields = ['jobID', 'JobID', 'job_id', 'id', 'ID', 'external_id', 'jobId'];
        foreach ($idFields as $field) {
            if (isset($externalJob[$field]) && !empty($externalJob[$field])) {
                $mapped['external_id'] = (string) $externalJob[$field];
                break;
            }
        }
    }
    
    // If still empty, try using array key if it's numeric
    if (empty($mapped['external_id']) && isset($externalJob['id'])) {
        $mapped['external_id'] = (string) $externalJob['id'];
    }
    
    // Convert to string and ensure it's not empty
    $mapped['external_id'] = !empty($mapped['external_id']) ? (string) $mapped['external_id'] : null;

    // Ensure title is not empty
    if (empty($mapped['title'])) {
        $mapped['title'] = $externalJob['job_title'] ?? $externalJob['title'] ?? 'Open Position';
    }
    
    // Set default status to 'open' if not provided (API doesn't provide status)
    if (empty($mapped['status'])) {
        $mapped['status'] = 'open';
    }
    
    // Ensure closes_at is always set (handle closing_date if not already mapped)
    if (!isset($mapped['closes_at']) && isset($externalJob['closing_date'])) {
        $value = $externalJob['closing_date'];
        if ($value) {
            $timestamp = strtotime((string) $value);
            $mapped['closes_at'] = $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
        } else {
            $mapped['closes_at'] = null;
        }
    }
    
    // Ensure closes_at is always in the array (even if null)
    if (!array_key_exists('closes_at', $mapped)) {
        $mapped['closes_at'] = null;
    }
    
    // Check if job is still open based on closing_date
    if (isset($externalJob['closing_date']) && !empty($externalJob['closing_date'])) {
        $closingDate = strtotime($externalJob['closing_date']);
        $today = strtotime('today');
        if ($closingDate && $closingDate < $today) {
            // Job has closed, but we'll still sync it (you can filter later if needed)
            // $mapped['status'] = 'closed';
        }
    }

    return $mapped;
}

/**
 * Gets jobs from HR API directly (without syncing).
 * Useful for testing or displaying external jobs without storing locally.
 */
function get_external_jobs(): array
{
    if (!EXTERNAL_JOBS_ENABLED) {
        return [];
    }

    $apiResponse = fetch_jobs_from_api();
    
    if (!isset($apiResponse['status']) || $apiResponse['status'] === 'error') {
        return [];
    }

    // Extract jobs from API response
    if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
        return $apiResponse['data'];
    } elseif (isset($apiResponse['jobs']) && is_array($apiResponse['jobs'])) {
        return $apiResponse['jobs'];
    } elseif (is_array($apiResponse) && isset($apiResponse[0])) {
        return $apiResponse;
    }

    return [];
}
