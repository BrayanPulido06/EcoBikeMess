<?php
require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../includes/auth.php';
requireWebAuth(['mensajero']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación Mensajero - EcoBikeMess</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <link rel="stylesheet" href="../../public/css/facturacionPanel.css">
</head>
<body class="facturacion-page">
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:35px;height:35px;vertical-align:middle;margin-right:6px;">EcoBikeMess</h1>
            <p class="user-name">Facturación del mensajero</p>
        </div>
    </header>

    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <main class="main-content facturacion-shell">
        <section
            id="facturacionApp"
            data-mode="mensajero"
            data-endpoint="../../controller/facturacionMensajeroController.php"
        >
            <div class="facturacion-top">
                <div>
                    <h1>Facturación del mensajero</h1>
                    <p>Consulta los paquetes visibles para ti con recaudos, valor del envío y valor a pagar definido por administración.</p>
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
                                <th>Visible</th>
                            </tr>
                        </thead>
                        <tbody id="table-body-mensajero" data-loading>
                            <tr><td colspan="11" class="loading-state">Cargando información...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="facturacion-footnote">Esta vista es solo lectura y únicamente muestra los pagos que administración marcó como visibles para ti.</div>
                <div class="facturacion-footnote" id="count-mensajero">0 registros</div>
            </div>
        </section>
    </main>

    <script src="../../public/js/facturacionPanel.js"></script>
    <script src="../../public/js/mensajeroLayout.js"></script>
</body>
</html>
