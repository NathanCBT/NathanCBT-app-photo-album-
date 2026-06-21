<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/src/models/Database.php'; 
require_once __DIR__ . '/src/repositories/AlbumRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non autorisé."]);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$userId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;
$albumRepository = new AlbumRepository();

try {
    $pdo = Database::getConnection();
    
    $stmt = $pdo->prepare("
    SELECT bio, banner_url, avatar_url 
    FROM users 
    WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $dbUser = $stmt->fetch();

    $userData = [
        "bio" => $dbUser['bio'] ?? "Aucune biographie pour le moment.",
        "banner" => $dbUser['banner_url'] ?? "",
        "avatar" => $dbUser['avatar_url'] ?? "frontend/assets/IMG/default-avatar.svg"
    ];

    // retrieving albums and calculating photos
    $albums = $albumRepository->getAlbumsByUserId($userId);

    $totalPhotos = 0;
    foreach ($albums as $album) {
        $photos = $albumRepository->getPhotosByAlbumId((int)$album['id']);
        $totalPhotos += count($photos);
    }
    
    // count subscribers
    $stmtFollowers = $pdo->prepare("
    SELECT COUNT(*) 
    FROM follows 
    WHERE following_id = ?
    ");
    $stmtFollowers->execute([$userId]);
    $followersCount = $stmtFollowers->fetchColumn();

    // count subscriptions
    $stmtFollowing = $pdo->prepare("
    SELECT COUNT(*) 
    FROM follows 
    WHERE follower_id = ?
    ");
    $stmtFollowing->execute([$userId]);
    $followingCount = $stmtFollowing->fetchColumn();

    echo json_encode([
        "success" => true,
        "user" => $userData,
        "stats" => [
            "albums_count" => count($albums),
            "photos_count" => $totalPhotos, 
            "followers_count" => $followersCount,
            "following_count" => $followingCount
        ],
        "albums" => $albums
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}