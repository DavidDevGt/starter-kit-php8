<?php

require_once __DIR__ . "/../lib/vendor/autoload.php";

use App\Controllers\SessionController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($username && $password) {
        $sessionController = new SessionController();
        $loginResult = $sessionController->login($username, $password);

        if ($loginResult) {
            echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso.']);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Usuario y contraseña requeridos.']);
    }
    exit;
}

// Security: Redirect to index.php if the request method is not POST
header('Location: index.php');
exit;