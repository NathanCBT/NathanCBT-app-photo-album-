<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/src/models/Database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non autorisé."]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
if (mb_strlen($bio) > 160) {
    $bio = mb_substr($bio, 0, 160); // maximum 160 characters 
}

try {
    $db = (new Database())->getConnection();

    $avatarDir = __DIR__ . '/../frontend/uploads/avatars/';
    $bannerDir = __DIR__ . '/../frontend/uploads/banners/';
    if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);
    if (!is_dir($bannerDir)) mkdir($bannerDir, 0755, true);

    $avatarPath = $_SESSION['avatar'] ?? null;
    $bannerPath = $_SESSION['banner'] ?? null;

    // avatar file management
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarDir . $fileName)) {
            $avatarPath = 'frontend/uploads/avatars/' . $fileName;
            $_SESSION['avatar'] = $avatarPath;
        }
    }

    // banner file management
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        $fileName = 'banner_' . $userId . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['banner']['tmp_name'], $bannerDir . $fileName)) {
            $bannerPath = 'frontend/uploads/banners/' . $fileName;
            $_SESSION['banner'] = $bannerPath;
        }
    }

    $query = "UPDATE users SET bio = :bio, avatar_url = :avatar, banner_url = :banner WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':bio' => $bio,
        ':avatar' => $avatarPath,
        ':banner' => $bannerPath,
        ':id' => $userId
    ]);

    $_SESSION['bio'] = $bio;

    echo json_encode([
        "success" => true,
        "bio" => $bio,
        "avatar" => $avatarPath,
        "banner" => $bannerPath
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur : " . $e->getMessage()]);
}