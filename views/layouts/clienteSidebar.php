<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img class="logo-icon" src="../../public/img/Logo_Circulo_Fondoblanco.png"  alt="EcoBikeMess" style="width: 55px; vertical-align: middle;">
            <span class="logo-text">EcoBikeMess</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <span class="toggle-icon">☰</span>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="../Clientes/inicioCliente.php" class="nav-link">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Inicio</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../Clientes/enviarPaquete.php" class="nav-link">
                    <span class="nav-icon">📦</span>
                    <span class="nav-text">Enviar Paquete</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../Clientes/misPedidos.php" class="nav-link">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text">Mis Pedidos</span>
                </a>
            </li>
            

            <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'colaborador'): ?>
            <li class="nav-item">
                <a href="../Clientes/equipoTrabajo.php" class="nav-link">
                    <span class="nav-icon">💬</span>
                    <span class="nav-text">Equipo de Trabajo</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-plan">
            <span class="plan-icon">⭐</span>
            <div class="plan-info">
                <span class="plan-name">Cliente</span>
                <span class="plan-status">Activo</span>
            </div>
        </div>
    </div>
</aside>

<!-- Botón Flotante de WhatsApp -->
<a href="https://wa.link/49g8jg" class="whatsapp-container" target="_blank" rel="noopener">
    <div class="whatsapp-btn">
        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
    </div>

</a>

<script src="../../public/js/clienteSidebar.js"></script>
