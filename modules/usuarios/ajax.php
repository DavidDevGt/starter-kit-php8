<?php
require_once __DIR__ . '/../../lib/vendor/autoload.php';

use App\Controllers\UserController;

$controller = new UserController();

// Manejo de las peticiones HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $action = $_GET['action'] ?? ''; // Acción a realizar
        $id = $_GET['id'] ?? ''; // ID del usuario a obtener
        $response = ''; // Respuesta a la petición

        switch ($action) {
            case 'show':
                // Mostrar un usuario específico
                $response = $controller->show($id);
                break;
            case 'index':
                // Mostrar lista de usuarios
                $response = $controller->index();
                break;
            // Agrega más casos GET según sea necesario
            default:
                $response = json_encode(['success' => false, 'message' => 'Acción GET no reconocida.']);
                break;
        }
        break;

    case 'POST':
        $action = $_POST['action'] ?? ''; // Acción a realizar
        $data = json_decode(file_get_contents('php://input'), true); // Datos de la petición

        switch ($action) {
            case 'create':
                // Crear un nuevo usuario
                $response = $controller->create($data);
                break;
            // Agrega más casos POST según sea necesario
            default:
                $response = json_encode(['success' => false, 'message' => 'Acción POST no reconocida.']);
                break;
        }
        break;

    case 'PUT':
        $action = $_GET['action'] ?? ''; // Acción a realizar
        $data = json_decode(file_get_contents('php://input'), true); // Datos de la petición para actualizar

        switch ($action) {
            case 'update':
                // Actualizar un usuario
                $response = $controller->update($data['id'], $data);
                break;
            // Agrega más casos PUT según sea necesario
            default:
                $response = json_encode(['success' => false, 'message' => 'Acción PUT no reconocida.']);
                break;
        }
        break;

    case 'DELETE':
        $action = $_GET['action'] ?? ''; // Acción a realizar
        parse_str(file_get_contents('php://input'), $data); // Datos de la petición para eliminar

        switch ($action) {
            case 'delete':
                // Eliminar un usuario
                $response = $controller->delete($data['id']);
                break;
            // Agrega más casos DELETE según sea necesario
            default:
                $response = json_encode(['success' => false, 'message' => 'Acción DELETE no reconocida.']);
                break;
        }
        break;

    default:
        http_response_code(405); // Método no permitido
        $response = json_encode(['success' => false, 'message' => 'Método HTTP no permitido.']);
        break;
}

header('Content-Type: application/json');
echo $response;
