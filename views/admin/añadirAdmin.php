<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema de Mensajería</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/añadirAdmin.css">
</head>
<body>
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <div class="container" style="margin-left: 250px; margin-top: 60px;">
        <!-- Header -->
        <header class="page-header">
            <div>
                <h1>👥 Gestión de Usuarios</h1>
                <p>Administradores y Mensajeros del Sistema</p>
            </div>
            <div class="header-actions">
                <span class="user-badge">Sesión: <strong id="currentUser">Super Admin</strong></span>
            </div>
        </header>

        <!-- Tabs Navigation -->
        <div class="tabs-container">
            <button class="tab-btn active" data-tab="administradores">
                👔 Administradores
            </button>
            <button class="tab-btn" data-tab="mensajeros">
                🚴 Mensajeros
            </button>
            <button class="tab-btn" data-tab="logs">
                📊 Logs de Actividad
            </button>
        </div>

        <!-- Tab: Administradores -->
        <div class="tab-content active" id="tab-administradores">
            <div class="section-actions">
                <button class="btn btn-primary" id="btnNuevoAdmin">
                    + Nuevo Administrador
                </button>
            </div>

            <!-- Estadísticas de Administradores -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <span class="stat-label">Total Administradores</span>
                        <span class="stat-value" id="totalAdmins">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-label">Activos</span>
                        <span class="stat-value" id="adminsActivos">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔒</div>
                    <div class="stat-info">
                        <span class="stat-label">Inactivos</span>
                        <span class="stat-value" id="adminsInactivos">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🌟</div>
                    <div class="stat-info">
                        <span class="stat-label">Super Admins</span>
                        <span class="stat-value" id="superAdmins">0</span>
                    </div>
                </div>
            </div>

            <!-- Tabla de Administradores -->
            <div class="table-section">
                <div class="table-header">
                    <h2>Administradores del Sistema</h2>
                    <div class="search-box">
                        <input type="text" id="searchAdmin" placeholder="🔍 Buscar administrador...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="tablaAdmins">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Último Acceso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaAdminsBody">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Mensajeros -->
        <div class="tab-content" id="tab-mensajeros">
            <div class="section-actions">
                <button class="btn btn-secondary" id="btnSolicitudesPendientes">
                    📋 Solicitudes Pendientes (<span id="countSolicitudes">0</span>)
                </button>
                <button class="btn btn-info" id="btnReporteMensajeros">
                    📊 Reporte de Productividad
                </button>
            </div>

            <!-- Estadísticas de Mensajeros -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🚴</div>
                    <div class="stat-info">
                        <span class="stat-label">Total Mensajeros</span>
                        <span class="stat-value" id="totalMensajeros">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🟢</div>
                    <div class="stat-info">
                        <span class="stat-label">En Ruta</span>
                        <span class="stat-value" id="mensajerosEnRuta">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <span class="stat-label">Paquetes Asignados Hoy</span>
                        <span class="stat-value" id="paquetesAsignadosHoy">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-label">Entregas Completadas Hoy</span>
                        <span class="stat-value" id="entregasHoy">0</span>
                    </div>
                </div>
            </div>

            <!-- Filtros de Mensajeros -->
            <div class="filters-section">
                <select id="filtroEstadoMensajero" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activos</option>
                    <option value="en_ruta">En Ruta</option>
                    <option value="inactivo">Inactivos</option>
                    <option value="descanso">En Descanso</option>
                </select>
                <input type="text" id="searchMensajero" placeholder="🔍 Buscar mensajero..." class="form-control">
            </div>

            <!-- Tabla de Mensajeros -->
            <div class="table-section">
                <div class="table-header">
                    <h2>Mensajeros Registrados</h2>
                </div>

                <div class="table-responsive">
                    <table id="tablaMensajeros">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Ubicación Actual</th>
                                <th>Paquetes Asignados</th>
                                <th>Entregas Hoy</th>
                                <th>Rendimiento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaMensajerosBody">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Logs -->
        <div class="tab-content" id="tab-logs">
            <div class="filters-section">
                <select id="filtroUsuarioLog" class="form-control">
                    <option value="">Todos los usuarios</option>
                </select>
                <select id="filtroAccionLog" class="form-control">
                    <option value="">Todas las acciones</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="crear">Crear</option>
                    <option value="editar">Editar</option>
                    <option value="eliminar">Eliminar</option>
                </select>
                <input type="date" id="filtroFechaLog" class="form-control">
            </div>

            <div class="logs-container" id="logsContainer">
                <!-- Se llena dinámicamente -->
            </div>
        </div>

        <!-- Modal: Nuevo/Editar Administrador -->
        <div class="modal" id="modalAdmin">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2 id="modalAdminTitle">Nuevo Administrador</h2>
                    <button class="btn-close" id="btnCerrarModalAdmin">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formAdmin">
                        <input type="hidden" id="adminId">
                        
                        <div class="form-section">
                            <h3>Información Personal</h3>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label>Foto de Perfil</label>
                                    <div class="photo-upload">
                                        <div class="photo-preview" id="photoPreview">
                                            <span>📷</span>
                                        </div>
                                        <input type="file" id="adminFoto" accept="image/*">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Nombre Completo *</label>
                                    <input type="text" id="adminNombre" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label>Correo Electrónico *</label>
                                    <input type="email" id="adminEmail" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label>Teléfono *</label>
                                    <input type="tel" id="adminTelefono" class="form-control" required>
                                </div>

                                <div class="form-group" id="passwordGroup">
                                    <label>Contraseña Temporal *</label>
                                    <input type="password" id="adminPassword" class="form-control">
                                    <small>El usuario deberá cambiarla en su primer acceso</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Rol y Permisos</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Rol de Administrador *</label>
                                    <select id="adminRol" class="form-control" required>
                                        <option value="">Seleccione un rol</option>
                                        <option value="super_admin">Super Administrador</option>
                                        <option value="admin_operativo">Administrador Operativo</option>
                                        <option value="admin_reportes">Administrador de Reportes</option>
                                        <option value="admin_mensajeros">Administrador de Mensajeros</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Estado de la Cuenta</label>
                                    <select id="adminEstado" class="form-control">
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="permisos-section" id="permisosSection">
                                <h4>Permisos Específicos</h4>
                                <div class="permisos-grid">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="permiso" value="crear_paquetes"> Crear Paquetes
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="permiso" value="editar_paquetes"> Editar Paquetes
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="permiso" value="eliminar_paquetes"> Eliminar Paquetes
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="permiso" value="asignar_mensajeros"> Asignar Mensajeros
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="permiso" value="ver_reportes"> Ver Reportes
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="permiso" value="gestionar_clientes"> Gestionar Clientes
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="permiso" value="gestionar_mensajeros"> Gestionar Mensajeros
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="permiso" value="facturacion"> Facturación
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCancelarAdmin">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Administrador</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Detalles del Mensajero -->
        <div class="modal" id="modalMensajero">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Detalles del Mensajero</h2>
                    <button class="btn-close" id="btnCerrarModalMensajero">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="detallesMensajero">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Solicitudes Pendientes -->
        <div class="modal" id="modalSolicitudes">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Solicitudes de Registro Pendientes</h2>
                    <button class="btn-close" id="btnCerrarModalSolicitudes">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="solicitudesList">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Resetear Contraseña -->
        <div class="modal" id="modalResetPassword">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Resetear Contraseña</h2>
                    <button class="btn-close" id="btnCerrarModalReset">&times;</button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea resetear la contraseña de <strong id="resetUserName"></strong>?</p>
                    <p>Se generará una contraseña temporal y se enviará por correo electrónico.</p>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="btnCancelarReset">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnConfirmarReset">Confirmar Reset</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="../../public/js/añadirAdmin.js"></script>
</body>
</html>