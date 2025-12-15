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
                                    codigo_cliente VARCHAR(50) NOT NULL,    -- Ej: "CLT-123456"
                                    codigo_qr VARCHAR(50) UNIQUE NOT NULL,  -- Ej: "ECO-XYZ456"
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
    
    -- Datos del remitente
    remitente_nombre VARCHAR(200) NOT NULL,
    remitente_telefono VARCHAR(20) NOT NULL,
    remitente_correo VARCHAR(150),
    direccion_origen TEXT NOT NULL,
    
    -- Datos del destinatario
    destinatario_nombre VARCHAR(200) NOT NULL,
    destinatario_telefono VARCHAR(20) NOT NULL,
    direccion_destino TEXT NOT NULL,
    coordenadas_destino_lat DECIMAL(10, 8),
    coordenadas_destino_lng DECIMAL(11, 8),
    instrucciones_entrega TEXT,
    
    -- Datos del paquete
    descripcion_contenido TEXT NOT NULL,
    peso DECIMAL(8,2),
    largo DECIMAL(8,2),
    ancho DECIMAL(8,2),
    alto DECIMAL(8,2),
    tipo_paquete ENUM('normal', 'fragil', 'urgente', 'express') DEFAULT 'normal',
    valor_declarado DECIMAL(10,2) DEFAULT 0.00,
    
    -- Costos y servicios
    tipo_servicio ENUM('entrega_simple', 'contraentrega') DEFAULT 'entrega_simple',
    costo_envio DECIMAL(10,2) NOT NULL,
    recaudo_esperado DECIMAL(10,2) DEFAULT 0.00,
    
    -- Estado y asignación
    estado ENUM('pendiente', 'asignado', 'en_transito', 'entregado', 'devuelto', 'cancelado') DEFAULT 'pendiente',
    mensajero_id INT NULL,
    
    -- Fechas
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion TIMESTAMP NULL,
    fecha_entrega TIMESTAMP NULL,
    
    -- QR y seguimiento
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
    
    -- Datos de quien recibe
    nombre_receptor VARCHAR(200) NOT NULL,
    parentesco_cargo VARCHAR(100),
    documento_receptor VARCHAR(50),
    recaudo_real DECIMAL(10,2) DEFAULT 0.00,
    
    -- Ubicación y tiempo
    coordenadas_entrega_lat DECIMAL(10, 8),
    coordenadas_entrega_lng DECIMAL(11, 8),
    fecha_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Evidencias
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
    
    -- Datos de recolección
    direccion_recoleccion TEXT NOT NULL,
    coordenadas_lat DECIMAL(10, 8),
    coordenadas_lng DECIMAL(11, 8),
    nombre_contacto VARCHAR(200) NOT NULL,
    telefono_contacto VARCHAR(20) NOT NULL,
    
    -- Descripción del trabajo
    descripcion_paquetes TEXT,
    cantidad_estimada INT DEFAULT 1,
    cantidad_real INT,
    horario_preferido TIME,
    prioridad ENUM('normal', 'urgente', 'programada') DEFAULT 'normal',
    
    -- Estado y fechas
    estado ENUM('asignada', 'en_curso', 'completada', 'cancelada') DEFAULT 'asignada',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion TIMESTAMP,
    fecha_completada TIMESTAMP,
    
    -- Evidencias
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
    
    -- Datos del comprobante (copia de datos importantes)
    numero_guia VARCHAR(20) NOT NULL,
    nombre_receptor VARCHAR(200) NOT NULL,
    parentesco_cargo VARCHAR(100),
    recaudo DECIMAL(10,2) DEFAULT 0.00,
    observaciones TEXT,
    
    -- Archivos y evidencias
    foto_entrega VARCHAR(255) NOT NULL,
    archivo_pdf VARCHAR(255),
    
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notificado BOOLEAN DEFAULT FALSE,
    fecha_notificacion TIMESTAMP,
    
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
    
    registrado_por INT, -- ID del admin que registró el pago
    
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
    fecha_expiracion TIMESTAMP NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);



CREATE TABLE IF NOT EXISTS notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    
    datos_adicionales JSON, -- Para almacenar datos específicos de cada notificación
    
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
    coordenadas_poligono JSON, -- Array de coordenadas que definen la zona
    tarifa_base DECIMAL(8,2) NOT NULL,
    tarifa_contraentrega DECIMAL(8,2) NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



-- Índices para búsquedas frecuentes
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);
CREATE INDEX idx_paquetes_cliente_fecha ON paquetes(cliente_id, fecha_creacion);
CREATE INDEX idx_paquetes_mensajero_estado ON paquetes(mensajero_id, estado);
CREATE INDEX idx_entregas_fecha ON entregas(fecha_entrega);
CREATE INDEX idx_recolecciones_mensajero_estado ON recolecciones(mensajero_id, estado);
CREATE INDEX idx_facturas_cliente_estado ON facturas(cliente_id, estado);
CREATE INDEX idx_notificaciones_usuario_leida ON notificaciones(usuario_id, leida);

-- Índices compuestos para reportes
CREATE INDEX idx_paquetes_fecha_estado ON paquetes(fecha_creacion, estado);
CREATE INDEX idx_entregas_mensajero_fecha ON entregas(mensajero_id, fecha_entrega);



-- Configuraciones básicas del sistema
INSERT INTO configuraciones (clave, valor, descripcion, categoria) VALUES
('tarifa_base_entrega', '8000', 'Tarifa base para servicio de entrega simple', 'tarifas'),
('tarifa_adicional_contraentrega', '3000', 'Costo adicional por contraentrega', 'tarifas'),
('tiempo_sesion_minutos', '240', 'Tiempo de expiración de sesiones administrativas en minutos', 'seguridad'),
('max_intentos_login', '5', 'Máximo número de intentos de login antes de bloquear', 'seguridad'),
('tiempo_bloqueo_minutos', '30', 'Minutos de bloqueo después de exceder intentos', 'seguridad');

-- Zona de cobertura inicial (Bogotá)
INSERT INTO zonas_cobertura (nombre, descripcion, tarifa_base, tarifa_contraentrega) VALUES
('Bogotá Centro', 'Zona centro de Bogotá', 8000.00, 3000.00);

-- Super Administrador inicial
INSERT INTO usuarios (nombres, apellidos, email, telefono, password_hash, tipo_usuario, email_verificado) VALUES
('Super', 'Administrador', 'admin@ecobikemess.com', '3001234567', '$2y$10$example_hash', 'administrador', TRUE);

INSERT INTO administradores (usuario_id, rol) VALUES
(1, 'super_admin');
