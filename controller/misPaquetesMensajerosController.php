<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/misPaquetesMensajerosModels.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

function guardarImagenBase64($base64, $subcarpeta = 'entregas')
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

    $nombre = $subcarpeta . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dirFisico = dirname(__DIR__) . '/uploads/' . $subcarpeta;
    if (!is_dir($dirFisico)) {
        mkdir($dirFisico, 0777, true);
    }

    $rutaFisica = $dirFisico . '/' . $nombre;
    if (file_put_contents($rutaFisica, $binario) === false) {
        return null;
    }

    return '/uploads/' . $subcarpeta . '/' . $nombre;
}

$model = new MisPaquetesMensajerosModels();
$mensajero = $model->obtenerMensajeroPorUsuario((int) $_SESSION['user_id']);
if (!$mensajero) {
    echo json_encode(['success' => false, 'message' => 'Mensajero no encontrado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'listar':
            $rows = $model->listarPaquetes((int) $mensajero['id']);
            echo json_encode([
                'success' => true,
                'mensajero' => [
                    'id' => (int) $mensajero['id'],
                    'nombre' => trim(($mensajero['nombres'] ?? '') . ' ' . ($mensajero['apellidos'] ?? ''))
                ],
                'data' => $rows
            ]);
            break;

        case 'entregar':
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            if (!is_array($input)) {
                throw new Exception('Payload inválido');
            }

            $fotos = $input['fotos'] ?? [];
            if (empty($fotos) || empty($fotos[0]['data'])) {
                throw new Exception('Debes adjuntar al menos una foto');
            }

            $rutaPrincipal = guardarImagenBase64($fotos[0]['data'], 'entregas');
            if (!$rutaPrincipal) {
                throw new Exception('No se pudo guardar la foto principal');
            }

            $rutaAdicional = null;
            if (!empty($fotos[1]['data'])) {
                $rutaAdicional = guardarImagenBase64($fotos[1]['data'], 'entregas');
            }

            $payload = [
                'paquete_id' => (int) ($input['paquete_id'] ?? 0),
                'numero_guia' => trim($input['numero_guia'] ?? ''),
                'nombre_receptor' => trim($input['nombreRecibe'] ?? ''),
                'parentesco_cargo' => trim($input['parentesco'] ?? ''),
                'documento_receptor' => trim($input['documento'] ?? ''),
                'recaudo_real' => (float) ($input['recaudo'] ?? 0),
                'observaciones' => trim($input['observaciones'] ?? ''),
                'lat' => isset($input['ubicacion']['lat']) ? (float) $input['ubicacion']['lat'] : null,
                'lng' => isset($input['ubicacion']['lng']) ? (float) $input['ubicacion']['lng'] : null,
                'foto_entrega' => $rutaPrincipal,
                'foto_adicional' => $rutaAdicional
            ];

            if (($payload['paquete_id'] <= 0 && $payload['numero_guia'] === '') || $payload['nombre_receptor'] === '') {
                throw new Exception('Datos obligatorios incompletos');
            }

            $model->registrarEntrega((int) $mensajero['id'], $payload);
            echo json_encode(['success' => true, 'message' => 'Entrega registrada']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
