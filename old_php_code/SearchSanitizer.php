<?php

class SearchSanitizer {
    private static $allowedSortFields = [
        'projects' => ['id', 'title', 'created_at', 'updated_at', 'featured', 'sort_order'],
        'skills' => ['id', 'name', 'level', 'sort_order'],
        'social_links' => ['id', 'platform', 'sort_order'],
        'testimonials' => ['id', 'name', 'company', 'sort_order'],
        'settings' => ['id', 'setting_key', 'setting_type']
    ];
    
    private static $allowedSortDirections = ['ASC', 'DESC'];
    
    public static function sanitizeSearchQuery($query) {
        if (empty($query)) {
            return '';
        }
        
        $query = trim($query);
        $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
        $query = preg_replace('/[^\w\s\-\.@]/', '', $query);
        $query = preg_replace('/\s+/', ' ', $query);
        
        return substr($query, 0, 100);
    }
    
    public static function sanitizeSortField($field, $table) {
        if (empty($field) || empty($table)) {
            return 'id';
        }
        
        $allowedFields = self::$allowedSortFields[$table] ?? ['id'];
        
        if (!in_array($field, $allowedFields)) {
            return 'id';
        }
        
        return $field;
    }
    
    public static function sanitizeSortDirection($direction) {
        $direction = strtoupper(trim($direction));
        
        if (!in_array($direction, self::$allowedSortDirections)) {
            return 'ASC';
        }
        
        return $direction;
    }
    
    public static function sanitizeLimit($limit, $maxLimit = 100) {
        $limit = intval($limit);
        
        if ($limit <= 0) {
            return 10;
        }
        
        if ($limit > $maxLimit) {
            return $maxLimit;
        }
        
        return $limit;
    }
    
    public static function sanitizeOffset($offset) {
        $offset = intval($offset);
        
        if ($offset < 0) {
            return 0;
        }
        
        return $offset;
    }
    
    public static function sanitizeFilters($filters, $table) {
        if (!is_array($filters)) {
            return [];
        }
        
        $sanitizedFilters = [];
        $allowedFields = self::$allowedSortFields[$table] ?? [];
        
        foreach ($filters as $field => $value) {
            if (in_array($field, $allowedFields) && !empty($value)) {
                $sanitizedFilters[$field] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            }
        }
        
        return $sanitizedFilters;
    }
    
    public static function buildSearchConditions($query, $searchFields) {
        if (empty($query) || !is_array($searchFields)) {
            return ['', []];
        }
        
        $conditions = [];
        $params = [];
        
        foreach ($searchFields as $field) {
            $conditions[] = "$field LIKE :search_$field";
            $params["search_$field"] = '%' . $query . '%';
        }
        
        $whereClause = '(' . implode(' OR ', $conditions) . ')';
        
        return [$whereClause, $params];
    }
    
    public static function buildFilterConditions($filters) {
        if (empty($filters) || !is_array($filters)) {
            return ['', []];
        }
        
        $conditions = [];
        $params = [];
        
        foreach ($filters as $field => $value) {
            $conditions[] = "$field = :filter_$field";
            $params["filter_$field"] = $value;
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        return [$whereClause, $params];
    }
    
    public static function validateSearchInput($input) {
        $errors = [];
        
        if (isset($input['query']) && strlen($input['query']) > 100) {
            $errors['query'] = 'Search query too long (max 100 characters)';
        }
        
        if (isset($input['limit']) && ($input['limit'] < 1 || $input['limit'] > 100)) {
            $errors['limit'] = 'Invalid limit (must be between 1 and 100)';
        }
        
        if (isset($input['offset']) && $input['offset'] < 0) {
            $errors['offset'] = 'Invalid offset (must be non-negative)';
        }
        
        return $errors;
    }
}
?>