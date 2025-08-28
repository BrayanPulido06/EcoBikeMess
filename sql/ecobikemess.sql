CREATE DATABASE IF NOT EXISTS ecobikemess;
use ecobikemess;

CREATE TABLE IF NOT EXISTS administradores (id INT AUTO_INCREMENT PRIMARY KEY,
                                    tipo_documento ENUM('cedula', 'dni', 'pasaporte', 'ruc', 'otro') NOT NULL,
                                    numDocumento VARCHAR(20) NOT NULL,
                                    nombres VARCHAR(200) NOT NULL,
                                    apellidos VARCHAR(200) NOT NULL,
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
                                    nombres VARCHAR(200) NOT NULL,
                                    apellidos VARCHAR(200) NOT NULL,
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
                                    numDocumento VARCHAR(20) NOT NULL,
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




-- Insertar Administradores
INSERT INTO administradores (tipo_documento, numDocumento, nombres, apellidos, correo, telefono, password, solicitarContraseña, sesionCaducada) VALUES
('cedula', '1014596349', 'Brayan Felipe', 'Pulido Lopez', 'brayan06.pulido@gmail.com', '3172509298', '123456789', '0', '1'),
('cedula', '87654321', 'Maria', 'Gonzalez Lopez', 'maria.admin@ecobikemess.com', '3009876543', '$2b$10$encrypted_password_hash2', '0', '1'),
('dni', '98765432', 'Juan', 'Perez Santos', 'juan.admin@ecobikemess.com', '3005555555', '$2b$10$encrypted_password_hash3', '1', '1');

-- Insertar Clientes (Emprendimientos)
INSERT INTO clientes (tipo_documento, numDocumento, nombre_emprendimiento, tipo_producto, cuenta_bancaria, nombres, apellidos, correo, telefono, instagram, password, solicitarContraseña, sesionCaducada) VALUES
('cedula', '1014596348', 'Delicias Caseras', 'Comida Casera', '1234567890123456', 'Brayan Felipe', 'Pulido Lopez', 'brayanpulido941@gmail.com', '3172509298', '@deliciascaseras', '123456789', '0', '1'),
('ruc', '20123456789', 'Flores del Valle', 'Floreria', '9876543210987654', 'Pedro', 'Martinez Silva', 'pedro@floresdevalle.com', '3022222222', '@floresdevalle', '$2b$10$client_password2', '0', '1'),
('cedula', '22222222', 'Reposteria Dulce', 'Reposteria', '5555666677778888', 'Sofia', 'Vargas Cruz', 'sofia@reposteriadulce.com', '3033333333', '@reposteriadulce', '$2b$10$client_password3', '1', '1'),
('cedula', '33333333', 'Farmacia Central', 'Medicamentos', '1111222233334444', 'Luis', 'Herrera Mora', 'luis@farmaciacentral.com', '3044444444', '@farmaciacentral', '$2b$10$client_password4', '0', '0'),
('dni', '44444444', 'Boutique Elegance', 'Ropa y Accesorios', '7777888899990000', 'Carmen', 'Lopez Diaz', 'carmen@boutiqueelegance.com', '3055555555', '@boutiqueelegance', '$2b$10$client_password5', '0', '1');

-- Insertar Mensajeros
INSERT INTO mensajeros (tipo_documento, numDocumento, nombres, apellidos, telefono, correo, password, tipo_sangre, direccion_residencia, foto, hoja_vida, telefono_emergencia1, nombre_emergencia1, apellido_emergencia1, telefono_emergencia2, nombre_emergencia2, apellido_emergencia2, tipo_vehiculo, numero_vehiculo, solicitarContraseña, sesionCaducada) VALUES
('cedula', '55555555', 'Miguel', 'Castro Jimenez', '3066666666', 'miguel.castro@gmail.com', '$2b$10$messenger_pass1', 'O+', 'Calle 45 #12-34, Bogotá', 'https://storage.com/fotos/miguel.jpg', 'https://storage.com/cv/miguel_cv.pdf', '3077777777', 'Rosa', 'Castro', '3088888888', 'Alberto', 'Jimenez', 'bicicleta', 'BCL-001', '0', '0'),
('cedula', '66666666', 'Laura', 'Mendez Restrepo', '3099999999', 'laura.mendez@gmail.com', '$2b$10$messenger_pass2', 'A-', 'Carrera 30 #25-67, Bogotá', 'https://storage.com/fotos/laura.jpg', 'https://storage.com/cv/laura_cv.pdf', '3010101010', 'Carmen', 'Mendez', '3020202020', 'Jorge', 'Restrepo', 'motocicleta', 'ABC-123', '0', '1'),
('dni', '77777777', 'Roberto', 'Sandoval Vega', '3030303030', 'roberto.sandoval@yahoo.com', '$2b$10$messenger_pass3', 'B+', 'Avenida 68 #40-15, Bogotá', 'https://storage.com/fotos/roberto.jpg', 'https://storage.com/cv/roberto_cv.pdf', '3040404040', 'Maria', 'Sandoval', '3050505050', 'Carlos', 'Vega', 'bicicleta', 'BCL-002', '1', '1'),
('cedula', '88888888', 'Diana', 'Rojas Morales', '3060606060', 'diana.rojas@hotmail.com', '$2b$10$messenger_pass4', 'AB+', 'Calle 80 #15-28, Bogotá', 'https://storage.com/fotos/diana.jpg', 'https://storage.com/cv/diana_cv.pdf', '3070707070', 'Ana', 'Rojas', '3080808080', 'Luis', 'Morales', 'vehiculo', 'DEF-456', '0', '0'),
('pasaporte', 'PA123456', 'Alejandro', 'Gutierrez Romero', '3090909090', 'alejandro.gutierrez@gmail.com', '$2b$10$messenger_pass5', 'O-', 'Transversal 20 #35-42, Bogotá', 'https://storage.com/fotos/alejandro.jpg', 'https://storage.com/cv/alejandro_cv.pdf', '3011111111', 'Elena', 'Gutierrez', '3022222222', 'Fernando', 'Romero', 'bicicleta', 'BCL-003', '0', '1');

-- Insertar Pedidos
INSERT INTO pedidos (cliente_id, codigo_qr, direccion_origen, nombre_origen, telefono_origen, direccion_destino, destinatario, telefono_destinatario, descripcion_paquete, instrucciones_entrega, estado) VALUES
(1, 'DELIV-ABC123', 'Carrera 15 #32-45, Chapinero', 'Ana Torres', '3011111111', 'Calle 100 #20-30, Zona Rosa', 'Juan Perez', '3001111111', 'Almuerzo ejecutivo - 2 porciones de pollo con arroz', 'Entregar en la recepción del edificio', 'pendiente'),
(2, 'DELIV-XYZ789', 'Avenida 19 #104-28, Usaquén', 'Pedro Martinez', '3022222222', 'Carrera 7 #63-15, Zona Rosa', 'Maria Rodriguez', '3002222222', 'Ramo de 12 rosas rojas con tarjeta', 'Llamar antes de subir al apartamento 502', 'asignado'),
(3, 'DELIV-DEF456', 'Calle 53 #14-20, Chapinero', 'Sofia Vargas', '3033333333', 'Transversal 23 #97-45, Chicó', 'Carlos Mendoza', '3003333333', 'Torta de chocolate para 8 personas', 'Manejar con cuidado - frágil', 'en_camino'),
(4, 'DELIV-GHI789', 'Carrera 30 #45-12, Teusaquillo', 'Luis Herrera', '3044444444', 'Calle 85 #12-34, Chapinero Norte', 'Patricia Silva', '3004444444', 'Medicamentos - Ibuprofeno y vitaminas', 'Entregar solo al destinatario con cédula', 'entregado'),
(5, 'DELIV-JKL012', 'Avenida 82 #20-15, Zona Rosa', 'Carmen Lopez', '3055555555', 'Calle 140 #15-25, Cedritos', 'Andrea Morales', '3005555555', 'Vestido de fiesta talla M - color azul', 'Tocar timbre apartamento 301', 'pendiente'),
(1, 'DELIV-MNO345', 'Carrera 15 #32-45, Chapinero', 'Ana Torres', '3011111111', 'Diagonal 109 #18-20, Usaquén', 'Roberto Castro', '3006666666', 'Cena para dos personas - pasta con pollo', 'Dejar en portería si no contesta', 'cancelado'),
(3, 'DELIV-PQR678', 'Calle 53 #14-20, Chapinero', 'Sofia Vargas', '3033333333', 'Carrera 11 #93-45, Chicó Norte', 'Lucia Herrera', '3007777777', 'Cupcakes variados x12 unidades', 'Entrega urgente antes de las 6 PM', 'asignado');

-- Insertar Asignaciones
INSERT INTO asignaciones (pedido_id, mensajero_id) VALUES
(2, 1),  -- Pedido XYZ789 asignado a Miguel Castro
(3, 2),  -- Pedido DEF456 asignado a Laura Mendez  
(4, 3),  -- Pedido GHI789 asignado a Roberto Sandoval
(7, 4);  -- Pedido PQR678 asignado a Diana Rojas

-- Insertar Entregas (solo para pedidos completados)
INSERT INTO entregas (pedido_id, mensajero_id, foto_url, nombre_receptor, id_receptor, monto_cobrado, metodo_pago, observacion) VALUES
(4, 3, 'https://storage.com/entregas/entrega_ghi789.jpg', 'Patricia Silva', '98765432', 35000.00, 'efectivo', 'Entrega exitosa. Cliente muy amable y medicamentos entregados correctamente.');

-- Actualizar estado del pedido entregado
UPDATE pedidos SET estado = 'entregado' WHERE id = 4;



















































































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