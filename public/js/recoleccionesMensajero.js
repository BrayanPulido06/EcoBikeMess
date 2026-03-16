// ============================================
// CONFIGURACIÓN Y VARIABLES GLOBALES
// ============================================
let recolecciones = [];
let recoleccionActual = null;
let fotosRecoleccion = [];
let ubicacionActual = null;
let mensajeroId = 'MENSAJERO_001'; // Se obtendría dinámicamente en producción
let watchId = null; // Para tracking de GPS continuo
const API_RECOLECCIONES = '../../controller/recoleccionesMensajeroController.php';

// ============================================
// FUNCIONES DE FEEDBACK TÁCTIL
// ============================================
function vibrar(patron = [10]) {
    if ('vibrate' in navigator) {
        navigator.vibrate(patron);
    }
}

function feedbackTactilExito() {
    vibrar([20, 50, 20]);
}

function feedbackTactilError() {
    vibrar([50, 100, 50, 100, 50]);
}

function feedbackTactilClick() {
    vibrar([5]);
}

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    inicializarApp();
    cargarRecolecciones();
    configurarEventListeners();
    solicitarPermisos();
    inicializarGeolocalización();
});

// ============================================
// PERMISOS Y NOTIFICACIONES
// ============================================
function solicitarPermisos() {
    // Solicitar permiso para notificaciones
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                console.log('Permisos de notificación otorgados');
            }
        });
    }
    
    // Solicitar permiso de GPS explícitamente para asegurar que aparezca el prompt
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            (pos) => console.log('GPS activo y permiso otorgado'),
            (err) => console.warn('GPS pendiente de permiso o denegado', err)
        );
    }
}

function inicializarGeolocalización() {
    if ('geolocation' in navigator) {
        // Opciones optimizadas para móvil
        const opciones = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 30000
        };
        
        // Obtener ubicación inicial
        navigator.geolocation.getCurrentPosition(
            position => {
                ubicacionActual = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy
                };
                console.log('Ubicación inicial obtenida:', ubicacionActual);
            },
            error => {
                console.error('Error obteniendo ubicación inicial:', error);
            },
            opciones
        );
        
        // Tracking continuo cuando hay recolección activa
        watchId = navigator.geolocation.watchPosition(
            position => {
                ubicacionActual = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp
                };
                
                // Si hay recolección en curso, verificar proximidad
                if (recoleccionActual && recoleccionActual.estado === 'en_curso') {
                    verificarProximidad();
                }
            },
            error => console.error('Error en tracking GPS:', error),
            opciones
        );
    }
}

function verificarProximidad() {
    if (!ubicacionActual || !recoleccionActual) return;
    
    const distancia = calcularDistancia(
        ubicacionActual.lat,
        ubicacionActual.lng,
        recoleccionActual.coordenadas.lat,
        recoleccionActual.coordenadas.lng
    );
    
    // Notificar cuando esté cerca (menos de 100 metros)
    if (distancia < 0.1 && !recoleccionActual.notificacionProximidad) {
        recoleccionActual.notificacionProximidad = true;
        vibrar([100, 50, 100]);
        mostrarNotificacionPush(
            '📍 Ya casi llegas',
            'Estás a menos de 100 metros del punto de recolección',
            {id: recoleccionActual.id}
        );
    }
}

function mostrarNotificacionPush(titulo, mensaje, datos) {
    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification(titulo, {
            body: mensaje,
            icon: '📦',
            badge: '📦',
            tag: 'recoleccion-' + datos.id,
            requireInteraction: true,
            data: datos
        });
        
        notification.onclick = function() {
            window.focus();
            verDetalleRecoleccion(datos.id);
            notification.close();
        };
    }
}

