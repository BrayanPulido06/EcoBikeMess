<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['admin', 'administrador'], 'No autorizado');
require_once __DIR__ . '/../models/facturacionModels.php';

header('Content-Type: application/json; charset=utf-8');

$model = new FacturacionModels();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? ($_POST['action'] ?? '') : '');

function parseMoneyInput($value): float
{
    if (is_numeric($value)) {
        return (float) $value;
    }

    $normalized = preg_replace('/[^\d,.\-]/', '', (string) $value);
    if ($normalized === null || $normalized === '') {
        return 0.0;
    }

    $normalized = str_replace('.', '', $normalized);
    $normalized = str_replace(',', '.', $normalized);

    return is_numeric($normalized) ? (float) $normalized : 0.0;
}

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
            throw new InvalidArgumentException('Paquete invalido.');
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

    if ($method === 'POST' && $action === 'registrar_abono_cliente') {
        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $fechaGrupo = trim((string) ($_POST['fecha_grupo'] ?? ''));
        $monto = parseMoneyInput($_POST['monto'] ?? 0);
        $metodoPago = trim((string) ($_POST['metodo_pago'] ?? ''));
        $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
        $registradoPor = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        if ($clienteId <= 0) {
            throw new InvalidArgumentException('Cliente invalido.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaGrupo)) {
            throw new InvalidArgumentException('La fecha del abono no es valida.');
        }

        if ($monto <= 0) {
            throw new InvalidArgumentException('El abono debe ser mayor a cero.');
        }

        if (!in_array($metodoPago, ['efectivo', 'transferencia'], true)) {
            throw new InvalidArgumentException('Metodo de pago invalido.');
        }

        $model->registrarAbonoCliente(
            $clienteId,
            $fechaGrupo,
            $monto,
            $metodoPago,
            $observaciones !== '' ? $observaciones : null,
            $registradoPor
        );

        echo json_encode([
            'success' => true,
            'message' => 'Abono registrado correctamente.',
            'data' => $model->obtenerVistaAdmin(),
        ]);
        exit;
    }

    throw new InvalidArgumentException('Accion no valida.');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
