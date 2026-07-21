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
                'recaudo' => $_REQUEST['recaudo'] ?? '',
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

        case 'imagen_ver':
            $ruta = '';
            $paqueteId = (int) ($_GET['paquete_id'] ?? 0);
            $imageId = (int) ($_GET['image_id'] ?? 0);
            $novedadId = (int) ($_GET['novedad_id'] ?? 0);
            $target = trim((string) ($_GET['target'] ?? ''));

            if ($imageId > 0) {
                $imagen = $model->getPaqueteImagenById($imageId);
                $ruta = (string) ($imagen['ruta_archivo'] ?? '');
            } elseif ($novedadId > 0) {
                $novedad = $model->getNovedadFotoById($novedadId);
                $campo = $target === 'novedad_adicional' ? 'foto_adicional' : 'foto_evidencia';
                $ruta = (string) ($novedad[$campo] ?? '');
            } elseif ($paqueteId > 0 && ($target === 'entrega_principal' || $target === 'entrega_adicional')) {
                $fotos = $model->getEntregaFotos($paqueteId);
                $campo = $target === 'entrega_principal' ? 'foto_entrega' : 'foto_adicional';
                $ruta = (string) ($fotos[$campo] ?? '');
            } elseif ($paqueteId > 0 && $target === 'cancelacion') {
                $foto = $model->getCancelacionFoto($paqueteId);
                $ruta = (string) ($foto['foto_evidencia'] ?? '');
            }

            servirImagenGuardada($ruta);
            break;
            
        case 'asignar':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $paqueteId = (int) ($_POST['paquete_id'] ?? 0);
                $mensajeroId = (int) ($_POST['mensajero_id'] ?? 0);
                $userId = $_SESSION['user_id'] ?? 0;

                if ($paqueteId <= 0 || $mensajeroId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Datos de asignación inválidos']);
                    break;
                }

                $res = $model->assignMensajero($paqueteId, $mensajeroId, $userId);
                echo json_encode(['success' => $res]);
            }
            break;

        case 'asignar_masivo':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $mensajeroId = (int) ($_POST['mensajero_id'] ?? 0);
                $paqueteIds = $_POST['paquete_ids'] ?? [];
                $userId = $_SESSION['user_id'] ?? 0;

                if (!is_array($paqueteIds)) {
                    $paqueteIds = [];
                }

                $paqueteIds = array_values(array_unique(array_filter(array_map('intval', $paqueteIds), static fn($id) => $id > 0)));

                if ($mensajeroId <= 0 || empty($paqueteIds)) {
                    echo json_encode(['success' => false, 'error' => 'Debes seleccionar paquetes y un mensajero válido']);
                    break;
                }

                $asignados = $model->assignMensajeroBulk($paqueteIds, $mensajeroId, $userId);
                echo json_encode([
                    'success' => $asignados > 0,
                    'asignados' => $asignados,
                    'error' => $asignados > 0 ? null : 'No se pudieron asignar los paquetes seleccionados'
                ]);
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
                    'recibio_cambios' => (int) ($entrega['recibio_cambios'] ?? 0),
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
                    'recibio_cambios' => 0,
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

            $success = (bool) $res;
            if (!empty($payloadEntrega)) {
                $success = $success && (bool) $actualizoEntrega;
            }
            if (!empty($input['cancelacion']) && is_array($input['cancelacion'])) {
                $success = $success && (bool) $actualizoCancelacion;
            }

            echo json_encode([
                'success' => $success,
                'actualizoEntrega' => $actualizoEntrega,
                'actualizoCancelacion' => $actualizoCancelacion,
                'error' => $success ? null : 'No se pudo guardar toda la información del cierre.'
            ]);
            break;
        case 'toggle_checklist_verde':
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
            $checked = !empty($input['checklist_verde']) ? 1 : 0;

            if ($paqueteId <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID de paquete inválido']);
                break;
            }

            $res = $model->updateChecklistVerde($paqueteId, $checked);
            echo json_encode([
                'success' => (bool) $res,
                'checklist_verde' => $checked,
                'error' => $res ? null : 'No se pudo actualizar el checklist'
            ]);
            break;

        case 'crear_entrega_sin_rotulo':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Metodo no permitido']);
                break;
            }

            $rutaPrincipal = '';
            $rutaAdicional = '';
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (empty($_FILES['foto_entrega']) || (int) ($_FILES['foto_entrega']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                echo json_encode(['success' => false, 'error' => 'Debes adjuntar la evidencia fotografica']);
                break;
            }

            $basenamePrincipal = saveUploadedFileSafe($_FILES['foto_entrega'], dirname(__DIR__) . '/uploads/entregas', $allowedMimes, 'ebm_entrega_admin', true);
            if (!$basenamePrincipal) {
                echo json_encode(['success' => false, 'error' => 'No se pudo guardar la evidencia fotografica']);
                break;
            }
            $rutaPrincipal = '/uploads/entregas/' . $basenamePrincipal;

            if (!empty($_FILES['foto_adicional']) && (int) ($_FILES['foto_adicional']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $basenameAdicional = saveUploadedFileSafe($_FILES['foto_adicional'], dirname(__DIR__) . '/uploads/entregas', $allowedMimes, 'ebm_entrega_admin_extra', true);
                if (!$basenameAdicional) {
                    eliminarArchivoSiExiste($rutaPrincipal);
                    echo json_encode(['success' => false, 'error' => 'No se pudo guardar la foto adicional']);
                    break;
                }
                $rutaAdicional = '/uploads/entregas/' . $basenameAdicional;
            }

            try {
                $payload = [
                    'cliente_id' => (int) ($_POST['cliente_id'] ?? 0),
                    'mensajero_id' => (int) ($_POST['mensajero_id'] ?? 0),
                    'numero_guia' => trim((string) ($_POST['numero_guia'] ?? '')),
                    'destinatario_nombre' => trim((string) ($_POST['destinatario_nombre'] ?? '')),
                    'nombre_receptor' => trim((string) ($_POST['nombre_receptor'] ?? '')),
                    'parentesco_cargo' => trim((string) ($_POST['parentesco_cargo'] ?? '')),
                    'documento_receptor' => trim((string) ($_POST['documento_receptor'] ?? '')),
                    'recaudo_real' => (float) ($_POST['recaudo_real'] ?? 0),
                    'recibio_cambios' => isset($_POST['recibio_cambios']) ? 1 : 0,
                    'observaciones' => trim((string) ($_POST['observaciones'] ?? '')),
                    'foto_entrega' => $rutaPrincipal,
                    'foto_adicional' => $rutaAdicional,
                    'creado_por' => (int) ($_SESSION['user_id'] ?? 0)
                ];

                $result = $model->crearEntregaSinRotuloAdmin($payload);
                echo json_encode(['success' => true, 'guia' => $result['guia'], 'paquete_id' => $result['paquete_id']]);
            } catch (Throwable $e) {
                eliminarArchivoSiExiste($rutaPrincipal);
                eliminarArchivoSiExiste($rutaAdicional);
                throw $e;
            }
            break;

        case 'cancelar_servicio':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                break;
            }

            $paqueteId = (int) ($_POST['paquete_id'] ?? 0);
            $motivo = trim((string) ($_POST['motivo'] ?? ''));

            if ($paqueteId <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID de paquete inválido']);
                break;
            }

            if ($motivo === '') {
                echo json_encode(['success' => false, 'error' => 'Debes ingresar la razón de cancelación']);
                break;
            }

            $ruta = '';
            if (!empty($_FILES['evidencia']) && (int) ($_FILES['evidencia']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                $basename = saveUploadedFileSafe($_FILES['evidencia'], dirname(__DIR__) . '/uploads/novedades', $allowedMimes, 'ebm_cancel', true);
                if (!$basename) {
                    echo json_encode(['success' => false, 'error' => 'No se pudo guardar la evidencia fotográfica']);
                    break;
                }

                $ruta = '/uploads/novedades/' . $basename;
            }

            try {
                $res = $model->cancelarServicioAdmin($paqueteId, $motivo, $ruta, (int) ($_SESSION['user_id'] ?? 0));
                echo json_encode(['success' => (bool) $res]);
            } catch (Throwable $e) {
                eliminarArchivoSiExiste($ruta);
                throw $e;
            }
            break;

        case 'eliminar':
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

            $result = $model->eliminarPaqueteAdmin($paqueteId);
            foreach (($result['rutas'] ?? []) as $ruta) {
                eliminarArchivoSiExiste($ruta);
            }

            echo json_encode([
                'success' => true,
                'resumen' => $result['resumen'] ?? null
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

                if ($tipo === 'entrega') {
                    $fotosActuales = $model->getEntregaFotos($paqueteId) ?: [];
                    $campo = empty($fotosActuales['foto_entrega']) ? 'foto_entrega' : 'foto_adicional';
                    if ($model->updateEntregaFoto($paqueteId, $campo, $ruta)) {
                        $added[] = [
                            'tipo' => $tipo,
                            'ruta_archivo' => $ruta,
                            'target' => $campo === 'foto_entrega' ? 'entrega_principal' : 'entrega_adicional'
                        ];
                    }
                    continue;
                }

                if ($tipo === 'cancelacion') {
                    $model->ensureCancelacionRecord($paqueteId);
                    if ($model->updateCancelacionFoto($paqueteId, $ruta)) {
                        $added[] = [
                            'tipo' => $tipo,
                            'ruta_archivo' => $ruta,
                            'target' => 'cancelacion'
                        ];
                    }
                    continue;
                }

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

function resolverRutaUpload($ruta): ?string
{
    $raw = trim((string) $ruta);
    if ($raw === '') {
        return null;
    }

    $normalized = str_replace('\\', '/', $raw);
    $uploadsPos = stripos($normalized, '/uploads/');
    if ($uploadsPos !== false) {
        $normalized = substr($normalized, $uploadsPos + 1);
    }

    $normalized = preg_replace('#^(\.\./)+#', '', $normalized);
    $normalized = preg_replace('#^(\./)+#', '', $normalized);
    $normalized = ltrim((string) $normalized, '/');

    if (stripos($normalized, 'uploads/') !== 0) {
        return null;
    }

    $baseUploads = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
    if (!$baseUploads) {
        return null;
    }

    $relative = substr($normalized, strlen('uploads/'));
    $candidate = realpath($baseUploads . DIRECTORY_SEPARATOR . $relative);
    if (!$candidate || !is_file($candidate)) {
        return null;
    }

    $basePrefix = rtrim($baseUploads, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strpos($candidate, $basePrefix) !== 0) {
        return null;
    }

    return $candidate;
}

function servirImagenGuardada($ruta): void
{
    $archivo = resolverRutaUpload($ruta);
    if (!$archivo) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Imagen no encontrada';
        return;
    }

    $mime = detectMimeFromFile($archivo) ?: 'application/octet-stream';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(415);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Tipo de imagen no permitido';
        return;
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($archivo));
    header('Cache-Control: private, max-age=300');
    readfile($archivo);
}
?>


