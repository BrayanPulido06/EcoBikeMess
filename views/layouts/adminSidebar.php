<?php
require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../includes/auth.php';

$adminNavItems = [
    ['permission' => 'admin_dashboard', 'route' => 'admin.dashboard', 'icon' => '📊', 'text' => 'Dashboard'],
    ['permission' => 'admin_packages', 'route' => 'admin.packages', 'icon' => '📦', 'text' => 'Gestion Paquetes'],
    ['permission' => 'admin_create_shipment', 'route' => 'admin.create-shipment', 'icon' => '📝', 'text' => 'Digitar Envio'],
    ['permission' => 'admin_collections', 'route' => 'admin.collections', 'icon' => '🚚', 'text' => 'Recolecciones'],
    ['permission' => 'admin_billing', 'route' => 'admin.billing', 'icon' => '💳', 'text' => 'Facturacion'],
    ['permission' => 'admin_notes', 'route' => 'admin.notes', 'icon' => '🧾', 'text' => 'Notas Admin'],
    ['permission' => 'admin_users', 'route' => 'admin.users', 'icon' => '👥', 'text' => 'Usuarios'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img class="logo-icon" src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width: 55px; vertical-align: middle;">
            <span class="logo-text">EcoBikeMess</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <span class="toggle-icon">☰</span>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($adminNavItems as $item): ?>
                <?php if (!adminCanAccess($item['permission'])) continue; ?>
                <li class="nav-item">
                    <a href="<?php echo htmlspecialchars(route_url($item['route']), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
                        <span class="nav-icon"><?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="nav-text"><?php echo htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-plan">
            <span class="plan-icon">🛡️</span>
            <div class="plan-info">
                <span class="plan-name">Administrador</span>
                <span class="plan-status">En linea</span>
            </div>
        </div>
    </div>
</aside>

<script src="../../public/js/clienteSidebar.js"></script>
