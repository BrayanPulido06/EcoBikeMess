// Variables globales
let administradores = [];
let mensajeros = [];
let logs = [];
let solicitudesPendientes = [];
let currentUser = { rol: 'super_admin' }; // Usuario actual (simula sesión)
let adminEnEdicion = null;
let userToReset = null;

// Permisos por rol
const PERMISOS_POR_ROL = {
    super_admin: ['todos'],
    admin_operativo: ['crear_paquetes', 'editar_paquetes', 'asignar_mensajeros', 'gestionar_clientes'],
    admin_reportes: ['ver_reportes'],
    admin_mensajeros: ['asignar_mensajeros', 'gestionar_mensajeros']
};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadInitialData();
});

// Inicializar aplicación
function initializeApp() {
    // Verificar permisos del usuario actual
    if (currentUser.rol !== 'super_admin') {
        document.getElementById('btnNuevoAdmin').style.display = 'none';
    }
}

// Configurar event listeners
function setupEventListeners() {
    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            switchTab(tabId);
        });
    });

    // Administradores
    document.getElementById('btnNuevoAdmin').addEventListener('click', () => abrirModalAdmin());
    document.getElementById('searchAdmin').addEventListener('input', filtrarAdmins);
    document.getElementById('btnCerrarModalAdmin').addEventListener('click', () => closeModal('modalAdmin'));
    document.getElementById('btnCancelarAdmin').addEventListener('click', () => closeModal('modalAdmin'));
    document.getElementById('formAdmin').addEventListener('submit', guardarAdministrador);
    document.getElementById('adminRol').addEventListener('change', actualizarPermisosSegunRol);
    document.getElementById('adminFoto').addEventListener('change', previsualizarFoto);

    // Mensajeros
    document.getElementById('btnSolicitudesPendientes').addEventListener('click', () => openModal('modalSolicitudes'));
    document.getElementById('btnReporteMensajeros').addEventListener('click', generarReporteMensajeros);
    document.getElementById('searchMensajero').addEventListener('input', filtrarMensajeros);
    document.getElementById('filtroEstadoMensajero').addEventListener('change', filtrarMensajeros);
    document.getElementById('btnCerrarModalMensajero').addEventListener('click', () => closeModal('modalMensajero'));
    document.getElementById('btnCerrarModalSolicitudes').addEventListener('click', () => closeModal('modalSolicitudes'));

    // Reset Password
    document.getElementById('btnCerrarModalReset').addEventListener('click', () => closeModal('modalResetPassword'));
    document.getElementById('btnCancelarReset').addEventListener('click', () => closeModal('modalResetPassword'));
    document.getElementById('btnConfirmarReset').addEventListener('click', confirmarResetPassword);

    // Logs
    document.getElementById('filtroUsuarioLog').addEventListener('change', filtrarLogs);
    document.getElementById('filtroAccionLog').addEventListener('change', filtrarLogs);
    document.getElementById('filtroFechaLog').addEventListener('change', filtrarLogs);
}

// Cambiar de tab
function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById(`tab-${tabId}`).classList.add('active');
    
    if (tabId === 'logs') {
        renderLogs();
    }
}

// Cargar datos iniciales
async function loadInitialData() {
    try {
        // Aquí deberías hacer llamadas reales a tu API
        administradores = generateMockAdmins();
        mensajeros = generateMockMensajeros();
        logs = generateMockLogs();
        solicitudesPendientes = generateMockSolicitudes();
        
        renderAdministradores();
        renderMensajeros();
        actualizarEstadisticas();
        cargarUsuariosEnFiltros();
        
    } catch (error) {
        console.error('Error al cargar datos:', error);
        showNotification('Error al cargar los datos', 'error');
    }
}

