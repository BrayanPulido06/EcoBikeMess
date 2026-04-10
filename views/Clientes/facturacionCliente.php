<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array(($_SESSION['user_role'] ?? ''), ['cliente', 'colaborador'], true)) {
    header("Location: ../login.php?error=Debes iniciar sesión.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - EcoBikeMess</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/facturacionCliente.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <link rel="stylesheet" href="../../public/css/clientesTheme.css">
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
            <!-- Header -->
            <div class="page-header">
                <div class="header-left">
                    <h1>📦 Mis Pedidos</h1>
                    <p>Gestiona y consulta todos tus envíos</p>
                </div>
                <div class="header-actions">
                    <button class="btn-action" id="btnNuevoPedido">
                        ➕ Nuevo Pedido
                    </button>
                </div>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="filters-section">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Buscar por número de guía, destinatario...">
                </div>
                
                <div class="filters-row">
                    <select id="filterEstado" class="filter-select">
                        <option value="all">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="en_transito">En Tránsito</option>
                        <option value="entregado">Entregado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>

                    <select id="filterFecha" class="filter-select">
                        <option value="all">Todas las fechas</option>
                        <option value="today">Hoy</option>
                        <option value="week">Esta semana</option>
                        <option value="month" selected>Este mes</option>
                        <option value="year">Este año</option>
                    </select>

                    <select id="filterOrden" class="filter-select">
                        <option value="desc">Más recientes</option>
                        <option value="asc">Más antiguos</option>
                    </select>

                    <button class="btn-filter" id="btnExportPDF" title="Exportar a PDF">
                        📄 PDF
                    </button>
                    <button class="btn-filter" id="btnExportExcel" title="Exportar a Excel">
                        📊 Excel
                    </button>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="quick-stats">
                <div class="quick-stat-card">
                    <div class="stat-icon pending">⏳</div>
                    <div class="stat-content">
                        <span class="stat-value" id="totalPendientes">0</span>
                        <span class="stat-label">Pendientes</span>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon transit">🚚</div>
                    <div class="stat-content">
                        <span class="stat-value" id="totalEnTransito">0</span>
                        <span class="stat-label">En Tránsito</span>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon delivered">✓</div>
                    <div class="stat-content">
                        <span class="stat-value" id="totalEntregados">0</span>
                        <span class="stat-label">Entregados</span>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="stat-icon total">📊</div>
                    <div class="stat-content">
                        <span class="stat-value" id="totalPedidos">0</span>
                        <span class="stat-label">Total</span>
                    </div>
                </div>
            </div>

            <!-- Tabs de Estado -->
            <div class="tabs-container">
                <button class="tab-btn active" data-filter="all">Todos</button>
                <button class="tab-btn" data-filter="pendiente">Pendientes</button>
                <button class="tab-btn" data-filter="en_transito">En Tránsito</button>
                <button class="tab-btn" data-filter="entregado">Entregados</button>
            </div>

            <!-- Lista de Pedidos -->
            <div class="table-responsive">
                <table class="pedidos-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Guía</th>
                            <th>Fecha</th>
                            <th>Destinatario</th>
                            <th>Dirección</th>
                            <th>Estado</th>
                            <th>Costo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="pedidosContainer">
                        <!-- Los pedidos se cargarán aquí dinámicamente como filas -->
                    </tbody>
                </table>
            </div>

            <!-- No Results -->
            <div class="no-results" id="noResults" style="display: none;">
                <div class="no-results-icon">📭</div>
                <h3>No se encontraron pedidos</h3>
                <p>Intenta ajustar los filtros de búsqueda</p>
            </div>

            <!-- Loading -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Cargando pedidos...</p>
            </div>

            <!-- Paginación -->
            <div class="pagination" id="pagination">
                <button class="page-btn" id="btnPrevPage" disabled>← Anterior</button>
                <div class="page-info">
                    Página <span id="currentPage">1</span> de <span id="totalPages">1</span>
                </div>
                <button class="page-btn" id="btnNextPage">Siguiente →</button>
            </div>
        </div>
    </div>

    <!-- Modal Detalle del Pedido -->
    <div class="modal" id="detalleModal">
        <div class="modal-content large">
            <button class="modal-close" id="closeDetalleModal">×</button>
            
            <div class="modal-header">
                <div class="modal-title">
                    <h2>Detalles del Pedido</h2>
                    <div class="guia-badge" id="modal_guia"></div>
                </div>
                <div class="modal-status" id="modal_estado_badge"></div>
            </div>

            <div class="modal-body">
                <!-- Timeline del Pedido -->
                <div class="timeline-section">
                    <h3>📍 Seguimiento</h3>
                    <div class="timeline" id="timeline">
                        <!-- Timeline dinámico -->
                    </div>
                </div>

                <!-- Información del Remitente -->
                <div class="info-section">
                    <h3>📤 Remitente</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Nombre:</span>
                            <span id="modal_remitente_nombre"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Teléfono:</span>
                            <span id="modal_remitente_telefono"></span>
                        </div>
                        <div class="info-item full-width">
                            <span class="label">Dirección:</span>
                            <span id="modal_remitente_direccion"></span>
                        </div>
                    </div>
                </div>

                <!-- Información del Destinatario -->
                <div class="info-section">
                    <h3>📥 Destinatario</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Nombre:</span>
                            <span id="modal_destinatario_nombre"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Teléfono:</span>
                            <span id="modal_destinatario_telefono"></span>
                        </div>
                        <div class="info-item full-width">
                            <span class="label">Dirección:</span>
                            <span id="modal_destinatario_direccion"></span>
                        </div>
                    </div>
                </div>

                <!-- Información del Paquete -->
                <div class="info-section">
                    <h3>📦 Paquete</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Descripción:</span>
                            <span id="modal_descripcion"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Peso:</span>
                            <span id="modal_peso"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Tipo:</span>
                            <span id="modal_tipo"></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Costo:</span>
                            <span id="modal_costo"></span>
                        </div>
                    </div>
                </div>

                <!-- Comprobante de Entrega (solo si está entregado) -->
                <div class="comprobante-section" id="comprobanteSection" style="display: none;">
                    <h3>✓ Comprobante de Entrega</h3>
                    <div class="comprobante-content">
                        <div class="comprobante-info">
                            <div class="info-row">
                                <span class="label">Recibió:</span>
                                <span id="modal_quien_recibio"></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Parentesco/Cargo:</span>
                                <span id="modal_parentesco"></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Fecha de Entrega:</span>
                                <span id="modal_fecha_entrega"></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Recaudo:</span>
                                <span id="modal_recaudo"></span>
                            </div>
                            <div class="info-row full">
                                <span class="label">Observaciones:</span>
                                <span id="modal_observaciones"></span>
                            </div>
                        </div>
                        <div class="comprobante-foto">
                            <img id="modal_foto_entrega" src="" alt="Evidencia de entrega">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn-secondary" id="btnRastrear">
                    🗺️ Rastrear en Mapa
                </button>
                <button class="btn-secondary" id="btnDescargarComprobante" style="display: none;">
                    ⬇️ Descargar Comprobante
                </button>
                <button class="btn-secondary" id="btnImprimirComprobante" style="display: none;">
                    🖨️ Imprimir
                </button>
                <button class="btn-danger" id="btnCancelar" style="display: none;">
                    ❌ Cancelar Pedido
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Rótulo (Guía) -->
    <div class="modal" id="rotuloModal">
        <div class="modal-content" style="max-width: 500px;">
            <button class="modal-close" id="closeRotuloModal">×</button>
            <div class="modal-header">
                <h2>🏷️ Rótulo de Envío</h2>
            </div>
            <div class="modal-body">
                <div id="rotuloPreview" style="background: white; padding: 20px; border: 1px solid #ccc; font-family: Arial, sans-serif; color: #333;">
                    <div class="rotulo-scale">
                        <table style="width: 100%; border-bottom: 2px solid #5cb85c; padding-bottom: 6px;">
                            <tr>
                                <td colspan="2">
                                    <div style="display: flex; align-items: center; gap: 10px; justify-content: center; text-align: center;">
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
                                    <div style="font-size: 13px; font-weight: 800; color: #000000;">NUM GUÍA: <span id="rotulo_guia_num" style="font-size: 19px; font-weight: 800; color: #1f2a37;">EBM-XXXXXX</span></div>
                                </td>
                            </tr>
                        </table>

                        <table style="width: 100%; margin-top: 4px; font-size: 12px;">
                            <tr>
                                <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                    <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📥 Destinatario</h3>
                                    <p><strong>Dirección:</strong> <span id="rotulo_dir_destinatario" class="rotulo-text-lg bold"></span></p>
                                    <p><strong>Nombre:</strong> <span id="rotulo_destinatario" class="rotulo-text-lg bold"></span></p>
                                    <p><strong>Teléfono:</strong> <span id="rotulo_tel_destinatario" class="rotulo-text-lg bold"></span></p>
                                    <p><strong>Observaciones:</strong> <span id="rotulo_observaciones" class="rotulo-text-lg bold"></span></p>
                                </td>
                                <td style="width: 4%;"></td>
                                <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                    <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📤 Remitente</h3>
                                    <p><strong>Tienda:</strong> <span id="rotulo_remitente" class="rotulo-text-lg bold"></span></p>
                                </td>
                            </tr>
                        </table>

                        <table style="width: 100%; margin-top: 4px; padding-top: 0;">
                            <tr>
                                <td style="width: 60%; vertical-align: top; font-size: 12px;">
                                    <div class="rotulo-card" style="border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📦 Detalles del Paquete</h3>
                                        <p><strong>Cambios por recoger:</strong> <span id="rotulo_cambios" class="rotulo-text-lg bold">No</span></p>
                                    </div>
                                    <div style="margin-top: 6px;">
                                        <h3 style="margin: 0 0 6px; font-size: 15px;">💰 Total a Cobrar</h3>
                                        <div id="rotulo_financiero"></div>
                                    </div>
                                </td>
                                <td style="width: 40%; text-align: right; vertical-align: top;">
                                    <div id="rotulo_qr_code" style="display: inline-block; width: 220px; height: 220px; margin-right: 6mm; margin-top: -7mm;"></div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-primary" id="btnDownloadRotulo">
                    ⬇️ Descargar PDF
                </button>
            </div>
        </div>
    </div>

    <!-- jsPDF para generar PDFs -->
    <script src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="../../public/js/facturacionCliente.js"></script>
</body>
</html>
