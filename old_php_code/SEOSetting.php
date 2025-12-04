<?php

require_once '../config/Model.php';

class SEOSetting extends Model {
    protected $table = 'seo_settings';
    protected $fillable = ['page', 'title', 'description', 'keywords', 'og_title', 'og_description', 'og_image'];
    
    public function getByPage($page) {
        $sql = "SELECT * FROM {$this->table} WHERE page = ?";
        return $this->db->fetch($sql, [$page]);
    }
    
    public function updateByPage($page, $data) {
        $existing = $this->getByPage($page);
        
        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['page'] = $page;
            return $this->create($data);
        }
    }
    
    public function validate($data, $isUpdate = false) {
        $rules = [
            'page' => 'required|max:100',
            'title' => 'required|max:255',
            'description' => 'max:500',
            'keywords' => 'max:500',
            'og_title' => 'max:255',
            'og_description' => 'max:500',
            'og_image' => 'max:255'
        ];
        
        $errors = parent::validate($data, $rules);
        
        // Custom validation for og_image URL
        if (isset($data['og_image']) && !empty($data['og_image'])) {
            if (!filter_var($data['og_image'], FILTER_VALIDATE_URL)) {
                $errors['og_image'] = 'OG Image must be a valid URL';
            }
        }
        
        return $errors;
    }
}