// ============================================
// CARGA DE DATOS
// ============================================
async function cargarRecolecciones() {
    try {
        const resp = await fetch(`${API_RECOLECCIONES}?action=listar`);
        const json = await resp.json();
        if (!json.success) {
            throw new Error(json.message || 'No se pudieron cargar recolecciones');
        }

        if (json.mensajero) {
            mensajeroId = String(json.mensajero.id);
            const nombreEl = document.getElementById('mensajeroNombre');
            if (nombreEl) {
                nombreEl.textContent = json.mensajero.nombre || 'Mis Recolecciones';
            }
        }

        recolecciones = (json.data || []).map(r => ({
            id: Number(r.id),
            numeroOrden: r.numero_orden,
            estado: r.estado === 'asignada' ? 'en_curso' : r.estado,
            prioridad: r.prioridad || 'normal',
            fechaAsignacion: r.fecha_asignacion,
            direccion: r.direccion_recoleccion,
            coordenadas: {
                lat: Number(r.coordenadas_lat || 0),
                lng: Number(r.coordenadas_lng || 0)
            },
            nombreContacto: r.nombre_contacto,
            telefono: r.telefono_contacto,
            cantidadPaquetes: Number(r.cantidad_estimada || 0),
            cantidadReal: Number(r.cantidad_real || 0),
            horarioSugerido: r.horario_preferido || 'Sin horario',
            instrucciones: r.descripcion_paquetes || r.observaciones_recoleccion || '',
            fotoRecoleccion: r.foto_recoleccion || '',
            paquetes: []
        }));

        mostrarRecolecciones();
    } catch (error) {
        console.error(error);
        document.getElementById('listaRecolecciones').innerHTML = '<p style="padding:1rem;color:#b91c1c;">Error cargando recolecciones.</p>';
    }
}

