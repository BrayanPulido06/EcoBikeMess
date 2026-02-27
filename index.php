<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoBikeMess - Mensajería Ecológica en Bicicleta</title>
    <link rel="stylesheet" href="public/css/styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <h1><img src="../public/img/Logo_Circulo_Fondoblanco.png" alt="Logo EcoBikeMess" style="height: 40px; vertical-align: middle; margin-right: 5px;"> EcoBikeMess</h1>
            </div>
            <div class="nav-links">
                <a href="#inicio">Inicio</a>
                <a href="#about">Nosotros</a>
                <a href="#services">Servicios</a>
                <a href="#pricing">Tarifas</a>
                <a href="#contact">Contacto</a>
            </div>
            <a href="views/login.php" class="btn-login" id="btnLogin">Iniciar Sesión</a>
        </nav>
    </header>

    <main>
        <!-- Sección Hero -->
        <section class="hero">
            <div class="hero-content">
                <h2>Mensajería Rápida y Ecológica</h2>
                <p>Entregas sostenibles en bicicleta por toda Bogotá</p>
            </div>
        </section>

        <!-- Información de EcoBikeMess -->
        <section class="about">
            <div class="container">
                <h2>¿Quiénes Somos?</h2>
                <p>EcoBikeMess es una empresa de mensajería urbana comprometida con el medio ambiente. Utilizamos bicicletas para realizar entregas rápidas, eficientes y sin emisiones de CO2 en toda Bogotá.</p>
                <p>Con más de 5 años de experiencia, nos hemos convertido en líderes en logística sostenible, atendiendo a empresas y particulares con el mismo nivel de profesionalismo y rapidez.</p>
            </div>
        </section>

        <!-- Servicios -->
        <section class="services">
            <div class="container">
                <h2>Nuestros Servicios</h2>
                <div class="services-grid">
                    <div class="service-card">
                        <h3>📦 Cobertura y precios base</h3>
                        <p>$8.000: Envío estándar en Bogotá</p>
                        <button class="btn-expand" data-service="express">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-express">
                            <p><strong>Características:</strong></p>
                            <ul>
                                <li>Todo paquete que sean menores o iguales a 20x20x20 cm</li>
                                <li>Para Soacha y zonas verdes oscuras en Bogotá tendra un adicional de $4.000</li>
                                <li>Todo paquete con tarifa plena tendra entre 1 a 2 dias de entrega</li>
                                <li>Garantía de entrega en 2 horas</li>
                                <li>Servicio disponible de lunes a sábado de 8:00 AM a 6:00 PM</li>
                                <li>Notificación por SMS y correo electrónico</li>
                            </ul>
                        </div>
                    </div>

                    <div class="service-card">
                        <h3>🏢 Mensajería Corporativa</h3>
                        <p>Soluciones personalizadas para empresas</p>
                        <button class="btn-expand" data-service="corporate">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-corporate">
                            <p><strong>Características:</strong></p>
                            <ul>
                                <li>Planes mensuales con tarifas preferenciales</li>
                                <li>Mensajero dedicado para tu empresa</li>
                                <li>Facturación electrónica</li>
                                <li>Reporte de entregas mensual</li>
                                <li>Atención prioritaria</li>
                                <li>Integración con sistemas de gestión empresarial</li>
                            </ul>
                        </div>
                    </div>

                    <div class="service-card">
                        <h3>🍔 Delivery de Alimentos</h3>
                        <p>Comida fresca y caliente a tu puerta</p>
                        <button class="btn-expand" data-service="food">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-food">
                            <p><strong>Características:</strong></p>
                            <ul>
                                <li>Bolsas térmicas especializadas</li>
                                <li>Entrega en máximo 45 minutos</li>
                                <li>Manejo especial para bebidas</li>
                                <li>Servicio disponible de 11:00 AM a 10:00 PM</li>
                                <li>Sin costos adicionales en pedidos mayores a $30.000</li>
                                <li>Alianzas con restaurantes locales</li>
                            </ul>
                        </div>
                    </div>

                    <div class="service-card">
                        <h3>📄 Documentos Urgentes</h3>
                        <p>Envíos seguros y confidenciales</p>
                        <button class="btn-expand" data-service="documents">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-documents">
                            <p><strong>Características:</strong></p>
                            <ul>
                                <li>Sobres sellados con código de seguridad</li>
                                <li>Entrega con firma digital</li>
                                <li>Seguro incluido</li>
                                <li>Confidencialidad garantizada</li>
                                <li>Servicio express en 1 hora</li>
                                <li>Certificado de entrega en PDF</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tarifas y Zonas -->
        <section class="pricing">
            <div class="container">
                <h2>Tarifas y Zonas de Cobertura</h2>
                
                <div class="pricing-content">
                    <div class="pricing-table">
                        <h3>Tarifas por Zona</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Zona</th>
                                    <th>Precio Base</th>
                                    <th>Tiempo Estimado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Zona Norte</td>
                                    <td>$8.000</td>
                                    <td>45-60 min</td>
                                </tr>
                                <tr>
                                    <td>Zona Centro</td>
                                    <td>$6.000</td>
                                    <td>30-45 min</td>
                                </tr>
                                <tr>
                                    <td>Zona Sur</td>
                                    <td>$7.500</td>
                                    <td>45-60 min</td>
                                </tr>
                                <tr>
                                    <td>Zona Occidente</td>
                                    <td>$7.000</td>
                                    <td>40-55 min</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="note">*Precios sujetos a peso y dimensiones del paquete</p>
                    </div>

                    <div class="map-container">
                        <h3>Mapa de Cobertura - Bogotá</h3>
                        <svg id="bogotaMap" viewBox="0 0 400 500">
                            <!-- Contorno de Bogotá simplificado -->
                            <path d="M 200 50 L 250 80 L 280 120 L 300 180 L 310 250 L 300 320 L 280 380 L 250 430 L 200 470 L 150 450 L 120 400 L 100 340 L 90 280 L 100 220 L 120 160 L 150 100 L 200 50 Z" 
                                  fill="#e8f5e9" 
                                  stroke="#2e7d32" 
                                  stroke-width="2"/>
                            
                            <!-- Zona Norte -->
                            <path class="zone zone-north" data-zone="norte"
                                  d="M 200 50 L 250 80 L 280 120 L 270 140 L 200 120 L 130 140 L 120 120 L 150 100 L 200 50 Z"/>
                            <text x="200" y="110" class="zone-label">NORTE</text>
                            
                            <!-- Zona Centro -->
                            <path class="zone zone-center" data-zone="centro"
                                  d="M 130 140 L 200 120 L 270 140 L 280 220 L 200 240 L 120 220 L 130 140 Z"/>
                            <text x="200" y="190" class="zone-label">CENTRO</text>
                            
                            <!-- Zona Sur -->
                            <path class="zone zone-south" data-zone="sur"
                                  d="M 120 220 L 200 240 L 280 220 L 280 320 L 250 380 L 200 420 L 150 380 L 120 320 L 120 220 Z"/>
                            <text x="200" y="310" class="zone-label">SUR</text>
                            
                            <!-- Zona Occidente -->
                            <path class="zone zone-west" data-zone="occidente"
                                  d="M 90 180 L 120 140 L 120 320 L 100 340 L 90 280 L 90 180 Z"/>
                            <text x="105" y="240" class="zone-label-small">OCCIDENTE</text>
                        </svg>
                        <div class="map-legend">
                            <div class="legend-item">
                                <span class="legend-color" style="background: #4CAF50;"></span>
                                <span>Zona Norte (Usaquén, Chapinero)</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background: #2196F3;"></span>
                                <span>Zona Centro (Teusaquillo, Santa Fe)</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background: #FF9800;"></span>
                                <span>Zona Sur (Kennedy, Bosa, Usme)</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background: #9C27B0;"></span>
                                <span>Zona Occidente (Fontibón, Engativá)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contacto -->
        <section class="contact">
            <div class="container">
                <h2>Contáctanos</h2>
                <div class="contact-grid">
                    <div class="contact-item">
                        <div class="contact-icon">📞</div>
                        <h3>Teléfono</h3>
                        <p>+57 (1) 234-5678</p>
                        <p>+57 300 123-4567</p>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">📍</div>
                        <h3>Dirección</h3>
                        <p>Calle 26 # 68-91</p>
                        <p>Bogotá, Colombia</p>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">✉️</div>
                        <h3>Email</h3>
                        <p>info@ecobikemess.com</p>
                        <p>soporte@ecobikemess.com</p>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">🕒</div>
                        <h3>Horario</h3>
                        <p>Lun - Sáb: 8:00 AM - 8:00 PM</p>
                        <p>Dom: 10:00 AM - 4:00 PM</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 EcoBikeMess. Todos los derechos reservados.</p>
            <p>Mensajería ecológica para un futuro sostenible 🌱</p>
        </div>
    </footer>

    <script src="public/js/script.js"></script>
</body>
</html>