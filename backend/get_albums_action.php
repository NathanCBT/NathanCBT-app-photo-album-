<?php
session_start();
ini_set('display_errors', 0); 
header('Content-Type: application/json');

require_once __DIR__ . '/src/repositories/AlbumRepository.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

try {
    $albumRepository = new AlbumRepository();
    $albums = $albumRepository->getAlbumsByUserId((int)$_SESSION['user_id']);

    echo json_encode($albums);
} catch (Exception $e) {
    echo json_encode(["error" => "Erreur lors du chargement des albums"]);
}