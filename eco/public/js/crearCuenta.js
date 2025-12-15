document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del DOM
    const typeBtns = document.querySelectorAll('.type-btn');
    const formCliente = document.getElementById('formCliente');
    const formMensajero = document.getElementById('formMensajero');
    const tipoTransporteSelect = document.getElementById('mensajero_tipo_transporte');
    const vehiculoFields = document.getElementById('vehiculoFields');
    
    // ============================================
    // CAMBIAR ENTRE TIPO DE USUARIO
    // ============================================
    typeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tipo = this.getAttribute('data-type');
            
            // Actualizar botones
            typeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Mostrar formulario correspondiente
            if (tipo === 'cliente') {
                formCliente.classList.add('active');
                formMensajero.classList.remove('active');
            } else {
                formMensajero.classList.add('active');
                formCliente.classList.remove('active');
            }
        });
    });
    
    // ============================================
    // MOSTRAR CAMPOS DE VEHÍCULO
    // ============================================
    if (tipoTransporteSelect) {
        tipoTransporteSelect.addEventListener('change', function() {
            const valor = this.value;
            if (valor === 'moto' || valor === 'vehiculo') {
                vehiculoFields.style.display = 'block';
                // Hacer campos requeridos
                document.getElementById('mensajero_placa').required = true;
                document.getElementById('mensajero_licencia').required = true;
                document.getElementById('mensajero_soat').required = true;
            } else {
                vehiculoFields.style.display = 'none';
                // Quitar requerido
                document.getElementById('mensajero_placa').required = false;
                document.getElementById('mensajero_licencia').required = false;
                document.getElementById('mensajero_soat').required = false;
            }
        });
    }
    
    // ============================================
    // TOGGLE CONTRASEÑAS
    // ============================================
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
    
    // ============================================
    // VALIDACIONES
    // ============================================
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function validatePhone(phone) {
        const re = /^[0-9]{7,10}$/;
        return re.test(phone.replace(/\s/g, ''));
    }
    
    function validatePassword(password) {
        return password.length >= 8;
    }
    
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        return strength;
    }
    
    function validateFileSize(file, maxSizeMB) {
        if (!file) return true;
        const maxSize = maxSizeMB * 1024 * 1024; // Convertir a bytes
        return file.size <= maxSize;
    }
    
    function validateFileType(file, allowedTypes) {
        if (!file) return true;
        return allowedTypes.some(type => file.type.includes(type) || file.name.endsWith(type));
    }
    
    // ============================================
    // FORTALEZA DE CONTRASEÑA EN TIEMPO REAL
    // ============================================
    const passwordInputs = [
        { input: document.getElementById('cliente_password'), strength: document.querySelector('#formCliente .password-strength') },
        { input: document.getElementById('mensajero_password'), strength: document.querySelector('#formMensajero .password-strength') }
    ];
    
    passwordInputs.forEach(({input, strength}) => {
        if (input && strength) {
            input.addEventListener('input', function() {
                const level = checkPasswordStrength(this.value);
                
                if (this.value.length === 0) {
                    strength.textContent = '';
                    strength.className = 'password-strength';
                } else if (level <= 2) {
                    strength.textContent = '⚠️ Contraseña débil';
                    strength.className = 'password-strength weak';
                } else if (level <= 3) {
                    strength.textContent = '⚡ Contraseña media';
                    strength.className = 'password-strength medium';
                } else {
                    strength.textContent = '✓ Contraseña fuerte';
                    strength.className = 'password-strength strong';
                }
            });
        }
    });
    
    // ============================================
    // VALIDACIÓN EN TIEMPO REAL
    // ============================================
    function setupRealtimeValidation(formId) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        const emailInput = form.querySelector('input[type="email"]');
        const phoneInputs = form.querySelectorAll('input[type="tel"]');
        const passwordInput = form.querySelector('input[name="password"]');
        const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');
        
        // Validar email
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const errorSpan = this.nextElementSibling;
                if (this.value && !validateEmail(this.value)) {
                    errorSpan.textContent = 'Email no válido';
                    this.style.borderColor = '#dc3545';
                } else {
                    errorSpan.textContent = '';
                    this.style.borderColor = '';
                }
            });
        }
        
        // Validar teléfonos
        phoneInputs.forEach(input => {
            input.addEventListener('blur', function() {
                const errorSpan = this.nextElementSibling;
                if (this.value && !validatePhone(this.value)) {
                    errorSpan.textContent = 'Teléfono no válido (7-10 dígitos)';
                    this.style.borderColor = '#dc3545';
                } else {
                    errorSpan.textContent = '';
                    this.style.borderColor = '';
                }
            });
        });
        
        // Validar confirmación de contraseña
        if (confirmPasswordInput && passwordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const errorSpan = this.parentElement.nextElementSibling;
                if (this.value && this.value !== passwordInput.value) {
                    errorSpan.textContent = 'Las contraseñas no coinciden';
                    this.style.borderColor = '#dc3545';
                } else {
                    errorSpan.textContent = '';
                    this.style.borderColor = '';
                }
            });
        }
    }
    
    setupRealtimeValidation('formCliente');
    setupRealtimeValidation('formMensajero');
    
    // ============================================
    // ENVÍO FORMULARIO CLIENTE
    // ============================================
    if (formCliente) {
        formCliente.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Limpiar errores
            clearFormErrors(this);
            
            const formData = new FormData(this);
            let isValid = true;
            
            // Validar campos
            const email = formData.get('correo');
            const telefono = formData.get('telefono');
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            const terms = document.getElementById('cliente_terms').checked;
            
            if (!validateEmail(email)) {
                showError(document.getElementById('cliente_correo'), 'Email no válido');
                isValid = false;
            }
            
            if (!validatePhone(telefono)) {
                showError(document.getElementById('cliente_telefono'), 'Teléfono no válido');
                isValid = false;
            }
            
            if (!validatePassword(password)) {
                showError(document.getElementById('cliente_password'), 'La contraseña debe tener al menos 8 caracteres');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                showError(document.getElementById('cliente_confirm_password'), 'Las contraseñas no coinciden');
                isValid = false;
            }
            
            if (!terms) {
                alert('Debes aceptar los términos y condiciones');
                isValid = false;
            }
            
            if (isValid) {
                // Aquí enviarías los datos al servidor
                console.log('Datos del cliente:', Object.fromEntries(formData));
                showSuccessAndRedirect('¡Cuenta de cliente creada exitosamente!');
            }
        });
    }
    
    // ============================================
    // ENVÍO FORMULARIO MENSAJERO
    // ============================================
    if (formMensajero) {
        formMensajero.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Limpiar errores
            clearFormErrors(this);
            
            const formData = new FormData(this);
            let isValid = true;
            
            // Validar campos básicos
            const email = formData.get('correo');
            const telefono = formData.get('telefono');
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            const foto = document.getElementById('mensajero_foto').files[0];
            const hojaVida = document.getElementById('mensajero_hoja_vida').files[0];
            const terms = document.getElementById('mensajero_terms').checked;
            
            if (!validateEmail(email)) {
                showError(document.getElementById('mensajero_correo'), 'Email no válido');
                isValid = false;
            }
            
            if (!validatePhone(telefono)) {
                showError(document.getElementById('mensajero_telefono'), 'Teléfono no válido');
                isValid = false;
            }
            
            if (!validatePassword(password)) {
                showError(document.getElementById('mensajero_password'), 'La contraseña debe tener al menos 8 caracteres');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                showError(document.getElementById('mensajero_confirm_password'), 'Las contraseñas no coinciden');
                isValid = false;
            }
            
            // Validar foto
            if (!foto) {
                showError(document.getElementById('mensajero_foto'), 'La foto es requerida');
                isValid = false;
            } else if (!validateFileSize(foto, 2)) {
                showError(document.getElementById('mensajero_foto'), 'La foto no debe superar 2MB');
                isValid = false;
            } else if (!validateFileType(foto, ['image/'])) {
                showError(document.getElementById('mensajero_foto'), 'Formato no válido (solo imágenes)');
                isValid = false;
            }
            
            // Validar hoja de vida
            if (!hojaVida) {
                showError(document.getElementById('mensajero_hoja_vida'), 'La hoja de vida es requerida');
                isValid = false;
            } else if (!validateFileSize(hojaVida, 5)) {
                showError(document.getElementById('mensajero_hoja_vida'), 'El archivo no debe superar 5MB');
                isValid = false;
            } else if (!validateFileType(hojaVida, ['.pdf', 'application/pdf'])) {
                showError(document.getElementById('mensajero_hoja_vida'), 'Formato no válido (solo PDF)');
                isValid = false;
            }
            
            // Validar teléfonos de emergencia
            const telEmergencia1 = formData.get('telefono_emergencia1');
            const telEmergencia2 = formData.get('telefono_emergencia2');
            
            if (!validatePhone(telEmergencia1)) {
                showError(document.getElementById('mensajero_telefono_emergencia1'), 'Teléfono no válido');
                isValid = false;
            }
            
            if (!validatePhone(telEmergencia2)) {
                showError(document.getElementById('mensajero_telefono_emergencia2'), 'Teléfono no válido');
                isValid = false;
            }
            
            if (!terms) {
                alert('Debes aceptar los términos y condiciones');
                isValid = false;
            }
            
            if (isValid) {
                // Aquí enviarías los datos al servidor
                console.log('Datos del mensajero:', Object.fromEntries(formData));
                showSuccessAndRedirect('¡Solicitud de mensajero enviada! Te notificaremos cuando sea aprobada.');
            }
        });
    }
    
    // ============================================
    // FUNCIONES AUXILIARES
    // ============================================
    function showError(input, message) {
        const errorSpan = input.parentElement.querySelector('.error-message') || input.nextElementSibling;
        if (errorSpan) {
            errorSpan.textContent = message;
            input.style.borderColor = '#dc3545';
        }
    }
    
    function clearFormErrors(form) {
        const errorMessages = form.querySelectorAll('.error-message');
        errorMessages.forEach(span => span.textContent = '');
        
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => input.style.borderColor = '');
    }
    
    function showSuccessAndRedirect(message) {
        // Crear mensaje de éxito
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message show';
        successDiv.textContent = message;
        
        const activeForm = document.querySelector('.register-form.active');
        activeForm.insertBefore(successDiv, activeForm.firstChild);
        
        // Scroll hacia arriba
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Redirigir después de 3 segundos
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3000);
    }
    
    console.log('Sistema de registro EcoBikeMess cargado ✓');
});