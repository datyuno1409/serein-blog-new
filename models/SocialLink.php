<?php

require_once '../config/Model.php';

class SocialLink extends Model {
    protected $table = 'social_links';
    protected $fillable = ['platform', 'url', 'about_id', 'sort_order'];
    
    public function getByAboutId($aboutId) {
        return $this->where('about_id', $aboutId);
    }
    
    public function updateOrder($links) {
        foreach ($links as $index => $link) {
            $this->update($link['id'], ['sort_order' => $index + 1]);
        }
        return true;
    }
    
    public function validate($data, $isUpdate = false) {
        $rules = [
            'platform' => 'required|max:50',
            'url' => 'required|max:255',
            'about_id' => 'required|numeric'
        ];
        
        $errors = parent::validate($data, $rules);
        
        // Custom validation for URL
        if (isset($data['url']) && !empty($data['url'])) {
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                $errors['url'] = 'URL must be a valid URL';
            }
        }
        
        // Validate platform
        if (isset($data['platform'])) {
            $validPlatforms = ['facebook', 'twitter', 'linkedin', 'github', 'instagram', 'youtube', 'website', 'email'];
            if (!in_array(strtolower($data['platform']), $validPlatforms)) {
                $errors['platform'] = 'Platform must be one of: ' . implode(', ', $validPlatforms);
            }
        }
        
        // Validate sort_order
        if (isset($data['sort_order']) && (!is_numeric($data['sort_order']) || $data['sort_order'] < 0)) {
            $errors['sort_order'] = 'Sort order must be a positive number';
        }
        
        return $errors;
    }
    
    public function sanitizeData($data) {
        $sanitized = [];
        
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'platform':
                        $sanitized[$field] = strtolower(htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8'));
                        break;
                    case 'url':
                        $sanitized[$field] = filter_var(trim($data[$field]), FILTER_SANITIZE_URL);
                        break;
                    case 'about_id':
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