<?php
/**
 * facturacionAdmin.php
 * Módulo de facturación administrativa — Clientes y Mensajeros
 * Requiere sesión activa con rol admin
 */

// session_start();
// if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
//     header('Location: login.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facturación Admin</title>
  <link rel="icon" href="../../public/img/Logo_Blanco_Trasparente_Circulo.png" type="image/png">
  <link rel="stylesheet" href="../../public/css/facturacionAdmin.css">
  <link rel="stylesheet" href="../../public/css/responsive.css">
</head>
<body>

<!-- ══ HEADER ══════════════════════════════════════════════════════════════ -->
<header class="app-header">
  <div class="logo">
    <span>📦</span>
    Facturación Admin
  </div>
  <div class="header-meta" id="headerDate"></div>
</header>

<!-- ══ MAIN ════════════════════════════════════════════════════════════════ -->
<main class="main-container">

  <!-- TABS DE ROL -->
  <div class="role-tabs">
    <button class="role-tab active" data-role="clientes" onclick="FacturacionAdmin.switchRole('clientes')">
      <span class="tab-icon">🏪</span>
      Clientes
      <span class="tab-badge" id="badgeClientes">—</span>
    </button>
    <button class="role-tab" data-role="mensajeros" onclick="FacturacionAdmin.switchRole('mensajeros')">
      <span class="tab-icon">🛵</span>
      Mensajeros
      <span class="tab-badge" id="badgeMensajeros">—</span>
    </button>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════
       PANEL CLIENTES
  ══════════════════════════════════════════════════════════════════════════ -->
  <div class="panel active" id="panel-clientes">

    <!-- Filtros clientes -->
    <div class="filters-bar">
      <div class="filter-group">
        <label>Buscar cliente / destinatario</label>
        <input type="text" id="fClienteNombre" placeholder="Nombre…" oninput="FacturacionAdmin.applyFilters('clientes')">
      </div>
      <div class="filter-group">
        <label>Desde</label>
        <input type="date" id="fClienteDesde" onchange="FacturacionAdmin.applyFilters('clientes')">
      </div>
      <div class="filter-group">
        <label>Hasta</label>
        <input type="date" id="fClienteHasta" onchange="FacturacionAdmin.applyFilters('clientes')">
      </div>
      <div class="filter-actions">
        <button class="btn btn-ghost" onclick="FacturacionAdmin.clearFilters('clientes')">↺ Limpiar</button>
      </div>
    </div>

    <!-- Tabla clientes -->
    <div class="table-wrapper">
      <div class="table-header-bar">
        <div class="table-title">
          📋 Envíos de clientes
          <span class="table-count" id="countClientes">—</span>
        </div>
      </div>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Nº Guía</th>
              <th>Destinatario</th>
              <th>Recaudo</th>
              <th>Recaudado</th>
              <th>Costo envío</th>
              <th>En recaudo</th>
              <th>Fecha envío</th>
              <th>Fecha entrega</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyClientes">
            <tr><td colspan="10"><div class="empty-state"><div class="empty-icon">⏳</div><p>Cargando…</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /panel-clientes -->


  <!-- ══════════════════════════════════════════════════════════════════════
       PANEL MENSAJEROS
  ══════════════════════════════════════════════════════════════════════════ -->
  <div class="panel" id="panel-mensajeros">

    <!-- Sub-tabs -->
    <div class="sub-tabs">
      <button class="sub-tab active" data-sub="recolecciones" onclick="FacturacionAdmin.switchSub('recolecciones')">
        📥 Recolecciones
      </button>
      <button class="sub-tab" data-sub="entregas" onclick="FacturacionAdmin.switchSub('entregas')">
        📤 Entregas
      </button>
    </div>

    <!-- ── SUB-PANEL RECOLECCIONES ── -->
    <div class="sub-panel active" id="sub-recolecciones">

      <div class="filters-bar">
        <div class="filter-group">
          <label>Buscar mensajero</label>
          <input type="text" id="fRecolNombre" placeholder="Nombre del mensajero…" oninput="FacturacionAdmin.applyFilters('recolecciones')">
        </div>
        <div class="filter-group">
          <label>Desde</label>
          <input type="date" id="fRecolDesde" onchange="FacturacionAdmin.applyFilters('recolecciones')">
        </div>
        <div class="filter-group">
          <label>Hasta</label>
          <input type="date" id="fRecolHasta" onchange="FacturacionAdmin.applyFilters('recolecciones')">
        </div>
        <div class="filter-actions">
          <button class="btn btn-ghost" onclick="FacturacionAdmin.clearFilters('recolecciones')">↺ Limpiar</button>
        </div>
      </div>

      <div class="table-wrapper">
        <div class="table-header-bar">
          <div class="table-title">
            📥 Recolecciones
            <span class="table-count" id="countRecolecciones">—</span>
          </div>
        </div>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Mensajero</th>
                <th>Dirección de recogida</th>
                <th>Cliente</th>
                <th>Paquetes</th>
                <th>Números de guía</th>
                <th>Fecha asignación</th>
                <th>Fecha recolección</th>
              </tr>
            </thead>
            <tbody id="tbodyRecolecciones">
              <tr><td colspan="7"><div class="empty-state"><div class="empty-icon">⏳</div><p>Cargando…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /sub-recolecciones -->

    <!-- ── SUB-PANEL ENTREGAS ── -->
    <div class="sub-panel" id="sub-entregas">

      <div class="filters-bar">
        <div class="filter-group">
          <label>Buscar mensajero</label>
          <input type="text" id="fEntregaNombre" placeholder="Nombre del mensajero…" oninput="FacturacionAdmin.applyFilters('entregas')">
        </div>
        <div class="filter-group">
          <label>Desde</label>
          <input type="date" id="fEntregaDesde" onchange="FacturacionAdmin.applyFilters('entregas')">
        </div>
        <div class="filter-group">
          <label>Hasta</label>
          <input type="date" id="fEntregaHasta" onchange="FacturacionAdmin.applyFilters('entregas')">
        </div>
        <div class="filter-actions">
          <button class="btn btn-ghost" onclick="FacturacionAdmin.clearFilters('entregas')">↺ Limpiar</button>
        </div>
      </div>

      <div class="table-wrapper">
        <div class="table-header-bar">
          <div class="table-title">
            📤 Entregas
            <span class="table-count" id="countEntregas">—</span>
          </div>
        </div>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Mensajero</th>
                <th>Nº Guía</th>
                <th>Destinatario</th>
                <th>Recaudo</th>
                <th>Recaudado</th>
                <th>Observaciones</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tbodyEntregas">
              <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">⏳</div><p>Cargando…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /sub-entregas -->

  </div><!-- /panel-mensajeros -->

</main>


<!-- ══ MODAL UNIVERSAL ═══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-head" id="modalHead">
      <h3 id="modalTitle">Detalle</h3>
      <button class="modal-close" onclick="FacturacionAdmin.closeModal()">✕</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-foot" id="modalFoot"></div>
  </div>
</div>

<!-- ══ TOASTS ═════════════════════════════════════════════════════════════ -->
<div class="toast-container" id="toastContainer"></div>

<!-- ══ SCRIPTS ════════════════════════════════════════════════════════════ -->
<script src="../../public/js/facturacionAdmin.js"></script>

<!-- Activar sub-panels correctamente en CSS -->
<style>
  .sub-panel { display: none; animation: fadeIn 0.25s ease; }
  .sub-panel.active { display: block; }
</style>

</body>
</html>