// Generar administradores de ejemplo
function generateMockAdmins() {
    return [
        {
            id: 1,
            nombre: 'Juan Pérez',
            email: 'juan@sistema.com',
            telefono: '3001234567',
            rol: 'super_admin',
            estado: 'activo',
            foto: null,
            fechaCreacion: new Date('2024-01-01').toISOString(),
            ultimoAcceso: new Date().toISOString(),
            permisos: ['todos']
        },
        {
            id: 2,
            nombre: 'María García',
            email: 'maria@sistema.com',
            telefono: '3009876543',
            rol: 'admin_operativo',
            estado: 'activo',
            foto: null,
            fechaCreacion: new Date('2024-02-15').toISOString(),
            ultimoAcceso: new Date(Date.now() - 3600000).toISOString(),
            permisos: ['crear_paquetes', 'editar_paquetes', 'asignar_mensajeros']
        },
        {
            id: 3,
            nombre: 'Carlos López',
            email: 'carlos@sistema.com',
            telefono: '3005555555',
            rol: 'admin_reportes',
            estado: 'inactivo',
            foto: null,
            fechaCreacion: new Date('2024-03-10').toISOString(),
            ultimoAcceso: new Date(Date.now() - 86400000).toISOString(),
            permisos: ['ver_reportes']
        }
    ];
}

// Generar mensajeros de ejemplo
function generateMockMensajeros() {
    const mensajeros = [];
    const estados = ['activo', 'en_ruta', 'descanso', 'inactivo'];
    
    for (let i = 1; i <= 15; i++) {
        mensajeros.push({
            id: i,
            nombre: `Mensajero ${i}`,
            telefono: `300${String(i).padStart(7, '0')}`,
            email: `mensajero${i}@sistema.com`,
            estado: estados[Math.floor(Math.random() * estados.length)],
            ubicacionActual: `Calle ${Math.floor(Math.random() * 100)} # ${Math.floor(Math.random() * 50)}-${Math.floor(Math.random() * 100)}`,
            paquetesAsignados: Math.floor(Math.random() * 10),
            entregasHoy: Math.floor(Math.random() * 15),
            rendimiento: (Math.random() * 30 + 70).toFixed(1),
            fechaRegistro: new Date(Date.now() - Math.random() * 90 * 86400000).toISOString(),
            aprobado: true
        });
    }
    
    return mensajeros;
}

// Generar logs de ejemplo
function generateMockLogs() {
    const acciones = ['login', 'logout', 'crear', 'editar', 'eliminar'];
    const logs = [];
    
    for (let i = 0; i < 50; i++) {
        logs.push({
            id: i + 1,
            usuario: administradores[Math.floor(Math.random() * administradores.length)].nombre,
            usuarioId: Math.floor(Math.random() * administradores.length) + 1,
            accion: acciones[Math.floor(Math.random() * acciones.length)],
            descripcion: 'Acción realizada en el sistema',
            fecha: new Date(Date.now() - Math.random() * 7 * 86400000).toISOString(),
            ip: `192.168.1.${Math.floor(Math.random() * 255)}`
        });
    }
    
    return logs.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
}

// Generar solicitudes de ejemplo
function generateMockSolicitudes() {
    return [
        {
            id: 1,
            nombre: 'Pedro Rodríguez',
            telefono: '3001112233',
            email: 'pedro@email.com',
            cedula: '1234567890',
            direccion: 'Calle 123 # 45-67',
            fechaSolicitud: new Date().toISOString(),
            documentos: ['cedula.pdf', 'antecedentes.pdf']
        },
        {
            id: 2,
            nombre: 'Ana Martínez',
            telefono: '3004445566',
            email: 'ana@email.com',
            cedula: '9876543210',
            direccion: 'Carrera 7 # 12-34',
            fechaSolicitud: new Date(Date.now() - 86400000).toISOString(),
            documentos: ['cedula.pdf', 'antecedentes.pdf']
        }
    ];
}

