<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié.']);
    exit;
}

require_once __DIR__ . '/src/models/Database.php';
$userId = (int)$_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$currentPassword = $data['currentPassword'] ?? '';
$newPassword = $data['newPassword'] ?? '';

if (strlen($newPassword) < 14) {
    echo json_encode(['error' => 'Le nouveau mot de passe doit faire au moins 14 caractères.']);
    exit;
}

try {
    $pdo = Database::getConnection();

    // retrieve the current hashed password
    $stmt = $pdo->prepare("
    SELECT password 
    FROM users 
    WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        echo json_encode(['error' => 'Le mot de passe actuel est incorrect.']);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

    $update = $pdo->prepare("
    UPDATE users 
    SET password = ? 
    WHERE id = ?
    ");
    $update->execute([$newHash, $userId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur de mise à jour du mot de passe.']);
}