// ============================================
// VISUALIZACIÓN DE RECOLECCIONES
// ============================================
function mostrarRecolecciones(filtro = 'todas') {
    const contenedor = document.getElementById('listaRecolecciones');
    const recoleccionesFiltradas = filtro === 'todas' 
        ? recolecciones 
        : recolecciones.filter(r => r.estado === filtro);
    
    if (recoleccionesFiltradas.length === 0) {
        contenedor.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: #64748b;">
                <p style="font-size: 3rem; margin-bottom: 1rem;">📭</p>
                <p style="font-size: 1.2rem; font-weight: 500;">No hay recolecciones ${filtro === 'todas' ? '' : filtro + 's'}</p>
            </div>
        `;
        return;
    }
    
    contenedor.innerHTML = recoleccionesFiltradas.map(recoleccion => {
        const estadoTexto = {
            'pendiente': 'Pendiente',
            'en_curso': 'En Curso',
            'completada': 'Completada'
        };
        
        const prioridadTexto = {
            'urgente': 'Urgente',
            'normal': 'Normal'
        };
        
        return `
            <div class="tarjeta-recoleccion">
                <div class="tarjeta-header">
                    <div class="numero-orden">${recoleccion.numeroOrden}</div>
                    <div class="badges">
                        <span class="badge ${recoleccion.estado}">${estadoTexto[recoleccion.estado]}</span>
                        <span class="badge ${recoleccion.prioridad}">${prioridadTexto[recoleccion.prioridad]}</span>
                    </div>
                </div>
                <div class="tarjeta-info">
                    <div class="info-row">
                        🕐 <strong>Asignado:</strong> ${formatearFecha(recoleccion.fechaAsignacion)}
                    </div>
                    <div class="info-row">
                        📦 <strong>Paquetes:</strong> ${recoleccion.cantidadPaquetes}
                    </div>
                    <div class="info-row">
                        📍 <strong>Dirección:</strong> ${recoleccion.direccion}
                    </div>
                </div>
                <div class="tarjeta-acciones">
                    ${recoleccion.estado === 'completada' ? `
                        <button class="btn-recolectar-card" onclick="verDetalleRecoleccion(${recoleccion.id})">
                            👁️ Ver detalles
                        </button>
                    ` : `
                        <button class="btn-recolectar-card" onclick="abrirRecoleccion(${recoleccion.id})">
                            📦 Realizar Recolección
                        </button>
                    `}
                </div>
            </div>
        `;
    }).join('');
}

function abrirRecoleccion(id) {
    recoleccionActual = recolecciones.find(r => r.id === id);
    if (!recoleccionActual) return;
    mostrarFormularioRecoleccion();
    if (!Array.isArray(recoleccionActual.paquetes) || recoleccionActual.paquetes.length === 0) {
        cargarDetalleRecoleccion();
    } else {
        renderGuiasFormulario();
    }
}

// ============================================
// DETALLE DE RECOLECCIÓN
// ============================================
function verDetalleRecoleccion(id) {
    recoleccionActual = recolecciones.find(r => r.id === id);
    
    if (!recoleccionActual) return;
    
    // Ocultar vista lista, mostrar vista detalle
    document.getElementById('vistaLista').classList.remove('vista-activa');
    document.getElementById('vistaLista').classList.add('oculto');
    document.getElementById('vistaDetalle').classList.remove('oculto');
    document.getElementById('vistaDetalle').classList.add('vista-activa');
    
    // Llenar información
    document.getElementById('detalleNumeroOrden').textContent = recoleccionActual.numeroOrden;
    
    const estadoBadge = document.getElementById('detalleEstado');
    estadoBadge.className = `badge ${recoleccionActual.estado}`;
    estadoBadge.textContent = {
        'pendiente': 'Pendiente',
        'en_curso': 'En Curso',
        'completada': 'Completada'
    }[recoleccionActual.estado];
    
    const prioridadBadge = document.getElementById('detallePrioridad');
    prioridadBadge.className = `badge ${recoleccionActual.prioridad}`;
    prioridadBadge.textContent = {
        'urgente': 'Urgente',
        'normal': 'Normal'
    }[recoleccionActual.prioridad];
    
    document.getElementById('detalleFechaAsignacion').textContent = formatearFecha(recoleccionActual.fechaAsignacion);
    const detalleDireccion = document.getElementById('detalleDireccion');
    if (detalleDireccion) detalleDireccion.textContent = recoleccionActual.direccion;
    document.getElementById('detalleCoordenadas').textContent = 
        `${recoleccionActual.coordenadas.lat}, ${recoleccionActual.coordenadas.lng}`;
    document.getElementById('detalleNombreContacto').textContent = recoleccionActual.nombreContacto;
    document.getElementById('detalleTelefono').textContent = recoleccionActual.telefono;
    document.getElementById('detalleCantidadPaquetes').textContent = recoleccionActual.cantidadPaquetes;
    document.getElementById('detalleHorarioSugerido').textContent = recoleccionActual.horarioSugerido;
    document.getElementById('detalleInstrucciones').textContent = recoleccionActual.instrucciones;
    
    // Mostrar/ocultar botones según estado
    const btnLlegue = document.getElementById('btnLleguePunto');
    const seccionPaquetes = document.getElementById('seccionPaquetesAsignados');
    const seccionEvidencia = document.getElementById('seccionEvidencia');
    const seccionUbicacion = document.getElementById('seccionUbicacion');
    const seccionContacto = document.getElementById('seccionContacto');
    
    if (recoleccionActual.estado === 'en_curso') {
        btnLlegue.classList.remove('oculto');
        if (seccionPaquetes) seccionPaquetes.classList.remove('oculto');
        if (seccionEvidencia) seccionEvidencia.classList.add('oculto');
        if (seccionUbicacion) seccionUbicacion.classList.remove('oculto');
        if (seccionContacto) seccionContacto.classList.remove('oculto');
    } else {
        btnLlegue.classList.add('oculto');
        if (seccionPaquetes) seccionPaquetes.classList.remove('oculto');
        if (seccionEvidencia && recoleccionActual.estado === 'completada') {
            seccionEvidencia.classList.remove('oculto');
        }
        if (recoleccionActual.estado === 'completada') {
            if (seccionUbicacion) seccionUbicacion.classList.add('oculto');
            if (seccionContacto) seccionContacto.classList.add('oculto');
        } else {
            if (seccionUbicacion) seccionUbicacion.classList.remove('oculto');
            if (seccionContacto) seccionContacto.classList.remove('oculto');
        }
    }
    
    cargarDetalleRecoleccion();
}

async function cargarDetalleRecoleccion() {
    if (!recoleccionActual) return;
    try {
        const resp = await fetch(`${API_RECOLECCIONES}?action=detalle&recoleccion_id=${recoleccionActual.id}`);
        const json = await resp.json();
        if (!json.success) return;
        recoleccionActual.paquetes = json.paquetes || [];
        renderPaquetesAsignados();
    } catch (error) {
        console.error('Error cargando detalle de recolección', error);
    }
}

function renderPaquetesAsignados() {
    const totalEl = document.getElementById('detalleTotalPaquetes');
    const recogidosEl = document.getElementById('detalleCantidadRecogida');
    const listaEl = document.getElementById('detalleListaPaquetes');
    if (!totalEl || !listaEl) return;

    const paquetes = Array.isArray(recoleccionActual?.paquetes) ? recoleccionActual.paquetes : [];
    const total = paquetes.length || recoleccionActual.cantidadPaquetes || 0;
    totalEl.textContent = total;
    if (recogidosEl) {
        recogidosEl.textContent = recoleccionActual.cantidadReal || 0;
    }

    if (paquetes.length === 0) {
        listaEl.innerHTML = '<p style="color:#64748b;">No hay guías asociadas.</p>';
    } else {
        listaEl.innerHTML = paquetes.map(p => `
            <div class="paquete-recoleccion-item">
                <strong>${p.numero_guia}</strong>
                <span>${p.destinatario_nombre || 'Sin nombre'}</span>
            </div>
        `).join('');
    }

    renderEvidenciaRecoleccion();
}

function renderEvidenciaRecoleccion() {
    const contenedor = document.getElementById('detalleFotoEvidencia');
    if (!contenedor) return;

    const foto = recoleccionActual?.fotoRecoleccion;
    if (!foto) {
        contenedor.innerHTML = '<p style="color:#64748b;">No hay evidencia cargada.</p>';
        return;
    }

    const url = foto.startsWith('/') ? `../../${foto}` : foto;
    contenedor.innerHTML = `
        <a href="${url}" target="_blank">
            <img src="${url}" alt="Evidencia de recolección" style="width: 100%; max-width: 360px; border-radius: 12px; display: block;">
        </a>
    `;
}

function calcularRutaOptimizada() {
    // En producción, esto usaría una API de mapas real
    console.log('Calculando ruta desde:', ubicacionActual, 'hasta:', recoleccionActual.coordenadas);
    // Aquí se integraría con Google Maps, Mapbox, etc.
}

// ============================================
// ACCIONES DE RECOLECCIÓN
// ============================================
async function iniciarRecoleccion() {
    feedbackTactilClick();
    mostrarLoading(true);

    try {
        const resp = await fetch(`${API_RECOLECCIONES}?action=iniciar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ recoleccion_id: recoleccionActual.id })
        });
        const json = await resp.json();
        if (!json.success) {
            throw new Error(json.message || 'No se pudo iniciar');
        }

        recoleccionActual.estado = 'en_curso';
        actualizarRecoleccionEnLista(recoleccionActual);
        verDetalleRecoleccion(recoleccionActual.id);
        feedbackTactilExito();
        mostrarNotificacion('Recolección confirmada.', 'exito');
    } catch (error) {
        feedbackTactilError();
        alert(error.message);
    } finally {
        mostrarLoading(false);
    }
}

