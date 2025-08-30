<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapidoEnvíos - Mensajería y Paquetería en Bogotá</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            line-height: 1.7;
            color: #2d3748;
            overflow-x: hidden;
            background: #f7fafc;
        }

        /* Navegación */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid #68d391;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.8rem;
            font-weight: bold;
            color: #2d3748;
            text-decoration: none;
            font-family: 'Georgia', serif;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: #4a5568;
            text-decoration: none;
            font-weight: 500;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .nav-links a:hover {
            color: #38a169;
            border-color: #68d391;
            background: rgba(104, 211, 145, 0.1);
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #68d391 0%, #48bb78 50%, #a3bdaf 100%);
            overflow: hidden;
            margin-top: 0;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            background-size: 100px 100px;
        }

        .hero .container {
            text-align: center;
            color: white;
            max-width: 800px;
            padding: 2rem;
            z-index: 2;
            position: relative;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            font-family: 'Georgia', serif;
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 3rem;
            line-height: 1.6;
            opacity: 0.95;
            font-style: italic;
        }

        .cta-button {
            padding: 1.2rem 3rem;
            border-radius: 50px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.4s ease;
            font-size: 1.1rem;
            border: 3px solid white;
            font-family: 'Georgia', serif;
            background: white;
            color: #38a169;
            display: inline-block;
        }

        .cta-button:hover {
            background: #38a169;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        /* Secciones generales */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 3rem;
            color: #2d3748;
            line-height: 1.2;
            font-family: 'Georgia', serif;
            text-align: center;
        }

        /* Como funciona */
        .como-funciona {
            padding: 6rem 0;
            background: white;
        }

        .pasos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-top: 4rem;
        }

        .paso {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            border: 3px solid transparent;
        }

        .paso:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-color: #68d391;
        }

        .paso-numero {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #68d391, #48bb78);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.5rem;
            color: white;
            font-weight: bold;
        }

        .paso h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2d3748;
            font-family: 'Georgia', serif;
        }

        .paso p {
            color: #4a5568;
            line-height: 1.6;
        }

        /* Servicios */
        .servicios {
            padding: 6rem 0;
            background: #f0fff4;
        }

        .servicios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            margin-top: 4rem;
        }

        .servicio {
            background: white;
            padding: 3rem 2rem;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            border: 3px solid transparent;
            text-align: center;
        }

        .servicio:hover {
            transform: translateY(-10px);
            border-color: #68d391;
        }

        .servicio-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }

        .servicio h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #2d3748;
            font-family: 'Georgia', serif;
        }

        .servicio p {
            color: #4a5568;
            margin-bottom: 1.5rem;
        }

        .precio {
            font-size: 1.2rem;
            font-weight: bold;
            color: #38a169;
        }

        .Tarifas {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 2rem;
            padding: 2rem;
            background: rgba(104, 211, 145, 0.1);
            border-radius: 20px;
            border: 2px dashed #68d391;
        }

        .Tarifas a {
            color: #38a169;
            text-decoration: none;
            font-weight: bold;
        }

        /* Cobertura */
        .cobertura {
            padding: 6rem 0;
            background: white;
        }

        .mapa-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 4rem;
        }

        .mapa-placeholder {
            background: #f7fafc;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        #map {
            width: 100%;
            height: 400px;
            border-radius: 20px;
        }

        .cobertura-info {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .info-card {
            background: #f0fff4;
            padding: 2rem;
            border-radius: 20px;
            border: 2px solid #68d391;
        }

        .info-card h4 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #2d3748;
            font-family: 'Georgia', serif;
        }

        .tiempo-zona {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(104, 211, 145, 0.2);
        }

        .tiempo-zona:last-child {
            border-bottom: none;
        }

        .zonas-list {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 3rem;
        }

        .zona {
            background: #68d391;
            color: white;
            padding: 1rem;
            border-radius: 15px;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .zona:hover {
            background: #48bb78;
            transform: scale(1.05);
        }

        /* Herramientas */
        .herramientas {
            padding: 6rem 0;
            background: #f0fff4;
        }

        .herramientas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-top: 4rem;
        }

        .herramienta {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.4s ease;
        }

        .herramienta:hover {
            transform: translateY(-10px);
        }

        .herramienta-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }

        .herramienta h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2d3748;
            font-family: 'Georgia', serif;
        }

        .herramienta p {
            color: #4a5568;
            margin-bottom: 1.5rem;
        }

        .herramienta button {
            background: #68d391;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Georgia', serif;
        }

        .herramienta button:hover {
            background: #48bb78;
            transform: translateY(-2px);
        }

        /* Blog */
        .blog {
            padding: 6rem 0;
            background: white;
        }

        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-top: 4rem;
        }

        .blog-post {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
        }

        .blog-post:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .blog-image {
            height: 200px;
            background: linear-gradient(135deg, #68d391, #48bb78);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .blog-content {
            padding: 2rem;
        }

        .blog-fecha {
            color: #68d391;
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .blog-post h3 {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: #2d3748;
            font-family: 'Georgia', serif;
        }

        .blog-post p {
            color: #4a5568;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: #2d3748;
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: #68d391;
            font-family: 'Georgia', serif;
        }

        .footer-section p {
            line-height: 1.6;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 0.8rem;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: #68d391;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: #68d391;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: #48bb78;
            transform: scale(1.1);
        }

        .footer-bottom {
            border-top: 2px solid #4a5568;
            padding-top: 2rem;
            text-align: center;
            opacity: 0.8;
        }

        /* Leaflet map customization */
        .leaflet-container {
            background: #e6fffa;
        }

        .custom-div-icon {
            background: transparent;
            border: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-links a {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .mapa-container {
                grid-template-columns: 1fr;
            }

            .zonas-list {
                grid-template-columns: repeat(2, 1fr);
            }

            .pasos {
                grid-template-columns: 1fr;
            }

            .servicios-grid {
                grid-template-columns: 1fr;
            }

            .herramientas-grid {
                grid-template-columns: 1fr;
            }

            .blog-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="navbar">
        <div class="nav-container">
            <div class="logo">RapidoEnvíos</div>
            <ul class="nav-links">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#servicios">Servicios</a></li>
                <li><a href="#cobertura">Cobertura</a></li>
                <li><a href="#rastreo">Rastreo</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="inicio">
        <div class="container">
            <h1>Envíos Rápidos y Seguros en Bogotá</h1>
            <p>Conectamos tu negocio con tus clientes a través de la mejor red de mensajería urbana</p>
            <a href="#cotizar" class="cta-button">Envía tu Paquete Ahora</a>
        </div>
    </section>

    <!-- Cómo Funciona -->
    <section class="como-funciona">
        <div class="container">
            <h2 class="section-title">¿Cómo Funciona?</h2>
            <div class="pasos">
                <div class="paso">
                    <div class="paso-numero">1</div>
                    <h3>Solicita</h3>
                    <p>Programa tu recogida online o por teléfono. Especifica origen, destino y tipo de servicio.</p>
                </div>
                <div class="paso">
                    <div class="paso-numero">2</div>
                    <h3>Recogemos</h3>
                    <p>Nuestro mensajero llega puntual a recoger tu paquete en la dirección indicada.</p>
                </div>
                <div class="paso">
                    <div class="paso-numero">3</div>
                    <h3>Procesamos</h3>
                    <p>Tu envío ingresa a nuestro sistema de seguimiento y se asigna al mejor mensajero.</p>
                </div>
                <div class="paso">
                    <div class="paso-numero">4</div>
                    <h3>Entregamos</h3>
                    <p>Entregamos en tiempo récord con confirmación y evidencia fotográfica.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Servicios -->
    <section class="servicios" id="servicios">
        <div class="container">
            <h2 class="section-title">Nuestros Servicios</h2>
            <div class="servicios-grid">
                <div class="servicio">
                    <div class="servicio-icon">🚚</div>
                    <h3>Estándar</h3>
                    <p>Entregas en 4-6 horas dentro de Bogotá. Perfecto para envíos regulares sin prisa.</p>
                    <div class="precio">Desde $8.500</div>
                </div>
                <div class="servicio">
                    <div class="servicio-icon">⚡</div>
                    <h3>Express</h3>
                    <p>Entregas en 2-3 horas. Ideal para documentos importantes y paquetes urgentes.</p>
                    <div class="precio">Desde $15.000</div>
                </div>
                <div class="servicio">
                    <div class="servicio-icon">🏃‍♂️</div>
                    <h3>Mismo Día</h3>
                    <p>Entrega garantizada el mismo día. Para envíos críticos que no pueden esperar.</p>
                    <div class="precio">Desde $25.000</div>
                </div>
                <div class="Tarifas">
                    <div class="servicio-icon">📅</div>
                    <h3><a href="#mas-informacion">Mas información</a></h3>
                    <p>Si necesitas más detalles sobre nuestros servicios, no dudes en contactarnos.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cobertura -->
    <section class="cobertura" id="cobertura">
        <div class="container">
            <h2 class="section-title">Cobertura en Bogotá</h2>
            <div class="mapa-container">
                <div class="mapa-placeholder">
                    <div id="map"></div>
                </div>
                <div class="cobertura-info">
                    <div class="info-card">
                        <h4>⏰ Tiempos de Entrega</h4>
                        <div class="tiempo-zona">
                            <span>Centro/Zona Rosa</span>
                            <span><strong>2-3 horas</strong></span>
                        </div>
                        <div class="tiempo-zona">
                            <span>Chapinero/Usaquén</span>
                            <span><strong>3-4 horas</strong></span>
                        </div>
                        <div class="tiempo-zona">
                            <span>Kennedy/Fontibón</span>
                            <span><strong>4-5 horas</strong></span>
                        </div>
                        <div class="tiempo-zona">
                            <span>Suba/Engativá</span>
                            <span><strong>4-6 horas</strong></span>
                        </div>
                        <div class="tiempo-zona">
                            <span>Bosa/Ciudad Bolívar</span>
                            <span><strong>5-7 horas</strong></span>
                        </div>
                    </div>
                    <div class="info-card">
                        <h4>📊 Estadísticas de Cobertura</h4>
                        <div class="tiempo-zona">
                            <span>Localidades cubiertas</span>
                            <span><strong>20/20</strong></span>
                        </div>
                        <div class="tiempo-zona">
                            <span>Barrios activos</span>
                            <span><strong>300+</strong></span>
                        </div>
                        <div class="tiempo-zona">
                            <span>Mensajeros disponibles</span>
                            <span><strong>150+</strong></span>
                        </div>
                        <div class="tiempo-zona">
                            <span>Entregas diarias</span>
                            <span><strong>2,500+</strong></span>
                        </div>
                        <div class="tiempo-zona">
                            <span>Tasa de éxito</span>
                            <span><strong>99.2%</strong></span>
                        </div>
                    </div>
                </div>
                <div class="zonas-list">
                    <div class="zona">Centro Histórico</div>
                    <div class="zona">Zona Rosa</div>
                    <div class="zona">Chapinero</div>
                    <div class="zona">Zona Norte</div>
                    <div class="zona">Kennedy</div>
                    <div class="zona">Fontibón</div>
                    <div class="zona">Suba</div>
                    <div class="zona">Usaquén</div>
                    <div class="zona">Bosa</div>
                    <div class="zona">Engativá</div>
                    <div class="zona">Zona Sur</div>
                    <div class="zona">Ciudad Bolívar</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Herramientas Públicas -->
    <section class="herramientas">
        <div class="container">
            <h2 class="section-title">Herramientas Gratuitas</h2>
            <div class="herramientas-grid">
                <div class="herramienta">
                    <div class="herramienta-icon">💰</div>
                    <h3>Calculadora de Tarifas</h3>
                    <p>Cotiza el costo de tu envío sin necesidad de registro</p>
                    <button>Calcular</button>
                </div>
                <div class="herramienta">
                    <div class="herramienta-icon">📦</div>
                    <h3>Rastreo de Paquetes</h3>
                    <p>Consulta el estado de tu envío en tiempo real</p>
                    <button>Rastrear</button>
                </div>
                <div class="herramienta">
                    <div class="herramienta-icon">🗺️</div>
                    <h3>Mapa de Cobertura</h3>
                    <p>Verifica si llegamos a tu zona de entrega</p>
                    <button>Ver Mapa</button>
                </div>
                <div class="herramienta">
                    <div class="herramienta-icon">⏰</div>
                    <h3>Tiempo de Entrega</h3>
                    <p>Consulta tiempos estimados entre zonas</p>
                    <button>Consultar</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog/Noticias -->
    <section class="blog">
        <div class="container">
            <h2 class="section-title">Blog y Noticias</h2>
            <div class="blog-grid">
                <article class="blog-post">
                    <div class="blog-image">📈 Imagen del Artículo</div>
                    <div class="blog-content">
                        <div class="blog-fecha">15 de Agosto, 2025</div>
                        <h3>Tendencias del E-commerce en Colombia 2025</h3>
                        <p>Descubre cómo está evolucionando el comercio electrónico y qué oportunidades presenta para tu negocio...</p>
                    </div>
                </article>
                <article class="blog-post">
                    <div class="blog-image">🚚 Imagen del Artículo</div>
                    <div class="blog-content">
                        <div class="blog-fecha">12 de Agosto, 2025</div>
                        <h3>Nuevas Rutas de Entrega en Bogotá</h3>
                        <p>Ampliamos nuestra cobertura con nuevas rutas optimizadas que reducen los tiempos de entrega...</p>
                    </div>
                </article>
                <article class="blog-post">
                    <div class="blog-image">📦 Imagen del Artículo</div>
                    <div class="blog-content">
                        <div class="blog-fecha">8 de Agosto, 2025</div>
                        <h3>Tips para Empacar tus Envíos</h3>
                        <p>Guía completa sobre cómo proteger tus productos durante el transporte y asegurar entregas perfectas...</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>RapidoEnvíos</h3>
                    <p>Tu aliado confiable en mensajería urbana. Conectamos Bogotá con entregas rápidas y seguras.</p>
                    <div class="social-links">
                        <a href="#" class="social-link">📘</a>
                        <a href="#" class="social-link">📷</a>
                        <a href="#" class="social-link">🐦</a>
                        <a href="#" class="social-link">📺</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Servicios</h3>
                    <ul>
                        <li><a href="#">Envío Estándar</a></li>
                        <li><a href="#">Envío Express</a></li>
                        <li><a href="#">Mismo Día</a></li>
                        <li><a href="#">Programado</a></li>
                        <li><a href="#">Tarifas</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Empresa</h3>
                    <ul>
                        <li><a href="#">Sobre Nosotros</a></li>
                        <li><a href="#">Cobertura</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Trabaja con Nosotros</a></li>
                        <li><a href="#">Prensa</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Soporte</h3>
                    <ul>
                        <li><a href="#">Centro de Ayuda</a></li>
                        <li><a href="#">Rastrear Paquete</a></li>
                        <li><a href="#">Preguntas Frecuentes</a></li>
                        <li><a href="#">Contacto</a></li>
                        <li><a href="#">Términos y Condiciones</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <ul>
                        <li>📞 (601) 123-4567</li>
                        <li>📱 300 123 4567</li>
                        <li>✉️ info@rapidoenvios.com</li>
                        <li>📍 Calle 26 #68-90, Bogotá</li>
                        <li>🕒 Lun-Vie: 7AM-7PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 RapidoEnvíos. Todos los derechos reservados. | Política de Privacidad | Términos de Servicio</p>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.js"></script>
    
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add scroll effect to header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.15)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            }
        });

        // Simple click handlers for tools
        document.querySelectorAll('.herramienta button').forEach(button => {
            button.addEventListener('click', function() {
                const toolName = this.parentElement.querySelector('h3').textContent;
                alert(`Redirigiendo a: ${toolName}`);
            });
        });

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the map centered on Bogotá with enhanced styling
            var map = L.map('map', {
                zoomControl: true,
                scrollWheelZoom: false
            }).setView([4.6097, -74.0817], 11);

            // Add custom tile layer with better contrast for Bogotá
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18,
                opacity: 0.8
            }).addTo(map);

            // Create custom icon for headquarters
            var headquartersIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.3);">🏢</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            // Add headquarters marker
            var headquarters = L.marker([4.6350, -74.1139], {icon: headquartersIcon}).addTo(map);
            headquarters.bindPopup("<div style='text-align: center; font-family: Georgia, serif;'><b>🏢 RapidoEnvíos - Sede Principal</b><br>📍 Calle 26 #68-90, Bogotá<br>📞 (601) 123-4567<br><span style='color: #68d391; font-weight: bold;'>¡Estamos aquí para servirte!</span></div>");

            // Create custom delivery icon
            var deliveryIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background: #28a745; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; font-size: 12px; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">🚚</div>',
                iconSize: [25, 25],
                iconAnchor: [12, 12]
            });

            // Sample delivery points to highlight Bogotá coverage
            var deliveryPoints = [
                {coords: [4.6482, -74.0637], popup: "<b>🚚 Centro Histórico</b><br>📦 Zona de alta demanda<br>⏱️ Tiempo promedio: 2-3 horas"},
                {coords: [4.6736, -74.0465], popup: "<b>🚚 Chapinero</b><br>📦 Zona comercial activa<br>⏱️ Tiempo promedio: 3-4 horas"},
                {coords: [4.7110, -74.0721], popup: "<b>🚚 Usaquén</b><br>📦 Zona residencial premium<br>⏱️ Tiempo promedio: 3-4 horas"},
                {coords: [4.5981, -74.1421], popup: "<b>🚚 Kennedy</b><br>📦 Gran volumen de entregas<br>⏱️ Tiempo promedio: 4-5 horas"},
                {coords: [4.6860, -74.1311], popup: "<b>🚚 Suba</b><br>📦 Zona en expansión<br>⏱️ Tiempo promedio: 4-6 horas"},
                {coords: [4.5287, -74.1624], popup: "<b>🚚 Bosa</b><br>📦 Cobertura completa<br>⏱️ Tiempo promedio: 5-7 horas"},
                {coords: [4.6629, -74.1067], popup: "<b>🚚 Engativá</b><br>📦 Ruta optimizada<br>⏱️ Tiempo promedio: 4-6 horas"}
            ];

            // Add delivery points to map
            deliveryPoints.forEach(function(point) {
                L.marker(point.coords, {icon: deliveryIcon}).addTo(map)
                    .bindPopup(point.popup);
            });

            // Add coverage area circle to highlight Bogotá coverage
            var coverageCircle = L.circle([4.6097, -74.0817], {
                color: '#68d391',
                fillColor: '#68d391',
                fillOpacity: 0.1,
                radius: 25000,
                weight: 3,
                dashArray: '10, 10'
            }).addTo(map);

            coverageCircle.bindPopup("<div style='text-align: center; font-family: Georgia, serif;'><b>🌍 Área de Cobertura RapidoEnvíos</b><br>📍 Toda Bogotá y área metropolitana<br>📊 99.2% de tasa de éxito<br>🚚 2,500+ entregas diarias</div>");

            // Add district boundaries for better visualization
            var districtStyle = {
                color: '#48bb78',
                weight: 2,
                opacity: 0.6,
                fillOpacity: 0.05,
                fillColor: '#68d391'
            };

            // Sample district polygons (simplified for demo)
            var centroHistorico = L.polygon([
                [4.585, -74.085],
                [4.620, -74.085],
                [4.620, -74.050],
                [4.585, -74.050]
            ], districtStyle).addTo(map);
            
            centroHistorico.bindPopup("<b>Centro Histórico</b><br>⏱️ Tiempo de entrega: 2-3 horas<br>📊 Alta densidad de entregas");

            // Disable zoom on double click to prevent accidental zooming
            map.doubleClickZoom.disable();

            // Add custom control for map info
            var info = L.control({position: 'topright'});
            info.onAdd = function (map) {
                var div = L.DomUtil.create('div', 'info');
                div.innerHTML = '<div style="background: white; padding: 10px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); font-family: Georgia, serif;"><b style="color: #68d391;">🗺️ Mapa Interactivo</b><br><small>Haz clic en los marcadores para más información</small></div>';
                return div;
            };
            info.addTo(map);

            // Add scale control
            L.control.scale({
                position: 'bottomleft',
                imperial: false
            }).addTo(map);
        });

        // Add fade-in animation for sections
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe sections for animation
        document.querySelectorAll('section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            section.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            observer.observe(section);
        });

        // Add hover effects to cards
        document.querySelectorAll('.paso, .servicio, .herramienta, .blog-post').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>