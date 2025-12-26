CREATE DATABASE IF NOT EXISTS ecobikemess;
USE ecobikemess;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('cliente', 'mensajero', 'administrador') NOT NULL,
    estado ENUM('activo', 'inactivo', 'pendiente_aprobacion', 'bloqueado') DEFAULT 'activo',
    correo_verificado BOOLEAN DEFAULT FALSE,
    codigo_verificacion VARCHAR(10),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    intentos_fallidos INT DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT UNIQUE NOT NULL,
    nombre_emprendimiento VARCHAR(150) NOT NULL,
    tipo_producto VARCHAR(200) NOT NULL,
    instagram VARCHAR(100),
    direccion_principal TEXT,
    saldo_pendiente DECIMAL(10,2) DEFAULT 0.00,
    limite_credito DECIMAL(10,2) DEFAULT 0.00,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mensajeros (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT UNIQUE NOT NULL,
    tipo_documento ENUM('cedula', 'tarjeta_identidad', 'pasaporte', 'cedula_extranjeria') NOT NULL,
    numDocumento VARCHAR(20) UNIQUE NOT NULL,
    foto VARCHAR(255) NOT NULL,
    hoja_vida VARCHAR(255) NOT NULL,
    telefono_emergencia1 VARCHAR(20) NOT NULL,
    nombre_emergencia1 VARCHAR(200) NOT NULL,
    apellido_emergencia1 VARCHAR(200) NOT NULL,
    telefono_emergencia2 VARCHAR(20) NOT NULL,
    nombre_emergencia2 VARCHAR(200) NOT NULL,
    apellido_emergencia2 VARCHAR(200) NOT NULL,
    tipo_sangre ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    direccion_residencia TEXT NOT NULL,
    tipo_transporte ENUM('moto', 'bicicleta', 'a_pie', 'vehiculo') NOT NULL,
    placa_vehiculo VARCHAR(10),
    licencia_conducir VARCHAR(100),
    soat VARCHAR(100),
    estado_aprobacion ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    calificacion_promedio DECIMAL(3,2) DEFAULT 0.00,
    total_entregas INT DEFAULT 0,
    ubicacion_actual_lat DECIMAL(10, 8),
    ubicacion_actual_lng DECIMAL(11, 8),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS administradores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT UNIQUE NOT NULL,
    tipo_documento ENUM('cedula', 'tarjeta_identidad', 'pasaporte', 'cedula_extranjeria') NOT NULL,
    num_documento VARCHAR(20) UNIQUE NOT NULL,
    rol ENUM('super_admin', 'admin_operativo', 'admin_reportes', 'admin_mensajeros') NOT NULL,
    foto VARCHAR(255) NOT NULL,
    permisos_especiales JSON,
    creado_por INT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES administradores(id)
);

CREATE TABLE IF NOT EXISTS paquetes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_guia VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    remitente_nombre VARCHAR(200) NOT NULL,
    remitente_telefono VARCHAR(20) NOT NULL,
    remitente_correo VARCHAR(150),
    direccion_origen TEXT NOT NULL,
    destinatario_nombre VARCHAR(200) NOT NULL,
    destinatario_telefono VARCHAR(20) NOT NULL,
    direccion_destino TEXT NOT NULL,
    coordenadas_destino_lat DECIMAL(10, 8),
    coordenadas_destino_lng DECIMAL(11, 8),
    instrucciones_entrega TEXT,
    descripcion_contenido TEXT NOT NULL,
    peso DECIMAL(8,2),
    largo DECIMAL(8,2),
    ancho DECIMAL(8,2),
    alto DECIMAL(8,2),
    tipo_paquete ENUM('normal', 'fragil', 'urgente', 'express') DEFAULT 'normal',
    valor_declarado DECIMAL(10,2) DEFAULT 0.00,
    tipo_servicio ENUM('entrega_simple', 'contraentrega') DEFAULT 'entrega_simple',
    costo_envio DECIMAL(10,2) NOT NULL,
    recaudo_esperado DECIMAL(10,2) DEFAULT 0.00,
    estado ENUM('pendiente', 'asignado', 'en_transito', 'entregado', 'devuelto', 'cancelado') DEFAULT 'pendiente',
    mensajero_id INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion TIMESTAMP NULL,
    fecha_entrega TIMESTAMP NULL,
    qr_code VARCHAR(255) UNIQUE,
    escaneado BOOLEAN DEFAULT FALSE,
    fecha_escaneo TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    INDEX idx_numero_guia (numero_guia),
    INDEX idx_estado (estado),
    INDEX idx_fecha_creacion (fecha_creacion)
);

