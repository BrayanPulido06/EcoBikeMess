document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('mensajero-send-package');

    const COSTO_FIJO = 8000;
    const form = document.getElementById('envioForm');
    const btnNext = document.getElementById('btnNext');
    const valorRecaudoInput = document.getElementById('valor_recaudo');
    const valorRecaudoHidden = document.getElementById('valor_recaudo_hidden');
    const costoTotalHidden = document.getElementById('costoTotalHidden');
    const tieneRecaudo = document.getElementById('tiene_recaudo');
    const containerSumarEnvio = document.getElementById('container_sumar_envio');
    const previewTotalRecaudo = document.getElementById('preview_total_recaudo');
    const qrcodeContainer = document.getElementById('qrcode');
    let qrFallbackInstance = null;

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

    const forzarCostoFijoEnResumen = () => {
        const costoTotal = document.getElementById('costoTotal');
        const costoBase = document.getElementById('costoBase');

        if (costoBase && costoBase.textContent !== '$8.000') {
            costoBase.textContent = '$8.000';
        }
        if (costoTotal && costoTotal.textContent !== '$8.000') {
            costoTotal.textContent = '$8.000';
        }
        if (costoTotalHidden && costoTotalHidden.value !== String(COSTO_FIJO)) {
            costoTotalHidden.value = String(COSTO_FIJO);
        }
    };

    const generarQRFallback = () => {
        if (!qrcodeContainer || typeof window.QRCodeStyling !== 'function') return;
        if (qrcodeContainer.querySelector('canvas, svg')) return;

        const numeroGuia = document.getElementById('numeroGuia')?.textContent?.trim();
        if (!numeroGuia) return;

        qrcodeContainer.innerHTML = '';
        qrFallbackInstance = new window.QRCodeStyling({
            width: 220,
            height: 220,
            data: numeroGuia,
            dotsOptions: {
                color: '#000000',
                type: 'square'
            },
            backgroundOptions: {
                color: '#ffffff'
            },
            qrOptions: {
                errorCorrectionLevel: 'M'
            }
        });
        qrFallbackInstance.append(qrcodeContainer);
    };

    const programarVerificacionQR = () => {
        [100, 250, 500].forEach((delay) => {
            window.setTimeout(generarQRFallback, delay);
        });
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

        forzarCostoFijoEnResumen();
    };

    const programarAplicacion = () => {
        [0, 80, 180, 320].forEach((delay) => {
            window.setTimeout(aplicarCostoFijo, delay);
        });
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

    if (btnNext) {
        btnNext.addEventListener('click', () => {
            programarAplicacion();
            programarVerificacionQR();
        });
    }

    const costoTotal = document.getElementById('costoTotal');
    if (costoTotal) {
        const observerCosto = new MutationObserver(() => {
            forzarCostoFijoEnResumen();
        });
        observerCosto.observe(costoTotal, { childList: true, subtree: true, characterData: true });
    }

    const numeroGuia = document.getElementById('numeroGuia');
    if (numeroGuia) {
        const observerGuia = new MutationObserver(() => {
            programarVerificacionQR();
        });
        observerGuia.observe(numeroGuia, { childList: true, subtree: true, characterData: true });
    }

    if (qrcodeContainer) {
        const observerQR = new MutationObserver(() => {
            if (!qrcodeContainer.querySelector('canvas, svg')) {
                programarVerificacionQR();
            }
        });
        observerQR.observe(qrcodeContainer, { childList: true, subtree: true });
    }

    aplicarCostoFijo();
    programarAplicacion();
    programarVerificacionQR();
});
