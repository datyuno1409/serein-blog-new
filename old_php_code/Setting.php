<?php

require_once '../config/Model.php';

class Setting extends Model {
    protected $table = 'settings';
    protected $fillable = ['setting_key', 'setting_value', 'setting_type', 'description'];
    
    public function getByKey($key) {
        $sql = "SELECT * FROM {$this->table} WHERE setting_key = ?";
        return $this->db->fetch($sql, [$key]);
    }
    
    public function getValue($key, $default = null) {
        $setting = $this->getByKey($key);
        
        if (!$setting) {
            return $default;
        }
        
        $value = $setting['setting_value'];
        
        // Convert based on type
        switch ($setting['setting_type']) {
            case 'boolean':
                return (bool)$value;
            case 'number':
                return is_numeric($value) ? (float)$value : $default;
            case 'json':
                return json_decode($value, true) ?: $default;
            default:
                return $value;
        }
    }
    
    public function setValue($key, $value, $type = 'text', $description = null) {
        $existing = $this->getByKey($key);
        
        // Convert value based on type
        switch ($type) {
            case 'boolean':
                $value = $value ? '1' : '0';
                break;
            case 'json':
                $value = json_encode($value);
                break;
            default:
                $value = (string)$value;
        }
        
        $data = [
            'setting_key' => $key,
            'setting_value' => $value,
            'setting_type' => $type
        ];
        
        if ($description) {
            $data['description'] = $description;
        }
        
        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            return $this->create($data);
        }
    }
    
    public function getByType($type) {
        return $this->where('setting_type', $type);
    }
    
    public function validate($data, $isUpdate = false) {
        $rules = [
            'setting_key' => 'required|max:100',
            'setting_value' => 'required',
            'setting_type' => 'required',
            'description' => 'max:255'
        ];
        
        $errors = parent::validate($data, $rules);
        
        // Validate setting_type
        if (isset($data['setting_type'])) {
            $validTypes = ['text', 'number', 'boolean', 'json', 'color', 'email', 'url'];
            if (!in_array($data['setting_type'], $validTypes)) {
                $errors['setting_type'] = 'Setting type must be one of: ' . implode(', ', $validTypes);
            }
        }
        
        // Validate setting_value based on type
        if (isset($data['setting_value']) && isset($data['setting_type'])) {
            switch ($data['setting_type']) {
                case 'number':
                    if (!is_numeric($data['setting_value'])) {
                        $errors['setting_value'] = 'Setting value must be a number';
                    }
                    break;
                case 'boolean':
                    if (!in_array($data['setting_value'], ['0', '1', 'true', 'false', true, false, 0, 1])) {
                        $errors['setting_value'] = 'Setting value must be a boolean (0, 1, true, false)';
                    }
                    break;
                case 'json':
                    if (is_string($data['setting_value'])) {
                        json_decode($data['setting_value']);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $errors['setting_value'] = 'Setting value must be valid JSON';
                        }
                    }
                    break;
                case 'email':
                    if (!filter_var($data['setting_value'], FILTER_VALIDATE_EMAIL)) {
                        $errors['setting_value'] = 'Setting value must be a valid email address';
                    }
                    break;
                case 'url':
                    if (!filter_var($data['setting_value'], FILTER_VALIDATE_URL)) {
                        $errors['setting_value'] = 'Setting value must be a valid URL';
                    }
                    break;
                case 'color':
                    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $data['setting_value'])) {
                        $errors['setting_value'] = 'Setting value must be a valid hex color (e.g., #FF0000)';
                    }
                    break;
            }
        }
        
        // Validate setting_key format
        if (isset($data['setting_key'])) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['setting_key'])) {
                $errors['setting_key'] = 'Setting key can only contain letters, numbers, and underscores';
            }
        }
        
        return $errors;
    }
    
    public function sanitizeData($data) {
        $sanitized = [];
        
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'setting_key':
                        $sanitized[$field] = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($data[$field])));
                        break;
                    case 'setting_value':
                        if (isset($data['setting_type'])) {
                            switch ($data['setting_type']) {
                                case 'number':
                                    $sanitized[$field] = (string) floatval($data[$field]);
                                    break;
                                case 'boolean':
                                    $sanitized[$field] = in_array($data[$field], [true, 1, '1', 'true']) ? '1' : '0';
                                    break;
                                case 'json':
                                    if (is_array($data[$field]) || is_object($data[$field])) {
                                        $sanitized[$field] = json_encode($data[$field]);
                                    } else {
                                        $sanitized[$field] = $data[$field];
                                    }
                                    break;
                                case 'email':
                                    $sanitized[$field] = filter_var(trim($data[$field]), FILTER_SANITIZE_EMAIL);
                                    break;
                                case 'url':
                                    $sanitized[$field] = filter_var(trim($data[$field]), FILTER_SANITIZE_URL);
                                    break;
                                default:
                                    $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
                                    break;
                            }
                        } else {
                            $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
                        }
                        break;
                    case 'setting_type':
                        $sanitized[$field] = strtolower(trim($data[$field]));
                        break;
                    case 'description':
                        $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
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