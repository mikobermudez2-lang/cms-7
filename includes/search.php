<?php

declare(strict_types=1);

/**
 * Search functionality for posts and jobs
 */

/**
 * Search posts using full-text search
 * 
 * @param string $query Search query
 * @param array $options Optional filters: category_id, date_from, date_to
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array Search results with pagination
 */
function search_posts(string $query, array $options = [], int $page = 1, int $perPage = 10): array
{
    $db = get_db();
    $offset = ($page - 1) * $perPage;
    $params = [];
    
    // Base query with LIKE-based partial matching
    $sql = "SELECT p.*, c.name as category_name, c.name_ph as category_name_ph, c.slug as category_slug, c.color as category_color,
                   u.display_name as author_name, u.username as author_username
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.status = 'published'
            AND p.archived_at IS NULL";
    
    $countSql = "SELECT COUNT(*) FROM posts p 
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.status = 'published' AND p.archived_at IS NULL";
    
    // Add partial search condition (searches in title, content, and category name)
    if (!empty($query)) {
        $searchTerm = '%' . $query . '%';
        $sql .= " AND (p.title LIKE ? OR p.content LIKE ? OR c.name LIKE ? OR c.name_ph LIKE ?)";
        $countSql .= " AND (p.title LIKE ? OR p.content LIKE ? OR c.name LIKE ? OR c.name_ph LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Category filter
    if (!empty($options['category_id'])) {
        $sql .= " AND p.category_id = ?";
        $countSql .= " AND p.category_id = ?";
        $params[] = $options['category_id'];
    }
    
    // Date range filters
    if (!empty($options['date_from'])) {
        $sql .= " AND DATE(COALESCE(p.published_at, p.created_at)) >= ?";
        $countSql .= " AND DATE(COALESCE(p.published_at, p.created_at)) >= ?";
        $params[] = $options['date_from'];
    }
    
    if (!empty($options['date_to'])) {
        $sql .= " AND DATE(COALESCE(p.published_at, p.created_at)) <= ?";
        $countSql .= " AND DATE(COALESCE(p.published_at, p.created_at)) <= ?";
        $params[] = $options['date_to'];
    }
    
    // Count query
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    
    // Add ordering and pagination
    $sql .= " ORDER BY p.published_at DESC";
    
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    
    return [
        'items' => $posts,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => (int) ceil($total / $perPage),
        'query' => $query,
    ];
}

/**
 * Search jobs
 */
function search_jobs(string $query, array $options = [], int $page = 1, int $perPage = 10): array
{
    $db = get_db();
    $offset = ($page - 1) * $perPage;
    $params = [];
    
    $sql = "SELECT j.*,
                   MATCH(j.title, j.summary, j.description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
            FROM jobs j
            WHERE j.status = 'open'";
    
    $countSql = "SELECT COUNT(*) FROM jobs j WHERE j.status = 'open'";
    
    if (!empty($query)) {
        $sql .= " AND MATCH(j.title, j.summary, j.description) AGAINST(? IN NATURAL LANGUAGE MODE)";
        $countSql .= " AND MATCH(j.title, j.summary, j.description) AGAINST(? IN NATURAL LANGUAGE MODE)";
        $params[] = $query;
    }
    
    // Department filter
    if (!empty($options['department'])) {
        $sql .= " AND j.department = ?";
        $countSql .= " AND j.department = ?";
        $params[] = $options['department'];
    }
    
    // Employment type filter
    if (!empty($options['employment_type'])) {
        $sql .= " AND j.employment_type = ?";
        $countSql .= " AND j.employment_type = ?";
        $params[] = $options['employment_type'];
    }
    
    // Count
    $countStmt = $db->prepare($countSql);
    $countParams = $params;
    if (!empty($query)) {
        array_unshift($countParams, $query);
    }
    $countStmt->execute(array_slice($countParams, 0, substr_count($countSql, '?')));
    $total = (int) $countStmt->fetchColumn();
    
    // Ordering
    if (!empty($query)) {
        $sql .= " ORDER BY relevance DESC, j.posted_at DESC";
        array_unshift($params, $query);
    } else {
        $sql .= " ORDER BY j.posted_at DESC";
    }
    
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return [
        'items' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => (int) ceil($total / $perPage),
        'query' => $query,
    ];
}

/**
 * Simple LIKE-based search (fallback if full-text fails)
 */
function search_posts_simple(string $query, int $page = 1, int $perPage = 10): array
{
    $db = get_db();
    $offset = ($page - 1) * $perPage;
    $searchTerm = '%' . $query . '%';
    
    $sql = "SELECT p.*, c.name as category_name, c.name_ph as category_name_ph, c.slug as category_slug, c.color as category_color, u.display_name as author_name
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.status = 'published'
            AND p.archived_at IS NULL
            AND (p.title LIKE ? OR p.content LIKE ? OR c.name LIKE ? OR c.name_ph LIKE ?)
            ORDER BY p.published_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
    $posts = $stmt->fetchAll();
    
    // Count
    $countSql = "SELECT COUNT(*) FROM posts p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.status = 'published' AND p.archived_at IS NULL
                 AND (p.title LIKE ? OR p.content LIKE ? OR c.name LIKE ? OR c.name_ph LIKE ?)";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $total = (int) $countStmt->fetchColumn();
    
    return [
        'items' => $posts,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => (int) ceil($total / $perPage),
        'query' => $query,
    ];
}

/**
 * Get search suggestions (autocomplete)
 */
function get_search_suggestions(string $query, int $limit = 5): array
{
    if (strlen($query) < 2) {
        return [];
    }
    
    $db = get_db();
    $searchTerm = $query . '%';
    
    $stmt = $db->prepare(
        "SELECT DISTINCT title FROM posts 
         WHERE status = 'published' AND archived_at IS NULL
         AND title LIKE ?
         ORDER BY published_at DESC
         LIMIT ?"
    );
    $stmt->execute([$searchTerm, $limit]);
    
    return array_column($stmt->fetchAll(), 'title');
}

