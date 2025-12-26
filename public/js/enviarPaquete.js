document.addEventListener('DOMContentLoaded', function() {
    
    let currentStep = 1;
    const totalSteps = 4;
    
    // Elementos del DOM
    const form = document.getElementById('envioForm');
    const btnNext = document.getElementById('btnNext');
    const btnPrevious = document.getElementById('btnPrevious');
    const btnSubmit = document.getElementById('btnSubmit');
    const autoFillBtn = document.getElementById('autoFillRemitente');
    const tieneRecaudo = document.getElementById('tiene_recaudo');
    const calcularCostoBtn = document.getElementById('calcularCosto');
    
    // Tarifas base por zona
    const tarifasZona = {
        'Norte': 8000,
        'Centro': 6000,
        'Sur': 7500,
        'Occidente': 7000
    };
    
    // Recargos por tipo
    const recargosTipo = {
        'normal': 0,
        'fragil': 2000,
        'urgente': 5000
    };
    
    // ============================================
    // NAVEGACIÓN ENTRE PASOS
    // ============================================
    
    function showStep(step) {
        // Ocultar todos los pasos
        document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
        
        // Mostrar paso actual
        const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
        if (stepElement) {
            stepElement.classList.add('active');
        }
        
        // Actualizar indicador
        document.querySelectorAll('.step').forEach((s, index) => {
            s.classList.remove('active', 'completed');
            if (index + 1 < step) {
                s.classList.add('completed');
            } else if (index + 1 === step) {
                s.classList.add('active');
            }
        });
        
        // Mostrar/ocultar botones
        btnPrevious.style.display = step === 1 ? 'none' : 'inline-block';
        btnNext.style.display = step === totalSteps ? 'none' : 'inline-block';
        btnSubmit.style.display = step === totalSteps ? 'inline-block' : 'none';
        
        // Scroll al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    btnNext.addEventListener('click', function() {
        if (validateCurrentStep()) {
            if (currentStep === 3) {
                // Antes de ir al paso 4, mostrar resumen
                mostrarResumen();
            }
            currentStep++;
            showStep(currentStep);
        }
    });
    
    btnPrevious.addEventListener('click', function() {
        currentStep--;
        showStep(currentStep);
    });
    
    // ============================================
    // VALIDACIÓN
    // ============================================
    
    function validateCurrentStep() {
        const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
        const inputs = currentStepElement.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            clearError(input);
            
            if (!input.value.trim()) {
                showError(input, 'Este campo es obligatorio');
                isValid = false;
            } else {
                // Validaciones específicas
                if (input.type === 'email' && !validateEmail(input.value)) {
                    showError(input, 'Email no válido');
                    isValid = false;
                }
                if (input.type === 'tel' && !validatePhone(input.value)) {
                    showError(input, 'Teléfono no válido (7-10 dígitos)');
                    isValid = false;
                }
                if (input.type === 'number') {
                    const value = parseFloat(input.value);
                    const min = parseFloat(input.getAttribute('min'));
                    const max = parseFloat(input.getAttribute('max'));
                    
                    if (min && value < min) {
                        showError(input, `Valor mínimo: ${min}`);
                        isValid = false;
                    }
                    if (max && value > max) {
                        showError(input, `Valor máximo: ${max}`);
                        isValid = false;
                    }
                }
            }
        });
        
        // Validar dimensiones
        if (currentStep === 3) {
            const largo = parseFloat(document.getElementById('dimension_largo').value);
            const ancho = parseFloat(document.getElementById('dimension_ancho').value);
            const alto = parseFloat(document.getElementById('dimension_alto').value);
            
            if (largo > 50 || ancho > 40 || alto > 30) {
                alert('Las dimensiones exceden el máximo permitido (50x40x30 cm)');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function validatePhone(phone) {
        return /^[0-9]{7,10}$/.test(phone.replace(/\s/g, ''));
    }
    
    function showError(input, message) {
        const errorSpan = input.parentElement.querySelector('.error-message');
        if (errorSpan) {
            errorSpan.textContent = message;
            input.style.borderColor = '#dc3545';
        }
    }
    
    function clearError(input) {
        const errorSpan = input.parentElement.querySelector('.error-message');
        if (errorSpan) {
            errorSpan.textContent = '';
            input.style.borderColor = '';
        }
    }
    
    // ============================================
    // AUTO-RELLENAR DATOS DEL REMITENTE
    // ============================================
    
    if (autoFillBtn) {
        autoFillBtn.addEventListener('click', function() {
            // Datos de ejemplo del usuario (en producción vendrían de la sesión)
            document.getElementById('remitente_nombre').value = 'Juan Pérez';
            document.getElementById('remitente_telefono').value = '300 123 4567';
            document.getElementById('remitente_email').value = 'juan@example.com';
            document.getElementById('remitente_direccion').value = 'Calle 123 #45-67, Apto 301';
            document.getElementById('remitente_ciudad').value = 'Bogotá';
            document.getElementById('remitente_zona').value = 'Centro';
            
            // Animación de confirmación
            this.textContent = '✓ Datos cargados';
            this.style.color = '#28a745';
            setTimeout(() => {
                this.textContent = 'Usar mis datos';
                this.style.color = '';
            }, 2000);
        });
    }
    
    // ============================================
    // MANEJO DE RECAUDO
    // ============================================
    
    if (tieneRecaudo) {
        tieneRecaudo.addEventListener('change', function() {
            const recaudoField = document.querySelector('.recaudo-field');
            if (this.checked) {
                recaudoField.style.display = 'block';
                document.getElementById('valor_recaudo').required = true;
            } else {
                recaudoField.style.display = 'none';
                document.getElementById('valor_recaudo').required = false;
                document.getElementById('valor_recaudo').value = '';
            }
        });
    }
    
    // ============================================
    // CALCULAR COSTO
    // ============================================
    
    function calcularCostoEnvio() {
        // Obtener valores
        const zonaOrigen = document.getElementById('remitente_zona').value;
        const zonaDestino = document.getElementById('destinatario_zona').value;
        const peso = parseFloat(document.getElementById('peso_paquete').value) || 0;
        const tipoPaquete = document.getElementById('tipo_paquete').value;
        const valorRecaudo = tieneRecaudo.checked ? (parseFloat(document.getElementById('valor_recaudo').value) || 0) : 0;
        
        // Validar que hay datos suficientes
        if (!zonaDestino || peso === 0 || !tipoPaquete) {
            alert('Por favor complete todos los campos requeridos antes de calcular el costo');
            return;
        }
        
        // Calcular costo base
        const costoBase = tarifasZona[zonaDestino] || 6000;
        
        // Calcular recargo por peso (si excede 5kg)
        let recargoPeso = 0;
        if (peso > 5) {
            recargoPeso = Math.ceil(peso - 5) * 1000; // $1000 por kg adicional
        }
        
        // Recargo por tipo
        const recargoTipo = recargosTipo[tipoPaquete] || 0;
        
        // Total
        const total = costoBase + recargoPeso + recargoTipo;
        
        // Mostrar desglose
        document.getElementById('costoBase').textContent = '$' + costoBase.toLocaleString('es-CO');
        document.getElementById('recargoPeso').textContent = '$' + recargoPeso.toLocaleString('es-CO');
        document.getElementById('recargoTipo').textContent = '$' + recargoTipo.toLocaleString('es-CO');
        document.getElementById('valorRecaudoDisplay').textContent = '$' + valorRecaudo.toLocaleString('es-CO');
        document.getElementById('costoTotal').textContent = '$' + total.toLocaleString('es-CO');
        
        // Animar el total
        const totalElement = document.getElementById('costoTotal');
        totalElement.style.transform = 'scale(1.1)';
        setTimeout(() => {
            totalElement.style.transform = 'scale(1)';
        }, 300);
        
        return total;
    }
    
    if (calcularCostoBtn) {
        calcularCostoBtn.addEventListener('click', calcularCostoEnvio);
    }
    
    // Calcular automáticamente cuando cambian los valores
    ['remitente_zona', 'destinatario_zona', 'peso_paquete', 'tipo_paquete', 'valor_recaudo'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', () => {
                if (currentStep === 3) {
                    calcularCostoEnvio();
                }
            });
        }
    });
    
    // ============================================
    // GENERAR NÚMERO DE GUÍA
    // ============================================
    
    function generarNumeroGuia() {
        const fecha = new Date();
        const año = fecha.getFullYear();
        const numero = Math.floor(Math.random() * 90000) + 10000;
        return `ECO-${año}-${numero}`;
    }
    
    // ============================================
    // MOSTRAR RESUMEN
    // ============================================
    
    function mostrarResumen() {
        // Remitente
        document.getElementById('confirm_remitente_nombre').textContent = 
            document.getElementById('remitente_nombre').value;
        document.getElementById('confirm_remitente_telefono').textContent = 
            document.getElementById('remitente_telefono').value;
        document.getElementById('confirm_remitente_direccion').textContent = 
            `${document.getElementById('remitente_direccion').value}, ${document.getElementById('remitente_zona').value}`;
        
        // Destinatario
        document.getElementById('confirm_destinatario_nombre').textContent = 
            document.getElementById('destinatario_nombre').value;
        document.getElementById('confirm_destinatario_telefono').textContent = 
            document.getElementById('destinatario_telefono').value;
        document.getElementById('confirm_destinatario_direccion').textContent = 
            `${document.getElementById('destinatario_direccion').value}, ${document.getElementById('destinatario_zona').value}`;
        
        // Paquete
        document.getElementById('confirm_descripcion').textContent = 
            document.getElementById('descripcion_contenido').value;
        document.getElementById('confirm_peso').textContent = 
            document.getElementById('peso_paquete').value + ' kg';
        
        const largo = document.getElementById('dimension_largo').value;
        const ancho = document.getElementById('dimension_ancho').value;
        const alto = document.getElementById('dimension_alto').value;
        document.getElementById('confirm_dimensiones').textContent = 
            `${largo} x ${ancho} x ${alto} cm`;
        
        const tipoPaquete = document.getElementById('tipo_paquete').value;
        const tipoTexto = {
            'normal': 'Normal',
            'fragil': 'Frágil',
            'urgente': 'Urgente'
        };
        document.getElementById('confirm_tipo').textContent = tipoTexto[tipoPaquete];
        
        // Total
        const costoTotal = document.getElementById('costoTotal').textContent;
        document.getElementById('confirm_total').textContent = costoTotal;
        
        // Generar número de guía
        const numeroGuia = generarNumeroGuia();
        document.getElementById('numeroGuia').textContent = numeroGuia;
    }
    
    // ============================================
    // ENVIAR FORMULARIO
    // ============================================
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateCurrentStep()) {
            return;
        }
        
        // Recopilar todos los datos
        const formData = new FormData(form);
        const numeroGuia = document.getElementById('numeroGuia').textContent;
        formData.append('numero_guia', numeroGuia);
        formData.append('costo_total', document.getElementById('costoTotal').textContent.replace(/[$.,]/g, ''));
        formData.append('fecha_creacion', new Date().toISOString());
        formData.append('estado', 'pendiente');
        
        // Mostrar datos en consola (en producción se enviaría al servidor)
        console.log('Datos del envío:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        // Simular envío al servidor
        btnSubmit.textContent = 'Procesando...';
        btnSubmit.disabled = true;
        
        setTimeout(() => {
            // Éxito
            alert(`¡Envío creado exitosamente!\n\nNúmero de Guía: ${numeroGuia}\n\nPodrás rastrear tu paquete en la sección "Mis Pedidos"`);
            
            // Redirigir a la página de pedidos
            window.location.href = 'misPedidos.php';
        }, 2000);
    });
    
    // ============================================
    // VALIDACIÓN EN TIEMPO REAL
    // ============================================
    
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                return; // No mostrar error en blur si está vacío
            }
            
            if (this.type === 'email' && this.value && !validateEmail(this.value)) {
                showError(this, 'Email no válido');
            } else if (this.type === 'tel' && this.value && !validatePhone(this.value)) {
                showError(this, 'Teléfono no válido');
            } else {
                clearError(this);
            }
        });
        
        input.addEventListener('input', function() {
            if (this.parentElement.querySelector('.error-message').textContent) {
                clearError(this);
            }
        });
    });
    
    // ============================================
    // ANIMACIONES DE ENTRADA
    // ============================================
    
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Inicializar
    showStep(1);
    
    console.log('Sistema de envío de paquetes cargado ✓');
});