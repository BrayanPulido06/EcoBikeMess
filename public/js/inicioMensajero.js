document.addEventListener('DOMContentLoaded', function() {
    console.log('EcoBikeMess inicioMensajero.js v1.2.7 - Máximo Rendimiento'); 
    // Desactivar Service Worker/caché agresiva en móvil (puede impedir permisos de cámara y cargar JS/CSS viejos)
    try {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(regs => regs.forEach(r => r.unregister())).catch(() => {});
        }
        if (window.caches && typeof window.caches.keys === 'function') {
            window.caches.keys().then(keys => keys.forEach(k => window.caches.delete(k))).catch(() => {});
        }
    } catch (_) {}
    
    // Estado de la aplicación
    let scannedQRs = [];
    let sessionStartTime = new Date();
    let sessionTimer;
    let html5QrCode = null; // Variable para la instancia del escáner
    let scannerStartToken = 0;
    let isScannerStarting = false;
    let isScannerStopping = false;
    let lastScannedCode = null;
    let isProcessingScan = false; // Bloqueo para evitar saturación
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
    const btnEnableCamera = document.getElementById('btnEnableCamera');
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
    
    if (menuBtn && sideMenu && menuOverlay) {
        menuBtn.addEventListener('click', function() {
            sideMenu.classList.add('active');
            menuOverlay.classList.add('active');
        });
        
        menuOverlay.addEventListener('click', function() {
            sideMenu.classList.remove('active');
            menuOverlay.classList.remove('active');
        });
    }
    
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
    
    if (btnScanQR && scanModal) {
        btnScanQR.addEventListener('click', function() {
            // Forzar reseteo de estado si el modal estaba "trabado"
            if (isScannerStarting) {
                console.log('Reiniciando estado del escáner...');
                isScannerStarting = false;
            }
            
            if (scanModal.classList.contains('active')) scanModal.classList.remove('active');
            scanModal.classList.add('active');
            // Iniciamos el escaneo directamente
            startScanning({ userGesture: true });
        });
    }

    if (btnEnableCamera) {
        btnEnableCamera.addEventListener('click', function() {
            startScanning({ userGesture: true });
        });
    }
    
    if (closeScanModal && scanModal) {
        closeScanModal.addEventListener('click', function() {
            stopScanning().then(() => {
                scanModal.classList.remove('active');
            });
        });
    }

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

    async function solicitarStreamCamaraBasico() {
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            return null;
        }

        const intentos = [
            { video: { facingMode: { ideal: 'environment' } }, audio: false },
            { video: { facingMode: 'environment' }, audio: false },
            { video: true, audio: false }
        ];

        for (const constraints of intentos) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                return stream;
            } catch (error) {
                console.warn('Fallo getUserMedia con restricciones', constraints, error);
            }
        }

        return null;
    }

    async function startScanning(options = {}) {
        if (isScannerStarting) return;
        isScannerStarting = true;

        const userGesture = options.userGesture === true;
        const currentToken = ++scannerStartToken;
        const readerEl = document.getElementById('reader');
        const modalCounterRef = document.getElementById('modalQrCounter');
        const btnFlash = document.getElementById('btnFlash');

        // Obtener la librería de forma segura
        const ScannerLib = window.Html5Qrcode || (typeof Html5Qrcode !== 'undefined' ? Html5Qrcode : null);

        if (!readerEl) {
            console.error("Error: Elemento 'reader' no encontrado en el DOM.");
            showToast('Error interno: Contenedor de cámara no encontrado (ID: reader).', 'error');
            isScannerStarting = false; 
            return; // Detener ejecución si el elemento principal no existe
        }
        
        try {
            if (!ScannerLib) {
                readerEl.innerHTML =
                    '<div style="color:#dc3545; padding:1.5rem; text-align:center;">' +
                    '<p><strong>Error:</strong> No se cargó la librería de escaneo.</p>' +
                    '<p>Revisa tu conexión a internet y recarga la página.</p></div>';
                showToast('Falta librería de escaneo', 'error');
                isScannerStarting = false;
                return;
            }

            if (btnEnableCamera) btnEnableCamera.style.display = 'none';
            if (btnFlash) btnFlash.style.display = 'none';
            if (readerEl) readerEl.innerHTML = '<div style="padding:2rem; text-align:center; color:#64748b;"><p>Iniciando cámara...</p></div>';
            if (modalCounterRef) modalCounterRef.textContent = String(scannedQRs?.length || 0);
            if (btnEnableCamera) btnEnableCamera.style.display = 'none';

            // Resetear variables de control
            lastScannedCode = null;
            isFlashOn = false;
            if (btnFlash) btnFlash.style.display = 'none';

            // 1. Validaciones de Contexto Seguro (Informativo, no bloqueante)
            const isSecure = window.isSecureContext === true || location.protocol === 'https:';
            const host = window.location.hostname;
            const isLocalhost = host === 'localhost' || host === '127.0.0.1' || host === '::1' || host.startsWith('192.168.') || host.startsWith('10.');

            if (!isSecure && !isLocalhost) {
                console.warn('Contexto no seguro. La cámara podría fallar si el navegador lo restringe.');
            }

            // Validar existencia de mediaDevices
            if (!navigator.mediaDevices || (!navigator.mediaDevices.getUserMedia && !navigator.getUserMedia)) {
                console.error("Acceso a mediaDevices no disponible directamente.");
            }

            // Verificar que la librería html5-qrcode cargó correctamente (si falla, Html5Qrcode queda undefined)
            if (typeof window.Html5Qrcode !== 'function') {
                if (readerEl) {
                    readerEl.innerHTML =
                        '<p style="color:#dc3545; padding:1rem;">No se cargó la librería de escaneo (html5-qrcode). Revisa conexión, bloqueadores (AdBlock) y recarga la página.</p>';
                }
                showToast('Falta librería de escaneo (html5-qrcode)', 'error');
                isScannerStarting = false;
                return;
            }

            // Configuración simplificada: evita cálculos dinámicos pesados que congelan la pantalla
            const config = {
                fps: 8,
                qrbox: { width: 250, height: 250 }, // Tamaño fijo para mayor rendimiento
                aspectRatio: 1.0,
                disableFlip: true
            };

            if (userGesture) {
                if (html5QrCode && isHtml5QrCodeScanning(html5QrCode)) {
                    isScannerStarting = false;
                    return;
                }
                if (html5QrCode) {
                    try { html5QrCode.clear(); } catch (_) {}
                    html5QrCode = null;
                }
            }

            // Detener instancia previa
            await stopScanning({ cancelPendingStart: false });
            if (currentToken !== scannerStartToken) {
                isScannerStarting = false;
                return;
            }

            // Validar que el elemento existe antes de pasarle el ID a la librería
            if (!document.getElementById("reader")) {
                throw new Error("El contenedor de la cámara (reader) no existe en la página.");
            }

            if (readerEl) readerEl.innerHTML = '';

            const streamPrueba = await solicitarStreamCamaraBasico();
            if (streamPrueba) {
                streamPrueba.getTracks().forEach(track => track.stop());
            }

            html5QrCode = new ScannerLib("reader");
            
            // IMPORTANTE: Quitamos "exact" de environment. 
            // Esto permite que funcione en computadores (usando la webcam) 
            // y evita que los celulares se queden "pensando" qué cámara usar.
            let started = false;
            const startStrategies = [
                { facingMode: { exact: "environment" } },
                { facingMode: { ideal: "environment" } },
                { facingMode: "environment" },
                { facingMode: "user" },
                undefined
            ];

            for (const cameraConfig of startStrategies) {
                try {
                    if (cameraConfig === undefined) {
                        const cameras = typeof ScannerLib.getCameras === 'function'
                            ? await ScannerLib.getCameras()
                            : [];
                        const rearCamera = cameras.find(camera => /back|rear|trasera|environment/i.test(camera.label || ''));
                        const selectedCamera = rearCamera?.id || cameras[0]?.id;
                        if (!selectedCamera) continue;

                        await html5QrCode.start(
                            selectedCamera,
                            config,
                            onScanSuccess,
                            null
                        );
                    } else {
                        await html5QrCode.start(
                            cameraConfig,
                            config,
                            onScanSuccess,
                            null
                        );
                    }

                    started = true;
                    break;
                } catch (startError) {
                    console.warn('Fallo iniciando cámara con estrategia', cameraConfig, startError);
                }
            }

            if (!started) {
                throw new Error('No fue posible iniciar la cámara con una configuración compatible');
            }

            if (btnFlash) {
                btnFlash.style.display = 'inline-flex';
                btnFlash.onclick = toggleFlash;
            }
            if (btnEnableCamera) btnEnableCamera.style.display = 'none';
        } catch (err) {
            console.error("Error crítico iniciando cámara:", err);
            isScannerStarting = false;
            if (readerEl) {
                const name = String(err?.name || '');
                const msg = String(err?.message || '');
                const isRef = /ReferenceError|TypeError/i.test(name) || /not defined|null|properties|textContent|setting|undefined|reading/i.test(msg);
                
                let baseHelp = 'No se pudo abrir la cámara. Verifica que el <b>ID "reader"</b> exista y hayas dado permisos.';
                
                if (isRef) {
                    baseHelp = '<strong>Error de Interfaz:</strong> Se detectó un fallo al intentar actualizar la pantalla. Por favor, recarga la página.';
                } else if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || msg.includes('denied')) {
                    baseHelp = '<strong>🚫 Permiso Denegado:</strong> El acceso está bloqueado. <br><br><b>Cómo arreglarlo:</b><br>1. Toca el <b>candado 🔒</b> en la barra de direcciones.<br>2. Entra en <b>"Permisos"</b> o <b>"Configuración del sitio"</b>.<br>3. Cambia <b>Cámara</b> a <b>"Permitir"</b>.<br>4. Recarga la página.';
                } else if (name === 'NotReadableError' || name === 'TrackStartError') {
                    baseHelp = '<strong>📷 Cámara en uso:</strong> Otra aplicación está usando la cámara (WhatsApp, Instagram, etc.). Ciérralas e intenta de nuevo.';
                }

                readerEl.innerHTML = `
                    <div style="color:#dc3545; padding:1.5rem; text-align:left;">
                        <p>${baseHelp}</p>
                        ${(name || msg) && !isRef ? `<hr style="opacity:0.2;margin:10px 0;"><small style="opacity:.85">Detalle Técnico: ${name} ${msg}</small>` : ''}
                        <button onclick="location.reload()" style="margin-top:10px; padding:8px 15px; background:#6c757d; color:white; border:none; border-radius:5px; cursor:pointer;">Recargar Página</button>
                    </div> 
                `;
            }
            showToast('No se pudo acceder a la cámara', 'error');
            if (btnEnableCamera) btnEnableCamera.style.display = 'block';
        } finally {
            // Retraso mínimo para permitir que el hardware se libere
            setTimeout(() => { isScannerStarting = false; }, 500);
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

    // Instancia única para evitar fugas de memoria y bloqueos de audio
    let scanAudioCtx = null;
    // Generar sonido de confirmación (Beep)
    function playScanSound(type = 'success') {
        try {
            if (!window.AudioContext && !window.webkitAudioContext) return;
            if (!scanAudioCtx) scanAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const audioCtx = scanAudioCtx;
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            
            if (type === 'success') {
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(1000, audioCtx.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(500, audioCtx.currentTime + 0.1);
            } else {
                oscillator.type = 'sawtooth';
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
        const textoLimpio = String(rawText).trim();
        if (!textoLimpio) return null;
        // MÁXIMA PERMISIVIDAD: Devolvemos todo el texto detectado
        return textoLimpio.toUpperCase();
    }

    function normalizarCodigoEscaneado(decodedText) {
        if (!decodedText) return null;
        return extraerCodigoDesdeTexto(decodedText);
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

    function onScanSuccess(decodedText) {
        if (isProcessingScan) return;
        isProcessingScan = true;
        
        const readerEl = document.getElementById('reader');
        console.log("Lectura detectada:", decodedText);

        if ("vibrate" in navigator) navigator.vibrate(100); // Feedback háptico solicitado
        if (readerEl) readerEl.style.border = '4px solid #28a745';

        try {
            const normalizedCode = normalizarCodigoEscaneado(decodedText);
            const qrInfo = extraerInformacionQR(decodedText);
            const now = Date.now();

            // Evitar re-escaneos accidentales del mismo código en 3 segundos
            if (normalizedCode === lastScannedCode && (now - lastScannedTime) < 3000) {
                throw "cooldown";
            }

            lastScannedCode = normalizedCode;
            lastScannedTime = now;

            if (scannedQRs.find(qr => qr.code === normalizedCode)) {
                if (lastScannedCode !== normalizedCode) playScanSound('error');
                showToast('Ya escaneado: ' + normalizedCode, 'warning');
            } else {
                addScannedQR(normalizedCode, qrInfo);
                playScanSound('success');
                validarGuiaEnServidor(normalizedCode).then(v => {
                    if (v.notice) showToast(v.notice, 'info');
                });
            }
        } catch (e) {
            if (e !== "cooldown") console.error("Error procesando scan:", e);
        } finally {
            // Pausa de seguridad para que el móvil respire
            setTimeout(() => {
                isProcessingScan = false;
                if (readerEl) readerEl.style.border = 'none';
            }, 1500);
        }
    }

    function onScanFailure(error) {
        // Se ejecuta continuamente mientras busca QR, no es necesario loguear todo
        // console.warn(`Code scan error = ${error}`);
    }

    // Sobrescrituras defensivas: algunos QRs contienen varias líneas y el escáner
    // debe quedarse con la guía, no con todo el texto bruto.
    function extraerCodigoDesdeTexto(rawText) {
        if (!rawText) return null;

        const textoLimpio = String(rawText).replace(/\r/g, '').trim();
        if (!textoLimpio) return null;

        const lineas = textoLimpio
            .split('\n')
            .map(line => line.trim())
            .filter(Boolean);

        const candidatos = [];
        const regexEtiqueta = /(?:^|\b)(?:guia|guía|numero_guia|nro_guia|codigo|código|qr_code|code)\s*[:#-]?\s*([A-Z0-9][A-Z0-9\-_/]{2,})/i;
        const regexGuiaLibre = /\b(?:[A-Z]{2,10}-)?\d{2,6}-[A-Z0-9]{2,}\b/i;

        for (const linea of lineas) {
            const matchEtiqueta = linea.match(regexEtiqueta);
            if (matchEtiqueta?.[1]) candidatos.push(matchEtiqueta[1]);

            const matchLibre = linea.match(regexGuiaLibre);
            if (matchLibre?.[0]) candidatos.push(matchLibre[0]);
        }

        if (textoLimpio.startsWith('{') && textoLimpio.endsWith('}')) {
            try {
                const json = JSON.parse(textoLimpio);
                ['guia', 'guía', 'numero_guia', 'codigo', 'qr_code', 'code'].forEach(key => {
                    if (json[key]) candidatos.push(String(json[key]));
                });
            } catch (_) {}
        }

        if (candidatos.length === 0 && lineas.length === 1 && !lineas[0].includes(':')) {
            candidatos.push(lineas[0]);
        }

        return candidatos
            .map(valor => String(valor).trim().toUpperCase())
            .map(valor => valor.replace(/^[\s#:.-]+|[\s#:.-]+$/g, ''))
            .find(Boolean) || null;
    }

    function normalizarCodigoEscaneado(decodedText) {
        return extraerCodigoDesdeTexto(decodedText);
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

        info.guia = tomar(['guia', 'numero_guia', 'codigo', 'qr_code', 'code']) || extraerCodigoDesdeTexto(info.rawText);
        info.nombre = tomar(['destinatario', 'destinatario_nombre', 'nombre_destinatario', 'nombre_receptor', 'nombre']);
        info.direccion = tomar(['direccion', 'direccion_destino', 'direccion_entrega']);
        info.remitente = tomar(['remitente', 'remitente_nombre', 'tienda', 'cliente']);
        info.telefono = tomar(['telefono', 'destinatario_telefono', 'telefono_destinatario']);
        info.total = tomar(['total', 'total_a_cobrar', 'recaudo', 'valor_recaudo']);

        if (info.guia) info.guia = normalizarCodigoEscaneado(info.guia) || String(info.guia).toUpperCase();

        return info;
    }

    function onScanSuccess(decodedText) {
        if (isProcessingScan) return;
        isProcessingScan = true;

        const readerEl = document.getElementById('reader');
        console.log("Lectura detectada:", decodedText);

        if ("vibrate" in navigator) navigator.vibrate(100);
        if (readerEl) readerEl.style.border = '4px solid #28a745';

        try {
            const normalizedCode = normalizarCodigoEscaneado(decodedText);
            const qrInfo = extraerInformacionQR(decodedText);
            const now = Date.now();

            if (!normalizedCode) {
                throw new Error('No se detectó una guía válida dentro del QR');
            }

            if (normalizedCode === lastScannedCode && (now - lastScannedTime) < 3000) {
                throw "cooldown";
            }

            lastScannedCode = normalizedCode;
            lastScannedTime = now;

            if (scannedQRs.find(qr => qr.code === normalizedCode)) {
                playScanSound('error');
                showToast('Ya escaneado: ' + normalizedCode, 'warning');
            } else {
                validarGuiaEnServidor(normalizedCode).then(v => {
                    if (!v.ok) {
                        playScanSound('error');
                        showToast(v.message || 'No se pudo validar el paquete', v.type || 'warning');
                        return;
                    }

                    addScannedQR(normalizedCode, {
                        ...qrInfo,
                        guia: normalizedCode
                    });
                    playScanSound('success');
                    if (v.notice) showToast(v.notice, 'info');
                });
            }
        } catch (e) {
            if (e !== "cooldown") console.error("Error procesando scan:", e);
        } finally {
            setTimeout(() => {
                isProcessingScan = false;
                if (readerEl) readerEl.style.border = 'none';
            }, 1500);
        }
    }
    
    // ============================================
    // CÓDIGO MANUAL
    // ============================================
    
    btnManualCode?.addEventListener('click', function() {
        stopScanning().then(() => {
            scanModal.classList.remove('active');
            if (manualModal) manualModal.classList.add('active');
            if (manualCodeInput) manualCodeInput.value = '';
            const errEl = document.getElementById('manualError');
            if (errEl) errEl.innerText = '';
        });
    });
    
    closeManualModal?.addEventListener('click', function() {
        manualModal?.classList.remove('active');
    });
    
    btnCancelManual?.addEventListener('click', function() {
        manualModal?.classList.remove('active');
    });
    
    btnConfirmManual?.addEventListener('click', function() {
        const code = normalizarCodigoEscaneado(manualCodeInput.value);
        const errorSpan = document.getElementById('manualError');
        
        if (errorSpan) errorSpan.innerText = '';
        
        if (!manualCodeInput?.value.trim()) {
            if (errorSpan) errorSpan.innerText = 'Por favor ingresa un código';
            return;
        }
        
        if (!code) {
            if (errorSpan) errorSpan.innerText = 'Código inválido. Debe incluir una guía válida';
            return;
        }
        
        if (scannedQRs.find(qr => qr.code === code)) {
            if (errorSpan) errorSpan.innerText = 'Este código ya fue escaneado';
            return;
        }

        validarGuiaEnServidor(code).then(result => {
            if (!result.ok) {
                if (errorSpan) errorSpan.innerText = result.message || 'No se pudo validar el paquete';
                return;
            }

            if (result.notice) {
                showToast(result.notice, 'info');
            }

            addScannedQR(code, extraerInformacionQR(manualCodeInput.value));
            manualModal?.classList.remove('active');
        });
    });
    
    // Enter para confirmar
    manualCodeInput?.addEventListener('keypress', function(e) {
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
        
        if (collectionsBadge) collectionsBadge.textContent = String(pending);
        if (collectionAsignadas) collectionAsignadas.textContent = String(collectionsData.length);
        if (collectionCompletadas) collectionCompletadas.textContent = String(completed);
        
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

    function updateStats() {
        const entregadas = document.getElementById('statsEntregadas');
        const pendientes = document.getElementById('statsPendientes');
        const ganancias = document.getElementById('statsGanancias');
        
        if (entregadas) entregadas.textContent = statsData.entregadas;
        if (pendientes) pendientes.textContent = statsData.pendientes;
        if (ganancias) ganancias.textContent = `$${statsData.ganancias.toLocaleString('es-CO')}`;
    }

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
        // 1. Intentar usar el sistema de UI global si existe (más robusto)
        if (window.EcoBikeUI && typeof window.EcoBikeUI.toast === 'function') {
            window.EcoBikeUI.toast(message, { type: type });
            return;
        }

        // 2. Fallback al sistema local validando nulos
        const toast = document.getElementById('toast');
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');
        
        if (!toast || !toastIcon || !toastMessage) {
            console.warn(`Toast Fallback [${type}]: ${message}`);
            return;
        }

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
                        if (statusDot) statusDot.style.background = '#28a745';
                        if (statusText) statusText.textContent = 'En línea - GPS Activo';
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
    // FUNCIONES DE UI (IMPLEMENTACIÓN FALTANTE)
    // ============================================

    function updateQRCounter() {
        if (qrCounter) qrCounter.textContent = scannedQRs.length;
        if (deliverCount) deliverCount.textContent = scannedQRs.length;
        
        if (deliverSection) {
            if (scannedQRs.length > 0) {
                deliverSection.classList.add('active');
            } else {
                deliverSection.classList.remove('active');
            }
        }
    }

    function renderScannedList() {
        if (!scannedList) return;
        
        if (scannedQRs.length === 0) {
            scannedList.innerHTML = '<div style="text-align:center; padding:1.5rem; color:#64748b;">No hay paquetes escaneados</div>';
            return;
        }
        
        scannedList.innerHTML = scannedQRs.map((qr, index) => `
            <div class="scanned-item" style="display:flex; justify-content:space-between; align-items:center; padding:12px; border-bottom:1px solid #eee; background:#fff; margin-bottom:5px; border-radius:8px;">
                <div class="scanned-info">
                    <div class="scanned-code" style="font-weight:bold; color: #16a34a;">${qr.code}</div>
                    <div class="scanned-time" style="font-size:0.8rem; color:#64748b;">${qr.time}</div>
                </div>
                <button type="button" class="btn-remove" onclick="removeScannedQR(${index})" style="background:none; border:none; color:#ef4444; font-size:1.2rem; cursor:pointer; padding:5px;">
                    ✕
                </button>
            </div>
        `).join('');
    }

    window.removeScannedQR = function(index) {
        scannedQRs.splice(index, 1);
        if (scannedQRs.length === 0) lastScannedCode = null; // Resetear bloqueo si se vacía la lista
        updateQRCounter();
        renderScannedList();
        if (isRouteMode) construirRutaDesdeEscaneados();
        guardarEstadoEscaneoLocal();
    };

    function construirRutaDesdeEscaneados() {
        routeDeliveriesData = scannedQRs.map((qr, index) => ({
            guia: qr.code,
            address: qr.details?.direccion || 'Dirección no detectada',
            nombre: qr.details?.nombre || 'Nombre no detectado',
            orden: index + 1,
            scannedAt: qr.time,
            scannedDate: qr.date,
            scannedDateTime: qr.dateTime,
            estado: 'escaneado',
            details: qr.details,
            rawText: qr.rawText
        }));
        
        renderDeliveries();
    }

    if (btnResetCounter) {
        btnResetCounter.addEventListener('click', function() {
            if (confirm('¿Limpiar la lista de escaneados?')) {
                scannedQRs = [];
                updateQRCounter();
                renderScannedList();
                if (isRouteMode) {
                    routeDeliveriesData = [];
                    renderDeliveries();
                }
                guardarEstadoEscaneoLocal();
            }
        });
    }

    if (btnDeliver) {
        btnDeliver.addEventListener('click', function() {
            if (scannedQRs.length === 0) return;
            isRouteMode = true;
            construirRutaDesdeEscaneados();
            guardarEstadoEscaneoLocal();
            showToast('Ruta generada', 'success');
        });
    }

    // Llamadas iniciales
    cargarEstadoEscaneoLocal();
    cargarDashboard();
    solicitarPermisosGPS();
});
