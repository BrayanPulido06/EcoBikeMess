<?php
session_start();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <div class="dashboard-container" style="margin-left: 250px; margin-top: 60px;">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="welcome-section">
                <h1>¡Bienvenido, <span id="adminName">Administrador</span>!</h1>
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
                    <h3>Paquetes Ingresados</h3>
                    <p class="stat-value" id="totalPaquetes">0</p>
                    <span class="stat-change" id="compPaquetes"></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚚</div>
                <div class="stat-info">
                    <h3>En Tránsito</h3>
                    <p class="stat-value" id="enTransito">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3>Entregados</h3>
                    <p class="stat-value" id="entregados">0</p>
                    <span class="stat-change" id="compEntregados"></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📥</div>
                <div class="stat-info">
                    <h3>Recolecciones</h3>
                    <p class="stat-value"><span id="recoleccionesPend">0</span> <small>Pend.</small> / <span id="recoleccionesComp">0</span> <small>Comp.</small></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚴</div>
                <div class="stat-info">
                    <h3>Mensajeros Activos</h3>
                    <p class="stat-value" id="mensajerosActivos">0</p>
                </div>
            </div>
            <div class="stat-card highlight">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>Ingresos Hoy</h3>
                    <p class="stat-value" id="ingresos">$0</p>
                    <span class="stat-change" id="compIngresos"></span>
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

    <script src="../../public/js/inicioAdmin.js"></script>
</body>
</html>