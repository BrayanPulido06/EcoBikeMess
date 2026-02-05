<?php
// Asegurarse de que la sesión esté iniciada para acceder a $_SESSION
if (session_status() === PHP_SESSION_NONE) session_start();
?>
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
        <!-- Menú de usuario -->
        <div class="user-menu" id="userMenu">
            <button class="user-btn" id="userBtn">
                <img src="../../public/img/default-avatar.png" alt="Usuario" class="user-avatar" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%224%22 fill=%22%235cb85c%22/%3E%3Cpath d=%22M12 14c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z%22 fill=%22%235cb85c%22/%3E%3C/svg%3E'">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars(($_SESSION['user_name'] ?? 'Usuario') . ' ' . ($_SESSION['user_lastname'] ?? '')); ?></span>
                    <span class="user-role"><?php echo ucfirst($_SESSION['user_role'] ?? 'Usuario'); ?></span>
                </div>
                <span class="dropdown-arrow">▼</span>
            </button>

            <!-- Dropdown del usuario -->
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <img src="../../public/img/default-avatar.png" alt="Usuario" class="dropdown-avatar" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%224%22 fill=%22%235cb85c%22/%3E%3Cpath d=%22M12 14c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z%22 fill=%22%235cb85c%22/%3E%3C/svg%3E'">
                    <div class="dropdown-user-info">
                        <span class="dropdown-name"><?php echo htmlspecialchars(($_SESSION['user_name'] ?? 'Usuario') . ' ' . ($_SESSION['user_lastname'] ?? '')); ?></span>
                        <span class="dropdown-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
                    </div>
                </div>
                <ul class="dropdown-menu">
                    <li>
                        <a href="../layouts/miPerfilAdmin.php" class="dropdown-item">
                            <span class="dropdown-icon">👤</span>
                            <span>Mi Perfil</span>
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
                        <a href="../../controller/logout.php" class="dropdown-item logout">
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