<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - EcoBikeMess</title>
    <link rel="stylesheet" href="../public/css/login.css">
</head>
<body>
    <div class="login-container">

        <!-- Header con logo -->
        <div class="login-header">
            <h1>🚴 EcoBikeMess</h1>
            <p>Mensajería Ecológica para un Futuro Sostenible</p>
        </div>

        <!-- Cuerpo del formulario -->
        <div class="login-body">
            <!-- Formulario de Inicio de Sesión -->
            <form id="loginForm" class="form-container active" action="../controller/loginController.php" method="POST">
                    <h2>Iniciar Sesión</h2>
                    <p class="form-subtitle">Bienvenido de nuevo</p>

                    <?php if (isset($_GET['mensaje'])): ?>
                        <p class="error-message" style="color: red; text-align: center; display: block;">
                            <?php echo htmlspecialchars($_GET['mensaje']); ?>
                        </p>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="loginEmail">Correo Electrónico</label>
                        <input type="email" id="loginEmail" name="correo" placeholder="tu@email.com" required>
                        <span class="error-message" id="loginEmailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="loginPassword">Contraseña</label>
                        <div class="password-input">
                            <input type="password" id="loginPassword" name="password" placeholder="••••••••" required>
                            <button type="button" class="toggle-password" data-target="loginPassword">
                                <span class="eye-icon">👁️</span>
                            </button>
                        </div>
                        <span class="error-message" id="loginPasswordError"></span>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" id="rememberMe">
                            <span>Recordarme</span>
                        </label>
                        <a href="recuperarContraseña.php" class="link">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn-submit">Iniciar Sesión</button>

                    <div class="form-footer">
                        <p>¿No tienes una cuenta? <a href="crearCuenta.php" class="link">Crear cuenta</a></p>
                    </div>
                    <!-- Botón para volver -->
                    <a class="volver" href="../index.php">← Volver al inicio</a>
                </form>
            </div>
        </div>
    </div>

    <script src="../public/js/login.js"></script>
</body>
</html>