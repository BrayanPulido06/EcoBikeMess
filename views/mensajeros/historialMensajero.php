<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Historial - EcoBikeMess</title>
    
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/misPaquetesMensajeros.css">
    <link rel="stylesheet" href="../../public/css/historialMensajero.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
</head>
<body>
    <!-- Header Móvil -->
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1>🚴 EcoBikeMess</h1>
            <p class="user-name">Historial de Entregas</p>
        </div>
        <button class="notif-btn" id="notifBtn">
            <span class="notif-icon">🔔</span>
            <span class="notif-badge">3</span>
        </button>
    </header>

    <!-- Menu Lateral -->
    <?php include '../layouts/mensajeroSidebar.php'; ?>


    <!-- Filtros de Historial -->
    <div class="filtros-container" style="margin-top: 80px;">
        <div class="filtros">
            <button class="filtro-btn activo" data-filtro="todos">Todos</button>
            <button class="filtro-btn" data-filtro="hoy">Hoy</button>
            <button class="filtro-btn" data-filtro="semana">Esta Semana</button>
            <button class="filtro-btn" data-filtro="mes">Este Mes</button>
        </div>
    </div>

    <!-- Lista de Historial -->
    <main id="vistaLista" class="vista-activa main-content" style="padding-top: 0;">
        <div id="listaHistorial" class="lista-paquetes">
            <!-- Los paquetes entregados se cargarán aquí -->
        </div>
    </main>

    <!-- Vista Detalle (Solo lectura) -->
    <div id="vistaDetalle" class="vista-detalle oculto">
        <div class="detalle-header">
            <button id="btnVolverDetalle" class="btn-volver">← Volver</button>
            <h2 id="detalleGuia">Guía #</h2>
        </div>

        <div class="detalle-contenido">
            <section class="seccion-detalle">
                <div class="estado-badge-grande">
                    <span class="badge-grande entregado">Entregado</span>
                </div>
            </section>

            <section class="seccion-detalle">
                <h3>✅ Información de Entrega</h3>
                <div class="info-item">
                    <span class="info-label">Recibió:</span>
                    <span id="entregaRecibio"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Parentesco:</span>
                    <span id="entregaParentesco"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Documento:</span>
                    <span id="entregaDocumento"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha y Hora:</span>
                    <span id="entregaFecha"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Recaudo:</span>
                    <span id="entregaRecaudo" class="valor-destacado"></span>
                </div>
            </section>

            <section class="seccion-detalle">
                <h3>📦 Detalles del Paquete</h3>
                <div class="info-item">
                    <span class="info-label">Destinatario Original:</span>
                    <span id="detalleDestinatario"></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dirección:</span>
                    <p id="detalleDireccion" class="direccion-completa"></p>
                </div>
                <div class="info-item">
                    <span class="info-label">Contenido:</span>
                    <span id="detalleContenido"></span>
                </div>
            </section>
        </div>
    </div>

    <script src="../../public/js/historialMensajero.js"></script>
    
    <script>
        // Lógica del Sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('menuBtn');
            const sideMenu = document.getElementById('sideMenu');
            const menuOverlay = document.getElementById('menuOverlay');
            
            if (menuBtn && sideMenu && menuOverlay) {
                menuBtn.addEventListener('click', () => { sideMenu.classList.add('active'); menuOverlay.classList.add('active'); });
                menuOverlay.addEventListener('click', () => { sideMenu.classList.remove('active'); menuOverlay.classList.remove('active'); });
                
                // Marcar activo
                sideMenu.querySelectorAll('a').forEach(link => link.classList.remove('active'));
                sideMenu.querySelector('a[href*="historialMensajero.php"]')?.classList.add('active');
            }
        });
    </script>
</body>
</html>