function marcarRecibido() {
    feedbackTactilClick();
    mostrarFormularioRecoleccion();
}

function mostrarFormularioRecoleccion() {
    // Ocultar vista detalle, mostrar formulario
    document.getElementById('vistaDetalle').classList.add('oculto');
    document.getElementById('vistaFormulario').classList.remove('oculto');
    document.getElementById('vistaLista').classList.add('oculto');
    
    // Configurar formulario
    document.getElementById('formNumeroOrden').textContent = recoleccionActual.numeroOrden;
    document.getElementById('cantidadReal').value = recoleccionActual.cantidadPaquetes;
    document.getElementById('formDireccionRecoleccion').value = recoleccionActual.direccion || '';
    document.getElementById('formNombreContacto').value = recoleccionActual.nombreContacto || '';
    document.getElementById('formTelefonoContacto').value = recoleccionActual.telefono || '';
    const totalEl = document.getElementById('formTotalPaquetes');
    if (totalEl) totalEl.textContent = recoleccionActual.cantidadPaquetes || 0;
    const fechaAsignacionEl = document.getElementById('formFechaAsignacion');
    if (fechaAsignacionEl) fechaAsignacionEl.textContent = formatearFecha(recoleccionActual.fechaAsignacion);
    renderGuiasFormulario();
    
    // Resetear fotos
    fotosRecoleccion = [];
    document.getElementById('previsualizacionFotos').innerHTML = '';
}

