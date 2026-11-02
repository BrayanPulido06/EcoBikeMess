<?php require_once __DIR__ . '/../includes/paths.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars(app_url('/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        window.APP_BASE_PATH = <?php echo json_encode(app_url(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <title>Recuperar Contraseña - EcoBikeMess</title>
    <link rel="icon" href="public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="public/css/login.css">
    <link rel="stylesheet" href="public/css/responsive.css">
</head>
<body>
    <div class="login-container">
        <!-- Header con logo -->
        <div class="login-header">
            <h1>
                <img src="public/img/Logo_Blanco_Trasparente_Circulo.png" alt="Logo" style="width: 90px; vertical-align: middle;"> 
                EcoBikeMess
            </h1>
            <p>Mensajería Ecológica para un Futuro Sostenible</p>
        </div>

        <!-- Cuerpo del formulario -->
        <div class="login-body">
            <!-- Formulario de Recuperación de Contraseña -->
            <div class="form-container active">
                <h2>Recuperar contrasena</h2>
                <p class="form-subtitle">La recuperacion por correo no esta configurada. Contacta al equipo de soporte.</p>
                <?php if (isset($_GET['error'])): ?>
                    <p class="error-message" style="color: #c62828; background: #ffebee; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 10px; display: block;">
                        <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                <?php endif; ?>
                <div class="form-footer">
                    <p><a href="<?php echo htmlspecialchars(route_url('login'), ENT_QUOTES, 'UTF-8'); ?>" class="link">Volver al inicio de sesion</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="public/js/login.js"></script>
</body>
</html>
