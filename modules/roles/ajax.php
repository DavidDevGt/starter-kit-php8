<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\RoleController;

header('Content-Type: application/json');

$roleController = new RoleController();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Obtener todos los roles o un rol específico por id
            if (isset($_GET['id'])) {
                $role = $roleController->show($_GET['id']);
                echo json_encode(['success' => true, 'data' => $role]);
            } else {
                $roles = $roleController->index();
                echo json_encode(['success' => true, 'data' => $roles]);
            }
            break;

        case 'POST':
            // Crear un nuevo rol
            $data = json_decode(file_get_contents('php://input'), true);
            $roleController->store($data);
            echo json_encode(['success' => true, 'message' => 'Rol creado exitosamente.']);
            break;

        case 'PUT':
            // Actualizar un rol existente
            parse_str(file_get_contents('php://input'), $data);
            $id = isset($data['id']) ? $data['id'] : null;
            if ($id) {
                $roleController->update($id, $data);
                echo json_encode(['success' => true, 'message' => 'Rol actualizado exitosamente.']);
            } else {
                throw new Exception('ID del rol no especificado.');
            }
            break;

        case 'DELETE':
            // Eliminar un rol (soft delete)
            parse_str(file_get_contents('php://input'), $data);
            $id = isset($data['id']) ? $data['id'] : null;
            if ($id) {
                $roleController->delete($id);
                echo json_encode(['success' => true, 'message' => 'Rol eliminado exitosamente.']);
            } else {
                throw new Exception('ID del rol no especificado.');
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
