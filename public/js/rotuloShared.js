(function () {
    const DEFAULTS = {
        contactPhone: '3123180619',
        logoSrc: '../../public/img/Logo_Circulo_Fondoblanco.png',
        filePrefix: 'Guia'
    };

    function firstNonEmpty(...values) {
        for (const value of values) {
            if (value === null || value === undefined) continue;
            const text = String(value).trim();
            if (text !== '') return text;
        }
        return '';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatMoney(value) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0
        }).format(Number(value) || 0);
    }

    function normalizeYesNo(value, fallback = 'No') {
        if (typeof value === 'boolean') return value ? 'Si' : 'No';
        const text = String(value ?? '').trim().toLowerCase();
        if (!text) return fallback;
        if (['si', 'sí', 'true', '1', 'x', 'yes'].includes(text)) return 'Si';
        if (['no', 'false', '0'].includes(text)) return 'No';
        return String(value).trim();
    }

    function normalizeData(raw = {}, options = {}) {
        const tiendaNombre = firstNonEmpty(
            raw.tienda_nombre,
            raw.nombre_tienda,
            raw.nombre_emprendimiento,
            raw.remitente_tienda,
            raw.remitente_nombre,
            'Tienda'
        );

        return {
            guia: firstNonEmpty(raw.guia, raw.numero_guia, 'N/A'),
            tienda_nombre: tiendaNombre,
            persona_nombre: firstNonEmpty(raw.persona_nombre, raw.nombre_persona, raw.remitente_nombre, ''),
            destinatario_nombre: firstNonEmpty(raw.destinatario_nombre, raw.nombre_destinatario, 'Cliente'),
            destinatario_direccion: firstNonEmpty(raw.destinatario_direccion, raw.direccion_destino, ''),
            destinatario_telefono: firstNonEmpty(raw.destinatario_telefono, raw.telefono_destinatario, ''),
            destinatario_observaciones: firstNonEmpty(
                raw.destinatario_observaciones,
                raw.instrucciones_entrega,
                'Sin observaciones'
            ),
            cambios: normalizeYesNo(raw.cambios ?? raw.recoger_cambios, 'No'),
            recaudo: Number(raw.recaudo ?? raw.total_cobrar ?? raw.valor_recaudo ?? 0) || 0,
            contactPhone: firstNonEmpty(options.contactPhone, raw.contactPhone, DEFAULTS.contactPhone),
            logoSrc: firstNonEmpty(options.logoSrc, raw.logoSrc, DEFAULTS.logoSrc)
        };
    }

    function applyPreviewStyles(container) {
        container.style.background = '#ffffff';
        container.style.border = '1px solid #cccccc';
        container.style.fontFamily = 'Arial, sans-serif';
        container.style.color = '#333333';
        container.style.width = '100mm';
        container.style.height = '100mm';
        container.style.padding = '1mm 2mm 2mm 3mm';
        container.style.boxSizing = 'border-box';
        container.style.position = 'relative';
        container.style.overflow = 'hidden';
    }

    function buildInnerHtml(data) {
        return `
            <div class="rotulo-scale" style="transform:scale(0.72);transform-origin:top left;width:139mm;height:139mm;">
                <table style="width:100%;border-bottom:2px solid #64c46a;padding-bottom:6px;border-collapse:collapse;">
                    <tr>
                        <td colspan="2">
                            <div style="display:flex;align-items:center;justify-content:center;gap:18px;text-align:left;">
                                <img src="${escapeHtml(data.logoSrc)}" alt="EcoBikeMess" style="width:96px;height:96px;object-fit:contain;">
                                <div>
                                    <div style="font-size:26px;font-weight:800;color:#56bb5d;line-height:1;">EcoBikeMess</div>
                                    <div style="margin-top:4px;font-size:15px;font-weight:700;color:#28a745;">Contactanos: ${escapeHtml(data.contactPhone)}</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top:6px;">
                            <div style="font-size:13px;font-weight:800;color:#000000;">
                                NUM GUIA: <span style="font-size:19px;font-weight:800;color:#1f2a37;">${escapeHtml(data.guia)}</span>
                            </div>
                        </td>
                    </tr>
                </table>

                <table style="width:100%;margin-top:4px;font-size:12px;table-layout:fixed;border-collapse:separate;border-spacing:0;">
                    <tr>
                        <td style="width:48%;vertical-align:top;border:1px solid #ececec;padding:6px;border-radius:8px;">
                            <h3 style="margin:0 0 6px;font-size:17px;font-weight:800;color:#30363d;">Destinatario</h3>
                            <p style="margin:1px 0;line-height:1.02;overflow-wrap:anywhere;"><strong style="font-size:14px;font-weight:800;">Direccion:</strong> <span style="font-size:14px;font-weight:700;">${escapeHtml(data.destinatario_direccion)}</span></p>
                            <p style="margin:1px 0;line-height:1.02;overflow-wrap:anywhere;"><strong style="font-size:14px;font-weight:800;">Nombre:</strong> <span style="font-size:14px;font-weight:700;">${escapeHtml(data.destinatario_nombre)}</span></p>
                            <p style="margin:1px 0;line-height:1.02;overflow-wrap:anywhere;"><strong style="font-size:14px;font-weight:800;">Telefono:</strong> <span style="font-size:14px;font-weight:700;">${escapeHtml(data.destinatario_telefono)}</span></p>
                            <p style="margin:1px 0;line-height:1.02;overflow-wrap:anywhere;"><strong style="font-size:14px;font-weight:800;">Observaciones:</strong> <span style="font-size:14px;font-weight:700;">${escapeHtml(data.destinatario_observaciones)}</span></p>
                        </td>
                        <td style="width:4%;"></td>
                        <td style="width:48%;vertical-align:top;border:1px solid #ececec;padding:6px;border-radius:8px;">
                            <h3 style="margin:0 0 6px;font-size:17px;font-weight:800;color:#30363d;">Remitente</h3>
                            <p style="margin:1px 0;line-height:1.02;overflow-wrap:anywhere;"><strong style="font-size:14px;font-weight:800;">Tienda:</strong> <span style="font-size:14px;font-weight:700;">${escapeHtml(data.tienda_nombre)}</span></p>
                        </td>
                    </tr>
                </table>

                <div style="display:flex;align-items:flex-start;width:100%;margin-top:4px;">
                    <div style="flex:1 1 auto;min-width:0;max-width:calc(100% - 140px);position:relative;padding-right:2px;">
                        <div style="border:1px solid #ececec;padding:4px 6px;border-radius:8px;">
                            <h3 style="margin:0 0 6px;font-size:17px;font-weight:800;color:#30363d;">Detalles del Paquete</h3>
                            <p style="margin:1px 0;line-height:1.02;overflow-wrap:anywhere;"><strong style="font-size:14px;font-weight:800;">Cambios por recoger:</strong> <span style="font-size:14px;font-weight:700;">${escapeHtml(data.cambios)}</span></p>
                        </div>
                        <div style="margin-top:4px;">
                            <h3 style="margin:0 0 6px;font-size:17px;font-weight:800;color:#30363d;">Total a Cobrar</h3>
                            <p style="margin:0;font-size:58px;font-weight:900;color:#28a745;line-height:0.82;text-align:center;letter-spacing:0.5px;">${escapeHtml(formatMoney(data.recaudo))}</p>
                        </div>
                    </div>
                    <div style="flex:0 0 132px;display:flex;justify-content:flex-start;align-items:flex-start;padding-top:0;margin-left:-12mm;">
                        <div style="display:flex;align-items:center;justify-content:center;width:132px;min-width:132px;height:132px;padding:2px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-sizing:border-box;overflow:hidden;margin-top:-6px;">
                            <div data-rotulo-qr style="display:flex;align-items:flex-start;justify-content:center;width:128px;height:128px;flex:0 0 128px;overflow:hidden;"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async function appendQrCode(slot, data) {
        if (!slot || typeof QRCodeStyling === 'undefined') return;
        slot.innerHTML = '';

        const qrCode = new QRCodeStyling({
            width: 128,
            height: 128,
            type: 'canvas',
            data: data.guia,
            dotsOptions: { color: '#000000', type: 'square' },
            backgroundOptions: { color: '#ffffff' },
            qrOptions: { errorCorrectionLevel: 'M' }
        });

        qrCode.append(slot);
        await new Promise(resolve => setTimeout(resolve, 60));
    }

    async function mountPreview(container, rawData, options = {}) {
        if (!container) return null;

        const data = normalizeData(rawData, options);
        applyPreviewStyles(container);
        container.innerHTML = buildInnerHtml(data);
        await appendQrCode(container.querySelector('[data-rotulo-qr]'), data);
        return data;
    }

    async function renderToCanvas(rawData, options = {}) {
        if (typeof html2canvas === 'undefined') {
            throw new Error('html2canvas no está disponible');
        }

        const temp = document.createElement('div');
        temp.id = 'rotuloPreview';
        temp.style.position = 'absolute';
        temp.style.left = '-9999px';
        temp.style.top = '0';
        document.body.appendChild(temp);

        try {
            await mountPreview(temp, rawData, options);
            return await html2canvas(temp, { scale: 2, backgroundColor: '#ffffff', useCORS: true });
        } finally {
            if (temp.parentNode) temp.parentNode.removeChild(temp);
        }
    }

    function buildFileName(data, options = {}) {
        const prefix = firstNonEmpty(options.filePrefix, DEFAULTS.filePrefix);
        const guide = String(data.guia || 'SIN-GUIA').replace(/[^\w-]/g, '_');
        return `${prefix}_${guide}.pdf`;
    }

    async function downloadPdf(rawData, options = {}) {
        if (!window.jspdf?.jsPDF) {
            throw new Error('jsPDF no está disponible');
        }

        const data = normalizeData(rawData, options);
        const canvas = await renderToCanvas(data, options);
        const pdf = new window.jspdf.jsPDF('p', 'mm', [100, 100]);
        const imgData = canvas.toDataURL('image/png');
        pdf.addImage(imgData, 'PNG', 0, 0, 100, 100);
        pdf.save(options.fileName || buildFileName(data, options));
        return data;
    }

    window.RotuloEcoBike = {
        normalizeData,
        formatMoney,
        mountPreview,
        renderToCanvas,
        downloadPdf,
        buildFileName
    };
})();
