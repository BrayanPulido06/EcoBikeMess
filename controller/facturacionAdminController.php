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
        $panel = isset($_GET['panel']) ? trim((string) $_GET['panel']) : null;
        if ($panel !== null && !in_array($panel, ['cliente', 'mensajero'], true)) {
            $panel = null;
        }

        echo json_encode([
            'success' => true,
            'data' => $model->obtenerVistaAdmin($panel),
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

    if ($method === 'POST' && $action === 'registrar_abono_mensajero') {
        $mensajeroId = (int) ($_POST['mensajero_id'] ?? 0);
        $fechaGrupo = trim((string) ($_POST['fecha_grupo'] ?? ''));
        $monto = parseMoneyInput($_POST['monto'] ?? 0);
        $metodoPago = trim((string) ($_POST['metodo_pago'] ?? ''));
        $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
        $registradoPor = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        if ($mensajeroId <= 0) {
            throw new InvalidArgumentException('Mensajero invalido.');
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

        $model->registrarAbonoMensajero(
            $mensajeroId,
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

    if ($method === 'POST' && $action === 'actualizar_costo_adicional_paquete') {
        $paqueteId = (int) ($_POST['paquete_id'] ?? 0);
        $monto = parseMoneyInput($_POST['monto'] ?? 0);
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

        if ($paqueteId <= 0) {
            throw new InvalidArgumentException('Paquete invalido.');
        }

        if ($monto < 0) {
            throw new InvalidArgumentException('El costo adicional no puede ser negativo.');
        }

        if ($monto > 0 && $descripcion === '') {
            throw new InvalidArgumentException('Debes ingresar la descripcion del costo adicional.');
        }

        $model->actualizarCostoAdicionalPaquete(
            $paqueteId,
            $monto,
            $monto > 0 ? $descripcion : null
        );

        echo json_encode([
            'success' => true,
            'message' => 'Costo adicional actualizado correctamente.',
            'data' => $model->obtenerVistaAdmin(),
        ]);
        exit;
    }

    if ($method === 'POST' && $action === 'actualizar_adicional_mensajero_paquete') {
        $paqueteId = (int) ($_POST['paquete_id'] ?? 0);
        $monto = parseMoneyInput($_POST['monto'] ?? 0);
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

        if ($paqueteId <= 0) {
            throw new InvalidArgumentException('Paquete invalido.');
        }

        if ($monto < 0) {
            throw new InvalidArgumentException('El adicional del mensajero no puede ser negativo.');
        }

        if ($monto > 0 && $descripcion === '') {
            throw new InvalidArgumentException('Debes ingresar la descripcion del adicional.');
        }

        $model->actualizarAdicionalMensajeroPaquete(
            $paqueteId,
            $monto,
            $monto > 0 ? $descripcion : null
        );

        echo json_encode([
            'success' => true,
            'message' => 'Adicional del mensajero actualizado correctamente.',
            'data' => $model->obtenerVistaAdmin(),
        ]);
        exit;
    }

    if ($method === 'POST' && $action === 'ocultar_grupo_cliente') {
        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $fechaGrupo = trim((string) ($_POST['fecha_grupo'] ?? ''));
        $ocultadoPor = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        if ($clienteId <= 0) {
            throw new InvalidArgumentException('Cliente invalido.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaGrupo)) {
            throw new InvalidArgumentException('La fecha del grupo no es valida.');
        }

        $model->ocultarGrupoCliente($clienteId, $fechaGrupo, $ocultadoPor);

        echo json_encode([
            'success' => true,
            'message' => 'La cuenta del dia fue ocultada correctamente.',
            'data' => $model->obtenerVistaAdmin(),
        ]);
        exit;
    }

    if ($method === 'POST' && $action === 'ocultar_grupo_mensajero') {
        $mensajeroId = (int) ($_POST['mensajero_id'] ?? 0);
        $fechaGrupo = trim((string) ($_POST['fecha_grupo'] ?? ''));
        $ocultadoPor = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        if ($mensajeroId <= 0) {
            throw new InvalidArgumentException('Mensajero invalido.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaGrupo)) {
            throw new InvalidArgumentException('La fecha del grupo no es valida.');
        }

        $model->ocultarGrupoMensajero($mensajeroId, $fechaGrupo, $ocultadoPor);

        echo json_encode([
            'success' => true,
            'message' => 'La cuenta del dia fue ocultada correctamente.',
            'data' => $model->obtenerVistaAdmin(),
        ]);
        exit;
    }

    if ($method === 'POST' && $action === 'actualizar_estado_grupo_cliente') {
        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $fechaGrupo = trim((string) ($_POST['fecha_grupo'] ?? ''));
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $actualizadoPor = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        if ($clienteId <= 0) {
            throw new InvalidArgumentException('Cliente invalido.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaGrupo)) {
            throw new InvalidArgumentException('La fecha del grupo no es valida.');
        }

        if (!in_array($estado, ['pendiente', 'pagado'], true)) {
            throw new InvalidArgumentException('Estado invalido.');
        }

        $model->actualizarEstadoGrupoCliente($clienteId, $fechaGrupo, $estado, $actualizadoPor);

        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'data' => $model->obtenerVistaAdmin(),
        ]);
        exit;
    }

    if ($method === 'POST' && $action === 'actualizar_estado_grupo_mensajero') {
        $mensajeroId = (int) ($_POST['mensajero_id'] ?? 0);
        $fechaGrupo = trim((string) ($_POST['fecha_grupo'] ?? ''));
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $actualizadoPor = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

        if ($mensajeroId <= 0) {
            throw new InvalidArgumentException('Mensajero invalido.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaGrupo)) {
            throw new InvalidArgumentException('La fecha del grupo no es valida.');
        }

        if (!in_array($estado, ['pendiente', 'pagado'], true)) {
            throw new InvalidArgumentException('Estado invalido.');
        }

        $model->actualizarEstadoGrupoMensajero($mensajeroId, $fechaGrupo, $estado, $actualizadoPor);

        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
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
