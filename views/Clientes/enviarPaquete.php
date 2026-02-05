<?php
session_start();

// Redirigir si no es cliente
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'cliente' && $_SESSION['user_role'] !== 'colaborador')) {
    header("Location: ../login.php");
    exit();
}

// Incluir conexión y obtener dirección principal del cliente
require_once '../../models/conexionGlobal.php';
$direccion_principal = '';
$telefono_usuario = $_SESSION['user_phone'] ?? '';

try {
    $conn = conexionDB();
    if ($conn) {
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'colaborador') {
            // Si es colaborador, obtenemos la dirección de la tienda (cliente) y el teléfono del colaborador
            $sql = "SELECT c.direccion_principal, u.telefono 
                    FROM colaboradores_cliente cc
                    JOIN clientes c ON cc.cliente_id = c.id
                    JOIN usuarios u ON cc.usuario_id = u.id
                    WHERE cc.usuario_id = :usuario_id";
        } else {
            // Si es cliente, obtenemos sus datos directos
            $sql = "SELECT c.direccion_principal, u.telefono 
                    FROM clientes c 
                    JOIN usuarios u ON c.usuario_id = u.id 
                    WHERE c.usuario_id = :usuario_id";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute([':usuario_id' => $_SESSION['user_id']]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($info) {
            $direccion_principal = $info['direccion_principal'] ?? '';
            $telefono_usuario = $info['telefono'] ?? $telefono_usuario;
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener datos del cliente: " . $e->getMessage());
}

// Preparar datos del usuario para autocompletar
$remitente_data = [
    'nombre_completo' => ($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_lastname'] ?? ''),
    'telefono' => $telefono_usuario,
    'correo' => $_SESSION['user_email'] ?? '',
    'direccion' => $direccion_principal ?? ''
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Paquete - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/enviarPaquete.css">
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
            width: 120px !important;
            height: 120px !important;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        #qrcode canvas {
            width: 100%;
            height: 100%;
        }
        .qr-code-container small {
            font-size: 0.8rem; color: #777;
        }
        /* Estilos para carga masiva */
        .bulk-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .bulk-table th, .bulk-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; font-size: 0.9rem; }
        .bulk-table th { background: #f8f9fa; font-weight: 600; color: #2d3e50; }
        .status-pending { color: #f0ad4e; font-weight: bold; }
        .status-success { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../layouts/clienteSidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <?php include '../layouts/clienteNavbar.php'; ?>

        <!-- Content -->
        <div class="content-container">
            <!-- Mensajes de Validación y Feedback -->
            <?php if (isset($_GET['error'])): ?>
                <div style="background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #ef9a9a; margin-top: 100px;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'envio_creado'): ?>
                <div style="background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #a5d6a7; margin-top: 100px;">
                    <strong>¡Éxito!</strong> Su envío ha sido registrado correctamente.
                    <?php if(isset($_GET['guia'])): ?>
                        <br>Número de Guía: <strong><?php echo htmlspecialchars($_GET['guia']); ?></strong>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div class="header-left">
                    <h1>Crear Nuevo Envío</h1>
                    <p>Complete la información para generar su guía de envío</p>
                </div>
                <div class="header-right">
                    <a href="misPedidos.php" class="btn-secondary">
                        <span>📋</span> Ver Mis Pedidos
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
            <form id="envioForm" class="envio-form" action="../../controller/enviarPaqueteController.php" method="POST" novalidate>
                
                <!-- PASO 1: DATOS DEL REMITENTE -->
                <div class="form-step active" data-step="1">
                    <div class="card">
                        <div class="card-header">
                            <h2>📤 Datos del Remitente</h2>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button type="button" class="btn-text" id="autoFillRemitente">
                                    Usar mis datos
                                </button>
                                <input type="file" id="excelUpload" accept=".xlsx, .xls" style="display: none;">
                                <button type="button" class="btn-text" id="btnUploadExcel" style="color: #2196f3;">
                                    📂 Cargar Excel
                                </button>
                                <button type="button" class="btn-text" id="btnDownloadTemplate" style="color: #28a745;">
                                    📥 Descargar Plantilla
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
                                    <label for="remitente_telefono">Teléfono *</label>
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
                                <label for="remitente_direccion">Dirección de Origen Completa *</label>
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
                            <h2>📥 Datos del Destinatario</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="destinatario_nombre">Nombre Completo *</label>
                                    <input type="text" id="destinatario_nombre" name="destinatario_nombre" required>
                                    <span class="error-message"></span>
                                </div>
                                <div class="form-group">
                                    <label for="destinatario_telefono">Teléfono *</label>
                                    <input type="tel" id="destinatario_telefono" name="destinatario_telefono" placeholder="300 123 4567" required>
                                    <span class="error-message"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="destinatario_direccion">Dirección de Destino Completa *</label>
                                <textarea id="destinatario_direccion" name="destinatario_direccion" rows="3" placeholder="Ej: Carrera 45 #67-89, Casa 202, Barrio Norte" required></textarea>
                                <span class="error-message"></span>
                            </div>
                            <div class="form-group">
                                <label for="instrucciones_entrega">Instrucciones Especiales de Entrega</label>
                                <textarea id="instrucciones_entrega" name="instrucciones_entrega" rows="3" placeholder="Ej: Tocar el timbre 2 veces, entregar en portería, etc."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="tiene_recaudo" name="tiene_recaudo">
                                    <span>Este envío tiene recaudo (pago contra entrega)</span>
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
                            <h2>📦 Información del Paquete</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="descripcion_contenido">Descripción del Contenido *</label>
                                <textarea id="descripcion_contenido" name="descripcion_contenido" rows="2" placeholder="Ej: Ropa, documentos, accesorios, etc." required></textarea>
                                <span class="error-message"></span>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="peso_paquete">Peso del Paquete (kg) *</label>
                                    <input type="number" id="peso_paquete" name="peso_paquete" step="0.1" min="0.1" max="15" placeholder="0.0" required>
                                    <small>Máximo 15 kg</small>
                                    <span class="error-message"></span>
                                </div>
                                <div class="form-group">
                                    <label for="tipo_paquete">Tipo de Paquete *</label>
                                    <select id="tipo_paquete" name="tipo_paquete" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="normal">Normal</option>
                                        <option value="fragil">Frágil (+$2.000)</option>
                                        <option value="urgente">Urgente (+$5.000)</option>
                                    </select>
                                    <span class="error-message"></span>
                                </div>
                            </div>
                            <div class="dimensions-group">
                                <label>Dimensiones del Paquete (cm) *</label>
                                <div class="form-row">
                                    <div class="form-group">
                                        <input type="number" id="dimension_largo" name="dimension_largo" placeholder="Largo" min="1" required>
                                        <span class="error-message"></span>
                                    </div>
                                    <div class="form-group">
                                        <input type="number" id="dimension_ancho" name="dimension_ancho" placeholder="Ancho" min="1" required>
                                        <span class="error-message"></span>
                                    </div>
                                    <div class="form-group">
                                        <input type="number" id="dimension_alto" name="dimension_alto" placeholder="Alto" min="1" required>
                                        <span class="error-message"></span>
                                    </div>
                                </div>
                                <small>Dimensiones máximas: 50 x 40 x 30 cm</small>
                            </div>
                        </div>
                    </div>

                    <!-- Cálculo de Costo -->
                    <div class="card cost-card">
                        <div class="card-header">
                            <h2>💰 Cálculo del Costo</h2>
                        </div>
                        <div class="card-body">
                            <div class="cost-breakdown">
                                <div class="cost-item">
                                    <span>Costo base por zona:</span>
                                    <span id="costoBase">$0</span>
                                </div>
                                <div class="cost-item">
                                    <span>Recargo por peso:</span>
                                    <span id="recargoPeso">$0</span>
                                </div>
                                <div class="cost-item">
                                    <span>Recargo por tipo:</span>
                                    <span id="recargoTipo">$0</span>
                                </div>
                                <div class="cost-item">
                                    <span>Recaudo (si aplica):</span>
                                    <span id="valorRecaudoDisplay">$0</span>
                                </div>
                                <div class="cost-divider"></div>
                                <div class="cost-item total">
                                    <span>Total a pagar:</span>
                                    <span id="costoTotal">$0</span>
                                </div>
                                <!-- Campo oculto para enviar el costo total -->
                                <input type="hidden" name="costo_total" id="costoTotalHidden" value="0">
                                <!-- Campo oculto para enviar el número de guía generado en JS -->
                                <input type="hidden" name="numero_guia" id="numeroGuiaHidden">
                            </div>
                            <button type="button" class="btn-text" id="calcularCosto" style="display: none;">
                                🔄 Calcular costo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PASO 4: CONFIRMACIÓN -->
                <div class="form-step" data-step="4">
                    <div class="card confirmation-card">
                        <div class="card-header">
                            <h2>✓ Confirmar Envío</h2>
                            <button type="button" class="btn-secondary" id="btnDownloadPDF">
                                <span>📄</span> Descargar Guía (PDF)
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="confirmation-section">
                                <h3>📤 Remitente</h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Nombre:</span>
                                        <span id="confirm_remitente_nombre"></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Teléfono:</span>
                                        <span id="confirm_remitente_telefono"></span>
                                    </div>
                                    <div class="info-item full-width">
                                        <span class="info-label">Dirección:</span>
                                        <span id="confirm_remitente_direccion"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="confirmation-section">
                                <h3>📥 Destinatario</h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Nombre:</span>
                                        <span id="confirm_destinatario_nombre"></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Teléfono:</span>
                                        <span id="confirm_destinatario_telefono"></span>
                                    </div>
                                    <div class="info-item full-width">
                                        <span class="info-label">Dirección:</span>
                                        <span id="confirm_destinatario_direccion"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="confirmation-section">
                                <h3>📦 Paquete</h3>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Descripción:</span>
                                        <span id="confirm_descripcion"></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Peso:</span>
                                        <span id="confirm_peso"></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Dimensiones:</span>
                                        <span id="confirm_dimensiones"></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Tipo:</span>
                                        <span id="confirm_tipo"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="confirmation-total">
                                <h3>Costo Total del Envío</h3>
                                <div class="total-amount" id="confirm_total">$0</div>
                                <!-- NUEVO: Info de pago -->
                                <div class="info-item" style="margin-top: 15px; font-size: 0.9rem;">
                                    <span class="info-label">Método de Pago:</span>
                                    <span id="confirm_metodo_pago" style="font-weight: bold;"></span>
                                </div>
                                <div class="info-item" id="confirm_recaudo_container" style="display: none; font-size: 0.9rem;">
                                    <span class="info-label">Valor a Recaudar:</span>
                                    <span id="confirm_valor_recaudo" style="font-weight: bold;"></span>
                                </div>
                                <p class="total-note">El pago se realizará contra entrega o según el método seleccionado</p>
                            </div>

                            <div class="guia-section">
                                <div class="guia-preview">
                                    <div class="guia-icon">🎫</div>
                                    <div class="guia-info">
                                        <span class="guia-label">Número de Guía:</span>
                                        <span class="guia-number" id="numeroGuia">ECO-2024-XXXXX</span>
                                    </div>
                                </div>
                                <div class="qr-code-container">
                                    <div id="qrcode"></div>
                                    <small>Escanea para ver detalles</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Navegación -->
                <div class="form-navigation">
                    <button type="button" class="btn-secondary" id="btnPrevious" style="display: none;">
                        ← Anterior
                    </button>
                    <div class="nav-spacer"></div>
                    <button type="button" class="btn-primary" id="btnNext">
                        Siguiente →
                    </button>
                    <button type="submit" class="btn-success" id="btnSubmit" style="display: none;">
                        ✓ Confirmar Envío
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contenedor para Carga Masiva (Oculto por defecto) -->
    <div id="bulkPreviewContainer" class="content-container" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h2>📦 Carga Masiva de Envíos</h2>
                <div class="header-actions">
                    <button type="button" class="btn-text" id="btnCancelBulk" style="color: #dc3545;">❌ Cancelar</button>
                    <button type="button" class="btn-primary" id="btnProcessBulk">🚀 Procesar Todos</button>
                </div>
            </div>
            <div class="card-body">
                <p>Se han detectado múltiples envíos. Revise la información antes de procesar.</p>
                <div class="table-responsive">
                    <table class="bulk-table">
                        <thead>
                            <tr>
                                <th>Destinatario</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
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

    <script>
        // Pasar datos de PHP a JavaScript para autocompletar
        window.remitenteData = <?php echo json_encode($remitente_data, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <!-- Librerías para QR con logo y para generar PDF -->
    <script src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script src="../../public/js/enviarPaquete.js"></script>
</body>
</html>