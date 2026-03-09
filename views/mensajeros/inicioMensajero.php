<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'mensajero') {
    header("Location: ../login.php");
    exit();
}

$nombreCompleto = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_lastname'] ?? ''));
if ($nombreCompleto === '') {
    $nombreCompleto = 'Mensajero';
}
?>
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
    <style>
        .route-guide-sheet {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            background: #fff;
        }
        .route-guide-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #eef2f7;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .route-guide-brand {
            margin: 0;
            font-size: 1rem;
            color: #1f2937;
        }
        .route-guide-subtitle {
            margin: 2px 0 0;
            font-size: 0.85rem;
            color: #6b7280;
        }
        .route-guide-number {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: right;
        }
        .route-guide-number small {
            display: block;
            color: #6b7280;
            font-size: 0.75rem;
        }
        .route-guide-number strong {
            color: #111827;
            font-size: 0.95rem;
        }
        .route-guide-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr;
        }
        .route-guide-card {
            border: 1px solid #eef2f7;
            border-radius: 10px;
            padding: 10px;
            background: #fbfdff;
        }
        .route-guide-card h4 {
            margin: 0 0 6px;
            font-size: 0.92rem;
            color: #1f2937;
        }
        .route-guide-card p {
            margin: 4px 0;
            font-size: 0.88rem;
            color: #334155;
        }
        .route-guide-meta {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #d1d5db;
            font-size: 0.85rem;
            color: #475569;
            display: grid;
            gap: 4px;
        }
        .route-guide-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 12px;
        }
        .route-guide-actions .btn-primary,
        .route-guide-actions .btn-secondary {
            min-width: 120px;
        }
    </style>
</head>
<body>
    <!-- Header Móvil -->
    <header class="mobile-header">
        <button class="menu-btn" id="menuBtn">
            <span class="menu-icon">☰</span>
        </button>
        <div class="header-info">
            <h1>🚴 EcoBikeMess</h1>
            <p class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></p>
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

        <!-- Botón Realizar Ruta -->
        <div class="deliver-section" id="deliverSection" style="display: none;">
            <button class="btn-deliver" id="btnDeliver">
                <span class="deliver-icon">📦</span>
                <span class="deliver-text">Realizar Ruta</span>
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

    <!-- Modal Detalle de Ruta -->
    <div class="modal" id="routeDetailModal">
        <div class="modal-content">
            <button class="modal-close" id="closeRouteDetailModal">×</button>
            <h2>Detalle del Paquete</h2>
            <div id="routeDetailBody" style="display: grid; gap: 8px; margin-top: 10px;"></div>
            <div class="modal-actions">
                <button class="btn-primary" id="btnCloseRouteDetail">Cerrar</button>
            </div>
        </div>
    </div>

    <script src="../../public/js/inicioMensajero.js"></script>
</body>
</html>
