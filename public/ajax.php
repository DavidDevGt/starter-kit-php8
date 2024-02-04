<?php

require_once __DIR__ . "/../vendor/autoload.php";

use App\Controllers\SessionController;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$username || !$password) {
        http_response_code(400); // Bad Request
        error_log("Faltan datos para el inicio de sesión: Usuario o contraseña no proporcionados.");
        echo json_encode(['success' => false, 'message' => 'Usuario y contraseña requeridos.']);
        exit;
    }

    $sessionController = new SessionController();
    $loginResult = $sessionController->login($username, $password);

    if ($loginResult) {
        echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso.']);
    } else {
        http_response_code(401); // Unauthorized
        error_log("Inicio de sesión fallido para usuario: $username");
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}

exit;
