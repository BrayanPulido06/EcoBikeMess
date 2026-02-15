<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#059669">
    <title>Mis Paquetes - Sistema de Mensajería</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest-paquetes.json">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>📦</text></svg>">
    
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/misPaquetesMensajeros.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
</head>
<body>
    <!-- Header Móvil (Diseño Unificado) -->
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1>🚴 EcoBikeMess</h1>
            <p class="user-name">Mis Paquetes</p>
        </div>
        <button class="notif-btn" id="notifBtn">
            <span class="notif-icon">🔔</span>
            <span class="notif-badge">3</span>
        </button>
    </header>

    <!-- Menu Lateral -->
    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <!-- Estadísticas del Día -->
    <section class="estadisticas-dia" style="margin-top: 80px;">
        <div class="stat-item">
            <div class="stat-valor" id="totalPaquetes">0</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-item">
            <div class="stat-valor" id="enRuta">0</div>
            <div class="stat-label">En Ruta</div>
        </div>
        <div class="stat-item">
            <div class="stat-valor" id="entregados">0</div>
            <div class="stat-label">Entregados</div>
        </div>
        <div class="stat-item">
            <div class="stat-valor recaudo" id="totalRecaudo">$0</div>
            <div class="stat-label">Recaudado</div>
        </div>
    </section>

    <!-- Filtros -->
    <div class="filtros-container">
        <div class="filtros">
            <button class="filtro-btn activo" data-filtro="todos">Todos</button>
            <button class="filtro-btn" data-filtro="pendiente">Pendientes</button>
            <button class="filtro-btn" data-filtro="en_ruta">En Ruta</button>
        </div>
        <button id="btnOrdenarRuta" class="btn-ordenar">
            🎯 Optimizar Ruta
        </button>
    </div>

    <!-- Vista Principal: Lista de Paquetes -->
    <main id="vistaLista" class="vista-activa main-content" style="padding-top: 0;">
        <!-- Controles Superiores (Movidos del Header antiguo) -->
        <div class="resumen-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; background: white; padding: 1rem; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <div class="contador-resumen">
                <span id="contadorPendientes" class="contador-badge pendiente">0</span>
                <span id="contadorEntregados" class="contador-badge entregado">0</span>
            </div>
            <div class="header-actions" style="display: flex; gap: 10px;">
                <button id="btnVerMapa" class="btn-icon" title="Ver Mapa de Entregas" style="background: #f8fdf9; color: #2d3e50; width: 40px; height: 40px; border-radius: 10px; border: 1px solid #e8f5f1;">
                    🗺️
                </button>
                <button id="btnActualizar" class="btn-icon" title="Actualizar" style="background: #f8fdf9; color: #2d3e50; width: 40px; height: 40px; border-radius: 10px; border: 1px solid #e8f5f1;">
                    🔄
                </button>
            </div>
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
                    <span class="info-label">Contenido:</span>
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
            <h2>✓ Entregar Paquete</h2>
            <p id="formGuia">Guía #</p>
        </div>

        <form id="formEntrega" class="formulario-entrega">
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
                <label for="recaudo" class="obligatorio">Recaudo (Efectivo)</label>
                <div class="input-dinero">
                    <span class="simbolo-dinero">$</span>
                    <input type="number" id="recaudo" name="recaudo" 
                           placeholder="0" min="0" step="100" value="0" required>
                </div>
                <small class="ayuda-texto">Monto recibido en efectivo. Si no hay recaudo, dejar en 0</small>
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
                    <input type="file" id="inputFotosEntrega" accept="image/*" capture="environment" multiple style="display: none;">
                    <button type="button" id="btnTomarFotoEntrega" class="btn-foto">
                        📷 Tomar Foto de la Entrega
                    </button>
                    <div id="previsualizacionFotosEntrega" class="previsualizacion-fotos"></div>
                </div>
                <small class="ayuda-texto">📸 Obligatorio: al menos una foto de la persona que recibe o evidencia de entrega</small>
            </div>

            <!-- Información Automática -->
            <div class="info-automatica">
                <div class="info-auto-item">
                    <span>📍 Ubicación GPS:</span>
                    <span id="infoGPSEntrega">Obteniendo...</span>
                </div>
                <div class="info-auto-item">
                    <span>🕐 Fecha/Hora:</span>
                    <span id="infoFechaEntrega">--</span>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="form-acciones">
                <button type="button" id="btnCancelarEntrega" class="btn-secundario">
                    Cancelar
                </button>
                <button type="submit" class="btn-exito btn-grande">
                    ✓ Confirmar Entrega
                </button>
            </div>
        </form>
    </div>

    <!-- Vista Mapa de Entregas -->
    <div id="vistaMapa" class="vista-mapa oculto">
        <div class="mapa-header">
            <button id="btnCerrarMapa" class="btn-volver">← Volver</button>
            <h2>🗺️ Mapa de Entregas</h2>
        </div>
        <div id="mapaContainer" class="mapa-container">
            <div class="mapa-placeholder">
                <p>🗺️</p>
                <p>El mapa interactivo se mostraría aquí</p>
                <p class="texto-pequeno">Integración con Google Maps API</p>
            </div>
        </div>
        <div class="mapa-leyenda">
            <div class="leyenda-item">
                <span class="punto pendiente"></span>
                <span>Pendiente</span>
            </div>
            <div class="leyenda-item">
                <span class="punto en-ruta"></span>
                <span>En Ruta</span>
            </div>
            <div class="leyenda-item">
                <span class="punto entregado"></span>
                <span>Entregado</span>
            </div>
        </div>
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

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay oculto">
        <div class="spinner"></div>
        <p id="loadingTexto">Procesando...</p>
    </div>

    <script src="../../public/js/misPaquetesMensajeros.js"></script>
    
    <!-- Script para funcionalidad del Sidebar -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('menuBtn');
            const sideMenu = document.getElementById('sideMenu');
            const menuOverlay = document.getElementById('menuOverlay');
            
            if (menuBtn && sideMenu && menuOverlay) {
                menuBtn.addEventListener('click', () => {
                    sideMenu.classList.add('active');
                    menuOverlay.classList.add('active');
                });
                
                menuOverlay.addEventListener('click', () => {
                    sideMenu.classList.remove('active');
                    menuOverlay.classList.remove('active');
                });

                // Marcar link activo
                const links = sideMenu.querySelectorAll('a');
                links.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href').includes('misPaquetesMensajeros.php')) {
                        link.classList.add('active');
                    }
                });
            }
        });
    </script>
</body>
</html>