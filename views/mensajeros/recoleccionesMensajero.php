<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#2563eb">
    <title>Recolecciones - Sistema de Mensajería</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/recoleccionesMensajero.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
    
    <!-- PWA Icons -->
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>📦</text></svg>">
</head>
<body>
    <!-- Header Móvil (Diseño Unificado) -->
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1>🚴 EcoBikeMess</h1>
            <p class="user-name" id="mensajeroNombre">Mis Recolecciones</p>
        </div>
        <button class="notif-btn" id="notifBtn">
            <span class="notif-icon">🔔</span>
            <span class="notif-badge">3</span>
        </button>
    </header>

    <!-- Menu Lateral -->
    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <!-- Vista Principal: Lista de Recolecciones -->
    <main id="vistaLista" class="vista-activa main-content" style="padding-top: 80px;">
        <div class="filtros">
            <button class="filtro-btn activo" data-filtro="todas">Todas</button>
            <button class="filtro-btn" data-filtro="pendiente">Pendientes</button>
            <button class="filtro-btn" data-filtro="en_curso">En Curso</button>
            <button class="filtro-btn" data-filtro="completada">Completadas</button>
        </div>

        <div id="listaRecolecciones" class="lista-recolecciones">
            <!-- Las recolecciones se cargarán dinámicamente aquí -->
        </div>
    </main>

    <!-- Vista Detalle de Recolección -->
    <div id="vistaDetalle" class="vista-detalle oculto">
        <div class="detalle-header">
            <button id="btnVolver" class="btn-volver">← Volver</button>
            <h2 id="detalleNumeroOrden">Orden #</h2>
        </div>

        <div class="detalle-contenido">
            <!-- Información General -->
            <section class="seccion-detalle">
                <h3>📋 Información General</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Estado:</span>
                        <span id="detalleEstado" class="badge"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Prioridad:</span>
                        <span id="detallePrioridad" class="badge"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Asignado:</span>
                        <span id="detalleFechaAsignacion"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Distancia:</span>
                        <span id="detalleDistancia"></span>
                    </div>
                </div>
            </section>

            <!-- Ubicación -->
            <section class="seccion-detalle">
                <h3>📍 Ubicación de Recolección</h3>
                <div class="ubicacion-info">
                    <p id="detalleDireccion" class="direccion"></p>
                    <div class="coordenadas">
                        <span>Coordenadas: </span>
                        <span id="detalleCoordenadas"></span>
                    </div>
                    <button id="btnNavegar" class="btn-primario btn-full">
                        🗺️ Abrir en Navegación
                    </button>
                </div>
            </section>

            <!-- Contacto -->
            <section class="seccion-detalle">
                <h3>👤 Información de Contacto</h3>
                <div class="contacto-info">
                    <div class="info-item">
                        <span class="info-label">Nombre:</span>
                        <span id="detalleNombreContacto"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teléfono:</span>
                        <span id="detalleTelefono"></span>
                    </div>
                    <button id="btnLlamar" class="btn-secundario btn-full">
                        📞 Llamar al Contacto
                    </button>
                </div>
            </section>

            <!-- Detalles de Recolección -->
            <section class="seccion-detalle">
                <h3>📦 Detalles de Recolección</h3>
                <div class="info-item">
                    <span class="info-label">Cantidad de Paquetes:</span>
                    <span id="detalleCantidadPaquetes" class="cantidad-grande"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Horario Sugerido:</span>
                    <span id="detalleHorarioSugerido"></span>
                </div>
                <div class="info-item instrucciones">
                    <span class="info-label">Instrucciones Especiales:</span>
                    <p id="detalleInstrucciones"></p>
                </div>
            </section>

            <!-- Acciones -->
            <div class="acciones-detalle">
                <button id="btnIniciarRecoleccion" class="btn-primario btn-grande">
                    🚀 Iniciar Recolección
                </button>
                <button id="btnLleguePunto" class="btn-exito btn-grande oculto">
                    ✓ Llegué al Punto de Recolección
                </button>
                <button id="btnCancelar" class="btn-peligro">
                    ✕ Cancelar Recolección
                </button>
            </div>
        </div>
    </div>

    <!-- Vista Formulario de Recolección -->
    <div id="vistaFormulario" class="vista-formulario oculto">
        <div class="formulario-header">
            <h2>📝 Completar Recolección</h2>
            <p id="formNumeroOrden">Orden #</p>
        </div>

        <form id="formRecoleccion" class="formulario-recoleccion">
            <!-- Fotos -->
            <div class="form-group">
                <label class="obligatorio">Fotos de la Recolección</label>
                <div class="fotos-container">
                    <input type="file" id="inputFotos" accept="image/*" capture="environment" multiple style="display: none;">
                    <button type="button" id="btnTomarFoto" class="btn-foto">
                        📷 Tomar Foto con Cámara
                    </button>
                    <div id="previsualizacionFotos" class="previsualizacion-fotos"></div>
                </div>
                <small class="ayuda-texto">📸 Obligatorio: al menos una foto del lugar/persona</small>
            </div>

            <!-- Cantidad Real de Paquetes -->
            <div class="form-group">
                <label for="cantidadReal" class="obligatorio">Cantidad Real de Paquetes Recibidos</label>
                <div class="cantidad-control">
                    <button type="button" class="btn-cantidad" data-accion="decrementar">-</button>
                    <input type="number" id="cantidadReal" name="cantidadReal" value="0" min="0" required>
                    <button type="button" class="btn-cantidad" data-accion="incrementar">+</button>
                </div>
                <small id="cantidadEsperada" class="ayuda-texto"></small>
            </div>

            <!-- Alerta de Diferencia -->
            <div id="alertaDiferencia" class="alerta alerta-advertencia oculto">
                <p>⚠️ La cantidad recibida no coincide con la solicitada</p>
                <div class="form-group">
                    <label for="explicacionDiferencia" class="obligatorio">Explicación de la Diferencia</label>
                    <textarea id="explicacionDiferencia" name="explicacionDiferencia" rows="3" 
                              placeholder="Explique por qué hay diferencia en la cantidad..."></textarea>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="form-group">
                <label for="observaciones">Observaciones del Proceso</label>
                <textarea id="observaciones" name="observaciones" rows="4" 
                          placeholder="Ingrese cualquier observación relevante sobre la recolección..."></textarea>
            </div>

            <!-- Conformidad -->
            <div class="form-group">
                <label class="obligatorio">Conformidad de la Recolección</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="conformidad" value="si" required>
                        <span class="radio-custom"></span>
                        ✓ Sí, todo conforme
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="conformidad" value="no">
                        <span class="radio-custom"></span>
                        ✕ No, hay inconformidades
                    </label>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="form-acciones">
                <button type="button" id="btnCancelarFormulario" class="btn-secundario">
                    Cancelar
                </button>
                <button type="submit" class="btn-exito btn-grande">
                    ✓ Completar Recolección
                </button>
            </div>
        </form>
    </div>

    <!-- Modal de Confirmación -->
    <div id="modalConfirmacion" class="modal oculto">
        <div class="modal-contenido">
            <div class="modal-icono exito">✓</div>
            <h2>¡Recolección Completada!</h2>
            <p id="mensajeConfirmacion"></p>
            <button id="btnCerrarModal" class="btn-primario">Aceptar</button>
        </div>
    </div>

    <!-- Modal de Cancelación -->
    <div id="modalCancelacion" class="modal oculto">
        <div class="modal-contenido">
            <h2>Cancelar Recolección</h2>
            <div class="form-group">
                <label for="motivoCancelacion" class="obligatorio">Motivo de Cancelación</label>
                <textarea id="motivoCancelacion" rows="4" 
                          placeholder="Indique el motivo por el cual cancela esta recolección..."></textarea>
            </div>
            <div class="modal-acciones">
                <button id="btnCerrarCancelacion" class="btn-secundario">Volver</button>
                <button id="btnConfirmarCancelacion" class="btn-peligro">Confirmar Cancelación</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay oculto">
        <div class="spinner"></div>
        <p>Procesando...</p>
    </div>

    <script src="../../public/js/recoleccionesMensajero.js"></script>
</body>
</html>