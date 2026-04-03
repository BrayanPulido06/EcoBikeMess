document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchClienteAdmin');
    const results = document.getElementById('resultadosClientesAdmin');
    const selectedCard = document.getElementById('clienteSeleccionadoAdmin');
    const btnCambiar = document.getElementById('btnCambiarClienteAdmin');
    const clienteIdInput = document.getElementById('cliente_id');
    const form = document.getElementById('envioForm');
    const autoFillBtn = document.getElementById('autoFillRemitente');

    if (!searchInput || !results || !selectedCard || !clienteIdInput) return;

    let clientes = [];
    let clienteSeleccionado = null;

    const fetchClientes = async () => {
        try {
            const resp = await fetch('../../controller/añadirAdminController.php?action=listar_clientes');
            const data = await resp.json();
            if (data.success) {
                clientes = data.data || [];
            } else {
                console.error('Error al listar clientes:', data.message);
            }
        } catch (err) {
            console.error('Error al cargar clientes:', err);
        }
    };

    const renderResults = (items) => {
        if (!items.length) {
            results.innerHTML = '<div class="admin-client-result"><p>Sin resultados</p></div>';
            results.classList.add('active');
            return;
        }

        results.innerHTML = items.map(c => {
            const nombre = c.emprendimiento || c.nombreContacto || 'Cliente';
            const telefono = c.telefono || 'Sin teléfono';
            const email = c.email || 'Sin email';
            return `
                <div class="admin-client-result" data-id="${c.id}">
                    <h4>${nombre}</h4>
                    <p>Contacto: ${c.nombreContacto || 'No registrado'}</p>
                    <p>📞 ${telefono} | 📧 ${email}</p>
                </div>
            `;
        }).join('');
        results.classList.add('active');
    };

    const seleccionarCliente = (id) => {
        const cliente = clientes.find(c => String(c.id) === String(id));
        if (!cliente) return;
        clienteSeleccionado = cliente;

        const nombre = cliente.emprendimiento || cliente.nombreContacto || 'Cliente';
        const contacto = cliente.nombreContacto || '-';

        document.getElementById('clienteAdminNombre').textContent = nombre;
        document.getElementById('clienteAdminContacto').textContent = contacto;
        document.getElementById('clienteAdminTelefono').textContent = cliente.telefono || '-';
        document.getElementById('clienteAdminEmail').textContent = cliente.email || '-';
        document.getElementById('clienteAdminDireccion').textContent = cliente.direccion || '-';
        document.getElementById('clienteAdminEstado').textContent = cliente.estado || 'activo';

        selectedCard.classList.remove('hidden');
        results.classList.remove('active');
        searchInput.value = '';
        clienteIdInput.value = cliente.id;

        if (btnCambiar) btnCambiar.style.display = 'inline-flex';

        window.remitenteData = {
            nombre_tienda: nombre,
            nombre_completo: contacto || nombre,
            telefono: cliente.telefono || '',
            correo: cliente.email || '',
            direccion: cliente.direccion || ''
        };

        if (autoFillBtn) autoFillBtn.click();
    };

    const limpiarSeleccion = () => {
        clienteSeleccionado = null;
        clienteIdInput.value = '';
        selectedCard.classList.add('hidden');
        if (btnCambiar) btnCambiar.style.display = 'none';
        searchInput.value = '';
        searchInput.focus();

        window.remitenteData = {
            nombre_tienda: '',
            nombre_completo: '',
            telefono: '',
            correo: '',
            direccion: ''
        };
    };

    searchInput.addEventListener('input', () => {
        const term = searchInput.value.trim().toLowerCase();
        if (term.length < 2) {
            results.classList.remove('active');
            return;
        }
        const filtered = clientes.filter(c => {
            const fields = [
                c.emprendimiento,
                c.nombreContacto,
                c.telefono,
                c.email,
                c.direccion
            ].filter(Boolean).join(' ').toLowerCase();
            return fields.includes(term);
        });
        renderResults(filtered);
    });

    results.addEventListener('click', (e) => {
        const item = e.target.closest('.admin-client-result');
        if (!item) return;
        seleccionarCliente(item.dataset.id);
    });

    if (btnCambiar) {
        btnCambiar.addEventListener('click', () => {
            limpiarSeleccion();
        });
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            if (!clienteIdInput.value) {
                e.preventDefault();
                alert('Debe seleccionar un cliente antes de registrar el envío.');
            }
        });
    }

    fetchClientes();
});
