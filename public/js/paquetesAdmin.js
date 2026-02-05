// Variables globales
let paquetes = [];
let paquetesFiltrados = [];
let currentPage = 1;
let pageSize = 25;
let sortColumn = 'fecha';
let sortDirection = 'desc';
let currentPaquete = null;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadInitialData();
});

// Inicializar aplicación
function initializeApp() {
    setDateFilters();
    startPolling();
}

// Configurar event listeners
function setupEventListeners() {
    // Búsqueda
    document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 300));
    
    // Filtros
    document.getElementById('filtroFechaDesde').addEventListener('change', applyFilters);
    document.getElementById('filtroFechaHasta').addEventListener('change', applyFilters);
    document.getElementById('filtroCliente').addEventListener('change', applyFilters);
    document.getElementById('filtroEstado').addEventListener('change', applyFilters);
    document.getElementById('filtroZona').addEventListener('change', applyFilters);
    document.getElementById('filtroMensajero').addEventListener('change', applyFilters);
    document.getElementById('filtroTipo').addEventListener('change', applyFilters);
    document.getElementById('btnLimpiarFiltros').addEventListener('click', limpiarFiltros);
    
    // Paginación
    document.getElementById('pageSize').addEventListener('change', function() {
        pageSize = parseInt(this.value);
        currentPage = 1;
        renderTable();
    });
    
    // Exportar
    document.getElementById('btnExportarExcel').addEventListener('click', exportarExcel);
    document.getElementById('btnExportarPDF').addEventListener('click', exportarPDF);
    
    // Modales
    document.getElementById('btnCerrarDetalles').addEventListener('click', () => closeModal('modalDetalles'));
    document.getElementById('btnCerrarEditar').addEventListener('click', () => closeModal('modalEditar'));
    document.getElementById('btnCancelarEditar').addEventListener('click', () => closeModal('modalEditar'));
    document.getElementById('btnCerrarAsignar').addEventListener('click', () => closeModal('modalAsignar'));
    document.getElementById('btnCancelarAsignar').addEventListener('click', () => closeModal('modalAsignar'));
    
    // Formularios
    document.getElementById('formEditarPaquete').addEventListener('submit', handleEditarPaquete);
    document.getElementById('formAsignarMensajero').addEventListener('submit', handleAsignarMensajero);
    
    // Selector de mensajero
    document.getElementById('asignarMensajero').addEventListener('change', mostrarInfoMensajero);
    
    // Ordenamiento
    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.dataset.column;
            if (sortColumn === column) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = column;
                sortDirection = 'asc';
            }
            updateSortIndicators();
            sortData();
            renderTable();
        });
    });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Establecer filtros de fecha
function setDateFilters() {
    const hoy = new Date();
    const hace30Dias = new Date();
    hace30Dias.setDate(hace30Dias.getDate() - 30);
    
    document.getElementById('filtroFechaDesde').value = hace30Dias.toISOString().split('T')[0];
    document.getElementById('filtroFechaHasta').value = hoy.toISOString().split('T')[0];
}

// Cargar datos iniciales
async function loadInitialData(isBackground = false) {
    try {
        if (!isBackground) showLoading(true);
        
        // 1. Cargar filtros (Clientes y Mensajeros)
        const responseFilters = await fetch('../../controller/paquetesAdminController.php?action=get_filters');
        const filtersData = await responseFilters.json();

        if (filtersData.success) {
            loadClientes(filtersData.clientes);
            loadMensajeros(filtersData.mensajeros);
        }

        // 2. Cargar Paquetes (usando los filtros actuales por defecto)
        applyFilters();
        
        // Nota: applyFilters ahora se encarga de llamar a fetchPaquetes y renderizar.
        // Ya no generamos datos mock.
        
        // paquetes = generateMockPaquetes(); // ELIMINADO
        // const clientes = generateMockClientes(); // ELIMINADO
        // const mensajeros = generateMockMensajeros(); // ELIMINADO
        
        // updateStats(); // Se llama dentro de fetchPaquetes
        
        if (!isBackground) showLoading(false);
        
    } catch (error) {
        console.error('Error al cargar datos:', error);
        if (!isBackground) {
            showNotification('Error al cargar los datos', 'error');
            showLoading(false);
        }
    }
}

// Iniciar actualización automática
function startPolling() {
    setInterval(() => {
        // Solo actualizar si no hay modales abiertos para no interrumpir la edición
        if (!document.querySelector('.modal.active')) {
            loadInitialData(true);
        }
    }, 10000); // Actualizar cada 10 segundos
}

// Funciones de generación de datos Mock ELIMINADAS
// generateMockPaquetes, generateHistorial, generateMockClientes, generateMockMensajeros
// han sido reemplazadas por llamadas a la API.

