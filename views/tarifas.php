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

    <div class="titulo">
        <h1>Nuestros Planes y Precios</h1>
    </div>

    <div class="tarifaemprendedor">
        <button id="tarifaemprendedor" onclick="mostrar1(event);">Tarifa Emprendedor</button>
        <h4>Ideal para tus envios con una tarfia fija</h4>

        <div id="tarifa1">

        <ul> ⏳ Entregas: 
            <li>Mismo día o siguiente: Gestionamos tu pedido en el transcurso del día (sin horario fijo).</li>
            <li>Prioridad same-day: Asegura tu envío el mismo día por $10.000 (Bogotá) o $14.000 (Soacha/zonas verde oscuro).</li>
        </ul>
        <ul>
            📦 Cobertura y precios base:
            <li>$8.000: Envío estándar en Bogotá (paquetes hasta 2kg y 20x20x20cm).</li>
            <li>$12.000: Para Soacha y zonas verdes oscuras en Bogotá (ver mapa de cobertura).</li>
        </ul>
        <ul>
            ⚠️ Importante:
            <li>Programación: Solicita tu envío antes de las 10:00 a.m. (después de esta hora, queda sujeto a disponibilidad).</li>
            <li>Horarios específicos: Si necesitas una hora de máxima de entrega, puede tener un adicional o aplicar la tarifa oportuna.</li>
            <li>Factores externos: Lluvia, tráfico u alta demanda pueden retrasar tu envío al día siguiente.</li>
        </ul>

            <button id="tarifaemprendedor" onclick="ocultar1();">menos</button>
        </div>
    </div>

    <div class="tarifaoportuna">
        <button id="tarifaoportuna" onclick="mostrar2();">Tarifa Oportuna</button>
        <h4>Entregas rápidas y/o con horarios</h4>

        <div id="tarifa2">

        <ul> 💵 Valor del servicio
            <li>$12.000: Cubre los primeros 7 km (desde el punto de recogida hasta la entrega).</li>
            <li>$1.500 Por cada km adicional.</li>          
        </ul>
        <ul>📦 Especificaciones del paquete:
            <li>Tamaño maximo: 30x30x30 cm.</li>
            <li>Peso máximo: 3 kg. (Si tu paquete excede estas medidas, puede tener un adicional).</li>
        </ul>
        <ul>
            ⏱️ ¿Cómo funciona?
            <li>Asignamos un mensajero exclusivo para gestionar tu envío.</li>
            <li>Entrega express: Lo más rápido posible o en el horario que nos indiques.</li>
            <li>Ruta optimizada: Calculamos la tarifa con base en la distancia real (Google Maps/Waze).</li>
        </ul>

            <button id="tarifaoportuna" onclick="ocultar2();">menos</button>
        </div>

    </div>

    <div class="contraentrega">
        <button id="contraentrega" onclick="mostrar3();">Servicio Contraentrega</button>
        <h4>¡Para que tu cliente cancele al recibir!</h4>

        <div id="tarifa3">

        <ul>
            📌 ¿Cómo funciona?
            <li>Al solicitar el servicio, indícanos el monto total a cobrar a tu cliente.</li>
            <li>Nuestro mensajero recogerá el pago al entregar el paquete (nos pueden pagar en efectivo o transferencia).</li>
        </ul>
        <ul>
            ⏱️ Devolución del dinero:
            <li>Máximo en 2 días hábiles después de la entrega.</li>
            <li>Métodos: Transferencia a Nequi, Daviplata, Davivienda o Bancolombia (o efectivo si es posible).</li>
        </ul>
        <ul>
            💲 Tarifas transparentes:
            <li>Costo fijo: $3.000 adicionales al valor del envío.</li>
            <li>Para recaudos mayores a $300.000 equivale al 1% del monto ($1.000 por cada $100.000).</li>
        </ul>

            <button id="contraentrega" onclick="ocultar3();">menos</button>
        </div>
    </div>
    </div>

    <div class="retornopaquetes">
        <button id="retornopaquetes" onclick="mostrar4();">Retorno de Paquetes</button>
        <h4>Servicios donde se entrega un paquete y se recoge otro con dimensiones similares.</h4>

        <div id="tarifa4">

        <ul>
            <li>📦 ¿Cómo funciona?</li>
            <li>Entregamos un paquete a tu cliente.</li>
            <li>Recogemos otro paquete de dimensiones similares en el mismo lugar.</li>
        </ul>
        <ul>
            <li>💲 Tarifas:</li>
            <li>Retorno en 3 días hábiles: $5.000 adicionales al servicio original.</li>
            <li>Retorno al día siguiente: Aplica tarifa normal (Emprendedor u Oportuna).</li>
        </ul>
        <ul>
            <li>⚠️ Importante:</li>
            <li>El paquete a recoger debe tener un tamaño/peso similar al entregado.</li>
        </ul>
            <button id="retornopaquetes" onclick="ocultar4();">menos</button>
        </div>
    </div>

    <div class="canceladosDevoluciones">
        <button id="canceladosDevoluciones" onclick="mostrar5();">Servicios Cancelados y/o Devoluciones</button>
        <h4>Cuando el cliente no recibe, no aparece y no se logra llevar a cabo la entrega.</h4>

        <div id="tarifa5">

        <ul>Cancelación antes de la entrega:
            <li>Costo fijo: $5.000 (por logística).</li>
            <li>Devolución del paquete: 3 días hábiles.</li>
        </ul>
        <ul>cliente no responde:
            <li>Primer intento: Entrega aplazada para el día siguiente.</li>
            <li>Segundo intento: Si persiste la falta de respuesta, se retorna el paquete (costo del servicio $5.000).</li>
        </ul>
        <ul>cliente no recibe:
            <li>Se cobra el valor completo del servicio.</li>
            <li>Devolución del paquete: 3 días hábiles.</li>
        </ul>
        <ul>reprogramación sin costo:
            <li>Solo aplica para paquetes pequeños y con aviso previo.</li>
        </ul>
        <ul>siempre notificamos:
            <li>Cada intento de entrega se notifica al cliente.</li>
            <li>Reprogramación gratuita si se avisa con tiempo.</li>
            <li>El paquete se devuelve en 72 horas hábiles.</li>
        </ul>
        <ul>📢 Recomendación para clientes:
            <li>"Por favor, asegúrate de estar disponible o delegar a alguien en la dirección indicada. ¡Así garantizamos una entrega exitosa! 🚴‍♂️"</li>
        </ul>

            <button id="canceladosDevoluciones" onclick="ocultar5();">menos</button>
        </div>
    </div>

    <div class="serviciopacking">
        <button id="serviciopacking" onclick="mostrar6();">Servicio de Packing</button>
        <h4> ¿Cuándo usarlo? <br>
            Paquetes sin sellar o sin información del destinatario.</h4>

        <div id="tarifa6">

        <ul>📝 Datos requeridos:
            <li>Dirección exacta (torre/apto si aplica).</li>
            <li>Nombre y teléfono del destinatario.</li>
            <li>Observaciones (ej.: "entregar solo a nombre de...").</li>
            <li>Indicar si es contraentrega (especificar el valor del recaudo).</li>
        </ul>
        <ul>💲 Tarifas simples:
            <li>$2.000 Incluye: embalaje + sellado + rotulado* con los datos del cliente.</li>
            <li>$1.000: Solo rotulado (si el paquete ya está empacado).</li>
        </ul>
        <ul>
            ⚠️ Exclusiones:
            <li>No embalamos: Objetos delicados (vidrio, cerámica, porcelana).</li>
            <li>No embalamos: Alimentos o líquidos.</li>
        </ul>
        <ul>
            🎯 Beneficios:
            <li>✨ Prevención de pérdidas: Etiquetamos claramente tu paquete.</li>
            <li>✨ Protección básica: Sellado seguro para evitar aperturas accidentales.</li>
            <li>✨ Ahorro de tiempo: Nos ocupamos de lo técnico, tú enfócate en lo importante.</li>
        </ul>

            <button id="serviciopacking" onclick="ocultar6();">menos</button>
        </div>
    </div>

    <div class="tiempodeespera">
        <button id="tiempodeespera" onclick="mostrar7();">Tiempo de Espera</button>
        <h4>Servicios que requieran esperar en un mismo punto.</h4>

        <div id="tarifa7">
            
        <ul>📌 ¿Cómo funciona?
            <li>Primeros 20 minutos: Incluidos en el servicio sin costo adicional.</li>
            <li>Después de 20 minutos: Se aplica un recargo de $2.000 por cada 10 minutos de espera.</li>
        </ul>
        <ul>📢 Mensaje amigable:
            <li>"Valoramos el tiempo de todos. ¡Coordina con tu destinatario para que todo esté listo al llegar nuestro mensajero! 🚴‍♂️💨"</li>
        </ul>
        <ul>🔹 Recomendaciones para clientes:
            <li>Programa entregas con tiempo suficiente para evitar esperas.</li>
            <li>Comunica cambios de horario con anticipación.</li>
        </ul>
        <ul>💡 Ejemplo:
            <li>Si el mensajero espera 40 minutos, el recargo será de $4.000 (2 bloques de 10 minutos).</li>
        </ul>

            <button id="tiempodeespera" onclick="ocultar7();">menos</button>
        </div>
    </div>

    <div class="adicionales">
        <button id="adicionales" onclick="mostrar8();">Adicionales</button>
        <h4>¡Tener en cuenta!</h4>

        <div id="tarifa8">

        <ul>📦 Información Requerida al Programar:
            <li>Envianos los datos completos de quien recibe el pedido (dirección, nombre y teléfono)</li>
            <li>Descripción del paquete: Qué se transporta (no alimentos perecederos o delicados).</li>
            <li>Tamaño y peso exactos (si supera los estándares, enviar foto para validar tarifa).</li>
        </ul>
        <ul>🚫 Restricciones Importantes:
            <li>No transportamos: Alimentos perecederos, vidrios, cerámicas u objetos frágiles sin embalaje seguro.</li>
            <li>No nos hacemos responsables por paquetes dejados en portería o con el cliente si no están sellados correctamente.</li>
        </ul>
        <ul>🔒 Sellado Seguro:
            <li>Sellado obligatorio, Evita pérdidas o malentendidos en la entrega</li>
        </ul>
        <ul>💲 Tarifas Adicionales:
            <li>📏 Paquetes sobredimensionados: Se calculan bajo la Tarifa Oportuna + ajuste por peso/volumen.</li>
            <li>💰 Compras en efectivo: Si requieres que el mensajero retire dinero (ej: para compras), tiene un adicional
            de $5.000 (no adelantamos o prestamos dinero para compras).</li>
            <li>📍 Múltiples paradas: $1.000 por punto adicional en la misma zona.</li>
        </ul>
        <ul>📢 Mensaje Clave para Clientes:
            <li>"Ayúdanos a garantizar que tu envío llegue seguro y a tiempo. Proporciona todos los detalles del paquete y
            asegúrate de que esté bien sellado. ¡Juntos hacemos logística eficiente! 🚴‍♂️📦"</li>
        </ul>
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