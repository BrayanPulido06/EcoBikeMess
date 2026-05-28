// public/js/asignarRecolecciones.js
let recolecciones = [];
let todosLosMensajeros = [];
let paquetesDetalleActual = [];
let currentRotuloData = null;

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadInitialData();
    cargarMensajerosEnModal();
});

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(Number(value) || 0);
}

function isTruthyValue(value) {
    const text = String(value ?? '').trim().toLowerCase();
    return ['1', 'si', 's\u00ed', 'true', 'x', 'yes'].includes(text);
}

function normalizeYesNo(value) {
    return isTruthyValue(value) ? 'Si' : 'No';
}

function getPackageObservations(paquete) {
    const observaciones = [
        ['Recoleccion', paquete.observaciones_recoleccion],
        ['Entrega', paquete.instrucciones_entrega],
        ['Registro entrega', paquete.observaciones_entrega],
        ['Ultima novedad', paquete.ultima_novedad_descripcion]
    ].filter(([, value]) => String(value ?? '').trim() !== '');

    if (observaciones.length === 0) {
        return '<span class="text-muted">Sin observaciones</span>';
    }

    return observaciones.map(([label, value]) => `
        <div class="package-note"><strong>${escapeHtml(label)}:</strong> ${escapeHtml(value)}</div>
    `).join('');
}

function getPackageAdditions(paquete) {
    const additions = [];
    const addressText = `${paquete.direccion_origen || ''} ${paquete.direccion_destino || ''}`.toLowerCase();

    if (isTruthyValue(paquete.envio_mismo_dia)) additions.push('Entrega hoy');
    if (isTruthyValue(paquete.recoger_cambios)) additions.push('Recoger cambios');
    if (isTruthyValue(paquete.zona_periferica)) additions.push('Zona periferica');
    if (addressText.includes('usme')) additions.push('Ubicacion Usme');
    if (String(paquete.tipo_servicio || '').toLowerCase() === 'contraentrega' || Number(paquete.recaudo_esperado || 0) > 0) {
        additions.push(`Recaudo ${formatCurrency(paquete.recaudo_esperado)}`);
    }

    if (additions.length === 0) {
        return '<span class="text-muted">Sin adicionales</span>';
    }

    return additions.map(text => `<span class="package-pill">${escapeHtml(text)}</span>`).join('');
}

