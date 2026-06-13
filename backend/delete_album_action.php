<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/src/repositories/AlbumRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non autorisé"]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
$albumId = isset($input['album_id']) ? (int)$input['album_id'] : null;

if (!$albumId) {
    http_response_code(400);
    echo json_encode(["error" => "Identifiant d'album invalide."]);
    exit;
}

$albumRepository = new AlbumRepository();

try {
    // cover album information 
    $album = $albumRepository->getAlbumById($albumId);
    if (!$album || (int)$album['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(["error" => "Impossible de supprimer cet album (il ne vous appartient pas)."]);
        exit;
    }

    // list the photos in the album so that you can then clean the disk
    $photos = $albumRepository->getPhotosByAlbumId($albumId);
    $coverUrl = $album['cover_url'];
    
    $albumRepository->beginTransaction();
  
    $success = $albumRepository->deleteAlbum($albumId, $userId);
    
    if ($success) {
        $albumRepository->commit();
        
        // delete all photos from the album
        foreach ($photos as $photo) {
            $photoPath = __DIR__ . '/../' . $photo['file_path'];
            if (!empty($photo['file_path']) && file_exists($photoPath)) {
                unlink($photoPath);
            }
        }
        
        // delete the cover of the album
        if (!empty($coverUrl)) {
            $coverPath = __DIR__ . '/../' . $coverUrl;
            if (file_exists($coverPath)) {
                unlink($coverPath);
            }
        }

        echo json_encode(["success" => true, "message" => "Album et fichiers associés supprimés avec succès."]);
    } else {
        $albumRepository->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la suppression en base de données."]);
    }
} catch (Exception $e) {
    if (isset($albumRepository)) {
        $albumRepository->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur lors de la suppression."]);
}