function renderGuiasFormulario() {
    const lista = document.getElementById('formListaGuias');
    if (!lista) return;
    const paquetes = Array.isArray(recoleccionActual?.paquetes) ? recoleccionActual.paquetes : [];
    if (paquetes.length === 0) {
        lista.innerHTML = '<p style="color:#64748b;">No hay guías asociadas.</p>';
        return;
    }
    lista.innerHTML = paquetes.map(p => `
        <div class="paquete-recoleccion-item">
            <strong>${p.numero_guia}</strong>
            <span>${p.destinatario_nombre || 'Sin nombre'}</span>
        </div>
    `).join('');
}

// ============================================
// MANEJO DE FOTOS
// ============================================
document.getElementById('btnTomarFoto')?.addEventListener('click', function() {
    document.getElementById('inputFotos').click();
});

document.getElementById('inputFotos')?.addEventListener('change', function(e) {
    const archivos = Array.from(e.target.files);
    
    archivos.forEach(archivo => {
        if (archivo.type.startsWith('image/')) {
            procesarFoto(archivo);
        }
    });
    
    // Limpiar input para permitir seleccionar las mismas fotos de nuevo
    e.target.value = '';
});

function procesarFoto(archivo) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            // Comprimir y redimensionar imagen para móvil
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Tamaño máximo
            const maxWidth = 1920;
            const maxHeight = 1920;
            let width = img.width;
            let height = img.height;
            
            // Calcular nuevas dimensiones
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
            
            // Dibujar imagen redimensionada
            ctx.drawImage(img, 0, 0, width, height);
            
            // Convertir a base64 con compresión
            const fotoData = canvas.toDataURL('image/jpeg', 0.85);
            
            const foto = {
                id: Date.now() + Math.random(),
                data: fotoData,
                fecha: new Date(),
                ubicacion: ubicacionActual ? {...ubicacionActual} : null,
                mensajero: mensajeroId,
                nombreArchivo: archivo.name,
                tamanoOriginal: archivo.size,
                tamanoComprimido: fotoData.length
            };
            
            fotosRecoleccion.push(foto);
            mostrarPreviewFoto(foto);
            
            // Feedback táctil
            feedbackTactilExito();
        };
        img.src = e.target.result;
    };
    
    reader.readAsDataURL(archivo);
}

