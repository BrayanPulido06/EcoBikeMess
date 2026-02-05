<?php
session_start();
require_once '../models/paquetesAdminModels.php';

header('Content-Type: application/json');

// Verificar sesión (opcionalmente verificar rol de admin)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$model = new PaquetesAdminModel();
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_filters':
            $data = $model->getFilters();
            echo json_encode([
                'success' => true, 
                'clientes' => $data['clientes'], 
                'mensajeros' => $data['mensajeros']
            ]);
            break;

        case 'get_paquetes':
            $filters = [
                'search' => $_GET['search'] ?? '',
                'fechaDesde' => $_GET['fechaDesde'] ?? '',
                'fechaHasta' => $_GET['fechaHasta'] ?? '',
                'cliente_id' => $_GET['cliente_id'] ?? '',
                'estado' => $_GET['estado'] ?? '',
                'zona' => $_GET['zona'] ?? '',
                'mensajero_id' => $_GET['mensajero_id'] ?? '',
                'tipo' => $_GET['tipo'] ?? ''
            ];
            $paquetes = $model->getPaquetes($filters);
            echo json_encode(['success' => true, 'data' => $paquetes]);
            break;

        case 'get_paquete_details':
            $id = $_GET['id'] ?? 0;
            $historial = $model->getPaqueteDetails($id);
            echo json_encode(['success' => true, 'historial' => $historial]);
            break;

        case 'update_paquete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $id = $_POST['id'];
            // Limpiar datos básicos
            $data = [
                'destinatario' => trim($_POST['destinatario']),
                'telefono' => trim($_POST['telefono']),
                'direccion' => trim($_POST['direccion']),
                'zona' => trim($_POST['zona']),
                'tipo' => trim($_POST['tipo']),
                'valor' => str_replace(['$', '.', ','], '', $_POST['valor']), // Limpiar formato moneda
                'peso' => trim($_POST['peso']),
                'observaciones' => trim($_POST['observaciones'])
            ];
            
            if ($model->updatePaquete($id, $data)) {
                echo json_encode(['success' => true, 'message' => 'Paquete actualizado correctamente']);
            } else {
                throw new Exception('Error al actualizar el paquete en la base de datos');
            }
            break;

        case 'assign_mensajero':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $paqueteId = $_POST['paquete_id'];
            $mensajeroId = $_POST['mensajero_id'];
            
            if (empty($mensajeroId)) {
                throw new Exception('Debe seleccionar un mensajero');
            }
            
            if ($model->assignMensajero($paqueteId, $mensajeroId, $_SESSION['user_id'])) {
                echo json_encode(['success' => true, 'message' => 'Mensajero asignado correctamente']);
            } else {
                throw new Exception('Error al asignar el mensajero');
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
