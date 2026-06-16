document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('mensajero-send-package');

    const headerTitle = document.querySelector('.envio-mensajero-header h1');
    if (headerTitle) {
        headerTitle.textContent = 'Crear Nuevo Envio';
    }

    const headerDescription = document.querySelector('.envio-mensajero-header p');
    if (headerDescription) {
        headerDescription.textContent = 'Registra un paquete desde la operacion de mensajeria y genera su guia.';
    }

    const costoBaseLabel = document.querySelector('.cost-breakdown .cost-item span');
    if (costoBaseLabel) {
        costoBaseLabel.textContent = 'Costo fijo de mensajeria:';
    }

    const tiendaLabel = document.querySelector('label[for="remitente_tienda"]');
    if (tiendaLabel) {
        tiendaLabel.textContent = 'Origen operativo';
    }

    const tiendaInput = document.getElementById('remitente_tienda');
    if (tiendaInput && !tiendaInput.value.trim()) {
        tiendaInput.value = 'Operativo Mensajero';
    }
});
