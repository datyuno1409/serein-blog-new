<?php

require_once '../config/Model.php';

class Skill extends Model {
    protected $table = 'skills';
    protected $fillable = ['name', 'level', 'about_id', 'sort_order'];
    
    public function getByAboutId($aboutId) {
        return $this->where('about_id', $aboutId);
    }
    
    public function updateOrder($skills) {
        foreach ($skills as $index => $skill) {
            $this->update($skill['id'], ['sort_order' => $index + 1]);
        }
        return true;
    }
    
    public function validate($data, $isUpdate = false) {
        $rules = [
            'name' => 'required|max:100',
            'level' => 'required|numeric|min:0|max:100',
            'about_id' => 'required|numeric'
        ];
        
        $errors = parent::validate($data, $rules);
        
        // Custom validation for level
        if (isset($data['level'])) {
            $level = (int)$data['level'];
            if ($level < 0 || $level > 100) {
                $errors['level'] = 'Level must be between 0 and 100';
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
                    case 'name':
                        $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
                        break;
                    case 'level':
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