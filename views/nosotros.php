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
    <link rel="stylesheet" href="public/css/nosotros.css">
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

    <script src="public/js/nosotros.js"></script>
</body>
</html>
