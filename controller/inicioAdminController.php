<?php
session_start();
header('Content-Type: application/json');

// Incluir el modelo
require_once '../models/inicioAdminModels.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || 
    (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'administrador' && $_SESSION['user_role'] !== 'admin'))) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Permisos insuficientes.']);
    exit;
}

$model = new InicioAdminModel();

// Manejar peticiones GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Acción por defecto: obtener datos del dashboard
        $action = $_GET['action'] ?? 'get_dashboard_data';

        if ($action === 'get_dashboard_data') {
            // Obtener estadísticas del día
            $stats = $model->obtenerEstadisticasDia();
            
            echo json_encode([
                'success' => true,
                'user_name' => $_SESSION['user_name'] ?? 'Administrador',
                'stats' => $stats,
                'last_update' => date('d/m/Y H:i:s')
            ]);
        }
        elseif ($action === 'get_chart_data') {
            $period = $_GET['period'] ?? 'dia';
            $data = $model->obtenerMovimientos($period);

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
