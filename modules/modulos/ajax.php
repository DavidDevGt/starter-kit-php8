<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\ModuleController;

header('Content-Type: application/json');

$moduleController = new ModuleController();

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'PUT' || $method == 'DELETE') {
    parse_str(file_get_contents('php://input'), $_REQUEST);
}

try {
    $action = $_REQUEST['action'] ?? null;
    $id = $_REQUEST['id'] ?? null;

    switch ($method) {
        case 'GET':
            // Ver todos los módulos o un módulo específico por id
            if (isset($_GET['id'])) {
                $module = $moduleController->show($_GET['id']);
                echo json_encode(['success' => true, 'data' => $module]);
            } else {
                $modules = $moduleController->index();
                echo json_encode(['success' => true, 'data' => $modules]);
            }
            break;

        case 'POST':
            // Crear un nuevo módulo
            $data = json_decode(file_get_contents('php://input'), true);
            $moduleController->store($data);
            echo json_encode(['success' => true, 'message' => 'Módulo creado exitosamente.']);
            break;

        case 'PUT':
            // Actualizar un módulo existente
            parse_str(file_get_contents('php://input'), $data);
            $id = isset($data['id']) ? $data['id'] : null;
            if ($id) {
                $moduleController->update($id, $data);
                echo json_encode(['success' => true, 'message' => 'Módulo actualizado exitosamente.']);
            } else {
                throw new Exception('ID del módulo no especificado.');
            }
            break;

        case 'DELETE':
            // Eliminar un módulo (soft delete)
            $id = $_GET['id'] ?? null;  // Lo mande por GET pero el nombre del case es DELETE por fines practicos

            if ($id) {
                $moduleController->delete($id);
                echo json_encode(['success' => true, 'message' => 'Módulo eliminado exitosamente.']);
            } else {
                throw new Exception('ID del módulo no especificado.');
            }
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
