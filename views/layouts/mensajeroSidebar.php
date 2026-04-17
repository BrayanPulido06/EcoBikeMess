<?php
require_once __DIR__ . '/../../includes/paths.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$nombreCompleto = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_lastname'] ?? ''));
if ($nombreCompleto === '') {
    $nombreCompleto = 'Mensajero';
}
?>
<nav class="side-menu" id="sideMenu">
    <div class="menu-header">
        <div class="user-avatar">
            <img src="../../public/img/default-avatar.png" alt="Avatar" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%224%22 fill=%22%235cb85c%22/%3E%3Cpath d=%22M12 14c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z%22 fill=%22%235cb85c%22/%3E%3C/svg%3E'">
        </div>
        <h3><?php echo htmlspecialchars($nombreCompleto); ?></h3>
        <p>Mensajero Activo</p>
    </div>
    <ul class="menu-list">
        <li><a href="<?php echo htmlspecialchars(route_url('messenger.dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="active">📊 Inicio</a></li>
        <li><a href="<?php echo htmlspecialchars(route_url('messenger.packages'), ENT_QUOTES, 'UTF-8'); ?>">📦 Mis Paquetes</a></li>
        <li><a href="<?php echo htmlspecialchars(route_url('messenger.history'), ENT_QUOTES, 'UTF-8'); ?>">📚 Historial</a></li>
        <li><a href="<?php echo htmlspecialchars(route_url('messenger.pickups'), ENT_QUOTES, 'UTF-8'); ?>">📦 Recolecciones</a></li>
        <li><a href="<?php echo htmlspecialchars(route_url('messenger.profile'), ENT_QUOTES, 'UTF-8'); ?>">👤 Mi Perfil</a></li>
        <li><a href="<?php echo htmlspecialchars(route_url('logout'), ENT_QUOTES, 'UTF-8'); ?>" class="logout">🚪 Cerrar Sesión</a></li>
    </ul>
</nav>

<!-- Overlay del menú -->
<div class="menu-overlay" id="menuOverlay"></div>
