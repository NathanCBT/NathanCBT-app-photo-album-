<?php
require_once __DIR__ . '/../models/Database.php';

class UserRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByEmailOrUsername($identifier) {
        $stmt = $this->db->prepare("
        SELECT * FROM users WHERE email = ? OR username = ?
        ");
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch();
    }

    // register
    public function create($username, $email, $hashedPassword, $displayName) {
        $stmt = $this->db->prepare("
        INSERT INTO users (username, email, password, display_name) 
        VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $hashedPassword, $displayName]);
        return $this->db->lastInsertId();
    }

    // update the avatar URL after uploading.
    public function updateAvatar($userId, $avatarUrl) {
        $stmt = $this->db->prepare("
        UPDATE users SET avatar_url = ? WHERE id = ?
        ");
        return $stmt->execute([$avatarUrl, $userId]);
    }
}