function mostrarPreviewFoto(foto) {
    const contenedor = document.getElementById('previsualizacionFotos');
    
    const div = document.createElement('div');
    div.className = 'foto-item';
    div.innerHTML = `
        <img src="${foto.data}" alt="Foto de recolección">
        <div class="foto-meta">
            ${formatearFechaHora(foto.fecha)}<br>
            ${foto.ubicacion ? `${foto.ubicacion.lat.toFixed(6)}, ${foto.ubicacion.lng.toFixed(6)}` : 'Sin ubicación'}<br>
            ID: ${foto.mensajero}
        </div>
        <button type="button" class="btn-eliminar-foto" onclick="eliminarFoto(${foto.id})">×</button>
    `;
    
    contenedor.appendChild(div);
}

function eliminarFoto(fotoId) {
    fotosRecoleccion = fotosRecoleccion.filter(f => f.id !== fotoId);
    
    // Re-renderizar previsualizaciones
    const contenedor = document.getElementById('previsualizacionFotos');
    contenedor.innerHTML = '';
    fotosRecoleccion.forEach(foto => mostrarPreviewFoto(foto));
}

// ============================================
// CONTROL DE CANTIDAD
// ============================================
document.querySelectorAll('.btn-cantidad').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = document.getElementById('cantidadReal');
        const accion = this.dataset.accion;
        let valor = parseInt(input.value) || 0;
        
        if (accion === 'incrementar') {
            valor++;
        } else if (accion === 'decrementar' && valor > 0) {
            valor--;
        }
        
        input.value = valor;
        validarCantidad();
    });
});

document.getElementById('cantidadReal')?.addEventListener('input', function() {});

// ============================================
// ENVÍO DE FORMULARIO
// ============================================
document.getElementById('formRecoleccion')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar fotos
    if (fotosRecoleccion.length === 0) {
        alert('Debes tomar al menos una foto de la recolección');
        return;
    }
    
    // Validar cantidad
    const cantidadReal = parseInt(document.getElementById('cantidadReal').value) || 0;
    if (cantidadReal === 0) {
        alert('La cantidad de paquetes debe ser mayor a 0');
        return;
    }
    
    completarRecoleccion();
});

async function completarRecoleccion() {
    feedbackTactilClick();
    mostrarLoading(true);
    
    // Preparar datos
    const datosRecoleccion = {
        recoleccionId: recoleccionActual.id,
        numeroOrden: recoleccionActual.numeroOrden,
        cantidadReal: parseInt(document.getElementById('cantidadReal').value),
        observaciones: document.getElementById('observaciones').value,
        fotos: fotosRecoleccion,
        fechaCompletada: new Date(),
        ubicacionCompletada: ubicacionActual ? {...ubicacionActual} : null,
        mensajero: mensajeroId
    };
    
    try {
        const resp = await fetch(`${API_RECOLECCIONES}?action=completar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recoleccion_id: recoleccionActual.id,
                cantidad_real: datosRecoleccion.cantidadReal,
                observaciones: datosRecoleccion.observaciones,
                conformidad: 'si',
                fotos: datosRecoleccion.fotos
            })
        });
        const json = await resp.json();
        if (!json.success) {
            throw new Error(json.message || 'No se pudo completar la recolección');
        }

        recoleccionActual.estado = 'completada';
        recoleccionActual.datosCompletada = datosRecoleccion;
        actualizarRecoleccionEnLista(recoleccionActual);
        document.getElementById('vistaFormulario').classList.add('oculto');
        feedbackTactilExito();
        mostrarConfirmacion(datosRecoleccion);
    } catch (error) {
        feedbackTactilError();
        alert(error.message);
    } finally {
        mostrarLoading(false);
    }
}

function mostrarConfirmacion(datos) {
    const modal = document.getElementById('modalConfirmacion');
    const mensaje = document.getElementById('mensajeConfirmacion');
    
    mensaje.innerHTML = `
        <strong>${datos.numeroOrden}</strong><br>
        Paquetes recibidos: ${datos.cantidadReal}<br>
        Fotos adjuntas: ${datos.fotos.length}
    `;
    
    modal.classList.remove('oculto');
}

async function cancelarRecoleccion() {
    if (!recoleccionActual) return;
    const motivo = (document.getElementById('motivoCancelacion')?.value || '').trim();
    if (!motivo) {
        alert('Debes ingresar el motivo de cancelación');
        return;
    }

    try {
        const resp = await fetch(`${API_RECOLECCIONES}?action=cancelar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recoleccion_id: recoleccionActual.id,
                motivo
            })
        });
        const json = await resp.json();
        if (!json.success) {
            throw new Error(json.message || 'No se pudo cancelar la recolección');
        }

        recoleccionActual.estado = 'cancelada';
        actualizarRecoleccionEnLista(recoleccionActual);
        document.getElementById('modalCancelacion').classList.add('oculto');
        volverALista();
        alert('Recolección cancelada');
    } catch (error) {
        alert(error.message);
    }
}

