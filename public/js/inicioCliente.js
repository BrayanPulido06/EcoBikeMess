document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // GRÁFICO DE ACTIVIDAD MENSUAL
    // ============================================
    const ctx = document.getElementById('activityChart');
    
    if (ctx) {
        // Usar datos dinámicos si existen, sino usar datos de ejemplo
        const chartData = window.dashboardChartData || {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            total: [12, 19, 15, 25, 22, 30, 28, 32, 26, 35, 29, 24],
            entregados: [10, 17, 13, 23, 20, 28, 25, 30, 24, 33, 27, 22]
        };

        const activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Envíos Realizados',
                        data: chartData.total,
                        borderColor: '#5cb85c',
                        backgroundColor: 'rgba(92, 184, 92, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#5cb85c',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Envíos Entregados',
                        data: chartData.entregados,
                        borderColor: '#2196f3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#2196f3',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12,
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(45, 62, 80, 0.9)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        borderColor: '#e8f5f1',
                        borderWidth: 1,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' envíos';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5,
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: '#f8fdf9',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Cambiar período del gráfico
        const periodSelect = document.querySelector('.period-select');
        if (periodSelect) {
            periodSelect.addEventListener('change', function() {
                const period = this.value;
                let periodKey = 'year';
                
                // Mapear el texto del select a claves para el backend
                if (period === 'Últimos 30 días') periodKey = '30_days';
                else if (period === 'Últimos 3 meses') periodKey = '3_months';
                else periodKey = 'year';

                // Llamada AJAX al controlador
                fetch(`../../controller/inicioClienteController.php?op=grafica&periodo=${periodKey}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Actualizar datos del gráfico
                        activityChart.data.datasets[0].data = data.total;
                        activityChart.data.datasets[1].data = data.entregados;
                        activityChart.update();
                    } else {
                        console.error('Error al cargar datos:', data.msg);
                    }
                })
                .catch(err => console.error('Error de red:', err));
            });
        }
    }
    
    // ============================================
    // ACCIONES DE LA TABLA
    // ============================================
    const iconButtons = document.querySelectorAll('.icon-btn');
    
    iconButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const title = this.getAttribute('title');
            const row = this.closest('tr');
            const orderId = row?.querySelector('.order-id')?.textContent;
            
            if (title === 'Ver detalles') {
                console.log('Ver detalles del pedido:', orderId);
                // window.location.href = `detallePedido.php?id=${orderId}`;
                alert(`Ver detalles del pedido ${orderId}`);
            } else if (title === 'Descargar') {
                console.log('Descargar comprobante:', orderId);
                alert(`Descargando comprobante ${orderId}...`);
            } else if (title === 'Rastrear') {
                console.log('Rastrear pedido:', orderId);
                // window.location.href = `seguimiento.php?id=${orderId}`;
                alert(`Rastreando pedido ${orderId}...`);
            } else if (title === 'Cancelar') {
                if (confirm(`¿Estás seguro de cancelar el pedido ${orderId}?`)) {
                    console.log('Cancelar pedido:', orderId);
                    alert(`Pedido ${orderId} cancelado`);
                }
            }
        });
    });
    
    // ============================================
    // CLICK EN FILA DE LA TABLA
    // ============================================
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    
    tableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // No hacer nada si se hizo click en un botón
            if (e.target.closest('.icon-btn')) return;
            
            const orderId = this.querySelector('.order-id')?.textContent;
            console.log('Click en fila:', orderId);
            // window.location.href = `detallePedido.php?id=${orderId}`;
        });
        
        // Estilo de hover cursor
        row.style.cursor = 'pointer';
    });
    
    // ============================================
    // DESCARGAR COMPROBANTES
    // ============================================
    const receiptItems = document.querySelectorAll('.receipt-item');
    
    receiptItems.forEach(item => {
        const downloadBtn = item.querySelector('.icon-btn');
        
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const receiptTitle = item.querySelector('.receipt-title').textContent;
                console.log('Descargar:', receiptTitle);
                alert(`Descargando ${receiptTitle}...`);
            });
        }
        
        // Click en toda la fila del comprobante
        item.addEventListener('click', function() {
            const receiptTitle = this.querySelector('.receipt-title').textContent;
            console.log('Ver comprobante:', receiptTitle);
            // window.location.href = `verComprobante.php?id=${receiptId}`;
        });
    });
    
    // ============================================
    // ANIMACIÓN DE NÚMEROS (CONTADOR)
    // ============================================
    function animateValue(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16); // 60 FPS
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            
            // Formatear número según el tipo
            if (element.textContent.includes('$')) {
                element.textContent = '$' + Math.floor(current).toLocaleString('es-CO');
            } else {
                element.textContent = Math.floor(current).toString();
            }
        }, 16);
    }
    
    // Animar estadísticas al cargar
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(stat => {
        const text = stat.textContent;
        let targetValue = 0;
        
        if (text.includes('$')) {
            // Limpiar formato moneda para obtener el número
            targetValue = parseInt(text.replace(/[$.]/g, '').trim());
        } else {
            targetValue = parseInt(text);
        }
        
        if (!isNaN(targetValue)) {
            // Resetear valor visual para la animación
            stat.textContent = text.includes('$') ? '$0' : '0';
            setTimeout(() => {
                animateValue(stat, 0, targetValue, 1000);
            }, 100);
        }
    });
    
    // ============================================
    // ACTUALIZAR DATOS EN TIEMPO REAL (SIMULADO)
    // ============================================
    function updateRealtimeData() {
        // Aquí irían las llamadas AJAX para actualizar datos
        console.log('Actualizando datos en tiempo real...');
        
        // Ejemplo: actualizar badge de en tránsito
        const enTransitoValue = document.querySelectorAll('.stat-value')[1];
        if (enTransitoValue) {
            const currentValue = parseInt(enTransitoValue.textContent);
            // Simular cambio aleatorio
            const change = Math.random() > 0.5 ? 1 : -1;
            const newValue = Math.max(0, currentValue + change);
            enTransitoValue.textContent = newValue.toString();
        }
    }
    
    // Actualizar cada 30 segundos (en producción esto vendría del servidor)
    // setInterval(updateRealtimeData, 30000);
    
    // ============================================
    // EFECTOS VISUALES
    // ============================================
    
    // Agregar efecto de carga a las cards
    const cards = document.querySelectorAll('.card, .stat-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
    
    console.log('Dashboard de cliente cargado ✓');
});