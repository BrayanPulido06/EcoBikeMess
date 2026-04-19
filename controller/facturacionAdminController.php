<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['admin', 'administrador'], 'No autorizado');
require_once __DIR__ . '/../models/facturacionModels.php';

header('Content-Type: application/json; charset=utf-8');

$model = new FacturacionModels();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? ($_POST['action'] ?? '') : '');

try {
    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'data' => $model->obtenerVistaAdmin(),
        ]);
        exit;
    }

    if ($method === 'POST' && $action === 'actualizar_pago_mensajero') {
        $paqueteId = (int) ($_POST['paquete_id'] ?? 0);
        $valorPago = (float) ($_POST['valor_pago_mensajero'] ?? 7000);
        $mostrarAlMensajero = (int) ($_POST['mostrar_al_mensajero'] ?? 0) === 1;

        if ($paqueteId <= 0) {
            throw new InvalidArgumentException('Paquete inválido.');
        }

        if ($valorPago < 0) {
            throw new InvalidArgumentException('El valor a pagar no puede ser negativo.');
        }

        $model->actualizarPagoMensajero($paqueteId, $valorPago, $mostrarAlMensajero);

        echo json_encode([
            'success' => true,
            'message' => 'Pago del mensajero actualizado correctamente.',
            'data' => $model->obtenerVistaAdmin(),
        ]);
        exit;
    }

    throw new InvalidArgumentException('Acción no válida.');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
