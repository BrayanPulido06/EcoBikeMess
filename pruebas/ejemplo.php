<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapidoEnvíos - Mensajería y Paquetería en Bogotá</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
    <link rel="stylesheet" href="ejemplo.css">
</head>
<body>
    <!-- Header -->
    <header class="navbar">
        <div class="nav-container">
            
            <div class="logo"> <img src="public/img/logonegro.png" alt="EcoBikeMess Logo" style="height: 100px; margin-right: 5px;"> EcoBikeMess</div>
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
            <p>Conectamos tu negocio con tus clientes a través de la mejor red de mensajería urbana con tecnología de vanguardia</p>
            <a href="#cotizar" class="cta-button">Envía tu Paquete Ahora</a>
        </div>
    </section>

    <!-- Cómo Funciona -->
    <section class="como-funciona">
        <div class="container">
            <h2 class="section-title">¿Cómo Funciona?</h2>
            <div class="pasos">
                <div class="paso fade-in">
                    <div class="paso-numero">1</div>
                    <h3>Solicita</h3>
                    <p>Programa tu recogida online o por teléfono. Especifica origen, destino y tipo de servicio de manera rápida y sencilla.</p>
                </div>
                <div class="paso fade-in">
                    <div class="paso-numero">2</div>
                    <h3>Recogemos</h3>
                    <p>Nuestro mensajero más cercano llega puntual a recoger tu paquete en la dirección indicada con total profesionalismo.</p>
                </div>
                <div class="paso fade-in">
                    <div class="paso-numero">3</div>
                    <h3>Procesamos</h3>
                    <p>Tu envío ingresa a nuestro sistema de seguimiento avanzado y se asigna automáticamente al mejor mensajero disponible.</p>
                </div>
                <div class="paso fade-in">
                    <div class="paso-numero">4</div>
                    <h3>Entregamos</h3>
                    <p>Entregamos en tiempo récord con confirmación digital, evidencia fotográfica y notificación automática al destinatario.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Servicios -->
    <section class="servicios" id="servicios">
        <div class="container">
            <h2 class="section-title">Nuestros Servicios Premium</h2>
            <div class="servicios-grid">
                <div class="servicio fade-in">
                    <div class="servicio-icon">🚚</div>
                    <h3>Estándar</h3>
                    <p>Entregas confiables en 4-6 horas dentro de Bogotá. Perfecto para envíos regulares sin prisa pero con la garantía de calidad.</p>
                    <div class="precio">Desde $8.500</div>
                </div>
                <div class="servicio fade-in">
                    <div class="servicio-icon">⚡</div>
                    <h3>Express</h3>
                    <p>Entregas ultra rápidas en 2-3 horas. Ideal para documentos importantes y paquetes urgentes que no pueden esperar.</p>
                    <div class="precio">Desde $15.000</div>
                </div>
                <div class="servicio fade-in">
                    <div class="servicio-icon">🏃‍♂️</div>
                    <h3>Mismo Día</h3>
                    <p>Entrega garantizada el mismo día. Para envíos críticos que requieren máxima velocidad y confiabilidad absoluta.</p>
                    <div class="precio">Desde $25.000</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cobertura -->
    <section class="cobertura" id="cobertura">
        <div class="container">
            <h2 class="section-title">Cobertura Completa en Bogotá</h2>
            <div class="mapa-container">
                <div class="mapa-placeholder">
                    <div id="map"></div>
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
                    <div class="zona">San Cristóbal</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Herramientas Públicas -->
    <section class="herramientas" id="rastreo">
        <div class="container">
            <h2 class="section-title">Herramientas Gratuitas</h2>
            <div class="herramientas-grid">
                <div class="herramienta fade-in">
                    <div class="herramienta-icon">💰</div>
                    <h3>Calculadora de Tarifas</h3>
                    <p>Cotiza el costo exacto de tu envío sin necesidad de registro. Obtén precios transparentes al instante.</p>
                    <button onclick="alert('Redirigiendo a calculadora de tarifas...')">Calcular Precio</button>
                </div>
                <div class="herramienta fade-in">
                    <div class="herramienta-icon">📦</div>
                    <h3>Rastreo de Paquetes</h3>
                    <p>Consulta el estado de tu envío en tiempo real con nuestro sistema de seguimiento avanzado GPS.</p>
                    <button onclick="alert('Redirigiendo a sistema de rastreo...')">Rastrear Envío</button>
                </div>
                <div class="herramienta fade-in">
                    <div class="herramienta-icon">🗺️</div>
                    <h3>Mapa de Cobertura</h3>
                    <p>Verifica si llegamos a tu zona de entrega y consulta tiempos estimados para tu ubicación.</p>
                    <button onclick="alert('Mostrando mapa interactivo...')">Ver Cobertura</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contacto">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Servicios</h3>
                    <ul>
                        <li><a href="#">Envío Estándar</a></li>
                        <li><a href="#">Envío Express</a></li>
                        <li><a href="#">Mismo Día</a></li>
                        <li><a href="#">Programado</a></li>
                        <li><a href="#">Corporativo</a></li>
                        <li><a href="#">Tarifas Especiales</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Empresa</h3>
                    <ul>
                        <li><a href="#">Sobre Nosotros</a></li>
                        <li><a href="#">Cobertura Total</a></li>
                        <li><a href="#">Blog y Noticias</a></li>
                        <li><a href="#">Trabaja con Nosotros</a></li>
                        <li><a href="#">Prensa</a></li>
                        <li><a href="#">Sostenibilidad</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <ul>
                        <li>📞 Línea Nacional: (601) 123-4567</li>
                        <li>📱 WhatsApp: 300 123 4567</li>
                        <li>✉️ info@rapidoenvios.com</li>
                        <li>📧 soporte@rapidoenvios.com</li>
                        <li>📍 Calle 26 #68-90, Bogotá D.C.</li>
                        <li>🕒 Atención: Lun-Vie 7AM-7PM</li>
                        <li>🚚 Servicio: 24/7 disponible</li>
                        <li>🚚 Facebook</li>
                        <li>🚚 Twitter</li>
                        <li>🚚 Instagram</li>
                        <li>🚚 LinkedIn</li>
                        <li>🚚 Youtube</li>
                        <li>🚚 TikTok</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 EcoBikeMess Todos los derechos reservados. | Política de Privacidad | Términos de Servicio | Cookies | Mapa del Sitio</p>
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

        // Enhanced scroll effect for header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(247, 251, 255, 0.98)';
                header.style.boxShadow = '0 4px 25px rgba(18, 140, 126, 0.2)';
                header.style.borderBottom = '3px solid #A8E6CF';
            } else {
                header.style.background = 'rgba(247, 251, 255, 0.95)';
                header.style.boxShadow = '0 4px 20px rgba(18, 140, 126, 0.1)';
                header.style.borderBottom = '3px solid #128C7E';
            }
        });

        // Initialize enhanced map with custom styling
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the map centered on Bogotá
            var map = L.map('map', {
                zoomControl: true,
                scrollWheelZoom: false
            }).setView([4.6097, -74.0817], 11);

            // Add tile layer with custom styling
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18,
                opacity: 0.9
            }).addTo(map);

            // Create custom headquarters icon
            var headquartersIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background: linear-gradient(135deg, #128C7E, #A8E6CF); color: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 4px solid white; box-shadow: 0 4px 15px rgba(18, 140, 126, 0.4); animation: pulse 3s infinite;"><span>🏢</span></div><style>@keyframes pulse { 0% { box-shadow: 0 4px 15px rgba(18, 140, 126, 0.4); } 50% { box-shadow: 0 4px 25px rgba(18, 140, 126, 0.7); } 100% { box-shadow: 0 4px 15px rgba(18, 140, 126, 0.4); } }</style>',
                iconSize: [35, 35],
                iconAnchor: [17, 17]
            });

            // Add headquarters marker
            var headquarters = L.marker([4.6350, -74.1139], {icon: headquartersIcon}).addTo(map);
            headquarters.bindPopup("<div style='text-align: center; font-family: Georgia, serif; padding: 8px;'><b style='color: #128C7E;'>🏢 RapidoEnvíos - Sede Principal</b><br><br>📍 Calle 61 #68-90, Bogotá D.C.<br>📞 (601) 123-4567<br>🕒 Servicio 24/7<br><br><span style='color: #128C7E; font-weight: bold; background: #A8E6CF; padding: 4px 8px; border-radius: 10px;'>¡Estamos aquí para servirte!</span></div>");

            // Create custom delivery icon
            var deliveryIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background: linear-gradient(135deg, #2196F3, #128C7E); color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 14px; border: 3px solid white; box-shadow: 0 3px 10px rgba(33, 150, 243, 0.3);">🚚</div>',
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            // Enhanced delivery points with more detail
            var deliveryPoints = [
                {coords: [4.6482, -74.0637], popup: "<div style='text-align: center; font-family: Georgia, serif;'><b style='color: #128C7E;'>🚚 Centro Histórico</b><br>📦 Zona de máxima demanda<br>⏱️ Tiempo promedio: <strong style='color: #2196F3;'>2-3 horas</strong><br>📊 +500 entregas diarias</div>"},
                {coords: [4.6736, -74.0465], popup: "<div style='text-align: center; font-family: Georgia, serif;'><b style='color: #128C7E;'>🚚 Chapinero</b><br>📦 Zona comercial premium<br>⏱️ Tiempo promedio: <strong style='color: #2196F3;'>3-4 horas</strong><br>📊 +400 entregas diarias</div>"},
                {coords: [4.7110, -74.0721], popup: "<div style='text-align: center; font-family: Georgia, serif;'><b style='color: #128C7E;'>🚚 Usaquén</b><br>📦 Zona residencial exclusiva<br>⏱️ Tiempo promedio: <strong style='color: #2196F3;'>3-4 horas</strong><br>📊 +350 entregas diarias</div>"},
                {coords: [4.5981, -74.1421], popup: "<div style='text-align: center; font-family: Georgia, serif;'><b style='color: #128C7E;'>🚚 Kennedy</b><br>📦 Gran volumen de entregas<br>⏱️ Tiempo promedio: <strong style='color: #2196F3;'>4-5 horas</strong><br>📊 +600 entregas diarias</div>"},
                {coords: [4.6860, -74.1311], popup: "<div style='text-align: center; font-family: Georgia, serif;'><b style='color: #128C7E;'>🚚 Suba</b><br>📦 Zona en expansión<br>⏱️ Tiempo promedio: <strong style='color: #2196F3;'>4-6 horas</strong><br>📊 +300 entregas diarias</div>"},
                {coords: [4.5287, -74.1624], popup: "<div style='text-align: center; font-family: Georgia, serif;'><b style='color: #128C7E;'>🚚 Bosa</b><br>📦 Cobertura completa garantizada<br>⏱️ Tiempo promedio: <strong style='color: #2196F3;'>5-7 horas</strong><br>📊 +250 entregas diarias</div>"},
                {coords: [4.6629, -74.1067], popup: "<div style='text-align: center; font-family: Georgia, serif;'><b style='color: #128C7E;'>🚚 Engativá</b><br>📦 Ruta optimizada<br>⏱️ Tiempo promedio: <strong style='color: #2196F3;'>4-6 horas</strong><br>📊 +280 entregas diarias</div>"}
            ];

            // Add delivery points to map
            deliveryPoints.forEach(function(point) {
                L.marker(point.coords, {icon: deliveryIcon}).addTo(map)
                    .bindPopup(point.popup);
            });

            // Add enhanced coverage area circle
            var coverageCircle = L.circle([4.6097, -74.0817], {
                color: '#128C7E',
                fillColor: '#A8E6CF',
                fillOpacity: 0.15,
                radius: 28000,
                weight: 4,
                dashArray: '15, 10',
                opacity: 0.8
            }).addTo(map);

            coverageCircle.bindPopup("<div style='text-align: center; font-family: Georgia, serif; padding: 10px;'><b style='color: #128C7E; font-size: 16px;'>🌍 Área de Cobertura RapidoEnvíos</b><br><br>📍 Toda Bogotá y área metropolitana<br>📊 99.5% de tasa de éxito garantizada<br>🚚 3,500+ entregas diarias<br>⚡ Tecnología GPS en tiempo real<br><br><span style='background: #A8E6CF; color: #128C7E; padding: 6px 12px; border-radius: 15px; font-weight: bold;'>¡Servicio Premium 24/7!</span></div>");

            // Add scale control
            L.control.scale({
                position: 'bottomleft',
                imperial: false
            }).addTo(map);

            // Custom info control
            var info = L.control({position: 'topright'});
            info.onAdd = function (map) {
                var div = L.DomUtil.create('div', 'info');
                div.innerHTML = '<div style="background: rgba(247, 251, 255, 0.95); padding: 12px; border-radius: 15px; box-shadow: 0 4px 15px rgba(18, 140, 126, 0.2); font-family: Georgia, serif; border: 2px solid #A8E6CF;"><b style="color: #128C7E;">🗺️ Mapa Interactivo</b><br><small style="color: #2196F3;">Haz clic en los marcadores para más información</small></div>';
                return div;
            };
            info.addTo(map);
        });

        // Enhanced fade-in animation with Intersection Observer
        const observerOptions = {
            threshold: 0.15,
            rootMargin: '0px 0px -80px 0px'
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
        document.querySelectorAll('.fade-in').forEach(section => {
            observer.observe(section);
        });

        // Enhanced hover effects
        document.querySelectorAll('.paso, .servicio, .herramienta, .blog-post, .zona').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.03)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Add loading animation for the page
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>