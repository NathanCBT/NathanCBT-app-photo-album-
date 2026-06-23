<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/src/models/Database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Non autorisé."]);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

// if a profile_id is provided in the url and is not empty we use that one
// otherwise it means we are looking at our own profile
$targetUserId = (!empty($_GET['profile_id'])) ? (int)$_GET['profile_id'] : $currentUserId;

$type = $_GET['type'] ?? 'followers'; 

try {
    $pdo = Database::getConnection();
    
    if ($type === 'following') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.avatar_url 
            FROM follows f
            JOIN users u ON f.following_id = u.id
            WHERE f.follower_id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.avatar_url 
            FROM follows f
            JOIN users u ON f.follower_id = u.id
            WHERE f.following_id = ?
        ");
    }
    
    // we pass $targetUserId to the request instead of the raw session
    $stmt->execute([$targetUserId]);
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "list" => $list
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}