<?php
require_once __DIR__ . '/../config/database.php';

class SEO {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllSEOSettings() {
        $query = "SELECT * FROM seo_settings ORDER BY page ASC";
        return $this->db->fetchAll($query);
    }
    
    public function getSEOByPage($pageName) {
        $query = "SELECT * FROM seo_settings WHERE page = ?";
        return $this->db->fetchOne($query, [$pageName]);
    }
    
    public function createSEOSetting($data) {
        $query = "INSERT INTO seo_settings (page, title, description, keywords) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->executeQuery($query, [
            $data['page'],
            $data['title'],
            $data['description'],
            $data['keywords'] ?? null
        ]);
        return $stmt !== false;
    }
    
    public function updateSEOSetting($page, $data) {
        $query = "UPDATE seo_settings SET title = ?, description = ?, keywords = ? WHERE page = ?";
        $stmt = $this->db->executeQuery($query, [
            $data['title'],
            $data['description'],
            $data['keywords'] ?? null,
            $page
        ]);
        return $stmt !== false;
    }
    
    public function deleteSEOSetting($page) {
        $query = "DELETE FROM seo_settings WHERE page = ?";
        $stmt = $this->db->executeQuery($query, [$page]);
        return $stmt !== false;
    }
    
    public function getSEOCount() {
        $query = "SELECT COUNT(*) as count FROM seo_settings";
        $result = $this->db->fetchOne($query);
        return $result['count'] ?? 0;
    }
    
    public function validateSEOData($data) {
        $errors = [];
        
        if (empty($data['page_name'])) {
            $errors[] = 'Page name is required';
        }
        
        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        } elseif (strlen($data['title']) > 60) {
            $errors[] = 'Title should be 60 characters or less';
        }
        
        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        } elseif (strlen($data['description']) > 160) {
            $errors[] = 'Description should be 160 characters or less';
        }
        
        if (!empty($data['keywords']) && strlen($data['keywords']) > 255) {
            $errors[] = 'Keywords should be 255 characters or less';
        }
        
        return $errors;
    }
    
    public function getPagesList() {
        return [
            'home' => 'Home Page',
            'about' => 'About Page',
            'projects' => 'Projects Page',
            'articles' => 'Articles Page',
            'contact' => 'Contact Page'
        ];
    }
}