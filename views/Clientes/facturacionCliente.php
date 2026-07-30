<?php
require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../includes/auth.php';
requireWebAuth(['cliente', 'colaborador']);
$facturacionPanelCssVersion = @filemtime(__DIR__ . '/../../public/css/facturacionPanel.css') ?: time();
$facturacionPanelJsVersion = @filemtime(__DIR__ . '/../../public/js/facturacionPanel.js') ?: time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación Cliente - EcoBikeMess</title>
    <link rel="icon" href="<?php echo htmlspecialchars(app_asset_url('img/Logo_Negro_Transparente.png'), ENT_QUOTES, 'UTF-8'); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/clienteSidebar.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/clienteNavbar.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/clientesTheme.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/responsive.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/facturacionPanel.css') . '?v=' . $facturacionPanelCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="facturacion-page">
    <?php include '../layouts/clienteNavbar.php'; ?>
    <?php include '../layouts/clienteSidebar.php'; ?>

    <main class="facturacion-shell app-shell">
        <section
            id="facturacionApp"
            data-mode="cliente"
            data-endpoint="<?php echo htmlspecialchars(app_controller_url('facturacionClienteController.php'), ENT_QUOTES, 'UTF-8'); ?>"
        >
            <div class="facturacion-top">
                <div>
                    <h1>Facturación del cliente</h1>
                    <p>Consulta tus entregas facturadas por día, con servicio, recaudos y saldo diario.</p>
                </div>
                <div class="facturacion-role-badge">Cliente</div>
            </div>

            <div id="summary-cliente" class="facturacion-summary"></div>

            <div class="facturacion-card">
                <div class="facturacion-filters">
                    <div class="facturacion-field">
                        <label>Buscar</label>
                        <input type="text" placeholder="Guia o destinatario" data-panel-filter="cliente" data-filter-field="q">
                    </div>
                    <div class="facturacion-field">
                        <label>Estado</label>
                        <select data-panel-filter="cliente" data-filter-field="estado">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="asignado">Asignado</option>
                            <option value="en_transito">En transito</option>
                            <option value="en_ruta">En ruta</option>
                            <option value="entregado">Entregado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="facturacion-field">
                        <label>Desde</label>
                        <input type="date" data-panel-filter="cliente" data-filter-field="desde">
                    </div>
                    <div class="facturacion-field">
                        <label>Hasta</label>
                        <input type="date" data-panel-filter="cliente" data-filter-field="hasta">
                    </div>
                    <div class="facturacion-actions">
                        <button class="fact-btn secondary" type="button" data-reset-panel="cliente">Limpiar filtros</button>
                    </div>
                </div>

                <div class="facturacion-table-wrap">
                    <table class="facturacion-table facturacion-table-clientes facturacion-table-cliente-resumen">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Paquetes entregados</th>
                                <th>Adicionales</th>
                                <th>Total servicio</th>
                                <th>Total recaudado</th>
                                <th>Abono</th>
                                <th>Estado</th>
                                <th>Saldo</th>
                                <th>Total acumulado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="table-body-cliente" data-loading>
                            <tr><td colspan="10" class="loading-state">Cargando información...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="facturacion-footnote">Saldo actual calculado con base en recaudos reales menos el valor de los envíos registrados.</div>
                <div class="facturacion-footnote" id="count-cliente">0 registros</div>
            </div>
        </section>
    </main>

    <div id="facturacionDetailModal" class="facturacion-modal-backdrop modal-hidden" aria-hidden="true">
        <div class="facturacion-modal">
            <div class="facturacion-modal-head">
                <div>
                    <h2 id="facturacionDetailTitle">Detalle de paquetes</h2>
                    <p id="facturacionDetailSubtitle">Consulta la información del grupo seleccionado.</p>
                </div>
                <button type="button" class="facturacion-modal-close" data-close-detail-modal>&times;</button>
            </div>
            <div class="facturacion-modal-body" id="facturacionDetailBody"></div>
        </div>
    </div>

    <script src="<?php echo htmlspecialchars(app_asset_url('js/facturacionPanel.js') . '?v=' . $facturacionPanelJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
