<?php
session_start();

require_once __DIR__ . '/../repositories/AlbumRepository.php';

class DashboardController {
    private $albumRepository;

    public function __construct() {
        $this->albumRepository = new AlbumRepository();
    }

    public function getDashboardData() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Utilisateur non authentifié.']);
            return;
        }

        try {
            $userId = (int)$_SESSION['user_id'];
            $albums = $this->albumRepository->getAlbumsWithPhotoCountsByUserId($userId);
            $allInvitations = $this->albumRepository->getInvitationsByUserId($userId);

            $displayedInvitations = array_slice($allInvitations, 0, 3);
            $moreCount = max(0, count($allInvitations) - 3);

            $favorites = $this->albumRepository->getUserFavoritePhotos($userId);

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
    }
}