CREATE TABLE IF NOT EXISTS entregas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paquete_id INT UNIQUE NOT NULL,
    mensajero_id INT NOT NULL,
    nombre_receptor VARCHAR(200) NOT NULL,
    parentesco_cargo VARCHAR(100),
    documento_receptor VARCHAR(50),
    recaudo_real DECIMAL(10,2) DEFAULT 0.00,
    coordenadas_entrega_lat DECIMAL(10, 8),
    coordenadas_entrega_lng DECIMAL(11, 8),
    fecha_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    foto_entrega VARCHAR(255) NOT NULL,
    foto_adicional VARCHAR(255),
    observaciones TEXT,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id)
);

CREATE TABLE IF NOT EXISTS recolecciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_orden VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    mensajero_id INT,
    direccion_recoleccion TEXT NOT NULL,
    coordenadas_lat DECIMAL(10, 8),
    coordenadas_lng DECIMAL(11, 8),
    nombre_contacto VARCHAR(200) NOT NULL,
    telefono_contacto VARCHAR(20) NOT NULL,
    descripcion_paquetes TEXT,
    cantidad_estimada INT DEFAULT 1,
    cantidad_real INT,
    horario_preferido TIME,
    prioridad ENUM('normal', 'urgente', 'programada') DEFAULT 'normal',
    estado ENUM('asignada', 'en_curso', 'completada', 'cancelada') DEFAULT 'asignada',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion TIMESTAMP NULL,
    fecha_completada TIMESTAMP NULL,
    foto_recoleccion VARCHAR(255),
    observaciones_recoleccion TEXT,
    conformidad BOOLEAN DEFAULT TRUE,
    justificacion_cancelacion TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id)
);

CREATE TABLE IF NOT EXISTS comprobantes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paquete_id INT UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    numero_comprobante VARCHAR(20) UNIQUE NOT NULL,
    numero_guia VARCHAR(20) NOT NULL,
    nombre_receptor VARCHAR(200) NOT NULL,
    parentesco_cargo VARCHAR(100),
    recaudo DECIMAL(10,2) DEFAULT 0.00,
    observaciones TEXT,
    foto_entrega VARCHAR(255) NOT NULL,
    archivo_pdf VARCHAR(255),
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notificado BOOLEAN DEFAULT FALSE,
    fecha_notificacion TIMESTAMP NULL,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE IF NOT EXISTS facturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    numero_factura VARCHAR(20) UNIQUE NOT NULL,
    periodo_inicio DATE NOT NULL,
    periodo_fin DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'pagada', 'vencida', 'anulada') DEFAULT 'pendiente',
    fecha_vencimiento DATE NOT NULL,
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_pago TIMESTAMP NULL,
    archivo_pdf VARCHAR(255),
    observaciones TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE IF NOT EXISTS detalle_facturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    factura_id INT NOT NULL,
    paquete_id INT NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    cantidad INT DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (paquete_id) REFERENCES paquetes(id)
);

