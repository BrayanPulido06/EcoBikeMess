let currentData = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('Script facturacionCliente.js cargado');

    // --- REFERENCIAS DOM ---
    const tableBody = document.getElementById('tablaFacturasBody');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const btnExportExcel = document.getElementById('btnExportarExcel');
    const btnExportarGuias = document.getElementById('btnExportarGuias');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    // Filtros
    const inputs = {
        search: document.getElementById('searchInput'),
        fechaDesde: document.getElementById('filtroFechaDesde'),
        fechaHasta: document.getElementById('filtroFechaHasta'),
        estado: document.getElementById('filtroEstado'),
        monto: document.getElementById('filtroMonto')
    };

    const btnNueva = document.getElementById('btnNuevaFactura');
    if (btnNueva) btnNueva.style.display = 'none'; 

    // --- INICIALIZACIÓN ---
    cargarEstadisticas();
    listarFacturas();
    setupModalClosers();

    // --- EVENTOS ---
    Object.values(inputs).forEach(input => {
        if (input) input.addEventListener('change', () => {
            listarFacturas();
            cargarEstadisticas(); // Recargar estadísticas al filtrar
        });
    });

    if (inputs.search) {
        inputs.search.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') listarFacturas();
        });
    }

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            Object.values(inputs).forEach(input => { if(input) input.value = ''; });
            listarFacturas();
            cargarEstadisticas();
        });
    }

    if (btnExportExcel) {
        btnExportExcel.addEventListener('click', exportarExcel);
    }
    if (btnExportarGuias) {
        btnExportarGuias.addEventListener('click', descargarGuiasSeleccionadas);
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.factura-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }
    document.addEventListener('change', (e) => {
        if (e.target && e.target.classList.contains('factura-checkbox')) {
            const all = document.querySelectorAll('.factura-checkbox');
            const checked = document.querySelectorAll('.factura-checkbox:checked');
            if (selectAllCheckbox) selectAllCheckbox.checked = all.length > 0 && all.length === checked.length;
        }
    });

    // --- FUNCIONES ---

    function cargarEstadisticas() {
        const params = new URLSearchParams();
        params.append('action', 'estadisticas');
        for (const [key, input] of Object.entries(inputs)) {
            if (input && input.value) params.append(key, input.value);
        }

        fetch(`../../controller/misPedidosController.php?${params.toString()}`)
            .then(res => res.json())
            .then(response => {
                if (response.success && response.data) {
                    const s = response.data;
                    
                    const elPagar = document.getElementById('statSaldoPagar');
                    const elFavor = document.getElementById('statSaldoFavor');

                    if (elPagar) elPagar.textContent = formatCurrency(s.saldo_pagar || 0);
                    if (elFavor) elFavor.textContent = formatCurrency(s.saldo_favor || 0);
                }
            })
            .catch(err => console.error('Error cargando estadísticas:', err));
    }

    function listarFacturas() {
        if (tableBody) tableBody.innerHTML = '<tr><td colspan="11" class="text-center">Cargando datos...</td></tr>';

        const params = new URLSearchParams();
        params.append('action', 'listar');
        for (const [key, input] of Object.entries(inputs)) {
            if (input && input.value) params.append(key, input.value);
        }

        fetch(`../../controller/misPedidosController.php?${params.toString()}`)
            .then(res => res.json())
            .then(response => {
                if (response.data) {
                    renderizarTabla(response.data);
                } else if (response.error) {
                    if (tableBody) tableBody.innerHTML = `<tr><td colspan="11" class="text-center text-danger">${response.error}</td></tr>`;
                } else {
                    if (tableBody) tableBody.innerHTML = `<tr><td colspan="11" class="text-center">No se encontraron datos</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                if (tableBody) tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error de conexión con el servidor</td></tr>';
            });
    }

    function renderizarTabla(data) {
        if (!tableBody) return;
        
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="11" class="text-center">No se encontraron facturas.</td></tr>';
            return;
        }

        currentData = data;
        let html = '';
        data.forEach(f => {
            let badgeClass = 'secondary';
            switch(f.estado) {
                case 'entregado': badgeClass = 'success'; break;
                case 'pendiente': 
                case 'asignado':
                case 'en_transito': badgeClass = 'warning'; break;
                case 'cancelado': badgeClass = 'danger'; break;
            }

            const fechaIngresoFormateada = formatDateTimeEs(f.fecha_creacion);
            const fechaEntregaFormateada = formatDateTimeEs(f.fecha_entrega);
            const valorEnvio = formatCurrency(f.costo_envio);
            const agregadoRecaudo = String(f.envio_destinatario).toLowerCase() === 'si' ? 'Sí' : 'No';

            html += `
                <tr>
                    <td><input type="checkbox" class="factura-checkbox" value="${f.id}"></td>
                    <td><strong>${f.numero_guia}</strong></td>
                    <td>${fechaIngresoFormateada}</td>
                    <td>${f.destinatario_nombre}</td>
                    <td>${f.direccion_destino}</td>
                    <td style="font-weight:bold;">${valorEnvio}</td>
                    <td>${agregadoRecaudo}</td>
                    <td>${formatCurrency(f.recaudo_esperado)}</td>
                    <td><span class="badge badge-${badgeClass}">${f.estado.toUpperCase()}</span></td>
                    <td>${fechaEntregaFormateada}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-warning" onclick="cargarRotulo(${f.id})" title="Ver Rótulo">🏷️ Rótulo</button>
                            <button class="btn btn-sm btn-info" onclick="verDetalle(${f.id})" title="Ver Detalle">👁️</button>
                            ${f.estado !== 'entregado' && f.estado !== 'cancelado' ? `<button class="btn btn-sm btn-danger" onclick="cancelarPedido(${f.id}, '${f.numero_guia}')" title="Cancelar Pedido">🗑️</button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });
        tableBody.innerHTML = html;
        
        // Actualizar contadores
        document.getElementById('showingFrom').textContent = '1';
        document.getElementById('showingTo').textContent = data.length;
        document.getElementById('totalResults').textContent = data.length;
    }

    // Función global para ver detalle
    window.verDetalle = function(id) {
        const modal = document.getElementById('modalDetalles');
        const container = document.getElementById('detallesFactura');
        
        if (modal) {
            modal.style.display = 'flex';
            container.innerHTML = '<p class="text-center">Cargando detalles...</p>';

            fetch(`../../controller/misPedidosController.php?action=detalle&id=${id}`)
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        const info = response.data.info;
                        
                        container.innerHTML = `
                            <div class="invoice-header" style="border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                                <div style="display:flex; justify-content:space-between;">
                                    <div>
                                        <h3 style="margin:0; color:#2c3e50;">Guía ${info.numero_guia}</h3>
                                        <p style="margin:5px 0; color:#7f8c8d;">Estado: <strong>${info.estado.toUpperCase()}</strong></p>
                                    </div>
                                    <div class="text-right">
                                        <p style="margin:0;"><strong>Fecha:</strong> ${info.fecha_creacion}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <table class="table" style="width:100%; border-collapse: collapse;">
                                <tbody>
                                    <tr>
                                        <td style="padding:8px;"><strong>Destinatario:</strong></td>
                                        <td style="padding:8px;">${info.destinatario_nombre}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px;"><strong>Dirección:</strong></td>
                                        <td style="padding:8px;">${info.direccion_destino}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px;"><strong>Contenido:</strong></td>
                                        <td style="padding:8px;">${info.descripcion_contenido}</td>
                                    </tr>
                                    <tr style="background:#f8f9fa;">
                                        <td style="padding:8px;"><strong>Total Recaudado:</strong></td>
                                        <td style="padding:8px;"><strong>${info.estado === 'entregado' && info.infoEntrega ? formatCurrency(info.infoEntrega.recaudo) : 'Pendiente'}</strong></td>
                                    </tr>
                                </tbody>
                            </table>

                            ${info.estado === 'entregado' && info.infoEntrega ? `
                                <div class="detalle-section" style="margin-top: 20px;">
                                    <h3>✅ Detalles de Entrega</h3>
                                    <div class="detalle-grid">
                                        <div class="detalle-item">
                                            <div class="detalle-label">Recibió</div>
                                            <div class="detalle-value">${info.infoEntrega.nombreRecibe || 'N/A'}</div>
                                        </div>
                                        <div class="detalle-item">
                                            <div class="detalle-label">Parentesco</div>
                                            <div class="detalle-value">${info.infoEntrega.parentesco || 'N/A'}</div>
                                        </div>
                                        <div class="detalle-item">
                                            <div class="detalle-label">Documento</div>
                                            <div class="detalle-value">${info.infoEntrega.documento || 'N/A'}</div>
                                        </div>
                                        <div class="detalle-item">
                                            <div class="detalle-label">Fecha de Entrega</div>
                                            <div class="detalle-value">${formatDateTimeEs(info.infoEntrega.fecha)}</div>
                                        </div>
                                        <div class="detalle-item">
                                            <div class="detalle-label">Recaudo Realizado</div>
                                            <div class="detalle-value">${formatCurrency(info.infoEntrega.recaudo || 0)}</div>
                                        </div>
                                        <div class="detalle-item" style="grid-column: span 2;">
                                            <div class="detalle-label">Observaciones de Entrega</div>
                                            <div class="detalle-value">${info.infoEntrega.observaciones || 'Sin observaciones.'}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="detalle-section" style="margin-top: 20px;">
                                    <h3>📸 Evidencia Fotográfica</h3>
                                    <div class="fotos-evidencia-container" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                                        ${info.infoEntrega.fotoPrincipal ? `
                                            <a href="../../${info.infoEntrega.fotoPrincipal}" class="js-image-lightbox" data-lightbox-src="../../${info.infoEntrega.fotoPrincipal}" data-lightbox-alt="Foto Principal" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                                <img src="../../${info.infoEntrega.fotoPrincipal}" alt="Foto Principal" style="width: 100%; height: 100%; object-fit: cover;">
                                            </a>
                                        ` : ''}
                                        ${info.infoEntrega.fotoAdicional ? `
                                            <a href="../../${info.infoEntrega.fotoAdicional}" class="js-image-lightbox" data-lightbox-src="../../${info.infoEntrega.fotoAdicional}" data-lightbox-alt="Foto Adicional" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                                <img src="../../${info.infoEntrega.fotoAdicional}" alt="Foto Adicional" style="width: 100%; height: 100%; object-fit: cover;">
                                            </a>
                                        ` : ''}
                                        ${!info.infoEntrega.fotoPrincipal && !info.infoEntrega.fotoAdicional ? `
                                            <p class="text-muted" style="width: 100%; text-align: center;">No hay fotos de evidencia disponibles.</p>
                                        ` : ''}
                                    </div>
                                </div>
                            ` : ''}

                            ${info.estado === 'cancelado' ? `
                                <div class="detalle-section" style="margin-top: 20px; background-color: #fff5f5; border: 1px solid #f5c2c7;">
                                    <h3 style="color: #b02a37;">❌ Detalles de Cancelación</h3>
                                    <div class="detalle-grid">
                                        <div class="detalle-item">
                                            <div class="detalle-label">Motivo</div>
                                            <div class="detalle-value">${info.infoCancelacion ? (info.infoCancelacion.motivo || 'Sin información.') : 'Sin información.'}</div>
                                        </div>
                                        <div class="detalle-item">
                                            <div class="detalle-label">Fecha</div>
                                            <div class="detalle-value">${info.infoCancelacion ? formatDateTimeEs(info.infoCancelacion.fecha) : 'Sin información.'}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="detalle-section" style="margin-top: 20px;">
                                    <h3>📸 Evidencia Fotográfica</h3>
                                    <div class="fotos-evidencia-container" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                                        ${info.infoCancelacion && info.infoCancelacion.foto ? `
                                            <a href="../../${info.infoCancelacion.foto}" class="js-image-lightbox" data-lightbox-src="../../${info.infoCancelacion.foto}" data-lightbox-alt="Foto Evidencia" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                                <img src="../../${info.infoCancelacion.foto}" alt="Foto Evidencia" style="width: 100%; height: 100%; object-fit: cover;">
                                            </a>
                                        ` : '<p class="text-muted" style="width: 100%; text-align: center;">No hay fotos de evidencia disponibles.</p>'}
                                    </div>
                                </div>
                            ` : ''}
                        `;
                    } else {
                        container.innerHTML = `<p class="text-danger text-center">${response.error}</p>`;
                    }
                });
        }
    };

    // Función para cargar datos y abrir el rótulo
    window.cargarRotulo = function(id) {
        // Feedback visual en el botón
        const btn = event.currentTarget;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '⌛';
        btn.disabled = true;

        fetch(`../../controller/misPedidosController.php?action=detalle&id=${id}`)
            .then(res => res.json())
            .then(response => {
                btn.innerHTML = originalContent;
                btn.disabled = false;

                if (response.success) {
                    const info = response.data.info;
                    // Preparar datos para el modal
                    const datos = {
                        guia: info.numero_guia,
                        remitente_nombre: info.remitente_nombre || 'EcoBikeMess', // Fallback si no viene del back
                        tienda_nombre: info.nombre_emprendimiento || info.remitente_nombre || 'Tienda',
                        remitente_direccion: info.remitente_direccion || '',
                        remitente_telefono: info.remitente_telefono || '',
                        destinatario_nombre: info.destinatario_nombre,
                        destinatario_direccion: info.direccion_destino,
                        destinatario_telefono: info.destinatario_telefono || '',
                        destinatario_observaciones: info.instrucciones_entrega || 'Sin observaciones',
                        contenido: info.descripcion_contenido || '',
                        cambios: info.recoger_cambios ? 'Sí' : 'No',
                        costo_envio: info.costo_envio,
                        recaudo: info.recaudo_esperado || 0
                    };
                    // Llamar a la función del PHP
                    if (typeof window.verRotulo === 'function') window.verRotulo(datos);
                } else {
                    alert('No se pudieron cargar los datos: ' + response.error);
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
    };

    function formatCurrency(val) {
        return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val);
    }

    // Función global para cancelar pedido
    window.cancelarPedido = function(id, guia) {
        if (!id) return;
        const mensaje = `¿Estás seguro de cancelar el paquete ${guia}? Esta acción no se puede deshacer.`;
        if (!confirm(mensaje)) return;

        fetch(`../../controller/misPedidosController.php?action=cancelar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert(response.message || 'Pedido cancelado correctamente');
                    listarFacturas();
                    cargarEstadisticas();
                } else {
                    alert(response.message || 'No se pudo cancelar el pedido');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión al cancelar el pedido');
            });
    }

    function formatDateTimeEs(dateValue) {
        if (!dateValue) return 'Pendiente';
        const raw = String(dateValue).replace('T', ' ').substring(0, 19);
        const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})\s*(\d{2}):(\d{2})(?::(\d{2}))?$/);
        if (!match) return raw;
        const [, y, m, d, hh, mm] = match;
        return `${d}/${m}/${y} ${hh}:${mm}`;
    }

    function setupModalClosers() {
        document.querySelectorAll('.btn-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });
    }

    function exportarExcel() {
        alert('Funcionalidad de exportación en desarrollo.');
    }

    function formatMoney(val) {
        return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(val || 0);
    }

    function buildRotuloHtml(datos) {
        return window.RotuloEcoBike ? `<div id="rotuloPreview"></div>` : '';
    }

    async function renderRotuloToCanvas(datos) {
        if (!window.RotuloEcoBike) {
            throw new Error('RotuloEcoBike no está disponible');
        }
        return window.RotuloEcoBike.renderToCanvas(datos);
    }

    async function descargarGuiasSeleccionadas() {
        const selectedCheckboxes = document.querySelectorAll('.factura-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            alert('Selecciona al menos un pedido.');
            return;
        }
        const ids = Array.from(selectedCheckboxes).map(cb => cb.value);
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', [100, 100]);
        let first = true;

        for (const id of ids) {
            try {
                const res = await fetch(`../../controller/misPedidosController.php?action=detalle&id=${id}`);
                const response = await res.json();
                if (!response.success) continue;
                const info = response.data.info;
                const datos = {
                    guia: info.numero_guia,
                    remitente_nombre: info.remitente_nombre || 'EcoBikeMess',
                    tienda_nombre: info.nombre_emprendimiento || info.remitente_nombre || 'Tienda',
                    destinatario_nombre: info.destinatario_nombre,
                    destinatario_direccion: info.direccion_destino,
                    destinatario_telefono: info.destinatario_telefono || '',
                    destinatario_observaciones: info.instrucciones_entrega || 'Sin observaciones',
                    contenido: info.descripcion_contenido || '',
                    cambios: info.recoger_cambios ? 'Sí' : 'No',
                    recaudo: info.recaudo_esperado || 0
                };
                const canvas = await renderRotuloToCanvas(datos);
                const imgData = canvas.toDataURL('image/png');
                if (!first) pdf.addPage([100, 100], 'p');
                pdf.addImage(imgData, 'PNG', 0, 0, 100, 100);
                first = false;
            } catch (err) {
                console.error('Error generando guía:', err);
            }
        }

        if (first) {
            alert('No se pudieron generar las guías.');
            return;
        }
        const fecha = new Date().toISOString().slice(0, 10);
        pdf.save(`Guias_${fecha}.pdf`);
    }

    window.descargarGuia = async function(id) {
        try {
            const res = await fetch(`../../controller/misPedidosController.php?action=detalle&id=${id}`);
            const response = await res.json();
            if (!response.success) {
                alert('No se pudo cargar la guía.');
                return;
            }
            const info = response.data.info;
            const datos = {
                guia: info.numero_guia,
                remitente_nombre: info.remitente_nombre || 'EcoBikeMess',
                tienda_nombre: info.nombre_emprendimiento || info.remitente_nombre || 'Tienda',
                destinatario_nombre: info.destinatario_nombre,
                destinatario_direccion: info.direccion_destino,
                destinatario_telefono: info.destinatario_telefono || '',
                destinatario_observaciones: info.instrucciones_entrega || 'Sin observaciones',
                contenido: info.descripcion_contenido || '',
                cambios: info.recoger_cambios ? 'Sí' : 'No',
                recaudo: info.recaudo_esperado || 0
            };
            const canvas = await renderRotuloToCanvas(datos);
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', [100, 100]);
            pdf.addImage(imgData, 'PNG', 0, 0, 100, 100);
            pdf.save(`Guia_${datos.guia}.pdf`);
        } catch (err) {
            console.error(err);
            alert('Error al generar la guía.');
        }
    };
});
