<?php
class Settings {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getAllSettings() {
        $sql = "SELECT * FROM settings ORDER BY setting_key";
        return $this->db->fetchAll($sql);
    }
    
    public function getSettingByKey($key) {
        $sql = "SELECT * FROM settings WHERE setting_key = ?";
        return $this->db->fetchOne($sql, [$key]);
    }
    
    public function createSetting($data) {
        $sql = "INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->executeQuery($sql, [
            $data['setting_key'],
            $data['setting_value'],
            $data['setting_type'] ?? 'text',
            $data['description'] ?? null
        ]);
        return $stmt !== false;
    }
    
    public function updateSetting($key, $value) {
        $sql = "UPDATE settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?";
        $stmt = $this->db->executeQuery($sql, [$value, $key]);
        return $stmt !== false;
    }
    
    public function deleteSetting($key) {
        $sql = "DELETE FROM settings WHERE setting_key = ?";
        $stmt = $this->db->executeQuery($sql, [$key]);
        return $stmt !== false;
    }
    
    public function getSettingsCount() {
        $sql = "SELECT COUNT(*) as count FROM settings";
        $result = $this->db->fetchOne($sql);
        return $result ? $result['count'] : 0;
    }
    
    public function getSettingsByType($type) {
        $sql = "SELECT * FROM settings WHERE setting_type = ? ORDER BY setting_key";
        return $this->db->fetchAll($sql, [$type]);
    }
    
    public function validateSettingData($data) {
        $errors = [];
        
        if (empty($data['setting_key'])) {
            $errors[] = 'Setting key is required';
        }
        
        if (empty($data['setting_value'])) {
            $errors[] = 'Setting value is required';
        }
        
        if (!empty($data['setting_key']) && !preg_match('/^[a-zA-Z0-9_]+$/', $data['setting_key'])) {
            $errors[] = 'Setting key can only contain letters, numbers, and underscores';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    public function getColorSettings() {
        return $this->getSettingsByType('color');
    }
    
    public function getSortableSettings() {
        return $this->getSettingsByType('sortable');
    }
    
    public function updateSortableOrder($key, $order) {
        $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ? AND setting_type = 'sortable'";
        $stmt = $this->db->executeQuery($sql, [json_encode($order), $key]);
        return $stmt !== false;
    }
    
    public function getDefaultSettings() {
        return [
            'site_title' => 'Serein - Cybersecurity Expert',
            'site_description' => 'Professional cybersecurity services and ethical hacking',
            'primary_color' => '#007bff',
            'secondary_color' => '#6c757d',
            'accent_color' => '#28a745',
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'menu_order' => json_encode(['home', 'about', 'services', 'projects', 'blog', 'contact']),
            'social_links_order' => json_encode(['github', 'linkedin', 'twitter', 'email']),
            'skills_order' => json_encode(['penetration_testing', 'vulnerability_assessment', 'security_audit', 'incident_response'])
        ];
    }
}
?>