// Renderizar administradores
function renderAdministradores() {
    const tbody = document.getElementById('tablaAdminsBody');
    
    if (administradores.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;">No hay administradores registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = administradores.map(admin => `
        <tr>
            <td>
                ${admin.foto ? `<img src="${admin.foto}" class="user-photo" alt="${admin.nombre}">` : 
                `<div class="user-photo-placeholder">${admin.nombre.charAt(0)}</div>`}
            </td>
            <td><strong>${admin.nombre}</strong></td>
            <td>${admin.email}</td>
            <td><span class="role-badge role-${admin.rol}">${formatRol(admin.rol)}</span></td>
            <td><span class="status-badge status-${admin.estado}">${admin.estado === 'activo' ? 'Activo' : 'Inactivo'}</span></td>
            <td>${formatDate(admin.fechaCreacion)}</td>
            <td>${formatRelativeTime(admin.ultimoAcceso)}</td>
            <td>
                <div class="action-buttons">
                    ${currentUser.rol === 'super_admin' ? `
                        <button class="btn btn-sm btn-info" onclick="editarAdmin(${admin.id})" title="Editar">✏️</button>
                        <button class="btn btn-sm btn-warning" onclick="toggleEstadoAdmin(${admin.id})" title="Cambiar estado">
                            ${admin.estado === 'activo' ? '🔒' : '🔓'}
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="resetPassword(${admin.id})" title="Resetear contraseña">🔑</button>
                        ${admin.id !== currentUser.id ? `
                            <button class="btn btn-sm btn-danger" onclick="eliminarAdmin(${admin.id})" title="Eliminar">🗑️</button>
                        ` : ''}
                    ` : `
                        <button class="btn btn-sm btn-info" onclick="verDetallesAdmin(${admin.id})">👁️</button>
                    `}
                </div>
            </td>
        </tr>
    `).join('');
}

// Renderizar mensajeros
function renderMensajeros() {
    const tbody = document.getElementById('tablaMensajerosBody');
    
    if (mensajeros.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;">No hay mensajeros registrados</td></tr>';
        return;
    }
    
    tbody.innerHTML = mensajeros.map(m => `
        <tr>
            <td><strong>M${String(m.id).padStart(3, '0')}</strong></td>
            <td>${m.nombre}</td>
            <td>${m.telefono}</td>
            <td><span class="status-badge status-${m.estado}">${formatEstadoMensajero(m.estado)}</span></td>
            <td>${m.ubicacionActual}</td>
            <td><strong>${m.paquetesAsignados}</strong></td>
            <td><strong>${m.entregasHoy}</strong></td>
            <td>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 60px; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">
                        <div style="width: ${m.rendimiento}%; height: 100%; background: ${m.rendimiento > 80 ? '#28a745' : m.rendimiento > 60 ? '#ffc107' : '#dc3545'};"></div>
                    </div>
                    <span>${m.rendimiento}%</span>
                </div>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-info" onclick="verDetallesMensajero(${m.id})">👁️</button>
                    <button class="btn btn-sm btn-warning" onclick="toggleEstadoMensajero(${m.id})">
                        ${m.estado === 'activo' || m.estado === 'en_ruta' ? '⏸️' : '▶️'}
                    </button>
                    ${currentUser.rol === 'super_admin' ? `
                        <button class="btn btn-sm btn-danger" onclick="eliminarMensajero(${m.id})">🗑️</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

// Renderizar logs
function renderLogs() {
    const container = document.getElementById('logsContainer');
    
    if (logs.length === 0) {
        container.innerHTML = '<p style="text-align: center; padding: 40px; color: #999;">No hay registros de actividad</p>';
        return;
    }
    
    container.innerHTML = logs.slice(0, 50).map(log => `
        <div class="log-item">
            <div class="log-item-header">
                <span class="log-user">${log.usuario}</span>
                <span class="log-time">${formatDateTime(log.fecha)}</span>
            </div>
            <div class="log-action">
                <strong>${log.accion.toUpperCase()}</strong>: ${log.descripcion}
            </div>
            <div style="font-size: 0.85em; color: #999; margin-top: 5px;">IP: ${log.ip}</div>
        </div>
    `).join('');
}

// Abrir modal administrador
function abrirModalAdmin(adminId = null) {
    if (currentUser.rol !== 'super_admin') {
        showNotification('No tiene permisos para crear administradores', 'error');
        return;
    }
    
    adminEnEdicion = adminId;
    
    if (adminId) {
        const admin = administradores.find(a => a.id === adminId);
        if (!admin) return;
        
        document.getElementById('modalAdminTitle').textContent = 'Editar Administrador';
        document.getElementById('adminId').value = admin.id;
        document.getElementById('adminNombre').value = admin.nombre;
        document.getElementById('adminEmail').value = admin.email;
        document.getElementById('adminTelefono').value = admin.telefono;
        document.getElementById('adminRol').value = admin.rol;
        document.getElementById('adminEstado').value = admin.estado;
        
        // Ocultar campo de contraseña al editar
        document.getElementById('passwordGroup').style.display = 'none';
        
        // Marcar permisos
        document.querySelectorAll('input[name="permiso"]').forEach(checkbox => {
            checkbox.checked = admin.permisos.includes(checkbox.value) || admin.permisos.includes('todos');
        });
    } else {
        document.getElementById('modalAdminTitle').textContent = 'Nuevo Administrador';
        document.getElementById('formAdmin').reset();
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('photoPreview').innerHTML = '<span>📷</span>';
    }
    
    openModal('modalAdmin');
}

// Editar admin
function editarAdmin(id) {
    abrirModalAdmin(id);
}

// Guardar administrador
function guardarAdministrador(e) {
    e.preventDefault();
    
    if (currentUser.rol !== 'super_admin') {
        showNotification('No tiene permisos para esta acción', 'error');
        return;
    }
    
    const id = document.getElementById('adminId').value;
    const nombre = document.getElementById('adminNombre').value;
    const email = document.getElementById('adminEmail').value;
    const telefono = document.getElementById('adminTelefono').value;
    const rol = document.getElementById('adminRol').value;
    const estado = document.getElementById('adminEstado').value;
    const password = document.getElementById('adminPassword').value;
    
    // Validar email único
    const emailExiste = administradores.some(a => a.email === email && a.id != id);
    if (emailExiste) {
        showNotification('El email ya está registrado', 'error');
        return;
    }
    
    // Obtener permisos seleccionados
    const permisos = Array.from(document.querySelectorAll('input[name="permiso"]:checked'))
        .map(cb => cb.value);
    
    if (id) {
        // Editar
        const admin = administradores.find(a => a.id == id);
        if (admin) {
            admin.nombre = nombre;
            admin.email = email;
            admin.telefono = telefono;
            admin.rol = rol;
            admin.estado = estado;
            admin.permisos = permisos;
            
            registrarLog('editar', `Administrador ${nombre} actualizado`);
            showNotification('Administrador actualizado exitosamente', 'success');
        }
    } else {
        // Crear nuevo
        const nuevoAdmin = {
            id: administradores.length + 1,
            nombre,
            email,
            telefono,
            rol,
            estado,
            foto: null,
            fechaCreacion: new Date().toISOString(),
            ultimoAcceso: null,
            permisos,
            cambiarPassword: true
        };
        
        administradores.push(nuevoAdmin);
        
        registrarLog('crear', `Nuevo administrador ${nombre} creado`);
        showNotification(`Administrador creado. Contraseña temporal: ${password}`, 'success');
    }
    
    renderAdministradores();
    actualizarEstadisticas();
    closeModal('modalAdmin');
    document.getElementById('formAdmin').reset();
}

// Toggle estado admin
function toggleEstadoAdmin(id) {
    if (currentUser.rol !== 'super_admin') {
        showNotification('No tiene permisos para esta acción', 'error');
        return;
    }
    
    const admin = administradores.find(a => a.id === id);
    if (!admin) return;
    
    admin.estado = admin.estado === 'activo' ? 'inactivo' : 'activo';
    
    registrarLog('editar', `Estado de ${admin.nombre} cambiado a ${admin.estado}`);
    renderAdministradores();
    actualizarEstadisticas();
    showNotification(`Administrador ${admin.estado === 'activo' ? 'activado' : 'desactivado'}`, 'success');
}

// Eliminar admin
function eliminarAdmin(id) {
    if (currentUser.rol !== 'super_admin') {
        showNotification('No tiene permisos para esta acción', 'error');
        return;
    }
    
    const admin = administradores.find(a => a.id === id);
    if (!admin) return;
    
    if (confirm(`¿Está seguro de eliminar al administrador ${admin.nombre}?`)) {
        const index = administradores.findIndex(a => a.id === id);
        administradores.splice(index, 1);
        
        registrarLog('eliminar', `Administrador ${admin.nombre} eliminado`);
        renderAdministradores();
        actualizarEstadisticas();
        showNotification('Administrador eliminado', 'success');
    }
}

// Reset password
function resetPassword(id) {
    const admin = administradores.find(a => a.id === id);
    if (!admin) return;
    
    userToReset = admin;
    document.getElementById('resetUserName').textContent = admin.nombre;
    openModal('modalResetPassword');
}

// Confirmar reset password
function confirmarResetPassword() {
    if (!userToReset) return;
    
    const tempPassword = 'Temp' + Math.random().toString(36).substr(2, 8);
    
    userToReset.cambiarPassword = true;
    
    registrarLog('editar', `Contraseña de ${userToReset.nombre} reseteada`);
    showNotification(`Contraseña temporal generada: ${tempPassword}`, 'success');
    
    closeModal('modalResetPassword');
    userToReset = null;
}

// Ver detalles mensajero
function verDetallesMensajero(id) {
    const mensajero = mensajeros.find(m => m.id === id);
    if (!mensajero) return;
    
    const html = `
        <div class="mensajero-detail-section">
            <h3>Información Personal</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Nombre</div>
                    <div class="detail-value">${mensajero.nombre}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Teléfono</div>
                    <div class="detail-value">${mensajero.telefono}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value">${mensajero.email}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Fecha Registro</div>
                    <div class="detail-value">${formatDate(mensajero.fechaRegistro)}</div>
                </div>
            </div>
        </div>

        <div class="mensajero-detail-section">
            <h3>Estado Actual</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Estado</div>
                    <div class="detail-value"><span class="status-badge status-${mensajero.estado}">${formatEstadoMensajero(mensajero.estado)}</span></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Ubicación Actual</div>
                    <div class="detail-value">${mensajero.ubicacionActual}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Paquetes Asignados</div>
                    <div class="detail-value">${mensajero.paquetesAsignados}</div>
                </div>
            </div>
        </div>

        <div class="mensajero-detail-section">
            <h3>Rendimiento del Día</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Entregas Completadas</div>
                    <div class="detail-value">${mensajero.entregasHoy}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Porcentaje de Éxito</div>
                    <div class="detail-value">${mensajero.rendimiento}%</div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('detallesMensajero').innerHTML = html;
    openModal('modalMensajero');
}

// Toggle estado mensajero
function toggleEstadoMensajero(id) {
    const mensajero = mensajeros.find(m => m.id === id);
    if (!mensajero) return;
    
    if (mensajero.estado === 'activo' || mensajero.estado === 'en_ruta') {
        mensajero.estado = 'inactivo';
    } else {
        mensajero.estado = 'activo';
    }
    
    registrarLog('editar', `Estado de mensajero ${mensajero.nombre} cambiado`);
    renderMensajeros();
    actualizarEstadisticas();
    showNotification('Estado actualizado', 'success');
}

// Eliminar mensajero
function eliminarMensajero(id) {
    if (currentUser.rol !== 'super_admin') {
        showNotification('No tiene permisos para esta acción', 'error');
        return;
    }
    
    const mensajero = mensajeros.find(m => m.id === id);
    if (!mensajero) return;
    
    if (confirm(`¿Está seguro de eliminar al mensajero ${mensajero.nombre}?`)) {
        const index = mensajeros.findIndex(m => m.id === id);
        mensajeros.splice(index, 1);
        
        registrarLog('eliminar', `Mensajero ${mensajero.nombre} eliminado`);
        renderMensajeros();
        actualizarEstadisticas();
        showNotification('Mensajero eliminado', 'success');
    }
}

// Generar reporte mensajeros
function generarReporteMensajeros() {
    alert('Funcionalidad de reporte en desarrollo');
}

// Actualizar permisos según rol
function actualizarPermisosSegunRol() {
    const rol = document.getElementById('adminRol').value;
    const permisos = PERMISOS_POR_ROL[rol] || [];
    
    document.querySelectorAll('input[name="permiso"]').forEach(checkbox => {
        if (permisos.includes('todos')) {
            checkbox.checked = true;
            checkbox.disabled = true;
        } else {
            checkbox.checked = permisos.includes(checkbox.value);
            checkbox.disabled = false;
        }
    });
}

// Previsualizar foto
function previsualizarFoto(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    }
}

// Filtrar administradores
function filtrarAdmins() {
    const search = document.getElementById('searchAdmin').value.toLowerCase();
    const rows = document.querySelectorAll('#tablaAdminsBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
}

// Filtrar mensajeros
function filtrarMensajeros() {
    const search = document.getElementById('searchMensajero').value.toLowerCase();
    const estado = document.getElementById('filtroEstadoMensajero').value;
    const rows = document.querySelectorAll('#tablaMensajerosBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const estadoRow = row.querySelector('.status-badge')?.classList.toString() || '';
        
        const matchSearch = text.includes(search);
        const matchEstado = !estado || estadoRow.includes(estado);
        
        row.style.display = matchSearch && matchEstado ? '' : 'none';
    });
}

// Filtrar logs
function filtrarLogs() {
    renderLogs();
}

// Cargar usuarios en filtros
function cargarUsuariosEnFiltros() {
    const select = document.getElementById('filtroUsuarioLog');
    select.innerHTML = '<option value="">Todos los usuarios</option>' +
        administradores.map(a => `<option value="${a.id}">${a.nombre}</option>`).join('');
}

// Actualizar estadísticas
function actualizarEstadisticas() {
    // Administradores
    document.getElementById('totalAdmins').textContent = administradores.length;
    document.getElementById('adminsActivos').textContent = administradores.filter(a => a.estado === 'activo').length;
    document.getElementById('adminsInactivos').textContent = administradores.filter(a => a.estado === 'inactivo').length;
    document.getElementById('superAdmins').textContent = administradores.filter(a => a.rol === 'super_admin').length;
    
    // Mensajeros
    document.getElementById('totalMensajeros').textContent = mensajeros.length;
    document.getElementById('mensajerosEnRuta').textContent = mensajeros.filter(m => m.estado === 'en_ruta').length;
    document.getElementById('paquetesAsignadosHoy').textContent = mensajeros.reduce((sum, m) => sum + m.paquetesAsignados, 0);
    document.getElementById('entregasHoy').textContent = mensajeros.reduce((sum, m) => sum + m.entregasHoy, 0);
    
    // Solicitudes
    document.getElementById('countSolicitudes').textContent = solicitudesPendientes.length;
}

// Registrar log
function registrarLog(accion, descripcion) {
    logs.unshift({
        id: logs.length + 1,
        usuario: currentUser.nombre || 'Super Admin',
        usuarioId: currentUser.id || 1,
        accion,
        descripcion,
        fecha: new Date().toISOString(),
        ip: '192.168.1.100'
    });
}

// Formatear rol
function formatRol(rol) {
    const roles = {
        super_admin: 'Super Admin',
        admin_operativo: 'Admin Operativo',
        admin_reportes: 'Admin Reportes',
        admin_mensajeros: 'Admin Mensajeros'
    };
    return roles[rol] || rol;
}

// Formatear estado mensajero
function formatEstadoMensajero(estado) {
    const estados = {
        activo: 'Activo',
        en_ruta: 'En Ruta',
        descanso: 'Descanso',
        inactivo: 'Inactivo'
    };
    return estados[estado] || estado;
}

// Formatear fecha
function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('es-ES');
}

// Formatear fecha y hora
function formatDateTime(dateStr) {
    return new Date(dateStr).toLocaleString('es-ES');
}

// Formatear tiempo relativo
function formatRelativeTime(dateStr) {
    const diff = Date.now() - new Date(dateStr);
    const mins = Math.floor(diff / 60000);
    const hours = Math.floor(mins / 60);
    const days = Math.floor(hours / 24);
    
    if (mins < 60) return `Hace ${mins} min`;
    if (hours < 24) return `Hace ${hours}h`;
    return `Hace ${days} días`;
}

// Abrir modal
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

// Cerrar modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Mostrar notificación
function showNotification(message, type = 'info') {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        info: '#17a2b8',
        warning: '#ffc107'
    };
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        max-width: 400px;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 4000);
}