// Cargar clientes en select
function loadClientes(clientes) {
    const select = document.getElementById('filtroCliente');
    select.innerHTML = '<option value="">Todos los clientes</option>';
    clientes.forEach(cliente => {
        const option = document.createElement('option');
        option.value = cliente.id; // Usar ID para filtrar
        option.textContent = cliente.nombre;
        select.appendChild(option);
    });
}

// Cargar mensajeros en select
function loadMensajeros(mensajeros) {
    const select1 = document.getElementById('filtroMensajero');
    const select2 = document.getElementById('asignarMensajero');
    
    select1.innerHTML = '<option value="">Todos los mensajeros</option>';
    select2.innerHTML = '<option value="">Seleccione un mensajero</option>';
    
    mensajeros.forEach(mensajero => {
        const option1 = document.createElement('option');
        option1.value = mensajero.id; // Usar ID para filtrar
        option1.textContent = mensajero.nombre;
        select1.appendChild(option1);
        
        const option2 = document.createElement('option');
        option2.value = mensajero.id;
        option2.textContent = `${mensajero.nombre} - ${mensajero.estado === 'activo' ? '🟢 Disponible' : '🔴 Ocupado'}`;
        option2.dataset.disponible = (mensajero.estado === 'activo');
        option2.dataset.tareas = mensajero.tareas_activas || 0;
        option2.dataset.zona = 'General'; // Ajustar si tienes zona en mensajero
        select2.appendChild(option2);
    });
}

// Aplicar filtros
function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const fechaDesde = document.getElementById('filtroFechaDesde').value;
    const fechaHasta = document.getElementById('filtroFechaHasta').value;
    const cliente = document.getElementById('filtroCliente').value;
    const estado = document.getElementById('filtroEstado').value;
    const zona = document.getElementById('filtroZona').value;
    const mensajero = document.getElementById('filtroMensajero').value;
    const tipo = document.getElementById('filtroTipo').value;
    
    // Construir URL con parámetros
    const params = new URLSearchParams({
        action: 'get_paquetes',
        search: searchTerm,
        fechaDesde: fechaDesde,
        fechaHasta: fechaHasta,
        cliente_id: cliente,
        estado: estado,
        zona: zona,
        mensajero_id: mensajero,
        tipo: tipo
    });

    fetch(`../../controller/paquetesAdminController.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                paquetes = data.data; // Actualizar global
                paquetesFiltrados = [...paquetes]; // Filtrado ya hecho en servidor
                
                sortData();
                currentPage = 1;
                renderTable();
                updateStats();
            }
        })
        .catch(error => console.error('Error fetching paquetes:', error));
}

// Limpiar filtros
function limpiarFiltros() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filtroCliente').value = '';
    document.getElementById('filtroEstado').value = '';
    document.getElementById('filtroZona').value = '';
    document.getElementById('filtroMensajero').value = '';
    document.getElementById('filtroTipo').value = '';
    setDateFilters();
    applyFilters();
}

// Ordenar datos
function sortData() {
    paquetesFiltrados.sort((a, b) => {
        let aVal, bVal;
        
        switch(sortColumn) {
            case 'guia':
                aVal = a.guia;
                bVal = b.guia;
                break;
            case 'fecha':
                aVal = new Date(a.fechaIngreso);
                bVal = new Date(b.fechaIngreso);
                break;
            case 'estado':
                aVal = a.estado;
                bVal = b.estado;
                break;
            case 'valor':
                aVal = a.valor;
                bVal = b.valor;
                break;
            default:
                return 0;
        }
        
        if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
        return 0;
    });
}

// Actualizar indicadores de ordenamiento
function updateSortIndicators() {
    document.querySelectorAll('th.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        if (th.dataset.column === sortColumn) {
            th.classList.add(`sorted-${sortDirection}`);
        }
    });
}

// Renderizar tabla
function renderTable() {
    const tbody = document.getElementById('tablaPaquetesBody');
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    const paginatedData = paquetesFiltrados.slice(start, end);
    
    if (paginatedData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px;">
                    <p style="color: #999; font-size: 1.1em;">No se encontraron paquetes</p>
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = paginatedData.map(paq => `
            <tr class="${paq.urgente ? 'urgente' : ''} ${paq.problema ? 'problema' : ''}">
                <td>
                    ${paq.urgente ? '<span class="urgente-indicator"></span>' : ''}
                    <strong>${paq.guia}</strong>
                </td>
                <td>${formatDateTime(paq.fechaIngreso)}</td>
                <td>${paq.remitente}</td>
                <td>
                    ${paq.destinatario}<br>
                    <small style="color: #666;">${paq.telefonoDestinatario}</small>
                </td>
                <td>${paq.direccion}<br><small style="color: #666;">Zona: ${paq.zona}</small></td>
                <td><span class="status-badge status-${paq.estado}">${formatEstado(paq.estado)}</span></td>
                <td>${paq.mensajero || '-'}</td>
                <td>${formatCurrency(paq.valor)}</td>
                <td><span class="type-badge">${formatTipo(paq.tipo)}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-info" onclick="verDetalles(${paq.id})">👁️</button>
                        ${paq.estado === 'pendiente' ? `
                            <button class="btn btn-sm btn-warning" onclick="editarPaquete(${paq.id})">✏️</button>
                            <button class="btn btn-sm btn-success" onclick="asignarMensajero(${paq.id})">👤</button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    updatePaginationInfo(start, end);
    renderPaginationControls();
}

// Actualizar información de paginación
function updatePaginationInfo(start, end) {
    document.getElementById('showingFrom').textContent = paquetesFiltrados.length > 0 ? start + 1 : 0;
    document.getElementById('showingTo').textContent = Math.min(end, paquetesFiltrados.length);
    document.getElementById('totalResults').textContent = paquetesFiltrados.length;
}

// Renderizar controles de paginación
function renderPaginationControls() {
    const totalPages = Math.ceil(paquetesFiltrados.length / pageSize);
    const controls = document.getElementById('paginationControls');
    
    let html = `
        <button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
            ‹ Anterior
        </button>
    `;
    
    const maxButtons = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    
    if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">
                ${i}
            </button>
        `;
    }
    
    html += `
        <button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
            Siguiente ›
        </button>
    `;
    
    controls.innerHTML = html;
}

