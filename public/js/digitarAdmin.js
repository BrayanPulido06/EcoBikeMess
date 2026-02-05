// Variables globales
let clientes = [];
let clienteSeleccionado = null;
let enviosHoy = [];
let ultimaGuia = null;

// Tarifas base
const TARIFAS = {
    baseNormal: 8000,
    baseUrgente: 15000,
    baseExpress: 25000,
    porKg: 2000,
    seguroPorcentaje: 0.02
};

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadInitialData();
});

// Inicializar aplicación
function initializeApp() {
    actualizarEstadisticas();
    calcularCosto();
    startPolling();
}

// Configurar event listeners
function setupEventListeners() {
    // Búsqueda de cliente
    document.getElementById('searchCliente').addEventListener('input', buscarCliente);
    document.getElementById('btnCambiarCliente').addEventListener('click', cambiarCliente);
    
    // Nuevo cliente
    document.getElementById('btnNuevoCliente').addEventListener('click', () => openModal('modalNuevoCliente'));
    document.getElementById('btnCerrarNuevoCliente').addEventListener('click', () => closeModal('modalNuevoCliente'));
    document.getElementById('btnCancelarNuevoCliente').addEventListener('click', () => closeModal('modalNuevoCliente'));
    document.getElementById('formNuevoCliente').addEventListener('submit', crearNuevoCliente);
    
    // Cálculo automático de costo
    document.getElementById('paquetePeso').addEventListener('input', calcularCosto);
    document.getElementById('paqueteValor').addEventListener('input', calcularCosto);
    document.getElementById('tipoServicio').addEventListener('change', calcularCosto);
    document.getElementById('aplicarDescuento').addEventListener('change', toggleDescuento);
    document.getElementById('descuentoPorcentaje').addEventListener('input', calcularCosto);
    
    // Formulario principal
    document.getElementById('formEnvio').addEventListener('submit', guardarEnvio);
    document.getElementById('btnCancelar').addEventListener('click', limpiarFormulario);
    document.getElementById('btnGuardarYNuevo').addEventListener('click', guardarYNuevo);
    
    // Etiqueta
    document.getElementById('btnPrevisualizarEtiqueta').addEventListener('click', previsualizarEtiqueta);
    document.getElementById('btnCerrarEtiqueta').addEventListener('click', () => closeModal('modalEtiqueta'));
    document.getElementById('btnCerrarEtiquetaBtn').addEventListener('click', () => closeModal('modalEtiqueta'));
    document.getElementById('btnImprimirEtiqueta').addEventListener('click', imprimirEtiqueta);
    
    // Click fuera del modal para cerrar
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
}

// Iniciar actualización automática de estadísticas
function startPolling() {
    setInterval(() => {
        // En un entorno real, aquí harías un fetch solo para obtener los contadores
        // fetch('api/stats.php').then(...)
        actualizarEstadisticas();
    }, 30000); // Actualizar cada 30 segundos
}

// Cargar datos iniciales
function loadInitialData() {
    // Aquí deberías cargar desde tu API
    // const response = await fetch('api/clientes.php');
    // clientes = await response.json();
    
    // Datos de ejemplo
    clientes = generateMockClientes();
    enviosHoy = [];
}

// Generar clientes de ejemplo
function generateMockClientes() {
    return [
        {
            id: 1,
            tipoDoc: 'NIT',
            documento: '900123456-7',
            nombre: 'Empresa ABC Ltda',
            telefono: '3001234567',
            email: 'contacto@abc.com',
            direccion: 'Calle 123 # 45-67',
            ciudad: 'Bogotá'
        },
        {
            id: 2,
            tipoDoc: 'CC',
            documento: '1234567890',
            nombre: 'Juan Pérez',
            telefono: '3009876543',
            email: 'juan@email.com',
            direccion: 'Carrera 7 # 12-34',
            ciudad: 'Bogotá'
        },
        {
            id: 3,
            tipoDoc: 'NIT',
            documento: '800987654-3',
            nombre: 'Comercializadora XYZ',
            telefono: '3005555555',
            email: 'ventas@xyz.com',
            direccion: 'Avenida 68 # 22-45',
            ciudad: 'Bogotá'
        }
    ];
}

