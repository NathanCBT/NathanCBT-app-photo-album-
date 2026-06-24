<?php
session_start();
require_once __DIR__ . '/src/Logger.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
session_unset();
session_destroy();

if ($userId !== null) {
    Logger::info($userId, 'user_logout', 'successful_logout=1');
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);