<?php
require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../includes/auth.php';
requireWebAuth(['admin', 'administrador']);
$facturacionPanelCssVersion = @filemtime(__DIR__ . '/../../public/css/facturacionPanel.css') ?: time();
$facturacionPanelJsVersion = @filemtime(__DIR__ . '/../../public/js/facturacionPanel.js') ?: time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturacion Admin - EcoBikeMess</title>
    <link rel="icon" href="<?php echo htmlspecialchars(app_asset_url('img/Logo_Negro_Transparente.png'), ENT_QUOTES, 'UTF-8'); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/clienteSidebar.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/clienteNavbar.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/responsive.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/facturacionPanel.css') . '?v=' . $facturacionPanelCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="facturacion-page">
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <main class="facturacion-shell app-shell">
        <section
            id="facturacionApp"
            data-mode="admin"
            data-endpoint="<?php echo htmlspecialchars(app_controller_url('facturacionAdminController.php'), ENT_QUOTES, 'UTF-8'); ?>"
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
                <button class="facturacion-tab" data-switch-panel="ecobikemess">EcoBikeMess</button>
            </div>

            <section data-panel="cliente">
                <div id="summary-cliente" class="facturacion-summary"></div>
                <div class="facturacion-card">
                    <div class="facturacion-filters">
                        <div class="facturacion-field">
                            <label>Buscar</label>
                            <input type="text" placeholder="Guia o cliente" data-panel-filter="cliente" data-filter-field="q">
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
                            <select data-role="bulk-client-status" aria-label="Estado para clientes seleccionados" disabled>
                                <option value="pagado">Pagado</option>
                                <option value="pendiente">Pendiente</option>
                            </select>
                            <button class="fact-btn primary" type="button" data-role="update-selected-client-status" disabled>Cambiar estado</button>
                            <button class="fact-btn danger" type="button" data-role="hide-selected-client-groups" disabled>Eliminar seleccionados</button>
                            <button class="fact-btn secondary" type="button" data-reset-panel="cliente">Limpiar filtros</button>
                        </div>
                    </div>

                    <div id="cliente-folder-view" class="client-folder-view"></div>

                    <div id="cliente-history-toolbar" class="client-history-toolbar panel-hidden">
                        <button class="fact-btn secondary" type="button" data-role="back-to-client-folders">Volver a clientes</button>
                        <div>
                            <span class="client-history-label">Historial de cliente</span>
                            <strong id="cliente-history-title">Cliente</strong>
                        </div>
                    </div>

                    <div id="cliente-history-table" class="facturacion-table-wrap panel-hidden">
                        <table class="facturacion-table facturacion-table-clientes">
                            <thead>
                                <tr>
                                    <th class="select-col">
                                        <input type="checkbox" data-role="select-all-client-groups" aria-label="Seleccionar todas las filas">
                                    </th>
                                    <th>Clientes</th>
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
                                <tr><td colspan="12" class="loading-state">Cargando informacion...</td></tr>
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
                            <input type="text" placeholder="Mensajero" data-panel-filter="mensajero" data-filter-field="q">
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
                            <select data-role="bulk-messenger-status" aria-label="Estado para mensajeros seleccionados" disabled>
                                <option value="pagado">Pagado</option>
                                <option value="pendiente">Pendiente</option>
                            </select>
                            <button class="fact-btn primary" type="button" data-role="update-selected-messenger-status" disabled>Cambiar estado</button>
                            <button class="fact-btn danger" type="button" data-role="hide-selected-messenger-groups" disabled>Eliminar seleccionados</button>
                            <button class="fact-btn secondary" type="button" data-reset-panel="mensajero">Limpiar filtros</button>
                        </div>
                    </div>

                    <div class="facturacion-table-wrap">
                        <table class="facturacion-table facturacion-table-mensajeros">
                            <thead>
                                <tr>
                                    <th class="select-col">
                                        <input type="checkbox" data-role="select-all-messenger-groups" aria-label="Seleccionar todas las filas">
                                    </th>
                                    <th>Mensajero</th>
                                    <th>Fecha</th>
                                    <th>Entregas</th>
                                    <th>Adicionales</th>
                                    <th>Total pago</th>
                                    <th>Total recaudado</th>
                                    <th>Abono</th>
                                    <th>Estado</th>
                                    <th>Saldo</th>
                                    <th>Total acumulado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="table-body-mensajero" data-loading>
                                <tr><td colspan="12" class="loading-state">Cargando informacion...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="facturacion-footnote">Facturacion agrupada por fecha y mensajero. El pago arranca en $7.000 por entrega y puede modificarse por paquete desde el detalle.</div>
                    <div class="facturacion-footnote" id="count-mensajero">0 registros</div>
                </div>
            </section>

            <section data-panel="ecobikemess" class="panel-hidden">
                <div id="summary-ecobikemess" class="facturacion-summary"></div>
                <div class="facturacion-card">
                    <div class="facturacion-filters">
                        <div class="facturacion-field">
                            <label>Buscar</label>
                            <input type="text" placeholder="Guia, cliente o mensajero" data-panel-filter="ecobikemess" data-filter-field="q">
                        </div>
                        <div class="facturacion-field">
                            <label>Desde</label>
                            <input type="date" data-panel-filter="ecobikemess" data-filter-field="desde">
                        </div>
                        <div class="facturacion-field">
                            <label>Hasta</label>
                            <input type="date" data-panel-filter="ecobikemess" data-filter-field="hasta">
                        </div>
                        <div class="facturacion-actions">
                            <button class="fact-btn secondary" type="button" data-reset-panel="ecobikemess">Limpiar filtros</button>
                        </div>
                    </div>

                    <div class="facturacion-table-wrap">
                        <table class="facturacion-table facturacion-table-ecobikemess">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Entregas</th>
                                    <th>Cobrado clientes</th>
                                    <th>Pago mensajeros</th>
                                    <th>Adicionales clientes</th>
                                    <th>Ajustes mensajeros</th>
                                    <th>Ganancia EcoBikeMess</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="table-body-ecobikemess" data-loading>
                                <tr><td colspan="8" class="loading-state">Cargando informacion...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="facturacion-footnote">Ganancia agrupada por fecha: cobrado al cliente menos pago al mensajero, incluyendo ajustes generales registrados.</div>
                    <div class="facturacion-footnote" id="count-ecobikemess">0 registros</div>
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

    <script src="<?php echo htmlspecialchars(app_asset_url('js/facturacionPanel.js') . '?v=' . $facturacionPanelJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
