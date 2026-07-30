<?php
require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../includes/auth.php';
requireWebAuth(['mensajero']);
$facturacionPanelCssVersion = @filemtime(__DIR__ . '/../../public/css/facturacionPanel.css') ?: time();
$facturacionPanelJsVersion = @filemtime(__DIR__ . '/../../public/js/facturacionPanel.js') ?: time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación Mensajero - EcoBikeMess</title>
    <link rel="icon" href="<?php echo htmlspecialchars(app_asset_url('img/Logo_Negro_Transparente.png'), ENT_QUOTES, 'UTF-8'); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/inicioMensajero.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/mensajeroSidebar.css') . '?v=20260528-1', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/responsive.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/facturacionPanel.css') . '?v=' . $facturacionPanelCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="facturacion-page">
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1><img src="<?php echo htmlspecialchars(app_asset_url('img/Logo_Circulo_Fondoblanco.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="EcoBikeMess" style="width:35px;height:35px;vertical-align:middle;margin-right:6px;">EcoBikeMess</h1>
            <p class="user-name">Facturación del mensajero</p>
        </div>
    </header>

    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <main class="main-content facturacion-shell">
        <section
            id="facturacionApp"
            data-mode="mensajero"
            data-endpoint="<?php echo htmlspecialchars(app_controller_url('facturacionMensajeroController.php'), ENT_QUOTES, 'UTF-8'); ?>"
        >
            <div class="facturacion-top">
                <div>
                    <h1>Facturación del mensajero</h1>
                    <p>Consulta tus paquetes entregados con recaudos, valor del envío y valor a pagar definido por administración.</p>
                </div>
                <div class="facturacion-role-badge">Mensajero</div>
            </div>

            <div id="summary-mensajero" class="facturacion-summary"></div>

            <div class="facturacion-card">
                <div class="facturacion-filters">
                    <div class="facturacion-field">
                        <label>Buscar</label>
                        <input type="text" placeholder="Guía o cliente" data-panel-filter="mensajero" data-filter-field="q">
                    </div>
                    <div class="facturacion-field">
                        <label>Estado</label>
                        <select data-panel-filter="mensajero" data-filter-field="estado">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="asignado">Asignado</option>
                            <option value="en_transito">En tránsito</option>
                            <option value="en_ruta">En ruta</option>
                            <option value="entregado">Entregado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="facturacion-field">
                        <label>Desde</label>
                        <input type="date" data-panel-filter="mensajero" data-filter-field="desde">
                    </div>
                    <div class="facturacion-field">
                        <label>Hasta</label>
                        <input type="date" data-panel-filter="mensajero" data-filter-field="hasta">
                    </div>
                    <div class="facturacion-actions">
                        <button class="fact-btn secondary" type="button" data-reset-panel="mensajero">Limpiar filtros</button>
                    </div>
                </div>

                <div class="facturacion-table-wrap">
                    <table class="facturacion-table">
                        <thead>
                            <tr>
                                <th>Número guía</th>
                                <th>Mensajero</th>
                                <th>Cliente</th>
                                <th>Paquetes por día</th>
                                <th>Valor envío</th>
                                <th>Agregado al recaudo</th>
                                <th>Valor recaudo</th>
                                <th>Recaudo real</th>
                                <th>Estado</th>
                                <th>Pago mensajero</th>
                            </tr>
                        </thead>
                        <tbody id="table-body-mensajero" data-loading>
                            <tr><td colspan="10" class="loading-state">Cargando información...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="facturacion-footnote">Esta vista es solo lectura y muestra tus entregas facturadas.</div>
                <div class="facturacion-footnote" id="count-mensajero">0 registros</div>
            </div>
        </section>
    </main>

    <script src="<?php echo htmlspecialchars(app_asset_url('js/facturacionPanel.js') . '?v=' . $facturacionPanelJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_asset_url('js/mensajeroLayout.js') . '?v=20260528-1', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