// Buscar cliente
function buscarCliente() {
    const searchTerm = document.getElementById('searchCliente').value.toLowerCase();
    const resultados = document.getElementById('resultadosClientes');
    
    if (searchTerm.length < 2) {
        resultados.classList.remove('active');
        return;
    }
    
    const clientesFiltrados = clientes.filter(c => 
        c.nombre.toLowerCase().includes(searchTerm) ||
        c.documento.includes(searchTerm) ||
        c.telefono.includes(searchTerm)
    );
    
    if (clientesFiltrados.length > 0) {
        resultados.innerHTML = clientesFiltrados.map(c => `
            <div class="search-result-item" onclick="seleccionarCliente(${c.id})">
                <h4>${c.nombre}</h4>
                <p><strong>${c.tipoDoc}:</strong> ${c.documento}</p>
                <p>📞 ${c.telefono} | 📧 ${c.email || 'Sin email'}</p>
            </div>
        `).join('');
        resultados.classList.add('active');
    } else {
        resultados.innerHTML = '<div class="search-result-item" style="text-align: center; color: #999;">No se encontraron clientes</div>';
        resultados.classList.add('active');
    }
}

// Seleccionar cliente
function seleccionarCliente(clienteId) {
    const cliente = clientes.find(c => c.id === clienteId);
    if (!cliente) return;
    
    clienteSeleccionado = cliente;
    
    // Mostrar información del cliente
    document.getElementById('clienteNombre').textContent = cliente.nombre;
    document.getElementById('clienteNit').textContent = `${cliente.tipoDoc}: ${cliente.documento}`;
    document.getElementById('clienteTelefono').textContent = cliente.telefono;
    document.getElementById('clienteDireccion').textContent = cliente.direccion;
    document.getElementById('clienteEmail').textContent = cliente.email || 'No registrado';
    
    // Auto-llenar datos de remitente
    document.getElementById('remitenteNombre').value = cliente.nombre;
    document.getElementById('remitenteTelefono').value = cliente.telefono;
    document.getElementById('remitenteDireccion').value = cliente.direccion;
    
    // Mostrar card de cliente y ocultar búsqueda
    document.getElementById('datosCliente').classList.remove('hidden');
    document.getElementById('searchCliente').value = '';
    document.getElementById('resultadosClientes').classList.remove('active');
    
    // Mostrar botón "Guardar y Agregar Otro" para múltiples paquetes
    document.getElementById('btnGuardarYNuevo').style.display = 'inline-flex';
    
    actualizarEstadisticas();
}

// Cambiar cliente
function cambiarCliente() {
    clienteSeleccionado = null;
    document.getElementById('datosCliente').classList.add('hidden');
    document.getElementById('searchCliente').value = '';
    document.getElementById('searchCliente').focus();
    document.getElementById('btnGuardarYNuevo').style.display = 'none';
    
    // Limpiar datos de remitente
    document.getElementById('remitenteNombre').value = '';
    document.getElementById('remitenteTelefono').value = '';
    document.getElementById('remitenteDireccion').value = '';
}

// Crear nuevo cliente
function crearNuevoCliente(e) {
    e.preventDefault();
    
    const nuevoCliente = {
        id: clientes.length + 1,
        tipoDoc: document.getElementById('nuevoClienteTipoDoc').value,
        documento: document.getElementById('nuevoClienteDoc').value,
        nombre: document.getElementById('nuevoClienteNombre').value,
        telefono: document.getElementById('nuevoClienteTelefono').value,
        email: document.getElementById('nuevoClienteEmail').value,
        direccion: document.getElementById('nuevoClienteDireccion').value,
        ciudad: document.getElementById('nuevoClienteCiudad').value
    };
    
    // Aquí deberías guardar en tu API
    // await fetch('api/clientes.php', {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify(nuevoCliente)
    // });
    
    clientes.push(nuevoCliente);
    
    closeModal('modalNuevoCliente');
    document.getElementById('formNuevoCliente').reset();
    
    // Seleccionar el cliente recién creado
    seleccionarCliente(nuevoCliente.id);
    
    showNotification('Cliente creado exitosamente', 'success');
}

