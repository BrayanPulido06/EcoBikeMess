<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/recoleccionesMensajeroModels.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

function guardarFotoRecoleccion($base64)
{
    if (!$base64 || strpos($base64, 'base64,') === false) {
        return null;
    }
    [$meta, $contenido] = explode('base64,', $base64, 2);
    $binario = base64_decode($contenido);
    if ($binario === false) {
        return null;
    }

    $ext = 'jpg';
    if (strpos($meta, 'image/png') !== false) {
        $ext = 'png';
    } elseif (strpos($meta, 'image/webp') !== false) {
        $ext = 'webp';
    }

    $dirFisico = dirname(__DIR__) . '/uploads/recolecciones';
    if (!is_dir($dirFisico)) {
        mkdir($dirFisico, 0777, true);
    }

    $nombre = 'recoleccion_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $rutaFisica = $dirFisico . '/' . $nombre;
    if (file_put_contents($rutaFisica, $binario) === false) {
        return null;
    }

    return '/uploads/recolecciones/' . $nombre;
}

$model = new RecoleccionesMensajeroModels();
$mensajero = $model->obtenerMensajeroPorUsuario((int) $_SESSION['user_id']);
if (!$mensajero) {
    echo json_encode(['success' => false, 'message' => 'Mensajero no encontrado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'listar':
            $rows = $model->listarRecolecciones((int) $mensajero['id']);
            echo json_encode([
                'success' => true,
                'mensajero' => [
                    'id' => (int) $mensajero['id'],
                    'nombre' => trim(($mensajero['nombres'] ?? '') . ' ' . ($mensajero['apellidos'] ?? ''))
                ],
                'data' => $rows
            ]);
            break;

        case 'iniciar':
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            $recoleccionId = (int) ($input['recoleccion_id'] ?? 0);
            if ($recoleccionId <= 0) {
                throw new Exception('ID de recolección inválido');
            }

            $ok = $model->iniciarRecoleccion($recoleccionId, (int) $mensajero['id']);
            if (!$ok) {
                throw new Exception('No se pudo iniciar la recolección');
            }
            echo json_encode(['success' => true, 'message' => 'Recolección iniciada']);
            break;

        case 'completar':
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            if (!is_array($input)) {
                throw new Exception('Payload inválido');
            }

            $recoleccionId = (int) ($input['recoleccion_id'] ?? 0);
            if ($recoleccionId <= 0) {
                throw new Exception('ID de recolección inválido');
            }

            $fotos = $input['fotos'] ?? [];
            if (empty($fotos) || empty($fotos[0]['data'])) {
                throw new Exception('Debes adjuntar al menos una foto');
            }
            $rutaFoto = guardarFotoRecoleccion($fotos[0]['data']);
            if (!$rutaFoto) {
                throw new Exception('No se pudo guardar la foto de recolección');
            }

            $payload = [
                'cantidad_real' => (int) ($input['cantidad_real'] ?? 0),
                'observaciones' => trim($input['observaciones'] ?? ''),
                'conformidad' => (($input['conformidad'] ?? 'no') === 'si') ? 1 : 0,
                'foto_recoleccion' => $rutaFoto
            ];

            if ($payload['cantidad_real'] <= 0) {
                throw new Exception('La cantidad real debe ser mayor a 0');
            }

            $ok = $model->completarRecoleccion($recoleccionId, (int) $mensajero['id'], $payload);
            if (!$ok) {
                throw new Exception('No se pudo completar la recolección');
            }

            echo json_encode(['success' => true, 'message' => 'Recolección completada']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

