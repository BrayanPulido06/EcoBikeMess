<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Historial - EcoBikeMess</title>
    
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/historialMensajero.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
</head>
<body>
    <!-- Header Móvil -->
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1>🚴 EcoBikeMess</h1>
            <p class="user-name">Historial de Entregas</p>
        </div>
    </header>

    <!-- Menu Lateral -->
    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <!-- Header -->
            <header class="page-header">
                <div>
                    <h1>📦 Historial de Entregas</h1>
                    <p>Comprobantes y recaudos del mensajero</p>
                </div>
            </header>

            <!-- Resumen -->
            <div class="stats-quick">
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-label">Entregas Totales</span>
                        <span class="stat-value" id="totalHistorico">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <span class="stat-label">Recaudo Total</span>
                        <span class="stat-value" id="totalRecaudoHistorico">$0</span>
                    </div>
                </div>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="filters-section">
                <div class="search-container">
                    <input type="text" id="searchHistorial" placeholder="🔍 Buscar por guía o destinatario..." class="search-input">
                </div>

                <div class="filters-grid">
                    <div class="filtros">
                        <button class="filtro-btn activo" data-filtro="todos">Todos</button>
                        <button class="filtro-btn" data-filtro="hoy">Hoy</button>
                        <button class="filtro-btn" data-filtro="semana">Esta Semana</button>
                        <button class="filtro-btn" data-filtro="mes">Este Mes</button>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div id="vistaLista" class="table-section">
                <div class="table-header">
                    <h2>Listado de Entregas</h2>
                    <div class="pagination-info">
                        Mostrando <span id="showingFrom">0</span> - <span id="showingTo">0</span> de <span id="totalResults">0</span> resultados
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="tablaHistorial">
                        <thead>
                            <tr>
                                <th>Guía</th>
                                <th>Destinatario</th>
                                <th>Dirección</th>
                                <th>Fecha Entrega</th>
                                <th>Recaudo</th>
                                <th>Recibió</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaHistorialBody">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Cards Mobile -->
                <div id="cardsHistorial" class="cards-historial">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
        </div>
    </main>

    <!-- Vista Detalle (Solo lectura) -->
    <div id="vistaDetalle" class="vista-detalle oculto">
        <div class="detalle-header">
            <button id="btnVolverDetalle" class="btn-volver">← Volver</button>
            <h2 id="detalleGuia">Guía #</h2>
        </div>

        <div class="detalle-contenido">
            <section class="seccion-detalle">
                <div class="estado-badge-grande">
                    <span class="badge-grande entregado">Entregado</span>
                </div>
            </section>

            <section class="seccion-detalle">
                <h3>✅ Información de Entrega</h3>
                <div class="info-item">
                    <span class="info-label">Recibió:</span>
                    <span id="entregaRecibio"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Parentesco:</span>
                    <span id="entregaParentesco"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Documento:</span>
                    <span id="entregaDocumento"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha y Hora:</span>
                    <span id="entregaFecha"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Recaudo:</span>
                    <span id="entregaRecaudo" class="valor-destacado"></span>
                </div>
            </section>

            <section class="seccion-detalle">
                <h3>📦 Detalles del Paquete</h3>
                <div class="info-item">
                    <span class="info-label">Destinatario Original:</span>
                    <span id="detalleDestinatario"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dirección:</span>
                    <p id="detalleDireccion" class="direccion-completa"></p>
                </div>
                <div class="info-item">
                    <span class="info-label">Contenido:</span>
                    <span id="detalleContenido"></span>
                </div>
            </section>
        </div>
    </div>

    <script src="../../public/js/mensajeroLayout.js"></script>
    <script src="../../public/js/historialMensajero.js"></script>
</body>
</html>
