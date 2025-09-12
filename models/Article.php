<?php
require_once __DIR__ . '/../config/database.php';

class Article {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllArticles($limit = null, $offset = 0) {
        $sql = "SELECT * FROM articles ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        return $this->db->fetchAll($sql);
    }
    
    public function getArticleById($id) {
        return $this->db->fetch("SELECT * FROM articles WHERE id = ?", [$id]);
    }
    
    public function getArticleBySlug($slug) {
        return $this->db->fetch("SELECT * FROM articles WHERE slug = ?", [$slug]);
    }
    
    public function createArticle($title, $slug, $content, $excerpt = null, $status = 'published') {
        if ($this->slugExists($slug)) {
            return false;
        }
        
        $sql = "INSERT INTO articles (title, slug, content, excerpt, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        return $this->db->execute($sql, [$title, $slug, $content, $excerpt, $status]);
    }
    
    public function updateArticle($id, $title, $slug, $content, $excerpt = null, $status = 'published') {
        $existingArticle = $this->getArticleById($id);
        if (!$existingArticle) {
            return false;
        }
        
        if ($existingArticle['slug'] !== $slug && $this->slugExists($slug)) {
            return false;
        }
        
        $sql = "UPDATE articles SET title = ?, slug = ?, content = ?, excerpt = ?, status = ?, updated_at = NOW() 
                WHERE id = ?";
        return $this->db->execute($sql, [$title, $slug, $content, $excerpt, $status, $id]);
    }
    
    public function deleteArticle($id) {
        return $this->db->execute("DELETE FROM articles WHERE id = ?", [$id]);
    }
    
    public function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM articles WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }
    
    public function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    public function getArticleCount() {
        return $this->db->count("SELECT COUNT(*) FROM articles");
    }
    
    public function getPublishedArticles($limit = null) {
        $sql = "SELECT * FROM articles WHERE status = 'published' ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        return $this->db->fetchAll($sql);
    }
    
    public function getDraftArticles() {
        return $this->db->fetchAll("SELECT * FROM articles WHERE status = 'draft' ORDER BY updated_at DESC");
    }
    
    public function searchArticles($keyword) {
        $sql = "SELECT * FROM articles WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC";
        $searchTerm = '%' . $keyword . '%';
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm]);
    }
    
    public function getRecentArticles($limit = 5) {
        return $this->db->fetchAll("SELECT * FROM articles ORDER BY created_at DESC LIMIT $limit");
    }
    
    public function toggleStatus($id) {
        $article = $this->getArticleById($id);
        if (!$article) {
            return false;
        }
        
        $newStatus = ($article['status'] === 'published') ? 'draft' : 'published';
        return $this->db->execute("UPDATE articles SET status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $id]);
    }
    
    public function bulkDelete($ids) {
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "DELETE FROM articles WHERE id IN ($placeholders)";
        return $this->db->execute($sql, $ids);
    }
    
    public function getArticlesByStatus($status, $limit = null) {
        $sql = "SELECT * FROM articles WHERE status = ? ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        return $this->db->fetchAll($sql, [$status]);
    }
}