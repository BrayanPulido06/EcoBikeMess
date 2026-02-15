<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Mensajero - EcoBikeMess</title>
    <link rel="stylesheet" href="../../public/css/inicioMensajero.css">
    <link rel="stylesheet" href="../../public/css/mensajeroSidebar.css">
    <!-- Librería para escaneo de QR -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body>
    <!-- Header Móvil -->
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1>🚴 EcoBikeMess</h1>
            <p class="user-name">Carlos Rodríguez</p>
        </div>
        <button class="notif-btn" id="notifBtn">
            <span class="notif-icon">🔔</span>
            <span class="notif-badge">3</span>
        </button>
    </header>

    <!-- Menu Lateral -->
    <?php include '../layouts/mensajeroSidebar.php'; ?>

    <!-- Contenido Principal -->
    <main class="main-content">
        <!-- Estado de Sesión -->
        <div class="session-status">
            <div class="status-indicator online" id="statusIndicator">
                <span class="status-dot"></span>
                <span class="status-text">En línea</span>
            </div>
            <div class="session-time">
                <span class="time-icon">⏱️</span>
                <span id="sessionTime">00:00:00</span>
            </div>
        </div>

        <!-- Botón Principal - Escanear QR -->
        <div class="main-action">
            <button class="btn-scan-qr" id="btnScanQR">
                <div class="qr-icon-container">
                    <svg class="qr-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <rect x="10" y="10" width="35" height="35" fill="currentColor"/>
                        <rect x="55" y="10" width="35" height="35" fill="currentColor"/>
                        <rect x="10" y="55" width="35" height="35" fill="currentColor"/>
                        <rect x="65" y="65" width="10" height="10" fill="currentColor"/>
                        <rect x="80" y="65" width="10" height="10" fill="currentColor"/>
                        <rect x="65" y="80" width="10" height="10" fill="currentColor"/>
                        <rect x="80" y="80" width="10" height="10" fill="currentColor"/>
                    </svg>
                </div>
                <span class="btn-text">Escanear QR</span>
                <span class="btn-subtitle">Toca para comenzar</span>
            </button>
        </div>

        <!-- Contador de QR Escaneados -->
        <div class="qr-counter-card">
            <div class="counter-header">
                <h3>📱 QR Escaneados Hoy</h3>
                <button class="btn-reset" id="btnResetCounter" style="display: none;">
                    Limpiar
                </button>
            </div>
            <div class="counter-display">
                <div class="counter-number" id="qrCounter">0</div>
                <div class="counter-label">Paquetes listos para entrega</div>
            </div>
            <div class="scanned-list" id="scannedList">
                <!-- Lista de QR escaneados -->
            </div>
        </div>

        <!-- Botón Entregar Paquetes -->
        <div class="deliver-section" id="deliverSection" style="display: none;">
            <button class="btn-deliver" id="btnDeliver">
                <span class="deliver-icon">📦</span>
                <span class="deliver-text">Entregar Paquetes</span>
                <span class="deliver-count" id="deliverCount">0 paquetes</span>
            </button>
        </div>

        <!-- Recolecciones Pendientes -->
        <div class="collections-section">
            <div class="section-header">
                <h2 class="section-title">📥 Recolecciones Pendientes</h2>
                <div class="collection-counter">
                    <span class="counter-badge" id="collectionsBadge">0</span>
                </div>
            </div>
            
            <div class="collection-stats">
                <div class="collection-stat">
                    <span class="stat-label">Asignadas:</span>
                    <span class="stat-value" id="collectionAsignadas">0</span>
                </div>
                <div class="collection-stat">
                    <span class="stat-label">Completadas:</span>
                    <span class="stat-value success" id="collectionCompletadas">0</span>
                </div>
            </div>

            <div class="collections-list" id="collectionsList">
                <!-- Lista de recolecciones -->
            </div>
        </div>

        <!-- Entregas Activas -->
        <div class="active-deliveries">
            <h2 class="section-title">🚚 Entregas en Curso</h2>
            <div class="deliveries-list" id="deliveriesList">
                <!-- Lista de entregas activas -->
            </div>
        </div>
    </main>

    <!-- Modal Escanear QR -->
    <div class="modal" id="scanModal">
        <div class="modal-content scan-modal">
            <button class="modal-close" id="closeScanModal">×</button>
            <h2>Escanear Código QR</h2>
            <div class="scan-container">
                <div style="position: relative;">
                    <div id="reader" style="width: 100%; border-radius: 10px; overflow: hidden;"></div>
                    <!-- Botón de Flash (oculto por defecto) -->
                    <button id="btnFlash" style="display: none; position: absolute; top: 10px; right: 10px; z-index: 10; background: rgba(0,0,0,0.6); color: white; border: none; padding: 10px; border-radius: 50%; cursor: pointer; width: 40px; height: 40px;">⚡</button>
                </div>
                <!-- Contador en la parte inferior del escáner -->
                <p style="margin-top: 10px; font-weight: bold; color: #5cb85c;">Escaneados en sesión: <span id="modalQrCounter">0</span></p>
                <p class="scan-instruction">Coloca el código QR frente a la cámara</p>
            </div>
            <div class="scan-actions">
                <button class="btn-secondary" id="btnManualCode">
                    ⌨️ Ingresar código manualmente
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Código Manual -->
    <div class="modal" id="manualModal">
        <div class="modal-content">
            <button class="modal-close" id="closeManualModal">×</button>
            <h2>Ingresar Código Manualmente</h2>
            <div class="manual-input-container">
                <label for="manualCode">Número de Guía</label>
                <input type="text" id="manualCode" placeholder="ECO-2024-XXXXX" maxlength="20">
                <span class="error-message" id="manualError"></span>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" id="btnCancelManual">Cancelar</button>
                <button class="btn-primary" id="btnConfirmManual">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Toast de Notificación -->
    <div class="toast" id="toast">
        <span class="toast-icon" id="toastIcon">✓</span>
        <span class="toast-message" id="toastMessage"></span>
    </div>

    <script src="../../public/js/inicioMensajero.js"></script>
</body>
</html>