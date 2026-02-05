<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - EcoBikeMess</title>
    <link rel="stylesheet" href="../public/css/crearCuenta.css">
</head>
<body>
    <div class="register-container">
        <!-- Botón para volver -->
        <a href="login.php" class="btn-back">
            <span class="back-arrow">←</span>
            <span>Volver al inicio de sesión</span>
        </a>

        <!-- Header -->
        <div class="register-header">
            <h1>🚴 EcoBikeMess</h1>
            <p>Crea tu cuenta y comienza tu experiencia</p>
        </div>

        <!-- Mensaje de Error PHP -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message" style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; display: block;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO UNIFICADO -->
        <form id="registerForm" class="register-form active" action="../controller/crearCuentaController.php" method="POST" enctype="multipart/form-data">
            
            <!-- 1. DATOS COMUNES (Siempre visibles) -->
            <h2>Información Personal</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nombres">Nombres *</label>
                    <input type="text" id="nombres" name="nombres" required>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="apellidos">Apellidos *</label>
                    <input type="text" id="apellidos" name="apellidos" required>
                    <span class="error-message"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="correo">Correo Electrónico *</label>
                    <input type="email" id="correo" name="correo" required>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono *</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="300 123 4567" required>
                    <span class="error-message"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" data-target="password">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                    <span class="password-strength"></span>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña *</label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="toggle-password" data-target="confirm_password">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                    <span class="error-message"></span>
                </div>
            </div>

            <!-- 2. SELECCIÓN DE TIPO DE USUARIO -->
            <h2>Tipo de Cuenta</h2>
            <div class="user-type-selector">
                <button type="button" class="type-btn active" data-type="cliente">
                    <span class="icon">🛍️</span>
                    <span class="type-title">Soy Cliente</span>
                    <span class="type-desc">Quiero enviar paquetes</span>
                </button>
                <button type="button" class="type-btn" data-type="mensajero">
                    <span class="icon">🚴</span>
                    <span class="type-title">Soy Mensajero</span>
                    <span class="type-desc">Quiero realizar entregas</span>
                </button>
            </div>
            <!-- Input oculto que guarda la selección -->
            <input type="hidden" name="tipo_usuario" id="tipoUsuario" value="cliente">

            <!-- 3. CAMPOS ESPECÍFICOS (Dinámicos) -->
            
            <!-- CAMPOS CLIENTE -->
            <div id="camposCliente">
                <h2>Información del Emprendimiento</h2>
                <div class="form-group">
                    <label for="cliente_nombre_emprendimiento">Nombre del Emprendimiento *</label>
                    <input type="text" id="cliente_nombre_emprendimiento" name="nombre_emprendimiento">
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="cliente_tipo_producto">Tipo de Producto/Servicio *</label>
                    <input type="text" id="cliente_tipo_producto" name="tipo_producto" placeholder="Ej: Ropa, Comida, Accesorios">
                    <span class="error-message"></span>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="cliente_instagram">Instagram (opcional)</label>
                        <input type="text" id="cliente_instagram" name="instagram" placeholder="@tuemprendimiento">
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="cliente_direccion">Dirección Principal *</label>
                        <input type="text" id="cliente_direccion" name="direccion_principal" placeholder="Calle 123 #45-67">
                        <span class="error-message"></span>
                    </div>
                </div>
            </div>

            <!-- CAMPOS MENSAJERO (Ocultos por defecto) -->
            <div id="camposMensajero" style="display: none;">
                <h2>Documentación</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="mensajero_tipo_documento">Tipo de Documento *</label>
                        <select id="mensajero_tipo_documento" name="tipo_documento">
                            <option value="">Seleccionar...</option>
                            <option value="cedula">Cédula de Ciudadanía</option>
                            <option value="tarjeta_identidad">Tarjeta de Identidad</option>
                            <option value="cedula_extranjeria">Cédula de Extranjería</option>
                            <option value="pasaporte">Pasaporte</option>
                        </select>
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="mensajero_numDocumento">Número de Documento *</label>
                        <input type="text" id="mensajero_numDocumento" name="numDocumento">
                        <span class="error-message"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mensajero_tipo_sangre">Tipo de Sangre *</label>
                        <select id="mensajero_tipo_sangre" name="tipo_sangre">
                            <option value="">Seleccionar...</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="mensajero_direccion">Dirección de Residencia *</label>
                        <input type="text" id="mensajero_direccion" name="direccion_residencia">
                        <span class="error-message"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mensajero_foto">Foto Personal *</label>
                        <input type="file" id="mensajero_foto" name="foto" accept="image/*">
                        <small class="file-info">Formato: JPG, PNG (máx. 2MB)</small>
                        <div id="foto-preview-container" class="foto-preview-container" style="display: none;">
                            <img id="foto-preview-img" src="#" alt="Previsualización de foto">
                        </div>
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="mensajero_hoja_vida">Hoja de Vida (PDF) *</label>
                        <input type="file" id="mensajero_hoja_vida" name="hoja_vida" accept=".pdf">
                        <small class="file-info">Formato: PDF (máx. 5MB)</small>
                        <span class="error-message"></span>
                    </div>
                </div>

                <h2>Contactos de Emergencia</h2>
                <h3>Contacto 1</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="mensajero_nombre_emergencia1">Nombre *</label>
                        <input type="text" id="mensajero_nombre_emergencia1" name="nombre_emergencia1">
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="mensajero_apellido_emergencia1">Apellido *</label>
                        <input type="text" id="mensajero_apellido_emergencia1" name="apellido_emergencia1">
                        <span class="error-message"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="mensajero_telefono_emergencia1">Teléfono *</label>
                    <input type="tel" id="mensajero_telefono_emergencia1" name="telefono_emergencia1" placeholder="300 123 4567">
                    <span class="error-message"></span>
                </div>

                <h3>Contacto 2</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="mensajero_nombre_emergencia2">Nombre *</label>
                        <input type="text" id="mensajero_nombre_emergencia2" name="nombre_emergencia2">
                        <span class="error-message"></span>
                    </div>
                    <div class="form-group">
                        <label for="mensajero_apellido_emergencia2">Apellido *</label>
                        <input type="text" id="mensajero_apellido_emergencia2" name="apellido_emergencia2">
                        <span class="error-message"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="mensajero_telefono_emergencia2">Teléfono *</label>
                    <input type="tel" id="mensajero_telefono_emergencia2" name="telefono_emergencia2" placeholder="300 123 4567">
                    <span class="error-message"></span>
                </div>

                <h2>Información de Transporte</h2>
                <div class="form-group">
                    <label for="mensajero_tipo_transporte">Tipo de Transporte *</label>
                    <select id="mensajero_tipo_transporte" name="tipo_transporte">
                        <option value="">Seleccionar...</option>
                        <option value="bicicleta">Bicicleta</option>
                        <option value="moto">Motocicleta</option>
                        <option value="vehiculo">Vehículo</option>
                        <option value="a_pie">A pie</option>
                    </select>
                    <span class="error-message"></span>
                </div>

                <div id="vehiculoFields" class="vehiculo-fields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="mensajero_placa">Placa del Vehículo *</label>
                            <input type="text" id="mensajero_placa" name="placa_vehiculo" placeholder="ABC123">
                            <span class="error-message"></span>
                        </div>
                        <div class="form-group">
                            <label for="mensajero_licencia">Licencia de Conducir *</label>
                            <input type="file" id="mensajero_licencia" name="licencia_conducir" accept=".pdf,image/*">
                            <small class="file-info">PDF o imagen (máx. 2MB)</small>
                            <span class="error-message"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mensajero_soat">SOAT *</label>
                        <input type="file" id="mensajero_soat" name="soat" accept=".pdf,image/*">
                        <small class="file-info">PDF o imagen (máx. 2MB)</small>
                        <span class="error-message"></span>
                    </div>
                </div>
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="terms" required>
                    <span>Acepto los <a href="#" class="link">términos y condiciones</a> y la <a href="#" class="link">política de privacidad</a></span>
                </label>
            </div>

            <button type="submit" class="btn-submit">Crear Cuenta</button>
        </form>

        <!-- Footer -->
        <div class="register-footer">
            <p>¿Ya tienes una cuenta? <a href="login.php" class="link">Iniciar sesión</a></p>
        </div>
    </div>

    <script src="../public/js/crearCuenta.js"></script>
</body>
</html>