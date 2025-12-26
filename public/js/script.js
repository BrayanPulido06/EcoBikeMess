// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    
    // Funcionalidad del botón de Iniciar Sesión
    const btnLogin = document.getElementById('btnLogin');
    btnLogin.addEventListener('click', function() {
        window.location.href = '../eco/views/login.php';
    });
    
    // Funcionalidad para expandir/contraer detalles de servicios
    const expandButtons = document.querySelectorAll('.btn-expand');
    
    expandButtons.forEach(button => {
        button.addEventListener('click', function() {
            const service = this.getAttribute('data-service');
            const details = document.getElementById(`details-${service}`);
            
            // Toggle de la clase 'show' en los detalles
            details.classList.toggle('show');
            
            // Toggle de la clase 'active' en el botón
            this.classList.toggle('active');
        });
    });
    
    // Funcionalidad interactiva del mapa
    const zones = document.querySelectorAll('.zone');
    
    zones.forEach(zone => {
        zone.addEventListener('click', function() {
            const zoneName = this.getAttribute('data-zone');
            showZoneInfo(zoneName);
        });
        
        // Efecto hover con información
        zone.addEventListener('mouseenter', function() {
            const zoneName = this.getAttribute('data-zone');
            this.style.opacity = '1';
        });
        
        zone.addEventListener('mouseleave', function() {
            this.style.opacity = '0.7';
        });
    });
    
    // Función para mostrar información de la zona
    function showZoneInfo(zone) {
        const zoneInfo = {
            'norte': {
                nombre: 'Zona Norte',
                localidades: 'Usaquén, Chapinero',
                tarifa: '$8.000',
                tiempo: '45-60 minutos'
            },
            'centro': {
                nombre: 'Zona Centro',
                localidades: 'Teusaquillo, Santa Fe, La Candelaria',
                tarifa: '$6.000',
                tiempo: '30-45 minutos'
            },
            'sur': {
                nombre: 'Zona Sur',
                localidades: 'Kennedy, Bosa, Usme, Ciudad Bolívar',
                tarifa: '$7.500',
                tiempo: '45-60 minutos'
            },
            'occidente': {
                nombre: 'Zona Occidente',
                localidades: 'Fontibón, Engativá',
                tarifa: '$7.000',
                tiempo: '40-55 minutos'
            }
        };
        
        const info = zoneInfo[zone];
        if (info) {
            alert(`${info.nombre}\n\nLocalidades: ${info.localidades}\nTarifa: ${info.tarifa}\nTiempo estimado: ${info.tiempo}`);
        }
    }
    
    // Smooth scroll para los enlaces internos (si se agregan en el futuro)
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
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
    
    // Animación de entrada para las tarjetas de servicio
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    entry.target.style.transition = 'all 0.6s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observar las tarjetas de servicio
    document.querySelectorAll('.service-card').forEach(card => {
        observer.observe(card);
    });
    
    // Observar las tarjetas de contacto
    document.querySelectorAll('.contact-item').forEach(item => {
        observer.observe(item);
    });
    
    // Validación básica para futuros formularios
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function validatePhone(phone) {
        const re = /^[0-9]{7,10}$/;
        return re.test(phone.replace(/\s/g, ''));
    }
    
    // Función para copiar información de contacto al portapapeles
    const contactItems = document.querySelectorAll('.contact-item p');
    contactItems.forEach(item => {
        item.style.cursor = 'pointer';
        item.title = 'Click para copiar';
        
        item.addEventListener('click', function() {
            const text = this.textContent;
            navigator.clipboard.writeText(text).then(() => {
                // Feedback visual
                const originalColor = this.style.color;
                this.style.color = '#5cb85c';
                this.style.fontWeight = 'bold';
                
                setTimeout(() => {
                    this.style.color = originalColor;
                    this.style.fontWeight = 'normal';
                }, 500);
                
                // Mostrar mensaje temporal
                showTemporaryMessage('¡Copiado al portapapeles!');
            }).catch(err => {
                console.error('Error al copiar:', err);
            });
        });
    });
    
    // Función para mostrar mensajes temporales
    function showTemporaryMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.textContent = message;
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #5cb85c;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                document.body.removeChild(messageDiv);
            }, 300);
        }, 2000);
    }
    
    // Agregar estilos de animación para los mensajes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    console.log('EcoBikeMess - Sistema cargado correctamente ✓');
});