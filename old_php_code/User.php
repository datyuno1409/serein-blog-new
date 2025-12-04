<?php

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        global $database;
        $this->db = $database;
    }

    public function authenticate($username, $password) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
            $user = $this->db->fetchOne($sql, ['username' => $username]);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                return $user;
            }
            return false;
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
            return $this->db->fetchOne($sql, ['id' => $id]);
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByUsername($username) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
            return $this->db->fetchOne($sql, ['username' => $username]);
        } catch (Exception $e) {
            error_log("Get user by username error: " . $e->getMessage());
            return false;
        }
    }

    public function createUser($username, $password, $role = 'admin') {
        try {
            if ($this->getUserByUsername($username)) {
                return false;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $data = [
                'username' => $username,
                'password_hash' => $passwordHash,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s')
            ];

            return $this->db->insert($this->table, $data);
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }

    public function updatePassword($userId, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $data = ['password_hash' => $passwordHash];
            
            return $this->db->update($this->table, $data, 'id = :id', ['id' => $userId]);
        } catch (Exception $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($userId, $data) {
        try {
            if (isset($data['password'])) {
                $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                unset($data['password']);
            }
            
            return $this->db->update($this->table, $data, 'id = :id', ['id' => $userId]);
        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($userId) {
        try {
            return $this->db->delete($this->table, 'id = :id', ['id' => $userId]);
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $sql = "SELECT id, username, role, created_at FROM {$this->table} ORDER BY created_at DESC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    public function getUsersCount() {
        try {
            return $this->db->count($this->table);
        } catch (Exception $e) {
            error_log("Get users count error: " . $e->getMessage());
            return 0;
        }
    }

    public function isValidRole($role) {
        $validRoles = ['admin', 'editor', 'viewer'];
        return in_array($role, $validRoles);
    }
}
?>