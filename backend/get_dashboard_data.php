<?php
ini_set('display_errors', 0); 
error_reporting(0);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/src/models/Database.php';
require_once __DIR__ . '/src/repositories/AlbumRepository.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Utilisateur non authentifié.']);
    exit();
}

try {
    $userId = (int)$_SESSION['user_id'];
    $db = Database::getConnection();
    $albumRepo = new AlbumRepository();

    // recovery of albums with the photo counter
    $stmtAlbums = $db->prepare("
        SELECT a.id, a.title, a.cover_url as banner, COUNT(p.id) as photos_count
        FROM albums a
        LEFT JOIN photos p ON a.id = p.album_id
        WHERE a.user_id = ?
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ");
    $stmtAlbums->execute([$userId]);
    $albums = $stmtAlbums->fetchAll(PDO::FETCH_ASSOC);

    // retrieving invitations
   $stmtInvites = $db->prepare("
        SELECT 
            ac.album_id,
            a.title AS album_title,
            u.username AS sender_name,
            u.avatar_url AS avatar
        FROM album_contributors ac
        JOIN albums a ON ac.album_id = a.id
        JOIN users u ON a.user_id = u.id
        WHERE ac.user_id = ? AND a.user_id != ?
        ORDER BY a.created_at DESC
    ");
    $stmtInvites->execute([$userId, $userId]);
    $allInvitations = $stmtInvites->fetchAll(PDO::FETCH_ASSOC);

    // separates the list (max 3 displayed on the dashboard) and the rest of the counter
    $displayedInvitations = array_slice($allInvitations, 0, 3);
    $moreCount = max(0, count($allInvitations) - 3);

    // retrieving favorites
    $favorites = $albumRepo->getUserFavoritePhotos($userId);

    echo json_encode([
        'success' => true,
        'albums' => $albums,
        'invitations' => [
            'list' => $displayedInvitations,
            'more_count' => $moreCount
        ],
        'favorites' => $favorites
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur serveur lors de la génération du tableau de bord.',
        'details' => $e->getMessage()
    ]);
}
exit();