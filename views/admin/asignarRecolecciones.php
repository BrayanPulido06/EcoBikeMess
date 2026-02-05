<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Recolecciones - Sistema de Mensajería</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/asignarRecolecciones.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <div class="container" style="margin-left: 250px; margin-top: 60px;">
        <!-- Header -->
        <header class="page-header">
            <div>
                <h1>📦 Gestión de Recolecciones</h1>
                <p>Asignar y administrar recolecciones de paquetes</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" id="btnReportes">📊 Reportes</button>
                <button class="btn btn-primary" id="btnNuevaRecoleccion">+ Nueva Recolección</button>
            </div>
        </header>

        <!-- Alertas -->
        <div class="alerts-section" id="alertsSection"></div>

        <!-- Modal Formulario Nueva Recolección -->
        <div class="modal" id="modalNuevaRecoleccion">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Nueva Recolección</h2>
                    <button class="btn-close" id="btnCerrarModal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formNuevaRecoleccion">
                        <div class="form-grid">
                            <!-- Sección: Información del Cliente -->
                            <div class="form-section">
                                <h3>Información del Cliente</h3>
                                
                                <div class="form-group">
                                    <label for="cliente">Cliente Solicitante *</label>
                                    <select id="cliente" name="cliente" required>
                                        <option value="">Seleccione un cliente</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="contacto">Nombre de Contacto en Origen *</label>
                                    <input type="text" id="contacto" name="contacto" required>
                                </div>

                                <div class="form-group">
                                    <label for="telefono">Teléfono de Contacto *</label>
                                    <input type="tel" id="telefono" name="telefono" required>
                                </div>
                            </div>

                            <!-- Sección: Ubicación -->
                            <div class="form-section">
                                <h3>Ubicación de Recolección</h3>
                                
                                <div class="form-group">
                                    <label for="direccion">Dirección Completa *</label>
                                    <textarea id="direccion" name="direccion" rows="2" required></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="latitud">Latitud *</label>
                                        <input type="text" id="latitud" name="latitud" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="longitud">Longitud *</label>
                                        <input type="text" id="longitud" name="longitud" readonly>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-secondary btn-block" id="btnObtenerUbicacion">
                                    📍 Obtener Ubicación Actual
                                </button>

                                <div id="mapContainer" class="map-container"></div>
                            </div>

                            <!-- Sección: Detalles del Paquete -->
                            <div class="form-section">
                                <h3>Detalles de los Paquetes</h3>
                                
                                <div class="form-group">
                                    <label for="descripcion">Descripción de Paquetes *</label>
                                    <textarea id="descripcion" name="descripcion" rows="3" required placeholder="Ej: 3 cajas de documentos, 1 sobre manila"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="cantidad">Cantidad Estimada de Paquetes *</label>
                                    <input type="number" id="cantidad" name="cantidad" min="1" required>
                                </div>
                            </div>

                            <!-- Sección: Programación -->
                            <div class="form-section">
                                <h3>Programación y Prioridad</h3>
                                
                                <div class="form-group">
                                    <label for="fechaRecoleccion">Fecha de Recolección *</label>
                                    <input type="date" id="fechaRecoleccion" name="fechaRecoleccion" required>
                                </div>

                                <div class="form-group">
                                    <label for="horario">Horario Preferido *</label>
                                    <select id="horario" name="horario" required>
                                        <option value="">Seleccione horario</option>
                                        <option value="08:00-10:00">08:00 - 10:00</option>
                                        <option value="10:00-12:00">10:00 - 12:00</option>
                                        <option value="12:00-14:00">12:00 - 14:00</option>
                                        <option value="14:00-16:00">14:00 - 16:00</option>
                                        <option value="16:00-18:00">16:00 - 18:00</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="prioridad">Prioridad *</label>
                                    <select id="prioridad" name="prioridad" required>
                                        <option value="">Seleccione prioridad</option>
                                        <option value="urgente">🔴 Urgente</option>
                                        <option value="normal">🟡 Normal</option>
                                        <option value="programada">🟢 Programada</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones Especiales</label>
                                    <textarea id="observaciones" name="observaciones" rows="3" placeholder="Instrucciones adicionales, restricciones de acceso, etc."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Asignación de Mensajero -->
                        <div class="form-section">
                            <h3>Asignación de Mensajero</h3>
                            <div id="mensajerosDisponibles" class="mensajeros-grid"></div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCancelarForm">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Crear Recolección</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Cancelar Recolección -->
        <div class="modal" id="modalCancelar">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Cancelar Recolección</h2>
                    <button class="btn-close" id="btnCerrarModalCancelar">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formCancelar">
                        <div class="form-group">
                            <label for="motivoCancelacion">Motivo de Cancelación *</label>
                            <textarea id="motivoCancelacion" name="motivoCancelacion" rows="4" required placeholder="Describa el motivo de la cancelación..."></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCerrarCancelar">Cerrar</button>
                            <button type="submit" class="btn btn-danger">Confirmar Cancelación</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="filters-section">
            <div class="filters-grid">
                <div class="form-group">
                    <input type="text" id="busqueda" placeholder="🔍 Buscar por cliente, dirección o mensajero...">
                </div>
                <div class="form-group">
                    <select id="filtroEstado">
                        <option value="">Todos los estados</option>
                        <option value="asignada">Asignada</option>
                        <option value="en_curso">En Curso</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <div class="form-group">
                    <select id="filtroPrioridad">
                        <option value="">Todas las prioridades</option>
                        <option value="urgente">Urgente</option>
                        <option value="normal">Normal</option>
                        <option value="programada">Programada</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="date" id="filtroFecha">
                </div>
            </div>
        </div>

        <!-- Tabla de Recolecciones -->
        <div class="table-section">
            <div class="table-header">
                <h2>Recolecciones Programadas</h2>
                <div class="table-stats">
                    <span class="stat-badge">Total: <strong id="totalRecolecciones">0</strong></span>
                    <span class="stat-badge">Pendientes: <strong id="pendientes">0</strong></span>
                    <span class="stat-badge">Completadas: <strong id="completadas">0</strong></span>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tablaRecolecciones">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Dirección</th>
                            <th>Contacto</th>
                            <th>Mensajero</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Programada</th>
                            <th>Completada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaRecoleccionesBody">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../../public/js/asignarRecolecciones.js"></script>
</body>
</html>