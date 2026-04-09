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
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/misPedidos.css">
    <link rel="stylesheet" href="../../public/css/clientesTheme.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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

        /* Estilos para el Rótulo (Copiados de misPedidos/enviarPaquete) */
        .rotulo-card { background: #fff; border: 2px solid #333; border-radius: 8px; padding: 20px; max-width: 450px; margin: 0 auto; font-family: Arial, sans-serif; color: #000; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .rotulo-header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .rotulo-brand .brand-name { font-size: 1.4rem; font-weight: bold; color: #28a745; display: block; }
        .rotulo-brand .brand-slogan { font-size: 0.8rem; color: #666; }
        .rotulo-guia { text-align: right; }
        .rotulo-guia .guia-label { font-size: 0.7rem; display: block; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .rotulo-guia .guia-value { font-size: 1.4rem; font-weight: bold; color: #333; }
        .rotulo-main { display: flex; gap: 15px; margin-bottom: 15px; align-items: center; }
        .rotulo-qr { width: 130px; height: 130px; border: 1px solid #eee; padding: 5px; border-radius: 4px; }
        .rotulo-dates { flex: 1; display: flex; flex-direction: column; gap: 10px; }
        .date-group { background: #f8f9fa; padding: 8px; border-radius: 4px; border-left: 3px solid #28a745; }
        .date-group label { font-size: 0.7rem; color: #666; display: block; margin-bottom: 2px; }
        .date-group span { font-weight: bold; font-size: 1rem; color: #333; }
        .rotulo-addresses { border: 1px solid #000; border-radius: 6px; margin-bottom: 15px; overflow: hidden; }
        .address-block { padding: 12px; }
        .address-block.from { border-bottom: 1px solid #ccc; background: #f9f9f9; }
        .block-label { font-size: 0.7rem; font-weight: bold; color: #666; margin-bottom: 5px; letter-spacing: 1px; text-transform: uppercase; }
        .block-content strong { display: block; font-size: 1.1rem; margin-bottom: 3px; color: #000; }
        .block-content p { margin: 0; font-size: 0.9rem; line-height: 1.4; color: #444; }
        .address-arrow { text-align: center; font-size: 1.2rem; color: #28a745; margin: -12px 0; position: relative; z-index: 1; text-shadow: 0 2px 0 #fff; }
        .rotulo-footer { border-top: 2px solid #000; padding-top: 10px; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-info span { display: block; font-size: 0.9rem; margin-bottom: 3px; }
        .footer-note { font-size: 0.8rem; font-style: italic; max-width: 60%; text-align: right; color: #666; }

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
            border-top: 2px solid #28a745;
            margin: 4px 0 6px;
        }
        .guia-left-col {
            position: relative;
            padding-right: 6px;
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
        /* Total a cobrar (estilo compacto como rótulo final) */
        #rotuloPreview .rotulo-scale .rotulo-total {
            margin: 2px 0;
            font-size: 26px !important;
            font-weight: 800;
            color: #28a745;
            text-align: left;
            line-height: 1.1;
        }
        /* Compactar textos para no mover el QR */
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
    </style>
</head>
<body>
    <?php include '../layouts/clienteNavbar.php'; ?>
    <?php include '../layouts/clienteSidebar.php'; ?>

    <div class="container app-shell">
        <!-- Header -->
        <header class="page-header">
            <div>
                <h1>Mis pedidos</h1>
                <p>Gestiona y consulta todos tus envíos</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" id="btnExportarExcel">
                    📊 Exportar Excel
                </button>
                <button class="btn btn-secondary" id="btnExportarGuias">
                    🧾 Descargar Guías
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
                <input type="text" id="searchInput" placeholder="🔍 Buscar por número guia, destinatario..." class="search-input">
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

            <div class="table-responsive pedidos-scroll">
                <table id="tablaFacturas">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Guía</th>
                            <th>Fecha Ingreso</th>
                            <th>Destinatario</th>
                            <th>Dirección</th>
                            <th>Valor Envío</th>
                            <th>Envío agregado<br>al recaudo</th>
                            <th>Recaudo</th>
                            <th>Estado</th>
                            <th>Fecha Entrega</th>
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

        <!-- Modal Rótulo (Guía) -->
        <div class="modal" id="rotuloModal">
            <div class="modal-content" style="max-width: 800px; padding: 20px;">
                <div class="modal-header" style="border: none; padding-bottom: 0;">
                    <h2>🏷️ Rótulo de Envío</h2>
                    <button class="btn-close" id="closeRotuloModal">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Estructura idéntica a enviarPaquete.php -->
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
                                        <div style="font-size: 13px; font-weight: 800; color: #000000;">NUM GUÍA: <span id="rotulo_guia_num" style="font-size: 19px; font-weight: 800; color: #1f2a37;">EBM-XXXXXX</span></div>
                                    </td>
                                </tr>
                            </table>

                            <table style="width: 100%; margin-top: 4px; font-size: 12px;">
                                <tr>
                                    <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;"> Destinatario</h3>
                                        <p><strong>Dirección:</strong> <span id="rotulo_dir_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Nombre:</strong> <span id="rotulo_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Teléfono:</strong> <span id="rotulo_tel_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Observaciones:</strong> <span id="rotulo_observaciones" class="rotulo-text-lg bold"></span></p>
                                    </td>
                                    <td style="width: 4%;"></td>
                                    <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;"> Remitente</h3>
                                        <p><strong>Tienda:</strong> <span id="rotulo_remitente" class="rotulo-text-lg bold"></span></p>
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
                                                <p><strong>Cambios por recoger:</strong> <span id="rotulo_cambios" class="rotulo-text-lg bold"></span></p>
                                            </div>
                                            <div style="margin-top: 6px;">
                                                <h3 style="margin: 0 0 6px; font-size: 15px;"> Total a Cobrar</h3>
                                                <div id="rotulo_financiero">
                                                    <!-- Se llena dinámicamente -->
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="width: 40%; text-align: right; vertical-align: top;">
                                        <div class="guia-right-col">
                                            <div id="rotulo_qr_code" style="display: inline-block; width: 220px; height: 220px; margin-right: 6mm; margin-top: -9mm;"></div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
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

    </div>

    <!-- Enlace al script JS recién creado -->
    <script src="../../public/js/imageLightbox.js"></script>
    <script src="../../public/js/misPedidos.js"></script>
    
    <!-- Script para manejar el Rótulo -->
    <script>
        // Función global para abrir el rótulo
        window.verRotulo = function(datos) {
            const modal = document.getElementById('rotuloModal');
            if(!modal) return;

            // Llenar datos
            document.getElementById('rotulo_guia_num').textContent = datos.guia || 'N/A';
            
            document.getElementById('rotulo_remitente').textContent = datos.tienda_nombre || datos.remitente_nombre || 'Tienda';
            
            document.getElementById('rotulo_destinatario').textContent = datos.destinatario_nombre || 'Cliente';
            document.getElementById('rotulo_dir_destinatario').textContent = datos.destinatario_direccion || '';
            document.getElementById('rotulo_tel_destinatario').textContent = datos.destinatario_telefono || '';
            document.getElementById('rotulo_observaciones').textContent = datos.destinatario_observaciones || 'Sin observaciones';
            
            document.getElementById('rotulo_cambios').textContent = datos.cambios || 'No';

            // Total a Cobrar
            const formatMoney = (val) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val);
            const totalCobrar = datos.recaudo > 0 ? Number(datos.recaudo) : 0;
            const totalTexto = formatMoney(totalCobrar);
            
            document.getElementById('rotulo_financiero').innerHTML = `
                <p class="rotulo-total">${totalTexto}</p>
            `;

            // Generar QR
            const qrContainer = document.getElementById('rotulo_qr_code');
            qrContainer.innerHTML = '';
            
            const qrData = `Guía: ${datos.guia}\nRemitente: ${datos.tienda_nombre || datos.remitente_nombre}\nDestinatario: ${datos.destinatario_nombre}\nDirección: ${datos.destinatario_direccion}\nTotal a Cobrar: ${totalTexto}`;
            
            const qrCode = new QRCodeStyling({ width: 220, height: 220, type: "canvas", data: qrData, dotsOptions: { color: "#000", type: "rounded" }, backgroundOptions: { color: "#fff" } });
            qrCode.append(qrContainer);

            modal.style.display = 'flex';
        };

        // Eventos del modal
        document.getElementById('closeRotuloModal').onclick = () => document.getElementById('rotuloModal').style.display = 'none';
        document.getElementById('btnDownloadRotulo').onclick = async () => {
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
                pdf.save(`Rotulo_${guia}.pdf`);
            } catch (error) { alert('Error al generar PDF'); console.error(error); }
        };
    </script>
</body>
</html>


