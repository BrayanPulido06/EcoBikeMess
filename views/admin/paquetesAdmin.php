<?php
require_once __DIR__ . '/../../includes/paths.php';
session_start();
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin' && ($_SESSION['user_role'] ?? '') !== 'administrador')) {
    redirect_route('login', ['error' => 'Debes iniciar sesión.']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars(app_url('/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        window.APP_BASE_PATH = <?php echo json_encode(app_url(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <title>Gestión de Paquetes - Sistema de Mensajería</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/paquetesAdmin.css?v=20260523-2">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
        .modal-nuevo-paquete .modal-content {
            max-width: 760px;
            width: min(94vw, 760px);
        }
        .nuevo-paquete-help {
            margin: 0 0 18px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            font-weight: 600;
        }
        .nuevo-guia-preview {
            margin: 0 0 18px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            font-size: 1.05rem;
            text-align: center;
        }
        .form-check-inline {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
        }
        .form-check-inline input {
            width: 18px;
            height: 18px;
            accent-color: #28a745;
        }
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

        /* Rótulo 10x10 cm */
        #rotuloPreview {
            width: 100mm;
            height: 100mm;
            padding: 1mm 2mm 2mm 3mm !important;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
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
            text-align: center;
        }
        #rotuloPreview .rotulo-scale .rotulo-top-grid {
            width: 100%;
            margin-top: 2px;
            font-size: 12px;
            table-layout: fixed;
        }
        #rotuloPreview .rotulo-scale .rotulo-remitente-card {
            height: 100%;
        }
        #rotuloPreview .rotulo-scale .rotulo-remitente-body {
            min-height: 0;
        }
        #rotuloPreview .rotulo-scale .rotulo-bottom-layout {
            display: flex;
            align-items: flex-start;
            gap: 0;
            width: 100%;
            margin-top: 0;
            padding-top: 0;
        }
        #rotuloPreview .rotulo-scale .rotulo-bottom-main {
            flex: 1 1 auto;
            min-width: 0;
            font-size: 12px;
            max-width: calc(100% - 140px);
        }
        #rotuloPreview .rotulo-scale .rotulo-bottom-qr {
            flex: 0 0 132px;
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            padding-top: 0;
            margin-left: -12mm;
        }
        #rotuloPreview .rotulo-scale .rotulo-qr-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 132px;
            min-width: 132px;
            height: 132px;
            padding: 2px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-sizing: border-box;
            overflow: hidden;
            margin-top: -6px;
        }
        #rotuloPreview .rotulo-scale .rotulo-qr-slot {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            width: 128px;
            height: 128px;
            flex: 0 0 128px;
            overflow: hidden;
        }
        #rotuloPreview .rotulo-scale .rotulo-qr-slot canvas {
            width: 128px !important;
            height: 128px !important;
            max-width: 128px !important;
            max-height: 128px !important;
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
        /* Forzar negrita en títulos y etiquetas */
        #rotuloPreview .rotulo-scale h3 { font-weight: 800 !important; }
        #rotuloPreview .rotulo-scale strong { font-weight: 800 !important; }
        #rotuloPreview .rotulo-scale p strong { font-weight: 800 !important; }
        /* Total a cobrar grande y centrado (override de tamaños generales) */
        #rotuloPreview .rotulo-scale .rotulo-total {
            margin: 2px 0;
            font-size: 30px !important;
            font-weight: 800;
            color: #28a745;
            text-align: center;
            line-height: 0.9;
        }
        /* Compactar textos para no mover el QR */
        #rotuloPreview .rotulo-scale .rotulo-card p {
            margin: 1px 0;
            line-height: 1.0;
            overflow-wrap: anywhere;
        }
        #rotuloPreview .rotulo-scale .rotulo-card h3 {
            margin: 0 0 4px;
        }
        #rotuloPreview .rotulo-scale .rotulo-text-lg {
            font-size: 14px !important;
            font-weight: 600;
            line-height: 1.0;
        }
        #rotuloPreview .rotulo-scale .rotulo-text-lg.bold {
            font-weight: 700;
        }
        #rotuloPreview .rotulo-scale .rotulo-card,
        #rotuloPreview .rotulo-scale .guia-left-col,
        #rotuloPreview .rotulo-scale .rotulo-bottom-main {
            overflow: hidden;
        }
        #rotuloPreview .rotulo-scale .rotulo-bottom-main .rotulo-card {
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <div class="container app-shell">
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
                <button type="button" class="btn btn-secondary is-disabled" id="btnAsignarSeleccionados" aria-disabled="true" onclick="if (window.abrirModalAsignacionMasiva) window.abrirModalAsignacionMasiva();">
                    Asignar Mensajero
                </button>
                <button class="btn btn-secondary" id="btnExportarGuias">
                    🧾 Descargar Guías
                </button>
                <button class="btn btn-primary" id="btnNuevoPaquete">
                    + Nueva Entrega
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
                    <div class="search-select">
                        <input type="text" id="filtroClienteInput" class="form-control" placeholder="Buscar cliente o remitente...">
                        <input type="hidden" id="filtroCliente" value="">
                        <div id="filtroClienteOpciones" class="search-select-options"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select id="filtroEstado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="sin_asignar">Sin asignar</option>
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
                    <div class="search-select">
                        <input type="text" id="filtroMensajeroInput" class="form-control" placeholder="Buscar mensajero...">
                        <input type="hidden" id="filtroMensajero" value="">
                        <div id="filtroMensajeroOpciones" class="search-select-options"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Recaudo</label>
                    <select id="filtroRecaudo" class="form-control">
                        <option value="">Todos</option>
                        <option value="con_recaudo">Con recaudo</option>
                        <option value="sin_recaudo">Sin recaudo</option>
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
                            <th title="Checklist de control"></th>
                            <th>N°</th>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Acciones</th>
                            <th class="sortable" data-column="fecha">Fecha/Hora <span class="sort-icon">↕</span></th>
                            <th>Remitente</th>
                            <th>Nombre</th>
                            <th>Destinatario</th>
                            <th>Dirección Entrega</th>
                            <th>Mjs Recolección</th>
                            <th>Estado Recolección</th>
                            <th>Mjs Entrega</th>
                            <th>Estado Entrega</th>
                            <th>Recaudo</th>
                            <th>Recaudo Real</th>
                            <th>Cambios</th>
                            <th>Valor Envío Agregado</th>
                            <th class="sortable" data-column="guia">N° Guía <span class="sort-icon">↕</span></th>
                            <th>Acciones 2</th>
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

        <!-- Modal Entrega sin Rótulo -->
        <div class="modal modal-nuevo-paquete" id="modalNuevoPaquete">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Entregar Paquete</h2>
                    <button type="button" class="btn-close" id="btnCerrarNuevoPaquete">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="nuevo-paquete-help">Registra una entrega sin r&oacute;tulo: se crea la gu&iacute;a, queda entregada y asociada a la tienda y mensajero seleccionados.</p>
                    <form id="formNuevoPaqueteAdmin">
                        <input type="hidden" name="numero_guia" id="nuevoNumeroGuia">
                        <div class="nuevo-guia-preview">Gu&iacute;a: <strong id="nuevoGuiaTexto">EBM-00000000-XXXXX</strong></div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nuevoClienteInput">Tienda *</label>
                                <div class="search-select">
                                    <input type="text" id="nuevoClienteInput" class="form-control" placeholder="Buscar tienda..." autocomplete="off" required>
                                    <input type="hidden" id="nuevoClienteId" name="cliente_id">
                                    <div id="nuevoClienteOpciones" class="search-select-options"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="nuevoMensajeroInput">Mensajero que entrega *</label>
                                <div class="search-select">
                                    <input type="text" id="nuevoMensajeroInput" class="form-control" placeholder="Buscar mensajero..." autocomplete="off" required>
                                    <input type="hidden" id="nuevoMensajeroId" name="mensajero_id">
                                    <div id="nuevoMensajeroOpciones" class="search-select-options"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="nuevoDestinatarioNombre">Destinatario *</label>
                                <input type="text" id="nuevoDestinatarioNombre" name="destinatario_nombre" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="nuevoNombreReceptor">Nombre de quien recibe *</label>
                                <input type="text" id="nuevoNombreReceptor" name="nombre_receptor" class="form-control" required placeholder="Nombre completo">
                            </div>
                            <div class="form-group">
                                <label for="nuevoParentescoInput">Parentesco o cargo *</label>
                                <div class="search-select">
                                    <input type="text" id="nuevoParentescoInput" class="form-control" placeholder="Seleccionar..." autocomplete="off" required>
                                    <input type="hidden" id="nuevoParentescoCargo" name="parentesco_cargo">
                                    <div id="nuevoParentescoOpciones" class="search-select-options"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="nuevoDocumentoReceptor">N&uacute;mero de c&eacute;dula o placa *</label>
                                <input type="text" id="nuevoDocumentoReceptor" name="documento_receptor" class="form-control" required placeholder="CC, CE, Placa, etc.">
                            </div>
                            <div class="form-group">
                                <label for="nuevoRecaudoReal">Total recaudado *</label>
                                <input type="number" id="nuevoRecaudoReal" name="recaudo_real" class="form-control" min="0" step="100" value="0" required>
                            </div>
                            <label class="form-check-inline">
                                <input type="checkbox" id="nuevoRecibioCambios" name="recibio_cambios">
                                <span>Recibi&oacute; cambios</span>
                            </label>
                            <div class="form-group full-width">
                                <label for="nuevoObservacionesEntrega">Observaciones</label>
                                <textarea id="nuevoObservacionesEntrega" name="observaciones" class="form-control" rows="2" placeholder="Detalles de la entrega..."></textarea>
                            </div>
                            <div class="form-group">
                                <label for="nuevoFotoEntrega">Evidencia fotogr&aacute;fica *</label>
                                <input type="file" id="nuevoFotoEntrega" name="foto_entrega" class="form-control" accept="image/*" required>
                            </div>
                            <div class="form-group">
                                <label for="nuevoFotoAdicional">Foto adicional</label>
                                <input type="file" id="nuevoFotoAdicional" name="foto_adicional" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCancelarNuevoPaquete">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="btnGuardarNuevoPaquete">Registrar entrega</button>
                        </div>
                    </form>
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
                            <label>Paquete(s)</label>
                            <textarea id="asignarGuia" readonly class="form-control" rows="4" style="resize: vertical;"></textarea>
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

        <!-- Modal Rótulo (Guía) -->
        <div class="modal" id="rotuloModal">
            <div class="modal-content" style="max-width: 800px; padding: 20px;">
                <div class="modal-header" style="border: none; padding-bottom: 0;">
                    <h2>🏷️ Guía de Envío</h2>
                    <button class="btn-close" id="closeRotuloModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="rotuloPreview" style="background: white; padding: 20px; border: 1px solid #ccc; font-family: Arial, sans-serif; color: #333;">
                        <div class="rotulo-scale">
                            <table style="width: 100%; border-bottom: 2px solid #5cb85c; padding-bottom: 6px;">
                                <tr>
                                    <td colspan="2">
                                        <div style="display: flex; align-items: center; gap: 100px; justify-content: center; text-align: center;">
                                            <img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:100px;height:100px;">
                                            <div>
                                                <div style="font-size: 26px; font-weight: 800; color: #5cb85c; line-height: 1;">EcoBikeMess</div>
                                                <div style="margin-top: 3px; font-size: 15px; font-weight: 700; color: #28a745;">Contactanos: 31235180619</div>
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

                            <table class="rotulo-top-grid">
                                <tr>
                                    <td style="width: 4%;"></td>
                                    <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 4px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 4px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 3px;">Destinatario</h3>
                                        <p><strong>Dirección:</strong> <span id="rotulo_dir_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Nombre:</strong> <span id="rotulo_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Teléfono:</strong> <span id="rotulo_tel_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Observaciones:</strong> <span id="rotulo_observaciones" class="rotulo-text-lg bold"></span></p>
                                    </td>
                                    <td class="rotulo-card rotulo-remitente-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 4px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 4px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 3px;">Remitente</h3>
                                        <div class="rotulo-remitente-body">
                                            <p><strong>Tienda:</strong> <span id="rotulo_remitente" class="rotulo-text-lg bold"></span></p>
                                        </div>
                                    </td>
                                    
                                </tr>
                            </table>

                            <div class="rotulo-bottom-layout">
                                <div class="rotulo-bottom-main">
                                    <div class="guia-left-col">
                                        <div class="guia-divider-h"></div>
                                        <div class="rotulo-card" style="padding:1px 2px; border-radius: 8px;">
                                            <h3 style="margin: 0 0 4px; font-size: 15px; padding-bottom: 3px;">Detalles del Paquete</h3>
                                            <p><strong>Cambios por recoger:</strong> <span id="rotulo_cambios" class="rotulo-text-lg"></span></p>
                                        </div>
                                        <div style="margin-top: 4px;">
                                            <h3 style="margin: 0 0 4px; font-size: 15px;">Total a Cobrar</h3>
                                            <div id="rotulo_financiero"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="rotulo-bottom-qr">
                                    <div class="rotulo-qr-panel">
                                        <div id="rotulo_qr_code" class="rotulo-qr-slot"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-actions" style="text-align: center; margin-top: 20px;">
                    <button class="btn btn-primary" id="btnDownloadRotulo">
                        ⬇️ Descargar PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="modal" id="modalCancelarServicio">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Cancelar Servicio</h2>
                    <button class="btn-close" id="btnCerrarCancelarServicio">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formCancelarServicio">
                        <input type="hidden" id="cancelarPaqueteId" name="paquete_id">
                        <div class="form-group">
                            <label>Guía</label>
                            <input type="text" id="cancelarGuia" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nombre del paquete</label>
                            <input type="text" id="cancelarNombrePaquete" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Razón de cancelación *</label>
                            <textarea id="cancelarMotivo" name="motivo" class="form-control" rows="4" required placeholder="Describe la razón por la cual se cancela el servicio"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Evidencia fotográfica</label>
                            <input type="file" id="cancelarEvidencia" name="evidencia" class="form-control" accept="image/*">
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCancelarServicioCerrar">Cerrar</button>
                            <button type="submit" class="btn btn-danger">Confirmar Cancelación</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script src="../../public/js/imageLightbox.js"></script>
    <script src="../../public/js/rotuloShared.js"></script>
    <script src="../../public/js/paquetesAdmin.js?v=20260721-1"></script>
    <script>
        window.abrirModalAsignacionMasiva = async function() {
            const seleccionados = Array.from(document.querySelectorAll('.paquete-checkbox:checked')).map((checkbox) => ({
                id: String(checkbox.value || '').trim(),
                guia: String(checkbox.dataset.guia || '').trim(),
                row: checkbox.closest('tr')
            })).filter((item) => item.id !== '');

            if (seleccionados.length === 0) {
                alert('Selecciona al menos un paquete para asignar.');
                return;
            }

            const modal = document.getElementById('modalAsignar');
            const form = document.getElementById('formAsignarMensajero');
            const inputGuias = document.getElementById('asignarGuia');
            const inputBuscarMensajero = document.getElementById('buscarMensajeroInput');
            const inputMensajero = document.getElementById('asignarMensajero');
            const listaMensajeros = document.getElementById('listaMensajeros');

            if (!modal || !form || !inputGuias || !inputBuscarMensajero || !inputMensajero || !listaMensajeros) {
                alert('No se pudo abrir el modal de asignación.');
                return;
            }

            let hiddenPaqueteId = document.getElementById('hiddenPaqueteId');
            if (!hiddenPaqueteId) {
                hiddenPaqueteId = document.createElement('input');
                hiddenPaqueteId.type = 'hidden';
                hiddenPaqueteId.id = 'hiddenPaqueteId';
                hiddenPaqueteId.name = 'paquete_id';
                form.appendChild(hiddenPaqueteId);
            }

            let hiddenPaqueteIds = document.getElementById('hiddenPaqueteIds');
            if (!hiddenPaqueteIds) {
                hiddenPaqueteIds = document.createElement('input');
                hiddenPaqueteIds.type = 'hidden';
                hiddenPaqueteIds.id = 'hiddenPaqueteIds';
                hiddenPaqueteIds.name = 'paquete_ids';
                form.appendChild(hiddenPaqueteIds);
            }

            const ids = seleccionados.map((item) => item.id);
            const guias = seleccionados.map((item) => {
                if (item.guia) {
                    return item.guia;
                }

                const celdas = item.row ? item.row.querySelectorAll('td') : [];
                const guiaCelda = celdas.length >= 17 ? celdas[16] : null;
                return String(guiaCelda?.textContent || '').trim();
            }).filter(Boolean);

            hiddenPaqueteId.value = ids.length === 1 ? ids[0] : '';
            hiddenPaqueteIds.value = ids.join(',');
            form.dataset.bulkMode = ids.length > 1 ? '1' : '0';
            inputGuias.value = guias.length > 0 ? guias.join('\n') : ids.join('\n');
            inputBuscarMensajero.value = '';
            inputMensajero.value = '';

            try {
                if (typeof todosLosMensajeros !== 'undefined' && Array.isArray(todosLosMensajeros) && todosLosMensajeros.length > 0 && typeof renderizarListaMensajeros === 'function') {
                    renderizarListaMensajeros(todosLosMensajeros);
                } else {
                    const response = await fetch('../../controller/paquetesAdminController.php?action=filtros');
                    const data = await response.json();
                    const mensajeros = Array.isArray(data.mensajeros) ? data.mensajeros : [];

                    if (typeof todosLosMensajeros !== 'undefined') {
                        todosLosMensajeros = mensajeros;
                    }

                    if (typeof renderizarListaMensajeros === 'function') {
                        renderizarListaMensajeros(mensajeros);
                    } else {
                        listaMensajeros.innerHTML = mensajeros.map((m) => `
                            <div class="mensajero-item" onclick="seleccionarMensajero(${Number(m.id || 0)}, '${String(m.nombre || '').replace(/'/g, "\\'")}')" data-id="${Number(m.id || 0)}">
                                <div style="font-weight:bold;">${String(m.nombre || '')}</div>
                                <div style="font-size:0.85em; color:#666;">
                                    <span style="color:${m.estado === 'activo' ? 'green' : 'gray'}">● ${String(m.estado || 'sin estado')}</span> | Tareas activas: ${Number(m.tareas_activas || 0)}
                                </div>
                            </div>
                        `).join('') || '<div class="mensajero-item text-muted">No se encontraron mensajeros</div>';
                    }
                }
            } catch (error) {
                console.error(error);
            }

            document.querySelectorAll('.mensajero-item').forEach((el) => el.classList.remove('selected'));
            modal.style.display = 'flex';
        };

        let currentRotuloData = null;
        const truncarRotulo = (value, max) => {
            const text = String(value || '').replace(/\s+/g, ' ').trim();
            if (!text) return '';
            return text.length > max ? `${text.slice(0, max - 1)}…` : text;
        };

        window.verRotulo = async function(datos) {
            const modal = document.getElementById('rotuloModal');
            const preview = document.getElementById('rotuloPreview');
            if (!modal) return;
            if (preview && window.RotuloEcoBike) {
                currentRotuloData = datos;
                await window.RotuloEcoBike.mountPreview(preview, datos);
                modal.style.display = 'flex';
                return;
            }

            document.getElementById('rotulo_guia_num').textContent = truncarRotulo(datos.guia || 'N/A', 26);
            document.getElementById('rotulo_remitente').textContent = truncarRotulo(datos.tienda_nombre || datos.remitente_nombre || 'Tienda', 34);
            document.getElementById('rotulo_destinatario').textContent = truncarRotulo(datos.destinatario_nombre || 'Cliente', 30);
            document.getElementById('rotulo_dir_destinatario').textContent = truncarRotulo(datos.destinatario_direccion || '', 82);
            document.getElementById('rotulo_tel_destinatario').textContent = truncarRotulo(datos.destinatario_telefono || '', 18);
            document.getElementById('rotulo_observaciones').textContent = truncarRotulo(datos.destinatario_observaciones || 'Sin observaciones', 52);
            document.getElementById('rotulo_cambios').textContent = truncarRotulo(datos.cambios || 'No', 24);

            const formatMoney = (val) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val);
            const totalCobrar = datos.recaudo > 0 ? Number(datos.recaudo) : 0;
            const totalTexto = formatMoney(totalCobrar);
            document.getElementById('rotulo_financiero').innerHTML = `<p class="rotulo-total">${totalTexto}</p>`;

            const qrContainer = document.getElementById('rotulo_qr_code');
            qrContainer.innerHTML = '';
            const qrData = datos.guia;
            const qrCode = new QRCodeStyling({
                width: 128,
                height: 128,
                type: "canvas",
                data: qrData,
                dotsOptions: { color: "#000", type: "square" },
                backgroundOptions: { color: "#fff" },
                qrOptions: { errorCorrectionLevel: 'M' }
            });
            qrCode.append(qrContainer);

            modal.style.display = 'flex';
        };

        document.getElementById('closeRotuloModal').onclick = () => document.getElementById('rotuloModal').style.display = 'none';
        document.getElementById('btnDownloadRotulo').onclick = async () => {
            if (currentRotuloData && window.RotuloEcoBike) {
                try {
                    await window.RotuloEcoBike.downloadPdf(currentRotuloData, { filePrefix: 'Guia' });
                    return;
                } catch (error) {
                    alert('Error al generar PDF');
                    console.error(error);
                    return;
                }
            }
            const element = document.getElementById('rotuloPreview');
            const guia = document.getElementById('rotulo_guia_num').textContent;
            try {
                const canvas = await html2canvas(element, { scale: 2, backgroundColor: '#ffffff' });
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', [100, 100]);
                const pdfWidth = 100;
                const pdfHeight = 100;
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save(`Guia_${guia}.pdf`);
            } catch (error) {
                alert('Error al generar PDF');
                console.error(error);
            }
        };
    </script>
</body>
</html>

