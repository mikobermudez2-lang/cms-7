<?php

/**
 * Emergency cleanup script to remove duplicate external jobs
 * Run this on your hosted server to clean up duplicates
 * 
 * This script will:
 * - Find all jobs with duplicate external_id values
 * - Keep the oldest job for each external_id
 * - Delete all newer duplicates
 */

require_once __DIR__ . '/includes/init.php';

echo "=== Duplicate External Jobs Cleanup ===\n\n";

try {
    $db = get_db();
    
    // Step 1: Find all duplicate external jobs
    echo "Step 1: Finding duplicate external jobs...\n";
    $duplicatesStmt = $db->query(
        "SELECT external_id, COUNT(*) as count 
         FROM jobs 
         WHERE external_id IS NOT NULL 
         GROUP BY external_id 
         HAVING count > 1
         ORDER BY count DESC"
    );
    $duplicates = $duplicatesStmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✓ No duplicate external jobs found. Database is clean!\n";
        exit(0);
    }
    
    echo "Found " . count($duplicates) . " external_id(s) with duplicates:\n\n";
    
    $totalToDelete = 0;
    $details = [];
    
    foreach ($duplicates as $dup) {
        $externalId = $dup['external_id'];
        $count = $dup['count'];
        $toDelete = $count - 1; // Keep 1, delete the rest
        $totalToDelete += $toDelete;
        
        // Get details of all jobs with this external_id
        $jobsStmt = $db->prepare(
            "SELECT id, title, created_at 
             FROM jobs 
             WHERE external_id = ? 
             ORDER BY created_at ASC"
        );
        $jobsStmt->execute([$externalId]);
        $jobs = $jobsStmt->fetchAll();
        
        $keepJob = $jobs[0];
        $deleteJobs = array_slice($jobs, 1);
        
        $details[] = [
            'external_id' => $externalId,
            'keep' => $keepJob,
            'delete' => $deleteJobs,
            'count' => $count
        ];
        
        echo "  External ID: {$externalId}\n";
        echo "    Total: {$count} jobs\n";
        echo "    Keep: 1 (ID: {$keepJob['id']}, Created: {$keepJob['created_at']})\n";
        echo "    Delete: {$toDelete} duplicate(s)\n\n";
    }
    
    echo "Summary:\n";
    echo "  Total duplicate groups: " . count($duplicates) . "\n";
    echo "  Total jobs to delete: {$totalToDelete}\n\n";
    
    // Step 2: Confirm deletion
    echo "Step 2: Cleaning up duplicates...\n\n";
    
    $deletedCount = 0;
    $errorCount = 0;
    
    foreach ($details as $detail) {
        $externalId = $detail['external_id'];
        $deleteJobs = $detail['delete'];
        
        foreach ($deleteJobs as $job) {
            try {
                $deleteStmt = $db->prepare("DELETE FROM jobs WHERE id = ?");
                $deleteStmt->execute([$job['id']]);
                
                if ($deleteStmt->rowCount() > 0) {
                    $deletedCount++;
                    echo "  ✓ Deleted job ID: {$job['id']} (External ID: {$externalId}, Title: {$job['title']})\n";
                }
            } catch (PDOException $e) {
                $errorCount++;
                echo "  ✗ Error deleting job ID: {$job['id']} - " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== Cleanup Complete ===\n";
    echo "Deleted: {$deletedCount} duplicate job(s)\n";
    if ($errorCount > 0) {
        echo "Errors: {$errorCount}\n";
    }
    
    // Step 3: Verify cleanup
    echo "\nStep 3: Verifying cleanup...\n";
    $verifyStmt = $db->query(
        "SELECT external_id, COUNT(*) as count 
         FROM jobs 
         WHERE external_id IS NOT NULL 
         GROUP BY external_id 
         HAVING count > 1"
    );
    $remaining = $verifyStmt->fetchAll();
    
    if (empty($remaining)) {
        echo "✓ Verification passed! No duplicates remaining.\n";
    } else {
        echo "⚠ Warning: " . count($remaining) . " duplicate group(s) still exist.\n";
        foreach ($remaining as $rem) {
            echo "  - External ID: {$rem['external_id']} has {$rem['count']} jobs\n";
        }
    }
    
    echo "\n✅ Cleanup script completed!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Throwable $e) {
    echo "\n❌ Unexpected Error: " . $e->getMessage() . "\n";
    exit(1);
}

