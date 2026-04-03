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
                        <button class="btn btn-sm btn-warning" onclick="cargarRotulo(${f.id})" title="Ver Rótulo" style="margin-right:3px;">🏷️ Rótulo</button>
                        <button class="btn btn-sm btn-info" onclick="verDetalle(${f.id})" title="Ver Detalle">👁️</button>
                        <button class="btn btn-sm btn-secondary" onclick="descargarGuia(${f.id})" title="Descargar PDF">⬇️</button>
                        ${f.estado !== 'entregado' && f.estado !== 'cancelado' ? `<button class="btn btn-sm btn-danger" onclick="cancelarPedido(${f.id}, '${f.numero_guia}')" title="Cancelar Pedido" style="margin-left:3px;">🗑️</button>` : ''}
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
                                            <a href="../../${info.infoEntrega.fotoPrincipal}" target="_blank" rel="noopener noreferrer" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                                <img src="../../${info.infoEntrega.fotoPrincipal}" alt="Foto Principal" style="width: 100%; height: 100%; object-fit: cover;">
                                            </a>
                                        ` : ''}
                                        ${info.infoEntrega.fotoAdicional ? `
                                            <a href="../../${info.infoEntrega.fotoAdicional}" target="_blank" rel="noopener noreferrer" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
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
                                            <a href="../../${info.infoCancelacion.foto}" target="_blank" rel="noopener noreferrer" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
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
        return `
            <div id="rotuloPreview" style="background: white; padding: 12px; border: 1px solid #ccc; font-family: Arial, sans-serif; color: #333; width: 100mm; height: 100mm; box-sizing: border-box;">
                <div style="transform: scale(0.72); transform-origin: top left; width: 139mm; height: 139mm;">
                    <table style="width: 100%; border-bottom: 2px solid #5cb85c; padding-bottom: 6px;">
                        <tr>
                            <td colspan="2">
                                <div style="display: flex; align-items: center; gap: 10px; justify-content: center; text-align: center;">
                                    <img src="/ecobikemess/public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:100px;height:100px;">
                                    <div>
                                        <div style="font-size: 26px; font-weight: 800; color: #5cb85c; line-height: 1;">EcoBikeMess</div>
                                        <div style="margin-top: 3px; font-size: 15px; font-weight: 700; color: #28a745;">Contactanos: 317509298</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 4px;">
                                <div style="font-size: 13px; font-weight: 800; color: #000000;">NUM GUÍA: <span style="font-size: 19px; font-weight: 800; color: #1f2a37;">${datos.guia}</span></div>
                            </td>
                        </tr>
                    </table>

                    <table style="width: 100%; margin-top: 4px; font-size: 12px;">
                        <tr>
                            <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800; border-bottom: 1px solid #eee; padding-bottom: 5px;">📥 Destinatario</h3>
                                <p style="margin: 2px 0; line-height: 1.05;"><strong>Dirección:</strong> <span style="font-size: 15px; font-weight: 700;">${datos.destinatario_direccion || ''}</span></p>
                                <p style="margin: 2px 0; line-height: 1.05;"><strong>Nombre:</strong> <span style="font-size: 15px; font-weight: 700;">${datos.destinatario_nombre || ''}</span></p>
                                <p style="margin: 2px 0; line-height: 1.05;"><strong>Teléfono:</strong> <span style="font-size: 15px; font-weight: 700;">${datos.destinatario_telefono || ''}</span></p>
                                <p style="margin: 2px 0; line-height: 1.05;"><strong>Observaciones:</strong> <span style="font-size: 15px; font-weight: 700;">${datos.destinatario_observaciones || 'Sin observaciones'}</span></p>
                            </td>
                            <td style="width: 4%;"></td>
                            <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800; border-bottom: 1px solid #eee; padding-bottom: 5px;">📤 Remitente</h3>
                                <p style="margin: 2px 0; line-height: 1.05;"><strong>Tienda:</strong> <span style="font-size: 15px; font-weight: 700;">${datos.tienda_nombre || datos.remitente_nombre || 'Tienda'}</span></p>
                            </td>
                        </tr>
                    </table>

                    <table style="width: 100%; margin-top: 4px; padding-top: 0;">
                        <tr>
                            <td style="width: 60%; vertical-align: top; font-size: 12px;">
                                <div style="border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                    <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800; border-bottom: 1px solid #eee; padding-bottom: 5px;">📦 Detalles del Paquete</h3>
                                    <p style="margin: 2px 0; line-height: 1.05;"><strong>Cambios por recoger:</strong> <span style="font-size: 15px; font-weight: 700;">${datos.cambios || 'No'}</span></p>
                                </div>
                                <div style="margin-top: 6px;">
                                    <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800;">💰 Total a Cobrar</h3>
                                    <p style="margin: 2px 0; font-size: 26px; font-weight: 800; color: #28a745; line-height: 1.1;">${formatMoney(datos.recaudo)}</p>
                                </div>
                            </td>
                            <td style="width: 40%; text-align: right; vertical-align: top;">
                                <div id="rotulo_qr_code" style="display: inline-block; width: 220px; height: 220px; margin-right: 6mm; margin-top: -7mm;"></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
    }

    async function renderRotuloToCanvas(datos) {
        const temp = document.createElement('div');
        temp.style.position = 'absolute';
        temp.style.left = '-9999px';
        temp.style.top = '0';
        temp.innerHTML = buildRotuloHtml(datos);
        document.body.appendChild(temp);

        const qrContainer = temp.querySelector('#rotulo_qr_code');
        const totalTexto = formatMoney(datos.recaudo);
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

        await new Promise(r => setTimeout(r, 50));
        const element = temp.querySelector('#rotuloPreview');
        const canvas = await html2canvas(element, { scale: 2, backgroundColor: '#ffffff' });
        document.body.removeChild(temp);
        return canvas;
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
