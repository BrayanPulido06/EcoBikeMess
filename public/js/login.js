// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del DOM
    const loginForm = document.getElementById('loginForm');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    
    // Toggle para mostrar/ocultar contraseñas
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            
            if (input.type === 'password') {
                input.type = 'text';
                this.querySelector('.eye-icon').textContent = '👁️‍🗨️';
            } else {
                input.type = 'password';
                this.querySelector('.eye-icon').textContent = '👁️';
            }
        });
    });
    
    // Validación de Email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Función para mostrar mensajes de éxito
    function showSuccessMessage(message) {
        // Crear elemento de mensaje si no existe
        let successDiv = document.querySelector('.success-message');
        if (!successDiv) {
            successDiv = document.createElement('div');
            successDiv.className = 'success-message';
            const activeForm = document.querySelector('.form-container.active');
            if (activeForm) {
                activeForm.insertBefore(successDiv, activeForm.firstChild);
            }
        }
        
        successDiv.textContent = message;
        successDiv.classList.add('show');
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            successDiv.classList.remove('show');
        }, 5000);
    }
    
    // ============================================
    // MANEJO DEL FORMULARIO DE LOGIN
    // ============================================
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            // Limpiar errores previos
            document.getElementById('loginEmailError').textContent = '';
            document.getElementById('loginPasswordError').textContent = '';
            
            let isValid = true;
            
            // Validar email
            if (!validateEmail(email)) {
                document.getElementById('loginEmailError').textContent = 'Por favor ingresa un email válido';
                isValid = false;
            }
            
            // Validar contraseña
            if (password.length === 0) {
                document.getElementById('loginPasswordError').textContent = 'Por favor ingresa tu contraseña';
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault(); // Solo detenemos el envío si hay errores de validación (campos vacíos o email inválido)
            }
        });
        
        // Validación en tiempo real para email
        document.getElementById('loginEmail').addEventListener('blur', function() {
            if (this.value && !validateEmail(this.value)) {
                document.getElementById('loginEmailError').textContent = 'Email no válido';
            } else {
                document.getElementById('loginEmailError').textContent = '';
            }
        });
        
        // Pre-llenar email si viene de registro
        const urlParams = new URLSearchParams(window.location.search);
        const emailParam = urlParams.get('email');
        if (emailParam && document.getElementById('loginEmail')) {
            document.getElementById('loginEmail').value = emailParam;
        }
    }
    
    // ============================================
    // MANEJO DEL FORMULARIO DE RECUPERACIÓN
    // ============================================
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('forgotEmail').value;
            
            // Limpiar errores previos
            document.getElementById('forgotEmailError').textContent = '';
            
            let isValid = true;
            
            // Validar email
            if (!validateEmail(email)) {
                document.getElementById('forgotEmailError').textContent = 'Por favor ingresa un email válido';
                isValid = false;
            }
            
            if (isValid) {
                // Aquí iría la lógica de recuperación de contraseña
                console.log('Recuperación de contraseña para:', email);
                showSuccessMessage('¡Enlace de recuperación enviado! Revisa tu correo electrónico.');
                
                // Volver al login después de 3 segundos
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
            }
        });
    }
    
    console.log('Sistema de autenticación EcoBikeMess cargado ✓');
});