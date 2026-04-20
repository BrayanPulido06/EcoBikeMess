document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('mensajero-send-package');

    const COSTO_FIJO = 8000;
    const form = document.getElementById('envioForm');
    const valorRecaudoInput = document.getElementById('valor_recaudo');
    const valorRecaudoHidden = document.getElementById('valor_recaudo_hidden');
    const costoTotalHidden = document.getElementById('costoTotalHidden');
    const tieneRecaudo = document.getElementById('tiene_recaudo');
    const containerSumarEnvio = document.getElementById('container_sumar_envio');
    const previewTotalRecaudo = document.getElementById('preview_total_recaudo');

    const headerTitle = document.querySelector('.envio-mensajero-header h1');
    if (headerTitle) {
        headerTitle.textContent = 'Crear Nuevo EnvÃ­o';
    }

    const headerDescription = document.querySelector('.envio-mensajero-header p');
    if (headerDescription) {
        headerDescription.textContent = 'Registra un paquete desde la operaciÃ³n de mensajerÃ­a y genera su guÃ­a.';
    }

    if (form && !document.getElementById('remitente_direccion')) {
        const hiddenDireccion = document.createElement('input');
        hiddenDireccion.type = 'hidden';
        hiddenDireccion.id = 'remitente_direccion';
        hiddenDireccion.name = 'remitente_direccion';
        hiddenDireccion.value = '';
        form.appendChild(hiddenDireccion);
    }

    const getBaseRecaudo = () => {
        const raw = String(valorRecaudoInput?.value || '');
        return parseInt(raw.replace(/[^\d]/g, ''), 10) || 0;
    };

    const seleccionarRadioPorDefecto = () => {
        if (!containerSumarEnvio) return;
        const seleccionado = document.querySelector('input[name="envio_destinatario"]:checked');
        if (seleccionado) return;
        const radioNo = document.querySelector('input[name="envio_destinatario"][value="no"]');
        if (radioNo) {
            radioNo.checked = true;
        }
    };

    const aplicarCostoFijo = () => {
        const costoBase = document.getElementById('costoBase');
        const costoTotal = document.getElementById('costoTotal');
        const recargoDimensiones = document.getElementById('recargoDimensiones');
        const recargoMismoDia = document.getElementById('recargoMismoDia');
        const recargoZona = document.getElementById('recargoZona');
        const recargoCambios = document.getElementById('recargoCambios');
        const valorRecaudoDisplay = document.getElementById('valorRecaudoDisplay');

        if (costoBase) costoBase.textContent = '$8.000';
        if (costoTotal) costoTotal.textContent = '$8.000';
        if (costoTotalHidden) costoTotalHidden.value = String(COSTO_FIJO);
        if (recargoDimensiones) recargoDimensiones.textContent = '$0';
        if (recargoMismoDia) recargoMismoDia.textContent = '$0';
        if (recargoZona) recargoZona.textContent = '$0';
        if (recargoCambios) recargoCambios.textContent = '$0';
        if (valorRecaudoDisplay) valorRecaudoDisplay.textContent = '$0';

        if (containerSumarEnvio) {
            containerSumarEnvio.style.display = (tieneRecaudo && tieneRecaudo.checked) ? 'block' : 'none';
        }

        if (tieneRecaudo && !tieneRecaudo.checked) {
            if (previewTotalRecaudo) {
                previewTotalRecaudo.style.display = 'none';
                previewTotalRecaudo.innerHTML = '';
            }
            return;
        }

        seleccionarRadioPorDefecto();

        const baseRecaudo = getBaseRecaudo();
        const radioSeleccionado = document.querySelector('input[name="envio_destinatario"]:checked');
        const sumaEnvio = radioSeleccionado && radioSeleccionado.value === 'si';
        const recaudoFinal = sumaEnvio ? (baseRecaudo + COSTO_FIJO) : baseRecaudo;

        if (valorRecaudoHidden) {
            valorRecaudoHidden.value = String(recaudoFinal);
        }

        if (previewTotalRecaudo) {
            previewTotalRecaudo.style.display = 'block';
            previewTotalRecaudo.innerHTML = `Total a cobrar al destinatario: <span style="font-size: 1.2em;">$${recaudoFinal.toLocaleString('es-CO')}</span>`;
        }
    };

    const programarAplicacion = () => {
        window.setTimeout(aplicarCostoFijo, 0);
    };

    [
        'dimensiones_paquete',
        'envio_mismo_dia',
        'zona_periferica',
        'recoger_cambios',
        'tiene_recaudo',
        'valor_recaudo'
    ].forEach((id) => {
        const element = document.getElementById(id);
        if (!element) return;
        element.addEventListener('change', programarAplicacion);
        element.addEventListener('input', programarAplicacion);
        element.addEventListener('click', programarAplicacion);
    });

    document.querySelectorAll('input[name="envio_destinatario"]').forEach((radio) => {
        radio.addEventListener('change', programarAplicacion);
        radio.addEventListener('click', programarAplicacion);
    });

    const btnNext = document.getElementById('btnNext');
    if (btnNext) {
        btnNext.addEventListener('click', () => {
            window.setTimeout(aplicarCostoFijo, 0);
        });
    }

    aplicarCostoFijo();
});
