// ============================================
// CONFIGURACIÓN Y VARIABLES GLOBALES
// ============================================
let paquetes = [];
let paqueteActual = null;
let fotosEntrega = [];
let ubicacionActual = null;
let watchId = null;
let totalRecaudoHoy = 0;
const API_MIS_PAQUETES = '../../controller/misPaquetesMensajerosController.php';
let deepLinkProcesado = false;
const STORAGE_SCANNED_QR_KEY = 'ecobikemess_mensajero_scanned_qr_v1';
let qrEscaneadosMap = new Map();

// ============================================
// FUNCIONES DE FEEDBACK TÁCTIL
// ============================================
function vibrar(patron = [10]) {
    if ('vibrate' in navigator) {
        navigator.vibrate(patron);
    }
}

function feedbackExito() {
    vibrar([20, 50, 20]);
}

function feedbackError() {
    vibrar([50, 100, 50]);
}

function feedbackClick() {
    vibrar([5]);
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    inicializarApp();
    cargarQREscaneadosLocales();
    cargarPaquetes();
    configurarEventListeners();
    inicializarGeolocalización();
    actualizarFechaHora();
});

function inicializarApp() {
    console.log('App de paquetes inicializada');
    
    // Wake Lock para mantener pantalla activa
    if ('wakeLock' in navigator) {
        let wakeLock = null;
        navigator.wakeLock.request('screen').then(wl => {
            wakeLock = wl;
            console.log('Wake Lock activado');
        }).catch(err => console.error('Error Wake Lock:', err));
    }
}

// ============================================
// GEOLOCALIZACIÓN
// ============================================
function inicializarGeolocalización() {
    if ('geolocation' in navigator) {
        const opciones = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 30000
        };
        
        navigator.geolocation.getCurrentPosition(
            position => {
                ubicacionActual = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                actualizarInfoGPS();
            },
            error => {
                console.error('Error GPS:', error);
                document.getElementById('infoGPSEntrega').textContent = 'GPS no disponible';
            },
            opciones
        );
        
        watchId = navigator.geolocation.watchPosition(
            position => {
                ubicacionActual = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                actualizarInfoGPS();
            },
            error => console.error('Error tracking GPS:', error),
            opciones
        );
    }
}

function actualizarInfoGPS() {
    const elemento = document.getElementById('infoGPSEntrega');
    if (elemento && ubicacionActual) {
        elemento.textContent = `${ubicacionActual.lat.toFixed(6)}, ${ubicacionActual.lng.toFixed(6)}`;
    }
}

// ============================================
// CARGA DE DATOS
// ============================================
async function cargarPaquetes() {
    try {
        const resp = await fetch(`${API_MIS_PAQUETES}?action=listar`);
        const json = await resp.json();
        if (!json.success) {
            throw new Error(json.message || 'No se pudieron cargar los paquetes');
        }

        const paquetesDB = (json.data || []).map(row => {
            const estadoFront = row.estado === 'entregado' ? 'entregado' : 'pendiente';
            const guiaNormalizada = normalizarGuia(row.numero_guia);
            const qrData = qrEscaneadosMap.get(guiaNormalizada);

            const item = {
                id: Number(row.id),
                guia: row.numero_guia,
                nombreDestinatario: row.destinatario_nombre,
                telefono: row.destinatario_telefono,
                direccion: row.direccion_destino,
                coordenadas: { lat: 4.65, lng: -74.06 },
                contenido: row.descripcion_contenido || 'Sin descripción',
                valorDeclarado: Number(row.costo_envio || 0),
                totalCobrar: Number(row.recaudo_esperado || 0),
                observaciones: row.instrucciones_entrega || '',
                estado: estadoFront,
                qrEscaneado: !!qrData,
                qrScanData: qrData || null
            };

            if (row.nombre_receptor) {
                item.infoEntrega = {
                    nombreRecibe: row.nombre_receptor,
                    parentesco: row.parentesco_cargo || 'N/A',
                    documento: row.documento_receptor || 'N/A',
                    recaudo: Number(row.recaudo_real || 0),
                    fecha: row.fecha_entrega ? formatearFechaHora(row.fecha_entrega) : '',
                    fotos: []
                };
            }
            return item;
        });

        // Agregar guias escaneadas que no estén en la consulta del backend
        const guiasEnDB = new Set(paquetesDB.map(p => normalizarGuia(p.guia)));
        const paquetesVirtuales = [];
        qrEscaneadosMap.forEach((qrData, guiaNorm) => {
            if (guiasEnDB.has(guiaNorm)) return;
            paquetesVirtuales.push({
                id: null,
                guia: qrData.code || guiaNorm,
                nombreDestinatario: qrData.details?.nombre || 'Destinatario QR',
                telefono: qrData.details?.telefono || 'No disponible',
                direccion: qrData.details?.direccion || 'Dirección no disponible',
                coordenadas: { lat: 4.65, lng: -74.06 },
                contenido: 'Sin descripción',
                valorDeclarado: parsearMonto(qrData.details?.total),
                totalCobrar: parsearMonto(qrData.details?.total),
                observaciones: 'Registro escaneado desde QR (sin coincidencia en paquetes asignados)',
                estado: 'pendiente',
                qrEscaneado: true,
                qrScanData: qrData,
                esVirtualQR: true
            });
        });

        paquetes = [...paquetesDB, ...paquetesVirtuales];

        mostrarPaquetes();
        actualizarEstadisticas();
        aplicarDeepLinkDesdeURL();
    } catch (error) {
        console.error(error);
        document.getElementById('listaPaquetes').innerHTML = '<p style="padding:1rem;color:#b91c1c;">Error cargando paquetes.</p>';
    }
}

