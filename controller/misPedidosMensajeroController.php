<?php
require_once __DIR__ . '/../includes/auth.php';
ensureSessionStarted();
header('Content-Type: application/json; charset=utf-8');
requireApiAuth(['mensajero'], 'No autorizado');

require_once __DIR__ . '/../models/misPedidosMensajeroModels.php';

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$model = new MisPedidosMensajeroModel();
$action = $_GET['action'] ?? 'listar';

$filtros = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'fechaDesde' => trim((string) ($_GET['fechaDesde'] ?? '')),
    'fechaHasta' => trim((string) ($_GET['fechaHasta'] ?? '')),
    'estado' => trim((string) ($_GET['estado'] ?? ''))
];

try {
    switch ($action) {
        case 'listar':
            echo json_encode(['success' => true, 'data' => $model->listarPedidos($usuarioId, $filtros)]);
            break;

        case 'estadisticas':
            echo json_encode(['success' => true, 'data' => $model->obtenerEstadisticas($usuarioId, $filtros)]);
            break;

        case 'detalle':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID de pedido invalido.');
            }
            $detalle = $model->obtenerDetalle($id, $usuarioId);
            if (!$detalle) {
                throw new Exception('Pedido no encontrado.');
            }
            echo json_encode(['success' => true, 'data' => $detalle]);
            break;

        default:
            throw new Exception('Accion no valida.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
