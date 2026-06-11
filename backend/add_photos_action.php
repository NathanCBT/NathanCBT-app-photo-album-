<?php
session_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/src/repositories/AlbumRepository.php';
    $albumRepository = new AlbumRepository();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion base de données : ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session expirée ou utilisateur non connecté.']);
    exit;
}
$userId = (int)$_SESSION['user_id'];

$album_id = isset($_POST['album_id']) ? (int)$_POST['album_id'] : 0;
if (!$album_id) {
    echo json_encode(['success' => false, 'error' => 'ID de l\'album manquant']);
    exit;
}

// access rights
$album = $albumRepository->getAlbumById($album_id);
if (!$album) {
    echo json_encode(['success' => false, 'error' => 'Album introuvable.']);
    exit;
}

$isOwner = ((int)$album['user_id'] === $userId);
$rights = $albumRepository->getContributorRights($album_id, $userId);

if (!$isOwner && $rights !== 'Peut modifier') {
    echo json_encode(['success' => false, 'error' => 'Vous n\'avez pas les droits nécessaires pour ajouter des photos.']);
    exit;
}

if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['name'])) {
    echo json_encode(['success' => false, 'error' => 'Aucune image reçue']);
    exit;
}

$photoDescriptions = $_POST['descriptions'] ?? [];
$photoDates = $_POST['dates'] ?? [];
$photoTags = $_POST['tags'] ?? [];

$uploadPhotoDir = __DIR__ . '/../frontend/uploads/albums/';
if (!is_dir($uploadPhotoDir)) {
    mkdir($uploadPhotoDir, 0755, true);
}

$successCount = 0;

try {
    $albumRepository->beginTransaction();

    foreach ($_FILES['photos']['name'] as $index => $originalName) {
        if ($_FILES['photos']['error'][$index] === UPLOAD_ERR_OK) {
            
            $fileTmpPath = $_FILES['photos']['tmp_name'][$index];
            $fileSize = $_FILES['photos']['size'][$index];
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newPhotoName = "photo_" . uniqid() . "_" . $index . "." . $fileExtension;
                $fullTargetPath = $uploadPhotoDir . $newPhotoName;

                if (move_uploaded_file($fileTmpPath, $fullTargetPath)) {
                    $relativePhotoUrl = "frontend/uploads/albums/" . $newPhotoName;

                    $pDesc = !empty($photoDescriptions[$index]) ? trim($photoDescriptions[$index]) : null;
                    $pDate = !empty($photoDates[$index]) ? $photoDates[$index] : null;

                    $photoId = $albumRepository->createPhoto($album_id, $userId, $relativePhotoUrl, $fileSize, $pDesc, $pDate);

                    if (isset($photoTags[$index])) {
                        $checkedTags = json_decode($photoTags[$index], true);
                        if (is_array($checkedTags) && !empty($checkedTags)) {
                            foreach ($checkedTags as $tagId) {
                                $albumRepository->addTagToPhoto($photoId, (int)$tagId);
                            }
                        }
                    }
                    $successCount++;
                }
            }
        }
    }

    $albumRepository->commit();

    echo json_encode([
        'success' => $successCount > 0,
        'message' => "$successCount photo(s) importée(s) avec succès."
    ]);
    exit;

} catch (Exception $e) {
    $albumRepository->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()
    ]);
    exit;
}