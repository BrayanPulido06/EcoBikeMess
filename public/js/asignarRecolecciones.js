// public/js/asignarRecolecciones.js
let recolecciones = [];
let todosLosMensajeros = []; // Almacenar mensajeros globalmente

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadInitialData();
    cargarMensajerosEnModal();
});

// Configurar event listeners
function setupEventListeners() {
    // Filtros
    document.getElementById('busqueda').addEventListener('input', applyFilters);
    
    // Modal Asignación Rápida
    const btnCerrarAsignar = document.getElementById('btnCerrarAsignar');
    if (btnCerrarAsignar) {
        btnCerrarAsignar.addEventListener('click', function() {
            document.getElementById('modalAsignarRapido').style.display = 'none';
        });
    }

    // Formulario de Asignación
    const formAsignar = document.getElementById('formAsignarRapido');
    if (formAsignar) {
        formAsignar.addEventListener('submit', handleAsignarSubmit);
    }

    // Filtro de mensajeros en el modal
    const inputBuscarMensajero = document.getElementById('buscarMensajeroInput');
    if (inputBuscarMensajero) {
        inputBuscarMensajero.addEventListener('input', filtrarListaMensajeros);
    }

    // Botón Reportes (Opcional)
    const btnReportes = document.getElementById('btnReportes');
    if (btnReportes) {
        btnReportes.addEventListener('click', () => alert('Funcionalidad de reportes en desarrollo'));
    }
}

// Cargar datos reales desde el servidor
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

// Cargar lista de mensajeros para el select del modal
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

