// Variables globales
let map;
let marker;
let recolecciones = [];
let mensajeros = [];
let selectedMensajero = null;
let recoleccionToCancel = null;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadInitialData();
});

// Inicializar aplicación
function initializeApp() {
    initializeMap();
    setMinDate();
    startPolling();
}

// Configurar event listeners
function setupEventListeners() {
    // Modal Nueva Recolección
    document.getElementById('btnNuevaRecoleccion').addEventListener('click', openNewRecoleccionModal);
    document.getElementById('btnCerrarModal').addEventListener('click', closeNewRecoleccionModal);
    document.getElementById('btnCancelarForm').addEventListener('click', closeNewRecoleccionModal);
    
    // Modal Cancelar
    document.getElementById('btnCerrarModalCancelar').addEventListener('click', closeCancelModal);
    document.getElementById('btnCerrarCancelar').addEventListener('click', closeCancelModal);
    
    // Formularios
    document.getElementById('formNuevaRecoleccion').addEventListener('submit', handleSubmitRecoleccion);
    document.getElementById('formCancelar').addEventListener('submit', handleCancelRecoleccion);
    
    // Obtener ubicación
    document.getElementById('btnObtenerUbicacion').addEventListener('click', obtenerUbicacionActual);
    
    // Filtros
    document.getElementById('busqueda').addEventListener('input', applyFilters);
    document.getElementById('filtroEstado').addEventListener('change', applyFilters);
    document.getElementById('filtroPrioridad').addEventListener('change', applyFilters);
    document.getElementById('filtroFecha').addEventListener('change', applyFilters);
    
    // Reportes
    document.getElementById('btnReportes').addEventListener('click', generarReportes);
}

// Inicializar mapa
function initializeMap() {
    map = L.map('mapContainer').setView([4.6097, -74.0817], 13); // Bogotá por defecto
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Click en el mapa para seleccionar ubicación
    map.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);
        updateMapMarker(lat, lng);
    });
}

// Actualizar marcador en el mapa
function updateMapMarker(lat, lng) {
    if (marker) {
        map.removeLayer(marker);
    }
    
    marker = L.marker([lat, lng]).addTo(map);
    document.getElementById('latitud').value = lat;
    document.getElementById('longitud').value = lng;
    
    map.setView([lat, lng], 15);
}

// Obtener ubicación actual del navegador
function obtenerUbicacionActual() {
    if (navigator.geolocation) {
        const btn = document.getElementById('btnObtenerUbicacion');
        btn.disabled = true;
        btn.innerHTML = '<span class="loading"></span> Obteniendo ubicación...';
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude.toFixed(6);
                const lng = position.coords.longitude.toFixed(6);
                updateMapMarker(lat, lng);
                btn.disabled = false;
                btn.innerHTML = '📍 Obtener Ubicación Actual';
                showNotification('Ubicación obtenida correctamente', 'success');
            },
            function(error) {
                btn.disabled = false;
                btn.innerHTML = '📍 Obtener Ubicación Actual';
                showNotification('No se pudo obtener la ubicación', 'error');
            }
        );
    } else {
        showNotification('Tu navegador no soporta geolocalización', 'error');
    }
}

// Establecer fecha mínima (hoy)
function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('fechaRecoleccion').min = today;
    document.getElementById('fechaRecoleccion').value = today;
}

// Cargar datos iniciales
async function loadInitialData(isBackground = false) {
    try {
        // Aquí deberías hacer llamadas reales a tu API
        // const [clientesData, mensajerosData, recoleccionesData] = await Promise.all([
        //     fetch('api/clientes.php').then(r => r.json()),
        //     fetch('api/mensajeros.php').then(r => r.json()),
        //     fetch('api/recolecciones.php').then(r => r.json())
        // ]);
        
        // Datos de ejemplo
        const clientesData = generateMockClientes();
        mensajeros = generateMockMensajeros();
        recolecciones = generateMockRecolecciones();
        
        loadClientes(clientesData);
        checkAlertas();
        renderRecolecciones();
        updateStats();
        
    } catch (error) {
        console.error('Error al cargar datos:', error);
        if (!isBackground) {
            showNotification('Error al cargar los datos', 'error');
        }
    }
}

