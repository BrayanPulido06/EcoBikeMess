document.addEventListener('DOMContentLoaded', function () {
    const API = '../../controller/misPedidosMensajeroController.php';
    const listEl = document.getElementById('pedidosList');
    const resultsCountEl = document.getElementById('resultsCount');

    const filters = {
        search: document.getElementById('searchInput'),
        estado: document.getElementById('filtroEstado'),
        fechaDesde: document.getElementById('filtroFechaDesde'),
        fechaHasta: document.getElementById('filtroFechaHasta')
    };

    const detalleModal = document.getElementById('detalleModal');
    const detalleContent = document.getElementById('detalleContent');
    const detalleClose = document.getElementById('detalleClose');
    const detalleBackdrop = document.getElementById('detalleBackdrop');

    const rotuloModal = document.getElementById('rotuloModal');
    const rotuloPreview = document.getElementById('rotuloPreview');
    const rotuloClose = document.getElementById('closeRotuloModal');
    const rotuloBackdrop = document.getElementById('rotuloBackdrop');
    const btnDownloadRotulo = document.getElementById('btnDownloadRotulo');

    let currentRotuloData = null;

    document.getElementById('btnBuscar')?.addEventListener('click', cargarTodo);
    document.getElementById('btnLimpiar')?.addEventListener('click', function () {
        Object.values(filters).forEach(el => {
            if (el) el.value = '';
        });
        cargarTodo();
    });

    filters.search?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            cargarTodo();
        }
    });

    detalleClose?.addEventListener('click', cerrarDetalle);
    detalleBackdrop?.addEventListener('click', cerrarDetalle);
    rotuloClose?.addEventListener('click', cerrarRotulo);
    rotuloBackdrop?.addEventListener('click', cerrarRotulo);
    btnDownloadRotulo?.addEventListener('click', descargarRotuloActual);

    async function cargarTodo() {
        await Promise.all([cargarEstadisticas(), cargarPedidos()]);
    }

    function buildParams(action, extra = {}) {
        const params = new URLSearchParams();
        params.set('action', action);

        Object.entries(filters).forEach(([key, input]) => {
            if (input && input.value) {
                params.set(key, input.value);
            }
        });

        Object.entries(extra).forEach(([key, value]) => {
            params.set(key, value);
        });

        return params.toString();
    }

    async function cargarEstadisticas() {
        try {
            const response = await fetch(`${API}?${buildParams('estadisticas')}`);
            const json = await response.json();
            if (!json.success || !json.data) return;

            document.getElementById('statTotal').textContent = json.data.total ?? 0;
            document.getElementById('statPendientes').textContent = json.data.pendientes ?? 0;
            document.getElementById('statEntregados').textContent = json.data.entregados ?? 0;
            document.getElementById('statCancelados').textContent = json.data.cancelados ?? 0;
        } catch (error) {
            console.error('Error cargando estadisticas:', error);
        }
    }

    async function cargarPedidos() {
        if (!listEl) return;
        listEl.innerHTML = '<div class="empty-state">Cargando pedidos...</div>';

        try {
            const response = await fetch(`${API}?${buildParams('listar')}`);
            const json = await response.json();

            if (!json.success) {
                listEl.innerHTML = `<div class="empty-state">${escapeHtml(json.message || 'No fue posible cargar los pedidos.')}</div>`;
                return;
            }

            renderPedidos(json.data || []);
        } catch (error) {
            console.error('Error cargando pedidos:', error);
            listEl.innerHTML = '<div class="empty-state">Error de conexion al cargar pedidos.</div>';
        }
    }

    function renderPedidos(rows) {
        if (!listEl) return;
        resultsCountEl.textContent = `${rows.length} resultados`;

        if (!rows.length) {
            listEl.innerHTML = '<div class="empty-state">No tienes pedidos creados con esos filtros.</div>';
            return;
        }

        listEl.innerHTML = rows.map(row => `
            <article class="pedido-item">
                <div class="pedido-top">
                    <div>
                        <div class="pedido-guia">${escapeHtml(row.numero_guia || '')}</div>
                        <div class="pedido-fecha">${formatDate(row.fecha_creacion)}</div>
                    </div>
                    <span class="estado-chip ${estadoClass(row.estado)}">${escapeHtml((row.estado || '').toUpperCase())}</span>
                </div>

                <div class="pedido-grid">
                    <div class="pedido-field">
                        <span>Destinatario</span>
                        <strong>${escapeHtml(row.destinatario_nombre || 'Sin nombre')}</strong>
                    </div>
                    <div class="pedido-field">
                        <span>Direccion</span>
                        <strong>${escapeHtml(row.direccion_destino || 'Sin direccion')}</strong>
                    </div>
                    <div class="pedido-field">
                        <span>Costo envio</span>
                        <strong>${formatCurrency(row.costo_envio || 0)}</strong>
                    </div>
                    <div class="pedido-field">
                        <span>Recaudo</span>
                        <strong>${formatCurrency(row.recaudo_esperado || 0)}</strong>
                    </div>
                    <div class="pedido-field">
                        <span>Tipo servicio</span>
                        <strong>${escapeHtml(row.tipo_servicio || 'entrega_simple')}</strong>
                    </div>
                    <div class="pedido-field">
                        <span>Mensajero asignado</span>
                        <strong>${escapeHtml(row.mensajero_asignado || 'Sin asignar')}</strong>
                    </div>
                </div>

                <div class="pedido-actions">
                    <button type="button" class="btn-rotulo" data-action="rotulo" data-id="${row.id}">Ver rotulo</button>
                    <button type="button" class="btn-detalle" data-action="detalle" data-id="${row.id}">Ver detalle</button>
                </div>
            </article>
        `).join('');

        listEl.querySelectorAll('[data-action="detalle"]').forEach(btn => {
            btn.addEventListener('click', function () {
                abrirDetalle(Number(btn.dataset.id));
            });
        });

        listEl.querySelectorAll('[data-action="rotulo"]').forEach(btn => {
            btn.addEventListener('click', function () {
                cargarRotulo(Number(btn.dataset.id), btn);
            });
        });
    }

    async function abrirDetalle(id) {
        try {
            const response = await fetch(`${API}?${buildParams('detalle', { id })}`);
            const json = await response.json();

            if (!json.success || !json.data) return;

            const d = json.data;
            detalleContent.innerHTML = `
                <div class="invoice-header" style="border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                    <div style="display:flex; justify-content:space-between; gap: 16px; flex-wrap: wrap;">
                        <div>
                            <h3 style="margin:0; color:#2c3e50;">Guia ${escapeHtml(d.numero_guia || '')}</h3>
                            <p style="margin:5px 0; color:#7f8c8d;">Estado: <strong>${escapeHtml((d.estado || '').toUpperCase())}</strong></p>
                        </div>
                        <div class="text-right">
                            <p style="margin:0;"><strong>Fecha:</strong> ${rawDateText(d.fecha_creacion)}</p>
                        </div>
                    </div>
                </div>
                
                <table class="table" style="width:100%; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td style="padding:8px;"><strong>Destinatario:</strong></td>
                            <td style="padding:8px;">${escapeHtml(d.destinatario_nombre || '')}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px;"><strong>Direccion:</strong></td>
                            <td style="padding:8px;">${escapeHtml(d.direccion_destino || '')}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px;"><strong>Telefono:</strong></td>
                            <td style="padding:8px;">${escapeHtml(d.destinatario_telefono || '')}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px;"><strong>Remitente:</strong></td>
                            <td style="padding:8px;">${escapeHtml(d.nombre_emprendimiento || d.remitente_nombre || 'Sin remitente')}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px;"><strong>Contenido:</strong></td>
                            <td style="padding:8px;">${escapeHtml(d.descripcion_contenido || '')}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px;"><strong>Observaciones:</strong></td>
                            <td style="padding:8px;">${escapeHtml(d.instrucciones_entrega || 'Sin observaciones')}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px;"><strong>Cambios por recoger:</strong></td>
                            <td style="padding:8px;">${normalizeYesNo(d.recoger_cambios)}</td>
                        </tr>
                        <tr style="background:#f8f9fa;">
                            <td style="padding:8px;"><strong>Total Recaudado:</strong></td>
                            <td style="padding:8px;"><strong>${d.estado === 'entregado' && d.infoEntrega ? formatCurrency(d.infoEntrega.recaudo) : 'Pendiente'}</strong></td>
                        </tr>
                    </tbody>
                </table>

                ${d.estado === 'entregado' && d.infoEntrega ? `
                    <div class="detalle-section" style="margin-top: 20px;">
                        <h3 style="margin:0 0 14px; color:#4f6df5; border-bottom:2px solid #4f6df5; padding-bottom:10px;">✅ Detalles de Entrega</h3>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
                            ${buildDetailCard('Recibió', d.infoEntrega.nombreRecibe || 'N/A')}
                            ${buildDetailCard('Parentesco', d.infoEntrega.parentesco || 'N/A')}
                            ${buildDetailCard('Documento', d.infoEntrega.documento || 'N/A')}
                            ${buildDetailCard('Fecha de Entrega', formatDate(d.infoEntrega.fecha))}
                            ${buildDetailCard('Recaudo Realizado', formatCurrency(d.infoEntrega.recaudo || 0))}
                            <div style="background:#f8f9fb; border-radius:12px; padding:14px; grid-column:1 / -1;">
                                <div style="color:#5b6777; font-size:0.9rem; margin-bottom:6px;">Observaciones de Entrega</div>
                                <div style="color:#263849; font-weight:600;">${escapeHtml(d.infoEntrega.observaciones || 'Sin observaciones.')}</div>
                            </div>
                        </div>
                    </div>

                    <div class="detalle-section" style="margin-top: 20px;">
                        <h3 style="margin:0 0 14px; color:#4f6df5; border-bottom:2px solid #4f6df5; padding-bottom:10px;">📸 Evidencia Fotográfica</h3>
                        <div class="fotos-evidencia-container" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                            ${buildPhotoLink(d.infoEntrega.fotoPrincipal, 'Foto Principal')}
                            ${buildPhotoLink(d.infoEntrega.fotoAdicional, 'Foto Adicional')}
                            ${!d.infoEntrega.fotoPrincipal && !d.infoEntrega.fotoAdicional ? '<p class="text-muted" style="width: 100%; text-align: center;">No hay fotos de evidencia disponibles.</p>' : ''}
                        </div>
                    </div>
                ` : ''}

                ${d.estado === 'cancelado' ? `
                    <div class="detalle-section" style="margin-top: 20px; background-color: #fff5f5; border: 1px solid #f5c2c7; border-radius: 12px; padding: 14px;">
                        <h3 style="margin:0 0 14px; color: #b02a37;">Detalles de Cancelacion</h3>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
                            ${buildDetailCard('Motivo', d.infoCancelacion ? (d.infoCancelacion.motivo || 'Sin informacion.') : 'Sin informacion.')}
                            ${buildDetailCard('Fecha', d.infoCancelacion ? formatDate(d.infoCancelacion.fecha) : 'Sin informacion.')}
                        </div>
                    </div>

                    <div class="detalle-section" style="margin-top: 20px;">
                        <h3 style="margin:0 0 14px; color:#4f6df5; border-bottom:2px solid #4f6df5; padding-bottom:10px;">📸 Evidencia Fotográfica</h3>
                        <div class="fotos-evidencia-container" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                            ${d.infoCancelacion && d.infoCancelacion.foto ? buildPhotoLink(d.infoCancelacion.foto, 'Foto Evidencia') : '<p class="text-muted" style="width: 100%; text-align: center;">No hay fotos de evidencia disponibles.</p>'}
                        </div>
                    </div>
                ` : ''}
            `;
            detalleModal.style.display = 'flex';
        } catch (error) {
            console.error('Error cargando detalle:', error);
        }
    }

    async function cargarRotulo(id, button) {
        const originalText = button?.textContent || 'Ver rotulo';
        if (button) {
            button.textContent = 'Cargando...';
            button.disabled = true;
        }

        try {
            const response = await fetch(`${API}?${buildParams('detalle', { id })}`);
            const json = await response.json();

            if (!json.success || !json.data) {
                alert(json.message || 'No se pudo cargar el rotulo.');
                return;
            }

            currentRotuloData = {
                guia: json.data.numero_guia,
                remitente_nombre: json.data.remitente_nombre || 'EcoBikeMess',
                tienda_nombre: json.data.nombre_emprendimiento || json.data.remitente_nombre || 'Tienda',
                destinatario_nombre: json.data.destinatario_nombre,
                destinatario_direccion: json.data.direccion_destino,
                destinatario_telefono: json.data.destinatario_telefono || '',
                destinatario_observaciones: json.data.instrucciones_entrega || 'Sin observaciones',
                cambios: json.data.recoger_cambios,
                recaudo: json.data.recaudo_esperado || 0
            };

            if (!window.RotuloEcoBike || !rotuloPreview) {
                alert('El generador de rotulos no esta disponible.');
                return;
            }

            await window.RotuloEcoBike.mountPreview(rotuloPreview, currentRotuloData);
            rotuloModal.style.display = 'block';
        } catch (error) {
            console.error('Error cargando rotulo:', error);
            alert('Error de conexion al cargar el rotulo.');
        } finally {
            if (button) {
                button.textContent = originalText;
                button.disabled = false;
            }
        }
    }

    async function descargarRotuloActual() {
        if (!currentRotuloData || !window.RotuloEcoBike) {
            alert('Primero abre un rotulo.');
            return;
        }

        try {
            await window.RotuloEcoBike.downloadPdf(currentRotuloData, { filePrefix: 'Rotulo' });
        } catch (error) {
            console.error('Error descargando rotulo:', error);
            alert('No se pudo generar el PDF del rotulo.');
        }
    }

    function cerrarDetalle() {
        if (detalleModal) detalleModal.style.display = 'none';
    }

    function cerrarRotulo() {
        if (rotuloModal) rotuloModal.style.display = 'none';
    }

    function formatCurrency(value) {
        const number = Number(value || 0);
        return `$${number.toLocaleString('es-CO')}`;
    }

    function formatDate(value) {
        if (!value) return 'Sin fecha';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleString('es-CO');
    }

    function normalizeYesNo(value) {
        const text = String(value ?? '').trim().toLowerCase();
        if (['1', 'si', 'sí', 'true', 'x', 'yes'].includes(text)) return 'Si';
        return 'No';
    }

    function rawDateText(value) {
        if (!value) return 'Sin fecha';
        return String(value).replace('T', ' ').substring(0, 19);
    }

    function buildDetailCard(label, value) {
        return `
            <div style="background:#f8f9fb; border-radius:12px; padding:14px;">
                <div style="color:#5b6777; font-size:0.9rem; margin-bottom:6px;">${escapeHtml(label)}</div>
                <div style="color:#263849; font-weight:600;">${escapeHtml(value)}</div>
            </div>
        `;
    }

    function buildPhotoLink(path, alt) {
        if (!path) return '';
        const safeAlt = escapeHtml(alt);
        const relativePath = String(path).replace(/^(\.\.\/|\.\/|\/)+/, '');
        const safePath = `../../${relativePath}`;
        return `
            <a href="${safePath}" class="js-image-lightbox" data-lightbox-src="${safePath}" data-lightbox-alt="${safeAlt}" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <img src="${safePath}" alt="${safeAlt}" style="width: 100%; height: 100%; object-fit: cover;">
            </a>
        `;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function estadoClass(estado) {
        const normalized = String(estado || '').toLowerCase();
        return `estado-${normalized || 'pendiente'}`;
    }

    cargarTodo();
});
