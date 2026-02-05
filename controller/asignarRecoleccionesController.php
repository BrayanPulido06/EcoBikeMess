<?php
session_start();
require_once '../models/asignarRecoleccionesModels.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

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

        // Guardar nueva recolección
        case 'crear':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            // Recoger datos del formulario
            $datos = [
                'cliente_id' => $_POST['cliente'],
                'contacto' => $_POST['contacto'],
                'telefono' => $_POST['telefono'],
                'direccion' => $_POST['direccion'],
                'latitud' => $_POST['latitud'],
                'longitud' => $_POST['longitud'],
                'descripcion' => $_POST['descripcion'],
                'cantidad' => $_POST['cantidad'],
                'fechaRecoleccion' => $_POST['fechaRecoleccion'],
                'horario' => $_POST['horario'],
                'prioridad' => $_POST['prioridad'],
                'observaciones' => $_POST['observaciones'],
                'mensajero_id' => $_POST['mensajero_id'] ?? null,
                'creado_por' => $_SESSION['user_id']
            ];

            if ($model->crearRecoleccion($datos)) {
                echo json_encode(['success' => true, 'message' => 'Recolección creada exitosamente']);
            } else {
                throw new Exception('Error al guardar la recolección en la base de datos');
            }
            break;

        // Cancelar recolección
        case 'cancelar':
            $id = $_POST['id'];
            $motivo = $_POST['motivo'];
            
            if ($model->cancelarRecoleccion($id, $motivo)) {
                echo json_encode(['success' => true, 'message' => 'Recolección cancelada correctamente']);
            } else {
                throw new Exception('Error al cancelar la recolección');
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
