<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

require_once __DIR__ . '/src/models/Database.php';
require_once __DIR__ . '/src/repositories/PhotoRepository.php';

try {
    // retrieving json data sent by Fetch
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];

    $db = new Database();
    $pdo = $db->getConnection();
    $photoRepo = new PhotoRepository($pdo);

    $userId = $_SESSION['user_id'];
    
    // cleaning of received criteria
    $criteria = [
        'search'    => isset($input['search']) ? trim($input['search']) : '',
        'tags'      => isset($input['tags']) ? $input['tags'] : [],
        'date_from' => isset($input['date_from']) ? $input['date_from'] : '',
        'date_to'   => isset($input['date_to']) ? $input['date_to'] : '',
        'scope'     => isset($input['scope']) ? $input['scope'] : 'owned'
    ];

    // research
    $photos = $photoRepo->searchPhotos($userId, $criteria);

    echo json_encode([
        'success' => true,
        'results' => $photos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}