// Renderizar tabla de recolecciones
function renderRecolecciones() {
    const tbody = document.getElementById('tablaRecoleccionesBody');
    const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);
    
    if (!recolecciones || recolecciones.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No hay recolecciones pendientes.</td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = recolecciones.map(rec => `
        <tr>
            <td>${rec.direccion_origen}</td>
            <td>${rec.cliente_nombre}</td>
            <td>${rec.mensajero_nombre}</td>
            <td>
                <span class="badge estado-${rec.estado}">
                    ${(rec.estado === 'entregado' || rec.estado === 'completada') ? 'Finalizada' : capitalize(rec.estado.replace('_', ' '))}
                </span>
            </td>
            <td>
                <span class="badge badge-info" style="font-size: 1em; background-color: #17a2b8;">
                    ${rec.cantidad} Paquetes
                </span>
            </td>
            <td><small>${rec.guias ? rec.guias.substring(0, 50) + (rec.guias.length > 50 ? '...' : '') : ''}</small></td>
            <td>${new Date(rec.fecha_creacion).toLocaleString()}</td>
            <td>
                <div class="actions">
                    <button class="btn btn-sm btn-info" title="Ver Paquetes" onclick="verDetallesPaquetes('${rec.ids}')">👁️</button>
                    ${!['entregado', 'completada', 'cancelado'].includes(rec.estado) ? `
                        ${rec.estado === 'pendiente' ? 
                            `<button class="btn btn-sm btn-warning" title="Asignar Recolección" onclick="asignarRecoleccion('${rec.ids}', '${rec.direccion_origen.replace(/'/g, "\\'")}', '${rec.cliente_nombre.replace(/'/g, "\\'")}')">🚴</button>` : 
                            `<button class="btn btn-sm btn-secondary" title="Reasignar" onclick="asignarRecoleccion('${rec.ids}', '${rec.direccion_origen.replace(/'/g, "\\'")}', '${rec.cliente_nombre.replace(/'/g, "\\'")}')">🔄</button>`
                        }
                        <button class="btn btn-sm btn-danger" title="Cancelar" onclick="cancelarRecoleccion('${rec.ids}')">🗑️</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// Actualizar estadísticas
function updateStats(stats) {
    if (stats) {
        document.getElementById('totalRecolecciones').textContent = stats.total || 0;
        document.getElementById('pendientes').textContent = stats.pendientes || 0;
        document.getElementById('completadas').textContent = stats.completadas || 0;
    }
}

// Aplicar filtros
function applyFilters() {
    const busqueda = document.getElementById('busqueda').value.toLowerCase();
    
    const filtered = recolecciones.filter(rec => {
        return !busqueda || 
            rec.cliente_nombre.toLowerCase().includes(busqueda) ||
            rec.direccion_origen.toLowerCase().includes(busqueda);
    });
    
    // Renderizar filtrados
    const temp = recolecciones;
    const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);
    
    // Hack temporal para usar la misma función de renderizado
    const tbody = document.getElementById('tablaRecoleccionesBody');
    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px;">No se encontraron resultados.</td></tr>`;
    } else {
        // Renderizar manualmente los filtrados (con la lógica corregida)
        tbody.innerHTML = filtered.map(rec => `
            <tr>
                <td>${rec.direccion_origen}</td>
                <td>${rec.cliente_nombre}</td>
                <td>${rec.mensajero_nombre}</td>
                <td>
                    <span class="badge estado-${rec.estado}">
                        ${(rec.estado === 'entregado' || rec.estado === 'completada') ? 'Finalizada' : capitalize(rec.estado.replace('_', ' '))}
                    </span>
                </td>
                <td>
                    <span class="badge badge-info" style="font-size: 1em; background-color: #17a2b8;">
                        ${rec.cantidad} Paquetes
                    </span>
                </td>
                <td><small>${rec.guias ? rec.guias.substring(0, 50) + (rec.guias.length > 50 ? '...' : '') : ''}</small></td>
                <td>${new Date(rec.fecha_creacion).toLocaleString()}</td>
                <td>
                    <div class="actions">
                        <button class="btn btn-sm btn-info" title="Ver Paquetes" onclick="verDetallesPaquetes('${rec.ids}')">👁️</button>
                        ${!['entregado', 'completada', 'cancelado'].includes(rec.estado) ? `
                            ${rec.estado === 'pendiente' ? 
                                `<button class="btn btn-sm btn-warning" title="Asignar Recolección" onclick="asignarRecoleccion('${rec.ids}', '${rec.direccion_origen.replace(/'/g, "\\'")}', '${rec.cliente_nombre.replace(/'/g, "\\'")}')">🚴</button>` : 
                                `<button class="btn btn-sm btn-secondary" title="Reasignar" onclick="asignarRecoleccion('${rec.ids}', '${rec.direccion_origen.replace(/'/g, "\\'")}', '${rec.cliente_nombre.replace(/'/g, "\\'")}')">🔄</button>`
                            }
                            <button class="btn btn-sm btn-danger" title="Cancelar" onclick="cancelarRecoleccion('${rec.ids}')">🗑️</button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    }
}

window.verDetallesPaquetes = async function(ids) {
    const modal = document.getElementById('modalDetalles');
    const container = document.getElementById('detallesRecoleccionBody');
    
    if (modal && container) {
        modal.style.display = 'flex';
        container.innerHTML = '<p style="text-align:center; padding: 2rem;">Cargando detalles de la base de datos...</p>';

        try {
            // 1. Obtener detalles de los paquetes
            const responsePaquetes = await fetch(`../../controller/asignarRecoleccionesController.php?action=detalles&ids=${ids}`);
            const resultPaquetes = await responsePaquetes.json();

            let recoleccionData = null;
            // 2. Si hay paquetes, obtener detalles de la recolección asociada
            if (resultPaquetes.success && resultPaquetes.data.length > 0) {
                const primerPaqueteId = resultPaquetes.data[0].id;
                const responseRecoleccion = await fetch(`../../controller/asignarRecoleccionesController.php?action=detalles_recoleccion&paquete_id=${primerPaqueteId}`);
                const resultRecoleccion = await responseRecoleccion.json();
                if (resultRecoleccion.success) {
                    recoleccionData = resultRecoleccion.data;
                }
            }

            // 3. Renderizar el HTML del modal
            if (resultPaquetes.success && resultPaquetes.data.length > 0) {
                const primerPaquete = resultPaquetes.data[0];
                const clienteNombre = primerPaquete.nombre_emprendimiento || (primerPaquete.cli_nombres + ' ' + primerPaquete.cli_apellidos);
                
                // HTML para la información de la recolección
                let recoleccionInfoHtml = '<p>No se encontraron detalles de la recolección en la tabla `recolecciones`.</p>';
                if (recoleccionData) {
                    recoleccionInfoHtml = `
                        <div class="detalle-grid">
                            <div class="detalle-item"><div class="detalle-label">Orden N°</div><div class="detalle-value">${recoleccionData.numero_orden || 'N/A'}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Horario</div><div class="detalle-value">${recoleccionData.horario_preferido || 'N/A'}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Paquetes Recogidos</div><div class="detalle-value">${recoleccionData.cantidad_real || 'No registrado'}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Fecha Completada</div><div class="detalle-value">${recoleccionData.fecha_completada ? new Date(recoleccionData.fecha_completada).toLocaleString() : 'N/A'}</div></div>
                        </div>
                        <h4 style="margin-top:1rem; margin-bottom:0.5rem;">Observaciones del Mensajero</h4>
                        <p style="background:#f8f9fa; padding:10px; border-radius:5px; min-height: 40px;">${recoleccionData.observaciones_recoleccion || 'No hay observaciones.'}</p>
                    `;
                }

                // HTML para las fotos
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
                                const fullUrl = `../../${fotoUrl}`; // Asume que la ruta es desde la raíz del proyecto
                                fotosHtml += `<a href="${fullUrl}" target="_blank"><img src="${fullUrl}" alt="Foto de recolección" style="width:100px; height:100px; object-fit:cover; border-radius:5px;"></a>`;
                            }
                        });
                        fotosHtml += '</div>';
                    }
                }

                // HTML final
                let html = `
                    <div class="detalle-section">
                        <h3 style="margin-bottom: 15px;">ℹ️ Información General</h3>
                        <div class="detalle-grid">
                            <div class="detalle-item"><div class="detalle-label">Cliente</div><div class="detalle-value">${clienteNombre}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Dirección</div><div class="detalle-value">${primerPaquete.direccion_origen}</div></div>
                            <div class="detalle-item"><div class="detalle-label">Teléfono</div><div class="detalle-value">${primerPaquete.cli_telefono || 'N/A'}</div></div>
                        </div>
                    </div>
                    <div class="detalle-section"><h3 style="margin-top: 20px;">📝 Detalles de la Recolección</h3>${recoleccionInfoHtml}</div>
                    <div class="detalle-section"><h3 style="margin-top: 20px;">📸 Fotos de Evidencia</h3>${fotosHtml}</div>
                    <div class="detalle-section">
                        <h3 style="margin-top: 20px;">📦 Paquetes Incluidos (${resultPaquetes.data.length})</h3>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 5px;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
                                <thead style="background: #f8f9fa; position: sticky; top: 0;">
                                    <tr><th style="padding: 8px; text-align: left;">Guía</th><th style="padding: 8px; text-align: left;">Destinatario</th><th style="padding: 8px; text-align: left;">Descripción</th></tr>
                                </thead>
                                <tbody>
                                    ${resultPaquetes.data.map(p => `
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding: 8px;"><strong>${p.numero_guia}</strong></td>
                                            <td style="padding: 8px;">${p.destinatario_nombre}<br><small>${p.direccion_destino}</small></td>
                                            <td style="padding: 8px;">${p.descripcion_contenido || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-danger text-center">No se encontraron detalles de los paquetes.</p>';
            }
        } catch (error) {
            console.error(error);
            container.innerHTML = '<p class="text-danger text-center">Error al cargar detalles.</p>';
        }
    }
}


