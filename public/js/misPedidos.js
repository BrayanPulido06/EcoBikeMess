document.addEventListener('DOMContentLoaded', function() {
    
    // Datos de ejemplo de pedidos
    let pedidos = [];
    let filteredPedidos = [];
    let currentPage = 1;
    const itemsPerPage = 5;
    
    // Elementos del DOM
    const searchInput = document.getElementById('searchInput');
    const filterEstado = document.getElementById('filterEstado');
    const filterFecha = document.getElementById('filterFecha');
    const filterOrden = document.getElementById('filterOrden');
    const btnExportPDF = document.getElementById('btnExportPDF');
    const btnExportExcel = document.getElementById('btnExportExcel');
    const btnNuevoPedido = document.getElementById('btnNuevoPedido');
    const selectAllCheckbox = document.getElementById('selectAll');
    const pedidosContainer = document.getElementById('pedidosContainer');
    const noResults = document.getElementById('noResults');
    const loading = document.getElementById('loading');
    const detalleModal = document.getElementById('detalleModal');
    const closeDetalleModal = document.getElementById('closeDetalleModal');
    
    // ============================================
    // INICIALIZAR
    // ============================================
    
    function init() {
        fetchPedidos();
        setupEventListeners();
    }

    async function fetchPedidos() {
        try {
            loading.style.display = 'block';
            const response = await fetch('../../controller/misPedidosController.php');
            const result = await response.json();
            
            if (result.success) {
                pedidos = result.data;
                filteredPedidos = [...pedidos];
                updateStats();
                renderPedidos();
            } else {
                console.error(result.message);
                showToast('Error al cargar pedidos: ' + result.message, 'error');
                loading.style.display = 'none';
                document.getElementById('noResults').style.display = 'block';
            }
        } catch (error) {
            console.error(error);
            showToast('Error de conexión al cargar pedidos', 'error');
            loading.style.display = 'none';
        }
    }
    
    // ============================================
    // ACTUALIZAR ESTADÍSTICAS
    // ============================================
    
    function updateStats() {
        const stats = {
            pendientes: pedidos.filter(p => p.estado === 'pendiente').length,
            enTransito: pedidos.filter(p => p.estado === 'en_transito').length,
            entregados: pedidos.filter(p => p.estado === 'entregado').length,
            total: pedidos.length
        };
        
        document.getElementById('totalPendientes').textContent = stats.pendientes;
        document.getElementById('totalEnTransito').textContent = stats.enTransito;
        document.getElementById('totalEntregados').textContent = stats.entregados;
        document.getElementById('totalPedidos').textContent = stats.total;
    }
    
    // ============================================
    // RENDERIZAR PEDIDOS
    // ============================================
    
    function renderPedidos() {
        loading.style.display = 'none';
        
        if (filteredPedidos.length === 0) {
            pedidosContainer.innerHTML = '';
            noResults.style.display = 'block';
            return;
        }
        
        noResults.style.display = 'none';
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedPedidos = filteredPedidos.slice(startIndex, endIndex);
        
        pedidosContainer.innerHTML = paginatedPedidos.map(pedido => `
            <tr onclick="verDetalle(${pedido.id})" style="cursor: pointer;">
                <td><input type="checkbox" class="pedido-checkbox" value="${pedido.id}" onclick="event.stopPropagation()"></td>
                <td><span class="pedido-guia">${pedido.guia}</span></td>
                <td>${formatDate(pedido.fecha)}</td>
                <td>${pedido.destinatario.nombre}</td>
                <td>${pedido.destinatario.direccion}</td>
                <td><span class="status-badge status-${pedido.estado}">${getEstadoTexto(pedido.estado)}</span></td>
                <td>$${pedido.paquete.costo.toLocaleString('es-CO')}</td>
                <td class="actions-cell" onclick="event.stopPropagation()">
                    <button class="btn-icon" title="Ver Rótulo" onclick="openRotuloModal(${pedido.id})">
                        🏷️
                    </button>
                    <button class="btn-icon" title="Ver Detalles" onclick="verDetalle(${pedido.id})">
                        👁️
                    </button>
                    ${pedido.estado === 'entregado' ? `
                        <button class="btn-icon" title="Descargar PDF" onclick="descargarComprobante(${pedido.id})">
                            ⬇️
                        </button>
                    ` : ''}
                    ${pedido.estado === 'pendiente' || pedido.estado === 'en_proceso' ? `
                        <button class="btn-icon delete" title="Cancelar Pedido" onclick="cancelarPedido(${pedido.id})">
                            ❌
                        </button>
                    ` : ''}
                </td>
            </tr>
        `).join('');
        
        renderPagination();
    }
    
    // ============================================
    // PAGINACIÓN
    // ============================================
    
    function renderPagination() {
        const totalPages = Math.ceil(filteredPedidos.length / itemsPerPage);
        const btnPrevPage = document.getElementById('btnPrevPage');
        const btnNextPage = document.getElementById('btnNextPage');
        
        document.getElementById('currentPage').textContent = currentPage;
        document.getElementById('totalPages').textContent = totalPages;
        
        btnPrevPage.disabled = currentPage === 1;
        btnNextPage.disabled = currentPage === totalPages;
        
        btnPrevPage.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                renderPedidos();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };
        
        btnNextPage.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderPedidos();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };
    }
    
    // ============================================
    // FILTROS Y BÚSQUEDA
    // ============================================
    
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const estado = filterEstado.value;
        const fecha = filterFecha.value;
        const orden = filterOrden.value;
        
        filteredPedidos = pedidos.filter(pedido => {
            const matchSearch = pedido.guia.toLowerCase().includes(searchTerm) ||
                              pedido.destinatario.nombre.toLowerCase().includes(searchTerm);
            
            const matchEstado = estado === 'all' || pedido.estado === estado;
            
            let matchFecha = true;
            if (fecha !== 'all') {
                const pedidoDate = new Date(pedido.fecha);
                const today = new Date();
                
                if (fecha === 'today') {
                    matchFecha = pedidoDate.toDateString() === today.toDateString();
                } else if (fecha === 'week') {
                    const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                    matchFecha = pedidoDate >= weekAgo;
                } else if (fecha === 'month') {
                    matchFecha = pedidoDate.getMonth() === today.getMonth() && 
                               pedidoDate.getFullYear() === today.getFullYear();
                } else if (fecha === 'year') {
                    matchFecha = pedidoDate.getFullYear() === today.getFullYear();
                }
            }
            
            return matchSearch && matchEstado && matchFecha;
        });
        
        // Ordenar
        filteredPedidos.sort((a, b) => {
            const dateA = new Date(a.fecha);
            const dateB = new Date(b.fecha);
            return orden === 'desc' ? dateB - dateA : dateA - dateB;
        });
        
        currentPage = 1;
        renderPedidos();
    }
    
    // ============================================
    // TABS DE ESTADO
    // ============================================
    
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            filterEstado.value = filter;
            applyFilters();
        });
    });
    
    // ============================================
    // VER DETALLE
    // ============================================
    
    window.verDetalle = function(id) {
        const pedido = pedidos.find(p => p.id === id);
        if (!pedido) return;
        
        // Guía y estado
        document.getElementById('modal_guia').textContent = pedido.guia;
        const estadoBadge = document.getElementById('modal_estado_badge');
        estadoBadge.className = `modal-status status-${pedido.estado}`;
        estadoBadge.textContent = getEstadoTexto(pedido.estado);
        
        // Timeline
        const timelineHTML = pedido.timeline.map(item => `
            <div class="timeline-item ${item.activo ? 'active' : ''}">
                <div class="timeline-content">
                    <div class="timeline-title">${item.estado}</div>
                    <div class="timeline-time">${formatDateFull(item.fecha)}</div>
                </div>
            </div>
        `).join('');
        document.getElementById('timeline').innerHTML = timelineHTML;
        
        // Remitente
        document.getElementById('modal_remitente_nombre').textContent = pedido.remitente.nombre;
        document.getElementById('modal_remitente_telefono').textContent = pedido.remitente.telefono;
        document.getElementById('modal_remitente_direccion').textContent = pedido.remitente.direccion;
        
        // Destinatario
        document.getElementById('modal_destinatario_nombre').textContent = pedido.destinatario.nombre;
        document.getElementById('modal_destinatario_telefono').textContent = pedido.destinatario.telefono;
        document.getElementById('modal_destinatario_direccion').textContent = pedido.destinatario.direccion;
        
        // Paquete
        document.getElementById('modal_descripcion').textContent = pedido.paquete.descripcion;
        document.getElementById('modal_peso').textContent = pedido.paquete.peso;
        document.getElementById('modal_tipo').textContent = pedido.paquete.tipo;
        document.getElementById('modal_costo').textContent = '$' + pedido.paquete.costo.toLocaleString('es-CO');
        
        // Comprobante (solo si está entregado)
        const comprobanteSection = document.getElementById('comprobanteSection');
        const btnDescargarComprobante = document.getElementById('btnDescargarComprobante');
        const btnImprimirComprobante = document.getElementById('btnImprimirComprobante');
        const btnCancelar = document.getElementById('btnCancelar');
        
        if (pedido.estado === 'entregado' && pedido.comprobante) {
            comprobanteSection.style.display = 'block';
            btnDescargarComprobante.style.display = 'block';
            btnImprimirComprobante.style.display = 'block';
            btnCancelar.style.display = 'none';
            
            document.getElementById('modal_quien_recibio').textContent = pedido.comprobante.quienRecibio;
            document.getElementById('modal_parentesco').textContent = pedido.comprobante.parentesco;
            document.getElementById('modal_fecha_entrega').textContent = formatDateFull(pedido.comprobante.fechaEntrega);
            document.getElementById('modal_recaudo').textContent = pedido.comprobante.recaudo > 0 ? 
                '$' + pedido.comprobante.recaudo.toLocaleString('es-CO') : 'Sin recaudo';
            document.getElementById('modal_observaciones').textContent = pedido.comprobante.observaciones;
            document.getElementById('modal_foto_entrega').src = pedido.comprobante.foto;
        } else {
            comprobanteSection.style.display = 'none';
            btnDescargarComprobante.style.display = 'none';
            btnImprimirComprobante.style.display = 'none';
            btnCancelar.style.display = (pedido.estado === 'pendiente' || pedido.estado === 'en_proceso') ? 'block' : 'none';
        }
        
        detalleModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Guardar ID actual
        detalleModal.dataset.currentId = id;
    };
    
    // ============================================
    // DESCARGAR COMPROBANTE PDF
    // ============================================
    
    window.descargarComprobante = async function(id) {
        const pedido = pedidos.find(p => p.id === id);
        if (!pedido || !pedido.comprobante) return;
        
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Generando...';
        btn.disabled = true;
        
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            let yPos = 20;
            
            // Header
            doc.setFontSize(20);
            doc.setTextColor(92, 184, 92);
            doc.text('EcoBikeMess', 20, yPos);
            yPos += 10;
            
            doc.setFontSize(12);
            doc.setTextColor(0, 0, 0);
            doc.text('Comprobante de Entrega', 20, yPos);
            yPos += 10;
            
            doc.setFontSize(11);
            doc.setTextColor(92, 184, 92);
            doc.text(`Guía: ${pedido.guia}`, 20, yPos);
            yPos += 15;
            
            // Cliente
            doc.setFontSize(12);
            doc.setTextColor(0, 0, 0);
            doc.text('Cliente:', 20, yPos);
            yPos += 7;
            doc.setFontSize(10);
            doc.text(pedido.remitente.nombre, 25, yPos);
            yPos += 6;
            doc.text(pedido.remitente.direccion, 25, yPos);
            yPos += 12;
            
            // Datos de entrega
            doc.setFontSize(12);
            doc.text('Datos de Entrega:', 20, yPos);
            yPos += 7;
            doc.setFontSize(10);
            doc.text(`Recibió: ${pedido.comprobante.quienRecibio}`, 25, yPos);
            yPos += 6;
            doc.text(`Parentesco: ${pedido.comprobante.parentesco}`, 25, yPos);
            yPos += 6;
            doc.text(`Fecha: ${formatDateFull(pedido.comprobante.fechaEntrega)}`, 25, yPos);
            yPos += 6;
            doc.text(`Recaudo: ${pedido.comprobante.recaudo > 0 ? '$' + pedido.comprobante.recaudo.toLocaleString('es-CO') : 'Sin recaudo'}`, 25, yPos);
            yPos += 12;
            
            // Observaciones
            doc.setFontSize(12);
            doc.text('Observaciones:', 20, yPos);
            yPos += 7;
            doc.setFontSize(10);
            const splitObs = doc.splitTextToSize(pedido.comprobante.observaciones, 170);
            doc.text(splitObs, 25, yPos);
            
            doc.save(`Comprobante-${pedido.guia}.pdf`);
            
            btn.textContent = originalText;
            btn.disabled = false;
            showToast('✓ PDF descargado exitosamente');
            
        } catch (error) {
            console.error('Error generando PDF:', error);
            alert('Error al generar el PDF');
            btn.textContent = originalText;
            btn.disabled = false;
        }
    };
    
    // Botón de descarga en modal
    document.getElementById('btnDescargarComprobante').addEventListener('click', function() {
        const id = parseInt(detalleModal.dataset.currentId);
        descargarComprobante(id);
    });
    
    // ============================================
    // IMPRIMIR COMPROBANTE
    // ============================================
    
    document.getElementById('btnImprimirComprobante').addEventListener('click', function() {
        window.print();
    });
    
    // ============================================
    // CANCELAR PEDIDO
    // ============================================
    
    window.cancelarPedido = function(id) {
        if (confirm('¿Estás seguro de cancelar este pedido?')) {
            const pedido = pedidos.find(p => p.id === id);
            if (pedido) {
                pedido.estado = 'cancelado';
                updateStats();
                applyFilters();
                showToast('Pedido cancelado', 'info');
            }
        }
    };
    
    document.getElementById('btnCancelar').addEventListener('click', function() {
        const id = parseInt(detalleModal.dataset.currentId);
        cancelarPedido(id);
        detalleModal.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // ============================================
    // EXPORTAR
    // ============================================
    
    function getPedidosParaExportar() {
        const selectedCheckboxes = document.querySelectorAll('.pedido-checkbox:checked');
        let lista = [];

        // Si hay seleccionados, exportamos esos. Si no, exportamos la lista filtrada actual.
        if (selectedCheckboxes.length > 0) {
            const selectedIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
            lista = pedidos.filter(p => selectedIds.includes(p.id));
        } else {
            lista = filteredPedidos;
        }
        return lista;
    }

    // --- EXPORTAR PDF ---
    if (btnExportPDF) {
        btnExportPDF.addEventListener('click', function() {
            const pedidosAExportar = getPedidosParaExportar();

            if (pedidosAExportar.length === 0) {
                showToast('No hay pedidos para exportar', 'error');
                return;
            }

            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Encabezado del PDF
                doc.setFontSize(18);
                doc.text('Reporte de Pedidos - EcoBikeMess', 14, 22);
                doc.setFontSize(11);
                doc.text(`Fecha: ${new Date().toLocaleDateString()}`, 14, 30);

                // Definir columnas y filas para la tabla
                const tableColumn = ["Guía", "Fecha", "Destinatario", "Dirección", "Estado", "Costo"];
                const tableRows = pedidosAExportar.map(pedido => [
                    pedido.guia,
                    formatDate(pedido.fecha),
                    pedido.destinatario.nombre,
                    pedido.destinatario.direccion,
                    getEstadoTexto(pedido.estado),
                    '$' + pedido.paquete.costo.toLocaleString('es-CO')
                ]);

                doc.autoTable({
                    head: [tableColumn],
                    body: tableRows,
                    startY: 40,
                });

                doc.save('Reporte_Pedidos.pdf');
                showToast('✓ Reporte PDF generado exitosamente');
            } catch (error) {
                console.error('Error generando reporte:', error);
                alert('Error al generar el PDF. Verifica la consola.');
            }
        });
    }

    // --- EXPORTAR EXCEL ---
    if (btnExportExcel) {
        btnExportExcel.addEventListener('click', function() {
            const pedidosAExportar = getPedidosParaExportar();

            if (pedidosAExportar.length === 0) {
                showToast('No hay pedidos para exportar', 'error');
                return;
            }

            try {
                // Preparar datos para Excel (aplanar objetos para que se vean bien en las celdas)
                const datosExcel = pedidosAExportar.map(p => ({
                    "Guía": p.guia,
                    "Fecha": formatDate(p.fecha),
                    "Estado": getEstadoTexto(p.estado),
                    "Remitente": p.remitente.nombre,
                    "Destinatario": p.destinatario.nombre,
                    "Dirección Destino": p.destinatario.direccion,
                    "Descripción Paquete": p.paquete.descripcion,
                    "Costo": p.paquete.costo
                }));

                // Crear hoja de trabajo
                const worksheet = XLSX.utils.json_to_sheet(datosExcel);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "Pedidos");

                // Descargar archivo
                XLSX.writeFile(workbook, `Reporte_Pedidos_${new Date().toISOString().slice(0,10)}.xlsx`);
                showToast('✓ Reporte Excel generado exitosamente');
            } catch (error) {
                console.error('Error generando Excel:', error);
                alert('Error al generar el Excel. Verifica la consola.');
            }
        });
    }
    
    /* Bloque anterior eliminado para evitar duplicados
    btnExport.addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.pedido-checkbox:checked');
        let pedidosAExportar = [];

        // Si hay seleccionados, exportamos esos. Si no, exportamos la lista filtrada actual.
        if (selectedCheckboxes.length > 0) {
            const selectedIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.value));
            pedidosAExportar = pedidos.filter(p => selectedIds.includes(p.id));
        } else {
            pedidosAExportar = filteredPedidos;
        }

        if (pedidosAExportar.length === 0) {
            showToast('No hay pedidos para exportar', 'error');
            return;
        }

        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Encabezado del PDF
            doc.setFontSize(18);
            doc.text('Reporte de Pedidos - EcoBikeMess', 14, 22);
            doc.setFontSize(11);
            doc.text(`Fecha: ${new Date().toLocaleDateString()}`, 14, 30);

            // Definir columnas y filas para la tabla
            const tableColumn = ["Guía", "Fecha", "Destinatario", "Dirección", "Estado", "Costo"];
            const tableRows = pedidosAExportar.map(pedido => [
                pedido.guia,
                formatDate(pedido.fecha),
                pedido.destinatario.nombre,
                pedido.destinatario.direccion,
                getEstadoTexto(pedido.estado),
                '$' + pedido.paquete.costo.toLocaleString('es-CO')
            ]);

            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: 40,
            });

            doc.save('Reporte_Pedidos.pdf');
            showToast('✓ Reporte PDF generado exitosamente');
        } catch (error) {
            console.error('Error generando reporte:', error);
            alert('Error al generar el PDF. Verifica la consola.');
        }
    });
    */

    // ============================================
    // MODAL RÓTULO (GUÍA)
    // ============================================
    const rotuloModal = document.getElementById('rotuloModal');
    const closeRotuloModal = document.getElementById('closeRotuloModal');
    const btnDownloadRotulo = document.getElementById('btnDownloadRotulo');

    if (closeRotuloModal) {
        closeRotuloModal.addEventListener('click', () => {
            rotuloModal.style.display = 'none';
        });
    }

    if (rotuloModal) {
        rotuloModal.addEventListener('click', (e) => {
            if (e.target === rotuloModal) {
                rotuloModal.style.display = 'none';
            }
        });
    }

    window.openRotuloModal = function(id) {
        const pedido = pedidos.find(p => p.id === id);
        if (!pedido) return;

        // Llenar datos del rótulo
        document.getElementById('rotulo_guia_num').textContent = pedido.guia;
        document.getElementById('rotulo_fecha_creacion').textContent = formatDate(pedido.fecha);
        document.getElementById('rotulo_tipo_paquete').textContent = (pedido.paquete.tipo || 'Normal').toUpperCase();
        
        document.getElementById('rotulo_remitente').textContent = pedido.remitente.nombre;
        document.getElementById('rotulo_dir_remitente').textContent = pedido.remitente.direccion;
        document.getElementById('rotulo_tel_remitente').textContent = 'Tel: ' + pedido.remitente.telefono;
        
        document.getElementById('rotulo_destinatario').textContent = pedido.destinatario.nombre;
        document.getElementById('rotulo_dir_destinatario').textContent = pedido.destinatario.direccion;
        document.getElementById('rotulo_tel_destinatario').textContent = 'Tel: ' + pedido.destinatario.telefono;
        
        document.getElementById('rotulo_peso').textContent = (pedido.paquete.peso || '0') + ' kg';
        document.getElementById('rotulo_notas').textContent = pedido.instrucciones_entrega || 'Sin observaciones';

        // Generar QR
        const qrContainer = document.getElementById('rotulo_qr_code');
        qrContainer.innerHTML = ''; // Limpiar anterior
        
        // Construir datos completos para el QR
        const qrData = `GUIA: ${pedido.guia}
REM: ${pedido.remitente.nombre}
DEST: ${pedido.destinatario.nombre}
DIR: ${pedido.destinatario.direccion}
TEL: ${pedido.destinatario.telefono}
TIPO: ${pedido.paquete.tipo || 'Normal'}
FECHA: ${formatDate(pedido.fecha)}`;

        const qrCode = new QRCodeStyling({
            width: 160,
            height: 160,
            type: "canvas",
            data: qrData,
            dotsOptions: { color: "#000", type: "square" },
            backgroundOptions: { color: "#fff" }
        });
        qrCode.append(qrContainer);

        // Mostrar modal
        rotuloModal.style.display = 'flex';
    };

    if (btnDownloadRotulo) {
        btnDownloadRotulo.addEventListener('click', async () => {
            const element = document.getElementById('rotuloPreview');
            const guia = document.getElementById('rotulo_guia_num').textContent;
            
            try {
                const canvas = await html2canvas(element, { scale: 2, backgroundColor: '#ffffff' });
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a6'); // Tamaño A6 para etiquetas
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save(`Rotulo_${guia}.pdf`);
            } catch (error) {
                console.error('Error al generar PDF:', error);
                alert('Hubo un error al generar el PDF.');
            }
        });
    }
    
    // ============================================
    // EVENT LISTENERS
    // ============================================
    
    function setupEventListeners() {
        searchInput.addEventListener('input', applyFilters);
        filterEstado.addEventListener('change', applyFilters);
        filterFecha.addEventListener('change', applyFilters);
        filterOrden.addEventListener('change', applyFilters);
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.pedido-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }
        
        btnNuevoPedido.addEventListener('click', function() {
            window.location.href = 'enviarPaquete.php';
        });
        
        closeDetalleModal.addEventListener('click', function() {
            detalleModal.classList.remove('active');
            document.body.style.overflow = '';
        });
        
        detalleModal.addEventListener('click', function(e) {
            if (e.target === detalleModal) {
                detalleModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        document.getElementById('btnRastrear').addEventListener('click', function() {
            const id = parseInt(detalleModal.dataset.currentId);
            const pedido = pedidos.find(p => p.id === id);
            if (pedido) {
                showToast('Abriendo mapa de rastreo...', 'info');
                // window.location.href = `seguimiento.php?guia=${pedido.guia}`;
            }
        });
    }
    
    // ============================================
    // UTILIDADES
    // ============================================
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-CO', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric' 
        });
    }
    
    function formatDateFull(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('es-CO', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function getEstadoTexto(estado) {
        const estados = {
            'pendiente': 'Pendiente',
            'en_proceso': 'En Proceso',
            'en_transito': 'En Tránsito',
            'entregado': 'Entregado',
            'cancelado': 'Cancelado'
        };
        return estados[estado] || estado;
    }
    
    function showToast(message, type = 'success') {
        // Implementar toast (similar a otros archivos)
        alert(message);
    }
    
    function simulateNotification() {
        setTimeout(() => {
            showToast('🔔 Tu pedido ECO-2024-12346 está en camino', 'info');
        }, 3000);
    }
    
    // Inicializar
    init();
    
    console.log('Mis Pedidos cargado ✓');
});