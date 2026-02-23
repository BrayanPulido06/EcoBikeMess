<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación - Sistema de Mensajería</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/facturacionCliente.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Estilos para centrar modales perfectamente */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            margin: 0;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        /* Estilos para badges de estado */
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            color: white;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; color: #333; }
        .badge-primary { background-color: #007bff; }
        .badge-info { background-color: #17a2b8; }
        .badge-secondary { background-color: #6c757d; }
        .badge-dark { background-color: #343a40; }
    </style>
</head>
<body>
    <?php include '../layouts/clienteNavbar.php'; ?>
    <?php include '../layouts/clienteSidebar.php'; ?>

    <div class="container" style="margin-left: 250px; margin-top: 60px;">
        <!-- Header -->
        <header class="page-header">
            <div>
                <h1>💰 Facturación</h1>
                <p>Gestión de facturas y pagos</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" id="btnExportarExcel">
                    📊 Exportar Excel
                </button>
                <button class="btn btn-primary" id="btnNuevaFactura">
                    + Nueva Factura
                </button>
            </div>
        </header>

        <!-- Resumen de Estadísticas -->
        <div class="stats-quick">
            <div class="stat-card" style="border-left: 5px solid #e74c3c;">
                <div class="stat-icon" style="background-color: rgba(231, 76, 60, 0.1); color: #e74c3c;">💸</div>
                <div class="stat-info">
                    <span class="stat-label">Saldo a Pagar</span>
                    <span class="stat-value" id="statSaldoPagar" style="color: #e74c3c;">$0</span>
                    <small style="color: #666; font-size: 0.8em;">(Envíos > Recaudos)</small>
                </div>
            </div>
            <div class="stat-card" style="border-left: 5px solid #2ecc71;">
                <div class="stat-icon" style="background-color: rgba(46, 204, 113, 0.1); color: #2ecc71;">💰</div>
                <div class="stat-info">
                    <span class="stat-label">Saldo a Favor</span>
                    <span class="stat-value" id="statSaldoFavor" style="color: #2ecc71;">$0</span>
                    <small style="color: #666; font-size: 0.8em;">(Recaudos > Envíos)</small>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="filters-section">
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="🔍 Buscar por número de factura, cliente..." class="search-input">
            </div>
            
            <div class="filters-grid">
                <div class="form-group">
                    <label>Fecha Desde</label>
                    <input type="date" id="filtroFechaDesde" class="form-control">
                </div>
                <div class="form-group">
                    <label>Fecha Hasta</label>
                    <input type="date" id="filtroFechaHasta" class="form-control">
                </div>
                <div class="form-group">
                    <label>Estado de Pago</label>
                    <select id="filtroEstado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="pagada">Pagada</option>
                        <option value="vencida">Vencida</option>
                        <option value="anulada">Anulada</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Monto</label>
                    <select id="filtroMonto" class="form-control">
                        <option value="">Todos los montos</option>
                        <option value="0-50000">$0 - $50,000</option>
                        <option value="50000-100000">$50,000 - $100,000</option>
                        <option value="100000-500000">$100,000 - $500,000</option>
                        <option value="500000+">Más de $500,000</option>
                    </select>
                </div>
                <div class="form-group align-end">
                    <button class="btn btn-secondary btn-block" id="btnLimpiarFiltros">
                        🔄 Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de Facturas -->
        <div class="table-section">
            <div class="table-header">
                <h2>Listado de Facturas</h2>
                <div class="pagination-info">
                    Mostrando <span id="showingFrom">0</span> - <span id="showingTo">0</span> de <span id="totalResults">0</span> resultados
                </div>
            </div>

            <div class="table-responsive">
                <table id="tablaFacturas">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th class="sortable" data-column="numero">Guía <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-column="fecha">Fecha Ingreso <span class="sort-icon">↕</span></th>
                            <th>Destinatario</th>
                            <th>Dirección</th>
                            <th class="sortable" data-column="valor">Valor Envío <span class="sort-icon">↕</span></th>
                            <th>Recaudo</th>
                            <th>Tipo</th>
                            <th class="sortable" data-column="estado">Estado <span class="sort-icon">↕</span></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaFacturasBody">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="pagination-container">
                <div class="pagination-size">
                    <label>Mostrar:</label>
                    <select id="pageSize">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>por página</span>
                </div>
                <div class="pagination-controls" id="paginationControls">
                    <!-- Se genera dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Modal Detalles de la Factura -->
        <div class="modal" id="modalDetalles">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Detalles de la Factura</h2>
                    <button class="btn-close" id="btnCerrarDetalles">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="detallesFactura">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar Factura -->
        <div class="modal" id="modalEditar">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Editar Factura</h2>
                    <button class="btn-close" id="btnCerrarEditar">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formEditarFactura">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>N° de Factura *</label>
                                <input type="text" id="editNumero" readonly class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Emisión *</label>
                                <input type="date" id="editFechaEmision" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha de Vencimiento *</label>
                                <input type="date" id="editFechaVencimiento" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Cliente *</label>
                                <select id="editCliente" class="form-control" required>
                                    <option value="">Seleccionar cliente</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label>Descripción *</label>
                                <textarea id="editDescripcion" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Subtotal *</label>
                                <input type="number" id="editSubtotal" class="form-control" min="0" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>IVA (%) *</label>
                                <input type="number" id="editIva" class="form-control" min="0" max="100" step="0.01" value="19" required>
                            </div>
                            <div class="form-group">
                                <label>Total</label>
                                <input type="number" id="editTotal" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Estado *</label>
                                <select id="editEstado" class="form-control" required>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="pagada">Pagada</option>
                                    <option value="vencida">Vencida</option>
                                    <option value="anulada">Anulada</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label>Observaciones</label>
                                <textarea id="editObservaciones" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCancelarEditar">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Nueva Factura -->
        <div class="modal" id="modalNueva">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Nueva Factura</h2>
                    <button class="btn-close" id="btnCerrarNueva">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formNuevaFactura">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Fecha de Emisión *</label>
                                <input type="date" id="nuevaFechaEmision" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha de Vencimiento *</label>
                                <input type="date" id="nuevaFechaVencimiento" class="form-control" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Cliente *</label>
                                <select id="nuevaCliente" class="form-control" required>
                                    <option value="">Seleccionar cliente</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label>Descripción *</label>
                                <textarea id="nuevaDescripcion" class="form-control" rows="2" required placeholder="Ej: Servicios de mensajería mes de febrero 2026"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Subtotal *</label>
                                <input type="number" id="nuevaSubtotal" class="form-control" min="0" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>IVA (%) *</label>
                                <input type="number" id="nuevaIva" class="form-control" min="0" max="100" step="0.01" value="19" required>
                            </div>
                            <div class="form-group">
                                <label>Total</label>
                                <input type="number" id="nuevaTotal" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Estado *</label>
                                <select id="nuevaEstado" class="form-control" required>
                                    <option value="pendiente" selected>Pendiente</option>
                                    <option value="pagada">Pagada</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label>Observaciones</label>
                                <textarea id="nuevaObservaciones" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCancelarNueva">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Crear Factura</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Enlace al script JS recién creado -->
    <script src="../../public/js/facturacionCliente.js"></script>
</body>
</html>