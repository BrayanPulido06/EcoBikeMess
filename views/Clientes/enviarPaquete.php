<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Paquete - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/enviarPaquete.css">
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
            <form id="envioForm" class="envio-form">
                
                <!-- PASO 1: DATOS DEL REMITENTE -->
                <div class="form-step active" data-step="1">
                    <div class="card">
                        <div class="card-header">
                            <h2>📤 Datos del Remitente</h2>
                            <button type="button" class="btn-text" id="autoFillRemitente">
                                Usar mis datos
                            </button>
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
                                    <span class="input-icon">$</span>
                                    <input type="number" id="valor_recaudo" name="valor_recaudo" placeholder="0" min="0">
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
                            </div>
                            <button type="button" class="btn-text" id="calcularCosto">
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
                                <p class="total-note">El pago se realizará contra entrega o según el método seleccionado</p>
                            </div>

                            <div class="guia-preview">
                                <div class="guia-icon">🎫</div>
                                <div class="guia-info">
                                    <span class="guia-label">Número de Guía:</span>
                                    <span class="guia-number" id="numeroGuia">ECO-2024-XXXXX</span>
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

    <script src="../../public/js/enviarPaquete.js"></script>
</body>
</html>