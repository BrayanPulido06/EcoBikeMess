<?php
session_start();
require_once '../models/inicioClienteModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'No autorizado']);
    exit;
}

$op = $_GET['op'] ?? '';
$model = new InicioClienteModel();
$cliente_id = $model->obtenerIdCliente($_SESSION['user_id'], $_SESSION['user_role'] ?? 'cliente');

if (!$cliente_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Cliente no encontrado']);
    exit;
}

switch ($op) {
    case 'grafica':
        $periodo = $_GET['periodo'] ?? 'year';
        $rawData = $model->obtenerDatosGrafica($cliente_id, $periodo);
        
        // Procesar datos para Chart.js (rellenar ceros si es necesario o devolver tal cual)
        // Para simplificar, devolvemos los arrays mapeados por mes (1-12)
        $dataTotal = array_fill(0, 12, 0);
        $dataEntregados = array_fill(0, 12, 0);

        foreach ($rawData as $row) {
            $mesIndex = intval($row['mes']) - 1;
            if ($mesIndex >= 0 && $mesIndex < 12) {
                $dataTotal[$mesIndex] = intval($row['total']);
                $dataEntregados[$mesIndex] = intval($row['entregados']);
            }
        }

        echo json_encode(['status' => 'success', 'total' => $dataTotal, 'entregados' => $dataEntregados]);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Operación inválida']);
}
?>