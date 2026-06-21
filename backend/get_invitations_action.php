<?php
session_start();
header('Content-Type: application/json');

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/src/models/Database.php'; 

$userId = (int)$_SESSION['user_id'];

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT 
            ac.album_id,
            ac.rights,
            a.title AS album_title,
            u.username,
            COALESCE(u.display_name, u.username) AS host_name,
            u.avatar_url AS host_avatar
        FROM album_contributors ac
        JOIN albums a ON ac.album_id = a.id
        JOIN users u ON a.user_id = u.id
        WHERE ac.user_id = ? AND a.user_id != ?
        ORDER BY a.created_at DESC
    ");
    
    $stmt->execute([$userId, $userId]);
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($invitations);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}