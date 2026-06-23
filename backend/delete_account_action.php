<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié.']);
    exit;
}

require_once __DIR__ . '/src/models/Database.php';
$userId = (int)$_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

try {
    $pdo = Database::getConnection();

    // password validation and profile picture retrieval
    $stmt = $pdo->prepare("
    SELECT password, avatar_url, banner_url 
    FROM users 
    WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['error' => 'Mot de passe incorrect. Impossible de supprimer le compte.']);
        exit;
    }

    // cleaning files on the server
    
    $basePath = dirname(__DIR__); 
    $filesToDelete = [];

    if (!empty($user['avatar_url'])) {
        $filesToDelete[] = $basePath . '/' . ltrim($user['avatar_url'], '/');
    }
    if (!empty($user['banner_url'])) {
        $filesToDelete[] = $basePath . '/' . ltrim($user['banner_url'], '/');
    }

    $stmtAlbums = $pdo->prepare("
    SELECT cover_url 
    FROM albums 
    WHERE user_id = ?
    ");
    $stmtAlbums->execute([$userId]);
    $albums = $stmtAlbums->fetchAll(PDO::FETCH_ASSOC);
    foreach ($albums as $album) {
        if (!empty($album['cover_url'])) {
            $filesToDelete[] = $basePath . '/' . ltrim($album['cover_url'], '/');
        }
    }

    $stmtPhotos = $pdo->prepare("
    SELECT file_path 
    FROM photos 
    WHERE user_id = ?
    ");
    $stmtPhotos->execute([$userId]);
    $photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
    foreach ($photos as $photo) {
        if (!empty($photo['file_path'])) {
            $filesToDelete[] = $basePath . '/' . ltrim($photo['file_path'], '/');
        }
    }

    // physical deletion of files
    foreach ($filesToDelete as $filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            unlink($filePath);
        }
    }


    // deletion from the Database
    $delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $delete->execute([$userId]);

    session_destroy();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
}