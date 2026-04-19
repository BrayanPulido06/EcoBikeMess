<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['mensajero'], 'No autorizado');
require_once __DIR__ . '/../models/facturacionModels.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $model = new FacturacionModels();
    $mensajeroId = $model->obtenerMensajeroIdPorUsuario((int) $_SESSION['user_id']);

    if ($mensajeroId === null) {
        throw new RuntimeException('No se encontró el mensajero relacionado con la sesión.');
    }

    echo json_encode([
        'success' => true,
        'data' => $model->obtenerVistaMensajero($mensajeroId),
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