// Toggle descuento
function toggleDescuento() {
    const checkbox = document.getElementById('aplicarDescuento');
    const input = document.getElementById('descuentoPorcentaje');
    
    if (checkbox.checked) {
        input.disabled = false;
        input.focus();
    } else {
        input.disabled = true;
        input.value = 0;
    }
    
    calcularCosto();
}

// Calcular costo del envío
function calcularCosto() {
    const peso = parseFloat(document.getElementById('paquetePeso').value) || 0;
    const valorDeclarado = parseFloat(document.getElementById('paqueteValor').value) || 0;
    const tipoServicio = document.getElementById('tipoServicio').value;
    const descuentoPorcentaje = parseFloat(document.getElementById('descuentoPorcentaje').value) || 0;
    
    // Costo base según tipo de servicio
    let costoBase = 0;
    switch(tipoServicio) {
        case 'normal':
            costoBase = TARIFAS.baseNormal;
            break;
        case 'urgente':
            costoBase = TARIFAS.baseUrgente;
            break;
        case 'express':
            costoBase = TARIFAS.baseExpress;
            break;
    }
    
    // Recargo por peso (por cada kg adicional después del primero)
    const recargoPeso = peso > 1 ? Math.ceil(peso - 1) * TARIFAS.porKg : 0;
    
    // Seguro (2% del valor declarado)
    const seguro = valorDeclarado * TARIFAS.seguroPorcentaje;
    
    // Subtotal
    const subtotal = costoBase + recargoPeso + seguro;
    
    // Descuento
    const descuento = subtotal * (descuentoPorcentaje / 100);
    
    // Total
    const total = subtotal - descuento;
    
    // Actualizar UI
    document.getElementById('costoBase').textContent = formatCurrency(costoBase);
    document.getElementById('costoRecargo').textContent = formatCurrency(recargoPeso);
    document.getElementById('costoSeguro').textContent = formatCurrency(seguro);
    document.getElementById('costoDescuento').textContent = formatCurrency(descuento);
    document.getElementById('costoTotal').textContent = formatCurrency(total);
}

// Generar número de guía
function generarNumeroGuia() {
    const fecha = new Date();
    const año = fecha.getFullYear().toString().substr(-2);
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    const consecutivo = String(enviosHoy.length + 1).padStart(4, '0');
    
    return `GU${año}${mes}${dia}${consecutivo}`;
}

// Validar formulario
function validarFormulario() {
    const errores = [];
    
    if (!clienteSeleccionado) {
        errores.push('Debe seleccionar un cliente');
    }
    
    if (!document.getElementById('destinatarioNombre').value) {
        errores.push('El nombre del destinatario es obligatorio');
    }
    
    if (!document.getElementById('destinatarioTelefono').value) {
        errores.push('El teléfono del destinatario es obligatorio');
    }
    
    if (!document.getElementById('destinatarioDireccion').value) {
        errores.push('La dirección de entrega es obligatoria');
    }
    
    if (!document.getElementById('destinatarioZona').value) {
        errores.push('Debe seleccionar una zona de entrega');
    }
    
    if (!document.getElementById('paqueteDescripcion').value) {
        errores.push('La descripción del paquete es obligatoria');
    }
    
    if (!document.getElementById('paqueteTipo').value) {
        errores.push('Debe seleccionar el tipo de paquete');
    }
    
    if (!document.getElementById('paquetePeso').value || parseFloat(document.getElementById('paquetePeso').value) <= 0) {
        errores.push('El peso del paquete debe ser mayor a 0');
    }
    
    if (!document.getElementById('tipoServicio').value) {
        errores.push('Debe seleccionar el tipo de servicio');
    }
    
    if (!document.getElementById('formaPago').value) {
        errores.push('Debe seleccionar la forma de pago');
    }
    
    return errores;
}

