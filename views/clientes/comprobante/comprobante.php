<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Entrega</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 25%, #81C784 50%, #A5D6A7 75%, #C8E6C9 100%);
            animation: gradientShift 8s ease-in-out infinite;
            padding: 40px 20px;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Elementos decorativos flotantes */
        body::before {
            content: '';
            position: absolute;
            top: 10%;
            left: 5%;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: 15%;
            right: 8%;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .main-container {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 30px;
            position: relative;
            z-index: 1;
        }

        /* Header Section */
        .comprobante {
            text-align: center;
            margin-bottom: 20px;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .comprobante h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            letter-spacing: -1px;
        }

        .comprobante p {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Container principal del comprobante */
        .fondocomprobante {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: slideIn 0.8s ease-out 0.2s both;
        }

        .degrade-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #2E7D32, #4CAF50, #81C784, #4CAF50, #2E7D32);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .comprobante-content {
            padding: 50px 40px 30px;
        }

        /* Header del comprobante */
        .comprobanteentrega {
            margin-bottom: 40px;
        }

        .comprobanteentrega h2 {
            text-align: center;
            color: #2E7D32;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 35px;
            letter-spacing: -0.5px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .comprobanteentrega h2::before {
            content: '\f15b';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #4CAF50;
            font-size: 24px;
        }

        /* Grid de información */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .info-card {
            background: linear-gradient(135deg, #F1F8E9 0%, #E8F5E8 100%);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #4CAF50;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(76, 175, 80, 0.15);
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 40px;
            height: 40px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 0 20px 0 20px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            color: #2E7D32;
            font-weight: 700;
            line-height: 1.4;
        }

        /* Sección de estado */
        .status-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border: 2px solid #E8F5E8;
        }

        .status-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .status-badge {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .delivery-date {
            text-align: center;
            color: #2E7D32;
            font-size: 18px;
            font-weight: 600;
        }

        /* Botón de descarga */
        .download-section {
            text-align: center;
            padding: 20px 0;
        }

        .login-button {
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
            padding: 18px 40px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 250px;
            justify-content: center;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 15px 30px rgba(76, 175, 80, 0.4),
                0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .login-button:active {
            transform: translateY(-1px);
        }

        /* Botón secundario para regresar */
        .back-button {
            background: transparent;
            color: #4CAF50;
            border: 2px solid #4CAF50;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 16px 30px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: #4CAF50;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 20px 15px;
            }

            .main-container {
                gap: 20px;
            }

            .comprobante-content {
                padding: 40px 30px 25px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .info-card {
                padding: 20px;
            }

            .status-section {
                padding: 25px 20px;
            }

            .login-button {
                width: 100%;
                padding: 16px 20px;
                font-size: 15px;
            }

            .back-button {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .comprobante h1 {
                font-size: 2rem;
            }

            .comprobante-content {
                padding: 30px 20px 20px;
            }

            .comprobanteentrega h2 {
                font-size: 24px;
                margin-bottom: 25px;
            }

            .info-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .info-icon {
                align-self: center;
            }
        }

        /* Animaciones de entrada */
        .info-card {
            opacity: 0;
            animation: slideInUp 0.6s ease forwards;
        }

        .info-card:nth-child(1) { animation-delay: 0.1s; }
        .info-card:nth-child(2) { animation-delay: 0.2s; }
        .info-card:nth-child(3) { animation-delay: 0.3s; }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Efecto de impresión */
        @media print {
            body {
                background: white !important;
                padding: 20px !important;
            }

            .fondocomprobante {
                background: white !important;
                box-shadow: none !important;
                border: 2px solid #2E7D32 !important;
            }

            .login-button, .back-button {
                display: none !important;
            }

            .degrade-container {
                background: #2E7D32 !important;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="comprobante">
            <h1>Comprobantes de Entrega</h1>
            <p>Aquí puedes encontrar los comprobantes de entrega de tus pedidos con información detallada y verificada.</p>
        </div>

        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Volver al Panel</span>
        </a>

        <div class="fondocomprobante">
            <div class="degrade-container"></div>
            <div class="comprobante-content">
                <div class="comprobanteentrega">
                    <h2>Comprobante de Entrega</h2>
                </div>

                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Cliente</div>
                                <div class="info-value">Juan Pérez</div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Teléfono</div>
                                <div class="info-value">+57 312 318 06 19</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Dirección de Entrega</div>
                                <div class="info-value">Calle 61 #17-15, Bogotá, Colombia</div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Valor Cobrado</div>
                                <div class="info-value">$10,000 COP</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-sticky-note"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Observaciones</div>
                                <div class="info-value">Entregar antes de las 5:00 p.m.</div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-sticky-note"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Foto</div>
                                <div class="info-value"><img src="../../../public/img/fondo.jpg" width="200" height="100" alt=""></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="status-section">
                    <div class="status-header">
                        <div class="status-badge">
                            <i class="fas fa-check-circle"></i>
                            <span>ENTREGADO EXITOSAMENTE</span>
                        </div>
                    </div>
                    <div class="delivery-date">
                        Fecha de Entrega: 25 de Abril de 2025
                    </div>
                </div>

                <div class="download-section">
                    <button type="button" class="login-button" onclick="window.print()">
                        <i class="fas fa-download"></i>
                        <span>Descargar Comprobante</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>