function normalizarGuia(valor) {
    const raw = (valor || '').toString().trim().toUpperCase();
    if (!raw) return '';
    return raw.startsWith('QR-') ? raw.substring(3) : raw;
}

function aplicarDeepLinkDesdeURL() {
    if (deepLinkProcesado) return;
    const params = new URLSearchParams(window.location.search);
    const guiaParam = params.get('guia');
    if (!guiaParam) return;

    const accion = (params.get('accion') || '').toLowerCase();
    const guiaBuscada = normalizarGuia(decodeURIComponent(guiaParam));
    if (!guiaBuscada) return;

    const paquete = paquetes.find(p => normalizarGuia(p.guia) === guiaBuscada);
    if (!paquete) {
        alert(`No se encontró la guía ${guiaBuscada} en tus paquetes asignados.`);
        deepLinkProcesado = true;
        return;
    }

    deepLinkProcesado = true;
    const paqueteRef = paquete.id === null ? `virtual_${paquete.guia}` : paquete.id;

    if (accion === 'entregar' && paquete.estado !== 'entregado') {
        abrirFormularioEntrega(paqueteRef);
    } else {
        verDetallePaquete(paqueteRef);
    }
}

// ============================================
// VISUALIZACIÓN DE PAQUETES
// ============================================
function mostrarPaquetes(filtro = 'todos') {
    const contenedor = document.getElementById('listaPaquetes');
    let paquetesFiltrados = paquetes;
    
    if (filtro === 'todos') {
        // En la vista principal ocultamos los entregados (ahora están en Historial)
        paquetesFiltrados = paquetes.filter(p => p.estado !== 'entregado');
    } else {
        paquetesFiltrados = paquetes.filter(p => p.estado === filtro);
    }

    // Si hay QR escaneados, priorizar esos sin ocultar todo si no hubo coincidencia
    if (qrEscaneadosMap.size > 0) {
        const soloEscaneados = paquetesFiltrados.filter(p => p.qrEscaneado);
        if (soloEscaneados.length > 0) {
            paquetesFiltrados = soloEscaneados;
        }
    }

    paquetesFiltrados.sort((a, b) => Number(b.qrEscaneado) - Number(a.qrEscaneado));
    
    if (paquetesFiltrados.length === 0) {
        contenedor.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: #64748b;">
                <p style="font-size: 3rem; margin-bottom: 1rem;">📭</p>
                <p style="font-size: 1.2rem; font-weight: 500;">No hay paquetes ${filtro === 'todos' ? '' : 'con estado: ' + filtro}</p>
            </div>
        `;
        return;
    }
    
    contenedor.innerHTML = paquetesFiltrados.map(paquete => {
        const estadoTexto = {
            'pendiente': 'Pendiente',
            'en_ruta': 'En Ruta',
            'entregado': 'Entregado'
        };
        
        const botonEntregar = paquete.estado === 'entregado' 
            ? '<button class="btn-entregar-rapido" disabled>✓ Entregado</button>'
            : `<button class="btn-entregar-rapido" onclick="abrirFormularioEntrega(${paquete.id === null ? `'virtual_${paquete.guia}'` : paquete.id})">✓ Entregar</button>`;
        const accionDetalle = paquete.id === null ? `'virtual_${paquete.guia}'` : paquete.id;
        
        return `
            <div class="tarjeta-paquete ${paquete.estado}">
                <div class="paquete-header">
                    <div class="guia-numero">${paquete.guia}</div>
                    <span class="badge ${paquete.estado}">${estadoTexto[paquete.estado]}</span>
                </div>
                ${paquete.qrEscaneado ? `<div class="info-row"><span class="info-row-label">QR:</span><span class="info-row-valor" style="color:#059669;font-weight:600;">Escaneado</span></div>` : ''}
                <div class="paquete-info">
                    <div class="info-row">
                        <span class="info-row-label">Destinatario</span>
                        <span class="info-row-valor destinatario-nombre">${paquete.nombreDestinatario}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-row-label">Teléfono</span>
                        <span class="info-row-valor">${paquete.telefono || 'No disponible'}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-row-label">Dirección</span>
                        <span class="info-row-valor direccion-breve">${paquete.direccion}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-row-label">Contenido</span>
                        <span class="info-row-valor">${paquete.contenido}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-row-label">Total a Cobrar</span>
                        <span class="info-row-valor valor-declarado">${formatearMoneda(paquete.totalCobrar || 0)}</span>
                    </div>
                </div>
                
                <div class="paquete-acciones">
                    <button class="btn-ver-detalle" onclick="verDetallePaquete(${accionDetalle})">
                        👁️ Ver Detalle
                    </button>
                    ${botonEntregar}
                </div>
            </div>
        `;
    }).join('');
}

// ============================================
// ESTADÍSTICAS
// ============================================
function actualizarEstadisticas() {
    const baseStats = (qrEscaneadosMap.size > 0 && paquetes.some(p => p.qrEscaneado))
        ? paquetes.filter(p => p.qrEscaneado)
        : paquetes;

    const total = baseStats.length;
    const pendientes = baseStats.filter(p => p.estado === 'pendiente').length;
    const enRuta = baseStats.filter(p => p.estado === 'en_ruta').length;
    const entregados = baseStats.filter(p => p.estado === 'entregado').length;
    
    totalRecaudoHoy = baseStats
        .filter(p => p.estado === 'entregado' && p.infoEntrega)
        .reduce((sum, p) => sum + (p.infoEntrega.recaudo || 0), 0);
    
    document.getElementById('totalPaquetes').textContent = total;
    document.getElementById('enRuta').textContent = enRuta;
    document.getElementById('entregados').textContent = entregados;
    document.getElementById('totalRecaudo').textContent = formatearMoneda(totalRecaudoHoy);
    
    document.getElementById('contadorPendientes').textContent = pendientes;
    document.getElementById('contadorEntregados').textContent = entregados;
}

// ============================================
// DETALLE DE PAQUETE
// ============================================
function verDetallePaquete(id) {
    feedbackClick();
    paqueteActual = paquetes.find(p => p.id === id || `virtual_${p.guia}` === id);
    
    if (!paqueteActual) return;
    
    document.getElementById('vistaLista').classList.add('oculto');
    document.getElementById('vistaDetalle').classList.remove('oculto');
    
    const estadoTexto = {
        'pendiente': 'Pendiente',
        'en_ruta': 'En Ruta',
        'entregado': 'Entregado'
    };
    
    document.getElementById('detalleGuia').textContent = paqueteActual.guia;
    
    const badgeEstado = document.getElementById('detalleEstadoBadge');
    badgeEstado.className = `badge-grande ${paqueteActual.estado}`;
    badgeEstado.textContent = estadoTexto[paqueteActual.estado];
    
    document.getElementById('detalleNombreDestinatario').textContent = paqueteActual.nombreDestinatario;
    document.getElementById('detalleTelefono').textContent = paqueteActual.telefono;
    document.getElementById('detalleDireccion').textContent = paqueteActual.direccion;
    document.getElementById('detalleContenido').textContent = paqueteActual.contenido;
    document.getElementById('detalleValorDeclarado').textContent = formatearMoneda(paqueteActual.valorDeclarado);
    document.getElementById('detalleObservaciones').textContent = paqueteActual.observaciones;
    
    // Mostrar/ocultar botón de entrega según estado
    const btnContainer = document.getElementById('btnEntregarContainer');
    const infoEntrega = document.getElementById('infoEntregaRealizada');
    
    if (paqueteActual.estado === 'entregado') {
        btnContainer.classList.add('oculto');
        infoEntrega.classList.remove('oculto');
        
        const info = paqueteActual.infoEntrega;
        document.getElementById('entregaRecibio').textContent = info.nombreRecibe;
        document.getElementById('entregaParentesco').textContent = info.parentesco;
        document.getElementById('entregaDocumento').textContent = info.documento;
        document.getElementById('entregaRecaudo').textContent = formatearMoneda(info.recaudo);
        document.getElementById('entregaFecha').textContent = info.fecha;
    } else {
        btnContainer.classList.remove('oculto');
        infoEntrega.classList.add('oculto');
    }
}

// ============================================
// NAVEGACIÓN
// ============================================
function abrirNavegacion(paquete = paqueteActual) {
    if (!paquete) return;
    
    feedbackClick();
    
    const lat = paquete.coordenadas.lat;
    const lng = paquete.coordenadas.lng;
    const direccion = encodeURIComponent(paquete.direccion);
    
    const esIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const esAndroid = /Android/.test(navigator.userAgent);
    
    if (esIOS) {
        const urlAppleMaps = `maps://maps.apple.com/?daddr=${lat},${lng}&dirflg=d`;
        window.location.href = urlAppleMaps;
        setTimeout(() => {
            window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
        }, 2000);
    } else if (esAndroid) {
        const urlNativo = `google.navigation:q=${lat},${lng}&mode=d`;
        window.location.href = urlNativo;
        setTimeout(() => {
            window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
        }, 1500);
    } else {
        window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
    }
}

