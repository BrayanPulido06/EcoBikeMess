// Variable global para almacenar todos los mensajeros
let todosLosMensajeros = [];
let todosLosClientes = [];
let currentData = []; // Almacenar datos actuales de la tabla para exportación

document.addEventListener('DOMContentLoaded', function() {
    console.log('Script paquetesAdmin.js cargado correctamente');

    // --- REFERENCIAS AL DOM ---
    // Asegúrate de que tu tabla en HTML tenga <tbody id="tablaPaquetesBody">
    const tableBody = document.getElementById('tablaPaquetesBody');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const btnExportExcel = document.getElementById('btnExportarExcel');
    const btnExportarGuias = document.getElementById('btnExportarGuias');
    const selectAllCheckbox = document.getElementById('selectAll');
    const btnNuevoPaquete = document.getElementById('btnNuevoPaquete');
    const filtroClienteInput = document.getElementById('filtroClienteInput');
    const filtroClienteOpciones = document.getElementById('filtroClienteOpciones');
    const filtroMensajeroInput = document.getElementById('filtroMensajeroInput');
    const filtroMensajeroOpciones = document.getElementById('filtroMensajeroOpciones');
    
    // Referencias a los filtros (Asegúrate de que los IDs en tu HTML coincidan)
    const inputs = {
        search: document.getElementById('searchInput'),        // Input tipo text (Corregido ID)
        fechaDesde: document.getElementById('filtroFechaDesde'), // Input date
        fechaHasta: document.getElementById('filtroFechaHasta'), // Input date
        cliente: filtroClienteInput,
        estado: document.getElementById('filtroEstado'),       // Select
        mensajero: filtroMensajeroInput
    };

    // Referencias a Modales
    const modals = {
        detalles: document.getElementById('modalDetalles'),
        asignar: document.getElementById('modalAsignar'),
        editar: document.getElementById('modalEditar')
    };

    // --- INICIALIZACIÓN ---
    listarPaquetes(); // Carga la tabla inicial
    setupModalClosers(); // Configura los botones de cerrar modales

    // --- EVENTOS ---
    // Agregar evento 'change' a todos los filtros para que la tabla se actualice sola
    Object.values(inputs).forEach(input => {
        if (input) {
            input.addEventListener('change', listarPaquetes);
        }
    });

    const listarPaquetesDebounced = debounce(listarPaquetes, 250);
    filtroClienteInput?.addEventListener('input', listarPaquetesDebounced);
    filtroMensajeroInput?.addEventListener('input', listarPaquetesDebounced);

    configurarBuscadorFiltro({
        input: filtroClienteInput,
        optionsContainer: filtroClienteOpciones,
        getItems: () => todosLosClientes,
        emptyLabel: 'Todos los clientes',
        onSelectionChange: listarPaquetes
    });

    configurarBuscadorFiltro({
        input: filtroMensajeroInput,
        optionsContainer: filtroMensajeroOpciones,
        getItems: () => todosLosMensajeros,
        emptyLabel: 'Todos los mensajeros',
        onSelectionChange: listarPaquetes
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-select')) {
            document.querySelectorAll('.search-select.open').forEach(el => el.classList.remove('open'));
        }
    });

    // Botón Limpiar Filtros
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            Object.values(inputs).forEach(input => {
                if (input) input.value = '';
            });
            if (filtroClienteInput) filtroClienteInput.value = '';
            if (filtroMensajeroInput) filtroMensajeroInput.value = '';
            listarPaquetes();
        });
    }
    
    // Evento para filtrar mensajeros en el modal al escribir
    const inputBuscarMensajero = document.getElementById('buscarMensajeroInput');
    if (inputBuscarMensajero) {
        inputBuscarMensajero.addEventListener('input', filtrarListaMensajeros);
    }

    // Permitir buscar al presionar Enter en el campo de texto
    if (inputs.search) {
        inputs.search.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') listarPaquetes();
        });
    }

    // Formulario Asignar Mensajero
    const formAsignar = document.getElementById('formAsignarMensajero');
    if (formAsignar) {
        formAsignar.addEventListener('submit', function(e) {
            e.preventDefault();
            asignarMensajeroAction();
        });
    }

    // Botones Cancelar en Modales
    document.getElementById('btnCancelarAsignar')?.addEventListener('click', () => closeModal('asignar'));
    document.getElementById('btnCancelarEditar')?.addEventListener('click', () => closeModal('editar'));

    // --- EXPORTACIÓN ---
    if (btnExportExcel) btnExportExcel.addEventListener('click', exportarExcel);
    if (btnExportarGuias) btnExportarGuias.addEventListener('click', descargarGuiasSeleccionadas);

    // --- NUEVO PAQUETE ---
    if (btnNuevoPaquete) {
        btnNuevoPaquete.addEventListener('click', () => window.location.href = 'digitarAdmin.php');
    }

    // --- SELECCIONAR TODOS ---
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.paquete-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                    actualizarFilaSeleccionada(cb);
                });
            });
        }
        document.addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains('paquete-checkbox')) {
                actualizarFilaSeleccionada(e.target);
                const all = document.querySelectorAll('.paquete-checkbox');
                const checked = document.querySelectorAll('.paquete-checkbox:checked');
                if (selectAllCheckbox) selectAllCheckbox.checked = all.length > 0 && all.length === checked.length;
            }
        });

    // --- FUNCIONES ---

    // 2. Obtener datos y renderizar la tabla
    function listarPaquetes() {
        // Mostrar indicador de carga
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="15" style="text-align:center;">Cargando datos...</td></tr>';
        }

        // Construir URL con los parámetros de los filtros
        const params = new URLSearchParams();
        params.append('action', 'listar');
        
        for (const [key, input] of Object.entries(inputs)) {
            if (input && input.value) {
                params.append(key, input.value);
            }
        }

        // Petición AJAX al controlador
        fetch(`../../controller/paquetesAdminController.php?${params.toString()}`)
            .then(response => response.json())
            .then(response => {
                if (response.data) {
                    renderizarTabla(response.data);
                } else if (response.error) {
                    console.error('Error del servidor:', response.error);
                    if (tableBody) tableBody.innerHTML = `<tr><td colspan="15" class="text-danger text-center">Error: ${response.error}</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                if (tableBody) tableBody.innerHTML = `<tr><td colspan="15" class="text-danger text-center">Error de conexión al cargar datos.</td></tr>`;
            });
    }
    window.listarPaquetes = listarPaquetes;

    // 3. Generar el HTML de las filas
    function renderizarTabla(data) {
        if (!tableBody) return;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="15" style="text-align:center;">No se encontraron paquetes con estos filtros.</td></tr>';
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            return;
        }

        currentData = data; // Guardar datos para exportación

        let html = '';
        data.forEach(p => {
            // Definir color del badge según estado
            let badgeClass = 'secondary'; // Gris por defecto
            
            switch(p.estado) {
                case 'entregado': badgeClass = 'success'; break;   // Verde
                case 'cancelado': badgeClass = 'danger'; break;    // Rojo
                case 'pendiente': badgeClass = 'warning'; break;   // Amarillo
                case 'en_transito': badgeClass = 'primary'; break; // Azul
                case 'asignado': badgeClass = 'info'; break;       // Cian
                case 'devuelto': badgeClass = 'dark'; break;       // Oscuro
            }

            // Definir color y etiqueta del estado de recolección
            let badgeRecoleccion = 'secondary';
            let estadoRecLabel = 'Sin asignar';
            const estadoRec = p.estado_recoleccion || '';
            if (estadoRec) {
                switch (estadoRec) {
                    case 'completada':
                        badgeRecoleccion = 'success';
                        estadoRecLabel = 'Recolectada';
                        break;
                    case 'cancelada':
                        badgeRecoleccion = 'danger';
                        estadoRecLabel = 'Cancelada';
                        break;
                    case 'en_curso':
                        badgeRecoleccion = 'primary';
                        estadoRecLabel = 'En tránsito';
                        break;
                    case 'asignada':
                        badgeRecoleccion = 'info';
                        estadoRecLabel = 'Asignado';
                        break;
                    default:
                        badgeRecoleccion = 'secondary';
                        estadoRecLabel = estadoRec.toUpperCase().replace('_', ' ');
                        break;
                }
            }

            // Formatear valor a moneda
            const recaudoFormateado = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(p.recaudo_esperado || 0);
            const recaudoRealFormateado = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(p.recaudo_real || 0);
            const valorEnvioFormateado = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(p.costo_envio || 0);

            const mensajeroEntrega = p.mensajero_entrega || '<span class="text-muted font-italic">Sin asignar</span>';
            const mensajeroRecoleccion = p.mensajero_recoleccion || '<span class="text-muted font-italic">Sin asignar</span>';
            const envioAgregado = String(p.envio_destinatario || '').toLowerCase() === 'si'
                ? `<span class="badge badge-success">Sí</span> ${valorEnvioFormateado}`
                : `<span class="badge badge-secondary">No</span> ${valorEnvioFormateado}`;

            html += `
                <tr class="paquete-row">
                    <td><input type="checkbox" class="paquete-checkbox" value="${p.id}"></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-warning" onclick="cargarRotuloAdmin(${p.id})" title="Guía">🏷️ Guía</button>
                            <button class="btn btn-sm btn-info" onclick="verDetalle(${p.id})" title="Ver Detalle">👁️</button>
                            ${p.estado !== 'entregado' && p.estado !== 'cancelado' ? `<button class="btn btn-sm btn-success" onclick="cerrarPaqueteAdmin(${p.id})" title="Cerrar paquete">✅ Cerrar</button>` : ''}
                            ${p.estado !== 'entregado' && p.estado !== 'cancelado' ? `<button class="btn btn-sm btn-warning" onclick="abrirModalAsignar(${p.id}, '${p.guia}')" title="Asignar/Reasignar">🚴 Asignar</button>` : ''}
                        </div>
                    </td>
                    <td>${p.fechaIngreso}</td>
                    <td>${p.remitente || '<span class="text-muted">N/A</span>'}</td>
                    <td>${p.nombre_persona || '<span class="text-muted">N/A</span>'}</td>
                    <td>${p.destinatario}</td>
                    <td>${p.direccion}</td>
                    <td>${mensajeroRecoleccion}</td>
                    <td><span class="badge badge-${badgeRecoleccion}">${estadoRecLabel}</span></td>
                    <td>${mensajeroEntrega}</td>
                    <td><span class="badge badge-${badgeClass}">${p.estado.toUpperCase().replace('_', ' ')}</span></td>
                    <td>${recaudoFormateado}</td>
                    <td>${recaudoRealFormateado}</td>
                    <td>${envioAgregado}</td>
                    <td>${p.guia}</td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;
        sincronizarFilasSeleccionadas();
    }

    function actualizarFilaSeleccionada(checkbox) {
        const fila = checkbox.closest('tr');
        if (!fila) return;
        fila.classList.toggle('selected-row', checkbox.checked);
    }

    function sincronizarFilasSeleccionadas() {
        const checkboxes = document.querySelectorAll('.paquete-checkbox');
        checkboxes.forEach(actualizarFilaSeleccionada);

        if (selectAllCheckbox) {
            const checked = document.querySelectorAll('.paquete-checkbox:checked');
            selectAllCheckbox.checked = checkboxes.length > 0 && checkboxes.length === checked.length;
        }
    }

    function normalizarTexto(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function escaparAtributoHtml(valor) {
        return String(valor || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function debounce(fn, wait = 250) {
        let timeoutId;
        return (...args) => {
            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(() => fn(...args), wait);
        };
    }

    function configurarBuscadorFiltro({ input, optionsContainer, getItems, emptyLabel, onSelectionChange }) {
        if (!input || !optionsContainer) return;

        const wrapper = input.closest('.search-select');

        const renderOptions = (query = '') => {
            const normalizedQuery = normalizarTexto(query);
            const items = getItems();
            const filtrados = normalizedQuery
                ? items.filter(item => normalizarTexto(item.nombre).includes(normalizedQuery))
                : items;

            let html = `<div class="search-select-option" data-value="" data-label="${escaparAtributoHtml(emptyLabel)}">${emptyLabel}</div>`;

            if (filtrados.length === 0) {
                html += '<div class="search-select-empty">No se encontraron resultados</div>';
            } else {
                filtrados.forEach(item => {
                    html += `<div class="search-select-option" data-value="${item.id}" data-label="${escaparAtributoHtml(item.nombre)}">${item.nombre}</div>`;
                });
            }

            optionsContainer.innerHTML = html;
            wrapper?.classList.add('open');
        };

        const seleccionarOpcion = (value, label) => {
            input.value = value ? (label || '') : '';
            wrapper?.classList.remove('open');
            onSelectionChange?.();
        };

        input.addEventListener('focus', () => renderOptions(input.value));

        input.addEventListener('input', () => {
            renderOptions(input.value);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                wrapper?.classList.remove('open');
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                const exacta = getItems().find(item => normalizarTexto(item.nombre) === normalizarTexto(input.value));
                if (exacta) {
                    seleccionarOpcion(String(exacta.id), exacta.nombre);
                }
            }
        });

        input.addEventListener('blur', () => {
            window.setTimeout(() => {
                const exacta = getItems().find(item => normalizarTexto(item.nombre) === normalizarTexto(input.value));
                if (input.value.trim() === '') {
                    input.value = '';
                } else if (exacta) {
                    input.value = exacta.nombre;
                }
                wrapper?.classList.remove('open');
            }, 150);
        });

        optionsContainer.addEventListener('mousedown', (e) => {
            const option = e.target.closest('.search-select-option');
            if (!option) return;
            seleccionarOpcion(option.dataset.value || '', option.dataset.label || '');
        });
    }

    // --- FUNCIONES DE EXPORTACIÓN ---
    function getPaquetesParaExportar() {
        const selectedCheckboxes = document.querySelectorAll('.paquete-checkbox:checked');
        let dataToExport = [];

        if (selectedCheckboxes.length > 0) {
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value.toString());
            dataToExport = currentData.filter(p => selectedIds.includes(p.id.toString()));
        } else {
            dataToExport = currentData;
        }
        return dataToExport;
    }

    function exportarExcel() {
        const data = getPaquetesParaExportar();
        if (data.length === 0) {
            alert("No hay datos para exportar");
            return;
        }

        const exportData = data.map(p => ({
            "Guía": p.guia,
            "Fecha": p.fechaIngreso,
            "Remitente": p.remitente,
            "Nombre": p.nombre_persona,
            "Destinatario": p.destinatario,
            "Dirección": p.direccion,
            "Mensajero Recoge": p.mensajero_recoleccion || 'Sin asignar',
            "Estado Rec.": p.estado_recoleccion || 'pendiente',
            "Mensajero Entrega": p.mensajero_entrega || 'Sin asignar',
            "Estado Entrega": p.estado,
            "Recaudo": p.recaudo_esperado || 0,
            "Recaudo Real": p.recaudo_real || 0,
            "Valor Envio": p.costo_envio || 0,
            "Envio Agregado": p.envio_destinatario || 'no'
        }));

        const ws = XLSX.utils.json_to_sheet(exportData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Paquetes");
        XLSX.writeFile(wb, `Paquetes_EcoBikeMess_${new Date().toISOString().slice(0,10)}.xlsx`);
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
        const selectedCheckboxes = document.querySelectorAll('.paquete-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            alert('Selecciona al menos un paquete.');
            return;
        }

        const ids = Array.from(selectedCheckboxes).map(cb => cb.value);
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', [100, 100]);
        let first = true;

        for (const id of ids) {
            try {
                const res = await fetch(`../../controller/paquetesAdminController.php?action=detalle&id=${id}`);
                const response = await res.json();
                const info = response.info;
                if (!info) continue;

                const datos = {
                    guia: info.numero_guia,
                    remitente_nombre: info.remitente || 'EcoBikeMess',
                    tienda_nombre: info.tienda_nombre || info.remitente || 'Tienda',
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
});

// --- FUNCIONES GLOBALES (Para los botones de la tabla) ---

// 1. Cargar opciones para los Selects (Clientes y Mensajeros) - AHORA GLOBAL
function cargarFiltros() {
    fetch('../../controller/paquetesAdminController.php?action=filtros')
        .then(response => response.json())
        .then(data => {
            if (data.clientes) {
                todosLosClientes = data.clientes;
            }

            if (data.mensajeros) {
                todosLosMensajeros = data.mensajeros;
                renderizarListaMensajeros(todosLosMensajeros);
            }
        })
        .catch(error => console.error('Error cargando filtros:', error));
}
// Llamar a cargar filtros al inicio
cargarFiltros();

// Función para renderizar la lista de mensajeros en el modal (divs en lugar de options)
function renderizarListaMensajeros(lista) {
    const contenedor = document.getElementById('listaMensajeros');
    if (!contenedor) return;

    if (lista.length === 0) {
        contenedor.innerHTML = '<div class="mensajero-item text-muted">No se encontraron mensajeros</div>';
        return;
    }

    let html = '';
    lista.forEach(m => {
        const tareas = m.tareas_activas || 0;
        const estadoColor = m.estado === 'activo' ? 'green' : 'gray';
        html += `
            <div class="mensajero-item" onclick="seleccionarMensajero(${m.id}, '${m.nombre}')" data-id="${m.id}">
                <div style="font-weight:bold;">${m.nombre}</div>
                <div style="font-size:0.85em; color:#666;">
                    <span style="color:${estadoColor}">● ${m.estado}</span> | Tareas activas: ${tareas}
                </div>
            </div>
        `;
    });
    contenedor.innerHTML = html;
}

// Función para filtrar la lista cuando el usuario escribe
function filtrarListaMensajeros(e) {
    const texto = e.target.value.toLowerCase();
    const filtrados = todosLosMensajeros.filter(m => 
        m.nombre.toLowerCase().includes(texto)
    );
    renderizarListaMensajeros(filtrados);
}

function verDetalle(id, options = {}) {
    const modal = document.getElementById('modalDetalles');
    const container = document.getElementById('detallesPaquete');
    
    if (modal && container) {
        modal.style.display = 'flex'; // Mostrar modal
        container.innerHTML = '<p style="text-align:center">Cargando historial...</p>';

        fetch(`../../controller/paquetesAdminController.php?action=detalle&id=${id}`)
            .then(res => res.json())
            .then(data => {
                const info = data.info;
                const historial = data.historial || [];
                const imagenes = data.imagenes || [];
                const novedades = data.novedades || [];

                if (!info) {
                    const msg = data.error ? `Error: ${data.error}` : 'No se encontró información del paquete.';
                    container.innerHTML = `<p class="text-danger text-center">${msg}</p>`;
                    return;
                }

                if (todosLosMensajeros.length === 0) {
                    cargarFiltros();
                }

                // Formateadores
                const currency = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 });
                
                let badgeClass = 'secondary';
                switch(info.estado) {
                    case 'entregado': badgeClass = 'success'; break;
                    case 'cancelado': badgeClass = 'danger'; break;
                    case 'pendiente': badgeClass = 'warning'; break;
                    case 'en_transito': badgeClass = 'primary'; break;
                    case 'asignado': badgeClass = 'info'; break;
                    case 'devuelto': badgeClass = 'dark'; break;
                }

                const escapeHtml = (value) => {
                    if (value === null || value === undefined) return '';
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                };

                const toInputDateTime = (value) => {
                    if (!value) return '';
                    const cleaned = String(value).replace(' ', 'T');
                    return cleaned.slice(0, 16);
                };

                const toDbDateTime = (value) => {
                    if (!value) return '';
                    const normalized = String(value).replace('T', ' ');
                    return normalized.length === 16 ? `${normalized}:00` : normalized;
                };

                const renderMensajeroOptions = (selectedId) => {
                    let opts = `<option value="">Sin asignar</option>`;
                    todosLosMensajeros.forEach(m => {
                        const sel = String(m.id) === String(selectedId) ? 'selected' : '';
                        opts += `<option value="${m.id}" ${sel}>${escapeHtml(m.nombre)}</option>`;
                    });
                    return opts;
                };

                const renderTipoOptions = (value) => {
                    const base = [
                        { value: 'entrega_simple', label: 'Entrega Simple' },
                        { value: 'contraentrega', label: 'Contraentrega' }
                    ];
                    if (value && !base.find(o => o.value === value)) {
                        base.push({ value, label: value });
                    }
                    return base.map(o => `<option value="${o.value}" ${o.value === value ? 'selected' : ''}>${o.label}</option>`).join('');
                };

                const renderEstadoOptions = (value) => {
                    const estados = [
                        { value: 'pendiente', label: 'Pendiente' },
                        { value: 'asignado', label: 'Asignado' },
                        { value: 'en_transito', label: 'En tránsito' },
                        { value: 'en_ruta', label: 'En ruta' },
                        { value: 'entregado', label: 'Entregado' },
                        { value: 'devuelto', label: 'Devuelto' },
                        { value: 'cancelado', label: 'Cancelado' }
                    ];
                    return estados.map(o => `<option value="${o.value}" ${o.value === value ? 'selected' : ''}>${o.label}</option>`).join('');
                };

                const evidenciaItems = [];
                if (info.infoEntrega && info.infoEntrega.fotoPrincipal) {
                    evidenciaItems.push({
                        tipo: 'entrega',
                        label: 'Entrega principal',
                        ruta: info.infoEntrega.fotoPrincipal,
                        target: 'entrega_principal',
                        allowDelete: true
                    });
                }
                if (info.infoEntrega && info.infoEntrega.fotoAdicional) {
                    evidenciaItems.push({
                        tipo: 'entrega',
                        label: 'Entrega adicional',
                        ruta: info.infoEntrega.fotoAdicional,
                        target: 'entrega_adicional',
                        allowDelete: true
                    });
                }
                if (info.infoCancelacion && info.infoCancelacion.foto) {
                    evidenciaItems.push({
                        tipo: 'cancelacion',
                        label: 'Cancelación',
                        ruta: info.infoCancelacion.foto,
                        target: 'cancelacion',
                        allowDelete: true
                    });
                }

                const cap = (v) => String(v || '').charAt(0).toUpperCase() + String(v || '').slice(1);
                novedades.forEach((n) => {
                    if (n && n.foto_evidencia) {
                        evidenciaItems.push({
                            tipo: n.tipo || 'novedad',
                            label: `${cap(n.tipo)} (${n.fecha_registro || ''})`,
                            ruta: n.foto_evidencia,
                            allowDelete: false
                        });
                    }
                    if (n && n.foto_adicional) {
                        evidenciaItems.push({
                            tipo: n.tipo || 'novedad',
                            label: `${cap(n.tipo)} adicional (${n.fecha_registro || ''})`,
                            ruta: n.foto_adicional,
                            allowDelete: false
                        });
                    }
                });

                const extraItems = imagenes.map(img => ({
                    tipo: img.tipo || 'general',
                    label: `Imagen ${img.tipo || 'general'}`,
                    ruta: img.ruta_archivo,
                    imageId: img.id
                }));

                const renderEvidenciaCard = (item) => {
                    const rutaRaw = item.ruta || item.ruta_archivo;
                    if (!rutaRaw) return '';
                    const ruta = String(rutaRaw).replace(/^\/+/, '');
                    const fullPath = `../../${ruta}`;

                    const canDelete = (item.allowDelete !== false) && (item.imageId || item.target);
                    const deleteAttrs = item.imageId
                        ? `data-action="eliminar-imagen" data-image-id="${item.imageId}"`
                        : (item.target ? `data-action="eliminar-imagen" data-target="${item.target}"` : '');

                    const replaceInput = item.target ? `
                        <label class="btn btn-sm btn-secondary">
                            Reemplazar
                            <input type="file" class="input-reemplazar" data-target="${item.target}" data-paquete-id="${info.paquete_id}" accept="image/*" hidden>
                        </label>
                    ` : '';

                    const deleteButton = canDelete
                        ? `<button class="btn btn-sm btn-danger" ${deleteAttrs}>Eliminar</button>`
                        : '';

                    const actions = (replaceInput || deleteButton) ? `
                        <div class="evidencia-actions">
                            ${replaceInput}
                            ${deleteButton}
                        </div>
                    ` : '';
                    return `
                        <div class="evidencia-card">
                            <a href="${fullPath}" class="js-image-lightbox" data-lightbox-src="${fullPath}" data-lightbox-alt="${escapeHtml(item.label)}" aria-label="${escapeHtml(item.label)}">
                                <img src="${fullPath}" alt="${escapeHtml(item.label)}">
                            </a>
                            <div class="evidencia-meta">
                                <span>${escapeHtml(item.label)}</span>
                                <span class="badge badge-secondary">${escapeHtml(item.tipo)}</span>
                            </div>
                            ${actions}
                        </div>
                    `;
                };

                const aplazados = novedades.filter(n => (n?.tipo || '').toLowerCase() === 'aplazado');
                const cancelaciones = novedades.filter(n => (n?.tipo || '').toLowerCase() === 'cancelado');

                const renderNovedadFotos = (n) => {
                    const fotos = [];
                    if (n?.foto_evidencia) fotos.push({ ruta: n.foto_evidencia, label: 'Evidencia' });
                    if (n?.foto_adicional) fotos.push({ ruta: n.foto_adicional, label: 'Adicional' });
                    if (fotos.length === 0) return '<span class="text-muted">Sin fotos</span>';

                    return fotos.map(f => {
                        const ruta = String(f.ruta).replace(/^\/+/, '');
                        const fullPath = `../../${ruta}`;
                        const alt = `${cap(n.tipo)} - ${f.label}`;
                        return `
                            <a href="${fullPath}" class="js-image-lightbox" data-lightbox-src="${fullPath}" data-lightbox-alt="${escapeHtml(alt)}" aria-label="${escapeHtml(alt)}" style="display:inline-block;width:120px;height:120px;border:1px solid #ddd;border-radius:10px;overflow:hidden;margin-right:10px;">
                                <img src="${fullPath}" alt="${escapeHtml(alt)}" style="width:100%;height:100%;object-fit:cover;display:block;">
                            </a>
                        `;
                    }).join('');
                };

                const renderNovedadesSection = (title, items) => {
                    if (!items || items.length === 0) return '';
                    return `
                        <div class="detalle-section" style="margin-top: 20px;">
                            <h3>${title}</h3>
                            <div style="display:flex;flex-direction:column;gap:12px;">
                                ${items.map(n => `
                                    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff;">
                                        <div class="text-muted small">${escapeHtml(n.fecha_registro || '')}</div>
                                        <p style="margin:6px 0 0;font-size:0.95em;">${escapeHtml(n.descripcion || '')}</p>
                                        <small class="text-muted">Mensajero: ${escapeHtml(n.mensajero || 'Sin información')}</small>
                                        <div style="margin-top:10px;">
                                            ${renderNovedadFotos(n)}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                };

                let html = `
                    <form id="formEditarDetalles" data-paquete-id="${info.paquete_id}">
                        <div class="detalle-section">
                            <h3>📦 Información del Paquete</h3>
                            <div class="detalle-grid">
                                <div class="detalle-item">
                                    <div class="detalle-label">Número de Guía</div>
                                    <input class="form-control" name="numero_guia" value="${escapeHtml(info.numero_guia)}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Estado Actual</div>
                                    <select class="form-control" name="estado">
                                        ${renderEstadoOptions(info.estado)}
                                    </select>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Fecha de Ingreso</div>
                                    <input class="form-control" type="datetime-local" name="fecha_creacion" value="${escapeHtml(toInputDateTime(info.fecha_creacion))}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Remitente</div>
                                    <input class="form-control" name="remitente_nombre" value="${escapeHtml(info.remitente_editable || info.remitente || '')}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Destinatario</div>
                                    <input class="form-control" name="destinatario_nombre" value="${escapeHtml(info.destinatario_nombre || '')}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Teléfono Destinatario</div>
                                    <input class="form-control" name="destinatario_telefono" value="${escapeHtml(info.destinatario_telefono || '')}">
                                </div>
                                <div class="detalle-item" style="grid-column: span 2;">
                                    <div class="detalle-label">Dirección de Entrega</div>
                                    <textarea class="form-control" name="direccion_destino" rows="2">${escapeHtml(info.direccion_destino || '')}</textarea>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Mensajero Recolección</div>
                                    <select class="form-control" name="mensajero_recoleccion_id">
                                        ${renderMensajeroOptions(info.mensajero_recoleccion_id)}
                                    </select>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Mensajero Entrega</div>
                                    <select class="form-control" name="mensajero_id">
                                        ${renderMensajeroOptions(info.mensajero_id)}
                                    </select>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Tipo de Paquete</div>
                                    <select class="form-control" name="tipo_servicio">
                                        ${renderTipoOptions(info.tipo_paquete)}
                                    </select>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Contenido</div>
                                    <input class="form-control" name="descripcion_contenido" value="${escapeHtml(info.descripcion_contenido || '')}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Costo Envío</div>
                                    <input class="form-control" type="number" name="costo_envio" step="0.01" min="0" value="${escapeHtml(info.costo_envio || 0)}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Valor a Recaudar</div>
                                    <input class="form-control" type="number" name="recaudo_esperado" step="0.01" min="0" value="${escapeHtml(info.recaudo_esperado || 0)}">
                                </div>
                                <div class="detalle-item" style="grid-column: span 2;">
                                    <div class="detalle-label">Instrucciones / Observaciones</div>
                                    <textarea class="form-control" name="instrucciones_entrega" rows="2">${escapeHtml(info.instrucciones_entrega || '')}</textarea>
                                </div>
                            </div>
                        </div>

                        ${info.infoEntrega ? `
                        <div class="detalle-section" style="margin-top: 20px; background-color: #f8fff9; border: 1px solid #c3e6cb;">
                            <h3 style="color: #155724;">✅ Detalles de la Entrega</h3>
                            <div class="detalle-grid">
                                <div class="detalle-item">
                                    <div class="detalle-label">Recibido por</div>
                                    <input class="form-control" name="entrega_nombre_receptor" value="${escapeHtml(info.infoEntrega.nombreRecibe || '')}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Parentesco/Cargo</div>
                                    <input class="form-control" name="entrega_parentesco" value="${escapeHtml(info.infoEntrega.parentesco || '')}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Documento</div>
                                    <input class="form-control" name="entrega_documento" value="${escapeHtml(info.infoEntrega.documento || '')}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Fecha Entrega</div>
                                    <input class="form-control" type="datetime-local" name="entrega_fecha" value="${escapeHtml(toInputDateTime(info.infoEntrega.fecha || ''))}">
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Total recaudado</div>
                                    <input class="form-control" type="number" name="entrega_recaudo_real" step="0.01" min="0" value="${escapeHtml(info.infoEntrega.recaudo || 0)}">
                                </div>
                                <div class="detalle-item" style="grid-column: span 2;">
                                    <div class="detalle-label">Observaciones de Entrega</div>
                                    <textarea class="form-control" name="entrega_observaciones" rows="2">${escapeHtml(info.infoEntrega.observaciones || '')}</textarea>
                                </div>
                            </div>
                        </div>
                        ` : ''}

                        ${info.infoCancelacion ? `
                        <div class="detalle-section" style="margin-top: 20px; background-color: #fff5f5; border: 1px solid #f5c2c7;">
                            <h3 style="color: #b02a37;">❌ Detalles de Cancelación</h3>
                            <div class="detalle-grid">
                                <div class="detalle-item">
                                    <div class="detalle-label">Motivo</div>
                                    <textarea class="form-control" name="cancelacion_motivo" rows="2">${escapeHtml(info.infoCancelacion.motivo || '')}</textarea>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Fecha</div>
                                    <div class="detalle-value">${escapeHtml(info.infoCancelacion.fecha || 'Sin información.')}</div>
                                </div>
                                <div class="detalle-item">
                                    <div class="detalle-label">Mensajero</div>
                                    <div class="detalle-value">${escapeHtml(info.infoCancelacion.mensajero || 'Sin información.')}</div>
                                </div>
                            </div>
                        </div>
                        ` : ''}

                        ${renderNovedadesSection('⏳ Historial de Aplazamientos', aplazados)}
                        ${renderNovedadesSection('🛑 Historial de Cancelaciones', cancelaciones)}

                        <div class="detalle-section" style="margin-top: 20px;">
                            <h3>🖼️ Evidencias e Imágenes</h3>
                            <div class="evidencia-grid">
                                ${evidenciaItems.map(renderEvidenciaCard).join('')}
                                ${extraItems.map(renderEvidenciaCard).join('')}
                                ${evidenciaItems.length === 0 && extraItems.length === 0 ? '<p class="text-muted">No hay imágenes registradas.</p>' : ''}
                            </div>
                            <div class="evidencia-upload">
                                <select class="form-control" id="tipoImagenNueva">
                                    <option value="general">General</option>
                                    <option value="entrega">Entrega</option>
                                    <option value="cancelacion">Cancelación</option>
                                    <option value="recoleccion">Recolección</option>
                                </select>
                                <input class="form-control" type="file" id="imagenesNueva" multiple accept="image/*">
                                <button type="button" class="btn btn-primary" id="btnSubirImagenes">Subir imágenes</button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">Guardar cambios</button>
                        </div>
                    </form>

                    <div class="detalle-section" style="margin-top: 20px;">
                        <h3>🕒 Historial de Movimientos</h3>
                        <div class="timeline-container" style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                `;

                if (historial.length === 0) {
                    html += '<p style="text-align:center; color: #999;">No hay historial registrado para este paquete.</p>';
                } else {
                    historial.forEach(item => {
                        html += `
                            <div class="timeline-item" style="border-left: 2px solid #ddd; padding-left: 15px; margin-bottom: 15px; position: relative;">
                                <div style="position: absolute; left: -21px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #667eea; border: 2px solid white;"></div>
                                <div class="text-muted small">${item.fecha}</div>
                                <strong>${item.estado.toUpperCase()}</strong>
                                <p class="mb-0" style="font-size: 0.9em;">${item.descripcion || ''}</p>
                                <small class="text-muted">Usuario: ${item.usuario || 'Sistema'}</small>
                            </div>
                        `;
                    });
                }
                html += '</div></div>';
                container.innerHTML = html;

                const form = document.getElementById('formEditarDetalles');
                if (form) {
                    if (options.modoCierre) {
                        const estadoSelect = form.querySelector('select[name="estado"]');
                        const fechaEntregaInput = form.querySelector('input[name="entrega_fecha"]');
                        const receptorInput = form.querySelector('input[name="entrega_nombre_receptor"]');

                        if (estadoSelect) {
                            estadoSelect.value = 'entregado';
                        }

                        if (fechaEntregaInput && !fechaEntregaInput.value) {
                            const now = new Date();
                            const tzOffset = now.getTimezoneOffset() * 60000;
                            fechaEntregaInput.value = new Date(now.getTime() - tzOffset).toISOString().slice(0, 16);
                        }

                        form.querySelector('.detalle-section[style*="#f8fff9"]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        receptorInput?.focus();
                    }

                    form.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const formData = new FormData(form);
                        const payload = {
                            paquete_id: id,
                            numero_guia: formData.get('numero_guia') || '',
                            estado: formData.get('estado') || '',
                            fecha_creacion: toDbDateTime(formData.get('fecha_creacion') || ''),
                            remitente_nombre: formData.get('remitente_nombre') || '',
                            destinatario_nombre: formData.get('destinatario_nombre') || '',
                            destinatario_telefono: formData.get('destinatario_telefono') || '',
                            direccion_destino: formData.get('direccion_destino') || '',
                            tipo_servicio: formData.get('tipo_servicio') || '',
                            descripcion_contenido: formData.get('descripcion_contenido') || '',
                            costo_envio: parseFloat(formData.get('costo_envio') || '0'),
                            recaudo_esperado: parseFloat(formData.get('recaudo_esperado') || '0'),
                            instrucciones_entrega: formData.get('instrucciones_entrega') || '',
                            mensajero_id: formData.get('mensajero_id') || '',
                            mensajero_recoleccion_id: formData.get('mensajero_recoleccion_id') || ''
                        };

                        if (formData.get('entrega_nombre_receptor') !== null) {
                            payload.entrega = {
                                nombre_receptor: formData.get('entrega_nombre_receptor') || '',
                                parentesco_cargo: formData.get('entrega_parentesco') || '',
                                documento_receptor: formData.get('entrega_documento') || '',
                                fecha_entrega: toDbDateTime(formData.get('entrega_fecha') || ''),
                                observaciones: formData.get('entrega_observaciones') || '',
                                recaudo_real: parseFloat(formData.get('entrega_recaudo_real') || '0')
                            };
                        }

                        if (formData.get('cancelacion_motivo') !== null) {
                            payload.cancelacion = {
                                descripcion: formData.get('cancelacion_motivo') || ''
                            };
                        }

                        try {
                            const resp = await fetch('../../controller/paquetesAdminController.php?action=actualizar', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(payload)
                            });
                            const result = await resp.json();
                            if (result.success) {
                                alert('Cambios guardados correctamente');
                                verDetalle(id);
                                if (typeof window.listarPaquetes === 'function') window.listarPaquetes();
                            } else {
                                alert('Error al guardar: ' + (result.error || 'Desconocido'));
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Error de conexión al guardar cambios');
                        }
                    });
                }

                const btnSubir = document.getElementById('btnSubirImagenes');
                if (btnSubir) {
                    btnSubir.addEventListener('click', async () => {
                        const tipo = document.getElementById('tipoImagenNueva').value;
                        const inputFiles = document.getElementById('imagenesNueva');
                        if (!inputFiles || !inputFiles.files || inputFiles.files.length === 0) {
                            if (inputFiles) inputFiles.click();
                            alert('Selecciona una o más imágenes');
                            return;
                        }
                        const fd = new FormData();
                        fd.append('paquete_id', id);
                        fd.append('tipo', tipo);
                        Array.from(inputFiles.files).forEach(file => fd.append('imagenes[]', file));
                        try {
                            const resp = await fetch('../../controller/paquetesAdminController.php?action=imagen_subir', {
                                method: 'POST',
                                body: fd
                            });
                            const result = await resp.json();
                            if (result.success) {
                                alert('Imágenes subidas');
                                verDetalle(id);
                            } else {
                                alert('Error al subir: ' + (result.error || 'Desconocido'));
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Error al subir imágenes');
                        }
                    });
                }

                container.querySelectorAll('[data-action="eliminar-imagen"]').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('¿Eliminar esta imagen?')) return;
                        const imageId = btn.getAttribute('data-image-id');
                        const target = btn.getAttribute('data-target');
                        const payload = { paquete_id: id };
                        if (imageId) payload.image_id = imageId;
                        if (target) payload.target = target;
                        try {
                            const resp = await fetch('../../controller/paquetesAdminController.php?action=imagen_eliminar', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(payload)
                            });
                            const result = await resp.json();
                            if (result.success) {
                                verDetalle(id);
                            } else {
                                alert('Error al eliminar: ' + (result.error || 'Desconocido'));
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Error de conexión al eliminar');
                        }
                    });
                });

                container.querySelectorAll('.input-reemplazar').forEach(input => {
                    input.addEventListener('change', async () => {
                        if (!input.files || input.files.length === 0) return;
                        const target = input.getAttribute('data-target');
                        const fd = new FormData();
                        fd.append('paquete_id', id);
                        fd.append('target', target);
                        fd.append('imagen', input.files[0]);
                        try {
                            const resp = await fetch('../../controller/paquetesAdminController.php?action=imagen_reemplazar', {
                                method: 'POST',
                                body: fd
                            });
                            const result = await resp.json();
                            if (result.success) {
                                verDetalle(id);
                            } else {
                                alert('Error al reemplazar: ' + (result.error || 'Desconocido'));
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Error al reemplazar imagen');
                        }
                    });
                });
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<p class="text-danger">Error al cargar datos.</p>';
            });
    }
}

