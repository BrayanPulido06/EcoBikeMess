<?php require_once __DIR__ . '/../includes/paths.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars(app_url('/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <script>
        window.APP_BASE_PATH = <?php echo json_encode(app_url(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <title>Nosotros - EcoBikeMess</title>
    <link rel="icon" href="public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="public/css/styles.css">
    <link rel="stylesheet" href="public/css/nosotros.css?v=3">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <h1><img src="public/img/Logo_Circulo_Fondoblanco.png" alt="Logo EcoBikeMess"> EcoBikeMess</h1>
            </div>
            <div class="nav-links">
                <a href="<?php echo htmlspecialchars(route_url('home'), ENT_QUOTES, 'UTF-8'); ?>#inicio">Inicio</a>
                <a href="<?php echo htmlspecialchars(route_url('about'), ENT_QUOTES, 'UTF-8'); ?>" class="active">Nosotros</a>
                <a href="<?php echo htmlspecialchars(route_url('home'), ENT_QUOTES, 'UTF-8'); ?>#services">Servicios</a>
                <a href="<?php echo htmlspecialchars(route_url('home'), ENT_QUOTES, 'UTF-8'); ?>#pricing">Cobertura</a>
                <a href="<?php echo htmlspecialchars(route_url('home'), ENT_QUOTES, 'UTF-8'); ?>#contact">Contacto</a>
            </div>
            <a href="<?php echo htmlspecialchars(route_url('login'), ENT_QUOTES, 'UTF-8'); ?>" class="btn-login">Iniciar Sesión</a>
        </nav>
    </header>

    <main class="about-page">
        <section class="about-hero">
            <div class="container about-hero-grid">
                <div class="about-hero-copy">
                    <span class="section-kicker">Nuestra historia</span>
                    <h2>Mensajería urbana hecha con energía limpia, cercanía y compromiso.</h2>
                    <p>
                        En EcoBikeMess conectamos negocios, emprendimientos y personas con una logística más humana:
                        entregas en bicicleta, comunicación clara y cuidado por cada paquete que sale a ruta.
                    </p>
                    <div class="about-actions">
                        <a href="<?php echo htmlspecialchars(route_url('about'), ENT_QUOTES, 'UTF-8'); ?>#tarifas" class="btn-primary-about">Ver tarifas</a>
                        <a href="https://wa.link/49g8jg" target="_blank" class="btn-secondary-about" rel="noopener">Escríbenos</a>
                    </div>
                </div>
                <div class="about-hero-card">
                    <img src="public/img/Logo_Circulo_Fondoblanco.png" alt="Logo EcoBikeMess">
                    <p>Más de 7 años pedaleando por entregas sostenibles en Bogotá y Soacha.</p>
                </div>
            </div>
        </section>

        <section class="mission-vision-section">
            <div class="container">
                <span class="section-kicker">Nuestro propósito</span>
                <h3>Misión y visión</h3>
                <div class="mission-vision-grid">
                    <article class="purpose-card reveal-on-scroll">
                        <span class="purpose-badge">M</span>
                        <h4>Misión de Eco BikeMess</h4>
                        <p>
                            Fomentar un cambio positivo en la movilidad urbana, ofreciendo un servicio de mensajería en
                            bicicleta que resuelve de forma ecológica la entrega de tus paquetes y mercancías en la ciudad.
                        </p>
                    </article>
                    <article class="purpose-card reveal-on-scroll">
                        <span class="purpose-badge">V</span>
                        <h4>Visión de Eco BikeMess</h4>
                        <p>
                            Ser líderes en la transformación de la logística urbana mediante soluciones innovadoras y
                            sostenibles en bicicleta. Aspiramos a redefinir el futuro de los envíos urbanos, combinando
                            velocidad, eficiencia y responsabilidad ambiental para crear ciudades más limpias, donde cada
                            envío sea una contribución activa al cuidado del medio ambiente.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="rates-section" id="tarifas">
            <div class="container">
                <div class="rates-header reveal-on-scroll">
                    <span class="section-kicker">Información comercial</span>
                    <h3>Tarifas</h3>
                </div>
                <div class="rates-list reveal-on-scroll">
                    <article class="rate-accordion">
                        <button class="rate-toggle" type="button" aria-expanded="false" aria-controls="rate-emprendedor">
                            <span>
                                <strong>Tarifa Emprendedor</strong>
                                <small>Ideal para tus envíos con una tarifa fija.</small>
                            </span>
                            <span class="toggle-icon" aria-hidden="true">+</span>
                        </button>

                        <div class="rate-content" id="rate-emprendedor">
                            <div class="rate-nested-list">
                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-emprendedor-entregas">
                                        <span>Entregas</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-emprendedor-entregas">
                                        <ul class="pretty-bullets">
                                            <li><strong>Mismo día o siguiente:</strong> Gestionamos tu pedido en el transcurso del día, sin horario fijo.</li>
                                            <li><strong>Prioridad same-day:</strong> Asegura tu envío el mismo día por $10.000 en Bogotá o $14.000 en Soacha y zonas verde oscuro.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-emprendedor-cobertura">
                                        <span>Cobertura y precios base</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-emprendedor-cobertura">
                                        <ul class="pretty-bullets">
                                            <li><strong>$8.000:</strong> Envío estándar en Bogotá para paquetes hasta 2kg y 20x20x20cm.</li>
                                            <li><strong>$12.000:</strong> Para Soacha y zonas verdes oscuras en Bogotá, según el mapa de cobertura.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-emprendedor-importante">
                                        <span>Importante</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-emprendedor-importante">
                                        <ul class="pretty-bullets">
                                            <li><strong>Programación:</strong> Solicita tu envío antes de las 10:00 a.m. Después de esta hora, queda sujeto a disponibilidad.</li>
                                            <li><strong>Horarios específicos:</strong> Si necesitas una hora máxima de entrega, puede tener un adicional o aplicar la tarifa oportuna.</li>
                                            <li><strong>Factores externos:</strong> Lluvia, tráfico o alta demanda pueden retrasar tu envío al día siguiente.</li>
                                        </ul>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>

                    <article class="rate-accordion">
                        <button class="rate-toggle" type="button" aria-expanded="false" aria-controls="rate-oportuna">
                            <span>
                                <strong>Tarifa Oportuna</strong>
                                <small>Entregas rápidas y/o con horarios.</small>
                            </span>
                            <span class="toggle-icon" aria-hidden="true">+</span>
                        </button>

                        <div class="rate-content" id="rate-oportuna">
                            <div class="rate-nested-list">
                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-oportuna-valor">
                                        <span>Valor del servicio</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-oportuna-valor">
                                        <ul class="pretty-bullets">
                                            <li><strong>$12.000:</strong> Cubre los primeros 7 km, desde el punto de recogida hasta la entrega.</li>
                                            <li><strong>$1.500:</strong> Por cada km adicional.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-oportuna-paquete">
                                        <span>Especificaciones del paquete</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-oportuna-paquete">
                                        <ul class="pretty-bullets">
                                            <li><strong>Tamaño máximo:</strong> 30x30x30 cm.</li>
                                            <li><strong>Peso máximo:</strong> 3 kg.</li>
                                            <li>Si tu paquete excede estas medidas, puede tener un adicional.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-oportuna-funciona">
                                        <span>Cómo funciona</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-oportuna-funciona">
                                        <ul class="pretty-bullets">
                                            <li>Asignamos un mensajero exclusivo para gestionar tu envío.</li>
                                            <li><strong>Entrega express:</strong> Lo más rápido posible o en el horario que nos indiques.</li>
                                            <li><strong>Ruta optimizada:</strong> Calculamos la tarifa con base en la distancia real usando Google Maps o Waze.</li>
                                        </ul>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>

                    <article class="rate-accordion">
                        <button class="rate-toggle" type="button" aria-expanded="false" aria-controls="rate-contraentrega">
                            <span>
                                <strong>Servicio Contraentrega</strong>
                                <small>Para que tu cliente cancele al recibir.</small>
                            </span>
                            <span class="toggle-icon" aria-hidden="true">+</span>
                        </button>

                        <div class="rate-content" id="rate-contraentrega">
                            <div class="rate-nested-list">
                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-contraentrega-funciona">
                                        <span>Cómo funciona</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-contraentrega-funciona">
                                        <ul class="pretty-bullets">
                                            <li>Al solicitar el servicio, indícanos el monto total a cobrar a tu cliente.</li>
                                            <li>Nuestro mensajero recogerá el pago al entregar el paquete. Nos pueden pagar en efectivo o transferencia.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-contraentrega-devolucion">
                                        <span>Devolución del dinero</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-contraentrega-devolucion">
                                        <ul class="pretty-bullets">
                                            <li>Máximo en 2 días hábiles después de la entrega.</li>
                                            <li><strong>Métodos:</strong> Transferencia a Nequi, Daviplata, Davivienda o Bancolombia, o efectivo si es posible.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-contraentrega-tarifas">
                                        <span>Tarifas transparentes</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-contraentrega-tarifas">
                                        <ul class="pretty-bullets">
                                            <li><strong>Costo fijo:</strong> $3.000 adicionales al valor del envío.</li>
                                            <li>Para recaudos mayores a $300.000 equivale al 1% del monto, es decir $1.000 por cada $100.000.</li>
                                        </ul>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>

                    <article class="rate-accordion">
                        <button class="rate-toggle" type="button" aria-expanded="false" aria-controls="rate-retorno">
                            <span>
                                <strong>Retorno de Paquetes</strong>
                                <small>Entregamos un paquete y recogemos otro con dimensiones similares.</small>
                            </span>
                            <span class="toggle-icon" aria-hidden="true">+</span>
                        </button>

                        <div class="rate-content" id="rate-retorno">
                            <div class="rate-nested-list">
                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-retorno-funciona">
                                        <span>Cómo funciona</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-retorno-funciona">
                                        <ul class="pretty-bullets">
                                            <li>Entregamos un paquete a tu cliente.</li>
                                            <li>Recogemos otro paquete de dimensiones similares en el mismo lugar.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-retorno-tarifas">
                                        <span>Tarifas</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-retorno-tarifas">
                                        <ul class="pretty-bullets">
                                            <li><strong>Retorno en 3 días hábiles:</strong> $5.000 adicionales al servicio original.</li>
                                            <li><strong>Retorno al día siguiente:</strong> Aplica tarifa normal, Emprendedor u Oportuna.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-retorno-importante">
                                        <span>Importante</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-retorno-importante">
                                        <ul class="pretty-bullets">
                                            <li>El paquete a recoger debe tener un tamaño y peso similar al entregado.</li>
                                        </ul>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>

                    <article class="rate-accordion">
                        <button class="rate-toggle" type="button" aria-expanded="false" aria-controls="rate-cancelados">
                            <span>
                                <strong>Servicios Cancelados y/o Devoluciones</strong>
                                <small>Cuando el cliente no recibe, no aparece y no se logra llevar a cabo la entrega.</small>
                            </span>
                            <span class="toggle-icon" aria-hidden="true">+</span>
                        </button>

                        <div class="rate-content" id="rate-cancelados">
                            <div class="rate-nested-list">
                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-cancelados-cancelacion">
                                        <span>Cancelación antes de la entrega</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-cancelados-cancelacion">
                                        <ul class="pretty-bullets">
                                            <li><strong>Costo fijo:</strong> $5.000 por logística.</li>
                                            <li><strong>Devolución del paquete:</strong> 3 días hábiles.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-cancelados-no-responde">
                                        <span>Cliente no responde</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-cancelados-no-responde">
                                        <ul class="pretty-bullets">
                                            <li><strong>Primer intento:</strong> Entrega aplazada para el día siguiente.</li>
                                            <li><strong>Segundo intento:</strong> Si persiste la falta de respuesta, se retorna el paquete con costo del servicio de $5.000.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-cancelados-no-recibe">
                                        <span>Cliente no recibe</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-cancelados-no-recibe">
                                        <ul class="pretty-bullets">
                                            <li>Se cobra el valor completo del servicio.</li>
                                            <li><strong>Devolución del paquete:</strong> 3 días hábiles.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-cancelados-reprogramacion">
                                        <span>Reprogramación y recomendaciones</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-cancelados-reprogramacion">
                                        <ul class="pretty-bullets">
                                            <li><strong>Reprogramación sin costo:</strong> Solo aplica para paquetes pequeños y con aviso previo.</li>
                                            <li>Siempre notificaremos cada intento de entrega.</li>
                                            <li>Una reprogramación gratuita aplica si avisas con tiempo.</li>
                                            <li>Tu paquete se devuelve en 72 horas hábiles.</li>
                                            <li><strong>Recomendación:</strong> Asegúrate de estar disponible o delegar a alguien en la dirección indicada para garantizar una entrega exitosa.</li>
                                        </ul>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>

                    <article class="rate-accordion">
                        <button class="rate-toggle" type="button" aria-expanded="false" aria-controls="rate-packing">
                            <span>
                                <strong>Servicio de Packing</strong>
                                <small>Para paquetes sin sellar o sin información del destinatario.</small>
                            </span>
                            <span class="toggle-icon" aria-hidden="true">+</span>
                        </button>

                        <div class="rate-content" id="rate-packing">
                            <div class="rate-nested-list">
                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-packing-uso">
                                        <span>Cuándo usarlo</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-packing-uso">
                                        <ul class="pretty-bullets">
                                            <li>Paquetes sin sellar o sin información del destinatario.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-packing-datos">
                                        <span>Datos requeridos</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-packing-datos">
                                        <ul class="pretty-bullets">
                                            <li>Dirección exacta, torre o apartamento si aplica.</li>
                                            <li>Nombre y teléfono del destinatario.</li>
                                            <li>Observaciones, por ejemplo: entregar solo a nombre de una persona específica.</li>
                                            <li>Indicar si es contraentrega y especificar el valor del recaudo.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-packing-tarifas">
                                        <span>Tarifas simples</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-packing-tarifas">
                                        <ul class="pretty-bullets">
                                            <li><strong>$2.000:</strong> Incluye embalaje, sellado y rotulado con los datos del cliente.</li>
                                            <li><strong>$1.000:</strong> Solo rotulado si el paquete ya está empacado.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-packing-exclusiones">
                                        <span>Exclusiones</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-packing-exclusiones">
                                        <ul class="pretty-bullets">
                                            <li>No embalamos objetos delicados como vidrio, cerámica o porcelana.</li>
                                            <li>No embalamos alimentos o líquidos.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-packing-beneficios">
                                        <span>Beneficios</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-packing-beneficios">
                                        <ul class="pretty-bullets">
                                            <li><strong>Prevención de pérdidas:</strong> Etiquetamos claramente tu paquete.</li>
                                            <li><strong>Protección básica:</strong> Sellado seguro para evitar aperturas accidentales.</li>
                                            <li><strong>Ahorro de tiempo:</strong> Nos ocupamos de lo técnico, tú enfócate en lo importante.</li>
                                        </ul>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>

                    <article class="rate-accordion">
                        <button class="rate-toggle" type="button" aria-expanded="false" aria-controls="rate-espera">
                            <span>
                                <strong>Tiempo de Espera</strong>
                                <small>Servicios que requieran esperar en un mismo punto.</small>
                            </span>
                            <span class="toggle-icon" aria-hidden="true">+</span>
                        </button>

                        <div class="rate-content" id="rate-espera">
                            <div class="rate-nested-list">
                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-espera-funciona">
                                        <span>Cómo funciona</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-espera-funciona">
                                        <ul class="pretty-bullets">
                                            <li><strong>Primeros 20 minutos:</strong> Incluidos en el servicio sin costo adicional.</li>
                                            <li><strong>Después de 20 minutos:</strong> Se aplica un recargo de $2.000 por cada 10 minutos de espera.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-espera-recomendaciones">
                                        <span>Recomendaciones para clientes</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-espera-recomendaciones">
                                        <ul class="pretty-bullets">
                                            <li>Programa entregas con tiempo suficiente para evitar esperas.</li>
                                            <li>Comunica cambios de horario con anticipación.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-espera-mensaje">
                                        <span>Mensaje amigable</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-espera-mensaje">
                                        <ul class="pretty-bullets">
                                            <li>Valoramos el tiempo de todos. Coordina con tu destinatario para que todo esté listo al llegar nuestro mensajero.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-espera-ejemplo">
                                        <span>Ejemplo</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-espera-ejemplo">
                                        <ul class="pretty-bullets">
                                            <li>Si el mensajero espera 40 minutos, el recargo será de $4.000, equivalente a 2 bloques de 10 minutos.</li>
                                        </ul>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>

                    <article class="rate-accordion">
                        <button class="rate-toggle" type="button" aria-expanded="false" aria-controls="rate-adicionales">
                            <span>
                                <strong>Adicionales</strong>
                                <small>Información requerida, restricciones y tarifas adicionales.</small>
                            </span>
                            <span class="toggle-icon" aria-hidden="true">+</span>
                        </button>

                        <div class="rate-content" id="rate-adicionales">
                            <div class="rate-nested-list">
                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-adicionales-info">
                                        <span>Información requerida al programar</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-adicionales-info">
                                        <ul class="pretty-bullets">
                                            <li>Envíanos los datos completos de quien recibe el pedido: dirección, nombre y teléfono.</li>
                                            <li><strong>Descripción del paquete:</strong> Qué se transporta. No alimentos perecederos o delicados.</li>
                                            <li><strong>Tamaño y peso exactos:</strong> Si supera los estándares, enviar foto para validar tarifa.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-adicionales-restricciones">
                                        <span>Restricciones importantes</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-adicionales-restricciones">
                                        <ul class="pretty-bullets">
                                            <li><strong>No transportamos:</strong> Alimentos perecederos, vidrios, cerámicas u objetos frágiles sin embalaje seguro.</li>
                                            <li>No nos hacemos responsables por paquetes dejados en portería o con el cliente si no están sellados correctamente.</li>
                                            <li><strong>Sellado obligatorio:</strong> Evita pérdidas o malentendidos en la entrega.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-adicionales-tarifas">
                                        <span>Tarifas adicionales</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-adicionales-tarifas">
                                        <ul class="pretty-bullets">
                                            <li><strong>Paquetes sobredimensionados:</strong> Se calculan bajo la Tarifa Oportuna más ajuste por peso o volumen.</li>
                                            <li><strong>Compras en efectivo:</strong> Si requieres que el mensajero retire dinero, por ejemplo para compras, tiene un adicional de $5.000. No adelantamos ni prestamos dinero para compras.</li>
                                            <li><strong>Múltiples paradas:</strong> $1.000 por punto adicional en la misma zona.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="rate-nested">
                                    <button class="rate-nested-toggle" type="button" aria-expanded="false" aria-controls="rate-adicionales-mensaje">
                                        <span>Mensaje clave para clientes</span>
                                        <span class="toggle-icon" aria-hidden="true">+</span>
                                    </button>
                                    <div class="rate-nested-content" id="rate-adicionales-mensaje">
                                        <ul class="pretty-bullets">
                                            <li>Ayúdanos a garantizar que tu envío llegue seguro y a tiempo. Proporciona todos los detalles del paquete y asegúrate de que esté bien sellado. Juntos hacemos logística eficiente.</li>
                                        </ul>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="about-cta">
            <div class="container cta-content">
                <h3>¿Quieres conocer más o programar un envío?</h3>
                <p>Estamos listos para ayudarte con entregas sostenibles, claras y cuidadosas.</p>
                <a href="https://wa.link/49g8jg" target="_blank" rel="noopener" class="btn-primary-about">Contactar por WhatsApp</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2020 EcoBikeMess. Todos los derechos reservados.</p>
            <p>Mensajería ecológica para un futuro sostenible</p>
        </div>
    </footer>

    <script src="public/js/nosotros.js?v=3"></script>
</body>
</html>
