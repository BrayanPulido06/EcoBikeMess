<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoBikeMess - Mensajería Ecológica en Bicicleta</title>
    <link rel="icon" href="public/img/Logo_Negro_Transparente.png" type="image/png">
    <link rel="stylesheet" href="public/css/styles.css">
    <style>
        /* Aumentamos el límite de altura para que no se corte el texto al desplegar */
        .service-details.show {
            max-height: 2000px !important;
        }

        /* Estilos del Botón Flotante de WhatsApp */
        .whatsapp-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .whatsapp-btn:hover {
            background-color: #128c7e;
            transform: scale(1.1);
            box-shadow: 2px 2px 15px rgba(0,0,0,0.3);
        }
        .whatsapp-btn img {
            width: 35px;
            height: 35px;
        }

        /* Ajustes de Responsividad */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 992px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px;
                text-align: center;
            }
            .nav-links {
                margin: 15px 0;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            .services-grid {
                grid-template-columns: 1fr;
            }
            .hero h2 {
                font-size: 1.8rem;
            }
            .btn-login {
                width: 100%;
                display: block;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <h1><img src="public/img/Logo_Circulo_Fondoblanco.png" alt="Logo" style="width: 90px; vertical-align: middle;"> EcoBikeMess</h1>
            </div>
            <div class="nav-links">
                <a href="#inicio">Inicio</a>
                <a href="#about">Nosotros</a>
                <a href="#services">Servicios</a>
                <a href="#pricing">Cobertura</a>
                <a href="#contact">Contacto</a>
            </div>
            <a href="views/login.php" class="btn-login" id="btnLogin">Iniciar Sesión</a>
        </nav>
    </header>

    <main>
        <!-- Sección Hero -->
        <section class="hero" id="inicio">
            <div class="hero-content">
                <h2>Mensajería Rápida y Ecológica</h2>
                <p>Entregas sostenibles en bicicleta por toda Bogotá</p>
            </div>
        </section>

        <!-- Información de EcoBikeMess -->
        <section class="about" id="about">
            <div class="container">
                <h2>¿Quiénes Somos?</h2>
                <p>EcoBikeMess es una empresa de mensajería urbana comprometida con el medio ambiente. Utilizamos bicicletas para realizar entregas rápidas, eficientes y sin emisiones de CO2 en toda Bogotá.</p>
                <p>Con más de 7 años de experiencia, nos hemos convertido en líderes en logística sostenible, atendiendo a empresas y particulares con el mismo nivel de profesionalismo y rapidez.</p>
            </div>
        </section>

        <!-- Servicios -->
        <section class="services" id="services">
            <div class="container">
                <h2>Nuestros Servicios</h2>
                <div class="services-grid">
                    <div class="service-card">
                        <h3>Tarifa Emprendedor</h3>
                        <p>Ideal para tus envios con una tarfia fija</p>
                        <button class="btn-expand" data-service="express">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-express">
                            <p><strong>Entregas:</strong></p>
                            <ul>
                                <li>Mismo día o siguiente: Gestionamos tu pedido en el transcurso del día (sin horario fijo).</li>
                                <li>Prioridad same-day: Asegura tu envío el mismo día por un adicional de $2.000.</li>
                            </ul>
                            <p><strong>Cobertura y precios base:</strong></p>
                            <ul>
                                <li>$8.000: Envío estándar en Bogotá (paquetes hasta 2kg y 20x20x20cm).</li>
                                <li>$12.000: Para Soacha y zonas verdes oscuras en Bogotá (ver mapa de cobertura).</li>
                            </ul>
                            <p><strong>⚠️ Importante:</strong></p>
                            <ul>
                                <li>Programación: Solicita tu envío antes de las 10:00 a.m. (después de esta hora, queda sujeto a disponibilidad).</li>
                                <li>Horarios específicos: Si necesitas una hora  de máxima de entrega, puede tener un adicional o aplicar la tarfia oportuna</li>
                                <li>Factores externos: Lluvia, tráfico u alta demanda pueden retrasar tu envío al día siguiente.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="service-card">
                        <h3>Tarifa Oportuna</h3>
                        <p>Entregas rápidas y/o con horarios</p>
                        <button class="btn-expand" data-service="corporate">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-corporate">
                            <p><strong>Valor del servicio:</strong></p>
                            <ul>
                                <li>$12.000: Cubre los primeros 7 km (desde el punto de recogida hasta la entrega).</li>
                                <li>$1.500 Por cada km adicional.</li>
                            </ul>
                            <p><strong>Especificaciones del paquete:</strong></p>
                            <ul>
                                <li>Tamaño maximo: 30x30x30 cm.</li>
                                <li>Peso máximo: 3 kg.(Si tu paquete excede estas medidas, puede tener un adicional). </li>
                            </ul>
                            <p><strong>¿Cómo funciona?</strong></p>
                            <ul>
                                <li>Asignamos un mensajero exclusivo para gestionar tu envío.</li>
                                <li>Entrega express: Lo más rápido posible o en el horario que nos indiques.</li>
                                <li>Ruta optimizada: Calculamos la tarifa con base en la distancia real (Google Maps/Waze).</li>
                            </ul>
                        </div>
                    </div>

                    <div class="service-card">
                        <h3>Servicio Contraentrega</h3>
                        <p>¡Para que tu cliente cancele al recibir!</p>
                        <button class="btn-expand" data-service="contraentrega">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-contraentrega">
                            <p><strong>¿Cómo funciona?</strong></p>
                            <ul>
                                <li>Al solicitar el servicio, indícanos el monto total a cobrar a tu cliente.</li>
                                <li>Nuestro mensajero recogerá el pago al entregar el paquete (nos pueden pagar en efectivo o transferencia ).</li>
                            </ul>
                            <p><strong>Devolución del dinero:</strong></p>
                            <ul>
                                <li>Máximo en 3 días hábiles después de la entrega.</li>
                                <li>Métodos: Transferencia a Nequi, Daviplata, Davivienda o Bancolombia (o efectivo si es posible).</li>
                            </ul>
                            <p><strong>Tarifas transparentes:</strong></p>
                            <ul>
                                <li>Costo fijo: $3.000 adicionales al valor del envío.</li>
                                <li>Para recaudos mayores a $300.000 equivale al 1% del monto ($1.000 por cada $100.000).</li>
                            </ul>
                        </div>
                    </div>

                    <div class="service-card">
                        <h3>Retorno de Paquetes</h3>
                        <p>Servicios donde se entrega un paquete y se recoge otro con dimensiones similares.</p>
                        <button class="btn-expand" data-service="retorno">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-retorno">
                            <p><strong>¿Cómo funciona?</strong></p>
                            <ul>
                                <li>Entregamos un paquete a tu cliente.</li>
                                <li>Recogemos otro paquete de dimensiones similares en el mismo lugar.</li>
                            </ul>
                            <p><strong>Tarifas:</strong></p>
                            <ul>
                                <li>Retorno en 3 días hábiles: $5.000 adicionales al servicio original.</li>
                                <li>Retorno al día siguiente: Aplica tarifa normal (Emprendedor u Oportuna).</li>
                            </ul>
                            <p><strong>⚠️ Importante:</strong></p>
                            <ul>
                                <li>El paquete a recoger debe tener un tamaño/peso similar al entregado</li>
                            </ul>
                        </div>
                    </div>
                    <div class="service-card">
                        <h3>Servicios Cancelados y/o Devoluciones</h3>
                        <p>En caso de que no se pueda completar la entrega por causas ajenas a Eco BikeMess.</p>
                        <button class="btn-expand" data-service="cancelados">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-cancelados">
                            <p><strong>Cancelación Antes de la Entrega:</strong></p>
                            <ul>
                                <p><strong>Si el cliente cancela el pedido después de que el mensajero ya salió a distribución:</strong></p>
                                <li>Costo: $5.000 (cubre logística y desplazamiento)</li>
                                <li>Devolución del paquete: 3 días hábiles</li>
                            </ul>
                            <p><strong>Cliente Ausente o no Responde:</strong></p>
                            <ul>
                                <p><strong>Primer Intento:</strong></p>
                                <li>Intentamos contacto telefónico o vía WhatsApp</li>
                                <li>Si no hay respuesta, reprogramamos para el día siguiente sin costo</li>
                                <p><strong>Segundo Intento:</strong></p>
                                <li>Si el cliente no ha dado respuesta por llamada o por Whatsapp, se retorna el paquete y se cobra $5.000 por servicio</li>
                                <li>Devolución: 3 días hábiles</li> 
                            </ul>
                            <p><strong>Cliente Rechaza el Paquete:</strong></p>  

                            <ul>
                                <p><strong>Si el destinatario se niega a recibir el envío::</strong></p>
                                <li>Se cobra el valor completo del servicio ($8.000 o $11.000 según sea entrega o contraentrega)</li>
                                <li>Devolución del paquete: 3 días hábiles</li>
                            </ul>
                        </div>
                    </div>

                    <div class="service-card">
                        <h3>Servicio de Packing</h3>
                        <p>Paquetes sin sellar o sin información del destinatario.</p>
                        <button class="btn-expand" data-service="extra">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-extra">
                            <p><strong>Tarifas simples:</strong></p>
                            <ul>
                                <li>$2.000 Incluye: embalaje + sellado + rotulado* con los datos del cliente.</li>
                                <li>$1.000: Solo rotulado (si el paquete ya está empacado).</li>
                            </ul>
                            <p><strong>Datos requeridos:</strong></p>
                            <ul>
                                <li>Dirección exacta (torre/apto si aplica)</li>
                                <li>Nombre y teléfono del destinatario.</li>
                                <li>Observaciones (ej.: "entregar solo a nombre de...").</li>
                                <li>Indicar si es contraentrega (especificar el valor del recaudo).</li>
                            </ul>
                            <p><strong>Beneficios:</strong></p>
                            <ul>
                                <li>Prevención de pérdidas: Etiquetamos claramente tu paquete.</li>
                                <li>Protección básica: Sellado seguro para evitar aperturas accidentales.</li>
                                <li>Ahorro de tiempo: Nos ocupamos de lo técnico, tú enfócate en lo importante</li>
                            </ul>
                            <p><strong>⚠️ No Embalamos:</strong></p>
                            <ul>
                                <li>Objetos delicados (vidrio, cerámica, porcelana).</li>
                                <li>Alimentos o líquidos.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="service-card">
                        <h3>Tiempo de Espera</h3>
                        <p>Servicios que requieran esperar en un mismo punto.</p>
                        <button class="btn-expand" data-service="espera">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-espera">
                            <p><strong>¿Cómo funciona?</strong></p>
                            <ul>
                                <li>Primeros 20 minutos: Incluidos en el servicio sin costo adicional.</li>
                                <li>Después de 20 minutos: Se aplica un recargo de $2.000 por cada 10 minutos de espera.</li>
                            </ul>
                            <p><strong> Recomendaciones:</strong></p>
                            <ul>
                                <li>Programa entregas con tiempo suficiente para evitar esperas.</li>
                                <li>Comunica cambios de horario con anticipación.</li>
                            </ul>
                            <p><strong>Mensaje amigable:</strong></p>
                            <ul>
                                <li>"Valoramos el tiempo de todos. ¡Coordina con tu destinatario para que todo esté listo al llegar nuestro mensajero!🚴💨"</li>
                            </ul>
                        </div>
                    </div>
                    <div class="service-card">
                        <h3>Adicionales</h3>
                        <p>Informacion a tener en cuenta.</p>
                        <button class="btn-expand" data-service="adicionales">
                            <span>Ver más detalles</span>
                            <span class="arrow">▼</span>
                        </button>
                        <div class="service-details" id="details-adicionales">
                            <p><strong>Características:</strong></p>
                            <ul>
                                <li>Facturación electrónica</li>
                                <li>Reporte de entregas mensual</li>
                                <li>Atención prioritaria</li>
                            </ul>
                            <p><strong>Características:</strong></p>
                            <ul>
                                <li>Facturación electrónica</li>
                                <li>Reporte de entregas mensual</li>
                                <li>Atención prioritaria</li>
                            </ul>
                            <p><strong>Características:</strong></p>
                            <ul>
                                <li>Facturación electrónica</li>
                                <li>Reporte de entregas mensual</li>
                                <li>Atención prioritaria</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tarifas y Zonas -->
        <section class="pricing" id="pricing">
            <div class="container">
                <h2>Zonas de Cobertura</h2>
                    <div class="map-container">
                        <img src="public/img/Mapa.jpg" alt="Mapa de cobertura de EcoBikeMess en Bogotá" style="width: 100%; max-width: 800px; height: auto; margin: 20px auto; display: block; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    </div>
                    <div class="map-container">
                        <p>Con la finalidad de expandir nuestra cobertura de zonas de entrega, hemos diseñado nuestro mapa de cobertura dentro de la ciudad de Bogotá y el municipio de Soacha en Cundinamarca</p>
                        <p>La zona en verde claro cuenta con distribución para la tarifa fija de $8.000 (Tener en cuenta los términos y condiciones para esta tarifa) 
                        La zona en verde oscuro tiene una tarifa fija de $12.000, por temas de difícil acceso, seguridad, o ser sitios de alta montaña (Tener en cuenta los términos y condiciones para esta tarifa) </p>
                        <p>En los sectores que no están demarcados en el mapa no tenemos cobertura al ser zonas catalogadas como zonas rojas en temas de seguridad (Como son Altos de Cazuca, Ciudadela Sucre, Altos de la Florida, etc.)</p>
                    </div>
            </div>
        </section>

        <!-- Contacto -->
        <section class="contact" id="contact">
            <div class="container">
                <h2>Contáctanos</h2>
                <div class="contact-grid">
                    <div class="contact-item">
                        <div class="contact-icon">📞</div>
                        <h3>Nuestro WhatsApp</h3>
                        
                        <p>+57 31235180619</p>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">✉️</div>
                        <h3>Email</h3>
                        <p>Eco.bikemess@gmail.com</p>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">🕒</div>
                        <h3>Horario</h3>
                        <p>De Lunes a Sábado</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2020 EcoBikeMess. Todos los derechos reservados.</p>
            <p>Mensajería ecológica para un futuro sostenible 🌱</p>
        </div>
    </footer>

    <!-- Botón Flotante de WhatsApp -->
    <a href="https://wa.link/49g8jg" class="whatsapp-container" target="_blank">
        <div class="whatsapp-btn">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
        </div>
    </a>

    <script src="public/js/script.js"></script>
</body>
</html>
