<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <span class="logo-icon">🚴</span>
            <span class="logo-text">EcoBikeMess</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <span class="toggle-icon">☰</span>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="/ecobikemess/views/Clientes/inicioCliente.php" class="nav-link">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Inicio</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/ecobikemess/views/Clientes/enviarPaquete.php" class="nav-link">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Enviar Paquete</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/ecobikemess/views/Clientes/misPedidos.php" class="nav-link">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Mis Pedidos</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/ecobikemess/views/Clientes/seguimiento.php" class="nav-link">
                    <span class="nav-icon">🗺️</span>
                    <span class="nav-text">Seguimiento</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/ecobikemess/views/Clientes/facturacion.php" class="nav-link">
                    <span class="nav-icon">💰</span>
                    <span class="nav-text">Facturación</span>
                </a>
            </li>

            <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'colaborador'): ?>
            <li class="nav-item">
                <a href="/ecobikemess/views/Clientes/equipoTrabajo.php" class="nav-link">
                    <span class="nav-icon">💬</span>
                    <span class="nav-text">Equipo de Trabajo</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-plan">
            <span class="plan-icon">⭐</span>
            <div class="plan-info">
                <span class="plan-name">Plan Cliente</span>
                <span class="plan-status">Activo</span>
            </div>
        </div>
    </div>
</aside>

<script src="/ecobikemess/public/js/clienteSidebar.js"></script>