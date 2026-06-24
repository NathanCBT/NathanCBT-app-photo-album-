<?php
require_once __DIR__ . '/../repositories/AlbumRepository.php';

class AlbumService {
    private $albumRepository;

    public function __construct() {
        $this->albumRepository = new AlbumRepository();
    }

    public function createFromRequest(int $userId, array $post, array $files): int {
        $title = trim($post['title'] ?? '');
        $description = trim($post['description'] ?? '');
        $visibility = $post['visibility'] ?? 'privé';

        if (empty($title)) {
            throw new Exception("Le nom de l'album est obligatoire.");
        }

        $invitedIds = $post['invited_users_ids'] ?? [];
        $invitedRights = $post['invited_users_rights'] ?? [];
        $albumTags = $post['album_tags'] ?? [];

        $this->albumRepository->beginTransaction();
        try {
            // cover upload
            $coverRelativeUrl = null;
            if (isset($files['cover_image']) && $files['cover_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $files['cover_image']['tmp_name'];
                $fileName = $files['cover_image']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExtension, $allowedExtensions)) {
                    $newBannerName = "banner_" . uniqid() . "." . $fileExtension;
                    $uploadTargetDir = __DIR__ . '/../../../frontend/uploads/albums/';

                    if (!is_dir($uploadTargetDir)) {
                        mkdir($uploadTargetDir, 0755, true);
                    }

                    if (move_uploaded_file($fileTmpPath, $uploadTargetDir . $newBannerName)) {
                        $coverRelativeUrl = "frontend/uploads/albums/" . $newBannerName;
                    }
                }
            }

            $albumId = $this->albumRepository->createAlbum($title, $description, $coverRelativeUrl, $visibility, $userId);

            if (!empty($albumTags) && is_array($albumTags)) {
                foreach ($albumTags as $tagId) {
                    $this->albumRepository->addTagToAlbum((int)$albumId, (int)$tagId);
                }
            }

            if ($visibility === 'restreint' && !empty($invitedIds)) {
                foreach ($invitedIds as $index => $invitedUserId) {
                    $right = $invitedRights[$index] ?? 'Peut voir';
                    if (!in_array($right, ['Peut voir', 'Peut commenter', 'Peut modifier'])) {
                        $right = 'Peut voir';
                    }
                    $this->albumRepository->addContributor((int)$albumId, (int)$invitedUserId, $right);
                }
            }

            // process multiple photos
            if (isset($files['photos']) && is_array($files['photos']['name'])) {
                $uploadPhotoDir = __DIR__ . '/../../../frontend/uploads/albums/';
                if (!is_dir($uploadPhotoDir)) mkdir($uploadPhotoDir, 0755, true);

                $photoDescriptions = $post['photo_descriptions'] ?? [];
                $photoDates = $post['photo_dates'] ?? [];
                $photoTags = $post['photo_tags'] ?? [];

                foreach ($files['photos']['name'] as $uniqueId => $originalName) {
                    if ($files['photos']['error'][$uniqueId] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $files['photos']['tmp_name'][$uniqueId];
                        $fileSize = $files['photos']['size'][$uniqueId];
                        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newPhotoName = "photo_" . uniqid() . "_" . $uniqueId . "." . $fileExtension;
                            $fullTargetPath = $uploadPhotoDir . $newPhotoName;

                            if (move_uploaded_file($fileTmpPath, $fullTargetPath)) {
                                $relativePhotoUrl = "frontend/uploads/albums/" . $newPhotoName;
                                $pDesc = !empty($photoDescriptions[$uniqueId]) ? trim($photoDescriptions[$uniqueId]) : null;
                                $pDate = !empty($photoDates[$uniqueId]) ? $photoDates[$uniqueId] : null;

                                $photoId = $this->albumRepository->createPhoto($albumId, $userId, $relativePhotoUrl, $fileSize, $pDesc, $pDate);

                                if (isset($photoTags[$uniqueId]) && is_array($photoTags[$uniqueId])) {
                                    foreach ($photoTags[$uniqueId] as $tagId) {
                                        $this->albumRepository->addTagToPhoto($photoId, (int)$tagId);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->albumRepository->commit();
            return (int)$albumId;
        } catch (Exception $e) {
            $this->albumRepository->rollBack();
            throw $e;
        }
    }

    public function addPhotosFromRequest(int $userId, int $albumId, array $post, array $files): array {
        // behavior mirrors controller.addPhotos
        $photoDescriptions = $post['descriptions'] ?? [];
        $photoDates = $post['dates'] ?? [];
        $photoTags = $post['tags'] ?? [];

        $uploadPhotoDir = __DIR__ . '/../../../frontend/uploads/albums/';
        if (!is_dir($uploadPhotoDir)) mkdir($uploadPhotoDir, 0755, true);

        $successCount = 0;
        try {
            $this->albumRepository->beginTransaction();
            foreach ($files['photos']['name'] as $index => $originalName) {
                if ($files['photos']['error'][$index] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $files['photos']['tmp_name'][$index];
                    $fileSize = $files['photos']['size'][$index];
                    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($fileExtension, $allowedExtensions)) {
                        $newPhotoName = "photo_" . uniqid() . "_" . $index . "." . $fileExtension;
                        $fullTargetPath = $uploadPhotoDir . $newPhotoName;

                        if (move_uploaded_file($fileTmpPath, $fullTargetPath)) {
                            $relativePhotoUrl = "frontend/uploads/albums/" . $newPhotoName;
                            $pDesc = !empty($photoDescriptions[$index]) ? trim($photoDescriptions[$index]) : null;
                            $pDate = !empty($photoDates[$index]) ? $photoDates[$index] : null;

                            $photoId = $this->albumRepository->createPhoto($albumId, $userId, $relativePhotoUrl, $fileSize, $pDesc, $pDate);

                            if (isset($photoTags[$index])) {
                                $checkedTags = json_decode($photoTags[$index], true);
                                if (is_array($checkedTags) && !empty($checkedTags)) {
                                    foreach ($checkedTags as $tagId) {
                                        $this->albumRepository->addTagToPhoto($photoId, (int)$tagId);
                                    }
                                }
                            }
                            $successCount++;
                        }
                    }
                }
            }
            $this->albumRepository->commit();
            return ['success' => $successCount > 0, 'count' => $successCount];
        } catch (Exception $e) {
            $this->albumRepository->rollBack();
            throw $e;
        }
    }

    public function updateFromRequest(int $userId, int $albumId, array $post, array $files): array {
        $title = trim($post['title'] ?? '');
        $description = trim($post['description'] ?? '');
        $visibility = trim($post['visibility'] ?? 'privé');

        if (empty($title)) throw new Exception('Données manquantes');

        $album = $this->albumRepository->getAlbumById($albumId);
        if (!$album) throw new Exception('Album introuvable');

        $isOwner = ((int)$album['user_id'] === $userId);
        $contributorRight = $this->albumRepository->getContributorRights($albumId, $userId);
        $canModify = $isOwner || ($contributorRight === 'Peut modifier');
        if (!$canModify) throw new Exception('Accès refusé');

        $newCoverUrl = null;
        $oldCoverUrl = $album['cover_url'];

        if (isset($files['cover_file']) && $files['cover_file']['error'] === UPLOAD_ERR_OK) {
            $file = $files['cover_file'];
            $maxSize = 5 * 1024 * 1024;
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if ($file['size'] > $maxSize) throw new Exception('Cover too large');
            if (!in_array($file['type'], $allowedTypes)) throw new Exception('Bad cover type');

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'banner_' . uniqid() . '.' . $extension;
            $targetDir = __DIR__ . '/../../../frontend/uploads/albums/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $targetPath = $targetDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $newCoverUrl = 'frontend/uploads/albums/' . $filename;
                if (!empty($oldCoverUrl)) {
                    $oldAbsolutePath = __DIR__ . '/../../../' . $oldCoverUrl;
                    if (file_exists($oldAbsolutePath)) unlink($oldAbsolutePath);
                }
            }
        }

        $this->albumRepository->beginTransaction();
        try {
            $success = $this->albumRepository->updateAlbum($albumId, $title, $description, $newCoverUrl, $visibility);
            if (!$success) {
                $this->albumRepository->rollBack();
                throw new Exception('Erreur lors de la mise à jour en base de données.');
            }

            if ($visibility === 'restreint') {
                $invitedIds = $post['invited_users_ids'] ?? [];
                $invitedRights = $post['invited_users_rights'] ?? [];
                $this->albumRepository->syncContributors($albumId, $invitedIds, $invitedRights);
            } else {
                $this->albumRepository->removeAllContributors($albumId);
            }

            $tags = isset($post['tags']) ? (array)$post['tags'] : [];
            $this->albumRepository->syncAlbumTags($albumId, $tags);

            $updatedTags = $this->albumRepository->getAlbumTags($albumId);
            $this->albumRepository->commit();

            return [
                'title' => $title,
                'description' => $description,
                'visibility' => $visibility,
                'cover_url' => $newCoverUrl ?? $oldCoverUrl,
                'tags' => $updatedTags
            ];
        } catch (Exception $e) {
            $this->albumRepository->rollBack();
            throw $e;
        }
    }

    public function deletePhoto(int $userId, int $photoId): bool {
        $photo = $this->albumRepository->getPhotoById($photoId);
        if (!$photo || (int)$photo['user_id'] !== $userId) {
            throw new Exception('Forbidden');
        }

        $relativeFilePath = $photo['file_path'];
        $success = $this->albumRepository->deletePhoto($photoId, $userId);
        if ($success && !empty($relativeFilePath)) {
            $absolutePath = __DIR__ . '/../../../' . $relativeFilePath;
            if (file_exists($absolutePath)) unlink($absolutePath);
        }
        return $success;
    }

    public function deleteAlbum(int $userId, int $albumId): bool {
        $album = $this->albumRepository->getAlbumById($albumId);
        if (!$album || (int)$album['user_id'] !== $userId) {
            throw new Exception('Forbidden');
        }

        $photos = $this->albumRepository->getPhotosByAlbumId($albumId);
        $coverUrl = $album['cover_url'];

        $this->albumRepository->beginTransaction();
        $success = $this->albumRepository->deleteAlbum($albumId, $userId);
        if ($success) {
            $this->albumRepository->commit();
            foreach ($photos as $photo) {
                $photoPath = __DIR__ . '/../../../' . $photo['file_path'];
                if (!empty($photo['file_path']) && file_exists($photoPath)) unlink($photoPath);
            }
            if (!empty($coverUrl)) {
                $coverPath = __DIR__ . '/../../../' . $coverUrl;
                if (file_exists($coverPath)) unlink($coverPath);
            }
            return true;
        }
        $this->albumRepository->rollBack();
        return false;
    }
}