window.cerrarPaqueteAdmin = function(id) {
    verDetalle(id, { modoCierre: true });
};

// Abrir modal de Guía (Rótulo) desde Admin
function cargarRotuloAdmin(id) {
    const btn = event?.currentTarget;
    const originalContent = btn ? btn.innerHTML : null;
    if (btn) {
        btn.innerHTML = '⌛';
        btn.disabled = true;
    }

    fetch(`../../controller/paquetesAdminController.php?action=detalle&id=${id}`)
        .then(res => res.json())
        .then(response => {
            if (btn) {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }

            const info = response.info;
            if (!info) {
                alert('No se pudieron cargar los datos de la guía.');
                return;
            }

            const datos = {
                guia: info.numero_guia,
                remitente_nombre: info.remitente || 'EcoBikeMess',
                tienda_nombre: info.tienda_nombre || info.remitente || 'Tienda',
                destinatario_nombre: info.destinatario_nombre,
                destinatario_direccion: info.direccion_destino,
                destinatario_telefono: info.destinatario_telefono || '',
                destinatario_observaciones: info.instrucciones_entrega || 'Sin observaciones',
                contenido: info.descripcion_contenido || '',
                cambios: info.recoger_cambios ? 'Sí' : 'No',
                costo_envio: info.costo_envio,
                recaudo: info.recaudo_esperado || 0
            };

            if (typeof window.verRotulo === 'function') window.verRotulo(datos);
        })
        .catch(err => {
            console.error(err);
            if (btn) {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
            alert('Error de conexión al cargar la guía.');
        });
}

function abrirModalAsignar(id, guia) {
    const modal = document.getElementById('modalAsignar');
    const inputId = document.getElementById('asignarGuia'); // Usamos este input oculto o visible para guardar el ID
    
    if (modal) {
        // Guardamos el ID del paquete en el formulario (puedes usar un data-attribute o un input hidden)
        // Si tu HTML tiene un input para mostrar la guía, úsalo, si no, crea un hidden dinámicamente
        if (!document.getElementById('hiddenPaqueteId')) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.id = 'hiddenPaqueteId';
            hidden.name = 'paquete_id';
            document.getElementById('formAsignarMensajero').appendChild(hidden);
        }
        document.getElementById('hiddenPaqueteId').value = id;
        
        if (inputId) inputId.value = guia; // Mostrar número de guía
        
        // Resetear búsqueda y selección en el modal
        document.getElementById('buscarMensajeroInput').value = '';
        document.getElementById('asignarMensajero').value = ''; // Limpiar ID seleccionado
        
        if (todosLosMensajeros.length === 0) {
            cargarFiltros(); // Intentar cargar de nuevo si la lista está vacía
        } else {
            renderizarListaMensajeros(todosLosMensajeros); // Mostrar todos de nuevo
        }
        
        document.querySelectorAll('.mensajero-item').forEach(el => el.classList.remove('selected'));
        
        modal.style.display = 'flex';
    }
}

