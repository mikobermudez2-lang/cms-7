<?php

/**
 * Simple one-command cleanup - removes all duplicate external jobs
 * Keeps the oldest job for each external_id
 */

require_once __DIR__ . '/includes/init.php';

try {
    $db = get_db();
    
    // Delete duplicates in one query (keeps the oldest one for each external_id)
    $result = $db->exec(
        "DELETE j1 FROM jobs j1
         INNER JOIN jobs j2 
         WHERE j1.external_id IS NOT NULL 
         AND j1.external_id = j2.external_id 
         AND j1.created_at > j2.created_at"
    );
    
    echo "✓ Cleanup complete! Deleted {$result} duplicate job(s).\n";
    
    // Verify
    $verifyStmt = $db->query(
        "SELECT external_id, COUNT(*) as count 
         FROM jobs 
         WHERE external_id IS NOT NULL 
         GROUP BY external_id 
         HAVING count > 1"
    );
    $remaining = $verifyStmt->fetchAll();
    
    if (empty($remaining)) {
        echo "✓ No duplicates remaining.\n";
    } else {
        echo "⚠ Warning: " . count($remaining) . " duplicate group(s) still exist.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