function llamarDestinatario(telefono = paqueteActual?.telefono) {
    if (!telefono) return;
    feedbackClick();
    window.location.href = `tel:${telefono}`;
}

// ============================================
// FORMULARIO DE ENTREGA
// ============================================
function abrirFormularioEntrega(id) {
    feedbackClick();
    paqueteActual = paquetes.find(p => p.id === id || `virtual_${p.guia}` === id);
    
    if (!paqueteActual || paqueteActual.estado === 'entregado') {
        alert('Este paquete ya fue entregado');
        return;
    }
    
    document.getElementById('vistaLista').classList.add('oculto');
    document.getElementById('vistaDetalle').classList.add('oculto');
    document.getElementById('vistaFormularioEntrega').classList.remove('oculto');
    
    document.getElementById('formGuia').textContent = `Guía: ${paqueteActual.guia}`;
    
    // Resetear formulario
    document.getElementById('formEntrega').reset();
    fotosEntrega = [];
    document.getElementById('previsualizacionFotosEntrega').innerHTML = '';

    // Prefill desde datos escaneados del QR si existen
    const qrData = paqueteActual.qrScanData;
    if (qrData) {
        const nombre = qrData.details?.nombre || paqueteActual.nombreDestinatario || '';
        const recaudo = parsearMonto(qrData.details?.total);
        const observacionBase = qrData.rawText ? `QR: ${qrData.rawText}` : '';

        const elNombre = document.getElementById('nombreRecibe');
        const elParentesco = document.getElementById('parentesco');
        const elRecaudo = document.getElementById('recaudo');
        const elObs = document.getElementById('observacionesEntrega');

        if (elNombre && nombre) elNombre.value = nombre;
        if (elParentesco) elParentesco.value = 'destinatario';
        if (elRecaudo && recaudo > 0) elRecaudo.value = recaudo;
        if (elObs && observacionBase) elObs.value = observacionBase;
    }
    
    actualizarFechaHora();
    actualizarInfoGPS();
}