CREATE TABLE IF NOT EXISTS pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    factura_id INT NOT NULL,
    cliente_id INT NOT NULL,
    numero_recibo VARCHAR(20) UNIQUE NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('transferencia', 'consignacion', 'efectivo', 'otros') NOT NULL,
    fecha_pago DATE NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    comprobante_pago VARCHAR(255),
    observaciones TEXT,
    registrado_por INT,
    FOREIGN KEY (factura_id) REFERENCES facturas(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (registrado_por) REFERENCES administradores(id)
);

CREATE TABLE IF NOT EXISTS sesiones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NULL,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    datos_adicionales JSON,
    leida BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS logs_actividad (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    tipo_actividad VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    datos_adicionales JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS configuraciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    categoria VARCHAR(50),
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS zonas_cobertura (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    coordenadas_poligono JSON,
    tarifa_base DECIMAL(8,2) NOT NULL,
    tarifa_contraentrega DECIMAL(8,2) NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_usuarios_email ON usuarios(correo);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);
CREATE INDEX idx_paquetes_cliente_fecha ON paquetes(cliente_id, fecha_creacion);
CREATE INDEX idx_paquetes_mensajero_estado ON paquetes(mensajero_id, estado);
CREATE INDEX idx_entregas_fecha ON entregas(fecha_entrega);
CREATE INDEX idx_recolecciones_mensajero_estado ON recolecciones(mensajero_id, estado);
CREATE INDEX idx_facturas_cliente_estado ON facturas(cliente_id, estado);
CREATE INDEX idx_notificaciones_usuario_leida ON notificaciones(usuario_id, leida);
CREATE INDEX idx_paquetes_fecha_estado ON paquetes(fecha_creacion, estado);
CREATE INDEX idx_entregas_mensajero_fecha ON entregas(mensajero_id, fecha_entrega);

-- INSERTS

INSERT INTO usuarios (nombres, apellidos, correo, telefono, password, tipo_usuario, estado, correo_verificado)
VALUES ('Brayan', 'Rodriguez Gomez', 'brayan@gmail.com', '3001234567', '123456789', 'cliente', 'activo', TRUE);

INSERT INTO usuarios (nombres, apellidos, correo, telefono, password, tipo_usuario, estado, correo_verificado)
VALUES ('Eco', 'Bike Admin', 'eco@gmail.com', '3109876543', '123456789', 'administrador', 'activo', TRUE);

INSERT INTO usuarios (nombres, apellidos, correo, telefono, password, tipo_usuario, estado, correo_verificado)
VALUES ('Marlon', 'Castro Perez', 'marlon@gmail.com', '3205551234', '123456789', 'mensajero', 'activo', TRUE);

INSERT INTO clientes (usuario_id, nombre_emprendimiento, tipo_producto, instagram, direccion_principal, saldo_pendiente, limite_credito)
VALUES (1, 'Brayan Store', 'Ropa y accesorios deportivos', '@brayanstore', 'Calle 45 #23-67, Bogota', 0.00, 500000.00);

INSERT INTO administradores (usuario_id, tipo_documento, num_documento, rol, foto, permisos_especiales)
VALUES (2, 'cedula', '1234567890', 'super_admin', '/uploads/admins/eco_foto.jpg', '{"permisos": ["todos"]}');

INSERT INTO mensajeros (usuario_id, tipo_documento, numDocumento, foto, hoja_vida, telefono_emergencia1, nombre_emergencia1, apellido_emergencia1, telefono_emergencia2, nombre_emergencia2, apellido_emergencia2, tipo_sangre, direccion_residencia, tipo_transporte, estado_aprobacion, calificacion_promedio, total_entregas)
VALUES (3, 'cedula', '9876543210', '/uploads/mensajeros/marlon_foto.jpg', '/uploads/mensajeros/marlon_hv.pdf', '3001112233', 'Maria', 'Castro Lopez', '3104445566', 'Pedro', 'Castro Ramirez', 'O+', 'Carrera 15 #78-90, Bogota', 'bicicleta', 'aprobado', 4.85, 0);

INSERT INTO paquetes (numero_guia, cliente_id, remitente_nombre, remitente_telefono, remitente_correo, direccion_origen, destinatario_nombre, destinatario_telefono, direccion_destino, coordenadas_destino_lat, coordenadas_destino_lng, instrucciones_entrega, descripcion_contenido, peso, largo, ancho, alto, tipo_paquete, valor_declarado, tipo_servicio, costo_envio, recaudo_esperado, estado, mensajero_id, fecha_asignacion, fecha_entrega, qr_code, escaneado, fecha_escaneo)
VALUES ('ECO-2024-001', 1, 'Brayan Rodriguez', '3001234567', 'brayan@gmail.com', 'Calle 45 #23-67, Bogota', 'Ana Maria Lopez', '3156789012', 'Carrera 7 #100-25, Bogota', 4.701954, -74.035599, 'Dejar con porteria si no esta', 'Camiseta deportiva talla M', 0.25, 30.00, 25.00, 5.00, 'normal', 50000.00, 'contraentrega', 8000.00, 75000.00, 'entregado', 1, '2024-12-20 08:30:00', '2024-12-20 14:45:00', 'QR-ECO-001', TRUE, '2024-12-20 08:35:00');

INSERT INTO paquetes (numero_guia, cliente_id, remitente_nombre, remitente_telefono, remitente_correo, direccion_origen, destinatario_nombre, destinatario_telefono, direccion_destino, coordenadas_destino_lat, coordenadas_destino_lng, instrucciones_entrega, descripcion_contenido, peso, largo, ancho, alto, tipo_paquete, valor_declarado, tipo_servicio, costo_envio, estado, mensajero_id, fecha_asignacion, qr_code, escaneado, fecha_escaneo)
VALUES ('ECO-2024-002', 1, 'Brayan Store', '3001234567', 'brayan@gmail.com', 'Calle 45 #23-67, Bogota', 'Carlos Mendoza', '3187654321', 'Calle 127 #15-40, Bogota', 4.722445, -74.045732, 'Llamar al llegar', 'Zapatillas deportivas talla 42', 0.80, 35.00, 25.00, 15.00, 'express', 120000.00, 'entrega_simple', 12000.00, 'en_transito', 1, '2024-12-25 09:15:00', 'QR-ECO-002', TRUE, '2024-12-25 09:20:00');

INSERT INTO paquetes (numero_guia, cliente_id, remitente_nombre, remitente_telefono, remitente_correo, direccion_origen, destinatario_nombre, destinatario_telefono, direccion_destino, instrucciones_entrega, descripcion_contenido, peso, tipo_paquete, valor_declarado, tipo_servicio, costo_envio, recaudo_esperado, estado, qr_code)
VALUES ('ECO-2024-003', 1, 'Brayan Store', '3001234567', 'brayan@gmail.com', 'Calle 45 #23-67, Bogota', 'Laura Sanchez', '3209876543', 'Avenida 68 #45-23, Bogota', 'Entregar en horario de oficina', 'Conjunto deportivo completo', 0.60, 'urgente', 85000.00, 'contraentrega', 10000.00, 95000.00, 'pendiente', 'QR-ECO-003');

INSERT INTO paquetes (numero_guia, cliente_id, remitente_nombre, remitente_telefono, direccion_origen, destinatario_nombre, destinatario_telefono, direccion_destino, descripcion_contenido, peso, tipo_paquete, valor_declarado, tipo_servicio, costo_envio, estado, mensajero_id, fecha_asignacion, qr_code)
VALUES ('ECO-2024-004', 1, 'Brayan Store', '3001234567', 'Calle 45 #23-67, Bogota', 'Miguel Torres', '3123456789', 'Calle 80 #10-20, Bogota', 'Gorra deportiva', 0.15, 'normal', 30000.00, 'entrega_simple', 7000.00, 'asignado', 1, '2024-12-25 10:00:00', 'QR-ECO-004');

INSERT INTO entregas (paquete_id, mensajero_id, nombre_receptor, parentesco_cargo, documento_receptor, recaudo_real, coordenadas_entrega_lat, coordenadas_entrega_lng, foto_entrega, foto_adicional, observaciones)
VALUES (1, 1, 'Ana Maria Lopez', 'Titular', '52123456', 75000.00, 4.701954, -74.035599, '/uploads/entregas/entrega_001_principal.jpg', '/uploads/entregas/entrega_001_adicional.jpg', 'Entrega exitosa. Cliente satisfecho con el producto.');

INSERT INTO recolecciones (numero_orden, cliente_id, mensajero_id, direccion_recoleccion, coordenadas_lat, coordenadas_lng, nombre_contacto, telefono_contacto, descripcion_paquetes, cantidad_estimada, cantidad_real, horario_preferido, prioridad, estado, fecha_asignacion, fecha_completada, foto_recoleccion, observaciones_recoleccion, conformidad)
VALUES ('REC-2024-001', 1, 1, 'Calle 45 #23-67, Bogota', 4.672855, -74.055374, 'Brayan Rodriguez', '3001234567', 'Paquetes de ropa deportiva para envio', 5, 5, '14:00:00', 'programada', 'completada', '2024-12-19 10:00:00', '2024-12-19 14:30:00', '/uploads/recolecciones/rec_001.jpg', 'Recoleccion exitosa. 5 paquetes recogidos.', TRUE);

INSERT INTO recolecciones (numero_orden, cliente_id, mensajero_id, direccion_recoleccion, nombre_contacto, telefono_contacto, descripcion_paquetes, cantidad_estimada, horario_preferido, prioridad, estado, fecha_asignacion)
VALUES ('REC-2024-002', 1, 1, 'Calle 45 #23-67, Bogota', 'Brayan Rodriguez', '3001234567', 'Nueva remesa de productos', 3, '16:00:00', 'normal', 'en_curso', '2024-12-25 11:00:00');

INSERT INTO comprobantes (paquete_id, cliente_id, numero_comprobante, numero_guia, nombre_receptor, parentesco_cargo, recaudo, observaciones, foto_entrega, archivo_pdf, notificado, fecha_notificacion)
VALUES (1, 1, 'COMP-2024-001', 'ECO-2024-001', 'Ana Maria Lopez', 'Titular', 75000.00, 'Entrega exitosa', '/uploads/entregas/entrega_001_principal.jpg', '/uploads/comprobantes/comp_001.pdf', TRUE, '2024-12-20 15:00:00');

INSERT INTO facturas (cliente_id, numero_factura, periodo_inicio, periodo_fin, subtotal, impuestos, total, estado, fecha_vencimiento, archivo_pdf)
VALUES (1, 'FACT-2024-001', '2024-12-01', '2024-12-20', 45000.00, 8550.00, 53550.00, 'pagada', '2024-12-30', '/uploads/facturas/fact_001.pdf');

INSERT INTO facturas (cliente_id, numero_factura, periodo_inicio, periodo_fin, subtotal, impuestos, total, estado, fecha_vencimiento)
VALUES (1, 'FACT-2024-002', '2024-12-21', '2024-12-31', 19000.00, 3610.00, 22610.00, 'pendiente', '2025-01-10');

INSERT INTO detalle_facturas (factura_id, paquete_id, descripcion, costo_unitario, cantidad, subtotal)
VALUES (1, 1, 'Envio contraentrega ECO-2024-001', 8000.00, 1, 8000.00);

INSERT INTO detalle_facturas (factura_id, paquete_id, descripcion, costo_unitario, cantidad, subtotal)
VALUES (1, 2, 'Envio express ECO-2024-002', 12000.00, 1, 12000.00);

INSERT INTO detalle_facturas (factura_id, paquete_id, descripcion, costo_unitario, cantidad, subtotal)
VALUES (2, 3, 'Envio urgente contraentrega ECO-2024-003', 10000.00, 1, 10000.00);

INSERT INTO detalle_facturas (factura_id, paquete_id, descripcion, costo_unitario, cantidad, subtotal)
VALUES (2, 4, 'Envio simple ECO-2024-004', 7000.00, 1, 7000.00);

INSERT INTO pagos (factura_id, cliente_id, numero_recibo, monto, metodo_pago, fecha_pago, comprobante_pago, observaciones, registrado_por)
VALUES (1, 1, 'REC-2024-001', 53550.00, 'transferencia', '2024-12-21', '/uploads/pagos/pago_001.jpg', 'Pago completo por transferencia bancaria', 1);

INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, fecha_expiracion, activa)
VALUES (1, 'token_brayan_123abc', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_ADD(NOW(), INTERVAL 7 DAY), TRUE);

INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, fecha_expiracion, activa)
VALUES (2, 'token_eco_456def', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', DATE_ADD(NOW(), INTERVAL 7 DAY), TRUE);

INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, fecha_expiracion, activa)
VALUES (3, 'token_marlon_789ghi', '192.168.1.102', 'Mozilla/5.0 (Linux; Android 11)', DATE_ADD(NOW(), INTERVAL 7 DAY), TRUE);

INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales, leida)
VALUES (1, 'entrega_exitosa', 'Paquete entregado', 'Tu paquete ECO-2024-001 ha sido entregado exitosamente', '{"paquete_id": 1, "numero_guia": "ECO-2024-001"}', TRUE);

INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales, leida)
VALUES (1, 'paquete_en_transito', 'Paquete en camino', 'Tu paquete ECO-2024-002 esta en transito', '{"paquete_id": 2, "numero_guia": "ECO-2024-002"}', FALSE);

INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales, leida)
VALUES (3, 'nueva_asignacion', 'Nuevo paquete asignado', 'Se te ha asignado el paquete ECO-2024-004', '{"paquete_id": 4, "numero_guia": "ECO-2024-004"}', FALSE);

INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales, leida)
VALUES (2, 'nuevo_pago', 'Pago recibido', 'El cliente Brayan Store ha realizado un pago de $53,550', '{"pago_id": 1, "cliente_id": 1, "monto": 53550.00}', FALSE);

