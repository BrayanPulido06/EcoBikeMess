<?php
session_start();
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin' && ($_SESSION['user_role'] ?? '') !== 'administrador')) {
    header("Location: ../login.php?error=Debes iniciar sesión.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Paquetes - Sistema de Mensajería</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/paquetesAdmin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Estilos para centrar modales perfectamente */
        .modal {
            display: none; /* Oculto por defecto */
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0,0,0,0.5);
            /* Flexbox para centrado */
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            margin: 0; /* Quitar márgenes automáticos */
            max-height: 90vh; /* Evitar que sea más alto que la pantalla */
            overflow-y: auto; /* Scroll interno si es necesario */
        }
        /* Estilos para la lista de mensajeros con búsqueda */
        .mensajeros-list-container {
            border: 1px solid #ced4da;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            margin-top: 5px;
        }
        .mensajero-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .mensajero-item:hover { background-color: #f8f9fa; }
        .mensajero-item.selected { background-color: #e8f0fe; color: #0d6efd; font-weight: bold; }
        
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
        .badge-success { background-color: #28a745; } /* Verde - Entregado */
        .badge-danger { background-color: #dc3545; }  /* Rojo - Cancelado */
        .badge-warning { background-color: #ffc107; color: #333; } /* Amarillo - Pendiente */
        .badge-primary { background-color: #007bff; } /* Azul - En tránsito */
        .badge-info { background-color: #17a2b8; }    /* Cian - Asignado */
        .badge-secondary { background-color: #6c757d; } /* Gris - Default */
        .badge-dark { background-color: #343a40; }    /* Oscuro - Devuelto */
    </style>
</head>
<body>
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <div class="container" style="margin-left: 250px; margin-top: 60px;">
        <!-- Header -->
        <header class="page-header">
            <div>
                <h1>📦 Gestión de Paquetes</h1>
                <p>Administrar todos los paquetes del sistema</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" id="btnExportarExcel">
                    📊 Exportar Excel
                </button>
                <button class="btn btn-primary" id="btnNuevoPaquete">
                    + Nuevo Paquete
                </button>
            </div>
        </header>

        <!-- Filtros y Búsqueda -->
        <div class="filters-section">
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="🔍 Buscar por número de guía, cliente o destinatario..." class="search-input">
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
                    <label>Cliente</label>
                    <select id="filtroCliente" class="form-control">
                        <option value="">Todos los clientes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select id="filtroEstado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="asignado">Asignado</option>
                        <option value="en_transito">En Tránsito</option>
                        <option value="entregado">Entregado</option>
                        <option value="devuelto">Devuelto</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mensajero</label>
                    <select id="filtroMensajero" class="form-control">
                        <option value="">Todos los mensajeros</option>
                    </select>
                </div>
                <div class="form-group align-end">
                    <button class="btn btn-secondary btn-block" id="btnLimpiarFiltros">
                        🔄 Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de Paquetes -->
        <div class="table-section">
            <div class="table-header">
                <h2>Listado de Paquetes</h2>
                <div class="pagination-info">
                    Mostrando <span id="showingFrom">0</span> - <span id="showingTo">0</span> de <span id="totalResults">0</span> resultados
                </div>
            </div>

            <div class="table-responsive">
                <table id="tablaPaquetes">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th class="sortable" data-column="guia">N° Guía <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-column="fecha">Fecha/Hora <span class="sort-icon">↕</span></th>
                            <th>Remitente</th>
                            <th>Destinatario</th>
                            <th>Dirección Entrega</th>
                            <th>Recolección</th>
                            <th>Estado Recolección</th>
                            <th>Entrega</th>
                            <th>Estado Entrega</th>
                            <th>Recaudo</th>
                            <th>Valor Envío Agregado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPaquetesBody">
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

        <!-- Modal Detalles del Paquete -->
        <div class="modal" id="modalDetalles">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Detalles del Paquete</h2>
                    <button class="btn-close" id="btnCerrarDetalles">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="detallesPaquete">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar Paquete -->
        <div class="modal" id="modalEditar">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Editar Paquete</h2>
                    <button class="btn-close" id="btnCerrarEditar">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPaquete">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>N° de Guía *</label>
                                <input type="text" id="editGuia" readonly class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Cliente/Remitente *</label>
                                <input type="text" id="editRemitente" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Destinatario *</label>
                                <input type="text" id="editDestinatario" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Teléfono Destinatario *</label>
                                <input type="tel" id="editTelefono" class="form-control" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Dirección de Entrega *</label>
                                <textarea id="editDireccion" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Zona *</label>
                                <select id="editZona" class="form-control" required>
                                    <option value="norte">Norte</option>
                                    <option value="sur">Sur</option>
                                    <option value="este">Este</option>
                                    <option value="oeste">Oeste</option>
                                    <option value="centro">Centro</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tipo de Paquete *</label>
                                <select id="editTipo" class="form-control" required>
                                    <option value="documento">Documento</option>
                                    <option value="paquete">Paquete</option>
                                    <option value="sobre">Sobre</option>
                                    <option value="caja">Caja</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Valor del Envío *</label>
                                <input type="number" id="editValor" class="form-control" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>Peso (kg)</label>
                                <input type="number" id="editPeso" class="form-control" step="0.1" min="0">
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

        <!-- Modal Asignar Mensajero -->
        <div class="modal" id="modalAsignar">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Asignar Mensajero</h2>
                    <button class="btn-close" id="btnCerrarAsignar">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formAsignarMensajero">
                        <div class="form-group">
                            <label>Paquete</label>
                            <input type="text" id="asignarGuia" readonly class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Seleccionar Mensajero *</label>
                            <input type="text" id="buscarMensajeroInput" class="form-control" placeholder="Escriba para buscar mensajero...">
                            <div id="listaMensajeros" class="mensajeros-list-container"></div>
                            <input type="hidden" id="asignarMensajero" name="mensajero_id" required>
                        </div>
                        <div id="mensajeroInfo" class="mensajero-info-card"></div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCancelarAsignar">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Asignar Mensajero</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script src="../../public/js/paquetesAdmin.js"></script>
</body>
</html>