// Guardar envío
function guardarEnvio(e) {
    e.preventDefault();
    
    const errores = validarFormulario();
    
    if (errores.length > 0) {
        showNotification(errores.join('\n'), 'error');
        return;
    }
    
    const numeroGuia = generarNumeroGuia();
    const costoTotal = document.getElementById('costoTotal').textContent;
    
    const envio = {
        id: enviosHoy.length + 1,
        numeroGuia: numeroGuia,
        fecha: new Date().toISOString(),
        cliente: clienteSeleccionado,
        remitente: {
            nombre: document.getElementById('remitenteNombre').value,
            telefono: document.getElementById('remitenteTelefono').value,
            direccion: document.getElementById('remitenteDireccion').value
        },
        destinatario: {
            nombre: document.getElementById('destinatarioNombre').value,
            telefono: document.getElementById('destinatarioTelefono').value,
            direccion: document.getElementById('destinatarioDireccion').value,
            ciudad: document.getElementById('destinatarioCiudad').value,
            zona: document.getElementById('destinatarioZona').value,
            referencia: document.getElementById('destinatarioReferencia').value
        },
        paquete: {
            descripcion: document.getElementById('paqueteDescripcion').value,
            tipo: document.getElementById('paqueteTipo').value,
            cantidad: document.getElementById('paqueteCantidad').value,
            peso: document.getElementById('paquetePeso').value,
            alto: document.getElementById('paqueteAlto').value,
            ancho: document.getElementById('paqueteAncho').value,
            largo: document.getElementById('paqueteLargo').value,
            valorDeclarado: document.getElementById('paqueteValor').value,
            instrucciones: document.getElementById('paqueteInstrucciones').value
        },
        servicio: {
            tipo: document.getElementById('tipoServicio').value,
            formaPago: document.getElementById('formaPago').value,
            quienPaga: document.getElementById('quienPaga').value
        },
        costo: costoTotal,
        estado: 'pendiente'
    };
    
    // Aquí deberías guardar en tu API
    // await fetch('api/envios.php', {
    //     method: 'POST',
    //     headers: { 'Content-Type': 'application/json' },
    //     body: JSON.stringify(envio)
    // });
    
    enviosHoy.push(envio);
    ultimaGuia = numeroGuia;
    
    // Generar y mostrar etiqueta
    generarEtiqueta(envio);
    openModal('modalEtiqueta');
    
    showNotification(`Envío registrado exitosamente. Guía: ${numeroGuia}`, 'success');
    actualizarEstadisticas();
}

// Guardar y crear nuevo
function guardarYNuevo() {
    const form = document.getElementById('formEnvio');
    const event = new Event('submit', { bubbles: true, cancelable: true });
    
    if (form.dispatchEvent(event)) {
        // Limpiar solo campos del paquete y destinatario, mantener cliente
        document.getElementById('destinatarioNombre').value = '';
        document.getElementById('destinatarioTelefono').value = '';
        document.getElementById('destinatarioDireccion').value = '';
        document.getElementById('destinatarioCiudad').value = 'Bogotá';
        document.getElementById('destinatarioZona').value = '';
        document.getElementById('destinatarioReferencia').value = '';
        
        document.getElementById('paqueteDescripcion').value = '';
        document.getElementById('paqueteTipo').value = '';
        document.getElementById('paqueteCantidad').value = '1';
        document.getElementById('paquetePeso').value = '';
        document.getElementById('paqueteAlto').value = '';
        document.getElementById('paqueteAncho').value = '';
        document.getElementById('paqueteLargo').value = '';
        document.getElementById('paqueteValor').value = '';
        document.getElementById('paqueteInstrucciones').value = '';
        
        calcularCosto();
        
        document.getElementById('destinatarioNombre').focus();
    }
}

// Previsualizar etiqueta
function previsualizarEtiqueta() {
    const errores = validarFormulario();
    
    if (errores.length > 0) {
        showNotification('Complete todos los campos obligatorios para previsualizar la etiqueta', 'error');
        return;
    }
    
    const envioTemp = {
        numeroGuia: 'PREVIEW-' + Date.now(),
        fecha: new Date().toISOString(),
        cliente: clienteSeleccionado,
        remitente: {
            nombre: document.getElementById('remitenteNombre').value,
            telefono: document.getElementById('remitenteTelefono').value,
            direccion: document.getElementById('remitenteDireccion').value
        },
        destinatario: {
            nombre: document.getElementById('destinatarioNombre').value,
            telefono: document.getElementById('destinatarioTelefono').value,
            direccion: document.getElementById('destinatarioDireccion').value,
            ciudad: document.getElementById('destinatarioCiudad').value,
            zona: document.getElementById('destinatarioZona').value
        },
        paquete: {
            descripcion: document.getElementById('paqueteDescripcion').value,
            peso: document.getElementById('paquetePeso').value
        },
        servicio: {
            tipo: document.getElementById('tipoServicio').value
        },
        costo: document.getElementById('costoTotal').textContent
    };
    
    generarEtiqueta(envioTemp);
    openModal('modalEtiqueta');
}

