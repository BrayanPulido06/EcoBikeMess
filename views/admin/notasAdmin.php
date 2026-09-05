<?php
require_once __DIR__ . '/../../includes/paths.php';
require_once __DIR__ . '/../../includes/auth.php';
requireWebAuth(['admin', 'administrador']);
$notasAdminCssVersion = @filemtime(__DIR__ . '/../../public/css/notasAdmin.css') ?: time();
$notasAdminJsVersion = @filemtime(__DIR__ . '/../../public/js/notasAdmin.js') ?: time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas Admin - EcoBikeMess</title>
    <link rel="icon" href="<?php echo htmlspecialchars(app_asset_url('img/Logo_Negro_Transparente.png'), ENT_QUOTES, 'UTF-8'); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/clienteSidebar.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/clienteNavbar.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/responsive.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_asset_url('css/notasAdmin.css') . '?v=' . $notasAdminCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="notas-admin-page">
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <main class="notas-admin-shell app-shell">
        <section
            id="notasAdminApp"
            class="notas-admin-app"
            data-endpoint="<?php echo htmlspecialchars(app_controller_url('notasAdminController.php'), ENT_QUOTES, 'UTF-8'); ?>"
        >
            <header class="notas-admin-header">
                <div>
                    <h1>Notas Admin</h1>
                    <p>Organiza listas y tarjetas de seguimiento interno.</p>
                </div>
                <label class="notas-search">
                    <span>Filtrar titulos</span>
                    <input type="search" id="notasAdminSearch" placeholder="Buscar lista o tarjeta">
                </label>
            </header>

            <div id="notasAdminStatus" class="notas-admin-status">Cargando notas...</div>
            <div id="notasAdminBoard" class="notas-board" aria-live="polite"></div>
        </section>
    </main>

    <div id="notasAdminModal" class="notas-modal-backdrop notas-hidden" aria-hidden="true">
        <div class="notas-modal" role="dialog" aria-modal="true" aria-labelledby="notasAdminModalTitle">
            <div class="notas-modal-head">
                <h2 id="notasAdminModalTitle">Tarjeta</h2>
                <button type="button" class="notas-icon-btn" data-close-modal aria-label="Cerrar">x</button>
            </div>
            <form id="notasAdminCardForm" class="notas-card-form">
                <input type="hidden" name="tarjeta_id" value="">
                <input type="hidden" name="lista_id" value="">
                <label>
                    <span>Titulo</span>
                    <input type="text" name="titulo" maxlength="180" required>
                </label>
                <label>
                    <span>Descripcion</span>
                    <textarea name="descripcion" rows="6"></textarea>
                </label>
                <div class="notas-modal-actions">
                    <button type="button" class="notas-btn danger notas-hidden" data-role="delete-card-modal">Eliminar</button>
                    <button type="button" class="notas-btn ghost" data-close-modal>Cancelar</button>
                    <button type="submit" class="notas-btn primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo htmlspecialchars(app_asset_url('js/notasAdmin.js') . '?v=' . $notasAdminJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
