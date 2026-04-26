<?php
require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../includes/auth.php';
requireWebAuth(['admin', 'administrador']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturacion Admin - EcoBikeMess</title>
    <link rel="icon" href="../../public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <link rel="stylesheet" href="../../public/css/facturacionPanel.css">
</head>
<body class="facturacion-page">
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <main class="facturacion-shell app-shell">
        <section
            id="facturacionApp"
            data-mode="admin"
            data-endpoint="../../controller/facturacionAdminController.php"
        >
            <div class="facturacion-top">
                <div>
                    <h1>Facturacion administrativa</h1>
                    <p>Consulta el saldo actual, la facturacion hacia clientes y el valor a pagar a mensajeros.</p>
                </div>
                <div class="facturacion-role-badge">Administrador</div>
            </div>

            <div class="facturacion-tabs">
                <button class="facturacion-tab active" data-switch-panel="cliente">Clientes</button>
                <button class="facturacion-tab" data-switch-panel="mensajero">Mensajeros</button>
            </div>

            <section data-panel="cliente">
                <div id="summary-cliente" class="facturacion-summary"></div>
                <div class="facturacion-card">
                    <div class="facturacion-filters">
                        <div class="facturacion-field">
                            <label>Buscar</label>
                            <input type="text" placeholder="Guia, cliente o destinatario" data-panel-filter="cliente" data-filter-field="q">
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
                        <table class="facturacion-table facturacion-table-clientes">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Clientes</th>
                                    <th>Cantidad paquetes</th>
                                    <th>Total servicio</th>
                                    <th>Total recaudado</th>
                                    <th>Abono</th>
                                    <th>Estado</th>
                                    <th>Saldo</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="table-body-cliente" data-loading>
                                <tr><td colspan="10" class="loading-state">Cargando informacion...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="facturacion-footnote">Facturacion agrupada por fecha y cliente. En acciones puedes abrir el detalle de los paquetes incluidos en cada fila.</div>
                    <div class="facturacion-footnote" id="count-cliente">0 registros</div>
                </div>
            </section>

            <section data-panel="mensajero" class="panel-hidden">
                <div id="summary-mensajero" class="facturacion-summary"></div>
                <div class="facturacion-card">
                    <div class="facturacion-filters">
                        <div class="facturacion-field">
                            <label>Buscar</label>
                            <input type="text" placeholder="Guia, cliente o mensajero" data-panel-filter="mensajero" data-filter-field="q">
                        </div>
                        <div class="facturacion-field">
                            <label>Estado</label>
                            <select data-panel-filter="mensajero" data-filter-field="estado">
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
                                    <th>Numero guia</th>
                                    <th>Mensajero</th>
                                    <th>Cliente</th>
                                    <th>Paquetes por dia</th>
                                    <th>Valor envio</th>
                                    <th>Agregado al recaudo</th>
                                    <th>Valor recaudo</th>
                                    <th>Recaudo real</th>
                                    <th>Estado</th>
                                    <th>Pago mensajero</th>
                                    <th>Configurar pago / mostrar</th>
                                </tr>
                            </thead>
                            <tbody id="table-body-mensajero" data-loading>
                                <tr><td colspan="11" class="loading-state">Cargando informacion...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="facturacion-footnote">El valor a pagar al mensajero arranca en $7.000 y puede modificarse por paquete. El boton "Mostrar" habilita su visualizacion para el mensajero.</div>
                    <div class="facturacion-footnote" id="count-mensajero">0 registros</div>
                </div>
            </section>
        </section>
    </main>

    <div id="facturacionDetailModal" class="facturacion-modal-backdrop modal-hidden" aria-hidden="true">
        <div class="facturacion-modal">
            <div class="facturacion-modal-head">
                <div>
                    <h2 id="facturacionDetailTitle">Detalle de paquetes</h2>
                    <p id="facturacionDetailSubtitle">Consulta la informacion del grupo seleccionado.</p>
                </div>
                <button type="button" class="facturacion-modal-close" data-close-detail-modal>&times;</button>
            </div>
            <div class="facturacion-modal-body" id="facturacionDetailBody"></div>
        </div>
    </div>

    <script src="../../public/js/facturacionPanel.js"></script>
</body>
</html>
