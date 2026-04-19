document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('facturacionApp');
    if (!app) {
        return;
    }

    const mode = app.dataset.mode || 'cliente';
    const endpoint = app.dataset.endpoint || '';
    const state = {
        rawData: null,
        activePanel: mode === 'admin' ? 'cliente' : mode,
        filters: {
            cliente: { q: '', estado: '', desde: '', hasta: '' },
            mensajero: { q: '', estado: '', desde: '', hasta: '' }
        }
    };

    const money = (value) => new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(Number(value || 0));

    const shortDate = (value) => {
        if (!value) return 'Sin fecha';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('es-CO');
    };

    const statusBadge = (status) => {
        const map = {
            pendiente: ['Pendiente', 'orange'],
            asignado: ['Asignado', 'teal'],
            en_transito: ['En tránsito', 'teal'],
            en_ruta: ['En ruta', 'teal'],
            entregado: ['Entregado', 'green'],
            cancelado: ['Cancelado', 'red'],
            devuelto: ['Devuelto', 'red']
        };
        const item = map[status] || [status || 'Sin estado', 'orange'];
        return `<span class="badge ${item[1]}">${item[0]}</span>`;
    };

    const boolBadge = (value, yesText = 'Sí', noText = 'No') =>
        value
            ? `<span class="badge green">${yesText}</span>`
            : `<span class="badge red">${noText}</span>`;

    const matchesFilter = (item, panel) => {
        const filter = state.filters[panel];
        const text = (filter.q || '').trim().toLowerCase();
        const haystack = [
            item.numero_guia,
            item.destinatario_nombre,
            item.cliente_nombre,
            item.mensajero_nombre
        ].join(' ').toLowerCase();

        if (text && !haystack.includes(text)) {
            return false;
        }

        if (filter.estado && item.estado !== filter.estado) {
            return false;
        }

        const baseDate = item.fecha_entrega || item.fecha_ingreso;
        if (filter.desde && (!baseDate || baseDate.slice(0, 10) < filter.desde)) {
            return false;
        }
        if (filter.hasta && (!baseDate || baseDate.slice(0, 10) > filter.hasta)) {
            return false;
        }

        return true;
    };

    const renderSummary = (summary, panel) => {
        const el = document.getElementById(`summary-${panel}`);
        if (!el || !summary) {
            return;
        }

        const saldoLabel = panel === 'mensajero' ? 'Total a pagar' : 'Saldo actual';
        const saldoNote = panel === 'mensajero'
            ? 'Suma de valores configurados para el mensajero.'
            : 'Recaudos reales menos costo de envíos.';

        el.innerHTML = `
            <div class="summary-card">
                <span class="summary-label">${saldoLabel}</span>
                <div class="summary-value">${money(summary.saldo_actual)}</div>
                <div class="summary-note">${saldoNote}</div>
            </div>
            <div class="summary-card">
                <span class="summary-label">Total valor envíos</span>
                <div class="summary-value">${money(summary.total_envios)}</div>
                <div class="summary-note">Costo acumulado de los paquetes filtrables.</div>
            </div>
            <div class="summary-card">
                <span class="summary-label">Total recaudos</span>
                <div class="summary-value">${money(summary.total_recaudos)}</div>
                <div class="summary-note">Valor real reportado en entregas.</div>
            </div>
            <div class="summary-card">
                <span class="summary-label">Paquetes / entregados</span>
                <div class="summary-value">${summary.cantidad_paquetes} / ${summary.paquetes_entregados}</div>
                <div class="summary-note">Cantidad total y paquetes con entrega finalizada.</div>
            </div>
        `;
    };

    const renderClienteTable = (items) => {
        const tbody = document.getElementById('table-body-cliente');
        if (!tbody) return;

        const filtered = items.filter((item) => matchesFilter(item, 'cliente'));
        document.getElementById('count-cliente').textContent = `${filtered.length} registros`;

        if (!filtered.length) {
            tbody.innerHTML = `<tr><td colspan="10" class="empty-state">No hay registros con los filtros actuales.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtered.map((item) => `
            <tr>
                <td class="mono">${item.numero_guia}</td>
                <td>${item.cliente_nombre || 'Cliente'}</td>
                <td>${item.destinatario_nombre}</td>
                <td>${item.cantidad_paquetes_dia}</td>
                <td>${money(item.valor_envio)}</td>
                <td>${boolBadge(item.agregado_al_recaudo, 'Agregado', 'No agregado')}</td>
                <td>${money(item.valor_recaudo)}</td>
                <td>${money(item.valor_recaudo_real)}</td>
                <td>${statusBadge(item.estado)}</td>
                <td>${shortDate(item.fecha_entrega || item.fecha_ingreso)}</td>
            </tr>
        `).join('');
    };

    const renderMensajeroTable = (items) => {
        const tbody = document.getElementById('table-body-mensajero');
        if (!tbody) return;

        const filtered = items.filter((item) => matchesFilter(item, 'mensajero'));
        document.getElementById('count-mensajero').textContent = `${filtered.length} registros`;

        if (!filtered.length) {
            tbody.innerHTML = `<tr><td colspan="11" class="empty-state">No hay registros con los filtros actuales.</td></tr>`;
            return;
        }

        tbody.innerHTML = filtered.map((item) => {
            const editCell = mode === 'admin'
                ? `
                    <td>
                        <div class="table-tools">
                            <input type="number" min="0" step="100" value="${Math.round(item.valor_pago_mensajero)}" data-role="payment-input" data-id="${item.paquete_id}">
                            <label class="toggle-wrap">
                                <input type="checkbox" data-role="show-toggle" data-id="${item.paquete_id}" ${item.mostrar_al_mensajero ? 'checked' : ''}>
                                Mostrar
                            </label>
                            <button class="fact-btn primary" data-role="save-payment" data-id="${item.paquete_id}">Guardar</button>
                        </div>
                    </td>
                `
                : `<td>${boolBadge(item.mostrar_al_mensajero, 'Visible', 'Oculto')}</td>`;

            return `
                <tr>
                    <td class="mono">${item.numero_guia}</td>
                    <td>${item.mensajero_nombre}</td>
                    <td>${item.cliente_nombre}</td>
                    <td>${item.cantidad_paquetes_dia}</td>
                    <td>${money(item.valor_envio)}</td>
                    <td>${boolBadge(item.agregado_al_recaudo, 'Agregado', 'No agregado')}</td>
                    <td>${money(item.valor_recaudo)}</td>
                    <td>${money(item.valor_recaudo_real)}</td>
                    <td>${statusBadge(item.estado)}</td>
                    <td>${money(item.valor_pago_mensajero)}</td>
                    ${editCell}
                </tr>
            `;
        }).join('');
    };

    const render = () => {
        if (!state.rawData) return;

        if (state.rawData.cliente) {
            renderSummary(state.rawData.cliente.summary, 'cliente');
            renderClienteTable(state.rawData.cliente.items);
        }

        if (state.rawData.mensajero) {
            renderSummary(state.rawData.mensajero.summary, 'mensajero');
            renderMensajeroTable(state.rawData.mensajero.items);
        }
    };

    const setLoading = (message = 'Cargando información...') => {
        const loaders = document.querySelectorAll('[data-loading]');
        loaders.forEach((el) => {
            const colspan = el.id === 'table-body-mensajero' ? 11 : 10;
            el.innerHTML = `<tr><td colspan="${colspan}" class="loading-state">${message}</td></tr>`;
        });
    };

    const fetchData = async () => {
        setLoading();
        const response = await fetch(endpoint, { credentials: 'same-origin' });
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'No fue posible cargar la facturación.');
        }

        state.rawData = result.data;
        render();
    };

    const resetFilters = (panel) => {
        state.filters[panel] = { q: '', estado: '', desde: '', hasta: '' };
        document.querySelectorAll(`[data-panel-filter="${panel}"]`).forEach((input) => {
            input.value = '';
        });
        render();
    };

    const bindFilters = () => {
        document.querySelectorAll('[data-panel-filter]').forEach((input) => {
            input.addEventListener('input', () => {
                const panel = input.dataset.panelFilter;
                const field = input.dataset.filterField;
                state.filters[panel][field] = input.value;
                render();
            });

            input.addEventListener('change', () => {
                const panel = input.dataset.panelFilter;
                const field = input.dataset.filterField;
                state.filters[panel][field] = input.value;
                render();
            });
        });

        document.querySelectorAll('[data-reset-panel]').forEach((btn) => {
            btn.addEventListener('click', () => resetFilters(btn.dataset.resetPanel));
        });
    };

    const bindTabs = () => {
        document.querySelectorAll('[data-switch-panel]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const panel = btn.dataset.switchPanel;
                state.activePanel = panel;
                document.querySelectorAll('[data-switch-panel]').forEach((item) => item.classList.remove('active'));
                document.querySelectorAll('[data-panel]').forEach((item) => item.classList.add('panel-hidden'));
                btn.classList.add('active');
                document.querySelector(`[data-panel="${panel}"]`).classList.remove('panel-hidden');
            });
        });
    };

    const savePayment = async (paqueteId) => {
        const paymentInput = document.querySelector(`[data-role="payment-input"][data-id="${paqueteId}"]`);
        const toggleInput = document.querySelector(`[data-role="show-toggle"][data-id="${paqueteId}"]`);

        const formData = new FormData();
        formData.append('action', 'actualizar_pago_mensajero');
        formData.append('paquete_id', paqueteId);
        formData.append('valor_pago_mensajero', paymentInput ? paymentInput.value : '7000');
        formData.append('mostrar_al_mensajero', toggleInput && toggleInput.checked ? '1' : '0');

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'No se pudo actualizar el pago.');
        }

        state.rawData = result.data;
        render();
    };

    const bindAdminActions = () => {
        if (mode !== 'admin') return;

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-role="save-payment"]');
            if (!button) {
                return;
            }

            const paqueteId = button.dataset.id;
            const originalText = button.textContent;
            button.textContent = 'Guardando...';
            button.disabled = true;

            try {
                await savePayment(paqueteId);
                button.textContent = 'Guardado';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 900);
            } catch (error) {
                button.textContent = 'Error';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 1200);
                alert(error.message);
            }
        });
    };

    bindFilters();
    bindTabs();
    bindAdminActions();
    fetchData().catch((error) => {
        document.querySelectorAll('[data-loading]').forEach((el) => {
            const colspan = el.id === 'table-body-mensajero' ? 11 : 10;
            el.innerHTML = `<tr><td colspan="${colspan}" class="empty-state">${error.message}</td></tr>`;
        });
    });
});
