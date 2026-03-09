document.addEventListener('DOMContentLoaded', function() {
    
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationPanel = document.getElementById('notificationPanel');
    const userBtn = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
    const userMenu = document.getElementById('userMenu');
    
    // ============================================
    // TOGGLE NOTIFICACIONES
    // ============================================
    if (notificationBtn && notificationPanel) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationPanel.classList.toggle('active');
            
            // Cerrar dropdown de usuario si está abierto
            if (userDropdown) {
                userDropdown.classList.remove('active');
                userMenu.classList.remove('active');
            }
        });
    }
    
    // ============================================
    // TOGGLE MENÚ DE USUARIO
    // ============================================
    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
            userMenu.classList.toggle('active');
            
            // Cerrar panel de notificaciones si está abierto
            if (notificationPanel) {
                notificationPanel.classList.remove('active');
            }
        });
    }
    
    // ============================================
    // CERRAR DROPDOWNS AL HACER CLIC FUERA
    // ============================================
    document.addEventListener('click', function(e) {
        // Cerrar notificaciones
        if (notificationPanel && !notificationBtn.contains(e.target) && !notificationPanel.contains(e.target)) {
            notificationPanel.classList.remove('active');
        }
        
        // Cerrar menú de usuario
        if (userDropdown && !userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.remove('active');
            userMenu.classList.remove('active');
        }
    });
    
    // ============================================
    // MARCAR NOTIFICACIONES COMO LEÍDAS
    // ============================================
    const markReadBtn = document.querySelector('.mark-read-btn');
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function() {
            const unreadItems = document.querySelectorAll('.notification-item.unread');
            unreadItems.forEach(item => {
                item.classList.remove('unread');
            });
            
            // Actualizar badge
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
            
            // Aquí iría la llamada al servidor para marcar como leídas
            console.log('Notificaciones marcadas como leídas');
        });
    }
    
    // ============================================
    // CLICK EN NOTIFICACIÓN INDIVIDUAL
    // ============================================
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            this.classList.remove('unread');
            
            // Actualizar contador
            updateNotificationBadge();
            
            // Aquí iría la lógica para navegar o mostrar detalles
            console.log('Notificación clickeada');
        });
    });
    
    // ============================================
    // ACTUALIZAR BADGE DE NOTIFICACIONES
    // ============================================
    function updateNotificationBadge() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        const badge = document.querySelector('.notification-badge');
        
        if (badge) {
            badge.textContent = unreadCount;
            if (unreadCount === 0) {
                badge.style.display = 'none';
            } else {
                badge.style.display = 'block';
            }
        }
    }
    
    // ============================================
    // ACTUALIZAR TÍTULO DE PÁGINA DINÁMICAMENTE
    // ============================================
    function updatePageTitle() {
        const currentPage = window.location.pathname.split('/').pop();
        const pageTitle = document.getElementById('pageTitle');
        const pageSubtitle = document.getElementById('pageSubtitle');
        
        const pageTitles = {
            'inicioCliente.php': { title: 'Dashboard', subtitle: 'Bienvenido de nuevo' },
            'enviarPaquete.php': { title: 'Enviar Paquete', subtitle: 'Crea un nuevo envío' },
            'misPedidos.php': { title: 'Mis Pedidos', subtitle: 'Gestiona tus envíos' },
            'seguimiento.php': { title: 'Seguimiento', subtitle: 'Rastrea tus paquetes' },
            'comprobantes.php': { title: 'Comprobantes', subtitle: 'Documentos y facturas' },
            'facturacion.php': { title: 'Facturación', subtitle: 'Estado de cuenta' },
            'historial.php': { title: 'Historial', subtitle: 'Registro completo' },
            'miEmprendimiento.php': { title: 'Mi Emprendimiento', subtitle: 'Información del negocio' },
            'soporte.php': { title: 'Soporte', subtitle: '¿Necesitas ayuda?' }
        };
        
        const pageInfo = pageTitles[currentPage] || { title: 'Dashboard', subtitle: 'Bienvenido' };
        
        if (pageTitle) pageTitle.textContent = pageInfo.title;
        if (pageSubtitle) pageSubtitle.textContent = pageInfo.subtitle;
    }
    
    // Actualizar título al cargar
    updatePageTitle();
    
    // ============================================
    // CERRAR SESIÓN CON CONFIRMACIÓN
    // ============================================
    const logoutLink = document.querySelector('.dropdown-item.logout');
    if (logoutLink) {
        // Inyectar HTML del modal personalizado si no existe
        if (!document.getElementById('customLogoutModal')) {
            const modalHtml = `
                <div id="customLogoutModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease;">
                    <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); transform: translateY(20px); transition: transform 0.3s ease;">
                        <div style="font-size: 48px; margin-bottom: 15px;">🚪</div>
                        <h3 style="margin: 0 0 10px; color: #2c3e50; font-size: 1.5rem;">¿Cerrar Sesión?</h3>
                        <p style="color: #666; margin-bottom: 25px; font-size: 1rem;">¿Estás seguro de que deseas salir del sistema?</p>
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button id="btnCancelLogout" style="padding: 10px 20px; border: 1px solid #ddd; background: #f8f9fa; color: #333; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;">Cancelar</button>
                            <button id="btnConfirmLogout" style="padding: 10px 20px; border: none; background: #dc3545; color: white; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 5px rgba(220, 53, 69, 0.3);">Sí, Cerrar Sesión</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        const modal = document.getElementById('customLogoutModal');
        const modalContent = modal.querySelector('div');
        const btnCancel = document.getElementById('btnCancelLogout');
        const btnConfirm = document.getElementById('btnConfirmLogout');
        let targetUrl = '';

        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            targetUrl = this.getAttribute('href');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.style.transform = 'translateY(0)';
            }, 10);
        });

        const closeModal = () => {
            modal.style.opacity = '0';
            modalContent.style.transform = 'translateY(20px)';
            setTimeout(() => { modal.style.display = 'none'; }, 300);
        };

        btnCancel.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        
        btnConfirm.addEventListener('click', function() {
            window.location.href = targetUrl;
        });
    }
    
    // ============================================
    // SIMULAR RECIBIR NUEVAS NOTIFICACIONES
    // ============================================
    function simulateNewNotification() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            let count = parseInt(badge.textContent) || 0;
            count++;
            badge.textContent = count;
            badge.style.display = 'block';
            
            // Efecto de animación
            badge.style.animation = 'none';
            setTimeout(() => {
                badge.style.animation = 'pulse 0.5s ease';
            }, 10);
        }
    }
    
    // Agregar animación de pulse al CSS dinámicamente
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
    `;
    document.head.appendChild(style);
    
    console.log('Navbar de cliente cargado ✓');
});
