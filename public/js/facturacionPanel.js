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
        },
        clienteGroups: [],
        selectedClienteGroupKey: null,
        activeClientModalView: 'detail'
    };

    const money = (value) => new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Number(value || 0));

    const shortDate = (value) => {
        if (!value) return 'Sin fecha';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('es-CO');
    };

    const dateKeyFromValue = (value) => {
        if (!value) return 'sin-fecha';
        return String(value).slice(0, 10);
    };

    const normalizeText = (value) => String(value || '').trim().toLowerCase();

    const getRecaudoRealValue = (item) => Number(item?.valor_recaudo_real || 0);

    const getClienteAbonos = () => {
        const abonos = state.rawData?.cliente?.abonos;
        return Array.isArray(abonos) ? abonos : [];
    };

    const getGroupAbonos = (clienteId, fechaGrupo) => getClienteAbonos()
        .filter((abono) => Number(abono.cliente_id) === Number(clienteId) && String(abono.fecha_grupo) === String(fechaGrupo));

    const getGroupAbonoTotal = (clienteId, fechaGrupo) => getGroupAbonos(clienteId, fechaGrupo)
        .reduce((sum, abono) => sum + Number(abono.monto || 0), 0);

    const clientDisplayName = (item) => {
        const contact = normalizeText(item.cliente_contacto) ? String(item.cliente_contacto).trim() : '';
        const business = normalizeText(item.cliente_nombre) ? String(item.cliente_nombre).trim() : '';

        if (contact && business && contact.toLowerCase() !== business.toLowerCase()) {
            return `${contact} - ${business}`;
        }

        return contact || business || 'Cliente';
    };

    const statusBadge = (status) => {
        const map = {
            pendiente: ['Pendiente', 'orange'],
            asignado: ['Asignado', 'teal'],
            en_transito: ['En transito', 'teal'],
            en_ruta: ['En ruta', 'teal'],
            entregado: ['Entregado', 'green'],
            cancelado: ['Cancelado', 'red'],
            devuelto: ['Devuelto', 'red']
        };
        const item = map[status] || [status || 'Sin estado', 'orange'];
        return `<span class="badge ${item[1]}">${item[0]}</span>`;
    };

    const boolBadge = (value, yesText = 'Si', noText = 'No') =>
        value
            ? `<span class="badge green">${yesText}</span>`
            : `<span class="badge red">${noText}</span>`;

    const escapeHtml = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const matchesFilter = (item, panel) => {
        const filter = state.filters[panel];
        const text = normalizeText(filter.q);
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
            : 'Recaudos reales menos costo de envios.';

        el.innerHTML = `
            <div class="summary-card">
                <span class="summary-label">${saldoLabel}</span>
                <div class="summary-value">${money(summary.saldo_actual)}</div>
                <div class="summary-note">${saldoNote}</div>
            </div>
            <div class="summary-card">
                <span class="summary-label">Total valor envios</span>
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

    const buildClienteSummaryFromGroups = (baseSummary, groups) => {
        const totalsByClient = new Map();

        groups.forEach((group) => {
            totalsByClient.set(group.clientKey, Number(group.totalAcumulado || 0));
        });

        return {
            ...baseSummary,
            saldo_actual: Array.from(totalsByClient.values()).reduce((sum, value) => sum + value, 0)
        };
    };

    const buildClienteGroups = (items) => {
        const filtered = items.filter((item) => matchesFilter(item, 'cliente'));
        const groupsMap = new Map();

        filtered.forEach((item) => {
            const baseDate = item.fecha_entrega || item.fecha_ingreso;
            const dateKey = dateKeyFromValue(baseDate);
            const displayName = clientDisplayName(item);
            const clientKey = normalizeText(displayName || 'cliente');
            const groupKey = `${dateKey}__${clientKey}`;

            if (!groupsMap.has(groupKey)) {
                groupsMap.set(groupKey, {
                    key: groupKey,
                    dateKey,
                    clientKey,
                    fechaLabel: shortDate(baseDate),
                    clienteNombre: displayName,
                    clienteId: Number(item.cliente_id || 0),
                    paquetesEntregados: 0,
                    totalServicio: 0,
                    totalRecaudado: 0,
                    abono: 0,
                    saldo: 0,
                    balance: 0,
                    totalAcumulado: 0,
                    packages: [],
                    statuses: new Set()
                });
            }

            const group = groupsMap.get(groupKey);
            if (item.estado === 'entregado') {
                group.paquetesEntregados += 1;
                group.totalServicio += Number(item.valor_envio || 0);
                group.totalRecaudado += getRecaudoRealValue(item);
                group.packages.push(item);
            }
            group.statuses.add(item.estado || 'pendiente');
        });

        const groups = Array.from(groupsMap.values())
            .map((group) => {
                const abonos = getGroupAbonos(group.clienteId, group.dateKey);
                const abono = abonos.reduce((sum, item) => sum + Number(item.monto || 0), 0);
                const saldo = Math.max(group.totalServicio - abono, 0);
                return {
                    ...group,
                    abonos,
                    abono,
                    balance: saldo,
                    saldo,
                    estado: saldo <= 0 ? 'pagado' : 'pendiente'
                };
            })
            .sort((a, b) => {
                if (a.dateKey === b.dateKey) {
                    return a.clienteNombre.localeCompare(b.clienteNombre, 'es', { sensitivity: 'base' });
                }
                return b.dateKey.localeCompare(a.dateKey);
            });

        const runningTotalsByClient = new Map();
        const groupsAsc = [...groups].sort((a, b) => {
            if (a.clientKey === b.clientKey) {
                if (a.dateKey === b.dateKey) return a.key.localeCompare(b.key, 'es', { sensitivity: 'base' });
                return a.dateKey.localeCompare(b.dateKey);
            }
            return a.clientKey.localeCompare(b.clientKey, 'es', { sensitivity: 'base' });
        });

        groupsAsc.forEach((group) => {
            const previous = Number(runningTotalsByClient.get(group.clientKey) || 0);
            const current = Math.max(previous + Number(group.totalServicio || 0) - Number(group.abono || 0), 0);
            runningTotalsByClient.set(group.clientKey, current);
            group.totalAcumulado = current;
            group.totalAcumuladoEstado = current <= 0 ? 'pagado' : 'pendiente';
            group.estado = current <= 0 ? 'pagado' : 'pendiente';
        });

        return groups;
    };

    const clientGroupStatusBadge = (status) => {
        if (status === 'pagado') {
            return '<span class="status-chip paid">Pagado</span>';
        }
        return '<span class="status-chip pending">Pendiente</span>';
    };

    const amountCellClass = (status) => {
        if (status === 'pagado') return 'is-paid';
        return 'is-pending';
    };

    const renderClienteTable = (items) => {
        const tbody = document.getElementById('table-body-cliente');
        if (!tbody) return;

        const groups = buildClienteGroups(items);
        state.clienteGroups = groups;
        document.getElementById('count-cliente').textContent = `${groups.length} registros`;

        if (!groups.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="empty-state">No hay registros con los filtros actuales.</td></tr>';
            return;
        }

        tbody.innerHTML = groups.map((group) => `
            <tr>
                <td>${group.fechaLabel}</td>
                <td>${escapeHtml(group.clienteNombre)}</td>
                <td>${group.paquetesEntregados}</td>
                <td>${money(group.totalServicio)}</td>
                <td>${money(group.totalRecaudado)}</td>
                <td>${money(group.abono)}</td>
                <td>${clientGroupStatusBadge(group.estado)}</td>
                <td class="amount-cell ${amountCellClass(group.estado)}">${money(group.saldo)}</td>
                <td class="amount-cell ${amountCellClass(group.totalAcumuladoEstado)}">${money(group.totalAcumulado)}</td>
                <td>
                    <div class="table-tools">
                        <button
                            type="button"
                            class="fact-btn tertiary detail-trigger"
                            data-role="open-client-detail"
                            data-group-key="${escapeHtml(group.key)}"
                        >
                            Ver paquetes
                        </button>
                        ${mode === 'admin' ? `
                            <button
                                type="button"
                                class="fact-btn secondary detail-trigger"
                                data-role="open-client-abono"
                                data-group-key="${escapeHtml(group.key)}"
                            >
                                Registrar abono
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    };

    const renderMensajeroTable = (items) => {
        const tbody = document.getElementById('table-body-mensajero');
        if (!tbody) return;

        const filtered = items.filter((item) => matchesFilter(item, 'mensajero'));
        document.getElementById('count-mensajero').textContent = `${filtered.length} registros`;

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="11" class="empty-state">No hay registros con los filtros actuales.</td></tr>';
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
                    <td class="mono">${escapeHtml(item.numero_guia)}</td>
                    <td>${escapeHtml(item.mensajero_nombre)}</td>
                    <td>${escapeHtml(item.cliente_nombre)}</td>
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
            renderClienteTable(state.rawData.cliente.items);
            renderSummary(buildClienteSummaryFromGroups(state.rawData.cliente.summary, state.clienteGroups), 'cliente');
        }

        if (state.rawData.mensajero) {
            renderSummary(state.rawData.mensajero.summary, 'mensajero');
            renderMensajeroTable(state.rawData.mensajero.items);
        }
    };

    const setLoading = (message = 'Cargando informacion...') => {
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
            throw new Error(result.message || 'No fue posible cargar la facturacion.');
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

    const saveClientAbono = async (form) => {
        const formData = new FormData();
        formData.append('action', 'registrar_abono_cliente');
        formData.append('cliente_id', form.cliente_id.value);
        formData.append('fecha_grupo', form.fecha_grupo.value);
        formData.append('monto', form.monto.value);
        formData.append('metodo_pago', form.metodo_pago.value);
        formData.append('observaciones', form.observaciones.value || '');

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'No se pudo registrar el abono.');
        }

        state.rawData = result.data;
        render();
        if (state.selectedClienteGroupKey) {
            openClientAbonoModal(state.selectedClienteGroupKey);
        }
    };

    const getClienteGroupByKey = (groupKey) => state.clienteGroups.find((group) => group.key === groupKey) || null;

    const renderPackageCard = (item) => {
        const recaudoReal = getRecaudoRealValue(item);
        const saldo = Math.max(Number(item.valor_envio || 0), 0);
        return `
            <article class="package-card">
                <div class="package-card-head">
                    <div>
                        <h3>${escapeHtml(item.numero_guia)}</h3>
                        <p>${escapeHtml(item.destinatario_nombre || 'Sin destinatario')}</p>
                    </div>
                    ${statusBadge(item.estado)}
                </div>
                <div class="package-card-grid">
                    <div class="package-data">
                        <span class="package-label">Direccion</span>
                        <strong>${escapeHtml(item.direccion_destino || 'Sin direccion')}</strong>
                    </div>
                    <div class="package-data">
                        <span class="package-label">Valor envio</span>
                        <strong>${money(item.valor_envio)}</strong>
                    </div>
                    <div class="package-data">
                        <span class="package-label">Valor recaudado</span>
                        <strong>${money(recaudoReal)}</strong>
                    </div>
                    <div class="package-data">
                        <span class="package-label">Saldo</span>
                        <strong>${money(saldo)}</strong>
                    </div>
                    <div class="package-data">
                        <span class="package-label">Fecha</span>
                        <strong>${shortDate(item.fecha_entrega || item.fecha_ingreso)}</strong>
                    </div>
                    <div class="package-data package-full">
                        <span class="package-label">Instrucciones</span>
                        <strong>${escapeHtml(item.instrucciones_entrega || 'Sin instrucciones')}</strong>
                    </div>
                </div>
            </article>
        `;
    };

    const renderAbonoHistory = (group) => {
        if (!group.abonos.length) {
            return '<div class="empty-state">Aun no hay abonos registrados para este dia.</div>';
        }

        return `
            <div class="package-list">
                ${group.abonos.map((abono) => `
                    <article class="package-card">
                        <div class="package-card-grid">
                            <div class="package-data">
                                <span class="package-label">Monto</span>
                                <strong>${money(abono.monto)}</strong>
                            </div>
                            <div class="package-data">
                                <span class="package-label">Metodo</span>
                                <strong>${escapeHtml(abono.metodo_pago)}</strong>
                            </div>
                            <div class="package-data">
                                <span class="package-label">Fecha de registro</span>
                                <strong>${shortDate(abono.fecha_registro)}</strong>
                            </div>
                            <div class="package-data">
                                <span class="package-label">Registrado por</span>
                                <strong>${escapeHtml(abono.registrado_por_nombre || 'Sistema')}</strong>
                            </div>
                            <div class="package-data package-full">
                                <span class="package-label">Observaciones</span>
                                <strong>${escapeHtml(abono.observaciones || 'Sin observaciones')}</strong>
                            </div>
                        </div>
                    </article>
                `).join('')}
            </div>
        `;
    };

    const openClientDetailModal = (groupKey) => {
        const group = getClienteGroupByKey(groupKey);
        const modal = document.getElementById('facturacionDetailModal');
        const title = document.getElementById('facturacionDetailTitle');
        const subtitle = document.getElementById('facturacionDetailSubtitle');
        const body = document.getElementById('facturacionDetailBody');

        if (!group || !modal || !title || !subtitle || !body) {
            return;
        }

        state.selectedClienteGroupKey = groupKey;
        state.activeClientModalView = 'detail';
        title.textContent = `${group.clienteNombre} - ${group.fechaLabel}`;
        subtitle.textContent = `${group.paquetesEntregados} entregado(s) | Servicio ${money(group.totalServicio)} | Recaudo ${money(group.totalRecaudado)} | Abono ${money(group.abono)} | Saldo ${money(group.saldo)} | Total ${money(group.totalAcumulado)}`;

        body.innerHTML = `
            <div class="detail-summary-strip">
                <div><span>Entregados</span><strong>${group.paquetesEntregados}</strong></div>
                <div><span>Recaudado</span><strong>${money(group.totalRecaudado)}</strong></div>
                <div><span>Abono</span><strong>${money(group.abono)}</strong></div>
                <div><span>Estado</span><strong>${group.estado === 'pagado' ? 'Pagado' : 'Pendiente'}</strong></div>
                <div><span>Total acumulado</span><strong>${money(group.totalAcumulado)}</strong></div>
            </div>
            <div class="package-list">
                ${group.packages.map(renderPackageCard).join('')}
            </div>
        `;

        modal.classList.remove('modal-hidden');
        modal.setAttribute('aria-hidden', 'false');
    };

    const openClientAbonoModal = (groupKey) => {
        const group = getClienteGroupByKey(groupKey);
        const modal = document.getElementById('facturacionDetailModal');
        const title = document.getElementById('facturacionDetailTitle');
        const subtitle = document.getElementById('facturacionDetailSubtitle');
        const body = document.getElementById('facturacionDetailBody');

        if (!group || !modal || !title || !subtitle || !body) {
            return;
        }

        state.selectedClienteGroupKey = groupKey;
        state.activeClientModalView = 'abono';
        title.textContent = `Registrar abono - ${group.clienteNombre}`;
        subtitle.textContent = `Fecha ${group.fechaLabel} | Servicio ${money(group.totalServicio)} | Abonado ${money(group.abono)} | Total pendiente ${money(group.totalAcumulado)}`;

        body.innerHTML = `
            <div class="detail-summary-strip">
                <div><span>Total servicio</span><strong>${money(group.totalServicio)}</strong></div>
                <div><span>Total recaudado</span><strong>${money(group.totalRecaudado)}</strong></div>
                <div><span>Abonado</span><strong>${money(group.abono)}</strong></div>
                <div><span>Saldo del dia</span><strong>${money(group.saldo)}</strong></div>
                <div><span>Total acumulado</span><strong>${money(group.totalAcumulado)}</strong></div>
            </div>
            <form id="clienteAbonoForm" class="facturacion-abono-form">
                <input type="hidden" name="cliente_id" value="${group.clienteId}">
                <input type="hidden" name="fecha_grupo" value="${escapeHtml(group.dateKey)}">
                <div class="facturacion-abono-grid">
                    <label class="facturacion-field">
                        <span>Monto del abono</span>
                        <input type="number" name="monto" min="1" step="100" placeholder="100000" required>
                    </label>
                    <label class="facturacion-field">
                        <span>Metodo de pago</span>
                        <select name="metodo_pago" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </label>
                    <label class="facturacion-field facturacion-field-full">
                        <span>Observaciones</span>
                        <textarea name="observaciones" rows="3" placeholder="Detalle del abono"></textarea>
                    </label>
                </div>
                <div class="facturacion-abono-actions">
                    <button type="submit" class="fact-btn primary" data-role="submit-client-abono">Guardar abono</button>
                    <button type="button" class="fact-btn tertiary" data-role="open-client-detail" data-group-key="${escapeHtml(group.key)}">Ver paquetes entregados</button>
                </div>
            </form>
            <div class="facturacion-footnote">Historial de abonos registrados para este cliente en esta fecha.</div>
            ${renderAbonoHistory(group)}
        `;

        modal.classList.remove('modal-hidden');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeClientDetailModal = () => {
        const modal = document.getElementById('facturacionDetailModal');
        if (!modal) return;

        modal.classList.add('modal-hidden');
        modal.setAttribute('aria-hidden', 'true');
        state.selectedClienteGroupKey = null;
    };

    const bindAdminActions = () => {
        document.addEventListener('click', async (event) => {
            const detailButton = event.target.closest('[data-role="open-client-detail"]');
            if (detailButton) {
                openClientDetailModal(detailButton.dataset.groupKey);
                return;
            }

            const abonoButton = event.target.closest('[data-role="open-client-abono"]');
            if (abonoButton) {
                openClientAbonoModal(abonoButton.dataset.groupKey);
                return;
            }

            if (event.target.closest('[data-close-detail-modal]')) {
                closeClientDetailModal();
                return;
            }

            const button = event.target.closest('[data-role="save-payment"]');
            if (!button || mode !== 'admin') {
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

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('#clienteAbonoForm');
            if (!form) return;

            event.preventDefault();
            const submitButton = form.querySelector('[data-role="submit-client-abono"]');
            const originalText = submitButton ? submitButton.textContent : 'Guardar abono';
            if (submitButton) {
                submitButton.textContent = 'Guardando...';
                submitButton.disabled = true;
            }

            try {
                await saveClientAbono(form);
            } catch (error) {
                alert(error.message);
            } finally {
                if (submitButton) {
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }
            }
        });

        const modal = document.getElementById('facturacionDetailModal');
        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeClientDetailModal();
                }
            });
        }
    };

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeClientDetailModal();
        }
    });

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
