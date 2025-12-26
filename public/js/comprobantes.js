document.addEventListener('DOMContentLoaded', function() {
    
    // Datos de ejemplo de comprobantes
    const comprobantesData = [
        {
            id: 1,
            guia: 'ECO-2024-12345',
            cliente: 'Juan Pérez',
            destinatario: 'María González',
            direccion: 'Calle 100 #15-30, Zona Norte',
            quienRecibio: 'María González',
            parentesco: 'Destinatario',
            fechaEntrega: '2024-12-14 15:30',
            recaudo: 50000,
            observaciones: 'Paquete entregado en perfecto estado. Cliente satisfecho con el servicio.',
            foto: 'https://via.placeholder.com/400x300/5cb85c/ffffff?text=Evidencia+Entrega',
            estado: 'entregado',
            fechaGeneracion: '2024-12-14 15:35'
        },
        {
            id: 2,
            guia: 'ECO-2024-12346',
            cliente: 'Juan Pérez',
            destinatario: 'Carlos Rodríguez',
            direccion: 'Carrera 7 #80-45, Zona Centro',
            quienRecibio: 'Portero del edificio',
            parentesco: 'Portero',
            fechaEntrega: '2024-12-13 10:15',
            recaudo: 0,
            observaciones: 'Entregado en portería. Destinatario no se encontraba en casa.',
            foto: 'https://via.placeholder.com/400x300/2196f3/ffffff?text=Evidencia+Entrega',
            estado: 'entregado',
            fechaGeneracion: '2024-12-13 10:20'
        },
        {
            id: 3,
            guia: 'ECO-2024-12347',
            cliente: 'Juan Pérez',
            destinatario: 'Ana Martínez',
            direccion: 'Avenida 68 #25-10, Zona Sur',
            quienRecibio: 'Ana Martínez',
            parentesco: 'Destinatario',
            fechaEntrega: '2024-12-12 14:20',
            recaudo: 75000,
            observaciones: 'Entrega exitosa. Paquete verificado por el destinatario.',
            foto: 'https://via.placeholder.com/400x300/ff9800/ffffff?text=Evidencia+Entrega',
            estado: 'entregado',
            fechaGeneracion: '2024-12-12 14:25'
        },
        {
            id: 4,
            guia: 'ECO-2024-12348',
            cliente: 'Juan Pérez',
            destinatario: 'Pedro López',
            direccion: 'Calle 26 #68-91, Zona Occidente',
            quienRecibio: 'Secretaria',
            parentesco: 'Empleada',
            fechaEntrega: '2024-12-11 16:45',
            recaudo: 0,
            observaciones: 'Entregado en recepción de oficina.',
            foto: 'https://via.placeholder.com/400x300/9c27b0/ffffff?text=Evidencia+Entrega',
            estado: 'entregado',
            fechaGeneracion: '2024-12-11 16:50'
        },
        {
            id: 5,
            guia: 'ECO-2024-12349',
            cliente: 'Juan Pérez',
            destinatario: 'Laura Sánchez',
            direccion: 'Transversal 45 #12-67, Zona Norte',
            quienRecibio: 'Vecino',
            parentesco: 'Vecino',
            fechaEntrega: '2024-12-10 11:30',
            recaudo: 35000,
            observaciones: 'Entregado a vecino autorizado. Destinatario no estaba disponible.',
            foto: 'https://via.placeholder.com/400x300/4caf50/ffffff?text=Evidencia+Entrega',
            estado: 'entregado',
            fechaGeneracion: '2024-12-10 11:35'
        }
    ];
    
    let comprobantes = [...comprobantesData];
    let filteredComprobantes = [...comprobantes];
    let currentPage = 1;
    const itemsPerPage = 10;
    
    // Elementos del DOM
    const searchInput = document.getElementById('searchInput');
    const filterPeriodo = document.getElementById('filterPeriodo');
    const filterEstado = document.getElementById('filterEstado');
    const btnApplyFilters = document.getElementById('btnApplyFilters');
    const btnRefresh = document.getElementById('btnRefresh');
    const customDateRange = document.getElementById('customDateRange');
    const btnApplyDateRange = document.getElementById('btnApplyDateRange');
    const comprobantesList = document.getElementById('comprobantesList');
    const comprobantesTableBody = document.getElementById('comprobantesTableBody');
    const noResults = document.getElementById('noResults');
    const loading = document.getElementById('loading');
    const modal = document.getElementById('comprobanteModal');
    const closeModal = document.getElementById('closeModal');
    const btnDownloadPDF = document.getElementById('btnDownloadPDF');
    
    // ============================================
    // INICIALIZAR
    // ============================================
    
    function init() {
        updateStats();
        renderComprobantes();
        setupEventListeners();
        simulateNotification();
    }
    
    // ============================================
    // ACTUALIZAR ESTADÍSTICAS
    // ============================================
    
    function updateStats() {
        const totalComprobantes = comprobantes.length;
        const entregadosMes = comprobantes.filter(c => c.estado === 'entregado').length;
        const recaudosMes = comprobantes.reduce((sum, c) => sum + c.recaudo, 0);
        
        document.getElementById('totalComprobantes').textContent = totalComprobantes;
        document.getElementById('entregadosMes').textContent = entregadosMes;
        document.getElementById('recaudosMes').textContent = '$' + recaudosMes.toLocaleString('es-CO');
    }
    
    // ============================================
    // RENDERIZAR COMPROBANTES
    // ============================================
    
    function renderComprobantes() {
        loading.style.display = 'none';
        
        if (filteredComprobantes.length === 0) {
            comprobantesTableBody.innerHTML = '';
            noResults.style.display = 'block';
            return;
        }
        
        noResults.style.display = 'none';
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedComprobantes = filteredComprobantes.slice(startIndex, endIndex);
        
        // Vista Lista (única)
        comprobantesTableBody.innerHTML = paginatedComprobantes.map(comp => `
            <tr onclick="verComprobante(${comp.id})">
                <td class="table-guia">${comp.guia}</td>
                <td>${comp.destinatario}</td>
                <td>${comp.quienRecibio}</td>
                <td>${formatDate(comp.fechaEntrega)}</td>
                <td>${comp.recaudo > 0 ? '$' + comp.recaudo.toLocaleString('es-CO') : '-'}</td>
                <td><span class="status-badge status-${comp.estado}">${comp.estado === 'entregado' ? 'Entregado' : 'Pendiente'}</span></td>
                <td class="table-actions">
                    <button class="icon-btn" onclick="event.stopPropagation(); verComprobante(${comp.id})" title="Ver detalles">📋</button>
                    <button class="icon-btn" onclick="event.stopPropagation(); descargarPDF(${comp.id})" title="Descargar">⬇️</button>
                </td>
            </tr>
        `).join('');
        
        renderPagination();
    }
    
    // ============================================
    // PAGINACIÓN
    // ============================================
    
    function renderPagination() {
        const totalPages = Math.ceil(filteredComprobantes.length / itemsPerPage);
        const pageNumbers = document.getElementById('pageNumbers');
        const btnPrevPage = document.getElementById('btnPrevPage');
        const btnNextPage = document.getElementById('btnNextPage');
        
        btnPrevPage.disabled = currentPage === 1;
        btnNextPage.disabled = currentPage === totalPages;
        
        let pagesHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            pagesHTML += `<div class="page-number ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</div>`;
        }
        pageNumbers.innerHTML = pagesHTML;
        
        btnPrevPage.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                renderComprobantes();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };
        
        btnNextPage.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderComprobantes();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        };
    }
    
    window.goToPage = function(page) {
        currentPage = page;
        renderComprobantes();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    // ============================================
    // FILTROS Y BÚSQUEDA
    // ============================================
    
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const periodo = filterPeriodo.value;
        const estado = filterEstado.value;
        
        filteredComprobantes = comprobantes.filter(comp => {
            const matchSearch = comp.guia.toLowerCase().includes(searchTerm) ||
                              comp.destinatario.toLowerCase().includes(searchTerm) ||
                              (comp.quienRecibio && comp.quienRecibio.toLowerCase().includes(searchTerm));
            
            const matchEstado = estado === 'all' || comp.estado === estado;
            
            let matchPeriodo = true;
            if (periodo !== 'all') {
                const fechaComp = new Date(comp.fechaEntrega);
                const hoy = new Date();
                
                if (periodo === 'today') {
                    matchPeriodo = fechaComp.toDateString() === hoy.toDateString();
                } else if (periodo === 'week') {
                    const weekAgo = new Date(hoy.getTime() - 7 * 24 * 60 * 60 * 1000);
                    matchPeriodo = fechaComp >= weekAgo;
                } else if (periodo === 'month') {
                    matchPeriodo = fechaComp.getMonth() === hoy.getMonth() && 
                                 fechaComp.getFullYear() === hoy.getFullYear();
                } else if (periodo === 'year') {
                    matchPeriodo = fechaComp.getFullYear() === hoy.getFullYear();
                }
            }
            
            return matchSearch && matchEstado && matchPeriodo;
        });
        
        currentPage = 1;
        renderComprobantes();
    }
    
    // ============================================
    // VER COMPROBANTE EN MODAL
    // ============================================
    
    window.verComprobante = function(id) {
        const comp = comprobantes.find(c => c.id === id);
        if (!comp) return;
        
        document.getElementById('modal_guia').textContent = comp.guia;
        document.getElementById('modal_cliente').textContent = comp.cliente;
        document.getElementById('modal_direccion').textContent = comp.direccion;
        document.getElementById('modal_quien_recibio').textContent = comp.quienRecibio;
        document.getElementById('modal_parentesco').textContent = comp.parentesco;
        document.getElementById('modal_fecha_entrega').textContent = formatDateFull(comp.fechaEntrega);
        document.getElementById('modal_recaudo').textContent = comp.recaudo > 0 ? 
            '$' + comp.recaudo.toLocaleString('es-CO') : 'Sin recaudo';
        document.getElementById('modal_foto').src = comp.foto;
        document.getElementById('modal_observaciones').textContent = comp.observaciones;
        document.getElementById('modal_fecha_generacion').textContent = formatDateFull(comp.fechaGeneracion);
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Guardar ID actual para usar en descarga/impresión
        modal.dataset.currentId = id;
    };
    
    // ============================================
    // CERRAR MODAL
    // ============================================
    
    closeModal.addEventListener('click', function() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // ============================================
    // DESCARGAR PDF
    // ============================================
    
    window.descargarPDF = async function(id) {
        const comp = comprobantes.find(c => c.id === id);
        if (!comp) return;
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Configuración
        const margin = 20;
        let yPos = margin;
        
        // Logo y título
        doc.setFontSize(20);
        doc.setTextColor(92, 184, 92);
        doc.text('EcoBikeMess', margin, yPos);
        yPos += 8;
        
        doc.setFontSize(10);
        doc.setTextColor(100, 100, 100);
        doc.text('Mensajería Ecológica', margin, yPos);
        yPos += 15;
        
        // Número de guía
        doc.setFontSize(16);
        doc.setTextColor(0, 0, 0);
        doc.text('Comprobante de Entrega', margin, yPos);
        yPos += 10;
        
        doc.setFontSize(12);
        doc.setTextColor(92, 184, 92);
        doc.text(`Guía: ${comp.guia}`, margin, yPos);
        yPos += 15;
        
        // Datos del cliente y entrega
        doc.setFontSize(12);
        doc.setTextColor(0, 0, 0);
        doc.text('Datos de Entrega:', margin, yPos);
        yPos += 7;
        
        doc.setFontSize(10);
        doc.setTextColor(80, 80, 80);
        doc.text(`Recibió: ${comp.quienRecibio}`, margin + 5, yPos);
        yPos += 6;
        doc.text(`Parentesco/Cargo: ${comp.parentesco}`, margin + 5, yPos);
        yPos += 6;
        doc.text(`Fecha y Hora: ${formatDateFull(comp.fechaEntrega)}`, margin + 5, yPos);
        yPos += 6;
        doc.text(`Recaudo: ${comp.recaudo > 0 ? '$' + comp.recaudo.toLocaleString('es-CO') : 'Sin recaudo'}`, margin + 5, yPos);
        yPos += 12;
        
        // Observaciones
        doc.setFontSize(12);
        doc.setTextColor(0, 0, 0);
        doc.text('Observaciones:', margin, yPos);
        yPos += 7;
        
        doc.setFontSize(10);
        doc.setTextColor(80, 80, 80);
        const splitObservaciones = doc.splitTextToSize(comp.observaciones, 170);
        doc.text(splitObservaciones, margin + 5, yPos);
        yPos += (splitObservaciones.length * 6) + 12;
        
        // Nota sobre la foto
        doc.setFontSize(10);
        doc.setTextColor(100, 100, 100);
        doc.text('[Evidencia fotográfica disponible en el sistema]', margin, yPos);
        yPos += 15;
        
        // Footer
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text('Este comprobante fue generado automáticamente por el sistema EcoBikeMess', margin, 270);
        doc.text(`Fecha de generación: ${formatDateFull(comp.fechaGeneracion)}`, margin, 275);
        
        // Guardar PDF
        doc.save(`Comprobante-${comp.guia}.pdf`);
        
        showNotification('✓ PDF descargado exitosamente');
    };
    
    // Botón de descarga en modal
    btnDownloadPDF.addEventListener('click', function() {
        const id = parseInt(modal.dataset.currentId);
        if (id) {
            descargarPDF(id);
        }
    });

    // ============================================
    // EVENT LISTENERS
    // ============================================
    
    function setupEventListeners() {
        searchInput.addEventListener('input', applyFilters);
        btnApplyFilters.addEventListener('click', applyFilters);
        
        filterPeriodo.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.style.display = 'block';
            } else {
                customDateRange.style.display = 'none';
                applyFilters();
            }
        });
        
        btnApplyDateRange.addEventListener('click', applyFilters);
        
        btnRefresh.addEventListener('click', function() {
            this.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                this.style.transform = '';
                location.reload();
            }, 500);
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
    
    function showNotification(message) {
        const notif = document.createElement('div');
        notif.textContent = message;
        notif.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: #5cb85c;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 3000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notif);
        
        setTimeout(() => {
            notif.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notif.remove(), 300);
        }, 3000);
    }
    
    // ============================================
    // SIMULAR NOTIFICACIÓN AUTOMÁTICA
    // ============================================
    
    function simulateNotification() {
        // Simular que se generó un nuevo comprobante
        setTimeout(() => {
            showNotification('🔔 Nuevo comprobante generado para guía ECO-2024-12345');
        }, 2000);
    }
    
    // Agregar estilos de animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Inicializar
    init();
    
    console.log('Sistema de comprobantes cargado ✓');
});