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
        aplicarFiltros();
    } catch (error) {
        console.error(error);
        const tbody = document.getElementById('tablaHistorialBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="padding:1rem;color:#b91c1c;">Error cargando historial.</td></tr>';
    }
}

let filtroActual = 'todos';

function aplicarFiltros() {
    const searchValue = (document.getElementById('searchHistorial')?.value || '').trim().toLowerCase();
    const paquetesFiltrados = filtrarHistorial(filtroActual, searchValue);
    renderTablaHistorial(paquetesFiltrados);
    renderCardsHistorial(paquetesFiltrados);
    actualizarConteoTabla(paquetesFiltrados.length);
}

function filtrarHistorial(filtro, searchValue) {
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

    if (searchValue) {
        paquetesFiltrados = paquetesFiltrados.filter(p => {
            const guia = (p.guia || '').toLowerCase();
            const destinatario = (p.nombreDestinatario || '').toLowerCase();
            const receptor = (p.infoEntrega?.nombreRecibe || '').toLowerCase();
            return guia.includes(searchValue) || destinatario.includes(searchValue) || receptor.includes(searchValue);
        });
    }

    return paquetesFiltrados;
}

function renderTablaHistorial(paquetes) {
    const tbody = document.getElementById('tablaHistorialBody');
    if (!tbody) return;

    if (paquetes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:1.5rem;color:#64748b;">No hay entregas en este periodo</td></tr>';
        return;
    }

    tbody.innerHTML = paquetes.map(paquete => `
        <tr>
            <td>${paquete.guia}</td>
            <td>${paquete.nombreDestinatario || '-'}</td>
            <td>${paquete.direccion || '-'}</td>
            <td>${formatearFecha(paquete.infoEntrega.fecha)}</td>
            <td>${formatearMoneda(paquete.infoEntrega.recaudo)}</td>
            <td>${paquete.infoEntrega.nombreRecibe || '-'}</td>
            <td>
                <button class="btn-ver-detalle" onclick="verDetalleHistorial(${paquete.id})">
                    Ver comprobante
                </button>
            </td>
        </tr>
    `).join('');
}

function renderCardsHistorial(paquetes) {
    const contenedor = document.getElementById('cardsHistorial');
    if (!contenedor) return;

    if (paquetes.length === 0) {
        contenedor.innerHTML = `
            <div class="card-empty">
                <p>📭</p>
                <span>No hay entregas en este periodo</span>
            </div>
        `;
        return;
    }

    contenedor.innerHTML = paquetes.map(paquete => `
        <div class="historial-card">
            <div class="card-header">
                <div class="card-title">${paquete.guia}</div>
                <span class="badge entregado">Entregado</span>
            </div>
            <div class="card-body">
                <div><strong>Destinatario:</strong> ${paquete.nombreDestinatario || '-'}</div>
                <div><strong>Recibió:</strong> ${paquete.infoEntrega.nombreRecibe || '-'}</div>
                <div><strong>Fecha:</strong> ${formatearFecha(paquete.infoEntrega.fecha)}</div>
                <div><strong>Recaudo:</strong> ${formatearMoneda(paquete.infoEntrega.recaudo)}</div>
            </div>
            <button class="btn-ver-detalle" onclick="verDetalleHistorial(${paquete.id})">
                Ver comprobante
            </button>
        </div>
    `).join('');
}

function actualizarConteoTabla(total) {
    const showingFrom = document.getElementById('showingFrom');
    const showingTo = document.getElementById('showingTo');
    const totalResults = document.getElementById('totalResults');
    if (!showingFrom || !showingTo || !totalResults) return;
    const from = total > 0 ? 1 : 0;
    showingFrom.textContent = from;
    showingTo.textContent = total;
    totalResults.textContent = total;
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
            filtroActual = this.dataset.filtro;
            aplicarFiltros();
        });
    });

    document.getElementById('btnVolverDetalle').addEventListener('click', () => {
        document.getElementById('vistaDetalle').classList.add('oculto');
        document.getElementById('vistaLista').classList.remove('oculto');
    });

    const search = document.getElementById('searchHistorial');
    if (search) {
        search.addEventListener('input', () => aplicarFiltros());
    }
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
