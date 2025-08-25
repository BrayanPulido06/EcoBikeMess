<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Clientes - LogiExpress</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --error-color: #dc2626;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Layout Principal */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--card-background);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .main-content {
            flex: 1;
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        .main-content.sidebar-open {
            margin-left: 280px;
        }

        /* Header */
        .header {
            background: var(--card-background);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }

        .menu-toggle:hover {
            background-color: var(--background-color);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }

        .notification-btn:hover {
            background-color: var(--background-color);
        }

        .notification-badge {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: var(--error-color);
            color: white;
            border-radius: 50%;
            width: 1rem;
            height: 1rem;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s;
        }

        .user-profile:hover {
            background-color: var(--background-color);
        }

        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Sidebar Navigation */
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .nav-menu {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
        }

        .nav-item:hover,
        .nav-item.active {
            background-color: var(--background-color);
            color: var(--primary-color);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary-color);
        }

        .nav-icon {
            width: 1.25rem;
            text-align: center;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-background);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-change {
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-change.positive {
            color: var(--success-color);
        }

        .stat-change.negative {
            color: var(--error-color);
        }

        /* Quick Actions */
        .quick-actions {
            background: var(--card-background);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            padding: 1.5rem;
            background: var(--background-color);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s;
            cursor: pointer;
        }

        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .action-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .action-btn:hover .action-icon {
            background: white;
            color: var(--primary-color);
        }

        /* Recent Orders */
        .recent-orders {
            background: var(--card-background);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .view-all-btn {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .view-all-btn:hover {
            text-decoration: underline;
        }

        .orders-list {
            space: 1rem 0;
        }

        .order-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .order-item:last-child {
            margin-bottom: 0;
        }

        .order-info {
            flex: 1;
        }

        .order-id {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .order-details {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-transit {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-background);
            border-radius: 0.75rem;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
        }

        .close-btn:hover {
            color: var(--text-primary);
        }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--background-color);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        /* Responsive */
        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
                position: static;
                height: auto;
            }
            
            .main-content {
                margin-left: 280px;
            }
            
            .menu-toggle {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
            
            .dashboard-content {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Notifications */
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: var(--card-background);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: var(--shadow-lg);
            z-index: 3000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            border-left: 4px solid var(--success-color);
        }

        .notification.error {
            border-left: 4px solid var(--error-color);
        }

        .notification.warning {
            border-left: 4px solid var(--warning-color);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-shipping-fast"></i>
                    LogiExpress
                </div>
            </div>
            
            <nav class="nav-menu">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <a href="#dashboard" class="nav-item active" data-page="dashboard">
                        <i class="nav-icon fas fa-home"></i>
                        Dashboard
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Pedidos</div>
                    <a href="#create-order" class="nav-item" data-page="create-order">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        Crear Pedido
                    </a>
                    <a href="#my-orders" class="nav-item" data-page="my-orders">
                        <i class="nav-icon fas fa-box"></i>
                        Mis Pedidos
                    </a>
                    <a href="#scheduled" class="nav-item" data-page="scheduled">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        Programados
                    </a>
                    <a href="#templates" class="nav-item" data-page="templates">
                        <i class="nav-icon fas fa-copy"></i>
                        Plantillas
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Herramientas</div>
                    <a href="#address-book" class="nav-item" data-page="address-book">
                        <i class="nav-icon fas fa-address-book"></i>
                        Libreta Direcciones
                    </a>
                    <a href="#contacts" class="nav-item" data-page="contacts">
                        <i class="nav-icon fas fa-users"></i>
                        Destinatarios
                    </a>
                    <a href="#calculator" class="nav-item" data-page="calculator">
                        <i class="nav-icon fas fa-calculator"></i>
                        Calculadora
                    </a>
                    <a href="#reports" class="nav-item" data-page="reports">
                        <i class="nav-icon fas fa-chart-line"></i>
                        Reportes
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Finanzas</div>
                    <a href="#account" class="nav-item" data-page="account">
                        <i class="nav-icon fas fa-wallet"></i>
                        Estado de Cuenta
                    </a>
                    <a href="#payments" class="nav-item" data-page="payments">
                        <i class="nav-icon fas fa-credit-card"></i>
                        Métodos de Pago
                    </a>
                    <a href="#billing" class="nav-item" data-page="billing">
                        <i class="nav-icon fas fa-file-invoice"></i>
                        Facturación
                    </a>
                    <a href="#promotions" class="nav-item" data-page="promotions">
                        <i class="nav-icon fas fa-tags"></i>
                        Promociones
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Cuenta</div>
                    <a href="#profile" class="nav-item" data-page="profile">
                        <i class="nav-icon fas fa-user"></i>
                        Mi Perfil
                    </a>
                    <a href="#settings" class="nav-item" data-page="settings">
                        <i class="nav-icon fas fa-cog"></i>
                        Configuración
                    </a>
                    <a href="#support" class="nav-item" data-page="support">
                        <i class="nav-icon fas fa-headset"></i>
                        Soporte
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="logo">
                        <i class="fas fa-shipping-fast"></i>
                        LogiExpress
                    </div>
                </div>
                
                <div class="header-right">
                    <button class="notification-btn" id="notificationBtn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    
                    <div class="user-profile" id="userProfile">
                        <div class="user-avatar">JD</div>
                        <div>
                            <div style="font-weight: 500; font-size: 0.875rem;">Juan Díaz</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary);">Emprendedor</div>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size: 0.75rem; color: var(--text-secondary);"></i>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="dashboard-content" id="pageContent">
                <!-- Dashboard Page -->
                <div class="page" id="dashboard-page">
                    <div class="page-header">
                        <h1 class="page-title">Dashboard</h1>
                        <p class="page-subtitle">Resumen general de tu actividad y pedidos</p>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-title">Pedidos Activos</div>
                                </div>
                                <div class="stat-icon" style="background: var(--primary-color);">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                            <div class="stat-value">12</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                +2 desde ayer
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-title">Entregas del Mes</div>
                                </div>
                                <div class="stat-icon" style="background: var(--success-color);">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="stat-value">156</div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i>
                                +23% vs mes anterior
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-title">Gastos del Mes</div>
                                </div>
                                <div class="stat-icon" style="background: var(--warning-color);">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <div class="stat-value">$2,450</div>
                            <div class="stat-change negative">
                                <i class="fas fa-arrow-down"></i>
                                -5% vs mes anterior
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div>
                                    <div class="stat-title">Crédito Disponible</div>
                                </div>
                                <div class="stat-icon" style="background: var(--secondary-color);">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                            </div>
                            <div class="stat-value">$7,500</div>
                            <div class="stat-change">
                                Límite: $10,000
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <h2 class="section-title">Acciones Rápidas</h2>
                        <div class="actions-grid">
                            <a href="#" class="action-btn" onclick="showCreateOrder()">
                                <div class="action-icon">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <span>Crear Pedido</span>
                            </a>
                            
                            <a href="#" class="action-btn" onclick="showTracking()">
                                <div class="action-icon">
                                    <i class="fas fa-search-location"></i>
                                </div>
                                <span>Rastrear Pedido</span>
                            </a>
                            
                            <a href="#" class="action-btn" onclick="showCalculator()">
                                <div class="action-icon">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <span>Calcular Tarifa</span>
                            </a>
                            
                            <a href="#" class="action-btn" onclick="showSupport()">
                                <div class="action-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <span>Soporte</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="recent-orders">
                        <div class="orders-header">
                            <h2 class="section-title">Pedidos Recientes</h2>
                            <a href="#" class="view-all-btn">Ver todos</a>
                        </div>
                        
                        <div class="orders-list">
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-id">#LX2024001234</div>
                                    <div class="order-details">Bogotá → Medellín • 2.5 kg • Hoy 09:30</div>
                                </div>
                                <div class="order-status status-transit">En tránsito</div>
                            </div>
                            
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-id">#LX2024001233</div>
                                    <div class="order-details">Cali → Barranquilla • 1.2 kg • Ayer 14:15</div>
                                </div>
                                <div class="order-status status-delivered">Entregado</div>
                            </div>
                            
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-id">#LX2024001232</div>
                                    <div class="order-details">Bogotá → Cartagena • 3.8 kg • 12 Ago</div>
                                </div>
                                <div class="order-status status-pending">Pendiente</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Other pages will be added here dynamically -->
            </div>
        </main>
    </div>
    
    <!-- Create Order Modal -->
    <div class="modal" id="createOrderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Crear Nuevo Pedido</h3>
                <button class="close-btn" onclick="closeModal('createOrderModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="createOrderForm">
                <div class="form-group">
                    <label class="form-label">Dirección de Origen</label>
                    <input type="text" class="form-input" placeholder="Ingresa la dirección de recogida" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dirección de Destino</label>
                    <input type="text" class="form-input" placeholder="Ingresa la dirección de entrega" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Peso (kg)</label>
                        <input type="number" class="form-input" placeholder="0.0" step="0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Valor Declarado</label>
                        <input type="number" class="form-input" placeholder="$0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Servicio</label>
                    <select class="form-input" required>
                        <option value="">Selecciona un servicio</option>
                        <option value="express">Express (24h)</option>
                        <option value="standard">Estándar (2-3 días)</option>
                        <option value="economy">Económico (3-5 días)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descripción del Paquete</label>
                    <textarea class="form-input" rows="3" placeholder="Describe brevemente el contenido"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Crear Pedido
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createOrderModal')">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tracking Modal -->
    <div class="modal" id="trackingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Rastrear Pedido</h3>
                <button class="close-btn" onclick="closeModal('trackingModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="trackingForm">
                <div class="form-group">
                    <label class="form-label">Número de Guía</label>
                    <input type="text" class="form-input" placeholder="Ej: LX2024001234" required>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Buscar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('trackingModal')">
                        Cancelar
                    </button>
                </div>
            </form>
            
            <!-- Tracking Results (hidden by default) -->
            <div id="trackingResults" style="display: none; margin-top: 2rem;">
                <div style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                    <h4 style="margin-bottom: 1rem;">Estado del Pedido: #LX2024001234</h4>
                    
                    <div style="display: flex; justify-content: between; margin-bottom: 1rem;">
                        <div>
                            <strong>Estado Actual:</strong> <span class="order-status status-transit">En tránsito</span>
                        </div>
                        <div style="text-align: right; font-size: 0.875rem; color: var(--text-secondary);">
                            Última actualización: Hoy 14:30
                        </div>
                    </div>
                    
                    <div style="background: var(--background-color); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                        <div style="font-weight: 500; margin-bottom: 0.5rem;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary-color);"></i>
                            Ubicación Actual
                        </div>
                        <div style="color: var(--text-secondary);">Centro de Distribución Medellín</div>
                    </div>
                    
                    <div>
                        <h5 style="margin-bottom: 0.75rem;">Historial de Movimientos</h5>
                        <div style="border-left: 2px solid var(--border-color); padding-left: 1rem;">
                            <div style="margin-bottom: 1rem; position: relative;">
                                <div style="position: absolute; left: -0.5rem; width: 0.75rem; height: 0.75rem; background: var(--success-color); border-radius: 50%;"></div>
                                <div style="font-weight: 500; font-size: 0.875rem;">En tránsito a destino</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Hoy 14:30 - Medellín</div>
                            </div>
                            
                            <div style="margin-bottom: 1rem; position: relative;">
                                <div style="position: absolute; left: -0.5rem; width: 0.75rem; height: 0.75rem; background: var(--border-color); border-radius: 50%;"></div>
                                <div style="font-weight: 500; font-size: 0.875rem;">Paquete en centro de distribución</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Hoy 08:15 - Bogotá</div>
                            </div>
                            
                            <div style="margin-bottom: 1rem; position: relative;">
                                <div style="position: absolute; left: -0.5rem; width: 0.75rem; height: 0.75rem; background: var(--border-color); border-radius: 50%;"></div>
                                <div style="font-weight: 500; font-size: 0.875rem;">Paquete recolectado</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Ayer 16:45 - Bogotá</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calculator Modal -->
    <div class="modal" id="calculatorModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Calculadora de Tarifas</h3>
                <button class="close-btn" onclick="closeModal('calculatorModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="calculatorForm">
                <div class="form-group">
                    <label class="form-label">Ciudad Origen</label>
                    <select class="form-input" required>
                        <option value="">Selecciona ciudad</option>
                        <option value="bogota">Bogotá</option>
                        <option value="medellin">Medellín</option>
                        <option value="cali">Cali</option>
                        <option value="barranquilla">Barranquilla</option>
                        <option value="cartagena">Cartagena</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ciudad Destino</label>
                    <select class="form-input" required>
                        <option value="">Selecciona ciudad</option>
                        <option value="bogota">Bogotá</option>
                        <option value="medellin">Medellín</option>
                        <option value="cali">Cali</option>
                        <option value="barranquilla">Barranquilla</option>
                        <option value="cartagena">Cartagena</option>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Peso (kg)</label>
                        <input type="number" class="form-input" placeholder="0.0" step="0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Valor Declarado</label>
                        <input type="number" class="form-input" placeholder="$0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Servicio</label>
                    <select class="form-input" required>
                        <option value="express">Express (24h) - +30%</option>
                        <option value="standard" selected>Estándar (2-3 días)</option>
                        <option value="economy">Económico (3-5 días) - 15% desc</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calculator"></i>
                        Calcular
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('calculatorModal')">
                        Cancelar
                    </button>
                </div>
            </form>
            
            <!-- Calculator Results -->
            <div id="calculatorResults" style="display: none; margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Cotización</h4>
                
                <div style="background: var(--background-color); padding: 1rem; border-radius: 0.5rem;">
                    <div style="display: flex; justify-content: between; margin-bottom: 0.5rem;">
                        <span>Tarifa base:</span>
                        <span>$25,000</span>
                    </div>
                    <div style="display: flex; justify-content: between; margin-bottom: 0.5rem;">
                        <span>Seguro (2%):</span>
                        <span>$500</span>
                    </div>
                    <div style="display: flex; justify-content: between; margin-bottom: 0.5rem;">
                        <span>Descuento cliente frecuente:</span>
                        <span style="color: var(--success-color);">-$2,500</span>
                    </div>
                    <hr style="margin: 0.75rem 0;">
                    <div style="display: flex; justify-content: between; font-weight: 600; font-size: 1.1rem;">
                        <span>Total:</span>
                        <span style="color: var(--primary-color);">$23,000</span>
                    </div>
                </div>
                
                <div style="margin-top: 1rem; padding: 1rem; background: #e0f2fe; border-radius: 0.5rem; border-left: 4px solid var(--primary-color);">
                    <div style="font-weight: 500; margin-bottom: 0.25rem;">
                        <i class="fas fa-info-circle"></i>
                        Tiempo estimado de entrega
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                        2-3 días hábiles • Entrega estimada: 25 de Agosto
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Modal -->
    <div class="modal" id="supportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Centro de Soporte</h3>
                <button class="close-btn" onclick="closeModal('supportModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div style="display: grid; gap: 1rem;">
                <a href="#" class="action-btn" style="text-decoration: none; padding: 1rem;">
                    <div class="action-icon" style="width: 2rem; height: 2rem; font-size: 1rem;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div>
                        <div style="font-weight: 500;">Chat en Vivo</div>
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">Respuesta inmediata</div>
                    </div>
                </a>
                
                <a href="#" class="action-btn" style="text-decoration: none; padding: 1rem;">
                    <div class="action-icon" style="width: 2rem; height: 2rem; font-size: 1rem;">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div>
                        <div style="font-weight: 500;">Crear Ticket</div>
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">Para consultas detalladas</div>
                    </div>
                </a>
                
                <a href="#" class="action-btn" style="text-decoration: none; padding: 1rem;">
                    <div class="action-icon" style="width: 2rem; height: 2rem; font-size: 1rem;">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div>
                        <div style="font-weight: 500;">Preguntas Frecuentes</div>
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">Encuentra respuestas rápidas</div>
                    </div>
                </a>
                
                <a href="#" class="action-btn" style="text-decoration: none; padding: 1rem;">
                    <div class="action-icon" style="width: 2rem; height: 2rem; font-size: 1rem;">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div>
                        <div style="font-weight: 500;">Línea de Atención</div>
                        <div style="font-size: 0.875rem; color: var(--text-secondary);">(01) 800-123-4567</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Estado global de la aplicación
        const AppState = {
            currentPage: 'dashboard',
            sidebarOpen: window.innerWidth >= 768,
            user: {
                name: 'Juan Díaz',
                avatar: 'JD',
                notifications: 3
            }
        };

        // Inicialización de la aplicación
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
            setupEventListeners();
            loadDashboardData();
        });

        // Inicializar aplicación
        function initializeApp() {
            updateSidebarState();
            setActiveNavItem('dashboard');
            showPage('dashboard');
        }

        // Configurar event listeners
        function setupEventListeners() {
            // Toggle sidebar en mobile
            document.getElementById('menuToggle').addEventListener('click', toggleSidebar);
            
            // Navigation items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', handleNavigation);
            });

            // Forms
            setupFormHandlers();
            
            // Modal events
            setupModalEvents();
            
            // Responsive behavior
            window.addEventListener('resize', handleResize);
        }

        // Toggle sidebar
        function toggleSidebar() {
            AppState.sidebarOpen = !AppState.sidebarOpen;
            updateSidebarState();
        }

        // Update sidebar state
        function updateSidebarState() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (AppState.sidebarOpen) {
                sidebar.classList.add('open');
                mainContent.classList.add('sidebar-open');
            } else {
                sidebar.classList.remove('open');
                mainContent.classList.remove('sidebar-open');
            }
        }

        // Handle navigation
        function handleNavigation(e) {
            e.preventDefault();
            const page = e.currentTarget.dataset.page;
            
            if (page && page !== AppState.currentPage) {
                setActiveNavItem(page);
                showPage(page);
                AppState.currentPage = page;
                
                // Close sidebar en mobile después de navegación
                if (window.innerWidth < 768) {
                    AppState.sidebarOpen = false;
                    updateSidebarState();
                }
            }
        }

        // Set active navigation item
        function setActiveNavItem(page) {
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            const activeItem = document.querySelector(`[data-page="${page}"]`);
            if (activeItem) {
                activeItem.classList.add('active');
            }
        }

        // Show page content
        function showPage(page) {
            // Por ahora solo mostramos el dashboard
            // En una implementación real, aquí cargaríamos el contenido específico de cada página
            const content = document.getElementById('pageContent');
            
            switch(page) {
                case 'dashboard':
                    // Dashboard ya está cargado
                    break;
                case 'create-order':
                    showCreateOrder();
                    break;
                case 'my-orders':
                    loadMyOrdersPage();
                    break;
                case 'calculator':
                    showCalculator();
                    break;
                default:
                    showComingSoon(page);
            }
        }

        // Show coming soon page
        function showComingSoon(pageName) {
            const content = document.getElementById('pageContent');
            const pageTitle = pageName.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            content.innerHTML = `
                <div style="text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h2 style="margin-bottom: 1rem; color: var(--text-primary);">${pageTitle}</h2>
                    <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 2rem;">
                        Esta sección está en desarrollo. Pronto estará disponible.
                    </p>
                    <button class="btn btn-primary" onclick="setActiveNavItem('dashboard'); showPage('dashboard'); AppState.currentPage = 'dashboard';">
                        <i class="fas fa-home"></i>
                        Volver al Dashboard
                    </button>
                </div>
            `;
        }

        // Setup form handlers
        function setupFormHandlers() {
            // Create order form
            document.getElementById('createOrderForm').addEventListener('submit', function(e) {
                e.preventDefault();
                handleCreateOrder(this);
            });

            // Tracking form
            document.getElementById('trackingForm').addEventListener('submit', function(e) {
                e.preventDefault();
                handleTracking(this);
            });

            // Calculator form
            document.getElementById('calculatorForm').addEventListener('submit', function(e) {
                e.preventDefault();
                handleCalculator(this);
            });
        }

        // Handle create order
        function handleCreateOrder(form) {
            showLoading('Creando pedido...');
            
            // Simular creación de pedido
            setTimeout(() => {
                hideLoading();
                closeModal('createOrderModal');
                showNotification('Pedido creado exitosamente', 'success');
                form.reset();
                
                // Actualizar dashboard
                loadDashboardData();
            }, 2000);
        }

        // Handle tracking
        function handleTracking(form) {
            const trackingNumber = form.querySelector('input').value;
            
            if (trackingNumber) {
                // Mostrar resultados de tracking
                document.getElementById('trackingResults').style.display = 'block';
                showNotification('Pedido encontrado', 'success');
            }
        }

        // Handle calculator
        function handleCalculator(form) {
            // Simular cálculo
            setTimeout(() => {
                document.getElementById('calculatorResults').style.display = 'block';
                showNotification('Cotización calculada', 'success');
            }, 500);
        }

        // Setup modal events
        function setupModalEvents() {
            // Close modal when clicking outside
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });
        }

        // Modal functions
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
                
                // Reset forms and hide results
                const form = modal.querySelector('form');
                if (form) form.reset();
                
                const results = modal.querySelectorAll('[id$="Results"]');
                results.forEach(result => result.style.display = 'none');
            }
        }

        // Quick action functions
        function showCreateOrder() {
            showModal('createOrderModal');
        }

        function showTracking() {
            showModal('trackingModal');
        }

        function showCalculator() {
            showModal('calculatorModal');
        }

        function showSupport() {
            showModal('supportModal');
        }

        // Load dashboard data
        function loadDashboardData() {
            // Simular carga de datos
            // En una app real, aquí haríamos llamadas a la API
            console.log('Loading dashboard data...');
        }

        // Load my orders page
        function loadMyOrdersPage() {
            const content = document.getElementById('pageContent');
            content.innerHTML = `
                <div class="page-header">
                    <h1 class="page-title">Mis Pedidos</h1>
                    <p class="page-subtitle">Gestiona y rastrea todos tus envíos</p>
                </div>
                
                <div class="recent-orders">
                    <div class="orders-header">
                        <h2 class="section-title">Todos los Pedidos</h2>
                        <button class="btn btn-primary" onclick="showCreateOrder()">
                            <i class="fas fa-plus"></i>
                            Nuevo Pedido
                        </button>
                    </div>
                    
                    <div style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <input type="text" class="form-input" placeholder="Buscar por número de guía..." style="flex: 1; min-width: 200px;">
                        <select class="form-input" style="width: 150px;">
                            <option>Todos los estados</option>
                            <option>Pendiente</option>
                            <option>En tránsito</option>
                            <option>Entregado</option>
                        </select>
                        <button class="btn btn-secondary">
                            <i class="fas fa-filter"></i>
                            Filtrar
                        </button>
                    </div>
                    
                    <div class="orders-list">
                        <div class="order-item">
                            <div class="order-info">
                                <div class="order-id">#LX2024001234</div>
                                <div class="order-details">Bogotá → Medellín • 2.5 kg • Hoy 09:30</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    Destinatario: María González • Tel: 300-123-4567
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="order-status status-transit">En tránsito</div>
                                <div style="margin-top: 0.5rem;">
                                    <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="showTracking()">
                                        <i class="fas fa-search-location"></i>
                                        Rastrear
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-item">
                            <div class="order-info">
                                <div class="order-id">#LX2024001233</div>
                                <div class="order-details">Cali → Barranquilla • 1.2 kg • Ayer 14:15</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    Destinatario: Carlos Rodríguez • Tel: 310-987-6543
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="order-status status-delivered">Entregado</div>
                                <div style="margin-top: 0.5rem;">
                                    <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <i class="fas fa-download"></i>
                                        Comprobante
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-item">
                            <div class="order-info">
                                <div class="order-id">#LX2024001232</div>
                                <div class="order-details">Bogotá → Cartagena • 3.8 kg • 12 Ago</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                    Destinatario: Ana Martínez • Tel: 320-456-7890
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="order-status status-pending">Pendiente</div>
                                <div style="margin-top: 0.5rem;">
                                    <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <i class="fas fa-edit"></i>
                                        Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button class="btn btn-secondary">
                            Cargar más pedidos
                        </button>
                    </div>
                </div>
            `;
        }

        // Notification system
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type} show`;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Loading system
        function showLoading(message = 'Cargando...') {
            // Implementar loading overlay
            const loading = document.createElement('div');
            loading.id = 'loading';
            loading.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            loading.innerHTML = `
                <div style="background: white; padding: 2rem; border-radius: 0.5rem; text-align: center;">
                    <div class="loading" style="margin: 0 auto 1rem;"></div>
                    <div>${message}</div>
                </div>
            `;
            document.body.appendChild(loading);
        }

        function hideLoading() {
            const loading = document.getElementById('loading');
            if (loading) {
                loading.remove();
            }
        }

        // Handle responsive behavior
        function handleResize() {
            if (window.innerWidth >= 768) {
                AppState.sidebarOpen = true;
            } else {
                AppState.sidebarOpen = false;
            }
            updateSidebarState();
        }

        // Utility functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0
            }).format(amount);
        }

        function formatDate(date) {
            return new Intl.DateTimeFormat('es-CO', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(date));
        }

        // API simulation functions
        function simulateApiCall(endpoint, data = null) {
            return new Promise((resolve) => {
                setTimeout(() => {
                    switch(endpoint) {
                        case 'orders':
                            resolve({
                                success: true,
                                data: [
                                    {
                                        id: 'LX2024001234',
                                        origin: 'Bogotá',
                                        destination: 'Medellín',
                                        weight: 2.5,
                                        status: 'transit',
                                        date: '2024-08-23T09:30:00',
                                        recipient: 'María González'
                                    }
                                ]
                            });
                        case 'tracking':
                            resolve({
                                success: true,
                                data: {
                                    status: 'transit',
                                    location: 'Centro de Distribución Medellín',
                                    updates: [
                                        { status: 'En tránsito', date: '2024-08-23T14:30:00', location: 'Medellín' },
                                        { status: 'En centro de distribución', date: '2024-08-23T08:15:00', location: 'Bogotá' },
                                        { status: 'Recolectado', date: '2024-08-22T16:45:00', location: 'Bogotá' }
                                    ]
                                }
                            });
                        case 'calculate':
                            resolve({
                                success: true,
                                data: {
                                    basePrice: 25000,
                                    insurance: 500,
                                    discount: -2500,
                                    total: 23000,
                                    deliveryTime: '2-3 días hábiles'
                                }
                            });
                        default:
                            resolve({ success: true, data: {} });
                    }
                }, Math.random() * 1000 + 500);
            });
        }

        // Advanced features
        class OrderManager {
            constructor() {
                this.orders = [];
                this.filters = {
                    status: 'all',
                    dateRange: 'all',
                    searchTerm: ''
                };
            }

            async loadOrders() {
                try {
                    const response = await simulateApiCall('orders');
                    if (response.success) {
                        this.orders = response.data;
                        this.renderOrders();
                    }
                } catch (error) {
                    showNotification('Error al cargar pedidos', 'error');
                }
            }

            filterOrders() {
                return this.orders.filter(order => {
                    const matchesStatus = this.filters.status === 'all' || order.status === this.filters.status;
                    const matchesSearch = !this.filters.searchTerm || 
                        order.id.toLowerCase().includes(this.filters.searchTerm.toLowerCase()) ||
                        order.recipient.toLowerCase().includes(this.filters.searchTerm.toLowerCase());
                    
                    return matchesStatus && matchesSearch;
                });
            }

            renderOrders() {
                const filteredOrders = this.filterOrders();
                const container = document.querySelector('.orders-list');
                
                if (container) {
                    container.innerHTML = filteredOrders.map(order => this.renderOrderCard(order)).join('');
                }
            }

            renderOrderCard(order) {
                const statusClass = {
                    'pending': 'status-pending',
                    'transit': 'status-transit', 
                    'delivered': 'status-delivered'
                }[order.status] || 'status-pending';

                const statusText = {
                    'pending': 'Pendiente',
                    'transit': 'En tránsito',
                    'delivered': 'Entregado'
                }[order.status] || 'Pendiente';

                return `
                    <div class="order-item">
                        <div class="order-info">
                            <div class="order-id">#${order.id}</div>
                            <div class="order-details">${order.origin} → ${order.destination} • ${order.weight} kg • ${formatDate(order.date)}</div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                Destinatario: ${order.recipient}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="order-status ${statusClass}">${statusText}</div>
                            <div style="margin-top: 0.5rem;">
                                <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="trackOrder('${order.id}')">
                                    <i class="fas fa-search-location"></i>
                                    Rastrear
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        // Initialize order manager
        const orderManager = new OrderManager();

        // Track specific order
        function trackOrder(orderId) {
            // Pre-fill tracking modal with order ID
            const trackingInput = document.querySelector('#trackingModal input');
            if (trackingInput) {
                trackingInput.value = orderId;
            }
            showModal('trackingModal');
        }

        // Enhanced form validation
        function validateForm(form) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--error-color)';
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--border-color)';
                }
            });
            
            return isValid;
        }

        // Auto-save draft functionality
        function autoSaveDraft(formId) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            const formData = new FormData(form);
            const draftData = {};
            
            for (let [key, value] of formData.entries()) {
                draftData[key] = value;
            }
            
            // In a real app, this would be saved to the server
            console.log('Draft saved:', draftData);
        }

        // Progressive Web App features
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K para buscar
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[placeholder*="Buscar"]');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Escape para cerrar modales
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
            
            // Ctrl/Cmd + N para nuevo pedido
            if ((e.ctrlKey || e.metaKey) && e.key === 'n' && AppState.currentPage === 'dashboard') {
                e.preventDefault();
                showCreateOrder();
            }
        });

        // Real-time notifications (simulation)
        function startRealtimeUpdates() {
            setInterval(() => {
                // Simulate real-time order updates
                if (Math.random() < 0.1) { // 10% chance every interval
                    const notifications = [
                        'Tu pedido #LX2024001235 está en camino',
                        'Pedido #LX2024001236 ha sido entregado',
                        'Nueva promoción disponible: 20% descuento'
                    ];
                    
                    const randomNotification = notifications[Math.floor(Math.random() * notifications.length)];
                    showNotification(randomNotification, 'success');
                    
                    // Update notification badge
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent) || 0;
                        badge.textContent = currentCount + 1;
                    }
                }
            }, 30000); // Every 30 seconds
        }

        // Advanced search functionality
        function setupAdvancedSearch() {
            const searchInputs = document.querySelectorAll('input[placeholder*="Buscar"]');
            
            searchInputs.forEach(input => {
                let searchTimeout;
                
                input.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    
                    searchTimeout = setTimeout(() => {
                        const searchTerm = this.value.toLowerCase();
                        performSearch(searchTerm);
                    }, 300); // Debounce search
                });
            });
        }

        function performSearch(term) {
            if (AppState.currentPage === 'my-orders') {
                orderManager.filters.searchTerm = term;
                orderManager.renderOrders();
            }
            
            // Add search analytics
            if (term.length > 2) {
                console.log('Search performed:', term);
            }
        }

        // Theme management
        function toggleTheme() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        // Initialize theme
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        }

        // Export functionality
        function exportData(type, format) {
            switch(type) {
                case 'orders':
                    exportOrders(format);
                    break;
                case 'invoices':
                    exportInvoices(format);
                    break;
                default:
                    showNotification('Tipo de exportación no válido', 'error');
            }
        }

        function exportOrders(format) {
            const orders = orderManager.filterOrders();
            
            if (format === 'csv') {
                const csv = convertToCSV(orders);
                downloadFile(csv, 'pedidos.csv', 'text/csv');
            } else if (format === 'pdf') {
                generatePDF(orders, 'Reporte de Pedidos');
            }
        }

        function convertToCSV(data) {
            const headers = ['ID', 'Origen', 'Destino', 'Peso', 'Estado', 'Fecha', 'Destinatario'];
            const csvContent = [
                headers.join(','),
                ...data.map(row => [
                    row.id,
                    row.origin,
                    row.destination,
                    row.weight,
                    row.status,
                    row.date,
                    `"${row.recipient}"`
                ].join(','))
            ].join('\n');
            
            return csvContent;
        }

        function downloadFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showNotification(`Archivo ${filename} descargado`, 'success');
        }

        // Print functionality
        function printPage() {
            window.print();
        }

        function printOrderDetails(orderId) {
            // Create print-friendly version of order details
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Pedido ${orderId}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
                        .details { margin: 20px 0; }
                        .tracking { margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>LogiExpress</h1>
                        <h2>Detalle del Pedido: ${orderId}</h2>
                    </div>
                    <div class="details">
                        <p><strong>Estado:</strong> En tránsito</p>
                        <p><strong>Origen:</strong> Bogotá</p>
                        <p><strong>Destino:</strong> Medellín</p>
                        <p><strong>Peso:</strong> 2.5 kg</p>
                        <p><strong>Fecha de envío:</strong> 23 de Agosto, 2024</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Initialize advanced features after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setupAdvancedSearch();
            initializeTheme();
            startRealtimeUpdates();
        });

        // Performance monitoring
        function trackPerformance() {
            if ('performance' in window) {
                window.addEventListener('load', function() {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
                });
            }
        }

        trackPerformance();

    </script>
</body>
</html>