<?php
require_once __DIR__ . '/src/controllers/AlbumController.php';
require_once __DIR__ . '/src/controllers/AuthController.php';

$route = $_GET['route'] ?? null;
if (!$route) {
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Router actif. Passez param ?route=domain.action']);
    exit;
}

$parts = explode('.', $route);
$domain = $parts[0] ?? null;
$action = $parts[1] ?? null;

switch ($domain) {
    case 'album':
        $ctrl = new AlbumController();
        switch ($action) {
            case 'get_photos':
                $ctrl->getAlbumPhotos();
                break;
            case 'add_photos':
                $ctrl->addPhotos();
                break;
            case 'favorites':
                $ctrl->getFavorites();
                break;
            case 'handle_favorite':
                $ctrl->handleFavorite();
                break;
            case 'comments':
                $ctrl->handleComments();
                break;
            case 'delete_photo':
                $ctrl->deletePhotoApi();
                break;
            case 'delete_album':
                $ctrl->deleteAlbumApi();
                break;
            case 'edit_album':
                $ctrl->editAlbumApi();
                break;
            case 'invitations':
                $ctrl->getInvitations();
                break;
            default:
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['error' => 'Route album inconnue']);
        }
        break;

    case 'auth':
        $auth = new AuthController();
        switch ($action) {
            case 'login':
                $auth->login();
                break;
            case 'register':
                $auth->register();
                break;
            default:
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['error' => 'Route auth inconnue']);
        }
        break;

    default:
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Route inconnue']);
}