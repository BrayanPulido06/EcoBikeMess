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
    <link rel="stylesheet" href="../../public/css/misPedidos.css">
    <link rel="stylesheet" href="../../public/css/clientesTheme.css">
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
                        <table style="width: 100%; border-bottom: 2px solid #5cb85c; padding-bottom: 10px;">
                            <tr>
                                <td style="width: 50%;">
                                    <h1 style="font-size: 24px; margin: 0; color: #5cb85c;">🚴 EcoBikeMess</h1>
                                    <p style="margin: 0; font-size: 12px;">Guía de Envío</p>
                                </td>
                                <td style="width: 50%; text-align: right;">
                                    <p style="margin: 0; font-size: 12px;">Número de Guía:</p>
                                    <h2 style="margin: 0; font-size: 18px;" id="rotulo_guia_num">ECO-XXXXXX</h2>
                                </td>
                            </tr>
                        </table>
                        
                        <table style="width: 100%; margin-top: 20px; font-size: 11px;">
                            <tr>
                                <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                                    <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📤 Remitente</h3>
                                    <p><strong>Tienda:</strong> <span id="rotulo_remitente"></span></p>
                                </td>
                                <td style="width: 4%;"></td>
                                <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                                    <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📥 Destinatario</h3>
                                    <p><strong>Dirección:</strong> <span id="rotulo_dir_destinatario"></span></p>
                                    <p><strong>Nombre:</strong> <span id="rotulo_destinatario"></span></p>
                                    <p><strong>Teléfono:</strong> <span id="rotulo_tel_destinatario"></span></p>
                                    <p><strong>Observaciones:</strong> <span id="rotulo_observaciones"></span></p>
                                </td>
                            </tr>
                        </table>

                        <div style="margin-top: 20px; border: 1px solid #eee; padding: 10px; border-radius: 8px; font-size: 11px;">
                            <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📦 Detalles del Paquete</h3>
                            <p><strong>Descripción:</strong> <span id="rotulo_contenido"></span></p>
                            <p><strong>Cambios por recoger:</strong> <span id="rotulo_cambios"></span></p>
                        </div>

                        <table style="width: 100%; margin-top: 20px; border-top: 2px solid #5cb85c; padding-top: 10px;">
                            <tr>
                                <td style="width: 60%; vertical-align: top; font-size: 11px;">
                                    <h3 style="margin: 0 0 10px; font-size: 14px;">💰 Total a Cobrar</h3>
                                    <div id="rotulo_financiero">
                                        <!-- Se llena dinámicamente -->
                                    </div>
                                </td>
                                <td style="width: 40%; text-align: right;">
                                    <div id="rotulo_qr_code" style="display: inline-block; width: 190px; height: 190px;"></div>
                                </td>
                            </tr>
                        </table>
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
            
            document.getElementById('rotulo_contenido').textContent = datos.contenido || '';
            document.getElementById('rotulo_cambios').textContent = datos.cambios || 'No';

            // Total a Cobrar
            const formatMoney = (val) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val);
            const totalCobrar = datos.recaudo > 0 ? Number(datos.recaudo) : 0;
            const totalTexto = formatMoney(totalCobrar);
            
            document.getElementById('rotulo_financiero').innerHTML = `
                <p style="margin: 4px 0; font-size: 32px; font-weight: 800; color: #28a745;">${totalTexto}</p>
            `;

            // Generar QR
            const qrContainer = document.getElementById('rotulo_qr_code');
            qrContainer.innerHTML = '';
            
            const qrData = `Guía: ${datos.guia}\nRemitente: ${datos.tienda_nombre || datos.remitente_nombre}\nDestinatario: ${datos.destinatario_nombre}\nDirección: ${datos.destinatario_direccion}\nTotal a Cobrar: ${totalTexto}`;
            
            const qrCode = new QRCodeStyling({ width: 190, height: 190, type: "canvas", data: qrData, dotsOptions: { color: "#000", type: "rounded" }, backgroundOptions: { color: "#fff" } });
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
                const pdf = new jsPDF('p', 'mm', 'a6');
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save(`Rotulo_${guia}.pdf`);
            } catch (error) { alert('Error al generar PDF'); console.error(error); }
        };
    </script>
</body>
</html>


