<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobantes - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/comprobantes.css">
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
                    <h1>📄 Comprobantes de Entrega</h1>
                    <p>Consulta y descarga tus comprobantes de envío</p>
                </div>
                <div class="header-actions">
                    <button class="btn-icon" id="btnRefresh" title="Actualizar">
                        🔄
                    </button>
                </div>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="filters-section">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Buscar por número de guía, destinatario...">
                </div>
                
                <div class="filters-group">
                    <select id="filterPeriodo" class="filter-select">
                        <option value="all">Todos los períodos</option>
                        <option value="today">Hoy</option>
                        <option value="week">Esta semana</option>
                        <option value="month" selected>Este mes</option>
                        <option value="year">Este año</option>
                        <option value="custom">Personalizado</option>
                    </select>

                    <select id="filterEstado" class="filter-select">
                        <option value="all">Todos los estados</option>
                        <option value="entregado">Entregados</option>
                        <option value="pendiente">Pendientes</option>
                    </select>

                    <button class="btn-filter" id="btnApplyFilters">
                        Aplicar Filtros
                    </button>
                </div>
            </div>

            <!-- Selector de Fecha Personalizado -->
            <div class="custom-date-range" id="customDateRange" style="display: none;">
                <div class="date-inputs">
                    <div class="date-group">
                        <label>Desde:</label>
                        <input type="date" id="dateFrom">
                    </div>
                    <div class="date-group">
                        <label>Hasta:</label>
                        <input type="date" id="dateTo">
                    </div>
                    <button class="btn-primary" id="btnApplyDateRange">Aplicar</button>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <span class="stat-value" id="totalComprobantes">0</span>
                        <span class="stat-label">Total Comprobantes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✓</div>
                    <div class="stat-info">
                        <span class="stat-value" id="entregadosMes">0</span>
                        <span class="stat-label">Entregados Este Mes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <span class="stat-value" id="recaudosMes">$0</span>
                        <span class="stat-label">Recaudos del Mes</span>
                    </div>
                </div>
            </div>

            <!-- Lista de Comprobantes -->
            <div class="comprobantes-container">
                <div class="comprobantes-header">
                    <h2>Comprobantes Disponibles</h2>
                    <div class="view-toggle">
                        <button class="toggle-btn active" data-view="grid" title="Vista en cuadrícula">
                            ▦
                        </button>
                        <button class="toggle-btn" data-view="list" title="Vista en lista">
                            ☰
                        </button>
                    </div>
                </div>

                <!-- Vista Grid -->
                <div class="comprobantes-grid active" id="comprobantesGrid">
                    <!-- Los comprobantes se cargarán aquí dinámicamente -->
                </div>

                <!-- Vista Lista -->
                <div class="comprobantes-list" id="comprobantesList">
                    <table class="comprobantes-table">
                        <thead>
                            <tr>
                                <th>Guía</th>
                                <th>Destinatario</th>
                                <th>Quien Recibió</th>
                                <th>Fecha Entrega</th>
                                <th>Recaudo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="comprobantesTableBody">
                            <!-- Se llenará dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje cuando no hay resultados -->
                <div class="no-results" id="noResults" style="display: none;">
                    <div class="no-results-icon">📭</div>
                    <h3>No se encontraron comprobantes</h3>
                    <p>Intenta ajustar los filtros de búsqueda</p>
                </div>

                <!-- Loading -->
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Cargando comprobantes...</p>
                </div>
            </div>

            <!-- Paginación -->
            <div class="pagination" id="pagination">
                <button class="page-btn" id="btnPrevPage" disabled>← Anterior</button>
                <div class="page-numbers" id="pageNumbers"></div>
                <button class="page-btn" id="btnNextPage">Siguiente →</button>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Comprobante Completo -->
    <div class="modal" id="comprobanteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Comprobante de Entrega</h2>
                <button class="modal-close" id="closeModal">×</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Contenido del comprobante -->
                <div class="comprobante-detail">
                    <div class="comprobante-section">
                        <div class="logo-section">
                            <h1>🚴 EcoBikeMess</h1>
                            <p>Mensajería Ecológica</p>
                        </div>
                        <div class="guia-section">
                            <span class="guia-label">Número de Guía</span>
                            <span class="guia-number" id="modal_guia"></span>
                        </div>
                    </div>

                    <div class="comprobante-section">
                        <h3>📤 Datos del Cliente</h3>
                        <div class="info-row">
                            <span class="label">Nombre:</span>
                            <span id="modal_cliente"></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Dirección:</span>
                            <span id="modal_direccion"></span>
                        </div>
                    </div>

                    <div class="comprobante-section">
                        <h3>📥 Datos de Entrega</h3>
                        <div class="info-row">
                            <span class="label">Recibió:</span>
                            <span id="modal_quien_recibio"></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Parentesco/Cargo:</span>
                            <span id="modal_parentesco"></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Fecha y Hora:</span>
                            <span id="modal_fecha_entrega"></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Recaudo:</span>
                            <span id="modal_recaudo"></span>
                        </div>
                    </div>

                    <div class="comprobante-section">
                        <h3>📸 Evidencia Fotográfica</h3>
                        <div class="foto-container">
                            <img id="modal_foto" src="" alt="Evidencia de entrega">
                        </div>
                    </div>

                    <div class="comprobante-section">
                        <h3>📝 Observaciones</h3>
                        <p class="observaciones" id="modal_observaciones"></p>
                    </div>

                    <div class="comprobante-footer">
                        <p>Este comprobante fue generado automáticamente por el sistema EcoBikeMess</p>
                        <p>Fecha de generación: <span id="modal_fecha_generacion"></span></p>
                        <p class="validity">Válido por 1 año desde la fecha de entrega</p>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" id="btnPrint">
                    🖨️ Imprimir
                </button>
                <button class="btn-primary" id="btnDownloadPDF">
                    ⬇️ Descargar PDF
                </button>
            </div>
        </div>
    </div>

    <!-- jsPDF para generar PDFs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="../../public/js/comprobantes.js"></script>
</body>
</html>