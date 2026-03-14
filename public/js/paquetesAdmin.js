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

            // Definir color del badge para estado de recolección
            let badgeRecoleccion = 'secondary';
            const estadoRec = p.estado_recoleccion || 'pendiente';
            switch(estadoRec) {
                case 'completada': badgeRecoleccion = 'success'; break;
                case 'cancelada': badgeRecoleccion = 'danger'; break;
                case 'en_curso': badgeRecoleccion = 'primary'; break;
                case 'asignada': badgeRecoleccion = 'info'; break;
            }

            // Formatear valor a moneda
            const valorFormateado = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(p.valor);
            const recaudoFormateado = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(p.recaudo || 0);

            const mensajeroEntrega = p.mensajero || '<span class="text-muted font-italic">Sin asignar</span>';
            const mensajeroRecoleccion = p.mensajero_recoleccion || '<span class="text-muted font-italic">Sin asignar</span>';

            html += `
                <tr>
                    <td><input type="checkbox" class="paquete-checkbox" value="${p.id}"></td>
                    <td>${p.guia}</td>
                    <td>${p.fechaIngreso}</td>
                    <td>${p.remitente || '<span class="text-muted">N/A</span>'}</td>
                    <td>${p.destinatario}</td>
                    <td>${p.direccion}</td>
                    <td>${mensajeroRecoleccion}</td>
                    <td><span class="badge badge-${badgeRecoleccion}">${estadoRec.toUpperCase().replace('_', ' ')}</span></td>
                    <td>${mensajeroEntrega}</td>
                    <td><span class="badge badge-${badgeClass}">${p.estado.toUpperCase().replace('_', ' ')}</span></td>
                    <td>${valorFormateado}</td>
                    <td>${recaudoFormateado}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="verDetalle(${p.id})" title="Ver Detalle">👁️</button>
                        ${p.estado !== 'entregado' && p.estado !== 'cancelado' ? `<button class="btn btn-sm btn-warning" onclick="abrirModalAsignar(${p.id}, '${p.guia}')" title="Asignar/Reasignar">🚴</button>` : ''}
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
            "Mensajero Entrega": p.mensajero || 'Sin asignar',
            "Estado Paq.": p.estado,
            "Valor": p.valor,
            "Recaudo": p.recaudo
        }));

        const ws = XLSX.utils.json_to_sheet(exportData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Paquetes");
        XLSX.writeFile(wb, `Paquetes_EcoBikeMess_${new Date().toISOString().slice(0,10)}.xlsx`);
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

                if (!info) {
                    const msg = data.error ? `Error: ${data.error}` : 'No se encontró información del paquete.';
                    container.innerHTML = `<p class="text-danger text-center">${msg}</p>`;
                    return;
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

                let html = `
                    <div class="detalle-section">
                        <h3>📦 Información del Paquete</h3>
                        <div class="detalle-grid">
                            <div class="detalle-item">
                                <div class="detalle-label">Número de Guía</div>
                                <div class="detalle-value" style="font-size: 1.2em; color: #667eea;">${info.numero_guia}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Estado Actual</div>
                                <div class="detalle-value"><span class="badge badge-${badgeClass}">${info.estado.toUpperCase()}</span></div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Fecha de Ingreso</div>
                                <div class="detalle-value">${info.fecha_creacion}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Remitente</div>
                                <div class="detalle-value">${info.remitente || 'N/A'}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Destinatario</div>
                                <div class="detalle-value">${info.destinatario_nombre}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Teléfono Destinatario</div>
                                <div class="detalle-value">${info.destinatario_telefono}</div>
                            </div>
                            <div class="detalle-item" style="grid-column: span 2;">
                                <div class="detalle-label">Dirección de Entrega</div>
                                <div class="detalle-value">${info.direccion_destino}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Mensajero Recolección</div>
                                <div class="detalle-value">${info.mensajero_recoleccion || '<span class="text-muted">Sin asignar</span>'}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Mensajero Entrega</div>
                                <div class="detalle-value">${info.mensajero || '<span class="text-muted">Sin asignar</span>'}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Tipo de Paquete</div>
                                <div class="detalle-value">${info.tipo_paquete}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Contenido</div>
                                <div class="detalle-value">${info.descripcion_contenido || '-'}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Costo Envío</div>
                                <div class="detalle-value">${currency.format(info.costo_envio)}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Valor a Recaudar</div>
                                <div class="detalle-value" style="color: ${info.recaudo_esperado > 0 ? '#dc3545' : '#333'}">
                                    ${currency.format(info.recaudo_esperado || 0)}
                                </div>
                            </div>
                            <div class="detalle-item" style="grid-column: span 2;">
                                <div class="detalle-label">Instrucciones / Observaciones</div>
                                <div class="detalle-value">${info.instrucciones_entrega || 'Ninguna'}</div>
                            </div>
                        </div>
                    </div>

                    ${info.estado === 'entregado' && info.infoEntrega ? `
                    <div class="detalle-section" style="margin-top: 20px; background-color: #f8fff9; border: 1px solid #c3e6cb;">
                        <h3 style="color: #155724;">✅ Detalles de la Entrega</h3>
                        <div class="detalle-grid">
                            <div class="detalle-item">
                                <div class="detalle-label">Recibido por</div>
                                <div class="detalle-value"><strong>${info.infoEntrega.nombreRecibe || 'N/A'}</strong></div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Parentesco/Cargo</div>
                                <div class="detalle-value">${info.infoEntrega.parentesco || 'N/A'}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Documento</div>
                                <div class="detalle-value">${info.infoEntrega.documento || 'N/A'}</div>
                            </div>
                            <div class="detalle-item">
                                <div class="detalle-label">Fecha Entrega</div>
                                <div class="detalle-value">${info.infoEntrega.fecha || 'N/A'}</div>
                            </div>
                            <div class="detalle-item" style="grid-column: span 2;">
                                <div class="detalle-label">Observaciones de Entrega</div>
                                <div class="detalle-value">${info.infoEntrega.observaciones || 'Sin observaciones'}</div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <h4 style="font-size: 0.9em; text-transform: uppercase; color: #666; margin-bottom: 10px;">Evidencia Fotográfica</h4>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                ${info.infoEntrega.fotoPrincipal ? `
                                    <a href="../../${info.infoEntrega.fotoPrincipal}" target="_blank" rel="noopener noreferrer" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                                        <img src="../../${info.infoEntrega.fotoPrincipal}" alt="Evidencia Principal" style="width: 100%; height: 100%; object-fit: cover;">
                                    </a>
                                ` : '<span class="text-muted">Sin foto principal</span>'}
                                ${info.infoEntrega.fotoAdicional ? `
                                    <a href="../../${info.infoEntrega.fotoAdicional}" target="_blank" rel="noopener noreferrer" style="display: block; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                                        <img src="../../${info.infoEntrega.fotoAdicional}" alt="Evidencia Adicional" style="width: 100%; height: 100%; object-fit: cover;">
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    ` : ''}

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
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<p class="text-danger">Error al cargar datos.</p>';
            });
    }
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
