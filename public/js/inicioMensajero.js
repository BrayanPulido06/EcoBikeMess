document.addEventListener('DOMContentLoaded', function() {
    
    // Estado de la aplicación
    let scannedQRs = [];
    let sessionStartTime = new Date();
    let sessionTimer;
    let html5QrCode = null; // Variable para la instancia del escáner
    let lastScannedCode = null;
    let lastScannedTime = 0;
    let isFlashOn = false;
    let ubicacionActual = null;
    let watchId = null;
    
    // Datos de ejemplo
    const statsData = {
        entregadas: 12,
        pendientes: 3,
        ganancias: 85000,
        kilometros: 24.5
    };
    
    const collectionsData = [
        {
            id: 1,
            guia: 'ECO-2024-12350',
            address: 'Calle 100 #15-30, Zona Norte',
            time: '10:00 AM',
            status: 'pending'
        },
        {
            id: 2,
            guia: 'ECO-2024-12351',
            address: 'Carrera 7 #80-45, Zona Centro',
            time: '11:30 AM',
            status: 'pending'
        },
        {
            id: 3,
            guia: 'ECO-2024-12352',
            address: 'Avenida 68 #25-10, Zona Sur',
            time: '09:00 AM',
            status: 'completed'
        }
    ];
    
    const deliveriesData = [
        {
            id: 1,
            guia: 'ECO-2024-12340',
            address: 'Calle 26 #68-91',
            progress: 60
        },
        {
            id: 2,
            guia: 'ECO-2024-12341',
            address: 'Transversal 45 #12-67',
            progress: 30
        }
    ];
    
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
        document.getElementById('sessionTime').textContent = timeString;
    }
    
    sessionTimer = setInterval(updateSessionTime, 1000);
    
    // ============================================
    // ESCANEAR QR
    // ============================================
    
    btnScanQR.addEventListener('click', function() {
        scanModal.classList.add('active');
        startScanning();
    });
    
    closeScanModal.addEventListener('click', function() {
        stopScanning().then(() => {
            scanModal.classList.remove('active');
        });
    });

    // ============================================
    // LÓGICA DE ESCANEO (HTML5-QRCODE)
    // ============================================

    function startScanning() {
        // Limpiar cualquier instancia previa o mensaje de error
        document.getElementById('reader').innerHTML = '';
        document.getElementById('modalQrCounter').textContent = scannedQRs.length;
        
        // Resetear variables de control
        lastScannedCode = null;
        isFlashOn = false;
        const btnFlash = document.getElementById('btnFlash');
        if(btnFlash) btnFlash.style.display = 'none';
        
        html5QrCode = new Html5Qrcode("reader");
        // fps: 10 para escaneo rápido, qrbox responsive
        const config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        // Usar 'environment' para forzar cámara trasera
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
        .then(() => {
            // Intentar habilitar botón de flash si el dispositivo lo soporta
            if(btnFlash) {
                btnFlash.style.display = 'block';
                btnFlash.onclick = toggleFlash;
            }
        })
        .catch(err => {
            console.error("Error iniciando cámara:", err);
            document.getElementById('reader').innerHTML = 
                '<p style="color:#dc3545; padding:1rem;">No se pudo acceder a la cámara. Por favor verifica los permisos.</p>';
        });
    }

    function stopScanning() {
        if (html5QrCode && html5QrCode.isScanning) {
            // Apagar flash si estaba encendido
            if (isFlashOn) toggleFlash();
            return html5QrCode.stop().then(() => {
                html5QrCode.clear();
                html5QrCode = null;
            }).catch(err => console.error("Error al detener:", err));
        }
        return Promise.resolve();
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

    function onScanSuccess(decodedText, decodedResult) {
        const now = Date.now();
        // Evitar lecturas múltiples del mismo código en menos de 2 segundos
        if (decodedText === lastScannedCode && (now - lastScannedTime) < 2000) {
            return;
        }
        lastScannedCode = decodedText;
        lastScannedTime = now;

        // 1. Validar formato del sistema
        if (!decodedText.startsWith('ECO-')) {
            playScanSound('error');
            showToast('Código inválido. Debe iniciar con ECO-', 'error');
            return;
        }

        // 2. Verificar duplicados
        if (scannedQRs.find(qr => qr.code === decodedText)) {
            playScanSound('error');
            showToast('Este paquete ya fue escaneado', 'warning');
            return;
        }

        // 3. Éxito: Agregar y continuar escaneando (No cerramos el modal)
        playScanSound('success');
        addScannedQR(decodedText);
        
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
        const code = manualCodeInput.value.trim().toUpperCase();
        const errorSpan = document.getElementById('manualError');
        
        errorSpan.textContent = '';
        
        if (!code) {
            errorSpan.textContent = 'Por favor ingresa un código';
            return;
        }
        
        if (!code.startsWith('ECO-')) {
            errorSpan.textContent = 'Código inválido. Debe comenzar con ECO-';
            return;
        }
        
        if (scannedQRs.find(qr => qr.code === code)) {
            errorSpan.textContent = 'Este código ya fue escaneado';
            return;
        }
        
        addScannedQR(code);
        manualModal.classList.remove('active');
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
    
    function addScannedQR(code) {
        const now = new Date();
        const timeString = now.toLocaleTimeString('es-CO', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        scannedQRs.push({
            code: code,
            time: timeString,
            timestamp: now
        });
        
        updateQRCounter();
        renderScannedList();
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
                    <div class="scanned-time">Escaneado a las ${qr.time}</div>
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
        showToast('QR eliminado', 'info');
    };
    
    // ============================================
    // LIMPIAR CONTADOR
    // ============================================
    
    btnResetCounter.addEventListener('click', function() {
        if (confirm('¿Estás seguro de limpiar todos los códigos escaneados?')) {
            scannedQRs = [];
            updateQRCounter();
            renderScannedList();
            showToast('Contador limpiado', 'info');
        }
    });
    
    // ============================================
    // ENTREGAR PAQUETES
    // ============================================
    
    btnDeliver.addEventListener('click', function() {
        if (scannedQRs.length === 0) return;
        
        if (confirm(`¿Iniciar proceso de entrega para ${scannedQRs.length} paquete(s)?`)) {
            // Aquí iría la lógica de entrega
            showToast(`Iniciando entrega de ${scannedQRs.length} paquetes...`, 'success');
            
            // Simular inicio de entrega
            setTimeout(() => {
                // Redirigir a página de entregas
                // window.location.href = 'procesarEntregas.php';
                console.log('Procesando entregas:', scannedQRs);
            }, 1000);
        }
    });
    
    // ============================================
    // ACTUALIZAR ESTADÍSTICAS
    // ============================================
    
    function updateStats() {
        document.getElementById('statEntregadas').textContent = statsData.entregadas;
        document.getElementById('statPendientes').textContent = statsData.pendientes;
        document.getElementById('statGanancias').textContent = '$' + statsData.ganancias.toLocaleString('es-CO');
        document.getElementById('statKilometros').textContent = statsData.kilometros + ' km';
    }
    
    // ============================================
    // RENDERIZAR RECOLECCIONES
    // ============================================
    
    function renderCollections() {
        const pending = collectionsData.filter(c => c.status === 'pending').length;
        const completed = collectionsData.filter(c => c.status === 'completed').length;
        
        document.getElementById('collectionsBadge').textContent = pending;
        document.getElementById('collectionAsignadas').textContent = collectionsData.length;
        document.getElementById('collectionCompletadas').textContent = completed;
        
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
        
        document.getElementById('collectionsList').innerHTML = listHTML;
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
    
    // ============================================
    // RENDERIZAR ENTREGAS ACTIVAS
    // ============================================
    
    function renderDeliveries() {
        if (deliveriesData.length === 0) {
            document.getElementById('deliveriesList').innerHTML = '<p style="text-align: center; color: #6c757d; padding: 1rem;">No hay entregas en curso</p>';
            return;
        }
        
        const listHTML = deliveriesData.map(del => `
            <div class="delivery-item">
                <div class="delivery-header">
                    <div class="delivery-id">${del.guia}</div>
                    <div class="delivery-badge">En tránsito</div>
                </div>
                <div class="delivery-address">📍 ${del.address}</div>
                <div class="delivery-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${del.progress}%"></div>
                    </div>
                    <div class="progress-text">${del.progress}%</div>
                </div>
            </div>
        `).join('');
        
        document.getElementById('deliveriesList').innerHTML = listHTML;
    }
    
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
    // NOTIFICACIONES
    // ============================================
    
    function simulateNotifications() {
        setTimeout(() => {
            showToast('Nueva recolección asignada', 'info');
            
            // Actualizar badge
            const badge = document.querySelector('.notif-badge');
            const currentCount = parseInt(badge.textContent);
            badge.textContent = currentCount + 1;
        }, 5000);
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
        updateStats();
        renderCollections();
        renderDeliveries();
        renderScannedList(); // LocalStorage o sesión actual
        simulateNotifications();
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