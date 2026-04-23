<?php
require_once __DIR__ . '/../../includes/paths.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    redirect_route('login', ['error' => 'Debes iniciar sesi?n.']);
}

require_once '../../models/conexionGlobal.php';
$direccion_principal = '';
$telefono_usuario = $_SESSION['user_phone'] ?? '';
$nombre_remitente = trim((string) (($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_lastname'] ?? '')));

try {
    $conn = conexionDB();
    $sql = "SELECT m.direccion_residencia, u.telefono
            FROM mensajeros m
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.usuario_id = :usuario_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':usuario_id' => $_SESSION['user_id']]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($info) {
        $direccion_principal = $info['direccion_residencia'] ?? '';
        $telefono_usuario = $info['telefono'] ?? $telefono_usuario;
    }
} catch (Exception $e) {
    error_log("Error al obtener datos del mensajero: " . $e->getMessage());
}

if ($nombre_remitente === '') {
    $nombre_remitente = 'Mensajero EcoBikeMess';
}

$remitente_data = [
    'nombre_tienda' => 'Operativo Mensajero',
    'nombre_completo' => $nombre_remitente,
    'telefono' => $telefono_usuario,
    'correo' => $_SESSION['user_email'] ?? '',
    'direccion' => $direccion_principal
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <base href="<?php echo htmlspecialchars(app_url('/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        window.APP_BASE_PATH = <?php echo json_encode(app_url(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <title>Enviar Paquete - EcoBikeMess</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
    <link rel="stylesheet" href="../../public/css/enviarPaqueteMensajero.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <style>
        .guia-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        .qr-code-container {
            text-align: center;
        }
        #qrcode {
            width: 132px !important;
            height: 132px !important;
            padding: 2px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 5px;
        }
        #qrcode canvas {
            width: 100%;
            height: 100%;
        }
        .qr-code-container small {
            font-size: 0.8rem; color: #777;
        }
        /* R?tulo 10x10 cm (igual a paquetesAdmin) */
        #rotuloPreview {
            width: 100mm;
            height: 100mm;
            padding: 1mm 2mm 2mm 3mm !important;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }
        #rotuloPreview .rotulo-scale {
            transform: scale(0.72);
            transform-origin: top left;
            width: 139mm;
            height: 139mm;
        }
        #rotuloPreview .rotulo-scale h1 { font-size: 26px !important; }
        #rotuloPreview .rotulo-scale h2 { font-size: 20px !important; }
        #rotuloPreview .rotulo-scale h3 { font-size: 17px !important; }
        #rotuloPreview .rotulo-scale p,
        #rotuloPreview .rotulo-scale span,
        #rotuloPreview .rotulo-scale strong { font-size: 14px !important; }
        #rotuloPreview .rotulo-scale h3 { font-weight: 800 !important; }
        #rotuloPreview .rotulo-scale strong { font-weight: 800 !important; }
        #rotuloPreview .rotulo-scale p strong { font-weight: 800 !important; }
        #rotuloPreview .rotulo-scale .rotulo-total {
            margin: 2px 0;
            font-size: 26px !important;
            font-weight: 800;
            color: #28a745;
            text-align: left;
            line-height: 1.1;
        }
        #rotuloPreview .rotulo-scale .rotulo-card p {
            margin: 2px 0;
            line-height: 1.05;
        }
        #rotuloPreview .rotulo-scale .rotulo-card h3 {
            margin: 0 0 6px;
        }
        #rotuloPreview .rotulo-scale .rotulo-text-lg {
            font-size: 15px !important;
            font-weight: 600;
            line-height: 1.05;
        }
        #rotuloPreview .rotulo-scale .rotulo-text-lg.bold {
            font-weight: 700;
        }
        /* Asegurar que el QR del r?tulo no se encoja por estilos globales */
        #rotuloPreview #qrcode {
            width: 132px !important;
            height: 132px !important;
            padding: 2px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin: 0;
        }
        #rotuloPreview #qrcode canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .guia-divider-h {
            border-top: 1px solid #28a745;
            margin: 0 0 4px;
        }
        .guia-left-col {
            position: relative;
            padding-right: 2px;
        }
        .guia-left-col::after {
            content: '';
            position: absolute;
            top: 0;
            right: -4px;
            bottom: 0;
            width: 0;
            border-right: 2px solid #28a745;
        }
        .guia-right-col {
            padding-left: 6px;
        }
        /* Estilos para carga masiva */
        .bulk-preview-container {
            padding-top: 1rem;
        }
        .bulk-summary {
            margin: 0 0 1rem;
            padding: 0.9rem 1rem;
            border: 1px solid #d8ead8;
            border-radius: 12px;
            background: linear-gradient(135deg, #f7fcf7, #eef9f1);
            color: #314559;
        }
        .bulk-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 0.5rem;
            border: 1px solid #dfe7dc;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
        }
        .bulk-table th,
        .bulk-table td {
            padding: 12px;
            border-right: 1px solid #edf2eb;
            border-bottom: 1px solid #edf2eb;
            text-align: center;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        .bulk-table th:last-child,
        .bulk-table td:last-child {
            border-right: none;
        }
        .bulk-table tr:last-child td {
            border-bottom: none;
        }
        .bulk-table th {
            background: #f8f9fa;
            font-weight: 700;
            color: #2d3e50;
        }
        .bulk-chip {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            background: #eef6ee;
            color: #2f6a3b;
        }
        .bulk-cell-stack {
            display: grid;
            gap: 4px;
            text-align: left;
        }
        .bulk-cell-stack strong {
            color: #243548;
            font-size: 0.92rem;
        }
        .bulk-cell-stack small {
            color: #6b7a89;
            font-size: 0.8rem;
        }
        body.bulk-mode #mainEnvioContainer {
            display: none !important;
        }
        body.bulk-mode .main-content {
            min-height: auto;
            padding-bottom: 0;
        }
        body.bulk-mode .content-container.bulk-preview-container {
            margin-top: 0;
        }
        .status-pending { color: #f0ad4e; font-weight: bold; }
        .status-success { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        
        /* Estilos para tarjetas de selecci?n de recaudo */
        .radio-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
        }
        .radio-card:hover { border-color: #b0b0b0; background: #f9f9f9; }
        .radio-card.selected { border-color: #28a745; background: #e8f5e9; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15); }
        .radio-card input[type="radio"] { transform: scale(1.5); accent-color: #28a745; margin: 0; }
        .radio-card-content strong { display: block; font-size: 1.1rem; color: #333; margin-bottom: 2px; }
        .radio-card-content small { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">?</span>
        </button>
        <div class="header-info">
            <h1><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:35px;height:35px;vertical-align:middle;margin-right:6px;">EcoBikeMess</h1>
            <p class="user-name">Crear env?o como mensajero</p>
        </div>
    </header>

    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <main class="main-content envio-mensajero-main">
        <div class="session-status">
            <div class="status-indicator online">
                <span class="status-dot"></span>
                <span class="status-text">Modo creaci?n activo</span>
            </div>
            <div class="session-time">
                <span class="time-icon">??</span>
                <span>Nuevo env?o</span>
            </div>
        </div>
        <!-- Content -->
        <div class="content-container" id="mainEnvioContainer">
            <!-- Mensajes de validacion y feedback -->
            <?php if (isset($_GET['error'])): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #ef9a9a; margin-top: 100px;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'envio_creado'): ?>
                <div style="background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #a5d6a7; margin-top: 100px;">
                    <strong>Exito!</strong> Su envio ha sido registrado correctamente.
                    <?php if(isset($_GET['guia'])): ?>
                        <br>Numero de Guia: <strong><?php echo htmlspecialchars($_GET['guia']); ?></strong>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="page-header envio-mensajero-header">
                <div class="header-left">
                    <h1>Crear Nuevo Env?o</h1>
                    <p>Registra un paquete desde la operaci?n de mensajer?a y genera su gu?a.</p>
                </div>
                <div class="header-right">
                    <a href="<?php echo htmlspecialchars(route_url('messenger.packages'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-secondary">
                        Ver Mis Paquetes
                    </a>
                </div>
            </div>

            <!-- Indicador de Pasos -->
            <div class="steps-indicator">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Remitente</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Destinatario</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Paquete</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">Confirmar</div>
                </div>
            </div>

            <!-- Formulario Multi-Step -->
            <form id="envioForm" class="envio-form" action="../../controller/enviarPaqueteMensajeroController.php" method="POST" novalidate>
                
                <!-- PASO 1: DATOS DEL REMITENTE -->
                <div class="form-step active" data-step="1">
                    <div class="card">
                        <div class="card-header">
                            <h2>Datos del Remitente</h2>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button type="button" class="btn-text" id="autoFillRemitente">
                                    Usar mis datos
                                </button>
                                <input type="file" id="excelUpload" accept=".xlsx, .xls" style="display: none;">
                                <button type="button" class="btn-text" id="btnUploadExcel" style="color: #2196f3;">
                                    Cargar Excel
                                </button>
                                <button type="button" class="btn-text" id="btnDownloadTemplate" style="color: #28a745;">
                                    Descargar Plantilla
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="remitente_nombre">Nombre Completo *</label>
                                    <input type="text" id="remitente_nombre" name="remitente_nombre" required>
                                    <span class="error-message"></span>
                                </div>
                                <div class="form-group">
                                    <label for="remitente_telefono">Telefono *</label>
                                    <input type="tel" id="remitente_telefono" name="remitente_telefono" placeholder="300 123 4567" required>
                                    <span class="error-message"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="remitente_email">Email de Contacto *</label>
                                <input type="email" id="remitente_email" name="remitente_email" required>
                                <span class="error-message"></span>
                            </div>
                            <div class="form-group">
                                <label for="remitente_direccion">Direccion de Origen Completa *</label>
                                <textarea id="remitente_direccion" name="remitente_direccion" rows="3" placeholder="Ej: Calle 123 #45-67, Apto 301, Barrio Centro" required></textarea>
                                <span class="error-message"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: DATOS DEL DESTINATARIO -->
                <div class="form-step" data-step="2">
                    <div class="card">
                        <div class="card-header">
                            <h2>Datos del Destinatario</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="destinatario_nombre">Nombre Completo *</label>
                                    <input type="text" id="destinatario_nombre" name="destinatario_nombre" required>
                                    <span class="error-message"></span>
                                </div>
                                <div class="form-group">
                                    <label for="destinatario_telefono">Telefono *</label>
                                    <input type="tel" id="destinatario_telefono" name="destinatario_telefono" placeholder="3001234567" required maxlength="10" pattern="\d{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    <span class="error-message"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="destinatario_direccion">Direccion de Destino Completa *</label>
                                <textarea id="destinatario_direccion" name="destinatario_direccion" rows="3" placeholder="Ej: Carrera 45 #67-89, Casa 202, Barrio Norte" required></textarea>
                                <span class="error-message"></span>
                            </div>
                            <div class="form-group">
                                <label for="instrucciones_entrega">Observaciones y/o Descripciones</label>
                                <textarea id="instrucciones_entrega" name="instrucciones_entrega" rows="3" placeholder="Ej: Tocar el timbre 2 veces, entregar en porteria, etc."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="tiene_recaudo" name="tiene_recaudo">
                                    <span>Este envio tiene recaudo (pago contra entrega)</span>
                                </label>
                            </div>
                            <div class="form-group recaudo-field" style="display: none;">
                                <label for="valor_recaudo">Valor del Recaudo *</label>
                                <div class="input-with-icon">
                                    <span class="input-icon"></span>
                                    <input type="text" id="valor_recaudo" placeholder="0" maxlength="15">
                                    <input type="hidden" id="valor_recaudo_hidden" name="valor_recaudo">
                                </div>
                                <span class="error-message"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 3: DATOS DEL PAQUETE -->
                <div class="form-step" data-step="3">
                    <div class="card">
                        <div class="card-header">
                            <h2>Informacion del Paquete</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="dimensiones_paquete">Dimensiones del Paquete *</label>
                                <select id="dimensiones_paquete" name="dimensiones_paquete" required>
                                    <option value="">Seleccionar tamano...</option>
                                    <option value="0">Menor o igual a 20 x 20 cm</option>
                                    <option value="2000">Entre 21x21 y 30x30 cm (+$2.000)</option>
                                    <option value="4000">Entre 31x31 y 35x35 cm (+$4.000)</option>
                                    <option value="7000">Entre 36x36 y 40x40 cm (+$7.000)</option>
                                    <option value="10000">Entre 41x41 y 45x45 cm (+$10.000)</option>
                                    <option value="12000">Entre 46x46 y 49x49 cm (+$12.000)</option>
                                    <option value="notificar">Igual o mayor a 50 x 50 cm (Notificar)</option>
                                </select>
                                <span class="error-message"></span>
                            </div>
                            <div class="form-row" style="margin-top: 15px;">
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="envio_mismo_dia" name="envio_mismo_dia">
                                        <span>Entrega el mismo dia? (+$2.000)</span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="zona_periferica" name="zona_periferica">
                                        <span>Destino Soacha, Usme, C. Bolivar o San Cristobal sur (+$4.000)</span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="recoger_cambios" name="recoger_cambios">
                                        <span>Hay cambios por recoger? (+$5.000)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calculo de costo -->
                    <div class="card cost-card">
                        <div class="card-header">
                            <h2>Calculo del Costo</h2>
                        </div>
                        <div class="card-body">
                            <div class="cost-breakdown">
                                <div class="cost-item">
                                    <span>Costo base por zona:</span>
                                    <span id="costoBase">$8.000</span>
                                </div>
                                <div class="cost-item">
                                    <span>Recargo por dimensiones:</span>
                                    <span id="recargoDimensiones">$0</span>
                                </div>
                                <div class="cost-item">
                                    <span>Entrega mismo dia:</span>
                                    <span id="recargoMismoDia">$0</span>
                                </div>
                                <div class="cost-item">
                                    <span>Zonas de dificil acceso:</span>
                                    <span id="recargoZona">$0</span>
                                </div>
                                <div class="cost-item">
                                    <span>Cambios por recoger:</span>
                                    <span id="recargoCambios">$0</span>
                                </div>
                                <div class="cost-item">
                                    <span>Recaudo (si aplica):</span>
                                    <span id="valorRecaudoDisplay">$0</span>
                                </div>
                                <div class="cost-divider"></div>
                                <div class="cost-item total">
                                    <span>Total a pagar:</span>
                                    <span id="costoTotal">$8.000</span>
                                </div>
                                
                                <!-- Opcion para sumar envio al recaudo -->
                                <div class="form-group" id="container_sumar_envio" style="display: none; margin-top: 25px; border-top: 2px dashed #eee; padding-top: 20px;">
                                    <label style="font-weight: bold; display: block; margin-bottom: 15px; font-size: 1.1rem; color: #2c3e50;">Desea sumar el costo del envio al valor del recaudo? *</label>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <label class="radio-card">
                                            <input type="radio" name="envio_destinatario" value="si" required>
                                            <div class="radio-card-content">
                                                <strong>SI, SUMAR</strong>
                                                <small>Cobrar envio al destinatario</small>
                                            </div>
                                        </label>
                                        <label class="radio-card">
                                            <input type="radio" name="envio_destinatario" value="no" required>
                                            <div class="radio-card-content">
                                                <strong>NO, MANTENER</strong>
                                                <small>Cobrar solo el valor del producto</small>
                                            </div>
                                        </label>
                                    </div>
                                    <div id="preview_total_recaudo" style="margin-top: 15px; font-weight: bold; color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 12px; border-radius: 6px; text-align: center; font-size: 1.1rem; display: none;"></div>
                                    <span class="error-message"></span>
                                </div>

                                <!-- Campo oculto para enviar el costo total -->
                                <input type="hidden" name="costo_total" id="costoTotalHidden" value="8000">
                                <!-- Campo oculto para enviar el numero de guia generado en JS -->
                                <input type="hidden" name="numero_guia" id="numeroGuiaHidden">
                            </div>
                            <button type="button" class="btn-text" id="calcularCosto" style="display: none;">
                                Calcular costo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PASO 4: CONFIRMACION -->
                <div class="form-step" data-step="4">
                    <div class="card confirmation-card">
                        <div class="card-header">
                            <h2>Confirmar Envio</h2>
                            <button type="button" class="btn-secondary" id="btnDownloadPDF">
                                Descargar Guia (PDF)
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="rotuloPreview" style="background: white; padding: 20px; border: 1px solid #ccc; font-family: Arial, sans-serif; color: #333;">
                                <div class="rotulo-scale">
                                    <table style="width: 100%; border-bottom: 2px solid #5cb85c; padding-bottom: 6px;">
                                        <tr>
                                            <td colspan="2">
                                                <div style="display: flex; align-items: center; gap: 100px; justify-content: center; text-align: center;">
                                                    <img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:100px;height:100px;">
                                                    <div>
                                                        <div style="font-size: 26px; font-weight: 800; color: #5cb85c; line-height: 1;">EcoBikeMess</div>
                                                        <div style="margin-top: 3px; font-size: 15px; font-weight: 700; color: #28a745;">Contactanos: 317509298</div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-top: 4px;">
                                                <div style="font-size: 13px; font-weight: 800; color: #000000;">NUM GUIA: <span id="numeroGuia" style="font-size: 19px; font-weight: 800; color: #1f2a37;">EBM-2024-XXXXX</span></div>
                                            </td>
                                        </tr>
                                    </table>

                                    <table style="width: 100%; margin-top: 4px; font-size: 12px;">
                                        <tr>
                                            <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                                <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;"> Destinatario</h3>
                                                <p><strong>Direccion:</strong> <span id="confirm_destinatario_direccion" class="rotulo-text-lg bold"></span></p>
                                                <p><strong>Nombre:</strong> <span id="confirm_destinatario_nombre" class="rotulo-text-lg bold"></span></p>
                                                <p><strong>Telefono:</strong> <span id="confirm_destinatario_telefono" class="rotulo-text-lg bold"></span></p>
                                                <p><strong>Observaciones:</strong> <span id="confirm_destinatario_observaciones" class="rotulo-text-lg bold"></span></p>
                                            </td>
                                            <td style="width: 4%;"></td>
                                            <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                                <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;"> Remitente</h3>
                                                <p><strong>Tienda:</strong> <span id="confirm_tienda_nombre" class="rotulo-text-lg bold"></span></p>
                                            </td>
                                        </tr>
                                    </table>

                                    <table style="width: 100%; margin-top: 4px; padding-top: 0;">
                                        <tr>
                                            <td style="width: 60%; vertical-align: top; font-size: 12px;">
                                                <div class="guia-left-col">
                                                    <div class="guia-divider-h"></div>
                                                    <div class="rotulo-card" style="border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                                        <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;"> Detalles del Paquete</h3>
                                                        <p><strong>Cambios por recoger:</strong> <span id="confirm_recoger_cambios" class="rotulo-text-lg bold"></span></p>
                                                    </div>
                                                    <div style="margin-top: 6px;">
                                                        <h3 style="margin: 0 0 6px; font-size: 15px;"> Total a Cobrar</h3>
                                                        <div>
                                                            <p id="confirm_total_cobrar" class="rotulo-total">$0</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="width: 40%; text-align: right; vertical-align: top;">
                                                <div class="guia-right-col">
                                                    <div id="qrcode" style="display:inline-flex;width:132px;height:132px;padding:2px;border:1px solid #e5e7eb;border-radius:10px;margin-right:6mm;margin-top:-2mm;background:#fff;align-items:center;justify-content:center;"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de navegacion -->
                <div class="form-navigation">
                    <button type="button" class="btn-secondary" id="btnPrevious" style="display: none;">
                        Anterior
                    </button>
                    <div class="nav-spacer"></div>
                    <button type="button" class="btn-primary" id="btnNext">
                        Siguiente
                    </button>
                    <button type="submit" class="btn-success" id="btnSubmit" style="display: none;">
                        Confirmar Envio
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contenedor para Carga Masiva (Oculto por defecto) -->
    <div id="bulkPreviewContainer" class="content-container bulk-preview-container" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h2>Carga Masiva de Envios</h2>
                <div class="header-actions">
                    <button type="button" class="btn-text" id="btnCancelBulk" style="color: #dc3545;">Cancelar</button>
                    <button type="button" class="btn-primary" id="btnProcessBulk">Procesar Todos</button>
                </div>
            </div>
            <div class="card-body">
                <p class="bulk-summary">Se han detectado multiples envios. Revisa la informacion cargada antes de procesar.</p>
                <div class="table-responsive">
                    <table class="bulk-table">
                        <thead>
                            <tr>
                                <th>Guia</th>
                                <th>Remitente</th>
                                <th>Destinatario</th>
                                <th>Paquete</th>
                                <th>Costo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="bulkTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    </main>

    <!-- Modal WhatsApp -->
    <div id="whatsappModal" class="modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
        <div class="modal-content" style="background: white; padding: 30px; border-radius: 15px; text-align: center; max-width: 400px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: fadeIn 0.3s;">
            <span class="close-wa-modal" style="position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #aaa;">&times;</span>
            <div style="font-size: 50px; margin-bottom: 15px;">Paquete</div>
            <h3 style="margin-bottom: 15px; color: #333;">Dimensiones Especiales</h3>
            <p style="margin-bottom: 25px; color: #666;">Para paquetes de 50x50 cm o mas, por favor contactanos directamente para coordinar el envio.</p>
            <a href="https://wa.link/49g8jg" target="_blank" class="btn-whatsapp" style="background-color: #25D366; color: white; padding: 12px 25px; border-radius: 50px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 10px; transition: transform 0.2s; box-shadow: 0 4px 6px rgba(37, 211, 102, 0.2);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382C17.117 14.205 15.374 13.349 15.049 13.231C14.724 13.113 14.488 13.054 14.252 13.408C14.016 13.762 13.337 14.559 13.13 14.795C12.924 15.031 12.717 15.06 12.363 14.883C12.009 14.706 10.867 14.332 9.514 13.125C8.455 12.181 7.74 11.016 7.533 10.662C7.326 10.308 7.511 10.116 7.689 9.939C7.847 9.781 8.04 9.529 8.217 9.323C8.394 9.117 8.453 8.969 8.571 8.733C8.689 8.497 8.63 8.291 8.541 8.114C8.452 7.937 7.744 6.195 7.449 5.487C7.162 4.798 6.87 4.892 6.653 4.892C6.456 4.892 6.23 4.892 6.004 4.892C5.778 4.892 5.413 4.976 5.108 5.309C4.803 5.642 3.947 6.448 3.947 8.08C3.947 9.712 5.137 11.292 5.304 11.518C5.471 11.744 7.664 15.125 11.021 16.574C11.819 16.919 12.442 17.125 12.926 17.278C13.88 17.58 14.746 17.536 15.426 17.434C16.183 17.321 17.758 16.481 18.083 15.567C18.408 14.653 18.408 13.887 18.29 13.68C18.172 13.474 17.827 13.415 17.472 13.238V14.382ZM12.046 21.957C10.266 21.957 8.593 21.485 7.127 20.654L6.812 20.467L3.047 21.453L4.052 17.78L3.845 17.45C2.92 15.979 2.432 14.278 2.432 12.518C2.432 7.213 6.748 2.897 12.051 2.897C14.62 2.897 17.035 3.897 18.85 5.712C20.665 7.527 21.665 9.942 21.665 12.511C21.665 17.816 17.349 22.132 12.046 21.957Z" fill="white"/></svg>
                WhatsApp +57 312318019
            </a>
        </div>
    </div>

    <script>
        // Pasar datos de PHP a JavaScript para autocompletar
        window.remitenteData = <?php echo json_encode($remitente_data, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <!-- Librerias para QR con logo y para generar PDF -->
    <script src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>

    <script src="../../public/js/imageLightbox.js"></script>
    <script src="../../public/js/rotuloShared.js"></script>
    <script src="../../public/js/enviarPaquete.js?v=20260418-1"></script>
    <script src="../../public/js/enviarPaqueteMensajero.js?v=20260418-1"></script>
    <script src="../../public/js/mensajeroLayout.js"></script>
</body>
</html>




