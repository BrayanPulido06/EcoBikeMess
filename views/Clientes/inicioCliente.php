<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../models/inicioClienteModel.php';

// Inicializar variables por defecto
$stats = ['pedidos_mes' => 0, 'en_transito' => 0, 'saldo_pendiente' => 0, 'entregados_total' => 0];
$ultimosPedidos = [];
$chartDataRaw = [];

// Obtener datos reales
$model = new InicioClienteModel();
$cliente_id = $model->obtenerIdCliente($_SESSION['user_id'], $_SESSION['user_role']);

if ($cliente_id) {
    $stats = $model->obtenerEstadisticas($cliente_id);
    $ultimosPedidos = $model->obtenerUltimosPedidos($cliente_id);
    $chartDataRaw = $model->obtenerDatosGrafica($cliente_id);
}

// Procesar datos para la gráfica (llenar meses vacíos con 0)
$chartLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$dataTotal = array_fill(0, 12, 0);
$dataEntregados = array_fill(0, 12, 0);

foreach ($chartDataRaw as $row) {
    $mesIndex = intval($row['mes']) - 1;
    if ($mesIndex >= 0 && $mesIndex < 12) {
        $dataTotal[$mesIndex] = intval($row['total']);
        $dataEntregados[$mesIndex] = intval($row['entregados']);
    }
}
?>
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
                        <span class="stat-value"><?php echo $stats['pedidos_mes']; ?></span>
                        <span class="stat-change positive">+12% vs mes anterior</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);">
                        🚴
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">En Tránsito</span>
                        <span class="stat-value"><?php echo $stats['en_transito']; ?></span>
                        <span class="stat-change neutral">Actualizaciones en tiempo real</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);">
                        💰
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Saldo Pendiente</span>
                        <span class="stat-value">$<?php echo number_format($stats['saldo_pendiente'], 0, ',', '.'); ?></span>
                        <span class="stat-change negative">Por pagar</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);">
                        ✓
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Entregados</span>
                        <span class="stat-value"><?php echo $stats['entregados_total']; ?></span>
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
                                    <?php if (empty($ultimosPedidos)): ?>
                                        <tr><td colspan="5" style="text-align:center;">No hay envíos recientes</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($ultimosPedidos as $pedido): ?>
                                            <?php 
                                                // Determinar clase CSS según estado
                                                $statusClass = 'status-pending';
                                                $statusText = ucfirst(str_replace('_', ' ', $pedido['estado']));
                                                if ($pedido['estado'] == 'entregado') $statusClass = 'status-delivered';
                                                elseif (in_array($pedido['estado'], ['en_transito', 'en_proceso'])) $statusClass = 'status-in-transit';
                                                elseif ($pedido['estado'] == 'cancelado') $statusClass = 'status-cancelled'; // Asegúrate de tener CSS para esto o usa pending
                                            ?>
                                            <tr>
                                                <td><span class="order-id">#<?php echo htmlspecialchars($pedido['numero_guia']); ?></span></td>
                                                <td><?php echo htmlspecialchars($pedido['direccion_destino']); ?></td>
                                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                <td><?php echo date('d/m/Y', strtotime($pedido['fecha_creacion'])); ?></td>
                                                <td>
                                                    <button class="icon-btn" title="Ver detalles">👁️</button>
                                                    <?php if ($pedido['estado'] == 'entregado'): ?>
                                                        <button class="icon-btn" title="Descargar">⬇️</button>
                                                    <?php else: ?>
                                                        <button class="icon-btn" title="Rastrear">📍</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
    
    <!-- Pasar datos de PHP a JS -->
    <script>
        window.dashboardChartData = {
            labels: <?php echo json_encode($chartLabels); ?>,
            total: <?php echo json_encode($dataTotal); ?>,
            entregados: <?php echo json_encode($dataEntregados); ?>
        };
    </script>
    <script src="../../public/js/inicioCliente.js"></script>
</body>
</html>