// Abrir modal de asignación y preparar lista
window.asignarRecoleccion = function(ids, direccion, cliente) {
    const modal = document.getElementById('modalAsignarRapido');
    if (modal) {
        // Poblar información de la recolección
        const infoContainer = document.getElementById('infoRecoleccionAsignar');
        if (infoContainer) {
            if (direccion && cliente) {
                infoContainer.innerHTML = `
                    <p style="margin:0; font-size: 0.9em; color: #6c757d;">Asignando recolección para:</p>
                    <p style="margin:2px 0 0; font-weight: 600;"><strong>Cliente:</strong> ${cliente}</p>
                    <p style="margin:2px 0 0; font-weight: 600;"><strong>Dirección:</strong> ${direccion}</p>`;
            } else {
                infoContainer.innerHTML = '<p>Información de recolección no disponible.</p>';
            }
        }

        // Resetear formulario
        document.getElementById('idsPaquetesHidden').value = ids;
        document.getElementById('mensajeroIdHidden').value = '';
        document.getElementById('buscarMensajeroInput').value = '';
        
        // Renderizar lista completa (o recargar si está vacía)
        if (todosLosMensajeros.length === 0) {
            cargarMensajerosEnModal().then(() => {
                renderizarListaMensajeros(todosLosMensajeros);
            });
        } else {
            renderizarListaMensajeros(todosLosMensajeros);
        }
        
        // Limpiar selección visual previa
        document.querySelectorAll('.mensajero-item').forEach(el => el.classList.remove('selected'));
        modal.style.display = 'flex';
    }
}

// Renderizar lista de mensajeros (estilo paquetesAdmin)
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
        const safeName = m.nombre.replace(/'/g, "\\'"); // Escapar comillas simples para el onclick
        html += `
            <div class="mensajero-item" onclick="seleccionarMensajero(${m.id}, '${safeName}')" data-id="${m.id}">
                <div style="font-weight:bold;">${m.nombre}</div>
                <div style="font-size:0.85em; color:#666;">
                    <span style="color:${estadoColor}">● ${m.estado}</span> | Tareas activas: ${tareas}
                </div>
            </div>
        `;
    });
    contenedor.innerHTML = html;
}

// Filtrar mensajeros al escribir
function filtrarListaMensajeros(e) {
    const texto = e.target.value.toLowerCase();
    const filtrados = todosLosMensajeros.filter(m => 
        m.nombre.toLowerCase().includes(texto)
    );
    renderizarListaMensajeros(filtrados);
}

// Seleccionar un mensajero de la lista
window.seleccionarMensajero = function(id, nombre) {
    document.getElementById('mensajeroIdHidden').value = id;
    document.getElementById('buscarMensajeroInput').value = nombre;
    
    document.querySelectorAll('.mensajero-item').forEach(el => el.classList.remove('selected'));
    const item = document.querySelector(`.mensajero-item[data-id="${id}"]`);
    if (item) {
        item.classList.add('selected');
    }
}

// Manejar envío de asignación
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
            loadInitialData(); // Recargar tabla
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ocurrió un error al procesar la solicitud');
    }
}

// Cancelar (Eliminar de la vista) recolección
window.cancelarRecoleccion = async function(ids) {
    if (!confirm('¿Estás seguro de eliminar esta recolección de la vista? Los paquetes pasarán a estado "cancelado" pero no se borrarán de la base de datos.')) {
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
            loadInitialData(); // Recargar tabla
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ocurrió un error al procesar la solicitud');
    }
}