// Cambiar página
function changePage(page) {
    const totalPages = Math.ceil(paquetesFiltrados.length / pageSize);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        renderTable();
    }
}

// Actualizar estadísticas
function updateStats() {
    document.getElementById('totalPaquetes').textContent = paquetes.length;
    document.getElementById('pendientes').textContent = paquetes.filter(p => p.estado === 'pendiente').length;
    document.getElementById('enTransito').textContent = paquetes.filter(p => p.estado === 'en_transito').length;
    document.getElementById('entregados').textContent = paquetes.filter(p => p.estado === 'entregado').length;
    document.getElementById('conProblemas').textContent = paquetes.filter(p => p.problema).length;
}

// Ver detalles del paquete
function verDetalles(id) {
    const paquete = paquetes.find(p => p.id === id);
    if (!paquete) return;
    
    // Obtener historial real
    fetch(`../../controller/paquetesAdminController.php?action=get_paquete_details&id=${id}`)
        .then(res => res.json())
        .then(data => {
            const historial = data.success ? data.historial : [];
            renderModalDetalles(paquete, historial);
        });
}

function renderModalDetalles(paquete, historial) {
    const detallesHTML = `
        <div class="detalle-section">
            <h3>Información del Paquete</h3>
            <div class="detalle-grid">
                <div class="detalle-item">
                    <div class="detalle-label">N° de Guía</div>
                    <div class="detalle-value">${paquete.guia}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Fecha de Ingreso</div>
                    <div class="detalle-value">${formatDateTime(paquete.fechaIngreso)}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Estado</div>
                    <div class="detalle-value"><span class="status-badge status-${paquete.estado}">${formatEstado(paquete.estado)}</span></div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Tipo</div>
                    <div class="detalle-value">${formatTipo(paquete.tipo)}</div>
                </div>
            </div>
        </div>

        <div class="detalle-section">
            <h3>Remitente y Destinatario</h3>
            <div class="detalle-grid">
                <div class="detalle-item">
                    <div class="detalle-label">Remitente</div>
                    <div class="detalle-value">${paquete.remitente}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Destinatario</div>
                    <div class="detalle-value">${paquete.destinatario}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Teléfono</div>
                    <div class="detalle-value">${paquete.telefonoDestinatario}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Dirección</div>
                    <div class="detalle-value">${paquete.direccion}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Zona</div>
                    <div class="detalle-value">${paquete.zona.toUpperCase()}</div>
                </div>
            </div>
        </div>

        <div class="detalle-section">
            <h3>Detalles del Envío</h3>
            <div class="detalle-grid">
                <div class="detalle-item">
                    <div class="detalle-label">Valor</div>
                    <div class="detalle-value">${formatCurrency(paquete.valor)}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Peso</div>
                    <div class="detalle-value">${paquete.peso} kg</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Mensajero</div>
                    <div class="detalle-value">${paquete.mensajero || 'Sin asignar'}</div>
                </div>
            </div>
            ${paquete.observaciones ? `
                <div class="detalle-item" style="margin-top: 15px;">
                    <div class="detalle-label">Observaciones</div>
                    <div class="detalle-value">${paquete.observaciones}</div>
                </div>
            ` : ''}
        </div>

        <div class="detalle-section">
            <h3>Historial de Cambios</h3>
            <div class="historial-timeline">
                ${historial.map(h => `
                    <div class="historial-item">
                        <div class="historial-time">${formatDateTime(h.fecha)}</div>
                        <div class="historial-text">
                            <strong>${formatEstado(h.estado)}</strong> - ${h.descripcion}
                            <br><small>Por: ${h.usuario}</small>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    document.getElementById('detallesPaquete').innerHTML = detallesHTML;
    openModal('modalDetalles');
}

