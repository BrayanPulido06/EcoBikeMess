// Variable global para almacenar todos los mensajeros
let todosLosMensajeros = [];
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
    
    // Referencias a los filtros (Asegúrate de que los IDs en tu HTML coincidan)
    const inputs = {
        search: document.getElementById('searchInput'),        // Input tipo text (Corregido ID)
        fechaDesde: document.getElementById('filtroFechaDesde'), // Input date
        fechaHasta: document.getElementById('filtroFechaHasta'), // Input date
        cliente: document.getElementById('filtroCliente'),     // Select
        estado: document.getElementById('filtroEstado'),       // Select
        mensajero: document.getElementById('filtroMensajero')  // Select
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

    // Botón Limpiar Filtros
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            Object.values(inputs).forEach(input => {
                if (input) input.value = '';
            });
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
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }
        document.addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains('paquete-checkbox')) {
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
            tableBody.innerHTML = '<tr><td colspan="13" style="text-align:center;">Cargando datos...</td></tr>';
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
                    if (tableBody) tableBody.innerHTML = `<tr><td colspan="13" class="text-danger text-center">Error: ${response.error}</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                if (tableBody) tableBody.innerHTML = `<tr><td colspan="13" class="text-danger text-center">Error de conexión al cargar datos.</td></tr>`;
            });
    }
    window.listarPaquetes = listarPaquetes;

    // 3. Generar el HTML de las filas
    function renderizarTabla(data) {
        if (!tableBody) return;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="13" style="text-align:center;">No se encontraron paquetes con estos filtros.</td></tr>';
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
            const valorEnvioFormateado = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(p.costo_envio || 0);

            const mensajeroEntrega = p.mensajero_entrega || '<span class="text-muted font-italic">Sin asignar</span>';
            const mensajeroRecoleccion = p.mensajero_recoleccion || '<span class="text-muted font-italic">Sin asignar</span>';
            const envioAgregado = String(p.envio_destinatario || '').toLowerCase() === 'si'
                ? `<span class="badge badge-success">Sí</span> ${valorEnvioFormateado}`
                : `<span class="badge badge-secondary">No</span> ${valorEnvioFormateado}`;

            html += `
                <tr>
                    <td><input type="checkbox" class="paquete-checkbox" value="${p.id}"></td>
                    <td>${p.guia}</td>
                    <td>${p.fechaIngreso}</td>
                    <td>${p.remitente || '<span class="text-muted">N/A</span>'}</td>
                    <td>${p.destinatario}</td>
                    <td>${p.direccion}</td>
                    <td>${mensajeroRecoleccion}</td>
                    <td><span class="badge badge-${badgeRecoleccion}">${estadoRecLabel}</span></td>
                    <td>${mensajeroEntrega}</td>
                    <td><span class="badge badge-${badgeClass}">${p.estado.toUpperCase().replace('_', ' ')}</span></td>
                    <td>${recaudoFormateado}</td>
                    <td>${envioAgregado}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-warning" onclick="cargarRotuloAdmin(${p.id})" title="Guía">🏷️ Guía</button>
                            <button class="btn btn-sm btn-info" onclick="verDetalle(${p.id})" title="Ver Detalle">👁️</button>
                            ${p.estado !== 'entregado' && p.estado !== 'cancelado' ? `<button class="btn btn-sm btn-warning" onclick="abrirModalAsignar(${p.id}, '${p.guia}')" title="Asignar/Reasignar">🚴 Asignar</button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;
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
            "Destinatario": p.destinatario,
            "Dirección": p.direccion,
            "Mensajero Recoge": p.mensajero_recoleccion || 'Sin asignar',
            "Estado Rec.": p.estado_recoleccion || 'pendiente',
            "Mensajero Entrega": p.mensajero_entrega || 'Sin asignar',
            "Estado Entrega": p.estado,
            "Recaudo": p.recaudo_esperado || 0,
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
                    tienda_nombre: info.remitente || 'Tienda',
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
            // Llenar select de Clientes (si existe en el DOM)
            const selectCliente = document.getElementById('filtroCliente');
            if (selectCliente && data.clientes) {
                let html = '<option value="">Todos los clientes</option>';
                data.clientes.forEach(c => {
                    html += `<option value="${c.id}">${c.nombre}</option>`;
                });
                selectCliente.innerHTML = html;
            }

            // Llenar select de Mensajeros
            if (data.mensajeros) {
                todosLosMensajeros = data.mensajeros; // Guardar para uso en el modal
                
                let html = '<option value="">Todos los mensajeros</option>';
                
                data.mensajeros.forEach(m => {
                    const texto = `${m.nombre} (${m.estado})`;
                    html += `<option value="${m.id}">${texto}</option>`;
                });
                
                const selectMensajero = document.getElementById('filtroMensajero');
                if (selectMensajero) selectMensajero.innerHTML = html;
                
                // Renderizar lista inicial en el modal si está abierto
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

function verDetalle(id) {
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
                        target: 'entrega_principal'
                    });
                }
                if (info.infoEntrega && info.infoEntrega.fotoAdicional) {
                    evidenciaItems.push({
                        tipo: 'entrega',
                        label: 'Entrega adicional',
                        ruta: info.infoEntrega.fotoAdicional,
                        target: 'entrega_adicional'
                    });
                }
                if (info.infoCancelacion && info.infoCancelacion.foto) {
                    evidenciaItems.push({
                        tipo: 'cancelacion',
                        label: 'Cancelación',
                        ruta: info.infoCancelacion.foto,
                        target: 'cancelacion'
                    });
                }

                const extraItems = imagenes.map(img => ({
                    tipo: img.tipo || 'general',
                    label: `Imagen ${img.tipo || 'general'}`,
                    ruta: img.ruta_archivo,
                    imageId: img.id
                }));

                const renderEvidenciaCard = (item) => {
                    const ruta = item.ruta || item.ruta_archivo;
                    if (!ruta) return '';
                    const deleteAttrs = item.imageId ? `data-action="eliminar-imagen" data-image-id="${item.imageId}"` : `data-action="eliminar-imagen" data-target="${item.target}"`;
                    const replaceInput = item.target ? `
                        <label class="btn btn-sm btn-secondary">
                            Reemplazar
                            <input type="file" class="input-reemplazar" data-target="${item.target}" data-paquete-id="${info.paquete_id}" accept="image/*" hidden>
                        </label>
                    ` : '';
                    return `
                        <div class="evidencia-card">
                            <a href="../../${ruta}" target="_blank" rel="noopener noreferrer">
                                <img src="../../${ruta}" alt="${escapeHtml(item.label)}">
                            </a>
                            <div class="evidencia-meta">
                                <span>${escapeHtml(item.label)}</span>
                                <span class="badge badge-secondary">${escapeHtml(item.tipo)}</span>
                            </div>
                            <div class="evidencia-actions">
                                ${replaceInput}
                                <button class="btn btn-sm btn-danger" ${deleteAttrs}>Eliminar</button>
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
                                recaudo_real: 0
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
                tienda_nombre: info.remitente || 'Tienda',
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
