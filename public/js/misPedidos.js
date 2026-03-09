document.addEventListener('DOMContentLoaded', function() {
    console.log('Script facturacionCliente.js cargado');

    // --- REFERENCIAS DOM ---
    const tableBody = document.getElementById('tablaFacturasBody');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const btnExportExcel = document.getElementById('btnExportarExcel');
    
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
                        <button class="btn btn-sm btn-secondary" title="Descargar PDF">⬇️</button>
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
                                        <td style="padding:8px;"><strong>Costo Envío:</strong></td>
                                        <td style="padding:8px;"><strong>${formatCurrency(info.costo_envio)}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
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
});
