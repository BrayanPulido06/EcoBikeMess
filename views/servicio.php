<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoBikeMess</title>
    <link rel="stylesheet" href="../public/assets/style.css">
    <script src="../public/js/scripts.js"></script>

    <div class="navfondo2">

        <nav class="navpagina">
            <img src="../public/img/logoblanco.png" alt=""class="logo" width="250" height="250">
            <ul class="navlista">
                <li><a href="inicio.php">Inicio</a></li>
                <li><a href="tarifas.php">Tarifas</a></li>
                <li><a href="clientes.php">Clientes</a></li>
                <li><a href="cobertura.php">Cobertura</a></li>
            </ul>
        </nav>

    </div>
    
</head>
<body>
    <div class="servicio">
        <h1>Solicita tu Servicio</h1>
        <p>Aquí puedes solicitar el servicio de entrega de paquetes.</p>
    </div>

    <div class="fondodatossolicitudservicio">
        <div class="datossolicitudservicio">
            <label for="nombre">Nombre</label> <br>
            <input type="text" id="nombre" name="nombre" placeholder="Ingresa el nombre del cliente" required>
        </div>

        <div class="datossolicitudservicio">
            <label for="telefono">Telefono</label> <br>
            <input type="text" id="telefono" name="telefono" placeholder="Ingresa el telefono" required>
        </div>

        <div class="datossolicitudservicio">
            <label for="direccion">Dirección de Entrega</label> <br>
            <input type="text" id="direccion" name="direccion" placeholder="Ingresa la dirección de entrega" required>
        </div>

        <div class="datossolicitudservicio">
            <label for="cobro">Cobro</label> <br>
            <input type="text" id="cobro" name="cobro" placeholder="Ingresa el cobro" required>
        </div>

        <div class="datossolicitudservicio">
            <label for="observacion">Observacion</label> <br> 
            <input type="text" id="observacion" name="observacion" placeholder="Ingresa una observación (de ser necesario)" required>
        </div>


        <button type="submit" class="login-button">Enviar</button>
    </div>



</body>
<footer>
    <img src="../public/img/logoblanco.png" alt=""class="logo" width="250" height="250">
    <ul>
        <li> <a href="https://wa.link/49g8jg">Teléfono: +57 312 318 06 19</a></li>
        <li> <a href="Eco.BikeMess@gmail.com">Email: Eco.BikeMess@gmail.com</a></li>
        <li> <a href="https://www.google.com/maps/place/Eco+BikeMess/@4.6481855,-74.0684432,19z/data=!4m16!1m7!3m6!1s0x8e3f9b65787e0213:0xfbf0e7c6f9dea484!2sEco+BikeMess!8m2!3d4.6484168!4d-74.0681079!16s%2Fg%2F11y79hdvrr!3m7!1s0x8e3f9b65787e0213:0xfbf0e7c6f9dea484!8m2!3d4.6484168!4d-74.0681079!9m1!1b1!16s%2Fg%2F11y79hdvrr?entry=ttu&g_ep=EgoyMDI1MDQwOS4wIKXMDSoASAFQAw%3D%3D">Dirección:
                Calle 61 #17-15, Bogotá, Colombia </a></li>
    </ul>
</footer>
</html>