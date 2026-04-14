<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    header('Location: ../login.php?error=Debes iniciar sesión.');
    exit;
}
?>
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
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/recoleccionesMensajero.css?v=20260411-1">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <style>
        /* RESET DE SCROLL AGRESIVO */
        html, body {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            height: auto !important;
            min-height: 100vh !important;
            /* Crucial: permite el scroll táctil nativo con un dedo */
            touch-action: auto !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior-y: auto !important;
        }

        /* Forzar al contenedor principal a ser elástico */
        .main-content {
            position: relative !important;
            overflow: visible !important;
            display: block !important;
            height: auto !important;
            min-height: 100% !important;
            padding-bottom: 120px !important;
            touch-action: auto !important;
        }

        /* Asegurar que la lista no capture y bloquee el scroll */
        .lista-recolecciones {
            overflow: visible !important;
            height: auto !important;
            touch-action: auto !important;
        }

        /* Estilos para que Detalle y Formularios funcionen como Modales a pantalla completa */
        .vista-detalle, .vista-formulario {
            position: fixed !important;
            top: 0;
            left: 0;
            width: 100vw !important;
            height: 100vh !important;
            background-color: #f4f7f6 !important;
            z-index: 5000 !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            touch-action: pan-y !important;
            padding: 0 !important;
            display: block;
        }

        .oculto {
            display: none !important;
        }
    </style>
    
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
            <h1><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:35px;height:35px;vertical-align:middle;margin-right:6px;">EcoBikeMess</h1>
            <p class="user-name" id="mensajeroNombre">Mis Recolecciones</p>
        </div>
    </header>

    <!-- Menu Lateral -->
    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <!-- Vista Principal: Lista de Recolecciones -->
    <main id="vistaLista" class="vista-activa main-content">
        <div class="session-status">
            <div class="status-indicator online">
                <span class="status-dot"></span>
                <span class="status-text">Recolecciones Activas</span>
            </div>
            <div class="session-time">
                <span class="time-icon">📥</span>
                <span>Rutas asignadas</span>
            </div>
        </div>

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
            <section class="seccion-detalle" id="seccionUbicacion">
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
                        <span class="info-label">Dirección:</span>
                        <span id="detalleDireccion"></span>
                    </div>
                </div>
            </section>

            <!-- Ubicación -->
            <section class="seccion-detalle" id="seccionContacto">
                <h3>📍 Ubicación de Recolección</h3>
                <div class="ubicacion-info">
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
                        <span class="info-label">Tienda:</span>
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

            <!-- Paquetes asignados -->
            <section class="seccion-detalle oculto" id="seccionPaquetesAsignados">
                <h3>📌 Paquetes asignados</h3>
                <div class="info-item">
                    <span class="info-label">Total a recoger:</span>
                    <span id="detalleTotalPaquetes" class="cantidad-grande"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Recogidos:</span>
                    <span id="detalleCantidadRecogida" class="cantidad-grande"></span>
                </div>
                <div id="detalleListaPaquetes" class="lista-paquetes-recoleccion"></div>
            </section>

            <!-- Evidencia -->
            <section class="seccion-detalle oculto" id="seccionEvidencia">
                <h3>📸 Evidencia de Recolección</h3>
                <div id="detalleFotoEvidencia" class="lista-paquetes-recoleccion"></div>
            </section>

            <!-- Acciones -->
            <div class="acciones-detalle">
                <button id="btnLleguePunto" class="btn-recibido btn-grande oculto">
                    📦 Realizar Recolección
                </button>
            </div>
        </div>
    </div>

    <!-- Vista Formulario de Recolección -->
    <div id="vistaFormulario" class="vista-formulario oculto">
        <div class="formulario-header">
            <button type="button" id="btnVolverFormulario" class="btn-volver formulario-volver">← Volver</button>
            <h2>📝 Registrar Recolección</h2>
            <p id="formNumeroOrden">Orden #</p>
            <p id="formFechaAsignacion" class="fecha-asignacion"></p>
        </div>

        <form id="formRecoleccion" class="formulario-recoleccion">
            <div class="form-group">
                <label>Dirección de Recolección</label>
                <input type="text" id="formDireccionRecoleccion" class="input-readonly" readonly>
            </div>

            <div class="form-group">
                <label>Nombre de la tienda</label>
                <input type="text" id="formNombreContacto" class="input-readonly" readonly>
            </div>

            <div class="form-group">
                <label>Teléfono de contacto</label>
                <div class="telefono-container">
                    <input type="text" id="formTelefonoContacto" class="input-readonly" readonly>
                    <button type="button" id="btnLlamarForm" class="btn-llamar-mini">
                        📞 Llamar
                    </button>
                    <button type="button" id="btnCopiarTelefonoForm" class="btn-copiar-mini">
                        📋 Copiar
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>Total de paquetes a recoger</label>
                <div id="formTotalPaquetes" class="cantidad-grande">0</div>
            </div>

            <div class="form-group">
                <label>Guías a recoger</label>
                <div id="formListaGuias" class="lista-paquetes-recoleccion"></div>
            </div>

            <div class="acciones-detalle">
            </div>
            <!-- Fotos -->
            <div class="form-group">
                <label class="obligatorio">Foto de los paquetes</label>
                <div class="fotos-container">
                    <input type="file" id="inputFotos" accept="image/*" capture="environment" multiple style="display: none;">
                    <button type="button" id="btnTomarFoto" class="btn-foto">
                        📷 Tomar Foto
                    </button>
                    <div id="previsualizacionFotos" class="previsualizacion-fotos"></div>
                </div>
                <small class="ayuda-texto">📸 Obligatorio: se debe colocar foto de los paquetes</small>
            </div>

            <!-- Cantidad Real de Paquetes -->
            <div class="form-group">
                <label for="cantidadReal" class="obligatorio">Cantidad Real de Paquetes Recibidos</label>
                <div class="cantidad-control">
                    <button type="button" class="btn-cantidad" data-accion="decrementar">-</button>
                    <input type="number" id="cantidadReal" name="cantidadReal" value="0" min="0" required>
                    <button type="button" class="btn-cantidad" data-accion="incrementar">+</button>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" rows="4" 
                          placeholder="Agrega una descripción si se necesita..."></textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="form-acciones">
                <button type="button" id="btnCancelarFormulario" class="btn-secundario">
                    Volver
                </button>
                <button type="submit" class="btn-exito btn-grande">
                    ✓ Recolección Exitosa
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

    <script src="../../public/js/mensajeroLayout.js?v=20260411-1"></script>
    <script src="../../public/js/recoleccionesMensajero.js?v=20260411-1"></script>

    <script>
        // Lógica de navegación para los botones "Volver" en Recolecciones
        document.addEventListener('DOMContentLoaded', function() {
            const vistaLista = document.getElementById('vistaLista');
            const modales = ['vistaDetalle', 'vistaFormulario'];
            const botonesVolver = ['btnVolver', 'btnVolverFormulario', 'btnCancelarFormulario'];

            botonesVolver.forEach(id => {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.addEventListener('click', function() {
                        modales.forEach(mId => document.getElementById(mId).classList.add('oculto'));
                        vistaLista.classList.remove('oculto');
                    });
                }
            });
        });
    </script>
</body>
</html>
