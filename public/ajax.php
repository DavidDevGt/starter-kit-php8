<?php
ini_set('log_errors', 1);
ini_set('error_log', './errors.log');
error_reporting(E_ALL);

require_once __DIR__ . "/../vendor/autoload.php";

use App\Controllers\SessionController;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;

    error_log("username: $username, password: $password");

    if (!$username || !$password) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Usuario y contraseña requeridos.']);
        exit;
    }

    $sessionController = new SessionController();

    error_log("sessionController: " . json_encode($sessionController->login($username, $password)));

    $loginResult = $sessionController->login($username, $password);

    error_log("loginResult: " . json_encode($loginResult));

    if ($loginResult) {
        echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso.']);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}

exit;
