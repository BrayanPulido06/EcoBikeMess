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

    const modal = document.getElementById('detalleModal');
    const modalContent = document.getElementById('detalleContent');
    const modalClose = document.getElementById('detalleClose');
    const modalBackdrop = document.getElementById('detalleBackdrop');

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

    modalClose?.addEventListener('click', cerrarModal);
    modalBackdrop?.addEventListener('click', cerrarModal);

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
                listEl.innerHTML = `<div class="empty-state">${json.message || 'No fue posible cargar los pedidos.'}</div>`;
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
                    <button type="button" class="btn-detalle" data-id="${row.id}">Ver detalle</button>
                </div>
            </article>
        `).join('');

        listEl.querySelectorAll('.btn-detalle').forEach(btn => {
            btn.addEventListener('click', function () {
                abrirDetalle(Number(btn.dataset.id));
            });
        });
    }

    async function abrirDetalle(id) {
        try {
            const response = await fetch(`${API}?${buildParams('detalle', { id })}`);
            const json = await response.json();

            if (!json.success || !json.data) {
                return;
            }

            const d = json.data;
            modalContent.innerHTML = `
                <div class="detalle-row"><span>Guia</span><strong>${escapeHtml(d.numero_guia || '')}</strong></div>
                <div class="detalle-row"><span>Estado</span><strong>${escapeHtml(d.estado || '')}</strong></div>
                <div class="detalle-row"><span>Remitente</span><strong>${escapeHtml(d.remitente_nombre || '')}</strong></div>
                <div class="detalle-row"><span>Destinatario</span><strong>${escapeHtml(d.destinatario_nombre || '')}</strong></div>
                <div class="detalle-row"><span>Telefono destinatario</span><strong>${escapeHtml(d.destinatario_telefono || '')}</strong></div>
                <div class="detalle-row"><span>Direccion destino</span><strong>${escapeHtml(d.direccion_destino || '')}</strong></div>
                <div class="detalle-row"><span>Instrucciones</span><strong>${escapeHtml(d.instrucciones_entrega || 'Sin instrucciones')}</strong></div>
                <div class="detalle-row"><span>Contenido</span><strong>${escapeHtml(d.descripcion_contenido || 'Sin descripcion')}</strong></div>
                <div class="detalle-row"><span>Costo envio</span><strong>${formatCurrency(d.costo_envio || 0)}</strong></div>
                <div class="detalle-row"><span>Recaudo esperado</span><strong>${formatCurrency(d.recaudo_esperado || 0)}</strong></div>
                <div class="detalle-row"><span>Fecha creacion</span><strong>${formatDate(d.fecha_creacion)}</strong></div>
            `;
            modal.style.display = 'block';
        } catch (error) {
            console.error('Error cargando detalle:', error);
        }
    }

    function cerrarModal() {
        if (modal) modal.style.display = 'none';
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
