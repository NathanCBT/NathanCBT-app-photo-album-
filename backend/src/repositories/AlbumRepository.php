<?php
require_once __DIR__ . '/../models/Database.php';

class AlbumRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function beginTransaction() {
        $this->db->beginTransaction();
    }

    public function commit() {
        $this->db->commit();
    }

    public function rollBack() {
        $this->db->rollBack();
    }

    public function getAlbumsByUserId(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id, title, description, cover_url, visibility 
            FROM albums 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createAlbum($title, $description, $coverUrl, $visibility, $userId) {
        $stmt = $this->db->prepare("
        INSERT INTO albums (title, description, cover_url, visibility, user_id, created_at) 
        VALUES (?, ?, ?, ?, ?, 
        NOW())
        ");
        $stmt->execute([$title, $description, $coverUrl, $visibility, $userId]);
        return $this->db->lastInsertId();
    }

    public function createPhoto($albumId, $userId, $filePath, $fileSize, $description, $shotAt) {
        $stmt = $this->db->prepare("
        INSERT INTO photos (album_id, user_id, file_path, file_size, description, shot_at, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 
        NOW())
        ");
        $stmt->execute([$albumId, $userId, $filePath, $fileSize, $description, $shotAt]);
        return $this->db->lastInsertId();
    }

    public function addTagToPhoto($photoId, $tagId) {
        $stmt = $this->db->prepare("
        INSERT INTO photo_tags (photo_id, tag_id) 
        VALUES (?, ?)
        ");
        return $stmt->execute([$photoId, $tagId]);
    }

    public function addContributor(int $albumId, int $userId, string $rights): bool {
        $stmt = $this->db->prepare("
            INSERT INTO album_contributors (album_id, user_id, rights) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$albumId, $userId, $rights]);
    }

    public function getAlbumById(int $albumId) {
        $stmt = $this->db->prepare("
            SELECT id, title, description, cover_url, visibility, user_id, created_at 
            FROM albums 
            WHERE id = ?
        ");
        $stmt->execute([$albumId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getContributorRights(int $albumId, int $userId): ?string {
        $stmt = $this->db->prepare("
            SELECT rights 
            FROM album_contributors 
            WHERE album_id = ? AND user_id = ?
        ");
        $stmt->execute([$albumId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['rights'] : null;
    }

    public function getPhotosByAlbumId(int $albumId): array {
        $stmt = $this->db->prepare("
            SELECT id, file_path, file_size, description, shot_at, created_at 
            FROM photos 
            WHERE album_id = ? 
            ORDER BY shot_at ASC, created_at ASC
        ");
        $stmt->execute([$albumId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPhotoTags(int $photoId): array {
        $stmt = $this->db->prepare("
            SELECT t.id, t.name 
            FROM tags t
            JOIN photo_tags pt ON t.id = pt.tag_id
            WHERE pt.photo_id = ?
        ");
        $stmt->execute([$photoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment(int $photoId, int $userId, string $content): bool {
        $stmt = $this->db->prepare("
            INSERT INTO comments (photo_id, user_id, content, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$photoId, $userId, $content]);
    }

    public function getCommentsByPhotoId(int $photoId): array {
        $stmt = $this->db->prepare("
            SELECT c.id, c.content, c.created_at, u.username, u.avatar_url 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.photo_id = ? 
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$photoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPhotoToFavorites(int $photoId, int $userId): bool {
        $stmt = $this->db->prepare("
        INSERT IGNORE INTO favorites (user_id, photo_id) VALUES (?, ?)
        ");
        return $stmt->execute([$userId, $photoId]);
    }

    public function removePhotoFromFavorites(int $photoId, int $userId): bool {
        $stmt = $this->db->prepare("
        DELETE FROM favorites 
        WHERE user_id = ? AND photo_id = ?
        ");
        return $stmt->execute([$userId, $photoId]);
    }

    public function isPhotoFavorite(int $photoId, int $userId): bool {
        $stmt = $this->db->prepare("
        SELECT 1 FROM favorites 
        WHERE user_id = ? AND photo_id = ?
        ");
        $stmt->execute([$userId, $photoId]);
        return (bool)$stmt->fetch();
    }

    public function deleteAlbum(int $albumId, int $userId): bool {
        // verify that the album belongs to the logged-in user
        $stmt = $this->db->prepare("
        DELETE FROM albums 
        WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$albumId, $userId]);
    }

    public function getPhotoById(int $photoId): ?array {
        $stmt = $this->db->prepare("
        SELECT id, album_id, file_path, user_id 
        FROM photos 
        WHERE id = ?
        ");
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $photo ?: null;
    }

    public function deletePhoto(int $photoId, int $userId): bool {
        $stmt = $this->db->prepare("
        DELETE FROM photos 
        WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$photoId, $userId]);
    }
}