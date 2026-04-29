<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['administrador', 'admin'], 'Acceso denegado');
require_once '../models/añadirAdminModels.php';

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
                // Aseguramos que el objeto tenga las propiedades que el JS espera
                $m['id'] = $m['id']; // ID de la tabla mensajeros
                $m['usuario_id'] = $m['usuario_id'] ?? null; // ID de la tabla usuarios (vital para el estado)
                $m['nombre'] = $m['nombre'] ?? ($m['nombres'] . ' ' . $m['apellidos']);
                $m['paquetesAsignados'] = $m['paquetesAsignados'] ?? 0;
                $m['entregasHoy'] = $m['entregasHoy'] ?? 0;
                $m['rendimiento'] = $m['rendimiento'] ?? 0;

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

        case 'detalle_cliente':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                throw new Exception("ID de cliente invalido");
            }

            $cliente = $model->getClienteById($id);
            if (!$cliente) {
                throw new Exception("Cliente no encontrado");
            }

            echo json_encode(['success' => true, 'data' => $cliente]);
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
