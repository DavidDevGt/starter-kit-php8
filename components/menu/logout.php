<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\SessionController;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$sessionController = new SessionController();
$logoutResult = $sessionController->logout();

$location = $_SERVER['HTTP_REFERER'];

$deleteAfter = 'modules/';

$changeModulesToPublic = 'public/';

$location = substr($location, 0, strpos($location, $deleteAfter) + strlen($deleteAfter));

$location = substr_replace($location, $changeModulesToPublic, strpos($location, $deleteAfter), strlen($deleteAfter));

if ($logoutResult) {
    header("Location: $location");
    exit;
} else {
    die('Error al cerrar sesión');
}