<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/facturacionCliente.css">
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
                <div id="rotuloPreview" class="rotulo-card">
                    <div class="rotulo-header">
                        <div class="rotulo-brand">
                            <span class="brand-name">EcoBikeMess</span>
                            <span class="brand-slogan">Mensajería Ecológica</span>
                        </div>
                        <div class="rotulo-guia">
                            <span class="guia-label">GUÍA DE ENVÍO</span>
                            <span class="guia-value" id="rotulo_guia_num">ECO-XXXXXX</span>
                        </div>
                    </div>
                    
                    <div class="rotulo-main">
                        <div class="rotulo-qr" id="rotulo_qr_code">
                            <!-- QR Code Here -->
                        </div>
                        <div class="rotulo-dates">
                            <div class="date-group">
                                <label>Fecha:</label>
                                <span id="rotulo_fecha_creacion">DD/MM/YYYY</span>
                            </div>
                            <div class="date-group">
                                <label>Tipo:</label>
                                <span id="rotulo_tipo_paquete">NORMAL</span>
                            </div>
                        </div>
                    </div>

                    <div class="rotulo-addresses">
                        <div class="address-block from">
                            <div class="block-label">REMITENTE</div>
                            <div class="block-content">
                                <strong id="rotulo_remitente">Nombre Remitente</strong>
                                <p id="rotulo_dir_remitente">Dirección completa del remitente</p>
                                <p id="rotulo_tel_remitente">Tel: 3000000000</p>
                            </div>
                        </div>
                        <div class="address-arrow">⬇</div>
                        <div class="address-block to">
                            <div class="block-label">DESTINATARIO</div>
                            <div class="block-content">
                                <strong id="rotulo_destinatario">Nombre Destinatario</strong>
                                <p id="rotulo_dir_destinatario">Dirección completa del destinatario</p>
                                <p id="rotulo_tel_destinatario">Tel: 3000000000</p>
                            </div>
                        </div>
                    </div>

                    <div class="rotulo-footer">
                        <div class="footer-info">
                            <span>Peso: <b id="rotulo_peso">0.0 kg</b></span>
                            <span>Piezas: <b>1/1</b></span>
                        </div>
                        <div class="footer-note" id="rotulo_notas">
                            Sin observaciones
                        </div>
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


