// ============================================
// CONFIGURACIÓN Y VARIABLES GLOBALES
// ============================================
let paquetes = [];
let paqueteActual = null;
let fotoEntregaPrincipal = null;
let fotoEntregaAdicional = null;
let fotoNovedad = null;
let fotoNovedadAdicional = null;
let tipoNovedadActual = null;
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

function mostrarToast(mensaje, tipo = 'info', opts = {}) {
    const toastFn = window.EcoBikeUI?.toast;
    if (typeof toastFn === 'function') {
        toastFn(mensaje, { type: tipo, ...opts });
        return;
    }
    console.log(`[${tipo}] ${mensaje}`);
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', async function() {
    mostrarLoading(true, 'Cargando paquetes...');
    try {
        inicializarApp();
        cargarQREscaneadosLocales();
        await cargarPaquetes(); // Esperamos a que los datos carguen
        configurarEventListeners();
        inicializarGeolocalización();
        actualizarFechaHora();
    } finally {
        mostrarLoading(false); // Aseguramos que el loader desaparezca y libere el scroll
    }
});

function inicializarApp() {
    console.log('App de paquetes inicializada');

    // Forzar scroll nativo y eliminar bloqueos residuales
    document.body.style.overflow = 'auto';
    document.body.style.height = 'auto';
    document.body.style.touchAction = 'auto';
    document.documentElement.style.overflow = 'auto';
    document.documentElement.style.height = 'auto';
    document.documentElement.style.touchAction = 'auto';
    
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
                actualizarInfoGPS();
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
    const textoGPS = ubicacionActual
        ? `${ubicacionActual.lat.toFixed(6)}, ${ubicacionActual.lng.toFixed(6)}`
        : 'GPS no disponible';

    const elementoNovedad = document.getElementById('infoGPSNovedad');
    if (elementoNovedad) elementoNovedad.textContent = textoGPS;
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
            const estadoBase = row.estado || 'pendiente';
            const estadoFront = estadoBase === 'entregado'
                ? 'entregado'
                : (estadoBase === 'cancelado' ? 'cancelado' : 'pendiente');
            const estadoVisual = (row.ultima_novedad_tipo === 'aplazado' && estadoFront === 'pendiente')
                ? 'aplazado'
                : estadoFront;
            const guiaNormalizada = normalizarGuia(row.numero_guia);
            const qrData = qrEscaneadosMap.get(guiaNormalizada);

            const item = {
                id: Number(row.id),
                guia: row.numero_guia,
                remitente: row.remitente || 'Cliente no disponible',
                nombreDestinatario: row.destinatario_nombre,
                telefono: row.destinatario_telefono,
                direccion: row.direccion_destino,
                coordenadas: { lat: 4.65, lng: -74.06 },
                contenido: row.descripcion_contenido || 'Sin descripción',
                instruccionesEntrega: row.instrucciones_entrega || 'Sin instrucciones de entrega',
                valorDeclarado: Number(row.costo_envio || 0),
                totalCobrar: Number(row.recaudo_esperado || 0),
                observaciones: row.instrucciones_entrega || '',
                estado: estadoVisual,
                estadoBase: estadoFront,
                qrEscaneado: !!qrData,
                qrScanData: qrData || null,
                ultimaNovedadTipo: row.ultima_novedad_tipo || null,
                ultimaNovedadDescripcion: row.ultima_novedad_descripcion || null,
                ultimaNovedadFecha: row.ultima_novedad_fecha || null
            };

            if (row.nombre_receptor) {
                item.infoEntrega = {
                    nombreRecibe: row.nombre_receptor,
                    parentesco: row.parentesco_cargo || 'N/A',
                    documento: row.documento_receptor || 'N/A',
                    recaudo: Number(row.recaudo_real || 0),
                    fecha: row.fecha_entrega ? formatearFechaHora(row.fecha_entrega) : '',
                    fotos: [],
                    observaciones: row.observaciones_entrega || null,
                    fotoPrincipal: row.foto_entrega || null,
                    fotoAdicional: row.foto_adicional || null
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
                remitente: 'Cliente no disponible',
                nombreDestinatario: qrData.details?.nombre || 'Destinatario QR',
                telefono: qrData.details?.telefono || 'No disponible',
                direccion: qrData.details?.direccion || 'Dirección no disponible',
                coordenadas: { lat: 4.65, lng: -74.06 },
                contenido: 'Sin descripción',
                instruccionesEntrega: qrData.details?.direccion ? `Entregar en: ${qrData.details.direccion}` : 'Sin instrucciones de entrega',
                valorDeclarado: parsearMonto(qrData.details?.total),
                totalCobrar: parsearMonto(qrData.details?.total),
                observaciones: 'Registro escaneado desde QR (sin coincidencia en paquetes asignados)',
                estado: 'pendiente',
                estadoBase: 'pendiente',
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
        mostrarToast(`No se encontró la guía ${guiaBuscada} en tus paquetes asignados.`, 'warning', { title: 'No encontrado' });
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
        // En la vista principal ocultamos entregados y cancelados.
        paquetesFiltrados = paquetes.filter(p => p.estado !== 'entregado' && p.estado !== 'cancelado');
    } else {
        if (filtro === 'pendiente') {
            paquetesFiltrados = paquetes.filter(p => p.estado === 'pendiente' || p.estado === 'aplazado');
        } else {
            paquetesFiltrados = paquetes.filter(p => p.estado === filtro);
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
            'aplazado': 'Aplazado',
            'entregado': 'Entregado',
            'cancelado': 'Cancelado'
        };

        const referenciaPaquete = paquete.id === null ? `'virtual_${paquete.guia}'` : paquete.id;
        const bloqueado = paquete.estado === 'entregado' || paquete.estado === 'cancelado';
        const botonEntregar = bloqueado
            ? `<button class="btn-entregar-rapido" disabled>${paquete.estado === 'cancelado' ? '✕ Cancelado' : '✓ Entregado'}</button>`
            : `<button class="btn-entregar-rapido" onclick="abrirFormularioEntrega(${referenciaPaquete})">✓ Entregar</button>`;
        const botonesNovedad = bloqueado
            ? `
                <button class="btn-aplazar" disabled>Aplazado</button>
                <button class="btn-cancelar-paquete" disabled>Cancelado</button>
            `
            : `
                <button class="btn-aplazar" onclick="abrirFormularioNovedad(${referenciaPaquete}, 'aplazado')">Aplazado</button>
                <button class="btn-cancelar-paquete" onclick="abrirFormularioNovedad(${referenciaPaquete}, 'cancelado')">Cancelado</button>
            `;
        
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
                        <span class="info-row-label">Instrucciones de Entrega</span>
                        <span class="info-row-valor">${paquete.instruccionesEntrega || 'Sin instrucciones de entrega'}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-row-label">Total a Cobrar</span>
                        <span class="info-row-valor valor-declarado">${formatearMoneda(paquete.totalCobrar || 0)}</span>
                    </div>
                </div>
                
                <div class="paquete-acciones">
                    ${botonEntregar}
                    <div class="paquete-acciones-secundarias">
                        ${botonesNovedad}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// ============================================
// ESTADÍSTICAS
// ============================================
function actualizarEstadisticas() {
    const paquetesActivos = paquetes.filter(p => p.estado !== 'cancelado');

    const total = paquetesActivos.length;
    const pendientes = paquetesActivos.filter(p => p.estado === 'pendiente' || p.estado === 'aplazado').length;
    const enRuta = paquetesActivos.filter(p => p.estado === 'en_ruta').length;
    const entregados = paquetesActivos.filter(p => p.estado === 'entregado').length;
    
    totalRecaudoHoy = paquetesActivos
        .filter(p => p.estado === 'entregado' && p.infoEntrega)
        .reduce((sum, p) => sum + (p.infoEntrega.recaudo || 0), 0);

    const totalPaquetesEl = document.getElementById('totalPaquetes');
    const enRutaEl = document.getElementById('enRuta');
    const entregadosEl = document.getElementById('entregados');
    const totalRecaudoEl = document.getElementById('totalRecaudo');
    const contadorPendientesEl = document.getElementById('contadorPendientes');
    const contadorEntregadosEl = document.getElementById('contadorEntregados');

    if (totalPaquetesEl) totalPaquetesEl.textContent = total;
    if (enRutaEl) enRutaEl.textContent = enRuta;
    if (entregadosEl) entregadosEl.textContent = entregados;
    if (totalRecaudoEl) totalRecaudoEl.textContent = formatearMoneda(totalRecaudoHoy);
    if (contadorPendientesEl) contadorPendientesEl.textContent = pendientes;
    if (contadorEntregadosEl) contadorEntregadosEl.textContent = entregados;

    actualizarVisibilidadCierreJornada();
}

function obtenerResumenCierre(baseStats = null) {
    const fuente = Array.isArray(baseStats) ? baseStats : paquetes.filter(p => p.estado !== 'cancelado');
    const total = fuente.length;
    const entregados = fuente.filter(p => p.estado === 'entregado').length;
    const aplazados = fuente.filter(p => p.estado === 'aplazado').length;
    const cancelados = paquetes.filter(p => p.estado === 'cancelado').length;
    const pendientesReales = fuente.filter(p => p.estado === 'pendiente' || p.estado === 'en_ruta').length;
    const recaudo_total = fuente
        .filter(p => p.estado === 'entregado' && p.infoEntrega)
        .reduce((sum, p) => sum + (p.infoEntrega.recaudo || 0), 0);

    return {
        total,
        entregados,
        aplazados,
        cancelados,
        pendientesReales,
        recaudo_total
    };
}

function actualizarVisibilidadCierreJornada() {
    const section = document.getElementById('cierreJornadaSection');
    if (!section) return;

    const resumen = obtenerResumenCierre();
    const habilitado = resumen.total > 0 && resumen.pendientesReales === 0;
    section.classList.toggle('oculto', !habilitado);
}

// ============================================
// DETALLE DE PAQUETE
// ============================================
function verDetallePaquete(id) {
    feedbackClick();
    paqueteActual = paquetes.find(p => p.id === id || `virtual_${p.guia}` === id);
    
    if (!paqueteActual) return;
    
    window.scrollTo(0, 0); // Resetear posición al inicio
    document.getElementById('vistaLista').classList.add('oculto');
    document.getElementById('vistaDetalle').classList.remove('oculto');
    
    const estadoTexto = {
        'pendiente': 'Pendiente',
        'en_ruta': 'En Ruta',
        'aplazado': 'Aplazado',
        'entregado': 'Entregado',
        'cancelado': 'Cancelado'
    };
    
    document.getElementById('detalleGuia').textContent = paqueteActual.guia;
    const detalleRemitente = document.getElementById('detalleRemitente');
    if (detalleRemitente) detalleRemitente.textContent = paqueteActual.remitente || 'Cliente no disponible';
    
    const badgeEstado = document.getElementById('detalleEstadoBadge');
    badgeEstado.className = `badge-grande ${paqueteActual.estado}`;
    badgeEstado.textContent = estadoTexto[paqueteActual.estado];
    
    document.getElementById('detalleNombreDestinatario').textContent = paqueteActual.nombreDestinatario;
    document.getElementById('detalleTelefono').textContent = paqueteActual.telefono;
    document.getElementById('detalleDireccion').textContent = paqueteActual.direccion;
    document.getElementById('detalleContenido').textContent = paqueteActual.instruccionesEntrega || 'Sin instrucciones de entrega';
    document.getElementById('detalleValorDeclarado').textContent = formatearMoneda(paqueteActual.valorDeclarado);
    document.getElementById('detalleTotalCobrar').textContent = formatearMoneda(paqueteActual.totalCobrar);
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
        document.getElementById('entregaObservaciones').textContent = info.observaciones || 'Sin observaciones.';

        const fotosContainer = document.getElementById('entregaFotos');
        if (fotosContainer) {
            fotosContainer.innerHTML = '';
            if (info.fotoPrincipal) {
                fotosContainer.innerHTML += `<a href="${info.fotoPrincipal}" target="_blank" rel="noopener noreferrer"><img src="${info.fotoPrincipal}" alt="Foto de entrega" class="foto-evidencia"></a>`;
            }
            if (info.fotoAdicional) {
                fotosContainer.innerHTML += `<a href="${info.fotoAdicional}" target="_blank" rel="noopener noreferrer"><img src="${info.fotoAdicional}" alt="Foto adicional" class="foto-evidencia"></a>`;
            }
            if (!info.fotoPrincipal && !info.fotoAdicional) {
                fotosContainer.innerHTML = '<p class="text-muted">No hay fotos de evidencia.</p>';
            }
        }
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
        mostrarToast('Este paquete ya fue entregado', 'info', { title: 'Sin cambios' });
        return;
    }
    if (paqueteActual.estado === 'cancelado') {
        mostrarToast('Este paquete está cancelado', 'info', { title: 'Sin cambios' });
        return;
    }
    
    window.scrollTo(0, 0); // Resetear posición al inicio
    document.getElementById('vistaLista').classList.add('oculto');
    document.getElementById('vistaDetalle').classList.add('oculto');
    document.getElementById('vistaFormularioEntrega').classList.remove('oculto');
    
    document.getElementById('formGuia').textContent = `Guía: ${paqueteActual.guia}`;
    
    // Resetear formulario
    document.getElementById('formEntrega').reset();
    const destinatarioEntrega = document.getElementById('nombreDestinatarioEntrega');
    if (destinatarioEntrega) {
        destinatarioEntrega.value = paqueteActual.nombreDestinatario || '';
    }
    fotoEntregaPrincipal = null;
    fotoEntregaAdicional = null;
    document.getElementById('previsualizacionFotoEntrega').innerHTML = '';
    document.getElementById('previsualizacionFotoEntregaAdicional').innerHTML = '';
    const recaudoEsperado = Number(paqueteActual.totalCobrar || 0);
    const elTotalRecaudar = document.getElementById('totalRecaudar');
    const elTotalRecaudado = document.getElementById('totalRecaudado');
    const elRecibioCambios = document.getElementById('recibioCambios');
    if (elTotalRecaudar) elTotalRecaudar.value = formatearMontoInput(recaudoEsperado);
    if (elTotalRecaudado) elTotalRecaudado.value = formatearMontoInput(recaudoEsperado);
    if (elRecibioCambios) elRecibioCambios.checked = false;

    // Prefill desde datos escaneados del QR si existen
    const qrData = paqueteActual.qrScanData;
    if (qrData) {
        const nombre = qrData.details?.nombre || paqueteActual.nombreDestinatario || '';
        const recaudo = parsearMonto(qrData.details?.total);
        const observacionBase = qrData.rawText ? `QR: ${qrData.rawText}` : '';

        const elNombre = document.getElementById('nombreRecibe');
        const elParentesco = document.getElementById('parentesco');
        const elObs = document.getElementById('observacionesEntrega');

        // Se comenta para que el campo "Nombre de quien recibe" quede vacío y deba llenarse manualmente
        // if (elNombre && nombre) elNombre.value = nombre;
        // if (elParentesco) elParentesco.value = 'destinatario';
        
        if (elTotalRecaudar && recaudo > 0 && recaudoEsperado === 0) elTotalRecaudar.value = formatearMontoInput(recaudo);
        if (elTotalRecaudado && recaudo > 0) elTotalRecaudado.value = formatearMontoInput(recaudo);

        // Se comenta para que el campo de observaciones no se llene con los datos del QR
        // if (elObs && observacionBase) elObs.value = observacionBase;
    }
    
    actualizarFechaHora();
    actualizarInfoGPS();
}

// ============================================
// MANEJO DE FOTOS
// ============================================
function abrirSelectorImagen(inputId) {
    const input = document.getElementById(inputId);
    if (input) input.click();
}

function manejarCambioImagen(event, callback) {
    const archivo = event.target.files?.[0];
    if (archivo && archivo.type.startsWith('image/')) {
        callback(archivo);
    }
    event.target.value = '';
}

document.getElementById('inputFotoEntrega')?.addEventListener('change', function(e) {
    manejarCambioImagen(e, archivo => procesarFotoEntrega(archivo, 'principal'));
});

document.getElementById('inputFotoEntregaAdicional')?.addEventListener('change', function(e) {
    manejarCambioImagen(e, archivo => procesarFotoEntrega(archivo, 'adicional'));
});

document.getElementById('inputFotoEntregaGaleria')?.addEventListener('change', function(e) {
    manejarCambioImagen(e, archivo => procesarFotoEntrega(archivo, 'principal'));
});

document.getElementById('inputFotoEntregaAdicionalGaleria')?.addEventListener('change', function(e) {
    manejarCambioImagen(e, archivo => procesarFotoEntrega(archivo, 'adicional'));
});

function procesarFotoEntrega(archivo, tipo = 'principal') {
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
            
            if (tipo === 'adicional') {
                fotoEntregaAdicional = foto;
                mostrarPreviewFotoEntrega(foto, 'adicional');
            } else {
                fotoEntregaPrincipal = foto;
                mostrarPreviewFotoEntrega(foto, 'principal');
            }
            feedbackExito();
        };
        img.src = e.target.result;
    };
    
    reader.readAsDataURL(archivo);
}

