<?php

require_once __DIR__ . "/../lib/vendor/autoload.php";

use App\Controllers\SessionController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($username && $password) {
        $sessionController = new SessionController();
        $loginResult = $sessionController->login($username, $password);
    }
}