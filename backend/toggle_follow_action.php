<?php
require_once __DIR__ . '/src/controllers/FollowController.php';

$controller = new FollowController();
$controller->toggleFollow();