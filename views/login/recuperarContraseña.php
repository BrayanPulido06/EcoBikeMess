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


                <form action="recovery.php" method="post">

                    <h1>Ingresa tu Correo Electrónico</h1> <!-- Título -->


                    <div class="input-group">
                        <label for="username">Correo</label>
                        <input type="text" id="correo" name="correo" placeholder="Ingresa tu correo" required>
                    </div>

                    <button type="submit" class="login-button">Recuperar Contraseña</button>


                </form>

                <?php
                switch ($_GET['mensaje']) {
                    case 'El correo no existe o no es válido':
                        echo "El correo no existe o no es válido.";
                        break;
                }
                ?>


            </div>
            <div class="degrade-container"></div>
            <div class="logo-container">
                <img src="../../public/img/logonegro.png" alt="Eco">
                <h6>EcoBikeMess © 2025</h6>
            </div>



        </div>
    </div>


</body>

</html>