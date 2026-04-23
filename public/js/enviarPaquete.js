document.addEventListener('DOMContentLoaded', function() {
    // --- ELEMENTOS DEL DOM ---
    const form = document.getElementById('envioForm');
    const steps = document.querySelectorAll('.form-step');
    const stepIndicators = document.querySelectorAll('.step');
    const btnNext = document.getElementById('btnNext');
    const btnPrevious = document.getElementById('btnPrevious');
    const btnSubmit = document.getElementById('btnSubmit');

    // Evitar que el formulario se envíe al presionar Enter
    if (form) {
        form.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
    }

    // Botones y campos específicos
    const autoFillBtn = document.getElementById('autoFillRemitente');
    const tieneRecaudoCheckbox = document.getElementById('tiene_recaudo');
    const btnUploadExcel = document.getElementById('btnUploadExcel');
    const excelUploadInput = document.getElementById('excelUpload');
    const btnDownloadTemplate = document.getElementById('btnDownloadTemplate');
    const valorRecaudoInput = document.getElementById('valor_recaudo');
    const valorRecaudoHidden = document.getElementById('valor_recaudo_hidden');
    const recaudoField = document.querySelector('.recaudo-field');
    const calcularCostoBtn = document.getElementById('calcularCosto');
    const costoTotalHiddenInput = document.getElementById('costoTotalHidden');
    const numeroGuiaHiddenInput = document.getElementById('numeroGuiaHidden');
    const btnDownloadPDF = document.getElementById('btnDownloadPDF');
    const formAction = form?.getAttribute('action') || '../../controller/enviarPaqueteController.php';
    const qrcodeContainer = document.getElementById('qrcode');
    let qrCodeStylingInstance = null; // Para la instancia del nuevo QR
    let currentPreviewRotuloData = null;
    let baseRecaudo = 0; // Variable para almacenar el valor base del recaudo (sin envío)

    const getBaseRecaudoValue = () => {
        const fromInput = valorRecaudoInput?.value ?? '';
        const raw = String(fromInput);
        const numeric = parseFloat(raw.replace(/[^\d]/g, '')) || 0;
        return numeric;
    };

    let currentStep = 1;
    const parseSiNo = (value) => {
        const v = String(value || '').trim().toLowerCase();
        return ['si', 'sí', 'true', '1', 'x', 'yes'].includes(v);
    };
    const normalizeDimensiones = (value) => {
        const vRaw = String(value || '').trim();
        const v = vRaw.toLowerCase();
        const allowed = new Set(['0', '2000', '4000', '7000', '10000', '12000', 'notificar']);
        if (allowed.has(v)) return v;
        const numericMap = {
            '1': '0',
            '2': '2000',
            '3': '4000',
            '4': '7000',
            '5': '10000',
            '6': '12000',
            '7': 'notificar'
        };
        if (numericMap[v]) return numericMap[v];
        const map = {
            'menor o igual a 20 x 20 cm': '0',
            'menor o igual a 20x20 cm': '0',
            'entre 21x21 y 30x30 cm': '2000',
            'entre 31x31 y 35x35 cm': '4000',
            'entre 36x36 y 40x40 cm': '7000',
            'entre 41x41 y 45x45 cm': '10000',
            'entre 46x46 y 49x49 cm': '12000',
            'igual o mayor a 50 x 50 cm (notificar)': 'notificar',
            'igual o mayor a 50x50 cm (notificar)': 'notificar'
        };
        return map[v] || '';
    };

    const normalizeHeaderRow = (row = []) => row.map(cell =>
        cell ? cell.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim() : ''
    );

    const findTemplateHeaderRowIndex = (rows = []) => rows.findIndex(row => {
        const normalized = normalizeHeaderRow(row);
        return normalized.some(cell => cell.includes('nombre completo')) &&
            normalized.some(cell => cell.includes('direccion de destino') || cell.includes('direccion')) &&
            normalized.some(cell => cell.includes('dimensiones del paquete'));
    });

    const generarSufijoAleatorio = (length = 5) => {
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let result = '';

        for (let i = 0; i < length; i += 1) {
            result += letters.charAt(Math.floor(Math.random() * letters.length));
        }

        return result;
    };

    const generarNumeroGuia = () => {
        const date = new Date();
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const random = generarSufijoAleatorio(5);
        return `EBM-${year}${month}${day}-${random}`;
    };

    const descripcionDimension = (value) => {
        const normalized = normalizeDimensiones(value);
        const labels = {
            '0': '1. Menor o igual a 20 x 20 cm',
            '2000': '2. Entre 21x21 y 30x30 cm',
            '4000': '3. Entre 31x31 y 35x35 cm',
            '7000': '4. Entre 36x36 y 40x40 cm',
            '10000': '5. Entre 41x41 y 45x45 cm',
            '12000': '6. Entre 46x46 y 49x49 cm',
            'notificar': '7. Igual o mayor a 50 x 50 cm'
        };
        return labels[normalized] || 'Sin definir';
    };

    // --- NAVEGACIÓN ENTRE PASOS ---
    btnNext.addEventListener('click', () => {
        if (validateStep(currentStep)) {
            if (currentStep < 4) {
                goToStep(currentStep + 1);
            }
        }
    });

    btnPrevious.addEventListener('click', () => {
        if (currentStep > 1) {
            goToStep(currentStep - 1);
        }
    });

    function goToStep(stepNumber) {
        currentStep = stepNumber;

        steps.forEach(step => {
            step.classList.toggle('active', parseInt(step.dataset.step) === currentStep);
        });

        stepIndicators.forEach(indicator => {
            const indicatorStep = parseInt(indicator.dataset.step);
            if (indicatorStep < currentStep) {
                indicator.classList.add('completed');
                indicator.classList.remove('active');
            } else if (indicatorStep === currentStep) {
                indicator.classList.add('active');
                indicator.classList.remove('completed');
            } else {
                indicator.classList.remove('active', 'completed');
            }
        });

        btnPrevious.style.display = currentStep > 1 ? 'inline-block' : 'none';
        btnNext.style.display = currentStep < 4 ? 'inline-block' : 'none';
        btnSubmit.style.display = currentStep === 4 ? 'inline-block' : 'none';

        if (currentStep === 4) {
            populateConfirmation();
        }
    }

    // --- VALIDACIÓN ---
    function validateStep(stepNumber) {
        let isValid = true;
        const currentStepFields = document.querySelector(`.form-step[data-step="${stepNumber}"]`);
        const inputs = currentStepFields.querySelectorAll('input[required], textarea[required], select[required]');

        // Validar inputs normales
        inputs.forEach(input => {
            // Ignorar campos ocultos (ej. si no hay recaudo, no validar radios ocultos)
            if (input.offsetParent === null) return;

            const formGroup = input.closest('.form-group');
            const errorSpan = formGroup.querySelector('.error-message');
            
            if (input.type === 'radio') {
                // Validación específica para radios
                const name = input.name;
                const group = currentStepFields.querySelectorAll(`input[name="${name}"]`);
                const isChecked = Array.from(group).some(r => r.checked);
                
                if (!isChecked) {
                    isValid = false;
                    formGroup.classList.add('error');
                    if (errorSpan) errorSpan.textContent = 'Debe seleccionar una opción.';
                } else {
                    formGroup.classList.remove('error');
                    if (errorSpan) errorSpan.textContent = '';
                }
            } else {
                // Validación estándar
                if (!input.value.trim()) {
                    isValid = false;
                    formGroup.classList.add('error');
                    if (errorSpan) errorSpan.textContent = 'Este campo es obligatorio.';
                } else {
                    formGroup.classList.remove('error');
                    if (errorSpan) errorSpan.textContent = '';
                }
            }
        });
        return isValid;
    }

    // --- FUNCIONALIDADES ESPECÍFICAS ---

    // Formatear campo de recaudo con puntos de mil
    if (valorRecaudoInput) {
        valorRecaudoInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, ''); // Remover no-dígitos
            if (value) {
                const numberValue = parseInt(value, 10);
                // Usar toLocaleString para formatear con puntos
                e.target.value = numberValue.toLocaleString('es-CO');
                // Actualizar input oculto con el valor limpio para la BD
                baseRecaudo = numberValue; // Guardar valor base
                if (valorRecaudoHidden) valorRecaudoHidden.value = numberValue;
                // El recargo de recaudo depende del monto, recalcular
                calcularCostoAutomatico();
                actualizarRecaudoFinal();
            } else {
                e.target.value = '';
                baseRecaudo = 0;
                if (valorRecaudoHidden) valorRecaudoHidden.value = '';
                calcularCostoAutomatico();
                actualizarRecaudoFinal();
            }
        });
    }

    // Usamos window.remitenteData para asegurar acceso global a los datos
    if (autoFillBtn) {
        autoFillBtn.addEventListener('click', () => {
            // Verificamos que existan los datos al momento de hacer clic
            const data = window.remitenteData || {};
            
            // Mapa de IDs de inputs y sus valores correspondientes
            const campos = {
                'remitente_nombre': data.nombre_completo,
                'remitente_telefono': data.telefono,
                'remitente_email': data.correo,
                'remitente_direccion': data.direccion
            };

            // Llenar campos y limpiar errores visuales
            for (const [id, valor] of Object.entries(campos)) {
                const input = document.getElementById(id);
                if (input) {
                    input.value = valor || '';
                    // Disparar evento input para simular escritura y limpiar validaciones
                    input.dispatchEvent(new Event('input'));
                    // Remover clase de error si existe
                    input.closest('.form-group')?.classList.remove('error');
                    const errorSpan = input.closest('.form-group')?.querySelector('.error-message');
                    if (errorSpan) errorSpan.textContent = '';
                }
            }
        });
    }

    // Elementos para carga masiva
    const bulkContainer = document.getElementById('bulkPreviewContainer');
    const btnCancelBulk = document.getElementById('btnCancelBulk');
    const btnProcessBulk = document.getElementById('btnProcessBulk');
    let bulkData = []; // Almacenará los datos procesados del Excel

    // --- CARGA DE EXCEL ---
    if (btnUploadExcel && excelUploadInput) {
        btnUploadExcel.addEventListener('click', () => {
            excelUploadInput.click();
        });

        excelUploadInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 }); // Array de arrays

                    if (jsonData.length < 2) {
                        alert('El archivo Excel parece estar vacío o sin datos.');
                        return;
                    }

                    // Normalizar cabeceras (quitar tildes y minúsculas) para arreglar problema de "Teléfono"
                    const normalize = (str) => str ? str.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim() : '';
                    const headerRowIndex = findTemplateHeaderRowIndex(jsonData);
                    const finalHeaderIndex = headerRowIndex >= 0 ? headerRowIndex : 0;
                    const headers = jsonData[finalHeaderIndex].map(h => normalize(h));
                    const structuredTemplate = finalHeaderIndex > 0;
                    
                    const dataRows = jsonData.slice(finalHeaderIndex + 1).filter(row =>
                        Array.isArray(row) && row.some(cell => String(cell ?? '').trim() !== '')
                    );

                    // --- MODO MASIVO ---
                    if (dataRows.length > 1) {
                        if(confirm(`Se encontraron ${dataRows.length} registros. ¿Deseas cargarlos en modo masivo?`)) {
                            initBulkMode(headers, dataRows, { structuredTemplate });
                            return;
                        }
                    }

                    // --- MODO INDIVIDUAL (Lógica existente mejorada) ---
                    const row = dataRows[0];
                    if (!row || row.length === 0) return;

                    const getValue = (keyPart) => {
                        const index = headers.findIndex(h => h.includes(keyPart));
                        return index !== -1 ? row[index] : '';
                    };
                    const getStructuredValue = (index, fallback = '') =>
                        structuredTemplate && index < row.length ? row[index] : fallback;

                    // Helper para asignar valor y disparar eventos
                    const setField = (id, val) => {
                        const input = document.getElementById(id);
                        if (input && val !== undefined) {
                            input.value = val;
                            input.dispatchEvent(new Event('input'));
                        }
                    };

                    // 0. Llenar Remitente si viene en la plantilla
                    setField('remitente_nombre', getStructuredValue(0, getValue('remitente nombre') || getValue('datos remitente nombre') || getValue('nombre remitente')));
                    setField('remitente_telefono', getStructuredValue(1, getValue('remitente telefono') || getValue('telefono remitente')));
                    setField('remitente_email', getStructuredValue(2, getValue('remitente email') || getValue('email de contacto') || getValue('correo remitente')));
                    setField('remitente_direccion', getStructuredValue(3, getValue('direccion de origen') || getValue('remitente direccion') || getValue('origen')));

                    // 1. Llenar Destinatario
                    setField('destinatario_nombre', getStructuredValue(4, getValue('nombre') || getValue('destinatario')));
                    // Agregamos 'movil' y la normalización arregla 'Teléfono'
                    setField('destinatario_telefono', getStructuredValue(5, getValue('num destinatario') || getValue('telefono') || getValue('celular') || getValue('movil')));
                    setField('destinatario_direccion', getStructuredValue(6, getValue('direccion') || getValue('destino')));
                    setField('instrucciones_entrega', getStructuredValue(7, getValue('instrucciones') || getValue('observaciones')));

                    // 2. Llenar Paquete
                    const dimensionesVal = normalizeDimensiones(getStructuredValue(10, getValue('dimensiones')));
                    if (dimensionesVal) setField('dimensiones_paquete', dimensionesVal);

                    const mismoDia = parseSiNo(getStructuredValue(11, getValue('mismo dia') || getValue('envio mismo dia')));
                    const zonaPeriferica = parseSiNo(getStructuredValue(12, getValue('zona periferica')));
                    const recogerCambios = parseSiNo(getStructuredValue(13, getValue('recoger cambios')));
                    const sumarEnvio = parseSiNo(getStructuredValue(14, getValue('sumar envio')));

                    const chkMismoDia = document.getElementById('envio_mismo_dia');
                    const chkZona = document.getElementById('zona_periferica');
                    const chkCambios = document.getElementById('recoger_cambios');
                    if (chkMismoDia) chkMismoDia.checked = mismoDia;
                    if (chkZona) chkZona.checked = zonaPeriferica;
                    if (chkCambios) chkCambios.checked = recogerCambios;

                    // Dimensiones - El usuario debe seleccionarlo manualmente ya que la lógica es compleja.
                    // Se podría implementar una lógica para mapear cm a la opción correcta si se desea.

                    // Recaudo
                    const recaudoVal = getStructuredValue(9, getValue('valor recaudo') || getValue('recaudo') || getValue('valor'));
                    if (recaudoVal && !isNaN(parseFloat(recaudoVal)) && parseFloat(recaudoVal) > 0) {
                        if (!tieneRecaudoCheckbox.checked) tieneRecaudoCheckbox.click();
                        setField('valor_recaudo', recaudoVal);
                    } else {
                        if (tieneRecaudoCheckbox.checked) tieneRecaudoCheckbox.click();
                    }

                    const radioSumar = document.querySelector('input[name="envio_destinatario"][value="si"]');
                    const radioNo = document.querySelector('input[name="envio_destinatario"][value="no"]');
                    if (sumarEnvio && radioSumar) radioSumar.checked = true;
                    if (!sumarEnvio && radioNo) radioNo.checked = true;

                    // 3. Auto-llenar Remitente solo si la plantilla no lo trajo
                    const remitenteNombreInput = document.getElementById('remitente_nombre');
                    if (autoFillBtn && remitenteNombreInput && !String(remitenteNombreInput.value || '').trim()) {
                        autoFillBtn.click();
                    }

                    calcularCostoAutomatico();

                    alert('✅ Datos cargados correctamente desde el Excel.');
                    excelUploadInput.value = ''; // Limpiar input para permitir recargar el mismo archivo

                } catch (error) {
                    console.error(error);
                    alert('Error al leer el archivo Excel.');
                }
            };
            reader.readAsArrayBuffer(file);
        });
    }

    // --- LÓGICA DE MODO MASIVO ---
    function initBulkMode(headers, rows, options = {}) {
        const structuredTemplate = options.structuredTemplate === true;
        // Ocultar formulario normal y mostrar contenedor masivo
        document.body.classList.add('bulk-mode');
        document.getElementById('envioForm').style.display = 'none';
        document.querySelector('.steps-indicator').style.display = 'none';
        if(bulkContainer) bulkContainer.style.display = 'block';

        const tbody = document.getElementById('bulkTableBody');
        tbody.innerHTML = '';
        bulkData = [];

        rows.forEach((row, index) => {
            if (!row || row.length === 0) return;

            const getValue = (keyPart) => {
                const idx = headers.findIndex(h => h.includes(keyPart));
                return idx !== -1 ? row[idx] : '';
            };
            const getStructuredValue = (index, fallback = '') =>
                structuredTemplate && index < row.length ? row[index] : fallback;

            const dimensionesVal = normalizeDimensiones(getStructuredValue(10, getValue('dimensiones')));
            const mismoDia = parseSiNo(getStructuredValue(11, getValue('mismo dia') || getValue('envio mismo dia')));
            const zonaPeriferica = parseSiNo(getStructuredValue(12, getValue('zona periferica')));
            const recogerCambios = parseSiNo(getStructuredValue(13, getValue('recoger cambios')));
            const sumarEnvio = parseSiNo(getStructuredValue(14, getValue('sumar envio')));
            const valorRecaudo = parseFloat(getStructuredValue(9, getValue('valor recaudo') || getValue('recaudo') || 0)) || 0;
            const tieneRecaudo = parseSiNo(getStructuredValue(8, getValue('tiene recaudo') || getValue('pago contra entrega'))) || valorRecaudo > 0;

            const recargoDimensiones = parseInt(dimensionesVal, 10) || 0;
            const recargoMismoDia = mismoDia ? 2000 : 0;
            const recargoZona = zonaPeriferica ? 4000 : 0;
            const recargoCambios = recogerCambios ? 5000 : 0;
            const fijoContraentrega = 3000;
            const extraRecaudo = (tieneRecaudo && valorRecaudo >= 300000)
                ? Math.floor((valorRecaudo - 300000) / 100000) * 1000
                : 0;
            const recargoRecaudo = tieneRecaudo ? (fijoContraentrega + Math.max(0, extraRecaudo)) : 0;
            const costoBase = 8000;
            const total = costoBase + recargoDimensiones + recargoMismoDia + recargoZona + recargoCambios + recargoRecaudo;
            const valorRecaudoFinal = sumarEnvio ? (valorRecaudo + total) : valorRecaudo;

            // Preparar objeto de datos
            const item = {
                id: index,
                cliente_id: document.getElementById('cliente_id')?.value || '',
                remitente_nombre: getStructuredValue(0, getValue('remitente nombre') || getValue('datos remitente nombre') || getValue('nombre remitente')) || document.getElementById('remitente_nombre').value || (window.remitenteData?.nombre_completo),
                remitente_telefono: getStructuredValue(1, getValue('remitente telefono') || getValue('telefono remitente')) || document.getElementById('remitente_telefono').value || (window.remitenteData?.telefono),
                remitente_email: getStructuredValue(2, getValue('remitente email') || getValue('email de contacto') || getValue('correo remitente')) || document.getElementById('remitente_email').value || (window.remitenteData?.correo),
                remitente_direccion: getStructuredValue(3, getValue('direccion de origen') || getValue('remitente direccion') || getValue('origen')) || document.getElementById('remitente_direccion').value || (window.remitenteData?.direccion),
                destinatario_nombre: getStructuredValue(4, getValue('nombre') || getValue('destinatario')),
                destinatario_telefono: getStructuredValue(5, getValue('num destinatario') || getValue('telefono') || getValue('celular') || getValue('movil')),
                destinatario_direccion: getStructuredValue(6, getValue('direccion') || getValue('destino')),
                instrucciones_entrega: getStructuredValue(7, getValue('instrucciones') || getValue('observaciones')),
                descripcion_contenido: getValue('descripcion contenido') || getValue('contenido') || getValue('producto') || getValue('paquete'),
                dimensiones_paquete: dimensionesVal,
                envio_mismo_dia: mismoDia ? 'on' : '',
                zona_periferica: zonaPeriferica ? 'on' : '',
                recoger_cambios: recogerCambios ? 'on' : '',
                tiene_recaudo: tieneRecaudo ? 'on' : '',
                valor_recaudo: valorRecaudoFinal || 0,
                envio_destinatario: sumarEnvio ? 'si' : 'no',
                costo_total: total,
                // Generar guía temporal
                numero_guia: generarNumeroGuia()
            };

            bulkData.push(item);

            // Renderizar fila
            const tr = document.createElement('tr');
            tr.id = `row-${index}`;
            tr.innerHTML = `
                <td><span class="bulk-chip">${item.numero_guia}</span></td>
                <td>
                    <div class="bulk-cell-stack">
                        <strong>${item.remitente_nombre || 'Sin nombre'}</strong>
                        <small>${item.remitente_telefono || 'Sin teléfono'}</small>
                        <small>${item.remitente_direccion || 'Sin dirección'}</small>
                    </div>
                </td>
                <td>
                    <div class="bulk-cell-stack">
                        <strong>${item.destinatario_nombre || 'Sin nombre'}</strong>
                        <small>${item.destinatario_telefono || 'Sin teléfono'}</small>
                        <small>${item.destinatario_direccion || 'Sin dirección'}</small>
                    </div>
                </td>
                <td>
                    <div class="bulk-cell-stack">
                        <strong>${descripcionDimension(item.dimensiones_paquete)}</strong>
                        <small>Recaudo: $${Number(item.valor_recaudo || 0).toLocaleString('es-CO')}</small>
                        <small>Mismo día: ${item.envio_mismo_dia ? 'Sí' : 'No'} | Cambios: ${item.recoger_cambios ? 'Sí' : 'No'}</small>
                    </div>
                </td>
                <td><strong>$${total.toLocaleString('es-CO')}</strong></td>
                <td class="status-pending" id="status-${index}">Pendiente</td>
                <td id="actions-${index}"></td>
            `;
            tbody.appendChild(tr);
        });
    }

    if (btnCancelBulk) {
        btnCancelBulk.addEventListener('click', () => {
            location.reload(); // Recargar para volver al estado inicial limpio
        });
    }

    if (btnProcessBulk) {
        btnProcessBulk.addEventListener('click', async () => {
            if (bulkData.length === 0) return;
            
            if (!confirm(`¿Estás seguro de procesar ${bulkData.length} envíos?`)) return;

            btnProcessBulk.disabled = true;
            btnProcessBulk.textContent = 'Procesando...';

            // Procesar uno por uno
            for (const item of bulkData) {
                const statusCell = document.getElementById(`status-${item.id}`);
                statusCell.textContent = 'Enviando...';

                try {
                    const formData = new FormData();
                    for (const key in item) {
                        formData.append(key, item[key]);
                    }
                    formData.append('ajax', '1'); // Indicar al controlador que es AJAX

                    // Enviar al controlador existente
                    const response = await fetch(formAction, {
                        method: 'POST',
                        body: formData
                    });

                    // Intentar parsear JSON
                    const result = await response.json();

                    if (result.success) {
                        statusCell.textContent = '✓ Creado';
                        statusCell.className = 'status-success';
                        if (result.guia) item.numero_guia = result.guia;
                        
                        // Agregar botón de descarga
                        const actionsCell = document.getElementById(`actions-${item.id}`);
                        actionsCell.innerHTML = `
                            <button type="button" class="btn-text" style="color: #28a745; font-size: 0.9rem;" onclick="downloadBulkPDF(${item.id}, '${item.numero_guia}')">⬇️ Rótulo</button>
                        `;
                    } else {
                        throw new Error(result.message || 'Error en servidor');
                    }
                } catch (error) {
                    console.error(error);
                    statusCell.textContent = '❌ Error';
                    statusCell.className = 'status-error';
                }
            }

            btnProcessBulk.textContent = 'Proceso Finalizado';
            alert('Proceso masivo finalizado. Revisa el estado de cada envío.');
        });
    }

    // --- FUNCIÓN PARA DESCARGAR PDF EN MODO MASIVO ---
    window.downloadBulkPDF = async function(index, numeroGuia) {
        const item = bulkData[index];
        if (!item) return;

        if (window.RotuloEcoBike) {
            try {
                await window.RotuloEcoBike.downloadPdf({
                    guia: numeroGuia,
                    tienda_nombre: window.remitenteData?.nombre_tienda || item.remitente_nombre || 'Tienda',
                    remitente_nombre: item.remitente_nombre || '',
                    destinatario_nombre: item.destinatario_nombre || '',
                    destinatario_direccion: item.destinatario_direccion || '',
                    destinatario_telefono: item.destinatario_telefono || '',
                    destinatario_observaciones: item.instrucciones_entrega || 'Sin observaciones',
                    cambios: item.recoger_cambios ? 'Si' : 'No',
                    recaudo: item.valor_recaudo > 0 ? item.valor_recaudo : 0
                }, { filePrefix: 'Guia' });
                return;
            } catch (error) {
                console.error("Error generando PDF masivo:", error);
                alert("Error al generar el PDF.");
                return;
            }
        }

        try {
            const { jsPDF } = window.jspdf;
            
            // 1. Generar QR
            const infoParaQR = `
Guía: ${numeroGuia}
Remitente: ${item.remitente_nombre}
Origen: ${item.remitente_direccion}
Destinatario: ${item.destinatario_nombre}
Destino: ${item.destinatario_direccion}
Costo Envío: $${item.costo_total.toLocaleString('es-CO')}
Recaudo: ${item.valor_recaudo > 0 ? '$' + item.valor_recaudo : 'No aplica'}
            `.trim();

            const qrCode = new QRCodeStyling({
                width: 128,
                height: 128,
                data: numeroGuia,
                dotsOptions: { color: "#000000", type: "square" },
                backgroundOptions: { color: "#ffffff" },
                imageOptions: { crossOrigin: "anonymous", margin: 4 },
                qrOptions: { errorCorrectionLevel: 'M' }
            });

            const qrBlob = await qrCode.getRawData('png');
            const qrImageDataUrl = URL.createObjectURL(qrBlob);

            // 2. Crear Template HTML Temporal
            const pdfTemplate = document.createElement('div');
            pdfTemplate.style.width = '210mm';
            pdfTemplate.style.position = 'absolute';
            pdfTemplate.style.left = '-9999px';
            pdfTemplate.style.background = 'white';
            document.body.appendChild(pdfTemplate);

            pdfTemplate.innerHTML = `
                <div style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
                    <table style="width: 100%; border-bottom: 2px solid #5cb85c; padding-bottom: 10px;">
                        <tr>
                            <td style="width: 50%;">
                                <h1 style="font-size: 24px; margin: 0; color: #5cb85c;"><img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;">EcoBikeMess</h1>
                                <p style="margin: 0; font-size: 12px;">Guía de Envío</p>
                            </td>
                            <td style="width: 50%; text-align: right;">
                                <p style="margin: 0; font-size: 12px;">Número de Guía:</p>
                                <h2 style="margin: 0; font-size: 18px;">${numeroGuia}</h2>
                            </td>
                        </tr>
                    </table>
                    <table style="width: 100%; margin-top: 20px; font-size: 11px;">
                        <tr>
                            <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                                <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📤 Remitente</h3>
                                <p><strong>Tienda:</strong> ${item.remitente_nombre}</p>
                            </td>
                            <td style="width: 4%;"></td>
                            <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                                <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📥 Destinatario</h3>
                                <p><strong>Dirección:</strong> ${item.destinatario_direccion}</p>
                                <p><strong>Nombre:</strong> ${item.destinatario_nombre}</p>
                                <p><strong>Teléfono:</strong> ${item.destinatario_telefono}</p>
                            </td>
                        </tr>
                    </table>
                    <div style="margin-top: 20px; border: 1px solid #eee; padding: 10px; border-radius: 8px; font-size: 11px;">
                        <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📦 Detalles del Paquete</h3>
                    </div>
                    <table style="width: 100%; margin-top: 20px; border-top: 2px solid #5cb85c; padding-top: 10px;">
                        <tr>
                            <td style="width: 60%; vertical-align: top; font-size: 11px;">
                                <h3 style="margin: 0 0 10px; font-size: 14px;">💰 Total a Cobrar</h3>
                                <p style="margin: 4px 0; font-size: 32px; font-weight: 800; color: #28a745;">$${(item.valor_recaudo > 0 ? item.valor_recaudo : 0).toLocaleString('es-CO')}</p>
                            </td>
                            <td style="width: 40%; text-align: right;">
                                <img src="${qrImageDataUrl}" style="width: 190px; height: 190px;">
                            </td>
                        </tr>
                    </table>
                </div>
            `;

            // 3. Generar PDF
            const canvas = await html2canvas(pdfTemplate, { useCORS: true, scale: 2 });
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save(`Guia-${numeroGuia}.pdf`);
            
            // Limpieza
            document.body.removeChild(pdfTemplate);
            URL.revokeObjectURL(qrImageDataUrl);

        } catch (error) {
            console.error("Error generando PDF masivo:", error);
            alert("Error al generar el PDF.");
        }
    };

    async function descargarPlantillaExcelEstilizada() {
        if (typeof ExcelJS === 'undefined') {
            throw new Error('ExcelJS no está disponible');
        }

        const workbook = new ExcelJS.Workbook();
        const sheet = workbook.addWorksheet('Plantilla');

        const rows = [
            ["Numero", "Dimenciones", "Costo adicional"],
            [1, "Menor o igual a 20 x 20 cm", 0],
            [2, "Entre 21x21 y 30x30 cm", 2000],
            [3, "Entre 31x31 y 35x35 cm", 4000],
            [4, "Entre 36x36 y 40x40 cm", 7000],
            [5, "Entre 41x41 y 45x45 cm", 10000],
            [6, "Entre 46x46 y 49x49 cm", 12000],
            [7, "Igual o mayor a 50 x 50 cm", "Notificar"],
            [],
            ["Datos Remitente", "", "", "", "Datos Destinatario", "", "", "", "Información del Paquete", "", "", "", "", "", ""],
            ["Nombre Completo", "Telefono", "Email de contacto", "Direccion de Origen", "Nombre Completo", "Telefono", "Direccion de Destino", "Observaciones y/o Descripciones", "Pago Contra Entrega (si/no)", "Valor recaudo", "Dimensiones del paquete", "Entrega Mismo Dia (si/no)", "Zona Periférica (si/no)", "Recoger Cambios (si/no)", "Sumar envio al recaudo (si/no)"],
            ["Ej: Pepito Perez", "1234567890", "pepito@gmail.com", "Carrera 5 #33-22", "Maria Perez", "1234567890", "Calle 4 #23-10 Este", "Ninguna", "si", 100000, 3, "no", "no", "si", "si"]
        ];

        rows.forEach(row => sheet.addRow(row));
        sheet.columns = [
            { width: 18 }, { width: 26 }, { width: 16 }, { width: 22 },
            { width: 18 }, { width: 14 }, { width: 20 }, { width: 24 },
            { width: 18 }, { width: 14 }, { width: 16 }, { width: 18 },
            { width: 16 }, { width: 16 }, { width: 20 }
        ];

        sheet.mergeCells('A10:D10');
        sheet.mergeCells('E10:H10');
        sheet.mergeCells('I10:O10');

        const border = {
            top: { style: 'thin', color: { argb: 'FF000000' } },
            bottom: { style: 'thin', color: { argb: 'FF000000' } },
            left: { style: 'thin', color: { argb: 'FF000000' } },
            right: { style: 'thin', color: { argb: 'FF000000' } }
        };

        const styleRange = (rowStart, rowEnd, colStart, colEnd, fill, bold = false) => {
            for (let row = rowStart; row <= rowEnd; row++) {
                for (let col = colStart; col <= colEnd; col++) {
                    const cell = sheet.getCell(row, col);
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    cell.border = border;
                    if (fill) {
                        cell.fill = {
                            type: 'pattern',
                            pattern: 'solid',
                            fgColor: { argb: fill }
                        };
                    }
                    cell.font = {
                        bold,
                        color: { argb: 'FF1F2937' },
                        size: row === 10 ? 12 : 11
                    };
                }
            }
        };

        styleRange(1, 8, 1, 3, 'FFFFF2CC', true);
        styleRange(10, 10, 1, 15, 'FFD9EAF7', true);
        styleRange(11, 11, 1, 15, 'FFDFF0D0', true);

        for (let row = 1; row <= 11; row++) {
            sheet.getRow(row).height = row === 10 ? 24 : 20;
        }

        const listSheet = workbook.addWorksheet('Listas', { state: 'hidden' });
        [
            ["Numero", "Dimension", "Costo"],
            [1, "Menor o igual a 20 x 20 cm", 0],
            [2, "Entre 21x21 y 30x30 cm", 2000],
            [3, "Entre 31x31 y 35x35 cm", 4000],
            [4, "Entre 36x36 y 40x40 cm", 7000],
            [5, "Entre 41x41 y 45x45 cm", 10000],
            [6, "Entre 46x46 y 49x49 cm", 12000],
            [7, "Igual o mayor a 50 x 50 cm", "Notificar"]
        ].forEach(row => listSheet.addRow(row));

        for (let row = 12; row <= 1000; row++) {
            sheet.getCell(`K${row}`).dataValidation = {
                type: 'list',
                allowBlank: true,
                formulae: ['Listas!$A$2:$A$8']
            };
        }

        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'Plantilla_Envio_EcoBikeMess.xlsx';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // --- DESCARGAR PLANTILLA EXCEL ---
    if (btnDownloadTemplate) {
        btnDownloadTemplate.addEventListener('click', async () => {
            try {
                if (typeof ExcelJS !== 'undefined') {
                    await descargarPlantillaExcelEstilizada();
                    return;
                }
                const plantillaAoa = [
                    {
                        "Destinatario Nombre": "Ej: María González",
                        "Num Destinatario": "3001234567",
                        "Dirección": "Calle 100 # 15-20",
                        "Instrucciones Entrega": "Dejar en recepción",
                        "Descripción Contenido": "Ropa y accesorios",
                        "Dimensiones del Paquete": "Menor o igual a 20 x 20 cm",
                        "Entrega Mismo Día (si/no)": "no",
                        "Zona Periférica (si/no)": "no",
                        "Recoger Cambios (si/no)": "no",
                        "Tiene Recaudo (si/no)": "no",
                        "Valor Recaudo": 0,
                        "Sumar Envío al Recaudo (si/no)": "no"
                    }
                ];
                const ws = (() => {
                    const sheet = XLSX.utils.aoa_to_sheet([
                        ["Numero", "Dimenciones", "Costo adicional"],
                        [1, "Menor o igual a 20 x 20 cm", 0],
                        [2, "Entre 21x21 y 30x30 cm", 2000],
                        [3, "Entre 31x31 y 35x35 cm", 4000],
                        [4, "Entre 36x36 y 40x40 cm", 7000],
                        [5, "Entre 41x41 y 45x45 cm", 10000],
                        [6, "Entre 46x46 y 49x49 cm", 12000],
                        [7, "Igual o mayor a 50 x 50 cm", "Notificar"],
                        [],
                        ["Datos Remitente", "", "", "", "Datos Destinatario", "", "", "", "Información Del Paquete", "", "", "", "", "", ""],
                        ["Nombre Completo", "Telefono", "Email de contacto", "Direccion de Origen", "Nombre Completo", "Telefono", "Direccion de Destino", "Observaciones y/o Descripciones", "Pago Contra Entrega (si/no)", "Valor recaudo", "Dimensiones del paquete", "Entrega Mismo Dia (si/no)", "Zona Periférica (si/no)", "Recoger Cambios (si/no)", "Sumar envio al recaudo (si/no)"]
                    ]);
                    sheet['!cols'] = [
                        { wch: 18 }, { wch: 26 }, { wch: 16 }, { wch: 22 },
                        { wch: 18 }, { wch: 14 }, { wch: 20 }, { wch: 24 },
                        { wch: 18 }, { wch: 14 }, { wch: 16 }, { wch: 18 },
                        { wch: 16 }, { wch: 16 }, { wch: 20 }
                    ];
                    sheet['!merges'] = [
                        { s: { r: 9, c: 0 }, e: { r: 9, c: 3 } },
                        { s: { r: 9, c: 4 }, e: { r: 9, c: 7 } },
                        { s: { r: 9, c: 8 }, e: { r: 9, c: 14 } }
                    ];
                    const border = {
                        top: { style: 'thin', color: { rgb: '000000' } },
                        bottom: { style: 'thin', color: { rgb: '000000' } },
                        left: { style: 'thin', color: { rgb: '000000' } },
                        right: { style: 'thin', color: { rgb: '000000' } }
                    };
                    const centerStyle = {
                        alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
                        border
                    };
                    const paintRange = (startRow, endRow, startCol, endCol, fill, bold = false) => {
                        for (let r = startRow; r <= endRow; r++) {
                            for (let c = startCol; c <= endCol; c++) {
                                const ref = XLSX.utils.encode_cell({ r, c });
                                if (!sheet[ref]) continue;
                                sheet[ref].s = {
                                    ...centerStyle,
                                    font: { bold, color: { rgb: '1F2937' } },
                                    fill: fill ? { fgColor: { rgb: fill } } : undefined
                                };
                            }
                        }
                    };
                    paintRange(0, 7, 0, 2, 'FFF2CC', true);
                    paintRange(9, 9, 0, 14, 'D9EAF7', true);
                    paintRange(10, 10, 0, 14, 'DFF0D0', true);
                    return sheet;
                })();
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Plantilla");

                const wsList = XLSX.utils.aoa_to_sheet([
                    ["Numero", "Dimension", "Costo"],
                    [1, "Menor o igual a 20 x 20 cm", 0],
                    [2, "Entre 21x21 y 30x30 cm", 2000],
                    [3, "Entre 31x31 y 35x35 cm", 4000],
                    [4, "Entre 36x36 y 40x40 cm", 7000],
                    [5, "Entre 41x41 y 45x45 cm", 10000],
                    [6, "Entre 46x46 y 49x49 cm", 12000],
                    [7, "Igual o mayor a 50 x 50 cm", "Notificar"]
                ]);
                wsList['!hidden'] = 1;
                XLSX.utils.book_append_sheet(wb, wsList, "Listas");

                ws['!dataValidation'] = [{
                    sqref: 'K12:K1000',
                    type: 'list',
                    allowBlank: true,
                    formula1: 'Listas!$A$2:$A$8'
                }];

                XLSX.writeFile(wb, "Plantilla_Envio_EcoBikeMess.xlsx");
            } catch (error) {
                console.error("Error al generar plantilla:", error);
                alert("Error al generar la plantilla. Asegúrate de que la librería XLSX esté cargada.");
            }
        });
    }

    if (tieneRecaudoCheckbox) {
        tieneRecaudoCheckbox.addEventListener('change', () => {
            recaudoField.style.display = tieneRecaudoCheckbox.checked ? 'block' : 'none';
            if (!tieneRecaudoCheckbox.checked) {
                document.getElementById('valor_recaudo').value = '';
                if (valorRecaudoHidden) valorRecaudoHidden.value = '';
                baseRecaudo = 0;
            }
            calcularCostoAutomatico();
        });
    }

    // En algunas versiones no existe esta función; evitar que el JS se rompa
    if (typeof actualizarAvisoHorario === 'function') {
        actualizarAvisoHorario();
    }

    // --- CÁLCULO AUTOMÁTICO DE COSTO ---
    function calcularCostoAutomatico() {
        // Nuevos campos de recargo
        const mismoDiaCheckbox = document.getElementById('envio_mismo_dia');
        const zonaPerifericaCheckbox = document.getElementById('zona_periferica');
        const recogerCambiosCheckbox = document.getElementById('recoger_cambios');
        const dimensionesSelect = document.getElementById('dimensiones_paquete');

        let recargoDimensionesValue = dimensionesSelect ? dimensionesSelect.value : '0';

        if (recargoDimensionesValue === 'notificar') {
            const modal = document.getElementById('whatsappModal');
            if (modal) {
                modal.style.display = 'flex';
                const closeBtn = modal.querySelector('.close-wa-modal');
                if(closeBtn) closeBtn.onclick = () => modal.style.display = 'none';
                modal.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
            } else {
                // Fallback si no existe el modal
                if(confirm('Para paquetes de 50x50 cm o más, por favor contáctanos directamente al WhatsApp +57 312318019. ¿Deseas ir ahora?')) {
                    window.open('https://wa.link/49g8jg', '_blank');
                }
            }
            
            dimensionesSelect.value = ''; // Resetear selección
            recargoDimensionesValue = '0'; // Evitar que se calcule un costo
        }

        let costoBase = 8000;
        let recargoMismoDia = (mismoDiaCheckbox && mismoDiaCheckbox.checked) ? 2000 : 0;
        let recargoZona = (zonaPerifericaCheckbox && zonaPerifericaCheckbox.checked) ? 4000 : 0;
        let recargoCambios = (recogerCambiosCheckbox && recogerCambiosCheckbox.checked) ? 5000 : 0;
        let recargoDimensiones = parseInt(recargoDimensionesValue, 10) || 0;
        
        let recargoRecaudo = 0;
        if (tieneRecaudoCheckbox && tieneRecaudoCheckbox.checked) {
            const fijoContraentrega = 3000;
            const monto = Number(baseRecaudo || 0);
            const extra = monto >= 300000 ? Math.floor((monto - 300000) / 100000) * 1000 : 0;
            recargoRecaudo = fijoContraentrega + Math.max(0, extra);
        }

        const total = costoBase + recargoRecaudo + recargoMismoDia + recargoZona + recargoDimensiones + recargoCambios;

        document.getElementById('costoBase').textContent = `$${costoBase.toLocaleString('es-CO')}`;
        
        const recargoDimensionesDisplay = document.getElementById('recargoDimensiones');
        if(recargoDimensionesDisplay) {
            recargoDimensionesDisplay.textContent = `$${recargoDimensiones.toLocaleString('es-CO')}`;
        }

        const recargoMismoDiaDisplay = document.getElementById('recargoMismoDia');
        if(recargoMismoDiaDisplay) {
            recargoMismoDiaDisplay.textContent = `$${recargoMismoDia.toLocaleString('es-CO')}`;
        }

        const recargoZonaDisplay = document.getElementById('recargoZona');
        if(recargoZonaDisplay) {
            recargoZonaDisplay.textContent = `$${recargoZona.toLocaleString('es-CO')}`;
        }

        const recargoCambiosDisplay = document.getElementById('recargoCambios');
        if(recargoCambiosDisplay) {
            recargoCambiosDisplay.textContent = `$${recargoCambios.toLocaleString('es-CO')}`;
        }
        
        const recaudoDisplay = document.getElementById('valorRecaudoDisplay');
        if (recaudoDisplay) {
            recaudoDisplay.textContent = `$${recargoRecaudo.toLocaleString('es-CO')}`;
        }
        
        document.getElementById('costoTotal').textContent = `$${total.toLocaleString('es-CO')}`;
        
        if(costoTotalHiddenInput) {
            costoTotalHiddenInput.value = total;
        }

        // Manejar visibilidad de la opción de sumar envío
        const containerSumar = document.getElementById('container_sumar_envio');
        if (containerSumar) containerSumar.style.display = 'block';
        asegurarSeleccionEnvioDestinatario();
        actualizarRecaudoFinal(); // Recalcular siempre
    }

    function asegurarSeleccionEnvioDestinatario() {
        const radios = document.querySelectorAll('input[name="envio_destinatario"]');
        if (!radios || radios.length === 0) return;

        const anyChecked = Array.from(radios).some(r => r.checked);
        if (anyChecked) return;

        const defaultRadio = document.querySelector('input[name="envio_destinatario"][value="no"]');
        if (defaultRadio) defaultRadio.checked = true;
    }

    // Función para actualizar el recaudo final según la selección
    function actualizarRecaudoFinal() {
        const sumarOption = document.querySelector('input[name="envio_destinatario"]:checked');
        const costoTotal = parseFloat(costoTotalHiddenInput.value) || 0;
        const baseRecaudoActual = getBaseRecaudoValue();
        baseRecaudo = baseRecaudoActual;
        const preview = document.getElementById('preview_total_recaudo');
        
        // Actualizar estilos visuales de las tarjetas
        document.querySelectorAll('.radio-card').forEach(c => c.classList.remove('selected'));
        if (sumarOption) {
            sumarOption.closest('.radio-card').classList.add('selected');
        }

        if (sumarOption && sumarOption.value === 'si') {
            const total = baseRecaudoActual + costoTotal;
            if (valorRecaudoHidden) valorRecaudoHidden.value = total;
            if (preview) {
                preview.style.display = 'block';
                preview.innerHTML = `Total a cobrar al destinatario: <span style="font-size: 1.2em;">$${total.toLocaleString('es-CO')}</span>`;
            }
        } else if (sumarOption && sumarOption.value === 'no') {
            if (valorRecaudoHidden) valorRecaudoHidden.value = baseRecaudoActual;
            if (preview) {
                preview.style.display = 'block';
                preview.innerHTML = `Total a cobrar al destinatario: <span style="font-size: 1.2em;">$${baseRecaudoActual.toLocaleString('es-CO')}</span>`;
            }
        } else {
            if (preview) preview.style.display = 'none';
        }
    }

    // Agregar listeners a los campos que afectan el precio
    const mismoDiaInput = document.getElementById('envio_mismo_dia');
    const zonaPerifericaInput = document.getElementById('zona_periferica');
    const recogerCambiosInput = document.getElementById('recoger_cambios');
    const dimensionesInput = document.getElementById('dimensiones_paquete');

    if (mismoDiaInput) mismoDiaInput.addEventListener('change', calcularCostoAutomatico);
    if (zonaPerifericaInput) zonaPerifericaInput.addEventListener('change', calcularCostoAutomatico);
    if (recogerCambiosInput) recogerCambiosInput.addEventListener('change', calcularCostoAutomatico);
    if (dimensionesInput) dimensionesInput.addEventListener('change', calcularCostoAutomatico);
    
    // Listeners para los radios de sumar envío
    const radiosSumar = document.querySelectorAll('input[name="envio_destinatario"]');
    radiosSumar.forEach(r => r.addEventListener('change', actualizarRecaudoFinal));

    // Hacer que toda la tarjeta sea clickeable (mejora UX y evita "no me deja seleccionar")
    document.querySelectorAll('#container_sumar_envio .radio-card').forEach(card => {
        card.addEventListener('click', () => {
            const radio = card.querySelector('input[type="radio"]');
            if (!radio) return;
            if (radio.disabled) return;
            radio.checked = true;
            radio.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    // Mantener compatibilidad con el botón si existe (aunque lo ocultaremos)
    if (calcularCostoBtn) {
        calcularCostoBtn.addEventListener('click', calcularCostoAutomatico);
    }

    // Inicializar costos/estado al cargar (para que aparezca la opción de cobrar envío)
    try {
        calcularCostoAutomatico();
        asegurarSeleccionEnvioDestinatario();
        actualizarRecaudoFinal();
    } catch (e) {
        console.warn('No se pudo inicializar el cálculo automático', e);
    }

    function populateConfirmation() {
        if (window.RotuloEcoBike) {
            const costoEnvioNum = parseFloat(document.getElementById('costoTotalHidden').value) || 0;
            const baseRecaudoNum = getBaseRecaudoValue() || 0;
            const sumarOption = document.querySelector('input[name="envio_destinatario"]:checked');
            const sumar = sumarOption ? sumarOption.value : 'no';
            const tieneRecaudo = document.getElementById('tiene_recaudo').checked;
            const cobrarEnvio = sumar === 'si';
            const totalCobrar = (tieneRecaudo || baseRecaudoNum > 0 || cobrarEnvio)
                ? baseRecaudoNum + (cobrarEnvio ? costoEnvioNum : 0)
                : 0;
            const numeroGuia = generarNumeroGuia();

            if (numeroGuiaHiddenInput) {
                numeroGuiaHiddenInput.value = numeroGuia;
            }

            currentPreviewRotuloData = {
                guia: numeroGuia,
                tienda_nombre: window.remitenteData?.nombre_tienda || document.getElementById('remitente_nombre').value || 'Tienda',
                remitente_nombre: document.getElementById('remitente_nombre').value || '',
                destinatario_nombre: document.getElementById('destinatario_nombre').value || '',
                destinatario_direccion: document.getElementById('destinatario_direccion').value || '',
                destinatario_telefono: document.getElementById('destinatario_telefono').value || '',
                destinatario_observaciones: document.getElementById('instrucciones_entrega').value || 'Sin observaciones',
                cambios: document.getElementById('recoger_cambios').checked ? 'Si' : 'No',
                recaudo: totalCobrar
            };

            const preview = document.getElementById('rotuloPreview');
            if (preview) {
                window.RotuloEcoBike.mountPreview(preview, currentPreviewRotuloData).catch(error => {
                    console.error('No se pudo renderizar el rótulo compartido:', error);
                });
            }
            return;
        }

        // Usar el nombre de la tienda desde los datos cargados de la BD (window.remitenteData)
        document.getElementById('confirm_tienda_nombre').textContent = window.remitenteData?.nombre_tienda || 'Tienda';

        document.getElementById('confirm_destinatario_nombre').textContent = document.getElementById('destinatario_nombre').value;
        document.getElementById('confirm_destinatario_telefono').textContent = document.getElementById('destinatario_telefono').value;
        document.getElementById('confirm_destinatario_direccion').textContent = document.getElementById('destinatario_direccion').value;
        document.getElementById('confirm_destinatario_observaciones').textContent = document.getElementById('instrucciones_entrega').value || 'Sin observaciones';

        const recogerCambios = document.getElementById('recoger_cambios').checked;
        document.getElementById('confirm_recoger_cambios').textContent = recogerCambios ? 'Sí' : 'No';


        // --- CÁLCULO DE TOTALES A COBRAR ---
        const costoEnvioNum = parseFloat(document.getElementById('costoTotalHidden').value) || 0;
        const baseRecaudoNum = getBaseRecaudoValue() || 0;
        const sumarOption = document.querySelector('input[name="envio_destinatario"]:checked');
        const sumar = sumarOption ? sumarOption.value : 'no';
        const tieneRecaudo = document.getElementById('tiene_recaudo').checked;

        let totalCobrar = 0;
        let valorProducto = 0;
        let valorEnvio = 0;

        // Cobro al destinatario si hay recaudo o si se decidió sumar envío
        const cobrarEnvio = sumar === 'si';
        const debeCobrar = tieneRecaudo || baseRecaudoNum > 0 || cobrarEnvio;

        if (debeCobrar) {
            valorProducto = baseRecaudoNum;
            valorEnvio = cobrarEnvio ? costoEnvioNum : 0;
            totalCobrar = valorProducto + valorEnvio;
        } else {
            totalCobrar = 0;
            valorProducto = 0;
            valorEnvio = 0;
        }

        const totalCobrarTexto = `$${totalCobrar.toLocaleString('es-CO')}`;
        document.getElementById('confirm_total_cobrar').textContent = totalCobrarTexto;

        const numeroGuia = generarNumeroGuia();
        document.getElementById('numeroGuia').textContent = numeroGuia;
        // Guardar la guía en el input oculto para enviarla al backend
        if (numeroGuiaHiddenInput) {
            numeroGuiaHiddenInput.value = numeroGuia;
        }

        // Lógica para mostrar información financiera en el QR
        let qrFinanciero = '';

        qrFinanciero = `Total a Cobrar: ${totalCobrarTexto}`;

        // --- QR CODE GENERATION ---
        const infoParaQR = `
Guía: ${numeroGuia}
Remitente: ${document.getElementById('remitente_nombre').value}
Origen: ${document.getElementById('remitente_direccion').value}
Destinatario: ${document.getElementById('destinatario_nombre').value}
Destino: ${document.getElementById('destinatario_direccion').value}
Cambios por recoger: ${recogerCambios ? 'Sí' : 'No'}
${qrFinanciero}
        `.trim();

        // Limpiar contenedor de QR
        qrcodeContainer.innerHTML = '';
        
        // Crear nueva instancia de QR con logo
        qrCodeStylingInstance = new QRCodeStyling({
            width: 128,
            height: 128,
            data: numeroGuia,
            // image: "../../public/img/logo_qr.png", // Desactivado para asegurar que el QR funcione. Actívalo cuando tengas el logo.
            dotsOptions: {
                color: "#000000",
                type: "square"
            },
            backgroundOptions: {
                color: "#ffffff",
            },
            imageOptions: {
                crossOrigin: "anonymous",
                margin: 4
            },
            qrOptions: {
                errorCorrectionLevel: 'M' // Menos densidad y mejor lectura en pantalla
            }
        });

        qrCodeStylingInstance.append(qrcodeContainer);
    }

    // --- PDF DOWNLOAD ---
    if (btnDownloadPDF) {
        btnDownloadPDF.addEventListener('click', async () => {
            if (currentPreviewRotuloData && window.RotuloEcoBike) {
                try {
                    await window.RotuloEcoBike.downloadPdf(currentPreviewRotuloData, { filePrefix: 'Guia' });
                    return;
                } catch (error) {
                    console.error("Error al generar PDF:", error);
                    alert("Ocurrió un error al intentar descargar el PDF. Revisa la consola para más detalles.");
                    return;
                }
            }
            try {
                const { jsPDF } = window.jspdf;
                const numeroGuia = document.getElementById('numeroGuia').textContent;
                
                const costoEnvioNum = parseFloat(document.getElementById('costoTotalHidden').value) || 0;
                const baseRecaudoNum = getBaseRecaudoValue() || 0;
                const sumarOption = document.querySelector('input[name="envio_destinatario"]:checked');
                const sumar = sumarOption ? sumarOption.value : 'no';
                const tieneRecaudo = document.getElementById('tiene_recaudo').checked;

                const cobrarEnvio = sumar === 'si';
                const debeCobrar = tieneRecaudo || baseRecaudoNum > 0 || cobrarEnvio;

                let totalCobrar = 0;
                if (debeCobrar) {
                    totalCobrar = baseRecaudoNum + (cobrarEnvio ? costoEnvioNum : 0);
                }

                const totalCobrarTexto = `$${totalCobrar.toLocaleString('es-CO')}`;

                const recogerCambiosChecked = document.getElementById('recoger_cambios').checked;

                // Obtener la imagen del QR como Data URL
                if (!qrCodeStylingInstance) {
                    alert('Error: El código QR no se ha generado. No se puede crear el PDF.');
                    return;
                }
                
                // CORRECCIÓN: Usamos getRawData y creamos un objeto URL porque getImageUrl no existe en esta versión
                const qrBlob = await qrCodeStylingInstance.getRawData('png');
                const qrImageDataUrl = URL.createObjectURL(qrBlob);

                // Crear un div temporal para generar el PDF a partir de HTML
                const pdfTemplate = document.createElement('div');
                pdfTemplate.style.width = '210mm'; // Ancho de A4
                pdfTemplate.style.position = 'absolute';
                pdfTemplate.style.left = '-9999px'; // Moverlo fuera de la pantalla
                document.body.appendChild(pdfTemplate); // Añadir al DOM para que html2canvas lo renderice
                
                pdfTemplate.innerHTML = `
                    <div style="font-family: Arial, sans-serif; color: #333; padding: 12px; border: 1px solid #ccc; background: #fff; width: 100mm; height: 100mm; box-sizing: border-box;">
                        <div style="transform: scale(0.72); transform-origin: top left; width: 139mm; height: 139mm;">
                            <table style="width: 100%; border-bottom: 2px solid #5cb85c; padding-bottom: 6px;">
                                <tr>
                                    <td colspan="2">
                                        <div style="display: flex; align-items: center; gap: 10px; justify-content: center; text-align: center;">
                                            <img src="../../public/img/Logo_Circulo_Fondoblanco.png" alt="EcoBikeMess" style="width:100px;height:100px;">
                                            <div>
                                                <div style="font-size: 26px; font-weight: 800; color: #5cb85c; line-height: 1;">EcoBikeMess</div>
                                                <div style="margin-top: 3px; font-size: 15px; font-weight: 700; color: #28a745;">Contactanos: 317509298</div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding-top: 4px;">
                                        <div style="font-size: 13px; font-weight: 800; color: #000000;">NUM GUÍA: <span style="font-size: 19px; font-weight: 800; color: #1f2a37;">${numeroGuia}</span></div>
                                    </td>
                                </tr>
                            </table>

                            <table style="width: 100%; margin-top: 4px; font-size: 12px;">
                                <tr>
                                    <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800; border-bottom: 1px solid #eee; padding-bottom: 5px;">📥 Destinatario</h3>
                                        <p style="margin: 2px 0; line-height: 1.05;"><strong>Dirección:</strong> <span style="font-size: 15px; font-weight: 700;">${document.getElementById('destinatario_direccion').value}</span></p>
                                        <p style="margin: 2px 0; line-height: 1.05;"><strong>Nombre:</strong> <span style="font-size: 15px; font-weight: 700;">${document.getElementById('destinatario_nombre').value}</span></p>
                                        <p style="margin: 2px 0; line-height: 1.05;"><strong>Teléfono:</strong> <span style="font-size: 15px; font-weight: 700;">${document.getElementById('destinatario_telefono').value}</span></p>
                                        <p style="margin: 2px 0; line-height: 1.05;"><strong>Observaciones:</strong> <span style="font-size: 15px; font-weight: 700;">${document.getElementById('instrucciones_entrega').value || 'Sin observaciones'}</span></p>
                                    </td>
                                    <td style="width: 4%;"></td>
                                    <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                        <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800; border-bottom: 1px solid #eee; padding-bottom: 5px;">📤 Remitente</h3>
                                        <p style="margin: 2px 0; line-height: 1.05;"><strong>Tienda:</strong> <span style="font-size: 15px; font-weight: 700;">${window.remitenteData?.nombre_tienda || 'Tienda'}</span></p>
                                    </td>
                                </tr>
                            </table>

                            <table style="width: 100%; margin-top: 4px; padding-top: 0;">
                                <tr>
                                    <td style="width: 60%; vertical-align: top; font-size: 12px;">
                                        <div style="border: 1px solid #eee; padding: 6px; border-radius: 8px;">
                                            <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800; border-bottom: 1px solid #eee; padding-bottom: 5px;">📦 Detalles del Paquete</h3>
                                            <p style="margin: 2px 0; line-height: 1.05;"><strong>Cambios por recoger:</strong> <span style="font-size: 15px; font-weight: 700;">${recogerCambiosChecked ? 'Sí' : 'No'}</span></p>
                                        </div>
                                        <div style="margin-top: 6px;">
                                            <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800;">💰 Total a Cobrar</h3>
                                            <p style="margin: 2px 0; font-size: 26px; font-weight: 800; color: #28a745; line-height: 1.1;">${totalCobrarTexto}</p>
                                        </div>
                                    </td>
                                    <td style="width: 40%; text-align: right; vertical-align: top;">
                                        <img src="${qrImageDataUrl}" style="width:132px;height:132px;border:1px solid #e5e7eb;border-radius:10px;padding:2px;margin-right:6mm;margin-top:-2mm;background:#fff;">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;

                html2canvas(pdfTemplate, { useCORS: true, scale: 2 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                    pdf.save(`Guia-${numeroGuia}.pdf`);
                    document.body.removeChild(pdfTemplate); // Limpiar el DOM
                    URL.revokeObjectURL(qrImageDataUrl); // Liberar memoria
                }).catch(err => {
                    console.error("Error al generar canvas:", err);
                    alert("Error al generar la imagen del PDF.");
                    if(document.body.contains(pdfTemplate)) document.body.removeChild(pdfTemplate);
                });

            } catch (error) {
                console.error("Error al generar PDF:", error);
                alert("Ocurrió un error al intentar descargar el PDF. Revisa la consola para más detalles.");
            }
        });
    }
});