function buildFileUrl(path) {
    const cleanPath = String(path ?? '').trim();
    if (!cleanPath) return '';
    if (/^https?:\/\//i.test(cleanPath)) return cleanPath;
    return `../../${cleanPath.replace(/^(\.\.\/|\.\/|\/)+/, '')}`;
}

function getRecollectionPhotos(rawValue) {
    if (!rawValue) return [];

    if (Array.isArray(rawValue)) {
        return rawValue.filter(Boolean);
    }

    const rawText = String(rawValue).trim();
    if (!rawText) return [];

    try {
        const parsed = JSON.parse(rawText);
        return Array.isArray(parsed) ? parsed.filter(Boolean) : [rawText];
    } catch (error) {
        return [rawText];
    }
}

function buildRecollectionPhotosHtml(recoleccionData) {
    const fotos = getRecollectionPhotos(recoleccionData?.foto_recoleccion);

    if (fotos.length === 0) {
        return '<p class="text-muted">El mensajero aun no ha subido foto de recoleccion.</p>';
    }

    return `
        <div class="recollection-photo-grid">
            ${fotos.map((foto, index) => {
                const url = buildFileUrl(foto);
                return `
                    <a href="${escapeHtml(url)}" target="_blank" rel="noopener" class="recollection-photo-link">
                        <img src="${escapeHtml(url)}" alt="Foto de recoleccion ${index + 1}">
                    </a>
                `;
            }).join('')}
        </div>
    `;
}

function buildRecollectionDataHtml(recoleccionData) {
    if (!recoleccionData) {
        return `
            <div class="recollection-data-empty">
                No hay datos registrados por el mensajero para esta recoleccion.
            </div>
        `;
    }

    const estadoRecoleccion = String(recoleccionData.estado || '').toLowerCase();
    const tieneCierreMensajero = estadoRecoleccion === 'completada'
        || Boolean(recoleccionData.fecha_completada)
        || Boolean(recoleccionData.foto_recoleccion)
        || Number(recoleccionData.cantidad_real || 0) > 0;
    const conformidad = tieneCierreMensajero
        ? (isTruthyValue(recoleccionData.conformidad) ? 'Si' : 'No')
        : 'No registrada';
    const cantidadReal = tieneCierreMensajero
        ? (recoleccionData.cantidad_real || 'No registrada')
        : 'No registrada';
    const fechaCompletada = tieneCierreMensajero && recoleccionData.fecha_completada
        ? new Date(recoleccionData.fecha_completada).toLocaleString()
        : 'No registrada';
    const observacionesMensajero = tieneCierreMensajero
        ? (recoleccionData.observaciones_recoleccion || 'Sin observaciones registradas por el mensajero.')
        : 'El mensajero aun no ha registrado observaciones de cierre.';

    return `
        <div class="recollection-data-grid">
            <div class="detalle-item">
                <div class="detalle-label">Estado</div>
                <div class="detalle-value">${escapeHtml(recoleccionData.estado || 'N/A')}</div>
            </div>
            <div class="detalle-item">
                <div class="detalle-label">Orden</div>
                <div class="detalle-value">${escapeHtml(recoleccionData.numero_orden || 'N/A')}</div>
            </div>
            <div class="detalle-item">
                <div class="detalle-label">Horario</div>
                <div class="detalle-value">${escapeHtml(recoleccionData.horario_preferido || 'N/A')}</div>
            </div>
            <div class="detalle-item">
                <div class="detalle-label">Cantidad estimada</div>
                <div class="detalle-value">${escapeHtml(recoleccionData.cantidad_estimada || 'N/A')}</div>
            </div>
            <div class="detalle-item">
                <div class="detalle-label">Cantidad recogida</div>
                <div class="detalle-value">${escapeHtml(cantidadReal)}</div>
            </div>
            <div class="detalle-item">
                <div class="detalle-label">Conformidad</div>
                <div class="detalle-value">${conformidad}</div>
            </div>
            <div class="detalle-item">
                <div class="detalle-label">Fecha asignacion</div>
                <div class="detalle-value">${escapeHtml(recoleccionData.fecha_asignacion ? new Date(recoleccionData.fecha_asignacion).toLocaleString() : 'N/A')}</div>
            </div>
            <div class="detalle-item">
                <div class="detalle-label">Fecha completada</div>
                <div class="detalle-value">${escapeHtml(fechaCompletada)}</div>
            </div>
        </div>
        <div class="recollection-observations">
            <div class="detalle-label">Observaciones del mensajero</div>
            <div class="detalle-value">${escapeHtml(observacionesMensajero)}</div>
        </div>
        <div class="recollection-photos">
            <div class="detalle-label">Foto subida por el mensajero</div>
            ${buildRecollectionPhotosHtml(recoleccionData)}
        </div>
    `;
}

function buildPackageRows(paquetes) {
    return paquetes.map(paquete => `
        <tr>
            <td>
                <strong>${escapeHtml(paquete.numero_guia || 'Sin guia')}</strong>
                <br><small>${escapeHtml(paquete.estado || '')}</small>
            </td>
            <td>
                ${escapeHtml(paquete.destinatario_nombre || 'Sin destinatario')}
                <br><small>${escapeHtml(paquete.direccion_destino || 'Sin direccion')}</small>
                ${paquete.descripcion_contenido ? `<br><small><strong>Contenido:</strong> ${escapeHtml(paquete.descripcion_contenido)}</small>` : ''}
            </td>
            <td>${getPackageObservations(paquete)}</td>
            <td>${escapeHtml(paquete.dimensiones || 'Sin dimension')}</td>
            <td><div class="package-pills">${getPackageAdditions(paquete)}</div></td>
            <td><strong>${formatCurrency(paquete.costo_envio)}</strong></td>
            <td>
                <button type="button" class="btn btn-sm btn-info btn-ver-guia-paquete" data-package-id="${escapeHtml(paquete.id)}">
                    Ver guia
                </button>
            </td>
        </tr>
    `).join('');
}

function renderAccionesRecoleccion(rec) {
    const ids = escapeHtml(rec.ids);
    const direccion = escapeHtml(rec.direccion_origen);
    const cliente = escapeHtml(rec.cliente_nombre);
    const observaciones = escapeHtml(rec.observaciones_recoleccion || '');
    const esPendiente = rec.estado === 'pendiente';

    if (['entregado', 'completada', 'cancelado'].includes(rec.estado)) {
        return `
            <div class="actions">
                <button class="btn btn-sm btn-info btn-ver-detalles" title="Ver Paquetes" data-ids="${ids}">Ver</button>
            </div>
        `;
    }

    return `
        <div class="actions">
            <button class="btn btn-sm btn-info btn-ver-detalles" title="Ver Paquetes" data-ids="${ids}">Ver</button>
            <button class="btn btn-sm ${esPendiente ? 'btn-warning' : 'btn-secondary'} btn-asignar-recoleccion" title="${esPendiente ? 'Asignar Recoleccion' : 'Reasignar'}" data-ids="${ids}" data-direccion="${direccion}" data-cliente="${cliente}" data-observaciones="${observaciones}">${esPendiente ? 'Asignar' : 'Reasignar'}</button>
            <button class="btn btn-sm btn-danger btn-cancelar-recoleccion" title="Cancelar" data-ids="${ids}">Cancelar</button>
        </div>
    `;
}

function setupEventListeners() {
    document.getElementById('busqueda').addEventListener('input', applyFilters);
    document.getElementById('filtroEstado').addEventListener('change', applyFilters);
    document.getElementById('filtroFecha').addEventListener('change', applyFilters);

    const tablaBody = document.getElementById('tablaRecoleccionesBody');
    if (tablaBody) {
        tablaBody.addEventListener('click', manejarClickTabla);
    }

    const btnCerrarAsignar = document.getElementById('btnCerrarAsignar');
    if (btnCerrarAsignar) {
        btnCerrarAsignar.addEventListener('click', function() {
            document.getElementById('modalAsignarRapido').style.display = 'none';
        });
    }

    const formAsignar = document.getElementById('formAsignarRapido');
    if (formAsignar) {
        formAsignar.addEventListener('submit', handleAsignarSubmit);
    }

    const inputBuscarMensajero = document.getElementById('buscarMensajeroInput');
    if (inputBuscarMensajero) {
        inputBuscarMensajero.addEventListener('input', filtrarListaMensajeros);
    }

    const listaMensajeros = document.getElementById('listaMensajeros');
    if (listaMensajeros) {
        listaMensajeros.addEventListener('click', manejarClickMensajeros);
    }

    const detallesBody = document.getElementById('detallesRecoleccionBody');
    if (detallesBody) {
        detallesBody.addEventListener('click', manejarClickDetallesModal);
    }

    const btnCerrarRotulo = document.getElementById('closeRotuloModal');
    if (btnCerrarRotulo) {
        btnCerrarRotulo.addEventListener('click', cerrarRotulo);
    }

    const btnDownloadRotulo = document.getElementById('btnDownloadRotulo');
    if (btnDownloadRotulo) {
        btnDownloadRotulo.addEventListener('click', descargarRotuloActual);
    }

    const rotuloModal = document.getElementById('rotuloModal');
    if (rotuloModal) {
        rotuloModal.addEventListener('click', function(event) {
            if (event.target === rotuloModal) cerrarRotulo();
        });
    }

    const btnReportes = document.getElementById('btnReportes');
    if (btnReportes) {
        btnReportes.addEventListener('click', () => alert('Funcionalidad de reportes en desarrollo'));
    }
}

function manejarClickTabla(event) {
    const btnDetalles = event.target.closest('.btn-ver-detalles');
    if (btnDetalles) {
        verDetallesPaquetes(btnDetalles.dataset.ids || '');
        return;
    }

    const btnAsignar = event.target.closest('.btn-asignar-recoleccion');
    if (btnAsignar) {
        asignarRecoleccion(
            btnAsignar.dataset.ids || '',
            btnAsignar.dataset.direccion || '',
            btnAsignar.dataset.cliente || '',
            btnAsignar.dataset.observaciones || ''
        );
        return;
    }

    const btnCancelar = event.target.closest('.btn-cancelar-recoleccion');
    if (btnCancelar) {
        cancelarRecoleccion(btnCancelar.dataset.ids || '');
    }
}

function manejarClickMensajeros(event) {
    const item = event.target.closest('.mensajero-item[data-id]');
    if (!item) return;

    seleccionarMensajero(
        item.dataset.id,
        item.dataset.nombre || ''
    );
}

function manejarClickDetallesModal(event) {
    const btnGuia = event.target.closest('.btn-ver-guia-paquete');
    if (!btnGuia) return;

    const paquete = paquetesDetalleActual.find(item => String(item.id) === String(btnGuia.dataset.packageId));
    if (!paquete) {
        alert('No se encontro la informacion de este paquete.');
        return;
    }

    verGuiaPaquete(paquete);
}

async function loadInitialData() {
    try {
        const response = await fetch('../../controller/asignarRecoleccionesController.php?action=listar');
        const data = await response.json();

        if (data.success) {
            recolecciones = data.data;
            renderRecolecciones();
            updateStats(data.stats);
        } else {
            console.error('Error cargando datos:', data.message);
        }
    } catch (error) {
        console.error('Error de red:', error);
    }
}

async function cargarMensajerosEnModal() {
    try {
        const response = await fetch('../../controller/asignarRecoleccionesController.php?action=get_data_init');
        const data = await response.json();

        if (data.success) {
            todosLosMensajeros = data.mensajeros;
        }
    } catch (error) {
        console.error('Error cargando mensajeros:', error);
    }
}

function renderRecolecciones() {
    const tbody = document.getElementById('tablaRecoleccionesBody');

    if (!recolecciones || recolecciones.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px;">No hay recolecciones pendientes.</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = buildRows(recolecciones);
}

function updateStats(stats) {
    if (stats) {
        document.getElementById('totalRecolecciones').textContent = stats.total || 0;
        document.getElementById('pendientes').textContent = stats.pendientes || 0;
        document.getElementById('completadas').textContent = stats.completadas || 0;
    }
}

function applyFilters() {
    const busqueda = document.getElementById('busqueda').value.toLowerCase();
    const estado = document.getElementById('filtroEstado').value;
    const fecha = document.getElementById('filtroFecha').value;

    const filtered = recolecciones.filter(rec => {
        const coincideBusqueda = !busqueda ||
            rec.cliente_nombre.toLowerCase().includes(busqueda) ||
            rec.direccion_origen.toLowerCase().includes(busqueda) ||
            (rec.mensajero_nombre || '').toLowerCase().includes(busqueda);

        const coincideEstado = !estado || getEstadoFiltro(rec.estado) === estado;
        const coincideFecha = !fecha || String(rec.fecha_creacion || '').slice(0, 10) === fecha;

        return coincideBusqueda && coincideEstado && coincideFecha;
    });

    const tbody = document.getElementById('tablaRecoleccionesBody');
    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 20px;">No se encontraron resultados.</td></tr>`;
    } else {
        tbody.innerHTML = buildRows(filtered);
    }
}

function getEstadoFiltro(estado) {
    if (estado === 'pendiente') {
        return 'pendiente';
    }
    if (['asignado', 'asignada', 'en_transito', 'en_ruta', 'en_curso'].includes(estado)) {
        return 'en_transito';
    }
    if (['entregado', 'completada', 'cancelado'].includes(estado)) {
        return 'finalizado';
    }
    return estado || '';
}

function formatEstadoLabel(estado) {
    if (estado === 'entregado' || estado === 'completada') {
        return 'Finalizada';
    }
    return capitalize((estado || '').replace('_', ' '));
}

function capitalize(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

function buildRows(items) {
    return items.map(rec => `
        <tr class="prioridad-${rec.color_prioridad || 'verde'}">
            <td>${rec.direccion_origen}</td>
            <td>${rec.cliente_nombre}</td>
            <td>${rec.mensajero_nombre}</td>
            <td>
                <span class="badge estado-${rec.estado}">
                    ${formatEstadoLabel(rec.estado)}
                </span>
            </td>
            <td>
                <span class="badge badge-info" style="font-size: 1em; background-color: #17a2b8;">
                    ${rec.cantidad} Paquetes
                </span>
            </td>
            <td><small>${rec.guias ? rec.guias.substring(0, 50) + (rec.guias.length > 50 ? '...' : '') : ''}</small></td>
            <td>${new Date(rec.fecha_creacion).toLocaleString()}</td>
            <td>${renderAccionesRecoleccion(rec)}</td>
        </tr>
    `).join('');
}

window.verDetallesPaquetes = async function(ids) {
    const modal = document.getElementById('modalDetalles');
    const container = document.getElementById('detallesRecoleccionBody');

    if (modal && container) {
        modal.style.display = 'flex';
        container.innerHTML = '<p style="text-align:center; padding: 2rem;">Cargando detalles de la base de datos...</p>';

        try {
            const responsePaquetes = await fetch(`../../controller/asignarRecoleccionesController.php?action=detalles&ids=${ids}`);
            const resultPaquetes = await responsePaquetes.json();

            let recoleccionData = null;
            if (resultPaquetes.success && resultPaquetes.data.length > 0) {
                const primerPaqueteId = resultPaquetes.data[0].id;
                const responseRecoleccion = await fetch(`../../controller/asignarRecoleccionesController.php?action=detalles_recoleccion&paquete_id=${primerPaqueteId}`);
                const resultRecoleccion = await responseRecoleccion.json();
                if (resultRecoleccion.success) {
                    recoleccionData = resultRecoleccion.data;
                }
            }

            if (resultPaquetes.success && resultPaquetes.data.length > 0) {
                paquetesDetalleActual = resultPaquetes.data;
                const primerPaquete = resultPaquetes.data[0];
                const clienteNombre = primerPaquete.nombre_emprendimiento || (primerPaquete.cli_nombres + ' ' + primerPaquete.cli_apellidos);

                const datosRecoleccionHtml = buildRecollectionDataHtml(recoleccionData);
                let recoleccionInfoHtml = '<p>No se encontraron detalles de la recoleccion en la tabla `recolecciones`.</p>';
                if (recoleccionData) {
                    recoleccionInfoHtml = `
                        <div class="detalle-grid">
                            <div class="detalle-item"><div class="detalle-label">Orden N°</div><div class="detalle-value">${recoleccionData.numero_orden || 'N/A'}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Horario</div><div class="detalle-value">${recoleccionData.horario_preferido || 'N/A'}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Paquetes Recogidos</div><div class="detalle-value">${recoleccionData.cantidad_real || 'No registrado'}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Fecha Completada</div><div class="detalle-value">${recoleccionData.fecha_completada ? new Date(recoleccionData.fecha_completada).toLocaleString() : 'N/A'}</div></div>
                        </div>
                        <h4 style="margin-top:1rem; margin-bottom:0.5rem;">Observaciones registradas en la recoleccion</h4>
                        <p style="background:#f8f9fa; padding:10px; border-radius:5px; min-height: 40px;">${recoleccionData.observaciones_recoleccion || 'No hay observaciones.'}</p>
                    `;
                }

                let fotosHtml = '<p>No hay fotos adjuntas.</p>';
                if (recoleccionData && recoleccionData.foto_recoleccion) {
                    let fotos = [];
                    try {
                        fotos = JSON.parse(recoleccionData.foto_recoleccion);
                    } catch (e) {
                        if (typeof recoleccionData.foto_recoleccion === 'string' && recoleccionData.foto_recoleccion.trim() !== '') {
                            fotos = [recoleccionData.foto_recoleccion];
                        }
                    }

                    if (Array.isArray(fotos) && fotos.length > 0 && fotos[0]) {
                        fotosHtml = '<div class="fotos-grid" style="display:flex; gap:10px; flex-wrap:wrap;">';
                        fotos.forEach(fotoUrl => {
                            if (fotoUrl) {
                                const fullUrl = `../../${fotoUrl}`;
                                fotosHtml += `<a href="${fullUrl}" target="_blank"><img src="${fullUrl}" alt="Foto de recoleccion" style="width:100px; height:100px; object-fit:cover; border-radius:5px;"></a>`;
                            }
                        });
                        fotosHtml += '</div>';
                    }
                }

                const html = `
                    <div class="detalle-section">
                        <h3 style="margin-bottom: 15px;">Informacion General</h3>
                        <div class="detalle-grid">
                            <div class="detalle-item"><div class="detalle-label">Cliente</div><div class="detalle-value">${clienteNombre}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Direccion</div><div class="detalle-value">${primerPaquete.direccion_origen}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Telefono</div><div class="detalle-value">${primerPaquete.cli_telefono || 'N/A'}</div></div>
                        </div>
                        <div style="margin-top: 12px;">
                            <div class="detalle-label">Observaciones de recoleccion solicitadas</div>
                            <div class="detalle-value" style="background:#f8f9fa; padding:10px; border-radius:5px; min-height: 40px;">${primerPaquete.observaciones_recoleccion || 'Sin observaciones de recoleccion.'}</div>
                        </div>
                    </div>
                    <div class="detalle-section">
                        <h3 style="margin-top: 20px;">Datos de recoleccion</h3>
                        ${datosRecoleccionHtml}
                    </div>
                    <div class="detalle-section">
                        <h3 style="margin-top: 20px;">Paquetes Incluidos (${resultPaquetes.data.length})</h3>
                        <div class="packages-detail-table-wrap">
                            <table class="packages-detail-table">
                                <thead>
                                    <tr>
                                        <th>Guia</th>
                                        <th>Destinatario</th>
                                        <th>Observaciones</th>
                                        <th>Dimension</th>
                                        <th>Adicionales</th>
                                        <th>Costo</th>
                                        <th>Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${buildPackageRows(paquetesDetalleActual)}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                container.innerHTML = html;
            } else {
                paquetesDetalleActual = [];
                container.innerHTML = '<p class="text-danger text-center">No se encontraron detalles de los paquetes.</p>';
            }
        } catch (error) {
            console.error(error);
            container.innerHTML = '<p class="text-danger text-center">Error al cargar detalles.</p>';
        }
    }
};

async function verGuiaPaquete(paquete) {
    const modal = document.getElementById('rotuloModal');
    const preview = document.getElementById('rotuloPreview');

    if (!modal || !preview) {
        alert('No se encontro el modal de la guia.');
        return;
    }

    currentRotuloData = {
        guia: paquete.numero_guia,
        remitente_nombre: paquete.remitente_nombre || 'EcoBikeMess',
        tienda_nombre: paquete.nombre_emprendimiento || paquete.remitente_nombre || 'Tienda',
        destinatario_nombre: paquete.destinatario_nombre,
        destinatario_direccion: paquete.direccion_destino,
        destinatario_telefono: paquete.destinatario_telefono || '',
        destinatario_observaciones: paquete.instrucciones_entrega || paquete.observaciones_entrega || 'Sin observaciones',
        cambios: normalizeYesNo(paquete.recoger_cambios),
        recaudo: paquete.recaudo_esperado || 0
    };

    if (!window.RotuloEcoBike) {
        alert('El generador de guias no esta disponible.');
        return;
    }

    try {
        preview.innerHTML = '<p style="text-align:center; padding: 2rem;">Cargando guia...</p>';
        modal.style.display = 'flex';
        await window.RotuloEcoBike.mountPreview(preview, currentRotuloData);
    } catch (error) {
        console.error('Error mostrando guia:', error);
        alert('No se pudo cargar la guia.');
    }
}

function cerrarRotulo() {
    const modal = document.getElementById('rotuloModal');
    if (modal) modal.style.display = 'none';
}

async function descargarRotuloActual() {
    if (!currentRotuloData || !window.RotuloEcoBike) {
        alert('Primero abre una guia.');
        return;
    }

    try {
        await window.RotuloEcoBike.downloadPdf(currentRotuloData, { filePrefix: 'Guia' });
    } catch (error) {
        console.error('Error descargando guia:', error);
        alert('No se pudo generar el PDF de la guia.');
    }
}

window.asignarRecoleccion = function(ids, direccion, cliente, observaciones = '') {
    const modal = document.getElementById('modalAsignarRapido');
    if (modal) {
        const infoContainer = document.getElementById('infoRecoleccionAsignar');
        if (infoContainer) {
            if (direccion && cliente) {
                infoContainer.innerHTML = `
                    <p style="margin:0; font-size: 0.9em; color: #6c757d;">Asignando recoleccion para:</p>
                    <p style="margin:2px 0 0; font-weight: 600;"><strong>Cliente:</strong> ${cliente}</p>
                    <p style="margin:2px 0 0; font-weight: 600;"><strong>Direccion:</strong> ${direccion}</p>
                    <p style="margin:6px 0 0; font-weight: 600;"><strong>Observaciones:</strong> ${observaciones || 'Sin observaciones de recoleccion.'}</p>`;
            } else {
                infoContainer.innerHTML = '<p>Informacion de recoleccion no disponible.</p>';
            }
        }

        document.getElementById('idsPaquetesHidden').value = ids;
        document.getElementById('mensajeroIdHidden').value = '';
        document.getElementById('buscarMensajeroInput').value = '';

        if (todosLosMensajeros.length === 0) {
            cargarMensajerosEnModal().then(() => {
                renderizarListaMensajeros(todosLosMensajeros);
            });
        } else {
            renderizarListaMensajeros(todosLosMensajeros);
        }

        document.querySelectorAll('.mensajero-item').forEach(el => el.classList.remove('selected'));
        modal.style.display = 'flex';
    }
};

function renderizarListaMensajeros(lista) {
    const contenedor = document.getElementById('listaMensajeros');
    if (!contenedor) return;

    if (lista.length === 0) {
        contenedor.innerHTML = '<div class="mensajero-item text-muted" style="padding:10px; text-align:center;">No se encontraron mensajeros</div>';
        return;
    }

    let html = '';
    lista.forEach(m => {
        const tareas = m.tareas_activas || 0;
        const estadoColor = (m.estado === 'activo' || m.estado === 'en_ruta') ? 'green' : 'gray';
        html += `
            <div class="mensajero-item" data-id="${m.id}" data-nombre="${escapeHtml(m.nombre)}">
                <div style="font-weight:bold;">${m.nombre}</div>
                <div style="font-size:0.85em; color:#666;">
                    <span style="color:${estadoColor}">● ${m.estado}</span> | Tareas activas: ${tareas}
                </div>
            </div>
        `;
    });
    contenedor.innerHTML = html;
}

function filtrarListaMensajeros(e) {
    const texto = e.target.value.toLowerCase();
    const filtrados = todosLosMensajeros.filter(m =>
        m.nombre.toLowerCase().includes(texto)
    );
    renderizarListaMensajeros(filtrados);
}

window.seleccionarMensajero = function(id, nombre) {
    document.getElementById('mensajeroIdHidden').value = id;
    document.getElementById('buscarMensajeroInput').value = nombre;

    document.querySelectorAll('.mensajero-item').forEach(el => el.classList.remove('selected'));
    const item = document.querySelector(`.mensajero-item[data-id="${id}"]`);
    if (item) {
        item.classList.add('selected');
    }
};

async function handleAsignarSubmit(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append('action', 'asignar');

    if (!formData.get('mensajero_id')) {
        alert('Por favor seleccione un mensajero de la lista.');
        return;
    }

    try {
        const response = await fetch('../../controller/asignarRecoleccionesController.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert('Mensajero asignado correctamente');
            document.getElementById('modalAsignarRapido').style.display = 'none';
            loadInitialData();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ocurrio un error al procesar la solicitud');
    }
}

window.cancelarRecoleccion = async function(ids) {
    if (!confirm('Estas seguro de ocultar esta recoleccion de la vista? No se eliminara de la base de datos ni se cambiara su estado real.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'cancelar');
    formData.append('ids_paquetes', ids);

    try {
        const response = await fetch('../../controller/asignarRecoleccionesController.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            loadInitialData();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ocurrio un error al procesar la solicitud');
    }
};
