document.addEventListener('DOMContentLoaded', function () {
    cargarHistorial();
    configurarFiltros();
});

let historialPaquetes = [];

async function cargarHistorial() {
    try {
        const resp = await fetch('../../controller/historialMensajeroController.php?action=listar');
        const json = await resp.json();

        if (!json.success) {
            throw new Error(json.message || 'No se pudo cargar el historial');
        }

        historialPaquetes = (json.data || []).map(row => ({
            id: Number(row.id),
            guia: row.numero_guia,
            nombreDestinatario: row.destinatario_nombre,
            direccion: row.direccion_destino,
            contenido: row.descripcion_contenido,
            valorDeclarado: Number(row.costo_envio || 0),
            estado: 'entregado',
            infoEntrega: {
                nombreRecibe: row.nombre_receptor,
                parentesco: row.parentesco_cargo || 'N/A',
                documento: row.documento_receptor || 'N/A',
                recaudo: Number(row.recaudo_real || 0),
                fecha: row.fecha_entrega,
                fechaObj: new Date(row.fecha_entrega)
            }
        }));

        actualizarEstadisticas();
        mostrarHistorial('todos');
    } catch (error) {
        console.error(error);
        document.getElementById('listaHistorial').innerHTML = '<p style="padding:1rem;color:#b91c1c;">Error cargando historial.</p>';
    }
}

function mostrarHistorial(filtro) {
    const contenedor = document.getElementById('listaHistorial');
    let paquetesFiltrados = historialPaquetes;

    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    if (filtro === 'hoy') {
        paquetesFiltrados = historialPaquetes.filter(p => {
            const fecha = new Date(p.infoEntrega.fechaObj);
            fecha.setHours(0, 0, 0, 0);
            return fecha.getTime() === hoy.getTime();
        });
    } else if (filtro === 'semana') {
        const semanaAtras = new Date(hoy);
        semanaAtras.setDate(hoy.getDate() - 7);
        paquetesFiltrados = historialPaquetes.filter(p => p.infoEntrega.fechaObj >= semanaAtras);
    } else if (filtro === 'mes') {
        const mesAtras = new Date(hoy);
        mesAtras.setMonth(hoy.getMonth() - 1);
        paquetesFiltrados = historialPaquetes.filter(p => p.infoEntrega.fechaObj >= mesAtras);
    }

    if (paquetesFiltrados.length === 0) {
        contenedor.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: #64748b;">
                <p style="font-size: 3rem; margin-bottom: 1rem;">📭</p>
                <p>No hay entregas en este periodo</p>
            </div>
        `;
        return;
    }

    contenedor.innerHTML = paquetesFiltrados.map(paquete => `
        <div class="tarjeta-paquete entregado">
            <div class="paquete-header">
                <div class="guia-numero">${paquete.guia}</div>
                <span class="badge entregado">Entregado</span>
            </div>
            <div class="paquete-info">
                <div class="info-row">
                    <span class="info-row-label">Entregado a:</span>
                    <span class="info-row-valor destinatario-nombre">${paquete.infoEntrega.nombreRecibe}</span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Fecha:</span>
                    <span class="info-row-valor">${formatearFecha(paquete.infoEntrega.fecha)}</span>
                </div>
                <div class="info-row">
                    <span class="info-row-label">Recaudo:</span>
                    <span class="info-row-valor valor-declarado">${formatearMoneda(paquete.infoEntrega.recaudo)}</span>
                </div>
            </div>
            <div class="paquete-acciones">
                <button class="btn-ver-detalle" onclick="verDetalleHistorial(${paquete.id})" style="width:100%">
                    👁️ Ver Comprobante
                </button>
            </div>
        </div>
    `).join('');
}

function verDetalleHistorial(id) {
    const paquete = historialPaquetes.find(p => p.id === id);
    if (!paquete) return;

    document.getElementById('vistaLista').classList.add('oculto');
    document.getElementById('vistaDetalle').classList.remove('oculto');

    const info = paquete.infoEntrega;
    document.getElementById('detalleGuia').textContent = paquete.guia;
    document.getElementById('entregaRecibio').textContent = info.nombreRecibe;
    document.getElementById('entregaParentesco').textContent = info.parentesco;
    document.getElementById('entregaDocumento').textContent = info.documento;
    document.getElementById('entregaFecha').textContent = formatearFecha(info.fecha);
    document.getElementById('entregaRecaudo').textContent = formatearMoneda(info.recaudo);

    document.getElementById('detalleDestinatario').textContent = paquete.nombreDestinatario;
    document.getElementById('detalleDireccion').textContent = paquete.direccion;
    document.getElementById('detalleContenido').textContent = paquete.contenido;
}

function actualizarEstadisticas() {
    const total = historialPaquetes.length;
    const recaudo = historialPaquetes.reduce((sum, p) => sum + (p.infoEntrega.recaudo || 0), 0);
    const elTotal = document.getElementById('totalHistorico');
    const elRecaudo = document.getElementById('totalRecaudoHistorico');
    if (elTotal) elTotal.textContent = total;
    if (elRecaudo) elRecaudo.textContent = formatearMoneda(recaudo);
}

function configurarFiltros() {
    document.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');
            mostrarHistorial(this.dataset.filtro);
        });
    });

    document.getElementById('btnVolverDetalle').addEventListener('click', () => {
        document.getElementById('vistaDetalle').classList.add('oculto');
        document.getElementById('vistaLista').classList.remove('oculto');
    });
}

function formatearMoneda(valor) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(valor || 0);
}

function formatearFecha(fecha) {
    const d = new Date(fecha);
    if (Number.isNaN(d.getTime())) return fecha || '';
    return d.toLocaleString('es-CO');
}

window.verDetalleHistorial = verDetalleHistorial;
