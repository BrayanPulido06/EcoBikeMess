<?php
require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../includes/auth.php';
requireWebAuth(['cliente', 'colaborador']);
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
    <link rel="stylesheet" href="../../public/css/facturacionPanel.css">
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
                    <p>Aquí ves la misma información de facturación hacia cliente que revisa el administrador.</p>
                </div>
                <div class="facturacion-role-badge">Cliente</div>
            </div>

            <div id="summary-cliente" class="facturacion-summary"></div>

            <div class="facturacion-card">
                <div class="facturacion-filters">
                    <div class="facturacion-field">
                        <label>Buscar</label>
                        <input type="text" placeholder="Guía o destinatario" data-panel-filter="cliente" data-filter-field="q">
                    </div>
                    <div class="facturacion-field">
                        <label>Estado</label>
                        <select data-panel-filter="cliente" data-filter-field="estado">
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
                    <table class="facturacion-table">
                        <thead>
                            <tr>
                                <th>Número guía</th>
                                <th>Cliente</th>
                                <th>Destinatario</th>
                                <th>Paquetes por día</th>
                                <th>Valor envío</th>
                                <th>Agregado al recaudo</th>
                                <th>Valor recaudo</th>
                                <th>Recaudo real</th>
                                <th>Estado</th>
                                <th>Fecha</th>
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

    <script src="../../public/js/facturacionPanel.js"></script>
</body>
</html>
