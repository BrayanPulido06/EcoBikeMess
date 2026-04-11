document.addEventListener('DOMContentLoaded', function() {
    
    // Estado de la aplicación
    let scannedQRs = [];
    let sessionStartTime = new Date();
    let sessionTimer;
    let html5QrCode = null; // Variable para la instancia del escáner
    let scannerStartToken = 0;
    let isScannerStarting = false;
    let isScannerStopping = false;
    let lastScannedCode = null;
    let lastScannedTime = 0;
    let isFlashOn = false;
    let ubicacionActual = null;
    let watchId = null;
    const pendingValidations = new Set();
    const STORAGE_SCANNED_QR_KEY = 'ecobikemess_mensajero_scanned_qr_v1';
    const STORAGE_ROUTE_MODE_KEY = 'ecobikemess_mensajero_route_mode_v1';
    
    const API_INICIO_MENSAJERO = '../../controller/inicioMensajeroController.php';

    // Datos dinámicos
    let statsData = {
        entregadas: 0,
        pendientes: 0,
        ganancias: 0,
        kilometros: 0
    };
    
    let collectionsData = [];
    
    let deliveriesData = [];
    let routeDeliveriesData = [];
    let isRouteMode = false;
    
    // Elementos del DOM
    const menuBtn = document.getElementById('menuBtn');
    const sideMenu = document.getElementById('sideMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    const btnScanQR = document.getElementById('btnScanQR');
    const scanModal = document.getElementById('scanModal');
    const closeScanModal = document.getElementById('closeScanModal');
    const btnManualCode = document.getElementById('btnManualCode');
    const manualModal = document.getElementById('manualModal');
    const closeManualModal = document.getElementById('closeManualModal');
    const btnConfirmManual = document.getElementById('btnConfirmManual');
    const btnCancelManual = document.getElementById('btnCancelManual');
    const manualCodeInput = document.getElementById('manualCode');
    const qrCounter = document.getElementById('qrCounter');
    const scannedList = document.getElementById('scannedList');
    const deliverSection = document.getElementById('deliverSection');
    const btnDeliver = document.getElementById('btnDeliver');
    const deliverCount = document.getElementById('deliverCount');
    const btnResetCounter = document.getElementById('btnResetCounter');
    const routeDetailModal = document.getElementById('routeDetailModal');
    const closeRouteDetailModal = document.getElementById('closeRouteDetailModal');
    const btnCloseRouteDetail = document.getElementById('btnCloseRouteDetail');
    const routeDetailBody = document.getElementById('routeDetailBody');
    const collectionsBadge = document.getElementById('collectionsBadge');
    const collectionAsignadas = document.getElementById('collectionAsignadas');
    const collectionCompletadas = document.getElementById('collectionCompletadas');
    const collectionsList = document.getElementById('collectionsList');
    const deliveriesList = document.getElementById('deliveriesList');

    function guardarEstadoEscaneoLocal() {
        try {
            localStorage.setItem(STORAGE_SCANNED_QR_KEY, JSON.stringify(scannedQRs));
            localStorage.setItem(STORAGE_ROUTE_MODE_KEY, isRouteMode ? '1' : '0');
        } catch (error) {
            console.warn('No se pudo guardar estado local de escaneo', error);
        }
    }

    function normalizarItemEscaneado(item) {
        if (!item || !item.code) return null;

        const rawTimestamp = item.timestamp ? new Date(item.timestamp) : new Date();
        const safeDate = isNaN(rawTimestamp.getTime()) ? new Date() : rawTimestamp;
        const timeString = item.time || safeDate.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
        const dateString = item.date || safeDate.toLocaleDateString('es-CO');
        const dateTimeString = item.dateTime || `${dateString} ${timeString}`;

        return {
            code: String(item.code).trim().toUpperCase(),
            time: timeString,
            date: dateString,
            dateTime: dateTimeString,
            timestamp: safeDate.toISOString(),
            rawText: item.rawText || '',
            details: {
                nombre: item.details?.nombre || null,
                direccion: item.details?.direccion || null,
                remitente: item.details?.remitente || null,
                telefono: item.details?.telefono || null,
                total: item.details?.total || null,
                campos: item.details?.campos || {}
            }
        };
    }

    function cargarEstadoEscaneoLocal() {
        try {
            const savedScanned = localStorage.getItem(STORAGE_SCANNED_QR_KEY);
            const savedRouteMode = localStorage.getItem(STORAGE_ROUTE_MODE_KEY);

            if (savedScanned) {
                const parsed = JSON.parse(savedScanned);
                if (Array.isArray(parsed)) {
                    scannedQRs = parsed
                        .map(normalizarItemEscaneado)
                        .filter(Boolean);
                }
            }

            isRouteMode = savedRouteMode === '1';
        } catch (error) {
            console.warn('No se pudo cargar estado local de escaneo', error);
            scannedQRs = [];
            isRouteMode = false;
        }
    }
    
    // ============================================
    // MENÚ LATERAL
    // ============================================
    
    menuBtn.addEventListener('click', function() {
        sideMenu.classList.add('active');
        menuOverlay.classList.add('active');
    });
    
    menuOverlay.addEventListener('click', function() {
        sideMenu.classList.remove('active');
        menuOverlay.classList.remove('active');
    });
    
    // ============================================
    // TEMPORIZADOR DE SESIÓN
    // ============================================
    
    function updateSessionTime() {
        const now = new Date();
        const diff = now - sessionStartTime;
        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        const sessionTime = document.getElementById('sessionTime');
        if (sessionTime) sessionTime.textContent = timeString;
    }
    
    sessionTimer = setInterval(updateSessionTime, 1000);
    
    // ============================================
    // ESCANEAR QR
    // ============================================
    
    btnScanQR.addEventListener('click', function() {
        if (scanModal.classList.contains('active')) return;
        scanModal.classList.add('active');
        // Esperar a que el modal sea visible (display:flex + animación) antes de inicializar la cámara
        setTimeout(() => startScanning(), 250);
    });
    
    closeScanModal.addEventListener('click', function() {
        stopScanning().then(() => {
            scanModal.classList.remove('active');
        });
    });

    // ============================================
    // LÓGICA DE ESCANEO (HTML5-QRCODE)
    // ============================================

    function isHtml5QrCodeScanning(instance) {
        if (!instance) return false;
        try {
            if (typeof instance.isScanning === 'function') return !!instance.isScanning();
            return !!instance.isScanning;
        } catch (_) {
            return false;
        }
    }

    async function startScanning() {
        if (isScannerStarting) return;
        isScannerStarting = true;

        const currentToken = ++scannerStartToken;
        const readerEl = document.getElementById('reader');
        const modalCounterEl = document.getElementById('modalQrCounter');
        const btnFlash = document.getElementById('btnFlash');

        try {
            // UI inicial
            if (readerEl) readerEl.innerHTML = '<p style="padding:1rem;color:#6c757d;">Iniciando cámara...</p>';
            if (modalCounterEl) modalCounterEl.textContent = scannedQRs.length;

            // Resetear variables de control
            lastScannedCode = null;
            isFlashOn = false;
            if (btnFlash) btnFlash.style.display = 'none';

            // Si hay una instancia previa, detenerla limpiamente (sin cancelar este arranque)
            await stopScanning({ cancelPendingStart: false });
            if (currentToken !== scannerStartToken) return;

            // Validaciones de compatibilidad / contexto seguro (muy común en celulares por HTTP en IP local)
            const isSecure = (typeof window.isSecureContext === 'boolean') ? window.isSecureContext : false;
            const host = (location && location.hostname) ? location.hostname : '';
            const isLocalhost = host === 'localhost' || host === '127.0.0.1' || host === '::1';
            if (!isSecure && !isLocalhost) {
                if (readerEl) {
                    readerEl.innerHTML =
                        '<p style="color:#dc3545; padding:1rem;">La cámara solo funciona en HTTPS o en localhost. Abre el sistema con HTTPS (o desde localhost) y vuelve a intentar.</p>';
                }
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                if (readerEl) {
                    readerEl.innerHTML =
                        '<p style="color:#dc3545; padding:1rem;">Este navegador no soporta acceso a cámara (getUserMedia).</p>';
                }
                return;
            }

            // Config más estable: fps moderado (menos "traba" en equipos lentos) y sin aspectRatio fijo
            const config = {
                fps: 10,
                qrbox: { width: 220, height: 220 },
                disableFlip: true
            };

            // Dejar terminar la animación del modal antes de iniciar la cámara
            await new Promise(resolve => setTimeout(resolve, 150));
            if (currentToken !== scannerStartToken) return;

            html5QrCode = new Html5Qrcode("reader");

            // Preferir cámara trasera si la lista está disponible (más compatible que facingMode en algunos Android)
            let cameraIdOrConfig = { facingMode: { ideal: "environment" } };
            try {
                const cameras = await Html5Qrcode.getCameras();
                if (Array.isArray(cameras) && cameras.length > 0) {
                    const backCam = cameras.find(c => /back|rear|traser|environment/i.test(c.label || '')) || cameras[cameras.length - 1];
                    cameraIdOrConfig = backCam.id;
                }
            } catch (_) {
                // Si falla, se usa facingMode ideal
            }

            await html5QrCode.start(cameraIdOrConfig, config, onScanSuccess, onScanFailure);

            // Botón de flash: mostrarlo y dejar que el usuario pruebe (no todos los dispositivos lo soportan)
            if (btnFlash) {
                btnFlash.style.display = 'block';
                btnFlash.onclick = toggleFlash;
            }
        } catch (err) {
            console.error("Error iniciando cámara:", err);
            if (readerEl) {
                readerEl.innerHTML =
                    '<p style="color:#dc3545; padding:1rem;">No se pudo acceder a la cámara. Verifica permisos del navegador y que estés en HTTPS/localhost.</p>';
            }
        } finally {
            isScannerStarting = false;
        }
    }

    async function stopScanning(options = {}) {
        const cancelPendingStart = options.cancelPendingStart !== false;

        if (cancelPendingStart) scannerStartToken++;
        if (isScannerStopping) return;
        isScannerStopping = true;

        try {
            if (!html5QrCode) return;

            try {
                if (isHtml5QrCodeScanning(html5QrCode)) {
                    // No forzar apagar torch aquí: en algunos equipos puede "congelar" el track
                    isFlashOn = false;
                    await html5QrCode.stop();
                }
            } catch (err) {
                console.warn("Error al detener:", err);
            }

            try {
                html5QrCode.clear();
            } catch (_) {}

            html5QrCode = null;

            const readerEl = document.getElementById('reader');
            if (readerEl) readerEl.innerHTML = '';
        } finally {
            isScannerStopping = false;
        }
    }
    
    function toggleFlash() {
        if (html5QrCode) {
            isFlashOn = !isFlashOn;
            html5QrCode.applyVideoConstraints({
                advanced: [{ torch: isFlashOn }]
            }).catch(err => {
                console.warn("Flash no soportado o error al cambiar:", err);
                isFlashOn = !isFlashOn; // Revertir estado si falla
            });
        }
    }

    // Generar sonido de confirmación (Beep)
    function playScanSound(type = 'success') {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            
            if (type === 'success') {
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(1000, audioCtx.currentTime); // 1000Hz
                oscillator.frequency.exponentialRampToValueAtTime(500, audioCtx.currentTime + 0.1);
            } else {
                oscillator.type = 'sawtooth'; // Sonido más áspero para error
                oscillator.frequency.setValueAtTime(200, audioCtx.currentTime);
            }
            
            gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
            
            oscillator.start();
            oscillator.stop(audioCtx.currentTime + 0.2);
        } catch (e) {
            console.error("AudioContext no soportado", e);
        }
    }

    function extraerCodigoDesdeTexto(rawText) {
        if (!rawText) return null;
        const text = String(rawText).trim();
        if (!text) return null;

        // 1) Formato etiqueta: "Guía: EBM-2024-001" (o ECO legacy)
        const matchGuia = text.match(/(?:gu[ií]a)\s*[:#-]?\s*((?:EBM|ECO)-[A-Z0-9-]+)/i);
        if (matchGuia && matchGuia[1]) return matchGuia[1].toUpperCase();

        // 2) Guía directa en cualquier parte del texto
        const matchEco = text.match(/\b((?:EBM|ECO)-[A-Z0-9-]{3,})\b/i);
        if (matchEco && matchEco[1]) return matchEco[1].toUpperCase();

        // 3) Compatibilidad con códigos guardados como QR-EBM-XXX / QR-ECO-XXX
        const matchQrEco = text.match(/\b(QR-(?:EBM|ECO)-[A-Z0-9-]{2,})\b/i);
        if (matchQrEco && matchQrEco[1]) return matchQrEco[1].toUpperCase();

        return null;
    }

    function normalizarCodigoEscaneado(decodedText) {
        if (!decodedText) return null;
        const raw = String(decodedText).trim();
        if (!raw) return null;

        // A) Intentar como URL con parámetros (ej: ?guia=EBM-2024-001)
        try {
            const maybeUrl = new URL(raw);
            const keys = ['guia', 'numero_guia', 'codigo', 'code', 'qr_code'];
            for (const key of keys) {
                const value = maybeUrl.searchParams.get(key);
                const parsed = extraerCodigoDesdeTexto(value);
                if (parsed) return parsed;
            }
        } catch (_) {
            // No es URL válida, continuar
        }

        // B) Intentar como JSON
        if (raw.startsWith('{') && raw.endsWith('}')) {
            try {
                const json = JSON.parse(raw);
                const keys = ['numero_guia', 'guia', 'codigo', 'qr_code', 'code'];
                for (const key of keys) {
                    const parsed = extraerCodigoDesdeTexto(json[key]);
                    if (parsed) return parsed;
                }
            } catch (_) {
                // No es JSON válido, continuar
            }
        }

        // C) Texto plano (incluye multilinea)
        return extraerCodigoDesdeTexto(raw);
    }

    function normalizarEtiquetaCampo(key) {
        return (key || '')
            .toString()
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function extraerInformacionQR(rawText) {
        const info = {
            rawText: rawText ? String(rawText).trim() : '',
            guia: null,
            nombre: null,
            direccion: null,
            remitente: null,
            telefono: null,
            total: null,
            campos: {}
        };

        if (!info.rawText) return info;
        info.guia = normalizarCodigoEscaneado(info.rawText);

        // JSON
        if (info.rawText.startsWith('{') && info.rawText.endsWith('}')) {
            try {
                const json = JSON.parse(info.rawText);
                Object.keys(json).forEach(k => {
                    const key = normalizarEtiquetaCampo(k);
                    const value = json[k];
                    if (value === undefined || value === null || value === '') return;
                    info.campos[key] = String(value);
                });
            } catch (_) {}
        } else {
            // Texto multilinea tipo "Campo: Valor"
            info.rawText.split(/\r?\n/).forEach(line => {
                const parts = line.split(':');
                if (parts.length < 2) return;
                const key = normalizarEtiquetaCampo(parts.shift());
                const value = parts.join(':').trim();
                if (!value) return;
                info.campos[key] = value;
            });
        }

        const tomar = (keys) => {
            for (const k of keys) {
                const kk = normalizarEtiquetaCampo(k);
                if (info.campos[kk]) return info.campos[kk];
            }
            return null;
        };

        info.guia = info.guia || tomar(['guia', 'numero_guia', 'codigo', 'qr_code', 'code']);
        info.nombre = tomar(['destinatario', 'destinatario_nombre', 'nombre_destinatario', 'nombre_receptor', 'nombre']);
        info.direccion = tomar(['direccion', 'direccion_destino', 'direccion_entrega']);
        info.remitente = tomar(['remitente', 'remitente_nombre', 'tienda', 'cliente']);
        info.telefono = tomar(['telefono', 'destinatario_telefono', 'telefono_destinatario']);
        info.total = tomar(['total', 'total_a_cobrar', 'recaudo', 'valor_recaudo']);

        // Si vino "Guía: ECO..." en campos, normalizarla también
        if (info.guia) info.guia = normalizarCodigoEscaneado(info.guia) || String(info.guia).toUpperCase();

        return info;
    }

    async function onScanSuccess(decodedText, decodedResult) {
        const now = Date.now();
        const normalizedCode = normalizarCodigoEscaneado(decodedText);
        const qrInfo = extraerInformacionQR(decodedText);

        // Evitar lecturas múltiples del mismo código en menos de 2 segundos
        if (normalizedCode && normalizedCode === lastScannedCode && (now - lastScannedTime) < 2000) {
            return;
        }
        lastScannedCode = normalizedCode || decodedText;
        lastScannedTime = now;

        // 1. Validar formato del sistema
        if (!normalizedCode) {
            playScanSound('error');
            showToast('Código inválido. No se encontró una guía válida', 'error');
            return;
        }

        // 2. Verificar duplicados
        if (scannedQRs.find(qr => qr.code === normalizedCode)) {
            playScanSound('error');
            showToast('Este paquete ya fue escaneado', 'warning');
            return;
        }

        const validation = await validarGuiaEnServidor(normalizedCode);
        if (!validation.ok) {
            playScanSound('error');
            const message = validation.message || 'No se pudo validar el paquete';
            const type = validation.type || 'warning';
            showToast(message, type);
            return;
        }

        if (validation.notice) {
            showToast(validation.notice, 'info');
        }

        // 3. Éxito: Agregar y continuar escaneando (No cerramos el modal)
        playScanSound('success');
        addScannedQR(normalizedCode, qrInfo);
        
        // Actualizar contador dentro del modal
        const modalCounter = document.getElementById('modalQrCounter');
        if (modalCounter) modalCounter.textContent = scannedQRs.length;
    }

    function onScanFailure(error) {
        // Se ejecuta continuamente mientras busca QR, no es necesario loguear todo
        // console.warn(`Code scan error = ${error}`);
    }
    
    // ============================================
    // CÓDIGO MANUAL
    // ============================================
    
    btnManualCode.addEventListener('click', function() {
        stopScanning().then(() => {
            scanModal.classList.remove('active');
            manualModal.classList.add('active');
            manualCodeInput.value = '';
            document.getElementById('manualError').textContent = '';
        });
    });
    
    closeManualModal.addEventListener('click', function() {
        manualModal.classList.remove('active');
    });
    
    btnCancelManual.addEventListener('click', function() {
        manualModal.classList.remove('active');
    });
    
    btnConfirmManual.addEventListener('click', function() {
        const code = normalizarCodigoEscaneado(manualCodeInput.value);
        const errorSpan = document.getElementById('manualError');
        
        errorSpan.textContent = '';
        
        if (!manualCodeInput.value.trim()) {
            errorSpan.textContent = 'Por favor ingresa un código';
            return;
        }
        
        if (!code) {
            errorSpan.textContent = 'Código inválido. Debe incluir una guía válida';
            return;
        }
        
        if (scannedQRs.find(qr => qr.code === code)) {
            errorSpan.textContent = 'Este código ya fue escaneado';
            return;
        }

        validarGuiaEnServidor(code).then(result => {
            if (!result.ok) {
                errorSpan.textContent = result.message || 'No se pudo validar el paquete';
                return;
            }

            if (result.notice) {
                showToast(result.notice, 'info');
            }

            addScannedQR(code, extraerInformacionQR(manualCodeInput.value));
            manualModal.classList.remove('active');
        });
    });
    
    // Enter para confirmar
    manualCodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            btnConfirmManual.click();
        }
    });
    
    // ============================================
    // AGREGAR QR ESCANEADO
    // ============================================
    
    function addScannedQR(code, qrInfo = null) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('es-CO', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        const dateString = now.toLocaleDateString('es-CO');
        const dateTimeString = `${dateString} ${timeString}`;
        const parsedInfo = qrInfo || {};
        
        scannedQRs.push({
            code: code,
            time: timeString,
            date: dateString,
            dateTime: dateTimeString,
            timestamp: now.toISOString(),
            rawText: parsedInfo.rawText || '',
            details: {
                nombre: parsedInfo.nombre || null,
                direccion: parsedInfo.direccion || null,
                remitente: parsedInfo.remitente || null,
                telefono: parsedInfo.telefono || null,
                total: parsedInfo.total || null,
                campos: parsedInfo.campos || {}
            }
        });
        
        updateQRCounter();
        renderScannedList();
        if (isRouteMode) construirRutaDesdeEscaneados();
        guardarEstadoEscaneoLocal();
        showToast('✓ QR escaneado correctamente', 'success');
        
        // Vibración (si está disponible)
        if (navigator.vibrate) {
            navigator.vibrate(200);
        }
    }
    
    // ============================================
    // ACTUALIZAR CONTADOR
    // ============================================
    
    function updateQRCounter() {
        const count = scannedQRs.length;
        qrCounter.textContent = count;

        const modalCounter = document.getElementById('modalQrCounter');
        if (modalCounter) modalCounter.textContent = count;
        
        // Mostrar/ocultar sección de entrega
        if (count > 0) {
            deliverSection.style.display = 'block';
            deliverCount.textContent = `${count} paquete${count > 1 ? 's' : ''}`;
            btnResetCounter.style.display = 'block';
        } else {
            deliverSection.style.display = 'none';
            btnResetCounter.style.display = 'none';
        }
        
        // Animación del contador
        qrCounter.style.transform = 'scale(1.2)';
        setTimeout(() => {
            qrCounter.style.transform = 'scale(1)';
        }, 200);
    }
    
    // ============================================
    // RENDERIZAR LISTA DE ESCANEADOS
    // ============================================
    
    function renderScannedList() {
        if (scannedQRs.length === 0) {
            scannedList.innerHTML = '<p style="text-align: center; color: #6c757d; padding: 1rem;">No hay códigos escaneados</p>';
            return;
        }
        
        scannedList.innerHTML = scannedQRs.map((qr, index) => `
            <div class="scanned-item">
                <div>
                    <div class="scanned-code">${qr.code}</div>
                    <div class="scanned-time">Escaneado: ${qr.dateTime}</div>
                </div>
                <button class="btn-remove" onclick="removeQR(${index})">×</button>
            </div>
        `).join('');
    }
    
    // ============================================
    // ELIMINAR QR
    // ============================================
    
    window.removeQR = function(index) {
        scannedQRs.splice(index, 1);
        updateQRCounter();
        renderScannedList();
        if (isRouteMode) construirRutaDesdeEscaneados();
        guardarEstadoEscaneoLocal();
        showToast('QR eliminado', 'info');
    };
    
    // ============================================
    // LIMPIAR CONTADOR
    // ============================================
    
    btnResetCounter.addEventListener('click', function() {
        if (confirm('¿Estás seguro de limpiar todos los códigos escaneados?')) {
            scannedQRs = [];
            routeDeliveriesData = [];
            isRouteMode = false;
            updateQRCounter();
            renderScannedList();
            renderDeliveries();
            guardarEstadoEscaneoLocal();
            showToast('Contador limpiado', 'info');
        }
    });
    
    // ============================================
    // ENTREGAR PAQUETES
    // ============================================
    
    function normalizarGuiaParaCruce(value) {
        const raw = (value || '').toString().trim().toUpperCase();
        if (!raw) return '';
        return raw.startsWith('QR-') ? raw.substring(3) : raw;
    }

    function construirRutaDesdeEscaneados() {
        routeDeliveriesData = scannedQRs.map((qr, index) => {
            const qrNormalizado = normalizarGuiaParaCruce(qr.code);
            const entregaEncontrada = deliveriesData.find(d => {
                return normalizarGuiaParaCruce(d.guia) === qrNormalizado;
            });

            return {
                id: entregaEncontrada ? entregaEncontrada.id : null,
                guia: qr.code,
                guiaBase: qrNormalizado,
                nombre: qr.details?.nombre || qr.details?.remitente || 'Nombre no disponible',
                address: entregaEncontrada ? entregaEncontrada.address : (qr.details?.direccion || 'Dirección no disponible'),
                estado: entregaEncontrada ? (entregaEncontrada.estado || 'pendiente') : 'sin_asignar',
                scannedAt: qr.time,
                scannedDate: qr.date || '',
                scannedDateTime: qr.dateTime || '',
                details: qr.details || {},
                rawText: qr.rawText || '',
                orden: index + 1
            };
        });

        isRouteMode = true;
        renderDeliveries();
        guardarEstadoEscaneoLocal();
    }

    btnDeliver.addEventListener('click', function() {
        if (scannedQRs.length === 0) return;
        construirRutaDesdeEscaneados();
        window.location.href = 'misPaquetesMensajeros.php';
    });
    
    // ============================================
    // ACTUALIZAR ESTADÍSTICAS
    // ============================================
    
    function updateStats() {
        const statEntregadas = document.getElementById('statEntregadas');
        const statPendientes = document.getElementById('statPendientes');
        const statGanancias = document.getElementById('statGanancias');
        const statKilometros = document.getElementById('statKilometros');

        if (statEntregadas) statEntregadas.textContent = statsData.entregadas;
        if (statPendientes) statPendientes.textContent = statsData.pendientes;
        if (statGanancias) statGanancias.textContent = '$' + Number(statsData.ganancias || 0).toLocaleString('es-CO');
        if (statKilometros) statKilometros.textContent = (statsData.kilometros || 0) + ' km';
    }
    
    // ============================================
    // RENDERIZAR RECOLECCIONES
    // ============================================
    
    function renderCollections() {
        if (!collectionsBadge || !collectionAsignadas || !collectionCompletadas || !collectionsList) {
            return;
        }

        const pending = collectionsData.filter(c => c.status === 'pending').length;
        const completed = collectionsData.filter(c => c.status === 'completed').length;
        
        collectionsBadge.textContent = pending;
        collectionAsignadas.textContent = collectionsData.length;
        collectionCompletadas.textContent = completed;
        
        const listHTML = collectionsData.map(col => `
            <div class="collection-item">
                <div class="collection-header">
                    <div class="collection-id">${col.guia}</div>
                    <div class="collection-status status-${col.status}">
                        ${col.status === 'pending' ? 'Pendiente' : 'Completada'}
                    </div>
                </div>
                <div class="collection-address">📍 ${col.address}</div>
                <div class="collection-time">⏰ ${col.time}</div>
                ${col.status === 'pending' ? `
                    <div class="collection-actions">
                        <button class="btn-collection primary" onclick="startCollection(${col.id})">
                            Iniciar
                        </button>
                        <button class="btn-collection secondary" onclick="viewCollection(${col.id})">
                            Ver detalles
                        </button>
                    </div>
                ` : ''}
            </div>
        `).join('');
        
        collectionsList.innerHTML = listHTML;
    }
    
    window.startCollection = function(id) {
        const collection = collectionsData.find(c => c.id === id);
        if (collection) {
            showToast(`Iniciando recolección ${collection.guia}`, 'success');
            // Redirigir a página de recolección
            window.location.href = `recoleccionesMensajero.php`;
        }
    };
    
    window.viewCollection = function(id) {
        const collection = collectionsData.find(c => c.id === id);
        if (collection) {
            alert(`Detalles de ${collection.guia}\n\nDirección: ${collection.address}\nHora: ${collection.time}`);
        }
    };

    async function cargarDashboard() {
        try {
            const resp = await fetch(`${API_INICIO_MENSAJERO}?action=dashboard`);
            const json = await resp.json();
            if (!json.success) {
                throw new Error(json.message || 'No se pudo cargar el dashboard');
            }

            if (json.mensajero?.nombre) {
                const userName = document.querySelector('.user-name');
                if (userName) userName.textContent = json.mensajero.nombre;
            }

            statsData = json.stats || statsData;
            collectionsData = (json.recolecciones || []).map(r => ({
                id: Number(r.id),
                guia: r.numero_orden,
                address: r.direccion_recoleccion,
                time: r.horario_preferido || 'Sin hora',
                status: r.estado === 'completada' ? 'completed' : 'pending'
            }));
            deliveriesData = (json.entregas || []).map(d => ({
                id: Number(d.id),
                guia: d.numero_guia,
                address: d.direccion_destino,
                estado: d.estado,
                progress: d.estado === 'pendiente' ? 25 : 50
            }));

            updateStats();
            renderCollections();
            if (isRouteMode && scannedQRs.length > 0) {
                construirRutaDesdeEscaneados();
            } else {
                renderDeliveries();
            }
        } catch (error) {
            console.error(error);
            showToast('No se pudo cargar la información del dashboard', 'warning');
        }
    }
    
    // ============================================
    // RENDERIZAR ENTREGAS ACTIVAS
    // ============================================
    
    function renderDeliveries() {
        if (!deliveriesList) {
            return;
        }

        const dataToRender = isRouteMode ? routeDeliveriesData : deliveriesData;

        if (dataToRender.length === 0) {
            deliveriesList.innerHTML = '<p style="text-align: center; color: #6c757d; padding: 1rem;">No hay entregas en curso</p>';
            return;
        }

        const listHTML = dataToRender.map((del, index) => `
            <div class="delivery-item">
                <div class="delivery-header">
                    <div class="delivery-id">${del.guia}</div>
                    <div class="delivery-badge">${isRouteMode ? `Parada ${del.orden || (index + 1)}` : 'En tránsito'}</div>
                </div>
                ${isRouteMode ? `<div class="delivery-address">👤 ${del.nombre || 'Nombre no disponible'}</div>` : ''}
                <div class="delivery-address">📍 ${del.address}</div>
                ${isRouteMode ? `<div class="delivery-address">🗓️ Escaneado: ${del.scannedDateTime || `${del.scannedDate || '--/--/----'} ${del.scannedAt || '--:--'}`}</div>` : ''}
                ${isRouteMode ? `<div class="delivery-address">📌 Estado: ${del.estado === 'sin_asignar' ? 'Sin información en entregas en curso' : (del.estado || 'pendiente')}</div>` : ''}
                ${isRouteMode ? `<div style="margin-top: 8px;"><button class="btn-secondary" onclick="verDetalleRuta(${index})">Ver detalles</button></div>` : ''}
                <div class="delivery-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${isRouteMode ? Math.min(100, ((index + 1) / dataToRender.length) * 100) : del.progress}%"></div>
                    </div>
                    <div class="progress-text">${isRouteMode ? `${index + 1}/${dataToRender.length}` : `${del.progress}%`}</div>
                </div>
            </div>
        `).join('');
        
        deliveriesList.innerHTML = listHTML;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    window.verDetalleRuta = function(index) {
        const data = routeDeliveriesData[index];
        if (!data || !routeDetailBody || !routeDetailModal) return;

        const campos = data.details?.campos || {};
        const camposHtml = Object.keys(campos).length
            ? Object.entries(campos).map(([k, v]) => `
                <div><strong>${escapeHtml(k)}:</strong> ${escapeHtml(v)}</div>
            `).join('')
            : '<div>No hay campos adicionales detectados.</div>';

        routeDetailBody.innerHTML = `
            <div class="route-guide-sheet">
                <div class="route-guide-header">
                    <div>
                        <h3 class="route-guide-brand">EcoBikeMess</h3>
                        <p class="route-guide-subtitle">Guía de Entrega</p>
                    </div>
                    <div class="route-guide-number">
                        <small>Número de Guía</small>
                        <strong>${escapeHtml(data.guia || 'N/A')}</strong>
                    </div>
                </div>

                <div class="route-guide-grid">
                    <div class="route-guide-card">
                        <h4>Destinatario</h4>
                        <p><strong>Nombre:</strong> ${escapeHtml(data.nombre || 'No disponible')}</p>
                        <p><strong>Dirección:</strong> ${escapeHtml(data.address || 'No disponible')}</p>
                        ${data.details?.telefono ? `<p><strong>Teléfono:</strong> ${escapeHtml(data.details.telefono)}</p>` : ''}
                    </div>

                    <div class="route-guide-card">
                        <h4>Información del Envío</h4>
                        <p><strong>Estado:</strong> ${escapeHtml(data.estado || 'pendiente')}</p>
                        <p><strong>Escaneado:</strong> ${escapeHtml(data.scannedDateTime || 'No disponible')}</p>
                        ${data.details?.remitente ? `<p><strong>Remitente:</strong> ${escapeHtml(data.details.remitente)}</p>` : ''}
                        ${data.details?.total ? `<p><strong>Total a cobrar:</strong> ${escapeHtml(data.details.total)}</p>` : ''}
                    </div>

                    <div class="route-guide-card">
                        <h4>Datos completos del QR</h4>
                        ${camposHtml}
                    </div>
                </div>

                <div class="route-guide-meta">
                    ${data.rawText ? `<div><strong>Texto bruto QR:</strong> ${escapeHtml(data.rawText)}</div>` : ''}
                </div>

                <div class="route-guide-actions">
                    <button class="btn-secondary" onclick="cerrarDetalleRutaDesdeBoton()">Cerrar</button>
                    <button class="btn-primary" onclick="irAEntregarGuia(${index})">Entregar</button>
                </div>
            </div>
        `;

        routeDetailModal.classList.add('active');
    };

    window.irAEntregarGuia = function(index) {
        const data = routeDeliveriesData[index];
        if (!data || !data.guia) return;
        const guia = encodeURIComponent(data.guia);
        window.location.href = `misPaquetesMensajeros.php?guia=${guia}&accion=entregar`;
    };

    function cerrarDetalleRuta() {
        if (routeDetailModal) routeDetailModal.classList.remove('active');
    }
    window.cerrarDetalleRutaDesdeBoton = cerrarDetalleRuta;

    if (closeRouteDetailModal) closeRouteDetailModal.addEventListener('click', cerrarDetalleRuta);
    if (btnCloseRouteDetail) btnCloseRouteDetail.addEventListener('click', cerrarDetalleRuta);
    
    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');
        
        const icons = {
            success: '✓',
            error: '✕',
            info: 'ℹ️',
            warning: '⚠️'
        };
        
        toastIcon.textContent = icons[type] || icons.success;
        toastMessage.textContent = message;
        
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // ============================================
    // VALIDACIÓN EN SERVIDOR
    // ============================================

    async function validarGuiaEnServidor(guia) {
        if (!guia) {
            return { ok: false, message: 'Código inválido', type: 'warning' };
        }

        if (pendingValidations.has(guia)) {
            return { ok: false, message: 'Validando guía, intenta de nuevo', type: 'info' };
        }

        pendingValidations.add(guia);

        try {
            const resp = await fetch(`${API_INICIO_MENSAJERO}?action=scan`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ guia })
            });
            const json = await resp.json();
            if (!json.success) {
                return { ok: false, message: json.message || 'No se pudo validar el paquete', type: 'warning' };
            }

            if (json.notice) {
                return { ok: true, notice: json.notice };
            }

            return { ok: true };
        } catch (error) {
            console.error('Error validando guía:', error);
            return { ok: false, message: 'No se pudo validar el paquete', type: 'warning' };
        } finally {
            pendingValidations.delete(guia);
        }
    }

    // ============================================
    // GEOLOCALIZACIÓN Y PERMISOS (AL INICIO)
    // ============================================
    
    function solicitarPermisosGPS() {
        if ('geolocation' in navigator) {
            // Opciones para alta precisión
            const opciones = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };
            
            // 1. Obtener ubicación inmediata para forzar el prompt de permisos
            navigator.geolocation.getCurrentPosition(
                position => {
                    ubicacionActual = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    console.log('📍 Ubicación inicial obtenida:', ubicacionActual);
                    
                    // Actualizar estado visual si existe el indicador
                    const statusDot = document.querySelector('.status-dot');
                    const statusText = document.querySelector('.status-text');
                    if(statusDot && statusText) {
                        statusDot.style.background = '#28a745'; // Verde
                        statusText.textContent = 'En línea - GPS Activo';
                    }
                    
                    // 2. Iniciar tracking continuo
                    iniciarTrackingGPS();
                },
                error => {
                    console.error('Error GPS:', error);
                    showToast('⚠️ Por favor activa la ubicación para recibir pedidos', 'warning');
                },
                opciones
            );
        }
    }

    function iniciarTrackingGPS() {
        if (watchId) return; // Ya está activo
        
        watchId = navigator.geolocation.watchPosition(
            position => {
                // Aquí podrías enviar la ubicación al servidor en segundo plano
                // updateLocationOnServer(position.coords);
                console.log('📡 GPS Actualizado');
            },
            error => console.warn('Pérdida de señal GPS'),
            { enableHighAccuracy: true }
        );
    }
    
    // ============================================
    // INICIALIZAR
    // ============================================
    
    function init() {
        cargarEstadoEscaneoLocal();
        cargarDashboard();
        updateQRCounter();
        renderScannedList(); // LocalStorage o sesión actual
        if (isRouteMode && scannedQRs.length > 0) {
            construirRutaDesdeEscaneados();
        }
        solicitarPermisosGPS(); // Solicitar GPS apenas carga el dashboard
        
        // Animación de entrada
        const cards = document.querySelectorAll('.stat-card, .qr-counter-card, .collections-section');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
    
    // Cerrar modales al hacer clic fuera
    scanModal.addEventListener('click', function(e) {
        if (e.target === scanModal) {
            stopScanning().then(() => {
                scanModal.classList.remove('active');
            });
        }
    });
    
    manualModal.addEventListener('click', function(e) {
        if (e.target === manualModal) {
            manualModal.classList.remove('active');
        }
    });

    if (routeDetailModal) {
        routeDetailModal.addEventListener('click', function(e) {
            if (e.target === routeDetailModal) {
                cerrarDetalleRuta();
            }
        });
    }
    
    // Prevenir zoom en inputs (iOS)
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('focus', function() {
            document.querySelector('meta[name=viewport]').setAttribute('content', 
                'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
        });
        
        input.addEventListener('blur', function() {
            document.querySelector('meta[name=viewport]').setAttribute('content', 
                'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
        });
    });
    
    // Inicializar
    init();
    
    console.log('Dashboard de mensajero cargado ✓');
});