function asignarMensajeroAction() {
    const paqueteId = document.getElementById('hiddenPaqueteId').value;
    const mensajeroId = document.getElementById('asignarMensajero').value;

    if (!mensajeroId) {
        alert('Por favor seleccione un mensajero');
        return;
    }

    const formData = new FormData();
    formData.append('paquete_id', paqueteId);
    formData.append('mensajero_id', mensajeroId);

    fetch('../../controller/paquetesAdminController.php?action=asignar', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Mensajero asignado correctamente');
            document.getElementById('modalAsignar').style.display = 'none';
            document.querySelector('.btn-close').click(); // Truco para recargar o llamar a listarPaquetes()
            location.reload(); // Recargar para ver cambios
        } else {
            alert('Error al asignar: ' + (data.error || 'Desconocido'));
        }
    })
    .catch(err => console.error(err));
}

// Función global para seleccionar un mensajero de la lista
window.seleccionarMensajero = function(id, nombre) {
    // Actualizar input oculto
    document.getElementById('asignarMensajero').value = id;
    
    // Actualizar input visual de búsqueda con el nombre seleccionado
    document.getElementById('buscarMensajeroInput').value = nombre;
    
    // Resaltar visualmente
    document.querySelectorAll('.mensajero-item').forEach(el => el.classList.remove('selected'));
    const item = document.querySelector(`.mensajero-item[data-id="${id}"]`);
    if (item) item.classList.add('selected');
};

// Utilidad para cerrar modales
function setupModalClosers() {
    document.querySelectorAll('.btn-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
    
    // Cerrar al hacer clic fuera del modal
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
}

function closeModal(name) {
    const modal = document.getElementById(`modal${name.charAt(0).toUpperCase() + name.slice(1)}`);
    if (modal) modal.style.display = 'none';
}
