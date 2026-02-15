document.addEventListener('DOMContentLoaded', function() {
    cargarHistorial();
    configurarFiltros();
});

let historialPaquetes = [];

function cargarHistorial() {
    // Simulamos datos que vendrían de la BD (Solo entregados)
    historialPaquetes = [
        {
            id: 4,
            guia: 'GUA-2024-0004',
            nombreDestinatario: 'Pedro Sánchez',
            direccion: 'Calle 100 #19-61, Bogotá',
            contenido: 'Medicamentos',
            valorDeclarado: 80000,
            estado: 'entregado',
            infoEntrega: {
                nombreRecibe: 'Pedro Sánchez',
                parentesco: 'destinatario',
                documento: '1234567890',
                recaudo: 80000,
                fecha: '2024-02-05 09:30',
                fechaObj: new Date('2024-02-05T09:30:00')
            }
        },
        {
            id: 10,
            guia: 'GUA-2024-0010',
            nombreDestinatario: 'Luisa Fernanda',
            direccion: 'Carrera 7 #45-10',
            contenido: 'Ropa',
            valorDeclarado: 120000,
            estado: 'entregado',
            infoEntrega: {
                nombreRecibe: 'Portería',
                parentesco: 'portero',
                documento: 'N/A',
                recaudo: 0,
                fecha: '2024-02-04 15:20',
                fechaObj: new Date('2024-02-04T15:20:00')
            }
        }
    ];

    actualizarEstadisticas();
    mostrarHistorial('todos');
}

function mostrarHistorial(filtro) {
    const contenedor = document.getElementById('listaHistorial');
    let paquetesFiltrados = historialPaquetes;

    // Filtros de fecha
    const hoy = new Date();
    hoy.setHours(0,0,0,0);

    if (filtro === 'hoy') {
        paquetesFiltrados = historialPaquetes.filter(p => {
            const fecha = new Date(p.infoEntrega.fechaObj);
            fecha.setHours(0,0,0,0);
            return fecha.getTime() === hoy.getTime();
        });
    } else if (filtro === 'semana') {
        // Lógica simple de semana (últimos 7 días)
        const semanaAtras = new Date(hoy);
        semanaAtras.setDate(hoy.getDate() - 7);
        paquetesFiltrados = historialPaquetes.filter(p => p.infoEntrega.fechaObj >= semanaAtras);
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
                    <span class="info-row-valor">${paquete.infoEntrega.fecha}</span>
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

    document.getElementById('detalleGuia').textContent = paquete.guia;
    
    // Info Entrega
    const info = paquete.infoEntrega;
    document.getElementById('entregaRecibio').textContent = info.nombreRecibe;
    document.getElementById('entregaParentesco').textContent = info.parentesco;
    document.getElementById('entregaDocumento').textContent = info.documento;
    document.getElementById('entregaFecha').textContent = info.fecha;
    document.getElementById('entregaRecaudo').textContent = formatearMoneda(info.recaudo);

    // Info Paquete
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
        btn.addEventListener('click', function() {
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
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(valor);
}
window.verDetalleHistorial = verDetalleHistorial;