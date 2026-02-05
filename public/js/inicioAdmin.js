// Variable global para el gráfico
let chartMovimientos;

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    setupEventListeners();
});

// Inicializar el dashboard
function initializeDashboard() {
    updateDateTime();
    loadUserData();
    loadChartData('dia'); // Cargar gráfico por defecto (Día)
    startAutoUpdate(); // Iniciar actualización automática
}

// Configurar event listeners
function setupEventListeners() {
    // Filtros del gráfico
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Actualizar clase activa
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Cargar datos
            loadChartData(this.dataset.period);
        });
    });
}

// Iniciar actualización automática (cada 5 segundos)
function startAutoUpdate() {
    setInterval(() => {
        updateDateTime();
        loadUserData();
        const activePeriod = document.querySelector('.filter-btn.active').dataset.period;
        loadChartData(activePeriod);
    }, 5000);
}

// Actualizar fecha y hora
function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    document.getElementById('currentDate').textContent = now.toLocaleDateString('es-ES', options);
}

// Cargar datos del usuario (Nombre)
async function loadUserData() {
    try {
        const response = await fetch('../../controller/inicioAdminController.php?action=get_dashboard_data');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('adminName').textContent = data.user_name;
            document.getElementById('lastUpdate').textContent = data.last_update;
            
            // Actualizar estadísticas
            if (data.stats) {
                updateStat('totalPaquetes', 'compPaquetes', data.stats.paquetes_ingresados, data.stats.paquetes_ayer);
                updateStat('enTransito', null, data.stats.en_transito);
                updateStat('entregados', 'compEntregados', data.stats.entregados, data.stats.entregados_ayer);
                updateStat('recoleccionesPend', null, data.stats.recolecciones_pendientes);
                updateStat('recoleccionesComp', null, data.stats.recolecciones_completadas);
                updateStat('mensajerosActivos', null, data.stats.mensajeros_activos);
                updateStat('ingresos', 'compIngresos', data.stats.ingresos_dia, data.stats.ingresos_ayer, true);
            }
        }
    } catch (error) {
        console.error('Error al cargar datos de usuario:', error);
    }
}

// Helper para actualizar números y comparaciones
function updateStat(elValueId, elCompId, current, previous, isCurrency = false) {
    const elValue = document.getElementById(elValueId);
    if (!elValue) return;

    // Formatear valor
    elValue.textContent = isCurrency 
        ? new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(current)
        : current;

    // Calcular comparación si existe elemento y valor previo
    if (elCompId && previous !== undefined && previous !== null) {
        const elComp = document.getElementById(elCompId);
        if (!elComp) return;

        let diff = current - previous;
        let percent = previous > 0 ? ((diff / previous) * 100).toFixed(1) : (current > 0 ? 100 : 0);
        
        let icon = diff > 0 ? '▲' : (diff < 0 ? '▼' : '-');
        let colorClass = diff > 0 ? 'trend-up' : (diff < 0 ? 'trend-down' : 'trend-neutral');
        
        elComp.innerHTML = `<span class="${colorClass}">${icon} ${Math.abs(percent)}%</span> vs ayer`;
    }
}

// Cargar datos del gráfico
async function loadChartData(period) {
    try {
        const response = await fetch(`../../controller/inicioAdminController.php?action=get_chart_data&period=${period}`);
        const result = await response.json();
        
        if (result.success) {
            renderChart(result.data, period);
        }
    } catch (error) {
        console.error('Error al cargar gráfico:', error);
    }
}

// Renderizar gráfico
function renderChart(data, period) {
    const ctx = document.getElementById('chartMovimientos').getContext('2d');
    
    // Procesar etiquetas según el periodo
    let labels = [];
    let values = [];
    
    // Mapeo de meses
    const monthNames = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
    
    data.forEach(item => {
        let label = item.label;
        if (period === 'dia') {
            label = `${item.label}:00`;
        } else if (period === 'anio') {
            label = monthNames[item.label - 1] || item.label;
        }
        // Para 'semana' y 'mes' usamos la fecha o número tal cual viene
        
        labels.push(label);
        values.push(item.cantidad);
    });

    // Si el gráfico ya existe, solo actualizamos los datos para evitar parpadeos
    if (chartMovimientos) {
        chartMovimientos.data.labels = labels;
        chartMovimientos.data.datasets[0].data = values;
        chartMovimientos.update();
    } else {
        // Si no existe, lo creamos desde cero
        chartMovimientos = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Envíos Realizados',
                data: values,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Envíos: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    }
}