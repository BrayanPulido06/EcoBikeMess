document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('mensajero-send-package');
    window.ECOBIKE_MENSAJERO_FIXED_COST = true;

    const fixedCost = 8000;
    const fixedCostText = `$${fixedCost.toLocaleString('es-CO')}`;

    const forceFixedCostUI = () => {
        const costoBase = document.getElementById('costoBase');
        const recargoDimensiones = document.getElementById('recargoDimensiones');
        const recargoMismoDia = document.getElementById('recargoMismoDia');
        const recargoZona = document.getElementById('recargoZona');
        const recargoCambios = document.getElementById('recargoCambios');
        const valorRecaudoDisplay = document.getElementById('valorRecaudoDisplay');
        const costoTotal = document.getElementById('costoTotal');
        const costoTotalHidden = document.getElementById('costoTotalHidden');
        const previewRecaudo = document.getElementById('preview_total_recaudo');
        const radios = document.querySelectorAll('input[name="envio_destinatario"]');

        if (costoBase) costoBase.textContent = fixedCostText;
        if (recargoDimensiones) recargoDimensiones.textContent = '$0';
        if (recargoMismoDia) recargoMismoDia.textContent = '$0';
        if (recargoZona) recargoZona.textContent = '$0';
        if (recargoCambios) recargoCambios.textContent = '$0';
        if (valorRecaudoDisplay) valorRecaudoDisplay.textContent = '$0';
        if (costoTotal) costoTotal.textContent = fixedCostText;
        if (costoTotalHidden) costoTotalHidden.value = String(fixedCost);
        if (previewRecaudo) {
            previewRecaudo.style.display = 'block';
            previewRecaudo.textContent = `Total a cobrar al destinatario: ${fixedCostText}`;
        }

        radios.forEach((radio) => {
            if (radio.value === 'no') {
                radio.checked = true;
            }
        });

        document.querySelectorAll('.radio-card').forEach((card) => {
            const radio = card.querySelector('input[type="radio"]');
            if (!radio) return;
            card.classList.toggle('selected', radio.checked);
        });
    };

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

    const patchExistingListeners = () => {
        document.querySelectorAll('input, select, textarea, button').forEach((element) => {
            element.addEventListener('change', forceFixedCostUI);
            element.addEventListener('click', forceFixedCostUI);
        });
    };

    patchExistingListeners();
    forceFixedCostUI();
    setTimeout(forceFixedCostUI, 0);
    setTimeout(forceFixedCostUI, 250);
    setInterval(forceFixedCostUI, 500);
});
