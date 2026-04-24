<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['administrador', 'admin'], 'No autorizado');
require_once '../models/asignarRecoleccionesModels.php';

header('Content-Type: application/json');

$model = new AsignarRecoleccionesModel();
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        // Obtener datos iniciales para llenar los selectores (Clientes y Mensajeros)
        case 'get_data_init':
            $clientes = $model->getClientes();
            $mensajeros = $model->getMensajeros();
            echo json_encode([
                'success' => true, 
                'clientes' => $clientes, 
                'mensajeros' => $mensajeros
            ]);
            break;

        // Listar recolecciones en la tabla
        case 'listar':
            $filtros = [
                'busqueda' => $_GET['busqueda'] ?? '',
                'estado' => $_GET['estado'] ?? '',
                'prioridad' => $_GET['prioridad'] ?? '',
                'fecha' => $_GET['fecha'] ?? ''
            ];
            
            $data = $model->listarRecolecciones($filtros);
            
            // Calcular estadísticas rápidas para los badges
            $total = count($data);
            $pendientes = 0;
            $completadas = 0;
            
            foreach ($data as $r) {
                if (in_array($r['estado'], ['pendiente', 'asignada', 'en_curso'])) {
                    $pendientes++;
                }
                if ($r['estado'] === 'completada') {
                    $completadas++;
                }
            }

            echo json_encode([
                'success' => true, 
                'data' => $data,
                'stats' => [
                    'total' => $total,
                    'pendientes' => $pendientes,
                    'completadas' => $completadas
                ]
            ]);
            break;

        // Nuevo caso: Asignar mensajero
        case 'asignar':
            $ids = $_POST['ids_paquetes'];
            $mensajeroId = $_POST['mensajero_id'];
            $creadoPor = $_SESSION['user_id']; // El ID del usuario que realiza la acción
            
            $model->asignarMensajeroPaquetes($ids, $mensajeroId, $creadoPor);
            echo json_encode(['success' => true, 'message' => 'Mensajero asignado correctamente']);
            break;

        // Nuevo caso: Obtener detalles para el modal
        case 'detalles':
            $ids = $_GET['ids'] ?? '';
            $detalles = $model->obtenerDetallesPaquetes($ids);
            echo json_encode(['success' => true, 'data' => $detalles]);
            break;

        // Nuevo caso: Obtener detalles de la recolección (para fotos, etc.)
        case 'detalles_recoleccion':
            if (isset($_GET['paquete_id'])) {
                $recoleccion = $model->getRecoleccionPorPaquete($_GET['paquete_id']);
                if ($recoleccion) {
                    echo json_encode(['success' => true, 'data' => $recoleccion]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se encontró recolección asociada.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de paquete no proporcionado.']);
            }
            break;

        // Nuevo caso: Cancelar recolección (Eliminar de la vista)
        case 'cancelar':
            $ids = $_POST['ids_paquetes'];
            if ($model->ocultarRecoleccionDeVista($ids)) {
                echo json_encode(['success' => true, 'message' => 'Recolección eliminada de la vista correctamente']);
            } else {
                throw new Exception('Error al eliminar la recolección');
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
