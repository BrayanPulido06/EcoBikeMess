<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - EcoBikeMess</title>
    <link rel="stylesheet" href="../public/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🚴 EcoBikeMess</h1>
            <p>Crea una nueva contraseña</p>
        </div>

        <div class="login-body">
            <?php
            // Verificar que llegue un token
            $token = $_GET['token'] ?? '';
            if (empty($token)) {
                echo "<div class='form-container active'><p class='error-message' style='display:block; text-align:center;'>Token no válido o faltante.</p>";
                echo "<div class='form-footer'><a href='login.php' class='link'>Volver al inicio</a></div></div>";
            } else {
            ?>
                <form action="../controller/recuperarContrasenaController.php" method="POST" class="form-container active">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <h2>Nueva Contraseña</h2>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <p class="error-message" style="color: red; text-align: center; display: block;">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </p>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="password">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la contraseña" required>
                    </div>

                    <button type="submit" class="btn-submit">Cambiar Contraseña</button>
                    
                    <div class="form-footer">
                        <p><a href="login.php" class="link">Cancelar</a></p>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
</body>
</html>