// ============================================
// NAVEGACIÓN Y CONTACTO
// ============================================
function abrirNavegacion() {
    if (!recoleccionActual) return;
    
    feedbackTactilClick();
    
    const lat = recoleccionActual.coordenadas.lat;
    const lng = recoleccionActual.coordenadas.lng;
    const direccion = encodeURIComponent(recoleccionActual.direccion);
    
    // Detectar plataforma
    const esIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const esAndroid = /Android/.test(navigator.userAgent);
    
    if (esIOS) {
        // Intentar abrir Apple Maps primero
        const urlAppleMaps = `maps://maps.apple.com/?daddr=${lat},${lng}&dirflg=d`;
        const urlGoogleMaps = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
        
        // Intentar Apple Maps
        window.location.href = urlAppleMaps;
        
        // Fallback a Google Maps si Apple Maps no se abre
        setTimeout(() => {
            if (confirm('¿Deseas abrir en Google Maps en su lugar?')) {
                window.open(urlGoogleMaps, '_blank');
            }
        }, 2000);
        
    } else if (esAndroid) {
        // Android - Google Maps nativo
        const urlNativo = `google.navigation:q=${lat},${lng}&mode=d`;
        const urlWeb = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
        
        // Intentar app nativa
        window.location.href = urlNativo;
        
        // Fallback a web
        setTimeout(() => {
            window.open(urlWeb, '_blank');
        }, 1500);
        
    } else {
        // Escritorio o desconocido - Google Maps web
        const urlMaps = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
        window.open(urlMaps, '_blank');
    }
}

function llamarContacto() {
    if (!recoleccionActual) return;
    window.location.href = `tel:${recoleccionActual.telefono}`;
}

// ============================================
// NAVEGACIÓN ENTRE VISTAS
// ============================================
function volverALista() {
    document.getElementById('vistaDetalle').classList.add('oculto');
    document.getElementById('vistaFormulario').classList.add('oculto');
    document.getElementById('vistaLista').classList.remove('oculto');
    document.getElementById('vistaLista').classList.add('vista-activa');
    
    recoleccionActual = null;
}

