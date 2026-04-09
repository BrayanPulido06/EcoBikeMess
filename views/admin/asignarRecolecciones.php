<?php
session_start();
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin' && ($_SESSION['user_role'] ?? '') !== 'administrador')) {
    header("Location: ../login.php?error=Debes iniciar sesión.");
    exit();
}
require_once '../../models/asignarRecoleccionesModels.php';

$model = new AsignarRecoleccionesModel();
// Por ahora, cargamos todas las recolecciones. El JS puede encargarse de filtrar dinámicamente.
$recolecciones = $model->listarRecolecciones([]);
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
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        /* Estilos para insignias de estado y prioridad */
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            color: white;
            display: inline-block;
            min-width: 90px;
            text-align: center;
            text-transform: capitalize;
        }
        .badge.estado-pendiente { background-color: #ffc107; color: #333; } /* Amarillo */
        .badge.estado-asignado { background-color: #007bff; }    /* Azul */
        .badge.estado-asignada { background-color: #007bff; }    /* Azul */
        .badge.estado-en_transito { background-color: #007bff; } /* Azul */
        .badge.estado-en_ruta { background-color: #007bff; }     /* Azul */
        .badge.estado-completada { background-color: #28a745; }  /* Verde para Recolección */
        .badge.estado-entregado { background-color: #28a745; }   /* Verde */

        .badge.prioridad-urgente { background-color: #dc3545; }
        .badge.prioridad-normal { background-color: #ffc107; color: #333; }
        .badge.prioridad-programada { background-color: #6c757d; }

        .actions { display: flex; gap: 5px; justify-content: center; }

        .prioridad-verde { border-left: 4px solid #28a745; }
        .prioridad-amarillo { border-left: 4px solid #ffc107; }
        .prioridad-rojo { border-left: 4px solid #dc3545; }
        
        /* Estilos de botones iguales a paquetesAdmin */
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; line-height: 1.5; border-radius: 0.2rem; cursor: pointer; border: 1px solid transparent; transition: all 0.2s; }
        .btn-info { color: #fff; background-color: #17a2b8; border-color: #17a2b8; }
        .btn-warning { color: #212529; background-color: #ffc107; border-color: #ffc107; }
        .btn-danger { color: #fff; background-color: #dc3545; border-color: #dc3545; }
        .btn-info:hover { background-color: #138496; border-color: #117a8b; }
        .btn-warning:hover { background-color: #e0a800; border-color: #d39e00; }
        .btn-danger:hover { background-color: #c82333; border-color: #bd2130; }

        /* Estilos para el Modal de Detalles (Grid) */
        .detalle-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .detalle-item { background: #f8f9fa; padding: 10px; border-radius: 5px; }
        .detalle-label { font-size: 0.85em; color: #6c757d; margin-bottom: 5px; }
        .detalle-value { font-weight: 600; color: #333; }

        /* Estilos para la lista de mensajeros en el modal */
        .mensajeros-list-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }
        .mensajero-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        .mensajero-item:hover { background-color: #f8f9fa; }
        .mensajero-item.selected { background-color: #e3f2fd; border-left: 4px solid #17a2b8; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .mb-2 { margin-bottom: 0.5rem; }

        /* Estilos para centrar el Modal (Igual a paquetesAdmin) */
        .modal {
            display: none; /* Oculto por defecto */
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); /* Fondo oscuro */
            justify-content: center; /* Centrar horizontalmente */
            align-items: center; /* Centrar verticalmente */
        }
    </style>
</head>
<body>
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <div class="container app-shell">
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

        <!-- Modal Detalles (Nuevo, igual a paquetesAdmin) -->
        <div class="modal" id="modalDetalles" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>📦 Detalles de Recolección</h2>
                    <button class="btn-close" onclick="document.getElementById('modalDetalles').style.display='none'">&times;</button>
                </div>
                <div class="modal-body" id="detallesRecoleccionBody">
                    <p style="text-align:center">Cargando información...</p>
                </div>
            </div>
        </div>

        <!-- Modal Asignación Rápida (Nuevo) -->
        <div class="modal" id="modalAsignarRapido" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Asignar Mensajero</h2>
                    <button class="btn-close" id="btnCerrarAsignar">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="infoRecoleccionAsignar" class="info-recoleccion-asignar" style="margin-bottom: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #eee;">
                        <!-- La información de la recolección se cargará aquí -->
                    </div>
                    <form id="formAsignarRapido">
                        <input type="hidden" id="idsPaquetesHidden" name="ids_paquetes">
                        <input type="hidden" id="mensajeroIdHidden" name="mensajero_id" required>
                        
                        <div class="form-group">
                            <label>Buscar Mensajero:</label>
                            <input type="text" id="buscarMensajeroInput" class="form-control mb-2" placeholder="Escriba nombre del mensajero...">
                            <div id="listaMensajeros" class="mensajeros-list-container"></div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Confirmar Asignación</button>
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
                            <th>Dirección Origen</th>
                            <th>Cliente</th>
                            <th>Mensajero</th>
                            <th>Estado</th>
                            <th>Cantidad</th>
                            <th>Guías</th>
                            <th>Fecha Solicitud</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaRecoleccionesBody">
                        <?php if (empty($recolecciones)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">No hay recolecciones pendientes.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recolecciones as $rec): ?>
                                <tr class="prioridad-<?php echo htmlspecialchars($rec['color_prioridad']); ?>">
                                    <td><?php echo htmlspecialchars($rec['direccion_origen']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['cliente_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['mensajero_nombre']); ?></td>
                                    <td>
                                        <span class="badge estado-<?php echo htmlspecialchars($rec['estado']); ?>">
                                            <?php
                                                if ($rec['estado'] === 'entregado' || $rec['estado'] === 'completada') {
                                                    echo 'Finalizada';
                                                } else {
                                                    echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rec['estado'])));
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info" style="font-size: 1em; background-color: #17a2b8;">
                                            <?php echo htmlspecialchars($rec['cantidad']); ?> Paquetes
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(mb_strimwidth($rec['guias'], 0, 50, "...")); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($rec['fecha_creacion']))); ?></td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn btn-sm btn-info" title="Ver Paquetes" onclick="verDetallesPaquetes('<?php echo $rec['ids']; ?>')">👁️</button>
                                            <?php if (!in_array($rec['estado'], ['entregado', 'completada', 'cancelado'])): ?>
                                                <?php if ($rec['estado'] === 'pendiente'): ?>
                                                    <button class="btn btn-sm btn-warning" title="Asignar Recolección" onclick="asignarRecoleccion('<?php echo $rec['ids']; ?>', '<?php echo htmlspecialchars($rec['direccion_origen'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($rec['cliente_nombre'], ENT_QUOTES); ?>')">🚴</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" title="Reasignar" onclick="asignarRecoleccion('<?php echo $rec['ids']; ?>', '<?php echo htmlspecialchars($rec['direccion_origen'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($rec['cliente_nombre'], ENT_QUOTES); ?>')">🔄</button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger" title="Cancelar" onclick="cancelarRecoleccion('<?php echo $rec['ids']; ?>')">🗑️</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../../public/js/asignarRecolecciones.js"></script>
</body>
</html>
