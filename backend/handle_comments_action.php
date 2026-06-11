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

// retrieve a comment
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $photoId = filter_input(INPUT_GET, 'photo_id', FILTER_VALIDATE_INT);
    if (!$photoId) {
        http_response_code(400);
        echo json_encode(["error" => "ID de photo manquant"]);
        exit;
    }

    try {
        $comments = $albumRepository->getCommentsByPhotoId($photoId);
        echo json_encode($comments);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur lors du chargement des commentaires"]);
    }
    exit;
}

// add a comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $photoId = isset($input['photo_id']) ? (int)$input['photo_id'] : null;
    $content = isset($input['content']) ? trim($input['content']) : '';

    if (!$photoId || empty($content)) {
        http_response_code(400);
        echo json_encode(["error" => "Données invalides ou commentaire vide"]);
        exit;
    }

    try {
        $success = $albumRepository->addComment($photoId, $userId, $content);
        if ($success) {
            echo json_encode(["success" => true, "message" => "Commentaire ajouté"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Impossible d'enregistrer le commentaire"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur serveur"]);
    }
    exit;
}