// Editar paquete
function editarPaquete(id) {
    const paquete = paquetes.find(p => p.id === id);
    if (!paquete) return;
    
    currentPaquete = paquete;
    
    document.getElementById('editGuia').value = paquete.guia;
    document.getElementById('editRemitente').value = paquete.remitente;
    document.getElementById('editDestinatario').value = paquete.destinatario;
    document.getElementById('editTelefono').value = paquete.telefonoDestinatario;
    document.getElementById('editDireccion').value = paquete.direccion;
    document.getElementById('editZona').value = paquete.zona;
    document.getElementById('editTipo').value = paquete.tipo;
    document.getElementById('editValor').value = paquete.valor;
    document.getElementById('editPeso').value = paquete.peso;
    document.getElementById('editObservaciones').value = paquete.observaciones;
    
    openModal('modalEditar');
}

// Manejar edición de paquete (AJAX)
async function handleEditarPaquete(e) {
    e.preventDefault();
    
    if (!currentPaquete) return;
    
    const formData = new FormData();
    formData.append('action', 'update_paquete');
    formData.append('id', currentPaquete.id);
    formData.append('destinatario', document.getElementById('editDestinatario').value);
    formData.append('telefono', document.getElementById('editTelefono').value);
    formData.append('direccion', document.getElementById('editDireccion').value);
    formData.append('zona', document.getElementById('editZona').value);
    formData.append('tipo', document.getElementById('editTipo').value);
    formData.append('valor', document.getElementById('editValor').value);
    formData.append('peso', document.getElementById('editPeso').value);
    formData.append('observaciones', document.getElementById('editObservaciones').value);

    try {
        const response = await fetch('../../controller/paquetesAdminController.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            closeModal('modalEditar');
            applyFilters(); // Recargar tabla
            showNotification('Paquete actualizado correctamente', 'success');
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}

// Asignar mensajero
function asignarMensajero(id) {
    const paquete = paquetes.find(p => p.id === id);
    if (!paquete) return;
    
    currentPaquete = paquete;
    document.getElementById('asignarGuia').value = paquete.guia;
    document.getElementById('asignarMensajero').value = '';
    document.getElementById('mensajeroInfo').classList.remove('active');
    
    openModal('modalAsignar');
}

// Mostrar información del mensajero
function mostrarInfoMensajero() {
    const select = document.getElementById('asignarMensajero');
    const option = select.options[select.selectedIndex];
    const infoCard = document.getElementById('mensajeroInfo');
    
    if (select.value) {
        const disponible = option.dataset.disponible === 'true';
        const tareas = option.dataset.tareas;
        const zona = option.dataset.zona;
        
        infoCard.innerHTML = `
            <h4>${option.text.split(' - ')[0]}</h4>
            <p><strong>Estado:</strong> ${disponible ? '🟢 Disponible' : '🔴 Ocupado'}</p>
            <p><strong>Tareas activas:</strong> ${tareas}</p>
            <p><strong>Zona asignada:</strong> ${zona.toUpperCase()}</p>
        `;
        infoCard.classList.add('active');
    } else {
        infoCard.classList.remove('active');
    }
}

// Manejar asignación de mensajero (AJAX)
async function handleAsignarMensajero(e) {
    e.preventDefault();
    
    if (!currentPaquete) return;
    
    const mensajeroId = document.getElementById('asignarMensajero').value;
    
    const formData = new FormData();
    formData.append('action', 'assign_mensajero');
    formData.append('paquete_id', currentPaquete.id);
    formData.append('mensajero_id', mensajeroId);

    try {
        const response = await fetch('../../controller/paquetesAdminController.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            closeModal('modalAsignar');
            applyFilters(); // Recargar tabla
            showNotification('Mensajero asignado correctamente', 'success');
        } else {
            showNotification('Error: ' + result.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión', 'error');
    }
}
