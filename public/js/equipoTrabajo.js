document.addEventListener('DOMContentLoaded', function() {
    cargarColaboradores();
    cargarHistorial();

    // Manejar envío del formulario de creación
    const formCrear = document.getElementById('formCrearColaborador');
    if(formCrear) {
        formCrear.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../../controller/ColaboradorController.php?op=guardar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    alert('Colaborador creado exitosamente');
                    document.getElementById('modalCrear').style.display = 'none';
                    formCrear.reset();
                    cargarColaboradores();
                } else {
                    alert('Error: ' + data.msg);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});

function cargarColaboradores() {
    fetch('../../controller/ColaboradorController.php?op=listar')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('listaColaboradores');
        container.innerHTML = '';

        if(data.data && data.data.length > 0) {
            data.data.forEach(colab => {
                const card = crearTarjetaColaborador(colab);
                container.appendChild(card);
            });
            
            // Actualizar contadores
            document.getElementById('totalMiembros').textContent = data.data.length;
            const activos = data.data.filter(c => c.estado_colaborador === 'activo').length;
            document.getElementById('miembrosActivos').textContent = activos;
        } else {
            container.innerHTML = '<p class="text-center w-100">No hay colaboradores registrados.</p>';
        }
    });
}

function crearTarjetaColaborador(colab) {
    const div = document.createElement('div');
    div.className = 'member-card';
    
    const iniciales = (colab.nombres[0] + colab.apellidos[0]).toUpperCase();
    const estadoClass = colab.estado_colaborador === 'activo' ? 'status-active' : 'status-inactive';
    
    // Generar etiquetas de permisos
    let permisosHTML = '';
    if(colab.puede_crear_paquetes) permisosHTML += '<span class="perm-tag">📦 Paquetes</span>';
    if(colab.puede_ver_facturas) permisosHTML += '<span class="perm-tag">💰 Facturas</span>';
    if(colab.puede_gestionar_recolecciones) permisosHTML += '<span class="perm-tag">🚚 Recolecciones</span>';

    div.innerHTML = `
        <div class="member-status ${estadoClass}" title="${colab.estado_colaborador}"></div>
        <div class="member-header">
            <div class="member-avatar">${iniciales}</div>
            <div class="member-info">
                <h3>${colab.nombres} ${colab.apellidos}</h3>
                <span class="member-role">${colab.cargo}</span>
            </div>
        </div>
        <div class="member-contact">
            <small>📧 ${colab.correo}</small><br>
            <small>📱 ${colab.telefono}</small>
        </div>
        <div class="member-permissions">
            ${permisosHTML}
        </div>
        <div class="member-actions" style="margin-top: 1rem; border-top: 1px solid #eee; padding-top: 0.5rem;">
            <button onclick="cambiarEstado(${colab.colaborador_id}, '${colab.estado_colaborador === 'activo' ? 'inactivo' : 'activo'}')" 
                    class="btn-sm ${colab.estado_colaborador === 'activo' ? 'btn-danger' : 'btn-success'}">
                ${colab.estado_colaborador === 'activo' ? 'Desactivar' : 'Activar'}
            </button>
        </div>
    `;
    return div;
}

function cargarHistorial() {
    fetch('../../controller/ColaboradorController.php?op=historial')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('listaHistorial');
        container.innerHTML = '';
        
        if(data.data) {
            data.data.forEach(log => {
                const item = document.createElement('div');
                item.className = 'activity-item';
                item.innerHTML = `
                    <div class="activity-icon">📝</div>
                    <div class="activity-content">
                        <strong>${log.nombres} ${log.apellidos}</strong> (${log.cargo})
                        <p>${log.descripcion}</p>
                        <small class="text-muted">${new Date(log.fecha_accion).toLocaleString()}</small>
                    </div>
                `;
                container.appendChild(item);
            });
        }
    });
}

function cambiarEstado(id, nuevoEstado) {
    if(!confirm(`¿Estás seguro de cambiar el estado a ${nuevoEstado}?`)) return;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('estado', nuevoEstado);

    fetch('../../controller/ColaboradorController.php?op=cambiar_estado', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            cargarColaboradores();
        } else {
            alert('Error al cambiar estado');
        }
    });
}

// Funciones para el modal
function abrirModal() {
    document.getElementById('modalCrear').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalCrear').style.display = 'none';
}

// Cerrar modal si se hace clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('modalCrear');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
