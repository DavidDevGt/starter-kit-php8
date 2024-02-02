<?php
require_once __DIR__ . '/../../lib/vendor/autoload.php';

use App\Config\Database;

// Funciones mejoradas para realizar consultas y obtener resultados
function dbQueryFetchAll($query)
{
    $database = new Database();
    $result = $database->dbQuery($query);
    return $database->dbFetchAll($result);
}

function dbQueryFetchAssoc($query)
{
    $database = new Database();
    $result = $database->dbQuery($query);
    return $database->dbFetchAssoc($result);
}

// Manejo de las peticiones HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $action = $_GET['action'] ?? ''; // Acción a realizar
        $id = $_GET['id'] ?? ''; // ID del registro a obtener
        $response = ''; // Respuesta a la petición

        switch ($action) {
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
                // Aquí puedes añadir casos para diferentes acciones POST

            default:
                echo json_encode(['success' => false, 'message' => 'Acción POST no reconocida.']);
                break;
        }
        break;

    case 'PUT':
        $action = $_GET['action'] ?? ''; // Acción a realizar
        $id = $_GET['id'] ?? ''; // ID del registro a actualizar
        $response = ''; // Respuesta a la petición

        switch ($action) {
                // Agrega más casos PUT según sea necesario
            default:
                $response = json_encode(['success' => false, 'message' => 'Acción PUT no reconocida.']);
                break;
        }
        break;

    case 'DELETE':
        $action = $_GET['action'] ?? ''; // Acción a realizar
        $id = $_GET['id'] ?? ''; // ID del registro a eliminar
        $response = ''; // Respuesta a la petición

        switch ($action) {
                // Agrega más casos DELETE según sea necesario
            default:
                $response = json_encode(['success' => false, 'message' => 'Acción DELETE no reconocida.']);
                break;
        }
        break;

    default:
        http_response_code(405); // Método no permitido
        echo json_encode(['success' => false, 'message' => 'Método HTTP no permitido.']);
        break;
}

exit;
