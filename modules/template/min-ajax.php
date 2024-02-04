<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// use App\Controllers\Controller;

header('Content-Type: application/json');

//$modelController = new Controller();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            
            break;

        case 'POST':
           
            break;

        case 'PUT':
            
            break;

        case 'DELETE':
            
            break;

        default:
            http_response_code(405); // Método no permitido
            echo json_encode(['success' => false, 'message' => 'Método HTTP no permitido.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
