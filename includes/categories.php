<?php

declare(strict_types=1);

/**
 * Categories and Tags management
 */

// ============================================
// CATEGORIES
// ============================================

/**
 * Get all categories
 */
function get_categories(): array
{
    $db = get_db();
    $stmt = $db->query(
        'SELECT c.*, COUNT(p.id) as post_count 
         FROM categories c 
         LEFT JOIN posts p ON c.id = p.category_id AND p.status = "published"
         GROUP BY c.id 
         ORDER BY c.sort_order ASC, c.name ASC'
    );
    return $stmt->fetchAll();
}

/**
 * Get category by ID
 */
function get_category(string $id): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get category by slug
 */
function get_category_by_slug(string $slug): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/**
 * Create a new category
 */
function create_category(array $data): ?string
{
    $db = get_db();
    $id = generate_id();
    $slug = slugify($data['name']);
    
    // Ensure unique slug
    $existingSlug = get_category_by_slug($slug);
    if ($existingSlug) {
        $slug .= '-' . substr($id, 0, 6);
    }
    
    $stmt = $db->prepare(
        'INSERT INTO categories (id, name, name_ph, slug, description, description_ph, color, icon, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    
    $result = $stmt->execute([
        $id,
        $data['name'],
        $data['name_ph'] ?? null,
        $slug,
        $data['description'] ?? null,
        $data['description_ph'] ?? null,
        $data['color'] ?? '#2563EB',
        $data['icon'] ?? 'bi-folder',
        $data['sort_order'] ?? 0,
    ]);
    
    if ($result) {
        log_activity('create_category', 'category', $id, 'Created: ' . $data['name']);
        return $id;
    }
    
    return null;
}

/**
 * Update a category
 */
function update_category(string $id, array $data): bool
{
    $db = get_db();
    
    $stmt = $db->prepare(
        'UPDATE categories 
         SET name = ?, name_ph = ?, description = ?, description_ph = ?, color = ?, icon = ?, sort_order = ?
         WHERE id = ?'
    );
    
    $result = $stmt->execute([
        $data['name'],
        $data['name_ph'] ?? null,
        $data['description'] ?? null,
        $data['description_ph'] ?? null,
        $data['color'] ?? '#2563EB',
        $data['icon'] ?? 'bi-folder',
        $data['sort_order'] ?? 0,
        $id,
    ]);
    
    if ($result) {
        log_activity('update_category', 'category', $id, 'Updated: ' . $data['name']);
    }
    
    return $result;
}

/**
 * Delete a category
 */
function delete_category(string $id): bool
{
    $category = get_category($id);
    if (!$category) {
        return false;
    }
    
    $db = get_db();
    
    // Set posts to no category
    $stmt = $db->prepare('UPDATE posts SET category_id = NULL WHERE category_id = ?');
    $stmt->execute([$id]);
    
    // Delete category
    $stmt = $db->prepare('DELETE FROM categories WHERE id = ?');
    $result = $stmt->execute([$id]);
    
    if ($result) {
        log_activity('delete_category', 'category', $id, 'Deleted: ' . $category['name']);
    }
    
    return $result;
}

// ============================================
// TAGS
// ============================================

/**
 * Get all tags
 */
function get_tags(): array
{
    $db = get_db();
    $stmt = $db->query(
        'SELECT t.*, COUNT(pt.post_id) as post_count 
         FROM tags t 
         LEFT JOIN post_tags pt ON t.id = pt.tag_id
         LEFT JOIN posts p ON pt.post_id = p.id AND p.status = "published"
         GROUP BY t.id 
         ORDER BY t.name ASC'
    );
    return $stmt->fetchAll();
}

/**
 * Get tag by ID
 */
function get_tag(string $id): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM tags WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get tag by slug
 */
function get_tag_by_slug(string $slug): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM tags WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/**
 * Get or create a tag by name
 */
function get_or_create_tag(string $name): string
{
    $slug = slugify($name);
    $tag = get_tag_by_slug($slug);
    
    if ($tag) {
        return $tag['id'];
    }
    
    $db = get_db();
    $id = generate_id();
    
    $stmt = $db->prepare('INSERT INTO tags (id, name, slug) VALUES (?, ?, ?)');
    $stmt->execute([$id, trim($name), $slug]);
    
    return $id;
}

/**
 * Get tags for a post
 */
function get_post_tags(string $postId): array
{
    $db = get_db();
    $stmt = $db->prepare(
        'SELECT t.* FROM tags t 
         INNER JOIN post_tags pt ON t.id = pt.tag_id 
         WHERE pt.post_id = ? 
         ORDER BY t.name ASC'
    );
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

/**
 * Set tags for a post (replaces existing)
 */
function set_post_tags(string $postId, array $tagIds): void
{
    $db = get_db();
    
    // Remove existing tags
    $stmt = $db->prepare('DELETE FROM post_tags WHERE post_id = ?');
    $stmt->execute([$postId]);
    
    // Add new tags
    if (!empty($tagIds)) {
        $stmt = $db->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
        foreach ($tagIds as $tagId) {
            $stmt->execute([$postId, $tagId]);
        }
    }
}

/**
 * Delete a tag
 */
function delete_tag(string $id): bool
{
    $db = get_db();
    
    // Remove from post_tags
    $stmt = $db->prepare('DELETE FROM post_tags WHERE tag_id = ?');
    $stmt->execute([$id]);
    
    // Delete tag
    $stmt = $db->prepare('DELETE FROM tags WHERE id = ?');
    return $stmt->execute([$id]);
}

