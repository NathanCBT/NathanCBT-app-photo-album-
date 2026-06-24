<?php
require_once __DIR__ . '/src/controllers/DashboardController.php';

$controller = new DashboardController();
$controller->getDashboardData();