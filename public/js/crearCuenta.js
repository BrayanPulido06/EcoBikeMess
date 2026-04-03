document.addEventListener('DOMContentLoaded', function() {
    const typeBtns = document.querySelectorAll('.type-btn');
    const registerForm = document.getElementById('registerForm');
    const camposCliente = document.getElementById('camposCliente');
    const camposMensajero = document.getElementById('camposMensajero');
    const tipoUsuarioInput = document.getElementById('tipoUsuario');
    const tipoTransporteSelect = document.getElementById('mensajero_tipo_transporte');
    const vehiculoFields = document.getElementById('vehiculoFields');
    const fotoInput = document.getElementById('mensajero_foto');
    const fotoPreviewContainer = document.getElementById('foto-preview-container');
    const fotoPreviewImg = document.getElementById('foto-preview-img');

    typeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tipo = this.getAttribute('data-type');

            typeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            tipoUsuarioInput.value = tipo;

            if (tipo === 'cliente') {
                camposCliente.style.display = 'block';
                camposMensajero.style.display = 'none';
            } else {
                camposMensajero.style.display = 'block';
                camposCliente.style.display = 'none';
            }
        });
    });

    if (tipoTransporteSelect) {
        tipoTransporteSelect.addEventListener('change', function() {
            const valor = this.value;
            if (valor === 'moto' || valor === 'vehiculo') {
                vehiculoFields.style.display = 'block';
                document.getElementById('mensajero_placa').required = true;
                document.getElementById('mensajero_licencia').required = true;
                document.getElementById('mensajero_soat').required = true;
                document.getElementById('mensajero_tecnomecanica').required = true;
            } else {
                vehiculoFields.style.display = 'none';
                document.getElementById('mensajero_placa').required = false;
                document.getElementById('mensajero_licencia').required = false;
                document.getElementById('mensajero_soat').required = false;
                document.getElementById('mensajero_tecnomecanica').required = false;
            }
        });
    }

    if (fotoInput) {
        fotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(loadEvent) {
                    fotoPreviewImg.src = loadEvent.target.result;
                    fotoPreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                fotoPreviewContainer.style.display = 'none';
            }
        });
    }

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

    function setupCustomFileInput(inputId) {
        const input = document.getElementById(inputId);
        const fileNameDisplay = document.getElementById(`file-name-${inputId}`);

        if (input && fileNameDisplay) {
            input.addEventListener('change', function() {
                fileNameDisplay.textContent = this.files && this.files.length > 0
                    ? this.files[0].name
                    : 'Ningún archivo seleccionado';
            });
        }
    }

    setupCustomFileInput('mensajero_foto');
    setupCustomFileInput('mensajero_licencia');
    setupCustomFileInput('mensajero_soat');
    setupCustomFileInput('mensajero_tecnomecanica');

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validatePhone(phone) {
        return /^[0-9]{7,10}$/.test((phone || '').replace(/\s/g, ''));
    }

    function validatePassword(password) {
        return (password || '').length >= 8;
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
        return file.size <= maxSizeMB * 1024 * 1024;
    }

    function validateFileType(file, allowedTypes) {
        if (!file) return true;
        const fileName = file.name.toLowerCase();
        return allowedTypes.some(type => file.type === type || fileName.endsWith(type));
    }

    function getErrorContainer(input) {
        if (!input) return null;
        const formGroup = input.closest('.form-group');
        return formGroup ? formGroup.querySelector('.error-message') : null;
    }

    const passwordInputs = [
        { input: document.getElementById('password'), strength: document.querySelector('.password-strength') }
    ];

    passwordInputs.forEach(({ input, strength }) => {
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

    function setupRealtimeValidation(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const emailInput = form.querySelector('input[type="email"]');
        const phoneInputs = form.querySelectorAll('input[type="tel"]');
        const passwordInput = form.querySelector('input[name="password"]');
        const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');

        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const errorSpan = getErrorContainer(this);
                if (this.value && !validateEmail(this.value)) {
                    if (errorSpan) errorSpan.textContent = 'Email no válido';
                    this.style.borderColor = '#dc3545';
                } else {
                    if (errorSpan) errorSpan.textContent = '';
                    this.style.borderColor = '';
                }
            });
        }

        phoneInputs.forEach(input => {
            input.addEventListener('blur', function() {
                const errorSpan = getErrorContainer(this);
                if (this.value && !validatePhone(this.value)) {
                    if (errorSpan) errorSpan.textContent = 'Teléfono no válido (7-10 dígitos)';
                    this.style.borderColor = '#dc3545';
                } else {
                    if (errorSpan) errorSpan.textContent = '';
                    this.style.borderColor = '';
                }
            });
        });

        if (confirmPasswordInput && passwordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const errorSpan = getErrorContainer(this);
                if (this.value && this.value !== passwordInput.value) {
                    if (errorSpan) errorSpan.textContent = 'Las contraseñas no coinciden';
                    this.style.borderColor = '#dc3545';
                } else {
                    if (errorSpan) errorSpan.textContent = '';
                    this.style.borderColor = '';
                }
            });
        }
    }

    setupRealtimeValidation('registerForm');

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            clearFormErrors(this);

            const formData = new FormData(this);
            const tipoUsuario = formData.get('tipo_usuario');
            let isValid = true;

            const email = formData.get('correo');
            const telefono = formData.get('telefono');
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            const terms = document.getElementById('terms').checked;

            if (!validateEmail(email)) {
                showError(document.getElementById('correo'), 'Email no válido');
                isValid = false;
            }

            if (!validatePhone(telefono)) {
                showError(document.getElementById('telefono'), 'Teléfono no válido');
                isValid = false;
            }

            if (!validatePassword(password)) {
                showError(document.getElementById('password'), 'La contraseña debe tener al menos 8 caracteres');
                isValid = false;
            }

            if (password !== confirmPassword) {
                showError(document.getElementById('confirm_password'), 'Las contraseñas no coinciden');
                isValid = false;
            }

            if (!terms) {
                alert('Debes aceptar los términos y condiciones');
                isValid = false;
            }

            if (tipoUsuario === 'cliente') {
                const nombreEmp = document.getElementById('cliente_nombre_emprendimiento').value;
                if (!nombreEmp.trim()) {
                    showError(document.getElementById('cliente_nombre_emprendimiento'), 'Requerido');
                    isValid = false;
                }
            } else if (tipoUsuario === 'mensajero') {
                const tipoDocumento = formData.get('tipo_documento');
                const numeroDocumento = formData.get('numDocumento');
                const tipoSangre = formData.get('tipo_sangre');
                const direccionResidencia = formData.get('direccion_residencia');
                const nombreEmergencia1 = formData.get('nombre_emergencia1');
                const apellidoEmergencia1 = formData.get('apellido_emergencia1');
                const telEmergencia1 = formData.get('telefono_emergencia1');
                const nombreEmergencia2 = formData.get('nombre_emergencia2');
                const apellidoEmergencia2 = formData.get('apellido_emergencia2');
                const telEmergencia2 = formData.get('telefono_emergencia2');
                const tipoTransporte = document.getElementById('mensajero_tipo_transporte').value;
                const foto = document.getElementById('mensajero_foto').files[0];

                if (!tipoDocumento) {
                    showError(document.getElementById('mensajero_tipo_documento'), 'Selecciona el tipo de documento');
                    isValid = false;
                }

                if (!numeroDocumento || !numeroDocumento.trim()) {
                    showError(document.getElementById('mensajero_numDocumento'), 'El número de documento es requerido');
                    isValid = false;
                }

                if (!tipoSangre) {
                    showError(document.getElementById('mensajero_tipo_sangre'), 'Selecciona el tipo de sangre');
                    isValid = false;
                }

                if (!direccionResidencia || !direccionResidencia.trim()) {
                    showError(document.getElementById('mensajero_direccion'), 'La dirección de residencia es requerida');
                    isValid = false;
                }

                if (!foto) {
                    showError(document.getElementById('mensajero_foto'), 'La foto es requerida');
                    isValid = false;
                } else if (!validateFileSize(foto, 2)) {
                    showError(document.getElementById('mensajero_foto'), 'La foto no debe superar 2MB');
                    isValid = false;
                } else if (!validateFileType(foto, ['image/jpeg', 'image/png', 'image/webp', '.jpg', '.jpeg', '.png', '.webp'])) {
                    showError(document.getElementById('mensajero_foto'), 'La foto debe ser JPG, PNG o WEBP');
                    isValid = false;
                }

                if (!nombreEmergencia1 || !nombreEmergencia1.trim()) {
                    showError(document.getElementById('mensajero_nombre_emergencia1'), 'El nombre es requerido');
                    isValid = false;
                }

                if (!apellidoEmergencia1 || !apellidoEmergencia1.trim()) {
                    showError(document.getElementById('mensajero_apellido_emergencia1'), 'El apellido es requerido');
                    isValid = false;
                }

                if (!validatePhone(telEmergencia1)) {
                    showError(document.getElementById('mensajero_telefono_emergencia1'), 'Teléfono no válido');
                    isValid = false;
                }

                if (!nombreEmergencia2 || !nombreEmergencia2.trim()) {
                    showError(document.getElementById('mensajero_nombre_emergencia2'), 'El nombre es requerido');
                    isValid = false;
                }

                if (!apellidoEmergencia2 || !apellidoEmergencia2.trim()) {
                    showError(document.getElementById('mensajero_apellido_emergencia2'), 'El apellido es requerido');
                    isValid = false;
                }

                if (!validatePhone(telEmergencia2)) {
                    showError(document.getElementById('mensajero_telefono_emergencia2'), 'Teléfono no válido');
                    isValid = false;
                }

                if (!tipoTransporte) {
                    showError(document.getElementById('mensajero_tipo_transporte'), 'Selecciona el tipo de transporte');
                    isValid = false;
                }

                if (tipoTransporte === 'moto' || tipoTransporte === 'vehiculo') {
                    const placa = document.getElementById('mensajero_placa').value;
                    const licencia = document.getElementById('mensajero_licencia').files[0];
                    const soat = document.getElementById('mensajero_soat').files[0];
                    const tecnomecanica = document.getElementById('mensajero_tecnomecanica').files[0];
                    const allowedDocTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', '.pdf', '.jpg', '.jpeg', '.png', '.webp'];

                    if (!placa.trim()) {
                        showError(document.getElementById('mensajero_placa'), 'La placa es requerida');
                        isValid = false;
                    }

                    if (!licencia) {
                        showError(document.getElementById('mensajero_licencia'), 'La licencia es requerida');
                        isValid = false;
                    } else if (!validateFileSize(licencia, 2) || !validateFileType(licencia, allowedDocTypes)) {
                        showError(document.getElementById('mensajero_licencia'), 'La licencia debe ser PDF o imagen y no superar 2MB');
                        isValid = false;
                    }

                    if (!soat) {
                        showError(document.getElementById('mensajero_soat'), 'El SOAT es requerido');
                        isValid = false;
                    } else if (!validateFileSize(soat, 2) || !validateFileType(soat, allowedDocTypes)) {
                        showError(document.getElementById('mensajero_soat'), 'El SOAT debe ser PDF o imagen y no superar 2MB');
                        isValid = false;
                    }

                    if (!tecnomecanica) {
                        showError(document.getElementById('mensajero_tecnomecanica'), 'La tecnomecánica es requerida');
                        isValid = false;
                    } else if (!validateFileSize(tecnomecanica, 2) || !validateFileType(tecnomecanica, allowedDocTypes)) {
                        showError(document.getElementById('mensajero_tecnomecanica'), 'La tecnomecánica debe ser PDF o imagen y no superar 2MB');
                        isValid = false;
                    }
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    function showError(input, message) {
        const errorSpan = getErrorContainer(input);
        if (errorSpan) {
            errorSpan.textContent = message;
        }
        if (input) {
            input.style.borderColor = '#dc3545';
        }
    }

    function clearFormErrors(form) {
        form.querySelectorAll('.error-message').forEach(span => {
            span.textContent = '';
        });

        form.querySelectorAll('input, select').forEach(input => {
            input.style.borderColor = '';
        });
    }

    function showSuccessAndRedirect(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message show';
        successDiv.textContent = message;

        const activeForm = document.querySelector('.register-form.active');
        activeForm.insertBefore(successDiv, activeForm.firstChild);

        window.scrollTo({ top: 0, behavior: 'smooth' });

        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3000);
    }

    console.log('Sistema de registro EcoBikeMess cargado ✓');
});
