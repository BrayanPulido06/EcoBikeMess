<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - EcoBikeMess</title>
    <link rel="stylesheet" href="../public/css/login.css">
    <style>
        /* Estabilización del campo de contraseña */
        .password-input {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }

        .password-input input {
            width: 100% !important;
            /* Copiamos los estilos exactos de login.css para que no se pierdan al cambiar el tipo */
            padding: 14px 45px 14px 16px !important; 
            border: 2px solid #e8f5f1 !important;
            border-radius: 10px !important;
            font-size: 1rem !important;
            background: #f8fdf9 !important;
            transition: all 0.3s ease !important;
        }

        .toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 35px; /* Ancho fijo para evitar saltos de diseño */
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            padding: 0;
            cursor: pointer;
            z-index: 2;
        }

        /* Mantenemos el estilo de enfoque (verde) igual que en los demás campos */
        .password-input input:focus {
            outline: none !important;
            border-color: #5cb85c !important;
            background: white !important;
            box-shadow: 0 0 0 3px rgba(92, 184, 92, 0.1) !important;
        }

        .eye-icon {
            font-size: 1.2rem;
            display: inline-block;
            line-height: 1;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">

        <!-- Header con logo -->
        <div class="login-header">
            <h1>
                <img src="../public/img/Logo_Blanco_Trasparente_Circulo.png" alt="Logo" style="width: 100px; vertical-align: middle;">EcoBikeMess</h1>
            <p>Mensajería Ecológica para un Futuro Sostenible</p>
        </div>

        <!-- Cuerpo del formulario -->
        <div class="login-body">
            <!-- Formulario de Inicio de Sesión -->
            <form id="loginForm" class="form-container active" action="../controller/loginController.php" method="POST">
                    <h2>Iniciar Sesión</h2>
                    <p class="form-subtitle">Bienvenido de nuevo</p>

                    <?php if (isset($_GET['mensaje'])): ?>
                        <p class="success-message show">
                            <?php echo htmlspecialchars($_GET['mensaje']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <p class="error-message" style="text-align: center; display: block;">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </p>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="loginEmail">Correo Electrónico</label>
                        <input type="email" id="loginEmail" name="correo" placeholder="tu@email.com" value="<?php echo isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : ''; ?>" required>
                        <span class="error-message" id="loginEmailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="loginPassword">Contraseña</label>
                        <div class="password-input">
                            <input type="password" id="loginPassword" name="password" placeholder="••••••••" value="<?php echo isset($_COOKIE['remember_password']) ? htmlspecialchars($_COOKIE['remember_password']) : ''; ?>" required>
                            <button type="button" class="toggle-password" data-target="loginPassword">
                                <span class="eye-icon">👁️</span>
                            </button>
                        </div>
                        <span class="error-message" id="loginPasswordError"></span>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" id="rememberMe" name="remember_me" <?php echo isset($_COOKIE['remember_email']) ? 'checked' : ''; ?>>
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
