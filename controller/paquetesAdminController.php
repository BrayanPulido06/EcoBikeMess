<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['administrador', 'admin'], 'No autorizado');
require_once '../models/paquetesAdminModels.php';

// Configurar cabecera para devolver JSON
header('Content-Type: application/json');

$model = new PaquetesAdminModel();
$action = $_REQUEST['action'] ?? 'listar'; 

try {
    switch ($action) {
        case 'listar':
            // Recoger filtros enviados desde el JS
            $filters = [
                'search' => $_REQUEST['search'] ?? '',
                'fechaDesde' => $_REQUEST['fechaDesde'] ?? '',
                'fechaHasta' => $_REQUEST['fechaHasta'] ?? '',
                'cliente_id' => $_REQUEST['cliente'] ?? '',
                'estado' => $_REQUEST['estado'] ?? '',
                'zona' => $_REQUEST['zona'] ?? '',
                'mensajero_id' => $_REQUEST['mensajero'] ?? '',
                'tipo' => $_REQUEST['tipo'] ?? ''
            ];
            
            $data = $model->getPaquetes($filters);
            
            // Devolver en formato que DataTables o tu JS pueda leer
            echo json_encode(['data' => $data]);
            break;

        case 'filtros':
            // Devolver listas para los selects (clientes y mensajeros)
            $data = $model->getFilters();
            echo json_encode($data);
            break;

        case 'detalle':
            $id = $_REQUEST['id'] ?? 0;
            $data = $model->getPaqueteDetails($id);
            echo json_encode($data);
            break;
            
        case 'asignar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $paqueteId = $_POST['paquete_id'];
                $mensajeroId = $_POST['mensajero_id'];
                $userId = $_SESSION['user_id'] ?? 0;
                
                $res = $model->assignMensajero($paqueteId, $mensajeroId, $userId);
                echo json_encode(['success' => $res]);
            }
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
