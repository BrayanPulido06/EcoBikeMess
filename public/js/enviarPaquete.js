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
    const qrcodeContainer = document.getElementById('qrcode');
    let qrCodeStylingInstance = null; // Para la instancia del nuevo QR
    let baseRecaudo = 0; // Variable para almacenar el valor base del recaudo (sin envío)

    let currentStep = 1;

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
                actualizarRecaudoFinal();
            } else {
                e.target.value = '';
                baseRecaudo = 0;
                if (valorRecaudoHidden) valorRecaudoHidden.value = '';
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
                    const headers = jsonData[0].map(h => normalize(h));
                    
                    const dataRows = jsonData.slice(1);

                    // --- MODO MASIVO ---
                    if (dataRows.length > 1) {
                        if(confirm(`Se encontraron ${dataRows.length} registros. ¿Deseas cargarlos en modo masivo?`)) {
                            initBulkMode(headers, dataRows);
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

                    // Helper para asignar valor y disparar eventos
                    const setField = (id, val) => {
                        const input = document.getElementById(id);
                        if (input && val !== undefined) {
                            input.value = val;
                            input.dispatchEvent(new Event('input'));
                        }
                    };

                    // 1. Llenar Destinatario
                    setField('destinatario_nombre', getValue('nombre') || getValue('destinatario'));
                    // Agregamos 'movil' y la normalización arregla 'Teléfono'
                    setField('destinatario_telefono', getValue('telefono') || getValue('celular') || getValue('movil'));
                    setField('destinatario_direccion', getValue('direccion') || getValue('destino'));
                    setField('instrucciones_entrega', getValue('instrucciones') || getValue('observaciones'));

                    // 2. Llenar Paquete
                    setField('descripcion_contenido', getValue('descripcion') || getValue('contenido'));

                    // Dimensiones - El usuario debe seleccionarlo manualmente ya que la lógica es compleja.
                    // Se podría implementar una lógica para mapear cm a la opción correcta si se desea.

                    // Recaudo
                    const recaudoVal = getValue('recaudo') || getValue('valor');
                    if (recaudoVal && !isNaN(parseFloat(recaudoVal)) && parseFloat(recaudoVal) > 0) {
                        if (!tieneRecaudoCheckbox.checked) tieneRecaudoCheckbox.click();
                        setField('valor_recaudo', recaudoVal);
                    } else {
                        if (tieneRecaudoCheckbox.checked) tieneRecaudoCheckbox.click();
                    }

                    // 3. Auto-llenar Remitente (Datos del usuario logueado)
                    if (autoFillBtn) autoFillBtn.click();

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
    function initBulkMode(headers, rows) {
        // Ocultar formulario normal y mostrar contenedor masivo
        document.getElementById('envioForm').style.display = 'none';
        document.querySelector('.steps-indicator').style.display = 'none';
        document.querySelector('.page-header h1').textContent = 'Carga Masiva';
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

            let costoBase = 8000;
            let recargoRecaudo = (getValue('recaudo') > 0) ? 3000 : 0;
            const total = costoBase + recargoRecaudo; // Nota: No se pueden calcular otros recargos desde excel (dimensiones, etc)

            // Preparar objeto de datos
            const item = {
                id: index,
                remitente_nombre: document.getElementById('remitente_nombre').value || (window.remitenteData?.nombre_completo), // Usar datos del usuario logueado
                remitente_telefono: document.getElementById('remitente_telefono').value || (window.remitenteData?.telefono),
                remitente_email: document.getElementById('remitente_email').value || (window.remitenteData?.correo),
                remitente_direccion: document.getElementById('remitente_direccion').value || (window.remitenteData?.direccion),
                destinatario_nombre: getValue('nombre') || getValue('destinatario'),
                destinatario_telefono: getValue('telefono') || getValue('celular') || getValue('movil'),
                destinatario_direccion: getValue('direccion') || getValue('destino'),
                instrucciones_entrega: getValue('instrucciones') || getValue('observaciones'),
                descripcion_contenido: getValue('descripcion') || getValue('contenido'),
                tiene_recaudo: (getValue('recaudo') > 0) ? 'on' : '',
                valor_recaudo: getValue('recaudo') || 0,
                costo_total: total,
                // Generar guía temporal
                numero_guia: `ECO-${new Date().getFullYear()}${Math.random().toString(36).substring(2, 7).toUpperCase()}`
            };

            bulkData.push(item);

            // Renderizar fila
            const tr = document.createElement('tr');
            tr.id = `row-${index}`;
            tr.innerHTML = `
                <td>${item.destinatario_nombre}</td>
                <td>${item.destinatario_telefono}</td>
                <td>${item.destinatario_direccion}</td>
                <td>${item.descripcion_contenido}</td>
                <td>$${total.toLocaleString('es-CO')}</td>
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
                    const response = await fetch('../../controller/enviarPaqueteController.php', {
                        method: 'POST',
                        body: formData
                    });

                    // Intentar parsear JSON
                    const result = await response.json();

                    if (result.success) {
                        statusCell.textContent = '✓ Creado';
                        statusCell.className = 'status-success';
                        
                        // Agregar botón de descarga
                        const actionsCell = document.getElementById(`actions-${item.id}`);
                        actionsCell.innerHTML = `
                            <button type="button" class="btn-text" style="color: #28a745; font-size: 0.9rem;" onclick="downloadBulkPDF(${item.id}, '${result.guia}')">⬇️ Rótulo</button>
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

        try {
            const { jsPDF } = window.jspdf;
            
            // 1. Generar QR
            const infoParaQR = `
Guía: ${numeroGuia}
Remitente: ${item.remitente_nombre}
Origen: ${item.remitente_direccion}
Destinatario: ${item.destinatario_nombre}
Destino: ${item.destinatario_direccion}
Contenido: ${item.descripcion_contenido}
Costo Envío: $${item.costo_total.toLocaleString('es-CO')}
Recaudo: ${item.valor_recaudo > 0 ? '$' + item.valor_recaudo : 'No aplica'}
            `.trim();

            const qrCode = new QRCodeStyling({
                width: 300,
                height: 300,
                data: infoParaQR,
                dotsOptions: { color: "#000000", type: "rounded" },
                backgroundOptions: { color: "#ffffff" },
                imageOptions: { crossOrigin: "anonymous", margin: 4 },
                qrOptions: { errorCorrectionLevel: 'H' }
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
                                <h1 style="font-size: 24px; margin: 0; color: #5cb85c;">🚴 EcoBikeMess</h1>
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
                                <p><strong>Nombre:</strong> ${item.remitente_nombre}</p>
                                <p><strong>Teléfono:</strong> ${item.remitente_telefono}</p>
                                <p><strong>Dirección:</strong> ${item.remitente_direccion}</p>
                            </td>
                            <td style="width: 4%;"></td>
                            <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                                <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📥 Destinatario</h3>
                                <p><strong>Nombre:</strong> ${item.destinatario_nombre}</p>
                                <p><strong>Teléfono:</strong> ${item.destinatario_telefono}</p>
                                <p><strong>Dirección:</strong> ${item.destinatario_direccion}</p>
                            </td>
                        </tr>
                    </table>
                    <div style="margin-top: 20px; border: 1px solid #eee; padding: 10px; border-radius: 8px; font-size: 11px;">
                        <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📦 Detalles del Paquete</h3>
                        <p><strong>Descripción:</strong> ${item.descripcion_contenido}</p>
                    </div>
                    <table style="width: 100%; margin-top: 20px; border-top: 2px solid #5cb85c; padding-top: 10px;">
                        <tr>
                            <td style="width: 60%; vertical-align: top; font-size: 11px;">
                                <h3 style="margin: 0 0 10px; font-size: 14px;">💰 Resumen Financiero</h3>
                                <p><strong>Costo Envío:</strong> $${item.costo_total.toLocaleString('es-CO')}</p>
                                <p><strong>Valor a Recaudar:</strong> ${item.valor_recaudo > 0 ? '$' + item.valor_recaudo : 'No aplica'}</p>
                            </td>
                            <td style="width: 40%; text-align: right;">
                                <img src="${qrImageDataUrl}" style="width: 180px; height: 180px;">
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

    // --- DESCARGAR PLANTILLA EXCEL ---
    if (btnDownloadTemplate) {
        btnDownloadTemplate.addEventListener('click', () => {
            try {
                const templateData = [
                    {
                        "Nombre Destinatario": "Ej: María González",
                        "Teléfono": "3001234567",
                        "Dirección Destino": "Calle 100 # 15-20",
                        "Instrucciones": "Dejar en recepción",
                        "Descripción Contenido": "Ropa y accesorios",
                        "Peso (kg)": 2.5,
                        "Largo (cm)": 30,
                        "Ancho (cm)": 20,
                        "Alto (cm)": 10,
                        "Tipo (Normal/Fragil/Urgente)": "Normal",
                        "Valor Recaudo": 50000
                    }
                ];
                const ws = XLSX.utils.json_to_sheet(templateData);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Plantilla");
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
            }
            calcularCostoAutomatico();
        });
    }

    // --- CÁLCULO AUTOMÁTICO DE COSTO ---
    function calcularCostoAutomatico() {
        // Nuevos campos de recargo
        const mismoDiaCheckbox = document.getElementById('envio_mismo_dia');
        const zonaPerifericaCheckbox = document.getElementById('zona_periferica');
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
        let recargoDimensiones = parseInt(recargoDimensionesValue, 10) || 0;
        let recargoAdicionales = recargoMismoDia + recargoZona;
        
        let recargoRecaudo = 0;
        if (tieneRecaudoCheckbox && tieneRecaudoCheckbox.checked) {
            recargoRecaudo = 3000;
        }

        const total = costoBase + recargoRecaudo + recargoAdicionales + recargoDimensiones;

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
        actualizarRecaudoFinal(); // Recalcular siempre
    }

    // Función para actualizar el recaudo final según la selección
    function actualizarRecaudoFinal() {
        const sumarOption = document.querySelector('input[name="sumar_envio_recaudo"]:checked');
        const costoTotal = parseFloat(costoTotalHiddenInput.value) || 0;
        const preview = document.getElementById('preview_total_recaudo');
        
        // Actualizar estilos visuales de las tarjetas
        document.querySelectorAll('.radio-card').forEach(c => c.classList.remove('selected'));
        if (sumarOption) {
            sumarOption.closest('.radio-card').classList.add('selected');
        }

        if (sumarOption && sumarOption.value === 'si') {
            const total = baseRecaudo + costoTotal;
            if (valorRecaudoHidden) valorRecaudoHidden.value = total;
            if (preview) {
                preview.style.display = 'block';
                preview.innerHTML = `Total a cobrar al destinatario: <span style="font-size: 1.2em;">$${total.toLocaleString('es-CO')}</span>`;
            }
        } else if (sumarOption && sumarOption.value === 'no') {
            if (valorRecaudoHidden) valorRecaudoHidden.value = baseRecaudo;
            if (preview) {
                preview.style.display = 'block';
                preview.innerHTML = `Total a cobrar al destinatario: <span style="font-size: 1.2em;">$${baseRecaudo.toLocaleString('es-CO')}</span>`;
            }
        } else {
            if (preview) preview.style.display = 'none';
        }
    }

    // Agregar listeners a los campos que afectan el precio
    const mismoDiaInput = document.getElementById('envio_mismo_dia');
    const zonaPerifericaInput = document.getElementById('zona_periferica');
    const dimensionesInput = document.getElementById('dimensiones_paquete');

    if (mismoDiaInput) mismoDiaInput.addEventListener('change', calcularCostoAutomatico);
    if (zonaPerifericaInput) zonaPerifericaInput.addEventListener('change', calcularCostoAutomatico);
    if (dimensionesInput) dimensionesInput.addEventListener('change', calcularCostoAutomatico);
    
    // Listeners para los radios de sumar envío
    const radiosSumar = document.querySelectorAll('input[name="sumar_envio_recaudo"]');
    radiosSumar.forEach(r => r.addEventListener('change', actualizarRecaudoFinal));

    // Mantener compatibilidad con el botón si existe (aunque lo ocultaremos)
    if (calcularCostoBtn) {
        calcularCostoBtn.addEventListener('click', calcularCostoAutomatico);
    }

    function populateConfirmation() {
        document.getElementById('confirm_remitente_nombre').textContent = document.getElementById('remitente_nombre').value;
        document.getElementById('confirm_remitente_telefono').textContent = document.getElementById('remitente_telefono').value;
        document.getElementById('confirm_remitente_direccion').textContent = document.getElementById('remitente_direccion').value;

        document.getElementById('confirm_destinatario_nombre').textContent = document.getElementById('destinatario_nombre').value;
        document.getElementById('confirm_destinatario_telefono').textContent = document.getElementById('destinatario_telefono').value;
        document.getElementById('confirm_destinatario_direccion').textContent = document.getElementById('destinatario_direccion').value;
        document.getElementById('confirm_destinatario_observaciones').textContent = document.getElementById('instrucciones_entrega').value || 'Sin observaciones';

        document.getElementById('confirm_descripcion').textContent = document.getElementById('descripcion_contenido').value;

        // --- CÁLCULO DE TOTALES A COBRAR ---
        const costoEnvioNum = parseFloat(document.getElementById('costoTotalHidden').value) || 0;
        const baseRecaudoNum = baseRecaudo || 0;
        const sumarOption = document.querySelector('input[name="sumar_envio_recaudo"]:checked');
        const sumar = sumarOption ? sumarOption.value : 'no';
        const tieneRecaudo = document.getElementById('tiene_recaudo').checked;

        let totalCobrar = 0;
        let valorProducto = 0;
        let valorEnvio = 0;

        if (tieneRecaudo) {
            valorProducto = baseRecaudoNum;
            if (sumar === 'si') {
                valorEnvio = costoEnvioNum;
                totalCobrar = valorProducto + valorEnvio;
            } else {
                valorEnvio = 0;
                totalCobrar = valorProducto;
            }
        } else {
            // Si no hay recaudo, asumimos que no se cobra nada al destinatario (0)
            totalCobrar = 0;
            valorProducto = 0;
            valorEnvio = 0;
        }

        document.getElementById('confirm_total_cobrar').textContent = `$${totalCobrar.toLocaleString('es-CO')}`;
        document.getElementById('confirm_valor_producto').textContent = `$${valorProducto.toLocaleString('es-CO')}`;
        document.getElementById('confirm_valor_envio').textContent = `$${valorEnvio.toLocaleString('es-CO')}`;
        
        const date = new Date();
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const random = Math.random().toString(36).substring(2, 7).toUpperCase();
        const numeroGuia = `ECO-${year}${month}${day}-${random}`;
        document.getElementById('numeroGuia').textContent = numeroGuia;
        // Guardar la guía en el input oculto para enviarla al backend
        if (numeroGuiaHiddenInput) {
            numeroGuiaHiddenInput.value = numeroGuia;
        }

        // Lógica para mostrar información financiera en el QR
        let qrFinanciero = '';

        if (sumar === 'si') {
            // Si se suma el envío, mostramos el total unificado a recaudar
            qrFinanciero = `Total a Recaudar: $${totalCobrar.toLocaleString('es-CO')}`;
        } else {
            // Si NO se suma, mostramos por separado
            const textoRecaudo = (valorProducto > 0) ? `$${valorProducto.toLocaleString('es-CO')}` : 'No aplica';
            qrFinanciero = `Costo Envío: $${costoEnvioNum.toLocaleString('es-CO')}
Recaudo: ${textoRecaudo}`;
        }

        // --- QR CODE GENERATION ---
        const infoParaQR = `
Guía: ${numeroGuia}
Remitente: ${document.getElementById('remitente_nombre').value}
Origen: ${document.getElementById('remitente_direccion').value}
Destinatario: ${document.getElementById('destinatario_nombre').value}
Destino: ${document.getElementById('destinatario_direccion').value}
Contenido: ${document.getElementById('descripcion_contenido').value}
${qrFinanciero}
        `.trim();

        // Limpiar contenedor de QR
        qrcodeContainer.innerHTML = '';
        
        // Crear nueva instancia de QR con logo
        qrCodeStylingInstance = new QRCodeStyling({
            width: 300,
            height: 300,
            data: infoParaQR,
            // image: "../../public/img/logo_qr.png", // Desactivado para asegurar que el QR funcione. Actívalo cuando tengas el logo.
            dotsOptions: {
                color: "#000000",
                type: "rounded"
            },
            backgroundOptions: {
                color: "#ffffff",
            },
            imageOptions: {
                crossOrigin: "anonymous",
                margin: 4
            },
            qrOptions: {
                errorCorrectionLevel: 'H' // Nivel alto para que el logo no afecte la lectura
            }
        });

        qrCodeStylingInstance.append(qrcodeContainer);
    }

    // --- PDF DOWNLOAD ---
    if (btnDownloadPDF) {
        btnDownloadPDF.addEventListener('click', async () => {
            try {
                const { jsPDF } = window.jspdf;
                const numeroGuia = document.getElementById('numeroGuia').textContent;
                
                // Lógica para el Resumen Financiero en el PDF
                const costoEnvioNum = parseFloat(document.getElementById('costoTotalHidden').value) || 0;
                const baseRecaudoNum = baseRecaudo || 0;
                const sumarOption = document.querySelector('input[name="sumar_envio_recaudo"]:checked');
                const sumar = sumarOption ? sumarOption.value : 'no';
                const tieneRecaudo = document.getElementById('tiene_recaudo').checked;

                let totalCobrar = 0;
                let valorProducto = 0;
                let valorEnvio = 0;

                if (tieneRecaudo) {
                    valorProducto = baseRecaudoNum;
                    if (sumar === 'si') {
                        valorEnvio = costoEnvioNum;
                        totalCobrar = valorProducto + valorEnvio;
                    } else {
                        valorEnvio = 0;
                        totalCobrar = valorProducto;
                    }
                }

                let htmlResumenFinanciero = '';
                htmlResumenFinanciero = `
                    <p><strong>Total a Cobrar:</strong> $${totalCobrar.toLocaleString('es-CO')}</p>
                    <p><strong>Valor Producto:</strong> $${valorProducto.toLocaleString('es-CO')}</p>
                    <p><strong>Valor Envío:</strong> $${valorEnvio.toLocaleString('es-CO')}</p>
                `;

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
                    <div style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
                        <table style="width: 100%; border-bottom: 2px solid #5cb85c; padding-bottom: 10px;">
                            <tr>
                                <td style="width: 50%;">
                                    <h1 style="font-size: 24px; margin: 0; color: #5cb85c;">🚴 EcoBikeMess</h1>
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
                                    <p><strong>Tienda:</strong> ${window.remitenteData?.nombre_tienda || ''}</p>
                                    <p><strong>Remitente:</strong> ${document.getElementById('remitente_nombre').value}</p>
                                    <p><strong>Teléfono:</strong> ${document.getElementById('remitente_telefono').value}</p>
                                    <p><strong>Dirección:</strong> ${document.getElementById('remitente_direccion').value}</p>
                                </td>
                                <td style="width: 4%;"></td>
                                <td style="width: 48%; vertical-align: top; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                                    <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📥 Destinatario</h3>
                                    <p><strong>Nombre:</strong> ${document.getElementById('destinatario_nombre').value}</p>
                                    <p><strong>Teléfono:</strong> ${document.getElementById('destinatario_telefono').value}</p>
                                    <p><strong>Dirección:</strong> ${document.getElementById('destinatario_direccion').value}</p>
                                    <p><strong>Observaciones:</strong> ${document.getElementById('instrucciones_entrega').value || 'Sin observaciones'}</p>
                                </td>
                            </tr>
                        </table>

                        <div style="margin-top: 20px; border: 1px solid #eee; padding: 10px; border-radius: 8px; font-size: 11px;">
                            <h3 style="margin: 0 0 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">📦 Detalles del Paquete</h3>
                            <p><strong>Descripción:</strong> ${document.getElementById('descripcion_contenido').value}</p>
                        </div>

                        <table style="width: 100%; margin-top: 20px; border-top: 2px solid #5cb85c; padding-top: 10px;">
                            <tr>
                                <td style="width: 60%; vertical-align: top; font-size: 11px;">
                                    <h3 style="margin: 0 0 10px; font-size: 14px;">💰 Resumen Financiero</h3>
                                    ${htmlResumenFinanciero}
                                </td>
                                <td style="width: 40%; text-align: right;">
                                    <img src="${qrImageDataUrl}" style="width: 180px; height: 180px;">
                                </td>
                            </tr>
                        </table>
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