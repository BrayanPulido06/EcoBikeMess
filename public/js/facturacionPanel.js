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
        selectedClienteGroups: new Set(),
        selectedClienteGroupKey: null,
        selectedMensajeroGroupKey: null,
        activeClientModalView: 'detail',
        activeMensajeroModalView: 'detail',
        mensajeroGroups: []
    };
    const formatCurrencyNumber = (value) => {
        const amount = Math.round(Number(value || 0));
        return Number.isFinite(amount)
            ? amount.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
            : '0';
    };

    const money = (value) => `$ ${formatCurrencyNumber(value)}`;
    const moneyAbs = (value) => `$ ${formatCurrencyNumber(Math.abs(Number(value || 0)))}`;

    const parseCurrencyInput = (value) => {
        const digits = String(value || '').replace(/[^\d]/g, '');
        return digits ? Number(digits) : 0;
    };

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

    const getMessengerPaymentValue = (item) => {
        const value = Number(item?.valor_pago_mensajero || 0);
        return value > 0 ? value : 7000;
    };

    const getClienteAbonos = () => {
        const abonos = state.rawData?.cliente?.abonos;
        return Array.isArray(abonos) ? abonos : [];
    };

    const getGroupAbonos = (clienteId, fechaGrupo) => getClienteAbonos()
        .filter((abono) => Number(abono.cliente_id) === Number(clienteId) && String(abono.fecha_grupo) === String(fechaGrupo));

    const getGroupAbonoTotal = (clienteId, fechaGrupo) => getGroupAbonos(clienteId, fechaGrupo)
        .reduce((sum, abono) => sum + Number(abono.monto || 0), 0);

    const getClienteEstados = () => {
        const estados = state.rawData?.cliente?.estados;
        return Array.isArray(estados) ? estados : [];
    };

    const getGroupEstadoManual = (clienteId, fechaGrupo) => {
        const estado = getClienteEstados()
            .find((item) => Number(item.cliente_id) === Number(clienteId) && String(item.fecha_grupo) === String(fechaGrupo));
        return estado?.estado || '';
    };

    const getMensajeroAbonos = () => {
        const abonos = state.rawData?.mensajero?.abonos;
        return Array.isArray(abonos) ? abonos : [];
    };

    const getMensajeroGroupAbonos = (mensajeroId, fechaGrupo) => getMensajeroAbonos()
        .filter((abono) => Number(abono.mensajero_id) === Number(mensajeroId) && String(abono.fecha_grupo) === String(fechaGrupo));

    const getMensajeroEstados = () => {
        const estados = state.rawData?.mensajero?.estados;
        return Array.isArray(estados) ? estados : [];
    };

    const getMensajeroGroupEstadoManual = (mensajeroId, fechaGrupo) => {
        const estado = getMensajeroEstados()
            .find((item) => Number(item.mensajero_id) === Number(mensajeroId) && String(item.fecha_grupo) === String(fechaGrupo));
        return estado?.estado || '';
    };

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
        const haystack = panel === 'cliente'
            ? [
                item.numero_guia,
                item.cliente_nombre,
                item.cliente_contacto,
                item.destinatario_nombre
            ].join(' ').toLowerCase()
            : [
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

        const isAdminMessengerPanel = mode === 'admin' && panel === 'mensajero';
        const saldoLabel = panel === 'mensajero' ? 'Total a pagar' : 'Saldo actual';
        const saldoNote = isAdminMessengerPanel
            ? 'Pendiente acumulado por pagar a mensajeros.'
            : panel === 'mensajero'
                ? 'Suma de valores configurados para el mensajero.'
                : 'Recaudos reales menos costo de envios.';
        const totalEnviosLabel = isAdminMessengerPanel ? 'Total pagos' : 'Total valor envios';
        const totalEnviosNote = isAdminMessengerPanel
            ? 'Suma de valores configurados por entrega.'
            : 'Costo acumulado de los paquetes filtrables.';

        el.innerHTML = `
            <div class="summary-card">
                <span class="summary-label">${saldoLabel}</span>
                <div class="summary-value">${money(summary.saldo_actual)}</div>
                <div class="summary-note">${saldoNote}</div>
            </div>
            <div class="summary-card">
                <span class="summary-label">${totalEnviosLabel}</span>
                <div class="summary-value">${money(summary.total_envios)}</div>
                <div class="summary-note">${totalEnviosNote}</div>
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

    const buildClienteSummaryFromGroups = (groups) => {
        const totalsByClient = new Map();

        let totalEnvios = 0;
        let totalRecaudos = 0;
        let cantidadPaquetes = 0;
        let paquetesEntregados = 0;

        groups.forEach((group) => {
            // Los grupos llegan ordenados del más reciente al más antiguo.
            // Para el resumen debemos conservar el saldo acumulado más reciente de cada cliente.
            if (!totalsByClient.has(group.clientKey)) {
                totalsByClient.set(group.clientKey, Number(group.totalAcumulado || 0));
            }
            totalEnvios += Number(group.totalServicio || 0);
            totalRecaudos += Number(group.totalRecaudado || 0);
            cantidadPaquetes += Array.isArray(group.packages) ? group.packages.length : 0;
            paquetesEntregados += Number(group.paquetesEntregados || 0);
        });

        return {
            saldo_actual: Array.from(totalsByClient.values()).reduce((sum, value) => sum + value, 0),
            total_envios: totalEnvios,
            total_recaudos: totalRecaudos,
            cantidad_paquetes: cantidadPaquetes,
            paquetes_entregados: paquetesEntregados
        };
    };

    const buildMensajeroSummaryFromGroups = (groups) => {
        const totalsByMessenger = new Map();

        let totalPagos = 0;
        let totalRecaudos = 0;
        let cantidadPaquetes = 0;
        let paquetesEntregados = 0;

        groups.forEach((group) => {
            if (!totalsByMessenger.has(group.messengerKey)) {
                totalsByMessenger.set(group.messengerKey, Number(group.totalAcumulado || 0));
            }
            totalPagos += Number(group.totalPago || 0);
            totalRecaudos += Number(group.totalRecaudado || 0);
            cantidadPaquetes += Array.isArray(group.packages) ? group.packages.length : 0;
            paquetesEntregados += Number(group.entregas || 0);
        });

        return {
            saldo_actual: Array.from(totalsByMessenger.values()).reduce((sum, value) => sum + value, 0),
            total_envios: totalPagos,
            total_recaudos: totalRecaudos,
            cantidad_paquetes: cantidadPaquetes,
            paquetes_entregados: paquetesEntregados
        };
    };

    const buildMensajeroSummaryFromItems = (items) => items.reduce((summary, item) => {
        summary.saldo_actual += getMessengerPaymentValue(item);
        summary.total_envios += Number(item.valor_envio || 0);
        summary.total_recaudos += Number(item.valor_recaudo_real || 0);
        summary.cantidad_paquetes += 1;
        if ((item.estado || '') === 'entregado') {
            summary.paquetes_entregados += 1;
        }
        return summary;
    }, {
        saldo_actual: 0,
        total_envios: 0,
        total_recaudos: 0,
        cantidad_paquetes: 0,
        paquetes_entregados: 0
    });

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
                    subtotalServicio: 0,
                    totalAdicionales: 0,
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
                const valorBase = Number(item.valor_envio_base ?? item.valor_envio ?? 0);
                const adicional = Number(item.costo_adicional_servicio || 0);
                group.paquetesEntregados += 1;
                group.subtotalServicio += valorBase;
                group.totalAdicionales += adicional;
                group.totalServicio += valorBase + adicional;
                group.totalRecaudado += getRecaudoRealValue(item);
                group.packages.push(item);
            }
            group.statuses.add(item.estado || 'pendiente');
        });

        const groups = Array.from(groupsMap.values())
            .filter((group) => group.paquetesEntregados > 0)
            .map((group) => {
                const abonos = getGroupAbonos(group.clienteId, group.dateKey);
                const abono = abonos.reduce((sum, item) => sum + Number(item.monto || 0), 0);
                const saldoCalculado = Number(group.totalRecaudado || 0) - Number(group.totalServicio || 0);
                const estadoManual = getGroupEstadoManual(group.clienteId, group.dateKey);
                const saldo = estadoManual === 'pagado' ? 0 : saldoCalculado;
                const balance = estadoManual === 'pagado' ? 0 : saldo + abono;
                return {
                    ...group,
                    abonos,
                    abono,
                    balance,
                    saldo,
                    saldoCalculado,
                    estado: estadoManual || groupStatusFromBalance(balance)
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
            const current = previous + Number(group.balance || 0);
            runningTotalsByClient.set(group.clientKey, current);
            group.totalAcumulado = current;
            group.totalAcumuladoEstado = groupStatusFromBalance(current);
        });

        return groups;
    };

    const buildMensajeroGroups = (items) => {
        const filtered = items.filter((item) => matchesFilter(item, 'mensajero'));
        const groupsMap = new Map();

        filtered.forEach((item) => {
            const baseDate = item.fecha_entrega || item.fecha_ingreso;
            const dateKey = dateKeyFromValue(baseDate);
            const messengerId = Number(item.mensajero_id || 0);
            const messengerName = String(item.mensajero_nombre || 'Sin asignar').trim() || 'Sin asignar';
            const messengerKey = messengerId > 0 ? `id-${messengerId}` : normalizeText(messengerName);
            const groupKey = `${dateKey}__${messengerKey}`;

            if (!groupsMap.has(groupKey)) {
                groupsMap.set(groupKey, {
                    key: groupKey,
                    dateKey,
                    messengerKey,
                    fechaLabel: shortDate(baseDate),
                    mensajeroId: messengerId,
                    mensajeroNombre: messengerName,
                    entregas: 0,
                    totalPago: 0,
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
                group.entregas += 1;
                group.totalPago += getMessengerPaymentValue(item);
                group.totalRecaudado += getRecaudoRealValue(item);
                group.packages.push(item);
            }
            group.statuses.add(item.estado || 'pendiente');
        });

        const groups = Array.from(groupsMap.values())
            .filter((group) => group.entregas > 0)
            .map((group) => {
                const abonos = getMensajeroGroupAbonos(group.mensajeroId, group.dateKey);
                const abono = abonos.reduce((sum, item) => sum + Number(item.monto || 0), 0);
                const saldoCalculado = Number(group.totalPago || 0) - Number(group.totalRecaudado || 0) - abono;
                const estadoManual = getMensajeroGroupEstadoManual(group.mensajeroId, group.dateKey);
                const saldo = estadoManual === 'pagado' ? 0 : saldoCalculado;
                const balance = estadoManual === 'pagado' ? 0 : saldo;
                return {
                    ...group,
                    abonos,
                    abono,
                    balance,
                    saldo,
                    saldoCalculado,
                    estado: estadoManual || groupStatusFromBalance(balance)
                };
            })
            .sort((a, b) => {
                if (a.dateKey === b.dateKey) {
                    return a.mensajeroNombre.localeCompare(b.mensajeroNombre, 'es', { sensitivity: 'base' });
                }
                return b.dateKey.localeCompare(a.dateKey);
            });

        const runningTotalsByMessenger = new Map();
        const groupsAsc = [...groups].sort((a, b) => {
            if (a.messengerKey === b.messengerKey) {
                if (a.dateKey === b.dateKey) return a.key.localeCompare(b.key, 'es', { sensitivity: 'base' });
                return a.dateKey.localeCompare(b.dateKey);
            }
            return a.messengerKey.localeCompare(b.messengerKey, 'es', { sensitivity: 'base' });
        });

        groupsAsc.forEach((group) => {
            const previous = Number(runningTotalsByMessenger.get(group.messengerKey) || 0);
            const current = previous + Number(group.balance || 0);
            runningTotalsByMessenger.set(group.messengerKey, current);
            group.totalAcumulado = current;
            group.totalAcumuladoEstado = groupStatusFromBalance(current);
        });

        return groups;
    };

    const clientGroupStatusBadge = (status) => {
        if (status === 'pagado') {
            return '<span class="status-chip paid">Pagado</span>';
        }
        return '<span class="status-chip pending">Pendiente</span>';
    };

    const clientGroupStatusSelect = (group) => `
        <select
            class="status-select ${group.estado === 'pagado' ? 'paid' : 'pending'}"
            data-role="client-group-status"
            data-group-key="${escapeHtml(group.key)}"
            aria-label="Estado de facturacion ${escapeHtml(group.clienteNombre)} ${escapeHtml(group.fechaLabel)}"
        >
            <option value="pendiente" ${group.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
            <option value="pagado" ${group.estado === 'pagado' ? 'selected' : ''}>Pagado</option>
        </select>
    `;

    const messengerGroupStatusSelect = (group) => `
        <select
            class="status-select ${group.estado === 'pagado' ? 'paid' : 'pending'}"
            data-role="messenger-group-status"
            data-group-key="${escapeHtml(group.key)}"
            aria-label="Estado de pago ${escapeHtml(group.mensajeroNombre)} ${escapeHtml(group.fechaLabel)}"
        >
            <option value="pendiente" ${group.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
            <option value="pagado" ${group.estado === 'pagado' ? 'selected' : ''}>Pagado</option>
        </select>
    `;

    const amountCellClass = (status) => {
        if (status === 'pagado') return 'is-paid';
        return 'is-pending';
    };

    const balanceCellClass = (value) => Number(value || 0) >= 0 ? 'is-paid' : 'is-pending';

    const groupStatusFromBalance = (value) => Math.round(Number(value || 0)) === 0 ? 'pagado' : 'pendiente';

    const clienteTableColspan = () => mode === 'admin' ? 11 : 8;
    const mensajeroTableColspan = () => mode === 'admin' ? 10 : 11;

    const renderClienteTable = (items) => {
        const tbody = document.getElementById('table-body-cliente');
        if (!tbody) return;

        const groups = buildClienteGroups(items);
        state.clienteGroups = groups;
        const visibleKeys = new Set(groups.map((group) => group.key));
        state.selectedClienteGroups.forEach((key) => {
            if (!visibleKeys.has(key)) {
                state.selectedClienteGroups.delete(key);
            }
        });
        document.getElementById('count-cliente').textContent = `${groups.length} registros`;

        if (!groups.length) {
            tbody.innerHTML = `<tr><td colspan="${clienteTableColspan()}" class="empty-state">No hay registros con los filtros actuales.</td></tr>`;
            syncClienteSelectionControls();
            return;
        }

        tbody.innerHTML = groups.map((group) => {
            if (mode !== 'admin') {
                return `
                    <tr>
                        <td>${group.fechaLabel}</td>
                        <td>${group.paquetesEntregados}</td>
                        <td>${money(group.totalServicio)}</td>
                        <td>${money(group.totalRecaudado)}</td>
                        <td>${money(group.abono)}</td>
                        <td>${clientGroupStatusBadge(group.estado)}</td>
                        <td class="amount-cell ${balanceCellClass(group.saldo)}">${moneyAbs(group.saldo)}</td>
                        <td>
                            <button
                                type="button"
                                class="fact-btn tertiary detail-trigger"
                                data-role="open-client-detail"
                                data-group-key="${escapeHtml(group.key)}"
                            >
                                Ver paquetes
                            </button>
                        </td>
                    </tr>
                `;
            }

            return `
                <tr>
                    <td class="select-col">
                        <input
                            type="checkbox"
                            data-role="select-client-group"
                            data-group-key="${escapeHtml(group.key)}"
                            aria-label="Seleccionar ${escapeHtml(group.clienteNombre)} del ${escapeHtml(group.fechaLabel)}"
                            ${state.selectedClienteGroups.has(group.key) ? 'checked' : ''}
                        >
                    </td>
                    <td>${escapeHtml(group.clienteNombre)}</td>
                    <td>${group.fechaLabel}</td>
                    <td>${group.paquetesEntregados}</td>
                    <td>${money(group.totalServicio)}</td>
                    <td>${money(group.totalRecaudado)}</td>
                    <td>${money(group.abono)}</td>
                    <td>${clientGroupStatusSelect(group)}</td>
                    <td class="amount-cell ${balanceCellClass(group.saldo)}">${moneyAbs(group.saldo)}</td>
                    <td class="amount-cell ${balanceCellClass(group.totalAcumulado)}">${moneyAbs(group.totalAcumulado)}</td>
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
                            <button
                                type="button"
                                class="fact-btn secondary detail-trigger"
                                data-role="open-client-abono"
                                data-group-key="${escapeHtml(group.key)}"
                            >
                                Registrar abono
                            </button>
                            <button
                                type="button"
                                class="fact-btn danger detail-trigger"
                                data-role="hide-client-group"
                                data-group-key="${escapeHtml(group.key)}"
                                data-client-name="${escapeHtml(group.clienteNombre)}"
                                data-date-label="${escapeHtml(group.fechaLabel)}"
                            >
                                Eliminar del dia
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        syncClienteSelectionControls();
    };

    const renderMensajeroTable = (items) => {
        const tbody = document.getElementById('table-body-mensajero');
        if (!tbody) return [];

        if (mode !== 'admin') {
            const filtered = items.filter((item) => matchesFilter(item, 'mensajero'));
            document.getElementById('count-mensajero').textContent = `${filtered.length} registros`;

            if (!filtered.length) {
                tbody.innerHTML = `<tr><td colspan="${mensajeroTableColspan()}" class="empty-state">No hay registros con los filtros actuales.</td></tr>`;
                return filtered;
            }

            tbody.innerHTML = filtered.map((item) => `
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
                    <td>${money(getMessengerPaymentValue(item))}</td>
                    <td>${boolBadge(item.mostrar_al_mensajero, 'Visible', 'Oculto')}</td>
                </tr>
            `).join('');

            return filtered;
        }

        const groups = buildMensajeroGroups(items);
        state.mensajeroGroups = groups;
        document.getElementById('count-mensajero').textContent = `${groups.length} registros`;

        if (!groups.length) {
            tbody.innerHTML = `<tr><td colspan="${mensajeroTableColspan()}" class="empty-state">No hay registros con los filtros actuales.</td></tr>`;
            return groups;
        }

        tbody.innerHTML = groups.map((group) => `
                <tr>
                    <td>${escapeHtml(group.mensajeroNombre)}</td>
                    <td>${group.fechaLabel}</td>
                    <td>${group.entregas}</td>
                    <td>${money(group.totalPago)}</td>
                    <td>${money(group.totalRecaudado)}</td>
                    <td>${money(group.abono)}</td>
                    <td>${messengerGroupStatusSelect(group)}</td>
                    <td class="amount-cell ${balanceCellClass(group.saldo)}">${moneyAbs(group.saldo)}</td>
                    <td class="amount-cell ${balanceCellClass(group.totalAcumulado)}">${moneyAbs(group.totalAcumulado)}</td>
                    <td>
                        <div class="table-tools">
                            <button
                                type="button"
                                class="fact-btn tertiary detail-trigger"
                                data-role="open-messenger-detail"
                                data-group-key="${escapeHtml(group.key)}"
                            >
                                Ver entregas
                            </button>
                            <button
                                type="button"
                                class="fact-btn secondary detail-trigger"
                                data-role="open-messenger-abono"
                                data-group-key="${escapeHtml(group.key)}"
                            >
                                Registrar abono
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

        return groups;
    };

    const render = () => {
        if (!state.rawData) return;

        if (state.rawData.cliente) {
            renderClienteTable(state.rawData.cliente.items);
            renderSummary(buildClienteSummaryFromGroups(state.clienteGroups), 'cliente');
        }

        if (state.rawData.mensajero) {
            const visibleMensajeroData = renderMensajeroTable(state.rawData.mensajero.items);
            renderSummary(
                mode === 'admin'
                    ? buildMensajeroSummaryFromGroups(state.mensajeroGroups)
                    : buildMensajeroSummaryFromItems(visibleMensajeroData),
                'mensajero'
            );
        }
    };

    const buildEndpointUrl = (panel = '') => {
        if (!panel || mode !== 'admin') {
            return endpoint;
        }

        const separator = endpoint.includes('?') ? '&' : '?';
        return `${endpoint}${separator}panel=${encodeURIComponent(panel)}`;
    };

    const setLoading = (message = 'Cargando informacion...', panel = '') => {
        const loaders = panel
            ? [document.getElementById(`table-body-${panel}`)].filter(Boolean)
            : document.querySelectorAll('[data-loading]');

        loaders.forEach((el) => {
            const colspan = el.id === 'table-body-cliente' ? clienteTableColspan() : mensajeroTableColspan();
            el.innerHTML = `<tr><td colspan="${colspan}" class="loading-state">${message}</td></tr>`;
        });
    };

    const fetchData = async (panel = '') => {
        setLoading('Cargando informacion...', panel);
        const response = await fetch(buildEndpointUrl(panel), { credentials: 'same-origin' });
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'No fue posible cargar la facturacion.');
        }

        state.rawData = {
            ...(state.rawData || {}),
            ...(result.data || {})
        };
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
        let filterRenderTimer = null;
        const scheduleRender = () => {
            window.clearTimeout(filterRenderTimer);
            filterRenderTimer = window.setTimeout(render, 180);
        };

        document.querySelectorAll('[data-panel-filter]').forEach((input) => {
            input.addEventListener('input', () => {
                if (input.type === 'date') {
                    return;
                }
                const panel = input.dataset.panelFilter;
                const field = input.dataset.filterField;
                state.filters[panel][field] = input.value;
                scheduleRender();
            });

            input.addEventListener('change', () => {
                const panel = input.dataset.panelFilter;
                const field = input.dataset.filterField;
                state.filters[panel][field] = input.value;
                window.clearTimeout(filterRenderTimer);
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

                if (mode === 'admin' && !state.rawData?.[panel]) {
                    fetchData(panel).catch((error) => {
                        const el = document.getElementById(`table-body-${panel}`);
                        if (!el) {
                            return;
                        }
                        const colspan = panel === 'cliente' ? clienteTableColspan() : mensajeroTableColspan();
                        el.innerHTML = `<tr><td colspan="${colspan}" class="empty-state">${error.message}</td></tr>`;
                    });
                }
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
        if (state.selectedMensajeroGroupKey && state.activeMensajeroModalView === 'detail') {
            openMessengerDetailModal(state.selectedMensajeroGroupKey);
        }
    };

    const saveClientGroupStatus = async (groupKey, estado) => {
        const group = getClienteGroupByKey(groupKey);
        if (!group || mode !== 'admin') {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'actualizar_estado_grupo_cliente');
        formData.append('cliente_id', String(group.clienteId));
        formData.append('fecha_grupo', group.dateKey);
        formData.append('estado', estado);

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'No se pudo actualizar el estado.');
        }

        state.rawData = result.data;
        render();
    };

    const getMensajeroGroupByKey = (groupKey) => state.mensajeroGroups.find((group) => group.key === groupKey) || null;

    const saveMessengerGroupStatus = async (groupKey, estado) => {
        const group = getMensajeroGroupByKey(groupKey);
        if (!group || mode !== 'admin') {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'actualizar_estado_grupo_mensajero');
        formData.append('mensajero_id', String(group.mensajeroId));
        formData.append('fecha_grupo', group.dateKey);
        formData.append('estado', estado);

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'No se pudo actualizar el estado.');
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

    const saveMessengerAbono = async (form) => {
        const formData = new FormData();
        formData.append('action', 'registrar_abono_mensajero');
        formData.append('mensajero_id', form.mensajero_id.value);
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
        if (state.selectedMensajeroGroupKey) {
            openMessengerAbonoModal(state.selectedMensajeroGroupKey);
        }
    };

    const savePackageAdditionalCost = async (form) => {
        const formData = new FormData();
        formData.append('action', 'actualizar_costo_adicional_paquete');
        formData.append('paquete_id', form.paquete_id.value);
        formData.append('monto', form.monto.value || '0');
        formData.append('descripcion', form.descripcion.value || '');

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'No se pudo actualizar el costo adicional.');
        }

        state.rawData = result.data;
        render();
        if (state.selectedClienteGroupKey) {
            openClientDetailModal(state.selectedClienteGroupKey);
        }
    };

    const syncCurrencyInput = (input) => {
        if (!input) return;

        const hiddenInput = input.form ? input.form.querySelector('input[name="monto"]') : null;
        const amount = parseCurrencyInput(input.value);

        if (hiddenInput) {
            hiddenInput.value = amount > 0 ? String(amount) : '';
        }

        input.value = amount > 0 ? money(amount) : '';
    };

    const getClienteGroupByKey = (groupKey) => state.clienteGroups.find((group) => group.key === groupKey) || null;

    const syncClienteSelectionControls = () => {
        const selectAll = document.querySelector('[data-role="select-all-client-groups"]');
        const bulkButton = document.querySelector('[data-role="hide-selected-client-groups"]');
        const visibleKeys = state.clienteGroups.map((group) => group.key);
        const selectedVisible = visibleKeys.filter((key) => state.selectedClienteGroups.has(key));

        if (selectAll) {
            selectAll.checked = visibleKeys.length > 0 && selectedVisible.length === visibleKeys.length;
            selectAll.indeterminate = selectedVisible.length > 0 && selectedVisible.length < visibleKeys.length;
            selectAll.disabled = visibleKeys.length === 0;
        }

        if (bulkButton) {
            bulkButton.disabled = selectedVisible.length === 0;
            bulkButton.textContent = selectedVisible.length > 0
                ? `Eliminar seleccionados (${selectedVisible.length})`
                : 'Eliminar seleccionados';
        }
    };

    const renderPackageCard = (item) => {
        const recaudoReal = getRecaudoRealValue(item);
        const valorBase = Number(item.valor_envio_base ?? item.valor_envio ?? 0);
        const adicional = Number(item.costo_adicional_servicio || 0);
        const valorTotal = Number(item.valor_envio || 0);
        const saldo = Math.max(valorTotal, 0);
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
                        <span class="package-label">Servicio base</span>
                        <strong>${money(valorBase)}</strong>
                    </div>
                    <div class="package-data">
                        <span class="package-label">Adicional</span>
                        <strong>${money(adicional)}</strong>
                    </div>
                    <div class="package-data">
                        <span class="package-label">Total servicio</span>
                        <strong>${money(valorTotal)}</strong>
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
                    <div class="package-data package-full">
                        <span class="package-label">Motivo adicional</span>
                        <strong>${escapeHtml(item.observaciones_admin || 'Sin adicional')}</strong>
                    </div>
                </div>
                ${mode === 'admin' ? `
                    <div class="facturacion-abono-actions">
                        <button
                            type="button"
                            class="fact-btn secondary"
                            data-role="open-package-additional-cost"
                            data-package-id="${item.paquete_id}"
                        >
                            Editar adicional
                        </button>
                    </div>
                ` : ''}
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

    const renderMessengerPackageCard = (item) => `
        <article class="package-card">
            <div class="package-card-head">
                <div>
                    <h3>${escapeHtml(item.numero_guia)}</h3>
                    <p>${escapeHtml(item.cliente_nombre || 'Sin cliente')} | ${escapeHtml(item.destinatario_nombre || 'Sin destinatario')}</p>
                </div>
                ${statusBadge(item.estado)}
            </div>
            <div class="package-card-grid">
                <div class="package-data">
                    <span class="package-label">Pago mensajero</span>
                    <strong>${money(getMessengerPaymentValue(item))}</strong>
                </div>
                <div class="package-data">
                    <span class="package-label">Servicio cliente</span>
                    <strong>${money(item.valor_envio)}</strong>
                </div>
                <div class="package-data">
                    <span class="package-label">Contraentrega</span>
                    <strong>${item.agregado_al_recaudo ? 'Si' : 'No'}</strong>
                </div>
                <div class="package-data">
                    <span class="package-label">Recaudo esperado</span>
                    <strong>${money(item.valor_recaudo)}</strong>
                </div>
                <div class="package-data">
                    <span class="package-label">Recaudo real</span>
                    <strong>${money(item.valor_recaudo_real)}</strong>
                </div>
                <div class="package-data">
                    <span class="package-label">Visible al mensajero</span>
                    <strong>${item.mostrar_al_mensajero ? 'Si' : 'No'}</strong>
                </div>
                <div class="package-data">
                    <span class="package-label">Fecha</span>
                    <strong>${shortDate(item.fecha_entrega || item.fecha_ingreso)}</strong>
                </div>
                <div class="package-data package-full">
                    <span class="package-label">Observaciones entrega</span>
                    <strong>${escapeHtml(item.observaciones || 'Sin observaciones')}</strong>
                </div>
            </div>
            ${mode === 'admin' ? `
                <div class="table-tools messenger-payment-tools">
                    <input type="number" min="0" step="100" value="${Math.round(getMessengerPaymentValue(item))}" data-role="payment-input" data-id="${item.paquete_id}">
                    <label class="toggle-wrap">
                        <input type="checkbox" data-role="show-toggle" data-id="${item.paquete_id}" ${item.mostrar_al_mensajero ? 'checked' : ''}>
                        Mostrar
                    </label>
                    <span class="facturacion-footnote" data-role="payment-status" data-id="${item.paquete_id}">Guardado automatico</span>
                </div>
            ` : ''}
        </article>
    `;

    const openMessengerDetailModal = (groupKey) => {
        const group = getMensajeroGroupByKey(groupKey);
        const modal = document.getElementById('facturacionDetailModal');
        const title = document.getElementById('facturacionDetailTitle');
        const subtitle = document.getElementById('facturacionDetailSubtitle');
        const body = document.getElementById('facturacionDetailBody');

        if (!group || !modal || !title || !subtitle || !body) {
            return;
        }

        state.selectedMensajeroGroupKey = groupKey;
        state.activeMensajeroModalView = 'detail';
        title.textContent = `${group.mensajeroNombre} - ${group.fechaLabel}`;
        subtitle.textContent = `${group.entregas} entrega(s) | Pago ${money(group.totalPago)} | Recaudo descontado ${money(group.totalRecaudado)} | Abono ${money(group.abono)} | Saldo ${moneyAbs(group.saldo)}`;

        body.innerHTML = `
            <div class="detail-summary-strip">
                <div><span>Entregas</span><strong>${group.entregas}</strong></div>
                <div><span>Total pago</span><strong>${money(group.totalPago)}</strong></div>
                <div><span>Recaudo descontado</span><strong>${money(group.totalRecaudado)}</strong></div>
                <div><span>Abono</span><strong>${money(group.abono)}</strong></div>
                <div><span>Estado</span><strong>${group.estado === 'pagado' ? 'Pagado' : 'Pendiente'}</strong></div>
                <div><span>Saldo del dia</span><strong>${moneyAbs(group.saldo)}</strong></div>
                <div><span>Total acumulado</span><strong>${moneyAbs(group.totalAcumulado)}</strong></div>
            </div>
            <div class="facturacion-abono-actions">
                <button
                    type="button"
                    class="fact-btn secondary"
                    data-role="open-messenger-abono"
                    data-group-key="${escapeHtml(group.key)}"
                >
                    Registrar abono
                </button>
            </div>
            <div class="package-list">
                ${group.packages.map(renderMessengerPackageCard).join('')}
            </div>
        `;

        modal.classList.remove('modal-hidden');
        modal.setAttribute('aria-hidden', 'false');
    };

    const openMessengerAbonoModal = (groupKey) => {
        const group = getMensajeroGroupByKey(groupKey);
        const modal = document.getElementById('facturacionDetailModal');
        const title = document.getElementById('facturacionDetailTitle');
        const subtitle = document.getElementById('facturacionDetailSubtitle');
        const body = document.getElementById('facturacionDetailBody');

        if (!group || !modal || !title || !subtitle || !body) {
            return;
        }

        state.selectedMensajeroGroupKey = groupKey;
        state.activeMensajeroModalView = 'abono';
        title.textContent = `Registrar abono - ${group.mensajeroNombre}`;
        subtitle.textContent = `Fecha ${group.fechaLabel} | Pago ${money(group.totalPago)} | Recaudo descontado ${money(group.totalRecaudado)} | Abonado ${money(group.abono)} | Total pendiente ${moneyAbs(group.totalAcumulado)}`;

        body.innerHTML = `
            <div class="detail-summary-strip">
                <div><span>Entregas</span><strong>${group.entregas}</strong></div>
                <div><span>Total pago</span><strong>${money(group.totalPago)}</strong></div>
                <div><span>Recaudo descontado</span><strong>${money(group.totalRecaudado)}</strong></div>
                <div><span>Abonado</span><strong>${money(group.abono)}</strong></div>
                <div><span>Saldo del dia</span><strong>${moneyAbs(group.saldo)}</strong></div>
                <div><span>Total acumulado</span><strong>${moneyAbs(group.totalAcumulado)}</strong></div>
            </div>
            <form id="mensajeroAbonoForm" class="facturacion-abono-form">
                <input type="hidden" name="mensajero_id" value="${group.mensajeroId}">
                <input type="hidden" name="fecha_grupo" value="${escapeHtml(group.dateKey)}">
                <div class="facturacion-abono-grid">
                    <label class="facturacion-field">
                        <span>Monto del abono</span>
                        <input type="hidden" name="monto" value="" required>
                        <input type="text" name="monto_display" inputmode="numeric" autocomplete="off" placeholder="$ 49.000" required>
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
                        <textarea name="observaciones" rows="3" placeholder="Detalle del pago al mensajero"></textarea>
                    </label>
                </div>
                <div class="facturacion-abono-actions">
                    <button type="submit" class="fact-btn primary" data-role="submit-messenger-abono">Guardar abono</button>
                    <button type="button" class="fact-btn tertiary" data-role="open-messenger-detail" data-group-key="${escapeHtml(group.key)}">Ver entregas</button>
                </div>
            </form>
            <div class="facturacion-footnote">Historial de abonos registrados para este mensajero en esta fecha.</div>
            ${renderAbonoHistory(group)}
        `;

        modal.classList.remove('modal-hidden');
        modal.setAttribute('aria-hidden', 'false');
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
        subtitle.textContent = `${group.paquetesEntregados} entregado(s) | Servicio ${money(group.totalServicio)} | Recaudo ${money(group.totalRecaudado)} | Abono ${money(group.abono)} | Saldo ${moneyAbs(group.saldo)} | Total ${moneyAbs(group.totalAcumulado)}`;

        body.innerHTML = `
            <div class="detail-summary-strip">
                <div><span>Entregados</span><strong>${group.paquetesEntregados}</strong></div>
                <div><span>Servicio base</span><strong>${money(group.subtotalServicio)}</strong></div>
                <div><span>Adicionales</span><strong>${money(group.totalAdicionales)}</strong></div>
                <div><span>Total servicio</span><strong>${money(group.totalServicio)}</strong></div>
                <div><span>Recaudado</span><strong>${money(group.totalRecaudado)}</strong></div>
                <div><span>Abono</span><strong>${money(group.abono)}</strong></div>
                <div><span>Estado</span><strong>${group.estado === 'pagado' ? 'Pagado' : 'Pendiente'}</strong></div>
                <div><span>Total acumulado</span><strong>${moneyAbs(group.totalAcumulado)}</strong></div>
            </div>
            ${mode === 'admin' ? `
                <div class="facturacion-abono-actions">
                    <button
                        type="button"
                        class="fact-btn danger"
                        data-role="hide-client-group"
                        data-group-key="${escapeHtml(group.key)}"
                        data-client-name="${escapeHtml(group.clienteNombre)}"
                        data-date-label="${escapeHtml(group.fechaLabel)}"
                    >
                        Eliminar esta cuenta del dia
                    </button>
                </div>
            ` : ''}
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
        subtitle.textContent = `Fecha ${group.fechaLabel} | Servicio ${money(group.totalServicio)} | Abonado ${money(group.abono)} | Total pendiente ${moneyAbs(group.totalAcumulado)}`;

        body.innerHTML = `
            <div class="detail-summary-strip">
                <div><span>Servicio base</span><strong>${money(group.subtotalServicio)}</strong></div>
                <div><span>Adicionales</span><strong>${money(group.totalAdicionales)}</strong></div>
                <div><span>Total servicio</span><strong>${money(group.totalServicio)}</strong></div>
                <div><span>Total recaudado</span><strong>${money(group.totalRecaudado)}</strong></div>
                <div><span>Abonado</span><strong>${money(group.abono)}</strong></div>
                <div><span>Saldo del dia</span><strong>${moneyAbs(group.saldo)}</strong></div>
                <div><span>Total acumulado</span><strong>${moneyAbs(group.totalAcumulado)}</strong></div>
            </div>
            <form id="clienteAbonoForm" class="facturacion-abono-form">
                <input type="hidden" name="cliente_id" value="${group.clienteId}">
                <input type="hidden" name="fecha_grupo" value="${escapeHtml(group.dateKey)}">
                <div class="facturacion-abono-grid">
                    <label class="facturacion-field">
                        <span>Monto del abono</span>
                        <input type="hidden" name="monto" value="" required>
                        <input type="text" name="monto_display" inputmode="numeric" autocomplete="off" placeholder="$ 100.000" required>
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

    const openPackageAdditionalCostModal = (paqueteId) => {
        const group = getClienteGroupByKey(state.selectedClienteGroupKey);
        const item = group?.packages.find((pkg) => Number(pkg.paquete_id) === Number(paqueteId));
        const modal = document.getElementById('facturacionDetailModal');
        const title = document.getElementById('facturacionDetailTitle');
        const subtitle = document.getElementById('facturacionDetailSubtitle');
        const body = document.getElementById('facturacionDetailBody');

        if (!group || !item || !modal || !title || !subtitle || !body) {
            return;
        }

        const valorBase = Number(item.valor_envio_base ?? item.valor_envio ?? 0);
        const adicional = Number(item.costo_adicional_servicio || 0);

        state.activeClientModalView = 'additional-cost';
        title.textContent = `Adicional paquete ${item.numero_guia}`;
        subtitle.textContent = `${group.clienteNombre} | ${group.fechaLabel} | Servicio base ${money(valorBase)} | Adicional ${money(adicional)}`;

        body.innerHTML = `
            <div class="detail-summary-strip">
                <div><span>Guia</span><strong>${escapeHtml(item.numero_guia)}</strong></div>
                <div><span>Destinatario</span><strong>${escapeHtml(item.destinatario_nombre || 'Sin destinatario')}</strong></div>
                <div><span>Servicio base</span><strong>${money(valorBase)}</strong></div>
                <div><span>Adicional actual</span><strong>${money(adicional)}</strong></div>
                <div><span>Total servicio</span><strong>${money(Number(item.valor_envio || 0))}</strong></div>
            </div>
            <form id="paqueteCostoAdicionalForm" class="facturacion-abono-form">
                <input type="hidden" name="paquete_id" value="${item.paquete_id}">
                <div class="facturacion-abono-grid">
                    <label class="facturacion-field">
                        <span>Valor adicional</span>
                        <input type="hidden" name="monto" value="${adicional > 0 ? adicional : ''}">
                        <input type="text" name="monto_display" inputmode="numeric" autocomplete="off" placeholder="$ 2.000" value="${adicional > 0 ? money(adicional) : ''}">
                    </label>
                    <label class="facturacion-field facturacion-field-full">
                        <span>Descripcion</span>
                        <textarea name="descripcion" rows="3" placeholder="Ej: espera prolongada en entrega">${escapeHtml(item.observaciones_admin || '')}</textarea>
                    </label>
                </div>
                <div class="facturacion-abono-actions">
                    <button type="submit" class="fact-btn primary" data-role="submit-package-additional-cost">Guardar adicional</button>
                    <button type="button" class="fact-btn tertiary" data-role="open-client-detail" data-group-key="${escapeHtml(group.key)}">Volver al dia</button>
                </div>
                <div class="facturacion-footnote">Para quitar el adicional, deja el valor en cero o vacio y guarda.</div>
            </form>
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
        state.selectedMensajeroGroupKey = null;
    };

    const hideClienteGroup = async (group) => {
        const formData = new FormData();
        formData.append('action', 'ocultar_grupo_cliente');
        formData.append('cliente_id', String(group.clienteId));
        formData.append('fecha_grupo', group.dateKey);

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'No se pudo ocultar la cuenta del dia.');
        }

        state.rawData = result.data;
        return result;
    };

    const handleHideClientGroup = async (button) => {
        const groupKey = button?.dataset.groupKey;
        const clientName = button?.dataset.clientName || 'este cliente';
        const dateLabel = button?.dataset.dateLabel || 'la fecha seleccionada';
        const group = getClienteGroupByKey(groupKey);

        if (!groupKey || !group || mode !== 'admin') {
            return;
        }

        const confirmed = window.confirm(`Se ocultara de la vista la cuenta de ${clientName} del ${dateLabel}. Esta accion no elimina nada de la base de datos. Deseas continuar?`);
        if (!confirmed) {
            return;
        }

        await hideClienteGroup(group);
        state.selectedClienteGroups.delete(group.key);
        closeClientDetailModal();
        render();
    };

    const handleHideSelectedClientGroups = async (button) => {
        if (mode !== 'admin') {
            return;
        }

        const groups = state.clienteGroups.filter((group) => state.selectedClienteGroups.has(group.key));
        if (!groups.length) {
            return;
        }

        const confirmed = window.confirm(`Se ocultaran ${groups.length} cuenta(s) seleccionada(s) de la vista. Esta accion no elimina nada de la base de datos. Deseas continuar?`);
        if (!confirmed) {
            return;
        }

        const originalText = button?.textContent || 'Eliminar seleccionados';
        if (button) {
            button.disabled = true;
            button.textContent = 'Eliminando...';
        }

        try {
            for (const group of groups) {
                await hideClienteGroup(group);
                state.selectedClienteGroups.delete(group.key);
            }
            closeClientDetailModal();
            render();
        } finally {
            if (button) {
                button.textContent = originalText;
                syncClienteSelectionControls();
            }
        }
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

            const messengerDetailButton = event.target.closest('[data-role="open-messenger-detail"]');
            if (messengerDetailButton) {
                openMessengerDetailModal(messengerDetailButton.dataset.groupKey);
                return;
            }

            const messengerAbonoButton = event.target.closest('[data-role="open-messenger-abono"]');
            if (messengerAbonoButton) {
                openMessengerAbonoModal(messengerAbonoButton.dataset.groupKey);
                return;
            }

            const packageAdditionalCostButton = event.target.closest('[data-role="open-package-additional-cost"]');
            if (packageAdditionalCostButton) {
                openPackageAdditionalCostModal(packageAdditionalCostButton.dataset.packageId);
                return;
            }

            const hideButton = event.target.closest('[data-role="hide-client-group"]');
            if (hideButton) {
                handleHideClientGroup(hideButton).catch((error) => {
                    alert(error.message);
                });
                return;
            }

            const bulkHideButton = event.target.closest('[data-role="hide-selected-client-groups"]');
            if (bulkHideButton) {
                handleHideSelectedClientGroups(bulkHideButton).catch((error) => {
                    alert(error.message);
                    syncClienteSelectionControls();
                });
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

        document.addEventListener('change', (event) => {
            const paymentControl = event.target.closest('[data-role="payment-input"], [data-role="show-toggle"]');
            if (paymentControl && mode === 'admin') {
                const paqueteId = paymentControl.dataset.id;
                const paymentInput = document.querySelector(`[data-role="payment-input"][data-id="${paqueteId}"]`);
                const toggleInput = document.querySelector(`[data-role="show-toggle"][data-id="${paqueteId}"]`);
                const statusEl = document.querySelector(`[data-role="payment-status"][data-id="${paqueteId}"]`);

                if (paymentInput && Number(paymentInput.value || 0) <= 0) {
                    paymentInput.value = '7000';
                }

                if (statusEl) {
                    statusEl.textContent = 'Guardando...';
                }
                if (paymentInput) {
                    paymentInput.disabled = true;
                }
                if (toggleInput) {
                    toggleInput.disabled = true;
                }

                savePayment(paqueteId).catch((error) => {
                    alert(error.message);
                    if (statusEl) {
                        statusEl.textContent = 'No se pudo guardar';
                    }
                    if (paymentInput) {
                        paymentInput.disabled = false;
                    }
                    if (toggleInput) {
                        toggleInput.disabled = false;
                    }
                });
                return;
            }

            const statusSelect = event.target.closest('[data-role="client-group-status"]');
            if (statusSelect) {
                const previousClass = statusSelect.className;
                statusSelect.disabled = true;
                saveClientGroupStatus(statusSelect.dataset.groupKey, statusSelect.value).catch((error) => {
                    alert(error.message);
                    statusSelect.disabled = false;
                    statusSelect.className = previousClass;
                });
                return;
            }

            const messengerStatusSelect = event.target.closest('[data-role="messenger-group-status"]');
            if (messengerStatusSelect) {
                const previousClass = messengerStatusSelect.className;
                messengerStatusSelect.disabled = true;
                saveMessengerGroupStatus(messengerStatusSelect.dataset.groupKey, messengerStatusSelect.value).catch((error) => {
                    alert(error.message);
                    messengerStatusSelect.disabled = false;
                    messengerStatusSelect.className = previousClass;
                });
                return;
            }

            const rowCheckbox = event.target.closest('[data-role="select-client-group"]');
            if (rowCheckbox) {
                const groupKey = rowCheckbox.dataset.groupKey;
                if (rowCheckbox.checked) {
                    state.selectedClienteGroups.add(groupKey);
                } else {
                    state.selectedClienteGroups.delete(groupKey);
                }
                syncClienteSelectionControls();
                return;
            }

            const selectAll = event.target.closest('[data-role="select-all-client-groups"]');
            if (selectAll) {
                state.clienteGroups.forEach((group) => {
                    if (selectAll.checked) {
                        state.selectedClienteGroups.add(group.key);
                    } else {
                        state.selectedClienteGroups.delete(group.key);
                    }
                });
                renderClienteTable(state.rawData?.cliente?.items || []);
            }
        });

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('#clienteAbonoForm');
            if (!form) return;

            event.preventDefault();
            const amountDisplayInput = form.querySelector('input[name="monto_display"]');
            if (amountDisplayInput) {
                syncCurrencyInput(amountDisplayInput);
            }

            if (!form.monto.value || Number(form.monto.value) <= 0) {
                alert('Ingresa un monto de abono valido.');
                return;
            }

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

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('#mensajeroAbonoForm');
            if (!form) return;

            event.preventDefault();
            const amountDisplayInput = form.querySelector('input[name="monto_display"]');
            if (amountDisplayInput) {
                syncCurrencyInput(amountDisplayInput);
            }

            if (!form.monto.value || Number(form.monto.value) <= 0) {
                alert('Ingresa un monto de abono valido.');
                return;
            }

            const submitButton = form.querySelector('[data-role="submit-messenger-abono"]');
            const originalText = submitButton ? submitButton.textContent : 'Guardar abono';
            if (submitButton) {
                submitButton.textContent = 'Guardando...';
                submitButton.disabled = true;
            }

            try {
                await saveMessengerAbono(form);
            } catch (error) {
                alert(error.message);
            } finally {
                if (submitButton) {
                    submitButton.textContent = originalText;
                    submitButton.disabled = false;
                }
            }
        });

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('#paqueteCostoAdicionalForm');
            if (!form) return;

            event.preventDefault();
            const amountDisplayInput = form.querySelector('input[name="monto_display"]');
            if (amountDisplayInput) {
                syncCurrencyInput(amountDisplayInput);
            }

            const monto = Number(form.monto.value || 0);
            if (monto > 0 && !String(form.descripcion.value || '').trim()) {
                alert('Ingresa la descripcion del costo adicional.');
                return;
            }

            const submitButton = form.querySelector('[data-role="submit-package-additional-cost"]');
            const originalText = submitButton ? submitButton.textContent : 'Guardar adicional';
            if (submitButton) {
                submitButton.textContent = 'Guardando...';
                submitButton.disabled = true;
            }

            try {
                await savePackageAdditionalCost(form);
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

        document.addEventListener('input', (event) => {
            const input = event.target.closest('input[name="monto_display"]');
            if (!input) return;

            const hiddenInput = input.form ? input.form.querySelector('input[name="monto"]') : null;
            const amount = parseCurrencyInput(input.value);

            if (hiddenInput) {
                hiddenInput.value = amount > 0 ? String(amount) : '';
            }
        });

        document.addEventListener('blur', (event) => {
            const input = event.target.closest('input[name="monto_display"]');
            if (!input) return;
            syncCurrencyInput(input);
        }, true);

        document.addEventListener('focus', (event) => {
            const input = event.target.closest('input[name="monto_display"]');
            if (!input) return;

            const hiddenInput = input.form ? input.form.querySelector('input[name="monto"]') : null;
            const amount = hiddenInput ? Number(hiddenInput.value || 0) : parseCurrencyInput(input.value);
            input.value = amount > 0 ? String(amount) : '';
        }, true);
    };

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeClientDetailModal();
        }
    });

    bindFilters();
    bindTabs();
    bindAdminActions();
    fetchData(mode === 'admin' ? 'cliente' : '').catch((error) => {
        const loaders = mode === 'admin'
            ? [document.getElementById('table-body-cliente')].filter(Boolean)
            : document.querySelectorAll('[data-loading]');

        loaders.forEach((el) => {
            const colspan = el.id === 'table-body-cliente' ? clienteTableColspan() : mensajeroTableColspan();
            el.innerHTML = `<tr><td colspan="${colspan}" class="empty-state">${error.message}</td></tr>`;
        });
    });
});