// Iniciar actualización automática
function startPolling() {
    setInterval(() => {
        // Solo actualizar si no hay modales abiertos
        if (!document.querySelector('.modal.active')) {
            loadInitialData(true);
        }
    }, 15000); // Actualizar cada 15 segundos
}

// Generar clientes de ejemplo
function generateMockClientes() {
    return [
        { id: 1, nombre: 'Empresa ABC Ltda', telefono: '3001234567' },
        { id: 2, nombre: 'Comercializadora XYZ', telefono: '3009876543' },
        { id: 3, nombre: 'Distribuidora 123', telefono: '3005555555' },
        { id: 4, nombre: 'Importadora Global', telefono: '3007777777' }
    ];
}

// Generar mensajeros de ejemplo
function generateMockMensajeros() {
    return [
        {
            id: 1,
            nombre: 'Carlos Martínez',
            estado: 'disponible',
            tareasActivas: 2,
            ubicacion: { lat: 4.6097, lng: -74.0817 },
            entregas: 23
        },
        {
            id: 2,
            nombre: 'Ana García',
            estado: 'disponible',
            tareasActivas: 1,
            ubicacion: { lat: 4.6500, lng: -74.1000 },
            entregas: 19
        },
        {
            id: 3,
            nombre: 'Luis Rodríguez',
            estado: 'ocupado',
            tareasActivas: 4,
            ubicacion: { lat: 4.5900, lng: -74.0700 },
            entregas: 18
        },
        {
            id: 4,
            nombre: 'María Sánchez',
            estado: 'disponible',
            tareasActivas: 0,
            ubicacion: { lat: 4.6200, lng: -74.0900 },
            entregas: 16
        }
    ];
}

// Generar recolecciones de ejemplo
function generateMockRecolecciones() {
    const estados = ['asignada', 'en_curso', 'completada'];
    const prioridades = ['urgente', 'normal', 'programada'];
    const recolecciones = [];
    
    for (let i = 1; i <= 15; i++) {
        const estado = estados[Math.floor(Math.random() * estados.length)];
        recolecciones.push({
            id: i,
            orden: `REC-${String(i).padStart(5, '0')}`,
            cliente: `Cliente ${i}`,
            direccion: `Calle ${i} # ${10 + i}-${20 + i}`,
            contacto: `Contacto ${i}`,
            telefono: `300${String(i).padStart(7, '0')}`,
            mensajero: mensajeros[Math.floor(Math.random() * mensajeros.length)].nombre,
            mensajeroId: Math.floor(Math.random() * mensajeros.length) + 1,
            estado: estado,
            prioridad: prioridades[Math.floor(Math.random() * prioridades.length)],
            fechaProgramada: new Date().toISOString().split('T')[0],
            horario: '10:00-12:00',
            fechaCompletada: estado === 'completada' ? new Date().toISOString() : null,
            cantidad: Math.floor(Math.random() * 10) + 1
        });
    }
    
    return recolecciones;
}

// Cargar clientes en el select
function loadClientes(clientes) {
    const select = document.getElementById('cliente');
    select.innerHTML = '<option value="">Seleccione un cliente</option>';
    
    clientes.forEach(cliente => {
        const option = document.createElement('option');
        option.value = cliente.id;
        option.textContent = cliente.nombre;
        option.dataset.telefono = cliente.telefono;
        select.appendChild(option);
    });
    
    // Auto-llenar contacto al seleccionar cliente
    select.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected.dataset.telefono) {
            document.getElementById('telefono').value = selected.dataset.telefono;
        }
    });
}

