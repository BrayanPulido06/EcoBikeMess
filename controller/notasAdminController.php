<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['admin', 'administrador'], 'Acceso denegado.');
requireAdminPermission('admin_notes', 'No tienes permiso para notas');
require_once __DIR__ . '/../models/notasAdminModels.php';

header('Content-Type: application/json; charset=utf-8');

$model = new NotasAdminModels();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? ($_POST['action'] ?? '') : '');

function notasAdminCleanText($value, int $maxLength): string
{
    $text = trim((string) $value);
    $text = preg_replace('/\s+/', ' ', $text);
    if (function_exists('mb_substr')) {
        return mb_substr((string) $text, 0, $maxLength, 'UTF-8');
    }
    return substr((string) $text, 0, $maxLength);
}

try {
    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'data' => $model->obtenerTablero(),
        ]);
        exit;
    }

    if ($method !== 'POST') {
        throw new InvalidArgumentException('Metodo no permitido.');
    }

    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    if ($action === 'crear_lista') {
        $titulo = notasAdminCleanText($_POST['titulo'] ?? '', 160);
        if ($titulo === '') {
            throw new InvalidArgumentException('Escribe el titulo de la lista.');
        }
        $model->crearLista($titulo, $userId);
    } elseif ($action === 'actualizar_lista') {
        $listaId = (int) ($_POST['lista_id'] ?? 0);
        $titulo = notasAdminCleanText($_POST['titulo'] ?? '', 160);
        if ($listaId <= 0 || $titulo === '') {
            throw new InvalidArgumentException('Lista o titulo invalido.');
        }
        $model->actualizarLista($listaId, $titulo);
    } elseif ($action === 'eliminar_lista') {
        $listaId = (int) ($_POST['lista_id'] ?? 0);
        if ($listaId <= 0) {
            throw new InvalidArgumentException('Lista invalida.');
        }
        $model->eliminarLista($listaId);
    } elseif ($action === 'reordenar_listas') {
        $ordenRaw = (string) ($_POST['orden'] ?? '');
        $orden = json_decode($ordenRaw, true);
        if (!is_array($orden)) {
            throw new InvalidArgumentException('Orden de listas invalido.');
        }
        $model->reordenarListas($orden);
    } elseif ($action === 'crear_tarjeta') {
        $listaId = (int) ($_POST['lista_id'] ?? 0);
        $titulo = notasAdminCleanText($_POST['titulo'] ?? '', 180);
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        if ($listaId <= 0 || $titulo === '') {
            throw new InvalidArgumentException('Lista o titulo invalido.');
        }
        $model->crearTarjeta($listaId, $titulo, $descripcion, $userId);
    } elseif ($action === 'actualizar_tarjeta') {
        $tarjetaId = (int) ($_POST['tarjeta_id'] ?? 0);
        $titulo = notasAdminCleanText($_POST['titulo'] ?? '', 180);
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        if ($tarjetaId <= 0 || $titulo === '') {
            throw new InvalidArgumentException('Tarjeta o titulo invalido.');
        }
        $model->actualizarTarjeta($tarjetaId, $titulo, $descripcion);
    } elseif ($action === 'cambiar_estado_tarjeta') {
        $tarjetaId = (int) ($_POST['tarjeta_id'] ?? 0);
        if ($tarjetaId <= 0) {
            throw new InvalidArgumentException('Tarjeta invalida.');
        }
        $model->cambiarEstadoTarjeta($tarjetaId, (string) ($_POST['completada'] ?? '0') === '1');
    } elseif ($action === 'eliminar_tarjeta') {
        $tarjetaId = (int) ($_POST['tarjeta_id'] ?? 0);
        if ($tarjetaId <= 0) {
            throw new InvalidArgumentException('Tarjeta invalida.');
        }
        $model->eliminarTarjeta($tarjetaId);
    } else {
        throw new InvalidArgumentException('Accion no valida.');
    }

    echo json_encode([
        'success' => true,
        'data' => $model->obtenerTablero(),
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
