<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\SessionController;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$sessionController = new SessionController();
$logoutResult = $sessionController->logout();

// Redirect to the login page — never use HTTP_REFERER for redirects (open redirect risk)
$location = '/public/';

if ($logoutResult) {
    header('Location: ' . $location);
    exit;
} else {
    die('Error al cerrar sesión');
}