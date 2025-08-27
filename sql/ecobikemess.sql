CREATE DATABASE IF NOT EXISTS ecobikemess;
use ecobikemess;

CREATE TABLE IF NOT EXISTS administradores (id INT AUTO_INCREMENT PRIMARY KEY,
                                    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
                                    cedula VARCHAR(20) NOT NULL,
                                    nombre VARCHAR(200) NOT NULL,
                                    correo VARCHAR(200) UNIQUE NOT NULL,
                                    telefono VARCHAR(15) NOT NULL,
                                    password VARCHAR(255) NOT NULL,
                                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    solicitarContraseña ENUM('0','1') DEFAULT '0',
                                    tokenPassword varchar (100) ,
                                    sesionCaducada ENUM('1','0') DEFAULT '1'
)ENGINE=INNODB;


CREATE TABLE IF NOT EXISTS clientes (id INT AUTO_INCREMENT PRIMARY KEY,
                                    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
                                    numDocumento VARCHAR(20) NOT NULL,
                                    nombre_emprendimiento VARCHAR(200) NOT NULL,
                                    tipo_producto VARCHAR(200) NOT NULL,
                                    cuenta_bancaria VARCHAR (300), 
                                    nombre VARCHAR(200) NOT NULL,
                                    correo VARCHAR(200) UNIQUE NOT NULL,
                                    telefono VARCHAR(15) NOT NULL,
                                    instagram VARCHAR(100) NOT NULL,
                                    password VARCHAR(255) NOT NULL,
                                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    solicitarContraseña ENUM('0','1') DEFAULT '0',
                                    tokenPassword varchar (100) ,
                                    sesionCaducada ENUM('1','0') DEFAULT '1'
)ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS mensajeros (id INT AUTO_INCREMENT PRIMARY KEY,
                                    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
                                    numero_documento VARCHAR(20) NOT NULL,
                                    nombres VARCHAR(200) NOT NULL,
                                    apellidos VARCHAR (200) NOT NULL,
                                    telefono VARCHAR(15) NOT NULL,
                                    correo VARCHAR(200) UNIQUE NOT NULL,
                                    password VARCHAR(255) NOT NULL,
                                    tipo_sangre VARCHAR (11) NOT NULL,
                                    direccion_residencia VARCHAR (200) NOT NULL,
                                    foto VARCHAR (300) NOT NULL,
                                    hoja_vida VARCHAR (300) NOT NULL,
                                    telefono_emergencia1 VARCHAR (15) NOT NULL,
                                    nombre_emergencia1 VARCHAR (200) NOT NULL,
                                    apellido_emergencia1 VARCHAR (200) NOT NULL,
                                    telefono_emergencia2 VARCHAR (15) NOT NULL,
                                    nombre_emergencia2 VARCHAR (200) NOT NULL,
                                    apellido_emergencia2 VARCHAR (200) NOT NULL,
                                    tipo_vehiculo ENUM('bicicleta', 'motocicleta', 'vehiculo') NOT NULL,
                                    numero_vehiculo VARCHAR(20) NOT NULL,  -- Placa o número de serie
                                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    solicitarContraseña ENUM('0','1') DEFAULT '0',
                                    tokenPassword varchar (100) ,
                                    sesionCaducada ENUM('1','0') DEFAULT '1'
)ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS pedidos (id INT AUTO_INCREMENT PRIMARY KEY,
                                    cliente_id INT NOT NULL,
                                    codigo_qr VARCHAR(50) UNIQUE NOT NULL,  -- Ej: "DELIV-ABC123"
                                    direccion_origen VARCHAR(255) NOT NULL,
                                    nombre_origen VARCHAR(200) NOT NULL,
                                    telefono_origen VARCHAR(15) NOT NULL,
                                    direccion_destino VARCHAR(255) NOT NULL,
                                    destinatario VARCHAR(100) NOT NULL,
                                    telefono_destinatario VARCHAR(15) NOT NULL,
                                    descripcion_paquete TEXT,
                                    instrucciones_entrega TEXT,
                                    estado ENUM('pendiente','asignado','en_camino','entregado','fallido','cancelado') DEFAULT 'pendiente',
                                    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
)ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS asignaciones (id INT AUTO_INCREMENT PRIMARY KEY,
                                    pedido_id INT NOT NULL,
                                    mensajero_id INT NOT NULL,
                                    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
                                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
                                    UNIQUE (pedido_id)  -- Evita asignar un pedido a múltiples mensajeros
)ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS entregas (id INT AUTO_INCREMENT PRIMARY KEY,
                                    pedido_id INT NOT NULL,
                                    mensajero_id INT NOT NULL,
                                    foto_url VARCHAR(255) NOT NULL,  -- URL de Firebase/Cloud Storage
                                    nombre_receptor VARCHAR(100) NOT NULL,  -- Quién recibió
                                    id_receptor VARCHAR(20),  -- Cédula/DNI (opcional)
                                    monto_cobrado DECIMAL(10, 2) DEFAULT 0.00,  -- Si aplica pago en destino
                                    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'no_aplica') DEFAULT 'no_aplica',
                                    observacion TEXT,  -- Notas adicionales
                                    fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
                                    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id)
)ENGINE=INNODB;



