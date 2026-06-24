<?php
session_start();

require_once __DIR__ . '/../Logger.php';
require_once __DIR__ . '/../repositories/PhotoRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class SearchController {
    public function searchPhotos() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $criteria = [
            'search' => isset($input['search']) ? trim($input['search']) : '',
            'tags' => isset($input['tags']) ? $input['tags'] : [],
            'date_from' => isset($input['date_from']) ? $input['date_from'] : '',
            'date_to' => isset($input['date_to']) ? $input['date_to'] : '',
            'scope' => isset($input['scope']) ? $input['scope'] : 'owned'
        ];

        try {
            $photoRepo = new PhotoRepository();
            $photos = $photoRepo->searchPhotos($userId, $criteria);

            echo json_encode(['success' => true, 'results' => $photos]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
    }

    public function searchUsers() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé.']);
            return;
        }

        $query = trim($_GET['q'] ?? '');
        if ($query === '') {
            echo json_encode([]);
            return;
        }

        try {
            $userRepo = new UserRepository();
            $users = $userRepo->searchUsers($query, (int)$_SESSION['user_id']);
            echo json_encode($users);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Erreur lors de la recherche']);
        }
    }
}