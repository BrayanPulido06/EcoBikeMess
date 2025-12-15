<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapidoEnvíos - El Futuro de la Mensajería en Bogotá</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            overflow-x: hidden;
            background: var(--light);
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: var(--dark);
            z-index: 1000;
            padding: 2rem;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .sidebar-brand {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }

        .sidebar-nav a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(8px);
        }

        /* Top Navigation Bar */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .menu-toggle:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .topbar-logo {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .brand-text {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 102, 234, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding-left: 0;
            transition: padding-left 0.3s ease;
        }

        /* Hero Section - Dashboard Style */
        .hero-dashboard {
            padding: 4rem 2rem;
            background: var(--gradient-primary);
            position: relative;
            overflow: hidden;
            color: white;
        }

        .hero-dashboard::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .hero-visual {
            position: relative;
        }

        .floating-cards {
            position: relative;
            height: 400px;
        }

        .floating-card {
            position: absolute;
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            color: var(--dark);
            animation: float 6s ease-in-out infinite;
        }

        .floating-card:nth-child(1) {
            top: 20px;
            left: 50px;
            animation-delay: 0s;
        }

        .floating-card:nth-child(2) {
            top: 120px;
            right: 30px;
            animation-delay: 2s;
        }

        .floating-card:nth-child(3) {
            bottom: 80px;
            left: 20px;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        /* Services Grid */
        .services-section {
            padding: 6rem 2rem;
            background: white;
        }

        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 4rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--gray);
        }

        .services-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .service-card {
            background: white;
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.12);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            background: var(--gradient-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .service-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .service-card p {
            color: var(--gray);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .service-price {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--success);
            margin-bottom: 1.5rem;
        }

        .service-features {
            list-style: none;
            text-align: left;
            margin-bottom: 2rem;
        }

        .service-features li {
            padding: 0.5rem 0;
            position: relative;
            padding-left: 1.5rem;
            color: var(--gray);
        }

        .service-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success);
            font-weight: bold;
        }

        /* Interactive Coverage Map */
        .coverage-section {
            padding: 6rem 2rem;
            background: var(--light);
        }

        .coverage-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .coverage-info {
            padding: 2rem;
        }

        .coverage-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }

        .coverage-description {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 3rem;
            line-height: 1.7;
        }

        .zone-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .zone-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .zone-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .zone-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .zone-time {
            color: var(--success);
            font-weight: 700;
            font-size: 0.9rem;
        }

        .map-container {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        #interactive-map {
            width: 100%;
            height: 500px;
        }

        /* Tools Dashboard */
        .tools-section {
            padding: 6rem 2rem;
            background: white;
        }

        .tools-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .tool-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s ease;
        }

        .tool-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            transform: scale(0);
            transition: transform 0.6s ease;
        }

        .tool-card:hover::before {
            transform: scale(1);
        }

        .tool-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(102, 126, 234, 0.3);
        }

        .tool-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .tool-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .tool-card p {
            opacity: 0.9;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .tool-button {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .tool-button:hover {
            background: white;
            color: var(--primary);
        }

        /* Process Timeline */
        .process-section {
            padding: 6rem 2rem;
            background: var(--light);
        }

        .timeline {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            width: 2px;
            height: 100%;
            background: var(--gradient-primary);
            transform: translateX(-50%);
        }

        .timeline-item {
            display: flex;
            margin-bottom: 4rem;
            position: relative;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-content {
            flex: 1;
            max-width: 45%;
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-right: 5%;
        }

        .timeline-item:nth-child(even) .timeline-content {
            margin-left: 5%;
        }

        .timeline-icon {
            position: absolute;
            left: 50%;
            top: 2rem;
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateX(-50%);
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 10;
        }

        .timeline-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .timeline-description {
            color: var(--gray);
            line-height: 1.6;
        }

        /* News & Blog Cards */
        .news-section {
            padding: 6rem 2rem;
            background: white;
        }

        .news-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
        }

        .news-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            cursor: pointer;
        }

        .news-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .news-image {
            height: 200px;
            background: var(--gradient-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            position: relative;
            overflow: hidden;
        }

        .news-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0,0,0,0.1) 0%, transparent 50%);
        }

        .news-content {
            padding: 2rem;
        }

        .news-date {
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .news-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .news-excerpt {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .read-more {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: gap 0.3s ease;
        }

        .read-more:hover {
            gap: 1rem;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 4rem 2rem 2rem;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr repeat(3, 1fr);
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-brand {
            padding-right: 2rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .footer-logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .footer-brand-name {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .footer-description {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social-link:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .footer-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.9rem;
        }

        .footer-links a:hover {
            color: white;
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .coverage-container {
                grid-template-columns: 1fr;
            }
            
            .timeline::before {
                left: 30px;
            }
            
            .timeline-item {
                flex-direction: row;
            }
            
            .timeline-content {
                max-width: none;
                margin-left: 80px;
            }
            
            .timeline-icon {
                left: 30px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
            }
            
            .hero-text h1 {
                font-size: 2.5rem;
            }
            
            .hero-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .services-grid,
            .tools-grid,
            .news-grid {
                grid-template-columns: 1fr;
            }
            
            .zone-cards {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .loading.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loader {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Overlay for sidebar */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="loader"></div>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🚀</div>
            <div class="sidebar-brand">RapidoEnvíos</div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="#inicio" class="nav-link active">🏠 Inicio</a></li>
            <li><a href="#servicios" class="nav-link">⚡ Servicios</a></li>
            <li><a href="#cobertura" class="nav-link">🗺️ Cobertura</a></li>
            <li><a href="#proceso" class="nav-link">🔄 Proceso</a></li>
            <li><a href="#herramientas" class="nav-link">🛠️ Herramientas</a></li>
            <li><a href="#noticias" class="nav-link">📰 Noticias</a></li>
            <li><a href="#contacto" class="nav-link">📞 Contacto</a></li>
        </ul>
    </nav>

    <!-- Top Navigation Bar -->
    <header class="topbar">
        <button class="menu-toggle" id="menuToggle">☰</button>
        <div class="topbar-brand">
            <div class="topbar-logo">🚀</div>
            <div class="brand-text">RapidoEnvíos</div>
        </div>
        <div class="topbar-actions">
            <button class="action-btn btn-outline">Rastrear</button>
            <button class="action-btn btn-primary">Enviar Ahora</button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Dashboard Section -->
        <section class="hero-dashboard" id="inicio">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>El Futuro de la Mensajería en Bogotá</h1>
                    <p class="hero-subtitle">Tecnología avanzada, entregas instantáneas y seguimiento en tiempo real. Conectamos tu negocio con el mundo digital de las entregas.</p>
                    
                    <div class="hero-stats">
                        <div class="stat-card">
                            <span class="stat-number">99.2%</span>
                            <span class="stat-label">Entregas Exitosas</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">2.5K+</span>
                            <span class="stat-label">Entregas Diarias</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">150+</span>
                            <span class="stat-label">Mensajeros Activos</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button class="action-btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">🚀 Comenzar Ahora</button>
                        <button class="action-btn btn-outline" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3); color: white;">📱 Descargar App</button>
                    </div>
                </div>
                
                <div class="hero-visual">
                    <div class="floating-cards">
                        <div class="floating-card">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 50px; height: 50px; background: var(--gradient-success); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white;">📦</div>
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Paquete en Tránsito</div>
                                    <div style="color: var(--gray); font-size: 0.9rem;">Chapinero → Zona Rosa</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="floating-card">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 50px; height: 50px; background: var(--gradient-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white;">⚡</div>
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Entrega Express</div>
                                    <div style="color: var(--success); font-size: 0.9rem; font-weight: 600;">Llegada en 45 min</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="floating-card">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 50px; height: 50px; background: var(--gradient-secondary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white;">✓</div>
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Entregado</div>
                                    <div style="color: var(--gray); font-size: 0.9rem;">Cliente satisfecho</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services-section" id="servicios">
            <div class="section-header">
                <h2 class="section-title">Servicios Premium</h2>
                <p class="section-subtitle">Soluciones de mensajería adaptadas a cada necesidad de tu negocio</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">🚚</div>
                    <h3>Estándar Pro</h3>
                    <p>Entregas confiables en 4-6 horas con seguimiento completo y notificaciones automáticas.</p>
                    <div class="service-price">Desde $8.500</div>
                    <ul class="service-features">
                        <li>Seguimiento en tiempo real</li>
                        <li>Notificaciones automáticas</li>
                        <li>Seguro incluido hasta $100K</li>
                        <li>Soporte 24/7</li>
                    </ul>
                    <button class="tool-button">Solicitar Servicio</button>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">⚡</div>
                    <h3>Express Ultra</h3>
                    <p>Velocidad máxima con entregas garantizadas en 2-3 horas para tus envíos más urgentes.</p>
                    <div class="service-price">Desde $15.000</div>
                    <ul class="service-features">
                        <li>Prioridad máxima</li>
                        <li>Mensajero dedicado</li>
                        <li>Confirmación fotográfica</li>
                        <li>Garantía de tiempo</li>
                    </ul>
                    <button class="tool-button">Solicitar Servicio</button>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">🏃‍♂️</div>
                    <h3>Mismo Día VIP</h3>
                    <p>El servicio más exclusivo con entrega garantizada el mismo día y atención personalizada.</p>
                    <div class="service-price">Desde $25.000</div>
                    <ul class="service-features">
                        <li>Entrega mismo día garantizada</li>
                        <li>Asesor personalizado</li>
                        <li>Manejo especial</li>
                        <li>Reporte detallado</li>
                    </ul>
                    <button class="tool-button">Solicitar Servicio</button>
                </div>
            </div>
        </section>

        <!-- Interactive Coverage Map -->
        <section class="coverage-section" id="cobertura">
            <div class="coverage-container">
                <div class="coverage-info">
                    <h2 class="coverage-title">Cobertura Total en Bogotá</h2>
                    <p class="coverage-description">Llegamos a cada rincón de la capital con nuestra red inteligente de mensajeros y tecnología de ruteo avanzada.</p>
                    
                    <div class="zone-cards">
                        <div class="zone-card">
                            <div class="zone-name">Centro/Zona Rosa</div>
                            <div class="zone-time">2-3 horas</div>
                        </div>
                        <div class="zone-card">
                            <div class="zone-name">Chapinero/Usaquén</div>
                            <div class="zone-time">3-4 horas</div>
                        </div>
                        <div class="zone-card">
                            <div class="zone-name">Kennedy/Fontibón</div>
                            <div class="zone-time">4-5 horas</div>
                        </div>
                        <div class="zone-card">
                            <div class="zone-name">Suba/Engativá</div>
                            <div class="zone-time">4-6 horas</div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                        <div style="text-align: center; padding: 1.5rem; background: white; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05);">
                            <div style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">20/20</div>
                            <div style="color: var(--gray); font-size: 0.9rem;">Localidades</div>
                        </div>
                        <div style="text-align: center; padding: 1.5rem; background: white; border-radius: 16px; border: 1px solid rgba(0,0,0,0.05);">
                            <div style="font-size: 2rem; font-weight: 800; color: var(--success); margin-bottom: 0.5rem;">300+</div>
                            <div style="color: var(--gray); font-size: 0.9rem;">Barrios Activos</div>
                        </div>
                    </div>
                </div>
                
                <div class="map-container">
                    <div id="interactive-map"></div>
                </div>
            </div>
        </section>

        <!-- Process Timeline -->
        <section class="process-section" id="proceso">
            <div class="section-header">
                <h2 class="section-title">Proceso Inteligente</h2>
                <p class="section-subtitle">Tecnología y eficiencia en cada paso de tu envío</p>
            </div>
            
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h3 class="timeline-title">Solicitud Inteligente</h3>
                        <p class="timeline-description">Plataforma web y app móvil con IA que optimiza automáticamente tu envío según distancia, tráfico y prioridad.</p>
                    </div>
                    <div class="timeline-icon">1</div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h3 class="timeline-title">Asignación Automática</h3>
                        <p class="timeline-description">Nuestro algoritmo selecciona al mejor mensajero basado en ubicación, disponibilidad y especialización.</p>
                    </div>
                    <div class="timeline-icon">2</div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h3 class="timeline-title">Recolección Express</h3>
                        <p class="timeline-description">Mensajero llega puntualmente con confirmación en tiempo real y código QR para máxima seguridad.</p>
                    </div>
                    <div class="timeline-icon">3</div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h3 class="timeline-title">Entrega Verificada</h3>
                        <p class="timeline-description">Entrega con confirmación biométrica, foto de evidencia y notificación automática al remitente.</p>
                    </div>
                    <div class="timeline-icon">4</div>
                </div>
            </div>
        </section>

        <!-- Tools Dashboard -->
        <section class="tools-section" id="herramientas">
            <div class="section-header">
                <h2 class="section-title">Herramientas Digitales</h2>
                <p class="section-subtitle">Potencia tu negocio con nuestras herramientas gratuitas</p>
            </div>
            
            <div class="tools-grid">
                <div class="tool-card">
                    <div class="tool-icon">💰</div>
                    <h3>Calculadora IA</h3>
                    <p>Cotización instantánea con inteligencia artificial que considera tráfico, distancia y urgencia en tiempo real</p>
                    <button class="tool-button">Calcular Ahora</button>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">📦</div>
                    <h3>Rastreo Avanzado</h3>
                    <p>Seguimiento en tiempo real con mapa interactivo, ETA dinámico y notificaciones proactivas</p>
                    <button class="tool-button">Rastrear Envío</button>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">🗺️</div>
                    <h3>Mapa de Calor</h3>
                    <p>Visualiza tiempos de entrega en tiempo real por zonas con predicciones basadas en tráfico</p>
                    <button class="tool-button">Ver Mapa</button>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">📊</div>
                    <h3>Analytics Pro</h3>
                    <p>Dashboard completo con métricas de rendimiento, costos optimizados y reportes detallados</p>
                    <button class="tool-button">Ver Dashboard</button>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">🤖</div>
                    <h3>API Inteligente</h3>
                    <p>Integra nuestros servicios directamente en tu e-commerce con nuestra API REST avanzada</p>
                    <button class="tool-button">Documentación</button>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">📱</div>
                    <h3>App Empresarial</h3>
                    <p>Aplicación móvil exclusiva para empresas con panel de control y gestión de flotas</p>
                    <button class="tool-button">Descargar App</button>
                </div>
            </div>
        </section>

        <!-- News & Blog -->
        <section class="news-section" id="noticias">
            <div class="section-header">
                <h2 class="section-title">Últimas Noticias</h2>
                <p class="section-subtitle">Mantente al día con las últimas innovaciones y noticias del sector</p>
            </div>
            
            <div class="news-grid">
                <article class="news-card">
                    <div class="news-image">🤖</div>
                    <div class="news-content">
                        <div class="news-date">25 de Agosto, 2025</div>
                        <h3 class="news-title">IA Revoluciona las Entregas en Bogotá</h3>
                        <p class="news-excerpt">Implementamos algoritmos de machine learning que han reducido los tiempos de entrega en un 35% y optimizado rutas en tiempo real...</p>
                        <a href="#" class="read-more">Leer más →</a>
                    </div>
                </article>
                
                <article class="news-card">
                    <div class="news-image">🚀</div>
                    <div class="news-content">
                        <div class="news-date">20 de Agosto, 2025</div>
                        <h3 class="news-title">Nueva Flota de Vehículos Eléctricos</h3>
                        <p class="news-excerpt">Inauguramos nuestra flota 100% eléctrica contribuyendo a un Bogotá más sostenible y reduciendo emisiones en un 80%...</p>
                        <a href="#" class="read-more">Leer más →</a>
                    </div>
                </article>
                
                <article class="news-card">
                    <div class="news-image">💼</div>
                    <div class="news-content">
                        <div class="news-date">18 de Agosto, 2025</div>
                        <h3 class="news-title">Partnership con E-commerce Líder</h3>
                        <p class="news-excerpt">Anunciamos alianza estratégica que permitirá entregas en menos de 2 horas para compras online en toda la ciudad...</p>
                        <a href="#" class="read-more">Leer más →</a>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer" id="contacto">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="footer-logo-icon">🚀</div>
                    <div class="footer-brand-name">RapidoEnvíos</div>
                </div>
                <p class="footer-description">Revolucionamos la mensajería en Bogotá con tecnología avanzada, entregas ultrarrápidas y el mejor servicio al cliente del mercado.</p>
                <div class="social-links">
                    <a href="#" class="social-link">📘</a>
                    <a href="#" class="social-link">📷</a>
                    <a href="#" class="social-link">🐦</a>
                    <a href="#" class="social-link">💼</a>
                    <a href="#" class="social-link">📺</a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Servicios</h4>
                <ul class="footer-links">
                    <li><a href="#">Envío Estándar Pro</a></li>
                    <li><a href="#">Express Ultra</a></li>
                    <li><a href="#">Mismo Día VIP</a></li>
                    <li><a href="#">Envío Programado</a></li>
                    <li><a href="#">API Empresarial</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Plataforma</h4>
                <ul class="footer-links">
                    <li><a href="#">Dashboard</a></li>
                    <li><a href="#">App Móvil</a></li>
                    <li><a href="#">API Documentation</a></li>
                    <li><a href="#">Integraciones</a></li>
                    <li><a href="#">Analytics</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Contacto</h4>
                <ul class="footer-links">
                    <li>📞 (601) 123-4567</li>
                    <li>📱 300 123 4567</li>
                    <li>✉️ hola@rapidoenvios.com</li>
                    <li>📍 Calle 26 #68-90, Bogotá</li>
                    <li>🕒 24/7 Disponible</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 RapidoEnvíos. Todos los derechos reservados. | Política de Privacidad | Términos de Servicio | Cookies</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.js"></script>
    
    <script>
        // Loading Screen
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loading').classList.add('hidden');
            }, 1000);
        });

        // Sidebar Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Smooth Scrolling Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                
                if (targetSection) {
                    targetSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update active nav link
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Close sidebar on mobile
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });

        // Interactive Map
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize enhanced map
            const map = L.map('interactive-map', {
                zoomControl: true,
                scrollWheelZoom: true,
                doubleClickZoom: false
            }).setView([4.6097, -74.0817], 11);

            // Custom dark theme tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18,
                opacity: 0.7
            }).addTo(map);

            // Enhanced headquarters icon
            const headquartersIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 18px; border: 4px solid white; box-shadow: 0 4px 15px rgba(102,126,234,0.4); animation: pulse 2s infinite;">🏢</div>',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });

            // Add headquarters with enhanced popup
            const headquarters = L.marker([4.6350, -74.1139], {icon: headquartersIcon}).addTo(map);
            headquarters.bindPopup(`
                <div style='text-align: center; font-family: Inter, sans-serif; padding: 10px;'>
                    <h3 style='color: var(--primary); margin-bottom: 10px;'>🚀 RapidoEnvíos HQ</h3>
                    <p style='margin-bottom: 10px;'>📍 Calle 26 #68-90, Bogotá</p>
                    <p style='margin-bottom: 10px;'>📞 (601) 123-4567</p>
                    <p style='color: var(--success); font-weight: 600;'>✅ Centro de Operaciones 24/7</p>
                </div>
            `);

            // Enhanced delivery zones with colors
            const deliveryZones = [
                {coords: [4.6482, -74.0637], name: "Centro Histórico", time: "2-3h", color: "#10b981"},
                {coords: [4.6736, -74.0465], name: "Chapinero", time: "3-4h", color: "#06b6d4"},
                {coords: [4.7110, -74.0721], name: "Usaquén", time: "3-4h", color: "#8b5cf6"},
                {coords: [4.5981, -74.1421], name: "Kennedy", time: "4-5h", color: "#f59e0b"},
                {coords: [4.6860, -74.1311], name: "Suba", time: "4-6h", color: "#ef4444"},
                {coords: [4.5287, -74.1624], name: "Bosa", time: "5-7h", color: "#6366f1"}
            ];

            // Add delivery zone markers
            deliveryZones.forEach(zone => {
                const zoneIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background: ${zone.color}; color: white; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.2);">🚚</div>`,
                    iconSize: [35, 35],
                    iconAnchor: [17, 17]
                });

                L.marker(zone.coords, {icon: zoneIcon}).addTo(map)
                    .bindPopup(`
                        <div style='text-align: center; font-family: Inter, sans-serif;'>
                            <h4 style='color: ${zone.color}; margin-bottom: 8px;'>📍 ${zone.name}</h4>
                            <p style='margin-bottom: 5px;'>⏱️ Tiempo promedio: <strong>${zone.time}</strong></p>
                            <p style='color: var(--success); font-weight: 600;'>✅ Zona Activa</p>
                        </div>
                    `);
            });

            // Add coverage area with gradient
            const coverageCircle = L.circle([4.6097, -74.0817], {
                color: '#6366f1',
                fillColor: '#6366f1',
                fillOpacity: 0.08,
                radius: 30000,
                weight: 2,
                dashArray: '10, 5'
            }).addTo(map);

            // Enhanced info control
            const info = L.control({position: 'topright'});
            info.onAdd = function (map) {
                const div = L.DomUtil.create('div', 'info');
                div.innerHTML = `
                    <div style="background: white; padding: 15px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); font-family: Inter, sans-serif; min-width: 200px;">
                        <h4 style="color: var(--primary); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                            🗺️ Mapa Interactivo
                        </h4>
                        <p style="font-size: 12px; color: var(--gray); margin-bottom: 8px;">Haz clic en los marcadores</p>
                        <div style="display: flex; gap: 10px; font-size: 11px;">
                            <span style="color: var(--success);">🟢 Activo</span>
                            <span style="color: var(--warning);">🟡 Ocupado</span>
                        </div>
                    </div>
                `;
                return div;
            };
            info.addTo(map);
        });

        // Button interactions
        document.querySelectorAll('.tool-button, .action-btn').forEach(button => {
            button.addEventListener('click', function() {
                const buttonText = this.textContent;
                
                // Create success notification
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 100px;
                    right: 20px;
                    background: var(--success);
                    color: white;
                    padding: 1rem 2rem;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
                    z-index: 10000;
                    font-family: Inter, sans-serif;
                    font-weight: 600;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                `;
                notification.textContent = `✅ ${buttonText} - ¡Redirigiendo!`;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                }, 100);
                
                setTimeout(() => {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            });
        });

        // Advanced scroll animations
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

        // Apply animations to sections
        document.querySelectorAll('section, .service-card, .tool-card, .news-card, .timeline-item').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            observer.observe(element);
        });

        // Dynamic topbar on scroll
        let lastScrollY = 0;
        window.addEventListener('scroll', () => {
            const topbar = document.querySelector('.topbar');
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 50) {
                topbar.style.background = 'rgba(255, 255, 255, 0.95)';
                topbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.05)';
            }
            
            lastScrollY = currentScrollY;
        });

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.floating-card');
            
            parallaxElements.forEach((element, index) => {
                const speed = 0.5 + (index * 0.1);
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });

        // Add dynamic typing effect to hero title
        function typeWriter(element, text, speed = 100) {
            let i = 0;
            element.innerHTML = '';
            
            function type() {
                if (i < text.length) {
                    element.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            type();
        }

        // Initialize typing effect after load
        setTimeout(() => {
            const heroTitle = document.querySelector('.hero-text h1');
            if (heroTitle) {
                const originalText = heroTitle.textContent;
                typeWriter(heroTitle, originalText, 80);
            }
        }, 1500);

        // Add ripple effect to buttons
        document.querySelectorAll('button, .tool-card').forEach(element => {
            element.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .floating-card {
                transition: transform 0.1s ease-out;
            }
            
            .service-card:hover .service-icon,
            .tool-card:hover .tool-icon {
                transform: scale(1.1) rotate(5deg);
                transition: transform 0.3s ease;
            }
            
            .timeline-item {
                animation-delay: var(--delay, 0s);
            }
        `;
        document.head.appendChild(style);

        // Add staggered animation delays to timeline items
        document.querySelectorAll('.timeline-item').forEach((item, index) => {
            item.style.setProperty('--delay', `${index * 0.2}s`);
        });

        // Enhanced service card interactions
        document.querySelectorAll('.service-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.borderColor = 'var(--primary)';
                this.style.borderWidth = '2px';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.borderColor = 'rgba(0, 0, 0, 0.05)';
                this.style.borderWidth = '1px';
            });
        });

        // Auto-updating stats animation
        function animateCounter(element, target, duration = 2000) {
            let start = 0;
            const increment = target / (duration / 16);
            
            function updateCounter() {
                start += increment;
                if (start < target) {
                    element.textContent = Math.floor(start).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = target.toLocaleString();
                }
            }
            updateCounter();
        }

        // Initialize counter animations when stats come into view
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                    const number = entry.target.querySelector('.stat-number');
                    const text = number.textContent.replace(/[^0-9.]/g, '');
                    const value = parseFloat(text);
                    
                    if (!isNaN(value)) {
                        animateCounter(number, value);
                        entry.target.classList.add('animated');
                    }
                }
            });
        });

        document.querySelectorAll('.stat-card').forEach(card => {
            statsObserver.observe(card);
        });

        // Add keyboard navigation for accessibility
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });

        // Add mobile touch gestures for sidebar
        let startX = 0;
        let currentX = 0;
        
        document.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        document.addEventListener('touchmove', function(e) {
            if (!startX) return;
            currentX = e.touches[0].clientX;
        });
        
        document.addEventListener('touchend', function(e) {
            if (!startX || !currentX) return;
            
            const diffX = startX - currentX;
            
            if (Math.abs(diffX) > 50) { // Minimum swipe distance
                if (diffX > 0 && startX < 50) { // Swipe left from left edge
                    sidebar.classList.add('active');
                    overlay.classList.add('active');
                } else if (diffX < 0 && sidebar.classList.contains('active')) { // Swipe right
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            }
            
            startX = 0;
            currentX = 0;
        });

        // Performance optimization: Throttle scroll events
        function throttle(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Apply throttling to scroll events
        const throttledScroll = throttle(() => {
            // Scroll-based animations here
        }, 16); // ~60fps

        window.addEventListener('scroll', throttledScroll);

        console.log('🚀 RapidoEnvíos - Sistema inicializado correctamente');
        console.log('📊 Dashboard moderno cargado');
        console.log('🗺️ Mapa interactivo activo');
        console.log('⚡ Animaciones y efectos habilitados');
    </script>
</body>
</html>'rgba(255, 255, 255, 0.98)';
                topbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
            } else {
                topbar.style.background =