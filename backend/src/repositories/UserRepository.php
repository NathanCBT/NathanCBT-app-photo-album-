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

    public function findById(int $userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function updateProfile(int $userId, string $bio, ?string $avatarUrl, ?string $bannerUrl): bool {
        $stmt = $this->db->prepare("
        UPDATE users SET bio = ?, avatar_url = ?, banner_url = ? 
        WHERE id = ?
        ");
        return $stmt->execute([$bio, $avatarUrl, $bannerUrl, $userId]);
    }

    public function isUsernameUnique(string $username, int $excludeUserId): bool {
        $stmt = $this->db->prepare("
        SELECT id 
        FROM users 
        WHERE username = ? AND id != ?
        ");
        $stmt->execute([$username, $excludeUserId]);
        return $stmt->fetch() === false;
    }

    public function isEmailUnique(string $email, int $excludeUserId): bool {
        $stmt = $this->db->prepare("
        SELECT id 
        FROM users 
        WHERE email = ? AND id != ?
        ");
        $stmt->execute([$email, $excludeUserId]);
        return $stmt->fetch() === false;
    }

    public function updateUsernameOrEmail(int $userId, ?string $username, ?string $email): bool {
        $fields = [];
        $params = [];

        if ($username !== null) {
            $fields[] = "username = ?";
            $params[] = $username;
        }
        if ($email !== null) {
            $fields[] = "email = ?";
            $params[] = $email;
        }
        if (empty($fields)) {
            return true;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updatePassword(int $userId, string $newHash): bool {
        $stmt = $this->db->prepare("
        UPDATE users SET password = ? 
        WHERE id = ?
        ");
        return $stmt->execute([$newHash, $userId]);
    }

    public function deleteById(int $userId): bool {
        $stmt = $this->db->prepare("
        DELETE FROM users 
        WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    public function getFollowersCount(int $userId): int {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM follows 
        WHERE following_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getFollowingCount(int $userId): int {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM follows 
        WHERE follower_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getFollowersList(int $userId): array {
        $stmt = $this->db->prepare("
        SELECT u.id, u.username, u.avatar_url 
        FROM follows f JOIN users u ON f.follower_id = u.id 
        WHERE f.following_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFollowingList(int $userId): array {
        $stmt = $this->db->prepare("
        SELECT u.id, u.username, u.avatar_url 
        FROM follows f JOIN users u ON f.following_id = u.id 
        WHERE f.follower_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isFollowing(int $followerId, int $followingId): bool {
        $stmt = $this->db->prepare("
        SELECT 1 FROM follows 
        WHERE follower_id = ? AND following_id = ?
        ");
        $stmt->execute([$followerId, $followingId]);
        return (bool)$stmt->fetch();
    }

    public function follow(int $followerId, int $followingId): bool {
        $stmt = $this->db->prepare("
        INSERT INTO follows (follower_id, following_id) VALUES (?, ?)
        ");
        return $stmt->execute([$followerId, $followingId]);
    }

    public function unfollow(int $followerId, int $followingId): bool {
        $stmt = $this->db->prepare("
        DELETE FROM follows 
        WHERE follower_id = ? AND following_id = ?
        ");
        return $stmt->execute([$followerId, $followingId]);
    }

    public function searchUsers(string $query, int $excludeUserId, int $limit = 10): array {
        $stmt = $this->db->prepare("
        SELECT id, username, avatar_url 
        FROM users 
        WHERE username LIKE ? AND id != ? LIMIT ?
        ");
        $stmt->execute(["%{$query}%", $excludeUserId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}