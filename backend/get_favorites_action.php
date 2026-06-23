<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/src/repositories/AlbumRepository.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non authentifié.'
    ]);
    exit();
}

try {
    $userId = (int)$_SESSION['user_id'];
    $albumRepo = new AlbumRepository();
    
    // retrieving favorites photos
    $favorites = $albumRepo->getUserFavoritePhotos($userId);

    echo json_encode([
        'success' => true,
        'results' => $favorites
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des favoris : ' . $e->getMessage()
    ]);
}