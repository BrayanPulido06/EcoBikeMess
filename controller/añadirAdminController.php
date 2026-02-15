<?php
session_start();
require_once '../models/añadirAdminModels.php';

// Verificar si es administrador (seguridad básica)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrador')) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

header('Content-Type: application/json');

$model = new AñadirAdminModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // --- LISTADOS ---
        case 'listar_admins':
            $data = $model->getAdministradores();
            // Formatear datos para el JS si es necesario
            $formatted = array_map(function($admin) {
                return [
                    'id' => $admin['admin_id'],
                    'nombre' => $admin['nombre'],
                    'email' => $admin['email'],
                    'telefono' => $admin['telefono'],
                    'rol' => $admin['rol'],
                    'estado' => $admin['estado'],
                    'foto' => $admin['foto'],
                    'fechaCreacion' => $admin['fecha_creacion'],
                    'ultimoAcceso' => $admin['ultimo_acceso'],
                    'permisos' => json_decode($admin['permisos_especiales'] ?? '[]')
                ];
            }, $data);
            echo json_encode(['success' => true, 'data' => $formatted]);
            break;

        case 'listar_mensajeros':
            $data = $model->getMensajeros();
            // Formatear ubicación para mostrar en tabla
            $formatted = array_map(function($m) {
                $m['ubicacionActual'] = ($m['ubicacion_actual_lat'] && $m['ubicacion_actual_lng']) 
                    ? $m['ubicacion_actual_lat'] . ', ' . $m['ubicacion_actual_lng'] 
                    : 'No disponible';
                return $m;
            }, $data);
            echo json_encode(['success' => true, 'data' => $formatted]);
            break;

        case 'listar_clientes':
            $data = $model->getClientes();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        // --- ACCIONES ADMINISTRADOR ---
        case 'guardar_admin':
            $id = $_POST['id'] ?? null;
            $datos = [
                'nombre' => $_POST['nombre'],
                'email' => $_POST['email'],
                'telefono' => $_POST['telefono'],
                'rol' => $_POST['rol'],
                'estado' => $_POST['estado'],
                'password' => $_POST['password'] ?? null,
                'permisos' => isset($_POST['permisos']) ? explode(',', $_POST['permisos']) : []
            ];

            if ($id) {
                $res = $model->actualizarAdministrador($id, $datos);
                $msg = 'Administrador actualizado correctamente';
            } else {
                if (empty($datos['password'])) throw new Exception("La contraseña es obligatoria para nuevos usuarios");
                $res = $model->crearAdministrador($datos);
                $msg = 'Administrador creado correctamente';
            }

            echo json_encode(['success' => $res, 'message' => $msg]);
            break;

        case 'eliminar_admin':
            $id = $_POST['id'];
            if ($id == $_SESSION['user_id']) { // Evitar auto-eliminación si el ID coincidiera (aunque aquí es ID de admin vs ID usuario, mejor validar en modelo)
                throw new Exception("No puedes eliminar tu propia cuenta desde aquí");
            }
            $res = $model->eliminarAdministrador($id);
            echo json_encode(['success' => $res, 'message' => 'Administrador eliminado']);
            break;

        case 'cambiar_estado_admin':
            $id = $_POST['id'];
            $estado = $_POST['estado']; // 'activo' o 'inactivo'
            $res = $model->cambiarEstadoUsuario($id, $estado);
            echo json_encode(['success' => $res, 'message' => 'Estado actualizado']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>