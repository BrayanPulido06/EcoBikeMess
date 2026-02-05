-- =====================================================
-- BASE DE DATOS ECOBIKEMESS - VERSIÓN COMPLETA CON COLABORADORES
-- =====================================================

DROP DATABASE IF EXISTS ecobikemess;
CREATE DATABASE IF NOT EXISTS ecobikemess;
USE ecobikemess;

-- =====================================================
-- TABLAS PRINCIPALES
-- =====================================================

-- Tabla de usuarios (MODIFICADA para incluir colaboradores)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('cliente', 'mensajero', 'administrador', 'colaborador') NOT NULL,
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

-- =====================================================
-- NUEVA TABLA: COLABORADORES DE CLIENTES
-- =====================================================

CREATE TABLE IF NOT EXISTS colaboradores_cliente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    cargo VARCHAR(100) NOT NULL,
    puede_crear_paquetes BOOLEAN DEFAULT TRUE,
    puede_ver_facturas BOOLEAN DEFAULT TRUE,
    puede_ver_comprobantes BOOLEAN DEFAULT TRUE,
    puede_gestionar_recolecciones BOOLEAN DEFAULT TRUE,
    puede_ver_reportes BOOLEAN DEFAULT TRUE,
    puede_editar_perfil_tienda BOOLEAN DEFAULT FALSE,
    puede_agregar_colaboradores BOOLEAN DEFAULT FALSE,
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    notas TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

-- =====================================================
-- NUEVA TABLA: INVITACIONES PARA COLABORADORES (CORREGIDA)
-- =====================================================

CREATE TABLE IF NOT EXISTS invitaciones_colaboradores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    correo_invitado VARCHAR(150) NOT NULL,
    token_invitacion VARCHAR(100) UNIQUE NOT NULL,
    cargo VARCHAR(100),
    permisos_propuestos JSON,
    mensaje_invitacion TEXT,
    estado ENUM('pendiente', 'aceptada', 'rechazada', 'expirada') DEFAULT 'pendiente',
    fecha_invitacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    fecha_respuesta DATETIME NULL,
    invitado_por INT NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (invitado_por) REFERENCES usuarios(id)
);

-- =====================================================
-- NUEVA TABLA: AUDITORÍA DE COLABORADORES
-- =====================================================

