<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - LodgeHub</title>
    <link rel="stylesheet" href="../../public/assets/stylesLogin.css"> <!-- Enlaza el archivo CSS -->
</head>

<body>
    <div class="page-background">
        <div class="borde-container-login">
            <div class="login-container">

                <!-- Círculos dentro del contenedor -->
                <div class="circle circle-1"></div>
                <div class="circle circle-2"></div>
                <div class="circle circle-3"></div>
                <div class="circle circle-4"></div>

                <div class="recuperarcontraseña">
                    <form action="validarNuevaContraseña.php" method="post" id="passwordForm">

                        <h4>Recupera tu contraseña</h4> 

                        <!-- Mostrar mensaje si existe -->
                        <?php if (isset($_GET['mensaje'])): ?>
                            <div class="alert">
                                <?php echo htmlspecialchars($_GET['mensaje']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Campo visible para número de documento -->
                        <div class="input-group">
                            <label for="username">Numero de documento</label>
                            <input type="text" 
                                   id="numDocumento" 
                                   name="numDocumento" 
                                   placeholder="Número de documento" 
                                   required 
                                   maxlength="15"
                                   value="<?php echo htmlspecialchars($_GET['id'] ?? $_GET['numDocumento'] ?? ''); ?>">
                        </div>

                        <div class="input-group">
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   placeholder="Nueva contraseña" 
                                   required 
                                   minlength="6">
                        </div>

                        <div class="input-group">
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Confirmar nueva contraseña" 
                                   required 
                                   minlength="6">
                        </div>
                        
                        <button type="submit" class="login-button">Cambiar Contraseña</button>
                    </form>
                </div>

            </div>
            <div class="degrade-container"></div>
            <div class="logo-container">
                <img src="../../public/img/logonegro.png" alt="EcoBikeMess">
                <h6>EcoBikeMess © 2025</h6>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const numDocumento = document.getElementById('numDocumento').value.trim();
            
            if (!numDocumento) {
                e.preventDefault();
                alert('Por favor ingresa tu número de documento.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden.');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres.');
                return false;
            }
        });
    </script>

</body>
</html>