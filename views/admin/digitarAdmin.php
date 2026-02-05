<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digitar Envío - Sistema de Mensajería</title>
    <link rel="stylesheet" href="../../public/css/clienteSidebar.css">
    <link rel="stylesheet" href="../../public/css/clienteNavbar.css">
    <link rel="stylesheet" href="../../public/css/digitarAdmin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <?php include '../layouts/adminNavbar.php'; ?>
    <?php include '../layouts/adminSidebar.php'; ?>

    <div class="container" style="margin-left: 250px; margin-top: 60px;">
        <!-- Header -->
        <header class="page-header">
            <div>
                <h1>📝 Digitar Nuevo Envío</h1>
                <p>Registro manual de paquetes y envíos</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" id="btnHistorialEnvios">
                    📋 Historial de Hoy
                </button>
            </div>
        </header>

        <!-- Resumen de Envíos del Día -->
        <div class="stats-summary">
            <div class="stat-item">
                <span class="stat-label">Envíos Hoy</span>
                <span class="stat-value" id="enviosHoy">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Facturado</span>
                <span class="stat-value" id="totalFacturado">$0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Último Cliente</span>
                <span class="stat-value" id="ultimoCliente">-</span>
            </div>
        </div>

        <!-- Formulario Principal -->
        <div class="form-container">
            <form id="formEnvio">
                <!-- Sección: Cliente -->
                <div class="form-section">
                    <div class="section-header">
                        <h2>🏢 Cliente / Remitente</h2>
                        <button type="button" class="btn btn-sm btn-success" id="btnNuevoCliente">
                            + Nuevo Cliente
                        </button>
                    </div>

                    <div class="search-client-container">
                        <div class="form-group">
                            <label>Buscar Cliente Existente</label>
                            <input type="text" id="searchCliente" placeholder="Buscar por nombre, NIT o teléfono..." class="form-control">
                            <div id="resultadosClientes" class="search-results"></div>
                        </div>
                    </div>

                    <div id="datosCliente" class="datos-cliente hidden">
                        <div class="cliente-info-card">
                            <div class="cliente-header">
                                <h3 id="clienteNombre">-</h3>
                                <button type="button" class="btn btn-sm btn-secondary" id="btnCambiarCliente">
                                    🔄 Cambiar Cliente
                                </button>
                            </div>
                            <div class="cliente-details">
                                <p><strong>NIT/CC:</strong> <span id="clienteNit">-</span></p>
                                <p><strong>Teléfono:</strong> <span id="clienteTelefono">-</span></p>
                                <p><strong>Dirección:</strong> <span id="clienteDireccion">-</span></p>
                                <p><strong>Email:</strong> <span id="clienteEmail">-</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid" id="remitente-section">
                        <div class="form-group">
                            <label>Nombre del Remitente *</label>
                            <input type="text" id="remitenteNombre" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono del Remitente *</label>
                            <input type="tel" id="remitenteTelefono" class="form-control" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Dirección de Recolección *</label>
                            <textarea id="remitenteDireccion" class="form-control" rows="2" required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sección: Destinatario -->
                <div class="form-section">
                    <div class="section-header">
                        <h2>📍 Destinatario</h2>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre Completo *</label>
                            <input type="text" id="destinatarioNombre" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono *</label>
                            <input type="tel" id="destinatarioTelefono" class="form-control" required>
                        </div>
                        <div class="form-group full-width">
                            <label>Dirección de Entrega *</label>
                            <textarea id="destinatarioDireccion" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Ciudad *</label>
                            <input type="text" id="destinatarioCiudad" class="form-control" value="Bogotá" required>
                        </div>
                        <div class="form-group">
                            <label>Zona *</label>
                            <select id="destinatarioZona" class="form-control" required>
                                <option value="">Seleccione zona</option>
                                <option value="norte">Norte</option>
                                <option value="sur">Sur</option>
                                <option value="este">Este</option>
                                <option value="oeste">Oeste</option>
                                <option value="centro">Centro</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Referencia / Indicaciones</label>
                            <textarea id="destinatarioReferencia" class="form-control" rows="2" placeholder="Ej: Casa blanca, segundo piso"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sección: Descripción del Paquete -->
                <div class="form-section">
                    <div class="section-header">
                        <h2>📦 Descripción del Paquete</h2>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Descripción Detallada del Contenido *</label>
                            <textarea id="paqueteDescripcion" class="form-control" rows="3" placeholder="Ej: 2 cajas con documentos legales" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Paquete *</label>
                            <select id="paqueteTipo" class="form-control" required>
                                <option value="">Seleccione tipo</option>
                                <option value="documento">Documento</option>
                                <option value="sobre">Sobre</option>
                                <option value="paquete">Paquete</option>
                                <option value="caja">Caja</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cantidad de Unidades *</label>
                            <input type="number" id="paqueteCantidad" class="form-control" min="1" value="1" required>
                        </div>

                        <div class="form-group">
                            <label>Peso Total (kg) *</label>
                            <input type="number" id="paquetePeso" class="form-control" step="0.1" min="0.1" required>
                        </div>

                        <div class="form-group">
                            <label>Alto (cm)</label>
                            <input type="number" id="paqueteAlto" class="form-control" step="0.1" min="0">
                        </div>

                        <div class="form-group">
                            <label>Ancho (cm)</label>
                            <input type="number" id="paqueteAncho" class="form-control" step="0.1" min="0">
                        </div>

                        <div class="form-group">
                            <label>Largo (cm)</label>
                            <input type="number" id="paqueteLargo" class="form-control" step="0.1" min="0">
                        </div>

                        <div class="form-group">
                            <label>Valor Declarado *</label>
                            <input type="number" id="paqueteValor" class="form-control" min="0" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Instrucciones Especiales</label>
                            <textarea id="paqueteInstrucciones" class="form-control" rows="2" placeholder="Ej: Frágil, Manejar con cuidado"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sección: Servicio y Pago -->
                <div class="form-section">
                    <div class="section-header">
                        <h2>💰 Servicio y Facturación</h2>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tipo de Servicio *</label>
                            <select id="tipoServicio" class="form-control" required>
                                <option value="">Seleccione servicio</option>
                                <option value="normal">Normal (24-48 horas)</option>
                                <option value="urgente">Urgente (Mismo día)</option>
                                <option value="express">Express (2-4 horas)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Forma de Pago *</label>
                            <select id="formaPago" class="form-control" required>
                                <option value="">Seleccione forma de pago</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="credito">Crédito (Cuenta Corriente)</option>
                                <option value="contraentrega">Contraentrega</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>¿Quién Paga?</label>
                            <select id="quienPaga" class="form-control">
                                <option value="remitente">Remitente</option>
                                <option value="destinatario">Destinatario</option>
                            </select>
                        </div>
                    </div>

                    <!-- Cálculo de Costo -->
                    <div class="costo-section">
                        <div class="costo-breakdown">
                            <div class="costo-item">
                                <span>Costo Base:</span>
                                <span id="costoBase">$0</span>
                            </div>
                            <div class="costo-item">
                                <span>Recargo por Servicio:</span>
                                <span id="costoRecargo">$0</span>
                            </div>
                            <div class="costo-item">
                                <span>Seguro (2%):</span>
                                <span id="costoSeguro">$0</span>
                            </div>
                            <div class="costo-item">
                                <label>
                                    <input type="checkbox" id="aplicarDescuento">
                                    Aplicar Descuento:
                                </label>
                                <input type="number" id="descuentoPorcentaje" class="form-control-sm" min="0" max="100" value="0" disabled>
                                <span>%</span>
                                <span id="costoDescuento">$0</span>
                            </div>
                            <div class="costo-total">
                                <span>TOTAL A PAGAR:</span>
                                <span id="costoTotal">$0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="btnCancelar">
                        ❌ Cancelar
                    </button>
                    <button type="button" class="btn btn-info" id="btnPrevisualizarEtiqueta">
                        👁️ Previsualizar Etiqueta
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Guardar y Generar Guía
                    </button>
                    <button type="button" class="btn btn-success" id="btnGuardarYNuevo" style="display: none;">
                        ➕ Guardar y Agregar Otro
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal Nuevo Cliente -->
        <div class="modal" id="modalNuevoCliente">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Nuevo Cliente</h2>
                    <button class="btn-close" id="btnCerrarNuevoCliente">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoCliente">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Tipo de Documento *</label>
                                <select id="nuevoClienteTipoDoc" class="form-control" required>
                                    <option value="NIT">NIT</option>
                                    <option value="CC">Cédula</option>
                                    <option value="CE">Cédula Extranjería</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Número de Documento *</label>
                                <input type="text" id="nuevoClienteDoc" class="form-control" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Nombre / Razón Social *</label>
                                <input type="text" id="nuevoClienteNombre" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Teléfono *</label>
                                <input type="tel" id="nuevoClienteTelefono" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" id="nuevoClienteEmail" class="form-control">
                            </div>
                            <div class="form-group full-width">
                                <label>Dirección *</label>
                                <textarea id="nuevoClienteDireccion" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Ciudad *</label>
                                <input type="text" id="nuevoClienteCiudad" class="form-control" value="Bogotá" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="btnCancelarNuevoCliente">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Crear Cliente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Previsualizar Etiqueta -->
        <div class="modal" id="modalEtiqueta">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Etiqueta de Envío</h2>
                    <button class="btn-close" id="btnCerrarEtiqueta">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="etiquetaPreview" class="etiqueta-container">
                        <!-- La etiqueta se genera dinámicamente -->
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="btnCerrarEtiquetaBtn">Cerrar</button>
                        <button type="button" class="btn btn-primary" id="btnImprimirEtiqueta">🖨️ Imprimir Etiqueta</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="../../public/js/digitarAdmin.js"></script>
</body>
</html>