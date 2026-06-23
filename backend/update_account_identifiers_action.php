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

// we only retrieve the key if it exists in the received json
$username = isset($data['username']) ? trim($data['username']) : null;
$email = isset($data['email']) ? trim($data['email']) : null;

// if nothing has been provided at all
if ($username === null && $email === null) {
    echo json_encode(['error' => 'Aucune donnée à modifier n\'a été transmise.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    
    $fieldsToUpdate = [];
    $sqlParams = [];

    // if provided then verification and validation of the username
    if ($username !== null) {
        if (strlen($username) < 3) {
            echo json_encode(['error' => 'Le pseudonyme doit contenir au moins 3 caractères.']);
            exit;
        }

        // check for duplicates on other users
        $stmt = $pdo->prepare("
        SELECT id 
        FROM users 
        WHERE username = ? 
        AND id != ?
        ");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'Ce pseudonyme est déjà utilisé par un autre compte.']);
            exit;
        }

        $fieldsToUpdate[] = "username = ?";
        $sqlParams[] = $username;
    }

    // if provided verification and validation of the email 
    if ($email !== null) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Le format de l\'adresse e-mail est invalide.']);
            exit;
        }

        $stmt = $pdo->prepare("
        SELECT id 
        FROM users 
        WHERE email = ? 
        AND id != ?
        ");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'Cette adresse e-mail est déjà associée à un autre compte.']);
            exit;
        }

        $fieldsToUpdate[] = "email = ?";
        $sqlParams[] = $email;
    }

    if (!empty($fieldsToUpdate)) {
        $sql = "UPDATE users SET " . implode(", ", $fieldsToUpdate) . " WHERE id = ?";
        $sqlParams[] = $userId;

        $update = $pdo->prepare($sql);
        $update->execute($sqlParams);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}