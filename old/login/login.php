<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="../../public/assets/stylelogin.css">
</head>
<body>
    

    <h1>Iniciar Sesión</h1>
    <form action="../../config/validarlogin.php" method="POST">

        <div>
            <label for="email">Correo Electrónico:</label>
            <input type="email" class="form-control" id="email" placeholder="Ingrese su correo electrónico" name="email" required>
        </div>
        <div>
            <label for="password">Contraseña:</label>
            <input type="password" class="form-control" id="password" placeholder="Ingrese su contraseña" name="password" required>
        </div>
        <button type="submit">Iniciar Sesión</button>
    </form>




    <script src="../../public/js/scriptlogin.js"></script>
</body>
</html>