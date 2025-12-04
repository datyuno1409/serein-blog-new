<?php

require_once '../config/Model.php';

class About extends Model {
    protected $table = 'about';
    protected $fillable = ['content'];
    private $skillsTable = 'skills';
    private $socialLinksTable = 'social_links';
    private $testimonialsTable = 'testimonials';

    public function __construct() {
        parent::__construct();
    }

    public function getAboutContent() {
        try {
            $sql = "SELECT * FROM {$this->aboutTable} ORDER BY id DESC LIMIT 1";
            return $this->db->fetchOne($sql);
        } catch (Exception $e) {
            error_log("Get about content error: " . $e->getMessage());
            return false;
        }
    }

    public function updateAboutContent($content) {
        try {
            $existing = $this->getAboutContent();
            
            if ($existing) {
                return $this->db->update($this->aboutTable, ['content' => $content], 'id = :id', ['id' => $existing['id']]);
            } else {
                return $this->db->insert($this->aboutTable, ['content' => $content]);
            }
        } catch (Exception $e) {
            error_log("Update about content error: " . $e->getMessage());
            return false;
        }
    }

    public function getSkills() {
        try {
            $sql = "SELECT * FROM {$this->skillsTable} ORDER BY name ASC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Get skills error: " . $e->getMessage());
            return [];
        }
    }

    public function addSkill($name, $level) {
        try {
            $data = [
                'name' => $name,
                'level' => $level
            ];
            return $this->db->insert($this->skillsTable, $data);
        } catch (Exception $e) {
            error_log("Add skill error: " . $e->getMessage());
            return false;
        }
    }

    public function updateSkill($id, $name, $level) {
        try {
            $data = [
                'name' => $name,
                'level' => $level
            ];
            return $this->db->update($this->skillsTable, $data, 'id = :id', ['id' => $id]);
        } catch (Exception $e) {
            error_log("Update skill error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSkill($id) {
        try {
            return $this->db->delete($this->skillsTable, 'id = :id', ['id' => $id]);
        } catch (Exception $e) {
            error_log("Delete skill error: " . $e->getMessage());
            return false;
        }
    }

    public function getSocialLinks() {
        try {
            $sql = "SELECT * FROM {$this->socialLinksTable} ORDER BY platform ASC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Get social links error: " . $e->getMessage());
            return [];
        }
    }

    public function addSocialLink($platform, $url) {
        try {
            $data = [
                'platform' => $platform,
                'url' => $url
            ];
            return $this->db->insert($this->socialLinksTable, $data);
        } catch (Exception $e) {
            error_log("Add social link error: " . $e->getMessage());
            return false;
        }
    }

    public function updateSocialLink($id, $platform, $url) {
        try {
            $data = [
                'platform' => $platform,
                'url' => $url
            ];
            return $this->db->update($this->socialLinksTable, $data, 'id = :id', ['id' => $id]);
        } catch (Exception $e) {
            error_log("Update social link error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSocialLink($id) {
        try {
            return $this->db->delete($this->socialLinksTable, 'id = :id', ['id' => $id]);
        } catch (Exception $e) {
            error_log("Delete social link error: " . $e->getMessage());
            return false;
        }
    }

    public function getTestimonials() {
        try {
            $sql = "SELECT * FROM {$this->testimonialsTable} ORDER BY name ASC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Get testimonials error: " . $e->getMessage());
            return [];
        }
    }

    public function addTestimonial($name, $text, $company) {
        try {
            $data = [
                'name' => $name,
                'text' => $text,
                'company' => $company
            ];
            return $this->db->insert($this->testimonialsTable, $data);
        } catch (Exception $e) {
            error_log("Add testimonial error: " . $e->getMessage());
            return false;
        }
    }

    public function updateTestimonial($id, $name, $text, $company) {
        try {
            $data = [
                'name' => $name,
                'text' => $text,
                'company' => $company
            ];
            return $this->db->update($this->testimonialsTable, $data, 'id = :id', ['id' => $id]);
        } catch (Exception $e) {
            error_log("Update testimonial error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteTestimonial($id) {
        try {
            return $this->db->delete($this->testimonialsTable, 'id = :id', ['id' => $id]);
        } catch (Exception $e) {
            error_log("Delete testimonial error: " . $e->getMessage());
            return false;
        }
    }

    public function getSkillsCount() {
        try {
            return $this->db->count($this->skillsTable);
        } catch (Exception $e) {
            error_log("Get skills count error: " . $e->getMessage());
            return 0;
        }
    }

    public function getSocialLinksCount() {
        try {
            return $this->db->count($this->socialLinksTable);
        } catch (Exception $e) {
            error_log("Get social links count error: " . $e->getMessage());
            return 0;
        }
    }

    public function getTestimonialsCount() {
        try {
            return $this->db->count($this->testimonialsTable);
        } catch (Exception $e) {
            error_log("Get testimonials count error: " . $e->getMessage());
            return 0;
        }
    }

    public function validate($data, $isUpdate = false) {
        $errors = [];
        
        if (empty($data['content'])) {
            $errors['content'] = 'Content is required';
        } elseif (strlen($data['content']) < 10) {
            $errors['content'] = 'Content must be at least 10 characters long';
        } elseif (strlen($data['content']) > 10000) {
            $errors['content'] = 'Content must not exceed 10,000 characters';
        }
        
        return $errors;
    }
    
    public function sanitizeData($data) {
        $sanitized = [];
        
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                switch ($field) {
                    case 'content':
                        $sanitized[$field] = trim(strip_tags($data[$field], '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4><h5><h6>'));
                        break;
                    default:
                        $sanitized[$field] = htmlspecialchars(trim($data[$field]), ENT_QUOTES, 'UTF-8');
                        break;
                }
            }
        }
        
        return $sanitized;
    }
}
?>