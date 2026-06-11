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
$albumRepository = new AlbumRepository();

// recovery of settings
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $photoId = filter_input(INPUT_GET, 'photo_id', FILTER_VALIDATE_INT);
    if (!$photoId) {
        http_response_code(400);
        echo json_encode(["error" => "ID manquant"]);
        exit;
    }
    
    $isFav = $albumRepository->isPhotoFavorite($photoId, $userId);
    echo json_encode(["is_favorite" => $isFav]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $photoId = isset($input['photo_id']) ? (int)$input['photo_id'] : null;

    if (!$photoId) {
        http_response_code(400);
        echo json_encode(["error" => "ID invalide"]);
        exit;
    }

    // if it's already in favorites remove it otherwise add it
    $alreadyFav = $albumRepository->isPhotoFavorite($photoId, $userId);
    
    if ($alreadyFav) {
        $success = $albumRepository->removePhotoFromFavorites($photoId, $userId);
        echo json_encode(["success" => $success, "is_favorite" => false]);
    } else {
        $success = $albumRepository->addPhotoToFavorites($photoId, $userId);
        echo json_encode(["success" => $success, "is_favorite" => true]);
    }
    exit;
}