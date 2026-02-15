// ============================================
// CONFIGURACIÓN Y VARIABLES GLOBALES
// ============================================
let paquetes = [];
let paqueteActual = null;
let fotosEntrega = [];
let ubicacionActual = null;
let watchId = null;
let totalRecaudoHoy = 0;

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
function cargarPaquetes() {
    // Datos de ejemplo - En producción vendrían de API
    paquetes = [
        {
            id: 1,
            guia: 'GUA-2024-0001',
            nombreDestinatario: 'María González Pérez',
            telefono: '+57 300 123 4567',
            direccion: 'Calle 72 #10-34, Apartamento 302, Edificio Torres del Parque, Bogotá',
            coordenadas: { lat: 4.6537, lng: -74.0577 },
            contenido: 'Documentos legales',
            valorDeclarado: 50000,
            observaciones: 'Tocar el timbre. No dejar en portería.',
            estado: 'pendiente'
        },
        {
            id: 2,
            guia: 'GUA-2024-0002',
            nombreDestinatario: 'Carlos Rodríguez',
            telefono: '+57 310 987 6543',
            direccion: 'Carrera 15 #93-40, Of. 501, Centro Empresarial, Bogotá',
            coordenadas: { lat: 4.6764, lng: -74.0533 },
            contenido: 'Equipos electrónicos',
            valorDeclarado: 2500000,
            observaciones: 'URGENTE: Entregar antes de 12pm. Preguntar en recepción.',
            estado: 'en_ruta'
        },
        {
            id: 3,
            guia: 'GUA-2024-0003',
            nombreDestinatario: 'Ana Martínez López',
            telefono: '+57 320 456 7890',
            direccion: 'Avenida 68 #45-23, Casa 12, Conjunto Residencial Los Pinos, Bogotá',
            coordenadas: { lat: 4.6583, lng: -74.0856 },
            contenido: 'Ropa y accesorios',
            valorDeclarado: 150000,
            observaciones: 'Llamar 10 minutos antes de llegar.',
            estado: 'pendiente'
        },
        {
            id: 4,
            guia: 'GUA-2024-0004',
            nombreDestinatario: 'Pedro Sánchez',
            telefono: '+57 315 234 5678',
            direccion: 'Calle 100 #19-61, Bogotá',
            coordenadas: { lat: 4.6870, lng: -74.0470 },
            contenido: 'Medicamentos',
            valorDeclarado: 80000,
            observaciones: 'Requiere recaudo de $80.000 en efectivo.',
            estado: 'entregado',
            infoEntrega: {
                nombreRecibe: 'Pedro Sánchez',
                parentesco: 'destinatario',
                documento: '1234567890',
                recaudo: 80000,
                observaciones: 'Entrega exitosa',
                fecha: '2024-02-05 09:30',
                fotos: []
            }
        },
        {
            id: 5,
            guia: 'GUA-2024-0005',
            nombreDestinatario: 'Laura Ramírez',
            telefono: '+57 301 654 3210',
            direccion: 'Transversal 45 #123-89, Bogotá',
            coordenadas: { lat: 4.6920, lng: -74.0420 },
            contenido: 'Libros y material educativo',
            valorDeclarado: 120000,
            observaciones: 'Horario preferido: 2pm - 5pm',
            estado: 'pendiente'
        }
    ];
    
    mostrarPaquetes();
    actualizarEstadisticas();
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
            : `<button class="btn-entregar-rapido" onclick="abrirFormularioEntrega(${paquete.id})">✓ Entregar</button>`;
        
        return `
            <div class="tarjeta-paquete ${paquete.estado}">
                <div class="paquete-header">
                    <div class="guia-numero">${paquete.guia}</div>
                    <span class="badge ${paquete.estado}">${estadoTexto[paquete.estado]}</span>
                </div>
                
                <div class="paquete-info">
                    <div class="info-row">
                        <span class="info-row-label">Destinatario</span>
                        <span class="info-row-valor destinatario-nombre">${paquete.nombreDestinatario}</span>
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
                        <span class="info-row-label">Valor Declarado</span>
                        <span class="info-row-valor valor-declarado">${formatearMoneda(paquete.valorDeclarado)}</span>
                    </div>
                </div>
                
                <div class="paquete-acciones">
                    <button class="btn-ver-detalle" onclick="verDetallePaquete(${paquete.id})">
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
    const total = paquetes.length;
    const pendientes = paquetes.filter(p => p.estado === 'pendiente').length;
    const enRuta = paquetes.filter(p => p.estado === 'en_ruta').length;
    const entregados = paquetes.filter(p => p.estado === 'entregado').length;
    
    totalRecaudoHoy = paquetes
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
    paqueteActual = paquetes.find(p => p.id === id);
    
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
    paqueteActual = paquetes.find(p => p.id === id);
    
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

function completarEntrega(datosEntrega) {
    feedbackClick();
    mostrarLoading(true, 'Registrando entrega...');
    
    setTimeout(() => {
        // Actualizar paquete
        paqueteActual.estado = 'entregado';
        paqueteActual.infoEntrega = {
            ...datosEntrega,
            fecha: formatearFechaHora(new Date(datosEntrega.fecha))
        };
        
        actualizarPaqueteEnLista(paqueteActual);
        actualizarEstadisticas();
        
        mostrarLoading(false);
        document.getElementById('vistaFormularioEntrega').classList.add('oculto');
        
        feedbackExito();
        mostrarConfirmacionEntrega(datosEntrega);
        
        console.log('Entrega completada:', datosEntrega);
    }, 2000);
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

// ============================================
// FUNCIONES GLOBALES
// ============================================
window.verDetallePaquete = verDetallePaquete;
window.abrirFormularioEntrega = abrirFormularioEntrega;
window.eliminarFotoEntrega = eliminarFotoEntrega;