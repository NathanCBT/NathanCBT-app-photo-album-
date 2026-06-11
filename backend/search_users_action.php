<?php
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/src/models/Database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');

// if the search is empty an empty array is returned
if (empty($query)) {
    echo json_encode([]);
    exit;
}

try {
    $db = Database::getConnection();
    
    // recherche par pseudonyme
    $stmt = $db->prepare("
        SELECT id, username, avatar_url 
        FROM users 
        WHERE username LIKE ? AND id != ? 
        LIMIT 10
    ");
    
    $stmt->execute(["%" . $query . "%", $currentUserId]);
    $users = $stmt->fetchAll();

    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode(["error" => "Erreur lors de la recherche"]);
}