INSERT INTO logs_actividad (usuario_id, tipo_actividad, descripcion, datos_adicionales, ip_address)
VALUES (1, 'login', 'Inicio de sesion exitoso', '{"metodo": "email_password"}', '192.168.1.100');

INSERT INTO logs_actividad (usuario_id, tipo_actividad, descripcion, datos_adicionales, ip_address)
VALUES (1, 'crear_paquete', 'Creacion de nuevo paquete', '{"numero_guia": "ECO-2024-003", "tipo": "urgente"}', '192.168.1.100');

INSERT INTO logs_actividad (usuario_id, tipo_actividad, descripcion, datos_adicionales, ip_address)
VALUES (3, 'entrega_completada', 'Entrega de paquete completada', '{"paquete_id": 1, "numero_guia": "ECO-2024-001", "recaudo": 75000.00}', '192.168.1.102');

INSERT INTO logs_actividad (usuario_id, tipo_actividad, descripcion, datos_adicionales, ip_address)
VALUES (2, 'registrar_pago', 'Registro de pago de cliente', '{"pago_id": 1, "monto": 53550.00, "cliente_id": 1}', '192.168.1.101');

INSERT INTO configuraciones (clave, valor, descripcion, tipo, categoria)
VALUES ('tarifa_base_urbana', '5000', 'Tarifa base para envios urbanos en Bogota', 'number', 'tarifas');

INSERT INTO configuraciones (clave, valor, descripcion, tipo, categoria)
VALUES ('tarifa_contraentrega', '1500', 'Costo adicional para envios contraentrega', 'number', 'tarifas');