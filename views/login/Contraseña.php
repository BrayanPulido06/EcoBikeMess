<?php
// Obtener token y tipo desde la URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$tipo_usuario = isset($_GET['type']) ? $_GET['type'] : '';

// Conectar a la base de datos para obtener el numDocumento
$numDocumento = '';
if (!empty($token) && !empty($tipo_usuario)) {
    $conexion = mysqli_connect("localhost", "root", "", "ecobikemess");
    
    if ($conexion) {
        $token_escaped = mysqli_real_escape_string($conexion, $token);
        
        // Buscar en la tabla correspondiente según el tipo de usuario
        $tabla = '';
        switch($tipo_usuario) {
            case 'administrador':
                $tabla = 'administradores';
                break;
            case 'cliente':
                $tabla = 'clientes';
                break;
            case 'mensajero':
                $tabla = 'mensajeros';
                break;
            default:
                header("location: login.php?mensaje=Tipo de usuario no válido");
                exit;
        }
        
        $consulta = "SELECT numDocumento FROM $tabla WHERE tokenPassword = '$token_escaped'";
        $resultado = mysqli_query($conexion, $consulta);
        
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $usuario = mysqli_fetch_assoc($resultado);
            $numDocumento = $usuario['numDocumento'];
        } else {
            header("location: login.php?mensaje=Token inválido o expirado");
            exit;
        }
        
        mysqli_close($conexion);
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/assets/stylesLogin.css"> <!-- Enlaza el archivo CSS -->
</head>

<body>
    <div class="page-background">
        <div class="borde-container-login">
            <div class="login-container">

                <div class="recuperarcontraseña">
                    <form action="../../controller/validarNuevaContraseña.php" method="post" id="passwordForm">

                        <h4>Recupera tu contraseña</h4> 

                        <!-- Mostrar mensaje si existe -->
                        <?php if (isset($_GET['mensaje'])): ?>
                            <div class="alert">
                                <?php echo htmlspecialchars($_GET['mensaje']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Campos ocultos para pasar token y tipo -->
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($tipo_usuario); ?>">

                        <!-- Campo visible para número de documento (solo lectura) -->
                        <div class="input-group">
                            <label for="numDocumento">Número de documento</label>
                            <input type="text" 
                                    id="numDocumento" 
                                    name="numDocumento" 
                                    placeholder="Número de documento" 
                                    required 
                                    maxlength="15"
                                    value="<?php echo htmlspecialchars($numDocumento); ?>"
                                    readonly
                                    style="background-color: #f5f5f5; cursor: not-allowed;">
                        </div>

                        <div class="input-group">
                            <label for="new_password">Nueva contraseña</label>
                            <input type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    placeholder="Nueva contraseña" 
                                    required 
                                    minlength="6">
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">Confirmar contraseña</label>
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
                alert('Error: No se pudo obtener el número de documento.');
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