<?php
session_start();
require_once __DIR__ . '/../Logger.php';
require_once __DIR__ . '/../repositories/AlbumRepository.php';
require_once __DIR__ . '/../services/AlbumService.php';

class AlbumController {
    private $albumRepository;
    private $albumService;

    public function __construct() {
        $this->albumRepository = new AlbumRepository();
        $this->albumService = new AlbumService();
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!isset($_SESSION['user_id'])) {
            header('Location: /frontend/pages/login-signin/html/login.php');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $albumTitle = trim($_POST['title'] ?? '');

        try {
            $albumId = $this->albumService->createFromRequest($userId, $_POST, $_FILES);
            Logger::info($userId, 'album_create', 'album_id=' . $albumId . '; title=' . addslashes(substr($albumTitle, 0, 100)));
            header('Location: ../../../frontend/pages/album/html/mes-albums.php?success=1');
            exit;
        } catch (Exception $e) {
            Logger::error('Echec création album', $e);
            $_SESSION['error'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            header('Location: ../../../frontend/pages/album/html/create-album.php');
            exit;
        }
    }
    
    // returns the list of albums
    public function getAlbums() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([]);
            return;
        }

        try {
            $albums = $this->albumRepository->getAlbumsByUserId((int)$_SESSION['user_id']);
            echo json_encode($albums);
        } catch (Exception $e) {
            echo json_encode(["error" => "Erreur lors du chargement des albums"]);
        }
    }

    public function getAlbumPhotos() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Non autorisé"]);
            return;
        }

        $albumId = filter_input(INPUT_GET, 'album_id', FILTER_VALIDATE_INT);
        if (!$albumId) {
            http_response_code(400);
            echo json_encode(["error" => "ID d'album manquant ou invalide"]);
            return;
        }

        try {
            $album = $this->albumRepository->getAlbumById($albumId);
            if (!$album) {
                http_response_code(404);
                echo json_encode(["error" => "Album introuvable"]);
                return;
            }

            $userId = (int)$_SESSION['user_id'];
            $isOwner = ((int)$album['user_id'] === $userId);
            $contributorRight = $this->albumRepository->getContributorRights($albumId, $userId);

            if ($album['visibility'] === 'privé' && !$isOwner) {
                http_response_code(403);
                echo json_encode(["error" => "Accès refusé"]);
                return;
            }
            if ($album['visibility'] === 'restreint' && !$isOwner && !$contributorRight) {
                http_response_code(403);
                echo json_encode(["error" => "Accès refusé"]);
                return;
            }

            $photos = $this->albumRepository->getPhotosByAlbumId($albumId);
            foreach ($photos as $index => $photo) {
                $photos[$index]['tags'] = $this->albumRepository->getPhotoTags((int)$photo['id']);
            }

            Logger::info($userId, 'album_view', 'album_id=' . $albumId . '; visibility=' . $album['visibility']);
            echo json_encode($photos);
        } catch (Exception $e) {
            Logger::error('Erreur lecture album', $e);
            http_response_code(500);
            echo json_encode(["error" => "Erreur serveur : " . $e->getMessage()]);
        }
    }

    public function addPhotos() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Requête invalide']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Session expirée ou utilisateur non connecté.']);
            return;
        }
        $userId = (int)$_SESSION['user_id'];

        $album_id = isset($_POST['album_id']) ? (int)$_POST['album_id'] : 0;
        if (!$album_id) {
            echo json_encode(['success' => false, 'error' => 'ID de l\'album manquant']);
            return;
        }

        $album = $this->albumRepository->getAlbumById($album_id);
        if (!$album) {
            echo json_encode(['success' => false, 'error' => 'Album introuvable.']);
            return;
        }

        $isOwner = ((int)$album['user_id'] === $userId);
        $rights = $this->albumRepository->getContributorRights($album_id, $userId);

        if (!$isOwner && $rights !== 'Peut modifier') {
            echo json_encode(['success' => false, 'error' => 'Vous n\'avez pas les droits nécessaires pour ajouter des photos.']);
            return;
        }

        if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['name'])) {
            echo json_encode(['success' => false, 'error' => 'Aucune image reçue']);
            return;
        }

        $photoDescriptions = $_POST['descriptions'] ?? [];
        $photoDates = $_POST['dates'] ?? [];
        $photoTags = $_POST['tags'] ?? [];

        $uploadPhotoDir = __DIR__ . '/../../../frontend/uploads/albums/';
        if (!is_dir($uploadPhotoDir)) {
            mkdir($uploadPhotoDir, 0755, true);
        }

        try {
            $result = $this->albumService->addPhotosFromRequest($userId, $album_id, $_POST, $_FILES);
            if ($result['success']) {
                Logger::info($userId, 'photo_upload', 'album_id=' . $album_id . '; count=' . $result['count']);
            }

            echo json_encode([
                'success' => $result['success'],
                'message' => $result['success'] ? ($result['count'] . ' photo(s) importée(s) avec succès.') : 'Aucune photo importée.'
            ]);
            return;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()
            ]);
            return;
        }
    }

    public function handleFavorite() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Non autorisé"]);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $photoId = filter_input(INPUT_GET, 'photo_id', FILTER_VALIDATE_INT);
            if (!$photoId) {
                http_response_code(400);
                echo json_encode(["error" => "ID manquant"]);
                return;
            }

            $isFav = $this->albumRepository->isPhotoFavorite($photoId, $userId);
            echo json_encode(["is_favorite" => $isFav]);
            return;
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $photoId = isset($input['photo_id']) ? (int)$input['photo_id'] : null;

            if (!$photoId) {
                http_response_code(400);
                echo json_encode(["error" => "ID invalide"]);
                return;
            }

            $alreadyFav = $this->albumRepository->isPhotoFavorite($photoId, $userId);
            if ($alreadyFav) {
                $success = $this->albumRepository->removePhotoFromFavorites($photoId, $userId);
                if ($success) {
                    Logger::info($userId, 'photo_unlike', 'photo_id=' . $photoId);
                }
                echo json_encode(["success" => $success, "is_favorite" => false]);
            } else {
                $success = $this->albumRepository->addPhotoToFavorites($photoId, $userId);
                if ($success) {
                    Logger::info($userId, 'photo_like', 'photo_id=' . $photoId);
                }
                echo json_encode(["success" => $success, "is_favorite" => true]);
            }
            return;
        }
    }

    public function handleComments() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Non autorisé"]);
            return;
        }

        $userId = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $photoId = filter_input(INPUT_GET, 'photo_id', FILTER_VALIDATE_INT);
            if (!$photoId) {
                http_response_code(400);
                echo json_encode(["error" => "ID de photo manquant"]);
                return;
            }

            try {
                $comments = $this->albumRepository->getCommentsByPhotoId($photoId);
                echo json_encode($comments);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["error" => "Erreur lors du chargement des commentaires"]);
            }
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = isset($input['action']) ? trim($input['action']) : 'create';

            try {
                if ($action === 'delete') {
                    $commentId = isset($input['comment_id']) ? (int)$input['comment_id'] : null;
                    if (!$commentId) {
                        http_response_code(400);
                        echo json_encode(["error" => "ID du commentaire manquant"]);
                        return;
                    }

                    $success = $this->albumRepository->deleteComment($commentId, $userId);
                    if ($success) {
                        Logger::info($userId, 'comment_delete', 'comment_id=' . $commentId);
                        echo json_encode(["success" => true, "message" => "Commentaire supprimé"]);
                    } else {
                        http_response_code(403);
                        echo json_encode(["error" => "Impossible de supprimer (Droit refusé ou introuvable)"]);
                    }
                    return;
                }

                if ($action === 'update') {
                    $commentId = isset($input['comment_id']) ? (int)$input['comment_id'] : null;
                    $content = isset($input['content']) ? trim($input['content']) : '';

                    if (!$commentId || empty($content)) {
                        http_response_code(400);
                        echo json_encode(["error" => "Données invalides ou texte vide"]);
                        return;
                    }

                    $success = $this->albumRepository->updateComment($commentId, $userId, $content);
                    if ($success) {
                        Logger::info($userId, 'comment_update', 'comment_id=' . $commentId . '; length=' . strlen($content));
                        echo json_encode(["success" => true, "message" => "Commentaire mis à jour"]);
                    } else {
                        http_response_code(403);
                        echo json_encode(["error" => "Impossible de modifier (Droit refusé ou introuvable)"]);
                    }
                    return;
                }

                if ($action === 'create' || !isset($input['action'])) {
                    $photoId = isset($input['photo_id']) ? (int)$input['photo_id'] : null;
                    $content = isset($input['content']) ? trim($input['content']) : '';

                    if (!$photoId || empty($content)) {
                        http_response_code(400);
                        echo json_encode(["error" => "Données invalides ou commentaire vide"]);
                        return;
                    }

                    $success = $this->albumRepository->addComment($photoId, $userId, $content);
                    if ($success) {
                        Logger::info($userId, 'comment_create', 'photo_id=' . $photoId . '; length=' . strlen($content));
                        echo json_encode(["success" => true, "message" => "Commentaire ajouté"]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "Impossible d'enregistrer le commentaire"]);
                    }
                    return;
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["error" => "Erreur serveur"]);
            }
            return;
        }
    }

    public function getFavorites() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Utilisateur non authentifié.'
            ]);
            return;
        }

        try {
            $userId = (int)$_SESSION['user_id'];
            $favorites = $this->albumRepository->getUserFavoritePhotos($userId);

            echo json_encode([
                'success' => true,
                'results' => $favorites
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la récupération des favoris : ' . $e->getMessage()
            ]);
        }
    }

    public function editAlbumApi() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Non autorisé."]);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $albumId = filter_input(INPUT_POST, 'album_id', FILTER_VALIDATE_INT);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $visibility = trim($_POST['visibility'] ?? 'privé');

        if (!$albumId || empty($title)) {
            http_response_code(400);
            echo json_encode(["error" => "Données manquantes ou invalides (Le titre est obligatoire)."]);
            return;
        }

        if (!in_array($visibility, ['privé', 'restreint', 'public'])) {
            $visibility = 'privé';
        }

        try {
            $result = $this->albumService->updateFromRequest($userId, $albumId, $_POST, $_FILES);
            Logger::info($userId, 'album_update', 'album_id=' . $albumId . '; title=' . addslashes(substr($title, 0, 100)) . '; visibility=' . $visibility . '; description_length=' . strlen($description));
            echo json_encode([
                "success" => true,
                "message" => "Album mis à jour avec succès !",
                "data" => $result
            ]);
        } catch (Exception $e) {
            Logger::error('Erreur edition album', $e);
            http_response_code(500);
            echo json_encode(["error" => "Erreur interne du serveur: " . $e->getMessage()]);
        }
    }

    public function deletePhotoApi() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Non autorisé"]);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);
        $photoId = isset($input['photo_id']) ? (int)$input['photo_id'] : null;

        if (!$photoId) {
            http_response_code(400);
            echo json_encode(["error" => "Identifiant de photo invalide."]);
            return;
        }

        try {
            $success = $this->albumService->deletePhoto($userId, $photoId);
            if ($success) {
                Logger::info($userId, 'photo_delete', 'photo_id=' . $photoId);
                echo json_encode(["success" => true, "message" => "Photo supprimée avec succès."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erreur lors de la suppression en base de données."]);
            }
        } catch (Exception $e) {
            Logger::error('Erreur suppression photo', $e);
            if ($e->getMessage() === 'Forbidden') {
                http_response_code(403);
                echo json_encode(["error" => "Vous n'avez pas le droit de supprimer cette photo."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erreur serveur: " . $e->getMessage()]);
            }
        }
    }

    public function deleteAlbumApi() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["error" => "Non autorisé"]);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);
        $albumId = isset($input['album_id']) ? (int)$input['album_id'] : null;

        if (!$albumId) {
            http_response_code(400);
            echo json_encode(["error" => "Identifiant d'album invalide."]);
            return;
        }

        try {
            $success = $this->albumService->deleteAlbum($userId, $albumId);
            if ($success) {                Logger::info($userId, 'album_delete', 'album_id=' . $albumId);                echo json_encode(["success" => true, "message" => "Album et fichiers associés supprimés avec succès."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erreur lors de la suppression en base de données."]);
            }
        } catch (Exception $e) {
            Logger::error('Erreur suppression album', $e);
            if ($e->getMessage() === 'Forbidden') {
                http_response_code(403);
                echo json_encode(["error" => "Impossible de supprimer cet album (il ne vous appartient pas)."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erreur serveur lors de la suppression: " . $e->getMessage()]);
            }
        }
    }

    public function getInvitations() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Non authentifié']);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        try {
            $invitations = $this->albumRepository->getInvitationsByUserId($userId);
            echo json_encode($invitations);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
        }
    }
}