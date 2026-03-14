<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - EcoBikeMess</title>
    <link rel="stylesheet" href="../public/css/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Header con logo -->
        <div class="login-header">
            <h1>
                <img src="../public/img/Logo_Circulo_Fondoblanco.png" alt="Logo" style="width: 50px; vertical-align: middle;"> 
                EcoBikeMess
            </h1>
            <p>Mensajería Ecológica para un Futuro Sostenible</p>
        </div>

        <!-- Cuerpo del formulario -->
        <div class="login-body">
            <!-- Formulario de Recuperación de Contraseña -->
            <form id="forgotPasswordForm" class="form-container active" action="recovery.php" method="POST">
                    <h2>Recuperar Contraseña</h2>
                    <p class="form-subtitle">Te enviaremos un enlace de recuperación</p>

                    <?php if (isset($_GET['mensaje'])): ?>
                        <p class="success-message" style="color: green; text-align: center; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($_GET['mensaje']); ?>
                        </p>
                        <?php if (isset($_GET['debug_link'])): ?>
                            <p style="font-size: 0.8em; text-align: center; background: #f0f0f0; padding: 5px; word-break: break-all;">
                                <a href="<?php echo htmlspecialchars($_GET['debug_link']); ?>">LINK DE PRUEBA (Click aquí)</a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <p class="error-message" style="color: #c62828; background: #ffebee; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 10px; display: block;">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </p>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="forgotEmail">Correo Electrónico</label>
                        <input type="email" id="forgotEmail" name="email" placeholder="tu@email.com" required>
                        <span class="error-message" id="forgotEmailError"></span>
                    </div>

                    <button type="submit" class="btn-submit">Enviar Enlace</button>

                    <div class="form-footer">
                        <p><a href="login.php" class="link">← Volver al inicio de sesión</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../public/js/login.js"></script>
</body>
</html>