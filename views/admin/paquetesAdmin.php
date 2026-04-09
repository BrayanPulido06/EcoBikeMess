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
                <button class="btn btn-secondary" id="btnExportarGuias">
                    🧾 Descargar Guías
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
                                            <img src="/ecobikemess/public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:100px;height:100px;">
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

                            <table style="width: 100%; margin-top: 2px; font-size: 12px;">
                                <tr>
                                    <td style="width: 4%;"></td>
                                    <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Destinatario</h3>
                                        <p><strong>Dirección:</strong> <span id="rotulo_dir_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Nombre:</strong> <span id="rotulo_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Teléfono:</strong> <span id="rotulo_tel_destinatario" class="rotulo-text-lg bold"></span></p>
                                        <p><strong>Observaciones:</strong> <span id="rotulo_observaciones" class="rotulo-text-lg bold"></span></p>
                                    </td>
                                    <td class="rotulo-card" style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Remitente</h3>
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
                                                <h3 style="margin: 0 0 8px; font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Detalles del Paquete</h3>
                                                <p><strong>Cambios por recoger:</strong> <span id="rotulo_cambios" class="rotulo-text-lg"></span></p>
                                            </div>
                                            <div style="margin-top: 6px;">
                                                <h3 style="margin: 0 0 6px; font-size: 15px;">Total a Cobrar</h3>
                                                <div id="rotulo_financiero"></div>
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

    <script src="../../public/js/paquetesAdmin.js"></script>
    <script>
        window.verRotulo = function(datos) {
            const modal = document.getElementById('rotuloModal');
            if (!modal) return;

            document.getElementById('rotulo_guia_num').textContent = datos.guia || 'N/A';
            document.getElementById('rotulo_remitente').textContent = datos.tienda_nombre || datos.remitente_nombre || 'Tienda';
            document.getElementById('rotulo_destinatario').textContent = datos.destinatario_nombre || 'Cliente';
            document.getElementById('rotulo_dir_destinatario').textContent = datos.destinatario_direccion || '';
            document.getElementById('rotulo_tel_destinatario').textContent = datos.destinatario_telefono || '';
            document.getElementById('rotulo_observaciones').textContent = datos.destinatario_observaciones || 'Sin observaciones';
            document.getElementById('rotulo_cambios').textContent = datos.cambios || 'No';

            const formatMoney = (val) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val);
            const totalCobrar = datos.recaudo > 0 ? Number(datos.recaudo) : 0;
            const totalTexto = formatMoney(totalCobrar);
            document.getElementById('rotulo_financiero').innerHTML = `<p class="rotulo-total">${totalTexto}</p>`;

            const qrContainer = document.getElementById('rotulo_qr_code');
            qrContainer.innerHTML = '';
            const qrData = `Guía: ${datos.guia}\nRemitente: ${datos.tienda_nombre || datos.remitente_nombre}\nDestinatario: ${datos.destinatario_nombre}\nDirección: ${datos.destinatario_direccion}\nTotal a Cobrar: ${totalTexto}`;
            const qrCode = new QRCodeStyling({
                width: 220,
                height: 220,
                type: "canvas",
                data: qrData,
                dotsOptions: { color: "#000", type: "rounded" },
                backgroundOptions: { color: "#fff" }
            });
            qrCode.append(qrContainer);

            modal.style.display = 'flex';
        };

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
                pdf.save(`Guia_${guia}.pdf`);
            } catch (error) {
                alert('Error al generar PDF');
                console.error(error);
            }
        };
    </script>
</body>
</html>
