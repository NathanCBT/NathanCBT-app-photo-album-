<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/src/repositories/AlbumRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non autorisé."]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$albumId = filter_input(INPUT_POST, 'album_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$visibility = trim($_POST['visibility'] ?? 'privé');

if (!$albumId || empty($title)) {
    http_response_code(400);
    echo json_encode(["error" => "Données manquantes ou invalides (Le titre est obligatoire)."]);
    exit;
}

if (!in_array($visibility, ['privé', 'restreint', 'public'])) {
    $visibility = 'privé';
}

$albumRepository = new AlbumRepository();

try {
    $album = $albumRepository->getAlbumById($albumId);
    if (!$album) {
        http_response_code(404);
        echo json_encode(["error" => "Album introuvable."]);
        exit;
    }

    $isOwner = ((int)$album['user_id'] === $userId);
    $contributorRight = $albumRepository->getContributorRights($albumId, $userId);
    $canModify = $isOwner || ($contributorRight === 'Peut modifier');

    if (!$canModify) {
        http_response_code(403);
        echo json_encode(["error" => "Vous n'avez pas les droits requis pour modifier cet album."]);
        exit;
    }

    $newCoverUrl = null;
    $oldCoverUrl = $album['cover_url'];

    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover_file'];
        $maxSize = 5 * 1024 * 1024; // 5 Mo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(["error" => "La couverture est trop lourde (Max 5Mo)."]);
            exit;
        }

        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(["error" => "Format d'image non supporté (JPEG, PNG ou WEBP uniquement)."]);
            exit;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'banner_' . uniqid() . '.' . $extension;
        
        $targetDir = __DIR__ . '/../frontend/uploads/albums/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $newCoverUrl = 'frontend/uploads/albums/' . $filename;
            
            if (!empty($oldCoverUrl)) {
                $oldAbsolutePath = __DIR__ . '/../' . $oldCoverUrl;
                if (file_exists($oldAbsolutePath)) {
                    unlink($oldAbsolutePath);
                }
            }
        }
    }

    $albumRepository->beginTransaction();

    $success = $albumRepository->updateAlbum($albumId, $title, $description, $newCoverUrl, $visibility);

    if ($success) {
        if ($visibility === 'restreint') {
            $invitedIds = $_POST['invited_users_ids'] ?? [];
            $invitedRights = $_POST['invited_users_rights'] ?? [];
            $albumRepository->syncContributors($albumId, $invitedIds, $invitedRights);
        } else {
            $albumRepository->removeAllContributors($albumId);
        }

        $tags = isset($_POST['tags']) ? (array)$_POST['tags'] : [];
        $albumRepository->syncAlbumTags($albumId, $tags);

        $updatedTags = $albumRepository->getAlbumTags($albumId);

        $albumRepository->commit();

        echo json_encode([
            "success" => true,
            "message" => "Album mis à jour avec succès !",
            "data" => [
                "title" => $title,
                "description" => $description,
                "visibility" => $visibility,
                "cover_url" => $newCoverUrl ?? $oldCoverUrl,
                "tags" => $updatedTags 
            ]
        ]);
    } else {
        $albumRepository->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la mise à jour en base de données."]);
    }

} catch (Exception $e) {
    if (isset($albumRepository)) {
        $albumRepository->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => "Erreur interne du serveur."]);
}