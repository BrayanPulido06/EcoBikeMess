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
    <link rel="stylesheet" href="public/css/nosotros.css?v=2">
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
                        <a href="<?php echo htmlspecialchars(route_url('home'), ENT_QUOTES, 'UTF-8'); ?>#services" class="btn-primary-about">Ver servicios</a>
                        <a href="https://wa.link/49g8jg" target="_blank" class="btn-secondary-about" rel="noopener">Escríbenos</a>
                    </div>
                </div>
                <div class="about-hero-card">
                    <img src="public/img/Logo_Circulo_Fondoverde.png" alt="Logo verde de EcoBikeMess">
                    <p>Más de 7 años pedaleando por entregas sostenibles en Bogotá y Soacha.</p>
                </div>
            </div>
        </section>

        <section class="about-content-section">
            <div class="container content-grid">
                <article class="content-panel reveal-on-scroll">
                    <span class="section-kicker">Quiénes somos</span>
                    <h3>Una empresa de mensajería ecológica pensada para la ciudad.</h3>
                    <p>
                        EcoBikeMess nace para ofrecer una alternativa eficiente a la mensajería tradicional. Nuestro
                        equipo se mueve en bicicleta para reducir emisiones, mejorar tiempos en zonas urbanas y atender
                        con mayor flexibilidad las necesidades de tiendas, emprendimientos y clientes particulares.
                    </p>
                    <p>
                        Aquí puedes ampliar la historia de la empresa, contar cómo empezó, quiénes hacen parte del
                        equipo, qué logros han tenido y qué diferencia a EcoBikeMess frente a otros servicios.
                    </p>
                </article>

                <aside class="stats-panel reveal-on-scroll">
                    <div class="stat-item">
                        <strong data-counter="7">0</strong>
                        <span>Años de experiencia</span>
                    </div>
                    <div class="stat-item">
                        <strong data-counter="0">0</strong>
                        <span>Emisiones directas en ruta</span>
                    </div>
                    <div class="stat-item">
                        <strong data-counter="100">0</strong>
                        <span>Compromiso con cada entrega</span>
                    </div>
                </aside>
            </div>
        </section>

        <section class="values-section">
            <div class="container">
                <span class="section-kicker">Lo que nos mueve</span>
                <h3>Nuestros valores</h3>
                <div class="values-grid">
                    <article class="value-card reveal-on-scroll">
                        <h4>Sostenibilidad</h4>
                        <p>Priorizamos entregas en bicicleta para aportar a una ciudad con menos ruido, menos tráfico y menos contaminación.</p>
                    </article>
                    <article class="value-card reveal-on-scroll">
                        <h4>Responsabilidad</h4>
                        <p>Cuidamos la información, los tiempos y los paquetes de cada cliente con procesos claros de recogida y entrega.</p>
                    </article>
                    <article class="value-card reveal-on-scroll">
                        <h4>Cercanía</h4>
                        <p>Mantenemos comunicación directa para que cada solicitud tenga seguimiento y respuesta oportuna.</p>
                    </article>
                    <article class="value-card reveal-on-scroll">
                        <h4>Agilidad</h4>
                        <p>Nos adaptamos a la dinámica de Bogotá para gestionar rutas prácticas, rápidas y eficientes.</p>
                    </article>
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

        <section class="rates-section">
            <div class="container">
                <div class="rates-header reveal-on-scroll">
                    <span class="section-kicker">Información comercial</span>
                    <h3>Tarifas</h3>
                    <p>
                        En este apartado puedes colocar la información detallada de tarifas, condiciones, servicios
                        adicionales, tiempos de entrega, zonas de cobertura y cualquier aclaración importante para tus clientes.
                    </p>
                    <p>
                        Puedes usarlo para explicar la Tarifa Emprendedor, Tarifa Oportuna, contraentregas, retornos,
                        packing, espera y recargos especiales.
                    </p>
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
                </div>
            </div>
        </section>

        <section class="timeline-section">
            <div class="container">
                <span class="section-kicker">Para ampliar</span>
                <h3>Espacio para contar más detalles</h3>
                <div class="timeline">
                    <article class="timeline-item reveal-on-scroll">
                        <span>Inicio</span>
                        <h4>Cómo nació EcoBikeMess</h4>
                        <p>Agrega aquí el origen de la empresa, la motivación inicial y los primeros clientes o rutas.</p>
                    </article>
                    <article class="timeline-item reveal-on-scroll">
                        <span>Crecimiento</span>
                        <h4>Experiencia y operación</h4>
                        <p>Describe cómo trabajan, qué zonas atienden, cómo se coordinan las entregas y qué aprendizajes han construido.</p>
                    </article>
                    <article class="timeline-item reveal-on-scroll">
                        <span>Hoy</span>
                        <h4>Compromiso actual</h4>
                        <p>Cuenta los objetivos actuales, alianzas, planes de expansión o mejoras que quieras mostrar al público.</p>
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

    <script src="public/js/nosotros.js?v=2"></script>
</body>
</html>