// ============================================
// MANEJO DE FOTOS
// ============================================
document.getElementById('btnTomarFotoEntrega')?.addEventListener('click', function() {
    document.getElementById('inputFotosEntrega').click();
});

document.getElementById('inputFotosEntrega')?.addEventListener('change', function(e) {
    const archivos = Array.from(e.target.files);
    archivos.forEach(archivo => {
        if (archivo.type.startsWith('image/')) {
            procesarFoto(archivo);
        }
    });
    e.target.value = '';
});

function procesarFoto(archivo) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            const maxWidth = 1920;
            const maxHeight = 1920;
            let width = img.width;
            let height = img.height;
            
            if (width > height) {
                if (width > maxWidth) {
                    height *= maxWidth / width;
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width *= maxHeight / height;
                    height = maxHeight;
                }
            }
            
            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);
            
            const fotoData = canvas.toDataURL('image/jpeg', 0.85);
            
            const foto = {
                id: Date.now() + Math.random(),
                data: fotoData,
                fecha: new Date(),
                ubicacion: ubicacionActual ? {...ubicacionActual} : null,
                nombreArchivo: archivo.name
            };
            
            fotosEntrega.push(foto);
            mostrarPreviewFoto(foto);
            feedbackExito();
        };
        img.src = e.target.result;
    };
    
    reader.readAsDataURL(archivo);
}

