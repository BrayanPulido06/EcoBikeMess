document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Toggle Sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Guardar estado en localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }
    
    // Restaurar estado del sidebar
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
    }
    
    // Marcar link activo según la URL actual
    const currentPage = window.location.pathname.split('/').pop();
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        
        // Remover active de todos
        link.classList.remove('active');
        
        // Agregar active al link correspondiente
        if (linkPage === currentPage) {
            link.classList.add('active');
        }
    });
    
    // Si no hay ningún link activo, activar "Inicio"
    const hasActive = Array.from(navLinks).some(link => link.classList.contains('active'));
    if (!hasActive && navLinks.length > 0) {
        navLinks[0].classList.add('active');
    }
    
    // Para móvil: crear overlay y manejar clics
    if (window.innerWidth <= 768) {
        // Crear overlay si no existe
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }
        
        // Toggle sidebar en móvil desde navbar
        window.toggleSidebarMobile = function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        };
        
        // Cerrar sidebar al hacer clic en overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        // Cerrar sidebar al hacer clic en un link (móvil)
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
    }
    
    // Ajustar sidebar en resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                const overlay = document.querySelector('.sidebar-overlay');
                if (overlay) {
                    overlay.classList.remove('active');
                }
            }
        }, 250);
    });
    
    console.log('Sidebar de cliente cargado ✓');
});