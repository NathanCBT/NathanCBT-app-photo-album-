<?php
session_start();
require_once __DIR__ . '/../repositories/AlbumRepository.php';

class AlbumController {
    private $albumRepository;

    public function __construct() {
        $this->albumRepository = new AlbumRepository();
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!isset($_SESSION['user_id'])) {
            header('Location: /frontend/pages/login-signin/html/login.php');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $visibility = $_POST['visibility'] ?? 'privé';

        // Récupération des invités
        $invitedIds = $_POST['invited_users_ids'] ?? [];
        $invitedRights = $_POST['invited_users_rights'] ?? [];

        // RÉCUPÉRATION DES TAGS DE L'ALBUM
        $albumTags = $_POST['album_tags'] ?? [];

        if (empty($title)) {
            $_SESSION['error'] = "Le nom de l'album est obligatoire.";
            header('Location: ../../../frontend/pages/album/html/create-album.php');
            exit;
        }

        try {
            $this->albumRepository->beginTransaction();

            // cover upload
            $coverRelativeUrl = null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['cover_image']['tmp_name'];
                $fileName = $_FILES['cover_image']['name'];
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

            // album tag recording
            if (!empty($albumTags) && is_array($albumTags)) {
                foreach ($albumTags as $tagId) {
                    $this->albumRepository->addTagToAlbum((int)$albumId, (int)$tagId);
                }
            }

            // managing collaborators if the album is restricted
            if ($visibility === 'restreint' && !empty($invitedIds)) {
                foreach ($invitedIds as $index => $invitedUserId) {
                    $right = $invitedRights[$index] ?? 'Peut voir';
                    
                    // avoids injections on the enum
                    if (!in_array($right, ['Peut voir', 'Peut commenter', 'Peut modifier'])) {
                        $right = 'Peut voir';
                    }

                    // registration via repository
                    $this->albumRepository->addContributor((int)$albumId, (int)$invitedUserId, $right);
                }
            }

            // multiple photo processing
            if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
                $uploadPhotoDir = __DIR__ . '/../../../frontend/uploads/albums/'; 

                $photoDescriptions = $_POST['photo_descriptions'] ?? [];
                $photoDates = $_POST['photo_dates'] ?? [];
                $photoTags = $_POST['photo_tags'] ?? [];

                // we loop directly through the unique keys received in the $_FILES
                foreach ($_FILES['photos']['name'] as $uniqueId => $originalName) {
                    if ($_FILES['photos']['error'][$uniqueId] === UPLOAD_ERR_OK) {
                        
                        $fileTmpPath = $_FILES['photos']['tmp_name'][$uniqueId];
                        $fileSize = $_FILES['photos']['size'][$uniqueId];
                        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newPhotoName = "photo_" . uniqid() . "_" . $uniqueId . "." . $fileExtension;
                            $fullTargetPath = $uploadPhotoDir . $newPhotoName;

                            if (move_uploaded_file($fileTmpPath, $fullTargetPath)) {
                                $relativePhotoUrl = "frontend/uploads/albums/" . $newPhotoName;

                                // direct recovery using the `$uniqueId` key without risk of offset
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

            header('Location: ../../../frontend/pages/album/html/mes-albums.php?success=1');
            exit;

        } catch (Exception $e) {
            $this->albumRepository->rollBack();
            die("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }
}