function mostrarPreviewFoto(foto) {
    const contenedor = document.getElementById('previsualizacionFotosEntrega');
    
    const div = document.createElement('div');
    div.className = 'foto-item';
    div.innerHTML = `
        <img src="${foto.data}" alt="Foto de entrega">
        <div class="foto-meta">
            ${formatearFechaHora(foto.fecha)}<br>
            ${foto.ubicacion ? `${foto.ubicacion.lat.toFixed(6)}, ${foto.ubicacion.lng.toFixed(6)}` : 'Sin GPS'}
        </div>
        <button type="button" class="btn-eliminar-foto" onclick="eliminarFotoEntrega(${foto.id})">×</button>
    `;
    
    contenedor.appendChild(div);
}

function eliminarFotoEntrega(fotoId) {
    feedbackClick();
    fotosEntrega = fotosEntrega.filter(f => f.id !== fotoId);
    
    const contenedor = document.getElementById('previsualizacionFotosEntrega');
    contenedor.innerHTML = '';
    fotosEntrega.forEach(foto => mostrarPreviewFoto(foto));
}

// ============================================
// MANEJO DE PARENTESCO
// ============================================
document.getElementById('parentesco')?.addEventListener('change', function() {
    const otroInput = document.getElementById('parentescoOtro');
    if (this.value === 'otro') {
        otroInput.classList.remove('oculto');
        otroInput.required = true;
    } else {
        otroInput.classList.add('oculto');
        otroInput.required = false;
        otroInput.value = '';
    }
});

// ============================================
// ENVÍO DE FORMULARIO
// ============================================
document.getElementById('formEntrega')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar fotos
    if (fotosEntrega.length === 0) {
        feedbackError();
        alert('Debes tomar al menos una foto de la entrega');
        return;
    }
    
    // Validar GPS
    if (!ubicacionActual) {
        if (!confirm('No se pudo obtener la ubicación GPS. ¿Deseas continuar de todos modos?')) {
            return;
        }
    }
    
    const parentesco = document.getElementById('parentesco').value;
    const parentescoFinal = parentesco === 'otro' 
        ? document.getElementById('parentescoOtro').value 
        : parentesco;
    
    const datosEntrega = {
        nombreRecibe: document.getElementById('nombreRecibe').value.trim(),
        parentesco: parentescoFinal,
        documento: document.getElementById('documento').value.trim(),
        recaudo: parseFloat(document.getElementById('recaudo').value) || 0,
        observaciones: document.getElementById('observacionesEntrega').value.trim(),
        fotos: fotosEntrega,
        fecha: new Date().toISOString(),
        ubicacion: ubicacionActual ? {...ubicacionActual} : null
    };
    
    completarEntrega(datosEntrega);
});