// Verificar y mostrar alertas
function checkAlertas() {
    const alertsSection = document.getElementById('alertsSection');
    alertsSection.innerHTML = '';
    
    const now = new Date();
    const recoleccionesRetrasadas = recolecciones.filter(r => {
        if (r.estado === 'completada' || r.estado === 'cancelada') return false;
        const programada = new Date(r.fechaProgramada + ' ' + r.horario.split('-')[1]);
        return programada < now;
    });
    
    if (recoleccionesRetrasadas.length > 0) {
        const alert = createAlert(
            'danger',
            'Recolecciones Retrasadas',
            `Hay ${recoleccionesRetrasadas.length} recolecciones que superaron su horario programado`
        );
        alertsSection.appendChild(alert);
    }
    
    const mensajerosInactivos = mensajeros.filter(m => m.estado === 'ocupado' && m.tareasActivas > 3);
    if (mensajerosInactivos.length > 0) {
        const alert = createAlert(
            'warning',
            'Mensajeros Sobrecargados',
            `${mensajerosInactivos.length} mensajeros tienen más de 3 tareas activas`
        );
        alertsSection.appendChild(alert);
    }
}

// Crear elemento de alerta
function createAlert(type, title, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">
            <h4>${title}</h4>
            <p>${message}</p>
        </div>
    `;
    return alert;
}

// Abrir modal nueva recolección
function openNewRecoleccionModal() {
    document.getElementById('modalNuevaRecoleccion').classList.add('active');
    loadMensajerosDisponibles();
}

// Cerrar modal nueva recolección
function closeNewRecoleccionModal() {
    document.getElementById('modalNuevaRecoleccion').classList.remove('active');
    document.getElementById('formNuevaRecoleccion').reset();
    selectedMensajero = null;
    if (marker) {
        map.removeLayer(marker);
        marker = null;
    }
}

// Cargar mensajeros disponibles
function loadMensajerosDisponibles() {
    const container = document.getElementById('mensajerosDisponibles');
    container.innerHTML = '';
    
    mensajeros.forEach(mensajero => {
        const card = document.createElement('div');
        card.className = 'mensajero-card';
        card.dataset.id = mensajero.id;
        
        // Calcular distancia (simulada)
        const distancia = (Math.random() * 10 + 1).toFixed(1);
        
        card.innerHTML = `
            <div class="mensajero-header">
                <div class="mensajero-avatar">${mensajero.nombre.charAt(0)}</div>
                <div class="mensajero-info">
                    <h4>${mensajero.nombre}</h4>
                    <span class="mensajero-status ${mensajero.estado}">${mensajero.estado === 'disponible' ? '🟢 Disponible' : '🟡 Ocupado'}</span>
                </div>
            </div>
            <div class="mensajero-details">
                <p>📍 Distancia: ~${distancia} km</p>
                <p>📦 Tareas activas: ${mensajero.tareasActivas}</p>
                <p>✅ Entregas hoy: ${mensajero.entregas}</p>
            </div>
        `;
        
        card.addEventListener('click', function() {
            document.querySelectorAll('.mensajero-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            selectedMensajero = mensajero.id;
        });
        
        container.appendChild(card);
    });
}

// Manejar envío de formulario
async function handleSubmitRecoleccion(e) {
    e.preventDefault();
    
    if (!selectedMensajero) {
        showNotification('Por favor selecciona un mensajero', 'error');
        return;
    }
    
    const formData = new FormData(e.target);
    const data = {
        cliente: formData.get('cliente'),
        contacto: formData.get('contacto'),
        telefono: formData.get('telefono'),
        direccion: formData.get('direccion'),
        latitud: formData.get('latitud'),
        longitud: formData.get('longitud'),
        descripcion: formData.get('descripcion'),
        cantidad: formData.get('cantidad'),
        fechaRecoleccion: formData.get('fechaRecoleccion'),
        horario: formData.get('horario'),
        prioridad: formData.get('prioridad'),
        observaciones: formData.get('observaciones'),
        mensajeroId: selectedMensajero
    };
    
    try {
        // Aquí deberías hacer la llamada a tu API
        // const response = await fetch('api/recolecciones.php', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify(data)
        // });
        
        // Simulación
        const newRecoleccion = {
            id: recolecciones.length + 1,
            orden: `REC-${String(recolecciones.length + 1).padStart(5, '0')}`,
            cliente: document.getElementById('cliente').options[document.getElementById('cliente').selectedIndex].text,
            direccion: data.direccion,
            contacto: data.contacto,
            telefono: data.telefono,
            mensajero: mensajeros.find(m => m.id === selectedMensajero).nombre,
            mensajeroId: selectedMensajero,
            estado: 'asignada',
            prioridad: data.prioridad,
            fechaProgramada: data.fechaRecoleccion,
            horario: data.horario,
            fechaCompletada: null,
            cantidad: data.cantidad
        };
        
        recolecciones.unshift(newRecoleccion);
        renderRecolecciones();
        updateStats();
        closeNewRecoleccionModal();
        
        showNotification('Recolección creada y asignada correctamente', 'success');
        
        // Simular notificación al mensajero
        setTimeout(() => {
            showNotification(`Notificación enviada a ${newRecoleccion.mensajero}`, 'info');
        }, 1000);
        
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al crear la recolección', 'error');
    }
}

// Renderizar tabla de recolecciones
function renderRecolecciones() {
    const tbody = document.getElementById('tablaRecoleccionesBody');
    
    if (recolecciones.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px;">
                    <div class="empty-state">
                        <p>No hay recolecciones registradas</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = recolecciones.map(rec => `
        <tr>
            <td><strong>${rec.orden}</strong></td>
            <td>${rec.cliente}</td>
            <td>${rec.direccion}</td>
            <td>${rec.contacto}<br><small>${rec.telefono}</small></td>
            <td>${rec.mensajero}</td>
            <td><span class="status-badge status-${rec.estado}">${formatEstado(rec.estado)}</span></td>
            <td><span class="priority-badge priority-${rec.prioridad}">${formatPrioridad(rec.prioridad)}</span></td>
            <td>${formatDate(rec.fechaProgramada)}<br><small>${rec.horario}</small></td>
            <td>${rec.fechaCompletada ? formatDateTime(rec.fechaCompletada) : '-'}</td>
            <td>
                <div class="action-buttons">
                    ${rec.estado !== 'completada' && rec.estado !== 'cancelada' ? `
                        <button class="btn btn-sm btn-warning" onclick="reasignarRecoleccion(${rec.id})">
                            🔄 Reasignar
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="openCancelModal(${rec.id})">
                            ❌ Cancelar
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-secondary" onclick="verDetalles(${rec.id})">
                        👁️ Ver
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Formatear estado
function formatEstado(estado) {
    const estados = {
        'asignada': 'Asignada',
        'en_curso': 'En Curso',
        'completada': 'Completada',
        'cancelada': 'Cancelada'
    };
    return estados[estado] || estado;
}

// Formatear prioridad
function formatPrioridad(prioridad) {
    const prioridades = {
        'urgente': '🔴 Urgente',
        'normal': '🟡 Normal',
        'programada': '🟢 Programada'
    };
    return prioridades[prioridad] || prioridad;
}

// Formatear fecha
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES');
}

// Formatear fecha y hora
function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('es-ES');
}

// Actualizar estadísticas
function updateStats() {
    document.getElementById('totalRecolecciones').textContent = recolecciones.length;
    document.getElementById('pendientes').textContent = recolecciones.filter(r => 
        r.estado === 'asignada' || r.estado === 'en_curso'
    ).length;
    document.getElementById('completadas').textContent = recolecciones.filter(r => 
        r.estado === 'completada'
    ).length;
}

// Aplicar filtros
function applyFilters() {
    const busqueda = document.getElementById('busqueda').value.toLowerCase();
    const estado = document.getElementById('filtroEstado').value;
    const prioridad = document.getElementById('filtroPrioridad').value;
    const fecha = document.getElementById('filtroFecha').value;
    
    const filtered = recolecciones.filter(rec => {
        const matchBusqueda = !busqueda || 
            rec.cliente.toLowerCase().includes(busqueda) ||
            rec.direccion.toLowerCase().includes(busqueda) ||
            rec.mensajero.toLowerCase().includes(busqueda);
        
        const matchEstado = !estado || rec.estado === estado;
        const matchPrioridad = !prioridad || rec.prioridad === prioridad;
        const matchFecha = !fecha || rec.fechaProgramada === fecha;
        
        return matchBusqueda && matchEstado && matchPrioridad && matchFecha;
    });
    
    // Temporalmente reemplazar el array para renderizar
    const temp = recolecciones;
    recolecciones = filtered;
    renderRecolecciones();
    recolecciones = temp;
}

// Reasignar recolección
function reasignarRecoleccion(id) {
    const recoleccion = recolecciones.find(r => r.id === id);
    if (!recoleccion) return;
    
    // Abrir modal con opciones de mensajeros
    openNewRecoleccionModal();
    showNotification(`Reasignando recolección ${recoleccion.orden}`, 'info');
}

// Abrir modal de cancelación
function openCancelModal(id) {
    recoleccionToCancel = id;
    document.getElementById('modalCancelar').classList.add('active');
}

// Cerrar modal de cancelación
function closeCancelModal() {
    document.getElementById('modalCancelar').classList.remove('active');
    document.getElementById('formCancelar').reset();
    recoleccionToCancel = null;
}

// Manejar cancelación
async function handleCancelRecoleccion(e) {
    e.preventDefault();
    
    const motivo = document.getElementById('motivoCancelacion').value;
    
    if (!recoleccionToCancel) return;
    
    try {
        // Aquí deberías hacer la llamada a tu API
        // await fetch(`api/recolecciones.php?id=${recoleccionToCancel}`, {
        //     method: 'DELETE',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({ motivo })
        // });
        
        const recoleccion = recolecciones.find(r => r.id === recoleccionToCancel);
        if (recoleccion) {
            recoleccion.estado = 'cancelada';
            recoleccion.motivoCancelacion = motivo;
        }
        
        renderRecolecciones();
        updateStats();
        closeCancelModal();
        showNotification('Recolección cancelada correctamente', 'success');
        
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al cancelar la recolección', 'error');
    }
}

// Ver detalles
function verDetalles(id) {
    const recoleccion = recolecciones.find(r => r.id === id);
    if (recoleccion) {
        alert(`Detalles de la recolección:\n\nOrden: ${recoleccion.orden}\nCliente: ${recoleccion.cliente}\nEstado: ${formatEstado(recoleccion.estado)}`);
    }
}

// Generar reportes
function generarReportes() {
    const reporte = {
        total: recolecciones.length,
        completadas: recolecciones.filter(r => r.estado === 'completada').length,
        pendientes: recolecciones.filter(r => r.estado === 'asignada' || r.estado === 'en_curso').length,
        canceladas: recolecciones.filter(r => r.estado === 'cancelada').length,
        tasaCompletado: ((recolecciones.filter(r => r.estado === 'completada').length / recolecciones.length) * 100).toFixed(2)
    };
    
    alert(`📊 Reporte de Productividad\n\nTotal de recolecciones: ${reporte.total}\nCompletadas: ${reporte.completadas}\nPendientes: ${reporte.pendientes}\nCanceladas: ${reporte.canceladas}\nTasa de completado: ${reporte.tasaCompletado}%`);
}

// Mostrar notificación
function showNotification(message, type = 'info') {
    // Crear notificación temporal
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '10000';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        <div class="alert-content">
            <p>${message}</p>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}