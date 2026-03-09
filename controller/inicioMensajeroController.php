<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/inicioMensajeroModels.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';
$model = new InicioMensajeroModels();
$mensajero = $model->obtenerMensajeroPorUsuario((int) $_SESSION['user_id']);

if (!$mensajero) {
    echo json_encode(['success' => false, 'message' => 'Mensajero no encontrado']);
    exit;
}

try {
    switch ($action) {
        case 'dashboard':
            $stats = $model->obtenerEstadisticas((int) $mensajero['id']);
            $recolecciones = $model->obtenerRecoleccionesPendientes((int) $mensajero['id']);
            $entregas = $model->obtenerEntregasEnCurso((int) $mensajero['id']);

            echo json_encode([
                'success' => true,
                'mensajero' => [
                    'id' => (int) $mensajero['id'],
                    'nombre' => trim(($mensajero['nombres'] ?? '') . ' ' . ($mensajero['apellidos'] ?? ''))
                ],
                'stats' => [
                    'entregadas' => (int) ($stats['entregadas_hoy'] ?? 0),
                    'pendientes' => (int) ($stats['pendientes'] ?? 0),
                    'ganancias' => (float) ($stats['recaudo_hoy'] ?? 0),
                    'kilometros' => 0
                ],
                'recolecciones' => $recolecciones,
                'entregas' => $entregas
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

