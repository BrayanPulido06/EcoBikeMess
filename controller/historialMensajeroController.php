<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/historialMensajeroModels.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';
$model = new HistorialMensajeroModels();
$mensajero = $model->obtenerMensajeroPorUsuario((int) $_SESSION['user_id']);

if (!$mensajero) {
    echo json_encode(['success' => false, 'message' => 'Mensajero no encontrado']);
    exit;
}

try {
    switch ($action) {
        case 'listar':
            $rows = $model->listarHistorial((int) $mensajero['id']);
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

