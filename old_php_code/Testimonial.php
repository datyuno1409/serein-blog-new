<?php

require_once '../config/Model.php';

class Testimonial extends Model {
    protected $table = 'testimonials';
    protected $fillable = ['name', 'text', 'company', 'about_id', 'sort_order'];
    
    public function getByAboutId($aboutId) {
        return $this->where('about_id', $aboutId);
    }
    
    public function updateOrder($testimonials) {
        foreach ($testimonials as $index => $testimonial) {
            $this->update($testimonial['id'], ['sort_order' => $index + 1]);
        }
        return true;
    }
    
    public function validate($data, $isUpdate = false) {
        $rules = [
            'name' => 'required|max:100',
            'text' => 'required|max:1000',
            'company' => 'max:100',
            'about_id' => 'required|numeric'
        ];
        
        $errors = parent::validate($data, $rules);
        
        // Validate text length
        if (isset($data['text']) && strlen($data['text']) < 10) {
            $errors['text'] = 'Testimonial text must be at least 10 characters long';
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
                    case 'company':
                        $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
                        break;
                    case 'text':
                        $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
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