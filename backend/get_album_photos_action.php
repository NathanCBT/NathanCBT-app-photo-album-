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

$albumId = filter_input(INPUT_GET, 'album_id', FILTER_VALIDATE_INT);
if (!$albumId) {
    http_response_code(400);
    echo json_encode(["error" => "ID d'album manquant ou invalide"]);
    exit;
}

try {
    $albumRepository = new AlbumRepository();
    
    // checking access rights to an album
    $album = $albumRepository->getAlbumById($albumId);
    if (!$album) {
        http_response_code(404);
        echo json_encode(["error" => "Album introuvable"]);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];
    $isOwner = ((int)$album['user_id'] === $userId);
    $contributorRight = $albumRepository->getContributorRights($albumId, $userId);

    if ($album['visibility'] === 'privé' && !$isOwner) {
        http_response_code(403);
        echo json_encode(["error" => "Accès refusé"]);
        exit;
    }
    if ($album['visibility'] === 'restreint' && !$isOwner && !$contributorRight) {
        http_response_code(403);
        echo json_encode(["error" => "Accès refusé"]);
        exit;
    }

    // retrieving photos and attaching tags
    $photos = $albumRepository->getPhotosByAlbumId($albumId);
    foreach ($photos as $index => $photo) {
        $photos[$index]['tags'] = $albumRepository->getPhotoTags((int)$photo['id']);
    }

    echo json_encode($photos);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur : " . $e->getMessage()]);
}