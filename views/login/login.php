<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/assets/stylesLogin.css"> <!-- Enlaza el archivo CSS -->
</head>

<body>
    <div class="page-background">
        <div class="borde-container-login">
            <div class="login-container">
                

                <form action="../../controller/validarLogin.php" method="post">

                    <h1>¡BIENVENIDO A ECOBIKEMESS!</h1> <!-- Título -->


                    <div class="input-group">
                        <label for="username">Correo</label>
                        <input type="text" id="correo" name="correo" placeholder="Ingresa tu correo" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                    </div>

                    <?php
                    if (isset($_GET['mensaje'])) {

                    ?>
                        <div class="alert" role="alert">
                            <?php
                            switch ($_GET['mensaje']) {
                                case 'Correo enviado correctamente. Revisa tu bandeja de entrada.':
                                    echo "Se te envió un enlace al correo para restablecer tu contraseña.";
                                    break;
                                case 'El correo no existe o no es válido':
                                    echo "El correo no existe o no es válido.";
                                    break;
                                case 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.':
                                    echo "La contraseña se ha actualizado correctamente.";
                                    break;
                                case 'Datos incompletos':
                                    echo "El correo o la contraseña son incorrectos. Por favor, inténtalo de nuevo.";
                                    break;
                                case 'Registro exitoso. ¡Ya puedes iniciar sesión!':
                                    echo "Registro exitoso. ¡Ya puedes iniciar sesión!";
                                    break;
                                case 'Error en la consulta':
                                    echo "Hubo un error en la consulta. Por favor, inténtalo de nuevo más tarde.";
                                    break;

                            }
                            ?>
                        </div>

                    <?php
                    }
                    ?>



                    <a href="recuperarContraseña.php" class="account-link">¿Olvidaste tu contraseña?</a>
                    <a href="crearUsuariologin.php" class="account-link">¿No tienes una cuenta? ¡Crea una!</a>

                    <button type="submit" class="login-button">Ingresar</button>


                </form>

                <?php
                ?>


            </div>
            <div class="degrade-container"></div>
            <div class="logo-container">
                <img src="../../public/img/logonegro.png" alt="EcoBikeMess">
                <h6>EcoBikeMess © 2025</h6>
            </div>



        </div>
    </div>


</body>

</html>