// ============================================
// CONFIGURACIÓN DE EVENT LISTENERS
// ============================================
function configurarEventListeners() {
    // Filtros
    document.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            feedbackTactilClick();
            document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');
            mostrarRecolecciones(this.dataset.filtro);
        });
    });
    
    // Navegación
    document.getElementById('btnVolver')?.addEventListener('click', () => {
        feedbackTactilClick();
        volverALista();
    });
    
    document.getElementById('btnNavegar')?.addEventListener('click', abrirNavegacion);
    
    document.getElementById('btnLlamar')?.addEventListener('click', () => {
        feedbackTactilClick();
        llamarContacto();
    });

    document.getElementById('btnLlamarForm')?.addEventListener('click', () => {
        feedbackTactilClick();
        llamarContacto();
    });
    
    // Acciones de recolección
    document.getElementById('btnLleguePunto')?.addEventListener('click', marcarRecibido);
    document.getElementById('btnCancelar')?.addEventListener('click', () => {
        document.getElementById('modalCancelacion').classList.remove('oculto');
    });
    document.getElementById('btnNoPuedoForm')?.addEventListener('click', () => {
        document.getElementById('modalCancelacion').classList.remove('oculto');
    });
    document.getElementById('btnCerrarCancelacion')?.addEventListener('click', () => {
        document.getElementById('modalCancelacion').classList.add('oculto');
    });
    document.getElementById('btnConfirmarCancelacion')?.addEventListener('click', cancelarRecoleccion);
    
    // Formulario
    document.getElementById('btnCancelarFormulario')?.addEventListener('click', function() {
        feedbackTactilClick();
        if (confirm('¿Estás seguro de cancelar? Se perderán los datos ingresados.')) {
            volverALista();
        }
    });

    document.getElementById('btnVolverFormulario')?.addEventListener('click', function() {
        feedbackTactilClick();
        volverALista();
    });
    
    // Modales
    document.getElementById('btnCerrarModal')?.addEventListener('click', function() {
        feedbackTactilClick();
        document.getElementById('modalConfirmacion').classList.add('oculto');
        volverALista();
    });
    
    // Agregar feedback táctil a los botones de cantidad
    document.querySelectorAll('.btn-cantidad').forEach(btn => {
        btn.addEventListener('click', () => {
            feedbackTactilClick();
        });
    });
}

// ============================================
// NOTIFICACIONES
// ============================================
function configurarNotificaciones() {}

// ============================================
// UTILIDADES
// ============================================
function formatearFecha(fecha) {
    if (!fecha) return 'Sin fecha';
    const d = new Date(fecha);
    const opciones = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return d.toLocaleDateString('es-CO', opciones);
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

function calcularDistancia(lat1, lon1, lat2, lon2) {
    // Fórmula de Haversine para calcular distancia entre coordenadas
    const R = 6371; // Radio de la Tierra en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function actualizarRecoleccionEnLista(recoleccion) {
    const index = recolecciones.findIndex(r => r.id === recoleccion.id);
    if (index !== -1) {
        recolecciones[index] = recoleccion;
        mostrarRecolecciones();
    }
}

function mostrarLoading(mostrar) {
    const overlay = document.getElementById('loadingOverlay');
    if (mostrar) {
        overlay.classList.remove('oculto');
    } else {
        overlay.classList.add('oculto');
    }
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Implementación simple de notificación
    // En producción se usaría una librería como toastr o similar
    alert(mensaje);
}

function inicializarApp() {
    // Prevenir que la pantalla se apague durante uso activo
    if ('wakeLock' in navigator) {
        let wakeLock = null;
        
        async function solicitarWakeLock() {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
                console.log('Wake Lock activado');
                
                wakeLock.addEventListener('release', () => {
                    console.log('Wake Lock liberado');
                });
            } catch (err) {
                console.error('Error activando Wake Lock:', err);
            }
        }
        
        // Activar cuando hay recolección en curso
        document.addEventListener('visibilitychange', async () => {
            if (wakeLock !== null && document.visibilityState === 'visible') {
                await solicitarWakeLock();
            }
        });
    }
    
    // Prevenir zoom con doble tap (mejor UX en móvil)
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(event) {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, false);
    
    // Detectar si es PWA instalada
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('App ejecutándose como PWA');
        document.body.classList.add('pwa-instalada');
    }
    
    // Registrar Service Worker para funcionamiento offline (opcional)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(() => {
            console.log('Service Worker registrado');
        }).catch(err => {
            console.log('Error registrando Service Worker:', err);
        });
    }
    
    console.log('App de recolecciones inicializada');
}

// ============================================
// FUNCIONES GLOBALES (llamadas desde HTML onclick)
// ============================================
window.verDetalleRecoleccion = verDetalleRecoleccion;
window.eliminarFoto = eliminarFoto;
