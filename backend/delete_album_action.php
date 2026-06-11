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
    $success = $albumRepository->deleteAlbum($albumId, $userId);
    
    if ($success) {
        echo json_encode(["success" => true, "message" => "Album supprimé avec succès."]);
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Impossible de supprimer cet album (il ne vous appartient peut-être pas)."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur lors de la suppression."]);
}