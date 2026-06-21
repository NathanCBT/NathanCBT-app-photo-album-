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
$targetUserId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;

if ($targetUserId === 0 || $targetUserId === $currentUserId) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Action invalide."]);
    exit;
}

try {
    $pdo = Database::getConnection();

    // check if the subscription already exists
    $stmtCheck = $pdo->prepare("
    SELECT 1 
    FROM follows 
    WHERE follower_id = ? 
    AND following_id = ?
    ");
    $stmtCheck->execute([$currentUserId, $targetUserId]);
    $isFollowing = $stmtCheck->fetchColumn();

    if ($isFollowing) {
        // unfollow
        $stmtDelete = $pdo->prepare("
        DELETE 
        FROM follows 
        WHERE follower_id = ? 
        AND following_id = ?
        ");
        $stmtDelete->execute([$currentUserId, $targetUserId]);
        $status = "follow"; 
    } else {
        // follow
        $stmtInsert = $pdo->prepare("
        INSERT INTO follows (follower_id, following_id) 
        VALUES (?, ?)
        ");
        $stmtInsert->execute([$currentUserId, $targetUserId]);
        $status = "unfollow"; 
    }

    // retrieve the new subscriber count of the visited profile for live display
    $stmtCount = $pdo->prepare("
    SELECT COUNT(*) 
    FROM follows 
    WHERE following_id = ?
    ");
    $stmtCount->execute([$targetUserId]);
    $newFollowersCount = $stmtCount->fetchColumn();

    echo json_encode([
        "success" => true,
        "status" => $status,
        "followers_count" => $newFollowersCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}