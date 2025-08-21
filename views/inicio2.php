<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoBikeMess</title>
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
            overflow-x: hidden;
        }

        /* Hero Section con Video */
        .hero-section {
            position: relative;
            height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }


        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                rgba(34, 139, 34, 0.8) 0%,
                rgba(0, 100, 0, 0.6) 50%,
                rgba(46, 125, 50, 0.7) 100%
            );
            z-index: -1;
        }

        /* Navegación */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(34, 139, 34, 0.95);
            backdrop-filter: blur(20px);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .nav-menu a:hover::before {
            left: 100%;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Hero Content */
        .hero-content {
            text-align: center;
            color: white;
            max-width: 800px;
            padding: 2rem;
            animation: fadeInUp 1s ease-out;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, #ffffff, #90EE90, #ffffff);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientShift 3s ease-in-out infinite;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-button {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #4CAF50, #8BC34A);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.5);
            background: linear-gradient(45deg, #45a049, #7CB342);
        }

        /* Secciones principales */
        .section {
            padding: 5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            background: linear-gradient(45deg, #2E7D32, #4CAF50);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(45deg, #4CAF50, #8BC34A);
            border-radius: 2px;
        }

        /* Cards Container */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(240, 255, 240, 0.8));
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(76, 175, 80, 0.2);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #4CAF50, #8BC34A, #4CAF50);
            background-size: 200% 100%;
            animation: shimmer 2s linear infinite;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(76, 175, 80, 0.2);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #4CAF50, #8BC34A);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2E7D32;
        }

        .card p {
            color: #555;
            line-height: 1.7;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #1B5E20, #2E7D32);
            color: white;
            padding: 3rem 2rem 1rem;
            margin-top: 5rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            align-items: center;
        }

        .footer-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
        }

        .footer-links a:hover {
            color: #8BC34A;
            transform: translateX(5px);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            opacity: 0.8;
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes gradientShift {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .nav-menu {
                flex-direction: column;
                gap: 1rem;
            }

            .navbar {
                padding: 1rem;
            }

            .cards-container {
                grid-template-columns: 1fr;
            }
        }

        /* Scroll animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <!-- Navegación -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <img src="../public/img/logoblanco.png" alt="EcoBikeMess" class="logo">
            <ul class="nav-menu">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#servicios">Servicios</a></li>
                <li><a href="#nosotros">Nosotros</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="inicio">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">EcoBikeMess</h1>
            <p class="hero-subtitle">
                Elige cómo distribuir tu paquetería por la ciudad sin contaminar. 
                Nosotros lo hacemos posible con nuestro servicio de mensajería ecológica 🍃
            </p>
            <a href="#servicios" class="cta-button">Conoce Nuestros Servicios</a>
        </div>
    </section>

    <!-- Sección Nosotros -->
    <section class="section fade-in" id="nosotros">
        <h2 class="section-title">Quiénes Somos</h2>
        <div class="cards-container">
            <div class="card">
                <div class="card-icon">🎯</div>
                <h3>Nuestra Visión</h3>
                <p>Ser líderes en la transformación de la logística urbana mediante soluciones innovadoras y sostenibles en bicicleta. Aspiramos a redefinir el futuro de los envíos urbanos, combinando velocidad, eficiencia y responsabilidad ambiental para crear ciudades más limpias, donde cada envío sea una contribución activa al cuidado del medio ambiente.</p>
            </div>
            <div class="card">
                <div class="card-icon">🚀</div>
                <h3>Nuestra Misión</h3>
                <p>Fomentar un cambio positivo en la movilidad urbana, ofreciendo un servicio de mensajería en bicicleta que resuelve de forma ecológica la entrega de tus paquetes y mercancías en la ciudad.</p>
            </div>
        </div>
    </section>

    <!-- Sección Servicios -->
    <section class="section fade-in" id="servicios">
        <h2 class="section-title">Nuestros Servicios</h2>
        <div class="cards-container">
            <div class="card">
                <div class="card-icon">📦</div>
                <h3>Servicios Completos</h3>
                <p>Ofrecemos una amplia gama de servicios de entrega en bicicleta, desde entregas exprés hasta servicios de mensajería personalizados, todo ello con un enfoque en la sostenibilidad y la reducción de emisiones.</p>
            </div>
            <div class="card">
                <div class="card-icon">🌱</div>
                <h3>Entrega Ecológica</h3>
                <p>En Eco BikeMess trabajamos con una conciencia ecológica: cada entrega en bicicleta es una decisión activa para reducir la huella de carbono urbana. Desde la optimización de rutas hasta el uso de materiales sostenibles, nuestro servicio no solo transporta paquetes, sino un modelo de logística responsable.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contacto">
        <div class="footer-content">
            <div>
                <img src="https://via.placeholder.com/120x120/4CAF50/FFFFFF?text=EB" alt="EcoBikeMess" class="footer-logo">
                <p>Mensajería ecológica para un futuro sostenible</p>
            </div>
            <div>
                <ul class="footer-links">
                    <li><a href="https://wa.link/49g8jg">📱 +57 312 318 06 19</a></li>
                    <li><a href="mailto:Eco.BikeMess@gmail.com">✉️ Eco.BikeMess@gmail.com</a></li>
                    <li><a href="https://www.google.com/maps/place/Eco+BikeMess/@4.6481855,-74.0684432,19z/data=!4m16!1m7!3m6!1s0x8e3f9b65787e0213:0xfbf0e7c6f9dea484!2sEco+BikeMess!8m2!3d4.6484168!4d-74.0681079!16s%2Fg%2F11y79hdvrr!3m7!1s0x8e3f9b65787e0213:0xfbf0e7c6f9dea484!8m2!3d4.6484168!4d-74.0681079!9m1!1b1!16s%2Fg%2F11y79hdvrr?entry=ttu&g_ep=EgoyMDI1MDQwOS4wIKXMDSoASAFQAw%3D%3D">📍 Calle 61 #17-15, Bogotá, Colombia</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 EcoBikeMess. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling
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

        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const heroContent = document.querySelector('.hero-content');
            if (heroContent) {
                heroContent.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    </script>
</body>

</html>