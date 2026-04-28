<?php
require_once __DIR__ . '/../includes/auth.php';
requireApiAuth(['administrador', 'admin'], 'No autorizado');
require_once '../models/paquetesAdminModels.php';
require_once __DIR__ . '/../includes/upload.php';

// Configurar cabecera para devolver JSON
header('Content-Type: application/json');

$model = new PaquetesAdminModel();
$action = $_REQUEST['action'] ?? 'listar'; 

try {
    switch ($action) {
        case 'listar':
            // Recoger filtros enviados desde el JS
            $filters = [
                'search' => $_REQUEST['search'] ?? '',
                'fechaDesde' => $_REQUEST['fechaDesde'] ?? '',
                'fechaHasta' => $_REQUEST['fechaHasta'] ?? '',
                'cliente' => $_REQUEST['cliente'] ?? '',
                'estado' => $_REQUEST['estado'] ?? '',
                'zona' => $_REQUEST['zona'] ?? '',
                'mensajero' => $_REQUEST['mensajero'] ?? '',
                'tipo' => $_REQUEST['tipo'] ?? ''
            ];
            
            $data = $model->getPaquetes($filters);
            
            // Devolver en formato que DataTables o tu JS pueda leer
            echo json_encode(['data' => $data]);
            break;

        case 'filtros':
            // Devolver listas para los selects (clientes y mensajeros)
            $data = $model->getFilters();
            echo json_encode($data);
            break;

        case 'detalle':
            $id = $_REQUEST['id'] ?? 0;
            $data = $model->getPaqueteDetails($id);
            echo json_encode($data);
            break;
            
        case 'asignar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $paqueteId = $_POST['paquete_id'];
                $mensajeroId = $_POST['mensajero_id'];
                $userId = $_SESSION['user_id'] ?? 0;
                
                $res = $model->assignMensajero($paqueteId, $mensajeroId, $userId);
                echo json_encode(['success' => $res]);
            }
            break;

        case 'actualizar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                break;
            }
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            if (!is_array($input)) {
                echo json_encode(['success' => false, 'error' => 'Payload inválido']);
                break;
            }

            $paqueteId = (int) ($input['paquete_id'] ?? 0);
            if ($paqueteId <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID de paquete inválido']);
                break;
            }

            $payload = [
                'numero_guia' => trim((string) ($input['numero_guia'] ?? '')),
                'remitente_nombre' => trim((string) ($input['remitente_nombre'] ?? '')),
                'destinatario_nombre' => trim((string) ($input['destinatario_nombre'] ?? '')),
                'destinatario_telefono' => trim((string) ($input['destinatario_telefono'] ?? '')),
                'direccion_destino' => trim((string) ($input['direccion_destino'] ?? '')),
                'descripcion_contenido' => trim((string) ($input['descripcion_contenido'] ?? '')),
                'tipo_servicio' => trim((string) ($input['tipo_servicio'] ?? '')),
                'costo_envio' => (float) ($input['costo_envio'] ?? 0),
                'recaudo_esperado' => (float) ($input['recaudo_esperado'] ?? 0),
                'instrucciones_entrega' => trim((string) ($input['instrucciones_entrega'] ?? '')),
                'estado' => trim((string) ($input['estado'] ?? '')),
                'mensajero_id' => (int) ($input['mensajero_id'] ?? 0),
                'mensajero_recoleccion_id' => (int) ($input['mensajero_recoleccion_id'] ?? 0),
                'fecha_creacion' => trim((string) ($input['fecha_creacion'] ?? ''))
            ];

            $fechaEntregaSync = null;

            $actualizoEntrega = null;
            if (!empty($input['entrega']) && is_array($input['entrega'])) {
                $entrega = $input['entrega'];
                $fechaEntrega = trim((string) ($entrega['fecha_entrega'] ?? ''));
                if ($payload['estado'] === 'entregado' && $fechaEntrega === '') {
                    $fechaEntrega = date('Y-m-d H:i:s');
                }
                $payloadEntrega = [
                    'nombre_receptor' => trim((string) ($entrega['nombre_receptor'] ?? '')),
                    'parentesco_cargo' => trim((string) ($entrega['parentesco_cargo'] ?? '')),
                    'documento_receptor' => trim((string) ($entrega['documento_receptor'] ?? '')),
                    'recaudo_real' => (float) ($entrega['recaudo_real'] ?? 0),
                    'fecha_entrega' => $fechaEntrega,
                    'observaciones' => trim((string) ($entrega['observaciones'] ?? '')),
                    'mensajero_id' => (int) ($input['mensajero_id'] ?? 0)
                ];
                if ($payload['estado'] === 'entregado') {
                    $fechaEntregaSync = $fechaEntrega;
                }
                $payload['fecha_entrega'] = $fechaEntregaSync;
            } elseif ($payload['estado'] === 'entregado') {
                $fechaEntregaSync = date('Y-m-d H:i:s');
                $payload['fecha_entrega'] = $fechaEntregaSync;
                $payloadEntrega = [
                    'nombre_receptor' => '',
                    'parentesco_cargo' => '',
                    'documento_receptor' => '',
                    'recaudo_real' => 0,
                    'fecha_entrega' => $fechaEntregaSync,
                    'observaciones' => '',
                    'mensajero_id' => (int) ($input['mensajero_id'] ?? 0)
                ];
            }

            $res = $model->updatePaqueteAdmin($paqueteId, $payload);

            if (!empty($payloadEntrega)) {
                $actualizoEntrega = $model->updateEntregaInfo($paqueteId, $payloadEntrega);
            }

            $actualizoCancelacion = null;
            if (!empty($input['cancelacion']) && is_array($input['cancelacion'])) {
                $cancelacion = $input['cancelacion'];
                $payloadCancel = [
                    'descripcion' => trim((string) ($cancelacion['descripcion'] ?? ''))
                ];
                $actualizoCancelacion = $model->updateCancelacionInfo($paqueteId, $payloadCancel);
            }

            echo json_encode([
                'success' => $res ? true : false,
                'actualizoEntrega' => $actualizoEntrega,
                'actualizoCancelacion' => $actualizoCancelacion
            ]);
            break;

        case 'imagen_subir':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                break;
            }
            $paqueteId = (int) ($_POST['paquete_id'] ?? 0);
            if ($paqueteId <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID de paquete inválido']);
                break;
            }

            $tipo = trim((string) ($_POST['tipo'] ?? 'general'));
            $tiposValidos = ['general', 'entrega', 'cancelacion', 'recoleccion'];
            if (!in_array($tipo, $tiposValidos, true)) {
                $tipo = 'general';
            }

            $subdir = 'paquetes';
            if ($tipo === 'entrega') $subdir = 'entregas';
            if ($tipo === 'cancelacion') $subdir = 'novedades';
            if ($tipo === 'recoleccion') $subdir = 'recolecciones';

            if (empty($_FILES['imagenes'])) {
                echo json_encode(['success' => false, 'error' => 'No se encontraron archivos']);
                break;
            }

            $files = $_FILES['imagenes'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $userId = $_SESSION['user_id'] ?? null;
            $added = [];

            $count = is_array($files['name']) ? count($files['name']) : 0;
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                $basename = saveUploadedFileSafe($file, dirname(__DIR__) . '/uploads/' . $subdir, $allowedMimes, 'ebm', true);
                if (!$basename) {
                    continue;
                }
                $ruta = '/uploads/' . $subdir . '/' . $basename;
                $imageId = $model->addPaqueteImagen($paqueteId, $tipo, $ruta, $userId);
                $added[] = [
                    'id' => $imageId,
                    'tipo' => $tipo,
                    'ruta_archivo' => $ruta
                ];
            }

            echo json_encode(['success' => true, 'imagenes' => $added]);
            break;

        case 'imagen_eliminar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                break;
            }
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            if (!is_array($input)) {
                echo json_encode(['success' => false, 'error' => 'Payload inválido']);
                break;
            }

            $paqueteId = (int) ($input['paquete_id'] ?? 0);
            $imageId = (int) ($input['image_id'] ?? 0);
            $target = trim((string) ($input['target'] ?? ''));

            if ($imageId > 0) {
                $img = $model->getPaqueteImagenById($imageId);
                if (!$img) {
                    echo json_encode(['success' => false, 'error' => 'Imagen no encontrada']);
                    break;
                }
                $ruta = $img['ruta_archivo'] ?? '';
                $model->deletePaqueteImagen($imageId);
                eliminarArchivoSiExiste($ruta);
                echo json_encode(['success' => true]);
                break;
            }

            if ($paqueteId <= 0 || $target === '') {
                echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
                break;
            }

            if ($target === 'entrega_principal' || $target === 'entrega_adicional') {
                $fotos = $model->getEntregaFotos($paqueteId);
                if (!$fotos) {
                    echo json_encode(['success' => false, 'error' => 'Entrega no encontrada']);
                    break;
                }
                $campo = $target === 'entrega_principal' ? 'foto_entrega' : 'foto_adicional';
                $ruta = $fotos[$campo] ?? '';
                $model->updateEntregaFoto($paqueteId, $campo, '');
                eliminarArchivoSiExiste($ruta);
                echo json_encode(['success' => true]);
                break;
            }

            if ($target === 'cancelacion') {
                $foto = $model->getCancelacionFoto($paqueteId);
                if (!$foto) {
                    echo json_encode(['success' => false, 'error' => 'Cancelación no encontrada']);
                    break;
                }
                $ruta = $foto['foto_evidencia'] ?? '';
                $model->updateCancelacionFoto($paqueteId, '');
                eliminarArchivoSiExiste($ruta);
                echo json_encode(['success' => true]);
                break;
            }

            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;

        case 'imagen_reemplazar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                break;
            }
            $paqueteId = (int) ($_POST['paquete_id'] ?? 0);
            $target = trim((string) ($_POST['target'] ?? ''));
            if ($paqueteId <= 0 || !isset($_FILES['imagen'])) {
                echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
                break;
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if ($target === 'entrega_principal' || $target === 'entrega_adicional') {
                $fotos = $model->getEntregaFotos($paqueteId);
                if (!$fotos) {
                    echo json_encode(['success' => false, 'error' => 'Entrega no encontrada']);
                    break;
                }
                $basename = saveUploadedFileSafe($_FILES['imagen'], dirname(__DIR__) . '/uploads/entregas', $allowedMimes, 'ebm', true);
                if (!$basename) {
                    echo json_encode(['success' => false, 'error' => 'No se pudo guardar la imagen']);
                    break;
                }
                $ruta = '/uploads/entregas/' . $basename;
                $campo = $target === 'entrega_principal' ? 'foto_entrega' : 'foto_adicional';
                $rutaAnterior = $fotos[$campo] ?? '';
                $model->updateEntregaFoto($paqueteId, $campo, $ruta);
                eliminarArchivoSiExiste($rutaAnterior);
                echo json_encode(['success' => true, 'ruta' => $ruta]);
                break;
            }

            if ($target === 'cancelacion') {
                $foto = $model->getCancelacionFoto($paqueteId);
                if (!$foto) {
                    echo json_encode(['success' => false, 'error' => 'Cancelación no encontrada']);
                    break;
                }
                $basename = saveUploadedFileSafe($_FILES['imagen'], dirname(__DIR__) . '/uploads/novedades', $allowedMimes, 'ebm', true);
                if (!$basename) {
                    echo json_encode(['success' => false, 'error' => 'No se pudo guardar la imagen']);
                    break;
                }
                $ruta = '/uploads/novedades/' . $basename;
                $rutaAnterior = $foto['foto_evidencia'] ?? '';
                $model->updateCancelacionFoto($paqueteId, $ruta);
                eliminarArchivoSiExiste($rutaAnterior);
                echo json_encode(['success' => true, 'ruta' => $ruta]);
                break;
            }

            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

function eliminarArchivoSiExiste($ruta)
{
    if (!$ruta) return;
    $rutaLimpia = str_replace(['..', '\\'], '', $ruta);
    $base = dirname(__DIR__);
    $rutaFisica = realpath($base . $rutaLimpia);
    if ($rutaFisica && is_file($rutaFisica)) {
        @unlink($rutaFisica);
    }
}
?>
