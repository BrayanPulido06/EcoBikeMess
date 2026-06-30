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
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/clientesTheme.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <link rel="stylesheet" href="../../public/css/facturacionPanel.css?v=<?php echo $facturacionPanelCssVersion; ?>">
</head>
<body class="facturacion-page">
    <?php include '../layouts/clienteNavbar.php'; ?>
    <?php include '../layouts/clienteSidebar.php'; ?>

    <main class="facturacion-shell app-shell">
        <section
            id="facturacionApp"
            data-mode="cliente"
            data-endpoint="../../controller/facturacionClienteController.php"
        >
            <div class="facturacion-top">
                <div>
                    <h1>Facturación del cliente</h1>
                    <p>Consulta tus entregas facturadas por día, con servicio, recaudos y saldo diario.</p>
                </div>
                <div class="facturacion-role-badge">Cliente</div>
            </div>

            <div id="summary-cliente" class="facturacion-summary" style="display: none;"></div>

            <div class="facturacion-card">
                <div class="facturacion-filters">
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
                                <th>Total servicio</th>
                                <th>Total recaudado</th>
                                <th>Abono</th>
                                <th>Estado</th>
                                <th>Saldo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="table-body-cliente" data-loading>
                            <tr><td colspan="8" class="loading-state">Cargando información...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="facturacion-footnote">Saldo actual calculado con base en recaudos reales menos el valor de los envíos registrados.</div>
                <div class="facturacion-footnote" id="count-cliente">0 registros</div>
            </div>
        </section>
    </main>

    <script src="../../public/js/facturacionPanel.js?v=<?php echo $facturacionPanelJsVersion; ?>"></script>
</body>
</html>
