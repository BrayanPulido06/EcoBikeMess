<?php
require_once __DIR__ . '/../../includes/paths.php';
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    header('Location: ' . route_url('login', ['error' => 'Debes iniciar sesión.']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <base href="<?php echo htmlspecialchars(app_url('/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        window.APP_BASE_PATH = <?php echo json_encode(app_url(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#059669">
    <title>Mis Paquetes - Sistema de Mensajería</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest-paquetes.json">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>📦</text></svg>">
    
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/misPaquetesMensajeros.css?v=20260411-1">
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
            position: relative !important; /* Evita que fixed o absolute bloqueen el flujo */
            overflow: visible !important;
            display: block !important;
            height: auto !important;
            min-height: 100% !important;
            padding-bottom: 120px !important;
            touch-action: auto !important;
        }

        /* Asegurar que la lista no capture y bloquee el scroll */
        .lista-paquetes {
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
            background-color: #f4f7f6 !important; /* Fondo sólido para que no se vea la lista atrás */
            z-index: 5000 !important; /* Por encima de headers y menús */
            overflow-y: auto !important; /* Permite scroll si el formulario es largo */
            -webkit-overflow-scrolling: touch !important;
            touch-action: pan-y !important;
            padding: 0 !important; /* Ajustamos padding para que ocupe todo */
            display: block;
        }

        .oculto {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- Header Móvil (Diseño Unificado) -->
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:35px;height:35px;vertical-align:middle;margin-right:6px;">EcoBikeMess</h1>
            <p class="user-name">Mis Paquetes</p>
        </div>
    </header>

    <!-- Menu Lateral -->
    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <main id="vistaLista" class="vista-activa main-content">
        <!-- Filtros -->
        <div class="filtros-container">
            <div class="filtros">
                <button class="filtro-btn activo" data-filtro="todos">Todos</button>
                <button class="filtro-btn" data-filtro="pendiente">Pendientes</button>
                <button class="filtro-btn" data-filtro="aplazado">Aplazados</button>
            </div>
        </div>
    
        <!-- Controles Superiores (Movidos del Header antiguo) -->
        <div class="resumen-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; background: white; padding: 1rem; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <div class="contador-resumen">
                <span id="contadorPendientes" class="contador-badge pendiente">0</span>
                <span id="contadorEntregados" class="contador-badge entregado">0</span>
            </div>
            <div class="header-actions" style="display: flex; gap: 10px;">
                <button id="btnActualizar" class="btn-icon" title="Actualizar" style="background: #f8fdf9; color: #2d3e50; width: 40px; height: 40px; border-radius: 10px; border: 1px solid #e8f5f1;">
                    🔄
                </button>
            </div>
        </div>

        <div id="cierreJornadaSection" class="cierre-jornada oculto">
            <button id="btnGuardarCierreJornada" class="btn-guardar-cierre">
                Guardar cierre de jornada
            </button>
        </div>

        <div id="listaPaquetes" class="lista-paquetes">
            <!-- Los paquetes se cargarán dinámicamente aquí -->
        </div>
    </main>

    <!-- Vista Detalle de Paquete -->
    <div id="vistaDetalle" class="vista-detalle oculto">
        <div class="detalle-header">
            <button id="btnVolverDetalle" class="btn-volver">← Volver</button>
            <h2 id="detalleGuia">Guía #</h2>
        </div>

        <div class="detalle-contenido">
            <!-- Estado -->
            <section class="seccion-detalle">
                <div class="estado-badge-grande">
                    <span id="detalleEstadoBadge" class="badge-grande"></span>
                </div>
            </section>

            <!-- Información del Destinatario -->
            <section class="seccion-detalle">
                <h3>👤 Destinatario</h3>
                <div class="info-item">
                    <span class="info-label">Pedido a nombre de:</span>
                    <span id="detalleRemitente" class="info-valor"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Nombre:</span>
                    <span id="detalleNombreDestinatario" class="info-valor"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono:</span>
                    <div class="telefono-container">
                        <span id="detalleTelefono" class="info-valor"></span>
                        <button id="btnLlamarDetalle" class="btn-llamar-mini">
                            📞 Llamar
                        </button>
                    </div>
                </div>
            </section>

            <!-- Dirección de Entrega -->
            <section class="seccion-detalle">
                <h3>📍 Dirección de Entrega</h3>
                <p id="detalleDireccion" class="direccion-completa"></p>
                <button id="btnNavegar" class="btn-primario btn-full">
                    🗺️ Navegar a Dirección
                </button>
            </section>

            <!-- Información del Paquete -->
            <section class="seccion-detalle">
                <h3>📦 Información del Paquete</h3>
                <div class="info-item">
                    <span class="info-label">Instrucciones de Entrega:</span>
                    <span id="detalleContenido" class="info-valor"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Valor Declarado:</span>
                    <span id="detalleValorDeclarado" class="info-valor valor-destacado"></span>
                </div>
                <div class="info-item observaciones-especiales">
                    <span class="info-label">Observaciones Especiales:</span>
                    <p id="detalleObservaciones" class="observaciones-texto"></p>
                </div>
            </section>

            <!-- Botón de Entrega -->
            <div id="btnEntregarContainer" class="accion-entrega-container">
                <button id="btnEntregar" class="btn-entregar">
                    ✓ Entregar Paquete
                </button>
            </div>

            <!-- Información de Entrega (si ya fue entregado) -->
            <section id="infoEntregaRealizada" class="seccion-detalle oculto">
                <h3>✅ Información de Entrega</h3>
                <div class="info-item">
                    <span class="info-label">Recibió:</span>
                    <span id="entregaRecibio"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Parentesco/Cargo:</span>
                    <span id="entregaParentesco"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Documento:</span>
                    <span id="entregaDocumento"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Recaudo:</span>
                    <span id="entregaRecaudo" class="valor-destacado"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha/Hora:</span>
                    <span id="entregaFecha"></span>
                </div>
            </section>
        </div>
    </div>

    <!-- Vista Formulario de Entrega -->
    <div id="vistaFormularioEntrega" class="vista-formulario oculto">
        <div class="formulario-header">
            <button type="button" id="btnVolverEntrega" class="btn-volver formulario-volver">← Volver</button>
            <h2>✓ Entregar Paquete</h2>
            <p id="formGuia">Guía #</p>
        </div>

        <form id="formEntrega" class="formulario-entrega">
            <!-- Destinatario (solo lectura) -->
            <div class="form-group">
                <label for="nombreDestinatarioEntrega">Destinatario</label>
                <input type="text" id="nombreDestinatarioEntrega" name="nombreDestinatarioEntrega"
                       value="" readonly>
            </div>
            <!-- Nombre de Quien Recibe -->
            <div class="form-group">
                <label for="nombreRecibe" class="obligatorio">Nombre de Quien Recibe</label>
                <input type="text" id="nombreRecibe" name="nombreRecibe" 
                       placeholder="Nombre completo" required autocomplete="name">
            </div>

            <!-- Parentesco o Cargo -->
            <div class="form-group">
                <label for="parentesco" class="obligatorio">Parentesco o Cargo</label>
                <select id="parentesco" name="parentesco" required>
                    <option value="">Seleccionar...</option>
                    <option value="destinatario">Destinatario</option>
                    <option value="familiar">Familiar</option>
                    <option value="empleado">Empleado</option>
                    <option value="portero">Portero/Vigilante</option>
                    <option value="vecino">Vecino</option>
                    <option value="otro">Otro</option>
                </select>
                <input type="text" id="parentescoOtro" name="parentescoOtro" 
                       class="oculto" placeholder="Especificar...">
            </div>

            <!-- Número de Cédula o Placa -->
            <div class="form-group">
                <label for="documento" class="obligatorio">Número de Cédula o Placa</label>
                <input type="text" id="documento" name="documento" 
                       placeholder="CC, CE, Placa, etc." required>
            </div>

            <!-- Recaudo -->
            <div class="form-group">
                <label for="totalRecaudar">Total a Recaudar</label>
                <div class="input-dinero input-dinero-bloqueado">
                    <input type="text" id="totalRecaudar" name="totalRecaudar"
                           value="$ 0" readonly>
                </div>
            </div>

            <div class="form-group">
                <label for="totalRecaudado" class="obligatorio">Total Recaudado</label>
                <div class="input-dinero">
                    <input type="text" id="totalRecaudado" name="totalRecaudado"
                           placeholder="0" inputmode="numeric" autocomplete="off" required>
                </div>
            </div>

            <div class="form-group">
                <div class="confirmacion-linea">
                    <label class="campo-toggle-label sin-margen" for="recibioCambios">¿Recibió cambios?</label>
                    <label class="switch-si-no">
                        <input type="checkbox" id="recibioCambios" name="recibioCambios">
                        <span class="switch-pill">
                            <span class="switch-opcion switch-no">No</span>
                            <span class="switch-opcion switch-si">Sí</span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="form-group">
                <label for="observacionesEntrega">Observaciones</label>
                <textarea id="observacionesEntrega" name="observacionesEntrega" rows="3"
                          placeholder="Ej: Entregado en recepción, cliente no estaba..."></textarea>
            </div>

            <!-- Fotos -->
            <div class="form-group">
                <label class="obligatorio">Foto de la Entrega</label>
                <div class="fotos-container">
                    <input type="file" id="inputFotoEntrega" accept="image/*" capture="environment" style="display: none;">
                    <button type="button" id="btnTomarFotoEntrega" class="btn-foto">
                        📷 Tomar Foto de la Entrega
                    </button>
                    <div id="previsualizacionFotoEntrega" class="previsualizacion-fotos"></div>
                </div>
            </div>

            <div class="form-group">
                <label>Foto adicional</label>
                <div class="fotos-container">
                    <input type="file" id="inputFotoEntregaAdicional" accept="image/*" capture="environment" style="display: none;">
                    <button type="button" id="btnTomarFotoEntregaAdicional" class="btn-foto btn-foto-secundaria">
                        📷 Tomar Foto Adicional
                    </button>
                    <div id="previsualizacionFotoEntregaAdicional" class="previsualizacion-fotos"></div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="form-acciones">
                <button type="submit" class="btn-exito btn-grande">
                    ✓ Confirmar Entrega
                </button>
            </div>
        </form>
    </div>

    <!-- Vista Formulario de Novedad -->
    <div id="vistaFormularioNovedad" class="vista-formulario oculto">
        <div class="formulario-header">
            <button type="button" id="btnVolverNovedad" class="btn-volver formulario-volver">← Volver</button>
            <h2 id="novedadTitulo">Novedad de Entrega</h2>
            <p id="novedadGuia">Guía #</p>
        </div>

        <form id="formNovedad" class="formulario-entrega">
            <div class="form-group">
                <label for="descripcionNovedad" class="obligatorio">Descripción</label>
                <textarea id="descripcionNovedad" name="descripcionNovedad" rows="4"
                          placeholder="Describe por qué se aplaza o cancela la entrega..." required></textarea>
            </div>

            <div class="form-group">
                <label class="obligatorio">Evidencia Fotográfica</label>
                <div class="fotos-container">
                    <input type="file" id="inputFotoNovedad" accept="image/*" capture="environment" style="display: none;">
                    <button type="button" id="btnTomarFotoNovedad" class="btn-foto">
                        📷 Tomar Foto de Evidencia
                    </button>
                    <div id="previsualizacionFotoNovedad" class="previsualizacion-fotos"></div>
                </div>
                <small class="ayuda-texto">📸 Obligatorio: una foto como evidencia</small>
            </div>

            <div class="form-group">
                <label>Foto adicional (opcional)</label>
                <div class="fotos-container">
                    <input type="file" id="inputFotoNovedadAdicional" accept="image/*" capture="environment" style="display: none;">
                    <button type="button" id="btnTomarFotoNovedadAdicional" class="btn-foto btn-foto-secundaria">
                        📷 Tomar Foto Adicional
                    </button>
                    <div id="previsualizacionFotoNovedadAdicional" class="previsualizacion-fotos"></div>
                </div>
                <small class="ayuda-texto">Opcional: agrega una segunda foto</small>
            </div>

            <div class="info-automatica">
                <div class="info-auto-item">
                    <span>🕐 Fecha/Hora:</span>
                    <span id="infoFechaNovedad">--</span>
                </div>
            </div>

            <div class="form-acciones">
                <button type="submit" id="btnEnviarNovedad" class="btn-exito btn-grande">
                    Enviar
                </button>
            </div>
        </form>
    </div>

    <!-- Modal de Confirmación -->
    <div id="modalConfirmacion" class="modal oculto">
        <div class="modal-contenido modal-exito">
            <div class="modal-icono exito">✓</div>
            <h2>¡Entrega Completada!</h2>
            <div id="resumenEntrega" class="resumen-entrega"></div>
            <button id="btnCerrarConfirmacion" class="btn-primario btn-grande">
                Continuar con Entregas
            </button>
        </div>
    </div>

    <div id="modalDecision" class="modal oculto">
        <div class="modal-contenido">
            <h2 id="modalDecisionTitulo">Confirmar acción</h2>
            <p id="modalDecisionMensaje" class="modal-mensaje"></p>
            <div class="form-acciones">
                <button type="button" id="btnDecisionCancelar" class="btn-secundario">Cancelar</button>
                <button type="button" id="btnDecisionAceptar" class="btn-primario">Aceptar</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay oculto">
        <div class="spinner"></div>
        <p id="loadingTexto">Procesando...</p>
    </div>

    <script src="../../public/js/mensajeroLayout.js?v=20260411-1"></script>
    <script src="../../public/js/uiToast.js"></script>
    <script src="../../public/js/misPaquetesMensajeros.js?v=20260411-1"></script>

    <script>
        // Garantizar que todos los botones "Volver" regresen a la lista principal
        document.addEventListener('DOMContentLoaded', function() {
            const vistaLista = document.getElementById('vistaLista');
            const modales = ['vistaDetalle', 'vistaFormularioEntrega', 'vistaFormularioNovedad'];
            const botonesVolver = ['btnVolverDetalle', 'btnVolverEntrega', 'btnVolverNovedad'];

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