function mostrarPreviewFotoEntrega(foto, tipo = 'principal') {
    const contenedor = document.getElementById(
        tipo === 'adicional' ? 'previsualizacionFotoEntregaAdicional' : 'previsualizacionFotoEntrega'
    );
    if (!contenedor) return;
    contenedor.innerHTML = '';
    
    const div = document.createElement('div');
    div.className = 'foto-item';
    div.innerHTML = `
        <img src="${foto.data}" alt="Foto de entrega">
        <div class="foto-meta">
            ${formatearFechaHora(foto.fecha)}<br>
            ${foto.ubicacion ? `${foto.ubicacion.lat.toFixed(6)}, ${foto.ubicacion.lng.toFixed(6)}` : 'Sin GPS'}
        </div>
        <button type="button" class="btn-eliminar-foto" onclick="eliminarFotoEntrega('${tipo}')">×</button>
    `;
    
    contenedor.appendChild(div);
}

function eliminarFotoEntrega(tipo = 'principal') {
    feedbackClick();
    if (tipo === 'adicional') {
        fotoEntregaAdicional = null;
        const contenedorAdicional = document.getElementById('previsualizacionFotoEntregaAdicional');
        if (contenedorAdicional) contenedorAdicional.innerHTML = '';
        return;
    }

    fotoEntregaPrincipal = null;
    const contenedor = document.getElementById('previsualizacionFotoEntrega');
    if (contenedor) contenedor.innerHTML = '';
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
document.getElementById('formEntrega')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validar fotos
    if (!fotoEntregaPrincipal) {
        feedbackError();
        mostrarToast('Debes tomar la foto principal de la entrega', 'warning', { title: 'Falta evidencia' });
        return;
    }
    
    // Validar GPS
    if (!ubicacionActual) {
        const continuar = await mostrarModalDecision('Continuar sin GPS', 'No se pudo obtener la ubicación GPS. ¿Deseas continuar de todos modos?');
        if (!continuar) return;
    }
    
    const parentesco = document.getElementById('parentesco').value;
    const parentescoFinal = parentesco === 'otro' 
        ? document.getElementById('parentescoOtro').value 
        : parentesco;
    const observacionesBase = document.getElementById('observacionesEntrega').value.trim();
    const recibioCambios = !!document.getElementById('recibioCambios').checked;
    const observacionesFinal = [observacionesBase, `Recibio cambios: ${recibioCambios ? 'Si' : 'No'}`]
        .filter(Boolean)
        .join('\n');
    
    const datosEntrega = {
        nombreRecibe: document.getElementById('nombreRecibe').value.trim(),
        parentesco: parentescoFinal,
        documento: document.getElementById('documento').value.trim(),
        recaudoEsperado: parsearMonto(document.getElementById('totalRecaudar').value),
        recaudo: parsearMonto(document.getElementById('totalRecaudado').value),
        recibioCambios,
        observaciones: observacionesFinal,
        fotos: [fotoEntregaPrincipal, fotoEntregaAdicional].filter(Boolean),
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
        mostrarToast('Entrega exitosa', 'success', { title: 'Listo' });
        mostrarConfirmacionEntrega(datosEntrega);
    } catch (error) {
        feedbackError();
        mostrarToast(error.message || 'Ocurrió un error al registrar la entrega', 'error', { title: 'Error', duration: 4200 });
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
            <span>Total a recaudar:</span>
            <strong>${formatearMoneda(datos.recaudoEsperado || 0)}</strong>
        </div>
        <div class="resumen-item">
            <span>Recaudo:</span>
            <strong style="color: var(--color-exito);">${formatearMoneda(datos.recaudo)}</strong>
        </div>
        <div class="resumen-item">
            <span>Recibió cambios:</span>
            <strong>${datos.recibioCambios ? 'Sí' : 'No'}</strong>
        </div>
        <div class="resumen-item">
            <span>Fotos:</span>
            <strong>${datos.fotos.length}</strong>
        </div>
    `;
    
    modal.classList.remove('oculto');
}

// ============================================
// NOVEDADES DE ENTREGA (APLAZADO / CANCELADO)
// ============================================
function abrirFormularioNovedad(id, tipo) {
    feedbackClick();
    paqueteActual = paquetes.find(p => p.id === id || `virtual_${p.guia}` === id);
    if (!paqueteActual) return;

    if (paqueteActual.estado === 'entregado') {
        mostrarToast('Este paquete ya fue entregado.', 'info', { title: 'Sin cambios' });
        return;
    }
    if (paqueteActual.estado === 'cancelado') {
        mostrarToast('Este paquete ya fue cancelado.', 'info', { title: 'Sin cambios' });
        return;
    }

    tipoNovedadActual = tipo === 'cancelado' ? 'cancelado' : 'aplazado';
    fotoNovedad = null;
    fotoNovedadAdicional = null;

    window.scrollTo(0, 0); // Resetear posición al inicio
    document.getElementById('vistaLista').classList.add('oculto');
    document.getElementById('vistaDetalle').classList.add('oculto');
    document.getElementById('vistaFormularioEntrega').classList.add('oculto');
    document.getElementById('vistaFormularioNovedad').classList.remove('oculto');

    document.getElementById('novedadTitulo').textContent = tipoNovedadActual === 'cancelado'
        ? '✕ Cancelar Entrega'
        : '⏳ Aplazar Entrega';
    document.getElementById('novedadGuia').textContent = `Guía: ${paqueteActual.guia}`;
    document.getElementById('btnEnviarNovedad').textContent = tipoNovedadActual === 'cancelado' ? 'Cancelar paquete' : 'Registrar aplazamiento';
    document.getElementById('descripcionNovedad').value = '';
    document.getElementById('previsualizacionFotoNovedad').innerHTML = '';
    const prevNovedadAdicional = document.getElementById('previsualizacionFotoNovedadAdicional');
    if (prevNovedadAdicional) prevNovedadAdicional.innerHTML = '';

    actualizarFechaHora();
    actualizarInfoGPS();
}

document.getElementById('btnTomarFotoNovedad')?.addEventListener('click', function() {
    abrirSelectorImagen('inputFotoNovedad');
});

document.getElementById('btnSubirFotoNovedad')?.addEventListener('click', function() {
    abrirSelectorImagen('inputFotoNovedadGaleria');
});

document.getElementById('inputFotoNovedad')?.addEventListener('change', function(e) {
    manejarCambioImagen(e, archivo => procesarFotoNovedad(archivo, false));
});

document.getElementById('inputFotoNovedadGaleria')?.addEventListener('change', function(e) {
    manejarCambioImagen(e, archivo => procesarFotoNovedad(archivo, false));
});

document.getElementById('btnTomarFotoNovedadAdicional')?.addEventListener('click', function() {
    abrirSelectorImagen('inputFotoNovedadAdicional');
});

document.getElementById('btnSubirFotoNovedadAdicional')?.addEventListener('click', function() {
    abrirSelectorImagen('inputFotoNovedadAdicionalGaleria');
});

document.getElementById('inputFotoNovedadAdicional')?.addEventListener('change', function(e) {
    manejarCambioImagen(e, archivo => procesarFotoNovedad(archivo, true));
});

document.getElementById('inputFotoNovedadAdicionalGaleria')?.addEventListener('change', function(e) {
    manejarCambioImagen(e, archivo => procesarFotoNovedad(archivo, true));
});

function procesarFotoNovedad(archivo, esAdicional) {
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
            } else if (height > maxHeight) {
                width *= maxHeight / height;
                height = maxHeight;
            }

            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);

            const objFoto = {
                id: Date.now(),
                data: canvas.toDataURL('image/jpeg', 0.85),
                fecha: new Date(),
                ubicacion: ubicacionActual ? { ...ubicacionActual } : null
            };

            if (esAdicional) {
                fotoNovedadAdicional = objFoto;
                renderPreviewFotoNovedadAdicional();
            } else {
                fotoNovedad = objFoto;
                renderPreviewFotoNovedad();
            }
            feedbackExito();
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(archivo);
}

function renderPreviewFotoNovedad() {
    const contenedor = document.getElementById('previsualizacionFotoNovedad');
    if (!contenedor) return;
    if (!fotoNovedad) {
        contenedor.innerHTML = '';
        return;
    }

    contenedor.innerHTML = `
        <div class="foto-item">
            <img src="${fotoNovedad.data}" alt="Foto de evidencia">
            <div class="foto-meta">
                ${formatearFechaHora(fotoNovedad.fecha)}<br>
                ${fotoNovedad.ubicacion ? `${fotoNovedad.ubicacion.lat.toFixed(6)}, ${fotoNovedad.ubicacion.lng.toFixed(6)}` : 'Sin GPS'}
            </div>
            <button type="button" class="btn-eliminar-foto" onclick="eliminarFotoNovedad()">×</button>
        </div>
    `;
}

function eliminarFotoNovedad() {
    feedbackClick();
    fotoNovedad = null;
    renderPreviewFotoNovedad();
}

function renderPreviewFotoNovedadAdicional() {
    const contenedor = document.getElementById('previsualizacionFotoNovedadAdicional');
    if (!contenedor) return;
    if (!fotoNovedadAdicional) {
        contenedor.innerHTML = '';
        return;
    }

    contenedor.innerHTML = `
        <div class="foto-item">
            <img src="${fotoNovedadAdicional.data}" alt="Foto adicional de evidencia">
            <div class="foto-meta">
                ${formatearFechaHora(fotoNovedadAdicional.fecha)}<br>
                ${fotoNovedadAdicional.ubicacion ? `${fotoNovedadAdicional.ubicacion.lat.toFixed(6)}, ${fotoNovedadAdicional.ubicacion.lng.toFixed(6)}` : 'Sin GPS'}
            </div>
            <button type="button" class="btn-eliminar-foto" onclick="eliminarFotoNovedadAdicional()">×</button>
        </div>
    `;
}

function eliminarFotoNovedadAdicional() {
    feedbackClick();
    fotoNovedadAdicional = null;
    renderPreviewFotoNovedadAdicional();
}

document.getElementById('formNovedad')?.addEventListener('submit', function(e) {
    e.preventDefault();
    registrarNovedad();
});

async function registrarNovedad() {
    const descripcion = document.getElementById('descripcionNovedad').value.trim();
    if (!tipoNovedadActual) {
        feedbackError();
        mostrarToast('No se definió el tipo de novedad.', 'error', { title: 'Error' });
        return;
    }
    if (tipoNovedadActual === 'cancelado') {
        const confirmar = await mostrarModalDecision('Confirmar cancelación', '¿Seguro que deseas cancelar esta entrega?');
        if (!confirmar) return;
    }
    if (!descripcion) {
        feedbackError();
        mostrarToast('Debes ingresar una descripción.', 'warning', { title: 'Campos incompletos' });
        return;
    }
    if (!fotoNovedad) {
        feedbackError();
        mostrarToast('Debes tomar una foto de evidencia.', 'warning', { title: 'Falta evidencia' });
        return;
    }

    mostrarLoading(true, tipoNovedadActual === 'cancelado' ? 'Cancelando paquete...' : 'Registrando aplazamiento...');
    try {
        const payload = {
            paquete_id: paqueteActual.id || 0,
            numero_guia: paqueteActual.guia,
            tipo: tipoNovedadActual,
            descripcion,
            foto: { data: fotoNovedad.data },
            foto_adicional: fotoNovedadAdicional ? { data: fotoNovedadAdicional.data } : undefined,
            ubicacion: ubicacionActual ? { ...ubicacionActual } : null
        };

        const resp = await fetch(`${API_MIS_PAQUETES}?action=registrar_novedad`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await resp.json();
        if (!json.success) {
            throw new Error(json.message || 'No se pudo registrar la novedad');
        }

        if (tipoNovedadActual === 'cancelado') {
            paqueteActual.estado = 'cancelado';
            paqueteActual.estadoBase = 'cancelado';
            eliminarQREscaneadoLocal(paqueteActual.guia);
            cargarQREscaneadosLocales();
        } else {
            paqueteActual.estado = 'aplazado';
            paqueteActual.estadoBase = 'pendiente';
        }
        paqueteActual.ultimaNovedadTipo = tipoNovedadActual;
        paqueteActual.ultimaNovedadDescripcion = descripcion;
        paqueteActual.ultimaNovedadFecha = new Date().toISOString();

    actualizarPaqueteEnLista(paqueteActual);
    actualizarEstadisticas();
    refrescarListaActual();
    feedbackExito();
    if (tipoNovedadActual === 'cancelado') {
        mostrarToast('La entrega se canceló', 'success', { title: 'Listo' });
    } else {
        mostrarToast('Entrega aplazada', 'success', { title: 'Listo' });
    }
    volverALista();
} catch (error) {
    feedbackError();
    mostrarToast(error.message || 'Ocurrió un error al registrar la novedad', 'error', { title: 'Error', duration: 4200 });
    } finally {
        mostrarLoading(false);
    }
}

// ============================================
// OPTIMIZACIÓN DE RUTA
// ============================================
// ============================================
// OPTIMIZACIÓN DE RUTA (Deshabilitado)
// ============================================

async function guardarCierreJornada() {
    const resumen = obtenerResumenCierre();
    if (resumen.total === 0 || resumen.pendientesReales > 0) {
        mostrarToast('Aún no cumples las condiciones para guardar el cierre de jornada.', 'warning', { title: 'Pendientes' });
        return;
    }

    const observacionDefault = resumen.aplazados > 0
        ? `Cierre con ${resumen.aplazados} paquete(s) aplazado(s).`
        : 'Cierre con todas las entregas completadas.';
    const observacion = prompt('Observación del cierre (opcional):', observacionDefault);
    if (observacion === null) return;

    const detalle = paquetes.map(p => ({
        id: p.id,
        guia: p.guia,
        estado: p.estado,
        estado_base: p.estadoBase || p.estado,
        qr_escaneado: !!p.qrEscaneado
    }));

    mostrarLoading(true, 'Guardando cierre de jornada...');
    try {
        const payload = {
            total_paquetes: resumen.total,
            entregados: resumen.entregados,
            aplazados: resumen.aplazados,
            cancelados: resumen.cancelados,
            recaudo_total: resumen.recaudo_total,
            observacion: (observacion || '').trim(),
            detalle
        };

        const resp = await fetch(`${API_MIS_PAQUETES}?action=guardar_cierre_jornada`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await resp.json();
        if (!json.success) {
            throw new Error(json.message || 'No se pudo guardar el cierre');
        }

        feedbackExito();
        mostrarToast('Cierre de jornada guardado correctamente.', 'success', { title: 'Listo' });
    } catch (error) {
        feedbackError();
        mostrarToast(error.message || 'Ocurrió un error al guardar el cierre', 'error', { title: 'Error', duration: 4200 });
    } finally {
        mostrarLoading(false);
    }
}

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
        if (!paqueteActual) return;
        const ref = paqueteActual.id === null ? `virtual_${paqueteActual.guia}` : paqueteActual.id;
        abrirFormularioEntrega(ref);
    });
    
    // Formulario
    document.getElementById('btnVolverEntrega')?.addEventListener('click', function() {
        feedbackClick();
        mostrarModalDecision('Volver al listado', 'Si vuelves ahora, se perderán los datos ingresados en este formulario.').then(aceptado => {
            if (aceptado) volverALista();
        });
    });

    document.getElementById('btnVolverNovedad')?.addEventListener('click', function() {
        feedbackClick();
        mostrarModalDecision('Volver al listado', 'Si vuelves ahora, se perderán los datos ingresados en este formulario.').then(aceptado => {
            if (aceptado) volverALista();
        });
    });

    document.getElementById('totalRecaudado')?.addEventListener('input', function() {
        this.value = formatearMontoInput(parsearMonto(this.value));
    });

    document.getElementById('btnDecisionCancelar')?.addEventListener('click', function() {
        resolverModalDecision(false);
    });

    document.getElementById('btnDecisionAceptar')?.addEventListener('click', function() {
        resolverModalDecision(true);
    });

    document.getElementById('modalDecision')?.addEventListener('click', function(e) {
        if (e.target === this) {
            resolverModalDecision(false);
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
        mostrarLoading(true, 'Sincronizando...');
        cargarPaquetes().finally(() => {
            mostrarLoading(false);
            feedbackExito();
        });
    });

    document.getElementById('btnGuardarCierreJornada')?.addEventListener('click', function() {
        feedbackClick();
        guardarCierreJornada();
    });
}

// ============================================
// UTILIDADES
// ============================================
function volverALista() {
    document.body.style.overflow = ''; 
    document.documentElement.style.overflow = '';
    document.body.style.touchAction = 'auto';

    window.scrollTo(0, 0); // Volver arriba
    document.getElementById('vistaDetalle').classList.add('oculto');
    document.getElementById('vistaFormularioEntrega').classList.add('oculto');
    document.getElementById('vistaFormularioNovedad').classList.add('oculto');
    document.getElementById('vistaLista').classList.remove('oculto');
    paqueteActual = null;
    fotoEntregaPrincipal = null;
    fotoEntregaAdicional = null;
    fotoNovedad = null;
    tipoNovedadActual = null;
}

function actualizarPaqueteEnLista(paquete) {
    const index = paquetes.findIndex(p => p.id === paquete.id || (p.id === null && paquete.id === null && p.guia === paquete.guia));
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
    const fechaActual = formatearFechaHora(new Date());
    const elementoEntrega = document.getElementById('infoFechaEntrega');
    if (elementoEntrega) elementoEntrega.textContent = fechaActual;
    const elementoNovedad = document.getElementById('infoFechaNovedad');
    if (elementoNovedad) elementoNovedad.textContent = fechaActual;
    setTimeout(actualizarFechaHora, 1000);
}

function formatearMontoInput(valor) {
    return '$ ' + Number(valor || 0).toLocaleString('es-CO');
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
        if (textoElement) textoElement.textContent = texto;
        if (overlay) overlay.classList.remove('oculto');
        document.body.style.setProperty('overflow', 'hidden', 'important');
        document.documentElement.style.setProperty('overflow', 'hidden', 'important');
    } else {
        if (overlay) overlay.classList.add('oculto');
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        document.body.style.touchAction = 'auto';
        document.documentElement.style.touchAction = 'auto';
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

let resolverDecisionActual = null;

function mostrarModalDecision(titulo, mensaje) {
    const modal = document.getElementById('modalDecision');
    const tituloEl = document.getElementById('modalDecisionTitulo');
    const mensajeEl = document.getElementById('modalDecisionMensaje');
    if (!modal || !tituloEl || !mensajeEl) return Promise.resolve(false);

    tituloEl.textContent = titulo;
    mensajeEl.textContent = mensaje;
    modal.classList.remove('oculto');
    document.body.style.setProperty('overflow', 'hidden', 'important');
    document.documentElement.style.setProperty('overflow', 'hidden', 'important');

    return new Promise(resolve => {
        resolverDecisionActual = resolve;
    });
}

function resolverModalDecision(valor) {
    const modal = document.getElementById('modalDecision');
    if (modal) modal.classList.add('oculto');
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';

    if (resolverDecisionActual) {
        const resolve = resolverDecisionActual;
        resolverDecisionActual = null;
        resolve(valor);
    }
}

// ============================================
// FUNCIONES GLOBALES
// ============================================
window.verDetallePaquete = verDetallePaquete;
window.abrirFormularioEntrega = abrirFormularioEntrega;
window.abrirFormularioNovedad = abrirFormularioNovedad;
window.eliminarFotoEntrega = eliminarFotoEntrega;
window.eliminarFotoNovedad = eliminarFotoNovedad;
