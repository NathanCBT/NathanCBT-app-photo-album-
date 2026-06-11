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
$photoId = isset($input['photo_id']) ? (int)$input['photo_id'] : null;

if (!$photoId) {
    http_response_code(400);
    echo json_encode(["error" => "Identifiant de photo invalide."]);
    exit;
}

$albumRepository = new AlbumRepository();

try {
    $photo = $albumRepository->getPhotoById($photoId);
    
    if (!$photo || (int)$photo['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(["error" => "Vous n'avez pas le droit de supprimer cette photo."]);
        exit;
    }

    $success = $albumRepository->deletePhoto($photoId, $userId);
    
    if ($success) {
        $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $photo['file_path'];
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }
        
        echo json_encode(["success" => true, "message" => "Photo supprimée avec succès."]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors de la suppression en base de données."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur."]);
}