// Generar etiqueta
function generarEtiqueta(envio) {
    const container = document.getElementById('etiquetaPreview');
    
    const html = `
        <div class="etiqueta-header">
            <h1>MENSAJERÍA EXPRESS</h1>
            <p>www.mensajeria.com | Tel: (601) 555-5555</p>
        </div>

        <div class="etiqueta-guia">${envio.numeroGuia}</div>

        <div class="etiqueta-section">
            <h3>REMITENTE</h3>
            <p><strong>Nombre:</strong> ${envio.remitente.nombre}</p>
            <p><strong>Teléfono:</strong> ${envio.remitente.telefono}</p>
            <p><strong>Dirección:</strong> ${envio.remitente.direccion}</p>
        </div>

        <div class="etiqueta-section">
            <h3>DESTINATARIO</h3>
            <p><strong>Nombre:</strong> ${envio.destinatario.nombre}</p>
            <p><strong>Teléfono:</strong> ${envio.destinatario.telefono}</p>
            <p><strong>Dirección:</strong> ${envio.destinatario.direccion}</p>
            <p><strong>Ciudad:</strong> ${envio.destinatario.ciudad} - Zona: ${envio.destinatario.zona.toUpperCase()}</p>
        </div>

        <div class="etiqueta-section">
            <h3>DETALLES DEL ENVÍO</h3>
            <p><strong>Descripción:</strong> ${envio.paquete.descripcion}</p>
            <p><strong>Peso:</strong> ${envio.paquete.peso} kg</p>
            <p><strong>Servicio:</strong> ${formatTipoServicio(envio.servicio.tipo)}</p>
            <p><strong>Valor:</strong> ${envio.costo}</p>
            <p><strong>Fecha:</strong> ${formatDateTime(envio.fecha)}</p>
        </div>

        <div class="etiqueta-qr">
            <div id="qrcode"></div>
        </div>

        <div class="etiqueta-footer">
            <p>Firma y sello de recibido: _______________________</p>
            <p>Este documento es prueba de recepción del envío</p>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Generar código QR
    document.getElementById('qrcode').innerHTML = '';
    new QRCode(document.getElementById('qrcode'), {
        text: envio.numeroGuia,
        width: 150,
        height: 150
    });
}

// Imprimir etiqueta
function imprimirEtiqueta() {
    window.print();
}

// Limpiar formulario
function limpiarFormulario() {
    if (confirm('¿Está seguro de que desea cancelar? Se perderán todos los datos ingresados.')) {
        document.getElementById('formEnvio').reset();
        cambiarCliente();
        calcularCosto();
    }
}

// Actualizar estadísticas
function actualizarEstadisticas() {
    document.getElementById('enviosHoy').textContent = enviosHoy.length;
    
    const totalFacturado = enviosHoy.reduce((sum, envio) => {
        const valor = parseFloat(envio.costo.replace(/[^0-9]/g, ''));
        return sum + valor;
    }, 0);
    
    document.getElementById('totalFacturado').textContent = formatCurrency(totalFacturado);
    document.getElementById('ultimoCliente').textContent = clienteSeleccionado ? clienteSeleccionado.nombre : '-';
}

// Formatear tipo de servicio
function formatTipoServicio(tipo) {
    const servicios = {
        'normal': 'Normal (24-48h)',
        'urgente': 'Urgente (Mismo día)',
        'express': 'Express (2-4h)'
    };
    return servicios[tipo] || tipo;
}

// Formatear moneda
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
}

// Formatear fecha y hora
function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Abrir modal
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

// Cerrar modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Mostrar notificación
function showNotification(message, type = 'info') {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        info: '#17a2b8',
        warning: '#ffc107'
    };
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        max-width: 400px;
        white-space: pre-line;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}