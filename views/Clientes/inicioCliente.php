<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array(($_SESSION['user_role'] ?? ''), ['cliente', 'colaborador'], true)) {
    header("Location: ../login.php?error=Debes iniciar sesión.");
    exit();
}

require_once '../../models/inicioClienteModel.php';

// Inicializar variables por defecto
$stats = ['pedidos_mes' => 0, 'en_transito' => 0, 'saldo_pendiente' => 0, 'entregados_total' => 0, 'pedidos_colaboradores' => 0];
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
    <link rel="icon" href="../../public/img/Logo_Blanco_Trasparente_Circulo.png" type="image/png">
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/inicioCliente.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <link rel="stylesheet" href="../../public/css/clientesTheme.css">
    <style>
        /* Estilos para la nueva tarjeta de información */
        .important-info-card {
            grid-column: 1 / -1; /* Ocupa todo el ancho de la fila */
            flex-direction: column;
            align-items: flex-start;
        }
        .important-info-card .stat-card-header {
            display: flex;
            align-items: center;
            gap: 20px;
            width: 100%;
        }
        .important-info-card .info-details {
            display: none; /* Oculto por defecto */
            width: 100%;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eef2f7;
            font-size: 0.95rem;
            color: #5a6c7d;
            line-height: 1.5;
        }
        .important-info-card .btn-details {
            margin-top: 1rem;
            background: none;
            border: none;
            color: #5cb85c;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
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
                <!-- Tarjeta de Información Importante -->
                <div class="stat-card important-info-card">
                    <div class="stat-card-header">
                        <div class="stat-info">
                            <h2>Asi es como gestionamos tu pedido</h2>
                        </div>
                    </div>
                    <div class="info-details">
                        <p>1. Recolección: Pasaremos por tu ubicación entre 10:00 a.m. y 12:30 p.m. para recoger tu paquete.</strong></p>
                        <p>2. Consolidación: Tu pedido llegará a nuestro centro de acopio en Chapinero alrededor de la 1:00 p.m., donde lo prepararemos para su distribución.</p>
                        <p>3. Distribución:</p>
                        <p>- Los pedidos salen clasificados por zonas alrededor de las 2:00pm</p>
                        <p>- Las entregas se realizan durante el resto del día (el horario exacto puede variar según ubicación y volumen de pedidos).</p>
                        <p>4. Notificación: El cliente final recibirá un aviso por WhatsApp o por llamada antes del mensajero llegar, para asegurar que esté disponible para recibir</p>
                        <p>5. Confirmación: Al cierre del día, te enviaremos el soporte de entrega.</p>
                    </div>
                    <button class="btn-details" id="toggleInfoBtn">
                        Ver Información ▼
                    </button>
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
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
    <script>
        // Script para la nueva tarjeta de información
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleInfoBtn');
            const infoDetails = document.querySelector('.important-info-card .info-details');

            if (toggleBtn && infoDetails) {
                toggleBtn.addEventListener('click', function() {
                    const isVisible = infoDetails.style.display === 'block';
                    infoDetails.style.display = isVisible ? 'none' : 'block';
                    toggleBtn.innerHTML = isVisible ? 'Ver detalles ▼' : 'Ocultar detalles ▲';
                });
            }
        });
    </script>
    <script src="../../public/js/inicioCliente.js"></script>
</body>
</html>
