<?php

require_once '../config/Model.php';

class Project extends Model {
    protected $table = 'projects';
    protected $fillable = [
        'title', 'description', 'short_description', 'technologies', 
        'project_url', 'github_url', 'featured_image', 'gallery_images',
        'status', 'featured', 'sort_order'
    ];
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllProjects() {
        return $this->all('sort_order ASC, created_at DESC');
    }
    
    public function getProjectById($id) {
        return $this->find($id);
    }
    
    public function getFeaturedProjects($limit = 6) {
        $sql = "SELECT * FROM {$this->table} WHERE featured = 1 AND status = 'active' ORDER BY sort_order ASC, created_at DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    public function getByStatus($status) {
        return $this->where('status', $status);
    }
    
    public function updateOrder($projects) {
        foreach ($projects as $index => $project) {
            $this->update($project['id'], ['sort_order' => $index + 1]);
        }
        return true;
    }
    
    public function createProject($data) {
        return $this->create($data);
    }
    
    public function updateProject($id, $data) {
        return $this->update($id, $data);
    }
    
    public function deleteProject($id) {
        return $this->delete($id);
    }
    
    public function getProjectsCount() {
        return $this->count();
    }
    
    public function validate($data, $isUpdate = false) {
        $rules = [
            'title' => 'required|max:255',
            'description' => 'required',
            'short_description' => 'max:500',
            'project_url' => 'max:255',
            'github_url' => 'max:255',
            'featured_image' => 'max:255'
        ];
        
        $errors = parent::validate($data, $rules);
        
        // Custom validation for URLs
        foreach (['project_url', 'github_url', 'featured_image'] as $urlField) {
            if (isset($data[$urlField]) && !empty($data[$urlField])) {
                if (!filter_var($data[$urlField], FILTER_VALIDATE_URL)) {
                    $errors[$urlField] = ucfirst(str_replace('_', ' ', $urlField)) . ' must be a valid URL';
                }
            }
        }
        
        // Validate status
        if (isset($data['status'])) {
            $validStatuses = ['active', 'completed', 'archived'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Status must be one of: ' . implode(', ', $validStatuses);
            }
        }
        
        // Validate technologies format (should be JSON array)
        if (isset($data['technologies']) && !empty($data['technologies'])) {
            if (is_string($data['technologies'])) {
                $decoded = json_decode($data['technologies'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors['technologies'] = 'Technologies must be a valid JSON array';
                }
            }
        }
        
        // Validate featured and sort_order
        if (isset($data['featured']) && !in_array($data['featured'], [0, 1, '0', '1', true, false])) {
            $errors['featured'] = 'Featured must be 0 or 1';
        }
        
        if (isset($data['sort_order']) && (!is_numeric($data['sort_order']) || $data['sort_order'] < 0)) {
            $errors['sort_order'] = 'Sort order must be a positive number';
        }
        
        return $errors;
    }
    
    public function getRecentProjects($limit = 5) {
        $query = "SELECT * FROM projects ORDER BY created_at DESC LIMIT ?";
        return $this->db->fetchAll($query, [$limit]);
    }
    
    public function sanitizeData($data) {
        $sanitized = [];
        
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'title':
                    case 'short_description':
                        $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
                        break;
                    case 'description':
                        $sanitized[$field] = trim($data[$field]);
                        break;
                    case 'technologies':
                    case 'gallery_images':
                        if (is_string($data[$field])) {
                            $sanitized[$field] = $data[$field];
                        } else {
                            $sanitized[$field] = json_encode($data[$field]);
                        }
                        break;
                    case 'featured':
                        $sanitized[$field] = (int) $data[$field];
                        break;
                    case 'sort_order':
                        $sanitized[$field] = (int) $data[$field];
                        break;
                    default:
                        $sanitized[$field] = trim($data[$field]);
                        break;
                }
            }
        }
        
        return $sanitized;
    }
}
?>