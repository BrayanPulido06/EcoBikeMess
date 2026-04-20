<?php
require_once __DIR__ . '/../../includes/paths.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$nombreCompleto = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_lastname'] ?? ''));
if ($nombreCompleto === '') {
    $nombreCompleto = 'Mensajero';
}

$resolverFotoPerfil = static function (?string $ruta): string {
    $ruta = trim((string) $ruta);
    if ($ruta === '') {
        return '../../public/img/default-avatar.png';
    }

    if (preg_match('#^https?://#i', $ruta) || str_starts_with($ruta, 'data:image/')) {
        return $ruta;
    }

    $projectRoot = dirname(__DIR__, 2);
    $candidatas = [];

    $rutaNormalizada = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, ltrim($ruta, '/\\'));
    $candidatas[] = $projectRoot . DIRECTORY_SEPARATOR . $rutaNormalizada;

    if (strpos($rutaNormalizada, 'uploads' . DIRECTORY_SEPARATOR) !== 0) {
        $candidatas[] = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'mensajeros' . DIRECTORY_SEPARATOR . basename($rutaNormalizada);
    }

    $rutaFisica = null;
    foreach ($candidatas as $candidata) {
        if (is_file($candidata) && is_readable($candidata)) {
            $rutaFisica = $candidata;
            break;
        }
    }

    if ($rutaFisica === null) {
        return '../../public/img/default-avatar.png';
    }

    $extension = strtolower(pathinfo($rutaFisica, PATHINFO_EXTENSION));
    $mime = match ($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => 'application/octet-stream',
    };

    $contenido = @file_get_contents($rutaFisica);
    if ($contenido === false) {
        return '../../public/img/default-avatar.png';
    }

    return 'data:' . $mime . ';base64,' . base64_encode($contenido);
};

$fotoSidebarOriginal = trim((string) ($_SESSION['user_photo'] ?? ''));
$fotoSidebarCache = $_SESSION['user_photo_resolved'] ?? '';
$fotoSidebar = $fotoSidebarOriginal;

if ($fotoSidebar === '') {
    require_once '../../models/conexionGlobal.php';
    try {
        if (!empty($_SESSION['user_id'])) {
            $conn = conexionDB();
            $stmtFoto = $conn->prepare("SELECT foto FROM mensajeros WHERE usuario_id = :usuario_id LIMIT 1");
            $stmtFoto->execute([':usuario_id' => $_SESSION['user_id']]);
            $fotoSidebar = trim((string) ($stmtFoto->fetchColumn() ?: ''));
            if ($fotoSidebar !== '') {
                $_SESSION['user_photo'] = $fotoSidebar;
                $fotoSidebarOriginal = $fotoSidebar;
            }
        }
    } catch (Throwable $e) {
        $fotoSidebar = '';
    }
}

if ($fotoSidebar !== '' && $fotoSidebar === $fotoSidebarOriginal && is_string($fotoSidebarCache) && $fotoSidebarCache !== '') {
    $fotoSidebar = $fotoSidebarCache;
} else {
    $fotoSidebar = $resolverFotoPerfil($fotoSidebar);
    if ($fotoSidebarOriginal !== '') {
        $_SESSION['user_photo_resolved'] = $fotoSidebar;
    }
}
?>
<nav class="side-menu" id="sideMenu">
    <div class="menu-header">
        <div class="user-avatar">
            <img src="<?php echo htmlspecialchars($fotoSidebar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22%3E%3Ccircle cx=%2212%22 cy=%228%22 r=%224%22 fill=%22%235cb85c%22/%3E%3Cpath d=%22M12 14c-4 0-8 2-8 4v2h16v-2c0-2-4-4-8-4z%22 fill=%22%235cb85c%22/%3E%3C/svg%3E'">
        </div>
        <h3><?php echo htmlspecialchars($nombreCompleto); ?></h3>
        <p>Mensajero Activo</p>
    </div>

    <ul class="menu-list">
        <li><a href="<?php echo htmlspecialchars(route_url('messenger.dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Inicio</a></li>

        <li class="menu-group">
            <div class="menu-section-title">Mensajeria</div>
            <ul class="submenu">
                <li><a href="<?php echo htmlspecialchars(route_url('messenger.pickups'), ENT_QUOTES, 'UTF-8'); ?>">Recolecciones</a></li>
                <li><a href="<?php echo htmlspecialchars(route_url('messenger.packages'), ENT_QUOTES, 'UTF-8'); ?>">Mis Paquetes</a></li>
                <li><a href="<?php echo htmlspecialchars(route_url('messenger.history'), ENT_QUOTES, 'UTF-8'); ?>">Historial</a></li>
                <!--<li><a href="<?php echo htmlspecialchars(route_url('messenger.billing'), ENT_QUOTES, 'UTF-8'); ?>">Facturación</a></li>-->
            </ul>
        </li>

        <li class="menu-group">
            <div class="menu-section-title">Paqueteria</div>
            <ul class="submenu">
                <li><a href="<?php echo htmlspecialchars(route_url('messenger.send-package'), ENT_QUOTES, 'UTF-8'); ?>">Crear envio</a></li>
                <li><a href="<?php echo htmlspecialchars(route_url('messenger.orders'), ENT_QUOTES, 'UTF-8'); ?>">Mis pedidos</a></li>
            </ul>
        </li>

        <li><a href="<?php echo htmlspecialchars(route_url('messenger.profile'), ENT_QUOTES, 'UTF-8'); ?>">Mi Perfil</a></li>
        <li><a href="<?php echo htmlspecialchars(route_url('logout'), ENT_QUOTES, 'UTF-8'); ?>" class="logout">Cerrar Sesion</a></li>
    </ul>
</nav>

<div class="menu-overlay" id="menuOverlay"></div>
