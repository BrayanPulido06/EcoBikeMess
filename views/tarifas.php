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

        <div class="titulo">
            <h1>Nuestros Planes y Precios</h1>
        </div>

    </div>
    
</head>

<body>


    <div class="tarifaemprendedor">
        <button id="tarifaemprendedor" onclick="mostrar1(event);">Tarifa Emprendedor</button>
        <h4>Ideal para tus envios con una tarfia fija</h4>

        <div id="tarifa1">
            >⏳ Entregas: <br>
            - Mismo día o siguiente: Gestionamos tu pedido en el transcurso del día (sin horario fijo).   <br>
            - Prioridad same-day: Asegura tu envío el mismo día por $10.000 (Bogotá) o $14.000 (Soacha/zonas verde
            oscuro). <br>
            <br>
            >📦 Cobertura y precios base: <br>
            - $8.000: Envío estándar en Bogotá (paquetes hasta 2kg y 20x20x20cm).   <br>
            - $12.000: Para Soacha y zonas verdes oscuras en Bogotá (ver mapa de cobertura). <br>
            <br>
            >⚠️ Importante: <br>
            1. Programación: Solicita tu envío antes de las 10:00 a.m. (después de esta hora, queda sujeto a
            disponibilidad).   <br>
            2. Horarios específicos: Si necesitas una hora  de máxima de entrega, puede tener un adicional o aplicar la
            tarfia oportuna   <br>
            3. Factores externos: Lluvia, tráfico u alta demanda pueden retrasar tu envío al día siguiente. <br>
            <br>

            <button id="tarifaemprendedor" onclick="ocultar1();">menos</button>
        </div>
    </div>

    <div class="tarifaoportuna">
        <button id="tarifaoportuna" onclick="mostrar2();">Tarifa Oportuna</button>
        <h4>Entregas rápidas y/o con horarios</h4>

        <div id="tarifa2">
            >💵 Valor del servicio  <br>
            - $12.000: Cubre los primeros 7 km (desde el punto de recogida hasta la entrega).   <br>
            - $1.500 Por cada km adicional. <br>
            <br>
            >📦 Especificaciones del paquete: <br>
            - Tamaño maximo: 30x30x30 cm.   <br>
            - Peso máximo: 3 kg.   <br>
            (Si tu paquete excede estas medidas, puede tener un adicional).  <br>
            <br>
            >⏱️ ¿Cómo funciona? <br>
            Asignamos un mensajero exclusivo para gestionar tu envío.   <br>
            2. Entrega express: Lo más rápido posible o en el horario que nos indiques.   <br>
            3. Ruta optimizada: Calculamos la tarifa con base en la distancia real (Google Maps/Waze). <br>
            <br>
            <button id="tarifaoportuna" onclick="ocultar2();">menos</button>
        </div>

    </div>

    <div class="contraentrega">
        <button id="contraentrega" onclick="mostrar3();">Servicio Contraentrega</button>
        <h4>¡Para que tu cliente cancele al recibir!</h4>

        <div id="tarifa3">
            >📌 ¿Cómo funciona? <br>
            1. Al solicitar el servicio, indícanos el monto total a cobrar a tu cliente.   <br>
            2. Nuestro mensajero recogerá el pago al entregar el paquete (nos pueden pagar en efectivo o transferencia
            ). <br>
            <br>
            >⏱️ Devolución del dinero: <br>
            - Máximo en 2 días hábiles después de la entrega.   <br>
            - Métodos: Transferencia a Nequi, Daviplata, Davivienda o Bancolombia (o efectivo si es posible). <br>
            <br>
            >💲 Tarifas transparentes: <br>
            - Costo fijo: $3.000 adicionales al valor del envío.   <br>
            - Para recaudos mayores a $300.000 equivale al 1% del monto ($1.000 por cada $100.000). <br>
            <br>
            <button id="contraentrega" onclick="ocultar3();">menos</button>
        </div>
    </div>
    </div>

    <div class="retornopaquetes">
        <button id="retornopaquetes" onclick="mostrar4();">Retorno de Paquetes</button>
        <h4>Servicios donde se entrega un paquete y se recoge otro con dimensiones similares.</h4>

        <div id="tarifa4">
            >📦 ¿Cómo funciona? <br>
            1. Entregamos un paquete a tu cliente.   <br>
            2. Recogemos otro paquete de dimensiones similares en el mismo lugar. <br>
            <br>
            >💲 Tarifas: <br>
            - Retorno en 3 días hábiles: $5.000 adicionales al servicio original.   <br>
            - Retorno al día siguiente: Aplica tarifa normal (Emprendedor u Oportuna). <br>
            <br>
            >⚠️ Importante:
            - El paquete a recoger debe tener un tamaño/peso similar al entregado <br>
            <br>
            <button id="retornopaquetes" onclick="ocultar4();">menos</button>
        </div>
    </div>

    <div class="canceladosDevoluciones">
        <button id="canceladosDevoluciones" onclick="mostrar5();">Servicios Cancelados y/o Devoluciones</button>
        <h4>Cuando el cliente no recibe, no aparece y no se logra llevar a cabo la entrega.</h4>

        <div id="tarifa5">
            >1. Cancelación antes de la entrega: <br>
               - Costo fijo: $5.000 (por logística).   <br>
               - Devolución del paquete: 3 días hábiles  <br>
            <br>
            >2. Cliente no responde: <br>
               - Primer intento: Entrega aplazada para el día siguiente.   <br>
               - Segundo intento: Si persiste la falta de respuesta, se retorna el paquete (costo del servicio $5.000).
              <br>
            <br>
            >3. Cliente no recibe <br>
               - Se cobra el valor completo del servicio.   <br>
               - Devolución del paquete: 3 días hábiles.  <br>
            <br>
            >4. Reprogramación sin costo: <br>
               - Solo aplica para paquetes pequeños y con aviso previo.   <br>
            <br>
            >✔  Siempre notificaremos cada intento de entrega.   <br>
            ✔ Una reprogramación gratuita si avisas con tiempo.   <br>
            ✔ Tu paquete se devuelve en 72 horas hábiles. <br>
            <br>
            >📢 Recomendación para clientes: <br>
            *"Por favor, asegúrate de estar disponible o delegar a alguien en la dirección indicada. ¡Así  garantizamos
            una entrega exitosa! 🚴‍♂️" <br>
            <br>
            <button id="canceladosDevoluciones" onclick="ocultar5();">menos</button>
        </div>
    </div>

    <div class="serviciopacking">
        <button id="serviciopacking" onclick="mostrar6();">Servicio de Packing</button>
        <h4>✔ ¿Cuándo usarlo? <br>
            - Paquetes sin sellar o sin información del destinatario.</h4>

        <div id="tarifa6">
            >📝 Datos requeridos: <br>
            Debes enviarnos:   <br>
            1. Dirección exacta (torre/apto si aplica).   <br>
            2. Nombre y teléfono del destinatario.   <br>
            3. Observaciones (ej.: "entregar solo a nombre de...").   <br>
            4. Indicar si es contraentrega (especificar el valor del recaudo). <br>
            <br>
            >💲 Tarifas simples: <br>
            - $2.000 Incluye: embalaje + sellado + rotulado* con los datos del cliente.   <br>
            - $1.000: Solo rotulado (si el paquete ya está empacado).   <br>
            <br>
            >⚠️ Exclusiones: <br>
            No embalamos:   <br>
            - Objetos delicados (vidrio, cerámica, porcelana).   <br>
            - Alimentos o líquidos. <br>
            <br>
            >🎯 Beneficios: <br>
            ✨ Prevención de pérdidas: Etiquetamos claramente tu paquete.   <br>
            ✨ Protección básica: Sellado seguro para evitar aperturas accidentales.   <br>
            ✨ Ahorro de tiempo: Nos ocupamos de lo técnico, tú enfócate en lo importante <br>
            <br>
            <button id="serviciopacking" onclick="ocultar6();">menos</button>
        </div>
    </div>

    <div class="tiempodeespera">
        <button id="tiempodeespera" onclick="mostrar7();">Tiempo de Espera</button>
        <h4>Servicios que requieran esperar en un mismo punto.</h4>

        <div id="tarifa7">
            >📌 ¿Cómo funciona? <br>
            - Primeros 20 minutos: Incluidos en el servicio sin costo adicional.   <br>
            - Después de 20 minutos: Se aplica un recargo de $2.000 por cada 10 minutos de espera. <br>
            <br>
            >📢 Mensaje amigable: <br>
            "Valoramos el tiempo de todos. ¡Coordina con tu destinatario para que todo esté listo al llegar nuestro
            mensajero! 🚴‍♂️💨" <br>
            <br>
            >🔹 Recomendaciones para clientes: <br>
            ✔ Programa entregas con tiempo suficiente para evitar esperas.   <br>
            ✔ Comunica cambios de horario con anticipación.   <br>
            <br>
            >💡 Ejemplo: <br>
            Si el mensajero espera 40 minutos, el recargo será de $4.000 (2 bloques de 10 minutos).   <br>
            <br>
            <button id="tiempodeespera" onclick="ocultar7();">menos</button>
        </div>
    </div>

    <div class="adicionales">
        <button id="adicionales" onclick="mostrar8();">Adicionales</button>
        <h4>¡Tener en cuenta!</h4>

        <div id="tarifa8">
            >📦 Información Requerida al Programar: <br>
            -Envianos los datos completos de quien recibe el pedido (dirección, nombre y teléfono) <br>
            - Descripción del paquete: Qué se transporta (no alimentos perecederos o delicados).   <br>
            - Tamaño y peso exactos (si supera los estándares, enviar foto para validar tarifa). <br>
            <br>
            >🚫 Restricciones Importantes: <br>
            - No transportamos: Alimentos perecederos, vidrios, cerámicas u objetos frágiles sin embalaje seguro.   <br>
            - No nos hacemos responsables por paquetes dejados en portería o con el cliente si no están sellados
            correctamente.   <br>
            - Sellado obligatorio, Evita pérdidas o malentendidos en la entrega <br>
            <br>
            >💲 Tarifas Adicionales: <br>
            - 📏 Paquetes sobredimensionados: Se calculan bajo la Tarifa Oportuna + ajuste por peso/volumen.   <br>
            - 💰 Compras en efectivo: Si requieres que el mensajero retire dinero (ej: para compras), tiene un adicional
            de $5.000 (no adelantamos o prestamos dinero para compras).   <br>
            - 📍 Múltiples paradas: $1.000 por punto adicional en la misma zona. <br>
            <br>
            >📢 Mensaje Clave para Clientes: <br>
            "Ayúdanos a garantizar que tu envío llegue seguro y a tiempo. Proporciona todos los detalles del paquete y
            asegúrate de que esté bien sellado. ¡Juntos hacemos logística eficiente! 🚴‍♂️📦" <br>
            <br>
            <button id="adicionales" onclick="ocultar8();">menos</button>
        </div>
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