INSERT INTO administradores (tipo_documento, cedula, nombre, correo, telefono, password, solicitarContraseña, sesionCaducada) VALUES
('cedula', '1014596349', 'Brayan Pulido Lopez', 'brayan06.pulido@gmail.com', '+573172509298', '123456789', '0', '0'),
('cedula', '9876543210', 'Ana María González', 'ana.gonzalez@ecobikemess.com', '+573009876543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0', '0'),
('cedula', '1122334455', 'Luis Fernando Martínez', 'luis.martinez@ecobikemess.com', '+573001122334', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1', '1');

-- =============================================
-- TABLA CLIENTES
-- =============================================
INSERT INTO clientes (tipo_documento, numDocumento, nombre_emprendimiento, tipo_producto, cuenta_bancaria, nombre, correo, telefono, instagram, password, solicitarContraseña, sesionCaducada) VALUES
('cedula', '1014596349', 'ecobikemess', 'paquetes', '123456789-001-Bancolombia', 'Felipe Pulido Lopez', 'brayanpulido941@gmail.com', '+573172509298', '@ecobikemess', '123456789', '0', '0'),
('ruc', '9000111222333', 'Panadería El Trigal', 'Panadería y repostería', '987654321-002-Davivienda', 'Jorge Andrés Silva', 'panaderia.trigal@outlook.com', '+573209876543', '@panaderia_eltrigal', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0', '0'),
('cedula', '5555666677', 'Café Artesano', 'Café y bebidas', '111222333-003-Nequi', 'Sandra Patricia Ruiz', 'cafe.artesano@gmail.com', '+573155556666', '@cafe_artesano_bog', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0', '0'),
('cedula', '7777888899', 'Frutas y Verduras Don Pedro', 'Frutas y verduras', '444555666-004-Bancolombia', 'Pedro Antonio López', 'donpedro.frutas@hotmail.com', '+573177778888', '@frutas_donpedro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1', '1'),
('cedula', '3333444455', 'Postres Divinos', 'Repostería', '777888999-005-Davivienda', 'Laura Cristina Morales', 'postres.divinos@gmail.com', '+573133334444', '@postres_divinos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0', '0');

-- =============================================
-- TABLA MENSAJEROS
-- =============================================
INSERT INTO mensajeros (tipo_documento, numero_documento, nombres, apellidos, telefono, correo, password, tipo_sangre, direccion_residencia, foto, hoja_vida, telefono_emergencia1, nombre_emergencia1, apellido_emergencia1, telefono_emergencia2, nombre_emergencia2, apellido_emergencia2, tipo_vehiculo, numero_vehiculo, solicitarContraseña, sesionCaducada) VALUES
('cedula', '2222333344', 'Juan Carlos', 'Ramírez Torres', '+573112223333', 'juan.ramirez.mensajero@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'O+', 'Calle 45 #12-34, Bogotá', 'https://storage.cloud.com/fotos/juan_ramirez.jpg', 'https://storage.cloud.com/cv/juan_ramirez_cv.pdf', '+573188887777', 'Carmen Elena', 'Ramírez Vega', '+573166665555', 'Miguel Angel', 'Torres Gómez', 'bicicleta', 'ECO-BIKE-001', '0', '0'),
('cedula', '4444555566', 'Andrea Paola', 'García Mendoza', '+573144445555', 'andrea.garcia.eco@hotmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'A+', 'Carrera 20 #78-90, Bogotá', 'https://storage.cloud.com/fotos/andrea_garcia.jpg', 'https://storage.cloud.com/cv/andrea_garcia_cv.pdf', '+573199998888', 'Rosa María', 'García López', '+573177776666', 'Fernando', 'Mendoza Castro', 'bicicleta', 'ECO-BIKE-002', '0', '0'),
('cedula', '6666777788', 'David Alejandro', 'Herrera Castillo', '+573166667777', 'david.herrera.delivery@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'B+', 'Calle 67 #89-12, Bogotá', 'https://storage.cloud.com/fotos/david_herrera.jpg', 'https://storage.cloud.com/cv/david_herrera_cv.pdf', '+573122221111', 'Lucía', 'Herrera Díaz', '+573155554444', 'Roberto', 'Castillo Pérez', 'motocicleta', 'ABC-123', '0', '0'),
('cedula', '8888999900', 'Camila Andrea', 'Ospina Vargas', '+573188889999', 'camila.ospina.bike@outlook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'AB+', 'Carrera 15 #23-45, Bogotá', 'https://storage.cloud.com/fotos/camila_ospina.jpg', 'https://storage.cloud.com/cv/camila_ospina_cv.pdf', '+573133332222', 'Gloria', 'Ospina Ruiz', '+573144443333', 'Carlos Eduardo', 'Vargas Montenegro', 'bicicleta', 'ECO-BIKE-003', '1', '1'),
('cedula', '1010202030', 'Sebastián', 'Moreno Jiménez', '+573110101020', 'sebastian.moreno.eco@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'O-', 'Calle 123 #45-67, Bogotá', 'https://storage.cloud.com/fotos/sebastian_moreno.jpg', 'https://storage.cloud.com/cv/sebastian_moreno_cv.pdf', '+573199990000', 'Esperanza', 'Moreno Gutiérrez', '+573188881111', 'Jairo', 'Jiménez Rojas', 'motocicleta', 'DEF-456', '0', '0');

-- =============================================
-- TABLA PEDIDOS
-- =============================================
INSERT INTO pedidos (cliente_id, codigo_qr, direccion_origen, nombre_origen, telefono_origen, direccion_destino, destinatario, telefono_destinatario, descripcion_paquete, instrucciones_entrega, estado, fecha_creacion) VALUES
(1, 'DELIV-ABC001', 'Carrera 7 #45-67, Bogotá', 'María Elena Pérez', '+573101112222', 'Calle 85 #12-34, Bogotá', 'Ana Sofía Herrera', '+573201234567', 'Almuerzo ejecutivo con sancocho y arroz', 'Tocar timbre, apartamento 301', 'entregado', '2025-08-26 12:30:00'),
(2, 'DELIV-XYZ002', 'Avenida 68 #123-45, Bogotá', 'Jorge Andrés Silva', '+573209876543', 'Carrera 15 #78-90, Bogotá', 'Carlos Mendoza', '+573187654321', 'Torta de chocolate personalizada', 'Entregar antes de las 6 PM', 'entregado', '2025-08-26 14:15:00'),
(3, 'DELIV-CAF003', 'Calle 93 #11-28, Bogotá', 'Sandra Patricia Ruiz', '+573155556666', 'Carrera 9 #72-15, Bogotá', 'Melissa García', '+573143216789', 'Café especial y croissants', 'Oficina piso 8, recepción', 'en_camino', '2025-08-27 09:20:00'),
(1, 'DELIV-DEL004', 'Carrera 7 #45-67, Bogotá', 'María Elena Pérez', '+573101112222', 'Calle 45 #23-56, Bogotá', 'Roberto Silva', '+573156789012', 'Bandeja paisa completa', 'Casa amarilla con portón negro', 'asignado', '2025-08-27 11:45:00'),
(4, 'DELIV-FRU005', 'Calle 32 #45-12, Bogotá', 'Pedro Antonio López', '+573177778888', 'Carrera 13 #67-89, Bogotá', 'Luisa Fernanda Rojas', '+573198765432', 'Mercado de frutas y verduras', 'Apartamento 205, segundo piso', 'pendiente', '2025-08-27 13:10:00'),
(5, 'DELIV-POS006', 'Avenida 19 #78-45, Bogotá', 'Laura Cristina Morales', '+573133334444', 'Calle 100 #15-28, Bogotá', 'Diana Marcela Vásquez', '+573174561230', 'Cupcakes para cumpleaños', 'Portería, preguntar por Diana', 'pendiente', '2025-08-27 15:30:00');

-- =============================================
-- TABLA ASIGNACIONES
-- =============================================
INSERT INTO asignaciones (pedido_id, mensajero_id, fecha_asignacion) VALUES
(1, 1, '2025-08-26 12:35:00'),  -- Pedido 1 asignado a Juan Carlos
(2, 2, '2025-08-26 14:20:00'),  -- Pedido 2 asignado a Andrea
(3, 3, '2025-08-27 09:25:00'),  -- Pedido 3 asignado a David
(4, 1, '2025-08-27 11:50:00');  -- Pedido 4 asignado a Juan Carlos

-- =============================================
-- TABLA ENTREGAS
-- =============================================
INSERT INTO entregas (pedido_id, mensajero_id, foto_url, nombre_receptor, id_receptor, monto_cobrado, metodo_pago, observacion, fecha_entrega) VALUES
(1, 1, 'https://storage.cloud.com/entregas/entrega_001.jpg', 'Ana Sofía Herrera', '9876543210', 25000.00, 'efectivo', 'Entregado sin novedad, cliente muy amable', '2025-08-26 13:15:00'),
(2, 2, 'https://storage.cloud.com/entregas/entrega_002.jpg', 'Carlos Mendoza', '5432167890', 45000.00, 'transferencia', 'Torta entregada en perfecto estado, celebración exitosa', '2025-08-26 17:45:00');




















































































/*casilla obsional para notificar*/

CREATE TABLE IF NOT EXISTS historial_pedidos (id INT AUTO_INCREMENT PRIMARY KEY,
                                    pedido_id INT NOT NULL,
                                    estado VARCHAR(50) NOT NULL,  -- Ej: "en_camino"
                                    mensaje TEXT,  -- Detalle adicional
                                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
);

/**/

CREATE TABLE IF NOT EXISTS notificaciones (id INT AUTO_INCREMENT PRIMARY KEY,
                                    usuario_id INT NOT NULL,  -- ID de cliente o mensajero
                                    tipo ENUM('cliente', 'mensajero') NOT NULL,
                                    mensaje TEXT NOT NULL,
                                    leida BOOLEAN DEFAULT FALSE,
                                    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP
);


INSERT INTO `clientes` (`id`, `nombre`, `correo`, `telefono`, `password`, `fecha_registro`, `estado`) VALUES (NULL, 'brayan Pulido', 'wool@gmail.com', '1234567899', '987654321', current_timestamp(), '1');
(`id`, `nombre`, `correo`, `telefono`, `password`, `fecha_registro`, `estado`) VALUES (NULL, 'brayan Pulido', 'brayan@gmail.com', '1234567899', '987654321', current_timestamp(), '1');

INSERT INTO `mensajeros` (`id`, `nombres`, `apellidos`, `correo`, `password`, `telefono`, `tipo_documento`, `numero_documento`, `sangre`, `direccion_residencia`, `foto`, `telefono_emergencia`, `nombre_emergencia`, `apellido_emergencia`, `fecha_registro`, `estado`) VALUES (NULL, 'brayan ', 'pulido', 'brayan06.pulido@gmail.com', '123456789', '9876543211', 'cedula', '1014596349', 'O+', 'calle 47 sur numero 1 f20 este', 'hola', '1234567899', 'marisol', 'lopez', current_timestamp(), '1');

INSERT INTO `pedidos` (`id`, `cliente_id`, `codigo_qr`, `direccion_origen`, `direccion_destino`, `destinatario`, `telefono_destinatario`, `descripcion_paquete`, `instrucciones_entrega`, `estado`, `fecha_creacion`) VALUES (NULL, '1', 'tuyf', 'ñklggasdfghjklñ', 'serdfghhjkl', 'marlon', '8897654321', 'dyttgkhujklñ', 'rdgfhjklkñ', 'pendiente', current_timestamp());

INSERT INTO `historial_pedidos` (`id`, `pedido_id`, `estado`, `mensaje`, `fecha_registro`) VALUES (NULL, '1', '1', 'dfghjklñ', current_timestamp());
INSERT INTO `entregas` (`id`, `pedido_id`, `mensajero_id`, `foto_url`, `nombre_receptor`, `id_receptor`, `monto_cobrado`, `metodo_pago`, `observacion`, `fecha_entrega`) VALUES (NULL, '1', '1', 'sdfggf', 'lorena', '132465789987', '100.000', 'no_aplica', 'hgasasad', current_timestamp());

INSERT INTO `asignaciones` (`id`, `pedido_id`, `mensajero_id`, `fecha_asignacion`) VALUES (NULL, '1', '1', current_timestamp());

/*ejemplo bASE DE DATOS */

-- ============================================
-- BASE DE DATOS DE MENSAJERÍA MEJORADA
-- ============================================

-- TABLAS PRINCIPALES MODIFICADAS
-- ============================================

CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
    cedula VARCHAR(20) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    correo VARCHAR(255) UNIQUE NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('super_admin', 'admin', 'operador') DEFAULT 'admin',
    permisos JSON, -- Para gestión granular de permisos
    ultimo_acceso DATETIME,
    intentos_login INT DEFAULT 0,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    estado INT(1) DEFAULT 1,
    INDEX idx_cedula (cedula),
    INDEX idx_correo (correo)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_emprendimiento VARCHAR(255) NOT NULL,
    tipo_producto VARCHAR(255) NOT NULL,
    cuenta_bancaria VARCHAR(300), 
    nombres VARCHAR(200) NOT NULL,
    apellidos VARCHAR(200) NOT NULL,
    correo VARCHAR(255) UNIQUE NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    instagram VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    direccion_principal VARCHAR(300),
    lat_direccion_principal DECIMAL(10, 8),
    lng_direccion_principal DECIMAL(11, 8),
    zona_cobertura VARCHAR(200),
    credito_disponible DECIMAL(10,2) DEFAULT 0.00,
    limite_credito DECIMAL(10,2) DEFAULT 0.00,
    calificacion_promedio DECIMAL(3,2) DEFAULT 0.00,
    total_pedidos INT DEFAULT 0,
    verificado BOOLEAN DEFAULT FALSE, /*posible enum*/
    fecha_verificacion DATETIME,
    ultimo_acceso DATETIME,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    estado INT(1) DEFAULT 1,
    INDEX idx_correo (correo),
    INDEX idx_verificado (verificado),
    INDEX idx_zona (zona_cobertura)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS mensajeros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
    numero_documento VARCHAR(20) NOT NULL,
    nombres VARCHAR(200) NOT NULL,
    apellidos VARCHAR(200) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    correo VARCHAR(200) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_sangre VARCHAR(11) NOT NULL,
    direccion_residencia VARCHAR(200) NOT NULL,
    foto VARCHAR(300) NOT NULL,
    hoja_vida VARCHAR(300) NOT NULL,
    telefono_emergencia1 VARCHAR(15) NOT NULL,
    nombre_emergencia1 VARCHAR(200) NOT NULL,
    apellido_emergencia1 VARCHAR(200) NOT NULL,
    telefono_emergencia2 VARCHAR(15) NOT NULL,
    nombre_emergencia2 VARCHAR(200) NOT NULL,
    apellido_emergencia2 VARCHAR(200) NOT NULL,
    tipo_vehiculo ENUM('bicicleta', 'motocicleta', 'vehiculo') NOT NULL,
    numero_vehiculo VARCHAR(20) NOT NULL,
    licencia_numero VARCHAR(50),
    licencia_vencimiento DATE,
    seguro_vehiculo VARCHAR(100),
    zona_trabajo VARCHAR(200),
    disponible BOOLEAN DEFAULT FALSE, -- Posible quitar
    en_servicio BOOLEAN DEFAULT FALSE, -- Posible quitar
    calificacion_promedio DECIMAL(3,2) DEFAULT 0.00, -- Posible quitar
    total_entregas INT DEFAULT 0, 
    entregas_exitosas INT DEFAULT 0,
    ganancia_total DECIMAL(10,2) DEFAULT 0.00,
    comision_porcentaje DECIMAL(5,2) DEFAULT 15.00, -- % que se lleva el mensajero     -- Posible quitar
    ultima_conexion DATETIME,
    ultima_ubicacion_lat DECIMAL(10, 8),
    ultima_ubicacion_lng DECIMAL(11, 8),
    verificado BOOLEAN DEFAULT FALSE,
    fecha_verificacion DATETIME,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    estado TINYINT(1) DEFAULT 1,
    INDEX idx_documento (numero_documento),
    INDEX idx_disponible (disponible),
    INDEX idx_zona (zona_trabajo),
    INDEX idx_tipo_vehiculo (tipo_vehiculo)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    codigo_qr VARCHAR(50) UNIQUE NOT NULL,
    direccion_origen VARCHAR(255) NOT NULL,
    nombre_origen VARCHAR(200) NOT NULL,
    telefono_origen VARCHAR(15) NOT NULL,
    lat_origen DECIMAL(10, 8),
    lng_origen DECIMAL(11, 8),
    direccion_destino VARCHAR(255) NOT NULL,
    destinatario VARCHAR(100) NOT NULL,
    telefono_destinatario VARCHAR(15) NOT NULL,
    lat_destino DECIMAL(10, 8),
    lng_destino DECIMAL(11, 8),
    descripcion_paquete TEXT,
    peso_kg DECIMAL(5,2),
    volumen_m3 DECIMAL(8,4),
    valor_declarado DECIMAL(10,2),
    fragil BOOLEAN DEFAULT FALSE,
    urgente BOOLEAN DEFAULT FALSE,
    instrucciones_entrega TEXT,
    fecha_limite DATETIME,
    tiempo_estimado_minutos INT,
    distancia_km DECIMAL(5,2),
    costo_calculado DECIMAL(8,2),
    costo_final DECIMAL(8,2),
    comision_mensajero DECIMAL(8,2),
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'credito') DEFAULT 'efectivo',
    estado ENUM('pendiente','asignado','aceptado','en_camino','en_destino','entregado','fallido','cancelado') DEFAULT 'pendiente',
    razon_cancelacion TEXT,
    intentos_asignacion INT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion DATETIME,
    fecha_aceptacion DATETIME,
    fecha_recogida DATETIME,
    fecha_entrega DATETIME,
    created_by INT,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_creacion (fecha_creacion),
    INDEX idx_codigo_qr (codigo_qr),
    INDEX idx_urgente (urgente)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    mensajero_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptado', 'rechazado', 'expirado') DEFAULT 'pendiente',
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME,
    tiempo_respuesta_minutos INT,
    razon_rechazo TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_mensajero (mensajero_id),
    INDEX idx_estado (estado)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    mensajero_id INT NOT NULL,
    foto_url VARCHAR(255) NOT NULL,
    nombre_receptor VARCHAR(100) NOT NULL,
    id_receptor VARCHAR(20),
    parentesco_receptor VARCHAR(50), -- Relación con el destinatario
    monto_cobrado DECIMAL(10, 2) DEFAULT 0.00,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'no_aplica') DEFAULT 'no_aplica',
    comprobante_pago VARCHAR(300), -- URL del comprobante
    lat_entrega DECIMAL(10, 8), -- Ubicación exacta de entrega
    lng_entrega DECIMAL(11, 8),
    observacion TEXT,
    tiempo_total_minutos INT,
    fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_mensajero (mensajero_id),
    INDEX idx_fecha (fecha_entrega)
) ENGINE=INNODB;

-- NUEVAS TABLAS DE SOPORTE
-- ============================================

CREATE TABLE IF NOT EXISTS tarifas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zona_origen VARCHAR(100) NOT NULL,
    zona_destino VARCHAR(100) NOT NULL,
    distancia_min DECIMAL(5,2) DEFAULT 0,
    distancia_max DECIMAL(5,2),
    precio_base DECIMAL(8,2) NOT NULL,
    precio_por_km DECIMAL(8,2) DEFAULT 0,
    recargo_urgente DECIMAL(8,2) DEFAULT 0,
    recargo_fragil DECIMAL(8,2) DEFAULT 0,
    recargo_nocturno DECIMAL(8,2) DEFAULT 0, -- Después de cierta hora
    hora_inicio TIME,
    hora_fin TIME,
    activo BOOLEAN DEFAULT TRUE,
    fecha_inicio DATE,
    fecha_fin DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_zonas (zona_origen, zona_destino),
    INDEX idx_activo (activo)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS ubicaciones_mensajeros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensajero_id INT NOT NULL,
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    velocidad_kmh DECIMAL(5,2),
    direccion DECIMAL(5,2), -- En grados
    precision_metros INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    INDEX idx_mensajero_timestamp (mensajero_id, timestamp)
) ENGINE=INNODB;

-- POSIBLE TABLA PARA QUITAR
CREATE TABLE IF NOT EXISTS calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    calificador_tipo ENUM('cliente', 'mensajero') NOT NULL,
    calificador_id INT NOT NULL,
    calificado_tipo ENUM('cliente', 'mensajero') NOT NULL,
    calificado_id INT NOT NULL,
    puntuacion TINYINT CHECK (puntuacion BETWEEN 1 AND 5),
    comentario TEXT,
    aspectos JSON, -- {"puntualidad": 5, "amabilidad": 4, "cuidado": 5}
    fecha_calificacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_calificado (calificado_tipo, calificado_id)
) ENGINE=INNODB;

-- POSIBLE TABLA PARA QUITAR

CREATE TABLE IF NOT EXISTS horarios_mensajeros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensajero_id INT NOT NULL,
    dia_semana TINYINT NOT NULL, -- 0=Domingo, 1=Lunes, ..., 6=Sábado
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    INDEX idx_mensajero (mensajero_id),
    UNIQUE KEY unique_mensajero_dia (mensajero_id, dia_semana)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_tipo ENUM('administrador', 'cliente', 'mensajero') NOT NULL,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('pedido', 'entrega', 'pago', 'sistema', 'promocion', 'calificacion') NOT NULL,
    datos_adicionales JSON, -- Para información específica del tipo
    leida BOOLEAN DEFAULT FALSE,
    enviada BOOLEAN DEFAULT FALSE,
    push_enviado BOOLEAN DEFAULT FALSE,
    email_enviado BOOLEAN DEFAULT FALSE,
    sms_enviado BOOLEAN DEFAULT FALSE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura DATETIME NULL,
    fecha_envio DATETIME NULL,
    INDEX idx_usuario (usuario_tipo, usuario_id),
    INDEX idx_leida (leida),
    INDEX idx_tipo (tipo)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS zonas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    poligono JSON, -- Coordenadas del polígono que define la zona
    centro_lat DECIMAL(10, 8),
    centro_lng DECIMAL(11, 8),
    radio_km DECIMAL(5,2), -- Radio para zonas circulares
    activa BOOLEAN DEFAULT TRUE,
    tarifa_base DECIMAL(8,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_activa (activa)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS incidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    mensajero_id INT,
    cliente_id INT,
    tipo ENUM('retraso', 'paquete_danado', 'direccion_incorrecta', 'cliente_ausente', 'accidente', 'robo', 'otro') NOT NULL,
    descripcion TEXT NOT NULL,
    evidencia_urls JSON, -- URLs de fotos/videos
    gravedad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    estado ENUM('abierta', 'en_proceso', 'resuelta', 'cerrada') DEFAULT 'abierta',
    resolucion TEXT,
    reportado_por ENUM('cliente', 'mensajero', 'sistema', 'administrador') NOT NULL,
    reportado_por_id INT,
    asignado_a INT, -- ID del administrador
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado),
    INDEX idx_gravedad (gravedad)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    mensajero_id INT NOT NULL,
    cliente_id INT NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    comision_plataforma DECIMAL(10,2) NOT NULL,
    comision_mensajero DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'credito') NOT NULL,
    estado_pago ENUM('pendiente', 'procesando', 'completado', 'fallido', 'reembolsado') DEFAULT 'pendiente',
    referencia_externa VARCHAR(100), -- ID de procesador de pagos
    comprobante_url VARCHAR(300),
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_liquidacion DATETIME, -- Cuándo se pagó al mensajero
    notas TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_estado (estado_pago),
    INDEX idx_fecha (fecha_pago)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    tipo ENUM('descuento_porcentaje', 'descuento_fijo', 'envio_gratis') NOT NULL,
    valor DECIMAL(8,2) NOT NULL, -- Porcentaje o monto fijo
    monto_minimo DECIMAL(8,2), -- Pedido mínimo para aplicar
    uso_maximo INT DEFAULT 1, -- Veces que se puede usar por usuario
    uso_total_maximo INT, -- Veces que se puede usar en total
    usos_actuales INT DEFAULT 0,
    valida_desde DATETIME NOT NULL,
    valida_hasta DATETIME NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    solo_nuevos_usuarios BOOLEAN DEFAULT FALSE,
    zonas_aplicables JSON, -- IDs de zonas donde aplica
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_activa (activa),
    INDEX idx_fechas (valida_desde, valida_hasta)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS uso_promociones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promocion_id INT NOT NULL,
    cliente_id INT NOT NULL,
    pedido_id INT NOT NULL,
    descuento_aplicado DECIMAL(8,2) NOT NULL,
    fecha_uso DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promocion_id) REFERENCES promociones(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    INDEX idx_promocion (promocion_id),
    INDEX idx_cliente (cliente_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    valores_anteriores JSON,
    valores_nuevos JSON,
    usuario_id INT,
    usuario_tipo ENUM('administrador', 'cliente', 'mensajero', 'sistema') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tabla_registro (tabla, registro_id),
    INDEX idx_usuario (usuario_tipo, usuario_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=INNODB;

-- TRIGGERS PARA AUDITORÍA AUTOMÁTICA
-- ============================================

DELIMITER $$

CREATE TRIGGER tr_pedidos_audit_insert
AFTER INSERT ON pedidos FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (tabla, registro_id, accion, valores_nuevos, usuario_tipo, usuario_id, timestamp)
    VALUES ('pedidos', NEW.id, 'INSERT', JSON_OBJECT(
        'cliente_id', NEW.cliente_id,
        'codigo_qr', NEW.codigo_qr,
        'estado', NEW.estado,
        'costo_final', NEW.costo_final
    ), 'sistema', NEW.created_by, NOW());
END$$

CREATE TRIGGER tr_pedidos_audit_update
AFTER UPDATE ON pedidos FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (tabla, registro_id, accion, valores_anteriores, valores_nuevos, usuario_tipo, usuario_id, timestamp)
    VALUES ('pedidos', NEW.id, 'UPDATE', JSON_OBJECT(
        'estado', OLD.estado,
        'costo_final', OLD.costo_final
    ), JSON_OBJECT(
        'estado', NEW.estado,
        'costo_final', NEW.costo_final
    ), 'sistema', NEW.updated_by, NOW());
END$$

DELIMITER ;

-- VISTAS ÚTILES
-- ============================================

CREATE VIEW vista_estadisticas_mensajeros AS
SELECT 
    m.id,
    m.nombres,
    m.apellidos,
    m.calificacion_promedio,
    m.total_entregas,
    m.entregas_exitosas,
    ROUND((m.entregas_exitosas / NULLIF(m.total_entregas, 0)) * 100, 2) as tasa_exito,
    m.ganancia_total,
    m.disponible,
    m.zona_trabajo,
    COUNT(CASE WHEN p.estado IN ('asignado', 'aceptado', 'en_camino') THEN 1 END) as pedidos_activos
FROM mensajeros m
LEFT JOIN asignaciones a ON m.id = a.mensajero_id
LEFT JOIN pedidos p ON a.pedido_id = p.id
WHERE m.estado = 1
GROUP BY m.id;

CREATE VIEW vista_pedidos_hoy AS
SELECT 
    p.*,
    c.nombre as nombre_cliente,
    c.nombre_emprendimiento,
    m.nombres as nombre_mensajero,
    m.apellidos as apellido_mensajero
FROM pedidos p
JOIN clientes c ON p.cliente_id = c.id
LEFT JOIN asignaciones a ON p.id = a.pedido_id AND a.estado = 'aceptado'
LEFT JOIN mensajeros m ON a.mensajero_id = m.id
WHERE DATE(p.fecha_creacion) = CURDATE();

-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- ============================================

CREATE INDEX idx_pedidos_fecha_estado ON pedidos(fecha_creacion, estado);
CREATE INDEX idx_mensajeros_disponible_zona ON mensajeros(disponible, zona_trabajo);
CREATE INDEX idx_ubicaciones_timestamp ON ubicaciones_mensajeros(timestamp DESC);
CREATE INDEX idx_notificaciones_pendientes ON notificaciones(usuario_tipo, usuario_id, leida);

-- DATOS INICIALES
-- ============================================

INSERT INTO zonas (nombre, descripcion, centro_lat, centro_lng, radio_km, activa, tarifa_base) VALUES
('Centro', 'Zona céntrica de la ciudad', 4.60971, -74.08175, 5.0, TRUE, 3000.00),
('Norte', 'Zona norte de la ciudad', 4.65, -74.08, 8.0, TRUE, 3500.00),
('Sur', 'Zona sur de la ciudad', 4.57, -74.08, 8.0, TRUE, 3500.00),
('Occidente', 'Zona occidental', 4.61, -74.12, 10.0, TRUE, 4000.00),
('Oriente', 'Zona oriental', 4.61, -74.04, 10.0, TRUE, 4000.00);

INSERT INTO tarifas (zona_origen, zona_destino, precio_base, precio_por_km, tipo_vehiculo, activo) VALUES
('Centro', 'Centro', 3000, 500, 'bicicleta', TRUE),
('Centro', 'Centro', 4000, 800, 'motocicleta', TRUE),
('Centro', 'Norte', 4000, 600, 'motocicleta', TRUE),
('Centro', 'Sur', 4000, 600, 'motocicleta', TRUE),
('Norte', 'Centro', 4000, 600, 'motocicleta', TRUE),
('Sur', 'Centro', 4000, 600, 'motocicleta', TRUE);

-- ============================================
-- FIN DE LA BASE DE DATOS
-- ============================================