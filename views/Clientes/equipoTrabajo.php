<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipo de Trabajo | EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/equipoTrabajo.css">
    <!-- Estilos básicos para el modal y layout -->
    <style>
        .main-content { margin-left: 250px; padding: 20px; margin-top: 60px; }
        .btn-primary { background: #009688; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .btn-sm { padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; color: white; }
        .btn-danger { background: #e74c3c; }
        .btn-success { background: #2ecc71; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 9999; }
        .modal-content { background: white; padding: 2rem; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .permissions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; }
        .checkbox-label { display: flex; align-items: center; gap: 8px; font-weight: normal; font-size: 0.9rem; }
        
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include '../layouts/clienteSidebar.php'; ?>
    <?php include '../layouts/clienteNavbar.php'; ?>

    <div class="main-content">
        <div class="team-header">
            <div>
                <h1>Equipo de Trabajo</h1>
                <p>Gestiona los usuarios que colaboran en tu tienda</p>
            </div>
            <button class="btn-primary" onclick="abrirModal()">+ Nuevo Miembro</button>
        </div>

        <div class="team-stats">
            <div class="stat-card">
                <div class="stat-number" id="totalMiembros">0</div>
                <div class="stat-label">Total Miembros</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="miembrosActivos">0</div>
                <div class="stat-label">Activos</div>
            </div>
        </div>

        <h2 style="margin-top: 2rem;">Colaboradores</h2>
        <div class="team-grid" id="listaColaboradores">
            <!-- Se llena con JS -->
            <p>Cargando equipo...</p>
        </div>

        <h2>Actividad Reciente</h2>
        <div class="activity-feed" id="listaHistorial">
            <!-- Se llena con JS -->
        </div>
    </div>

    <!-- Modal Crear Colaborador -->
    <div id="modalCrear" class="modal">
        <div class="modal-content">
            <h2>Agregar Nuevo Miembro</h2>
            <form id="formCrearColaborador">
                <div class="row" style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label>Nombres</label>
                        <input type="text" name="nombres" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Apellidos</label>
                        <input type="text" name="apellidos" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" required>
                </div>

                <div class="row" style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label>Cargo</label>
                        <input type="text" name="cargo" placeholder="Ej: Vendedor, Logística" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Contraseña Temporal</label>
                        <input type="password" name="password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Permisos de Acceso</label>
                    <div class="permissions-grid">
                        <label class="checkbox-label">
                            <input type="checkbox" name="perm_paquetes" checked> Crear Paquetes
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="perm_facturas"> Ver Facturas
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="perm_comprobantes" checked> Ver Comprobantes
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="perm_recolecciones" checked> Gestionar Recolecciones
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="perm_reportes"> Ver Reportes
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="perm_perfil"> Editar Perfil Tienda
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="perm_colaboradores"> Gestionar Equipo
                        </label>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" class="btn-sm btn-danger" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../public/js/equipoTrabajo.js"></script>
</body>
</html>