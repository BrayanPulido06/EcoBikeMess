<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/inicioCliente.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include '../layouts/clienteSidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <?php include '../layouts/clienteNavbar.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #5cb85c 0%, #4cae4c 100%);">
                        📦
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Pedidos del Mes</span>
                        <span class="stat-value">24</span>
                        <span class="stat-change positive">+12% vs mes anterior</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);">
                        🚴
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">En Tránsito</span>
                        <span class="stat-value">5</span>
                        <span class="stat-change neutral">Actualizaciones en tiempo real</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);">
                        💰
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Saldo Pendiente</span>
                        <span class="stat-value">$125.500</span>
                        <span class="stat-change negative">Por pagar</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);">
                        ✓
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Entregados</span>
                        <span class="stat-value">158</span>
                        <span class="stat-change positive">Total histórico</span>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="content-grid">
                <!-- Gráfico de Actividad -->
                <div class="card chart-card">
                    <div class="card-header">
                        <h2>Actividad Mensual</h2>
                        <div class="header-actions">
                            <select class="period-select">
                                <option>Últimos 30 días</option>
                                <option>Últimos 3 meses</option>
                                <option>Último año</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>

                <!-- Acción Rápida -->
                <div class="card quick-action-card">
                    <div class="card-header">
                        <h2>Acción Rápida</h2>
                    </div>
                    <div class="card-body">
                        <div class="quick-action-content">
                            <div class="quick-icon">📦</div>
                            <h3>Enviar Paquete</h3>
                            <p>Crea un nuevo envío de forma rápida y sencilla</p>
                            <a href="enviarPaquete.php" class="btn-primary">Nuevo Envío</a>
                        </div>
                        <div class="quick-stats">
                            <div class="quick-stat-item">
                                <span class="quick-stat-label">Último envío</span>
                                <span class="quick-stat-value">Hace 2 horas</span>
                            </div>
                            <div class="quick-stat-item">
                                <span class="quick-stat-label">Tiempo promedio</span>
                                <span class="quick-stat-value">45 min</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimos Envíos y Comprobantes -->
            <div class="content-grid-2">
                <!-- Últimos Envíos -->
                <div class="card">
                    <div class="card-header">
                        <h2>Últimos Envíos</h2>
                        <a href="misPedidos.php" class="view-all-link">Ver todos →</a>
                    </div>
                    <div class="card-body no-padding">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Destino</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="order-id">#12345</span></td>
                                        <td>Calle 100 #15-30</td>
                                        <td><span class="status-badge status-delivered">Entregado</span></td>
                                        <td>14/12/2024</td>
                                        <td>
                                            <button class="icon-btn" title="Ver detalles">👁️</button>
                                            <button class="icon-btn" title="Descargar">⬇️</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="order-id">#12344</span></td>
                                        <td>Carrera 7 #80-45</td>
                                        <td><span class="status-badge status-in-transit">En tránsito</span></td>
                                        <td>14/12/2024</td>
                                        <td>
                                            <button class="icon-btn" title="Ver detalles">👁️</button>
                                            <button class="icon-btn" title="Rastrear">📍</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="order-id">#12343</span></td>
                                        <td>Avenida 68 #25-10</td>
                                        <td><span class="status-badge status-pending">Pendiente</span></td>
                                        <td>13/12/2024</td>
                                        <td>
                                            <button class="icon-btn" title="Ver detalles">👁️</button>
                                            <button class="icon-btn" title="Cancelar">❌</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="order-id">#12342</span></td>
                                        <td>Calle 26 #68-91</td>
                                        <td><span class="status-badge status-delivered">Entregado</span></td>
                                        <td>13/12/2024</td>
                                        <td>
                                            <button class="icon-btn" title="Ver detalles">👁️</button>
                                            <button class="icon-btn" title="Descargar">⬇️</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="order-id">#12341</span></td>
                                        <td>Transversal 45 #12-67</td>
                                        <td><span class="status-badge status-delivered">Entregado</span></td>
                                        <td>12/12/2024</td>
                                        <td>
                                            <button class="icon-btn" title="Ver detalles">👁️</button>
                                            <button class="icon-btn" title="Descargar">⬇️</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Comprobantes Recientes -->
                <div class="card">
                    <div class="card-header">
                        <h2>Comprobantes Recientes</h2>
                        <a href="comprobantes.php" class="view-all-link">Ver todos →</a>
                    </div>
                    <div class="card-body">
                        <div class="receipt-list">
                            <div class="receipt-item">
                                <div class="receipt-icon">📄</div>
                                <div class="receipt-info">
                                    <span class="receipt-title">Comprobante #2024-001</span>
                                    <span class="receipt-date">14 de Diciembre, 2024</span>
                                </div>
                                <div class="receipt-amount">$35.000</div>
                                <button class="icon-btn" title="Descargar">⬇️</button>
                            </div>
                            <div class="receipt-item">
                                <div class="receipt-icon">📄</div>
                                <div class="receipt-info">
                                    <span class="receipt-title">Comprobante #2024-002</span>
                                    <span class="receipt-date">13 de Diciembre, 2024</span>
                                </div>
                                <div class="receipt-amount">$28.500</div>
                                <button class="icon-btn" title="Descargar">⬇️</button>
                            </div>
                            <div class="receipt-item">
                                <div class="receipt-icon">📄</div>
                                <div class="receipt-info">
                                    <span class="receipt-title">Comprobante #2024-003</span>
                                    <span class="receipt-date">12 de Diciembre, 2024</span>
                                </div>
                                <div class="receipt-amount">$42.000</div>
                                <button class="icon-btn" title="Descargar">⬇️</button>
                            </div>
                            <div class="receipt-item">
                                <div class="receipt-icon">📄</div>
                                <div class="receipt-info">
                                    <span class="receipt-title">Comprobante #2024-004</span>
                                    <span class="receipt-date">11 de Diciembre, 2024</span>
                                </div>
                                <div class="receipt-amount">$31.500</div>
                                <button class="icon-btn" title="Descargar">⬇️</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="../../public/js/inicioCliente.js"></script>
</body>
</html>