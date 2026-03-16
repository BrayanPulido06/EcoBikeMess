<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['mensajero'], 'No autorizado');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../models/recoleccionesMensajeroModels.php';

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

        case 'detalle':
            $recoleccionId = (int) ($_GET['recoleccion_id'] ?? 0);
            if ($recoleccionId <= 0) {
                throw new Exception('ID de recolección inválido');
            }

            $paquetes = $model->listarPaquetesRecoleccion($recoleccionId, (int) $mensajero['id']);
            echo json_encode([
                'success' => true,
                'paquetes' => $paquetes
            ]);
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
            $rutaFoto = saveBase64ImageSafe($fotos[0]['data'], 'recolecciones', 'ebm');
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

        case 'cancelar':
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            if (!is_array($input)) {
                throw new Exception('Payload inválido');
            }
            $recoleccionId = (int) ($input['recoleccion_id'] ?? 0);
            if ($recoleccionId <= 0) {
                throw new Exception('ID de recolección inválido');
            }
            $motivo = trim((string) ($input['motivo'] ?? ''));
            if ($motivo === '') {
                throw new Exception('Motivo requerido');
            }

            $ok = $model->cancelarRecoleccion($recoleccionId, (int) $mensajero['id'], $motivo);
            if (!$ok) {
                throw new Exception('No se pudo cancelar la recolección');
            }
            echo json_encode(['success' => true, 'message' => 'Recolección cancelada']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