async function completarEntrega(datosEntrega) {
    feedbackClick();
    mostrarLoading(true, 'Registrando entrega...');

    try {
        const payload = {
            paquete_id: paqueteActual.id || 0,
            numero_guia: paqueteActual.guia,
            ...datosEntrega
        };

        const resp = await fetch(`${API_MIS_PAQUETES}?action=entregar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await resp.json();
        if (!json.success) {
            throw new Error(json.message || 'No se pudo registrar la entrega');
        }

        paqueteActual.estado = 'entregado';
        paqueteActual.infoEntrega = {
            ...datosEntrega,
            fecha: formatearFechaHora(new Date(datosEntrega.fecha))
        };
        eliminarQREscaneadoLocal(paqueteActual.guia);
        cargarQREscaneadosLocales();
        actualizarPaqueteEnLista(paqueteActual);
        actualizarEstadisticas();
        refrescarListaActual();
        document.getElementById('vistaFormularioEntrega').classList.add('oculto');
        feedbackExito();
        mostrarConfirmacionEntrega(datosEntrega);
    } catch (error) {
        feedbackError();
        alert(error.message);
    } finally {
        mostrarLoading(false);
    }
}

function mostrarConfirmacionEntrega(datos) {
    const modal = document.getElementById('modalConfirmacion');
    const resumen = document.getElementById('resumenEntrega');
    
    resumen.innerHTML = `
        <div class="resumen-item">
            <span>Guía:</span>
            <strong>${paqueteActual.guia}</strong>
        </div>
        <div class="resumen-item">
            <span>Recibió:</span>
            <strong>${datos.nombreRecibe}</strong>
        </div>
        <div class="resumen-item">
            <span>Documento:</span>
            <strong>${datos.documento}</strong>
        </div>
        <div class="resumen-item">
            <span>Recaudo:</span>
            <strong style="color: var(--color-exito);">${formatearMoneda(datos.recaudo)}</strong>
        </div>
        <div class="resumen-item">
            <span>Fotos:</span>
            <strong>${datos.fotos.length}</strong>
        </div>
    `;
    
    modal.classList.remove('oculto');
}

// ============================================
// OPTIMIZACIÓN DE RUTA
// ============================================
document.getElementById('btnOrdenarRuta')?.addEventListener('click', function() {
    feedbackClick();
    
    if (!ubicacionActual) {
        alert('Necesitas activar el GPS para optimizar la ruta');
        return;
    }
    
    mostrarLoading(true, 'Optimizando ruta...');
    
    setTimeout(() => {
        // Ordenar por distancia (simple)
        const pendientes = paquetes.filter(p => p.estado === 'pendiente' || p.estado === 'en_ruta');
        
        pendientes.sort((a, b) => {
            const distA = calcularDistancia(
                ubicacionActual.lat, ubicacionActual.lng,
                a.coordenadas.lat, a.coordenadas.lng
            );
            const distB = calcularDistancia(
                ubicacionActual.lat, ubicacionActual.lng,
                b.coordenadas.lat, b.coordenadas.lng
            );
            return distA - distB;
        });
        
        // Reorganizar array
        const entregados = paquetes.filter(p => p.estado === 'entregado');
        paquetes = [...pendientes, ...entregados];
        
        mostrarLoading(false);
        mostrarPaquetes();
        feedbackExito();
        
        alert(`✓ Ruta optimizada\n\nSe organizaron ${pendientes.length} paquetes por distancia desde tu ubicación actual.`);
    }, 1500);
});

// ============================================
// VISTA MAPA
// ============================================
document.getElementById('btnVerMapa')?.addEventListener('click', function() {
    feedbackClick();
    document.getElementById('vistaLista').classList.add('oculto');
    document.getElementById('vistaMapa').classList.remove('oculto');
});

document.getElementById('btnCerrarMapa')?.addEventListener('click', function() {
    feedbackClick();
    document.getElementById('vistaMapa').classList.add('oculto');
    document.getElementById('vistaLista').classList.remove('oculto');
});

// ============================================
// CONFIGURACIÓN DE EVENT LISTENERS
// ============================================
function configurarEventListeners() {
    // Filtros
    document.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            feedbackClick();
            document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');
            mostrarPaquetes(this.dataset.filtro);
        });
    });
    
    // Navegación
    document.getElementById('btnVolverDetalle')?.addEventListener('click', function() {
        feedbackClick();
        volverALista();
    });
    
    document.getElementById('btnNavegar')?.addEventListener('click', () => abrirNavegacion());
    document.getElementById('btnLlamarDetalle')?.addEventListener('click', () => llamarDestinatario());
    document.getElementById('btnEntregar')?.addEventListener('click', function() {
        abrirFormularioEntrega(paqueteActual.id);
    });
    
    // Formulario
    document.getElementById('btnCancelarEntrega')?.addEventListener('click', function() {
        feedbackClick();
        if (confirm('¿Estás seguro de cancelar? Se perderán los datos ingresados.')) {
            volverALista();
        }
    });
    
    // Modal
    document.getElementById('btnCerrarConfirmacion')?.addEventListener('click', function() {
        feedbackClick();
        document.getElementById('modalConfirmacion').classList.add('oculto');
        volverALista();
    });
    
    // Actualizar
    document.getElementById('btnActualizar')?.addEventListener('click', function() {
        feedbackClick();
        mostrarLoading(true, 'Actualizando...');
        setTimeout(() => {
            cargarPaquetes();
            mostrarLoading(false);
            feedbackExito();
        }, 1000);
    });
}

// ============================================
// UTILIDADES
// ============================================
function volverALista() {
    document.getElementById('vistaDetalle').classList.add('oculto');
    document.getElementById('vistaFormularioEntrega').classList.add('oculto');
    document.getElementById('vistaLista').classList.remove('oculto');
    paqueteActual = null;
    fotosEntrega = [];
}

function actualizarPaqueteEnLista(paquete) {
    const index = paquetes.findIndex(p => p.id === paquete.id);
    if (index !== -1) {
        paquetes[index] = paquete;
    }
}

function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(valor);
}

function formatearFechaHora(fecha) {
    const d = new Date(fecha);
    const dia = d.getDate().toString().padStart(2, '0');
    const mes = (d.getMonth() + 1).toString().padStart(2, '0');
    const año = d.getFullYear();
    const hora = d.getHours().toString().padStart(2, '0');
    const min = d.getMinutes().toString().padStart(2, '0');
    return `${dia}/${mes}/${año} ${hora}:${min}`;
}

function actualizarFechaHora() {
    const elemento = document.getElementById('infoFechaEntrega');
    if (elemento) {
        elemento.textContent = formatearFechaHora(new Date());
    }
    setTimeout(actualizarFechaHora, 1000);
}

function calcularDistancia(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function mostrarLoading(mostrar, texto = 'Procesando...') {
    const overlay = document.getElementById('loadingOverlay');
    const textoElement = document.getElementById('loadingTexto');
    
    if (mostrar) {
        textoElement.textContent = texto;
        overlay.classList.remove('oculto');
    } else {
        overlay.classList.add('oculto');
    }
}

function cargarQREscaneadosLocales() {
    qrEscaneadosMap = new Map();
    try {
        const raw = localStorage.getItem(STORAGE_SCANNED_QR_KEY);
        if (!raw) return;
        const data = JSON.parse(raw);
        if (!Array.isArray(data)) return;
        data.forEach(item => {
            const guia = normalizarGuia(item?.code);
            if (!guia) return;
            qrEscaneadosMap.set(guia, item);
        });
    } catch (error) {
        console.warn('No se pudieron cargar QR escaneados locales', error);
    }
}

function eliminarQREscaneadoLocal(guia) {
    try {
        const raw = localStorage.getItem(STORAGE_SCANNED_QR_KEY);
        if (!raw) return;
        const data = JSON.parse(raw);
        if (!Array.isArray(data)) return;
        const normalizada = normalizarGuia(guia);
        const nuevaLista = data.filter(item => normalizarGuia(item?.code) !== normalizada);
        localStorage.setItem(STORAGE_SCANNED_QR_KEY, JSON.stringify(nuevaLista));
    } catch (error) {
        console.warn('No se pudo limpiar QR local entregado', error);
    }
}

function parsearMonto(valor) {
    if (valor === null || valor === undefined) return 0;
    const limpio = String(valor).replace(/[^\d]/g, '');
    return limpio ? Number(limpio) : 0;
}

function refrescarListaActual() {
    const filtroActivo = document.querySelector('.filtro-btn.activo')?.dataset?.filtro || 'todos';
    mostrarPaquetes(filtroActivo);
}

// ============================================
// FUNCIONES GLOBALES
// ============================================
window.verDetallePaquete = verDetallePaquete;
window.abrirFormularioEntrega = abrirFormularioEntrega;
window.eliminarFotoEntrega = eliminarFotoEntrega;
