<nav class="navbar-top">
    <div class="navbar-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebarMobile()">
            <span class="menu-icon">☰</span>
        </button>
        
        <div class="page-title">
            <h1 id="pageTitle">Dashboard</h1>
            <p class="page-subtitle" id="pageSubtitle">Bienvenido de nuevo</p>
        </div>
    </div>

    <div class="navbar-right">
        <!-- Notificaciones -->
        <div class="notification-btn" id="notificationBtn">
            <span class="notification-icon">🔔</span>
            <span class="notification-badge">3</span>
        </div>

        <!-- Panel de notificaciones -->
        <div class="notification-panel" id="notificationPanel">
            <div class="notification-header">
                <h3>Notificaciones</h3>
                <button class="mark-read-btn">Marcar todas como leídas</button>
            </div>
            <div class="notification-list">
                <div class="notification-item unread">
                    <span class="notif-icon">📦</span>
                    <div class="notif-content">
                        <p class="notif-title">Paquete entregado</p>
                        <p class="notif-text">Tu pedido #12345 fue entregado exitosamente</p>
                        <span class="notif-time">Hace 5 min</span>
                    </div>
                </div>
                <div class="notification-item unread">
                    <span class="notif-icon">💰</span>
                    <div class="notif-content">
                        <p class="notif-title">Pago recibido</p>
                        <p class="notif-text">Se registró un pago de $50.000</p>
                        <span class="notif-time">Hace 1 hora</span>
                    </div>
                </div>
                <div class="notification-item unread">
                    <span class="notif-icon">🚴</span>
                    <div class="notif-content">
                        <p class="notif-title">Mensajero asignado</p>
                        <p class="notif-text">Juan Pérez fue asignado a tu pedido</p>
                        <span class="notif-time">Hace 2 horas</span>
                    </div>
                </div>
            </div>
            <div class="notification-footer">
                <a href="notificaciones.php">Ver todas las notificaciones</a>
            </div>
        </div>

        <!-- Menú de usuario -->
        <div class="user-menu" id="userMenu">
            <button class="user-btn" id="userBtn">
                <img src="../public/img/default-avatar.png" alt="Usuario" class="user-avatar" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%224%22 fill=%22%235cb85c%22/%3E%3Cpath d=%22M12 14c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z%22 fill=%22%235cb85c%22/%3E%3C/svg%3E'">
                <div class="user-info">
                    <span class="user-name">Juan Pérez</span>
                    <span class="user-role">Cliente</span>
                </div>
                <span class="dropdown-arrow">▼</span>
            </button>

            <!-- Dropdown del usuario -->
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <img src="../public/img/default-avatar.png" alt="Usuario" class="dropdown-avatar" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%224%22 fill=%22%235cb85c%22/%3E%3Cpath d=%22M12 14c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z%22 fill=%22%235cb85c%22/%3E%3C/svg%3E'">
                    <div class="dropdown-user-info">
                        <span class="dropdown-name">Juan Pérez</span>
                        <span class="dropdown-email">juan@example.com</span>
                    </div>
                </div>
                <ul class="dropdown-menu">
                    <li>
                        <a href="miPerfil.php" class="dropdown-item">
                            <span class="dropdown-icon">👤</span>
                            <span>Mi Perfil</span>
                        </a>
                    </li>
                    <li>
                        <a href="actualizarDatos.php" class="dropdown-item">
                            <span class="dropdown-icon">⚙️</span>
                            <span>Actualizar Datos</span>
                        </a>
                    </li>
                    <li>
                        <a href="configuracion.php" class="dropdown-item">
                            <span class="dropdown-icon">🔧</span>
                            <span>Configuración</span>
                        </a>
                    </li>
                    <li class="dropdown-divider"></li>
                    <li>
                        <a href="../controllers/logout.php" class="dropdown-item logout">
                            <span class="dropdown-icon">🚪</span>
                            <span>Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script src="../../public/js/clienteNavbar.js"></script>