<?php
require_once __DIR__ . '/../../includes/paths.php';
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    redirect_route('login', ['error' => 'Debes iniciar sesion.']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <base href="<?php echo htmlspecialchars(app_url('/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        window.APP_BASE_PATH = <?php echo json_encode(app_url(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <title>Mis Pedidos - EcoBikeMess</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
    <link rel="stylesheet" href="../../public/css/misPedidosMensajero.css?v=20260418-1">
    <link rel="stylesheet" href="../../public/css/responsive.css">
</head>
<body>
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">Menu</span>
        </button>
        <div class="header-info">
            <h1><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:35px;height:35px;vertical-align:middle;margin-right:6px;">EcoBikeMess</h1>
            <p class="user-name">Mis pedidos creados</p>
        </div>
    </header>

    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <main class="main-content pedidos-mensajero-main">
        <div class="session-status">
            <div class="status-indicator online">
                <span class="status-dot"></span>
                <span class="status-text">Paqueteria</span>
            </div>
            <div class="session-time">
                <span class="time-icon">Pedidos</span>
                <span>Mis pedidos</span>
            </div>
        </div>

        <section class="pedidos-header-card">
            <div>
                <h1>Mis pedidos</h1>
                <p>Aqui ves todos los paquetes creados por ti desde paqueteria.</p>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-box">
                <span class="stat-label">Total</span>
                <strong id="statTotal">0</strong>
            </article>
            <article class="stat-box">
                <span class="stat-label">Pendientes</span>
                <strong id="statPendientes">0</strong>
            </article>
            <article class="stat-box">
                <span class="stat-label">Entregados</span>
                <strong id="statEntregados">0</strong>
            </article>
            <article class="stat-box">
                <span class="stat-label">Cancelados</span>
                <strong id="statCancelados">0</strong>
            </article>
        </section>

        <section class="filters-card">
            <div class="filter-group full">
                <label for="searchInput">Buscar</label>
                <input type="text" id="searchInput" placeholder="Guia, destinatario o direccion">
            </div>
            <div class="filter-group">
                <label for="filtroEstado">Estado</label>
                <select id="filtroEstado">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="asignado">Asignado</option>
                    <option value="en_transito">En transito</option>
                    <option value="entregado">Entregado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filtroFechaDesde">Fecha desde</label>
                <input type="date" id="filtroFechaDesde">
            </div>
            <div class="filter-group">
                <label for="filtroFechaHasta">Fecha hasta</label>
                <input type="date" id="filtroFechaHasta">
            </div>
            <div class="filter-actions">
                <button type="button" id="btnBuscar" class="btn-primary-inline">Buscar</button>
                <button type="button" id="btnLimpiar" class="btn-secondary-inline">Limpiar</button>
            </div>
        </section>

        <section class="list-card">
            <div class="list-card-header">
                <h2>Listado</h2>
                <span id="resultsCount">0 resultados</span>
            </div>
            <div id="pedidosList" class="pedidos-list">
                <div class="empty-state">Cargando pedidos...</div>
            </div>
        </section>
    </main>

    <div id="detalleModal" class="detalle-modal" style="display: none;">
        <div class="detalle-backdrop" id="detalleBackdrop"></div>
        <div class="detalle-dialog">
            <button type="button" class="detalle-close" id="detalleClose">Cerrar</button>
            <div id="detalleContent" class="detalle-content"></div>
        </div>
    </div>

    <script src="../../public/js/mensajeroLayout.js?v=20260418-1"></script>
    <script src="../../public/js/misPedidosMensajero.js?v=20260418-1"></script>
</body>
</html>
