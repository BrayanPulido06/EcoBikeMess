<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow-x: hidden;
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 25%, #81C784 50%, #A5D6A7 75%, #C8E6C9 100%);
            animation: gradientShift 8s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Elementos decorativos flotantes */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .floating-shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 20%;
            right: 8%;
            animation-delay: 2s;
        }

        .floating-shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 15%;
            animation-delay: 4s;
        }

        .floating-shape:nth-child(4) {
            width: 100px;
            height: 100px;
            bottom: 15%;
            right: 10%;
            animation-delay: 6s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .main-container {
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 40px;
        }

        /* Header Section */
        .header-section {
            text-align: center;
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

        .main-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            letter-spacing: -1px;
        }

        .main-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            line-height: 1.6;
            margin: 0 auto 40px;
        }

        /* Action Buttons Container */
        .actions-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            animation: slideIn 0.8s ease-out 0.2s both;
        }

        .degrade-top {
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

        .actions-title {
            text-align: center;
            color: #2E7D32;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
            letter-spacing: -0.5px;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-button {
            width: 100%;
            padding: 18px 25px;
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
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .action-button:nth-child(2) {
            background: linear-gradient(135deg, #388E3C 0%, #66BB6A 100%);
        }

        .action-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .action-button:hover::before {
            left: 100%;
        }

        .action-button:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 15px 30px rgba(76, 175, 80, 0.4),
                0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-button:active {
            transform: translateY(-1px);
        }

        .button-icon {
            font-size: 18px;
        }

        /* Process Section */
        .process-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 90%;
            position: relative;
            overflow: hidden;
            animation: slideIn 0.8s ease-out 0.4s both;
        }

        .process-header {
            padding: 40px 40px 20px;
            text-align: center;
            background: linear-gradient(135deg, #F1F8E9 0%, #E8F5E8 100%);
            position: relative;
        }

        .process-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #2E7D32, #4CAF50, #81C784, #4CAF50, #2E7D32);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        .process-title {
            color: #2E7D32;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .process-subtitle {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
        }

        .process-content {
            padding: 30px 40px 40px;
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .process-step {
            background: white;
            border-radius: 20px;
            padding: 30px 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-left: 5px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .process-step:nth-child(1) { border-left-color: #4CAF50; }
        .process-step:nth-child(2) { border-left-color: #66BB6A; }
        .process-step:nth-child(3) { border-left-color: #81C784; }
        .process-step:nth-child(4) { border-left-color: #A5D6A7; }
        .process-step:nth-child(5) { border-left-color: #C8E6C9; }

        .process-step:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(76, 175, 80, 0.15);
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .step-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #E8F5E8, #F1F8E9);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2E7D32;
            font-size: 20px;
        }

        .step-title {
            font-size: 18px;
            font-weight: 700;
            color: #2E7D32;
            flex: 1;
        }

        .step-time {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .step-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .step-details {
            list-style: none;
        }

        .step-details li {
            color: #555;
            margin: 8px 0;
            padding-left: 20px;
            position: relative;
            line-height: 1.5;
        }

        .step-details li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #4CAF50;
            font-weight: bold;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 20px 15px;
                gap: 30px;
            }

            .actions-container,
            .process-container {
                padding: 30px 25px;
            }

            .process-header {
                padding: 30px 25px 15px;
            }

            .process-content {
                padding: 20px 25px 30px;
            }

            .process-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .process-step {
                padding: 25px 20px;
            }

            .main-title {
                font-size: 2.5rem;
            }

            .process-title {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 15px 10px;
            }

            .actions-container,
            .process-container {
                padding: 25px 20px;
            }

            .process-header {
                padding: 25px 20px 15px;
            }

            .process-content {
                padding: 15px 20px 25px;
            }

            .button-group {
                gap: 15px;
            }

            .action-button {
                padding: 16px 20px;
                font-size: 15px;
            }

            .step-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }

        /* Loading animations */
        .step-details li {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
        }

        .step-details li:nth-child(1) { animation-delay: 0.1s; }
        .step-details li:nth-child(2) { animation-delay: 0.2s; }
        .step-details li:nth-child(3) { animation-delay: 0.3s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <div class="main-container">
        <div class="header-section">
            <h1 class="main-title">Centro de Gestión</h1>
            <p class="main-subtitle">
                Plataforma integral para el seguimiento y control de tus envíos con tecnología avanzada
            </p>
        </div>

        <div class="actions-container">
            <div class="degrade-top"></div>
            <h2 class="actions-title">Servicios Principales</h2>
            
            <div class="button-group">
                <a href="servicio.php" class="action-button">
                    <i class="fas fa-paper-plane button-icon"></i>
                    <span>Solicitar Nuevo Servicio</span>
                </a>
                <a href="../comprobante/comprobante.php" class="action-button">
                    <i class="fas fa-file-contract button-icon"></i>
                    <span>Consultar Comprobante</span>
                </a>
            </div>
        </div>

        <div class="process-container">
            <div class="process-header">
                <h2 class="process-title">Flujo de Proceso</h2>
                <p class="process-subtitle">Seguimiento detallado desde el origen hasta el destino final</p>
            </div>
            
            <div class="process-content">
                <div class="process-grid">
                    <div class="process-step">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <div class="step-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="step-title">Recolección</div>
                            <div class="step-time">10:00 - 12:30</div>
                        </div>
                        <p class="step-description">Recogida programada en tu domicilio con confirmación previa</p>
                        <ul class="step-details">
                            <li>Ventana de recolección garantizada</li>
                            <li>Notificación WhatsApp previa</li>
                            <li>Empaque y etiquetado profesional</li>
                        </ul>
                    </div>

                    <div class="process-step">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <div class="step-icon">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <div class="step-title">Consolidación</div>
                            <div class="step-time">13:00</div>
                        </div>
                        <p class="step-description">Procesamiento en centro logístico de Chapinero</p>
                        <ul class="step-details">
                            <li>Llegada al hub principal</li>
                            <li>Clasificación por zonas de entrega</li>
                            <li>Actualización de estado en tiempo real</li>
                        </ul>
                    </div>

                    <div class="process-step">
                        <div class="step-header">
                            <div class="step-number">3</div>
                            <div class="step-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="step-title">Distribución</div>
                            <div class="step-time">14:00</div>
                        </div>
                        <p class="step-description">Salida optimizada por rutas inteligentes</p>
                        <ul class="step-details">
                            <li>Clasificación por zonas geográficas</li>
                            <li>Entregas durante el resto del día</li>
                            <li>Horario variable según ubicación</li>
                        </ul>
                    </div>

                    <div class="process-step">
                        <div class="step-header">
                            <div class="step-number">4</div>
                            <div class="step-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="step-title">Notificación</div>
                            <div class="step-time">Pre-entrega</div>
                        </div>
                        <p class="step-description">Comunicación directa con el destinatario final</p>
                        <ul class="step-details">
                            <li>Aviso por WhatsApp automático</li>
                            <li>Llamada de confirmación disponibilidad</li>
                            <li>Asegurar recepción del paquete</li>
                        </ul>
                    </div>

                    <div class="process-step">
                        <div class="step-header">
                            <div class="step-number">5</div>
                            <div class="step-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="step-title">Confirmación</div>
                            <div class="step-time">Cierre día</div>
                        </div>
                        <p class="step-description">Documentación completa de la entrega exitosa</p>
                        <ul class="step-details">
                            <li>Soporte digital de entrega</li>
                            <li>Confirmación al remitente</li>
                            <li>Archivo en sistema para consultas</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>