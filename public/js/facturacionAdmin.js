/**
 * facturacionAdmin.js
 * Lógica principal del módulo de facturación administrativa
 */

const FacturacionAdmin = (() => {

  // ── DATOS DE EJEMPLO ──────────────────────────────────────────────────────

  const DATA = {
    clientes: [
      {
        id: 1, cliente: 'Tienda El Sol', numGuia: 'GU-001234',
        destinatario: 'Juan Pérez', recaudo: 45000, recaudado: 45000,
        costoEnvio: 8500, enRecaudo: true,
        fechaEnvio: '2025-06-01', fechaEntrega: '2025-06-03',
        estado: 'entregado', comprobantes: [], observaciones: ''
      },
      {
        id: 2, cliente: 'Distribuidora Norte', numGuia: 'GU-001235',
        destinatario: 'María García', recaudo: 0, recaudado: 0,
        costoEnvio: 6000, enRecaudo: false,
        fechaEnvio: '2025-06-02', fechaEntrega: null,
        estado: 'en_camino', comprobantes: [], observaciones: 'Llamar antes de entregar'
      },
      {
        id: 3, cliente: 'Almacén Central', numGuia: 'GU-001236',
        destinatario: 'Carlos Ruiz', recaudo: 120000, recaudado: 0,
        costoEnvio: 12000, enRecaudo: true,
        fechaEnvio: '2025-06-03', fechaEntrega: null,
        estado: 'pendiente', comprobantes: [], observaciones: ''
      },
      {
        id: 4, cliente: 'Fashion Store', numGuia: 'GU-001237',
        destinatario: 'Ana López', recaudo: 0, recaudado: 0,
        costoEnvio: 7000, enRecaudo: false,
        fechaEnvio: '2025-05-30', fechaEntrega: '2025-06-01',
        estado: 'entregado', comprobantes: [], observaciones: 'Entregado en portería'
      }
    ],
    recolecciones: [
      {
        id: 1, mensajero: 'Pedro Martínez', direccion: 'Cra 15 #45-23, Bogotá',
        cliente: 'Tienda El Sol', cantPaquetes: 3,
        guias: ['GU-001234', 'GU-001238', 'GU-001239'],
        fechaAsignacion: '2025-06-01', fechaRecoleccion: '2025-06-01'
      },
      {
        id: 2, mensajero: 'Luis Herrera', direccion: 'Av Caracas #80-10, Bogotá',
        cliente: 'Distribuidora Norte', cantPaquetes: 5,
        guias: ['GU-001235', 'GU-001240', 'GU-001241', 'GU-001242', 'GU-001243'],
        fechaAsignacion: '2025-06-02', fechaRecoleccion: '2025-06-02'
      },
      {
        id: 3, mensajero: 'Pedro Martínez', direccion: 'Cl 100 #15-50, Bogotá',
        cliente: 'Almacén Central', cantPaquetes: 2,
        guias: ['GU-001236', 'GU-001244'],
        fechaAsignacion: '2025-06-03', fechaRecoleccion: null
      }
    ],
    entregas: [
      {
        id: 1, mensajero: 'Pedro Martínez', numGuia: 'GU-001234',
        destinatario: 'Juan Pérez', recaudo: 45000, recaudado: 45000,
        observaciones: '', estado: 'entregado',
        comprobantes: [], pagoEntrega: 8500
      },
      {
        id: 2, mensajero: 'Luis Herrera', numGuia: 'GU-001235',
        destinatario: 'María García', recaudo: 0, recaudado: 0,
        observaciones: 'Llamar antes de entregar', estado: 'en_camino',
        comprobantes: [], pagoEntrega: null
      },
      {
        id: 3, mensajero: 'Sandra Vargas', numGuia: 'GU-001237',
        destinatario: 'Ana López', recaudo: 0, recaudado: 0,
        observaciones: 'Entregado en portería', estado: 'entregado',
        comprobantes: [], pagoEntrega: 7000
      }
    ]
  };

  // ── ESTADO ────────────────────────────────────────────────────────────────

  const state = {
    activeRole: 'clientes',
    activeSub: 'recolecciones',
    filters: {
      clientes:       { nombre: '', desde: '', hasta: '' },
      recolecciones:  { nombre: '', desde: '', hasta: '' },
      entregas:       { nombre: '', desde: '', hasta: '' }
    },
    modal: { type: null, recordId: null, subtype: null }
  };

  // ── UTILIDADES ────────────────────────────────────────────────────────────

  const fmt = {
    currency: n => n > 0 ? `$${n.toLocaleString('es-CO')}` : '—',
    date:     d => d ? new Date(d + 'T12:00:00').toLocaleDateString('es-CO') : '—',
    bool:     b => b ? '<span class="badge badge-green">✓ Sí</span>' : '<span class="badge badge-red">✗ No</span>'
  };

  const estadoBadge = e => ({
    entregado:  '<span class="badge badge-green">Entregado</span>',
    en_camino:  '<span class="badge badge-blue">En camino</span>',
    pendiente:  '<span class="badge badge-yellow">Pendiente</span>'
  }[e] || '<span class="badge badge-yellow">—</span>');

  function filterByFecha(date, desde, hasta) {
    if (!date) return true;
    const d = new Date(date);
    if (desde && d < new Date(desde)) return false;
    if (hasta && d > new Date(hasta)) return false;
    return true;
  }

  function showToast(msg, type = 'success') {
    const tc = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span>${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span> ${msg}`;
    tc.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.4s'; setTimeout(() => t.remove(), 400); }, 3000);
  }

  // ── RENDER CLIENTES ───────────────────────────────────────────────────────

  function renderClientes() {
    const f = state.filters.clientes;
    const list = DATA.clientes.filter(r => {
      if (f.nombre && !r.cliente.toLowerCase().includes(f.nombre.toLowerCase()) && !r.destinatario.toLowerCase().includes(f.nombre.toLowerCase())) return false;
      if (!filterByFecha(r.fechaEnvio, f.desde, f.hasta)) return false;
      return true;
    });

    const tbody = document.getElementById('tbodyClientes');
    const count = document.getElementById('countClientes');
    count.textContent = `${list.length} registros`;

    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📦</div><p>Sin registros con los filtros aplicados</p></div></td></tr>`;
      return;
    }

    tbody.innerHTML = list.map(r => `
      <tr>
        <td>${r.cliente}</td>
        <td class="cell-mono">${r.numGuia}</td>
        <td>${r.destinatario}</td>
        <td>${fmt.currency(r.recaudo)}</td>
        <td>${r.recaudado >= r.recaudo && r.recaudo > 0 ? `<span class="badge badge-green">${fmt.currency(r.recaudado)}</span>` : fmt.currency(r.recaudado)}</td>
        <td>${fmt.currency(r.costoEnvio)}</td>
        <td>${fmt.bool(r.enRecaudo)}</td>
        <td>${fmt.date(r.fechaEnvio)}</td>
        <td>${fmt.date(r.fechaEntrega)}</td>
        <td>
          <div class="actions-cell">
            <button class="action-btn view" title="Ver detalle" onclick="FacturacionAdmin.openModal('ver','cliente',${r.id})">👁</button>
            <button class="action-btn upload" title="Agregar comprobante" onclick="FacturacionAdmin.openModal('comprobante','cliente',${r.id})">📎</button>
            <button class="action-btn obs" title="Observaciones" onclick="FacturacionAdmin.openModal('obs','cliente',${r.id})">📝</button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  // ── RENDER RECOLECCIONES ──────────────────────────────────────────────────

  function renderRecolecciones() {
    const f = state.filters.recolecciones;
    const list = DATA.recolecciones.filter(r => {
      if (f.nombre && !r.mensajero.toLowerCase().includes(f.nombre.toLowerCase())) return false;
      if (!filterByFecha(r.fechaAsignacion, f.desde, f.hasta)) return false;
      return true;
    });

    const tbody = document.getElementById('tbodyRecolecciones');
    const count = document.getElementById('countRecolecciones');
    count.textContent = `${list.length} registros`;

    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🛵</div><p>Sin recolecciones con los filtros aplicados</p></div></td></tr>`;
      return;
    }

    tbody.innerHTML = list.map(r => `
      <tr>
        <td>${r.mensajero}</td>
        <td>${r.direccion}</td>
        <td>${r.cliente}</td>
        <td><span class="badge badge-blue">${r.cantPaquetes} paq.</span></td>
        <td>
          <div class="guias-list">
            ${r.guias.map(g => `<span class="guia-chip">${g}</span>`).join('')}
          </div>
        </td>
        <td>${fmt.date(r.fechaAsignacion)}</td>
        <td>${r.fechaRecoleccion ? fmt.date(r.fechaRecoleccion) : '<span class="badge badge-yellow">Pendiente</span>'}</td>
      </tr>
    `).join('');
  }

  // ── RENDER ENTREGAS ───────────────────────────────────────────────────────

  function renderEntregas() {
    const f = state.filters.entregas;
    const list = DATA.entregas.filter(r => {
      if (f.nombre && !r.mensajero.toLowerCase().includes(f.nombre.toLowerCase())) return false;
      return true;
    });

    const tbody = document.getElementById('tbodyEntregas');
    const count = document.getElementById('countEntregas');
    count.textContent = `${list.length} registros`;

    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">📬</div><p>Sin entregas con los filtros aplicados</p></div></td></tr>`;
      return;
    }

    tbody.innerHTML = list.map(r => `
      <tr>
        <td>${r.mensajero}</td>
        <td class="cell-mono">${r.numGuia}</td>
        <td>${r.destinatario}</td>
        <td>${fmt.currency(r.recaudo)}</td>
        <td>${r.recaudado >= r.recaudo && r.recaudo > 0 ? `<span class="badge badge-green">${fmt.currency(r.recaudado)}</span>` : fmt.currency(r.recaudado)}</td>
        <td>${r.observaciones || '—'}</td>
        <td>${estadoBadge(r.estado)}</td>
        <td>
          <div class="actions-cell">
            <button class="action-btn view"   title="Ver detalle"   onclick="FacturacionAdmin.openModal('ver','entrega',${r.id})">👁</button>
            <button class="action-btn upload" title="Comprobante"   onclick="FacturacionAdmin.openModal('comprobante','entrega',${r.id})">📎</button>
            <button class="action-btn obs"    title="Observaciones" onclick="FacturacionAdmin.openModal('obs','entrega',${r.id})">📝</button>
            <button class="action-btn pay"    title="Pago entrega"  onclick="FacturacionAdmin.openModal('pago','entrega',${r.id})">💰</button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  // ── MODALES ───────────────────────────────────────────────────────────────

  function openModal(type, subtype, id) {
    state.modal = { type, subtype, id };
    const overlay = document.getElementById('modalOverlay');
    const head    = document.getElementById('modalHead');
    const body    = document.getElementById('modalBody');
    const foot    = document.getElementById('modalFoot');

    overlay.classList.add('open');
    foot.innerHTML = '';

    const record = subtype === 'cliente'
      ? DATA.clientes.find(r => r.id === id)
      : subtype === 'entrega'
        ? DATA.entregas.find(r => r.id === id)
        : null;

    if (type === 'ver') {
      renderModalVer(head, body, foot, subtype, record);
    } else if (type === 'comprobante') {
      renderModalComprobante(head, body, foot, subtype, record);
    } else if (type === 'obs') {
      renderModalObs(head, body, foot, subtype, record);
    } else if (type === 'pago') {
      renderModalPago(head, body, foot, record);
    }
  }

  function renderModalVer(head, body, foot, subtype, r) {
    head.querySelector('h3').innerHTML = `👁 Detalle — <span style="font-family:monospace;color:var(--accent)">${r.numGuia}</span>`;

    if (subtype === 'cliente') {
      body.innerHTML = `
        <div class="detail-grid">
          <div class="detail-item"><label>Cliente</label><div class="value">${r.cliente}</div></div>
          <div class="detail-item"><label>Nº Guía</label><div class="value cell-mono">${r.numGuia}</div></div>
          <div class="detail-item"><label>Destinatario</label><div class="value">${r.destinatario}</div></div>
          <div class="detail-item"><label>Estado</label><div class="value">${estadoBadge(r.estado)}</div></div>
          <div class="detail-item"><label>Recaudo</label><div class="value">${fmt.currency(r.recaudo)}</div></div>
          <div class="detail-item"><label>Recaudado</label><div class="value">${fmt.currency(r.recaudado)}</div></div>
          <div class="detail-item"><label>Costo envío</label><div class="value">${fmt.currency(r.costoEnvio)}</div></div>
          <div class="detail-item"><label>En recaudo</label><div class="value">${r.enRecaudo ? 'Sí' : 'No'}</div></div>
          <div class="detail-item"><label>Fecha envío</label><div class="value">${fmt.date(r.fechaEnvio)}</div></div>
          <div class="detail-item"><label>Fecha entrega</label><div class="value">${fmt.date(r.fechaEntrega)}</div></div>
          <div class="detail-item full"><label>Observaciones</label><div class="value">${r.observaciones || '—'}</div></div>
          ${r.comprobantes.length ? `<div class="detail-item full"><label>Comprobantes</label><div class="image-previews">${r.comprobantes.map(src => `<div class="image-preview"><img src="${src}"></div>`).join('')}</div></div>` : ''}
        </div>`;
    } else {
      body.innerHTML = `
        <div class="detail-grid">
          <div class="detail-item"><label>Mensajero</label><div class="value">${r.mensajero}</div></div>
          <div class="detail-item"><label>Nº Guía</label><div class="value cell-mono">${r.numGuia}</div></div>
          <div class="detail-item"><label>Destinatario</label><div class="value">${r.destinatario}</div></div>
          <div class="detail-item"><label>Estado</label><div class="value">${estadoBadge(r.estado)}</div></div>
          <div class="detail-item"><label>Recaudo</label><div class="value">${fmt.currency(r.recaudo)}</div></div>
          <div class="detail-item"><label>Recaudado</label><div class="value">${fmt.currency(r.recaudado)}</div></div>
          <div class="detail-item"><label>Pago entrega</label><div class="value">${r.pagoEntrega ? fmt.currency(r.pagoEntrega) : '—'}</div></div>
          <div class="detail-item full"><label>Observaciones</label><div class="value">${r.observaciones || '—'}</div></div>
        </div>`;
    }

    foot.innerHTML = `<button class="btn btn-ghost" onclick="FacturacionAdmin.closeModal()">Cerrar</button>`;
  }

  function renderModalComprobante(head, body, foot, subtype, r) {
    head.querySelector('h3').innerHTML = `📎 Comprobante de pago — <span style="font-family:monospace;color:var(--accent)">${r.numGuia}</span>`;
    body.innerHTML = `
      <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px">Sube las imágenes del comprobante de pago para esta guía.</p>
      <div class="upload-zone" id="uploadZone">
        <input type="file" accept="image/*" multiple id="fileInput" onchange="FacturacionAdmin.handleFiles(event,'${subtype}',${r.id})">
        <div class="upload-icon">🖼</div>
        <div class="upload-text">Arrastra imágenes aquí o <strong>haz clic para seleccionar</strong></div>
        <div style="font-size:11px;color:var(--text-dim);margin-top:6px">PNG, JPG, WEBP — máx. 5 MB c/u</div>
      </div>
      <div class="image-previews" id="uploadPreviews">
        ${r.comprobantes.map((src, i) => `
          <div class="image-preview" id="prev-${i}">
            <img src="${src}">
            <button class="remove-img" onclick="FacturacionAdmin.removeImg('${subtype}',${r.id},${i})">✕</button>
          </div>`).join('')}
      </div>`;

    // Drag & drop
    setTimeout(() => {
      const zone = document.getElementById('uploadZone');
      zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragging'); });
      zone.addEventListener('dragleave', () => zone.classList.remove('dragging'));
      zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragging');
        handleFileList(e.dataTransfer.files, subtype, r.id);
      });
    }, 50);

    foot.innerHTML = `
      <button class="btn btn-ghost" onclick="FacturacionAdmin.closeModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="FacturacionAdmin.saveComprobante()">💾 Guardar</button>`;
  }

  function renderModalObs(head, body, foot, subtype, r) {
    head.querySelector('h3').innerHTML = `📝 Observaciones — <span style="font-family:monospace;color:var(--accent)">${r.numGuia}</span>`;
    body.innerHTML = `
      <div class="form-group">
        <label>Observaciones</label>
        <textarea id="obsInput" placeholder="Ingresa las observaciones del envío...">${r.observaciones}</textarea>
      </div>`;

    foot.innerHTML = `
      <button class="btn btn-ghost" onclick="FacturacionAdmin.closeModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="FacturacionAdmin.saveObs('${subtype}',${r.id})">💾 Guardar</button>`;
  }

  function renderModalPago(head, body, foot, r) {
    head.querySelector('h3').innerHTML = `💰 Pago de entrega — <span style="font-family:monospace;color:var(--accent)">${r.numGuia}</span>`;
    body.innerHTML = `
      <div class="form-group">
        <label>Valor del pago de entrega</label>
        <input type="number" id="pagoInput" placeholder="0" min="0" step="100" value="${r.pagoEntrega || ''}">
      </div>
      <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
          <span style="color:var(--text-muted)">Recaudo pendiente</span>
          <strong style="color:var(--yellow)">${fmt.currency(r.recaudo - r.recaudado)}</strong>
        </div>
      </div>`;

    foot.innerHTML = `
      <button class="btn btn-ghost" onclick="FacturacionAdmin.closeModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="FacturacionAdmin.savePago(${r.id})">💾 Registrar pago</button>`;
  }

  function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    state.modal = { type: null, subtype: null, id: null };
  }

  // ── ACCIONES DE MODAL ────────────────────────────────────────────────────

  function handleFiles(event, subtype, id) {
    handleFileList(event.target.files, subtype, id);
  }

  function handleFileList(files, subtype, id) {
    const ds = subtype === 'cliente' ? DATA.clientes : DATA.entregas;
    const record = ds.find(r => r.id === id);
    const previews = document.getElementById('uploadPreviews');

    Array.from(files).forEach(file => {
      if (!file.type.startsWith('image/')) return;
      const reader = new FileReader();
      reader.onload = e => {
        record.comprobantes.push(e.target.result);
        const idx = record.comprobantes.length - 1;
        const div = document.createElement('div');
        div.className = 'image-preview';
        div.id = `prev-${idx}`;
        div.innerHTML = `<img src="${e.target.result}"><button class="remove-img" onclick="FacturacionAdmin.removeImg('${subtype}',${id},${idx})">✕</button>`;
        previews.appendChild(div);
      };
      reader.readAsDataURL(file);
    });
  }

  function removeImg(subtype, id, idx) {
    const ds = subtype === 'cliente' ? DATA.clientes : DATA.entregas;
    const record = ds.find(r => r.id === id);
    record.comprobantes.splice(idx, 1);
    const el = document.getElementById(`prev-${idx}`);
    if (el) el.remove();
    // re-index buttons
    document.querySelectorAll('.image-preview').forEach((p, i) => {
      p.id = `prev-${i}`;
      const btn = p.querySelector('.remove-img');
      if (btn) btn.setAttribute('onclick', `FacturacionAdmin.removeImg('${subtype}',${id},${i})`);
    });
  }

  function saveComprobante() {
    closeModal();
    showToast('Comprobante guardado correctamente');
    renderAll();
  }

  function saveObs(subtype, id) {
    const val = document.getElementById('obsInput').value.trim();
    const ds = subtype === 'cliente' ? DATA.clientes : DATA.entregas;
    const record = ds.find(r => r.id === id);
    record.observaciones = val;
    closeModal();
    showToast('Observaciones actualizadas');
    renderAll();
  }

  function savePago(id) {
    const val = parseFloat(document.getElementById('pagoInput').value) || 0;
    const record = DATA.entregas.find(r => r.id === id);
    record.pagoEntrega = val;
    closeModal();
    showToast('Pago de entrega registrado correctamente');
    renderEntregas();
  }

  // ── FILTROS ───────────────────────────────────────────────────────────────

  function applyFilters(section) {
    if (section === 'clientes') {
      state.filters.clientes.nombre = document.getElementById('fClienteNombre').value;
      state.filters.clientes.desde  = document.getElementById('fClienteDesde').value;
      state.filters.clientes.hasta  = document.getElementById('fClienteHasta').value;
      renderClientes();
    } else if (section === 'recolecciones') {
      state.filters.recolecciones.nombre = document.getElementById('fRecolNombre').value;
      state.filters.recolecciones.desde  = document.getElementById('fRecolDesde').value;
      state.filters.recolecciones.hasta  = document.getElementById('fRecolHasta').value;
      renderRecolecciones();
    } else if (section === 'entregas') {
      state.filters.entregas.nombre = document.getElementById('fEntregaNombre').value;
      state.filters.entregas.desde  = document.getElementById('fEntregaDesde').value;
      state.filters.entregas.hasta  = document.getElementById('fEntregaHasta').value;
      renderEntregas();
    }
  }

  function clearFilters(section) {
    if (section === 'clientes') {
      ['fClienteNombre','fClienteDesde','fClienteHasta'].forEach(id => document.getElementById(id).value = '');
      state.filters.clientes = { nombre: '', desde: '', hasta: '' };
      renderClientes();
    } else if (section === 'recolecciones') {
      ['fRecolNombre','fRecolDesde','fRecolHasta'].forEach(id => document.getElementById(id).value = '');
      state.filters.recolecciones = { nombre: '', desde: '', hasta: '' };
      renderRecolecciones();
    } else if (section === 'entregas') {
      ['fEntregaNombre','fEntregaDesde','fEntregaHasta'].forEach(id => document.getElementById(id).value = '');
      state.filters.entregas = { nombre: '', desde: '', hasta: '' };
      renderEntregas();
    }
  }

  // ── TABS ──────────────────────────────────────────────────────────────────

  function switchRole(role) {
    state.activeRole = role;
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`.role-tab[data-role="${role}"]`).classList.add('active');
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.getElementById(`panel-${role}`).classList.add('active');
  }

  function switchSub(sub) {
    state.activeSub = sub;
    document.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`.sub-tab[data-sub="${sub}"]`).classList.add('active');
    document.querySelectorAll('.sub-panel').forEach(p => p.classList.remove('active'));
    document.getElementById(`sub-${sub}`).classList.add('active');
  }

  // ── INIT ──────────────────────────────────────────────────────────────────

  function renderAll() {
    renderClientes();
    renderRecolecciones();
    renderEntregas();
  }

  function init() {
    renderAll();

    // Cerrar modal al click en overlay
    document.getElementById('modalOverlay').addEventListener('click', e => {
      if (e.target === e.currentTarget) closeModal();
    });

    // Actualizar fecha en header
    document.getElementById('headerDate').textContent =
      new Date().toLocaleDateString('es-CO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
  }

  // API pública
  return { init, switchRole, switchSub, applyFilters, clearFilters, openModal, closeModal, handleFiles, removeImg, saveComprobante, saveObs, savePago, handleFileList };
})();

document.addEventListener('DOMContentLoaded', FacturacionAdmin.init);