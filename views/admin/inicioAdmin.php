<?php
session_start();

// Verificar permisos de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrador')) {
    header("Location: ../login.php?error=Debes iniciar sesión.");
    exit();
}

require_once '../../models/inicioAdminModels.php';

// Obtener datos reales
$model = new InicioAdminModel(); // La clase se llama igual en singular dentro del archivo
$stats = $model->obtenerEstadisticasDia(); // Usamos el método existente en este modelo
$chartData = $model->obtenerDatosGraficaSemana(); // Agregaremos este método al modelo existente

// Calcular porcentaje de cambio en ingresos (vs ayer)
$ingresosHoy = $stats['ingresos_dia'];
$ingresosAyer = $stats['ingresos_ayer'];
$cambioIngresos = 0;
if ($ingresosAyer > 0) {
    $cambioIngresos = (($ingresosHoy - $ingresosAyer) / $ingresosAyer) * 100;
} elseif ($ingresosHoy > 0) {
    $cambioIngresos = 100; // Si ayer fue 0 y hoy hay ingresos
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Mensajería</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/inicioAdmin.css">
    <link rel="stylesheet" href="../../public/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <div class="dashboard-container app-shell">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>¡Bienvenido, <span id="adminName"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrador'); ?></span>!</h1>
                <p class="current-date" id="currentDate"></p>
            </div>
            <div class="header-actions">
                <div class="last-update">
                    Última actualización: <span id="lastUpdate"></span>
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-info">
                    <h3>Paquetes Hoy</h3>
                    <p class="stat-value" id="totalPaquetes"><?php echo number_format($stats['paquetes_ingresados']); ?></p>
                    <span class="stat-change" id="compPaquetes">Ingresados hoy</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚚</div>
                <div class="stat-info">
                    <h3>En Tránsito</h3>
                    <p class="stat-value" id="enTransito"><?php echo number_format($stats['en_transito']); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3>Entregados Hoy</h3>
                    <p class="stat-value" id="entregados"><?php echo number_format($stats['entregados']); ?></p>
                    <span class="stat-change" id="compEntregados">Completados hoy</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📥</div>
                <div class="stat-info">
                    <h3>Recolecciones</h3>
                    <p class="stat-value">
                        <span id="recoleccionesPend"><?php echo $stats['recolecciones_pendientes']; ?></span> <small>Pend.</small> / 
                        <span id="recoleccionesComp"><?php echo $stats['recolecciones_completadas']; ?></span> <small>Comp.</small>
                    </p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:24px;height:24px;"></div>
                <div class="stat-info">
                    <h3>Mensajeros Activos</h3>
                    <p class="stat-value" id="mensajerosActivos"><?php echo $stats['mensajeros_activos']; ?></p>
                </div>
            </div>
            <div class="stat-card highlight">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>Ingresos Hoy</h3>
                    <p class="stat-value" id="ingresos">$<?php echo number_format($stats['ingresos_dia'], 0, ',', '.'); ?></p>
                    <span class="stat-change <?php echo $cambioIngresos >= 0 ? 'positive' : 'negative'; ?>" id="compIngresos">
                        <?php echo ($cambioIngresos >= 0 ? '+' : '') . number_format($cambioIngresos, 1); ?>% vs ayer
                    </span>
                </div>
            </div>
        </section>

        <!-- Charts Section -->
        <section class="charts-section">
            <div class="chart-container full-width">
                <div class="chart-header">
                    <h3>Movimientos de Entrega</h3>
                    <div class="chart-filters">
                        <button class="filter-btn active" data-period="dia">Día</button>
                        <button class="filter-btn" data-period="semana">Semana</button>
                        <button class="filter-btn" data-period="mes">Mes</button>
                        <button class="filter-btn" data-period="anio">Año</button>
                    </div>
                </div>
                <canvas id="chartMovimientos"></canvas>
            </div>
        </section>
    </div>

    <script>
        // Pasar datos de PHP a JS para la gráfica
        window.adminChartData = <?php echo json_encode($chartData); ?>;
    </script>
    <script src="../../public/js/inicioAdmin.js"></script>
</body>
</html>
