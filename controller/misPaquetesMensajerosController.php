<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['mensajero'], 'No autorizado');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../models/misPaquetesMensajerosModels.php';

function guardarImagenBase64($base64, $subcarpeta = 'entregas')
{
    return saveBase64ImageSafe($base64, $subcarpeta, 'ebm');
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

        case 'registrar_novedad':
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            if (!is_array($input)) {
                throw new Exception('Payload inválido');
            }

            $tipo = trim((string) ($input['tipo'] ?? ''));
            if (!in_array($tipo, ['aplazado', 'cancelado'], true)) {
                throw new Exception('Tipo de novedad no válido');
            }

            $descripcion = trim((string) ($input['descripcion'] ?? ''));
            if ($descripcion === '') {
                throw new Exception('La descripción es obligatoria');
            }

            $fotoData = $input['foto']['data'] ?? null;
            if (!$fotoData) {
                throw new Exception('Debes adjuntar una evidencia fotográfica');
            }

            $rutaFoto = guardarImagenBase64($fotoData, 'novedades');
            if (!$rutaFoto) {
                throw new Exception('No se pudo guardar la foto de evidencia');
            }

            $rutaFotoAdicional = null;
            $fotoAdicionalData = null;
            if (!empty($input['foto_adicional']) && is_array($input['foto_adicional'])) {
                $fotoAdicionalData = $input['foto_adicional']['data'] ?? null;
            }
            if ($fotoAdicionalData) {
                $rutaFotoAdicional = guardarImagenBase64($fotoAdicionalData, 'novedades');
            }

            $payload = [
                'paquete_id' => (int) ($input['paquete_id'] ?? 0),
                'numero_guia' => trim($input['numero_guia'] ?? ''),
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'foto_evidencia' => $rutaFoto,
                'foto_adicional' => $rutaFotoAdicional,
                'lat' => isset($input['ubicacion']['lat']) ? (float) $input['ubicacion']['lat'] : null,
                'lng' => isset($input['ubicacion']['lng']) ? (float) $input['ubicacion']['lng'] : null
            ];

            if ($payload['paquete_id'] <= 0 && $payload['numero_guia'] === '') {
                throw new Exception('No se identificó el paquete');
            }

            $model->registrarNovedad((int) $mensajero['id'], $payload);
            echo json_encode([
                'success' => true,
                'message' => $tipo === 'cancelado' ? 'Paquete cancelado correctamente' : 'Novedad registrada correctamente'
            ]);
            break;

        case 'guardar_cierre_jornada':
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            if (!is_array($input)) {
                throw new Exception('Payload inválido');
            }

            $payload = [
                'total_paquetes' => (int) ($input['total_paquetes'] ?? 0),
                'entregados' => (int) ($input['entregados'] ?? 0),
                'aplazados' => (int) ($input['aplazados'] ?? 0),
                'cancelados' => (int) ($input['cancelados'] ?? 0),
                'recaudo_total' => (float) ($input['recaudo_total'] ?? 0),
                'observacion' => trim((string) ($input['observacion'] ?? '')) ?: null,
                'detalle_json' => isset($input['detalle']) ? json_encode($input['detalle'], JSON_UNESCAPED_UNICODE) : null
            ];

            $model->guardarCierreJornada((int) $mensajero['id'], $payload);
            echo json_encode(['success' => true, 'message' => 'Cierre de jornada guardado']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