CREATE TABLE IF NOT EXISTS auditoria_colaboradores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    colaborador_id INT NOT NULL,
    cliente_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT,
    datos_anteriores JSON,
    datos_nuevos JSON,
    ip_address VARCHAR(45),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores_cliente(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLAS EXISTENTES (sin cambios)
-- =====================================================

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
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion TIMESTAMP NULL,
    fecha_entrega TIMESTAMP NULL,
    qr_code VARCHAR(255) UNIQUE,
    escaneado BOOLEAN DEFAULT FALSE,
    fecha_escaneo TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
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
    creada_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion TIMESTAMP NULL,
    fecha_completada TIMESTAMP NULL,
    foto_recoleccion VARCHAR(255),
    observaciones_recoleccion TEXT,
    conformidad BOOLEAN DEFAULT TRUE,
    justificacion_cancelacion TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (mensajero_id) REFERENCES mensajeros(id),
    FOREIGN KEY (creada_por) REFERENCES usuarios(id)
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

-- =====================================================
-- ÍNDICES
-- =====================================================

-- Índices originales
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
CREATE INDEX idx_numero_guia ON paquetes(numero_guia);
CREATE INDEX idx_estado ON paquetes(estado);
CREATE INDEX idx_fecha_creacion ON paquetes(fecha_creacion);
CREATE INDEX idx_logs_usuario_tipo ON logs_actividad(usuario_id, tipo_actividad);

-- Índices para colaboradores
CREATE INDEX idx_colaborador_cliente ON colaboradores_cliente(cliente_id, estado);
CREATE INDEX idx_colaborador_usuario ON colaboradores_cliente(usuario_id);
CREATE INDEX idx_colaborador_estado ON colaboradores_cliente(estado);
CREATE INDEX idx_invitacion_correo ON invitaciones_colaboradores(correo_invitado);
CREATE INDEX idx_invitacion_estado ON invitaciones_colaboradores(estado, fecha_expiracion);
CREATE INDEX idx_invitacion_cliente ON invitaciones_colaboradores(cliente_id);
CREATE INDEX idx_auditoria_colaborador ON auditoria_colaboradores(colaborador_id, fecha_accion);
CREATE INDEX idx_auditoria_cliente ON auditoria_colaboradores(cliente_id, fecha_accion);

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de colaboradores con información completa
CREATE OR REPLACE VIEW vista_colaboradores_completa AS
SELECT 
    cc.id AS colaborador_id,
    u.id AS usuario_id,
    u.nombres,
    u.apellidos,
    u.correo,
    u.telefono,
    u.estado AS estado_usuario,
    u.ultimo_acceso,
    c.id AS cliente_id,
    c.nombre_emprendimiento,
    cc.cargo,
    cc.puede_crear_paquetes,
    cc.puede_ver_facturas,
    cc.puede_ver_comprobantes,
    cc.puede_gestionar_recolecciones,
    cc.puede_ver_reportes,
    cc.puede_editar_perfil_tienda,
    cc.puede_agregar_colaboradores,
    cc.estado AS estado_colaborador,
    cc.fecha_creacion,
    cc.ultimo_acceso AS ultimo_acceso_colaborador,
    cc.notas,
    creador.nombres AS creado_por_nombre,
    creador.apellidos AS creado_por_apellido,
    creador.correo AS creado_por_correo
FROM colaboradores_cliente cc
INNER JOIN usuarios u ON cc.usuario_id = u.id
INNER JOIN clientes c ON cc.cliente_id = c.id
INNER JOIN usuarios creador ON cc.creado_por = creador.id;

-- Vista de paquetes con información del creador
CREATE OR REPLACE VIEW vista_paquetes_completa AS
SELECT 
    p.*,
    c.nombre_emprendimiento,
    u.nombres AS creador_nombres,
    u.apellidos AS creador_apellidos,
    u.tipo_usuario AS tipo_creador,
    CASE 
        WHEN u.tipo_usuario = 'colaborador' THEN cc.cargo
        ELSE 'Propietario'
    END AS rol_creador
FROM paquetes p
INNER JOIN clientes c ON p.cliente_id = c.id
INNER JOIN usuarios u ON p.creado_por = u.id
LEFT JOIN colaboradores_cliente cc ON u.id = cc.usuario_id AND c.id = cc.cliente_id;

-- =====================================================
-- FUNCIONES Y PROCEDIMIENTOS
-- =====================================================

DELIMITER //

-- Función para verificar permisos de colaborador
CREATE FUNCTION verificar_permiso_colaborador(
    p_usuario_id INT,
    p_cliente_id INT,
    p_permiso VARCHAR(50)
) RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE tiene_permiso BOOLEAN DEFAULT FALSE;
    DECLARE tipo_usr VARCHAR(50);
    
    SELECT tipo_usuario INTO tipo_usr
    FROM usuarios
    WHERE id = p_usuario_id;
    
    -- Si es el cliente principal, tiene todos los permisos
    IF tipo_usr = 'cliente' THEN
        SELECT EXISTS(
            SELECT 1 FROM clientes WHERE usuario_id = p_usuario_id AND id = p_cliente_id
        ) INTO tiene_permiso;
        RETURN tiene_permiso;
    END IF;
    
    -- Si es colaborador, verificar permiso específico
    IF tipo_usr = 'colaborador' THEN
        CASE p_permiso
            WHEN 'crear_paquetes' THEN
                SELECT puede_crear_paquetes INTO tiene_permiso
                FROM colaboradores_cliente
                WHERE usuario_id = p_usuario_id AND cliente_id = p_cliente_id AND estado = 'activo';
            WHEN 'ver_facturas' THEN
                SELECT puede_ver_facturas INTO tiene_permiso
                FROM colaboradores_cliente
                WHERE usuario_id = p_usuario_id AND cliente_id = p_cliente_id AND estado = 'activo';
            WHEN 'ver_comprobantes' THEN
                SELECT puede_ver_comprobantes INTO tiene_permiso
                FROM colaboradores_cliente
                WHERE usuario_id = p_usuario_id AND cliente_id = p_cliente_id AND estado = 'activo';
            WHEN 'gestionar_recolecciones' THEN
                SELECT puede_gestionar_recolecciones INTO tiene_permiso
                FROM colaboradores_cliente
                WHERE usuario_id = p_usuario_id AND cliente_id = p_cliente_id AND estado = 'activo';
            WHEN 'ver_reportes' THEN
                SELECT puede_ver_reportes INTO tiene_permiso
                FROM colaboradores_cliente
                WHERE usuario_id = p_usuario_id AND cliente_id = p_cliente_id AND estado = 'activo';
            WHEN 'editar_perfil' THEN
                SELECT puede_editar_perfil_tienda INTO tiene_permiso
                FROM colaboradores_cliente
                WHERE usuario_id = p_usuario_id AND cliente_id = p_cliente_id AND estado = 'activo';
            WHEN 'agregar_colaboradores' THEN
                SELECT puede_agregar_colaboradores INTO tiene_permiso
                FROM colaboradores_cliente
                WHERE usuario_id = p_usuario_id AND cliente_id = p_cliente_id AND estado = 'activo';
            ELSE
                SET tiene_permiso = FALSE;
        END CASE;
    END IF;
    
    RETURN IFNULL(tiene_permiso, FALSE);
END //

-- Función para obtener el cliente_id asociado a un usuario
CREATE FUNCTION obtener_cliente_id(p_usuario_id INT) 
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE cliente_id_result INT;
    DECLARE tipo_usr VARCHAR(50);
    
    SELECT tipo_usuario INTO tipo_usr FROM usuarios WHERE id = p_usuario_id;
    
    IF tipo_usr = 'cliente' THEN
        SELECT id INTO cliente_id_result FROM clientes WHERE usuario_id = p_usuario_id;
    ELSEIF tipo_usr = 'colaborador' THEN
        SELECT cliente_id INTO cliente_id_result 
        FROM colaboradores_cliente 
        WHERE usuario_id = p_usuario_id AND estado = 'activo';
    END IF;
    
    RETURN cliente_id_result;
END //

-- Procedimiento para crear un colaborador
CREATE PROCEDURE crear_colaborador(
    IN p_cliente_id INT,
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_correo VARCHAR(150),
    IN p_telefono VARCHAR(20),
    IN p_password VARCHAR(255),
    IN p_cargo VARCHAR(100),
    IN p_puede_crear_paquetes BOOLEAN,
    IN p_puede_ver_facturas BOOLEAN,
    IN p_puede_ver_comprobantes BOOLEAN,
    IN p_puede_gestionar_recolecciones BOOLEAN,
    IN p_puede_ver_reportes BOOLEAN,
    IN p_puede_editar_perfil_tienda BOOLEAN,
    IN p_puede_agregar_colaboradores BOOLEAN,
    IN p_creado_por INT,
    OUT p_usuario_id INT,
    OUT p_colaborador_id INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_usuario_id = NULL;
        SET p_colaborador_id = NULL;
    END;
    
    START TRANSACTION;
    
    -- Crear usuario
    INSERT INTO usuarios (nombres, apellidos, correo, telefono, password, tipo_usuario, estado, correo_verificado)
    VALUES (p_nombres, p_apellidos, p_correo, p_telefono, p_password, 'colaborador', 'activo', TRUE);
    
    SET p_usuario_id = LAST_INSERT_ID();
    
    -- Crear registro de colaborador
    INSERT INTO colaboradores_cliente (
        usuario_id, cliente_id, cargo,
        puede_crear_paquetes, puede_ver_facturas, puede_ver_comprobantes,
        puede_gestionar_recolecciones, puede_ver_reportes,
        puede_editar_perfil_tienda, puede_agregar_colaboradores,
        estado, creado_por
    ) VALUES (
        p_usuario_id, p_cliente_id, p_cargo,
        p_puede_crear_paquetes, p_puede_ver_facturas, p_puede_ver_comprobantes,
        p_puede_gestionar_recolecciones, p_puede_ver_reportes,
        p_puede_editar_perfil_tienda, p_puede_agregar_colaboradores,
        'activo', p_creado_por
    );
    
    SET p_colaborador_id = LAST_INSERT_ID();
    
    COMMIT;
END //

-- Procedimiento para actualizar permisos de colaborador
CREATE PROCEDURE actualizar_permisos_colaborador(
    IN p_colaborador_id INT,
    IN p_puede_crear_paquetes BOOLEAN,
    IN p_puede_ver_facturas BOOLEAN,
    IN p_puede_ver_comprobantes BOOLEAN,
    IN p_puede_gestionar_recolecciones BOOLEAN,
    IN p_puede_ver_reportes BOOLEAN,
    IN p_puede_editar_perfil_tienda BOOLEAN,
    IN p_puede_agregar_colaboradores BOOLEAN,
    IN p_modificado_por INT
)
BEGIN
    DECLARE v_usuario_id INT;
    DECLARE v_cliente_id INT;
    DECLARE v_permisos_anteriores JSON;
    DECLARE v_permisos_nuevos JSON;
    
    -- Obtener datos del colaborador
    SELECT usuario_id, cliente_id INTO v_usuario_id, v_cliente_id
    FROM colaboradores_cliente
    WHERE id = p_colaborador_id;
    
    -- Guardar permisos anteriores
    SELECT JSON_OBJECT(
        'puede_crear_paquetes', puede_crear_paquetes,
        'puede_ver_facturas', puede_ver_facturas,
        'puede_ver_comprobantes', puede_ver_comprobantes,
        'puede_gestionar_recolecciones', puede_gestionar_recolecciones,
        'puede_ver_reportes', puede_ver_reportes,
        'puede_editar_perfil_tienda', puede_editar_perfil_tienda,
        'puede_agregar_colaboradores', puede_agregar_colaboradores
    ) INTO v_permisos_anteriores
    FROM colaboradores_cliente
    WHERE id = p_colaborador_id;
    
    -- Actualizar permisos
    UPDATE colaboradores_cliente
    SET 
        puede_crear_paquetes = p_puede_crear_paquetes,
        puede_ver_facturas = p_puede_ver_facturas,
        puede_ver_comprobantes = p_puede_ver_comprobantes,
        puede_gestionar_recolecciones = p_puede_gestionar_recolecciones,
        puede_ver_reportes = p_puede_ver_reportes,
        puede_editar_perfil_tienda = p_puede_editar_perfil_tienda,
        puede_agregar_colaboradores = p_puede_agregar_colaboradores
    WHERE id = p_colaborador_id;
    
    -- Crear permisos nuevos JSON
    SET v_permisos_nuevos = JSON_OBJECT(
        'puede_crear_paquetes', p_puede_crear_paquetes,
        'puede_ver_facturas', p_puede_ver_facturas,
        'puede_ver_comprobantes', p_puede_ver_comprobantes,
        'puede_gestionar_recolecciones', p_puede_gestionar_recolecciones,
        'puede_ver_reportes', p_puede_ver_reportes,
        'puede_editar_perfil_tienda', p_puede_editar_perfil_tienda,
        'puede_agregar_colaboradores', p_puede_agregar_colaboradores
    );
    
    -- Registrar en auditoría
    INSERT INTO auditoria_colaboradores (
        colaborador_id, cliente_id, accion, descripcion,
        datos_anteriores, datos_nuevos
    ) VALUES (
        p_colaborador_id, v_cliente_id,
        'actualizar_permisos',
        'Se actualizaron los permisos del colaborador',
        v_permisos_anteriores, v_permisos_nuevos
    );
END //

DELIMITER ;

-- =====================================================
-- DATOS DE EJEMPLO
-- =====================================================

-- Usuarios
INSERT INTO usuarios (nombres, apellidos, correo, telefono, password, tipo_usuario, estado, correo_verificado)
VALUES 
('Brayan', 'Rodriguez Gomez', 'brayan@gmail.com', '3001234567', '123456789', 'cliente', 'activo', TRUE),
('Eco', 'Bike Admin', 'eco@gmail.com', '3109876543', '123456789', 'administrador', 'activo', TRUE),
('Marlon', 'Castro Perez', 'marlon@gmail.com', '3205551234', '123456789', 'mensajero', 'activo', TRUE),
('Sofia', 'Martinez Ruiz', 'sofia@brayanstore.com', '3159876543', '123456789', 'colaborador', 'activo', TRUE),
('Carlos', 'Diaz Lopez', 'carlos@brayanstore.com', '3187654321', '123456789', 'colaborador', 'activo', TRUE);

-- Clientes
INSERT INTO clientes (usuario_id, nombre_emprendimiento, tipo_producto, instagram, direccion_principal, saldo_pendiente, limite_credito)
VALUES (1, 'Brayan Store', 'Ropa y accesorios deportivos', '@brayanstore', 'Calle 45 #23-67, Bogota', 0.00, 500000.00);

-- Administradores
INSERT INTO administradores (usuario_id, tipo_documento, num_documento, rol, foto, permisos_especiales)
VALUES (2, 'cedula', '1234567890', 'super_admin', '/uploads/admins/eco_foto.jpg', '{"permisos": ["todos"]}');

-- Mensajeros
INSERT INTO mensajeros (usuario_id, tipo_documento, numDocumento, foto, hoja_vida, telefono_emergencia1, nombre_emergencia1, apellido_emergencia1, telefono_emergencia2, nombre_emergencia2, apellido_emergencia2, tipo_sangre, direccion_residencia, tipo_transporte, estado_aprobacion, calificacion_promedio, total_entregas)
VALUES (3, 'cedula', '9876543210', '/uploads/mensajeros/marlon_foto.jpg', '/uploads/mensajeros/marlon_hv.pdf', '3001112233', 'Maria', 'Castro Lopez', '3104445566', 'Pedro', 'Castro Ramirez', 'O+', 'Carrera 15 #78-90, Bogota', 'bicicleta', 'aprobado', 4.85, 0);

-- Colaboradores (NUEVO)
INSERT INTO colaboradores_cliente (
    usuario_id, cliente_id, cargo,
    puede_crear_paquetes, puede_ver_facturas, puede_ver_comprobantes,
    puede_gestionar_recolecciones, puede_ver_reportes,
    puede_editar_perfil_tienda, puede_agregar_colaboradores,
    estado, creado_por, notas
) VALUES 
(4, 1, 'Asistente de Logística',
 TRUE, TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
 'activo', 1, 'Encargada de gestionar envíos y seguimiento'),
 
(5, 1, 'Coordinador de Entregas',
 TRUE, TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
 'activo', 1, 'Responsable de coordinar con mensajeros');

-- Invitaciones colaboradores (NUEVO - CORREGIDO CON DATETIME)
INSERT INTO invitaciones_colaboradores (
    cliente_id, correo_invitado, token_invitacion, cargo,
    permisos_propuestos, mensaje_invitacion,
    fecha_expiracion, invitado_por
) VALUES (
    1, 'nuevo@brayanstore.com', 
    CONCAT('INV-', UPPER(UUID())),
    'Asistente Administrativo',
    JSON_OBJECT(
        'puede_crear_paquetes', true,
        'puede_ver_facturas', true,
        'puede_ver_comprobantes', true,
        'puede_gestionar_recolecciones', false,
        'puede_ver_reportes', true,
        'puede_editar_perfil_tienda', false,
        'puede_agregar_colaboradores', false
    ),
    'Te invitamos a unirte al equipo de Brayan Store',
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    1
);

-- Paquetes (con campo creado_por)
INSERT INTO paquetes (numero_guia, cliente_id, remitente_nombre, remitente_telefono, remitente_correo, direccion_origen, destinatario_nombre, destinatario_telefono, direccion_destino, coordenadas_destino_lat, coordenadas_destino_lng, instrucciones_entrega, descripcion_contenido, peso, largo, ancho, alto, tipo_paquete, valor_declarado, tipo_servicio, costo_envio, recaudo_esperado, estado, mensajero_id, creado_por, fecha_asignacion, fecha_entrega, qr_code, escaneado, fecha_escaneo)
VALUES 
('ECO-2024-001', 1, 'Brayan Rodriguez', '3001234567', 'brayan@gmail.com', 'Calle 45 #23-67, Bogota', 'Ana Maria Lopez', '3156789012', 'Carrera 7 #100-25, Bogota', 4.701954, -74.035599, 'Dejar con porteria si no esta', 'Camiseta deportiva talla M', 0.25, 30.00, 25.00, 5.00, 'normal', 50000.00, 'contraentrega', 8000.00, 75000.00, 'entregado', 1, 1, '2024-12-20 08:30:00', '2024-12-20 14:45:00', 'QR-ECO-001', TRUE, '2024-12-20 08:35:00'),

('ECO-2024-002', 1, 'Brayan Store', '3001234567', 'brayan@gmail.com', 'Calle 45 #23-67, Bogota', 'Carlos Mendoza', '3187654321', 'Calle 127 #15-40, Bogota', 4.722445, -74.045732, 'Llamar al llegar', 'Zapatillas deportivas talla 42', 0.80, 35.00, 25.00, 15.00, 'express', 120000.00, 'entrega_simple', 12000.00, 0, 'en_transito', 1, 4, '2024-12-25 09:15:00', NULL, 'QR-ECO-002', TRUE, '2024-12-25 09:20:00'),

('ECO-2024-003', 1, 'Brayan Store', '3001234567', 'brayan@gmail.com', 'Calle 45 #23-67, Bogota', 'Laura Sanchez', '3209876543', 'Avenida 68 #45-23, Bogota', NULL, NULL, 'Entregar en horario de oficina', 'Conjunto deportivo completo', 0.60, NULL, NULL, NULL, 'urgente', 85000.00, 'contraentrega', 10000.00, 95000.00, 'pendiente', NULL, 5, NULL, NULL, 'QR-ECO-003', FALSE, NULL),

('ECO-2024-004', 1, 'Brayan Store', '3001234567', NULL, 'Calle 45 #23-67, Bogota', 'Miguel Torres', '3123456789', 'Calle 80 #10-20, Bogota', NULL, NULL, NULL, 'Gorra deportiva', 0.15, NULL, NULL, NULL, 'normal', 30000.00, 'entrega_simple', 7000.00, 0, 'asignado', 1, 4, '2024-12-25 10:00:00', NULL, 'QR-ECO-004', FALSE, NULL);

-- Entregas
INSERT INTO entregas (paquete_id, mensajero_id, nombre_receptor, parentesco_cargo, documento_receptor, recaudo_real, coordenadas_entrega_lat, coordenadas_entrega_lng, foto_entrega, foto_adicional, observaciones)
VALUES (1, 1, 'Ana Maria Lopez', 'Titular', '52123456', 75000.00, 4.701954, -74.035599, '/uploads/entregas/entrega_001_principal.jpg', '/uploads/entregas/entrega_001_adicional.jpg', 'Entrega exitosa. Cliente satisfecho con el producto.');

-- Recolecciones (con campo creada_por)
INSERT INTO recolecciones (numero_orden, cliente_id, mensajero_id, direccion_recoleccion, coordenadas_lat, coordenadas_lng, nombre_contacto, telefono_contacto, descripcion_paquetes, cantidad_estimada, cantidad_real, horario_preferido, prioridad, estado, creada_por, fecha_asignacion, fecha_completada, foto_recoleccion, observaciones_recoleccion, conformidad)
VALUES 
('REC-2024-001', 1, 1, 'Calle 45 #23-67, Bogota', 4.672855, -74.055374, 'Brayan Rodriguez', '3001234567', 'Paquetes de ropa deportiva para envio', 5, 5, '14:00:00', 'programada', 'completada', 1, '2024-12-19 10:00:00', '2024-12-19 14:30:00', '/uploads/recolecciones/rec_001.jpg', 'Recoleccion exitosa. 5 paquetes recogidos.', TRUE),

('REC-2024-002', 1, 1, 'Calle 45 #23-67, Bogota', NULL, NULL, 'Sofia Martinez', '3159876543', 'Nueva remesa de productos', 3, NULL, '16:00:00', 'normal', 'en_curso', 4, '2024-12-25 11:00:00', NULL, NULL, NULL, TRUE);

-- Comprobantes
INSERT INTO comprobantes (paquete_id, cliente_id, numero_comprobante, numero_guia, nombre_receptor, parentesco_cargo, recaudo, observaciones, foto_entrega, archivo_pdf, notificado, fecha_notificacion)
VALUES (1, 1, 'COMP-2024-001', 'ECO-2024-001', 'Ana Maria Lopez', 'Titular', 75000.00, 'Entrega exitosa', '/uploads/entregas/entrega_001_principal.jpg', '/uploads/comprobantes/comp_001.pdf', TRUE, '2024-12-20 15:00:00');

-- Facturas
INSERT INTO facturas (cliente_id, numero_factura, periodo_inicio, periodo_fin, subtotal, impuestos, total, estado, fecha_vencimiento, archivo_pdf, fecha_pago)
VALUES 
(1, 'FACT-2024-001', '2024-12-01', '2024-12-20', 45000.00, 8550.00, 53550.00, 'pagada', '2024-12-30', '/uploads/facturas/fact_001.pdf', '2024-12-21 10:30:00'),
(1, 'FACT-2024-002', '2024-12-21', '2024-12-31', 19000.00, 3610.00, 22610.00, 'pendiente', '2025-01-10', NULL, NULL);

-- Detalle facturas
INSERT INTO detalle_facturas (factura_id, paquete_id, descripcion, costo_unitario, cantidad, subtotal)
VALUES 
(1, 1, 'Envio contraentrega ECO-2024-001', 8000.00, 1, 8000.00),
(1, 2, 'Envio express ECO-2024-002', 12000.00, 1, 12000.00),
(2, 3, 'Envio urgente contraentrega ECO-2024-003', 10000.00, 1, 10000.00),
(2, 4, 'Envio simple ECO-2024-004', 7000.00, 1, 7000.00);

-- Pagos
INSERT INTO pagos (factura_id, cliente_id, numero_recibo, monto, metodo_pago, fecha_pago, comprobante_pago, observaciones, registrado_por)
VALUES (1, 1, 'REC-2024-001', 53550.00, 'transferencia', '2024-12-21', '/uploads/pagos/pago_001.jpg', 'Pago completo por transferencia bancaria', 1);

-- Sesiones
INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, fecha_expiracion, activa)
VALUES 
(1, 'token_brayan_123abc', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_ADD(NOW(), INTERVAL 7 DAY), TRUE),
(2, 'token_eco_456def', '192.168.1.101', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', DATE_ADD(NOW(), INTERVAL 7 DAY), TRUE),
(3, 'token_marlon_789ghi', '192.168.1.102', 'Mozilla/5.0 (Linux; Android 11)', DATE_ADD(NOW(), INTERVAL 7 DAY), TRUE),
(4, 'token_sofia_101jkl', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_ADD(NOW(), INTERVAL 7 DAY), TRUE),
(5, 'token_carlos_202mno', '192.168.1.104', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)', DATE_ADD(NOW(), INTERVAL 7 DAY), TRUE);

-- Notificaciones
INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_adicionales, leida, fecha_lectura)
VALUES 
(1, 'entrega_exitosa', 'Paquete entregado', 'Tu paquete ECO-2024-001 ha sido entregado exitosamente', '{"paquete_id": 1, "numero_guia": "ECO-2024-001"}', TRUE, '2024-12-20 15:30:00'),
(1, 'paquete_en_transito', 'Paquete en camino', 'Tu paquete ECO-2024-002 esta en transito', '{"paquete_id": 2, "numero_guia": "ECO-2024-002"}', FALSE, NULL),
(3, 'nueva_asignacion', 'Nuevo paquete asignado', 'Se te ha asignado el paquete ECO-2024-004', '{"paquete_id": 4, "numero_guia": "ECO-2024-004"}', FALSE, NULL),
(2, 'nuevo_pago', 'Pago recibido', 'El cliente Brayan Store ha realizado un pago de $53,550', '{"pago_id": 1, "cliente_id": 1, "monto": 53550.00}', FALSE, NULL),
(4, 'bienvenida_colaborador', 'Bienvenida al equipo', 'Has sido agregado como colaborador de Brayan Store', '{"cliente_id": 1, "cargo": "Asistente de Logística"}', TRUE, '2024-12-15 09:00:00'),
(5, 'bienvenida_colaborador', 'Bienvenida al equipo', 'Has sido agregado como colaborador de Brayan Store', '{"cliente_id": 1, "cargo": "Coordinador de Entregas"}', TRUE, '2024-12-16 10:30:00');

-- Logs de actividad
INSERT INTO logs_actividad (usuario_id, tipo_actividad, descripcion, datos_adicionales, ip_address)
VALUES 
(1, 'login', 'Inicio de sesion exitoso', '{"metodo": "email_password"}', '192.168.1.100'),
(1, 'crear_paquete', 'Creacion de nuevo paquete', '{"numero_guia": "ECO-2024-001", "tipo": "normal"}', '192.168.1.100'),
(3, 'entrega_completada', 'Entrega de paquete completada', '{"paquete_id": 1, "numero_guia": "ECO-2024-001", "recaudo": 75000.00}', '192.168.1.102'),
(2, 'registrar_pago', 'Registro de pago de cliente', '{"pago_id": 1, "monto": 53550.00, "cliente_id": 1}', '192.168.1.101'),
(1, 'agregar_colaborador', 'Se agregó un nuevo colaborador', '{"colaborador_id": 4, "nombre": "Sofia Martinez", "cargo": "Asistente de Logística"}', '192.168.1.100'),
(4, 'login', 'Primer inicio de sesion como colaborador', '{"metodo": "email_password"}', '192.168.1.103'),
(4, 'crear_paquete', 'Creacion de paquete por colaborador', '{"numero_guia": "ECO-2024-002", "tipo": "express"}', '192.168.1.103'),
(5, 'crear_paquete', 'Creacion de paquete por colaborador', '{"numero_guia": "ECO-2024-003", "tipo": "urgente"}', '192.168.1.104');

-- Auditoría de colaboradores (NUEVO)
INSERT INTO auditoria_colaboradores (colaborador_id, cliente_id, accion, descripcion, datos_anteriores, datos_nuevos, ip_address)
VALUES 
(1, 1, 'creacion', 'Colaborador creado exitosamente', NULL, 
 JSON_OBJECT('cargo', 'Asistente de Logística', 'estado', 'activo'),
 '192.168.1.100'),
 
(2, 1, 'creacion', 'Colaborador creado exitosamente', NULL,
 JSON_OBJECT('cargo', 'Coordinador de Entregas', 'estado', 'activo'),
 '192.168.1.100');

-- Configuraciones
INSERT INTO configuraciones (clave, valor, descripcion, tipo, categoria)
VALUES 
('tarifa_base_urbana', '5000', 'Tarifa base para envios urbanos en Bogota', 'number', 'tarifas'),
('tarifa_contraentrega', '1500', 'Costo adicional para envios contraentrega', 'number', 'tarifas'),
('dias_expiracion_invitacion', '7', 'Días que dura activa una invitación de colaborador', 'number', 'colaboradores'),
('max_colaboradores_por_cliente', '10', 'Máximo número de colaboradores por cliente', 'number', 'colaboradores');

-- =====================================================
-- CONSULTAS ÚTILES DE EJEMPLO
-- =====================================================

-- Ver todos los colaboradores de un cliente
-- SELECT * FROM vista_colaboradores_completa WHERE cliente_id = 1;

-- Ver paquetes creados por colaboradores
-- SELECT * FROM vista_paquetes_completa WHERE tipo_creador = 'colaborador';

-- Verificar si un usuario tiene un permiso específico
-- SELECT verificar_permiso_colaborador(4, 1, 'crear_paquetes') AS tiene_permiso;

-- Ver invitaciones pendientes
-- SELECT * FROM invitaciones_colaboradores WHERE estado = 'pendiente' AND fecha_expiracion > NOW();

-- Ver actividad de colaboradores
-- SELECT * FROM auditoria_colaboradores WHERE cliente_id = 1 ORDER BY fecha_accion DESC;

-- Obtener cliente_id de cualquier usuario (cliente o colaborador)
-- SELECT obtener_cliente_id(4) AS mi_cliente_id;

-- Ver estadísticas de colaboradores por cliente
-- SELECT 
--     c.nombre_emprendimiento,
--     COUNT(cc.id) as total_colaboradores,
--     SUM(CASE WHEN cc.estado = 'activo' THEN 1 ELSE 0 END) as activos,
--     SUM(CASE WHEN cc.puede_agregar_colaboradores THEN 1 ELSE 0 END) as con_permiso_agregar
-- FROM clientes c
-- LEFT JOIN colaboradores_cliente cc ON c.id = cc.cliente_id
-- GROUP BY c.id, c.nombre_emprendimiento;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================