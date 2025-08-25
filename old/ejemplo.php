<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapidoEnvíos - Mensajería y Paquetería en Bogotá</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 120px 0 80px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-button {
            background: #ff6b6b;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .cta-button:hover {
            background: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
        }

        /* Como Funciona */
        .como-funciona {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }

        .pasos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .paso {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .paso:hover {
            transform: translateY(-5px);
        }

        .paso-numero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }

        /* Servicios */
        .servicios {
            padding: 80px 0;
        }

        .servicios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .servicio {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            border: 2px solid #f0f0f0;
            transition: all 0.3s;
        }

        .servicio:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }

        .servicio-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .precio {
            color: #667eea;
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 1rem;
        }

        /* Cobertura */
        .cobertura {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .mapa-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            margin-top: 3rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .mapa-placeholder {
            background: #f8f9fa;
            height: 500px;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
            border: 2px solid #e9ecef;
        }

        #map {
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }

        .mapa-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .mapa-overlay h4 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 1.1rem;
        }

        .cobertura-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .info-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }

        .info-card h4 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        .tiempo-zona {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .zonas-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .zona {
            background: #667eea;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        /* Testimonios */
        .testimonios {
            padding: 80px 0;
        }

        .testimonios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .testimonio {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }

        .testimonio-texto {
            font-style: italic;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .testimonio-autor {
            font-weight: bold;
            color: #667eea;
        }

        .estrellas {
            color: #ffd700;
            margin-bottom: 1rem;
        }

        /* Herramientas */
        .herramientas {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .herramientas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .herramienta {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .herramienta:hover {
            transform: translateY(-5px);
        }

        .herramienta-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }

        /* Blog */
        .blog {
            padding: 80px 0;
        }

        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .blog-post {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .blog-post:hover {
            transform: translateY(-5px);
        }

        .blog-image {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .blog-content {
            padding: 1.5rem;
        }

        .blog-fecha {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            padding: 60px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #667eea;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: #667eea;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-link {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background 0.3s;
        }

        .social-link:hover {
            background: #5a6fd8;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #555;
            color: #ccc;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="container">
            <div class="logo">RapidoEnvíos</div>
            <ul class="nav-links">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#servicios">Servicios</a></li>
                <li><a href="#cobertura">Cobertura</a></li>
                <li><a href="#rastreo">Rastreo</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>
        </nav>
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
                <div class="servicio">
                    <div class="servicio-icon">📅</div>
                    <h3>Programado</h3>
                    <p>Programa tu entrega para una fecha y hora específica. Planifica con anticipación.</p>
                    <div class="precio">Desde $12.000</div>
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
                    <div class="mapa-overlay">
                        <h4>🚚 Cobertura RapidoEnvíos</h4>
                        <p><strong>Zona Verde:</strong> Entrega 2-4 horas<br>
                        <strong>Zona Azul:</strong> Entrega 4-6 horas<br>
                        <strong>Zona Amarilla:</strong> Entrega 6-8 horas</p>
                    </div>
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

    <!-- Testimonios -->
    <section class="testimonios">
        <div class="container">
            <h2 class="section-title">Lo Que Dicen Nuestros Clientes</h2>
            <div class="testimonios-grid">
                <div class="testimonio">
                    <div class="estrellas">⭐⭐⭐⭐⭐</div>
                    <p class="testimonio-texto">"Excelente servicio, siempre puntuales y muy profesionales. Mis clientes quedan satisfechos con la rapidez de las entregas."</p>
                    <div class="testimonio-autor">- María González, Tienda Online</div>
                </div>
                <div class="testimonio">
                    <div class="estrellas">⭐⭐⭐⭐⭐</div>
                    <p class="testimonio-texto">"Uso RapidoEnvíos para mi empresa de repuestos. La plataforma de seguimiento es fantástica y los precios muy competitivos."</p>
                    <div class="testimonio-autor">- Carlos Rodríguez, AutoPartes SAS</div>
                </div>
                <div class="testimonio">
                    <div class="estrellas">⭐⭐⭐⭐⭐</div>
                    <p class="testimonio-texto">"Confiamos en ellos para nuestros documentos legales. Nunca han fallado, siempre con evidencia de entrega."</p>
                    <div class="testimonio-autor">- Ana Martínez, Bufete Jurídico</div>
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
                    <button class="cta-button" style="margin-top: 1rem; font-size: 1rem; padding: 10px 25px;">Calcular</button>
                </div>
                <div class="herramienta">
                    <div class="herramienta-icon">📦</div>
                    <h3>Rastreo de Paquetes</h3>
                    <p>Consulta el estado de tu envío en tiempo real</p>
                    <button class="cta-button" style="margin-top: 1rem; font-size: 1rem; padding: 10px 25px;">Rastrear</button>
                </div>
                <div class="herramienta">
                    <div class="herramienta-icon">🗺️</div>
                    <h3>Mapa de Cobertura</h3>
                    <p>Verifica si llegamos a tu zona de entrega</p>
                    <button class="cta-button" style="margin-top: 1rem; font-size: 1rem; padding: 10px 25px;">Ver Mapa</button>
                </div>
                <div class="herramienta">
                    <div class="herramienta-icon">⏰</div>
                    <h3>Tiempo de Entrega</h3>
                    <p>Consulta tiempos estimados entre zonas</p>
                    <button class="cta-button" style="margin-top: 1rem; font-size: 1rem; padding: 10px 25px;">Consultar</button>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
    
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
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(102, 126, 234, 0.95)';
            } else {
                header.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
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
            // Initialize the map centered on Bogotá
            var map = L.map('map').setView([4.6097, -74.0817], 11);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Define coverage zones with different colors
            var zonaVerde = {
                color: '#28a745',
                weight: 2,
                opacity: 0.8,
                fillColor: '#28a745',
                fillOpacity: 0.3
            };

            var zonaAzul = {
                color: '#007bff',
                weight: 2,
                opacity: 0.8,
                fillColor: '#007bff',
                fillOpacity: 0.3
            };

            var zonaAmarilla = {
                color: '#ffc107',
                weight: 2,
                opacity: 0.8,
                fillColor: '#ffc107',
                fillOpacity: 0.3
            };

            // Centro/Zona Rosa (Verde - 2-3 horas)
            var centro = L.polygon([
                [4.6200, -74.0900],
                [4.6200, -74.0700],
                [4.5950, -74.0700],
                [4.5950, -74.0900]
            ], zonaVerde).addTo(map);
            centro.bindPopup("<b>Centro/Zona Rosa</b><br>Tiempo: 2-3 horas<br>Tarifa: Desde $8.500");

            // Chapinero (Verde - 3-4 horas)  
            var chapinero = L.polygon([
                [4.6400, -74.0800],
                [4.6400, -74.0500],
                [4.6200, -74.0500],
                [4.6200, -74.0800]
            ], zonaVerde).addTo(map);
            chapinero.bindPopup("<b>Chapinero</b><br>Tiempo: 3-4 horas<br>Tarifa: Desde $9.500");

            // Usaquén (Azul - 3-4 horas)
            var usaquen = L.polygon([
                [4.7000, -74.0600],
                [4.7000, -74.0300],
                [4.6600, -74.0300],
                [4.6600, -74.0600]
            ], zonaAzul).addTo(map);
            usaquen.bindPopup("<b>Usaquén</b><br>Tiempo: 3-4 horas<br>Tarifa: Desde $10.500");

            // Kennedy (Amarillo - 4-5 horas)
            var kennedy = L.polygon([
                [4.6200, -74.1500],
                [4.6200, -74.1100],
                [4.5700, -74.1100],
                [4.5700, -74.1500]
            ], zonaAmarilla).addTo(map);
            kennedy.bindPopup("<b>Kennedy</b><br>Tiempo: 4-5 horas<br>Tarifa: Desde $12.000");

            // Fontibón (Amarillo - 4-5 horas)
            var fontibon = L.polygon([
                [4.6800, -74.1600],
                [4.6800, -74.1200],
                [4.6400, -74.1200],
                [4.6400, -74.1600]
            ], zonaAmarilla).addTo(map);
            fontibon.bindPopup("<b>Fontibón</b><br>Tiempo: 4-5 horas<br>Tarifa: Desde $12.000");

            // Suba (Azul - 4-6 horas)
            var suba = L.polygon([
                [4.7600, -74.1200],
                [4.7600, -74.0800],
                [4.7200, -74.0800],
                [4.7200, -74.1200]
            ], zonaAzul).addTo(map);
            suba.bindPopup("<b>Suba</b><br>Tiempo: 4-6 horas<br>Tarifa: Desde $11.500");

            // Engativá (Azul - 4-6 horas)
            var engativa = L.polygon([
                [4.7200, -74.1400],
                [4.7200, -74.1000],
                [4.6800, -74.1000],
                [4.6800, -74.1400]
            ], zonaAzul).addTo(map);
            engativa.bindPopup("<b>Engativá</b><br>Tiempo: 4-6 horas<br>Tarifa: Desde $11.500");

            // Bosa (Amarillo - 5-7 horas)
            var bosa = L.polygon([
                [4.6000, -74.2000],
                [4.6000, -74.1600],
                [4.5600, -74.1600],
                [4.5600, -74.2000]
            ], zonaAmarilla).addTo(map);
            bosa.bindPopup("<b>Bosa</b><br>Tiempo: 5-7 horas<br>Tarifa: Desde $13.500");

            // Ciudad Bolívar (Amarillo - 5-7 horas)
            var ciudadBolivar = L.polygon([
                [4.5800, -74.1800],
                [4.5800, -74.1400],
                [4.5200, -74.1400],
                [4.5200, -74.1800]
            ], zonaAmarilla).addTo(map);
            ciudadBolivar.bindPopup("<b>Ciudad Bolívar</b><br>Tiempo: 5-7 horas<br>Tarifa: Desde $13.500");

            // Add headquarters marker
            var headquartersIcon = L.divIcon({
                className: 'headquarters-marker',
                html: '<div style="background: #ff6b6b; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">🏢</div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            var headquarters = L.marker([4.6350, -74.1139], {icon: headquartersIcon}).addTo(map);
            headquarters.bindPopup("<b>RapidoEnvíos - Sede Principal</b><br>Calle 26 #68-90, Bogotá<br>📞 (601) 123-4567");

            // Add some delivery points
            var deliveryIcon = L.divIcon({
                className: 'delivery-marker',
                html: '<div style="background: #28a745; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px;">🚚</div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            // Add some example delivery markers
            var deliveries = [
                {coords: [4.6500, -74.0600], popup: "Entrega en Zona Rosa<br>Estado: En camino"},
                {coords: [4.6800, -74.0500], popup: "Entrega en Chapinero<br>Estado: Entregado ✓"},
                {coords: [4.5900, -74.1200], popup: "Entrega en Kennedy<br>Estado: Recogido"},
                {coords: [4.7100, -74.0400], popup: "Entrega en Usaquén<br>Estado: En tránsito"}
            ];

            deliveries.forEach(function(delivery) {
                L.marker(delivery.coords, {icon: deliveryIcon}).addTo(map)
                    .bindPopup(delivery.popup);
            });
        });
    </script>
</body>
</html>